<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    die("Unauthorized access");
}

$month = $_GET['month'] ?? date('Y-m');
$location = $_GET['location'] ?? '';
$academic_year = $_GET['academic_year'] ?? '';

// Parse month and year
list($year, $month_num) = explode('-', $month);
$first_day = date('Y-m-01', strtotime($month));
$last_day = date('Y-m-t', strtotime($month));
$days_in_month = date('t', strtotime($month));

// Get all cleaning records for the selected month
$query = "SELECT wc.*, 
          a.fullname as cleaner_name,
          s.fullname as submitted_by_name
          FROM washroom_cleaning wc
          LEFT JOIN rssimyaccount_members a ON wc.cleaner_id = a.associatenumber
          LEFT JOIN rssimyaccount_members s ON wc.submitted_by = s.associatenumber
          WHERE wc.cleaning_date BETWEEN '$first_day' AND '$last_day'
          AND wc.current_status != 'REJECTED'";

// Add location filter if specified
if (!empty($location)) {
    $query .= " AND wc.washroom_location = '$location'";
}

// Add academic year filter
if (!empty($academic_year)) {
    list($start_year, $end_year) = explode('-', $academic_year);
    $start_year = (int)$start_year;
    $end_year = (int)$end_year;

    $query .= " AND (
        (EXTRACT(MONTH FROM wc.cleaning_date) >= 4 
        AND EXTRACT(YEAR FROM wc.cleaning_date) = $start_year)
        OR
        (EXTRACT(MONTH FROM wc.cleaning_date) < 4 
        AND EXTRACT(YEAR FROM wc.cleaning_date) = $end_year)
    )";
}

$query .= " ORDER BY wc.cleaning_date, wc.washroom_location";

$result = pg_query($con, $query);
$cleaning_data = [];

if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        $cleaning_date = $row['cleaning_date'];
        $location_name = $row['washroom_location'];
        $status = $row['current_status'];

        if (!isset($cleaning_data[$cleaning_date])) {
            $cleaning_data[$cleaning_date] = [];
        }

        $cleaning_data[$cleaning_date][$location_name] = [
            'status' => $status,
            'cleaner_name' => $row['cleaner_name'],
            'is_cleaned' => $row['is_cleaned'],
            'is_sanitized' => $row['is_sanitized'],
            'is_restocked' => $row['is_restocked'],
            'issues_found' => $row['issues_found']
        ];
    }
}

// Get unique locations for the header
$all_locations = [];
foreach ($cleaning_data as $date => $locations) {
    $all_locations = array_merge($all_locations, array_keys($locations));
}
$unique_locations = array_unique($all_locations);
sort($unique_locations);

// If no specific location filter, use all available locations
if (empty($location) && empty($unique_locations)) {
    $unique_locations = [
        'Ground Floor - Student Washroom',
        'Ground Floor - Teacher Washroom'
    ];
}

function getStatusBadge($status)
{
    switch ($status) {
        case 'FINAL_APPROVED':
            return '<span class="badge bg-success" title="Completed">✓</span>';
        case 'LEVEL2_APPROVED':
        case 'LEVEL1_APPROVED':
        case 'SUBMITTED':
            return '<span class="badge bg-warning" title="In Progress">⌛</span>';
        default:
            return '<span class="badge bg-secondary" title="Not Cleaned">-</span>';
    }
}

function getStatusTooltip($data, $location, $date)
{
    if (!$data) {
        return "No cleaning record for $location on " . date('M j, Y', strtotime($date));
    }

    $status_text = ucfirst(strtolower(str_replace('_', ' ', $data['status'])));
    $tooltip = "<strong>$location</strong><br>";
    $tooltip .= "Date: " . date('M j, Y', strtotime($date)) . "<br>";
    $tooltip .= "Status: $status_text<br>";
    $tooltip .= "Cleaner: " . htmlspecialchars($data['cleaner_name']) . "<br>";
    $tooltip .= "Tasks: ";

    $tasks = [];
    if ($data['is_cleaned'] == 't') $tasks[] = "Cleaned";
    if ($data['is_sanitized'] == 't') $tasks[] = "Sanitized";
    if ($data['is_restocked'] == 't') $tasks[] = "Restocked";

    $tooltip .= $tasks ? implode(', ', $tasks) : 'None';

    if (!empty($data['issues_found'])) {
        $tooltip .= "<br>Issues: " . htmlspecialchars($data['issues_found']);
    }

    return $tooltip;
}
?>

<div class="calendar-container">
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <?php foreach ($unique_locations as $loc): ?>
                        <th><?= htmlspecialchars($loc) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                    <?php
                    $current_date = sprintf('%s-%02d', $month, $day);
                    $is_weekend = in_array(date('w', strtotime($current_date)), [0, 6]);
                    $row_class = $is_weekend ? 'table-secondary' : '';
                    ?>
                    <tr class="<?= $row_class ?>">
                        <td class="fw-bold"><?= $day ?></td>
                        <td class="text-muted"><?= date('D', strtotime($current_date)) ?></td>
                        <?php foreach ($unique_locations as $loc): ?>
                            <?php
                            $data = $cleaning_data[$current_date][$loc] ?? null;
                            $status_badge = getStatusBadge($data ? $data['status'] : '');
                            $tooltip = getStatusTooltip($data, $loc, $current_date);
                            ?>
                            <td class="text-center">
                                <span data-bs-toggle="tooltip" data-bs-html="true" title="<?= htmlspecialchars($tooltip) ?>">
                                    <?= $status_badge ?>
                                </span>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <div class="legend mt-3">
        <h6>Legend:</h6>
        <div class="d-flex gap-3">
            <span class="badge bg-success">✓ Completed</span>
            <span class="badge bg-warning">⌛ In Progress</span>
            <span class="badge bg-secondary">- Not Cleaned</span>
            <span class="text-muted"><i>Gray rows indicate weekends</i></span>
        </div>
    </div>

    <?php if (empty($cleaning_data)): ?>
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle"></i> No cleaning records found for <?= date('F Y', strtotime($month)) ?>.
        </div>
    <?php endif; ?>
</div>

<script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>