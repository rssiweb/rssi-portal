<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

use OTPHP\TOTP;

if (!isset($_SESSION['otp_verification_user'])) {
    header("Location: index.php");
    exit;
}

$user = $_SESSION['otp_verification_user'];

// Fetch user's secret from DB
$query = "SELECT twofa_secret FROM rssimyaccount_members WHERE email=$1";
$stmt = pg_prepare($con, "get_user_2fa_secret", $query);
$res = pg_execute($con, "get_user_2fa_secret", [$user]);
$row = pg_fetch_assoc($res);

if (!$row || empty($row['twofa_secret'])) {
    echo "2FA is not set up for your account.";
    exit;
}

$secret = $row['twofa_secret'];
$totp = TOTP::create($secret);

// --- Add logUserLogin function here ---
function logUserLogin($con, $username, $date)
{
    function getUserIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipList[0]);
            return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : $_SERVER['REMOTE_ADDR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    $user_ip = getUserIpAddr();
    pg_query($con, "INSERT INTO userlog_member VALUES (DEFAULT, '$username', '$user_ip', '$date')");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_totp'])) {
    $otpEntered = $_POST['otp_code'];
    if ($totp->verify($otpEntered)) {
        $_SESSION['aid'] = $user;
        unset($_SESSION['otp_verification_user']);
        logUserLogin($con, $user, date('Y-m-d H:i:s'));

        // --- Build redirect URL ---
        $redirect = "home.php"; // default
        if (isset($_SESSION["login_redirect"])) {
            $params = "";
            if (isset($_SESSION["login_redirect_params"])) {
                foreach ($_SESSION["login_redirect_params"] as $key => $value) {
                    $params .= "$key=" . urlencode($value) . "&";
                }
                unset($_SESSION["login_redirect_params"]);
            }
            $redirect = $_SESSION["login_redirect"] . ($params ? "?" . rtrim($params, "&") : "");
            unset($_SESSION["login_redirect"]);
        }

        echo json_encode(["status" => "SUCCESS_2FA", "redirect" => $redirect]);
        exit;
    } else {
        echo json_encode(["status" => "FAIL_2FA"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        @media (max-width: 767px) {
            .logo {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .logo span {
                margin: 5px 0;
            }
        }

        .by-line {
            background-color: #CE1212;
            padding: 1px 5px;
            border-radius: 0px;
            font-size: small !important;
            color: white !important;
            margin-left: 10%;
        }

        .otp-input {
            letter-spacing: 10px;
            font-size: 1.5rem;
            text-align: center;
            font-weight: bold;
        }

        .verify-btn {
            position: relative;
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

        .help-link {
            color: #0d6efd;
            text-decoration: none;
        }

        .help-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <?php include 'banner.php'; ?>
    <main>
        <div class="container">
            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                            <div class="container text-center py-4">
                                <div class="logo">
                                    <img src="../img/phoenix.png" alt="Phoenix Logo" width="40%">
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4">Two-Factor Authentication</h5>
                                        <p class="text-center small">Enter the code from your Authenticator app</p>
                                    </div>

                                    <form id="otpForm" class="row g-3 needs-validation" role="form" method="POST">
                                        <div class="col-12">
                                            <label for="otp_code" class="form-label">Verification Code</label>
                                            <input type="text" name="otp_code" class="form-control otp-input" id="otp_code" maxlength="6" required placeholder="000000" autocomplete="off" autofocus>
                                            <div class="form-text">Open your authenticator app and enter the 6-digit code</div>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-primary w-100 verify-btn" type="submit" id="verifyBtn">
                                                <span class="btn-text">Verify and Continue</span>
                                            </button>
                                        </div>
                                        <div class="col-12 text-center">
                                            <p class="small mb-0">
                                                <a href="#" data-bs-toggle="modal" data-bs-target="#infoModal">
                                                    Why am I seeing this page?
                                                </a>
                                            </p>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="credits">
                                Designed by <a href="https://www.rssi.in/">rssi.in</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Info Modal -->
    <div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="infoModalLabel">About Two-Factor Authentication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Two-factor authentication (2FA) has been enabled for your account.</strong>
                    </div>

                    <p>You're seeing this page because two-factor authentication is required to access your account. This additional security measure helps protect your account from unauthorized access.</p>

                    <h6>What you need to do:</h6>
                    <ol>
                        <li>Open your authenticator app (Google Authenticator, Authy, Microsoft Authenticator, etc.)</li>
                        <li>Find the entry for this website</li>
                        <li>Enter the 6-digit code shown in the app into the verification field</li>
                        <li>Click "Verify and Continue" to access your account</li>
                    </ol>

                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        If you don't have access to your authenticator app, please contact your system administrator for assistance.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Understood</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const otpInput = document.getElementById("otp_code");
        const btn = document.getElementById("verifyBtn");
        const form = document.getElementById("otpForm");

        // Function to handle OTP verification
        function verifyOtp(otp) {
            if (otp.length !== 6) {
                alert("Please enter a 6-digit code.");
                return;
            }

            btn.classList.add("btn-loading");
            btn.disabled = true;

            fetch("<?php echo basename(__FILE__); ?>", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "verify_totp=1&otp_code=" + encodeURIComponent(otp)
                })
                .then(res => res.text())
                .then(response => {
                    try {
                        const data = JSON.parse(response); // expect JSON
                        if (data.status === "SUCCESS_2FA") {
                            window.location.href = data.redirect; // dynamic redirect
                        } else {
                            alert("Invalid code. Please try again.");
                            otpInput.value = ""; // reset field
                            otpInput.focus(); // refocus
                            btn.classList.remove("btn-loading");
                            btn.disabled = false;
                        }
                    } catch (e) {
                        console.error("Unexpected response:", response);
                        alert("Something went wrong. Try again.");
                        btn.classList.remove("btn-loading");
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Something went wrong. Try again.");
                    btn.classList.remove("btn-loading");
                    btn.disabled = false;
                });
        }

        // Handle form submit (manual click)
        form.addEventListener("submit", function(e) {
            e.preventDefault();
            verifyOtp(otpInput.value.trim());
        });

        // Auto-submit when 6 digits entered
        otpInput.addEventListener("input", function() {
            this.value = this.value.replace(/\D/g, ''); // only digits
            if (this.value.length === 6) {
                verifyOtp(this.value.trim());
            }
        });
    </script>
</body>

</html>