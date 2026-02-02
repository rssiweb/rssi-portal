<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    header('Content-Type: application/json');
    echo json_encode(['leaves' => [], 'stats' => []]);
    exit;
}

// Check admin/supervisor
$is_admin = ($role == 'Admin');
$associatenumber = $associatenumber;

$is_supervisor = false;
$supervisor_check = pg_query($con, "SELECT supervisor FROM rssimyaccount_members WHERE associatenumber='$associatenumber'");
if ($supervisor_check && pg_num_rows($supervisor_check) > 0) {
    $supervisor_data = pg_fetch_assoc($supervisor_check);
    $is_supervisor = !empty($supervisor_data['supervisor']);
}

if (!$is_admin && !$is_supervisor) {
    header('Content-Type: application/json');
    echo json_encode(['leaves' => [], 'stats' => []]);
    exit;
}

// Get date range and target month from POST data
$input = json_decode(file_get_contents('php://input'), true);
$startDate = $input['start'] ?? date('Y-m-d', strtotime('-1 month'));
$endDate = $input['end'] ?? date('Y-m-d', strtotime('+1 month'));
$targetMonth = $input['month'] ?? date('n') - 1; // JavaScript months are 0-11
$targetYear = $input['year'] ?? date('Y');

// Calculate the actual target month (PHP months are 1-12, JavaScript sends 0-11)
$targetMonthPHP = $targetMonth + 1;

// Build query for calendar data (shows ALL leaves without filters for the date range)
$where_conditions = ["l.fromdate <= '$endDate'", "l.todate >= '$startDate'"];

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

// Get leave requests for calendar
$result = pg_query($con, "SELECT l.*, 
                         TO_CHAR(l.fromdate, 'YYYY-MM-DD') as fromdate_formatted,
                         TO_CHAR(l.todate, 'YYYY-MM-DD') as todate_formatted,
                         COALESCE(f.fullname, s.studentname) as applicant_name,
                         r.fullname as reviewer_name
                         FROM leavedb_leavedb l
                         LEFT JOIN rssimyaccount_members f ON l.applicantid = f.associatenumber  
                         LEFT JOIN rssimyprofile_student s ON l.applicantid = s.student_id 
                         LEFT JOIN rssimyaccount_members r ON l.reviewer_id = r.associatenumber
                         WHERE $where_clause
                         ORDER BY l.fromdate, l.applicantid");

$leaves = $result ? pg_fetch_all($result) : [];

// Prepare calendar data and statistics for CURRENT MONTH ONLY
$calendar_data = [];
$stats = [
    'total_days' => 0.0,    // Total days with leaves in target month (decimal)
    'approved_days' => 0.0, // Total approved days in target month (decimal)
    'pending_days' => 0.0,  // Total pending days in target month (decimal)  
    'rejected_days' => 0.0, // Total rejected days in target month (decimal)
    'total_applications' => 0 // Count of database records that affect target month
];

if ($leaves) {
    foreach ($leaves as $leave) {
        $from_date = $leave['fromdate_formatted'] ?: $leave['fromdate'];
        $to_date = $leave['todate_formatted'] ?: $leave['todate'];
        $leave_days = floatval($leave['days']); // Convert to float to handle decimals
        $status = $leave['status'];

        // Calculate total days in this leave period
        $start_timestamp = strtotime($from_date);
        $end_timestamp = strtotime($to_date);
        $total_leave_days = ($end_timestamp - $start_timestamp) / (60 * 60 * 24) + 1;

        // If it's a single day leave with decimal days (like 0.5), use the original value
        if ($total_leave_days == 1 && $leave_days < 1) {
            $days_per_calendar_day = $leave_days;
        } else {
            // For multi-day leaves, distribute the total days evenly across calendar days
            $days_per_calendar_day = $leave_days / $total_leave_days;
        }

        $has_leave_in_target_month = false;

        // Loop through each day from fromdate to todate (inclusive)
        $current_timestamp = $start_timestamp;
        while ($current_timestamp <= $end_timestamp) {
            $date_str = date('Y-m-d', $current_timestamp);
            $current_month = date('n', $current_timestamp); // PHP month (1-12)
            $current_year = date('Y', $current_timestamp);

            // Check if this day falls within the target month
            $is_in_target_month = ($current_month == $targetMonthPHP && $current_year == $targetYear);

            if (!isset($calendar_data[$date_str])) {
                $calendar_data[$date_str] = [];
            }
            $calendar_data[$date_str][] = [
                'leaveid' => $leave['leaveid'],
                'applicantid' => $leave['applicantid'],
                'applicant_name' => $leave['applicant_name'],
                'typeofleave' => $leave['typeofleave'],
                'status' => $status,
                'fromdate' => $from_date,
                'todate' => $to_date,
                'days' => $leave_days,
                'comment' => $leave['comment'],
                'reviewer_name' => $leave['reviewer_name'],
                'shift' => $leave['shift'] ?? '',
                'days_this_date' => $days_per_calendar_day // Add this for accurate counting
            ];

            // Only count statistics for days in the target month
            if ($is_in_target_month) {
                $has_leave_in_target_month = true;

                // Add to total days (with decimal values)
                $stats['total_days'] += $days_per_calendar_day;

                // Add to status-specific totals
                if ($status === 'Approved') {
                    $stats['approved_days'] += $days_per_calendar_day;
                } elseif (in_array($status, ['Pending', 'Under review'])) {
                    $stats['pending_days'] += $days_per_calendar_day;
                } elseif ($status === 'Rejected') {
                    $stats['rejected_days'] += $days_per_calendar_day;
                }
            }

            // Move to next day
            $current_timestamp = strtotime('+1 day', $current_timestamp);
        }

        // Count this application if it affects the target month
        if ($has_leave_in_target_month) {
            $stats['total_applications']++;
        }
    }

    // For backward compatibility (but keep as decimals)
    $stats['total_leaves'] = $stats['total_days'];
    $stats['approved'] = $stats['approved_days'];
    $stats['pending'] = $stats['pending_days'];
    $stats['rejected'] = $stats['rejected_days'];
}

header('Content-Type: application/json');
echo json_encode([
    'leaves' => $calendar_data,
    'stats' => $stats,
    'target_month' => $targetMonthPHP,
    'target_year' => $targetYear
]);
