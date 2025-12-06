<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Calculate default date range (1 month before and after current date)
$currentDate = date('Y-m-d');
$defaultStartDate = date('Y-m-d', strtotime('-1 month', strtotime($currentDate)));
$defaultEndDate = date('Y-m-d', strtotime('+1 month', strtotime($currentDate)));

// Get filter values
$startDate = $_GET['start_date'] ?? $defaultStartDate;
$endDate = $_GET['end_date'] ?? $defaultEndDate;
$status = $_GET['status'] ?? '';
$verified = $_GET['verified'] ?? '';
$searchKeyword = $_GET['search_keyword'] ?? '';
$searchEnabled = isset($_GET['search_enabled']) ? $_GET['search_enabled'] : '0';

// Build WHERE clause
$whereConditions = [];
$params = [];
$paramCount = 0;

// Date range filter (only if keyword search is not enabled)
if ($searchEnabled !== '1') {
    $whereConditions[] = "created_at BETWEEN $" . ++$paramCount . " AND $" . ++$paramCount;
    $params[] = $startDate . ' 00:00:00';
    $params[] = $endDate . ' 23:59:59';
}

// Status filter
if ($status !== '') {
    $whereConditions[] = "is_active = $" . ++$paramCount;
    $params[] = ($status === 'active' ? 'true' : 'false');
}

// Verification filter
if ($verified !== '') {
    $whereConditions[] = "is_verified = $" . ++$paramCount;
    $params[] = ($verified === 'verified' ? 'true' : 'false');
}

// Keyword search (only if enabled)
if ($searchEnabled === '1' && !empty($searchKeyword)) {
    $searchTerm = "%" . $searchKeyword . "%";
    $whereConditions[] = "(
        full_name ILIKE $" . ++$paramCount . " OR 
        company_name ILIKE $" . $paramCount . " OR 
        email ILIKE $" . $paramCount . " OR 
        phone ILIKE $" . $paramCount . " OR 
        aadhar_number::text ILIKE $" . $paramCount . "
    )";
    $params[] = $searchTerm;
}

// Combine WHERE conditions
$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// Build and execute query
$query = "SELECT 
            id, 
            full_name, 
            company_name, 
            email, 
            phone, 
            aadhar_number,
            company_address,
            is_verified,
            is_active,
            created_at,
            updated_at,
            notes
          FROM recruiters 
          $whereClause 
          ORDER BY created_at DESC";

// For debugging
error_log("Query: $query");
error_log("Params: " . print_r($params, true));

$result = pg_query_params($con, $query, $params);
$recruiters = pg_fetch_all($result) ?: [];

// Get counts for filters
$countQuery = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN is_active = true THEN 1 END) as active_count,
    COUNT(CASE WHEN is_active = false THEN 1 END) as inactive_count,
    COUNT(CASE WHEN is_verified = true THEN 1 END) as verified_count,
    COUNT(CASE WHEN is_verified = false THEN 1 END) as unverified_count
FROM recruiters";

if ($searchEnabled === '1' && !empty($searchKeyword)) {
    $searchTerm = "%" . $searchKeyword . "%";
    $countQuery .= " WHERE (
        full_name ILIKE $1 OR 
        company_name ILIKE $1 OR 
        email ILIKE $1 OR 
        phone ILIKE $1 OR 
        aadhar_number::text ILIKE $1
    )";
    $countResult = pg_query_params($con, $countQuery, [$searchTerm]);
} else {
    $countResult = pg_query($con, $countQuery);
}

