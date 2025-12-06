<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Check if job ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Job ID is required!";
    header("Location: job-admin.php");
    exit;
}

$jobId = $_GET['id'];

// Fetch job details
$query = "SELECT 
            j.*,
            r.full_name as recruiter_name,
            r.company_name as recruiter_company,
            r.email as recruiter_email
          FROM job_posts j
          LEFT JOIN recruiters r ON j.recruiter_id = r.id
          WHERE j.id = $1";

$result = pg_query_params($con, $query, [$jobId]);

if (!$result || pg_num_rows($result) === 0) {
    $_SESSION['error_message'] = "Job not found!";
    header("Location: job-admin.php");
    exit;
}

$job = pg_fetch_assoc($result);

// Fetch all recruiters for dropdown
$recruitersQuery = "SELECT id, full_name, company_name, email FROM recruiters WHERE is_active = true ORDER BY company_name";
$recruitersResult = pg_query($con, $recruitersQuery);
$recruiters = pg_fetch_all($recruitersResult) ?: [];

// Fetch education levels
$educationQuery = "SELECT id, name FROM education_levels ORDER BY sort_order";
$educationResult = pg_query($con, $educationQuery);
$educationLevels = pg_fetch_all($educationResult) ?: [];

// Job types for dropdown
$jobTypes = ['full-time', 'part-time', 'contract', 'internship', 'remote', 'freelance'];

// Job statuses
$jobStatuses = ['draft', 'pending', 'active', 'inactive', 'closed', 'approved', 'rejected'];

