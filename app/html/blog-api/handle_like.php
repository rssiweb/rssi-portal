<?php
require_once __DIR__ . "/../../bootstrap.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$post_id = (int)$_POST['post_id'];
$user_id = $_SESSION['user_id'] ?? $_POST['user_id'];
$action = $_POST['action']; // 'like' or 'unlike'

if (!$post_id || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

if ($action === 'like') {
    // Check if already liked
    $checkSql = "SELECT id FROM blog_likes WHERE post_id = $1 AND user_id = $2";
    $checkResult = pg_query_params($con, $checkSql, [$post_id, $user_id]);

    if (pg_num_rows($checkResult) === 0) {
        $insertSql = "INSERT INTO blog_likes (post_id, user_id) 
                      VALUES ($1, $2)";
        $result = pg_query_params($con, $insertSql, [$post_id, $user_id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Liked successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to like']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Already liked']);
    }
} else if ($action === 'unlike') {
    $deleteSql = "DELETE FROM blog_likes WHERE post_id = $1 AND user_id = $2";
    $result = pg_query_params($con, $deleteSql, [$post_id, $user_id]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Unliked successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to unlike']);
    }
}
