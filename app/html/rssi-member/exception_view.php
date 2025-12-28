<?php
require_once __DIR__ . "/../../bootstrap.php";
require_once __DIR__ . "/../../util/login_util.php";

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

$is_admin = ($role == 'Admin');
$is_centreIncharge = ($position == 'Centre Incharge' || $position == 'Senior Centre Incharge');

// Handle exception deletion
if (isset($_GET['delete_exception'])) {
    $exception_id = $_GET['delete_exception'];

    // Begin transaction
    pg_query($con, "BEGIN");

    try {
        // Delete mappings first
        $delete_mappings = pg_query_params(
            $con,
            "DELETE FROM student_exception_mapping WHERE exception_id = $1",
            array($exception_id)
        );

        if (!$delete_mappings) {
            throw new Exception("Failed to delete student mappings");
        }

        // Then delete the exception
        $delete_exception = pg_query_params(
            $con,
            "DELETE FROM student_class_days_exceptions WHERE exception_id = $1",
            array($exception_id)
        );

        if (!$delete_exception || pg_affected_rows($delete_exception) == 0) {
            throw new Exception("Failed to delete exception or exception not found");
        }

        // Commit transaction
        pg_query($con, "COMMIT");

        $_SESSION['success_message'] = "Exception deleted successfully";
    } catch (Exception $e) {
        // Rollback on error
        pg_query($con, "ROLLBACK");
        $_SESSION['error_message'] = "Error deleting exception: " . $e->getMessage();
    }

    header("Location: exception_view.php");
    exit;
}

// Check if viewing a specific exception
$current_exception_id = isset($_GET['exception_id']) ? $_GET['exception_id'] : null;

