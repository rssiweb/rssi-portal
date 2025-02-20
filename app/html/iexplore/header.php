<!-- Modern Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="home.php">
                <i class="bi bi-journal-bookmark-fill me-2"></i>
                iExplore
            </a>
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
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else : ?>
                    <!-- Guest State -->
                    <a href="register_user.php" class="btn btn-outline-light me-2">Register</a>
                    <!-- <a href="index.php" class="btn btn-primary">Login</a> -->
                    <!-- Trigger Button -->
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>