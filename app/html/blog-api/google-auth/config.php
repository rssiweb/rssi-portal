<?php
require_once __DIR__ . '/../../bootstrap.php';

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '880893711562-48c591om401pva696dnk9ffnqb9it4mm.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// Start session
session_start();

function verifyGoogleToken($idToken) {
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'picture' => $_SESSION['user_picture'] ?? null,
            'google_id' => $_SESSION['google_id']
        ];
    }
    return null;
}
?>