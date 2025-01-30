<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_iexplore.php");

if (!isLoggedIn("aid")) {
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
            COUNT(ua.id) AS total_questions
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

    <title>Exam Attempt Details</title>

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

</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Exam Attempt Details</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Exam Attempt Details</li>
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
                                    <div class="card mt-4">
                                        <div class="card-header">
                                            <h4>Exam Summary</h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <!-- First Column -->
                                                <div class="col-md-6">
                                                    <p><strong>Attempt ID:</strong> <?= $exam_details['user_exam_id'] ?></p>
                                                    <p><strong>User Name:</strong> <?= $exam_details['user_name'] ?> (ID: <?= $exam_details['user_id'] ?>)</p>
                                                    <p><strong>Exam Name:</strong> <?= $exam_details['exam_name'] ?></p>
                                                </div>

                                                <!-- Second Column -->
                                                <div class="col-md-6">
                                                    <p><strong>Exam Date:</strong> <?= date("d-m-Y H:i:s", strtotime($exam_details['exam_date'])) ?></p>
                                                    <p><strong>Total Questions:</strong> <?= $exam_details['total_questions'] ?></p>
                                                    <p><strong>Total Duration:</strong> <?= $exam_details['total_duration'] ?> minutes</p>
                                                    <p><strong>Score:</strong> <?= $exam_details['score'] ?> points</p>

                                                    <h4 class="mt-4">Category-Wise Performance</h4>
                                                    <table class="table table-bordered mt-3">
                                                        <thead>
                                                            <tr>
                                                                <th>Category Name</th>
                                                                <th>Total Questions</th>
                                                                <th>Correct Answers</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($categories)): ?>
                                                                <?php foreach ($categories as $category): ?>
                                                                    <tr>
                                                                        <td><?= $category['category_name'] ?></td>
                                                                        <td><?= $category['total_questions'] ?></td>
                                                                        <td><?= $category['correct_answers'] ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <tr>
                                                                    <td colspan="3" class="text-center">No category data available</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
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