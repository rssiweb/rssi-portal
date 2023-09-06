<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

if ($role != 'Admin') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}


date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');


if ($role == 'Admin') {
    @$id = strtoupper($_GET['get_id']);
    $result = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$id'"); //select query for viewing users.
    $resultt = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$user_check'");
}

if ($role != 'Admin') {

    $result = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$user_check'"); //select query for viewing users.
}


$resultArr = pg_fetch_all($result);
$resultArrr = pg_fetch_all($resultt);


if (!$result) {
    echo "An error occurred.\n";
    exit;
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
    <?php if ($role != 'Admin') { ?>
        <title>Experience Letter_<?php echo $user_check ?></title>
    <?php } ?>
    <?php if ($role == 'Admin' && $id != null) { ?>
        <title>Experience Letter_<?php echo $id ?></title>
    <?php } ?>
    <?php if ($role == 'Admin' && $id == null) { ?>
        <title>Experience Letter</title>
    <?php } ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- Main css -->
    <style>
        table {
            page-break-inside: avoid;
        }

        @media screen {
            .no-display {
                display: none;
            }
        }

        @media print {
            .footer {
                position: fixed;
                bottom: 0;
            }

            .no-print,
            .no-print * {
                display: none !important;
            }

            .report-footer {
                position: fixed;
                bottom: 0px;
                height: 20px;
                display: block;
                width: 90%;
                border-top: solid 1px #ccc;
                overflow: visible;
            }

        }

        body {
            background-color: initial;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
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
    <div class="col-md-12">
        <?php if ($role == 'Admin') { ?>
            <form action="" method="GET" class="no-print">
                <br>
                <div class="form-group" style="display: inline-block;">
                    <div class="col2" style="display: inline-block;">

                        <input name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate Id" value="<?php echo $id ?>" required>
                    </div>
                </div>

                <div class="col2 left" style="display: inline-block;">
                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                        <i class="bi bi-search"></i>&nbsp;Search</button>
                    <button type="button" onclick="window.print()" name="print" class="btn btn-info btn-sm" style="outline: none;"><i class="bi bi-save"></i>&nbsp;Save</button>
                </div>
            </form>
        <?php } ?>

        <?php if ($role != 'Admin') { ?>
            <div class="col no-print" style="width:99%;margin-left:1.5%;text-align:right;">
                <button type="button" onclick="window.print()" name="print" class="btn btn-danger btn-sm" style="outline: none;"><i class="bi bi-save"></i>&nbsp;Save</button><br><br>
            </div>
        <?php } ?>

        <?php if ($resultArr != null) { ?>

            <?php foreach ($resultArr as $array) { ?>

                <table class="table" border="0">
                    <thead>
                        <tr>
                            <td>
                                <div class="row">
                                    <div class="col" style="display: inline-block; width:65%;">

                                        <p><b>Rina Shiksha Sahayak Foundation (RSSI)</b></p>
                                        <p style="font-size: small;">1074/801, Jhapetapur, Backside of Municipality, West Midnapore, West Bengal 721301</p>
                                        <p style="font-size: small;">CIN— U80101WB2020NPL237900</p>
                                    </div>
                                    <div class="col" style="display: inline-block; width:34%; vertical-align: top;">
                                        Scan QR code to check authenticity
                                        <?php

                                        $a = 'https://login.rssi.in/rssi-member/getdetails.php?scode=';
                                        $b = $array['scode'];
                                        $c = $array['photo'];

                                        $url = $a . $b;
                                        $url = urlencode($url); ?>
                                        <img class="qrimage" src="https://chart.googleapis.com/chart?chs=85x85&cht=qr&chl=<?php echo $url ?>" width="100px" />
                                        <img src=<?php echo $c ?> width=80px height=80px />
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </thead>

                    <tbody>

                        <tr>
                            <td>
                                <p><b><?php echo $array['fullname'] ?></b><br><?php echo $array['currentaddress'] ?><br>Contact Number:&nbsp;<?php echo $array['phone'] ?>
                                    <br>Email:&nbsp;<?php echo $array['email'] ?><br>Date:&nbsp;<?php echo @date("d/m/Y g:i a", strtotime($date)) ?>
                                </p><br>

                                <p style="text-align: center;"><b><u>TO WHOMSOEVER IT MAY CONCERN</u></b></p><br>

                                <p>This is to certify that <?php echo $array['fullname'] ?> has worked with us for the tenure of <?php echo $array['yos'] ?>.
                                    <?php if ($array['gender'] == 'Male') { ?><?php echo 'He' ?><?php } else { ?> <?php echo 'She' ?><?php } ?>

                                    has worked with Rina Shiksha Sahayak Foundation (RSSI) for the position of <?php echo substr($array['position'], 0, strrpos($array['position'], "-")) ?> from <?php echo date('d/m/Y', strtotime($array['doj'])) ?> to <?php if ($array['associationstatus'] != null) { ?>
                                        <?php echo date('d/m/Y', strtotime($array['effectivedate'])) ?>
                                    <?php } else { ?> <?php echo 'Present' ?>
                                        <?php } ?>(date in dd/mm/yyyy).<br><br>

                                        <?php echo $array['fullname'] ?> has been an exemplary associate and has always worked diligently to complete tasks assigned to
                                        <?php if ($array['gender'] == 'Male') { ?><?php echo 'him' ?><?php } else { ?> <?php echo 'her' ?><?php } ?>. <?php if ($array['gender'] == 'Male') { ?><?php echo 'He' ?><?php } else { ?> <?php echo 'She' ?><?php } ?> has been a valuable asset to the team and has consistently met the required goals and expectations. We appreciate <?php if ($array['gender'] == 'Male') { ?><?php echo 'his' ?><?php } else { ?> <?php echo 'her' ?><?php } ?> commitment to excellence, hard work and dedication.<br><br>
                                        We wish <?php if ($array['gender'] == 'Male') { ?><?php echo 'him' ?><?php } else { ?> <?php echo 'her' ?><?php } ?> all the best for <?php if ($array['gender'] == 'Male') { ?><?php echo 'his' ?><?php } else { ?> <?php echo 'her' ?><?php } ?> future endeavors.<br><br>

                                        Sincerely,<br><br><br><br>

                                        <?php echo $fullname ?><br>
                                        <?php echo $engagement ?><br>
                                        Rina Shiksha Sahayak Foundation (RSSI)



                            </td>
                        </tr>

                    </tbody>

                    <div class="report-footer">
                        <p style="text-align:right;">Document generated on:&nbsp;<?php echo @date("d/m/Y g:i a", strtotime($date)) ?></p>
                    </div>
                <?php }
        } else { ?>
                <p class="no-print">Please enter Associate ID.</p> <?php } ?>
                </table>
                </section>
    </div>
</body>

</html>