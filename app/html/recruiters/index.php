<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_recruiters.php");
include("../../util/email.php");

// Simple math captcha function
function generateMathCaptcha()
{
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $operators = ['+', '-', '*'];
    $operator = $operators[array_rand($operators)];

    // Calculate answer
    switch ($operator) {
        case '+':
            $answer = $num1 + $num2;
            break;
        case '-':
            $answer = $num1 - $num2;
            break;
        case '*':
            $answer = $num1 * $num2;
            break;
    }

    // Store in session
    $_SESSION['captcha_answer'] = $answer;
    $_SESSION['captcha_question'] = "$num1 $operator $num2";

    return $_SESSION['captcha_question'];
}

$date = date('Y-m-d H:i:s');
$login_failed_dialog = "";
$registration_success = "";
$registration_error = "";

function afterlogin($con, $date)
{
    $username = $_SESSION['rid'];
    $user_query = pg_query($con, "select password_updated_by,password_updated_on,default_pass_updated_on from recruiters WHERE email='$username'");
    $row = pg_fetch_row($user_query);
    $password_updated_by = $row[0];
    $password_updated_on = $row[1];
    $default_pass_updated_on = $row[2];

    passwordCheck($password_updated_by, $password_updated_on, $default_pass_updated_on);

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
    pg_query($con, "INSERT INTO userlog_member VALUES (DEFAULT,'$username','$user_ip','$date')");

    if (isset($_SESSION["login_redirect"])) {
        $params = "";
        if (isset($_SESSION["login_redirect_params"])) {
            foreach ($_SESSION["login_redirect_params"] as $key => $value) {
                $params .= "$key=$value&";
            }
            unset($_SESSION["login_redirect_params"]);
        }
        header("Location: " . $_SESSION["login_redirect"] . '?' . $params);
        unset($_SESSION["login_redirect"]);
    } else {
        header("Location: home.php");
    }
    exit;
}

if (isLoggedIn("rid")) {
    afterlogin($con, $date);
}

