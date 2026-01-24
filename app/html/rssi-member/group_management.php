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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_group'])) {
        // Create new group
        $group_name = pg_escape_string($con, $_POST['group_name']);
        $description = pg_escape_string($con, $_POST['description']);
        $created_by = $associatenumber;

        $result = pg_query_params(
            $con,
            "INSERT INTO stock_item_groups (group_name, description, created_by) VALUES ($1, $2, $3) RETURNING group_id",
            array($group_name, $description, $created_by)
        );

        if ($result) {
            $group_id = pg_fetch_result($result, 0, 0);
            $_SESSION['success_message'] = "Group created successfully!";
            header("Location: edit_group.php?group_id=$group_id");
            exit;
        }
    } elseif (isset($_POST['update_group'])) {
        // Update existing group
        $group_id = $_POST['group_id'];
        $group_name = pg_escape_string($con, $_POST['group_name']);
        $description = pg_escape_string($con, $_POST['description']);

        pg_query_params(
            $con,
            "UPDATE stock_item_groups SET group_name=$1, description=$2, updated_at=NOW(), updated_by='$associatenumber' WHERE group_id=$3",
            array($group_name, $description, $group_id)
        );

        $_SESSION['success_message'] = "Group updated successfully!";
        header("Location: group_management.php");
        exit;
    } elseif (isset($_POST['delete_group'])) {
        // Delete group
        $group_id = $_POST['group_id'];
        pg_query_params($con, "DELETE FROM stock_item_groups WHERE group_id=$1", array($group_id));
        $_SESSION['success_message'] = "Group deleted successfully!";
        header("Location: group_management.php");
        exit;
    }
}

// Fetch all groups
$groups_result = pg_query($con, "
    SELECT 
        rm.fullname AS updated_by_name, 
        rmc.fullname AS created_by_name, 
        stock_item_groups.*
    FROM stock_item_groups
    LEFT JOIN rssimyaccount_members rm ON rm.associatenumber = stock_item_groups.updated_by
    LEFT JOIN rssimyaccount_members rmc ON rmc.associatenumber = stock_item_groups.created_by
    ORDER BY group_name
");
$groups = pg_fetch_all($groups_result) ?: [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>
    
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <style>
        .modal-body .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .badge-count {
            background-color: #6c757d;
            color: white;
            border-radius: 10px;
            padding: 3px 8px;
            font-size: 0.8em;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $_SESSION['success_message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body container">
                            <h5 class="card-header mb-4">Create New Group</h5>
                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="group_name" class="form-label">Group Name</label>
                                        <input type="text" class="form-control" id="group_name" name="group_name"
                                            placeholder="e.g., Stationery Kit, Uniform Set" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"
                                            placeholder="Brief description of the group (optional)"></textarea>
                                    </div>
                                </div>
                                <button type="submit" name="create_group" class="btn btn-primary">Create Group</button>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-header mb-4">Existing Groups</h5>
                            <div class="table-responsive">
                                <table id="groupsTable" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Group Name</th>
                                            <th>Description</th>
                                            <th>Items Count</th>
                                            <th>Created By</th>
                                            <th>Last Updated By</th>
                                            <th>Last Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($groups as $group):
                                            // Get item count for this group
                                            $count_result = pg_query_params(
                                                $con,
                                                "SELECT COUNT(*) FROM stock_item_group_items WHERE group_id = $1",
                                                array($group['group_id'])
                                            );
                                            $item_count = pg_fetch_result($count_result, 0, 0);
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($group['group_name']) ?></td>
                                                <td><?= !empty($group['description']) ? htmlspecialchars($group['description']) : '-' ?></td>
                                                <td><?= $item_count ?></td>
                                                <td><?= htmlspecialchars($group['created_by_name']) ?></td>
                                                <td><?= !empty($group['updated_by_name']) ? htmlspecialchars($group['updated_by_name']) : 'Not edited yet' ?></td>
                                                <td><?= (new DateTime($group['updated_at']))->format('d/m/Y h:i A') ?></td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <button class="dropdown-item view-group"
                                                                    data-group-id="<?= $group['group_id'] ?>"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#viewGroupModal">
                                                                    <i class="bi bi-eye"></i> View
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <a href="edit_group.php?group_id=<?= $group['group_id'] ?>" class="dropdown-item">
                                                                    <i class="bi bi-pencil"></i> Edit
                                                                </a>
                                                            </li>
                                                            <?php if ($role === 'Admin'): ?>
                                                                <li>
                                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this group?');">
                                                                        <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                                                                        <button type="submit" name="delete_group" class="dropdown-item text-danger">
                                                                            <i class="bi bi-trash"></i> Delete
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                </td>

                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- View Group Modal -->
    <div class="modal fade" id="viewGroupModal" tabindex="-1" aria-labelledby="viewGroupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewGroupModalLabel">Group Items</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 id="groupNameDisplay"></h6>
                    <p id="groupDescriptionDisplay" class="text-muted"></p>
                    <div id="loadingSpinner" class="text-center py-3" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading items...</p>
                    </div>
                    <div id="groupItemsList">
                        <ul class="list-group">
                            <!-- Items will be loaded here via AJAX -->
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#groupsTable').DataTable({
                responsive: true,
                columnDefs: [{
                        orderable: false,
                        targets: [3]
                    } // Disable sorting on actions column
                ]
            });

            // Handle view group button click
            $('.view-group').click(function() {
                var groupId = $(this).data('group-id');
                var groupName = $(this).closest('tr').find('td:first').text();
                var groupDescription = $(this).closest('tr').find('td:nth-child(2)').text();

                // Set group name and description in modal
                $('#groupNameDisplay').text(groupName);
                $('#groupDescriptionDisplay').text(groupDescription || 'No description available');

                // Show loading spinner and hide items list
                $('#loadingSpinner').show();
                $('#groupItemsList').hide();

                // Load items via AJAX
                $.ajax({
                    url: 'get_group_items.php',
                    method: 'GET',
                    data: {
                        group_id: groupId
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        // This runs before the request is sent
                        $('#loadingSpinner').show();
                        $('#groupItemsList').hide();
                    },
                    success: function(response) {
                        var itemsList = $('#groupItemsList .list-group');
                        itemsList.empty();

                        if (response.length > 0) {
                            $.each(response, function(index, item) {
                                // Format quantity - remove decimals if not needed
                                var quantity = item.quantity;
                                var formattedQuantity = parseFloat(quantity) % 1 === 0 ?
                                    parseInt(quantity) :
                                    parseFloat(quantity).toFixed(2).replace(/\.?0+$/, '');

                                itemsList.append(
                                    '<li class="list-group-item">' +
                                    '<span>' + item.item_name + '</span>' +
                                    formattedQuantity + ' ' + item.unit_name +
                                    '</li>'
                                );
                            });
                        } else {
                            itemsList.append('<li class="list-group-item">No items in this group yet.</li>');
                        }
                    },
                    error: function() {
                        $('#groupItemsList .list-group').html('<li class="list-group-item text-danger">Error loading items. Please try again.</li>');
                    },
                    complete: function() {
                        // This runs after success or error
                        $('#loadingSpinner').hide();
                        $('#groupItemsList').show();
                    }
                });
            });
        });
    </script>
</body>

</html>