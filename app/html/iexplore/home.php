<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_iexplore.php");

// Fetch categories dynamically for filter dropdown
$categoryQuery = "SELECT id, name FROM test_categories WHERE is_active = true ORDER BY name;";
$categoryResult = pg_query($con, $categoryQuery);

if (!$categoryResult) {
    echo "Error fetching categories.";
    exit;
}

$categories = pg_fetch_all($categoryResult);

// Fetch exams from the database
$query = "
SELECT 
    e.id AS exam_id,
    e.name AS exam_name,
    e.total_duration AS duration,
    e.total_questions AS total_questions,
    e.created_at AS created_date,
    STRING_AGG(c.name, ', ') AS categories
FROM 
    test_exams e
LEFT JOIN test_exam_categories ec ON ec.exam_id = e.id 
LEFT JOIN test_categories c ON c.id = ec.category_id
WHERE e.is_active=true
GROUP BY 
    e.id, e.name, e.total_duration, e.total_questions, e.created_at
ORDER BY 
    e.created_at DESC;
";

$result = pg_query($con, $query);

if (!$result) {
    echo "Error fetching exams.";
    exit;
}

$exams = pg_fetch_all($result);
?>
<?php

$date = date('Y-m-d H:i:s');
$login_failed_dialog = "";

// Function to generate a 12-digit random user ID
function generateUserId()
{
    return str_pad(mt_rand(0, 999999999999), 12, '0', STR_PAD_LEFT);
}

// Function to handle post-login actions
function afterLogin($con, $date)
{
    if (!isset($_SESSION['aid']) || !isset($_SESSION['user_type'])) {
        header("Location: index.php");
        exit;
    }

    $user_id = $_SESSION['aid'];
    $user_type = $_SESSION['user_type'];

    // Fetch password-related details based on user type
    $query = match ($user_type) {
        'iexplore' => "SELECT password_updated_by, password_updated_on, default_pass_updated_on FROM test_users WHERE email='$user_id'",
        'rssi-member' => "SELECT password_updated_by, password_updated_on, default_pass_updated_on FROM rssimyaccount_members WHERE email='$user_id'",
        'tap' => "SELECT password_updated_by, password_updated_on, default_pass_updated_on FROM signup WHERE email='$user_id'",
        default => null,
    };

    if (!$query) {
        header("Location: error.php?message=Invalid user type");
        exit;
    }

    $result = pg_query($con, $query);
    if (!$result) {
        header("Location: error.php?message=Database query failed");
        exit;
    }

    $row = pg_fetch_row($result);
    if (!$row) {
        header("Location: error.php?message=No data found");
        exit;
    }

    // Store password-related details in the session
    $_SESSION['password_updated_by'] = $row[0];
    $_SESSION['password_updated_on'] = $row[1];
    $_SESSION['default_pass_updated_on'] = $row[2];

    // Check if password reset is required
    passwordCheck($row[0], $row[1], $row[2]);

    // Log the login attempt
    $user_ip = $_SERVER['REMOTE_ADDR'];
    pg_query($con, "INSERT INTO userlog_member VALUES (DEFAULT, '$user_id', '$user_ip', '$date')");

    // Redirect to the appropriate page
    // if (isset($_SESSION["login_redirect"])) {
    //     $params = http_build_query($_SESSION["login_redirect_params"] ?? []);
    //     header("Location: " . $_SESSION["login_redirect"] . '?' . $params);
    //     unset($_SESSION["login_redirect"], $_SESSION["login_redirect_params"]);
    // } else {
    //     header("Location: home.php");
    // }
    // exit;
}
// Function to handle login
function checkLogin($con, $date)
{
    global $login_failed_dialog;

    $username = $_POST['aid'];
    $password = $_POST['pass'];

    // Check in rssi-member (rssimyaccount_members table)
    $query = "SELECT password, absconding, fullname, email, phone FROM rssimyaccount_members WHERE associatenumber='$username'";
    $result = pg_query($con, $query);
    if ($result && $user = pg_fetch_assoc($result)) {
        if ($user['password'] !== null && password_verify($password, $user['password'])) {
            if (!empty($user['absconding'])) {
                $login_failed_dialog = "Your account has been flagged as inactive. Please contact support.";
            } else {
                $_SESSION['aid'] = $user['email'];
                $_SESSION['user_type'] = 'rssi-member';

                // Insert or update user in test_users table
                $test_user_query = pg_query($con, "SELECT id, name, email, user_type, contact FROM test_users WHERE email='{$user['email']}'");
                $test_user = pg_fetch_assoc($test_user_query);

                if (!$test_user) {
                    $new_user_id = generateUserId();
                    pg_query($con, "INSERT INTO test_users (id, name, email, user_type, contact, created_at) VALUES ('$new_user_id', '{$user['fullname']}', '{$user['email']}', 'rssi-member', '{$user['phone']}', '$date')");
                } else {
                    pg_query($con, "UPDATE test_users SET name='{$user['fullname']}', email='{$user['email']}', user_type='rssi-member', contact='{$user['phone']}' WHERE id='{$test_user['id']}'");
                }

                afterLogin($con, $date);
                return; // Exit the function after successful login
            }
        }
    }

    // Check in tap (signup table)
    $query = "SELECT password, absconding, applicant_name, email, telephone FROM signup WHERE email='$username'";
    $result = pg_query($con, $query);
    if ($result && $user = pg_fetch_assoc($result)) {
        if ($user['password'] !== null && password_verify($password, $user['password'])) {
            if (!empty($user['absconding'])) {
                $login_failed_dialog = "Your account has been flagged as inactive. Please contact support.";
            } else {
                $_SESSION['aid'] = $username;
                $_SESSION['user_type'] = 'tap';

                // Insert or update user in test_users table
                $test_user_query = pg_query($con, "SELECT id, name, email, user_type, contact FROM test_users WHERE email='{$user['email']}'");
                $test_user = pg_fetch_assoc($test_user_query);

                if (!$test_user) {
                    $new_user_id = generateUserId();
                    pg_query($con, "INSERT INTO test_users (id, name, email, user_type, contact, created_at) VALUES ('$new_user_id', '{$user['applicant_name']}', '{$user['email']}', 'tap', '{$user['telephone']}', '$date')");
                } else {
                    pg_query($con, "UPDATE test_users SET name='{$user['applicant_name']}', email='{$user['email']}', user_type='tap', contact='{$user['telephone']}' WHERE id='{$test_user['id']}'");
                }

                afterLogin($con, $date);
                return; // Exit the function after successful login
            }
        }
    }

    // Check in iexplore (test_users table)
    $query = "SELECT password, absconding FROM test_users WHERE email='$username'";
    $result = pg_query($con, $query);
    if ($result && $user = pg_fetch_assoc($result)) {
        if ($user['password'] !== null && password_verify($password, $user['password'])) {
            if (!empty($user['absconding'])) {
                $login_failed_dialog = "Your account has been flagged as inactive. Please contact support.";
            } else {
                $_SESSION['aid'] = $username;
                $_SESSION['user_type'] = 'iexplore';
                afterLogin($con, $date);
                return; // Exit the function after successful login
            }
        }
    }

    // If no match found
    $login_failed_dialog = "Incorrect username or password.";
}

