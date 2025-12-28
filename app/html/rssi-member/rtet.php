<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

// Function to generate a random auth code
function generateAuthCode()
{
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Function to generate a 12-digit random user ID
function generateUserId()
{
    return str_pad(mt_rand(0, 999999999999), 12, '0', STR_PAD_LEFT);
}

// Fetch the list of exams from the database
$exams = [];
$query = "SELECT id, name, total_questions, total_duration, language, is_restricted FROM test_exams WHERE is_active = TRUE AND is_restricted=TRUE;";
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
        // Fetch email and other details from signup table using application_number
        $signupQuery = "SELECT applicant_name, email, telephone FROM signup WHERE application_number = $1;";
        $signupResult = pg_query_params($con, $signupQuery, [$applicationNumber]);

        if ($signupResult && pg_num_rows($signupResult) > 0) {
            $signupRow = pg_fetch_assoc($signupResult);
            $applicantName = $signupRow['applicant_name'];
            $applicantEmail = $signupRow['email'];
            $telephone = $signupRow['telephone'];

            // Fetch user_id from test_users table using email
            $userQuery = "SELECT id FROM test_users WHERE email = $1;";
            $userResult = pg_query_params($con, $userQuery, [$applicantEmail]);

            if ($userResult && pg_num_rows($userResult) > 0) {
                // User exists in test_users table
                $userRow = pg_fetch_assoc($userResult);
                $userId = $userRow['id'];
            } else {
                // User does not exist in test_users table, insert new user
                $userId = generateUserId();
                $insertUserQuery = "INSERT INTO test_users (id, name, email, created_at, user_type, contact) 
                                    VALUES ($1, $2, $3, NOW(), 'tap', $4);";
                $insertUserResult = pg_query_params($con, $insertUserQuery, [
                    $userId,
                    $applicantName,
                    $applicantEmail,
                    $telephone
                ]);

                if (!$insertUserResult) {
                    echo "<script>alert('Failed to insert user into test_users table.');</script>";
                    exit;
                }
            }

            // Insert into test_user_exams
            $insertExamQuery = "INSERT INTO test_user_exams (user_id, exam_id) VALUES ($1, $2) RETURNING id;";
            $insertExamResult = pg_query_params($con, $insertExamQuery, [$userId, $examId]);

            if ($insertExamResult && pg_num_rows($insertExamResult) > 0) {
                $examRow = pg_fetch_assoc($insertExamResult);
                $userExamId = $examRow['id'];

                // Fetch exam details to check if it's restricted
                $examDetailsQuery = "SELECT is_restricted, total_duration, id, name, total_questions, language 
                                     FROM test_exams WHERE id = $1;";
                $examDetailsResult = pg_query_params($con, $examDetailsQuery, [$examId]);
                $examDetails = pg_fetch_assoc($examDetailsResult);
                $isRestricted = $examDetails['is_restricted'];

                // Generate auth code for restricted exams
                $authCode = ($isRestricted === 't') ? generateAuthCode() : null;

                // Set session status based on whether the exam is restricted
                $sessionStatus = ($isRestricted === 't') ? 'pending' : 'active';

                // Insert into test_user_sessions
                $insertSessionQuery = "INSERT INTO test_user_sessions (user_exam_id, auth_code, status) 
                                        VALUES ($1, $2, $3) RETURNING id, auth_code;";
                $insertSessionResult = pg_query_params($con, $insertSessionQuery, [$userExamId, $authCode, $sessionStatus]);

                if ($insertSessionResult && pg_num_rows($insertSessionResult) > 0) {
                    $sessionRow = pg_fetch_assoc($insertSessionResult);
                    $sessionId = $sessionRow['id'];
                    $otp = $sessionRow['auth_code'];

                    // Update signup table with session_name and exam_id
                    $updateSignupQuery = "UPDATE signup SET rtet_session_id = $1, exam_id = $2 
                                          WHERE application_number = $3;";
                    $updateSignupResult = pg_query_params($con, $updateSignupQuery, [$sessionId, $examId, $applicationNumber]);

                    if (!$updateSignupResult) {
                        echo "<script>alert('Failed to update rtet_session_id and exam_id in signup table.');</script>";
                    }

                    // Fetch the language for the exam
                    $language_query = "SELECT language FROM test_exams WHERE id = $1";
                    $language_result = pg_query_params($con, $language_query, array($examId));
                    $language_row = pg_fetch_assoc($language_result);
                    $language = $language_row['language']; // e.g., "Hindi", "English", or "Hindi,English"

                    // Prepare the language filter for the query
                    $language_filter = '';
                    if ($language === 'Hindi') {
                        $language_filter = "AND q_language = 'Hindi'"; // Use q_language instead of language
                    } elseif ($language === 'English') {
                        $language_filter = "AND q_language = 'English'"; // Use q_language instead of language
                    } elseif ($language === 'Hindi,English' || $language === 'English,Hindi') {
                        $language_filter = "AND q_language IN ('Hindi', 'English')"; // Use q_language instead of language
                    } else {
                        echo "Error: Invalid language configuration for the exam.";
                        exit;
                    }
                    // Fetch categories linked to the exam with question counts
                    $category_query = "SELECT category_id, question_count FROM test_exam_categories WHERE exam_id = $1";
                    $category_result = pg_query_params($con, $category_query, array($examId));
                    $category_data = [];
                    while ($row = pg_fetch_assoc($category_result)) {
                        $category_data[] = $row; // Store category_id and question_count
                    }

                    if (empty($category_data)) {
                        echo "Error: No categories found for this exam.";
                        exit;
                    }

                    // Fetch random questions for the exam based on question_count for each category and language
                    $questions = [];
                    foreach ($category_data as $category) {
                        $category_id = $category['category_id'];
                        $question_count = $category['question_count'];

                        if ($question_count > 0) {
                            $question_query = "
        WITH random_questions AS (
            SELECT id AS question_id, question_text
            FROM test_questions
            WHERE category_id = $1
            $language_filter
            ORDER BY RANDOM()
            LIMIT $2
        )
        SELECT rq.question_id, rq.question_text, o.option_key, o.option_text
        FROM random_questions rq
        JOIN test_options o ON rq.question_id = o.question_id
        ORDER BY rq.question_id, o.option_key";
                            $result = pg_query_params($con, $question_query, array($category_id, $question_count));

                            if (!$result) {
                                echo "Error: Failed to fetch questions for category $category_id.";
                                exit;
                            }

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
                        }
                    }

                    // Insert questions into test_user_answers
                    foreach ($questions as $question_id => $q_data) {
                        $insert_answer_query = "INSERT INTO test_user_answers (user_exam_id, question_id, selected_option) 
                                               VALUES ($1, $2, $3)";
                        pg_query_params($con, $insert_answer_query, array($userExamId, $question_id, null));
                    }

                    // Display success message with OTP (if restricted)
                    if ($isRestricted === 't') {
                        echo "<script>alert('Exam session created successfully. Your OTP is: $otp');</script>";
                    } else {
                        echo "<script>alert('Exam session created successfully.');</script>";
                    }
                } else {
                    echo "<script>alert('Failed to create session.');</script>";
                }
            } else {
                echo "<script>alert('Failed to create exam.');</script>";
            }
        } else {
            echo "<script>alert('Applicant not found in signup table.');</script>";
        }
    } else {
        echo "<script>alert('Please select an exam and provide your application number.');</script>";
    }
}
?>
<?php
// Add this at the top of your PHP file
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'searchApplicant') {
    $searchQuery = $_GET['query'] ?? null;

    if ($searchQuery) {
        // Fetch applicant details from the signup table
        $signupQuery = "SELECT applicant_name, application_number, email FROM signup 
                        WHERE applicant_name ILIKE $1 OR application_number ILIKE $1 OR email ILIKE $1;";
        $signupResult = pg_query_params($con, $signupQuery, ["%$searchQuery%"]);

        if ($signupResult && pg_num_rows($signupResult) > 0) {
            $applicants = [];
            while ($row = pg_fetch_assoc($signupResult)) {
                $applicants[] = $row;
            }
            echo json_encode([
                'status' => 'success',
                'data' => $applicants
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No applicants found.'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Search query is required.'
        ]);
    }
    exit;
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

    <title>Create RTET</title>

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
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }
    </style>
    <style>
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            /* background-color: #f9f9f9; */
        }

        .search-results {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 10px;
            display: none;
        }

        .search-results .list-group-item {
            cursor: pointer;
        }

        .search-results .list-group-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Create RTET</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">People Plus</a></li>
                    <li class="breadcrumb-item active">Create RTET</li>
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
                                <div class="form-container">
                                    <form method="POST" action="" class="mb-4" id="examForm">
                                        <!-- Search Field -->
                                        <div class="mb-3">
                                            <label for="searchInput" class="form-label">Search Applicant:</label>
                                            <div class="input-group">
                                                <input type="text" id="searchInput" class="form-control" placeholder="Search by name, application number, or email">
                                                <button type="button" class="btn btn-secondary" id="searchButton">Search</button>
                                            </div>
                                            <!-- Search Results -->
                                            <div id="searchResults" class="search-results">
                                                <div class="list-group" id="resultsList"></div>
                                            </div>
                                        </div>

                                        <!-- Application Number Field (Locked After Selection) -->
                                        <div class="mb-3">
                                            <label for="application_number" class="form-label">Application Number:</label>
                                            <input type="text" id="application_number" name="application_number" class="form-control" readonly required>
                                        </div>

                                        <!-- Exam Selection Field -->
                                        <div class="mb-3">
                                            <label for="exam_id" class="form-label">Select Exam:</label>
                                            <select id="exam_id" name="exam_id" class="form-select" required>
                                                <option value="">-- Select Exam --</option>
                                                <?php foreach ($exams as $exam): ?>
                                                    <option value="<?php echo $exam['id']; ?>"><?php echo htmlspecialchars($exam['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Submit Button -->
                                        <button type="submit" class="btn btn-primary w-100" id="submitBtn" disabled>Create Exam</button>
                                    </form>
                                </div>

                                <?php if ($applicantName && $applicantEmail && $applicationNumber && $sessionId && $otp && $examDetails): ?>
                                    <div class="card border-dark shadow-sm mt-4" id="examDetails">
                                        <div class="card-header bg-dark text-white text-center">
                                            <h3 class="mb-0">Exam Confirmation Details</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h5 class="mb-3">Applicant Details</h5>
                                                    <table class="table table-bordered table-sm">
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Applicant Name</th>
                                                                <td><?php echo htmlspecialchars($applicantName); ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Applicant Email</th>
                                                                <td><?php echo htmlspecialchars($applicantEmail); ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Application Number</th>
                                                                <td><?php echo htmlspecialchars($applicationNumber); ?></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5 class="mb-3">Exam Details</h5>
                                                    <table class="table table-bordered table-sm">
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Exam ID</th>
                                                                <td><?php echo htmlspecialchars($examDetails['id']); ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Exam Name</th>
                                                                <td><?php echo htmlspecialchars($examDetails['name']); ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Total Questions</th>
                                                                <td><?php echo htmlspecialchars($examDetails['total_questions']); ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Total Duration</th>
                                                                <td><?php echo htmlspecialchars($examDetails['total_duration']); ?> minutes</td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">Language</th>
                                                                <td><?php echo htmlspecialchars($examDetails['language']); ?></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="row mt-4">
                                                <div class="col-md-6">
                                                    <h5 class="mb-3">Session Details</h5>
                                                    <table class="table table-bordered table-sm">
                                                        <tbody>
                                                            <tr>
                                                                <th scope="row">Session ID</th>
                                                                <td><?php echo htmlspecialchars($sessionId); ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th scope="row">OTP</th>
                                                                <td><?php echo htmlspecialchars($otp); ?></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="text-center mt-4">
                                                <button class="btn btn-primary" onclick="printExamDetails()">Print</button>
                                            </div>
                                        </div>
                                    </div>

                                    <script>
                                        function printExamDetails() {
                                            var content = document.getElementById('examDetails').innerHTML;
                                            var printWindow = window.open('', '', 'width=800,height=600');
                                            printWindow.document.write('<html><head><title>Print Exam Details</title>');
                                            printWindow.document.write('<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">');
                                            printWindow.document.write('</head><body>');
                                            printWindow.document.write(content);
                                            printWindow.document.write('</body></html>');
                                            printWindow.document.close();
                                            // Wait for the Bootstrap CSS to load before printing
                                            printWindow.onload = function() {
                                                printWindow.focus();
                                                printWindow.print();
                                            };
                                        }
                                    </script>
                                <?php endif; ?>
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
  <script src="../assets_new/js/text-refiner.js?v=1.1.0"></script>
    <script>
        // Prevent form resubmission on page reload or back
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Disable submit button after form submission
        document.getElementById('examForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').disabled = true;
        });

        // Search button click event
        document.getElementById('searchButton').addEventListener('click', function() {
            const searchQuery = document.getElementById('searchInput').value;
            const resultsList = document.getElementById('resultsList');
            const searchResults = document.getElementById('searchResults');
            const searchButton = document.getElementById('searchButton');

            if (!searchQuery) {
                alert('Please enter a search term.');
                return;
            }

            // Show loading spinner and disable the search button
            searchButton.innerHTML = `
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Searching...
        `;
            searchButton.disabled = true;

            // Show loading message in the results list
            resultsList.innerHTML = '<div class="text-center text-muted">Loading...</div>';
            searchResults.style.display = 'block';

            // Fetch applicant details via AJAX
            fetch(`?action=searchApplicant&query=${searchQuery}`)
                .then(response => response.json())
                .then(data => {
                    resultsList.innerHTML = ''; // Clear previous results

                    if (data.status === 'success' && data.data.length > 0) {
                        data.data.forEach(applicant => {
                            const listItem = document.createElement('div');
                            listItem.className = 'list-group-item';
                            listItem.innerHTML = `
                            <strong>Name:</strong> ${applicant.applicant_name}<br>
                            <strong>Application Number:</strong> ${applicant.application_number}<br>
                            <strong>Email:</strong> ${applicant.email}
                        `;
                            listItem.addEventListener('click', () => {
                                document.getElementById('application_number').value = applicant.application_number;
                                document.getElementById('application_number').readOnly = true;
                                searchResults.style.display = 'none';
                                document.getElementById('submitBtn').disabled = false;
                            });
                            resultsList.appendChild(listItem);
                        });
                    } else {
                        resultsList.innerHTML = '<div class="text-center text-danger">No applicants found.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultsList.innerHTML = '<div class="text-center text-danger">An error occurred. Please try again.</div>';
                })
                .finally(() => {
                    // Reset the search button text and enable it
                    searchButton.innerHTML = 'Search';
                    searchButton.disabled = false;
                });
        });
    </script>
</body>

</html>