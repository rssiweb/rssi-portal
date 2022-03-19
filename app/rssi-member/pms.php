<?php
session_start();
// Storing Session
define('SITE_KEY', '6LfJRc0aAAAAAEhNPCD7ju6si7J4qRUCBSN_8RsL');
define('SECRET_KEY', '6LfJRc0aAAAAAFuZLLd3_7KFmxQ7KPCZmLIiYLDH');
include("../util/login_util.php");
include("database.php");
if (!isLoggedIn("aid")) {
    header("Location: index.php");
}
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
} else if ($_SESSION['role'] != 'Admin') {

    header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
    exit;
}
if ($_POST) {
    $user_id = $_POST['userid'];
    $password = $_POST['newpass'];
    $type = $_POST['type'];
    $newpass_hash = password_hash($password, PASSWORD_DEFAULT);

    if ($type == "Associate") {
        $change_password_query = "UPDATE rssimyaccount_members SET password='$newpass_hash' where associatenumber='$user_id'";
    } else {
        $change_password_query = "UPDATE rssimyprofile_student SET password='$newpass_hash' where student_id='$user_id'";
    }
    $success = pg_query($con, $change_password_query);
    if ($success) {
        //echo "sone success";
    } else {
        //echo "failed";
    }
}
?>
<?php
include("member_data.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>RSSI-PMS</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
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
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <section class="box" style="padding: 2%;">
                        <p>Please enter both the User ID and the new password to reset the user's password.</p><br><br>
                        <form name="pms" action="pms.php" method="POST">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">
                                    <select name="type" class="form-control" style="width:max-content; display:inline-block" required>
                                        <?php if ($id == null) { ?>
                                            <option value="" disabled selected hidden>Association Type</option>
                                        <?php
                                        } else { ?>
                                            <option hidden selected><?php echo $type ?></option>
                                        <?php }
                                        ?>
                                        <option>Associate</option>
                                        <option>Student</option>
                                    </select>
                                    <input type="text" name="userid" class="form-control" style="width:max-content; display:inline-block" placeholder="User ID" value="" required>
                                    <input type="password" name="newpass" id="newpass" class="form-control" style="width:max-content; display:inline-block" placeholder="New password" value="" required>
                                </div>

                            </div>

                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" class="btn btn-danger" style="outline: none;">
                                    <i class="fas fa-sync-alt"></i>&nbsp;Update password</button>
                            </div>
                            <br>
                            <label for="show-password" class="field__toggle" style="margin-top: 5px;font-weight: unset;">
                                <input type="checkbox" class="checkbox" id="show-password" class="field__toggle-input" style="display: inline-block;" />&nbsp;Show password
                            </label>
                        </form>

                </div>
            </div>
            </div>
        </section>
        </div>
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


    <!-- Back top -->
    <script>
        $(document).ready(function() {
            $(window).scroll(function() {
                if ($(this).scrollTop() > 50) {
                    $('#back-to-top').fadeIn();
                } else {
                    $('#back-to-top').fadeOut();
                }
            });
            // scroll body to 0px on click
            $('#back-to-top').click(function() {
                $('body,html').animate({
                    scrollTop: 0
                }, 400);
                return false;
            });
        });
    </script>
    <a id="back-to-top" href="#" class="go-top" role="button"><i class="fa fa-angle-up"></i></a>
</body>

</html>