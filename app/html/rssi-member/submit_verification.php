<?php
// Turn off all error reporting to prevent HTML output
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

header('Content-Type: application/json');

// Start output buffering
ob_start();

try {
    if (!isLoggedIn("aid")) {
        throw new Exception('Not authenticated');
    }

    $asset_id = $_POST['asset_id'] ?? '';
    $verified_by = $_POST['verified_by'] ?? '';
    $action_type = $_POST['action_type'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    if (!$asset_id || !$verified_by || !$action_type) {
        throw new Exception('Missing required fields');
    }

    $now = date('Y-m-d H:i:s');
    
    pg_query($con, "BEGIN");
    
    // Fetch current asset data
    $current_query = "SELECT * FROM gps WHERE itemid = '$asset_id'";
    $current_result = pg_query($con, $current_query);
    
    if (!$current_result) {
        throw new Exception("Database query failed");
    }
    
    if (pg_num_rows($current_result) == 0) {
        throw new Exception("Asset not found: $asset_id");
    }
    
    $current_asset = pg_fetch_assoc($current_result);
    
    // Initialize variables
    $quantityChanged = false;
    $evidence_photo_path = null;
    
    // Handle file uploads for all action types
    if (!empty($_FILES['verification_photo']['name'])) {
        // Upload evidence photo
        $filename_photo = "evidence_" . $asset_id . "_" . time();
        $parent_photo_path = '19maeFLJUscJcS6k2xwR6Y-Bg6LtHG7NR';
        $evidence_photo_path = uploadeToDrive($_FILES['verification_photo'], $parent_photo_path, $filename_photo);
        
        // If action is 'update', also update GPS table with new photo
        if ($action_type === 'update') {
            $update_photo_query = "UPDATE gps SET asset_photo = '$evidence_photo_path' WHERE itemid = '$asset_id'";
            pg_query($con, $update_photo_query);
        }
    }
    
    // Handle bill upload for 'update' action only
    if ($action_type === 'update' && !empty($_FILES['verification_bill']['name'])) {
        $filename_bill = "bill_" . $asset_id . "_" . time();
        $parent_bill_path = '1TxjIHmYuvvyqe48eg9q_lnsyt1wDq6os';
        $bill_path = uploadeToDrive($_FILES['verification_bill'], $parent_bill_path, $filename_bill);
        
        // Update GPS table with new bill
        $update_bill_query = "UPDATE gps SET purchase_bill = '$bill_path' WHERE itemid = '$asset_id'";
        pg_query($con, $update_bill_query);
    }
    
    // Handle different action types
    if ($action_type === 'verified') {
        // CASE 1: Verified Correct
        $verification_status = 'verified';
        $new_quantity = $current_asset['quantity'];
        $quantityChanged = false;
        $issue_type = null;
        $issue_description = null;
        $update_reason = null;
        $admin_review_status = 'approved'; // Auto-approve verified actions
        
    } elseif ($action_type === 'update') {
        // CASE 2: Update Details
        $new_quantity = $_POST['new_quantity'] ?? $current_asset['quantity'];
        $update_reason = $remarks;
        $quantityChanged = ($new_quantity != $current_asset['quantity']);
        
        if ($quantityChanged) {
            $verification_status = 'pending_approval'; // Needs approval for quantity change
            $admin_review_status = 'pending';
        } else {
            // Only file uploads, no quantity change
            $verification_status = 'file_uploaded';
            $admin_review_status = 'approved'; // Auto-approve file-only updates
        }
        
        $issue_type = null;
        $issue_description = null;
        
    } elseif ($action_type === 'discrepancy') {
        // CASE 3: Report Issue
        $issue_type = $_POST['issue_type'] ?? 'other';
        $issue_description = $remarks;
        $verification_status = 'discrepancy_' . $issue_type;
        $new_quantity = $current_asset['quantity'];
        $quantityChanged = false;
        $update_reason = null;
        $admin_review_status = 'pending'; // Needs admin review
    }
    
    // Insert into gps_verifications table
    $insert_verification = "INSERT INTO gps_verifications (
        asset_id, 
        verification_date, 
        verified_by, 
        verification_status,
        old_quantity,
        new_quantity,
        remarks,
        issue_type,
        issue_description,
        update_reason,
        admin_review_status,
        evidence_photo_path
    ) VALUES (
        '$asset_id',
        '$now',
        '$verified_by',
        '$verification_status',
        '{$current_asset['quantity']}',
        '$new_quantity',
        " . ($remarks ? "'" . pg_escape_string($con, $remarks) . "'" : "NULL") . ",
        " . ($issue_type ? "'" . pg_escape_string($con, $issue_type) . "'" : "NULL") . ",
        " . ($issue_description ? "'" . pg_escape_string($con, $issue_description) . "'" : "NULL") . ",
        " . ($update_reason ? "'" . pg_escape_string($con, $update_reason) . "'" : "NULL") . ",
        '$admin_review_status',
        " . ($evidence_photo_path ? "'" . pg_escape_string($con, $evidence_photo_path) . "'" : "NULL") . "
    )";
    
    $insert_result = pg_query($con, $insert_verification);
    
    if (!$insert_result) {
        $error = pg_last_error($con);
        throw new Exception("Failed to insert verification: " . $error);
    }
    
    $verification_id = pg_last_oid($insert_result);
    
    pg_query($con, "COMMIT");
    
    // Prepare success message
    $message = '';
    if ($action_type === 'verified') {
        $message = 'Asset verified successfully.';
    } elseif ($action_type === 'update') {
        if ($quantityChanged) {
            $message = 'Update submitted for approval.';
        } else {
            $message = 'Files uploaded successfully.';
        }
    } elseif ($action_type === 'discrepancy') {
        $message = 'Issue reported successfully.';
    }
    
    // Clear any output buffers
    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'verification_id' => $verification_id,
        'action_type' => $action_type,
        'quantity_changed' => $quantityChanged,
        'files_uploaded' => !empty($evidence_photo_path)
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if (isset($con)) {
        pg_query($con, "ROLLBACK");
    }
    
    // Clear any output buffers
    ob_end_clean();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Ensure nothing else is output
exit;
?>