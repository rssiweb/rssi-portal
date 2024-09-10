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

validation();

if ($_POST) {
    $date = $_POST['date'];
    $items_distributed = $_POST['item_distributed']; // This will be an array
    $unit = htmlspecialchars($_POST['unit'], ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
    $quantity_distributed = $_POST['quantity_distributed'];
    $distributed_by = $associatenumber; // Assuming $associatenumber is set correctly
    $timestamp = date('Y-m-d H:i:s');

    $success = true;

    // Check if any items and recipients are selected
    if (!empty($items_distributed) && !empty($_POST['distributed_to'])) {
        foreach ($items_distributed as $item_distributed) {
            foreach ($_POST['distributed_to'] as $distributed_to) {
                // Generate a unique transaction_out_id for each row
                $transaction_out_id = uniqid();

                $query = "INSERT INTO stock_out (transaction_out_id, date, item_distributed, unit, description, quantity_distributed, distributed_to, distributed_by, timestamp)
                          VALUES ('$transaction_out_id', '$date', '$item_distributed', '$unit', '$description', '$quantity_distributed', '$distributed_to', '$distributed_by', '$timestamp')";

                $result = pg_query($con, $query);

                if (!$result) {
                    $success = false;
                    break 2; // Exit both loops on error
                }
            }
        }
    }
}

// Fetch items, units, and recipients for dropdowns
$item_query = "
    SELECT
        i.item_id,
        i.item_name,
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

$unit_query = "SELECT unit_id, unit_name FROM stock_item_unit";
$recipient_query = "
    SELECT associatenumber AS id, fullname AS name FROM rssimyaccount_members WHERE filterstatus = 'Active'
    UNION
    SELECT student_id AS id, studentname AS name FROM rssimyprofile_student WHERE filterstatus = 'Active'
";

$item_result = pg_query($con, $item_query);
$unit_result = pg_query($con, $unit_query);
$recipient_result = pg_query($con, $recipient_query);

$items = [];
$units = [];
$recipients = [];

while ($row = pg_fetch_assoc($item_result)) {
    $is_out_of_stock = $row['in_stock'] <= 0;
    $items[] = [
        'item_id' => $row['item_id'],
        'item_name' => $row['item_name'],
        'unit_id' => $row['unit_id'],
        'unit_name' => $row['unit_name'],
        'in_stock' => $row['in_stock'],
        'is_out_of_stock' => $is_out_of_stock
    ];
}

while ($row = pg_fetch_assoc($unit_result)) {
    $units[] = [
        'id' => $row['unit_id'],
        'text' => $row['unit_name']
    ];
}

while ($row = pg_fetch_assoc($recipient_result)) {
    $recipients[] = [
        'id' => $row['id'],
        'text' => $row['name'] . ' (' . $row['id'] . ')'
    ];
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
    <title>Distribute Stock</title>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <style>
        .readonly-select {
            pointer-events: none;
            /* Prevent user from interacting with the select box */
            background-color: #e9ecef;
            /* Optional: Change background color to indicate read-only state */
            color: #6c757d;
            /* Optional: Change text color to indicate read-only state */
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>
    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Distribute Stock</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Stock Management</a></li>
                    <li class="breadcrumb-item active">Distribute Stock</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->
        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <?php if ($_POST && !$success) { ?>
                                <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span>Error: Something went wrong.</span>
                                </div>
                            <?php } elseif ($_POST && $success) { ?>
                                <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-check2-circle"></i>
                                    <span>Stock items successfully distributed.</span>
                                </div>
                                <script>
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                </script>
                            <?php } ?>
                            <div class="container my-5">
                                <div class="row justify-content-center">
                                    <div class="col-lg-8">
                                        <form method="POST">
                                            <!-- Date -->
                                            <div class="mb-3">
                                                <label for="date" class="form-label">Date</label>
                                                <input type="date" class="form-control" id="date" name="date" required>
                                            </div>

                                            <!-- Item Distributed -->
                                            <div class="mb-3">
                                                <label for="item_distributed" class="form-label">Item Distributed</label>
                                                <select id="item_distributed" name="item_distributed[]" class="form-control" multiple="multiple" required>
                                                    <?php foreach ($items as $item): ?>
                                                        <option value="<?php echo htmlspecialchars($item['item_id']); ?>" data-unit="<?php echo htmlspecialchars($item['unit_id']); ?>" <?php echo $item['is_out_of_stock'] ? 'disabled' : ''; ?>>
                                                            <?php echo htmlspecialchars($item['item_name']); ?> - <?php echo htmlspecialchars($item['unit_name']); ?>
                                                            (<?php echo htmlspecialchars($item['in_stock']); ?> in stock)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Unit -->
                                            <div class="mb-3">
                                                <label for="unit" class="form-label">Unit</label>
                                                <select id="unit" name="unit" class="form-control readonly-select" required>
                                                    <option value="" disabled selected>Select Unit</option>
                                                    <?php foreach ($units as $unit): ?>
                                                        <option value="<?php echo htmlspecialchars($unit['id']); ?>"><?php echo htmlspecialchars($unit['text']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <!-- Help text to indicate the field is auto-populated -->
                                                <small class="form-text text-muted">This field is auto-populated based on your selection.</small>
                                            </div>


                                            <!-- Quantity Distributed -->
                                            <div class="mb-3">
                                                <label for="quantity_distributed" class="form-label">Quantity Distributed</label>
                                                <input type="number" class="form-control" id="quantity_distributed" name="quantity_distributed" required>
                                            </div>

                                            <!-- Distributed To -->
                                            <div class="mb-3">
                                                <label for="distributed_to" class="form-label">Distributed To</label>
                                                <select id="distributed_to" name="distributed_to[]" class="form-control" multiple="multiple" required>
                                                    <?php foreach ($recipients as $recipient): ?>
                                                        <option value="<?php echo htmlspecialchars($recipient['id']); ?>"><?php echo htmlspecialchars($recipient['text']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Description -->
                                            <div class="mb-3">
                                                <label for="description" class="form-label">Description (Optional)</label>
                                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                            </div>

                                            <div class="text-center">
                                                <button type="submit" class="btn btn-primary">Submit</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main><!-- End #main -->
    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#item_distributed').select2();
            $('#distributed_to').select2();

            // Track previously selected items
            let previousSelection = $('#item_distributed').val() || [];

            // Handle item selection change
            $('#item_distributed').on('change', function(event) {
                let selectedItems = $(this).val(); // Currently selected items
                let unitDropdown = $('#unit');
                let allUnits = {};

                // Gather all selected items' units from the previous selection
                previousSelection.forEach(itemId => {
                    let unitId = $(`#item_distributed option[value="${itemId}"]`).data('unit');
                    allUnits[unitId] = (allUnits[unitId] || 0) + 1;
                });

                // Get the newly selected item
                let newlySelectedItem = selectedItems.filter(item => !previousSelection.includes(item))[0];
                if (newlySelectedItem) {
                    let newItemUnit = $(`#item_distributed option[value="${newlySelectedItem}"]`).data('unit');

                    // Check if the newly selected item belongs to a different unit
                    if (Object.keys(allUnits).length > 0 && !allUnits.hasOwnProperty(newItemUnit)) {
                        alert('Please select items from the same unit only.');

                        // Remove the last selected item that triggered the alert
                        selectedItems = selectedItems.filter(item => item !== newlySelectedItem);
                        $(this).val(selectedItems).trigger('change');
                        return;
                    }
                }

                // Update the previous selection
                previousSelection = selectedItems;

                // Gather all selected items' units after the change
                selectedItems.forEach(itemId => {
                    let unitId = $(`#item_distributed option[value="${itemId}"]`).data('unit');
                    allUnits[unitId] = (allUnits[unitId] || 0) + 1;
                });

                // Auto-select unit if only one unit is selected
                if (Object.keys(allUnits).length === 1) {
                    let unitId = Object.keys(allUnits)[0];
                    unitDropdown.val(unitId).trigger('change');
                }
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#item_distributed').select2();
            $('#distributed_to').select2();

            // Handle item selection change
            $('#item_distributed').on('change', function() {
                let selectedItems = $(this).val(); // Currently selected items
                let unitDropdown = $('#unit');
                let allUnits = {};

                // Gather all selected items' units
                selectedItems.forEach(itemId => {
                    let unitId = $(`#item_distributed option[value="${itemId}"]`).data('unit');
                    allUnits[unitId] = (allUnits[unitId] || 0) + 1;
                });

                // If multiple units are selected, prevent further selection
                if (Object.keys(allUnits).length > 1) {
                    alert('Please select items from the same unit only.');

                    // Remove the last selected item that triggered the alert
                    let newlySelectedItem = selectedItems[selectedItems.length - 1];
                    selectedItems = selectedItems.filter(item => item !== newlySelectedItem);
                    $(this).val(selectedItems).trigger('change');
                    return;
                }

                // Auto-select unit if only one unit is selected
                if (Object.keys(allUnits).length === 1) {
                    let unitId = Object.keys(allUnits)[0];
                    unitDropdown.val(unitId).trigger('change');
                } else {
                    // If no items are selected, set the dropdown to "Select Unit"
                    unitDropdown.val('').trigger('change');
                }
            });
        });
    </script>
</body>

</html>