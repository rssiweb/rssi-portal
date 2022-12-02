<?php
session_start();
include("../util/login_util.php");
include("../rssi-student/database.php");

date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');
@$id = $_GET['get_id'];
@$stid = $_GET['get_stid'];
$view_users_query = "select * from new_result WHERE studentid='$stid' AND examname='$id'"; //select query for viewing users.
$view_users_queryy = "select * from rssimyprofile_student WHERE student_id='$stid'";
$run = pg_query($con, $view_users_query); //here run the sql query.
$runn = pg_query($con, $view_users_queryy);

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
    $name = $row[0];
    $studentid = $row[1];
    $category = $row[2];
    $class = $row[3];
    $dob = $row[4];
    $hnd = $row[5];
    $eng = $row[6];
    $mth = $row[7];
    $sce = $row[8];
    $gka = $row[9];
    $ssc = $row[10];
    $phy = $row[11];
    $chm = $row[12];
    $bio = $row[13];
    $com = $row[14];
    $hd = $row[15];
    $acc = $row[16];
    $pt = $row[17];
    $total = $row[18];
    $mm = $row[19];
    $op = $row[20];
    $grade = $row[21];
    $result = $row[22];
    $position = $row[23];
    $attd = $row[24];
    $examname = $row[25];
    $fullmarks = $row[26];
    $month = $row[27];
    $language1 = $row[28];
?>
<?php } ?>
<?php

while ($roww = pg_fetch_array($runn)) //while look to fetch the result and store in a array $row.  
{
    $student_id = $roww[1];
    $studentname = $roww[3];
    $photourl = $roww[24];

?>
<?php } ?>


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
                    <option value="QT2/2022" <?php if (isset($_GET['get_id']) and $_GET['get_id'] == 'QT2/2022') {
                                                    echo ('selected="QT2/2022"');
                                                } ?>>QT2/2022</option>
                    <option value="QT1/2022" <?php if (isset($_GET['get_id']) and $_GET['get_id'] == 'QT1/2022') {
                                                    echo ('selected="QT1/2022"');
                                                } ?>>QT1/2022</option>
                    <option value="QT3/2022" <?php if (isset($_GET['get_id']) and $_GET['get_id'] == 'QT3/2022') {
                                                    echo ('selected="QT3/2022"');
                                                } ?>>QT3/2022</option>
                    <option value="QT2/2021" <?php if (isset($_GET['get_id']) and $_GET['get_id'] == 'QT2/2021') {
                                                    echo ('selected="QT2/2021"');
                                                } ?>>QT2/2021</option>
                    <option value="QT1/2021" <?php if (isset($_GET['get_id']) and $_GET['get_id'] == 'QT1/2021') {
                                                    echo ('selected="QT1/2021"');
                                                } ?>>QT1/2021</option>
                </select>
                <div class="col topbutton" style="display: inline-block;">
                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                        <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                    <button type="button" onclick="window.print()" name="print" class="btn btn-info btn-sm" style="outline: none;">
                        <i class="fa-regular fa-floppy-disk"></i>&nbsp;Save</button>
                </div>
            </form><br>
        </div>


        <?php if (@$examname > 0) {
        ?>
            <table class="table" border="0">
                <thead class="no-display">
                    <tr>
                        <td colspan=4>
                            <div class="row">
                                <div class="col" style="display: inline-block; width:65%;">

                                    <p><b>Rina Shiksha Sahayak Foundation (RSSI)</b></p>
                                    <p style="font-size: small;">624B/195/01, Vijayipur, Vijaipur Village, Vishesh Khand 2, Gomti Nagar, Lucknow, Uttar Pradesh 226010</p>
                                    <p style="font-size: small;">CIN— U80101WB2020NPL237900</p>
                                </div>
                                <div class="col" style="display: inline-block; width:32%; vertical-align: top;">
                                    Scan QR code to check authenticity
                                    <?php $url = "https://login.rssi.in/util/result.php?get_stid=$stid&get_id=$id";
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
                        <th><?php echo $examname ?></th>
                    </tr>
                    <tr>
                        <td> Date Of Birth </td>
                        <th><?php echo $dob ?></th>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table><br>

            <table class="table" border="0" align="center" style="width: 80%;">
                <tbody>
                    <tr bgcolor="#428BCA" style="color: #fff;">
                        <th style="text-align:left">Subject</th>
                        <th style="text-align:left"> Full Marks </th>
                        <th style="text-align:left"> Marks Obtained </th>
                        <th style="text-align:left"> Positional grade </th>
                    </tr>
                    <?php if (@$hnd != null && @$hnd != "-") { ?>
                        <tr>
                            <td style="text-align:left"> Language I </td>
                            <td style="text-align:left"><?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $hnd ?> </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$eng != null && @$eng != '-') { ?>
                        <tr>
                            <td style="text-align:left"> English </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $eng ?> </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$mth != null && @$mth != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Mathematics </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?></td>
                            <th style="text-align:left"> <?php echo $mth ?> </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$sce != null && @$sce != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Science </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $sce ?> </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$ssc != null && @$ssc != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Social Science </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $ssc ?> </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$gka != null && @$gka != '-') { ?>
                        <tr>
                            <td style="text-align:left"> General Knowledge </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $gka ?> </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$com != null && @$com != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Computer </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $com ?> </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$bio != null && @$bio != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Biology/Life science </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $bio ?> </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$phy != null && @$phy != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Physics/Physical science </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $phy ?> </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$chm != null && @$chm != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Chemistry </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $chm ?> </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$acc != null && @$acc != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Accountancy </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $acc ?> </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$hd != null && @$hd != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Arts & Crafts </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $hd ?> </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <?php if (@$pt != null && @$pt != '-') { ?>
                        <tr>
                            <td style="text-align:left"> Physical Fitness </td>
                            <td style="text-align:left"> <?php echo $fullmarks ?> </td>
                            <th style="text-align:left"> <?php echo $pt ?> </th>
                            <td style="text-align:left"></td>
                        </tr>
                    <?php } else {
                    } ?>
                    <tr bgcolor="#428BCA" style="color: #fff;">
                        <th style="text-align:left"></th>
                        <th style="text-align:left"> <?php echo $mm ?> </th>
                        <th style="text-align:left"> <?php echo $total ?> (<?php echo $op ?>%) </th>
                        <th style="text-align:left"> <?php echo $grade ?> </th>
                    </tr>
                    <?php if (@$hnd != null && @$hnd != '-') { ?>
                        <tr>
                            <td style="text-align:left" colspan="4">* Language I - <?php echo $language1 ?></td>
                        </tr>
                    <?php } else { ?>
                        <tr>
                            <td style="text-align:left" colspan="4"></td>
                        </tr>
                    <?php } ?>

                </tbody>
            </table>
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
            <table class="table" border="0" align="left" style="width: 30%; margin-left:10%; margin-top:5%;">
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
            No record found for <?php echo $stid ?>&nbsp;<?php echo $id ?>

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