<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/drive.php");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Check if image was uploaded
if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'No image uploaded']);
    exit;
}

$uploadedFile = $_FILES['image'];

// Validate file
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
$max_size = 10 * 1024 * 1024; // 10MB

if ($uploadedFile['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 10MB']);
    exit;
}

if (!in_array($uploadedFile['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, WebP allowed']);
    exit;
}

// Generate filename
$extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
$filename = 'blog_' . time() . '_' . uniqid() . '.' . $extension;

// Upload to Google Drive
// Replace with your actual Drive folder ID for blog content images
$parent_folder_id = '1lhVTEMKD7ItMjPjYCow-cWboq0jOo-Ut';

$drive_url = uploadeToDrive($uploadedFile, $parent_folder_id, $filename);

if ($drive_url) {
    // Return URL for Summernote editor
    echo json_encode([
        'success' => true,
        'url' => $drive_url
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to upload image to Drive']);
}
