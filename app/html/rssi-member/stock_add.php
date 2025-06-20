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
    $date_received = $_POST['date_received'];
    $source = htmlspecialchars($_POST['source'], ENT_QUOTES, 'UTF-8');
    $unit_id = $_POST['unit_id'];
    $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
    $quantity_received = $_POST['quantity_received'];
    $added_by = $associatenumber;
    $timestamp = date('Y-m-d H:i:s');

    // Server-side validation: Check if any item already exists with a different unit in stock_add
    $validation_errors = [];
    foreach ($_POST['item_id'] as $item_id) {
        // Get the most recently used unit for this item from stock_add
        $check_query = "SELECT unit_id FROM stock_add 
                   WHERE item_id = '$item_id' AND unit_id IS NOT NULL 
                   ORDER BY timestamp DESC LIMIT 1";
        $check_result = pg_query($con, $check_query);

        if ($check_result && pg_num_rows($check_result) > 0) {
            $existing_unit = pg_fetch_assoc($check_result)['unit_id'];
            if ($existing_unit && $existing_unit != $unit_id) {
                // Get item name for error message
                $item_name_query = "SELECT item_name FROM stock_item WHERE item_id = '$item_id'";
                $item_name_result = pg_query($con, $item_name_query);
                $item_name = pg_fetch_assoc($item_name_result)['item_name'];

                // Get unit name for error message
                $unit_name_query = "SELECT unit_name FROM stock_item_unit WHERE unit_id = '$existing_unit'";
                $unit_name_result = pg_query($con, $unit_name_query);
                $unit_name = pg_fetch_assoc($unit_name_result)['unit_name'];

                $validation_errors[] = "Item '$item_name' was previously added with unit '$unit_name'. Please use the same unit for consistency.";
            }
        }
    }

    if (!empty($validation_errors)) {
        $success = false;
        $error_message = implode("<br>", $validation_errors);
    } else {
        // Proceed with insertion if validation passes
        $success = true;
        foreach ($_POST['item_id'] as $item_id) {
            $transaction_id = uniqid();
            $query = "INSERT INTO stock_add (transaction_id, date_received, source, item_id, unit_id, description, quantity_received, timestamp, added_by)
                      VALUES ('$transaction_id', '$date_received', '$source', '$item_id', '$unit_id', '$description', '$quantity_received', '$timestamp', '$added_by')";

            $result = pg_query($con, $query);
            if (!$result) {
                $success = false;
                break;
            }

            // Update the item's unit if it wasn't set before
            $update_query = "UPDATE stock_item SET unit_id = '$unit_id' WHERE item_id = '$item_id' AND unit_id IS NULL";
            pg_query($con, $update_query);
        }
    }
}

// Fetch items and units for dropdowns
$item_query = "SELECT item_id, item_name FROM stock_item ORDER BY item_name";
$unit_query = "SELECT unit_id, unit_name FROM stock_item_unit ORDER BY unit_name";

$item_result = pg_query($con, $item_query);
$unit_result = pg_query($con, $unit_query);

$items = [];
$units = [];
$item_units = []; // To store existing item-unit mappings

// Get all items
while ($row = pg_fetch_assoc($item_result)) {
    $items[] = ['id' => $row['item_id'], 'text' => $row['item_name']];
}

// Get all units
while ($row = pg_fetch_assoc($unit_result)) {
    $units[] = ['id' => $row['unit_id'], 'text' => $row['unit_name']];
}

// Get the most recent unit used for each item from stock_add
$item_unit_query = "SELECT DISTINCT ON (item_id) item_id, unit_id 
                    FROM stock_add 
                    WHERE unit_id IS NOT NULL 
                    ORDER BY item_id, timestamp DESC";
$item_unit_result = pg_query($con, $item_unit_query);

