<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
validation();

if (!isset($_GET['settlement_id'])) {
    die("Settlement ID required");
}

$settlementId = (int)$_GET['settlement_id'];

// Get settlement info
$settlementQuery = "SELECT s.*, m.fullname as settled_by_name
                    FROM settlements s
                    JOIN rssimyaccount_members m ON s.settled_by = m.associatenumber
                    WHERE s.id = $settlementId";
$settlementResult = pg_query($con, $settlementQuery);
$settlement = pg_fetch_assoc($settlementResult);

if (!$settlement) {
    die("Settlement not found");
}

// Get payment details
$paymentsQuery = "SELECT p.*, s.studentname, s.class, m.fullname as collector_name
                  FROM fee_payments p
                  JOIN rssimyprofile_student s ON p.student_id = s.student_id
                  JOIN rssimyaccount_members m ON p.collected_by = m.associatenumber
                  WHERE p.settlement_id = $settlementId
                  ORDER BY p.collection_date";
$paymentsResult = pg_query($con, $paymentsQuery);
$payments = pg_fetch_all($paymentsResult) ?? [];
?>

<div class="container-fluid">
    <h4>Settlement Details - #<?= $settlement['id'] ?></h4>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card" id="settlement-info">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title">Settlement Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Settlement Date:</div>
                        <div class="col-md-8"><?= date('d-M-Y', strtotime($settlement['settlement_date'])) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Total Amount:</div>
                        <div class="col-md-8">₹<?= number_format($settlement['total_amount'], 2) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Cash Amount:</div>
                        <div class="col-md-8">₹<?= number_format($settlement['cash_amount'], 2) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Online Amount:</div>
                        <div class="col-md-8">₹<?= number_format($settlement['online_amount'], 2) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Settled By:</div>
                        <div class="col-md-8"><?= htmlspecialchars($settlement['settled_by_name']) ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 fw-bold">Notes:</div>
                        <div class="col-md-8"><?= $settlement['notes'] ? htmlspecialchars($settlement['notes']) : 'N/A' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card" id="settlement-summary-info">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title">Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-6 fw-bold">Total Payments:</div>
                        <div class="col-md-6"><?= count($payments) ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6 fw-bold">Cash Payments:</div>
                        <div class="col-md-6">
                            <?= count(array_filter($payments, function ($p) {
                                return $p['payment_type'] === 'cash';
                            })) ?>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6 fw-bold">Online Payments:</div>
                        <div class="col-md-6">
                            <?= count(array_filter($payments, function ($p) {
                                return $p['payment_type'] === 'online';
                            })) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 fw-bold">Created At:</div>
                        <div class="col-md-6"><?= date('d-M-Y H:i', strtotime($settlement['created_at'])) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <h5>Payment Details</h5>
            <?php if (empty($payments)): ?>
                <div class="alert alert-info">No payment records found for this settlement</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-info">
                            <tr>
                                <th>Payment ID</th>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Month</th>
                                <!-- <th>Year</th> -->
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Transaction ID</th>
                                <th>Source</th>
                                <th>Collector</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= $payment['id'] ?></td>
                                    <td><?= date('d-M-Y H:i', strtotime($payment['collection_date'])) ?></td>
                                    <td><?= htmlspecialchars($payment['studentname']) ?></td>
                                    <td><?= htmlspecialchars($payment['class']) ?></td>
                                    <td><?= $payment['month'] ?>-<?= $payment['academic_year'] ?></td>
                                    <!-- <td><?= $payment['academic_year'] ?></td> -->
                                    <td>₹<?= number_format($payment['amount'], 2) ?></td>
                                    <td><?= ucfirst($payment['payment_type']) ?></td>
                                    <td><?= $payment['transaction_id'] ?: 'N/A' ?></td>
                                    <td><?= isset($payment['source']) ? htmlspecialchars($payment['source']) : '' ?></td>
                                    <td><?= htmlspecialchars($payment['collector_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>