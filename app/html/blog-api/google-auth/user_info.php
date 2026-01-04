<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
// CORS headers - Add these at the very beginning
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if (isLoggedIn()) {
    echo json_encode([
        'success' => true,
        'user' => getCurrentUser()
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Not logged in'
    ]);
}
?>