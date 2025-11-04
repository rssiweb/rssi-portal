<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Check user role and permissions
if ($role != 'Admin' && $role != 'SuperAdmin') {
    echo "<script>alert('You are not authorized to access this page.'); window.location.href='home.php';</script>";
    exit;
}

// Handle AJAX request for record fetching
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['formType']) && $_POST['formType'] === 'ajax_fetch') {
    $offset = $_POST['offset'] ?? 0;
    $limit = $_POST['limit'] ?? 20;

    // Filter parameters
    $status_filter = $_POST['status_filter'] ?? 'active';
    $education_filter = $_POST['education_filter'] ?? '';
    $search_term = $_POST['search_term'] ?? '';
    $search_mode = filter_var($_POST['search_mode'] ?? false, FILTER_VALIDATE_BOOLEAN);

    // Build WHERE clause based on search mode
    $where_conditions = [];
    $params = [];
    $param_count = 0;

    if ($search_mode) {
        // Search mode - only use search term
        if (!empty($search_term)) {
            $param_count++;
            $where_conditions[] = "(name ILIKE $" . $param_count . " OR js.contact ILIKE $" . $param_count . " OR skills ILIKE $" . $param_count . " OR preferences ILIKE $" . $param_count . ")";
            $params[] = "%$search_term%";
        }
    } else {
        // Filter mode - use status and education filters
        if ($status_filter === 'active') {
            $where_conditions[] = "status = 'Active'";
        } elseif ($status_filter === 'inactive') {
            $where_conditions[] = "status = 'Inactive'";
        } elseif ($status_filter === 'all') {
            // Show all statuses - no condition needed
        }

        if (!empty($education_filter)) {
            $param_count++;
            $where_conditions[] = "education = $" . $param_count;
            $params[] = $education_filter;
        }

        // Also allow search in filter mode
        if (!empty($search_term)) {
            $param_count++;
            $where_conditions[] = "(name ILIKE $" . $param_count . " OR js.contact ILIKE $" . $param_count . " OR skills ILIKE $" . $param_count . " OR preferences ILIKE $" . $param_count . ")";
            $params[] = "%$search_term%";
        }
    }

    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // Get total count for filtered results
    $count_query = "SELECT COUNT(*) FROM job_seeker_data js LEFT JOIN survey_data s ON js.family_id = s.family_id $where_clause";
    $count_result = pg_query_params($con, $count_query, $params);
    $total_filtered_records = pg_fetch_result($count_result, 0, 0);

    // Get job seekers data with pagination
    $params[] = $limit;
    $params[] = $offset;

    $query = "SELECT js.*, s.parent_name, s.address, s.surveyor_id 
              FROM job_seeker_data js 
              LEFT JOIN survey_data s ON js.family_id = s.family_id 
              $where_clause 
              ORDER BY js.created_at DESC 
              LIMIT $" . ($param_count + 1) . " OFFSET $" . ($param_count + 2);

    $result = pg_query_params($con, $query, $params);

    if ($result) {
        $records = pg_fetch_all($result) ?: [];
        $isLastBatch = !$limit || count($records) < $limit;

        echo json_encode([
            "success" => true,
            "records" => $records,
            "isLastBatch" => $isLastBatch,
            "totalFiltered" => (int)$total_filtered_records,
            "currentOffset" => $offset,
            "currentLimit" => $limit
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Error fetching data.",
        ]);
    }
    exit;
}

