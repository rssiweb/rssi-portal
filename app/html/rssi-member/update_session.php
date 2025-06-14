<?php
require_once __DIR__ . "/../../bootstrap.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['itemsPerPage'])) {
    $itemsPerPage = max(5, min(100, intval($_POST['itemsPerPage'])));
    $_SESSION['emart_items_per_page'] = $itemsPerPage;
    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error']);
?>