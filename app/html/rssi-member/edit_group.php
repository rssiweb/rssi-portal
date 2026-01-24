<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

$group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_group'])) {
        // Update group info
        $group_name = pg_escape_string($con, $_POST['group_name']);
        $description = pg_escape_string($con, $_POST['description']);

        pg_query_params(
            $con,
            "UPDATE stock_item_groups SET group_name=$1, description=$2, updated_at=NOW(), updated_by='$associatenumber' WHERE group_id=$3",
            array($group_name, $description, $group_id)
        );

        // Handle item additions
        if (isset($_POST['item_id']) && is_array($_POST['item_id'])) {
            // First remove all existing items
            pg_query_params($con, "DELETE FROM stock_item_group_items WHERE group_id=$1", array($group_id));

            // Add new items
            foreach ($_POST['item_id'] as $index => $item_id) {
                $quantity = $_POST['quantity'][$index];
                $unit_id = $_POST['unit_id'][$index];

                if ($item_id && $quantity > 0 && $unit_id) {
                    pg_query_params(
                        $con,
                        "INSERT INTO stock_item_group_items (group_id, item_id, quantity, unit_id) VALUES ($1, $2, $3, $4)",
                        array($group_id, $item_id, $quantity, $unit_id)
                    );
                }
            }
        }

        $_SESSION['success_message'] = "Group updated successfully!";
        header("Location: group_management.php");
        exit;
    } elseif (isset($_POST['add_item'])) {
        // Add single item
        $item_id = $_POST['new_item_id'];
        $quantity = $_POST['new_quantity'];
        $unit_id = $_POST['new_unit_id'];

        if ($item_id && $quantity > 0 && $unit_id) {
            pg_query_params(
                $con,
                "INSERT INTO stock_item_group_items (group_id, item_id, quantity, unit_id) VALUES ($1, $2, $3, $4)",
                array($group_id, $item_id, $quantity, $unit_id)
            );
        }
    }
}

// Get group info
$group_result = pg_query_params($con, "SELECT * FROM stock_item_groups WHERE group_id=$1", array($group_id));
$group = pg_fetch_assoc($group_result);

if (!$group) {
    $_SESSION['error_message'] = "Group not found!";
    header("Location: group_management.php");
    exit;
}

// Get items in this group
$items_result = pg_query_params(
    $con,
    "SELECT gi.*, i.item_name, u.unit_name 
     FROM stock_item_group_items gi
     JOIN stock_item i ON gi.item_id = i.item_id
     JOIN stock_item_unit u ON gi.unit_id = u.unit_id
     WHERE gi.group_id = $1
     ORDER BY i.item_name",
    array($group_id)
);
$items = pg_fetch_all($items_result) ?: [];

