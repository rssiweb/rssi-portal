<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header('Content-Type: text/html');

if (!isLoggedIn("aid")) {
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit;
}

if (!isset($_GET['batch_id'])) {
    echo '<div class="alert alert-danger">Batch ID not specified</div>';
    exit;
}

$batch_id = pg_escape_string($con, $_GET['batch_id']);

// Get batch header information
$batch_query = "SELECT 
    b.batch_id,
    b.created_by,
    m.fullname as created_by_name,
    b.created_date,
    o.status,
    b.vendor_name,
    b.admin_remarks,
    b.ordered_date,
    o.delivered_date,
    o.delivered_remarks,
    COUNT(o.id) as item_count,
    MIN(o.academic_year) as academic_year
FROM id_card_batches b
LEFT JOIN id_card_orders o ON b.batch_id = o.batch_id
LEFT JOIN rssimyaccount_members m ON b.created_by = m.associatenumber
WHERE b.batch_id = '$batch_id'
GROUP BY b.batch_id, b.created_by, m.fullname, b.created_date, o.status, 
         b.vendor_name, b.admin_remarks, b.ordered_date, o.delivered_date, o.delivered_remarks";
$batch_result = pg_query($con, $batch_query);
$batch = pg_fetch_assoc($batch_result);

if (!$batch) {
    echo '<div class="alert alert-danger">Batch not found</div>';
    exit;
}

// Get all orders in this batch
$orders_query = "SELECT 
    o.id,
    o.student_id,
    COALESCE(s.studentname, m.fullname) as student_name,
    s.class,
    COALESCE(s.photourl, m.photo) as photo,
    o.order_type,
    o.payment_status,
    o.remarks,
    o.order_date,
    o.order_placed_by,
    u.fullname as order_placed_by_name,
    o.status,
    (SELECT COUNT(*) FROM id_card_orders WHERE student_id = o.student_id AND status = 'Delivered') as times_issued,
    (SELECT MAX(order_date) FROM id_card_orders WHERE student_id = o.student_id AND status = 'Delivered') as last_issued
FROM id_card_orders o
LEFT JOIN rssimyprofile_student s ON o.student_id = s.student_id
LEFT JOIN rssimyaccount_members m ON o.student_id = m.associatenumber
JOIN rssimyaccount_members u ON o.order_placed_by = u.associatenumber
WHERE o.batch_id = '$batch_id'
ORDER BY o.order_date DESC";
$orders_result = pg_query($con, $orders_query);
$orders = pg_fetch_all($orders_result) ?: [];
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="h5 mb-0">Batch Summary: <code><?= htmlspecialchars($batch['batch_id']) ?></code></h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <span class="badge <?=
                                                    $batch['status'] === 'Delivered' ? 'bg-success' : ($batch['status'] === 'Ordered' ? 'bg-warning text-dark' : 'bg-primary')
                                                    ?>">
                                    <?= $batch['status'] ?>
                                </span>
                            </div>
                            <div class="mb-3">
                                <strong>Created By:</strong>
                                <?= htmlspecialchars($batch['created_by_name']) ?> (<?= $batch['created_by'] ?>)
                            </div>
                            <div class="mb-3">
                                <strong>Created Date:</strong>
                                <?= date('d M Y H:i', strtotime($batch['created_date'])) ?>
                            </div>
                            <div class="mb-3">
                                <strong>Academic Year:</strong>
                                <?= htmlspecialchars($batch['academic_year']) ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?php if ($batch['status'] === 'Ordered' || $batch['status'] === 'Delivered'): ?>
                                <div class="mb-3">
                                    <strong>Vendor:</strong>
                                    <?= htmlspecialchars($batch['vendor_name'] ?? 'Not specified') ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Ordered Date:</strong>
                                    <?= $batch['ordered_date'] ? date('d M Y H:i', strtotime($batch['ordered_date'])) : 'Not ordered yet' ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Admin Remarks:</strong>
                                    <?= htmlspecialchars($batch['admin_remarks'] ?? 'None') ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($batch['status'] === 'Delivered'): ?>
                                <div class="mb-3">
                                    <strong>Delivered Date:</strong>
                                    <?= date('d M Y H:i', strtotime($batch['delivered_date'])) ?>
                                </div>
                                <div class="mb-3">
                                    <strong>Delivery Remarks:</strong>
                                    <?= htmlspecialchars($batch['delivered_remarks'] ?? 'None') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h3 class="h5 mb-0">Order Items (<?= count($orders) ?>)</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Photo</th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Class</th>
                                    <th>Type</th>
                                    <th>Payment</th>
                                    <th>Remarks</th>
                                    <th>Requested By</th>
                                    <th>Order Date</th>
                                    <th>History</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= htmlspecialchars($order['photo'] ?? 'default_photo.jpg') ?>"
                                                class="student-photo" style="width: 50px; height: 50px; object-fit: cover;">
                                        </td>
                                        <td><?= htmlspecialchars($order['student_id']) ?></td>
                                        <td><?= htmlspecialchars($order['student_name']) ?></td>
                                        <td><?= htmlspecialchars($order['class'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge <?= $order['order_type'] === 'New' ? 'bg-primary' : 'bg-secondary' ?>">
                                                <?= $order['order_type'] ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($order['payment_status'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($order['remarks'] ?? '-') ?></td>
                                        <td>
                                            <?= htmlspecialchars($order['order_placed_by_name']) ?><br>
                                            <small><?= $order['order_placed_by'] ?></small>
                                        </td>
                                        <td><?= date('d M Y', strtotime($order['order_date'])) ?></td>
                                        <td>
                                            <?php if ($order['times_issued'] > 0): ?>
                                                Issued <?= $order['times_issued'] ?> time(s)
                                                <br>
                                                <small>Last: <?= date('d M Y', strtotime($order['last_issued'])) ?></small>
                                            <?php else: ?>
                                                First issue
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-12 text-end">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="id_export_batch.php?batch_id=<?= urlencode($batch['batch_id']) ?>" class="btn btn-secondary">
                <i class="bi bi-download"></i> Export Batch
            </a>
            <!-- <?php if ($role === 'Admin' && $batch['status'] === 'Ordered'): ?>
                <button class="btn btn-success mark-delivered" data-batch="<?= $batch['batch_id'] ?>">
                    <i class="bi bi-check-circle"></i> Mark Delivered
                </button>
            <?php endif; ?> -->
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Handle mark delivered button in the modal
        $('.mark-delivered').click(function() {
            const batchId = $(this).data('batch');
            $('#deliveryBatchId').text(batchId);
            $('#deliveryRemarks').val('');
            $('#deliveryRemarksModal').modal('show');
        });
    });
</script>