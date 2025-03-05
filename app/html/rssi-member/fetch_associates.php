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

// Prepare the query to fetch associates
$query = "
    SELECT 
        associatenumber, 
        CONCAT(fullname, ' - ', associatenumber) AS text 
    FROM 
        rssimyaccount_members 
    WHERE 
        fullname ILIKE $1 OR 
        associatenumber::text ILIKE $1 
    ORDER BY 
        fullname 
    LIMIT 10"; // Limit results to 10 for performance

// Prepare and execute the query
$stmt = pg_prepare($con, "fetch_associates", $query);
$result = pg_execute($con, "fetch_associates", ["%$searchTerm%"]);

// Fetch the results
$associates = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $associates[] = [
            'id' => $row['associatenumber'], // Value to be submitted
            'text' => $row['text'] // Display text in the dropdown
        ];
    }
}

// Return the results as JSON
header('Content-Type: application/json');
echo json_encode(['results' => $associates]);