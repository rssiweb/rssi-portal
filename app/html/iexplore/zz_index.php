<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_iexplore.php");

$date = date('Y-m-d H:i:s');
$login_failed_dialog = "";

function afterlogin($con, $date)
{
    $email = $_SESSION['eid'];
    $user_query = pg_query($con, "select password_updated_by,password_updated_on,default_pass_updated_on from test_users WHERE email='$email'");
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
    pg_query($con, "INSERT INTO userlog_member VALUES (DEFAULT,'$email','$user_ip','$date')");

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

if (isLoggedIn("eid")) {
    afterlogin($con, $date);
}

function checkLogin($con, $date)
{
    global $login_failed_dialog;
    $email = $_POST['eid'];
    $password = $_POST['pass'];

    $query = "SELECT password, absconding FROM test_users WHERE email='$email'";
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
                    $_SESSION['eid'] = $email;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        checkLogin($con, $date);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - iExplore</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #4f46e5;
            --accent-color: #6366f1;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            
        }

        .split-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }

        /* Advertisement Section */
        .ad-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 4rem;
            position: relative;
            overflow: hidden;
        }

        .ad-content {
            position: relative;
            z-index: 2;
            color: white;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .ad-blob {
            position: absolute;
            width: 600px;
            height: 600px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            filter: blur(60px);
        }

        .ad-blob-1 {
            top: -20%;
            right: -30%;
        }

        .ad-blob-2 {
            bottom: -30%;
            left: -20%;
        }

        .exam-features li {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            margin-bottom: 1rem;
            backdrop-filter: blur(5px);
        }

        /* Login Form Section */
        .form-section {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: url('data:image/svg+xml,<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><path fill="%23e2e8f0" d="M45.7,-45.7C59.3,-32,70.4,-16,70.8,0.3C71.1,16.6,60.7,33.2,47.1,47.9C33.5,62.6,16.7,75.4,-2.1,77.5C-20.9,79.6,-41.8,71,-55.7,56.3C-69.6,41.6,-76.5,20.8,-75.5,0.9C-74.5,-19.1,-65.6,-38.2,-51.7,-51.9C-37.8,-65.6,-18.9,-73.8,-0.3,-73.5C18.3,-73.2,36.6,-64.4,45.7,-45.7Z"/></svg>') no-repeat center center;
            background-size: cover;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            border: 2px solid var(--primary-color);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header img {
            width: 80px;
            margin-bottom: 1rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group input {
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            transition: transform 0.3s ease;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }

        @media (max-width: 768px) {
            .split-container {
                grid-template-columns: 1fr;
            }

            .ad-section {
                padding: 2rem;
            }

            .form-container {
                padding: 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="split-container">
        <!-- Advertisement Section -->
        <div class="ad-section">
            <div class="ad-blob ad-blob-1"></div>
            <div class="ad-blob ad-blob-2"></div>
            <div class="ad-content">
                <h2 class="mb-4 animate__animated animate__fadeIn">Welcome to iExplore</h2>
                <ul class="exam-features list-unstyled">
                    <li class="animate__animated animate__fadeInLeft">
                        <h5>📊 Track Your Progress</h5>
                        <p class="mb-0">Monitor your performance with detailed analytics</p>
                    </li>
                    <li class="animate__animated animate__fadeInLeft delay-1">
                        <h5>📚 Access Study Materials</h5>
                        <p class="mb-0">Get curated resources for better preparation</p>
                    </li>
                    <li class="animate__animated animate__fadeInLeft delay-2">
                        <h5>🏅 Compete with Peers</h5>
                        <p class="mb-0">Join leaderboards and improve your ranking</p>
                    </li>
                </ul>
                <div class="mt-4 text-center animate__animated animate__fadeInUp">
                    <div class="d-flex justify-content-center gap-3 mb-3">
                        <div class="badge bg-white text-primary p-2"><i class="bi bi-shield-check"></i> Secure Login</div>
                        <div class="badge bg-white text-primary p-2"><i class="bi bi-clock"></i> 24/7 Access</div>
                    </div>
                    <div class="text-white-50">Join 500K+ students preparing for success</div>
                </div>
            </div>
        </div>

        <!-- Login Form Section -->
        <div class="form-section">
            <div class="form-container">
                <div class="form-header">
                    <!-- <img src="https://via.placeholder.com/80x80.png?text=SE" alt="Logo" class="rounded-circle"> -->
                    <h3 class="mb-2">Login to Your Account</h3>
                    <p class="text-muted">Continue your exam preparation journey</p>
                </div>

                <form method="POST" action="">
                    <div class="input-group">
                        <input type="email" class="form-control" id="eid" name="eid" placeholder="Email Address" required>
                        <i class="bi bi-envelope input-icon"></i>
                    </div>

                    <div class="input-group">
                        <input type="password" class="form-control" id="pass" name="pass" placeholder="Password" required>
                        <i class="bi bi-lock input-icon"></i>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="show-password">
                        <label class="form-check-label" for="show-password">Show Password</label>
                    </div>

                    <button type="submit" class="login-btn" name="login">
                        Login <i class="bi bi-arrow-right"></i>
                    </button>

                    <div class="mt-3 text-center">
                        <p><a href="#" class="text-primary" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot Password?</a></p>
                        <p class="text-muted small mb-0">Don't have an account? <a href="register_user.php" class="text-primary text-decoration-none">Sign up</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Forgot Password?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Please contact support at <strong>info@rssi.in</strong> or call <strong>7980168159</strong> for assistance.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <?php if ($login_failed_dialog) { ?>
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="errorModalLabel">Login Failed</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo $login_failed_dialog ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var myModal = new bootstrap.Modal(document.getElementById('errorModal'));
                myModal.show();
            });
        </script>
    <?php } ?>

    <script>
        // Show/Hide Password
        const passwordInput = document.getElementById('pass');
        const showPasswordCheckbox = document.getElementById('show-password');
        showPasswordCheckbox.addEventListener('change', function() {
            passwordInput.type = this.checked ? 'text' : 'password';
        });
    </script>
    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>