<?php
session_start();
// Storing Session
include("../util/login_util.php");

if(! isLoggedIn("sid")){
    header("Location: index.php");
}
$user_check = $_SESSION['sid'];

if(!$_SESSION['sid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;  
  }
  else if ($_SESSION['filterstatus']!='Active') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">'; 
    echo 'alert("Access Denied. You are not authorized to access this web page.");'; 
    echo 'window.location.href = "home.php";';
    echo '</script>';
}

define('SITE_KEY', '6LfJRc0aAAAAAEhNPCD7ju6si7J4qRUCBSN_8RsL');
define('SECRET_KEY', '6LfJRc0aAAAAAFuZLLd3_7KFmxQ7KPCZmLIiYLDH');
include("database.php");

if (isset($_SESSION['sid']) && $_SESSION['sid']) {
    $student_id = $_SESSION['sid'];
    $user_query = "select * from rssimyprofile_student WHERE student_id='$student_id'";
    $result = pg_query($con, $user_query);

    $row = pg_fetch_row($result);
    $student_id = $row[1];
    $studentname = $row[3];

    $_SESSION['studentname'] = $studentname;
    $_SESSION['student_id'] = $student_id;
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
        echo "You are a Robot!!";
    }
}

date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');
$login_failed_dialog = false;

if (isset($_POST['login'])) {

    $newpass = $_POST['newpass'];
    $oldpass = $_POST['oldpass'];
    if ($newpass == $oldpass) {

        $password = $_POST['currentpass'];

        $query = "select password from rssimyprofile_student WHERE student_id='$student_id'";
        $result = pg_query($con, $query);
        $user = pg_fetch_row($result);
        $existingHashFromDb = $user[0];

        $loginSuccess = password_verify($password, $existingHashFromDb);
        if ($loginSuccess) {
            $newpass = $_POST['newpass'];

            $newpass_hash = password_hash($newpass, PASSWORD_DEFAULT);

            $change_password_query = "UPDATE rssimyprofile_student SET password='$newpass_hash' where student_id='$student_id'";
            $result = pg_query($con, $change_password_query);

            header("Location: ../rssi-student/index.php");
        } else {
            $login_failed_dialog = true;
        }
    } else {
    }
}
?>

<html>

<head lang="en">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <title>My Account</title>
    <script src='https://www.google.com/recaptcha/api.js?render=<?php echo SITE_KEY; ?>'></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>

<body>
    <div class="page-topbar">
        <div class="logo-area">
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <!--<img src="..//images/phoenix1b.png" alt="Phoenix" class="center">-->
                        <b>Reset password</b>
                    </div>
                    <div class="panel-body">
                        <form role="form" method="post" name="login" action="resetpassword.php">
                            <p style="text-align: right;line-height: 2;font-size:small">Not <?php echo strtok($studentname, ' ') ?> (<?php echo @$student_id ?>)? <span class="noticea"><a href="logout.php" target="_self">Switch Account</a></span></p>
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
                                <input style="font-family:'Google Sans'; float: right;" class="btn btn-primary btn-block" type="submit" onclick="return Validate()" value="Update" name="login">

                                <!-- Change this to a button or input when using this as a form -->
                                <!--  <a href="index.html" class="btn btn-lg btn-success btn-block">Login</a> -->
                            </fieldset>
                        </form>
                    </div>
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
    <script type="text/javascript">
        function Validate() {
            var password = document.getElementById("newpass").value;
            var confirmPassword = document.getElementById("oldpass").value;
            if (password != confirmPassword) {
                alert("New password and confirm password don't match.");
                return false;
            }
            alert("Your password has been changed successfully.");
            return true;
        }
    </script>
</body>

</html>

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
    <?php include '../css/style.css';
    ?><?php include '../css/addstyle.css';

        ?>label {
        display: block;
        padding-left: 15px;
        text-indent: -15px;
    }

    .checkbox {
        padding: 0;
        margin: 0;
        vertical-align: bottom;
        position: relative;
        top: 0px;
        overflow: hidden;
    }
</style>