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

// Fetch event types from database
$event_types = [];
$type_sql = "SELECT id, display_name FROM event_types WHERE is_active = true ORDER BY sort_order";
$type_result = pg_query($con, $type_sql);

if ($type_result) {
    while ($row = pg_fetch_assoc($type_result)) {
        $event_types[$row['id']] = $row['display_name'];
    }
}

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name = pg_escape_string($con, $_POST['event_name']);
    $event_date = pg_escape_string($con, $_POST['event_date']);
    $event_type = pg_escape_string($con, $_POST['event_type']);
    $is_full_day = isset($_POST['is_full_day']) ? 'true' : 'false';

    // Handle time fields - convert 12-hour to 24-hour format
    $event_start_time = null;
    $event_end_time = null;
    $reporting_time = null;

    // ALWAYS check for time fields, regardless of is_full_day
    if (!empty($_POST['event_start_time'])) {
        $time_str = trim($_POST['event_start_time']);
        // Convert 12-hour format (e.g., "11:00 AM") to 24-hour format
        $event_start_time = date("H:i:00", strtotime($time_str));
        $event_start_time = pg_escape_string($con, $event_start_time);
    }

    if (!empty($_POST['event_end_time'])) {
        $time_str = trim($_POST['event_end_time']);
        $event_end_time = date("H:i:00", strtotime($time_str));
        $event_end_time = pg_escape_string($con, $event_end_time);
    }

    if (!empty($_POST['reporting_time'])) {
        $time_str = trim($_POST['reporting_time']);
        $reporting_time = date("H:i:00", strtotime($time_str));
        $reporting_time = pg_escape_string($con, $reporting_time);
    }

    $location = pg_escape_string($con, $_POST['location']);
    $description = pg_escape_string($con, $_POST['description']);
    $created_by = $associatenumber;

    // Check if event already exists on this date
    $check_sql = "SELECT COUNT(*) as count FROM internal_events WHERE event_date = $1 AND event_name = $2";
    $check_result = pg_query_params($con, $check_sql, [$event_date, $event_name]);
    $check_data = pg_fetch_assoc($check_result);

    if ($check_data['count'] > 0) {
        $message = 'An event with the same name already exists on this date!';
        $message_type = 'danger';
    } else {
        // Insert the event
        $insert_sql = "INSERT INTO internal_events (
            event_name, event_date, event_type, is_full_day, 
            event_start_time, event_end_time, reporting_time, 
            location, description, created_by, updated_by
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)";

        $params = [
            $event_name,
            $event_date,
            $event_type,
            $is_full_day,
            $event_start_time,
            $event_end_time,
            $reporting_time,
            $location,
            $description,
            $created_by,
            $created_by
        ];

        $result = pg_query_params($con, $insert_sql, $params);

        if ($result) {
            $message = 'Event created successfully!';
            $message_type = 'success';
            $_POST = []; // Clear form
        } else {
            $message = 'Error creating event: ' . pg_last_error($con);
            $message_type = 'danger';
        }
    }
}

// Handle search filters
$search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$filter_event_type = isset($_GET['filter_event_type']) ? trim($_GET['filter_event_type']) : '';

// Build filter conditions for count query
$count_params = [];
$count_conditions = [];

if (!empty($search_name)) {
    $count_conditions[] = "e.event_name ILIKE $" . (count($count_params) + 1);
    $count_params[] = '%' . $search_name . '%';
}

if (!empty($date_from)) {
    $count_conditions[] = "e.event_date >= $" . (count($count_params) + 1);
    $count_params[] = $date_from;
}

if (!empty($date_to)) {
    $count_conditions[] = "e.event_date <= $" . (count($count_params) + 1);
    $count_params[] = $date_to;
}

if (!empty($filter_event_type)) {
    $count_conditions[] = "e.event_type = $" . (count($count_params) + 1);
    $count_params[] = $filter_event_type;
}

