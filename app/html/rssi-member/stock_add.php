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

    // Server-side validation
    $validation_errors = [];
    foreach ($_POST['item_id'] as $item_id) {
        // Get the most recently used unit for this item
        $check_query = "SELECT unit_id FROM stock_add 
                       WHERE item_id = '$item_id' AND unit_id IS NOT NULL 
                       ORDER BY timestamp DESC LIMIT 1";
        $check_result = pg_query($con, $check_query);

        if ($check_result && pg_num_rows($check_result) > 0) {
            $existing_unit = pg_fetch_assoc($check_result)['unit_id'];
            if ($existing_unit != $unit_id) {
                $item_name = pg_fetch_result(pg_query(
                    $con,
                    "SELECT item_name FROM stock_item WHERE item_id = '$item_id'"
                ), 0, 0);
                $unit_name = pg_fetch_result(pg_query(
                    $con,
                    "SELECT unit_name FROM stock_item_unit WHERE unit_id = '$existing_unit'"
                ), 0, 0);

                $validation_errors[] = "Item '$item_name' was previously added with unit '$unit_name'. Please use the same unit.";
            }
        }
    }

    if (!empty($validation_errors)) {
        $success = false;
        $error_message = implode("<br>", $validation_errors);
    } else {
        // Proceed with insertion
        $success = true;
        foreach ($_POST['item_id'] as $item_id) {
            $transaction_id = uniqid();
            $result = pg_query_params(
                $con,
                "INSERT INTO stock_add (transaction_id, date_received, source, item_id, unit_id, description, quantity_received, timestamp, added_by)
                 VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)",
                [$transaction_id, $date_received, $source, $item_id, $unit_id, $description, $quantity_received, $timestamp, $added_by]
            );

            if (!$result) {
                $success = false;
                break;
            }
        }
    }
}

// Fetch data for dropdowns
$items = pg_fetch_all(pg_query($con, "SELECT item_id as id, item_name as text FROM stock_item ORDER BY item_name")) ?: [];
$units = pg_fetch_all(pg_query($con, "SELECT unit_id as id, unit_name as text FROM stock_item_unit ORDER BY unit_name")) ?: [];

// Get existing item-unit mappings
$item_units = [];
$result = pg_query(
    $con,
    "SELECT DISTINCT ON (item_id) item_id, unit_id 
     FROM stock_add WHERE unit_id IS NOT NULL ORDER BY item_id, timestamp DESC"
);
while ($row = pg_fetch_assoc($result)) {
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
    </main>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>


    <script>
        $(document).ready(function() {
            const itemUnits = <?php echo json_encode($item_units); ?>;
            let lockedUnitId = null;
            let currentSelection = [];

            $('#item_id').select2({
                data: <?php echo json_encode($items); ?>,
                placeholder: "Select items",
                allowClear: true
            });

            $('#item_id').on('change', function() {
                const selectedItems = $(this).val() || [];
                const unitSelect = $('#unit_id');
                const unitValidation = $('#unitValidation');

                // Reset UI
                unitSelect.removeClass('readonly-select');
                unitValidation.text('');

                // Find newly added items
                const newItems = selectedItems.filter(id => !currentSelection.includes(id));

                // Check for unit conflicts
                let requiredUnit = lockedUnitId;
                let conflictFound = false;

                for (const itemId of newItems) {
                    if (itemUnits[itemId]) {
                        if (!requiredUnit) {
                            requiredUnit = itemUnits[itemId];
                        } else if (itemUnits[itemId] !== requiredUnit) {
                            conflictFound = true;
                            const itemName = $(this).select2('data').find(i => i.id == itemId).text;
                            const unitName = unitSelect.find(`option[value="${itemUnits[itemId]}"]`).text();

                            alert(`${itemName} uses ${unitName} and cannot be added with the current selection.`);

                            // Remove the conflicting item
                            $(this).val(selectedItems.filter(id => id !== itemId)).trigger('change');
                            return;
                        }
                    }
                }

                // Update locked state if needed
                if (requiredUnit) {
                    lockedUnitId = requiredUnit;
                    unitSelect.val(lockedUnitId).addClass('readonly-select');
                    const unitName = unitSelect.find('option:selected').text();
                    unitValidation.text(`Unit locked to: ${unitName}`);
                }

                currentSelection = selectedItems;
            });
        });
    </script>
</body>

</html>