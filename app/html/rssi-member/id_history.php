<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
?>
<?php
// Example: $role and $position should already be set from session or DB
$can_access = false;

if ($role === 'Admin' || $position === 'Centre Incharge' || $position === 'Senior Centre Incharge') {
    $can_access = true;
}

// Set default status to Ordered if not already set
$default_status = isset($_GET['status']) ? $_GET['status'] : 'Ordered';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ID Card Order History</title>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }

        .badge-ordered {
            background-color: #1cc88a;
        }

        .badge-delivered {
            background-color: #4e73df;
        }

        .badge-pending {
            background-color: #f6c23e;
        }

        .student-photo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }

        .search-options {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .search-option-checkbox {
            margin-right: 5px;
        }

        .search-option-label {
            font-weight: normal;
            margin-right: 15px;
        }

        .filter-section {
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>ID Card Order History</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="id.php">ID Card Orders</a></li>
                    <li class="breadcrumb-item active">History</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="search-options mb-4">
                                <h6>Search By:</h6>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input search-option-checkbox" type="checkbox" id="search-by-batch" checked>
                                    <label class="form-check-label search-option-label" for="search-by-batch">Batch ID</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input search-option-checkbox" type="checkbox" id="search-by-student" checked>
                                    <label class="form-check-label search-option-label" for="search-by-student">Student ID</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input search-option-checkbox" type="checkbox" id="search-by-status" checked>
                                    <label class="form-check-label search-option-label" for="search-by-status">Status</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input search-option-checkbox" type="checkbox" id="search-by-date" checked>
                                    <label class="form-check-label search-option-label" for="search-by-date">Date Range</label>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3 filter-section" id="batch-filter-section">
                                    <label class="form-label">Batch ID (comma separated)</label>
                                    <input type="text" class="form-control" id="batch-id" placeholder="BATCH001, BATCH002">
                                </div>
                                <div class="col-md-3 filter-section" id="student-filter-section">
                                    <label class="form-label">Student ID</label>
                                    <select class="form-select student-select" id="student-id" multiple="multiple">
                                        <!-- Options will be loaded dynamically -->
                                    </select>
                                </div>
                                <div class="col-md-2 filter-section" id="status-filter-section">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" id="status-filter">
                                        <option value="">All Status</option>
                                        <option value="Pending" <?php echo $default_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Ordered" <?php echo $default_status === 'Ordered' ? 'selected' : ''; ?>>Ordered</option>
                                        <option value="Delivered" <?php echo $default_status === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    </select>
                                </div>
                                <div class="col-md-2 filter-section" id="date-from-section">
                                    <label class="form-label">From Date</label>
                                    <input type="date" class="form-control" id="from-date">
                                </div>
                                <div class="col-md-2 filter-section" id="date-to-section">
                                    <label class="form-label">To Date</label>
                                    <input type="date" class="form-control" id="to-date">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <button class="btn btn-primary" id="apply-filters">
                                        <i class="bi bi-funnel"></i> Apply Filters
                                    </button>
                                    <button class="btn btn-outline-secondary" id="reset-filters">
                                        <i class="bi bi-arrow-repeat"></i> Reset Filters
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover" id="orders-table">
                                    <thead>
                                        <tr>
                                            <th>Batch ID</th>
                                            <th>Student</th>
                                            <th>Class</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Requested By</th>
                                            <th>Order Date</th>
                                            <th>Vendor</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Delivery Confirmation Modal -->
        <div class="modal fade" id="deliveryModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Mark as Delivered</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Delivery Remarks</label>
                            <textarea class="form-control" id="delivery-remarks" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Delivery Date</label>
                            <input type="date" class="form-control" id="delivery-date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="confirm-delivery">
                            <span class="spinner-border spinner-border-sm d-none" id="delivery-spinner"></span>
                            Confirm Delivery
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- View Details Modal -->
        <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Order Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="order-details-content">
                        <!-- Content will be loaded dynamically -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revert to Pending Modal -->
        <div class="modal fade" id="revertPendingModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">Revert to Pending</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" id="revert-remarks" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning" id="confirm-revert">
                            <span class="spinner-border spinner-border-sm d-none" id="revert-spinner"></span>
                            Confirm Revert
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            const deliveryModal = new bootstrap.Modal('#deliveryModal');
            let currentDeliveryId = null;
            let isBatchDelivery = false;

            // Initialize date pickers
            flatpickr("#from-date", {
                defaultDate: new Date().setMonth(new Date().getMonth() - 1)
            });
            flatpickr("#to-date", {
                defaultDate: new Date()
            });

            // Initialize Select2 for student selection
            $('.student-select').select2({
                placeholder: "Select student(s)",
                allowClear: true,
                ajax: {
                    url: 'id_process_order.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'search_students',
                            search: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2
            });

            // Set default status to Ordered on page load
            $('#status-filter').val('Ordered');

            // Toggle filter sections based on checkboxes
            $('.search-option-checkbox').change(function() {
                toggleFilterSections();
            });

            function toggleFilterSections() {
                $('#batch-filter-section').toggle($('#search-by-batch').is(':checked'));
                $('#student-filter-section').toggle($('#search-by-student').is(':checked'));
                $('#status-filter-section').toggle($('#search-by-status').is(':checked'));

                const dateEnabled = $('#search-by-date').is(':checked');
                $('#date-from-section').toggle(dateEnabled);
                $('#date-to-section').toggle(dateEnabled);

                // Disable date inputs when date search is not selected
                $('#from-date, #to-date').prop('disabled', !dateEnabled);
            }

            // Initialize filter sections
            toggleFilterSections();

            // Load initial data
            loadOrders();

            // Filter button click
            $('#apply-filters').click(function() {
                loadOrders();
            });

            // Reset filters button
            $('#reset-filters').click(function() {
                $('#batch-id').val('');
                $('.student-select').val(null).trigger('change');
                $('#status-filter').val('Ordered');
                $('#from-date, #to-date').val('');

                // Reset checkboxes to default
                $('#search-by-batch, #search-by-student, #search-by-status, #search-by-date').prop('checked', true);
                toggleFilterSections();

                loadOrders();
            });

            // Modify your row generation to include delivery action
            function loadOrders() {
                const params = {
                    from_date: $('#search-by-date').is(':checked') ? $('#from-date').val() : '',
                    to_date: $('#search-by-date').is(':checked') ? $('#to-date').val() : '',
                    status: $('#search-by-status').is(':checked') ? $('#status-filter').val() : '',
                    batch_ids: $('#search-by-batch').is(':checked') ? $('#batch-id').val() : '',
                    student_ids: $('#search-by-student').is(':checked') ? $('#student-id').val() : ''
                };

                $('#orders-table tbody').html(`
            <tr>
                <td colspan="10" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td>
            </tr>
        `);

                $.ajax({
                    url: 'id_process_order.php',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        action: 'get_order_history',
                        ...params
                    },
                    success: function(response) {
                        $('#orders-table tbody').empty();

                        if (response.success && response.data && response.data.length > 0) {
                            // Group orders by batch_id
                            const batches = {};
                            response.data.forEach(order => {
                                if (!batches[order.batch_id]) {
                                    batches[order.batch_id] = [];
                                }
                                batches[order.batch_id].push(order);
                            });

                            // Render each batch
                            Object.entries(batches).forEach(([batchId, orders]) => {
                                const isBatchOrdered = orders.every(o => o.status === 'Ordered');
                                const isBatchDeliverable = isBatchOrdered || orders.some(o => o.status === 'Ordered');
                                const ordered_date = orders[0]?.ordered_date || '';
                                const vendor_name = orders[0]?.vendor_name || '';
                                const canAccess = <?php echo json_encode($can_access); ?>;

                                // Batch header row
                                if (orders.length > 1) {
                                    const batchRow = `
                                <tr class="batch-header bg-light">
                                    <td colspan="7">
                                        <strong>Batch:</strong> ${batchId}
                                        <span class="badge ${isBatchOrdered ? 'bg-success' : 'bg-secondary'} ms-2">
                                            ${orders.length} cards
                                        </span>
                                    </td>
                                    <td>
                                        ${ordered_date ? new Date(orders[0].ordered_date).toLocaleString('en-GB') : '-'}
                                    </td>
                                    <td>
                                        ${vendor_name}
                                    </td>
                                    <td>
                                        ${canAccess && isBatchDeliverable ? `
                                        <button class="btn btn-sm btn-success mark-delivered-btn" 
                                                data-batch-id="${batchId}" 
                                                title="Mark entire batch as delivered">
                                            <i class="bi bi-check-circle"></i> Deliver
                                        </button>
                                        ` : ''}
                                    </td>
                                </tr>
                            `;
                                    $('#orders-table tbody').append(batchRow);
                                }

                                // Individual order rows
                                orders.forEach(order => {
                                    const statusClass = order.status === 'Ordered' ? 'badge-ordered' :
                                        order.status === 'Delivered' ? 'badge-delivered' : 'badge-pending';

                                    const row = `
                                <tr>
                                    <td><code>${order.batch_id}</code></td>
                                    <td>
                                        <img src="${order.photourl || 'default_photo.jpg'}" class="student-photo me-2">
                                        ${order.studentname || 'N/A'} (${order.student_id || 'N/A'})
                                    </td>
                                    <td>${order.class || '-'}</td>
                                    <td>
                                        <span class="badge ${order.order_type === 'New' ? 'bg-primary' : 'bg-secondary'}">
                                            ${order.order_type || '-'}
                                        </span>
                                    </td>
                                    <td><span class="badge ${statusClass}">${order.status || '-'}</span></td>
                                    <td>${order.payment_status || '-'}</td>
                                    <td>${order.order_placed_by_name || '-'}</td>
                                    <td></td>
                                    <td></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-link text-secondary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <!-- View Details Option -->
                                                <li>
                                                    <button class="dropdown-item view-details" data-id="${order.id}">
                                                        <i class="bi bi-eye me-2"></i> View Details
                                                    </button>
                                                </li>
                                                
                                                ${canAccess && order.status === 'Ordered' ? `
                                                <!-- Mark Delivered Option -->
                                                <li>
                                                    <button class="dropdown-item mark-single-delivered" data-id="${order.id}">
                                                        <i class="bi bi-check-circle me-2"></i> Mark Delivered
                                                    </button>
                                                </li>
                                                ` : ''}
                                                
                                                ${canAccess && order.status === 'Delivered' ? `
                                                <!-- Revert to Pending Option -->
                                                <li>
                                                    <button class="dropdown-item mark-as-pending" data-id="${order.id}">
                                                        <i class="bi bi-arrow-counterclockwise me-2"></i> Revert to Pending
                                                    </button>
                                                </li>
                                                ` : ''}
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            `;
                                    $('#orders-table tbody').append(row);
                                });
                            });
                        } else {
                            const message = response.message || 'No orders found';
                            $('#orders-table tbody').html(`
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox"></i> ${message}
                            </td>
                        </tr>
                    `);
                        }
                    },
                    error: function(xhr, status, error) {
                        // ... [keep your existing error handling] ...
                    }
                });
            }

            // Handle batch delivery button click
            $(document).on('click', '.mark-delivered-btn', function() {
                currentDeliveryId = $(this).data('batch-id');
                isBatchDelivery = true;
                $('#delivery-remarks').val('');
                deliveryModal.show();
            });

            // Handle single order delivery button click
            $(document).on('click', '.mark-single-delivered', function() {
                currentDeliveryId = $(this).data('id');
                isBatchDelivery = false;
                $('#delivery-remarks').val('');
                deliveryModal.show();
            });

            // Confirm delivery
            // In your confirm delivery handler
            $('#confirm-delivery').click(function() {
                const btn = $(this);
                const spinner = $('#delivery-spinner');
                const remarks = $('#delivery-remarks').val();
                const deliveryDateTime = $('#delivery-date').val();

                btn.prop('disabled', true);
                spinner.removeClass('d-none');

                const action = isBatchDelivery ? 'mark_batch_delivered' : 'mark_order_delivered';
                const data = {
                    action: action,
                    remarks: remarks,
                    delivery_date: deliveryDateTime
                };

                if (isBatchDelivery) {
                    data.batch_id = currentDeliveryId;
                } else {
                    data.order_id = currentDeliveryId;
                }

                $.post('id_process_order.php', data, function(response) {
                    if (response.success) {
                        alert(response.message);
                        deliveryModal.hide();
                        loadOrders(); // Refresh the table

                        // If we delivered a single order, check if we need to update the batch header
                        if (!isBatchDelivery) {
                            $('.batch-header').each(function() {
                                const batchId = $(this).find('button').data('batch-id');
                                updateBatchHeaderStatus(batchId);
                            });
                        }
                    } else {
                        alert('Error: ' + response.message);
                    }
                }, 'json').always(() => {
                    btn.prop('disabled', false);
                    spinner.addClass('d-none');
                });
            });

            // Confirm Revert Handler
            $('#confirm-revert').click(function() {
                const btn = $(this);
                const spinner = $('#revert-spinner');
                const remarks = $('#revert-remarks').val();

                btn.prop('disabled', true);
                spinner.removeClass('d-none');

                $.post('id_process_order.php', {
                    action: 'revert_to_pending',
                    order_id: currentOrderId,
                    remarks: remarks
                }, function(response) {
                    if (response.success) {
                        alert(response.message);
                        revertPendingModal.hide();
                        loadOrders(); // Refresh the table
                    } else {
                        alert('Error: ' + response.message);
                    }
                }, 'json').always(() => {
                    btn.prop('disabled', false);
                    spinner.addClass('d-none');
                });
            });

            // Add this helper function
            function updateBatchHeaderStatus(batchId) {
                $.get('id_process_order.php', {
                    action: 'get_batch_status',
                    batch_id: batchId
                }, function(response) {
                    if (response.success) {
                        const $header = $(`.batch-header button[data-batch-id="${batchId}"]`).closest('tr');

                        // Update the status badge
                        const $badge = $header.find('.badge');
                        $badge.removeClass('bg-success bg-warning bg-secondary')
                            .addClass(response.allDelivered ? 'bg-success' :
                                response.hasOrdered ? 'bg-warning' : 'bg-secondary');

                        // Show/hide deliver button
                        const $deliverBtn = $header.find('.mark-delivered-btn');
                        if (response.hasOrdered) {
                            $deliverBtn.show();
                        } else {
                            $deliverBtn.hide();
                        }
                    }
                }, 'json');
            }
        });
    </script>
    <script>
        // Initialize modals
        const viewDetailsModal = new bootstrap.Modal('#viewDetailsModal');
        const revertPendingModal = new bootstrap.Modal('#revertPendingModal');
        let currentOrderId = null;

        // View Details Handler
        $(document).on('click', '.view-details', function() {
            const orderId = $(this).data('id');

            $.get('id_process_order.php', {
                action: 'get_order_details_history',
                id: orderId
            }, function(response) {
                if (response.success) {
                    const order = response.data;
                    let statusBadge = '';
                    if (order.status === 'Ordered') {
                        statusBadge = 'bg-warning';
                    } else if (order.status === 'Delivered') {
                        statusBadge = 'bg-success';
                    } else if (order.status === 'Pending') {
                        statusBadge = 'bg-secondary';
                    }

                    const content = `
                <div class="mb-3">
                    <h6>Order Information</h6>
                    <p><strong>${order.studentname || 'N/A'} (${order.student_id || 'N/A'})</strong></p>
                    <p><strong>Batch ID:</strong> ${order.batch_id || '-'}</p>
                    <p><strong>Status:</strong> <span class="badge ${statusBadge}">${order.status || '-'}</span></p>
                    <p><strong>Order Date:</strong> ${order.order_date ? new Date(order.order_date).toLocaleDateString('en-GB') : '-'}</p>
                    <p><strong>Requested By:</strong> ${order.order_placed_by_name || order.order_placed_by || '-'}</p>
                    <p><strong>Type:</strong> ${order.order_type || '-'}</p>
                    <p><strong>Payment Status:</strong> ${order.payment_status || '-'}</p>
                    <p><strong>Remarks:</strong> ${order.remarks || '-'}</p>
                    <p><strong>Order Placed with Vendor:</strong> ${order.ordered_date ? new Date(order.ordered_date).toLocaleString('en-GB') : '-'}</p>
                </div>
                
                ${order.status === 'Delivered' ? `
                <div class="mb-3">
                    <h6>Delivery Information</h6>
                    <p><strong>Delivered Date:</strong> ${order.delivered_date ? new Date(order.delivered_date).toLocaleDateString('en-GB') : '-'}</p>
                    <p><strong>Received By:</strong> ${order.delivered_by_name || order.delivered_by || '-'}</p>
                    <p><strong>Delivery Remarks:</strong> ${order.delivered_remarks || '-'}</p>
                </div>
                ` : ''}
                
                ${order.status === 'Ordered' && (order.pending_remarks || order.updated_by) ? `
                <div class="mb-3">
                    <h6>Pending Status Information</h6>
                    ${order.pending_remarks ? `<p><strong>Pending Remarks:</strong> ${order.pending_remarks}</p>` : ''}
                    ${order.updated_by_name ? `<p><strong>Updated By:</strong> ${order.updated_by_name}</p>` : ''}
                    ${order.updated_at ? `<p><strong>Updated At:</strong> ${new Date(order.updated_at).toLocaleString('en-GB')}</p>` : ''}
                </div>
                ` : ''}
                
                <div class="mb-3">
                    <h6>System Information</h6>
                    ${order.created_at ? `<p><strong>Created At:</strong> ${new Date(order.created_at).toLocaleString('en-GB')}</p>` : ''}
                    ${order.updated_at ? `<p><strong>Last Updated:</strong> ${new Date(order.updated_at).toLocaleString('en-GB')}</p>` : ''}
                </div>
            `;

                    $('#order-details-content').html(content);
                    viewDetailsModal.show();
                } else {
                    alert('Error: ' + (response.message || 'Failed to load order details'));
                }
            }, 'json');
        });
        // Revert to Pending Handler
        $(document).on('click', '.mark-as-pending', function() {
            currentOrderId = $(this).data('id');
            $('#revert-remarks').val('');
            revertPendingModal.show();
        });
    </script>
    <script>
        $(document).ready(function() {
            // Include Student IDs
            $('#student-id').select2({
                ajax: {
                    url: 'fetch_students.php',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: data.results
                    }),
                    cache: true
                },
                placeholder: 'Search by name or ID',
                width: '100%',
                minimumInputLength: 1,
                multiple: true
            });
        });
    </script>
</body>

</html>