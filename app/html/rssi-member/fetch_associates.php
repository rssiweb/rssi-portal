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
$isShiftPlanner = $_GET['isShiftPlanner'] ?? false;
$isMycertificate = $_GET['isMycertificate'] ?? false;
$isActive = $_GET['isActive'] ?? false;
$restrictToSupervisor = isset($_GET['restrictToSupervisor']) ? filter_var($_GET['restrictToSupervisor'], FILTER_VALIDATE_BOOLEAN) : false;

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

// Parameters array
$params = ["%$searchTerm%"];

// Add filter for Active status only if it's the shift planner request
if ($isShiftPlanner || $isMycertificate || $isActive) {
    $query .= " AND filterstatus = 'Active'";
}

// For Non-Admin users AND when restrictToSupervisor is enabled, restrict to themselves and their reportees
if ($role !== 'Admin' && $restrictToSupervisor === true) {
    // Use IN with a subquery that returns the allowed associates (self + reportees)
    $query .= " AND associatenumber IN (
        SELECT associatenumber FROM rssimyaccount_members 
        WHERE associatenumber = $" . (count($params) + 1) . " 
        OR supervisor = $" . (count($params) + 1) . "
    )";
    $params[] = $associatenumber;
}

$query .= " ORDER BY fullname LIMIT 10";

// Prepare and execute the query
$stmt = pg_prepare($con, "fetch_associates_query", $query);
if ($stmt) {
    $result = pg_execute($con, "fetch_associates_query", $params);

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
} else {
    $associates = [];
}

// Return the results as JSON
header('Content-Type: application/json');
echo json_encode(['results' => $associates]);
