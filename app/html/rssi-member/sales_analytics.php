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

// =========================================================
// EXPORT — DETAILED LINE ITEMS (CSV)
// Must run BEFORE any HTML output
// =========================================================
if (isset($_GET['export']) && $_GET['export'] === 'line_items') {

    $dateFrom      = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo        = $_GET['date_to']   ?? date('Y-m-t');
    $productFilter = $_GET['product_id'] ?? [];
    $paymentMode   = $_GET['payment_mode'] ?? '';

    $dateFromEsc    = pg_escape_string($con, $dateFrom);
    $dateToEsc      = pg_escape_string($con, $dateTo);
    $paymentModeEsc = pg_escape_string($con, $paymentMode);

    if (!is_array($productFilter)) $productFilter = [$productFilter];
    $productFilter = array_filter($productFilter);

    $productParams = [];
    $productCondition = "";
    if (!empty($productFilter)) {
        $ph = [];
        foreach ($productFilter as $i => $pid) {
            $ph[] = '$' . ($i + 1);
            $productParams[] = (int)$pid;
        }
        $productCondition = " AND oi.product_id IN (" . implode(',', $ph) . ")";
    }

    $paymentCondition = "";
    if (!empty($paymentMode)) {
        $paymentCondition = " AND o.payment_mode = '" . $paymentModeEsc . "'";
    }

    $exportQuery = "
        SELECT
            o.order_id,
            o.order_number,
            o.order_date,
            o.payment_mode,
            COALESCE(s.studentname, m.fullname, h.name) AS customer_name,
            oi.product_id,
            COALESCE(si.item_name, 'Product #' || oi.product_id) AS product_name,
            oi.quantity,
            oi.unit_price,
            oi.base_price,
            oi.custom_price,
            oi.discount_percent,
            oi.is_fixed_price,
            (oi.quantity * oi.unit_price * (1 - COALESCE(oi.discount_percent,0)/100.0)) AS line_total
        FROM emart_orders o
        JOIN emart_order_items oi ON oi.order_id = o.order_id
        LEFT JOIN stock_item si ON si.item_id = oi.product_id
        LEFT JOIN rssimyprofile_student s ON o.beneficiary = s.student_id
        LEFT JOIN rssimyaccount_members m ON o.beneficiary = m.associatenumber
        LEFT JOIN public_health_records h ON o.beneficiary = h.id::text
        WHERE o.order_date::date BETWEEN '$dateFromEsc' AND '$dateToEsc'
          AND (o.status IS NULL OR o.status NOT IN ('cancelled','refunded','failed'))
          $paymentCondition
          $productCondition
        ORDER BY o.order_date DESC, o.order_id DESC
    ";

    $exportResult = pg_query_params($con, $exportQuery, $productParams);
    $exportData = $exportResult ? pg_fetch_all($exportResult) : [];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="detailed_line_items_' . date('Y-m-d_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // UTF-8 BOM so Excel opens ₹ correctly
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, [
        'Order ID',
        'Order Number',
        'Order Date',
        'Customer',
        'Product ID',
        'Product Name',
        'Quantity',
        'Unit Price',
        'Base Price',
        'Custom Price',
        'Discount %',
        'Fixed Price?',
        'Line Total',
        'Payment Mode'
    ]);

    foreach ($exportData as $row) {
        fputcsv($output, [
            $row['order_id'],
            $row['order_number'],
            date('d/m/Y', strtotime($row['order_date'])),
            $row['customer_name'] ?? '',
            $row['product_id'],
            $row['product_name'],
            (int)$row['quantity'],
            number_format((float)$row['unit_price'], 2, '.', ''),
            number_format((float)($row['base_price'] ?? 0), 2, '.', ''),
            number_format((float)($row['custom_price'] ?? 0), 2, '.', ''),
            number_format((float)($row['discount_percent'] ?? 0), 2, '.', ''),
            (!empty($row['is_fixed_price']) && $row['is_fixed_price'] !== 'f') ? 'Yes' : 'No',
            number_format((float)$row['line_total'], 2, '.', ''),
            ucfirst($row['payment_mode'] ?? '')
        ]);
    }

    fclose($output);
    exit;
}

// =========================================================
// FILTERS
// =========================================================
$dateFrom      = $_GET['date_from'] ?? date('Y-m-01');
$dateTo        = $_GET['date_to']   ?? date('Y-m-t');
$productFilter = $_GET['product_id'] ?? [];
$paymentMode   = $_GET['payment_mode'] ?? '';

$dateFromEsc    = pg_escape_string($con, $dateFrom);
$dateToEsc      = pg_escape_string($con, $dateTo);
$paymentModeEsc = pg_escape_string($con, $paymentMode);

