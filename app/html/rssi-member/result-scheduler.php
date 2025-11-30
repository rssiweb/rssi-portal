<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$success_message = "";
$error_message = "";

// Set default date range (last 1 month)
$end_date = date('Y-m-d', strtotime('+1 month'));
$start_date = date('Y-m-d', strtotime('-1 month'));

// Handle date range filter
if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
}
if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
}

// Validate date range
if (strtotime($end_date) < strtotime($start_date)) {
    echo "<script>alert('Error: End date cannot be before start date!');</script>";
    // Reset to default dates
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-1 month'));
}

if ($_POST && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_type = $_POST['exam_type'];
    $academic_year = $_POST['academic_year'];
    $publish_date = $_POST['publish_date'];

    // Check if entry already exists
    $check_query = "SELECT * FROM result_publication_dates 
                    WHERE exam_type = $1 AND academic_year = $2";
    $check_result = pg_query_params($con, $check_query, [$exam_type, $academic_year]);

    if ($check_result === false) {
        echo "<script>alert('Database error while checking existing records!');</script>";
    } else {
        if (pg_num_rows($check_result) > 0) {
            // Entry already exists - show exact details
            $existing_record = pg_fetch_assoc($check_result);
            $existing_date = date('M j, Y g:i A', strtotime($existing_record['publish_date']));

            echo "<script>alert('Result already published!\\\\n\\\\nThe $exam_type exam for academic year $academic_year has already been published/scheduled to be published on $existing_date.');</script>";
        } else {
            // New entry - proceed with insert
            $query = "INSERT INTO result_publication_dates (exam_type, academic_year, publish_date, created_by) 
                      VALUES ($1, $2, $3, $4)";

            $result = pg_query_params($con, $query, [$exam_type, $academic_year, $publish_date, $associatenumber]);

            if ($result) {
                echo "<script>alert('Publication date set successfully for $exam_type - $academic_year!');</script>";
                // Prevent form resubmission on page refresh
                echo "<script>window.location.replace(window.location.pathname + '?start_date=$start_date&end_date=$end_date');</script>";
                exit;
            } else {
                echo "<script>alert('Error setting publication date! Please try again.');</script>";
            }
        }
    }
}
?>
<?php
// Generate current and last 3 academic years
$years = [];
$currentYear = date("Y");

for ($i = 0; $i < 4; $i++) {
    $start = $currentYear - $i;
    $end = $start + 1;
    $years[] = "$start-$end";
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

    <title>Result Scheduler</title>

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
            //facebookPixel: '',
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
            <h1>Result Scheduler</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Academic</a></li>
                    <li class="breadcrumb-item"><a href="exam-management.php">Exam Management</a></li>
                    <li class="breadcrumb-item active">Result Scheduler</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Set Publication Date Form -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <p class="text-muted mt-3">Schedule when results should become visible to students.</p>

                            <form method="POST" class="row g-3" id="publicationForm">
                                <div class="col-md-4">
                                    <label for="exam_type" class="form-label">Exam Type</label>
                                    <select class="form-select" name="exam_type" required>
                                        <option value="">Select Exam Type</option>
                                        <option value="First Term">First Term</option>
                                        <option value="Half Yearly">Half Yearly</option>
                                        <option value="Annual">Annual</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="academic_year" class="form-label">Academic Year</label>
                                    <select class="form-select" name="academic_year" required>
                                        <option value="">Select Academic Year</option>
                                        <?php foreach ($years as $year): ?>
                                            <option value="<?= $year ?>"><?= $year ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="publish_date" class="form-label">Publication Date & Time</label>
                                    <input type="datetime-local" class="form-control" name="publish_date" required>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-calendar-check"></i> Schedule Publication
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Filter Published Results</h5>
                            <form method="GET" class="row g-3" id="dateFilterForm">
                                <div class="col-md-4">
                                    <label for="start_date" class="form-label">From Date</label>
                                    <input type="date" class="form-control" name="start_date" id="start_date" value="<?= $start_date ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="end_date" class="form-label">To Date</label>
                                    <input type="date" class="form-control" name="end_date" id="end_date" value="<?= $end_date ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-filter"></i> Apply Filter
                                        </button>
                                        <a href="?" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-clockwise"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Current Publication Dates Table -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                Published Results
                            </h5>

                            <div class="table-responsive">
                                <table class="table" id="resultsTable">
                                    <thead>
                                        <tr>
                                            <th>Id</th>
                                            <th>Exam Type</th>
                                            <th>Academic Year</th>
                                            <th>Publication Date</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $current_date = date('Y-m-d H:i:s');
                                        $list_query = "
                                            SELECT rpd.*, ram.fullname 
                                            FROM result_publication_dates rpd 
                                            LEFT JOIN rssimyaccount_members ram 
                                                ON rpd.created_by = ram.associatenumber 
                                            WHERE rpd.publish_date BETWEEN $1 AND $2 
                                            ORDER BY rpd.publish_date DESC
                                        ";

                                        $list_result = pg_query_params(
                                            $con,
                                            $list_query,
                                            [$start_date . ' 00:00:00', $end_date . ' 23:59:59']
                                        );

                                        if ($list_result && pg_num_rows($list_result) > 0):

                                            while ($row = pg_fetch_assoc($list_result)):

                                                $publish_timestamp = strtotime($row['publish_date']);
                                                $current_timestamp = time();

                                                // Determine status
                                                $status = ($current_timestamp >= $publish_timestamp)
                                                    ? '<span class="badge bg-success">Published</span>'
                                                    : '<span class="badge bg-warning">Scheduled</span>';

                                                // Creator information
                                                $creator_name  = $row['fullname'] ?: 'Unknown User';
                                                $created_date  = date('d/m/Y h:i A', strtotime($row['created_at']));
                                        ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                                    <td><?= htmlspecialchars($row['exam_type']) ?></td>
                                                    <td><?= htmlspecialchars($row['academic_year']) ?></td>
                                                    <td><?= date('d/m/Y h:i A', $publish_timestamp) ?></td>
                                                    <td><?= $status ?></td>
                                                    <td>
                                                        <?= htmlspecialchars($creator_name) ?>
                                                        <div class="text-muted small"><?= $created_date ?></div>
                                                    </td>
                                                </tr>
                                            <?php
                                            endwhile;

                                        else:
                                            ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="bi bi-inbox display-4 text-muted"></i><br>
                                                    No publication dates found for the selected date range.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
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
            // Initialize DataTable ONLY if data rows exist
            if ($('#resultsTable tbody tr').length > 0 &&
                !$('#resultsTable tbody tr td').hasClass('text-center')) {

                // Initialize DataTables only if resultArr is not empty
                $('#resultsTable').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            }

            // Date validation for filter form
            $('#dateFilterForm').on('submit', function(e) {
                const startDate = new Date($('#start_date').val());
                const endDate = new Date($('#end_date').val());

                if (endDate < startDate) {
                    alert('Error: End date cannot be before start date!');
                    e.preventDefault();
                    return false;
                }
                return true;
            });

            // Prevent form resubmission on page refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }

            // Set minimum datetime for publication date to current time
            // const now = new Date();
            // now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            // document.querySelector('input[name="publish_date"]').min = now.toISOString().slice(0, 16);
        });
    </script>

</body>

</html>