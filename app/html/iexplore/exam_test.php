<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_iexplore.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();
?>
<?php
// Get parameters
$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : null;
$login_redirect = isset($_GET['login_redirect']) ? $_GET['login_redirect'] : null;
$questions = []; // Ensure it's initialized

// If no exam_id is provided, set a flag to show the form
$show_form = !$exam_id;

if (!$exam_id) {
    echo "Error: Exam ID is missing.";
    exit;
}

if (!$show_form) {
    // If session exists, check its status
    if ($session_id) {
        $session_query = "SELECT * FROM test_user_sessions WHERE id = $1";
        $session_result = pg_query_params($con, $session_query, array($session_id));
        $session_row = pg_fetch_assoc($session_result);

        if (!$session_row) {
            echo "Error: Invalid session ID.";
            exit;
        }

        $user_exam_id = $session_row['user_exam_id'];
        $session_start = strtotime($session_row['session_start']);
        $status = $session_row['status'];

        // Fetch total duration for exam
        $exam_query = "SELECT total_duration FROM test_exams WHERE id = $1";
        $exam_result = pg_query_params($con, $exam_query, array($exam_id));
        $exam_row = pg_fetch_assoc($exam_result);
        $total_duration = $exam_row['total_duration'];

        // Calculate session end time
        $session_end_time = $session_start + ($total_duration * 60);

        // Check if time expired
        $current_time = time();
        if ($current_time > $session_end_time) {
            // Auto-submit and redirect
            $update_query = "UPDATE test_user_sessions SET status = 'submitted', session_end = NOW() WHERE id = $1";
            pg_query_params($con, $update_query, array($session_id));

            echo "<script>alert('Session time expired. Submitting the exam.'); window.location.href = 'my_exam.php?session_id=$session_id';</script>";
            exit;
        }

        // Fetch existing questions and user responses from test_user_answers
        $question_query = "
        SELECT q.id AS question_id, q.question_text, o.option_key, o.option_text, ua.selected_option
        FROM test_user_answers ua
        JOIN test_questions q ON ua.question_id = q.id
        JOIN test_options o ON q.id = o.question_id
        WHERE ua.user_exam_id = $1
        ORDER BY q.id, o.option_key";
        $result = pg_query_params($con, $question_query, array($user_exam_id));

        while ($row = pg_fetch_assoc($result)) {
            if (!isset($questions[$row['question_id']])) {
                $questions[$row['question_id']] = [
                    'question_text' => $row['question_text'],
                    'selected_option' => $row['selected_option'], // Store the selected option
                    'options' => []
                ];
            }
            $questions[$row['question_id']]['options'][] = [
                'option_key' => $row['option_key'],
                'option_text' => $row['option_text']
            ];
        }
    } else {
        // Create a new exam session
        $user_exam_query = "INSERT INTO test_user_exams (user_id, exam_id) VALUES ($1, $2) RETURNING id";
        $user_exam_result = pg_query_params($con, $user_exam_query, array($id, $exam_id));

        if (!$user_exam_result) {
            echo "Error inserting user exam: " . pg_last_error($con);
            exit;
        }

        $user_exam_row = pg_fetch_assoc($user_exam_result);
        $user_exam_id = $user_exam_row['id'];

        $session_query = "INSERT INTO test_user_sessions (user_exam_id, session_start, status) VALUES ($1, NOW(), 'active') RETURNING id";
        $session_result = pg_query_params($con, $session_query, array($user_exam_id));

        if (!$session_result) {
            echo "Error inserting session: " . pg_last_error($con);
            exit;
        }

        $session_row = pg_fetch_assoc($session_result);
        $session_id = $session_row['id'];

        // Fetch categories linked to the exam
        $category_query = "SELECT category_id FROM test_exam_categories WHERE exam_id = $1";
        $category_result = pg_query_params($con, $category_query, array($exam_id));
        $category_ids = [];
        while ($row = pg_fetch_assoc($category_result)) {
            $category_ids[] = $row['category_id'];
        }

        if (empty($category_ids)) {
            echo "Error: No categories found for this exam.";
            exit;
        }

        // Fetch random questions for the exam
        $category_ids_str = implode(",", $category_ids);
        $question_query = "
        WITH random_questions AS (
            SELECT id AS question_id, question_text
            FROM test_questions
            WHERE category_id IN ($category_ids_str)
            ORDER BY RANDOM()
            LIMIT (SELECT total_questions FROM test_exams WHERE id = $1)
        )
        SELECT rq.question_id, rq.question_text, o.option_key, o.option_text
        FROM random_questions rq
        JOIN test_options o ON rq.question_id = o.question_id
        ORDER BY rq.question_id, o.option_key";
        $result = pg_query_params($con, $question_query, array($exam_id));

        while ($row = pg_fetch_assoc($result)) {
            if (!isset($questions[$row['question_id']])) {
                $questions[$row['question_id']] = [
                    'question_text' => $row['question_text'],
                    'selected_option' => null, // Initialize selected option as null
                    'options' => []
                ];
            }
            $questions[$row['question_id']]['options'][] = [
                'option_key' => $row['option_key'],
                'option_text' => $row['option_text']
            ];
        }

        // Insert questions into test_user_answers
        foreach ($questions as $question_id => $q_data) {
            $insert_answer_query = "INSERT INTO test_user_answers (user_exam_id, question_id, selected_option) VALUES ($1, $2, $3)";
            pg_query_params($con, $insert_answer_query, array($user_exam_id, $question_id, null));
        }

        // Redirect with session_id
        header("Location: exam_test.php?exam_id=$exam_id&session_id=$session_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iExplore-Online Examination Portal</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

        /* Header Styles */
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header .organisation-name,
        header .user-name {
            font-size: 1.2rem;
        }

        /* Sidebar Scrollable */
        .status-sidebar {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            height: calc(100vh - 200px);
            /* Adjust height as needed */
            overflow-y: auto;
            /* Make the content scrollable */
        }
    </style>
</head>

<body>
    <header style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background-color: var(--primary-color); color: white;">
        <div class="organisation-name">Rina Shiksha Sahayak Foundation</div>
        <div class="user-name">
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
                            <!-- <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="my_exam.php"><i class="bi bi-graph-up-arrow me-2"></i>My Exam</a></li> -->
                            <!-- <li><a class="dropdown-item" href="resetpassword.php"><i class="bi bi-gear me-2"></i>Reset Password</a></li> -->
                            <!-- <li>
                                <hr class="dropdown-divider">
                            </li> -->
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else : ?>
                    <!-- Guest State -->
                    <a href="register_user.php" class="btn btn-outline-light me-2">Register</a>
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
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
            let warningCount = 0; // Track the number of tab change warnings
            let isExamSubmitted = false; // Flag to track if the exam has been submitted

            // Get the URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const isLoginRedirect = urlParams.has('login_redirect'); // Check if login_redirect is present

            // Function to handle tab change
            function handleVisibilityChange() {
                if (document.visibilityState === 'hidden' && !isExamSubmitted) {
                    warningCount++;
                    if (warningCount <= 2) {
                        alert(`Warning ${warningCount}: You are not allowed to change tabs during the exam.`);
                    } else {
                        // Submit the exam after 3 warnings
                        alert("You have changed tabs too many times. Your exam will now be submitted.");
                        submitExam();
                    }
                }
            }

            // Add event listener for tab change detection
            document.addEventListener('visibilitychange', handleVisibilityChange);

            // Function to submit the exam
            function submitExam() {
                if (isExamSubmitted) return; // Prevent multiple submissions
                isExamSubmitted = true; // Mark the exam as submitted

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

                                // Remove the beforeunload event listener to prevent resubmission
                                window.removeEventListener('beforeunload', handleBeforeUnload);

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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const totalDurationInMinutes = <?php echo $total_duration; ?>;
            const sessionStartTime = <?php echo $session_start; ?> * 1000; // Convert to milliseconds
            const sessionEndTime = sessionStartTime + (totalDurationInMinutes * 60 * 1000); // Calculate end time
            let isExamSubmitted = false; // Flag to track if the exam has been submitted

            // Timer display
            const timerContainer = document.getElementById('timer-container');
            const timerElement = document.createElement('div');
            timerElement.id = 'timer';
            timerContainer.appendChild(timerElement);

            // Update timer every second
            const timerInterval = setInterval(() => {
                const now = Date.now();
                const timeLeft = sessionEndTime - now;

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerElement.textContent = 'Time expired!';
                    submitExam(); // Auto-submit when time expires
                } else {
                    const minutes = Math.floor(timeLeft / (1000 * 60));
                    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                    timerElement.textContent = `Time left: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
                }
            }, 1000);

            // Function to save the user's selected answer
            function saveAnswer(questionContainer) {
                const questionId = questionContainer.dataset.questionId;
                const selectedOption = questionContainer.querySelector('input[type="radio"]:checked');

                if (selectedOption) {
                    // Save the selected option to the database
                    fetch('save-answer.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                user_exam_id: <?php echo $user_exam_id; ?>,
                                question_id: questionId,
                                selected_option: selectedOption.value
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.success) {
                                console.error('Failed to save answer:', data.error);
                            }
                        })
                        .catch(error => {
                            console.error('Error saving answer:', error);
                        });
                }
            }

            // Save user responses on "Next" click
            document.querySelectorAll('.next-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const questionContainer = button.closest('.question-container');
                    saveAnswer(questionContainer); // Save the answer before moving to the next question
                });
            });

            // Save user responses on "Previous" click
            document.querySelectorAll('.prev-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const questionContainer = button.closest('.question-container');
                    saveAnswer(questionContainer); // Save the answer before moving to the previous question
                });
            });

            // Repopulate user responses on page load
            const questionContainers = document.querySelectorAll('.question-container');
            questionContainers.forEach(container => {
                const questionId = container.dataset.questionId;
                const selectedOption = <?php echo json_encode($questions); ?>[questionId]?.selected_option;

                if (selectedOption) {
                    const radioButton = container.querySelector(`input[value="${selectedOption}"]`);
                    if (radioButton) {
                        radioButton.checked = true;
                    }
                }
            });

            // Function to submit the exam
            function submitExam() {
                if (isExamSubmitted) return; // Prevent multiple submissions
                isExamSubmitted = true; // Mark the exam as submitted

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

            // Handle session expiry on page load
            const currentTime = Date.now();
            if (currentTime > sessionEndTime) {
                alert('Session expired. Submitting the exam...');
                submitExam(); // Submit the exam if the session has expired
            }
        });
    </script>
</body>

</html>