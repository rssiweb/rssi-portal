<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("sid")) {
    header("Location: index.php");
}

$user_check = $_SESSION['sid'];

if (!$_SESSION['sid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
} else if ($_SESSION['feesflag'] == 'd') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "profile.php";';
    echo '</script>';
}
?>

<?php
include("student_data.php");
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Classroom</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>@media (max-width:767px) {
            .cw3 {
                width: 80% !important;
                margin-top: 2%;
            }

        }

        .cw3 {
            width: 20%;
        }
    </style>
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

</head>

<body>
    <?php $home_active = 'active'; ?>
    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class=col style="text-align: right;"><?php echo @$badge?></div>
                <div class="alert alert-info alert-dismissible" role="alert" style="text-align: -webkit-center;">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <span class="noticet">Now you can download your ID card from your profile > My Document<!--<a href="document.php" target="_self">My Document</a></span>-->&nbsp;&nbsp;<span class="label label-danger blink_me">new</span>
                </div>
                <?php
                if (@$class == 10 || @$module == 'National') {
                ?>
                    <!--<div class="alert alert-info alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        Viva Schedule has been published. Please check&nbsp;<span class="noticet">
                            <a href="https://drive.google.com/file/d/1WUpyFeTYXKM4Yg3AOO-1zNJ_qHaVnL1P/view" target="_blank">here..</a></span>
                        //&nbsp;&nbsp;<span class="label label-warning blink_me">update</span>
                    </div>-->
                    <!--<div class="alert alert-warning alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        Written exam Schedule has been published. Please check&nbsp;<span class="noticet">
                            <a href="https://drive.google.com/file/d/1Q_pWvJCGxz1U5YbSL1fevzp801pX9FOy/view" target="_blank">here..</a></span>&nbsp;&nbsp;<span class="label label-danger blink_me">new</span>
                    </div>-->

                <?php
                } else {
                }
                ?>

                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Class URL</th>
                                <th scope="col">Quick link</th>
                                <th scope="col">Annual Fee</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="line-height: 2;"><?php echo $classurl ?></td>
                                <td style="line-height: 2;"><?php echo $lastlogin ?></td>
                                <td style="line-height: 2;"><?php echo $fees ?></td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>

            <div class="clearfix"></div>

        </section>
    </section>

    <!-- Messenger Chat Plugin Code -->
    <div id="fb-root"></div>

    <!-- Your Chat Plugin code -->
    <div id="fb-customer-chat" class="fb-customerchat">
    </div>

    <script>
        var chatbox = document.getElementById('fb-customer-chat');
        chatbox.setAttribute("page_id", "215632685291793");
        chatbox.setAttribute("attribution", "biz_inbox");

        window.fbAsyncInit = function() {
            FB.init({
                xfbml: true,
                version: 'v12.0'
            });
        };

        (function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s);
            js.id = id;
            js.src = 'https://connect.facebook.net/en_US/sdk/xfbml.customerchat.js';
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));
    </script>
</body>

</html>