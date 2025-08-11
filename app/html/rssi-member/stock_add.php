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

// Keep the units query:
$units = pg_fetch_all(pg_query($con, "SELECT unit_id as id, unit_name as text FROM stock_item_unit ORDER BY unit_name")) ?: [];

// Keep the item_units mapping as it's needed for validation
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
        /* Table styling */
        .stock-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 1rem;
        }

        .stock-table th {
            background-color: #f1f3f5;
            padding: 8px 12px;
            text-align: left;
            font-weight: 500;
            border-bottom: 1px solid #dee2e6;
        }

        .stock-table td {
            padding: 8px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }

        .stock-table tr:last-child td {
            border-bottom: none;
        }

        /* Form controls */
        .table-select {
            width: 100%;
            min-width: 150px;
            padding: 0.375rem 2.25rem 0.375rem 0.75rem;
        }

        .table-input {
            width: 80px;
            padding: 0.375rem 0.75rem;
        }

        /* Action buttons */
        .btn-action {
            width: 28px;
            height: 28px;
            padding: 0;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
        }

        .btn-delete:hover {
            background-color: #bb2d3b;
        }

        .btn-delete:disabled {
            background-color: #6c757d;
            opacity: 0.65;
        }

        /* Locked unit styling */
        .is-locked .select2-selection {
            background-color: #e9ecef !important;
            cursor: not-allowed !important;
        }

        .is-locked .select2-selection__arrow {
            display: none !important;
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
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Date Received</label>
                                            <input type="date" class="form-control" name="date_received" required value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Source</label>
                                            <select name="source" class="form-select" required>
                                                <option value="">Select Source</option>
                                                <option value="Donation">Donation</option>
                                                <option value="Purchased">Purchased</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="description" class="form-label">Description (Optional)</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                        </div>
                                    </div>

                                    <hr>

                                    <h5>Items</h5>
                                    <table class="stock-table">
                                        <thead>
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Unit</th>
                                                <th>Quantity</th>
                                                <th style="width: 40px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="items-container">
                                            <!-- Initial row -->
                                            <tr>
                                                <td>
                                                    <select name="item_id[]" class="form-select table-select item-select" required>
                                                        <option value="">Search and select item...</option> <!-- Changed from "Select Item" -->
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="unit_id[]" class="form-select table-select unit-select" required>
                                                        <option value="">Select Unit</option>
                                                        <?php foreach ($units as $unit): ?>
                                                            <option value="<?php echo $unit['id']; ?>"><?php echo $unit['text']; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" name="quantity_received[]" class="form-control table-input" min="0.01" step="0.01" required>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn-action btn-delete" disabled>
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <button type="button" id="add-item" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-plus-circle"></i> Add Row
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

    <!-- Bootstrap Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p id="loadingMessage">Submission in progress.
                            Please do not close or reload this page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Create a new Bootstrap modal instance with backdrop: 'static' and keyboard: false options
        const myModal = new bootstrap.Modal(document.getElementById("myModal"), {
            backdrop: 'static',
            keyboard: false
        });
        // Add event listener to intercept Escape key press
        document.body.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                // Prevent default behavior of Escape key
                event.preventDefault();
            }
        });
    </script>
    <script>
        // Function to show loading modal
        function showLoadingModal() {
            $('#myModal').modal('show');
        }

        // Function to hide loading modal
        function hideLoadingModal() {
            $('#myModal').modal('hide');
        }

        // Add event listener to form submission
        document.getElementById('stockForm').addEventListener('submit', function(event) {
            // Show loading modal when form is submitted
            showLoadingModal();
        });

        // Optional: Close loading modal when the page is fully loaded
        window.addEventListener('load', function() {
            // Hide loading modal
            hideLoadingModal();
        });
    </script>

    <script>
        $(document).ready(function() {
            const itemUnits = <?php echo json_encode($item_units); ?>;
            let selectedItems = new Set();

            // Initialize Select2 for units (since we have these locally)
            $('.unit-select').select2({
                width: '100%'
            });

            // Initialize Select2 for items with AJAX search
            function initializeItemSelect2(selectElement) {
                $(selectElement).select2({
                    width: '100%',
                    placeholder: 'Search and select item...',
                    allowClear: true,
                    minimumInputLength: 1,
                    ajax: {
                        url: 'search_products.php',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                search: params.term, // search term
                                add_stock: 'true' // our new flag
                            };
                        },
                        processResults: function(data) {
                            // Map the results to Select2 format
                            return {
                                results: data.results.map(item => ({
                                    id: item.id,
                                    text: item.name
                                }))
                            };
                        },
                        cache: true
                    },
                    templateResult: formatItem,
                    templateSelection: formatItemSelection
                });
            }

            // Format how items appear in the dropdown
            function formatItem(item) {
                if (item.loading) {
                    return item.text;
                }
                return $('<span>').text(item.text);
            }

            // Format how the selected item appears
            function formatItemSelection(item) {
                return item.text;
            }

            // Initialize existing item selects
            $('.item-select').each(function() {
                initializeItemSelect2(this);
                if ($(this).val()) {
                    selectedItems.add($(this).val());
                }
            });

            // Add new item row
            $('#add-item').click(function() {
                const newRow = $(`
            <tr>
                <td>
                    <select name="item_id[]" class="form-select table-select item-select" required>
                        <option value="">Search and select item...</option>
                    </select>
                </td>
                <td>
                    <select name="unit_id[]" class="form-select table-select unit-select" required>
                        <option value="">Select Unit</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?php echo $unit['id']; ?>"><?php echo $unit['text']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="number" name="quantity_received[]" class="form-control table-input" min="0.01" step="0.01" required>
                </td>
                <td>
                    <button type="button" class="btn-action btn-delete">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            </tr>
        `);

                $('#items-container').append(newRow);
                newRow.find('.unit-select').select2({
                    width: '100%'
                });

                // Initialize the new item select with AJAX search
                initializeItemSelect2(newRow.find('.item-select'));

                // Enable all delete buttons
                $('.btn-delete').prop('disabled', false);
            });

            // Remove row
            $(document).on('click', '.btn-delete', function() {
                const row = $(this).closest('tr');
                const itemSelect = row.find('.item-select');
                const itemId = itemSelect.val();

                if (itemId) {
                    selectedItems.delete(itemId);
                    updateItemAvailability();
                }

                if ($('#items-container tr').length > 1) {
                    row.remove();

                    // Disable delete button if only one row remains
                    if ($('#items-container tr').length === 1) {
                        $('.btn-delete').prop('disabled', true);
                    }
                }
            });

            // Handle item selection changes
            $(document).on('change', '.item-select', function() {
                const row = $(this).closest('tr');
                const previousValue = row.data('previous-item');
                const newValue = $(this).val();
                const unitSelect = row.find('.unit-select');
                const select2Container = unitSelect.next('.select2-container');

                // Update selected items set
                if (previousValue) {
                    selectedItems.delete(previousValue);
                }
                if (newValue) {
                    selectedItems.add(newValue);
                }
                row.data('previous-item', newValue);

                // Handle unit locking
                if (newValue && itemUnits[newValue]) {
                    // Lock the unit
                    unitSelect.val(itemUnits[newValue]).trigger('change');
                    select2Container.addClass('is-locked');
                    unitSelect.prop('disabled', false);
                    select2Container.find('.select2-selection').css('pointer-events', 'none');
                    unitSelect.on('select2:opening', function(e) {
                        e.preventDefault();
                    });
                } else {
                    // Unlock the unit
                    select2Container.removeClass('is-locked');
                    select2Container.find('.select2-selection').css('pointer-events', '');
                    unitSelect.off('select2:opening');
                }
            });

            // Update item availability in all dropdowns
            function updateItemAvailability() {
                // With AJAX search, we don't need to disable options in the dropdown
                // because each search is fresh and we can filter server-side
            }

            // Form validation
            $('#stockForm').on('submit', function(e) {
                let isValid = true;
                $('#items-container tr').each(function() {
                    const item = $(this).find('.item-select').val();
                    const unit = $(this).find('.unit-select').val();
                    const qty = $(this).find('.table-input').val();

                    if (!item || !unit || !qty) {
                        isValid = false;
                        $(this).addClass('table-danger');
                    } else {
                        $(this).removeClass('table-danger');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please complete all fields in each row before submitting.');
                }
            });
        });
    </script>
</body>

</html>