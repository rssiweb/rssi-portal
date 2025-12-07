<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_recruiters.php");
include("../../util/email.php");

$date = date('Y-m-d H:i:s');
$login_failed_dialog = "";
$registration_success = "";
$registration_error = "";

function afterlogin($con, $date)
{
    $username = $_SESSION['rid'];
    $user_query = pg_query($con, "select password_updated_by,password_updated_on,default_pass_updated_on from recruiters WHERE email='$username'");
    $row = pg_fetch_row($user_query);
    $password_updated_by = $row[0];
    $password_updated_on = $row[1];
    $default_pass_updated_on = $row[2];

    passwordCheck($password_updated_by, $password_updated_on, $default_pass_updated_on);

    function getUserIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipList[0]);
            return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : $_SERVER['REMOTE_ADDR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    $user_ip = getUserIpAddr();
    pg_query($con, "INSERT INTO userlog_member VALUES (DEFAULT,'$username','$user_ip','$date')");

    if (isset($_SESSION["login_redirect"])) {
        $params = "";
        if (isset($_SESSION["login_redirect_params"])) {
            foreach ($_SESSION["login_redirect_params"] as $key => $value) {
                $params .= "$key=$value&";
            }
            unset($_SESSION["login_redirect_params"]);
        }
        header("Location: " . $_SESSION["login_redirect"] . '?' . $params);
        unset($_SESSION["login_redirect"]);
    } else {
        header("Location: home.php");
    }
    exit;
}

if (isLoggedIn("rid")) {
    afterlogin($con, $date);
}