$counts = pg_fetch_assoc($countResult);
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

    <title>Recruiter Management</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stats-card {
            border-left: 4px solid #0d6efd;
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .filter-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .form-check-label {
            font-weight: 500;
        }

        .action-dropdown .btn {
            padding: 0.25rem 0.5rem;
        }

        .action-dropdown .dropdown-toggle::after {
            display: none;
        }

        .badge-verified {
            background-color: #198754;
        }

        .badge-unverified {
            background-color: #6c757d;
        }

        .badge-active {
            background-color: #0d6efd;
        }

        .badge-inactive {
            background-color: #dc3545;
        }

        .dataTables_wrapper {
            padding: 0;
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }

        #keywordSearch:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Recruiter Management</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="job-admin.php">Job Admin</a></li>
                    <li class="breadcrumb-item active">Recruiter Management</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <!-- Statistics Cards -->
                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Recruiters</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-people" style="font-size: 2rem; color: #0d6efd;"></i>
                                </div>
                                <div class="ps-3">
                                    <h6><?php echo $counts['total'] ?? 0; ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h5 class="card-title">Active</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-check-circle" style="font-size: 2rem; color: #198754;"></i>
                                </div>
                                <div class="ps-3">
                                    <h6><?php echo $counts['active_count'] ?? 0; ?></h6>
                                    <span class="text-success small pt-1 fw-bold">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h5 class="card-title">Verified</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-shield-check" style="font-size: 2rem; color: #6f42c1;"></i>
                                </div>
                                <div class="ps-3">
                                    <h6><?php echo $counts['verified_count'] ?? 0; ?></h6>
                                    <span class="text-primary small pt-1 fw-bold">Verified</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h5 class="card-title">Inactive</h5>
                            <div class="d-flex align-items-center">
                                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="bi bi-x-circle" style="font-size: 2rem; color: #dc3545;"></i>
                                </div>
                                <div class="ps-3">
                                    <h6><?php echo $counts['inactive_count'] ?? 0; ?></h6>
                                    <span class="text-danger small pt-1 fw-bold">Inactive</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Filters</h5>

                            <form method="GET" id="filterForm" class="filter-section">
                                <div class="row">
                                    <!-- Keyword Search Toggle -->
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="searchEnabled" name="search_enabled" value="1" <?php echo $searchEnabled === '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="searchEnabled">
                                                <strong>Search by Keyword</strong> (Disables date range filter)
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Keyword Search Field -->
                                    <div class="col-md-6 mb-3">
                                        <label for="keywordSearch" class="form-label">Search Keyword</label>
                                        <input type="text" class="form-control" id="keywordSearch" name="search_keyword"
                                            value="<?php echo htmlspecialchars($searchKeyword); ?>"
                                            placeholder="Search by name, company, email, phone, or Aadhar..."
                                            <?php echo $searchEnabled !== '1' ? 'disabled' : ''; ?>>
                                    </div>

                                    <!-- Date Range Fields -->
                                    <div class="col-md-3 mb-3 date-range-field">
                                        <label for="startDate" class="form-label">From Date</label>
                                        <input type="date" class="form-control" id="startDate" name="start_date"
                                            value="<?php echo $startDate; ?>"
                                            <?php echo $searchEnabled === '1' ? 'disabled' : ''; ?>>
                                    </div>

                                    <div class="col-md-3 mb-3 date-range-field">
                                        <label for="endDate" class="form-label">To Date</label>
                                        <input type="date" class="form-control" id="endDate" name="end_date"
                                            value="<?php echo $endDate; ?>"
                                            <?php echo $searchEnabled === '1' ? 'disabled' : ''; ?>>
                                    </div>

                                    <!-- Status Filter -->
                                    <div class="col-md-3 mb-3">
                                        <label for="statusFilter" class="form-label">Status</label>
                                        <select class="form-select" id="statusFilter" name="status" <?php echo $searchEnabled === '1' ? 'disabled' : ''; ?>>
                                            <option value="">All Status</option>
                                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>

                                    <!-- Verification Filter -->
                                    <div class="col-md-3 mb-3">
                                        <label for="verifiedFilter" class="form-label">Verification Status</label>
                                        <select class="form-select" id="verifiedFilter" name="verified" <?php echo $searchEnabled === '1' ? 'disabled' : ''; ?>>
                                            <option value="">All Verification</option>
                                            <option value="verified" <?php echo $verified === 'verified' ? 'selected' : ''; ?>>Verified</option>
                                            <option value="unverified" <?php echo $verified === 'unverified' ? 'selected' : ''; ?>>Unverified</option>
                                        </select>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="col-md-12">
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-filter"></i> Apply Filters
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                                                <i class="bi bi-arrow-clockwise"></i> Reset
                                            </button>
                                            <a href="recruiter-add.php" class="btn btn-success ms-auto">
                                                <i class="bi bi-plus-circle"></i> Add New Recruiter
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Recruiters Table -->
                            <div class="table-responsive">
                                <table id="recruitersTable" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Company</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Aadhar</th>
                                            <th>Status</th>
                                            <th>Verification</th>
                                            <th>Created Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recruiters)): ?>
                                            <tr>
                                                <td colspan="10" class="text-center">No recruiters found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recruiters as $recruiter): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($recruiter['id']); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($recruiter['full_name']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($recruiter['company_name']); ?></td>
                                                    <td>
                                                        <a href="mailto:<?php echo htmlspecialchars($recruiter['email']); ?>">
                                                            <?php echo htmlspecialchars($recruiter['email']); ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($recruiter['phone']); ?></td>
                                                    <td>
                                                        <?php if (!empty($recruiter['aadhar_number'])): ?>
                                                            <span class="badge bg-info"><?php echo htmlspecialchars($recruiter['aadhar_number']); ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Not Provided</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($recruiter['is_active'] === 't'): ?>
                                                            <span class="badge badge-active">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-inactive">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($recruiter['is_verified'] === 't'): ?>
                                                            <span class="badge badge-verified">Verified</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-unverified">Unverified</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('d-m-Y', strtotime($recruiter['created_at'])); ?></td>
                                                    <td>
                                                        <div class="dropdown action-dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                                <i class="bi bi-three-dots-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <a class="dropdown-item edit-recruiter" href="#"
                                                                        data-id="<?php echo $recruiter['id']; ?>"
                                                                        data-bs-toggle="modal" data-bs-target="#editRecruiterModal">
                                                                        <i class="bi bi-pencil me-2"></i>Edit
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="#" onclick="viewDetails(<?php echo $recruiter['id']; ?>)">
                                                                        <i class="bi bi-eye me-2"></i>View Details
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <hr class="dropdown-divider">
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger" href="#"
                                                                        onclick="confirmDelete(<?php echo $recruiter['id']; ?>)">
                                                                        <i class="bi bi-trash me-2"></i>Delete
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Edit Recruiter Modal -->
    <div class="modal fade" id="editRecruiterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Recruiter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editRecruiterForm">
                        <input type="hidden" id="editRecruiterId" name="id">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editFullName" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="editFullName" name="full_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editCompanyName" class="form-label">Company Name *</label>
                                <input type="text" class="form-control" id="editCompanyName" name="company_name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editEmail" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="editEmail" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editPhone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="editPhone" name="phone" pattern="[0-9]{10}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editAadharNumber" class="form-label">Aadhar Number</label>
                                <input type="text" class="form-control" id="editAadharNumber" name="aadhar_number" pattern="[0-9]{12}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editStatus" class="form-label">Status</label>
                                <select class="form-select" id="editStatus" name="is_active">
                                    <option value="true">Active</option>
                                    <option value="false">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editVerification" class="form-label">Verification Status</label>
                                <select class="form-select" id="editVerification" name="is_verified">
                                    <option value="true">Verified</option>
                                    <option value="false">Unverified</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="editCompanyAddress" class="form-label">Company Address *</label>
                            <textarea class="form-control" id="editCompanyAddress" name="company_address" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="editNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="editNotes" name="notes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveRecruiterBtn">
                        <span class="btn-text">Save Changes</span>
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        // API endpoint configuration
        const API_BASE = window.location.hostname === 'localhost' ?
            'http://localhost:8082/job-api/' :
            'https://login.rssi.in/job-api/';
        $(document).ready(function() {
            // Initialize DataTable
            $('#recruitersTable').DataTable({
                responsive: true,
                pageLength: 25,
                order: [
                    [8, 'desc']
                ], // Sort by created_at descending
                language: {
                    search: "Search in table:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                }
            });

            // Toggle keyword search field
            $('#searchEnabled').change(function() {
                const isChecked = $(this).is(':checked');

                // Toggle keyword search field
                $('#keywordSearch').prop('disabled', !isChecked);

                // Toggle date range and other filters
                $('.date-range-field input, #statusFilter, #verifiedFilter').prop('disabled', isChecked);

                // Clear keyword if disabling
                if (!isChecked) {
                    $('#keywordSearch').val('');
                }
            });

            // Edit recruiter modal handler
            $('.edit-recruiter').click(function() {
                const recruiterId = $(this).data('id');
                loadRecruiterData(recruiterId);
            });

            // Save recruiter changes
            $('#saveRecruiterBtn').click(function() {
                saveRecruiterChanges();
            });
        });

        function loadRecruiterData(recruiterId) {
            $.ajax({
                url: API_BASE + 'get_recruiter.php?id=' + recruiterId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const recruiter = response.data;

                        // Populate form fields
                        $('#editRecruiterId').val(recruiter.id);
                        $('#editFullName').val(recruiter.full_name);
                        $('#editCompanyName').val(recruiter.company_name);
                        $('#editEmail').val(recruiter.email);
                        $('#editPhone').val(recruiter.phone);
                        $('#editAadharNumber').val(recruiter.aadhar_number || '');
                        $('#editCompanyAddress').val(recruiter.company_address);
                        $('#editNotes').val(recruiter.notes || '');

                        // Handle boolean values - PostgreSQL returns 't'/'f' or true/false
                        const isActive = (recruiter.is_active === 't' || recruiter.is_active === true || recruiter.is_active === 'true');
                        const isVerified = (recruiter.is_verified === 't' || recruiter.is_verified === true || recruiter.is_verified === 'true');

                        $('#editStatus').val(isActive ? 'true' : 'false');
                        $('#editVerification').val(isVerified ? 'true' : 'false');
                    } else {
                        alert('Failed to load recruiter data: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading recruiter data. Please try again.');
                }
            });
        }

        function saveRecruiterChanges() {
            // Get the button and its elements
            const saveBtn = $('#saveRecruiterBtn');
            const btnText = saveBtn.find('.btn-text');
            const spinner = saveBtn.find('.spinner-border');

            // Disable button and show spinner
            saveBtn.prop('disabled', true);
            btnText.text('Updating...');
            spinner.show();
            const formData = $('#editRecruiterForm').serialize();

            $.ajax({
                url: API_BASE + 'update_recruiter.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Recruiter updated successfully!');
                        $('#editRecruiterModal').modal('hide');
                        location.reload(); // Reload page to show updated data
                    } else {
                        alert('Failed to update: ' + response.message);
                        // Re-enable button on error
                        resetSaveButton();
                    }
                },
                error: function() {
                    alert('Error updating recruiter. Please try again.');
                    // Re-enable button on error
                    resetSaveButton();
                }
            });
            // Helper function to reset button state
            function resetSaveButton() {
                saveBtn.prop('disabled', false);
                btnText.text('Save Changes');
                spinner.hide();
            }
        }

        function viewDetails(recruiterId) {
            window.location.href = 'recruiter-details.php?id=' + recruiterId;
        }

        function confirmDelete(recruiterId) {
            if (confirm('Are you sure you want to delete this recruiter? This action cannot be undone.')) {
                deleteRecruiter(recruiterId);
            }
        }

        function deleteRecruiter(recruiterId) {
            $.ajax({
                url: API_BASE + 'delete_recruiter.php',
                type: 'POST',
                data: {
                    id: recruiterId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Recruiter deleted successfully!');
                        location.reload();
                    } else {
                        alert('Failed to delete: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error deleting recruiter. Please try again.');
                }
            });
        }

        function resetFilters() {
            // Calculate default dates
            const today = new Date();
            const startDate = new Date(today);
            startDate.setMonth(today.getMonth() - 1);
            const endDate = new Date(today);
            endDate.setMonth(today.getMonth() + 1);

            // Format dates as YYYY-MM-DD
            const formatDate = (date) => date.toISOString().split('T')[0];

            // Reset form
            document.getElementById('filterForm').reset();
            document.getElementById('startDate').value = formatDate(startDate);
            document.getElementById('endDate').value = formatDate(endDate);

            // Reset toggle state
            document.getElementById('searchEnabled').checked = false;
            document.getElementById('keywordSearch').disabled = true;
            $('.date-range-field input, #statusFilter, #verifiedFilter').prop('disabled', false);

            // Submit form
            document.getElementById('filterForm').submit();
        }
    </script>
</body>

</html>