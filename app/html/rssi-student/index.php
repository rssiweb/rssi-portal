<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

define('SITE_KEY', '6LfJRc0aAAAAAEhNPCD7ju6si7J4qRUCBSN_8RsL');
define('SECRET_KEY', '6LfJRc0aAAAAAFuZLLd3_7KFmxQ7KPCZmLIiYLDH');

$date = date('Y-m-d H:i:s');
$login_failed_dialog = false;

function afterlogin($con, $date)
{

    $student_id = $_SESSION['sid']; //here session is used and value of $user_email store in $_SESSION.

    $user_query = "select * from rssimyprofile_student WHERE student_id='$student_id'";
    $result = pg_query($con, $user_query);

    $row = pg_fetch_row($result);
    $password_updated_by = $row[47];
    $password_updated_on = $row[48];
    $default_pass_updated_by = $row[52];
    $default_pass_updated_on = $row[53];
    // $role = $row[62];

    // instead of REMOTE_ADDR use HTTP_X_REAL_IP to get real client IP
    $query = "INSERT INTO userlog_member VALUES (DEFAULT,'$student_id','$_SERVER[HTTP_X_REAL_IP]','$date')";
    $result = pg_query($con, $query);

    if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {
        echo '<script type="text/javascript">';
        echo 'window.location.href = "defaultpasswordreset.php";';
        echo '</script>';
    }

    if (isset($_SESSION["login_redirect"])) {
        $params = "";
        if (isset($_SESSION["login_redirect_params"])) {
            foreach ($_SESSION["login_redirect_params"] as $key => $value) {
                $params = $params . "$key=$value&";
            }
            unset($_SESSION["login_redirect_params"]);
        }
        header("Location: " . $_SESSION["login_redirect"] . '?' . $params);
        unset($_SESSION["login_redirect"]);
    } else {
        // Line 25 we have the role value
        // if ($role !='Member') {
        header("Location: home.php");
        // } else {
        //     header("Location: myprofile.php");
        // }
    }
}
if (isLoggedIn("sid")) {
    afterlogin($con, $date);
    exit;
}

function checkLogin($con, $date)
{
    global $login_failed_dialog;
    $student_id = strtoupper($_POST['sid']);
    $colors = $_POST['pass'];

    $query = "select password from rssimyprofile_student WHERE student_id='$student_id'";
    $result = pg_query($con, $query);
    $user = pg_fetch_row($result);
    $existingHashFromDb = $user[0];

    @$loginSuccess = password_verify($colors, $existingHashFromDb);

    // Do the login stuff...

    if ($loginSuccess) {
        $_SESSION['sid'] = $student_id;
        afterlogin($con, $date);
    } else {
        $login_failed_dialog = true;
    }
}

function getCaptcha($SecretKey)
{
    $Response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . SECRET_KEY . "&response={$SecretKey}");
    $Return = json_decode($Response);
    return $Return;
}

if ($_POST) {
    $islocal = $_ENV['IS_LOCAL'] ?? "false";
    if ($islocal == "true") {
        if (isset($_POST['login'])) {
            checkLogin($con, $date);
        }
    } else {
        $Return = getCaptcha($_POST['g-recaptcha-response']);
        if ($Return->success == true && $Return->score > 0.5) {
            if (isset($_POST['login'])) {
                checkLogin($con, $date);
            }
        } else {
            $login_failed_dialog = true;
        }
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <title>My Account</title>
    <script src='https://www.google.com/recaptcha/api.js?render=<?php echo SITE_KEY; ?>'></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/addstyle.css">
    <style>
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

        .btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none !important;
        }
    </style>
    <!--------------- POP-UP BOX ------------
-------------------------------------->
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            padding-top: 100px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
        }

        /* Modal Content */

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 100vh;
        }

        @media (max-width:767px) {
            .modal-content {
                width: 50vh;
            }
        }

        /* The Close Button */

        .close {
            color: #aaaaaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            text-align: right;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="page-topbar">
        <div class="logo-area"> </div>
        <!-- <img src="https://res.cloudinary.com/hs4stt5kg/image/upload/v1659756398/indian-flag-20_v5idmk.gif" width="50" class="over" /> -->
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
                                    <input class="form-control" placeholder="Student ID" name="sid" type="text" autofocus required>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Password" name="pass" id="pass" type="password" value="" required>
                                    <label for="show-password" class="field__toggle" style="margin-top: 5px;font-weight: unset;">
                                        <input type="checkbox" class="checkbox" id="show-password" class="field__toggle-input" style="display: inline-block;" />&nbsp;Show
                                        password
                                    </label>
                                </div>
                                <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" />
                                <input style="font-family:'Google Sans'; float: right;" class="btn btn-primary btn-block" type="submit" value="Sign in" name="login">
                                <br><br>
                                <p style="text-align: right;"><a id="myBtn" href="javascript:void(0)">Forgot password?</a></p>
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

    <?php if ($login_failed_dialog) { ?>
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
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

    <div id="myModal" class="modal">

        <!-- Modal content -->
        <div class="modal-content">
            <span class="close">&times;</span>
            Please contact RSSI Admin at 7980168159 or email at info@rssi.in
        </div>

    </div>
    <script>
        var modal = document.getElementById("myModal");
        var btn = document.getElementById("myBtn");
        var span = document.getElementsByClassName("close")[0];
        btn.onclick = function() {
            modal.style.display = "block";
        }
        span.onclick = function() {
            modal.style.display = "none";
        }
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

    <!--<div id="thoverX" class="thover"></div>
<div id="tpopupX" class="tpopup">
    <img src="/images/pride3.jpg" class="img-fluid img-responsive hidden-xs" style="display: block;margin-left: auto;margin-right: auto;">
    <p style="display: block; margin-left: 5%;margin-right: 5%; text-align: left;">This Pride Month, RSSI launches #AgarTumSaathHo, to bring together LGBTQ Community and their straight allies.<br><br> Families and friends really matter! We know that most young people from the LGBTQ community grow up having to hide their identity
        because they fear being judged and rejected even by their loved ones. But this has a severe impact on their self-esteem and sense of self-worth. Supportive parents, families, friends, teachers, and peers can all play an important role in helping
        build self-esteem and a positive sense of self among LGBTQ youth, including gender non-conforming teens. This Pride month, RSSI NGO aims to bring forward and celebrate these stories of support, courage, love, and of understanding.</p>
    <div class="embed-responsive embed-responsive-16by9">
        <iframe class="embed-responsive-item" src="https://www.youtube.com/embed/e677aw0T0Pk" allowfullscreen></iframe>
    </div>

    <div id="tcloseX" class="tclose notranslate">X</div>
    <script>
        $("#tcloseX").click(function() {
            $("#tpopupX").toggleClass('hidden');
            $("#thoverX").toggleClass('hidden');
        });

        $("#thoverX").click(function() {
            $("#tpopupX").toggleClass('hidden');
            $("#thoverX").toggleClass('hidden');
        });
    </script>
</div>-->
    <?php include("../../util/footer.php"); ?>
</body>

</html>