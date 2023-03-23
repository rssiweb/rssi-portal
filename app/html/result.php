<?php
require_once __DIR__ . '/../bootstrap.php';

include(__DIR__ . "/../util/login_util.php");

date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');

@$id = isset($_GET["get_id"]) ? $_GET["get_id"] : "Half Yearly Exam"; //$_GET['get_id'];
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
    <title><?php echo $student_id ?>_<?php echo $id ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Comfortaa');

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
            <div class="noprint top">
                <div style="font-family:Poppins; text-align:Center;font-size:20px;">Rina Shiksha Sahayak Foundation (RSSI)</div>
                <div style="font-family:Roboto; text-align:Center;font-size:20px; line-height:2">Online Result Portal</div><br>
            </div>

            <div class="noprint">

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
                        <option value="First Term Exam" <?php if (isset($_GET['get_id']) and $_GET['get_id'] == 'First Term Exam') {
                                                        echo ('selected="First Term Exam"');
                                                    } ?>>First Term Exam</option>
                        <option value="Half Yearly Exam" <?php if (isset($_GET['get_id']) and $_GET['get_id'] == 'Half Yearly Exam') {
                                                        echo ('selected="Half Yearly Exam"');
                                                    } ?>>Half Yearly Exam</option>
                        <option value="Annual Exam" <?php if (isset($_GET['get_id']) and $_GET['get_id'] == 'Annual Exam') {
                                                        echo ('selected="Annual Exam"');
                                                    } ?>>Annual Exam</option>
                    </select>
                    <select name="get_year" class="form-control" style="width:max-content;display:inline-block" required>
                                    <?php if ($year == null) { ?>
                                        <option value="" disabled selected hidden>Select Year</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $year ?></option>
                                    <?php }
                                    ?>
                                    <option>2022-2023</option>
                                    <option>2021-2022</option>
                                    <option>2020-2021</option>
                                </select>
                    <div class="col topbutton" style="display: inline-block;">
                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                            <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                        <button type="button" onclick="window.print()" name="print" class="btn btn-info btn-sm" style="outline: none;">
                            <i class="fa-regular fa-floppy-disk"></i>&nbsp;Save</button>
                    </div>
                </form><br>
            </div>

        <?php } ?>


        <?php if (@$examname > 0) {
        ?>
            <table class="table" border="0">
                <thead> <!--class="no-display"-->
                    <tr>
                        <td colspan=4>
                            <div class="row">
                                <div class="col" style="display: inline-block; width:65%;">

                                    <p><b>Rina Shiksha Sahayak Foundation (RSSI)</b></p>
                                    <p style="font-size: small;">624V/195/01, Vijayipur, Vijaipur Village, Vishesh Khand 2, Gomti Nagar, Lucknow, Uttar Pradesh 226010</p>
                                    <p style="font-size: small;">CIN— U80101WB2020NPL237900</p>
                                </div>
                                <div class="col" style="display: inline-block; width:32%; vertical-align: top;">
                                    Scan QR code to check authenticity
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
                            <h3 style="text-align:center;margin-top: 10px;">Report card</h3>
                        </td>
                    </tr>
                    <tr>
                        <td>Registration Number </td>
                        <th><?php echo $studentid ?></th>
                        <td> Learning Group/Class </td>
                        <th><?php echo $category ?>/<?php echo $class ?></th>
                    </tr>
                    <tr>
                        <td> Name </td>
                        <th><?php echo $studentname ?></th>
                        <td>Name of the examination</td>
                        <th><?php echo $examname ?><br><?php echo $academicyear ?></th>
                    </tr>
                    <tr>
                        <td> Date Of Birth </td>
                        <th><?php echo $dateofbirth ?></th>
                        <td colspan="2"></td>
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
                    <?php if (@$hnd != null && @$hnd != "-") { ?>
                        <tr>
                            <td style="text-align:left"> Language I </td>
                            <td style="text-align:left"><?php echo $fullmarks_o ?> </td>
                            <td style="text-align:left"><?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $hnd_o ?> </th>
                            <th style="text-align:left"> <?php echo $hnd ?> </th>
                            <th style="text-align:left">
                                <?php if (($hnd_o == null || $hnd == null) || ($hnd_o == 'A' || $hnd == 'A')) {
                                    echo $hnd_o . $hnd;
                                } else {
                                    echo $hnd_o + $hnd;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$eng != null && @$eng != '-') { ?>
                        <tr>
                            <td style="text-align:left"> English </td>
                            <td style="text-align:left"> <?php echo $fullmarks_o ?> </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $eng_o ?> </th>
                            <th style="text-align:left"> <?php echo $eng ?> </th>
                            <th style="text-align:left">
                                <?php if (($eng_o == null || $eng == null) || ($eng_o == 'A' || $eng == 'A')) {
                                    echo $eng_o . $eng;
                                } else {
                                    echo $eng_o + $eng;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$mth != null && @$mth != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Mathematics </td>
                            <td style="text-align:left"> <?php echo $fullmarks_o ?></td>
                            <td style="text-align:left"> <?php echo $fullmarks ?></td>
                            <th style="text-align:left"> <?php echo $mth_o ?> </th>
                            <th style="text-align:left"> <?php echo $mth ?> </th>
                            <th style="text-align:left">
                                <?php if (($mth_o == null || $mth == null) || ($mth_o == 'A' || $mth == 'A')) {
                                    echo $mth_o . $mth;
                                } else {
                                    echo $mth_o + $mth;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$sce != null && @$sce != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Science </td>
                            <td style="text-align:left"> <?php echo $fullmarks_o ?> </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?></td>
                            <th style="text-align:left"> <?php echo $sce_o ?> </th>
                            <th style="text-align:left"> <?php echo $sce ?> </th>
                            <th style="text-align:left">
                                <?php if (($sce_o == null || $sce == null) || ($sce_o == 'A' || $sce == 'A')) {
                                    echo $sce_o . $sce;
                                } else {
                                    echo $sce_o + $sce;
                                } ?></th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$ssc != null && @$ssc != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Social Science </td>
                            <td style="text-align:left"> <?php echo $fullmarks_o ?> </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?></td>
                            <th style="text-align:left"> <?php echo $ssc_o ?> </th>
                            <th style="text-align:left"> <?php echo $ssc ?> </th>
                            <th style="text-align:left">
                                <?php if (($ssc_o == null || $ssc == null) || ($ssc_o == 'A' || $ssc == 'A')) {
                                    echo $ssc_o . $ssc;
                                } else {
                                    echo $ssc_o + $ssc;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$gka != null && @$gka != '-') { ?>
                        <tr>
                            <td style="text-align:left">
                                <?php if ($class == 1) {
                                    echo "General knowledge";
                                } else {
                                    echo "Hamara Parivesh";
                                }
                                ?>
                            </td>
                            <td style="text-align:left"> <?php echo $fullmarks_o ?> </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?></td>
                            <th style="text-align:left"> <?php echo $gka_o ?> </th>
                            <th style="text-align:left"> <?php echo $gka ?> </th>
                            <th style="text-align:left">
                                <?php if (($gka_o == null || $gka == null) || ($gka_o == 'A' || $gka == 'A')) {
                                    echo $gka_o . $gka;
                                } else {
                                    echo $gka_o + $gka;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$com != null && @$com != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Computer </td>
                            <td style="text-align:left"> <?php echo $fullmarks_o ?> </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?></td>
                            <th style="text-align:left"> <?php echo $com_o ?> </th>
                            <th style="text-align:left"> <?php echo $com ?> </th>
                            <th style="text-align:left">
                                <?php if (($com_o == null || $com == null) || ($com_o == 'A' || $com == 'A')) {
                                    echo $com_o . $com;
                                } else {
                                    echo $com_o + $com;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$bio != null && @$bio != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Biology/Life science </td>
                            <td style="text-align:left"> <?php echo $fullmarks_o ?> </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?></td>
                            <th style="text-align:left"> <?php echo $bio_o ?> </th>
                            <th style="text-align:left"> <?php echo $bio ?> </th>
                            <th style="text-align:left">
                                <?php if (($bio_o == null || $bio == null) || ($bio_o == 'A' || $bio == 'A')) {
                                    echo $bio_o . $bio;
                                } else {
                                    echo $bio_o + $bio;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$phy != null && @$phy != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Physics/Physical science </td>
                            <td style="text-align:left"> <?php echo $fullmarks_o ?> </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?></td>
                            <th style="text-align:left"> <?php echo $phy_o ?> </th>
                            <th style="text-align:left"> <?php echo $phy ?> </th>
                            <th style="text-align:left">
                                <?php if (($phy_o == null || $phy == null) || ($phy_o == 'A' || $phy == 'A')) {
                                    echo $phy_o . $phy;
                                } else {
                                    echo $phy_o + $phy;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$chm != null && @$chm != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Chemistry </td>
                            <td style="text-align:left"> <?php echo $fullmarks_o ?> </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?></td>
                            <th style="text-align:left"> <?php echo $chm_o ?> </th>
                            <th style="text-align:left"> <?php echo $chm ?> </th>
                            <th style="text-align:left">
                                <?php if (($chm_o == null || $chm == null) || ($chm_o == 'A' || $chm == 'A')) {
                                    echo $chm_o . $chm;
                                } else {
                                    echo $chm_o + $chm;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$acc != null && @$acc != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Accountancy </td>
                            <td style="text-align:left"> <?php echo $fullmarks_o ?> </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?></td>
                            <th style="text-align:left"> <?php echo $acc_o ?> </th>
                            <th style="text-align:left"> <?php echo $acc ?> </th>
                            <th style="text-align:left">
                                <?php if (($acc_o == null || $acc == null) || ($acc_o == 'A' || $acc == 'A')) {
                                    echo $acc_o . $acc;
                                } else {
                                    echo $acc_o + $acc;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$hd != null && @$hd != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Arts & Crafts </td>
                            <td style="text-align:left"> <?php echo $fullmarks_o ?> </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?></td>
                            <th style="text-align:left"> <?php echo $hd_o ?> </th>
                            <th style="text-align:left"> <?php echo $hd ?> </th>
                            <th style="text-align:left">
                                <?php if (($hd_o == null || $hd == null) || ($hd_o == 'A' || $hd == 'A')) {
                                    echo $hd_o . $hd;
                                } else {
                                    echo $hd_o + $hd;
                                } ?>
                            </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$pt != null && @$pt != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Physical Fitness </td>
                            <td style="text-align:left"> <?php echo $fullmarks_o ?> </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?></td>
                            <th style="text-align:left"> <?php echo $pt_o ?> </th>
                            <th style="text-align:left"> <?php echo $pt ?> </th>
                            <th style="text-align:left">
                                <?php if (($pt_o == null || $pt == null) || ($pt_o == 'A' || $pt == 'A')) {
                                    echo $pt_o . $pt;
                                } else {
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
                <?php if (@$hnd != null && @$hnd != '-') { ?>
                    <tr>
                        <td style="text-align:left">* Language I - <?php echo $language1 ?></td>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <td style="text-align:left"></td>
                    </tr>
                <?php } ?>
            </table>
            <br>

            <table class="table" border="0" align="center" style="width: 50%;">
                <tbody>
                    <tr>
                        <td style="text-align:left"> Result </td>
                        <th style="text-align:left"><?php echo $result ?></th>
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
            <table class="table" border="0" align="left" style="width: 30%; margin-left:0%; margin-top:2;">
                <tbody>
                    <tr>
                        <td style="text-align:left">Signature of Class Teacher / Center In-charge<br><br>Date:</td>
                    </tr>
                </tbody>
            </table>

            <!-- <div class="footer no-display" style="width: 97%;">
                <p style="text-align: left;">Report card generated:&nbsp;<?php echo @date("d/m/Y g:i a", strtotime($date)) ?></p>
            </div> -->

        <?php
        } else if ($id == "" && $stid == "") {
        ?>
            Please select Filter value.
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