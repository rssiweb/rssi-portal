<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

include("../../util/email.php");
if (date('m') == 1 || date('m') == 2 || date('m') == 3) { //Upto March
    $academic_year = (date('Y') - 1) . '-' . date('Y');
} else { //After MARCH
    $academic_year = date('Y') . '-' . (date('Y') + 1);
}

@$now = date('Y-m-d H:i:s');
@$year = $academic_year;

if (@$_POST['form-type'] == "leaveapply") {
    @$leaveid = 'RSL' . time();
    @$applicantid = strtoupper($_POST['applicantid']);
    @$fromdate = $_POST['fromdate'];
    @$todate = $_POST['todate'];
    @$typeofleave = $_POST['typeofleave'];
    @$creason = $_POST['creason'];
    @$comment = htmlspecialchars($_POST['comment'], ENT_QUOTES, 'UTF-8');
    @$appliedby = $_POST['appliedby'];
    @$halfday = $_POST['is_userh'] ?? 0;

    if ($leaveid != "") {

        if ($halfday == 1) {

            @$day = round((strtotime($_POST['todate']) - strtotime($_POST['fromdate'])) / (60 * 60 * 24) + 1) / 2;
        } else {
            @$day = round((strtotime($_POST['todate']) - strtotime($_POST['fromdate'])) / (60 * 60 * 24) + 1);
        }

        $leave = "INSERT INTO leavedb_leavedb (timestamp,leaveid,applicantid,fromdate,todate,typeofleave,creason,comment,appliedby,lyear,days,halfday) VALUES ('$now','$leaveid','$applicantid','$fromdate','$todate','$typeofleave','$creason','$comment','$appliedby','$year','$day',$halfday)";

        $result = pg_query($con, $leave);
        $cmdtuples = pg_affected_rows($result);


        $resultt = pg_query($con, "Select fullname,email from rssimyaccount_members where associatenumber='$applicantid'");
        @$nameassociate = pg_fetch_result($resultt, 0, 0);
        @$emailassociate = pg_fetch_result($resultt, 0, 1);

        $resulttt = pg_query($con, "Select studentname,emailaddress from rssimyprofile_student where student_id='$applicantid'");
        @$namestudent = pg_fetch_result($resulttt, 0, 0);
        @$emailstudent = pg_fetch_result($resulttt, 0, 1);

        $applicantname = $nameassociate . $namestudent;
        $email = $emailassociate . $emailstudent;

        if ($halfday != 1) {
            sendEmail("leaveapply_admin", array(
                "leaveid" => $leaveid,
                "applicantid" => $applicantid,
                "applicantname" => @$applicantname,
                "fromdate" => @date("d/m/Y", strtotime($fromdate)),
                "todate" => @date("d/m/Y", strtotime($todate)),
                "typeofleave" => $typeofleave,
                "category" => $creason,
                "day" => round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1),
                "now" => $now,
            ), $email, False);
        }
        if ($halfday == 1) {
            sendEmail("leaveapply_admin", array(
                "leaveid" => $leaveid,
                "applicantid" => $applicantid,
                "applicantname" => @$applicantname,
                "fromdate" => @date("d/m/Y", strtotime($fromdate)),
                "todate" => @date("d/m/Y", strtotime($todate)),
                "typeofleave" => $typeofleave,
                "category" => $creason,
                "day" => round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1) / 2,
                "now" => $now,
            ), $email, False);
        }
    }
}

@$id = $_POST['get_id'];
@$appid = strtoupper($_POST['get_appid']);
@$lyear = $_POST['lyear'] ? $_POST['lyear'] : $year;
@$is_user = $_POST['is_user'];

date_default_timezone_set('Asia/Kolkata');
// $date = date('Y-d-m h:i:s');