// Handle actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $id = $_POST['id'];
                $status = $_POST['status'];
                $query = "UPDATE job_seeker_data SET status = $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2";
                $result = pg_query_params($con, $query, array($status, $id));
                if ($result) {
                    $_SESSION['success_message'] = "Status updated successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to update status!";
                }
                break;

            case 'add_remark':
                $id = $_POST['id'];
                $remark = pg_escape_string($con, $_POST['remark']);
                $timestamp = date('Y-m-d H:i:s');
                $formatted_remark = "[$timestamp] " . $remark . "\n";

                $query = "UPDATE job_seeker_data 
                        SET remarks = COALESCE(remarks, '') || $1::text,
                            updated_at = CURRENT_TIMESTAMP 
                        WHERE id = $2";
                $result = pg_query_params($con, $query, array($formatted_remark, $id));
                if ($result) {
                    $_SESSION['success_message'] = "Remark added successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to add remark!";
                }
                break;

            case 'update_job_seeker':
                $id = $_POST['id'];
                $name = pg_escape_string($con, $_POST['name']);
                $age = intval($_POST['age']);
                $contact = pg_escape_string($con, $_POST['contact']);
                $education = pg_escape_string($con, $_POST['education']);
                $skills = pg_escape_string($con, $_POST['skills']);
                $preferences = pg_escape_string($con, $_POST['preferences']);

                $query = "UPDATE job_seeker_data SET 
                         name = $1, 
                         age = $2, 
                         contact = $3, 
                         education = $4, 
                         skills = $5, 
                         preferences = $6, 
                         updated_at = CURRENT_TIMESTAMP 
                         WHERE id = $7";

                $result = pg_query_params($con, $query, array(
                    $name,
                    $age,
                    $contact,
                    $education,
                    $skills,
                    $preferences,
                    $id
                ));

                if ($result) {
                    $_SESSION['success_message'] = "Job seeker details updated successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to update job seeker details!";
                }
                break;
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get total records count for initial load (without filters)
$count_query = "SELECT COUNT(*) FROM job_seeker_data WHERE status = 'Active'";
$count_result = pg_query($con, $count_query);
$total_records = pg_fetch_result($count_result, 0, 0);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Job Seekers Management</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <style>
        .remarks-content {
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }

        .job-seeker-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .detail-row {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
        }

        .detail-value {
            color: #212529;
        }

        /* Loader styling */
        #progressLoader .loader {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }

        .form-control:disabled {
            background-color: #e9ecef;
            opacity: 0.6;
        }

        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .has-remarks .dropdown-toggle {
            color: #0d6efd;
            font-weight: bold;
        }

        #recordsInfo {
            font-weight: 600;
            color: #495057;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Job Seekers Management</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Survey</a></li>
                    <li class="breadcrumb-item active">Job Seekers</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body mt-3">
                            <!-- Success/Error Messages -->
                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo $_SESSION['success_message']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['success_message']); ?>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $_SESSION['error_message']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['error_message']); ?>
                            <?php endif; ?>

                            <!-- Add progress loader HTML -->
                            <div id="pageOverlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 9999;">
                                <div id="progressLoader" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                    <p>Loading, please wait...</p>
                                    <div class="loader"></div>
                                </div>
                            </div>

                            <!-- Filters -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <form id="filterForm" class="row g-3" onsubmit="handleFormSubmit(event)">
                                        <div class="col-md-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="all">All</option>
                                                <option value="active" selected>Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="education" class="form-label">Education</label>
                                            <select class="form-select" id="education" name="education">
                                                <option value="">All Education</option>
                                                <!-- Basic Literacy Levels -->
                                                <option value="Illiterate">Illiterate (Cannot read or write)</option>

                                                <option value="Can Read Hindi Only">Can Read – Hindi Only</option>
                                                <option value="Can Read English Only">Can Read – English Only</option>
                                                <option value="Can Read Both">Can Read – Hindi & English</option>

                                                <option value="Can Write Hindi Only">Can Write – Hindi Only</option>
                                                <option value="Can Write English Only">Can Write – English Only</option>
                                                <option value="Can Write Both">Can Write – Hindi & English</option>

                                                <option value="Can Read & Write Hindi Only">Can Read & Write – Hindi Only</option>
                                                <option value="Can Read & Write English Only">Can Read & Write – English Only</option>
                                                <option value="Can Read & Write Both">Can Read & Write – Hindi & English</option>

                                                <!-- School Level -->
                                                <option value="Below 5th">Below 5th Standard</option>
                                                <option value="5th Pass">5th Pass</option>
                                                <option value="8th Pass">8th Pass</option>
                                                <option value="Below 10th">Below 10th</option>

                                                <option value="10th Pass">10th Pass</option>
                                                <option value="12th Pass">12th Pass</option>

                                                <!-- Higher Education -->
                                                <option value="Diploma">Diploma</option>
                                                <option value="Graduate">Graduate</option>
                                                <option value="Post Graduate">Post Graduate</option>
                                                <option value="Doctorate">Doctorate</option>

                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="search" class="form-label">Search</label>
                                            <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, contact, skills...">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="button" id="applyFilters" class="btn btn-primary w-100">Apply Filters</button>
                                        </div>
                                        <div class="row mt-3 mb-3">
                                            <div class="col-md-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="toggleSearchMode">
                                                    <label class="form-check-label" for="toggleSearchMode">Enable Search Mode</label>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="recordsPerLoad" class="form-label me-2">Records Per Load:</label>
                                    <select id="recordsPerLoad" class="form-select d-inline-block" style="width: auto;">
                                        <option value="20" selected>20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="200">200</option>
                                    </select>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button class="btn btn-success" onclick="exportToExcel()">
                                        <i class="bi bi-file-earmark-excel"></i> Export to Excel
                                    </button>
                                </div>
                            </div>

                            <!-- Job Seekers Table -->
                            <div class="table-responsive">
                                <table id="jobSeekersTable" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Id</th>
                                            <th>Name</th>
                                            <th>Age</th>
                                            <th>Contact</th>
                                            <th>Education</th>
                                            <th>Skills</th>
                                            <th>Preferences</th>
                                            <th>Surveyor ID</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="data-tbody">
                                        <!-- Data will be loaded here dynamically -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-center mt-3">
                                <button id="loadMoreBtn" class="btn btn-primary">Load More</button>
                                <div id="loadingIndicator" class="mt-2" style="display: none;">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2">Loading more records...</span>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-12 text-center">
                                    <p id="recordsInfo">Loading records...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Add Remark Modal -->
    <div class="modal fade" id="addRemarkModal" tabindex="-1" aria-labelledby="addRemarkModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRemarkModalLabel">Add Remark</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="addRemarkForm" onsubmit="handleAddRemarkSubmit(event)">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_remark">
                        <input type="hidden" name="id" id="addRemarkJobSeekerId">
                        <div class="mb-3">
                            <label for="remark" class="form-label">Remark</label>
                            <textarea class="form-control" id="remark" name="remark" rows="4" required placeholder="Enter your remark here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="addRemarkSubmitBtn">
                            <span class="submit-text">Save Remark</span>
                            <span class="loading-spinner" style="display: none;">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Remarks Modal -->
    <div class="modal fade" id="viewRemarksModal" tabindex="-1" aria-labelledby="viewRemarksModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewRemarksModalLabel">Job Seeker Details & Remarks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Job Seeker Details -->
                    <div class="job-seeker-details">
                        <h6 class="mb-3">Job Seeker Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-row">
                                    <span class="detail-label">Name:</span>
                                    <span class="detail-value" id="viewName"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Age:</span>
                                    <span class="detail-value" id="viewAge"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Contact:</span>
                                    <span class="detail-value" id="viewContact"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Education:</span>
                                    <span class="detail-value" id="viewEducation"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-row">
                                    <span class="detail-label">Skills:</span>
                                    <span class="detail-value" id="viewSkills"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Preferences:</span>
                                    <span class="detail-value" id="viewPreferences"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value" id="viewStatus"></span>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="detail-row">
                                    <span class="detail-label">Address:</span>
                                    <span class="detail-value" id="viewAddress"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks Section -->
                    <div class="mt-4">
                        <h6 class="mb-3">Remarks History</h6>
                        <div class="remarks-content" id="viewRemarksContent">
                            <!-- Remarks will be loaded here -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="addNewRemark()">
                        <i class="bi bi-plus-circle"></i> Add New Remark
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Job Seeker Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="editForm" onsubmit="handleEditSubmit(event)">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_job_seeker">
                        <input type="hidden" name="id" id="editJobSeekerId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editName" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editName" name="name" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="editAge" class="form-label">Age <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="editAge" name="age" min="18" max="65" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="editContact" class="form-label">Contact <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="editContact" name="contact" pattern="[0-9]{10}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editEducation" class="form-label">Education <span class="text-danger">*</span></label>
                                <select class="form-select" id="editEducation" name="education" required>
                                    <option value="">Select qualification</option>
                                    <!-- Basic Literacy Levels -->
                                    <option value="Illiterate">Illiterate (Cannot read or write)</option>

                                    <option value="Can Read Hindi Only">Can Read – Hindi Only</option>
                                    <option value="Can Read English Only">Can Read – English Only</option>
                                    <option value="Can Read Both">Can Read – Hindi & English</option>

                                    <option value="Can Write Hindi Only">Can Write – Hindi Only</option>
                                    <option value="Can Write English Only">Can Write – English Only</option>
                                    <option value="Can Write Both">Can Write – Hindi & English</option>

                                    <option value="Can Read & Write Hindi Only">Can Read & Write – Hindi Only</option>
                                    <option value="Can Read & Write English Only">Can Read & Write – English Only</option>
                                    <option value="Can Read & Write Both">Can Read & Write – Hindi & English</option>

                                    <!-- School Level -->
                                    <option value="Below 5th">Below 5th Standard</option>
                                    <option value="5th Pass">5th Pass</option>
                                    <option value="8th Pass">8th Pass</option>
                                    <option value="Below 10th">Below 10th</option>

                                    <option value="10th Pass">10th Pass</option>
                                    <option value="12th Pass">12th Pass</option>

                                    <!-- Higher Education -->
                                    <option value="Diploma">Diploma</option>
                                    <option value="Graduate">Graduate</option>
                                    <option value="Post Graduate">Post Graduate</option>
                                    <option value="Doctorate">Doctorate</option>

                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="editSkills" class="form-label">Skills/Experience</label>
                                <input type="text" class="form-control" id="editSkills" name="skills" placeholder="e.g., Computer skills, driving, etc.">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="editPreferences" class="form-label">Job Preferences</label>
                            <input type="text" class="form-control" id="editPreferences" name="preferences" placeholder="e.g., Full-time, part-time, specific industry">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="editSubmitBtn">
                            <span class="submit-text">Save Changes</span>
                            <span class="loading-spinner" style="display: none;">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        let offset = 0;
        let isLastBatch = false;
        let currentFilters = {
            status_filter: 'active',
            education_filter: '',
            search_term: ''
        };
        let totalFilteredRecords = 0;
        let currentOffset = 0;
        let currentLimit = 20;

        // Toggle search mode functionality
        $(document).ready(function() {
            const toggleSearch = $('#toggleSearchMode');
            const statusField = $('#status');
            const educationField = $('#education');
            const searchField = $('#search');
            const applyFiltersBtn = $('#applyFilters');

            // Read URL parameters first
            readURLParameters();

            function toggleSearchMode() {
                if (toggleSearch.is(':checked')) {
                    // Search mode enabled
                    statusField.prop('disabled', true);
                    educationField.prop('disabled', true);
                    searchField.prop('disabled', false);
                    applyFiltersBtn.text('Search');

                    // Clear other filters when switching to search mode
                    currentFilters.status_filter = 'all';
                    currentFilters.education_filter = '';
                    statusField.val('all');
                    educationField.val('');
                } else {
                    // Search mode disabled - reset to active only
                    statusField.prop('disabled', false);
                    educationField.prop('disabled', false);
                    searchField.prop('disabled', true);
                    applyFiltersBtn.text('Apply Filters');

                    // Reset to active only and clear search
                    statusField.val('active');
                    currentFilters.status_filter = 'active';
                    currentFilters.education_filter = '';
                    currentFilters.search_term = ''; // Clear search term
                    educationField.val('');
                    searchField.val(''); // Clear search input field
                }
                // Update URL when mode changes
                updateURLParameters();
            }

            // Initial state - disable search field by default
            toggleSearchMode();

            // On checkbox change
            toggleSearch.change(function() {
                toggleSearchMode();
                // Apply filters immediately when mode changes
                applyFilters();
            });

            // Handle Enter key in search field
            searchField.keypress(function(event) {
                if (event.which === 13) { // Enter key
                    event.preventDefault();
                    applyFilters();
                }
            });

            // Handle form submit (for Enter key in any field)
            $('#filterForm').on('submit', function(event) {
                event.preventDefault();
                applyFilters();
                return false;
            });

            // Initial load
            fetchRecords();

            // Load more button
            $("#loadMoreBtn").click(() => {
                fetchRecords();
            });

            // Apply filters button
            $("#applyFilters").click(applyFilters);

            // Records per load change
            $("#recordsPerLoad").change(() => {
                fetchRecords(true);
            });

            // Initialize dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function() {
            readURLParameters();
            applyFilters();
        });

        const fetchRecords = async (reset = false) => {
            if (reset) {
                offset = 0;
                currentOffset = 0;
                isLastBatch = false;
                $('#data-tbody').empty();
                $('#loadMoreBtn').show();
            }

            if (isLastBatch && !reset) return;

            const limit = parseInt($('#recordsPerLoad').val());
            currentLimit = limit;
            $('#loadingIndicator').show();
            $('#loadMoreBtn').prop('disabled', true);

            try {
                const response = await $.post("", {
                    formType: 'ajax_fetch',
                    offset: offset,
                    limit: limit,
                    status_filter: currentFilters.status_filter,
                    education_filter: currentFilters.education_filter,
                    search_term: currentFilters.search_term,
                    search_mode: $('#toggleSearchMode').is(':checked')
                });

                const data = JSON.parse(response);

                if (data.success) {
                    const tbody = $("#data-tbody");
                    const records = data.records;

                    // Update global variables with response data
                    totalFilteredRecords = data.totalFiltered || 0;
                    currentOffset = data.currentOffset || 0;

                    if (records.length > 0) {
                        records.forEach((record, index) => {
                            const hasRemarks = record.remarks && record.remarks.trim() !== '';
                            const rowClass = hasRemarks ? 'has-remarks' : '';

                            const row = `
                            <tr data-job-seeker-id="${record.id}" 
                                data-name="${escapeHtml(record.name)}" 
                                data-age="${escapeHtml(record.age)}" 
                                data-contact="${escapeHtml(record.contact)}" 
                                data-education="${escapeHtml(record.education)}" 
                                data-skills="${escapeHtml(record.skills || '')}" 
                                data-preferences="${escapeHtml(record.preferences || '')}"
                                data-parent-name="${escapeHtml(record.parent_name || '')}"
                                data-address="${escapeHtml(record.address || '')}"
                                data-surveyor-id="${escapeHtml(record.surveyor_id || '')}"
                                data-remarks="${escapeHtml(record.remarks || '')}"
                                data-created-at="${escapeHtml(record.created_at || '')}"
                                data-updated-at="${escapeHtml(record.updated_at || '')}"
                                class="${rowClass}">
                                <td>${escapeHtml(record.id)}</td>
                                <td>${escapeHtml(record.name)}</td>
                                <td>${escapeHtml(record.age)}</td>
                                <td>${escapeHtml(record.contact)}</td>
                                <td>${escapeHtml(record.education)}</td>
                                <td>${escapeHtml(record.skills || 'N/A')}</td>
                                <td>${escapeHtml(record.preferences || 'N/A')}</td>
                                <td>${escapeHtml(record.surveyor_id || 'N/A')}</td>
                                <td>${escapeHtml(record.status)}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-link text-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0.15rem 0.5rem;">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu action-dropdown">
                                            ${hasRemarks ? 
                                                `<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewRemarksModal" onclick="loadRemarksData(${record.id})"><i class="bi bi-eye me-2"></i>View Remarks</a></li>` : 
                                                ''}
                                            <li><a class="dropdown-item" href="#" onclick="updateStatus(${record.id}, '${record.status === 'Active' ? 'Inactive' : 'Active'}')">
                                                <i class="bi ${record.status === 'Active' ? 'bi-pause-fill' : 'bi-play-fill'} me-2"></i>
                                                ${record.status === 'Active' ? 'Mark Inactive' : 'Mark Active'}
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editModal" onclick="loadJobSeekerDataDirect(${record.id})">
                                                <i class="bi bi-pencil me-2"></i>Edit Data
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addRemarkModal" onclick="setJobSeekerId(${record.id})">
                                                <i class="bi bi-chat-left-text me-2"></i>Add Remark
                                            </a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        `;
                            tbody.append(row);
                        });

                        offset += records.length;
                        currentOffset = offset;
                        isLastBatch = data.isLastBatch;

                        if (isLastBatch) {
                            $('#loadMoreBtn').hide();
                        }

                        // Update records info with proper showing X of Y format
                        updateRecordsInfo();
                    } else if (reset) {
                        tbody.html('<tr><td colspan="10" class="text-center">No records found</td></tr>');
                        $('#loadMoreBtn').hide();
                        updateRecordsInfo(); // Update even when no records
                    }
                } else {
                    alert(data.message || "Failed to load records.");
                }
            } catch (error) {
                console.error("Error fetching records:", error);
                alert("Error loading records. Please try again.");
            } finally {
                $('#loadingIndicator').hide();
                $('#loadMoreBtn').prop('disabled', false);
            }
        };

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
        }

        function updateRecordsInfo() {
            const showingCount = Math.min(currentOffset, totalFilteredRecords);
            const totalCount = totalFilteredRecords;

            let infoText = '';
            if (totalCount === 0) {
                infoText = '0 of 0 records showing';
            } else {
                infoText = `<strong>${showingCount}</strong> of <strong>${totalCount}</strong> records showing`;

                // Add additional info when not all records are loaded
                if (showingCount < totalCount && !isLastBatch) {
                    infoText += ` <span class="text-muted">(${totalCount - showingCount} more available)</span>`;
                } else if (isLastBatch) {
                    infoText += ' <span class="text-success">(all records loaded)</span>';
                }
            }

            $('#recordsInfo').html(infoText);
        }

        function applyFilters() {
            const searchMode = $('#toggleSearchMode').is(':checked');

            currentFilters = {
                status_filter: $('#status').val(),
                education_filter: $('#education').val(),
                search_term: searchMode ? $('#search').val() : '' // Only include search term in search mode
            };

            // Reset the offset and total when filters change
            offset = 0;
            currentOffset = 0;
            totalFilteredRecords = 0;

            // Update URL parameters without reloading page
            updateURLParameters();

            fetchRecords(true);
        }

        function updateURLParameters() {
            const params = new URLSearchParams();
            params.set('status', currentFilters.status_filter);
            params.set('education', currentFilters.education_filter);
            params.set('search', currentFilters.search_term);
            params.set('search_mode', $('#toggleSearchMode').is(':checked') ? '1' : '0');

            // Update URL without reloading page
            const newUrl = window.location.pathname + '?' + params.toString();
            window.history.pushState({}, '', newUrl);
        }

        function readURLParameters() {
            const urlParams = new URLSearchParams(window.location.search);

            // Set status filter
            if (urlParams.has('status')) {
                $('#status').val(urlParams.get('status'));
                currentFilters.status_filter = urlParams.get('status');
            }

            // Set education filter
            if (urlParams.has('education')) {
                $('#education').val(urlParams.get('education'));
                currentFilters.education_filter = urlParams.get('education');
            }

            // Set search term - but only if search_mode is enabled
            const searchMode = urlParams.has('search_mode') ? urlParams.get('search_mode') === '1' : false;
            if (urlParams.has('search') && searchMode) {
                $('#search').val(urlParams.get('search'));
                currentFilters.search_term = urlParams.get('search');
            } else {
                // Clear search if search_mode is disabled
                $('#search').val('');
                currentFilters.search_term = '';
            }

            // Set search mode checkbox
            if (urlParams.has('search_mode')) {
                $('#toggleSearchMode').prop('checked', searchMode);

                // Trigger the toggle to update UI state
                if (searchMode) {
                    $('#toggleSearchMode').trigger('change');
                }
            }
        }

        function updateStatus(id, status) {
            if (confirm('Are you sure you want to change the status to ' + status + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'update_status';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;

                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = status;

                form.appendChild(actionInput);
                form.appendChild(idInput);
                form.appendChild(statusInput);

                document.body.appendChild(form);
                form.submit();
            }
        }

        function setJobSeekerId(id) {
            document.getElementById('addRemarkJobSeekerId').value = id;
        }

        function exportToExcel() {
            // Get current filter parameters
            const params = new URLSearchParams();
            params.append('status', currentFilters.status_filter);
            params.append('education', currentFilters.education_filter);
            params.append('search', currentFilters.search_term);

            // Redirect to export script with current filters
            window.location.href = 'export_job_seekers.php?' + params.toString();
        }

        function loadJobSeekerDataDirect(id) {
            const row = document.querySelector(`tr[data-job-seeker-id="${id}"]`);
            if (row) {
                document.getElementById('editJobSeekerId').value = id;
                document.getElementById('editName').value = row.dataset.name;
                document.getElementById('editAge').value = row.dataset.age;
                document.getElementById('editContact').value = row.dataset.contact;
                document.getElementById('editEducation').value = row.dataset.education;
                document.getElementById('editSkills').value = row.dataset.skills || '';
                document.getElementById('editPreferences').value = row.dataset.preferences || '';
            } else {
                alert('Error: Could not find job seeker data');
            }
        }

        function loadRemarksData(id) {
            const row = document.querySelector(`tr[data-job-seeker-id="${id}"]`);
            if (row) {
                document.getElementById('viewName').textContent = row.dataset.name;
                document.getElementById('viewAge').textContent = row.dataset.age;
                document.getElementById('viewContact').textContent = row.dataset.contact;
                document.getElementById('viewEducation').textContent = row.dataset.education;
                document.getElementById('viewSkills').textContent = row.dataset.skills || 'N/A';
                document.getElementById('viewPreferences').textContent = row.dataset.preferences || 'N/A';
                document.getElementById('viewAddress').textContent = row.dataset.address || 'N/A';

                const status = row.cells[8].textContent; // Status is in the 9th column (index 8)
                const statusElement = document.getElementById('viewStatus');
                statusElement.textContent = status;
                statusElement.className = 'badge ' + (status === 'Active' ? 'bg-success' : 'bg-secondary');

                const remarksContent = document.getElementById('viewRemarksContent');
                if (row.dataset.remarks && row.dataset.remarks.trim() !== '') {
                    remarksContent.textContent = row.dataset.remarks;
                    remarksContent.style.display = 'block';
                } else {
                    remarksContent.textContent = 'No remarks available.';
                    remarksContent.style.color = '#6c757d';
                    remarksContent.style.fontStyle = 'italic';
                }

                document.getElementById('addRemarkJobSeekerId').value = id;
            }
        }

        function addNewRemark() {
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewRemarksModal'));
            const addModal = new bootstrap.Modal(document.getElementById('addRemarkModal'));

            viewModal.hide();
            setTimeout(() => {
                addModal.show();
            }, 500);
        }

        function handleEditSubmit(event) {
            event.preventDefault();
            const age = parseInt(document.getElementById('editAge').value);
            const contact = document.getElementById('editContact').value;

            if (age < 18 || age > 65) {
                alert('Age must be between 18 and 65');
                return false;
            }

            if (!/^\d{10}$/.test(contact)) {
                alert('Contact number must be exactly 10 digits');
                return false;
            }

            const submitBtn = document.getElementById('editSubmitBtn');
            const submitText = submitBtn.querySelector('.submit-text');
            const loadingSpinner = submitBtn.querySelector('.loading-spinner');

            submitText.style.display = 'none';
            loadingSpinner.style.display = 'inline';
            submitBtn.disabled = true;

            const form = event.target;
            const formData = new FormData(form);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.text();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    submitText.style.display = 'inline';
                    loadingSpinner.style.display = 'none';
                    submitBtn.disabled = false;
                    alert('Error submitting form. Please try again.');
                });

            return false;
        }

        function handleAddRemarkSubmit(event) {
            event.preventDefault();
            const submitBtn = document.getElementById('addRemarkSubmitBtn');
            const submitText = submitBtn.querySelector('.submit-text');
            const loadingSpinner = submitBtn.querySelector('.loading-spinner');

            submitText.style.display = 'none';
            loadingSpinner.style.display = 'inline';
            submitBtn.disabled = true;

            const form = event.target;
            const formData = new FormData(form);

            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.text();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    submitText.style.display = 'inline';
                    loadingSpinner.style.display = 'none';
                    submitBtn.disabled = false;
                    alert('Error submitting remark. Please try again.');
                });

            return false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('editModal');
            editModal.addEventListener('hidden.bs.modal', function() {
                const submitBtn = document.getElementById('editSubmitBtn');
                const submitText = submitBtn.querySelector('.submit-text');
                const loadingSpinner = submitBtn.querySelector('.loading-spinner');

                submitText.style.display = 'inline';
                loadingSpinner.style.display = 'none';
                submitBtn.disabled = false;
            });

            const addRemarkModal = document.getElementById('addRemarkModal');
            addRemarkModal.addEventListener('hidden.bs.modal', function() {
                const submitBtn = document.getElementById('addRemarkSubmitBtn');
                const submitText = submitBtn.querySelector('.submit-text');
                const loadingSpinner = submitBtn.querySelector('.loading-spinner');

                submitText.style.display = 'inline';
                loadingSpinner.style.display = 'none';
                submitBtn.disabled = false;
                document.getElementById('remark').value = '';
            });
        });
    </script>
</body>

</html>