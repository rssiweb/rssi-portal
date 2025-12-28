<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

// Start output buffering to prevent header issues
ob_start();

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

// Check for active tab
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], ['hrms', 'student']) ? $_GET['tab'] : 'hrms';

// Handle bulk actions for HRMS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action_type'])) {
    if ($_POST['bulk_action_type'] === 'hrms' && isset($_POST['bulk_action'], $_POST['selected_ids'], $_POST['bulk_remarks'])) {
        $selected_ids = explode(',', $_POST['selected_ids']);
        $bulk_action = $_POST['bulk_action'];
        $bulk_remarks = trim($_POST['bulk_remarks']);
        $reviewer_id = $associatenumber;

        if (!empty($selected_ids) && !empty($bulk_action)) {
            // Begin transaction
            pg_query($con, "BEGIN");

            try {
                // Group requests by email
                $emailGroups = [];

                // Process each selected ID
                foreach ($selected_ids as $workflow_id) {
                    $workflow_id = pg_escape_string($con, trim($workflow_id));

                    // Get the basic workflow details
                    $details_query = "SELECT 
                        w.associatenumber, 
                        w.fieldname, 
                        w.submitted_value, 
                        m.fullname, 
                        m.email
                    FROM hrms_workflow w
                    JOIN rssimyaccount_members m ON w.associatenumber = m.associatenumber
                    WHERE w.workflow_id = '$workflow_id'";

                    $details_result = pg_query($con, $details_query);
                    $details_row = pg_fetch_assoc($details_result);

                    if ($details_row) {
                        $associatenumber = $details_row['associatenumber'];
                        $fieldname = $details_row['fieldname'];
                        $submitted_value = $details_row['submitted_value'];
                        $requestedby_name = $details_row['fullname'];
                        $requestedby_email = $details_row['email'];
                        $requested_on = date('d/m/Y h:i A');

                        // Get the current value
                        $currentValueQuery = "SELECT $fieldname AS current_value 
                                            FROM rssimyaccount_members 
                                            WHERE associatenumber = '$associatenumber'";
                        $currentValueResult = pg_query($con, $currentValueQuery);
                        $currentValueRow = pg_fetch_assoc($currentValueResult);
                        $current_value = $currentValueRow['current_value'] ?? null;

                        // Update workflow status
                        $update_query = "UPDATE hrms_workflow
                            SET reviewer_status = '$bulk_action', 
                                reviewer_id = '$reviewer_id', 
                                reviewed_on = NOW(),
                                remarks = '$bulk_remarks'
                            WHERE workflow_id = '$workflow_id'";

                        $update_result = pg_query($con, $update_query);

                        if ($bulk_action === 'Approved' && $update_result) {
                            // Update member record if approved
                            $fieldname_escaped = pg_escape_string($con, $fieldname);
                            $member_update = "UPDATE rssimyaccount_members 
                                SET $fieldname_escaped = '$submitted_value' 
                                WHERE associatenumber = '$associatenumber'";
                            pg_query($con, $member_update);
                        }

                        // Add to email groups instead of sending immediately
                        if (!empty($requestedby_email)) {
                            if (!isset($emailGroups[$requestedby_email])) {
                                $emailGroups[$requestedby_email] = [
                                    'name' => $requestedby_name,
                                    'requests' => []
                                ];
                            }

                            $emailGroups[$requestedby_email]['requests'][] = [
                                'fieldname' => $fieldname,
                                'oldvalue' => $current_value,
                                'newvalue' => $submitted_value,
                                'requested_on' => $requested_on
                            ];
                        }
                    }
                }

                // Commit transaction
                pg_query($con, "COMMIT");

                // Send grouped emails
                foreach ($emailGroups as $email => $group) {
                    // Build HTML table for all requests
                    $tableRows = '';
                    foreach ($group['requests'] as $request) {
                        $tableRows .= "
                        <tr>
                            <td style='border: 1px solid #ddd; padding: 8px;'>{$request['fieldname']}</td>
                            <td style='border: 1px solid #ddd; padding: 8px;'>{$request['oldvalue']}</td>
                            <td style='border: 1px solid #ddd; padding: 8px;'>{$request['newvalue']}</td>
                        </tr>";
                    }

                    // Determine table headers based on action type
                    $valueHeader1 = ($bulk_action === 'Approved') ? 'Previous Value' : 'Current Value';
                    $valueHeader2 = ($bulk_action === 'Approved') ? 'Updated Value' : 'Requested Change';

                    // Construct the full table HTML
                    $fullTable = "
                    <table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>
                    <tr>
                        <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Field Name</th>
                        <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>{$valueHeader1}</th>
                        <th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>{$valueHeader2}</th>
                    </tr>
                    {$tableRows}
                    </table>";

                    // Prepare data for the existing email template
                    $emailData = [
                        "requested_by_name" => $group['name'],
                        "requested_on" => date('d/m/Y h:i A'),
                        "reviewer_status" => $bulk_action,
                        "fieldname" => "Multiple Fields", // Static text
                        "oldvalue" => "", // Leave empty
                        "newvalue" => $fullTable, // Inject the full HTML table
                        "hide_approve" => 'style="display: none;"',
                        "remarks" => $bulk_remarks,
                        "email_subject" => count($group['requests']) > 1
                            ? "HRMS: " . count($group['requests']) . " Requests " . ucfirst($bulk_action)
                            : "HRMS: Your Change Request has been " . ucfirst($bulk_action)
                    ];

                    sendEmail("hrms_review", $emailData, $email, false);
                }

                // Store success message in session
                $_SESSION['bulk_action_status'] = [
                    'status' => 'success',
                    'message' => 'Bulk action completed successfully',
                    'tab' => 'hrms'
                ];

                // Redirect to avoid form resubmission
                header("Location: " . $_SERVER['PHP_SELF'] . "?tab=hrms");
                exit;
            } catch (Exception $e) {
                // Rollback on error
                pg_query($con, "ROLLBACK");

                // Store error message in session
                $_SESSION['bulk_action_status'] = [
                    'status' => 'error',
                    'message' => 'Error processing bulk action: ' . $e->getMessage(),
                    'tab' => 'hrms'
                ];

                // Redirect to avoid form resubmission
                header("Location: " . $_SERVER['PHP_SELF'] . "?tab=hrms");
                exit;
            }
        }
    }
    // Handle bulk actions for Student
    elseif ($_POST['bulk_action_type'] === 'student' && isset($_POST['bulk_action'], $_POST['selected_ids'], $_POST['bulk_remarks'])) {
        $selected_ids = explode(',', $_POST['selected_ids']);
        $bulk_action = $_POST['bulk_action'];
        $bulk_remarks = trim($_POST['bulk_remarks']);
        $reviewer_id = $associatenumber;

        if (!empty($selected_ids) && !empty($bulk_action)) {
            // Begin transaction
            pg_query($con, "BEGIN");

            try {
                // Group requests by student/email if needed
                $emailGroups = [];

                // Process each selected ID
                foreach ($selected_ids as $workflow_id) {
                    $workflow_id = pg_escape_string($con, trim($workflow_id));

                    // Get the student workflow details
                    $details_query = "SELECT 
                        w.*, 
                        s.studentname,
                        s.emailaddress as student_email,
                        a.fullname as submitter_name,
                        a.email as submitter_email
                    FROM student_profile_update_workflow w
                    JOIN rssimyprofile_student s ON w.student_id = s.student_id
                    LEFT JOIN rssimyaccount_members a ON w.submitted_by = a.associatenumber
                    WHERE w.workflow_id = '$workflow_id'";

                    $details_result = pg_query($con, $details_query);
                    $details_row = pg_fetch_assoc($details_result);

                    if ($details_row) {
                        $student_id = $details_row['student_id'];
                        $field_name = $details_row['field_name'];
                        $submitted_value = $details_row['submitted_value'];
                        $student_name = $details_row['studentname'];
                        $student_email = $details_row['student_email'];
                        $submitter_name = $details_row['submitter_name'];
                        $submitter_email = $details_row['submitter_email'];
                        $requested_on = date('d/m/Y h:i A');

                        // Update workflow status
                        $update_query = "UPDATE student_profile_update_workflow
                            SET reviewer_status = '$bulk_action', 
                                reviewer_id = '$reviewer_id', 
                                reviewed_on = NOW(),
                                remarks = '$bulk_remarks'
                            WHERE workflow_id = '$workflow_id'";

                        $update_result = pg_query($con, $update_query);

                        if ($bulk_action === 'Approved' && $update_result) {
                            // Update student record if approved
                            if (strpos($field_name, 'photo') !== false || strpos($field_name, 'document') !== false) {
                                // For file fields, we need to handle differently
                                $student_update = "UPDATE rssimyprofile_student 
                                    SET $field_name = '$submitted_value' 
                                    WHERE student_id = '$student_id'";
                            } else {
                                $field_name_escaped = pg_escape_string($con, $field_name);
                                $submitted_value_escaped = pg_escape_string($con, $submitted_value);
                                $student_update = "UPDATE rssimyprofile_student 
                                    SET $field_name_escaped = '$submitted_value_escaped' 
                                    WHERE student_id = '$student_id'";
                            }
                            pg_query($con, $student_update);
                        }

                        // Group by submitter email for notification
                        $email_to = $submitter_email ?: $student_email;
                        if (!empty($email_to)) {
                            if (!isset($emailGroups[$email_to])) {
                                $emailGroups[$email_to] = [
                                    'name' => $submitter_name ?: $student_name,
                                    'requests' => []
                                ];
                            }

                            $emailGroups[$email_to]['requests'][] = [
                                'student_name' => $student_name,
                                'field_name' => $field_name,
                                'old_value' => $details_row['current_value'],
                                'new_value' => $submitted_value,
                                'requested_on' => $requested_on
                            ];
                        }
                    }
                }

                // Commit transaction
                pg_query($con, "COMMIT");

                // Send notifications (you can implement email sending similar to HRMS)
                foreach ($emailGroups as $email => $group) {
                    // Build notification content
                    $notification = "Dear " . $group['name'] . ",\n\n";
                    $notification .= "Your student profile update requests have been " . strtolower($bulk_action) . ".\n\n";

                    foreach ($group['requests'] as $request) {
                        $notification .= "Student: " . $request['student_name'] . "\n";
                        $notification .= "Field: " . $request['field_name'] . "\n";
                        $notification .= "Status: " . $bulk_action . "\n";
                        if ($bulk_remarks) {
                            $notification .= "Remarks: " . $bulk_remarks . "\n";
                        }
                        $notification .= "---\n";
                    }

                    // You can implement email sending here using your existing email function
                    // sendEmail($email, "Student Profile Updates " . ucfirst($bulk_action), $notification);
                }

                // Store success message in session
                $_SESSION['bulk_action_status'] = [
                    'status' => 'success',
                    'message' => 'Student bulk action completed successfully',
                    'tab' => 'student'
                ];

                // Redirect to avoid form resubmission
                header("Location: " . $_SERVER['PHP_SELF'] . "?tab=student");
                exit;
            } catch (Exception $e) {
                // Rollback on error
                pg_query($con, "ROLLBACK");

                // Store error message in session
                $_SESSION['bulk_action_status'] = [
                    'status' => 'error',
                    'message' => 'Error processing student bulk action: ' . $e->getMessage(),
                    'tab' => 'student'
                ];

                // Redirect to avoid form resubmission
                header("Location: " . $_SERVER['PHP_SELF'] . "?tab=student");
                exit;
            }
        }
    }
}

