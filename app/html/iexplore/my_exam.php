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
    <title>My Exam</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #4f46e5;
            --accent-color: #6366f1;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .exam-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        }

        .exam-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .category-container {
            max-width: 180px;
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .category-badge {
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 0.75rem;
            white-space: nowrap;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: default;
        }

        .category-more-badge {
            background: rgba(100, 116, 139, 0.1);
            color: #64748b;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .category-more-badge:hover {
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
        }

        /* Tooltip customization */
        .popover {
            max-width: 300px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .popover-body {
            padding: 12px;
            color: #334155;
        }

        .filter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
        }

        .exam-stats {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .custom-checkbox .custom-control-input:checked~.custom-control-label::before {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .prebanner {
            display: none;
        }
    </style>
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
    <?php include 'header.php'; ?>
    <main class="container py-5">
        <div class="row g-4">
            <!-- Enhanced Filter Section -->

            <!-- Modern Exam Grid -->
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
    </main>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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