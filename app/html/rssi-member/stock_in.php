<?php
require_once __DIR__ . "/../../bootstrap.php";

// Include necessary files and check login status
include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Query to fetch the total added and distributed counts
$query = "
    SELECT
    i.item_id,
    i.item_name,
    i.category,
    u.unit_id,
    u.unit_name,
    COALESCE((SELECT SUM(quantity_received) 
              FROM stock_add 
              WHERE item_id = i.item_id 
              AND unit_id = u.unit_id), 0) AS total_added_count,
    COALESCE((SELECT SUM(quantity_distributed) 
              FROM stock_out 
              WHERE item_distributed = i.item_id 
              AND unit = u.unit_id), 0) AS total_distributed_count,
    (COALESCE((SELECT SUM(quantity_received) 
              FROM stock_add 
              WHERE item_id = i.item_id 
              AND unit_id = u.unit_id), 0) 
     - 
     COALESCE((SELECT SUM(quantity_distributed) 
              FROM stock_out 
              WHERE item_distributed = i.item_id 
              AND unit = u.unit_id), 0)) AS in_stock
FROM 
    stock_item i
JOIN 
    stock_item_unit u 
ON 
    EXISTS (
        SELECT 1 
        FROM stock_add a 
        WHERE a.item_id = i.item_id 
        AND a.unit_id = u.unit_id
    )
ORDER BY 
    i.item_id, u.unit_id;
";
$result = pg_query($con, $query);

if (!$result) {
    echo "Error: " . pg_last_error($con);
    exit;
}

$stock_data = [];
while ($row = pg_fetch_assoc($result)) {
    $stock_data[] = $row;
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stock Overview</title>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>
    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Stock Overview</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Stock Management</a></li>
                    <li class="breadcrumb-item active">Stock Overview</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->
        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <div class="container my-5">
                                <div class="row justify-content-center">
                                    <div class="col-lg-12">
                                        <div class="table-responsive">
                                            <table class="table" id="table-id">
                                                <thead>
                                                    <tr>
                                                        <th>Item ID</th>
                                                        <th>Item Name</th>
                                                        <th>Category</th>
                                                        <th>Unit</th>
                                                        <th>Total Added Count</th>
                                                        <th>Total Distributed Count</th>
                                                        <th>In Stock</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($stock_data as $stock): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($stock['item_id']); ?></td>
                                                            <td><?php echo htmlspecialchars($stock['item_name']); ?></td>
                                                            <td><?php echo isset($stock['category']) && $stock['category'] !== '' ? htmlspecialchars($stock['category']) : null; ?></td>
                                                            <td><?php echo htmlspecialchars($stock['unit_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($stock['total_added_count']); ?></td>
                                                            <td><?php echo htmlspecialchars($stock['total_distributed_count']); ?></td>
                                                            <td><?php echo htmlspecialchars($stock['in_stock']); ?></td>
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
                </div>
            </div>
        </section>
    </main><!-- End #main -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($stock_data)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>