<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Get parameters
$associate = $_GET['associate'] ?? '';
$date = $_GET['date'] ?? '';

if (!$associate || !$date) {
    echo json_encode(['count' => 0]);
    exit;
}

// Get the month and year from the date
$month = date('Y-m', strtotime($date));

// Query to count approved/pending exceptions for the same month
$query = "SELECT COUNT(*) as count FROM exception_requests 
          WHERE submitted_by = '$associate' 
          AND status IN ('Pending', 'Approved')
          AND (TO_CHAR(start_date_time, 'YYYY-MM') = '$month' 
               OR TO_CHAR(end_date_time, 'YYYY-MM') = '$month')";

$result = pg_query($con, $query);

if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);
    echo json_encode(['count' => (int)$row['count']]);
} else {
    echo json_encode(['count' => 0]);
}
