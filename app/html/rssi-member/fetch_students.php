<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
}

validation();

// Get the search term from the AJAX request
$searchTerm = $_GET['q'] ?? '';
$isActive = $_GET['isActive'] ?? false;
// $isMycertificate = $_GET['isMycertificate'] ?? false;

// Prepare the base query
$query = "
    SELECT 
        student_id, 
        CONCAT(studentname, ' - ', student_id) AS text 
    FROM 
        rssimyprofile_student 
    WHERE 
        (studentname ILIKE $1 OR 
        student_id::text ILIKE $1)";

// Add filter for Active status only if it's the shift planner request
if ($isActive) {
    $query .= " AND filterstatus = 'Active'";
}

$query .= " ORDER BY studentname LIMIT 10";

// Prepare and execute the query
$stmt = pg_prepare($con, "fetch_students", $query);
$result = pg_execute($con, "fetch_students", ["%$searchTerm%"]);

// Fetch the results
$students = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $students[] = [
            'id' => $row['student_id'],
            'text' => $row['text']
        ];
    }
}

// Return the results as JSON
header('Content-Type: application/json');
echo json_encode(['results' => $students]);
