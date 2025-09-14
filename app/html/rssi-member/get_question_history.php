<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    echo json_encode([]);
    exit;
}

if (isset($_GET['question_id'])) {
    $questionId = intval($_GET['question_id']);

    $query = "SELECT change_history FROM test_questions WHERE id = $1";
    $result = pg_query_params($con, $query, array($questionId));

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $history = json_decode($row['change_history'], true) ?: [];

        // Sort history by timestamp descending (newest first)
        usort($history, function ($a, $b) {
            return strtotime($b['change_timestamp']) - strtotime($a['change_timestamp']);
        });

        echo json_encode($history);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
