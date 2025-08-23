<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// ---------------------------
// Check mobile number (AJAX)
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_mobile'])) {
    header('Content-Type: application/json');

    $mobile = pg_escape_string($con, $_POST['mobile']);

    // Validate mobile format
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid mobile number. Please enter exactly 10 digits.']);
        exit;
    }

    // Check if exists
    $checkQuery = "SELECT id, name FROM public_health_records WHERE contact_number = '$mobile'";
    $result = pg_query($con, $checkQuery);

    if (pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $beneficiary_id = $row['id'];

        // Check if any students are linked to this beneficiary
        $studentQuery = "SELECT psr.student_id, s.studentname 
                         FROM parent_student_relationships psr
                         JOIN rssimyprofile_student s ON psr.student_id = s.student_id
                         WHERE psr.parent_id = '$beneficiary_id'";
        $studentResult = pg_query($con, $studentQuery);

        $linkedStudents = [];
        while ($student = pg_fetch_assoc($studentResult)) {
            $linkedStudents[] = $student;
        }

        echo json_encode([
            'status' => 'error',
            'message' => 'This mobile number is already registered',
            'beneficiary_id' => $row['id'],
            'name' => $row['name'],
            'linked_students' => $linkedStudents,
            'has_linked_students' => !empty($linkedStudents)
        ]);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Mobile number available']);
    }
    exit;
}

// ---------------------------
// Link beneficiary to student (AJAX)
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_student'])) {
    header('Content-Type: application/json');

    $beneficiary_id = pg_escape_string($con, $_POST['beneficiary_id']);
    $student_id = pg_escape_string($con, $_POST['student_id']);

    // Check if student exists
    $studentQuery = "SELECT studentname FROM rssimyprofile_student WHERE student_id = '$student_id'";
    $studentResult = pg_query($con, $studentQuery);

    if (pg_num_rows($studentResult) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Student ID not found in our system']);
        exit;
    }

    // Check if relationship already exists
    $checkQuery = "SELECT * FROM parent_student_relationships 
                   WHERE parent_id = '$beneficiary_id' AND student_id = '$student_id'";
    $checkResult = pg_query($con, $checkQuery);

    if (pg_num_rows($checkResult) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'This relationship already exists']);
        exit;
    }

    // Check parent count
    $parentCountQuery = "SELECT COUNT(*) as parent_count FROM parent_student_relationships WHERE student_id = '$student_id'";
    $parentCountResult = pg_query($con, $parentCountQuery);
    $parentCount = pg_fetch_assoc($parentCountResult)['parent_count'];

    if ($parentCount >= 2) {
        echo json_encode(['status' => 'error', 'message' => 'This student already has 2 parents registered']);
        exit;
    }

    // Create relationship
    $relationshipSql = "INSERT INTO parent_student_relationships (parent_id, student_id) 
                        VALUES ('$beneficiary_id', '$student_id')";

    if (pg_query($con, $relationshipSql)) {
        // Mark as parent
        $updateSql = "UPDATE public_health_records SET is_parent = TRUE WHERE id = '$beneficiary_id'";
        pg_query($con, $updateSql);

        echo json_encode(['status' => 'success', 'message' => 'Student linked successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error linking student: ' . pg_last_error($con)]);
    }
    exit;
}

