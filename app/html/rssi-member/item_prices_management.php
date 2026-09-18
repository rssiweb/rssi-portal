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

// Never let PHP warnings corrupt the JSON response for AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');
}

// -----------------------------------------------------------------------------
// Initialize variables
// -----------------------------------------------------------------------------
$action   = $_POST['action']   ?? '';
$price_id = $_POST['price_id'] ?? '';
$message  = '';
$error    = '';

// Form-scoped item id (Add/Edit Price form only)
$form_item_id = '';

// Flash message after PRG redirect
if (!empty($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
if (!empty($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// -----------------------------------------------------------------------------
// Handle form actions
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'add' || $action === 'edit') {

            // ---- Required fields ----
            foreach (['item_id', 'unit_id', 'effective_start_date'] as $field) {
                if (empty(trim($_POST[$field] ?? ''))) {
                    throw new Exception("Field '$field' is required");
                }
            }

            $is_dynamic = isset($_POST['is_dynamic_price']);

            if (!$is_dynamic && empty(trim($_POST['price_per_unit'] ?? ''))) {
                throw new Exception("Field 'price_per_unit' is required for fixed prices");
            }

            $item_id_v    = intval($_POST['item_id']);
            $unit_id_v    = intval($_POST['unit_id']);
            $start_date   = $_POST['effective_start_date'];
            $end_date_raw = trim($_POST['effective_end_date'] ?? '');
            $end_date     = $end_date_raw !== '' ? $end_date_raw : null;

            // Guard: end date must be >= start date
            if ($end_date !== null && $end_date < $start_date) {
                throw new Exception("Effective End Date cannot be earlier than Effective Start Date.");
            }

            $price_per_unit_v = $is_dynamic ? null : floatval($_POST['price_per_unit']);
            $unit_quantity_v  = (!$is_dynamic && !empty($_POST['unit_quantity'])) ? floatval($_POST['unit_quantity']) : null;
            $discount_pct_v   = (!$is_dynamic && !empty($_POST['discount_percentage'])) ? floatval($_POST['discount_percentage']) : null;
            $original_price_v = (!$is_dynamic && !empty($_POST['original_price'])) ? floatval($_POST['original_price']) : null;
            $is_fixed_price_v = $is_dynamic ? 'false' : 'true';

            // -----------------------------------------------------------------
            // Overlap check
            // -----------------------------------------------------------------
            $exclude_id = ($action === 'edit' && $price_id) ? intval($price_id) : 0;

            $overlap_sql = "
                SELECT price_id, price_per_unit, effective_start_date, effective_end_date, is_fixed_price
                FROM stock_item_price
                WHERE item_id = \$1
                  AND unit_id = \$2
                  AND price_id <> \$3
                  AND (effective_end_date IS NULL OR effective_end_date >= \$4::date)
                  AND effective_start_date <= COALESCE(\$5::date, DATE '9999-12-31')
                ORDER BY effective_start_date ASC
                LIMIT 1
            ";
            $overlap_res = @pg_query_params($con, $overlap_sql, [
                $item_id_v,
                $unit_id_v,
                $exclude_id,
                $start_date,
                $end_date,
            ]);
            if ($overlap_res === false) {
                throw new Exception("Overlap check failed: " . pg_last_error($con));
            }
            $conflict = pg_fetch_assoc($overlap_res);

            if ($conflict && empty($_POST['confirm_end_date'])) {
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                }
                echo json_encode([
                    'status'    => 'conflict',
                    'message'   => 'This item already has an active price covering the selected period. Do you want to end the previous price the day before the new one starts, and create this as a new price?',
                    'conflict'  => $conflict,
                    'new_start' => $start_date,
                ]);
                exit;
            }

            // ---- User confirmed: close conflicting row(s) ----
            if ($conflict && !empty($_POST['confirm_end_date'])) {
                $conflict_start = $conflict['effective_start_date'];

                if ($conflict_start < $start_date) {
                    $day_before = date('Y-m-d', strtotime($start_date . ' -1 day'));
                    if ($day_before < $conflict_start) {
                        $day_before = $conflict_start;
                    }
                    pg_query_params(
                        $con,
                        "UPDATE stock_item_price
                            SET effective_end_date = \$1::date,
                                updated_at = NOW()
                          WHERE price_id = \$2",
                        [$day_before, intval($conflict['price_id'])]
                    );
                } else {
                    $day_before = date('Y-m-d', strtotime($start_date . ' -1 day'));
                    if ($day_before >= $conflict_start) {
                        pg_query_params(
                            $con,
                            "UPDATE stock_item_price
                                SET effective_end_date = \$1::date,
                                    updated_at = NOW()
                              WHERE price_id = \$2",
                            [$day_before, intval($conflict['price_id'])]
                        );
                    } else {
                        pg_query_params(
                            $con,
                            "DELETE FROM stock_item_price WHERE price_id = \$1",
                            [intval($conflict['price_id'])]
                        );
                    }
                }
            }

            // -----------------------------------------------------------------
            // If creating a new version of an existing price, release the
            // unique-constraint slot held by the source row.
            // -----------------------------------------------------------------
            if ($action === 'edit' && !empty($price_id)) {
                $src_id = intval($price_id);

                $src_q = @pg_query_params(
                    $con,
                    "SELECT effective_start_date FROM stock_item_price WHERE price_id = \$1",
                    [$src_id]
                );
                if ($src_q && ($src = pg_fetch_assoc($src_q))) {
                    $src_start = $src['effective_start_date'];

                    if ($src_start === $start_date) {
                        pg_query_params(
                            $con,
                            "DELETE FROM stock_item_price WHERE price_id = \$1",
                            [$src_id]
                        );
                    } else {
                        $day_before_edit = date('Y-m-d', strtotime($start_date . ' -1 day'));
                        if ($day_before_edit >= $src_start) {
                            pg_query_params(
                                $con,
                                "UPDATE stock_item_price
                                    SET effective_end_date = \$1::date,
                                        updated_at = NOW()
                                  WHERE price_id = \$2
                                    AND (effective_end_date IS NULL OR effective_end_date > \$1::date)",
                                [$day_before_edit, $src_id]
                            );
                        }
                    }
                }
            }

            // -----------------------------------------------------------------
            // ALWAYS INSERT A NEW ROW
            // -----------------------------------------------------------------
            $insert_sql = "
                INSERT INTO stock_item_price
                    (item_id, unit_id, price_per_unit, effective_start_date, effective_end_date,
                     unit_quantity, discount_percentage, original_price, is_fixed_price, created_at)
                VALUES
                    (\$1, \$2, \$3, \$4::date, \$5::date, \$6, \$7, \$8, \$9, NOW())
            ";
            $insert_res = @pg_query_params($con, $insert_sql, [
                $item_id_v,
                $unit_id_v,
                $price_per_unit_v,
                $start_date,
                $end_date,
                $unit_quantity_v,
                $discount_pct_v,
                $original_price_v,
                $is_fixed_price_v,
            ]);

            if ($insert_res) {
                $successMsg = ($action === 'edit')
                    ? "New price version saved. Previous price closed."
                    : "Price added successfully!";

                if ($isAjax) {
                    if (!headers_sent()) {
                        header('Content-Type: application/json');
                    }
                    echo json_encode(['status' => 'success', 'message' => $successMsg]);
                    exit;
                }

                // PRG fallback (non-AJAX)
                $_SESSION['flash_message'] = $successMsg;
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $pg_err = pg_last_error($con);
                if (strpos($pg_err, 'unique_price_period') !== false) {
                    throw new Exception("A price already exists for this item + unit starting on this exact date. " .
                        "Please choose a different start date, or edit that existing price instead.");
                }
                if (strpos($pg_err, 'no_overlapping_prices') !== false) {
                    throw new Exception("The new price period overlaps an existing price. " .
                        "Please check the dates for this item + unit.");
                }
                throw new Exception("Failed to save price: " . $pg_err);
            }
        } elseif ($action === 'delete' && $price_id) {
            $sql = "DELETE FROM stock_item_price WHERE price_id = " . intval($price_id);
            $result = pg_query($con, $sql);
            if ($result) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success', 'message' => 'Price deleted successfully!']);
                    exit;
                }
                $_SESSION['flash_message'] = "Price deleted successfully!";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            throw new Exception("Failed to delete price: " . pg_last_error($con));
        } elseif ($action === 'deactivate_current' && $price_id) {
            $today = date('Y-m-d');
            $sql = "UPDATE stock_item_price SET effective_end_date = '$today' WHERE price_id = " . intval($price_id);
            $result = pg_query($con, $sql);
            if ($result) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success', 'message' => 'Price deactivated successfully!']);
                    exit;
                }
                $_SESSION['flash_message'] = "Price deactivated successfully!";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            throw new Exception("Failed to deactivate price: " . pg_last_error($con));
        }
    } catch (Exception $e) {
        if ($isAjax) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
        $error = $e->getMessage();
    }
}

