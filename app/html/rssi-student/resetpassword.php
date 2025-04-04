<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("sid")) {
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

        $query = "select password from rssimyprofile_student WHERE student_id='$student_id'";
        $result = pg_query($con, $query);
        $rows = pg_num_rows($result); //Som added this line.
        $user = pg_fetch_row($result);
        $existingHashFromDb = $user[0];

        $loginSuccess = password_verify($password, $existingHashFromDb);
        if ($loginSuccess) {
            $newpass = $_POST['newpass'];

            $newpass_hash = password_hash($newpass, PASSWORD_DEFAULT);
            $now = date('Y-m-d H:i:s');
            $change_password_query = "UPDATE rssimyprofile_student SET password='$newpass_hash', password_updated_by='$associatenumber', password_updated_on='$now' where student_id='$student_id'";
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
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Student-Reset Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
<link rel="stylesheet" href="/css/style.css" />
    
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <!------ Include the above in your HEAD tag ---------->

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

</head>

<body>
    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <?php if (@$newpass != @$oldpass) { ?>

                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span class="blink_me"><i class="bi bi-exclamation-triangle"></i></span>&nbsp;&nbsp;<span>ERROR: New password does't match the confirm password.</span>
                    </div>
                <?php }
                if (@$cmdtuples == 1) { ?>

                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span><i class="bi bi-check2-circle" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Your password has been changed successfully.</span>
                    </div>
                <?php }
                if (@$login_failed_dialog) { ?>
                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
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
                                <form role="form" method="post" name="login" action="resetpassword.php">
                                    <fieldset>
                                        <div class="form-group">
                                            <input class="form-control" placeholder="Current password" name="currentpass" id="currentpass" type="password" required>
                                        </div>
                                        <div class="form-group">
                                            <input class="form-control" placeholder="New password" name="newpass" id="newpass" type="password" required>
                                            <label for="show-password" class="field__toggle" style="margin-top: 5px;font-weight: unset;">
                                                <input type="checkbox" class="checkbox" id="show-password" class="field__toggle-input" style="display: inline-block;" />&nbsp;Show password
                                            </label>
                                        </div>
                                        <div class="form-group">
                                            <input class="form-control" placeholder="Confirm password" name="oldpass" id="oldpass" type="password" required>
                                        </div>
                                        <input style="font-family:'Google Sans'; float: right;" class="btn btn-primary btn-block" type="submit" value="Update" name="login">

                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <div class="col-md-12">
                <div class="clearfix"></div>
        </section>
    </section>

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
    </style>
</body>

</html>
