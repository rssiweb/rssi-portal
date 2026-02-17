<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Get filter parameters
$associate_filter = $_GET['associate'] ?? '';
$start_date_filter = $_GET['start_date'] ?? '';
$end_date_filter = $_GET['end_date'] ?? '';
$day_filter = $_GET['day'] ?? '';
$show_inactive = isset($_GET['show_inactive']) ? 1 : 0;

// Build WHERE clause
$where_conditions = [];
$params = [];
$param_count = 0;

if (!empty($associate_filter)) {
    $param_count++;
    $where_conditions[] = "s.associate_number = $" . $param_count;
    $params[] = $associate_filter;
}

if (!empty($start_date_filter)) {
    $param_count++;
    $where_conditions[] = "s.start_date >= $" . $param_count;
    $params[] = $start_date_filter;
}

if (!empty($end_date_filter)) {
    $param_count++;
    $where_conditions[] = "s.start_date <= $" . $param_count;
    $params[] = $end_date_filter;
}

if (!empty($day_filter)) {
    $param_count++;
    $where_conditions[] = "s.workday = $" . $param_count;
    $params[] = $day_filter;
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// First, get the latest dates for each workday for all associates
$latest_dates_query = "SELECT associate_number, workday, MAX(start_date) as latest_date
                       FROM associate_schedule_v2 
                       GROUP BY associate_number, workday";
$latest_dates_result = pg_query($con, $latest_dates_query);

// Create a lookup array for latest dates
$latest_dates_lookup = [];
while ($row = pg_fetch_assoc($latest_dates_result)) {
    $key = $row['associate_number'] . '_' . $row['workday'];
    $latest_dates_lookup[$key] = $row['latest_date'];
}

// Get all associate status info (effectivedate, filterstatus)
$associate_status_query = "SELECT associatenumber, effectivedate, filterstatus 
                          FROM rssimyaccount_members 
                          WHERE filterstatus = 'Inactive' 
                          AND effectivedate <= CURRENT_DATE";
$associate_status_result = pg_query($con, $associate_status_query);

// Create lookup array for inactive associates
$inactive_associates = [];
while ($row = pg_fetch_assoc($associate_status_result)) {
    $inactive_associates[$row['associatenumber']] = [
        'effectivedate' => $row['effectivedate'],
        'filterstatus' => $row['filterstatus']
    ];
}

// Always grouped view
$data_query = "SELECT 
    s.associate_number,
    a.fullname,
    a.position,
    a.engagement,
    a.effectivedate,
    a.filterstatus,
    TO_CHAR(s.reporting_time, 'HH12:MI AM') AS formatted_start,
    TO_CHAR(s.exit_time, 'HH12:MI AM') AS formatted_end,
    MIN(s.start_date) AS start_date,
    MAX(s.start_date) AS end_date,
    TO_CHAR(MIN(s.start_date), 'DD-Mon-YYYY') AS formatted_start_date,
    TO_CHAR(MAX(s.start_date), 'DD-Mon-YYYY') AS formatted_end_date,
    STRING_AGG(s.workday, ',' ORDER BY 
        CASE s.workday
            WHEN 'Mon' THEN 1
            WHEN 'Tue' THEN 2
            WHEN 'Wed' THEN 3
            WHEN 'Thu' THEN 4
            WHEN 'Fri' THEN 5
            WHEN 'Sat' THEN 6
            WHEN 'Sun' THEN 7
        END) AS workdays,
    COUNT(*) AS total_schedules,
    COUNT(DISTINCT s.workday) AS day_count,
    MAX(s.updated_at) as last_updated,
    TO_CHAR(MAX(s.updated_at), 'DD-Mon-YYYY HH12:MI AM') AS formatted_updated,
    STRING_AGG(s.id, ',') as schedule_ids,
    STRING_AGG(s.start_date || '||' || s.workday, ',') as date_day_pairs
FROM associate_schedule_v2 s
LEFT JOIN rssimyaccount_members a 
    ON s.associate_number = a.associatenumber
$where_sql
GROUP BY
    s.associate_number,
    a.fullname,
    a.position,
    a.engagement,
    a.effectivedate,
    a.filterstatus,
    s.reporting_time,
    s.exit_time
ORDER BY s.associate_number, MIN(s.start_date)";

// Execute query
if (!empty($params)) {
    $data_result = pg_query_params($con, $data_query, $params);
} else {
    $data_result = pg_query($con, $data_query);
}

// Get statistics - we need to calculate based on actual active status including associate status
$stats_query = "SELECT 
                COUNT(*) as total_schedules,
                COUNT(DISTINCT associate_number) as unique_associates
                FROM associate_schedule_v2";
$stats_result = pg_query($con, $stats_query);
$stats = $stats_result ? pg_fetch_assoc($stats_result) : ['total_schedules' => 0, 'unique_associates' => 0];

// Calculate active/inactive counts based on latest date logic AND associate status
$active_count = 0;
$inactive_count = 0;
$temp_result = pg_query($con, "SELECT s.*, a.filterstatus, a.effectivedate 
                               FROM associate_schedule_v2 s
                               LEFT JOIN rssimyaccount_members a ON s.associate_number = a.associatenumber");
while ($row = pg_fetch_assoc($temp_result)) {
    $key = $row['associate_number'] . '_' . $row['workday'];
    $latest_date = $latest_dates_lookup[$key] ?? null;

    // Check if associate is inactive based on effectivedate and filterstatus
    $is_associate_inactive = false;
    if (!empty($row['filterstatus']) && $row['filterstatus'] == 'Inactive' && !empty($row['effectivedate'])) {
        $effective_date = strtotime($row['effectivedate']);
        $current_date = strtotime(date('Y-m-d'));
        if ($effective_date <= $current_date) {
            $is_associate_inactive = true;
        }
    }

    // A schedule is active if it's the latest version AND the associate is active
    if ($latest_date && $row['start_date'] == $latest_date && !$is_associate_inactive) {
        $active_count++;
    } else {
        $inactive_count++;
    }
}

$stats['active_schedules'] = $active_count;
$stats['inactive_schedules'] = $inactive_count;
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>

    <title>Shift Schedule Viewer</title>

    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .stats-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .filter-section {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .table-container {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .table td {
            vertical-align: middle;
        }

        .day-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin: 2px;
            background-color: #e9ecef;
            color: #495057;
        }

        .status-badge-active {
            background-color: #d1e7dd !important;
            color: #0f5132 !important;
        }

        .status-badge-inactive {
            background-color: #f8d7da !important;
            color: #842029 !important;
        }

        .status-badge-mixed {
            background-color: #fff3cd !important;
            color: #664d03 !important;
        }

        .time-slot {
            font-weight: 600;
            color: #0d6efd;
        }

        .inactive-row {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .associate-name {
            font-weight: 600;
            color: #212529;
        }

        .associate-id {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .shift-duration {
            font-size: 0.85rem;
            color: #6c757d;
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
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-12">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Total Schedules</h6>
                                        <h3 class="mb-0"><?php echo $stats['total_schedules']; ?></h3>
                                    </div>
                                    <i class="bi bi-calendar3" style="font-size: 1.5rem; color: #3498db;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Active Schedules</h6>
                                        <h3 class="mb-0"><?php echo $stats['active_schedules']; ?></h3>
                                    </div>
                                    <i class="bi bi-check-circle" style="font-size: 1.5rem; color: #27ae60;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Inactive Schedules</h6>
                                        <h3 class="mb-0"><?php echo $stats['inactive_schedules']; ?></h3>
                                    </div>
                                    <i class="bi bi-x-circle" style="font-size: 1.5rem; color: #e74c3c;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Unique Associates</h6>
                                        <h3 class="mb-0"><?php echo $stats['unique_associates']; ?></h3>
                                    </div>
                                    <i class="bi bi-people" style="font-size: 1.5rem; color: #9b59b6;"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters Section -->
                    <div class="filter-section">
                        <form method="GET" action="" id="filter-form">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Associate</label>
                                    <input type="hidden" class="form-control" id="associate-filter" name="associate"
                                        value="<?php echo htmlspecialchars($associate_filter); ?>">
                                    <select class="form-control select2" id="associate-select">
                                        <option value=""></option>
                                        <?php if (!empty($associate_filter)):
                                            $assoc_query = "SELECT fullname FROM rssimyaccount_members WHERE associatenumber = $1";
                                            $assoc_result = pg_query_params($con, $assoc_query, [$associate_filter]);
                                            $assoc_name = pg_fetch_result($assoc_result, 0, 0) ?? $associate_filter;
                                        ?>
                                            <option value="<?php echo htmlspecialchars($associate_filter); ?>" selected>
                                                <?php echo htmlspecialchars($associate_filter . ' - ' . $assoc_name); ?>
                                            </option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">From Date</label>
                                    <input type="date" class="form-control" name="start_date"
                                        value="<?php echo htmlspecialchars($start_date_filter); ?>">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">To Date</label>
                                    <input type="date" class="form-control" name="end_date"
                                        value="<?php echo htmlspecialchars($end_date_filter); ?>">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Day</label>
                                    <select class="form-select" name="day">
                                        <option value="">All Days</option>
                                        <option value="Mon" <?php echo $day_filter == 'Mon' ? 'selected' : ''; ?>>Monday</option>
                                        <option value="Tue" <?php echo $day_filter == 'Tue' ? 'selected' : ''; ?>>Tuesday</option>
                                        <option value="Wed" <?php echo $day_filter == 'Wed' ? 'selected' : ''; ?>>Wednesday</option>
                                        <option value="Thu" <?php echo $day_filter == 'Thu' ? 'selected' : ''; ?>>Thursday</option>
                                        <option value="Fri" <?php echo $day_filter == 'Fri' ? 'selected' : ''; ?>>Friday</option>
                                        <option value="Sat" <?php echo $day_filter == 'Sat' ? 'selected' : ''; ?>>Saturday</option>
                                        <option value="Sun" <?php echo $day_filter == 'Sun' ? 'selected' : ''; ?>>Sunday</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="show_inactive"
                                            value="1" id="showInactive" <?php echo $show_inactive ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="showInactive">
                                            Show Inactive Schedules
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-filter"></i> Apply Filters
                                    </button>
                                    <a href="view_shift.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </a>
                                    <button type="button" class="btn btn-success" onclick="exportToCSV()">
                                        <i class="bi bi-download"></i> Export CSV
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Schedule Table -->
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-hover" id="table-id">
                                <thead>
                                    <tr>
                                        <th>Associate</th>
                                        <th>Working Days</th>
                                        <th>Shift Time</th>
                                        <th>Date Range</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($data_result && pg_num_rows($data_result) > 0): ?>
                                        <?php while ($row = pg_fetch_assoc($data_result)):
                                            // Check if associate is inactive based on effectivedate and filterstatus
                                            $is_associate_inactive = false;
                                            if (!empty($row['filterstatus']) && $row['filterstatus'] == 'Inactive' && !empty($row['effectivedate'])) {
                                                $effective_date = strtotime($row['effectivedate']);
                                                $current_date = strtotime(date('Y-m-d'));
                                                if ($effective_date <= $current_date) {
                                                    $is_associate_inactive = true;
                                                }
                                            }

                                            // Parse the date-day pairs to determine which schedules in this group are active
                                            $date_day_pairs = explode(',', $row['date_day_pairs'] ?? '');
                                            $active_in_group = 0;
                                            $total_in_group = count($date_day_pairs);

                                            foreach ($date_day_pairs as $pair) {
                                                list($date_str, $workday) = explode('||', $pair);
                                                $date = date('Y-m-d', strtotime($date_str));
                                                $key = $row['associate_number'] . '_' . $workday;
                                                $latest_date = $latest_dates_lookup[$key] ?? null;

                                                // A schedule is active only if it's the latest AND associate is active
                                                if ($latest_date && $date == $latest_date && !$is_associate_inactive) {
                                                    $active_in_group++;
                                                }
                                            }

                                            $all_active = ($active_in_group == $total_in_group && !$is_associate_inactive);
                                            $some_active = ($active_in_group > 0 && $active_in_group < $total_in_group);
                                            $none_active = ($active_in_group == 0);

                                            $is_inactive = $none_active || $is_associate_inactive;

                                            // Skip if showing inactive is off and this group has no active schedules
                                            if (!$show_inactive && ($none_active || $is_associate_inactive)) {
                                                continue;
                                            }

                                            // Calculate duration
                                            $start_time = $row['formatted_start'];
                                            $end_time = $row['formatted_end'];

                                            // Convert time strings to DateTime objects
                                            $start = DateTime::createFromFormat('h:i A', $start_time);
                                            $end = DateTime::createFromFormat('h:i A', $end_time);

                                            if ($start && $end) {
                                                if ($end < $start) {
                                                    $end->modify('+1 day');
                                                }
                                                $interval = $start->diff($end);
                                                $duration = $interval->h . 'h ' . $interval->i . 'm';
                                            } else {
                                                $duration = 'N/A';
                                            }
                                        ?>
                                            <tr class="<?php echo ($none_active || $is_associate_inactive) ? 'inactive-row' : ''; ?>">
                                                <td>
                                                    <div class="associate-name">
                                                        <?php echo htmlspecialchars($row['fullname'] ?? 'N/A'); ?>
                                                        <?php if ($is_associate_inactive): ?>
                                                            <span class="badge bg-danger ms-2">Inactive Associate</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="associate-id">ID: <?php echo htmlspecialchars($row['associate_number']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($row['position'] ?? ''); ?></small>
                                                    <?php if ($is_associate_inactive): ?>
                                                        <br><small class="text-danger">
                                                            <i class="bi bi-person-x"></i>
                                                            Inactive since <?php echo date('d-M-Y', strtotime($row['effectivedate'])); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <?php
                                                    $workdays = explode(',', $row['workdays'] ?? '');
                                                    foreach ($workdays as $day):
                                                        $day = trim($day);
                                                        if (!empty($day)):
                                                    ?>
                                                            <span class="day-badge"><?php echo $day; ?></span>
                                                    <?php
                                                        endif;
                                                    endforeach;
                                                    ?>
                                                    <div class="text-muted mt-1"><?php echo $row['day_count'] ?? '0'; ?> days</div>
                                                </td>

                                                <td>
                                                    <div class="time-slot"><?php echo $row['formatted_start']; ?> - <?php echo $row['formatted_end']; ?></div>
                                                    <div class="shift-duration"><?php echo $duration; ?></div>
                                                </td>

                                                <td>
                                                    <div><?php echo $row['formatted_start_date']; ?></div>
                                                    <?php if ($row['formatted_start_date'] != $row['formatted_end_date']): ?>
                                                        <div class="text-muted">to <?php echo $row['formatted_end_date']; ?></div>
                                                    <?php endif; ?>
                                                    <div class="text-muted"><?php echo $row['total_schedules'] ?? '1'; ?> schedules</div>
                                                </td>

                                                <td>
                                                    <?php if ($is_associate_inactive): ?>
                                                        <span class="day-badge status-badge-inactive">Associate Inactive</span>
                                                        <br><small class="text-muted">All schedules inactive</small>
                                                    <?php elseif ($all_active): ?>
                                                        <span class="day-badge status-badge-active">All Active</span>
                                                        <br><small class="text-muted"><?php echo $active_in_group; ?>/<?php echo $total_in_group; ?> active</small>
                                                    <?php elseif ($some_active): ?>
                                                        <span class="day-badge status-badge-mixed">Some Active</span>
                                                        <br><small class="text-muted"><?php echo $active_in_group; ?>/<?php echo $total_in_group; ?> active</small>
                                                    <?php else: ?>
                                                        <span class="day-badge status-badge-inactive">All Inactive</span>
                                                        <br><small class="text-muted"><?php echo $active_in_group; ?>/<?php echo $total_in_group; ?> active</small>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo $row['formatted_updated'] ?? 'N/A'; ?>
                                                    </small>
                                                </td>

                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary"
                                                            onclick="viewGroupDetails('<?php echo $row['associate_number']; ?>', 
                                                                  '<?php echo $row['formatted_start']; ?>', 
                                                                  '<?php echo $row['formatted_end']; ?>',
                                                                  <?php echo $is_associate_inactive ? 'true' : 'false'; ?>)"
                                                            title="View All Schedules in This Group"
                                                            <?php echo $is_associate_inactive ? 'disabled' : ''; ?>>
                                                            <i class="bi bi-eye"></i> View
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <i class="bi bi-calendar-x" style="font-size: 3rem; color: #95a5a6;"></i>
                                                <h5 class="mt-3">No schedules found</h5>
                                                <p class="text-muted">Try adjusting your filters or create new schedules.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
        </section>
    </main>

    <!-- Modal for Viewing Group Schedules -->
    <div class="modal fade" id="groupScheduleModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Schedule Group Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="group-schedule-details">
                    Loading...
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for associate search
            $('#associate-select').select2({
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
                            results: data.results || []
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Search associate...',
                allowClear: true,
                theme: 'bootstrap-5'
            }).on('select2:select', function(e) {
                $('#associate-filter').val(e.params.data.id);
            }).on('select2:clear', function() {
                $('#associate-filter').val('');
            });
        });

        function viewGroupDetails(associateNumber, startTime, endTime, isAssociateInactive) {
            if (isAssociateInactive) {
                alert('Cannot view details for inactive associate');
                return;
            }

            $('#group-schedule-details').html('<div class="text-center p-4"><div class="spinner-border" role="status"></div><p class="mt-2">Loading group schedules...</p></div>');
            var modal = new bootstrap.Modal(document.getElementById('groupScheduleModal'));
            modal.show();

            $.ajax({
                url: 'get_schedule_details.php',
                method: 'GET',
                data: {
                    associate_number: associateNumber,
                    start_time: startTime,
                    end_time: endTime
                },
                success: function(response) {
                    $('#group-schedule-details').html(response);
                },
                error: function() {
                    $('#group-schedule-details').html('<div class="alert alert-danger">Error loading group schedules.</div>');
                }
            });
        }

        function exportToCSV() {
            let csv = [];
            let rows = document.querySelectorAll("table tr");

            // Add headers
            let headers = [];
            document.querySelectorAll("table thead th").forEach(th => {
                headers.push('"' + th.innerText.replace(/"/g, '""') + '"');
            });
            csv.push(headers.join(","));

            // Add data rows
            for (let i = 1; i < rows.length; i++) {
                let row = [],
                    cols = rows[i].querySelectorAll("td");
                if (cols.length === 0) continue;

                for (let j = 0; j < cols.length; j++) {
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                }
                csv.push(row.join(","));
            }

            // Download CSV
            let csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
            let encodedUri = encodeURI(csvContent);
            let link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "shift_schedules_" + new Date().toISOString().slice(0, 10) + ".csv");
            document.body.appendChild(link);
            link.click();
        }
    </script>
    <script>
        $(document).ready(function() {
            <?php if ($data_result && pg_num_rows($data_result) > 0): ?>
                $('#table-id').DataTable({
                    order: [],
                    columnDefs: [{
                            orderable: false,
                            targets: 6
                        } // Actions column
                    ]
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>