<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Check if recruiter ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: recruiter-management.php");
    exit;
}

$recruiterId = $_GET['id'];

// Fetch recruiter basic info
$recruiterQuery = "SELECT full_name, company_name FROM recruiters WHERE id = $1";
$recruiterResult = pg_query_params($con, $recruiterQuery, [$recruiterId]);
$recruiter = pg_fetch_assoc($recruiterResult);

if (!$recruiter) {
    $_SESSION['error_message'] = "Recruiter not found!";
    header("Location: recruiter-management.php");
    exit;
}

// Fetch all jobs by this recruiter
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
              GROUP BY j.id
              ORDER BY j.created_at DESC";

$jobsResult = pg_query_params($con, $jobsQuery, [$recruiterId]);
$jobs = pg_fetch_all($jobsResult) ?: [];
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

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #667eea;
            border: 4px solid white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .info-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            height: 100%;
        }

        .info-card:hover {
            transform: translateY(-5px);
        }

        .info-card .card-body {
            padding: 1.5rem;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 1.1rem;
            color: #212529;
            margin-bottom: 1rem;
        }

        .badge-status {
            padding: 0.5em 1em;
            font-size: 0.875rem;
        }

        .stats-card {
            text-align: center;
            padding: 1.5rem;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline:before {
            content: '';
            position: absolute;
            left: 7px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .timeline-item:before {
            content: '';
            position: absolute;
            left: -2.1rem;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            border: 2px solid white;
            box-shadow: 0 0 0 3px #e9ecef;
        }

        .timeline-date {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .timeline-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .timeline-content {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .action-buttons {
            position: sticky;
            top: 20px;
            z-index: 100;
        }

        .document-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s;
        }

        .document-link:hover {
            background: #e9ecef;
            color: #212529;
            text-decoration: none;
        }

        .notes-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 1rem;
            border-radius: 5px;
            white-space: pre-wrap;
            font-family: inherit;
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
                            <li class="breadcrumb-item"><a href="job-admin.php">Job Admin</a></li>
                            <li class="breadcrumb-item"><a href="recruiter-management.php">Recruiter Management</a></li>
                            <li class="breadcrumb-item"><a href="recruiter-details.php?id=<?php echo $recruiterId; ?>">
                                    <?php echo htmlspecialchars($recruiter['full_name']); ?>
                                </a></li>
                            <li class="breadcrumb-item active">All Jobs</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="recruiter-details.php?id=<?php echo $recruiterId; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Details
                    </a>
                    <a href="job-add.php?recruiter_id=<?php echo $recruiterId; ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add New Job
                    </a>
                </div>
            </div>
        </div>

        <section class="section dashboard">
            <div class="row">

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
                                                        <div class="btn-group">
                                                            <a href="job_view.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="job-edit.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
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
                                    <p>This recruiter hasn't posted any jobs yet.</p>
                                    <a href="job-add.php?recruiter_id=<?php echo $recruiterId; ?>" class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Create First Job
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        // API endpoint configuration
        const API_BASE = window.location.hostname === 'localhost' ?
            'http://localhost:8082/' :
            'https://login.rssi.in/';
        $(document).ready(function() {
            // Initialize DataTable for jobs table
            $('table').DataTable({
                responsive: true,
                pageLength: 5,
                searching: false,
                lengthChange: false,
                info: false,
                paging: <?php echo count($jobs) > 5 ? 'true' : 'false'; ?>
            });

            // Save recruiter changes
            $('#saveRecruiterBtn').click(function() {
                saveRecruiterChanges();
            });

            // Reset save button when modal is closed
            $('#editRecruiterModal').on('hidden.bs.modal', function() {
                resetSaveButton();
            });
        });

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
                        // Show success toast
                        showToast('Recruiter updated successfully!', 'success');

                        // Close modal after a short delay
                        setTimeout(function() {
                            $('#editRecruiterModal').modal('hide');
                            // Reset button state
                            resetSaveButton();
                            // Reload page to show updated data
                            location.reload();
                        }, 1500);
                    } else {
                        showToast('Failed to update: ' + response.message, 'danger');
                        // Re-enable button on error
                        resetSaveButton();
                    }
                },
                error: function(xhr, status, error) {
                    showToast('Error updating recruiter. Please try again.', 'danger');
                    // Re-enable button on error
                    resetSaveButton();
                }
            });
        }

        // Helper function to reset save button state
        function resetSaveButton() {
            const saveBtn = $('#saveRecruiterBtn');
            const btnText = saveBtn.find('.btn-text');
            const spinner = saveBtn.find('.spinner-border');

            saveBtn.prop('disabled', false);
            btnText.text('Save Changes');
            spinner.hide();
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

        function confirmDelete(recruiterId) {
            if (confirm('Are you sure you want to delete this recruiter? This will also delete all associated jobs. This action cannot be undone.')) {
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
                        showToast('Recruiter deleted successfully!', 'success');
                        setTimeout(function() {
                            window.location.href = 'recruiter-management.php';
                        }, 1500);
                    } else {
                        showToast('Failed to delete: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showToast('Error deleting recruiter. Please try again.', 'danger');
                }
            });
        }

        // Copy contact info to clipboard
        function copyToClipboard(text, type) {
            navigator.clipboard.writeText(text).then(function() {
                showToast(type + ' copied to clipboard!', 'success');
            }, function() {
                showToast('Failed to copy to clipboard', 'danger');
            });
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>

</html>