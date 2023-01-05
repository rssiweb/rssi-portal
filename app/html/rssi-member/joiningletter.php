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
    <link rel="stylesheet" href="/css/style.css">
    <!-- Main css -->
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
    </style>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
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

        <section class="box" style="padding: 2%;">

            <?php if ($role == 'Admin') { ?>
                <form action="" method="GET" class="no-print">
                    <div class="form-group" style="display: inline-block;">
                        <div class="col2" style="display: inline-block;">

                            <input name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate Id" value="<?php echo $id ?>" required>
                        </div>
                    </div>

                    <div class="col2 left" style="display: inline-block;">
                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                            <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                        <button type="button" onclick="window.print()" name="print" class="btn btn-info btn-sm" style="outline: none;"><i class="fa-regular fa-floppy-disk"></i>&nbsp;Save</button>
                    </div>
                </form>
            <?php } ?>

            <?php if ($role != 'Admin') { ?>
                <div class="col no-print" style="width:99%;margin-left:1.5%;text-align:right;">
                    <button type="button" onclick="window.print()" name="print" class="btn btn-danger btn-sm" style="outline: none;"><i class="fa-regular fa-floppy-disk"></i>&nbsp;Save</button><br><br>
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
                            <?php echo '<tr>
                           <td>
<p style="text-align: center;"><b><u>JOINING LETTER</u></b></p>

Dear ' . strtok($array['fullname'], ' ') . ',<br><br>

<p>We are pleased to offer you the position of ' . substr($array['position'], 0, strrpos($array['position'], "-")) . ' (' . $array['job_type'] . ') in the division of ' . $array['depb'] . '. This appointment will be effective from ' . $array['doj'] . '.</p>

<p><b><u>Detailed Information:</u></b></p>

<p>Associate Number:  <b>' . $array['associatenumber'] . '</b></p>

                            <p>Sincerely,</p> 
                            <p><b>For Rina Shiksha Sahayak Foundation</b></p>
                            <img src="../img/' ?><?php echo $associatenumber ?><?php echo '.png" width="65px" style="margin-bottom:-5px"><br>
                            <p style="line-height: 2;">' ?><?php echo $fullname ?><br>

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
                                    <p class="report-footer">
                                        <?php echo 'Private and Confidential' ?>
                                    </p>
                                    <?php echo '<p><b><u>Disclaimer:</u></b></p>

<p>I acknowledge that I have read and agree to the Terms and Conditions. Also, I give my consent to processing my data by the RSSI. My consent applies to the following information: my surname, name, telephone, email, and any other information relating to my personality.
</p>
<p style="margin-top:5%;">Signature of the Associate</p><p>Date&nbsp;(dd/mm/yyyy)&nbsp;&#8212;</p>
                            </td>
                            </tr>
                      </tfoot>  
                    </table>' ?>
                                <?php }
                        } else { ?>
                                <p class="no-print">Please enter Associate ID.</p> <?php } ?>
        </section>
    </div>
</body>

</html>