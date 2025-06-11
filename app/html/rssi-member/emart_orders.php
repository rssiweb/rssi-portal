<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Initialize variables for pagination and filtering
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Initialize filter variables
$searchTerm = isset($_GET['search']) ? pg_escape_string($con, $_GET['search']) : '';
$paymentMode = isset($_GET['payment_mode']) ? pg_escape_string($con, $_GET['payment_mode']) : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Base query
$baseQuery = "
    SELECT 
        o.*,
        COALESCE(s.studentname, m.fullname, h.name) AS customer_name,
        COALESCE(s.contact, m.phone, h.contact_number) AS customer_contact,
        COALESCE(s.emailaddress, m.email, h.email) AS customer_email
    FROM emart_orders o
    LEFT JOIN rssimyprofile_student s ON o.beneficiary = s.student_id
    LEFT JOIN rssimyaccount_members m ON o.beneficiary = m.associatenumber
    LEFT JOIN public_health_records h ON o.beneficiary = h.id::text
";

// Where conditions for filters
$whereConditions = [];
$queryParams = [];

if (!empty($searchTerm)) {
    $whereConditions[] = "(
        o.order_number ILIKE '%$searchTerm%' OR 
        COALESCE(s.studentname, m.fullname, h.name) ILIKE '%$searchTerm%' OR
        COALESCE(s.emailaddress, m.email, h.email) ILIKE '%$searchTerm%' OR
        COALESCE(s.contact, m.phone, h.contact_number) ILIKE '%$searchTerm%'
    )";
}

if (!empty($paymentMode)) {
    $whereConditions[] = "o.payment_mode = '$paymentMode'";
}

if (!empty($dateFrom)) {
    $whereConditions[] = "o.order_date >= '$dateFrom'";
}

if (!empty($dateTo)) {
    $whereConditions[] = "o.order_date <= '$dateTo 23:59:59'";
}

// Combine where conditions
if (!empty($whereConditions)) {
    $baseQuery .= " WHERE " . implode(' AND ', $whereConditions);
}

// Count total records for pagination
$countQuery = "SELECT COUNT(*) as total FROM ($baseQuery) as subquery";
$countResult = pg_query($con, $countQuery);
$totalRecords = pg_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRecords / $itemsPerPage);

// Add sorting and pagination to main query
$ordersQuery = $baseQuery . " ORDER BY o.order_date DESC LIMIT $itemsPerPage OFFSET $offset";
$ordersResult = pg_query($con, $ordersQuery);
$orders = pg_fetch_all($ordersResult);

// Get unique payment modes for filter dropdown
$paymentModesQuery = "SELECT DISTINCT payment_mode FROM emart_orders ORDER BY payment_mode";
$paymentModesResult = pg_query($con, $paymentModesQuery);
$paymentModes = pg_fetch_all($paymentModesResult);

?>

<!DOCTYPE html>
<html>

<head>
    <title>eMart Order Summary</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- Include Date Range Picker CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>eMart Orders</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Community Supply</a></li>
                    <li class="breadcrumb-item active">eMart Orders</li>
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
                            <main class="container py-4">
                                <!-- Search and Filter Form -->
                                <form method="get" class="mb-4">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <input type="text" name="search" class="form-control" placeholder="Search Order #, Name..." value="<?= htmlspecialchars($searchTerm) ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <select name="payment_mode" class="form-select">
                                                <option value="">All Payment Methods</option>
                                                <?php foreach ($paymentModes as $mode): ?>
                                                    <option value="<?= htmlspecialchars($mode['payment_mode']) ?>" <?= $paymentMode == $mode['payment_mode'] ? 'selected' : '' ?>>
                                                        <?= ucfirst(htmlspecialchars($mode['payment_mode'])) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="date_range" class="form-control date-range-picker" placeholder="Date range"
                                                value="<?= !empty($dateFrom) && !empty($dateTo) ? htmlspecialchars("$dateFrom - $dateTo") : '' ?>">
                                            <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                                            <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-search"></i> Filter
                                            </button>
                                            <!-- </div>
                                    <div class="col-md-2"> -->
                                            <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="btn btn-outline-secondary">
                                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                                            </a>
                                        </div>
                                    </div>
                                </form>


                                <?php if (empty($orders)): ?>
                                    <div class="alert alert-info">
                                        No orders found matching your criteria.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Id</th>
                                                    <th>Order #</th>
                                                    <th>Name</th>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Payment Method</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($order['order_id']) ?></td>
                                                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                                        <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                                                        <td>₹<?= number_format($order['total_amount'], 2) ?></td>
                                                        <td><?= ucfirst($order['payment_mode']) ?></td>
                                                        <td>
                                                            <a href="order_confirmation.php?id=<?= $order['order_id'] ?>"
                                                                class="btn btn-sm btn-outline-primary" target="_blank">
                                                                <i class="bi bi-receipt"></i> View Receipt
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($currentPage > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" aria-label="First">
                                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>" aria-label="Previous">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>

                                            <?php
                                            // Show page numbers
                                            $startPage = max(1, $currentPage - 2);
                                            $endPage = min($totalPages, $currentPage + 2);

                                            if ($startPage > 1) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }

                                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                                <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor;

                                            if ($endPage < $totalPages) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                            ?>

                                            <?php if ($currentPage < $totalPages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>" aria-label="Next">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" aria-label="Last">
                                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                        <div class="text-center text-muted">
                                            Showing <?= ($offset + 1) ?> to <?= min($offset + $itemsPerPage, $totalRecords) ?> of <?= $totalRecords ?> entries
                                        </div>
                                    </nav>
                                <?php endif; ?>
                            </main>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- Include jQuery and Date Range Picker -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        // Initialize date range picker
        $(document).ready(function() {
            $('.date-range-picker').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD'
                }
            });

            $('.date-range-picker').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $('input[name="date_from"]').val(picker.startDate.format('YYYY-MM-DD'));
                $('input[name="date_to"]').val(picker.endDate.format('YYYY-MM-DD'));
            });

            $('.date-range-picker').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                $('input[name="date_from"]').val('');
                $('input[name="date_to"]').val('');
            });

            // Set initial values if they exist
            <?php if (!empty($dateFrom) && !empty($dateTo)): ?>
                $('.date-range-picker').val('<?= "$dateFrom - $dateTo" ?>');
            <?php endif; ?>
        });
    </script>
</body>

</html>