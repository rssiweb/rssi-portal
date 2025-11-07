<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Handle AJAX request for record fetching
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['formType'])) {
    if ($_POST['formType'] === 'ajax_fetch') {
        $offset = $_POST['offset'] ?? 0;
        $limit = $_POST['limit'] ?? 20;

        // Filter parameters
        $status_filter = $_POST['status_filter'] ?? 'all';
        $search_term = $_POST['search_term'] ?? '';
        $search_mode = filter_var($_POST['search_mode'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $date_from = $_POST['date_from'] ?? '';
        $date_to = $_POST['date_to'] ?? '';
        $surveyor_filter = $_POST['surveyor_filter'] ?? 'all';

        // Build WHERE clause based on search mode
        $where_conditions = [];
        $params = [];
        $param_count = 0;

        if ($search_mode) {
            // Search mode - only use search term
            if (!empty($search_term)) {
                $param_count++;
                $where_conditions[] = "(sd.student_name ILIKE $" . $param_count . " OR s.contact ILIKE $" . $param_count . " OR s.parent_name ILIKE $" . $param_count . " OR s.family_id ILIKE $" . $param_count . " OR s.address ILIKE $" . $param_count . " OR rm.fullname ILIKE $" . $param_count . ")";
                $params[] = "%$search_term%";
            }
        } else {
            // Filter mode - use all filters
            if ($status_filter !== 'all') {
                $param_count++;
                $where_conditions[] = "sd.status = $" . $param_count;
                $params[] = $status_filter;
            }

            // Also allow search in filter mode
            if (!empty($search_term)) {
                $param_count++;
                $where_conditions[] = "(sd.student_name ILIKE $" . $param_count . " OR s.contact ILIKE $" . $param_count . " OR s.parent_name ILIKE $" . $param_count . " OR s.family_id ILIKE $" . $param_count . " OR s.address ILIKE $" . $param_count . " OR rm.fullname ILIKE $" . $param_count . ")";
                $params[] = "%$search_term%";
            }

            // Add date range filter (only in filter mode)
            if (!empty($date_from)) {
                $param_count++;
                $where_conditions[] = "DATE(s.timestamp) >= $" . $param_count;
                $params[] = $date_from;
            }

            if (!empty($date_to)) {
                $param_count++;
                $where_conditions[] = "DATE(s.timestamp) <= $" . $param_count;
                $params[] = $date_to;
            }

            // Add surveyor filter (only in filter mode)
            if ($surveyor_filter !== 'all') {
                $param_count++;
                $where_conditions[] = "s.surveyor_id = $" . $param_count;
                $params[] = $surveyor_filter;
            }
        }

        $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

        // Get total count for filtered results
        $count_query = "SELECT COUNT(*) FROM survey_data s 
                        LEFT JOIN student_data sd ON s.family_id = sd.family_id 
                        LEFT JOIN rssimyaccount_members rm ON s.surveyor_id = rm.associatenumber
                        $where_clause";
        $count_result = pg_query_params($con, $count_query, $params);
        $total_filtered_records = pg_fetch_result($count_result, 0, 0);

        // Get students data with pagination
        $params[] = $limit;
        $params[] = $offset;

        $query = "SELECT s.*, sd.id as student_id, sd.student_name, sd.age, sd.gender, sd.grade, sd.status as student_status, 
                         sd.already_going_school, sd.school_type, sd.already_coaching, sd.coaching_name, sd.remarks,
                         rm.fullname AS surveyor_name
                  FROM survey_data s 
                  LEFT JOIN student_data sd ON s.family_id = sd.family_id 
                  LEFT JOIN rssimyaccount_members rm ON s.surveyor_id = rm.associatenumber
                  $where_clause 
                  ORDER BY s.timestamp DESC 
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
    } elseif ($_POST['formType'] === 'get_remarks') {
        // Handle AJAX request to get remarks
        $student_id = $_POST['student_id'];

        $query = "SELECT remarks FROM student_data WHERE id = $1";
        $result = pg_query_params($con, $query, array($student_id));

        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            $remarks = $row['remarks'];

            // Parse remarks as JSON or return as is
            $remarks_data = [];
            if (!empty($remarks)) {
                // Try to decode as JSON (new format)
                $decoded_remarks = json_decode($remarks, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_remarks)) {
                    $remarks_data = $decoded_remarks;
                } else {
                    // If not JSON, treat as single remark with current timestamp
                    $remarks_data[] = [
                        'remark' => $remarks,
                        'timestamp' => date('Y-m-d H:i:s'),
                        'added_by' => $_SESSION['fullname'] ?? 'System'
                    ];
                }
            }

            echo json_encode([
                "success" => true,
                "remarks" => $remarks_data
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Student not found."
            ]);
        }
        exit;
    }
}