$count_where_clause = !empty($count_conditions) ? "WHERE " . implode(" AND ", $count_conditions) : "";

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM internal_events e
    LEFT JOIN event_types et ON e.event_type = et.id
    $count_where_clause
";

if (!empty($count_params)) {
    $count_result = pg_query_params($con, $count_sql, $count_params);
} else {
    $count_result = pg_query($con, $count_sql);
}

$total_records = 0;
if ($count_result) {
    $total_records = pg_fetch_result($count_result, 0, 'total');
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_records / $limit);

// Build filter conditions for main query
$query_params = [];
$query_conditions = [];

if (!empty($search_name)) {
    $query_conditions[] = "e.event_name ILIKE $" . (count($query_params) + 1);
    $query_params[] = '%' . $search_name . '%';
}

if (!empty($date_from)) {
    $query_conditions[] = "e.event_date >= $" . (count($query_params) + 1);
    $query_params[] = $date_from;
}

if (!empty($date_to)) {
    $query_conditions[] = "e.event_date <= $" . (count($query_params) + 1);
    $query_params[] = $date_to;
}

if (!empty($filter_event_type)) {
    $query_conditions[] = "e.event_type = $" . (count($query_params) + 1);
    $query_params[] = $filter_event_type;
}

$query_where_clause = !empty($query_conditions) ? "WHERE " . implode(" AND ", $query_conditions) : "";

// Build the main query
$recent_events_sql = "
    SELECT 
        e.*, 
        u.fullname,
        et.display_name AS event_type_name
    FROM internal_events e
    LEFT JOIN rssimyaccount_members u 
        ON e.created_by = u.associatenumber
    LEFT JOIN event_types et
        ON e.event_type = et.id
    $query_where_clause
    ORDER BY e.event_date DESC, e.created_at DESC
";

// Add LIMIT and OFFSET with proper parameter placeholders
if (!empty($query_params)) {
    // If we have filter parameters, add LIMIT and OFFSET with next parameter numbers
    $recent_events_sql .= " LIMIT $" . (count($query_params) + 1) . " OFFSET $" . (count($query_params) + 2);
    $query_params[] = $limit;
    $query_params[] = $offset;

    $recent_events_result = pg_query_params($con, $recent_events_sql, $query_params);
} else {
    // No filters, just use LIMIT and OFFSET as parameters 1 and 2
    $recent_events_sql .= " LIMIT $1 OFFSET $2";
    $recent_events_result = pg_query_params($con, $recent_events_sql, [$limit, $offset]);
}

