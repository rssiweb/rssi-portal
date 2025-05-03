<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Get parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = isset($_GET['search']) ? pg_escape_string($con, $_GET['search']) : '';
$author = isset($_GET['author']) ? pg_escape_string($con, $_GET['author']) : '';
$publisher = isset($_GET['publisher']) ? pg_escape_string($con, $_GET['publisher']) : '';
$category = isset($_GET['search_category']) ? pg_escape_string($con, $_GET['search_category']) : '';

// Calculate limits
$initial_limit = 10;
$load_more_limit = 10;
$limit = ($page == 1) ? $initial_limit : $initial_limit + (($page - 1) * $load_more_limit);

// Build query
$query = "SELECT * FROM books WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (title ILIKE '%$search%' OR description ILIKE '%$search%')";
}
if (!empty($author)) {
    $query .= " AND author = '$author'";
}
if (!empty($publisher)) {
    $query .= " AND publisher = '$publisher'";
}
if (!empty($category)) {
    $query .= " AND category = '$category'";
}

$query .= " ORDER BY title LIMIT $load_more_limit OFFSET " . (($page - 1) * $load_more_limit);
$books_result = pg_query($con, $query);

// Output HTML for new books
while ($book = pg_fetch_assoc($books_result)) {
    // Your book card HTML here (same as in your main file)
    include 'book_card.php'; // Or output directly
}

// Return total count if needed
// $total_books = pg_fetch_result(pg_query($con, "SELECT COUNT(*) FROM books"), 0, 0);
// echo '|||' . $total_books; // For example
?>