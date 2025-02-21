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
// Fetch categories from the database
$query = "SELECT id, name FROM test_categories WHERE is_active=true ORDER BY name";
$result = pg_query($con, $query);

// Check for errors
if (!$result) {
    die("Error fetching categories: " . pg_last_error($con));
}

$categories = [];
while ($row = pg_fetch_assoc($result)) {
    $categories[] = $row;
}
?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start a transaction
    pg_query($con, "BEGIN");

    try {
        $questionSets = $_POST['questionSets'];

        foreach ($questionSets as $set) {
            $category = $set['category'];
            $language = $set['language'];
            $questions = $set['questions'];

            foreach ($questions as $question) {
                $text = $question['text'];
                $correctOptionIndex = $question['correct']; // Correct option index (e.g., 1, 2, 3)

                // Convert the correct option index to its corresponding key (A, B, C, D)
                $correctOptionKey = chr(64 + $correctOptionIndex); // 1 -> A, 2 -> B, etc.

                // Insert the question into the database with the correct_option
                $query = "INSERT INTO test_questions (question_text, category_id, correct_option, created_by, q_language) VALUES ($1, $2, $3, $4, $5) RETURNING id";
                $result = pg_query_params($con, $query, array($text, $category, $correctOptionKey, $user_check, $language));
                if (!$result) {
                    throw new Exception("Error inserting question: " . pg_last_error($con));
                }

                $questionId = pg_fetch_result($result, 0, 'id');

                // Insert options into the database
                foreach ($question['options'] as $key => $option) {
                    $optionText = $option['text'];
                    $optionKey = chr(64 + $key); // Converts 1, 2, 3... to A, B, C...

                    $optionQuery = "INSERT INTO test_options (question_id, option_key, option_text) VALUES ($1, $2, $3)";
                    $insertOptionResult = pg_query_params($con, $optionQuery, array($questionId, $optionKey, $optionText));

                    if (!$insertOptionResult) {
                        throw new Exception("Error inserting option: " . pg_last_error($con));
                    }
                }
            }
        }

        // Commit the transaction if all queries succeed
        pg_query($con, "COMMIT");

        // Show success message
        echo "<script>
            alert('Questions have been saved.');
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            window.location.reload();
        </script>";
    } catch (Exception $e) {
        // Rollback the transaction if any query fails
        pg_query($con, "ROLLBACK");

        // Show error message
        echo "<script>
            alert('Error: " . addslashes($e->getMessage()) . "');
        </script>";
    }
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
    <title>Add Questions</title>
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
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Add Question</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">iExplore Edge</li>
                    <li class="breadcrumb-item active">Add Question</li>
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
                                <form id="questionsForm" method="POST" action="#">
                                    <div id="questionSetsContainer">
                                        <!-- Dynamic Question Sets will be added here -->
                                    </div>
                                    <button type="button" class="btn btn-primary mt-3" id="addQuestionSetBtn">+ Add Question Set</button>
                                    <button type="submit" class="btn btn-success mt-3" id="saveQuestionsBtn" style="display: none;">Save Questions</button>
                                </form>
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
        let questionSetCount = 0;
        let questionCount = 0;

        // Categories from PHP
        const categories = <?php echo json_encode($categories); ?>;

        // Add new question set
        document.getElementById('addQuestionSetBtn').addEventListener('click', () => {
            questionSetCount++;
            const categoryOptions = categories.map(category => `<option value="${category.id}">${category.name}</option>`).join('');
            const questionSetTemplate = `
            <div class="card mb-4" id="questionSet-${questionSetCount}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Question Set ${questionSetCount}</span>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestionSet(${questionSetCount})">Remove</button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="category-${questionSetCount}" class="form-label">Category</label>
                        <select class="form-select" id="category-${questionSetCount}" name="questionSets[${questionSetCount}][category]" required>
                            <option value="" selected disabled>Select a category</option>
                            ${categoryOptions}
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="language-${questionSetCount}" class="form-label">Language</label>
                        <select class="form-select" id="language-${questionSetCount}" name="questionSets[${questionSetCount}][language]" required>
                            <option value="" selected disabled>Select a language</option>
                            <option>Hindi</option>
                            <option>English</option>
                        </select>
                    </div>
                    <div id="questionsContainer-${questionSetCount}">
                        <!-- Dynamic Questions will be added here -->
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addQuestion(${questionSetCount})">+ Add Question</button>
                </div>
            </div>
        `;
            document.getElementById('questionSetsContainer').insertAdjacentHTML('beforeend', questionSetTemplate);
            addQuestion(questionSetCount); // Add the first question by default
            toggleSaveButton(); // Check visibility
        });

        // Remove question set
        function removeQuestionSet(questionSetId) {
            document.getElementById(`questionSet-${questionSetId}`).remove();

            toggleSaveButton(); // <-- Add this line
        }

        // Add new question to a question set
        function addQuestion(questionSetId) {
            toggleSaveButton(); // Check visibility
            questionCount++;
            const questionTemplate = `
            <div class="card mb-4" id="question-${questionCount}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Question ${questionCount}</span>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestion(${questionCount})">Remove</button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="questionText-${questionCount}" class="form-label">Question Text</label>
                        <input type="text" class="form-control" id="questionText-${questionCount}" name="questionSets[${questionSetId}][questions][${questionCount}][text]" placeholder="Enter the question" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Options</label>
                        <div id="optionsContainer-${questionCount}">
                            <!-- Dynamic Options will be added here -->
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addOption(${questionCount})">+ Add Option</button>
                    </div>
                </div>
            </div>
        `;
            document.getElementById(`questionsContainer-${questionSetId}`).insertAdjacentHTML('beforeend', questionTemplate);
            addOption(questionCount); // Add the first option by default
        }

        // Remove question
        function removeQuestion(questionId) {
            document.getElementById(`question-${questionId}`).remove();

            toggleSaveButton(); // <-- Add this line
        }

        // Toggle visibility of the "Save Questions" button
        function toggleSaveButton() {
            const questionSetsContainer = document.getElementById('questionSetsContainer');
            const saveButton = document.getElementById('saveQuestionsBtn');
            if (questionSetsContainer.children.length > 0) {
                saveButton.style.display = 'inline-block'; // Show the button
            } else {
                saveButton.style.display = 'none'; // Hide the button
            }
        }

        // Add new option to a question
        function addOption(questionId) {
            const optionContainer = document.getElementById(`optionsContainer-${questionId}`);
            const optionCount = optionContainer.childElementCount + 1;
            const optionTemplate = `
            <div class="input-group mb-2" id="question-${questionId}-option-${optionCount}">
                <input type="text" class="form-control" name="questionSets[${questionSetCount}][questions][${questionId}][options][${optionCount}][text]" placeholder="Enter option ${optionCount}" required>
                <label class="input-group-text" style="cursor: pointer;">
                    <input style="cursor: pointer;" type="radio" name="questionSets[${questionSetCount}][questions][${questionId}][correct]" value="${optionCount}" required>
                </label>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeOption(${questionId}, ${optionCount})">Remove</button>
            </div>
        `;
            optionContainer.insertAdjacentHTML('beforeend', optionTemplate);
        }

        // Remove option
        function removeOption(questionId, optionId) {
            document.getElementById(`question-${questionId}-option-${optionId}`).remove();
        }
    </script>
</body>

</html>