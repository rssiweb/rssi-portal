<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_id = $_POST['job_id'] ?? '';
    $action = $_POST['action'] ?? '';
    $rejection_reason = $_POST['rejection_reason'] ?? '';

    if (!empty($job_id) && !empty($action)) {
        if ($action === 'approve') {
            $query = "UPDATE job_posts SET status = 'approved', updated_at = NOW(), reviewed_by='$associatenumber' WHERE id = $1";
            $result = pg_query_params($con, $query, [$job_id]);

            if ($result) {
                // Send approval email to recruiter
                $jobQuery = "SELECT jp.*, r.email, r.full_name, r.company_name 
                           FROM job_posts jp 
                           JOIN recruiters r ON jp.recruiter_id = r.id 
                           WHERE jp.id = $1";
                $jobResult = pg_query_params($con, $jobQuery, [$job_id]);

                if ($jobResult && pg_num_rows($jobResult) > 0) {
                    $jobData = pg_fetch_assoc($jobResult);

                    $emailData = [
                        "recruiter_name" => $jobData['full_name'],
                        "job_title" => $jobData['job_title'],
                        "company_name" => $jobData['company_name'],
                        "job_id" => $jobData['id'],
                        "approval_date" => date("d/m/Y g:i a"),
                        "job_link" => "https://www.rssi.in/job_details?id=" . $jobData['id']
                    ];

                    sendEmail("job_approval_notification", $emailData, $jobData['email'], false);
                }

                $_SESSION['success_message'] = "Job approved successfully!";
            }
        } elseif ($action === 'reject') {
            $query = "UPDATE job_posts SET status = 'rejected', rejection_reason = $2, updated_at = NOW(), reviewed_by='$associatenumber' WHERE id = $1";
            $result = pg_query_params($con, $query, [$job_id, $rejection_reason]);

            if ($result) {
                // Send rejection email to recruiter
                $jobQuery = "SELECT jp.*, r.email, r.full_name, r.company_name 
                           FROM job_posts jp 
                           JOIN recruiters r ON jp.recruiter_id = r.id 
                           WHERE jp.id = $1";
                $jobResult = pg_query_params($con, $jobQuery, [$job_id]);

                if ($jobResult && pg_num_rows($jobResult) > 0) {
                    $jobData = pg_fetch_assoc($jobResult);

                    $emailData = [
                        "recruiter_name" => $jobData['full_name'],
                        "job_title" => $jobData['job_title'],
                        "company_name" => $jobData['company_name'],
                        "job_id" => $jobData['id'],
                        "rejection_date" => date("d/m/Y g:i a"),
                        "rejection_reason" => $rejection_reason
                    ];

                    sendEmail("job_rejection_notification", $emailData, $jobData['email'], false);
                }

                $_SESSION['success_message'] = "Job rejected successfully!";
            }
        }
    }
}

// Fetch pending jobs for approval
$query = "SELECT jp.*, r.full_name as recruiter_name, r.company_name, r.email as recruiter_email, r.phone as recruiter_phone
          FROM job_posts jp 
          JOIN recruiters r ON jp.recruiter_id = r.id 
          WHERE jp.status = 'pending'
          ORDER BY jp.created_at DESC";

$result = pg_query($con, $query);
$pendingJobs = $result ? pg_fetch_all($result) : [];

// Handle filter parameters for history
$status_filter = $_GET['status'] ?? 'all'; // 'all', 'approved', 'rejected'
$start_date = $_GET['start_date'] ?? date('Y-m-01', strtotime('-1 month'));
$end_date = $_GET['end_date'] ?? date('Y-m-t', strtotime('+1 month'));

// Build query for history with filters
$historyQuery = "SELECT jp.*, r.full_name as recruiter_name, r.company_name 
                 FROM job_posts jp 
                 JOIN recruiters r ON jp.recruiter_id = r.id 
                 WHERE jp.status IN ('approved', 'rejected')";

