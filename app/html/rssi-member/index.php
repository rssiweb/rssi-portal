<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");

$date = date('Y-m-d H:i:s');
$login_failed_dialog = "";

// Function to log user login
function logUserLogin($con, $username, $date)
{
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
    pg_query($con, "INSERT INTO userlog_member VALUES (DEFAULT, '$username', '$user_ip', '$date')");
}

function afterlogin($con, $date)
{
    $user_check = $_SESSION['aid'];

    // Log the user login
    logUserLogin($con, $user_check, $date);

    $user_query = pg_query($con, "SELECT password_updated_by, password_updated_on, default_pass_updated_on FROM rssimyaccount_members WHERE email='$user_check'");
    $row = pg_fetch_row($user_query);
    $password_updated_by = $row[0];
    $password_updated_on = $row[1];
    $default_pass_updated_on = $row[2];

    passwordCheck($password_updated_by, $password_updated_on, $default_pass_updated_on);

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

if (isLoggedIn("aid")) {
    afterlogin($con, $date);
}

function generateOTP($length = 6)
{
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

function sendOTPEmail($con, $username)
{
    // Generate OTP
    $otp = generateOTP();
    $otp_created_at = date('Y-m-d H:i:s');

    // Hash OTP before storing (just like passwords)
    $hashedOtp = password_hash($otp, PASSWORD_DEFAULT);

    // Store hashed OTP in database
    $update_query = "UPDATE rssimyaccount_members SET otp_code = '$hashedOtp', otp_created_at = '$otp_created_at', otp_attempts = 0 WHERE email = '$username'";
    pg_query($con, $update_query);

    // Get user details
    $user_query = pg_query($con, "SELECT fullname FROM rssimyaccount_members WHERE email='$username'");
    $user = pg_fetch_assoc($user_query);
    $name = $user['fullname'];

    // Prepare email data
    $email_data = [
        "name" => $name,
        "otp_code" => $otp, // Send plain text OTP in email
        "valid_time" => "10 minutes" // OTP validity period
    ];

    // Send email
    sendEmail("otp_verification", $email_data, $username, false);

    return true;
}

function verifyOTP($con, $username, $entered_otp)
{
    // Get stored OTP details
    $query = "SELECT otp_code, otp_created_at, otp_attempts FROM rssimyaccount_members WHERE email='$username'";
    $result = pg_query($con, $query);
    $user = pg_fetch_assoc($result);

    if (!$user || empty($user['otp_code'])) {
        return false;
    }

    // Verify OTP using password_verify (just like password verification)
    $stored_otp_hash = $user['otp_code'];
    $otp_created_at = $user['otp_created_at'];
    $otp_attempts = $user['otp_attempts'];

    // Check if OTP attempts exceeded
    if ($otp_attempts >= 5) {
        return "max_attempts";
    }

    // Check if OTP is expired (10 minutes)
    $current_time = time();
    $otp_time = strtotime($otp_created_at);
    if (($current_time - $otp_time) > 600) { // 10 minutes in seconds
        return "expired";
    }

    // Verify OTP using password_verify
    if (password_verify($entered_otp, $stored_otp_hash)) {
        // Clear OTP data after successful verification
        pg_query($con, "UPDATE rssimyaccount_members SET otp_code = NULL, otp_created_at = NULL, otp_attempts = 0 WHERE email = '$username'");
        return true;
    } else {
        // Increment failed attempts
        pg_query($con, "UPDATE rssimyaccount_members SET otp_attempts = otp_attempts + 1 WHERE email = '$username'");
        return false;
    }
}

function checkLogin($con, $date)
{
    global $login_failed_dialog;
    $username = strtolower($_POST['aid']);
    $password = $_POST['pass'];

    $query = "SELECT password, absconding, twofa_enabled, twofa_secret FROM rssimyaccount_members WHERE email='$username'";
    $result = pg_query($con, $query);
    if ($result) {
        $user = pg_fetch_assoc($result);
        if ($user) {
            $existingHashFromDb = $user['password'];
            $absconding = $user['absconding'];
            $twofa_enabled = $user['twofa_enabled'];
            $twofa_secret = $user['twofa_secret'];

            if (password_verify($password, $existingHashFromDb)) {
                if (!empty($absconding)) {
                    $login_failed_dialog = "Your account has been flagged as inactive. Please contact support.";
                } else {
                    // Set session for OTP / 2FA verification
                    $_SESSION['otp_verification_user'] = $username;

                    if ($twofa_enabled == 't' && !empty($twofa_secret)) {
                        // Redirect to Authenticator TOTP verification page
                        header("Location: setup_2fa_verify.php"); // new page for TOTP verification
                        exit;
                    } else {
                        // Continue email OTP flow
                        if (sendOTPEmail($con, $username)) {
                            header("Location: index.php?otp_verification=1");
                            exit;
                        } else {
                            $login_failed_dialog = "Failed to send OTP. Please try again.";
                        }
                    }
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

// Handle AJAX OTP verification
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    if (isset($_POST['verify_otp_ajax']) && isset($_SESSION['otp_verification_user'])) {
        $username = $_SESSION['otp_verification_user'];
        $entered_otp = $_POST['otp_code'];

        $verification_result = verifyOTP($con, $username, $entered_otp);

        if ($verification_result === true) {
            // OTP verified successfully
            $_SESSION['aid'] = $username;
            unset($_SESSION['otp_verification_user']);

            // Log the user login
            logUserLogin($con, $username, $date);

            echo json_encode(['success' => true, 'redirect' => 'home.php']);
            exit;
        } else {
            $error_msg = "Invalid OTP. Please try again.";
            if ($verification_result === "expired") {
                $error_msg = "OTP has expired. Please request a new one.";
            } elseif ($verification_result === "max_attempts") {
                $error_msg = "Too many failed attempts. Please request a new OTP.";
            }

            echo json_encode(['success' => false, 'error' => $error_msg]);
            exit;
        }
    }

    if (isset($_POST['resend_otp_ajax']) && isset($_SESSION['otp_verification_user'])) {
        $username = $_SESSION['otp_verification_user'];
        if (sendOTPEmail($con, $username)) {
            echo json_encode(['success' => true, 'message' => 'New OTP has been sent to your email.']);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to resend OTP. Please try again.']);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        checkLogin($con, $date);
    } elseif (isset($_POST['verify_otp'])) {
        // Traditional form submission fallback
        $username = $_SESSION['otp_verification_user'];
        $entered_otp = $_POST['otp_code'];

        $verification_result = verifyOTP($con, $username, $entered_otp);

        if ($verification_result === true) {
            // OTP verified successfully, complete login
            $_SESSION['aid'] = $username;
            unset($_SESSION['otp_verification_user']);
            afterlogin($con, $date);
        } else {
            if ($verification_result === "expired") {
                $login_failed_dialog = "OTP has expired. Please request a new one.";
            } elseif ($verification_result === "max_attempts") {
                $login_failed_dialog = "Too many failed attempts. Please request a new OTP.";
            } else {
                $login_failed_dialog = "Invalid OTP. Please try again.";
            }

            // Stay on OTP verification page
            header("Location: index.php?otp_verification=1&error=1");
            exit;
        }
    } elseif (isset($_POST['resend_otp'])) {
        // Resend OTP - traditional form submission fallback
        if (isset($_SESSION['otp_verification_user'])) {
            $username = $_SESSION['otp_verification_user'];
            if (sendOTPEmail($con, $username)) {
                header("Location: index.php?otp_verification=1&resent=1");
                exit;
            } else {
                $login_failed_dialog = "Failed to resend OTP. Please try again.";
                header("Location: index.php?otp_verification=1&error=1");
                exit;
            }
        } else {
            $login_failed_dialog = "Session expired. Please login again.";
            header("Location: index.php");
            exit;
        }
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
        $query = "SELECT fullname FROM rssimyaccount_members WHERE email = $1";
        $stmt = pg_prepare($con, "check_email", $query);
        $result = pg_execute($con, "check_email", array($email));

        if (pg_num_rows($result) > 0) {
            // Fetch the user's name
            $row = pg_fetch_assoc($result);
            $name = $row['fullname'];
            $reset_auth_code_timestamp = date('Y-m-d H:i:s');

            // Generate a 20-character random alphanumeric string
            $reset_auth_code = bin2hex(random_bytes(10)); // 10 bytes = 20 hexadecimal characters

            // Update the reset_auth_code column in the database
            $update_query = "UPDATE rssimyaccount_members SET reset_auth_code = $1, reset_auth_code_timestamp=$3 WHERE email = $2";
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
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

        .otp-input {
            letter-spacing: 10px;
            font-size: 1.5rem;
            text-align: center;
            font-weight: bold;
        }

        .resend-btn {
            background: none;
            border: none;
            color: #0d6efd;
            text-decoration: underline;
            padding: 0;
            cursor: pointer;
        }

        .resend-btn:hover {
            color: #0a58ca;
        }

        .resend-btn:disabled {
            color: #6c757d;
            cursor: not-allowed;
        }

        .verify-btn {
            position: relative;
        }

        .btn-loading .btn-text {
            visibility: hidden;
        }

        .btn-loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
            border: 3px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: button-loading-spinner 1s ease infinite;
        }

        @keyframes button-loading-spinner {
            from {
                transform: rotate(0turn);
            }

            to {
                transform: rotate(1turn);
            }
        }
    </style>
</head>

<body>
    <?php include 'banner.php'; ?>
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

                            <?php if (isset($_GET['otp_verification'])): ?>
                                <!-- OTP Verification Form -->
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="pt-4 pb-2">
                                            <h5 class="card-title text-center pb-0 fs-4">Two-Factor Authentication</h5>
                                            <p class="text-center small">Enter the OTP sent to your email</p>
                                            <?php if (isset($_GET['resent'])): ?>
                                                <div class="alert alert-success" role="alert">
                                                    New OTP has been sent to your email.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <form class="row g-3 needs-validation" role="form" method="post" name="otp_verification" action="index.php" id="otpForm">
                                            <div class="col-12">
                                                <label for="otp_code" class="form-label">OTP Code</label>
                                                <input type="text" name="otp_code" class="form-control otp-input" id="otp_code" maxlength="6" required placeholder="000000">
                                                <div class="form-text">Check your email for the 6-digit code</div>
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-primary w-100 verify-btn" type="submit" name="verify_otp" id="verifyBtn">
                                                    <span class="btn-text">Verify OTP</span>
                                                </button>
                                            </div>
                                            <div class="col-12 text-center">
                                                <p class="small mb-0">Didn't receive the code?
                                                    <button type="button" name="resend_otp" class="resend-btn" id="resendBtn">Resend OTP</button>
                                                    <span id="countdown" class="text-muted small"></span>
                                                </p>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Regular Login Form -->
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
                                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                                    <input type="email" name="aid" class="form-control" placeholder="Username" required>
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
                                                    <input type="checkbox" class="form-check-input" id="show-password">
                                                    <label for="show-password" class="form-label">Show password</label>
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
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                        document.addEventListener("DOMContentLoaded", function() {
                                            var password = document.querySelector("#pass");
                                            var toggle = document.querySelector("#show-password");
                                            toggle.addEventListener("click", function() {
                                                password.type = this.checked ? "text" : "password";
                                            });
                                        });
                                    </script>
                                </div>
                            <?php endif; ?>

                            <div class="credits">
                                Designed by <a href="https://www.rssi.in/">rssi.in</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php if (!empty($login_failed_dialog) && !isset($_GET['otp_verification'])) { ?>
        <div class="modal" tabindex="-1" role="dialog" id="errorModal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Error: Login Failed</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p><?php echo $login_failed_dialog ?></p>
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
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
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

        // OTP Verification Handling
        document.addEventListener("DOMContentLoaded", function() {
            const otpInput = document.getElementById('otp_code');
            const verifyBtn = document.getElementById('verifyBtn');
            const resendBtn = document.getElementById('resendBtn');
            const countdownEl = document.getElementById('countdown');
            const otpForm = document.getElementById('otpForm');

            <?php if (isset($_GET['error'])): ?>
                // Show error alert if OTP verification failed
                alert('<?php echo $login_failed_dialog; ?>');
            <?php endif; ?>

            if (otpInput) {
                otpInput.focus();

                // Auto-submit when 6 digits are entered
                otpInput.addEventListener('input', function() {
                    if (this.value.length === 6) {
                        startVerification();
                    }
                });
            }

            if (verifyBtn) {
                verifyBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    startVerification();
                });
            }

            function startVerification() {
                const otpValue = otpInput.value.trim();

                if (otpValue.length !== 6) {
                    alert('Please enter a valid 6-digit OTP code.');
                    otpInput.focus();
                    return;
                }

                // Disable inputs and show loading state
                otpInput.disabled = true;
                verifyBtn.disabled = true;
                verifyBtn.classList.add('btn-loading');

                // Submit the form via AJAX
                const formData = new FormData();
                formData.append('verify_otp_ajax', '1');
                formData.append('otp_code', otpValue);

                fetch('index.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Redirect on success
                            window.location.href = data.redirect;
                        } else {
                            // Show error message
                            alert(data.error);

                            // Re-enable inputs
                            otpInput.value = '';
                            otpInput.disabled = false;
                            verifyBtn.disabled = false;
                            verifyBtn.classList.remove('btn-loading');
                            otpInput.focus();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred during verification. Please try again.');

                        // Re-enable inputs
                        otpInput.disabled = false;
                        verifyBtn.disabled = false;
                        verifyBtn.classList.remove('btn-loading');
                    });
            }

            // Resend OTP countdown timer
            if (resendBtn && countdownEl) {
                let countdown = 30;
                resendBtn.disabled = true;

                const countdownInterval = setInterval(() => {
                    countdownEl.textContent = ` (${countdown}s)`;
                    countdown--;

                    if (countdown < 0) {
                        clearInterval(countdownInterval);
                        resendBtn.disabled = false;
                        countdownEl.textContent = '';
                    }
                }, 1000);

                resendBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Disable resend button during request
                    resendBtn.disabled = true;
                    countdownEl.textContent = ' (Sending...)';

                    // Create a form for resend request
                    const formData = new FormData();
                    formData.append('resend_otp_ajax', '1');

                    fetch('index.php', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert(data.message);

                                // Reset countdown
                                countdown = 30;
                                resendBtn.disabled = true;

                                const newCountdownInterval = setInterval(() => {
                                    countdownEl.textContent = ` (${countdown}s)`;
                                    countdown--;

                                    if (countdown < 0) {
                                        clearInterval(newCountdownInterval);
                                        resendBtn.disabled = false;
                                        countdownEl.textContent = '';
                                    }
                                }, 1000);
                            } else {
                                alert(data.error);
                                resendBtn.disabled = false;
                                countdownEl.textContent = '';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred. Please try again.');
                            resendBtn.disabled = false;
                            countdownEl.textContent = '';
                        });
                });
            }
        });
    </script>
</body>

</html>