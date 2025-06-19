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

// Fetch existing exams for dropdown
$exams_query = "
    SELECT e.exam_id, exam_type, academic_year, ed.class, subject 
    FROM exams e
    LEFT JOIN exam_marks_data ed ON ed.exam_id = e.exam_id
    ORDER BY academic_year DESC, exam_type, subject
";
$exams_result = pg_query($con, $exams_query);
$exams = pg_fetch_all($exams_result) ?: [];

// Process form submission for adding students
if (@$_POST['form-type'] == "add_students") {
    $exam_ids = $_POST['exam_id'] ?? []; // Now an array of exam IDs
    $student_ids = $_POST['student_ids'] ?? [];

    if (empty($exam_ids)) {
        echo "<script>alert('Please select at least one exam');</script>";
    } elseif (empty($student_ids)) {
        echo "<script>alert('Please select at least one student');</script>";
    } else {
        $success_count = 0;
        $error_count = 0;

        foreach ($exam_ids as $exam_id) {
            foreach ($student_ids as $student_id) {
                // Check if student already exists in this exam
                $check_query = "SELECT 1 FROM exam_marks_data WHERE exam_id = $1 AND student_id = $2";
                $check_result = pg_query_params($con, $check_query, [$exam_id, $student_id]);

                if (pg_num_rows($check_result) > 0) {
                    $error_count++;
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
                    }
                } else {
                    $error_count++;
                }
            }
        }

        // In your PHP code where you process the form:
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success_count,
            'errors' => $error_count,
            'message' => "Successfully added $success_count student-exam combinations." .
                ($error_count > 0 ? " $error_count combinations were not added (already exists or invalid)." : "")
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

        /* Add to your existing styles */
        .table-warning {
            background-color: rgba(255, 193, 7, 0.1);
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
                                            <div class="col-md-8">
                                                <label for="exam_id" class="form-label">Existing Exam(s)</label>
                                                <select class="form-select" id="exam_id" name="exam_id[]" multiple required>
                                                    <?php foreach ($exams as $exam): ?>
                                                        <option value="<?= $exam['exam_id'] ?>">
                                                            <?= htmlspecialchars($exam['academic_year']) ?>-
                                                            <?= htmlspecialchars($exam['exam_type']) ?>-
                                                            <?= htmlspecialchars($exam['class']) ?>-
                                                            <?= htmlspecialchars($exam['subject']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" id="loadExamBtn" class="btn btn-primary w-100">
                                                    <i class="bi bi-search"></i> Load Exam(s)
                                                </button>
                                            </div>
                                        </form>
                                        <!-- <div id="selectedExamsContainer" class="mt-2"></div> -->
                                    </div>

                                    <div class="col-md-4">
                                        <div class="exam-info-card p-3 rounded" id="examInfoCard" style="display:none;">
                                            <h6>Selected Exams <span id="examCounter" class="badge bg-secondary ms-2"></span></h6>

                                            <!-- Slider controls -->
                                            <div class="d-flex justify-content-between mb-2">
                                                <button id="prevExam" class="btn btn-sm btn-outline-secondary" disabled>
                                                    <i class="bi bi-chevron-left"></i>
                                                </button>
                                                <span id="currentExamIndicator">1/1</span>
                                                <button id="nextExam" class="btn btn-sm btn-outline-secondary" disabled>
                                                    <i class="bi bi-chevron-right"></i>
                                                </button>
                                            </div>

                                            <!-- Exam details container -->
                                            <div id="examDetailsSlider">
                                                <!-- Individual exam details will be inserted here by JavaScript -->
                                            </div>
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
                                            <label for="class" class="form-label small mb-1">Class</label>
                                            <select class="form-select" id="class" name="class[]" multiple data-placeholder="Select class(es)">
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
                                            <input type="text" class="form-control" id="student_ids" name="student_ids"
                                                placeholder="e.g., RSSI001, RSSI002">
                                        </div>

                                        <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                                            <label for="excluded_ids" class="form-label small mb-1">Exclude Student IDs</label>
                                            <input type="text" class="form-control" id="excluded_ids" name="excluded_ids"
                                                placeholder="e.g., RSSI003, RSSI004">
                                        </div>

                                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                                            <button type="button" id="filterBtn" class="btn btn-primary w-100">
                                                <i class="bi bi-funnel-fill me-1"></i> Filter
                                            </button>
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
                                                <tbody id="studentTableBody">
                                                    <!-- Student data will be loaded here via AJAX -->
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="text-end mt-3">
                                            <button type="submit" class="btn btn-success" id="addStudentsBtn" disabled>
                                                <i class="bi bi-plus-circle"></i> Add Selected Students
                                            </button>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
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

            $('#exam_id').select2();

            // Make table rows clickable
            $(document).on('click', '#studentTableBody tr', function(e) {
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
                    const checkbox = $(this).find('.student-checkbox');
                    checkbox.prop('checked', !checkbox.prop('checked'));
                    updateSelectedCount();
                }
            });

            // Load exam details when exam is selected
            $('#loadExamBtn').click(function() {
                const examIds = $('#exam_id').val();
                if (!examIds || examIds.length === 0) {
                    showAlert('Please select at least one exam first', 'warning');
                    return;
                }

                // Show loading state
                const $btn = $(this);
                $btn.prop('disabled', true);
                $btn.html(`
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Loading Exam(s)...
                `);

                // Clear previous exam info
                $('#examTitles').empty();
                $('#selectedExamsContainer').empty();

                // Create badges for selected exams
                examIds.forEach(examId => {
                    const selectedOption = $('#exam_id option[value="' + examId + '"]');
                    const examText = selectedOption.text();
                    $('#selectedExamsContainer').append(`
                        <span class="badge bg-primary selected-exam-badge">${examText}</span>
                    `);
                });

                // For demo purposes, we'll load details for the first selected exam
                // In a real app, you might want to load all exam details
                const firstExamId = examIds[0];

                $.ajax({
                    url: 'fetch_exam_details.php',
                    method: 'GET',
                    data: {
                        exam_id: firstExamId
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data) {
                            updateExamInfoUI(data);
                            $('#studentFilterSection').show();
                            $('#selected_exam_id').val(examIds.join(','));
                            $('#form_exam_id').val(examIds.join(','));

                            $('#studentTableContainer').hide();
                            $('#studentTableBody').empty();

                            scrollToElement('#studentFilterSection');
                        }
                    },
                    error: function() {
                        showAlert('Error loading exam details', 'danger');
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $btn.html('<i class="bi bi-search me-1"></i> Load Exam(s)');
                    }
                });
            });

            // Handle filter button click
            $('#filterBtn').click(function() {
                const examIds = $('#selected_exam_id').val();
                if (!examIds) {
                    showAlert('Please select and load exam(s) first', 'warning');
                    return;
                }

                // Show loading state
                const $btn = $(this);
                $btn.prop('disabled', true);
                $btn.html(`
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Filtering...
                `);

                // Get filter values
                const classFilter = $('#class').val() || [];
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
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.length > 0) {
                            populateStudentTable(data);
                            $('#studentTableContainer').show();
                            scrollToElement('#studentTableContainer');
                        } else {
                            showAlert('No students found matching your criteria', 'info');
                            $('#studentTableContainer').hide();
                        }
                    },
                    error: function(xhr, status, error) {
                        showAlert('Error filtering students: ' + error, 'danger');
                        console.error('Error:', error);
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $btn.html('<i class="bi bi-funnel-fill me-1"></i> Filter');
                    }
                });
            });

            // Helper function to update exam info UI
            function updateExamInfoUI(data) {
                $('#examTitles').text(`${data.academic_year} - ${data.exam_type} - ${data.subject}`);

                // Parse exam modes
                const modes = data.exam_mode.replace(/{|}/g, '').split(',');
                $('#examMode').empty();
                modes.forEach(mode => {
                    $('#examMode').append(`<span class="badge bg-primary me-1">${mode.trim()}</span>`);
                });

                // Update exam details
                updateExamDetail('#writtenInfo', data.full_marks_written, data.exam_date_written, data.teacher_written);
                updateExamDetail('#vivaInfo', data.full_marks_viva, data.exam_date_viva, data.teacher_viva);

                // Show the card with animation
                $('#examInfoCard').show();
            }

            // Helper function for exam detail display
            function updateExamDetail(selector, marks, date, teacher) {
                const $element = $(selector);
                $element.empty();

                if (marks) {
                    $element.append(`<span class="fw-semibold">${marks} marks</span>`);
                    if (date) {
                        $element.append(`<span class="text-muted ms-2">${date}</span>`);
                    }
                    if (teacher) {
                        $element.append(`<div class="text-muted small">Assigned to: ${teacher}</div>`);
                    }
                } else {
                    $element.append('<span class="text-muted">Not applicable</span>');
                }
            }

            // Helper function to populate student table
            // Modify the populateStudentTable function:
            function populateStudentTable(students) {
                const $tbody = $('#studentTableBody');
                $tbody.empty();

                students.forEach(student => {
                    const isEnrolled = student.exam_count > 0;
                    const rowClass = isEnrolled ? 'table-warning' : '';
                    const statusText = isEnrolled ? '<span class="badge bg-warning text-dark">Already in exam</span>' : '';

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
            }

            // Helper function for smooth scrolling
            function scrollToElement(selector) {
                const element = $(selector);
                if (element.length) {
                    $('html, body').animate({
                        scrollTop: element.offset().top - 20
                    }, 500);
                }
            }

            // Helper function for alerts
            function showAlert(message, type) {
                const alert = $(`
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                $('#alertContainer').empty().append(alert);
            }

            // Select all/none functionality
            // Modify the selectAll change handler:
            $('#selectAll').change(function() {
                $('.student-checkbox:not(:disabled)').prop('checked', this.checked);
                updateSelectedCount();
            });

            function updateSelectedCount() {
                const count = $('.student-checkbox:checked').length;
                const disabledCount = $('.student-checkbox:disabled').length;
                $('#selectedCount').text(`Selected: ${count}`);
                $('#addStudentsBtn').prop('disabled', count === 0);

                if (disabledCount > 0) {
                    $('#selectedCount').append(` <span class="text-muted">(${disabledCount} already enrolled)</span>`);
                }
            }

            // Handle form submission for adding students
            $('#addStudentsForm').submit(function(e) {
                e.preventDefault();

                const $btn = $('#addStudentsBtn');
                $btn.prop('disabled', true);
                $btn.html(`
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Adding Students...
                `);

                $.ajax({
                    url: '',
                    method: 'POST',
                    data: $(this).serialize(),
                    // Replace the current success handler in your AJAX call with:
                    success: function(response) {
                        if (response.success > 0 || response.errors > 0) {
                            showAlert(response.message, response.errors > 0 ? 'info' : 'success');
                        }
                        // Reload the student list to reflect changes
                        $('#filterBtn').click();
                    },
                    error: function(xhr) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            showAlert(response.message || 'Error adding students', 'danger');
                        } catch (e) {
                            showAlert('Error adding students', 'danger');
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $btn.html('<i class="bi bi-plus-circle"></i> Add Selected Students');
                    }
                });
            });
        });
    </script>
    <script>
        // Add these variables at the top of your script
        let currentExamIndex = 0;
        let examDetailsData = [];

        // Update the load exam function
        $('#loadExamBtn').click(function() {
            const examIds = $('#exam_id').val();
            if (!examIds || examIds.length === 0) {
                showAlert('Please select at least one exam first', 'warning');
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true);
            $btn.html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...`);

            // Clear previous data
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
                    showAlert('Error loading some exam details', 'danger');
                    console.error('Error:', error);
                })
                .finally(() => {
                    $btn.prop('disabled', false);
                    $btn.html('<i class="bi bi-search me-1"></i> Load Exam(s)');
                });
        });

        // Slider navigation functions
        function updateExamSlider() {
            const exam = examDetailsData[currentExamIndex];
            const examCount = examDetailsData.length;

            // Update counter
            $('#examCounter').text(`${currentExamIndex + 1}/${examCount}`);
            $('#currentExamIndicator').text(`${currentExamIndex + 1}/${examCount}`);

            // Update navigation buttons
            $('#prevExam').prop('disabled', currentExamIndex === 0);
            $('#nextExam').prop('disabled', currentExamIndex === examCount - 1);

            // Update exam details
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
            const modeList = modes.replace(/{|}/g, '').split(',');
            return modeList.map(mode => `<span class="badge bg-primary me-1">${mode.trim()}</span>`).join('');
        }

        function formatExamDetail(marks, date, teacher) {
            let detail = marks ? `<span class="fw-semibold">${marks} marks</span>` : '<span class="text-muted">Not applicable</span>';
            if (date) detail += `<span class="text-muted ms-2">${date}</span>`;
            if (teacher) detail += `<div class="text-muted small">Assigned to: ${teacher}</div>`;
            return detail;
        }

        // Slider navigation event handlers
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
    </script>
</body>

</html>