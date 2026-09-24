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
// FILTERS
// =========================================================
$dateFromRaw   = $_GET['date_from'] ?? '';
$dateToRaw     = $_GET['date_to']   ?? '';
$productFilter = $_GET['product_id'] ?? [];
$paymentMode   = $_GET['payment_mode'] ?? '';

$validDate = fn($d) => $d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
$datesProvided = $validDate($dateFromRaw) && $validDate($dateToRaw);

if ($datesProvided) {
    $dateFrom = $dateFromRaw;
    $dateTo   = $dateToRaw;
} else {
    $dateFrom = date('Y-m-01');
    $dateTo   = date('Y-m-t');
}

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

// Shared SQL fragments
$mrpExpr       = "COALESCE(NULLIF(oi.base_price,0), NULLIF(oi.custom_price,0), oi.unit_price)";
$finalExpr     = "(" . $mrpExpr . " * (1 - COALESCE(oi.discount_percent,0)/100.0))";
$lineTotalExpr = "(" . $finalExpr . " * oi.quantity)";

// =========================================================
// EXPORT — DETAILED LINE ITEMS (CSV)
// =========================================================
if (isset($_GET['export']) && $_GET['export'] === 'line_items') {

    $exportQuery = "
        SELECT
            o.order_id,
            o.order_number,
            o.order_date,
            o.payment_mode,
            o.total_amount AS order_total,
            COALESCE(s.studentname, m.fullname, h.name) AS customer_name,
            oi.product_id,
            COALESCE(si.item_name, 'Product #' || oi.product_id) AS product_name,
            oi.quantity,
            $mrpExpr AS mrp,
            COALESCE(oi.discount_percent,0) AS discount_percent,
            ($mrpExpr - $finalExpr) AS discount_amount,
            $finalExpr AS final_price,
            ($mrpExpr * oi.quantity) AS mrp_value,
            $lineTotalExpr AS line_total
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
        ORDER BY o.order_date DESC, o.order_id DESC, oi.item_id ASC
    ";

    $exportResult = pg_query_params($con, $exportQuery, $productParams);
    $exportData = $exportResult ? pg_fetch_all($exportResult) : [];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="detailed_line_items_' . date('Y-m-d_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, [
        'Order ID',
        'Order Number',
        'Order Date',
        'Customer',
        'Product ID',
        'Product Name',
        'Quantity',
        'MRP',
        'Discount %',
        'Discount Amount',
        'Final Price',
        'MRP Value',
        'Line Total',
        'Order Total',
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
            number_format((float)$row['mrp'], 2, '.', ''),
            number_format((float)$row['discount_percent'], 2, '.', ''),
            number_format((float)$row['discount_amount'], 2, '.', ''),
            number_format((float)$row['final_price'], 2, '.', ''),
            number_format((float)$row['mrp_value'], 2, '.', ''),
            number_format((float)$row['line_total'], 2, '.', ''),
            number_format((float)$row['order_total'], 2, '.', ''),
            ucfirst($row['payment_mode'] ?? '')
        ]);
    }

    fclose($output);
    exit;
}

// =========================================================
// 1) MONTHLY EARNINGS
// =========================================================
$monthlyQuery = "
    SELECT
        TO_CHAR(o.order_date, 'YYYY-MM')  AS month_key,
        TO_CHAR(o.order_date, 'Mon YYYY') AS month_label,
        SUM($lineTotalExpr) AS total_amount,
        COUNT(DISTINCT o.order_id) AS order_count
    FROM emart_orders o
    JOIN emart_order_items oi ON oi.order_id = o.order_id
    WHERE o.order_date::date BETWEEN '$dateFromEsc' AND '$dateToEsc'
      AND (o.status IS NULL OR o.status NOT IN ('cancelled','refunded','failed'))
      AND o.total_amount > 0
      $paymentCondition
      $productCondition
    GROUP BY month_key, month_label
    ORDER BY month_key
