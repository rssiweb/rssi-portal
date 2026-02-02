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

date_default_timezone_set('Asia/Kolkata');
$now = date('Y-m-d H:i:s');

// Handle form submission for leave adjustment
if ($role == "Admin" && $filterstatus == 'Active' && isset($_POST['form-type']) && $_POST['form-type'] == "leaveadj") {
    $leaveadjustmentid = 'RSD' . time();
    $adj_applicantid = strtoupper($_POST['adj_applicantid']);
    $adj_leavetype = $_POST['adj_leavetype'];
    $adj_reason = $_POST['adj_reason'];
    $adj_academicyear = $_POST['adj_academicyear'];
    $adj_appliedby = $associatenumber;
    $adj_appliedby_name = $fullname;

    $day = 0;
    $adj_fromdate = null;
    $adj_todate = null;

    // Handle different types of adjustments
    if (!empty($_POST['adj_day'])) {
        // Adjustment with direct day count
        $adj_day = $_POST['adj_day'];
        $day = $adj_day;
    } else if (!empty($_POST['adj_fromdate']) && !empty($_POST['adj_todate'])) {
        // Adjustment with date range
        $adj_fromdate = $_POST['adj_fromdate'];
        $adj_todate = $_POST['adj_todate'];
        $day = round((strtotime($adj_todate) - strtotime($adj_fromdate)) / (60 * 60 * 24) + 1);
        $adj_day = $day;
    }

    // Insert leave adjustment record
    if ($adj_fromdate && $adj_todate) {
        $sql = "INSERT INTO leaveadjustment (adj_regdate, leaveadjustmentid, adj_applicantid, adj_fromdate, adj_todate, adj_reason, adj_leavetype, adj_appliedby, adj_academicyear, adj_day, adj_appliedby_name) 
                VALUES ('$now', '$leaveadjustmentid', '$adj_applicantid', '$adj_fromdate', '$adj_todate', '$adj_reason', '$adj_leavetype', '$adj_appliedby', '$adj_academicyear', '$day', '$adj_appliedby_name')";
    } else {
        $sql = "INSERT INTO leaveadjustment (adj_regdate, leaveadjustmentid, adj_applicantid, adj_reason, adj_leavetype, adj_appliedby, adj_academicyear, adj_day, adj_appliedby_name) 
                VALUES ('$now', '$leaveadjustmentid', '$adj_applicantid', '$adj_reason', '$adj_leavetype', '$adj_appliedby', '$adj_academicyear', '$day', '$adj_appliedby_name')";
    }

    $result = pg_query($con, $sql);
    $cmdtuples = pg_affected_rows($result);

    // Send email notification if successful
    if ($cmdtuples == 1) {
        // Get applicant details
        $query = "SELECT fullname, email FROM rssimyaccount_members WHERE associatenumber = '$adj_applicantid'
                 UNION ALL
                 SELECT studentname, emailaddress FROM rssimyprofile_student WHERE student_id = '$adj_applicantid'";
        $res = pg_query($con, $query);

        if ($row = pg_fetch_assoc($res)) {
            $emailfullname = $row['fullname'] ?? $row['studentname'] ?? '';
            $emailemail = $row['email'] ?? $row['emailaddress'] ?? '';

            if (!empty($emailemail)) {
                $emailadj_fromdate = $adj_fromdate ? date("d/m/Y", strtotime($adj_fromdate)) : "N/A";
                $emailadj_todate = $adj_todate ? date("d/m/Y", strtotime($adj_todate)) : "N/A";

                sendEmail("leaveadjustment", array(
                    "leaveadjustmentid" => $leaveadjustmentid,
                    "adj_applicantid" => $adj_applicantid,
                    "adj_applicantname" => $emailfullname,
                    "adj_fromdate" => $emailadj_fromdate,
                    "adj_todate" => $emailadj_todate,
                    "adj_day" => $day,
                    "adj_leavetype" => $adj_leavetype,
                    "now" => date("d/m/Y g:i a", strtotime($now)),
                    "adj_appliedby" => $adj_appliedby,
                    "adj_reason" => $adj_reason,
                ), $emailemail, false);
            }
        }
    }
}

