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
    <!-- Your existing meta tags and scripts -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Stock</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <style>
        /* Improved locked unit styling */
        .select2-container--locked .select2-selection {
            background-color: #e9ecef !important;
            cursor: not-allowed !important;
            opacity: 1 !important;
        }

        .select2-container--locked .select2-selection__arrow {
            display: none !important;
        }

        /* Professional delete button styling */
        .btn-delete-row {
            width: 28px;
            height: 28px;
            padding: 0;
            border-radius: 50%;
            background-color: #dc3545;
            color: white;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            line-height: 1;
            transition: all 0.2s;
        }

        .btn-delete-row:hover {
            background-color: #bb2d3b;
            transform: scale(1.05);
        }

        .btn-delete-row:disabled {
            opacity: 0.5;
            background-color: #6c757d;
            cursor: not-allowed;
        }

        .item-row {
            margin-bottom: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 0.25rem;
            transition: all 0.2s;
        }

        .item-row:hover {
            background-color: #f1f3f5;
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
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <?php if ($_POST && !$success) { ?>
                                <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Error: <?php echo isset($error_message) ? $error_message : 'Something went wrong.'; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php } elseif ($_POST && $success) { ?>
                                <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Stock items successfully added.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <script>
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                </script>
                            <?php } ?>

                            <div class="container">
                                <form method="POST" id="stockForm">
                                    <div class="mb-3">
                                        <label for="date_received" class="form-label">Date Received</label>
                                        <input type="date" class="form-control" id="date_received" name="date_received" required value="<?php echo date('Y-m-d'); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="source" class="form-label">Source</label>
                                        <select id="source" name="source" class="form-select" required>
                                            <option value="">Select Source</option>
                                            <option value="Donation">Donation</option>
                                            <option value="Purchased">Purchased</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description (Optional)</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                    </div>

                                    <hr>

                                    <h5>Items</h5>
                                    <div id="items-container">
                                        <!-- Initial row -->
                                        <div class="item-row row g-3 align-items-end">
                                            <div class="col-md-5">
                                                <label class="form-label">Item Name</label>
                                                <select name="item_id[]" class="form-select item-select" required>
                                                    <option value="">Select Item</option>
                                                    <?php foreach ($items as $item): ?>
                                                        <option value="<?php echo $item['id']; ?>"><?php echo $item['text']; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Unit</label>
                                                <select name="unit_id[]" class="form-select unit-select" required>
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
                                            <div class="col-md-1 d-flex align-items-end justify-content-end">
                                                <button type="button" class="btn-delete-row" disabled>
                                                    ×
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" id="add-item" class="btn btn-outline-secondary mt-3">
                                        <i class="bi bi-plus-circle"></i> Add Another Item
                                    </button>

                                    <div class="text-center mt-4">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            const itemUnits = <?php echo json_encode($item_units); ?>;

            // Initialize Select2
            $('.item-select, .unit-select').select2({
                width: '100%'
            });

            // Add new item row
            $('#add-item').click(function() {
                const newRow = $(`
                    <div class="item-row row g-3 align-items-end mt-2">
                        <div class="col-md-5">
                            <select name="item_id[]" class="form-select item-select" required>
                                <option value="">Select Item</option>
                                <?php foreach ($items as $item): ?>
                                    <option value="<?php echo $item['id']; ?>"><?php echo $item['text']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="unit_id[]" class="form-select unit-select" required>
                                <option value="">Select Unit</option>
                                <?php foreach ($units as $unit): ?>
                                    <option value="<?php echo $unit['id']; ?>"><?php echo $unit['text']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" name="quantity_received[]" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end justify-content-end">
                            <button type="button" class="btn-delete-row">
                                ×
                            </button>
                        </div>
                    </div>
                `);

                $('#items-container').append(newRow);
                newRow.find('.item-select, .unit-select').select2({
                    width: '100%'
                });

                // Enable all delete buttons if more than one row exists
                if ($('.item-row').length > 1) {
                    $('.btn-delete-row').prop('disabled', false);
                }
            });

            // Remove row
            $(document).on('click', '.btn-delete-row:not(:disabled)', function() {
                if ($('.item-row').length > 1) {
                    $(this).closest('.item-row').remove();

                    // Disable delete button if only one row remains
                    if ($('.item-row').length === 1) {
                        $('.btn-delete-row').prop('disabled', true);
                    }
                }
            });

            // Handle item selection to lock units
            $(document).on('change', '.item-select', function() {
                const selectedItem = $(this).val();
                const row = $(this).closest('.item-row');
                const unitSelect = row.find('.unit-select');
                const select2Container = unitSelect.next('.select2-container');

                if (selectedItem && itemUnits[selectedItem]) {
                    // Lock the unit visually and functionally
                    unitSelect.val(itemUnits[selectedItem]).trigger('change');
                    select2Container.addClass('select2-container--locked');

                    // Prevent any changes
                    unitSelect.prop('disabled', false); // Keep enabled for submission
                    select2Container.find('.select2-selection').css('pointer-events', 'none');

                    // Special handling for Select2
                    unitSelect.on('select2:opening', function(e) {
                        e.preventDefault();
                    });
                } else {
                    // Unlock the unit
                    select2Container.removeClass('select2-container--locked');
                    select2Container.find('.select2-selection').css('pointer-events', '');
                    unitSelect.off('select2:opening');
                }
            });

            // Form validation
            $('#stockForm').on('submit', function(e) {
                let isValid = true;
                $('.item-row').each(function() {
                    const item = $(this).find('.item-select').val();
                    const unit = $(this).find('.unit-select').val();
                    const qty = $(this).find('input[type="number"]').val();

                    if (!item || !unit || !qty) {
                        isValid = false;
                        $(this).addClass('border border-danger');
                    } else {
                        $(this).removeClass('border border-danger');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please complete all fields in each item row before submitting.');
                }
            });
        });
    </script>
</body>

</html>