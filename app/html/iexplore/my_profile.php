<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_iexplore.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
?>
<?php

$user_id = $id;

// Fetch user data
$query = "SELECT name, email, contact, created_at FROM test_users WHERE id = $1";
$result = pg_query_params($con, $query, array($user_id));
$user = pg_fetch_assoc($result);

if (!$user) {
    echo "User not found.";
    exit;
}

$name = $user['name'];
$email = $user['email'];
$contact = $user['contact'];
$created_at = date('F j, Y', strtotime($user['created_at']));

// Handle contact update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact'])) {
    $new_contact = $_POST['contact'];
    $update_query = "UPDATE test_users SET contact = $1 WHERE id = $2";
    $update_result = pg_query_params($con, $update_query, array($new_contact, $user_id));

    if ($update_result) {
        header("Location: my_profile.php"); // Refresh the page
        exit;
    } else {
        echo "Failed to update contact.";
    }
}

// Handle account deletion
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
//     $delete_query = "DELETE FROM test_users WHERE id = $1";
//     $delete_result = pg_query_params($con, $delete_query, array($user_id));

//     if ($delete_result) {
//         session_destroy(); // Destroy the session
//         header("Location: login.php"); // Redirect to login page
//         exit;
//     } else {
//         echo "Failed to delete account.";
//     }
// }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <style>
        .profile-pic {
            width: 100px;
            height: 100px;
            background-color: #007bff;
            color: white;
            font-size: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <!-- Profile Photo (Initials) -->
                        <div class="profile-pic">
                            <?php
                            $name_parts = explode(' ', $name);
                            $initials = '';

                            if (isset($name_parts[0])) {
                                $initials .= strtoupper(substr($name_parts[0], 0, 1));
                            }

                            if (isset($name_parts[1])) {
                                $initials .= strtoupper(substr($name_parts[1], 0, 1));
                            }

                            echo $initials;
                            ?>
                        </div>
                        <h2 class="card-title mt-3"><?php echo $name; ?></h2>
                        <p class="card-text"><?php echo $email; ?></p>
                        <p class="card-text">User ID: <?php echo $id; ?></p>
                        <p class="card-text">
                            Contact: <?php echo $contact ? $contact : 'Not provided'; ?>
                            <span data-bs-toggle="modal" data-bs-target="#updateContactModal" style="cursor: pointer;">
                                <i class="bi bi-pencil text-secondary ms-2"></i>
                            </span>
                        </p>
                        <p class="card-text">Account Created: <?php echo $created_at; ?></p>
                    </div>

                    <!-- Bootstrap Modal -->
                    <div class="modal fade" id="updateContactModal" tabindex="-1" aria-labelledby="updateContactModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="updateContactModalLabel">Update Contact Number</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Update Contact Form -->
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label for="contact" class="form-label">New Contact Number</label>
                                            <input type="text" class="form-control" id="contact" name="contact" value="<?php echo $contact; ?>" pattern="[0-9]{10}" title="Please enter a valid contact number." required>
                                        </div>
                                        <button type="submit" name="update_contact" class="btn btn-primary w-100">Update Contact</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>