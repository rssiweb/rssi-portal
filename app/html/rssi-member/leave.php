<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

if (date('m') == 1 || date('m') == 2 || date('m') == 3) { //Upto March
    $academic_year = (date('Y') - 1) . '-' . date('Y');
} else { //After MARCH
    $academic_year = date('Y') . '-' . (date('Y') + 1);
}

@$now = date('Y-m-d H:i:s');
@$currentAcademicYear = $academic_year;

@$lyear = $_POST['adj_academicyear'] ?? $currentAcademicYear;

$totalsl = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$associatenumber' AND typeofleave='Sick Leave' AND lyear='$lyear' AND (status='Approved' OR status is null)");
$totalcl = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$associatenumber' AND typeofleave='Casual Leave' AND lyear='$lyear' AND (status='Approved' OR status is null)");
$cladj = pg_query($con, "SELECT COALESCE(SUM(adj_day),0) FROM leaveadjustment WHERE adj_applicantid='$associatenumber' AND adj_leavetype='Casual Leave' AND adj_academicyear='$lyear'");
$sladj = pg_query($con, "SELECT COALESCE(SUM(adj_day),0) FROM leaveadjustment WHERE adj_applicantid='$associatenumber'AND adj_leavetype='Sick Leave' AND adj_academicyear='$lyear'");

$allocl = pg_query($con, "SELECT COALESCE(SUM(allo_daycount),0) FROM leaveallocation WHERE allo_applicantid='$associatenumber' AND allo_leavetype='Casual Leave' AND allo_academicyear='$lyear'");
$allosl = pg_query($con, "SELECT COALESCE(SUM(allo_daycount),0) FROM leaveallocation WHERE allo_applicantid='$associatenumber' AND allo_leavetype='Sick Leave' AND allo_academicyear='$lyear'");

$resultArrsl = pg_fetch_result($totalsl, 0, 0);
$resultArrcl = pg_fetch_result($totalcl, 0, 0);
@$resultArr_cladj = pg_fetch_result($cladj, 0, 0);
@$resultArr_sladj = pg_fetch_result($sladj, 0, 0);
@$resultArrrcl = pg_fetch_result($allocl, 0, 0);
@$resultArrrsl = pg_fetch_result($allosl, 0, 0);

@$slbalance = ($resultArrrsl + $resultArr_sladj) - $resultArrsl;
@$clbalance = ($resultArrrcl + $resultArr_cladj) - $resultArrcl;

