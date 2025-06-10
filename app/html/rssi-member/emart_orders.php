<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Fetch user's orders
$ordersQuery = "SELECT o.* FROM emart_orders o 
                --WHERE o.associatenumber = $1 
                ORDER BY o.order_date DESC";
//$ordersResult = pg_query_params($con, $ordersQuery, [$associatenumber]);
$ordersResult = pg_query($con, $ordersQuery);
$orders = pg_fetch_all($ordersResult);
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
                                <!-- <h2 class="mb-4">My Orders</h2> -->

                                <?php if (empty($orders)): ?>
                                    <div class="alert alert-info">
                                        You haven't placed any orders yet.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Payment Method</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($order['order_number']) ?></td>
                                                        <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                                                        <td>₹<?= number_format($order['total_amount'], 2) ?></td>
                                                        <td><?= ucfirst($order['payment_mode']) ?></td>
                                                        <td>
                                                            <span class="badge bg-success">Completed</span>
                                                        </td>
                                                        <td>
                                                            <a href="order_confirmation.php?id=<?= $order['order_id'] ?>"
                                                                class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-receipt"></i> View Receipt
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
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

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
</body>

</html>