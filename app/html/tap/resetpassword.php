<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util_tap.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}

validation();


date_default_timezone_set('Asia/Kolkata');
$login_failed_dialog = false;
$newpass = "";
$oldpass = "";
$cmdtuples = 0;
if (isset($_POST['login'])) {

    $newpass = $_POST['newpass'];
    $oldpass = $_POST['oldpass'];
    if ($newpass == $oldpass) {

        $password = $_POST['currentpass'];

        $query = "select password from signup WHERE application_number='$application_number'";
        $result = pg_query($con, $query);
        $rows = pg_num_rows($result); //Som added this line.
        $user = pg_fetch_row($result);
        $existingHashFromDb = $user[0];

        $loginSuccess = password_verify($password, $existingHashFromDb);
        if ($loginSuccess) {
            $newpass = $_POST['newpass'];

            $newpass_hash = password_hash($newpass, PASSWORD_DEFAULT);
            $now = date('Y-m-d H:i:s');
            $change_password_query = "UPDATE signup SET password='$newpass_hash', password_updated_by='$application_number', password_updated_on='$now' where application_number='$application_number'";
            $result = pg_query($con, $change_password_query);
            $cmdtuples = pg_affected_rows($result);
        } else {
            $login_failed_dialog = true;
        }
    } else {
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-11316670180');
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Reset Password</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

    <style>
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        .error {
            color: red;
            list-style-type: none;
        }

        .success {
            color: green;
            list-style-type: none;
        }

        .box {
            display: flex;
        }
    </style>

</head>

<body>
<?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Reset Password</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active"><a href="#">Reset Password</a></li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="col-md-12">
                                <?php if (@$newpass != @$oldpass) { ?>
                                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: center;">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <span class="blink_me"><i class="bi bi-exclamation-triangle"></i></span>&nbsp;&nbsp;<span>ERROR: New password doesn't match the confirm password.</span>
                                    </div>
                                <?php }
                                if (@$cmdtuples == 1) { ?>
                                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: center;">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <span><i class="bi bi-check2-circle" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Your password has been changed successfully.</span>
                                    </div>
                                <?php }
                                if (@$login_failed_dialog) { ?>
                                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: center;">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <span class="blink_me"><i class="bi bi-exclamation-triangle"></i></span>&nbsp;&nbsp;<span>ERROR: The current password you entered is incorrect.</span>
                                    </div>
                                <?php } ?>
                                <div class="col" style="display: inline-block; width:100%; text-align:right">
                                    Last password updated on: <?php echo @date("d/m/Y g:i a", strtotime($password_updated_on)) ?>
                                </div>

                                <div class="col-md-4 col-md-offset-4">
                                    <div class="login-panel panel panel-default" style="margin-top: unset;">
                                        <div class="panel-heading">
                                            <b>Reset password</b>
                                        </div>
                                        <div class="panel-body">
                                            <form role="form" method="post" name="login" id="login" action="resetpassword.php">
                                                <fieldset>
                                                    <div class="form-group mb-3">
                                                        <input class="form-control" placeholder="Current password" name="currentpass" id="currentpass" type="password" required>
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <input class="form-control" placeholder="New password" name="newpass" id="newpass" type="password" required>
                                                        <label for="show-password" class="form-check-label" style="margin-top: 5px; font-weight: unset;">
                                                            <input type="checkbox" class="form-check-input" id="show-password" style="display: inline-block;"> Show password
                                                        </label>
                                                        <div id="password-message"></div>
                                                    </div>
                                                    <div class="form-group mb-3">
                                                        <input class="form-control" placeholder="Confirm password" name="oldpass" id="oldpass" type="password" required>
                                                        <div id="password_message_conf"></div>
                                                        <div id="password-message-success"></div>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <input class="btn btn-primary" type="submit" value="Update" name="login">
                                                        <p><a href="#" data-bs-toggle="modal" data-bs-target="#myModal">Password Fields User Guide</a></p>
                                                    </div>
                                                </fieldset>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Popup -->
                                <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <!-- Header -->
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="myModalLabel">Password Fields User Guide</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <!-- Body -->
                                            <div class="modal-body">
                                                <p>When filling out the form below to reset your password, please keep the following guidelines in mind:</p>
                                                <ol>
                                                    <li>The "Current Password" field is where you should enter your current password.</li>
                                                    <li>The "New Password" field is where you should enter your desired new password.</li>
                                                    <li>You can show the password you're typing in the "New Password" field by checking the "Show password" checkbox.</li>
                                                    <li>The "Confirm Password" field is where you should re-enter your new password to confirm it.</li>
                                                    <li>Once you have filled out all three password fields, click the "Update" button to submit the form.</li>
                                                </ol>
                                            </div>
                                            <!-- Footer -->
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                    var password = document.querySelector("#newpass");
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

                                <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
                                <!-- Glow Cookies v3.0.1 -->
                                <script>
                                    glowCookies.start('en', {
                                        analytics: 'G-S25QWTFJ2S',
                                        //facebookPixel: '',
                                        policyLink: 'https://www.rssi.in/disclaimer'
                                    });
                                </script>
                                <script>
                                    // Get the password input field and message element
                                    const passwordInput = document.getElementById('newpass');
                                    const passwordMessage = document.getElementById('password-message');

                                    // Add an event listener to the password input field to check for changes
                                    passwordInput.addEventListener('input', function() {
                                        const password = passwordInput.value;

                                        // Check if the password meets all the criteria
                                        const hasLength = password.length >= 8 && password.length <= 15;
                                        const hasUppercase = /[A-Z]/.test(password);
                                        const hasLowercase = /[a-z]/.test(password);
                                        const hasNumber = /[0-9]/.test(password);
                                        const hasSpecialChar = /[!@#$^&*~]/.test(password);
                                        const hasNoInvalidChars = /^[^'"\s]+$/.test(password);
                                        const hasNoCommonWords = !/password|123456|qwerty|letmein|welcome/.test(password.toLowerCase());

                                        // Set the error message based on which criteria are not met
                                        let errorMessage = '';
                                        if (!hasLength) {
                                            errorMessage += '<li class="error">✘ Password should be between 8 and 15 characters.</li>';
                                        }
                                        if (!hasUppercase) {
                                            errorMessage += '<li class="error">✘ Password should contain at least one uppercase letter.</li>';
                                        }
                                        if (!hasLowercase) {
                                            errorMessage += '<li class="error">✘ Password should contain at least one lowercase letter.</li>';
                                        }
                                        if (!hasNumber) {
                                            errorMessage += '<li class="error">✘ Password should contain at least one number.</li>';
                                        }
                                        if (!hasSpecialChar) {
                                            errorMessage += '<li class="error">✘ Password should contain at least one special character.</li>';
                                        }
                                        if (!hasNoInvalidChars) {
                                            errorMessage += '<li class="error">✘ Password should not contain single quotes, double quotes, or spaces.</li>';
                                        }
                                        if (!hasNoCommonWords) {
                                            errorMessage += '<li class="error">✘ Password should not be a common word.</li>';
                                        }


                                        // Display the error message or a success message if all criteria are met
                                        if (errorMessage) {
                                            passwordMessage.innerHTML = errorMessage;
                                            passwordInput.setCustomValidity('Please fix the errors in the password field.');
                                        } else {
                                            passwordMessage.innerHTML = '<li class="success">✔ Password meets all criteria.</li>';
                                            passwordInput.setCustomValidity('');
                                        }
                                    });
                                </script>
                                <script>
                                    const newPassword = document.getElementById('newpass');
                                    const confirmPassword = document.getElementById('oldpass');
                                    const passwordMessage_conf = document.getElementById('password_message_conf');
                                    const passwordMessageSuccess = document.getElementById('password-message-success');
                                    const form = document.getElementById('login');

                                    const checkPasswords = () => {
                                        if (newPassword.value !== confirmPassword.value) {
                                            confirmPassword.setCustomValidity("Please fix the errors in the password field.");
                                            passwordMessage_conf.innerHTML = '<p class="error">✘ New password and confirm password do not match.</p>';
                                            passwordMessageSuccess.innerHTML = '';
                                        } else {
                                            confirmPassword.setCustomValidity("");
                                            passwordMessageSuccess.innerHTML = '<p class="success">✔ New password and confirm password match!</p>';
                                            passwordMessage_conf.innerHTML = '';
                                        }
                                    }

                                    form.addEventListener('submit', (event) => {
                                        checkPasswords();
                                        if (passwordMessage_conf.innerHTML) {
                                            event.preventDefault();
                                        }
                                    });

                                    confirmPassword.addEventListener('input', () => {
                                        checkPasswords();
                                    });

                                    newPassword.addEventListener('input', () => {
                                        checkPasswords();
                                    });
                                </script>
                            </div>
                        </div>
                    </div><!-- End Reports -->
                </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

</body>

</html>