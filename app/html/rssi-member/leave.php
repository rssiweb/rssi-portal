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

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

if ($role == 'Member') {

    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}

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
    @$applicantcomment = $_POST['applicantcomment'];
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
    if (($slbalance >= $day && $typeofleave = "Sick Leave") || ($clbalance >= $day && $typeofleave = "Casual Leave")) {

        // send uploaded file to drive
        // get the drive link
        if(empty($_FILES['medicalcertificate']['name'])) {
            $doclink = null;
        } else {
            $filename = "doc_" . $leaveid . "_" . $applicantid . "_" . time();
            $parent = '1zbevlcQJg2sZcldp23ix1uGqy5cy5Un-Sy8x8cwz0L15GRhSSdFy0k7HjMjraVwefgB6TfL0';
            $doclink = uploadeToDrive($uploadedFile, $parent, $filename);
        }

        $leave = "INSERT INTO leavedb_leavedb (timestamp,leaveid,applicantid,fromdate,todate,typeofleave,creason,comment,appliedby,lyear,applicantcomment,days,halfday,doc) VALUES ('$now','$leaveid','$applicantid','$fromdate','$todate','$typeofleave','$creason','$comment','$appliedby','$currentAcademicYear','$applicantcomment','$day',$halfday,'$doclink')";

        $result = pg_query($con, $leave);
        $cmdtuples = pg_affected_rows($result);

        if ($typeofleave = "Sick Leave") {
            @$slbalance = $slbalance - $day;
        } else if ($typeofleave = "Casual Leave") {
            @$clbalance = $clbalance - $day;
        }
    }
    if ($email != "" && $halfday != 1) {
        sendEmail("leaveapply", array(
            "leaveid" => $leaveid,
            "applicantid" => $applicantid,
            "applicantname" => @$fullname,
            "fromdate" => @date("d/m/Y", strtotime($fromdate)),
            "todate" => @date("d/m/Y", strtotime($todate)),
            "typeofleave" => $typeofleave,
            "category" => $creason,
            "day" => round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1),
            "now" => @date("d/m/Y g:i a", strtotime($now))
        ), $email);
    }
    if ($email != "" && $halfday == 1) {
        sendEmail("leaveapply", array(
            "leaveid" => $leaveid,
            "applicantid" => $applicantid,
            "applicantname" => @$fullname,
            "fromdate" => @date("d/m/Y", strtotime($fromdate)),
            "todate" => @date("d/m/Y", strtotime($todate)),
            "typeofleave" => $typeofleave,
            "category" => $creason,
            "day" => round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1) / 2,
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
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .checkbox {
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

        #hidden-panel {
            display: none;
        }
    </style>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous">
    </script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
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
    <?php $leave_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <?php if (@$leaveid != null && @$cmdtuples == 0 && @$typeofleave == "Sick Leave") { ?>

                        <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <span class="blink_me"><i class="fa-solid fa-xmark"></i></span>&nbsp;&nbsp;<span>ERROR: Your SL
                                request has not been submitted because you have applied for more than the leave
                                balance.</span>
                        </div>
                    <?php } else if (@$leaveid != null && @$cmdtuples == 0 && @$typeofleave == "Casual Leave") { ?>

                        <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <span class="blink_me"><i class="fa-solid fa-xmark"></i></i></span>&nbsp;&nbsp;<span>ERROR: Your
                                CL request has not been submitted because you have applied for more than the leave
                                balance.</span>
                        </div>
                    <?php
                    } else if (@$cmdtuples == 1) { ?>

                        <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Your
                                request has been submitted. Leave id <?php echo $leaveid ?>.</span>
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
                        <div class="alert alert-danger" role="alert" style="text-align: -webkit-center;"><span class="blink_me"><i class="fas fa-exclamation-triangle" style="color: #A9444C;"></i></span>&nbsp;
                            <b><span id="demo" style="display: inline-block;"></span></b>&nbsp; Inadequate SL and CL
                            balance. You are not eligible to take leave. Please take a makeup class to enable the apply
                            leave option.
                        </div>
                    <?php
                    } else if ((@$clbalance == 0 || @$clbalance < 0) && $filterstatus == 'Active' && $lyear != null) {
                    ?>
                        <div class="alert alert-warning" role="alert" style="text-align: -webkit-center;"><span class="blink_me"><i class="fas fa-exclamation-triangle" style="color: #A9444C;"></i></span>&nbsp;
                            <b><span id="demo" style="display: inline-block;"></span></b>&nbsp; Insufficient CL balance. You
                            are not eligible for casual leave. Please take makeup class to increase CL balance.
                        </div>
                    <?php
                    } else if ((@$slbalance == 0 || @$slbalance < 0) && $filterstatus == 'Active' && $lyear != null) {
                    ?>
                        <div class="alert alert-warning" role="alert" style="text-align: -webkit-center;"><span class="blink_me"><i class="fas fa-exclamation-triangle" style="color: #A9444C;"></i></span>&nbsp;
                            <b><span id="demo" style="display: inline-block;"></span></b>&nbsp; Insufficient SL balance. You
                            are not eligible for sick leave. Please take makeup class to increase SL balance.
                        </div>
                    <?php
                    } ?>

                    <div class="col" style="display: inline-block; width:50%;margin-left:1.5%">Home / Leave</div>
                    <div class="col" style="display: inline-block; width:47%; text-align:right">
                        <span class="noticea"><a href="leaveadjustment.php?adj_academicyear_search=<?php echo $lyear ?>" target="_blank" title="Check Adjusted Leave Record">Leave Adjustment</a></span> | <span class="noticea"><a href="leaveallo.php?allo_academicyear_search=<?php echo $lyear ?>" target="_blank" title="Check allotted leave record">Leave Allocation</a></span>
                    </div>

                    <div class="col" style="display: inline-block; width:99%; text-align:left;margin-left:1.5%">

                        <form autocomplete="off" name="academicyear" id="academicyear" action="leave.php" method="POST">
                            Academic year:&nbsp;<select name="adj_academicyear" id="adj_academicyear" onchange="this.form.submit()" class="form-control" style="display: -webkit-inline-box; width:20vh; font-size: small;" required>
                                <?php if ($lyear != null) { ?>
                                    <option hidden selected><?php echo $lyear ?></option>
                                <?php }
                                ?>
                            </select>
                        </form>
                    </div>
                </div>

                <section class="box" style="padding: 2%;">

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
                        <div class="form-group" style="display: inline-block;">

                            <input type="hidden" name="form-type" value="leaveapply">

                            <span class="input-help">
                                <input type="date" class="form-control" name="fromdate" id="fromdate" value="" onchange="cal()" required>
                                <small id="passwordHelpBlock" class="form-text text-muted">From<span style="color:red">*</span></small>
                            </span>
                            <span class="input-help">
                                <input type="date" class="form-control" name="todate" id="todate" value="" onchange="cal()" required>
                                <small id="passwordHelpBlock" class="form-text text-muted">To<span style="color:red">*</span></small>
                            </span>
                            <span class="input-help">
                                <input type="text" class="form-control" name="numdays2" id="numdays2" value="" placeholder="Day count" placeholder="Day count" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" readonly>
                                <small id="passwordHelpBlock" class="form-text text-muted">Days count</small>
                            </span>
                            <span class="input-help">
                                <select name="typeofleave" id="typeofleave" class="form-control" style="display: -webkit-inline-box; width:20vh; font-size: small;" required>
                                    <option value="" disabled selected hidden>Types of Leave</option>
                                    <option value="Sick Leave">Sick Leave</option>
                                    <option value="Casual Leave">Casual Leave</option>
                                </select>
                                <small id="passwordHelpBlock" class="form-text text-muted">Types of Leave<span style="color:red">*</span></small>
                            </span>
                            <span class="input-help">
                                <select name="creason" id='creason' class="form-control" required>
                                    <option>--Select--</option>
                                </select>
                                <small id="passwordHelpBlock" class="form-text text-muted">Leave Category<span style="color:red">*</span></small>
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

                            <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">Apply</button>

                            <div id="filter-checksh">
                                <input type="checkbox" name="is_userh" id="is_userh" value="1" onchange="cal()" />
                                <label for="is_userh" style="font-weight: 400;">Half day</label>
                            </div>

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
                        //Showing days count
                        function GetDays() {
                            var todate = new Date(document.getElementById("todate").value);
                            var fromdate = new Date(document.getElementById("fromdate").value);

                            if ($('#is_userh').not(':checked').length > 0) {
                                return ((todate - fromdate) / (24 * 3600 * 1000) + 1);

                            } else {
                                return (((todate - fromdate) / (24 * 3600 * 1000) + 1) / 2);
                            }
                            const checkbox = document.getElementById('is_userh');
                            checkbox.addEventListener('change', (event) => {
                                if (event.target.checked) {
                                    return (((todate - fromdate) / (24 * 3600 * 1000) + 1) / 2);
                                } else {
                                    return ((todate - fromdate) / (24 * 3600 * 1000) + 1);
                                }
                            })
                        }

                        function cal() {
                            if (document.getElementById("todate")) {
                                document.getElementById("numdays2").value = GetDays();
                            }
                        }

                        //Showing document upload for sick leave only 
                        $(document).ready(function() {
                            $("#typeofleave").change(function() {
                                if ($("#typeofleave").val() == "Sick Leave") {
                                    $("#hidden-panel").show()
                                } else {
                                    $("#hidden-panel").hide()
                                }
                            })
                        });
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

                                <select name="adj_academicyear" id="adj_academicyear_A" class="form-control" style="display: -webkit-inline-box; width:20vh; font-size: small;" required>
                                    <?php if ($lyear != null) { ?>
                                        <option hidden selected><?php echo $lyear ?></option>
                                    <?php }
                                    ?>
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
                       <p>Select Number Of Rows</p>
                       <div class="form-group">
                           <select class="form-control" name="state" id="maxRows">
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
                                    </table>';
                    ?>
                    <!--		Start Pagination -->
                    <div class='pagination-container'>
                        <nav>
                            <ul class="pagination">

                                <li data-page="prev">
                                    <span>
                                        < <span class="sr-only">(current)
                                    </span></span>
                                </li>
                                <!--	Here the JS Function Will Add the Rows -->
                                <li data-page="next" id="prev">
                                    <span> > <span class="sr-only">(current)</span></span>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </section>
            </div>
        </section>
    </section>
    <!--------------- POP-UP BOX ------------
-------------------------------------->
    <style>
        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 100;
            /* Sit on top */
            padding-top: 100px;
            /* Location of the box */
            left: 0;
            top: 0;
            width: 100%;
            /* Full width */
            height: 100%;
            /* Full height */
            overflow: auto;
            /* Enable scroll if needed */
            background-color: rgb(0, 0, 0);
            /* Fallback color */
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
        }

        /* Modal Content */

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 100vh;
        }

        @media (max-width:767px) {
            .modal-content {
                width: 50vh;
            }
        }

        /* The Close Button */

        .close {
            color: #aaaaaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            text-align: right;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
    <div id="myModalpdf" class="modal">

        <!-- Modal content -->
        <div class="modal-content">
            <span id="closepdf" class="close">&times;</span>

            <div style="width:100%; text-align:right">
                <p id="status2" class="label " style="display: inline !important;"><span class="status"></span></p>
            </div>

            <p style="font-size: small;">
                Leave Id: <span class="leaveid"></span><br>
                <object name="docid" id="" data="#" type="application/pdf" width="100%" height="450px"></object>
            </p>
        </div>

    </div>
    <script>
        var data1 = <?php echo json_encode($resultArr) ?>

        // Get the modal
        var modal1 = document.getElementById("myModalpdf");
        var closepdf = document.getElementById("closepdf");

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
                statuss.classList.add("label-success")
                statuss.classList.remove("label-danger")
            } else if (mydata1["status"] === "Rejected") {
                statuss.classList.remove("label-success")
                statuss.classList.add("label-danger")
            } else {
                statuss.classList.remove("label-success")
                statuss.classList.remove("label-danger")
            }
            //class add end
            document.getElementsByName("docid")[0].id = "docid" + mydata1["leaveid"];

            randomvar = document.getElementById("docid" + mydata1["leaveid"])
            randomvar.data = mydata1["docp"]
        }
        closepdf.onclick = function() {
            modal1.style.display = "none";
        }
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal1) {
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
    <script>
        getPagination('#table-id');

        function getPagination(table) {
            var lastPage = 1;

            $('#maxRows')
                .on('change', function(evt) {
                    //$('.paginationprev').html('');						// reset pagination

                    lastPage = 1;
                    $('.pagination')
                        .find('li')
                        .slice(1, -1)
                        .remove();
                    var trnum = 0; // reset tr counter
                    var maxRows = parseInt($(this).val()); // get Max Rows from select option

                    if (maxRows == 5000) {
                        $('.pagination').hide();
                    } else {
                        $('.pagination').show();
                    }

                    var totalRows = $(table + ' tbody tr').length; // numbers of rows
                    $(table + ' tr:gt(0)').each(function() {
                        // each TR in  table and not the header
                        trnum++; // Start Counter
                        if (trnum > maxRows) {
                            // if tr number gt maxRows

                            $(this).hide(); // fade it out
                        }
                        if (trnum <= maxRows) {
                            $(this).show();
                        } // else fade in Important in case if it ..
                    }); //  was fade out to fade it in
                    if (totalRows > maxRows) {
                        // if tr total rows gt max rows option
                        var pagenum = Math.ceil(totalRows / maxRows); // ceil total(rows/maxrows) to get ..
                        //	numbers of pages
                        for (var i = 1; i <= pagenum;) {
                            // for each page append pagination li
                            $('.pagination #prev')
                                .before(
                                    '<li data-page="' +
                                    i +
                                    '">\
								  <span>' +
                                    i++ +
                                    '<span class="sr-only">(current)</span></span>\
								</li>'
                                )
                                .show();
                        } // end for i
                    } // end if row count > max rows
                    $('.pagination [data-page="1"]').addClass('active'); // add active class to the first li
                    $('.pagination li').on('click', function(evt) {
                        // on click each page
                        evt.stopImmediatePropagation();
                        evt.preventDefault();
                        var pageNum = $(this).attr('data-page'); // get it's number

                        var maxRows = parseInt($('#maxRows').val()); // get Max Rows from select option

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
                        var trIndex = 0; // reset tr counter
                        $('.pagination li').removeClass('active'); // remove active class from all li
                        $('.pagination [data-page="' + lastPage + '"]').addClass(
                            'active'); // add active class to the clicked
                        // $(this).addClass('active');					// add active class to the clicked
                        limitPagging();
                        $(table + ' tr:gt(0)').each(function() {
                            // each tr in table not the header
                            trIndex++; // tr index counter
                            // if tr index gt maxRows*pageNum or lt maxRows*pageNum-maxRows fade if out
                            if (
                                trIndex > maxRows * pageNum ||
                                trIndex <= maxRows * pageNum - maxRows
                            ) {
                                $(this).hide();
                            } else {
                                $(this).show();
                            } //else fade in
                        }); // end of for each tr in table
                    }); // end of on click pagination list
                    limitPagging();
                })
                .val(5)
                .change();

            // end of on select change

            // END OF PAGINATION
        }

        function limitPagging() {
            // alert($('.pagination li').length)

            if ($('.pagination li').length > 7) {
                if ($('.pagination li.active').attr('data-page') <= 3) {
                    $('.pagination li:gt(5)').hide();
                    $('.pagination li:lt(5)').show();
                    $('.pagination [data-page="next"]').show();
                }
                if ($('.pagination li.active').attr('data-page') > 3) {
                    $('.pagination li:gt(0)').hide();
                    $('.pagination [data-page="next"]').show();
                    for (let i = (parseInt($('.pagination li.active').attr('data-page')) - 2); i <= (parseInt($(
                            '.pagination li.active').attr('data-page')) + 2); i++) {
                        $('.pagination [data-page="' + i + '"]').show();

                    }

                }
            }
        }
    </script>

</body>

</html>