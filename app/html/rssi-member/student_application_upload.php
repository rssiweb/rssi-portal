<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];

    if ($_POST['action'] === 'save_draft_row') {
        // Save draft row when user adds a row or selects document type
        $student_id = $_POST['student_id'];
        $student_name = $_POST['student_name'];
        $document_type = $_POST['document_type'] ?? '';
        $custom_document_name = $_POST['custom_document_name'] ?? '';

        // Get teacher info
        $teacher_id = $associatenumber;

        // Fetch teacher's fullname from rssimyaccount_members using associatenumber
        if (!empty($teacher_id)) {
            $user_query = "SELECT fullname FROM rssimyaccount_members WHERE associatenumber = $1";
            $user_result = pg_query_params($con, $user_query, array($teacher_id));
            if ($user_result && pg_num_rows($user_result) > 0) {
                $user_row = pg_fetch_assoc($user_result);
                $teacher_name = $user_row['fullname'];
            }
        }

        // Check if row already exists
        $application_id = $_POST['application_id'] ?? null;

        if ($application_id) {
            // Update existing draft
            $query = "UPDATE student_applications SET 
                      document_type = $1, 
                      custom_document_name = $2,
                      updated_at = CURRENT_TIMESTAMP
                      WHERE id = $3 AND status = 'draft'
                      RETURNING id";

            $result = pg_query_params($con, $query, array($document_type, $custom_document_name, $application_id));
        } else {
            // Insert new draft application record
            $query = "INSERT INTO student_applications 
                      (student_id, student_name, document_type, custom_document_name, 
                       status, uploaded_by) 
                      VALUES ($1, $2, $3, $4, 'draft', $5) 
                      RETURNING id";

            $result = pg_query_params($con, $query, array(
                $student_id,
                $student_name,
                $document_type,
                $custom_document_name,
                $teacher_id
            ));
        }

        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            $response['success'] = true;
            $response['application_id'] = $row['id'];
            $response['message'] = 'Draft saved successfully';
        } else {
            $response['message'] = 'Failed to save draft';
        }

        echo json_encode($response);
        exit;
    } elseif ($_POST['action'] === 'upload_file') {
        // Upload file to Google Drive and update application
        $application_id = $_POST['application_id'];

        if (isset($_FILES['application_file']) && $_FILES['application_file']['error'] === UPLOAD_ERR_OK) {
            $uploadedFile = $_FILES['application_file'];

            // First check if document type is selected
            $check_query = "SELECT document_type FROM student_applications WHERE id = $1";
            $check_result = pg_query_params($con, $check_query, array($application_id));
            $app_check = pg_fetch_assoc($check_result);

            if (!$app_check['document_type']) {
                $response['message'] = 'Please select document type first';
                echo json_encode($response);
                exit;
            }

            // Get application details for filename
            $query = "SELECT id, student_id, student_name FROM student_applications WHERE id = $1";
            $result = pg_query_params($con, $query, array($application_id));

            if ($result && pg_num_rows($result) > 0) {
                $app_data = pg_fetch_assoc($result);
                $filename = "application_" . $app_data['id'] . "_" . $app_data['student_id'];
                $parent = '1ZbKO3uttwNPAzjlWtvZNeqAhWxWjOXOq'; // Your Google Drive folder ID

                try {
                    $drive_link = uploadeToDrive($uploadedFile, $parent, $filename);

                    // Update application record with file info
                    $update_query = "UPDATE student_applications SET 
                                    file_path = $1, 
                                    original_filename = $2, 
                                    file_size = $3, 
                                    mime_type = $4,
                                    updated_at = CURRENT_TIMESTAMP
                                    WHERE id = $5";

                    $update_result = pg_query_params($con, $update_query, array(
                        $drive_link,
                        $uploadedFile['name'],
                        $uploadedFile['size'],
                        $uploadedFile['type'],
                        $application_id
                    ));

                    if ($update_result) {
                        $response['success'] = true;
                        $response['drive_link'] = $drive_link;
                        $response['message'] = 'File uploaded successfully';
                    } else {
                        $response['message'] = 'Failed to update database';
                    }
                } catch (Exception $e) {
                    $response['message'] = 'Failed to upload to Drive: ' . $e->getMessage();
                }
            } else {
                $response['message'] = 'Application not found';
            }
        } else {
            $response['message'] = 'No file uploaded or upload error';
        }

        echo json_encode($response);
        exit;
    } elseif ($_POST['action'] === 'delete_row') {
        // Delete draft application row
        $application_id = $_POST['application_id'];

        $query = "DELETE FROM student_applications WHERE id = $1 AND status = 'draft'";
        $result = pg_query_params($con, $query, array($application_id));

        if ($result && pg_affected_rows($result) > 0) {
            $response['success'] = true;
            $response['message'] = 'Application row deleted successfully';
        } else {
            $response['message'] = 'Failed to delete row or row already submitted';
        }

        echo json_encode($response);
        exit;
    } elseif ($_POST['action'] === 'submit_all') {
        // Submit all draft applications for the current session
        $application_ids = json_decode($_POST['application_ids'], true);

        if (empty($application_ids)) {
            $response['message'] = 'No applications to submit';
            echo json_encode($response);
            exit;
        }

        // Start transaction
        pg_query($con, "BEGIN");

        try {
            $submitted_count = 0;
            $submitted_numbers = [];

            foreach ($application_ids as $app_id) {
                // Check if file is uploaded
                $check_query = "SELECT file_path, document_type FROM student_applications WHERE id = $1 AND status = 'draft'";
                $check_result = pg_query_params($con, $check_query, array($app_id));
                $app_data = pg_fetch_assoc($check_result);

                if (!$app_data['file_path']) {
                    throw new Exception("Application ID $app_id has no file uploaded");
                }

                if (!$app_data['document_type']) {
                    throw new Exception("Application ID $app_id has no document type selected");
                }

                // Submit the application (trigger will generate application number)
                $update_query = "UPDATE student_applications SET 
                                status = 'submitted', 
                                submitted_at = CURRENT_TIMESTAMP,
                                updated_at = CURRENT_TIMESTAMP
                                WHERE id = $1 AND status = 'draft'
                                RETURNING application_number";

                $update_result = pg_query_params($con, $update_query, array($app_id));

                if ($update_result && pg_num_rows($update_result) > 0) {
                    $updated = pg_fetch_assoc($update_result);
                    $submitted_count++;
                    $submitted_numbers[] = $updated['application_number'];
                }
            }

            pg_query($con, "COMMIT");

            $response['success'] = true;
            $response['message'] = $submitted_count . ' applications submitted successfully';
            $response['application_numbers'] = $submitted_numbers;
        } catch (Exception $e) {
            pg_query($con, "ROLLBACK");
            $response['message'] = 'Failed to submit: ' . $e->getMessage();
        }

        echo json_encode($response);
        exit;
    } elseif ($_POST['action'] === 'fetch_submitted') {
        // Fetch submitted applications with filters
        $search_type = $_POST['search_type'] ?? 'student';
        $student_id = $_POST['student_id'] ?? '';
        $application_number = $_POST['application_number'] ?? '';
        $date_from = $_POST['date_from'] ?? '';
        $date_to = $_POST['date_to'] ?? '';
        $offset = $_POST['offset'] ?? 0;
        $limit = $_POST['limit'] ?? 20;

        $where_conditions = ["sa.status = 'submitted'"];
        $params = [];
        $param_count = 0;

        if ($search_type === 'application' && !empty($application_number)) {
            $param_count++;
            $where_conditions[] = "sa.application_number ILIKE $" . $param_count;
            $params[] = "%$application_number%";
        } else {
            if (!empty($student_id)) {
                $param_count++;
                $where_conditions[] = "sa.student_id ILIKE $" . $param_count;
                $params[] = "%$student_id%";
            }

            if (!empty($date_from)) {
                $param_count++;
                $where_conditions[] = "DATE(sa.submitted_at) >= $" . $param_count;
                $params[] = $date_from;
            }

            if (!empty($date_to)) {
                $param_count++;
                $where_conditions[] = "DATE(sa.submitted_at) <= $" . $param_count;
                $params[] = $date_to;
            }
        }

        $where_clause = "WHERE " . implode(" AND ", $where_conditions);

        // Get total count
        $count_query = "SELECT COUNT(*) FROM student_applications sa $where_clause";
        $count_result = pg_query_params($con, $count_query, $params);
        $total_records = pg_fetch_result($count_result, 0, 0);

        // Get paginated results with join to get teacher name
        $params[] = $limit;
        $params[] = $offset;

        $query = "SELECT sa.*, rm.fullname as uploaded_by_name 
              FROM student_applications sa
              LEFT JOIN rssimyaccount_members rm ON sa.uploaded_by = rm.associatenumber
              $where_clause 
              ORDER BY sa.submitted_at DESC 
              LIMIT $" . ($param_count + 1) . " OFFSET $" . ($param_count + 2);

        $result = pg_query_params($con, $query, $params);
        $applications = pg_fetch_all($result) ?: [];

        $response['success'] = true;
        $response['applications'] = $applications;
        $response['total'] = (int)$total_records;
        $response['offset'] = $offset;

        echo json_encode($response);
        exit;
    }
}

