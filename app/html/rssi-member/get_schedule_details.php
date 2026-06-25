<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    echo "Access denied";
    exit;
}

$associate_number = $_GET['associate_number'] ?? '';
$start_time = $_GET['start_time'] ?? '';
$end_time = $_GET['end_time'] ?? '';

if (empty($associate_number) || empty($start_time) || empty($end_time)) {
    echo "<div class='alert alert-danger'>Invalid parameters</div>";
    exit;
}

// Get associate info including status
$assoc_query = "SELECT *, 
                       effectivedate, 
                       filterstatus 
                FROM rssimyaccount_members 
                WHERE associatenumber = $1";
$assoc_result = pg_query_params($con, $assoc_query, [$associate_number]);
$associate = pg_fetch_assoc($assoc_result);

// Check if associate is inactive
$is_associate_inactive = false;
if (!empty($associate['filterstatus']) && $associate['filterstatus'] == 'Inactive' && !empty($associate['effectivedate'])) {
    $effective_date = strtotime($associate['effectivedate']);
    $current_date = strtotime(date('Y-m-d'));
    if ($effective_date <= $current_date) {
        $is_associate_inactive = true;
    }
}

// Get all schedules for this associate with same timing (current group)
$query = "SELECT s.*, 
                 TO_CHAR(s.reporting_time, 'HH12:MI AM') as formatted_start,
                 TO_CHAR(s.exit_time, 'HH12:MI AM') as formatted_end,
                 TO_CHAR(s.start_date, 'DD-Mon-YYYY') as formatted_date,
                 TO_CHAR(s.updated_at, 'DD-Mon-YYYY HH12:MI AM') as updated_formatted,
                 s.start_date as original_date,
                 s.end_date as schedule_end_date
          FROM associate_schedule_v2 s
          WHERE s.associate_number = $1 
            AND TO_CHAR(s.reporting_time, 'HH12:MI AM') = $2 
            AND TO_CHAR(s.exit_time, 'HH12:MI AM') = $3
          ORDER BY s.start_date, 
                   CASE s.workday 
                       WHEN 'Mon' THEN 1
                       WHEN 'Tue' THEN 2
                       WHEN 'Wed' THEN 3
                       WHEN 'Thu' THEN 4
                       WHEN 'Fri' THEN 5
                       WHEN 'Sat' THEN 6
                       WHEN 'Sun' THEN 7
                   END";

$result = pg_query_params($con, $query, [$associate_number, $start_time, $end_time]);

// Get ALL schedules for this associate to find latest date and end_date for each workday
$latest_dates_query = "SELECT workday, MAX(start_date) as latest_date, end_date
                       FROM associate_schedule_v2 
                       WHERE associate_number = $1
                       GROUP BY workday, end_date";
$latest_dates_result = pg_query_params($con, $latest_dates_query, [$associate_number]);

// Create array of latest dates and end_dates for each workday
$latest_dates = [];
while ($row = pg_fetch_assoc($latest_dates_result)) {
    $workday = $row['workday'];
    // Only keep the latest date for each workday
    if (!isset($latest_dates[$workday]) || $row['latest_date'] > $latest_dates[$workday]['latest_date']) {
        $latest_dates[$workday] = [
            'latest_date' => $row['latest_date'],
            'end_date' => $row['end_date']
        ];
    }
}

// Store current group schedules
$schedules = [];
while ($row = pg_fetch_assoc($result)) {
    $schedules[] = $row;
}

$schedules_count = count($schedules);

