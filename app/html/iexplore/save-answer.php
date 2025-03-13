<?php
require_once __DIR__ . "/../../bootstrap.php"; // Include your database connection and other dependencies

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Extract data from the request
$user_exam_id = $data['user_exam_id'];
$question_id = $data['question_id'];
$selected_option = $data['selected_option'];
$marked_for_review = $data['marked_for_review'] ? 'true' : 'false';

// Update the selected_option and marked_for_review in the test_user_answers table
$update_query = "
    UPDATE test_user_answers
    SET selected_option = $1, marked_for_review = $2
    WHERE user_exam_id = $3 AND question_id = $4
";
$result = pg_query_params($con, $update_query, array($selected_option, $marked_for_review, $user_exam_id, $question_id));

if (!$result) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Failed to save the answer']);
    exit;
}

// Return a success response
echo json_encode(['success' => true]);
?>