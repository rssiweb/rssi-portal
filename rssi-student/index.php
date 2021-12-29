<?php
session_start(); //session starts here

define('SITE_KEY', '6LfJRc0aAAAAAEhNPCD7ju6si7J4qRUCBSN_8RsL');
define('SECRET_KEY', '6LfJRc0aAAAAAFuZLLd3_7KFmxQ7KPCZmLIiYLDH');

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
        //echo "Succes!";
    } else {
        //echo "You are a Robot!!";
    }
}

?>
<?php
date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>

<head lang="en">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        <?php include '../css/addstyle.css'; ?>
    </style>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <title>My Profile</title>
    <script src='https://www.google.com/recaptcha/api.js?render=<?php echo SITE_KEY; ?>'></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>
<style>
    .prebanner {
        display: none;
    }

    .center {
        display: block;
        margin-left: auto;
        margin-right: auto;
        width: 50%;
    }

    .glow-banner-description {
        font-size: .8em !important;
    }

    .cookie-consent-btn,
    .cookie-consent-btn-secondary {
        font-size: .7em !important;
    }
</style>

<body>
    <div class="box">
        <img src="..//images/phoenix.png" alt="Phoenix" style="width:50%;" class="center">
        <br>
        <h2>Sign in</h2>
        <br>
        <form role="form" method="post" name="login" action="index.php"><br>
            <div class="inputBox">
                <input type="text" name="sid" required onkeyup="this.setAttribute('value', this.value);">
                <label>Student ID</label>
            </div>
            <div class="inputBox">
                <input type="password" name="pass" required onkeyup="this.setAttribute('value', this.value);">
                <label>Password</label>
            </div>
            <div class="inputBox">
                <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" />
            </div>
            <input type="submit" name="login" value="Sign in">
        </form>
    </div>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    <?php

    include("database.php");

    if (isset($_POST['login'])) {
        $student_id = strtoupper($_POST['sid']);
        $colors = $_POST['pass'];

        $check_user = "select * from rssimyprofile_student WHERE student_id='$student_id'AND colors='$colors'";

        $run = pg_query($con, $check_user);

        if (pg_num_rows($run)) {
            if (isset($_SESSION["login_redirect"])) {
                header("Location: " . $_SESSION["login_redirect"]);
                unset($_SESSION["login_redirect"]);
            } else {
                header("Location: ../rssi-student/home.php");
            }

            $_SESSION['sid'] = $student_id; //here session is used and value of $user_email store in $_SESSION.

            $row = pg_fetch_row($run);
            $filterstatus = $row[39];
            $feesflag = $row[50];

            $_SESSION['filterstatus'] = $filterstatus;
            $_SESSION['feesflag'] = $feesflag;
            $uip = $_SERVER['REMOTE_ADDR'];

            $query = "INSERT INTO userlog_member VALUES (DEFAULT,'$_POST[sid]','$_POST[pass]','$_SERVER[REMOTE_ADDR]','$date')";
            $result = pg_query($con, $query);
        } else { ?>
            <div class="container">
                <div class="row absolutetop" style="text-align: center;">
                    <span style="color:red; font-size:14px">Error: Login failed. Please enter valid credentials.</span>
                </div>
            </div>


    <?php }
    }
    ?>

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