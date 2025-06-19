<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/email.php");
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}
validation();

if (@$_POST['form-type'] == "exam_filter") {
    $class = isset($_POST['class']) ? $_POST['class'] : [];
    $category = isset($_POST['category']) ? $_POST['category'] : [];
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : '';
    $excluded_ids = isset($_POST['excluded_ids']) ? $_POST['excluded_ids'] : '';

    $query = "SELECT student_id, studentname, category, class FROM rssimyprofile_student WHERE filterstatus='Active'";
    $conditions = [];

    if (!empty($class)) {
        $class_list = implode("','", $class);
        $conditions[] = "class IN ('$class_list')";
    }
    if (!empty($category)) {
        $category_list = implode("','", $category);
        $conditions[] = "category IN ('$category_list')";
    }
    if (!empty($student_ids)) {
        $student_ids_list = implode("','", array_map('trim', explode(',', $student_ids)));
        $conditions[] = "student_id IN ('$student_ids_list')";
    }
    if (!empty($excluded_ids)) {
        $excluded_ids_list = implode("','", array_map('trim', explode(',', $excluded_ids)));
        $conditions[] = "student_id NOT IN ('$excluded_ids_list')";
    }

    if (count($conditions) > 0) {
        $query .= " AND " . implode(" AND ", $conditions);
    } else {
        $resultArr = null;
    }

    $result = pg_query($con, $query);

    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    if (count($conditions) > 0) {
        $resultArr = pg_fetch_all($result);
        $_SESSION['filtered_results'] = $resultArr;
    }
}

