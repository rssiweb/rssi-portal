<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

include("../../util/email.php");

// Academic year calculation
if (in_array(date('m'), [1, 2, 3])) { // Upto March
    $academic_year = (date('Y') - 1) . '-' . date('Y');
} else { // After March
    $academic_year = date('Y') . '-' . (date('Y') + 1);
}

$now = date('Y-m-d H:i:s');
$year = $academic_year;
$cmdtuples = '';

// Handle leave apply form
if (!empty($_POST['form-type']) && $_POST['form-type'] === "leaveapply") {
    $leaveid      = 'RSL' . time();
    $applicantid  = strtoupper(trim($_POST['applicantid'] ?? ''));
    $fromdate     = $_POST['fromdate'] ?? null;
    $todate       = $_POST['todate'] ?? null;
    $typeofleave  = $_POST['typeofleave'] ?? '';
    $creason      = $_POST['creason'] ?? '';
    $comment      = htmlspecialchars($_POST['comment'] ?? '', ENT_QUOTES, 'UTF-8');
    $appliedby    = $_POST['appliedby'] ?? '';
    $halfday      = isset($_POST['is_userh']) ? (int)$_POST['is_userh'] : 0;

    if (!empty($leaveid) && $fromdate && $todate) {
        $diffDays = (strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1;
        $day = $halfday === 1 ? round($diffDays) / 2 : round($diffDays);

        $leaveQuery = "
            INSERT INTO leavedb_leavedb 
            (timestamp, leaveid, applicantid, fromdate, todate, typeofleave, creason, comment, appliedby, lyear, days, halfday)
            VALUES 
            ('$now', '$leaveid', '$applicantid', '$fromdate', '$todate', '$typeofleave', '$creason', '$comment', '$appliedby', '$year', '$day', $halfday)
        ";

        $result = pg_query($con, $leaveQuery);
        $cmdtuples = pg_affected_rows($result);
        if (!$result) {
            error_log("Leave insertion failed: " . pg_last_error($con));
        }

        // Fetch applicant details (faculty and student)
        $resultt  = pg_query($con, "SELECT fullname, email FROM rssimyaccount_members WHERE associatenumber='$applicantid'");
        $nameassociate  = $resultt ? pg_fetch_result($resultt, 0, 0) : '';
        $emailassociate = $resultt ? pg_fetch_result($resultt, 0, 1) : '';

        $resulttt  = pg_query($con, "SELECT studentname, emailaddress FROM rssimyprofile_student WHERE student_id='$applicantid'");
        $namestudent  = $resulttt ? pg_fetch_result($resulttt, 0, 0) : '';
        $emailstudent = $resulttt ? pg_fetch_result($resulttt, 0, 1) : '';

        $applicantname = $nameassociate . $namestudent;
        $email = $emailassociate . $emailstudent;

        // Send email notification
        $dayCount = $halfday === 1 ? $day : round($diffDays);

        sendEmail("leaveapply_admin", [
            "leaveid"       => $leaveid,
            "applicantid"   => $applicantid,
            "applicantname" => $applicantname,
            "fromdate"      => date("d/m/Y", strtotime($fromdate)),
            "todate"        => date("d/m/Y", strtotime($todate)),
            "typeofleave"   => $typeofleave,
            "category"      => $creason,
            "day"           => $dayCount,
            "now"           => $now,
        ], $email, false);
    }
}

// Filters and queries
$appid   = isset($_POST['get_appid']) ? strtoupper($_POST['get_appid']) : null;
$lyear   = $_POST['lyear'] ?? $year;

date_default_timezone_set('Asia/Kolkata');

if (!$appid && $lyear) {
    $result = pg_query($con, "SELECT *, REPLACE(doc, 'view', 'preview') docp 
        FROM leavedb_leavedb 
        LEFT JOIN (SELECT associatenumber, fullname, email, phone FROM rssimyaccount_members) faculty 
            ON leavedb_leavedb.applicantid = faculty.associatenumber  
        LEFT JOIN (SELECT student_id, studentname, emailaddress, contact FROM rssimyprofile_student) student 
            ON leavedb_leavedb.applicantid = student.student_id 
        WHERE lyear = '$lyear' 
        ORDER BY timestamp DESC");
} elseif ($appid && $lyear) {
    $result = pg_query($con, "SELECT *, REPLACE(doc, 'view', 'preview') docp 
        FROM leavedb_leavedb 
        LEFT JOIN (SELECT associatenumber, fullname, email, phone FROM rssimyaccount_members) faculty 
            ON leavedb_leavedb.applicantid = faculty.associatenumber  
        LEFT JOIN (SELECT student_id, studentname, emailaddress, contact FROM rssimyprofile_student) student 
            ON leavedb_leavedb.applicantid = student.student_id 
        WHERE applicantid = '$appid' AND lyear = '$lyear' 
        ORDER BY timestamp DESC");

    // Calculations for leaves
    $totalsl    = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$appid' AND typeofleave='Sick Leave' AND lyear='$lyear' AND status='Approved'");
    $totalcl    = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$appid' AND typeofleave='Casual Leave' AND lyear='$lyear' AND status='Approved'");
    $allocl     = pg_query($con, "SELECT COALESCE(SUM(allo_daycount),0) FROM leaveallocation WHERE allo_applicantid='$appid' AND allo_leavetype='Casual Leave' AND allo_academicyear='$lyear'");
    $allosl     = pg_query($con, "SELECT COALESCE(SUM(allo_daycount),0) FROM leaveallocation WHERE allo_applicantid='$appid' AND allo_leavetype='Sick Leave' AND allo_academicyear='$lyear'");
    $cladj      = pg_query($con, "SELECT COALESCE(SUM(adj_day),0) FROM leaveadjustment WHERE adj_applicantid='$appid' AND adj_leavetype='Casual Leave' AND adj_academicyear='$lyear'");
    $sladj      = pg_query($con, "SELECT COALESCE(SUM(adj_day),0) FROM leaveadjustment WHERE adj_applicantid='$appid' AND adj_leavetype='Sick Leave' AND adj_academicyear='$lyear'");
    $lwptaken   = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$appid' AND (typeofleave='Leave Without Pay' OR typeofleave='Adjustment Leave') AND lyear='$lyear' AND status='Approved'");
    $lwpadj     = pg_query($con, "SELECT COALESCE(SUM(adj_day),0) FROM leaveadjustment WHERE adj_applicantid='$appid' AND (adj_leavetype='Leave Without Pay' OR adj_leavetype='Adjustment Leave') AND adj_academicyear='$lyear'");

    $resultArrsl       = pg_fetch_result($totalsl, 0, 0);
    $resultArrcl       = pg_fetch_result($totalcl, 0, 0);
    $resultArrrcl      = pg_fetch_result($allocl, 0, 0);
    $resultArrrsl      = pg_fetch_result($allosl, 0, 0);
    $resultArr_cladj   = pg_fetch_result($cladj, 0, 0);
    $resultArr_sladj   = pg_fetch_result($sladj, 0, 0);
    $resultArr_lwptaken = pg_fetch_result($lwptaken, 0, 0);
    $resultArr_lwpadj  = pg_fetch_result($lwpadj, 0, 0);
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

    <title>Leave Admin</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for associate numbers
            $('#get_appid').select2({
                ajax: {
                    url: 'fetch_associates.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Select associate(s)',
                allowClear: true,
                // multiple: true
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for associate numbers
            $('#applicantid').select2({
                ajax: {
                    url: 'fetch_associates.php?isActive=true',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Select associate(s)',
                allowClear: true,
                // multiple: true
            });
        });
    </script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Leave Admin</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Leave Management System</a></li>
                    <li class="breadcrumb-item active">Leave Admin</li>
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
                                <?php if ($cmdtuples == 0) { ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="text-align: -webkit-center;">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>ERROR: Oops, something wasn't right.</span>
                                    </div>
                                <?php } else if ($cmdtuples == 1) { ?>
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
                                <div class="row">
                                    <div class="col text-end">
                                        <span class="noticea">
                                            <a href="leaveadjustment.php" title="Click to adjust leave">Leave Adjustment</a>
                                        </span>
                                        |
                                        <span class="noticea">
                                            <a href="leaveallo.php" title="Click to allocate leave">Leave Allocation</a>
                                        </span>
                                    </div>
                                </div>

                                <form autocomplete="off" name="leaveapply" id="leaveapply" action="leave_admin.php" method="POST">
                                    <div class="form-group" style="display: inline-block;">

                                        <input type="hidden" name="form-type" type="text" value="leaveapply">

                                        <span class="input-help">
                                            <select class="form-select" id="applicantid" name="applicantid" required style="width:200px;">
                                                <!-- Options will be populated by Select2 AJAX -->
                                            </select>
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
                                                <option value="Adjustment Leave">Adjustment Leave</option>
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
                                            <input class="form-check-input" type="checkbox" name="is_userh" id="is_userh" value="1" <?php if (isset($_POST['is_userh'])) echo "checked='checked'"; ?> />
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
                                        } else if (x === "Leave Without Pay" || x === "Adjustment Leave") {
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
                                <div class="table-responsive">
                                    <table class="table align-middle mt-5" id="myTable">
                                        <thead class="table-light">
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
                                                    <form action="" method="POST" class="row g-2 align-items-center">
                                                        <div class="col-auto">
                                                            <select class="form-select" id="get_appid" name="get_appid" required style="width:200px;">
                                                                <?php if (!empty($appid)): ?>
                                                                    <option value="<?= $appid ?>" selected><?= $appid ?></option>
                                                                <?php endif; ?>
                                                            </select>
                                                        </div>

                                                        <div class="col-auto">
                                                            <select name="lyear" id="lyear" class="form-select" required>
                                                                <?php if ($lyear == null) { ?>
                                                                    <option disabled selected hidden>Academic Year</option>
                                                                <?php } else { ?>
                                                                    <option hidden selected><?php echo $lyear ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>

                                                        <div class="col-auto">
                                                            <button type="submit" name="search_by_id" class="btn btn-success btn-sm">
                                                                <i class="bi bi-search"></i> Search
                                                            </button>
                                                        </div>
                                                    </form>

                                                    <script>
                                                        <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
                                                            var currentYear = new Date().getFullYear() - 1;
                                                        <?php } else { ?>
                                                            var currentYear = new Date().getFullYear();
                                                        <?php } ?>
                                                        for (var i = 0; i < 5; i++) {
                                                            var next = currentYear + 1;
                                                            var year = currentYear + '-' + next;
                                                            $('#lyear').append(new Option(year, year));
                                                            currentYear--;
                                                        }
                                                    </script>
                                                </td>

                                                <td>
                                                    <?php if ($appid != null) { ?>
                                                        Sick Leave - (<?= ($resultArrrsl + $resultArr_sladj) - $resultArrsl ?>)<br>
                                                        Casual Leave - (<?= ($resultArrrcl + $resultArr_cladj) - $resultArrcl ?>)<br>
                                                        Leave Without Pay/Adj - (<?= $resultArr_lwptaken - $resultArr_lwpadj ?>)
                                                    <?php } ?>
                                                </td>

                                                <td>
                                                    <?php if ($appid != null) { ?>
                                                        Sick Leave - <?= $resultArrsl ?><br>
                                                        Casual Leave - <?= $resultArrcl ?><br>
                                                        Leave Without Pay/Adj - <?= $resultArr_lwptaken ?>
                                                    <?php } ?>
                                                </td>

                                                <td>
                                                    <?php if ($appid != null) { ?>
                                                        Sick Leave - <?= $resultArrrsl ?><br>
                                                        Casual Leave - <?= $resultArrrcl ?>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
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

</body>

</html>