<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

header('Content-Type: application/json');

$searchTerm = $_POST['search_term'] ?? '';

try {
    $results = [];

    // Search in students
    $studentQuery = "SELECT student_id as id, studentname as name, class 
                    FROM rssimyprofile_student 
                    WHERE (student_id = $1 OR LOWER(studentname) LIKE LOWER($2))";
    $studentParams = [$searchTerm, '%' . $searchTerm . '%'];
    $studentResult = pg_query_params($con, $studentQuery, $studentParams);

    while ($student = pg_fetch_assoc($studentResult)) {
        $results[] = array_merge($student, ['type' => 'student']);
    }

    // Search in associates
    $associateQuery = "SELECT associatenumber as id, fullname as name 
                      FROM rssimyaccount_members 
                      WHERE (associatenumber = $1 OR LOWER(fullname) LIKE LOWER($2))
                      AND filterstatus = 'Active'
                      AND class != 'Offline'";
    $associateResult = pg_query_params($con, $associateQuery, $studentParams);

    while ($associate = pg_fetch_assoc($associateResult)) {
        $results[] = array_merge($associate, ['type' => 'associate', 'class' => '']);
    }

    if (count($results) > 0) {
        echo json_encode([
            'success' => true,
            'matches' => $results
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No matching active student or associate found. Please check the ID/name or verify the person is active, or manual attendance may be disabled for them.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