if (@$_POST['form-type'] == "leaveapply") {
    @$leaveid = 'RSL' . time();
    @$applicantid = $associatenumber;
    @$fromdate = $_POST['fromdate'];
    @$todate = $_POST['todate'];
    //echo json_encode($_FILES);
    @$uploadedFile = $_FILES['medicalcertificate'];
    @$typeofleave = $_POST['typeofleave'];
    @$creason = $_POST['creason'];
    @$comment = $_POST['comment'];
    @$appliedby = $_POST['appliedby'];
    @$shift = $_POST['shift'];
    @$applicantcomment = htmlspecialchars($_POST['applicantcomment'], ENT_QUOTES, 'UTF-8');
    @$ack = $_POST['ack'] ?? 0;
    @$halfday = $_POST['is_userh'] ?? 0;
    @$email = $email;

    // send $file to google =======> google (rssi.in) // robotic service account credential.json

    if ($leaveid != "") {
        if ($halfday == 1) {
            @$day = round((strtotime($_POST['todate']) - strtotime($_POST['fromdate'])) / (60 * 60 * 24) + 1) / 2;
        } else {
            @$day = round((strtotime($_POST['todate']) - strtotime($_POST['fromdate'])) / (60 * 60 * 24) + 1);
        }
    }
    if (($slbalance >= $day && $typeofleave == "Sick Leave") || ($clbalance >= $day && $typeofleave == "Casual Leave")) {

        // send uploaded file to drive
        // get the drive link
        if (empty($_FILES['medicalcertificate']['name'])) {
            $doclink = null;
        } else {
            $filename = "doc_" . $leaveid . "_" . $applicantid . "_" . time();
            $parent = '1zbevlcQJg2sZcldp23ix1uGqy5cy5Un-Sy8x8cwz0L15GRhSSdFy0k7HjMjraVwefgB6TfL0';
            $doclink = uploadeToDrive($uploadedFile, $parent, $filename);
        }
        $leave = "INSERT INTO leavedb_leavedb (timestamp,leaveid,applicantid,fromdate,todate,typeofleave,creason,comment,appliedby,lyear,applicantcomment,days,halfday,doc,ack,shift) VALUES ('$now','$leaveid','$applicantid','$fromdate','$todate','$typeofleave','$creason','$comment','$appliedby','$currentAcademicYear','$applicantcomment','$day',$halfday,'$doclink','$ack','$shift')";

        $result = pg_query($con, $leave);
        $cmdtuples = pg_affected_rows($result);

        if ($typeofleave == "Sick Leave") {
            @$slbalance = $slbalance - $day;
        } else if ($typeofleave == "Casual Leave") {
            @$clbalance = $clbalance - $day;
        }
    }
    if ($typeofleave == "Leave Without Pay") {

        // send uploaded file to drive
        // get the drive link
        if (empty($_FILES['medicalcertificate']['name'])) {
            $doclink = null;
        } else {
            $filename = "doc_" . $leaveid . "_" . $applicantid . "_" . time();
            $parent = '1zbevlcQJg2sZcldp23ix1uGqy5cy5Un-Sy8x8cwz0L15GRhSSdFy0k7HjMjraVwefgB6TfL0';
            $doclink = uploadeToDrive($uploadedFile, $parent, $filename);
        }
        $leave = "INSERT INTO leavedb_leavedb (timestamp,leaveid,applicantid,fromdate,todate,typeofleave,creason,comment,appliedby,lyear,applicantcomment,days,halfday,doc,ack,shift) VALUES ('$now','$leaveid','$applicantid','$fromdate','$todate','$typeofleave','$creason','$comment','$appliedby','$currentAcademicYear','$applicantcomment','$day',$halfday,'$doclink','$ack','$shift')";

        $result = pg_query($con, $leave);
        $cmdtuples = pg_affected_rows($result);

        if ($typeofleave == "Sick Leave") {
            @$slbalance = $slbalance - $day;
        } else if ($typeofleave == "Casual Leave") {
            @$clbalance = $clbalance - $day;
        }
    }
    $emaildaycount = "undefined";
    if (@$cmdtuples == 1 && $halfday != 1) {

        $emaildaycount = round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1);
    }
    if (@$cmdtuples == 1 && $halfday == 1) {

        $emaildaycount = round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1) / 2;
    }

    if (@$cmdtuples == 1 && $email != "") {
        sendEmail("leaveapply", array(
            "leaveid" => $leaveid,
            "applicantid" => $applicantid,
            "applicantname" => @$fullname,
            "fromdate" => @date("d/m/Y", strtotime($fromdate)),
            "todate" => @date("d/m/Y", strtotime($todate)),
            "typeofleave" => $typeofleave,
            "category" => $creason,
            "day" => $emaildaycount,
            "now" => @date("d/m/Y g:i a", strtotime($now))
        ), $email);
    }
}


@$status = $_POST['get_status'];

