<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_iexplore.php");

if (!isLoggedIn("eid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

$session_name = isset($_GET['session_name']) ? $_GET['session_name'] : null;
$show_form = !$session_name;

if ($session_name) {
    // Fetch user_id, user_exam_id, and session details using a JOIN
    $query = "
        SELECT te.user_id, tus.user_exam_id, tus.session_start, tus.session_end, tus.auth_code
        FROM test_user_sessions tus
        JOIN test_user_exams te ON tus.user_exam_id = te.id
        WHERE tus.id = $1
    ";
    $result = pg_query_params($con, $query, [$session_name]);

    if (!$result || pg_num_rows($result) === 0) {
        echo "<script>alert('Invalid session ID or session not found.'); window.location.href = 'my_exam.php';</script>";
        exit();
    }

    $session_data = pg_fetch_assoc($result);

    // Check if the session belongs to the user
    // if ($id != $session_data['user_id']) {
    //     echo "<script>alert('Unauthorized Access: You do not have permission to access this exam. This may be because the exam session is not linked to your account or the session is invalid or expired. If you believe this is a mistake, please contact support for assistance.'); window.location.href = 'my_exam.php';</script>";
    //     exit();
    // }

    // Check if auth_code is present in the URL
    if (isset($_GET['auth_code']) && $_GET['auth_code'] === $session_data['auth_code']) {
        // Allow access without checking user ID
    } else {
        // Check if the session belongs to the user
        if ($id != $session_data['user_id']) {
            echo "<script>alert('Unauthorized Access: You do not have permission to access this exam. This may be because the exam session is not linked to your account or the session is invalid or expired. If you believe this is a mistake, please contact support for assistance.'); window.location.href = 'my_exam.php';</script>";
            exit();
        }
    }

    $user_exam_id = $session_data['user_exam_id'];

    // Fetch exam details and user's score
    $exam_query = "
        SELECT 
            ue.id AS user_exam_id,
            ue.score,
            u.name AS user_name,
            u.id AS user_id,
            e.name AS exam_name,
            e.total_duration,
            ue.created_at AS exam_date,
            COUNT(ua.id) AS total_questions,
            e.id AS exam_id,
            s.id AS session_name,
            e.show_answer AS show_answer
        FROM test_user_exams ue
        JOIN test_users u ON u.id = ue.user_id
        JOIN test_exams e ON e.id = ue.exam_id
        JOIN test_user_sessions s ON s.user_exam_id = ue.id
        JOIN test_user_answers ua ON ua.user_exam_id = ue.id
        WHERE ue.id = $1
        GROUP BY ue.id, u.id, e.id, s.id
    ";
    $exam_result = pg_query_params($con, $exam_query, [$user_exam_id]);

    if (!$exam_result) {
        die("Error fetching exam data: " . pg_last_error($con));  // Pass $con explicitly
    }

    $exam_details = pg_fetch_assoc($exam_result);

    if (!isset($exam_details['exam_id'])) {
        die("Exam ID not found.");
    }

    // Fetch category-wise statistics
    $category_query = "
        SELECT 
            c.name AS category_name,
            COUNT(q.id) AS total_questions,
            SUM(CASE WHEN q.correct_option = ua.selected_option THEN 1 ELSE 0 END) AS correct_answers
        FROM test_user_answers ua
        JOIN test_questions q ON ua.question_id = q.id
        JOIN test_categories c ON c.id = q.category_id
        WHERE ua.user_exam_id = $1
        GROUP BY c.name
    ";
    $category_result = pg_query_params($con, $category_query, [$user_exam_id]);
    $categories = pg_fetch_all($category_result) ?: [];

    // Fetch questions and user answers
    $question_query = "
        SELECT 
            q.id AS question_id,
            q.question_text,
            q.correct_option,
            o.option_key,
            o.option_text,
            ua.selected_option
        FROM test_user_answers ua
        JOIN test_questions q ON ua.question_id = q.id
        JOIN test_options o ON o.question_id = q.id
        WHERE ua.user_exam_id = $1
        ORDER BY q.id, o.option_key
    ";
    $question_result = pg_query_params($con, $question_query, [$user_exam_id]);

    if (!$question_result) {
        die("Error fetching question data: " . pg_last_error($con));  // Pass $con explicitly
    }

    // Store questions for display
    $questions = [];
    while ($row = pg_fetch_assoc($question_result)) {
        $question_id = $row['question_id'];
        if (!isset($questions[$question_id])) {
            $questions[$question_id] = [
                'question_text' => $row['question_text'],
                'correct_option' => $row['correct_option'],
                'user_answer' => $row['selected_option'],
                'options' => []
            ];
        }
        $questions[$question_id]['options'][] = [
            'option_key' => $row['option_key'],
            'option_text' => $row['option_text']
        ];
    }

    // Fetch total participants
    $total_participants_query = "
        SELECT COUNT(*) AS total_participants
        FROM test_user_exams
        WHERE exam_id = $1
    ";
    $total_participants_result = pg_query_params($con, $total_participants_query, [$exam_details['exam_id']]);

    if (!$total_participants_result) {
        die("Error fetching total participants: " . pg_last_error($con));  // Pass $con explicitly
    }

    $total_participants_data = pg_fetch_assoc($total_participants_result);
    $total_participants = $total_participants_data['total_participants'];

    // Fetch rank for the current user
    $rank_query = "
        SELECT 
            id AS user_exam_id,  -- Use the correct column name
            RANK() OVER (ORDER BY score DESC) AS rank
        FROM test_user_exams
        WHERE exam_id = $1
    ";
    $rank_result = pg_query_params($con, $rank_query, [$exam_details['exam_id']]);

    if (!$rank_result) {
        die("Error calculating rank: " . pg_last_error($con));  // Pass $con explicitly
    }

    $current_user_rank = null;
    while ($rank_data = pg_fetch_assoc($rank_result)) {
        if ($rank_data['user_exam_id'] == $user_exam_id) {
            $current_user_rank = $rank_data['rank'];
            break;
        }
    }

    // Calculate time spent
    $session_start = $session_data['session_start'] ?? null;  // Use null coalescing operator
    $session_end = $session_data['session_end'] ?? null;      // Use null coalescing operator

    // Calculate the difference between session_start and session_end
    if ($session_start && $session_end) {
        $session_start_dt = new DateTime($session_start);
        $session_end_dt = new DateTime($session_end);
        $interval = $session_start_dt->diff($session_end_dt);

        // Format the time difference
        if ($interval->h > 0) {
            // More than an hour
            $time_spent = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ' . $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
        } elseif ($interval->i > 0) {
            // More than a minute but less than an hour
            $time_spent = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
        } else {
            // Less than a minute (seconds)
            $time_spent = $interval->s . ' second' . ($interval->s > 1 ? 's' : '');
        }
    } else {
        // Handle case where session_start or session_end is missing
        $time_spent = 'Not available';
    }
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
    <title>Exam Analysis</title>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include 'header.php'; ?>
    <main class="container py-5">
        <div class="row g-4">
            <main id="main" class="main">
                <div class="container">
                    <?php if ($show_form): ?>
                        <!-- Show the form if no exam_id is provided -->
                        <h4 class="mb-3">Enter Session ID</h4>
                        <form method="GET" action="">
                            <div class="input-group mb-3">
                                <input type="text" name="session_name" class="form-control" placeholder="Enter Attempt ID" required>
                                <button class="btn btn-primary" type="submit">Submit</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="card mt-4">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0">📊 Exam Summary</h4>
                            </div>
                            <div class="card-body" style="padding: 5%;">
                                <div class="row">
                                    <!-- First Column: Key Details -->
                                    <div class="col-md-6">
                                        <div class="mb-4">
                                            <h5 class="text-info">📝 Exam Attempt Details</h5>
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item"><strong>Session ID:</strong> <?= $exam_details['session_name'] ?></li>
                                                <li class="list-group-item"><strong>User Name:</strong> <?= $exam_details['user_name'] ?> (ID: <?= $exam_details['user_id'] ?>)</li>
                                                <li class="list-group-item"><strong>Exam Name:</strong> <?= $exam_details['exam_name'] ?></li>
                                                <li class="list-group-item"><strong>Exam Date:</strong> <?= date("d-m-Y H:i:s", strtotime($exam_details['exam_date'])) ?></li>
                                                <li class="list-group-item"><strong>Total Questions:</strong> <?= $exam_details['total_questions'] ?></li>
                                                <li class="list-group-item"><strong>Total Duration:</strong> <?= $exam_details['total_duration'] ?> minutes</li>
                                                <li class="list-group-item"><strong>Score:</strong> <span class="badge bg-success"><?= $exam_details['score'] ?> points</span></li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Second Column: Performance Visualization -->
                                    <div class="col-md-6">
                                        <h5 class="text-info">📈 Category-Wise Performance</h5>
                                        <?php if (!empty($categories)): ?>
                                            <!-- Bar Chart for Category Performance -->
                                            <canvas id="categoryPerformanceChart" width="400" height="200"></canvas>
                                            <script>
                                                const ctx = document.getElementById('categoryPerformanceChart').getContext('2d');

                                                // Calculate accuracy for each category
                                                const accuracies = <?= json_encode(array_map(function ($category) {
                                                                        return ($category['total_questions'] > 0) ? ($category['correct_answers'] / $category['total_questions']) * 100 : 0;
                                                                    }, $categories)); ?>;

                                                const chart = new Chart(ctx, {
                                                    type: 'bar',
                                                    data: {
                                                        labels: <?= json_encode(array_column($categories, 'category_name')) ?>,
                                                        datasets: [{
                                                            label: 'Accuracy (%)',
                                                            data: accuracies,
                                                            backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                                            borderColor: 'rgba(75, 192, 192, 1)',
                                                            borderWidth: 1
                                                        }]
                                                    },
                                                    options: {
                                                        scales: {
                                                            y: {
                                                                beginAtZero: true,
                                                                max: 100 // Accuracy is always between 0 and 100%
                                                            }
                                                        }
                                                    }
                                                });
                                            </script>

                                            <!-- Table for Detailed Category Performance -->
                                            <div class="table-responsive">
                                                <table class="table table-bordered mt-3">
                                                    <thead>
                                                        <tr>
                                                            <th>Category Name</th>
                                                            <th>Total Questions</th>
                                                            <th>Correct Answers</th>
                                                            <th>Accuracy (%)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($categories as $category): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($category['category_name']) ?></td>
                                                                <td><?= htmlspecialchars($category['total_questions']) ?></td>
                                                                <td><?= htmlspecialchars($category['correct_answers']) ?></td>
                                                                <td>
                                                                    <?php
                                                                    $accuracy = ($category['total_questions'] > 0) ? ($category['correct_answers'] / $category['total_questions']) * 100 : 0;
                                                                    echo number_format($accuracy, 2); // Display accuracy with two decimal points
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning">No category data available.</div>
                                        <?php endif; ?>
                                    </div>

                                </div>

                                <!-- Additional Insights -->
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <h5 class="text-info">🔍 Insights & Analysis</h5>
                                        <div class="row d-flex align-items-stretch"> <!-- Add d-flex and align-items-stretch here -->
                                            <div class="col-md-4 d-flex">
                                                <div class="card text-center flex-fill"> <!-- Add flex-fill here -->
                                                    <div class="card-body">
                                                        <h6 class="card-title">Accuracy</h6>
                                                        <h3 class="text-success">
                                                            <?php
                                                            // Calculate total number of questions and total correct questions
                                                            $total_questions = array_sum(array_column($categories, 'total_questions'));
                                                            $total_correct_answers = array_sum(array_column($categories, 'correct_answers'));

                                                            // Calculate the accuracy as the percentage of correct answers
                                                            $accuracy = ($total_questions > 0) ? ($total_correct_answers / $total_questions) * 100 : 0;

                                                            // Round the accuracy to 2 decimal places
                                                            echo round($accuracy, 2) . '%';
                                                            ?>
                                                        </h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 d-flex">
                                                <div class="card text-center flex-fill"> <!-- Add flex-fill here -->
                                                    <div class="card-body">
                                                        <h6 class="card-title">Time Spent</h6>
                                                        <h3 class="text-primary"><?= $time_spent ?></h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Display the Rank and Total Participants -->
                                            <div class="col-md-4 d-flex">
                                                <div class="card text-center flex-fill"> <!-- Add flex-fill here -->
                                                    <div class="card-body">
                                                        <h6 class="card-title">Rank</h6>
                                                        <h3 class="text-warning">#<?= $current_user_rank ?? 'N/A' ?></h3>
                                                        <p class="text-muted">Total Participants: <?= $total_participants ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if ($exam_details['show_answer'] == 't'): ?>
                            <?php if (!empty($questions)): ?>
                                <div class="mt-4">
                                    <?php $question_number = 1; // Initialize question number 
                                    ?>
                                    <?php foreach ($questions as $question_id => $data): ?>
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <!-- Display question number before the question text -->
                                                <strong>Q <?= $question_number ?>:</strong> <?= $data['question_text'] ?>
                                            </div>
                                            <div class="card-body">
                                                <ul class="list-group">
                                                    <?php foreach ($data['options'] as $option): ?>
                                                        <li class="list-group-item 
                                        <?= $option['option_key'] === $data['correct_option'] ? 'list-group-item-success' : '' ?>
                                        <?= $option['option_key'] === $data['user_answer'] && $option['option_key'] !== $data['correct_option'] ? 'list-group-item-danger' : '' ?>">
                                                            <?php
                                                            $isUserAnswer = $option['option_key'] === $data['user_answer'];
                                                            $isCorrectOption = $option['option_key'] === $data['correct_option'];
                                                            ?>

                                                            <?php if ($isUserAnswer || $isCorrectOption): ?>
                                                                <span class="position-absolute" style="left: 0;">
                                                                    <?php if ($isUserAnswer && !$isCorrectOption): ?>
                                                                        <span class="text-danger">✖</span>
                                                                    <?php elseif ($isCorrectOption): ?>
                                                                        <span class="text-success">✔</span>
                                                                    <?php endif; ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <strong><?= $option['option_key'] ?>:</strong> <?= $option['option_text'] ?>
                                                            <?php if ($option['option_key'] === $data['user_answer']): ?>
                                                                <span class="badge text-bg-warning ms-2">Your Answer</span>
                                                            <?php endif; ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <?php $question_number++; // Increment question number after each question 
                                        ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <!-- No questions available -->
                                <p>No questions found for this attempt.</p>
                            <?php endif; ?>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </main>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>