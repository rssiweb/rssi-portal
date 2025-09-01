<?php
require_once __DIR__ . "/../../bootstrap.php";
require __DIR__ . '/../vendor/autoload.php';
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
        // OTP correct: login successful
        $_SESSION['aid'] = $user;
        unset($_SESSION['otp_verification_user']);

        // Log login
        logUserLogin($con, $user, date('Y-m-d H:i:s'));

        header("Location: home.php");
        exit;
    } else {
        $error = "Invalid code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Authenticator Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Two-Factor Authentication</h2>
    <p>Enter the 6-digit code from your Authenticator app.</p>
    <?php if (!empty($error)) { echo "<div class='alert alert-danger'>$error</div>"; } ?>
    <form method="POST">
        <input type="text" name="otp_code" class="form-control mb-2" maxlength="6" placeholder="Enter code" required>
        <button type="submit" name="verify_totp" class="btn btn-primary">Verify</button>
    </form>
</div>
</body>
</html>
