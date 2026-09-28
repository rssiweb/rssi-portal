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
$is_centreIncharge = ($position == 'Centre Incharge' || $position == 'Senior Centre Incharge');
$can_create = ($is_admin || $is_centreIncharge);

/* =========================================================
   ROUTING
   action = list (default) | detail | new
   ========================================================= */
$action = $_GET['action'] ?? 'list';
$exception_id = isset($_GET['exception_id']) ? (int)$_GET['exception_id'] : null;

/* =========================================================
   Clear stale filter results when ENTERING the "new" view
   via a fresh GET (i.e., not a filter POST-back).
   ========================================================= */
if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && ($_GET['action'] ?? 'list') === 'new'
) {
    unset($_SESSION['filtered_results']);
}

/* =========================================================
   DELETE
   ========================================================= */
if (isset($_GET['delete_exception']) && $is_admin) {
    $del_id = (int)$_GET['delete_exception'];
    pg_query($con, "BEGIN");
    try {
        pg_query_params($con, "DELETE FROM student_exception_mapping WHERE exception_id = $1", [$del_id]);
        $del = pg_query_params($con, "DELETE FROM student_class_days_exceptions WHERE exception_id = $1", [$del_id]);
        if (!$del || pg_affected_rows($del) == 0) throw new Exception("Exception not found");
        pg_query($con, "COMMIT");
        $_SESSION['success_message'] = "Exception deleted successfully.";
    } catch (Exception $e) {
        pg_query($con, "ROLLBACK");
        $_SESSION['error_message'] = "Delete failed: " . $e->getMessage();
    }
    header("Location: class_days_exception.php");
    exit;
}

/* =========================================================
   HANDLER — Filter students (student scope)
   ========================================================= */
if (@$_POST['form-type'] == "exception_filter") {
    $class        = $_POST['class']         ?? [];
    $category     = $_POST['category']      ?? [];
    $student_ids  = $_POST['student_ids']   ?? [];
    $excluded_ids = $_POST['excluded_ids']  ?? [];

    $query = "SELECT student_id, studentname, category, class
              FROM rssimyprofile_student
              WHERE filterstatus = 'Active'";
    $conditions = [];

    if (!empty($class)) {
        $class_list = implode("','", array_map(fn($c) => pg_escape_string($con, $c), $class));
        $conditions[] = "class IN ('$class_list')";
    }
    if (!empty($category)) {
        $category_list = implode("','", array_map(fn($c) => pg_escape_string($con, $c), $category));
        $conditions[] = "category IN ('$category_list')";
    }
    if (!empty($student_ids)) {
        $ids_list = implode("','", array_map(fn($id) => pg_escape_string($con, $id), $student_ids));
        $conditions[] = "student_id IN ('$ids_list')";
    }
    if (!empty($excluded_ids)) {
        $excl_list = implode("','", array_map(fn($id) => pg_escape_string($con, $id), $excluded_ids));
        $conditions[] = "student_id NOT IN ('$excl_list')";
    }

    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
        $result = pg_query($con, $query);
        if (!$result) {
            echo "Query error.\n";
            exit;
        }
        $resultArr = pg_fetch_all($result);
        $_SESSION['filtered_results'] = $resultArr;
    } else {
        $resultArr = null;
        $_SESSION['filtered_results'] = null;
    }
}

/* =========================================================
   HANDLER — Create exception
   ========================================================= */
