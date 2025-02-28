<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
}

validation();

$searchTerm = $_GET['searchTerm'] ?? '';

$query = "SELECT courseid, coursename FROM wbt WHERE coursename ILIKE '%$searchTerm%' ORDER BY coursename LIMIT 10";
$result = pg_query($con, $query);

$data = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $data[] = [
            "id" => $row['courseid'],
            "text" => $row['courseid'] . ' - ' . $row['coursename']
        ];
    }
}

echo json_encode($data);
?>