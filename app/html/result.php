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
    $total = $row[30];
    $mm = $row[31];
    $op = $row[32];
    $grade = $row[33];
    $result = $row[34];
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
            font-family: "Roboto";
            font-style: normal;
            font-weight: 400;
            overflow-x: hidden;
            margin: 0;
            font-size: 14px;
            color: #444;
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

                    <input name="get_stid" class="form-control" style="width:max-content; display:inline-block" required placeholder="Student ID" value="<?php echo @$stid ?>">

                    <select name="get_id" class="form-control" style="width:max-content; display:inline-block" required>
                        <?php if ($id == null) { ?>
                            <option value="" disabled selected hidden>Select Exam name</option>
                        <?php
                        } else { ?>
                            <option hidden selected><?php echo $id ?></option>
                        <?php }
                        ?>
                        <option>First Term Exam</option>
                        <option>Half Yearly Exam</option>
                        <option>Annual Exam</option>
                    </select>
                    <select name="get_year" id="get_year" class="form-control" style="width:max-content;display:inline-block" required>
                        <?php if ($year == null) { ?>
                            <option value="" disabled selected hidden>Select Year</option>
                        <?php
                        } else { ?>
                            <option hidden selected><?php echo $year ?></option>
                        <?php }
                        ?>
                    </select>
                    <div class="col topbutton" style="display: inline-block;">
                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                            <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                        <button type="button" onclick="window.print()" name="print" class="btn btn-danger btn-sm" style="outline: none;">
                            <i class="fa-solid fa-print"></i>&nbsp;Save</button>
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
                                    <p style="font-size: small;">624V/195/01, Vijayipur, Vijaipur Village, Vishesh Khand 2, Gomti Nagar, Lucknow, Uttar Pradesh 226010</p>
                                    <p style="font-size: small;">CIN— U80101WB2020NPL237900</p>
                                </div>
                                <div class="col" style="display: inline-block; width:32%; vertical-align: top;">
                                    <p style="font-size: small;">Scan QR code to check authenticity</p>
                                    <?php $url = "https://login.rssi.in/result.php?get_stid=$stid&get_id=$id";
                                    $url = urlencode($url); ?>
                                    <img class="qrimage" src="https://chart.googleapis.com/chart?chs=85x85&cht=qr&chl=<?php echo $url ?>" width="100px" />
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
                        <th><?php echo $dateofbirth ?></th>
                    </tr>
                    <tr>
                        <td colspan="2"></td>
                        <th colspan="2"><?php echo $examname ?>&nbsp;&nspar;&nbsp;Academic Year:&nbsp;<?php echo $academicyear ?></th>
                    </tr>
                </tbody>
            </table>

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
                            <td style="text-align:left"><?php if (@$hnd_o != null && @$hnd_o != "-") {
                                                            echo $fullmarks_o;
                                                        } ?> </td>
                            <td style="text-align:left"><?php if (@$hnd != null && @$hnd != "-") {
                                                            echo $fullmarks;
                                                        } ?> </td>
                            <th style="text-align:left"> <?php if (@$hnd_o != null && @$hnd_o != "-") {
                                                                echo $hnd_o;
                                                            } ?> </th>
                            <th style="text-align:left"> <?php if (@$hnd != null && @$hnd != "-") {
                                                                echo $hnd;
                                                            } ?> </th>
                            <th style="text-align:left">
                                <?php
                                if ($hnd_o == "-" && $hnd == "-") {
                                    echo null;
                                }
                                if ($hnd_o == "-" && $hnd == "A") {
                                    echo "A";
                                }
                                if ($hnd_o == "A" && $hnd == "-") {
                                    echo "A";
                                }
                                if ($hnd_o == "A" && $hnd == "A") {
                                    echo "A";
                                }

                                if ($hnd_o == "-" && ($hnd != "A" && $hnd != "-")) {
                                    echo $hnd;
                                }
                                if ($hnd == "-" && ($hnd_o != "A" && $hnd_o != "-")) {
                                    echo $hnd_o;
                                }

                                if ($hnd_o == "A" && ($hnd != "A" && $hnd != "-")) {
                                    echo $hnd;
                                }
                                if ($hnd == "A" && ($hnd_o != "A" && $hnd_o != "-")) {
                                    echo $hnd_o;
                                }
                                if (($hnd != "A" && $hnd != "-") && ($hnd_o != "A" && $hnd_o != "-")) {
                                    echo $hnd_o + $hnd;
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
                            <td style="text-align:left"><?php if (@$eng_o != null && @$eng_o != '-') {
                                                            echo $fullmarks_o;
                                                        } ?> </td>
                            <td style="text-align:left"><?php if (@$eng != null && @$eng != '-') {
                                                            echo $fullmarks;
                                                        } ?> </td>
                            <th style="text-align:left"> <?php if (@$eng_o != null && @$eng_o != '-') {
                                                                echo $eng_o;
                                                            } ?> </th>
                            <th style="text-align:left"> <?php if (@$eng != null && @$eng != '-') {
                                                                echo $eng;
                                                            } ?> </th>
                            <th style="text-align:left">
                                <?php if ($eng_o == "-" && $eng == "-") {
                                    echo null;
                                }
                                if ($eng_o == "-" && $eng == "A") {
                                    echo "A";
                                }
                                if ($eng_o == "A" && $eng == "-") {
                                    echo "A";
                                }
                                if ($eng_o == "A" && $eng == "A") {
                                    echo "A";
                                }

                                if ($eng_o == "-" && ($eng != "A" && $eng != "-")) {
                                    echo $eng;
                                }
                                if ($eng == "-" && ($eng_o != "A" && $eng_o != "-")) {
                                    echo $eng_o;
                                }

                                if ($eng_o == "A" && ($eng != "A" && $eng != "-")) {
                                    echo $eng;
                                }
                                if ($eng == "A" && ($eng_o != "A" && $eng_o != "-")) {
                                    echo $eng_o;
                                }
                                if (($eng != "A" && $eng != "-") && ($eng_o != "A" && $eng_o != "-")) {
                                    echo $eng_o + $eng;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$mth != null && @$mth != '-') || (@$mth_o != null && @$mth_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Mathematics </td>
                            <td style="text-align:left"><?php if (@$mth_o != null && @$mth_o != '-') {
                                                            echo $fullmarks_o;
                                                        } ?> </td>
                            <td style="text-align:left"><?php if (@$mth != null && @$mth != '-') {
                                                            echo $fullmarks;
                                                        } ?> </td>
                            <th style="text-align:left"> <?php if (@$mth_o != null && @$mth_o != '-') {
                                                                echo $mth_o;
                                                            } ?> </th>
                            <th style="text-align:left"> <?php if (@$mth != null && @$mth != '-') {
                                                                echo $mth;
                                                            } ?> </th>
                            <th style="text-align:left">
                                <?php if ($mth_o == "-" && $mth == "-") {
                                    echo null;
                                }
                                if ($mth_o == "-" && $mth == "A") {
                                    echo "A";
                                }
                                if ($mth_o == "A" && $mth == "-") {
                                    echo "A";
                                }
                                if ($mth_o == "A" && $mth == "A") {
                                    echo "A";
                                }

                                if ($mth_o == "-" && ($mth != "A" && $mth != "-")) {
                                    echo $mth;
                                }
                                if ($mth == "-" && ($mth_o != "A" && $mth_o != "-")) {
                                    echo $mth_o;
                                }

                                if ($mth_o == "A" && ($mth != "A" && $mth != "-")) {
                                    echo $mth;
                                }
                                if ($mth == "A" && ($mth_o != "A" && $mth_o != "-")) {
                                    echo $mth_o;
                                }
                                if (($mth != "A" && $mth != "-") && ($mth_o != "A" && $mth_o != "-")) {
                                    echo $mth_o + $mth;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$sce != null && @$sce != '-') || (@$sce_o != null && @$sce_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Science </td>
                            <td style="text-align:left"><?php if (@$sce_o != null && @$sce_o != '-') {
                                                            echo $fullmarks_o;
                                                        } ?> </td>
                            <td style="text-align:left"><?php if (@$sce != null && @$sce != '-') {
                                                            echo $fullmarks;
                                                        } ?> </td>
                            <th style="text-align:left"> <?php if (@$sce_o != null && @$sce_o != '-') {
                                                                echo $sce_o;
                                                            } ?> </th>
                            <th style="text-align:left"> <?php if (@$sce != null && @$sce != '-') {
                                                                echo $sce;
                                                            } ?> </th>
                            <th style="text-align:left">
                                <?php if ($sce_o == "-" && $sce == "-") {
                                    echo null;
                                }
                                if ($sce_o == "-" && $sce == "A") {
                                    echo "A";
                                }
                                if ($sce_o == "A" && $sce == "-") {
                                    echo "A";
                                }
                                if ($sce_o == "A" && $sce == "A") {
                                    echo "A";
                                }

                                if ($sce_o == "-" && ($sce != "A" && $sce != "-")) {
                                    echo $sce;
                                }
                                if ($sce == "-" && ($sce_o != "A" && $sce_o != "-")) {
                                    echo $sce_o;
                                }

                                if ($sce_o == "A" && ($sce != "A" && $sce != "-")) {
                                    echo $sce;
                                }
                                if ($sce == "A" && ($sce_o != "A" && $sce_o != "-")) {
                                    echo $sce_o;
                                }
                                if (($sce != "A" && $sce != "-") && ($sce_o != "A" && $sce_o != "-")) {
                                    echo $sce_o + $sce;
                                } ?></th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$ssc != null && @$ssc != '-') || (@$ssc_o != null && @$ssc_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Social Science </td>
                            <td style="text-align:left"><?php if (@$ssc_o != null && @$ssc_o != '-') {
                                                            echo $fullmarks_o;
                                                        } ?> </td>
                            <td style="text-align:left"><?php if (@$ssc != null && @$ssc != '-') {
                                                            echo $fullmarks;
                                                        } ?> </td>
                            <th style="text-align:left"> <?php if (@$ssc_o != null && @$ssc_o != '-') {
                                                                echo $ssc_o;
                                                            } ?> </th>
                            <th style="text-align:left"> <?php if (@$ssc != null && @$ssc != '-') {
                                                                echo $ssc;
                                                            } ?> </th>
                            <th style="text-align:left">
                                <?php if ($ssc_o == "-" && $ssc == "-") {
                                    echo null;
                                }
                                if ($ssc_o == "-" && $ssc == "A") {
                                    echo "A";
                                }
                                if ($ssc_o == "A" && $ssc == "-") {
                                    echo "A";
                                }
                                if ($ssc_o == "A" && $ssc == "A") {
                                    echo "A";
                                }

                                if ($ssc_o == "-" && ($ssc != "A" && $ssc != "-")) {
                                    echo $ssc;
                                }
                                if ($ssc == "-" && ($ssc_o != "A" && $ssc_o != "-")) {
                                    echo $ssc_o;
                                }

                                if ($ssc_o == "A" && ($ssc != "A" && $ssc != "-")) {
                                    echo $ssc;
                                }
                                if ($ssc == "A" && ($ssc_o != "A" && $ssc_o != "-")) {
                                    echo $ssc_o;
                                }
                                if (($ssc != "A" && $ssc != "-") && ($ssc_o != "A" && $ssc_o != "-")) {
                                    echo $ssc_o + $ssc;
                                } ?>
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
                            <td style="text-align:left"> <?php if (@$gka_o != null && @$gka_o != '-') {
                                                                echo $fullmarks_o;
                                                            } ?> </td>
                            <td style="text-align:left"> <?php if (@$gka != null && @$gka != '-') {
                                                                echo $fullmarks;
                                                            } ?> </td>
                            <th style="text-align:left"> <?php if (@$gka_o != null && @$gka_o != '-') {
                                                                echo $gka_o;
                                                            } ?> </th>
                            <th style="text-align:left"> <?php if (@$gka != null && @$gka != '-') {
                                                                echo $gka;
                                                            } ?> </th>
                            <th style="text-align:left">
                                <?php if ($gka_o == "-" && $gka == "-") {
                                    echo null;
                                }
                                if ($gka_o == "-" && $gka == "A") {
                                    echo "A";
                                }
                                if ($gka_o == "A" && $gka == "-") {
                                    echo "A";
                                }
                                if ($gka_o == "A" && $gka == "A") {
                                    echo "A";
                                }

                                if ($gka_o == "-" && ($gka != "A" && $gka != "-")) {
                                    echo $gka;
                                }
                                if ($gka == "-" && ($gka_o != "A" && $gka_o != "-")) {
                                    echo $gka_o;
                                }

                                if ($gka_o == "A" && ($gka != "A" && $gka != "-")) {
                                    echo $gka;
                                }
                                if ($gka == "A" && ($gka_o != "A" && $gka_o != "-")) {
                                    echo $gka_o;
                                }
                                if (($gka != "A" && $gka != "-") && ($gka_o != "A" && $gka_o != "-")) {
                                    echo $gka_o + $gka;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$com != null && @$com != '-') || (@$com_o != null && @$com_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Computer </td>
                            <td style="text-align:left"><?php if (@$com_o != null && @$com_o != '-') {
                                                            echo $fullmarks_o;
                                                        } ?> </td>
                            <td style="text-align:left"><?php if (@$com != null && @$com != '-') {
                                                            echo $fullmarks;
                                                        } ?> </td>
                            <th style="text-align:left"> <?php if (@$com_o != null && @$com_o != '-') {
                                                                echo $com_o;
                                                            } ?> </th>
                            <th style="text-align:left"> <?php if (@$com != null && @$com != '-') {
                                                                echo $com;
                                                            } ?> </th>
                            <th style="text-align:left">
                                <?php if ($com_o == "-" && $com == "-") {
                                    echo null;
                                }
                                if ($com_o == "-" && $com == "A") {
                                    echo "A";
                                }
                                if ($com_o == "A" && $com == "-") {
                                    echo "A";
                                }
                                if ($com_o == "A" && $com == "A") {
                                    echo "A";
                                }

                                if ($com_o == "-" && ($com != "A" && $com != "-")) {
                                    echo $com;
                                }
                                if ($com == "-" && ($com_o != "A" && $com_o != "-")) {
                                    echo $com_o;
                                }

                                if ($com_o == "A" && ($com != "A" && $com != "-")) {
                                    echo $com;
                                }
                                if ($com == "A" && ($com_o != "A" && $com_o != "-")) {
                                    echo $com_o;
                                }
                                if (($com != "A" && $com != "-") && ($com_o != "A" && $com_o != "-")) {
                                    echo $com_o + $com;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$bio != null && @$bio != '-') || (@$bio_o != null && @$bio_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Biology/Life science </td>
                            <td style="text-align:left"><?php if (@$bio_o != null && @$bio_o != '-') {
                                                            echo $fullmarks_o;
                                                        } ?> </td>
                            <td style="text-align:left"><?php if (@$bio != null && @$bio != '-') {
                                                            echo $fullmarks;
                                                        } ?> </td>
                            <th style="text-align:left"> <?php if (@$bio_o != null && @$bio_o != '-') {
                                                                echo $bio_o;
                                                            } ?> </th>
                            <th style="text-align:left"> <?php if (@$bio != null && @$bio != '-') {
                                                                echo $bio;
                                                            } ?> </th>
                            <th style="text-align:left">
                                <?php if ($bio_o == "-" && $bio == "-") {
                                    echo null;
                                }
                                if ($bio_o == "-" && $bio == "A") {
                                    echo "A";
                                }
                                if ($bio_o == "A" && $bio == "-") {
                                    echo "A";
                                }
                                if ($bio_o == "A" && $bio == "A") {
                                    echo "A";
                                }

                                if ($bio_o == "-" && ($bio != "A" && $bio != "-")) {
                                    echo $bio;
                                }
                                if ($bio == "-" && ($bio_o != "A" && $bio_o != "-")) {
                                    echo $bio_o;
                                }

                                if ($bio_o == "A" && ($bio != "A" && $bio != "-")) {
                                    echo $bio;
                                }
                                if ($bio == "A" && ($bio_o != "A" && $bio_o != "-")) {
                                    echo $bio_o;
                                }
                                if (($bio != "A" && $bio != "-") && ($bio_o != "A" && $bio_o != "-")) {
                                    echo $bio_o + $bio;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$phy != null && @$phy != '-') || (@$phy_o != null && @$phy_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Physics/Physical science </td>
                            <td style="text-align:left"><?php if (@$phy_o != null && @$phy_o != '-') {
                                                            echo $fullmarks_o;
                                                        } ?> </td>
                            <td style="text-align:left"><?php if (@$phy != null && @$phy != '-') {
                                                            echo $fullmarks;
                                                        } ?> </td>
                            <th style="text-align:left"> <?php if (@$phy_o != null && @$phy_o != '-') {
                                                                echo $phy_o;
                                                            } ?> </th>
                            <th style="text-align:left"> <?php if (@$phy != null && @$phy != '-') {
                                                                echo $phy;
                                                            } ?> </th>
                            <th style="text-align:left">
                                <?php if ($phy_o == "-" && $phy == "-") {
                                    echo null;
                                }
                                if ($phy_o == "-" && $phy == "A") {
                                    echo "A";
                                }
                                if ($phy_o == "A" && $phy == "-") {
                                    echo "A";
                                }
                                if ($phy_o == "A" && $phy == "A") {
                                    echo "A";
                                }

                                if ($phy_o == "-" && ($phy != "A" && $phy != "-")) {
                                    echo $phy;
                                }
                                if ($phy == "-" && ($phy_o != "A" && $phy_o != "-")) {
                                    echo $phy_o;
                                }

                                if ($phy_o == "A" && ($phy != "A" && $phy != "-")) {
                                    echo $phy;
                                }
                                if ($phy == "A" && ($phy_o != "A" && $phy_o != "-")) {
                                    echo $phy_o;
                                }
                                if (($phy != "A" && $phy != "-") && ($phy_o != "A" && $phy_o != "-")) {
                                    echo $phy_o + $phy;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$chm != null && @$chm != '-') || (@$chm_o != null && @$chm_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Chemistry </td>
                            <td style="text-align:left"><?php if (@$chm_o != null && @$chm_o != '-') {
                                                            echo $fullmarks_o;
                                                        } ?> </td>
                            <td style="text-align:left"><?php if (@$chm != null && @$chm != '-') {
                                                            echo $fullmarks;
                                                        } ?> </td>
                            <th style="text-align:left"> <?php if (@$chm_o != null && @$chm_o != '-') {
                                                                echo $chm_o;
                                                            } ?> </th>
                            <th style="text-align:left"> <?php if (@$chm != null && @$chm != '-') {
                                                                echo $chm;
                                                            } ?> </th>
                            <th style="text-align:left">
                                <?php if ($chm_o == "-" && $chm == "-") {
                                    echo null;
                                }
                                if ($chm_o == "-" && $chm == "A") {
                                    echo "A";
                                }
                                if ($chm_o == "A" && $chm == "-") {
                                    echo "A";
                                }
                                if ($chm_o == "A" && $chm == "A") {
                                    echo "A";
                                }

                                if ($chm_o == "-" && ($chm != "A" && $chm != "-")) {
                                    echo $chm;
                                }
                                if ($chm == "-" && ($chm_o != "A" && $chm_o != "-")) {
                                    echo $chm_o;
                                }

                                if ($chm_o == "A" && ($chm != "A" && $chm != "-")) {
                                    echo $chm;
                                }
                                if ($chm == "A" && ($chm_o != "A" && $chm_o != "-")) {
                                    echo $chm_o;
                                }
                                if (($chm != "A" && $chm != "-") && ($chm_o != "A" && $chm_o != "-")) {
                                    echo $chm_o + $chm;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$acc != null && @$acc != '-') || (@$acc_o != null && @$acc_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Accountancy </td>
                            <td style="text-align:left"><?php if (@$acc_o != null && @$acc_o != '-') {
                                                            echo $fullmarks_o;
                                                        } ?> </td>
                            <td style="text-align:left"><?php if (@$acc != null && @$acc != '-') {
                                                            echo $fullmarks;
                                                        } ?> </td>
                            <th style="text-align:left"> <?php if (@$acc_o != null && @$acc_o != '-') {
                                                                echo $acc_o;
                                                            } ?> </th>
                            <th style="text-align:left"> <?php if (@$acc != null && @$acc != '-') {
                                                                echo $acc;
                                                            } ?> </th>
                            <th style="text-align:left">
                                <?php if ($acc_o == "-" && $acc == "-") {
                                    echo null;
                                }
                                if ($acc_o == "-" && $acc == "A") {
                                    echo "A";
                                }
                                if ($acc_o == "A" && $acc == "-") {
                                    echo "A";
                                }
                                if ($acc_o == "A" && $acc == "A") {
                                    echo "A";
                                }

                                if ($acc_o == "-" && ($acc != "A" && $acc != "-")) {
                                    echo $acc;
                                }
                                if ($acc == "-" && ($acc_o != "A" && $acc_o != "-")) {
                                    echo $acc_o;
                                }

                                if ($acc_o == "A" && ($acc != "A" && $acc != "-")) {
                                    echo $acc;
                                }
                                if ($acc == "A" && ($acc_o != "A" && $acc_o != "-")) {
                                    echo $acc_o;
                                }
                                if (($acc != "A" && $acc != "-") && ($acc_o != "A" && $acc_o != "-")) {
                                    echo $acc_o + $acc;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$hd != null && @$hd != '-') || (@$hd_o != null && @$hd_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Sulekh, Imla, Arts & Crafts </td>
                            <td style="text-align:left"> </td>
                            <td style="text-align:left"> <?php echo $fullmarks + $fullmarks_o ?></td>
                            <th style="text-align:left"> </th>
                            <th style="text-align:left">
                                <?php if ($hd_o == "-" && $hd == "-") {
                                    echo null;
                                }
                                if ($hd_o == "-" && $hd == "A") {
                                    echo "A";
                                }
                                if ($hd_o == "A" && $hd == "-") {
                                    echo "A";
                                }
                                if ($hd_o == "A" && $hd == "A") {
                                    echo "A";
                                }

                                if ($hd_o == "-" && ($hd != "A" && $hd != "-")) {
                                    echo $hd;
                                }
                                if ($hd == "-" && ($hd_o != "A" && $hd_o != "-")) {
                                    echo $hd_o;
                                }

                                if ($hd_o == "A" && ($hd != "A" && $hd != "-")) {
                                    echo $hd;
                                }
                                if ($hd == "A" && ($hd_o != "A" && $hd_o != "-")) {
                                    echo $hd_o;
                                }
                                if (($hd != "A" && $hd != "-") && ($hd_o != "A" && $hd_o != "-")) {
                                    echo $hd_o + $hd;
                                } ?>
                            </th>
                            <th style="text-align:left">
                                <?php if ($hd_o == "-" && $hd == "-") {
                                    echo null;
                                }
                                if ($hd_o == "-" && $hd == "A") {
                                    echo "A";
                                }
                                if ($hd_o == "A" && $hd == "-") {
                                    echo "A";
                                }
                                if ($hd_o == "A" && $hd == "A") {
                                    echo "A";
                                }

                                if ($hd_o == "-" && ($hd != "A" && $hd != "-")) {
                                    echo $hd;
                                }
                                if ($hd == "-" && ($hd_o != "A" && $hd_o != "-")) {
                                    echo $hd_o;
                                }

                                if ($hd_o == "A" && ($hd != "A" && $hd != "-")) {
                                    echo $hd;
                                }
                                if ($hd == "A" && ($hd_o != "A" && $hd_o != "-")) {
                                    echo $hd_o;
                                }
                                if (($hd != "A" && $hd != "-") && ($hd_o != "A" && $hd_o != "-")) {
                                    echo $hd_o + $hd;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if ((@$pt != null && @$pt != '-') || (@$pt_o != null && @$pt_o != '-')) { ?>
                        <tr>
                            <td style="text-align:left"> Physical Fitness </td>
                            <td style="text-align:left"><?php if (@$pt_o != null && @$pt_o != '-') {
                                                            echo $fullmarks_o;
                                                        } ?> </td>
                            <td style="text-align:left"><?php if (@$pt != null && @$pt != '-') {
                                                            echo $fullmarks;
                                                        } ?> </td>
                            <th style="text-align:left"> <?php if (@$pt_o != null && @$pt_o != '-') {
                                                                echo $pt_o;
                                                            } ?> </th>
                            <th style="text-align:left"> <?php if (@$pt != null && @$pt != '-') {
                                                                echo $pt;
                                                            } ?> </th>
                            <th style="text-align:left">
                                <?php if ($pt_o == "-" && $pt == "-") {
                                    echo null;
                                }
                                if ($pt_o == "-" && $pt == "A") {
                                    echo "A";
                                }
                                if ($pt_o == "A" && $pt == "-") {
                                    echo "A";
                                }
                                if ($pt_o == "A" && $pt == "A") {
                                    echo "A";
                                }

                                if ($pt_o == "-" && ($pt != "A" && $pt != "-")) {
                                    echo $pt;
                                }
                                if ($pt == "-" && ($pt_o != "A" && $pt_o != "-")) {
                                    echo $pt_o;
                                }

                                if ($pt_o == "A" && ($pt != "A" && $pt != "-")) {
                                    echo $pt;
                                }
                                if ($pt == "A" && ($pt_o != "A" && $pt_o != "-")) {
                                    echo $pt_o;
                                }
                                if (($pt != "A" && $pt != "-") && ($pt_o != "A" && $pt_o != "-")) {
                                    echo $pt_o + $pt;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <tr bgcolor="#428BCA" style="color: #fff;">
                        <th style="text-align:left"></th>
                        <th style="text-align:left" colspan="2"> <?php echo $mm ?> </th>
                        <th style="text-align:left" colspan="2"></th>
                        <th style="text-align:left"> <?php echo $total ?> (<?php echo $op ?>%) </th>
                        <th style="text-align:left"> <?php echo $grade ?> </th>
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
                        <th style="text-align:left"><?php echo strtoupper($result) ?></th>
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

                    label {
                        display: block;
                        font-size: 20px;
                        margin-bottom: 10px;
                    }

                    input[type=text] {
                        font-size: 18px;
                        padding: 5px 10px;
                        border-radius: 5px;
                        border: 2px solid #ccc;
                        width: 100%;
                        margin-bottom: 20px;
                    }

                    input[type=submit] {
                        background-color: #4CAF50;
                        color: white;
                        font-size: 18px;
                        padding: 10px 20px;
                        border-radius: 5px;
                        border: none;
                        cursor: pointer;
                    }
                </style>

                <!-- Welcome message -->
                <h1>Welcome to the Online Result Portal</h1>
                <p>Please enter your student ID, name of the examination, and year to view your results.</p>

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