<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'upload_raw_photo') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$associatenumber = $_POST['associatenumber'] ?? null;
$currentUser     = $associatenumber; // logged-in user's associate number

// Security: user can only upload their own photo
if (!$associatenumber || strtoupper($associatenumber) !== strtoupper($currentUser)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You can only upload your own photo.']);
    exit;
}

// Validate file
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['photo'];

// Verify actual MIME
$allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedMimes)) {
    echo json_encode(['success' => false, 'message' => 'Only JPG and PNG images are allowed.']);
    exit;
}

if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File exceeds 2 MB limit.']);
    exit;
}

// ===== Check for existing pending request on raw_photo =====
$pendingCheck = "SELECT workflow_id 
                 FROM hrms_workflow 
                 WHERE associatenumber = $1 
                   AND fieldname = 'raw_photo' 
                   AND reviewer_status = 'Pending' 
                 ORDER BY submission_timestamp DESC 
                 LIMIT 1";
$pendingRes = pg_query_params($con, $pendingCheck, [$associatenumber]);

if ($pendingRes && pg_num_rows($pendingRes) > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'You already have a pending photo change request. Please wait for approval or rejection before uploading a new photo.'
    ]);
    exit;
}

try {
    // ============================================================
    // UPLOAD TO GOOGLE DRIVE
    // Signature: uploadeToDrive($file, $parent, $filename)
    //   $file     → the $_FILES entry array (NOT tmp_name string!)
    //   $parent   → Drive folder ID
    //   $filename → base name WITHOUT extension
    // ============================================================

    // Base filename (no extension — the Drive helper appends it)
    $baseFileName = $associatenumber . '_raw_' . time();

    // ⚠️ Replace this with the actual Google Drive folder ID
    $folderId = '1gv6JnDX5QTzlcZV-CekoherLdKeriH-A';

    // Call with the WHOLE $_FILES array (this is the fix!)
    $driveUrl = uploadeToDrive($file, $folderId, $baseFileName);

    if (empty($driveUrl) || !filter_var($driveUrl, FILTER_VALIDATE_URL)) {
        throw new Exception('Drive upload failed — no valid URL returned.');
    }

    // Update DB — set raw_photo
    $updateQuery = "UPDATE rssimyaccount_members SET raw_photo = $1 WHERE associatenumber = $2";
    $updateResult = pg_query_params($con, $updateQuery, [$driveUrl, $associatenumber]);

    if (!$updateResult) {
        throw new Exception('DB update failed: ' . pg_last_error($con));
    }

    // Log to hrms_workflow for admin approval
    pg_query_params(
        $con,
        "INSERT INTO hrms_workflow 
            (associatenumber, fieldname, submitted_value, submission_timestamp, reviewer_status)
         VALUES ($1, 'raw_photo', $2, NOW(), 'Pending')",
        [$associatenumber, $driveUrl]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Photo uploaded successfully. Awaiting admin approval.',
        'url'     => $driveUrl
    ]);
} catch (Exception $e) {
    error_log('Profile photo upload error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Upload failed: ' . $e->getMessage()
    ]);
}
