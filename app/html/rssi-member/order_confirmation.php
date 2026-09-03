<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

// Get order ID from URL
$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header("Location: emart_orders.php");
    exit;
}

// Fetch order details
$orderQuery = "SELECT o.*, m.fullname AS created_by,
        COALESCE(s.studentname, c.fullname, h.name) AS customer_name,
        COALESCE(s.contact, c.phone, h.contact_number) AS customer_contact,
        COALESCE(s.emailaddress, c.email, h.email) AS customer_email 
               FROM emart_orders o
               LEFT JOIN rssimyprofile_student s ON o.beneficiary = s.student_id
               LEFT JOIN rssimyaccount_members c ON o.beneficiary = c.associatenumber
               LEFT JOIN public_health_records h ON o.beneficiary = h.id::text
               JOIN rssimyaccount_members m ON o.associatenumber = m.associatenumber
               WHERE o.order_id = $1";
$orderResult = pg_query_params($con, $orderQuery, [$orderId]);
$order = pg_fetch_assoc($orderResult);

if (!$order) {
    header("Location: emart_orders.php");
    exit;
}

// Fetch order items with dynamic pricing details
$itemsQuery = "SELECT 
    oi.*, 
    i.item_name, 
    i.image_url,
    CASE 
        WHEN oi.is_fixed_price = true THEN 'Fixed Price'
        ELSE 'Dynamic Price'
    END AS price_type,
    CASE 
        WHEN oi.is_fixed_price = true THEN oi.unit_price
        ELSE oi.unit_price
    END AS final_price
FROM emart_order_items oi
JOIN stock_item i ON oi.product_id = i.item_id
WHERE oi.order_id = $1
ORDER BY oi.item_id";
$itemsResult = pg_query_params($con, $itemsQuery, [$orderId]);
$items = pg_fetch_all($itemsResult);

// Get stored values from session
$itemsPerPage = $_SESSION['emart_items_per_page'] ?? 5;
$page = $_SESSION['emart_page'] ?? 1;
$searchTerm = $_SESSION['emart_search'] ?? '';
?>

<!DOCTYPE html>
<html>

