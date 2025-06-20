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
    $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
    $added_by = $associatenumber;
    $timestamp = date('Y-m-d H:i:s');

    $success = true;
    $error_message = '';

    // Process each item row
    foreach ($_POST['item_id'] as $index => $item_id) {
        $unit_id = $_POST['unit_id'][$index];
        $quantity_received = $_POST['quantity_received'][$index];

        // Validate unit consistency for existing items
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

                $success = false;
                $error_message = "Item '$item_name' was previously added with unit '$unit_name'. Please use the same unit.";
                break;
            }
        }

        // Insert the record
        $transaction_id = uniqid();
        $result = pg_query_params(
            $con,
            "INSERT INTO stock_add (transaction_id, date_received, source, item_id, unit_id, description, quantity_received, timestamp, added_by)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)",
            [$transaction_id, $date_received, $source, $item_id, $unit_id, $description, $quantity_received, $timestamp, $added_by]
        );

        if (!$result) {
            $success = false;
            $error_message = "Error inserting record for item ID $item_id";
            break;
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

        .item-row {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background-color: #f8f9fa;
        }

        .remove-row {
            color: #dc3545;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .remove-row:hover {
            color: #bb2d3b;
        }
    </style>
    <style>
        .locked-unit {
            /* This class is for JavaScript to identify locked units */
        }

        .select2-container--default .select2-selection--single.locked-unit {
            background-color: #e9ecef !important;
            cursor: not-allowed !important;
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
                                    <div class="col-lg-10">
                                        <form method="POST" enctype="multipart/form-data" id="stockForm">
                                            <!-- Date Received -->
                                            <div class="mb-3">
                                                <label for="date_received" class="form-label">Date Received</label>
                                                <input type="date" class="form-control" id="date_received" name="date_received" required value="<?php echo date('Y-m-d'); ?>">
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

                                            <!-- Description -->
                                            <div class="mb-3">
                                                <label for="description" class="form-label">Description (Optional)</label>
                                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                            </div>

                                            <hr>

                                            <!-- Items Section -->
                                            <div class="mb-3">
                                                <h5>Items</h5>
                                                <div id="items-container">
                                                    <!-- First row will be added by default -->
                                                    <div class="item-row" data-row="0">
                                                        <div class="row">
                                                            <div class="col-md-5">
                                                                <label class="form-label">Item Name</label>
                                                                <select name="item_id[]" class="form-control item-select" required>
                                                                    <option value="">Select Item</option>
                                                                    <?php foreach ($items as $item): ?>
                                                                        <option value="<?php echo $item['id']; ?>"><?php echo $item['text']; ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Unit</label>
                                                                <select name="unit_id[]" class="form-control unit-select" required>
                                                                    <option value="">Select Unit</option>
                                                                    <?php foreach ($units as $unit): ?>
                                                                        <option value="<?php echo $unit['id']; ?>"><?php echo $unit['text']; ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label">Quantity</label>
                                                                <input type="number" name="quantity_received[]" class="form-control" min="1" required>
                                                            </div>
                                                            <div class="col-md-1 d-flex align-items-end">
                                                                <span class="remove-row"><i class="bi bi-trash"></i></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="button" id="add-item" class="btn btn-secondary mt-2">
                                                    <i class="bi bi-plus-circle"></i> Add Another Item
                                                </button>
                                            </div>

                                            <div class="text-center mt-4">
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
            // Convert PHP item_units to JS object
            const itemUnits = <?php echo json_encode($item_units); ?>;
            let rowCount = 1;

            // Initialize the first row
            initRow($('.item-row'));

            // Add new item row
            $('#add-item').click(function() {
                const newRow = $(`
                <div class="item-row" data-row="${rowCount}">
                    <div class="row">
                        <div class="col-md-5">
                            <label class="form-label">Item Name</label>
                            <select name="item_id[]" class="form-control item-select" required>
                                <option value="">Select Item</option>
                                <?php foreach ($items as $item): ?>
                                    <option value="<?php echo $item['id']; ?>"><?php echo $item['text']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit</label>
                            <select name="unit_id[]" class="form-control unit-select" required>
                                <option value="">Select Unit</option>
                                <?php foreach ($units as $unit): ?>
                                    <option value="<?php echo $unit['id']; ?>"><?php echo $unit['text']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity_received[]" class="form-control qty-input" min="1" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <span class="remove-row"><i class="bi bi-trash"></i></span>
                        </div>
                    </div>
                </div>
            `);

                $('#items-container').append(newRow);
                initRow(newRow);
                rowCount++;
            });

            // Remove row
            $(document).on('click', '.remove-row', function() {
                if ($('.item-row').length > 1) {
                    $(this).closest('.item-row').remove();
                } else {
                    // Reset the single remaining row instead of removing it
                    resetRow($(this).closest('.item-row'));
                }
            });

            // Function to reset a row's unit and quantity fields
            function resetRow(row) {
                const unitSelect = row.find('.unit-select');
                const qtyInput = row.find('.qty-input');

                unitSelect.val('').trigger('change').removeClass('locked-unit');
                qtyInput.val('');

                // Reset Select2 styling and enable interactions
                unitSelect.next('.select2-container').find('.select2-selection').css({
                    'background-color': '',
                    'cursor': ''
                });
                unitSelect.off('select2:opening');
            }

            // Initialize a row (item select and unit handling)
            function initRow(row) {
                const itemSelect = row.find('.item-select');
                const unitSelect = row.find('.unit-select');

                // Initialize Select2 for item select
                itemSelect.select2({
                    placeholder: "Select an item",
                    allowClear: true,
                    width: '100%'
                }).on('change', function() {
                    const selectedItem = $(this).val();
                    const row = $(this).closest('.item-row');
                    const unitSelect = row.find('.unit-select');
                    const qtyInput = row.find('.qty-input');

                    if (!selectedItem) {
                        // Item was cleared - immediately reset unit and quantity
                        resetRow(row);
                        return;
                    }

                    if (itemUnits[selectedItem]) {
                        // Item has a predefined unit - lock it visually
                        unitSelect.val(itemUnits[selectedItem]).trigger('change');
                        unitSelect.addClass('locked-unit');

                        // Style to look disabled
                        unitSelect.next('.select2-container').find('.select2-selection').css({
                            'background-color': '#e9ecef',
                            'cursor': 'not-allowed'
                        });

                        // Prevent opening dropdown
                        unitSelect.on('select2:opening', function(e) {
                            e.preventDefault();
                        });
                    } else {
                        // New item - allow selection
                        unitSelect.removeClass('locked-unit');
                        unitSelect.next('.select2-container').find('.select2-selection').css({
                            'background-color': '',
                            'cursor': ''
                        });
                        unitSelect.off('select2:opening');
                    }
                });

                // Initialize Select2 for unit select
                unitSelect.select2({
                    placeholder: "Select unit",
                    width: '100%'
                });
            }

            // Form validation before submission
            $('#stockForm').on('submit', function(e) {
                let valid = true;

                // Check each row for completeness
                $('.item-row').each(function() {
                    const itemSelect = $(this).find('.item-select');
                    const unitSelect = $(this).find('.unit-select');
                    const quantityInput = $(this).find('.qty-input');

                    if (!itemSelect.val() || !unitSelect.val() || !quantityInput.val()) {
                        valid = false;
                        $(this).css('border-color', '#dc3545');
                    } else {
                        $(this).css('border-color', '#dee2e6');
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    alert('Please complete all fields in each item row before submitting.');
                }

                return valid;
            });
        });
    </script>
</body>

</html>