// Handle status and remarks update action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $new_remark = $_POST['remark'] ?? '';

    // Get current user's fullname from rssimyaccount_members table
    $current_user_fullname = 'Unknown';
    if (isset($_SESSION['aid'])) {
        $user_query = "SELECT fullname FROM rssimyaccount_members WHERE associatenumber = $1";
        $user_result = pg_query_params($con, $user_query, array($associatenumber));
        if ($user_result && pg_num_rows($user_result) > 0) {
            $user_row = pg_fetch_assoc($user_result);
            $current_user_fullname = $user_row['fullname'];
        }
    }

    // Get existing remarks
    $get_remarks_query = "SELECT remarks FROM student_data WHERE id = $1";
    $get_remarks_result = pg_query_params($con, $get_remarks_query, array($id));

    $remarks_array = [];
    if ($get_remarks_result && pg_num_rows($get_remarks_result) > 0) {
        $row = pg_fetch_assoc($get_remarks_result);
        $existing_remarks = $row['remarks'];

        if (!empty($existing_remarks)) {
            // Try to decode existing remarks as JSON
            $decoded_remarks = json_decode($existing_remarks, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_remarks)) {
                $remarks_array = $decoded_remarks;
            } else {
                // If not JSON, convert existing remark to new format
                $remarks_array[] = [
                    'remark' => $existing_remarks,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'added_by' => $current_user_fullname
                ];
            }
        }
    }

    // Add new remark if provided
    if (!empty($new_remark)) {
        $remarks_array[] = [
            'remark' => $new_remark,
            'timestamp' => date('Y-m-d H:i:s'),
            'added_by' => $current_user_fullname
        ];
    }

    // Update database
    $query = "UPDATE student_data SET status = $1, remarks = $2 WHERE id = $3";
    $result = pg_query_params($con, $query, array(
        $status,
        json_encode($remarks_array),
        $id
    ));

    if ($result) {
        $_SESSION['success_message'] = "Status and remarks updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update status and remarks!";
    }

    // Check if it's an AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // Return JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $result ? true : false,
            'message' => $result ? 'Status and remarks updated successfully!' : 'Failed to update status and remarks!'
        ]);
        exit;
    } else {
        // Preserve filter parameters in redirect
        $filter_params = '';
        if (isset($_POST['current_filters'])) {
            $filter_params = '&' . $_POST['current_filters'];
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY));
        exit;
    }
}

// Get surveyors for filter dropdown
$surveyors_query = "SELECT DISTINCT rm.associatenumber, rm.fullname 
                   FROM survey_data s 
                   LEFT JOIN rssimyaccount_members rm ON s.surveyor_id = rm.associatenumber 
                   WHERE rm.fullname IS NOT NULL 
                   ORDER BY rm.fullname";
$surveyors_result = pg_query($con, $surveyors_query);
$surveyors = pg_fetch_all($surveyors_result) ?: [];

