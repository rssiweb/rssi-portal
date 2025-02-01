<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/email.php");

// Function to generate a 12-digit random user ID
function generateUserId()
{
    return str_pad(mt_rand(0, 999999999999), 12, '0', STR_PAD_LEFT);
}

$message = ''; // Variable to store the message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = generateUserId();
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $random_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
    // Hash the generated password securely
    $password = password_hash(trim($random_password), PASSWORD_DEFAULT);

    // Input validation
    if (empty($name) || empty($email) || empty($contact)) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.";
    } elseif (!preg_match('/^\d{10}$/', $contact)) {
        $message = "Contact number must be a valid 10-digit number.";
    } else {
        // If no errors, insert the data into the database
        $query = "INSERT INTO test_users (id, name, email, password) VALUES ($1, $2, $3, $4)";
        $result = pg_query_params($con, $query, array($userId, $name, $email, $password));

        if ($result) {
            sendEmail("register_user", [
                "applicant_name" => $name,
                "email" => $email,
                "password" => $random_password,
            ], $email);
            // Success message
            $message = "User registered successfully!";
        } else {
            // Capture the error message from PostgreSQL
            $errorMessage = pg_last_error($con);
            if (strpos($errorMessage, 'duplicate key value violates unique constraint') !== false) {
                $message = "Error: The email address is already in use.";
            } else {
                $message = "Error: " . htmlspecialchars($errorMessage);
            }
        }
    }

    // Display the message in an alert
    echo "<script>
        alert('" . addslashes($message) . "');
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
         if ('" . addslashes($message) . "'.includes('Error')) {
        window.location.reload();
    } else {
        window.location.href = 'index.php';
    }
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Portal Registration</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #4f46e5;
            --accent-color: #6366f1;
            --exam-red: #ef4444;
            --exam-green: #22c55e;
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

        .ad-blob-1 { top: -20%; right: -30%; }
        .ad-blob-2 { bottom: -30%; left: -20%; }

        .exam-features li {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            margin-bottom: 1rem;
            backdrop-filter: blur(5px);
        }

        /* Registration Form Section */
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

        .register-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            transition: transform 0.3s ease;
        }

        .register-btn:hover {
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
                        <h5>🎯 1000+ Mock Tests</h5>
                        <p class="mb-0">Practice with our curated question banks</p>
                    </li>
                    <li class="animate__animated animate__fadeInLeft delay-1">
                        <h5>📈 Performance Analytics</h5>
                        <p class="mb-0">Detailed insights with AI-powered reports</p>
                    </li>
                    <li class="animate__animated animate__fadeInLeft delay-2">
                        <h5>🏆 Expert Preparation</h5>
                        <p class="mb-0">Study materials from top educators</p>
                    </li>
                </ul>
                <div class="mt-4 text-center animate__animated animate__fadeInUp">
                    <div class="d-flex justify-content-center gap-3 mb-3">
                        <div class="badge bg-white text-primary p-2"><i class="bi bi-stopwatch"></i> Timed Exams</div>
                        <div class="badge bg-white text-primary p-2"><i class="bi bi-shield-check"></i> Secure Platform</div>
                    </div>
                    <div class="text-white-50">Trusted by 500K+ students worldwide</div>
                </div>
            </div>
        </div>

        <!-- Registration Form Section -->
        <div class="form-section">
            <div class="form-container">
                <div class="form-header">
                    <!-- <img src="https://via.placeholder.com/80x80.png?text=SE" alt="Logo" class="rounded-circle"> -->
                    <h3 class="mb-2">Create Exam Account</h3>
                    <p class="text-muted">Start your exam preparation journey</p>
                </div>
                
                <form method="POST" action="">
                    <div class="input-group">
                        <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" required>
                        <i class="bi bi-person input-icon"></i>
                    </div>

                    <div class="input-group">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" required>
                        <i class="bi bi-envelope input-icon"></i>
                    </div>

                    <div class="input-group">
                        <input type="tel" class="form-control" id="contact" name="contact" 
                               placeholder="Contact Number" required pattern="[0-9]{10}">
                        <i class="bi bi-phone input-icon"></i>
                    </div>

                    <button type="submit" class="register-btn">
                        Register Now <i class="bi bi-arrow-right"></i>
                    </button>
                </form>

                <div class="mt-4 text-center text-muted">
                    <small>By registering, you agree to our <a href="#" class="text-primary">Terms</a> and <a href="#" class="text-primary">Privacy Policy</a></small>
                </div>
            </div>
        </div>
    </div>
</body>

</html>