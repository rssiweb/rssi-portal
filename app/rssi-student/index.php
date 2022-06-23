<?php
session_start(); //session starts here
include("database.php");
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
        // echo "Succes!";
    } else {
        //echo "You are a Robot!!";
    }
}

date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');
$login_failed_dialog = false;



if (isset($_POST['login'])) {
    $student_id = strtoupper($_POST['sid']);
    $colors = $_POST['pass'];

    $query = "select password from rssimyprofile_student WHERE student_id='$student_id'";
    $result = pg_query($con, $query);
    $user = pg_fetch_row($result);
    $existingHashFromDb = $user[0];

    @$loginSuccess = password_verify($colors, $existingHashFromDb);

    if ($loginSuccess) {
        $_SESSION['sid'] = $student_id; //here session is used and value of $user_email store in $_SESSION.

        $user_query = "select * from rssimyprofile_student WHERE student_id='$student_id'";
        $result = pg_query($con, $user_query);

        $row = pg_fetch_row($result);
        $password_updated_by = $row[47];


        $_SESSION['password_updated_by'] = $password_updated_by;

        $query = "INSERT INTO userlog_member VALUES (DEFAULT,'$_POST[sid]','$_SERVER[HTTP_X_REAL_IP]','$date')";
        $result = pg_query($con, $query);

        if ($_SESSION['password_updated_by'] == null || ($_SESSION['password_updated_by'] == 'VTHN20008')) {
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
            header("Location: home.php");
        }
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <title>My Profile</title>
    <script src='https://www.google.com/recaptcha/api.js?render=<?php echo SITE_KEY; ?>'></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
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
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 1;
            /* Sit on top */
            padding-top: 100px;
            /* Location of the box */
            left: 0;
            top: 0;
            width: 100%;
            /* Full width */
            height: 100%;
            /* Full height */
            overflow: auto;
            /* Enable scroll if needed */
            background-color: rgb(0, 0, 0);
            /* Fallback color */
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
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
            policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
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
        // Get the modal
        var modal = document.getElementById("myModal");

        // Get the button that opens the modal
        var btn = document.getElementById("myBtn");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // When the user clicks the button, open the modal 
        btn.onclick = function() {
            modal.style.display = "block";
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
    <?php include("../util/footer.php"); ?>
</body>

</html>