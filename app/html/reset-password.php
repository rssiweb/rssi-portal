<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");
include(__DIR__ . "../../util/email.php");

// Function to validate password
function validatePassword($password)
{
    $errors = [];
    if (strlen($password) < 8 || strlen($password) > 15) {
        $errors[] = "✘ Password should be between 8 and 15 characters.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "✘ Password should contain at least one uppercase letter.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "✘ Password should contain at least one number.";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "✘ Password should contain at least one special character.";
    }
    return $errors;
}

// Check if the "c" code is present in the URL
if (!isset($_GET['c'])) {
    die("<script>alert('Invalid URL.'); window.location.href = 'select-account.php';</script>");
}

$reset_code = $_GET['c'];

// Define the tables and their respective name columns and redirect URLs
$tables = [
    'rssimyaccount_members' => ['associate_id' => 'associatenumber', 'name_column' => 'fullname', 'redirect' => 'rssi-member/index.php'],
    'signup' => ['associate_id' => 'application_number', 'name_column' => 'applicant_name', 'redirect' => 'tap/index.php'],
    'test_users' => ['associate_id' => 'id', 'name_column' => 'name', 'redirect' => 'iexplore/home.php']
];

$found = false;
$table_name = '';
$name_column = '';
$redirect_url = '';

// Loop through each table to find the reset code
foreach ($tables as $table => $details) {
    $query = "SELECT {$details['associate_id']} as associate_id, email, reset_auth_code_timestamp, {$details['name_column']} as name FROM $table WHERE reset_auth_code = $1";
    $stmt = pg_prepare($con, "fetch_reset_code_$table", $query);
    $result = pg_execute($con, "fetch_reset_code_$table", array($reset_code));

    if (pg_num_rows($result) > 0) {
        $found = true;
        $table_name = $table;
        $name_column = $details['name_column'];
        $redirect_url = $details['redirect'];
        $row = pg_fetch_assoc($result);
        $email = $row['email'];
        $associate_id = $row['associate_id']; // Fetch the actual associate ID value
        $name = $row['name']; // Fetch the actual name value
        $reset_timestamp = strtotime($row['reset_auth_code_timestamp']);
        break;
    }
}

if (!$found) {
    die("<script>alert('Invalid URL.'); window.location.href = 'select-account.php';</script>");
}

$current_timestamp = time();
$remaining_time = 600 - ($current_timestamp - $reset_timestamp); // 600 seconds = 10 minutes

