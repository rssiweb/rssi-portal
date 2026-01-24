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


$id = isset($_GET['get_id']) ? strtoupper($_GET['get_id']) : null;
$ctc = isset($_GET['ctc']) ? $_GET['ctc'] : null;
$effective_date = isset($_GET['effective_date']) && !empty($_GET['effective_date'])
    ? $_GET['effective_date']
    : date('Y-m-d');

if ($id) {
    $result = pg_query($con, "SELECT * FROM rssimyaccount_members WHERE associatenumber='$id'");
}

// Optional: if you really need $resultt for logged-in user
$resultt = pg_query($con, "SELECT * FROM rssimyaccount_members WHERE associatenumber='$associatenumber'");

// Fetch HR Officer (Chief Human Resources Officer)
$hrQuery = pg_query($con, "
    SELECT fullname 
    FROM rssimyaccount_members 
    WHERE LOWER(position) = LOWER('Chief Human Resources Officer')
    LIMIT 1
");

// Fetch data safely
$resultArr = $result ? pg_fetch_all($result) : [];
$resultArrr = isset($resultt) && $resultt ? pg_fetch_all($resultt) : [];
$hrOfficer  = $hrQuery ? pg_fetch_assoc($hrQuery) : null;

// Error check
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
    <?php include 'includes/meta.php' ?>

    
    <!-- Bootstrap CSS -->
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

            .report-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                border-top: 1px solid #ccc;
            }

            .no-print,
            .no-print * {
                display: none !important;
            }
        }

        li {
            margin-bottom: 10px;
        }

        body {
            background-color: initial;
        }

        @media print {
            .watermark {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 60px;
                width: 80%;
                color: rgba(0, 0, 0, 0.1);
                /* Light gray with transparency */
                z-index: 9999;
                pointer-events: none;
                font-weight: bold;
                text-align: center;
                opacity: 0.3;
            }
        }

        /*@media print {
            .watermark {
                position: fixed;
                width: 100%;
                height: 100%;
                top: 0;
                left: 0;
                z-index: 9999;
                pointer-events: none;
                opacity: 0.1;
                background: url('path/to/watermark-image.png') center center no-repeat;
                background-size: 50% auto;
            }
        }*/

        @media screen {
            .watermark {
                display: none;
            }
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

                <!-- Associate ID -->
                <div class="form-group" style="display: inline-block;">
                    <div class="col2" style="display: inline-block;">
                        <input name="get_id"
                            class="form-control"
                            style="width:max-content; display:inline-block"
                            placeholder="Associate Id"
                            value="<?php echo $id; ?>"
                            required>
                    </div>
                </div>
                <!-- CTC -->
                <div class="form-group" style="display:inline-block; margin-left:10px;">
                    <label>CTC (per annum):</label>
                    <input type="number"
                        name="ctc"
                        class="form-control"
                        style="width:120px; display:inline-block;"
                        value="<?php echo htmlspecialchars($ctc ?? ''); ?>">
                </div>
                <!-- Effective Date -->
                <div class="form-group" style="display:inline-block; margin-left:10px;">
                    <label>Effective Date:</label>
                    <input type="date"
                        name="effective_date"
                        class="form-control"
                        style="width:150px; display:inline-block;"
                        value="<?php echo htmlspecialchars($effective_date ?? ''); ?>">
                </div>

                <!-- Buttons -->
                <div class="col2 left" style="display: inline-block; margin-left:10px;">
                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                        <i class="bi bi-search"></i>&nbsp;Search
                    </button>
                    <button type="button" onclick="window.print()" name="print" class="btn btn-info btn-sm" style="outline: none;">
                        <i class="bi bi-save"></i>&nbsp;Save
                    </button>
                </div>

            </form>
        <?php } ?>

        <?php if ($resultArr != null) { ?>

            <?php foreach ($resultArr as $array) { ?>

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
                                <?php     // Choose custom CTC if given, else use the DB value
                                $finalCtc = !empty($ctc) ? $ctc : $array['salary']; ?>
                                <?php
                                $formattedDate = date("d/m/Y", strtotime($date));
                                $firstName = strtok($array['fullname'], ' ');
                                ?>

                                <!-- HTML Starts Here -->
                                <p>
                                    <?php echo $formattedDate; ?><br>
                                    RSSI/<?php echo $array['associatenumber']; ?>/<?php echo $array['depb']; ?><br><br>

                                    <?php echo $array['fullname']; ?><br>
                                    <?php echo $array['currentaddress']; ?><br><br>

                                    <b>Sub: Compensation Letter</b><br><br>

                                    Dear <?php echo $firstName; ?>,<br><br>
                                </p>

                                <p>Thank you for your dedication and hard work.</p>

                                <p>I am pleased to share with you the revised Annual Compensation, effective <?= date("F d, Y", strtotime($effective_date)) ?>. Your Annual Compensation is Rs. <?= $finalCtc ?>/ -. The details of your compensation and related benefits
                                    are enclosed in the Annexure to this letter.</p>
                                <p>I encourage you to speak to your Manager / Business Unit Head or your HR Business Partner in case
                                    you need any clarification or discussion.</p>

                                <p>I look forward to your continued support and commitment in our journey together.</p>

                                <p>Sincerely,</p>
                                <p><b>For Rina Shiksha Sahayak Foundation</b></p>
                                <!-- <img src="../img/<?php echo $associatenumber ?>.png" width="65px" style="margin-bottom:-5px">-->
                                <br><br>
                                <p><?= $hrOfficer['fullname'] ?></p>
                                <p>Chief Human Resources Officer
                                </p>
                            </td>
                        </tr>
                    </tbody>

                    <div class="report-footer visible-print-block" style="text-align: right;">
                        <p>Private and Confidential
                        </p>
                    </div>

                </table>

            <?php }
        } else { ?>
            <p class="no-print">Please enter Associate ID.</p> <?php } ?>
        </section>
    </div>
</body>

</html>