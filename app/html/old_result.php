<?php
require_once __DIR__ . '/../bootstrap.php';

include(__DIR__ . "/../util/login_util.php");

date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');

@$id = $_GET['get_id']; //$_GET['get_id'];isset($_GET["get_id"]) ? $_GET["get_id"] : "Half Yearly Exam"
@$stid = $_GET['get_stid'];
@$print = (isset($_GET["print"]) ? $_GET["print"] : "False") == "True";
@$year = $_GET['get_year'];

$view_users_query = "select * from result WHERE studentid='$stid' AND examname='$id' AND academicyear='$year'"; //select query for viewing users.
$view_users_queryy = "select student_id,studentname,dateofbirth,photourl from rssimyprofile_student WHERE student_id='$stid'";
$run = pg_query($con, $view_users_query); //here run the sql query.
$runn = pg_query($con, $view_users_queryy);

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
    $name = $row[0];
    $studentid = $row[1];
    $category = $row[2];
    $class = $row[3];
    $hnd_o = $row[4];
    $hnd = $row[5];
    $eng_o = $row[6];
    $eng = $row[7];
    $mth_o = $row[8];
    $mth = $row[9];
    $sce_o = $row[10];
    $sce = $row[11];
    $gka_o = $row[12];
    $gka = $row[13];
    $ssc_o = $row[14];
    $ssc = $row[15];
    $phy_o = $row[16];
    $phy = $row[17];
    $chm_o = $row[18];
    $chm = $row[19];
    $bio_o = $row[20];
    $bio = $row[21];
    $com_o = $row[22];
    $com = $row[23];
    $hd_o = $row[24];
    $hd = $row[25];
    $acc_o = $row[26];
    $acc = $row[27];
    $pt_o = $row[28];
    $pt = $row[29];
    // $total = $row[30];
    $mm = $row[31];
    // $op = $row[32];
    // $grade = $row[33];
    // $result = $row[34];
    $position = $row[35];
    $fullmarks_o = $row[36];
    $fullmarks = $row[37];
    $examname = $row[38];
    $language1 = $row[39];
    $attd = $row[40];
    $month = $row[41];
    $academicyear = $row[43];
}

while ($roww = pg_fetch_array($runn)) //while look to fetch the result and store in a array $row.  
{
    $student_id = $roww[0];
    $studentname = $roww[1];
    $dateofbirth = $roww[2];
    $photourl = $roww[3];
}

?>


