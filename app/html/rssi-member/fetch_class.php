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

$searchTerm = $_GET['q'] ?? '';

$query = "
    SELECT 
        class_name, value 
    FROM 
        school_classes 
    WHERE 
        (class_name ILIKE $1 OR 
         value ILIKE $1)
    ORDER BY id
    LIMIT 10";

$stmt = pg_prepare($con, "fetch_class", $query);
$result = pg_execute($con, "fetch_class", ["%$searchTerm%"]);

$classes = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $classes[] = [
            'id' => $row['value'],
            'text' => $row['class_name'] . ' (' . $row['value'] . ')'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode(['results' => $classes]);
