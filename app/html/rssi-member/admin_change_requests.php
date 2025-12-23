<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid") || $role != 'Admin') {
    header("Location: index.php");
    exit;
}

// Fetch pending change requests
$query = "SELECT v.*, 
          g.itemname, g.itemtype,
          tm.fullname as old_tagged_name,
          nm.fullname as new_tagged_name,
          verified_by_user.fullname as verified_by_name
          FROM gps_verifications v
          JOIN gps g ON v.asset_id = g.itemid
          LEFT JOIN rssimyaccount_members tm ON v.old_tagged_to = tm.associatenumber
          LEFT JOIN rssimyaccount_members nm ON v.new_tagged_to = nm.associatenumber
          LEFT JOIN rssimyaccount_members verified_by_user ON v.verified_by = verified_by_user.associatenumber
          WHERE v.admin_review_status = 'pending'
          ORDER BY v.verification_date DESC";

$result = pg_query($con, $query);
?>

<!-- Admin interface to approve/reject changes -->
<table class="table">
    <thead>
        <tr>
            <th>Asset</th>
            <th>Request Type</th>
            <th>Changes Requested</th>
            <th>Requested By</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = pg_fetch_assoc($result)): ?>
        <tr>
            <td><?= $row['itemname'] ?> (<?= $row['asset_id'] ?>)</td>
            <td>
                <?php 
                $type = $row['verification_status'];
                $badge_color = strpos($type, 'discrepancy') !== false ? 'danger' : 
                              ($type === 'pending_update' ? 'warning' : 'info');
                ?>
                <span class="badge bg-<?= $badge_color ?>"><?= $type ?></span>
            </td>
            <td>
                <?php if ($row['verification_status'] === 'pending_update'): ?>
                    Quantity: <?= $row['old_quantity'] ?> → <?= $row['new_quantity'] ?><br>
                    Tagged To: <?= $row['old_tagged_name'] ?> → <?= $row['new_tagged_name'] ?>
                <?php elseif (strpos($row['verification_status'], 'discrepancy') !== false): ?>
                    Issue: <?= $row['issue_type'] ?><br>
                    <?= substr($row['issue_description'], 0, 100) ?>...
                <?php endif; ?>
            </td>
            <td><?= $row['verified_by_name'] ?></td>
            <td><?= date('d/m/Y H:i', strtotime($row['verification_date'])) ?></td>
            <td>
                <button class="btn btn-sm btn-success" onclick="reviewRequest(<?= $row['id'] ?>, 'approved')">Approve</button>
                <button class="btn btn-sm btn-danger" onclick="reviewRequest(<?= $row['id'] ?>, 'rejected')">Reject</button>
                <button class="btn btn-sm btn-info" onclick="viewDetails(<?= $row['id'] ?>)">Details</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>