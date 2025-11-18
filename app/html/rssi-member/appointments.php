<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Initialize session messages
if (!isset($_SESSION['flash_messages'])) {
    $_SESSION['flash_messages'] = [];
}

// Function to add flash messages
function addFlashMessage($type, $message)
{
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
        'timestamp' => time()
    ];
}

// Function to display flash messages
function displayFlashMessages()
{
    if (!empty($_SESSION['flash_messages'])) {
        foreach ($_SESSION['flash_messages'] as $message) {
            $alertClass = 'alert-' . $message['type'];
            echo <<<HTML
                <div class="alert {$alertClass} alert-dismissible fade show mb-4 py-2">
                    {$message['message']}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
HTML;
        }
        // Clear messages after displaying
        $_SESSION['flash_messages'] = [];
    }
}

// Function to calculate academic year based on date (1st April to 31st March)
function getAcademicYear($date)
{
    $year = date('Y', strtotime($date));
    $month = date('m', strtotime($date));
    if ($month >= 4) { // April or later
        return $year . '-' . ($year + 1);
    } else { // January-March
        return ($year - 1) . '-' . $year;
    }
}

// Function to build the base appointments query
function buildAppointmentsQuery($con, $status_filter, $selected_academic_year)
{
    $query_params = [];
    $query_where = [];

    $query = "
        SELECT 
            aa.id AS appointment_id,
            aa.beneficiary_id,
            aa.appointment_for,
            aa.appointment_date,
            aa.appointment_time,
            aa.status,
            aa.remarks,
            aa.created_at AS appointment_created_at,
            aa.updated_at,
            aa.workflow,
            creator.fullname AS created_by_name,
            updater.fullname AS updated_by_name,
            COALESCE(
                (SELECT studentname FROM rssimyprofile_student WHERE student_id = aa.beneficiary_id LIMIT 1),
                (SELECT fullname FROM rssimyaccount_members WHERE associatenumber = aa.beneficiary_id LIMIT 1),
                (SELECT name FROM public_health_records WHERE id::text = aa.beneficiary_id LIMIT 1),
                (SELECT parent_name FROM survey_data WHERE id::text = aa.beneficiary_id LIMIT 1)
            ) AS beneficiary_name,
            COALESCE(
                (SELECT contact FROM rssimyprofile_student WHERE student_id = aa.beneficiary_id LIMIT 1),
                (SELECT phone FROM rssimyaccount_members WHERE associatenumber = aa.beneficiary_id LIMIT 1),
                (SELECT contact_number FROM public_health_records WHERE id::text = aa.beneficiary_id LIMIT 1),
                (SELECT contact FROM survey_data WHERE id::text = aa.beneficiary_id LIMIT 1)
            ) AS beneficiary_contact
        FROM appointments aa
        LEFT JOIN rssimyaccount_members creator ON creator.associatenumber = aa.created_by
        LEFT JOIN rssimyaccount_members updater ON updater.associatenumber = aa.updated_by
    ";

    if ($status_filter !== 'all') {
        $query_where[] = "aa.status = $" . (count($query_params) + 1);
        $query_params[] = $status_filter;
    }

    if ($selected_academic_year !== 'all') {
        list($start_year, $end_year) = explode('-', $selected_academic_year);
        $query_where[] = "(aa.appointment_date >= '$start_year-04-01' AND aa.appointment_date <= '$end_year-03-31')";
    }

    if (!empty($query_where)) {
        $query .= " WHERE " . implode(" AND ", $query_where);
    }

    return [
        'query' => $query,
        'params' => $query_params
    ];
}

// Function to get status badge class
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'scheduled':
            return 'primary';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Get current academic year
$current_date = date('Y-m-d');
$current_academic_year = getAcademicYear($current_date);

// Set default filters
$status_filter = $_GET['status'] ?? 'scheduled';
$selected_academic_year = $_GET['academic_year'] ?? $current_academic_year;

// Generate academic years for dropdown
$years_query = "SELECT DISTINCT 
    CASE 
        WHEN EXTRACT(MONTH FROM appointment_date) >= 4 THEN 
            EXTRACT(YEAR FROM appointment_date) || '-' || (EXTRACT(YEAR FROM appointment_date) + 1)
        ELSE 
            (EXTRACT(YEAR FROM appointment_date) - 1) || '-' || EXTRACT(YEAR FROM appointment_date)
    END AS academic_year
