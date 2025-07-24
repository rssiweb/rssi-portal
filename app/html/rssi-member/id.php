<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Replace the batch ID generation logic with:
$current_batch = null;
$result = pg_query(
    $con,
    "SELECT batch_id FROM id_card_batches 
     WHERE status = 'Pending'
     ORDER BY created_date DESC
     LIMIT 1"
);

if (pg_num_rows($result) > 0) {
    $current_batch = pg_fetch_assoc($result)['batch_id'];
} else {
    // Create new shared batch only if no pending batches exist
    $batch_id = 'ID-' . date('Ymd-His');

    // Insert into batches table first
    pg_query_params(
        $con,
        "INSERT INTO id_card_batches (
            batch_id, created_by, created_date, status
         ) VALUES (
            $1, $2, $3, $4
         )",
        [
            $batch_id,
            $associatenumber,
            date('Y-m-d H:i:s'),
            'Pending'
        ]
    );

    $current_batch = $batch_id;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ID Card Order Management</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .student-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }

        .badge-new {
            background-color: #0d6efd;
        }

        .badge-reissue {
            background-color: #6c757d;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .btn-xs {
            padding: 0.15rem 0.3rem;
            font-size: 0.75rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }

        .edit-mode {
            display: none;
        }

        .view-mode {
            display: block;
        }

        .editing .edit-mode {
            display: block;
        }

        .editing .view-mode {
            display: none;
        }

        .editing {
            background-color: #f8f9fa;
        }

        .spinner-border {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }

        .loading-spinner {
            display: inline-block;
            width: 2rem;
            height: 2rem;
            vertical-align: middle;
            border: 0.25em solid #f3f3f3;
            border-top: 0.25em solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-container {
            text-align: center;
            padding: 2rem;
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
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">ID Card Order Management</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container py-4">
                                <div class="card shadow">
                                    <div class="card-header bg-primary text-white">
                                        <h2 class="h4 mb-0"><i class="bi bi-person-badge"></i> ID Card Order Management</h2>
                                    </div>

                                    <div class="card-body">
                                        <div class="card mb-4 border-primary">
                                            <div class="card-header bg-primary bg-opacity-10">
                                                <h3 class="h5 mb-0">Current Batch: <span id="current-batch-display" class="fw-bold"><?= $current_batch ?></span></h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Search Student/Associate</label>
                                                        <select name="student_ids[]" id="student-select" class="form-control select2" multiple="multiple">

                                                        </select>
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
                                                    <div class="col-12">
                                                        <button id="add-to-batch" class="btn btn-primary">
                                                            <i class="bi bi-plus-circle"></i> Add to Batch
                                                        </button>
                                                        <!-- <button id="create-new-batch" class="btn btn-outline-primary ms-2">
                                                            <i class="bi bi-file-earmark-plus"></i> Create New Batch
                                                        </button> -->
                                                        <div id="add-progress" class="spinner-border text-primary d-none" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card border-0 shadow-sm">
                                            <div class="card-header bg-secondary text-white">
                                                <h3 class="h5 mb-0"><?= $role === 'Admin' ? 'Pending Orders' : 'Current Batch Items' ?></h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table id="orders-table" class="table table-hover align-middle">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Photo</th>
                                                                <th>ID</th>
                                                                <th>Name</th>
                                                                <th>Class</th>
                                                                <th>Type</th>
                                                                <th>Payment</th>
                                                                <th>Remarks</th>
                                                                <th>Last Issued</th>
                                                                <th>Times Issued</th>
                                                                <th>Requested By</th>
                                                                <th>order Date</th>
                                                                <th>Batch ID</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td colspan="13" class="text-center py-4">
                                                                    <div class="loading-spinner"></div>
                                                                    <div class="mt-2">Loading orders...</div>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <?php
                                                $showOrderButton = false;
                                                $allowedPositions = ['Senior Centre Incharge', 'Centre Incharge'];

                                                // Show button if: has allowed position AND current batch exists
                                                if ((in_array($position, $allowedPositions)) && $current_batch) {
                                                    $showOrderButton = true;
                                                }
                                                ?>

                                                <?php if ($showOrderButton): ?>
                                                    <div class="mt-3 text-end">
                                                        <button id="place-order" class="btn btn-success" disabled>
                                                            <i class="bi bi-send-check"></i> Request Order Placement
                                                        </button>

                                                        <div id="place-order-progress" class="spinner-border text-success d-none ms-2" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>

                                                        <div id="place-order-message" class="alert d-none mt-2"></div>
                                                    </div>
                                                <?php elseif ($role != 'Admin' && !in_array($position, $allowedPositions) && $current_batch): ?>
                                                    <div class="mt-3 text-end">
                                                        <div class="alert alert-info">
                                                            <i class="bi bi-info-circle"></i> To place the final order, please contact Centre Incharge.
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($role === 'Admin'): ?>
                                                    <div class="row g-3 mt-3">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Vendor Name</label>
                                                            <input type="text" id="vendor-name" class="form-control">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Admin Remarks</label>
                                                            <textarea id="admin-remarks" class="form-control" rows="1"></textarea>
                                                        </div>
                                                        <div class="col-md-4 d-flex align-items-end">
                                                            <button id="mark-ordered" class="btn btn-warning w-100">
                                                                <i class="bi bi-check-circle"></i> Mark Ordered
                                                            </button>
                                                            <div id="mark-ordered-progress" class="spinner-border text-warning d-none ms-2" role="status">
                                                                <span class="visually-hidden">Loading...</span>
                                                            </div>
                                                        </div>
                                                        <div id="mark-ordered-message" class="alert d-none mt-2"></div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between mt-4">
                                            <a href="id_history.php" class="btn btn-info">
                                                <i class="bi bi-clock-history"></i> View Order History
                                            </a>
                                            <?php if ($role === 'Admin'): ?>
                                                <button id="export-batch" class="btn btn-secondary">
                                                    <i class="bi bi-download"></i> Export Current Batch
                                                </button>
                                            <?php endif; ?>
                                        </div>
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
    <!-- Bootstrap 5.3 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Track already added student IDs in current session
            let addedStudentIds = [];
            let currentBatchId = '<?= $current_batch ?>';

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

            // Initialize Select2 for student search
            $('#student-select').select2({
                ajax: {
                    url: 'search_beneficiaries.php',
                    dataType: 'json',
                    delay: 250,
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
                width: '100%'
            });

            // Function to create new batch
            function createNewBatch() {
                return new Promise((resolve, reject) => {
                    const now = new Date();
                    const pad = num => String(num).padStart(2, '0');

                    const newBatchId = 'ID-' +
                        now.getFullYear().toString() +
                        pad(now.getMonth() + 1) +
                        pad(now.getDate()) + '-' +
                        pad(now.getHours()) +
                        pad(now.getMinutes()) +
                        pad(now.getSeconds());

                    $('#create-new-batch').prop('disabled', true);
                    $('#create-new-batch').html('<span class="spinner-border spinner-border-sm" role="status"></span> Creating...');

                    $.post('id_process_order.php', {
                            action: 'create_batch',
                            batch_id: newBatchId,
                            created_by: '<?= $associatenumber ?>'
                        }, function(response) {
                            if (response.success) {
                                currentBatchId = newBatchId;
                                $('#current-batch-display').text(newBatchId);
                                resolve(newBatchId);
                            } else {
                                reject(response.message);
                            }
                        }, 'json')
                        .fail(() => reject('Failed to create new batch'))
                        .always(() => {
                            $('#create-new-batch').prop('disabled', false);
                            $('#create-new-batch').html('<i class="bi bi-file-earmark-plus"></i> Create New Batch');
                        });
                });
            }

            // Manual batch creation
            $('#create-new-batch').click(function() {
                if (confirm('Create a new batch? Any pending items will remain in the current batch.')) {
                    createNewBatch()
                        .then(newBatchId => {
                            alert(`New batch created: ${newBatchId}`);
                            loadBatchItems();
                        })
                        .catch(error => {
                            alert(error);
                        });
                }
            });

            // Add to batch with automatic batch creation on failure
            $('#add-to-batch').click(async function() {
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

                    // Remove duplicates from selection
                    const filteredValues = selectedValues.filter(id => !duplicates.includes(id));
                    $('#student-select').val(filteredValues).trigger('change');

                    return;
                }

                $('#add-progress').removeClass('d-none');
                $('#add-to-batch').prop('disabled', true);

                try {
                    let results = await addToBatchAttempt(currentBatchId, selectedValues);

                    // Check for batch errors
                    const batchError = results.find(r => r && r.isBatchError);
                    if (batchError) {
                        if (confirm('The current batch is no longer available. Create a new batch and try again?')) {
                            currentBatchId = await createNewBatch();
                            results = await addToBatchAttempt(currentBatchId, selectedValues);
                        } else {
                            throw new Error('Operation cancelled by user');
                        }
                    }

                    processResults(results, selectedValues);
                } catch (error) {
                    console.error('Error:', error);
                    alert(error.message || 'Unexpected error during batch addition');
                } finally {
                    $('#add-progress').addClass('d-none');
                    $('#add-to-batch').prop('disabled', false);
                }
            });

            // Helper function to attempt adding to batch
            async function addToBatchAttempt(batchId, studentIds) {
                const promises = studentIds.map(studentId =>
                    $.post('id_process_order.php', {
                        action: 'add',
                        batch_id: batchId,
                        student_id: studentId,
                        order_type: $('#order-type').val(),
                        payment_status: $('#payment-status').val(),
                        remarks: $('#order-remarks').val()
                    }).then(r => typeof r === 'string' ? JSON.parse(r) : r)
                    .catch(error => ({
                        success: false,
                        message: error.responseJSON?.message || error.statusText || 'Unknown error',
                        student_id: studentId
                    }))
                );

                return Promise.all(promises);
            }

            // Process addition results
            function processResults(results, selectedValues) {
                const added = [];
                const failed = [];

                results.forEach(r => {
                    if (r && r.success) {
                        added.push(r.student_id);
                        addedStudentIds.push(r.student_id);
                    } else if (r) {
                        failed.push({
                            id: r.student_id,
                            message: r.message || 'Unknown error'
                        });
                    }
                });

                // Show success message for added items
                if (added.length > 0) {
                    alert(`Successfully added: ${added.join(', ')}`);
                    loadBatchItems();

                    // Update selection to remove successfully added items
                    const remaining = selectedValues.filter(id => !added.includes(id));
                    $('#student-select').val(remaining).trigger('change');

                    // Clear form if all items were added
                    if (remaining.length === 0) {
                        $('#order-type').val('New').trigger('change');
                        $('#payment-status').val('');
                        $('#order-remarks').val('');
                    }
                }

                // Show detailed error message for failed items
                if (failed.length > 0) {
                    const grouped = {};
                    failed.forEach(item => {
                        if (!grouped[item.message]) {
                            grouped[item.message] = [];
                        }
                        grouped[item.message].push(item.id);
                    });

                    let summary = 'Failed to add:\n';
                    for (const [reason, ids] of Object.entries(grouped)) {
                        summary += `${ids.join(', ')} ➜ ${reason}\n`;
                    }

                    alert(summary.trim());
                }

                // Enable place order button if we have items
                if (added.length > 0) {
                    $('#place-order').prop('disabled', false);
                }
            }

            // Place order request
            $('#place-order').click(function() {
                if (confirm('Are you sure you want to submit this order for processing?')) {
                    $('#place-order-progress').removeClass('d-none');
                    $('#place-order').prop('disabled', true);

                    $.post('id_process_order.php', {
                            action: 'request_order',
                            batch_id: currentBatchId
                        }, function(response) {
                            if (response.success) {
                                alert('Order request submitted to admin');
                                location.reload();
                            } else {
                                alert(response.message);
                            }
                        }, 'json')
                        .always(() => {
                            $('#place-order-progress').addClass('d-none');
                            $('#place-order').prop('disabled', false);
                        });
                }
            });

            // Admin mark as ordered
            $('#mark-ordered').click(function() {
                const vendor = $('#vendor-name').val();
                if (!vendor) {
                    alert('Please enter vendor name');
                    return;
                }

                if (confirm('Mark this batch as ordered with vendor?')) {
                    const batchId = $(this).data('batch');
                    $('#mark-ordered-progress').removeClass('d-none');
                    $('#mark-ordered').prop('disabled', true);

                    $.post('id_process_order.php', {
                            action: 'mark_ordered',
                            batch_id: batchId,
                            vendor_name: vendor,
                            remarks: $('#admin-remarks').val()
                        }, function(response) {
                            if (response.success) {
                                alert('Batch marked as ordered successfully');
                                location.reload();
                            } else {
                                alert(response.message);
                            }
                        }, 'json')
                        .always(() => {
                            $('#mark-ordered-progress').addClass('d-none');
                            $('#mark-ordered').prop('disabled', false);
                        });
                }
            });

            // Export batch
            $('#export-batch').click(function() {
                const batchId = $(this).data('batch');
                window.location.href = 'id_export_batch.php?batch_id=' + encodeURIComponent(batchId);
            });

            // Load batch items with edit/save functionality
            function loadBatchItems() {
                const tableBody = $('#orders-table tbody');
                tableBody.html(`
                    <tr>
                        <td colspan="13" class="text-center py-4">
                            <div class="loading-spinner"></div>
                            <div class="mt-2">Loading orders...</div>
                        </td>
                    </tr>
                `);

                const batchId = '<?= $role === 'Admin' ? '' : $current_batch ?>';
                $.get('id_process_order.php', {
                    action: 'get_batch',
                    batch_id: batchId
                }, function(response) {
                    tableBody.empty();
                    addedStudentIds = [];

                    if (response.data && response.data.length) {
                        // Enable place order button if there are items
                        $('#place-order').prop('disabled', false);

                        response.data.forEach(function(item) {
                            const canEdit = item.status === 'Pending';
                            const isAdmin = '<?= $role === 'Admin' ?>' === '1';
                            const isOrderPlacer = '<?= $associatenumber ?>' === item.order_placed_by;
                            // Show buttons if: Admin OR (non-Admin who placed the order AND status is Pending)
                            const showButtons = isAdmin || (!isAdmin && isOrderPlacer && canEdit);
                            const row = `
                <tr data-id="${item.id}" data-batch="${item.batch_id}">
                    <td><img src="${item.photourl || 'default_photo.jpg'}" class="student-photo"/></td>
                    <td>${item.student_id}</td>
                    <td>${item.studentname}</td>
                    <td>${item.class}</td>
                    <td><span class="badge ${item.order_type === 'New' ? 'bg-primary' : 'bg-secondary'}">${item.order_type}</span></td>
                    <td>
                        <span class="view-mode">${item.payment_status || '-'}</span>
                        <select class="form-select form-select-sm edit-mode payment-status-select">
                            <option value="">Select Status</option>
                            <option value="Paid" ${item.payment_status === 'Paid' ? 'selected' : ''}>Paid</option>
                            <option value="Unpaid" ${item.payment_status === 'Unpaid' ? 'selected' : ''}>Unpaid</option>
                            <option value="Partial" ${item.payment_status === 'Partial' ? 'selected' : ''}>Partial</option>
                            <option value="Waived" ${item.payment_status === 'Waived' ? 'selected' : ''}>Waived</option>
                        </select>
                    </td>
                    <td>
                        <span class="view-mode">${item.remarks || '-'}</span>
                        <input type="text" class="form-control form-control-sm edit-mode remarks-input" value="${item.remarks || ''}">
                    </td>
                    <td>${item.last_issued ? new Date(item.last_issued).toLocaleDateString() : 'Never'}</td>
                    <td>${item.times_issued || '0'}</td>
                    <td>${item.order_placed_by_name}</td>
                    <td>${new Date(item.order_date).toLocaleDateString('en-GB')}</td>
                    <td><code>${item.batch_id}</code></td>
                    <td>
                        ${showButtons ? `
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-danger remove-item" data-id="${item.id}">
                                <i class="bi bi-trash"></i>
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            </button>
                            <button class="btn btn-outline-primary edit-item view-mode" data-id="${item.id}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-success save-item edit-mode" data-id="${item.id}">
                                <i class="bi bi-check"></i>
                            </button>
                        </div>
                        ` : '<span class="text-muted">Locked</span>'}
                    </td>
                </tr>`;
                            tableBody.append(row);
                            addedStudentIds.push(item.student_id);
                        });

                        <?php if ($role === 'Admin'): ?>
                            $('#mark-ordered').data('batch', response.batch_id);
                            $('#export-batch').data('batch', response.batch_id);
                        <?php endif; ?>
                    } else {
                        $('#place-order').prop('disabled', true);
                        tableBody.append(`
                <tr>
                    <td colspan="${$role === 'Admin' ? 12 : 10}" class="text-center text-muted py-4">
                        No items found in current batch
                    </td>
                </tr>
            `);
                    }

                    // Set up edit/save buttons
                    $('.edit-item').click(function() {
                        const row = $(this).closest('tr');
                        row.addClass('editing');
                        $(this).addClass('d-none');
                    });

                    $('.save-item').click(function() {
                        const row = $(this).closest('tr');
                        const id = row.data('id');
                        const paymentStatus = row.find('.payment-status-select').val();
                        const remarks = row.find('.remarks-input').val();

                        // Show saving indicator
                        const saveBtn = $(this);
                        const originalHtml = saveBtn.html();
                        saveBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                        saveBtn.prop('disabled', true);

                        $.post('id_process_order.php', {
                            action: 'update_order',
                            id: id,
                            field: 'combined',
                            payment_status: paymentStatus,
                            remarks: remarks
                        }, function(response) {
                            if (response.success) {
                                loadBatchItems();
                            } else {
                                alert(response.message);
                                saveBtn.html(originalHtml);
                                saveBtn.prop('disabled', false);
                            }
                        }, 'json');
                    });

                    // Set up remove item buttons
                    $('.remove-item').click(function() {
                        const id = $(this).data('id');
                        if (confirm('Are you sure you want to remove this item from the batch?')) {
                            // Show spinner and disable button
                            const deleteBtn = $(this);
                            const deleteIcon = deleteBtn.find('.bi-trash');
                            const deleteSpinner = deleteBtn.find('.spinner-border');

                            deleteIcon.addClass('d-none');
                            deleteSpinner.removeClass('d-none');
                            deleteBtn.prop('disabled', true);

                            $.post('id_process_order.php', {
                                    action: 'remove_item',
                                    id: id
                                }, function(response) {
                                    if (response.success) {
                                        loadBatchItems();
                                        // After removal, check if batch is empty
                                        if ($('#orders-table tbody tr').length <= 1) { // 1 for the "no items" row
                                            $('#place-order').prop('disabled', true);
                                        }
                                    } else {
                                        alert(response.message);
                                        // Reset button state on error
                                        deleteIcon.removeClass('d-none');
                                        deleteSpinner.addClass('d-none');
                                        deleteBtn.prop('disabled', false);
                                    }
                                }, 'json')
                                .fail(function() {
                                    // Reset button state on failure
                                    deleteIcon.removeClass('d-none');
                                    deleteSpinner.addClass('d-none');
                                    deleteBtn.prop('disabled', false);
                                });
                        }
                    });
                }, 'json').fail(function(xhr) {
                    tableBody.html(`
                        <tr>
                            <td colspan="13" class="text-center text-danger py-4">
                                <i class="bi bi-exclamation-triangle"></i> Failed to load data
                            </td>
                        </tr>
                    `);
                    console.error('Error loading batch:', xhr.responseText);
                });
            }

            // Initial load
            loadBatchItems();
        });
    </script>
</body>

</html>