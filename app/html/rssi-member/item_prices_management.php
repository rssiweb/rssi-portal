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
$price_id = $_POST['price_id'] ?? '';
$item_id = $_GET['item_id'] ?? $_POST['item_id'] ?? '';
$message = '';
$error = '';
$price_data = [];

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'add' || $action === 'edit') {
            // Validate required fields
            $required = ['item_id', 'unit_id', 'price_per_unit', 'effective_start_date'];
            foreach ($required as $field) {
                if (empty(trim($_POST[$field] ?? ''))) {
                    throw new Exception("Field '$field' is required");
                }
            }

            // Prepare data
            $data = [
                'item_id' => intval($_POST['item_id']),
                'unit_id' => intval($_POST['unit_id']),
                'price_per_unit' => floatval($_POST['price_per_unit']),
                'effective_start_date' => pg_escape_string($con, $_POST['effective_start_date']),
                'effective_end_date' => !empty($_POST['effective_end_date']) ?
                    "'" . pg_escape_string($con, $_POST['effective_end_date']) . "'" : 'NULL',
                'unit_quantity' => !empty($_POST['unit_quantity']) ?
                    floatval($_POST['unit_quantity']) : 'NULL',
                'discount_percentage' => !empty($_POST['discount_percentage']) ?
                    floatval($_POST['discount_percentage']) : 'NULL',
                'original_price' => !empty($_POST['original_price']) ?
                    floatval($_POST['original_price']) : 'NULL'
            ];

            if ($action === 'add') {
                // Insert new price
                $sql = "INSERT INTO stock_item_price 
                        (item_id, unit_id, price_per_unit, effective_start_date, effective_end_date, 
                         unit_quantity, discount_percentage, original_price, created_at) 
                        VALUES ({$data['item_id']}, {$data['unit_id']}, {$data['price_per_unit']}, 
                                '{$data['effective_start_date']}', {$data['effective_end_date']}, 
                                {$data['unit_quantity']}, {$data['discount_percentage']}, 
                                {$data['original_price']}, NOW())";

                $result = pg_query($con, $sql);
                if ($result) {
                    $message = "Price added successfully!";
                    // Clear form data
                    $price_data = [];
                } else {
                    throw new Exception("Failed to add price: " . pg_last_error($con));
                }
            } elseif ($action === 'edit' && $price_id) {
                // Update existing price
                $sql = "UPDATE stock_item_price SET 
                        item_id = {$data['item_id']},
                        unit_id = {$data['unit_id']},
                        price_per_unit = {$data['price_per_unit']},
                        effective_start_date = '{$data['effective_start_date']}',
                        effective_end_date = {$data['effective_end_date']},
                        unit_quantity = {$data['unit_quantity']},
                        discount_percentage = {$data['discount_percentage']},
                        original_price = {$data['original_price']},
                        updated_at = NOW()
                        WHERE price_id = $price_id";

                $result = pg_query($con, $sql);
                if ($result) {
                    $message = "Price updated successfully!";
                } else {
                    throw new Exception("Failed to update price: " . pg_last_error($con));
                }
            }
        } elseif ($action === 'delete' && $price_id) {
            // Delete price record
            $sql = "DELETE FROM stock_item_price WHERE price_id = $price_id";
            $result = pg_query($con, $sql);
            if ($result) {
                $message = "Price deleted successfully!";
            } else {
                throw new Exception("Failed to delete price: " . pg_last_error($con));
            }
        } elseif ($action === 'deactivate_current' && $price_id) {
            // Set end date to today for current price
            $today = date('Y-m-d');
            $sql = "UPDATE stock_item_price SET effective_end_date = '$today' WHERE price_id = $price_id";
            $result = pg_query($con, $sql);
            if ($result) {
                $message = "Price deactivated successfully!";
            } else {
                throw new Exception("Failed to deactivate price: " . pg_last_error($con));
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get price data for editing
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = pg_query($con, "SELECT * FROM stock_item_price WHERE price_id = $edit_id");
    if ($result && pg_num_rows($result) > 0) {
        $price_data = pg_fetch_assoc($result);
        $item_id = $price_data['item_id'];
    }
} elseif (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $item_id = intval($_GET['add']);
}

// Get item details if item_id is set
$item_details = [];
if ($item_id) {
    $result = pg_query($con, "SELECT item_name FROM stock_item WHERE item_id = $item_id");
    if ($result && pg_num_rows($result) > 0) {
        $item_details = pg_fetch_assoc($result);
    }
}

// Get all items for dropdown
$items_result = pg_query($con, "SELECT item_id, item_name FROM stock_item WHERE is_active = 't' ORDER BY item_name");
$items = pg_fetch_all($items_result) ?: [];

// Get all units for dropdown
$units_result = pg_query($con, "SELECT unit_id, unit_name FROM stock_item_unit ORDER BY unit_name");
$units = pg_fetch_all($units_result) ?: [];

// Get filter parameters
$filters = [
    'item_id' => $_GET['item_id'] ?? '',
    'unit_id' => $_GET['unit_id'] ?? '',
    'active_only' => $_GET['active_only'] ?? '1'
];

// Build WHERE clause for filtering
$where_conditions = [];

if (!empty($filters['item_id']) && is_numeric($filters['item_id'])) {
    $where_conditions[] = "sip.item_id = " . intval($filters['item_id']);
}

if (!empty($filters['unit_id']) && is_numeric($filters['unit_id'])) {
    $where_conditions[] = "sip.unit_id = " . intval($filters['unit_id']);
}

if ($filters['active_only'] === '1') {
    $today = date('Y-m-d');
    $where_conditions[] = "(sip.effective_end_date IS NULL OR sip.effective_end_date >= '$today')";
    $where_conditions[] = "sip.effective_start_date <= '$today'";
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get prices with item and unit details
$sql = "SELECT 
            sip.*,
            si.item_name,
            siu.unit_name,
            CASE 
                WHEN sip.effective_end_date IS NULL OR sip.effective_end_date >= CURRENT_DATE 
                     AND sip.effective_start_date <= CURRENT_DATE 
                THEN 'Active'
                ELSE 'Inactive'
            END as price_status
        FROM stock_item_price sip
        JOIN stock_item si ON sip.item_id = si.item_id
        JOIN stock_item_unit siu ON sip.unit_id = siu.unit_id
        $where_sql
        ORDER BY sip.effective_start_date DESC, sip.price_id DESC
        LIMIT 100";

$result = pg_query($con, $sql);
$prices = pg_fetch_all($result) ?: [];
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
    <?php include 'includes/meta.php' ?>
    
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
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
            background-color: #6c757d;
        }

        .badge-future {
            background-color: #ffc107;
            color: #000;
        }

        .badge-expired {
            background-color: #dc3545;
        }

        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }

        .current-price {
            background-color: #e8f5e9;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
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

                <!-- Price Form -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?= empty($price_data) ? 'Add New Price' : 'Edit Price' ?>
                                <?php if (!empty($item_details)): ?>
                                    for: <strong><?= htmlspecialchars($item_details['item_name']) ?></strong>
                                <?php endif; ?>
                            </h5>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="<?= empty($price_data) ? 'add' : 'edit' ?>">
                                <?php if (!empty($price_data)): ?>
                                    <input type="hidden" name="price_id" value="<?= $price_data['price_id'] ?>">
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="item_id" class="form-label">Item *</label>
                                            <select class="form-select" id="item_id" name="item_id" required
                                                <?= !empty($item_id) ? 'readonly style="background-color: #e9ecef;"' : '' ?>>
                                                <option value="">Select Item</option>
                                                <?php foreach ($items as $item): ?>
                                                    <option value="<?= $item['item_id'] ?>"
                                                        <?= ($item_id == $item['item_id'] || ($price_data['item_id'] ?? '') == $item['item_id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($item['item_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (!empty($item_id)): ?>
                                                <input type="hidden" name="item_id" value="<?= $item_id ?>">
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="unit_id" class="form-label">Unit *</label>
                                            <select class="form-select" id="unit_id" name="unit_id" required>
                                                <option value="">Select Unit</option>
                                                <?php foreach ($units as $unit): ?>
                                                    <option value="<?= $unit['unit_id'] ?>"
                                                        <?= ($price_data['unit_id'] ?? '') == $unit['unit_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($unit['unit_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="price_per_unit" class="form-label">Price Per Unit *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" class="form-control" id="price_per_unit" name="price_per_unit"
                                                    step="0.01" min="0" required
                                                    value="<?= number_format($price_data['price_per_unit'] ?? 0, 2) ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="unit_quantity" class="form-label">Quantity in Unit (Optional)</label>
                                            <input type="number" class="form-control" id="unit_quantity" name="unit_quantity"
                                                step="0.001" min="0"
                                                value="<?= $price_data['unit_quantity'] ?? '' ?>">
                                            <small class="text-muted">e.g., 1 kg = 1000 grams</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="discount_percentage" class="form-label">Discount % (Optional)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="discount_percentage" name="discount_percentage"
                                                    step="0.01" min="0" max="100"
                                                    value="<?= $price_data['discount_percentage'] ?? '' ?>">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="effective_start_date" class="form-label">Effective Start Date *</label>
                                            <input type="date" class="form-control" id="effective_start_date" name="effective_start_date"
                                                required value="<?= $price_data['effective_start_date'] ?? date('Y-m-d') ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="effective_end_date" class="form-label">Effective End Date (Optional)</label>
                                            <input type="date" class="form-control" id="effective_end_date" name="effective_end_date"
                                                value="<?= $price_data['effective_end_date'] ?? '' ?>">
                                            <small class="text-muted">Leave empty for ongoing price</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="original_price" class="form-label">Original Price (Optional)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" class="form-control" id="original_price" name="original_price"
                                                    step="0.01" min="0"
                                                    value="<?= $price_data['original_price'] ?? '' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <?= empty($price_data) ? 'Add Price' : 'Update Price' ?>
                                    </button>
                                    <?php if (!empty($price_data)): ?>
                                        <a href="item_prices_management.php" class="btn btn-secondary">Cancel</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="col-12">
                    <div class="filter-card">
                        <h5 class="card-title">Filter Prices</h5>
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-4">
                                <label for="item_id" class="form-label">Item</label>
                                <select class="form-select" id="item_id" name="item_id">
                                    <option value="">All Items</option>
                                    <?php foreach ($items as $item): ?>
                                        <option value="<?= $item['item_id'] ?>"
                                            <?= $filters['item_id'] == $item['item_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($item['item_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="unit_id" class="form-label">Unit</label>
                                <select class="form-select" id="unit_id" name="unit_id">
                                    <option value="">All Units</option>
                                    <?php foreach ($units as $unit): ?>
                                        <option value="<?= $unit['unit_id'] ?>"
                                            <?= $filters['unit_id'] == $unit['unit_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($unit['unit_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="active_only" class="form-label">Status</label>
                                <select class="form-select" id="active_only" name="active_only">
                                    <option value="1" <?= $filters['active_only'] === '1' ? 'selected' : '' ?>>Active Prices Only</option>
                                    <option value="0" <?= $filters['active_only'] === '0' ? 'selected' : '' ?>>All Prices</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="btn-group" role="group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-funnel"></i> Apply
                                    </button>
                                    <a href="item_prices_management.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Prices List -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Price History</h5>

                            <?php if (count($prices) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Unit</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Discount</th>
                                                <th>Effective Period</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($prices as $price):
                                                $isCurrent = $price['price_status'] === 'Active';
                                                $rowClass = $isCurrent ? 'current-price' : '';
                                            ?>
                                                <tr class="<?= $rowClass ?>">
                                                    <td><?= htmlspecialchars($price['item_name']) ?></td>
                                                    <td><?= htmlspecialchars($price['unit_name']) ?></td>
                                                    <td>
                                                        <strong>₹<?= number_format($price['price_per_unit'], 2) ?></strong>
                                                        <?php if (!empty($price['original_price'])): ?>
                                                            <br><small class="text-muted text-decoration-line-through">
                                                                ₹<?= number_format($price['original_price'], 2) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= !empty($price['unit_quantity']) ?
                                                            number_format($price['unit_quantity'], 3) : '1' ?>
                                                    </td>
                                                    <td>
                                                        <?= !empty($price['discount_percentage']) ?
                                                            number_format($price['discount_percentage'], 1) . '%' : '—' ?>
                                                    </td>
                                                    <td>
                                                        <?= date('d M Y', strtotime($price['effective_start_date'])) ?>
                                                        <?php if (!empty($price['effective_end_date'])): ?>
                                                            <br>to <?= date('d M Y', strtotime($price['effective_end_date'])) ?>
                                                        <?php else: ?>
                                                            <br>to <em>Ongoing</em>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?=
                                                                            $price['price_status'] === 'Active' ? 'badge-active' : ($price['effective_start_date'] > date('Y-m-d') ? 'badge-future' : 'badge-expired')
                                                                            ?>">
                                                            <?= $price['price_status'] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="?edit=<?= $price['price_id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <?php if ($price['price_status'] === 'Active'): ?>
                                                                <form method="POST" style="display:inline;"
                                                                    onsubmit="return confirm('Deactivate this price?');">
                                                                    <input type="hidden" name="action" value="deactivate_current">
                                                                    <input type="hidden" name="price_id" value="<?= $price['price_id'] ?>">
                                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Deactivate">
                                                                        <i class="bi bi-calendar-x"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                            <form method="POST" style="display:inline;"
                                                                onsubmit="return confirm('Delete this price record?');">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="price_id" value="<?= $price['price_id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="text-center text-muted mt-3">
                                    Showing <?= count($prices) ?> price records
                                    <?php if ($filters['active_only'] === '1'): ?>
                                        (active only)
                                    <?php endif; ?>
                                </div>

                            <?php else: ?>
                                <div class="alert alert-info">
                                    No price records found.
                                    <?php if (!empty($filters['item_id']) || !empty($filters['unit_id'])): ?>
                                        Try changing your filters or <a href="item_prices_management.php">clear all filters</a>.
                                    <?php else: ?>
                                        <a href="?add=">Add your first price</a>.
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

            // Calculate discounted price
            const priceInput = document.getElementById('price_per_unit');
            const discountInput = document.getElementById('discount_percentage');
            const originalInput = document.getElementById('original_price');

            function calculateOriginalPrice() {
                if (priceInput.value && discountInput.value) {
                    const price = parseFloat(priceInput.value);
                    const discount = parseFloat(discountInput.value);
                    if (discount > 0 && discount <= 100) {
                        const original = price / (1 - discount / 100);
                        originalInput.value = original.toFixed(2);
                    }
                }
            }

            if (priceInput && discountInput && originalInput) {
                priceInput.addEventListener('change', calculateOriginalPrice);
                discountInput.addEventListener('change', calculateOriginalPrice);
            }
        });
    </script>
</body>

</html>