";
$monthlyResult = pg_query_params($con, $monthlyQuery, $productParams);
$monthlyData = $monthlyResult ? pg_fetch_all($monthlyResult) : [];

// =========================================================
// 2) PAYMENT MODE SPLIT — cash & online only
// =========================================================
$paymentQuery = "
    SELECT
        o.payment_mode,
        SUM($lineTotalExpr) AS total_amount,
        COUNT(DISTINCT o.order_id) AS order_count
    FROM emart_orders o
    JOIN emart_order_items oi ON oi.order_id = o.order_id
    WHERE o.order_date::date BETWEEN '$dateFromEsc' AND '$dateToEsc'
      AND (o.status IS NULL OR o.status NOT IN ('cancelled','refunded','failed'))
      AND LOWER(o.payment_mode) IN ('cash','online')
      AND o.total_amount > 0
      $productCondition
    GROUP BY o.payment_mode
    ORDER BY total_amount DESC
";
$paymentResult = pg_query_params($con, $paymentQuery, $productParams);
$paymentData = $paymentResult ? pg_fetch_all($paymentResult) : [];

// =========================================================
// 3) PAID vs FREEBIE summary
// =========================================================
$paidFreebieQuery = "
    SELECT
        CASE WHEN o.total_amount > 0 THEN 'Paid' ELSE 'Freebie' END AS sale_type,
        COUNT(DISTINCT o.order_id) AS order_count,
        SUM(oi.quantity) AS total_qty,
        SUM($lineTotalExpr) AS list_value,
        SUM(CASE WHEN o.total_amount > 0 THEN $lineTotalExpr ELSE 0 END) AS paid_value
    FROM emart_orders o
    JOIN emart_order_items oi ON oi.order_id = o.order_id
    WHERE o.order_date::date BETWEEN '$dateFromEsc' AND '$dateToEsc'
      AND (o.status IS NULL OR o.status NOT IN ('cancelled','refunded','failed'))
      $paymentCondition
      $productCondition
    GROUP BY sale_type
    ORDER BY sale_type
";
$paidFreebieResult = pg_query_params($con, $paidFreebieQuery, $productParams);
$paidFreebieData = $paidFreebieResult ? pg_fetch_all($paidFreebieResult) : [];

// =========================================================
// 4A) PRODUCT-WISE — PAID only
// =========================================================
$paidProductQuery = "
    SELECT
        oi.product_id,
        COALESCE(si.item_name, 'Product #' || oi.product_id) AS product_name,
        SUM(oi.quantity) AS total_qty,
        SUM($mrpExpr * oi.quantity) AS mrp_value,
        SUM($mrpExpr * oi.quantity) - SUM($lineTotalExpr) AS discount_value,
        SUM($lineTotalExpr) AS paid_amount,
        COUNT(DISTINCT o.order_id) AS order_count
    FROM emart_orders o
    JOIN emart_order_items oi ON oi.order_id = o.order_id
    LEFT JOIN stock_item si ON si.item_id = oi.product_id
    WHERE o.order_date::date BETWEEN '$dateFromEsc' AND '$dateToEsc'
      AND (o.status IS NULL OR o.status NOT IN ('cancelled','refunded','failed'))
      AND o.total_amount > 0
      $paymentCondition
      $productCondition
    GROUP BY oi.product_id, si.item_name
    ORDER BY paid_amount DESC, total_qty DESC
";
$paidProductResult = pg_query_params($con, $paidProductQuery, $productParams);
$paidProductData = $paidProductResult ? pg_fetch_all($paidProductResult) : [];

