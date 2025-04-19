<?php
require_once __DIR__ . "/../../bootstrap.php";

$studentId = $_GET['student_id'] ?? '';
$month = $_GET['month'] ?? '';
$year = $_GET['year'] ?? '';

if (empty($studentId)) {
    die("Student ID is required");
}

// Get payment history STRICTLY for the specified month/year
$query = "SELECT p.id, p.academic_year, p.month, 
                 COALESCE(fc.category_name, 'Previous Dues') as category_name,
                 p.amount, p.payment_type, p.transaction_id, 
                 p.collection_date, p.notes,
                 m.fullname as collected_by
          FROM fee_payments p
          LEFT JOIN fee_categories fc ON p.category_id = fc.id
          LEFT JOIN rssimyaccount_members m ON p.collected_by = m.associatenumber
          WHERE p.student_id = '$studentId'";

if (!empty($month) && !empty($year)) {
    $query .= " AND p.month = '$month' AND p.academic_year = '$year'";
}

// Order by collection_date DESC to show newest payments first
$query .= " ORDER BY p.collection_date DESC, p.id DESC";

$result = pg_query($con, $query);
$payments = pg_fetch_all($result) ?? [];

// Get student info
$studentQuery = "SELECT studentname, class FROM rssimyprofile_student WHERE student_id = '$studentId'";
$studentResult = pg_query($con, $studentQuery);
$student = pg_fetch_assoc($studentResult);
?>

<div class="card">
    <div class="card-header bg-info text-white">
        <h5>Payment History for <?= $student['studentname'] ?> (<?= $student['class'] ?>) - <?= $month ?> <?= $year ?></h5>
    </div>
    <div class="card-body">
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
</div>