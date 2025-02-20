<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();


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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        @media screen {
            .no-display {
                display: none;
            }
        }

        @media print {
            .print-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background-color: #f8f9fa;
                padding: 10px;
                font-size: 12px;
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


        .info-box {
            display: flex;
            justify-content: space-between;
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 5px;
        }

        .info-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .address {
            margin-bottom: 10px;
        }

        .date-time {
            font-weight: bold;
        }

        .qr-code {
            text-align: center;
            margin-top: 10px;
        }

        .qr-message {
            font-size: 14px;
            margin-top: 5px;
        }

        .qr-image {
            width: 180px;
        }
    </style>
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
    <?php include 'inactive_session_expire_check.php'; ?>
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

                <?php
                // Assuming $array['reporting_time'] holds the job type information

                // Initialize the reporting time
                $reporting_time = "";

                // Check the job type
                if ($array['shift'] === "Afternoon") {
                    $reporting_time = "2:45 pm";
                } elseif ($array['shift'] === null) {
                    $reporting_time = "10:45 am";
                } else {
                    // Handle the case where job type is neither Part-time nor Full-time
                    // You may want to set a default reporting time here or handle it differently
                    $reporting_time = "10:45 am"; // Defaulting to 10:45 am for other cases
                }

                // Now incorporate the $reporting_time into the statement
                ?>

                <table class="table" border="0">
                    <thead>
                        <tr>
                            <td>
                                <div class="col" style="display: inline-block; width:65%;">

                                    <p><b>Rina Shiksha Sahayak Foundation</b></p>
                                    <p style="font-size: small;">(Comprising RSSI NGO and Kalpana Buds School)</p>
                                    <!-- <p style="font-size: small;">1074/801, Jhapetapur, Backside of Municipality, West Midnapore, West Bengal 721301</p> -->
                                    <p style="font-size: small;">NGO-DARPAN Id: WB/2021/0282726, CIN: U80101WB2020NPL237900</p>
                                    <p style="font-size: small;">Email: info@rssi.in, Website: www.rssi.in</p>
                                </div>
                                <div class="col" style="display: inline-block; width:32%; vertical-align: top; text-align:right;">
                                    <!-- Scan QR code to check authenticity -->
                                    <?php

                                    $a = 'https://login.rssi.in/rssi-member/verification.php?get_id=';
                                    $b = $array['associatenumber'];
                                    $c = $array['photo'];

                                    $url = $a . $b;
                                    $url = urlencode($url); ?>
                                    <img class="qrimage" src="https://qrcode.tec-it.com/API/QRCode?data=<?php echo $url ?>" width="100px" />
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

                                <p>We would like to take this opportunity to extend a very warm welcome to Rina Shiksha Sahayak Foundation family.</p>
                                <?php
                                echo '<p>Please find the details below:</p><br>';
                                echo '<ul style="list-style-type: none; padding: 0;">';
                                echo '<li><strong>Position:</strong> ' . $array['position'] . ' (' . $array['job_type'] . ')</li>';
                                echo '<li><strong>Division:</strong> ' . $array['depb'] . '</li>';
                                echo '<li><strong>Date of Join:</strong> ' . date('d/F/Y', strtotime($array['doj'])) . '</li>';
                                echo '</ul>';
                                ?>
                                <br>
                                <p><b>Onboarding Checklist</b></p>
                                <ol>
                                    <li>
                                        <p>Please upload scanned copies of all required documents at the following URL if you did not upload them during the interview:
                                            https://login.rssi.in/rssi-member/document.php (Digital Archive and My Bank details)</p>
                                        <ol type="A">
                                            <li>Offer Letter (Signed Copy)</li>
                                            <li>Highschool Marksheet</li>
                                            <li>Intermediate Marksheet</li>
                                            <li>Graduation Marksheet / Certificate OR Any supporting document with college ID.</li>
                                            <li>Post-Graduation or equivalent Marksheet / Certificate (If applicable)</li>
                                            <li>Additional training or course Certificate (If any)</li>
                                            <li>PAN Card</li>
                                            <li>
                                                <p>If you are joining as an employee, please update the following bank account details.</p>
                                                <ol type="i">
                                                    <li>Bank Account Number</li>
                                                    <li>Name of the Bank</li>
                                                    <li>Bank IFSC Code</li>
                                                    <li>Name of the account holder - This should be your account.</li>
                                                </ol>
                                            </li>
                                        </ol>
                                    </li>
                                </ol>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <ol start=2>
                                    <li>
                                        <p>Please complete the mandatory web-based training within one month of your joining date. You can take multiple attempts to achieve a passing score or higher. Once you pass, you can take a screenshot for your reference. To access the training, visit the following URL: https://login.rssi.in/rssi-member/iexplore.php</p>
                                        <div class="container">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Course ID</th>
                                                        <th>Course Title</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>11202</td>
                                                        <td>WBT on Child Sexual Abuse</td>
                                                    </tr>
                                                    <tr>
                                                        <td>11217</td>
                                                        <td>WBT on Classroom Teaching</td>
                                                    </tr>
                                                    <tr>
                                                        <td>11218</td>
                                                        <td>WBT on Corporal Punishment</td>
                                                    </tr>
                                                    <tr>
                                                        <td>11216</td>
                                                        <td>WBT on Digital Etiquette</td>
                                                    </tr>
                                                    <tr>
                                                        <td>11215</td>
                                                        <td>WBT on Information Security Awareness</td>
                                                    </tr>
                                                    <tr>
                                                        <td>11213</td>
                                                        <td>Occupational Health and Safety Policy</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>


                                    </li>
                                </ol>

                                <div class="info-box">
                                    <div class="left-column">
                                        <div class="info-title">Address</div>
                                        <div class="address">
                                            Rina Shiksha Sahayak Foundation<br> D/1/122, Vinamra Khand, Gomti Nagar, Lucknow, Uttar Pradesh 226010<br>
                                            <!-- <a href="https://maps.app.goo.gl/BNq37UdBq4bUcM7a8">Google Map</a> -->
                                            Email – info@rssi.in , Contact – +91 7980168159, +91 9717445551
                                        </div>
                                        <div class="date-time">
                                            <p>Reporting Date and Time:</p>
                                            <p><?php echo date('d/F/Y', strtotime($array['doj'])); ?>&nbsp;&nbsp;<span class="time"><?php echo $reporting_time ?></span></p>
                                        </div>
                                    </div>
                                    <div class="right-column">
                                        <p class="qr-message">Scan the QR code to view location in Google Maps</p>
                                        <div class="qr-code">
                                            <img class="qr-image" src="https://qrcode.tec-it.com/API/QRCode?data=https://maps.app.goo.gl/E5VhwfWBbh61gDrz9" alt="QR Code">

                                        </div>
                                    </div>
                                </div>
                                <br>
                                <p>Warm regards,</p>
                                <p><b>For Rina Shiksha Sahayak Foundation</b></p>
                                <!-- <img src="../img/<?php echo $associatenumber ?>.png" width="65px" style="margin-bottom:-5px"> -->
                                <br><br>
                                <p style="line-height: 2;"><?php echo $fullname ?><br>

                                    <?php if (str_contains($position, "Director")) { ?>
                                        <?php echo 'Talent Acquisition & Academic Interface Program (AIP)' ?>
                                    <?php } else { ?>
                                        <?php echo $position ?>
                                    <?php } ?>

                                </p>
                            </td>
                        </tr>
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="2">
                                <div class="print-footer d-none d-print-inline-flex">
                                    <p style="text-align: right;">Internal</p>
                                </div>
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