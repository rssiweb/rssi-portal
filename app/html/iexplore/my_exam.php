<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_iexplore.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
?>
<?php

// Handle filters
$exam_name_filter = $_GET['exam_name'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Default date range: One month ending with the current date
if (empty($start_date) || empty($end_date)) {
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-1 month'));
}

// Build query with filters
$query = "SELECT 
            uexam.id AS attempt_id, 
            exam.id AS exam_id, 
            exam.name AS exam_name, 
            uexam.score, 
            TO_CHAR(uexam.created_at, 'DD-MM-YYYY HH24:MI:SS') AS exam_date
          FROM test_user_exams uexam
          JOIN test_exams exam ON uexam.exam_id = exam.id
          WHERE uexam.user_id = $1";

$conditions = [];
$params = [$id];
$paramIndex = 2;

if (!empty($exam_name_filter)) {
    $conditions[] = "exam.name ILIKE $" . $paramIndex++;
    $params[] = '%' . $exam_name_filter . '%';
}

if (!empty($start_date)) {
    $conditions[] = "uexam.created_at >= $" . $paramIndex++;
    $params[] = $start_date;
}

if (!empty($end_date)) {
    // Include the entire end date by adding one day and comparing less than
    $conditions[] = "uexam.created_at < $" . $paramIndex++;
    $params[] = date('Y-m-d', strtotime($end_date . ' +1 day'));
}

if ($conditions) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " ORDER BY uexam.created_at DESC";

$result = pg_query_params($con, $query, $params);

if (!$result) {
    echo "Error in fetching exam data.";
    exit;
}
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

    <title>My Exams</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>

    <body>
        <?php include 'header.php'; ?>
        <?php include 'inactive_session_expire_check.php'; ?>

        <main id="main" class="main">
            <div class="pagetitle">
                <h1>My Exams</h1>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                        <li class="breadcrumb-item active">My Exams</li>
                    </ol>
                </nav>
            </div><!-- End Page Title -->

            <section class="section dashboard">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <br>
                                <div class="container mt-4">
                                    <!-- <h2>My Exams</h2> -->

                                    <!-- Filter Form -->
                                    <!-- Filter Form -->
                                    <form method="get" class="mb-3">
                                        <div class="row gx-2">
                                            <div class="col-md-3">
                                                <input type="text" name="exam_name" class="form-control"
                                                    placeholder="Search by exam name (partial match)"
                                                    value="<?= htmlspecialchars($exam_name_filter) ?>">
                                                <small class="text-muted">Search by exam name</small>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="date" name="start_date" class="form-control"
                                                    value="<?= htmlspecialchars($start_date) ?>">
                                                <small class="text-muted">Start date (inclusive)</small>
                                            </div>
                                            <div class="col-md-3">
                                                <input type="date" name="end_date" class="form-control"
                                                    value="<?= htmlspecialchars($end_date) ?>">
                                                <small class="text-muted">End date (inclusive)</small>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                            </div>
                                        </div>
                                    </form>

                                    <!-- Exam Records Table -->
                                    <div class="table-responsive">
                                        <table class="table" id="table-id">
                                            <thead>
                                                <tr>
                                                    <th>Attempt ID</th>
                                                    <th>Exam ID</th>
                                                    <th>Exam Name</th>
                                                    <th>Date Taken</th>
                                                    <th>Score</th>
                                                    <th>Analysis</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (pg_num_rows($result) > 0): ?>
                                                    <?php while ($row = pg_fetch_assoc($result)): ?>
                                                        <tr>
                                                            <td><?= $row['attempt_id'] ?></td>
                                                            <td><?= $row['exam_id'] ?></td>
                                                            <td><?= $row['exam_name'] ?></td>
                                                            <td><?= (new DateTime($row['exam_date']))->format('d/m/Y h:i A') ?></td>
                                                            <td><?= $row['score'] ?></td>
                                                            <td>
                                                                <a href="exam_analysis.php?user_exam_id=<?= $row['attempt_id'] ?>" class="btn btn-outline-primary">View Analysis</a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </main>
        <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

        <!-- Vendor JS Files -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

        <!-- Template Main JS File -->
        <script src="../assets_new/js/main.js"></script>
        <script>
            $(document).ready(function() {
                // Check if resultArr is empty
                <?php if (!empty($result)) : ?>
                    // Initialize DataTables only if resultArr is not empty
                    $('#table-id').DataTable({
                        // paging: false,
                        "order": [] // Disable initial sorting
                        // other options...
                    });
                <?php endif; ?>
            });
        </script>
    </body>

</html>