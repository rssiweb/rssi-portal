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
        <title>Joining Letter_<?php echo $user_check ?></title>
    <?php } ?>
    <?php if ($role == 'Admin' && $id != null) { ?>
        <title>Joining Letter_<?php echo $id ?></title>
    <?php } ?>
    <?php if ($role == 'Admin' && $id == null) { ?>
        <title>Joining Letter</title>
    <?php } ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        @media screen {
            .no-display {
                display: none;
            }
        }

        @media print {
            table {
                page-break-inside: auto;
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

            .no-print,
            .no-print * {
                display: none !important;
            }
        }

        li {
            margin-bottom: 10px;
        }

        .details td,
        .details th {
            border: 1px solid black;
            border-collapse: collapse;
            padding: 5px;
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
                                <div class="col" style="display: inline-block; width:65%;">

                                    <p><b>Rina Shiksha Sahayak Foundation (RSSI)</b></p>
                                    <p style="font-size: small;">1074/801, Jhapetapur, Backside of Municipality, West Midnapore, West Bengal 721301</p>
                                </div>
                                <div class="col" style="display: inline-block; width:32%; vertical-align: top; text-align:right;">
                                    <!-- Scan QR code to check authenticity -->
                                    <?php

                                    $a = 'https://login.rssi.in/rssi-member/verification.php?get_id=';
                                    $b = $array['associatenumber'];
                                    $c = $array['photo'];

                                    $url = $a . $b;
                                    $url = urlencode($url); ?>
                                    <img class="qrimage" src="https://chart.googleapis.com/chart?chs=85x85&cht=qr&chl=<?php echo $url ?>" width="75px" />
                                    <!-- <img src=<?php echo $c ?> width=80px height=80px /> -->
                                </div>
                            </td>
                        </tr>
                    </thead>


                    <tbody>
                        <tr>
                            <td>
                                <?php echo @date("d/m/Y", strtotime($date)) . '<br>RSSI/' . $array['associatenumber'] . '/' . $array['depb'] . '<br><br>

                                        ' . $array['fullname'] . '<br>
                                        ' . $array['currentaddress'] . '<br><br>

                                        <b>Sub: Joining Letter</b><br><br>'
                                ?>

                                Dear <?php echo strtok($array['fullname'], ' ')  ?>,<br><br>

                                <p>We would like to take this opportunity to extend a very warm welcome to Rina Shiksha Sahayak Foundation (RSSI) family.</p>
                                <?php echo '<p>We are pleased to offer you the position of <b>' . substr($array['position'], 0, strrpos($array['position'], "-")) . ' (' . $array['job_type'] . ')</b> in the division of <b>' . $array['depb'] . '</b>. This appointment will be effective from <b>' . date('d/M/Y', strtotime($array['doj'])) . '</b>' ?>.</p>
                                <p>You are now set to experience learning through our coveted WBT Program. RSSI HR Team will reach out to you over email in the next few days to guide you further on the web-based mandatory training process and steps to be taken to prepare yourself for onboarding.</p>
                                <p>
                                <p><b><u>Reporting Date and Time</u></b></p>
                                <?php echo date('d/M/Y', strtotime($array['doj'])) . '&nbsp;&nbsp;3:30 pm' ?>
                                </p>
                                <p><b><u>Reporting Address</u></b></p>
                                Rina Shiksha Sahayak Foundation (RSSI)<br>
                                624V/195/01, Vijayipur, Gomti Nagar, Lucknow, Uttar Pradesh 226010<br>
                                Email – info@rssi.in , Contact – +91 7980168159, +91 9717445551
                                </p>

                                <br>
                                <p>Warm regards,</p>
                                <p><b>For Rina Shiksha Sahayak Foundation</b></p>
                                <img src="../img/<?php echo $associatenumber ?>.png" width="65px" style="margin-bottom:-5px"><br>
                                <p style="line-height: 2;"><?php echo $fullname ?><br>

                                    <?php if (str_contains($position, "Talent")) { ?>
                                        <?php echo 'Talent Acquisition & Academic Interface Program (AIP)' ?>
                                    <?php } else { ?>
                                        <?php echo $engagement ?>
                                    <?php } ?>

                                </p>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>
                                <p class=" report-footer">Private and Confidential</p>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            <?php }
        } else { ?>
            <p class="no-print">Please enter Associate ID.</p> <?php } ?>
        </section>
    </div>
</body>

</html>