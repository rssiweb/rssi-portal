<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
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

// Fetch recruiter details
$query = "SELECT 
            id, 
            full_name, 
            company_name, 
            email, 
            phone, 
            aadhar_number,
            aadhar_file_path,
            company_address,
            is_verified,
            is_active,
            created_at,
            updated_at,
            notes
          FROM recruiters 
          WHERE id = $1";

$result = pg_query_params($con, $query, [$recruiterId]);

if (!$result || pg_num_rows($result) === 0) {
    $_SESSION['error_message'] = "Recruiter not found!";
    header("Location: recruiter-management.php");
    exit;
}

$recruiter = pg_fetch_assoc($result);

// Convert boolean values for display
$isActive = ($recruiter['is_active'] === 't' || $recruiter['is_active'] === true || $recruiter['is_active'] === 'true');
$isVerified = ($recruiter['is_verified'] === 't' || $recruiter['is_verified'] === true || $recruiter['is_verified'] === 'true');

// Get jobs posted by this recruiter
$jobsQuery = "SELECT 
                id,
                job_title,
                job_type,
                location,
                min_salary,
                max_salary,
                vacancies,
                status,
                created_at,
                apply_by
              FROM job_posts 
              WHERE recruiter_id = $1 
              ORDER BY created_at DESC
              LIMIT 10";

$jobsResult = pg_query_params($con, $jobsQuery, [$recruiterId]);
$jobs = pg_fetch_all($jobsResult) ?: [];

// Get total job count
$totalJobsQuery = "SELECT COUNT(*) as total_jobs FROM job_posts WHERE recruiter_id = $1";
$totalJobsResult = pg_query_params($con, $totalJobsQuery, [$recruiterId]);
$totalJobs = pg_fetch_result($totalJobsResult, 0, 'total_jobs');

