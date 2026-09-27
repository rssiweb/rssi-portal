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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---------- NEW: Handle edit of effective_from / effective_to ----------
    if (isset($_POST['action']) && $_POST['action'] === 'edit_effective') {
        $editId    = (int) $_POST['edit_id'];
        $editFrom  = $_POST['edit_effective_from'] ?? '';
        $editTo    = $_POST['edit_effective_to']   ?? '';

        // Validate
        if ($editId <= 0 || empty($editFrom)) {
            $_SESSION['error_message'] = "Invalid edit request.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        if (!empty($editTo) && $editTo < $editFrom) {
            $_SESSION['error_message'] = "Effective To cannot be earlier than Effective From.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        $fromEsc = pg_escape_literal($con, $editFrom);
        $toSql   = !empty($editTo) ? pg_escape_literal($con, $editTo) : 'NULL';

        $updateSql = "UPDATE student_class_days
                      SET effective_from = $fromEsc,
                          effective_to   = $toSql
                      WHERE id = $editId";

        $ok = pg_query($con, $updateSql);
        if ($ok) {
            $_SESSION['success_message'] = "Schedule dates updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update dates: " . pg_last_error($con);
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    // ---------- Existing add/update schedule logic ----------
    $category      = pg_escape_string($con, $_POST['category']);
    $location      = (int) $_POST['location'];
    $class_days    = pg_escape_string($con, $_POST['class_days']);
    $effectiveFrom = pg_escape_string($con, $_POST['effective_from']);
    $classStart    = pg_escape_string($con, $_POST['class_start_time']); // NEW
    $classEnd      = pg_escape_string($con, $_POST['class_end_time']);   // NEW

    pg_query($con, "BEGIN");

    $updateQuery = "UPDATE student_class_days 
                    SET effective_to = (DATE '$effectiveFrom' - INTERVAL '1 day')::date
                    WHERE category = '$category' 
                      AND location = $location 
                      AND effective_to IS NULL";
    pg_query($con, $updateQuery);

    // NEW: include class_start_time and class_end_time
    $insertQuery = "INSERT INTO student_class_days 
                        (category, location, class_days, effective_from, class_start_time, class_end_time)
                    VALUES 
                        ('$category', $location, '$class_days', '$effectiveFrom', '$classStart', '$classEnd')";
    $result = pg_query($con, $insertQuery);

    if ($result) {
        pg_query($con, "COMMIT");
        $_SESSION['success_message'] = "Class days updated successfully!";
    } else {
        pg_query($con, "ROLLBACK");
        $_SESSION['error_message'] = "Failed to update class days: " . pg_last_error($con);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Filters (all via GET so URLs are bookmarkable) ---
$selected_location = isset($_GET['get_location']) && $_GET['get_location'] !== ''
    ? (int) $_GET['get_location']
    : '';
$show_history      = isset($_GET['show_history']) && $_GET['show_history'] == '1';
$history_from      = $_GET['history_from'] ?? '';
$history_to        = $_GET['history_to']   ?? '';
$history_page      = max(1, (int)($_GET['history_page'] ?? 1));
$history_per_page  = 15; // keep page light

// Fetch active locations
$locations   = [];
$locationMap = [];
$locations_result = pg_query($con, "SELECT id, name FROM office_locations WHERE is_active = true ORDER BY name");
while ($row = pg_fetch_assoc($locations_result)) {
    $locations[] = $row;                       // array of ['id' => ..., 'name' => ...]
    $locationMap[$row['id']] = $row['name'];   // id => name lookup
}

// --- Active settings (always small: one row per category+location) ---
$activeQuery  = "SELECT * FROM student_class_days WHERE effective_to IS NULL";
$activeParams = [];
if (!empty($selected_location)) {
    $activeQuery   .= " AND location = $1";
    $activeParams[] = $selected_location;
}
$activeQuery .= " ORDER BY category, location";

$activeResult = !empty($activeParams)
    ? pg_query_params($con, $activeQuery, $activeParams)
    : pg_query($con, $activeQuery);
$activeSettings = pg_fetch_all($activeResult) ?: [];

// --- Count of historical rows (cheap COUNT, no data fetched) ---
$countQuery  = "SELECT COUNT(*) AS cnt FROM student_class_days WHERE effective_to IS NOT NULL";
$countParams = [];
$paramCount  = 1;
if (!empty($selected_location)) {
    $countQuery  .= " AND location = $" . $paramCount++;
    $countParams[] = $selected_location;
}
if (!empty($history_from)) {
    $countQuery  .= " AND effective_from >= $" . $paramCount++;
    $countParams[] = $history_from;
}
if (!empty($history_to)) {
    $countQuery  .= " AND effective_from <= $" . $paramCount++;
    $countParams[] = $history_to;
}

$countResult  = !empty($countParams)
    ? pg_query_params($con, $countQuery, $countParams)
    : pg_query($con, $countQuery);
$historyTotal = (int)(pg_fetch_assoc($countResult)['cnt'] ?? 0);
$historyPages = max(1, (int)ceil($historyTotal / $history_per_page));
if ($history_page > $historyPages) $history_page = $historyPages;
$historyOffset = ($history_page - 1) * $history_per_page;

// --- Historical rows: only fetched if user explicitly asked (show_history=1) ---
$historySettings = [];
if ($show_history && $historyTotal > 0) {
    $histQuery  = "SELECT * FROM student_class_days WHERE effective_to IS NOT NULL";
    $histParams = [];
    $paramCount = 1;
    if (!empty($selected_location)) {
        $histQuery  .= " AND location = $" . $paramCount++;
        $histParams[] = $selected_location;
    }
    if (!empty($history_from)) {
        $histQuery  .= " AND effective_from >= $" . $paramCount++;
        $histParams[] = $history_from;
    }
    if (!empty($history_to)) {
        $histQuery  .= " AND effective_from <= $" . $paramCount++;
        $histParams[] = $history_to;
    }
    $histQuery .= " ORDER BY effective_from DESC, category, location
                    LIMIT " . (int)$history_per_page . " OFFSET " . (int)$historyOffset;

    $histResult = !empty($histParams)
        ? pg_query_params($con, $histQuery, $histParams)
        : pg_query($con, $histQuery);
    $historySettings = pg_fetch_all($histResult) ?: [];
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>
    <link href="../img/favicon.ico" rel="icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">
    <style>
        .day-checkbox {
            display: inline-block;
            margin-right: 15px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px 14px;
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            border-radius: 4px;
        }

        .section-header h5 {
            margin: 0;
            font-weight: 600;
            color: #212529;
        }

        .section-header .badge {
            font-size: 0.85rem;
        }

        .empty-state {
            text-align: center;
            color: #6c757d;
            padding: 20px;
            font-style: italic;
        }

        .filter-card {
            background: #f8f9fa;
            padding: 14px 16px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        .table thead th {
            background: #f1f3f5;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #495057;
        }

        .day-chip {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin: 2px;
            background-color: #e9ecef;
            color: #495057;
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
                <div class="col-12">
                    <div class="card">
                        <div class="card-body pt-4">

                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success alert-dismissible fade show">
                                    <?= $_SESSION['success_message'] ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php unset($_SESSION['success_message']); ?>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <?= $_SESSION['error_message'] ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php unset($_SESSION['error_message']); ?>
                            <?php endif; ?>

                            <!-- ============ ADD / UPDATE FORM ============ -->
                            <div class="section-header">
                                <h5><i class="bi bi-plus-circle me-2"></i>Add / Update Schedule</h5>
                            </div>

                            <form method="POST" class="mb-4">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="category" class="form-label">Category</label>
                                        <select class="form-select" id="category" name="category" required>
                                            <option value="">Select Category</option>
                                            <?php
                                            $catResult = pg_query($con, "SELECT category_name, category_value
                                          FROM school_categories
                                          ORDER BY category_name");
                                            if ($catResult) {
                                                while ($row = pg_fetch_assoc($catResult)) {
                                                    echo '<option value="' . htmlspecialchars($row['category_value']) . '">'
                                                        . htmlspecialchars($row['category_name']) .
                                                        '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="location" class="form-label">Location</label>
                                        <select class="form-select" id="location" name="location" required>
                                            <option value="">Select Location</option>
                                            <?php foreach ($locations as $loc): ?>
                                                <option value="<?= htmlspecialchars($loc['id']) ?>">
                                                    <?= htmlspecialchars($loc['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="effective_from" class="form-label">Effective From</label>
                                        <input type="date" class="form-control" id="effective_from" name="effective_from" required>
                                    </div>

                                    <!-- NEW: Start time -->
                                    <div class="col-md-3">
                                        <label for="class_start_time" class="form-label">Class Start Time</label>
                                        <input type="time" class="form-control" id="class_start_time" name="class_start_time" required>
                                    </div>
                                </div>

                                <!-- NEW ROW: End time -->
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="class_end_time" class="form-label">Class End Time</label>
                                        <input type="time" class="form-control" id="class_end_time" name="class_end_time" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Class Days</label><br>
                                        <?php foreach (['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'] as $id => $day): ?>
                                            <div class="day-checkbox">
                                                <input class="form-check-input" type="checkbox" id="<?= $id ?>" name="class_days[]" value="<?= $day ?>">
                                                <label for="<?= $id ?>"><?= $day ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                        <input type="hidden" id="class_days" name="class_days">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Save Settings
                                </button>
                            </form>

                            <!-- ============ FILTER BAR ============ -->
                            <div class="filter-card">
                                <form method="GET" class="row g-2 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">Location</label>
                                        <select name="get_location" id="get_location" class="form-select form-select-sm">
                                            <option value="">All Locations</option>
                                            <?php foreach ($locations as $loc): ?>
                                                <option value="<?= htmlspecialchars($loc['id']) ?>"
                                                    <?= $loc['id'] == $selected_location ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($loc['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">History from</label>
                                        <input type="date" name="history_from" class="form-control form-control-sm"
                                            value="<?= htmlspecialchars($history_from) ?>">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">History to</label>
                                        <input type="date" name="history_to" class="form-control form-control-sm"
                                            value="<?= htmlspecialchars($history_to) ?>">
                                    </div>

                                    <div class="col-md-3 d-flex gap-2">
                                        <button type="submit" class="btn btn-success btn-sm flex-fill">
                                            <i class="bi bi-funnel me-1"></i> Apply
                                        </button>
                                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary btn-sm">
                                            Clear
                                        </a>
                                    </div>
                                </form>
                            </div>

                            <!-- ============ CURRENT ACTIVE SETTINGS ============ -->
                            <div class="section-header mt-4">
                                <h5>
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    Current Active Settings
                                    <?= $selected_location ? '<span class="loc-chip ms-2">' . htmlspecialchars($locationMap[$selected_location] ?? '') . '</span>' : '' ?>
                                </h5>
                                <span class="badge bg-success"><?= count($activeSettings) ?> active</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Location</th>
                                            <th>Class Days</th>
                                            <th>Class Time</th>
                                            <th>Effective From</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($activeSettings)): ?>
                                            <?php foreach ($activeSettings as $setting): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($setting['category'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($locationMap[$setting['location']] ?? '—') ?></td>
                                                    <td>
                                                        <?php
                                                        $days = array_filter(array_map('trim', explode(',', $setting['class_days'] ?? '')));
                                                        if ($days) {
                                                            foreach ($days as $d) {
                                                                echo '<span class="day-chip">' . htmlspecialchars($d) . '</span>';
                                                            }
                                                        } else {
                                                            echo '<span class="text-muted">—</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($setting['class_start_time']) && !empty($setting['class_end_time'])): ?>
                                                            <span class="badge bg-light text-dark border">
                                                                <i class="bi bi-clock me-1"></i>
                                                                <?= htmlspecialchars(date('h:i A', strtotime($setting['class_start_time']))) ?>
                                                                &ndash;
                                                                <?= htmlspecialchars(date('h:i A', strtotime($setting['class_end_time']))) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= !empty($setting['effective_from'])
                                                            ? htmlspecialchars(date('d-M-Y', strtotime($setting['effective_from'])))
                                                            : '—' ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editEffectiveModal"
                                                            data-id="<?= (int)$setting['id'] ?>"
                                                            data-category="<?= htmlspecialchars($setting['category'] ?? '') ?>"
                                                            data-location="<?= htmlspecialchars($locationMap[$setting['location']] ?? '') ?>"
                                                            data-from="<?= htmlspecialchars($setting['effective_from'] ?? '') ?>"
                                                            data-to="<?= htmlspecialchars($setting['effective_to'] ?? '') ?>">
                                                            <i class="bi bi-pencil-square"></i> Edit
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="empty-state">
                                                    No active settings<?= $selected_location ? ' for ' . htmlspecialchars($locationMap[$selected_location] ?? '') : '' ?>.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- ============ HISTORICAL SETTINGS (lazy) ============ -->
                            <div class="section-header mt-5">
                                <h5>
                                    <i class="bi bi-clock-history text-secondary me-2"></i>
                                    Historical Settings
                                    <?= $selected_location ? '<span class="loc-chip ms-2">' . htmlspecialchars($locationMap[$selected_location] ?? '') . '</span>' : '' ?>
                                </h5>
                                <span class="badge bg-secondary"><?= $historyTotal ?> archived</span>
                            </div>

                            <?php if ($historyTotal == 0): ?>
                                <div class="empty-state">
                                    No historical records<?= $selected_location ? ' for ' . htmlspecialchars($locationMap[$selected_location] ?? '') : '' ?>.
                                </div>
                            <?php else: ?>

                                <!-- Toggle button that triggers the fetch via query string -->
                                <div class="mb-3">
                                    <?php
                                    $toggleParams = $_GET;
                                    if ($show_history) {
                                        unset($toggleParams['show_history'], $toggleParams['history_page']);
                                    } else {
                                        $toggleParams['show_history'] = '1';
                                    }
                                    $toggleUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query($toggleParams);
                                    ?>
                                    <a href="<?= htmlspecialchars($toggleUrl) ?>"
                                        class="btn btn-sm <?= $show_history ? 'btn-outline-secondary' : 'btn-outline-primary' ?>">
                                        <i class="bi bi-<?= $show_history ? 'chevron-up' : 'chevron-down' ?> me-1"></i>
                                        <?= $show_history ? 'Hide history' : 'Load ' . $historyTotal . ' historical record' . ($historyTotal === 1 ? '' : 's') ?>
                                    </a>
                                    <small class="text-muted ms-2">(not loaded by default to keep the page fast)</small>
                                </div>

                                <?php if ($show_history): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Category</th>
                                                    <th>Location</th>
                                                    <th>Class Days</th>
                                                    <th>Class Time</th>
                                                    <th>Effective From</th>
                                                    <th>Effective To</th>
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($historySettings as $setting): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($setting['category'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($locationMap[$setting['location']] ?? '—') ?></td>
                                                        <td>
                                                            <?php
                                                            $days = array_filter(array_map('trim', explode(',', $setting['class_days'] ?? '')));
                                                            if ($days) {
                                                                foreach ($days as $d) {
                                                                    echo '<span class="day-chip">' . htmlspecialchars($d) . '</span>';
                                                                }
                                                            } else {
                                                                echo '<span class="text-muted">—</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($setting['class_start_time']) && !empty($setting['class_end_time'])): ?>
                                                                <span class="badge bg-light text-dark border">
                                                                    <i class="bi bi-clock me-1"></i>
                                                                    <?= htmlspecialchars(date('h:i A', strtotime($setting['class_start_time']))) ?>
                                                                    &ndash;
                                                                    <?= htmlspecialchars(date('h:i A', strtotime($setting['class_end_time']))) ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span>
                                                            <?php endif; ?>
                                                        <td><?= !empty($setting['effective_from']) ? htmlspecialchars(date('d-M-Y', strtotime($setting['effective_from']))) : '—' ?></td>
                                                        <td><?= !empty($setting['effective_to']) ? htmlspecialchars(date('d-M-Y', strtotime($setting['effective_to']))) : '—' ?></td>
                                                        <td class="text-end">
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editEffectiveModal"
                                                                data-id="<?= (int)$setting['id'] ?>"
                                                                data-category="<?= htmlspecialchars($setting['category'] ?? '') ?>"
                                                                data-location="<?= htmlspecialchars($locationMap[$setting['location']] ?? '') ?>"
                                                                data-from="<?= htmlspecialchars($setting['effective_from'] ?? '') ?>"
                                                                data-to="<?= htmlspecialchars($setting['effective_to'] ?? '') ?>">
                                                                <i class="bi bi-pencil-square"></i> Edit
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination -->
                                    <?php if ($historyPages > 1): ?>
                                        <nav>
                                            <ul class="pagination pagination-sm justify-content-center">
                                                <?php
                                                $pageBase = $_GET;
                                                $pageBase['show_history'] = '1';
                                                ?>
                                                <li class="page-item <?= $history_page <= 1 ? 'disabled' : '' ?>">
                                                    <?php $pageBase['history_page'] = $history_page - 1; ?>
                                                    <a class="page-link" href="<?= $_SERVER['PHP_SELF'] . '?' . http_build_query($pageBase) ?>">
                                                        &laquo; Prev
                                                    </a>
                                                </li>

                                                <?php
                                                $start = max(1, $history_page - 2);
                                                $end   = min($historyPages, $history_page + 2);
                                                for ($p = $start; $p <= $end; $p++):
                                                    $pageBase['history_page'] = $p;
                                                ?>
                                                    <li class="page-item <?= $p == $history_page ? 'active' : '' ?>">
                                                        <a class="page-link" href="<?= $_SERVER['PHP_SELF'] . '?' . http_build_query($pageBase) ?>">
                                                            <?= $p ?>
                                                        </a>
                                                    </li>
                                                <?php endfor; ?>

                                                <li class="page-item <?= $history_page >= $historyPages ? 'disabled' : '' ?>">
                                                    <?php $pageBase['history_page'] = $history_page + 1; ?>
                                                    <a class="page-link" href="<?= $_SERVER['PHP_SELF'] . '?' . http_build_query($pageBase) ?>">
                                                        Next &raquo;
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>
    <!-- ============ EDIT EFFECTIVE DATES MODAL ============ -->
    <div class="modal fade" id="editEffectiveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <input type="hidden" name="action" value="edit_effective">
                <input type="hidden" name="edit_id" id="edit_id">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-calendar-range me-1"></i> Edit Effective Dates
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2 small text-muted">
                        <strong id="edit_ctx_category"></strong>
                        &middot;
                        <span id="edit_ctx_location"></span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Effective From</label>
                            <input type="date" class="form-control" name="edit_effective_from" id="edit_effective_from" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                Effective To
                                <small class="text-muted">(leave blank = active)</small>
                            </label>
                            <input type="date" class="form-control" name="edit_effective_to" id="edit_effective_to">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets_new/js/main.js"></script>

    <script>
        document.getElementById('effective_from').valueAsDate = new Date();

        const checkboxes = document.querySelectorAll('input[name="class_days[]"]');
        const hiddenField = document.getElementById('class_days');

        function updateClassDays() {
            const selectedDays = Array.from(checkboxes)
                .filter(c => c.checked)
                .map(c => c.value)
                .join(',');
            hiddenField.value = selectedDays;
        }

        checkboxes.forEach(c => c.addEventListener('change', updateClassDays));
        // Default times if empty
        const startTimeInput = document.getElementById('class_start_time');
        const endTimeInput = document.getElementById('class_end_time');
        if (startTimeInput && !startTimeInput.value) startTimeInput.value = '11:00';
        if (endTimeInput && !endTimeInput.value) endTimeInput.value = '18:30';

        // Populate edit modal from clicked button
        const editModal = document.getElementById('editEffectiveModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function(event) {
                const btn = event.relatedTarget;
                if (!btn) return;

                document.getElementById('edit_id').value = btn.getAttribute('data-id') || '';
                document.getElementById('edit_effective_from').value = btn.getAttribute('data-from') || '';
                document.getElementById('edit_effective_to').value = btn.getAttribute('data-to') || '';
                document.getElementById('edit_ctx_category').textContent = btn.getAttribute('data-category') || '';
                document.getElementById('edit_ctx_location').textContent = btn.getAttribute('data-location') || '';
            });
        }
    </script>
</body>

</html>