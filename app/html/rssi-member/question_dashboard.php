<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Ensure the user is logged in
if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}
validation();
// Fetch categories for the filter dropdown
$categoryQuery = "SELECT id, name FROM test_categories WHERE is_active=true ORDER BY name";
$categoryResult = pg_query($con, $categoryQuery);
$categories = pg_fetch_all($categoryResult);

// Handle the form submission when editing a question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $questionId = $_POST['id'];
    $questionText = $_POST['question_text'];
    $categoryId = $_POST['category'];
    $correctOption = $_POST['correct_option'];
    $options = $_POST['options']; // Options array
    $modifiedAt = date('Y-m-d H:i:s');

    // Update the question text
    $updateQuery = "
        UPDATE test_questions
        SET question_text = $1, category_id = $2, correct_option = $3, created_at=$5, created_by=$6
        WHERE id = $4
    ";
    $result = pg_query_params($con, $updateQuery, array($questionText, $categoryId, $correctOption, $questionId, $modifiedAt, $associatenumber));

    if ($result) {
        // Update the options
        foreach ($options as $key => $optionText) {
            $updateOptionQuery = "
                UPDATE test_options
                SET option_text = $1
                WHERE question_id = $2 AND option_key = $3
            ";
            pg_query_params($con, $updateOptionQuery, array($optionText, $questionId, $key));
        }

        echo "<script>
                alert('Question and options updated successfully!');
                if (window.history.replaceState) {
                        // Update the URL without causing a page reload or resubmission
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload(); // Trigger a page reload to reflect changes
              </script>";
    } else {
        echo "<script>
                alert('Error updating question.');
              </script>";
    }
}

// Handle the delete request for a question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];
    $deleteQuery = "DELETE FROM test_questions WHERE id = $1";
    $deleteResult = pg_query_params($con, $deleteQuery, array($deleteId));

    if ($deleteResult) {
        echo "<script>
                alert('Question deleted successfully!');
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.href);
                }
                window.location.reload();
              </script>";
    } else {
        echo "<script>
                alert('Error deleting question.');
              </script>";
    }
}
?>
<?php
// Get current date
$currentDate = date('Y-m-d'); // Today's date (e.g., 2025-01-25)

// Default to the last 7 days if no date is selected
if (empty($_GET['date_from']) && empty($_GET['date_to'])) {
    $dateFromFilter = date('Y-m-d', strtotime('-7 days', strtotime($currentDate))); // 7 days ago (e.g., 2025-01-18)
    $dateToFilter = $currentDate; // Today (e.g., 2025-01-25)
} else {
    $dateFromFilter = $_GET['date_from'] ?? $currentDate;
    $dateToFilter = $_GET['date_to'] ?? $currentDate;
}

// Capture category filter
$categoryFilter = $_GET['category'] ?? '';

// Build the WHERE clause based on filters
$whereClauses = [];
if ($categoryFilter) {
    $whereClauses[] = "q.category_id = '$categoryFilter'";
}

// Include both the start and end date with exact time
if ($dateFromFilter && $dateToFilter) {
    // Ensure the entire start day is included (from the beginning of the day)
    $whereClauses[] = "q.created_at >= '$dateFromFilter 00:00:00' AND q.created_at <= '$dateToFilter 23:59:59.999999'";
}

// Combine the WHERE clauses (if any) and adjust the query
$whereSql = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Query to fetch data based on the filters
$query = "
    SELECT q.id, q.question_text, q.correct_option, q.created_at, q.created_by, c.id AS category_id, c.name AS category_name
    FROM test_questions q
    LEFT JOIN test_categories c ON q.category_id = c.id
    $whereSql
    AND q.id>2522
    ORDER BY q.created_at DESC
";

