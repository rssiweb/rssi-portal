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

$is_admin = ($role == 'Admin');

/* =========================================================
   HELPERS
   ========================================================= */
function normalise_date($raw)
{
    $raw = trim($raw);
    if ($raw === '') return null;
    foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d'] as $fmt) {
        $d = DateTime::createFromFormat($fmt, $raw);
        if ($d && $d->format($fmt) === $raw) return $d->format('Y-m-d');
    }
    $ts = strtotime($raw);
    return $ts ? date('Y-m-d', $ts) : null;
}

function bool_from_csv($v)
{
    $v = strtolower(trim((string)$v));
    return in_array($v, ['1', 'true', 'yes', 'y', 't'], true);
}

/* =========================================================
   TEMPLATE DOWNLOAD
   ========================================================= */
if (isset($_GET['download_template']) && $_GET['download_template'] === '1') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="holiday_import_template.csv"');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM

    // Header row
    fputcsv($out, ['holiday_name', 'holiday_date', 'is_flexi', 'is_public', 'location_id']);

    // Sample rows (clearly marked so users know to replace them)
    fputcsv($out, ['New Year\'s Day',    '2024-01-01', 'FALSE', 'TRUE',  '1']);
    fputcsv($out, ['Republic Day',       '2024-01-26', 'FALSE', 'TRUE',  '1']);
    fputcsv($out, ['Holi',               '2024-03-25', 'FALSE', 'TRUE',  '1']);
    fputcsv($out, ['Diwali',             '2024-11-01', 'FALSE', 'TRUE',  '1']);
    fputcsv($out, ['Optional Holiday',   '2024-12-24', 'TRUE',  'FALSE', '2']);

    fclose($out);
    exit;
}

/* =========================================================
   EXPORT CSV
   ========================================================= */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $loc_filter = isset($_GET['filter_location']) ? (int)$_GET['filter_location'] : 0;
    $year       = isset($_GET['filter_year'])     ? (int)$_GET['filter_year']     : 0;
    $type       = $_GET['filter_type'] ?? '';

    $sql = "SELECT h.id, h.holiday_name, h.holiday_date, h.is_flexi, h.is_public,
                   h.location, ol.name AS location_name
            FROM holidays h
            LEFT JOIN office_locations ol ON ol.id = h.location
            WHERE 1=1";
    $params = [];
    if ($loc_filter > 0) {
        $sql .= " AND h.location = $" . (count($params) + 1);
        $params[] = $loc_filter;
    }
    if ($year > 0) {
        $sql .= " AND EXTRACT(YEAR FROM h.holiday_date) = $" . (count($params) + 1);
        $params[] = $year;
    }
    if ($type === 'flexi')   $sql .= " AND h.is_flexi = true";
    if ($type === 'public')  $sql .= " AND h.is_public = true AND h.is_flexi = false";
    if ($type === 'regular') $sql .= " AND h.is_public = false AND h.is_flexi = false";
    $sql .= " ORDER BY h.holiday_date ASC";

    $res = !empty($params) ? pg_query_params($con, $sql, $params) : pg_query($con, $sql);
    $rows = $res ? pg_fetch_all($res) : [];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="holidays_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['id', 'holiday_name', 'holiday_date', 'is_flexi', 'is_public', 'location_id', 'location_name']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['holiday_name'],
            $r['holiday_date'],
            ($r['is_flexi']  === 't' ? 'TRUE' : 'FALSE'),
            ($r['is_public'] === 't' ? 'TRUE' : 'FALSE'),
            $r['location'],
            $r['location_name'],
        ]);
    }
    fclose($out);
    exit;
}

/* =========================================================
   DELETE
   ========================================================= */
if (isset($_GET['delete']) && $is_admin) {
    $del_id = (int)$_GET['delete'];
    $r = pg_query_params($con, "DELETE FROM holidays WHERE id = $1", [$del_id]);
    if ($r) {
        $_SESSION['success_message'] = "Holiday #$del_id deleted.";
    } else {
        $_SESSION['error_message'] = "Delete failed: " . pg_last_error($con);
    }
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    parse_str($qs, $qp);
    unset($qp['delete']);
    header("Location: holiday_management.php" . ($qp ? '?' . http_build_query($qp) : ''));
    exit;
}

