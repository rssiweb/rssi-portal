<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Check if user is logged in
if (!isLoggedIn("aid")) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Validate request
if (!isset($_GET['exam_id']) || empty($_GET['exam_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Exam ID is required']);
    exit;
}

$exam_id = pg_escape_string($con, $_GET['exam_id']);

// Fetch exam details
$query = "SELECT exams.exam_id, exam_type, academic_year, subject, exam_mode, 
                 full_marks_written, full_marks_viva, 
                 exam_date_written, exam_date_viva,
                 teacher_id_written, teacher_id_viva, class
          FROM exams 
          LEFT JOIN exam_marks_data em ON exams.exam_id = em.exam_id
          WHERE exams.exam_id = $1";

$result = pg_query_params($con, $query, [$exam_id]);

if (!$result || pg_num_rows($result) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Exam not found']);
    exit;
}

$exam = pg_fetch_assoc($result);

// Fetch teacher names if available
$teachers = [];
if (!empty($exam['teacher_id_written']) || !empty($exam['teacher_id_viva'])) {
    $teacher_ids = array_filter([$exam['teacher_id_written'], $exam['teacher_id_viva']]);
    $teacher_query = "SELECT associatenumber, fullname 
                      FROM rssimyaccount_members 
                      WHERE associatenumber IN ('" . implode("','", $teacher_ids) . "')";
    $teacher_result = pg_query($con, $teacher_query);
    
    while ($row = pg_fetch_assoc($teacher_result)) {
        $teachers[$row['associatenumber']] = $row['fullname'];
    }
}

// Prepare response data
$response = [
    'exam_id' => $exam['exam_id'],
    'exam_type' => $exam['exam_type'],
    'academic_year' => $exam['academic_year'],
    'subject' => $exam['subject'],
    'exam_mode' => $exam['exam_mode'],
    'full_marks_written' => $exam['full_marks_written'],
    'full_marks_viva' => $exam['full_marks_viva'],
    'exam_date_written' => $exam['exam_date_written'],
    'exam_date_viva' => $exam['exam_date_viva'],
    'class' => $exam['class'],
    'teacher_written' => $teachers[$exam['teacher_id_written']] ?? null,
    'teacher_viva' => $teachers[$exam['teacher_id_viva']] ?? null
];

header('Content-Type: application/json');
echo json_encode($response);