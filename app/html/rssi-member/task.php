<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php"); // Include drive upload functionality

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

// Check if user is Admin or Centre Incharge (Offline Manager)
$is_admin = ($role === 'Admin' || $role === 'Offline Manager');

// Get date filter
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');
$view_all = isset($_GET['view_all']) && $is_admin ? $_GET['view_all'] : '';

// Handle file upload for deliverables
function handleDeliverableUpload($file, $task_id, $associatenumber)
{
    if (!empty($file['name'])) {
        $folder_id = '1zbevlcQJg2sZcldp23ix1uGqy5cy5Un-Sy8x8cwz0L15GRhSSdFy0k7HjMjraVwefgB6TfL0';
        $filename = "deliverable_{$task_id}_{$associatenumber}_" . time();
        return uploadeToDrive($file, $folder_id, $filename);
    }
    return null;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_task' && $is_admin) {
            $success_count = 0;
            $error_count = 0;

            // Process each task
            $task_names = $_POST['task_name'] ?? [];
            $priorities = $_POST['priority'] ?? [];
            $estimated_hours = $_POST['estimated_hours'] ?? [];
            $start_dates = $_POST['start_date'] ?? [];
            $end_dates = $_POST['end_date'] ?? [];
            $milestones = $_POST['milestone'] ?? [];
            $rates = $_POST['rate'] ?? [];
            $notes = $_POST['notes'] ?? [];
            $owner_associatenumber = $_POST['owner_associatenumber'] ?? '';

            // Handle file uploads for deliverables
            $deliverable_files = $_FILES['deliverables'] ?? [];
            $deliverable_texts = $_POST['deliverable_text'] ?? [];
            $deliverable_types = $_POST['deliverable_type'] ?? [];

            for ($i = 0; $i < count($task_names); $i++) {
                if (empty($task_names[$i])) continue;

                // Handle deliverable based on type
                $deliverable_link = null;

                if (isset($deliverable_types[$i]) && $deliverable_types[$i] === 'file') {
                    // File upload
                    if (isset($deliverable_files['name'][$i]) && !empty($deliverable_files['name'][$i][0])) {
                        $uploaded_files = [];
                        foreach ($deliverable_files['tmp_name'][$i] as $key => $tmp_name) {
                            if ($deliverable_files['error'][$i][$key] == 0) {
                                $file = [
                                    'name' => $deliverable_files['name'][$i][$key],
                                    'type' => $deliverable_files['type'][$i][$key],
                                    'tmp_name' => $tmp_name,
                                    'error' => $deliverable_files['error'][$i][$key],
                                    'size' => $deliverable_files['size'][$i][$key]
                                ];
                                $link = handleDeliverableUpload($file, 'new', $owner_associatenumber);
                                if ($link) {
                                    $uploaded_files[] = $link;
                                }
                            }
                        }
                        $deliverable_link = !empty($uploaded_files) ? implode(',', $uploaded_files) : null;
                    }
                } else {
                    // Text deliverable
                    $deliverable_link = $deliverable_texts[$i] ?? null;
                }

                // Create new task
                $insert_query = "INSERT INTO tasks (
                    task_name, priority, owner_associatenumber, status, 
                    estimated_hours, start_date, end_date, milestone, 
                    deliverables, rate, notes, created_by
                ) VALUES (
                    $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12
                ) RETURNING task_id";

                $params = [
                    $task_names[$i],
                    $priorities[$i] ?? 'P3',
                    $owner_associatenumber,
                    'Not started', // Default status
                    $estimated_hours[$i] ?: null,
                    $start_dates[$i] ?: null,
                    $end_dates[$i] ?: null,
                    $milestones[$i] ?? '',
                    $deliverable_link,
                    $rates[$i] ?: null,
                    $notes[$i] ?? '',
                    $associatenumber
                ];

                $result = pg_query_params($con, $insert_query, $params);
                if ($result) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }

            if ($success_count > 0) {
                $success = "$success_count task(s) created successfully!";
                if ($error_count > 0) {
                    $success .= " $error_count task(s) failed.";
                }
            } else {
                $error = "Error creating tasks: " . pg_last_error($con);
            }
        } elseif ($_POST['action'] === 'update_status') {
            // Handle deliverable upload for EOD update
            $deliverable_link = null;
            if (isset($_FILES['eod_deliverables']) && $_FILES['eod_deliverables']['error'][0] != 4) {
                $uploaded_files = [];
                foreach ($_FILES['eod_deliverables']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['eod_deliverables']['error'][$key] == 0) {
                        $file = [
                            'name' => $_FILES['eod_deliverables']['name'][$key],
                            'type' => $_FILES['eod_deliverables']['type'][$key],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['eod_deliverables']['error'][$key],
                            'size' => $_FILES['eod_deliverables']['size'][$key]
                        ];
                        $link = handleDeliverableUpload($file, $_POST['task_id'], $associatenumber);
                        if ($link) {
                            $uploaded_files[] = $link;
                        }
                    }
                }
                $deliverable_link = !empty($uploaded_files) ? implode(',', $uploaded_files) : null;
            }

            // Update task status
            $update_query = "UPDATE tasks SET 
                status = $1, 
                updated_by = $2, 
                updated_date = CURRENT_TIMESTAMP 
                WHERE task_id = $3";

            $params = [$_POST['status'], $associatenumber, $_POST['task_id']];
            pg_query_params($con, $update_query, $params);

            // Add comment with deliverables
            $comment_query = "INSERT INTO task_comments (
                task_id, comment_text, status, hours_spent, deliverables, created_by
            ) VALUES ($1, $2, $3, $4, $5, $6)";

            $comment_params = [
                $_POST['task_id'],
                $_POST['comment_text'],
                $_POST['status'],
                $_POST['hours_spent'] ?: null,
                $deliverable_link,
                $associatenumber
            ];
            pg_query_params($con, $comment_query, $comment_params);

            $success = "Status updated successfully for Task #" . $_POST['task_id'] . "!";
        }
    }
}

