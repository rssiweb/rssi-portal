<?php
require_once __DIR__ . "/../../bootstrap.php"; // Include the database connection

// Get the application number from the query parameter
$applicationNumber = $_GET['application_number'] ?? null;

if ($applicationNumber) {
    // Fetch rtet_session_id from the signup table
    $signupQuery = "SELECT rtet_session_id FROM signup WHERE application_number = $1;";
    $signupResult = pg_query_params($con, $signupQuery, [$applicationNumber]);

    if ($signupResult && pg_num_rows($signupResult) > 0) {
        $signupRow = pg_fetch_assoc($signupResult);
        $rtetSessionId = $signupRow['rtet_session_id'];

        if ($rtetSessionId) {
            // Fetch exam details using rtet_session_id
            $examQuery = "SELECT te.name, tus.auth_code, tus.status 
                          FROM test_user_sessions tus
                          JOIN test_user_exams tue ON tus.user_exam_id = tue.id
                          JOIN test_exams te ON tue.exam_id = te.id
                          WHERE tus.id = $1;";
            $examResult = pg_query_params($con, $examQuery, [$rtetSessionId]);

            if ($examResult && pg_num_rows($examResult) > 0) {
                $examRow = pg_fetch_assoc($examResult);
                echo json_encode([
                    'success' => true,
                    'examName' => $examRow['name'],
                    'sessionId' => $rtetSessionId,
                    'otp' => $examRow['auth_code'],
                    'status' => $examRow['status']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Exam not created yet']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Exam not created yet']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Applicant not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid application number']);
}
?>