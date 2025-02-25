<?php
require_once __DIR__ . "/../../bootstrap.php"; // Include your database connection and other dependencies

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate the input data
if (empty($data['user_exam_id']) || empty($data['question_id']) || !isset($data['selected_option'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

// Extract data from the request
$user_exam_id = $data['user_exam_id'];
$question_id = $data['question_id'];
$selected_option = $data['selected_option'];

// Update the selected_option in the test_user_answers table
$update_query = "
    UPDATE test_user_answers
    SET selected_option = $1
    WHERE user_exam_id = $2 AND question_id = $3
";
$result = pg_query_params($con, $update_query, array($selected_option, $user_exam_id, $question_id));

if (!$result) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to save the answer']);
    exit;
}

// Return a success response
echo json_encode(['success' => true]);