/* =========================================================
   IMPORT CSV
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form-type'] ?? '') === 'import_csv') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = "Upload failed (no file or error).";
        header("Location: holiday_management.php");
        exit;
    }

    $fh = fopen($_FILES['csv_file']['tmp_name'], 'r');
    if (!$fh) {
        $_SESSION['error_message'] = "Could not open uploaded file.";
        header("Location: holiday_management.php");
        exit;
    }

    // Detect & skip header
    $first = fgetcsv($fh);
    $has_header = false;
    if ($first) {
        $joined = strtolower(implode(',', $first));
        if (strpos($joined, 'holiday_name') !== false || strpos($joined, 'holiday_date') !== false) {
            $has_header = true;
        }
    }
    if (!$has_header && $first) {
        rewind($fh); // first row was data
    }

    $inserted = 0;
    $skipped  = 0;
    $errors   = [];

    pg_query($con, "BEGIN");
    try {
        while (($row = fgetcsv($fh)) !== false) {
            // Skip blank lines
            if (count($row) === 1 && trim($row[0]) === '') continue;

            // Accept either 5 or 6 columns (id at start is optional)
            if (count($row) >= 6 && is_numeric(trim($row[0]))) {
                $offset = 1; // has id column
            } else {
                $offset = 0;
            }
            if (count($row) < $offset + 5) {
                $skipped++;
                $errors[] = "Not enough columns: " . implode(',', $row);
                continue;
            }

            $name     = trim($row[$offset + 0] ?? '');
            $dateRaw  = trim($row[$offset + 1] ?? '');
            $isFlexi  = bool_from_csv($row[$offset + 2] ?? '');
            $isPublic = bool_from_csv($row[$offset + 3] ?? '');
            $location = (int)($row[$offset + 4] ?? 0);

            $date = normalise_date($dateRaw);
            if ($name === '' || $date === null || $location <= 0) {
                $errors[] = "Skipped: '$name' / '$dateRaw' / loc=$location";
                $skipped++;
                continue;
            }

            $res = pg_query_params(
                $con,
                "INSERT INTO holidays (holiday_name, holiday_date, is_flexi, is_public, location)
                 VALUES ($1, $2, $3, $4, $5)",
                [$name, $date, $isFlexi ? 'true' : 'false', $isPublic ? 'true' : 'false', $location]
            );
            if ($res) $inserted++;
            else {
                $errors[] = pg_last_error($con);
                $skipped++;
            }
        }
        pg_query($con, "COMMIT");
        $msg = "Imported $inserted row(s).";
        if ($skipped > 0) $msg .= " Skipped $skipped.";
        $_SESSION['success_message'] = $msg;
        if (!empty($errors)) {
            $_SESSION['error_message'] = "Warnings: " . implode(' | ', array_slice($errors, 0, 5));
        }
    } catch (Exception $e) {
        pg_query($con, "ROLLBACK");
        $_SESSION['error_message'] = "Import failed: " . $e->getMessage();
    }
    fclose($fh);
    header("Location: holiday_management.php");
    exit;
}

/* =========================================================
   ADD SINGLE HOLIDAY
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form-type'] ?? '') === 'add_holiday') {
    $name     = trim(pg_escape_string($con, $_POST['holiday_name'] ?? ''));
    $dateRaw  = $_POST['holiday_date'] ?? '';
    $isFlexi  = isset($_POST['is_flexi'])  ? 'true' : 'false';
    $isPublic = isset($_POST['is_public']) ? 'true' : 'false';
    $location = (int)($_POST['location'] ?? 0);

    $date = normalise_date($dateRaw);

    if ($name === '' || !$date || $location <= 0) {
        $_SESSION['error_message'] = "Please fill all fields correctly.";
    } else {
        $res = pg_query_params(
            $con,
            "INSERT INTO holidays (holiday_name, holiday_date, is_flexi, is_public, location)
             VALUES ($1, $2, $3, $4, $5)",
            [$name, $date, $isFlexi, $isPublic, $location]
        );
        if ($res) {
            $_SESSION['success_message'] = "Holiday '$name' added on $date.";
        } else {
            $_SESSION['error_message'] = "Insert failed: " . pg_last_error($con);
        }
    }
    header("Location: holiday_management.php");
    exit;
}

/* =========================================================
   FILTERS (read from GET)
   ========================================================= */
$filter_location = isset($_GET['filter_location']) ? (int)$_GET['filter_location'] : 0;
$filter_year     = isset($_GET['filter_year'])     ? (int)$_GET['filter_year']     : 0;
$filter_type     = $_GET['filter_type'] ?? '';

