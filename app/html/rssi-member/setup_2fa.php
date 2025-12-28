<?php
require_once __DIR__ . "/../../bootstrap.php";
require __DIR__ . '/../vendor/autoload.php';
include("../../util/login_util.php");
include("../../util/email.php");

use OTPHP\TOTP;

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

$user = $_SESSION['aid']; // logged in user email or ID

// Fetch user secret from DB
$query = "SELECT twofa_secret, twofa_enabled, email FROM rssimyaccount_members WHERE email=$1";
$stmt = pg_prepare($con, "get_user_2fa", $query);
$res = pg_execute($con, "get_user_2fa", [$user]);
$row = pg_fetch_assoc($res);

// If no secret exists, generate one
if (!$row['twofa_secret']) {
    $totp = TOTP::create();
    $secret = $totp->getSecret();

    // Store in DB
    $update = "UPDATE rssimyaccount_members SET twofa_secret=$1 WHERE email=$2";
    $stmt2 = pg_prepare($con, "update_secret", $update);
    pg_execute($con, "update_secret", [$secret, $user]);
} else {
    $secret = $row['twofa_secret'];
    $totp = TOTP::create($secret);
}

// Set issuer and label
$totp->setLabel($user);
$totp->setIssuer('RSSI My Account');

// Generate provisioning URI (used for QR code)
$qrUrl = $totp->getProvisioningUri();

// Don't generate email code on page load - only when user initiates verification
$needsNewEmailCode = false;