if (isset($_POST['form-type']) && $_POST['form-type'] == "exception") {
    $scope_type = $_POST['scope_type'] ?? 'student';
    $reason     = trim($_POST['reason'] ?? '');
    $created_by = $associatenumber;

    if ($reason === '') {
        echo "Reason is required.";
        exit;
    }

    if ($scope_type === 'class') {
        $scope_classes   = $_POST['scope_classes']   ?? [];
        $scope_locations = $_POST['scope_locations'] ?? [];

        $scope_classes = array_values(array_filter(array_map(
            fn($c) => trim(pg_escape_string($con, $c)),
            (array)$scope_classes
        )));
        $scope_locations = array_values(array_filter(array_map(
            'intval',
            (array)$scope_locations
        ), fn($v) => $v > 0));

        if (empty($scope_classes)) {
            echo "Please select at least one class.";
            exit;
        }
    } else {
        if (empty($_SESSION['filtered_results'])) {
            echo "Please filter students first.";
            exit;
        }
        $resultArr = $_SESSION['filtered_results'];
    }

    // Date list
    $dates = [];
    if (!empty($_POST['multiple_days'])) {
        $start = $_POST['start_date'] ?? '';
        $end   = $_POST['end_date']   ?? '';
        if (!$start || !$end) {
            echo "Start and end dates required.";
            exit;
        }
        $period = new DatePeriod(
            new DateTime($start),
            new DateInterval('P1D'),
            (new DateTime($end))->modify('+1 day')
        );
        foreach ($period as $d) {
            $dates[] = $d->format('Y-m-d');
        }
    } else {
        $single = $_POST['exception_date'] ?? '';
        if (!$single) {
            echo "Exception date required.";
            exit;
        }
        $dates[] = $single;
    }

    $messages = [];
    pg_query($con, "BEGIN");
    try {
        foreach ($dates as $exception_date) {
            $res = pg_query_params(
                $con,
                "INSERT INTO student_class_days_exceptions
                    (exception_date, reason, created_by, scope_type)
                 VALUES ($1, $2, $3, $4)
                 RETURNING exception_id",
                [$exception_date, $reason, $created_by, $scope_type]
            );
            if (!$res) throw new Exception(pg_last_error($con));
            $new_id = pg_fetch_result($res, 0, 'exception_id');

            if ($scope_type === 'student') {
                foreach ($resultArr as $row) {
                    pg_query_params(
                        $con,
                        "INSERT INTO student_exception_mapping (exception_id, student_id) VALUES ($1, $2)",
                        [$new_id, $row['student_id']]
                    );
                }
                $messages[] = "$exception_date — " . count($resultArr) . " student(s)";
            } else {
                foreach ($scope_classes as $cls) {
                    pg_query_params(
                        $con,
                        "INSERT INTO exception_class_scope (exception_id, class) VALUES ($1, $2)
                         ON CONFLICT DO NOTHING",
                        [$new_id, $cls]
                    );
                }
                foreach ($scope_locations as $loc) {
                    pg_query_params(
                        $con,
                        "INSERT INTO exception_location_scope (exception_id, location_id) VALUES ($1, $2)
                         ON CONFLICT DO NOTHING",
                        [$new_id, $loc]
                    );
                }
                $clsLabel = implode(', ', $scope_classes);
                $locLabel = empty($scope_locations) ? 'all locations' : count($scope_locations) . ' location(s)';
                $messages[] = "$exception_date — classes: $clsLabel ($locLabel)";
            }
        }
        pg_query($con, "COMMIT");
        $_SESSION['success_message'] = "Exception created: " . implode(' | ', $messages);
    } catch (Exception $e) {
        pg_query($con, "ROLLBACK");
        $_SESSION['error_message'] = "Creation failed: " . $e->getMessage();
    }
    unset($_SESSION['filtered_results']);
    header("Location: class_days_exception.php");
    exit;
}

/* =========================================================
   LOAD DATA FOR CURRENT VIEW
   ========================================================= */
$exception = null;
$affected  = [];
$declared_classes   = [];
$declared_locations = [];

if ($action === 'detail' && $exception_id) {
    $r = pg_query_params(
        $con,
        "SELECT * FROM student_class_days_exceptions WHERE exception_id = $1",
        [$exception_id]
    );
    $exception = pg_fetch_assoc($r);

    if ($exception) {
        $rr = pg_query_params(
            $con,
            "SELECT student_id, studentname, class, location_id
             FROM v_exception_students
             WHERE exception_id = $1
             ORDER BY class, studentname",
            [$exception_id]
        );
        $affected = $rr ? pg_fetch_all($rr) : [];

        if (($exception['scope_type'] ?? '') === 'class') {
            $cr = pg_query_params(
                $con,
                "SELECT class FROM exception_class_scope WHERE exception_id = $1 ORDER BY class",
                [$exception_id]
            );
            $declared_classes = $cr ? pg_fetch_all_columns($cr, 0) : [];

            $lr = pg_query_params(
                $con,
                "SELECT els.location_id, ol.name
                 FROM exception_location_scope els
                 LEFT JOIN office_locations ol ON ol.id = els.location_id
                 WHERE els.exception_id = $1
                 ORDER BY ol.name",
                [$exception_id]
            );
            $declared_locations = $lr ? pg_fetch_all($lr) : [];
        }
    }
}

