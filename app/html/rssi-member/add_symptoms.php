<?php
require_once __DIR__ . "/../../bootstrap.php";

// Store the referring URL with parameters before processing
if (!isset($_SESSION['referrer'])) {
    $_SESSION['referrer'] = $_SERVER['HTTP_REFERER'] ?? 'student_period_records';
}

header('Content-Type: application/json');

// Validate input
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['record_id']) || !isset($_POST['symptoms'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid request']));
}

$recordId = $_POST['record_id'];
$newSymptoms = trim($_POST['symptoms']);

if (empty($newSymptoms)) {
    http_response_code(400);
    die(json_encode(['error' => 'Symptoms cannot be empty']));
}

try {
    // Get existing symptoms
    $result = pg_query_params(
        $con,
        "SELECT symptoms FROM student_period_records WHERE id = $1",
        [$recordId]
    );

    if (!$result) {
        throw new Exception(pg_last_error($con));
    }

    $row = pg_fetch_assoc($result);
    $existingSymptoms = $row['symptoms'] ?? '';

    // Prepare updated symptoms with timestamp
    $timestamp = date('d-M-Y h:i A');
    $updatedSymptoms = $existingSymptoms;

    if (!empty($existingSymptoms)) {
        $updatedSymptoms .= "\n\n";
    }

    $updatedSymptoms .= "[" . $timestamp . "]\n" . $newSymptoms;

    // Update record
    $updateResult = pg_query_params(
        $con,
        "UPDATE student_period_records SET symptoms = $1 WHERE id = $2",
        [$updatedSymptoms, $recordId]
    );

    if (!$updateResult) {
        throw new Exception(pg_last_error($con));
    }

    echo json_encode([
        'success' => true,
        'message' => 'Symptoms added successfully',
        'redirect_url' => $_SESSION['referrer'] // Send back the original URL
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
