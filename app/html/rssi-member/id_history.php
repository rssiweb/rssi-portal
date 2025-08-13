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
        .table-responsive { max-height: 600px; overflow-y: auto; }
        .badge-ordered { background-color: #1cc88a; }
        .badge-delivered { background-color: #4e73df; }
        .badge-pending { background-color: #f6c23e; }
        .student-photo { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; }
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
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {
            // Initialize date pickers
            flatpickr("#from-date", { defaultDate: new Date().setMonth(new Date().getMonth() - 1) });
            flatpickr("#to-date", { defaultDate: new Date() });

            // Load initial data
            loadOrders();

            // Filter button click
            $('#apply-filters').click(function() {
                loadOrders();
            });

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

                $.get('id_process_order.php', {
                    action: 'get_order_history',
                    ...params
                }, function(response) {
                    $('#orders-table tbody').empty();

                    if (response.success && response.data.length > 0) {
                        response.data.forEach(order => {
                            const statusClass = order.status === 'Ordered' ? 'badge-ordered' : 
                                             order.status === 'Delivered' ? 'badge-delivered' : 'badge-pending';

                            const row = `
                                <tr>
                                    <td><code>${order.batch_id}</code></td>
                                    <td>
                                        <img src="${order.photourl || 'default_photo.jpg'}" class="student-photo me-2">
                                        ${order.studentname} (${order.student_id})
                                    </td>
                                    <td>${order.class}</td>
                                    <td>
                                        <span class="badge ${order.order_type === 'New' ? 'bg-primary' : 'bg-secondary'}">
                                            ${order.order_type}
                                        </span>
                                    </td>
                                    <td><span class="badge ${statusClass}">${order.status}</span></td>
                                    <td>${order.payment_status || '-'}</td>
                                    <td>${order.order_placed_by_name}</td>
                                    <td>${new Date(order.order_date).toLocaleDateString()}</td>
                                    <td>${order.vendor_name || '-'}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary view-order" data-id="${order.id}">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                            $('#orders-table tbody').append(row);
                        });
                    } else {
                        $('#orders-table tbody').html(`
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox"></i> No orders found
                                </td>
                            </tr>
                        `);
                    }
                }, 'json');
            }
        });
    </script>
</body>
</html>