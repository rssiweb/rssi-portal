<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
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
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">From Date</label>
                                    <input type="date" class="form-control" id="from-date">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">To Date</label>
                                    <input type="date" class="form-control" id="to-date">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" id="status-filter">
                                        <option value="">All Status</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Ordered">Ordered</option>
                                        <option value="Delivered">Delivered</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button class="btn btn-primary" id="apply-filters">
                                        <i class="bi bi-funnel"></i> Apply Filters
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
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

            // Load initial data
            loadOrders();

            // Filter button click
            $('#apply-filters').click(function() {
                loadOrders();
            });

            // Modify your row generation to include delivery action
            function loadOrders() {
                const params = {
                    from_date: $('#from-date').val(),
                    to_date: $('#to-date').val(),
                    status: $('#status-filter').val()
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

                                // Batch header row
                                if (orders.length > 1) {
                                    const batchRow = `
                                <tr class="batch-header bg-light">
                                    <td colspan="9">
                                        <strong>Batch:</strong> ${batchId}
                                        <span class="badge ${isBatchOrdered ? 'bg-success' : 'bg-secondary'} ms-2">
                                            ${orders.length} cards
                                        </span>
                                    </td>
                                    <td>
                                        ${isBatchDeliverable ? `
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
                                    <td>${order.order_date ? new Date(order.order_date).toLocaleDateString() : '-'}</td>
                                    <td>${order.vendor_name || '-'}</td>
                                    <td>
                                    ${order.status === 'Ordered' ? `
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-link text-secondary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <button class="dropdown-item mark-single-delivered" data-id="${order.id}">
                                                        <i class="bi bi-check-circle me-2"></i> Mark Delivered
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                        ` : ''}
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

                btn.prop('disabled', true);
                spinner.removeClass('d-none');

                const action = isBatchDelivery ? 'mark_batch_delivered' : 'mark_order_delivered';
                const data = {
                    action: action,
                    remarks: remarks
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

</body>

</html>