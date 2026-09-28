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

if (!$is_admin && !$is_centreIncharge) {
    echo "<script>
        alert('Access Denied. You do not have permission to access this page.');
        window.location.href = 'index.php';
    </script>";
    exit;
}

/* =========================================================
   HANDLER 1 — Filter students (student scope only)
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
            echo "An error occurred.\n";
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
   HANDLER 2 — Create exception
   ========================================================= */
if (isset($_POST['form-type']) && $_POST['form-type'] == "exception") {
    $scope_type = $_POST['scope_type'] ?? 'student';
    $reason     = $_POST['reason'] ?? '';
    $created_by = $associatenumber;

    $scope_class    = null;
    $scope_category = null;

    if ($scope_type === 'class') {
        $scope_class = $_POST['scope_class'] ?? null;
        if (empty($scope_class)) {
            echo "Please select a class.";
            exit;
        }
    } elseif ($scope_type === 'class_category') {
        $scope_class    = $_POST['scope_class']    ?? null;
        $scope_category = $_POST['scope_category'] ?? null;
        if (empty($scope_class) || empty($scope_category)) {
            echo "Please select both class and category.";
            exit;
        }
    } elseif ($scope_type === 'category') {
        $scope_category = $_POST['scope_category'] ?? null;
        if (empty($scope_category)) {
            echo "Please select a category.";
            exit;
        }
    } else { // student
        if (empty($_SESSION['filtered_results'])) {
            echo "Filtered results not available. Please apply filters first.\n";
            exit;
        }
        $resultArr = $_SESSION['filtered_results'];
    }

    // Build date list
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

    $successMessages = [];
    pg_query($con, "BEGIN");

    foreach ($dates as $exception_date) {
        $sql = "INSERT INTO student_class_days_exceptions
                    (exception_date, reason, created_by, scope_type, scope_class, scope_category)
                VALUES ($1, $2, $3, $4, $5, $6)
                RETURNING exception_id";
        $res = pg_query_params($con, $sql, [
            $exception_date,
            $reason,
            $created_by,
            $scope_type,
            $scope_class,
            $scope_category
        ]);
        if (!$res) {
            pg_query($con, "ROLLBACK");
            echo "Error creating exception record: " . pg_last_error($con);
            exit;
        }
        $exception_id = pg_fetch_result($res, 0, 'exception_id');

        if ($scope_type === 'student') {
            foreach ($resultArr as $row) {
                $mapping_result = pg_query_params(
                    $con,
                    "INSERT INTO student_exception_mapping (exception_id, student_id) VALUES ($1, $2)",
                    [$exception_id, $row['student_id']]
                );
                if ($mapping_result) {
                    $successMessages[] = "Exception applied on $exception_date for student: " . $row['studentname'];
                }
            }
        } else {
            $label = $scope_type === 'class'
                ? "class $scope_class"
                : ($scope_type === 'class_category'
                    ? "class $scope_class / category $scope_category"
                    : "category $scope_category");
            $successMessages[] = "Exception applied on $exception_date for $label";
        }
    }

    pg_query($con, "COMMIT");

    echo "<script>
            alert('" . implode("\\n", array_unique($successMessages)) . "');
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
          </script>";
}

/* =========================================================
   Fetch helper dropdowns
   ========================================================= */
