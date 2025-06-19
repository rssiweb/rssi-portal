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

// Define class and category options
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

// Fetch existing exams for dropdown - MODIFIED QUERY
$exams_query = "
    SELECT DISTINCT academic_year 
    FROM exams 
    WHERE estatus IS NULL 
    AND academic_year IS NOT NULL
    AND academic_year != ''
    ORDER BY academic_year DESC
";
$academic_years_result = pg_query($con, $exams_query);
$academic_years = pg_fetch_all($academic_years_result) ?: [];

// Process form submission for adding students
if (@$_POST['form-type'] == "add_students") {
    $exam_ids = $_POST['exam_id'] ?? [];
    $student_ids = $_POST['student_ids'] ?? [];

    if (empty($exam_ids)) {
        echo "<script>alert('Please select at least one exam');</script>";
    } elseif (empty($student_ids)) {
        echo "<script>alert('Please select at least one student');</script>";
    } else {
        $success_count = 0;
        $error_count = 0;
        $errors = [];

        foreach ($exam_ids as $exam_id) {
            foreach ($student_ids as $student_id) {
                // Check if student already exists in this exam
                $check_query = "SELECT 1 FROM exam_marks_data WHERE exam_id = $1 AND student_id = $2";
                $check_result = pg_query_params($con, $check_query, [$exam_id, $student_id]);

                if (pg_num_rows($check_result) > 0) {
                    $error_count++;
                    $errors[] = "Student $student_id already exists in exam $exam_id";
                    continue;
                }

                // Get student details
                $student_query = "SELECT studentname, category, class FROM rssimyprofile_student WHERE student_id = $1";
                $student_result = pg_query_params($con, $student_query, [$student_id]);
                $student = pg_fetch_assoc($student_result);

                if ($student) {
                    $insert_query = "INSERT INTO exam_marks_data (exam_id, student_id, category, class) 
                                    VALUES ($1, $2, $3, $4)";
                    $insert_result = pg_query_params($con, $insert_query, [
                        $exam_id,
                        $student_id,
                        $student['category'],
                        $student['class']
                    ]);

                    if ($insert_result) {
                        $success_count++;
                    } else {
                        $error_count++;
                        $errors[] = "Failed to add student $student_id to exam $exam_id";
                    }
                } else {
                    $error_count++;
                    $errors[] = "Student $student_id not found";
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success_count,
            'errors' => $error_count,
            'message' => "Successfully added $success_count student-exam combinations." .
                ($error_count > 0 ? " $error_count combinations had errors." : ""),
            'error_details' => $errors
        ]);
        exit;
    }
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
    <title>Add Students to Exam</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
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

        .exam-info-card {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
        }

        #studentTableContainer {
            display: none;
        }

        #studentTableBody tr {
            cursor: pointer;
        }

        #studentTableBody tr:hover {
            background-color: #f8f9fa;
        }

        .selected-exam-badge {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .table-warning {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .loading-spinner {
            display: none;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Add Students to Exam</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Academic</li>
                    <li class="breadcrumb-item active">Add Students to Exam</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="container mt-3">
                                <!-- Alert container -->
                                <div id="alertContainer"></div>

                                <!-- Exam Selection Section -->
                                <div class="row mb-4">
                                    <div class="col-md-8">
                                        <h4>Select Exam(s)</h4>
                                        <form id="examSelectForm" class="row g-3 align-items-end">
                                            <div class="col-md-3">
                                                <label for="academic_year" class="form-label">Academic Year</label>
                                                <select class="form-select" id="academic_year" name="academic_year" required>
                                                    <option value="" selected disabled>Select year...</option>
                                                    <?php foreach ($academic_years as $year): ?>
                                                        <option value="<?= htmlspecialchars($year['academic_year']) ?>">
                                                            <?= htmlspecialchars($year['academic_year']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="exam_type" class="form-label">Exam Type</label>
                                                <select class="form-select" id="exam_type" name="exam_type" disabled required>
                                                    <option value="" selected disabled>Select type...</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="class" class="form-label">Class</label>
                                                <select class="form-select" id="class" name="class" disabled required>
                                                    <option value="" selected disabled>Select class...</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="subject" class="form-label">Subject(s)</label>
                                                <select class="form-select" id="subject" name="exam_id[]" multiple disabled required>
                                                    <!-- Options will be loaded via AJAX -->
                                                </select>
                                            </div>
                                            <div class="col-md-12">
                                                <button type="button" id="loadExamBtn" class="btn btn-primary">
                                                    <i class="bi bi-search me-1"></i> Load Exam(s)
                                                </button>
                                                <span id="loadingAcademic" class="loading-spinner ms-2">
                                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                                    Loading...
                                                </span>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="exam-info-card p-3 rounded" id="examInfoCard" style="display:none;">
                                            <h6>Selected Exams <span id="examCounter" class="badge bg-secondary ms-2"></span></h6>
                                            <div class="d-flex justify-content-between mb-2">
                                                <button id="prevExam" class="btn btn-sm btn-outline-secondary" disabled>
                                                    <i class="bi bi-chevron-left"></i>
                                                </button>
                                                <span id="currentExamIndicator">1/1</span>
                                                <button id="nextExam" class="btn btn-sm btn-outline-secondary" disabled>
                                                    <i class="bi bi-chevron-right"></i>
                                                </button>
                                            </div>
                                            <div id="examDetailsSlider"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Student Filter Section -->
                                <div id="studentFilterSection" style="display:none;">
                                    <h4>Select Students to Add</h4>
                                    <div class="alert alert-info mb-3 py-2">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Filter students to add to the selected exam(s).
                                    </div>

                                    <form id="filterForm" class="row g-2 align-items-end">
                                        <input type="hidden" name="exam_id" id="selected_exam_id">

                                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                            <label for="filter_class" class="form-label small mb-1">Class</label>
                                            <select class="form-select" id="filter_class" name="class[]" multiple data-placeholder="Select class(es)">
                                                <?php foreach ($class_options as $value => $label): ?>
                                                    <option value="<?= $value ?>"><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                            <label for="category" class="form-label small mb-1">Category</label>
                                            <select class="form-select" id="category" name="category[]" multiple data-placeholder="Select category(ies)">
                                                <?php foreach ($category_options as $value => $label): ?>
                                                    <option value="<?= $value ?>"><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                                            <label for="student_ids" class="form-label small mb-1">Include Student IDs</label>
                                            <input type="text" class="form-control" id="student_ids" name="student_ids" placeholder="e.g., RSSI001, RSSI002">
                                        </div>

                                        <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                                            <label for="excluded_ids" class="form-label small mb-1">Exclude Student IDs</label>
                                            <input type="text" class="form-control" id="excluded_ids" name="excluded_ids" placeholder="e.g., RSSI003, RSSI004">
                                        </div>

                                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                            <button type="button" id="filterBtn" class="btn btn-primary w-100">
                                                <i class="bi bi-funnel-fill me-1"></i> Filter
                                            </button>
                                            <span id="loadingFilter" class="loading-spinner" style="display: none;">
                                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                                Loading...
                                            </span>
                                        </div>
                                    </form>
                                </div>

                                <!-- Student Listing Section -->
                                <div id="studentTableContainer" class="mt-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="mb-0">Students to Add</h4>
                                        <div>
                                            <span class="badge bg-primary me-2" id="totalCount">Total: 0</span>
                                            <span class="badge bg-success" id="selectedCount">Selected: 0</span>
                                        </div>
                                    </div>

                                    <form id="addStudentsForm" method="post" action="">
                                        <input type="hidden" name="form-type" value="add_students">
                                        <input type="hidden" name="exam_id[]" id="form_exam_id">

                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="40"><input type="checkbox" id="selectAll"></th>
                                                        <th>Student ID</th>
                                                        <th>Student Name</th>
                                                        <th>Category</th>
                                                        <th>Class</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="studentTableBody"></tbody>
                                            </table>
                                        </div>

                                        <div class="text-end mt-3">
                                            <button type="submit" class="btn btn-success" id="addStudentsBtn" disabled>
                                                <i class="bi bi-plus-circle"></i> Add Selected Students
                                            </button>
                                            <span id="loadingAdd" class="loading-spinner ms-2" style="display: none;">
                                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                                Processing...
                                            </span>
                                        </div>
                                    </form>
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('select[multiple]').select2({
                placeholder: $(this).data('placeholder'),
                width: '100%',
                closeOnSelect: false
            });

            // Variables for exam slider
            let currentExamIndex = 0;
            let examDetailsData = [];

            // Helper functions
            function showAlert(message, type) {
                const alert = $(`
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                $('#alertContainer').empty().append(alert);
            }

            function scrollToElement(selector) {
                const element = $(selector);
                if (element.length) {
                    $('html, body').animate({
                        scrollTop: element.offset().top - 20
                    }, 500);
                }
            }

            function updateSelectedCount() {
                const count = $('.student-checkbox:checked').length;
                const disabledCount = $('.student-checkbox:disabled').length;
                $('#selectedCount').text(`Selected: ${count}`);
                $('#addStudentsBtn').prop('disabled', count === 0);

                if (disabledCount > 0) {
                    $('#selectedCount').append(` <span class="text-muted">(${disabledCount} already enrolled)</span>`);
                }
            }

            function toggleLoading(element, show) {
                if (show) {
                    element.next('.loading-spinner').show();
                    element.prop('disabled', true);
                } else {
                    element.next('.loading-spinner').hide();
                    element.prop('disabled', false);
                }
            }

            // Academic year cascading dropdowns
            $('#academic_year').change(function() {
                const year = $(this).val();
                if (!year) return;

                toggleLoading($('#academic_year'), true);
                $('#exam_type').empty().append('<option value="">Loading...</option>').prop('disabled', true);
                $('#class').empty().prop('disabled', true);
                $('#subject').empty().prop('disabled', true);

                $.get('fetch_exam_types.php', {
                        academic_year: year
                    })
                    .done(function(data) {
                        console.log("Received exam types:", data); // <-- Add this
                        $('#exam_type').empty().append('<option value="">Select exam type</option>');
                        data.forEach(type => {
                            $('#exam_type').append(`<option value="${type}">${type}</option>`);
                        });
                        $('#exam_type').prop('disabled', false);
                    })
                    .fail(function() {
                        showAlert('Error loading exam types', 'danger');
                    })
                    .always(function() {
                        toggleLoading($('#academic_year'), false);
                    });
            });

            $('#exam_type').change(function() {
                const year = $('#academic_year').val();
                const type = $(this).val();
                if (!year || !type) return;

                toggleLoading($('#exam_type'), true);
                $('#class').empty().append('<option value="">Loading...</option>').prop('disabled', false);
                $('#subject').empty().prop('disabled', true);

                $.get('fetch_exam_classes.php', {
                        academic_year: year,
                        exam_type: type
                    })
                    .done(function(data) {
                        $('#class').empty().append('<option value="">Select class</option>');
                        data.forEach(cls => {
                            $('#class').append(`<option value="${cls}">${cls}</option>`);
                        });
                    })
                    .fail(function() {
                        showAlert('Error loading classes', 'danger');
                    })
                    .always(function() {
                        toggleLoading($('#exam_type'), false);
                    });
            });

            $('#class').change(function() {
                const year = $('#academic_year').val();
                const type = $('#exam_type').val();
                const cls = $(this).val();
                if (!year || !type || !cls) return;

                toggleLoading($('#class'), true);
                $('#subject').empty().append('<option value="">Loading...</option>');

                $.get('fetch_exam_subjects.php', {
                        academic_year: year,
                        exam_type: type,
                        class: cls
                    })
                    .done(function(data) {
                        $('#subject').prop('disabled', false); // ✅ Enable the dropdown here
                        $('#subject').empty().select2({
                            placeholder: "Select subject(s)",
                            width: '100%',
                            multiple: true,
                            data: data.map(subject => ({
                                id: subject.exam_id,
                                text: subject.subject
                            }))
                        });
                    })
                    .fail(function() {
                        showAlert('Error loading subjects', 'danger');
                    })
                    .always(function() {
                        toggleLoading($('#class'), false);
                    });
            });

            // Load exam details
            $('#loadExamBtn').click(function() {
                const examIds = $('#subject').val();
                if (!examIds || examIds.length === 0) {
                    showAlert('Please select at least one subject first', 'warning');
                    return;
                }

                toggleLoading($('#loadExamBtn'), true);
                examDetailsData = [];
                $('#examDetailsSlider').empty();

                // Create an array of promises for all exam details
                const loadPromises = examIds.map(examId => {
                    return $.ajax({
                        url: 'fetch_exam_details.php',
                        method: 'GET',
                        data: {
                            exam_id: examId
                        },
                        dataType: 'json'
                    });
                });

                // Load all exam details
                Promise.all(loadPromises)
                    .then(responses => {
                        examDetailsData = responses.filter(r => r); // Filter out any failed requests

                        if (examDetailsData.length === 0) {
                            showAlert('No valid exam data could be loaded', 'warning');
                            return;
                        }

                        // Initialize slider
                        currentExamIndex = 0;
                        updateExamSlider();

                        $('#studentFilterSection').show();
                        $('#selected_exam_id').val(examIds.join(','));
                        $('#form_exam_id').val(examIds.join(','));

                        $('#examInfoCard').show();
                        scrollToElement('#studentFilterSection');
                    })
                    .catch(error => {
                        showAlert('Error loading some exam details: ' + error, 'danger');
                        console.error('Error:', error);
                    })
                    .always(() => {
                        toggleLoading($('#loadExamBtn'), false);
                    });
            });

            // Exam slider functions
            function updateExamSlider() {
                if (examDetailsData.length === 0) return;

                const exam = examDetailsData[currentExamIndex];
                const examCount = examDetailsData.length;

                // Update counter and navigation
                $('#examCounter').text(`${currentExamIndex + 1}/${examCount}`);
                $('#currentExamIndicator').text(`${currentExamIndex + 1}/${examCount}`);
                $('#prevExam').prop('disabled', currentExamIndex === 0);
                $('#nextExam').prop('disabled', currentExamIndex === examCount - 1);

                // Update exam details display
                $('#examDetailsSlider').html(`
                    <div class="exam-details">
                        <h6 class="mb-2">${exam.academic_year} - ${exam.exam_type} - ${exam.subject}</h6>
                        <div class="small">
                            <div>Mode: ${formatExamModes(exam.exam_mode)}</div>
                            <div>Written: ${formatExamDetail(exam.full_marks_written, exam.exam_date_written, exam.teacher_written)}</div>
                            <div>Viva: ${formatExamDetail(exam.full_marks_viva, exam.exam_date_viva, exam.teacher_viva)}</div>
                        </div>
                    </div>
                `);
            }

            function formatExamModes(modes) {
                if (!modes) return 'N/A';
                const modeList = modes.replace(/{|}/g, '').split(',');
                return modeList.map(mode => `<span class="badge bg-primary me-1">${mode.trim()}</span>`).join('');
            }

            function formatExamDetail(marks, date, teacher) {
                let detail = marks ? `<span class="fw-semibold">${marks} marks</span>` : '<span class="text-muted">Not applicable</span>';
                if (date) detail += `<span class="text-muted ms-2">${date}</span>`;
                if (teacher) detail += `<div class="text-muted small">Assigned to: ${teacher}</div>`;
                return detail;
            }

            // Slider navigation
            $('#prevExam').click(function() {
                if (currentExamIndex > 0) {
                    currentExamIndex--;
                    updateExamSlider();
                }
            });

            $('#nextExam').click(function() {
                if (currentExamIndex < examDetailsData.length - 1) {
                    currentExamIndex++;
                    updateExamSlider();
                }
            });

            // Filter students
            $('#filterBtn').click(function() {
                const examIds = $('#selected_exam_id').val();
                if (!examIds) {
                    showAlert('Please select and load exam(s) first', 'warning');
                    return;
                }

                toggleLoading($('#filterBtn'), true);

                const classFilter = $('#filter_class').val() || [];
                const categoryFilter = $('#category').val() || [];
                const includeIds = $('#student_ids').val() || '';
                const excludeIds = $('#excluded_ids').val() || '';

                $.ajax({
                        url: 'fetch_exam_students.php',
                        method: 'POST',
                        data: {
                            exam_id: examIds,
                            class: classFilter,
                            category: categoryFilter,
                            student_ids: includeIds,
                            excluded_ids: excludeIds
                        },
                        dataType: 'json'
                    })
                    .done(function(data) {
                        if (data && data.length > 0) {
                            populateStudentTable(data, examIds.split(','));
                            $('#studentTableContainer').show();
                            scrollToElement('#studentTableContainer');
                        } else {
                            showAlert('No students found matching your criteria', 'info');
                            $('#studentTableContainer').hide();
                        }
                    })
                    .fail(function(xhr, status, error) {
                        showAlert('Error filtering students: ' + error, 'danger');
                        console.error('Error:', error);
                    })
                    .always(function() {
                        toggleLoading($('#filterBtn'), false);
                    });
            });

            // Populate student table with enrollment status
            function populateStudentTable(students, examIds) {
                const $tbody = $('#studentTableBody');
                $tbody.empty();

                if (!students || students.length === 0) {
                    $tbody.append('<tr><td colspan="5" class="text-center">No students found</td></tr>');
                    return;
                }

                const studentIds = students.map(s => s.student_id);

                $.ajax({
                        url: 'fetch_student_exam_status.php',
                        method: 'POST',
                        data: {
                            student_ids: studentIds.join(','),
                            exam_ids: examIds.join(',')
                        },
                        dataType: 'json'
                    })
                    .done(function(enrollmentData) {
                        students.forEach(student => {
                            const enrolledExams = enrollmentData.filter(e => e.student_id === student.student_id);
                            const isEnrolled = enrolledExams.length > 0;
                            const rowClass = isEnrolled ? 'table-warning' : '';
                            const statusText = isEnrolled ?
                                `<span class="badge bg-warning text-dark">Already in ${enrolledExams.map(e => e.subject).join(', ')}</span>` : '';

                            $tbody.append(`
                            <tr class="${rowClass}">
                                <td>
                                    <input type="checkbox" name="student_ids[]" 
                                           value="${student.student_id}" 
                                           class="student-checkbox"
                                           ${isEnrolled ? 'disabled' : ''}>
                                </td>
                                <td>${student.student_id} ${statusText}</td>
                                <td>${student.studentname}</td>
                                <td>${student.category}</td>
                                <td>${student.class}</td>
                            </tr>
                        `);
                        });

                        $('#totalCount').text(`Total: ${students.length}`);
                        updateSelectedCount();
                    })
                    .fail(function() {
                        showAlert('Error checking enrollment status', 'danger');
                    });
            }

            // Select all/none functionality
            $('#selectAll').change(function() {
                $('.student-checkbox:not(:disabled)').prop('checked', this.checked);
                updateSelectedCount();
            });

            // Form submission
            $('#addStudentsForm').submit(function(e) {
                e.preventDefault();

                const selectedStudents = $('.student-checkbox:checked');
                if (selectedStudents.length === 0) {
                    showAlert('Please select at least one student to add', 'warning');
                    return;
                }

                toggleLoading($('#addStudentsBtn'), true);

                $.ajax({
                        url: '',
                        method: 'POST',
                        data: $(this).serialize(),
                        dataType: 'json'
                    })
                    .done(function(response) {
                        if (response.success > 0 || response.errors > 0) {
                            const alertType = response.errors > 0 ? 'info' : 'success';
                            showAlert(response.message, alertType);

                            if (response.error_details && response.error_details.length > 0) {
                                console.log('Error details:', response.error_details);
                            }
                        }
                        // Refresh the student list
                        $('#filterBtn').click();
                    })
                    .fail(function(xhr) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            showAlert(response.message || 'Error adding students', 'danger');
                        } catch (e) {
                            showAlert('Error adding students', 'danger');
                        }
                    })
                    .always(function() {
                        toggleLoading($('#addStudentsBtn'), false);
                    });
            });
        });
    </script>
</body>

</html>