// =========================================================
// 4B) PRODUCT-WISE — FREEBIE only
// =========================================================
$freebieProductQuery = "
    SELECT
        oi.product_id,
        COALESCE(si.item_name, 'Product #' || oi.product_id) AS product_name,
        SUM(oi.quantity) AS total_qty,
        SUM($mrpExpr * oi.quantity) AS mrp_value,
        COUNT(DISTINCT o.order_id) AS order_count
    FROM emart_orders o
    JOIN emart_order_items oi ON oi.order_id = o.order_id
    LEFT JOIN stock_item si ON si.item_id = oi.product_id
    WHERE o.order_date::date BETWEEN '$dateFromEsc' AND '$dateToEsc'
      AND (o.status IS NULL OR o.status NOT IN ('cancelled','refunded','failed'))
      AND o.total_amount = 0
      $paymentCondition
      $productCondition
    GROUP BY oi.product_id, si.item_name
    ORDER BY mrp_value DESC, total_qty DESC
";
$freebieProductResult = pg_query_params($con, $freebieProductQuery, $productParams);
$freebieProductData = $freebieProductResult ? pg_fetch_all($freebieProductResult) : [];

// =========================================================
// 5) DETAILED LINE ITEMS — grouped
// =========================================================
$ordersQuery = "
    SELECT
        o.order_id,
        o.order_number,
        o.order_date,
        o.payment_mode,
        o.total_amount AS order_total,
        COALESCE(s.studentname, m.fullname, h.name) AS customer_name,
        oi.product_id,
        COALESCE(si.item_name, 'Product #' || oi.product_id) AS product_name,
        $mrpExpr AS mrp,
        $finalExpr AS final_price,
        COALESCE(oi.discount_percent, 0) AS discount_percent,
        SUM(oi.quantity) AS quantity,
        SUM($mrpExpr * oi.quantity) AS mrp_value,
        SUM($mrpExpr * oi.quantity) - SUM($lineTotalExpr) AS discount_amount,
        SUM($lineTotalExpr) AS line_total
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
    GROUP BY
        o.order_id, o.order_number, o.order_date, o.payment_mode, o.total_amount,
        COALESCE(s.studentname, m.fullname, h.name),
        oi.product_id, si.item_name,
        $mrpExpr,
        $finalExpr,
        COALESCE(oi.discount_percent, 0)
    ORDER BY o.order_id DESC
";
$ordersResult = pg_query_params($con, $ordersQuery, $productParams);
$ordersData = $ordersResult ? pg_fetch_all($ordersResult) : [];

// =========================================================
// 6) TOTALS
// =========================================================
$kpiQuery = "
    SELECT
        SUM($lineTotalExpr) AS total_paid,
        COUNT(DISTINCT o.order_id) AS total_orders,
        SUM(oi.quantity) AS total_qty
    FROM emart_orders o
    JOIN emart_order_items oi ON oi.order_id = o.order_id
    WHERE o.order_date::date BETWEEN '$dateFromEsc' AND '$dateToEsc'
      AND (o.status IS NULL OR o.status NOT IN ('cancelled','refunded','failed'))
      AND o.total_amount > 0
      $paymentCondition
      $productCondition
";
$kpiResult = pg_query_params($con, $kpiQuery, $productParams);
$kpi = $kpiResult ? pg_fetch_assoc($kpiResult) : ['total_paid' => 0, 'total_orders' => 0, 'total_qty' => 0];

$grandTotal  = (float)($kpi['total_paid'] ?? 0);
$totalOrders = (int)($kpi['total_orders'] ?? 0);
$totalQty    = (int)($kpi['total_qty'] ?? 0);

$totalFreebieQty   = 0;
$totalFreebieValue = 0;
foreach ($paidFreebieData as $pf) {
    if ($pf['sale_type'] === 'Freebie') {
        $totalFreebieQty   = (int)$pf['total_qty'];
        $totalFreebieValue = (float)$pf['list_value'];
    }
}

// =========================================================
// 7) PAYMENT MODES DROPDOWN
// =========================================================
$paymentModesQuery = "
    SELECT DISTINCT payment_mode
    FROM emart_orders
    WHERE payment_mode IS NOT NULL AND TRIM(payment_mode) <> ''
    ORDER BY payment_mode