// Get all units for dropdown
$units_result = pg_query($con, "SELECT * FROM stock_item_unit ORDER BY unit_name");
$units = pg_fetch_all($units_result) ?: [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Group: <?= htmlspecialchars($group['group_name']) ?></title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .items-header {
            /* font-weight: 600; */
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 0.75rem;
        }

        .item-row {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f3f5;
            align-items: center;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: 500;
        }

        .quantity-input {
            max-width: 100px;
        }

        .btn-remove {
            color: #dc3545;
            background-color: transparent;
            border: none;
            padding: 0.375rem;
        }

        .btn-remove:hover {
            color: #bb2d3b;
            background-color: #f8d7da;
        }

        .modal-content {
            border-radius: 10px;
        }

        .item-preview {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.5rem;
        }

        .preview-item {
            padding: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .preview-item:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>

                            <section class="section">
                                <div class="row justify-content-center">
                                    <div class="col-lg-10">
                                        <div class="card">
                                            <h5 class="card-header">Group Information</h5>
                                            <div class="card-body">
                                                <form method="POST" id="groupForm">
                                                    <input type="hidden" name="group_id" value="<?= $group_id ?>">

                                                    <div class="form-section mt-3">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <label for="group_name" class="form-label">Group Name</label>
                                                                <input type="text" class="form-control" id="group_name" name="group_name"
                                                                    value="<?= htmlspecialchars($group['group_name']) ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label for="description" class="form-label">Description</label>
                                                                <textarea class="form-control" id="description" name="description" rows="3"
                                                                    placeholder="Brief description of the group (optional)"><?= htmlspecialchars($group['description']) ?></textarea>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-section mt-4">
                                                        <div class="items-container" id="groupItems">
                                                            <?php if (empty($items)): ?>
                                                                <div class="text-center py-4">
                                                                    <i class="bi bi-inbox" style="font-size: 2rem; color: #adb5bd;"></i>
                                                                    <p class="text-muted mt-2">No items added yet</p>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="row items-header">
                                                                    <div class="col-md-5">Item Name</div>
                                                                    <div class="col-md-3">Quantity</div>
                                                                    <div class="col-md-3">Unit</div>
                                                                    <div class="col-md-1">Action</div>
                                                                </div>
                                                                <?php foreach ($items as $item):
                                                                    $unitName = '';
                                                                    foreach ($units as $unit) {
                                                                        if ($unit['unit_id'] == $item['unit_id']) {
                                                                            $unitName = htmlspecialchars($unit['unit_name']);
                                                                            break;
                                                                        }
                                                                    }
                                                                    // Format quantity display
                                                                    $quantityDisplay = (float)$item['quantity'] == (int)$item['quantity']
                                                                        ? (int)$item['quantity']
                                                                        : (float)$item['quantity'];
                                                                ?>
                                                                    <div class="row item-row">
                                                                        <div class="col-md-5">
                                                                            <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                                                                            <input type="hidden" name="item_id[]" value="<?= $item['item_id'] ?>">
                                                                            <input type="hidden" name="item_name[]" value="<?= htmlspecialchars($item['item_name']) ?>">
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <input type="number" step="0.01" class="form-control quantity-input" name="quantity[]"
                                                                                value="<?= $item['quantity'] ?>" min="0.01" required>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <?= $unitName ?>
                                                                            <input type="hidden" name="unit_id[]" value="<?= $item['unit_id'] ?>">
                                                                        </div>
                                                                        <div class="col-md-1">
                                                                            <button type="button" class="btn btn-remove remove-item">
                                                                                <i class="bi bi-trash"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="mt-4">
                                                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                                                <i class="bi bi-plus"></i> Add Items
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-between mt-4">
                                                        <a href="group_management.php" class="btn btn-outline-secondary">Cancel</a>
                                                        <button type="submit" name="update_group" class="btn btn-primary px-4">
                                                            <i class="bi bi-check-lg"></i> Save Changes
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <!-- Add Item Modal -->
                            <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="addItemModalLabel">Add Items to Group</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form id="addItemForm">
                                            <div class="modal-body">
                                                <div class="mb-4">
                                                    <label for="new_item_search" class="form-label">Search Items</label>
                                                    <select id="new_item_search" class="form-control" style="width: 100%" multiple></select>
                                                </div>

                                                <h6 class="mt-4 mb-3">Items to be added:</h6>
                                                <div class="item-preview" id="itemsPreview">
                                                    <div class="text-muted text-center py-3">No items selected</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-primary" id="confirmAddItems">Add Items</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            let selectedItems = [];

            // Format quantity display
            function formatQuantity(qty) {
                return parseFloat(qty) % 1 === 0 ? parseInt(qty) : parseFloat(qty);
            }

            // Initialize Select2 for item search
            function initSelect2() {
                $('#new_item_search').select2({
                    ajax: {
                        url: 'search_products.php',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                search: params.term,
                                for_stock_management: true
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: $.map(data.results, function(item) {
                                    return {
                                        id: item.id,
                                        text: item.name + ' (' + item.unit_name + ')',
                                        unitId: item.unit_id,
                                        unitName: item.unit_name
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 2,
                    placeholder: 'Search for items',
                    dropdownParent: $('#addItemModal'),
                    closeOnSelect: true,
                });
            }

            // Initialize Select2 when modal is shown
            $('#addItemModal').on('shown.bs.modal', function() {
                initSelect2();
                // Clear previous selections when modal opens
                $('#new_item_search').val(null).trigger('change');
                selectedItems = [];
                updateItemsPreview();
            });

            // When items are selected
            $('#new_item_search').on('change', function() {
                const selectedOptions = $(this).select2('data');
                selectedItems = selectedOptions.map(item => {
                    // Handle cases where item name might contain parentheses
                    const lastParenIndex = item.text.lastIndexOf(' (');
                    const itemName = lastParenIndex >= 0 ?
                        item.text.substring(0, lastParenIndex) :
                        item.text;

                    return {
                        id: item.id,
                        text: itemName, // Now properly handles item names with parentheses
                        unitId: item.unitId,
                        unitName: item.unitName
                    };
                });

                updateItemsPreview();
            });

            // Update the items preview list
            function updateItemsPreview() {
                const previewContainer = $('#itemsPreview');

                if (selectedItems.length === 0) {
                    previewContainer.html('<div class="text-muted text-center py-3">No items selected</div>');
                    return;
                }

                let previewHTML = '';
                selectedItems.forEach(item => {
                    previewHTML += `
                        <div class="preview-item">
                            <div class="d-flex justify-content-between">
                                <span>${item.text}</span>
                                <span>${item.unitName}</span>
                            </div>
                        </div>
                    `;
                });

                previewContainer.html(previewHTML);
            }
            // Handle adding items to the list
            $('#confirmAddItems').on('click', function() {
                if (selectedItems.length === 0) {
                    alert('Please select at least one item');
                    return;
                }

                let duplicateItems = [];
                let addedItems = 0;

                // Add each selected item to the main list
                selectedItems.forEach(item => {
                    // Check if item already exists in the group
                    const existingItemIndex = Array.from($('input[name="item_id[]"]')).findIndex(input =>
                        $(input).val() == item.id
                    );

                    if (existingItemIndex !== -1) {
                        // Add to duplicate list
                        duplicateItems.push(item.text);
                    } else {
                        // Add new item
                        const itemUniqueId = 'item_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                        const initialQty = 1;

                        const itemRow = `
                <div class="row item-row">
                    <div class="col-md-5">
                        <span class="item-name">${item.text}</span>
                        <input type="hidden" name="item_id[]" value="${item.id}">
                        <input type="hidden" name="item_name[]" value="${item.text}">
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="0.01" class="form-control quantity-input" name="quantity[]" 
                               value="${initialQty}" min="0.01" required>
                    </div>
                    <div class="col-md-3">
                        ${item.unitName}
                        <input type="hidden" name="unit_id[]" value="${item.unitId}">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-remove remove-item">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;

                        // Add to items list (remove empty state if present)
                        if ($('#groupItems').find('.text-center').length) {
                            $('#groupItems').html('<div class="row items-header"><div class="col-md-5">Item Name</div><div class="col-md-3">Quantity</div><div class="col-md-3">Unit</div><div class="col-md-1">Action</div></div>');
                        }
                        $('#groupItems').append(itemRow);
                        addedItems++;
                    }
                });

                // Show appropriate message
                if (duplicateItems.length > 0) {
                    alert(`These items are already in the group: ${duplicateItems.join(', ')}`);
                }

                // Close modal if any items were added
                if (addedItems > 0) {
                    $('#addItemModal').modal('hide');
                }
            });

            // Remove item button
            $(document).on('click', '.remove-item', function() {
                $(this).closest('.item-row').remove();

                // Show empty state if no items left
                if ($('#groupItems').children('.item-row').length === 0) {
                    $('#groupItems').html(`
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 2rem; color: #adb5bd;"></i>
                            <p class="text-muted mt-2">No items added yet</p>
                        </div>
                    `);
                }
            });

            // Form submission validation
            $('#groupForm').on('submit', function(e) {
                // Count how many items are in the form
                const itemCount = $('input[name="item_id[]"]').length;

                if (itemCount === 0) {
                    e.preventDefault();
                    if (confirm('This group has no items. Would you like to delete this group instead?')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'group_management.php';

                        const groupIdInput = document.createElement('input');
                        groupIdInput.type = 'hidden';
                        groupIdInput.name = 'group_id';
                        groupIdInput.value = '<?= $group_id ?>';

                        const deleteInput = document.createElement('input');
                        deleteInput.type = 'hidden';
                        deleteInput.name = 'delete_group';
                        deleteInput.value = '1';

                        form.appendChild(groupIdInput);
                        form.appendChild(deleteInput);

                        document.body.appendChild(form);
                        form.submit();
                    }
                    return false;
                }

                return true;
            });
        });
    </script>
</body>

</html>