while ($row = pg_fetch_assoc($item_unit_result)) {
    $item_units[$row['item_id']] = $row['unit_id'];
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

    <title>Add Stock</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <style>
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }

        .readonly-select {
            pointer-events: none;
            background-color: #e9ecef;
            color: #6c757d;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Add Stock</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Stock Management</a></li>
                    <li class="breadcrumb-item active">Add Stock</li>
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
                            <?php if ($_POST && !$success) { ?>
                                <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span>Error: <?php echo isset($error_message) ? $error_message : 'Something went wrong.'; ?></span>
                                </div>
                            <?php } elseif ($_POST && $success) { ?>
                                <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-check2-circle"></i>
                                    <span>Stock items successfully added.</span>
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
                                        <form method="POST" enctype="multipart/form-data" id="stockForm">
                                            <!-- Date Received -->
                                            <div class="mb-3">
                                                <label for="date_received" class="form-label">Date Received</label>
                                                <input type="date" class="form-control" id="date_received" name="date_received" required>
                                            </div>

                                            <!-- Source -->
                                            <div class="mb-3">
                                                <label for="source" class="form-label">Source</label>
                                                <select id="source" name="source" class="form-select" required>
                                                    <option value="">Select Source</option>
                                                    <option value="Donation">Donation</option>
                                                    <option value="Purchased">Purchased</option>
                                                </select>
                                            </div>

                                            <!-- Item Name -->
                                            <div class="mb-3">
                                                <label for="item_id" class="form-label">Item Name</label>
                                                <select id="item_id" name="item_id[]" class="form-control" multiple="multiple" required>
                                                    <?php foreach ($items as $item): ?>
                                                        <option value="<?php echo $item['id']; ?>"><?php echo $item['text']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Unit -->
                                            <div class="mb-3">
                                                <label for="unit_id" class="form-label">Unit</label>
                                                <select id="unit_id" name="unit_id" class="form-select" required>
                                                    <?php foreach ($units as $unit): ?>
                                                        <option value="<?php echo $unit['id']; ?>"><?php echo $unit['text']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div id="unitValidation" class="text-danger"></div>
                                            </div>

                                            <!-- Quantity Received -->
                                            <div class="mb-3">
                                                <label for="quantity_received" class="form-label">Quantity Received</label>
                                                <input type="number" class="form-control" id="quantity_received" name="quantity_received" required>
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
            // Initialize Select2 for item_id
            $('#item_id').select2({
                data: <?php echo json_encode($items); ?>,
                placeholder: "Select items",
                allowClear: true
            });

            // Convert PHP item_units to JS object
            const itemUnits = <?php echo json_encode($item_units); ?>;
            let lockedUnitId = null;
            let previouslySelectedItems = [];

            // When items are selected
            $('#item_id').on('change', function() {
                const selectedItems = $(this).val() || [];
                const unitSelect = $('#unit_id');
                const unitValidation = $('#unitValidation');

                // Reset validation and select state
                unitValidation.text('');
                unitSelect.removeClass('readonly-select');

                // Detect newly added items
                const newlyAddedItems = selectedItems.filter(item => !previouslySelectedItems.includes(item));

                // Check for any items with predefined units
                const allItemsWithUnits = selectedItems.filter(itemId => itemUnits[itemId]);

                if (allItemsWithUnits.length > 0) {
                    // Determine which unit to lock to (prioritize existing locked unit)
                    const requiredUnitId = lockedUnitId || itemUnits[allItemsWithUnits[0]];
                    const requiredUnitName = unitSelect.find(`option[value="${requiredUnitId}"]`).text();

                    // Check for conflicts in newly added items first
                    const newConflicts = newlyAddedItems.filter(itemId =>
                        itemUnits[itemId] && itemUnits[itemId] !== requiredUnitId
                    );

                    // If no new conflicts, check all items
                    const allConflicts = newConflicts.length > 0 ? newConflicts :
                        selectedItems.filter(itemId => itemUnits[itemId] && itemUnits[itemId] !== requiredUnitId);

                    if (allConflicts.length > 0) {
                        // Get the first conflicting item (prioritize newly added ones)
                        const conflictingItem = newConflicts[0] || allConflicts[0];
                        const itemName = $('#item_id').select2('data').find(item => item.id == conflictingItem).text;

                        // Show alert
                        alert(`${itemName} cannot be added because it uses a different unit.`);

                        // Remove only the conflicting item
                        const updatedSelection = selectedItems.filter(itemId => itemId !== conflictingItem);
                        $(this).val(updatedSelection).trigger('change');
                        previouslySelectedItems = updatedSelection;
                        return;
                    }

                    // Lock the unit to the required unit (readonly style)
                    lockedUnitId = requiredUnitId;
                    unitSelect.val(lockedUnitId).addClass('readonly-select');
                    unitValidation.text(`Unit is locked to ${requiredUnitName} for selected items`);
                } else {
                    // No predefined units - unlock the field
                    lockedUnitId = null;
                    unitSelect.removeClass('readonly-select');
                }

                previouslySelectedItems = selectedItems;
            });
        });
    </script>
</body>

</html>