$result = pg_query($con, $query);
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
    <title>Question Dashboard</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Question Dashboard</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">iExplore Edge</li>
                    <li class="breadcrumb-item active">Question Dashboard</li>
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
                            <div class="container mt-5">
                                <!-- <h1 class="mb-4">Question Management</h1> -->

                                <!-- Filter Section -->
                                <form method="GET" id="filterForm" class="mb-4">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="categoryFilter" class="form-label">Category</label>
                                            <select id="categoryFilter" name="category" class="form-select">
                                                <option value="">All Categories</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?= $category['id'] ?>" <?= isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($category['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="dateFromFilter" class="form-label">Creation Date From</label>
                                            <input type="date" id="dateFromFilter" name="date_from" class="form-control" value="<?= isset($_GET['date_from']) ? $_GET['date_from'] : $dateFromFilter ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="dateToFilter" class="form-label">Creation Date To</label>
                                            <input type="date" id="dateToFilter" name="date_to" class="form-control" value="<?= isset($_GET['date_to']) ? $_GET['date_to'] : $dateToFilter ?>">
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    </div>
                                </form>

                                <!-- Question Table -->
                                <div class="table-responsive">
                                    <table class="table" id="table-id">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Question Text</th>
                                                <th>Category</th>
                                                <th>Correct Option</th>
                                                <th>Options</th>
                                                <th>Last Updated</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (pg_num_rows($result) > 0): ?>
                                                <?php while ($row = pg_fetch_assoc($result)):
                                                    // Fetch options for each question
                                                    $optionsQuery = "SELECT option_key, option_text FROM test_options WHERE question_id = $1 ORDER BY option_key";
                                                    $optionsResult = pg_query_params($con, $optionsQuery, array($row['id']));
                                                    $options = [];
                                                    while ($optionRow = pg_fetch_assoc($optionsResult)) {
                                                        $options[] = $optionRow;
                                                    }

                                                    // Prepare the options display
                                                    $optionsDisplay = '';
                                                    foreach ($options as $option) {
                                                        $optionsDisplay .= "{$option['option_key']}: {$option['option_text']}<br>";
                                                    }
                                                ?>
                                                    <tr>
                                                        <td><?= $row['id'] ?></td>
                                                        <td><?= htmlspecialchars($row['question_text']) ?></td>
                                                        <td><?= htmlspecialchars($row['category_name']) ?></td>
                                                        <td><?= $row['correct_option'] ?></td>
                                                        <td><?= $optionsDisplay ?></td>
                                                        <td><?= (new DateTime($row['created_at']))->format('d/m/Y h:i A') ?> by <?= $row['created_by'] ?></td>
                                                        <td>
                                                            <?php if ($role === 'Admin' || $row['created_by'] === $associatenumber): ?>
                                                                <!-- Edit button -->
                                                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal"
                                                                    data-id="<?= $row['id'] ?>" data-question="<?= htmlspecialchars($row['question_text']) ?>"
                                                                    data-category="<?= $row['category_id'] ?>" data-correct="<?= $row['correct_option'] ?>"
                                                                    data-options='<?= json_encode($options) ?>'>
                                                                    Edit
                                                                </button>
                                                            <?php endif; ?>

                                                            <?php if ($role === 'Admin'): ?>
                                                                <!-- Delete button for Admin only -->
                                                                <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this question?');" style="display:inline;">
                                                                    <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                                                    <button type="submit" class="btn btn-danger btn-sm mt-2">Delete</button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <p>No questions found.</p>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="#">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Question</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editQuestionId">
                        <div class="mb-3">
                            <label for="editQuestionText" class="form-label">Question Text</label>
                            <textarea class="form-control" id="editQuestionText" name="question_text" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editCategory" class="form-label">Category</label>
                            <select id="editCategory" name="category" class="form-select" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editCorrectOption" class="form-label">Correct Option</label>
                            <input type="text" class="form-control" id="editCorrectOption" name="correct_option" required>
                        </div>
                        <div id="editOptionsContainer">
                            <!-- Dynamic options will be added here -->
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

    <script>
        // Modal handling for editing questions
        const editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const question = button.getAttribute('data-question');
            const category = button.getAttribute('data-category');
            const correct = button.getAttribute('data-correct');
            const options = JSON.parse(button.getAttribute('data-options')); // Parse the options JSON

            // Set values for question and other fields
            document.getElementById('editQuestionId').value = id;
            document.getElementById('editQuestionText').value = question;
            document.getElementById('editCategory').value = category;
            document.getElementById('editCorrectOption').value = correct;

            // Clear the options container
            const optionsContainer = document.getElementById('editOptionsContainer');
            optionsContainer.innerHTML = ''; // Clear any previous options

            // Add option fields dynamically
            options.forEach((option, index) => {
                const optionDiv = document.createElement('div');
                optionDiv.classList.add('mb-3');
                optionDiv.innerHTML = `
            <label for="editOption${index}" class="form-label">Option ${option.option_key}</label>
            <input type="text" class="form-control" id="editOption${index}" name="options[${option.option_key}]" value="${option.option_text}" required>
        `;
                optionsContainer.appendChild(optionDiv);
            });
        });
    </script>
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
</body>

</html>