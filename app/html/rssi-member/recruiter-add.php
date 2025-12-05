<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
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

    <title>Add New Recruiter</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

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

        .file-size {
            font-size: 0.85em;
            color: #6c757d;
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

    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Add New Recruiter</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="job-admin.php">Job Admin</a></li>
                    <li class="breadcrumb-item active">Add Recruiter</li>
                </ol>
            </nav>
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
                                                <h2 class="mb-4 text-center">Register New Recruiter</h2>

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
                                                                <span id="toastMessage">Recruiter added successfully!</span>
                                                            </div>
                                                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Recruiter Registration Form -->
                                                <form id="recruiterRegistrationForm">
                                                    <h4 class="mb-3 border-bottom pb-2">Recruiter Information</h4>

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="fullName" class="form-label">Full Name</label>
                                                            <input type="text" class="form-control" id="fullName" name="fullName" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="companyName" class="form-label">Company Name</label>
                                                            <input type="text" class="form-control" id="companyName" name="companyName" required>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="email" class="form-label">Email Address</label>
                                                            <input type="email" class="form-control" id="email" name="email" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="phone" class="form-label">Phone Number</label>
                                                            <input type="tel" class="form-control" id="phone" name="phone" pattern="[0-9]{10}" required>
                                                            <div class="form-text">10-digit phone number</div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="aadharNumber" class="form-label">Aadhar Number</label>
                                                            <input type="text" class="form-control" id="aadharNumber" name="aadharNumber" pattern="[0-9]{12}" maxlength="12">
                                                            <div class="form-text">12-digit Aadhar number</div>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="aadharFile" class="form-label">Aadhar Card Upload</label>
                                                            <div class="file-upload-area" id="aadharUploadArea">
                                                                <i class="bi bi-cloud-upload" style="font-size: 2rem; color: #6c757d;"></i>
                                                                <p class="mt-2">Click to upload or drag and drop</p>
                                                                <p class="text-muted small">PDF, JPG, PNG (Max 2MB)</p>
                                                            </div>
                                                            <input type="file" class="form-control d-none" id="aadharFile" name="aadharFile" accept=".pdf,.jpg,.jpeg,.png">
                                                            <div class="file-preview" id="aadharPreview">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <strong id="aadharFileName"></strong>
                                                                        <br><small class="file-size" id="aadharFileSize"></small>
                                                                    </div>
                                                                    <button type="button" class="btn btn-sm btn-danger" id="removeAadharFile">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="companyAddress" class="form-label">Company Address</label>
                                                        <textarea class="form-control" id="companyAddress" name="companyAddress" rows="3" required></textarea>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="notes" class="form-label">Admin Notes (Optional)</label>
                                                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any additional information about this recruiter..."></textarea>
                                                    </div>

                                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                                        <a href="job-admin.php" class="btn btn-secondary me-md-2">Cancel</a>
                                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                                            <span class="submit-text">Register Recruiter</span>
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
                                                        <h4>Recruiter Registered Successfully!</h4>
                                                        <p>Recruiter has been registered and can now post jobs.</p>
                                                        <p>Login credentials have been sent to their email.</p>
                                                        <div class="mt-3">
                                                            <a href="recruiter-add.php" class="btn btn-primary me-2">Add Another Recruiter</a>
                                                            <a href="job-admin.php" class="btn btn-outline-primary">Back to Admin Panel</a>
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
        $(document).ready(function() {
            // API endpoint configuration
            const API_BASE = window.location.hostname === 'localhost' ?
                'http://localhost:8082/' :
                'https://login.rssi.in/';

            // File upload handling
            const fileInput = $('#aadharFile');
            const dropArea = $('#aadharUploadArea');
            const filePreview = $('#aadharPreview');
            const fileName = $('#aadharFileName');
            const fileSize = $('#aadharFileSize');

            // Click on drop area triggers file input
            dropArea.on('click', function(e) {
                if (!$(e.target).is('button')) {
                    fileInput.click();
                }
            });

            // Handle file selection
            fileInput.on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    displayFilePreview(file);
                }
            });

            // Drag and drop events
            dropArea.on('dragover', function(e) {
                e.preventDefault();
                dropArea.addClass('dragover');
            });

            dropArea.on('dragleave', function() {
                dropArea.removeClass('dragover');
            });

            dropArea.on('drop', function(e) {
                e.preventDefault();
                dropArea.removeClass('dragover');

                const file = e.originalEvent.dataTransfer.files[0];
                if (file) {
                    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                    if (allowedTypes.includes(file.type)) {
                        fileInput[0].files = e.originalEvent.dataTransfer.files;
                        displayFilePreview(file);
                    } else {
                        showToast('Please upload PDF, JPG, or PNG files only.', 'error');
                    }
                }
            });

            function displayFilePreview(file) {
                // Check file type
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    showToast('Please upload PDF, JPG, or PNG files only.', 'error');
                    return;
                }

                // Check file size (2MB limit)
                if (file.size > 2 * 1024 * 1024) {
                    showToast('File size should be less than 2MB.', 'error');
                    return;
                }

                fileName.text(file.name);
                fileSize.text(formatFileSize(file.size));
                filePreview.addClass('show');
            }

            // Remove file
            $('#removeAadharFile').on('click', function() {
                fileInput.val('');
                filePreview.removeClass('show');
            });

            // Form submission
            $('#recruiterRegistrationForm').on('submit', function(e) {
                e.preventDefault();

                // Validate form
                if (!validateForm()) {
                    return;
                }

                // Show loading state
                showLoading(true);

                const formData = new FormData();
                formData.append('full_name', $('#fullName').val());
                formData.append('company_name', $('#companyName').val());
                formData.append('email', $('#email').val());
                formData.append('phone', $('#phone').val());
                formData.append('aadhar_number', $('#aadharNumber').val());
                formData.append('company_address', $('#companyAddress').val());
                formData.append('notes', $('#notes').val());
                formData.append('created_by', 'admin'); // Indicate admin created
                formData.append('admin_id', '<?php echo $associatenumber; ?>'); // Current admin ID

                const aadharFile = $('#aadharFile')[0].files[0];
                if (aadharFile) {
                    formData.append('aadhar_file', aadharFile);
                }

                // Submit to API
                $.ajax({
                    url: API_BASE + 'register.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        showLoading(false);

                        if (response.success) {
                            // Show success message
                            $('#recruiterRegistrationForm').hide();
                            $('#successMessage').show();
                            showToast('Recruiter registered successfully!', 'success');
                        } else {
                            showToast(response.message || 'Registration failed. Please try again.', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showLoading(false);
                        let errorMsg = 'Error submitting form. Please try again.';
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

            // Validation function
            function validateForm() {
                // Basic validation
                const email = $('#email').val().trim();
                const phone = $('#phone').val().trim();
                const aadhar = $('#aadharNumber').val().trim();

                if (!validateEmail(email)) {
                    showToast('Please enter a valid email address', 'error');
                    return false;
                }

                if (!/^\d{10}$/.test(phone)) {
                    showToast('Please enter a valid 10-digit phone number', 'error');
                    return false;
                }

                // Validate Aadhar number ONLY if user entered something
                if (aadhar.trim() !== "") {
                    if (!/^\d{12}$/.test(aadhar)) {
                        showToast('Please enter a valid 12-digit Aadhar number', 'error');
                        return false;
                    }
                }

                // Validate Aadhar file ONLY if Aadhar number is filled (optional logic)
                // OR if you want file to be optional always, remove this entire block
                if (aadhar.trim() !== "") {
                    if (!$('#aadharFile')[0].files[0]) {
                        showToast('Please upload Aadhar card document', 'error');
                        return false;
                    }
                }

                return true;
            }

            // Helper functions
            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }

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
</body>

</html>