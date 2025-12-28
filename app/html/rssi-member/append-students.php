<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/email.php");
include("../../util/login_util.php");

ob_start();

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
    // Handle exam IDs - ensure it's an array
    $exam_ids = is_array($_POST['exam_id']) ? $_POST['exam_id'] : [$_POST['exam_id'] ?? ''];
    $exam_ids = array_filter(array_map('trim', $exam_ids), 'strlen');

    // Handle student IDs - ensure it's an array
    $student_ids = is_array($_POST['student_ids']) ? $_POST['student_ids'] : [$_POST['student_ids'] ?? ''];
    $student_ids = array_filter(array_map('trim', $student_ids), 'strlen');

    if (empty($exam_ids)) {
        echo json_encode(['error' => 'Please select at least one exam']);
        exit;
    } elseif (empty($student_ids)) {
        echo json_encode(['error' => 'Please select at least one student']);
        exit;
    }

    // Start transaction
    pg_query($con, "BEGIN");

    $success_count = 0;
    $error_count = 0;
    $errors = [];
    $added_combinations = [];

    try {
        // First get all student details at once
        $student_ids_str = implode("','", array_map(function ($id) use ($con) {
            return pg_escape_string($con, $id);
        }, $student_ids));
        $student_query = "SELECT student_id, studentname, category, class 
                         FROM rssimyprofile_student 
                         WHERE student_id IN ('$student_ids_str')";
        $student_result = pg_query($con, $student_query);
        $students = pg_fetch_all($student_result) ?: [];
        $students = array_column($students, null, 'student_id');

        foreach ($exam_ids as $exam_id) {
            foreach ($student_ids as $student_id) {
                $key = "$exam_id-$student_id";
                if (isset($added_combinations[$key])) {
                    continue;
                }

                // Check if student exists in this specific exam
                $check_query = "SELECT 1 FROM exam_marks_data 
                                WHERE exam_id = $1 
                                AND LOWER(TRIM(student_id)) = LOWER(TRIM($2))";
                $check_result = pg_query_params($con, $check_query, [
                    $exam_id,
                    $student_id
                ]);

                if (pg_num_rows($check_result) > 0) {
                    $error_count++;
                    $errors[] = "Student $student_id already exists in exam $exam_id";
                    $added_combinations[$key] = false;
                    continue;
                }

                // Insert if student exists and not already in exam
                if (isset($students[$student_id])) {
                    $student = $students[$student_id];
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
                        $added_combinations[$key] = true;
                    } else {
                        throw new Exception("Failed to add student $student_id to exam $exam_id");
                    }
                } else {
                    $error_count++;
                    $errors[] = "Student $student_id not found";
                    $added_combinations[$key] = false;
                }
            }
        }

        pg_query($con, "COMMIT");
    } catch (Exception $e) {
        pg_query($con, "ROLLBACK");
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
    ob_end_clean();
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
    <title>Add Students</title>
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

        .exam-tag {
            display: inline-block;
            background-color: #f0f0f0;
            border-radius: 3px;
            padding: 2px 5px;
            margin: 2px;
            font-size: 0.8em;
            color: #555;
        }

        .exam-tag.subject {
            background-color: #e3f2fd;
            color: #1976d2;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Add Students</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Academic</li>
                    <li class="breadcrumb-item"><a href="exam-management.php">Exam Management</a></li>
                    <li class="breadcrumb-item active">Add Students (Post Exam Creation)</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="container mt-3">
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
                                                    <span id="loadExamBtnLoading" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                                </button>
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
                                    <div class="mb-3">
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
                                            <button type="button" id="filterBtn" class="btn btn-primary">
                                                <i class="bi bi-funnel-fill me-1"></i> Apply Filters
                                                <span id="filterBtnLoading" class="spinner-border spinner-border-sm d-none" role="status"></span>
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
                                        <div id="examIdContainer"></div>

                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="40"><input class="form-check-input" type="checkbox" id="selectAll"></th>
                                                        <th>Student ID</th>
                                                        <th>Student Name</th>
                                                        <th>Category</th>
                                                        <th>Class</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="studentTableBody"></tbody>
                                            </table>
                                        </div>

                                        <div class="text-end mt-3">
                                            <button type="submit" class="btn btn-success" id="addStudentsBtn" disabled>
                                                <i class="bi bi-plus-circle"></i> Add Selected Students
                                                <span id="loadingAdd" class="spinner-border spinner-border-sm d-none" role="status"></span>
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
            function showAlert(message, type = 'info') {
                alert(message); // Using simple JavaScript alert
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
                $('#selectedCount').text(`Selected: ${count}`);
                $('#addStudentsBtn').prop('disabled', count === 0);

                // Update "Select All" checkbox state
                const allChecked = $('.student-checkbox:not(:disabled)').length > 0 &&
                    $('.student-checkbox:not(:checked):not(:disabled)').length === 0;
                $('#selectAll').prop('checked', allChecked);
            }

            function toggleLoading(button, isLoading) {
                const loadingElement = button.find('.spinner-border');
                if (isLoading) {
                    loadingElement.removeClass('d-none');
                    button.prop('disabled', true);
                } else {
                    loadingElement.addClass('d-none');
                    button.prop('disabled', false);
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
                        $('#exam_type').empty().append('<option value="">Select exam type</option>');
                        data.forEach(type => {
                            $('#exam_type').append(`<option value="${type}">${type}</option>`);
                        });
                        $('#exam_type').prop('disabled', false);
                    })
                    .fail(function() {
                        showAlert('Error loading exam types');
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
                        showAlert('Error loading classes');
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
                        $('#subject').prop('disabled', false);
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
                        showAlert('Error loading subjects');
                    })
                    .always(function() {
                        toggleLoading($('#class'), false);
                    });
            });

            // Load exam details
            $('#loadExamBtn').click(function() {
                const examIds = $('#subject').val();
                if (!examIds || examIds.length === 0) {
                    showAlert('Please select at least one subject first');
                    return;
                }

                toggleLoading($(this), true);
                examDetailsData = [];
                $('#examDetailsSlider').empty();

                // Load all exam details
                Promise.all(examIds.map(examId =>
                        $.get('fetch_exam_details.php', {
                            exam_id: examId
                        })
                    ))
                    .then(responses => {
                        examDetailsData = responses.filter(r => r);
                        if (examDetailsData.length === 0) {
                            showAlert('No valid exam data could be loaded');
                            return;
                        }

                        // Initialize slider
                        currentExamIndex = 0;
                        updateExamSlider();

                        $('#studentFilterSection').show();
                        $('#selected_exam_id').val(examIds.join(','));
                        $('#examIdContainer').empty();
                        examIds.forEach(id => {
                            $('#examIdContainer').append(`<input type="hidden" name="exam_id[]" value="${id}">`);
                        });

                        $('#examInfoCard').show();
                        scrollToElement('#studentFilterSection');
                    })
                    .catch(error => {
                        showAlert('Error loading some exam details: ' + error);
                        console.error('Error:', error);
                    })
                    .finally(() => {
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
                    <h6 class="mb-2">${exam.academic_year} - ${exam.exam_type} - ${exam.class} - ${exam.subject}</h6>
                    <div class="small">
                        <div>Id- ${(exam.exam_id)}</div>
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
                    showAlert('Please select and load exam(s) first');
                    return;
                }

                toggleLoading($(this), true);

                const formData = {
                    exam_id: examIds,
                    class: $('#filter_class').val() || [],
                    category: $('#category').val() || [],
                    student_ids: $('#student_ids').val() || '',
                    excluded_ids: $('#excluded_ids').val() || ''
                };

                $.post('fetch_exam_students.php', formData)
                    .done(function(data) {
                        if (data.error) {
                            showAlert(data.error);
                            return;
                        }

                        if (data && data.length > 0) {
                            populateStudentTable(data, examIds.split(','));
                            $('#studentTableContainer').show();
                            scrollToElement('#studentTableContainer');
                        } else {
                            showAlert('No students found matching your criteria');
                            $('#studentTableContainer').hide();
                        }
                    })
                    .fail(function(xhr) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            showAlert(response.error || 'Error filtering students');
                        } catch (e) {
                            showAlert('Error filtering students: ' + xhr.statusText);
                        }
                        console.error('Error:', xhr.responseText);
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
                    $tbody.append('<tr><td colspan="6" class="text-center">No students found</td></tr>');
                    return;
                }

                const studentIds = students.map(s => s.student_id);

                $.post('fetch_student_exam_status.php', {
                        student_ids: studentIds.join(','),
                        exam_ids: examIds.join(',')
                    })
                    .done(function(response) {
                        if (response.error) {
                            showAlert(response.error);
                            return;
                        }

                        const enrollmentData = Array.isArray(response) ? response : [];

                        students.forEach(student => {
                            const enrolledExams = enrollmentData.filter(e =>
                                String(e.student_id).toLowerCase() === String(student.student_id).toLowerCase()
                            );

                            const isEnrolled = enrolledExams.length > 0;
                            const rowClass = isEnrolled ? 'table-warning' : '';

                            // Create tags for each exam the student is enrolled in
                            const examTags = enrolledExams.map(e =>
                                `<span class="exam-tag subject">${e.subject || 'Unknown'}</span>`
                            ).join('');

                            $tbody.append(`
                            <tr class="${rowClass}">
                                <td>
                                    <input type="checkbox" name="student_ids[]" 
                                           value="${student.student_id}" 
                                           class="form-check-input student-checkbox"
                                           ${isEnrolled ? 'disabled' : ''}>
                                </td>
                                <td>${student.student_id}</td>
                                <td>${student.studentname}</td>
                                <td>${student.category}</td>
                                <td>${student.class}</td>
                                <td>${examTags || '<span class="text-success">Available</span>'}</td>
                            </tr>
                        `);
                        });

                        $('#totalCount').text(`Total: ${students.length}`);
                        updateSelectedCount();
                    })
                    .fail(function(jqXHR) {
                        let errorMsg = 'Error checking enrollment status';
                        try {
                            const errResponse = JSON.parse(jqXHR.responseText);
                            if (errResponse.error) errorMsg += ': ' + errResponse.error;
                        } catch (e) {
                            errorMsg += ' (Invalid server response)';
                        }
                        showAlert(errorMsg);
                        $tbody.append(`<tr><td colspan="6" class="text-center text-danger">${errorMsg}</td></tr>`);
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
                    showAlert('Please select at least one student to add');
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
                        let message = response.message;
                        if (response.error_details && response.error_details.length > 0) {
                            message += "\n\nError details:\n" + response.error_details.join("\n");
                        }
                        showAlert(message);
                        $('#filterBtn').click(); // Refresh the student list
                    })
                    .fail(function(xhr) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            showAlert(response.message || 'Error adding students');
                        } catch (e) {
                            showAlert('Error adding students');
                        }
                    })
                    .always(function() {
                        toggleLoading($('#addStudentsBtn'), false);
                    });
            });

            // Row click handler
            $('#studentTableBody').on('click', 'tr', function(e) {
                if ($(e.target).is('a, button, input, select, textarea')) return;
                const checkbox = $(this).find('.student-checkbox');
                if (!checkbox.is(':disabled')) {
                    checkbox.prop('checked', !checkbox.is(':checked'));
                    updateSelectedCount();
                }
            });

            // Reset form when changing filters
            $('#academic_year, #exam_type, #class, #subject').change(function() {
                $('#studentFilterSection, #studentTableContainer').hide();
                $('#filterForm')[0].reset();
                $('#studentTableBody').empty();
                $('#totalCount').text('Total: 0');
                $('#selectedCount').text('Selected: 0');
                $('#addStudentsBtn').prop('disabled', true);
                $('#examInfoCard').hide();
                examDetailsData = [];
            });
        });
    </script>
</body>

</html>