<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}


date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');
$login_failed_dialog = false;
$newpass = "";
$oldpass = "";
$cmdtuples = 0;
if (isset($_POST['login'])) {

    $newpass = $_POST['newpass'];
    $oldpass = $_POST['oldpass'];
    if ($newpass == $oldpass) {

        $password = $_POST['currentpass'];

        $query = "select password from rssimyaccount_members WHERE associatenumber='$associatenumber'";
        $result = pg_query($con, $query);
        $rows = pg_num_rows($result); //Som added this line.
        $user = pg_fetch_row($result);
        $existingHashFromDb = $user[0];

        $loginSuccess = password_verify($password, $existingHashFromDb);
        if ($loginSuccess) {
            $newpass = $_POST['newpass'];

            $newpass_hash = password_hash($newpass, PASSWORD_DEFAULT);
            $now = date('Y-m-d H:i:s');
            $change_password_query = "UPDATE rssimyaccount_members SET password='$newpass_hash', password_updated_by='$associatenumber', password_updated_on='$now' where associatenumber='$associatenumber'";
            $result = pg_query($con, $change_password_query);
            $cmdtuples = pg_affected_rows($result);
        } else {
            $login_failed_dialog = true;
        }
    } else {
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Associate-Reset Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <link rel="stylesheet" href="/css/style.css" />

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <!------ Include the above in your HEAD tag ---------->
    <style>
        .checkbox {
            padding: 0;
            margin: 0;
            vertical-align: bottom;
            position: relative;
            top: 0px;
            overflow: hidden;
        }

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
    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <?php if (@$newpass != @$oldpass) { ?>

                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: New password does't match the confirm password.</span>
                    </div>
                <?php }
                if (@$cmdtuples == 1) { ?>

                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span><i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Your password has been changed successfully.</span>
                    </div>
                <?php }
                if (@$login_failed_dialog) { ?>
                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: The current password you entered is incorrect.</span>
                    </div>

                <?php } ?>
                <div class="col" style="display: inline-block; width:100%; text-align:right">
                    Last password updated on: <?php echo @date("d/m/Y g:i a", strtotime($password_updated_on)) ?>
                </div>
                <section class="box" style="padding: 2%;">
                    <div class="col-md-4 col-md-offset-4">
                        <div class="login-panel panel panel-default" style="margin-top: unset;">
                            <div class="panel-heading">
                                <b>Reset password</b>
                            </div>
                            <div class="panel-body">
                                <form role="form" method="post" name="login" id="login" action="resetpassword.php">
                                    <fieldset>
                                        <div class="form-group">
                                            <input class="form-control" placeholder="Current password" name="currentpass" id="currentpass" type="password" value="" required>
                                        </div>
                                        <div class="form-group">
                                            <input class="form-control" placeholder="New password" name="newpass" id="newpass" type="password" value="" required>
                                            <label for="show-password" class="field__toggle" style="margin-top: 5px;font-weight: unset;">
                                                <input type="checkbox" class="checkbox" id="show-password" class="field__toggle-input" style="display: inline-block;" />&nbsp;Show password
                                            </label>
                                            <div id="password-message"></div>
                                        </div>
                                        <div class="form-group">
                                            <input class="form-control" placeholder="Confirm password" name="oldpass" id="oldpass" type="password" value="" required>
                                            <div id="password_message_conf"></div>
                                            <div id="password-message-success"></div>
                                        </div>
                                        <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" />
                                        <input style="font-family:'Google Sans'; float: right;" class="btn btn-primary btn-block" type="submit" value="Update" name="login">
                                        <br><br><br>
                                        <p style="text-align: right;"><a href="#" data-toggle="modal" data-target="#myModal">Password Fields User Guide</a></p>

                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </section>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Password Fields User Guide</h4>
                </div>
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
</body>

</html>