// Check if we have a pending verification session
$verificationStarted = isset($_SESSION['email_verification_started']) &&
    time() - $_SESSION['email_verification_started'] < 120;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Two-Factor Authentication | RSSI My Account</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #6c63ff;
            --success: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --border-radius: 16px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        .auth-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        .auth-header {
            background: var(--primary);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .auth-body {
            padding: 40px;
        }

        .auth-icon {
            font-size: 3.5rem;
            margin-bottom: 15px;
            color: white;
            background: rgba(255, 255, 255, 0.2);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .section-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }

        .qr-container {
            background: var(--light);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 25px;
            border: 1px dashed var(--primary);
            position: relative;
            overflow: hidden;
        }

        .qr-mask {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: var(--transition);
        }

        .qr-mask.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .reveal-btn {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .reveal-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        .secret-container {
            position: relative;
            width: 100%;
        }

        .secret-code {
            background: var(--light);
            padding: 15px;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            letter-spacing: 2px;
            text-align: center;
            margin: 20px 0;
            border: 1px solid #e9ecef;
            color: var(--dark);
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .secret-mask {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: var(--transition);
        }

        .secret-mask.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .form-control {
            border-radius: 10px;
            padding: 15px 20px;
            border: 2px solid #e1e5eb;
            transition: var(--transition);
            font-size: 18px;
            letter-spacing: 8px;
            text-align: center;
            font-weight: 600;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            border-radius: 10px;
            padding: 15px 25px;
            font-weight: 600;
            transition: var(--transition);
            width: 100%;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.4);
        }

        .help-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
        }

        .help-link:hover {
            color: var(--secondary);
            text-decoration: underline;
        }

        .step-container {
            display: flex;
            margin-bottom: 20px;
            align-items: flex-start;
        }

        .step-number {
            background: var(--primary);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .step-content {
            flex: 1;
        }

        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
        }

        .modal-header {
            background: var(--primary);
            color: white;
            border-top-left-radius: var(--border-radius);
            border-top-right-radius: var(--border-radius);
        }

        @media (max-width: 768px) {
            .auth-body {
                padding: 25px;
            }

            .form-control {
                font-size: 16px;
                padding: 12px 15px;
            }

            .section-title {
                font-size: 1.5rem;
            }
        }

        .verify-btn {
            position: relative;
            overflow: hidden;
        }

        /* .btn-loading .btn-text {
            visibility: hidden;
        }

        .btn-loading::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
            border: 3px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: button-loading-spinner 1s ease infinite;
        } */

        @keyframes button-loading-spinner {
            from {
                transform: rotate(0turn);
            }

            to {
                transform: rotate(1turn);
            }
        }

        .alert-highlight {
            border-left: 4px solid var(--primary);
            background-color: rgba(67, 97, 238, 0.05);
        }

        .verification-step {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }

        .verification-step h5 {
            color: var(--primary);
            margin-bottom: 15px;
        }

        .code-input-group {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .code-input {
            flex: 1;
        }

        .email-note {
            background: #e8f4fd;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            border-left: 4px solid #4cc9f0;
        }

        .verification-modal .modal-dialog {
            max-width: 600px;
        }

        .verification-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }

        .verification-steps:before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }

        .step-item {
            text-align: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }

        .step-indicator {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dee2e6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
        }

        .step-item.active .step-indicator {
            background: var(--primary);
        }

        .step-item.completed .step-indicator {
            background: var(--success);
        }

        .step-title {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .verification-content {
            min-height: 300px;
        }

        .timer {
            font-size: 0.9rem;
            color: #dc3545;
            font-weight: 600;
            margin-top: 10px;
        }

        .custom-confirm-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1060;
        }

        .custom-confirm-dialog {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            max-width: 400px;
            width: 90%;
            box-shadow: var(--box-shadow);
        }

        .custom-confirm-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            justify-content: flex-end;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Authentication Setup</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active">Enable 2FA</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container">
                                <div class="auth-container">
                                    <div class="auth-header">
                                        <div class="auth-icon">
                                            <i class="fas fa-shield-alt"></i>
                                        </div>
                                        <h2>Two-Factor Authentication</h2>
                                        <p class="mb-0">Add an extra layer of security to your account</p>
                                    </div>

                                    <div class="auth-body">
                                        <div class="row">
                                            <?php if ($row['twofa_enabled'] === 'f') { ?>
                                                <div class="col-md-6">
                                                    <h3 class="section-title">Setup Instructions</h3>

                                                    <div class="step-container">
                                                        <div class="step-number">1</div>
                                                        <div class="step-content">
                                                            <h5>Download Authenticator App</h5>
                                                            <p class="mb-0">Install Google Authenticator or Microsoft Authenticator on your phone.</p>
                                                        </div>
                                                    </div>

                                                    <div class="step-container">
                                                        <div class="step-number">2</div>
                                                        <div class="step-content">
                                                            <h5>Scan QR Code</h5>
                                                            <p class="mb-0">Open the app and scan the QR code with your camera.</p>
                                                        </div>
                                                    </div>

                                                    <div class="step-container">
                                                        <div class="step-number">3</div>
                                                        <div class="step-content">
                                                            <h5>Complete Verification</h5>
                                                            <p class="mb-0">Click the "Start Verification" button to complete the setup process.</p>
                                                        </div>
                                                    </div>

                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        <strong>Important:</strong> Scanning the QR code is only the first step. You must complete the verification process to enable 2FA.
                                                    </div>

                                                    <div class="qr-container">
                                                        <h5>Scan QR Code</h5>
                                                        <canvas id="qrcode" class="mb-3"></canvas>
                                                        <div class="qr-mask" id="qrMask">
                                                            <button type="button" class="reveal-btn" id="revealQrBtn">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </div>
                                                        <p class="small text-muted text-center">Open your authenticator app and scan this code</p>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="mb-4">
                                                        <h5>Manual Setup Option</h5>
                                                        <p class="text-muted">If you can't scan the QR code, enter this secret key manually:</p>
                                                        <div class="secret-container">
                                                            <div class="secret-code" id="secretCode">
                                                                <?php echo chunk_split($secret, 4, ' '); ?>
                                                            </div>
                                                            <div class="secret-mask" id="secretMask">
                                                                <button type="button" class="reveal-btn" id="revealSecretBtn">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <h3 class="section-title">Complete Setup</h3>
                                                    <p class="text-muted mb-4">After scanning the QR code or entering the secret key manually, click the button below to start the verification process.</p>

                                                    <button type="button" class="btn btn-primary btn-lg w-100" id="startVerificationBtn">
                                                        <i class="fas fa-shield-alt me-2"></i>Start Verification
                                                    </button>

                                                    <div class="text-center mt-4">
                                                        <a href="#" data-bs-toggle="modal" data-bs-target="#helpModal" class="help-link">
                                                            <i class="fas fa-question-circle me-2"></i>Need help setting up?
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php } else { ?>
                                                <div class="alert alert-success mt-4">
                                                    Two-Factor Authentication is already enabled on your account.
                                                </div>
                                            <?php } ?>
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

    <!-- Verification Modal -->
    <div class="modal fade verification-modal" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verificationModalLabel">Complete Two-Factor Authentication Setup</h5>
                    <button type="button" class="btn-close" id="modalCloseBtn" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="verification-steps">
                        <div class="step-item active" id="step1">
                            <div class="step-indicator">1</div>
                            <div class="step-title">Email Verification</div>
                        </div>
                        <div class="step-item" id="step2">
                            <div class="step-indicator">2</div>
                            <div class="step-title">Authenticator App</div>
                        </div>
                        <div class="step-item" id="step3">
                            <div class="step-indicator">3</div>
                            <div class="step-title">Complete</div>
                        </div>
                    </div>

                    <div class="verification-content">
                        <!-- Step 1: Email Verification -->
                        <div id="emailVerificationStep">
                            <h5>Verify Your Email Address</h5>
                            <p>We've sent a 6-digit verification code to your registered email address with RSSI.</p>

                            <div class="code-input-group">
                                <input type="text" class="form-control code-input" id="email_otp" maxlength="6" placeholder="000000" autocomplete="off">
                            </div>

                            <div class="email-note">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>Can't find the email? Check your spam folder or <a href="#" onclick="resendEmailCode(); return false;">click here to resend</a>.</small>
                            </div>

                            <button type="button" class="btn btn-primary mt-3 w-100" onclick="verifyEmailCode()">
                                Verify Email Code
                            </button>
                        </div>

                        <!-- Step 2: Authenticator App Verification -->
                        <div id="authVerificationStep" style="display: none;">
                            <h5>Verify Authenticator App</h5>
                            <p>Enter the 6-digit code from your authenticator app.</p>
                            <p class="timer" id="authTimer">Time remaining: <span id="timeRemaining">02:00</span></p>

                            <div class="code-input-group">
                                <input type="text" class="form-control code-input" id="otp" maxlength="6" placeholder="000000" autocomplete="off">
                            </div>

                            <button type="button" class="btn btn-primary mt-3 w-100" onclick="verifyAuthCode()">
                                Verify Authenticator Code
                            </button>
                        </div>

                        <!-- Step 3: Completion -->
                        <div id="completionStep" style="display: none;">
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                                </div>
                                <h4 class="text-success">Two-Factor Authentication Enabled!</h4>
                                <p>Your account is now protected with two-factor authentication.</p>
                                <button type="button" class="btn btn-primary mt-3" data-bs-dismiss="modal">
                                    Continue to Dashboard
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div class="custom-confirm-modal" id="customConfirmModal" style="display: none;">
        <div class="custom-confirm-dialog">
            <h5>Exit 2FA Setup?</h5>
            <p>Do you want to exit 2FA setup? Exiting now will require you to restart the verification process.</p>
            <div class="custom-confirm-buttons">
                <button type="button" class="btn btn-secondary" id="confirmCancelBtn">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmOkBtn">OK</button>
            </div>
        </div>
    </div>

    <!-- Help Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="helpModalLabel"><i class="fas fa-mobile-alt me-2"></i>Authenticator App Setup Guide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="step-container">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h5>Download the App</h5>
                            <p class="mb-0">Install Google Authenticator from the App Store (iOS) or Google Play Store (Android).</p>
                        </div>
                    </div>

                    <div class="step-container">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h5>Open the App</h5>
                            <p class="mb-0">Launch Google Authenticator on your device.</p>
                        </div>
                    </div>

                    <div class="step-container">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h5>Add a New Account</h5>
                            <p class="mb-0">Tap the "+" button and select "Scan a QR code" or "Enter a setup key".</p>
                        </div>
                    </div>

                    <div class="step-container">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h5>Scan the QR Code</h5>
                            <p class="mb-0">If you selected "Scan a QR code", point your camera at the QR code shown on this page.</p>
                        </div>
                    </div>

                    <div class="step-container">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <h5>Enter Setup Key (Alternative)</h5>
                            <p class="mb-0">If you selected "Enter a setup key", type in the provided secret key and select "Time-based".</p>
                        </div>
                    </div>

                    <div class="step-container bg-light bg-opacity-50 rounded p-3 mt-3">
                        <div class="step-number">6</div>
                        <div class="step-content">
                            <h5>Complete Verification</h5>
                            <p class="mb-0"><strong>After scanning the QR code or entering the setup key</strong>, click "Start Verification" to complete the setup process. You will need to verify both your email and the authenticator app.</p>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Important:</strong> You need to complete the verification process to enable two-factor authentication.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it!</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  <script src="../assets_new/js/text-refiner.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const qrCodeCanvas = document.getElementById('qrcode');
            QRCode.toCanvas(qrCodeCanvas, "<?php echo $qrUrl; ?>", {
                width: 180,
                height: 180,
                margin: 1
            });

            // Format OTP inputs to only allow numbers
            const otpInputs = document.querySelectorAll('.code-input');
            otpInputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '').slice(0, 6);
                });
            });

            // Show verification modal if email was already verified but auth not completed
            <?php if ($verificationStarted) : ?>
                var verificationModal = new bootstrap.Modal(document.getElementById('verificationModal'));
                verificationModal.show();
                showAuthVerificationStep();
                startAuthTimer();
            <?php endif; ?>

            // Setup QR code and secret reveal functionality
            const qrMask = document.getElementById('qrMask');
            const secretMask = document.getElementById('secretMask');
            const revealQrBtn = document.getElementById('revealQrBtn');
            const revealSecretBtn = document.getElementById('revealSecretBtn');
            let qrHideTimeout, secretHideTimeout;

            revealQrBtn.addEventListener('click', function() {
                qrMask.classList.add('hidden');

                // Set timeout to re-mask after 10 seconds
                clearTimeout(qrHideTimeout);
                qrHideTimeout = setTimeout(() => {
                    qrMask.classList.remove('hidden');
                }, 10000);
            });

            revealSecretBtn.addEventListener('click', function() {
                secretMask.classList.add('hidden');

                // Set timeout to re-mask after 10 seconds
                clearTimeout(secretHideTimeout);
                secretHideTimeout = setTimeout(() => {
                    secretMask.classList.remove('hidden');
                }, 10000);
            });

            // Manual hide when clicking again
            document.getElementById('qrcode').addEventListener('click', function() {
                if (qrMask.classList.contains('hidden')) {
                    qrMask.classList.remove('hidden');
                    clearTimeout(qrHideTimeout);
                }
            });

            document.getElementById('secretCode').addEventListener('click', function() {
                if (secretMask.classList.contains('hidden')) {
                    secretMask.classList.remove('hidden');
                    clearTimeout(secretHideTimeout);
                }
            });

            // Start verification button handler
            document.getElementById('startVerificationBtn').addEventListener('click', function() {
                const btn = this;

                // Show loading state
                //btn.classList.add('btn-loading');
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Initiating Verification...';
                btn.disabled = true;

                // Call backend to generate and send verification code
                fetch('start_2fa_verification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show verification modal
                            var verificationModal = new bootstrap.Modal(document.getElementById('verificationModal'));
                            verificationModal.show();
                        } else {
                            alert('Failed to start verification: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while starting verification.');
                    })
                    .finally(() => {
                        // Reset button state
                        //btn.classList.remove('btn-loading');
                        btn.innerHTML = '<i class="fas fa-shield-alt me-2"></i>Start Verification';
                        btn.disabled = false;
                    });
            });

            // Custom modal close confirmation
            const modalCloseBtn = document.getElementById('modalCloseBtn');
            const customConfirmModal = document.getElementById('customConfirmModal');
            const confirmOkBtn = document.getElementById('confirmOkBtn');
            const confirmCancelBtn = document.getElementById('confirmCancelBtn');

            modalCloseBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Show custom confirmation modal
                customConfirmModal.style.display = 'flex';
            });

            confirmOkBtn.addEventListener('click', function() {
                // Store original button text
                const originalText = this.innerHTML;

                // Show loading state
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Exiting setup...';
                this.disabled = true;

                // Also disable the cancel button to prevent multiple clicks
                confirmCancelBtn.disabled = true;

                // User confirmed exit - reset session and reload page
                fetch('reset_2fa_session.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Failed to reset session. Please try again.');
                            // Restore button state
                            this.innerHTML = originalText;
                            this.disabled = false;
                            confirmCancelBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                        // Restore button state
                        this.innerHTML = originalText;
                        this.disabled = false;
                        confirmCancelBtn.disabled = false;
                    });
            });

            confirmCancelBtn.addEventListener('click', function() {
                // User canceled exit - hide confirmation, keep verification modal open
                customConfirmModal.style.display = 'none';
            });
        });

        let authTimerInterval;
        let timeLeft = 120; // 2 minutes in seconds

        function startAuthTimer() {
            clearInterval(authTimerInterval);
            timeLeft = 120;
            updateTimerDisplay();

            authTimerInterval = setInterval(function() {
                timeLeft--;
                updateTimerDisplay();

                if (timeLeft <= 0) {
                    clearInterval(authTimerInterval);
                    // Time's up, reset the process
                    alert('Time has expired. Please restart the verification process.');
                    window.location.reload();
                }
            }, 1000);
        }

        function updateTimerDisplay() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timeRemaining').textContent =
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }

        function showAuthVerificationStep() {
            document.getElementById('emailVerificationStep').style.display = 'none';
            document.getElementById('authVerificationStep').style.display = 'block';
            document.getElementById('completionStep').style.display = 'none';

            document.getElementById('step1').classList.remove('active');
            document.getElementById('step2').classList.add('active');
            document.getElementById('step3').classList.remove('active');
        }

        function showCompletionStep() {
            document.getElementById('emailVerificationStep').style.display = 'none';
            document.getElementById('authVerificationStep').style.display = 'none';
            document.getElementById('completionStep').style.display = 'block';

            document.getElementById('step1').classList.remove('active');
            document.getElementById('step2').classList.remove('active');
            document.getElementById('step3').classList.add('active');
            document.getElementById('step3').classList.add('completed');

            clearInterval(authTimerInterval);
        }

        function resendEmailCode() {
            const resendLink = document.querySelector('a[onclick*="resendEmailCode"]');
            const originalText = resendLink.innerHTML;
            // Show sending state
            resendLink.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
            resendLink.onclick = null; // Prevent multiple clicks
            fetch('resend_2fa_email.php')
                .then(response => response.json())
                .then(data => {
                    // Restore original state
                    resendLink.innerHTML = originalText;
                    resendLink.onclick = function() {
                        resendEmailCode();
                        return false;
                    };
                    if (data.success) {
                        alert('A new verification code has been sent to your email.');
                    } else {
                        alert('Failed to resend verification code. Please try again.');
                    }
                })
                .catch(error => {
                    // Restore original state on error
                    resendLink.innerHTML = originalText;
                    resendLink.onclick = function() {
                        resendEmailCode();
                        return false;
                    };
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        }

        function verifyEmailCode() {
            const emailOtpInput = document.getElementById('email_otp');
            const emailOtp = emailOtpInput.value.trim();
            const verifyBtn = document.querySelector('#emailVerificationStep button');

            if (emailOtp.length !== 6) {
                alert('Please enter a valid 6-digit code from your email.');
                emailOtpInput.focus();
                return;
            }

            // Disable button and show spinner
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';

            fetch('verify_2fa_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email_otp: emailOtp
                    })
                })
                .then(res => res.json())
                .then(data => {
                    // Re-enable button regardless of outcome
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = 'Verify Email Code';
                    if (data.success) {
                        // Email verification successful, proceed to auth verification
                        showAuthVerificationStep();
                        startAuthTimer();
                    } else {
                        alert(data.message || 'Invalid verification code. Please try again.');
                        emailOtpInput.value = '';
                        emailOtpInput.focus();
                    }
                })
                .catch(err => {
                    // Re-enable button on error
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = 'Verify Email Code';
                    console.error(err);
                    alert('An error occurred. Please try again.');
                    emailOtpInput.value = '';
                    emailOtpInput.focus();
                });
        }

        function verifyAuthCode() {
            const otpInput = document.getElementById('otp');
            const otp = otpInput.value.trim();
            // Get the verify button
            const verifyBtn = document.querySelector('#authVerificationStep button');

            if (otp.length !== 6) {
                alert('Please enter a valid 6-digit code from your authenticator app.');
                otpInput.focus();
                return;
            }
            // Disable button and show spinner
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...';
            fetch('verify_2fa.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        otp: otp
                    })
                })
                .then(res => res.json())
                .then(data => {
                    // Re-enable button regardless of outcome
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = 'Verify Authenticator Code';
                    if (data.success) {
                        // Both verifications successful
                        showCompletionStep();
                        // Show success alert
                        alert('Two-Factor Authentication has been successfully enabled!');
                        // Reload the page - PHP will check twofa_enabled status
                        window.location.reload();
                    } else {
                        alert(data.message || 'Invalid verification code. Please try again.');
                        otpInput.value = '';
                        otpInput.focus();
                    }
                })
                .catch(err => {
                    console.error(err);
                    // Re-enable button on error
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = 'Verify Authenticator Code';
                    alert('An error occurred. Please try again.');
                    otpInput.value = '';
                    otpInput.focus();
                });
        }
    </script>
</body>

</html>