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

// Permission check
if (!$is_admin && !$is_centreIncharge) {
    echo "<script>
        alert('Access Denied. You do not have permission to access this page.');
        window.location.href = 'index.php'; // redirect to homepage
    </script>";
    exit; // Stop further execution
}

if (@$_POST['form-type'] == "exception_filter") {
    $class = $_POST['class'] ?? [];
    $category = $_POST['category'] ?? [];
    $student_ids = $_POST['student_ids'] ?? [];
    $excluded_ids = $_POST['excluded_ids'] ?? [];

    $query = "SELECT student_id, studentname, category, class FROM rssimyprofile_student WHERE filterstatus='Active'";
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
        $student_ids_list = implode("','", array_map(fn($id) => pg_escape_string($con, $id), $student_ids));
        $conditions[] = "student_id IN ('$student_ids_list')";
    }

    if (!empty($excluded_ids)) {
        $excluded_ids_list = implode("','", array_map(fn($id) => pg_escape_string($con, $id), $excluded_ids));
        $conditions[] = "student_id NOT IN ('$excluded_ids_list')";
    }

    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    } else {
        $resultArr = null;
    }

    $result = pg_query($con, $query);

    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    if (!empty($conditions)) {
        $resultArr = pg_fetch_all($result);
        $_SESSION['filtered_results'] = $resultArr;
    }
}

