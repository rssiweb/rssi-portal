<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: login.php");
    exit;
}
validation();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_settlement'])) {
        require_once __DIR__ . "/process_settlement.php";
    }
}

// Get settlement data
$settlementDate = $_GET['settlement_date'] ?? date('Y-m-d');
$status = $_GET['status'] ?? 'unsettled'; // 'unsettled' or 'settled'

if ($status === 'unsettled') {
    // Get unsettled payments
    $paymentsQuery = "SELECT p.*, s.studentname, s.class, m.fullname as collector_name
                      FROM fee_payments p
                      JOIN rssimyprofile_student s ON p.student_id = s.student_id
                      JOIN rssimyaccount_members m ON p.collected_by = m.associatenumber
                      WHERE p.is_settled = FALSE
                      ORDER BY p.collection_date";

    $paymentsResult = pg_query($con, $paymentsQuery);
    $payments = pg_fetch_all($paymentsResult) ?? [];

    // Get summary
    $summaryQuery = "SELECT COUNT(*) as total_payments, 
                            SUM(amount) as total_amount,
                            SUM(CASE WHEN payment_type = 'cash' THEN amount ELSE 0 END) as cash_amount,
                            SUM(CASE WHEN payment_type = 'online' THEN amount ELSE 0 END) as online_amount
                     FROM fee_payments
                     WHERE is_settled = FALSE";

    $summaryResult = pg_query($con, $summaryQuery);
    $summary = pg_fetch_assoc($summaryResult);
} else {
    // Get settled payments
    $settlementsQuery = "SELECT s.*, m.fullname as settled_by_name
                         FROM settlements s
                         JOIN rssimyaccount_members m ON s.settled_by = m.associatenumber
                         ORDER BY s.settlement_date DESC";

    $settlementsResult = pg_query($con, $settlementsQuery);
    $settlements = pg_fetch_all($settlementsResult) ?? [];
}

