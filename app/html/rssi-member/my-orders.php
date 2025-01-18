<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$yearFilter = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Base query
$query = "
SELECT 
    o.id AS order_id,
    o.order_date AS order_date,
    o.order_by AS associate_number,
    m.fullname AS associate_name,
    oi.product_id,
    oi.id,
    oi.quantity,
    p.name AS product_name,
    oi.product_points AS product_price,
    oi.status,
    oi.remarks,
    oi.updated_on,
    oi.updated_by,
    EXTRACT(YEAR FROM o.order_date) AS order_year
FROM 
    orders o
JOIN 
    order_items oi ON o.id = oi.order_id
JOIN 
    products p ON oi.product_id = p.id
JOIN 
    rssimyaccount_members m ON o.order_by = m.associatenumber
WHERE 
    EXTRACT(YEAR FROM o.order_date) = $yearFilter
";

// Append condition based on role
if ($role !== 'Admin') {
    $query .= " AND o.order_by = '" . pg_escape_string($con, $associatenumber) . "'";
}

// Order by clause
$query .= " ORDER BY order_date DESC";

$result = pg_query($con, $query);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entry_id'])) {
    $entryId = $_POST['entry_id'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'];
    $updated_by = $user_check;
    $updated_on = date('Y-m-d H:i:s'); // Format: YYYY-MM-DD HH:MM:SS

    $updateQuery = "
    UPDATE 
        order_items
    SET 
        status = $1,
        remarks = $2,
        updated_by = $4,
        updated_on = $5
    WHERE 
        id = $3
    ";

    $updateResult = pg_query_params($con, $updateQuery, [$status, $remarks, $entryId, $updated_by, $updated_on]);

    if ($updateResult) {
        echo "<script>alert('Order updated successfully.');
        if (window.history.replaceState) {
                        // Update the URL without causing a page reload or resubmission
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload(); // Trigger a page reload to reflect changes
                    </script>";
    } else {
        echo "<script>alert('Failed to update order.');</script>";
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-11316670180');
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>My Orders</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }
    </style>
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>My Orders</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Rewards & Recognition</a></li>
                    <li class="breadcrumb-item active">My Orders</li>
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
                            <div class="container my-5">
                                <!-- Year Filter Form -->
                                <div class="d-flex justify-content-end mb-4">
                                    <form method="GET" class="d-flex align-items-center">
                                        <label for="year" class="form-label me-2">Filter by Year:</label>
                                        <select name="year" id="year" class="form-select w-auto me-2">
                                            <?php
                                            // Generate last 5 years, including the current year, in descending order
                                            for ($i = date('Y'); $i >= date('Y') - 4; $i--) {
                                                echo "<option value='$i' " . ($yearFilter == $i ? 'selected' : '') . ">$i</option>";
                                            }
                                            ?>
                                        </select>
                                        <button type="submit" class="btn btn-primary">Filter</button>
                                    </form>
                                </div>
                                <div class="container mt-4">
                                    <?php if ($result && pg_num_rows($result) > 0): ?>
                                        <?php while ($row = pg_fetch_assoc($result)): ?>
                                            <div class="card mb-3 shadow-sm">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between">
                                                        <div>
                                                            <h5 class="card-title">Order ID: <?php echo $row['order_id']; ?>/<?php echo $row['id']; ?></h5>
                                                            <p class="text-muted mb-1">Order Date: <?php echo date('d/m/Y h:i A', strtotime($row['order_date'])); ?></p>
                                                            <p class="mb-1">Associate: <strong><?php echo $row['associate_name']; ?></strong></p>
                                                        </div>
                                                        <div>
                                                            <p class="mb-1"><strong>Status:</strong> <?php echo $row['status']; ?></p>
                                                            <p class="mb-1"><strong>Updated On:</strong>
                                                                <?php if (!empty($row['updated_on'])): ?>
                                                                    <span class="text-muted"><?php echo date('d/m/Y h:i A', strtotime($row['updated_on'])); ?></span>
                                                                    <br>by <?php echo htmlspecialchars($row['updated_by']); ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">N/A</span>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p class="mb-1"><strong>Product:</strong> <?php echo $row['product_name']; ?></p>
                                                            <p class="mb-1"><strong>Quantity:</strong> <?php echo $row['quantity']; ?></p>
                                                            <p class="mb-1"><strong>Price:</strong> ₹<?php echo $row['product_price']; ?></p>
                                                        </div>
                                                        <?php if ($role === 'Admin'): ?>
                                                            <div class="col-md-6">
                                                                <form method="POST" class="d-flex flex-column align-items-start">
                                                                    <label for="status" class="form-label">Update Status</label>
                                                                    <select name="status" class="form-select form-select-sm mb-2 w-auto" <?php echo in_array($row['status'], ['Delivered', 'Refunded']) ? 'disabled' : ''; ?>>
                                                                        <option value="Pending" <?php echo $row['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="Shipped" <?php echo $row['status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                                        <option value="Delivered" <?php echo $row['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                                        <option value="Refunded" <?php echo $row['status'] === 'Refunded' ? 'selected' : ''; ?>>Refunded</option>
                                                                    </select>
                                                                    <label for="remarks" class="form-label">Remarks</label>
                                                                    <textarea name="remarks" class="form-control form-control-sm mb-2" <?php echo in_array($row['status'], ['Delivered', 'Refunded']) ? 'disabled' : ''; ?>><?php echo htmlspecialchars($row['remarks'] ?? ''); ?></textarea>
                                                                    <input type="hidden" name="entry_id" value="<?php echo $row['id']; ?>">
                                                                    <button type="submit" class="btn btn-success btn-sm" <?php echo in_array($row['status'], ['Delivered', 'Refunded']) ? 'disabled' : ''; ?>>Save</button>
                                                                </form>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="alert alert-warning">No records found.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
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