$locationMap = [];
$lm = pg_query($con, "SELECT id, name FROM office_locations");
while ($row = pg_fetch_assoc($lm)) {
    $locationMap[$row['id']] = $row['name'];
}

// List view data
$exceptions = [];
$total_exceptions = 0;
$total_pages = 1;
$page = 1;
$offset = 0;
$per_page = 20;
$search = '';
$date_from = '';
$date_to = '';

if ($action === 'list') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * $per_page;
    $search    = isset($_GET['search'])    ? pg_escape_string($con, $_GET['search']) : '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to   = $_GET['date_to']   ?? '';

    $base = "FROM student_class_days_exceptions e
             LEFT JOIN student_exception_mapping m ON m.exception_id = e.exception_id
             LEFT JOIN rssimyprofile_student s     ON m.student_id   = s.student_id";
    $conds = [];
    $params = [];
    $pc = 0;
    if ($search !== '') {
        $conds[] = "(e.reason ILIKE $" . ++$pc . " OR e.created_by ILIKE $" . $pc . ")";
        $params[] = "%$search%";
    }
    if ($date_from !== '') {
        $conds[] = "e.exception_date >= $" . ++$pc;
        $params[] = $date_from;
    }
    if ($date_to   !== '') {
        $conds[] = "e.exception_date <= $" . ++$pc;
        $params[] = $date_to;
    }
    $where = $conds ? "WHERE " . implode(" AND ", $conds) : "";

    $cnt = pg_query_params($con, "SELECT COUNT(DISTINCT e.exception_id) AS total $base $where", $params);
    $total_exceptions = (int)pg_fetch_result($cnt, 0, 'total');
    $total_pages = max(1, (int)ceil($total_exceptions / $per_page));

    $q = "
        SELECT e.exception_id, e.exception_date, e.reason, e.created_by, e.created_at, e.scope_type,
               COUNT(DISTINCT m.student_id) AS student_count,

               COALESCE(
                   (SELECT STRING_AGG(ecs.class, ', ' ORDER BY ecs.class)
                    FROM exception_class_scope ecs
                    WHERE ecs.exception_id = e.exception_id),
                   ''
               ) AS classes_scoped,

               COALESCE(
                   (SELECT STRING_AGG(ol.name, ', ' ORDER BY ol.name)
                    FROM exception_location_scope els
                    JOIN office_locations ol ON ol.id = els.location_id
                    WHERE els.exception_id = e.exception_id),
                   ''
               ) AS locations_scoped,

               STRING_AGG(DISTINCT s.class, ', ') AS classes_affected

        $base
        $where
        GROUP BY e.exception_id
        ORDER BY e.exception_date DESC, e.exception_id DESC
        LIMIT $per_page OFFSET $offset";
    $res = pg_query_params($con, $q, $params);
    $exceptions = $res ? pg_fetch_all($res) : [];
}

/* =========================================================
   DROPDOWNS for create view
   ========================================================= */