<!DOCTYPE html>
<html lang="en">

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
    <title><?php echo $student_id ?>_<?php echo $id ?>_<?php echo $year ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Comfortaa');
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600&display=swap');

        body {
            background: #ffffff;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif
                /* font-style: normal;
            font-weight: 400;
            overflow-x: hidden;
            margin: 0;
            font-size: 14px;
            color: #444; */
        }

        @media (min-width:767px) {
            .top {
                margin-top: 2%
            }
        }

        @media (max-width:767px) {
            .top {
                margin-top: 10%
            }

            .topbutton {
                margin-top: 5%
            }
        }

        @media print {
            .noprint {
                visibility: hidden;
                position: absolute;
                left: 0;
                top: 0;
            }

            .footer {
                position: fixed;
                bottom: 0;
            }
        }

        @media screen {
            .no-display {
                display: none;
            }
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
    </style>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <div class="col-md-12">

        <?php if ($print == FALSE) { ?>

            <div class="noprint" style="display: flex; justify-content: flex-end; margin-top: 2%;">
                <form action="" method="GET" id="formid">
                    <input name="get_stid" class="form-control" style="width: max-content; display: inline-block;" required placeholder="Student ID" value="<?php echo @$stid ?>">
                    <select name="get_id" class="form-control" style="width: max-content; display: inline-block;" required>
                        <?php if ($id == null) { ?>
                            <option value="" disabled selected hidden>Select Exam Name</option>
                        <?php } else { ?>
                            <option hidden selected><?php echo $id ?></option>
                        <?php } ?>
                        <option>First Term Exam</option>
                        <option>Half Yearly Exam</option>
                        <option>Annual Exam</option>
                    </select>
                    <select name="get_year" id="get_year" class="form-control" style="width: max-content; display: inline-block;" required>
                        <?php if ($year == null) { ?>
                            <option value="" disabled selected hidden>Select Year</option>
                        <?php } else { ?>
                            <option hidden selected><?php echo $year ?></option>
                        <?php } ?>
                    </select>
                    <div class="col topbutton" style="display: inline-block;">
                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                            <i class="bi bi-search"></i>&nbsp;Search</button>
                        <button type="button" onclick="window.print()" name="print" class="btn btn-danger btn-sm" style="outline: none;">
                            <i class="fa-solid fa-print"></i>&nbsp;Print</button>
                    </div>
                </form>
                <br>
            </div>

            <script>
                <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
                    var currentYear = new Date().getFullYear() - 1;
                <?php } else { ?>
                    var currentYear = new Date().getFullYear();
                <?php } ?>

                for (var i = 0; i < 5; i++) {
                    var next = currentYear + 1;
                    var year = currentYear + '-' + next;
                    //next.toString().slice(-2) 
                    $('#get_year').append(new Option(year, year));
                    currentYear--;
                }
            </script>

        <?php } ?>


        <?php if (@$examname > 0) {
        ?>
            <table class="table" border="0">
                <thead> <!--class="no-display"-->
                    <tr>
                        <td colspan=4>
                            <div class="row">
                                <div class="col" style="display: inline-block; width:65%;">

                                    <p><b>Rina Shiksha Sahayak Foundation (RSSI NGO)</b></p>
                                    <p style="font-size: small;">D/1/122, Vinamra Khand, Gomti Nagar, Lucknow, Uttar Pradesh 226010</p>
                                    <p style="font-size: small;">NGO-DARPAN Id: WB/2021/0282726, CIN: U80101WB2020NPL237900</p>
                                    <p style="font-size: small;">Email: info@rssi.in, Website: www.rssi.in</p>
                                </div>
                                <div class="col" style="display: inline-block; width:32%; vertical-align: top;">
                                    <p style="font-size: small;">Scan QR code to check authenticity</p>
                                    <?php
                                    $exam = str_replace(" ", "%20", $id);
                                    $url = "https://login.rssi.in/result.php?get_stid=$stid&get_id=$exam&get_year=$year";
                                    $url_u = urlencode($url); ?>
                                    <img class="qrimage" src="https://qrcode.tec-it.com/API/QRCode?data=<?php echo $url_u ?>"  width="80px" />&nbsp;
                                    <img src=<?php echo $photourl ?> width=80px height=80px />
                                </div>
                            </div>
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4">
                            <h3 style="text-align:center;margin-top: 10px;font-family: 'Cinzel', serif;">Report card</h3>
                        </td>
                    </tr>
                    <tr>
                        <td>STUDENT ID</td>
                        <th><?php echo $studentid ?></th>
                        <td> LEARNING GROUP/CLASS </td>
                        <th><?php echo $category ?>/<?php echo $class ?></th>
                    </tr>
                    <tr>
                        <td>NAME OF STUDENT</td>
                        <th><?php echo $studentname ?></th>
                        <td>DATE OF BIRTH</td>
                        <th><?php echo date("d/m/Y", strtotime($dateofbirth)) ?></th>
                    </tr>
                </tbody>
            </table>
            <table>
                <tr>
                    <td style="text-align:center;"><b><?php echo $examname ?>&nbsp; <?php echo $academicyear ?></b></td>
                </tr>
            </table>
            <br>
            <table class="table" border="1" align="center" style="width: 100%;">
                <tbody>
                    <tr bgcolor="#428BCA" style="color: #fff;">
                        <th style="text-align:left">Subject</th>
                        <th style="text-align:left" colspan="2"> Full Marks </th>
                        <th style="text-align:left" colspan="2"> Marks Obtained </th>
                        <th style="text-align:left"> Total Marks Obtained </th>
                        <th style="text-align:left"> Positional grade </th>
                    </tr>
                    <tr>
                        <th style="text-align:left"></th>
                        <th style="text-align:left"> Viva</th>
                        <th style="text-align:left"> Written</th>
                        <th style="text-align:left"> Viva</th>
                        <th style="text-align:left"> Written</th>
                        <th style="text-align:left"></th>
                        <th style="text-align:left"></th>
                    </tr>
                    <?php if ((@$hnd != null && @$hnd != "-") || (@$hnd_o != null && @$hnd_o != "-")) { ?>
                        <tr>
                            <td style="text-align:left"> Language I </td>
                            <td style="text-align:left"><?php echo @$hnd_o != null && @$hnd_o != "-" ? $fullmarks_o : ''; ?></td>
                            <td style="text-align:left"><?php echo @$hnd != null && @$hnd != "-" ? $fullmarks : ''; ?></td>
                            <th style="text-align:left"><?php echo is_numeric($hnd_o) ? round($hnd_o) : ($hnd_o == '-' ? null : $hnd_o); ?></th>
                            <th style="text-align:left"><?php echo is_numeric($hnd) ? round($hnd) : ($hnd == '-' ? null : $hnd); ?></th>
                            <th style="text-align:left">
                                <?php
                                if (($hnd_o == "A" && $hnd == "A") || ($hnd_o == "-" && $hnd == "A") || ($hnd_o == "A" && $hnd == "-")) {
                                    echo "A";
                                } elseif ($hnd_o == "-" && $hnd == "-") {
                                    echo null;
                                } elseif ($hnd_o == "A" && is_numeric($hnd)) {
                                    echo round($hnd);
                                } elseif ($hnd == "A" && is_numeric($hnd_o)) {
                                    echo round($hnd_o);
                                } else {
                                    $total = is_numeric($hnd_o) ? $hnd_o : 0;
                                    $total += is_numeric($hnd) ? $hnd : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$eng != null && @$eng != '-') || (@$eng_o != null && @$eng_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> English </td>
                            <td style="text-align:left"><?php echo @$eng_o != null && @$eng_o != '-' ? $fullmarks_o : ''; ?></td>
                            <td style="text-align:left"><?php echo @$eng != null && @$eng != '-' ? $fullmarks : ''; ?></td>
                            <th style="text-align:left"><?php echo is_numeric($eng_o) ? round($eng_o) : ($eng_o == '-' ? null : $eng_o); ?></th>
                            <th style="text-align:left"><?php echo is_numeric($eng) ? round($eng) : ($eng == '-' ? null : $eng); ?></th>
                            <th style="text-align:left">
                                <?php
                                if (($eng_o == "A" && $eng == "A") || ($eng_o == "-" && $eng == "A") || ($eng_o == "A" && $eng == "-")) {
                                    echo "A";
                                } elseif ($eng_o == "-" && $eng == "-") {
                                    echo null;
                                } elseif ($eng_o == "A" && is_numeric($eng)) {
                                    echo round($eng);
                                } elseif ($eng == "A" && is_numeric($eng_o)) {
                                    echo round($eng_o);
                                } else {
                                    $total = is_numeric($eng_o) ? $eng_o : 0;
                                    $total += is_numeric($eng) ? $eng : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$mth != null && @$mth != '-') || (@$mth_o != null && @$mth_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Mathematics </td>
                            <td style="text-align:left"><?php echo @$mth_o != null && @$mth_o != '-' ? $fullmarks_o : ''; ?></td>
                            <td style="text-align:left"><?php echo @$mth != null && @$mth != '-' ? $fullmarks : ''; ?></td>
                            <th style="text-align:left"><?php echo is_numeric($mth_o) ? round($mth_o) : ($mth_o == '-' ? null : $mth_o); ?></th>
                            <th style="text-align:left"><?php echo is_numeric($mth) ? round($mth) : ($mth == '-' ? null : $mth); ?></th>
                            <th style="text-align:left">
                                <?php
                                if (($mth_o == "A" && $mth == "A") || ($mth_o == "-" && $mth == "A") || ($mth_o == "A" && $mth == "-")) {
                                    echo "A";
                                } elseif ($mth_o == "-" && $mth == "-") {
                                    echo null;
                                } elseif ($mth_o == "A" && is_numeric($mth)) {
                                    echo round($mth);
                                } elseif ($mth == "A" && is_numeric($mth_o)) {
                                    echo round($mth_o);
                                } else {
                                    $total = is_numeric($mth_o) ? $mth_o : 0;
                                    $total += is_numeric($mth) ? $mth : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$sce != null && @$sce != '-') || (@$sce_o != null && @$sce_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Science </td>
                            <td style="text-align:left"><?php echo @$sce_o != null && @$sce_o != '-' ? $fullmarks_o : ''; ?></td>
                            <td style="text-align:left"><?php echo @$sce != null && @$sce != '-' ? $fullmarks : ''; ?></td>
                            <th style="text-align:left"><?php echo is_numeric($sce_o) ? round($sce_o) : ($sce_o == '-' ? null : $sce_o); ?></th>
                            <th style="text-align:left"><?php echo is_numeric($sce) ? round($sce) : ($sce == '-' ? null : $sce); ?></th>
                            <th style="text-align:left">
                                <?php
                                if (($sce_o == "A" && $sce == "A") || ($sce_o == "-" && $sce == "A") || ($sce_o == "A" && $sce == "-")) {
                                    echo "A";
                                } elseif ($sce_o == "-" && $sce == "-") {
                                    echo null;
                                } elseif ($sce_o == "A" && is_numeric($sce)) {
                                    echo round($sce);
                                } elseif ($sce == "A" && is_numeric($sce_o)) {
                                    echo round($sce_o);
                                } else {
                                    $total = is_numeric($sce_o) ? $sce_o : 0;
                                    $total += is_numeric($sce) ? $sce : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$ssc != null && @$ssc != '-') || (@$ssc_o != null && @$ssc_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Social Science </td>
                            <td style="text-align:left"><?php echo @$ssc_o != null && @$ssc_o != '-' ? $fullmarks_o : ''; ?></td>
                            <td style="text-align:left"><?php echo @$ssc != null && @$ssc != '-' ? $fullmarks : ''; ?></td>
                            <th style="text-align:left"><?php echo is_numeric($ssc_o) ? round($ssc_o) : ($ssc_o == '-' ? null : $ssc_o); ?></th>
                            <th style="text-align:left"><?php echo is_numeric($ssc) ? round($ssc) : ($ssc == '-' ? null : $ssc); ?></th>
                            <th style="text-align:left">
                                <?php
                                if (($ssc_o == "A" && $ssc == "A") || ($ssc_o == "-" && $ssc == "A") || ($ssc_o == "A" && $ssc == "-")) {
                                    echo "A";
                                } elseif ($ssc_o == "-" && $ssc == "-") {
                                    echo null;
                                } elseif ($ssc_o == "A" && is_numeric($ssc)) {
                                    echo round($ssc);
                                } elseif ($ssc == "A" && is_numeric($ssc_o)) {
                                    echo round($ssc_o);
                                } else {
                                    $total = is_numeric($ssc_o) ? $ssc_o : 0;
                                    $total += is_numeric($ssc) ? $ssc : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$gka != null && @$gka != '-') || (@$gka_o != null && @$gka_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left">
                                <?php if ($class == 1) {
                                    echo "General knowledge";
                                } else {
                                    echo "Hamara Parivesh";
                                }
                                ?>
                            </td>
                            <td style="text-align:left"><?php echo @$gka_o != null && @$gka_o != '-' ? $fullmarks_o : ''; ?></td>
                            <td style="text-align:left"><?php echo @$gka != null && @$gka != '-' ? $fullmarks : ''; ?></td>
                            <th style="text-align:left"><?php echo is_numeric($gka_o) ? round($gka_o) : ($gka_o == '-' ? null : $gka_o); ?></th>
                            <th style="text-align:left"><?php echo is_numeric($gka) ? round($gka) : ($gka == '-' ? null : $gka); ?></th>
                            <th style="text-align:left">
                                <?php
                                if (($gka_o == "A" && $gka == "A") || ($gka_o == "-" && $gka == "A") || ($gka_o == "A" && $gka == "-")) {
                                    echo "A";
                                } elseif ($gka_o == "-" && $gka == "-") {
                                    echo null;
                                } elseif ($gka_o == "A" && is_numeric($gka)) {
                                    echo round($gka);
                                } elseif ($gka == "A" && is_numeric($gka_o)) {
                                    echo round($gka_o);
                                } else {
                                    $total = is_numeric($gka_o) ? $gka_o : 0;
                                    $total += is_numeric($gka) ? $gka : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$com != null && @$com != '-') || (@$com_o != null && @$com_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Computer </td>
                            <td style="text-align:left"><?php echo @$com_o != null && @$com_o != '-' ? $fullmarks_o : ''; ?></td>
                            <td style="text-align:left"><?php echo @$com != null && @$com != '-' ? $fullmarks : ''; ?></td>
                            <th style="text-align:left"><?php echo is_numeric($com_o) ? round($com_o) : ($com_o == '-' ? null : $com_o); ?></th>
                            <th style="text-align:left"><?php echo is_numeric($com) ? round($com) : ($com == '-' ? null : $com); ?></th>
                            <th style="text-align:left">
                                <?php
                                if (($com_o == "A" && $com == "A") || ($com_o == "-" && $com == "A") || ($com_o == "A" && $com == "-")) {
                                    echo "A";
                                } elseif ($com_o == "-" && $com == "-") {
                                    echo null;
                                } elseif ($com_o == "A" && is_numeric($com)) {
                                    echo round($com);
                                } elseif ($com == "A" && is_numeric($com_o)) {
                                    echo round($com_o);
                                } else {
                                    $total = is_numeric($com_o) ? $com_o : 0;
                                    $total += is_numeric($com) ? $com : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$bio != null && @$bio != '-') || (@$bio_o != null && @$bio_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Biology/Life science </td>
                            <td style="text-align:left"><?php echo @$bio_o != null && @$bio_o != '-' ? $fullmarks_o : ''; ?></td>
                            <td style="text-align:left"><?php echo @$bio != null && @$bio != '-' ? $fullmarks : ''; ?></td>
                            <th style="text-align:left"><?php echo is_numeric($bio_o) ? round($bio_o) : ($bio_o == '-' ? null : $bio_o); ?></th>
                            <th style="text-align:left"><?php echo is_numeric($bio) ? round($bio) : ($bio == '-' ? null : $bio); ?></th>
                            <th style="text-align:left">
                                <?php
                                if (($bio_o == "A" && $bio == "A") || ($bio_o == "-" && $bio == "A") || ($bio_o == "A" && $bio == "-")) {
                                    echo "A";
                                } elseif ($bio_o == "-" && $bio == "-") {
                                    echo null;
                                } elseif ($bio_o == "A" && is_numeric($bio)) {
                                    echo round($bio);
                                } elseif ($bio == "A" && is_numeric($bio_o)) {
                                    echo round($bio_o);
                                } else {
                                    $total = is_numeric($bio_o) ? $bio_o : 0;
                                    $total += is_numeric($bio) ? $bio : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$phy != null && @$phy != '-') || (@$phy_o != null && @$phy_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Physics/Physical science </td>
                            <td style="text-align:left"><?php echo @$phy_o != null && @$phy_o != '-' ? $fullmarks_o : ''; ?></td>
                            <td style="text-align:left"><?php echo @$phy != null && @$phy != '-' ? $fullmarks : ''; ?></td>
                            <th style="text-align:left"><?php echo is_numeric($phy_o) ? round($phy_o) : ($phy_o == '-' ? null : $phy_o); ?></th>
                            <th style="text-align:left"><?php echo is_numeric($phy) ? round($phy) : ($phy == '-' ? null : $phy); ?></th>
                            <th style="text-align:left">
                                <?php
                                if (($phy_o == "A" && $phy == "A") || ($phy_o == "-" && $phy == "A") || ($phy_o == "A" && $phy == "-")) {
                                    echo "A";
                                } elseif ($phy_o == "-" && $phy == "-") {
                                    echo null;
                                } elseif ($phy_o == "A" && is_numeric($phy)) {
                                    echo round($phy);
                                } elseif ($phy == "A" && is_numeric($phy_o)) {
                                    echo round($phy_o);
                                } else {
                                    $total = is_numeric($phy_o) ? $phy_o : 0;
                                    $total += is_numeric($phy) ? $phy : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$chm != null && @$chm != '-') || (@$chm_o != null && @$chm_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Chemistry </td>
                            <td style="text-align:left"><?php echo @$chm_o != null && @$chm_o != '-' ? $fullmarks_o : ''; ?></td>
                            <td style="text-align:left"><?php echo @$chm != null && @$chm != '-' ? $fullmarks : ''; ?></td>
                            <th style="text-align:left"><?php echo is_numeric($chm_o) ? round($chm_o) : ($chm_o == '-' ? null : $chm_o); ?></th>
                            <th style="text-align:left"><?php echo is_numeric($chm) ? round($chm) : ($chm == '-' ? null : $chm); ?></th>
                            <th style="text-align:left">
                                <?php
                                if (($chm_o == "A" && $chm == "A") || ($chm_o == "-" && $chm == "A") || ($chm_o == "A" && $chm == "-")) {
                                    echo "A";
                                } elseif ($chm_o == "-" && $chm == "-") {
                                    echo null;
                                } elseif ($chm_o == "A" && is_numeric($chm)) {
                                    echo round($chm);
                                } elseif ($chm == "A" && is_numeric($chm_o)) {
                                    echo round($chm_o);
                                } else {
                                    $total = is_numeric($chm_o) ? $chm_o : 0;
                                    $total += is_numeric($chm) ? $chm : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$acc != null && @$acc != '-') || (@$acc_o != null && @$acc_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Accountancy </td>
                            <td style="text-align:left"><?php echo @$acc_o != null && @$acc_o != '-' ? $fullmarks_o : ''; ?></td>
                            <td style="text-align:left"><?php echo @$acc != null && @$acc != '-' ? $fullmarks : ''; ?></td>
                            <th style="text-align:left"><?php echo is_numeric($acc_o) ? round($acc_o) : ($acc_o == '-' ? null : $acc_o); ?></th>
                            <th style="text-align:left"><?php echo is_numeric($acc) ? round($acc) : ($acc == '-' ? null : $acc); ?></th>
                            <th style="text-align:left">
                                <?php
                                if (($acc_o == "A" && $acc == "A") || ($acc_o == "-" && $acc == "A") || ($acc_o == "A" && $acc == "-")) {
                                    echo "A";
                                } elseif ($acc_o == "-" && $acc == "-") {
                                    echo null;
                                } elseif ($acc_o == "A" && is_numeric($acc)) {
                                    echo round($acc);
                                } elseif ($acc == "A" && is_numeric($acc_o)) {
                                    echo round($acc_o);
                                } else {
                                    $total = is_numeric($acc_o) ? $acc_o : 0;
                                    $total += is_numeric($acc) ? $acc : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$hd != null && @$hd != '-') || (@$hd_o != null && @$hd_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Sulekh, Imla, Arts & Crafts </td>
                            <td style="text-align:left"> </td>
                            <td style="text-align:left">
                                <?php
                                $sum = 0;

                                if (is_numeric($fullmarks)) {
                                    $sum += $fullmarks;
                                }

                                if (is_numeric($fullmarks_o)) {
                                    $sum += $fullmarks_o;
                                }

                                echo $sum;
                                ?>
                            </td>
                            <th style="text-align:left"> </th>
                            <th style="text-align:left">
                                <?php
                                if (($hd_o == "A" && $hd == "A") || ($hd_o == "-" && $hd == "A") || ($hd_o == "A" && $hd == "-")) {
                                    echo "A";
                                } elseif ($hd_o == "-" && $hd == "-") {
                                    echo null;
                                } elseif ($hd_o == "A" && is_numeric($hd)) {
                                    echo round($hd);
                                } elseif ($hd == "A" && is_numeric($hd_o)) {
                                    echo round($hd_o);
                                } else {
                                    $total = is_numeric($hd_o) ? $hd_o : 0;
                                    $total += is_numeric($hd) ? $hd : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <th style="text-align:left">
                                <?php
                                if (($hd_o == "A" && $hd == "A") || ($hd_o == "-" && $hd == "A") || ($hd_o == "A" && $hd == "-")) {
                                    echo "A";
                                } elseif ($hd_o == "-" && $hd == "-") {
                                    echo null;
                                } elseif ($hd_o == "A" && is_numeric($hd)) {
                                    echo round($hd);
                                } elseif ($hd == "A" && is_numeric($hd_o)) {
                                    echo round($hd_o);
                                } else {
                                    $total = is_numeric($hd_o) ? $hd_o : 0;
                                    $total += is_numeric($hd) ? $hd : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$pt != null && @$pt != '-') || (@$pt_o != null && @$pt_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Physical Fitness </td>
                            <td style="text-align:left"><?php echo @$pt_o != null && @$pt_o != '-' ? $fullmarks_o : ''; ?> </td>
                            <td style="text-align:left"><?php echo @$pt != null && @$pt != '-' ? $fullmarks : ''; ?> </td>
                            <th style="text-align:left"> <?php echo is_numeric($pt_o) ? round($pt_o) : ($pt_o == '-' ? null : $pt_o); ?> </th>
                            <th style="text-align:left"> <?php echo is_numeric($pt) ? round($pt) : ($pt == '-' ? null : $pt); ?> </th>
                            <th style="text-align:left">
                                <?php
                                if (($pt_o == 'A' && $pt == 'A') || ($pt_o == "-" && $pt == "A") || ($pt_o == "A" && $pt == "-")) {
                                    echo 'A';
                                } elseif ($pt_o == '-' && $pt == '-') {
                                    echo null;
                                } elseif ($pt_o == 'A' && is_numeric($pt)) {
                                    echo 'A';
                                } elseif ($pt == 'A' && is_numeric($pt_o)) {
                                    echo 'A';
                                } else {
                                    $total = is_numeric($pt_o) ? $pt_o : 0;
                                    $total += is_numeric($pt) ? $pt : 0;
                                    echo is_numeric($total) ? round($total) : $total;
                                }
                                ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php
                    $totalMarks = 0; // Initialize total marks

                    // Language I
                    if ((@$hnd != null && @$hnd != "-") || (@$hnd_o != null && @$hnd_o != "-")) {
                        $totalMarks += is_numeric($hnd_o) ? round($hnd_o) : 0;
                        $totalMarks += is_numeric($hnd) ? round($hnd) : 0;
                    }

                    // English
                    if ((@$eng != null && @$eng != '-') || (@$eng_o != null && @$eng_o != '-')) {
                        $totalMarks += is_numeric($eng_o) ? round($eng_o) : 0;
                        $totalMarks += is_numeric($eng) ? round($eng) : 0;
                    }

                    // Mathematics
                    if ((@$mth != null && @$mth != '-') || (@$mth_o != null && @$mth_o != '-')) {
                        $totalMarks += is_numeric($mth_o) ? round($mth_o) : 0;
                        $totalMarks += is_numeric($mth) ? round($mth) : 0;
                    }

                    // Science
                    if ((@$sce != null && @$sce != '-') || (@$sce_o != null && @$sce_o != '-')) {
                        $totalMarks += is_numeric($sce_o) ? round($sce_o) : 0;
                        $totalMarks += is_numeric($sce) ? round($sce) : 0;
                    }

                    // Social Science
                    if ((@$ssc != null && @$ssc != '-') || (@$ssc_o != null && @$ssc_o != '-')) {
                        $totalMarks += is_numeric($ssc_o) ? round($ssc_o) : 0;
                        $totalMarks += is_numeric($ssc) ? round($ssc) : 0;
                    }

                    // General Knowledge
                    if ((@$gka != null && @$gka != '-') || (@$gka_o != null && @$gka_o != '-')) {
                        $totalMarks += is_numeric($gka_o) ? round($gka_o) : 0;
                        $totalMarks += is_numeric($gka) ? round($gka) : 0;
                    }

                    // Computer
                    if ((@$com != null && @$com != '-') || (@$com_o != null && @$com_o != '-')) {
                        $totalMarks += is_numeric($com_o) ? round($com_o) : 0;
                        $totalMarks += is_numeric($com) ? round($com) : 0;
                    }

                    // Biology/Life science
                    if ((@$bio != null && @$bio != '-') || (@$bio_o != null && @$bio_o != '-')) {
                        $totalMarks += is_numeric($bio_o) ? round($bio_o) : 0;
                        $totalMarks += is_numeric($bio) ? round($bio) : 0;
                    }

                    // Physics/Physical science
                    if ((@$phy != null && @$phy != '-') || (@$phy_o != null && @$phy_o != '-')) {
                        $totalMarks += is_numeric($phy_o) ? round($phy_o) : 0;
                        $totalMarks += is_numeric($phy) ? round($phy) : 0;
                    }

                    // Chemistry
                    if ((@$chm != null && @$chm != '-') || (@$chm_o != null && @$chm_o != '-')) {
                        $totalMarks += is_numeric($chm_o) ? round($chm_o) : 0;
                        $totalMarks += is_numeric($chm) ? round($chm) : 0;
                    }

                    // Accountancy
                    if ((@$acc != null && @$acc != '-') || (@$acc_o != null && @$acc_o != '-')) {
                        $totalMarks += is_numeric($acc_o) ? round($acc_o) : 0;
                        $totalMarks += is_numeric($acc) ? round($acc) : 0;
                    }

                    // Sulekh, Imla, Arts & Crafts
                    if ((@$hd != null && @$hd != '-') || (@$hd_o != null && @$hd_o != '-')) {
                        $totalMarks += is_numeric($hd_o) ? round($hd_o) : 0;
                        $totalMarks += is_numeric($hd) ? round($hd) : 0;
                    }

                    // Physical Fitness
                    if ((@$pt != null && @$pt != '-') || (@$pt_o != null && @$pt_o != '-')) {
                        $totalMarks += is_numeric($pt_o) ? round($pt_o) : 0;
                        $totalMarks += is_numeric($pt) ? round($pt) : 0;
                    }
                    ?>
                    <?php
                    function calculateGrade($percentage)
                    {
                        if ($percentage >= 90) {
                            return ["A1", "Excellent"];
                        } elseif ($percentage >= 80) {
                            return ["A2", "Very Good"];
                        } elseif ($percentage >= 70) {
                            return ["B1", "Good"];
                        } elseif ($percentage >= 60) {
                            return ["B2", "Above Average"];
                        } elseif ($percentage >= 50) {
                            return ["C1", "Fair / Average"];
                        } elseif ($percentage >= 40) {
                            return ["C2", "Below Average"];
                        } elseif ($percentage >= 20) {
                            return ["D", "Poor"];
                        } else {
                            return ["E", "Fail"];
                        }
                    }
                    ?>

                    <tr bgcolor="#428BCA" style="color: #fff;">
                        <th style="text-align:left"></th>
                        <th style="text-align:left" colspan="2"> <?php echo $mm ?> </th>
                        <th style="text-align:left" colspan="2"></th>
                        <?php
                        $percentage = (round($totalMarks) / $mm) * 100;
                        $formattedPercentage = number_format($percentage, 2);
                        $gradeAndDesc = calculateGrade($percentage);
                        // Determine pass or fail based on the formatted percentage
                        $passOrFail = ($formattedPercentage < 20) ? 'Fail' : 'Pass';
                        ?>
                        <th style="text-align:left"> <?php echo round($totalMarks) ?> (<?php echo $formattedPercentage; ?>%) </th>
                        <th style="text-align:left"> <?php echo $gradeAndDesc[0] . ' - ' . $gradeAndDesc[1]; ?> </th>
                    </tr>
                </tbody>
            </table>
            <table border="0" align="center" style="width: 100%;">
                <?php if ((@$hnd != null && @$hnd != '-') || (@$hnd_o != null && @$hnd_o != '-')) { ?>
                    <tr>
                        <td style="text-align:left">Language I - <?php echo $language1 ?></td>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <td style="text-align:left"></td>
                    </tr>
                <?php } ?>
            </table>
            <br>

            <table class="table" border="0" align="right" style="width: 50%;">
                <tbody>
                    <tr>
                        <td style="text-align:left"> Result </td>
                        <th style="text-align:left"><?php echo strtoupper($passOrFail) ?></th>
                    </tr>
                    <tr>
                        <td style="text-align:left"> Overall ranking </th>
                        <th style="text-align:left"><?php echo $position ?></th>
                    </tr>
                    <tr>
                        <td style="text-align:left"> Attendance (<?php echo $month ?>) </th>
                        <th style="text-align:left"><?php echo $attd ?></th>
                    </tr>
                </tbody>
            </table>
            <table class="table visible-xs" border="0" align="left" style="width: 40%; margin-left:0%; margin-top:20%;">
                <tbody>
                    <tr>
                        <td style="text-align:left">Signature of Class Teacher / Center In-charge<br><br>Date:</td>
                    </tr>
                </tbody>
            </table>
            <p class="report-footer visible-xs" style="text-align: right;">A - Absent denotes that the student was absent during the exam for that particular subject.</p>

            <!-- <div class="footer no-display" style="width: 97%;">
                <p style="text-align: left;">Report card generated:&nbsp;<?php echo @date("d/m/Y g:i a", strtotime($date)) ?></p>
            </div> -->

        <?php
        } else if ($id == "" && $stid == "") {
        ?>
            <div class="noprint">

                <style>
                    /* CSS styles for the welcome message */
                    h1 {
                        font-size: 36px;
                        text-align: center;
                        margin-top: 50px;
                    }

                    p {
                        font-size: 24px;
                        text-align: center;
                        margin-top: 20px;
                    }

                    /* CSS styles for the form */
                    form {
                        margin: 50px auto;
                        width: 400px;
                        border: 2px solid #ccc;
                        padding: 20px;
                        border-radius: 10px;
                    }
                </style>

                <!-- Welcome message -->
                <h1>Welcome to the Online Result Portal of RSSI NGO</h1>
                <p>Please enter your Student ID, Exam Name and Year to view your result.</p>

                <!-- Form for entering student ID, name of the examination, and year -->

            </div>
        <?php
        } else {
        ?>
            No record found for <?php echo $stid ?>&nbsp;<?php echo $id ?>&nbsp;<?php echo $year ?>

        <?php }
        ?>
    </div>
    <script>
        function submit() {
            document.getElementById("formid").click(); // Simulates button click
            document.lostpasswordform.submit(); // Submits the form without the button
        }
    </script>
    <script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

</body>

</html>