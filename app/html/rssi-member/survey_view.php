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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['formType']) && $_POST['formType'] === 'ajax_fetch') {
    $offset = $_POST['offset'] ?? 0;
    $limit = $_POST['limit'] ?? 20;

    // Filter parameters
    $status_filter = $_POST['status_filter'] ?? 'all';
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
            $where_conditions[] = "(sd.student_name ILIKE $" . $param_count . " OR s.contact ILIKE $" . $param_count . " OR s.parent_name ILIKE $" . $param_count . " OR sd.family_id ILIKE $" . $param_count . ")";
            $params[] = "%$search_term%";
        }
    } else {
        // Filter mode - use status filter
        if ($status_filter !== 'all') {
            $param_count++;
            $where_conditions[] = "sd.status = $" . $param_count;
            $params[] = $status_filter;
        }

        // Also allow search in filter mode
        if (!empty($search_term)) {
            $param_count++;
            $where_conditions[] = "(sd.student_name ILIKE $" . $param_count . " OR s.contact ILIKE $" . $param_count . " OR s.parent_name ILIKE $" . $param_count . " OR sd.family_id ILIKE $" . $param_count . ")";
            $params[] = "%$search_term%";
        }
    }

    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // Get total count for filtered results
    $count_query = "SELECT COUNT(*) FROM student_data sd LEFT JOIN survey_data s ON sd.family_id = s.family_id $where_clause";
    $count_result = pg_query_params($con, $count_query, $params);
    $total_filtered_records = pg_fetch_result($count_result, 0, 0);

    // Get students data with pagination
    $params[] = $limit;
    $params[] = $offset;

    $query = "SELECT sd.*, s.parent_name, s.address, s.surveyor_id, s.contact, s.earning_source, s.other_earning_source_input, 
                     s.timestamp, rm.fullname AS surveyor_name
              FROM student_data sd 
              LEFT JOIN survey_data s ON sd.family_id = s.family_id 
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
}