// Check if query failed
if (!$recent_events_result) {
    error_log("Recent events query failed: " . pg_last_error($con));
    $recent_events_result = null;
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
    <?php include 'includes/meta.php' ?>


    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Flatpickr for date/time picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

    <style>
        .required-field::after {
            content: " *";
            color: #dc3545;
        }

        .time-fields-container {
            display: none;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .btn-submit {
            background: #667eea;
            border: none;
            padding: 10px 30px;
            font-weight: 600;
        }

        .btn-submit:hover {
            background: #5a67d8;
        }

        .time-input-group .input-group-text {
            height: 38px;
        }

        .time-input-group .form-control {
            height: 38px;
        }

        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .filter-section .form-label {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .active-filter-badge {
            background-color: #e7f3ff;
            border-radius: 20px;
            padding: 5px 12px;
            margin-right: 8px;
            margin-bottom: 8px;
            display: inline-block;
            font-size: 0.9em;
        }

        .active-filter-badge .remove-filter {
            color: #666;
            margin-left: 6px;
            text-decoration: none;
            font-weight: bold;
        }

        .active-filter-badge .remove-filter:hover {
            color: #dc3545;
        }
    </style>
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
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Event Details</h5>

                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                                    <?php echo $message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="row g-3">
                                    <!-- Event Name -->
                                    <div class="col-md-6">
                                        <label for="event_name" class="form-label required-field">Event Name</label>
                                        <input type="text" class="form-control" id="event_name" name="event_name"
                                            value=""
                                            required maxlength="255">
                                        <div class="invalid-feedback">Please enter event name.</div>
                                    </div>

                                    <!-- Event Date -->
                                    <div class="col-md-6">
                                        <label for="event_date" class="form-label required-field">Event Date</label>
                                        <input type="text" class="form-control flatpickr-date" id="event_date" name="event_date" value="" required>
                                        <div class="invalid-feedback">Please select event date.</div>
                                    </div>

                                    <!-- Event Type -->
                                    <div class="col-md-6">
                                        <label for="event_type" class="form-label required-field">Event Type</label>
                                        <select class="form-select" id="event_type" name="event_type" required>
                                            <option value="">Select Type</option>
                                            <?php
                                            $current_type = $_POST['event_type'] ?? $event['event_type'] ?? '';
                                            foreach ($event_types as $type_value => $display_name):
                                            ?>
                                                <option value="<?php echo htmlspecialchars($type_value); ?>"
                                                    <?php echo $current_type == $type_value ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($display_name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select event type.</div>
                                    </div>

                                    <!-- Location -->
                                    <div class="col-md-6">
                                        <label for="location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="location" name="location"
                                            value=""
                                            maxlength="255" placeholder="e.g., Main Auditorium, Sports Ground" required>
                                    </div>

                                    <!-- Full Day Event -->
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_full_day" name="is_full_day"
                                                value="1" <?php echo isset($_POST['is_full_day']) ? 'checked' : 'checked'; ?>>
                                            <label class="form-check-label" for="is_full_day">
                                                This is a full day event
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Time Fields Section -->
                                    <div id="timeFieldsContainer" class="time-fields-container mt-3">
                                        <div class="row g-3">
                                            <!-- Start Time -->
                                            <div class="col-md-4">
                                                <label for="event_start_time" class="form-label">Start Time</label>
                                                <div class="input-group time-input-group">
                                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                                    <input type="text" class="form-control flatpickr-time" id="event_start_time" name="event_start_time"
                                                        value=""
                                                        placeholder="09:00 AM">
                                                </div>
                                                <small class="form-text text-muted">When the event begins</small>
                                            </div>

                                            <!-- End Time -->
                                            <div class="col-md-4">
                                                <label for="event_end_time" class="form-label">End Time</label>
                                                <div class="input-group time-input-group">
                                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                                    <input type="text" class="form-control flatpickr-time" id="event_end_time" name="event_end_time"
                                                        value=""
                                                        placeholder="05:00 PM">
                                                </div>
                                                <small class="form-text text-muted">When the event ends</small>
                                            </div>

                                            <!-- Reporting Time -->
                                            <div class="col-md-4">
                                                <label for="reporting_time" class="form-label">Reporting Time</label>
                                                <div class="input-group time-input-group">
                                                    <span class="input-group-text"><i class="bi bi-person-check"></i></span>
                                                    <input type="text" class="form-control flatpickr-time" id="reporting_time" name="reporting_time"
                                                        value=""
                                                        placeholder="08:30 AM">
                                                </div>
                                                <small class="form-text text-muted">When to arrive</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div class="col-12">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description"
                                            rows="4" maxlength="1000"
                                            placeholder="Enter event details, agenda, instructions..." required></textarea>
                                        <small class="form-text">Maximum 1000 characters</small>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="col-12 text-center mt-4">
                                        <button type="submit" class="btn btn-primary btn-submit">
                                            <i class="bi bi-calendar-plus"></i> Create Event
                                        </button>
                                        <a href="home.php" class="btn btn-outline-secondary ms-2">
                                            <i class="bi bi-calendar-week"></i> View Calendar
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Recent Events Section with Filters -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title">Recent Events</h5>

                            <!-- Filter Section -->
                            <div class="filter-section">
                                <form method="GET" action="" class="row g-3" id="filterForm">
                                    <div class="col-md-4">
                                        <label for="search_name" class="form-label">Search by Event Name</label>
                                        <input type="text" class="form-control" id="search_name" name="search_name"
                                            value="<?php echo htmlspecialchars($search_name); ?>"
                                            placeholder="Enter event name...">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="date_from" class="form-label">Date From</label>
                                        <input type="text" class="form-control flatpickr-date" id="date_from" name="date_from"
                                            value="<?php echo htmlspecialchars($date_from); ?>"
                                            placeholder="YYYY-MM-DD">
                                    </div>

                                    <div class="col-md-3">
                                        <label for="date_to" class="form-label">Date To</label>
                                        <input type="text" class="form-control flatpickr-date" id="date_to" name="date_to"
                                            value="<?php echo htmlspecialchars($date_to); ?>"
                                            placeholder="YYYY-MM-DD">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="filter_event_type" class="form-label">Event Type</label>
                                        <select class="form-select" id="filter_event_type" name="filter_event_type">
                                            <option value="">All Types</option>
                                            <?php foreach ($event_types as $type_value => $display_name): ?>
                                                <option value="<?php echo htmlspecialchars($type_value); ?>"
                                                    <?php echo $filter_event_type == $type_value ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($display_name); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-search"></i> Apply Filters
                                        </button>
                                        <a href="?" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-circle"></i> Clear Filters
                                        </a>
                                    </div>
                                </form>

                                <!-- Active Filters Display -->
                                <?php if (!empty($search_name) || !empty($date_from) || !empty($date_to) || !empty($filter_event_type)): ?>
                                    <div class="mt-3">
                                        <strong>Active Filters:</strong>
                                        <div class="mt-2">
                                            <?php if (!empty($search_name)): ?>
                                                <span class="active-filter-badge">
                                                    Name: <?php echo htmlspecialchars($search_name); ?>
                                                    <a href="?<?php
                                                                $params = $_GET;
                                                                unset($params['search_name']);
                                                                unset($params['page']);
                                                                echo http_build_query($params);
                                                                ?>" class="remove-filter" title="Remove this filter">×</a>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($date_from)): ?>
                                                <span class="active-filter-badge">
                                                    From: <?php echo htmlspecialchars($date_from); ?>
                                                    <a href="?<?php
                                                                $params = $_GET;
                                                                unset($params['date_from']);
                                                                unset($params['page']);
                                                                echo http_build_query($params);
                                                                ?>" class="remove-filter" title="Remove this filter">×</a>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($date_to)): ?>
                                                <span class="active-filter-badge">
                                                    To: <?php echo htmlspecialchars($date_to); ?>
                                                    <a href="?<?php
                                                                $params = $_GET;
                                                                unset($params['date_to']);
                                                                unset($params['page']);
                                                                echo http_build_query($params);
                                                                ?>" class="remove-filter" title="Remove this filter">×</a>
                                                </span>
                                            <?php endif; ?>

                                            <?php if (!empty($filter_event_type)): ?>
                                                <span class="active-filter-badge">
                                                    Type: <?php echo htmlspecialchars($event_types[$filter_event_type] ?? $filter_event_type); ?>
                                                    <a href="?<?php
                                                                $params = $_GET;
                                                                unset($params['filter_event_type']);
                                                                unset($params['page']);
                                                                echo http_build_query($params);
                                                                ?>" class="remove-filter" title="Remove this filter">×</a>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Results count -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    Showing <?php echo ($recent_events_result && pg_num_rows($recent_events_result) > 0) ? pg_num_rows($recent_events_result) : 0; ?> of <?php echo $total_records; ?> events
                                </small>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Event Name</th>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Location</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($recent_events_result && pg_num_rows($recent_events_result) > 0) {
                                            while ($event = pg_fetch_assoc($recent_events_result)) {

                                                // Prepare time info
                                                $time_info = '';
                                                if ($event['is_full_day'] === 't') {
                                                    $time_info = '<small class="text-muted">Full Day</small>';
                                                } else if ($event['event_start_time']) {
                                                    $start_time = date('h:i A', strtotime($event['event_start_time']));
                                                    $end_time = $event['event_end_time'] ? date('h:i A', strtotime($event['event_end_time'])) : '';
                                                    $time_info = '<small class="text-muted">' . $start_time . ($end_time ? ' - ' . $end_time : '') . '</small>';
                                                }
                                        ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($event['event_name']); ?></strong>
                                                        <?php if ($event['description']): ?>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?= substr(htmlspecialchars($event['description']), 0, 50) . '...'; ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= date('d M Y', strtotime($event['event_date'])); ?>
                                                        <br><?= $time_info; ?>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($event['event_type_name'] ?? 'Other'); ?>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($event['location'] ?: 'N/A'); ?>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($event['fullname'] ?: 'User ' . $event['created_by']); ?>
                                                    </td>
                                                    <td>
                                                        <a href="edit_event.php?id=<?= $event['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                        <?php
                                            }
                                        } else {
                                            echo '<tr><td colspan="6" class="text-center">No events found matching your criteria.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination - Only show if there are events and more than one page -->
                            <?php if ($total_records > 0 && $total_pages > 1): ?>
                                <div class="pagination-container">
                                    <nav aria-label="Event pagination">
                                        <ul class="pagination">
                                            <?php
                                            // Build query string for pagination links
                                            $query_params = $_GET;
                                            unset($query_params['page']);
                                            $base_url = '?' . http_build_query($query_params) . '&page=';
                                            ?>

                                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="<?php echo $base_url . ($page - 1); ?>" tabindex="-1">Previous</a>
                                            </li>

                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="<?php echo $base_url . $i; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="<?php echo $base_url . ($page + 1); ?>">Next</a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script src="../assets_new/js/text-refiner.js?v=1.2.0"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Date picker for event creation
            flatpickr(".flatpickr-date", {
                dateFormat: "Y-m-d",
                allowInput: true
            });

            // Time picker - 12-hour format
            flatpickr(".flatpickr-time", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "h:i K",
                time_24hr: false,
                minuteIncrement: 5
            });

            // Toggle time fields
            const fullDayCheckbox = document.getElementById('is_full_day');
            const timeFieldsContainer = document.getElementById('timeFieldsContainer');

            function toggleTimeFields() {
                if (fullDayCheckbox.checked) {
                    timeFieldsContainer.style.display = 'none';
                } else {
                    timeFieldsContainer.style.display = 'block';
                }
            }

            fullDayCheckbox.addEventListener('change', toggleTimeFields);
            toggleTimeFields();

            // Date range validation for filters
            const dateFromInput = document.getElementById('date_from');
            const dateToInput = document.getElementById('date_to');

            if (dateFromInput && dateToInput) {
                const dateFrom = flatpickr("#date_from", {
                    dateFormat: "Y-m-d",
                    allowInput: true,
                    onChange: function(selectedDates, dateStr) {
                        if (dateStr) {
                            dateTo.set('minDate', dateStr);
                        }
                    }
                });

                const dateTo = flatpickr("#date_to", {
                    dateFormat: "Y-m-d",
                    allowInput: true,
                    onChange: function(selectedDates, dateStr) {
                        if (dateStr) {
                            dateFrom.set('maxDate', dateStr);
                        }
                    }
                });

                // Set initial constraints
                if (dateFromInput.value) {
                    dateTo.set('minDate', dateFromInput.value);
                }
                if (dateToInput.value) {
                    dateFrom.set('maxDate', dateToInput.value);
                }
            }

            // Form validation before submit
            const filterForm = document.getElementById('filterForm');
            if (filterForm) {
                filterForm.addEventListener('submit', function(e) {
                    const fromDate = document.getElementById('date_from').value;
                    const toDate = document.getElementById('date_to').value;

                    if (fromDate && toDate) {
                        if (fromDate > toDate) {
                            e.preventDefault();
                            alert('Error: "Date From" cannot be after "Date To". Please adjust your dates.');
                            return false;
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>