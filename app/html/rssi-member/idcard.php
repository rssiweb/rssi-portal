<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
?>

<!DOCTYPE html>
<html>

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-11316670180');
    </script>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>ID Card_<?php echo $fullname ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu&family=Ubuntu+Condensed&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300..700&display=swap" rel="stylesheet">

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
            top: 49%;
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
            top: 56%;
            width: 235px !important;
            text-align: center;
            font-family: "Ubuntu";
            font-size: 13px;
            font-weight: bold;
            color: black;
            z-index: 2;
        }

        .third-txt {
            position: absolute;
            top: 61%;
            width: 235px !important;
            text-align: center;
            font-family: "Ubuntu";
            font-size: 13px;
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
            top: 6%;
            right: 28%;
        }

        .qrimage {
            border: 0;
            position: absolute;
            top: 69%;
            right: 30%;
            z-index: 1;
        }

        body {
            background: #ffffff;
            font-family: "Roboto";
            font-style: normal;
            font-weight: 400;
            overflow-x: hidden;
            margin: 0;
            font-size: 13px;
            /*line-height: 1.42857143;*/
            color: #444;
        }

        .prebanner {
            display: none;
        }

        .vertical-text {
            writing-mode: vertical-rl;
            /* Vertical text from right to left */
            transform: rotate(180deg);
            /* Rotate the text 180 degrees to make it upright */
            position: absolute;
            top: 10%;
            left: 3%;
            width: 235px !important;
            text-align: center;
            font-family: Comfortaa;
            font-size: 30px;
            font-weight: bold;
            color: black;
            z-index: 2;
        }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
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
    <?php include 'inactive_session_expire_check.php'; ?>
    <div class="col2 left noprint" style="display: inline;">
        <div class="col-md-12">
            <div class="row">
                <div class="col noprint" style="display: inline-block; width:100%;margin-left:10%; margin-top:2%">

                    <button type="button" onclick="window.print()" name="print" class="btn btn-success btn-sm" style="outline: none;">
                        <i class="bi bi-save"></i>&nbsp;Save</button>
                </div>
            </div>
        </div>
    </div>

    <div class="col2 left" style="display: inline;">
        <div class="col-md-12">
            <div class="row">
                <div class="col containerdiv" style="display: inline-block; width:246px;margin-left:10%; margin-top:2%">

                    <img src="https://res.cloudinary.com/hs4stt5kg/image/upload/v1711773170/ID%20Card/id_card_front_v1.jpg" width="100%" />
                    <img class="cornerimage" src=<?php echo $photo ?> width="110px" />
                    <p class="first-txt"><?php echo $fullname ?></p>
                    <p class="second-txt"><?php echo $associatenumber ?></p>
                    <p class="third-txt"><?php echo $position ?></p>
                    <img class="qrimage" src="https://qrcode.tec-it.com/API/QRCode?data=https://login.rssi.in/rssi-member/verification.php?get_id=<?php echo $associatenumber ?>" width="100" />
                    <div class="vertical-text">
                        rssi.in
                    </div>

                </div>
                <div class="col containerdiv" style="display: inline-block; width:246px;margin-left:10%; margin-top:2%">
                    <img src="https://res.cloudinary.com/hs4stt5kg/image/upload/v1713469925/ID%20Card/id_card_back.jpg" width="100%" />
                </div>

            </div>
        </div>
    </div>

</body>

</html>