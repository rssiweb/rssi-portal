<?php
require_once __DIR__ . "/../../bootstrap.php";

$studentId = trim($_GET['student_id'] ?? '');
$monthYear = $_GET['month_year'] ?? date('Y-m');

if ($studentId === '') {
    die("Student ID is required");
}

// '!' resets unspecified fields, so the day is 1 (no more Sep 31 -> Oct 1)
$date = DateTime::createFromFormat('!Y-m', $monthYear) ?: new DateTime('first day of this month');

$month           = $date->format('F');      // September
$year            = $date->format('Y');      // 2026
$firstDayOfMonth = $date->format('Y-m-01'); // 2026-09-01
$lastDayOfMonth  = $date->format('Y-m-t');  // 2026-09-30

// ---------- Payments ----------
$paymentQuery = "SELECT p.id, p.academic_year, p.month,
                 COALESCE(fc.category_name, 'Previous Dues') AS category_name,
                 p.amount, p.payment_type, p.transaction_id,
                 p.collection_date, p.notes,
                 m.fullname AS collected_by
          FROM fee_payments p
          LEFT JOIN fee_categories fc ON p.category_id = fc.id
          LEFT JOIN rssimyaccount_members m ON p.collected_by = m.associatenumber
          WHERE p.student_id = $1 AND p.month = $2 AND p.academic_year = $3
          ORDER BY p.collection_date DESC, p.id DESC";

$payments = pg_fetch_all(pg_query_params($con, $paymentQuery, [$studentId, $month, $year])) ?: [];

// ---------- Concessions ----------
$concessionQuery = "SELECT sc.*, fc.category_name,
                    m.fullname AS created_by_name
             FROM student_concessions sc
             LEFT JOIN fee_categories fc ON sc.category_id = fc.id
             LEFT JOIN rssimyaccount_members m ON sc.created_by = m.associatenumber
             WHERE sc.student_id = $1
               AND (
                    -- concession period overlaps this month
                    (sc.effective_from <= $3::date
                     AND (sc.effective_until IS NULL OR sc.effective_until >= $2::date))
                    OR
                    -- or it starts inside this month
                    (sc.effective_from >= $2::date AND sc.effective_from <= $3::date)
               )
             ORDER BY sc.effective_from DESC, sc.id DESC";

$concessions = pg_fetch_all(
    pg_query_params($con, $concessionQuery, [$studentId, $firstDayOfMonth, $lastDayOfMonth])
) ?: [];

// ---------- Student info ----------
$student = pg_fetch_assoc(pg_query_params(
    $con,
    "SELECT studentname, class FROM rssimyprofile_student WHERE student_id = $1",
    [$studentId]
));

if (!$student) {
    die("Student not found");
}
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