<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Configuration
define('OTP_EXPIRY_MINUTES', 5);
define('MSG91_AUTH_KEY', '453453AdtJnF86vsre68318b1cP1'); // Replace with your actual Msg91 auth key
define('MSG91_TEMPLATE_ID', 'EntertemplateID'); // Replace with your template ID
define('MSG91_SENDER_ID', 'testid'); // Replace with your sender ID

// Handle OTP generation and sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_otp'])) {
    header('Content-Type: application/json');
    
    $mobile = pg_escape_string($con, $_POST['contact_number']);
    
    // Validate mobile number format
    if (!preg_match('/^[0-9]{10,15}$/', $mobile)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid mobile number format']);
        exit;
    }
    
    // Check if mobile already exists and registration is completed
    $checkQuery = "SELECT 1 FROM quick_registrations 
                  WHERE contact_number = '$mobile' AND registration_completed = TRUE";
    $result = pg_query($con, $checkQuery);
    
    if (pg_num_rows($result) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'This mobile number is already registered']);
        exit;
    }
    
    // Generate 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpGeneratedAt = date('Y-m-d H:i:s');
    
    // Store OTP in database (without requiring other fields)
    $upsertQuery = "INSERT INTO quick_registrations (contact_number, otp, otp_generated_at)
                    VALUES ('$mobile', '$otp', '$otpGeneratedAt')
                    ON CONFLICT (contact_number) 
                    DO UPDATE SET otp = '$otp', otp_generated_at = '$otpGeneratedAt', 
                    registration_completed = FALSE, otp_verified = FALSE";
    
    if (!pg_query($con, $upsertQuery)) {
        error_log("Database error: " . pg_last_error($con));
        echo json_encode(['status' => 'error', 'message' => 'Failed to generate OTP']);
        exit;
    }
    
    // Send OTP via Msg91
    $curl = curl_init();
    $message = "Your verification code is: $otp. Valid for " . OTP_EXPIRY_MINUTES . " minutes.";

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.msg91.com/api/v5/otp",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
            'template_id' => '6831925cd6fc052b7e309652',
            'mobile' => '91' . $mobile, // Assuming Indian numbers with country code 91
            'otp' => $otp
        ]),
        CURLOPT_HTTPHEADER => [
            "authkey: " . '453453AdtJnF86vsre68318b1cP1',
            "content-type: application/json"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        error_log("Msg91 API error: " . $err);
        echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP via SMS']);
    } else {
        $responseData = json_decode($response, true);
        if (isset($responseData['type']) && $responseData['type'] == 'success') {
            echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully']);
        } else {
            error_log("Msg91 API response error: " . $response);
            echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP via SMS']);
        }
    }
    exit;
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    header('Content-Type: application/json');
    
    $mobile = pg_escape_string($con, $_POST['contact_number']);
    $otp = pg_escape_string($con, $_POST['otp']);
    
    // Check if OTP is valid and not expired
    $query = "SELECT otp, otp_generated_at FROM quick_registrations 
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
    $updateQuery = "UPDATE quick_registrations 
                   SET otp_verified = TRUE, updated_at = CURRENT_TIMESTAMP
                   WHERE contact_number = '$mobile'";
    pg_query($con, $updateQuery);
    
    echo json_encode(['status' => 'success', 'message' => 'OTP verified successfully']);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $mobile = pg_escape_string($con, $_POST['contact_number']);
    
    // Verify OTP status in database
    $checkQuery = "SELECT 1 FROM quick_registrations 
                  WHERE contact_number = '$mobile' 
                  AND otp_verified = TRUE
                  AND registration_completed = FALSE";
    $result = pg_query($con, $checkQuery);
    
    if (pg_num_rows($result) == 0) {
        die("<div class='alert alert-danger'>OTP not verified. Please complete OTP verification first.</div>");
    }
    
    // Proceed with registration
    $name = pg_escape_string($con, $_POST['name']);
    $email = pg_escape_string($con, $_POST['email']);
    $dob = pg_escape_string($con, $_POST['date_of_birth']);
    $referral = pg_escape_string($con, $_POST['referral_source']);
    
    $sql = "UPDATE quick_registrations 
           SET name = '$name', 
               email = '$email', 
               date_of_birth = '$dob', 
               referral_source = '$referral',
               registration_completed = TRUE,
               updated_at = CURRENT_TIMESTAMP
           WHERE contact_number = '$mobile'";
    
    if (pg_query($con, $sql)) {
        $success = "Registration successful! Thank you.";
    } else {
        $error = "Error: " . pg_last_error($con);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Quick Registration</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php elseif (isset($error)): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php else: ?>
                            <form id="registrationForm" method="post">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="contact" class="form-label">Contact Number</label>
                                    <div class="input-group">
                                        <input type="tel" class="form-control" id="contact" name="contact_number" required>
                                        <button type="button" class="btn btn-outline-secondary" id="sendOtpBtn">Send OTP</button>
                                    </div>
                                    <small class="text-muted">We'll send a verification code to this number</small>
                                </div>
                                <div class="mb-3" id="otpField" style="display:none;">
                                    <label for="otp" class="form-label">OTP Verification</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="otp" name="otp" placeholder="Enter 6-digit OTP">
                                        <button type="button" class="btn btn-outline-secondary" id="verifyOtpBtn">Verify</button>
                                    </div>
                                    <div id="otpStatus" class="mt-2"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="dob" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="dob" name="date_of_birth" required>
                                </div>
                                <div class="mb-3">
                                    <label for="referral" class="form-label">How did you hear about us?</label>
                                    <select class="form-select" id="referral" name="referral_source" required>
                                        <option value="" selected disabled>Select option</option>
                                        <option value="Google Search">Google Search</option>
                                        <option value="Social Media">Social Media</option>
                                        <option value="Friend/Family">Friend/Family</option>
                                        <option value="Advertisement">Advertisement</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100" name="register" id="registerBtn" disabled>Register</button>
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
            const sendOtpBtn = document.getElementById('sendOtpBtn');
            const verifyOtpBtn = document.getElementById('verifyOtpBtn');
            const contactField = document.getElementById('contact');
            const otpField = document.getElementById('otpField');
            const otpInput = document.getElementById('otp');
            const otpStatus = document.getElementById('otpStatus');
            const registerBtn = document.getElementById('registerBtn');
            const form = document.getElementById('registrationForm');

            // Mobile number validation
            contactField.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // Send OTP
            sendOtpBtn.addEventListener('click', function() {
                const mobile = contactField.value.trim();

                if (!mobile || mobile.length < 10) {
                    alert('Please enter a valid mobile number');
                    return;
                }

                sendOtpBtn.disabled = true;
                sendOtpBtn.textContent = 'Sending...';

                fetch('register.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `generate_otp=1&contact_number=${mobile}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            otpField.style.display = 'block';
                            otpStatus.innerHTML = '<div class="alert alert-success">OTP sent successfully</div>';
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to send OTP');
                    })
                    .finally(() => {
                        sendOtpBtn.disabled = false;
                        sendOtpBtn.textContent = 'Send OTP';
                    });
            });

            // Verify OTP - updated version
            verifyOtpBtn.addEventListener('click', function() {
                const otp = otpInput.value.trim();
                const mobile = contactField.value.trim();

                if (!otp || otp.length !== 6) {
                    otpStatus.innerHTML = '<div class="alert alert-danger">Please enter a valid 6-digit OTP</div>';
                    return;
                }

                verifyOtpBtn.disabled = true;
                verifyOtpBtn.textContent = 'Verifying...';

                fetch('register.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `verify_otp=1&contact_number=${mobile}&otp=${otp}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            otpStatus.innerHTML = '<div class="alert alert-success">OTP verified successfully</div>';
                            registerBtn.disabled = false;
                            verifyOtpBtn.textContent = 'Verified';
                        } else {
                            otpStatus.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                            verifyOtpBtn.disabled = false;
                            verifyOtpBtn.textContent = 'Verify';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        otpStatus.innerHTML = '<div class="alert alert-danger">Error verifying OTP</div>';
                        verifyOtpBtn.disabled = false;
                        verifyOtpBtn.textContent = 'Verify';
                    });
            });
        });
    </script>
</body>

</html>