<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Ensure the user is logged in
if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
validation();
?>
<?php

// Fetch exams with related categories
$query = "
    SELECT te.id AS exam_id, te.name AS exam_name, te.total_questions, te.total_duration, te.is_active, te.created_at,
           STRING_AGG(tc.name, ', ') AS categories, STRING_AGG(tc.id::text, ', ') AS category_ids -- Fetch category IDs as a comma-separated string
    FROM test_exams te
    LEFT JOIN test_exam_categories tec ON te.id = tec.exam_id
    LEFT JOIN test_categories tc ON tec.category_id = tc.id
    GROUP BY te.id
    ORDER BY te.created_at DESC;
";
$result = pg_query($con, $query);

if (!$result) {
    die("Error in query: " . pg_last_error($con));
}

// Fetch categories for the dropdown in the add/edit form
$categoriesQuery = "SELECT id, name FROM test_categories WHERE is_active=true ORDER BY name";
$categoriesResult = pg_query($con, $categoriesQuery);

if (!$categoriesResult) {
    die("Error in categories query: " . pg_last_error($con));
}
?>
<?php
// Handle the delete operation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_exam'])) {
    $examId = $_POST['exam_id'];

    // Update the table name to test_exams (as per your schema)
    $deleteQuery = "DELETE FROM test_exams WHERE id = $1";  // Correct table name is test_exams
    if ($stmt = pg_prepare($con, "delete_exam", $deleteQuery)) {
        $result = pg_execute($con, "delete_exam", [$examId]);
        if ($result) {
            $message = 'Exam deleted successfully!';
        } else {
            $message = 'Error deleting exam!';
        }
        echo "<script>alert('$message'); if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        window.location.reload();</script>";  // Reload the page to reflect changes
    }
}
?>
<?php
// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_exam'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $name = pg_escape_string($con, $_POST['name']);
    $total_questions = intval($_POST['total_questions']);
    $total_duration = intval($_POST['total_duration']);
    $categories = $_POST['categories']; // Array of category IDs
    $status = $_POST['status'];

    if ($id) {
        // Update existing exam
        $updateExamQuery = "
            UPDATE test_exams
            SET name = $1, total_questions = $2, total_duration = $3, is_active = $5, created_at = CURRENT_TIMESTAMP
            WHERE id = $4
        ";

        $updateExamResult = pg_query_params($con, $updateExamQuery, [$name, $total_questions, $total_duration, $id, $status]);

        if (!$updateExamResult) {
            die("Error updating exam: " . pg_last_error($con));
        }

        // Clear existing categories
        $deleteCategoriesQuery = "DELETE FROM test_exam_categories WHERE exam_id = $1";
        pg_query_params($con, $deleteCategoriesQuery, [$id]);
    } else {
        // Insert new exam
        $insertExamQuery = "
            INSERT INTO test_exams (name, total_questions, total_duration, created_at,is_active)
            VALUES ($1, $2, $3, CURRENT_TIMESTAMP,$4)
            RETURNING id
        ";

        $insertExamResult = pg_query_params($con, $insertExamQuery, [$name, $total_questions, $total_duration, $status]);

        if (!$insertExamResult) {
            die("Error inserting exam: " . pg_last_error($con));
        }

        $row = pg_fetch_assoc($insertExamResult);
        $id = $row['id'];
    }

    // Insert categories
    foreach ($categories as $category_id) {
        $insertCategoryQuery = "INSERT INTO test_exam_categories (exam_id, category_id) VALUES ($1, $2)";
        pg_query_params($con, $insertCategoryQuery, [$id, $category_id]);
    }

    // Show success message
    echo "<script>
        alert('Exam updated successfully.');
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Management</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Exam Management</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">iExplore Edge</li>
                    <li class="breadcrumb-item active">Exam Management</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container my-5">
                                <!-- <h1 class="mb-4">Exam Management</h1> -->
                                <!-- Add New Exam Button -->
                                <div class="text-end mb-3">
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#examModal" id="addExamBtn">Add New Exam</button>
                                </div>
                                <!-- Exam Table -->
                                <div class="table-responsive">
                                    <table class="table" id="table-id">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Total Questions</th>
                                                <th>Categories</th>
                                                <th>Total Duration (min)</th>
                                                <th>Status</th>
                                                <th>Created At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = pg_fetch_assoc($result)): ?>
                                                <tr>
                                                    <td><?= $row['exam_id'] ?></td>
                                                    <td><?= $row['exam_name'] ?></td>
                                                    <td><?= $row['total_questions'] ?></td>
                                                    <td><?= $row['categories'] ?></td>
                                                    <td><?= $row['total_duration'] ?></td>
                                                    <td><?= $row['is_active'] === 't' ? 'Active' : 'Inactive' ?></td>
                                                    <td><?= $row['created_at'] ?></td>
                                                    <td>
                                                        <button class="btn btn-warning btn-sm me-2 edit-exam"
                                                            data-id="<?= $row['exam_id'] ?>"
                                                            data-name="<?= $row['exam_name'] ?>"
                                                            data-total-questions="<?= $row['total_questions'] ?>"
                                                            data-total-duration="<?= $row['total_duration'] ?>"
                                                            data-categories="<?= $row['category_ids'] ?>"
                                                            data-status="<?= $row['is_active'] === 't' ? 'true' : 'false' ?>">
                                                            Edit
                                                        </button>
                                                        <!-- Delete Form -->
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this exam?')">
                                                            <input type="hidden" name="exam_id" value="<?= $row['exam_id'] ?>">
                                                            <button type="submit" name="delete_exam" class="btn btn-danger btn-sm">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Add/Edit Exam Modal -->
                            <div class="modal fade" id="examModal" tabindex="-1" aria-labelledby="examModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="examModalLabel">Add/Edit Exam</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form id="examForm" action="#" method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id" id="examId">

                                                <div class="mb-3">
                                                    <label for="examName" class="form-label">Exam Name</label>
                                                    <input type="text" class="form-control" id="examName" name="name" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="totalQuestions" class="form-label">Total Questions</label>
                                                    <input type="number" class="form-control" id="totalQuestions" name="total_questions" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="totalDuration" class="form-label">Total Duration (minutes)</label>
                                                    <input type="number" class="form-control" id="totalDuration" name="total_duration" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="categories" class="form-label">Categories</label>
                                                    <select class="form-select" id="categories" name="categories[]" multiple required>
                                                        <?php while ($cat = pg_fetch_assoc($categoriesResult)): ?>
                                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="status" class="form-label">Status</label>
                                                    <select class="form-select" id="status" name="status" required>
                                                        <option value="true">Active</option>
                                                        <option value="false">Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($result)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
    <script>
        document.querySelectorAll('.edit-exam').forEach(button => {
            button.addEventListener('click', function() {
                const examId = this.dataset.id;
                const examName = this.dataset.name;
                const totalQuestions = this.dataset.totalQuestions;
                const totalDuration = this.dataset.totalDuration;
                const status = this.dataset.status;
                // Get the categories as a comma-separated string (e.g., '1,3')
                const selectedCategories = this.dataset.categories.split(',').map(category => category.trim());

                document.getElementById('examId').value = examId;
                document.getElementById('examName').value = examName;
                document.getElementById('totalQuestions').value = totalQuestions;
                document.getElementById('totalDuration').value = totalDuration;
                // Set the status dropdown value based on is_active
                document.getElementById('status').value = status === 'true' ? 'true' : 'false';

                // Pre-select the categories in the dropdown
                const categoriesSelect = document.getElementById('categories');
                for (let option of categoriesSelect.options) {
                    // Check if the option's value is included in the selectedCategories array
                    if (selectedCategories.includes(option.value.trim())) {
                        option.selected = true; // Mark the option as selected
                    } else {
                        option.selected = false; // Otherwise, ensure it's not selected
                    }
                }

                // Show modal
                const examModal = new bootstrap.Modal(document.getElementById('examModal'));
                examModal.show();
            });
        });
    </script>
</body>

</html>