// -----------------------------------------------------------------------------
// Load data for editing
// -----------------------------------------------------------------------------
$price_data     = [];
$edit_source_id = null;
$edit_item      = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = pg_query($con, "SELECT * FROM stock_item_price WHERE price_id = $edit_id");
    if ($result && pg_num_rows($result) > 0) {
        $source = pg_fetch_assoc($result);
        $edit_source_id = intval($source['price_id']);
        $form_item_id = $source['item_id'];
        $price_data = $source;
    }
} elseif (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $form_item_id = intval($_GET['add']);
}

// POST-refill only when the request did NOT succeed (so after a validation
// error we keep what the user typed; after success we already redirected).
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['item_id'])
    && $action !== 'add' && $action !== 'edit'
) {
    $form_item_id = intval($_POST['item_id']);
}

// -----------------------------------------------------------------------------
// Item details for the Add/Edit form
// -----------------------------------------------------------------------------
$item_details = [];
if ($form_item_id) {
    $result = pg_query($con, "SELECT item_id, item_name FROM stock_item WHERE item_id = " . intval($form_item_id));
    if ($result && pg_num_rows($result) > 0) {
        $item_details = pg_fetch_assoc($result);
    }
}

if (!empty($price_data['item_id'])) {
    $r = pg_query($con, "SELECT item_id, item_name FROM stock_item WHERE item_id = " . intval($price_data['item_id']));
    if ($r && pg_num_rows($r) > 0) {
        $edit_item = pg_fetch_assoc($r);
    }
}