if ($id != null) {
    $result = pg_query($con, "select *, REPLACE (doc, 'view', 'preview') docp from leavedb_leavedb left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leavedb_leavedb.applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leavedb_leavedb.applicantid=student.student_id WHERE leaveid='$id' order by timestamp desc");
} else if ($appid == null && $lyear != null) {
    $result = pg_query($con, "select *, REPLACE (doc, 'view', 'preview') docp from leavedb_leavedb left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leavedb_leavedb.applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leavedb_leavedb.applicantid=student.student_id WHERE lyear='$lyear' order by timestamp desc");
} else if ($appid != null && $lyear != null) {
    $result = pg_query($con, "select *, REPLACE (doc, 'view', 'preview') docp from leavedb_leavedb left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leavedb_leavedb.applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leavedb_leavedb.applicantid=student.student_id WHERE applicantid='$appid' AND lyear='$lyear' order by timestamp desc");
    $totalsl = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$appid' AND typeofleave='Sick Leave' AND lyear='$lyear' AND (status='Approved')");
    $totalcl = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$appid' AND typeofleave='Casual Leave' AND lyear='$lyear' AND (status='Approved')");

    $allocl = pg_query($con, "SELECT COALESCE(SUM(allo_daycount),0) FROM leaveallocation WHERE allo_applicantid='$appid' AND allo_leavetype='Casual Leave' AND allo_academicyear='$lyear'");
    $allosl = pg_query($con, "SELECT COALESCE(SUM(allo_daycount),0) FROM leaveallocation WHERE allo_applicantid='$appid' AND allo_leavetype='Sick Leave' AND allo_academicyear='$lyear'");

    $cladj = pg_query($con, "SELECT COALESCE(SUM(adj_day),0) FROM leaveadjustment WHERE adj_applicantid='$appid' AND adj_leavetype='Casual Leave' AND adj_academicyear='$lyear'");
    $sladj = pg_query($con, "SELECT COALESCE(SUM(adj_day),0) FROM leaveadjustment WHERE adj_applicantid='$appid'AND adj_leavetype='Sick Leave' AND adj_academicyear='$lyear'");

    $lwptaken = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$appid'AND typeofleave='Leave Without Pay' AND lyear='$lyear' AND (status='Approved')");
    $lwpadj = pg_query($con, "SELECT COALESCE(SUM(adj_day),0) FROM leaveadjustment WHERE adj_applicantid='$appid'AND adj_leavetype='Leave Without Pay' AND adj_academicyear='$lyear'");


    $resultArrsl = pg_fetch_result($totalsl, 0, 0); //sltaken        (resultArrrsl+resultArr_sladj)-$resultArrsl
    $resultArrcl = pg_fetch_result($totalcl, 0, 0); //cltaken
    @$resultArrrcl = pg_fetch_result($allocl, 0, 0); //clallocate
    @$resultArrrsl = pg_fetch_result($allosl, 0, 0); //slallocate
    @$resultArr_cladj = pg_fetch_result($cladj, 0, 0); //cladjusted
    @$resultArr_sladj = pg_fetch_result($sladj, 0, 0); //sladjusted
    @$resultArr_lwptaken = pg_fetch_result($lwptaken, 0, 0); //sladjusted
    @$resultArr_lwpadj = pg_fetch_result($lwpadj, 0, 0); //sladjusted
} else {
    $result = pg_query($con, "select * , REPLACE (doc, 'view', 'preview') docp from leavedb_leavedb left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leavedb_leavedb.applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leavedb_leavedb.applicantid=student.student_id order by timestamp desc");
}

