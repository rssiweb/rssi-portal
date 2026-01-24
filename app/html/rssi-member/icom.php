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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>
    
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }

        .card-batch {
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
            cursor: pointer;
        }

        .card-batch:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .card-batch.active {
            border-left: 4px solid var(--success-color);
            background-color: rgba(28, 200, 138, 0.05);
        }

        .badge-public {
            background-color: var(--primary-color);
        }

        .badge-restricted {
            background-color: var(--secondary-color);
        }

        .badge-pending {
            background-color: var(--warning-color);
        }

        .badge-ordered {
            background-color: var(--success-color);
        }

        .student-photo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }

        .batch-actions {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 1rem;
            border-top: 1px solid #eee;
            z-index: 10;
        }

        .search-container {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }

        .batch-header {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Open Batches</h5>
                            <button id="create-new-batch" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg"></i> New Batch
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush" id="batch-list">
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0" id="current-batch-title">Select a Batch</h5>
                        </div>
                        <div class="card-body" id="batch-details-container">
                            <div class="empty-state">
                                <i class="bi bi-folder2-open"></i>
                                <h5>No batch selected</h5>
                                <p>Please select a batch from the list or create a new one</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Create Batch Modal -->
        <div class="modal fade" id="createBatchModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Batch</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="batch-form">
                            <div class="mb-3">
                                <label class="form-label">Batch Name (Optional)</label>
                                <input type="text" class="form-control" id="batch-name" placeholder="e.g. January 2023 Batch">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Batch Type</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="batch-type" id="batch-type-public" value="Public" checked>
                                    <label class="form-check-label" for="batch-type-public">
                                        Public (Visible to all users)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="batch-type" id="batch-type-restricted" value="Restricted">
                                    <label class="form-check-label" for="batch-type-restricted">
                                        Restricted (Only visible to you and admins)
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirm-create-batch">
                            Create Batch
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Students Modal -->
        <div class="modal fade" id="addStudentsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Students to Batch</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Search Student/Associate</label>
                                <select name="student_ids[]" id="student-select" class="form-control select2" multiple="multiple"></select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Order Type</label>
                                <select id="order-type" class="form-select">
                                    <option value="New">New</option>
                                    <option value="Reissue">Reissue</option>
                                </select>
                            </div>
                            <div class="col-md-3" id="payment-status-container">
                                <label class="form-label">Payment Status</label>
                                <select id="payment-status" class="form-select">
                                    <option value="">Select Status</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Unpaid">Unpaid</option>
                                    <option value="Partial">Partial Payment</option>
                                    <option value="Waived">Fee Waived</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Remarks</label>
                                <textarea id="order-remarks" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="add-to-batch">
                            <span class="spinner-border spinner-border-sm d-none" id="add-spinner"></span>
                            Add to Batch
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Confirmation Modal -->
        <div class="modal fade" id="orderConfirmationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">Confirm Order Placement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>You are about to place orders for the selected batches. This action cannot be undone.</p>
                        <div class="mb-3">
                            <label class="form-label">Vendor Name</label>
                            <input type="text" class="form-control" id="vendor-name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Admin Remarks</label>
                            <textarea class="form-control" id="admin-remarks" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Order Date & Time</label>
                            <input type="datetime-local" class="form-control" id="order-date-time" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning text-white" id="confirm-place-orders">
                            <span class="spinner-border spinner-border-sm d-none" id="order-spinner"></span>
                            Confirm Order Placement
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Order Modal -->
        <div class="modal fade" id="editOrderModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Order Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="edit-order-form">
                            <input type="hidden" id="edit-order-id">
                            <div class="mb-3">
                                <h6>Student: <span id="edit-student-name"></span> (<span id="edit-student-id"></span>)</h6>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Order Type</label>
                                <select id="edit-order-type" class="form-select" required>
                                    <option value="New">New</option>
                                    <option value="Reissue">Reissue</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Status</label>
                                <select id="edit-payment-status" class="form-select">
                                    <option value="">Select Status</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Unpaid">Unpaid</option>
                                    <option value="Partial">Partial Payment</option>
                                    <option value="Waived">Fee Waived</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment ID</label>
                                <input type="text" id="edit-payment-id" class="form-control" placeholder="Enter Payment ID if applicable">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea id="edit-remarks" class="form-control" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="save-order-changes">
                            <span class="spinner-border spinner-border-sm d-none" id="save-spinner"></span>
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- Bootstrap 5.3 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const orderType = document.getElementById("edit-order-type");
            const paymentStatus = document.getElementById("edit-payment-status");
            const paymentId = document.getElementById("edit-payment-id");

            // Function to handle enabling/disabling based on conditions
            function updateFieldStates() {
                const orderTypeValue = orderType.value;
                const paymentStatusValue = paymentStatus.value;

                if (orderTypeValue === "New") {
                    // For New orders
                    paymentStatus.value = "";
                    paymentStatus.disabled = true;
                    paymentStatus.required = false;

                    paymentId.value = "";
                    paymentId.disabled = true;
                    paymentId.required = false;

                } else if (orderTypeValue === "Reissue") {
                    // For Reissue orders
                    paymentStatus.disabled = false;
                    paymentStatus.required = true;

                    if (paymentStatusValue === "Paid") {
                        paymentId.disabled = false;
                        paymentId.required = true;
                    } else {
                        paymentId.value = "";
                        paymentId.disabled = true;
                        paymentId.required = false;
                    }
                }
            }

            // Event listeners
            orderType.addEventListener("change", updateFieldStates);
            paymentStatus.addEventListener("change", updateFieldStates);

            // Initialize on modal show (Bootstrap 5 event)
            const editOrderModal = document.getElementById("editOrderModal");
            editOrderModal.addEventListener("shown.bs.modal", function() {
                updateFieldStates();
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            let currentBatchId = null;
            let addedStudentIds = [];
            let hasOpenPublicBatch = false;
            const batchModal = new bootstrap.Modal('#createBatchModal');
            const addStudentsModal = new bootstrap.Modal('#addStudentsModal');
            const orderConfirmationModal = new bootstrap.Modal('#orderConfirmationModal');
            let selectedBatches = [];

            // Initialize Select2 when the modal is shown
            $('#addStudentsModal').on('shown.bs.modal', function() {
                $('#student-select').select2({
                    ajax: {
                        url: 'search_beneficiaries.php',
                        dataType: 'json',
                        delay: 500,
                        data: function(params) {
                            return {
                                q: params.term,
                                isStockout: true
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 2,
                    placeholder: 'Search by ID or Name',
                    width: '100%',
                    dropdownParent: $('#addStudentsModal') // This is crucial for proper dropdown positioning
                });
            });

            // Destroy Select2 when modal is hidden to prevent issues
            $('#addStudentsModal').on('hidden.bs.modal', function() {
                $('#student-select').select2('destroy');
            });

            // Toggle payment status field based on order type
            function togglePaymentStatus() {
                if ($('#order-type').val() === 'Reissue') {
                    $('#payment-status-container').show();
                    $('#payment-status').prop('required', true);
                } else {
                    $('#payment-status-container').hide();
                    $('#payment-status').prop('required', false);
                }
            }

            // Initialize toggle
            togglePaymentStatus();
            $('#order-type').change(togglePaymentStatus);

            // Load all open batches
            function loadOpenBatches() {
                $('#batch-list').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');

                $.get('id_process_order.php', {
                    action: 'get_open_batches'
                }, function(response) {
                    $('#batch-list').empty();

                    if (response.success) {
                        hasOpenPublicBatch = response.data.some(batch => batch.batch_type === 'Public');

                        if (response.count > 0) {
                            response.data.forEach(batch => {
                                const isRestricted = batch.batch_type === 'Restricted';
                                const isAdmin = <?= $role === 'Admin' ? 'true' : 'false' ?>;
                                const isCreator = batch.created_by === '<?= $associatenumber ?>';

                                // Show batch only if: public OR (restricted AND (creator OR admin))
                                if (!isRestricted || isCreator || isAdmin) {
                                    const batchItem = `
                            <div class="list-group-item list-group-item-action batch-item ${batch.batch_id === currentBatchId ? 'active' : ''}" 
                                 data-batch-id="${batch.batch_id}" 
                                 data-batch-type="${batch.batch_type}"
                                 data-created-by="${batch.created_by}">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">${batch.batch_name || 'ID Card Batch'}</h6>
                                    <span class="badge ${batch.batch_type === 'Public' ? 'bg-primary' : 'bg-secondary'}">
                                        ${batch.batch_type}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">
                                    ${batch.batch_id}
                                </small>
                                </div>
                                <small class="text-muted">
                                    Created: ${new Date(batch.created_date).toLocaleString()}
                                </small>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="small">
                                        <i class="bi bi-person"></i> ${batch.created_by_name}
                                    </span>
                                    <span class="badge bg-warning text-dark">
                                        ${batch.item_count} items
                                    </span>
                                </div>
                            </div>
                        `;
                                    $('#batch-list').append(batchItem);
                                }
                            });
                        } else {
                            $('#batch-list').html(`
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                        <p class="mt-2">No open batches found</p>
                    </div>
                `);
                        }
                    } else {
                        $('#batch-list').html(`
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Failed to load batches
                </div>
            `);
                    }
                }, 'json');
            }

            // Load batch details
            function loadBatchDetails(batchId) {
                currentBatchId = batchId;
                $('.batch-item').removeClass('active');
                $(`.batch-item[data-batch-id="${batchId}"]`).addClass('active');

                $('#batch-details-container').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);

                $.get('id_process_order.php', {
                    action: 'get_batch_details',
                    batch_id: batchId
                }, function(response) {
                    if (response.success) {
                        const batch = response.batch;
                        const items = response.items;
                        const isAdmin = <?= $role === 'Admin' ? 'true' : 'false' ?>;
                        const isCreator = batch.created_by === '<?= $associatenumber ?>';
                        const canEdit = batch.status === 'Pending' && (isCreator || isAdmin);

                        $('#current-batch-title').html(`
                            ${batch.batch_name || 'ID Card Batch'} 
                            <span class="badge ${batch.batch_type === 'Public' ? 'bg-primary' : 'bg-secondary'}">
                                ${batch.batch_type}
                            </span>
                            <span class="badge ${batch.status === 'Pending' ? 'bg-warning text-dark' : 'bg-success'}">
                                ${batch.status}
                            </span>
                        `);

                        let batchDetails = `
                            <div class="batch-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">Created by ${batch.created_by_name} on ${new Date(batch.created_date).toLocaleString()}</span>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-primary" id="add-students-btn">
                                        <i class="bi bi-plus-lg"></i> Add Students
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Photo</th>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Status</th>
                                            <th>Type</th>
                                            <th>Payment</th>
                                            <th>Payment Id</th>
                                            <th>Remarks</th>
                                            <th>Last Issued</th>
                                            <th>Times Issued</th>
                                            <th>Requested By</th>
                                            <th>Date</th>
                                            ${canEdit ? '<th>Actions</th>' : ''}
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        if (items.length > 0) {
                            addedStudentIds = [];
                            items.forEach(item => {
                                addedStudentIds.push(item.student_id);

                                batchDetails += `
                                    <tr>
                                        <td>
                                            <img src="${item.photourl || 'default_photo.jpg'}" class="student-photo"/>
                                        </td>
                                        <td>${item.student_id}</td>
                                        <td>${item.studentname}</td>
                                        <td>${item.filterstatus}</td>
                                        <td>
                                            <span class="badge ${item.order_type === 'New' ? 'bg-primary' : 'bg-secondary'}">
                                                ${item.order_type}
                                            </span>
                                        </td>
                                        <td>${item.payment_status || '-'}</td>
                                        <td>${item.payment_id || '-'}</td>
                                        <td>${item.remarks || '-'}</td>
                                        <td>${item.last_issued ? new Date(item.last_issued).toLocaleDateString('en-GB') : '-'}</td>
                                        <td>${item.times_issued || '-'}</td>
                                        <td>${item.order_placed_by_name}</td>
                                        <td>${item.order_date ? new Date(item.order_date).toLocaleDateString('en-GB') : '-'}</td>
                                        ${canEdit ? `
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary edit-order-btn" data-id="${item.id}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-outline-danger remove-item" data-id="${item.id}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                        ` : ''}
                                    </tr>
                                `;
                            });
                        } else {
                            batchDetails += `
                                <tr>
                                    <td colspan="${canEdit ? 12 : 11}" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox"></i> No items in this batch
                                    </td>
                                </tr>
                            `;
                        }

                        batchDetails += `
                                    </tbody>
                                </table>
                            </div>
                        `;

                        // Add batch actions for admin
                        if (isAdmin) {
                            batchDetails += `
                            <div class="batch-actions d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <span class="text-muted">${items.length} items in batch</span>
                                </div>
                                <div>
                                    ${items.length > 0 && batch.status === 'Pending' ? `
                                    <button class="btn btn-sm btn-success" id="place-order-btn" data-batch-id="${batch.batch_id}">
                                        <i class="bi bi-send-check"></i> Place Order for All
                                    </button>
                                    ` : ''}
                                    <button class="btn btn-sm btn-secondary ms-2" id="export-batch-btn" data-batch-id="${batch.batch_id}">
                                        <i class="bi bi-download"></i> Export
                                    </button>
                                </div>
                            </div>
                            `;
                        }

                        $('#batch-details-container').html(batchDetails);

                        // Set up event handlers
                        $('#add-students-btn').click(function() {
                            addStudentsModal.show();
                        });

                        $('.remove-item').click(function() {
                            const itemId = $(this).data('id');
                            if (confirm('Are you sure you want to remove this item from the batch?')) {
                                const btn = $(this);
                                btn.html('<span class="spinner-border spinner-border-sm"></span>');
                                btn.prop('disabled', true);

                                $.post('id_process_order.php', {
                                    action: 'remove_item',
                                    id: itemId
                                }, function(response) {
                                    if (response.success) {
                                        loadBatchDetails(currentBatchId);
                                        loadOpenBatches();
                                    } else {
                                        alert(response.message);
                                        btn.html('<i class="bi bi-trash"></i>');
                                        btn.prop('disabled', false);
                                    }
                                }, 'json');
                            }
                        });

                        // Top of your script (with other modal declarations)
                        const editOrderModal = new bootstrap.Modal('#editOrderModal');
                        let currentEditOrderId = null; // Track currently edited order

                        // Ensure handler is only bound once
                        $(document).off('click', '.edit-order-btn').on('click', '.edit-order-btn', function() {
                            const orderId = $(this).data('id');
                            const btn = $(this);
                            const row = btn.closest('tr');

                            btn.html('<span class="spinner-border spinner-border-sm"></span>')
                                .prop('disabled', true);

                            $.get('id_process_order.php', {
                                    action: 'get_order_details',
                                    id: orderId
                                })
                                .done(function(response) {
                                    if (response.success) {
                                        currentEditOrderId = orderId;
                                        $('#edit-student-name').text(row.find('td:eq(2)').text());
                                        $('#edit-student-id').text(row.find('td:eq(1)').text());
                                        $('#edit-order-id').val(orderId);
                                        $('#edit-order-type').val(response.data.order_type);
                                        $('#edit-payment-status').val(response.data.payment_status || '');
                                        $('#edit-remarks').val(response.data.remarks || '');

                                        editOrderModal.show();
                                    } else {
                                        alert(response.message);
                                    }
                                })
                                .always(function() {
                                    btn.html('<i class="bi bi-pencil"></i>').prop('disabled', false);
                                });
                        });

                        // Save handler — only bound once
                        $(document).off('click', '#save-order-changes').on('click', '#save-order-changes', function() {
                            const btn = $(this);
                            btn.prop('disabled', true);
                            $('#save-spinner').removeClass('d-none');

                            const orderType = $('#edit-order-type').val();
                            const paymentStatus = $('#edit-payment-status').val();
                            const paymentId = $('#edit-payment-id').val().trim();

                            // Validation logic
                            if (orderType === 'Reissue') {
                                if (!paymentStatus) {
                                    alert('Please select a Payment Status for Reissue orders.');
                                    btn.prop('disabled', false);
                                    $('#save-spinner').addClass('d-none');
                                    return;
                                }

                                if ((paymentStatus === 'Paid' || paymentStatus === 'Partial') && !paymentId) {
                                    alert('Please enter a Payment ID for Paid status.');
                                    btn.prop('disabled', false);
                                    $('#save-spinner').addClass('d-none');
                                    return;
                                }
                            }

                            $.post('id_process_order.php', {
                                    action: 'update_order',
                                    id: $('#edit-order-id').val(),
                                    order_type: $('#edit-order-type').val(),
                                    payment_status: $('#edit-payment-status').val(),
                                    payment_id: $('#edit-payment-id').val().trim(),
                                    remarks: $('#edit-remarks').val()
                                })
                                .done(function(response) {
                                    if (response.success) {
                                        editOrderModal.hide();
                                        loadBatchDetails(currentBatchId);
                                        loadOpenBatches();
                                    } else {
                                        alert(response.message);
                                    }
                                })
                                .always(function() {
                                    btn.prop('disabled', false);
                                    $('#save-spinner').addClass('d-none');
                                });
                        });

                        // Cleanup form on modal hide
                        $('#editOrderModal').on('hidden.bs.modal', function() {
                            $(this).find('form')[0].reset();
                            currentEditOrderId = null;
                        });

                        if (isAdmin) {
                            // Place order button click
                            $('#place-order-btn').click(function() {
                                selectedBatches = [$(this).data('batch-id')];
                                orderConfirmationModal.show();
                            });

                            $('#export-batch-btn').click(function() {
                                const batchId = $(this).data('batch-id');
                                window.location.href = 'id_export_batch.php?batch_id=' + encodeURIComponent(batchId);
                            });
                        }
                    } else {
                        $('#batch-details-container').html(`
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> ${response.message || 'Failed to load batch details'}
                            </div>
                        `);
                    }
                }, 'json');
            }

            // Create new batch
            $('#create-new-batch').click(function() {
                $('#public-batch-warning').addClass('d-none');
                $('#batch-form')[0].reset();
                batchModal.show();
            });

            $('#confirm-create-batch').click(function() {
                const batchName = $('#batch-name').val();
                const batchType = $('input[name="batch-type"]:checked').val();

                const btn = $(this);
                btn.html('<span class="spinner-border spinner-border-sm"></span> Creating...');
                btn.prop('disabled', true);

                // Check if trying to create public batch when one exists
                if (batchType === 'Public' && hasOpenPublicBatch) {
                    if (!confirm('There is already an open public batch. Are you sure you want to create another public batch?')) {
                        btn.html('Create Batch');
                        btn.prop('disabled', false);
                        return;
                    }
                }

                $.post('id_process_order.php', {
                        action: 'create_batch',
                        batch_name: batchName,
                        batch_type: batchType,
                        created_by: '<?= $associatenumber ?>'
                    }, function(response) {
                        if (response.success) {
                            batchModal.hide();
                            currentBatchId = response.batch_id;
                            loadOpenBatches();
                            loadBatchDetails(response.batch_id);
                        } else {
                            alert(response.message);
                            btn.html('Create Batch');
                            btn.prop('disabled', false);
                        }
                    }, 'json')
                    .fail(() => {
                        alert('An error occurred while creating the batch');
                        btn.html('Create Batch');
                        btn.prop('disabled', false);
                    });
            });

            // Add students to batch
            $('#add-to-batch').click(function() {
                const selectedValues = $('#student-select').val();
                if (!selectedValues || selectedValues.length === 0) {
                    alert('Please select at least one student');
                    return;
                }

                if ($('#order-type').val() === 'Reissue' && !$('#payment-status').val()) {
                    alert('Payment status is required for Reissue orders');
                    return;
                }

                const duplicates = selectedValues.filter(id => addedStudentIds.includes(id));
                if (duplicates.length > 0) {
                    alert(`These profiles are already in the batch: ${duplicates.join(', ')}`);
                    return;
                }

                const btn = $(this);
                const spinner = $('#add-spinner');
                btn.prop('disabled', true);
                spinner.removeClass('d-none');

                const promises = selectedValues.map(studentId =>
                    $.post('id_process_order.php', {
                        action: 'add',
                        batch_id: currentBatchId,
                        student_id: studentId,
                        order_type: $('#order-type').val(),
                        payment_status: $('#payment-status').val(),
                        remarks: $('#order-remarks').val()
                    })
                );

                Promise.all(promises)
                    .then(responses => {
                        const failed = responses.filter(r => !r.success);
                        const pendingOrders = responses.filter(r => !r.success && r.existing_batch);

                        if (failed.length > 0) {
                            let errorMessage = `Failed to add ${failed.length} items. Successfully added ${selectedValues.length - failed.length} items.`;

                            // If there are pending orders in other batches
                            if (pendingOrders.length > 0) {
                                errorMessage += '\n\nSome students have existing orders in other batches:';
                                pendingOrders.forEach(order => {
                                    errorMessage += `\n- ${order.student_id}: ${order.message}`;
                                });

                                errorMessage += '\n\nWould you like to view these batches?';

                                if (confirm(errorMessage)) {
                                    // Open the first batch with pending orders
                                    loadBatchDetails(pendingOrders[0].existing_batch);
                                }
                            } else {
                                alert(errorMessage);
                            }
                        } else {
                            alert(`Successfully added ${selectedValues.length} items to the batch.`);
                            $('#student-select').val(null).trigger('change');
                            $('#order-remarks').val('');
                        }

                        loadBatchDetails(currentBatchId);
                        loadOpenBatches();
                        addStudentsModal.hide();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while adding items to the batch: ' + error.message);
                    })
                    .finally(() => {
                        btn.prop('disabled', false);
                        spinner.addClass('d-none');
                    });
            });

            // Place orders (admin)
            $('#confirm-place-orders').click(function() {
                const vendorName = $('#vendor-name').val();
                if (!vendorName) {
                    alert('Please enter vendor name');
                    return;
                }

                const btn = $(this);
                const spinner = $('#order-spinner');
                btn.prop('disabled', true);
                spinner.removeClass('d-none');

                $.post('id_process_order.php', {
                        action: 'place_orders',
                        batch_ids: JSON.stringify(selectedBatches),
                        vendor_name: vendorName,
                        remarks: $('#admin-remarks').val(),
                        order_date: $('#order-date-time').val()
                    }, function(response) {
                        if (response.success) {
                            // Show success message and reload after OK
                            alert('Order placed successfully for Batch: ' + response.batch_id);
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }, 'json')
                    .fail(function(xhr, status, error) {
                        alert('Request failed: ' + error);
                    })
                    .always(() => {
                        btn.prop('disabled', false);
                        spinner.addClass('d-none');
                    });
            });

            // Batch item click handler
            $(document).on('click', '.batch-item', function() {
                const batchId = $(this).data('batch-id');
                loadBatchDetails(batchId);
            });

            // Initial load
            loadOpenBatches();
        });
    </script>
</body>

</html>