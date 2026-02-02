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

$searchTerm = trim($_POST['search_term'] ?? '');

// 🔒 Basic validation
if ($searchTerm === '' || strlen($searchTerm) < 2) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter at least 2 characters'
    ]);
    exit;
}

$isIdSearch = ctype_digit($searchTerm);
$results = [];

try {

    /* =======================
       STUDENT SEARCH
    ======================== */

    if ($isIdSearch) {
        // Fast ID search
        $studentQuery = "
            SELECT 
                student_id AS id,
                studentname AS name,
                class,
                filterstatus,
                photourl
            FROM rssimyprofile_student
            WHERE student_id = $1
            LIMIT 5
        ";
        $studentParams = [$searchTerm];
    } else {
        // Prefix name search (indexed, fast)
        $studentQuery = "
            SELECT 
                student_id AS id,
                studentname AS name,
                class,
                filterstatus,
                photourl
            FROM rssimyprofile_student
            WHERE studentname ILIKE $1
            LIMIT 5
        ";
        $studentParams = [$searchTerm . '%'];
    }

    $studentResult = pg_query_params($con, $studentQuery, $studentParams);

    while ($row = pg_fetch_assoc($studentResult)) {
        $results[] = array_merge($row, ['type' => 'student']);
    }

    /* =======================
       ASSOCIATE SEARCH
       (Only if needed)
    ======================== */

    if (count($results) < 10) {

        if ($isIdSearch) {
            $associateQuery = "
                SELECT 
                    associatenumber AS id,
                    fullname AS name,
                    filterstatus,
                    photo
                FROM rssimyaccount_members
                WHERE associatenumber = $1
                  AND filterstatus = 'Active'
                  AND class != 'Offline'
                LIMIT 5
            ";
            $associateParams = [$searchTerm];
        } else {
            $associateQuery = "
                SELECT 
                    associatenumber AS id,
                    fullname AS name,
                    filterstatus,
                    photo
                FROM rssimyaccount_members
                WHERE fullname ILIKE $1
                  AND filterstatus = 'Active'
                  AND class != 'Offline'
                LIMIT 5
            ";
            $associateParams = [$searchTerm . '%'];
        }

        $associateResult = pg_query_params($con, $associateQuery, $associateParams);

        while ($row = pg_fetch_assoc($associateResult)) {
            $row['class'] = '';
            $results[] = array_merge($row, ['type' => 'associate']);
        }
    }

    /* =======================
       RESPONSE
    ======================== */

    if (!empty($results)) {
        echo json_encode([
            'success' => true,
            'matches' => $results
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' =>
            'No matching active student or associate found. Please verify the ID or name.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again.'
    ]);
}
