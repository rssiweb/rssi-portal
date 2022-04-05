<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
    header("Location: index.php");
}
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
} else if ($_SESSION['filterstatus'] != 'Active') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}

define('SITE_KEY', '6LfJRc0aAAAAAEhNPCD7ju6si7J4qRUCBSN_8RsL');
define('SECRET_KEY', '6LfJRc0aAAAAAFuZLLd3_7KFmxQ7KPCZmLIiYLDH');
include("database.php");

if (isset($_SESSION['aid']) && $_SESSION['aid']) {
    $associatenumber = $_SESSION['aid'];
    $user_query = "select * from rssimyaccount_members WHERE associatenumber='$associatenumber'";
    $result = pg_query($con, $user_query);

    $row = pg_fetch_row($result);
    $associatenumber = $row[1];
    $fullname = $row[2];
    $lastupdatedon = $row[27];
    $photo = $row[28];
    $filterstatus = $row[35];
    $password_updated_on = $row[81];

    $_SESSION['fullname'] = $fullname;
    $_SESSION['associatenumber'] = $associatenumber;
    $_SESSION['photo'] = $photo;
    $_SESSION['lastupdatedon'] = $lastupdatedon;
    $_SESSION['filterstatus'] = $filterstatus;
    $_SESSION['password_updated_on'] = $password_updated_on;
}

if ($_POST) {
    function getCaptcha($SecretKey)
    {
        $Response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . SECRET_KEY . "&response={$SecretKey}");
        $Return = json_decode($Response);
        return $Return;
    }
    $Return = getCaptcha($_POST['g-recaptcha-response']);
    //var_dump($Return);
    if ($Return->success == true && $Return->score > 0.5) {
        // echo "Succes!";
    } else {
        //echo "You are a Robot!!";
    }
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
            $now=date('Y-m-d H:i:s');
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
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <!------ Include the above in your HEAD tag ---------->

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
        });
    </script>
    <style>
        @media (max-width:767px) {
            td {
                width: 100%
            }

            .page-topbar .logo-area {
                width: 240px !important;
                margin-top: 2.5%;
            }
        }

        .page-topbar,
        .logo-area {
            -webkit-transition: 0ms;
            -moz-transition: 0ms;
            -o-transition: 0ms;
            transition: 0ms;
        }
    </style>

</head>

<body>
<div class="page-topbar">
    <div class="logo-area"> </div>
</div>
    <section>
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <?php if (@$newpass != @$oldpass) { ?>

                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: New password does't match the confirm password.</span>
                    </div> 
                <?php } if (@$cmdtuples == 1) { ?>

                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <span><i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Your password has been changed successfully. Please sign out and sign in using the new password.&nbsp; <a href="logout.php" target="_self" class="btn btn-danger btn-sm" role="button"><i class="glyphicon glyphicon-log-out"></i>&nbsp;Sign Out</a></span>
                    </div>
                <?php } if (@$login_failed_dialog) { ?>
                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: The current password you entered is incorrect.</span>
                    </div>

                <?php } if (@$newpass==null) { ?>
                    <div class="alert alert-info alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span class="blink_me"><i class="glyphicon glyphicon-info-sign"></i></span>&nbsp;&nbsp;<span>Please change your default password</span>
                    </div>

                <?php }?>
                <section class="box" style="padding: 2%;">
                    <div class="col-md-4 col-md-offset-4">
                        <div class="login-panel panel panel-default" style="margin-top: unset;">
                            <div class="panel-heading">
                                <b>Reset password</b>
                            </div>
                            <div class="panel-body">
                                <form role="form" method="post" name="login" action="defaultpasswordreset.php">
                                    <fieldset>
                                        <div class="form-group">
                                            <input class="form-control" placeholder="Current password" name="currentpass" id="currentpass" type="password" value="" required>
                                        </div>
                                        <div class="form-group">
                                            <input class="form-control" placeholder="New password" name="newpass" id="newpass" type="password" value="" required>
                                            <label for="show-password" class="field__toggle" style="margin-top: 5px;font-weight: unset;">
                                                <input type="checkbox" class="checkbox" id="show-password" class="field__toggle-input" style="display: inline-block;" />&nbsp;Show password
                                            </label>
                                        </div>
                                        <div class="form-group">
                                            <input class="form-control" placeholder="Confirm password" name="oldpass" id="oldpass" type="password" value="" required>
                                        </div>
                                        <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" />
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

    <!--protected by reCAPTCHA-->
    <script>
        grecaptcha.ready(function() {
            grecaptcha.execute('<?php echo SITE_KEY; ?>', {
                    action: 'homepage'
                })
                .then(function(token) {
                    //console.log(token);
                    document.getElementById('g-recaptcha-response').value = token;
                });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
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