// Handle status update action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $query = "UPDATE student_data SET status = $1 WHERE id = $2";
    $result = pg_query_params($con, $query, array($status, $id));
    if ($result) {
        $_SESSION['success_message'] = "Status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update status!";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get total records count for initial load (without filters)
$count_query = "SELECT COUNT(*) FROM student_data WHERE status = 'Active'";
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
                                    <form id="filterForm" class="mb-3 d-inline-block" onsubmit="handleFormSubmit(event)">
                                        <div class="d-inline-block me-3">
                                            <label for="status" class="form-label d-block">Status</label>
                                            <select class="form-select d-inline-block" id="status" name="status">
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
                                        <div class="d-inline-block me-3">
                                            <label for="search" class="form-label d-block">Search</label>
                                            <input type="text" class="form-control d-inline-block" style="width:500px;" id="search" name="search" placeholder="Search by name, contact, parent name, family ID...">
                                        </div>
                                        <div class="d-inline-block mt-3 mt-md-0 align-bottom">
                                            <button type="button" id="applyFilters" class="btn btn-primary d-inline-block">Apply Filters</button>
                                        </div>
                                        <div class="row mt-3 mb-3">
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
                                        <i class="bi bi-file-earmark-excel"></i> Export to Excel
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="updateStatusForm" onsubmit="handleStatusUpdateSubmit(event)">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" id="updateStatusStudentId">
                        <div class="mb-3">
                            <label for="statusSelect" class="form-label">Status</label>
                            <select class="form-select" id="statusSelect" name="status" required>
                                <option value="">Select Status</option>

                                <!-- Active/Inactive -->
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>

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
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="updateStatusSubmitBtn">
                            <span class="submit-text">Update Status</span>
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
                    <h5 class="modal-title" id="viewDetailsModalLabel">Student Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="student-details">
                        <h6 class="mb-3">Student Information</h6>
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
                                    <span class="detail-label">Family ID:</span>
                                    <span class="detail-value" id="viewFamilyId"></span>
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
                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="detail-row">
                                    <span class="detail-label">Earning Source:</span>
                                    <span class="detail-value" id="viewEarningSource"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let offset = 0;
        let isLastBatch = false;
        let currentFilters = {
            status_filter: 'all',
            search_term: ''
        };
        let totalFilteredRecords = 0;
        let currentOffset = 0;
        let currentLimit = 20;

        // Toggle search mode functionality
        $(document).ready(function() {
            const toggleSearch = $('#toggleSearchMode');
            const statusField = $('#status');
            const searchField = $('#search');
            const applyFiltersBtn = $('#applyFilters');

            // Read URL parameters first
            readURLParameters();

            function toggleSearchMode() {
                if (toggleSearch.is(':checked')) {
                    // Search mode enabled
                    statusField.prop('disabled', true);
                    searchField.prop('disabled', false);
                    applyFiltersBtn.text('Search');

                    // Clear status filter when switching to search mode
                    currentFilters.status_filter = 'all';
                    statusField.val('all');
                } else {
                    // Search mode disabled - reset to active only
                    statusField.prop('disabled', false);
                    searchField.prop('disabled', true);
                    applyFiltersBtn.text('Apply Filters');

                    // Reset to active only and clear search
                    statusField.val('all');
                    currentFilters.status_filter = 'all';
                    currentFilters.search_term = ''; // Clear search term
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
                            const statusClass = getStatusClass(record.status);

                            const row = `
                            <tr data-student-id="${record.id}" 
                                data-student-name="${escapeHtml(record.student_name)}" 
                                data-age="${escapeHtml(record.age)}" 
                                data-gender="${escapeHtml(record.gender)}" 
                                data-grade="${escapeHtml(record.grade)}" 
                                data-parent-name="${escapeHtml(record.parent_name || '')}"
                                data-contact="${escapeHtml(record.contact || '')}"
                                data-address="${escapeHtml(record.address || '')}"
                                data-family-id="${escapeHtml(record.family_id || '')}"
                                data-earning-source="${escapeHtml(record.earning_source === "other" ? record.other_earning_source_input : record.earning_source || '')}"
                                data-status="${escapeHtml(record.status || '')}">
                                <td>${offset + index + 1}</td>
                                <td>${escapeHtml(record.family_id || 'N/A')}</td>
                                <td>${escapeHtml(record.student_name)}</td>
                                <td>${escapeHtml(record.age)}</td>
                                <td>${escapeHtml(record.gender)}</td>
                                <td>${escapeHtml(record.grade)}</td>
                                <td>${escapeHtml(record.parent_name || 'N/A')}</td>
                                <td>${escapeHtml(record.contact || 'N/A')}</td>
                                <td>
                                    <span class="short-address">${record.address && record.address.length > 30 ? record.address.substring(0, 30) + "..." : (record.address || 'N/A')}</span>
                                    ${record.address && record.address.length > 30 ? 
                                        `<span class="full-address" style="display: none;">${escapeHtml(record.address)}</span>
                                         <a href="#" class="more-link">more</a>` : 
                                        ''}
                                </td>
                                <td>${escapeHtml(record.surveyor_name || 'N/A')}</td>
                                <td>${escapeHtml(record.timestamp || 'N/A')}</td>
                                <td>${escapeHtml(record.status)}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-link text-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0.15rem 0.5rem;">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu action-dropdown">
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewDetailsModal" onclick="loadStudentDetails(${record.id})">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#updateStatusModal" onclick="setStudentId(${record.id}, '${escapeHtml(record.status)}')">
                                                <i class="bi bi-pencil me-2"></i>Update Status
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
                        tbody.html('<tr><td colspan="13" class="text-center">No records found</td></tr>');
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

        function getStatusClass(status) {
            switch (status) {
                case 'No Show':
                    return 'bg-warning';
                case 'Enrollment Completed':
                    return 'bg-info';
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

        function setStudentId(id, currentStatus) {
            document.getElementById('updateStatusStudentId').value = id;
            document.getElementById('statusSelect').value = currentStatus;
        }

        function loadStudentDetails(id) {
            const row = document.querySelector(`tr[data-student-id="${id}"]`);
            if (row) {
                document.getElementById('viewStudentName').textContent = row.dataset.studentName;
                document.getElementById('viewAge').textContent = row.dataset.age;
                document.getElementById('viewGender').textContent = row.dataset.gender;
                document.getElementById('viewGrade').textContent = row.dataset.grade;
                document.getElementById('viewParentName').textContent = row.dataset.parentName || 'N/A';
                document.getElementById('viewContact').textContent = row.dataset.contact || 'N/A';
                document.getElementById('viewAddress').textContent = row.dataset.address || 'N/A';
                document.getElementById('viewFamilyId').textContent = row.dataset.familyId || 'N/A';
                document.getElementById('viewEarningSource').textContent = row.dataset.earningSource || 'N/A';

                const status = row.dataset.status;
                const statusElement = document.getElementById('viewStatus');
                statusElement.textContent = status;
                statusElement.className = 'badge status-badge ' + getStatusClass(status);
            }
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
                    alert('Error updating status. Please try again.');
                });

            return false;
        }

        function exportToExcel() {
            // Get current filter parameters
            const params = new URLSearchParams();
            params.append('status', currentFilters.status_filter);
            params.append('search', currentFilters.search_term);
            params.append('search_mode', $('#toggleSearchMode').is(':checked') ? '1' : '0');

            // Redirect to export script with current filters
            window.location.href = 'export_students.php?' + params.toString();
        }

        // Address more/less functionality
        $(document).on("click", ".more-link", function(e) {
            e.preventDefault();
            const shortAddress = $(this).siblings(".short-address");
            const fullAddress = $(this).siblings(".full-address");

            if (fullAddress.is(":visible")) {
                // Hide full address and show short address
                fullAddress.hide();
                shortAddress.show();
                $(this).text("more");
            } else {
                // Show full address and hide short address
                fullAddress.show();
                shortAddress.hide();
                $(this).text("less");
            }
        });

        // Reset modal states when closed
        document.addEventListener('DOMContentLoaded', function() {
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