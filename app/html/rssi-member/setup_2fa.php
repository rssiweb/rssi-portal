<?php
require_once __DIR__ . "/../../bootstrap.php";
require __DIR__ . '/../vendor/autoload.php';
include("../../util/login_util.php");

use OTPHP\TOTP;

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

//validation(); // Your custom validation

$user = $_SESSION['aid']; // logged in user email or ID

// Fetch user secret from DB
$query = "SELECT twofa_secret, twofa_enabled FROM rssimyaccount_members WHERE email=$1";
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

        /* body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
            color: var(--dark);
        } */

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

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 30px 0;
            color: var(--gray);
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }

        .divider::before {
            margin-right: 10px;
        }

        .divider::after {
            margin-left: 10px;
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

        .btn-loading .btn-text {
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
        }

        @keyframes button-loading-spinner {
            from {
                transform: rotate(0turn);
            }

            to {
                transform: rotate(1turn);
            }
        }
    </style>
</head>

<body>

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
                                                                <h5>Verify Setup</h5>
                                                                <p class="mb-0">Enter the 6-digit code from the app to complete setup.</p>
                                                            </div>
                                                        </div>

                                                        <div class="qr-container">
                                                            <h5>Scan QR Code</h5>
                                                            <canvas id="qrcode" class="mb-3"></canvas>
                                                            <p class="small text-muted text-center">Open your authenticator app and scan this code</p>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <h5>Manual Setup Option</h5>
                                                            <p class="text-muted">If you can't scan the QR code, enter this secret key manually:</p>
                                                            <div class="secret-code">
                                                                <?php echo chunk_split($secret, 4, ' '); ?>
                                                            </div>
                                                        </div>

                                                        <div class="divider">
                                                            <h3 class="section-title">Verify Setup</h3>
                                                        </div>

                                                        <form id="verifyForm">
                                                            <div class="mb-3">
                                                                <label for="otp" class="form-label">Enter Verification Code</label>
                                                                <input type="text" class="form-control" id="otp" maxlength="6" required placeholder="000000" autocomplete="off">
                                                                <div class="form-text">Enter the 6-digit code from your authenticator app</div>
                                                            </div>

                                                            <button type="button" class="btn btn-primary verify-btn" onclick="verifyOtp()">
                                                                <span class="btn-text">Verify & Enable 2FA</span>
                                                            </button>
                                                        </form>

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
                                <h5>Verification</h5>
                                <p class="mb-0"><strong>After scanning the QR code or entering the setup key</strong>, your account will be added to the authenticator app and it will start generating 6-digit verification codes.</p>
                            </div>
                        </div>

                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Important:</strong> Enter the 6-digit code currently displayed in the authenticator app to verify your setup.
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
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const qrCodeCanvas = document.getElementById('qrcode');

                // Generate QR code on canvas
                QRCode.toCanvas(qrCodeCanvas, "<?php echo $qrUrl; ?>", {
                    width: 180,
                    height: 180,
                    margin: 1
                }, function(error) {
                    if (error) console.error(error);
                });

                // Format the OTP input
                const otpInput = document.getElementById('otp');
                const verifyBtn = document.querySelector('.verify-btn');

                otpInput.addEventListener('input', function() {
                    // Remove any non-digit characters
                    this.value = this.value.replace(/\D/g, '');

                    // Only allow up to 6 digits
                    if (this.value.length > 6) {
                        this.value = this.value.slice(0, 6);
                    }

                    // Auto-verify when 6 digits are entered
                    if (this.value.length === 6 && !verifyBtn.disabled) {
                        verifyOtp();
                    }
                });

                function verifyOtp() {
                    const otp = otpInput.value.trim();
                    if (otp.length !== 6) {
                        alert('Please enter a valid 6-digit code.');
                        return;
                    }

                    // Disable button and show loading state
                    verifyBtn.disabled = true;
                    verifyBtn.classList.add('btn-loading');

                    fetch('verify_2fa.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                otp
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            // Re-enable button
                            verifyBtn.disabled = false;
                            verifyBtn.classList.remove('btn-loading');

                            if (data.success) {
                                alert('OTP verified successfully! 2FA is now enabled.');
                                window.location.href = 'home.php';
                            } else {
                                alert(data.message || 'Invalid OTP. Please try again.');
                                otpInput.value = '';
                                otpInput.focus();
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            verifyBtn.disabled = false;
                            verifyBtn.classList.remove('btn-loading');
                            alert('An error occurred. Please try again.');
                            otpInput.value = '';
                            otpInput.focus();
                        });
                }
            });
        </script>
    </body>

</html>