if ($remaining_time <= 0) {
    die("<script>alert('This link has expired.'); window.location.href = '$redirect_url';</script>");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newpass = $_POST['newpass'];
    $confirmpass = $_POST['confirmpass'];

    // Validate passwords
    if ($newpass !== $confirmpass) {
        $password_match_error = "Passwords do not match.";
    } else {
        $errors = validatePassword($newpass);
        if (count($errors) > 0) {
            $password_errors = implode("<br>", $errors);
        } else {
            // Fetch the current password hash from the database
            $query = "SELECT password FROM $table_name WHERE email = $1";
            $stmt = pg_prepare($con, "fetch_current_password", $query);
            $result = pg_execute($con, "fetch_current_password", array($email));

            if (pg_num_rows($result) > 0) {
                $row = pg_fetch_assoc($result);
                $current_password_hash = $row['password'];

                // Check if the new password is the same as the current password
                if (password_verify($newpass, $current_password_hash)) {
                    echo "<script>
                        alert('New password cannot be the same as the current password. Please choose a different password.');
                        window.history.back(); // Go back to the previous page
                    </script>";
                    exit;
                }
            }

            // Hash the new password
            $newpass_hash = password_hash($newpass, PASSWORD_DEFAULT);

            // Update the password and related fields in the database
            $update_query = "UPDATE $table_name SET password = $1, password_updated_by = $2, password_updated_on = NOW(), reset_auth_code = NULL, reset_auth_code_timestamp = NULL WHERE email = $3";
            $update_stmt = pg_prepare($con, "update_password", $update_query);
            $update_result = pg_execute($con, "update_password", array($newpass_hash, $associate_id, $email));

            if ($update_result) {
                // Fetch user location using Geolocation API
                $location = 'Location not available'; // Default value
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

                $ip = getUserIpAddr();

                // Use an IP geolocation API to fetch location
                $location_data = @file_get_contents("http://ip-api.com/json/{$ip}");
                if ($location_data) {
                    $location_data = json_decode($location_data, true);
                    if ($location_data['status'] === 'success') {
                        $location = $location_data['city'] . ', ' . $location_data['country'];
                    }
                }

                // Get the user agent string
                $userAgent = $_SERVER['HTTP_USER_AGENT'];

                // Function to extract browser information
                function getBrowser($userAgent)
                {
                    $browser = "Unknown Browser";
                    $browserVersion = "";

                    // Check for Chrome
                    if (preg_match('/Chrome\/([\d\.]+)/i', $userAgent, $matches)) {
                        $browser = "Chrome";
                        $browserVersion = $matches[1];
                    }
                    // Check for Firefox
                    elseif (preg_match('/Firefox\/([\d\.]+)/i', $userAgent, $matches)) {
                        $browser = "Firefox";
                        $browserVersion = $matches[1];
                    }
                    // Check for Safari
                    elseif (preg_match('/Safari\/([\d\.]+)/i', $userAgent, $matches)) {
                        $browser = "Safari";
                        $browserVersion = $matches[1];
                    }
                    // Check for Edge
                    elseif (preg_match('/Edg\/([\d\.]+)/i', $userAgent, $matches)) {
                        $browser = "Edge";
                        $browserVersion = $matches[1];
                    }
                    // Check for Internet Explorer
                    elseif (preg_match('/MSIE ([\d\.]+)/i', $userAgent, $matches)) {
                        $browser = "Internet Explorer";
                        $browserVersion = $matches[1];
                    }

                    return $browser . " " . $browserVersion;
                }

                // Function to extract operating system information
                function getOperatingSystem($userAgent)
                {
                    $os = "Unknown OS";
                    $osVersion = "";

                    // Check for Android
                    if (preg_match('/Android ([\d\.]+)/i', $userAgent, $matches)) {
                        $os = "Android";
                        $osVersion = $matches[1];
                    }
                    // Check for iOS
                    elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
                        $os = "iOS";
                        if (preg_match('/OS ([\d_]+)/i', $userAgent, $matches)) {
                            $osVersion = str_replace('_', '.', $matches[1]);
                        }
                    }
                    // Check for Windows
                    elseif (preg_match('/Windows NT ([\d\.]+)/i', $userAgent, $matches)) {
                        $os = "Windows";
                        $osVersion = $matches[1];
                    }
                    // Check for macOS
                    elseif (preg_match('/Macintosh/i', $userAgent)) {
                        $os = "macOS";
                        if (preg_match('/Mac OS X ([\d_]+)/i', $userAgent, $matches)) {
                            $osVersion = str_replace('_', '.', $matches[1]);
                        }
                    }
                    // Check for Linux
                    elseif (preg_match('/Linux/i', $userAgent)) {
                        $os = "Linux";
                    }

                    return $os . " " . $osVersion;
                }

                // Get browser and OS information
                $browser = getBrowser($userAgent);
                $os = getOperatingSystem($userAgent);

                // Prepare email data
                $email_data = [
                    "name" => $name, // User's name fetched from the database
                    "reset_time" => date("d/m/Y g:i a"), // Current date and time
                    "ip_address" => $ip, // User's IP address
                    "device" => $os . '/' . $browser, // User's device information
                    "location" => $location, // User's location
                ];

                // Send email
                if (!empty($email)) {
                    sendEmail("rest_pass_conf", $email_data, $email, false);
                }
                echo "<script>
                    alert('Password has been updated successfully. Click OK to go to the login page.');
                    window.location.href = '$redirect_url';
                </script>";
                exit;
            } else {
                $error_message = "Failed to update password. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .password-match {
            color: green;
            display: none;
        }

        .password-mismatch {
            color: red;
            display: none;
        }

        .header {
            background-color: #ffffff;
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header .organisation-name {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
        }

        .header .expiry-timer {
            font-size: 16px;
            color: #d9534f;
            /* Red color for emphasis */
        }

        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            display: none;
            margin-left: 10px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <!-- Corporate Header -->
    <header class="header">
        <!-- Organisation Name -->
        <div class="organisation-name">
            Rina Shiksha Sahayak Foundation
        </div>

        <!-- Link Expiry Timer -->
        <div class="expiry-timer">
            Link expires in: <span id="expiry-timer"><?php echo floor($remaining_time / 60) . ':' . str_pad($remaining_time % 60, 2, '0', STR_PAD_LEFT); ?></span>
        </div>
    </header>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Reset Password</h3>
                    </div>
                    <div class="card-body">
                        <form id="reset-password-form" method="POST">
                            <div class="mb-3">
                                <label for="newpass" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="newpass" name="newpass" required>
                                <div id="password-errors" class="text-danger"></div>
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" id="show-password">
                                    <label class="form-check-label" for="show-password">Show Password</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="confirmpass" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmpass" name="confirmpass" required>
                                <div id="password-match" class="password-match"><i class="bi bi-check-lg"></i> Passwords match</div>
                                <div id="password-mismatch" class="password-mismatch">✘ Passwords do not match</div>
                            </div>
                            <button type="submit" class="btn btn-primary" id="reset-button" disabled>
                                Reset Password
                                <div class="loader" id="loader"></div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Minimalistic Footer position: fixed; bottom: 0; left: 0; -->
    <footer style="width: 100%; background-color: #f8f9fa; padding: 10px; border-top: 1px solid #e9ecef; font-family: system-ui, -apple-system, sans-serif; font-size: 14px; color: #6c757d; text-align: center;" class="mt-5">
        <div style="max-width: 1200px; margin: 0 auto;">
            <div>
                &copy; <?php echo date("Y"); ?> RSSI. All rights reserved. |
                <a href="https://www.rssi.in/privacy-policy" style="color: #6c757d; text-decoration: none;">Privacy Policy</a> |
                <a href="https://www.rssi.in/terms-of-service" style="color: #6c757d; text-decoration: none;">Terms of Service</a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        const showPasswordCheckbox = document.getElementById('show-password');
        const newpassInput = document.getElementById('newpass');

        showPasswordCheckbox.addEventListener('change', function() {
            newpassInput.type = this.checked ? 'text' : 'password';
        });

        // Function to validate password (client-side)
        function validatePassword(password) {
            const errors = [];
            if (password.length < 8 || password.length > 15) {
                errors.push("✘ Password should be between 8 and 15 characters.");
            }
            if (!/[A-Z]/.test(password)) {
                errors.push("✘ Password should contain at least one uppercase letter.");
            }
            if (!/[0-9]/.test(password)) {
                errors.push("✘ Password should contain at least one number.");
            }
            if (!/[^A-Za-z0-9]/.test(password)) {
                errors.push("✘ Password should contain at least one special character.");
            }
            return errors;
        }

        // Function to check if all validations pass
        function validateForm() {
            const newpass = document.getElementById('newpass').value;
            const confirmpass = document.getElementById('confirmpass').value;
            const errors = validatePassword(newpass);
            const passwordsMatch = newpass === confirmpass;

            // Display validation errors
            document.getElementById('password-errors').innerHTML = errors.join('<br>');

            // Display password match status
            if (newpass && confirmpass) {
                if (passwordsMatch) {
                    document.getElementById('password-match').style.display = 'block';
                    document.getElementById('password-mismatch').style.display = 'none';
                } else {
                    document.getElementById('password-match').style.display = 'none';
                    document.getElementById('password-mismatch').style.display = 'block';
                }
            }

            // Enable/disable submit button based on validation
            const submitButton = document.getElementById('reset-button');
            if (errors.length === 0 && passwordsMatch) {
                submitButton.disabled = false;
            } else {
                submitButton.disabled = true;
            }
        }

        // Attach event listeners to validate form on input
        document.getElementById('newpass').addEventListener('input', validateForm);
        document.getElementById('confirmpass').addEventListener('input', validateForm);

        // Link expiry timer
        const expiryTimer = document.getElementById('expiry-timer');
        let timeLeft = <?php echo $remaining_time; ?>; // Use server-side calculated remaining time

        const timer = setInterval(() => {
            if (timeLeft > 0) {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                expiryTimer.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                timeLeft--;
            } else {
                clearInterval(timer);
                expiryTimer.textContent = 'Expired';
                alert('This link has expired. You will be redirected to the login page.');
                window.location.href = '<?php echo $redirect_url; ?>';
            }
        }, 1000);

        // Show loader on form submission
        document.getElementById('reset-password-form').addEventListener('submit', function(e) {
            const submitButton = document.getElementById('reset-button');
            const loader = document.getElementById('loader');

            // Disable button and show loader
            submitButton.disabled = true;
            loader.style.display = 'inline-block';
        });
    </script>
</body>

</html>