if (@$_POST['form-type'] == "exam") {
    $successMessages = [];

    if (isset($_SESSION['filtered_results'])) {
        $resultArr = $_SESSION['filtered_results'];
    } else {
        echo "Filtered results not available. Please apply filters first.\n";
        exit;
    }

    // Process each subject
    $subjects = $_POST['subject'];
    $exam_type = $_POST['exam_type'];
    $academic_year = $_POST['academic_year'];
    $exam_modes = isset($_POST['exam_mode']) ? $_POST['exam_mode'] : [];

    foreach ($subjects as $index => $subject) {
        $teacher_id_viva = $_POST['teacher_id_viva'][$index] ?? null;
        $teacher_id_written = $_POST['teacher_id_written'][$index] ?? null;
        $full_marks_written = $_POST['full_marks_written'][$index] ?? null;
        $exam_date_written = $_POST['exam_date_written'][$index] ?? null;
        $full_marks_viva = $_POST['full_marks_viva'][$index] ?? null;
        $exam_date_viva = $_POST['exam_date_viva'][$index] ?? null;
        $exam_id = uniqid();

        $exam_mode_pg_array = '{' . implode(',', $exam_modes) . '}';

        $exam_sql = "INSERT INTO exams (exam_type, academic_year, teacher_id_viva, teacher_id_written, subject, exam_mode, full_marks_written, full_marks_viva, exam_id, exam_date_written, exam_date_viva)
                 VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)";

        $params = array(
            $exam_type,
            $academic_year,
            $teacher_id_viva,
            $teacher_id_written,
            $subject,
            $exam_mode_pg_array,
            ($full_marks_written !== null && $full_marks_written !== "") ? $full_marks_written : null,
            ($full_marks_viva !== null && $full_marks_viva !== "") ? $full_marks_viva : null,
            $exam_id,
            ($exam_date_written !== null && $exam_date_written !== "") ? $exam_date_written : null,
            ($exam_date_viva !== null && $exam_date_viva !== "") ? $exam_date_viva : null
        );

        $exam_result = pg_query_params($con, $exam_sql, $params);

        foreach ($resultArr as $row) {
            $student_id = $row['student_id'];
            $studentname = $row['studentname'];
            $category = $row['category'];
            $class = $row['class'];

            $insertQuery = "INSERT INTO exam_marks_data (exam_id, student_id, category, class) VALUES ('$exam_id', '$student_id', '$category', '$class')";
            $insertResult = pg_query($con, $insertQuery);
            $cmdtuples = pg_affected_rows($insertResult);

            if ($exam_result && $insertResult) {
                $successMessages[] = "Exam created for $subject - $studentname";
            } else {
                echo "Error inserting data into exam_marks_data.\n";
                exit;
            }
        }

        // Send emails to examiners
        $examiner_ids = array_filter([$teacher_id_viva, $teacher_id_written]);
        if (!empty($examiner_ids)) {
            $examiner_data_query = pg_query($con, "SELECT associatenumber, phone, email, fullname 
                                           FROM rssimyaccount_members 
                                           WHERE associatenumber IN ('" . implode("','", $examiner_ids) . "')");

            $examiners = [];
            while ($row = pg_fetch_assoc($examiner_data_query)) {
                $examiners[$row['associatenumber']] = $row;
            }

            // Send email to viva examiner if different from written examiner
            if (!empty($teacher_id_viva)) {
                $examiner_email = $examiners[$teacher_id_viva]['email'] ?? null;
                $examiner_name = $examiners[$teacher_id_viva]['fullname'] ?? null;

                if ($cmdtuples == 1 && !empty($examiner_email)) {
                    sendEmail("exam_create", [
                        "exam_id" => $exam_id,
                        "exam_type" => $exam_type,
                        "academic_year" => $academic_year,
                        "subject" => $subject,
                        "class" => $class,
                        "exam_mode" => in_array('Viva', $exam_modes) ? 'Viva' : '',
                        "full_marks_written" => $full_marks_written,
                        "full_marks_viva" => $full_marks_viva,
                        "examiner_name" => $examiner_name,
                    ], $examiner_email);
                }
            }

            // Send email to written examiner if different from viva examiner
            if (!empty($teacher_id_written) && $teacher_id_written != $teacher_id_viva) {
                $examiner_email_written = $examiners[$teacher_id_written]['email'] ?? null;
                $examiner_name_written = $examiners[$teacher_id_written]['fullname'] ?? null;

                if ($cmdtuples == 1 && !empty($examiner_email_written)) {
                    sendEmail("exam_create", [
                        "exam_id" => $exam_id,
                        "exam_type" => $exam_type,
                        "academic_year" => $academic_year,
                        "subject" => $subject,
                        "class" => $class,
                        "exam_mode" => in_array('Written', $exam_modes) ? 'Written' : '',
                        "full_marks_written" => $full_marks_written,
                        "full_marks_viva" => $full_marks_viva,
                        "examiner_name" => $examiner_name_written,
                    ], $examiner_email_written);
                }
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
    <title>Create Exam</title>
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

        .subject-table {
            margin-top: 20px;
        }

        .subject-table th {
            background-color: #f8f9fa;
        }

        /* .select2-container .select2-selection--multiple {
            min-height: 38px;
            padding: 5px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 5px;
        } */
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Create Exam</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Academic</li>
                    <li class="breadcrumb-item active">Create Exam</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <!-- Reports -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <div class="container mt-5">
                                <?php
                                // Define class and category options in arrays for easy management
                                $class_options = [
                                    'Nursery' => 'Nursery',
                                    'LKG' => 'LKG',
                                    'UKG' => 'UKG',
                                    '1' => '1',
                                    '2' => '2',
                                    '3' => '3',
                                    '4' => '4',
                                    '5' => '5',
                                    '6' => '6'
                                ];

                                $category_options = [
                                    'LG1' => 'LG1',
                                    'LG2-A' => 'LG2-A',
                                    'LG2-B' => 'LG2-B'
                                ];

                                // Function to generate selected attribute
                                function is_selected($value, $field)
                                {
                                    return (isset($_POST[$field]) && in_array($value, $_POST[$field])) ? 'selected' : '';
                                }
                                ?>

                                <h4>Select Students</h4>
                                <div class="mb-3 py-2">
                                    Filter students for exam creation using any combination of the filters below.
                                </div>
                                <form id="filterForm" method="post" action="" class="row g-2 align-items-end mb-4">
                                    <input type="hidden" name="form-type" value="exam_filter">

                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                        <label for="class" class="form-label small mb-1">Class</label>
                                        <select class="form-select" id="class" name="class[]" multiple data-placeholder="Select class(es)">
                                            <?php foreach ($class_options as $value => $label): ?>
                                                <option value="<?= $value ?>" <?= is_selected($value, 'class') ?>>
                                                    <?= $label ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                        <label for="category" class="form-label small mb-1">Category</label>
                                        <select class="form-select" id="category" name="category[]" multiple data-placeholder="Select category(ies)">
                                            <?php foreach ($category_options as $value => $label): ?>
                                                <option value="<?= $value ?>" <?= is_selected($value, 'category') ?>>
                                                    <?= $label ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                                        <label for="student_ids" class="form-label small mb-1">Include Student IDs</label>
                                        <input type="text" class="form-control" id="student_ids" name="student_ids"
                                            placeholder="e.g., RSSI001, RSSI002"
                                            value="<?= htmlspecialchars($_POST['student_ids'] ?? '') ?>">
                                    </div>

                                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                                        <label for="excluded_ids" class="form-label small mb-1">Exclude Student IDs</label>
                                        <input type="text" class="form-control" id="excluded_ids" name="excluded_ids"
                                            placeholder="e.g., RSSI003, RSSI004"
                                            value="<?= htmlspecialchars($_POST['excluded_ids'] ?? '') ?>">
                                    </div>

                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-funnel-fill me-1"></i> Filter
                                        </button>
                                    </div>
                                </form>
                                <?php if (isset($resultArr)) : ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="mb-0">Creating Exams for</h4>
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
                                    <h4 class="mb-4">Exam Parameters</h4>
                                    <form action="exam_create.php" name="exam" id="exam" method="post">
                                        <input type="hidden" name="form-type" value="exam">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="exam_type" class="form-label">Exam Type<span class="asterisk">*</span></label>
                                                <select class="form-select" id="exam_type" name="exam_type" required>
                                                    <option disabled selected value="">Select Exam Type</option>
                                                    <option value="First Term">First Term</option>
                                                    <option value="Half Yearly">Half Yearly</option>
                                                    <option value="Annual">Annual</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="academic_year" class="form-label">Academic Year<span class="asterisk">*</span></label>
                                                <select class="form-select" id="academic_year" name="academic_year" required>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="exam_mode" class="form-label">Exam Mode<span class="asterisk">*</span></label>
                                                <select class="form-select" id="exam_mode" name="exam_mode[]" multiple required>
                                                    <option value="Written">Written</option>
                                                    <option value="Viva">Viva</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="subject_select" class="form-label">Select Subjects<span class="asterisk">*</span></label>
                                                <select class="form-select" id="subject_select" name="subject_select[]" multiple required>
                                                    <option value="Hindi">Hindi</option>
                                                    <option value="English">English</option>
                                                    <option value="Mathematics">Mathematics</option>
                                                    <option value="GK">GK</option>
                                                    <option value="Hamara Parivesh">Hamara Parivesh</option>
                                                    <option value="Computer">Computer</option>
                                                    <option value="Art & Craft">Art & Craft</option>
                                                    <option value="Sulekh+Imla">Sulekh+Imla</option>
                                                    <option value="Project">Project</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div id="subject_details_container">
                                            <!-- Subject details will be added here dynamically -->
                                        </div>

                                        <div class="text-end mt-3 mb-3">
                                            <button type="submit" class="btn btn-primary">Create Exam</button>
                                        </div>
                                    </form>
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
            $('select:not([multiple])').select2({
                minimumResultsForSearch: Infinity
            });

            // Populate academic years
            <?php
            if (date('m') == 1 || date('m') == 2 || date('m') == 3) {
                $currentYear = date('Y') - 1;
            } else {
                $currentYear = date('Y');
            }
            ?>
            var currentYear = <?= $currentYear ?>;
            for (var i = 0; i < 5; i++) {
                var next = currentYear + 1;
                var year = currentYear + '-' + next;
                $('#academic_year').append(new Option(year, year));
                currentYear--;
            }

            // Store all subject data
            var subjectData = {};

            // Main function to rebuild the table
            // Replace the rebuildExamTable() function with this version:

            function rebuildExamTable() {
                var selectedSubjects = $('#subject_select').val() || [];
                var examModes = $('#exam_mode').val() || [];
                var container = $('#subject_details_container');

                // Create table structure if it doesn't exist
                var table = container.find('table.subject-table');
                if (table.length === 0) {
                    table = $('<table class="table table-bordered subject-table">');
                    container.append(table);
                }

                // Build table header
                var thead = $('<thead><tr>');
                thead.append('<th>Subject</th>');

                if (examModes.includes('Written')) {
                    thead.append(
                        '<th style="width: 10%">Full Marks Written</th>' +
                        '<th>Written Exam Date</th>' +
                        '<th>Written Teacher</th>'
                    );
                }

                if (examModes.includes('Viva')) {
                    thead.append(
                        '<th style="width: 10%">Full Marks Viva</th>' +
                        '<th>Viva Exam Date</th>' +
                        '<th>Viva Teacher</th>'
                    );
                }

                thead.append('</tr></thead>');
                table.empty().append(thead);

                // Build table body
                var tbody = $('<tbody>');

                selectedSubjects.forEach(function(subject) {
                    // Get existing data or initialize new entry
                    if (!subjectData[subject]) {
                        subjectData[subject] = {
                            written: {
                                marks: '',
                                date: '',
                                teacher: ''
                            },
                            viva: {
                                marks: '',
                                date: '',
                                teacher: ''
                            }
                        };
                    }

                    var row = $('<tr>');
                    row.append('<td>' + subject + '<input type="hidden" name="subject[]" value="' + subject + '"></td>');

                    // Written exam columns
                    if (examModes.includes('Written')) {
                        row.append(
                            '<td><input type="number" class="form-control" name="full_marks_written[]" min="1" value="' +
                            subjectData[subject].written.marks + '" required></td>' +
                            '<td><input type="date" class="form-control" name="exam_date_written[]" value="' +
                            subjectData[subject].written.date + '" required></td>'
                        );

                        // Handle teacher selection separately to preserve it
                        var writtenTeacherCell = $('<td></td>');
                        var writtenTeacherSelect = $('<select class="form-select teacher-select" name="teacher_id_written[]" required>' +
                            '<option value="" selected disabled>Select Teacher</option></select>');

                        if (subjectData[subject].written.teacher) {
                            // If we have a saved teacher, add it as a selected option
                            writtenTeacherSelect.append(
                                '<option value="' + subjectData[subject].written.teacher + '" selected>' +
                                subjectData[subject].written.teacher + '</option>'
                            );
                        }

                        writtenTeacherCell.append(writtenTeacherSelect);
                        row.append(writtenTeacherCell);
                    }

                    // Viva exam columns
                    if (examModes.includes('Viva')) {
                        row.append(
                            '<td><input type="number" class="form-control" name="full_marks_viva[]" min="1" value="' +
                            subjectData[subject].viva.marks + '" required></td>' +
                            '<td><input type="date" class="form-control" name="exam_date_viva[]" value="' +
                            subjectData[subject].viva.date + '" required></td>'
                        );

                        // Handle teacher selection separately to preserve it
                        var vivaTeacherCell = $('<td></td>');
                        var vivaTeacherSelect = $('<select class="form-select teacher-select" name="teacher_id_viva[]" required>' +
                            '<option value="" selected disabled>Select Teacher</option></select>');

                        if (subjectData[subject].viva.teacher) {
                            // If we have a saved teacher, add it as a selected option
                            vivaTeacherSelect.append(
                                '<option value="' + subjectData[subject].viva.teacher + '" selected>' +
                                subjectData[subject].viva.teacher + '</option>'
                            );
                        }

                        vivaTeacherCell.append(vivaTeacherSelect);
                        row.append(vivaTeacherCell);
                    }

                    tbody.append(row);
                });

                table.append(tbody);

                // Initialize Select2 for all teacher dropdowns
                $('.teacher-select').each(function() {
                    initializeTeacherSelect($(this));

                    // Set the correct selected value after initialization
                    var subject = $(this).closest('tr').find('input[name="subject[]"]').val();
                    var examType = $(this).attr('name').includes('written') ? 'written' : 'viva';
                    var savedTeacher = subjectData[subject][examType].teacher;

                    if (savedTeacher) {
                        $(this).val(savedTeacher).trigger('change');
                    }
                });
            }

            // Save data before any changes
            function saveCurrentData() {
                $('#subject_details_container tbody tr').each(function() {
                    var subject = $(this).find('input[name="subject[]"]').val();
                    subjectData[subject] = {
                        written: {
                            marks: $(this).find('input[name="full_marks_written[]"]').val(),
                            date: $(this).find('input[name="exam_date_written[]"]').val(),
                            teacher: $(this).find('select[name="teacher_id_written[]"]').val()
                        },
                        viva: {
                            marks: $(this).find('input[name="full_marks_viva[]"]').val(),
                            date: $(this).find('input[name="exam_date_viva[]"]').val(),
                            teacher: $(this).find('select[name="teacher_id_viva[]"]').val()
                        }
                    };
                });
            }

            // Handle subject selection changes
            $('#subject_select').on('change', function() {
                saveCurrentData();
                rebuildExamTable();
            });

            // Handle exam mode changes
            $('#exam_mode').on('change', function() {
                saveCurrentData();
                rebuildExamTable();
            });

            // Function to initialize teacher select dropdowns
            function initializeTeacherSelect(selector) {
                selector.select2({
                    ajax: {
                        url: 'fetch_associates.php',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term,
                                isShiftPlanner: true
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1,
                    placeholder: 'Select teacher',
                    allowClear: true,
                    templateResult: function(teacher) {
                        if (teacher.loading) return teacher.text;
                        return $('<span>').text(teacher.text);
                    },
                    templateSelection: function(teacher) {
                        return teacher.text;
                    }
                }).on('change', function() {
                    // Save teacher selection immediately
                    var subject = $(this).closest('tr').find('input[name="subject[]"]').val();
                    var examType = $(this).attr('name').includes('written') ? 'written' : 'viva';
                    subjectData[subject][examType].teacher = $(this).val();
                });
            }

            // Save input changes on blur
            $(document).on('blur', 'input[name^="full_marks_"], input[name^="exam_date_"]', function() {
                var subject = $(this).closest('tr').find('input[name="subject[]"]').val();
                var fieldType = $(this).attr('name').includes('written') ? 'written' : 'viva';
                var fieldName = $(this).attr('name').includes('marks') ? 'marks' : 'date';
                subjectData[subject][fieldType][fieldName] = $(this).val();
            });
        });
    </script>

</body>

</html>