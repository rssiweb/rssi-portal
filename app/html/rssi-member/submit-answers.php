<?php
// submit-answers.php

require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Ensure the user is logged in
if (!isLoggedIn("aid")) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User is not logged in.']);
    exit;
}

// Get data from the POST request
$data = json_decode(file_get_contents('php://input'), true);

// Validate form type
if (isset($data['form_type']) && $data['form_type'] !== 'exam') {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid form type.']);
    exit;
}

// Retrieve user ID and answers from the POST data
$user_id = isset($data['user_id']) ? $data['user_id'] : null;
$exam_id = isset($data['exam_id']) ? $data['exam_id'] : null;
$answers = isset($data['answers']) ? $data['answers'] : [];

if (!$user_id || empty($answers) || !$exam_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing user ID, answers or exam ID.']);
    exit;
}

// Initialize score
$score = 0;

// Create a new user exam record to store the user's participation
$query = "
    INSERT INTO test_user_exams (user_id, exam_id)
    VALUES ($1, $2) RETURNING id
";
$result = pg_query_params($con, $query, array($user_id, $exam_id));
$user_exam_id = pg_fetch_assoc($result)['id']; // Get the ID of the newly inserted user exam record

// Process each answer
foreach ($answers as $answer) {
    $question_id = $answer['question_id'];
    $selected_option = $answer['selected_option'];

    // Fetch the correct answer for the question from the database
    $query = "
        SELECT correct_option
        FROM test_questions
        WHERE id = $1
    ";
    $result = pg_query_params($con, $query, array($question_id));
    $correct_answer = pg_fetch_assoc($result)['correct_option'];

    // Check if the selected option is correct
    $is_correct = ($selected_option === $correct_answer);

    // Insert the answer into the database
    $query = "
        INSERT INTO test_user_answers (user_exam_id, question_id, selected_option)
        VALUES ($1, $2, $3)
    ";
    pg_query_params($con, $query, array($user_exam_id, $question_id, $selected_option));

    // Increment the score if the answer is correct
    if ($is_correct) {
        $score++;
    }
}

// Update the user's score in the test_user_exams table
$query = "
    UPDATE test_user_exams
    SET score = $1
    WHERE id = $2
";
pg_query_params($con, $query, array($score, $user_exam_id));

// Return the result as JSON
echo json_encode(['score' => $score]);
