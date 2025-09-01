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

validation(); // Your custom validation

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
<html>
<head>
    <meta charset="utf-8">
    <title>Setup 2FA</title>
    <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
</head>
<body>
    <h2>Scan this QR code with your Authenticator app</h2>
    <canvas id="qrcode"></canvas>
    <p>Or use this code manually: <strong><?php echo $secret; ?></strong></p>

    <h3>Verify OTP</h3>
    <input type="text" id="otp" placeholder="Enter OTP from app">
    <button onclick="verifyOtp()">Verify OTP</button>

    <script>
        const qrCodeCanvas = document.getElementById('qrcode');

        // Generate QR code on canvas
        QRCode.toCanvas(qrCodeCanvas, "<?php echo $qrUrl; ?>", function(error) {
            if (error) console.error(error);
        });

        // OTP verification
        function verifyOtp() {
            const otp = document.getElementById('otp').value.trim();
            if (otp.length === 0) {
                alert('Please enter the OTP.');
                return;
            }

            fetch('verify_2fa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ otp })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('OTP verified successfully! 2FA is now enabled.');
                    window.location.href = 'home.php';
                } else {
                    alert(data.message || 'Invalid OTP. Try again.');
                }
            })
            .catch(err => console.error(err));
        }
    </script>
</body>
</html>
