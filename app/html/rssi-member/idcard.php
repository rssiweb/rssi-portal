<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

 if ($filterstatus != 'Active') {

    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu&family=Ubuntu+Condensed&display=swap" rel="stylesheet">
    <!-- Main css -->
    <link rel="stylesheet" href="/css/style.css">
    <style>
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
    
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <!------ Include the above in your HEAD tag ---------->

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

</head>

<body>

    <div class="col2 left noprint" style="display: inline;">
        <div class="col-md-12">
            <div class="row">
                <div class="col noprint" style="display: inline-block; width:100%;margin-left:10%; margin-top:2%">

                    <button type="button" onclick="window.print()" name="print" class="btn btn-success btn-sm" style="outline: none;">
                    <i class="fa-regular fa-floppy-disk"></i>&nbsp;Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="col2 left" style="display: inline;">
        <div class="col-md-12">
            <div class="row">
                <div class="col containerdiv" style="display: inline-block; width:246px;margin-left:10%; margin-top:2%">

                    <img src="https://res.cloudinary.com/hs4stt5kg/image/upload/v1647837724/ID%20Card/ID_Card_FINAL_php.jpg" width="100%" />
                    <img class="cornerimage" src=<?php echo $photo ?> width="75px" />
                    <p class="first-txt"><?php echo $fullname ?></p>
                    <p class="second-txt"><?php echo $associatenumber ?></p>
                    <p class="third-txt"><?php echo $engagement ?></p>
                    <img class="qrimage" src="https://chart.googleapis.com/chart?chs=85x85&cht=qr&chl=https://login.rssi.in/rssi-member/verification.php?get_id=<?php echo $associatenumber ?>" width="74px"/>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
