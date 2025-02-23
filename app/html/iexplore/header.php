<style>
    :root {
        --primary-color: #2563eb;
        --secondary-color: #4f46e5;
        --accent-color: #6366f1;
    }

    body {
        /* font-family: 'Poppins', sans-serif; */
        background-color: #f8fafc;
    }

    .navbar {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    }

    .exam-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        border-radius: 15px;
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
    }

    .exam-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    }

    .category-container {
        max-width: 180px;
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .category-badge {
        background: rgba(37, 99, 235, 0.1);
        color: #2563eb;
        border-radius: 8px;
        padding: 4px 10px;
        font-size: 0.75rem;
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: default;
    }

    .category-more-badge {
        background: rgba(100, 116, 139, 0.1);
        color: #64748b;
        border-radius: 8px;
        padding: 4px 10px;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .category-more-badge:hover {
        background: rgba(37, 99, 235, 0.1);
        color: #2563eb;
    }

    /* Tooltip customization */
    .popover {
        max-width: 300px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }

    .popover-body {
        padding: 12px;
        color: #334155;
    }

    .filter-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
    }

    .btn-gradient {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
    }

    .exam-stats {
        display: flex;
        gap: 1rem;
        margin: 1rem 0;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #64748b;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        border-radius: 15px 15px 0 0;
    }

    .custom-checkbox .custom-control-input:checked~.custom-control-label::before {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .prebanner {
        display: none;
    }

    /* Back to Top Arrow Styles */
    .back-to-top {
        display: none;
        /* Hidden by default */
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
        /* Ensure it's above other elements */
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 50%;
        background-color: #007bff;
        /* Blue background */
        color: white;
        font-size: 18px;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        /* Add a shadow */
        transition: background-color 0.3s, opacity 0.3s;
    }

    .back-to-top:hover {
        background-color: #0056b3;
        /* Darker blue on hover */
    }

    .back-to-top i {
        vertical-align: middle;
    }
</style>
<!-- Modern Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="home.php">
            <i class="bi bi-journal-bookmark-fill me-2"></i>
            iExplore
        </a>

        <!-- Add a toggle button for mobile view -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="home.php">Home</a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" href="page2.php">Page 2</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="page3.php">Page 3</a>
                </li> -->
            </ul>
        </div>

        <!-- User Actions -->
        <div class="d-flex align-items-center">
            <?php if (isLoggedIn("aid")) : ?>
                <!-- Logged-in State -->
                <a href="#" class="text-white me-3"><i class="bi bi-bell"></i></a>
                <div class="dropdown">
                    <?php
                    // Get user details from database
                    $email = $_SESSION['aid'];
                    $user_query = pg_query($con, "SELECT name FROM test_users WHERE email='$email'");
                    $user = pg_fetch_assoc($user_query);
                    $displayName = $user['name'] ?? explode('@', $email)[0];
                    ?>

                    <a class="btn btn-light btn-sm dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($displayName) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="my_exam.php"><i class="bi bi-graph-up-arrow me-2"></i>My Exam</a></li>
                        <!-- <li><a class="dropdown-item" href="resetpassword.php"><i class="bi bi-gear me-2"></i>Reset Password</a></li> -->
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            <?php else : ?>
                <!-- Guest State -->
                <a href="register_user.php" class="btn btn-outline-light me-2">Register</a>
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>