// Build query based on user role and filters
if ($is_admin && $view_all === 'all') {
    $query = "SELECT t.*, rm.fullname as owner_name,
                     (SELECT SUM(hours_spent) FROM task_comments WHERE task_id = t.task_id AND comment_date = '$filter_date') as today_hours,
                     (SELECT comment_text FROM task_comments WHERE task_id = t.task_id AND comment_date = '$filter_date' ORDER BY created_date DESC LIMIT 1) as today_comment,
                     (SELECT deliverables FROM task_comments WHERE task_id = t.task_id AND comment_date = '$filter_date' ORDER BY created_date DESC LIMIT 1) as today_deliverables
              FROM tasks t
              LEFT JOIN (
                  SELECT associatenumber, fullname 
                  FROM rssimyaccount_members
              ) rm ON t.owner_associatenumber = rm.associatenumber
              WHERE (t.start_date <= '$filter_date' OR t.start_date IS NULL)
              AND (t.end_date >= '$filter_date' OR t.end_date IS NULL)
              ORDER BY t.priority, t.created_date DESC";
} else {
    $query = "SELECT t.*, rm.fullname as owner_name,
                     (SELECT SUM(hours_spent) FROM task_comments WHERE task_id = t.task_id AND comment_date = '$filter_date') as today_hours,
                     (SELECT comment_text FROM task_comments WHERE task_id = t.task_id AND comment_date = '$filter_date' ORDER BY created_date DESC LIMIT 1) as today_comment,
                     (SELECT deliverables FROM task_comments WHERE task_id = t.task_id AND comment_date = '$filter_date' ORDER BY created_date DESC LIMIT 1) as today_deliverables
              FROM tasks t
              LEFT JOIN (
                  SELECT associatenumber, fullname 
                  FROM rssimyaccount_members
              ) rm ON t.owner_associatenumber = rm.associatenumber
              WHERE t.owner_associatenumber = '$associatenumber'
              AND (t.start_date <= '$filter_date' OR t.start_date IS NULL)
              AND (t.end_date >= '$filter_date' OR t.end_date IS NULL)
              ORDER BY t.priority, t.created_date DESC";
}

$result = pg_query($con, $query);
$tasks = $result ? pg_fetch_all($result) : [];

