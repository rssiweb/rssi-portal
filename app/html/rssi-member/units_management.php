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

// Initialize variables
$action = $_POST['action'] ?? '';
$unit_id = $_POST['unit_id'] ?? '';
$message = '';
$error = '';
$unit_data = [];

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'add' || $action === 'edit') {
            // Validate required fields
            $required = ['unit_name'];
            foreach ($required as $field) {
                if (empty(trim($_POST[$field] ?? ''))) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            // Prepare data
            $unit_name = pg_escape_string($con, trim($_POST['unit_name']));
            $description = pg_escape_string($con, trim($_POST['description'] ?? ''));
            $is_active = isset($_POST['is_active']) ? 't' : 'f';
            
            // Check for duplicate unit name
            $check_sql = "SELECT unit_id FROM stock_item_unit WHERE LOWER(unit_name) = LOWER('$unit_name')";
            if ($action === 'edit' && $unit_id) {
                $check_sql .= " AND unit_id != $unit_id";
            }
            $check_result = pg_query($con, $check_sql);
            if (pg_num_rows($check_result) > 0) {
                throw new Exception("Unit with this name already exists!");
            }
            
            if ($action === 'add') {
                // Insert new unit
                $sql = "INSERT INTO stock_item_unit (unit_name, description, is_active, created_at) 
                        VALUES ('$unit_name', '$description', '$is_active', NOW())";
                
                $result = pg_query($con, $sql);
                if ($result) {
                    $message = "Unit added successfully!";
                } else {
                    throw new Exception("Failed to add unit: " . pg_last_error($con));
                }
            } elseif ($action === 'edit' && $unit_id) {
                // Update existing unit
                $sql = "UPDATE stock_item_unit SET 
                        unit_name = '$unit_name',
                        description = '$description',
                        is_active = '$is_active',
                        updated_at = NOW()
                        WHERE unit_id = $unit_id";
                
                $result = pg_query($con, $sql);
                if ($result) {
                    $message = "Unit updated successfully!";
                } else {
                    throw new Exception("Failed to update unit: " . pg_last_error($con));
                }
            }
        } elseif ($action === 'delete' && $unit_id) {
            // Check if unit is used in prices
            $check_sql = "SELECT COUNT(*) as count FROM stock_item_price WHERE unit_id = $unit_id";
            $check_result = pg_query($con, $check_sql);
            $usage_count = pg_fetch_assoc($check_result)['count'];
            
            if ($usage_count > 0) {
                throw new Exception("Cannot delete unit. It is used in $usage_count price records.");
            }
            
            // Delete unit
            $sql = "DELETE FROM stock_item_unit WHERE unit_id = $unit_id";
            $result = pg_query($con, $sql);
            if ($result) {
                $message = "Unit deleted successfully!";
            } else {
                throw new Exception("Failed to delete unit: " . pg_last_error($con));
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get unit data for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = pg_query($con, "SELECT * FROM stock_item_unit WHERE unit_id = $edit_id");
    if ($result && pg_num_rows($result) > 0) {
        $unit_data = pg_fetch_assoc($result);
    }
}

// Get filter parameters
$filters = [
    'search' => $_GET['search'] ?? '',
    'is_active' => $_GET['is_active'] ?? ''
];

// Build WHERE clause for filtering
$where_conditions = [];

if (!empty($filters['search'])) {
    $search = pg_escape_string($con, $filters['search']);
    $where_conditions[] = "(unit_name ILIKE '%$search%' OR description ILIKE '%$search%')";
}

if ($filters['is_active'] !== '') {
    $is_active = $filters['is_active'] === 't' ? 't' : 'f';
    $where_conditions[] = "is_active = '$is_active'";
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM stock_item_unit $where_sql";
$count_result = pg_query($con, $count_sql);
$total_units = pg_fetch_assoc($count_result)['total'] ?? 0;

// Get units with pagination
$sql = "SELECT *, 
        (SELECT COUNT(*) FROM stock_item_price WHERE unit_id = stock_item_unit.unit_id) as usage_count
        FROM stock_item_unit 
        $where_sql 
        ORDER BY unit_name 
        LIMIT 100";

$result = pg_query($con, $sql);
$units = pg_fetch_all($result) ?: [];
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'AW-11316670180');
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Units Management</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        .form-container { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .filter-card { background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .badge-active { background-color: #198754; }
        .badge-inactive { background-color: #6c757d; }
        .badge-usage { background-color: #0d6efd; }
        .table-responsive { max-height: 600px; overflow-y: auto; }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Units Management</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="items_management.php">Inventory</a></li>
                    <li class="breadcrumb-item active">Units</li>
                </ol>
            </nav>
        </div>

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

                <!-- Unit Form -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?= empty($unit_data) ? 'Add New Unit' : 'Edit Unit' ?></h5>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="<?= empty($unit_data) ? 'add' : 'edit' ?>">
                                <?php if (!empty($unit_data)): ?>
                                    <input type="hidden" name="unit_id" value="<?= $unit_data['unit_id'] ?>">
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="unit_name" class="form-label">Unit Name *</label>
                                            <input type="text" class="form-control" id="unit_name" name="unit_name" 
                                                   required value="<?= htmlspecialchars($unit_data['unit_name'] ?? '') ?>"
                                                   placeholder="e.g., Kilogram, Liter, Piece, Pack">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description (Optional)</label>
                                            <input type="text" class="form-control" id="description" name="description" 
                                                   value="<?= htmlspecialchars($unit_data['description'] ?? '') ?>"
                                                   placeholder="e.g., Standard unit for weight">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                           value="1" <?= (empty($unit_data) || ($unit_data['is_active'] ?? 't') === 't') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <?= empty($unit_data) ? 'Add Unit' : 'Update Unit' ?>
                                    </button>
                                    <?php if (!empty($unit_data)): ?>
                                        <a href="units_management.php" class="btn btn-secondary">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="col-12">
                    <div class="filter-card">
                        <h5 class="card-title">Filter Units</h5>
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search"
                                    value="<?= htmlspecialchars($filters['search']) ?>"
                                    placeholder="Search by name or description...">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="is_active" class="form-label">Status</label>
                                <select class="form-select" id="is_active" name="is_active">
                                    <option value="">All Status</option>
                                    <option value="t" <?= $filters['is_active'] === 't' ? 'selected' : '' ?>>Active</option>
                                    <option value="f" <?= $filters['is_active'] === 'f' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="col-md-5">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="btn-group" role="group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-funnel"></i> Apply Filters
                                    </button>
                                    <a href="units_management.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </a>
                                    <a href="?is_active=t" class="btn btn-outline-success">
                                        <i class="bi bi-check-circle"></i> Active Only
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Units List -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Units List (<?= number_format($total_units) ?> total)</h5>
                            
                            <?php if (count($units) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Unit Name</th>
                                                <th>Description</th>
                                                <th>Status</th>
                                                <th>Usage</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($units as $unit): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($unit['unit_name']) ?></strong>
                                                    </td>
                                                    <td><?= htmlspecialchars($unit['description'] ?? '—') ?></td>
                                                    <td>
                                                        <span class="badge <?= $unit['is_active'] === 't' ? 'badge-active' : 'badge-inactive' ?>">
                                                            <?= $unit['is_active'] === 't' ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-usage">
                                                            <?= $unit['usage_count'] ?> uses
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= date('d M Y', strtotime($unit['created_at'] ?? '')) ?>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="?edit=<?= $unit['unit_id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <?php if ($unit['usage_count'] == 0): ?>
                                                                <form method="POST" style="display:inline;" 
                                                                      onsubmit="return confirm('Delete this unit?');">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="unit_id" value="<?= $unit['unit_id'] ?>">
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="text-center text-muted mt-3">
                                    Showing <?= count($units) ?> units
                                    <?php if (!empty($where_conditions)): ?>
                                        (filtered)
                                    <?php endif; ?>
                                </div>
                                
                            <?php else: ?>
                                <div class="alert alert-info">
                                    No units found.
                                    <?php if (!empty($filters['search']) || !empty($filters['is_active'])): ?>
                                        Try changing your filters or <a href="units_management.php">clear all filters</a>.
                                    <?php else: ?>
                                        <a href="units_management.php">Add your first unit</a>.
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                document.querySelectorAll('.alert').forEach(alert => {
                    new bootstrap.Alert(alert).close();
                });
            }, 5000);
        });
    </script>
</body>
</html>