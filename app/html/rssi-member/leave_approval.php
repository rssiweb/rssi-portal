<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

// Check admin
$is_admin = ($role == 'Admin');
$associatenumber = $associatenumber;

// Check if supervisor
$is_supervisor = false;
$supervisor_check = pg_query($con, "SELECT supervisor FROM rssimyaccount_members WHERE associatenumber='$associatenumber'");
if ($supervisor_check && pg_num_rows($supervisor_check) > 0) {
    $supervisor_data = pg_fetch_assoc($supervisor_check);
    $is_supervisor = !empty($supervisor_data['supervisor']);
}

// Redirect if neither admin nor supervisor
if (!$is_admin && !$is_supervisor) {
    header("Location: access_denied.php");
    exit;
}

validation();

// Academic year calculation
if (in_array(date('m'), [1, 2, 3])) {
    $academic_year = (date('Y') - 1) . '-' . date('Y');
} else {
    $academic_year = date('Y') . '-' . (date('Y') + 1);
}

$now = date('Y-m-d H:i:s');
$year = $academic_year;

// --------------------
// Handle bulk approval
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['form-type']) && $_POST['form-type'] === "bulkapproval") {

    $message = '';
    $message_type = '';

    if (!empty($_POST['leave_ids']) && is_array($_POST['leave_ids'])) {
        $leave_ids = $_POST['leave_ids'];
        $status = $_POST['bulk_status'] ?? '';
        $comment = htmlspecialchars($_POST['bulk_comment'] ?? '', ENT_QUOTES, 'UTF-8');

        if (!empty($status)) {
            $success_count = 0;

            foreach ($leave_ids as $leave_id) {
                $update_query = "UPDATE leavedb_leavedb SET 
                                status = '$status', 
                                comment = '$comment',
                                reviewer_id = '$associatenumber',
                                reviewed_on = '$now'
                                WHERE leaveid = '$leave_id'";
                $result = pg_query($con, $update_query);

                if ($result) {
                    $success_count++;

                    // Fetch leave info
                    $leave_info = pg_query($con, "SELECT * FROM leavedb_leavedb WHERE leaveid = '$leave_id'");
                    if ($leave_info && pg_num_rows($leave_info) > 0) {
                        $leave_data = pg_fetch_assoc($leave_info);
                        $applicantid = $leave_data['applicantid'];

                        // Fetch applicant details from members table
                        $nameassociate = '';
                        $emailassociate = '';
                        $resultt = pg_query($con, "SELECT fullname, email FROM rssimyaccount_members WHERE associatenumber='$applicantid'");
                        if ($resultt && pg_num_rows($resultt) > 0) {
                            $nameassociate = pg_fetch_result($resultt, 0, 0);
                            $emailassociate = pg_fetch_result($resultt, 0, 1);
                        }

                        // Fetch applicant details from student table
                        $namestudent = '';
                        $emailstudent = '';
                        $resulttt = pg_query($con, "SELECT studentname, emailaddress FROM rssimyprofile_student WHERE student_id='$applicantid'");
                        if ($resulttt && pg_num_rows($resulttt) > 0) {
                            $namestudent = pg_fetch_result($resulttt, 0, 0);
                            $emailstudent = pg_fetch_result($resulttt, 0, 1);
                        }

                        $applicantname = trim($nameassociate . ' ' . $namestudent);
                        $emailList = array_filter([$emailassociate, $emailstudent]); // clean empty ones
                        $emails = implode(',', $emailList);

                        $fromdate = $leave_data['fromdate'];
                        $todate   = $leave_data['todate'];
                        $halfday  = $leave_data['halfday']; // assuming you have this column in leavedb_leavedb

                        $days = round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1);

                        if ($halfday == 1) {
                            $days = $days / 2;
                        }

                        if (!empty($emails)) {
                            sendEmail("leaveconf", [
                                "leaveid" => $leave_id,
                                "applicantid" => $applicantid,
                                "applicantname" => $applicantname,
                                "fromdate" => date("d/m/Y", strtotime($leave_data['fromdate'])),
                                "todate" => date("d/m/Y", strtotime($leave_data['todate'])),
                                "typeofleave" => $leave_data['typeofleave'],
                                "status" => $status,
                                "comment" => $comment,
                                "reviewer" => $fullname,
                                "now" => $now,
                                "day" => $days
                            ], $emails);
                        }
                    }
                }
            }

            $message = $success_count > 0
                ? "Successfully updated $success_count leave request(s)."
                : "No leaves were updated.";
            $message_type = $success_count > 0 ? "success" : "warning";
        } else {
            $message = "Please select a status for bulk action.";
            $message_type = "danger";
        }
    } else {
        $message = "Please select at least one leave request.";
        $message_type = "danger";
    }

    // --------------------
    // Redirect to prevent resubmission
    // --------------------
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_message_type'] = $message_type;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Retrieve flash message
$message = $_SESSION['flash_message'] ?? '';
$message_type = $_SESSION['flash_message_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

// --------------------
// Filters for TABLE VIEW only
// --------------------
$lyear = $_POST['lyear'] ?? $year;
$status_filter = $_POST['status_filter'] ?? '';
$type_filter = $_POST['type_filter'] ?? '';
$applicant_id = $_POST['applicant_id'] ?? '';
$leave_id = $_POST['leave_id'] ?? '';
$enable_leave_id = isset($_POST['enable_leave_id']); // Check if checkbox was checked

// Build query for TABLE VIEW
$where_conditions = [];

// If searching exclusively by leave_id - IGNORE ALL OTHER FILTERS including academic year
if ($enable_leave_id && !empty($leave_id)) {
    $where_conditions[] = "l.leaveid = '$leave_id'";
}
// Normal multi-filter search
else {
    // Always apply academic year filter in normal mode
    $where_conditions[] = "l.lyear = '$lyear'";

    // Status filter
    if (!empty($status_filter)) {
        $where_conditions[] = "l.status = '$status_filter'";
    } else if (empty($status_filter) && empty($type_filter) && empty($applicant_id) && empty($leave_id)) {
        // Default: show only pending/under review when no specific filters are applied
        $where_conditions[] = "l.status IN ('Pending', 'Under review') AND f.filterstatus = 'Active'";
    }

    // Type filter
    if (!empty($type_filter)) {
        $where_conditions[] = "l.typeofleave = '$type_filter'";
    }

    // Applicant ID filter
    if (!empty($applicant_id)) {
        $where_conditions[] = "l.applicantid = '$applicant_id'";
    }

    // Leave ID filter (when not in exclusive mode)
    if (!empty($leave_id) && !$enable_leave_id) {
        $where_conditions[] = "l.leaveid = '$leave_id'";
    }
}

// Supervisor: show only team members' leaves
if ($is_supervisor && !$is_admin) {
    $team_members = pg_query($con, "SELECT associatenumber FROM rssimyaccount_members WHERE supervisor = '$associatenumber'");
    $team_ids = [];
    if ($team_members && pg_num_rows($team_members) > 0) {
        while ($member = pg_fetch_assoc($team_members)) {
            $team_ids[] = "'" . $member['associatenumber'] . "'";
        }
    }
    if (!empty($team_ids)) {
        $where_conditions[] = "l.applicantid IN (" . implode(",", $team_ids) . ")";
    } else {
        $where_conditions[] = "1 = 0";
    }
}

// Exclude current user's own leaves
$where_conditions[] = "l.applicantid != '$associatenumber'";
$where_clause = implode(" AND ", $where_conditions);

// Get leave requests for TABLE VIEW
$result = pg_query($con, "SELECT l.*, 
                         TO_CHAR(l.fromdate, 'YYYY-MM-DD') as fromdate_formatted,
                         TO_CHAR(l.todate, 'YYYY-MM-DD') as todate_formatted,
                         REPLACE(l.doc, 'view', 'preview') docp,
                         COALESCE(f.fullname, s.studentname) as applicant_name,
                         COALESCE(f.email, s.emailaddress) as applicant_email,
                         r.fullname as reviewer_name
                         FROM leavedb_leavedb l
                         LEFT JOIN rssimyaccount_members f ON l.applicantid = f.associatenumber  
                         LEFT JOIN rssimyprofile_student s ON l.applicantid = s.student_id 
                         LEFT JOIN rssimyaccount_members r ON l.reviewer_id = r.associatenumber
                         WHERE $where_clause
                         ORDER BY l.timestamp DESC");

$resultArr = $result ? pg_fetch_all($result) : [];

// For Calendar View - we'll load data via AJAX to optimize performance
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Leave Approval</title>

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

        tbody tr {
            cursor: pointer;
        }

        tbody tr:hover {
            background-color: #f5f5f5;
        }

        .bulk-actions {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }

        .checkbox-cell {
            width: 30px;
        }

        .pdf-viewer {
            width: 100%;
            height: 50vh;
            border: none;
        }

        /* Calendar Styles */
        .calendar-view {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .calendar-nav {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #dee2e6;
        }

        .calendar-day-header {
            background: #e9ecef;
            padding: 10px;
            text-align: center;
            font-weight: 600;
            font-size: 0.9em;
        }

        .calendar-day {
            background: white;
            min-height: 120px;
            padding: 8px;
            position: relative;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .calendar-day:hover {
            background: #f8f9fa;
        }

        .calendar-day.other-month {
            background: #f8f9fa;
            color: #6c757d;
        }

        .calendar-day.today {
            background: #e7f1ff;
        }

        .calendar-date {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 0.9em;
        }

        .calendar-events {
            font-size: 0.75em;
        }

        .calendar-event {
            margin: 2px 0;
            padding: 2px 4px;
            border-radius: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }

        .calendar-event.approved {
            background: #d1e7dd;
            color: #0f5132;
            border-left: 3px solid #198754;
        }

        .calendar-event.pending {
            background: #fff3cd;
            color: #664d03;
            border-left: 3px solid #ffc107;
        }

        .calendar-event.under-review {
            background: #fff3cd;
            color: #664d03;
            border-left: 3px solid #ffc107;
        }

        .calendar-event.rejected {
            background: #f8d7da;
            color: #721c24;
            border-left: 3px solid #dc3545;
        }

        .calendar-event.multiple {
            background: #e7f1ff;
            color: #0d6efd;
            border-left: 3px solid #0d6efd;
        }

        .calendar-view-selector {
            margin-bottom: 20px;
        }

        .event-count {
            background: #6c757d;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 5px;
        }

        .legend {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.8em;
        }

        .legend-color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
        }

        .month-selector {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .calendar-loading {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }

        .calendar-filters {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Leave Approval</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Leave Management System</a></li>
                    <li class="breadcrumb-item active">Leave Approval</li>
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

                            <?php if (!empty($message)): ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi <?php echo $message_type == 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>"></i>
                                    <span><?php echo $message; ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="calendar-view-selector mb-3">
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="view-type" id="view-table" autocomplete="off" <?php echo (!isset($_GET['view']) || $_GET['view'] == 'list') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="view-table">
                                        <i class="bi bi-list"></i> Table View
                                    </label>

                                    <input type="radio" class="btn-check" name="view-type" id="view-calendar" autocomplete="off" <?php echo (isset($_GET['view']) && $_GET['view'] == 'calendar') ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-primary" for="view-calendar">
                                        <i class="bi bi-calendar"></i> Calendar View
                                    </label>
                                </div>
                            </div>

                            <!-- TABLE VIEW SECTION -->
                            <div id="table-view-section">
                                <div class="row align-items-center">
                                    <div class="col-md-9">
                                        <form action="" method="POST" class="mb-3">
                                            <div class="form-group d-inline-block">
                                                <div class="col2 d-inline-block">
                                                    <input name="leave_id" id="leave_id" class="form-control d-inline-block" style="width:max-content;"
                                                        placeholder="Leave ID" value="<?php echo $leave_id ?>"
                                                        <?php echo empty($leave_id) ? 'disabled' : '' ?>>

                                                    <input name="applicant_id" id="applicant_id" class="form-control d-inline-block" style="width:max-content;" placeholder="Applicant ID" value="<?php echo $applicant_id ?>">

                                                    <select name="lyear" id="lyear" class="form-select d-inline-block" style="width:max-content;" required>
                                                        <?php if ($lyear == null) { ?>
                                                            <option disabled selected hidden>Academic Year</option>
                                                        <?php } else { ?>
                                                            <option hidden selected><?php echo $lyear ?></option>
                                                        <?php } ?>
                                                        <?php
                                                        $currentYear = (in_array(date('m'), [1, 2, 3])) ? date('Y') - 1 : date('Y');
                                                        for ($i = 0; $i < 5; $i++) {
                                                            $startYear = $currentYear;
                                                            $endYear = $startYear + 1;
                                                            echo "<option value='$startYear-$endYear'>$startYear-$endYear</option>";
                                                            $currentYear--;
                                                        }
                                                        ?>
                                                    </select>

                                                    <select name="status_filter" id="status_filter" class="form-select d-inline-block" style="width:max-content;">
                                                        <option value="" <?php echo $status_filter == '' ? 'selected' : ''; ?> disabled>Select Status</option>
                                                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="Under review" <?php echo $status_filter == 'Under review' ? 'selected' : ''; ?>>Under Review</option>
                                                        <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                                        <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                    </select>

                                                    <select name="type_filter" id="type_filter" class="form-select d-inline-block" style="width:max-content;">
                                                        <option value="" <?php echo $type_filter == '' ? 'selected' : ''; ?> disabled>Select Type</option>
                                                        <option value="Sick Leave" <?php echo $type_filter == 'Sick Leave' ? 'selected' : ''; ?>>Sick Leave</option>
                                                        <option value="Casual Leave" <?php echo $type_filter == 'Casual Leave' ? 'selected' : ''; ?>>Casual Leave</option>
                                                        <option value="Leave Without Pay" <?php echo $type_filter == 'Leave Without Pay' ? 'selected' : ''; ?>>Leave Without Pay</option>
                                                        <option value="Adjustment Leave" <?php echo $type_filter == 'Adjustment Leave' ? 'selected' : ''; ?>>Adjustment Leave</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col2 left mb-3 d-inline-block">
                                                <button type="submit" name="search_by_id" class="btn btn-primary">
                                                    <i class="bi bi-filter"></i>&nbsp;Search
                                                </button>
                                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                                                    <i class="bi bi-arrow-clockwise"></i>&nbsp;Reset Filters
                                                </a>
                                            </div>
                                            <!-- Checkbox to toggle Leave ID filter -->
                                            <div class="form-check d-inline-block me-2">
                                                <input class="form-check-input" type="checkbox" name="enable_leave_id" id="enable_leave_id"
                                                    <?php echo !empty($leave_id) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="enable_leave_id">Search by Leave ID</label>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="col-md-3 text-end">
                                        <button id="bulk-review-button" class="btn btn-primary" disabled>Bulk Review (0)</button>
                                    </div>
                                </div>

                                <!-- Bulk Review Modal -->
                                <div class="modal fade" id="bulkReviewModal" tabindex="-1" aria-labelledby="bulkReviewModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="bulkReviewModalLabel">Bulk Review</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form id="bulk-review-form" action="" method="POST">
                                                    <input type="hidden" name="form-type" value="bulkapproval">
                                                    <div class="mb-3">
                                                        <label for="bulk-status" class="form-label">Status</label>
                                                        <select name="bulk_status" id="bulk-status" class="form-select" required>
                                                            <option disabled selected hidden>Select Status</option>
                                                            <option value="Approved">Approved</option>
                                                            <option value="Under review">Under review</option>
                                                            <option value="Rejected">Rejected</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="bulk_comment" class="form-label">Remarks</label>
                                                        <textarea name="bulk_comment" id="bulk_comment" class="form-control" placeholder="Remarks"></textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">Apply</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <form id="main-form" method="POST" action="">
                                    <input type="hidden" name="form-type" value="bulkapproval">

                                    <div class="table-responsive">
                                        <table class="table" id="table-id">
                                            <thead>
                                                <tr>
                                                    <th class="checkbox-cell"></th>
                                                    <th>Leave ID</th>
                                                    <th>Applicant</th>
                                                    <th>Applied On</th>
                                                    <th>From - To Date</th>
                                                    <th>Days</th>
                                                    <th>Leave Details</th>
                                                    <th>Applied by</th>
                                                    <th>Status</th>
                                                    <th>Reviewer</th>
                                                    <th width="15%">Remarks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($resultArr)) { ?>
                                                    <?php foreach ($resultArr as $array) { ?>
                                                        <tr>
                                                            <td class="checkbox-cell">
                                                                <input
                                                                    type="checkbox"
                                                                    class="form-check-input leave-checkbox"
                                                                    name="leave_ids[]"
                                                                    value="<?php echo $array['leaveid']; ?>"
                                                                    <?php echo ($array['status'] === 'Approved' || $array['status'] === 'Rejected') ? 'disabled' : ''; ?>>
                                                            </td>
                                                            <td>
                                                                <?php if ($array['doc'] != null): ?>
                                                                    <a href="javascript:void(0)" onclick="showpdf('<?php echo $array['docp']; ?>')">
                                                                        <?php echo $array['leaveid']; ?>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <?php echo $array['leaveid']; ?>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo $array['applicantid'] . '<br>' . $array['applicant_name']; ?></td>
                                                            <td><?php echo date("d/m/Y g:i a", strtotime($array['timestamp'])); ?></td>
                                                            <td><?php echo date("d/m/Y", strtotime($array['fromdate'])) . ' - ' . date("d/m/Y", strtotime($array['todate'])); ?></td>
                                                            <td><?php echo $array['days']; ?></td>
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
                                                                <?php echo ($array['appliedby'] === $array['applicantid']) ? 'Self' : 'System';  ?>
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
                                                        </tr>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <tr>
                                                        <td colspan="11">No records found or you are not authorized to view this data.</td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </form>
                            </div>

                            <!-- CALENDAR VIEW SECTION -->
                            <div id="calendar-view-section" style="display: none;">
                                <div class="calendar-filters">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Calendar shows all leaves at a glance</h6>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <small class="text-muted">Click on any date to view leave details</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="calendar-view">
                                    <div class="calendar-header">
                                        <div class="month-selector">
                                            <button class="btn btn-sm btn-outline-secondary" id="prev-month">
                                                <i class="bi bi-chevron-left"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary" id="today-btn">Today</button>
                                            <button class="btn btn-sm btn-outline-secondary" id="next-month">
                                                <i class="bi bi-chevron-right"></i>
                                            </button>
                                            <h5 id="current-month" class="mb-0 mx-2"></h5>

                                            <!-- Month Picker -->
                                            <select class="form-select form-select-sm" id="calendar-month" style="width: auto;">
                                                <option value="0">January</option>
                                                <option value="1">February</option>
                                                <option value="2">March</option>
                                                <option value="3">April</option>
                                                <option value="4">May</option>
                                                <option value="5">June</option>
                                                <option value="6">July</option>
                                                <option value="7">August</option>
                                                <option value="8">September</option>
                                                <option value="9">October</option>
                                                <option value="10">November</option>
                                                <option value="11">December</option>
                                            </select>

                                            <select class="form-select form-select-sm" id="calendar-year" style="width: auto;">
                                                <?php
                                                $currentYear = date('Y');
                                                for ($i = 0; $i <= 5; $i++) {
                                                    $yearOption = $currentYear - $i;
                                                    $selected = ($yearOption == $currentYear) ? 'selected' : '';
                                                    echo "<option value='$yearOption' $selected>$yearOption</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="calendar-nav">
                                            <span id="calendar-stats" class="text-muted small"></span>
                                        </div>
                                    </div>

                                    <div class="calendar-grid" id="calendar-grid">
                                        <div class="calendar-loading" id="calendar-loading">
                                            <div class="spinner-border spinner-border-sm" role="status"></div>
                                            Loading calendar data...
                                        </div>
                                    </div>

                                    <div class="p-3">
                                        <div class="legend">
                                            <div class="legend-item">
                                                <div class="legend-color" style="background-color: #d1e7dd;"></div>
                                                <span>Approved</span>
                                            </div>
                                            <div class="legend-item">
                                                <div class="legend-color" style="background-color: #fff3cd;"></div>
                                                <span>Pending/Under Review</span>
                                            </div>
                                            <div class="legend-item">
                                                <div class="legend-color" style="background-color: #f8d7da;"></div>
                                                <span>Rejected</span>
                                            </div>
                                            <div class="legend-item">
                                                <div class="legend-color" style="background-color: #e7f1ff;"></div>
                                                <span>Multiple Status</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Leave Details Modal -->
                            <div class="modal fade" id="leaveDetailsModal" tabindex="-1" aria-labelledby="leaveDetailsModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="leaveDetailsModalLabel">Leave Details - <span id="modal-date"></span></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div id="leave-details-content">
                                                <!-- Leave details will be populated by JavaScript -->
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <!-- PDF Viewer Modal -->
    <div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-labelledby="pdfViewerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfViewerModalLabel">Document Viewer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe id="pdf-viewer" class="pdf-viewer" src="" sandbox="allow-scripts allow-same-origin"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  

    <script>
        // Calendar functionality
        let currentDate = new Date();
        let leaveData = {};
        let calendarInitialized = false;

        // View type toggle
        document.querySelectorAll('input[name="view-type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const url = new URL(window.location);

                if (this.id === 'view-calendar') {
                    document.getElementById('table-view-section').style.display = 'none';
                    document.getElementById('calendar-view-section').style.display = 'block';
                    url.searchParams.set('view', 'calendar');
                    if (!calendarInitialized) {
                        initializeCalendar();
                        calendarInitialized = true;
                    }
                } else {
                    document.getElementById('table-view-section').style.display = 'block';
                    document.getElementById('calendar-view-section').style.display = 'none';
                    url.searchParams.delete('view');
                }

                // Update URL without page reload
                window.history.replaceState({}, '', url);
            });
        });

        // Set initial view from URL on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const viewParam = urlParams.get('view');

            if (viewParam === 'calendar') {
                document.getElementById('view-calendar').checked = true;
                document.getElementById('table-view-section').style.display = 'none';
                document.getElementById('calendar-view-section').style.display = 'block';
                if (!calendarInitialized) {
                    initializeCalendar();
                    calendarInitialized = true;
                }
            }
            // else table view is already set by default
        });

        // Initialize calendar with current date
        function initializeCalendar() {
            const currentMonth = currentDate.getMonth();
            const currentYear = currentDate.getFullYear();

            // Set month and year pickers to current date
            document.getElementById('calendar-month').value = currentMonth;
            document.getElementById('calendar-year').value = currentYear;

            // Load data for current month
            loadCalendarData();
        }

        // Calendar navigation
        document.getElementById('prev-month').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            updateDatePickers();
            loadCalendarData();
        });

        document.getElementById('next-month').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            updateDatePickers();
            loadCalendarData();
        });

        document.getElementById('today-btn').addEventListener('click', () => {
            currentDate = new Date();
            updateDatePickers();
            loadCalendarData();
        });

        // Month picker change
        document.getElementById('calendar-month').addEventListener('change', function() {
            currentDate.setMonth(parseInt(this.value));
            loadCalendarData();
        });

        // Year picker change
        document.getElementById('calendar-year').addEventListener('change', function() {
            currentDate.setFullYear(parseInt(this.value));
            loadCalendarData();
        });

        // Update date pickers to match currentDate
        function updateDatePickers() {
            document.getElementById('calendar-month').value = currentDate.getMonth();
            document.getElementById('calendar-year').value = currentDate.getFullYear();
        }

        // Load calendar data via AJAX
        function loadCalendarData() {
            const calendarGrid = document.getElementById('calendar-grid');
            const loadingElement = document.getElementById('calendar-loading');
            const statsElement = document.getElementById('calendar-stats');

            // Show loading - create a new loading element instead of moving existing one
            calendarGrid.innerHTML = '<div class="calendar-loading" id="calendar-loading-temp"><div class="spinner-border spinner-border-sm" role="status"></div> Loading calendar data...</div>';
            statsElement.textContent = 'Loading...';

            // Calculate date range for current month view - using local dates
            const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);

            // Extend range by one week on each side for better UX
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - 7);

            const endDate = new Date(lastDay);
            endDate.setDate(endDate.getDate() + 7);

            // Format dates as YYYY-MM-DD for consistent timezone handling
            const formatDateForAPI = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            const dateRange = {
                start: formatDateForAPI(startDate),
                end: formatDateForAPI(endDate),
                month: currentDate.getMonth(),
                year: currentDate.getFullYear()
            };

            // AJAX call to fetch calendar data
            fetch('get_calendar_leaves.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(dateRange)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    leaveData = data.leaves || {};
                    generateCalendar();
                    updateCalendarStats(data.stats || {});
                })
                .catch(error => {
                    console.error('Error loading calendar data:', error);
                    calendarGrid.innerHTML = '<div class="calendar-loading">Error loading calendar data. Please try again.</div>';
                    statsElement.textContent = 'Error loading data';
                });
        }

        function updateCalendarStats(stats) {
            const statsElement = document.getElementById('calendar-stats');
            let statsText = [];

            // Format numbers to show decimals only when needed
            const formatDecimal = (value) => {
                if (value === undefined || value === null) return '0';
                // If it's a whole number, show without decimals, otherwise show 1 decimal
                return value % 1 === 0 ? value.toString() : value.toFixed(1);
            };

            // Use the day-based statistics for current month only (with decimals)
            const totalDays = stats.total_days !== undefined ? stats.total_days : stats.total_leaves;
            const approvedDays = stats.approved_days !== undefined ? stats.approved_days : stats.approved;
            const pendingDays = stats.pending_days !== undefined ? stats.pending_days : stats.pending;

            if (totalDays > 0) {
                statsText.push(`${formatDecimal(totalDays)} days applied`);
            } else {
                statsText.push(`0 days applied`);
            }

            if (approvedDays > 0) {
                statsText.push(`${formatDecimal(approvedDays)} approved`);
            }

            if (pendingDays > 0) {
                statsText.push(`${formatDecimal(pendingDays)} pending`);
            }

            statsElement.textContent = statsText.join(' • ');
        }

        function generateCalendar() {
            const calendarGrid = document.getElementById('calendar-grid');
            const currentMonth = currentDate.getMonth();
            const currentYear = currentDate.getFullYear();

            // Update month header
            const monthNames = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
            ];
            document.getElementById('current-month').textContent =
                `${monthNames[currentMonth]} ${currentYear}`;

            // Clear previous calendar
            calendarGrid.innerHTML = '';

            // Add day headers
            const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            dayHeaders.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'calendar-day-header';
                dayHeader.textContent = day;
                calendarGrid.appendChild(dayHeader);
            });

            // Get first day of month and total days
            const firstDay = new Date(currentYear, currentMonth, 1);
            const lastDay = new Date(currentYear, currentMonth + 1, 0);
            const totalDays = lastDay.getDate();
            const startingDay = firstDay.getDay(); // 0 = Sunday, 1 = Monday, etc.

            // Get the previous month's last day to show trailing days
            const prevMonthLastDay = new Date(currentYear, currentMonth, 0).getDate();

            // Add empty days from previous month (trailing days)
            for (let i = startingDay - 1; i >= 0; i--) {
                const dayNumber = prevMonthLastDay - i;
                const prevMonthDay = new Date(currentYear, currentMonth - 1, dayNumber);
                const dayElement = createDayElement(prevMonthDay, true);
                calendarGrid.appendChild(dayElement);
            }

            // Add current month days
            for (let day = 1; day <= totalDays; day++) {
                const currentDay = new Date(currentYear, currentMonth, day);
                const dayElement = createDayElement(currentDay, false);
                calendarGrid.appendChild(dayElement);
            }

            // Calculate how many empty days needed for next month to complete grid
            const totalCellsDisplayed = startingDay + totalDays;
            const remainingCells = totalCellsDisplayed < 35 ? 35 - totalCellsDisplayed : 42 - totalCellsDisplayed;

            // Add empty days from next month
            for (let day = 1; day <= remainingCells; day++) {
                const nextMonthDay = new Date(currentYear, currentMonth + 1, day);
                const dayElement = createDayElement(nextMonthDay, true);
                calendarGrid.appendChild(dayElement);
            }
        }

        function createDayElement(date, isOtherMonth) {
            const dayElement = document.createElement('div');
            dayElement.className = `calendar-day ${isOtherMonth ? 'other-month' : ''}`;

            // Create date string in local timezone (YYYY-MM-DD format)
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const dateStr = `${year}-${month}-${day}`;

            // Check if today - use local date comparison
            const today = new Date();
            const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

            if (!isOtherMonth && dateStr === todayStr) {
                dayElement.classList.add('today');
            }

            const dayEvents = leaveData[dateStr] || [];

            // Date number
            const dateNumber = document.createElement('div');
            dateNumber.className = 'calendar-date';
            dateNumber.textContent = date.getDate();
            dayElement.appendChild(dateNumber);

            // Events container
            const eventsContainer = document.createElement('div');
            eventsContainer.className = 'calendar-events';

            if (dayEvents.length > 0) {
                // Group events by status
                const eventsByStatus = {};
                dayEvents.forEach(event => {
                    const status = event.status.toLowerCase().replace(' ', '-');
                    if (!eventsByStatus[status]) {
                        eventsByStatus[status] = [];
                    }
                    eventsByStatus[status].push(event);
                });

                const statuses = Object.keys(eventsByStatus);

                if (statuses.length === 1) {
                    // Single status - show all events
                    const status = statuses[0];
                    const events = eventsByStatus[status];

                    if (events.length === 1) {
                        // Single event
                        const eventElement = createEventElement(events[0], status);
                        eventsContainer.appendChild(eventElement);
                    } else {
                        // Multiple events with same status
                        const eventElement = document.createElement('div');
                        eventElement.className = `calendar-event ${status}`;
                        eventElement.innerHTML = `${events.length} leaves <span class="event-count">${events.length}</span>`;
                        eventElement.setAttribute('data-date', dateStr);
                        eventElement.setAttribute('data-status', status);
                        eventsContainer.appendChild(eventElement);
                    }
                } else {
                    // Multiple statuses
                    const eventElement = document.createElement('div');
                    eventElement.className = 'calendar-event multiple';
                    const totalEvents = dayEvents.length;
                    eventElement.innerHTML = `${totalEvents} leaves <span class="event-count">${totalEvents}</span>`;
                    eventElement.setAttribute('data-date', dateStr);
                    eventElement.setAttribute('data-status', 'multiple');
                    eventsContainer.appendChild(eventElement);
                }
            }

            dayElement.appendChild(eventsContainer);

            // Add click handler for day
            if (!isOtherMonth) {
                dayElement.style.cursor = 'pointer';
                dayElement.addEventListener('click', () => {
                    showLeaveDetails(dateStr, dayEvents);
                });
            }

            return dayElement;
        }

        function createEventElement(event, status) {
            const eventElement = document.createElement('div');
            eventElement.className = `calendar-event ${status}`;

            // Shorten name for display
            const shortName = event.applicant_name.length > 12 ?
                event.applicant_name.substring(0, 12) + '...' : event.applicant_name;

            eventElement.textContent = `${shortName} (${event.typeofleave})`;
            eventElement.setAttribute('data-date', event.fromdate);
            eventElement.setAttribute('data-status', status);

            // Add tooltip
            eventElement.setAttribute('title',
                `Applicant: ${event.applicant_name}\nType: ${event.typeofleave}\nStatus: ${event.status}\nDays: ${event.days}\nFrom: ${formatDateForDisplay(event.fromdate)}\nTo: ${formatDateForDisplay(event.todate)}`
            );

            return eventElement;
        }

        function formatDateForDisplay(dateStr) {
            // Parse the date string in local timezone
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                const [year, month, day] = parts;
                return `${day}/${month}/${year}`;
            }
            return dateStr;
        }

        function showLeaveDetails(dateStr, events) {
            const modal = new bootstrap.Modal(document.getElementById('leaveDetailsModal'));
            const modalDate = document.getElementById('modal-date');
            const content = document.getElementById('leave-details-content');

            // Format date for display - parse the YYYY-MM-DD string properly
            const parts = dateStr.split('-');
            let displayDate;
            if (parts.length === 3) {
                const [year, month, day] = parts;
                const date = new Date(year, month - 1, day); // Use local timezone
                displayDate = date.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            } else {
                displayDate = dateStr;
            }

            modalDate.textContent = displayDate;

            if (events.length === 0) {
                content.innerHTML = '<p>No leave requests for this date.</p>';
            } else {
                let html = `<div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Leave ID</th>
                        <th>Applicant</th>
                        <th>Leave Type</th>
                        <th>Period</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>`;

                events.forEach(event => {
                    const statusClass = event.status === 'Approved' ? 'text-success' :
                        event.status === 'Rejected' ? 'text-danger' :
                        event.status === 'Under review' ? 'text-warning' : 'text-secondary';

                    html += `<tr>
                    <td>${event.leaveid}</td>
                <td>${event.applicant_name}<br><small class="text-muted">${event.applicantid}</small></td>
                <td>${event.typeofleave}${event.shift ? ' - ' + event.shift : ''}</td>
                <td>${formatDateForDisplay(event.fromdate)} - ${formatDateForDisplay(event.todate)}</td>
                <td>${event.days}</td>
                <td><span class="${statusClass} fw-bold">${event.status}</span></td>
                <td>${event.comment || '-'}</td>
            </tr>`;
                });

                html += `</tbody></table></div>`;
                content.innerHTML = html;
            }

            modal.show();
        }

        // Make the entire row clickable to toggle the checkbox
        document.querySelectorAll('#table-view-section tbody tr').forEach(row => {
            row.addEventListener('click', (e) => {
                // Check if the click was on a checkbox, link, or button to avoid unwanted toggling
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
                    const checkbox = row.querySelector('td .leave-checkbox');
                    if (checkbox && !checkbox.disabled) {
                        checkbox.checked = !checkbox.checked;

                        // Trigger the change event manually to update the bulk review button
                        checkbox.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }
                }
            });
        });

        $(document).ready(function() {
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    "order": [] // Disable initial sorting
                });
            <?php endif; ?>
        });

        // PDF viewer function
        function showpdf(docUrl) {
            $('#pdf-viewer').attr('src', docUrl);
            $('#pdfViewerModal').modal('show');
        }

        // Function to update the bulk review button
        function updateBulkReviewButton() {
            // Only count checkboxes inside the table
            const selectedCount = document.querySelectorAll('.leave-checkbox:checked').length;
            const bulkReviewButton = document.getElementById('bulk-review-button');
            bulkReviewButton.textContent = `Bulk Review (${selectedCount})`;
            bulkReviewButton.disabled = selectedCount === 0;
        }

        // Attach event listeners to table checkboxes only
        document.querySelectorAll('.leave-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkReviewButton);
        });

        // Initial update of the button
        updateBulkReviewButton();

        // Open the bulk review modal and populate selected IDs
        document.getElementById('bulk-review-button').addEventListener('click', () => {
            const selectedIds = getSelectedIds();
            if (selectedIds.length > 0) {
                // Initialize and show the modal
                const bulkReviewModal = new bootstrap.Modal(document.getElementById('bulkReviewModal'));
                bulkReviewModal.show();
            } else {
                alert('Please select at least one leave request to proceed.');
            }
        });

        // Handle form submission from the modal
        document.getElementById('bulk-review-form').addEventListener('submit', function(e) {
            // Get selected IDs
            const selectedIds = getSelectedIds();

            // Create hidden inputs for each selected ID
            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'leave_ids[]';
                input.value = id;
                this.appendChild(input);
            });

            // The form will now submit with all the necessary data
        });

        // Function to get selected IDs (only from table checkboxes)
        function getSelectedIds() {
            const selectedIds = [];
            document.querySelectorAll('.leave-checkbox:checked').forEach(checkbox => {
                selectedIds.push(checkbox.value);
            });
            return selectedIds;
        }

        // Use event delegation for dynamically created rows
        $('#table-id').on('click', '.more-link', function(e) {
            e.preventDefault();
            var shortComment = $(this).siblings('.short-comment');
            var fullComment = $(this).siblings('.full-comment');
            if (fullComment.is(':visible')) {
                shortComment.show();
                fullComment.hide();
                $(this).text('more');
            } else {
                shortComment.hide();
                fullComment.show();
                $(this).text('less');
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const enableLeaveIdCheckbox = document.getElementById('enable_leave_id');
            const leaveIdInput = document.getElementById('leave_id');
            const applicantIdInput = document.getElementById('applicant_id');
            const lyearSelect = document.getElementById('lyear');
            const statusFilterSelect = document.getElementById('status_filter');
            const typeFilterSelect = document.getElementById('type_filter');

            function toggleFilters() {
                const isLeaveIdEnabled = enableLeaveIdCheckbox.checked;

                // Toggle Leave ID field
                leaveIdInput.disabled = !isLeaveIdEnabled;

                // Toggle other filters
                applicantIdInput.disabled = isLeaveIdEnabled;
                lyearSelect.disabled = isLeaveIdEnabled;
                statusFilterSelect.disabled = isLeaveIdEnabled;
                typeFilterSelect.disabled = isLeaveIdEnabled;

                // Clear other filters when Leave ID is enabled
                if (isLeaveIdEnabled) {
                    applicantIdInput.value = '';
                    // Don't clear lyear as it's required
                    statusFilterSelect.value = '';
                    typeFilterSelect.value = '';
                }
            }

            // Initialize on page load
            toggleFilters();

            // Add event listener for checkbox changes
            enableLeaveIdCheckbox.addEventListener('change', toggleFilters);
        });
    </script>
</body>

</html>