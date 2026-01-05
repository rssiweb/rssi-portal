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

if (!$post_id || empty($content) || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// Clean content
$content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

$sql = "WITH inserted_comment AS (
    INSERT INTO blog_comments (
        post_id,
        parent_id,
        user_id,
        content,
        status
    )
    VALUES ($1, $2, $3, $4, 'approved')
    RETURNING
        id,
        post_id,
        parent_id,
        user_id,
        content,
        status,
        created_at
)
SELECT
    ic.id,
    ic.post_id,
    ic.parent_id,
    ic.user_id,
    u.name  AS user_name,
    u.email AS user_email,
    ic.content,
    ic.status,
    ic.created_at
FROM inserted_comment ic
LEFT JOIN blog_users u
    ON u.id = ic.user_id;
";

$result = pg_query_params($con, $sql, [
    $post_id,
    $parent_id,
    $user_id,
    $content
]);

if ($result) {
    $row = pg_fetch_assoc($result);
    echo json_encode([
        'success' => true,
        'message' => 'Comment posted successfully',
        'comment_id' => $row['id'],
        'comment' => [
            'id' => $row['id'],
            'user_name' => $row['user_name'],
            'content' => $row['content'],
            'created_at' => $row['created_at'],
            'replies' => []
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit comment']);
}
