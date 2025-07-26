<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid") || $role != 'Admin') {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
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
            "UPDATE stock_item_groups SET group_name=$1, description=$2, updated_at=NOW() WHERE group_id=$3",
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .item-row {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }

        .unit-select {
            min-width: 150px;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Edit Group: <?= htmlspecialchars($group['group_name']) ?></h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="group_management.php">Group Management</a></li>
                    <li class="breadcrumb-item active">Edit Group</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="group_id" value="<?= $group_id ?>">

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="group_name" class="form-label">Group Name</label>
                                        <input type="text" class="form-control" id="group_name" name="group_name"
                                            value="<?= htmlspecialchars($group['group_name']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="description" class="form-label">Description</label>
                                        <input type="text" class="form-control" id="description" name="description"
                                            value="<?= htmlspecialchars($group['description']) ?>">
                                    </div>
                                </div>

                                <h5 class="card-title mt-4">Group Items</h5>

                                <div id="groupItems">
                                    <?php foreach ($items as $item): ?>
                                        <div class="item-row row">
                                            <div class="col-md-5">
                                                <input type="text" class="form-control" name="item_name[]"
                                                    value="<?= htmlspecialchars($item['item_name']) ?>" readonly>
                                                <input type="hidden" name="item_id[]" value="<?= $item['item_id'] ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" step="0.01" class="form-control" name="quantity[]"
                                                    value="<?= $item['quantity'] ?>" min="0.01" required>
                                            </div>
                                            <div class="col-md-3">
                                                <select class="form-select unit-select" name="unit_id[]" required>
                                                    <?php foreach ($units as $unit): ?>
                                                        <option value="<?= $unit['unit_id'] ?>"
                                                            <?= $unit['unit_id'] == $item['unit_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($unit['unit_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-danger remove-item">Remove</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="mt-3">
                                    <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                        <i class="bi bi-plus"></i> Add Item
                                    </button>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" name="update_group" class="btn btn-primary">Save Changes</button>
                                    <a href="group_management.php" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemModalLabel">Add Item to Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addItemForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="new_item_search" class="form-label">Item</label>
                            <select id="new_item_search" class="form-control" style="width: 100%"></select>
                            <input type="hidden" id="new_item_id" name="new_item_id">
                        </div>
                        <div class="mb-3">
                            <label for="new_quantity" class="form-label">Quantity</label>
                            <input type="number" step="0.01" class="form-control" id="new_quantity" name="new_quantity" min="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_unit_id" class="form-label">Unit</label>
                            <select class="form-select" id="new_unit_id" name="new_unit_id" required>
                                <?php foreach ($units as $unit): ?>
                                    <option value="<?= $unit['unit_id'] ?>"><?= htmlspecialchars($unit['unit_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for item search - with modal fix
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
                    minimumInputLength: 1,
                    placeholder: 'Search for an item',
                    dropdownParent: $('#addItemModal')
                });
            }

            // Initialize Select2 when modal is shown
            $('#addItemModal').on('shown.bs.modal', function() {
                initSelect2();
                // Clear previous selections when modal opens
                $('#new_item_search').val('').trigger('change');
                $('#new_quantity').val('');
            });

            // When item is selected, set the unit
            $('#new_item_search').on('change', function() {
                const selectedItem = $(this).select2('data')[0];
                if (selectedItem) {
                    $('#new_item_id').val(selectedItem.id);
                    $('#new_unit_id').val(selectedItem.unitId).trigger('change');
                }
            });

            // Handle adding item to the list (not to database yet)
            $('#addItemForm').on('submit', function(e) {
                e.preventDefault();

                const selectedItem = $('#new_item_search').select2('data')[0];
                const quantity = $('#new_quantity').val();
                const unitId = $('#new_unit_id').val();
                const unitName = $('#new_unit_id option:selected').text();

                if (!selectedItem || !quantity || quantity <= 0 || !unitId) {
                    alert('Please select an item, enter quantity, and select unit');
                    return;
                }

                // Generate unique ID for this item in the list
                const itemUniqueId = 'item_' + Date.now() + Math.floor(Math.random() * 1000);

                // Create new item row
                const itemRow = `
                <div class="item-row row" id="${itemUniqueId}">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="item_name[]" 
                               value="${selectedItem.text.split(' (')[0]}" readonly>
                        <input type="hidden" name="item_id[]" value="${selectedItem.id}">
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" class="form-control" name="quantity[]" 
                               value="${quantity}" min="0.01" required>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select unit-select" name="unit_id[]" required>
                            ${$('#new_unit_id').html()} <!-- Copy all unit options -->
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-item">Remove</button>
                    </div>
                </div>
            `;

                // Add to items list
                $('#groupItems').append(itemRow);

                // Set the selected unit in the new row
                $(`#${itemUniqueId} select[name="unit_id[]"]`).val(unitId);

                // Reset form and close modal
                $('#addItemForm')[0].reset();
                $('#new_item_search').val('').trigger('change');
                $('#addItemModal').modal('hide');
            });

            // Remove item button
            $(document).on('click', '.remove-item', function() {
                $(this).closest('.item-row').remove();
            });
        });
    </script>
</body>

</html>