// Set default date range (April 1st to March 31st of current academic year)
$current_year = date('Y');
$current_month = date('m');
if ($current_month >= 4) {
    $default_date_from = $current_year . '-04-01';
    $default_date_to = ($current_year + 1) . '-03-31';
} else {
    $default_date_from = ($current_year - 1) . '-04-01';
    $default_date_to = $current_year . '-03-31';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>

    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        .application-row {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
            transition: all 0.3s ease;
        }

        .application-row:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .application-number-badge {
            font-family: monospace;
            font-size: 14px;
            font-weight: bold;
            color: #0d6efd;
        }

        .delete-row {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #dc3545;
            cursor: pointer;
            font-size: 18px;
        }

        .delete-row:hover {
            color: #bb2d3b;
        }

        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-area:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }

        .uploaded-file-info {
            background-color: #d1e7dd;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
        }

        .submitted-card {
            transition: all 0.3s ease;
            border-left: 4px solid #0d6efd;
        }

        .submitted-card:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .current-student-badge {
            background-color: #0d6efd;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 15px;
        }

        .loader {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .student-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .student-header {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .filter-option {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
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
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-12">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-4" id="appTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button" role="tab">
                                <i class="bi bi-cloud-upload"></i> Upload Applications
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="submitted-tab" data-bs-toggle="tab" data-bs-target="#submitted" type="button" role="tab">
                                <i class="bi bi-archive"></i> Submitted Applications
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Upload Tab -->
                        <div class="tab-pane fade show active" id="upload" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="alert alert-info mt-3">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>How to use:</strong> Select a student, then add files for that student.
                                        You can add multiple files for the same student, or add another student to upload files for multiple students at once.
                                    </div>

                                    <div id="studentsContainer">
                                        <!-- Dynamic student sections will be added here -->
                                    </div>

                                    <div class="text-center mt-4 mb-3">
                                        <button type="button" class="btn btn-outline-primary btn-lg" id="addAnotherStudentBtn">
                                            <i class="bi bi-person-plus"></i> Add Another Student
                                        </button>
                                        <button type="button" class="btn btn-success btn-lg ms-3" id="submitAllBtn">
                                            <i class="bi bi-check2-circle"></i> Submit All Applications
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submitted Applications Tab -->
                        <div class="tab-pane fade" id="submitted" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <div class="filter-section">
                                        <div class="filter-option">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="searchByAppNumberCheckbox">
                                                <label class="form-check-label" for="searchByAppNumberCheckbox">
                                                    <i class="bi bi-search"></i> Search by Application Number
                                                </label>
                                                <small class="text-muted d-block">Enable this to search by application number instead of student ID</small>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-4" id="studentSearchDiv">
                                                <label class="form-label"><i class="bi bi-person"></i> Student ID/Name</label>
                                                <select class="form-select" id="filterStudentSelect">
                                                    <option value="">All Students</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4" id="appNumberSearchDiv" style="display: none;">
                                                <label class="form-label"><i class="bi bi-hash"></i> Application Number</label>
                                                <input type="text" class="form-control" id="filterApplicationNumber" placeholder="Enter application number">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label"><i class="bi bi-calendar"></i> From Date</label>
                                                <input type="date" class="form-control" id="filterDateFrom" value="<?php echo $default_date_from; ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label"><i class="bi bi-calendar"></i> To Date</label>
                                                <input type="date" class="form-control" id="filterDateTo" value="<?php echo $default_date_to; ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">&nbsp;</label>
                                                <button type="button" class="btn btn-primary d-block w-100" id="applyFiltersBtn">
                                                    <i class="bi bi-search"></i> Apply Filters
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="submittedApplicationsList">
                                        <div class="text-center py-4">
                                            <div class="loader"></div>
                                            <p class="mt-2">Loading applications...</p>
                                        </div>
                                    </div>

                                    <div class="text-center mt-3" id="loadMoreContainer" style="display: none;">
                                        <button type="button" class="btn btn-outline-primary" id="loadMoreBtn">Load More</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script src="../assets_new/js/main.js"></script>
    <script>
        let studentCounter = 0;
        let allApplicationRows = []; // Store all application row objects
        let submittedOffset = 0;
        let submittedLimit = 20;
        let hasMoreSubmitted = true;
        let currentSearchType = 'student'; // 'student' or 'application'

        $(document).ready(function() {
            // Function to get URL parameter
            function getUrlParameter(name) {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(name);
            }

            // Function to update URL parameter without reload
            function updateUrlParameter(param, value) {
                const urlParams = new URLSearchParams(window.location.search);
                if (value) {
                    urlParams.set(param, value);
                } else {
                    urlParams.delete(param);
                }
                const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                window.history.pushState({}, '', newUrl);
            }

            // Initialize Select2 for student filter
            $('#filterStudentSelect').select2({
                ajax: {
                    url: 'fetch_students.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            isActive: true
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                placeholder: 'Search by student ID or name',
                allowClear: true,
                theme: 'bootstrap-5'
            });

            // Toggle search type
            $('#searchByAppNumberCheckbox').change(function() {
                if ($(this).is(':checked')) {
                    currentSearchType = 'application';
                    $('#studentSearchDiv').hide();
                    $('#appNumberSearchDiv').show();
                    $('#filterStudentSelect').val('').trigger('change');
                    // Disable date filters
                    $('#filterDateFrom, #filterDateTo').prop('disabled', true);
                } else {
                    currentSearchType = 'student';
                    $('#studentSearchDiv').show();
                    $('#appNumberSearchDiv').hide();
                    $('#filterApplicationNumber').val('');
                    // Enable date filters
                    $('#filterDateFrom, #filterDateTo').prop('disabled', false);
                }
                // Reset and apply filters
                submittedOffset = 0;
                hasMoreSubmitted = true;
                $('#submittedApplicationsList').empty();
                fetchSubmittedApplications();
            });

            // Check URL parameter for active tab
            const activeTab = getUrlParameter('tab');

            // Activate the appropriate tab based on URL parameter
            if (activeTab === 'submitted') {
                $('#submitted-tab').tab('show');
                updateUrlParameter('tab', 'submitted');
            } else {
                $('#upload-tab').tab('show');
                updateUrlParameter('tab', 'upload');
            }

            // When tabs are clicked, update URL
            $('#upload-tab').on('click', function() {
                updateUrlParameter('tab', 'upload');
            });

            $('#submitted-tab').on('click', function() {
                updateUrlParameter('tab', 'submitted');
            });

            // Add first student section
            addStudentSection();

            // Add another student button
            $('#addAnotherStudentBtn').click(function() {
                addStudentSection();
            });

            // Submit all button
            $('#submitAllBtn').click(function() {
                submitAllApplications();
            });

            // Apply filters button
            $('#applyFiltersBtn').click(function() {
                submittedOffset = 0;
                hasMoreSubmitted = true;
                $('#submittedApplicationsList').empty();
                fetchSubmittedApplications();
            });

            // Load more button
            $('#loadMoreBtn').click(function() {
                if (hasMoreSubmitted) {
                    fetchSubmittedApplications();
                }
            });

            // Fetch initial submitted applications
            fetchSubmittedApplications();
        });

        function addStudentSection() {
            studentCounter++;
            const sectionId = `student_section_${studentCounter}_${Date.now()}`;

            const sectionHtml = `
            <div id="${sectionId}" class="student-section">
                <div class="student-header">
                    <div class="row">
                        <div class="col-md-8">
                            <h5><i class="bi bi-person-badge"></i> Student ${studentCounter}</h5>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-sm btn-danger" onclick="removeStudentSection('${sectionId}')">
                                <i class="bi bi-trash"></i> Remove Student
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Select Student *</label>
                        <select class="form-select student-select" data-section="${sectionId}" required>
                            <option value="">Search for student...</option>
                        </select>
                        <input type="hidden" class="student-id" value="">
                        <input type="hidden" class="student-name" value="">
                    </div>
                </div>
                
                <div id="${sectionId}_files_container">
                    <!-- Application rows will be added here -->
                </div>
                
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-sm btn-primary" onclick="addFileRow('${sectionId}')">
                        <i class="bi bi-plus-circle"></i> Add File
                    </button>
                </div>
            </div>
        `;

            $('#studentsContainer').append(sectionHtml);

            // Initialize Select2 for this student section
            initializeStudentSelect(sectionId);

            // Add first file row
            addFileRow(sectionId);
        }

        function initializeStudentSelect(sectionId) {
            $(`#${sectionId} .student-select`).select2({
                ajax: {
                    url: 'fetch_students.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            isActive: true
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                placeholder: 'Search by student ID or name',
                allowClear: true,
                theme: 'bootstrap-5',
                dropdownParent: $(`#${sectionId}`)
            }).on('change', function() {
                const selectedData = $(this).select2('data')[0];
                const studentId = $(this).val();
                const studentName = selectedData ? selectedData.text.split(' - ')[0] : '';

                $(`#${sectionId} .student-id`).val(studentId);
                $(`#${sectionId} .student-name`).val(studentName);

                // Update all existing draft rows for this student
                updateStudentForAllRows(sectionId, studentId, studentName);
            });
        }

        function addFileRow(sectionId) {
            const rowId = `file_row_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
            const containerId = `${sectionId}_files_container`;

            const rowHtml = `
            <div id="${rowId}" class="application-row">
                <i class="bi bi-trash delete-row" onclick="deleteFileRow('${rowId}')"></i>
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <small class="text-muted">
                            <i class="bi bi-hash"></i> Application #: <span class="application-number-badge">Pending</span>
                        </small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Document Type *</label>
                        <select class="form-select doc-type" data-row="${rowId}" required>
                            <option value="">Select Document Type</option>
                            <option value="Form 1A">Form 1A</option>
                            <option value="Form 1B">Form 1B</option>
                            <option value="Retention Form">Retention Form</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3 custom-doc-name" style="display: none;">
                        <label class="form-label">Document Name *</label>
                        <input type="text" class="form-control" placeholder="Enter document name">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="file-upload-area" onclick="triggerFileUpload('${rowId}')">
                            <i class="bi bi-cloud-upload fs-1"></i>
                            <p class="mb-0">Click to upload file</p>
                            <small class="text-muted">PDF, JPG, PNG (Max 10MB)</small>
                        </div>
                        <input type="file" id="file_${rowId}" class="d-none" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="uploaded-file-info" style="display: none;">
                            <i class="bi bi-check-circle-fill text-success"></i> 
                            <span class="file-name"></span>
                            <a href="#" class="file-link ms-2" target="_blank">View</a>
                        </div>
                        <input type="hidden" class="application-id" value="">
                    </div>
                </div>
            </div>
        `;

            $(`#${containerId}`).append(rowHtml);

            // Store row info
            allApplicationRows.push({
                rowId: rowId,
                sectionId: sectionId,
                applicationId: null,
                studentId: $(`#${sectionId} .student-id`).val(),
                studentName: $(`#${sectionId} .student-name`).val()
            });

            // Handle document type change - save immediately
            $(`#${rowId} .doc-type`).change(function() {
                const customDiv = $(`#${rowId} .custom-doc-name`);
                const selectedValue = $(this).val();

                if (selectedValue === 'Other') {
                    customDiv.show();
                    customDiv.find('input').prop('required', true);
                } else {
                    customDiv.hide();
                    customDiv.find('input').prop('required', false);
                    // Save immediately when document type is selected
                    saveOrUpdateDraftRow(rowId, sectionId);
                }
            });

            // Handle custom document name input - save on blur
            $(`#${rowId} .custom-doc-name input`).on('blur', function() {
                if ($(`#${rowId} .doc-type`).val() === 'Other') {
                    saveOrUpdateDraftRow(rowId, sectionId);
                }
            });

            // Handle file upload - direct without delay
            $(`#file_${rowId}`).off('change').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    uploadFileForRow(rowId, file);
                }
            });

            // If student is already selected, we're ready for document type selection
            const studentId = $(`#${sectionId} .student-id`).val();
            if (studentId) {
                console.log('Student selected, waiting for document type');
            }
        }

        function triggerFileUpload(rowId) {
            // Check if document type is selected before allowing file upload
            const docType = $(`#${rowId} .doc-type`).val();

            if (!docType) {
                showAlert('Please select document type first', 'warning');
                return;
            }

            // Check if student is selected
            const sectionId = getSectionIdFromRow(rowId);
            const studentId = $(`#${sectionId} .student-id`).val();

            if (!studentId) {
                showAlert('Please select a student first', 'warning');
                return;
            }

            // Get or create application ID
            const appId = $(`#${rowId} .application-id`).val();

            if (appId) {
                // Application exists, just open file dialog
                $(`#file_${rowId}`).click();
            } else {
                // Need to create draft first - do it synchronously with a loading indicator
                const uploadArea = $(`#${rowId} .file-upload-area`);
                const originalHtml = uploadArea.html();
                uploadArea.html('<i class="bi bi-arrow-repeat spin"></i><p>Preparing...</p>');

                // Save draft first, then open file dialog
                saveOrUpdateDraftRow(rowId, sectionId, function(success) {
                    uploadArea.html(originalHtml);
                    if (success) {
                        // Small delay to ensure database is updated, then open file dialog
                        setTimeout(function() {
                            $(`#file_${rowId}`).click();
                        }, 100);
                    }
                });
            }
        }

        function getSectionIdFromRow(rowId) {
            const row = $(`#${rowId}`);
            const container = row.closest('.student-section').find('div[id$="_files_container"]');
            const sectionId = container.attr('id').replace('_files_container', '');
            return sectionId;
        }

        function saveOrUpdateDraftRow(rowId, sectionId, callback) {
            const studentId = $(`#${sectionId} .student-id`).val();
            const studentName = $(`#${sectionId} .student-name`).val();
            const docType = $(`#${rowId} .doc-type`).val();
            const existingAppId = $(`#${rowId} .application-id`).val();

            if (!studentId) {
                showAlert('Please select a student first', 'warning');
                if (callback) callback(false);
                return;
            }

            if (!docType) {
                showAlert('Please select document type', 'warning');
                if (callback) callback(false);
                return;
            }

            let customDocName = '';
            if (docType === 'Other') {
                customDocName = $(`#${rowId} .custom-doc-name input`).val();
                if (!customDocName) {
                    showAlert('Please enter document name', 'warning');
                    if (callback) callback(false);
                    return;
                }
            }

            $.ajax({
                url: '',
                type: 'POST',
                data: {
                    action: 'save_draft_row',
                    application_id: existingAppId,
                    student_id: studentId,
                    student_name: studentName,
                    document_type: docType,
                    custom_document_name: customDocName
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $(`#${rowId} .application-id`).val(response.application_id);

                        // Update the stored row info
                        const rowIndex = allApplicationRows.findIndex(r => r.rowId === rowId);
                        if (rowIndex !== -1) {
                            allApplicationRows[rowIndex].applicationId = response.application_id;
                        }

                        $(`#${rowId}`).css('border-color', '#28a745');

                        if (callback) callback(true);
                    } else {
                        showAlert('Error: ' + response.message, 'danger');
                        if (callback) callback(false);
                    }
                },
                error: function() {
                    showAlert('Error saving draft', 'danger');
                    if (callback) callback(false);
                }
            });
        }

        function uploadFileForRow(rowId, file) {
            const appId = $(`#${rowId} .application-id`).val();

            if (!appId) {
                showAlert('Please save the draft first', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'upload_file');
            formData.append('application_id', appId);
            formData.append('application_file', file);

            const uploadArea = $(`#${rowId} .file-upload-area`);
            const originalHtml = uploadArea.html();
            uploadArea.html('<i class="bi bi-arrow-repeat spin"></i><p>Uploading...</p>');

            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const fileInfo = $(`#${rowId} .uploaded-file-info`);
                        fileInfo.find('.file-name').text(file.name);
                        fileInfo.find('.file-link').attr('href', response.drive_link);
                        fileInfo.show();
                        uploadArea.hide();
                        $(`#${rowId}`).css('border-color', '#28a745');
                        showAlert('File uploaded successfully!', 'success');
                    } else {
                        uploadArea.html(originalHtml);
                        showAlert('Upload failed: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    uploadArea.html(originalHtml);
                    showAlert('Upload failed. Please try again.', 'danger');
                }
            });
        }

        function updateStudentForAllRows(sectionId, studentId, studentName) {
            $(`#${sectionId} .application-row`).each(function() {
                const rowId = $(this).attr('id');
                const appId = $(this).find('.application-id').val();

                if (appId) {
                    // Update existing draft in database
                    $.ajax({
                        url: '',
                        type: 'POST',
                        data: {
                            action: 'save_draft_row',
                            application_id: appId,
                            student_id: studentId,
                            student_name: studentName,
                            document_type: $(this).find('.doc-type').val(),
                            custom_document_name: $(this).find('.custom-doc-name input').val() || ''
                        },
                        dataType: 'json'
                    });
                }
            });
        }

        function deleteFileRow(rowId) {
            const appId = $(`#${rowId} .application-id`).val();

            if (appId) {
                if (confirm('Are you sure you want to delete this application?')) {
                    $.ajax({
                        url: '',
                        type: 'POST',
                        data: {
                            action: 'delete_row',
                            application_id: appId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $(`#${rowId}`).remove();
                                // Remove from array
                                const rowIndex = allApplicationRows.findIndex(r => r.rowId === rowId);
                                if (rowIndex !== -1) {
                                    allApplicationRows.splice(rowIndex, 1);
                                }
                                showAlert('Application deleted', 'success');
                            } else {
                                showAlert('Error: ' + response.message, 'danger');
                            }
                        }
                    });
                }
            } else {
                $(`#${rowId}`).remove();
                // Remove from array
                const rowIndex = allApplicationRows.findIndex(r => r.rowId === rowId);
                if (rowIndex !== -1) {
                    allApplicationRows.splice(rowIndex, 1);
                }
            }
        }

        function removeStudentSection(sectionId) {
            if (confirm('Remove this student and all associated applications?')) {
                // Delete all draft applications for this student
                $(`#${sectionId} .application-row`).each(function() {
                    const appId = $(this).find('.application-id').val();
                    if (appId) {
                        $.ajax({
                            url: '',
                            type: 'POST',
                            data: {
                                action: 'delete_row',
                                application_id: appId
                            },
                            async: false
                        });
                    }
                });

                $(`#${sectionId}`).remove();

                // Remove from array
                allApplicationRows = allApplicationRows.filter(r => r.sectionId !== sectionId);

                showAlert('Student section removed', 'success');
            }
        }

        function submitAllApplications() {
            // Collect all application IDs that have files uploaded
            const applicationIds = [];
            let hasMissingFiles = false;
            let hasMissingDocType = false;

            $('.application-row').each(function() {
                const appId = $(this).find('.application-id').val();
                const hasFile = $(this).find('.uploaded-file-info').css('display') !== 'none';
                const docType = $(this).find('.doc-type').val();

                if (!docType) {
                    hasMissingDocType = true;
                    $(this).css('border-color', '#dc3545');
                } else if (appId && hasFile) {
                    applicationIds.push(appId);
                    $(this).css('border-color', '#28a745');
                } else if (appId && !hasFile) {
                    hasMissingFiles = true;
                    $(this).css('border-color', '#ffc107');
                }
            });

            if (hasMissingDocType) {
                showAlert('Please select document type for all rows', 'warning');
                return;
            }

            if (hasMissingFiles) {
                showAlert('Please upload files for all rows before submitting', 'warning');
                return;
            }

            if (applicationIds.length === 0) {
                showAlert('No applications to submit', 'warning');
                return;
            }

            if (confirm(`Submit ${applicationIds.length} application(s)? This action cannot be undone.`)) {
                $('#submitAllBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Submitting...');

                $.ajax({
                    url: '',
                    type: 'POST',
                    data: {
                        action: 'submit_all',
                        application_ids: JSON.stringify(applicationIds)
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message, 'success');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            showAlert('Error: ' + response.message, 'danger');
                            $('#submitAllBtn').prop('disabled', false).html('<i class="bi bi-check2-circle"></i> Submit All Applications');
                        }
                    },
                    error: function() {
                        showAlert('Failed to submit. Please try again.', 'danger');
                        $('#submitAllBtn').prop('disabled', false).html('<i class="bi bi-check2-circle"></i> Submit All Applications');
                    }
                });
            }
        }

        function fetchSubmittedApplications() {
            let searchType = currentSearchType;
            let studentId = '';
            let applicationNumber = '';

            if (searchType === 'application') {
                applicationNumber = $('#filterApplicationNumber').val();
                // When searching by application number, don't send date filters
                var dateFrom = '';
                var dateTo = '';
            } else {
                studentId = $('#filterStudentSelect').val() || '';
                var dateFrom = $('#filterDateFrom').val();
                var dateTo = $('#filterDateTo').val();
            }

            const filters = {
                action: 'fetch_submitted',
                search_type: searchType,
                student_id: studentId,
                application_number: applicationNumber,
                date_from: dateFrom,
                date_to: dateTo,
                offset: submittedOffset,
                limit: submittedLimit
            };

            if (submittedOffset === 0) {
                $('#submittedApplicationsList').html('<div class="text-center py-4"><div class="loader"></div><p class="mt-2">Loading applications...</p></div>');
            }

            $.ajax({
                url: '',
                type: 'POST',
                data: filters,
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.applications) {
                        if (submittedOffset === 0) {
                            $('#submittedApplicationsList').empty();
                        }

                        if (response.applications.length === 0 && submittedOffset === 0) {
                            $('#submittedApplicationsList').html('<div class="text-center py-4"><i class="bi bi-inbox fs-1"></i><p>No applications found</p></div>');
                            hasMoreSubmitted = false;
                            $('#loadMoreContainer').hide();
                            return;
                        }

                        response.applications.forEach(function(app) {
                            const appHtml = `
                            <div class="submitted-card border rounded p-3 mb-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <small class="text-muted">Application Number</small><br>
                                        <strong class="text-primary">${escapeHtml(app.application_number)}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Student ID</small><br>
                                        <strong>${escapeHtml(app.student_id)}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Student Name</small><br>
                                        <strong>${escapeHtml(app.student_name)}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Submitted Date</small><br>
                                        <strong>${new Date(app.submitted_at).toLocaleString()}</strong>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <small class="text-muted">Document Type</small><br>
                                        <span class="badge bg-info">${escapeHtml(app.document_type)}</span>
                                        ${app.custom_document_name ? '<br><small>' + escapeHtml(app.custom_document_name) + '</small>' : ''}
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Uploaded By</small><br>
                                        ${escapeHtml(app.uploaded_by_name)}
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">Document</small><br>
                                        ${app.file_path ? `<a href="${app.file_path}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View Document</a>` : 'No file'}
                                    </div>
                                </div>
                            </div>
                        `;
                            $('#submittedApplicationsList').append(appHtml);
                        });

                        submittedOffset += response.applications.length;
                        hasMoreSubmitted = response.applications.length === submittedLimit;

                        if (hasMoreSubmitted) {
                            $('#loadMoreContainer').show();
                        } else {
                            $('#loadMoreContainer').hide();
                        }
                    } else {
                        $('#submittedApplicationsList').html('<div class="text-center py-4 text-danger">Failed to load applications</div>');
                    }
                },
                error: function() {
                    $('#submittedApplicationsList').html('<div class="text-center py-4 text-danger">Error loading applications</div>');
                }
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showAlert(message, type) {
            const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999; min-width: 300px; z-index: 10000;" role="alert">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
            $('body').append(alertHtml);
            setTimeout(() => {
                $('.alert').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);
        }
    </script>
</body>

</html>