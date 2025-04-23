<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (empty($_POST['category'])) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit;
}

$category = pg_escape_string($con, trim($_POST['category']));

// Check if category already exists
$check_query = "SELECT 1 FROM books WHERE category = '$category' LIMIT 1";
$exists = pg_fetch_assoc(pg_query($con, $check_query));

if ($exists) {
    echo json_encode(['success' => true, 'message' => 'Category already exists']);
    exit;
}

// Update one book to add the category (or implement a proper categories table)
$update_query = "UPDATE books SET category = '$category' WHERE book_id = (
    SELECT book_id FROM books WHERE category IS NULL OR category = '' LIMIT 1
)";
$result = pg_query($con, $update_query);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Category added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add category']);
}