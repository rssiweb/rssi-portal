<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_tap.php");
include("../../util/email.php");

$date = date('Y-m-d H:i:s');
$login_failed_dialog = "";

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
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RSSI-My Account</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        @media (max-width: 767px) {

            /* Styles for mobile devices */
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
    </style>
</head>

<body>
    <main>
        <div class="container">
            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                            <div class="container text-center py-4">
                                <div class="logo">
                                    <img src="../img/phoenix.png" alt="Phoenix Logo" width="40%">
                                </div>
                            </div>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4">Login to Your Account</h5>
                                        <p class="text-center small">Enter your username & password to login</p>
                                    </div>
                                    <form class="row g-3 needs-validation" role="form" method="post" name="login" action="index.php">

                                        <div class="col-12">
                                            <label for="yourUsername" class="form-label">Username</label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text" id="inputGroupPrepend"><i class="bi bi-person"></i></span>
                                                <input type="email" name="rid" class="form-control" id="tid" placeholder="Username" required>
                                                <div class="invalid-feedback">Please enter your username.</div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="pass" class="form-label">Password</label>
                                            <input type="password" name="pass" class="form-control" id="pass" required>
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
                            </div>

                            <div class="credits">
                                Designed by <a href="https://www.rssi.in/">rssi.in</a>
                            </div>

                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        var password = document.querySelector("#pass");
        var toggle = document.querySelector("#show-password");
        // I'm using the "(click)" event to make this works cross-browser.
        toggle.addEventListener("click", handleToggleClick, false);
        // I handle the toggle click, changing the TYPE of password input.
        function handleToggleClick(event) {
            if (this.checked) {
                console.warn("Change input 'type' to: text");
                password.type = "text";
            } else {
                console.warn("Change input 'type' to: password");
                password.type = "password";
            }
        }
    </script>

    <?php if ($login_failed_dialog) { ?>
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
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var myModal = new bootstrap.Modal(document.getElementById('errorModal'));
                myModal.show();
            });
        </script>
    <?php } ?>

    <!-- Popup -->
    <div class="modal fade" id="popup" tabindex="-1" aria-labelledby="popupLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="popupLabel">Forgot password?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <!-- Form with ID and correct action -->
                    <form id="forgot-password-form" action="#" method="POST" onsubmit="showSpinner()">
                        <!-- Email Input -->
                        <div class="mb-3">
                            <label for="reset_username" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="reset_username" name="reset_username" placeholder="Enter your email address" required>
                            <div class="form-text help-text">
                                Please enter the email address associated with your account. We will send you a link to reset your password.
                            </div>
                        </div>

                        <!-- Hidden Form Identifier -->
                        <input type="hidden" name="form_identifier" value="forgot_password_form">
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <!-- Close Button -->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <!-- Send Email Button with Spinner -->
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
        function showSpinner() {
            // Disable the submit button to prevent multiple submissions
            const submitButton = document.getElementById('send-email-button');
            submitButton.disabled = true;

            // Show the spinner
            const spinner = document.getElementById('spinner');
            spinner.style.display = 'inline-block';

            // Change the button text (optional)
            const buttonText = document.getElementById('button-text');
            buttonText.textContent = 'Sending...';
        }
    </script>

</body>

</html>