function checkLogin($con, $date)
{
    global $login_failed_dialog;
    $username = $_POST['rid'];
    $password = $_POST['pass'];

    $query = "SELECT password, absconding FROM recruiters WHERE email='$username'";
    $result = pg_query($con, $query);
    if ($result) {
        $user = pg_fetch_assoc($result);
        if ($user) {
            $existingHashFromDb = $user['password'];
            $absconding = $user['absconding'];
            if (password_verify($password, $existingHashFromDb)) {
                if (!empty($absconding)) {
                    $login_failed_dialog = "Your account has been flagged as inactive. Please contact support.";
                } else {
                    $_SESSION['rid'] = $username;
                    afterlogin($con, $date);
                }
            } else {
                $login_failed_dialog = "Incorrect username or password.";
            }
        } else {
            $login_failed_dialog = "User not found.";
        }
    } else {
        $login_failed_dialog = "Error executing query.";
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'register') {
    // Basic validation
    $full_name = pg_escape_string($con, $_POST['full_name']);
    $company_name = pg_escape_string($con, $_POST['company_name']);
    $email = pg_escape_string($con, $_POST['email']);
    $phone = pg_escape_string($con, $_POST['phone']);
    $company_address = pg_escape_string($con, $_POST['company_address']);

    // Check if email already exists
    $check_email = pg_query($con, "SELECT email FROM recruiters WHERE email='$email'");
    if (pg_num_rows($check_email) > 0) {
        $registration_error = "Email already registered. Please login or use another email.";
    } else {
        // Generate temporary password
        $temp_password = bin2hex(random_bytes(8)); // 16 character password
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

        // Insert new recruiter with pending status
        $query = "INSERT INTO recruiters (
            full_name, 
            company_name, 
            email, 
            phone, 
            company_address, 
            password, 
            is_verified, 
            created_at
        ) VALUES (
            '$full_name',
            '$company_name',
            '$email',
            '$phone',
            '$company_address',
            '$hashed_password',
            false,
            '$date'
        )";

        $result = pg_query($con, $query);

        if ($result) {
            // Send email with login credentials
            $email_data = [
                "name" => $full_name,
                "email" => $email,
                "temp_password" => $temp_password,
                "now" => date("d/m/Y g:i a")
            ];

            if (sendEmail("recruiter_registration", $email_data, $email, false)) {
                $registration_success = "Registration successful! A temporary password has been sent to your email. Your account is pending approval.";

                // Redirect to prevent form resubmission on refresh
                header("Location: index.php?tab=register&success=1");
                exit;
            } else {
                $registration_error = "Registration successful but email notification failed. Please contact support.";
            }
        } else {
            $registration_error = "Registration failed. Please try again.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        checkLogin($con, $date);
    }
}
?>

<?php
// Check if the form is submitted with the correct identifier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_identifier']) && $_POST['form_identifier'] === 'forgot_password_form') {
    $email = $_POST['reset_username'];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email address.');</script>";
        exit;
    }

    // Check if the email exists in the database
    try {
        // Query to check if the email exists and fetch the user's name
        $query = "SELECT full_name FROM recruiters WHERE email = $1";
        $stmt = pg_prepare($con, "check_email", $query);
        $result = pg_execute($con, "check_email", array($email));

        if (pg_num_rows($result) > 0) {
            // Fetch the user's name
            $row = pg_fetch_assoc($result);
            $name = $row['full_name'];
            $reset_auth_code_timestamp = date('Y-m-d H:i:s');

            // Generate a 20-character random alphanumeric string
            $reset_auth_code = bin2hex(random_bytes(10)); // 10 bytes = 20 hexadecimal characters

            // Update the reset_auth_code column in the database
            $update_query = "UPDATE recruiters SET reset_auth_code = $1, reset_auth_code_timestamp=$3 WHERE email = $2";
            $update_stmt = pg_prepare($con, "update_reset_auth_code", $update_query);
            $update_result = pg_execute($con, "update_reset_auth_code", array($reset_auth_code, $email, $reset_auth_code_timestamp));

            // Check if the update was successful
            if ($update_result && pg_affected_rows($update_result) > 0) {
                // Prepare email data
                $email_data = [
                    "name" => $name, // User's name fetched from the database
                    "reset_auth_code" => $reset_auth_code, // Generated reset auth code
                    "now" => date("d/m/Y g:i a", strtotime($reset_auth_code_timestamp)), // Format the future date, // Current date and time
                ];

                // Send email
                if (!empty($email)) {
                    sendEmail("forgot_pass_link", $email_data, $email, false);
                }

                echo "<script>alert('A password reset link has been sent to your email address. Please check your inbox.');</script>";
            } else {
                echo "<script>alert('Failed to update the reset auth code. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('No account found with this email address. Please enter a valid username.');</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('Database error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Check for success parameter from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $registration_success = "Registration successful! A temporary password has been sent to your email. Your account is pending approval.";
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RSSI - Recruiter Portal</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        @media (max-width: 767px) {
            .logo {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .logo span {
                margin: 5px 0;
            }
        }

        .by-line {
            background-color: #CE1212;
            padding: 1px 5px;
            border-radius: 0px;
            font-size: small !important;
            color: white !important;
            margin-left: 10%;
        }

        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-color: #0d6efd;
        }

        .form-container {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        .required-field::after {
            content: " *";
            color: #dc3545;
        }

        /* Hide success alert when empty */
        .alert:empty {
            display: none !important;
        }
    </style>
</head>

<body>
    <main>
        <div class="container">
            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 col-md-10 d-flex flex-column align-items-center justify-content-center">
                            <div class="container text-center py-4">
                                <div class="logo">
                                    <img src="../img/phoenix.png" alt="Phoenix Logo" width="40%">
                                    <h4 class="mt-3">Recruiter Portal</h4>
                                    <p class="text-muted">Find and hire the best talent for your organization</p>
                                </div>
                            </div>

                            <div class="form-container">
                                <ul class="nav nav-tabs nav-justified mb-4" id="authTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'login') ? 'active' : ''; ?>"
                                            id="login-tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#login"
                                            type="button"
                                            role="tab"
                                            onclick="updateURL('login')">Login</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'register') ? 'active' : ''; ?>"
                                            id="register-tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#register"
                                            type="button"
                                            role="tab"
                                            onclick="updateURL('register')">Register</button>
                                    </li>
                                </ul>

                                <div class="tab-content" id="authTabsContent">
                                    <!-- Login Tab -->
                                    <div class="tab-pane fade <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'login') ? 'show active' : ''; ?>" id="login" role="tabpanel">
                                        <div class="pt-2 pb-2">
                                            <h5 class="card-title text-center pb-0 fs-4">Login to Your Account</h5>
                                            <p class="text-center small">Enter your username & password to login</p>
                                        </div>
                                        <form class="row g-3 needs-validation" role="form" method="post" name="login" action="index.php?tab=login">
                                            <div class="col-12">
                                                <label for="yourUsername" class="form-label">Email Address</label>
                                                <div class="input-group has-validation">
                                                    <span class="input-group-text" id="inputGroupPrepend"><i class="bi bi-person"></i></span>
                                                    <input type="email" name="rid" class="form-control" id="tid" placeholder="Enter your email address" required>
                                                    <div class="invalid-feedback">Please enter your email address.</div>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <label for="pass" class="form-label">Password</label>
                                                <input type="password" name="pass" class="form-control" id="pass" placeholder="Enter your password" required>
                                                <div class="invalid-feedback">Please enter your password!</div>
                                            </div>

                                            <div class="col-12">
                                                <div class="form-check">
                                                    <label for="show-password" class="form-label">
                                                        <input type="checkbox" class="form-check-input" id="show-password" class="field__toggle-input" style="display: inline-block;"> Show password
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <button class="btn btn-primary w-100" type="submit" name="login">Login</button>
                                            </div>

                                            <div class="col-12">
                                                <p class="small mb-0">Forgot password? <a href="#" data-bs-toggle="modal" data-bs-target="#popup">Click here</a></p>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Register Tab -->
                                    <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'register') ? 'show active' : ''; ?>" id="register" role="tabpanel">
                                        <div class="pt-2 pb-2">
                                            <h5 class="card-title text-center pb-0 fs-4">Create New Account</h5>
                                            <p class="text-center small">Register as a recruiter to post jobs</p>
                                        </div>

                                        <?php if (!empty($registration_success)): ?>
                                            <div class="alert alert-success">
                                                <?php echo $registration_success; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($registration_error)): ?>
                                            <div class="alert alert-danger">
                                                <?php echo $registration_error; ?>
                                            </div>
                                        <?php endif; ?>

                                        <form class="row g-3 needs-validation" role="form" method="post" name="register" action="index.php?tab=register" novalidate>
                                            <input type="hidden" name="form_type" value="register">

                                            <div class="col-md-6">
                                                <label for="full_name" class="form-label required-field">Full Name</label>
                                                <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Enter your full name" required>
                                                <div class="invalid-feedback">Please enter your full name.</div>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="company_name" class="form-label required-field">Company Name</label>
                                                <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Enter your company name" required>
                                                <div class="invalid-feedback">Please enter your company name.</div>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="email" class="form-label required-field">Email Address</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
                                                </div>
                                                <div class="invalid-feedback">Please enter a valid email address.</div>
                                            </div>

                                            <div class="col-md-6">
                                                <label for="phone" class="form-label required-field">Phone Number</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                                    <input type="tel" class="form-control" id="phone" name="phone" pattern="[0-9]{10}" placeholder="Enter 10-digit phone number" required>
                                                </div>
                                                <div class="invalid-feedback">Please enter a valid 10-digit phone number.</div>
                                            </div>

                                            <div class="col-12">
                                                <label for="company_address" class="form-label required-field">Company Address</label>
                                                <textarea class="form-control" id="company_address" name="company_address" rows="3" placeholder="Enter complete company address" required></textarea>
                                                <div class="invalid-feedback">Please enter your company address.</div>
                                            </div>

                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                                    <label class="form-check-label" for="terms">
                                                        I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                                                    </label>
                                                    <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <button class="btn btn-success w-100" type="submit">Register Now</button>
                                            </div>

                                            <div class="col-12">
                                                <p class="small mb-0 text-center">Already have an account? <a href="#" onclick="switchToLogin(); return false;">Login here</a></p>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="credits mt-4">
                                Designed by <a href="https://www.rssi.in/">rssi.in</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Recruiter Agreement</h6>
                    <p>By registering as a recruiter, you agree to:</p>
                    <ul>
                        <li>Post only legitimate job openings</li>
                        <li>Provide accurate company information</li>
                        <li>Maintain confidentiality of candidate information</li>
                        <li>Not discriminate against any candidate</li>
                        <li>Follow all applicable labor laws</li>
                        <li>Respond to applicants in a timely manner</li>
                    </ul>
                    <p>Your account requires approval before you can post jobs. You will receive an email notification once your account is approved.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="popup" tabindex="-1" aria-labelledby="popupLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="popupLabel">Forgot password?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="forgot-password-form" action="#" method="POST" onsubmit="showSpinner()">
                        <div class="mb-3">
                            <label for="reset_username" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="reset_username" name="reset_username" placeholder="Enter your email address" required>
                            <div class="form-text help-text">
                                Please enter the email address associated with your account. We will send you a link to reset your password.
                            </div>
                        </div>
                        <input type="hidden" name="form_identifier" value="forgot_password_form">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="send-email-button" form="forgot-password-form">
                        <span id="button-text">Send Email</span>
                        <span id="spinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        // Function to update URL parameter
        function updateURL(tab) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);

            // Remove success parameter if present
            if (url.searchParams.has('success')) {
                url.searchParams.delete('success');
            }

            window.history.replaceState({}, '', url);
        }

        // Function to switch to login tab
        function switchToLogin() {
            var loginTab = new bootstrap.Tab(document.getElementById('login-tab'));
            loginTab.show();
            updateURL('login');
            return false;
        }

        // Show/hide password
        document.getElementById('show-password').addEventListener('change', function() {
            var passwordField = document.getElementById('pass');
            passwordField.type = this.checked ? 'text' : 'password';
        });

        // Form validation
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()

        // Show spinner on forgot password
        function showSpinner() {
            const submitButton = document.getElementById('send-email-button');
            submitButton.disabled = true;
            const spinner = document.getElementById('spinner');
            spinner.style.display = 'inline-block';
            const buttonText = document.getElementById('button-text');
            buttonText.textContent = 'Sending...';
        }

        // Clear form validation when switching tabs
        document.getElementById('authTabs').addEventListener('shown.bs.tab', function(event) {
            var forms = document.querySelectorAll('.needs-validation');
            forms.forEach(function(form) {
                form.classList.remove('was-validated');
            });
        });

        // Initialize based on URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');

            if (tab === 'register') {
                var registerTab = new bootstrap.Tab(document.getElementById('register-tab'));
                registerTab.show();
            } else {
                var loginTab = new bootstrap.Tab(document.getElementById('login-tab'));
                loginTab.show();
            }

            <?php if (!empty($login_failed_dialog)): ?>
                // Show login error modal
                var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                errorModal.show();
            <?php endif; ?>

            // Clear form data on successful registration to prevent resubmission
            <?php if (!empty($registration_success)): ?>
                document.querySelector('form[name="register"]').reset();
            <?php endif; ?>
        });

        // Update URL when tab is clicked
        document.querySelectorAll('#authTabs button[data-bs-toggle="tab"]').forEach(function(tab) {
            tab.addEventListener('click', function() {
                const tabId = this.id === 'login-tab' ? 'login' : 'register';
                updateURL(tabId);
            });
        });
    </script>

    <?php if (!empty($login_failed_dialog)) { ?>
        <div class="modal" tabindex="-1" role="dialog" id="errorModal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Error: Login Failed</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo $login_failed_dialog ?>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
</body>

</html>