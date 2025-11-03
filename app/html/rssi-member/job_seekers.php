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

// Pagination setup
$records_per_page = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'active';
$education_filter = isset($_GET['education']) ? $_GET['education'] : '';
$search_term = isset($_GET['search']) ? pg_escape_string($con, $_GET['search']) : '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$param_count = 0;

if ($status_filter === 'active') {
    $where_conditions[] = "status = 'Active'";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "status = 'Inactive'";
}

if (!empty($education_filter)) {
    $param_count++;
    $where_conditions[] = "education = $" . $param_count;
    $params[] = $education_filter;
}

if (!empty($search_term)) {
    $param_count++;
    $where_conditions[] = "(name ILIKE $" . $param_count . " OR contact ILIKE $" . $param_count . " OR skills ILIKE $" . $param_count . " OR preferences ILIKE $" . $param_count . ")";
    $params[] = "%$search_term%";
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total records count
$count_query = "SELECT COUNT(*) FROM job_seeker_data $where_clause";
$count_result = $params ? pg_query_params($con, $count_query, $params) : pg_query($con, $count_query);
$total_records = pg_fetch_result($count_result, 0, 0);
$total_pages = ceil($total_records / $records_per_page);

// Get job seekers data with pagination
$params[] = $records_per_page;
$params[] = $offset;

$query = "SELECT js.*, s.parent_name, s.address, s.surveyor_id 
          FROM job_seeker_data js 
          LEFT JOIN survey_data s ON js.family_id = s.family_id 
          $where_clause 
          ORDER BY js.created_at DESC 
          LIMIT $" . ($param_count + 1) . " OFFSET $" . ($param_count + 2);

$result = pg_query_params($con, $query, $params);
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
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
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
                        <div class="card-body">
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

                            <!-- Filters -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <form method="GET" action="" class="row g-3">
                                        <div class="col-md-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="education" class="form-label">Education</label>
                                            <select class="form-select" id="education" name="education" onchange="this.form.submit()">
                                                <option value="">All Education</option>
                                                <option value="Below 10th" <?php echo $education_filter === 'Below 10th' ? 'selected' : ''; ?>>Below 10th</option>
                                                <option value="10th Pass" <?php echo $education_filter === '10th Pass' ? 'selected' : ''; ?>>10th Pass</option>
                                                <option value="12th Pass" <?php echo $education_filter === '12th Pass' ? 'selected' : ''; ?>>12th Pass</option>
                                                <option value="Diploma" <?php echo $education_filter === 'Diploma' ? 'selected' : ''; ?>>Diploma</option>
                                                <option value="Graduate" <?php echo $education_filter === 'Graduate' ? 'selected' : ''; ?>>Graduate</option>
                                                <option value="Post Graduate" <?php echo $education_filter === 'Post Graduate' ? 'selected' : ''; ?>>Post Graduate</option>
                                                <option value="Doctorate" <?php echo $education_filter === 'Doctorate' ? 'selected' : ''; ?>>Doctorate</option>
                                                <option value="Other" <?php echo $education_filter === 'Other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="search" class="form-label">Search</label>
                                            <input type="text" class="form-control" id="search" name="search"
                                                value="<?php echo htmlspecialchars($search_term); ?>"
                                                placeholder="Search by name, contact, skills...">
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Job Seekers Table -->
                            <div class="table-responsive">
                                <table id="jobSeekersTable" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Age</th>
                                            <th>Contact</th>
                                            <th>Education</th>
                                            <th>Skills</th>
                                            <th>Preferences</th>
                                            <th>Parent/Guardian</th>
                                            <th>Surveyor ID</th>
                                            <th>Status</th>
                                            <th>Remarks</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $serial_number = $offset + 1;
                                        while ($row = pg_fetch_assoc($result)) {
                                            echo "<tr data-job-seeker-id='" . $row['id'] . "' 
                                                  data-name='" . htmlspecialchars($row['name']) . "' 
                                                  data-age='" . htmlspecialchars($row['age']) . "' 
                                                  data-contact='" . htmlspecialchars($row['contact']) . "' 
                                                  data-education='" . htmlspecialchars($row['education']) . "' 
                                                  data-skills='" . htmlspecialchars($row['skills'] ?? '') . "' 
                                                  data-preferences='" . htmlspecialchars($row['preferences'] ?? '') . "'
                                                  data-parent-name='" . htmlspecialchars($row['parent_name']) . "'
                                                  data-address='" . htmlspecialchars($row['address']) . "'
                                                  data-surveyor-id='" . htmlspecialchars($row['surveyor_id']) . "'
                                                  data-remarks='" . htmlspecialchars($row['remarks'] ?? '') . "'
                                                  data-created-at='" . htmlspecialchars($row['created_at']) . "'
                                                  data-updated-at='" . htmlspecialchars($row['updated_at']) . "'>";
                                            echo "<td>" . $serial_number++ . "</td>";
                                            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['age']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['contact']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['education']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['skills'] ?? 'N/A') . "</td>";
                                            echo "<td>" . htmlspecialchars($row['preferences'] ?? 'N/A') . "</td>";
                                            echo "<td>" . htmlspecialchars($row['parent_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['surveyor_id']) . "</td>";
                                            echo "<td>";
                                            echo "<span class='badge " . ($row['status'] === 'Active' ? 'bg-success' : 'bg-secondary') . "'>";
                                            echo htmlspecialchars($row['status']);
                                            echo "</span>";
                                            echo "</td>";
                                            echo "<td>";
                                            if (!empty($row['remarks'])) {
                                                echo "<button class='btn btn-sm btn-outline-info' data-bs-toggle='modal' data-bs-target='#viewRemarksModal' onclick='loadRemarksData(" . $row['id'] . ")' title='View Remarks'><i class='bi bi-eye'></i> View Remarks</button>";
                                            } else {
                                                echo "No remarks";
                                            }
                                            echo "</td>";
                                            echo "<td>";
                                            echo "<div class='btn-group'>";
                                            // Status toggle
                                            if ($row['status'] === 'Active') {
                                                echo "<button class='btn btn-sm btn-warning' onclick='updateStatus(" . $row['id'] . ", \"Inactive\")' title='Mark Inactive'><i class='bi bi-pause-fill'></i></button>";
                                            } else {
                                                echo "<button class='btn btn-sm btn-success' onclick='updateStatus(" . $row['id'] . ", \"Active\")' title='Mark Active'><i class='bi bi-play-fill'></i></button>";
                                            }
                                            // Edit button
                                            echo "<button class='btn btn-sm btn-primary' data-bs-toggle='modal' data-bs-target='#editModal' onclick='loadJobSeekerDataDirect(" . $row['id'] . ")' title='Edit Details'><i class='bi bi-pencil'></i></button>";
                                            // Add remark
                                            echo "<button class='btn btn-sm btn-info' data-bs-toggle='modal' data-bs-target='#addRemarkModal' onclick='setJobSeekerId(" . $row['id'] . ")' title='Add Remark'><i class='bi bi-chat-left-text'></i></button>";
                                            echo "</div>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                        </li>
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <p>Showing <?php echo min($records_per_page, $total_records - $offset); ?> of <?php echo $total_records; ?> records</p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button class="btn btn-success" onclick="exportToExcel()">
                                        <i class="bi bi-file-earmark-excel"></i> Export to Excel
                                    </button>
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
                                    <span class="detail-label">Parent/Guardian:</span>
                                    <span class="detail-value" id="viewParentName"></span>
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
                                    <option value="Below 10th">Below 10th</option>
                                    <option value="10th Pass">10th Pass</option>
                                    <option value="12th Pass">12th Pass</option>
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
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#jobSeekersTable').DataTable({
                "paging": false,
                "searching": false,
                "info": false,
                "ordering": true,
                "dom": 'Bfrtip',
                "buttons": [
                    'copy', 'csv', 'excel', 'pdf'
                ]
            });

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

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
            const params = new URLSearchParams(window.location.search);

            // Redirect to export script with current filters
            window.location.href = 'export_job_seekers.php?' + params.toString();
        }

        // Function to load job seeker data using data attributes
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

        // Function to load remarks data for view modal
        function loadRemarksData(id) {
            const row = document.querySelector(`tr[data-job-seeker-id="${id}"]`);
            if (row) {
                // Set job seeker details
                document.getElementById('viewName').textContent = row.dataset.name;
                document.getElementById('viewAge').textContent = row.dataset.age;
                document.getElementById('viewContact').textContent = row.dataset.contact;
                document.getElementById('viewEducation').textContent = row.dataset.education;
                document.getElementById('viewSkills').textContent = row.dataset.skills || 'N/A';
                document.getElementById('viewPreferences').textContent = row.dataset.preferences || 'N/A';
                document.getElementById('viewParentName').textContent = row.dataset.parentName;
                document.getElementById('viewAddress').textContent = row.dataset.address || 'N/A';

                // Set status with badge
                const status = row.querySelector('.badge').textContent;
                const statusElement = document.getElementById('viewStatus');
                statusElement.textContent = status;
                statusElement.className = 'badge ' + (status === 'Active' ? 'bg-success' : 'bg-secondary');

                // Set remarks content
                const remarksContent = document.getElementById('viewRemarksContent');
                if (row.dataset.remarks && row.dataset.remarks.trim() !== '') {
                    remarksContent.textContent = row.dataset.remarks;
                    remarksContent.style.display = 'block';
                } else {
                    remarksContent.textContent = 'No remarks available.';
                    remarksContent.style.color = '#6c757d';
                    remarksContent.style.fontStyle = 'italic';
                }

                // Store the current job seeker ID for adding new remarks
                document.getElementById('addRemarkJobSeekerId').value = id;
            }
        }

        // Function to add new remark from view modal
        function addNewRemark() {
            const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewRemarksModal'));
            const addModal = new bootstrap.Modal(document.getElementById('addRemarkModal'));

            viewModal.hide();
            setTimeout(() => {
                addModal.show();
            }, 500);
        }

        // Form validation for edit modal
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const age = parseInt(document.getElementById('editAge').value);
            const contact = document.getElementById('editContact').value;

            if (age < 18 || age > 65) {
                e.preventDefault();
                alert('Age must be between 18 and 65');
                return false;
            }

            if (!/^\d{10}$/.test(contact)) {
                e.preventDefault();
                alert('Contact number must be exactly 10 digits');
                return false;
            }

            return true;
        });

        // Function to handle edit form submission with loading state
        function handleEditSubmit(event) {
            event.preventDefault();

            // Validate form
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

            // Show loading state
            const submitBtn = document.getElementById('editSubmitBtn');
            const submitText = submitBtn.querySelector('.submit-text');
            const loadingSpinner = submitBtn.querySelector('.loading-spinner');

            submitText.style.display = 'none';
            loadingSpinner.style.display = 'inline';
            submitBtn.disabled = true;

            // Submit the form
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
                    // Reset button state on error
                    submitText.style.display = 'inline';
                    loadingSpinner.style.display = 'none';
                    submitBtn.disabled = false;
                    alert('Error submitting form. Please try again.');
                });

            return false;
        }

        // Function to handle add remark form submission with loading state
        function handleAddRemarkSubmit(event) {
            event.preventDefault();

            // Show loading state
            const submitBtn = document.getElementById('addRemarkSubmitBtn');
            const submitText = submitBtn.querySelector('.submit-text');
            const loadingSpinner = submitBtn.querySelector('.loading-spinner');

            submitText.style.display = 'none';
            loadingSpinner.style.display = 'inline';
            submitBtn.disabled = true;

            // Submit the form
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
                    // Reset button state on error
                    submitText.style.display = 'inline';
                    loadingSpinner.style.display = 'none';
                    submitBtn.disabled = false;
                    alert('Error submitting remark. Please try again.');
                });

            return false;
        }

        // Function to reset modal forms when they are hidden
        document.addEventListener('DOMContentLoaded', function() {
            // Reset edit modal
            const editModal = document.getElementById('editModal');
            editModal.addEventListener('hidden.bs.modal', function() {
                const submitBtn = document.getElementById('editSubmitBtn');
                const submitText = submitBtn.querySelector('.submit-text');
                const loadingSpinner = submitBtn.querySelector('.loading-spinner');

                submitText.style.display = 'inline';
                loadingSpinner.style.display = 'none';
                submitBtn.disabled = false;
            });

            // Reset add remark modal
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