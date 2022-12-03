<?php
session_start();
// Storing Session
include("../util/login_util.php");

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

if (date('m') <= 4) { //Upto June 2014-2015
    $academic_year = (date('Y') - 1) . '-' . date('Y');
} else { //After June 2015-2016
    $academic_year = date('Y') . '-' . (date('Y') + 1);
}

@$now = date('Y-m-d H:i:s');
@$year = $academic_year;

if (@$_POST['form-type'] == "leaveapply") {
    @$leaveid = 'RSL' . time();
    @$applicantid = $associatenumber;
    @$fromdate = $_POST['fromdate'];
    @$todate = $_POST['todate'];
    @$typeofleave = $_POST['typeofleave'];
    @$creason = $_POST['creason'];
    @$comment = $_POST['comment'];
    @$appliedby = $_POST['appliedby'];
    @$email = $email;

    if ($leaveid != "") {
        $leave = "INSERT INTO leavedb_leavedb (timestamp,leaveid,applicantid,fromdate,todate,typeofleave,creason,comment,appliedby,lyear) VALUES ('$now','$leaveid','$applicantid','$fromdate','$todate','$typeofleave','$creason','$comment','$appliedby','$year')";
        $result = pg_query($con, $leave);
        $cmdtuples = pg_affected_rows($result);
    }
}

@$id = $_POST['get_id'];
@$status = $_POST['get_status'];
date_default_timezone_set('Asia/Kolkata');

if (($id == null && $status == null) || (($status > 0 && $status != 'ALL') && ($id > 0 && $id != 'ALL'))) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE applicantid='$user_check' AND status='$status' AND lyear='$id' order by timestamp desc");
} else if (($id == 'ALL' && $status == null) || ($id == null && $status == 'ALL')) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE applicantid='$user_check' order by timestamp desc");
} else if (($id > 0 && $id != 'ALL') && ($status == null)) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE applicantid='$user_check' AND lyear='$id' order by timestamp desc");
} else if (($id > 0 && $id != 'ALL') && ($status == 'ALL')) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE associatenumber='$user_check' AND lyear='$id' order by timestamp desc");
} else if (($status > 0 && $status != 'ALL') && ($id == null)) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE applicantid='$user_check' AND status='$status' order by timestamp desc");
} else if (($status > 0 && $status != 'ALL') && ($id == 'ALL')) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE applicantid='$user_check' AND status='$status' order by timestamp desc");
} else {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE applicantid='$user_check' order by timestamp desc");
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>My Leave</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>.checkbox {
            padding: 0;
            margin: 0;
            vertical-align: bottom;
            position: relative;
            top: 0px;
            overflow: hidden;
        }

        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            font-size: x-small;
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
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
            policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
        });
    </script>

</head>

