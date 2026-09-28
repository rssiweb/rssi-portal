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
   Handle form submission (add / update exception)
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exception_date = pg_escape_string($con, $_POST['exception_date']);
    $is_workday     = isset($_POST['is_workday']) ? 'true' : 'false';

    $query = "INSERT INTO workday_exceptions (exception_date, is_workday)
              VALUES ('$exception_date', $is_workday)
              ON CONFLICT (exception_date)
              DO UPDATE SET is_workday = EXCLUDED.is_workday";
    pg_query($con, $query);

    $_SESSION['success_message'] = "Exception saved for $exception_date.";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* =========================================================
   Fetch existing exceptions
   ========================================================= */
$result = pg_query($con, "SELECT * FROM workday_exceptions ORDER BY exception_date DESC");
if (!$result) {
    echo "An error occurred.\n";
    exit;
}
$resultArr = pg_fetch_all($result) ?: [];
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

                            <!-- ============ FORM ============ -->
                            <h5 class="mb-3">Add / Modify Workday Exception</h5>
                            <form method="POST" class="row g-3 align-items-end mb-4">
                                <div class="col-md-4">
                                    <label for="exception_date" class="form-label">Select Date</label>
                                    <input type="date" class="form-control" id="exception_date" name="exception_date" required>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" class="form-check-input" id="is_workday" name="is_workday">
                                        <label class="form-check-label" for="is_workday">Is Workday</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-save me-1"></i> Add / Modify Exception
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
                                                <th>Workday</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($resultArr as $row): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['exception_date']) ?></td>
                                                    <td>
                                                        <?php if ($row['is_workday'] === 't' || $row['is_workday'] === true || $row['is_workday'] === 'true'): ?>
                                                            <span class="badge bg-success">Yes</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No</span>
                                                        <?php endif; ?>
                                                    </td>
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
                    "order": []
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>