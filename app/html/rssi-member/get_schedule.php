<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("schedule_helper.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

validation(); // sets $associatenumber, same as the other pages

$date  = $_GET['date'] ?? '';
$sched = getScheduleForDate($con, $associatenumber, $date); // always the logged-in user

echo json_encode([
    'found'          => (bool)$sched,
    'reporting_time' => $sched['reporting_time'] ?? null,
    'exit_time'      => $sched['exit_time'] ?? null,
]);