// -----------------------------------------------------------------------------
// Units dropdown (filter + display joins)
// -----------------------------------------------------------------------------
$units_result = pg_query($con, "SELECT unit_id, unit_name FROM stock_item_unit ORDER BY unit_name");
$units = pg_fetch_all($units_result) ?: [];

// -----------------------------------------------------------------------------
// Filters — item_id is an ARRAY
// -----------------------------------------------------------------------------
$filters = [
    'item_id' => $_GET['item_id'] ?? [],
    'unit_id' => $_GET['unit_id'] ?? '',
    'status'  => $_GET['status']  ?? '',
];

// Normalise item_id to a clean int array
if (!is_array($filters['item_id'])) {
    $filters['item_id'] = ($filters['item_id'] === '' || $filters['item_id'] === null)
        ? []
        : [$filters['item_id']];
}
$item_ids = array_values(array_filter(array_map('intval', $filters['item_id']), fn($v) => $v > 0));

$where_conditions = [];

if (!empty($item_ids)) {
    $where_conditions[] = "sip.item_id = ANY(ARRAY[" . implode(',', $item_ids) . "]::int[])";
}
if (!empty($filters['unit_id']) && is_numeric($filters['unit_id'])) {
    $where_conditions[] = "sip.unit_id = " . intval($filters['unit_id']);
}

$today = date('Y-m-d');
switch ($filters['status']) {
    case 'active':
        $where_conditions[] = "(sip.effective_end_date IS NULL OR sip.effective_end_date >= '$today')";
        $where_conditions[] = "sip.effective_start_date <= '$today'";
        break;
    case 'inactive':
        $where_conditions[] = "sip.effective_end_date IS NOT NULL AND sip.effective_end_date < '$today'";
        break;
    case 'future':
        $where_conditions[] = "sip.effective_start_date > '$today'";
        break;
    case 'all':
    default:
        break;
}

$where_sql = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$hasFilter = !empty($item_ids)
    || !empty($filters['unit_id'])
    || $filters['status'] !== '';

$prices = [];
if ($hasFilter) {
    $sql = "SELECT
                sip.*,
                si.item_name,
                siu.unit_name,
                CASE
                    WHEN (sip.effective_end_date IS NULL OR sip.effective_end_date >= CURRENT_DATE)
                         AND sip.effective_start_date <= CURRENT_DATE
                    THEN 'Active'
                    WHEN sip.effective_start_date > CURRENT_DATE
                    THEN 'Future'
                    ELSE 'Expired'
                END as price_status
            FROM stock_item_price sip
            JOIN stock_item si ON sip.item_id = si.item_id
            JOIN stock_item_unit siu ON sip.unit_id = siu.unit_id
            $where_sql
            ORDER BY sip.effective_start_date DESC, sip.price_id DESC
            LIMIT 200";

    $result = pg_query($con, $sql);
    $prices = pg_fetch_all($result) ?: [];
}

// Preselected items for the filter <select>
$filter_items_display = [];
if (!empty($item_ids)) {
    $r = pg_query(
        $con,
        "SELECT item_id, item_name
         FROM stock_item
         WHERE item_id IN (" . implode(',', $item_ids) . ")
         ORDER BY item_name"
    );
    $filter_items_display = pg_fetch_all($r) ?: [];
}
?>
<!doctype html>
<html lang="en">

