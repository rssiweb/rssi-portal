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

                                            <!-- Add this below the "Items to Distribute" section -->
                                            <div class="mb-3">
                                                <label for="select_group" class="form-label">Or select from predefined groups</label>
                                                <div class="row g-2 align-items-center">
                                                    <div class="col-6">
                                                        <select id="select_group" class="form-select form-select-sm">
                                                            <option value="">Select Group</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6">
                                                        <button id="extractItemsBtn" class="btn btn-primary" disabled>
                                                            Extract Items
                                                        </button>
                                                    </div>
                                                    <!-- <div class="col-2">
                                                        <a href="group_management.php" target="_blank" title="Create a new group">Learn about Groups (create, use, and manage)</a>
                                                    </div> -->
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
        // GLOBAL bag shared by all components
        let itemsBag = [];

        // SHARED function to update the bag display
        function updateBagDisplay() {
            const bagContainer = $('#itemsBag');
            bagContainer.empty();

            const itemCount = itemsBag.length;
            bagContainer.append(`<div class="bag-header mb-2">Items in Bag (${itemCount})</div>`);

            if (itemCount === 0) {
                bagContainer.append('<div class="empty-bag-message">No items added yet</div>');
                return;
            }

            const itemsContainer = $('<div class="items-container"></div>');
            bagContainer.append(itemsContainer);

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
                        <button type="button" class="btn btn-outline-danger btn-sm remove-item-btn" 
                                data-item-id="${item.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;
                itemsContainer.append(itemRow);
            });
        }

        $(document).ready(function() {
            // Set today's date by default
            $('#date').val(new Date().toISOString().split('T')[0]);

            // Initialize Select2 for beneficiaries
            $('#distributed_to').select2({
                ajax: {
                    url: 'search_beneficiaries.php',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term,
                        isStockout: true
                    }),
                    processResults: data => ({
                        results: data.results
                    }),
                    cache: true
                },
                minimumInputLength: 1,
                placeholder: 'Select beneficiary(s)',
                allowClear: true,
                multiple: true
            });

            // Initialize Select2 for item selection
            $('#new_item').select2({
                ajax: {
                    url: 'search_products.php',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        search: params.term,
                        for_stock_management: true
                    }),
                    processResults: data => {
                        const items = data.products || data.results || [];
                        return {
                            results: $.map(items, item => ({
                                id: item.id || item.item_id,
                                text: (item.name || item.item_name) + ' - ' + (item.unit_name || '') + ' (' + (item.in_stock || 0) + ' in stock)',
                                unitId: item.unit_id,
                                unitName: item.unit_name,
                                inStock: item.in_stock,
                                disabled: item.soldOut || (item.in_stock <= 0)
                            }))
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1,
                placeholder: 'Select an item',
                templateResult: item => {
                    if (item.loading) return item.text;
                    const $container = $('<div>' + item.text + '</div>');
                    if (item.disabled) $container.addClass('text-muted');
                    return $container;
                }
            });

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

            // Add selected item to the bag
            $('#addItemBtn').on('click', function() {
                const selectedItem = $('#new_item').select2('data')[0];
                const quantity = parseFloat($('#new_quantity').val());

                // Basic validation
                if (!selectedItem) {
                    alert('Please select an item');
                    return;
                }

                if (!quantity || isNaN(quantity)) {
                    alert('Please enter a valid quantity');
                    return;
                }

                if (quantity <= 0) {
                    alert('Quantity must be greater than zero');
                    return;
                }

                if (quantity > selectedItem.inStock) {
                    alert(`Cannot add more than ${selectedItem.inStock} (available stock)`);
                    $('#new_quantity').val(selectedItem.inStock);
                    return;
                }

                // Check if item already exists in bag
                const existingItemIndex = itemsBag.findIndex(item =>
                    item.itemId == selectedItem.id && item.unitId == selectedItem.unitId
                );

                if (existingItemIndex !== -1) {
                    const newQuantity = itemsBag[existingItemIndex].quantity + quantity;

                    if (newQuantity > selectedItem.inStock) {
                        alert(`Total quantity (${newQuantity}) cannot exceed available stock (${selectedItem.inStock})`);
                        return;
                    }

                    const confirmUpdate = confirm(
                        `The item "${selectedItem.text.split(' -')[0]}" already exists in your bag.\n\n` +
                        `Do you want to update the quantity to ${newQuantity}?`
                    );

                    if (confirmUpdate) {
                        itemsBag[existingItemIndex].quantity = newQuantity;
                    } else {
                        return; // user cancelled update
                    }
                } else {
                    // Add new item
                    const itemUniqueId = 'item_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                    itemsBag.push({
                        id: itemUniqueId,
                        itemId: selectedItem.id,
                        displayText: selectedItem.text.split(' -')[0],
                        quantity: quantity,
                        unitName: selectedItem.unitName,
                        unitId: selectedItem.unitId,
                        maxStock: selectedItem.inStock
                    });
                }

                updateBagDisplay();
                // Reset all related inputs
                $('#new_item').val('').trigger('change');
                $('#new_quantity').val('');
                $('#new_unit').val('');
                $('#new_unit_id').val('');
            });

            // Remove item from bag
            $(document).on('click', '.remove-item-btn', function() {
                const itemId = $(this).data('item-id');
                itemsBag = itemsBag.filter(item => item.id !== itemId);
                updateBagDisplay();
            });

            // Form submission validation
            $('#distributionForm').on('submit', function(e) {
                if (itemsBag.length === 0) {
                    alert('Please add at least one item to distribute');
                    e.preventDefault();
                    return false;
                }
            });

            // GROUP SELECTION HANDLING
            const groupSelect = $('#select_group').select2({
                ajax: {
                    url: 'search_groups.php',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: data.results
                    }),
                    cache: true
                },
                minimumInputLength: 1,
                placeholder: 'Select a group',
                allowClear: true
            });

            $('#extractItemsBtn').prop('disabled', true);
            groupSelect.on('change', function() {
                $('#extractItemsBtn').prop('disabled', !$(this).val());
            });

            // Add "See Available Groups" button next to Extract button
            const extractBtn = $('#extractItemsBtn');
            extractBtn.after(`
            <button type="button" class="btn btn-outline-primary ms-2" id="viewGroupsBtn">
            See Available Groups
            </button>
        `);

            // View Groups Modal
            $('#viewGroupsBtn').on('click', function() {
                const modalHTML = `
                <div class="modal fade" id="groupsListModal" tabindex="-1" aria-labelledby="groupsListModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="groupsListModalLabel">Available Groups</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="d-flex justify-content-center align-items-center" style="height: 100px;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-3">Loading groups...</span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>`;

                if ($('#groupsListModal').length) $('#groupsListModal').remove();
                $('body').append(modalHTML);

                const modal = new bootstrap.Modal(document.getElementById('groupsListModal'));
                modal.show();

                // Load all groups without search term
                $.ajax({
                    url: 'search_groups.php',
                    dataType: 'json',
                    data: {
                        q: ''
                    }, // Empty search term to get all groups
                    success: function(data) {
                        if (data.results.length === 0) {
                            $('#groupsListModal .modal-body').html('<div class="alert alert-info">No groups found.</div>');
                            return;
                        }

                        const tableHTML = `
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.results.map(group => `
                                        <tr>
                                            <td>${group.text || 'N/A'}</td>
                                            <td>${group.description || 'No description'}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary select-group-btn" 
                                                    data-group-id="${group.id}" 
                                                    data-group-name="${group.text}">
                                                    <i class="bi bi-check-lg"></i> Select
                                                </button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>`;

                        $('#groupsListModal .modal-body').html(tableHTML);
                    },
                    error: function() {
                        $('#groupsListModal .modal-body').html('<div class="alert alert-danger">Failed to load groups. Please try again.</div>');
                    }
                });
            });

            // Handle group selection from modal
            $(document).on('click', '.select-group-btn', function() {
                const groupId = $(this).data('group-id');
                const groupName = $(this).data('group-name');

                // Set the selected group in the select2
                const newOption = new Option(groupName, groupId, true, true);
                groupSelect.append(newOption).trigger('change');

                // Close the modal
                bootstrap.Modal.getInstance(document.getElementById('groupsListModal')).hide();
            });

            // Extract group items
            $('#extractItemsBtn').on('click', function() {
                const groupId = $('#select_group').val();
                if (!groupId) return;

                const loadingSpinner = `
                <div class="d-flex justify-content-center align-items-center" style="height: 100px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-3">Loading group items...</span>
                </div>`;

                const modalHTML = `
                <div class="modal fade" id="groupItemsModal" tabindex="-1" aria-labelledby="groupItemsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="groupItemsModalLabel">Review Group Items</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">${loadingSpinner}</div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="confirmAddGroupToBag" disabled>Add to Bag</button>
                            </div>
                        </div>
                    </div>
                </div>`;

                if ($('#groupItemsModal').length) $('#groupItemsModal').remove();
                $('body').append(modalHTML);

                const modal = new bootstrap.Modal(document.getElementById('groupItemsModal'));
                modal.show();

                $.get('get_group_items.php', {
                        group_id: groupId,
                        with_stock: true
                    })
                    .done(data => {
                        let outOfStockCount = 0;
                        let totalItems = data.length;

                        const tableRows = data.map(item => {
                            const isOutOfStock = item.in_stock <= 0;
                            if (isOutOfStock) outOfStockCount++;

                            return `
                            <tr data-item-id="${item.item_id}" data-unit-id="${item.unit_id}" data-out-of-stock="${isOutOfStock}" data-max-stock="${item.in_stock}">
                                <td>
                                    ${item.item_name}
                                    ${isOutOfStock ? '<span class="text-danger ms-2">(Out of stock)</span>' : ''}
                                </td>
                                <td>
                                    <input type="text" class="form-control" value="${item.unit_name}" readonly>
                                    <input type="hidden" class="unit-id" value="${item.unit_id}">
                                </td>
                                <td>
                                    <input type="number" step="0.01" class="form-control quantity-input" 
                                           value="${item.quantity}" min="1" max="${item.in_stock}"
                                           ${isOutOfStock ? 'disabled' : ''} required>
                                    <div class="invalid-feedback">Please enter a valid quantity</div>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-group-item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>`;
                        }).join('');

                        const allOutOfStock = outOfStockCount === totalItems;
                        const someOutOfStock = outOfStockCount > 0;

                        const table = `
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Unit</th>
                                        <th>Quantity</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="groupItemsTableBody">
                                    ${tableRows}
                                </tbody>
                            </table>
                        </div>
                        <div id="outOfStockAlert" class="alert alert-warning" ${someOutOfStock ? '' : 'style="display:none"'}>
                            ${outOfStockCount} item(s) are out of stock. Please remove them or restock before adding to bag.
                        </div>
                        ${allOutOfStock ? `
                        <div class="alert alert-danger">
                            All items in this group are out of stock. Cannot add to bag.
                        </div>
                        ` : ''}`;

                        $('#groupItemsModal .modal-body').html(table);
                        validateGroupItems(); // Initial validation
                    })
                    .fail(() => {
                        $('#groupItemsModal .modal-body').html(`<div class="alert alert-danger">Failed to load group items. Please try again.</div>`);
                    });
            });

            // Validate inputs in real-time and enable/disable Add button accordingly
            function validateGroupItems() {
                let hasErrors = false;
                let hasValidItems = false;
                let outOfStockCount = 0;

                $('#groupItemsTableBody tr').each(function() {
                    const $row = $(this);
                    const isOutOfStock = $row.data('out-of-stock') === true;
                    const quantityInput = $row.find('.quantity-input');
                    const feedback = $row.find('.invalid-feedback');

                    if (isOutOfStock) {
                        outOfStockCount++;
                        return true; // continue to next item
                    }

                    const quantity = parseFloat(quantityInput.val());
                    const maxQuantity = parseFloat(quantityInput.attr('max'));
                    const itemName = $row.find('td:first').text().replace('(Out of stock)', '').trim();

                    if (isNaN(quantity)) {
                        feedback.text('Please enter a valid quantity').show();
                        hasErrors = true;
                    } else if (quantity < 0.01) {
                        feedback.text('Quantity must be greater than zero').show();
                        hasErrors = true;
                    } else if (quantity > maxQuantity) {
                        alert(`Cannot add more than ${maxQuantity} for "${itemName}" (available stock).`);
                        quantityInput.val(maxQuantity);
                        feedback.hide();
                    } else {
                        feedback.hide();
                        hasValidItems = true;
                    }
                });

                // Update out of stock alert
                const outOfStockAlert = $('#outOfStockAlert');
                if (outOfStockCount > 0) {
                    outOfStockAlert.show().html(`${outOfStockCount} item(s) are out of stock. Please remove them or restock before adding to bag.`);
                } else {
                    outOfStockAlert.hide();
                }

                // Enable Add button only if:
                // 1. There are valid items (not out of stock)
                // 2. No validation errors exist
                // 3. No out-of-stock items remain
                $('#confirmAddGroupToBag').prop('disabled', hasErrors || !hasValidItems || outOfStockCount > 0);
            }

            // Handle input changes for validation
            $(document).on('input', '.quantity-input', function() {
                validateGroupItems();
            });

            // Handle removing items from the modal
            $(document).on('click', '.remove-group-item', function() {
                $(this).closest('tr').remove();
                validateGroupItems(); // Revalidate after removal

                // If no items left, disable the Add button
                if ($('#groupItemsTableBody tr').length === 0) {
                    $('#confirmAddGroupToBag').prop('disabled', true);
                }
            });

            // Confirm add group items to bag
            $(document).on('click', '#confirmAddGroupToBag', function() {
                let itemsAdded = 0;
                let duplicateItems = [];
                let duplicatesToUpdate = [];

                // First pass: loop through and collect duplicates/new items
                $('#groupItemsTableBody tr').each(function() {
                    const $row = $(this);
                    const isOutOfStock = $row.data('out-of-stock');
                    if (isOutOfStock) return true; // Skip out-of-stock items

                    const itemId = $row.data('item-id');
                    const unitId = $row.data('unit-id');
                    const itemName = $row.find('td:first').text().replace('(Out of stock)', '').trim();
                    const unitName = $row.find('td:nth-child(2) input').val();
                    const quantity = parseFloat($row.find('.quantity-input').val());
                    const maxStock = parseFloat($row.data('max-stock'));

                    const existingItemIndex = itemsBag.findIndex(item =>
                        item.itemId == itemId && item.unitId == unitId
                    );

                    if (existingItemIndex !== -1) {
                        const newQuantity = itemsBag[existingItemIndex].quantity + quantity;

                        if (newQuantity > maxStock) {
                            alert(`Total quantity (${newQuantity}) for ${itemName} exceeds available stock (${maxStock}).`);
                            return false; // abort loop
                        }

                        // Store for later confirmation
                        duplicateItems.push(itemName);
                        duplicatesToUpdate.push({
                            index: existingItemIndex,
                            newQuantity: newQuantity
                        });
                    } else {
                        // Add new item directly
                        const itemUniqueId = 'item_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                        itemsBag.push({
                            id: itemUniqueId,
                            itemId,
                            displayText: itemName,
                            quantity,
                            unitName,
                            unitId,
                            maxStock
                        });
                        itemsAdded++;
                    }
                });

                // One-time confirmation for duplicates
                let proceedWithDuplicates = true;
                if (duplicateItems.length > 0) {
                    proceedWithDuplicates = confirm(
                        `The following items already exist in your bag:\n\n- ${duplicateItems.join('\n- ')}\n\nDo you want to update their quantities?`
                    );
                }

                if (proceedWithDuplicates) {
                    // Apply updates to duplicates
                    duplicatesToUpdate.forEach(update => {
                        itemsBag[update.index].quantity = update.newQuantity;
                        itemsAdded++;
                    });

                    if (itemsAdded > 0) {
                        updateBagDisplay();
                        bootstrap.Modal.getInstance(document.getElementById('groupItemsModal')).hide();
                        $('#select_group').val('').trigger('change');
                    }
                }
            });
        });
    </script>
</body>

</html>