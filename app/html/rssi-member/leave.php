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
        $leave = "INSERT INTO leavedb_leavedb (timestamp,leaveid,applicantid,fromdate,todate,typeofleave,creason,comment,appliedby,lyear,applicantcomment,days,halfday,doc,ack) VALUES ('$now','$leaveid','$applicantid','$fromdate','$todate','$typeofleave','$creason','$comment','$appliedby','$currentAcademicYear','$applicantcomment','$day',$halfday,'$doclink','$ack')";

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
        $leave = "INSERT INTO leavedb_leavedb (timestamp,leaveid,applicantid,fromdate,todate,typeofleave,creason,comment,appliedby,lyear,applicantcomment,days,halfday,doc,ack) VALUES ('$now','$leaveid','$applicantid','$fromdate','$todate','$typeofleave','$creason','$comment','$appliedby','$currentAcademicYear','$applicantcomment','$day',$halfday,'$doclink','$ack')";

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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Apply for Leave</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Template Main CSS File -->
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

    <script type="text/javascript" src="http://code.jquery.com/jquery.js"> </script>
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
                                            <input type="date" class="form-control" name="fromdate" id="fromdate" value="" max="" onchange="cal();" required>
                                            <small id="passwordHelpBlock" class="form-text text-muted">From<span style="color:red">*</span></small>
                                        </span>
                                        <span class="input-help">
                                            <input type="date" class="form-control" name="todate" id="todate" value="" min="" onchange="cal();" required>
                                            <small id="passwordHelpBlock" class="form-text text-muted">To<span style="color:red">*</span></small>
                                        </span>
                                        <div id="filter-checksh">
                                            <input type="checkbox" name="is_userh" id="is_userh" value="1" onchange="cal()" disabled />
                                            <label for="is_userh" style="font-weight: 400;">Half day</label>
                                        </div>
                                        <span class="input-help">
                                            <input type="text" class="form-control" name="numdays2" id="numdays2" value="" placeholder="Day count" placeholder="Day count" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" size="10" readonly>
                                            <small id="passwordHelpBlock" class="form-text text-muted">Days count</small>
                                        </span>

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

                            <?php echo '
                            <p>Select Number Of Rows</p>
                            <div class="form-group">
                           <select class="form-select" name="state" id="maxRows">
                               <option value="5000">Show ALL Rows</option>
                               <option value="5">5</option>
                               <option value="10">10</option>
                               <option value="15">15</option>
                               <option value="20">20</option>
                               <option value="50">50</option>
                               <option value="70">70</option>
                               <option value="100">100</option>
                           </select>
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
                            </thead>' ?>
                            <?php if (sizeof($resultArr) > 0) { ?>
                                <?php
                                echo '<tbody>';
                                foreach ($resultArr as $array) {
                                    echo '<tr>'
                                ?>

                                    <?php if ($array['doc'] != null) { ?>
                                        <?php
                                        echo '<td>
                                <span class="noticea"><a href="javascript:void(0)" onclick="showpdf(\'' . $array['leaveid'] . '\')">' . $array['leaveid'] . '</a></span>  
                                </td>'
                                        ?>
                                        <?php } else { ?><?php
                                                            echo '<td>' . $array['leaveid'] . '</td>' ?>
                                    <?php } ?>
                                <?php
                                    echo '
                                <td>' . @date("d/m/Y g:i a", strtotime($array['timestamp'])) . '</td>
                                <td>' . @date("d/m/Y", strtotime($array['fromdate'])) . '—' . @date("d/m/Y", strtotime($array['todate'])) . '</td>
                                <td>' . $array['days'] . '</td>
                                <td>' . $array['typeofleave'] . '<br>
                                ' . $array['creason'] . '<br>
                                ' . $array['applicantcomment'] . '</td>
                                <td>' . $array['status'] . '</td>
                                <td>' . $array['comment'] . '<br>' . $array['reviewer_id'] . '<br>' . $array['reviewer_name'] . '</td>
                            </tr>';
                                } ?>
                            <?php
                            } else if ($lyear == null && $status == null) {
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
                                    </table>
                                    </div>';
                            ?>
                            <!-- Start Pagination -->
                            <div class="pagination-container">
                                <nav>
                                    <ul class="pagination">
                                        <li class="page-item" data-page="prev">
                                            <button class="page-link pagination-button" aria-label="Previous">&lt;</button>
                                        </li>
                                        <!-- Here the JS Function Will Add the Rows -->
                                        <li class="page-item">
                                            <button class="page-link pagination-button">1</button>
                                        </li>
                                        <li class="page-item">
                                            <button class="page-link pagination-button">2</button>
                                        </li>
                                        <li class="page-item">
                                            <button class="page-link pagination-button">3</button>
                                        </li>
                                        <li class="page-item" data-page="next" id="prev">
                                            <button class="page-link pagination-button" aria-label="Next">&gt;</button>
                                        </li>
                                    </ul>
                                </nav>
                            </div>

                            <script>
                                getPagination('#table-id');

                                function getPagination(table) {
                                    var lastPage = 1;

                                    $('#maxRows').on('change', function(evt) {
                                        lastPage = 1;
                                        $('.pagination').find('li').slice(1, -1).remove();
                                        var trnum = 0;
                                        var maxRows = parseInt($(this).val());

                                        if (maxRows == 5000) {
                                            $('.pagination').hide();
                                        } else {
                                            $('.pagination').show();
                                        }

                                        var totalRows = $(table + ' tbody tr').length;
                                        $(table + ' tr:gt(0)').each(function() {
                                            trnum++;
                                            if (trnum > maxRows) {
                                                $(this).hide();
                                            }
                                            if (trnum <= maxRows) {
                                                $(this).show();
                                            }
                                        });

                                        if (totalRows > maxRows) {
                                            var pagenum = Math.ceil(totalRows / maxRows);
                                            for (var i = 1; i <= pagenum; i++) {
                                                $('.pagination #prev').before('<li class="page-item" data-page="' + i + '">\
                                                <button class="page-link pagination-button">' + i + '</button>\
                                                </li>').show();
                                            }
                                        }

                                        $('.pagination [data-page="1"]').addClass('active');
                                        $('.pagination li').on('click', function(evt) {
                                            evt.stopImmediatePropagation();
                                            evt.preventDefault();
                                            var pageNum = $(this).attr('data-page');

                                            var maxRows = parseInt($('#maxRows').val());

                                            if (pageNum == 'prev') {
                                                if (lastPage == 1) {
                                                    return;
                                                }
                                                pageNum = --lastPage;
                                            }
                                            if (pageNum == 'next') {
                                                if (lastPage == $('.pagination li').length - 2) {
                                                    return;
                                                }
                                                pageNum = ++lastPage;
                                            }

                                            lastPage = pageNum;
                                            var trIndex = 0;
                                            $('.pagination li').removeClass('active');
                                            $('.pagination [data-page="' + lastPage + '"]').addClass('active');
                                            limitPagging();
                                            $(table + ' tr:gt(0)').each(function() {
                                                trIndex++;
                                                if (
                                                    trIndex > maxRows * pageNum ||
                                                    trIndex <= maxRows * pageNum - maxRows
                                                ) {
                                                    $(this).hide();
                                                } else {
                                                    $(this).show();
                                                }
                                            });
                                        });
                                        limitPagging();
                                    }).val(5).change();
                                }

                                function limitPagging() {
                                    if ($('.pagination li').length > 7) {
                                        if ($('.pagination li.active').attr('data-page') <= 3) {
                                            $('.pagination li.page-item:gt(5)').hide();
                                            $('.pagination li.page-item:lt(5)').show();
                                            $('.pagination [data-page="next"]').show();
                                        }
                                        if ($('.pagination li.active').attr('data-page') > 3) {
                                            $('.pagination li.page-item').hide();
                                            $('.pagination [data-page="next"]').show();
                                            var currentPage = parseInt($('.pagination li.active').attr('data-page'));
                                            for (let i = currentPage - 2; i <= currentPage + 2; i++) {
                                                $('.pagination [data-page="' + i + '"]').show();
                                            }
                                        }
                                    }
                                }
                            </script>
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
                                // When the user clicks anywhere outside of the modal, close it
                                // window.onclick = function(event) {
                                //     if (event.target == modal1) {
                                //         modal1.style.display = "none";
                                //     }
                                // }
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

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

</body>

</html>