function checkLogin($con, $date)
{
    global $login_failed_dialog;
    $username = $_POST['rid'];
    $password = $_POST['pass'];

    $query = "SELECT password, absconding FROM recruiters WHERE email='$username'";
    $result = pg_query($con, $query);
    if ($result) {
        $user = pg_fetch_assoc($result);
        if ($user) {
            $existingHashFromDb = $user['password'];
            $absconding = $user['absconding'];
            if (password_verify($password, $existingHashFromDb)) {
                if (!empty($absconding)) {
                    $login_failed_dialog = "Your account has been flagged as inactive. Please contact support.";
                } else {
                    $_SESSION['rid'] = $username;
                    afterlogin($con, $date);
                }
            } else {
                $login_failed_dialog = "Incorrect username or password.";
            }
        } else {
            $login_failed_dialog = "User not found.";
        }
    } else {
        $login_failed_dialog = "Error executing query.";
    }
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'register') {

    // CAPTCHA Validation
    if (empty($_SESSION['captcha_answer']) || empty($_POST['captcha'])) {
        $registration_error = "Please complete the math verification.";
        // Generate new captcha
        generateMathCaptcha();
    } elseif ((int)$_POST['captcha'] !== (int)$_SESSION['captcha_answer']) {
        $registration_error = "Math verification failed. Please try again.";
        // Generate new captcha
        generateMathCaptcha();
    } else {
        // CAPTCHA passed - proceed with registration
        // Basic validation
        $full_name = pg_escape_string($con, $_POST['full_name']);
        $company_name = pg_escape_string($con, $_POST['company_name']);
        $email = pg_escape_string($con, $_POST['email']);
        $phone = pg_escape_string($con, $_POST['phone']);
        $company_address = pg_escape_string($con, $_POST['company_address']);

        // Check if email already exists
        $check_email = pg_query($con, "SELECT email FROM recruiters WHERE email='$email'");
        if (pg_num_rows($check_email) > 0) {
            $registration_error = "Email already registered. Please login or use another email.";
        } else {
            // Generate temporary password
            $temp_password = bin2hex(random_bytes(3)); // Generates 6-character hex string
            $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

            // Insert new recruiter with pending status
            $query = "INSERT INTO recruiters (
            full_name, 
            company_name, 
            email, 
            phone, 
            company_address, 
            password, 
            is_verified, 
            created_at
        ) VALUES (
            '$full_name',
            '$company_name',
            '$email',
            '$phone',
            '$company_address',
            '$hashed_password',
            false,
            '$date'
        )";

            $result = pg_query($con, $query);

            if ($result) {
                // Prepare email data
                $email_data = [
                    "name" => $full_name,
                    "email" => $email,
                    "temp_password" => $temp_password,
                    "now" => date("d/m/Y g:i a")
                ];

                // Send email (do not depend on return value)
                sendEmail("recruiter_registration", $email_data, $email, false);

                // Always treat registration as successful
                $registration_success = "Registration successful! A temporary password has been sent to your email. Your account is pending approval.";

                // Store in session and redirect to prevent resubmission
                $_SESSION['registration_success'] = $registration_success;
                header("Location: index.php?tab=register&success=1");
                exit;
            } else {
                $registration_error = "Registration failed. Please try again.";
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        checkLogin($con, $date);
    }
}
?>

<?php
// Check if the form is submitted with the correct identifier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_identifier']) && $_POST['form_identifier'] === 'forgot_password_form') {
    $email = $_POST['reset_username'];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email address.');</script>";
        exit;
    }

    // Check if the email exists in the database
    try {
        // Query to check if the email exists and fetch the user's name
        $query = "SELECT full_name FROM recruiters WHERE email = $1";
        $stmt = pg_prepare($con, "check_email", $query);
        $result = pg_execute($con, "check_email", array($email));

        if (pg_num_rows($result) > 0) {
            // Fetch the user's name
            $row = pg_fetch_assoc($result);
            $name = $row['full_name'];
            $reset_auth_code_timestamp = date('Y-m-d H:i:s');

            // Generate a 20-character random alphanumeric string
            $reset_auth_code = bin2hex(random_bytes(10)); // 10 bytes = 20 hexadecimal characters

            // Update the reset_auth_code column in the database
            $update_query = "UPDATE recruiters SET reset_auth_code = $1, reset_auth_code_timestamp=$3 WHERE email = $2";
            $update_stmt = pg_prepare($con, "update_reset_auth_code", $update_query);
            $update_result = pg_execute($con, "update_reset_auth_code", array($reset_auth_code, $email, $reset_auth_code_timestamp));

            // Check if the update was successful
            if ($update_result && pg_affected_rows($update_result) > 0) {
                // Prepare email data
                $email_data = [
                    "name" => $name, // User's name fetched from the database
                    "reset_auth_code" => $reset_auth_code, // Generated reset auth code
                    "now" => date("d/m/Y g:i a", strtotime($reset_auth_code_timestamp)), // Format the future date, // Current date and time
                ];

                // Send email
                if (!empty($email)) {
                    sendEmail("forgot_pass_link", $email_data, $email, false);
                }

                echo "<script>alert('A password reset link has been sent to your email address. Please check your inbox.');</script>";
            } else {
                echo "<script>alert('Failed to update the reset auth code. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('No account found with this email address. Please enter a valid username.');</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('Database error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Check for success parameter from redirect OR session
if (isset($_GET['success']) && $_GET['success'] == '1') {
    if (isset($_SESSION['registration_success'])) {
        $registration_success = $_SESSION['registration_success'];
        unset($_SESSION['registration_success']);
    } else {
        $registration_success = "Registration successful! A temporary password has been sent to your email. Your account is pending approval.";
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RSSI - Recruiter Portal</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        :root {
            /* DeepSeek Color Palette */
            --deepseek-primary: #0d6efd;
            --deepseek-secondary: #6c757d;
            --deepseek-success: #198754;
            --deepseek-info: #0dcaf0;
            --deepseek-warning: #ffc107;
            --deepseek-danger: #dc3545;
            --deepseek-dark: #212529;
            --deepseek-light: #f8f9fa;
            --deepseek-bg-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            --deepseek-card-bg: rgba(255, 255, 255, 0.95);
            --deepseek-border: rgba(13, 110, 253, 0.1);
            --deepseek-shadow: 0 8px 30px rgba(13, 110, 253, 0.08);
        }

        body {
            background: var(--deepseek-bg-gradient);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }

        .hero-section {
            background: linear-gradient(135deg, var(--deepseek-primary) 0%, #0b5ed7 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--deepseek-shadow);
        }

        .hero-section h2 {
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero-section p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .benefit-icon {
            font-size: 2rem;
            color: var(--deepseek-primary);
            margin-bottom: 1rem;
        }

        .benefit-card {
            background: var(--deepseek-card-bg);
            border: 1px solid var(--deepseek-border);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .benefit-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--deepseek-shadow);
        }

        .form-container {
            background: var(--deepseek-card-bg);
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: var(--deepseek-shadow);
            border: 1px solid var(--deepseek-border);
        }

        .nav-tabs {
            border-bottom: 2px solid var(--deepseek-border);
            margin-bottom: 2rem;
        }

        .nav-tabs .nav-link {
            color: var(--deepseek-secondary);
            font-weight: 500;
            padding: 1rem 1.5rem;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            color: var(--deepseek-primary);
            background: rgba(13, 110, 253, 0.05);
        }

        .nav-tabs .nav-link.active {
            color: var(--deepseek-primary);
            background: none;
            border-bottom: 3px solid var(--deepseek-primary);
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--deepseek-primary) 0%, #0b5ed7 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--deepseek-success) 0%, #157347 100%);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
        }

        .form-control {
            border: 2px solid var(--deepseek-border);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--deepseek-primary);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }

        .input-group-text {
            background: linear-gradient(135deg, var(--deepseek-primary) 0%, #0b5ed7 100%);
            color: white;
            border: none;
            border-radius: 8px 0 0 8px;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.5rem;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(25, 135, 84, 0.1) 0%, rgba(25, 135, 84, 0.05) 100%);
            border-left: 4px solid var(--deepseek-success);
            color: var(--deepseek-success);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(220, 53, 69, 0.05) 100%);
            border-left: 4px solid var(--deepseek-danger);
            color: var(--deepseek-danger);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-container img {
            max-width: 180px;
            height: auto;
            margin-bottom: 1rem;
        }

        .logo-container h4 {
            color: var(--deepseek-dark);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .logo-container .tagline {
            color: var(--deepseek-primary);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .ecosystem-stats {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid var(--deepseek-border);
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--deepseek-primary);
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--deepseek-secondary);
        }

        .required-field::after {
            content: " *";
            color: var(--deepseek-danger);
        }

        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: var(--deepseek-shadow);
        }

        .credits {
            color: var(--deepseek-secondary);
            font-size: 0.9rem;
            margin-top: 2rem;
        }

        .credits a {
            color: var(--deepseek-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .section-title {
            color: var(--deepseek-dark);
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, var(--deepseek-primary) 0%, #0b5ed7 100%);
            border-radius: 2px;
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 1.5rem;
            }

            .hero-section {
                padding: 1.5rem;
                text-align: center;
            }

            .nav-tabs .nav-link {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <main>
        <div class="container py-4">
            <div class="row justify-content-center align-items-center">
                <!-- Left Column - Benefits & Info -->
                <!-- <div class="col-lg-5 d-none d-lg-block">
                    <div class="logo-container mb-4">
                        <img src="../img/phoenix.png" alt="RSSI Logo" class="img-fluid">
                        <h4>Recruiter Portal</h4>
                        <p class="tagline">Build Your Talent Ecosystem</p>
                    </div>

                    <div class="hero-section">
                        <h2>Be Part of the Change</h2>
                        <p class="lead">Join our ecosystem connecting talented job seekers with innovative companies. Together, we create opportunities that transform lives.</p>
                        <div class="mt-4">
                            <h5><i class="bi bi-check-circle me-2"></i>Why Join Our Ecosystem?</h5>
                            <ul class="mt-3">
                                <li>Access to pre-vetted, quality candidates</li>
                                <li>Streamlined hiring process</li>
                                <li>Make a real social impact</li>
                                <li>Community of change-makers</li>
                            </ul>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <h6>Access Talent Pool</h6>
                                <p class="small mb-0">Connect with qualified candidates actively seeking opportunities</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                                <h6>Growth Opportunities</h6>
                                <p class="small mb-0">Scale your organization with the right talent at the right time</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <h6>Verified Profiles</h6>
                                <p class="small mb-0">All candidates go through rigorous verification processes</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="bi bi-hand-thumbs-up"></i>
                                </div>
                                <h6>Social Impact</h6>
                                <p class="small mb-0">Create meaningful employment opportunities in the community</p>
                            </div>
                        </div>
                    </div>

                    <div class="ecosystem-stats">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="stat-item">
                                    <span class="stat-number">500+</span>
                                    <span class="stat-label">Companies</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <span class="stat-number">10K+</span>
                                    <span class="stat-label">Candidates</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <span class="stat-number">95%</span>
                                    <span class="stat-label">Success Rate</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->

                <!-- Right Column - Login/Register -->
                <div class="col-lg-7 col-md-10">
                    <div class="form-container">
                        <!-- Mobile Logo -->
                        <div class="logo-container d-block d-lg-none mb-4">
                            <img src="../img/phoenix.png" alt="RSSI Logo" class="img-fluid" style="max-width: 120px;">
                            <h4>Recruiter Portal</h4>
                            <p class="tagline">Build Your Talent Ecosystem</p>
                        </div>

                        <h5 class="section-title">Welcome to Our Talent Ecosystem</h5>
                        <p class="text-muted mb-4">Join other change-makers in creating employment opportunities. Login or register to start making a difference.</p>

                        <ul class="nav nav-tabs nav-justified mb-4" id="authTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'login') ? 'active' : ''; ?>"
                                    id="login-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#login"
                                    type="button"
                                    role="tab"
                                    onclick="updateURL('login')">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'register') ? 'active' : ''; ?>"
                                    id="register-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#register"
                                    type="button"
                                    role="tab"
                                    onclick="updateURL('register')">
                                    <i class="bi bi-person-plus me-2"></i>Register
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="authTabsContent">
                            <!-- Login Tab -->
                            <div class="tab-pane fade <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'login') ? 'show active' : ''; ?>" id="login" role="tabpanel">
                                <div class="pt-2 pb-2">
                                    <h5 class="card-title pb-0 fs-5">Login to Your Account</h5>
                                    <p class="small text-muted">Enter your credentials to access your recruiter dashboard</p>
                                </div>
                                <form class="row g-3 needs-validation" role="form" method="post" name="login" action="index.php?tab=login">
                                    <div class="col-12">
                                        <label for="yourUsername" class="form-label">Email Address</label>
                                        <div class="input-group has-validation">
                                            <span class="input-group-text" id="inputGroupPrepend"><i class="bi bi-envelope"></i></span>
                                            <input type="email" name="rid" class="form-control" id="tid" placeholder="Enter your email address" required>
                                            <div class="invalid-feedback">Please enter your email address.</div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="pass" class="form-label">Password</label>
                                        <div class="input-group">
                                            <input type="password" name="pass" class="form-control" id="pass" placeholder="Enter your password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">Please enter your password!</div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remember-me">
                                            <label class="form-check-label" for="remember-me">
                                                Remember me
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <button class="btn btn-primary w-100" type="submit" name="login">
                                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                        </button>
                                    </div>

                                    <div class="col-12 text-center">
                                        <p class="small mb-0">Forgot password? <a href="#" data-bs-toggle="modal" data-bs-target="#popup" class="text-decoration-none">Click here to reset</a></p>
                                    </div>
                                </form>
                            </div>

                            <!-- Register Tab -->
                            <div class="tab-pane fade <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'register') ? 'show active' : ''; ?>" id="register" role="tabpanel">
                                <div class="pt-2 pb-2">
                                    <h5 class="card-title pb-0 fs-5">Join Our Ecosystem</h5>
                                    <p class="small text-muted">Register as a recruiter to post jobs and find talent</p>
                                </div>

                                <?php if (!empty($registration_success)): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <?php echo $registration_success; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($registration_error)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <?php echo $registration_error; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <form class="row g-3" role="form" method="post" name="register" action="index.php?tab=register" id="register-form">
                                    <input type="hidden" name="form_type" value="register">

                                    <div class="col-md-6">
                                        <label for="full_name" class="form-label required-field">Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Enter your full name" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="company_name" class="form-label required-field">Company Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-building"></i></span>
                                            <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Enter your company name" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label required-field">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email address" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="phone" class="form-label required-field">Phone Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                            <input type="tel" class="form-control" id="phone" name="phone" pattern="[0-9]{10}" placeholder="Enter 10-digit phone number" required>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="company_address" class="form-label required-field">Company Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                            <textarea class="form-control" id="company_address" name="company_address" rows="3" placeholder="Enter complete company address" required></textarea>
                                        </div>
                                    </div>

                                    <!-- Add this in registration form section -->
                                    <div class="col-md-6">
                                        <label for="captcha" class="form-label required-field">Math Verification</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                                            <input type="text" class="form-control" id="captcha" name="captcha"
                                                placeholder="Solve: <?php echo isset($_SESSION['captcha_question']) ? $_SESSION['captcha_question'] : generateMathCaptcha(); ?> = ?"
                                                required>
                                            <button type="button" class="btn btn-outline-secondary" id="refresh-captcha">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            Solve this simple math problem to prove you're human
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="terms" required>
                                            <label class="form-check-label" for="terms">
                                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="text-decoration-none">Terms and Conditions</a> and want to join the talent ecosystem
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <button class="btn btn-success w-100" type="submit" id="register-button">
                                            <i class="bi bi-person-plus me-2"></i>
                                            <span id="register-text">Join Ecosystem</span>
                                            <span id="register-spinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                                        </button>
                                    </div>

                                    <div class="col-12 text-center">
                                        <p class="small mb-0">Already part of our ecosystem? <a href="#" onclick="switchToLogin(); return false;" class="text-decoration-none">Login here</a></p>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-center">
                            <p class="small text-muted mb-2">By joining, you become part of a community creating employment opportunities</p>
                            <div class="credits">
                                Powered by <a href="https://www.rssi.in/">RSSI Talent Ecosystem</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">
                        <i class="bi bi-file-text me-2"></i>Ecosystem Agreement
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Welcome to Our Talent Ecosystem</h6>
                    <p>By joining our ecosystem, you're becoming part of a community dedicated to creating meaningful employment opportunities. Together, we're building bridges between talent and opportunity.</p>

                    <h6 class="mt-4">Recruiter Commitment</h6>
                    <p>As a member of our ecosystem, you agree to:</p>
                    <ul>
                        <li>Post legitimate job openings that contribute to community development</li>
                        <li>Provide accurate and transparent company information</li>
                        <li>Maintain strict confidentiality of candidate information</li>
                        <li>Promote equal opportunity and non-discrimination</li>
                        <li>Follow all applicable labor laws and regulations</li>
                        <li>Respond to applicants in a timely and professional manner</li>
                        <li>Contribute to our shared goal of reducing unemployment</li>
                    </ul>

                    <div class="alert alert-info mt-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Ecosystem Benefits:</strong> Upon verification, you'll gain access to our talent pool, community events, and networking opportunities with other change-makers.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="popup" tabindex="-1" aria-labelledby="popupLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="popupLabel">
                        <i class="bi bi-key me-2"></i>Reset Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="forgot-password-form">
                        <div class="mb-3">
                            <label for="reset_username" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="reset_username" name="reset_username" placeholder="Enter your registered email" required>
                            </div>
                            <div class="form-text mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Enter the email address associated with your ecosystem account. We'll send you a secure reset link.
                            </div>
                        </div>
                        <input type="hidden" name="form_identifier" value="forgot_password_form">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="send-email-button">
                        <i class="bi bi-send me-2"></i>
                        <span id="button-text">Send Reset Link</span>
                        <span id="spinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        // Function to update URL parameter
        function updateURL(tab) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);

            // Remove success parameter if present
            if (url.searchParams.has('success')) {
                url.searchParams.delete('success');
            }

            window.history.replaceState({}, '', url);
        }

        // Function to switch to login tab
        function switchToLogin() {
            var loginTab = new bootstrap.Tab(document.getElementById('login-tab'));
            loginTab.show();
            updateURL('login');
            return false;
        }

        // Toggle password visibility
        document.getElementById('toggle-password')?.addEventListener('click', function() {
            const passwordField = document.getElementById('pass');
            const icon = this.querySelector('i');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Form validation for login
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()

        // Register form handler
        document.getElementById('register-form')?.addEventListener('submit', function(e) {
            if (this.checkValidity()) {
                const registerButton = document.getElementById('register-button');
                const registerText = document.getElementById('register-text');
                const registerSpinner = document.getElementById('register-spinner');

                // Disable button and show spinner
                registerButton.disabled = true;
                registerText.textContent = 'Joining Ecosystem...';
                registerSpinner.style.display = 'inline-block';

                return true;
            }
        });

        // Forgot password handler
        document.getElementById('send-email-button')?.addEventListener('click', function() {
            const form = document.getElementById('forgot-password-form');
            const emailInput = document.getElementById('reset_username');
            const sendButton = this;
            const buttonText = document.getElementById('button-text');
            const spinner = document.getElementById('spinner');

            if (!emailInput.checkValidity()) {
                emailInput.reportValidity();
                return;
            }

            // Disable button and show spinner
            sendButton.disabled = true;
            buttonText.textContent = 'Sending...';
            spinner.style.display = 'inline-block';

            const formData = new FormData(form);

            fetch('', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'text/html',
                    }
                })
                .then(response => response.text())
                .then(data => {
                    // Reset button
                    sendButton.disabled = false;
                    buttonText.textContent = 'Send Reset Link';
                    spinner.style.display = 'none';

                    const alertMatch = data.match(/alert\('([^']+)'\)/);
                    if (alertMatch) {
                        alert(alertMatch[1]);

                        if (alertMatch[1].includes('password reset link has been sent')) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('popup'));
                            if (modal) {
                                modal.hide();
                                form.reset();
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    sendButton.disabled = false;
                    buttonText.textContent = 'Send Reset Link';
                    spinner.style.display = 'none';
                    alert('An error occurred. Please try again.');
                });
        });

        // Clear form validation when switching tabs
        document.getElementById('authTabs')?.addEventListener('shown.bs.tab', function(event) {
            var forms = document.querySelectorAll('.needs-validation');
            forms.forEach(function(form) {
                form.classList.remove('was-validated');
            });

            // Reset register button when switching tabs
            const registerButton = document.getElementById('register-button');
            const registerText = document.getElementById('register-text');
            const registerSpinner = document.getElementById('register-spinner');

            if (registerButton) {
                registerButton.disabled = false;
                registerText.textContent = 'Join Ecosystem';
                registerSpinner.style.display = 'none';
            }
        });

        // Initialize based on URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');

            if (tab === 'register') {
                var registerTab = new bootstrap.Tab(document.getElementById('register-tab'));
                registerTab.show();

                if (urlParams.has('success')) {
                    document.getElementById('register-form')?.reset();
                }
            } else {
                var loginTab = new bootstrap.Tab(document.getElementById('login-tab'));
                loginTab.show();
            }

            <?php if (!empty($login_failed_dialog)): ?>
                // Show login error modal
                var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                errorModal.show();
            <?php endif; ?>

            <?php if (!empty($registration_success)): ?>
                document.getElementById('register-form')?.reset();
            <?php endif; ?>

            // Prevent form resubmission warning
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        });

        // Update URL when tab is clicked
        document.querySelectorAll('#authTabs button[data-bs-toggle="tab"]')?.forEach(function(tab) {
            tab.addEventListener('click', function() {
                const tabId = this.id === 'login-tab' ? 'login' : 'register';
                updateURL(tabId);
            });
        });
    </script>

    <?php if (!empty($login_failed_dialog)) { ?>
        <div class="modal" tabindex="-1" role="dialog" id="errorModal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle me-2"></i>Login Failed
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo $login_failed_dialog ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Try Again</button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <script>
        // Add this to your existing JavaScript
        document.getElementById('refresh-captcha')?.addEventListener('click', function() {
            fetch('refresh_captcha.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const captchaInput = document.getElementById('captcha');
                        captchaInput.placeholder = 'Solve: ' + data.question + ' = ?';
                        captchaInput.value = '';
                        captchaInput.focus();
                    }
                })
                .catch(error => {
                    console.error('Error refreshing captcha:', error);
                });
        });

        // Initialize captcha when register tab is shown
        document.getElementById('register-tab')?.addEventListener('shown.bs.tab', function() {
            // Generate new captcha if not already set
            if (!<?php echo isset($_SESSION['captcha_question']) ? 'true' : 'false'; ?>) {
                fetch('refresh_captcha.php');
            }
        });
    </script>
</body>

</html>