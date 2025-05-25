<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Configuration
define('OTP_EXPIRY_MINUTES', 5);

// Check if contact number exists
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_contact'])) {
    header('Content-Type: application/json');

    $mobile = pg_escape_string($con, $_POST['contact_number']);

    // Validate mobile number format 
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid mobile number. Please enter exactly 10 digits without spaces, letters, or special characters.']);
        exit;
    }

    // Check if mobile already exists
    $checkQuery = "SELECT 1 FROM public_health_records 
                  WHERE contact_number = '$mobile' AND registration_completed = TRUE";
    $result = pg_query($con, $checkQuery);

    if (pg_num_rows($result) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'This mobile number is already registered']);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Proceed to verification']);
    }
    exit;
}

// Handle OTP generation and sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    header('Content-Type: application/json');

    $mobile = pg_escape_string($con, $_POST['contact_number']);
    $email = pg_escape_string($con, $_POST['email']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
        exit;
    }

    // Check if email already exists
    // $emailCheck = "SELECT 1 FROM public_health_records WHERE email = '$email'";
    // $emailResult = pg_query($con, $emailCheck);
    // if (pg_num_rows($emailResult) > 0) {
    //     echo json_encode(['status' => 'error', 'message' => 'This email is already registered']);
    //     exit;
    // }

    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpGeneratedAt = date('Y-m-d H:i:s');

    // Store OTP in database
    $upsertQuery = "INSERT INTO public_health_records (contact_number, email, otp, otp_generated_at)
                    VALUES ('$mobile', '$email', '$otp', '$otpGeneratedAt')
                    ON CONFLICT (contact_number) 
                    DO UPDATE SET otp = '$otp', otp_generated_at = '$otpGeneratedAt', 
                    registration_completed = FALSE, otp_verified = FALSE";

    if (!pg_query($con, $upsertQuery)) {
        error_log("Database error: " . pg_last_error($con));
        echo json_encode(['status' => 'error', 'message' => 'Failed to generate OTP']);
        exit;
    }

    // Send OTP via Email
    try {
        sendEmail("otp_verification", [
            "otp" => $otp,
            "valid_minutes" => OTP_EXPIRY_MINUTES
        ], $email);

        echo json_encode(['status' => 'success', 'message' => 'We’ve sent a 6-digit OTP to your email. Please check your inbox.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP email']);
    }
    exit;
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    header('Content-Type: application/json');

    $mobile = pg_escape_string($con, $_POST['contact_number']);
    $otp = pg_escape_string($con, $_POST['otp']);

    // Check if OTP is valid and not expired
    $query = "SELECT otp, otp_generated_at FROM public_health_records 
             WHERE contact_number = '$mobile' 
             AND registration_completed = FALSE";
    $result = pg_query($con, $query);

    if (pg_num_rows($result) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'No OTP found for this number or registration already completed']);
        exit;
    }

    $row = pg_fetch_assoc($result);
    $dbOtp = $row['otp'];
    $generatedAt = strtotime($row['otp_generated_at']);

    // Check OTP expiry
    if (time() - $generatedAt > OTP_EXPIRY_MINUTES * 60) {
        echo json_encode(['status' => 'error', 'message' => 'OTP has expired. Please generate a new one.']);
        exit;
    }

    if ($dbOtp !== $otp) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid OTP']);
        exit;
    }

    // Mark OTP as verified in database
    $updateQuery = "UPDATE public_health_records 
                   SET otp_verified = TRUE, updated_at = CURRENT_TIMESTAMP
                   WHERE contact_number = '$mobile'";
    pg_query($con, $updateQuery);

    echo json_encode(['status' => 'success', 'message' => 'OTP verified successfully']);
    exit;
}