// Determine current academic year for filtering
$current_year = date('Y');
$current_month = date('m');
if ($current_month == 1 || $current_month == 2 || $current_month == 3) {
    $current_academic_year = ($current_year - 1) . '-' . $current_year;
} else {
    $current_academic_year = $current_year . '-' . ($current_year + 1);
}

// Handle search parameters
$id = isset($_GET['leaveadjustmentid']) ? $_GET['leaveadjustmentid'] : null;
$appid = isset($_GET['adj_applicantid_search']) ? strtoupper($_GET['adj_applicantid_search']) : null;
$adj_academicyear_search = isset($_GET['adj_academicyear_search']) ? $_GET['adj_academicyear_search'] : $current_academic_year;
$is_user = isset($_GET['is_user']) ? $_GET['is_user'] : 0;

// Build query based on user role and search parameters
$query = "SELECT la.*, 
                 fac.associatenumber, fac.fullname, fac.email, fac.phone,
                 stu.student_id, stu.studentname, stu.emailaddress, stu.contact
          FROM leaveadjustment la
          LEFT JOIN rssimyaccount_members fac ON la.adj_applicantid = fac.associatenumber
          LEFT JOIN rssimyprofile_student stu ON la.adj_applicantid = stu.student_id
          WHERE 1=1";

// Apply filters based on user role
if ($role == "Admin" && $filterstatus == 'Active') {

    // ALWAYS restrict to academic year
    $query .= " AND la.adj_academicyear = '$adj_academicyear_search'";

    if ($is_user && $id) {
        $query .= " AND la.leaveadjustmentid = '$id'";
    }

    if ($appid) {
        $query .= " AND la.adj_applicantid = '$appid'";
    }
}

$query .= " ORDER BY la.adj_regdate DESC";

