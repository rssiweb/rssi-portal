<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$user_id = $associatenumber;
$user_role = $role;
$user_position = $position; // Added position variable

// Get current academic year
$current_year = date('Y');
$current_month = date('n');
$academic_year = ($current_month >= 4) ? $current_year . '-' . ($current_year + 1) : ($current_year - 1) . '-' . $current_year;
$selected_tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_cleaning'])) {
        // Process form submission for multiple locations
        $locations = $_POST['location'];
        $cleaning_date = pg_escape_string($con, $_POST['cleaning_date']);
        $is_cleaned = isset($_POST['is_cleaned']) ? 'true' : 'false';
        $is_sanitized = isset($_POST['is_sanitized']) ? 'true' : 'false';
        $is_restocked = isset($_POST['is_restocked']) ? 'true' : 'false';
        $issues_found = pg_escape_string($con, $_POST['issues_found']);
        $cleaner_id = pg_escape_string($con, $_POST['cleaner_id']);

        $success_count = 0;

        // Add server-side validation
        if (!isset($_POST['is_cleaned']) && !isset($_POST['is_sanitized']) && !isset($_POST['is_restocked'])) {
            $_SESSION['error_message'] = "Please select at least one option (Cleaned, Sanitized, or Restocked)";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        foreach ($locations as $location) {
            $location = pg_escape_string($con, $location);
            $query = "INSERT INTO washroom_cleaning (
                washroom_location, cleaner_id, cleaning_date, 
                is_cleaned, is_sanitized, is_restocked, 
                issues_found, submitted_by, current_status
            ) VALUES (
                '$location', '$cleaner_id', '$cleaning_date', 
                $is_cleaned, $is_sanitized, $is_restocked, 
                '$issues_found', '$user_id', 'SUBMITTED'
            ) RETURNING id";

            $result = pg_query($con, $query);

            if ($result) {
                $success_count++;
            }
        }

        if ($success_count > 0) {
            $_SESSION['success_message'] = "Submitted $success_count cleaning record(s) successfully!";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $_SESSION['error_message'] = "Error submitting cleaning record: " . pg_last_error($con);
        }
    } elseif (isset($_POST['approve_action'])) {
        // Process approval/rejection (unchanged)
        $cleaning_id = pg_escape_string($con, $_POST['cleaning_id']);
        $comments = pg_escape_string($con, $_POST['comments']);
        $action = pg_escape_string($con, $_POST['approve_action']);
        $approval_level = pg_escape_string($con, $_POST['approval_level']);

        $status = ($action == 'approve') ? 'APPROVED' : 'REJECTED';

        $update_fields = [];
        $next_status = '';

        if ($approval_level == 'level1') {
            // Only Assistant Teachers can approve at level 1
            if ($user_position != 'Assistant Teacher') {
                $_SESSION['error_message'] = "You don't have permission to perform this action";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
            $update_fields = [
                "level1_approver = '$user_id'",
                "level1_approval_date = NOW()",
                "level1_approval_status = '$status'",
                "level1_comments = '$comments'"
            ];
            $next_status = ($status == 'APPROVED') ? 'LEVEL1_APPROVED' : 'REJECTED';
        } elseif ($approval_level == 'level2') {
            // Only Offline Managers can approve at level 2
            if ($user_role != 'Offline Manager') {
                $_SESSION['error_message'] = "You don't have permission to perform this action";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
            $update_fields = [
                "level2_approver = '$user_id'",
                "level2_approval_date = NOW()",
                "level2_approval_status = '$status'",
                "level2_comments = '$comments'"
            ];
            $next_status = ($status == 'APPROVED') ? 'LEVEL2_APPROVED' : 'REJECTED';
        } elseif ($approval_level == 'level3') {
            // Only Admins can approve at level 3
            if ($user_role != 'Admin') {
                $_SESSION['error_message'] = "You don't have permission to perform this action";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
            $update_fields = [
                "level3_approver = '$user_id'",
                "level3_approval_date = NOW()",
                "level3_approval_status = '$status'",
                "level3_comments = '$comments'"
            ];
            $next_status = ($status == 'APPROVED') ? 'FINAL_APPROVED' : 'REJECTED';
        }

        $update_fields[] = "current_status = '$next_status'";
        $update_str = implode(', ', $update_fields);

        // Add condition based on current status
        $condition = '';
        if ($approval_level == 'level1') {
            $condition = "current_status = 'SUBMITTED'";
        } elseif ($approval_level == 'level2') {
            $condition = "current_status = 'LEVEL1_APPROVED'";
        } elseif ($approval_level == 'level3') {
            $condition = "current_status = 'LEVEL2_APPROVED'";
        }

        $query = "UPDATE washroom_cleaning SET $update_str WHERE id = $cleaning_id AND $condition";

        $result = pg_query($con, $query);

        if ($result && pg_affected_rows($result) > 0) {
            $_SESSION['success_message'] = "Approval processed successfully!";
        } else {
            $_SESSION['error_message'] = "Error processing approval: " . pg_last_error($con);
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Function to generate academic year condition for SQL
function getAcademicYearCondition($selected_academic_year, $table_alias = '')
{
    if (empty($selected_academic_year)) {
        return '';
    }

    list($start_year, $end_year) = explode('-', $selected_academic_year);
    $start_year = (int)$start_year;
    $end_year = (int)$end_year;

    $prefix = $table_alias ? "$table_alias." : '';

    return " AND (
        (EXTRACT(MONTH FROM {$prefix}cleaning_date) >= 4 
        AND EXTRACT(YEAR FROM {$prefix}cleaning_date) = $start_year)
        OR
        (EXTRACT(MONTH FROM {$prefix}cleaning_date) < 4 
        AND EXTRACT(YEAR FROM {$prefix}cleaning_date) = $end_year)
    )";
}

// Get cleaning records based on user role and status
function getCleaningRecords($con, $status, $user_id, $user_role, $user_position, $selected_academic_year = null)
{
    $query = "SELECT wc.*, 
              a.fullname as cleaner_name,
              s.fullname as submitted_by_name,
              l1.fullname as level1_approver_name,
              l2.fullname as level2_approver_name,
              l3.fullname as level3_approver_name
              FROM washroom_cleaning wc
              LEFT JOIN rssimyaccount_members a ON wc.cleaner_id = a.associatenumber
              LEFT JOIN rssimyaccount_members s ON wc.submitted_by = s.associatenumber
              LEFT JOIN rssimyaccount_members l1 ON wc.level1_approver = l1.associatenumber
              LEFT JOIN rssimyaccount_members l2 ON wc.level2_approver = l2.associatenumber
              LEFT JOIN rssimyaccount_members l3 ON wc.level3_approver = l3.associatenumber
              WHERE wc.current_status = '$status'";

    // Add academic year filter
    if ($selected_academic_year) {
        $query .= getAcademicYearCondition($selected_academic_year, 'wc');
    }

    // For approvers, only show records they need to approve based on role/position
    if ($status == 'SUBMITTED' && $user_position == 'Assistant Teacher') {
        $query .= " AND wc.level1_approver IS NULL";
    } elseif ($status == 'LEVEL1_APPROVED' && $user_role == 'Offline Manager') {
        $query .= " AND wc.level2_approver IS NULL";
    } elseif ($status == 'LEVEL2_APPROVED' && $user_role == 'Admin') {
        $query .= " AND wc.level3_approver IS NULL";
    }

    $query .= " ORDER BY wc.cleaning_date DESC";

    return pg_query($con, $query);
}

// Get cleaners for dropdown
$cleaners_result = pg_query($con, "SELECT associatenumber, fullname FROM rssimyaccount_members WHERE position = 'Housekeeping Attendant' ORDER BY fullname");
$cleaners = [];
while ($row = pg_fetch_assoc($cleaners_result)) {
    $cleaners[$row['associatenumber']] = $row['fullname'];
}

// Get available academic years for filter
$academic_years_result = pg_query(
    $con,
    "SELECT DISTINCT 
        CASE 
            WHEN EXTRACT(MONTH FROM cleaning_date) >= 4 
            THEN EXTRACT(YEAR FROM cleaning_date)::text || '-' || (EXTRACT(YEAR FROM cleaning_date) + 1)::text
            ELSE (EXTRACT(YEAR FROM cleaning_date) - 1)::text || '-' || EXTRACT(YEAR FROM cleaning_date)::text
        END AS academic_year
     FROM washroom_cleaning 
     ORDER BY academic_year DESC"
);

if (!$academic_years_result) {
    die("Error in academic year query: " . pg_last_error($con));
}

$academic_years = [];
while ($row = pg_fetch_assoc($academic_years_result)) {
    $academic_years[] = $row['academic_year'];
}

// Ensure current academic year is in the list if no records exist yet
if (!in_array($academic_year, $academic_years)) {
    array_unshift($academic_years, $academic_year);
}

// Get selected academic year from filter
$selected_academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : $academic_year;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Hygiene Monitoring</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .status-submitted {
            background-color: #ffc107;
            color: #000;
        }

        .status-level1 {
            background-color: #fd7e14;
            color: #fff;
        }

        .status-level2 {
            background-color: #0d6efd;
            color: #fff;
        }

        .status-approved {
            background-color: #198754;
            color: #fff;
        }

        .status-rejected {
            background-color: #dc3545;
            color: #fff;
        }

        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #0d6efd;
        }

        .cleaning-card {
            border-left: 4px solid #0d6efd;
            transition: all 0.3s ease;
        }

        .cleaning-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .bg-orange {
            background-color: #fd7e14;
        }

        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .help-link {
            cursor: pointer;
            color: #0d6efd;
            text-decoration: underline;
            margin-left: 10px;
            font-size: 0.9rem;
        }

        .help-link:hover {
            color: #0b5ed7;
        }
    </style>
</head>

<body>

    <body>
        <?php include 'inactive_session_expire_check.php'; ?>
        <?php include 'header.php'; ?>

        <main id="main" class="main">

            <div class="pagetitle">
                <h1>FHM</h1>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">Work</a></li>
                        <li class="breadcrumb-item active">Facility Hygiene Monitoring</li>
                    </ol>
                </nav>
            </div><!-- End Page Title -->

            <section class="section dashboard">
                <div class="row">

                    <!-- Reports -->
                    <div class="col-12">
                        <div class="card">

                            <div class="card-body">
                                <!-- <br> -->
                                <div class="container-fluid py-4">
                                    <div class="row mb-4">
                                        <div class="col">

                                            <?php if (isset($_SESSION['success_message'])): ?>
                                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                    <?= $_SESSION['success_message'] ?>
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                </div>
                                                <?php unset($_SESSION['success_message']); ?>
                                            <?php endif; ?>

                                            <?php if (isset($_SESSION['error_message'])): ?>
                                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                    <?= $_SESSION['error_message'] ?>
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                </div>
                                                <?php unset($_SESSION['error_message']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Right Side: Help Link -->
                                        <div class="text-end">
                                            <span class="help-link" data-bs-toggle="modal" data-bs-target="#helpModal" title="Click to learn how the Washroom Cleaning Tracker works">
                                                <i class="bi bi-question-circle"></i> How it works
                                            </span>
                                        </div>
                                        <div class="col-md-4 mb-4">
                                            <div class="card shadow-sm">
                                                <div class="card-header bg-primary text-white mb-3">
                                                    <h5 class="card-title text-light mb-0"><i class="bi bi-plus-circle"></i> New Cleaning Record</h5>
                                                </div>
                                                <div class="card-body">
                                                    <form method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>" id="cleaningForm" onsubmit="return validateCheckboxes()">
                                                        <div class="mb-3">
                                                            <label for="location" class="form-label">Facility Location</label>
                                                            <select id="location" name="location[]" multiple required>
                                                                <option value="Ground Floor - Student Washroom">Ground Floor - Student Washroom</option>
                                                                <option value="Ground Floor - Teacher Washroom">Ground Floor - Teacher Washroom</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="cleaner_id" class="form-label">Cleaner</label>
                                                            <select class="form-select" id="cleaner_id" name="cleaner_id" required>
                                                                <option value="">Select cleaner</option>
                                                                <?php foreach ($cleaners as $id => $name): ?>
                                                                    <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($name) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="cleaning_date" class="form-label">Cleaning Date</label>
                                                            <input type="date" class="form-control" id="cleaning_date" name="cleaning_date" required>
                                                        </div>

                                                        <div class="mb-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="is_cleaned" name="is_cleaned">
                                                                <label class="form-check-label" for="is_cleaned">Cleaned</label>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="is_sanitized" name="is_sanitized">
                                                                <label class="form-check-label" for="is_sanitized">Sanitized</label>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="is_restocked" name="is_restocked">
                                                                <label class="form-check-label" for="is_restocked">Restocked</label>
                                                            </div>
                                                        </div>

                                                        <div id="checkboxError" class="alert alert-danger d-none mb-3">
                                                            Please select at least one option (Cleaned, Sanitized, or Restocked)
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="issues_found" class="form-label">Issues Found (if any)</label>
                                                            <textarea class="form-control" id="issues_found" name="issues_found" rows="2"></textarea>
                                                        </div>

                                                        <button type="submit" name="submit_cleaning" class="btn btn-primary w-100">
                                                            <i class="bi bi-save"></i> Submit Cleaning Record
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-8">
                                            <div class="filter-section mb-3">
                                                <form method="GET" action="<?= $_SERVER['PHP_SELF'] ?>" class="row g-2">
                                                    <div class="col-md-4">
                                                        <label for="academic_year" class="form-label">Academic Year</label>
                                                        <select class="form-select" id="academic_year" name="academic_year" onchange="this.form.submit()">
                                                            <?php foreach ($academic_years as $year): ?>
                                                                <option value="<?= $year ?>" <?= $selected_academic_year == $year ? 'selected' : '' ?>><?= $year ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <!-- Add hidden input to preserve tab parameter -->
                                                    <input type="hidden" name="tab" value="<?= htmlspecialchars($selected_tab) ?>">
                                                </form>
                                            </div>

                                            <ul class="nav nav-tabs" id="cleaningTabs" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link <?= $selected_tab == 'pending' ? 'active' : '' ?>" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="<?= $selected_tab == 'pending' ? 'true' : 'false' ?>">
                                                        Pending Approval <span class="badge bg-warning ms-1">
                                                            <?php
                                                            $count_query = "SELECT COUNT(*) FROM washroom_cleaning WHERE current_status = 'SUBMITTED'";
                                                            if ($selected_academic_year) {
                                                                $count_query .= getAcademicYearCondition($selected_academic_year);
                                                            }
                                                            $result = pg_query($con, $count_query);
                                                            echo $result ? pg_fetch_result($result, 0, 0) : "0";
                                                            ?>
                                                        </span>
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link <?= $selected_tab == 'level1' ? 'active' : '' ?>" id="level1-tab" data-bs-toggle="tab" data-bs-target="#level1" type="button" role="tab" aria-controls="level1" aria-selected="<?= $selected_tab == 'level1' ? 'true' : 'false' ?>">
                                                        Level 1 Approved <span class="badge bg-orange ms-1">
                                                            <?php
                                                            $count_query = "SELECT COUNT(*) FROM washroom_cleaning WHERE current_status = 'LEVEL1_APPROVED'";
                                                            if ($selected_academic_year) {
                                                                $count_query .= getAcademicYearCondition($selected_academic_year);
                                                            }
                                                            $result = pg_query($con, $count_query);
                                                            echo $result ? pg_fetch_result($result, 0, 0) : "0";
                                                            ?>
                                                        </span>
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link <?= $selected_tab == 'level2' ? 'active' : '' ?>" id="level2-tab" data-bs-toggle="tab" data-bs-target="#level2" type="button" role="tab" aria-controls="level2" aria-selected="<?= $selected_tab == 'level2' ? 'true' : 'false' ?>">
                                                        Level 2 Approved <span class="badge bg-primary ms-1">
                                                            <?php
                                                            $count_query = "SELECT COUNT(*) FROM washroom_cleaning WHERE current_status = 'LEVEL2_APPROVED'";
                                                            if ($selected_academic_year) {
                                                                $count_query .= getAcademicYearCondition($selected_academic_year);
                                                            }
                                                            $result = pg_query($con, $count_query);
                                                            echo $result ? pg_fetch_result($result, 0, 0) : "0";
                                                            ?>
                                                        </span>
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link <?= $selected_tab == 'completed' ? 'active' : '' ?>" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab" aria-controls="completed" aria-selected="<?= $selected_tab == 'completed' ? 'true' : 'false' ?>" data-load-on-click="true">
                                                        Completed <span class="badge bg-success ms-1">
                                                            <?php
                                                            $count_query = "SELECT COUNT(*) FROM washroom_cleaning WHERE current_status IN ('FINAL_APPROVED', 'REJECTED')";
                                                            if ($selected_academic_year) {
                                                                $count_query .= getAcademicYearCondition($selected_academic_year);
                                                            }
                                                            $result = pg_query($con, $count_query);
                                                            echo $result ? pg_fetch_result($result, 0, 0) : "0";
                                                            ?>
                                                        </span>
                                                    </button>
                                                </li>
                                            </ul>

                                            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="cleaningTabsContent">
                                                <div class="tab-pane fade <?= $selected_tab == 'pending' ? 'show active' : '' ?>" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                                                    <h5 class="mb-3">Pending Approval</h5>
                                                    <?php
                                                    $records = getCleaningRecords($con, 'SUBMITTED', $user_id, $user_role, $position, $selected_academic_year);
                                                    displayCleaningRecords($records, ($position == 'Assistant Teacher') ? 'level1' : null);
                                                    ?>
                                                </div>

                                                <div class="tab-pane fade <?= $selected_tab == 'level1' ? 'show active' : '' ?>" id="level1" role="tabpanel" aria-labelledby="level1-tab">
                                                    <h5 class="mb-3">Level 1 Approved</h5>
                                                    <?php
                                                    $records = getCleaningRecords($con, 'LEVEL1_APPROVED', $user_id, $user_role, $position, $selected_academic_year);
                                                    displayCleaningRecords($records, ($user_role == 'Offline Manager') ? 'level2' : null);
                                                    ?>
                                                </div>

                                                <div class="tab-pane fade <?= $selected_tab == 'level2' ? 'show active' : '' ?>" id="level2" role="tabpanel" aria-labelledby="level2-tab">
                                                    <h5 class="mb-3">Level 2 Approved</h5>
                                                    <?php
                                                    $records = getCleaningRecords($con, 'LEVEL2_APPROVED', $user_id, $user_role, $position, $selected_academic_year);
                                                    displayCleaningRecords($records, ($user_role == 'Admin') ? 'level3' : null);
                                                    ?>
                                                </div>

                                                <div class="tab-pane fade <?= $selected_tab == 'completed' ? 'show active' : '' ?>" id="completed" role="tabpanel" aria-labelledby="completed-tab">
                                                    <h5 class="mb-3">Completed Cleanings</h5>
                                                    <div id="completed-content">
                                                        <?php if ($selected_tab == 'completed'): ?>
                                                            <?php
                                                            $records = pg_query($con, "SELECT wc.*, 
                                                                a.fullname as cleaner_name,
                                                                s.fullname as submitted_by_name,
                                                                l1.fullname as level1_approver_name,
                                                                l2.fullname as level2_approver_name,
                                                                l3.fullname as level3_approver_name
                                                                FROM washroom_cleaning wc
                                                                LEFT JOIN rssimyaccount_members a ON wc.cleaner_id = a.associatenumber
                                                                LEFT JOIN rssimyaccount_members s ON wc.submitted_by = s.associatenumber
                                                                LEFT JOIN rssimyaccount_members l1 ON wc.level1_approver = l1.associatenumber
                                                                LEFT JOIN rssimyaccount_members l2 ON wc.level2_approver = l2.associatenumber
                                                                LEFT JOIN rssimyaccount_members l3 ON wc.level3_approver = l3.associatenumber
                                                                WHERE wc.current_status IN ('FINAL_APPROVED', 'REJECTED')" .
                                                                ($selected_academic_year ? getAcademicYearCondition($selected_academic_year, 'wc') : '') .
                                                                " ORDER BY wc.cleaning_date DESC");
                                                            displayCleaningRecords($records, null);
                                                            ?>
                                                        <?php else: ?>
                                                            <div class="text-center py-4">
                                                                <div class="spinner-border text-primary" role="status">
                                                                    <span class="visually-hidden">Loading...</span>
                                                                </div>
                                                                <p class="mt-2">Loading completed records...</p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
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

        <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

        <!-- Approval Modal -->
        <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>">
                        <input type="hidden" id="cleaning_id" name="cleaning_id">
                        <input type="hidden" id="approval_level" name="approval_level">
                        <div class="modal-header">
                            <h5 class="modal-title" id="approvalModalLabel">Process Approval</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="approve_action" class="form-label">Action</label>
                                <select class="form-select" id="approve_action" name="approve_action" required>
                                    <option value="">Select action</option>
                                    <option value="approve">Approve</option>
                                    <option value="reject">Reject</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="comments" class="form-label">Comments</label>
                                <textarea class="form-control" id="comments" name="comments" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Modal -->
        <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="helpModalLabel"><i class="bi bi-info-circle"></i> How the Facility Hygiene Monitoring Works</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="workflow-step">
                            <h5>1. Submission</h5>
                            <p>Any user can submit new cleaning records by filling out the form on the left.</p>
                            <p>You can select multiple facility locations at once using the multi-select dropdown.</p>
                            <p>Submitted records appear in the <span class="badge bg-warning">Pending Approval</span> tab.</p>
                        </div>

                        <div class="workflow-step">
                            <h5>2. Approval Process</h5>
                            <p>The cleaning records go through a 3-level approval process:</p>

                            <div class="ms-3 mb-3">
                                <p><span class="approval-level">Level 1 Approval</span> - Can be processed by <strong>Assistant Teachers</strong> only</p>
                                <p><span class="approval-level">Level 2 Approval</span> - Can be processed by <strong>Offline Managers</strong> only</p>
                                <p><span class="approval-level">Level 3 Approval</span> - Can be processed by <strong>Admins</strong> only</p>
                            </div>

                            <p>At each level, approvers can either approve (moving to next level) or reject (ending the workflow).</p>
                        </div>

                        <div class="workflow-step">
                            <h5>3. Final Status</h5>
                            <p>After all approvals are complete, records move to the <span class="badge bg-success">Completed</span> tab.</p>
                            <p>If rejected at any level, records immediately go to the <span class="badge bg-danger">Completed</span> tab with Rejected status.</p>
                        </div>

                        <div class="alert alert-info">
                            <strong>Note:</strong> All users can view all tabs to track progress, but only authorized users can perform approvals at each level.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it!</button>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
        <!-- Template Main JS File -->
        <script src="../assets_new/js/main.js"></script>
        <script>
            // Initialize Select2 for multi-select
            $(document).ready(function() {
                $('#location').select2({
                    placeholder: "Select facility location(s)",
                    width: '100%'
                    //allowClear: true
                });

                // Set today's date as default
                document.getElementById('cleaning_date').valueAsDate = new Date();
            });

            function showApprovalModal(cleaningId, level) {
                document.getElementById('cleaning_id').value = cleaningId;
                document.getElementById('approval_level').value = level;

                let modalTitle = '';
                if (level === 'level1') {
                    modalTitle = 'Level 1 Approval';
                } else if (level === 'level2') {
                    modalTitle = 'Level 2 Approval';
                } else if (level === 'level3') {
                    modalTitle = 'Final Approval';
                }

                document.getElementById('approvalModalLabel').textContent = modalTitle;

                // Reset form
                document.getElementById('approve_action').value = '';
                document.getElementById('comments').value = '';

                const modal = new bootstrap.Modal(document.getElementById('approvalModal'));
                modal.show();
            }
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
                const urlParams = new URLSearchParams(window.location.search);
                const initialTab = urlParams.get('tab');
                const academicYear = urlParams.get('academic_year');

                // Activate initial tab if specified in URL
                if (initialTab) {
                    const tabToActivate = document.querySelector(`[data-bs-target="#${initialTab}"]`);
                    if (tabToActivate) {
                        new bootstrap.Tab(tabToActivate).show();
                    }
                }

                // Update URL when tabs are changed
                tabLinks.forEach(tabLink => {
                    tabLink.addEventListener('shown.bs.tab', function(event) {
                        const tabName = event.target.getAttribute('data-bs-target').replace('#', '');
                        updateUrlParameters(tabName);

                        // Load completed tab content if it's the active tab
                        if (tabName === 'completed') {
                            loadCompletedData();
                        }
                    });
                });

                function updateUrlParameters(tabName) {
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabName);

                    // Preserve academic_year if it exists
                    if (academicYear) {
                        url.searchParams.set('academic_year', academicYear);
                    }

                    window.history.replaceState({}, '', url);
                }

                // Initialize with correct parameters on page load
                if (initialTab || academicYear) {
                    updateUrlParameters(initialTab || 'pending');
                }

                // Load completed data if completed tab is active on page load
                if (initialTab === 'completed') {
                    loadCompletedData();
                }
            });

            function loadCompletedData() {
                const academicYear = document.getElementById('academic_year').value;
                const contentDiv = document.getElementById('completed-content');

                // Only load if content hasn't been loaded yet
                if (contentDiv.dataset.loaded === 'true') {
                    return;
                }

                // Show loading state
                contentDiv.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading completed records...</p>
                    </div>
                `;

                // Fetch data via AJAX
                fetch('get_completed_data.php?academic_year=' + encodeURIComponent(academicYear))
                    .then(response => response.text())
                    .then(data => {
                        contentDiv.innerHTML = data;
                        contentDiv.dataset.loaded = 'true';
                    })
                    .catch(error => {
                        contentDiv.innerHTML = `
                            <div class="alert alert-danger">
                                Error loading completed records. Please try again.
                            </div>
                        `;
                        console.error('Error:', error);
                    });
            }
        </script>
        <script>
            function validateCheckboxes() {
                const isCleaned = document.getElementById('is_cleaned').checked;
                const isSanitized = document.getElementById('is_sanitized').checked;
                const isRestocked = document.getElementById('is_restocked').checked;
                const errorDiv = document.getElementById('checkboxError');

                if (!isCleaned && !isSanitized && !isRestocked) {
                    errorDiv.classList.remove('d-none');
                    return false; // Prevent form submission
                }

                errorDiv.classList.add('d-none');
                return true; // Allow form submission
            }

            // Also add event listeners to hide error when any checkbox is checked
            document.addEventListener('DOMContentLoaded', function() {
                const checkboxes = document.querySelectorAll('.form-check-input');
                const errorDiv = document.getElementById('checkboxError');

                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        if (this.checked) {
                            errorDiv.classList.add('d-none');
                        }
                    });
                });
            });
        </script>
    </body>

</html>

<?php
function displayCleaningRecords($records, $approval_level)
{
    if (pg_num_rows($records) == 0) {
        echo '<div class="alert alert-info">No records found.</div>';
        return;
    }

    while ($row = pg_fetch_assoc($records)) {
        $status_class = '';
        $status_text = '';

        switch ($row['current_status']) {
            case 'SUBMITTED':
                $status_class = 'status-submitted';
                $status_text = 'Submitted';
                break;
            case 'LEVEL1_APPROVED':
                $status_class = 'status-level1';
                $status_text = 'Level 1 Approved';
                break;
            case 'LEVEL2_APPROVED':
                $status_class = 'status-level2';
                $status_text = 'Level 2 Approved';
                break;
            case 'FINAL_APPROVED':
                $status_class = 'status-approved';
                $status_text = 'Approved';
                break;
            case 'REJECTED':
                $status_class = 'status-rejected';
                $status_text = 'Rejected';
                break;
        }

        echo '<div class="card mb-3 cleaning-card">';
        echo '<div class="card-body">';
        echo '<div class="d-flex justify-content-between align-items-start">';
        echo '<div>';
        echo '<h5 class="card-title">' . htmlspecialchars($row['washroom_location']) . '</h5>';
        echo '<p class="card-text mb-1"><small class="text-muted">Cleaned by: ' . htmlspecialchars($row['cleaner_name']) . '</small></p>';
        echo '<p class="card-text mb-1"><small class="text-muted">Date: ' . date('d M Y', strtotime($row['cleaning_date'])) . '</small></p>';
        echo '</div>';
        echo '<span class="badge ' . $status_class . '">' . $status_text . '</span>';
        echo '</div>';

        // Display cleaning details only if checked
        echo '<div class="row mt-2">';
        echo '<div class="col-md-6">';
        if ($row['is_cleaned'] == 't') {
            echo '<p class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Cleaned</p>';
        }
        if ($row['is_sanitized'] == 't') {
            echo '<p class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Sanitized</p>';
        }
        if ($row['is_restocked'] == 't') {
            echo '<p class="mb-1"><i class="bi bi-check-circle-fill text-success"></i> Restocked</p>';
        }
        echo '</div>';
        echo '<div class="col-md-6">';
        if (!empty($row['issues_found'])) {
            echo '<div class="alert alert-warning p-2 mb-0">';
            echo '<strong>Issues:</strong> ' . htmlspecialchars($row['issues_found']);
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';

        // Display approval information if available
        if (!empty($row['level1_approver_name'])) {
            echo '<hr class="my-2">';
            echo '<div class="row">';
            echo '<div class="col">';
            echo '<p class="mb-1 small"><strong>Level 1 Approval:</strong> ' . htmlspecialchars($row['level1_approver_name']) . ' on ' . date('d M Y H:i', strtotime($row['level1_approval_date'])) . '</p>';
            if (!empty($row['level1_comments'])) {
                echo '<p class="mb-0 small text-muted">' . htmlspecialchars($row['level1_comments']) . '</p>';
            }
            echo '</div>';
            echo '</div>';
        }

        if (!empty($row['level2_approver_name'])) {
            echo '<div class="row">';
            echo '<div class="col">';
            echo '<p class="mb-1 small"><strong>Level 2 Approval:</strong> ' . htmlspecialchars($row['level2_approver_name']) . ' on ' . date('d M Y H:i', strtotime($row['level2_approval_date'])) . '</p>';
            if (!empty($row['level2_comments'])) {
                echo '<p class="mb-0 small text-muted">' . htmlspecialchars($row['level2_comments']) . '</p>';
            }
            echo '</div>';
            echo '</div>';
        }

        if (!empty($row['level3_approver_name'])) {
            echo '<div class="row">';
            echo '<div class="col">';
            echo '<p class="mb-1 small"><strong>Final Approval:</strong> ' . htmlspecialchars($row['level3_approver_name']) . ' on ' . date('d M Y H:i', strtotime($row['level3_approval_date'])) . '</p>';
            if (!empty($row['level3_comments'])) {
                echo '<p class="mb-0 small text-muted">' . htmlspecialchars($row['level3_comments']) . '</p>';
            }
            echo '</div>';
            echo '</div>';
        }

        // Display approval button if needed
        if ($approval_level) {
            echo '<hr class="my-2">';
            echo '<div class="d-flex justify-content-end">';
            echo '<button type="button" class="btn btn-sm btn-primary" onclick="showApprovalModal(' . $row['id'] . ', \'' . $approval_level . '\')">';
            echo '<i class="bi bi-clipboard-check"></i> Process Approval';
            echo '</button>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }
}
?>