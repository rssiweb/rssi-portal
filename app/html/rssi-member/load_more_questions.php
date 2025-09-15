<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Ensure the user is logged in
if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get current date
$currentDate = date('Y-m-d');

// Get user role and ID
$role = $role;
$associatenumber = $associatenumber;

// Get parameters from request
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$questionIdFilter = $_GET['question_ids'] ?? '';
$dateFromFilter = $_GET['date_from'] ?? $currentDate;
$dateToFilter = $_GET['date_to'] ?? $currentDate;
$ignoreDate = isset($_GET['ignore_date']) && $_GET['ignore_date'] == '1';

// Build WHERE clause
$whereClauses = [];

// Category filter applies to all
if ($categoryFilter) {
    $whereClauses[] = "q.category_id = '$categoryFilter'";
}

// Question ID filter
if ($questionIdFilter) {
    $ids = explode(',', $questionIdFilter);
    $sanitizedIds = array_map('intval', $ids);
    $idsString = implode(',', $sanitizedIds);
    $whereClauses[] = "q.id IN ($idsString)";
}

// Status filter (only if valid value is selected)
if ($statusFilter === 't' || $statusFilter === 'f') {
    $whereClauses[] = "q.is_active = '$statusFilter'";
}

// Date filter logic
if ($role !== 'Admin' || ($role === 'Admin' && !$ignoreDate)) {
    $whereClauses[] = "q.created_at >= '$dateFromFilter 00:00:00' AND q.created_at <= '$dateToFilter 23:59:59.999999'";
}

// For non-admins, restrict by creator
if ($role !== 'Admin') {
    $whereClauses[] = "q.created_by = '$associatenumber'";
}

$whereSql = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Query to get more questions
$query = "
    SELECT q.id, q.question_text, q.correct_option, q.created_at, q.created_by, q.is_active, 
           c.id AS category_id, c.name AS category_name
    FROM test_questions q
    LEFT JOIN test_categories c ON q.category_id = c.id
    $whereSql
    ORDER BY q.created_at DESC
    LIMIT $limit OFFSET $offset
";

$result = pg_query($con, $query);
$questions = [];

if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        // Fetch options for each question
        $optionsQuery = "SELECT option_key, option_text FROM test_options WHERE question_id = $1 ORDER BY option_key";
        $optionsResult = pg_query_params($con, $optionsQuery, array($row['id']));
        $options = [];
        while ($optionRow = pg_fetch_assoc($optionsResult)) {
            $options[] = $optionRow;
        }

        // Format the created_at date
        $createdAt = new DateTime($row['created_at']);
        $row['created_at_formatted'] = $createdAt->format('d/m/Y h:i A');
        $row['options'] = $options;

        $questions[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'questions' => $questions,
    'offset' => $offset,
    'limit' => $limit
]);