// Get collectors
$collectorsQuery = "SELECT associatenumber, fullname FROM rssimyaccount_members ORDER BY fullname";
$collectorsResult = pg_query($con, $collectorsQuery);
$collectors = pg_fetch_all($collectorsResult) ?? [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlement Management</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title"><i class="fas fa-calculator"></i> Settlement Management</h3>
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-light text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; margin-right: 8px;">
                            <?= strtoupper(substr($fullname, 0, 1)) ?>
                        </div>
                        <span><?= $fullname ?> (<?= $associatenumber ?>)</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <!-- <li><a class="dropdown-item" href="home.php"><i class="fas fa-home me-2"></i> Home</a></li> -->
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="get" class="row g-3 mb-4">
                    <input type="hidden" name="page" value="settlement">
                    <div class="col-md-3">
                        <label for="settlementDate" class="form-label">Settlement Date:</label>
                        <input type="date" class="form-control" name="settlement_date" value="<?= $settlementDate ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status:</label>
                        <select class="form-select" name="status">
                            <option value="unsettled" <?= $status === 'unsettled' ? 'selected' : '' ?>>Unsettled Payments</option>
                            <option value="settled" <?= $status === 'settled' ? 'selected' : '' ?>>Settled Payments</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-success w-100" id="exportSettlement">
                            <i class="fas fa-file-excel"></i> Export
                        </button>
                    </div>
                </form>

                <?php if ($status === 'unsettled'): ?>
                    <!-- Unsettled Payments -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card summary-card total">
                                <div class="card-body">
                                    <h5 class="card-title">Unsettled Payments</h5>
                                    <p class="card-text display-6"><?= $summary['total_payments'] ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card summary-card total">
                                <div class="card-body">
                                    <h5 class="card-title">Total Amount</h5>
                                    <p class="card-text display-6">₹<?= @number_format($summary['total_amount'], 2) ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card summary-card cash">
                                <div class="card-body">
                                    <h5 class="card-title">Cash Amount</h5>
                                    <p class="card-text display-6">₹<?= @number_format($summary['cash_amount'], 2) ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card summary-card online">
                                <div class="card-body">
                                    <h5 class="card-title">Online Amount</h5>
                                    <p class="card-text display-6">₹<?= @number_format($summary['online_amount'], 2) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-success" id="createSettlement" <?php if ($role !== 'Admin') echo 'disabled'; ?>>
                            <i class="fas fa-file-invoice-dollar"></i> Create Settlement
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered" id="paymentsTable">
                            <thead class="table">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" class="form-check-input" id="selectAllPayments">
                                    </th>
                                    <th>Payment ID</th>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Month</th>
                                    <th>Year</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Transaction ID</th>
                                    <th>Collector</th>
                                    <th>Data Entry Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input payment-check" data-id="<?= $payment['id'] ?>"></td>
                                        <td><?= $payment['id'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($payment['collection_date'])) ?></td>
                                        <td><?= htmlspecialchars($payment['studentname']) ?></td>
                                        <td><?= htmlspecialchars($payment['class']) ?></td>
                                        <td><?= $payment['month'] ?></td>
                                        <td><?= $payment['academic_year'] ?></td>
                                        <td>₹<?= number_format($payment['amount'], 2) ?></td>
                                        <td><?= ucfirst($payment['payment_type']) ?></td>
                                        <td><?= $payment['transaction_id'] ?: 'N/A' ?></td>
                                        <td><?= htmlspecialchars($payment['collector_name']) ?></td>
                                        <td><?= date('d/m/Y h:i A', strtotime($payment['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Settlement Modal -->
                    <div class="modal fade" id="settlementModal" tabindex="-1" aria-labelledby="settlementModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title" id="settlementModalLabel">Create Settlement</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post" id="settlementForm">
                                    <input type="hidden" name="create_settlement" value="1">
                                    <input type="hidden" id="settlementPaymentIds" name="payment_ids">
                                    <input type="hidden" id="settlementDate" name="settlement_date" value="<?= $settlementDate ?>">

                                    <div class="modal-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Total Amount:</label>
                                                <div class="form-control-plaintext fw-bold" id="settlementTotal">₹0.00</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Cash Amount:</label>
                                                <div class="form-control-plaintext fw-bold" id="settlementCash">₹0.00</div>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Online Amount:</label>
                                                <div class="form-control-plaintext fw-bold" id="settlementOnline">₹0.00</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="settledBy" class="form-label">Settled By:</label>
                                                <select class="form-select" id="settledBy" name="settled_by" required>
                                                    <?php foreach ($collectors as $collector): ?>
                                                        <option value="<?= $collector['associatenumber'] ?>" <?= $collector['associatenumber'] == $associatenumber ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($collector['fullname']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="settlementNotes" class="form-label">Notes:</label>
                                            <textarea class="form-control" id="settlementNotes" name="notes" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-success">Submit Settlement</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Settled Payments -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered" id="settlementsTable">
                            <thead class="table">
                                <tr>
                                    <th>Settlement ID</th>
                                    <th>Date</th>
                                    <th>Total Amount</th>
                                    <th>Cash Amount</th>
                                    <th>Online Amount</th>
                                    <th>Settled By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($settlements as $settlement): ?>
                                    <tr>
                                        <td><?= $settlement['id'] ?></td>
                                        <td><?= date('d-M-Y', strtotime($settlement['settlement_date'])) ?></td>
                                        <td>₹<?= number_format($settlement['total_amount'], 2) ?></td>
                                        <td>₹<?= number_format($settlement['cash_amount'], 2) ?></td>
                                        <td>₹<?= number_format($settlement['online_amount'], 2) ?></td>
                                        <td><?= htmlspecialchars($settlement['settled_by_name']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info view-settlement" data-id="<?= $settlement['id'] ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-warning print-settlement" data-id="<?= $settlement['id'] ?>">
                                                <i class="fas fa-print"></i> Print
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Settlement Details Modal -->
                    <div class="modal fade" id="settlementDetailsModal" tabindex="-1" aria-labelledby="settlementDetailsModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <div class="modal-header bg-info text-white">
                                    <h5 class="modal-title" id="settlementDetailsModalLabel">Settlement Details</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="settlementDetailsContent">
                                        <div id="settlementLoading" class="text-center py-4" style="display: none;">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <div>Loading settlement details...</div>
                                        </div>
                                        <!-- Actual content will be injected here -->
                                        <div id="settlementLoadedContent"></div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" id="printSettlementDetails">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
    <script>
        $(document).ready(function() {
            <?php if ($status === 'unsettled'): ?>
                // Select all payments checkbox
                $("#selectAllPayments").change(function() {
                    $(".payment-check").prop("checked", $(this).prop("checked"));
                });

                // Create settlement button handler
                $("#createSettlement").click(function() {
                    const checkedPayments = $(".payment-check:checked");
                    if (checkedPayments.length === 0) {
                        alert("Please select at least one payment to settle");
                        return;
                    }

                    const paymentIds = checkedPayments.map(function() {
                        return $(this).data("id");
                    }).get();

                    $("#settlementPaymentIds").val(paymentIds.join(","));

                    // Calculate totals
                    let total = 0,
                        cash = 0,
                        online = 0;
                    checkedPayments.each(function() {
                        const row = $(this).closest("tr");
                        const amount = parseFloat(row.find("td:eq(7)").text().replace(/[^0-9.]/g, ''));
                        const type = row.find("td:eq(8)").text().toLowerCase();

                        total += amount;
                        if (type === 'cash') {
                            cash += amount;
                        } else {
                            online += amount;
                        }
                    });

                    $("#settlementTotal").text("₹" + total.toFixed(2));
                    $("#settlementCash").text("₹" + cash.toFixed(2));
                    $("#settlementOnline").text("₹" + online.toFixed(2));

                    const settlementModal = new bootstrap.Modal(document.getElementById("settlementModal"));
                    settlementModal.show();
                });
            <?php else: ?>
                // View settlement button handler
                $(".view-settlement").click(function() {
    const settlementId = $(this).data("id");

    // Show spinner, hide old content
    $("#settlementLoading").show();
    $("#settlementLoadedContent").html('');

    const detailsModal = new bootstrap.Modal(document.getElementById("settlementDetailsModal"));
    detailsModal.show();

    $.ajax({
        url: "get_settlement_details.php",
        method: "GET",
        data: {
            settlement_id: settlementId
        },
        success: function(data) {
            $("#settlementLoading").hide();
            $("#settlementLoadedContent").html(data);
        },
        error: function(xhr, status, error) {
            $("#settlementLoading").hide();
            $("#settlementLoadedContent").html('<div class="text-danger">Error loading settlement details: ' + error + '</div>');
        }
    });
});


                // Print settlement button handler
                $(".print-settlement").click(function() {
                    const settlementId = $(this).data("id");
                    window.open("print_settlement.php?settlement_id=" + settlementId, "_blank");
                });

                // Print button in modal
                $("#printSettlementDetails").click(function() {
                    const settlementId = $(".view-settlement").data("id");
                    window.open("print_settlement.php?settlement_id=" + settlementId, "_blank");
                });
            <?php endif; ?>

            // Export button handler
            $("#exportSettlement").click(function() {
                window.location.href = "export_settlement.php?status=<?= $status ?>&settlement_date=<?= $settlementDate ?>";
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            <?php if (!empty($payments)) : ?>
                $('#paymentsTable').DataTable({
                    paging: false,
                    order: [], // Disable initial sorting
                    columnDefs: [{
                            targets: 0,
                            orderable: false
                        } // Disable sorting on the first column (index 0)
                    ]
                });
            <?php endif; ?>

            <?php if (!empty($settlements)) : ?>
                $('#settlementsTable').DataTable({
                    paging: false,
                    order: [] // Disable initial sorting
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>