if (isset($_POST['form-type']) && $_POST['form-type'] == "exception") {
    $successMessages = [];

    if (isset($_SESSION['filtered_results'])) {
        $resultArr = $_SESSION['filtered_results'];
    } else {
        echo "Filtered results not available. Please apply filters first.\n";
        exit;
    }

    $reason = $_POST['reason'];
    $created_by = $associatenumber;

    // Check if multiple days selected
    if (!empty($_POST['multiple_days'])) {
        $start_date = $_POST['start_date'];
        $end_date   = $_POST['end_date'];

        // Generate date range
        $period = new DatePeriod(
            new DateTime($start_date),
            new DateInterval('P1D'),
            (new DateTime($end_date))->modify('+1 day') // inclusive
        );

        foreach ($period as $date) {
            $exception_date = $date->format("Y-m-d");

            // Insert exception record
            $exception_sql = "INSERT INTO student_class_days_exceptions (exception_date, reason, created_by) 
                              VALUES ($1, $2, $3) RETURNING exception_id";
            $params = array($exception_date, $reason, $created_by);
            $exception_result = pg_query_params($con, $exception_sql, $params);

            if (!$exception_result) {
                echo "Error creating exception record.\n";
                exit;
            }

            $exception_row = pg_fetch_assoc($exception_result);
            $exception_id = $exception_row['exception_id'];

            // Insert student mappings
            foreach ($resultArr as $row) {
                $student_id = $row['student_id'];
                $mapping_sql = "INSERT INTO student_exception_mapping (exception_id, student_id) VALUES ($1, $2)";
                $mapping_result = pg_query_params($con, $mapping_sql, array($exception_id, $student_id));

                if ($mapping_result) {
                    $successMessages[] = "Exception applied on $exception_date for student: " . $row['studentname'];
                }
            }
        }
    } else {
        // Single date
        $exception_date = $_POST['exception_date'];

        $exception_sql = "INSERT INTO student_class_days_exceptions (exception_date, reason, created_by) 
                          VALUES ($1, $2, $3) RETURNING exception_id";
        $params = array($exception_date, $reason, $created_by);
        $exception_result = pg_query_params($con, $exception_sql, $params);

        if (!$exception_result) {
            echo "Error creating exception record.\n";
            exit;
        }

        $exception_row = pg_fetch_assoc($exception_result);
        $exception_id = $exception_row['exception_id'];

        foreach ($resultArr as $row) {
            $student_id = $row['student_id'];
            $mapping_sql = "INSERT INTO student_exception_mapping (exception_id, student_id) VALUES ($1, $2)";
            $mapping_result = pg_query_params($con, $mapping_sql, array($exception_id, $student_id));

            if ($mapping_result) {
                $successMessages[] = "Exception applied on $exception_date for student: " . $row['studentname'];
            }
        }
    }

    echo "<script>
            alert('" . implode("\\n", array_unique($successMessages)) . "');
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
          </script>";
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
    <title>Class Days Exception</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
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
    <style>
        .asterisk {
            color: red;
            margin-left: 5px;
        }

        .back-link {
            color: #0d6efd;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Class Days Exception</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Schedule Hub</li>
                    <li class="breadcrumb-item active">Create Exceptions</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <!-- Reports -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="container mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <a href="exception_view.php" class="back-link">
                                        <i class="bi bi-arrow-left"></i> Back to all exceptions
                                    </a>
                                </div>

                                <h4>Select Students</h4>
                                <div class="mb-3 py-2">
                                    Filter students for applying class days exception using any combination of the filters below.
                                </div>
                                <form id="filterForm" method="post" action="" class="row g-2 align-items-end mb-4">
                                    <input type="hidden" name="form-type" value="exception_filter">

                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                        <label for="classes" class="form-label small mb-1">Class</label>
                                        <select class="form-select" id="classes" name="class[]" multiple></select>
                                    </div>

                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                        <label for="categories" class="form-label small mb-1">Category</label>
                                        <select class="form-select" id="categories" name="category[]" multiple></select>
                                    </div>

                                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                                        <label for="student_ids" class="form-label small mb-1">Include Student IDs</label>
                                        <select class="form-select" id="student_ids" name="student_ids[]" multiple></select>
                                    </div>

                                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                                        <label for="excluded_ids" class="form-label small mb-1">Exclude Student IDs</label>
                                        <select class="form-select" id="excluded_ids" name="excluded_ids[]" multiple></select>
                                    </div>

                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-funnel-fill me-1"></i> Filter
                                        </button>
                                    </div>
                                </form>

                                <?php if (isset($resultArr)) : ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="mb-0">Applying Exception for</h4>
                                        <span>Total Students: <?= count($resultArr) ?></span>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th scope="col" style="width:15%">Student ID</th>
                                                    <th scope="col" style="width:35%">Student Name</th>
                                                    <th scope="col" style="width:25%">Category</th>
                                                    <th scope="col" style="width:25%">Class</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($resultArr)) : ?>
                                                    <?php foreach ($resultArr as $student) : ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                                                            <td><?= htmlspecialchars($student['studentname']) ?></td>
                                                            <td><?= htmlspecialchars($student['category']) ?></td>
                                                            <td><?= htmlspecialchars($student['class']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else : ?>
                                                    <tr>
                                                        <td class="text-center py-3" colspan="4">No active students found matching your criteria.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($resultArr) && !empty($resultArr)) : ?>
                                    <h4 class="mb-4">Exception Parameters</h4>
                                    <form action="" name="exception" id="exception" method="post">
                                        <input type="hidden" name="form-type" value="exception">
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
                                                <textarea class="form-control" id="reason" name="reason" placeholder="Enter reason for exception" required></textarea>
                                            </div>
                                        </div>

                                        <div class="text-end mt-3 mb-3">
                                            <button type="submit" class="btn btn-primary">Apply Exception</button>
                                        </div>
                                    </form>

                                    <script>
                                        document.getElementById('multiple_days').addEventListener('change', function() {
                                            if (this.checked) {
                                                document.querySelector('.single-date').style.display = 'none';
                                                document.querySelector('.date-range').style.display = 'flex';
                                                document.getElementById('exception_date').required = false;
                                                document.getElementById('start_date').required = true;
                                                document.getElementById('end_date').required = true;
                                            } else {
                                                document.querySelector('.single-date').style.display = 'flex';
                                                document.querySelector('.date-range').style.display = 'none';
                                                document.getElementById('exception_date').required = true;
                                                document.getElementById('start_date').required = false;
                                                document.getElementById('end_date').required = false;
                                            }
                                        });
                                    </script>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div><!-- End Reports -->
                </div>
            </div>
        </section>
    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for multiple selects
            $('select[multiple]').select2();

            // Set default date to today
            $('#exception_date').val(new Date().toISOString().substr(0, 10));
        });
    </script>
    <script>
        $(document).ready(function() {
            // Include Student IDs
            $('#student_ids').select2({
                ajax: {
                    url: 'fetch_students.php?isActive=true',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: data.results
                    }),
                    cache: true
                },
                placeholder: 'Search by name or ID',
                width: '100%',
                minimumInputLength: 2,
                multiple: true
            });

            // Exclude Student IDs
            $('#excluded_ids').select2({
                ajax: {
                    url: 'fetch_students.php?isActive=true',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: data.results
                    }),
                    cache: true
                },
                placeholder: 'Search by name or ID',
                width: '100%',
                minimumInputLength: 2,
                multiple: true
            });

            // Categories
            $('#categories').select2({
                ajax: {
                    url: 'fetch_category.php',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: data.results
                    }),
                    cache: true
                },
                placeholder: 'Search by category',
                width: '100%',
                minimumInputLength: 2,
                multiple: true
            });

            // Classes
            $('#classes').select2({
                ajax: {
                    url: 'fetch_class.php',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => ({
                        results: data.results
                    }),
                    cache: true
                },
                placeholder: 'Search by class',
                width: '100%',
                minimumInputLength: 2,
                multiple: true
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            const selectedClasses = <?= json_encode($_POST['class'] ?? []) ?>;
            const selectedCategories = <?= json_encode($_POST['category'] ?? []) ?>;
            const selectedStudentIds = <?= json_encode($_POST['student_ids'] ?? []) ?>;
            const excludedStudentIds = <?= json_encode($_POST['excluded_ids'] ?? []) ?>;

            function prepopulateSelect2(selector, values, fetchUrl) {
                values.forEach(val => {
                    $.ajax({
                        type: 'GET',
                        url: fetchUrl,
                        data: {
                            q: val
                        },
                        dataType: 'json'
                    }).then(data => {
                        const match = data.results.find(option => option.id == val);
                        if (match) {
                            const newOption = new Option(match.text, match.id, true, true);
                            $(selector).append(newOption).trigger('change');
                        }
                    });
                });
            }

            prepopulateSelect2('#classes', selectedClasses, 'fetch_class.php');
            prepopulateSelect2('#categories', selectedCategories, 'fetch_category.php');
            prepopulateSelect2('#student_ids', selectedStudentIds, 'fetch_students.php?isActive=true');
            prepopulateSelect2('#excluded_ids', excludedStudentIds, 'fetch_students.php?isActive=true');
        });
    </script>

</body>

</html>