$result = pg_query($con, $query);
$resultArr = pg_fetch_all($result) ?: [];
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
    <?php include 'includes/meta.php' ?>



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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
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
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="row">
                                <?php if (@$leaveadjustmentid != null && @$cmdtuples == 0) { ?>
                                    <div class="alert alert-danger alert-dismissible text-center" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>ERROR: Oops, something wasn't right.</span>
                                    </div>
                                <?php } else if (@$cmdtuples == 1) { ?>
                                    <div class="alert alert-success alert-dismissible text-center" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-check2-circle"></i>
                                        <span>Your request has been submitted. Leave adjustment id <?php echo $leaveadjustmentid ?>.</span>
                                    </div>
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                                <?php } ?>

                                <?php if ($role == "Admin" && $filterstatus == 'Active') { ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col" colspan="2">Apply Leave Adjustment</th>
                                            </tr>
                                        </thead>
                                    </table>

                                    <form autocomplete="off" name="leaveadj" id="leaveadj" action="leaveadjustment.php" method="POST">
                                        <div class="form-group" style="display: inline-block;">

                                            <input type="hidden" name="form-type" type="text" value="leaveadj">

                                            <span class="input-help">
                                                <input type="text" name="adj_applicantid" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo @$_GET['adj_applicantid']; ?>" required>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Applicant ID*</small>
                                            </span>
                                            <span class="input-help">
                                                <input type="date"
                                                    class="form-control"
                                                    name="adj_fromdate"
                                                    id="adj_fromdate"
                                                    onchange="cal()"
                                                    required>
                                                <small class="form-text text-muted">From*</small>
                                            </span>

                                            <span class="input-help">
                                                <input type="date"
                                                    class="form-control"
                                                    name="adj_todate"
                                                    id="adj_todate"
                                                    required>
                                                <small class="form-text text-muted">To*</small>
                                            </span>

                                            <span class="input-help">
                                                <input type="number" name="adj_day" id='adj_day' class="form-control" placeholder="Day count" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" required>
                                                <small id="passwordHelpBlock_dayadjusted" class="form-text text-muted">No of day adjusted*</small>
                                            </span>
                                            <span class="input-help">
                                                <select name="adj_leavetype" id="adj_leavetype" class="form-select" style="display: -webkit-inline-box; width:20vh; " required>
                                                    <option value="" disabled selected hidden>Types of Leave</option>
                                                    <option value="Sick Leave">Sick Leave</option>
                                                    <option value="Casual Leave">Casual Leave</option>
                                                    <option value="Leave Without Pay">Leave Without Pay</option>
                                                    <option value="Adjustment Leave">Adjustment Leave</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Adjusted Leave Type</small>
                                            </span>
                                            <span class="input-help">
                                                <select name="adj_academicyear" id="adj_academicyear" class="form-select" style="display: -webkit-inline-box; width:20vh; " required>
                                                    <option value="" disabled selected hidden>Academic Year</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Academic Year</small>
                                            </span>

                                            <span class="input-help">
                                                <textarea type="text" name="adj_reason" class="form-control" placeholder="Remarks" value=""></textarea>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Remarks</small>
                                            </span>
                                        </div>
                                        <div class="col2 left" style="display: inline-block;">
                                            <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">Adjust Leave</button>

                                        </div>

                                        <div id="filter-checkss">
                                            <input class="form-check-input" type="checkbox" name="is_users" id="is_users" value="1" <?php if (isset($_GET['is_users'])) echo "checked='checked'"; ?> />
                                            <label for="is_users" style="font-weight: 400;">Adjust Leave With Salary/Other</label>
                                        </div>

                                    </form>
                                <?php } ?>
                                <table class="table" style="margin-top: 2%;">
                                    <thead>
                                        <tr>
                                            <th scope="col" colspan="2">Leave Adjustment Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <form action="" method="GET">
                                                    <div class="form-group" style="display: inline-block;">
                                                        <div class="col2" style="display: inline-block;">
                                                            <input name="leaveadjustmentid" id="leaveadjustmentid" class="form-control" style="width:max-content; display:inline-block" placeholder="Leave Adjustment ID" value="<?php echo $id ?>">
                                                            <?php if ($role == "Admin" && $filterstatus == 'Active') { ?>
                                                                <input name="adj_applicantid_search" id="adj_applicantid_search" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo $appid ?>">
                                                            <?php } ?>
                                                            <select name="adj_academicyear_search" id="adj_academicyear_search" class="form-select" style="width:max-content; display:inline-block" placeholder="Appraisal type" required>
                                                                <?php if ($adj_academicyear_search == null) { ?>
                                                                    <option value="" disabled selected hidden>Academic Year</option>
                                                                <?php
                                                                } else { ?>
                                                                    <option hidden selected><?php echo $adj_academicyear_search ?></option>
                                                                <?php }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col2 left" style="display: inline-block;">
                                                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                                            <i class="bi bi-search"></i>&nbsp;Search</button>
                                                    </div>
                                                    <?php if ($role == "Admin" && $filterstatus == 'Active') { ?>
                                                        <div id="filter-checks">
                                                            <input class="form-check-input" type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_POST['is_user'])) echo "checked='checked'"; ?> />
                                                            <label for="is_user" style="font-weight: 400;">Search by Leave Adjustment ID</label>
                                                        </div>
                                                    <?php } ?>
                                                </form>
                                                <?php if ($role == "Admin" && $filterstatus == 'Active') { ?>
                                                    <script>
                                                        if ($('#is_user').not(':checked').length > 0) {

                                                            document.getElementById("leaveadjustmentid").disabled = true;
                                                            document.getElementById("adj_applicantid_search").disabled = false;
                                                            document.getElementById("adj_academicyear_search").disabled = false;

                                                        } else {

                                                            document.getElementById("leaveadjustmentid").disabled = false;
                                                            document.getElementById("adj_applicantid_search").disabled = true;
                                                            document.getElementById("adj_academicyear_search").disabled = true;

                                                        }

                                                        const checkbox = document.getElementById('is_user');

                                                        checkbox.addEventListener('change', (event) => {
                                                            if (event.target.checked) {
                                                                document.getElementById("leaveadjustmentid").disabled = false;
                                                                document.getElementById("adj_applicantid_search").disabled = true;
                                                                document.getElementById("adj_academicyear_search").disabled = true;
                                                            } else {
                                                                document.getElementById("leaveadjustmentid").disabled = true;
                                                                document.getElementById("adj_applicantid_search").disabled = false;
                                                                document.getElementById("adj_academicyear_search").disabled = false;
                                                            }
                                                        })
                                                    </script>
                                                <?php } ?>
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
                                                        $('#adj_academicyear_search').append(new Option(year, year));
                                                        currentYear--;
                                                    }
                                                </script>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="table-responsive">
                                    <table class="table" id="table-id">
                                        <thead>
                                            <tr>
                                                <th scope="col">Leave adjustment id</th>
                                                <th scope="col">Applicant ID</th>
                                                <th scope="col">Recorded on</th>
                                                <th scope="col">Adjusted on</th>
                                                <th scope="col">No of day(s) adjusted</th>
                                                <th scope="col">Adjusted Leave Type</th>
                                                <th scope="col">Academic year</th>
                                                <th scope="col">Reviewer</th>
                                                <th scope="col">Remarks</th>

                                                <?php if ($role === "Admin" && $filterstatus === 'Active') : ?>
                                                    <th scope="col"></th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php if (!empty($resultArr)) : ?>

                                                <?php foreach ($resultArr as $array) : ?>
                                                    <tr>
                                                        <td><?= $array['leaveadjustmentid']; ?></td>

                                                        <td>
                                                            <?= $array['adj_applicantid']; ?><br>
                                                            <?= $array['fullname'] . $array['studentname']; ?>
                                                        </td>

                                                        <td>
                                                            <?= date("d/m/Y g:i a", strtotime($array['adj_regdate'])); ?>
                                                        </td>

                                                        <td>
                                                            <?php if (!empty($array['adj_fromdate']) && !empty($array['adj_todate'])) : ?>
                                                                <?= date("d/m/Y", strtotime($array['adj_fromdate'])); ?>
                                                                —
                                                                <?= date("d/m/Y", strtotime($array['adj_todate'])); ?>
                                                            <?php endif; ?>
                                                        </td>

                                                        <td><?= $array['adj_day']; ?></td>

                                                        <td>
                                                            <?= $array['adj_leavetype']; ?>
                                                        </td>
                                                        <td><?= $array['adj_academicyear']; ?></td>

                                                        <td>
                                                            <?= $array['adj_appliedby']; ?><br>
                                                            <?= $array['adj_appliedby_name']; ?>
                                                        </td>

                                                        <td><?= $array['adj_reason']; ?></td>

                                                        <?php if ($role === "Admin" && $filterstatus === 'Active') : ?>
                                                            <td class="text-center">
                                                                <div class="dropdown">
                                                                    <button
                                                                        class="btn btn-sm"
                                                                        type="button"
                                                                        data-bs-toggle="dropdown"
                                                                        aria-expanded="false"
                                                                        style="border:none;background:none;"
                                                                        title="More options">
                                                                        <i class="bi bi-three-dots-vertical"></i>
                                                                    </button>

                                                                    <ul class="dropdown-menu dropdown-menu-end">

                                                                        <!-- WhatsApp Option -->
                                                                        <?php if (!empty($array['phone']) || !empty($array['contact'])) : ?>
                                                                            <li>
                                                                                <a
                                                                                    class="dropdown-item"
                                                                                    href="https://api.whatsapp.com/send?phone=91<?= $array['phone'] . $array['contact']; ?>&text=Dear <?= $array['fullname'] . $array['studentname']; ?> (<?= $array['adj_applicantid']; ?>),%0A%0AYour <?= $array['adj_day']; ?> day(s) <?= $array['adj_leavetype']; ?> has been adjusted in the system.%0A%0A--RSSI"
                                                                                    target="_blank">
                                                                                    <i class="bi bi-whatsapp me-2 text-success"></i> Send WhatsApp
                                                                                </a>
                                                                            </li>
                                                                        <?php else : ?>
                                                                            <li>
                                                                                <span class="dropdown-item text-muted">
                                                                                    <i class="bi bi-whatsapp me-2"></i> WhatsApp not available
                                                                                </span>
                                                                            </li>
                                                                        <?php endif; ?>

                                                                        <li>
                                                                            <hr class="dropdown-divider">
                                                                        </li>

                                                                        <!-- Delete Option -->
                                                                        <!-- <li>
                                                                            <form
                                                                                name="leaveadjdelete_<?= $array['leaveadjustmentid']; ?>"
                                                                                method="POST"
                                                                                onsubmit="return validateForm();">
                                                                                <input type="hidden" name="form-type" value="leaveadjdelete">
                                                                                <input type="hidden" name="leaveadjdeleteid" value="<?= $array['leaveadjustmentid']; ?>">

                                                                                <button
                                                                                    type="submit"
                                                                                    class="dropdown-item text-danger">
                                                                                    <i class="bi bi-x-lg me-2"></i> Delete
                                                                                </button>
                                                                            </form>
                                                                        </li> -->

                                                                    </ul>
                                                                </div>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>

                                            <?php elseif ($id === null && $adj_academicyear_search === null) : ?>
                                                <tr>
                                                    <td colspan="9">Please select Filter value.</td>
                                                </tr>
                                            <?php else : ?>
                                                <tr>
                                                    <td colspan="9">No record was found for the selected filter value.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div><!-- End Reports -->
                    </div>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        if ($('#is_users').not(':checked').length > 0) {

            document.getElementById("adj_day").disabled = true;
            $('#adj_day').get(0).type = 'hidden';
            document.getElementById("passwordHelpBlock_dayadjusted").classList.add("d-none");

            document.getElementById("adj_fromdate").disabled = false;
            document.getElementById("adj_todate").disabled = false;
            $('#adj_fromdate').get(0).type = 'date';
            $('#adj_todate').get(0).type = 'date';
            document.getElementById("passwordHelpBlock_from").classList.remove("d-none");
            document.getElementById("passwordHelpBlock_to").classList.remove("d-none");

        } else {

            document.getElementById("adj_day").disabled = false;
            $('#adj_day').get(0).type = 'number';
            document.getElementById("passwordHelpBlock_dayadjusted").classList.remove("d-none");
            document.getElementById("adj_fromdate").disabled = true;
            document.getElementById("adj_todate").disabled = true;
            $('#adj_fromdate').get(0).type = 'hidden';
            $('#adj_todate').get(0).type = 'hidden';
            document.getElementById("passwordHelpBlock_from").classList.add("d-none");
            document.getElementById("passwordHelpBlock_to").classList.add("d-none");

        }

        const checkboxs = document.getElementById('is_users');

        checkboxs.addEventListener('change', (event) => {
            if (event.target.checked) {
                document.getElementById("adj_day").disabled = false;
                $('#adj_day').get(0).type = 'number';
                document.getElementById("passwordHelpBlock_dayadjusted").classList.remove("d-none");
                document.getElementById("adj_fromdate").disabled = true;
                document.getElementById("adj_todate").disabled = true;
                $('#adj_fromdate').get(0).type = 'hidden';
                $('#adj_todate').get(0).type = 'hidden';
                document.getElementById("passwordHelpBlock_from").classList.add("d-none");
                document.getElementById("passwordHelpBlock_to").classList.add("d-none");
            } else {
                document.getElementById("adj_day").disabled = true;
                $('#adj_day').get(0).type = 'hidden';
                document.getElementById("passwordHelpBlock_dayadjusted").classList.add("d-none");
                document.getElementById("adj_fromdate").disabled = false;
                document.getElementById("adj_todate").disabled = false;
                $('#adj_fromdate').get(0).type = 'date';
                $('#adj_todate').get(0).type = 'date';
                document.getElementById("passwordHelpBlock_from").classList.remove("d-none");
                document.getElementById("passwordHelpBlock_to").classList.remove("d-none");
            }
        })
    </script>
    <script>
        function cal() {
            const from = document.getElementById("adj_fromdate").value;
            const to = document.getElementById("adj_todate");

            if (from) {
                to.min = from;
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
            currentYear--;
        }
    </script>
    <script>
        var data = <?php echo json_encode($resultArr) ?>;
        const scriptURL = 'payment-api.php'

        function validateForm() {
            if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {

                data.forEach(item => {
                    const form = document.forms['leaveadjdelete_' + item.leaveadjustmentid]
                    form.addEventListener('submit', e => {
                        e.preventDefault()
                        fetch(scriptURL, {
                                method: 'POST',
                                body: new FormData(document.forms['leaveadjdelete_' + item.leaveadjustmentid])
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
    </script>
    <?php if (!empty($resultArr)) : ?>
        <script>
            $(document).ready(function() {
                $('#table-id').DataTable({
                    order: [], // no initial sorting
                    columnDefs: [{
                        orderable: false,
                        targets: -1 // last column
                    }]
                });
            });
        </script>
    <?php endif; ?>

</body>

</html>