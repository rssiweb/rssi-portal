<?php
session_start(); //session starts here 

define('SITE_KEY', '6LfJRc0aAAAAAEhNPCD7ju6si7J4qRUCBSN_8RsL');
define('SECRET_KEY', '6LfJRc0aAAAAAFuZLLd3_7KFmxQ7KPCZmLIiYLDH');

if (isset($_SESSION['aid']) && $_SESSION['aid']) {
    header("Location: home.php");
    exit;
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

include("database.php");

if (isset($_POST['login'])) {
    $associatenumber = strtoupper($_POST['aid']);
    $colors = $_POST['pass'];

    $check_user = "select * from rssimyaccount_members WHERE associatenumber='$associatenumber'AND colors='$colors'";

    $run = pg_query($con, $check_user);

    //if (pg_num_rows($run)) {

    // Do the login stuff...

    if (pg_num_rows($run)) {
        if (isset($_SESSION["login_redirect"])) {
            header("Location: " . $_SESSION["login_redirect"]);
            unset($_SESSION["login_redirect"]);
        } else {
            header("Location: ../rssi-member/home.php");
        }

        $_SESSION['aid'] = $associatenumber; //here session is used and value of $user_email store in $_SESSION.

        $row = pg_fetch_row($run);
        $role = $row[62];
        $engagement = $row[48];
        $filterstatus = $row[35];

        $_SESSION['role'] = $role;
        $_SESSION['engagement'] = $engagement;
        $_SESSION['filterstatus'] = $filterstatus;
        $uip = $_SERVER['HTTP_X_REAL_IP'];

        // instead of REMOTE_ADDR use HTTP_X_REAL_IP to get real client IP
        $query = "INSERT INTO userlog_member VALUES (DEFAULT,'$_POST[aid]','$_SERVER[HTTP_X_REAL_IP]','$date')";
        $result = pg_query($con, $query);

        //echo "<script>alert('";
        //echo $engagement;
        //echo "')</script>";
    } else {
        $login_failed_dialog = true;
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
        <div class="logo-area"> </div>
    </div>
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <!--<img src="..//images/phoenix1b.png" alt="Phoenix" class="center">-->
                        <b>Phoenix</b>
                    </div>
                    <div class="panel-body">
                        <form role="form" method="post" name="login" action="index.php">
                            <fieldset>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Associate ID" name="aid" type="text" autofocus required>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Password" name="pass" id="pass" type="password" value="" required>
                                    <label for="show-password" class="field__toggle" style="margin-top: 5px;font-weight: unset;">
                                        <input type="checkbox" class="checkbox" id="show-password" class="field__toggle-input" style="display: inline-block;" />&nbsp;Show password
                                    </label>
                                </div>
                                <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" />
                                <input style="font-family:'Google Sans'; float: right;" class="btn btn-primary btn-block" type="submit" value="Sign in" name="login">

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
</body>

</html>

<?php if($login_failed_dialog) {?>
    <div class="container">
    <div class="row">
        <div class="col-md-4 col-md-offset-4" style="text-align: center;">
            <span style="color:red">Error: Login failed. Please enter valid credentials.</span>
        </div>
    </div>
    </div>
<?php } ?>
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
    <?php include '../css/style.css'; ?><?php include '../css/addstyle.css'; ?>
    label {
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