// Add status filter
if ($status_filter !== 'all') {
    $historyQuery .= " AND jp.status = '" . pg_escape_string($status_filter) . "'";
}

// Add date range filter
if (!empty($start_date) && !empty($end_date)) {
    $escaped_start_date = pg_escape_string($con, $start_date);
    $escaped_end_date = pg_escape_string($con, $end_date);
    $historyQuery .= " AND DATE(jp.updated_at) BETWEEN '$escaped_start_date' AND '$escaped_end_date'";
}

$historyQuery .= " ORDER BY jp.updated_at DESC 
                   LIMIT 100"; // Increased limit to accommodate filtered results

$historyResult = pg_query($con, $historyQuery);
$historyJobs = $historyResult ? pg_fetch_all($historyResult) : [];
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

    <title>Job Approval Management</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <style>
        .job-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .job-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .job-card.pending {
            border-left: 5px solid #ffc107;
        }

        .job-card.approved {
            border-left: 5px solid #28a745;
        }

        .job-card.rejected {
            border-left: 5px solid #dc3545;
        }

        .status-badge {
            font-size: 0.8em;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Job Approval Management</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Job Assistance</a></li>
                    <li class="breadcrumb-item"><a href="job-admin.php">Job Admin Panel</a></li>
                    <li class="breadcrumb-item active">Job Approval</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body mt-3">
                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo $_SESSION['success_message']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['success_message']); ?>
                            <?php endif; ?>
                            <!-- Summary Statistics -->
                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-primary">Total Records</h5>
                                            <h3 class="mb-0"><?php echo count($historyJobs); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-success">Approved</h5>
                                            <h3 class="mb-0">
                                                <?php
                                                $approved_count = 0;
                                                foreach ($historyJobs as $job) {
                                                    if ($job['status'] === 'approved') $approved_count++;
                                                }
                                                echo $approved_count;
                                                ?>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h5 class="card-title text-danger">Rejected</h5>
                                            <h3 class="mb-0">
                                                <?php
                                                $rejected_count = 0;
                                                foreach ($historyJobs as $job) {
                                                    if ($job['status'] === 'rejected') $rejected_count++;
                                                }
                                                echo $rejected_count;
                                                ?>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Pending Jobs Tab -->
                            <div class="col-lg-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Pending Jobs for Approval</h5>

                                        <?php if (empty($pendingJobs)): ?>
                                            <div class="alert alert-info">
                                                No pending jobs for approval.
                                            </div>
                                        <?php else: ?>
                                            <div class="row">
                                                <?php foreach ($pendingJobs as $job): ?>
                                                    <div class="col-lg-6">
                                                        <div class="job-card pending">
                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                <div>
                                                                    <h5 class="mb-1"><?php echo htmlspecialchars($job['job_title']); ?></h5>
                                                                    <p class="text-muted mb-1">
                                                                        <i class="bi bi-building"></i> <?php echo htmlspecialchars($job['company_name']); ?>
                                                                    </p>
                                                                    <p class="text-muted mb-1">
                                                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($job['recruiter_name']); ?>
                                                                    </p>
                                                                </div>
                                                                <span class="status-badge status-pending">Pending</span>
                                                            </div>

                                                            <div class="mb-3">
                                                                <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
                                                                <p class="mb-1"><strong>Job Type:</strong> <?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?></p>
                                                                <p class="mb-1"><strong>Vacancies:</strong> <?php echo $job['vacancies']; ?></p>
                                                                <p class="mb-1"><strong>Apply By:</strong> <?php echo date('d/m/Y', strtotime($job['apply_by'])); ?></p>
                                                                <p class="mb-1"><strong>Salary:</strong>
                                                                    <?php
                                                                    echo ($job['min_salary'] && $job['max_salary'])
                                                                        ? '₹' . $job['min_salary'] . ' - ₹' . $job['max_salary'] . ' per month'
                                                                        : ($job['min_salary']
                                                                            ? '₹' . $job['min_salary'] . ' per month'
                                                                            : ($job['max_salary']
                                                                                ? 'Up to ₹' . $job['max_salary'] . ' per month'
                                                                                : 'Not specified'
                                                                            )
                                                                        );
                                                                    ?>
                                                                </p>
                                                            </div>

                                                            <div class="mb-3">
                                                                <h6>Job Description:</h6>
                                                                <p><?php echo nl2br(htmlspecialchars(substr($job['job_description'], 0, 200))) . '...'; ?></p>
                                                            </div>

                                                            <div class="mb-3">
                                                                <h6>Recruiter Contact:</h6>
                                                                <p class="mb-1"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($job['recruiter_email']); ?></p>
                                                                <p class="mb-1"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($job['recruiter_phone']); ?></p>
                                                            </div>

                                                            <div class="d-flex justify-content-between">
                                                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $job['id']; ?>">
                                                                    <i class="bi bi-check-circle"></i> Approve
                                                                </button>
                                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $job['id']; ?>">
                                                                    <i class="bi bi-x-circle"></i> Reject
                                                                </button>
                                                                <a href="job_view.php?id=<?php echo $job['id']; ?>" class="btn btn-info btn-sm">
                                                                    <i class="bi bi-eye"></i> View Details
                                                                </a>
                                                            </div>
                                                        </div>

                                                        <!-- Approve Modal -->
                                                        <div class="modal fade" id="approveModal<?php echo $job['id']; ?>" tabindex="-1">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Approve Job Post</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p>Are you sure you want to approve this job post?</p>
                                                                        <p><strong><?php echo htmlspecialchars($job['job_title']); ?></strong></p>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <form method="POST" style="display: inline;">
                                                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                                            <input type="hidden" name="action" value="approve">
                                                                            <button type="submit" class="btn btn-success">Yes, Approve</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Reject Modal -->
                                                        <div class="modal fade" id="rejectModal<?php echo $job['id']; ?>" tabindex="-1">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Reject Job Post</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form method="POST">
                                                                        <div class="modal-body">
                                                                            <p>Are you sure you want to reject this job post?</p>
                                                                            <p><strong><?php echo htmlspecialchars($job['job_title']); ?></strong></p>

                                                                            <div class="mb-3">
                                                                                <label for="rejection_reason" class="form-label">Reason for Rejection (Optional)</label>
                                                                                <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" placeholder="Enter reason for rejection..."></textarea>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                                            <input type="hidden" name="action" value="reject">
                                                                            <button type="submit" class="btn btn-danger">Yes, Reject</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Job History Tab -->
                            <div class="col-lg-12 mt-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Job Approval History</h5>

                                        <!-- Filter Form -->
                                        <div class="card mb-4">
                                            <div class="card-body">
                                                <form method="GET" class="row g-3 align-items-end" id="filterForm">
                                                    <div class="col-md-3">
                                                        <label for="status" class="form-label">Status</label>
                                                        <select class="form-select" id="status" name="status">
                                                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label for="start_date" class="form-label">From Date</label>
                                                        <input type="date" class="form-control" id="start_date" name="start_date"
                                                            value="<?php echo htmlspecialchars($start_date); ?>">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label for="end_date" class="form-label">To Date</label>
                                                        <input type="date" class="form-control" id="end_date" name="end_date"
                                                            value="<?php echo htmlspecialchars($end_date); ?>">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="d-flex gap-2">
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="bi bi-funnel"></i> Apply Filters
                                                            </button>
                                                            <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                                                                <i class="bi bi-arrow-clockwise"></i> Reset
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <?php if (empty($historyJobs)): ?>
                                            <div class="alert alert-info">
                                                No job history available for the selected filters.
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover" id="jobHistoryTable">
                                                    <thead>
                                                        <tr>
                                                            <th>Job Title</th>
                                                            <th>Company</th>
                                                            <th>Recruiter</th>
                                                            <th>Location</th>
                                                            <th>Status</th>
                                                            <th>Posted Date</th>
                                                            <th>Updated Date</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($historyJobs as $job): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($job['job_title']); ?></td>
                                                                <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($job['recruiter_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($job['location']); ?></td>
                                                                <td>
                                                                    <?php if ($job['status'] === 'approved'): ?>
                                                                        <span class="status-badge status-approved">Approved</span>
                                                                    <?php else: ?>
                                                                        <span class="status-badge status-rejected">Rejected</span>
                                                                        <?php if (!empty($job['rejection_reason'])): ?>
                                                                            <br><small class="text-muted" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($job['rejection_reason']); ?>">
                                                                                Reason: <?php echo htmlspecialchars(substr($job['rejection_reason'], 0, 30)) . (strlen($job['rejection_reason']) > 30 ? '...' : ''); ?>
                                                                            </small>
                                                                        <?php endif; ?>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td><?php echo date('d/m/Y', strtotime($job['created_at'])); ?></td>
                                                                <td><?php echo date('d/m/Y', strtotime($job['updated_at'])); ?></td>
                                                                <td>
                                                                    <div class="dropdown">
                                                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                            <i class="bi bi-three-dots-vertical"></i>
                                                                        </button>

                                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                                            <li>
                                                                                <a class="dropdown-item" href="job_view.php?id=<?php echo $job['id']; ?>">
                                                                                    <i class="bi bi-eye me-2"></i> View Job
                                                                                </a>
                                                                            </li>
                                                                            <li>
                                                                                <a class="dropdown-item" href="job-edit.php?id=<?php echo $job['id']; ?>">
                                                                                    <i class="bi bi-pencil-square me-2"></i> Edit Job
                                                                                </a>
                                                                            </li>
                                                                        </ul>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable for job history
            $('#jobHistoryTable').DataTable({
                "order": [
                    [6, "desc"]
                ], // Sort by updated date descending
                "pageLength": 25,
                "dom": '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
                "language": {
                    "search": "Search records:",
                    "lengthMenu": "Show _MENU_ records per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ records",
                    "infoEmpty": "Showing 0 to 0 of 0 records",
                    "infoFiltered": "(filtered from _MAX_ total records)"
                }
            });

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        function resetFilters() {
            // Set default dates (1 month before and 1 month after current month)
            var today = new Date();
            var startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            var endDate = new Date(today.getFullYear(), today.getMonth() + 2, 0);

            // Format dates as YYYY-MM-DD
            document.getElementById('start_date').value = formatDate(startDate);
            document.getElementById('end_date').value = formatDate(endDate);
            document.getElementById('status').value = 'all';

            // Submit the form
            document.getElementById('filterForm').submit();
        }

        function formatDate(date) {
            var year = date.getFullYear();
            var month = (date.getMonth() + 1).toString().padStart(2, '0');
            var day = date.getDate().toString().padStart(2, '0');
            return year + '-' + month + '-' + day;
        }

        // Set max date for end_date to prevent future dates
        document.addEventListener('DOMContentLoaded', function() {
            var today = new Date();
            var maxDate = today.toISOString().split('T')[0];
            document.getElementById('end_date').max = maxDate;

            // Validate date range
            document.getElementById('filterForm').addEventListener('submit', function(e) {
                var startDate = new Date(document.getElementById('start_date').value);
                var endDate = new Date(document.getElementById('end_date').value);

                if (startDate > endDate) {
                    e.preventDefault();
                    alert('Start date cannot be after end date!');
                    return false;
                }

                // Limit date range to 6 months
                var sixMonthsLater = new Date(startDate);
                sixMonthsLater.setMonth(sixMonthsLater.getMonth() + 6);

                if (endDate > sixMonthsLater) {
                    e.preventDefault();
                    alert('Date range cannot exceed 6 months!');
                    return false;
                }
            });
        });
    </script>

</body>

</html>