// Fetch HRMS data
$hrms_query = "
    WITH latest_submissions AS (
        SELECT 
            w.associatenumber,
            w.fieldname,
            MAX(w.workflow_id) as latest_workflow_id
        FROM hrms_workflow w
        GROUP BY w.associatenumber, w.fieldname
    )
    SELECT 
        w.workflow_id, 
        w.associatenumber, 
        w.fieldname, 
        w.submitted_value, 
        w.submission_timestamp, 
        w.reviewer_status,
        m.fullname, 
        m.phone, 
        m.email,
        w.remarks
    FROM hrms_workflow w
    JOIN rssimyaccount_members m ON w.associatenumber = m.associatenumber
    JOIN latest_submissions ls ON 
        w.associatenumber = ls.associatenumber AND 
        w.fieldname = ls.fieldname AND
        w.workflow_id = ls.latest_workflow_id
    WHERE w.reviewer_status = 'Pending'
    ORDER BY w.submission_timestamp DESC
";

$hrms_result = pg_query($con, $hrms_query);
$hrms_data = [];

if ($hrms_result) {
    while ($row = pg_fetch_assoc($hrms_result)) {
        $fieldname = pg_escape_string($con, $row['fieldname']);
        $currentValueQuery = "SELECT $fieldname AS current_value FROM rssimyaccount_members WHERE associatenumber = '{$row['associatenumber']}'";
        $currentValueResult = pg_query($con, $currentValueQuery);
        $currentValueRow = pg_fetch_assoc($currentValueResult);
        $row['current_value'] = $currentValueRow['current_value'] ?? '<em>Not Available</em>';
        $hrms_data[] = $row;
    }
}

