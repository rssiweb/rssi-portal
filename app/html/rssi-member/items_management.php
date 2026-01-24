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

// Database connection
// $con = getCon();

// Initialize variables
$action = $_POST['action'] ?? '';
$item_id = $_POST['item_id'] ?? '';
$message = '';
$error = '';
$item_data = [];

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'add' || $action === 'edit') {
            // Validate required fields
            $required = ['item_name', 'access_scope', 'category'];
            foreach ($required as $field) {
                if (empty(trim($_POST[$field] ?? ''))) {
                    throw new Exception("Field '$field' is required");
                }
            }

            // Prepare data
            $data = [
                'item_name' => pg_escape_string($con, trim($_POST['item_name'])),
                'access_scope' => pg_escape_string($con, trim($_POST['access_scope'])),
                'image_url' => pg_escape_string($con, trim($_POST['image_url'] ?? '')),
                'description' => pg_escape_string($con, trim($_POST['description'] ?? '')),
                'is_featured' => isset($_POST['is_featured']) ? '1' : '0',
                'rating' => floatval($_POST['rating'] ?? 0),
                'review_count' => intval($_POST['review_count'] ?? 0),
                'category' => pg_escape_string($con, trim($_POST['category'])),
                'is_active' => isset($_POST['is_active']) ? '1' : '0',
                'is_ration' => isset($_POST['is_ration']) ? '1' : '0'
            ];

            if ($action === 'add') {
                // Insert new item
                $sql = "INSERT INTO stock_item (item_name, access_scope, image_url, description, is_featured, rating, review_count, category, is_active, is_ration, created_at, created_by) 
                        VALUES ('{$data['item_name']}', '{$data['access_scope']}', '{$data['image_url']}', '{$data['description']}', 
                                '{$data['is_featured']}', {$data['rating']}, {$data['review_count']}, '{$data['category']}', 
                                '{$data['is_active']}', '{$data['is_ration']}', NOW(), '$associatenumber')";

                $result = pg_query($con, $sql);
                if ($result) {
                    $message = "Item added successfully!";
                } else {
                    throw new Exception("Failed to add item: " . pg_last_error($con));
                }
            } elseif ($action === 'edit' && $item_id) {
                // Update existing item
                $sql = "UPDATE stock_item SET 
                        item_name = '{$data['item_name']}',
                        access_scope = '{$data['access_scope']}',
                        image_url = '{$data['image_url']}',
                        description = '{$data['description']}',
                        is_featured = '{$data['is_featured']}',
                        rating = {$data['rating']},
                        review_count = {$data['review_count']},
                        category = '{$data['category']}',
                        is_active = '{$data['is_active']}',
                        is_ration = '{$data['is_ration']}',
                        updated_at = NOW(),
                        updated_by = $associatenumber
                        WHERE item_id = $item_id";

                $result = pg_query($con, $sql);
                if ($result) {
                    $message = "Item updated successfully!";
                } else {
                    throw new Exception("Failed to update item: " . pg_last_error($con));
                }
            }
        } elseif ($action === 'delete' && $item_id) {
            // Soft delete (mark as inactive) instead of hard delete
            $sql = "UPDATE stock_item SET is_active = '0', updated_at = NOW(), updated_by = $associatenumber WHERE item_id = $item_id";
            $result = pg_query($con, $sql);
            if ($result) {
                $message = "Item deactivated successfully!";
            } else {
                throw new Exception("Failed to deactivate item: " . pg_last_error($con));
            }
        } elseif ($action === 'toggle_featured' && $item_id) {
            // Toggle featured status
            $sql = "UPDATE stock_item SET is_featured = NOT is_featured, updated_at = NOW(), updated_by = $associatenumber WHERE item_id = $item_id";
            $result = pg_query($con, $sql);
            if ($result) {
                $message = "Featured status updated!";
            } else {
                throw new Exception("Failed to update featured status: " . pg_last_error($con));
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get item data for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = pg_query($con, "SELECT * FROM stock_item WHERE item_id = $edit_id");
    if ($result && pg_num_rows($result) > 0) {
        $item_data = pg_fetch_assoc($result);
    }
}

// Get filter parameters
$filters = [
    'search' => $_GET['search'] ?? '',
    'category' => $_GET['category'] ?? '',
    'access_scope' => $_GET['access_scope'] ?? '',
    'is_active' => $_GET['is_active'] ?? '',
    'is_featured' => $_GET['is_featured'] ?? '',
    'is_ration' => $_GET['is_ration'] ?? ''
];

// Build WHERE clause for filtering
$where_conditions = [];
$params = [];

if (!empty($filters['search'])) {
    $search = pg_escape_string($con, $filters['search']);
    $where_conditions[] = "(item_name ILIKE '%$search%' OR description ILIKE '%$search%')";
}

if (!empty($filters['category'])) {
    $category = pg_escape_string($con, $filters['category']);
    $where_conditions[] = "category = '$category'";
}

if (!empty($filters['access_scope'])) {
    $access_scope = pg_escape_string($con, $filters['access_scope']);
    $where_conditions[] = "access_scope = '$access_scope'";
}

if ($filters['is_active'] !== '') {
    $is_active = $filters['is_active'] === 't' ? '1' : '0';
    $where_conditions[] = "is_active = '$is_active'";
}

if ($filters['is_featured'] !== '') {
    $is_featured = $filters['is_featured'] === 't' ? '1' : '0';
    $where_conditions[] = "is_featured = '$is_featured'";
}

if ($filters['is_ration'] !== '') {
    $is_ration = $filters['is_ration'] === 't' ? '1' : '0';
    $where_conditions[] = "is_ration = '$is_ration'";
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get distinct values for filters
$categories_result = pg_query($con, "SELECT DISTINCT category FROM stock_item WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = pg_fetch_all($categories_result) ?: [];

$access_scopes_result = pg_query($con, "SELECT DISTINCT access_scope FROM stock_item WHERE access_scope IS NOT NULL AND access_scope != '' ORDER BY access_scope");
$access_scopes = pg_fetch_all($access_scopes_result) ?: [];

// Pagination setup
$items_per_page = 10; // Optimized for large datasets
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM stock_item $where_sql";
$count_result = pg_query($con, $count_sql);
$total_items = pg_fetch_assoc($count_result)['total'] ?? 0;
$total_pages = ceil($total_items / $items_per_page);

// Get items with pagination and ordering
$order_by = $_GET['order_by'] ?? 'item_id';
$order_dir = $_GET['order_dir'] ?? 'DESC';
$allowed_order_columns = ['item_id', 'item_name', 'category', 'access_scope', 'rating', 'review_count', 'created_at'];
$order_by = in_array($order_by, $allowed_order_columns) ? $order_by : 'item_id';
$order_dir = strtoupper($order_dir) === 'ASC' ? 'ASC' : 'DESC';

$sql = "SELECT * FROM stock_item 
        $where_sql 
        ORDER BY $order_by $order_dir 
        LIMIT $items_per_page OFFSET $offset";

$result = pg_query($con, $sql);
$items = pg_fetch_all($result) ?: [];
?>

<?php
$isFeatured = $_GET['is_featured'] ?? null;
$isRation   = $_GET['is_ration'] ?? null;
$isActive   = $_GET['is_active'] ?? null;
?>
<?php
// Define categories array
$categories = [
    'Stationery & Writing',
    'Books & Educational Material',
    'Laboratory Equipment',
    'Science & Physics Tools',
    'Classroom Supplies',
    'Art & Craft Supplies',
    'Technology & Electronics',
    'Sports & Physical Education',
    'School Uniform & Identity',
    'Cleaning & Maintenance',
    'Cleaning Supplies',
    'Health & Hygiene',
    'Food & Ration Items',
    'Clothing & Textiles',
    'Gift Cards & Coupons',
    'Furniture & Storage',
    'Office Supplies',
    'Pantry items',
    'Musical Instruments',
    'Teaching Aids',
    'Miscellaneous'
];

$selectedCategory = $item_data['category'] ?? '';
$filterCategory = $filters['category'] ?? '';
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

    <title>Items Management</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Select2 for better dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }

        .form-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .filter-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .badge-active {
            background-color: #198754;
        }

        .badge-inactive {
            background-color: #dc3545;
        }

        .badge-featured {
            background-color: #ffc107;
            color: #000;
        }

        .badge-ration {
            background-color: #0d6efd;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .status-toggle {
            cursor: pointer;
        }

        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Items Management</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                    <li class="breadcrumb-item active">Items Management</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Add/Edit Form -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?= empty($item_data) ? 'Add New Item' : 'Edit Item' ?></h5>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="<?= empty($item_data) ? 'add' : 'edit' ?>">
                                <?php if (!empty($item_data)): ?>
                                    <input type="hidden" name="item_id" value="<?= $item_data['item_id'] ?>">
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="item_name" class="form-label">Item Name *</label>
                                            <input type="text" class="form-control" id="item_name" name="item_name"
                                                value="<?= htmlspecialchars($item_data['item_name'] ?? '') ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="form_category" class="form-label">Category *</label>
                                            <select class="form-select" id="form_category" name="category" required>
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?= htmlspecialchars($category) ?>"
                                                        <?= ($item_data['category'] ?? '') === $category ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($category) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="access_scope" class="form-label">Access Scope *</label>
                                            <select class="form-select" id="access_scope" name="access_scope" required>
                                                <option value="">Select Access Scope</option>
                                                <?php foreach ($access_scopes as $scope): ?>
                                                    <option value="<?= htmlspecialchars($scope['access_scope']) ?>"
                                                        <?= ($item_data['access_scope'] ?? '') === $scope['access_scope'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($scope['access_scope']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="image_url" class="form-label">Image URL</label>
                                            <input type="url" class="form-control" id="image_url" name="image_url"
                                                value="<?= htmlspecialchars($item_data['image_url'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description"
                                        rows="3"><?= htmlspecialchars($item_data['description'] ?? '') ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="rating" class="form-label">Rating</label>
                                            <input type="number" class="form-control" id="rating" name="rating"
                                                min="0" max="5" step="0.1"
                                                value="<?= $item_data['rating'] ?? '0' ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="review_count" class="form-label">Review Count</label>
                                            <input type="number" class="form-control" id="review_count" name="review_count"
                                                min="0" value="<?= $item_data['review_count'] ?? '0' ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3 form-check pt-4">
                                            <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured"
                                                value="1" <?= ($item_data['is_featured'] ?? '0') === 't' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="is_featured">Featured Item</label>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3 form-check pt-4">
                                            <input type="checkbox" class="form-check-input" id="is_ration" name="is_ration"
                                                value="1" <?= ($item_data['is_ration'] ?? '0') === 't' ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="is_ration">Ration Item</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                                        value="1" <?= (empty($item_data) || ($item_data['is_active'] ?? '1') === 't') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <?= empty($item_data) ? 'Add Item' : 'Update Item' ?>
                                    </button>
                                    <?php if (!empty($item_data)): ?>
                                        <a href="items_management.php" class="btn btn-secondary">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="col-12">
                    <div class="filter-card">
                        <h5 class="card-title">Filter Items</h5>
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search"
                                    value="<?= htmlspecialchars($filters['search']) ?>"
                                    placeholder="Name or description...">
                            </div>

                            <div class="col-md-2">
                                <label for="filter_category" class="form-label">Category</label>
                                <select class="form-select" id="filter_category" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category) ?>"
                                            <?= (!isset($_GET['edit']) && ($filters['category'] ?? '') === $category) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="access_scope" class="form-label">Access Scope</label>
                                <select class="form-select" id="access_scope" name="access_scope">
                                    <option value="">All Scopes</option>
                                    <?php foreach ($access_scopes as $scope): ?>
                                        <option value="<?= htmlspecialchars($scope['access_scope']) ?>"
                                            <?= $filters['access_scope'] === $scope['access_scope'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($scope['access_scope']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="is_active" class="form-label">Status</label>
                                <select class="form-select" id="is_active" name="is_active">
                                    <option value="">All</option>
                                    <option value="t" <?= $filters['is_active'] === 't' ? 'selected' : '' ?>>Active</option>
                                    <option value="f" <?= $filters['is_active'] === 'f' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="btn-group" role="group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-funnel"></i> Apply Filters
                                    </button>
                                    <a href="items_management.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                        <!-- Quick Filter Toggles -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="btn-group" role="group">

                                    <a href="?is_featured=t"
                                        class="btn btn-outline-warning btn-sm <?= $isFeatured === 't' ? 'active' : '' ?>">
                                        <i class="bi bi-star"></i> Featured
                                    </a>

                                    <a href="?is_ration=t"
                                        class="btn btn-outline-primary btn-sm <?= $isRation === 't' ? 'active' : '' ?>">
                                        <i class="bi bi-basket"></i> Ration Items
                                    </a>

                                    <a href="?is_active=f"
                                        class="btn btn-outline-danger btn-sm <?= $isActive === 'f' ? 'active' : '' ?>">
                                        <i class="bi bi-eye-slash"></i> Inactive
                                    </a>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items List -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Items List (<?= number_format($total_items) ?> total)</h5>

                            <?php if ($total_items > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="items-table">
                                        <thead>
                                            <tr>
                                                <th>ID
                                                    <a href="?order_by=item_id&order_dir=ASC<?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['order_by' => '', 'order_dir' => ''])) : '' ?>">
                                                        <i class="bi bi-arrow-up"></i>
                                                    </a>
                                                    <a href="?order_by=item_id&order_dir=DESC<?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['order_by' => '', 'order_dir' => ''])) : '' ?>">
                                                        <i class="bi bi-arrow-down"></i>
                                                    </a>
                                                </th>
                                                <th>Name
                                                    <a href="?order_by=item_name&order_dir=ASC<?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['order_by' => '', 'order_dir' => ''])) : '' ?>">
                                                        <i class="bi bi-arrow-up"></i>
                                                    </a>
                                                    <a href="?order_by=item_name&order_dir=DESC<?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['order_by' => '', 'order_dir' => ''])) : '' ?>">
                                                        <i class="bi bi-arrow-down"></i>
                                                    </a>
                                                </th>
                                                <th>Category</th>
                                                <th>Access</th>
                                                <th>Rating</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items as $item): ?>
                                                <tr>
                                                    <td><?= $item['item_id'] ?></td>
                                                    <td>
                                                        <?= htmlspecialchars($item['item_name']) ?>
                                                        <?php if ($item['is_featured'] === 't'): ?>
                                                            <span class="badge badge-featured">Featured</span>
                                                        <?php endif; ?>
                                                        <?php if ($item['is_ration'] === 't'): ?>
                                                            <span class="badge badge-ration">Ration</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($item['category'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($item['access_scope'] ?? '') ?></td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?= number_format($item['rating'], 1) ?>
                                                            <i class="bi bi-star-fill"></i>
                                                        </span>
                                                        <small class="text-muted">(<?= $item['review_count'] ?> reviews)</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $item['is_active'] === 't' ? 'badge-active' : 'badge-inactive' ?>">
                                                            <?= $item['is_active'] === 't' ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="?edit=<?= $item['item_id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="action" value="toggle_featured">
                                                                <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Toggle Featured">
                                                                    <i class="bi bi-star<?= $item['is_featured'] === 't' ? '-fill' : '' ?>"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to deactivate this item?');">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                                                <!-- <button type="submit" class="btn btn-sm btn-outline-danger" title="Deactivate">
                                                                    <i class="bi bi-trash"></i>
                                                                </button> -->
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                                                        Previous
                                                    </a>
                                                </li>
                                            <?php endif; ?>

                                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                                                        <?= $i ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                                                        Next
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>

                                <div class="text-center text-muted">
                                    Showing <?= min($items_per_page, count($items)) ?> of <?= number_format($total_items) ?> items
                                    <?php if (!empty($where_conditions)): ?>
                                        (filtered)
                                    <?php endif; ?>
                                </div>

                            <?php else: ?>
                                <div class="alert alert-info">
                                    No items found.
                                    <?php if (!empty($where_conditions)): ?>
                                        Try changing your filters or <a href="items_management.php">clear all filters</a>.
                                    <?php else: ?>
                                        <a href="items_management.php?edit=">Add your first item</a>.
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for better dropdowns
            $('#access_scope').select2({
                placeholder: "Select Access Scope",
                allowClear: true
            });

            // Initialize DataTables with optimized settings for large datasets
            if ($('#items-table').length) {
                $('#items-table').DataTable({
                    paging: false, // We use custom pagination
                    searching: false, // We use custom search
                    ordering: false, // We use custom ordering
                    info: false,
                    autoWidth: false,
                    scrollY: '400px',
                    scrollCollapse: true,
                    responsive: true,
                    columnDefs: [{
                            orderable: false,
                            targets: [6]
                        } // Disable sorting on actions column
                    ]
                });
            }

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);

            // Confirm before deactivation
            $('form[onsubmit]').submit(function(e) {
                return confirm('Are you sure you want to deactivate this item?');
            });

            // Quick edit on double click row
            $('#items-table tbody tr').on('dblclick', function() {
                var itemId = $(this).find('td:first').text();
                window.location.href = '?edit=' + itemId;
            });
        });
    </script>
</body>

</html>