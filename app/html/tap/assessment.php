<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_tap.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Initialize exam details and flags
$examDetails = null;
$noExamDetails = false;

// Fetch exam details
$applicationNumber = $application_number; // Replace with actual application number
$signupQuery = "SELECT rtet_session_id, exam_id FROM signup WHERE application_number = $1;";
$signupResult = pg_query_params($con, $signupQuery, [$applicationNumber]);

if ($signupResult && pg_num_rows($signupResult) > 0) {
    $signupRow = pg_fetch_assoc($signupResult);
    $rtetSessionId = $signupRow['rtet_session_id'];
    $examId = $signupRow['exam_id'];

    // Fetch exam details
    $examQuery = "SELECT name, total_questions, total_duration, language FROM test_exams WHERE id = $1;";
    $examResult = pg_query_params($con, $examQuery, [$examId]);

    if ($examResult && pg_num_rows($examResult) > 0) {
        $examDetails = pg_fetch_assoc($examResult);
    } else {
        $noExamDetails = true; // No exam details found
    }
} else {
    $noExamDetails = true; // Application number not found
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

    <title>RTET-Assessment</title>

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

</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Assessment</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Assessment</li>
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
                            <div class="container py-5">
                                <div class="row justify-content-center">
                                    <div class="col-lg-6 col-md-8">
                                        <?php if ($noExamDetails): ?>
                                            <!-- No Exam Details Message -->
                                            <div class="alert alert-info text-center">
                                                Your exam has not been created yet.
                                            </div>
                                        <?php else: ?>
                                            <div class="card shadow-sm border-0">
                                                <div class="card-header bg-light text-center">
                                                    <h5 class="mb-0">Assessment Summary</h5>
                                                </div>
                                                <div class="card-body">
                                                    <!-- Exam Details -->
                                                    <ul class="list-group list-group-flush">
                                                        <li class="list-group-item"><strong>Exam Name:</strong> <span id="examName"><?php echo htmlspecialchars($examDetails['name']); ?></span></li>
                                                        <li class="list-group-item"><strong>Total Questions:</strong> <span id="totalQuestions"><?php echo htmlspecialchars($examDetails['total_questions']); ?></span></li>
                                                        <li class="list-group-item"><strong>Total Duration:</strong> <span id="totalDuration"><?php echo htmlspecialchars($examDetails['total_duration']); ?> minutes</span></li>
                                                        <li class="list-group-item"><strong>Language:</strong> <span id="language"><?php echo htmlspecialchars($examDetails['language']); ?></span></li>
                                                    </ul>
                                                    <div class="text-center mt-4">
                                                        <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#examInstructionsModal">
                                                            Start Assessment
                                                        </button>
                                                    </div>
                                                <?php endif; ?>
                                                </div>
                                            </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Exam Instructions Modal -->
                            <div class="modal fade" id="examInstructionsModal" tabindex="-1" aria-labelledby="examInstructionsModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="examInstructionsModalLabel">Exam Instructions</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <!-- Instructions -->
                                            <h6>Please read the instructions carefully:</h6>
                                            <ol>
                                                <li>Ensure you have a stable internet connection.</li>
                                                <li>Do not refresh the page during the exam.</li>
                                                <li>This is a password-protected test. To get the OTP, contact the center incharge.</li>
                                                <li>Any suspicious activity may result in disqualification.</li>
                                            </ol>

                                            <!-- Confirmation Checkbox -->
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" id="confirmCheckbox">
                                                <label class="form-check-label" for="confirmCheckbox">
                                                    I have read and understood the instructions. I am ready to start the exam.
                                                </label>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <!-- Start Exam Button (Disabled by Default) -->
                                            <button id="startExamButton" class="btn btn-primary" disabled>
                                                Start Now
                                            </button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
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
        // Enable Start Exam button when checkbox is checked
        document.getElementById('confirmCheckbox').addEventListener('change', function() {
            const startExamButton = document.getElementById('startExamButton');
            startExamButton.disabled = !this.checked;
        });

        // Redirect to exam page when Start Exam button is clicked
        document.getElementById('startExamButton').addEventListener('click', function() {
            const examId = "<?php echo htmlspecialchars($examId); ?>";
            const sessionId = "<?php echo htmlspecialchars($rtetSessionId); ?>";
            window.location.href = `../iexplore/exam.php?exam_id=${examId}&session_id=${sessionId}`;
        });
    </script>
</body>

</html>