// Get active jobs count
$activeJobsQuery = "SELECT COUNT(*) as active_jobs FROM job_posts WHERE recruiter_id = $1 AND status = 'active'";
$activeJobsResult = pg_query_params($con, $activeJobsQuery, [$recruiterId]);
$activeJobs = pg_fetch_result($activeJobsResult, 0, 'active_jobs');
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

    <title>Recruiter Details - <?php echo htmlspecialchars($recruiter['full_name']); ?></title>

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
                    <h1>Recruiter Details</h1>
                    <nav>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="job-admin.php">Job Admin</a></li>
                            <li class="breadcrumb-item"><a href="recruiter-management.php">Recruiter Management</a></li>
                            <li class="breadcrumb-item active">Recruiter Details</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="recruiter-management.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editRecruiterModal">
                        <i class="bi bi-pencil"></i> Edit Recruiter
                    </a>
                </div>
            </div>
        </div><!-- End Page Title -->
        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <!-- Profile Header -->
                            <div class="profile-header">
                                <div class="container">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="profile-avatar">
                                                <i class="bi bi-person-badge"></i>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <h2 class="mb-1"><?php echo htmlspecialchars($recruiter['full_name']); ?></h2>
                                            <p class="mb-2">
                                                <i class="bi bi-building me-1"></i>
                                                <?php echo htmlspecialchars($recruiter['company_name']); ?>
                                            </p>
                                            <div class="d-flex gap-2">
                                                <?php if ($isActive): ?>
                                                    <span class="badge bg-success badge-status">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger badge-status">Inactive</span>
                                                <?php endif; ?>

                                                <?php if ($isVerified): ?>
                                                    <span class="badge bg-primary badge-status">Verified</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark badge-status">Unverified</span>
                                                <?php endif; ?>

                                                <span class="badge bg-info badge-status">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    Joined: <?php echo date('d M Y', strtotime($recruiter['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <section class="section">
                                <div class="container">
                                    <div class="row">
                                        <!-- Left Column: Basic Information -->
                                        <div class="col-lg-8">
                                            <div class="row">
                                                <!-- Contact Information Card -->
                                                <div class="col-md-6 mb-4">
                                                    <div class="card info-card">
                                                        <div class="card-body">
                                                            <h5 class="card-title">
                                                                <i class="bi bi-telephone text-primary me-2"></i>Contact Information
                                                            </h5>
                                                            <div class="mb-3">
                                                                <div class="info-label">Email Address</div>
                                                                <div class="info-value">
                                                                    <a href="mailto:<?php echo htmlspecialchars($recruiter['email']); ?>">
                                                                        <?php echo htmlspecialchars($recruiter['email']); ?>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <div class="info-label">Phone Number</div>
                                                                <div class="info-value">
                                                                    <a href="tel:<?php echo htmlspecialchars($recruiter['phone']); ?>">
                                                                        <?php echo htmlspecialchars($recruiter['phone']); ?>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                            <?php if (!empty($recruiter['aadhar_number'])): ?>
                                                                <div class="mb-3">
                                                                    <div class="info-label">Aadhar Number</div>
                                                                    <div class="info-value">
                                                                        <?php echo htmlspecialchars($recruiter['aadhar_number']); ?>
                                                                        <?php if (!empty($recruiter['aadhar_file_path'])): ?>
                                                                            <br>
                                                                            <a href="<?php echo htmlspecialchars($recruiter['aadhar_file_path']); ?>"
                                                                                target="_blank" class="document-link mt-2">
                                                                                <i class="bi bi-file-earmark-pdf"></i> View Aadhar Document
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Company Information Card -->
                                                <div class="col-md-6 mb-4">
                                                    <div class="card info-card">
                                                        <div class="card-body">
                                                            <h5 class="card-title">
                                                                <i class="bi bi-building text-success me-2"></i>Company Information
                                                            </h5>
                                                            <div class="mb-3">
                                                                <div class="info-label">Company Name</div>
                                                                <div class="info-value"><?php echo htmlspecialchars($recruiter['company_name']); ?></div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <div class="info-label">Company Address</div>
                                                                <div class="info-value"><?php echo nl2br(htmlspecialchars($recruiter['company_address'])); ?></div>
                                                            </div>
                                                            <?php if (!empty($recruiter['created_by'])): ?>
                                                                <div class="mb-3">
                                                                    <div class="info-label">Created By</div>
                                                                    <div class="info-value">
                                                                        <?php
                                                                        $createdBy = htmlspecialchars($recruiter['created_by']);
                                                                        echo $createdBy === 'admin' ? 'Admin' : ucfirst($createdBy);
                                                                        ?>
                                                                        <?php if (!empty($recruiter['admin_id'])): ?>
                                                                            (ID: <?php echo htmlspecialchars($recruiter['admin_id']); ?>)
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Notes Card -->
                                                <?php if (!empty($recruiter['notes'])): ?>
                                                    <div class="col-12 mb-4">
                                                        <div class="card info-card">
                                                            <div class="card-body">
                                                                <h5 class="card-title">
                                                                    <i class="bi bi-sticky text-warning me-2"></i>Admin Notes
                                                                </h5>
                                                                <div class="notes-box">
                                                                    <?php echo nl2br(htmlspecialchars($recruiter['notes'])); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Recent Jobs Card -->
                                                <div class="col-12 mb-4">
                                                    <div class="card info-card">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                                <h5 class="card-title mb-0">
                                                                    <i class="bi bi-briefcase text-info me-2"></i>Recent Jobs Posted
                                                                </h5>
                                                                <span class="badge bg-primary">
                                                                    Total: <?php echo $totalJobs; ?> | Active: <?php echo $activeJobs; ?>
                                                                </span>
                                                            </div>

                                                            <?php if (!empty($jobs)): ?>
                                                                <div class="table-responsive">
                                                                    <table class="table table-hover">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Job Title</th>
                                                                                <th>Type</th>
                                                                                <th>Location</th>
                                                                                <th>Salary</th>
                                                                                <th>Status</th>
                                                                                <th>Posted Date</th>
                                                                                <th>Apply By</th>
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
                                                                                    <td>
                                                                                        <span class="badge <?php echo $statusClass; ?>">
                                                                                            <?php echo ucfirst($jobStatus); ?>
                                                                                        </span>
                                                                                    </td>
                                                                                    <td><?php echo date('d M Y', strtotime($job['created_at'])); ?></td>
                                                                                    <td><?php echo date('d M Y', strtotime($job['apply_by'])); ?></td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                                <?php if ($totalJobs > 10): ?>
                                                                    <div class="text-center mt-3">
                                                                        <a href="recruiter-jobs.php?id=<?php echo $recruiterId; ?>" class="btn btn-outline-primary">
                                                                            View All Jobs (<?php echo $totalJobs; ?>)
                                                                        </a>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <div class="empty-state">
                                                                    <i class="bi bi-briefcase"></i>
                                                                    <h5>No Jobs Posted</h5>
                                                                    <p>This recruiter hasn't posted any jobs yet.</p>
                                                                    <a href="job-add.php?recruiter_id=<?php echo $recruiterId; ?>" class="btn btn-primary">
                                                                        <i class="bi bi-plus-circle"></i> Create First Job
                                                                    </a>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Column: Stats & Timeline -->
                                        <div class="col-lg-4">
                                            <div style="position: relative;">
                                                <!-- Statistics Card -->
                                                <div class="card info-card mb-4">
                                                    <div class="card-body">
                                                        <h5 class="card-title text-center mb-4">
                                                            <i class="bi bi-bar-chart text-purple me-2"></i>Statistics
                                                        </h5>
                                                        <div class="row text-center">
                                                            <div class="col-6 mb-4">
                                                                <div class="stats-card">
                                                                    <div class="stats-number"><?php echo $totalJobs; ?></div>
                                                                    <div class="stats-label">Total Jobs</div>
                                                                </div>
                                                            </div>
                                                            <div class="col-6 mb-4">
                                                                <div class="stats-card">
                                                                    <div class="stats-number"><?php echo $activeJobs; ?></div>
                                                                    <div class="stats-label">Active Jobs</div>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="stats-card">
                                                                    <div class="stats-number">
                                                                        <?php echo date('d M Y', strtotime($recruiter['created_at'])); ?>
                                                                    </div>
                                                                    <div class="stats-label">Joined Date</div>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="stats-card">
                                                                    <div class="stats-number">
                                                                        <?php
                                                                        $days = floor((time() - strtotime($recruiter['created_at'])) / (60 * 60 * 24));
                                                                        echo $days;
                                                                        ?>
                                                                    </div>
                                                                    <div class="stats-label">Days Active</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Timeline Card -->
                                                <div class="card info-card">
                                                    <div class="card-body">
                                                        <h5 class="card-title mb-4">
                                                            <i class="bi bi-clock-history text-info me-2"></i>Activity Timeline
                                                        </h5>
                                                        <div class="timeline">
                                                            <div class="timeline-item">
                                                                <div class="timeline-date">
                                                                    <?php echo date('d M Y, h:i A', strtotime($recruiter['created_at'])); ?>
                                                                </div>
                                                                <div class="timeline-title">Account Created</div>
                                                                <div class="timeline-content">
                                                                    Recruiter account was created in the system
                                                                </div>
                                                            </div>

                                                            <?php if (!empty($recruiter['updated_at']) && $recruiter['updated_at'] !== $recruiter['created_at']): ?>
                                                                <div class="timeline-item">
                                                                    <div class="timeline-date">
                                                                        <?php echo date('d M Y, h:i A', strtotime($recruiter['updated_at'])); ?>
                                                                    </div>
                                                                    <div class="timeline-title">Last Updated</div>
                                                                    <div class="timeline-content">
                                                                        Profile information was last updated
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>

                                                            <?php if (!empty($jobs)): ?>
                                                                <?php
                                                                // Get latest job
                                                                $latestJob = $jobs[0];
                                                                ?>
                                                                <div class="timeline-item">
                                                                    <div class="timeline-date">
                                                                        <?php echo date('d M Y', strtotime($latestJob['created_at'])); ?>
                                                                    </div>
                                                                    <div class="timeline-title">Latest Job Posted</div>
                                                                    <div class="timeline-content">
                                                                        "<?php echo htmlspecialchars($latestJob['job_title']); ?>"
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>

                                                            <?php if ($isVerified): ?>
                                                                <div class="timeline-item">
                                                                    <div class="timeline-date">Verified</div>
                                                                    <div class="timeline-title">Account Verified</div>
                                                                    <div class="timeline-content">
                                                                        Account has been verified by admin
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Quick Actions Card -->
                                                <div class="card info-card mt-4">
                                                    <div class="card-body">
                                                        <h5 class="card-title mb-4">
                                                            <i class="bi bi-lightning text-warning me-2"></i>Quick Actions
                                                        </h5>
                                                        <div class="d-grid gap-2">
                                                            <a href="mailto:<?php echo htmlspecialchars($recruiter['email']); ?>" class="btn btn-outline-primary">
                                                                <i class="bi bi-envelope me-2"></i>Send Email
                                                            </a>
                                                            <a href="tel:<?php echo htmlspecialchars($recruiter['phone']); ?>" class="btn btn-outline-success">
                                                                <i class="bi bi-telephone me-2"></i>Call Recruiter
                                                            </a>
                                                            <a href="job-add.php?recruiter_id=<?php echo $recruiterId; ?>" class="btn btn-outline-info">
                                                                <i class="bi bi-plus-circle me-2"></i>Post New Job
                                                            </a>
                                                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $recruiterId; ?>)">
                                                                <i class="bi bi-trash me-2"></i>Delete Recruiter
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>
    </main>

    <!-- Edit Recruiter Modal (Same as in management page) -->
    <div class="modal fade" id="editRecruiterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Recruiter - <?php echo htmlspecialchars($recruiter['full_name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editRecruiterForm">
                        <input type="hidden" id="editRecruiterId" name="id" value="<?php echo $recruiterId; ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editFullName" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="editFullName" name="full_name"
                                    value="<?php echo htmlspecialchars($recruiter['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editCompanyName" class="form-label">Company Name *</label>
                                <input type="text" class="form-control" id="editCompanyName" name="company_name"
                                    value="<?php echo htmlspecialchars($recruiter['company_name']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editEmail" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="editEmail" name="email"
                                    value="<?php echo htmlspecialchars($recruiter['email']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editPhone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="editPhone" name="phone"
                                    value="<?php echo htmlspecialchars($recruiter['phone']); ?>" pattern="[0-9]{10}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editAadharNumber" class="form-label">Aadhar Number</label>
                                <input type="text" class="form-control" id="editAadharNumber" name="aadhar_number"
                                    value="<?php echo htmlspecialchars($recruiter['aadhar_number'] ?? ''); ?>"
                                    pattern="[0-9]{12}" maxlength="12">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editStatus" class="form-label">Status</label>
                                <select class="form-select" id="editStatus" name="is_active">
                                    <option value="true" <?php echo $isActive ? 'selected' : ''; ?>>Active</option>
                                    <option value="false" <?php echo !$isActive ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editVerification" class="form-label">Verification Status</label>
                                <select class="form-select" id="editVerification" name="is_verified">
                                    <option value="true" <?php echo $isVerified ? 'selected' : ''; ?>>Verified</option>
                                    <option value="false" <?php echo !$isVerified ? 'selected' : ''; ?>>Unverified</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="editCompanyAddress" class="form-label">Company Address *</label>
                            <textarea class="form-control" id="editCompanyAddress" name="company_address" rows="3" required><?php echo htmlspecialchars($recruiter['company_address']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="editNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="editNotes" name="notes" rows="2"><?php echo htmlspecialchars($recruiter['notes'] ?? ''); ?></textarea>
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

    <!-- Toast Notification -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 99999;">
        <div class="toast align-items-center text-white bg-success border-0" id="successToast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle me-2"></i>
                    <span id="toastMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

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