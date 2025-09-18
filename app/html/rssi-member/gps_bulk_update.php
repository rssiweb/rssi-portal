<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid") || $role != 'Admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selected_assets = $_POST['selected_assets'] ?? [];
    $update_tagged_to = isset($_POST['update_tagged_to']);
    $update_status = isset($_POST['update_status']);
    $tagged_to = $_POST['tagged_to'] ?? '';
    $status = $_POST['status'] ?? '';
    $remarks = htmlspecialchars($_POST['remarks'] ?? '', ENT_QUOTES, 'UTF-8');
    $updated_by = $associatenumber;
    $now = date('Y-m-d H:i:s');
    
    if (!empty($selected_assets)) {
        foreach ($selected_assets as $asset_id) {
            // Build update query based on what needs to be updated
            $updates = [];
            $history_data = [
                'itemid' => $asset_id,
                'updated_by' => $updated_by,
                'update_time' => $now,
                'changes' => []
            ];
            
            if ($update_tagged_to && !empty($tagged_to)) {
                $updates[] = "taggedto = '$tagged_to'";
                $history_data['changes']['tagged_to'] = $tagged_to;
            }
            
            if ($update_status && !empty($status)) {
                $updates[] = "asset_status = '$status'";
                $history_data['changes']['status'] = $status;
            }
            
            if (!empty($remarks)) {
                $updates[] = "remarks = CONCAT(remarks, '\nBulk update: $remarks')";
                $history_data['changes']['remarks'] = $remarks;
            }
            
            $updates[] = "lastupdatedby = '$updated_by'";
            $updates[] = "lastupdatedon = '$now'";
            
            if (!empty($updates)) {
                $update_query = "UPDATE gps SET " . implode(', ', $updates) . " WHERE itemid = '$asset_id'";
                $result = pg_query($con, $update_query);
                
                // Add to history table
                if (!empty($history_data['changes'])) {
                    $changes_json = json_encode($history_data['changes']);
                    $history_query = "INSERT INTO gps_history (itemid, update_type, updatedby, date, changes) 
                                     VALUES ('$asset_id', 'bulk_update', '$updated_by', '$now', '$changes_json')";
                    pg_query($con, $history_query);
                }
            }
        }
        
        $_SESSION['success_message'] = "Successfully updated " . count($selected_assets) . " assets.";
    } else {
        $_SESSION['error_message'] = "No assets selected for update.";
    }
    
    header("Location: gps.php");
    exit;
}
?>