$day_names = [
    'Mon' => 'Monday',
    'Tue' => 'Tuesday',
    'Wed' => 'Wednesday',
    'Thu' => 'Thursday',
    'Fri' => 'Friday',
    'Sat' => 'Saturday',
    'Sun' => 'Sunday'
];
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4>Schedule Group Details</h4>
            <div class="card">
                <div class="card-body">
                    <h5>
                        <?php echo htmlspecialchars($associate['fullname'] ?? ''); ?>
                        (ID: <?php echo htmlspecialchars($associate_number); ?>)
                        <?php if ($is_associate_inactive): ?>
                            <span class="badge bg-danger">Inactive Associate</span>
                        <?php endif; ?>
                    </h5>
                    <p class="mb-1"><strong>Shift Time:</strong> <?php echo htmlspecialchars($start_time); ?> - <?php echo htmlspecialchars($end_time); ?></p>
                    <p><strong>Total Schedules in Group:</strong> <?php echo $schedules_count; ?></p>

                    <?php if ($is_associate_inactive): ?>
                        <div class="alert alert-warning mt-2">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Note:</strong> This associate has been inactive since
                            <?php echo date('d-M-Y', strtotime($associate['effectivedate'])); ?>.
                            All schedules for this associate are considered inactive.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mt-2">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> A schedule is active only if:
                            <ul class="mb-0 mt-1">
                                <li>It has the latest start date for that day of week</li>
                                <li>It has no end date OR the end date is in the future</li>
                            </ul>
                            Older schedules or expired schedules are automatically inactive.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Day</th>
                            <th>Date</th>
                            <th>Shift Time</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($schedules_count > 0): ?>
                            <?php foreach ($schedules as $row):
                                $workday = $row['workday'];
                                $current_date = $row['original_date'];
                                $schedule_end_date = $row['schedule_end_date'] ?? null;
                                
                                // Get latest data for this workday
                                $latest_data = $latest_dates[$workday] ?? null;
                                $latest_date_for_workday = $latest_data['latest_date'] ?? null;
                                $latest_end_date = $latest_data['end_date'] ?? null;

                                // Check if schedule has end_date and it has passed
                                $is_schedule_expired = false;
                                if (!empty($schedule_end_date)) {
                                    $end_date_timestamp = strtotime($schedule_end_date);
                                    $current_date_timestamp = strtotime(date('Y-m-d'));
                                    if ($end_date_timestamp < $current_date_timestamp) {
                                        $is_schedule_expired = true;
                                    }
                                }

                                // Check if this is the latest version
                                $is_latest_version = ($latest_date_for_workday && $current_date == $latest_date_for_workday);

                                // Schedule is active only if:
                                // 1. It's the latest version AND
                                // 2. Associate is active AND
                                // 3. (No end_date OR end_date is in the future or today)
                                $is_schedule_active = $is_latest_version && !$is_associate_inactive && !$is_schedule_expired;
                            ?>
                                <tr class="<?php echo !$is_schedule_active ? 'table-secondary' : ''; ?>">
                                    <td>
                                        <strong><?php echo $row['workday']; ?></strong><br>
                                        <small class="text-muted"><?php echo $day_names[$workday] ?? $workday; ?></small>
                                    </td>
                                    <td>
                                        <?php echo $row['formatted_date']; ?>
                                        <?php 
                                        // Show "Latest" ONLY if there's a newer version (this is NOT the latest version)
                                        if (!$is_latest_version && $latest_date_for_workday && !$is_associate_inactive): 
                                        ?>
                                            <br><small class="text-danger">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                Latest: <?php echo date('d-M-Y', strtotime($latest_date_for_workday)); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['formatted_start']; ?> - <?php echo $row['formatted_end']; ?></td>
                                    <td>
                                        <?php if ($is_associate_inactive): ?>
                                            <span class="badge bg-secondary">Associate Inactive</span>
                                        <?php elseif ($is_schedule_expired): ?>
                                            <span class="badge bg-danger">Expired</span>
                                            <br><small class="text-danger">
                                                <i class="bi bi-clock-history"></i>
                                                Expired on <?php echo date('d-M-Y', strtotime($schedule_end_date)); ?>
                                            </small>
                                        <?php elseif ($is_schedule_active): ?>
                                            <span class="badge bg-success">Active</span>
                                            <?php if (!empty($schedule_end_date)): ?>
                                                <br><small class="text-muted">
                                                    Expires: <?php echo date('d-M-Y', strtotime($schedule_end_date)); ?>
                                                </small>
                                            <?php else: ?>
                                                <br><small class="text-muted">No end date</small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                            <?php if ($latest_date_for_workday && !$is_schedule_expired): ?>
                                                <br><small class="text-muted">Newer schedule exists</small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $row['updated_formatted']; ?></td>
                                    <td>
                                        <?php if ($is_schedule_active): ?>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editSchedule('<?php echo $row['id']; ?>')">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-primary" disabled title="Inactive schedules cannot be edited">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No schedules found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12 text-end">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>

<script>
    function editSchedule(id) {
        window.location.href = 'edit_schedule.php?id=' + id;
    }
</script>