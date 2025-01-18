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
    p.price AS product_price,
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

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Date</th>
                    <th>Associate Name</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <?php if ($role === 'Admin'): ?>
                        <th>Action</th>
                    <?php endif; ?>
                    <th>Updated on</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result): ?>
                    <?php while ($row = pg_fetch_assoc($result)): ?>
                        <tr>
                            <form method="POST">
                                <td><?php echo $row['order_id']; ?>/<?php echo $row['id']; ?></td>
                                <td><?php echo date('d/m/Y h:i A', strtotime($row['order_date'])); ?></td>
                                <td><?php echo $row['associate_name']; ?></td>
                                <td><?php echo $row['product_name']; ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo $row['product_price']; ?></td>
                                <?php if ($role !== 'Admin'): ?>
                                    <td><?php echo $row['status']; ?></td>
                                    <td><?php echo $row['remarks']; ?></td>
                                <?php endif; ?>
                                <?php if ($role === 'Admin'): ?>
                                    <td>
                                        <select name="status" class="form-select form-select-sm" <?php echo in_array($row['status'], ['Delivered', 'Refunded']) ? 'disabled' : ''; ?>>
                                            <option value="Pending" <?php echo $row['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Shipped" <?php echo $row['status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="Delivered" <?php echo $row['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="Refunded" <?php echo $row['status'] === 'Refunded' ? 'selected' : ''; ?>>Refunded</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="remarks" class="form-control form-control-sm" value="<?php echo htmlspecialchars($row['remarks'] ?? ''); ?>"
                                            <?php echo in_array($row['status'], ['Delivered', 'Refunded']) ? 'disabled' : ''; ?>>
                                    </td>
                                    <td>
                                        <input type="hidden" name="entry_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm" <?php echo in_array($row['status'], ['Delivered', 'Refunded']) ? 'disabled' : ''; ?>>Save</button>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <?php if (!empty($row['updated_on'])): ?>
                                        <span class="text-muted">
                                            <?php echo date('d/m/Y h:i A', strtotime($row['updated_on'])); ?> by <?php echo htmlspecialchars($row['updated_by']); ?>
                                        </span>
                                    <?php endif; ?>
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