FROM appointments
ORDER BY academic_year DESC";
$years_result = pg_query($con, $years_query);
$academic_years = [];
while ($year_row = pg_fetch_assoc($years_result)) {
    $academic_years[] = $year_row['academic_year'];
}

// Handle appointment status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'] ?? '';

    // Get current status and workflow
    $current_data = pg_query_params(
        $con,
        "SELECT status, workflow FROM appointments WHERE id = $1",
        [$appointment_id]
    );
    $current_row = pg_fetch_assoc($current_data);
    $old_status = $current_row['status'];
    $workflow = $current_row['workflow'] ? json_decode($current_row['workflow'], true) : [];

    // Validate inputs
    if (!in_array($status, ['scheduled', 'completed', 'cancelled'])) {
        addFlashMessage('danger', 'Invalid status value');
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    pg_query($con, "BEGIN");

    try {
        // Add to workflow history
        $workflow_entry = [
            'old_status' => $old_status,
            'new_status' => $status,
            'remarks' => $remarks,
            'changed_by' => $associatenumber,
            'changed_at' => date('Y-m-d H:i:s')
        ];
        $workflow[] = $workflow_entry;
        $workflow_json = json_encode($workflow);

        // Update appointment
        $result = pg_query_params(
            $con,
            "UPDATE appointments 
             SET status = $1, remarks = $2, updated_at = CURRENT_TIMESTAMP, 
                 updated_by = $3, workflow = $4
             WHERE id = $5 RETURNING id",
            [$status, $remarks, $associatenumber, $workflow_json, $appointment_id]
        );

        if (!$result) {
            throw new Exception("Error updating appointment: " . pg_last_error($con));
        }

        if (pg_num_rows($result) == 0) {
            throw new Exception("No appointment found with ID: $appointment_id");
        }

        pg_query($con, "COMMIT");
        addFlashMessage('success', 'Appointment status updated successfully!');
    } catch (Exception $e) {
        pg_query($con, "ROLLBACK");
        addFlashMessage('danger', $e->getMessage());
    }

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}

// Handle export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="appointments_' . date('Ymd') . '.csv"');

    $out = fopen('php://output', 'w');

    // Header row
    fputcsv($out, [
        'Appointment ID',
        'Beneficiary Name',
        'Beneficiary ID',
        'Appointment For',
        'Date',
        'Time',
        'Contact',
        'Status',
        'Remarks',
        'Created By',
        'Created On',
        'Updated By',
        'Updated On',
        'Workflow History'
    ]);

    // Build and execute export query
    $query_builder = buildAppointmentsQuery($con, $status_filter, $selected_academic_year);
    $export_query = $query_builder['query'] . " ORDER BY aa.appointment_date DESC, aa.appointment_time DESC";
    $export_result = pg_query_params($con, $export_query, $query_builder['params']);

    while ($row = pg_fetch_assoc($export_result)) {
        $workflow_history = '';
        if ($row['workflow']) {
            $workflow = json_decode($row['workflow'], true);
            foreach ($workflow as $entry) {
                $workflow_history .= "[" . date('d M Y H:i', strtotime($entry['changed_at'])) . "] ";
                $workflow_history .= "Status changed from " . $entry['old_status'] . " to " . $entry['new_status'];
                $workflow_history .= " by " . $entry['changed_by'];
                if (!empty($entry['remarks'])) {
                    $workflow_history .= " (Remarks: " . $entry['remarks'] . ")";
                }
                $workflow_history .= "\n";
            }
        }

        fputcsv($out, [
            $row['appointment_id'],
            $row['beneficiary_name'],
            $row['beneficiary_id'],
            $row['appointment_for'],
            $row['appointment_date'],
            $row['appointment_time'],
            $row['beneficiary_contact'],
            $row['status'],
            $row['remarks'],
            $row['created_by_name'],
            $row['appointment_created_at'],
            $row['updated_by_name'],
            $row['updated_at'],
            $workflow_history
        ]);
    }

    fclose($out);
    exit;
}

// Get statistics and counts
$count_query = "SELECT COUNT(*) AS total FROM appointments aa";
$stats_query = "SELECT status, COUNT(*) as count FROM appointments aa";
$count_where = $stats_where = [];

