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

// // Fetch recruiters for dropdown
// $recruiters_query = "SELECT id, full_name, company_name, email FROM recruiters WHERE is_active = true ORDER BY company_name";
// $recruiters_result = pg_query($con, $recruiters_query);
// $recruiters = pg_fetch_all($recruiters_result) ?: [];

// // Store recruiter data as JSON for JavaScript access
// $recruiters_data = json_encode(array_column($recruiters, null, 'id'));
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

    <title>Add New Job</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <style>
        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
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
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            display: none;
        }

        .file-preview.show {
            display: block;
        }

        /* Global loading overlay - CENTERED */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }

        .loading-overlay-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 99999;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <div class="container">
                                <div class="row justify-content-center">
                                    <div class="col-lg-10">
                                        <div class="card shadow">
                                            <div class="card-body p-5">
                                                <h2 class="mb-4 text-center">Post New Job</h2>

                                                <!-- Loading Overlay -->
                                                <div class="loading-overlay" id="loadingOverlay">
                                                    <div class="loading-overlay-content">
                                                        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                        <h5 id="loadingText">Processing...</h5>
                                                        <p class="text-muted mt-2" id="loadingSubtext">Please wait</p>
                                                    </div>
                                                </div>

                                                <!-- Toast Notification -->
                                                <div class="toast-container">
                                                    <div class="toast align-items-center text-white bg-success border-0" id="successToast" role="alert" aria-live="assertive" aria-atomic="true">
                                                        <div class="d-flex">
                                                            <div class="toast-body">
                                                                <i class="bi bi-check-circle me-2"></i>
                                                                <span id="toastMessage">Job posted successfully!</span>
                                                            </div>
                                                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Job Post Form -->
                                                <form id="jobPostForm">
                                                    <h4 class="mb-3 border-bottom pb-2">Job Information</h4>

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="recruiter" class="form-label">Recruiter</label>
                                                            <select class="form-select select2-recruiter" id="recruiter" name="recruiter" required>
                                                                <option value="">Select Recruiter</option>
                                                                <!-- Options will be loaded via AJAX -->
                                                            </select>
                                                            <!-- <div class="form-text">Start typing to search for a recruiter...</div> -->
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="recruiterEmailDisplay" class="form-label">Recruiter Email</label>
                                                            <input type="email" class="form-control" id="recruiterEmailDisplay" readonly>
                                                            <div class="form-text">Email will auto-populate when you select a recruiter</div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-4 mb-3">
                                                            <label for="jobTitle" class="form-label">Job Title</label>
                                                            <input type="text" class="form-control" id="jobTitle" name="jobTitle" required>
                                                        </div>

                                                        <div class="col-md-4 mb-3">
                                                            <label for="jobType" class="form-label">Job Type</label>
                                                            <select class="form-select" id="jobType" name="jobType" required>
                                                                <option value="">Select Type</option>
                                                                <option value="full-time">Full Time</option>
                                                                <option value="part-time">Part Time</option>
                                                                <option value="contract">Contract</option>
                                                                <option value="internship">Internship</option>
                                                                <option value="remote">Remote</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-md-4 mb-3">
                                                            <label for="location" class="form-label">Location</label>
                                                            <input type="text" class="form-control" id="location" name="location" required>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-4 mb-3">
                                                            <label for="minSalary" class="form-label">Min Salary (₹ per month)</label>
                                                            <input type="number" class="form-control" id="minSalary" name="minSalary" min="0" placeholder="e.g., 25000" required>
                                                            <div class="form-text">Minimum expected salary</div>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label for="maxSalary" class="form-label">Max Salary (₹ per month)</label>
                                                            <input type="number" class="form-control" id="maxSalary" name="maxSalary" min="0" placeholder="e.g., 50000" required>
                                                            <div class="form-text">Maximum expected salary</div>
                                                        </div>
                                                        <div class="col-md-4 mb-3">
                                                            <label for="vacancies" class="form-label">Vacancies</label>
                                                            <input type="number" class="form-control" id="vacancies" name="vacancies" min="1" required>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="jobDescription" class="form-label">Job Description</label>
                                                        <textarea class="form-control" id="jobDescription" name="jobDescription" rows="5" required></textarea>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="requirements" class="form-label">Requirements</label>
                                                        <textarea class="form-control" id="requirements" name="requirements" rows="3" required></textarea>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="experience" class="form-label">Experience Required</label>
                                                            <input type="text" class="form-control" id="experience" name="experience" placeholder="e.g., 2-4 years" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="educationQualification" class="form-label">Educational Qualification</label>
                                                            <select class="form-select" id="educationQualification" name="educationQualification" required>
                                                                <option value="">Select Qualification</option>
                                                                <!-- Options will be populated by JavaScript -->
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="applyBy" class="form-label">Apply By Date</label>
                                                            <input type="date" class="form-control" id="applyBy" name="applyBy" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="jobFile" class="form-label">Additional Document (Optional)</label>
                                                            <div class="file-upload-area" id="jobFileUploadArea">
                                                                <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #6c757d;"></i>
                                                                <p class="mt-2">Click to upload or drag and drop</p>
                                                                <p class="text-muted small">PDF, DOC, DOCX (Max 5MB)</p>
                                                            </div>
                                                            <input type="file" class="form-control d-none" id="jobFile" name="jobFile" accept=".pdf,.doc,.docx">
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

                                                    <div class="mb-3">
                                                        <label for="benefits" class="form-label">Benefits (Optional)</label>
                                                        <textarea class="form-control" id="benefits" name="benefits" rows="2" placeholder="e.g., Health insurance, flexible hours, etc."></textarea>
                                                    </div>

                                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                                        <a href="job-admin.php" class="btn btn-secondary me-md-2">Cancel</a>
                                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                                            <span class="submit-text">Post Job</span>
                                                            <span class="loading-spinner" style="display: none;">
                                                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                                Processing...
                                                            </span>
                                                        </button>
                                                    </div>
                                                </form>

                                                <!-- Success Message -->
                                                <div id="successMessage" style="display: none;">
                                                    <div class="alert alert-success text-center">
                                                        <h4>Job Posted Successfully!</h4>
                                                        <p>The recruiter has been notified about the submitted job request. The job has been submitted for approval and will be visible to job seekers once approved.</p>
                                                        <div class="mt-3">
                                                            <a href="job-add.php" class="btn btn-primary me-2">Post Another Job</a>
                                                            <a href="job-admin.php" class="btn btn-outline-primary">Back to Admin Panel</a>
                                                            <a href="job-approval.php" class="btn btn-outline-success ms-2">View Pending Approvals</a>
                                                        </div>
                                                    </div>
                                                </div>
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

    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  

    <script>
        // API endpoint configuration
        const API_BASE = window.location.hostname === 'localhost' ?
            'http://localhost:8082/job-api/' :
            'https://login.rssi.in/job-api/';
        $(document).ready(function() {

            // Load education levels
            loadEducationLevels();

            // File upload handling for job file
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
                        showToast('Please upload PDF, DOC, or DOCX files only.', 'error');
                    }
                }
            });

            function displayJobFilePreview(file) {
                // Check file type
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!allowedTypes.includes(file.type)) {
                    showToast('Please upload PDF, DOC, or DOCX files only.', 'error');
                    return;
                }

                // Check file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    showToast('File size should be less than 5MB.', 'error');
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

            // Form submission
            $('#jobPostForm').on('submit', function(e) {
                e.preventDefault();

                // Validate form
                if (!validateForm()) {
                    return;
                }

                // Show loading state
                showLoading(true);

                const formData = new FormData();
                formData.append('recruiter_id', $('#recruiter').val());
                formData.append('email', $('#recruiterEmail').val()); // Add this line
                formData.append('job_title', $('#jobTitle').val());
                formData.append('job_type', $('#jobType').val());
                formData.append('location', $('#location').val());
                formData.append('min_salary', $('#minSalary').val());
                formData.append('max_salary', $('#maxSalary').val());
                formData.append('vacancies', $('#vacancies').val());
                formData.append('job_description', $('#jobDescription').val());
                formData.append('requirements', $('#requirements').val());
                formData.append('experience', $('#experience').val());
                formData.append('education_qualification', $('#educationQualification').val());
                formData.append('apply_by', $('#applyBy').val());
                formData.append('benefits', $('#benefits').val());
                formData.append('created_by', 'admin'); // Indicate admin created
                formData.append('admin_id', '<?php echo $associatenumber; ?>'); // Current admin ID
                formData.append('status', 'approved'); // Auto-approve admin posts

                const jobFile = $('#jobFile')[0].files[0];
                if (jobFile) {
                    formData.append('job_file', jobFile);
                }

                // Submit to API
                $.ajax({
                    url: API_BASE + 'post_job.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        showLoading(false);

                        if (response.success) {
                            // Show success message
                            $('#jobPostForm').hide();
                            $('#successMessage').show();
                            showToast('Job posted successfully!', 'success');
                        } else {
                            showToast(response.message || 'Job posting failed. Please try again.', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showLoading(false);
                        let errorMsg = 'Error submitting job. Please try again.';
                        if (xhr.responseText) {
                            try {
                                const errorResponse = JSON.parse(xhr.responseText);
                                if (errorResponse.message) errorMsg = errorResponse.message;
                            } catch (e) {}
                        }
                        showToast(errorMsg, 'error');
                    }
                });
            });

            // Load education levels function
            function loadEducationLevels() {
                $.ajax({
                    url: API_BASE + 'get_education_levels.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data) {
                            const select = $('#educationQualification');
                            response.data.forEach(level => {
                                select.append(`<option value="${level.id}">${level.name}</option>`);
                            });
                        }
                    },
                    error: function() {
                        console.error('Failed to load education levels');
                    }
                });
            }

            // Validation function
            function validateForm() {
                // Basic validation
                if (!$('#recruiter').val()) {
                    showToast('Please select a recruiter', 'error');
                    return false;
                }

                const minSalary = $('#minSalary').val();
                const maxSalary = $('#maxSalary').val();

                // Salary validation
                if (minSalary && maxSalary && parseInt(maxSalary) < parseInt(minSalary)) {
                    showToast('Maximum salary must be greater than or equal to minimum salary', 'error');
                    return false;
                }

                // Apply by date validation
                const applyBy = new Date($('#applyBy').val());
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (applyBy < today) {
                    showToast('Apply by date cannot be in the past', 'error');
                    return false;
                }

                return true;
            }

            // Helper functions
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function showLoading(show) {
                if (show) {
                    $('#loadingOverlay').fadeIn();
                    $('#submitBtn').prop('disabled', true);
                    $('.submit-text').hide();
                    $('.loading-spinner').show();
                } else {
                    $('#loadingOverlay').fadeOut();
                    $('#submitBtn').prop('disabled', false);
                    $('.submit-text').show();
                    $('.loading-spinner').hide();
                }
            }

            function showToast(message, type = 'success') {
                const toast = $('#successToast');
                const toastMessage = $('#toastMessage');

                // Set message
                toastMessage.text(message);

                // Set color based on type
                if (type === 'error') {
                    toast.removeClass('bg-success').addClass('bg-danger');
                } else if (type === 'info') {
                    toast.removeClass('bg-success').addClass('bg-info');
                } else {
                    toast.removeClass('bg-danger bg-info').addClass('bg-success');
                }

                // Show toast
                const bsToast = new bootstrap.Toast(toast[0]);
                bsToast.show();
            }
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("input[required], select[required], textarea[required]").forEach(function(field) {
                const label = document.querySelector(`label[for='${field.id}']`);
                if (label && !label.querySelector("span[data-required]")) {
                    const span = document.createElement("span");
                    span.setAttribute("data-required", "true");
                    span.classList.add("text-danger");
                    span.textContent = " *";
                    label.appendChild(span);
                }
            });
        });
    </script>
    <script>
        // Initialize Select2 for recruiter dropdown with AJAX
        $(document).ready(function() {
            // Initialize Select2
            $('#recruiter').select2({
                // theme: 'bootstrap-5',
                placeholder: 'Search for a recruiter...',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: API_BASE + 'get_recruiters.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            search: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.data,
                            pagination: {
                                more: data.pagination ? data.pagination.more : false
                            }
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1,
                templateResult: formatRecruiterResult,
                templateSelection: formatRecruiterSelection
            });

            // Format how results are displayed in dropdown
            function formatRecruiterResult(recruiter) {
                if (recruiter.loading) {
                    return 'Searching...';
                }

                if (!recruiter.id) {
                    return recruiter.text;
                }

                const $container = $('<div>');
                $container.append($('<strong>').text(recruiter.company));
                $container.append($('<div class="small">').text(recruiter.name + ' • ' + recruiter.email));
                return $container;
            }

            // Format how selected item is displayed
            function formatRecruiterSelection(recruiter) {
                if (!recruiter.id) {
                    return recruiter.text;
                }
                return recruiter.company + ' - ' + recruiter.name;
            }

            // Handle recruiter selection change
            $('#recruiter').on('change', function() {
                const selectedId = $(this).val();
                const selectedOption = $(this).select2('data')[0];

                if (selectedOption && selectedOption.email) {
                    // Update email display field
                    $('#recruiterEmailDisplay').val(selectedOption.email);

                    // Store email in hidden field for form submission
                    if ($('#recruiterEmail').length === 0) {
                        $('#recruiter').after('<input type="hidden" id="recruiterEmail" name="recruiterEmail">');
                    }
                    $('#recruiterEmail').val(selectedOption.email);

                    // Also store additional data if needed
                    if ($('#recruiterName').length === 0) {
                        $('#recruiterEmail').after('<input type="hidden" id="recruiterName" name="recruiterName">');
                        $('#recruiterEmail').after('<input type="hidden" id="recruiterCompany" name="recruiterCompany">');
                    }
                    $('#recruiterName').val(selectedOption.name || '');
                    $('#recruiterCompany').val(selectedOption.company || '');

                    console.log('Selected Recruiter:', {
                        id: selectedId,
                        name: selectedOption.name,
                        company: selectedOption.company,
                        email: selectedOption.email
                    });
                } else {
                    // Clear fields if no recruiter selected
                    $('#recruiterEmailDisplay').val('');
                    $('#recruiterEmail').val('');
                    $('#recruiterName').val('');
                    $('#recruiterCompany').val('');
                }
            });

            // Add hidden fields for additional recruiter data
            if ($('#recruiterEmail').length === 0) {
                $('#recruiter').after('<input type="hidden" id="recruiterEmail" name="recruiterEmail">');
                $('#recruiter').after('<input type="hidden" id="recruiterName" name="recruiterName">');
                $('#recruiter').after('<input type="hidden" id="recruiterCompany" name="recruiterCompany">');
            }
        });
    </script>
</body>

</html>