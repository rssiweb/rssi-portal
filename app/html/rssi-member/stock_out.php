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
    $items_distributed = $_POST['item_distributed']; // Array of item IDs
    $units = $_POST['unit']; // Array of unit IDs
    $quantities_distributed = $_POST['quantity_distributed']; // Array of quantities
    $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
    $distributed_by = $associatenumber;
    $timestamp = date('Y-m-d H:i:s');

    $success = true;

    // Check if any items and recipients are selected
    if (!empty($items_distributed) && !empty($_POST['distributed_to'])) {
        // Loop through each item
        foreach ($items_distributed as $index => $item_distributed) {
            $unit = $units[$index];
            $quantity_distributed = $quantities_distributed[$index];

            // Loop through each recipient for this item
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

// Fetch units for dropdowns
$unit_query = "SELECT unit_id, unit_name FROM stock_item_unit";
$unit_result = pg_query($con, $unit_query);
$units = [];

while ($row = pg_fetch_assoc($unit_result)) {
    $units[] = [
        'id' => $row['unit_id'],
        'text' => $row['unit_name']
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <style>
        .bag-container {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .empty-bag-message {
            color: #6c757d;
            text-align: center;
            padding: 20px;
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
        </div>
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
                                        <form method="POST" id="distributionForm">
                                            <!-- Date -->
                                            <div class="mb-3">
                                                <label for="date" class="form-label">Date</label>
                                                <input type="date" class="form-control" id="date" name="date" required>
                                            </div>

                                            <!-- Distributed To -->
                                            <div class="mb-3">
                                                <label for="distributed_to" class="form-label">Distributed To</label>
                                                <select id="distributed_to" name="distributed_to[]" class="form-control" multiple="multiple" required></select>
                                            </div>

                                            <!-- Description -->
                                            <div class="mb-3">
                                                <label for="description" class="form-label">Description (Optional)</label>
                                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                            </div>

                                            <hr class="my-4">

                                            <h5 class="mb-3">Items to Distribute</h5>

                                            <!-- New Item Form -->
                                            <div class="row g-3 mb-4">
                                                <div class="col-md-5">
                                                    <label for="new_item" class="form-label">Item</label>
                                                    <select id="new_item" class="form-control">
                                                        <option value="">Select Item</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="new_unit" class="form-label">Unit</label>
                                                    <input type="text" id="new_unit" class="form-control" readonly>
                                                    <input type="hidden" id="new_unit_id">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="new_quantity" class="form-label">Quantity</label>
                                                    <input type="number" step="any" class="form-control" id="new_quantity" min="0.01">
                                                </div>
                                                <div class="col-md-1 d-flex align-items-end">
                                                    <button type="button" class="btn btn-primary" id="addItemBtn">Add</button>
                                                </div>
                                            </div>

                                            <!-- Items Bag -->
                                            <h5 class="mb-3">Items Bag</h5>
                                            <div class="bag-container">
                                                <div id="itemsBag">
                                                    <div class="empty-bag-message">No items added yet</div>
                                                </div>
                                            </div>

                                            <div class="text-center mt-4">
                                                <button type="submit" class="btn btn-primary">Distribute Items</button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for beneficiaries
            $('#distributed_to').select2({
                ajax: {
                    url: 'search_beneficiaries.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            isStockout: true
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1,
                placeholder: 'Select beneficiary(s)',
                allowClear: true,
                multiple: true
            });

            // Initialize Select2 for items
            $('#new_item').select2({
                ajax: {
                    url: 'search_products.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term,
                            for_stock_management: true,
                        };
                    },
                    processResults: function(data) {
                        var items = data.products || data.results || [];
                        return {
                            results: $.map(items, function(item) {
                                return {
                                    id: item.id || item.item_id,
                                    text: (item.name || item.item_name) + ' - ' + (item.unit_name || '') + ' (' + (item.in_stock || 0) + ' in stock)',
                                    unitId: item.unit_id,
                                    unitName: item.unit_name,
                                    inStock: item.in_stock,
                                    disabled: item.soldOut || (item.in_stock <= 0)
                                };
                            })
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1,
                placeholder: 'Select an item',
                templateResult: function(item) {
                    if (item.loading) return item.text;
                    var $container = $('<div>' + item.text + '</div>');
                    if (item.disabled) $container.addClass('text-muted');
                    return $container;
                }
            });

            // Track items in the bag
            let itemsBag = [];

            // When an item is selected
            $('#new_item').on('change', function() {
                const selectedItem = $(this).select2('data')[0];
                if (selectedItem) {
                    $('#new_unit').val(selectedItem.unitName);
                    $('#new_unit_id').val(selectedItem.unitId);
                    $('#new_quantity').val('');
                    $('#new_quantity').attr('max', selectedItem.inStock);
                }
            });

            // Add item to bag
            $('#addItemBtn').on('click', function() {
                const selectedItem = $('#new_item').select2('data')[0];
                if (!selectedItem) {
                    alert('Please select an item');
                    return;
                }

                const quantity = $('#new_quantity').val();
                if (!quantity) {
                    alert('Please enter quantity');
                    return;
                }

                if (parseFloat(quantity) > selectedItem.inStock) {
                    alert('Quantity cannot exceed available stock');
                    return;
                }

                // Generate unique ID for this item in the bag
                const itemUniqueId = 'item_' + Date.now() + Math.floor(Math.random() * 1000);

                // Add to itemsBag array with complete display text
                itemsBag.push({
                    id: itemUniqueId,
                    itemId: selectedItem.id,
                    displayText: selectedItem.text.split(' -')[0], // Name + Unit without stock info
                    quantity: quantity,
                    unitName: selectedItem.unitName,
                    unitId: selectedItem.unitId
                });

                updateBagDisplay();
                $('#new_item').val('').trigger('change');
                $('#new_quantity').val('');
            });

            // Remove item from bag
            $(document).on('click', '.remove-item-btn', function() {
                const itemId = $(this).data('item-id');
                itemsBag = itemsBag.filter(item => item.id !== itemId);
                updateBagDisplay();
            });

            // Update the bag display with minimalist format
            function updateBagDisplay() {
                const bagContainer = $('#itemsBag');
                bagContainer.empty();

                // Add item count header
                const itemCount = itemsBag.length;
                bagContainer.append(`<div class="bag-header mb-2">Items in Bag (${itemCount})</div>`);

                if (itemCount === 0) {
                    bagContainer.append('<div class="empty-bag-message">No items added yet</div>');
                    return;
                }

                itemsBag.forEach((item, index) => {
                    const itemRow = `
                    <div class="item-row" data-item-id="${item.id}">
                        <div class="d-flex justify-content-between align-items-center py-2 ${index !== itemsBag.length - 1 ? 'border-bottom' : ''}">
                            <div class="item-info">
                                ${item.displayText} - ${item.quantity} ${item.unitName}
                                <input type="hidden" name="item_distributed[]" value="${item.itemId}">
                                <input type="hidden" name="unit[]" value="${item.unitId}">
                                <input type="hidden" name="quantity_distributed[]" value="${item.quantity}">
                            </div>
                            <button type="button" class="btn btn-outline-danger remove-item-btn" 
                                    data-item-id="${item.id}">
                                <i class="bi bi-bag-x"></i>
                            </button>
                        </div>
                    </div>
                `;
                    bagContainer.append(itemRow);
                });
            }

            // Form submission validation
            $('#distributionForm').on('submit', function(e) {
                if (itemsBag.length === 0) {
                    alert('Please add at least one item to distribute');
                    e.preventDefault();
                    return false;
                }
                return true;
            });

            // Set default date to today
            $('#date').val(new Date().toISOString().split('T')[0]);
        });
    </script>
</body>

</html>