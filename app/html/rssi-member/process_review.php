<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: application/json');

if (!isLoggedIn("aid") || $role != 'Admin') {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access. Admin privileges required.'
    ]);
    exit;
}

// Get POST data
$request_id = $_POST['request_id'] ?? '';
$action = $_POST['action'] ?? ''; // 'approved' or 'rejected'
$request_type = $_POST['request_type'] ?? ''; // 'update' or 'discrepancy'
$admin_remarks = $_POST['admin_remarks'] ?? '';
$admin_id = $associatenumber;
$now = date('Y-m-d H:i:s');

if (!$request_id || !$action || !$request_type) {
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required parameters.'
    ]);
    exit;
}

try {
    pg_query($con, "BEGIN");
    
    // Get verification request details
    $query = "SELECT v.*, g.itemname, g.itemid 
              FROM gps_verifications v
              JOIN gps g ON v.asset_id = g.itemid
              WHERE v.id = $request_id AND v.admin_review_status = 'pending'";
    
    $result = pg_query($con, $query);
    if (!$result || pg_num_rows($result) === 0) {
        throw new Exception("Request not found or already processed.");
    }
    
    $request = pg_fetch_assoc($result);
    $asset_id = $request['asset_id'];
    
    // Update verification request status
    $update_verification = "UPDATE gps_verifications SET 
                           admin_review_status = '$action',
                           reviewed_by = '$admin_id',
                           review_date = '$now',
                           admin_remarks = '" . pg_escape_string($con, $admin_remarks) . "'
                           WHERE id = $request_id";
    
    if (!pg_query($con, $update_verification)) {
        throw new Exception("Failed to update verification status: " . pg_last_error($con));
    }
    
    // If approved and it's an update request, apply changes to GPS table
    if ($action === 'approved' && $request_type === 'update') {
        // Apply quantity and tagged_to changes to GPS table
        $update_gps = "UPDATE gps SET 
                      quantity = '" . $request['new_quantity'] . "',
                      taggedto = '" . pg_escape_string($con, $request['new_tagged_to']) . "',
                      lastupdatedon = '$now',
                      lastupdatedby = '$admin_id'
                      WHERE itemid = '" . pg_escape_string($con, $asset_id) . "'";
        
        if (!pg_query($con, $update_gps)) {
            throw new Exception("Failed to update GPS table: " . pg_last_error($con));
        }
        
        // Add to GPS history
        $history_data = json_encode([
            'type' => 'admin_approved_update',
            'request_id' => $request_id,
            'old_quantity' => $request['old_quantity'],
            'new_quantity' => $request['new_quantity'],
            'old_tagged_to' => $request['old_tagged_to'],
            'new_tagged_to' => $request['new_tagged_to'],
            'admin_id' => $admin_id,
            'admin_remarks' => $admin_remarks,
            'timestamp' => $now
        ]);
        
        $history_query = "INSERT INTO gps_history (itemid, update_type, updatedby, date, changes) 
                         VALUES ('" . pg_escape_string($con, $asset_id) . "', 
                                 'admin_approved_update', 
                                 '$admin_id', 
                                 '$now', 
                                 '" . pg_escape_string($con, $history_data) . "')";
        
        pg_query($con, $history_query);
        
        // Send notification to requester if they have email (optional)
        $notify_requester = true; // Set to false if you don't want notifications
        
    } elseif ($action === 'approved' && strpos($request_type, 'discrepancy') !== false) {
        // For discrepancy reports, mark as resolved but don't change GPS data
        // (Admin might want to manually update GPS if needed)
        
        // Add to GPS history
        $history_data = json_encode([
            'type' => 'admin_resolved_discrepancy',
            'request_id' => $request_id,
            'issue_type' => $request['issue_type'],
            'admin_id' => $admin_id,
            'admin_remarks' => $admin_remarks,
            'resolution' => 'resolved',
            'timestamp' => $now
        ]);
        
        $history_query = "INSERT INTO gps_history (itemid, update_type, updatedby, date, changes) 
                         VALUES ('" . pg_escape_string($con, $asset_id) . "', 
                                 'admin_resolved_discrepancy', 
                                 '$admin_id', 
                                 '$now', 
                                 '" . pg_escape_string($con, $history_data) . "')";
        
        pg_query($con, $history_query);
        
    } elseif ($action === 'rejected') {
        // For rejected requests, just log it in history
        
        $reject_type = $request_type === 'update' ? 'admin_rejected_update' : 'admin_rejected_discrepancy';
        
        $history_data = json_encode([
            'type' => $reject_type,
            'request_id' => $request_id,
            'admin_id' => $admin_id,
            'admin_remarks' => $admin_remarks,
            'timestamp' => $now
        ]);
        
        $history_query = "INSERT INTO gps_history (itemid, update_type, updatedby, date, changes) 
                         VALUES ('" . pg_escape_string($con, $asset_id) . "', 
                                 '$reject_type', 
                                 '$admin_id', 
                                 '$now', 
                                 '" . pg_escape_string($con, $history_data) . "')";
        
        pg_query($con, $history_query);
    }
    
    // Commit transaction
    pg_query($con, "COMMIT");
    
    // Log the action
    error_log("Admin $admin_id $action $request_type request $request_id for asset $asset_id");
    
    echo json_encode([
        'success' => true,
        'message' => ucfirst($request_type) . ' request has been ' . $action . ' successfully.',
        'asset_id' => $asset_id,
        'asset_name' => $request['itemname']
    ]);
    
} catch (Exception $e) {
    pg_query($con, "ROLLBACK");
    
    error_log("Error processing review: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>