<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_iexplore.php");

if (!isLoggedIn("eid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$user_exam_id = isset($_GET['user_exam_id']) ? $_GET['user_exam_id'] : null;
$show_form = !$user_exam_id;

if ($user_exam_id) {
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
            e.id AS exam_id
        FROM test_user_exams ue
        JOIN test_users u ON u.id = ue.user_id
        JOIN test_exams e ON e.id = ue.exam_id
        JOIN test_user_answers ua ON ua.user_exam_id = ue.id
        WHERE ue.id = $1
        GROUP BY ue.id, u.id, e.id
    ";

    $exam_result = pg_query_params($con, $exam_query, [$user_exam_id]);

    if (!$exam_result) {
        die("Error fetching exam data: " . pg_last_error());
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
    $categories = pg_fetch_all($category_result);

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
        die("Error fetching question data: " . pg_last_error());
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
        die("Error fetching total participants: " . pg_last_error());
    }

    $total_participants_data = pg_fetch_assoc($total_participants_result);
    $total_participants = $total_participants_data['total_participants'];

// Fetch all participants' scores for the specific exam, considering each attempt (user_exam_id)
$rank_query = "
    SELECT 
        ue.user_id,
        ue.id AS user_exam_id,  -- Including the attempt ID
        ue.score,
        RANK() OVER (PARTITION BY ue.exam_id ORDER BY ue.score DESC) AS rank
    FROM test_user_exams ue
    WHERE ue.exam_id = $1
";

$rank_result = pg_query_params($con, $rank_query, [$exam_details['exam_id']]);

if (!$rank_result) {
    die("Error calculating rank: " . pg_last_error());
}

// Find the rank for the current user and track the total participants
$current_user_rank = null;
$total_participants = 0;

while ($rank_data = pg_fetch_assoc($rank_result)) {
    // Increment total participants count
    $total_participants++;

    // Find the rank of the current attempt (user_exam_id) of the current user
    if ($rank_data['user_exam_id'] == $exam_details['user_exam_id']) {
        $current_user_rank = $rank_data['rank'];
    }
}

// Fetch the total number of participants (this is counted above)

}
?>
<?php
// Fetch session start and end time for the exam user
$query = "
    SELECT session_start, session_end
    FROM test_user_sessions
    WHERE user_exam_id = $1 AND status = 'submitted'
";
$result = pg_query_params($con, $query, array($user_exam_id));

if ($session_data = pg_fetch_assoc($result)) {
    $session_start = new DateTime($session_data['session_start']);
    $session_end = new DateTime($session_data['session_end']);
    
    // Calculate the difference
    $interval = $session_start->diff($session_end);
    
    // Check if the time difference is more than an hour
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
    $time_spent = 'Not available'; // Handle case where session is not found
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Exam Analysis</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="my_exam.php">My Exams</a></li>
                    <li class="breadcrumb-item active">Exam Analysis</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <div class="container mt-5">
                                <?php if ($show_form): ?>
                                    <!-- Show the form if no exam_id is provided -->
                                    <h4 class="mb-3">Enter Attempt ID</h4>
                                    <form method="GET" action="">
                                        <div class="input-group mb-3">
                                            <input type="text" name="user_exam_id" class="form-control" placeholder="Enter Attempt ID" required>
                                            <button class="btn btn-primary" type="submit">Submit</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="card mt-4 shadow-lg">
                                        <div class="card-header bg-primary text-white">
                                            <h4 class="mb-0">📊 Exam Summary</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <!-- First Column: Key Details -->
                                                <div class="col-md-6">
                                                    <div class="mb-4">
                                                        <h5 class="text-info">📝 Exam Attempt Details</h5>
                                                        <ul class="list-group list-group-flush">
                                                            <li class="list-group-item"><strong>Attempt ID:</strong> <?= $exam_details['user_exam_id'] ?></li>
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
                                                    <?php else: ?>
                                                        <div class="alert alert-warning">No category data available.</div>
                                                    <?php endif; ?>
                                                </div>

                                            </div>

                                            <!-- Additional Insights -->
                                            <div class="row mt-4">
                                                <div class="col-md-12">
                                                    <h5 class="text-info">🔍 Insights & Analysis</h5>
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="card text-center">
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
                                                        <div class="col-md-4">
                                                            <div class="card text-center">
                                                                <div class="card-body">
                                                                    <h6 class="card-title">Time Spent</h6>
                                                                    <h3 class="text-primary"><?= $time_spent ?></h3>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- Display the Rank and Total Participants -->
                                                        <div class="col-md-4">
                                                            <div class="card text-center">
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
                                                                    <strong><?= $option['option_key'] ?>:</strong> <?= $option['option_text'] ?>
                                                                    <?php if ($option['option_key'] === $data['user_answer']): ?>
                                                                        <span class="badge bg-primary ms-2">Your Answer</span>
                                                                    <?php endif; ?>
                                                                    <?php if ($option['option_key'] === $data['correct_option']): ?>
                                                                        <span class="badge bg-success ms-2">Correct Answer</span>
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
</body>

</html>