$class_options = [];
$cls_res = pg_query($con, "SELECT DISTINCT class FROM rssimyprofile_student
                           WHERE class IS NOT NULL AND filterstatus='Active'
                           ORDER BY class");
if ($cls_res) {
    $class_options = pg_fetch_all_columns($cls_res, 0);
}

$category_options = [];
$cat_res = pg_query($con, "SELECT DISTINCT category FROM rssimyprofile_student
                           WHERE category IS NOT NULL AND filterstatus='Active'
                           ORDER BY category");
if ($cat_res) {
    $category_options = pg_fetch_all_columns($cat_res, 0);
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
    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
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
                            <div class="container mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <a href="exception_view.php" class="back-link">
                                        <i class="bi bi-arrow-left"></i> Back to all exceptions
                                    </a>
                                </div>

                                <h4>Create Class Days Exception</h4>

                                <!-- SCOPE PICKER -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Apply exception to:</label>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="scope-card d-block active" data-scope="student">
                                                <input type="radio" name="scope_type_ui" value="student" checked>
                                                <strong><i class="bi bi-person-lines-fill me-1"></i> Specific Students</strong>
                                                <div class="small text-muted mt-1">Pick individual students using filters</div>
                                            </label>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="scope-card d-block" data-scope="class">
                                                <input type="radio" name="scope_type_ui" value="class">
                                                <strong><i class="bi bi-people-fill me-1"></i> Entire Class</strong>
                                                <div class="small text-muted mt-1">Applies to every student in a class</div>
                                            </label>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="scope-card d-block" data-scope="class_category">
                                                <input type="radio" name="scope_type_ui" value="class_category">
                                                <strong><i class="bi bi-diagram-3-fill me-1"></i> Class + Category</strong>
                                                <div class="small text-muted mt-1">Applies to a class within a category</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- STUDENT PANEL -->
                                <div id="scope-student-panel">
                                    <div class="mb-3 py-2">
                                        Filter students using any combination of the filters below.
                                    </div>
                                    <form id="filterForm" method="post" action="" class="row g-2 align-items-end mb-4">
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

                                    <?php if (isset($resultArr) && !empty($resultArr)) : ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">Students in scope</h5>
                                            <span>Total Students: <?= count($resultArr) ?></span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width:15%">Student ID</th>
                                                        <th style="width:35%">Student Name</th>
                                                        <th style="width:25%">Category</th>
                                                        <th style="width:25%">Class</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($resultArr as $student) : ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                                                            <td><?= htmlspecialchars($student['studentname']) ?></td>
                                                            <td><?= htmlspecialchars($student['category']) ?></td>
                                                            <td><?= htmlspecialchars($student['class']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- CLASS PANEL -->
                                <div id="scope-class-panel" style="display:none;">
                                    <div class="row mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label">Class <span class="asterisk">*</span></label>
                                            <select class="form-select" id="scope_class_simple">
                                                <option value="">Select class</option>
                                                <?php foreach ($class_options as $c): ?>
                                                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="alert alert-info py-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Applies to every active student who was in the selected class <strong>on the exception date</strong>.
                                    </div>
                                </div>

                                <!-- CLASS + CATEGORY PANEL -->
                                <div id="scope-class-category-panel" style="display:none;">
                                    <div class="row mb-4">
                                        <div class="col-md-4">
                                            <label class="form-label">Class <span class="asterisk">*</span></label>
                                            <select class="form-select" id="scope_class_cc">
                                                <option value="">Select class</option>
                                                <?php foreach ($class_options as $c): ?>
                                                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Category <span class="asterisk">*</span></label>
                                            <select class="form-select" id="scope_category_cc">
                                                <option value="">Select category</option>
                                                <?php foreach ($category_options as $c): ?>
                                                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="alert alert-info py-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Applies to students matching the class <strong>and</strong> category on the exception date.
                                    </div>
                                </div>

                                <!-- EXCEPTION PARAMETERS -->
                                <h5 class="mb-3 mt-4">Exception Parameters</h5>
                                <form action="" name="exception" id="exception" method="post">
                                    <input type="hidden" name="form-type" value="exception">
                                    <input type="hidden" name="scope_type" id="scope_type_hidden" value="student">
                                    <input type="hidden" name="scope_class" id="scope_class_hidden">
                                    <input type="hidden" name="scope_category" id="scope_category_hidden">

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
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <i class="bi bi-check-circle me-1"></i> Apply Exception
                                        </button>
                                    </div>
                                </form>
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
            $('select[multiple]').select2();
            $('#exception_date').val(new Date().toISOString().substr(0, 10));

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
                        })
                        .then(data => {
                            const match = data.results.find(o => o.id == val);
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

    <script>
        $(document).ready(function() {
            $('.scope-card').on('click', function() {
                $('.scope-card').removeClass('active');
                $(this).addClass('active');
                $(this).find('input[type=radio]').prop('checked', true);
                const scope = $(this).data('scope');
                $('#scope_type_hidden').val(scope);

                $('#scope-student-panel').toggle(scope === 'student');
                $('#scope-class-panel').toggle(scope === 'class');
                $('#scope-class-category-panel').toggle(scope === 'class_category');
            });

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

            $('#exception').on('submit', function(e) {
                const scope = $('#scope_type_hidden').val();
                if (scope === 'class') {
                    const cls = $('#scope_class_simple').val();
                    if (!cls) {
                        alert('Please select a class.');
                        e.preventDefault();
                        return;
                    }
                    $('#scope_class_hidden').val(cls);
                } else if (scope === 'class_category') {
                    const cls = $('#scope_class_cc').val();
                    const cat = $('#scope_category_cc').val();
                    if (!cls || !cat) {
                        alert('Please select both class and category.');
                        e.preventDefault();
                        return;
                    }
                    $('#scope_class_hidden').val(cls);
                    $('#scope_category_hidden').val(cat);
                } else {
                    $('#scope_class_hidden').val('');
                    $('#scope_category_hidden').val('');
                }
            });
        });
    </script>
</body>

</html>