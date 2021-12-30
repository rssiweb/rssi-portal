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
        echo "Succes!";
    } else {
        echo "You are a Robot!!";
    }
}

?>
<?php
      date_default_timezone_set('Asia/Kolkata');
      $date = date('Y-m-d H:i:s'); 
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
    <title>My Profile</title>
    <style>
    <?php include '../css/addstyle.css'; ?>
</style>
    <script src='https://www.google.com/recaptcha/api.js?render=<?php echo SITE_KEY; ?>'></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-default">
                    <div class="panel-heading">
                        <img src="..//images/phoenix1b.png" alt="Phoenix" class="center">
                    </div>
                    <div class="panel-body">
                        <form role="form" method="post" name="login" action="index.php">
                            <fieldset>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Student ID" name="sid" type="text" autofocus required>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Password" name="pass" type="password" value="" required>
                                </div>
                                <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" />
                                <input style="font-family:'Google Sans';" class="btn btn-lg btn-primary btn-block" type="submit" value="Sign in" name="login">

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
    </script>
</body>

</html>

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
        $filterstatus= $row[39];
        $feesflag = $row[50];

       $_SESSION['filterstatus'] = $filterstatus;
       $_SESSION['feesflag'] = $feesflag;
       $uip=$_SERVER['REMOTE_ADDR'];

       $query = "INSERT INTO userlog_member VALUES (DEFAULT,'$_POST[sid]','$_POST[pass]','$_SERVER[REMOTE_ADDR]','$date')";
       $result = pg_query($con, $query); 

    } else {?>
        <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4" style="text-align: center;">
            <span style="color:red">Error: Login failed. Please enter valid credentials.</span>
            </div></div></div>
        
        
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