$resultArr = pg_fetch_all($result);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Leave Management System (LMS)</title>

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
            <h1>Leave Management System (LMS)</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">LMS</li>
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
                                <?php if (@$leaveid != null && @$cmdtuples == 0) { ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="text-align: -webkit-center;">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>ERROR: Oops, something wasn't right.</span>
                                    </div>
                                <?php } else if (@$cmdtuples == 1) { ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert" style="text-align: -webkit-center;">
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
                                <div class="col" style="display: inline-block; width:47%; text-align:right">
                                    <span class="noticea"><a href="leaveadjustment.php" target="_blank" title="Click to adjust leave">Leave Adjustment</a></span> | <span class="noticea"><a href="leaveallo.php" target="_blank" title="Click to allocate leave">Leave Allocation</a></span>
                                </div>

                                <form autocomplete="off" name="leaveapply" id="leaveapply" action="leave_admin.php" method="POST">
                                    <div class="form-group" style="display: inline-block;">

                                        <input type="hidden" name="form-type" type="text" value="leaveapply">

                                        <span class="input-help">
                                            <input type="text" name="applicantid" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo @$_GET['applicantid']; ?>" required>
                                            <small id="passwordHelpBlock" class="form-text text-muted">Applicant ID*</small>
                                        </span>
                                        <span class="input-help">
                                            <input type="date" class="form-control" name="fromdate" id="fromdate" max="" onchange="cal();" required>
                                            <small id="passwordHelpBlock" class="form-text text-muted">From</small>
                                        </span>
                                        <span class="input-help">
                                            <input type="date" class="form-control" name="todate" id="todate" min="" onchange="cal();" required>
                                            <small id="passwordHelpBlock" class="form-text text-muted">To</small>
                                        </span>
                                        <span class="input-help">
                                            <select name="typeofleave" id="typeofleave" class="form-select" style="display: -webkit-inline-box; width:20vh; " required>
                                                <option disabled selected hidden>Types of Leave</option>
                                                <option value="Sick Leave">Sick Leave</option>
                                                <option value="Casual Leave">Casual Leave</option>
                                                <option value="Leave Without Pay">Leave Without Pay</option>
                                            </select>
                                            <small id="passwordHelpBlock" class="form-text text-muted">Types of Leave</small>
                                        </span>
                                        <span class="input-help">
                                            <select name="creason" id='creason' class="form-select">
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
                                        <div id="filter-checksh">
                                            <input type="checkbox" name="is_userh" id="is_userh" value="1" <?php if (isset($_POST['is_userh'])) echo "checked='checked'"; ?> />
                                            <label for="is_userh" style="font-weight: 400;">Half day</label>
                                        </div>
                                    </div>

                                </form>

                                <script>
                                    function cal() {
                                        document.getElementById("todate").min = document.getElementById("fromdate").value;
                                        document.getElementById("fromdate").max = document.getElementById("todate").value;
                                    }
                                </script>

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
                                            items = ["Late entry", "Timesheet leave", "Earned/Vacation/Privilege Leave", "Sabbatical Leave", "Marriage leave", "Compensatory leaves", "Maternity Leave", "Paternity leaves", "Compassionate leaves", "Other"]
                                        } else if (x === "Leave Without Pay") {
                                            items = ["Late entry", "Timesheet leave", "Earned/Vacation/Privilege Leave", "Sabbatical Leave", "Marriage leave", "Compensatory leaves", "Maternity Leave", "Paternity leaves", "Compassionate leaves", "Other"]
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

                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Search Criteria</th>
                                            <th scope="col">Current Leave Balance</th>
                                            <th scope="col">Total Leave Taken</th>
                                            <th scope="col">Total Allocated Leave</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <form action="" method="POST">
                                                    <div class="form-group" style="display: inline-block;">
                                                        <div class="col2" style="display: inline-block;">
                                                            <input name="get_id" id="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Leave ID" value="<?php echo $id ?>">

                                                            <input name="get_appid" id="get_appid" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo $appid ?>">

                                                            <select name="lyear" id="lyear" class="form-select" style="width:max-content; display:inline-block" placeholder="Academic Year" required>
                                                                <?php if ($lyear == null) { ?>
                                                                    <option disabled selected hidden>Academic Year</option>
                                                                <?php
                                                                } else { ?>
                                                                    <option hidden selected><?php echo $lyear ?></option>
                                                                <?php }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col2 left" style="display: inline-block;">
                                                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                                            <i class="bi bi-search"></i>&nbsp;Search</button>
                                                    </div>
                                                    <div id="filter-checks">
                                                        <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_POST['is_user'])) echo "checked='checked'"; ?> />
                                                        <label for="is_user" style="font-weight: 400;">Search by Leave ID</label>
                                                    </div>
                                                </form>
                                                <script>
                                                    if ($('#is_user').not(':checked').length > 0) {

                                                        document.getElementById("get_id").disabled = true;
                                                        document.getElementById("get_appid").disabled = false;
                                                        document.getElementById("lyear").disabled = false;

                                                    } else {

                                                        document.getElementById("get_id").disabled = false;
                                                        document.getElementById("get_appid").disabled = true;
                                                        document.getElementById("lyear").disabled = true;

                                                    }

                                                    const checkbox = document.getElementById('is_user');

                                                    checkbox.addEventListener('change', (event) => {
                                                        if (event.target.checked) {
                                                            document.getElementById("get_id").disabled = false;
                                                            document.getElementById("get_appid").disabled = true;
                                                            document.getElementById("lyear").disabled = true;
                                                        } else {
                                                            document.getElementById("get_id").disabled = true;
                                                            document.getElementById("get_appid").disabled = false;
                                                            document.getElementById("lyear").disabled = false;
                                                        }
                                                    })
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
                                                        $('#lyear').append(new Option(year, year));
                                                        currentYear--;
                                                    }
                                                </script>
                                            </td>
                                            <td>
                                                <?php if ($appid != null) { ?>

                                                    Sick Leave - (<?php echo ($resultArrrsl + $resultArr_sladj) - $resultArrsl ?>)
                                                    <br>Casual Leave - (<?php echo ($resultArrrcl + $resultArr_cladj) - $resultArrcl ?>)
                                                    <br>Leave Without Pay - (<?php echo $resultArr_lwptaken - $resultArr_lwpadj ?>)
                                                <?php } ?>
                                            </td>

                                            <td>
                                                <?php if ($appid != null) { ?>

                                                    Sick Leave - <?php echo $resultArrsl ?>
                                                    <br>Casual Leave - <?php echo $resultArrcl ?>
                                                    <br>Leave Without Pay - <?php echo $resultArr_lwptaken ?>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <?php if ($appid != null) { ?>

                                                    Sick Leave - <?php echo $resultArrrsl ?>
                                                    <br>Casual Leave - <?php echo $resultArrrcl ?>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="col" style="display: inline-block; width:100%; text-align:right;">
                                    Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                                </div>

                                <div class="table-responsive">
                                    <table class="table" id="table-id">
                                        <thead>
                                            <tr>
                                                <th scope="col">Leave ID</th>
                                                <th scope="col">Applicant ID</th>
                                                <th scope="col">Applied on</th>
                                                <th scope="col">From-To</th>
                                                <th scope="col">Day(s) count</th>
                                                <th scope="col">Leave Details</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Reviewer</th>
                                                <th scope="col" width="15%">Remarks</th>
                                                <th scope="col"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (sizeof($resultArr) > 0) { ?>
                                                <?php foreach ($resultArr as $array) { ?>
                                                    <tr>
                                                        <td>
                                                            <?php if ($array['doc'] != null) { ?>
                                                                <a href="javascript:void(0)" onclick="showpdf('<?php echo $array['leaveid']; ?>')">
                                                                    <?php echo $array['leaveid']; ?>
                                                                </a>
                                                            <?php } else { ?>
                                                                <?php echo $array['leaveid']; ?>
                                                            <?php } ?>
                                                        </td>
                                                        <td>
                                                            <?php echo $array['applicantid'] . '<br>' . $array['fullname'] . $array['studentname']; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo @date("d/m/Y g:i a", strtotime($array['timestamp'])); ?>
                                                        </td>
                                                        <td>
                                                            <?php echo @date("d/m/Y", strtotime($array['fromdate'])) . '—' . @date("d/m/Y", strtotime($array['todate'])); ?>
                                                        </td>
                                                        <td>
                                                            <?php echo $array['days']; ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $typeofLeave = htmlspecialchars($array['typeofleave'] ?? '');
                                                            $shift = htmlspecialchars($array['shift'] ?? '');
                                                            echo $typeofLeave . ($shift ? '-' . $shift : '') . '<br>' . $array['creason'] . '<br>';

                                                            $applicantComment = isset($array['applicantcomment']) ? $array['applicantcomment'] : '';
                                                            $shortComment = strlen($applicantComment) > 30 ? substr($applicantComment, 0, 30) . "..." : $applicantComment;
                                                            echo '<span class="short-comment">' . htmlspecialchars($shortComment) . '</span>';
                                                            echo '<span class="full-comment" style="display: none;">' . htmlspecialchars($applicantComment) . '</span>';

                                                            if (strlen($applicantComment) > 30) {
                                                                echo ' <a href="#" class="more-link">more</a>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php echo $array['status']; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo $array['reviewer_id'] . '<br>' . $array['reviewer_name']; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo $array['comment']; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo '<button type="button" onclick="showDetails(\'' . $array['leaveid'] . '\')" style="display: inline-block; width:fit-content; word-wrap:break-word; outline: none; background: none; padding: 0; border: none;" title="Details">
                                <i class="bi bi-box-arrow-up-right" style="font-size: 14px; color:#777777;" title="Show Details"></i>
                            </button>'; ?>

                                                            <?php if ($array['phone'] != null || $array['contact'] != null) { ?>
                                                                <?php echo '<a href="https://api.whatsapp.com/send?phone=91' . $array['phone'] . $array['contact'] . '&text=Dear ' . $array['fullname'] . $array['studentname'] . ' (' . $array['applicantid'] . '),%0A%0ABased on your timesheet data, system-enforced leave has been initiated for ' . @date("d/m/Y", strtotime($array['fromdate'])) . '—' . @date("d/m/Y", strtotime($array['todate'])) . ' (' . $array['days'] . ' day(s)) in the system.%0A%0AIf you think this is done by mistake, please call on 7980168159 or write to us at info@rssi.in.%0A%0A--RSSI%0A%0A**This is an automatically generated SMS" target="_blank">
                                    <i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS ' . $array['phone'] . $array['contact'] . '"></i>
                                </a>'; ?>
                                                            <?php } else { ?>
                                                                <i class="bi bi-whatsapp" style="color:#A2A2A2;" title="Send SMS"></i>
                                                            <?php } ?>

                                                            <?php if ($array['email'] != null || $array['emailaddress'] != null) { ?>
                                                                <form action="#" name="email-form-<?php echo $array['leaveid']; ?>" method="POST" style="display: inline-block;">
                                                                    <input type="hidden" name="template" value="leaveconf">
                                                                    <input type="hidden" name="data[leaveid]" value="<?php echo $array['leaveid']; ?>">
                                                                    <input type="hidden" name="data[applicantid]" value="<?php echo $array['applicantid']; ?>">
                                                                    <input type="hidden" name="data[typeofleave]" value="<?php echo $array['typeofleave']; ?>">
                                                                    <input type="hidden" name="data[applicantname]" value="<?php echo $array['fullname'] . $array['studentname']; ?>">
                                                                    <input type="hidden" name="data[category]" value="<?php echo $array['creason']; ?>">
                                                                    <input type="hidden" name="data[comment]" value="<?php echo $array['comment']; ?>">
                                                                    <input type="hidden" name="data[day]" value="<?php echo $array['days']; ?>">
                                                                    <input type="hidden" name="data[fromdate]" value="<?php echo @date("d/m/Y", strtotime($array['fromdate'])); ?>">
                                                                    <input type="hidden" name="data[todate]" value="<?php echo @date("d/m/Y", strtotime($array['todate'])); ?>">
                                                                    <input type="hidden" name="data[status]" value="<?php echo @strtoupper($array['status']); ?>">
                                                                    <input type="hidden" name="email" value="<?php echo $array['email'] . $array['emailaddress']; ?>">

                                                                    <button type="submit" style="display: inline-block; width:fit-content; word-wrap:break-word; outline: none; background: none; padding: 0; border: none;">
                                                                        <i class="bi bi-envelope-at" style="color:#444444;" title="Send Email <?php echo $array['email'] . $array['emailaddress']; ?>"></i>
                                                                    </button>
                                                                </form>
                                                            <?php } else { ?>
                                                                <i class="bi bi-envelope-at" style="color:#A2A2A2;" title="Send Email"></i>
                                                            <?php } ?>

                                                            <form name="leavedelete_<?php echo $array['leaveid']; ?>" action="#" method="POST" style="display: inline-block;">
                                                                <input type="hidden" name="form-type" value="leavedelete">
                                                                <input type="hidden" name="leavedeleteid" value="<?php echo $array['leaveid']; ?>">
                                                                <button type="submit" onclick="validateForm()" style="display: inline-block; width:fit-content; word-wrap:break-word; outline: none; background: none; padding: 0; border: none;" title="Delete <?php echo $array['leaveid']; ?>">
                                                                    <i class="bi bi-x-lg"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } else if ($id == null) { ?>
                                                <tr>
                                                    <td colspan="10">Please select Filter value.</td>
                                                </tr>
                                            <?php } else { ?>
                                                <tr>
                                                    <td colspan="10">No record was found for the selected filter value.</td>
                                                </tr>
                                            <?php } ?>
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
                                <div class="modal" id="myModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5" id="exampleModalLabel">Leave Details</h1>
                                                <button type="button" id="closedetails-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">

                                                <div style="width:100%; text-align:right">
                                                    <p id="status" class="badge " style="display: inline !important;"><span class="leaveid"></span></p>
                                                </div>

                                                <form id="leavereviewform" name="leavereviewform" action="#" method="POST">
                                                    <input type="hidden" class="form-control" name="form-type" type="text" value="leavereviewform" readonly>
                                                    <input type="hidden" class="form-control" name="reviewer_id" id="reviewer_id" type="text" value="<?php echo $associatenumber ?>" readonly>
                                                    <input type="hidden" class="form-control" name="reviewer_name" id="reviewer_name" type="text" value="<?php echo $fullname ?>" readonly>
                                                    <input type="hidden" class="form-control" name="leaveidd" id="leaveidd" type="text" readonly>
                                                    <span class="input-help">
                                                        <input type="date" class="form-control" name="fromdate" id="fromdated" type="text" value="">
                                                        <small id="passwordHelpBlock" class="form-text text-muted">From</small>
                                                    </span>
                                                    <span class="input-help">
                                                        <input type="date" class="form-control" name="todate" id="todated" type="text" value="">
                                                        <small id="passwordHelpBlock" class="form-text text-muted">To</small>
                                                    </span>

                                                    <select name="leave_status" id="leave_status" class="form-select" style="display: -webkit-inline-box; width:20vh; " required>
                                                        <option disabled selected hidden>Status</option>
                                                        <option value="Approved">Approved</option>
                                                        <option value="Under review">Under review</option>
                                                        <option value="Rejected">Rejected</option>
                                                    </select>

                                                    <span class="input-help">
                                                        <textarea type="text" name="reviewer_remarks" id="reviewer_remarks" class="form-control" placeholder="HR remarks" value=""></textarea>
                                                        <small id="passwordHelpBlock" class="form-text text-muted">HR remarks</small>
                                                    </span>
                                                    <div id="filter-checkshr">
                                                        <input type="checkbox" name="is_userhr" id="" />
                                                        <label for="is_userhr" style="font-weight: 400;">Half day</label>
                                                    </div>
                                                    <br>
                                                    <button type="submit" id="leaveupdate" class="btn btn-danger btn-sm " style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none">Update</button>
                                                </form>
                                                <div class="modal-footer">
                                                    <button type="button" id="closedetails-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <script>
                                    var data = <?php echo json_encode($resultArr) ?>

                                    // Get the modal
                                    var modal = document.getElementById("myModal");
                                    // Get the <span> element that closes the modal
                                    var closedetails = [
                                        document.getElementById("closedetails-header"),
                                        document.getElementById("closedetails-footer")
                                    ];

                                    function showDetails(id) {
                                        // console.log(modal)
                                        // console.log(modal.getElementsByClassName("data"))
                                        var mydata = undefined
                                        data.forEach(item => {
                                            if (item["leaveid"] == id) {
                                                mydata = item;
                                            }
                                        })

                                        var keys = Object.keys(mydata)
                                        keys.forEach(key => {
                                            var span = modal.getElementsByClassName(key)
                                            if (span.length > 0)
                                                span[0].innerHTML = mydata[key];
                                        })
                                        modal.style.display = "block";

                                        //class add 
                                        var status = document.getElementById("status")
                                        if (mydata["status"] === "Approved") {
                                            status.classList.add("bg-success")
                                            status.classList.remove("bg-danger")
                                        } else {
                                            status.classList.remove("bg-success")
                                            status.classList.add("bg-danger")
                                        }
                                        //class add end

                                        var profile = document.getElementById("leaveidd")
                                        profile.value = mydata["leaveid"]
                                        if (mydata["status"] !== null) {
                                            profile = document.getElementById("leave_status")
                                            profile.value = mydata["status"]
                                        }
                                        if (mydata["comment"] !== null) {
                                            profile = document.getElementById("reviewer_remarks")
                                            profile.value = mydata["comment"]
                                        }

                                        if (mydata["fromdate"] !== null) {
                                            profile = document.getElementById("fromdated")
                                            profile.value = mydata["fromdate"]
                                        }
                                        if (mydata["todate"] !== null) {
                                            profile = document.getElementById("todated")
                                            profile.value = mydata["todate"]
                                        }

                                        // document.getElementsByName("leavereviewform")[0].id = "leavereviewform" + mydata["leaveid"];
                                        document.getElementsByName("is_userhr")[0].id = "is_userhr" + mydata["leaveid"];

                                        profile = document.getElementById("is_userhr" + mydata["leaveid"])
                                        profile.value = mydata["halfday"]

                                        $('input[type="checkbox"]').on('change', function() {
                                            this.value ^= 1;
                                        });


                                        if (mydata["halfday"] == 1) {
                                            document.getElementById("is_userhr" + mydata["leaveid"]).checked = true;
                                        } else {
                                            document.getElementById("is_userhr" + mydata["leaveid"]).checked = false;
                                        }

                                        if (mydata["status"] == 'Approved' || mydata["status"] == 'Rejected') {
                                            document.getElementById("leaveupdate").disabled = true;
                                        } else {
                                            document.getElementById("leaveupdate").disabled = false;
                                        }
                                    }
                                    // When the user clicks on <span> (x), close the modal
                                    closedetails.forEach(function(element) {
                                        element.addEventListener("click", closeModal);
                                    });

                                    function closeModal() {
                                        var modal1 = document.getElementById("myModal");
                                        modal1.style.display = "none";
                                    }
                                </script>
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
                                    var data = <?php echo json_encode($resultArr) ?>;
                                    const scriptURL = 'payment-api.php'

                                    function validateForm() {
                                        if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {

                                            data.forEach(item => {
                                                const form = document.forms['leavedelete_' + item.leaveid]
                                                form.addEventListener('submit', e => {
                                                    e.preventDefault()
                                                    fetch(scriptURL, {
                                                            method: 'POST',
                                                            body: new FormData(document.forms['leavedelete_' + item.leaveid])
                                                        })
                                                        .then(response =>
                                                            alert("Record has been deleted.") +
                                                            location.reload()
                                                        )
                                                        .catch(error => console.error('Error!', error.message))
                                                })

                                                console.log(item)
                                            })
                                        } else {
                                            alert("Record has NOT been deleted.");
                                            return false;
                                        }
                                    }

                                    const form = document.getElementById('leavereviewform')
                                    form.addEventListener('submit', e => {
                                        e.preventDefault()
                                        fetch(scriptURL, {
                                                method: 'POST',
                                                body: new FormData(document.getElementById('leavereviewform'))
                                            })
                                            .then(response =>
                                                alert("Record has been updated.") +
                                                location.reload()
                                            )
                                            .catch(error => console.error('Error!', error.message))
                                    })

                                    data.forEach(item => {
                                        const formId = 'email-form-' + item.leaveid
                                        const form = document.forms[formId]
                                        form.addEventListener('submit', e => {
                                            e.preventDefault()
                                            fetch('mailer.php', {
                                                    method: 'POST',
                                                    body: new FormData(document.forms[formId])
                                                })
                                                .then(response =>
                                                    alert("Email has been sent.")
                                                )
                                                .catch(error => console.error('Error!', error.message))
                                        })
                                    })
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