// Handle Aadhar verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_aadhar'])) {
    header('Content-Type: application/json');

    $mobile = pg_escape_string($con, $_POST['contact_number']);
    $aadhar = pg_escape_string($con, $_POST['aadhar_number']);

    // Basic Aadhar validation
    if (!preg_match('/^[0-9]{12}$/', $aadhar)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Aadhar number (must be 12 digits)']);
        exit;
    }

    // Check if Aadhar already exists
    $aadharCheck = "SELECT 1 FROM public_health_records WHERE aadhar_number = '$aadhar'";
    $aadharResult = pg_query($con, $aadharCheck);
    if (pg_num_rows($aadharResult) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'This Aadhar number is already registered']);
        exit;
    }

    // Mark Aadhar as verified in database
    $updateQuery = "UPDATE public_health_records 
                   SET aadhar_verified = TRUE, 
                       aadhar_number = '$aadhar',
                       updated_at = CURRENT_TIMESTAMP
                   WHERE contact_number = '$mobile'";
    pg_query($con, $updateQuery);

    echo json_encode(['status' => 'success', 'message' => 'Aadhar verification completed']);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $mobile = pg_escape_string($con, $_POST['contact_number']);

    // Verify either OTP or Aadhar is completed
    $checkQuery = "SELECT 1 FROM public_health_records 
                  WHERE contact_number = '$mobile' 
                  AND (otp_verified = TRUE OR aadhar_verified = TRUE)
                  AND registration_completed = FALSE";
    $result = pg_query($con, $checkQuery);

    if (pg_num_rows($result) == 0) {
        die("<div class='alert alert-danger'>Verification not completed. Please complete OTP or Aadhar verification first.</div>");
    }

    // Proceed with registration
    $name = pg_escape_string($con, $_POST['name']);
    $email = pg_escape_string($con, $_POST['email'] ?? null);
    $dob = pg_escape_string($con, $_POST['date_of_birth']);
    $referral = pg_escape_string($con, $_POST['referral_source']);
    $aadhar = pg_escape_string($con, $_POST['aadhar_number'] ?? null);

    $sql = "UPDATE public_health_records 
           SET name = '$name', 
               email = '$email', 
               date_of_birth = '$dob', 
               referral_source = '$referral',
               aadhar_number = '$aadhar',
               registration_completed = TRUE,
               updated_at = CURRENT_TIMESTAMP
           WHERE contact_number = '$mobile'";

    if (pg_query($con, $sql)) {
        $_SESSION['registration_success'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Error: " . pg_last_error($con);
    }
}

