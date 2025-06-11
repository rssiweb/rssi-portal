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
$isShiftPlanner = $_GET['isShiftPlanner'] ?? false;
$isMycertificate = $_GET['isMycertificate'] ?? false;

// Prepare the base query
$query = "
    SELECT 
        associatenumber, 
        CONCAT(fullname, ' - ', associatenumber) AS text 
    FROM 
        rssimyaccount_members 
    WHERE 
        (fullname ILIKE $1 OR 
        associatenumber::text ILIKE $1)";

// Add filter for Active status only if it's the shift planner request
if ($isShiftPlanner || $isMycertificate) {
    $query .= " AND filterstatus = 'Active'";
}

$query .= " ORDER BY fullname LIMIT 10";

// Prepare and execute the query
$stmt = pg_prepare($con, "fetch_associates", $query);
$result = pg_execute($con, "fetch_associates", ["%$searchTerm%"]);

// Fetch the results
$associates = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $associates[] = [
            'id' => $row['associatenumber'],
            'text' => $row['text']
        ];
    }
}

// Return the results as JSON
header('Content-Type: application/json');
echo json_encode(['results' => $associates]);