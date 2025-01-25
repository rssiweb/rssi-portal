<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Ensure the user is logged in
if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Fetch categories for the filter dropdown
$categoryQuery = "SELECT id, name FROM test_categories ORDER BY name";
$categoryResult = pg_query($con, $categoryQuery);
$categories = pg_fetch_all($categoryResult);

// Handle the form submission when editing a question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $questionId = $_POST['id'];
    $questionText = $_POST['question_text'];
    $categoryId = $_POST['category'];
    $correctOption = $_POST['correct_option'];
    $options = $_POST['options']; // Options array

    // Update the question text
    $updateQuery = "
        UPDATE test_questions
        SET question_text = $1, category_id = $2, correct_option = $3
        WHERE id = $4
    ";
    $result = pg_query_params($con, $updateQuery, array($questionText, $categoryId, $correctOption, $questionId));

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
                window.location.href = 'manage_questions.php'; // Redirect to the manage page
              </script>";
    } else {
        echo "<script>
                alert('Error updating question.');
              </script>";
    }
}

// Handle the delete request for a question
if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];
    $deleteQuery = "DELETE FROM test_questions WHERE id = $1";
    $deleteResult = pg_query_params($con, $deleteQuery, array($deleteId));

    if ($deleteResult) {
        echo "<script>
                alert('Question deleted successfully!');
                window.location.href = 'manage_questions.php'; // Redirect to the manage page
              </script>";
    } else {
        echo "<script>
                alert('Error deleting question.');
              </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Question Management</h1>

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
                    <input type="date" id="dateFromFilter" name="date_from" class="form-control" value="<?= isset($_GET['date_from']) ? $_GET['date_from'] : '' ?>">
                </div>
                <div class="col-md-4">
                    <label for="dateToFilter" class="form-label">Creation Date To</label>
                    <input type="date" id="dateToFilter" name="date_to" class="form-control" value="<?= isset($_GET['date_to']) ? $_GET['date_to'] : '' ?>">
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </div>
        </form>


        <!-- Add Question Button -->
        <div class="mb-4 text-end">
            <a href="addq.php" class="btn btn-success">Add New Question</a>
        </div>

        <!-- Question Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Question Text</th>
                        <th>Category</th>
                        <th>Correct Option</th>
                        <th>Options</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch questions and their options, grouping options together
                    $query = "
                SELECT q.id, q.question_text, q.correct_option, q.created_at, c.id AS category_id, c.name AS category_name
                FROM test_questions q
                LEFT JOIN test_categories c ON q.category_id = c.id
                ORDER BY q.created_at DESC
            ";
                    $result = pg_query($con, $query);
                    if (pg_num_rows($result) > 0) {
                        while ($row = pg_fetch_assoc($result)) {
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

                            echo "<tr>";
                            echo "<td>{$row['id']}</td>";
                            echo "<td>" . htmlspecialchars($row['question_text']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                            echo "<td>{$row['correct_option']}</td>";
                            echo "<td>{$optionsDisplay}</td>";
                            echo "<td>{$row['created_at']}</td>";
                            echo "<td>
                            <button class='btn btn-warning btn-sm' data-bs-toggle='modal' data-bs-target='#editModal' 
                                    data-id='{$row['id']}' data-question='" . htmlspecialchars($row['question_text']) . "' 
                                    data-category='{$row['category_id']}' data-correct='{$row['correct_option']}' 
                                    data-options='" . json_encode($options) . "'>
                                Edit
                            </button>
                            <a href='?delete_id={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                          </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>No questions found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="manage_questions.php">
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
    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
</body>

</html>