// Format dates for input fields
$applyBy = date('Y-m-d', strtotime($job['apply_by']));
$createdAt = date('Y-m-d', strtotime($job['created_at']));
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

    <title>Edit Job - <?php echo htmlspecialchars($job['job_title']); ?></title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <style>
        .page-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 10px;
        }

        .info-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .info-card .card-header {
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }

        .info-card .card-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
        }

        .required-field::after {
            content: " *";
            color: #dc3545;
        }

        .salary-input-group {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .salary-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        .salary-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #198754;
        }

        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload-area:hover {
            border-color: #0d6efd;
            background: #e9f5ff;
        }

        .file-upload-area.dragover {
            border-color: #0d6efd;
            background: #e9f5ff;
        }

        .file-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            display: none;
        }

        .file-preview.show {
            display: block;
        }

        .existing-file {
            background: #e8f5e9;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .existing-file a {
            text-decoration: none;
            color: #155724;
        }

        .existing-file a:hover {
            text-decoration: underline;
        }

        .char-count {
            font-size: 0.875rem;
            color: #6c757d;
            text-align: right;
            margin-top: 0.25rem;
        }

        .btn-loading {
            position: relative;
            color: transparent !important;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-top: -8px;
            margin-left: -8px;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: button-loading-spinner 1s ease infinite;
        }

        @keyframes button-loading-spinner {
            from {
                transform: rotate(0turn);
            }

            to {
                transform: rotate(1turn);
            }
        }

        /* Toast Notification */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 99999;
        }

        /* Select2 custom styles */
        .select2-container--bootstrap-5 .select2-selection {
            min-height: 42px;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 32px;
        }

        .stats-badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <!-- Page Header -->
        <div class="page-header">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">Edit Job Posting</h1>
                        <p class="mb-0">
                            <i class="bi bi-briefcase me-1"></i>
                            <strong><?php echo htmlspecialchars($job['job_title']); ?></strong>
                            <span class="mx-2">•</span>
                            <i class="bi bi-building me-1"></i>
                            <?php echo htmlspecialchars($job['recruiter_company']); ?>
                        </p>
                    </div>
                    <div>
                        <a href="job_view.php?id=<?php echo $jobId; ?>" class="btn btn-light">
                            <i class="bi bi-eye me-1"></i> View Job
                        </a>
                        <a href="job-admin.php" class="btn btn-outline-light">
                            <i class="bi bi-arrow-left me-1"></i> Back to Jobs
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <section class="section">
            <div class="container">
                <div class="row">
                    <!-- Left Column: Job Form -->
                    <div class="col-lg-8">
                        <!-- Success/Error Messages -->
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['success_message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['error_message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error_message']); ?>
                        <?php endif; ?>

                        <!-- Edit Job Form -->
                        <div class="card info-card">
                            <div class="card-header">
                                <i class="bi bi-pencil-square me-2"></i>Edit Job Details
                            </div>
                            <div class="card-body">
                                <form id="editJobForm" enctype="multipart/form-data">
                                    <input type="hidden" id="jobId" name="job_id" value="<?php echo $jobId; ?>">

                                    <!-- Basic Information Section -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2 mb-3">
                                            <i class="bi bi-info-circle me-2"></i>Basic Information
                                        </h5>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="recruiter" class="form-label required-field">Recruiter</label>
                                                <select class="form-select select2-recruiter" id="recruiter" name="recruiter_id" required>
                                                    <option value="">Select Recruiter</option>
                                                    <?php foreach ($recruiters as $recruiter): ?>
                                                        <option value="<?php echo $recruiter['id']; ?>"
                                                            <?php echo $recruiter['id'] == $job['recruiter_id'] ? 'selected' : ''; ?>
                                                            data-email="<?php echo htmlspecialchars($recruiter['email']); ?>">
                                                            <?php echo htmlspecialchars($recruiter['company_name'] . ' - ' . $recruiter['full_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text">
                                                    Current: <?php echo htmlspecialchars($job['recruiter_company'] . ' - ' . $job['recruiter_name']); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="recruiterEmail" class="form-label">Recruiter Email</label>
                                                <input type="email" class="form-control" id="recruiterEmail"
                                                    value="<?php echo htmlspecialchars($job['recruiter_email']); ?>" readonly>
                                                <input type="hidden" id="recruiterEmailHidden" name="recruiter_email"
                                                    value="<?php echo htmlspecialchars($job['recruiter_email']); ?>">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <label for="jobTitle" class="form-label required-field">Job Title</label>
                                                <input type="text" class="form-control" id="jobTitle" name="job_title"
                                                    value="<?php echo htmlspecialchars($job['job_title']); ?>" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="jobType" class="form-label required-field">Job Type</label>
                                                <select class="form-select" id="jobType" name="job_type" required>
                                                    <option value="">Select Type</option>
                                                    <?php foreach ($jobTypes as $type): ?>
                                                        <option value="<?php echo $type; ?>"
                                                            <?php echo $job['job_type'] == $type ? 'selected' : ''; ?>>
                                                            <?php echo ucfirst(str_replace('-', ' ', $type)); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="location" class="form-label required-field">Location</label>
                                                <input type="text" class="form-control" id="location" name="location"
                                                    value="<?php echo htmlspecialchars($job['location']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="vacancies" class="form-label required-field">Vacancies</label>
                                                <input type="number" class="form-control" id="vacancies" name="vacancies"
                                                    value="<?php echo htmlspecialchars($job['vacancies']); ?>" min="1" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Salary Information Section -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2 mb-3">
                                            <i class="bi bi-currency-rupee me-2"></i>Salary Information
                                        </h5>

                                        <div class="salary-input-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="salary-label">Minimum Salary (₹ per month)</div>
                                                    <input type="number" class="form-control" id="minSalary" name="min_salary"
                                                        value="<?php echo htmlspecialchars($job['min_salary']); ?>" min="0" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="salary-label">Maximum Salary (₹ per month)</div>
                                                    <input type="number" class="form-control" id="maxSalary" name="max_salary"
                                                        value="<?php echo htmlspecialchars($job['max_salary']); ?>" min="0" required>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-12">
                                                    <div id="salaryRangeError" class="text-danger small" style="display: none;">
                                                        Maximum salary must be greater than or equal to minimum salary
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Job Description Section -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2 mb-3">
                                            <i class="bi bi-file-text me-2"></i>Job Description & Requirements
                                        </h5>

                                        <div class="mb-3">
                                            <label for="jobDescription" class="form-label required-field">Job Description</label>
                                            <textarea class="form-control" id="jobDescription" name="job_description"
                                                rows="5" required><?php echo htmlspecialchars($job['job_description']); ?></textarea>
                                            <div class="char-count">
                                                <span id="descCharCount">0</span> characters (Minimum: 100)
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="requirements" class="form-label required-field">Requirements</label>
                                            <textarea class="form-control" id="requirements" name="requirements"
                                                rows="3" required><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                                            <div class="char-count">
                                                <span id="reqCharCount">0</span> characters (Minimum: 50)
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="benefits" class="form-label">Benefits (Optional)</label>
                                            <textarea class="form-control" id="benefits" name="benefits"
                                                rows="2"><?php echo htmlspecialchars($job['benefits'] ?? ''); ?></textarea>
                                            <div class="char-count">
                                                <span id="benefitsCharCount">0</span> characters
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Qualifications & Experience Section -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2 mb-3">
                                            <i class="bi bi-mortarboard me-2"></i>Qualifications & Experience
                                        </h5>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="experience" class="form-label required-field">Experience Required</label>
                                                <input type="text" class="form-control" id="experience" name="experience"
                                                    value="<?php echo htmlspecialchars($job['experience'] ?? ''); ?>"
                                                    placeholder="e.g., 2-4 years" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="educationQualification" class="form-label required-field">Educational Qualification</label>
                                                <select class="form-select" id="educationQualification" name="education_levels" required>
                                                    <option value="">Select Qualification</option>
                                                    <?php foreach ($educationLevels as $level): ?>
                                                        <option value="<?php echo $level['id']; ?>"
                                                            <?php echo $job['education_levels'] == $level['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($level['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dates & Status Section -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2 mb-3">
                                            <i class="bi bi-calendar me-2"></i>Dates & Status
                                        </h5>

                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="applyBy" class="form-label required-field">Apply By Date</label>
                                                <input type="date" class="form-control" id="applyBy" name="apply_by"
                                                    value="<?php echo $applyBy; ?>" required>
                                                <div class="form-text">
                                                    Current: <?php echo date('d M Y', strtotime($job['apply_by'])); ?>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="jobStatus" class="form-label required-field">Job Status</label>
                                                <select class="form-select" id="jobStatus" name="status" required>
                                                    <?php foreach ($jobStatuses as $status): ?>
                                                        <option value="<?php echo $status; ?>"
                                                            <?php echo $job['status'] == $status ? 'selected' : ''; ?>>
                                                            <?php echo ucfirst($status); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="createdAt" class="form-label">Created Date</label>
                                                <input type="date" class="form-control" id="createdAt"
                                                    value="<?php echo $createdAt; ?>" readonly>
                                                <div class="form-text">
                                                    Posted: <?php echo date('d M Y', strtotime($job['created_at'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Document Upload Section -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2 mb-3">
                                            <i class="bi bi-paperclip me-2"></i>Additional Documents
                                        </h5>

                                        <!-- Existing Document -->
                                        <?php if (!empty($job['job_file_path'])): ?>
                                            <div class="existing-file">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                                        <strong>Current Document:</strong>
                                                        <a href="<?php echo htmlspecialchars($job['job_file_path']); ?>"
                                                            target="_blank" class="ms-2">
                                                            <?php echo basename($job['job_file_path']); ?>
                                                        </a>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" id="removeExistingFile">
                                                        <i class="bi bi-trash"></i> Remove
                                                    </button>
                                                </div>
                                                <input type="hidden" id="existingFilePath" name="existing_file_path"
                                                    value="<?php echo htmlspecialchars($job['job_file_path']); ?>">
                                            </div>
                                        <?php endif; ?>

                                        <!-- New File Upload -->
                                        <div class="mt-3">
                                            <label class="form-label">Upload New Document (Optional)</label>
                                            <div class="file-upload-area" id="jobFileUploadArea">
                                                <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #6c757d;"></i>
                                                <p class="mt-2">Click to upload or drag and drop</p>
                                                <p class="text-muted small mb-0">PDF, DOC, DOCX (Max 5MB)</p>
                                                <p class="text-muted small">
                                                    <?php if (!empty($job['job_file_path'])): ?>
                                                        Uploading new file will replace the existing one
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                            <input type="file" class="form-control d-none" id="jobFile" name="job_file"
                                                accept=".pdf,.doc,.docx">
                                            <div class="file-preview" id="jobFilePreview">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong id="jobFileName"></strong>
                                                        <br><small class="file-size" id="jobFileSize"></small>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-danger" id="removeJobFile">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Admin Notes Section -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2 mb-3">
                                            <i class="bi bi-sticky me-2"></i>Admin Notes
                                        </h5>

                                        <div class="mb-3">
                                            <label for="adminNotes" class="form-label">Internal Notes (Optional)</label>
                                            <textarea class="form-control" id="adminNotes" name="admin_notes" rows="2"><?php echo htmlspecialchars($job['admin_notes'] ?? ''); ?></textarea>
                                            <div class="form-text">These notes are only visible to administrators</div>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                        <div>
                                            <a href="job_view.php?id=<?php echo $jobId; ?>" class="btn btn-outline-secondary">
                                                <i class="bi bi-x-circle me-1"></i> Cancel
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $jobId; ?>)">
                                                <i class="bi bi-trash me-1"></i> Delete Job
                                            </button>
                                        </div>
                                        <div>
                                            <button type="submit" class="btn btn-primary" id="saveJobBtn">
                                                <span class="btn-text">Update Job</span>
                                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Job Statistics & Info -->
                    <div class="col-lg-4">
                        <div class="sticky-top" style="top: 100px;">
                            <!-- Job Statistics Card -->
                            <div class="card info-card mb-4">
                                <div class="card-header">
                                    <i class="bi bi-bar-chart me-2"></i>Job Statistics
                                </div>
                                <div class="card-body">
                                    <?php
                                    // Fetch job statistics
                                    $statsQuery = "SELECT 
                                                    COUNT(DISTINCT ja.id) as total_applications,
                                                    COUNT(DISTINCT CASE WHEN ja.status = 'shortlisted' THEN ja.id END) as shortlisted,
                                                    COUNT(DISTINCT CASE WHEN ja.status = 'rejected' THEN ja.id END) as rejected,
                                                    COUNT(DISTINCT CASE WHEN ja.status = 'hired' THEN ja.id END) as hired
                                                  FROM job_applications ja
                                                  WHERE ja.job_id = $1";

                                    $statsResult = pg_query_params($con, $statsQuery, [$jobId]);
                                    $stats = pg_fetch_assoc($statsResult);
                                    ?>

                                    <div class="row text-center">
                                        <div class="col-6 mb-3">
                                            <div class="stats-number"><?php echo $stats['total_applications'] ?? 0; ?></div>
                                            <div class="stats-label">Applications</div>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <div class="stats-number"><?php echo $stats['shortlisted'] ?? 0; ?></div>
                                            <div class="stats-label">Shortlisted</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stats-number"><?php echo $stats['hired'] ?? 0; ?></div>
                                            <div class="stats-label">Hired</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stats-number"><?php echo $stats['rejected'] ?? 0; ?></div>
                                            <div class="stats-label">Rejected</div>
                                        </div>
                                    </div>

                                    <?php if (($stats['total_applications'] ?? 0) > 0): ?>
                                        <div class="text-center mt-3">
                                            <a href="job_view.php?id=<?php echo $jobId; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-people me-1"></i> View Applications
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Job Status Card -->
                            <div class="card info-card mb-4">
                                <div class="card-header">
                                    <i class="bi bi-info-circle me-2"></i>Current Status
                                </div>
                                <div class="card-body">
                                    <?php
                                    $statusClass = '';
                                    switch ($job['status']) {
                                        case 'active':
                                            $statusClass = 'bg-success';
                                            break;
                                        case 'pending':
                                            $statusClass = 'bg-warning text-dark';
                                            break;
                                        case 'draft':
                                            $statusClass = 'bg-secondary';
                                            break;
                                        case 'closed':
                                            $statusClass = 'bg-info';
                                            break;
                                        case 'rejected':
                                            $statusClass = 'bg-danger';
                                            break;
                                        case 'inactive':
                                            $statusClass = 'bg-dark';
                                            break;
                                        default:
                                            $statusClass = 'bg-secondary';
                                    }
                                    ?>
                                    <div class="d-flex justify-content-center mb-3">
                                        <span class="badge <?php echo $statusClass; ?> stats-badge">
                                            <?php echo ucfirst($job['status']); ?>
                                        </span>
                                    </div>

                                    <div class="timeline small">
                                        <div class="timeline-item">
                                            <div class="timeline-date"><?php echo date('d M Y', strtotime($job['created_at'])); ?></div>
                                            <div class="timeline-title">Created</div>
                                        </div>
                                        <?php if (!empty($job['approved_at'])): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-date"><?php echo date('d M Y', strtotime($job['approved_at'])); ?></div>
                                                <div class="timeline-title">Approved</div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($job['published_at'])): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-date"><?php echo date('d M Y', strtotime($job['published_at'])); ?></div>
                                                <div class="timeline-title">Published</div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="timeline-item">
                                            <div class="timeline-date"><?php echo date('d M Y', strtotime($job['apply_by'])); ?></div>
                                            <div class="timeline-title">Apply By</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions Card -->
                            <div class="card info-card">
                                <div class="card-header">
                                    <i class="bi bi-lightning me-2"></i>Quick Actions
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="https://www.rssi.in/job_details?id=<?php echo $jobId; ?>" class="btn btn-outline-primary" target="_blank">
                                            <i class="bi bi-eye me-2"></i> View Public Page
                                        </a>
                                        <a href="job_view.php?id=<?php echo $jobId; ?>" class="btn btn-outline-success">
                                            <i class="bi bi-people me-2"></i> Manage Applications
                                        </a>
                                        <a href="recruiter-details.php?id=<?php echo $job['recruiter_id']; ?>" class="btn btn-outline-info">
                                            <i class="bi bi-person me-2"></i> View Recruiter
                                        </a>
                                        <button type="button" class="btn btn-outline-warning" onclick="duplicateJob(<?php echo $jobId; ?>)">
                                            <i class="bi bi-copy me-2"></i> Duplicate Job
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

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

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        // API endpoint configuration
        const API_BASE = window.location.hostname === 'localhost' ?
            'http://localhost:8082/job-api/' :
            'https://login.rssi.in/job-api/';
        $(document).ready(function() {
            // Initialize Select2 for recruiter dropdown
            $('#recruiter').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search for a recruiter...',
                allowClear: true,
                width: '100%'
            });

            // Handle recruiter selection change
            $('#recruiter').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const recruiterEmail = selectedOption.data('email') || '';
                $('#recruiterEmail').val(recruiterEmail);
                $('#recruiterEmailHidden').val(recruiterEmail);
            });

            // Character count for textareas
            function updateCharCount(textareaId, counterId) {
                const textarea = $(textareaId);
                const counter = $(counterId);

                textarea.on('input', function() {
                    const length = $(this).val().length;
                    counter.text(length);

                    // Add warning class if below minimum
                    if (textareaId === '#jobDescription' && length < 100) {
                        counter.addClass('text-danger');
                    } else if (textareaId === '#requirements' && length < 50) {
                        counter.addClass('text-danger');
                    } else {
                        counter.removeClass('text-danger');
                    }
                });

                // Trigger on load
                textarea.trigger('input');
            }

            // Initialize character counters
            updateCharCount('#jobDescription', '#descCharCount');
            updateCharCount('#requirements', '#reqCharCount');
            updateCharCount('#benefits', '#benefitsCharCount');

            // Salary validation
            function validateSalary() {
                const minSalary = parseFloat($('#minSalary').val()) || 0;
                const maxSalary = parseFloat($('#maxSalary').val()) || 0;
                const errorDiv = $('#salaryRangeError');

                if (maxSalary > 0 && maxSalary < minSalary) {
                    errorDiv.show();
                    $('#maxSalary').addClass('is-invalid');
                    return false;
                } else {
                    errorDiv.hide();
                    $('#maxSalary').removeClass('is-invalid');
                    return true;
                }
            }

            $('#minSalary, #maxSalary').on('input', validateSalary);

            // File upload handling
            const jobFileInput = $('#jobFile');
            const jobDropArea = $('#jobFileUploadArea');
            const jobFilePreview = $('#jobFilePreview');
            const jobFileName = $('#jobFileName');
            const jobFileSize = $('#jobFileSize');

            // Click on drop area triggers file input
            jobDropArea.on('click', function(e) {
                if (!$(e.target).is('button')) {
                    jobFileInput.click();
                }
            });

            // Handle file selection
            jobFileInput.on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    displayJobFilePreview(file);
                }
            });

            // Drag and drop events
            jobDropArea.on('dragover', function(e) {
                e.preventDefault();
                jobDropArea.addClass('dragover');
            });

            jobDropArea.on('dragleave', function() {
                jobDropArea.removeClass('dragover');
            });

            jobDropArea.on('drop', function(e) {
                e.preventDefault();
                jobDropArea.removeClass('dragover');

                const file = e.originalEvent.dataTransfer.files[0];
                if (file) {
                    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    if (allowedTypes.includes(file.type)) {
                        jobFileInput[0].files = e.originalEvent.dataTransfer.files;
                        displayJobFilePreview(file);
                    } else {
                        showToast('Please upload PDF, DOC, or DOCX files only.', 'danger');
                    }
                }
            });

            function displayJobFilePreview(file) {
                // Check file type
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!allowedTypes.includes(file.type)) {
                    showToast('Please upload PDF, DOC, or DOCX files only.', 'danger');
                    return;
                }

                // Check file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    showToast('File size should be less than 5MB.', 'danger');
                    return;
                }

                jobFileName.text(file.name);
                jobFileSize.text(formatFileSize(file.size));
                jobFilePreview.addClass('show');
            }

            // Remove job file
            $('#removeJobFile').on('click', function() {
                jobFileInput.val('');
                jobFilePreview.removeClass('show');
            });

            // Remove existing file
            $('#removeExistingFile').on('click', function() {
                if (confirm('Are you sure you want to remove the existing document?')) {
                    $('.existing-file').hide();
                    $('#existingFilePath').val('');
                    showToast('Existing document will be removed on save.', 'warning');
                }
            });

            // Form submission
            $('#editJobForm').on('submit', function(e) {
                e.preventDefault();
                saveJobChanges();
            });

            // Apply by date validation
            $('#applyBy').on('change', function() {
                const applyByDate = new Date($(this).val());
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (applyByDate < today) {
                    showToast('Apply by date cannot be in the past.', 'warning');
                    $(this).val('<?php echo $applyBy; ?>'); // Reset to original value
                }
            });
        });

        function saveJobChanges() {
            // Validate form
            if (!validateForm()) {
                return;
            }

            // Get the button and its elements
            const saveBtn = $('#saveJobBtn');
            const btnText = saveBtn.find('.btn-text');
            const spinner = saveBtn.find('.spinner-border');

            // Disable button and show spinner
            saveBtn.prop('disabled', true);
            btnText.text('Updating...');
            spinner.show();

            const formData = new FormData($('#editJobForm')[0]);

            $.ajax({
                url: API_BASE + 'update_job.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success toast
                        showToast('Job updated successfully!', 'success');

                        // Update page after a short delay
                        setTimeout(function() {
                            // Reset button state
                            resetSaveButton();
                            // Reload page to show updated data
                            window.location.href = 'job_view.php?id=' + $('#jobId').val();
                        }, 1500);
                    } else {
                        showToast('Failed to update: ' + response.message, 'danger');
                        // Re-enable button on error
                        resetSaveButton();
                    }
                },
                error: function(xhr, status, error) {
                    showToast('Error updating job. Please try again.', 'danger');
                    // Re-enable button on error
                    resetSaveButton();
                }
            });
        }

        function validateForm() {
            // Check required fields
            const requiredFields = [
                '#recruiter', '#jobTitle', '#jobType', '#location',
                '#vacancies', '#minSalary', '#maxSalary', '#jobDescription',
                '#requirements', '#experience', '#educationQualification', '#applyBy'
            ];

            for (const fieldId of requiredFields) {
                const field = $(fieldId);
                if (!field.val()) {
                    showToast(field.attr('placeholder') || 'Please fill all required fields', 'warning');
                    field.focus();
                    return false;
                }
            }

            // Validate salary range
            if (!validateSalary()) {
                showToast('Maximum salary must be greater than or equal to minimum salary', 'warning');
                $('#maxSalary').focus();
                return false;
            }

            // Validate character counts
            const descLength = $('#jobDescription').val().length;
            const reqLength = $('#requirements').val().length;

            if (descLength < 100) {
                showToast('Job description must be at least 100 characters', 'warning');
                $('#jobDescription').focus();
                return false;
            }

            if (reqLength < 50) {
                showToast('Requirements must be at least 50 characters', 'warning');
                $('#requirements').focus();
                return false;
            }

            // Validate apply by date
            const applyBy = new Date($('#applyBy').val());
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (applyBy < today) {
                showToast('Apply by date cannot be in the past', 'warning');
                $('#applyBy').focus();
                return false;
            }

            return true;
        }

        // Helper function to validate salary
        function validateSalary() {
            const minSalary = parseFloat($('#minSalary').val()) || 0;
            const maxSalary = parseFloat($('#maxSalary').val()) || 0;

            if (maxSalary > 0 && maxSalary < minSalary) {
                return false;
            }
            return true;
        }

        // Helper function to reset save button state
        function resetSaveButton() {
            const saveBtn = $('#saveJobBtn');
            const btnText = saveBtn.find('.btn-text');
            const spinner = saveBtn.find('.spinner-border');

            saveBtn.prop('disabled', false);
            btnText.text('Update Job');
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
                toast.removeClass('bg-success bg-info bg-danger').addClass('bg-warning text-dark');
            } else if (type === 'info') {
                toast.removeClass('bg-success bg-danger bg-warning').addClass('bg-info');
            } else {
                toast.removeClass('bg-danger bg-info bg-warning').addClass('bg-success');
            }

            // Show toast
            const bsToast = new bootstrap.Toast(toast[0]);
            bsToast.show();
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function confirmDelete(jobId) {
            if (confirm('Are you sure you want to delete this job? This will also delete all applications. This action cannot be undone.')) {
                deleteJob(jobId);
            }
        }

        function deleteJob(jobId) {
            $.ajax({
                url: API_BASE + 'delete_job.php',
                type: 'POST',
                data: {
                    id: jobId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Job deleted successfully!', 'success');
                        setTimeout(function() {
                            window.location.href = 'job-admin.php';
                        }, 1500);
                    } else {
                        showToast('Failed to delete: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showToast('Error deleting job. Please try again.', 'danger');
                }
            });
        }

        function duplicateJob(jobId) {
            if (confirm('Create a duplicate of this job?')) {
                $.ajax({
                    url: API_BASE + 'duplicate_job.php',
                    type: 'POST',
                    data: {
                        id: jobId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showToast('Job duplicated successfully!', 'success');
                            setTimeout(function() {
                                window.location.href = 'job-edit.php?id=' + response.new_job_id;
                            }, 1500);
                        } else {
                            showToast('Failed to duplicate: ' + response.message, 'danger');
                        }
                    },
                    error: function() {
                        showToast('Error duplicating job. Please try again.', 'danger');
                    }
                });
            }
        }
    </script>
</body>

</html>