// Fetch Student data
$student_query = "SELECT w.*, s.studentname, a.fullname as submitter_name
                FROM student_profile_update_workflow w
                JOIN rssimyprofile_student s ON w.student_id = s.student_id
                LEFT JOIN rssimyaccount_members a ON w.submitted_by = a.associatenumber
                WHERE w.reviewer_status = 'Pending'
                ORDER BY w.submission_timestamp ASC";
$student_result = pg_query($con, $student_query);
$student_data = [];

// Field name mapping for display
$field_names_mapping = [
    'studentname' => 'Student Name',
    'fathername' => 'Father Name',
    'mothername' => 'Mother Name',
    'email' => 'Email',
    'phone' => 'Phone',
    // Add more mappings as needed
];

if ($student_result) {
    while ($row = pg_fetch_assoc($student_result)) {
        $row['field_display'] = $field_names_mapping[$row['field_name']] ?? $row['field_name'];
        $student_data[] = $row;
    }
}
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

    <title>Worklist Management</title>

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
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }

        .nav-tabs .nav-link {
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            font-weight: 600;
        }

        .tab-content {
            padding-top: 20px;
        }

        .badge-count {
            font-size: 0.7em;
            vertical-align: top;
            margin-left: 5px;
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <style>
        tbody tr {
            cursor: pointer;
        }

        tbody tr:hover {
            background-color: #f5f5f5;
        }

        .form-check-input {
            margin-left: 0;
        }

        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            border-radius: 0.25rem;
        }

        .file-link {
            color: #0d6efd;
            text-decoration: underline;
        }

        .file-link:hover {
            text-decoration: none;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Worklist Management</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Worklist</a></li>
                    <li class="breadcrumb-item active">Worklist Management</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>

                            <!-- Tab Navigation -->
                            <ul class="nav nav-tabs" id="worklistTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $active_tab === 'hrms' ? 'active' : '' ?>"
                                        id="hrms-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#hrms-tab-pane"
                                        type="button"
                                        role="tab">
                                        HRMS Worklist
                                        <?php if (!empty($hrms_data)): ?>
                                            <span class="badge bg-danger badge-count"><?= count($hrms_data) ?></span>
                                        <?php endif; ?>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $active_tab === 'student' ? 'active' : '' ?>"
                                        id="student-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#student-tab-pane"
                                        type="button"
                                        role="tab">
                                        Student Data Worklist
                                        <?php if (!empty($student_data)): ?>
                                            <span class="badge bg-danger badge-count"><?= count($student_data) ?></span>
                                        <?php endif; ?>
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content" id="worklistTabsContent">

                                <!-- HRMS Tab -->
                                <div class="tab-pane fade <?= $active_tab === 'hrms' ? 'show active' : '' ?>"
                                    id="hrms-tab-pane"
                                    role="tabpanel"
                                    tabindex="0">

                                    <?php if ($role == 'Admin'): ?>
                                        <div class="row mb-3">
                                            <div class="col-md-12 text-end">
                                                <button id="bulk-review-button-hrms" class="btn btn-primary" disabled>Bulk Review (0)</button>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (empty($hrms_data)): ?>
                                        <div class="alert alert-info mt-3">
                                            No pending HRMS approval requests.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table" id="worklist-table-hrms">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>Workflow ID</th>
                                                        <th>Submitted on</th>
                                                        <th>Associate</th>
                                                        <th>Full Name</th>
                                                        <th>Field Name</th>
                                                        <th>Current Value</th>
                                                        <th>Submitted Value</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($hrms_data as $row): ?>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox"
                                                                    class="form-check-input hrms-checkbox"
                                                                    name="selected_ids[]"
                                                                    value="<?= htmlspecialchars($row['workflow_id']) ?>"
                                                                    <?= ($row['reviewer_status'] === 'Approved' || $row['reviewer_status'] === 'Rejected') ? 'disabled' : ''; ?>>
                                                            </td>
                                                            <td><?= htmlspecialchars($row['workflow_id']) ?></td>
                                                            <td><?= date('d/m/Y h:i a', strtotime($row['submission_timestamp'])) ?></td>
                                                            <td><?= htmlspecialchars($row['associatenumber']) ?></td>
                                                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                                                            <td><?= htmlspecialchars($row['fieldname']) ?></td>
                                                            <td><?= htmlspecialchars($row['current_value']) ?></td>
                                                            <td><?= htmlspecialchars($row['submitted_value']) ?></td>
                                                            <td><?= htmlspecialchars($row['reviewer_status']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Student Tab -->
                                <div class="tab-pane fade <?= $active_tab === 'student' ? 'show active' : '' ?>"
                                    id="student-tab-pane"
                                    role="tabpanel"
                                    tabindex="0">

                                    <?php if ($role == 'Admin'): ?>
                                        <div class="row mb-3">
                                            <div class="col-md-12 text-end">
                                                <button id="bulk-review-button-student" class="btn btn-primary" disabled>Bulk Review (0)</button>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (empty($student_data)): ?>
                                        <div class="alert alert-info mt-3">
                                            No pending student profile update requests.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table" id="worklist-table-student">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th>Student</th>
                                                        <th>Field</th>
                                                        <th>Current Value</th>
                                                        <th>Requested Value</th>
                                                        <th>Submitted By</th>
                                                        <th>Submitted On</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($student_data as $row): ?>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox"
                                                                    class="form-check-input student-checkbox"
                                                                    name="selected_ids[]"
                                                                    value="<?= htmlspecialchars($row['workflow_id']) ?>">
                                                            </td>
                                                            <td>
                                                                <?= htmlspecialchars($row['studentname']) ?><br>
                                                                <small class="text-muted">ID: <?= $row['student_id'] ?></small>
                                                            </td>
                                                            <td><?= htmlspecialchars($row['field_display']) ?></td>
                                                            <td>
                                                                <?php if (strpos($row['field_name'], 'photo') !== false || strpos($row['field_name'], 'document') !== false): ?>
                                                                    <?php if ($row['current_value']): ?>
                                                                        <a href="<?= $row['current_value'] ?>" target="_blank" class="file-link">View Current</a>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">None</span>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <?= htmlspecialchars($row['current_value'] ?? 'Not set') ?>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if (strpos($row['field_name'], 'photo') !== false || strpos($row['field_name'], 'document') !== false): ?>
                                                                    <?php if ($row['submitted_value']): ?>
                                                                        <a href="<?= $row['submitted_value'] ?>" target="_blank" class="file-link">View New</a>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <?= htmlspecialchars($row['submitted_value'] ?? '') ?>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?= htmlspecialchars($row['submitter_name'] ?? $row['submitted_by']) ?>
                                                            </td>
                                                            <td><?= date('d/m/Y H:i', strtotime($row['submission_timestamp'])) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Bulk Review Modal for HRMS -->
    <div class="modal fade" id="bulkReviewModalHrms" tabindex="-1" aria-labelledby="bulkReviewModalHrmsLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkReviewModalHrmsLabel">Bulk Review - HRMS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="bulk-review-form-hrms" method="POST" action="#">
                    <input type="hidden" name="bulk_action_type" value="hrms">
                    <div class="modal-body">
                        <input type="hidden" name="selected_ids" id="selected-ids-hrms">
                        <div class="mb-3">
                            <label for="bulk-action-hrms" class="form-label">Action</label>
                            <select name="bulk_action" id="bulk-action-hrms" class="form-select" required>
                                <option value="">Select Action</option>
                                <option value="Approved">Approve</option>
                                <option value="Rejected">Reject</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="bulk-remarks-hrms" class="form-label">Remarks</label>
                            <textarea name="bulk_remarks" id="bulk-remarks-hrms" class="form-control" placeholder="Enter remarks..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Review Modal for Student -->
    <div class="modal fade" id="bulkReviewModalStudent" tabindex="-1" aria-labelledby="bulkReviewModalStudentLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkReviewModalStudentLabel">Bulk Review - Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="bulk-review-form-student" method="POST" action="#">
                    <input type="hidden" name="bulk_action_type" value="student">
                    <div class="modal-body">
                        <input type="hidden" name="selected_ids" id="selected-ids-student">
                        <div class="mb-3">
                            <label for="bulk-action-student" class="form-label">Action</label>
                            <select name="bulk_action" id="bulk-action-student" class="form-select" required>
                                <option value="">Select Action</option>
                                <option value="Approved">Approve</option>
                                <option value="Rejected">Reject</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="bulk-remarks-student" class="form-label">Remarks</label>
                            <textarea name="bulk_remarks" id="bulk-remarks-student" class="form-control" placeholder="Enter remarks..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  <script src="../assets_new/js/text-refiner.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#worklist-table-hrms').DataTable({
                "order": [],
                "columnDefs": [{
                    "orderable": false,
                    "targets": 0
                }]
            });

            $('#worklist-table-student').DataTable({
                "order": [],
                "columnDefs": [{
                    "orderable": false,
                    "targets": 0
                }]
            });

            // Make rows clickable for HRMS table
            $('#worklist-table-hrms tbody').on('click', 'tr', function(e) {
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'A' && e.target.tagName !== 'I') {
                    const checkbox = $(this).find('td:first-child .form-check-input');
                    if (checkbox.length && !checkbox.prop('disabled')) {
                        checkbox.prop('checked', !checkbox.prop('checked'));
                        updateHrmsBulkReviewButton();
                    }
                }
            });

            // Make rows clickable for Student table
            $('#worklist-table-student tbody').on('click', 'tr', function(e) {
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'A' && e.target.tagName !== 'I') {
                    const checkbox = $(this).find('td:first-child .form-check-input');
                    if (checkbox.length) {
                        checkbox.prop('checked', !checkbox.prop('checked'));
                        updateStudentBulkReviewButton();
                    }
                }
            });

            // Update HRMS bulk review button
            function updateHrmsBulkReviewButton() {
                const selectedCount = $('#worklist-table-hrms tbody .hrms-checkbox:checked').length;
                const bulkReviewButton = $('#bulk-review-button-hrms');
                bulkReviewButton.text(`Bulk Review (${selectedCount})`);
                bulkReviewButton.prop('disabled', selectedCount === 0);
            }

            // Update Student bulk review button
            function updateStudentBulkReviewButton() {
                const selectedCount = $('#worklist-table-student tbody .student-checkbox:checked').length;
                const bulkReviewButton = $('#bulk-review-button-student');
                bulkReviewButton.text(`Bulk Review (${selectedCount})`);
                bulkReviewButton.prop('disabled', selectedCount === 0);
            }

            // Initialize HRMS bulk review modal
            $('#bulk-review-button-hrms').click(function() {
                const selectedIds = [];
                $('#worklist-table-hrms tbody .hrms-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length > 0) {
                    $('#selected-ids-hrms').val(selectedIds.join(','));
                    const bulkReviewModal = new bootstrap.Modal(document.getElementById('bulkReviewModalHrms'));
                    bulkReviewModal.show();
                } else {
                    alert('Please select at least one request to proceed.');
                }
            });

            // Initialize Student bulk review modal
            $('#bulk-review-button-student').click(function() {
                const selectedIds = [];
                $('#worklist-table-student tbody .student-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length > 0) {
                    $('#selected-ids-student').val(selectedIds.join(','));
                    const bulkReviewModal = new bootstrap.Modal(document.getElementById('bulkReviewModalStudent'));
                    bulkReviewModal.show();
                } else {
                    alert('Please select at least one request to proceed.');
                }
            });

            // Checkbox change handlers
            $('#worklist-table-hrms tbody .hrms-checkbox').change(function() {
                updateHrmsBulkReviewButton();
            });

            $('#worklist-table-student tbody .student-checkbox').change(function() {
                updateStudentBulkReviewButton();
            });

            // Initialize button states
            updateHrmsBulkReviewButton();
            updateStudentBulkReviewButton();

            // Handle tab changes to update URLs
            $('#worklistTabs button').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr('id');
                const tabName = target.replace('-tab', '');
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.replaceState({}, '', url);
            });
        });
    </script>
    <?php if (isset($_SESSION['bulk_action_status']) && $_SESSION['bulk_action_status']['tab'] === $active_tab): ?>
        <script>
            $(document).ready(function() {
                alert('<?php echo addslashes($_SESSION['bulk_action_status']['message']); ?>');
                <?php unset($_SESSION['bulk_action_status']); ?>
            });
        </script>
    <?php endif; ?>
</body>

</html>