<?php
session_start();
include("../util/login_util.php");

if (!isLoggedIn("sid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

date_default_timezone_set('Asia/Kolkata');
$date = date('Y-m-d H:i:s');
@$id = $_POST['get_id'];
$view_users_query = "select * from new_result WHERE studentid='$user_check' AND examname='$id'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

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
} ?>


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
            /*line-height: 1.42857143;*/
            color: #444;
        }
    </style>

    <!------ Include the above in your HEAD tag ---------->
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
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

        /*---------------------------------------
    CLASS NAME- NOTICET : URL DECORATION for table              
-----------------------------------------*/

        .noticet a:link {
            text-decoration: none !important;
            color: #F2545F !important;
            font-size: 14px;
        }

        .noticet a:visited {
            text-decoration: none !important;
            color: #F2545F !important;
            font-size: 14px;
        }

        .noticet a:hover {
            text-decoration: underline !important;
            color: #F2545F !important;
            font-size: 14px;
        }

        .noticet a:active {
            text-decoration: underline !important;
            color: #F2545F !important;
            font-size: 14px;
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
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col noprint" style="display: inline-block; width:100%;margin-left:10%; margin-top:2%">

                        <form action="" method="POST" id="formid">
                            <div class="form-group" style="display: unset;">
                                <div class="col2" style="display: inline-block;">
                                    <select name="get_id" class="form-control" style="width:max-content;" required>
                                        <?php if ($id == null) { ?>
                                            <option value="" disabled selected hidden>Select Exam name</option>
                                        <?php
                                        } else { ?>
                                            <option hidden selected><?php echo $id ?></option>
                                        <?php }
                                        ?>
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
                                </div>
                            </div>
                            <div class="col2 left noprint" style="display: inline;">
                                <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                                <button type="button" onclick="window.print()" name="print" class="btn btn-success btn-sm" style="outline: none;">
                                    <i class="fa-regular fa-floppy-disk"></i>&nbsp;Save</button>
                            </div>
                        </form>
                    </div>
                </div>
                <section class="box" style="padding: 2%;">
                    <b>
                        <h5 style="font-size: 20px; text-align:center">Rina Shiksha Sahayak Foundation (RSSI)
                    </b></h5>
                    <h5 style="text-align:center; line-height:2">1074/801, Jhapetapur, Backside of Municipality, West Midnapore, West Bengal 721301<br>
                        Registration Number - 237900</h5>
                    <hr>
                    <b>
                        <h4 style="font-size: 20px; text-align:center"> Report Card
                    </b></h4><br>
                    <table class="table" border="0" align="center" style="width: 80%;">
                        <?php if (@$examname > 0) {
                        ?>
                            <tbody>
                                <tr>
                                    <td style="text-align:left"> Registration Number </td>
                                    <th style="text-align:left"><?php echo $studentid ?></th>
                                    <td style="text-align:left"> Learning Group/Class </td>
                                    <th style="text-align:left"><?php echo $category ?>/<?php echo $class ?></th>
                                </tr>
                                <tr>
                                    <td style="text-align:left"> Name </td>
                                    <th style="text-align:left"><?php echo $studentname ?></th>
                                    <td style="text-align:left">Name of the examination</td>
                                    <th style="text-align:left"><?php echo $examname ?></th>
                                </tr>
                                <tr>
                                    <td style="text-align:left"> Date Of Birth </td>
                                    <th style="text-align:left"><?php echo $dob ?></th>
                                    <td style="text-align:left"></td>
                                    <th style="text-align:left"><img src=<?php echo $photourl ?> width=50px /></th>
                                </tr>
                            </tbody>
                    </table>

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
                                    <td style="text-align:left">* Language I - <?php echo $language1 ?></td>
                                </tr>
                            <?php } else { ?>
                                <tr>
                                    <td style="text-align:left"></td>
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

                    <div class="footer no-display">
                        <p style="text-align:right;">Admission form generated:&nbsp;<?php echo $date ?></p>
                    </div>
                <?php
                        } else if ($id == "") {
                ?>
                    <tr>
                        <td>Please select Exam name.</td>
                    </tr>
                <?php
                        } else {
                ?>
                    <tr>
                        <td>No record found for <?php echo $id ?></td>
                    </tr>
                <?php }
                ?>
            </div>
        </section>
    </section>
</body>

</html>