// ---------------------------
// Verify student ID (AJAX)
// ---------------------------
// Add this after the mobile check endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_student_id'])) {
    header('Content-Type: application/json');

    $student_id = pg_escape_string($con, $_POST['student_id']);

    // Check if student exists in your student database (replace with your actual student table)
    $studentQuery = "SELECT studentname FROM rssimyprofile_student WHERE student_id = '$student_id'";
    $studentResult = pg_query($con, $studentQuery);

    if (pg_num_rows($studentResult) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Student ID not found in our system']);
        exit;
    }

    $student = pg_fetch_assoc($studentResult);

    // Check how many parents are already linked to this student
    $parentCountQuery = "SELECT COUNT(*) as parent_count FROM parent_student_relationships WHERE student_id = '$student_id'";
    $parentCountResult = pg_query($con, $parentCountQuery);
    $parentCount = pg_fetch_assoc($parentCountResult)['parent_count'];

    // Get existing parent details if any
    $existingParentsQuery = "SELECT p.id, p.name 
                             FROM parent_student_relationships psr
                             JOIN public_health_records p ON psr.parent_id = p.id
                             WHERE psr.student_id = '$student_id'";
    $existingParentsResult = pg_query($con, $existingParentsQuery);
    $existingParents = [];

    while ($row = pg_fetch_assoc($existingParentsResult)) {
        $existingParents[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'student_name' => $student['studentname'],
        'parent_count' => $parentCount,
        'existing_parents' => $existingParents
    ]);
    exit;
}
// ---------------------------
// Handle form submission
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $mobile = pg_escape_string($con, $_POST['contact_number']);

    $name = pg_escape_string($con, $_POST['name']);
    $email = pg_escape_string($con, $_POST['email'] ?? null);
    $dob = pg_escape_string($con, $_POST['date_of_birth']);
    $gender = pg_escape_string($con, $_POST['gender']);
    $referral = pg_escape_string($con, $_POST['referral_source']);

    // ---------------------------
    // Handle photo upload
    // ---------------------------
    $photoUrl = null;
    if (!empty($_POST['photo_data'])) {
        $photoData = $_POST['photo_data'];
        $photoData = str_replace('data:image/jpeg;base64,', '', $photoData);
        $photoData = str_replace(' ', '+', $photoData);
        $data = base64_decode($photoData);

        // Temporary file
        $tempFileName = 'temp_profile_' . $mobile . '_' . time() . '.jpg';
        $tempFilePath = sys_get_temp_dir() . '/' . $tempFileName;
        file_put_contents($tempFilePath, $data);

        // Prepare file for Drive
        $uploadedFile = [
            'name' => 'profile_' . $mobile . '_' . time() . '.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $tempFilePath,
            'error' => 0,
            'size' => filesize($tempFilePath)
        ];

        // Google Drive folder
        $parentFolderId = '1LtKZNkfWzxrgMTN2GSHF1O-d6AmFsLRD'; // change to actual folder ID
        $photoUrl = uploadeToDrive($uploadedFile, $parentFolderId, 'profile_' . $mobile);

        unlink($tempFilePath); // cleanup
    }

    // ---------------------------
    // Insert record
    // ---------------------------
    $sql = "INSERT INTO public_health_records 
        (contact_number, name, email, date_of_birth, gender, referral_source, profile_photo, registration_completed, created_at)
        VALUES 
        ('$mobile', '$name', '$email', '$dob', '$gender', '$referral', '$photoUrl', TRUE, CURRENT_TIMESTAMP)";

    if (pg_query($con, $sql)) {
        // Fetch new ID
        $idQuery = "SELECT id FROM public_health_records WHERE contact_number = '$mobile' ORDER BY created_at DESC LIMIT 1";
        $idResult = pg_query($con, $idQuery);
        $newRecord = pg_fetch_assoc($idResult);
        $new_id = $newRecord['id'];

        // ---------------------------
        // Parent registration
        // ---------------------------
        // If this is a parent registration, create the relationship
        if (isset($_POST['is_parent']) && $_POST['is_parent'] == 'yes' && !empty($_POST['student_id'])) {
            $student_id = pg_escape_string($con, $_POST['student_id']);

            // Check parent count again (in case of race conditions)
            $parentCountQuery = "SELECT COUNT(*) as parent_count FROM parent_student_relationships WHERE student_id = '$student_id'";
            $parentCountResult = pg_query($con, $parentCountQuery);
            $parentCount = pg_fetch_assoc($parentCountResult)['parent_count'];

            if ($parentCount < 2) {
                // Create relationship
                $relationshipSql = "INSERT INTO parent_student_relationships (parent_id, student_id) 
                                VALUES ('$new_id', '$student_id')";
                pg_query($con, $relationshipSql);

                // Mark as parent
                $updateSql = "UPDATE public_health_records SET is_parent = TRUE WHERE id = '$new_id'";
                pg_query($con, $updateSql);
            } else {
                // Log error - this shouldn't happen due to frontend validation
                error_log("Attempted to add third parent to student $student_id");
            }
        }

        // Redirect with query params (avoids resubmit)
        header("Location: " . $_SERVER['PHP_SELF'] . "?success=1&beneficiary_id=" . urlencode($new_id));
        exit;
    } else {
        $error = "Error: " . pg_last_error($con);
    }
}

