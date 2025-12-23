<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid")) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$asset_id = $_POST['asset_id'] ?? '';
$verified_by = $_POST['verified_by'] ?? '';
$action_type = $_POST['action_type'] ?? '';
$remarks = $_POST['remarks'] ?? '';

if (!$asset_id || !$verified_by || !$action_type) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$now = date('Y-m-d H:i:s');
$associatenumber = $associatenumber; // Get current user

try {
    pg_query($con, "BEGIN");
    
    // Fetch current asset data
    $current_query = "SELECT * FROM gps WHERE itemid = '$asset_id'";
    $current_result = pg_query($con, $current_query);
    $current_asset = pg_fetch_assoc($current_result);
    
    if (!$current_asset) {
        throw new Exception("Asset not found");
    }
    
    // Prepare verification data
    $verification_data = [
        'asset_id' => $asset_id,
        'verified_by' => $verified_by,
        'verification_status' => $action_type,
        'verification_date' => $now,
        'old_quantity' => $current_asset['quantity'],
        'old_tagged_to' => $current_asset['taggedto'],
        'old_status' => $current_asset['asset_status'],
        'remarks' => $remarks
    ];
    
    // Handle different action types
    if ($action_type === 'verified') {
        // CASE 1: Verified Correct - Update only verification tracking fields
        $update_gps = "UPDATE gps SET 
                      last_verified_on = '$now',
                      verified_by = '$verified_by',
                      verified_count = COALESCE(verified_count, 0) + 1
                      WHERE itemid = '$asset_id'";
        
        $update_result = pg_query($con, $update_gps);
        if (!$update_result) {
            throw new Exception("Failed to update verification tracking: " . pg_last_error($con));
        }
        
        $verification_data['new_quantity'] = $current_asset['quantity'];
        $verification_data['new_tagged_to'] = $current_asset['taggedto'];
        $verification_data['new_status'] = $current_asset['asset_status'];
        
    } elseif ($action_type === 'update') {
        // CASE 2: Update Details - Only create verification record, NO GPS update
        $new_quantity = $_POST['new_quantity'] ?? $current_asset['quantity'];
        $new_tagged_to = $_POST['new_tagged_to'] ?? $current_asset['taggedto'];
        $update_reason = $_POST['remarks'] ?? '';
        
        // Store proposed changes in verification record
        $verification_data['new_quantity'] = $new_quantity;
        $verification_data['new_tagged_to'] = $new_tagged_to;
        $verification_data['new_status'] = $current_asset['asset_status'];
        $verification_data['update_reason'] = $update_reason;
        $verification_data['verification_status'] = 'pending_update'; // Different status
        
        // DO NOT UPDATE GPS TABLE HERE - Only verification tracking
        $update_verification_tracking = "UPDATE gps SET 
                                        last_verified_on = '$now',
                                        verified_by = '$verified_by'
                                        WHERE itemid = '$asset_id'";
        
        $update_result = pg_query($con, $update_verification_tracking);
        if (!$update_result) {
            throw new Exception("Failed to update verification tracking: " . pg_last_error($con));
        }
        
    } elseif ($action_type === 'discrepancy') {
        // CASE 3: Report Issue - Only create verification record, NO GPS update
        $issue_type = $_POST['issue_type'] ?? 'other';
        $issue_description = $_POST['remarks'] ?? '';
        
        $verification_data['verification_status'] = 'discrepancy_' . $issue_type;
        $verification_data['issue_type'] = $issue_type;
        $verification_data['issue_description'] = $issue_description;
        
        // DO NOT UPDATE GPS STATUS HERE - Only verification tracking
        $update_verification_tracking = "UPDATE gps SET 
                                        last_verified_on = '$now',
                                        verified_by = '$verified_by'
                                        WHERE itemid = '$asset_id'";
        
        $update_result = pg_query($con, $update_verification_tracking);
        if (!$update_result) {
            throw new Exception("Failed to update verification tracking: " . pg_last_error($con));
        }
    }
    
    // Insert verification record (always)
    $columns = implode(', ', array_keys($verification_data));
    $values = "'" . implode("', '", array_values($verification_data)) . "'";
    
    $insert_verification = "INSERT INTO gps_verifications ($columns) VALUES ($values)";
    
    $insert_result = pg_query($con, $insert_verification);
    
    if (!$insert_result) {
        throw new Exception("Failed to insert verification record: " . pg_last_error($con));
    }
    
    // Get the verification ID using pg_last_oid on the INSERT result
    $verification_id = pg_last_oid($insert_result); // FIXED: Use $insert_result, not $con
    
    // Also insert into gps_history for audit trail
    $history_data = json_encode([
        'verification_type' => $action_type,
        'verified_by' => $verified_by,
        'timestamp' => $now,
        'verification_id' => $verification_id,
        'verification_data' => $verification_data
    ]);
    
    $history_query = "INSERT INTO gps_history (itemid, update_type, updatedby, date, changes) 
                      VALUES ('$asset_id', 'verification_$action_type', '$verified_by', '$now', '$history_data')";
    
    $history_result = pg_query($con, $history_query);
    if (!$history_result) {
        throw new Exception("Failed to insert history record: " . pg_last_error($con));
    }
    
    pg_query($con, "COMMIT");
    
    echo json_encode([
        'success' => true,
        'message' => 'Verification submitted successfully',
        'verification_id' => $verification_id
    ]);
    
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>