";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">

    <style>
        .kpi-card {
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
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

        .table-freebie {
            background-color: #fff8e1;
        }

        .section-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 12px;
        }

        .section-title i {
            margin-right: 6px;
        }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 6px;
            padding: 4px 8px;
            border: 1px solid #ced4da;
        }

        .dataTables_wrapper .dataTables_length select {
            border-radius: 6px;
            padding: 4px 6px;
            border: 1px solid #ced4da;
        }
    </style>

    <!-- jQuery first -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Select2 JS (loaded in head, before body scripts) -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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

                            <form method="GET" class="row g-3 align-items-end mb-4" id="filterForm">
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Date Range <span class="text-danger">*</span></label>
                                    <input type="text" name="date_range" id="dateRange"
                                        class="form-control date-range-picker"
                                        value="<?= htmlspecialchars("$dateFrom - $dateTo") ?>"
                                        placeholder="Select date range" readonly>
                                    <input type="hidden" name="date_from" id="dateFrom" value="<?= htmlspecialchars($dateFrom) ?>">
                                    <input type="hidden" name="date_to" id="dateTo" value="<?= htmlspecialchars($dateTo) ?>">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Products</label>
                                    <select name="product_id[]" id="productFilter" class="form-control select2" multiple="multiple">
                                        <?php
                                        // Pre-render selected options so they persist on filter submit
                                        if (!empty($productFilter)) {
                                            $inList = implode(',', array_map('intval', $productFilter));
                                            $selQuery = "SELECT item_id, item_name FROM stock_item WHERE item_id IN ($inList) ORDER BY item_name";
                                            $selResult = pg_query($con, $selQuery);
                                            if ($selResult) {
                                                while ($srow = pg_fetch_assoc($selResult)) {
                                                    echo '<option value="' . htmlspecialchars($srow['item_id']) . '" selected>'
                                                        . htmlspecialchars($srow['item_name']) . '</option>';
                                                }
                                            }
                                        }
                                        ?>
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
                                            <div class="kpi-label">Total Sales (Paid)</div>
                                            <div class="kpi-value">₹<?= number_format($grandTotal, 2) ?></div>
                                        </div>
                                        <i class="bi bi-currency-rupee kpi-icon text-success"></i>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="kpi-card d-flex justify-content-between">
                                        <div>
                                            <div class="kpi-label">Paid Orders</div>
                                            <div class="kpi-value"><?= $totalOrders ?></div>
                                        </div>
                                        <i class="bi bi-cart-check kpi-icon text-primary"></i>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="kpi-card d-flex justify-content-between">
                                        <div>
                                            <div class="kpi-label">Items Sold (Paid)</div>
                                            <div class="kpi-value"><?= $totalQty ?></div>
                                        </div>
                                        <i class="bi bi-box-seam kpi-icon text-warning"></i>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="kpi-card d-flex justify-content-between">
                                        <div>
                                            <div class="kpi-label">Freebie Items / Value</div>
                                            <div class="kpi-value">
                                                <?= $totalFreebieQty ?> / ₹<?= number_format($totalFreebieValue, 2) ?>
                                            </div>
                                        </div>
                                        <i class="bi bi-gift kpi-icon text-danger"></i>
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
                                        <small class="text-muted">Only Cash &amp; Online</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="chart-card">
                                        <h6><i class="bi bi-gift me-2"></i>Paid vs Freebie — Qty &amp; Value</h6>
                                        <div class="chart-wrapper" style="height: 320px;">
                                            <canvas id="paidFreebieChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="chart-card">
                                        <h6><i class="bi bi-box-seam me-2"></i>Product-wise Paid Sales</h6>
                                        <div class="chart-wrapper" style="height: 380px;">
                                            <canvas id="productChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PRODUCT SUMMARY — PAID -->
                            <div class="chart-card">
                                <div class="section-title">
                                    <i class="bi bi-cart-check text-success"></i>
                                    Product-wise Summary — <span class="text-success">Paid Sales</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Product ID</th>
                                                <th>Product Name</th>
                                                <th class="text-end">Qty Sold</th>
                                                <th class="text-end">Orders</th>
                                                <th class="text-end">MRP Value</th>
                                                <th class="text-end">Discount</th>
                                                <th class="text-end">Paid Sales</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($paidProductData)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted py-4">
                                                        No paid sales for the selected filters.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php
                                                $sumQty = 0;
                                                $sumMRP = 0;
                                                $sumDisc = 0;
                                                $sumPaid = 0;
                                                foreach ($paidProductData as $i => $p):
                                                    $sumQty  += (int)$p['total_qty'];
                                                    $sumMRP  += (float)$p['mrp_value'];
                                                    $sumDisc += (float)$p['discount_value'];
                                                    $sumPaid += (float)$p['paid_amount'];
                                                ?>
                                                    <tr>
                                                        <td><?= $i + 1 ?></td>
                                                        <td><?= htmlspecialchars($p['product_id']) ?></td>
                                                        <td><?= htmlspecialchars($p['product_name']) ?></td>
                                                        <td class="text-end"><?= number_format($p['total_qty']) ?></td>
                                                        <td class="text-end"><?= number_format($p['order_count']) ?></td>
                                                        <td class="text-end">₹<?= number_format($p['mrp_value'], 2) ?></td>
                                                        <td class="text-end text-danger">- ₹<?= number_format($p['discount_value'], 2) ?></td>
                                                        <td class="text-end fw-bold text-success">₹<?= number_format($p['paid_amount'], 2) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3" class="text-end">Grand Total</th>
                                                <th class="text-end"><?= number_format($sumQty ?? 0) ?></th>
                                                <th class="text-end"><?= number_format($totalOrders) ?></th>
                                                <th class="text-end">₹<?= number_format($sumMRP ?? 0, 2) ?></th>
                                                <th class="text-end text-danger">- ₹<?= number_format($sumDisc ?? 0, 2) ?></th>
                                                <th class="text-end text-success">₹<?= number_format($sumPaid ?? 0, 2) ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    <small class="text-muted">
                                        <strong>MRP Value − Discount = Paid Sales</strong> per product.
                                        Per-product "Orders" counts distinct paid orders containing that product;
                                        a single order may contain multiple products, so the footer shows the total unique paid orders.
                                    </small>
                                </div>
                            </div>

                            <!-- PRODUCT SUMMARY — FREEBIES -->
                            <div class="chart-card">
                                <div class="section-title">
                                    <i class="bi bi-gift text-warning"></i>
                                    Product-wise Summary — <span class="text-warning">Freebies Given</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Product ID</th>
                                                <th>Product Name</th>
                                                <th class="text-end">Qty Given</th>
                                                <th class="text-end">Orders</th>
                                                <th class="text-end">MRP Value (Free)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($freebieProductData)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-4">
                                                        No freebies given for the selected filters.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php
                                                $fbQty = 0;
                                                $fbMRP = 0;
                                                $fbOrders = 0;
                                                foreach ($freebieProductData as $i => $p):
                                                    $fbQty    += (int)$p['total_qty'];
                                                    $fbMRP    += (float)$p['mrp_value'];
                                                    $fbOrders += (int)$p['order_count'];
                                                ?>
                                                    <tr>
                                                        <td><?= $i + 1 ?></td>
                                                        <td><?= htmlspecialchars($p['product_id']) ?></td>
                                                        <td><?= htmlspecialchars($p['product_name']) ?></td>
                                                        <td class="text-end"><?= number_format($p['total_qty']) ?></td>
                                                        <td class="text-end"><?= number_format($p['order_count']) ?></td>
                                                        <td class="text-end fw-bold text-warning">₹<?= number_format($p['mrp_value'], 2) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3" class="text-end">Grand Total</th>
                                                <th class="text-end"><?= number_format($fbQty ?? 0) ?></th>
                                                <th class="text-end">—</th>
                                                <th class="text-end text-warning">₹<?= number_format($fbMRP ?? 0, 2) ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                    <small class="text-muted">
                                        <strong>Freebies</strong> are orders where <code>total_amount = 0</code>.
                                        MRP Value shown is the list value of goods given free.
                                    </small>
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
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle" id="lineItemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Order #</th>
                                                <th>Date</th>
                                                <th>Customer</th>
                                                <th>Product</th>
                                                <th class="text-end">Qty</th>
                                                <th class="text-end">MRP</th>
                                                <th class="text-end">Disc %</th>
                                                <th class="text-end">Disc Amt</th>
                                                <th class="text-end">Final Price/Unit</th>
                                                <th class="text-end">Line Total</th>
                                                <th>Payment</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($ordersData)): ?>
                                                <tr>
                                                    <td colspan="11" class="text-center text-muted py-4">
                                                        No line items found.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($ordersData as $row): ?>
                                                    <tr class="<?= ((float)$row['line_total'] === 0.0 || strtolower($row['payment_mode']) === 'freebie') ? 'table-freebie' : '' ?>">
                                                        <td><?= htmlspecialchars($row['order_number']) ?></td>
                                                        <td data-order="<?= date('Y-m-d', strtotime($row['order_date'])) ?>"><?= date('d/m/Y', strtotime($row['order_date'])) ?></td>
                                                        <td><?= htmlspecialchars($row['customer_name'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['product_name']) ?></td>
                                                        <td class="text-end" data-order="<?= (int)$row['quantity'] ?>"><?= (int)$row['quantity'] ?></td>
                                                        <td class="text-end" data-order="<?= (float)$row['mrp'] ?>">₹<?= number_format($row['mrp'], 2) ?></td>
                                                        <td class="text-end" data-order="<?= (float)$row['discount_percent'] ?>"><?= number_format($row['discount_percent'], 2) ?>%</td>
                                                        <td class="text-end text-danger" data-order="<?= (float)$row['discount_amount'] ?>">- ₹<?= number_format($row['discount_amount'], 2) ?></td>
                                                        <td class="text-end" data-order="<?= (float)$row['final_price'] ?>">₹<?= number_format($row['final_price'], 2) ?></td>
                                                        <td class="text-end fw-bold" data-order="<?= (float)$row['line_total'] ?>">₹<?= number_format($row['line_total'], 2) ?></td>
                                                        <td><?= ucfirst(htmlspecialchars($row['payment_mode'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    Highlighted rows = Freebie orders (₹0 paid).
                                    Each price line is preserved (dynamic-price items may repeat for the same product at different prices).
                                </small>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <script src="../assets_new/js/main.js"></script>

    <script>
        const monthlyData = <?= json_encode($monthlyData) ?>;
        const paymentData = <?= json_encode($paymentData) ?>;
        const paidProductData = <?= json_encode($paidProductData) ?>;
        const paidFreebieData = <?= json_encode($paidFreebieData) ?>;

        $(document).ready(function() {
            const initialStart = '<?= $dateFrom ?>';
            const initialEnd = '<?= $dateTo ?>';

            // ---- Date Range Picker ----
            $('.date-range-picker').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD'
                },
                startDate: initialStart,
                endDate: initialEnd
            });

            $('.date-range-picker').val(initialStart + ' - ' + initialEnd);

            $('.date-range-picker').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                $('#dateFrom').val(picker.startDate.format('YYYY-MM-DD'));
                $('#dateTo').val(picker.endDate.format('YYYY-MM-DD'));
            });

            $('.date-range-picker').on('cancel.daterangepicker', function() {
                $(this).val('');
                $('#dateFrom').val('');
                $('#dateTo').val('');
            });

            // ---- Products Select2 (AJAX) ----
            // search_products.php returns { products: [{id, name}, ...] }
            // Select2 needs each item to have id + text
            $('#productFilter').select2({
                placeholder: 'Search by product name',
                width: '100%',
                minimumInputLength: 1,
                ajax: {
                    url: 'search_products.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term,
                            itemsPerPage: 50,
                            page: 1
                        };
                    },
                    processResults: function(data) {
                        // search_products.php returns either:
                        //   { products: [...] }  OR  { results: [...] }
                        const list = data.products || data.results || [];
                        return {
                            results: list.map(function(p) {
                                return {
                                    id: p.id,
                                    text: p.name || p.text || ('Product #' + p.id)
                                };
                            })
                        };
                    },
                    cache: true
                }
            });

            // ---- Form submit validation ----
            $('#filterForm').on('submit', function(e) {
                if (!$('#dateFrom').val() || !$('#dateTo').val()) {
                    e.preventDefault();
                    alert('Please select a date range before applying filters.');
                    return false;
                }
            });

            // ---- DataTable — Detailed Line Items ----
            if ($('#lineItemsTable').length && !$('#lineItemsTable tbody td[colspan]').length) {
                $('#lineItemsTable').DataTable({
                    paging: true,
                    pageLength: 25,

                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "All"]
                    ],

                    // IMPORTANT:
                    // Do not apply any initial sorting.
                    // Keep the order returned by the database.
                    order: [],

                    columnDefs: [{
                        orderable: false,
                        targets: [10]
                    }],

                    language: {
                        search: "Search line items:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ line items",
                        infoEmpty: "No line items",
                        zeroRecords: "No matching line items found"
                    },

                    dom: '<"row mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row mt-2"<"col-md-6"i><"col-md-6"p>>'
                });
            }
        });

        // ---- Monthly chart ----
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

        // ---- Payment chart ----
        const modeColorMap = {
            cash: 'rgba(75,192,192,.8)',
            online: 'rgba(54,162,235,.8)'
        };
        new Chart(document.getElementById('paymentChart'), {
            type: 'doughnut',
            data: {
                labels: paymentData.map(d => d.payment_mode.charAt(0).toUpperCase() + d.payment_mode.slice(1).toLowerCase()),
                datasets: [{
                    data: paymentData.map(d => parseFloat(d.total_amount)),
                    backgroundColor: paymentData.map(d => modeColorMap[d.payment_mode] || 'rgba(153,102,255,.8)'),
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
                                const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return ctx.label + ': ₹' + ctx.parsed.toLocaleString('en-IN') + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });

        // ---- Paid vs Freebie chart ----
        (function() {
            const labels = paidFreebieData.map(d => d.sale_type);
            const qty = paidFreebieData.map(d => parseInt(d.total_qty));
            const value = paidFreebieData.map(d => parseFloat(d.list_value));

            new Chart(document.getElementById('paidFreebieChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Qty',
                            data: qty,
                            backgroundColor: 'rgba(54, 162, 235, 0.75)',
                            yAxisID: 'y'
                        },
                        {
                            label: 'Value (₹)',
                            data: value,
                            backgroundColor: 'rgba(255, 206, 86, 0.75)',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.label === 'Value (₹)' ?
                                    'Value: ₹' + ctx.parsed.y.toLocaleString('en-IN') : 'Qty: ' + ctx.parsed.y
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Quantity'
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
                                text: 'Value (₹)'
                            },
                            ticks: {
                                callback: v => '₹' + v.toLocaleString('en-IN')
                            }
                        }
                    }
                }
            });
        })();

        // ---- Product chart ----
        new Chart(document.getElementById('productChart'), {
            type: 'bar',
            data: {
                labels: paidProductData.map(d => d.product_name),
                datasets: [{
                    label: 'Paid Sales (₹)',
                    data: paidProductData.map(d => parseFloat(d.paid_amount)),
                    backgroundColor: 'rgba(75, 192, 192, 0.75)',
                    borderColor: 'rgba(75, 192, 192, 1)',
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
                            label: ctx => 'Paid: ₹' + ctx.parsed.x.toLocaleString('en-IN')
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