// ---------------------------
// Show success message
// ---------------------------
if (isset($_GET['success'])) {
    $success = "Registration successful! Thank you.";
    $beneficiary_id = $_GET['beneficiary_id'] ?? null;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Health Portal Registration</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a8bfc;
            --secondary-color: #f8f9fa;
            --accent-color: #e9f2ff;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            background-color: var(--primary-color);
            padding: 1.5rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(74, 139, 252, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
        }

        .btn-primary:hover {
            background-color: #3a7be0;
        }

        .photo-container {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            overflow: hidden;
            margin: 0 auto 20px;
            border: 3px solid var(--primary-color);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .photo-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .photo-placeholder {
            font-size: 40px;
            color: var(--primary-color);
        }

        .photo-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }

        #videoElement {
            width: 100%;
            height: auto;
            background-color: #000;
            display: none;
        }

        .camera-container {
            position: relative;
            margin-bottom: 20px;
            display: none;
        }

        .camera-actions {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .section-title {
            font-size: 1.1rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            font-size: 1.2rem;
        }

        .required-field::after {
            content: " *";
            color: red;
        }

        .step {
            display: none;
        }

        .step.active {
            display: block;
        }

        #mobileStatus {
            min-height: 24px;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header text-white">
                        <h4 class="mb-0 text-center"><i class="fas fa-user-plus me-2"></i>New User Registration</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success text-center">
                                <i class="fas fa-check-circle me-2"></i><?= $success ?>
                                <?php if (!empty($beneficiary_id)): ?>
                                    <br><strong>Your Beneficiary ID: <?= htmlspecialchars($beneficiary_id) ?></strong>
                                <?php endif; ?>
                            </div>
                            <div class="text-center mt-4">
                                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-primary px-4">
                                    <i class="fas fa-user-plus me-2"></i>New Registration
                                </a>
                            </div>
                        <?php elseif (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                            </div>
                        <?php else: ?>
                            <!-- Step 1: Mobile Number Verification -->
                            <div id="step1" class="step active">
                                <div class="text-center mb-4">
                                    <i class="fas fa-mobile-alt fa-3x mb-3" style="color: var(--primary-color);"></i>
                                    <h5>Enter Your Mobile Number</h5>
                                    <p class="text-muted">We'll use this for important notifications</p>
                                </div>

                                <div class="mb-3">
                                    <label for="mobile" class="form-label required-field">Mobile Number</label>
                                    <input type="tel" class="form-control" id="mobile" name="mobile" required maxlength="10">
                                    <small class="text-muted">Please enter exactly 10 digits</small>
                                </div>

                                <div id="mobileStatus" class="mb-3"></div>

                                <div class="d-grid">
                                    <button type="button" id="checkMobileBtn" class="btn btn-primary">
                                        <span id="mobileSpinner" class="spinner-border spinner-border-sm me-1" style="display: none;"></span>
                                        Check Availability
                                    </button>
                                </div>
                            </div>

                            <!-- Step 2: Registration Form -->
                            <div id="step2" class="step">
                                <form id="registrationForm" method="post" enctype="multipart/form-data">
                                    <input type="hidden" id="contact_number" name="contact_number">

                                    <div class="mb-4 text-center">
                                        <h5 class="section-title">
                                            <i class="fas fa-id-card"></i> Basic Information
                                        </h5>

                                        <div class="photo-container">
                                            <div class="photo-placeholder">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <img id="photoPreview" class="photo-preview" src="#" alt="Profile preview">
                                        </div>

                                        <div class="photo-actions">
                                            <button type="button" id="takePhotoBtn" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-camera me-1"></i> Take Photo
                                            </button>
                                            <button type="button" id="uploadPhotoBtn" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-upload me-1"></i> Upload
                                            </button>
                                            <input type="file" id="photoUpload" accept="image/*" capture="user" style="display: none;">
                                        </div>

                                        <div class="camera-container">
                                            <video id="videoElement" autoplay playsinline></video>
                                            <div class="camera-actions">
                                                <button type="button" id="captureBtn" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-camera me-1"></i> Capture
                                                </button>
                                                <button type="button" id="cancelCaptureBtn" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-times me-1"></i> Cancel
                                                </button>
                                            </div>
                                        </div>

                                        <input type="hidden" id="photoData" name="photo_data">
                                    </div>

                                    <div class="mb-4">
                                        <div class="mb-3">
                                            <label for="name" class="form-label required-field">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="date_of_birth" class="form-label required-field">Date of Birth</label>
                                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="gender" class="form-label required-field">Gender</label>
                                                <select class="form-select" id="gender" name="gender" required>
                                                    <option value="" selected disabled>Select Gender</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <h5 class="section-title">
                                            <i class="fas fa-users"></i> Parent-Student Relationship
                                        </h5>

                                        <div class="mb-3">
                                            <label class="form-label required-field">Are you a parent of a student registered in RSSI NGO or Kalpana Buds School?</label>
                                            <div class="d-flex gap-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="is_parent" id="is_parent_yes" value="yes">
                                                    <label class="form-check-label" for="is_parent_yes">Yes</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="is_parent" id="is_parent_no" value="no" checked>
                                                    <label class="form-check-label" for="is_parent_no">No</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="studentIdSection" style="display: none;">
                                            <div class="mb-3">
                                                <label for="student_id" class="form-label">Student ID</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Enter student ID">
                                                    <button type="button" class="btn btn-outline-secondary" id="verifyStudentBtn">
                                                        <span id="studentSpinner" class="spinner-border spinner-border-sm me-1" style="display: none;"></span>
                                                        Verify
                                                    </button>
                                                </div>
                                                <small class="text-muted">Please enter the official student ID</small>
                                            </div>

                                            <div id="studentStatus" class="mb-3"></div>

                                            <div id="existingParentsInfo" class="alert alert-info" style="display: none;">
                                                <h6>Existing Parents for this Student:</h6>
                                                <div id="parentsList"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <h5 class="section-title">
                                            <i class="fas fa-envelope"></i> Contact Details
                                        </h5>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email">
                                            <small class="text-muted">Optional - for receiving reports</small>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <h5 class="section-title">
                                            <i class="fas fa-info-circle"></i> Additional Information
                                        </h5>

                                        <div class="mb-3">
                                            <label for="referral_source" class="form-label required-field">How did you hear about us?</label>
                                            <select class="form-select" id="referral_source" name="referral_source" required>
                                                <option value="" selected disabled>Select option</option>
                                                <option value="Doctor Reference">Doctor Reference</option>
                                                <option value="Friend/Family">Friend/Family</option>
                                                <option value="Google Search">Google Search</option>
                                                <option value="Social Media">Social Media</option>
                                                <option value="Advertisement">Advertisement</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="d-grid mt-4">
                                        <button type="submit" name="register" class="btn btn-primary btn-lg">
                                            <i class="fas fa-user-check me-2"></i> Complete Registration
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center mt-3 text-muted">
                    <small>Already have an account? <a href="#">Sign in here</a></small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const mobileInput = document.getElementById('mobile');
            const contactNumber = document.getElementById('contact_number');
            const checkMobileBtn = document.getElementById('checkMobileBtn');
            const mobileStatus = document.getElementById('mobileStatus');
            const mobileSpinner = document.getElementById('mobileSpinner');

            // Photo capture elements
            const takePhotoBtn = document.getElementById('takePhotoBtn');
            const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
            const photoUpload = document.getElementById('photoUpload');
            const photoPreview = document.getElementById('photoPreview');
            const photoPlaceholder = document.querySelector('.photo-placeholder');
            const photoData = document.getElementById('photoData');
            const videoElement = document.getElementById('videoElement');
            const cameraContainer = document.querySelector('.camera-container');
            const captureBtn = document.getElementById('captureBtn');
            const cancelCaptureBtn = document.getElementById('cancelCaptureBtn');

            // Mobile number validation
            mobileInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
            });

            // Check mobile availability
            checkMobileBtn.addEventListener('click', function() {
                const mobile = mobileInput.value.trim();

                if (mobile.length !== 10) {
                    mobileStatus.innerHTML = '<div class="alert alert-danger">Please enter a valid 10-digit mobile number</div>';
                    return;
                }

                checkMobileBtn.disabled = true;
                mobileSpinner.style.display = 'inline-block';

                fetch('register_beneficiary.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `check_mobile=1&mobile=${mobile}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            mobileStatus.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                            contactNumber.value = mobile;
                            step1.classList.remove('active');
                            step2.classList.add('active');
                        } else {
                            // Inside the mobile check response handler
                            if (data.status === 'error') {
                                // Show beneficiary details if available
                                let errorHtml = '<div class="alert alert-danger">' + data.message;

                                if (data.beneficiary_id && data.name) {
                                    errorHtml += `<br><small>Beneficiary ID: ${data.beneficiary_id}, Name: ${data.name}</small>`;

                                    // Show linked students if any
                                    if (data.has_linked_students) {
                                        errorHtml += `<br><br><strong>Linked Students:</strong><ul>`;
                                        data.linked_students.forEach(student => {
                                            errorHtml += `<li>${student.studentname} (ID: ${student.student_id})</li>`;
                                        });
                                        errorHtml += `</ul>`;
                                    } else {
                                        errorHtml += `<br><br><div class="mt-3">
                <p class="mb-2">Does your child study here? If yes, you can link them to avail exclusive offers.</p>
                <div class="input-group mb-2">
                    <input type="text" class="form-control" id="link_student_id" placeholder="Enter student ID">
                    <button type="button" class="btn btn-outline-primary" id="linkStudentBtn">
                        <span id="linkSpinner" class="spinner-border spinner-border-sm me-1" style="display: none;"></span>
                        Link Student
                    </button>
                </div>
                <div id="linkStatus"></div>
            </div>`;
                                    }
                                }

                                errorHtml += '</div>';
                                mobileStatus.innerHTML = errorHtml;

                                // Add event listener for linking student if needed
                                if (!data.has_linked_students) {
                                    setTimeout(() => {
                                        const linkStudentBtn = document.getElementById('linkStudentBtn');
                                        const linkStudentId = document.getElementById('link_student_id');
                                        const linkStatus = document.getElementById('linkStatus');
                                        const linkSpinner = document.getElementById('linkSpinner');

                                        if (linkStudentBtn) {
                                            linkStudentBtn.addEventListener('click', function() {
                                                const studentId = linkStudentId.value.trim();

                                                if (!studentId) {
                                                    linkStatus.innerHTML = '<div class="alert alert-danger mt-2">Please enter a student ID</div>';
                                                    return;
                                                }

                                                linkStudentBtn.disabled = true;
                                                linkSpinner.style.display = 'inline-block';

                                                fetch('register_beneficiary.php', {
                                                        method: 'POST',
                                                        headers: {
                                                            'Content-Type': 'application/x-www-form-urlencoded',
                                                        },
                                                        body: `link_student=1&beneficiary_id=${data.beneficiary_id}&student_id=${studentId}`
                                                    })
                                                    .then(response => response.json())
                                                    .then(linkData => {
                                                        if (linkData.status === 'success') {
                                                            linkStatus.innerHTML = '<div class="alert alert-success mt-2">' + linkData.message + '</div>';
                                                            // Refresh the page after a short delay
                                                            setTimeout(() => {
                                                                location.reload();
                                                            }, 2000);
                                                        } else {
                                                            linkStatus.innerHTML = '<div class="alert alert-danger mt-2">' + linkData.message + '</div>';
                                                        }
                                                    })
                                                    .catch(error => {
                                                        console.error('Error:', error);
                                                        linkStatus.innerHTML = '<div class="alert alert-danger mt-2">Error linking student</div>';
                                                    })
                                                    .finally(() => {
                                                        linkStudentBtn.disabled = false;
                                                        linkSpinner.style.display = 'none';
                                                    });
                                            });
                                        }
                                    }, 100);
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        mobileStatus.innerHTML = '<div class="alert alert-danger">Error checking mobile number</div>';
                    })
                    .finally(() => {
                        checkMobileBtn.disabled = false;
                        mobileSpinner.style.display = 'none';
                    });
            });

            // Photo upload handler
            uploadPhotoBtn.addEventListener('click', function() {
                photoUpload.click();
            });

            photoUpload.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    const file = e.target.files[0];
                    const reader = new FileReader();

                    reader.onload = function(event) {
                        photoPreview.src = event.target.result;
                        photoPreview.style.display = 'block';
                        photoPlaceholder.style.display = 'none';
                        photoData.value = event.target.result;
                    };

                    reader.readAsDataURL(file);
                }
            });

            // Camera capture handler
            takePhotoBtn.addEventListener('click', async function() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            width: {
                                ideal: 640
                            },
                            height: {
                                ideal: 480
                            },
                            facingMode: 'user'
                        },
                        audio: false
                    });

                    videoElement.srcObject = stream;
                    videoElement.style.display = 'block';
                    cameraContainer.style.display = 'block';
                    takePhotoBtn.style.display = 'none';
                    uploadPhotoBtn.style.display = 'none';

                } catch (err) {
                    console.error("Error accessing camera: ", err);
                    alert("Could not access the camera. Please check permissions.");
                }
            });

            // Capture photo from camera
            captureBtn.addEventListener('click', function() {
                const canvas = document.createElement('canvas');
                canvas.width = videoElement.videoWidth;
                canvas.height = videoElement.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

                const imageData = canvas.toDataURL('image/jpeg');
                photoPreview.src = imageData;
                photoPreview.style.display = 'block';
                photoPlaceholder.style.display = 'none';
                photoData.value = imageData;

                // Stop camera and hide
                const stream = videoElement.srcObject;
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }

                videoElement.style.display = 'none';
                cameraContainer.style.display = 'none';
                takePhotoBtn.style.display = 'block';
                uploadPhotoBtn.style.display = 'block';
            });

            // Cancel camera capture
            cancelCaptureBtn.addEventListener('click', function() {
                const stream = videoElement.srcObject;
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }

                videoElement.style.display = 'none';
                cameraContainer.style.display = 'none';
                takePhotoBtn.style.display = 'block';
                uploadPhotoBtn.style.display = 'block';
            });
        });
    </script>
    <script>
        // Add these variables at the top with other element declarations
        const isParentYes = document.getElementById('is_parent_yes');
        const isParentNo = document.getElementById('is_parent_no');
        const studentIdSection = document.getElementById('studentIdSection');
        const studentIdInput = document.getElementById('student_id');
        const verifyStudentBtn = document.getElementById('verifyStudentBtn');
        const studentStatus = document.getElementById('studentStatus');
        const studentSpinner = document.getElementById('studentSpinner');
        const existingParentsInfo = document.getElementById('existingParentsInfo');
        const parentsList = document.getElementById('parentsList');
        const submitButton = document.querySelector('button[name="register"]');

        // Track student verification status
        let isStudentVerified = false;

        // Toggle student ID section based on radio button
        function toggleStudentSection() {
            const isParent = isParentYes.checked;
            studentIdSection.style.display = isParent ? 'block' : 'none';

            if (!isParent) {
                studentIdInput.value = '';
                studentStatus.innerHTML = '';
                existingParentsInfo.style.display = 'none';
                isStudentVerified = false;
                updateSubmitButton();
            } else {
                isStudentVerified = false;
                updateSubmitButton();
            }
        }

        isParentYes.addEventListener('change', toggleStudentSection);
        isParentNo.addEventListener('change', toggleStudentSection);

        // Verify student ID
        verifyStudentBtn.addEventListener('click', function() {
            const studentId = studentIdInput.value.trim();

            if (!studentId) {
                studentStatus.innerHTML = '<div class="alert alert-danger">Please enter a student ID</div>';
                return;
            }

            verifyStudentBtn.disabled = true;
            studentSpinner.style.display = 'inline-block';
            studentStatus.innerHTML = '';

            fetch('register_beneficiary.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `check_student_id=1&student_id=${studentId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        let statusHtml = `<div class="alert alert-success">
                Student verified: ${data.student_name}<br>
                Currently has ${data.parent_count} parent(s) registered
            </div>`;

                        studentStatus.innerHTML = statusHtml;

                        // Show existing parents if any
                        if (data.existing_parents && data.existing_parents.length > 0) {
                            let parentsHtml = '<ul class="mb-0">';
                            data.existing_parents.forEach(parent => {
                                parentsHtml += `<li>${parent.name} (ID: ${parent.id})</li>`;
                            });
                            parentsHtml += '</ul>';

                            parentsList.innerHTML = parentsHtml;
                            existingParentsInfo.style.display = 'block';

                            // Show warning if already 2 parents
                            if (data.parent_count >= 2) {
                                studentStatus.innerHTML += '<div class="alert alert-warning mt-2">This student already has 2 parents registered. You cannot add more parents.</div>';
                                verifyStudentBtn.disabled = true;
                                isStudentVerified = false;
                            } else {
                                isStudentVerified = true;
                            }
                        } else {
                            existingParentsInfo.style.display = 'none';
                            isStudentVerified = true;
                        }
                    } else {
                        studentStatus.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                        isStudentVerified = false;
                    }
                    updateSubmitButton();
                })
                .catch(error => {
                    console.error('Error:', error);
                    studentStatus.innerHTML = '<div class="alert alert-danger">Error verifying student ID</div>';
                    isStudentVerified = false;
                    updateSubmitButton();
                })
                .finally(() => {
                    verifyStudentBtn.disabled = false;
                    studentSpinner.style.display = 'none';
                });
        });

        // Update submit button state
        function updateSubmitButton() {
            if (isParentYes.checked && !isStudentVerified) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-ban me-2"></i> Please verify student ID first';
            } else {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-user-check me-2"></i> Complete Registration';
            }
        }

        // Update form submission to include parent data
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const isParent = isParentYes.checked;
            const studentId = studentIdInput.value.trim();

            if (isParent && !isStudentVerified) {
                e.preventDefault();
                studentStatus.innerHTML = '<div class="alert alert-danger">Please verify the student ID before submitting</div>';
                studentIdSection.scrollIntoView({
                    behavior: 'smooth'
                });
                return;
            }

            // Additional validation for maximum parents
            if (isParent && studentId) {
                const parentCountMatch = studentStatus.textContent.match(/Currently has (\d+) parent/);
                if (parentCountMatch && parseInt(parentCountMatch[1]) >= 2) {
                    e.preventDefault();
                    studentStatus.innerHTML += '<div class="alert alert-danger mt-2">Cannot proceed. This student already has 2 parents.</div>';
                    studentIdSection.scrollIntoView({
                        behavior: 'smooth'
                    });
                    return;
                }
            }
        });
    </script>
</body>

</html>