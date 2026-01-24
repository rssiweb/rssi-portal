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
    $resultt = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$associatenumber'");
    $target_associatenumber = $id;
}

if ($role != 'Admin') {

    $result = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$associatenumber'"); //select query for viewing users.
    $target_associatenumber = $associatenumber;
}


$resultArr = pg_fetch_all($result);
$resultArrr = pg_fetch_all($resultt);


if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$ipf_result = pg_query($con, "
    SELECT ar1.ipf
    FROM appraisee_response ar1
    INNER JOIN (
        SELECT 
            appraisee_associatenumber,
            MAX(goalsheet_created_on) AS max_date
        FROM appraisee_response
        WHERE ipf IS NOT NULL
        GROUP BY appraisee_associatenumber
    ) ar2 
    ON ar1.appraisee_associatenumber = ar2.appraisee_associatenumber 
    AND ar1.goalsheet_created_on = ar2.max_date
    WHERE ar1.appraisee_associatenumber = '$target_associatenumber'
");

$ipf_data = pg_fetch_assoc($ipf_result);
$current_ipf = $ipf_data['ipf'] ?? null;

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
        
    <?php } ?>
    <?php if ($role == 'Admin' && $id != null) { ?>
        
    <?php } ?>
    <?php if ($role == 'Admin' && $id == null) { ?>
        
    <?php } ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

                <table class="table" border="0">
                    <thead>
                        <tr>
                            <td>
                                <div class="col" style="display: inline-block; width:63%;">

                                    <p><b>Rina Shiksha Sahayak Foundation</b></p>
                                    <p style="font-size: small;">(Comprising RSSI NGO and Kalpana Buds School)</p>
                                    <!-- <p style="font-size: small;">1074/801, Jhapetapur, Backside of Municipality, West Midnapore, West Bengal 721301</p> -->
                                    <p style="font-size: small;">NGO-DARPAN Id: WB/2021/0282726, CIN: U80101WB2020NPL237900</p>
                                    <p style="font-size: small;">Email: info@rssi.in, Website: www.rssi.in</p>
                                </div>
                                <div class="col" style="display: inline-block; width:35%; vertical-align: top; text-align:right;">
                                    <p>Scan QR code to check authenticity</p>
                                    <?php

                                    $a = 'https://login.rssi.in/rssi-member/getdetails.php?scode=';
                                    $b = $array['scode'];
                                    $c = $array['photo'];

                                    $url = $a . $b;
                                    $url = urlencode($url); ?>
                                    <img class="qrimage" src="https://qrcode.tec-it.com/API/QRCode?data=<?php echo $url ?>" width="100px" />
                                    <img src=<?php echo $c ?> width=80px height=80px />
                                </div>
                            </td>
                        </tr>
                    </thead>

                    <tbody>

                        <tr>
                            <td>
                                <?php
                                // Example input dates
                                $doj = $array["doj"]; // Date of Joining
                                $effectiveFrom = $array["effectivedate"]; // Effective End Date, could be null

                                // Parse dates
                                $dojDate = new DateTime($doj);
                                $currentDate = new DateTime(); // Current date
                                $endDate = $effectiveFrom ? new DateTime($effectiveFrom) : $currentDate; // Use effective date if set, otherwise use today

                                // Check if DOJ is in the future
                                if ($dojDate > $currentDate) {
                                    // If the DOJ is in the future, display a message
                                    $experience = "Not yet commenced";
                                } else {
                                    // Calculate the difference
                                    $interval = $dojDate->diff($endDate);

                                    // Extract years, months, and days
                                    $years = $interval->y;
                                    $months = $interval->m;
                                    $days = $interval->d;

                                    // Determine the format to display
                                    if ($years > 0) {
                                        $experience = number_format($years + ($months / 12), 2) . " year(s)";
                                    } elseif ($months > 0) {
                                        $experience = number_format($months + ($days / 30), 2) . " month(s)";
                                    } else {
                                        $experience = number_format($days, 2) . " day(s)";
                                    }
                                }
                                ?>
                                <p><b><?php echo $array['fullname'] ?></b><br><?php echo $array['currentaddress'] ?><br>Contact Number:&nbsp;<?php echo $array['phone'] ?>
                                    <br>Email:&nbsp;<?php echo $array['email'] ?><br>Date:&nbsp;<?php echo @date("d/m/Y g:i a", strtotime($date)) ?>
                                </p><br>

                                <p style="text-align: center;"><b><u>TO WHOMSOEVER IT MAY CONCERN</u></b></p><br>

                                <p>This is to certify that <?php echo $array['fullname'] ?> has worked with us for the tenure of <?php echo $experience ?>.
                                    <?php if ($array['gender'] == 'Male') { ?><?php echo 'He' ?><?php } else { ?> <?php echo 'She' ?><?php } ?>

                                    has worked with Rina Shiksha Sahayak Foundation (RSSI) for the position of <?php echo $array['position'] ?> from <?php echo date('d/m/Y', strtotime($array['doj'])) ?> to <?php if ($array['effectivedate'] != null) { ?>
                                        <?php echo date('d/m/Y', strtotime($array['effectivedate'])) ?>
                                    <?php } else { ?> <?php echo 'Present' ?>
                                        <?php } ?>(date in dd/mm/yyyy).<br><br>

                                        <?php if ($current_ipf > 3.75): ?>
                                            <?php echo $array['fullname'] ?> has been an exemplary associate and has always worked diligently to complete tasks assigned to
                                            <?php if ($array['gender'] == 'Male') { ?><?php echo 'him' ?><?php } else { ?> <?php echo 'her' ?><?php } ?>. <?php if ($array['gender'] == 'Male') { ?><?php echo 'He' ?><?php } else { ?> <?php echo 'She' ?><?php } ?> has been a valuable asset to the team and has consistently met the required goals and expectations. We appreciate <?php if ($array['gender'] == 'Male') { ?><?php echo 'his' ?><?php } else { ?> <?php echo 'her' ?><?php } ?> commitment to excellence, hard work and dedication.<br><br>
                                        <?php endif; ?>
                                        We wish <?php if ($array['gender'] == 'Male') { ?><?php echo 'him' ?><?php } else { ?> <?php echo 'her' ?><?php } ?> all the best for <?php if ($array['gender'] == 'Male') { ?><?php echo 'his' ?><?php } else { ?> <?php echo 'her' ?><?php } ?> future endeavors.<br><br>

                                        Sincerely,<br><br><br><br>

                                        <?php echo $fullname ?><br>
                                        <?php echo $position ?><br>
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