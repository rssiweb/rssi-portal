<?php
require_once __DIR__ . "/../../bootstrap.php";

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
$paymentsQuery = "SELECT p.*, s.studentname, s.class, m.fullname as collector_name, eo.order_number,
       eo.order_id, fc.category_name AS source
                  FROM fee_payments p
                  JOIN rssimyprofile_student s ON p.student_id = s.student_id
                  JOIN rssimyaccount_members m ON p.collected_by = m.associatenumber
                  LEFT JOIN emart_orders eo ON p.id = eo.payment_id
                  LEFT JOIN fee_payments fp ON eo.payment_id = fp.id
                  LEFT JOIN fee_categories fc ON fp.category_id=fc.id
                  WHERE p.settlement_id = $settlementId
                  ORDER BY p.collection_date";
$paymentsResult = pg_query($con, $paymentsQuery);
$payments = pg_fetch_all($paymentsResult) ?? [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlement #<?= $settlement['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body {
                padding: 20px;
            }

            .no-print {
                display: none !important;
            }

            .table {
                width: 100%;
            }
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .summary-card {
            border-left: 5px solid;
            margin-bottom: 20px;
        }

        .summary-card.total {
            border-color: #007bff;
        }

        .summary-card.cash {
            border-color: #28a745;
        }

        .summary-card.online {
            border-color: #17a2b8;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="no-print text-center mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                <i class="fas fa-times"></i> Close
            </button>
        </div>

        <div class="header">
            <h2>Fee Settlement Receipt</h2>
            <h4>Settlement #<?= $settlement['id'] ?></h4>
            <p>Date: <?= date('d-M-Y', strtotime($settlement['settlement_date'])) ?></p>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
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
                <div class="card">
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

        <div class="mb-4">
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
                                    <td>
                                        <?= isset($payment['source']) ? htmlspecialchars($payment['source']) : '' ?>
                                        &nbsp;
                                        <?= isset($payment['order_number']) ? '#' . htmlspecialchars($payment['order_number']) : '' ?>
                                    </td>
                                    <td><?= htmlspecialchars($payment['collector_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer no-print mt-4 text-center">
            <p>Printed on: <?= date('d-M-Y H:i') ?></p>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Auto-print when page loads
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>

</html>