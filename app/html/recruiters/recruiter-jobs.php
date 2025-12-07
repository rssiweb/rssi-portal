<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_recruiters.php");

if (!isLoggedIn("rid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$recruiterId = $recruiter_id;

// Fetch recruiter basic info
$recruiterQuery = "SELECT full_name, company_name FROM recruiters WHERE id = $1";
$recruiterResult = pg_query_params($con, $recruiterQuery, [$recruiterId]);
$recruiter = pg_fetch_assoc($recruiterResult);

if (!$recruiter) {
    $_SESSION['error_message'] = "Recruiter not found!";
    header("Location: recruiter-management.php");
    exit;
}

// Get filter parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+1 month'));
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with filters
$jobsQuery = "SELECT 
                j.id,
                j.job_title,
                j.job_type,
                j.location,
                j.min_salary,
                j.max_salary,
                j.vacancies,
                j.status,
                j.created_at,
                j.apply_by,
                COUNT(ja.id) as applications_count
              FROM job_posts j
              LEFT JOIN job_applications ja ON j.id = ja.job_id
              WHERE j.recruiter_id = $1 
              AND j.status != 'deleted'"; // Exclude soft-deleted jobs

$params = [$recruiterId];
$paramCount = 1;

// Add date range filter
if ($startDate && $endDate) {
    $paramCount++;
    $jobsQuery .= " AND j.created_at BETWEEN $" . $paramCount . " AND $" . ($paramCount + 1);
    $params[] = $startDate . ' 00:00:00';
    $params[] = $endDate . ' 23:59:59';
    $paramCount++;
}

// Add status filter
if ($statusFilter && $statusFilter !== 'all') {
    $paramCount++;
    $jobsQuery .= " AND j.status = $" . $paramCount;
    $params[] = $statusFilter;
}

$jobsQuery .= " GROUP BY j.id ORDER BY j.created_at DESC";

$jobsResult = pg_query_params($con, $jobsQuery, $params);
$jobs = pg_fetch_all($jobsResult) ?: [];

// Get unique statuses for filter dropdown
$statusQuery = "SELECT DISTINCT status FROM job_posts WHERE recruiter_id = $1 AND status != 'deleted' ORDER BY status";
$statusResult = pg_query_params($con, $statusQuery, [$recruiterId]);
$statuses = pg_fetch_all($statusResult) ?: [];
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
    <title>All Jobs - <?php echo htmlspecialchars($recruiter['full_name']); ?></title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 10px;
        }

        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .filter-form .row {
            align-items: flex-end;
        }

        .filter-btn {
            min-width: 120px;
        }

        .three-dots {
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s;
        }

        .three-dots:hover {
            background-color: #f8f9fa;
        }

        .dropdown-menu {
            min-width: 160px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 8px;
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .dropdown-item.text-danger:hover {
            background-color: #dc3545;
            color: white !important;
        }

        .badge-status {
            padding: 0.5em 1em;
            font-size: 0.875rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>All Jobs by <?php echo htmlspecialchars($recruiter['full_name']); ?></h1>
                    <nav>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                            <li class="breadcrumb-item active">All Jobs</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <section class="section dashboard">
            <div class="row">
                <!-- Filters Card -->
                <div class="col-12">
                    <div class="filter-card">
                        <h5 class="mb-3">Filter Jobs</h5>
                        <form method="GET" class="filter-form">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="start_date" class="form-label">From Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date"
                                        value="<?php echo htmlspecialchars($startDate); ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date" class="form-label">To Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date"
                                        value="<?php echo htmlspecialchars($endDate); ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="all" <?php echo $statusFilter === 'all' || $statusFilter === '' ? 'selected' : ''; ?>>All Status</option>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?php echo htmlspecialchars($status['status']); ?>"
                                                <?php echo $statusFilter === $status['status'] ? 'selected' : ''; ?>>
                                                <?php echo ucfirst(htmlspecialchars($status['status'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary filter-btn w-100">
                                        <i class="bi bi-funnel"></i> Apply Filters
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <h5 class="card-title">All Jobs (<?php echo count($jobs); ?> jobs found)</h5>
                            <?php if (!empty($jobs)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Job Title</th>
                                                <th>Type</th>
                                                <th>Location</th>
                                                <th>Salary</th>
                                                <th>Vacancies</th>
                                                <th>Applications</th>
                                                <th>Status</th>
                                                <th>Posted Date</th>
                                                <th>Apply By</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($jobs as $job): ?>
                                                <?php
                                                $jobStatus = $job['status'];
                                                $statusClass = '';
                                                if ($jobStatus === 'active') $statusClass = 'bg-success';
                                                elseif ($jobStatus === 'pending') $statusClass = 'bg-warning text-dark';
                                                elseif ($jobStatus === 'rejected') $statusClass = 'bg-danger';
                                                else $statusClass = 'bg-secondary';
                                                ?>
                                                <tr>
                                                    <td>
                                                        <a href="job_view.php?id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($job['job_title']); ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?></td>
                                                    <td><?php echo htmlspecialchars($job['location']); ?></td>
                                                    <td>
                                                        ₹<?php echo number_format($job['min_salary']); ?> -
                                                        ₹<?php echo number_format($job['max_salary']); ?>
                                                    </td>
                                                    <td><?php echo $job['vacancies']; ?></td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?php echo $job['applications_count']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php echo $statusClass; ?>">
                                                            <?php echo ucfirst($jobStatus); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d M Y', strtotime($job['created_at'])); ?></td>
                                                    <td><?php echo date('d M Y', strtotime($job['apply_by'])); ?></td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <div class="three-dots" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="bi bi-three-dots-vertical"></i>
                                                            </div>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <a class="dropdown-item" href="job_view.php?id=<?php echo $job['id']; ?>">
                                                                        <i class="bi bi-eye"></i> View Job
                                                                    </a>
                                                                </li>
                                                                <!-- <li>
                                                                    <a class="dropdown-item" href="job-edit.php?id=<?php echo $job['id']; ?>">
                                                                        <i class="bi bi-pencil"></i> Edit Job
                                                                    </a>
                                                                </li> -->
                                                                <li>
                                                                    <hr class="dropdown-divider">
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger delete-job-btn" href="#"
                                                                        data-job-id="<?php echo $job['id']; ?>"
                                                                        data-job-title="<?php echo htmlspecialchars($job['job_title']); ?>">
                                                                        <i class="bi bi-trash"></i> Delete Job
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
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-briefcase" style="font-size: 3rem; color: #dee2e6;"></i>
                                    <h5 class="mt-3">No Jobs Found</h5>
                                    <p>No jobs found with the selected filters.</p>
                                    <a href="?start_date=<?php echo date('Y-m-d', strtotime('-1 month')); ?>&end_date=<?php echo date('Y-m-d', strtotime('+1 month')); ?>&status=all"
                                        class="btn btn-outline-primary">
                                        Clear Filters
                                    </a>
                                    <a href="job-add.php?recruiter_id=<?php echo $recruiterId; ?>" class="btn btn-primary ms-2">
                                        <i class="bi bi-plus-circle"></i> Create New Job
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteJobModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the job "<span id="deleteJobTitle"></span>"?</p>
                        <p class="text-danger"><small>This action will mark the job as deleted. This action cannot be undone.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Job</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Toast -->
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
            <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body" id="toastMessage"></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        // API endpoint configuration
        const API_BASE = window.location.hostname === 'localhost' ?
            'http://localhost:8082/job-api/' :
            'https://login.rssi.in/job-api/';

        let jobToDelete = null;
        let jobTitleToDelete = '';

        $(document).ready(function() {
            // Initialize DataTable for jobs table
            $('table').DataTable({
                responsive: true,
                pageLength: 10,
                searching: true,
                lengthChange: true,
                info: true,
                paging: <?php echo count($jobs) > 5 ? 'true' : 'false'; ?>
            });

            // Handle delete job button click
            $(document).on('click', '.delete-job-btn', function(e) {
                e.preventDefault();
                jobToDelete = $(this).data('job-id');
                jobTitleToDelete = $(this).data('job-title');

                $('#deleteJobTitle').text(jobTitleToDelete);
                $('#deleteJobModal').modal('show');
            });

            // Handle confirm delete button click
            $('#confirmDeleteBtn').click(function() {
                if (jobToDelete) {
                    deleteJob(jobToDelete);
                }
            });

            // Set default date range (1 month before and after current month)
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const lastDay = new Date(today.getFullYear(), today.getMonth() + 2, 0);

            // Format dates as YYYY-MM-DD
            const formatDate = (date) => {
                return date.toISOString().split('T')[0];
            };

            // Set default values if not already set
            if (!$('#start_date').val()) {
                $('#start_date').val(formatDate(firstDay));
            }
            if (!$('#end_date').val()) {
                $('#end_date').val(formatDate(lastDay));
            }

            // Validate date range
            $('form').submit(function(e) {
                const startDate = new Date($('#start_date').val());
                const endDate = new Date($('#end_date').val());

                if (startDate > endDate) {
                    e.preventDefault();
                    alert('Start date cannot be after end date.');
                    return false;
                }
            });
        });

        function deleteJob(jobId) {
            $('#confirmDeleteBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...');

            $.ajax({
                url: API_BASE + 'delete_job.php',
                type: 'POST',
                data: {
                    id: jobId,
                    action: 'soft_delete'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Job deleted successfully!', 'success');
                        $('#deleteJobModal').modal('hide');

                        // Remove the row from table
                        $(`[data-job-id="${jobId}"]`).closest('tr').fadeOut(400, function() {
                            $(this).remove();
                            // Reload page after 1.5 seconds to refresh counts
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        });
                    } else {
                        showToast('Failed to delete: ' + response.message, 'danger');
                        $('#confirmDeleteBtn').prop('disabled', false).text('Delete Job');
                    }
                },
                error: function() {
                    showToast('Error deleting job. Please try again.', 'danger');
                    $('#confirmDeleteBtn').prop('disabled', false).text('Delete Job');
                }
            });
        }

        // Function to show toast messages
        function showToast(message, type = 'success') {
            const toast = $('#successToast');
            const toastMessage = $('#toastMessage');

            // Set message
            toastMessage.text(message);

            // Set color based on type
            if (type === 'danger' || type === 'error') {
                toast.removeClass('bg-success bg-info bg-warning').addClass('bg-danger');
            } else if (type === 'warning') {
                toast.removeClass('bg-success bg-info bg-danger').addClass('bg-warning');
            } else if (type === 'info') {
                toast.removeClass('bg-success bg-danger bg-warning').addClass('bg-info');
            } else {
                toast.removeClass('bg-danger bg-info bg-warning').addClass('bg-success');
            }

            // Show toast
            const bsToast = new bootstrap.Toast(toast[0]);
            bsToast.show();
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>

</html>