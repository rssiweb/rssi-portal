<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_iexplore.php");

// Ensure the user is logged in
if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

// Get the exam_id from the query string
$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;

// If no exam_id is provided, set a flag to show the form
$show_form = !$exam_id;

// If exam_id is provided, proceed with fetching exam details
if (!$show_form) {
    // Step 1: Insert a new row in the test_user_exams table to track the user's exam participation
    $user_exam_query = "
    INSERT INTO test_user_exams (user_id, exam_id)
    VALUES ($1, $2)
    RETURNING id";  // Get the user_exam_id for further use
    $user_exam_result = pg_query_params($con, $user_exam_query, array($id, $exam_id));

    if (!$user_exam_result) {
        // Handle query failure
        echo "Error inserting user exam: " . pg_last_error($con);
        exit;
    }

    // Retrieve the user_exam_id
    $user_exam_row = pg_fetch_assoc($user_exam_result);
    $user_exam_id = $user_exam_row['id'];

    // Step 2: Insert a new row in the test_user_sessions table to track the session
    $session_query = "
    INSERT INTO test_user_sessions (user_exam_id, session_start, status)
    VALUES ($1, NOW(), 'active')
    RETURNING id";  // Get the session ID
    $session_result = pg_query_params($con, $session_query, array($user_exam_id));

    if (!$session_result) {
        // Handle query failure
        echo "Error inserting session: " . pg_last_error($con);
        exit;
    }

    // Retrieve the session ID
    $session_row = pg_fetch_assoc($session_result);
    $session_id = $session_row['id'];

    // Step 3: Fetch the total_questions for the exam
    $query = "
    SELECT total_questions, total_duration
    FROM test_exams
    WHERE id = $1
    ";
    $exam_result = pg_query_params($con, $query, array($exam_id));

    // If the exam doesn't exist, handle the error
    if ($exam_row = pg_fetch_assoc($exam_result)) {
        $total_questions = $exam_row['total_questions'];
        $total_duration = $exam_row['total_duration'];
    } else {
        echo "Error: Exam not found.";
        exit;
    }

    // Step 4: Fetch the categories associated with the exam
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

    // Step 5: Fetch random questions based on the categories and total_questions
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
    <title>Online Examination Portal</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
        }

        body {
            /* background-color: #f8f9fa; */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .exam-container {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
            max-width: 1400px;
            margin: 2rem auto;
            padding: 1rem;
        }

        .question-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            min-height: 60vh;
        }

        .status-sidebar {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }

        .question-nav {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .question-number {
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
        }

        .question-text {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .option-label {
            display: block;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .option-label:hover {
            background-color: #f8f9fa;
        }

        .option-input:checked+.option-label {
            border-color: var(--accent-color);
            background-color: #e8f4ff;
        }

        .status-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
        }

        .status-item:hover {
            background-color: #f8f9fa;
        }

        .status-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 1rem;
            border: 2px solid #ddd;
        }

        .timer-container {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="exam-container" id="exam-form">
        <?php if ($show_form): ?>
            <!-- Show the form if no exam_id is provided -->
            <form method="GET" action="">
                <label for="exam_id" class="form-label">Exam ID:</label>
                <div class="input-group mb-3">
                    <input type="text" name="exam_id" class="form-control" placeholder="Enter Exam ID" required>
                    <button class="btn btn-primary" type="submit">Submit</button>
                </div>
            </form>
        <?php else: ?>
            <!-- Main Question Area -->
            <div class="question-area">
                <div class="d-flex justify-content-end">
                    <div id="timer-container" style="font-size: 18px;">
                        <!-- Timer content goes here -->
                    </div>
                </div>

                <div id="questions-wrapper">
                    <?php
                    $sequenceNumber = 1; // Initialize the sequence number
                    foreach ($questions as $questionId => $question): ?>
                        <div class="question-container" data-question-id="<?= $questionId ?>" <?= $sequenceNumber > 1 ? 'style="display:none;"' : '' ?>>
                            <!-- Display the sequence number (e.g., "1 of 5") -->
                            <div class="question-number">Question <?= $sequenceNumber ?> of <?= count($questions) ?></div>
                            <div class="question-text"><?= $question['question_text'] ?></div>

                            <!-- Display options for the question -->
                            <div class="options-container">
                                <?php foreach ($question['options'] as $option): ?>
                                    <label class="option-label">
                                        <input type="radio" class="option-input" name="question-<?= $questionId ?>"
                                            value="<?= $option['option_key'] ?>">
                                        <?= $option['option_text'] ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="user_exam_id" value="<?php echo $user_exam_id; ?>" />

                            <!-- Navigation buttons -->
                            <div class="question-nav">
                                <button type="button" class="btn btn-outline-primary prev-btn" <?= $sequenceNumber === 1 ? 'disabled' : '' ?>>
                                    Previous
                                </button>
                                <div>
                                    <!-- Clear Selection Button -->
                                    <button type="button" class="btn btn-outline-danger clear-btn hidden">
                                        Clear Selection
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary mark-btn">
                                        Mark for Review
                                    </button>
                                    <button type="button"
                                        class="btn btn-primary next-btn"
                                        data-question="<?= $sequenceNumber ?>"
                                        <?= $sequenceNumber === count($questions) ? 'id="submit-exam"' : '' ?>>
                                        <?= $sequenceNumber === count($questions) ? 'Submit' : 'Next' ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php
                        $sequenceNumber++; // Increment the sequence number
                    endforeach; ?>
                </div>
            </div>

            <!-- Status Sidebar -->
            <div class="status-sidebar">
                <h5>Question Status</h5>
                <div class="status-list" id="status-list">
                    <?php
                    $sequenceNumber = 1; // Initialize the sequence number
                    foreach ($questions as $questionId => $question): ?>
                        <div class="status-item" data-question-id="<?= $questionId ?>">
                            <div class="status-indicator"></div>
                            <div>
                                <div>Question <?= $sequenceNumber ?></div>
                                <small class="text-muted">Not Answered</small>
                            </div>
                        </div>
                    <?php
                        $sequenceNumber++; // Increment the sequence number
                    endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <!-- Result Section -->
    <div id="result-container" class="mt-5 d-none text-center">
        <div class="card border-success mx-auto" style="max-width: 500px;">
            <div class="card-header bg-success text-white">
                <h3 class="card-title mb-0">Exam Submitted Successfully!</h3>
            </div>
            <div class="card-body">
                <!-- Score Display -->
                <p class="fs-4">
                    <span id="score" class="text-success fw-bold"></span>
                </p>

                <!-- Detailed Analysis Note -->
                <div class="mt-4">
                    <p class="text-muted">
                        You can view the detailed analysis of your performance in the <strong>My Exam</strong> section.
                    </p>
                    <a href="my_exam.php" class="btn btn-outline-success">
                        Go to My Exams
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentQuestionIndex = 0;
        const questionContainers = document.querySelectorAll('.question-container');
        const statusItems = document.querySelectorAll('.status-item');

        function showQuestion(index) {
            questionContainers.forEach((container, i) => {
                container.style.display = i === index ? 'block' : 'none';
            });
            currentQuestionIndex = index;
            updateStatusIndicator();
        }

        function updateStatusIndicator() {
            statusItems.forEach((item, index) => {
                const container = questionContainers[index];
                const answered = container.querySelector('input:checked');
                const statusIndicator = item.querySelector('.status-indicator');
                const statusText = item.querySelector('.text-muted');
                const isMarked = container.classList.contains('marked-for-review');

                if (isMarked) {
                    statusIndicator.style.backgroundColor = '#ffc107';
                    statusText.textContent = 'Marked for Review';
                } else if (answered) {
                    statusIndicator.style.backgroundColor = '#28a745';
                    statusText.textContent = 'Answered';
                } else {
                    statusIndicator.style.backgroundColor = '';
                    statusText.textContent = 'Not Answered';
                }
            });
        }

        document.querySelectorAll('.next-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (currentQuestionIndex < questionContainers.length - 1) {
                    showQuestion(currentQuestionIndex + 1);
                } else {
                    submitExam();
                }
            });
        });

        document.querySelectorAll('.prev-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (currentQuestionIndex > 0) {
                    showQuestion(currentQuestionIndex - 1);
                }
            });
        });

        statusItems.forEach((item, index) => {
            item.addEventListener('click', () => {
                showQuestion(index);
            });
        });

        document.querySelectorAll('.mark-btn').forEach((btn, index) => {
            btn.addEventListener('click', () => {
                const container = questionContainers[index];
                container.classList.toggle('marked-for-review');
                btn.textContent = container.classList.contains('marked-for-review') ?
                    'Clear Review' :
                    'Mark for Review';
                updateStatusIndicator();
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add this script at the end of the HTML body -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Bootstrap Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p id="loadingMessage">Submission in progress.
                            Please do not close or reload this page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Create a new Bootstrap modal instance with backdrop: 'static' and keyboard: false options
        const myModal = new bootstrap.Modal(document.getElementById("myModal"), {
            backdrop: 'static',
            keyboard: false
        });
        // Add event listener to intercept Escape key press
        document.body.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                // Prevent default behavior of Escape key
                event.preventDefault();
            }
        });
    </script>
    <script>
        // Function to show loading modal
        function showLoadingModal() {
            $('#myModal').modal('show');
        }

        // Function to hide loading modal
        function hideLoadingModal() {
            $('#myModal').modal('hide');
        }

        // Add event listener to form submission
        document.getElementById('exception').addEventListener('submit', function(event) {
            // Show loading modal when form is submitted
            showLoadingModal();
        });

        // Optional: Close loading modal when the page is fully loaded
        window.addEventListener('load', function() {
            // Hide loading modal
            hideLoadingModal();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const totalDurationInMinutes = <?php echo $total_duration; ?>;
            let countdown = totalDurationInMinutes * 60;

            const countdownMessage = document.createElement('p');
            countdownMessage.id = 'timer';
            countdownMessage.textContent = `Time remaining: ${formatTime(countdown)}`;
            document.getElementById('timer-container').appendChild(countdownMessage);

            const countdownInterval = setInterval(() => {
                countdown--;
                countdownMessage.textContent = `Time remaining: ${formatTime(countdown)}`;

                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    submitExam();
                }
            }, 1000);

            function formatTime(seconds) {
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = seconds % 60;
                return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
            }

            function submitExam() {
                const userExamIdElement = document.getElementById('user_exam_id');
                if (!userExamIdElement) {
                    console.error('Error: user_exam_id element not found!');
                    return;
                }

                const user_exam_id = userExamIdElement.value;
                const answers = [];
                const questionContainers = document.querySelectorAll('.question-container');

                // Loop through all question containers
                questionContainers.forEach(container => {
                    const questionId = container.dataset.questionId; // Use actual question ID
                    const selectedOption = container.querySelector('input[type="radio"]:checked');

                    // Push the question ID and selected option (or null if unanswered)
                    answers.push({
                        question_id: questionId,
                        selected_option: selectedOption ? selectedOption.value : null // Store null for unanswered questions
                    });
                });

                // Log the answers for debugging
                console.log('Answers to be submitted:', answers);

                showLoadingModal();

                fetch('submit-answers.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            user_id: "<?php echo $id; ?>",
                            exam_id: <?php echo $exam_id; ?>,
                            user_exam_id: user_exam_id,
                            answers: answers,
                            form_type: 'exam'
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        console.log('Result:', result); // Debugging line
                        hideLoadingModal();

                        if (result.error) {
                            console.error('Submission Error:', result.error);
                            return;
                        }

                        if (result.score !== undefined) {
                            const resultContainer = document.getElementById('result-container');
                            const examForm = document.getElementById('exam-form');

                            if (resultContainer && examForm) {
                                // Hide exam form and show result container
                                examForm.classList.add('d-none');
                                resultContainer.classList.remove('d-none');

                                // Display score
                                document.getElementById('score').textContent = `Your score is: ${result.score}`;

                                // Start countdown
                                let countdown = 5;
                                const countdownMessage = document.createElement('p');
                                countdownMessage.id = 'countdown';
                                countdownMessage.textContent = `Redirecting to My Exams in ${countdown} seconds...`;
                                resultContainer.appendChild(countdownMessage);

                                const countdownInterval = setInterval(() => {
                                    countdown--;
                                    countdownMessage.textContent = `Redirecting to My Exams in ${countdown} seconds...`;
                                    if (countdown === 0) {
                                        clearInterval(countdownInterval);
                                        window.location.href = 'my_exam.php';
                                    }
                                }, 1000);
                            } else {
                                console.error('Error: result-container or exam-form element not found!');
                            }
                        }
                    })
                    .catch(error => {
                        hideLoadingModal();
                        console.error('Error submitting answers:', error);
                    });
            }

            // Event delegation for dynamically generated buttons
            document.addEventListener('click', (event) => {
                if (event.target.matches('.next-btn')) {
                    const currentQuestion = event.target.dataset.question;
                    if (event.target.id === 'submit-exam') {
                        submitExam(); // Submit if it's the last button
                    } else {
                        showNextQuestion(currentQuestion);
                    }
                }
            });

            function showNextQuestion(currentQuestion) {
                const currentContainer = document.querySelector(`.question-container[data-question="${currentQuestion}"]`);
                if (currentContainer) {
                    currentContainer.style.display = 'none'; // Hide current question
                }

                const nextContainer = document.querySelector(`.question-container[data-question="${parseInt(currentQuestion) + 1}"]`);
                if (nextContainer) {
                    nextContainer.style.display = 'block'; // Show next question
                }
            }
        });

        // Show and hide loading modal functions
        function showLoadingModal() {
            $('#myModal').modal('show');
        }

        function hideLoadingModal() {
            $('#myModal').modal('hide');
        }
    </script>
    <script>
        document.querySelectorAll('.clear-btn').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear your selection?')) {
                    const questionContainer = this.closest('.question-container');
                    const radioButtons = questionContainer.querySelectorAll('.option-input');
                    radioButtons.forEach(radio => {
                        radio.checked = false;
                    });
                }
            });
        });
    </script>
    <script>
        document.querySelectorAll('.clear-btn').forEach(button => {
            button.addEventListener('click', function() {
                const questionContainer = this.closest('.question-container');
                const radioButtons = questionContainer.querySelectorAll('.option-input');
                radioButtons.forEach(radio => {
                    radio.checked = false;
                });
                // Add visual feedback
                questionContainer.style.opacity = '0.5';
                setTimeout(() => {
                    questionContainer.style.opacity = '1';
                }, 200);
            });
        });
    </script>
    <script>
        // Function to toggle "Clear Selection" button visibility
        function toggleClearButton(questionContainer) {
            const clearButton = questionContainer.querySelector('.clear-btn');
            const radioButtons = questionContainer.querySelectorAll('.option-input');
            const isAnySelected = Array.from(radioButtons).some(radio => radio.checked);

            // Show/hide the "Clear Selection" button
            if (isAnySelected) {
                clearButton.classList.remove('hidden');
            } else {
                clearButton.classList.add('hidden');
            }
        }

        // Add event listeners to all radio buttons
        document.querySelectorAll('.option-input').forEach(radio => {
            radio.addEventListener('change', function() {
                const questionContainer = this.closest('.question-container');
                toggleClearButton(questionContainer);
            });
        });

        // Add event listener to the "Clear Selection" button
        document.querySelectorAll('.clear-btn').forEach(button => {
            button.addEventListener('click', function() {
                const questionContainer = this.closest('.question-container');
                const radioButtons = questionContainer.querySelectorAll('.option-input');
                radioButtons.forEach(radio => {
                    radio.checked = false;
                });
                // Hide the "Clear Selection" button after clearing
                this.classList.add('hidden');
            });
        });
    </script>
</body>

</html>