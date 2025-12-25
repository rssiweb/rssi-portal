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
$order = pg_fetch_assoc(pg_query_params($con, $orderQuery, [$orderId]));

// Fetch order items
$itemsQuery = "SELECT oi.*, i.item_name, i.image_url 
               FROM emart_order_items oi
               JOIN stock_item i ON oi.product_id = i.item_id
               WHERE oi.order_id = $1";
$itemsResult = pg_query_params($con, $itemsQuery, [$orderId]);
$items = pg_fetch_all($itemsResult);
?>
<?php
// Get stored values from session
$itemsPerPage = $_SESSION['emart_items_per_page'] ?? 5; // Default to 5 if not set
$page = $_SESSION['emart_page'] ?? 1;
$searchTerm = $_SESSION['emart_search'] ?? '';
?>

<!DOCTYPE html>
<html>

<head>
    <title>Order Confirmation</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .print-only {
            display: none;
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
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="receipt-container">
            <div class="text-center mb-4">
                <h2>Order Confirmation</h2>
                <p class="text-muted">Order #<?= htmlspecialchars($order['order_number']) ?></p>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Order Details</h5>
                    <p><strong>Date:</strong> <?= date('M j, Y g:i A', strtotime($order['order_date'])) ?></p>
                    <!-- <p><strong>Status:</strong> <span class="badge bg-success">Completed</span></p> -->
                </div>
                <div class="col-md-6">
                    <!-- Customer Information -->
                    <h5>Customer Information</h5>
                    <p><strong>Name:</strong> <?= !empty($order['customer_name']) ? htmlspecialchars($order['customer_name']) : '—' ?><br>
                        <strong>Contact:</strong> <?= !empty($order['customer_contact']) ? htmlspecialchars($order['customer_contact']) : '—' ?><br>
                        <strong>Email:</strong> <?= !empty($order['customer_email']) ? htmlspecialchars($order['customer_email']) : '—' ?>
                    </p>
                    <h5>Payment Information</h5>
                    <p><strong>Payment Method:</strong> <?= ucfirst($order['payment_mode']) ?><br>
                        <?php if ($order['transaction_id']): ?>
                            <strong>Transaction ID:</strong> <?= htmlspecialchars($order['transaction_id']) ?><br>
                        <?php endif; ?>
                        <strong>Total Amount:</strong> ₹<?= number_format($order['total_amount'], 2) ?>
                    </p>
                </div>
            </div>

            <h5>Items Ordered</h5>
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                        alt="<?= htmlspecialchars($item['item_name']) ?>"
                                        width="50" class="me-2">
                                    <div>
                                        <?= htmlspecialchars($item['item_name']) ?>
                                        <div class="text-muted small">
                                            <?= $item['unit_quantity'] ?> <?= $item['unit_name'] ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>₹<?= number_format($item['unit_price'], 2) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>₹<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total:</th>
                        <th>₹<?= number_format($order['total_amount'], 2) ?></th>
                    </tr>
                </tfoot>
            </table>

            <div class="no-print mt-4">
                <button onclick="window.print()" class="btn btn-primary me-2">
                    <i class="bi bi-printer"></i> Print Receipt
                </button>
                <a href="emart.php?itemsPerPage=<?php echo $itemsPerPage; ?>&page=<?php echo $page; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>"
                    class="btn btn-outline-secondary">
                    <i class="bi bi-list-ul"></i> Continue Shopping
                </a>
            </div>

            <div class="print-only mt-4">
                <p>Thank you for your order!</p>
                <p>Order #: <?= htmlspecialchars($order['order_number']) ?></p>
                <p>Date: <?= date('M j, Y g:i A', strtotime($order['order_date'])) ?></p>
            </div>
        </div>
    </div>
</body>

</html>