// Clear success message on page load
if (isset($_SESSION['registration_success'])) {
    $success = "Registration successful! Thank you.";
    unset($_SESSION['registration_success']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .step {
            display: none;
        }

        .step.active {
            display: block;
        }

        .spinner {
            display: none;
        }

        #contactStatus,
        #otpStatus,
        #aadharStatus {
            min-height: 24px;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Quick Registration</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                            <div class="text-center mt-3">
                                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-primary">New Registration</a>
                            </div>
                        <?php elseif (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php else: ?>
                            <form id="registrationForm" method="post">
                                <!-- Step 1: Contact Number -->
                                <div id="step1" class="step active">
                                    <h5 class="mb-3">Enter Your Contact Number</h5>
                                    <div class="mb-3">
                                        <label for="contact_number" class="form-label">Mobile Number*</label>
                                        <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
                                        <small class="text-muted">Primary contact number for messages and updates</small>
                                    </div>
                                    <div id="contactStatus" class="mb-3"></div>
                                    <div class="text-end">
                                        <button type="button" id="checkContactBtn" class="btn btn-primary">
                                            Check Availability
                                            <span class="spinner-border spinner-border-sm spinner" id="contactSpinner"></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Step 2: Verification Method -->
                                <div id="step2" class="step">
                                    <h5 class="mb-3">Select Verification Method</h5>
                                    <div class="mb-4">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="verification_method" id="otpOption" value="otp" checked>
                                            <label class="form-check-label" for="otpOption">
                                                <strong>Verify via OTP</strong> (An OTP will be sent to your email for verification)
                                            </label>
                                        </div>
                                        <div id="emailField" class="mb-3">
                                            <label for="email" class="form-label">Email Address*</label>
                                            <input type="email" class="form-control" id="email" name="email">
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="verification_method" id="aadharOption" value="aadhar">
                                            <label class="form-check-label" for="aadharOption">
                                                <strong>Verify via Aadhaar Number</strong> (if you don't have email)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" id="backToStep1" class="btn btn-outline-secondary me-2">Back</button>
                                        <button type="button" id="proceedToVerification" class="btn btn-primary">Proceed</button>
                                    </div>
                                </div>

                                <!-- Step 3: OTP Verification -->
                                <div id="step3otp" class="step">
                                    <h5 class="mb-3">Verify Your Email</h5>
                                    <div class="mb-3">
                                        <label for="otp" class="form-label">Enter OTP*</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="otp" name="otp" placeholder="6-digit code">
                                            <button type="button" class="btn btn-outline-secondary" id="verifyOtpBtn">
                                                Verify
                                                <span class="spinner-border spinner-border-sm spinner" id="otpSpinner"></span>
                                            </button>
                                        </div>
                                    </div>
                                    <div id="otpStatus" class="mb-3"></div>
                                    <div class="text-end">
                                        <button type="button" id="backToStep2FromOtp" class="btn btn-outline-secondary me-2">Back</button>
                                        <button type="button" id="resendOtpBtn" class="btn btn-outline-primary me-2">Resend OTP</button>
                                    </div>
                                </div>

                                <!-- Step 3: Aadhaar Verification -->
                                <div id="step3aadhar" class="step">
                                    <h5 class="mb-3">Verify Your Aadhaar</h5>
                                    <div class="mb-3">
                                        <label for="aadhar_number" class="form-label">Aadhaar Number*</label>
                                        <input type="text" class="form-control" id="aadhar_number" name="aadhar_number" placeholder="12-digit number">
                                        <small class="text-muted">We'll verify this with UIDAI records</small>
                                    </div>
                                    <div id="aadharStatus" class="mb-3"></div>
                                    <div class="text-end">
                                        <button type="button" id="backToStep2FromAadhar" class="btn btn-outline-secondary me-2">Back</button>
                                        <button type="button" id="verifyAadharBtn" class="btn btn-primary">
                                            Verify Aadhaar
                                            <span class="spinner-border spinner-border-sm spinner" id="aadharSpinner"></span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Step 4: Personal Details -->
                                <div id="step4" class="step">
                                    <h5 class="mb-3">Complete Your Registration</h5>
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name*</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="date_of_birth" class="form-label">Date of Birth*</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="referral_source" class="form-label">How did you hear about us?*</label>
                                        <select class="form-select" id="referral_source" name="referral_source" required>
                                            <option value="" selected disabled>Select option</option>
                                            <option value="Google Search">Google Search</option>
                                            <option value="Social Media">Social Media</option>
                                            <option value="Friend/Family">Friend/Family</option>
                                            <option value="Advertisement">Advertisement</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" id="backToVerification" class="btn btn-outline-secondary me-2">Back</button>
                                        <button type="submit" name="register" class="btn btn-success">Complete Registration</button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form elements
            const contactNumber = document.getElementById('contact_number');
            const emailField = document.getElementById('email');
            const aadharNumber = document.getElementById('aadhar_number');
            const otpOption = document.getElementById('otpOption');
            const aadharOption = document.getElementById('aadharOption');

            // Status displays
            const contactStatus = document.getElementById('contactStatus');
            const otpStatus = document.getElementById('otpStatus');
            const aadharStatus = document.getElementById('aadharStatus');

            // Navigation between steps
            function showStep(stepId) {
                document.querySelectorAll('.step').forEach(step => {
                    step.classList.remove('active');
                });
                document.getElementById(stepId).classList.add('active');
            }

            // Step 1: Check contact number
            document.getElementById('checkContactBtn').addEventListener('click', function() {
                const btn = this;
                const spinner = document.getElementById('contactSpinner');
                const mobile = contactNumber.value.trim();

                if (!mobile || mobile.length < 10) {
                    contactStatus.innerHTML = '<div class="alert alert-danger">Please enter a valid mobile number</div>';
                    return;
                }

                btn.disabled = true;
                spinner.style.display = 'inline-block';

                fetch('register_beneficiary.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `check_contact=1&contact_number=${mobile}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            contactStatus.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                            showStep('step2');
                        } else {
                            contactStatus.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        contactStatus.innerHTML = '<div class="alert alert-danger">Error checking contact number</div>';
                    })
                    .finally(() => {
                        btn.disabled = false;
                        spinner.style.display = 'none';
                    });
            });

            // Step 2: Select verification method
            document.getElementById('proceedToVerification').addEventListener('click', function() {
                const method = document.querySelector('input[name="verification_method"]:checked').value;

                if (method === 'otp') {
                    const email = emailField.value.trim();
                    if (!email || !email.includes('@')) {
                        alert('Please enter a valid email address');
                        return;
                    }
                    showStep('step3otp');
                    sendOtp();
                } else {
                    showStep('step3aadhar');
                }
            });

            // Back buttons
            document.getElementById('backToStep1').addEventListener('click', () => showStep('step1'));
            document.getElementById('backToStep2FromOtp').addEventListener('click', () => showStep('step2'));
            document.getElementById('backToStep2FromAadhar').addEventListener('click', () => showStep('step2'));
            document.getElementById('backToVerification').addEventListener('click', function() {
                const method = document.querySelector('input[name="verification_method"]:checked').value;
                showStep(method === 'otp' ? 'step3otp' : 'step3aadhar');
            });

            // Send OTP function
            function sendOtp() {
                const mobile = contactNumber.value.trim();
                const email = emailField.value.trim();
                const btn = document.getElementById('resendOtpBtn');
                const spinner = document.createElement('span');
                spinner.className = 'spinner-border spinner-border-sm';
                spinner.style.marginLeft = '5px';

                otpStatus.innerHTML = '';
                btn.disabled = true;
                btn.appendChild(spinner);

                fetch('register_beneficiary.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `send_otp=1&contact_number=${mobile}&email=${email}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            otpStatus.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                        } else {
                            otpStatus.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        otpStatus.innerHTML = '<div class="alert alert-danger">Failed to send OTP</div>';
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.removeChild(spinner);
                    });
            }

            // Resend OTP
            document.getElementById('resendOtpBtn').addEventListener('click', sendOtp);

            // Verify OTP
            document.getElementById('verifyOtpBtn').addEventListener('click', function() {
                const btn = this;
                const spinner = document.getElementById('otpSpinner');
                const otp = document.getElementById('otp').value.trim();
                const mobile = contactNumber.value.trim();

                if (!otp || otp.length !== 6) {
                    otpStatus.innerHTML = '<div class="alert alert-danger">Please enter a valid 6-digit OTP</div>';
                    return;
                }

                btn.disabled = true;
                spinner.style.display = 'inline-block';

                fetch('register_beneficiary.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `verify_otp=1&contact_number=${mobile}&otp=${otp}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            otpStatus.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                            showStep('step4');
                        } else {
                            otpStatus.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        otpStatus.innerHTML = '<div class="alert alert-danger">Error verifying OTP</div>';
                    })
                    .finally(() => {
                        btn.disabled = false;
                        spinner.style.display = 'none';
                    });
            });

            // Verify Aadhaar
            document.getElementById('verifyAadharBtn').addEventListener('click', function() {
                const btn = this;
                const spinner = document.getElementById('aadharSpinner');
                const aadhar = aadharNumber.value.trim();
                const mobile = contactNumber.value.trim();

                if (!aadhar || aadhar.length !== 12) {
                    aadharStatus.innerHTML = '<div class="alert alert-danger">Please enter a valid 12-digit Aadhaar number</div>';
                    return;
                }

                btn.disabled = true;
                spinner.style.display = 'inline-block';

                fetch('register_beneficiary.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `verify_aadhar=1&contact_number=${mobile}&aadhar_number=${aadhar}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            aadharStatus.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                            showStep('step4');
                        } else {
                            aadharStatus.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        aadharStatus.innerHTML = '<div class="alert alert-danger">Error verifying Aadhaar</div>';
                    })
                    .finally(() => {
                        btn.disabled = false;
                        spinner.style.display = 'none';
                    });
            });

            // Toggle email field based on verification method
            otpOption.addEventListener('change', function() {
                emailField.required = this.checked;
            });

            aadharOption.addEventListener('change', function() {
                emailField.required = !this.checked;
            });

            // Input validation
            contactNumber.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            aadharNumber.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12);
            });

            document.getElementById('otp').addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            });
        });
    </script>
</body>

</html>