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
$query = "
SELECT 
    o.id AS order_id,
    o.order_by AS associate_number,
    m.fullname AS associate_name,
    oi.product_id,
    oi.id,
    oi.quantity,
    p.name AS product_name,
    p.price AS product_price,
    oi.status,
    oi.remarks,
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
ORDER BY 
    o.id ASC
";

$result = pg_query($con, $query);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entry_id'])) {
    $entryId = $_POST['entry_id'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'];

    $updateQuery = "
    UPDATE 
        order_items
    SET 
        status = $1,
        remarks = $2
    WHERE 
        id = $3
    ";

    $updateResult = pg_query_params($con, $updateQuery, [$status, $remarks, $entryId]);

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
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container my-5">
        <h1 class="mb-4">My Orders</h1>

        <!-- Year Filter Form -->
        <form method="GET" class="mb-4">
            <label for="year" class="form-label">Filter by Year:</label>
            <select name="year" id="year" class="form-select w-auto">
                <?php
                for ($i = 2020; $i <= date('Y'); $i++) {
                    echo "<option value='$i' " . ($yearFilter == $i ? 'selected' : '') . ">$i</option>";
                }
                ?>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Associate Name</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result): ?>
                    <?php while ($row = pg_fetch_assoc($result)): ?>
                        <tr>
                            <form method="POST">
                                <td><?php echo $row['order_id']; ?></td>
                                <td><?php echo $row['associate_name']; ?></td>
                                <td><?php echo $row['product_name']; ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo $row['product_price']; ?></td>
                                <td>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="Pending" <?php echo $row['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Shipped" <?php echo $row['status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="Delivered" <?php echo $row['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="Refunded" <?php echo $row['status'] === 'Refunded' ? 'selected' : ''; ?>>Refunded</option>
                                    </select>
                                </td>
                                <td>
                                <input type="text" name="remarks" class="form-control form-control-sm" value="<?php echo htmlspecialchars($row['remarks'] ?? ''); ?>">
                                </td>
                                <td>
                                    <input type="hidden" name="entry_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">Save</button>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
