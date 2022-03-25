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
} else if ($_SESSION['filterstatus'] != 'Active') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>
<?php
include("student_data.php");
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
    <title>ID Card_<?php echo $studentname ?></title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu&family=Ubuntu+Condensed&display=swap" rel="stylesheet">
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>@media (min-width:767px) {
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

        .first-txt {
            position: absolute;
            top: 70%;
            width: 235px !important;
            text-align: center;
            font-family: "Ubuntu";
            font-style: normal;
            font-size: 14.5px;
            font-weight: bold;
            color: black;
            z-index: 2;
        }

        .second-txt {
            position: absolute;
            top: 75%;
            width: 235px !important;
            text-align: center;
            font-family: "Ubuntu";
            font-size: 13.5px;
            font-weight: bold;
            color: black;
            z-index: 2;
        }

        .third-txt {
            position: absolute;
            top: 80%;
            width: 235px !important;
            text-align: center;
            font-family: "Ubuntu";
            font-size: 13.5px;
            font-weight: bold;
            color: black;
            z-index: 2;
        }

        .containerdiv {
            border: 0;
            float: left;
            position: relative;
        }

        .cornerimage {
            border: 0;
            position: absolute;
            top: 42%;
            right: 37%;
        }

        .qrimage {
            border: 0;
            position: absolute;
            top: 77%;
            right: 1%;
            z-index: 1;
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
                <div class="col containerdiv" style="display: inline-block; width:246px;margin-left:10%; margin-top:2%">

                    <img src="https://res.cloudinary.com/hs4stt5kg/image/upload/v1647837724/ID%20Card/ID_Card_FINAL_php.jpg" width="100%" />
                    <img class="cornerimage" src=<?php echo $photourl ?> width="75px" />
                    <p class="first-txt"><?php echo $studentname ?></p>
                    <p class="second-txt"><?php echo $student_id ?></p>
                    <p class="third-txt">Student</p>
                    <img class="qrimage" src="https://chart.googleapis.com/chart?chs=85x85&cht=qr&chl=https://login.rssi.in/rssi-student/verification.php?get_id=<?php echo $student_id ?>" width="74px"/>
                </div>
            </div>
        </div>
    </div>
</body>

</html>