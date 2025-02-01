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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #4f46e5;
            --accent-color: #6366f1;
        }

        body {
            font-family: 'Poppins', sans-serif;
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
    </style>
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

    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
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
                    <a href="index.php" class="btn btn-primary">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

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

    <!-- Modern Footer -->
    <footer class="bg-light border-top py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <!-- <img src="logo.svg" alt="Logo" style="height: 40px;" class="me-3"> -->
                        <span class="text-muted">© 2024 Rina Shiksha Sahayak Foundation. All rights reserved.</span>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <a href="#" class="text-muted me-3"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="text-muted me-3"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-muted"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div>
    </footer>

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
                    window.location.href = "testc.php?exam_id=" + examId;
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
</body>

</html>