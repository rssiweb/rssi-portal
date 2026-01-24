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
?>
<?php
// After fetching the exam details, add the question counts to the dataset
$query = "
    SELECT te.id AS exam_id, te.name AS exam_name, te.total_questions, te.total_duration, te.is_active, te.created_at, te.language, te.show_answer, te.is_restricted, te.is_paid, te.course_id,
           STRING_AGG(tc.name, ', ') AS categories, 
           STRING_AGG(tc.id::text, ', ') AS category_ids, -- Fetch category IDs as a comma-separated string
           STRING_AGG(tec.question_count::text, ', ') AS question_counts -- Fetch question counts as a comma-separated string
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

// Fetch distinct languages for the language dropdown
$languageQuery = "SELECT DISTINCT q_language FROM test_questions";
$languageResult = pg_query($con, $languageQuery);
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
    // Sanitize and validate input data
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $name = pg_escape_string($con, $_POST['name']);
    @$courseId = pg_escape_string($con, $_POST['courseId']);
    $total_questions = intval($_POST['total_questions']);
    $total_duration = intval($_POST['total_duration']);
    $categories = $_POST['categories']; // Array of category IDs
    $question_counts = $_POST['question_count']; // Array of question counts for each category
    $status = ($_POST['status'] === 'true') ? true : false; // Convert to boolean

    // Handle multi-select language (convert array to comma-separated string)
    $languages = isset($_POST['language']) ? $_POST['language'] : []; // Array of selected languages
    $languageString = pg_escape_string($con, implode(',', $languages)); // Convert array to string

    // Handle checkboxes: Set to false if not present in $_POST
    $show_answer = isset($_POST['show_answer']) && $_POST['show_answer'] == '1' ? 'true' : 'false';
    $is_restricted = isset($_POST['is_restricted']) && $_POST['is_restricted'] == '1' ? 'true' : 'false';
    $is_paid = isset($_POST['is_paid']) && $_POST['is_paid'] == '1' ? 'true' : 'false';

    if ($id) {
        // Update existing exam
        $updateExamQuery = "
            UPDATE test_exams
            SET 
                name = $1, 
                total_questions = $2, 
                total_duration = $3, 
                is_active = $4, 
                language = $5, 
                show_answer = $6, 
                is_restricted = $7, 
                is_paid = $8,
                course_id = $10,
                created_at = CURRENT_TIMESTAMP
            WHERE id = $9
        ";

        $updateExamResult = pg_query_params($con, $updateExamQuery, [
            $name,
            $total_questions,
            $total_duration,
            $status,
            $languageString, // Store multi-select languages as a string
            $show_answer,
            $is_restricted,
            $is_paid,
            $id,
            $courseId
        ]);

        if (!$updateExamResult) {
            die("Error updating exam: " . pg_last_error($con));
        }

        // Clear existing categories
        $deleteCategoriesQuery = "DELETE FROM test_exam_categories WHERE exam_id = $1";
        pg_query_params($con, $deleteCategoriesQuery, [$id]);
    } else {
        // Insert new exam
        $insertExamQuery = "
            INSERT INTO test_exams (
                name, 
                total_questions, 
                total_duration, 
                is_active, 
                language, 
                show_answer, 
                is_restricted, 
                is_paid, 
                created_at,
                course_id
            )
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, CURRENT_TIMESTAMP,$9)
            RETURNING id
        ";

        $insertExamResult = pg_query_params($con, $insertExamQuery, [
            $name,
            $total_questions,
            $total_duration,
            $status,
            $languageString, // Store multi-select languages as a string
            $show_answer,
            $is_restricted,
            $is_paid,
            $courseId
        ]);

        if (!$insertExamResult) {
            die("Error inserting exam: " . pg_last_error($con));
        }

        $row = pg_fetch_assoc($insertExamResult);
        $id = $row['id'];
    }

    // Insert categories with question counts
    foreach ($categories as $category_id) {
        $question_count = isset($question_counts[$category_id]) ? intval($question_counts[$category_id]) : 0;
        $insertCategoryQuery = "INSERT INTO test_exam_categories (exam_id, category_id, question_count) VALUES ($1, $2, $3)";
        pg_query_params($con, $insertCategoryQuery, [$id, $category_id, $question_count]);
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
    <style>
        .form-switch .form-check-input {
            width: 3em;
            /* Adjust width */
            height: 1.5em;
            /* Adjust height */
            margin-left: 10px;
            /* Add some spacing */
        }

        /* Custom CSS for better alignment */
        .form-check.form-switch {
            display: flex;
            align-items: center;
        }

        .form-check-input {
            margin-right: 0.5rem;
            /* Adjust spacing between switch and label */
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
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
                                                <th>Course ID</th>
                                                <th>Name</th>
                                                <th>Total Questions</th>
                                                <th>Categories</th>
                                                <th>Total Duration (min)</th>
                                                <th>Status</th>
                                                <th>language</th>
                                                <th>Show Ans.</th>
                                                <th>Res.</th>
                                                <th>Paid</th>
                                                <th>Created At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = pg_fetch_assoc($result)): ?>
                                                <tr>
                                                    <td><?= $row['exam_id'] ?></td>
                                                    <td><?= $row['course_id'] ?></td>
                                                    <td><?= $row['exam_name'] ?></td>
                                                    <td><?= $row['total_questions'] ?></td>
                                                    <td><?= $row['categories'] ?></td>
                                                    <td><?= $row['total_duration'] ?></td>
                                                    <td><?= $row['is_active'] === 't' ? 'Active' : 'Inactive' ?></td>
                                                    <td><?= $row['language'] ?></td>
                                                    <td><?= $row['show_answer'] ?></td>
                                                    <td><?= $row['is_restricted'] ?></td>
                                                    <td><?= $row['is_paid'] ?></td>
                                                    <td><?= date("d/m/Y h:i A", strtotime($row['created_at'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-warning btn-sm me-2 edit-exam"
                                                            data-id="<?= $row['exam_id'] ?>"
                                                            data-name="<?= htmlspecialchars($row['exam_name']) ?>"
                                                            data-course_id="<?= $row['course_id'] ?>"
                                                            data-total-questions="<?= $row['total_questions'] ?>"
                                                            data-total-duration="<?= $row['total_duration'] ?>"
                                                            data-categories="<?= htmlspecialchars($row['category_ids'] ?? '') ?>"
                                                            data-question-counts="<?= htmlspecialchars($row['question_counts'] ?? '') ?>"
                                                            data-status="<?= $row['is_active'] === 't' ? 'true' : 'false' ?>"
                                                            data-language="<?= htmlspecialchars($row['language'] ?? '') ?>"
                                                            data-show-answer="<?= $row['show_answer'] === 't' ? 'true' : 'false' ?>"
                                                            data-is-restricted="<?= $row['is_restricted'] === 't' ? 'true' : 'false' ?>"
                                                            data-is-paid="<?= $row['is_paid'] === 't' ? 'true' : 'false' ?>">
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
    <!-- Add/Edit Exam Modal -->
    <div class="modal fade show" id="examModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="examModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="examModalLabel">Add/Edit Exam</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="examForm" action="#" method="POST">
                    <div class="modal-body">
                        <!-- Toggle Switch Button -->
                        <div class="d-flex justify-content-end align-items-center mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="editToggle">
                                <label class="form-check-label" for="editToggle">Edit Mode</label>
                            </div>
                        </div>
                        <input type="hidden" name="id" id="examId">

                        <!-- Exam Name -->
                        <div class="mb-3">
                            <label for="examName" class="form-label">Exam Name</label>
                            <input type="text" class="form-control" id="examName" name="name" required>
                        </div>

                        <!-- Course ID (initially disabled and not required) -->
                        <div class="mb-3">
                            <label for="courseId" class="form-label">Course ID</label>
                            <input type="text" class="form-control" id="courseId" name="courseId">
                            <div class="form-text">
                                Enter the Course/WBT ID to link it with iExplore Learner.
                            </div>
                        </div>

                        <!-- Total Questions -->
                        <div class="mb-3">
                            <label for="totalQuestions" class="form-label">Total Questions</label>
                            <input type="number" class="form-control" id="totalQuestions" name="total_questions" required>
                        </div>

                        <!-- Total Duration -->
                        <div class="mb-3">
                            <label for="totalDuration" class="form-label">Total Duration (minutes)</label>
                            <input type="number" class="form-control" id="totalDuration" name="total_duration" required>
                        </div>

                        <!-- Categories -->
                        <div class="mb-3">
                            <label for="categories" class="form-label">Categories</label>
                            <select class="form-select" id="categories" name="categories[]" multiple required>
                                <?php while ($cat = pg_fetch_assoc($categoriesResult)): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Informational Block for Category-wise Question Counts -->
                        <div id="infoBlock" class="mb-3">
                            <h6>Question count by category</h6>
                            <ul id="infoList"></ul>
                        </div>

                        <!-- Question Count per Category -->
                        <div id="questionCountContainer" class="mb-3">
                            <!-- Dynamically generated question count inputs will go here -->
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="true">Active</option>
                                <option value="false">Inactive</option>
                            </select>
                        </div>

                        <!-- Language (Multi-Select Dropdown) -->
                        <div class="mb-3">
                            <label for="language" class="form-label">Language</label>
                            <select class="form-select" id="language" name="language[]" multiple required>
                                <?php
                                $languageQuery = "SELECT DISTINCT q_language FROM test_questions";
                                $languageResult = pg_query($con, $languageQuery);
                                while ($lang = pg_fetch_assoc($languageResult)) {
                                    echo '<option value="' . htmlspecialchars($lang['q_language']) . '">' . htmlspecialchars($lang['q_language']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Show Answer (Boolean) -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="showAnswer" name="show_answer" value='1'>
                            <label class="form-check-label" for="showAnswer">Show Answers After Exam</label>
                        </div>

                        <!-- Is Restricted (Boolean) -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="isRestricted" name="is_restricted" value='1'>
                            <label class="form-check-label" for="isRestricted">Restrict Exam Access</label>
                        </div>

                        <!-- Is Paid (Boolean) -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="isPaid" name="is_paid" value='1'>
                            <label class="form-check-label" for="isPaid">Paid Exam</label>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const examModal = new bootstrap.Modal(document.getElementById('examModal'));
            const addExamBtn = document.getElementById('addExamBtn');
            const categoriesSelect = document.getElementById('categories');
            const questionCountContainer = document.getElementById('questionCountContainer');
            const infoBlock = document.getElementById('infoBlock');
            const infoList = document.getElementById('infoList');

            // Function to generate question count inputs
            function generateQuestionCountInputs(questionCounts = {}) {
                questionCountContainer.innerHTML = ''; // Clear existing inputs
                const selectedCategories = Array.from(categoriesSelect.selectedOptions).map(option => option.value);

                selectedCategories.forEach(categoryId => {
                    const categoryName = categoriesSelect.querySelector(`option[value="${categoryId}"]`).textContent;
                    const count = questionCounts[categoryId] || 0; // Use the provided count or default to 0

                    const inputGroup = document.createElement('div');
                    inputGroup.className = 'mb-3';

                    const label = document.createElement('label');
                    label.className = 'form-label';
                    label.textContent = `Number of Questions for ${categoryName}`;
                    inputGroup.appendChild(label);

                    const input = document.createElement('input');
                    input.type = 'number';
                    input.className = 'form-control';
                    input.name = `question_count[${categoryId}]`;
                    input.required = true;
                    input.min = 0;
                    input.value = count;
                    inputGroup.appendChild(input);

                    questionCountContainer.appendChild(inputGroup);
                });
            }

            // Function to populate informational block
            function populateInfoBlock(questionCounts = {}) {
                infoList.innerHTML = ''; // Clear existing info
                for (const [categoryId, count] of Object.entries(questionCounts)) {
                    const categoryName = categoriesSelect.querySelector(`option[value="${categoryId}"]`).textContent;
                    const listItem = document.createElement('li');
                    listItem.textContent = `${categoryName}: ${count} questions`;
                    infoList.appendChild(listItem);
                }
            }

            // Handle Add Exam Button Click
            addExamBtn.addEventListener("click", function() {
                // Reset form fields
                document.getElementById('examForm').reset(); // Reset all input fields
                document.getElementById('examId').value = ''; // Ensure exam ID is empty

                // Reset category and language selections
                categoriesSelect.querySelectorAll('option').forEach(option => option.selected = false);
                document.getElementById('language').querySelectorAll('option').forEach(option => option.selected = false);

                // Clear question count inputs and info block
                questionCountContainer.innerHTML = '';
                infoList.innerHTML = '';

                // Open modal
                examModal.show();
            });

            // Handle Category Selection Change
            categoriesSelect.addEventListener('change', function() {
                generateQuestionCountInputs();
            });

            // Handle Edit Exam Button Click
            document.querySelectorAll('.edit-exam').forEach(button => {
                button.addEventListener('click', function() {
                    const examId = this.dataset.id;
                    const examName = this.dataset.name;
                    const courseId = this.dataset.course_id;
                    const totalQuestions = this.dataset.totalQuestions;
                    const totalDuration = this.dataset.totalDuration;
                    const status = this.dataset.status;
                    const showAnswer = this.dataset.showAnswer === 'true';
                    const isRestricted = this.dataset.isRestricted === 'true';
                    const isPaid = this.dataset.isPaid === 'true';

                    // Get selected categories and question counts
                    const selectedCategories = this.dataset.categories.split(',').map(category => category.trim());
                    const questionCounts = this.dataset.questionCounts.split(',').reduce((acc, count, index) => {
                        acc[selectedCategories[index]] = count.trim();
                        return acc;
                    }, {});
                    const selectedLanguages = this.dataset.language ? this.dataset.language.split(',').map(lang => lang.trim()) : [];

                    document.getElementById('examId').value = examId;
                    document.getElementById('examName').value = examName;
                    document.getElementById('courseId').value = courseId;
                    document.getElementById('totalQuestions').value = totalQuestions;
                    document.getElementById('totalDuration').value = totalDuration;
                    document.getElementById('status').value = status === 'true' ? 'true' : 'false';

                    // Set language selections
                    const languageSelect = document.getElementById('language');
                    for (let option of languageSelect.options) {
                        option.selected = selectedLanguages.includes(option.value.trim());
                    }

                    // Set checkboxes
                    document.getElementById('showAnswer').checked = showAnswer;
                    document.getElementById('isRestricted').checked = isRestricted;
                    document.getElementById('isPaid').checked = isPaid;

                    // Set category selections
                    for (let option of categoriesSelect.options) {
                        option.selected = selectedCategories.includes(option.value.trim());
                    }

                    // Generate question count inputs for selected categories with existing data
                    generateQuestionCountInputs(questionCounts);

                    // Populate informational block with existing data
                    populateInfoBlock(questionCounts);

                    // Show modal
                    examModal.show();
                });
            });

            // Validate total questions and sum of questions per category before form submission
            document.getElementById('examForm').addEventListener('submit', function(event) {
                const totalQuestions = parseInt(document.getElementById('totalQuestions').value, 10);
                const questionCounts = Array.from(document.querySelectorAll('input[name^="question_count"]'))
                    .map(input => parseInt(input.value, 10))
                    .reduce((sum, count) => sum + count, 0);

                if (questionCounts !== totalQuestions) {
                    event.preventDefault();
                    alert('The total number of questions does not match the sum of questions per category.');
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editToggle = document.getElementById('editToggle');
            const examForm = document.getElementById('examForm');
            const formElements = examForm.querySelectorAll('input, select, textarea, button');
            const questionCountContainer = document.getElementById('questionCountContainer');

            // Function to enable/disable form elements
            function toggleFormEditable(isEditable) {
                formElements.forEach(element => {
                    if (element !== editToggle) { // Exclude the toggle switch itself
                        element.disabled = !isEditable;
                    }
                });

                // Handle dynamically generated inputs inside questionCountContainer
                const dynamicInputs = questionCountContainer.querySelectorAll('input, select, textarea');
                dynamicInputs.forEach(input => {
                    input.disabled = !isEditable;
                });
            }

            // Initial state: form is disabled
            toggleFormEditable(false);

            // Add event listener to the toggle switch
            editToggle.addEventListener('change', function() {
                toggleFormEditable(this.checked);
            });

            // Observer to watch for changes in the questionCountContainer
            const observer = new MutationObserver(function(mutationsList) {
                mutationsList.forEach(mutation => {
                    if (mutation.type === 'childList') {
                        // If new inputs are added, disable/enable them based on the toggle state
                        const dynamicInputs = questionCountContainer.querySelectorAll('input, select, textarea');
                        dynamicInputs.forEach(input => {
                            input.disabled = !editToggle.checked;
                        });
                    }
                });
            });

            // Start observing the questionCountContainer for changes
            observer.observe(questionCountContainer, {
                childList: true,
                subtree: true
            });
        });
    </script>
</body>

</html>