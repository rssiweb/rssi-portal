<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    header("Location: index.php");
    exit;
}

// Get all batches with counts
$query = "SELECT 
             batch_id, 
             MIN(order_date) as start_date,
             MAX(order_date) as end_date,
             COUNT(*) as item_count,
             status,
             MIN(academic_year) as academic_year,
             MAX(vendor_name) as vendor_name,
             MAX(admin_remarks) as admin_remarks
          FROM id_card_orders
          GROUP BY batch_id, status
          ORDER BY start_date DESC";
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
                    <li class="breadcrumb-item"><a href="id.php">ICOM</a></li>
                    <li class="breadcrumb-item active">ID Card Order History</li>
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
                                        <h2 class="h4 mb-0"><i class="bi bi-clock-history"></i> ID Card Order History</h2>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Batch ID</th>
                                                        <th>Academic Year</th>
                                                        <th>Date Range</th>
                                                        <th>Items</th>
                                                        <th>Status</th>
                                                        <th>Vendor</th>
                                                        <th>Remarks</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($batches as $batch): ?>
                                                        <tr>
                                                            <td><code><?= htmlspecialchars($batch['batch_id']) ?></code></td>
                                                            <td><?= htmlspecialchars($batch['academic_year']) ?></td>
                                                            <td>
                                                                <?= date('d M Y', strtotime($batch['start_date'])) ?> -
                                                                <?= date('d M Y', strtotime($batch['end_date'])) ?>
                                                            </td>
                                                            <td><?= $batch['item_count'] ?></td>
                                                            <td>
                                                                <span class="badge <?=
                                                                                    $batch['status'] === 'Delivered' ? 'bg-success' : ($batch['status'] === 'Ordered' ? 'bg-warning text-dark' : 'bg-primary')
                                                                                    ?>">
                                                                    <?= $batch['status'] ?>
                                                                </span>
                                                            </td>
                                                            <td><?= htmlspecialchars($batch['vendor_name'] ?? '-') ?></td>
                                                            <td><?= htmlspecialchars($batch['admin_remarks'] ?? '-') ?></td>
                                                            <td>
                                                                <a href="id_export_batch.php?batch_id=<?= urlencode($batch['batch_id']) ?>" class="btn btn-sm btn-secondary">
                                                                    <i class="bi bi-download"></i> Export
                                                                </a>
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
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.mark-delivered').click(function() {
                const batchId = $(this).data('batch');
                const btn = $(this);
                const spinner = $('#deliver-spinner-' + batchId);

                if (confirm('Mark this batch as delivered?')) {
                    btn.prop('disabled', true);
                    spinner.removeClass('d-none');

                    $.post('id_process_order.php', {
                        action: 'mark_delivered',
                        batch_id: batchId
                    }, function(response) {
                        if (response.success) {
                            alert('Batch marked as delivered');
                            location.reload();
                        } else {
                            alert(response.message);
                            btn.prop('disabled', false);
                            spinner.addClass('d-none');
                        }
                    }, 'json');
                }
            });
        });
    </script>
    <script>
        // Add this to your $(document).ready() function
        $(document).on('click', '.mark-delivered', function() {
            const batchId = $(this).data('batch');
            const button = $(this);
            const spinner = $('#deliver-spinner-' + batchId);

            if (confirm('Mark this batch as delivered?')) {
                // Show spinner and disable button
                button.prop('disabled', true);
                spinner.removeClass('d-none');

                $.post('id_process_order.php', {
                        action: 'mark_delivered',
                        batch_id: batchId
                    }, function(response) {
                        if (response.success) {
                            // Reload the page or update UI as needed
                            location.reload();
                        } else {
                            alert(response.message);
                            button.prop('disabled', false);
                            spinner.addClass('d-none');
                        }
                    }, 'json')
                    .fail(function() {
                        alert('Error marking batch as delivered');
                        button.prop('disabled', false);
                        spinner.addClass('d-none');
                    });
            }
        });
    </script>
</body>

</html>