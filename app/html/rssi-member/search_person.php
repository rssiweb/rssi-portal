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

    $query = "
    SELECT *
    FROM (
        SELECT
            student_id AS id,
            studentname AS name,
            class,
            filterstatus,
            photourl AS photo,
            'student' AS type
        FROM public.rssimyprofile_student
        WHERE student_id = $1
           OR studentname ILIKE $2

        UNION ALL

        SELECT
            associatenumber AS id,
            fullname AS name,
            '' AS class,
            filterstatus,
            photo,
            'associate' AS type
        FROM public.rssimyaccount_members
        WHERE (
                associatenumber = $1
             OR fullname ILIKE $2
              )
          AND (class = 'Online' OR class = 'Hybrid')
    ) t
    ORDER BY
        CASE WHEN filterstatus = 'Active' THEN 1 ELSE 2 END,
        name
";
    $params = [
        $searchTerm,
        '%' . $searchTerm . '%'
    ];

    $result = pg_query_params($con, $query, $params);

    while ($row = pg_fetch_assoc($result)) {
        $results[] = $row;
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
