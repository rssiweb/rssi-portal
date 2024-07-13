<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

$date = date('Y-m-d H:i:s');
$login_failed_dialog = false;

function afterlogin($con, $date)
{
    $student_id = $_SESSION['sid'];

    $user_query = "select * from rssimyprofile_student WHERE student_id='$student_id'";
    $result = pg_query($con, $user_query);

    $row = pg_fetch_row($result);
    $password_updated_by = $row[47];
    $password_updated_on = $row[48];
    $default_pass_updated_by = $row[52];
    $default_pass_updated_on = $row[53];

    $query = "INSERT INTO userlog_member VALUES (DEFAULT,'$student_id','$_SERVER[REMOTE_ADDR]','$date')";
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
        header("Location: home.php");
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
    $password = $_POST['pass'];

    $query = "select password from rssimyprofile_student WHERE student_id='$student_id'";
    $result = pg_query($con, $query);
    $user = pg_fetch_row($result);
    $existingHashFromDb = $user[0];

    $loginSuccess = password_verify($password, $existingHashFromDb);

    if ($loginSuccess) {
        $_SESSION['sid'] = $student_id;
        afterlogin($con, $date);
    } else {
        $login_failed_dialog = true;
    }
}

if ($_POST) {
    if (isset($_POST['login'])) {
        checkLogin($con, $date);
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
        toggle.addEventListener("click", function() {
            password.type = toggle.checked ? "text" : "password";
        });
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

    <?php include("../../util/footer.php"); ?>
</body>
</html>