// Get analytics data
if ($is_admin && $view_all === 'all') {
    $analytics_query = "SELECT 
                        t.owner_associatenumber,
                        rm.fullname as owner_name,
                        COUNT(*) as total_tasks,
                        SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
                        SUM(CASE WHEN t.status = 'In progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                        SUM(CASE WHEN t.status = 'Not started' THEN 1 ELSE 0 END) as not_started_tasks,
                        SUM(CASE WHEN t.status = 'Blocked' THEN 1 ELSE 0 END) as blocked_tasks,
                        SUM(CASE WHEN t.status = 'Rescheduled' THEN 1 ELSE 0 END) as rescheduled_tasks,
                        SUM(t.estimated_hours) as total_planned_hours,
                        COALESCE(SUM(tc.hours_spent), 0) as total_actual_hours
                       FROM tasks t
                       LEFT JOIN rssimyaccount_members rm ON t.owner_associatenumber = rm.associatenumber
                       LEFT JOIN task_comments tc ON t.task_id = tc.task_id AND tc.comment_date = '$filter_date'
                       WHERE (t.start_date <= '$filter_date' OR t.start_date IS NULL)
                       AND (t.end_date >= '$filter_date' OR t.end_date IS NULL)
                       GROUP BY t.owner_associatenumber, rm.firstname, rm.lastname
                       ORDER BY owner_name";
} else {
    $analytics_query = "SELECT 
                        'You' as owner_name,
                        COUNT(*) as total_tasks,
                        SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
                        SUM(CASE WHEN t.status = 'In progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                        SUM(CASE WHEN t.status = 'Not started' THEN 1 ELSE 0 END) as not_started_tasks,
                        SUM(CASE WHEN t.status = 'Blocked' THEN 1 ELSE 0 END) as blocked_tasks,
                        SUM(CASE WHEN t.status = 'Rescheduled' THEN 1 ELSE 0 END) as rescheduled_tasks,
                        SUM(t.estimated_hours) as total_planned_hours,
                        COALESCE(SUM(tc.hours_spent), 0) as total_actual_hours
                       FROM tasks t
                       LEFT JOIN task_comments tc ON t.task_id = tc.task_id AND tc.comment_date = '$filter_date'
                       WHERE t.owner_associatenumber = '$associatenumber'
                       AND (t.start_date <= '$filter_date' OR t.start_date IS NULL)
                       AND (t.end_date >= '$filter_date' OR t.end_date IS NULL)";
}

$analytics_result = pg_query($con, $analytics_query);
$analytics_data = $analytics_result ? pg_fetch_all($analytics_result) : [];

// Get status breakdown for chart
$status_query = "SELECT status, COUNT(*) as count, SUM(estimated_hours) as hours
                 FROM tasks t
                 WHERE (t.start_date <= '$filter_date' OR t.start_date IS NULL)
                 AND (t.end_date >= '$filter_date' OR t.end_date IS NULL)";
if (!($is_admin && $view_all === 'all')) {
    $status_query .= " AND t.owner_associatenumber = '$associatenumber'";
}
$status_query .= " GROUP BY status";
$status_result = pg_query($con, $status_query);
$status_breakdown = $status_result ? pg_fetch_all($status_result) : [];
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-not-started {
            background-color: #6c757d;
            color: white;
        }

        .status-in-progress {
            background-color: #007bff;
            color: white;
        }

        .status-blocked {
            background-color: #dc3545;
            color: white;
        }

        .status-completed {
            background-color: #28a745;
            color: white;
        }

        .status-rescheduled {
            background-color: #ffc107;
            color: black;
        }

        .priority-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .priority-P0 {
            background-color: #dc3545;
            color: white;
        }

        .priority-P1 {
            background-color: #fd7e14;
            color: white;
        }

        .priority-P2 {
            background-color: #ffc107;
            color: black;
        }

        .priority-P3 {
            background-color: #28a745;
            color: white;
        }

        .priority-P4 {
            background-color: #17a2b8;
            color: white;
        }

        .priority-P5 {
            background-color: #6c757d;
            color: white;
        }

        .priority-P6 {
            background-color: #343a40;
            color: white;
        }

        .priority-P7 {
            background-color: #6f42c1;
            color: white;
        }

        .analytics-card {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .deliverable-link {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }

        .filter-section {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .task-row {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #dee2e6;
        }

        .total-hours-badge {
            font-size: 1.1rem;
            padding: 8px 15px;
            background-color: #e9ecef;
            border-radius: 20px;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }

        .rating-stars .star-filled {
            color: #ffc107;
        }

        .rating-stars .star-empty {
            color: #e4e5e9;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Activity Tracker</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active">Activity Tracker</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <div class="row">

                <!-- Filter Section -->
                <div class="col-12">
                    <div class="filter-section card">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="filter_date" class="form-label">Select Date</label>
                                <input type="text" class="form-control datepicker" id="filter_date" name="filter_date" value="<?php echo $filter_date; ?>">
                            </div>
                            <?php if ($is_admin): ?>
                                <div class="col-md-3">
                                    <label for="view_all" class="form-label">View</label>
                                    <select class="form-select" id="view_all" name="view_all">
                                        <option value="">My Tasks Only</option>
                                        <option value="all" <?php echo $view_all === 'all' ? 'selected' : ''; ?>>All Associates Tasks</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                            </div>
                            <div class="col-md-2">
                                <a href="?filter_date=<?php echo date('Y-m-d'); ?>&view_all=<?php echo $view_all; ?>" class="btn btn-secondary w-100">Today</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Analytics Dashboard -->
                <div class="col-12">
                    <div class="row">
                        <?php if (!empty($analytics_data)): ?>
                            <?php foreach ($analytics_data as $analytics): ?>
                                <div class="col-md-<?php echo ($is_admin && $view_all === 'all') ? '4' : '12'; ?>">
                                    <div class="analytics-card card">
                                        <h5><?php echo $analytics['owner_name']; ?> - Summary</h5>
                                        <div class="row">
                                            <div class="col-4">
                                                <small>Total Tasks</small>
                                                <h3><?php echo $analytics['total_tasks'] ?? 0; ?></h3>
                                            </div>
                                            <div class="col-4">
                                                <small>Planned Hours</small>
                                                <h3><?php echo number_format($analytics['total_planned_hours'] ?? 0, 1); ?></h3>
                                            </div>
                                            <div class="col-4">
                                                <small>Actual Hours</small>
                                                <h3><?php echo number_format($analytics['total_actual_hours'] ?? 0, 1); ?></h3>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <small class="text-muted">Breakdown:</small>
                                                <div class="d-flex flex-wrap gap-2 mt-1">
                                                    <span class="badge bg-success">Completed: <?php echo $analytics['completed_tasks'] ?? 0; ?></span>
                                                    <span class="badge bg-primary">In Progress: <?php echo $analytics['in_progress_tasks'] ?? 0; ?></span>
                                                    <span class="badge bg-secondary">Not Started: <?php echo $analytics['not_started_tasks'] ?? 0; ?></span>
                                                    <span class="badge bg-danger">Blocked: <?php echo $analytics['blocked_tasks'] ?? 0; ?></span>
                                                    <span class="badge bg-warning text-dark">Rescheduled: <?php echo $analytics['rescheduled_tasks'] ?? 0; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status Breakdown Chart -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Status Breakdown</h5>
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Hours Distribution -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Hours Distribution</h5>
                            <canvas id="hoursChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Create Task Section (for admins) -->
                <?php if ($is_admin): ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Create New Tasks</h5>

                                <!-- Associate Selection -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Select Associate *</label>
                                        <select class="form-control associate-select" id="associateSelect" style="width: 100%;">
                                            <option value="">Search and select associate...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="total-hours-badge">
                                            <i class="bi bi-clock"></i> Total Hours Assigned: <span id="totalHours">0</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tasks Container -->
                                <div id="tasksContainer">
                                    <!-- Tasks will be added here dynamically -->
                                </div>

                                <!-- Add Task Button -->
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="button" class="btn btn-success" id="addTaskBtn" disabled>
                                            <i class="bi bi-plus-circle"></i> Add Task
                                        </button>
                                        <button type="button" class="btn btn-primary" id="saveAllTasksBtn" disabled>
                                            <i class="bi bi-save"></i> Save All Tasks
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tasks Table -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Tasks for <?php echo date('d M Y', strtotime($filter_date)); ?></h5>

                            <div class="table-responsive">
                                <table id="tasksTable" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Task ID</th>
                                            <th>Task</th>
                                            <th>Priority</th>
                                            <th>Owner</th>
                                            <th>Status</th>
                                            <th>Est. Hours</th>
                                            <th>Today Hours</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Milestone</th>
                                            <th>Deliverables</th>
                                            <th>Rate</th>
                                            <th>Notes</th>
                                            <th>Today Update</th>
                                            <?php if (!$is_admin || ($is_admin && $view_all !== 'all')): ?>
                                                <th>Action</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($tasks)): ?>
                                            <?php foreach ($tasks as $task): ?>
                                                <tr>
                                                    <td><strong>#<?php echo $task['task_id']; ?></strong></td>
                                                    <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                                                    <td>
                                                        <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                                            <?php echo htmlspecialchars($task['priority']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($task['owner_name'] ?? $task['owner_associatenumber']); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $task['status'])); ?>">
                                                            <?php echo $task['status']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $task['estimated_hours'] ? number_format($task['estimated_hours'], 1) : '-'; ?></td>
                                                    <td><?php echo $task['today_hours'] ? number_format($task['today_hours'], 1) : '-'; ?></td>
                                                    <td><?php echo $task['start_date'] ? date('d M Y', strtotime($task['start_date'])) : '-'; ?></td>
                                                    <td><?php echo $task['end_date'] ? date('d M Y', strtotime($task['end_date'])) : '-'; ?></td>
                                                    <td><?php echo htmlspecialchars($task['milestone'] ?? '-'); ?></td>
                                                    <td>
                                                        <?php if (!empty($task['deliverables'])):
                                                            if (filter_var($task['deliverables'], FILTER_VALIDATE_URL) || strpos($task['deliverables'], 'http') === 0):
                                                                $files = explode(',', $task['deliverables']);
                                                                foreach ($files as $index => $file):
                                                        ?>
                                                                    <a href="<?php echo $file; ?>" target="_blank" class="deliverable-link">
                                                                        <i class="bi bi-file-earmark"></i> File <?php echo $index + 1; ?>
                                                                    </a><br>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <small><?php echo htmlspecialchars(substr($task['deliverables'], 0, 30)) . '...'; ?></small>
                                                            <?php endif; ?>
                                                            <?php else: ?>-<?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($task['rate'])): ?>
                                                            <div class="rating-stars">
                                                                <?php
                                                                $rating = intval($task['rate']);
                                                                for ($i = 1; $i <= 5; $i++) {
                                                                    if ($i <= $rating) {
                                                                        echo '<i class="bi bi-star-fill star-filled"></i>';
                                                                    } else {
                                                                        echo '<i class="bi bi-star star-empty"></i>';
                                                                    }
                                                                }
                                                                ?>
                                                            </div>
                                                            <?php else: ?>-<?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars(substr($task['notes'] ?? '-', 0, 50)) . (strlen($task['notes'] ?? '') > 50 ? '...' : ''); ?></td>
                                                    <td>
                                                        <?php if (!empty($task['today_comment'])): ?>
                                                            <small><?php echo htmlspecialchars(substr($task['today_comment'], 0, 30)) . (strlen($task['today_comment']) > 30 ? '...' : ''); ?></small>
                                                            <?php if (!empty($task['today_deliverables'])): ?>
                                                                <br><a href="<?php echo explode(',', $task['today_deliverables'])[0]; ?>" target="_blank"><i class="bi bi-paperclip"></i></a>
                                                            <?php endif; ?>
                                                            <?php else: ?>-<?php endif; ?>
                                                    </td>
                                                    <?php if (!$is_admin || ($is_admin && $view_all !== 'all')): ?>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#updateStatusModal"
                                                                data-task-id="<?php echo $task['task_id']; ?>"
                                                                data-task-name="<?php echo htmlspecialchars($task['task_name']); ?>"
                                                                data-current-status="<?php echo $task['status']; ?>">
                                                                Update
                                                            </button>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Update Status Modal -->
        <div class="modal fade" id="updateStatusModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Task Status - <span id="taskNameDisplay"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="task_id" id="taskId">

                            <div class="mb-3">
                                <label class="form-label">Current Status</label>
                                <select class="form-select" name="status" id="currentStatus" required>
                                    <option value="Not started">Not started</option>
                                    <option value="In progress">In progress</option>
                                    <option value="Blocked">Blocked</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Rescheduled">Rescheduled</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Hours Spent Today</label>
                                <input type="number" step="0.5" class="form-control" name="hours_spent">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">EOD Comment/Update</label>
                                <textarea class="form-control" name="comment_text" rows="3" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload Deliverables (if any)</label>
                                <input type="file" class="form-control" name="eod_deliverables[]" multiple>
                                <small class="text-muted">Upload any files related to today's work</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#tasksTable').DataTable({
                order: [
                    [0, 'desc']
                ],
                pageLength: 25,
                responsive: true
            });

            // Initialize datepicker
            flatpickr(".datepicker", {
                dateFormat: "Y-m-d",
                defaultDate: "<?php echo $filter_date; ?>"
            });

            // Initialize Select2 for associate search
            $('.associate-select').select2({
                theme: 'bootstrap-5',
                ajax: {
                    url: 'fetch_associates.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            isActive: true
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
                placeholder: 'Search and select associate...',
                allowClear: true,
                width: '100%'
            });

            // Task counter and hours tracking
            let taskCounter = 0;
            let totalHours = 0;

            // Enable/disable buttons based on associate selection
            $('#associateSelect').on('change', function() {
                const selected = $(this).val();
                $('#addTaskBtn').prop('disabled', !selected);
                $('#saveAllTasksBtn').prop('disabled', !selected || taskCounter === 0);
            });

            // Add Task button click handler
            $('#addTaskBtn').click(function() {
                const associateId = $('#associateSelect').val();
                if (!associateId) {
                    alert('Please select an associate first');
                    return;
                }

                taskCounter++;
                const taskId = 'task_' + taskCounter;
                const taskHtml = `
                    <div class="task-row" id="${taskId}">
                        <div class="row g-3">
                            <div class="col-md-12 text-end">
                                <button type="button" class="btn btn-sm btn-danger remove-task" data-task-id="${taskId}">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Task Name *</label>
                                <input type="text" class="form-control task-name" name="task_name[]" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Priority</label>
                                <select class="form-select task-priority" name="priority[]">
                                    <option value="P0">P0 - Critical</option>
                                    <option value="P1">P1 - High</option>
                                    <option value="P2">P2 - Medium-High</option>
                                    <option value="P3" selected>P3 - Medium</option>
                                    <option value="P4">P4 - Medium-Low</option>
                                    <option value="P5">P5 - Low</option>
                                    <option value="P6">P6 - Very Low</option>
                                    <option value="P7">P7 - Backlog</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Est. Hours *</label>
                                <input type="number" step="0.5" class="form-control task-hours" name="estimated_hours[]" required onchange="updateTotalHours()">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control task-start-date" name="start_date[]">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control task-end-date" name="end_date[]">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Milestone</label>
                                <input type="text" class="form-control" name="milestone[]">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Deliverable Type</label>
                                <select class="form-select deliverable-type" name="deliverable_type[]" onchange="toggleDeliverableInput(this, '${taskId}')">
                                    <option value="text">Text</option>
                                    <option value="file">File Upload</option>
                                </select>
                            </div>
                            <div class="col-md-3 deliverable-container" id="${taskId}_deliverable">
                                <label class="form-label">Deliverable</label>
                                <input type="text" class="form-control" name="deliverable_text[]" placeholder="Enter deliverable details">
                            </div>
                            <div class="col-md-3" style="display: none;" id="${taskId}_file_container">
                                <label class="form-label">Upload Files</label>
                                <input type="file" class="form-control" name="deliverables[${taskCounter}][]" multiple disabled>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Rating (0-5)</label>
                                <select class="form-select task-rate" name="rate[]">
                                    <option value="">No rating</option>
                                    <option value="1">1 - Poor</option>
                                    <option value="2">2 - Fair</option>
                                    <option value="3">3 - Good</option>
                                    <option value="4">4 - Very Good</option>
                                    <option value="5">5 - Excellent</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" name="notes[]" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                `;

                $('#tasksContainer').append(taskHtml);
                $('#saveAllTasksBtn').prop('disabled', false);
                updateTotalHours();
            });

            // Remove task handler
            $(document).on('click', '.remove-task', function() {
                const taskId = $(this).data('task-id');
                $('#' + taskId).remove();
                taskCounter--;
                updateTotalHours();
                if (taskCounter === 0) {
                    $('#saveAllTasksBtn').prop('disabled', true);
                }
            });

            // Toggle deliverable input type
            window.toggleDeliverableInput = function(select, taskId) {
                const type = $(select).val();
                const textContainer = $('#' + taskId + '_deliverable');
                const fileContainer = $('#' + taskId + '_file_container');

                if (type === 'text') {
                    textContainer.show();
                    fileContainer.hide();
                    fileContainer.find('input').prop('disabled', true);
                    textContainer.find('input').prop('disabled', false);
                } else {
                    textContainer.hide();
                    fileContainer.show();
                    textContainer.find('input').prop('disabled', true);
                    fileContainer.find('input').prop('disabled', false);
                }
            };

            // Update total hours
            window.updateTotalHours = function() {
                totalHours = 0;
                $('.task-hours').each(function() {
                    const hours = parseFloat($(this).val()) || 0;
                    totalHours += hours;
                });
                $('#totalHours').text(totalHours.toFixed(1));
            };

            // Save all tasks
            $('#saveAllTasksBtn').click(function() {
                const associateId = $('#associateSelect').val();
                if (!associateId) {
                    alert('Please select an associate');
                    return;
                }

                // Validate task rows
                let isValid = true;
                $('.task-row').each(function() {
                    const taskName = $(this).find('.task-name').val();
                    const taskHours = $(this).find('.task-hours').val();

                    if (!taskName || !taskHours) {
                        isValid = false;
                        $(this).css('border-left-color', '#dc3545');
                    } else {
                        $(this).css('border-left-color', '#28a745');
                    }
                });

                if (!isValid) {
                    alert('Please fill in all required fields (Task Name and Estimated Hours)');
                    return;
                }

                // Create form and submit
                const form = $('<form method="POST" enctype="multipart/form-data"></form>');
                form.append('<input type="hidden" name="action" value="create_task">');
                form.append('<input type="hidden" name="owner_associatenumber" value="' + associateId + '">');

                // Copy all form inputs
                $('#tasksContainer').find('input, select, textarea').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        if ($(this).attr('type') === 'file') {
                            // Handle file inputs separately
                            const files = $(this)[0].files;
                            for (let i = 0; i < files.length; i++) {
                                const fileInput = $('<input type="file" name="' + name + '" style="display:none;">');
                                form.append(fileInput);
                                // Note: Due to security restrictions, we can't programmatically set file inputs
                                // This will need to be handled by the form submission
                            }
                        } else {
                            const value = $(this).val();
                            if (value) {
                                form.append('<input type="hidden" name="' + name + '" value="' + value + '">');
                            }
                        }
                    }
                });

                // Submit the form
                $('body').append(form);
                form.submit();
            });

            // Update Status Modal - Set task details
            $('#updateStatusModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var taskId = button.data('task-id');
                var taskName = button.data('task-name');
                var currentStatus = button.data('current-status');

                var modal = $(this);
                modal.find('#taskId').val(taskId);
                modal.find('#taskNameDisplay').text(taskName);
                modal.find('#currentStatus').val(currentStatus);
            });

            // Charts
            <?php if (!empty($status_breakdown)): ?>
                // Status Chart
                var ctx1 = document.getElementById('statusChart').getContext('2d');
                var statusLabels = [];
                var statusCounts = [];
                var statusColors = {
                    'Not started': '#6c757d',
                    'In progress': '#007bff',
                    'Blocked': '#dc3545',
                    'Completed': '#28a745',
                    'Rescheduled': '#ffc107'
                };
                var backgroundColors = [];

                <?php foreach ($status_breakdown as $status): ?>
                    statusLabels.push('<?php echo $status['status']; ?>');
                    statusCounts.push(<?php echo $status['count']; ?>);
                    backgroundColors.push(statusColors['<?php echo $status['status']; ?>'] || '#17a2b8');
                <?php endforeach; ?>

                new Chart(ctx1, {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusCounts,
                            backgroundColor: backgroundColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });

                // Hours Chart
                var ctx2 = document.getElementById('hoursChart').getContext('2d');
                new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            label: 'Hours',
                            data: [<?php
                                    $hours_array = [];
                                    foreach ($status_breakdown as $status) {
                                        $hours_array[] = $status['hours'] ?? 0;
                                    }
                                    echo implode(',', $hours_array);
                                    ?>],
                            backgroundColor: backgroundColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Hours'
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>
        });
    </script>

    <?php if (isset($success)): ?>
        <script>
            alert('<?php echo $success; ?>');
        </script>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <script>
            alert('<?php echo $error; ?>');
        </script>
    <?php endif; ?>

</body>

</html>