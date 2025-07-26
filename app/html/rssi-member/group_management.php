<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid") || $role != 'Admin') {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
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
            "UPDATE stock_item_groups SET group_name=$1, description=$2, updated_at=NOW() WHERE group_id=$3",
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
$groups_result = pg_query($con, "SELECT * FROM stock_item_groups ORDER BY group_name");
$groups = pg_fetch_all($groups_result) ?: [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stock Item Group Management</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .group-card {
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .group-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .item-badge {
            margin-right: 5px;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Stock Item Group Management</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Stock Management</a></li>
                    <li class="breadcrumb-item active">Group Management</li>
                </ol>
            </nav>
        </div>

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
                        <div class="card-body">
                            <h5 class="card-title">Create New Group</h5>
                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="group_name" class="form-label">Group Name</label>
                                        <input type="text" class="form-control" id="group_name" name="group_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="description" class="form-label">Description</label>
                                        <input type="text" class="form-control" id="description" name="description">
                                    </div>
                                </div>
                                <button type="submit" name="create_group" class="btn btn-primary">Create Group</button>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title">Existing Groups</h5>
                            <div class="row">
                                <?php foreach ($groups as $group): ?>
                                    <div class="col-md-6">
                                        <div class="card group-card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <h5><?= htmlspecialchars($group['group_name']) ?></h5>
                                                    <div>
                                                        <a href="edit_group.php?group_id=<?= $group['group_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this group?');">
                                                            <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                                                            <button type="submit" name="delete_group" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                                <?php if (!empty($group['description'])): ?>
                                                    <p class="text-muted"><?= htmlspecialchars($group['description']) ?></p>
                                                <?php endif; ?>

                                                <?php
                                                // Get items in this group
                                                $items_result = pg_query_params(
                                                    $con,
                                                    "SELECT i.item_name, gi.quantity, u.unit_name 
                                                     FROM stock_item_group_items gi
                                                     JOIN stock_item i ON gi.item_id = i.item_id
                                                     JOIN stock_item_unit u ON gi.unit_id = u.unit_id
                                                     WHERE gi.group_id = $1",
                                                    array($group['group_id'])
                                                );
                                                $items = pg_fetch_all($items_result) ?: [];
                                                ?>

                                                <div class="mt-2">
                                                    <h6>Items in Group:</h6>
                                                    <?php if (!empty($items)): ?>
                                                        <?php foreach ($items as $item): ?>
                                                            <span class="badge bg-primary item-badge">
                                                                <?= htmlspecialchars($item['item_name']) ?>
                                                                (<?= $item['quantity'] ?> <?= $item['unit_name'] ?>)
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <p class="text-muted">No items in this group yet.</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
</body>

</html>