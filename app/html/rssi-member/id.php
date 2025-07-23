<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Get current batch if exists
$current_batch = null;
$result = pg_query_params(
    $con,
    "SELECT batch_id FROM id_card_orders 
     WHERE order_placed_by = $1 AND status = 'Pending'
     LIMIT 1",
    array($associatenumber)
);
if (pg_num_rows($result) > 0) {
    $current_batch = pg_fetch_assoc($result)['batch_id'];
}

// Create new batch if none exists
if (!$current_batch) {
    $batch_id = 'ID-' . date('Ymd-His');
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
                                                <h3 class="h5 mb-0">Current Batch: <span class="fw-bold"><?= $current_batch ?></span></h3>
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
                                                                <?php if ($role === 'Admin'): ?>
                                                                    <th>Requested By</th>
                                                                    <th>Batch ID</th>
                                                                <?php endif; ?>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- Filled via AJAX -->
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <?php if ($role !== 'Admin' && $current_batch): ?>
                                                    <div class="mt-3 text-end">
                                                        <button id="place-order" class="btn btn-success" disabled>
                                                            <i class="bi bi-send-check"></i> Request Order Placement
                                                        </button>

                                                        <div id="place-order-progress" class="spinner-border text-success d-none ms-2" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>

                                                        <div id="place-order-message" class="alert d-none mt-2"></div>
                                                    </div>
                                                <?php elseif ($role === 'Admin'): ?>
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
                            // isActive: true
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

            // Track already added student IDs in current session
            let addedStudentIds = [];

            // Load current batch items
            loadBatchItems();

            // Add to batch - handle multiple students
            $('#add-to-batch').click(function() {
                const selectedValues = $('#student-select').val();
                if (!selectedValues || selectedValues.length === 0) {
                    alert('Please select at least one student');
                    return;
                }

                // Validate payment status for reissue
                if ($('#order-type').val() === 'Reissue' && !$('#payment-status').val()) {
                    alert('Payment status is required for Reissue orders');
                    return;
                }

                // Check for duplicates in current session
                const duplicates = selectedValues.filter(id => addedStudentIds.includes(id));
                if (duplicates.length > 0) {
                    alert(`These profiles are already in the batch: ${duplicates.join(', ')}`);
                    return;
                }

                // Show progress
                $('#add-progress').removeClass('d-none');
                $('#add-to-batch').prop('disabled', true);

                const promises = selectedValues.map(studentId => {
                    return $.post('id_process_order.php', {
                        action: 'add',
                        batch_id: '<?= $current_batch ?>',
                        student_id: studentId,
                        order_type: $('#order-type').val(),
                        payment_status: $('#payment-status').val(),
                        remarks: $('#order-remarks').val()
                    });
                });

                Promise.all(promises)
                    .then(responses => {
                        const allSuccess = responses.every(r => r.success);
                        const failedItems = responses.filter(r => !r.success);

                        if (allSuccess) {
                            // Add to tracked student IDs
                            addedStudentIds.push(...selectedValues);

                            alert('Selected profiles added to batch successfully.');
                            loadBatchItems();
                            $('#student-select').val(null).trigger('change');
                            $('#payment-status').val('');
                            $('#order-remarks').val('');
                        } else {
                            const errorMessages = failedItems.map(r => r.message).join('\n');
                            alert('Some orders failed:\n' + errorMessages);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error processing orders');
                    })
                    .finally(() => {
                        $('#add-progress').addClass('d-none');
                        $('#add-to-batch').prop('disabled', false);
                    });
            });

            // Place order request
            $('#place-order').click(function() {
                if (confirm('Are you sure you want to submit this order for processing?')) {
                    $('#place-order-progress').removeClass('d-none');
                    $('#place-order').prop('disabled', true);

                    $.post('id_process_order.php', {
                            action: 'request_order',
                            batch_id: '<?= $current_batch ?>'
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
                const batchId = '<?= $role === 'Admin' ? '' : $current_batch ?>';
                $.get('id_process_order.php', {
                    action: 'get_batch',
                    batch_id: batchId
                }, function(response) {
                    $('#orders-table tbody').empty();

                    // Reset tracked student IDs
                    addedStudentIds = [];

                    if (response.data && response.data.length) {
                        // Enable place order button if there are items
                        $('#place-order').prop('disabled', false);

                        response.data.forEach(function(item) {
                            const canEdit = item.status === 'Pending';
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
                    <?php if ($role === 'Admin'): ?>
                    <td>${item.order_placed_by_name}</td>
                    <td><code>${item.batch_id}</code></td>
                    <?php endif; ?>
                    <td>
                        ${canEdit ? `
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
                            $('#orders-table tbody').append(row);

                            // Add to tracked student IDs
                            addedStudentIds.push(item.student_id);
                        });

                        <?php if ($role === 'Admin'): ?>
                            $('#mark-ordered').data('batch', response.batch_id);
                            $('#export-batch').data('batch', response.batch_id);
                        <?php endif; ?>
                    } else {
                        // Disable place order button if no items
                        $('#place-order').prop('disabled', true);

                        $('#orders-table tbody').append(`
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
                    console.error('Error loading batch:', xhr.responseText);
                });
            }
        });
    </script>
</body>

</html>