if ($current_exception_id) {
    // Get exception header info
    $exception_header_query = pg_query_params(
        $con,
        "SELECT * FROM student_class_days_exceptions WHERE exception_id = $1",
        array($current_exception_id)
    );
    $exception_header = pg_fetch_assoc($exception_header_query);

    if (!$exception_header) {
        $_SESSION['error_message'] = "Exception not found";
        header("Location: exception_view.php");
        exit;
    }

    // Get exception details (affected students)
    $exception_details_query = pg_query_params(
        $con,
        "SELECT s.student_id, s.studentname, s.class, s.category 
         FROM student_exception_mapping m
         JOIN rssimyprofile_student s ON m.student_id = s.student_id
         WHERE m.exception_id = $1
         ORDER BY s.class, s.studentname",
        array($current_exception_id)
    );
    $exception_details = pg_fetch_all($exception_details_query) ?: [];
}

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$search = isset($_GET['search']) ? pg_escape_string($con, $_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build base query
$base_query = "
    FROM 
        student_class_days_exceptions e
    LEFT JOIN 
        student_exception_mapping m ON e.exception_id = m.exception_id
    LEFT JOIN
        rssimyprofile_student s ON m.student_id = s.student_id";

// Build where conditions
$conditions = [];
$params = [];
$param_count = 0;

if (!empty($search)) {
    $conditions[] = "(e.reason ILIKE $" . ++$param_count . " OR e.created_by ILIKE $" . $param_count . ")";
    $params[] = "%$search%";
}

if (!empty($date_from)) {
    $conditions[] = "e.exception_date >= $" . ++$param_count;
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $conditions[] = "e.exception_date <= $" . ++$param_count;
    $params[] = $date_to;
}

// Complete queries
$where_clause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$count_query = "SELECT COUNT(DISTINCT e.exception_id) as total $base_query $where_clause";
$count_result = pg_query_params($con, $count_query, $params);
$total_exceptions = pg_fetch_result($count_result, 0, 'total');
$total_pages = ceil($total_exceptions / $per_page);

$exceptions_query = "
    SELECT 
        e.exception_id,
        e.exception_date,
        e.reason,
        e.created_by,
        e.created_at,
        COUNT(m.student_id) AS student_count,
        STRING_AGG(DISTINCT s.class, ', ') AS classes_affected,
        STRING_AGG(DISTINCT s.category, ', ') AS categories_affected
    $base_query
    $where_clause
    GROUP BY 
        e.exception_id
    ORDER BY 
        e.exception_date DESC
    LIMIT $per_page OFFSET $offset";

$exceptions_result = pg_query_params($con, $exceptions_query, $params);
$exceptions = pg_fetch_all($exceptions_result) ?: [];
?>

<!DOCTYPE html>
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
    <title>View Class Days Exceptions</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">

    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <style>
        .exception-card {
            transition: all 0.3s ease;
            border-left: 4px solid #0d6efd;
        }

        .exception-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .clickable-row {
            cursor: pointer;
        }

        .back-link {
            color: #0d6efd;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .badge-rounded {
            border-radius: 1rem;
        }

        .table-responsive {
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Class Days Exceptions</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Schedule Hub</li>
                    <li class="breadcrumb-item active">View Exceptions</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['success_message']); ?>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['error_message']); ?>
                            <?php endif; ?>

                            <div class="container mt-3">
                                <?php if ($current_exception_id): ?>
                                    <!-- Detail View -->
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <a href="exception_view.php" class="back-link">
                                                <i class="bi bi-arrow-left"></i> Back to all exceptions
                                            </a>
                                            <h4 class="mt-2">Exception Details</h4>
                                        </div>
                                        <div>
                                            <span class="badge bg-primary badge-rounded">
                                                <?= count($exception_details) ?> student(s)
                                            </span>
                                        </div>
                                    </div>

                                    <div class="card mb-4 exception-card">
                                        <div class="card-body">
                                            <div class="row mt-4">
                                                <div class="col-md-3">
                                                    <h6>Exception Date</h6>
                                                    <p><?= date('F j, Y', strtotime($exception_header['exception_date'])) ?></p>
                                                </div>
                                                <div class="col-md-5">
                                                    <h6>Reason</h6>
                                                    <p><?= htmlspecialchars($exception_header['reason']) ?></p>
                                                </div>
                                                <div class="col-md-2">
                                                    <h6>Created By</h6>
                                                    <p><?= htmlspecialchars($exception_header['created_by']) ?></p>
                                                </div>
                                                <div class="col-md-2">
                                                    <h6>Created On</h6>
                                                    <p><?= date('M j, Y g:i A', strtotime($exception_header['created_at'])) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($role === 'Admin'): ?>
                                            <div class="card-footer text-end">
                                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $current_exception_id ?>)">
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
                                                    <th style="width:25%">Class</th>
                                                    <th style="width:25%">Category</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($exception_details)): ?>
                                                    <?php foreach ($exception_details as $student): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                                                            <td><?= htmlspecialchars($student['studentname']) ?></td>
                                                            <td><?= htmlspecialchars($student['class']) ?></td>
                                                            <td><?= htmlspecialchars($student['category']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center py-3">No students found for this exception.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <!-- Summary View -->
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
                                                <h4>All Class Days Exceptions</h4>
                                                <?php if ($is_admin || $is_centreIncharge): ?>
                                                    <div>
                                                        <a href="class_days_exception.php" class="btn btn-primary">
                                                            <i class="bi bi-plus-circle"></i> Create New
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Search and Filter Section -->
                                            <div class="row mb-4">
                                                <div class="col-md-4">
                                                    <form method="get" class="search-form">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" name="search" placeholder="Search..."
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
                                                                    value="<?= htmlspecialchars($date_from) ?>" placeholder="From date">
                                                            </div>
                                                            <div class="col-md-5">
                                                                <input type="date" class="form-control" name="date_to"
                                                                    value="<?= htmlspecialchars($date_to) ?>" placeholder="To date">
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                                <div class="col-md-3 text-end">
                                                    <div class="btn-group">
                                                        <a href="exception_view.php" class="btn btn-outline-secondary">Reset</a>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if (empty($exceptions)): ?>
                                                <div class="alert alert-info">
                                                    No exceptions found matching your criteria.
                                                </div>
                                            <?php else: ?>
                                                <!-- Tabular View -->
                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th>Reason</th>
                                                                <th>Students</th>
                                                                <th>Classes</th>
                                                                <th>Created By</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($exceptions as $exception): ?>
                                                                <tr class="clickable-row"
                                                                    onclick="window.location='exception_view.php?exception_id=<?= $exception['exception_id'] ?>'">
                                                                    <td>
                                                                        <?= date('M j, Y', strtotime($exception['exception_date'])) ?>
                                                                        <br>
                                                                        <small class="text-muted"><?= date('D', strtotime($exception['exception_date'])) ?></small>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($exception['reason']) ?></td>
                                                                    <td>
                                                                        <?= $exception['student_count'] ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($exception['classes_affected']): ?>
                                                                            <?= implode(', ', array_unique(explode(', ', $exception['classes_affected']))) ?>
                                                                        <?php else: ?>
                                                                            <span class="text-muted">None</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <?= htmlspecialchars($exception['created_by']) ?>
                                                                        <br>
                                                                        <small class="text-muted">
                                                                            <?= date('M j', strtotime($exception['created_at'])) ?>
                                                                        </small>
                                                                    </td>
                                                                    <td>
                                                                        <a href="exception_view.php?exception_id=<?= $exception['exception_id'] ?>"
                                                                            class="btn btn-sm btn-outline-primary">
                                                                            View
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <!-- Pagination -->
                                                <nav aria-label="Page navigation">
                                                    <ul class="pagination justify-content-center">
                                                        <?php if ($page > 1): ?>
                                                            <li class="page-item">
                                                                <a class="page-link"
                                                                    href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">
                                                                    First
                                                                </a>
                                                            </li>
                                                            <li class="page-item">
                                                                <a class="page-link"
                                                                    href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                                                    Previous
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>

                                                        <?php
                                                        // Show page numbers
                                                        $start_page = max(1, $page - 2);
                                                        $end_page = min($total_pages, $page + 2);

                                                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                                <a class="page-link"
                                                                    href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                                    <?= $i ?>
                                                                </a>
                                                            </li>
                                                        <?php endfor; ?>

                                                        <?php if ($page < $total_pages): ?>
                                                            <li class="page-item">
                                                                <a class="page-link"
                                                                    href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                                                    Next
                                                                </a>
                                                            </li>
                                                            <li class="page-item">
                                                                <a class="page-link"
                                                                    href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">
                                                                    Last
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </nav>

                                                <div class="text-center text-muted">
                                                    Showing <?= ($offset + 1) ?> to <?= min($offset + $per_page, $total_exceptions) ?>
                                                    of <?= $total_exceptions ?> exceptions
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  <script src="../assets_new/js/text-refiner.js"></script>

    <script>
        $(document).ready(function() {
            // Make rows clickable
            // $('.clickable-row').click(function() {
            //     window.location = $(this).data('href');
            // }).css('cursor', 'pointer');

            // Date range validation
            $('.date-range-form').submit(function(e) {
                const from = $('[name="date_from"]').val();
                const to = $('[name="date_to"]').val();

                if (from && to && new Date(from) > new Date(to)) {
                    alert('End date must be after start date');
                    e.preventDefault();
                }
            });
        });

        function confirmDelete(exceptionId) {
            if (confirm("Are you sure you want to delete this exception? This will remove it for all students.")) {
                window.location.href = 'exception_view.php?delete_exception=' + exceptionId +
                    '&' + new URLSearchParams(window.location.search).toString();
            }
        }
    </script>
</body>

</html>