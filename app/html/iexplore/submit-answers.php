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

// Retrieve user ID, exam ID, and user_exam_id from the POST data
$user_id = isset($data['user_id']) ? $data['user_id'] : null;
$exam_id = isset($data['exam_id']) ? $data['exam_id'] : null;
$user_exam_id = isset($data['user_exam_id']) ? $data['user_exam_id'] : null;
$answers = isset($data['answers']) ? $data['answers'] : [];

if (!$user_id || empty($answers) || !$exam_id || !$user_exam_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing user ID, answers, exam ID, or user_exam_id.']);
    exit;
}

// Initialize score
$score = 0;

// Process each answer (answer insertion should already have happened elsewhere in the flow)
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
        UPDATE test_user_answers
        SET selected_option = $3
        WHERE user_exam_id = $1 AND question_id = $2
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

// Update the test_user_sessions table (end session and set status to inactive)
$query = "
    UPDATE test_user_sessions
    SET session_end = CURRENT_TIMESTAMP, status = 'submitted'
    WHERE user_exam_id = $1 AND status = 'active'
";
pg_query_params($con, $query, array($user_exam_id));

// Fetch the user's email and user_type from the test_users table
$query = "
    SELECT email, user_type
    FROM test_users
    WHERE id = $1
";
$result = pg_query_params($con, $query, array($user_id));
$user_data = pg_fetch_assoc($result);

if ($user_data) {
    $user_email = $user_data['email'];
    $user_type = $user_data['user_type'];

    // Fetch the exam name and course_id from the test_exams table
    $query = "
        SELECT name, total_questions, course_id
        FROM test_exams
        WHERE id = $1
    ";
    $result = pg_query_params($con, $query, array($exam_id));
    $exam_data = pg_fetch_assoc($result);

    if ($exam_data) {
        $exam_name = $exam_data['name'];
        $total_questions = $exam_data['total_questions'];
        $course_id = $exam_data['course_id']; // Fetch the course_id from the exam data

        // Check if the exam has a course_id and the user_type is "rssi-member"
        if (!empty($course_id) && $user_type === 'rssi-member') {
            // Fetch the associatenumber from rssimyaccount_members using the email
            $query = "
                SELECT associatenumber
                FROM rssimyaccount_members
                WHERE email = $1
            ";
            $result = pg_query_params($con, $query, array($user_email));
            $member_data = pg_fetch_assoc($result);

            if ($member_data) {
                $associatenumber = $member_data['associatenumber'];

                // Calculate the f_score (score divided by total questions)
                $f_score = $total_questions > 0 ? ($score / $total_questions) : 0;

                // Insert data into the wbt_status table using course_id instead of exam_id
                $insert_query = "
                    INSERT INTO wbt_status (associatenumber, courseid, timestamp, f_score, email)
                    VALUES ($1, $2, CURRENT_TIMESTAMP, $3, $4)
                ";
                pg_query_params($con, $insert_query, array($associatenumber, $course_id, $f_score, $user_email));
            }
        }
    }
}

// Return the result as JSON
echo json_encode(['score' => $score]);
