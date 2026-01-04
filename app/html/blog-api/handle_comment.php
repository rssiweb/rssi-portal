<?php
require_once __DIR__ . "/../../bootstrap.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$post_id = (int)$_POST['post_id'];
$parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
$content = trim($_POST['content']);
$user_id = $_SESSION['user_id'] ?? $_POST['user_id'];
$user_name = $_SESSION['user_name'] ?? $_POST['user_name'];
$user_email = $_SESSION['user_email'] ?? $_POST['user_email'];

if (!$post_id || empty($content) || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Clean content
$content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

$sql = "INSERT INTO blog_comments 
        (post_id, parent_id, user_id, user_name, user_email, content, status)
        VALUES ($1, $2, $3, $4, $5, $6, 'approved')";

$result = pg_query_params($con, $sql, [
    $post_id,
    $parent_id,
    $user_id,
    $user_name,
    $user_email,
    $content
]);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Comment posted successfully',
        'comment_id' => pg_last_oid($result)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit comment']);
}