if (!is_array($productFilter)) $productFilter = [$productFilter];
$productFilter = array_filter($productFilter);

// Build product placeholder params
$productParams = [];
$productCondition = "";
if (!empty($productFilter)) {
    $ph = [];
    foreach ($productFilter as $i => $pid) {
        $ph[] = '$' . ($i + 1);
        $productParams[] = (int)$pid;
    }
    $productCondition = " AND oi.product_id IN (" . implode(',', $ph) . ")";
}

$paymentCondition = "";
if (!empty($paymentMode)) {
    $paymentCondition = " AND o.payment_mode = '" . $paymentModeEsc . "'";
}

// Common FROM/JOIN used by all queries
$baseFrom = "
    FROM emart_orders o
    JOIN emart_order_items oi ON oi.order_id = o.order_id
    LEFT JOIN stock_item si ON si.item_id = oi.product_id
    WHERE o.order_date::date BETWEEN '$dateFromEsc' AND '$dateToEsc'
      AND (o.status IS NULL OR o.status NOT IN ('cancelled','refunded','failed'))
      $paymentCondition
      $productCondition
";

// =========================================================
// 1) MONTHLY EARNINGS
// =========================================================
$monthlyQuery = "
    SELECT
        TO_CHAR(o.order_date, 'YYYY-MM')     AS month_key,
        TO_CHAR(o.order_date, 'Mon YYYY')    AS month_label,
        SUM(oi.quantity * oi.unit_price * (1 - COALESCE(oi.discount_percent,0)/100.0)) AS total_amount,
        COUNT(DISTINCT o.order_id) AS order_count
    $baseFrom
    GROUP BY month_key, month_label
    ORDER BY month_key
";
$monthlyResult = pg_query_params($con, $monthlyQuery, $productParams);
$monthlyData = $monthlyResult ? pg_fetch_all($monthlyResult) : [];

// =========================================================
// 2) PAYMENT MODE SEGREGATION
// =========================================================
$paymentQuery = "
    SELECT
        o.payment_mode,
        SUM(oi.quantity * oi.unit_price * (1 - COALESCE(oi.discount_percent,0)/100.0)) AS total_amount,
        COUNT(DISTINCT o.order_id) AS order_count
    $baseFrom
    GROUP BY o.payment_mode
    ORDER BY total_amount DESC
";
$paymentResult = pg_query_params($con, $paymentQuery, $productParams);
$paymentData = $paymentResult ? pg_fetch_all($paymentResult) : [];

// =========================================================
// 3) PRODUCT-WISE SALES
// =========================================================
$productQuery = "
    SELECT
        oi.product_id,
        COALESCE(si.item_name, 'Product #' || oi.product_id) AS product_name,
        SUM(oi.quantity) AS total_qty,
        SUM(oi.quantity * oi.unit_price * (1 - COALESCE(oi.discount_percent,0)/100.0)) AS total_amount,
        COUNT(DISTINCT o.order_id) AS order_count
    $baseFrom
    GROUP BY oi.product_id, si.item_name
    ORDER BY total_amount DESC
";
$productResult = pg_query_params($con, $productQuery, $productParams);
$productData = $productResult ? pg_fetch_all($productResult) : [];

// =========================================================
// 4) DETAILED LINE ITEMS
// =========================================================
$ordersQuery = "
    SELECT
        o.order_id,
        o.order_number,
        o.order_date,
        o.payment_mode,
        COALESCE(s.studentname, m.fullname, h.name) AS customer_name,
        oi.product_id,
        COALESCE(si.item_name, 'Product #' || oi.product_id) AS product_name,
        oi.quantity,
        oi.unit_price,
        oi.base_price,
        oi.custom_price,
        oi.discount_percent,
        oi.is_fixed_price,
        (oi.quantity * oi.unit_price * (1 - COALESCE(oi.discount_percent,0)/100.0)) AS line_total
    FROM emart_orders o
    JOIN emart_order_items oi ON oi.order_id = o.order_id
    LEFT JOIN stock_item si ON si.item_id = oi.product_id
    LEFT JOIN rssimyprofile_student s ON o.beneficiary = s.student_id
    LEFT JOIN rssimyaccount_members m ON o.beneficiary = m.associatenumber
    LEFT JOIN public_health_records h ON o.beneficiary = h.id::text
    WHERE o.order_date::date BETWEEN '$dateFromEsc' AND '$dateToEsc'
      AND (o.status IS NULL OR o.status NOT IN ('cancelled','refunded','failed'))
      $paymentCondition
      $productCondition
    ORDER BY o.order_date DESC, o.order_id DESC
";
$ordersResult = pg_query_params($con, $ordersQuery, $productParams);
$ordersData = $ordersResult ? pg_fetch_all($ordersResult) : [];

