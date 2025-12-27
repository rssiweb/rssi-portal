<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Include your email system
include("../../util/email.php");

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

    // Store assets grouped by recipient for email
    $allocations_by_recipient = []; // For new allocations
    $status_updates_by_recipient = []; // For status updates

    if (!empty($selected_assets)) {
        // Get current user's info for email
        $admin_info_query = "SELECT fullname, email FROM rssimyaccount_members WHERE associatenumber = '$updated_by'";
        $admin_result = pg_query($con, $admin_info_query);
        $admin_info = pg_fetch_assoc($admin_result);
        $admin_fullname = $admin_info['fullname'] ?? '';
        $admin_email = $admin_info['email'] ?? '';

        // Get new user info if tagged_to is being updated
        $new_user_info = null;
        if ($update_tagged_to && !empty($tagged_to)) {
            $new_user_query = "SELECT fullname, email FROM rssimyaccount_members WHERE associatenumber = '$tagged_to'";
            $new_user_result = pg_query($con, $new_user_query);
            $new_user_info = pg_fetch_assoc($new_user_result);
        }

        foreach ($selected_assets as $asset_id) {
            // Get current asset details
            $asset_query = "SELECT g.*, 
                           tmember.fullname as tfullname, 
                           tmember.email as temail,
                           imember.fullname as ifullname
                    FROM gps g
                    LEFT JOIN rssimyaccount_members AS tmember ON g.taggedto = tmember.associatenumber
                    LEFT JOIN rssimyaccount_members AS imember ON g.collectedby = imember.associatenumber
                    WHERE g.itemid = '$asset_id'";
            $asset_result = pg_query($con, $asset_query);
            $asset_data = pg_fetch_assoc($asset_result);

            if (!$asset_data) continue;

            // Store original data
            $original_tagged_to = $asset_data['taggedto'];
            $original_status = $asset_data['asset_status'];
            $original_temail = $asset_data['temail'] ?? '';
            $original_tfullname = $asset_data['tfullname'] ?? '';

            // Build update query
            $updates = [];
            $history_data = [
                'itemid' => $asset_id,
                'updated_by' => $updated_by,
                'update_time' => $now,
                'changes' => []
            ];

            $has_changes = false;

            // Handle tagged_to update
            if ($update_tagged_to && !empty($tagged_to) && $tagged_to !== $original_tagged_to) {
                $updates[] = "taggedto = '$tagged_to'";
                $history_data['changes']['tagged_to'] = ['from' => $original_tagged_to, 'to' => $tagged_to];
                $has_changes = true;

                // Add to allocations if we have new user info
                if ($new_user_info && !empty($new_user_info['email'])) {
                    $recipient_email = $new_user_info['email'];

                    if (!isset($allocations_by_recipient[$recipient_email])) {
                        $allocations_by_recipient[$recipient_email] = [
                            'recipient_name' => $new_user_info['fullname'] ?? '',
                            'assets' => []
                        ];
                    }

                    $allocations_by_recipient[$recipient_email]['assets'][] = [
                        'itemid' => $asset_id,
                        'itemname' => $asset_data['itemname'],
                        'quantity' => $asset_data['quantity'],
                        'old_tagged_to' => $original_tfullname ? $original_tfullname . ' (' . $original_tagged_to . ')' : $original_tagged_to,
                        'new_tagged_to' => ($new_user_info['fullname'] ?? '') . ' (' . $tagged_to . ')',
                        'ifullname' => $admin_fullname,
                        'collectedby' => $updated_by
                    ];
                }
            }

            // Handle status update
            if ($update_status && !empty($status) && $status !== $original_status) {
                $updates[] = "asset_status = '$status'";
                $history_data['changes']['status'] = ['from' => $original_status, 'to' => $status];
                $has_changes = true;

                // Determine who to notify about status change
                if ($update_tagged_to && !empty($tagged_to)) {
                    // If tagged_to is also being updated, notify the NEW user
                    if ($new_user_info && !empty($new_user_info['email'])) {
                        $recipient_email = $new_user_info['email'];
                        $recipient_name = $new_user_info['fullname'] ?? '';
                    } else {
                        continue; // No email for new user
                    }
                } else {
                    // Notify the CURRENT tagged user
                    if (!empty($original_temail)) {
                        $recipient_email = $original_temail;
                        $recipient_name = $original_tfullname;
                    } else {
                        continue; // No email for current user
                    }
                }

                // Add to status updates
                if (!empty($recipient_email)) {
                    if (!isset($status_updates_by_recipient[$recipient_email])) {
                        $status_updates_by_recipient[$recipient_email] = [
                            'recipient_name' => $recipient_name,
                            'assets' => []
                        ];
                    }

                    $status_updates_by_recipient[$recipient_email]['assets'][] = [
                        'itemid' => $asset_id,
                        'itemname' => $asset_data['itemname'],
                        'quantity' => $asset_data['quantity'],
                        'old_status' => $original_status,
                        'new_status' => $status,
                        'ifullname' => $admin_fullname,
                        'collectedby' => $updated_by
                    ];
                }
            }

            // Handle remarks
            if (!empty($remarks)) {
                $updates[] = "remarks = CONCAT(COALESCE(remarks, ''), '\nBulk update [$now]: $remarks')";
                $history_data['changes']['remarks'] = $remarks;
                $has_changes = true;
            }

            // Add update metadata
            $updates[] = "lastupdatedby = '$updated_by'";
            $updates[] = "lastupdatedon = '$now'";

            // Execute update if there are changes
            if ($has_changes && !empty($updates)) {
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

        // Send email notifications

        // 1. Send allocation emails (for tagged_to changes)
        if (!empty($allocations_by_recipient)) {
            foreach ($allocations_by_recipient as $recipient_email => $data) {
                $assets = $data['assets'];
                $recipient_name = $data['recipient_name'];

                // Prepare asset list for email template
                $asset_list = [];
                foreach ($assets as $asset) {
                    $asset_list[] = [
                        'itemname' => $asset['itemname'],
                        'quantity' => $asset['quantity'],
                        'itemid' => $asset['itemid'],
                        'old_tagged_to' => $asset['old_tagged_to']
                    ];
                }

                // For allocation emails, replace the $assets_html building section with:
                $assets_html = '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">#</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Item Name</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Quantity</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Asset ID</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Previously Tagged To</th>
                    </tr>
                </thead>
                <tbody>';

                foreach ($assets as $index => $asset) {
                    $row_color = $index % 2 == 0 ? '#ffffff' : '#f9f9f9';
                    $assets_html .= '<tr style="background-color: ' . $row_color . ';">';
                    $assets_html .= '<td style="padding: 10px; border: 1px solid #ddd;">' . ($index + 1) . '</td>';
                    $assets_html .= '<td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($asset['itemname']) . '</td>';
                    $assets_html .= '<td style="padding: 10px; border: 1px solid #ddd; text-align: center;">' . htmlspecialchars($asset['quantity']) . '</td>';
                    $assets_html .= '<td style="padding: 10px; border: 1px solid #ddd; font-family: monospace;">' . htmlspecialchars($asset['itemid']) . '</td>';
                    $assets_html .= '<td style="padding: 10px; border: 1px solid #ddd;">' .
                        (!empty($asset['old_tagged_to']) ? htmlspecialchars($asset['old_tagged_to']) : 'N/A (First allocation)') .
                        '</td>';
                    $assets_html .= '</tr>';
                }

                $assets_html .= '</tbody></table>';

                // Prepare email data for template
                $email_data = [
                    'tfullname' => $recipient_name,
                    'ifullname' => $admin_fullname,
                    'collectedby' => $updated_by,
                    'asset_count' => count($assets),
                    'assets_table' => $assets_html, // Changed to table HTML
                    'now' => date('d/m/Y g:i a', strtotime($now))
                ];

                // Send email using your template system, to add admin as CC add $admin_email as 4th parameter
                sendEmail("gps_allocation", $email_data, $recipient_email, false);
            }
        }

        // 2. Send status update emails
        if (!empty($status_updates_by_recipient)) {
            foreach ($status_updates_by_recipient as $recipient_email => $data) {
                $assets = $data['assets'];
                $recipient_name = $data['recipient_name'];

                // Prepare asset list for email template
                $asset_list = [];
                foreach ($assets as $asset) {
                    $asset_list[] = [
                        'itemname' => $asset['itemname'],
                        'quantity' => $asset['quantity'],
                        'itemid' => $asset['itemid'],
                        'old_status' => $asset['old_status'],
                        'new_status' => $asset['new_status']
                    ];
                }

                // For status update emails, replace with:
                $assets_html = '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">#</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Item Name</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Quantity</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Asset ID</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Old Status</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">New Status</th>
                    </tr>
                </thead>
                <tbody>';

                foreach ($assets as $index => $asset) {
                    $row_color = $index % 2 == 0 ? '#ffffff' : '#f9f9f9';
                    $assets_html .= '<tr style="background-color: ' . $row_color . ';">';
                    $assets_html .= '<td style="padding: 10px; border: 1px solid #ddd;">' . ($index + 1) . '</td>';
                    $assets_html .= '<td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($asset['itemname']) . '</td>';
                    $assets_html .= '<td style="padding: 10px; border: 1px solid #ddd; text-align: center;">' . htmlspecialchars($asset['quantity']) . '</td>';
                    $assets_html .= '<td style="padding: 10px; border: 1px solid #ddd; font-family: monospace;">' . htmlspecialchars($asset['itemid']) . '</td>';
                    $assets_html .= '<td style="padding: 10px; border: 1px solid #ddd; color: #666;">' . htmlspecialchars($asset['old_status']) . '</td>';
                    $assets_html .= '<td style="padding: 10px; border: 1px solid #ddd; color: #28a745; font-weight: bold;">' . htmlspecialchars($asset['new_status']) . '</td>';
                    $assets_html .= '</tr>';
                }

                $assets_html .= '</tbody></table>';
                // Prepare email data for template
                $email_data = [
                    'tfullname' => $recipient_name,
                    'ifullname' => $admin_fullname,
                    'collectedby' => $updated_by,
                    'asset_count' => count($assets),
                    'assets_table' => $assets_html, // Changed to table HTML
                    'now' => date('d/m/Y g:i a', strtotime($now))
                ];

                // Send email using your template system
                sendEmail("gps_status_update", $email_data, $recipient_email, false);
            }
        }

        // Count emails sent
        $total_emails_sent = count($allocations_by_recipient) + count($status_updates_by_recipient);

        $_SESSION['success_message'] = "Successfully updated " . count($selected_assets) . " assets.";
        if ($total_emails_sent > 0) {
            $_SESSION['success_message'] .= " " . $total_emails_sent . " email(s) sent.";
        }
    } else {
        $_SESSION['error_message'] = "No assets selected for update.";
    }

    header("Location: gps.php");
    exit;
}
