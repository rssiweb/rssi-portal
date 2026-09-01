<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
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
$location = $_GET['location'] ?? '';

// Fetch locations for dropdown
$locationQuery = "SELECT id, name FROM office_locations WHERE is_active = true ORDER BY name";
$locationResult = pg_query($con, $locationQuery);
$locations = [];
if ($locationResult) {
    while ($row = pg_fetch_assoc($locationResult)) {
        $locations[] = $row;
    }
}

if ($status === 'unsettled') {
    // Get unsettled payments with location filter
    $paymentsQuery = "
SELECT p.*,
       COALESCE(s.studentname, m.fullname, h.name) AS student_name,
       s.class,
       c.fullname AS collector_name,
       eo.order_number,
       eo.order_id,
       fc.category_name AS source
FROM fee_payments p
LEFT JOIN rssimyprofile_student s ON p.student_id = s.student_id
LEFT JOIN rssimyaccount_members m ON p.student_id = m.associatenumber
LEFT JOIN public_health_records h ON p.student_id = h.id::text
LEFT JOIN emart_orders eo ON p.id = eo.payment_id
LEFT JOIN fee_payments fp ON eo.payment_id = fp.id
LEFT JOIN fee_categories fc ON fp.category_id=fc.id
JOIN rssimyaccount_members c ON p.collected_by = c.associatenumber
WHERE p.is_settled = FALSE";

    // Add location filter if selected
    if (!empty($location)) {
        // Get location name from ID
        $locationNameQuery = "SELECT name FROM office_locations WHERE id = '$location'";
        $locationNameResult = pg_query($con, $locationNameQuery);
        $locationNameRow = pg_fetch_assoc($locationNameResult);
        $locationName = $locationNameRow['name'];

        $paymentsQuery .= " AND s.preferredbranch = '$locationName'";
    }

    $paymentsQuery .= " ORDER BY p.id DESC";

    $paymentsResult = pg_query($con, $paymentsQuery);
    $payments = pg_fetch_all($paymentsResult) ?? [];

    // Get summary with location filter - FIXED: Added alias for payment_type
    $summaryQuery = "SELECT COUNT(*) as total_payments, 
                            SUM(p.amount) as total_amount,
                            SUM(CASE WHEN p.payment_type = 'cash' THEN p.amount ELSE 0 END) as cash_amount,
                            SUM(CASE WHEN p.payment_type = 'online' THEN p.amount ELSE 0 END) as online_amount
                     FROM fee_payments p
                     LEFT JOIN rssimyprofile_student s ON p.student_id = s.student_id
                     WHERE p.is_settled = FALSE";

    // Add location filter to summary if selected
    if (!empty($location)) {
        $locationNameQuery = "SELECT name FROM office_locations WHERE id = '$location'";
        $locationNameResult = pg_query($con, $locationNameQuery);
        $locationNameRow = pg_fetch_assoc($locationNameResult);
        $locationName = $locationNameRow['name'];

        $summaryQuery .= " AND s.preferredbranch = '$locationName'";
    }

    $summaryResult = pg_query($con, $summaryQuery);
    $summary = pg_fetch_assoc($summaryResult);
} else {
    // Get settled payments with location filter
    $settlementsQuery = "
SELECT s.*,
       COALESCE(m.fullname, h.name) AS settled_by_name,
       sl.location_name
FROM settlements s
LEFT JOIN rssimyaccount_members m ON s.settled_by = m.associatenumber
LEFT JOIN public_health_records h ON s.settled_by = h.id::text
LEFT JOIN (
    SELECT DISTINCT settlement_id, STRING_AGG(DISTINCT preferredbranch, ', ') AS location_name
    FROM (
        SELECT sp.settlement_id, stu.preferredbranch
        FROM settlement_payments sp
        JOIN fee_payments fp ON sp.payment_id = fp.id
        LEFT JOIN rssimyprofile_student stu ON fp.student_id = stu.student_id
        WHERE stu.preferredbranch IS NOT NULL
    ) loc_data
    GROUP BY settlement_id
) sl ON s.id = sl.settlement_id
WHERE 1=1";

    // Add location filter if selected
    if (!empty($location)) {
        $locationNameQuery = "SELECT name FROM office_locations WHERE id = '$location'";
        $locationNameResult = pg_query($con, $locationNameQuery);
        $locationNameRow = pg_fetch_assoc($locationNameResult);
        $locationName = $locationNameRow['name'];

        $settlementsQuery .= " AND s.id IN (
            SELECT DISTINCT sp.settlement_id
            FROM settlement_payments sp
            JOIN fee_payments fp ON sp.payment_id = fp.id
            LEFT JOIN rssimyprofile_student stu ON fp.student_id = stu.student_id
            WHERE stu.preferredbranch = '$locationName'
        )";
    }

    $settlementsQuery .= " ORDER BY s.settlement_date DESC";

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
    <?php include 'includes/meta.php' ?>

    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">
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

        #settlement-info .card-title,
        #settlement-summary-info .card-title {
            padding: 0;
            /* or correct padding value */
            color: var(--bs-card-title-color);
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
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container-fluid mt-4">
                                <div class="card">
                                    <div class="card-body">
                                        <!-- Filters -->
                                        <form method="get" class="row g-3 mb-4 mt-4">
                                            <input type="hidden" name="page" value="settlement">
                                            <div class="col-md-2">
                                                <label for="settlementDate" class="form-label">Settlement Date:</label>
                                                <input type="date" class="form-control" name="settlement_date" value="<?= $settlementDate ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label for="status" class="form-label">Status:</label>
                                                <select class="form-select" name="status">
                                                    <option value="unsettled" <?= $status === 'unsettled' ? 'selected' : '' ?>>Unsettled Payments</option>
                                                    <option value="settled" <?= $status === 'settled' ? 'selected' : '' ?>>Settled Payments</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="location" class="form-label">Location:</label>
                                                <select class="form-select" name="location">
                                                    <option value="">All Locations</option>
                                                    <?php foreach ($locations as $loc): ?>
                                                        <option value="<?php echo htmlspecialchars($loc['id']); ?>" <?php echo (isset($_GET['location']) && $_GET['location'] == $loc['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($loc['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
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
                                                            <th>Payer</th>
                                                            <th>Class</th>
                                                            <th>Month</th>
                                                            <th>Amount</th>
                                                            <th>Type</th>
                                                            <th>Transaction ID</th>
                                                            <th>Source</th>
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
                                                                <td><?= htmlspecialchars($payment['student_name']) ?></td>
                                                                <td><?= htmlspecialchars($payment['class'] ?? 'N/A') ?: 'N/A' ?></td>
                                                                <td><?= $payment['month'] ?>-<?= $payment['academic_year'] ?></td>
                                                                <td>₹<?= number_format($payment['amount'], 2) ?></td>
                                                                <td><?= ucfirst($payment['payment_type']) ?></td>
                                                                <td><?= $payment['transaction_id'] ?: 'N/A' ?></td>
                                                                <td>
                                                                    <?= isset($payment['source']) ? htmlspecialchars($payment['source']) : '' ?>
                                                                </td>

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
                                                            <th>Location(s)</th>
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
                                                                <td><?= htmlspecialchars($settlement['location_name'] ?? 'N/A') ?></td>
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
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

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
                        const amount = parseFloat(row.find("td:eq(6)").text().replace(/[^0-9.]/g, ''));
                        const type = row.find("td:eq(7)").text().toLowerCase();

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
                let url = "export_settlement.php?status=<?= $status ?>&settlement_date=<?= $settlementDate ?>";
                const location = $("select[name='location']").val();
                if (location) {
                    url += "&location=" + encodeURIComponent(location);
                }
                window.location.href = url;
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