if ($selected_academic_year !== 'all') {
    list($start_year, $end_year) = explode('-', $selected_academic_year);
    $date_condition = "(aa.appointment_date >= '$start_year-04-01' AND aa.appointment_date <= '$end_year-03-31')";
    $count_where[] = $date_condition;
    $stats_where[] = $date_condition;
}

if (!empty($count_where)) {
    $count_query .= " WHERE " . implode(" AND ", $count_where);
    $stats_query .= " WHERE " . implode(" AND ", $stats_where);
}

$count_result = pg_query($con, $count_query);
$total_appointments = pg_fetch_result($count_result, 0, 'total');

$stats_query .= " GROUP BY status";
$stats_result = pg_query($con, $stats_query);
$stats = ['scheduled' => 0, 'completed' => 0, 'cancelled' => 0];
while ($stat_row = pg_fetch_assoc($stats_result)) {
    $stats[$stat_row['status']] = $stat_row['count'];
}

// Build and execute main query
$query_builder = buildAppointmentsQuery($con, $status_filter, $selected_academic_year);
$query = $query_builder['query'] . " ORDER BY aa.appointment_date asc, aa.created_at asc";
$result = pg_query_params($con, $query, $query_builder['params']);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

    <!-- In your head section -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --dark-color: #5a5c69;
        }

        .app-container {
            max-height: calc(100vh - 60px);
            overflow-y: auto;
        }

        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card.primary {
            border-left-color: var(--primary-color);
        }

        .stat-card.success {
            border-left-color: var(--success-color);
        }

        .stat-card.danger {
            border-left-color: var(--danger-color);
        }

        .stat-card.info {
            border-left-color: var(--info-color);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .stat-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--dark-color);
        }

        .appointment-row {
            transition: background-color 0.2s;
            border-left: 3px solid transparent;
        }

        .appointment-row:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }

        .badge-scheduled {
            background-color: var(--primary-color);
        }

        .badge-completed {
            background-color: var(--success-color);
        }

        .badge-cancelled {
            background-color: var(--danger-color);
        }

        .compact-table th {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--dark-color);
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .compact-table td {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            vertical-align: middle;
        }

        .beneficiary-info {
            line-height: 1.3;
        }

        .beneficiary-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .beneficiary-id {
            font-size: 0.8rem;
            color: var(--dark-color);
        }

        .date-time {
            line-height: 1.3;
        }

        .date {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .time {
            font-size: 0.85rem;
            color: var(--dark-color);
        }

        .chart-container {
            height: 250px;
        }

        .filter-card {
            background-color: rgba(0, 0, 0, 0.02);
        }
    </style>
    <style>
        .timeline {
            position: relative;
            padding-left: 1.5rem;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
            padding-left: 1.5rem;
            border-left: 2px solid #dee2e6;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
            border-left: 2px solid transparent;
        }

        .timeline-badge {
            position: absolute;
            left: -0.5rem;
            top: 0;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            border: 2px solid white;
            z-index: 1;
        }

        .timeline-content {
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 0.25rem;
        }

        .status-change {
            display: flex;
            align-items: center;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Appointments</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Survey</a></li>
                    <li class="breadcrumb-item"><a href="#">Appointments</a></li>
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
                            <div class="container-fluid py-3">

                                <?php displayFlashMessages(); ?>

                                <!-- Stats Cards -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <div class="card stat-card primary h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <div class="stat-value"><?= $total_appointments ?></div>
                                                        <div class="stat-label">Total Appointments</div>
                                                    </div>
                                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                                        <i class="bi bi-calendar-check text-primary"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card stat-card info h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <div class="stat-value"><?= $stats['scheduled'] ?? 0 ?></div>
                                                        <div class="stat-label">Scheduled</div>
                                                    </div>
                                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                                        <i class="bi bi-clock text-info"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card stat-card success h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <div class="stat-value"><?= $stats['completed'] ?? 0 ?></div>
                                                        <div class="stat-label">Completed</div>
                                                    </div>
                                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                                        <i class="bi bi-check-circle text-success"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card stat-card danger h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <div class="stat-value"><?= $stats['cancelled'] ?? 0 ?></div>
                                                        <div class="stat-label">Cancelled</div>
                                                    </div>
                                                    <div class="bg-danger bg-opacity-10 p-3 rounded">
                                                        <i class="bi bi-x-circle text-danger"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="container-fluid">
                                    <!-- Filters and Chart Section -->
                                    <div class="row g-3 mb-3">
                                        <!-- Filters Card -->
                                        <div class="col-md-3">
                                            <div class="card shadow-sm border-0 h-100">
                                                <div class="card-body p-3">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <i class="bi bi-funnel me-2 text-primary"></i>
                                                        <h6 class="card-title mb-0 fw-semibold">Filters</h6>
                                                    </div>
                                                    <form method="GET" class="mb-4">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-muted">Academic Year</label>
                                                            <select class="form-select form-select-sm border-300" name="academic_year">
                                                                <option value="all">All Years</option>
                                                                <?php foreach ($academic_years as $year): ?>
                                                                    <option value="<?= htmlspecialchars($year) ?>" <?= $selected_academic_year == $year ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($year) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-muted">Status</label>
                                                            <select class="form-select form-select-sm border-300" name="status">
                                                                <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Appointments</option>
                                                                <option value="scheduled" <?= $status_filter == 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                                                <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                                                                <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                            </select>
                                                        </div>
                                                        <button type="submit" class="btn btn-sm btn-primary w-100 py-2">
                                                            <i class="bi bi-funnel me-1"></i> Apply Filters
                                                        </button>
                                                    </form>

                                                    <!-- Chart Card -->
                                                    <div class="card border-0 shadow-sm">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                                <div class="d-flex align-items-center">
                                                                    <i class="bi bi-bar-chart me-2 text-primary"></i>
                                                                    <h6 class="card-title mb-0 fw-semibold">Appointments Overview</h6>
                                                                </div>
                                                                <span class="badge bg-light text-dark small"><?= htmlspecialchars($selected_academic_year) ?></span>
                                                            </div>
                                                            <div class="chart-container" style="height: 220px;">
                                                                <div id="statusChart"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Appointments Table -->
                                        <div class="col-md-9">
                                            <div class="card shadow-sm border-0 h-100">
                                                <div class="card-header bg-white border-bottom-0 py-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h5 class="mb-0 fw-semibold">Appointments</h5>

                                                        <div class="d-flex gap-2 ms-auto">
                                                            <!-- Quick Create Appointment Button -->
                                                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createAppointmentModal">
                                                                <i class="bi bi-calendar-plus"></i> Create Appointment
                                                            </button>

                                                            <!-- Export Button -->
                                                            <a href="?export=1&<?= http_build_query($_GET) ?>" class="btn btn-outline-secondary">
                                                                <i class="bi bi-download"></i> Export
                                                            </a>
                                                        </div>
                                                    </div>

                                                </div>
                                                <div class="card-body p-0">
                                                    <div class="table-responsive">
                                                        <table id="table-id" class="table table-hover align-middle mb-0">
                                                            <thead class="bg-light">
                                                                <tr>
                                                                    <th class="text-nowrap ps-3">SL</th> <!-- New Serial Number Column -->
                                                                    <th class="text-nowrap">Beneficiary</th>
                                                                    <th class="text-nowrap">Date & Time</th>
                                                                    <th class="text-nowrap">Purpose</th>
                                                                    <th class="text-nowrap">Contact</th>
                                                                    <th class="text-nowrap">Status</th>
                                                                    <th class="text-nowrap">Created By</th>
                                                                    <th class="text-nowrap pe-3">Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if (pg_num_rows($result) > 0): ?>
                                                                    <?php
                                                                    $sl = 1; // Initialize serial number counter
                                                                    while ($row = pg_fetch_assoc($result)): ?>
                                                                        <tr class="appointment-row">
                                                                            <td class="ps-3">
                                                                                <span><?= $sl++ ?></span> <!-- Display and increment SL number -->
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex flex-column">
                                                                                    <span class="fw-medium"><?= htmlspecialchars($row['beneficiary_name']) ?></span>
                                                                                    <small class="text-muted">Ben ID: <?= htmlspecialchars($row['beneficiary_id']) ?></small>
                                                                                    <small class="text-muted">Appt ID: <?= htmlspecialchars($row['appointment_id']) ?></small>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex flex-column">
                                                                                    <span><?= date('d M Y', strtotime($row['appointment_date'])) ?></span>
                                                                                    <small class="text-muted"><?= date('h:i A', strtotime($row['appointment_time'])) ?></small>
                                                                                </div>
                                                                            </td>
                                                                            <td class="ps-3">
                                                                                <span><?= htmlspecialchars($row['appointment_for']) ?></span>
                                                                            </td>
                                                                            <td class="ps-3">
                                                                                <span><?= htmlspecialchars($row['beneficiary_contact']) ?></span>
                                                                            </td>
                                                                            <td>
                                                                                <span class="badge py-1 px-2 bg-<?=
                                                                                                                ($row['status'] ?? 'scheduled') == 'scheduled' ? 'primary' : (($row['status'] ?? '') == 'completed' ? 'success' : 'danger')
                                                                                                                ?>">
                                                                                    <?= ucfirst($row['status'] ?? 'scheduled') ?>
                                                                                </span>
                                                                            </td>
                                                                            <td><small><?= isset($row['created_by_name']) ? htmlspecialchars($row['created_by_name']) : '' ?></small><br>
                                                                                <small><?= date('d M Y', strtotime($row['appointment_created_at'])) ?></small>
                                                                            </td>
                                                                            <td class="pe-3">
                                                                                <div class="d-flex">
                                                                                    <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                                                                        data-bs-toggle="modal" data-bs-target="#statusModal<?= $row['appointment_id'] ?>"
                                                                                        title="Update Status">
                                                                                        <i class="bi bi-pencil"></i>
                                                                                    </button>
                                                                                    <?php if (!empty($row['workflow'])): ?>
                                                                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                                                            data-bs-toggle="modal" data-bs-target="#historyModal<?= $row['appointment_id'] ?>"
                                                                                            title="View History">
                                                                                            <i class="bi bi-clock-history"></i>
                                                                                        </button>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                            </td>
                                                                        </tr>

                                                                        <!-- Status Update Modal -->
                                                                        <div class="modal fade" id="statusModal<?= $row['appointment_id'] ?>" tabindex="-1" aria-hidden="true">
                                                                            <div class="modal-dialog modal-dialog-centered">
                                                                                <div class="modal-content">
                                                                                    <div class="modal-header">
                                                                                        <h5 class="modal-title fs-6">Update Appointment Status</h5>
                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                    </div>

                                                                                    <form method="POST">
                                                                                        <div class="modal-body">

                                                                                            <?php if ($role === 'Admin'): ?>
                                                                                                <?php
                                                                                                $beneficiaryName = $row['beneficiary_name'];
                                                                                                $contactNumber = preg_replace('/[^0-9]/', '', $row['beneficiary_contact']); // ensure only digits
                                                                                                $appointmentFor = htmlspecialchars($row['appointment_for']);
                                                                                                $appointmentDateTime = date('d/m/Y \को h:i A', strtotime($row['appointment_date'] . ' ' . $row['appointment_time']));

                                                                                                $whatsappMessage = "प्रिय {$beneficiaryName},\n\n"
                                                                                                    . "आपकी \"{$appointmentFor}\" की अपॉइंटमेंट {$appointmentDateTime} बजे निर्धारित की गई है। कृपया समय पर उपस्थित हों और नीचे दिए गए दस्तावेज़ साथ लेकर आएं:\n\n"
                                                                                                    . "- आधार कार्ड\n"
                                                                                                    . "- आधार से लिंक किया गया मोबाइल फोन\n\n"
                                                                                                    . "यह संदेश RSSI NGO की ओर से है।\nधन्यवाद।\n\nयह एक स्वचालित संदेश है।";

                                                                                                $encodedMessage = urlencode($whatsappMessage);
                                                                                                $whatsappURL = "https://wa.me/91{$contactNumber}?text={$encodedMessage}";
                                                                                                ?>

                                                                                                <div class="text-end">
                                                                                                    <a href="<?= $whatsappURL ?>" target="_blank">Send WhatsApp Reminder</a>
                                                                                                </div>
                                                                                            <?php endif; ?>

                                                                                            <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($row['appointment_id']) ?>">

                                                                                            <div class="mb-3">
                                                                                                <label class="form-label small fw-bold">Beneficiary</label>
                                                                                                <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($row['beneficiary_name']) ?>" readonly>
                                                                                            </div>

                                                                                            <div class="mb-3">
                                                                                                <label class="form-label small fw-bold">Appointment Date/Time</label>
                                                                                                <input type="text" class="form-control form-control-sm"
                                                                                                    value="<?= date('d M Y h:i A', strtotime($row['appointment_date'] . ' ' . $row['appointment_time'])) ?>" readonly>
                                                                                            </div>

                                                                                            <div class="mb-3">
                                                                                                <label class="form-label small fw-bold">Status</label>
                                                                                                <select class="form-select form-select-sm" name="status" required>
                                                                                                    <option value="scheduled" <?= ($row['status'] ?? 'scheduled') == 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                                                                                    <option value="completed" <?= ($row['status'] ?? '') == 'completed' ? 'selected' : '' ?>>Completed</option>
                                                                                                    <option value="cancelled" <?= ($row['status'] ?? '') == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                                                                </select>
                                                                                            </div>

                                                                                            <div class="mb-3">
                                                                                                <label class="form-label small fw-bold">Remarks</label>
                                                                                                <textarea class="form-control form-control-sm" name="remarks" rows="3"><?= htmlspecialchars($row['remarks'] ?? '') ?></textarea>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="modal-footer">
                                                                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                                                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">Save Changes</button>
                                                                                        </div>
                                                                                    </form>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Workflow History Modal -->
                                                                        <?php if (!empty($row['workflow'])): ?>
                                                                            <div class="modal fade" id="historyModal<?= $row['appointment_id'] ?>" tabindex="-1" aria-hidden="true">
                                                                                <div class="modal-dialog modal-lg">
                                                                                    <div class="modal-content">
                                                                                        <div class="modal-header">
                                                                                            <h5 class="modal-title">History for #<?= $row['appointment_id'] ?></h5>
                                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                        </div>
                                                                                        <div class="modal-body">
                                                                                            <div class="timeline">
                                                                                                <?php
                                                                                                $workflow = json_decode($row['workflow'], true);
                                                                                                if ($workflow && is_array($workflow)):
                                                                                                    foreach (array_reverse($workflow) as $entry):
                                                                                                        // Get member name with fallback to ID
                                                                                                        $member_name = $entry['changed_by'];
                                                                                                        if (!empty($entry['changed_by'])) {
                                                                                                            $member_query = pg_query_params(
                                                                                                                $con,
                                                                                                                "SELECT fullname FROM rssimyaccount_members WHERE associatenumber = $1 LIMIT 1",
                                                                                                                [$entry['changed_by']]
                                                                                                            );
                                                                                                            if ($member_query && pg_num_rows($member_query) > 0) {
                                                                                                                $member_name = pg_fetch_result($member_query, 0, 'fullname');
                                                                                                            }
                                                                                                        }
                                                                                                ?>
                                                                                                        <div class="timeline-item">
                                                                                                            <div class="timeline-badge bg-<?=
                                                                                                                                            $entry['new_status'] == 'scheduled' ? 'primary' : ($entry['new_status'] == 'completed' ? 'success' : 'danger')
                                                                                                                                            ?>"></div>
                                                                                                            <div class="timeline-content">
                                                                                                                <div class="d-flex justify-content-between">
                                                                                                                    <span class="fw-bold">Status changed</span>
                                                                                                                    <small class="text-muted"><?= date('d M Y, H:i', strtotime($entry['changed_at'])) ?></small>
                                                                                                                </div>
                                                                                                                <div class="status-change mt-1">
                                                                                                                    <span class="badge bg-<?=
                                                                                                                                            ($entry['old_status'] ?? '') == 'scheduled' ? 'primary' : (($entry['old_status'] ?? '') == 'completed' ? 'success' : 'secondary')
                                                                                                                                            ?>">
                                                                                                                        <?= ucfirst($entry['old_status'] ?? 'N/A') ?>
                                                                                                                    </span>
                                                                                                                    <i class="bi bi-arrow-right mx-2"></i>
                                                                                                                    <span class="badge bg-<?=
                                                                                                                                            $entry['new_status'] == 'scheduled' ? 'primary' : ($entry['new_status'] == 'completed' ? 'success' : 'danger')
                                                                                                                                            ?>">
                                                                                                                        <?= ucfirst($entry['new_status']) ?>
                                                                                                                    </span>
                                                                                                                </div>
                                                                                                                <div class="mt-2">
                                                                                                                    <small class="text-muted">Changed by: <?= htmlspecialchars($member_name) ?></small>
                                                                                                                    <?php if (!empty($entry['remarks'])): ?>
                                                                                                                        <div class="mt-1 p-2 bg-light rounded"><?= htmlspecialchars($entry['remarks']) ?></div>
                                                                                                                    <?php endif; ?>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    <?php endforeach; ?>
                                                                                                <?php else: ?>
                                                                                                    <div class="alert alert-info">No history available</div>
                                                                                                <?php endif; ?>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="modal-footer">
                                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    <?php endwhile; ?>
                                                                <?php else: ?>
                                                                    <tr>
                                                                        <td colspan="7" class="text-center py-4 text-muted">No appointments found for the selected filters</td>
                                                                    </tr>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (pg_num_rows($result) > 0) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>

    <!-- Modal -->
    <div class="modal fade" id="createAppointmentModal" tabindex="-1" aria-labelledby="createAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAppointmentModalLabel">Create New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quickAppointmentForm" method="POST" action="process_appointment.php">
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Beneficiary Selection -->
                            <div class="col-md-12">
                                <label for="beneficiarySelect" class="form-label">Search and Select Beneficiary</label>
                                <select id="beneficiarySelect" name="beneficiary_id" class="form-select js-data-ajax" required>
                                    <!-- Beneficiary will be loaded via AJAX -->
                                </select>
                                <div class="form-text text-muted">
                                    First-time user? <a href="register_beneficiary.php" target="_blank">Register here</a>
                                </div>
                                <div class="invalid-feedback">Please select a beneficiary.</div>
                            </div>

                            <!-- Services Needed Section -->
                            <div class="col-md-12">
                                <label class="form-label">Which services do you need assistance with? (Select all that apply)</label>
                                <div class="form-check">
                                    <input class="form-check-input service-checkbox" type="checkbox" id="serviceAadhar" name="servicesNeeded[]" value="Aadhar Card">
                                    <label class="form-check-label" for="serviceAadhar">Aadhar Card</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input service-checkbox" type="checkbox" id="servicePAN" name="servicesNeeded[]" value="PAN Card">
                                    <label class="form-check-label" for="servicePAN">PAN Card</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input service-checkbox" type="checkbox" id="serviceShram" name="servicesNeeded[]" value="Shram Card">
                                    <label class="form-check-label" for="serviceShram">Shram Card</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input service-checkbox" type="checkbox" id="serviceABHA" name="servicesNeeded[]" value="ABHA Card">
                                    <label class="form-check-label" for="serviceABHA">ABHA Card</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input service-checkbox" type="checkbox" id="serviceOther" name="servicesNeeded[]" value="Other">
                                    <label class="form-check-label" for="serviceOther">Other (please specify)</label>
                                    <input type="text" class="form-control mt-2" id="otherService" name="otherService" style="display: none;">
                                </div>
                                <input type="hidden" id="appointmentFor" name="appointment_for">
                            </div>

                            <!-- Appointment Details -->
                            <div class="col-md-6">
                                <label for="appointmentDate" class="form-label">Date</label>
                                <input type="date" class="form-control" id="appointmentDate" name="appointment_date" required>
                                <div class="invalid-feedback">Please select a date.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="appointmentTime" class="form-label">Time</label>
                                <input type="time" class="form-control" id="appointmentTime" name="appointment_time" required>
                                <div class="invalid-feedback">Please select a time.</div>
                            </div>

                            <div class="col-12">
                                <label for="appointmentRemarks" class="form-label">Remarks</label>
                                <textarea class="form-control" id="appointmentRemarks" name="remarks" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <!-- Add ID to target it in JS -->
                        <button type="submit" class="btn btn-primary" id="submitAppointmentBtn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" id="submitSpinner"></span>
                            <span id="submitBtnText">Create Appointment</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for beneficiary search
            $('#beneficiarySelect').select2({
                dropdownParent: $('#createAppointmentModal'),
                ajax: {
                    url: 'search_beneficiaries.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results || []
                        };
                    }
                },
                minimumInputLength: 2,
                placeholder: 'Search by name, ID, or contact',
                allowClear: false,
                closeOnSelect: true,
                width: '100%'
            });

            // Show/hide other service text input
            $('#serviceOther').change(function() {
                if ($(this).is(':checked')) {
                    $('#otherService').show();
                } else {
                    $('#otherService').hide().val('');
                }
            });

            // Update appointment_for hidden field when checkboxes change
            $('.service-checkbox').change(function() {
                updateAppointmentForField();
            });

            // Function to update the appointment_for field
            function updateAppointmentForField() {
                const selectedServices = [];
                $('.service-checkbox:checked').each(function() {
                    if ($(this).val() === 'Other' && $('#otherService').val()) {
                        selectedServices.push($('#otherService').val());
                    } else if ($(this).val() !== 'Other') {
                        selectedServices.push($(this).val());
                    }
                });
                $('#appointmentFor').val(selectedServices.join(', '));
            }

            // Also update when other service text changes
            $('#otherService').on('input', function() {
                updateAppointmentForField();
            });

            // Form validation
            $('#quickAppointmentForm').on('submit', function(e) {
                // Check if at least one service is selected
                if ($('.service-checkbox:checked').length === 0) {
                    e.preventDefault();
                    alert('Please select at least one service');
                    return false;
                }

                if (this.checkValidity() === false) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });

            // Set default date to today
            $('#appointmentDate').val(new Date().toISOString().split('T')[0]);

            // Set default time to next hour
            const now = new Date();
            const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
            $('#appointmentTime').val(nextHour.toTimeString().substring(0, 5));
        });
    </script>
    <script>
        $('#quickAppointmentForm').on('submit', function(e) {
            e.preventDefault();

            const form = this;

            if (form.checkValidity() === false) {
                e.stopPropagation();
                return;
            }

            // Disable button and show spinner
            const $btn = $('#submitAppointmentBtn');
            $btn.prop('disabled', true);
            $('#submitSpinner').removeClass('d-none');
            $('#submitBtnText').text('Submitting...');

            $.ajax({
                url: 'process_appointment.php',
                type: 'POST',
                data: $(form).serialize(),
                dataType: 'json',
                success: function(response) {
                    // Re-enable and reset button
                    $btn.prop('disabled', false);
                    $('#submitSpinner').addClass('d-none');
                    $('#submitBtnText').text('Create Appointment');

                    if (response.success) {
                        alert(response.message);
                        $('#createAppointmentModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false);
                    $('#submitSpinner').addClass('d-none');
                    $('#submitBtnText').text('Create Appointment');
                    alert('An error occurred: ' + (xhr.responseText || error));
                }
            });
        });
    </script>

    <script>
        // Status Distribution Chart with ApexCharts
        document.addEventListener('DOMContentLoaded', function() {
            const options = {
                series: [<?= $stats['scheduled'] ?? 0 ?>, <?= $stats['completed'] ?? 0 ?>, <?= $stats['cancelled'] ?? 0 ?>],
                chart: {
                    type: 'donut',
                    height: '100%',
                    fontFamily: 'inherit',
                    sparkline: {
                        enabled: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: true,
                            delay: 150
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 350
                        }
                    }
                },
                labels: ['Scheduled', 'Completed', 'Cancelled'],
                colors: ['#4e73df', '#1cc88a', '#e74a3b'],
                stroke: {
                    width: 0
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    showAlways: true,
                                    label: 'Total',
                                    fontSize: '14px',
                                    fontWeight: 600,
                                    color: '#5a5c69',
                                    formatter: function(w) {
                                        return w.globals.seriesTotals.reduce((a, b) => {
                                            return a + b
                                        }, 0)
                                    }
                                },
                                value: {
                                    fontSize: '18px',
                                    fontWeight: 700,
                                    color: '#5a5c69',
                                    formatter: function(value) {
                                        return value
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontSize: '12px',
                    markers: {
                        width: 10,
                        height: 10,
                        radius: 50
                    },
                    itemMargin: {
                        horizontal: 10,
                        vertical: 5
                    }
                },
                tooltip: {
                    enabled: true,
                    fillSeriesColor: false,
                    y: {
                        formatter: function(value, {
                            seriesIndex,
                            w
                        }) {
                            const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${value} (${percentage}%)`;
                        }
                    }
                }
            };

            const chart = new ApexCharts(document.querySelector("#statusChart"), options);
            chart.render();
        });
    </script>
</body>

</html>