<head>
    <?php include 'includes/meta.php' ?>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background: white;
        }

        .print-only {
            display: none;
        }

        .price-detail {
            font-size: 0.85rem;
            color: #666;
        }

        .price-detail .original-price {
            text-decoration: line-through;
            color: #999;
        }

        .price-detail .discounted-price {
            color: #28a745;
            font-weight: bold;
        }

        .dynamic-price-badge {
            background-color: #ff9800;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            display: inline-block;
        }

        .fixed-price-badge {
            background-color: #4caf50;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            display: inline-block;
        }

        @media print {
            .no-print {
                display: none;
            }

            .print-only {
                display: block;
            }

            body {
                background: white;
                font-size: 12pt;
            }

            .receipt-container {
                border: none;
                box-shadow: none;
                padding: 0;
            }

            .dynamic-price-badge,
            .fixed-price-badge {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="pagetitle">
        <!-- <h1><?php echo getPageTitle(); ?></h1> -->
        <!-- <?php echo generateDynamicBreadcrumb(); ?> -->
    </div>
    <div class="container py-4">
        <div class="receipt-container">
            <div class="text-center mb-4">
                <h2><i class="bi bi-check-circle-fill text-success"></i> Order Confirmation</h2>
                <p class="text-muted">Order #<?= htmlspecialchars($order['order_number']) ?></p>
                <p><span class="badge bg-success">Completed</span></p>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h5><i class="bi bi-info-circle"></i> Order Details</h5>
                    <p>
                        <strong>Date:</strong> <?= date('M j, Y g:i A', strtotime($order['order_date'])) ?><br>
                        <strong>Order ID:</strong> #<?= htmlspecialchars($order['order_id']) ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h5><i class="bi bi-person"></i> Customer Information</h5>
                    <p>
                        <strong>Name:</strong> <?= !empty($order['customer_name']) ? htmlspecialchars($order['customer_name']) : '—' ?><br>
                        <strong>Contact:</strong> <?= !empty($order['customer_contact']) ? htmlspecialchars($order['customer_contact']) : '—' ?><br>
                        <strong>Email:</strong> <?= !empty($order['customer_email']) ? htmlspecialchars($order['customer_email']) : '—' ?>
                    </p>
                    <h5><i class="bi bi-credit-card"></i> Payment Information</h5>
                    <p>
                        <strong>Payment Method:</strong> <?= ucfirst($order['payment_mode']) ?><br>
                        <?php if ($order['transaction_id']): ?>
                            <strong>Transaction ID:</strong> <?= htmlspecialchars($order['transaction_id']) ?><br>
                        <?php endif; ?>
                        <strong>Total Amount:</strong> <span class="fw-bold text-success">₹<?= number_format($order['total_amount'], 2) ?></span>
                    </p>
                    <?php if (!empty($order['remarks'])): ?>
                        <p><strong>Remarks:</strong> <?= htmlspecialchars($order['remarks']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <h5><i class="bi bi-list-ul"></i> Items Ordered</h5>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Price Details</th>
                            <th>Qty</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subtotal = 0;
                        foreach ($items as $item):
                            $itemTotal = $item['unit_price'] * $item['quantity'];
                            $subtotal += $itemTotal;
                            $isFixedPrice = $item['is_fixed_price'] === 't' || $item['is_fixed_price'] === true || $item['is_fixed_price'] === 'true';
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($item['image_url']): ?>
                                            <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                                alt="<?= htmlspecialchars($item['item_name']) ?>"
                                                width="50" class="me-2 rounded">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                                            <div class="text-muted small">
                                                <?= $item['unit_quantity'] ?> <?= $item['unit_name'] ?>
                                                <?php if ($isFixedPrice): ?>
                                                    <span class="fixed-price-badge">Fixed</span>
                                                <?php else: ?>
                                                    <span class="dynamic-price-badge">Dynamic</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isFixedPrice): ?>
                                        <div>
                                            <span class="fw-bold">₹<?= number_format($item['unit_price'], 2) ?></span>
                                            <div class="price-detail">per <?= $item['unit_quantity'] ?> <?= $item['unit_name'] ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div>
                                            <div class="price-detail">
                                                <strong>Base Price:</strong> ₹<?= number_format($item['base_price'] ?? $item['unit_price'], 2) ?>
                                            </div>
                                            <?php if (isset($item['custom_price']) && $item['custom_price'] > 0): ?>
                                                <div class="price-detail">
                                                    <strong>Custom Price:</strong> ₹<?= number_format($item['custom_price'], 2) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($item['discount_percent']) && $item['discount_percent'] > 0): ?>
                                                <div class="price-detail">
                                                    <strong>Discount:</strong> <?= number_format($item['discount_percent'], 0) ?>%
                                                </div>
                                            <?php endif; ?>
                                            <div class="price-detail">
                                                <strong>Final Price:</strong>
                                                <span class="discounted-price">₹<?= number_format($item['unit_price'], 2) ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= $item['quantity'] ?></td>
                                <td class="text-end fw-bold">₹<?= number_format($itemTotal, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Subtotal:</th>
                            <th class="text-end">₹<?= number_format($subtotal, 2) ?></th>
                        </tr>
                        <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                            <tr>
                                <th colspan="3" class="text-end text-danger">Discount:</th>
                                <th class="text-end text-danger">-₹<?= number_format($order['discount_amount'], 2) ?></th>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th colspan="3" class="text-end">Total:</th>
                            <th class="text-end fs-5 text-success">₹<?= number_format($order['total_amount'], 2) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- <?php if (!empty($order['beneficiary'])): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Beneficiary ID:</strong> <?= htmlspecialchars($order['beneficiary']) ?>
                </div>
            <?php endif; ?> -->

            <div class="no-print mt-4 d-flex gap-2">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Print Receipt
                </button>
                <a href="emart.php?itemsPerPage=<?php echo $itemsPerPage; ?>&page=<?php echo $page; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>"
                    class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Continue Shopping
                </a>
                <a href="emart_orders.php" class="btn btn-outline-info">
                    <i class="bi bi-list-ul"></i> View All Orders
                </a>
            </div>

            <div class="print-only mt-4 text-center">
                <p>Thank you for your order!</p>
                <p>Order #: <?= htmlspecialchars($order['order_number']) ?></p>
                <p>Date: <?= date('M j, Y g:i A', strtotime($order['order_date'])) ?></p>
                <p class="mt-3">Total: ₹<?= number_format($order['total_amount'], 2) ?></p>
                <hr>
                <p><small>This is a system generated receipt.</small></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>