// Get total records count for initial load (without filters)
$count_query = "SELECT COUNT(*) FROM survey_data";
$count_result = pg_query($con, $count_query);
$total_records = pg_fetch_result($count_result, 0, 0);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View Survey Results</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <!-- Add daterangepicker CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

    <!-- Add moment.js and daterangepicker JS -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <style>
        .student-details {
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

        #recordsInfo {
            font-weight: 600;
            color: #495057;
        }

        .short-address {
            display: inline;
        }

        .full-address {
            display: none;
        }

        .status-badge {
            font-size: 0.875em;
            padding: 0.35em 0.65em;
        }

        .no-student-data {
            color: #6c757d;
            font-style: italic;
        }

        .filter-row {
            margin-bottom: 15px;
        }

        .date-range-picker {
            position: relative;
        }

        .daterangepicker {
            font-family: inherit;
        }

        /* Remarks Styles */
        .remarks-history {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .remark-item {
            background-color: white;
            padding: 10px;
            border-radius: 3px;
            border-left: 4px solid #0d6efd;
            margin-bottom: 10px;
        }

        .remarks-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .remark-content p {
            margin-bottom: 5px;
            word-wrap: break-word;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>View Survey Results</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Survey</a></li>
                    <li class="breadcrumb-item active">View Survey Results</li>
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
                                    <form id="filterForm" class="mb-3" onsubmit="handleFormSubmit(event)">
                                        <div class="row filter-row">
                                            <div class="col-md-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="all" selected>All Status</option>

                                                    <!-- Inquiry Stage -->
                                                    <option value="Came for Inquiry">Came for Inquiry</option>
                                                    <option value="Telephonic Inquiry">Telephonic Inquiry</option>

                                                    <!-- Follow-up Stage -->
                                                    <option value="Follow-up Pending">Follow-up Pending</option>
                                                    <option value="Follow-up Done">Follow-up Done</option>

                                                    <!-- Interest Stage -->
                                                    <option value="Interested - Decision Pending">Interested - Decision Pending</option>

                                                    <!-- Enrollment Stage -->
                                                    <option value="Enrollment Initiated">Enrollment Initiated</option>
                                                    <option value="Enrollment Completed">Enrollment Completed</option>

                                                    <!-- Non-conversion Stage -->
                                                    <option value="No Show">No Show</option>
                                                    <option value="Not Interested">Not Interested</option>
                                                    <option value="Not Reachable">Not Reachable</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="surveyor_filter" class="form-label">Surveyor</label>
                                                <select class="form-select" id="surveyor_filter" name="surveyor_filter">
                                                    <option value="all" selected>All Surveyors</option>
                                                    <?php foreach ($surveyors as $surveyor): ?>
                                                        <option value="<?php echo $surveyor['associatenumber']; ?>">
                                                            <?php echo htmlspecialchars($surveyor['fullname']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="date_range" class="form-label">Date Range</label>
                                                <input type="text" class="form-control" id="date_range" name="date_range" placeholder="Select date range">
                                            </div>
                                        </div>
                                        <div class="row filter-row">
                                            <div class="col-md-8">
                                                <label for="search" class="form-label">Search</label>
                                                <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, contact, parent name, family ID, address, surveyor...">
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <button type="button" id="applyFilters" class="btn btn-primary me-2">Apply Filters</button>
                                                <button type="button" id="resetFilters" class="btn btn-outline-secondary">Reset</button>
                                            </div>
                                        </div>
                                        <div class="row mt-1 mb-1">
                                            <div class="col-md-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="toggleSearchMode">
                                                    <label class="form-check-label" for="toggleSearchMode">Enable Search Mode (ignores status filter)</label>
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
                                        <i class="bi bi-file-earmark-spreadsheet"></i> Export to CSV
                                    </button>
                                </div>
                            </div>

                            <!-- Students Table -->
                            <div class="table-responsive">
                                <table id="studentsTable" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>SL</th>
                                            <th>Family ID</th>
                                            <th>Student Name</th>
                                            <th>Age</th>
                                            <th>Gender</th>
                                            <th>Grade</th>
                                            <th>Parent Name</th>
                                            <th>Contact</th>
                                            <th>Address</th>
                                            <th>CSA</th>
                                            <th>JSA</th>
                                            <th>Surveyor Name</th>
                                            <th>Timestamp</th>
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

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Status & Remarks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="updateStatusForm">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="id" id="updateStatusStudentId">
                    <input type="hidden" name="current_filters" id="currentFilters">

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="statusSelect" class="form-label">Status</label>
                                    <select class="form-select" id="statusSelect" name="status" required>
                                        <option value="">Select Status</option>
                                        <!-- Inquiry Stage -->
                                        <option value="Came for Inquiry">Came for Inquiry</option>
                                        <option value="Telephonic Inquiry">Telephonic Inquiry</option>
                                        <!-- Follow-up Stage -->
                                        <option value="Follow-up Pending">Follow-up Pending</option>
                                        <option value="Follow-up Done">Follow-up Done</option>
                                        <!-- Interest Stage -->
                                        <option value="Interested - Decision Pending">Interested - Decision Pending</option>
                                        <!-- Enrollment Stage -->
                                        <option value="Enrollment Initiated">Enrollment Initiated</option>
                                        <option value="Enrollment Completed">Enrollment Completed</option>
                                        <!-- Non-conversion Stage -->
                                        <option value="No Show">No Show</option>
                                        <option value="Not Interested">Not Interested</option>
                                        <option value="Not Reachable">Not Reachable</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currentStatus" class="form-label">Current Status</label>
                                    <input type="text" class="form-control" id="currentStatus" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="remarkText" class="form-label">Add New Remark</label>
                            <textarea class="form-control" id="remarkText" name="remark" rows="3" placeholder="Enter your remarks here..."></textarea>
                            <div class="form-text">This remark will be added to the remarks history with timestamp.</div>
                        </div>

                        <!-- Remarks History Section -->
                        <div class="remarks-history mt-4">
                            <h6 class="mb-3">Remarks History</h6>
                            <div id="remarksContainer" style="max-height: 200px; overflow-y: auto; border: 1px solid #e9ecef; border-radius: 5px; padding: 10px;">
                                <div id="remarksList" class="remarks-list">
                                    <!-- Remarks will be loaded here dynamically -->
                                    <div class="text-muted text-center">Loading remarks...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="updateStatusSubmitBtn">
                            <span class="submit-text">Update Status & Remarks</span>
                            <span class="loading-spinner" style="display: none;">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Updating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDetailsModalLabel">Survey Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="student-details">
                        <h6 class="mb-3">Survey Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-row">
                                    <span class="detail-label">Student Name:</span>
                                    <span class="detail-value" id="viewStudentName"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Age:</span>
                                    <span class="detail-value" id="viewAge"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Gender:</span>
                                    <span class="detail-value" id="viewGender"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Grade:</span>
                                    <span class="detail-value" id="viewGrade"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Family ID:</span>
                                    <span class="detail-value" id="viewFamilyId"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-row">
                                    <span class="detail-label">Parent Name:</span>
                                    <span class="detail-value" id="viewParentName"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Contact:</span>
                                    <span class="detail-value" id="viewContact"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value" id="viewStatus"></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Earning Source:</span>
                                    <span class="detail-value" id="viewEarningSource"></span>
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

                    <!-- Remarks History Section in View Modal -->
                    <div class="remarks-history mt-4">
                        <h6 class="mb-3">Remarks History</h6>
                        <div id="viewRemarksContainer" style="max-height: 200px; overflow-y: auto; border: 1px solid #e9ecef; border-radius: 5px; padding: 10px;">
                            <div id="viewRemarksList" class="remarks-list">
                                <!-- Remarks will be loaded here dynamically -->
                                <div class="text-muted text-center">Loading remarks...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal" id="updateStatusFromViewBtn" style="display: none;">
                        Update Status
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add daterangepicker CSS and JS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        let offset = 0;
        let isLastBatch = false;
        let currentFilters = {
            status_filter: 'all',
            search_term: '',
            date_from: '',
            date_to: '',
            surveyor_filter: 'all'
        };
        let totalFilteredRecords = 0;
        let currentOffset = 0;
        let currentLimit = 20;
        let currentDateRange = '';

        // Toggle search mode functionality
        $(document).ready(function() {
            const toggleSearch = $('#toggleSearchMode');
            const statusField = $('#status');
            const surveyorField = $('#surveyor_filter');
            const dateRangeField = $('#date_range');
            const searchField = $('#search');
            const applyFiltersBtn = $('#applyFilters');
            const resetFiltersBtn = $('#resetFilters');

            // Initialize date range picker
            $('#date_range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD'
                }
            });

            // Handle date range selection
            $('#date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
                currentDateRange = $(this).val();

                // Update current filters with parsed dates
                currentFilters.date_from = picker.startDate.format('YYYY-MM-DD');
                currentFilters.date_to = picker.endDate.format('YYYY-MM-DD');
            });

            // Handle date range clear
            $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                currentDateRange = '';
                currentFilters.date_from = '';
                currentFilters.date_to = '';
            });

            // Read URL parameters first
            readURLParameters();

            function toggleSearchMode() {
                if (toggleSearch.is(':checked')) {
                    // Search mode enabled - enable search field only
                    statusField.prop('disabled', true);
                    surveyorField.prop('disabled', true);
                    dateRangeField.prop('disabled', true);
                    searchField.prop('disabled', false); // Enable search field
                    applyFiltersBtn.text('Search');
                } else {
                    // Search mode disabled - disable search field, enable other fields
                    statusField.prop('disabled', false);
                    surveyorField.prop('disabled', false);
                    dateRangeField.prop('disabled', false);
                    searchField.prop('disabled', true); // Disable search field
                    applyFiltersBtn.text('Apply Filters');
                }
                // Update URL when mode changes
                updateURLParameters();
            }

            // Initial state - search disabled by default
            $('#toggleSearchMode').prop('checked', false);
            toggleSearchMode();

            // On checkbox change - only update UI, don't apply filters automatically
            toggleSearch.change(function() {
                toggleSearchMode();
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

            // Handle reset filters
            resetFiltersBtn.click(function() {
                // Reset all form fields to initial state
                $('#status').val('all');
                $('#surveyor_filter').val('all');
                $('#date_range').val('');
                $('#search').val('');
                $('#toggleSearchMode').prop('checked', false); // Uncheck search mode

                // Reset current filters
                currentFilters = {
                    status_filter: 'all',
                    search_term: '',
                    date_from: '',
                    date_to: '',
                    surveyor_filter: 'all'
                };
                currentDateRange = '';

                // Update UI to initial state
                toggleSearchMode();

                // Update URL
                updateURLParameters();

                // Apply reset filters
                applyFilters();
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

            // Set current filters in hidden field when modal opens
            $('#updateStatusModal').on('show.bs.modal', function() {
                const currentParams = new URLSearchParams(window.location.search);
                $('#currentFilters').val(currentParams.toString());
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
                    search_term: currentFilters.search_term,
                    date_from: currentFilters.date_from,
                    date_to: currentFilters.date_to,
                    surveyor_filter: currentFilters.surveyor_filter,
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
                            const hasStudentData = record.student_name !== null && record.student_name !== '';
                            const statusClass = getStatusClass(record.student_status);
                            const displayStatus = record.student_status || '';

                            const row = `
                        <tr data-student-id="${record.student_id || ''}" 
                            data-student-name="${escapeHtml(record.student_name || '')}" 
                            data-age="${escapeHtml(record.age || '')}" 
                            data-gender="${escapeHtml(record.gender || '')}" 
                            data-grade="${escapeHtml(record.grade || '')}" 
                            data-parent-name="${escapeHtml(record.parent_name || '')}"
                            data-contact="${escapeHtml(record.contact || '')}"
                            data-address="${escapeHtml(record.address || '')}"
                            data-family-id="${escapeHtml(record.family_id || '')}"
                            data-earning-source="${escapeHtml(record.earning_source === "other" ? record.other_earning_source_input : record.earning_source || '')}"
                            data-status="${escapeHtml(record.student_status || '')}">
                            <td>${offset + index + 1}</td>
                            <td>${escapeHtml(record.family_id || 'N/A')}</td>
                            <td class="${!hasStudentData ? 'no-student-data' : ''}">${hasStudentData ? escapeHtml(record.student_name) : 'No Student Data'}</td>
                            <td>${hasStudentData ? escapeHtml(record.age) : ''}</td>
                            <td>${hasStudentData ? escapeHtml(record.gender) : ''}</td>
                            <td>${hasStudentData ? escapeHtml(record.grade) : ''}</td>
                            <td>${escapeHtml(record.parent_name || 'N/A')}</td>
                            <td>${record.contact ? `<a href="tel:${record.contact}">${escapeHtml(record.contact)}</a>` : 'N/A'}</td>
                            <td>${record.address ? (record.address.length > 30 ? record.address.substring(0, 30) + "..." : record.address) : 'N/A'}</td>
                            <td>${(()=>{try{const v=record.services_needed; if(!v) return ''; const arr=Array.isArray(v)?v:(typeof v==='string'&&v.trim().startsWith('[')?JSON.parse(v):v); return Array.isArray(arr)&&arr.length? escapeHtml(arr.join(', ')) : (typeof arr==='string'? escapeHtml(arr): ''); }catch(e){return ''}})()}</td>
                            <td>${escapeHtml(record.need_job_assistance || 'N/A')}</td>
                            <td>${escapeHtml(record.surveyor_name || 'N/A')}</td>
                            <td>${escapeHtml(record.timestamp || 'N/A')}</td>
                            <td>${escapeHtml(displayStatus)}</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link text-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0.15rem 0.5rem;">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu action-dropdown">
                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewDetailsModal" onclick="loadStudentDetails(this)">
                                            <i class="bi bi-eye me-2"></i>View Details
                                        </a></li>
                                        ${hasStudentData ? 
                                            `<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal" onclick="setStudentId(${record.student_id}, '${escapeHtml(record.student_status || '')}')">
                                                <i class="bi bi-pencil me-2"></i>Update Status
                                            </a></li>` : 
                                            ''}
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
                        tbody.html('<tr><td colspan="15" class="text-center">No records found</td></tr>');
                        $('#loadMoreBtn').hide();
                        updateRecordsInfo();
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

        function getStatusClass(status) {
            if (!status || status === 'No Student Data') return 'bg-secondary';
            switch (status) {
                case 'No Show':
                case 'Not Interested':
                case 'Not Reachable':
                    return 'bg-warning';
                case 'Enrollment Completed':
                    return 'bg-success';
                case 'Enrollment Initiated':
                case 'Interested - Decision Pending':
                    return 'bg-info';
                case 'Follow-up Pending':
                    return 'bg-danger';
                case 'Follow-up Done':
                case 'Came for Inquiry':
                case 'Telephonic Inquiry':
                    return 'bg-primary';
                default:
                    return 'bg-secondary';
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function updateRecordsInfo() {
            const showingCount = Math.min(currentOffset, totalFilteredRecords);
            const totalCount = totalFilteredRecords;

            let infoText = '';
            if (totalCount === 0) {
                infoText = '0 of 0 records showing';
            } else {
                infoText = `<strong>${showingCount}</strong> of <strong>${totalCount}</strong> records showing`;

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

            // Parse date range if it exists in the single field
            let dateFrom = currentFilters.date_from;
            let dateTo = currentFilters.date_to;

            if (currentDateRange && (!dateFrom || !dateTo)) {
                const dates = currentDateRange.split(' to ');
                if (dates.length === 2) {
                    dateFrom = dates[0];
                    dateTo = dates[1];
                }
            }

            currentFilters = {
                status_filter: searchMode ? 'all' : $('#status').val(),
                search_term: $('#search').val(),
                date_from: searchMode ? '' : dateFrom,
                date_to: searchMode ? '' : dateTo,
                surveyor_filter: searchMode ? 'all' : $('#surveyor_filter').val()
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
            params.set('search', currentFilters.search_term);
            params.set('date_from', currentFilters.date_from);
            params.set('date_to', currentFilters.date_to);
            params.set('date_range', currentDateRange);
            params.set('surveyor', currentFilters.surveyor_filter);
            params.set('search_mode', $('#toggleSearchMode').is(':checked') ? '1' : '0');

            const newUrl = window.location.pathname + '?' + params.toString();
            window.history.pushState({}, '', newUrl);
        }

        function readURLParameters() {
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.has('status')) {
                $('#status').val(urlParams.get('status'));
                currentFilters.status_filter = urlParams.get('status');
            }

            if (urlParams.has('surveyor')) {
                $('#surveyor_filter').val(urlParams.get('surveyor'));
                currentFilters.surveyor_filter = urlParams.get('surveyor');
            }

            if (urlParams.has('date_range')) {
                const dateRange = urlParams.get('date_range');
                $('#date_range').val(dateRange);
                currentDateRange = dateRange;

                const dates = dateRange.split(' to ');
                if (dates.length === 2) {
                    currentFilters.date_from = dates[0];
                    currentFilters.date_to = dates[1];
                }
            } else {
                if (urlParams.has('date_from') && urlParams.has('date_to')) {
                    const dateFrom = urlParams.get('date_from');
                    const dateTo = urlParams.get('date_to');
                    const dateRange = dateFrom + ' to ' + dateTo;
                    $('#date_range').val(dateRange);
                    currentDateRange = dateRange;
                    currentFilters.date_from = dateFrom;
                    currentFilters.date_to = dateTo;
                }
            }

            if (urlParams.has('search')) {
                $('#search').val(urlParams.get('search'));
                currentFilters.search_term = urlParams.get('search');
            }

            if (urlParams.has('search_mode')) {
                const searchMode = urlParams.get('search_mode') === '1';
                $('#toggleSearchMode').prop('checked', searchMode);
                toggleSearchMode();
            }
        }

        function setStudentId(id, currentStatus) {
            document.getElementById('updateStatusStudentId').value = id;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('currentStatus').value = currentStatus;
            document.getElementById('remarkText').value = '';

            // Load remarks for this student
            loadRemarks(id, 'remarksList');
        }

        function loadStudentDetails(element) {
            const row = $(element).closest('tr');
            const hasStudentData = row.find('td:nth-child(3)').hasClass('no-student-data') ? false : true;
            const studentId = row.data('student-id');

            document.getElementById('viewStudentName').textContent = hasStudentData ? row.data('student-name') : 'No Student Data';
            document.getElementById('viewAge').textContent = hasStudentData ? row.data('age') : 'N/A';
            document.getElementById('viewGender').textContent = hasStudentData ? row.data('gender') : 'N/A';
            document.getElementById('viewGrade').textContent = hasStudentData ? row.data('grade') : 'N/A';
            document.getElementById('viewParentName').textContent = row.data('parent-name') || 'N/A';
            document.getElementById('viewContact').textContent = row.data('contact') || 'N/A';
            document.getElementById('viewAddress').textContent = row.data('address') || 'N/A';
            document.getElementById('viewFamilyId').textContent = row.data('family-id') || 'N/A';
            document.getElementById('viewEarningSource').textContent = row.data('earning-source') || 'N/A';

            const status = row.data('status') || '';
            const statusElement = document.getElementById('viewStatus');
            statusElement.textContent = status || 'Not Set';
            statusElement.className = 'badge status-badge ' + getStatusClass(status);

            if (hasStudentData && studentId) {
                loadRemarks(studentId, 'viewRemarksList');
            } else {
                $('#viewRemarksList').html('<div class="text-muted text-center">No remarks available</div>');
            }

            const updateStatusBtn = document.getElementById('updateStatusFromViewBtn');
            if (hasStudentData && row.data('student-id')) {
                updateStatusBtn.style.display = 'inline-block';
                updateStatusBtn.onclick = function() {
                    setStudentId(row.data('student-id'), status);
                    $('#viewDetailsModal').modal('hide');
                    $('#updateStatusModal').modal('show');
                };
            } else {
                updateStatusBtn.style.display = 'none';
            }
        }

        function loadRemarks(studentId, containerId) {
            const container = $('#' + containerId);
            container.html('<div class="text-muted text-center">Loading remarks...</div>');

            $.post("", {
                formType: 'get_remarks',
                student_id: studentId
            }, function(response) {
                const data = JSON.parse(response);

                if (data.success) {
                    const remarks = data.remarks;

                    if (remarks && remarks.length > 0) {
                        let remarksHtml = '';

                        remarks.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

                        remarks.forEach((remarkObj, index) => {
                            const timestamp = new Date(remarkObj.timestamp).toLocaleString();
                            const addedBy = remarkObj.added_by || 'Unknown';

                            remarksHtml += `
                                <div class="remark-item mb-3 pb-2 ${index < remarks.length - 1 ? 'border-bottom' : ''}">
                                    <div class="remark-content">
                                        <p class="mb-1">${escapeHtml(remarkObj.remark)}</p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>${timestamp} 
                                            <i class="bi bi-person me-1 ms-2"></i>${escapeHtml(addedBy)}
                                        </small>
                                    </div>
                                </div>
                            `;
                        });

                        container.html(remarksHtml);
                    } else {
                        container.html('<div class="text-muted text-center">No remarks yet</div>');
                    }
                } else {
                    container.html('<div class="text-danger text-center">Failed to load remarks</div>');
                }
            }).fail(function() {
                container.html('<div class="text-danger text-center">Error loading remarks</div>');
            });
        }

        function handleStatusUpdateSubmit(event) {
            event.preventDefault();
            const submitBtn = document.getElementById('updateStatusSubmitBtn');
            const submitText = submitBtn.querySelector('.submit-text');
            const loadingSpinner = submitBtn.querySelector('.loading-spinner');

            submitText.style.display = 'none';
            loadingSpinner.style.display = 'inline';
            submitBtn.disabled = true;

            const form = event.target;
            const formData = new FormData(form);

            fetch('', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        $('#updateStatusModal').modal('hide');
                        // Reload with current filters preserved
                        fetchRecords(true);
                        // Show success message
                        showAlert('Status and remarks updated successfully!', 'success');
                    } else {
                        throw new Error(data.message || 'Update failed');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    submitText.style.display = 'inline';
                    loadingSpinner.style.display = 'none';
                    submitBtn.disabled = false;
                    alert('Error updating status: ' + error.message);
                });

            return false;
        }

        function showAlert(message, type) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            $('.card-body').prepend(alertHtml);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        }

        function exportToExcel() {
            const params = new URLSearchParams();
            params.append('status', currentFilters.status_filter);
            params.append('search', currentFilters.search_term);
            params.append('date_from', currentFilters.date_from);
            params.append('date_to', currentFilters.date_to);
            params.append('date_range', currentDateRange);
            params.append('surveyor', currentFilters.surveyor_filter);
            params.append('search_mode', $('#toggleSearchMode').is(':checked') ? '1' : '0');

            window.location.href = 'export_students.php?' + params.toString();
        }

        // Add event listener for the update status form
        document.addEventListener('DOMContentLoaded', function() {
            const updateStatusForm = document.getElementById('updateStatusForm');
            if (updateStatusForm) {
                updateStatusForm.addEventListener('submit', handleStatusUpdateSubmit);
            }

            const updateStatusModal = document.getElementById('updateStatusModal');
            updateStatusModal.addEventListener('hidden.bs.modal', function() {
                const submitBtn = document.getElementById('updateStatusSubmitBtn');
                const submitText = submitBtn.querySelector('.submit-text');
                const loadingSpinner = submitBtn.querySelector('.loading-spinner');

                submitText.style.display = 'inline';
                loadingSpinner.style.display = 'none';
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>