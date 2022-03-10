<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
    header("Location: index.php");
}
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
} else if ($_SESSION['filterstatus'] != 'Active') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>
<?php
include("member_data.php");
?>
<?php
include("database.php");
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>ID Card_<?php echo $fullname ?></title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>

        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }

        @media print {
            .noprint {
                visibility: hidden;
                position: absolute;
                left: 0;
                top: 0;
            }
        }
    </style>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
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

    <div class="col2 left noprint" style="display: inline;">
        <div class="col-md-12">
            <div class="row">
                <div class="col noprint" style="display: inline-block; width:100%;margin-left:10%; margin-top:2%">

                    <button type="button" onclick="window.print()" name="print" class="btn btn-success btn-sm" style="outline: none;">
                        <span class="glyphicon glyphicon-save"></span>&nbsp;Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="col2 left" style="display: inline;">
        <div class="col-md-12">
            <div class="row">
                <div class="col" style="display: inline-block; width:100%;margin-left:10%; margin-top:2%">

                    <img src=<?php echo $feedback ?> width=246px />
                </div>
            </div>
        </div>
    </div>

</body>

</html>