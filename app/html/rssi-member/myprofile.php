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


date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');


if ($role == 'Admin') {
    @$id = strtoupper($_GET['get_id']);
    $result = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$id'"); //select query for viewing users.
}

if ($role != 'Admin') {

    $result = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$user_check'"); //select query for viewing users.
}


$resultArr = pg_fetch_all($result);


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
        <title><?php echo $user_check ?></title>
    <?php } ?>
    <?php if ($role == 'Admin' && $id != null) { ?>
        <title><?php echo $id ?></title>
    <?php } ?>
    <?php if ($role == 'Admin' && $id == null) { ?>
        <title>My Profile</title>
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
    <br>
    <div class="col-md-12">
        <?php if ($role == 'Admin') { ?>
            <form action="" method="GET" class="no-print">
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

            <?php foreach ($resultArr as $array) {

                echo '
                    <table class="table" border="0">
                        <thead class="no-display">
                            <tr>
                            <td colspan=5>
                            <div class="col" style="display: inline-block; width:55%; text-align:left;">

                            <p><b>Rina Shiksha Sahayak Foundation (RSSI)</b></p>
                            <p>1074/801, Jhapetapur, Backside of Municipality, West Midnapore, West Bengal 721301</p>
                            </div>' ?>
                <?php if ($role != 'Admin') {
                    echo '<div class="col" style="display: inline-block; width:42%;margin-left:1.5%;text-align:right;">
                                <img class="qrimage" src="https://chart.googleapis.com/chart?chs=85x85&cht=qr&chl=https://login.rssi.in/rssi-member/verification.php?get_id=' . $array['associatenumber'] . '" width="74px" />
                            </div>' ?><?php } ?>

                <?php if ($role == 'Admin') {
                    echo '<div class="col" style="display: inline-block; width:42%;margin-left:1.5%;text-align:right;">
                                <img class="qrimage" src="https://chart.googleapis.com/chart?chs=85x85&cht=qr&chl=https://login.rssi.in/rssi-member/verification.php?get_id=' ?><?php echo $id ?><?php echo '" width="74px" />
                            </div>' ?><?php } ?>

                <?php echo
                '</td>
                            </tr>
                        </thead>

                        <tr>
                            <th>Photo</th>    
                            <th>Associate Details</th>
                            <th>Date of Join</th>
                            <th>Association Status</th>
                            <th>Badge</th>
                        </tr>

                        <tr>
                            <td><img src= ' . $array['photo'] . ' width=75px /></td>
                            <td style="line-height: 1.7;"><b>' . $array['fullname'] . '</b><br>Associate ID - <b>' . $array['associatenumber'] . '</b><br><span style="line-height: 3;">' . $array['engagement'] . ',&nbsp;' . $array['gender'] . '&nbsp;(' . $array['age'] . '&nbsp;Years)</span>
                            </td>
                            <td style="line-height: 2;">Original DOJ -' . @date("d/m/Y", strtotime($array['originaldoj'])) . '<br>Recent DOJ -' . @date("d/m/Y", strtotime($array['doj'])) . '<br>(' . $array['yos'] . ')</td>
                            <td>' . $array['filterstatus'] . '<br><br>' . $array['remarks'] . '</td>
                            <td>' . $array['badge'] . '</td>
                        </tr>

                        <tr>
                            <th >Date of Birth</th>
                            <th colspan=2>National Identifier</th>
                            <th  colspan=2>Last 4 digits of Identifier</th>
                        </tr>

                        <tr>
                            <td>' . $array['dateofbirth'] . '</td>' ?>

                <?php if ($array['iddoc'] != null) {

                    echo '<td colspan=2>
                            <iframe src="' . $array['iddoc'] . '" width="300px" height="200px" /></iframe></td>' ?>
                    <?php  } else {
                    echo '<td colspan=2>No document uploaded.</td>'
                    ?><?php }
                    echo '
                                <td colspan=2>' . $array['identifier'] . '</td>
                        </tr>

                        <tr>
                            <th >Application Number</th>
                            <th  colspan=2>Responsibility</th>
                            <th >Base Branch</th>
                            <th >Deputed Branch</th>
                        </tr>
                    
                        <tr>
                            <td>' . $array['applicationnumber'] . '</td>
                            <td colspan=2>' . substr($array['position'], 0, strrpos($array['position'], "-")) . '</td>
                            <td>' . $array['basebranch'] . '</td>
                            <td>' . $array['depb'] . '</td>
                        </tr>
                        
                        <tr>
                            <th  colspan=2>Current Address</th>
                            <th >Permanent Address</th>
                            <th  colspan=2>Contact/Email Address</th>
                        </tr>
                        
                        <tr>
                            <td colspan=2>' . $array['currentaddress'] . '</td>
                            <td>' . $array['permanentaddress'] . '</td>
                            <td colspan=2>' . $array['phone'] . '<br>' . $array['email'] . '</td>
                        </tr>

                        <tr>
                            <th >Language Details</th>
                            <th >Educational qualifications</th>
                            <th >Area of specialization</th>
                            <th >Work Experience</th>
                            <th >Account Approved by</th>
                        </tr>
                        
                        <tr>
                            <td>English - ' . $array['languagedetailsenglish'] . '<br>Hindi - ' . $array['languagedetailshindi'] . '</td>
                            <td>' . $array['eduq'] . '</td>
                            <td>' . $array['mjorsub'] . '</td>
                            <td>' . $array['workexperience'] . '</td>
                            <td>' . $array['approvedby'] . '</td>
                        </tr>
                    </table>
                         
                    <div class="report-footer">
                    <p style="text-align:right;">Document generated on:&nbsp;' ?><?php echo @date("d/m/Y g:i a", strtotime($date)) ?><?php echo '</p>
                    </div>' ?>
                <?php }
        } else { ?>
                <p class="no-print">Please enter Associate ID.</p> <?php } ?>
            </section>
    </div>
</body>

</html>