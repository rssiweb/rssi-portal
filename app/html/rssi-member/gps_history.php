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

$assetid = isset($_GET['assetid']) ? trim($_GET['assetid']) : '';

if (!empty($assetid)) {
    $assetid_escaped = pg_escape_string($con, $assetid);
    $query = "SELECT itemid, update_type, updatedby, date, changes FROM gps_history WHERE itemid='$assetid_escaped' ORDER BY date DESC";
} else {
    // If no asset ID provided, fetch no records
    $query = "SELECT itemid, update_type, updatedby, date, changes FROM gps_history WHERE false";
}

$result = pg_query($con, $query);
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
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
    <title>GPS History <?php echo $assetid ?></title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">

    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>GPS History</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item"><a href="gps.php">GPS</a></li>
                    <li class="breadcrumb-item active">GPS History</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <!-- Reports -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <form action="" method="GET" class="row g-3 mt-3 mb-4">
                                <div class="col-md-2">
                                    <input name="assetid" id="assetid" class="form-control" placeholder="Enter Asset ID" value="<?php echo $assetid ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" name="search_by_id" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                    <?php if ($assetid): ?>
                                        <a href="gps_history.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-circle"></i> Clear
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table" id="gpsHistoryTable">
                                    <thead>
                                        <tr>
                                            <th scope="col">Asset ID</th>
                                            <th scope="col">Update Type</th>
                                            <th scope="col">Changes</th>
                                            <th scope="col">Updated By</th>
                                            <th scope="col">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (sizeof($resultArr) > 0): ?>
                                            <?php foreach ($resultArr as $array):
                                                $changes = json_decode($array['changes'], true);
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($array['itemid']) ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($array['update_type'] ?? 'Unknown'); ?>
                                                    </td>
                                                    <td>
                                                        <?php if (is_array($changes) && count($changes) > 0): ?>
                                                            <div class="changes-container">
                                                                <?php foreach ($changes as $field => $value): ?>
                                                                    <div class="change-detail">
                                                                        <strong><?php echo htmlspecialchars((string)($field ?? '')); ?>:</strong>
                                                                        <?php if (is_array($value)): ?>
                                                                            <ul>
                                                                                <?php foreach ($value as $subKey => $subValue): ?>
                                                                                    <li>
                                                                                        <?php echo htmlspecialchars((string)$subKey); ?>: <?php echo htmlspecialchars((string)$subValue); ?>
                                                                                    </li>
                                                                                <?php endforeach; ?>
                                                                            </ul>
                                                                        <?php else: ?>
                                                                            <?php
                                                                            $text = (string)($value ?? '');
                                                                            $maxLength = 30;
                                                                            if (mb_strlen($text) > $maxLength) {
                                                                                $shortText = mb_substr($text, 0, $maxLength) . '...';
                                                                                $fullText = $text;
                                                                            } else {
                                                                                $shortText = $text;
                                                                                $fullText = '';
                                                                            }
                                                                            ?>
                                                                            <?php if ($fullText): ?>
                                                                                <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($fullText); ?>">
                                                                                    <?php echo htmlspecialchars($shortText); ?>
                                                                                </span>
                                                                            <?php else: ?>
                                                                                <?php echo htmlspecialchars($shortText); ?>
                                                                            <?php endif; ?>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted">No changes recorded</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($array['updatedby'] ?? 'Unknown'); ?></td>
                                                    <td>
                                                        <?php if ($array['date'] != null): ?>
                                                            <?php echo date("d/m/Y g:i a", strtotime($array['date'])) ?>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">
                                                    <?php if ($assetid == null): ?>
                                                        Please enter an Asset ID to search.
                                                    <?php else: ?>
                                                        No history records found for the selected Asset ID.
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>
    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables
                $('#gpsHistoryTable').DataTable({
                    "order": [] // Disable initial sorting
                });
            <?php endif; ?>
            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
</body>

</html>