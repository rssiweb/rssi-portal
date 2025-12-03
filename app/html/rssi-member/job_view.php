<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Get job ID from query string
$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$job_id) {
    header("Location: job-approval.php");
    exit;
}

// Fetch job details with recruiter information
$query = "SELECT jp.*, r.full_name as recruiter_name, r.company_name, 
                 r.email as recruiter_email, r.phone as recruiter_phone
          FROM job_posts jp 
          JOIN recruiters r ON jp.recruiter_id = r.id 
          WHERE jp.id = $1";

$result = pg_query_params($con, $query, [$job_id]);

if (!$result || pg_num_rows($result) == 0) {
    $_SESSION['error_message'] = "Job not found.";
    header("Location: job-approval.php");
    exit;
}

$job = pg_fetch_assoc($result);

// Format dates
$created_date = date('d/m/Y g:i a', strtotime($job['created_at']));
$updated_date = !empty($job['updated_at'])
    ? date('d/m/Y g:i a', strtotime($job['updated_at']))
    : 'Not specified';
$apply_by_date = date('d/m/Y', strtotime($job['apply_by']));
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

    <title>Job Details - <?php echo htmlspecialchars($job['job_title']); ?></title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <style>
        .job-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 10px;
        }

        .status-badge {
            font-size: 0.9em;
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
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

        .detail-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            /* height: 100%; */
        }

        .detail-card h6 {
            color: #667eea;
            margin-bottom: 15px;
        }

        .info-item {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: 600;
            color: #555;
            min-width: 150px;
            display: inline-block;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Job Details</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="job-approval.php">Job Approval</a></li>
                    <li class="breadcrumb-item active">Job Details</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="row">
                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="col-lg-12">
                                <!-- Job Header -->
                                <div class="job-header">
                                    <div class="container">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h2 class="mb-2"><?php echo htmlspecialchars($job['job_title']); ?></h2>
                                                <div class="d-flex align-items-center mb-3">
                                                    <i class="bi bi-building me-2"></i>
                                                    <h5 class="mb-0"><?php echo htmlspecialchars($job['company_name']); ?></h5>
                                                </div>
                                                <div class="d-flex flex-wrap gap-3">
                                                    <span><i class="bi bi-geo-alt me-1"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                                    <span><i class="bi bi-briefcase me-1"></i> <?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?></span>
                                                    <span><i class="bi bi-people me-1"></i> <?php echo $job['vacancies']; ?> Vacancies</span>
                                                    <span><i class="bi bi-calendar-check me-1"></i> Apply by: <?php echo $apply_by_date; ?></span>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-md-end">
                                                <?php if ($job['status'] === 'pending'): ?>
                                                    <span class="status-badge status-pending">Pending Approval</span>
                                                <?php elseif ($job['status'] === 'approved'): ?>
                                                    <span class="status-badge status-approved">Approved</span>
                                                <?php elseif ($job['status'] === 'rejected'): ?>
                                                    <span class="status-badge status-rejected">Rejected</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Job Statistics (if needed later) -->
                                <div class="detail-card mt-3">
                                    <div class="row">
                                        <div class="col-md-3 text-center">
                                            <h3>0</h3>
                                            <p class="text-muted mb-0">Views</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h3>0</h3>
                                            <p class="text-muted mb-0">Applications</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h3><?php echo $job['vacancies']; ?></h3>
                                            <p class="text-muted mb-0">Vacancies</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <h3>
                                                <?php
                                                $days_left = ceil((strtotime($job['apply_by']) - time()) / (60 * 60 * 24));
                                                echo $days_left > 0 ? $days_left : 0;
                                                ?>
                                            </h3>
                                            <p class="text-muted mb-0">Days Left</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Job Details -->
                                <div class="row">
                                    <!-- Job Information -->
                                    <div class="col-lg-8">
                                        <div class="detail-card">
                                            <h6><i class="bi bi-file-text me-2"></i>Job Description</h6>
                                            <div class="mb-4">
                                                <?php echo nl2br(htmlspecialchars($job['job_description'])); ?>
                                            </div>

                                            <?php if (!empty($job['requirements'])): ?>
                                                <h6><i class="bi bi-list-check me-2"></i>Requirements</h6>
                                                <div class="mb-4">
                                                    <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($job['benefits'])): ?>
                                                <h6><i class="bi bi-gift me-2"></i>Benefits</h6>
                                                <div class="mb-4">
                                                    <?php echo nl2br(htmlspecialchars($job['benefits'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Application Information -->
                                        <div class="detail-card">
                                            <h6><i class="bi bi-info-circle me-2"></i>Application Information</h6>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <span class="label">Job Type:</span>
                                                        <?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?>
                                                    </div>
                                                    <div class="info-item">
                                                        <span class="label">Experience:</span>
                                                        <?php echo $job['experience'] ?> years
                                                    </div>
                                                    <div class="info-item">
                                                        <span class="label">Salary:</span>
                                                        <?php echo $job['salary'] ? '₹' . number_format($job['salary']) : 'Not specified'; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <span class="label">Vacancies:</span>
                                                        <?php echo $job['vacancies']; ?>
                                                    </div>
                                                    <div class="info-item">
                                                        <span class="label">Education:</span>
                                                        <?php echo !empty($job['education']) ? htmlspecialchars($job['education']) : 'Not specified'; ?>
                                                    </div>
                                                    <div class="info-item">
                                                        <span class="label">Apply By:</span>
                                                        <?php echo $apply_by_date; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sidebar - Recruiter & Job Info -->
                                    <div class="col-lg-4">
                                        <!-- Recruiter Information -->
                                        <div class="detail-card">
                                            <h6><i class="bi bi-person-badge me-2"></i>Recruiter Information</h6>
                                            <div class="info-item">
                                                <span class="label">Name:</span>
                                                <?php echo htmlspecialchars($job['recruiter_name']); ?>
                                            </div>
                                            <div class="info-item">
                                                <span class="label">Company:</span>
                                                <?php echo htmlspecialchars($job['company_name']); ?>
                                            </div>
                                            <div class="info-item">
                                                <span class="label">Email:</span>
                                                <a href="mailto:<?php echo htmlspecialchars($job['recruiter_email']); ?>">
                                                    <?php echo htmlspecialchars($job['recruiter_email']); ?>
                                                </a>
                                            </div>
                                            <div class="info-item">
                                                <span class="label">Phone:</span>
                                                <a href="tel:<?php echo htmlspecialchars($job['recruiter_phone']); ?>">
                                                    <?php echo htmlspecialchars($job['recruiter_phone']); ?>
                                                </a>
                                            </div>
                                            <?php if (!empty($job['recruiter_address'])): ?>
                                                <div class="info-item">
                                                    <span class="label">Address:</span>
                                                    <?php
                                                    $address_parts = [];
                                                    if (!empty($job['recruiter_address'])) $address_parts[] = $job['recruiter_address'];
                                                    if (!empty($job['recruiter_city'])) $address_parts[] = $job['recruiter_city'];
                                                    if (!empty($job['recruiter_state'])) $address_parts[] = $job['recruiter_state'];
                                                    if (!empty($job['recruiter_pincode'])) $address_parts[] = $job['recruiter_pincode'];
                                                    echo htmlspecialchars(implode(', ', $address_parts));
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Job Status Information -->
                                        <div class="detail-card">
                                            <h6><i class="bi bi-clock-history me-2"></i>Job Status</h6>
                                            <div class="info-item">
                                                <span class="label">Status:</span>
                                                <?php if ($job['status'] === 'pending'): ?>
                                                    <span class="status-badge status-pending">Pending</span>
                                                <?php elseif ($job['status'] === 'approved'): ?>
                                                    <span class="status-badge status-approved">Approved</span>
                                                <?php elseif ($job['status'] === 'rejected'): ?>
                                                    <span class="status-badge status-rejected">Rejected</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($job['status'] === 'rejected' && !empty($job['rejection_reason'])): ?>
                                                <div class="info-item">
                                                    <span class="label">Rejection Reason:</span>
                                                    <?php echo htmlspecialchars($job['rejection_reason']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="info-item">
                                                <span class="label">Posted On:</span>
                                                <?php echo $created_date; ?>
                                            </div>
                                            <div class="info-item">
                                                <span class="label">Last Updated:</span>
                                                <?php echo $updated_date; ?>
                                            </div>
                                        </div>

                                        <!-- Quick Actions -->
                                        <div class="detail-card">
                                            <h6><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
                                            <div class="d-grid gap-2">
                                                <?php if ($job['status'] === 'pending'): ?>
                                                    <a href="job-approval.php" class="btn btn-success">
                                                        <i class="bi bi-check-circle me-2"></i>Go to Approval
                                                    </a>
                                                <?php endif; ?>
                                                <a href="job-approval.php" class="btn btn-outline-primary">
                                                    <i class="bi bi-arrow-left me-2"></i>Back to Job List
                                                </a>
                                                <?php if (!empty($job['status']) && $job['status'] !== 'pending'): ?>
                                                    <button type="button" class="btn btn-outline-info" onclick="window.print()">
                                                        <i class="bi bi-printer me-2"></i>Print Details
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
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

    <script>
        // Function to format phone numbers
        function formatPhoneNumber(phone) {
            return phone.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
        }

        // Apply phone formatting if needed
        document.addEventListener('DOMContentLoaded', function() {
            const phoneElements = document.querySelectorAll('a[href^="tel:"]');
            phoneElements.forEach(el => {
                const phone = el.textContent.trim();
                if (phone.length === 10) {
                    el.textContent = formatPhoneNumber(phone);
                }
            });
        });
    </script>

</body>

</html>