<body>
    <?php $leave_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col" style="display: inline-block; width:99%; text-align:right">
                        Academic year: 2022-2023
                        <!--<br>Opening balance is the balance carried forward from previous credit cycle and refers to the leave till the allocation end date.-->
                    </div>
                    <?php if (@$leaveid != null && @$cmdtuples == 0) { ?>

                        <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                        </div>
                    <?php
                    } else if (@$cmdtuples == 1) { ?>

                        <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Your request has been submitted. Leave id <?php echo $leaveid ?>.</span>
                        </div>
                        <script>
                            if (window.history.replaceState) {
                                window.history.replaceState(null, null, window.location.href);
                            }
                        </script>
                    <?php } ?>
                    <?php
                    if ((@$clbal == 0 || @$clbal < 0) && ($slbal == 0 || $slbal < 0) && $filterstatus == 'Active') {
                    ?>
                        <div class="alert alert-danger" role="alert" style="text-align: -webkit-center;"><span class="blink_me"><i class="fas fa-exclamation-triangle" style="color: #A9444C;"></i></span>&nbsp;
                            <b><span id="demo" style="display: inline-block;"></span></b>&nbsp; Inadequate SL and CL balance. You are not eligible to take leave. Please take a makeup class to enable the apply leave option.
                        </div>
                    <?php
                    } else if ((@$clbal == 0 || @$clbal < 0) && $filterstatus == 'Active') {
                    ?>
                        <div class="alert alert-warning" role="alert" style="text-align: -webkit-center;"><span class="blink_me"><i class="fas fa-exclamation-triangle" style="color: #A9444C;"></i></span>&nbsp;
                            <b><span id="demo" style="display: inline-block;"></span></b>&nbsp; Insufficient CL balance. You are not eligible for casual leave. Please take makeup class to increase CL balance.
                        </div>
                    <?php
                    } else if ((@$slbal == 0 || @$slbal < 0) && $filterstatus == 'Active') {
                    ?>
                        <div class="alert alert-warning" role="alert" style="text-align: -webkit-center;"><span class="blink_me"><i class="fas fa-exclamation-triangle" style="color: #A9444C;"></i></span>&nbsp;
                            <b><span id="demo" style="display: inline-block;"></span></b>&nbsp; Insufficient SL balance. You are not eligible for sick leave. Please take makeup class to increase SL balance.
                        </div>
                    <?php
                    } else {
                    }
                    ?>
                </div>

                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Leave Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <!-- <td style="line-height: 2;">Sick Leave - <?php echo (int)$sl ?><br>Casual Leave - <?php echo (int)$cl ?></td> -->
                                <td style="line-height: 2;">Sick Leave - <?php echo $slbal ?>
                                    <br>Casual Leave - <?php echo $clbal ?>
                                    <!--<br>Other Leave - <?php echo $elbal ?></td>-->
                            </tr>
                        </tbody>
                    </table>



                    <form autocomplete="off" name="leaveapply" id="leaveapply" action="leave.php" method="POST">
                        <div class="form-group" style="display: inline-block;">

                            <input type="hidden" name="form-type" type="text" value="leaveapply">

                            <span class="input-help">
                                <input type="date" class="form-control" name="fromdate" id="fromdate" type="text" value="">
                                <small id="passwordHelpBlock" class="form-text text-muted">From</small>
                            </span>
                            <span class="input-help">
                                <input type="date" class="form-control" name="todate" id="todate" type="text" value="">
                                <small id="passwordHelpBlock" class="form-text text-muted">To</small>
                            </span>
                            <span class="input-help">
                                <select name="typeofleave" id="typeofleave" class="form-control" style="display: -webkit-inline-box; width:20vh; font-size: small;" required>
                                    <option value="" disabled selected hidden>Types of Leave</option>
                                    <option value="Sick Leave">Sick Leave</option>
                                    <option value="Casual Leave">Casual Leave</option>
                                </select>
                                <small id="passwordHelpBlock" class="form-text text-muted">Types of Leave</small>
                            </span>
                            <span class="input-help">
                                <select name="creason" id='creason' class="form-control">
                                    <option>--Select--</option>
                                </select>
                                <small id="passwordHelpBlock" class="form-text text-muted">Leave Category*</small>
                            </span>

                            <span class="input-help">
                                <textarea type="text" name="comment" class="form-control" placeholder="Remarks" value=""></textarea>
                                <small id="passwordHelpBlock" class="form-text text-muted">Remarks</small>
                            </span>

                            <input type="hidden" name="appliedby" class="form-control" placeholder="Applied by" value="<?php echo $associatenumber ?>" required readonly>

                            <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">Apply</button>

                        </div>

                    </form>

                    <script>
                        function getType() {
                            var x = document.getElementById("typeofleave").value;
                            var items;
                            if (x === "Sick Leave") {
                                items = ["Abdominal/Pelvic pain",
                                    "Anemia",
                                    "Appendicitis / Pancreatitis",
                                    "Asthma / bronchitis / pneumonia",
                                    "Burns",
                                    "Cancer -Carcinoma/ Malignant neoplasm",
                                    "Cardiac related ailments or Heart Disease",
                                    "Chest Pain",
                                    "Convulsions/ Epilepsy",
                                    "Dental Related Ailments - Tooth Ache / Impacted Tooth",
                                    "Emotional Well Being",
                                    "Digestive System Disorders/Indigestion/Food Poisoning/Diarrhea/Dysentry/Gastritis & Enteritis",
                                    "Excessive vomiting in pregnancy/Pregnancy induced hypertension",
                                    "Eye Related Ailments -Low Vision/Blindness/Eye Infections",
                                    "Fever/Cough/Cold",
                                    "Fracture/Injury/Dislocation/Sprain/Strain of joints/Ligaments of knee/Internal derangement/Other Orthopedic related ailments",
                                    "Gynecological Ailments/Disorders -Endometriosis/Fibroids",
                                    "Haemorrhoids (Piles)/Fissure/Fistula",
                                    "Headache/Nausea/Vomiting",
                                    "Hernia - Inguinal / Umbilical / Ventral",
                                    "Hepatitis",
                                    "Liver Related Ailments",
                                    "Maternity-Normal Delivery/Caesarean Section/Abortion",
                                    "Nervous Disorders",
                                    "Quarantine Leave",
                                    "Respiratory Related Ailments-Sinusitis/Tonsillitis,/Chronic rhinitis/Nasopharyngitis and pharyngitis/Congenital malformations of nose bronchitis",
                                    "Skin Related Ailments-Abscess/Swelling",
                                    "Spondilitis/ Intervertebral Disc Disorders / Spondylosis",
                                    "Urinary Tract Infections/Disorders",
                                    "Varicose veins of other sites",
                                ];
                            } else if (x === "Casual Leave") {
                                items = ["Other", "Timesheet leave"]
                            } else {
                                items = ["--Select--"]
                            }
                            var str = ""
                            for (var item of items) {
                                str += "<option>" + item + "</option>"
                            }
                            document.getElementById("creason").innerHTML = str;
                        }
                        document.getElementById("typeofleave").addEventListener("click", getType)
                    </script>







                    <b><span class="underline">Leave Details</span></b><br><br>
                    <form action="" method="POST">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <select name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type">
                                    <?php if ($id == null) { ?>
                                        <option value="" disabled selected hidden>Select Year</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $id ?></option>
                                    <?php }
                                    ?>
                                    <option>2022-2023</option>
                                    <option>2021-2022</option>
                                    <option>ALL</option>
                                </select>&nbsp;
                                <select name="get_status" class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type">
                                    <?php if ($status == null) { ?>
                                        <option value="" disabled selected hidden>Select Status</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $status ?></option>
                                    <?php }
                                    ?>
                                    <option>Approved</option>
                                    <option>Rejected</option>
                                    <option>ALL</option>
                                </select>
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                        </div>
                    </form>
                    <div class="col" style="display: inline-block; width:99%; text-align:right">
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                    </div>

                    <?php echo '
                       <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Leave ID</th>
                                <th scope="col">Applied on</th>
                                <th scope="col">From-To</th>
                                <th scope="col">Day(s) count</th>
                                <th scope="col">Type of Leave</th>
                                <th scope="col">Certificate(s)</th>
                                <th scope="col">Status</th>
                                <th scope="col">HR remarks</th>
                            </tr>
                        </thead>' ?>
                    <?php if (sizeof($resultArr) > 0) { ?>
                        <?php
                        echo '<tbody>';
                        foreach ($resultArr as $array) {
                            echo '<tr>
                                <td>' . $array['leaveid'] . '</td>
                                <td>' . @date("d/m/Y g:i a", strtotime($array['timestamp'])) . '</td>
                                <td>' .  @date("d/m/Y", strtotime($array['fromdate'])) . '—' .  @date("d/m/Y", strtotime($array['todate'])) . '</td>
                                <td>' . round((strtotime($array['todate']) - strtotime($array['fromdate'])) / (60 * 60 * 24) + 1) . '</td>
                                <td>' . $array['typeofleave'] . '<br>
                                ' . $array['creason'] . '</td>
                                <td>' . $array['doc'] . '</td>
                                <td>' . $array['status'] . '</td>
                                <td>' . $array['comment'] . '</td>
                            </tr>';
                        } ?>
                    <?php
                    } else if ($id == null && $status == null) {
                    ?>
                        <tr>
                            <td colspan="5">Please select Filter value.</td>
                        </tr>
                    <?php
                    } else {
                    ?>
                        <tr>
                            <td colspan="5">No record was found for the selected filter value.</td>
                        </tr>
                    <?php }

                    echo '</tbody>
                                    </table>';
                    ?>
                </section>
            </div>
        </section>
    </section>
</body>

</html>