// =========================================================
// 5) TOTALS
// =========================================================
$grandTotal = 0;
foreach ($productData as $p) $grandTotal += (float)$p['total_amount'];

$totalOrders = count(array_unique(array_column($ordersData, 'order_id')));
$totalQty = 0;
foreach ($productData as $p) $totalQty += (int)$p['total_qty'];

// =========================================================
// 6) PRODUCTS DROPDOWN (only products actually sold in range)
// =========================================================
$productsListQuery = "
    SELECT DISTINCT
        oi.product_id,
        COALESCE(si.item_name, 'Product #' || oi.product_id) AS product_name
    FROM emart_orders o
    JOIN emart_order_items oi ON oi.order_id = o.order_id
    LEFT JOIN stock_item si ON si.item_id = oi.product_id
    WHERE o.order_date::date BETWEEN '$dateFromEsc' AND '$dateToEsc'
    ORDER BY product_name
";
$productsListResult = pg_query($con, $productsListQuery);
$productsList = $productsListResult ? pg_fetch_all($productsListResult) : [];

// =========================================================
// 7) PAYMENT MODES DROPDOWN
// =========================================================
$paymentModesQuery = "SELECT DISTINCT payment_mode FROM emart_orders WHERE payment_mode IS NOT NULL ORDER BY payment_mode";
$paymentModesResult = pg_query($con, $paymentModesQuery);
$paymentModes = $paymentModesResult ? pg_fetch_all($paymentModesResult) : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/meta.php'; ?>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .kpi-card {
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
            padding: 18px;
            background: #fff;
            height: 100%;
        }

        .kpi-label {
            font-size: .85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .kpi-value {
            font-size: 1.7rem;
            font-weight: 700;
            color: #2c3e50;
            margin-top: 6px;
        }

        .kpi-icon {
            font-size: 1.8rem;
            opacity: .25;
        }

        .chart-card {
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
            padding: 18px;
            background: #fff;
            margin-bottom: 20px;
        }

        .chart-card h6 {
            font-weight: 600;
            margin-bottom: 14px;
            color: #2c3e50;
        }

        .chart-wrapper {
            position: relative;
            height: 300px;
        }

        .select2-container {
            width: 100% !important;
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
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>

                            <!-- FILTERS -->
                            <form method="GET" class="row g-3 align-items-end mb-4">
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Date Range</label>
                                    <input type="text" name="date_range" class="form-control date-range-picker"
                                        value="<?= htmlspecialchars("$dateFrom - $dateTo") ?>">
                                    <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                                    <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Products</label>
                                    <select name="product_id[]" id="productFilter" class="form-select" multiple>
                                        <?php foreach ($productsList as $p): ?>
                                            <option value="<?= htmlspecialchars($p['product_id']) ?>"
                                                <?= in_array($p['product_id'], $productFilter) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['product_name']) ?>
                                                (ID: <?= htmlspecialchars($p['product_id']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Payment Mode</label>
                                    <select name="payment_mode" class="form-select">
                                        <option value="">All</option>
                                        <?php foreach ($paymentModes as $pm): ?>
                                            <option value="<?= htmlspecialchars($pm['payment_mode']) ?>"
                                                <?= $paymentMode == $pm['payment_mode'] ? 'selected' : '' ?>>
                                                <?= ucfirst(htmlspecialchars($pm['payment_mode'])) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-funnel-fill me-1"></i> Apply
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>"
                                        class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                    </a>
                                </div>
                            </form>

                            <!-- KPI CARDS -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="kpi-card d-flex justify-content-between">
                                        <div>
                                            <div class="kpi-label">Total Sales</div>
                                            <div class="kpi-value">₹<?= number_format($grandTotal, 2) ?></div>
                                        </div>
                                        <i class="bi bi-currency-rupee kpi-icon text-success"></i>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="kpi-card d-flex justify-content-between">
                                        <div>
                                            <div class="kpi-label">Total Orders</div>
                                            <div class="kpi-value"><?= $totalOrders ?></div>
                                        </div>
                                        <i class="bi bi-cart-check kpi-icon text-primary"></i>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="kpi-card d-flex justify-content-between">
                                        <div>
                                            <div class="kpi-label">Items Sold</div>
                                            <div class="kpi-value"><?= $totalQty ?></div>
                                        </div>
                                        <i class="bi bi-box-seam kpi-icon text-warning"></i>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="kpi-card d-flex justify-content-between">
                                        <div>
                                            <div class="kpi-label">Avg. Order Value</div>
                                            <div class="kpi-value">
                                                ₹<?= $totalOrders > 0 ? number_format($grandTotal / $totalOrders, 2) : '0.00' ?>
                                            </div>
                                        </div>
                                        <i class="bi bi-graph-up-arrow kpi-icon text-info"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- CHARTS -->
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="chart-card">
                                        <h6><i class="bi bi-bar-chart-line me-2"></i>Monthly Sales Trend</h6>
                                        <div class="chart-wrapper">
                                            <canvas id="monthlyChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="chart-card">
                                        <h6><i class="bi bi-pie-chart me-2"></i>Payment Mode Split</h6>
                                        <div class="chart-wrapper">
                                            <canvas id="paymentChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="chart-card">
                                        <h6><i class="bi bi-box-seam me-2"></i>Product-wise Sales</h6>
                                        <div class="chart-wrapper" style="height: 380px;">
                                            <canvas id="productChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PRODUCT SUMMARY -->
                            <div class="chart-card">
                                <h6><i class="bi bi-table me-2"></i>Product-wise Summary</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Product ID</th>
                                                <th>Product Name</th>
                                                <th class="text-end">Qty Sold</th>
                                                <th class="text-end">Orders</th>
                                                <th class="text-end">Total Sales</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($productData)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-4">
                                                        No sales data for the selected filters.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($productData as $i => $p): ?>
                                                    <tr>
                                                        <td><?= $i + 1 ?></td>
                                                        <td><?= htmlspecialchars($p['product_id']) ?></td>
                                                        <td><?= htmlspecialchars($p['product_name']) ?></td>
                                                        <td class="text-end"><?= number_format($p['total_qty']) ?></td>
                                                        <td class="text-end"><?= number_format($p['order_count']) ?></td>
                                                        <td class="text-end fw-bold">
                                                            ₹<?= number_format($p['total_amount'], 2) ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3" class="text-end">Grand Total</th>
                                                <th class="text-end"><?= number_format($totalQty) ?></th>
                                                <th class="text-end"><?= number_format($totalOrders) ?></th>
                                                <th class="text-end">₹<?= number_format($grandTotal, 2) ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- LINE ITEMS -->
                            <div class="chart-card">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Detailed Line Items</h6>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'line_items'])) ?>"
                                        class="btn btn-sm btn-outline-success rounded-pill px-3">
                                        <i class="bi bi-download me-1"></i> Export CSV
                                    </a>
                                </div>
                                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">

                                </div>
                            </div>
                        </div>
                    </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="../assets_new/js/main.js"></script>

    <script>
        const monthlyData = <?= json_encode($monthlyData) ?>;
        const paymentData = <?= json_encode($paymentData) ?>;
        const productData = <?= json_encode($productData) ?>;

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
            $('.date-range-picker').on('cancel.daterangepicker', function() {
                $(this).val('');
                $('input[name="date_from"]').val('');
                $('input[name="date_to"]').val('');
            });
            $('#productFilter').select2({
                placeholder: 'All products',
                width: '100%',
                closeOnSelect: false
            });
        });

        // Monthly chart
        new Chart(document.getElementById('monthlyChart'), {
            type: 'bar',
            data: {
                labels: monthlyData.map(d => d.month_label),
                datasets: [{
                        label: 'Sales (₹)',
                        data: monthlyData.map(d => parseFloat(d.total_amount)),
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: monthlyData.map(d => parseInt(d.order_count)),
                        type: 'line',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderWidth: 2,
                        tension: 0.3,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Sales (₹)'
                        },
                        ticks: {
                            callback: v => '₹' + v.toLocaleString('en-IN')
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Orders'
                        }
                    }
                }
            }
        });

        // Payment chart
        const paymentColors = {
            cash: 'rgba(75,192,192,.8)',
            online: 'rgba(54,162,235,.8)',
            freebie: 'rgba(255,206,86,.8)'
        };
        new Chart(document.getElementById('paymentChart'), {
            type: 'doughnut',
            data: {
                labels: paymentData.map(d => d.payment_mode.charAt(0).toUpperCase() + d.payment_mode.slice(1)),
                datasets: [{
                    data: paymentData.map(d => parseFloat(d.total_amount)),
                    backgroundColor: paymentData.map(d => paymentColors[d.payment_mode] || 'rgba(153,102,255,.8)'),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = ((ctx.parsed / total) * 100).toFixed(1);
                                return ctx.label + ': ₹' + ctx.parsed.toLocaleString('en-IN') + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Product chart
        new Chart(document.getElementById('productChart'), {
            type: 'bar',
            data: {
                labels: productData.map(d => d.product_name),
                datasets: [{
                    label: 'Sales (₹)',
                    data: productData.map(d => parseFloat(d.total_amount)),
                    backgroundColor: 'rgba(75,192,192,.75)',
                    borderColor: 'rgba(75,192,192,1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => 'Sales: ₹' + ctx.parsed.x.toLocaleString('en-IN')
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: v => '₹' + v.toLocaleString('en-IN')
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>