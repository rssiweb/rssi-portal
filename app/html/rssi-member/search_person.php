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

if ($searchTerm === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter an ID or name'
    ]);
    exit;
}

$results = [];

try {

    /* =====================
       STUDENTS
    ====================== */

    $studentQuery = "
        SELECT
            student_id AS id,
            studentname AS name,
            class,
            filterstatus,
            photourl
        FROM public.rssimyprofile_student
        WHERE student_id = $1
           OR studentname ILIKE $2
        LIMIT 5
    ";

    $studentParams = [
        $searchTerm,
        '%' . $searchTerm . '%'
    ];

    $studentResult = pg_query_params($con, $studentQuery, $studentParams);

    while ($row = pg_fetch_assoc($studentResult)) {
        $results[] = array_merge($row, ['type' => 'student']);
    }

    /* =====================
       ASSOCIATES
    ====================== */

    if (count($results) < 10) {

        $associateQuery = "
            SELECT
                associatenumber AS id,
                fullname AS name,
                filterstatus,
                photo
            FROM public.rssimyaccount_members
            WHERE (
                    associatenumber = $1
                 OR fullname ILIKE $2
                  )
              AND filterstatus = 'Active'
              AND class != 'Offline'
            LIMIT 5
        ";

        $associateParams = [
            $searchTerm,
            '%' . $searchTerm . '%'
        ];

        $associateResult = pg_query_params($con, $associateQuery, $associateParams);

        while ($row = pg_fetch_assoc($associateResult)) {
            $row['class'] = '';
            $results[] = array_merge($row, ['type' => 'associate']);
        }
    }

    /* =====================
       RESPONSE
    ====================== */

    if (!empty($results)) {
        echo json_encode([
            'success' => true,
            'matches' => $results
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' =>
            'No matching active student or associate found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