if (($lyear > 0 && $lyear != 'ALL') && ($status == null || $status == 'ALL')) {
    $result = pg_query($con, "select *, REPLACE (doc, 'view', 'preview') docp from leavedb_leavedb WHERE applicantid='$associatenumber' AND lyear='$lyear' order by timestamp desc");
} else if (($status > 0 && $status != 'ALL') && ($lyear == null || $lyear == 'ALL')) {
    $result = pg_query($con, "select *, REPLACE (doc, 'view', 'preview') docp from leavedb_leavedb WHERE applicantid='$associatenumber' AND status='$status' order by timestamp desc");
} else if (($status > 0 && $status != 'ALL') && ($lyear > 0 || $lyear != 'ALL')) {
    $result = pg_query($con, "select *, REPLACE (doc, 'view', 'preview') docp from leavedb_leavedb WHERE applicantid='$associatenumber' AND status='$status' AND lyear='$lyear' order by timestamp desc");
} else {
    $result = pg_query($con, "select *, REPLACE (doc, 'view', 'preview') docp from leavedb_leavedb WHERE applicantid='$associatenumber' order by timestamp desc");
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Apply for Leave</title>

    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

    <style>
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }

        #hidden-panel,
        #hidden-panel_ack,
        #hidden-panel_creason {
            display: none;
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Apply for Leave</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">My Services</a></li>
                    <li class="breadcrumb-item active">Apply for Leave</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="row">
                                <?php if (@$leaveid != null && @$cmdtuples == 0 && @$typeofleave == "Sick Leave") { ?>
                                    <div class="alert alert-danger alert-dismissible text-center" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-x-lg"></i>
                                        <span>ERROR: Your SL request has not been submitted because you have applied for more than the leave balance.</span>
                                    </div>
                                <?php } else if (@$leaveid != null && @$cmdtuples == 0 && @$typeofleave == "Casual Leave") { ?>
                                    <div class="alert alert-danger alert-dismissible text-center" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-x-lg"></i>
                                        <span>ERROR: Your CL request has not been submitted because you have applied for more than the leave balance.</span>
                                    </div>
                                <?php } else if (@$cmdtuples == 1) { ?>
                                    <div class="alert alert-success alert-dismissible text-center" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-check2-circle"></i>
                                        <span>Your request has been submitted. Leave id <?php echo $leaveid ?>.</span>
                                    </div>
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                                <?php } ?>

                                <?php
                                if (($clbalance == 0 || @$clbalance < 0) && ($slbalance == 0 || $slbalance < 0) && $filterstatus == 'Active' && $lyear != null) {
                                ?>
                                    <div class="alert alert-danger text-center" role="alert">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>Inadequate SL and CL balance. You are not eligible to take leave.
                                            Please take a makeup class to enable the apply leave option.</span>
                                    </div>
                                <?php } else if ((@$clbalance == 0 || @$clbalance < 0) && $filterstatus == 'Active' && $lyear != null) { ?>
                                    <div class="alert alert-warning text-center" role="alert">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>Insufficient CL balance. You are not eligible for casual leave.
                                            Please take a makeup class to increase CL balance.</span>
                                    </div>
                                <?php } else if ((@$slbalance == 0 || @$slbalance < 0) && $filterstatus == 'Active' && $lyear != null) { ?>
                                    <div class="alert alert-warning text-center" role="alert">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>Insufficient SL balance. You are not eligible for sick leave.
                                            Please take a makeup class to increase SL balance.</span>
                                    </div>
                                <?php } ?>
                                <!-- Warning message for leave eligibility -->
                                <span id="leaveWarning" style="color: red;"></span>
                                <div class="text-end">
                                    <span class="link-secondary"><a href="leaveadjustment.php?adj_academicyear_search=<?php echo $lyear ?>" target="_blank" title="Check Adjusted Leave Record">Leave Adjustment</a></span>
                                    <span class="separator"> | </span>
                                    <span class="link-secondary"><a href="leaveallo.php?allo_academicyear_search=<?php echo $lyear ?>" target="_blank" title="Check allotted leave record">Leave Allocation</a></span>
                                </div>


                                <form autocomplete="off" name="academicyear" id="academicyear" action="leave.php" method="POST">
                                    <div class="text-start">
                                        Academic year:
                                        <select name="adj_academicyear" id="adj_academicyear" onchange="this.form.submit()" class="form-select" style="width: 20vh;" required>
                                            <?php if ($lyear != null) { ?>
                                                <option hidden selected><?php echo $lyear ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </form>

                            </div><br>

                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">Apply Leave</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>

                                        <td style="line-height: 2;">
                                            Sick Leave - <?php echo $slbalance ?>
                                            <br>Casual Leave - <?php echo $clbalance ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <form autocomplete="off" name="leaveapply" id="leaveapply" action="leave.php" method="POST" enctype="multipart/form-data">
                                <fieldset <?php echo ($filterstatus != 'Active') ? 'disabled' : ''; ?>>
                                    <div class="form-group" style="display: inline-block;">

                                        <input type="hidden" name="form-type" value="leaveapply">

                                        <span class="input-help">
                                            <input type="date" class="form-control" name="fromdate" id="fromdate" max="" onchange="cal(); checkLeaveType();" required>
                                            <small id="passwordHelpBlock" class="form-text text-muted">From<span style="color:red">*</span></small>
                                        </span>
                                        <span class="input-help">
                                            <input type="date" class="form-control" name="todate" id="todate" min="" onchange="cal(); checkLeaveType();" required>
                                            <small id="passwordHelpBlock" class="form-text text-muted">To<span style="color:red">*</span></small>
                                        </span>
                                        <div id="filter-checksh">
                                            <input type="checkbox" name="is_userh" id="is_userh" value="1" onchange="cal(); toggleShiftField()" disabled />
                                            <label for="is_userh" style="font-weight: 400;">Half day</label>
                                        </div>
                                        <span class="input-help">
                                            <input type="text" class="form-control" name="numdays2" id="numdays2" placeholder="Day count" placeholder="Day count" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" size="10" readonly>
                                            <small id="passwordHelpBlock" class="form-text text-muted">Days count</small>
                                        </span>
                                        <?php if ($job_type != 'Full-time') { ?>
                                            <span id="shiftField" class="input-help" style="display: none;">
                                                <select name="shift" id="shift" class="form-select">
                                                    <option disabled selected hidden value="">Select</option>
                                                    <option value="MFH">Morning First Half</option>
                                                    <option value="MSH">Morning Second Half</option>
                                                    <option value="AFH">Afternoon First Half</option>
                                                    <option value="ASH">Afternoon Second Half</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Shift<span style="color:red">*</span></small>
                                            </span>
                                        <?php } else { ?>
                                            <span id="shiftField" class="input-help" style="display: none;">
                                                <select name="shift" id="shift" class="form-select">
                                                    <option disabled selected hidden value="">Select</option>
                                                    <option value="MOR">Morning</option>
                                                    <option value="AFN">Afternoon</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Shift<span style="color:red">*</span></small>
                                            </span>
                                        <?php } ?>
                                        <span class="input-help">
                                            <select name="typeofleave" id="typeofleave" class="typeofleave form-select" required>
                                                <option disabled selected hidden value="">Select</option>
                                                <option value="Sick Leave">Sick Leave</option>
                                                <option value="Casual Leave">Casual Leave</option>
                                                <option value="Leave Without Pay">Leave Without Pay</option>
                                                <!-- <option value="uk">United Kingdom</option> -->
                                            </select>
                                            <small id="passwordHelpBlock" class="form-text text-muted">Types of Leave<span style="color:red">*</span></small>
                                        </span>
                                        <span name="hidden-panel_creason" id="hidden-panel_creason">
                                            <span id="response"></span>
                                        </span>

                                        <span name="hidden-panel" id="hidden-panel">
                                            <span class="input-help">
                                                <input type="file" name="medicalcertificate" class="form-control" />
                                                <small id="passwordHelpBlock" class="form-text text-muted">Documents</small>
                                            </span>
                                        </span>

                                        <span class="input-help">
                                            <textarea type="text" name="applicantcomment" class="form-control" placeholder="Remarks" value=""></textarea>
                                            <small id="passwordHelpBlock" class="form-text text-muted">Remarks</small>
                                        </span>

                                        <input type="hidden" name="appliedby" class="form-control" placeholder="Applied by" value="<?php echo $associatenumber ?>" required readonly>

                                        <span name="hidden-panel_ack" id="hidden-panel_ack">
                                            <div id="filter-checksh">
                                                <input type="checkbox" name="ack" id="ack" value="1" />
                                                <label for="ack" style="font-weight: 400;"> I hereby confirm submitting the relevant supporting medical documents if the leave duration is more than 2 days.</label>
                                            </div>
                                        </span>
                                        <br>
                                        <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">Apply</button>

                                    </div>
                                </fieldset>
                            </form><br>

                            <script>
                                function checkLeaveType() {
                                    var fromdate = new Date(document.getElementById("fromdate").value);
                                    var todate = new Date(document.getElementById("todate").value);
                                    var today = new Date();

                                    // Reset typeofleave if fromdate or todate changes
                                    document.getElementById("typeofleave").value = ""; // Reset the selected value

                                    // Disable Casual Leave if fromdate or todate is today or in the past
                                    if (fromdate <= today || todate <= today) {
                                        document.getElementById("typeofleave").options[2].disabled = true; // Disable Casual Leave
                                        document.getElementById("leaveWarning").innerText = "You have selected current or past date. You are not eligible to apply for Casual Leave.";
                                    } else {
                                        document.getElementById("typeofleave").options[2].disabled = false; // Enable Casual Leave
                                        document.getElementById("leaveWarning").innerText = ""; // Clear warning message
                                    }
                                }
                            </script>

                            <script>
                                if (<?php echo $slbalance ?> <= 0) {
                                    document.getElementById("typeofleave").options[1].disabled = true;
                                } else {
                                    document.getElementById("typeofleave").options[1].disabled = false;
                                }

                                if (<?php echo $clbalance ?> <= 0) {
                                    document.getElementById("typeofleave").options[2].disabled = true;
                                } else {
                                    document.getElementById("typeofleave").options[2].disabled = false;
                                }
                            </script>
                            <script>
                                function toggleShiftField() {
                                    var isHalfDay = document.getElementById('is_userh').checked;
                                    var shiftField = document.getElementById('shiftField');
                                    var shiftSelect = document.getElementById('shift');

                                    if (isHalfDay && !document.getElementById('is_userh').disabled) {
                                        shiftField.style.display = 'inline-block';
                                        shiftSelect.setAttribute('required', 'required');
                                    } else {
                                        shiftField.style.display = 'none';
                                        shiftSelect.removeAttribute('required');
                                        shiftSelect.value = '';
                                    }
                                }

                                function cal() {
                                    if (document.getElementById("todate") || document.getElementById("fromdate")) {
                                        function GetDays() {
                                            var todate = new Date(document.getElementById("todate").value);
                                            var fromdate = new Date(document.getElementById("fromdate").value);
                                            var diffDays = (todate - fromdate) / (24 * 3600 * 1000) + 1;

                                            var todatecheck = document.forms["leaveapply"]["todate"].value;
                                            var fromdatecheck = document.forms["leaveapply"]["fromdate"].value;

                                            if ((todatecheck == null || fromdatecheck == null) || diffDays !== 1) {
                                                document.getElementById("is_userh").disabled = true;
                                                document.getElementById("is_userh").checked = false;
                                                toggleShiftField(); // Call to hide shift field if needed
                                            } else {
                                                document.getElementById("is_userh").disabled = false;
                                            }
                                            if ($('#is_userh').not(':checked').length > 0) {
                                                return (diffDays);

                                            } else if (event.target.checked) {
                                                return (diffDays / 2);
                                            }
                                            const checkbox = document.getElementById('is_userh');
                                            checkbox.addEventListener('change', (event) => {
                                                if (event.target.checked) {
                                                    return (diffDays / 2);
                                                } else if ($('#is_userh').not(':checked').length > 0) {
                                                    return (diffDays);
                                                }
                                            })
                                        }
                                        document.getElementById("numdays2").value = GetDays();

                                        document.getElementById("todate").min = document.getElementById("fromdate").value;
                                        document.getElementById("fromdate").max = document.getElementById("todate").value;
                                    }
                                }

                                //Showing document upload for sick leave only 
                                $(document).ready(function() {
                                    $("#typeofleave").change(function() {
                                        if ($("#typeofleave").val() == "Sick Leave") {
                                            $("#hidden-panel").show()
                                            $("#hidden-panel_ack").show()
                                            $("#hidden-panel_creason").show()
                                        } else if ($("#typeofleave").val() == "Casual Leave") {
                                            $("#hidden-panel").hide()
                                            $("#hidden-panel_ack").hide()
                                            $("#hidden-panel_creason").show()
                                        } else if ($("#typeofleave").val() == "Leave Without Pay") {
                                            $("#hidden-panel").hide()
                                            $("#hidden-panel_ack").hide()
                                            $("#hidden-panel_creason").hide()
                                        } else {
                                            $("#hidden-panel").hide()
                                            $("#hidden-panel_ack").hide()
                                            $("#hidden-panel_creason").hide()
                                        }
                                    })
                                });
                            </script>
                            <!--To make a filed (acknowledgement) required based on a dropdown value (sick leave)-->
                            <script>
                                if (document.getElementById('typeofleave').value == "Leave Without Pay" && document.getElementById('typeofleave').value == "Casual Leave") {

                                    document.getElementById("ack").required = false;
                                } else {

                                    document.getElementById("ack").required = true;
                                }

                                const randvar = document.getElementById('typeofleave');

                                randvar.addEventListener('change', (event) => {
                                    if (document.getElementById('typeofleave').value == "Sick Leave") {

                                        document.getElementById("ack").required = true;

                                    } else {

                                        document.getElementById("ack").required = false;
                                    }
                                })
                            </script>

                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">Leave Details</th>
                                    </tr>
                                </thead>
                            </table>
                            <form action="" method="POST">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">
                                        <select name="get_status" class="form-select" style="width:max-content; display:inline-block" placeholder="Appraisal type">
                                            <?php if ($status == null) { ?>
                                                <option disabled selected hidden>Select Status</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $status ?></option>
                                            <?php }
                                            ?>
                                            <option>Approved</option>
                                            <option>Rejected</option>
                                            <option>ALL</option>
                                        </select>

                                        <select name="adj_academicyear" id="adj_academicyear_A" class="form-select" style="display: -webkit-inline-box; width:20vh;" required>
                                            <?php if ($lyear != null) { ?>
                                                <option hidden selected><?php echo $lyear ?></option>
                                            <?php }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                                        <i class="bi bi-search"></i>&nbsp;Search</button>
                                </div>
                            </form>
                            <div class="col" style="display: inline-block; width:99%; text-align:right">
                                Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                            </div>

                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th scope="col">Leave ID</th>
                                            <th scope="col">Applied on</th>
                                            <th scope="col">From-To</th>
                                            <th scope="col">Day(s) count</th>
                                            <th scope="col">Type of Leave</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">HR remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (sizeof($resultArr) > 0) : ?>
                                            <?php foreach ($resultArr as $array) : ?>
                                                <tr>
                                                    <?php if ($array['doc'] != null) : ?>
                                                        <td>
                                                            <a href="javascript:void(0)" onclick="showpdf('<?php echo htmlspecialchars($array['leaveid'] ?? ''); ?>')">
                                                                <?php echo htmlspecialchars($array['leaveid'] ?? ''); ?>
                                                            </a>
                                                        </td>
                                                    <?php else : ?>
                                                        <td><?php echo htmlspecialchars($array['leaveid'] ?? ''); ?></td>
                                                    <?php endif; ?>

                                                    <td><?php echo date("d/m/Y g:i a", strtotime($array['timestamp'] ?? '')); ?></td>
                                                    <td><?php echo date("d/m/Y", strtotime($array['fromdate'] ?? '')) . ' — ' . date("d/m/Y", strtotime($array['todate'] ?? '')); ?></td>
                                                    <td><?php echo htmlspecialchars($array['days'] ?? ''); ?></td>
                                                    <td>
                                                        <?php
                                                        // Prepare type of leave with optional shift value
                                                        $typeofLeave = htmlspecialchars($array['typeofleave'] ?? '');
                                                        $shift = htmlspecialchars($array['shift'] ?? '');
                                                        echo $typeofLeave . ($shift ? '-' . $shift : '');
                                                        ?><br>
                                                        <?php echo htmlspecialchars($array['creason'] ?? ''); ?><br>

                                                        <?php
                                                        // Ensure the comment is a string and handle null values
                                                        $applicantComment = $array['applicantcomment'] ?? '';
                                                        // Shorten the applicant comment
                                                        $shortComment = strlen($applicantComment) > 30 ? substr($applicantComment, 0, 30) . "..." : $applicantComment;
                                                        ?>

                                                        <span class="short-comment"><?php echo htmlspecialchars($shortComment); ?></span>
                                                        <span class="full-comment" style="display: none;"><?php echo htmlspecialchars($applicantComment); ?></span>

                                                        <?php if (strlen($applicantComment) > 30) : ?>
                                                            <a href="#" class="more-link">more</a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($array['status'] ?? ''); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($array['comment'] ?? ''); ?><br>
                                                        <?php echo htmlspecialchars($array['reviewer_id'] ?? ''); ?><br>
                                                        <?php echo htmlspecialchars($array['reviewer_name'] ?? ''); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr>
                                                <td colspan="7">
                                                    <?php if ($lyear == null && $status == null) : ?>
                                                        Please select Filter value.
                                                    <?php else : ?>
                                                        No record was found for the selected filter value.
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!--------------- POP-UP BOX ------------
                            -------------------------------------->
                            <style>
                                .modal {
                                    background-color: rgba(0, 0, 0, 0.4);
                                    /* Black w/ opacity */
                                }
                            </style>
                            <div class="modal" id="myModalpdf" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Supporting documents</h1>
                                            <button type="button" id="closepdf-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div style="width:100%;">
                                                <span style="float: left;">Leave Id: <span class="leaveid"></span></span>
                                                <span style="float: right;">
                                                    <p id="status2" class="badge" style="display: inline !important;"><span class="status"></span></p>
                                                </span>
                                            </div>
                                            <object name="docid" id="" data="" type="application/pdf" width="100%" height="450px"></object>
                                            <div class="modal-footer">
                                                <button type="button" id="closepdf-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script>
                                var data1 = <?php echo json_encode($resultArr) ?>

                                // Get the modal
                                var modal1 = document.getElementById("myModalpdf");
                                var closepdf = [
                                    document.getElementById("closepdf-header"),
                                    document.getElementById("closepdf-footer")
                                ];

                                function showpdf(id1) {
                                    var mydata1 = undefined
                                    data1.forEach(item1 => {
                                        if (item1["leaveid"] == id1) {
                                            mydata1 = item1;
                                        }
                                    })
                                    var keys1 = Object.keys(mydata1)
                                    keys1.forEach(key => {
                                        var span1 = modal1.getElementsByClassName(key)
                                        if (span1.length > 0)
                                            span1[0].innerHTML = mydata1[key];
                                    })
                                    modal1.style.display = "block";

                                    //class add 
                                    var statuss = document.getElementById("status2")
                                    if (mydata1["status"] === "Approved") {
                                        statuss.classList.add("bg-success")
                                        statuss.classList.remove("bg-danger")
                                    } else if (mydata1["status"] === "Rejected") {
                                        statuss.classList.remove("bg-success")
                                        statuss.classList.add("bg-danger")
                                    } else {
                                        statuss.classList.remove("bg-success")
                                        statuss.classList.remove("bg-danger")
                                    }
                                    //class add end
                                    document.getElementsByName("docid")[0].id = "docid" + mydata1["leaveid"];

                                    randomvar = document.getElementById("docid" + mydata1["leaveid"])
                                    randomvar.data = mydata1["docp"]

                                    closepdf.forEach(function(element) {
                                        element.addEventListener("click", closeModel);
                                    });

                                    function closeModel() {
                                        var modal1 = document.getElementById("myModalpdf");
                                        modal1.style.display = "none";
                                    }
                                }
                            </script>

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
                                    $('#adj_academicyear').append(new Option(year, year));
                                    $('#adj_academicyear_A').append(new Option(year, year));
                                    currentYear--;
                                }
                            </script>

                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>
    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script>
        $(document).ready(function() {
            // Toggle full comment visibility on "more" link click
            $('.more-link').click(function(e) {
                e.preventDefault();
                var shortComment = $(this).siblings('.short-comment');
                var fullComment = $(this).siblings('.full-comment');
                if (fullComment.is(':visible')) {
                    // If full comment is visible, toggle to show short comment
                    shortComment.show();
                    fullComment.hide();
                    $(this).text('more');
                } else {
                    // If short comment is visible, toggle to show full comment
                    shortComment.hide();
                    fullComment.show();
                    $(this).text('less');
                }
            });
        });
    </script>
    <!--Here .typeofleave is a class and has been assigned to the input filed id=typeofleave-->
    <script type="text/javascript">
        $(document).ready(function() {
            $("select.typeofleave").change(function() {
                var selectedtypeofleave = $(".typeofleave option:selected").val();
                $.ajax({
                    type: "POST",
                    url: "process-request.php",
                    data: {
                        typeofleave: selectedtypeofleave
                    }
                }).done(function(data) {
                    $("#response").html(data);
                });
            });
        });
    </script>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>