// Handle login request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    checkLogin($con, $date);
}

// Redirect logged-in users
if (isLoggedIn("aid")) {
    afterLogin($con, $date);
}
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-11316670180');
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exam Portal | Test Your Skills</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'banner.php'; ?>
    <main class="container py-5">
        <div class="row g-4">
            <!-- Enhanced Filter Section -->
            <div class="col-lg-3">
                <div class="filter-card">
                    <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filters</h5>
                    <div class="mb-4">
                        <label class="form-label text-muted">Categories</label>
                        <select id="categoryFilter" class="form-select" multiple style="height: 150px;">
                            <?php foreach ($categories as $category) : ?>
                                <option value="<?= $category['name'] ?>"><?= $category['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Search Exams</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Exam name...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern Exam Grid -->
            <div class="col-lg-9">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="examContainer">
                    <?php if ($exams) : ?>
                        <?php foreach ($exams as $exam) : ?>
                            <div class="col exam-card" data-categories="<?= strtolower($exam['categories']) ?>" data-name="<?= strtolower($exam['exam_name']) ?>">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="card-title mb-0"><?= $exam['exam_name'] ?></h5>
                                            <div class="category-container">
                                                <?php
                                                $categories = explode(', ', $exam['categories']);
                                                $maxVisible = 2; // Number of categories to show before truncating
                                                $totalCategories = count($categories);

                                                foreach (array_slice($categories, 0, $maxVisible) as $category) :
                                                    if (!empty(trim($category))) :
                                                ?>
                                                        <span class="category-badge" data-bs-toggle="tooltip" title="<?= trim($category) ?>">
                                                            <?= trim($category) ?>
                                                        </span>
                                                    <?php
                                                    endif;
                                                endforeach;

                                                if ($totalCategories > $maxVisible) :
                                                    $remaining = $totalCategories - $maxVisible;
                                                    ?>
                                                    <span class="category-more-badge"
                                                        data-bs-toggle="popover"
                                                        data-bs-html="true"
                                                        data-bs-content="<?= htmlspecialchars(implode('<br>', $categories)) ?>">
                                                        +<?= $remaining ?> more
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="exam-stats">
                                            <div class="stat-item">
                                                <i class="bi bi-clock-history text-primary"></i>
                                                <span><?= $exam['duration'] ?>m</span>
                                            </div>
                                            <div class="stat-item">
                                                <i class="bi bi-question-circle text-primary"></i>
                                                <span><?= $exam['total_questions'] ?> Qs</span>
                                            </div>
                                        </div>
                                        <button class="btn btn-gradient w-100" data-bs-toggle="modal" data-bs-target="#examModal<?= $exam['exam_id'] ?>">
                                            Start Assessment <i class="bi bi-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Enhanced Modal -->
                            <div class="modal fade" id="examModal<?= $exam['exam_id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Assessment Details</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-primary">
                                                <i class="bi bi-info-circle me-2"></i>
                                                Complete this assessment in one session - timer starts when you begin
                                            </div>

                                            <div class="row g-3 mb-4">
                                                <div class="col-6">
                                                    <div class="border p-3 rounded text-center">
                                                        <div class="text-muted small">Duration</div>
                                                        <div class="h5 mb-0"><?= $exam['duration'] ?> mins</div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="border p-3 rounded text-center">
                                                        <div class="text-muted small">Questions</div>
                                                        <div class="h5 mb-0"><?= $exam['total_questions'] ?></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <h6 class="mb-3"><i class="bi bi-list-task me-2"></i>Instructions</h6>
                                            <ul class="list-unstyled">
                                                <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>Stable internet required</li>
                                                <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>No page refresh during test</li>
                                                <li class="mb-2"><i class="bi bi-check-circle me-2 text-success"></i>Read questions carefully</li>
                                            </ul>

                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" id="agreeCheck<?= $exam['exam_id'] ?>">
                                                <label class="form-check-label" for="agreeCheck<?= $exam['exam_id'] ?>">
                                                    I agree to the assessment rules
                                                </label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                            <button type="button" class="btn btn-primary proceed-btn" disabled data-exam-id="<?= $exam['exam_id'] ?>">
                                                Start Now <i class="bi bi-arrow-right ms-2"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="col-12 text-center py-5">
                            <img src="placeholder-empty-state.svg" alt="No exams" class="mb-3" style="height: 150px;">
                            <h5 class="text-muted">No assessments available at the moment</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <!-- Keep existing JavaScript functionality -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Filter by category and search by exam name
            const categoryFilter = document.getElementById("categoryFilter");
            const searchInput = document.getElementById("searchInput");
            const examCards = document.querySelectorAll(".exam-card");

            function filterExams() {
                const selectedCategories = Array.from(categoryFilter.selectedOptions).map(option => option.value.toLowerCase());
                const searchQuery = searchInput.value.toLowerCase();

                examCards.forEach(card => {
                    const cardCategories = card.dataset.categories.split(", ");
                    const cardName = card.dataset.name;
                    const categoryMatch = selectedCategories.length === 0 || selectedCategories.some(cat => cardCategories.includes(cat));
                    const searchMatch = cardName.includes(searchQuery);

                    card.style.display = categoryMatch && searchMatch ? "block" : "none";
                });
            }

            categoryFilter.addEventListener("change", filterExams);
            searchInput.addEventListener("input", filterExams);

            // Enable proceed button only when terms checkbox is checked
            document.querySelectorAll(".form-check-input").forEach(function(checkbox) {
                checkbox.addEventListener("change", function() {
                    const proceedButton = this.closest(".modal-content").querySelector(".proceed-btn");
                    proceedButton.disabled = !this.checked;
                });
            });

            // Proceed button click to navigate
            document.querySelectorAll(".proceed-btn").forEach(function(button) {
                button.addEventListener("click", function() {
                    const examId = this.dataset.examId;
                    window.location.href = "exam_test.php?exam_id=" + examId;
                });
            });
        });
    </script>
    <script>
        // Initialize Bootstrap components
        document.addEventListener('DOMContentLoaded', function() {
            // Tooltips for individual categories
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    boundary: 'window'
                });
            });

            // Popover for "more" categories
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl, {
                    trigger: 'hover focus',
                    placement: 'left'
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="loginModalLabel">Login to Your Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4">
                    <!-- Error Message -->
                    <?php if ($login_failed_dialog) { ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $login_failed_dialog; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php } ?>

                    <!-- Login Form -->
                    <form method="POST" action="">
                        <!-- Email Input -->
                        <div class="mb-3">
                            <label for="aid" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="aid" name="aid" placeholder="Enter your username" required>
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="mb-3">
                            <label for="pass" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="pass" name="pass" placeholder="Enter your password" required>
                            </div>
                        </div>

                        <!-- Show Password Checkbox -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="show-password">
                            <label class="form-check-label" for="show-password">Show Password</label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100 mb-3" name="login">
                            Login <i class="bi bi-arrow-right ms-2"></i>
                        </button>

                        <!-- Forgot Password Link -->
                        <div class="text-center">
                            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot Password?</a>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer (Optional) -->
                <div class="modal-footer bg-light">
                    <p class="text-muted small mb-0">Don't have an account? <a href="register_user.php" class="text-primary text-decoration-none">Sign up</a></p>
                </div>
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
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var myModal = new bootstrap.Modal(document.getElementById('loginModal'));
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
</body>

</html>