$class_options = [];
$cls_res = pg_query($con, "SELECT DISTINCT class FROM rssimyprofile_student
                           WHERE class IS NOT NULL AND filterstatus='Active'
                           ORDER BY class");
if ($cls_res) {
    $class_options = pg_fetch_all_columns($cls_res, 0);
}

$location_options = [];
$loc_res = pg_query($con, "SELECT id, name FROM office_locations WHERE is_active = true ORDER BY name");
if ($loc_res) {
    $location_options = pg_fetch_all($loc_res);
}

/* Re-fetch filtered students for the create view after page reload */
$filtered_results = $_SESSION['filtered_results'] ?? [];

function scope_label($row)
{
    $t = $row['scope_type'] ?? 'student';

    if ($t === 'class') {
        $cls = trim((string)($row['classes_scoped'] ?? ''));
        $loc = trim((string)($row['locations_scoped'] ?? ''));

        $clsTxt = $cls === '' ? '(no class)' : $cls;
        $locTxt = $loc === '' ? 'all locations' : $loc;

        return '<span class="badge bg-info text-dark">Class: '
            . htmlspecialchars($clsTxt) . ' @ ' . htmlspecialchars($locTxt)
            . '</span>';
    }

    return '<span class="badge bg-primary">Students</span>';
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">
    <style>
        .asterisk {
            color: red;
            margin-left: 4px;
        }

        .back-link {
            color: #0d6efd;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .scope-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 14px 18px;
            cursor: pointer;
            transition: all .2s;
        }

        .scope-card:hover {
            border-color: #0d6efd;
            background: #f8f9ff;
        }

        .scope-card.active {
            border-color: #0d6efd;
            background: #eef4ff;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, .15);
        }

        .scope-card input[type=radio] {
            margin-right: 8px;
        }

        .clickable-row {
            cursor: pointer;
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
                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success alert-dismissible fade show mt-3">
                                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php unset($_SESSION['success_message']); ?>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show mt-3">
                                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php unset($_SESSION['error_message']); ?>
                            <?php endif; ?>

                            <div class="container mt-3">

                                <!-- ========================= LIST VIEW ========================= -->
                                <?php if ($action === 'list'): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="mb-0">Class Days Exceptions</h4>
                                        <?php if ($can_create): ?>
                                            <a href="class_days_exception.php?action=new" class="btn btn-primary">
                                                <i class="bi bi-plus-circle"></i> Create New
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-4">
                                            <form method="get">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="search"
                                                        placeholder="Search reason or creator..."
                                                        value="<?= htmlspecialchars($search) ?>">
                                                    <button class="btn btn-outline-secondary" type="submit">
                                                        <i class="bi bi-search"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="col-md-5">
                                            <form method="get" class="date-range-form">
                                                <div class="row g-2">
                                                    <div class="col-md-5">
                                                        <input type="date" class="form-control" name="date_from"
                                                            value="<?= htmlspecialchars($date_from) ?>">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <input type="date" class="form-control" name="date_to"
                                                            value="<?= htmlspecialchars($date_to) ?>">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button class="btn btn-primary w-100">Filter</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <a href="class_days_exception.php" class="btn btn-outline-secondary">Reset</a>
                                        </div>
                                    </div>

                                    <?php if (empty($exceptions)): ?>
                                        <div class="alert alert-info">No exceptions found.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Scope</th>
                                                        <th>Reason</th>
                                                        <th>Students</th>
                                                        <th>Classes Affected</th>
                                                        <th>Created By</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($exceptions as $ex): ?>
                                                        <tr class="clickable-row"
                                                            onclick="window.location='class_days_exception.php?action=detail&exception_id=<?= (int)$ex['exception_id'] ?>'">
                                                            <td>
                                                                <?= date('M j, Y', strtotime($ex['exception_date'])) ?>
                                                                <br><small class="text-muted"><?= date('D', strtotime($ex['exception_date'])) ?></small>
                                                            </td>
                                                            <td><?= scope_label($ex) ?></td>
                                                            <td><?= htmlspecialchars($ex['reason']) ?></td>
                                                            <td><?= (int)$ex['student_count'] ?></td>
                                                            <td>
                                                                <?php if ($ex['classes_affected']): ?>
                                                                    <?= implode(', ', array_unique(explode(', ', $ex['classes_affected']))) ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">—</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?= htmlspecialchars($ex['created_by']) ?>
                                                                <br><small class="text-muted"><?= date('M j', strtotime($ex['created_at'])) ?></small>
                                                            </td>
                                                            <td>
                                                                <a href="class_days_exception.php?action=detail&exception_id=<?= (int)$ex['exception_id'] ?>"
                                                                    class="btn btn-sm btn-outline-primary"
                                                                    onclick="event.stopPropagation();">View</a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <nav>
                                            <ul class="pagination justify-content-center">
                                                <?php if ($page > 1): ?>
                                                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">First</a></li>
                                                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a></li>
                                                <?php endif; ?>
                                                <?php
                                                $sp = max(1, $page - 2);
                                                $ep = min($total_pages, $page + 2);
                                                for ($i = $sp; $i <= $ep; $i++): ?>
                                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                                    </li>
                                                <?php endfor; ?>
                                                <?php if ($page < $total_pages): ?>
                                                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a></li>
                                                    <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">Last</a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                        <div class="text-center text-muted">
                                            Showing <?= ($offset + 1) ?> to <?= min($offset + $per_page, $total_exceptions) ?>
                                            of <?= $total_exceptions ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- ========================= DETAIL VIEW ========================= -->
                                <?php elseif ($action === 'detail'): ?>
                                    <?php if (!$exception): ?>
                                        <div class="alert alert-warning">Exception not found.</div>
                                        <a href="class_days_exception.php" class="btn btn-outline-secondary">Back to list</a>
                                    <?php else: ?>
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                                                <a href="class_days_exception.php" class="back-link">
                                                    <i class="bi bi-arrow-left"></i> Back to all exceptions
                                                </a>
                                                <h4 class="mt-2 mb-0">Exception #<?= (int)$exception['exception_id'] ?></h4>
                                            </div>
                                            <span class="badge bg-primary rounded-pill">
                                                <?= count($affected) ?> student(s) affected
                                            </span>
                                        </div>

                                        <div class="card mb-4">
                                            <div class="card-body">
                                                <div class="row mt-3">
                                                    <div class="col-md-3">
                                                        <h6 class="text-muted">Date</h6>
                                                        <p><?= date('F j, Y', strtotime($exception['exception_date'])) ?></p>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <h6 class="text-muted">Scope</h6>
                                                        <p>
                                                            <?php if (($exception['scope_type'] ?? '') === 'class'): ?>
                                                                <?php
                                                                $clsTxt = empty($declared_classes) ? '(no class)' : implode(', ', $declared_classes);
                                                                $locNames = array_filter(array_map(
                                                                    fn($l) => $l['name'] ?? null,
                                                                    $declared_locations
                                                                ));
                                                                $locTxt = empty($locNames) ? 'all locations' : implode(', ', $locNames);
                                                                ?>
                                                                <span class="badge bg-info text-dark">
                                                                    Class: <?= htmlspecialchars($clsTxt) ?> @ <?= htmlspecialchars($locTxt) ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-primary">Students</span>
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <h6 class="text-muted">Created By</h6>
                                                        <p><?= htmlspecialchars($exception['created_by']) ?></p>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <h6 class="text-muted">Created On</h6>
                                                        <p><?= date('M j, Y g:i A', strtotime($exception['created_at'])) ?></p>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <h6 class="text-muted">Reason</h6>
                                                        <p><?= nl2br(htmlspecialchars($exception['reason'])) ?></p>
                                                    </div>
                                                </div>

                                                <?php if (($exception['scope_type'] ?? '') === 'class'): ?>
                                                    <hr>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6 class="text-muted">Declared Classes</h6>
                                                            <p>
                                                                <?php if (empty($declared_classes)): ?>
                                                                    <span class="text-muted">—</span>
                                                                <?php else: ?>
                                                                    <?php foreach ($declared_classes as $c): ?>
                                                                        <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($c) ?></span>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6 class="text-muted">Declared Locations</h6>
                                                            <p>
                                                                <?php if (empty($declared_locations)): ?>
                                                                    <span class="text-muted">All locations</span>
                                                                <?php else: ?>
                                                                    <?php foreach ($declared_locations as $l): ?>
                                                                        <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($l['name'] ?? '—') ?></span>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($is_admin): ?>
                                                <div class="card-footer text-end">
                                                    <button class="btn btn-sm btn-danger"
                                                        onclick="confirmDelete(<?= (int)$exception['exception_id'] ?>)">
                                                        <i class="bi bi-trash"></i> Delete Exception
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <h5 class="mb-3">Affected Students</h5>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width:15%">Student ID</th>
                                                        <th style="width:35%">Student Name</th>
                                                        <th style="width:25%">Class (on date)</th>
                                                        <th style="width:25%">Location (on date)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($affected)): ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center py-3">No students affected.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($affected as $s): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($s['student_id']) ?></td>
                                                                <td><?= htmlspecialchars($s['studentname']) ?></td>
                                                                <td><?= htmlspecialchars($s['class'] ?? '—') ?></td>
                                                                <td><?= htmlspecialchars($locationMap[$s['location_id']] ?? '—') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <!-- ========================= CREATE VIEW ========================= -->
                                <?php elseif ($action === 'new'): ?>
                                    <?php if (!$can_create): ?>
                                        <div class="alert alert-danger">You don't have permission to create exceptions.</div>
                                    <?php else: ?>
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <a href="class_days_exception.php" class="back-link">
                                                <i class="bi bi-arrow-left"></i> Back to all exceptions
                                            </a>
                                        </div>

                                        <h4>Create Class Days Exception</h4>

                                        <!-- SCOPE PICKER -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Apply exception to:</label>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="scope-card d-block active" data-scope="student">
                                                        <input type="radio" name="scope_type_ui" value="student" checked>
                                                        <strong><i class="bi bi-person-lines-fill me-1"></i> Specific Students</strong>
                                                        <div class="small text-muted mt-1">Pick individual students using filters</div>
                                                    </label>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="scope-card d-block" data-scope="class">
                                                        <input type="radio" name="scope_type_ui" value="class">
                                                        <strong><i class="bi bi-people-fill me-1"></i> Class + Location</strong>
                                                        <div class="small text-muted mt-1">Multi-select classes and locations</div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- STUDENT PANEL -->
                                        <div id="scope-student-panel">
                                            <div class="mb-3 py-2 text-muted">
                                                Filter students using any combination of the filters below.
                                            </div>
                                            <form method="post" action="" class="row g-2 align-items-end mb-4">
                                                <input type="hidden" name="form-type" value="exception_filter">

                                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                                    <label class="form-label small mb-1">Class</label>
                                                    <select class="form-select" id="classes" name="class[]" multiple></select>
                                                </div>
                                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                                    <label class="form-label small mb-1">Category</label>
                                                    <select class="form-select" id="categories" name="category[]" multiple></select>
                                                </div>
                                                <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                                                    <label class="form-label small mb-1">Include Student IDs</label>
                                                    <select class="form-select" id="student_ids" name="student_ids[]" multiple></select>
                                                </div>
                                                <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                                                    <label class="form-label small mb-1">Exclude Student IDs</label>
                                                    <select class="form-select" id="excluded_ids" name="excluded_ids[]" multiple></select>
                                                </div>
                                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="bi bi-funnel-fill me-1"></i> Filter
                                                    </button>
                                                </div>
                                            </form>

                                            <?php if (!empty($filtered_results)): ?>
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h5 class="mb-0">Students in scope</h5>
                                                    <span>Total Students: <?= count($filtered_results) ?></span>
                                                </div>
                                                <div class="table-responsive mb-4">
                                                    <table class="table table-sm table-bordered">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width:15%">Student ID</th>
                                                                <th style="width:35%">Student Name</th>
                                                                <th style="width:25%">Category</th>
                                                                <th style="width:25%">Class</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($filtered_results as $s): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($s['student_id']) ?></td>
                                                                    <td><?= htmlspecialchars($s['studentname']) ?></td>
                                                                    <td><?= htmlspecialchars($s['category']) ?></td>
                                                                    <td><?= htmlspecialchars($s['class']) ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- CLASS PANEL -->
                                        <div id="scope-class-panel" style="display:none;">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Classes <span class="asterisk">*</span></label>
                                                    <select class="form-select" id="scope_classes" multiple>
                                                        <?php foreach ($class_options as $c): ?>
                                                            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <small class="text-muted">Pick one or more classes</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Locations <span class="text-muted small">(optional — blank = all)</span></label>
                                                    <select class="form-select" id="scope_locations" multiple>
                                                        <?php foreach ($location_options as $loc): ?>
                                                            <option value="<?= (int)$loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <small class="text-muted">Pick one or more locations</small>
                                                </div>
                                            </div>
                                            <div class="alert alert-info py-2">
                                                <i class="bi bi-info-circle me-1"></i>
                                                Applies to students whose <strong>class on the exception date</strong> matches one of the selected classes,
                                                <strong>AND</strong> whose location on that date matches one of the selected locations.
                                                Blank location = all locations.
                                            </div>
                                        </div>

                                        <!-- EXCEPTION PARAMS -->
                                        <h5 class="mb-3 mt-4">Exception Parameters</h5>
                                        <form action="" id="exception" method="post">
                                            <input type="hidden" name="form-type" value="exception">
                                            <input type="hidden" name="scope_type" id="scope_type_hidden" value="student">
                                            <div id="hidden-scope-inputs"></div>

                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" id="multiple_days" name="multiple_days" value="1">
                                                        <label class="form-check-label" for="multiple_days">Apply for multiple days</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row single-date">
                                                <div class="col-md-4 mb-3">
                                                    <label for="exception_date" class="form-label">Exception Date <span class="asterisk">*</span></label>
                                                    <input type="date" class="form-control" id="exception_date" name="exception_date">
                                                </div>
                                            </div>

                                            <div class="row date-range" style="display:none;">
                                                <div class="col-md-4 mb-3">
                                                    <label for="start_date" class="form-label">Start Date <span class="asterisk">*</span></label>
                                                    <input type="date" class="form-control" id="start_date" name="start_date">
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label for="end_date" class="form-label">End Date <span class="asterisk">*</span></label>
                                                    <input type="date" class="form-control" id="end_date" name="end_date">
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-8 mb-3">
                                                    <label for="reason" class="form-label">Reason <span class="asterisk">*</span></label>
                                                    <textarea class="form-control" id="reason" name="reason" rows="3"
                                                        placeholder="Enter reason for exception" required></textarea>
                                                </div>
                                            </div>

                                            <div class="text-end mt-3 mb-3">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-check-circle me-1"></i> Apply Exception
                                                </button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            /* ------- Select2 for the "new" view (only runs if those elements exist) ------- */
            if ($('#classes').length) {
                $('#student_ids').select2({
                    ajax: {
                        url: 'fetch_students.php?isActive=true',
                        dataType: 'json',
                        delay: 250,
                        data: p => ({
                            q: p.term
                        }),
                        processResults: d => ({
                            results: d.results
                        }),
                        cache: true
                    },
                    placeholder: 'Search by name or ID',
                    width: '100%',
                    minimumInputLength: 2,
                    multiple: true
                });
                $('#excluded_ids').select2({
                    ajax: {
                        url: 'fetch_students.php?isActive=true',
                        dataType: 'json',
                        delay: 250,
                        data: p => ({
                            q: p.term
                        }),
                        processResults: d => ({
                            results: d.results
                        }),
                        cache: true
                    },
                    placeholder: 'Search by name or ID',
                    width: '100%',
                    minimumInputLength: 2,
                    multiple: true
                });
                $('#categories').select2({
                    ajax: {
                        url: 'fetch_category.php',
                        dataType: 'json',
                        delay: 250,
                        data: p => ({
                            q: p.term
                        }),
                        processResults: d => ({
                            results: d.results
                        }),
                        cache: true
                    },
                    placeholder: 'Search by category',
                    width: '100%',
                    minimumInputLength: 1,
                    multiple: true
                });
                $('#classes').select2({
                    ajax: {
                        url: 'fetch_class.php',
                        dataType: 'json',
                        delay: 250,
                        data: p => ({
                            q: p.term
                        }),
                        processResults: d => ({
                            results: d.results
                        }),
                        cache: true
                    },
                    placeholder: 'Search by class',
                    width: '100%',
                    minimumInputLength: 1,
                    multiple: true
                });
            }
            if ($('#scope_classes').length) {
                $('#scope_classes').select2({
                    placeholder: 'Select one or more classes',
                    width: '100%'
                });
                $('#scope_locations').select2({
                    placeholder: 'All locations (or pick specific ones)',
                    width: '100%'
                });
            }
            if ($('#exception_date').length) {
                $('#exception_date').val(new Date().toISOString().substr(0, 10));
            }

            /* ------- Scope card toggle ------- */
            $('.scope-card').on('click', function() {
                $('.scope-card').removeClass('active');
                $(this).addClass('active');
                $(this).find('input[type=radio]').prop('checked', true);
                const scope = $(this).data('scope');
                $('#scope_type_hidden').val(scope);
                $('#scope-student-panel').toggle(scope === 'student');
                $('#scope-class-panel').toggle(scope === 'class');
            });

            /* ------- Multi-day toggle ------- */
            $('#multiple_days').on('change', function() {
                if (this.checked) {
                    $('.single-date').hide();
                    $('.date-range').css('display', 'flex');
                    $('#exception_date').prop('required', false);
                    $('#start_date').prop('required', true);
                    $('#end_date').prop('required', true);
                } else {
                    $('.single-date').css('display', 'flex');
                    $('.date-range').hide();
                    $('#exception_date').prop('required', true);
                    $('#start_date').prop('required', false);
                    $('#end_date').prop('required', false);
                }
            });

            /* ------- Build hidden inputs for class scope on submit ------- */
            $('#exception').on('submit', function(e) {
                const scope = $('#scope_type_hidden').val();
                const $holder = $('#hidden-scope-inputs').empty();

                if (scope === 'class') {
                    const classes = $('#scope_classes').val() || [];
                    const locations = $('#scope_locations').val() || [];

                    if (classes.length === 0) {
                        alert('Please select at least one class.');
                        e.preventDefault();
                        return;
                    }
                    classes.forEach(c => $('<input>', {
                        type: 'hidden',
                        name: 'scope_classes[]',
                        value: c
                    }).appendTo($holder));
                    locations.forEach(l => $('<input>', {
                        type: 'hidden',
                        name: 'scope_locations[]',
                        value: l
                    }).appendTo($holder));
                } else {
                    if (!<?= json_encode(!empty($filtered_results)) ?>) {
                        alert('Please filter students first (use the Filter button above).');
                        e.preventDefault();
                    }
                }
            });

            /* ------- Prepopulate Select2 after POST-back ------- */
            const selectedClasses = <?= json_encode($_POST['class'] ?? []) ?>;
            const selectedCategories = <?= json_encode($_POST['category'] ?? []) ?>;
            const selectedStudentIds = <?= json_encode($_POST['student_ids'] ?? []) ?>;
            const excludedStudentIds = <?= json_encode($_POST['excluded_ids'] ?? []) ?>;

            function prepopulateSelect2(selector, values, fetchUrl) {
                if (!$(selector).length) return;
                values.forEach(val => {
                    $.ajax({
                            type: 'GET',
                            url: fetchUrl,
                            data: {
                                q: val
                            },
                            dataType: 'json'
                        })
                        .then(data => {
                            const match = data.results.find(o => o.id == val);
                            if (match) {
                                const opt = new Option(match.text, match.id, true, true);
                                $(selector).append(opt).trigger('change');
                            }
                        });
                });
            }
            prepopulateSelect2('#classes', selectedClasses, 'fetch_class.php');
            prepopulateSelect2('#categories', selectedCategories, 'fetch_category.php');
            prepopulateSelect2('#student_ids', selectedStudentIds, 'fetch_students.php?isActive=true');
            prepopulateSelect2('#excluded_ids', excludedStudentIds, 'fetch_students.php?isActive=true');
        });

        function confirmDelete(id) {
            if (confirm("Delete this exception? This will remove it for all affected students.")) {
                window.location.href = 'class_days_exception.php?delete_exception=' + id;
            }
        }
    </script>
</body>

</html>