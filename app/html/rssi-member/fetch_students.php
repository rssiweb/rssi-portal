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

// Get the search term from the AJAX request
$searchTerm = $_GET['q'] ?? '';
$isActive = $_GET['isActive'] ?? false;
$isInactive = $_GET['isInactive'] ?? false;
$status = $_GET['status'] ?? null;

// Prepare parameters array
$params = ["%$searchTerm%"];
$query = "
    SELECT 
        student_id, 
        CONCAT(studentname, ' - ', student_id) AS text 
    FROM 
        rssimyprofile_student 
    WHERE 
        (studentname ILIKE $1 OR 
        student_id::text ILIKE $1)";

// Handle status filtering with parameterized queries
if ($isActive && !$isInactive) {
    // Old method: isActive parameter
    $query .= " AND filterstatus = 'Active'";
} elseif (!$isActive && $isInactive) {
    // Old method: isInactive parameter
    $query .= " AND filterstatus = 'Inactive'";
} elseif ($status && ($status === 'Active' || $status === 'Inactive')) {
    // New method: status parameter - safe validation
    $query .= " AND filterstatus = '$status'"; // Safe because we validated $status
}

$query .= " ORDER BY studentname LIMIT 10";

// Prepare and execute the query
$stmt = pg_prepare($con, "fetch_students", $query);
$result = pg_execute($con, "fetch_students", $params);

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
