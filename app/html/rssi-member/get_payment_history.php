<?php
require_once __DIR__ . "/../../bootstrap.php";

$studentId = $_GET['student_id'] ?? '';
$month = $_GET['month'] ?? '';
$year = $_GET['year'] ?? '';

if (empty($studentId)) {
    die("Student ID is required");
}

// Get payment history
$paymentQuery = "SELECT p.id, p.academic_year, p.month, 
                 COALESCE(fc.category_name, 'Previous Dues') as category_name,
                 p.amount, p.payment_type, p.transaction_id, 
                 p.collection_date, p.notes,
                 m.fullname as collected_by
          FROM fee_payments p
          LEFT JOIN fee_categories fc ON p.category_id = fc.id
          LEFT JOIN rssimyaccount_members m ON p.collected_by = m.associatenumber
          WHERE p.student_id = '$studentId'";

if (!empty($month) && !empty($year)) {
    $paymentQuery .= " AND p.month = '$month' AND p.academic_year = '$year'";
}
$paymentQuery .= " ORDER BY p.collection_date DESC, p.id DESC";

// Get concession details
// Get concession details - FIXED VERSION
$concessionQuery = "SELECT sc.*, fc.category_name,
                   m.fullname as created_by_name
            FROM student_concessions sc
            LEFT JOIN fee_categories fc ON sc.category_id = fc.id
            LEFT JOIN rssimyaccount_members m ON sc.created_by = m.associatenumber
            WHERE sc.student_id = '$studentId'";

if (!empty($month) && !empty($year)) {
    // Convert month name to number (April → 4)
    $monthNumber = date('n', strtotime("1 $month $year"));
    $firstDayOfMonth = "$year-$monthNumber-01";
    $lastDayOfMonth = date("Y-m-t", strtotime($firstDayOfMonth));

    $concessionQuery .= " AND (
        (sc.effective_from <= '$lastDayOfMonth' AND 
         (sc.effective_until IS NULL OR sc.effective_until >= '$firstDayOfMonth'))
        OR
        (EXTRACT(YEAR FROM sc.effective_from) = '$year' AND 
         EXTRACT(MONTH FROM sc.effective_from) = '$monthNumber')
    )";
}
$concessionQuery .= " ORDER BY sc.effective_from DESC, sc.id DESC";

// Execute queries
$payments = pg_fetch_all(pg_query($con, $paymentQuery)) ?? [];
$concessions = pg_fetch_all(pg_query($con, $concessionQuery)) ?? [];

// Get student info
$studentQuery = "SELECT studentname, class FROM rssimyprofile_student WHERE student_id = '$studentId'";
$student = pg_fetch_assoc(pg_query($con, $studentQuery));
?>

<div class="card">
    <div class="card-header bg-info text-white">
        <h5>Student Details: <?= $student['studentname'] ?> (<?= $student['class'] ?>) - <?= $month ?> <?= $year ?></h5>
    </div>

    <div class="card-body">
        <ul class="nav nav-tabs" id="studentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="true">
                    <i class="fas fa-money-bill-wave"></i> Payment History
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="concessions-tab" data-bs-toggle="tab" data-bs-target="#concessions" type="button" role="tab" aria-controls="concessions" aria-selected="false">
                    <i class="fas fa-tag"></i> Concession Details
                </button>
            </li>
        </ul>

        <div class="tab-content" id="studentTabsContent">
            <!-- Payment History Tab -->
            <div class="tab-pane fade show active" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Payment Date</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Reference</th>
                                <th>Collected By</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= date('d-M-Y', strtotime($payment['collection_date'])) ?></td>
                                    <td><?= $payment['category_name'] ?></td>
                                    <td class="text-end">₹<?= number_format($payment['amount'], 2) ?></td>
                                    <td><?= $payment['payment_type'] ?></td>
                                    <td><?= $payment['transaction_id'] ?: '-' ?></td>
                                    <td><?= $payment['collected_by'] ?></td>
                                    <td><?= $payment['notes'] ?: '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($payments)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        No payments found for <?= $month ?> <?= $year ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Concession Details Tab -->
            <div class="tab-pane fade" id="concessions" role="tabpanel" aria-labelledby="concessions-tab">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Concession Category</th>
                                <th>Reason</th>
                                <th>Effective From</th>
                                <th>Effective Until</th>
                                <th>Created By</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($concessions as $concession): ?>
                                <tr>
                                    <td><?= $concession['category_name'] ?? 'General' ?></td>
                                    <td class="text-end">₹<?= number_format($concession['concession_amount'], 2) ?></td>
                                    <td><?= $concession['concession_category'] ?></td>
                                    <td><?= $concession['reason'] ?></td>
                                    <td><?= date('d-M-Y', strtotime($concession['effective_from'])) ?></td>
                                    <td><?= $concession['effective_until'] ? date('d-M-Y', strtotime($concession['effective_until'])) : 'Ongoing' ?></td>
                                    <td><?= $concession['created_by_name'] ?></td>
                                    <td><?= date('d-M-Y H:i', strtotime($concession['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($concessions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        No concession records found <?= (!empty($month) && !empty($year)) ? "for $month $year" : '' ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5.3 JS Bundle (already in your code) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>