// Lazy-load: only fetch holidays if a year is chosen
$can_load = ($filter_year > 0);

/* Dropdown data */
$locations = [];
$lres = pg_query($con, "SELECT id, name FROM office_locations WHERE is_active = true ORDER BY name");
while ($r = pg_fetch_assoc($lres)) {
    $locations[] = $r;
}

$years = [];
$yres = pg_query($con, "SELECT DISTINCT EXTRACT(YEAR FROM holiday_date)::int AS y
                        FROM holidays ORDER BY y DESC");
while ($r = pg_fetch_assoc($yres)) {
    $years[] = (int)$r['y'];
}

// Also add current year to the top of the list (in case there are no holidays yet)
$currentYear = (int)date('Y');
if (!in_array($currentYear, $years)) {
    array_unshift($years, $currentYear);
}

$holidays = [];
if ($can_load) {
    $sql = "SELECT h.*, ol.name AS location_name
            FROM holidays h
            LEFT JOIN office_locations ol ON ol.id = h.location
            WHERE EXTRACT(YEAR FROM h.holiday_date) = $" . 1;
    $params = [$filter_year];

    if ($filter_location > 0) {
        $sql .= " AND h.location = $" . (count($params) + 1);
        $params[] = $filter_location;
    }
    if ($filter_type === 'flexi')   $sql .= " AND h.is_flexi = true";
    if ($filter_type === 'public')  $sql .= " AND h.is_public = true AND h.is_flexi = false";
    if ($filter_type === 'regular') $sql .= " AND h.is_public = false AND h.is_flexi = false";
    $sql .= " ORDER BY h.holiday_date ASC";

    $res = pg_query_params($con, $sql, $params);
    $holidays = $res ? pg_fetch_all($res) : [];
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
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }

        .type-badge {
            font-size: .75rem;
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
                        <div class="card-body">
                            <br>

                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success alert-dismissible fade show">
                                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php unset($_SESSION['success_message']); ?>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php unset($_SESSION['error_message']); ?>
                            <?php endif; ?>

                            <!-- ============ ADD HOLIDAY ============ -->
                            <h5 class="mb-3">Add Holiday</h5>
                            <form method="POST" class="row g-3 align-items-end mb-4">
                                <input type="hidden" name="form-type" value="add_holiday">

                                <div class="col-md-4">
                                    <label class="form-label">Holiday Name</label>
                                    <input type="text" name="holiday_name" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="holiday_date" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Location</label>
                                    <select name="location" class="form-select" required>
                                        <option value="">Select</option>
                                        <?php foreach ($locations as $loc): ?>
                                            <option value="<?= (int)$loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="is_flexi" name="is_flexi">
                                        <label class="form-check-label" for="is_flexi">Flexi Holiday</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="is_public" name="is_public" checked>
                                        <label class="form-check-label" for="is_public">Public Holiday</label>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm mt-2">
                                        <i class="bi bi-plus-circle"></i> Add Holiday
                                    </button>
                                </div>
                            </form>

                            <hr>

                            <!-- ============ IMPORT / EXPORT / TEMPLATE ============ -->
                            <div class="row g-3 align-items-start mb-4">
                                <div class="col-md-6">
                                    <h5 class="mb-2">Import CSV</h5>
                                    <form method="POST" enctype="multipart/form-data" class="d-flex gap-2 mb-2">
                                        <input type="hidden" name="form-type" value="import_csv">
                                        <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required>
                                        <button type="submit" class="btn btn-success text-nowrap">
                                            <i class="bi bi-upload"></i> Import
                                        </button>
                                    </form>
                                    <small class="text-muted d-block">
                                        Expected columns: <code>holiday_name, holiday_date, is_flexi, is_public, location_id</code>.
                                        Header row optional. Dates accepted as <code>YYYY-MM-DD</code> or <code>DD-MM-YYYY</code>.
                                    </small>
                                    <a href="holiday_management.php?download_template=1"
                                        class="btn btn-outline-secondary btn-sm mt-2">
                                        <i class="bi bi-file-earmark-arrow-down"></i> Download Import Template
                                    </a>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h5 class="mb-2">Export</h5>
                                    <?php
                                    $qs = $_GET;
                                    $qs['export'] = 'csv';
                                    $exportUrl = 'holiday_management.php?' . http_build_query($qs);
                                    ?>
                                    <a href="<?= htmlspecialchars($exportUrl) ?>"
                                        class="btn btn-outline-primary <?= !$can_load ? 'disabled' : '' ?>"
                                        <?= !$can_load ? 'onclick="return false;"' : '' ?>>
                                        <i class="bi bi-download"></i> Download CSV
                                    </a>
                                    <?php if (!$can_load): ?>
                                        <small class="text-muted d-block mt-1">
                                            Select a year below to enable export.
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <hr>

                            <!-- ============ FILTERS ============ -->
                            <h5 class="mb-3">View Holidays</h5>
                            <form method="GET" class="row g-2 align-items-end mb-4">
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">
                                        Year <span class="text-danger">*</span>
                                    </label>
                                    <select name="filter_year" class="form-select form-select-sm" required>
                                        <option value="">Select year</option>
                                        <?php foreach ($years as $y): ?>
                                            <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Location</label>
                                    <select name="filter_location" class="form-select form-select-sm">
                                        <option value="">All locations</option>
                                        <?php foreach ($locations as $loc): ?>
                                            <option value="<?= (int)$loc['id'] ?>"
                                                <?= $filter_location == $loc['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($loc['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Type</label>
                                    <select name="filter_type" class="form-select form-select-sm">
                                        <option value="" <?= $filter_type === ''        ? 'selected' : '' ?>>All</option>
                                        <option value="public" <?= $filter_type === 'public'  ? 'selected' : '' ?>>Public</option>
                                        <option value="flexi" <?= $filter_type === 'flexi'   ? 'selected' : '' ?>>Flexi</option>
                                        <option value="regular" <?= $filter_type === 'regular' ? 'selected' : '' ?>>Regular</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex gap-2">
                                    <button class="btn btn-primary btn-sm">Apply</button>
                                    <a href="holiday_management.php" class="btn btn-outline-secondary btn-sm">Clear</a>
                                </div>
                            </form>

                            <!-- ============ TABLE ============ -->
                            <?php if (!$can_load): ?>
                                <div class="alert alert-secondary d-flex align-items-center">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Select a <strong>year</strong> (location is optional) and click <strong>Apply</strong> to load holidays.
                                </div>
                            <?php else: ?>
                                <h5 class="mb-3">
                                    Holidays — <?= (int)$filter_year ?>
                                    <?php if ($filter_location): ?>
                                        @ <?= htmlspecialchars($locations[array_search($filter_location, array_column($locations, 'id'))]['name'] ?? '') ?>
                                    <?php endif; ?>
                                </h5>
                                <?php if (empty($holidays)): ?>
                                    <div class="alert alert-info">No holidays found for these filters.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table id="holiday-table" class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Holiday Name</th>
                                                    <th>Date</th>
                                                    <th>Day</th>
                                                    <th>Location</th>
                                                    <th>Type</th>
                                                    <?php if ($is_admin): ?><th>Actions</th><?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($holidays as $h): ?>
                                                    <tr>
                                                        <td><?= (int)$h['id'] ?></td>
                                                        <td><?= htmlspecialchars($h['holiday_name']) ?></td>
                                                        <td><?= date('d-M-Y', strtotime($h['holiday_date'])) ?></td>
                                                        <td><?= date('D', strtotime($h['holiday_date'])) ?></td>
                                                        <td><?= htmlspecialchars($h['location_name'] ?? '—') ?></td>
                                                        <td>
                                                            <?php if ($h['is_flexi'] === 't'): ?>
                                                                <span class="badge bg-warning text-dark type-badge">Flexi</span>
                                                            <?php elseif ($h['is_public'] === 't'): ?>
                                                                <span class="badge bg-success type-badge">Public</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary type-badge">Regular</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <?php if ($is_admin): ?>
                                                            <td>
                                                                <?php
                                                                $delParams = $_GET;
                                                                $delParams['delete'] = (int)$h['id'];
                                                                ?>
                                                                <a href="holiday_management.php?<?= htmlspecialchars(http_build_query($delParams)) ?>"
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    onclick="return confirm('Delete this holiday?');">
                                                                    <i class="bi bi-trash"></i>
                                                                </a>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            <?php if (!empty($holidays)) : ?>
                $('#holiday-table').DataTable({
                    "order": [],
                    "columnDefs": [{
                        "orderable": false,
                        "targets": -1
                    }]
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>