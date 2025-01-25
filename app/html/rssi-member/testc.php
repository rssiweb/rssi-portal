<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Ensure the user is logged in
if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Get the exam_id from the query string
$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;

// If no exam_id is provided, set a flag to show the form
$show_form = !$exam_id;
// If exam_id is provided, proceed with fetching exam details
if (!$show_form) {
    // Step 1: Fetch the total_questions for the exam
    $query = "
    SELECT total_questions
    FROM test_exams
    WHERE id = $1
";
    $exam_result = pg_query_params($con, $query, array($exam_id));

    // If the exam doesn't exist, handle the error
    if ($exam_row = pg_fetch_assoc($exam_result)) {
        $total_questions = $exam_row['total_questions'];
    } else {
        echo "Error: Exam not found.";
        exit;
    }

    // Step 2: Fetch the categories associated with the exam
    $query = "
    SELECT category_id
    FROM test_exam_categories
    WHERE exam_id = $1
";
    $exam_categories_result = pg_query_params($con, $query, array($exam_id));

    // Initialize an array to store the category IDs
    $category_ids = array();
    while ($row = pg_fetch_assoc($exam_categories_result)) {
        $category_ids[] = $row['category_id'];
    }

    // If no categories are found for the exam, handle the error
    if (empty($category_ids)) {
        echo "Error: No categories found for the exam.";
        exit;
    }

    // Step 3: Fetch random questions based on the categories and total_questions
    $category_ids_str = implode(",", $category_ids);

    $question_query = "
    WITH random_questions AS (
        SELECT id AS question_id, question_text
        FROM test_questions
        WHERE category_id IN ($category_ids_str)
        ORDER BY RANDOM()
        LIMIT $1
    )
    SELECT rq.question_id, rq.question_text, 
           o.option_key, o.option_text
    FROM random_questions rq
    JOIN test_options o ON rq.question_id = o.question_id
    ORDER BY rq.question_id, o.option_key
";

    $result = pg_query_params($con, $question_query, array($total_questions));

    // Initialize an array to store the questions by their ID
    $questions = array();
    while ($row = pg_fetch_assoc($result)) {
        // Store the question data
        if (!isset($questions[$row['question_id']])) {
            $questions[$row['question_id']] = [
                'question_text' => $row['question_text'],
                'options' => []
            ];
        }

        // Add options to the question
        $questions[$row['question_id']]['options'][] = [
            'option_key' => $row['option_key'],
            'option_text' => $row['option_text']
        ];
    }
}
// HTML output starts here
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <!-- <div id="exam-header" class="mb-4 text-center">
            <h1>Welcome to the Exam</h1>
            <p class="text-muted">Answer the questions below and submit to see your result.</p>
        </div> -->
        <?php if ($show_form): ?>
            <!-- Show the form if no exam_id is provided -->
            <h4 class="mb-3">Enter Exam ID</h4>
            <form method="GET" action="">
                <div class="input-group mb-3">
                    <input type="text" name="exam_id" class="form-control" placeholder="Enter Exam ID" required>
                    <button class="btn btn-primary" type="submit">Submit</button>
                </div>
            </form>
        <?php else: ?>
            <!-- Show the exam content if exam_id is provided -->
            <div id="exam-header" class="mb-4 text-center">
                <h1>Welcome to the Exam</h1>
                <p class="text-muted">Answer the questions below and submit to see your result.</p>
            </div>
            <p>Your exam ID: <?= htmlspecialchars($exam_id) ?></p>
            <!-- Additional exam content goes here -->

            <form id="exam-form">
                <div id="questions-container" class="mb-4">
                    <?php
                    $questionNumber = 1; // This is used for displaying the question number to the user, not for the question_id.
                    foreach ($questions as $questionId => $question) {
                        echo "<div class='mb-4'>";
                        echo "<p>" . $questionNumber . ". " . $question['question_text'] . "</p>";
                        foreach ($question['options'] as $option) {
                            // Use questionId from the database for name and value, not the question number.
                            echo "<div class='form-check'>";
                            echo "<input type='radio' class='form-check-input' name='question-" . $questionId . "' value='" . $option['option_key'] . "'>";
                            echo "<label class='form-check-label'>" . $option['option_text'] . "</label>";
                            echo "</div>";
                        }
                        echo "</div>";
                        $questionNumber++; // This is only for user-friendly numbering
                    }
                    ?>
                </div>
                <div class="text-center">
                    <button type="button" id="submit-exam" class="btn btn-primary">Submit Exam</button>
                </div>
            </form>
        <?php endif; ?>
        <!-- Result Section -->
        <div id="result-container" class="mt-5 d-none">
            <h3>Your Result</h3>
            <p id="score" class="fs-5"></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const examId = <?php echo $exam_id; ?>; // Use PHP to inject the exam ID
            const userId = "<?php echo $user_check; ?>"; // Use PHP to inject the logged-in user's ID as a string

            // Handle Exam Submission
            document.getElementById('submit-exam').addEventListener('click', () => {
                const formData = new FormData(document.getElementById('exam-form'));

                // Collect answers
                const answers = [];
                formData.forEach((value, key) => {
                    const questionId = key.split('-')[1]; // Get question number
                    answers.push({
                        question_id: questionId,
                        selected_option: value
                    });
                });

                // Send answers to the server
                fetch('submit-answers.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            exam_id: examId,
                            answers: answers,
                            form_type: 'exam'
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.score !== undefined) {
                            // Show score
                            document.getElementById('exam-form').classList.add('d-none');
                            document.getElementById('result-container').classList.remove('d-none');
                            document.getElementById('score').textContent = `Your score is: ${result.score}`;
                        }
                    })
                    .catch(error => {
                        console.error('Error submitting answers:', error);
                    });
            });
        });
    </script>
</body>

</html>