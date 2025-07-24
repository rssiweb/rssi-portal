<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    header("Location: index.php");
    exit;
}

// Get all batches with counts
$query = "
    SELECT 
        b.batch_id,
        b.created_by,
        m.fullname AS created_by_name,
        b.created_date,
        o.status,
        b.vendor_name,
        b.admin_remarks,
        b.ordered_date,
        MAX(o.delivered_date) AS delivered_date,
        MAX(o.delivered_remarks) AS delivered_remarks,
        COUNT(o.id) AS item_count,
        MIN(o.academic_year) AS academic_year,
        MIN(o.order_date) AS start_date,
        MAX(o.order_date) AS end_date
    FROM id_card_batches b
    INNER JOIN id_card_orders o ON b.batch_id = o.batch_id
    LEFT JOIN rssimyaccount_members m ON b.created_by = m.associatenumber
    GROUP BY 
        b.batch_id, b.created_by, m.fullname, b.created_date, o.status, 
        b.vendor_name, b.admin_remarks, b.ordered_date
    ORDER BY start_date DESC
";

$batches = pg_query($con, $query);
$batches = pg_fetch_all($batches) ?: [];

?>

<!DOCTYPE html>
<html>

<head>
    <title>ID Card Order History</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <style>
        .badge-delivered {
            background-color: #198754;
        }

        .badge-ordered {
            background-color: #ffc107;
            color: #000;
        }

        .badge-pending {
            background-color: #0d6efd;
        }

        .batch-id-link {
            cursor: pointer;
            color: #0d6efd;
            text-decoration: underline;
        }

        .selected-row {
            background-color: #e6f2ff !important;
        }

        #deliveryRemarksModal textarea {
            min-height: 120px;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>ICOM</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="id.php">ICOM</a></li>
                    <li class="breadcrumb-item active">ID Card Order History</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <div class="py-4">
                                <div class="card shadow">
                                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                        <h2 class="h4 mb-0"><i class="bi bi-clock-history"></i> ID Card Order History</h2>
                                        <button id="exportSelected" class="btn btn-light btn-sm" disabled>
                                            <i class="bi bi-download"></i> Export Selected
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="historyTable" class="table table-striped table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><input class="form-check-input" type="checkbox" id="selectAll"></th>
                                                        <th>Batch ID</th>
                                                        <!-- <th>Academic Year</th> -->
                                                        <th>Date Range</th>
                                                        <th>Order Date</th>
                                                        <th>Items</th>
                                                        <th>Status</th>
                                                        <th>Vendor</th>
                                                        <th>Admin Remarks</th>
                                                        <th>Delivered Date</th>
                                                        <th>Delivered Remarks</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($batches as $batch): ?>
                                                        <tr data-batch-id="<?= htmlspecialchars($batch['batch_id']) ?>">
                                                            <td><input class="form-check-input" type="checkbox" class="batch-checkbox"></td>
                                                            <td>
                                                                <span class="batch-id-link" data-batch="<?= htmlspecialchars($batch['batch_id']) ?>">
                                                                    <code><?= htmlspecialchars($batch['batch_id']) ?></code>
                                                                </span>
                                                            </td>
                                                            <!-- <td><?= htmlspecialchars($batch['academic_year']) ?></td> -->
                                                            <td>
                                                                <?= date('d M Y', strtotime($batch['start_date'])) ?> -
                                                                <?= date('d M Y', strtotime($batch['end_date'])) ?>
                                                            </td>
                                                            <td>
                                                                <?= isset($batch['ordered_date']) && $batch['ordered_date']
                                                                    ? htmlspecialchars(date('d M Y', strtotime($batch['ordered_date'])))
                                                                    : '-' ?>
                                                            </td>
                                                            <td><?= $batch['item_count'] ?></td>
                                                            <td>
                                                                <span class="badge <?= $batch['status'] === 'Delivered' ? 'bg-success' : ($batch['status'] === 'Ordered' ? 'bg-warning text-dark' : 'bg-primary') ?>">
                                                                    <?= $batch['status'] ?>
                                                                </span>
                                                            </td>
                                                            <td><?= htmlspecialchars($batch['vendor_name'] ?? '-') ?></td>
                                                            <td><?= htmlspecialchars($batch['admin_remarks'] ?? '-') ?></td>
                                                            <td>
                                                                <?= isset($batch['delivered_date']) && $batch['delivered_date']
                                                                    ? htmlspecialchars(date('d M Y', strtotime($batch['delivered_date'])))
                                                                    : '-' ?>
                                                            </td>
                                                            <td><?= htmlspecialchars($batch['delivered_remarks'] ?? '-') ?></td>
                                                            <td>
                                                                <?php if ($role === 'Admin' && $batch['status'] === 'Ordered'): ?>
                                                                    <button class="btn btn-sm btn-success mark-delivered" data-batch="<?= $batch['batch_id'] ?>">
                                                                        <i class="bi bi-check-circle"></i> Mark Delivered
                                                                    </button>
                                                                    <div id="deliver-spinner-<?= $batch['batch_id'] ?>" class="spinner-border text-success d-none" role="status">
                                                                        <span class="visually-hidden">Loading...</span>
                                                                    </div>
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
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Delivery Remarks Modal -->
    <div class="modal fade" id="deliveryRemarksModal" tabindex="-1" aria-labelledby="deliveryRemarksModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="deliveryRemarksModalLabel">Mark Batch as Delivered</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Batch ID: <strong id="deliveryBatchId"></strong></p>
                    <div class="mb-3">
                        <label for="deliveryRemarks" class="form-label">Delivery Remarks</label>
                        <textarea class="form-control" id="deliveryRemarks" placeholder="Enter any remarks about the delivery"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmDelivery">Confirm Delivery</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Batch Details Modal -->
    <div class="modal fade" id="batchDetailsModal" tabindex="-1" aria-labelledby="batchDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="batchDetailsModalLabel">Batch Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="batchDetailsContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            <?php if (!empty($batches)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#historyTable').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>

            // Row selection functionality
            $('#selectAll').change(function() {
                $('.batch-checkbox').prop('checked', this.checked);
                updateExportButton();
            });

            $('tbody').on('change', '.batch-checkbox', function() {
                if (!this.checked) {
                    $('#selectAll').prop('checked', false);
                }
                updateExportButton();
            });

            function updateExportButton() {
                const selectedCount = $('.batch-checkbox:checked').length;
                $('#exportSelected').prop('disabled', selectedCount === 0);
            }

            // Export selected batches
            $('#exportSelected').click(function() {
                const selectedBatches = [];
                $('.batch-checkbox:checked').each(function() {
                    selectedBatches.push($(this).closest('tr').data('batch-id'));
                });

                if (selectedBatches.length > 0) {
                    window.location.href = 'id_export_batch.php?batch_ids=' + selectedBatches.join(',');
                }
            });

            // Batch details modal
            $('.batch-id-link').click(function() {
                const batchId = $(this).data('batch');
                $('#batchDetailsModalLabel').text('Batch Details: ' + batchId);

                // Show loading content
                $('#batchDetailsContent').html(`
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p>Loading batch details...</p>
                    </div>
                `);

                // Load batch details
                $.get('id_get_batch_details.php', {
                    batch_id: batchId
                }, function(data) {
                    $('#batchDetailsContent').html(data);
                }).fail(function() {
                    $('#batchDetailsContent').html(`
                        <div class="alert alert-danger">
                            Failed to load batch details. Please try again.
                        </div>
                    `);
                });

                $('#batchDetailsModal').modal('show');
            });

            // Delivery remarks modal
            let currentBatchId = null;
            $('.mark-delivered').click(function() {
                currentBatchId = $(this).data('batch');
                $('#deliveryBatchId').text(currentBatchId);
                $('#deliveryRemarks').val('');
                $('#deliveryRemarksModal').modal('show');
            });

            $('#confirmDelivery').click(function() {
                const remarks = $('#deliveryRemarks').val();
                const button = $(`.mark-delivered[data-batch="${currentBatchId}"]`);
                const spinner = $(`#deliver-spinner-${currentBatchId}`);

                button.prop('disabled', true);
                spinner.removeClass('d-none');
                $('#deliveryRemarksModal').modal('hide');

                $.post('id_process_order.php', {
                    action: 'mark_delivered',
                    batch_id: currentBatchId,
                    remarks: remarks
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message);
                        button.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                }, 'json');
            });
        });
    </script>
</body>

</html>