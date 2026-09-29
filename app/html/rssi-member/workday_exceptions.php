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

/* =========================================================
   DELETE
   ========================================================= */
if (isset($_GET['delete']) && ($role ?? '') === 'Admin') {
    $del_id = (int)$_GET['delete'];
    $r = pg_query_params($con, "DELETE FROM workday_exceptions WHERE id = $1", [$del_id]);
    if ($r) {
        $_SESSION['success_message'] = "Workday exception deleted.";
    } else {
        $_SESSION['error_message'] = "Delete failed: " . pg_last_error($con);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* =========================================================
   ADD / EDIT
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    $exception_date = pg_escape_string($con, $_POST['exception_date'] ?? '');
    $is_workday     = isset($_POST['is_workday']) ? 'true' : 'false';
    $location_id    = (int)($_POST['location_id'] ?? 0);
    $remarks        = trim($_POST['remarks'] ?? '');

    if ($exception_date === '') {
        $_SESSION['error_message'] = "Please select a date.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Treat 0 as NULL for location (means "all locations")
    $loc_param = $location_id > 0 ? $location_id : null;

    if ($action === 'edit') {
        $edit_id = (int)($_POST['edit_id'] ?? 0);
        if ($edit_id <= 0) {
            $_SESSION['error_message'] = "Invalid edit request.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        $sql = "UPDATE workday_exceptions
                SET exception_date = $1,
                    is_workday     = $2,
                    location_id    = $3,
                    remarks        = $4,
                    updated_by     = $5,
                    updated_at     = NOW()
                WHERE id = $6";
        $res = pg_query_params($con, $sql, [
            $exception_date,
            $is_workday,
            $loc_param,
            $remarks ?: null,
            $associatenumber,
            $edit_id
        ]);

        if ($res) {
            $_SESSION['success_message'] = "Exception updated for $exception_date.";
        } else {
            $_SESSION['error_message'] = "Update failed: " . pg_last_error($con);
        }
    } else { // add
        $sql = "INSERT INTO workday_exceptions
                    (exception_date, is_workday, location_id, remarks, created_by, created_at)
                VALUES ($1, $2, $3, $4, $5, NOW())
                ON CONFLICT (exception_date, COALESCE(location_id, 0))
                DO UPDATE SET is_workday = EXCLUDED.is_workday,
                              remarks    = EXCLUDED.remarks,
                              updated_by = $5,
                              updated_at = NOW()";
        $res = pg_query_params($con, $sql, [
            $exception_date,
            $is_workday,
            $loc_param,
            $remarks ?: null,
            $associatenumber
        ]);

        if ($res) {
            $_SESSION['success_message'] = "Exception saved for $exception_date.";
        } else {
            $_SESSION['error_message'] = "Save failed: " . pg_last_error($con);
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* =========================================================
   LOAD DATA
   ========================================================= */
$locations = [];
$locRes = pg_query($con, "SELECT id, name FROM office_locations WHERE is_active = true ORDER BY name");
if ($locRes) {
    $locations = pg_fetch_all($locRes);
}

// Location id → name map
$locationMap = [];
$lm = pg_query($con, "SELECT id, name FROM office_locations");
while ($r = pg_fetch_assoc($lm)) {
    $locationMap[$r['id']] = $r['name'];
}

// Fetch all exceptions with created/updated user names
$sql = "
    SELECT we.*,
           creator.fullname  AS created_by_name,
           updater.fullname  AS updated_by_name,
           ol.name           AS location_name
    FROM workday_exceptions we
    LEFT JOIN rssimyaccount_members creator ON creator.associatenumber = we.created_by
    LEFT JOIN rssimyaccount_members updater ON updater.associatenumber = we.updated_by
    LEFT JOIN office_locations ol          ON ol.id = we.location_id
    ORDER BY we.exception_date DESC, ol.name";
$result    = pg_query($con, $sql);
$resultArr = $result ? (pg_fetch_all($result) ?: []) : [];
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

                            <!-- ============ FORM ============ -->
                            <h5 class="mb-3">Add / Modify Workday Exception</h5>
                            <form method="POST" class="row g-3 align-items-end mb-4" id="exceptionForm">
                                <input type="hidden" name="action" id="form_action" value="add">
                                <input type="hidden" name="edit_id" id="form_edit_id" value="">

                                <div class="col-md-3">
                                    <label for="exception_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="exception_date" name="exception_date" required>
                                </div>

                                <div class="col-md-3">
                                    <label for="location_id" class="form-label">Location (Leave blank for all locations)</label>
                                    <select class="form-select" id="location_id" name="location_id">
                                        <option value="">All locations</option>
                                        <?php foreach ($locations as $loc): ?>
                                            <option value="<?= (int)$loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <!-- <small class="text-muted">Leave blank for all locations</small> -->
                                </div>

                                <div class="col-md-3">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea class="form-control" id="remarks" name="remarks" placeholder="Optional"></textarea>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" class="form-check-input" id="is_workday" name="is_workday">
                                        <label class="form-check-label" for="is_workday">Is Workday</label>
                                    </div>
                                </div>

                                <div class="col-12 text-end">
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">Clear</button>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="bi bi-save me-1"></i> <span id="submitText">Add / Modify Exception</span>
                                    </button>
                                </div>
                            </form>

                            <!-- ============ TABLE ============ -->
                            <h5 class="mb-3 mt-4">Current Exceptions</h5>
                            <?php if (empty($resultArr)): ?>
                                <div class="alert alert-info">No exceptions recorded yet.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="table-id" class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Location</th>
                                                <th>Workday</th>
                                                <th>Remarks</th>
                                                <th>Created</th>
                                                <th>Updated</th>
                                                <?php if (($role ?? '') === 'Admin'): ?><th>Actions</th><?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($resultArr as $row): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(date('d-M-Y', strtotime($row['exception_date']))) ?></td>
                                                    <td><?= htmlspecialchars($row['location_name'] ?? 'All locations') ?></td>
                                                    <td>
                                                        <?php if ($row['is_workday'] === 't' || $row['is_workday'] === true || $row['is_workday'] === 'true'): ?>
                                                            <span class="badge bg-success">Yes</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['remarks'] ?? '') ?></td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($row['created_by_name'] ?? ($row['created_by'] ?? '—')) ?>
                                                            <?php if (!empty($row['created_at'])): ?>
                                                                <br><?= date('d-M-Y h:i A', strtotime($row['created_at'])) ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($row['updated_at'])): ?>
                                                            <small class="text-muted">
                                                                <?= htmlspecialchars($row['updated_by_name'] ?? ($row['updated_by'] ?? '—')) ?>
                                                                <br><?= date('d-M-Y h:i A', strtotime($row['updated_at'])) ?>
                                                            </small>
                                                        <?php else: ?>
                                                            <span class="text-muted">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <?php if (($role ?? '') === 'Admin'): ?>
                                                        <td>
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-primary edit-btn"
                                                                data-id="<?= (int)$row['id'] ?>"
                                                                data-date="<?= htmlspecialchars($row['exception_date']) ?>"
                                                                data-location="<?= (int)($row['location_id'] ?? 0) ?>"
                                                                data-workday="<?= ($row['is_workday'] === 't' || $row['is_workday'] === true || $row['is_workday'] === 'true') ? '1' : '0' ?>"
                                                                data-remarks="<?= htmlspecialchars($row['remarks'] ?? '') ?>">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <a href="?delete=<?= (int)$row['id'] ?>"
                                                                class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Delete this exception?');">
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
            <?php if (!empty($resultArr)) : ?>
                $('#table-id').DataTable({
                    "order": [],
                    "columnDefs": [{
                        "orderable": false,
                        "targets": -1
                    }]
                });
            <?php endif; ?>

            // Edit button — populate the form
            $(document).on('click', '.edit-btn', function() {
                const $btn = $(this);

                $('#form_action').val('edit');
                $('#form_edit_id').val($btn.data('id'));
                $('#exception_date').val($btn.data('date'));
                $('#location_id').val($btn.data('location') ? String($btn.data('location')) : '');
                $('#remarks').val($btn.data('remarks') || '');
                $('#is_workday').prop('checked', String($btn.data('workday')) === '1');
                $('#submitText').text('Update Exception');

                // Scroll up to the form
                $('html, body').animate({
                    scrollTop: $('#exceptionForm').offset().top - 100
                }, 300);
            });
        });

        function resetForm() {
            $('#form_action').val('add');
            $('#form_edit_id').val('');
            $('#exceptionForm')[0].reset();
            $('#submitText').text('Add / Modify Exception');
        }
    </script>
</body>

</html>