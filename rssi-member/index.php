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

<html>

<head lang="en">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <title>My Account</title>
    <script src='https://www.google.com/recaptcha/api.js?render=<?php echo SITE_KEY; ?>'></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>
<style>
    .login-panel {
        margin-top: 150px;
    }

    .x-btn:focus,
    .button:focus,
    [type="submit"]:focus {
        outline: none;
    }

    .prebanner {
        display: none;
    }
</style>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-success">
                    <div class="panel-heading">
                        <h3 class="panel-title">Sign In</h3>
                    </div>
                    <div class="panel-body">
                        <form role="form" method="post" action="index.php">
                            <fieldset>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Associate ID" name="aid" type="text" autofocus required>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Password" name="pass" type="password" value="" required>
                                </div>
                                <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" />
                                <input class="btn btn-lg btn-success btn-block" type="submit" value="Login" name="login">

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

    <div id="thoverX" class="thover"></div>
    <div id="tpopupX" class="tpopup">
        <img src="../img/b1.jpg" class="img-fluid img-responsive">
        <div id="tcloseX" class="tclose notranslate">X</div>
        <script>
            $("#tcloseX").click(function() {
                $("#tpopupX").toggleClass('hidden');
                $("#thoverX").toggleClass('hidden');
            });
        </script>
    </div>
</body>

</html>

<?php

include("database.php");

if (isset($_POST['login'])) {
    $associatenumber = strtoupper($_POST['aid']);
    $colors = $_POST['pass'];

    $check_user = "select * from rssimyaccount_members WHERE associatenumber='$associatenumber'AND colors='$colors'";

    $run = pg_query($con, $check_user);

    if (pg_num_rows($run)) {
        echo "<script>window.open('home.php','_self')</script>";

        $_SESSION['aid'] = $associatenumber; //here session is used and value of $user_email store in $_SESSION.

        $row = pg_fetch_row($run);
        $role = $row[62];
        $filterstatus = $row[31];

        $_SESSION['role'] = $role;
        $_SESSION['filterstatus'] = $filterstatus;

        //echo "<script>alert('";  
        //echo $role;
        //echo $filterstatus;
        //echo "')</script>";


    } else { ?>
        <div class="container">
            <div class="row">
                <div class="col-md-4 col-md-offset-4" style="text-align: center;">
                    <span style="color:red">Error: Login failed. Please enter valid credentials.</span>
                </div>
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