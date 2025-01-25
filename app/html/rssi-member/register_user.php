<?php
require_once __DIR__ . "/../../bootstrap.php";

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
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    // Input validation
    if (empty($name) || empty($email) || empty($contact) || empty($_POST['password'])) {
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
        window.location.reload();
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .form-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .input-group-text {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-container">
            <h3 class="text-center mb-4">User Registration</h3>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="mb-3">
                    <label for="contact" class="form-label">Contact Number</label>
                    <input type="text" class="form-control" id="contact" name="contact" placeholder="Enter your contact number" required maxlength="10" pattern="\d{10}">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        <span class="input-group-text" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
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
    </script>
</body>

</html>