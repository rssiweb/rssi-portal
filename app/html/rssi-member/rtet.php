<?php
require_once __DIR__ . "/../../bootstrap.php"; // Include the database connection

// Function to generate a random auth code
function generateAuthCode() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Fetch the list of exams from the database
$exams = [];
$query = "SELECT id, name, total_questions, total_duration, language FROM test_exams WHERE is_active = TRUE;";
$result = pg_query($con, $query);
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $exams[] = $row;
    }
}

// Initialize variables for displaying information
$applicantName = null;
$applicantEmail = null;
$applicationNumber = null;
$sessionId = null;
$otp = null;
$examDetails = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = $_POST['exam_id'] ?? null;
    $applicationNumber = $_POST['application_number'] ?? null;

    if ($examId && $applicationNumber) {
        // Fetch email from signup table using application_number
        $signupQuery = "SELECT applicant_name, email FROM signup WHERE application_number = $1;";
        $signupResult = pg_query_params($con, $signupQuery, [$applicationNumber]);

        if ($signupResult && pg_num_rows($signupResult) > 0) {
            $signupRow = pg_fetch_assoc($signupResult);
            $applicantName = $signupRow['applicant_name'];
            $applicantEmail = $signupRow['email'];

            // Fetch user_id from test_users table using email
            $userQuery = "SELECT id FROM test_users WHERE email = $1;";
            $userResult = pg_query_params($con, $userQuery, [$applicantEmail]);

            if ($userResult && pg_num_rows($userResult) > 0) {
                $userRow = pg_fetch_assoc($userResult);
                $userId = $userRow['id'];

                // Insert into test_user_exams
                $insertExamQuery = "INSERT INTO test_user_exams (user_id, exam_id) VALUES ($1, $2) RETURNING id;";
                $insertExamResult = pg_query_params($con, $insertExamQuery, [$userId, $examId]);

                if ($insertExamResult && pg_num_rows($insertExamResult) > 0) {
                    $examRow = pg_fetch_assoc($insertExamResult);
                    $userExamId = $examRow['id'];

                    // Generate auth code
                    $authCode = generateAuthCode();

                    // Insert into test_user_sessions
                    $insertSessionQuery = "INSERT INTO test_user_sessions (user_exam_id, auth_code) VALUES ($1, $2) RETURNING id, auth_code;";
                    $insertSessionResult = pg_query_params($con, $insertSessionQuery, [$userExamId, $authCode]);

                    if ($insertSessionResult && pg_num_rows($insertSessionResult) > 0) {
                        $sessionRow = pg_fetch_assoc($insertSessionResult);
                        $sessionId = $sessionRow['id'];
                        $otp = $sessionRow['auth_code'];

                        // Update rtet_session_id in signup table
                        $updateSignupQuery = "UPDATE signup SET rtet_session_id = $1 WHERE application_number = $2;";
                        $updateSignupResult = pg_query_params($con, $updateSignupQuery, [$sessionId, $applicationNumber]);

                        if (!$updateSignupResult) {
                            echo "<script>alert('Failed to update rtet_session_id in signup table.');</script>";
                        }

                        // Fetch exam details for display
                        $examDetailsQuery = "SELECT name, total_questions, total_duration, language FROM test_exams WHERE id = $1;";
                        $examDetailsResult = pg_query_params($con, $examDetailsQuery, [$examId]);
                        if ($examDetailsResult && pg_num_rows($examDetailsResult) > 0) {
                            $examDetails = pg_fetch_assoc($examDetailsResult);
                        }
                    } else {
                        echo "<script>alert('Failed to create session.');</script>";
                    }
                } else {
                    echo "<script>alert('Failed to create exam.');</script>";
                }
            } else {
                echo "<script>alert('User not found in test_users table.');</script>";
            }
        } else {
            echo "<script>alert('Applicant not found in signup table.');</script>";
        }
    } else {
        echo "<script>alert('Please select an exam and provide your application number.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Create Exam</h1>
        <form method="POST" action="" class="mb-4">
            <div class="mb-3">
                <label for="application_number" class="form-label">Application Number:</label>
                <input type="text" id="application_number" name="application_number" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="exam_id" class="form-label">Select Exam:</label>
                <select id="exam_id" name="exam_id" class="form-select" required>
                    <option value="">-- Select Exam --</option>
                    <?php foreach ($exams as $exam): ?>
                        <option value="<?php echo $exam['id']; ?>"><?php echo htmlspecialchars($exam['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Create Exam</button>
        </form>

        <?php if ($applicantName && $applicantEmail && $applicationNumber && $sessionId && $otp && $examDetails): ?>
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h2 class="card-title mb-0">Exam Created Successfully!</h2>
                </div>
                <div class="card-body">
                    <p><strong>Applicant Name:</strong> <?php echo htmlspecialchars($applicantName); ?></p>
                    <p><strong>Applicant Email:</strong> <?php echo htmlspecialchars($applicantEmail); ?></p>
                    <p><strong>Application Number:</strong> <?php echo htmlspecialchars($applicationNumber); ?></p>
                    <p><strong>Session ID:</strong> <?php echo htmlspecialchars($sessionId); ?></p>
                    <p><strong>OTP:</strong> <?php echo htmlspecialchars($otp); ?></p>
                    <p><strong>Exam Name:</strong> <?php echo htmlspecialchars($examDetails['name']); ?></p>
                    <p><strong>Total Questions:</strong> <?php echo htmlspecialchars($examDetails['total_questions']); ?></p>
                    <p><strong>Total Duration:</strong> <?php echo htmlspecialchars($examDetails['total_duration']); ?> minutes</p>
                    <p><strong>Language:</strong> <?php echo htmlspecialchars($examDetails['language']); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>