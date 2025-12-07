<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_recruiters.php");

if (!isLoggedIn("rid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Get job ID from query string
$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$job_id) {
    echo "<script>
        alert('Invalid job ID.');
        window.location.href = 'recruiter-jobs.php';
    </script>";
    exit;
}

// Logged-in recruiter ID (from session or your user data)
$recruiter_id = $recruiter_id;

// Fetch job only if it belongs to the logged-in recruiter
$query = "SELECT jp.*, 
                 r.full_name as recruiter_name, 
                 r.company_name, 
                 r.email as recruiter_email, 
                 r.phone as recruiter_phone,
                 r.company_address as recruiter_address,
                 el.name as education_level_name
          FROM job_posts jp 
          JOIN recruiters r ON jp.recruiter_id = r.id 
          LEFT JOIN education_levels el ON jp.education_levels = el.id 
          WHERE jp.id = $1 
            AND jp.recruiter_id = $2";

$result = pg_query_params($con, $query, [$job_id, $recruiter_id]);

if (!$result || pg_num_rows($result) == 0) {
    echo "<script>
        alert('You are not authorized to view this job.');
        window.location.href = 'recruiter-jobs.php';
    </script>";
    exit;
}

$job = pg_fetch_assoc($result);

// Fetch application count and list of applicants
$applications_query = "SELECT COUNT(*) as total_applications FROM job_applications WHERE job_id = $1";
$applications_result = pg_query_params($con, $applications_query, [$job_id]);
$applications_data = pg_fetch_assoc($applications_result);
$total_applications = $applications_data['total_applications'];

// Fetch applicants data
$applicants_query = "SELECT ja.*, jsd.name, jsd.contact, jsd.email, jsd.dob, jsd.education, 
                            jsd.skills, jsd.preferences, jsd.address1, jsd.resume, jsd.status as applicant_status,
                            el.name as education_name
                     FROM job_applications ja
                     JOIN job_seeker_data jsd ON ja.job_seeker_id = jsd.id
                     LEFT JOIN education_levels el ON jsd.education = el.id
                     WHERE ja.job_id = $1
                     ORDER BY ja.application_date DESC";

$applicants_result = pg_query_params($con, $applicants_query, [$job_id]);
$applicants = pg_fetch_all($applicants_result) ?: [];

// Format dates
$created_date = date('d/m/Y g:i a', strtotime($job['created_at']));
$updated_date = !empty($job['updated_at'])
    ? date('d/m/Y g:i a', strtotime($job['updated_at']))
    : 'Not specified';
$apply_by_date = date('d/m/Y', strtotime($job['apply_by']));

// Check if export requested
$export = isset($_GET['export']) && $_GET['export'] == 'csv';
if ($export) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="applicants_job_' . $job_id . '_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV header
    fputcsv($output, [
        'Application ID',
        'Name',
        'Phone',
        'Email',
        'Age',
        'Education',
        'Skills',
        'Preferences',
        'Address',
        'Application Date',
        'Status',
        'Resume Link'
    ]);

    // CSV data
    foreach ($applicants as $applicant) {
        // Calculate age from DOB
        $age = '--';
        if (!empty($applicant['dob'])) {
            $dob = new DateTime($applicant['dob']);
            $today = new DateTime();
            $age = $today->diff($dob)->y;
        }

        fputcsv($output, [
            $applicant['id'],
            $applicant['name'],
            $applicant['contact'],
            $applicant['email'],
            $age,  // Changed from $applicant['age'] to calculated $age
            $applicant['education_name'] ?? $applicant['education'],
            $applicant['skills'],
            $applicant['preferences'],
            $applicant['address1'],
            date('d/m/Y H:i:s', strtotime($applicant['application_date'])),
            $applicant['applicant_status'],
            $applicant['resume']
        ]);
    }

    fclose($output);
    exit;
}
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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

        .status-applied {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-selected {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected-app {
            background-color: #f8d7da;
            color: #721c24;
        }

        .detail-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
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

        .applicant-table {
            font-size: 0.9em;
        }

        .applicant-table th {
            background-color: #f8f9fa;
        }

        .view-applicant-btn {
            font-size: 0.85em;
            padding: 3px 8px;
        }

        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
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
                    <li class="breadcrumb-item"><a href="recruiter-jobs.php">View Job Applications</a></li>
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

                                <!-- Job Statistics -->
                                <div class="detail-card mt-3">
                                    <div class="row">
                                        <div class="col-md-3 text-center">
                                            <div class="stat-number"><?php echo $total_applications; ?></div>
                                            <p class="stat-label">Applications</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="stat-number"><?php echo $job['vacancies']; ?></div>
                                            <p class="stat-label">Vacancies</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <div class="stat-number">
                                                <?php
                                                $days_left = ceil((strtotime($job['apply_by']) - time()) / (60 * 60 * 24));
                                                echo $days_left > 0 ? $days_left : 0;
                                                ?>
                                            </div>
                                            <p class="stat-label">Days Left</p>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <?php if ($total_applications > 0 && $job['vacancies'] > 0): ?>
                                                <div class="stat-number">
                                                    <?php echo round(($total_applications / $job['vacancies']) * 100, 1); ?>%
                                                </div>
                                                <p class="stat-label">Application/Vacancy Ratio</p>
                                            <?php else: ?>
                                                <div class="stat-number">0%</div>
                                                <p class="stat-label">Application/Vacancy Ratio</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Applications Section -->
                                <a id="applications"></a> <!-- Add this line -->
                                <?php if ($total_applications > 0): ?>
                                    <div class="detail-card">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6><i class="bi bi-people me-2"></i>Job Applications (<?php echo $total_applications; ?>)</h6>
                                            <div>
                                                <a href="?id=<?php echo $job_id; ?>&export=csv" class="btn btn-success btn-sm">
                                                    <i class="bi bi-download me-1"></i>Export CSV
                                                </a>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-hover applicant-table" id="applicantsTable">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Name</th>
                                                        <th>Phone</th>
                                                        <th>Email</th>
                                                        <th>Age</th>
                                                        <th>Education</th>
                                                        <th>Skills</th>
                                                        <th>Applied On</th>
                                                        <th>Status</th>
                                                        <th>Resume</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($applicants as $index => $applicant): ?>
                                                        <tr>
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td><?php echo htmlspecialchars($applicant['name']); ?></td>
                                                            <td>
                                                                <a href="tel:<?php echo htmlspecialchars($applicant['contact']); ?>">
                                                                    <?php echo htmlspecialchars($applicant['contact']); ?>
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($applicant['email'])): ?>
                                                                    <a href="mailto:<?php echo htmlspecialchars($applicant['email']); ?>">
                                                                        <?php echo htmlspecialchars($applicant['email']); ?>
                                                                    </a>
                                                                <?php else: ?>
                                                                    N/A
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                if (!empty($applicant['dob'])) {
                                                                    $dob = new DateTime($applicant['dob']);
                                                                    $today = new DateTime();
                                                                    $age = $today->diff($dob)->y;
                                                                    echo $age;
                                                                } else {
                                                                    echo 'N/A';
                                                                }
                                                                ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($applicant['education_name'] ?? $applicant['education']); ?></td>
                                                            <td>
                                                                <span class="d-inline-block text-truncate" style="max-width: 150px;"
                                                                    title="<?php echo htmlspecialchars($applicant['skills']); ?>">
                                                                    <?php echo htmlspecialchars($applicant['skills']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('d/m/Y H:i', strtotime($applicant['application_date'])); ?></td>
                                                            <td>
                                                                <?php if ($applicant['applicant_status'] === 'Active'): ?>
                                                                    <span class="status-badge status-applied">Applied</span>
                                                                <?php elseif ($applicant['applicant_status'] === 'Selected'): ?>
                                                                    <span class="status-badge status-selected">Selected</span>
                                                                <?php elseif ($applicant['applicant_status'] === 'Rejected'): ?>
                                                                    <span class="status-badge status-rejected-app">Rejected</span>
                                                                <?php else: ?>
                                                                    <?php echo htmlspecialchars($applicant['applicant_status']); ?>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($applicant['resume'])): ?>
                                                                    <a href="<?php echo htmlspecialchars($applicant['resume']); ?>"
                                                                        target="_blank" class="btn btn-outline-primary btn-sm">
                                                                        <i class="bi bi-file-earmark-pdf"></i> View
                                                                    </a>
                                                                <?php else: ?>
                                                                    <span class="text-muted">No resume</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-info btn-sm view-applicant-btn"
                                                                    onclick="viewApplicantDetails(<?php echo $applicant['job_seeker_id']; ?>)">
                                                                    <i class="bi bi-eye"></i> View
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Job Details -->
                                <div class="row">
                                    <!-- Job Information -->
                                    <div class="col-lg-8">
                                        <div class="detail-card">
                                            <h6><i class="bi bi-file-text me-2"></i>Job Description</h6>
                                            <div class="mb-4">
                                                <?php echo nl2br(htmlspecialchars($job['job_description'])); ?>
                                                <?php
                                                echo '<div class="mt-4">' .
                                                    (!empty($job['job_file_path'])
                                                        ? nl2br(htmlspecialchars($job['job_file_path']))
                                                        : 'No file attached'
                                                    ) .
                                                    '</div>';
                                                ?>
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
                                                        <?php
                                                        echo ($job['min_salary'] && $job['max_salary'])
                                                            ? '₹' . number_format($job['min_salary']) . ' - ₹' . number_format($job['max_salary']) . ' per month'
                                                            : ($job['min_salary']
                                                                ? '₹' . number_format($job['min_salary']) . ' per month'
                                                                : ($job['max_salary']
                                                                    ? 'Up to ₹' . number_format($job['max_salary']) . ' per month'
                                                                    : 'Not specified'
                                                                )
                                                            );
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-item">
                                                        <span class="label">Vacancies:</span>
                                                        <?php echo $job['vacancies']; ?>
                                                    </div>
                                                    <div class="info-item">
                                                        <span class="label">Education:</span>
                                                        <?php echo !empty($job['education_level_name']) ? htmlspecialchars($job['education_level_name']) : 'Not specified'; ?>
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
                                                <?php if ($total_applications > 0): ?>
                                                    <a href="#applications" class="btn btn-info">
                                                        <i class="bi bi-people me-2"></i>View Applicants (<?php echo $total_applications; ?>)
                                                    </a>
                                                <?php endif; ?>
                                                <a href="#" onclick="history.back();" class="btn btn-outline-primary">
                                                    <i class="bi bi-arrow-left me-2"></i>Back
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

    <!-- Applicant Details Modal -->
    <div class="modal fade" id="applicantModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Applicant Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="applicantDetails">
                    <!-- Applicant details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        // Initialize DataTable
        $(document).ready(function() {
            $('#applicantsTable').DataTable({
                "pageLength": 10,
                "order": [
                    [7, 'desc']
                ], // Sort by application date descending
                "language": {
                    "search": "Search applicants:"
                }
            });
        });

        // Function to view applicant details
        function viewApplicantDetails(applicantId) {
            // Show loading
            $('#applicantDetails').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading applicant details...</p>
                </div>
            `);

            // Fetch applicant details
            $.ajax({
                url: 'get_applicant_details.php',
                type: 'GET',
                data: {
                    applicant_id: applicantId,
                    job_id: <?php echo $job_id; ?>
                },
                success: function(response) {
                    $('#applicantDetails').html(response);
                },
                error: function() {
                    $('#applicantDetails').html(`
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            Unable to load applicant details. Please try again.
                        </div>
                    `);
                }
            });

            // Show modal
            const applicantModal = new bootstrap.Modal(document.getElementById('applicantModal'));
            applicantModal.show();
        }

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

        // Auto-linkify URLs in text
        document.addEventListener("DOMContentLoaded", function() {
            function linkifyTextNode(node) {
                const urlRegex = /(https?:\/\/[^\s]+)/g;

                if (node.nodeType === 3) {
                    const text = node.nodeValue;
                    if (urlRegex.test(text)) {
                        const span = document.createElement("span");
                        span.innerHTML = text.replace(urlRegex, '<a href="$1" target="_blank">$1</a>');
                        node.replaceWith(span);
                    }
                }
            }

            function walk(node) {
                node = node.firstChild;
                while (node) {
                    const nextNode = node.nextSibling;
                    if (node.nodeType === 3) {
                        linkifyTextNode(node);
                    } else if (node.nodeType === 1) {
                        walk(node);
                    }
                    node = nextNode;
                }
            }

            walk(document.body);
        });
    </script>

</body>

</html>