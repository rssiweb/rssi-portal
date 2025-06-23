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

// Get the search term
$searchTerm = $_GET['q'] ?? '';

$query = "
    SELECT 
        category_name, category_value 
    FROM 
        school_categories 
    WHERE 
        (category_name ILIKE $1 OR category_value ILIKE $1)
    ORDER BY id
    LIMIT 10";

$stmt = pg_prepare($con, "fetch_category", $query);
$result = pg_execute($con, "fetch_category", ["%$searchTerm%"]);

$categories = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $categories[] = [
            'id' => $row['category_value'],
            'text' => $row['category_name'] . ' (' . $row['category_value'] . ')'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode(['results' => $categories]);