<head>
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

    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">

    <style>
        .filter-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, .1);
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

        .select2-container {
            width: 100% !important;
        }

        #filter_item_id+.select2-container .select2-selection--multiple {
            min-height: 38px;
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
        </div>

        <section class="section dashboard">
            <div class="row">

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Price form -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php if (!empty($price_data) && $edit_source_id): ?>
                                    New Price Version
                                <?php else: ?>
                                    Add New Price
                                <?php endif; ?>
                                <?php if (!empty($item_details)): ?>
                                    for: <strong><?= htmlspecialchars($item_details['item_name']) ?></strong>
                                <?php endif; ?>
                            </h5>

                            <?php if ($edit_source_id): ?>
                                <div class="alert alert-info py-2">
                                    <i class="bi bi-info-circle"></i>
                                    You are creating a <strong>new price entry</strong>. The old price
                                    (#<?= $edit_source_id ?>) will be closed automatically the day before the new start date.
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" id="price-form">
                                <input type="hidden" name="action" value="<?= empty($price_data) ? 'add' : 'edit' ?>">
                                <?php if ($edit_source_id): ?>
                                    <input type="hidden" name="price_id" value="<?= $edit_source_id ?>">
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Item *</label>
                                            <?php if (!empty($item_details)): ?>
                                                <input type="text" class="form-control"
                                                    value="<?= htmlspecialchars($item_details['item_name']) ?>" disabled>
                                                <input type="hidden" name="item_id" id="item_id"
                                                    value="<?= (int)$item_details['item_id'] ?>">
                                            <?php else: ?>
                                                <select class="form-select" id="item_id" name="item_id" required></select>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="unit_id" class="form-label">Unit *</label>

                                            <?php if (!empty($price_data['unit_id'])):
                                                // Edit flow: unit is fixed to the source price's unit
                                                $selected_unit_name = '';
                                                foreach ($units as $u) {
                                                    if ($u['unit_id'] == $price_data['unit_id']) {
                                                        $selected_unit_name = $u['unit_name'];
                                                        break;
                                                    }
                                                }
                                            ?>
                                                <input type="text" class="form-control"
                                                    value="<?= htmlspecialchars($selected_unit_name ?: ('unit #' . $price_data['unit_id'])) ?>"
                                                    disabled>
                                                <input type="hidden" name="unit_id" id="unit_id" value="<?= (int)$price_data['unit_id'] ?>">
                                                <small class="text-muted">Locked to the source price's unit.</small>
                                            <?php else: ?>
                                                <select class="form-select" id="unit_id" name="unit_id" required></select>
                                                <small class="text-muted" id="unit-hint">
                                                    Pick an item first — the unit is set from that item's stock records.
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_dynamic_price"
                                        name="is_dynamic_price" value="1"
                                        <?= (!empty($price_data) && ($price_data['is_fixed_price'] ?? 't') === 'f') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_dynamic_price">
                                        Dynamic Price
                                        <small class="text-muted">(price varies — no fixed value)</small>
                                    </label>
                                </div>

                                <div class="row" id="price-fields-row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="price_per_unit" class="form-label">Price Per Unit *</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" class="form-control price-field"
                                                    id="price_per_unit" name="price_per_unit"
                                                    step="0.01" min="0"
                                                    value="<?= isset($price_data['price_per_unit']) && $price_data['price_per_unit'] !== null
                                                                ? number_format((float)$price_data['price_per_unit'], 2)
                                                                : '' ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="unit_quantity" class="form-label">Quantity in Unit (Optional)</label>
                                            <input type="number" class="form-control price-field"
                                                id="unit_quantity" name="unit_quantity"
                                                step="0.001" min="0"
                                                value="<?= $price_data['unit_quantity'] ?? '' ?>">
                                            <small class="text-muted">e.g., 1 kg = 1000 grams</small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="discount_percentage" class="form-label">Discount % (Optional)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control price-field"
                                                    id="discount_percentage" name="discount_percentage"
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
                                            <label for="original_price" class="form-label">Original Price (Optional)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" class="form-control price-field"
                                                    id="original_price" name="original_price"
                                                    step="0.01" min="0"
                                                    value="<?= $price_data['original_price'] ?? '' ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="effective_start_date" class="form-label">Effective Start Date *</label>
                                            <input type="date" class="form-control" id="effective_start_date"
                                                name="effective_start_date" required
                                                value="<?= $price_data['effective_start_date'] ?? date('Y-m-d') ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="effective_end_date" class="form-label">Effective End Date (Optional)</label>
                                            <input type="date" class="form-control" id="effective_end_date"
                                                name="effective_end_date"
                                                value="<?= $price_data['effective_end_date'] ?? '' ?>">
                                            <small class="text-muted">Leave empty for ongoing price</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary" id="price-submit-btn">
                                        <?= empty($price_data) ? 'Add Price' : 'Save New Version' ?>
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
                        <form method="GET" action="" class="row g-3" id="filter-form">
                            <div class="col-md-4">
                                <label class="form-label">Item(s)</label>
                                <select class="form-select" id="filter_item_id" name="item_id[]" multiple="multiple">
                                    <?php foreach ($filter_items_display as $fi): ?>
                                        <option value="<?= (int)$fi['item_id'] ?>" selected>
                                            <?= htmlspecialchars($fi['item_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted" id="filter-item-hint">
                                    Leave empty to search all items.
                                </small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Unit</label>
                                <select class="form-select" id="filter_unit_id" name="unit_id">
                                    <?php if (!empty($filters['unit_id']) && is_numeric($filters['unit_id'])):
                                        $selected_filter_unit_name = '';
                                        foreach ($units as $u) {
                                            if ($u['unit_id'] == $filters['unit_id']) {
                                                $selected_filter_unit_name = $u['unit_name'];
                                                break;
                                            }
                                        }
                                        if ($selected_filter_unit_name):
                                    ?>
                                            <option value="<?= (int)$filters['unit_id'] ?>" selected>
                                                <?= htmlspecialchars($selected_filter_unit_name) ?>
                                            </option>
                                    <?php endif;
                                    endif; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="" <?= $filters['status'] === '' ? 'selected' : '' ?>>Select status</option>
                                    <option value="active" <?= $filters['status'] === 'active'   ? 'selected' : '' ?>>Active only</option>
                                    <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive only</option>
                                    <option value="future" <?= $filters['status'] === 'future'   ? 'selected' : '' ?>>Future only</option>
                                    <option value="all" <?= $filters['status'] === 'all'      ? 'selected' : '' ?>>All prices</option>
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

                <!-- Price list -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Price History</h5>

                                <?php if (count($prices) > 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="export-prices-btn">
                                        <i class="bi bi-file-earmark-excel"></i> Export
                                    </button>
                                <?php endif; ?>
                            </div>

                            <?php if (count($prices) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Unit</th>
                                                <th>Price</th>
                                                <th>Type</th>
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
                                                $isDynamic = ($price['is_fixed_price'] === 'f');
                                            ?>
                                                <tr class="<?= $isCurrent ? 'current-price' : '' ?>">
                                                    <td><?= htmlspecialchars($price['item_name']) ?></td>
                                                    <td><?= htmlspecialchars($price['unit_name']) ?></td>
                                                    <td>
                                                        <?php if ($isDynamic): ?>
                                                            <em class="text-muted">Varies</em>
                                                        <?php else: ?>
                                                            <strong>₹<?= number_format((float)$price['price_per_unit'], 2) ?></strong>
                                                            <?php if (!empty($price['original_price'])): ?>
                                                                <br><small class="text-muted text-decoration-line-through">
                                                                    ₹<?= number_format((float)$price['original_price'], 2) ?>
                                                                </small>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($isDynamic): ?>
                                                            <span class="badge bg-info text-dark">Dynamic</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Fixed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= !empty($price['unit_quantity'])
                                                            ? number_format((float)$price['unit_quantity'], 3)
                                                            : '—' ?>
                                                    </td>
                                                    <td>
                                                        <?= !empty($price['discount_percentage'])
                                                            ? number_format((float)$price['discount_percentage'], 1) . '%'
                                                            : '—' ?>
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
                                                        <?php
                                                        $badge = 'badge-inactive';
                                                        if ($price['price_status'] === 'Active') $badge = 'badge-active';
                                                        elseif ($price['price_status'] === 'Future') $badge = 'badge-future';
                                                        elseif ($price['price_status'] === 'Expired') $badge = 'badge-expired';
                                                        ?>
                                                        <span class="badge <?= $badge ?>">
                                                            <?= htmlspecialchars($price['price_status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons d-flex gap-1 flex-wrap">
                                                            <a href="?edit=<?= $price['price_id'] ?>"
                                                                class="btn btn-sm btn-outline-primary"
                                                                title="Create new price version">
                                                                <i class="bi bi-plus-square"></i>
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
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <?php if (!$hasFilter): ?>
                                        <i class="bi bi-funnel"></i>
                                        Please select at least one filter (Item, Unit, or Status) and click <strong>Apply</strong> to view price records.
                                    <?php else: ?>
                                        No price records found.
                                        Try changing your filters or <a href="item_prices_management.php">clear all filters</a>.
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(function() {

            // -----------------------------------------------------------------
            // Select2 configs
            // -----------------------------------------------------------------
            function itemSelect2Config() {
                return {
                    placeholder: "Type to search item…",
                    allowClear: true,
                    minimumInputLength: 2,
                    ajax: {
                        url: 'search_products.php',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                add_stock: 'true',
                                search: params.term
                            };
                        },
                        processResults: function(data) {
                            const rows = data.results || [];
                            return {
                                results: rows.map(r => ({
                                    id: r.id,
                                    text: r.name
                                }))
                            };
                        },
                        cache: true
                    }
                };
            }

            function unitSelect2Config() {
                return {
                    placeholder: "Type to search unit…",
                    allowClear: true,
                    minimumInputLength: 0,
                    ajax: {
                        url: 'unit_search.php',
                        dataType: 'json',
                        delay: 200,
                        data: function(params) {
                            return {
                                q: params.term || ''
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results || []
                            };
                        },
                        cache: true
                    }
                };
            }

            // Filter item picker — search by item name (like student-id/name search)
            $('#filter_item_id').select2({
                ajax: {
                    url: 'search_products.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            add_stock: 'true',
                            search: params.term
                        };
                    },
                    processResults: function(data) {
                        const rows = data.results || [];
                        return {
                            results: rows.map(r => ({
                                id: r.id,
                                text: r.name
                            }))
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Search by item name',
                multiple: true,
                closeOnSelect: true, // ← closes + clears search after each pick
                width: '100%'
            });

            // Render pre-selected <option selected> as tags on page load
            $('#filter_item_id').trigger('change.select2');

            // Form item + unit pickers
            const formItemSelect = $('#item_id').is('select') ? $('#item_id') : null;
            if (formItemSelect) {
                formItemSelect.select2(itemSelect2Config());
                <?php if ($edit_item): ?>
                    const presetItem = new Option(
                        <?= json_encode($edit_item['item_name']) ?>,
                        <?= (int)$edit_item['item_id'] ?>,
                        true, true
                    );
                    formItemSelect.append(presetItem).trigger('change');
                <?php endif; ?>
            }
            // $('#unit_id').select2(unitSelect2Config());
            $('#filter_unit_id').select2(unitSelect2Config());

            // -----------------------------------------------------------------
            // Add-form: Unit is driven by the chosen Item.
            // The unit options come from the item's stock_add records.
            // -----------------------------------------------------------------
            const unitSelectEl = document.getElementById('unit_id');

            // Only touch it if it's an actual <select> (add mode).
            // In edit mode it's a hidden input and we leave it alone.
            if (unitSelectEl && unitSelectEl.tagName === 'SELECT') {

                // Select2 for the (constrained) unit picker
                $('#unit_id').select2({
                    placeholder: 'Unit will be set from item',
                    minimumInputLength: 0,
                    width: '100%',
                    ajax: {
                        // We don't need a remote search here — options come from the item.
                        // Return nothing so the user can't type arbitrary units.
                        transport: function(params, success) {
                            success({
                                results: []
                            });
                        }
                    }
                });

                // Helper to render a single unit option and select it
                function setUnitOptions(unitList) {
                    const $unit = $('#unit_id');
                    const $hint = $('#unit-hint');

                    // Clear existing options/tags
                    $unit.val(null).trigger('change');

                    if (!unitList || unitList.length === 0) {
                        $hint.text('This item has no stock records yet, so no unit can be assigned.');
                        $hint.removeClass('text-muted').addClass('text-danger');
                        $unit.prop('disabled', true);
                        return;
                    }

                    // Rebuild options from the item's units
                    $unit.empty();
                    unitList.forEach(u => {
                        $unit.append(new Option(u.name, u.id, false, false));
                    });

                    $hint.text('Unit is set automatically from the item\'s stock records.')
                        .removeClass('text-danger').addClass('text-muted');

                    if (unitList.length === 1) {
                        // Only one unit → lock it
                        $unit.val(String(unitList[0].id)).trigger('change');
                        $unit.prop('disabled', true);

                        // Use a hidden input to actually submit the value
                        // (disabled selects are not submitted)
                        ensureHiddenUnitField(unitList[0].id);
                    } else {
                        // Multiple units → let user pick, but only among these
                        $unit.prop('disabled', false);
                        removeHiddenUnitField();
                        // Auto-select the first one as a sensible default
                        $unit.val(String(unitList[0].id)).trigger('change');
                    }
                }

                // When the item picker changes, fetch its units and set them
                $('#item_id').on('change', function() {
                    const itemId = $(this).val();
                    if (!itemId) {
                        setUnitOptions([]);
                        return;
                    }

                    $.ajax({
                        url: 'check_items_unit.php',
                        method: 'POST',
                        data: {
                            item_id: itemId
                        },
                        dataType: 'json',
                        success: function(resp) {
                            if (resp.error) {
                                console.error(resp.error);
                                setUnitOptions([]);
                                return;
                            }
                            setUnitOptions(resp.units || []);
                        },
                        error: function(xhr) {
                            console.error('check_item_units failed', xhr.status, xhr.statusText);
                            setUnitOptions([]);
                        }
                    });
                });

                // Initial state — if item is already selected (e.g. via ?add=), fetch now
                const initialItemId = $('#item_id').val();
                if (initialItemId) {
                    $('#item_id').trigger('change');
                }

                // Ensure a hidden field carries the unit value when the select is disabled
                function ensureHiddenUnitField(unitId) {
                    let $h = $('#unit_id_hidden');
                    if ($h.length === 0) {
                        $h = $('<input>', {
                            type: 'hidden',
                            id: 'unit_id_hidden',
                            name: 'unit_id',
                            value: unitId
                        });
                        $('#unit_id').after($h);
                    } else {
                        $h.val(unitId);
                    }
                }

                function removeHiddenUnitField() {
                    $('#unit_id_hidden').remove();
                }
            }

            // -----------------------------------------------------------------
            // Dynamic price checkbox
            // -----------------------------------------------------------------
            const dynamicChk = document.getElementById('is_dynamic_price');
            const priceFields = document.querySelectorAll('.price-field');

            function applyDynamicState() {
                const isDynamic = dynamicChk.checked;
                priceFields.forEach(f => {
                    if (isDynamic) {
                        if (f.dataset.prevValue === undefined) f.dataset.prevValue = f.value;
                        f.value = '';
                        f.disabled = true;
                        f.required = false;
                    } else {
                        f.disabled = false;
                        if (f.id === 'price_per_unit') {
                            f.required = true;
                            if (f.dataset.prevValue) f.value = f.dataset.prevValue;
                        }
                    }
                });
            }
            if (dynamicChk) {
                dynamicChk.addEventListener('change', applyDynamicState);
                applyDynamicState();
            }

            // -----------------------------------------------------------------
            // Export Price History
            // -----------------------------------------------------------------
            const exportBtn = document.getElementById('export-prices-btn');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    const table = document.querySelector('.table-responsive table');
                    if (!table) {
                        Swal.fire('Nothing to export', 'No price data available.', 'info');
                        return;
                    }

                    const rows = [];
                    const headers = Array.from(table.querySelectorAll('thead th'))
                        .map(th => cleanCell(th.innerText));
                    rows.push(headers);

                    table.querySelectorAll('tbody tr').forEach(tr => {
                        const cells = Array.from(tr.querySelectorAll('td')).map(td => cleanCell(td.innerText));
                        rows.push(cells);
                    });

                    const csv = rows.map(r =>
                        r.map(v => '"' + String(v).replace(/"/g, '""') + '"').join(',')
                    ).join('\r\n');

                    const blob = new Blob(['\uFEFF' + csv], {
                        type: 'text/csv;charset=utf-8;'
                    });
                    const url = URL.createObjectURL(blob);

                    const ts = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'price_history_' + ts + '.csv';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);

                    Swal.fire({
                        icon: 'success',
                        title: 'Exported',
                        text: 'Price history downloaded as CSV.',
                        timer: 1800,
                        showConfirmButton: false
                    });
                });
            }

            function cleanCell(text) {
                return String(text).replace(/\s+/g, ' ').replace(/^"|"$/g, '').trim();
            }

            // -----------------------------------------------------------------
            // Price form submit with conflict confirmation
            // -----------------------------------------------------------------
            const priceForm = document.getElementById('price-form');

            priceForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                if (!priceForm.checkValidity()) {
                    priceForm.reportValidity();
                    return;
                }

                const fd = new FormData(priceForm);

                async function postForm(extra = {}) {
                    const body = new FormData();
                    for (const [k, v] of fd.entries()) body.append(k, v);
                    for (const [k, v] of Object.entries(extra)) body.append(k, v);

                    return fetch('', {
                        method: 'POST',
                        body,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                }

                // Pass 1: conflict check
                Swal.fire({
                    title: 'Checking…',
                    text: 'Checking for conflicts, please wait.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading()
                });

                let res, ct;
                try {
                    res = await postForm();
                    ct = res.headers.get('content-type') || '';
                } catch (err) {
                    Swal.close();
                    Swal.fire('Network error', err.message, 'error');
                    return;
                }

                const bodyText1 = await res.text();

                if (!ct.includes('application/json')) {
                    Swal.close();
                    Swal.fire('Unexpected response', 'Server returned non-JSON. Please reload.', 'error');
                    return;
                }

                let data;
                try {
                    data = JSON.parse(bodyText1);
                } catch (err) {
                    Swal.close();
                    Swal.fire('Error', 'Invalid JSON from server.', 'error');
                    return;
                }

                if (data.status === 'error') {
                    Swal.close();
                    Swal.fire('Error', data.message, 'error');
                    return;
                }

                // Successful add/edit → reload with flash
                if (data.status === 'success') {
                    Swal.close();
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: data.message,
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = window.location.pathname + window.location.search;
                    });
                    return;
                }

                // Conflict path
                if (data.status === 'conflict') {
                    const prev = data.conflict;
                    const prevPrice = prev.price_per_unit === null ?
                        'Dynamic' :
                        '₹' + parseFloat(prev.price_per_unit).toFixed(2);

                    const conflictHtml =
                        '<div style="text-align:left;font-size:14px;">' +
                        '<div><strong>Existing price:</strong> ' + prevPrice + '</div>' +
                        '<div><strong>From:</strong> ' + prev.effective_start_date +
                        ' <strong>To:</strong> ' + (prev.effective_end_date || 'Ongoing') + '</div>' +
                        '<hr style="margin:8px 0;">' +
                        '<div><strong>New price will start from:</strong> ' + data.new_start + '</div>' +
                        '</div>';

                    const confirmed = await Swal.fire({
                        title: 'Price conflict',
                        html: conflictHtml,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Proceed',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#0d6efd',
                        cancelButtonColor: '#6c757d',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    });

                    if (!confirmed.isConfirmed) return;

                    // Pass 2: confirmed submission
                    Swal.fire({
                        title: 'Submission is in progress…',
                        html: 'Please wait while we save the new price.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => Swal.showLoading()
                    });

                    await new Promise(r => setTimeout(r, 30));

                    let res2;
                    try {
                        res2 = await postForm({
                            confirm_end_date: '1'
                        });
                    } catch (err) {
                        Swal.close();
                        Swal.fire('Network error', err.message, 'error');
                        return;
                    }

                    const ct2 = res2.headers.get('content-type') || '';
                    const bodyText2 = await res2.text();

                    Swal.close();

                    if (ct2.includes('application/json')) {
                        try {
                            const data2 = JSON.parse(bodyText2);
                            if (data2.status === 'error') {
                                Swal.fire('Error', data2.message, 'error');
                                return;
                            }
                            if (data2.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Saved',
                                    text: data2.message,
                                    timer: 1200,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = window.location.pathname + window.location.search;
                                });
                                return;
                            }
                        } catch (_) {
                            /* fall through */
                        }
                    }

                    Swal.fire('Unexpected response', 'Please reload the page.', 'error');
                }
            });

            // -----------------------------------------------------------------
            // FILTER SUBMIT — validate item+unit combination, then fetch
            //
            // Rule: if a Unit is selected AND any selected item does NOT belong
            // to that unit (per stock_add), block and name the offending items.
            // No auto-deselect, no auto-filter.
            //
            // IMPORTANT: If the validator cannot be reached, we DO NOT fail open —
            // we block the search and inform the user.
            // -----------------------------------------------------------------
            const filterForm = document.getElementById('filter-form');
            let filterBypassOnce = false;

            filterForm.addEventListener('submit', async function(e) {
                if (filterBypassOnce) {
                    filterBypassOnce = false;
                    return; // allow the real submit (loader already shown)
                }

                const unitId = $('#filter_unit_id').val();
                const selectedItems = $('#filter_item_id').val() || [];

                // No unit, or no items → allow freely (mixed units OK).
                // Still show a fetching loader so the user sees progress.
                if (!unitId || selectedItems.length === 0) {
                    e.preventDefault();
                    showFetchingLoaderThenSubmit();
                    return;
                }

                e.preventDefault();

                // Pass 1: verification loader
                Swal.fire({
                    title: 'Checking…',
                    text: 'Verifying filter criteria, please wait.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading()
                });

                // Ask the server which items belong to this unit (source: stock_add)
                let data;
                try {
                    const params = new URLSearchParams();
                    params.append('unit_id', unitId);
                    selectedItems.forEach(id => params.append('item_ids[]', id));

                    const res = await fetch('check_items_unit.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: params
                    });

                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }

                    data = await res.json();

                    if (data.error) {
                        throw new Error(data.error);
                    }
                } catch (err) {
                    Swal.close();
                    console.error('Unit/item validation failed', err);
                    await Swal.fire({
                        icon: 'error',
                        title: 'Could not verify items',
                        html: 'We could not check whether the selected items belong to the chosen unit.<br>' +
                            'Please try again. If the problem persists, contact support.' +
                            '<div style="margin-top:8px;font-size:12px;color:#6c757d;">' +
                            escapeHtml(err.message || String(err)) +
                            '</div>',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#0d6efd'
                    });
                    return;
                }

                // Close the verification loader now that we have an answer
                Swal.close();

                const mismatched = data.mismatched || []; // [{id, name}, ...]

                if (mismatched.length === 0) {
                    // Verified OK → show "fetching data" loader and submit for real
                    showFetchingLoaderThenSubmit();
                    return;
                }

                const unitName = data.unit_name || ('unit #' + unitId);
                const listHtml = mismatched
                    .map(m => '<li><strong>' + escapeHtml(m.name) + '</strong></li>')
                    .join('');

                await Swal.fire({
                    icon: 'warning',
                    title: 'Unit doesn\'t match',
                    html: '<div style="text-align:left;">' +
                        'The unit <strong>' + escapeHtml(unitName) + '</strong> you selected ' +
                        'does not match all the items you have chosen.<br><br>' +
                        'Please remove the following item(s) so that all selected items ' +
                        'share the same unit — you can only search items of the same unit together:' +
                        '<ul style="margin-top:8px;">' + listHtml + '</ul>' +
                        '</div>',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#0d6efd',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });
                // Do NOT submit — user must remove the offending items manually.
            });

            // -----------------------------------------------------------------
            // Show "Fetching data…" then submit the filter form for real.
            // The loader is intentionally not closed — the browser will navigate
            // away and the modal disappears with the page.
            // -----------------------------------------------------------------
            function showFetchingLoaderThenSubmit() {
                Swal.fire({
                    title: 'Fetching data…',
                    text: 'Please wait while we load the matching price records.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading()
                });

                // Let the loader paint before the synchronous submit kicks in
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        filterBypassOnce = true;
                        filterForm.submit();
                    });
                });
            }

            function escapeHtml(s) {
                return String(s)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

        });
    </script>
</body>

</html>