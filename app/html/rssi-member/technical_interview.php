<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
}

validation();
$interview_id = null;
if (isset($_GET['applicationNumber_verify'])) {
    // Get the application number from the GET parameter
    $applicationNumber = $_GET['applicationNumber_verify'];
    $applicationNumberEscaped = pg_escape_string($con, $applicationNumber);
    $documentVerification = false;
    // Fetch user's highest educational qualification
    $getUserQualification = "SELECT education_qualification FROM signup WHERE application_number = '$applicationNumberEscaped'";
    $qualificationResult = pg_query($con, $getUserQualification);

    if ($qualificationResult && pg_num_rows($qualificationResult) > 0) {
        $qualificationRow = pg_fetch_assoc($qualificationResult);
        $educationQualification = $qualificationRow['education_qualification'];

        // Determine required fields based on education qualification
        $requiredFields = [
            "11" => ["highschool"],
            "12" => ["highschool", "intermediate"],
            "Bachelor" => ["highschool", "intermediate", "graduation"],
            "Master" => ["highschool", "intermediate", "graduation", "post_graduation"],
            "Doctorate" => ["highschool", "intermediate", "graduation", "post_graduation"]
        ];

        $requiredFiles = [];
        foreach ($requiredFields as $key => $fields) {
            if (stripos($educationQualification, $key) !== false) {
                $requiredFiles = $fields;
                break;
            }
        }

        // Get the latest uploaded files and their corresponding verification status
        $getLatestFiles = "
            SELECT file_name, uploaded_on AS latest_upload, verification_status
            FROM archive
            WHERE uploaded_by = '$applicationNumberEscaped'
            AND uploaded_on = (
                SELECT MAX(uploaded_on)
                FROM archive AS a2
                WHERE a2.file_name = archive.file_name
                AND a2.uploaded_by = '$applicationNumberEscaped'
            )";
        $filesResult = pg_query($con, $getLatestFiles);

        $uploadedFiles = [];
        $allVerified = true;
        if ($filesResult && pg_num_rows($filesResult) > 0) {
            while ($fileRow = pg_fetch_assoc($filesResult)) {
                $fileName = $fileRow['file_name'];
                $verificationStatus = $fileRow['verification_status'];

                $uploadedFiles[$fileName] = $verificationStatus;

                if (empty($verificationStatus)) {
                    $allVerified = false; // Some file is not verified
                }
            }

            // Check which required files are missing or unverified
            $missingFiles = array_diff($requiredFiles, array_keys($uploadedFiles));
            $unverifiedFiles = [];

            foreach ($requiredFiles as $file) {
                if (isset($uploadedFiles[$file]) && $uploadedFiles[$file] !== 'Verified') {
                    $unverifiedFiles[] = $file;
                }
            }

            // Check if all required files are verified
            $allRequiredVerified = empty($missingFiles) && empty($unverifiedFiles);

            // Set documentVerification based on conditions
            if ($allVerified && $allRequiredVerified) {
                $documentVerification = true;
            }
            // Prepare response
            $response = [
                'application_number' => $applicationNumber,
                'documentVerification' => $documentVerification,
                'requiredFiles' => $requiredFiles,
                'uploadedFiles' => $uploadedFiles,
                'missingFiles' => $missingFiles,
                'unverifiedFiles' => $unverifiedFiles
            ];
        } else {
            // No matching record found in signup
            $response = [
                'status' => 'no_records',
                'message' => 'No records found for the given application number.'
            ];
        }
        // echo json_encode($response);
    }

    $isFormDisabled = null;

    // Escape the application number to prevent SQL injection
    $applicationNumberEscaped = pg_escape_string($con, $applicationNumber);

    // Query to fetch data from signup table based on application number
    $getDetails = "SELECT * FROM signup WHERE application_number = '$applicationNumberEscaped'";
    $result = pg_query($con, $getDetails);

    if ($result) {
        $row = pg_fetch_assoc($result);
        if ($row) {
            // Query to fetch interview data from the interview table based on application number
            $getInterview = "SELECT * FROM interview WHERE application_number = '$applicationNumberEscaped'";
            $interviewResult = pg_query($con, $getInterview);

            // Initialize interview data response as null
            $interviewDataResponse = null;

            // Check if interview data exists
            if ($interviewResult && pg_num_rows($interviewResult) > 0) {
                $interviewData = pg_fetch_assoc($interviewResult);

                $formStatus = $interviewData['form_status']; // Assuming you fetched this column from your query

                $isFormDisabled = ($formStatus == true) ? 'disabled' : '';

                // Split interviewer_ids into an array
                $interviewerIds = explode(',', $interviewData['interviewer_ids']);

                // Fetch interviewer details if interviewer IDs are available
                if (!empty($interviewerIds)) {
                    $interviewerIdsQuoted = array_map(function ($id) use ($con) {
                        return "'" . pg_escape_string($con, $id) . "'";
                    }, $interviewerIds);
                    $interviewerIdsFinal = implode(',', $interviewerIdsQuoted);

                    $query = "SELECT associatenumber AS id, fullname AS name, position
                    FROM rssimyaccount_members
                    WHERE associatenumber IN ($interviewerIdsFinal) AND filterstatus = 'Active'";
                    $employeeResult = pg_query($con, $query);

                    // Collect interviewer details
                    $interviewers = [];
                    if ($employeeResult && pg_num_rows($employeeResult) > 0) {
                        while ($employee = pg_fetch_assoc($employeeResult)) {
                            $interviewers[] = $employee;
                        }
                    }

                    // Add interview data and interviewer details to the response
                    $interviewDataResponse = array(
                        'documentsList' => $interviewData['documents'], // Assuming documents are stored as comma-separated values
                        'subjectKnowledge' => $interviewData['subject_knowledge'],
                        'computerKnowledge' => $interviewData['computer_knowledge'],
                        'demoClass' => $interviewData['demo_class'],
                        'writtenTest' => $interviewData['written_test'],
                        'experience' => $interviewData['experience'],
                        'remarks' => $interviewData['remarks'],
                        'declaration' => $interviewData['declaration'],
                        'interview_duration' => $interviewData['interview_duration'],
                        'interviewers' => $interviewers // Include interviewer details
                    );
                }
            }

            // Prepare final response
            $responseData = array(
                'applicantFullName' => $row['applicant_name'],
                'application_number' => $row['application_number'],
                'email' => $row['email'],
                'base_branch' => $row['branch'],
                'association_type' => $row['association'],
                'resumeLink' => $row['resume_upload'],
                'aadhar_number' => $row['identifier_number'],
                'contact' => $row['telephone'],
                'photo' => $row['applicant_photo'],
                'subject_preference_1' => $row['subject1'],
                'tech_interview_schedule' => $row['tech_interview_schedule'],
                'position' => 'Post: ' . $row['post_select'] . ', Job: ' . $row['job_select'],
                'interview_data' => $interviewDataResponse,
            );
        } else {
            // No matching record found in signup
            $responseData = ['status' => 'no_records', 'message' => 'No records found for the given application number.'];
        }
    } else {
        // Error in query execution for signup
        $responseData = ['status' => 'error', 'message' => 'Error retrieving user data.'];
    }
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch form data
    $interview_id = uniqid();
    $application_number = pg_escape_string($con, $responseData['application_number']);
    $applicant_name = htmlspecialchars($responseData['applicantFullName']);
    $applicant_email = htmlspecialchars($responseData['email']);
    $documents_string = isset($_POST['documents']) && is_array($_POST['documents']) ? pg_escape_string($con, implode(',', $_POST['documents'])) : '';
    $subject_knowledge = (int) $_POST['subjectKnowledge'];
    $computer_knowledge = (int) $_POST['computerKnowledge'];
    $demo_class = (int) $_POST['demoClass'];
    $written_test = isset($_POST['writtenTest']) ? (int) $_POST['writtenTest'] : NULL;
    $experience = pg_escape_string($con, $_POST['experience']);
    $remarks = pg_escape_string($con, $_POST['remarks']);
    $application_status = 'Technical Interview Completed';

    // Check if interviewer_ids is set and not empty, if not, set it to an empty string
    $interviewer_ids_string = isset($_POST['interviewer_ids']) ? pg_escape_string($con, $_POST['interviewer_ids']) : '';

    $interview_duration = (int) $_POST['interview_duration'];

    // Correct the declaration field to boolean (true/false)
    $declaration = isset($_POST['declaration']) && $_POST['declaration'] == 'on' ? 'true' : 'false';

    function getUserIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipList[0]);
            return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : $_SERVER['REMOTE_ADDR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    $ip_address = getUserIpAddr();

    // Insert data into the interview table
    $insert_query = "INSERT INTO interview (interview_id,application_number, applicant_name, applicant_email, documents,subject_knowledge, computer_knowledge, demo_class, written_test, experience, remarks, interviewer_ids, interview_duration, declaration,submitted_by,ip_address)
    VALUES ('$interview_id','$application_number', '$applicant_name', '$applicant_email', '$documents_string',$subject_knowledge, $computer_knowledge, $demo_class, $written_test, '$experience', '$remarks', '$interviewer_ids_string', $interview_duration, $declaration,'$associatenumber','$ip_address')";

    $update_query_signup = "UPDATE signup SET application_status=$1 WHERE application_number=$2";
    pg_prepare($con, "update_signup", $update_query_signup);
    pg_execute($con, "update_signup", array($application_status, $application_number));

    // Execute the query
    $result = pg_query($con, $insert_query);
    $cmdtuples = pg_affected_rows($result);


    if (!empty($interviewer_ids_string)) {
        // Convert the string of IDs into an array
        $interviewer_ids = explode(',', $interviewer_ids_string);

        // Sanitize each ID and create a string for the SQL IN clause
        $sanitized_ids = array_map(function ($id) use ($con) {
            return "'" . pg_escape_string($con, trim($id)) . "'";
        }, $interviewer_ids);

        // Join sanitized IDs into a string for the SQL query
        $id_list = implode(',', $sanitized_ids);

        // Query to fetch names and emails
        $query = "SELECT fullname, email FROM rssimyaccount_members WHERE associatenumber IN ($id_list)";
        $result = pg_query($con, $query);

        if ($result && pg_num_rows($result) > 0) {
            $recipients = [];
            $interviewer_details = [];

            while ($row = pg_fetch_assoc($result)) {
                $interviewer_details[] = [
                    'name' => $row['fullname'],
                    'email' => $row['email']
                ];
                if (!empty($row['email'])) {
                    $recipients[] = $row['email'];
                }
            }

            if (!empty($recipients)) {
                // Send email notification to all recipients
                // foreach ($interviewer_details as $interviewer) {
                sendEmail("tap_technical_interview_completed", array(
                    "application_number" => $application_number, // Assumes candidate ID is posted
                    "applicant_name" => $applicant_name, // Assumes candidate name is posted
                    //"interviewer_name" => $interviewer['name'],
                ), $recipients); //$interviewer['email']
                // }
            }
        }
    }
}

?>
<?php
// Initialize variables for name and ID
$submittedByName = '';
$submittedById = '';

// Check if 'submitted_by' is available in the interview data
if (!empty($interviewData['submitted_by'])) {
    // If 'submitted_by' is not empty, fetch the name from rssimyaccount_members
    $submittedById = $interviewData['submitted_by']; // This holds the associatenumber
    $query = "SELECT fullname FROM rssimyaccount_members WHERE associatenumber = '$submittedById'";

    $result = pg_query($con, $query);
    if ($result && pg_num_rows($result) > 0) {
        $submittedBy = pg_fetch_assoc($result);
        $submittedByName = $submittedBy['fullname']; // Get the name from the database
    } else {
        $submittedByName = 'Information not found'; // If no match is found in the database
    }
} else {
    // If 'submitted_by' is empty, use default name and ID
    $submittedByName = $fullname;  // Default fullname
    $submittedById = $associatenumber; // Default associatenumber
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

    <title>Technical Interview</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
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
        .callout {
            padding: 1rem;
            margin: 1rem 0;
            border: 1px solid #eee;
            border-left-width: 5px;
            border-radius: 4px;
        }

        .callout-info {
            border-left-color: #5bc0de;
            background-color: #f4f8fa;
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <!-- Include Select2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>


</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>

                            <?php
                            if ($interview_id != null && $cmdtuples == 0) {
                                // Error handling: display a message when an error occurs
                            ?>
                                <div class="alert alert-danger alert-dismissible text-center" role="alert">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <span>Error: We encountered an error while updating the record. Please try again.</span>
                                </div>
                            <?php
                            } else if (@$cmdtuples == 1) {
                                // Success handling: display a confirmation message and redirect
                            ?>
                                <script>
                                    var applicationNumber = "<?php echo $_GET['applicationNumber_verify']; ?>";
                                    var interviewID = "<?php echo $interview_id; ?>";

                                    // Show an alert message with the reference ID
                                    alert("Assessment successfully submitted. Reference ID: " + interviewID);

                                    // Redirect to the updated record after the alert
                                    window.location.href = "technical_interview.php?applicationNumber_verify=" + applicationNumber;

                                    // Prevent resubmission after redirect
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                </script>
                            <?php
                            }
                            ?>

                            <div class="container">

                                <form id="lookupForm" method="GET">
                                    <!-- Application Number Input -->
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="applicationNumber_verify"
                                            name="applicationNumber_verify" placeholder="Enter your Application Number"
                                            value="<?php echo @htmlspecialchars($applicationNumberEscaped); ?>" required>
                                        <button type="submit" class="btn btn-primary">Fetch Applicant Data</button>
                                    </div>
                                </form>
                                <?php
                                // Check if responseData contains valid data and status is not 'no_records' or 'error'
                                if (!empty($responseData) && @$responseData['status'] != 'no_records' && @$responseData['status'] != 'error') {
                                    // Check if HR interview is scheduled or not
                                    if (empty($responseData['tech_interview_schedule'])) {
                                        // HR interview not scheduled yet
                                        echo "The Technical interview has not been scheduled yet for this application number.";
                                    } else { ?>
                                        <div id="detailsSection">
                                            <!-- Name Input -->
                                            <div class="card">
                                                <div class="card-body mt-3">
                                                    <div class="row align-items-center">
                                                        <!-- First Table (Contact details) -->
                                                        <div class="col-md-5">
                                                            <table style="width: 100%; border-collapse: collapse;">
                                                                <tbody>
                                                                    <tr>
                                                                        <td><strong>Applicant Name:</strong></td>
                                                                        <td><?php echo htmlspecialchars($responseData['applicantFullName']); ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Application Number:</strong></td>
                                                                        <td><?php echo htmlspecialchars($responseData['application_number']); ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Contact Number:</strong></td>
                                                                        <td><?php echo htmlspecialchars($responseData['contact']); ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Email:</strong></td>
                                                                        <td><?php echo htmlspecialchars($responseData['email']); ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Technical Interview Date:</strong></td>
                                                                        <td><?= !empty($responseData['tech_interview_schedule']) ? (new DateTime($responseData['tech_interview_schedule']))->format('d/m/Y h:i A') : '' ?></td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>

                                                        </div>

                                                        <!-- Second Table (Additional details) -->
                                                        <div class="col-md-4">
                                                            <table style="width: 100%; border-collapse: collapse;">
                                                                <tbody>
                                                                    <tr>
                                                                        <td><strong>Aadhar Card Number:</strong></td>
                                                                        <td>
                                                                            <?php
                                                                            $aadharNumber = !empty($responseData['aadhar_number']) ? $responseData['aadhar_number'] : "N/A";
                                                                            if ($aadharNumber !== "N/A" && strlen($aadharNumber) === 12) {
                                                                                $aadharNumber = substr($aadharNumber, 0, 2) . "XX-XXXX" . substr($aadharNumber, -4);
                                                                            }
                                                                            echo htmlspecialchars($aadharNumber);
                                                                            ?>
                                                                        </td>

                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Association Type:</strong></td>
                                                                        <td><?php echo htmlspecialchars($responseData['association_type']); ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Work Profile:</strong></td>
                                                                        <td><?php echo htmlspecialchars($responseData['position']); ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Subject Preference 1:</strong></td>
                                                                        <td>
                                                                            <?php echo htmlspecialchars(!empty($responseData['subject_preference_1']) ? $responseData['subject_preference_1'] : 'N/A'); ?>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td></td>
                                                                        <td>
                                                                            <ul class="list-group list-group-flush">
                                                                                <li class="list-group-item">
                                                                                    <a href="<?php echo htmlspecialchars($responseData['resumeLink']); ?>"
                                                                                        target="_blank"
                                                                                        class="btn btn-outline-primary btn-sm w-100 text-start">
                                                                                        <i class="bi bi-file-earmark-pdf"></i> View Applicant CV
                                                                                    </a>
                                                                                </li>
                                                                                <li class="list-group-item">
                                                                                    <a href="tap_doc_approval.php?application_number=<?php echo htmlspecialchars($responseData['application_number']); ?>"
                                                                                        class="btn btn-outline-success btn-sm w-100 text-start">
                                                                                        <i class="bi bi-file-earmark-check"></i> Centralized Document Verification System
                                                                                    </a>
                                                                                </li>
                                                                                <?php if ($documentVerification == true): ?>
                                                                                    <li class="list-group-item">
                                                                                        <a href="#"
                                                                                            id="getExamDetails"
                                                                                            class="btn btn-outline-info btn-sm w-100 text-start">
                                                                                            <i class="bi bi-info-circle"></i> Check RTET Information
                                                                                        </a>
                                                                                    </li>
                                                                                <?php endif; ?>
                                                                            </ul>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <!-- Candidate Photo (in the same row) -->
                                                        <div class="col-md-3 d-flex justify-content-center">
                                                            <div class="photo-box"
                                                                style="border: 1px solid #ccc; padding: 10px; width: 150px; height: 200px; display: flex; align-items: center; justify-content: center;"
                                                                id="candidatePhotoContainer">
                                                                <?php
                                                                if (!empty($responseData['photo'])) {
                                                                    // Extract photo ID from the Google Drive link using a regular expression
                                                                    $pattern = '/\/d\/([a-zA-Z0-9_-]+)/';
                                                                    if (preg_match($pattern, $responseData['photo'], $matches)) {
                                                                        $photoID = $matches[1]; // Extracted file ID
                                                                        $previewUrl = "https://drive.google.com/file/d/{$photoID}/preview";
                                                                        echo '<iframe src="' . $previewUrl . '" width="150" height="200" frameborder="0" allow="autoplay" sandbox="allow-scripts allow-same-origin"></iframe>';
                                                                    } else {
                                                                        // If no valid photo ID is found
                                                                        echo "Invalid Google Drive photo URL.";
                                                                    }
                                                                } else {
                                                                    // If photo is not provided
                                                                    echo "No photo available.";
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                            // Define an array mapping association types to required fields
                                            $associationRequirements = [
                                                'Intern' => [],
                                                'Employee' => ['subjectKnowledge', 'computerKnowledge', 'demoClass', 'writtenTest'],
                                                'Membership' => [],
                                                // Add more as needed
                                            ];

                                            // Get the current association type (assuming it's available in $row['association'])
                                            $currentAssociationType = $responseData['association_type'] ?? null;

                                            // Function to check if a field is required for the current association type
                                            function isRequired($fieldName, $associationRequirements, $currentAssociationType)
                                            {
                                                return $currentAssociationType && in_array($fieldName, $associationRequirements[$currentAssociationType] ?? []);
                                            }
                                            ?>
                                            <?php
                                            // Check if documentVerification is false or if there are missing or unverified documents
                                            if ($documentVerification != true) { ?>
                                                <div class="callout callout-info">
                                                    Please complete document verification before proceeding with the interview. If you have already completed it, please refresh the page.
                                                    <!-- <a href="tap_doc_approval.php?application_number=<?php echo $responseData['application_number']; ?>">Centralized Document Verification System</a> -->
                                                </div>
                                            <?php } ?>
                                            <?php if ($documentVerification === true): ?>
                                                <form id="applicationForm" action="#" method="POST">
                                                    <fieldset <?php echo $isFormDisabled; ?>>
                                                        <div class="container my-5">
                                                            <div class="row g-3 align-items-center">

                                                                <!-- Subject Knowledge -->
                                                                <div class="col-md-6">
                                                                    <label for="documents" class="form-label">Documents
                                                                        Checklist</label>
                                                                </div>
                                                                <?php
                                                                // Check if 'documentsList' exists and is not null, then split it; otherwise, use an empty array
                                                                $documentsListArray = isset($interviewDataResponse['documentsList']) && $interviewDataResponse['documentsList'] !== null
                                                                    ? explode(',', $interviewDataResponse['documentsList'])
                                                                    : [];
                                                                ?>

                                                                <div class="col-md-6">
                                                                    <label for="documents" class="form-label">Verified Documents</label>
                                                                    <select id="documents" name="documents[]" class="form-control" multiple="multiple" required>
                                                                        <option value="highschool" <?php echo (isset($uploadedFiles['highschool']) && $uploadedFiles['highschool'] === 'Verified') ? 'selected' : ''; ?>>Highschool Marksheet</option>
                                                                        <option value="intermediate" <?php echo (isset($uploadedFiles['intermediate']) && $uploadedFiles['intermediate'] === 'Verified') ? 'selected' : ''; ?>>Intermediate Marksheet</option>
                                                                        <option value="graduation" <?php echo (isset($uploadedFiles['graduation']) && $uploadedFiles['graduation'] === 'Verified') ? 'selected' : ''; ?>>Graduation Marksheet</option>
                                                                        <option value="post_graduation" <?php echo (isset($uploadedFiles['post_graduation']) && $uploadedFiles['post_graduation'] === 'Verified') ? 'selected' : ''; ?>>Post-Graduation Marksheet</option>
                                                                        <option value="additional_certificate" <?php echo (isset($uploadedFiles['additional_certificate']) && $uploadedFiles['additional_certificate'] === 'Verified') ? 'selected' : ''; ?>>Additional training or course Certificate</option>
                                                                        <option value="previous_employment_information" <?php echo (isset($uploadedFiles['previous_employment_information']) && $uploadedFiles['previous_employment_information'] === 'Verified') ? 'selected' : ''; ?>>Previous employment information</option>
                                                                        <option value="pan_card" <?php echo (isset($uploadedFiles['pan_card']) && $uploadedFiles['pan_card'] === 'Verified') ? 'selected' : ''; ?>>PAN Card</option>
                                                                        <option value="aadhar_card" <?php echo (isset($uploadedFiles['aadhar_card']) && $uploadedFiles['aadhar_card'] === 'Verified') ? 'selected' : ''; ?>>Aadhar Card</option>
                                                                    </select>
                                                                </div>

                                                                <?php
                                                                $fields = [
                                                                    'subjectKnowledge' => 'Subject Knowledge',
                                                                    'computerKnowledge' => 'Computer Knowledge',
                                                                    'demoClass' => 'Demo Class Performance'
                                                                ];

                                                                foreach ($fields as $field => $label) {
                                                                ?>
                                                                    <div class="col-md-6">
                                                                        <label for="<?php echo $field; ?>" class="form-label"><?php echo $label; ?></label>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <input
                                                                            type="number"
                                                                            class="form-control"
                                                                            name="<?php echo $field; ?>"
                                                                            id="<?php echo $field; ?>"
                                                                            min="1"
                                                                            max="10"
                                                                            placeholder="Enter marks (1-10)"
                                                                            value="<?php echo isset($interviewDataResponse[$field]) ? htmlspecialchars($interviewDataResponse[$field]) : ''; ?>"
                                                                            <?php echo isRequired($field, $associationRequirements, $currentAssociationType) ? 'required' : ''; ?>>
                                                                    </div>
                                                                <?php
                                                                }
                                                                ?>

                                                                <!-- Written Test Marks -->
                                                                <div class="col-md-6">
                                                                    <label for="writtenTest" class="form-label">Written Test Marks (RTET)</label>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="input-group mb-2">
                                                                        <input type="number" class="form-control" name="writtenTest" id="writtenTest" placeholder="Enter marks"
                                                                            value="<?php echo isset($interviewDataResponse['writtenTest']) ? htmlspecialchars($interviewDataResponse['writtenTest']) : ''; ?>"
                                                                            <?php echo isRequired('writtenTest', $associationRequirements, $currentAssociationType) ? 'required' : ''; ?>>
                                                                        <button type="button" id="fetchWrittenTest" class="btn btn-primary">Fetch</button>
                                                                    </div>
                                                                </div>

                                                                <!-- Experience and Qualifications -->
                                                                <div class="col-md-6">
                                                                    <label for="experience" class="form-label">Experience and
                                                                        Qualifications</label>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <textarea class="form-control" name="experience" id="experience"
                                                                        rows="3" placeholder="Enter details"
                                                                        required><?php echo isset($interviewDataResponse['experience']) ? htmlspecialchars($interviewDataResponse['experience']) : ''; ?></textarea>
                                                                </div>

                                                                <!-- Remarks -->
                                                                <div class="col-md-6">
                                                                    <label for="remarks" class="form-label">Remarks</label>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <textarea class="form-control" name="remarks" id="remarks" rows="3"
                                                                        placeholder="Enter remarks"
                                                                        required><?php echo isset($interviewDataResponse['remarks']) ? htmlspecialchars($interviewDataResponse['remarks']) : ''; ?></textarea>
                                                                </div>

                                                                <!-- Interviewer Panel Section -->
                                                                <div class="row border p-3 rounded mt-5">

                                                                    <div class="col-md-12 mt-3">
                                                                        <table class="table table-bordered" id="interviewer_table">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>Employee No.</th>
                                                                                    <th>Name</th>
                                                                                    <th>Designation</th>
                                                                                    <th>Action</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                <!-- Dynamic rows will be added here -->
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                    <div class="col-md-12 mb-3">
                                                                        <div class="row">
                                                                            <div class="col-md-4">
                                                                                <input type="text" id="employee_no" class="form-control"
                                                                                    placeholder="Enter Employee ID for multiple Interviewers">
                                                                            </div>
                                                                            <div class="col-md-4">
                                                                                <button type="button" id="add_interviewer"
                                                                                    class="btn btn-primary">Add Interviewer</button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <input type="hidden" id="interviewer_ids" name="interviewer_ids">
                                                                <div class="row mt-3">
                                                                    <div class="col-md-12">
                                                                        <label class="form-label">Interview Assessment filled by: </label>
                                                                        <span class="fw-normal">
                                                                            <?php
                                                                            // Display name and ID in the format "Name (ID)"
                                                                            echo $submittedByName . '&nbsp;(' . $submittedById . ')';
                                                                            ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="row mt-3">
                                                                    <div class="col-md-6">
                                                                        <label for="interview_duration" class="form-label">Interview
                                                                            Duration:</label>
                                                                        <input type="number" name="interview_duration"
                                                                            id="interview_duration" class="form-control"
                                                                            placeholder="Minutes"
                                                                            value="<?php echo isset($interviewDataResponse['interview_duration']) ? htmlspecialchars($interviewDataResponse['interview_duration']) : ''; ?>" required>
                                                                    </div>
                                                                </div>

                                                                <div class="mb-3 form-check">
                                                                    <input type="checkbox" class="form-check-input" id="declaration" name="declaration"
                                                                        <?php
                                                                        // Check if $interviewDataResponse is not null and contains the 'declaration' key before accessing it
                                                                        echo (isset($interviewDataResponse['declaration']) && $interviewDataResponse['declaration'] == true) ? 'checked' : '';
                                                                        ?>
                                                                        required>
                                                                    <label class="form-check-label" for="declaration">
                                                                        I accept that I have read the terms of agreement and agree to abide by the terms and conditions mentioned therein.
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <!-- Submit Button -->
                                                            <div class="text-center mt-4">
                                                                <button type="submit" id="submit_form" class="btn btn-primary">Submit</button>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                <?php }
                                } else {
                                    // Show message when application number is not found
                                    echo "Please enter a valid application number to view the data.";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->
    <!-- Modal -->
    <div class="modal fade" id="examDetailsModal" tabindex="-1" aria-labelledby="examDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="examDetailsModalLabel">Exam Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading exam details...</p>
                    </div>

                    <!-- Exam Details Content -->
                    <div id="examDetailsContent" style="display: none;">
                        <p><strong>Exam Name:</strong> <span id="examName"></span></p>
                        <p><strong>Session ID:</strong> <span id="sessionId"></span></p>
                        <p><strong>OTP:</strong> <span id="otp" class="text-danger"></span></p>
                        <p><strong>Test Result:</strong> <span id="testResult"></span></p>
                        <a id="examAnalysisLink" href="#" target="_blank" class="btn btn-primary mt-3" style="display: none;">
                            View Exam Analysis
                        </a>
                    </div>

                    <!-- Instructions for Interviewer -->
                    <div class="mt-4">
                        <h6>Instructions for Interviewer:</h6>
                        <ol>
                            <li>Ask the candidate to log in to their profile and navigate to the <strong>Assessment Section</strong> to start the exam.</li>
                            <li>Before sharing the OTP, ensure the candidate's exam setup meets the required standards.</li>
                            <li>Only after confirming the exam setup is correct, share the OTP with the candidate.</li>
                            <li>Remind the candidate that the exam is being monitored, and any suspicious activity may result in disqualification.</li>
                        </ol>
                    </div>
                </div>
                <div class="modal-footer">
                    <!-- Refresh Button -->
                    <button type="button" id="refreshButton" class="btn btn-info">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Select all input elements with the 'required' attribute
            const requiredFields = document.querySelectorAll('input[required], select[required], textarea[required]');

            // Loop through each required field
            requiredFields.forEach(function(field) {
                // Get the label associated with the field
                const label = document.querySelector(`label[for="${field.id}"]`);

                // If label exists, add the asterisk
                if (label) {
                    label.innerHTML = label.innerHTML + ' <span style="color:red;">*</span>';
                }
            });
        }
    </script>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>


    <script>
        $(document).ready(function() {
            const interviewerIds = []; // Array to hold the IDs of interviewers

            // Add interviewer
            $('#add_interviewer').on('click', function() {
                const employeeNo = $('#employee_no').val();

                if (!employeeNo) {
                    alert('Please enter an Employee No.');
                    return;
                }

                // Disable button and show loading text
                $('#add_interviewer').prop('disabled', true).text('Loading...');

                // Fetch employee details via AJAX
                $.ajax({
                    url: 'payment-api.php',
                    type: 'POST',
                    data: {
                        'form-type': 'fetch_employee',
                        employee_no: employeeNo
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const {
                                id,
                                name,
                                position
                            } = response.data;

                            // Check if interviewer is already added
                            if (interviewerIds.includes(id)) {
                                alert('This interviewer has already been added.');
                            } else {
                                // Add the interviewer ID to the array
                                interviewerIds.push(id);

                                // Create a new row for the added interviewer
                                const newRow = `
                            <tr id="row-${id}">
                                <td>${id}</td>
                                <td>${name}</td>
                                <td>${position}</td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-row" data-id="${id}">X</button></td>
                            </tr>
                        `;
                                $('#interviewer_table tbody').append(newRow);
                                $('#employee_no').val(''); // Clear the input
                            }

                            // Update the hidden input field with the current list of interviewer IDs
                            $('#interviewer_ids').val(interviewerIds.join(','));
                        } else {
                            alert(response.message || 'Unable to fetch employee details.');
                        }
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON?.message || 'An error occurred while fetching employee details.';
                        alert(errorMessage);
                    },
                    complete: function() {
                        $('#add_interviewer').prop('disabled', false).text('Add Interviewer');
                    },
                });
            });

            // Remove interviewer
            $('#interviewer_table').on('click', '.remove-row', function() {
                const id = $(this).data('id');

                // Remove the row from the table
                $(`#row-${id}`).remove();

                // Remove the interviewer ID from the array
                const index = interviewerIds.indexOf(id);
                if (index > -1) {
                    interviewerIds.splice(index, 1);
                }

                // Update the hidden input field with the current list of interviewer IDs
                $('#interviewer_ids').val(interviewerIds.join(','));
            });
        });
        document.getElementById('submit_form').addEventListener('click', function(event) {
            var interviewerIds = document.getElementById('interviewer_ids').value;
            if (!interviewerIds) {
                alert('At least one interviewer is required!');
                event.preventDefault(); // Prevent form submission
            }
        });
    </script>
    <script>
        // Embed PHP data into JavaScript
        var responseData = <?php echo json_encode($responseData); ?>;

        // Check if interviewers data exists and populate the table
        if (responseData.interview_data && responseData.interview_data.interviewers) {
            const interviewers = responseData.interview_data.interviewers;

            const interviewerTableBody = document.querySelector('#interviewer_table tbody');

            // Loop through interviewers and create rows for each
            interviewers.forEach(interviewer => {
                const row = document.createElement('tr');
                row.innerHTML = `
            <td>${interviewer.id}</td>
            <td>${interviewer.name}</td>
            <td>${interviewer.position}</td>
            <td></td>
        `;
                interviewerTableBody.appendChild(row);
            });
        }

        // Function to remove an interviewer from the table (if needed)
        function removeInterviewer(button) {
            const row = button.closest('tr');
            row.remove();
        }
    </script>
    <script>
        $(document).ready(function() {
            // When the fetch button is clicked
            document.getElementById('fetchWrittenTest').addEventListener('click', function() {
                const applicationNumber = "<?php echo $responseData['application_number']; ?>"; // Use the application number from PHP

                // Check if application number is valid
                if (!applicationNumber) {
                    alert("Application number is missing.");
                    return;
                }

                // Show loading spinner
                const fetchButton = document.getElementById('fetchWrittenTest');
                fetchButton.innerHTML = `
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading...
            `;
                fetchButton.disabled = true; // Disable the button during loading

                // Make AJAX request to fetch written test data
                $.ajax({
                    url: 'payment-api.php',
                    type: 'POST',
                    data: {
                        'form-type': 'fetch_rtet', // Form type to specify the action
                        'application_number': applicationNumber // Send the application number
                    },
                    dataType: 'json', // Expect JSON response
                    success: function(response) {
                        console.log("Response received:", response); // Log the entire response for debugging

                        // Reset the button text and enable it
                        fetchButton.innerHTML = 'Fetch';
                        fetchButton.disabled = false;

                        // Check if the response status is 'success'
                        if (response.status === 'success') {
                            // Convert writtenTest to number if it's not already
                            var writtenTestValue = parseFloat(response.writtenTest);

                            console.log("Setting writtenTest value to:", writtenTestValue); // Log the value to be set
                            document.getElementById('writtenTest').value = writtenTestValue; // Set the fetched value in the input field
                        } else {
                            // If no data or error in response
                            alert(response.message || 'No data found for this application number.');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Reset the button text and enable it
                        fetchButton.innerHTML = 'Fetch';
                        fetchButton.disabled = false;

                        // Handle AJAX errors
                        console.error('AJAX Error:', error);
                        alert('An error occurred while fetching data.');
                    }
                });
            });
        });
    </script>
    <script>
        // Function to fetch exam details
        function fetchExamDetails() {
            // Get the application number from the response data
            const applicationNumber = "<?php echo $responseData['application_number']; ?>";

            // Show the "Loading..." indicator and hide the exam details
            document.getElementById('loadingIndicator').style.display = 'block';
            document.getElementById('examDetailsContent').style.display = 'none';

            // Make an AJAX request to fetch exam details
            fetch('get_exam_details.php?application_number=' + applicationNumber)
                .then(response => response.json())
                .then(data => {
                    const examNameSpan = document.getElementById('examName');
                    const sessionIdSpan = document.getElementById('sessionId');
                    const otpSpan = document.getElementById('otp');
                    const testResultSpan = document.getElementById('testResult');
                    const examAnalysisLink = document.getElementById('examAnalysisLink');

                    if (data.success) {
                        // Display the exam details in the modal
                        examNameSpan.textContent = data.examName;
                        sessionIdSpan.textContent = data.sessionId;
                        otpSpan.textContent = data.otp;

                        // Handle status-based messages and links
                        if (data.status === 'pending') {
                            testResultSpan.textContent = 'Exam has not started yet.';
                            examAnalysisLink.style.display = 'none'; // Hide the link
                        } else if (data.status === 'active') {
                            testResultSpan.textContent = 'Exam is still in progress.';
                            examAnalysisLink.style.display = 'none'; // Hide the link
                        } else if (data.status === 'submitted') {
                            testResultSpan.textContent = 'Exam completed. Click below to view results.';
                            examAnalysisLink.href = `redirect_iexplore.php?path=exam_analysis&session_name=${data.sessionId}&auth_code=${data.otp}`;
                            examAnalysisLink.style.display = 'block'; // Show the link
                        } else {
                            testResultSpan.textContent = 'Invalid exam status.';
                            examAnalysisLink.style.display = 'none'; // Hide the link
                        }
                    } else {
                        // Show "Exam not created yet" message
                        examNameSpan.textContent = 'Exam not created yet';
                        sessionIdSpan.textContent = 'N/A';
                        otpSpan.textContent = 'N/A';
                        testResultSpan.textContent = 'N/A';
                        examAnalysisLink.style.display = 'none'; // Hide the link
                    }

                    // Hide the "Loading..." indicator and show the exam details
                    document.getElementById('loadingIndicator').style.display = 'none';
                    document.getElementById('examDetailsContent').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error fetching exam details:', error);

                    // Hide the "Loading..." indicator and show an error message
                    document.getElementById('loadingIndicator').style.display = 'none';
                    document.getElementById('examDetailsContent').style.display = 'block';
                    document.getElementById('examName').textContent = 'Error fetching details';
                    document.getElementById('sessionId').textContent = 'N/A';
                    document.getElementById('otp').textContent = 'N/A';
                    document.getElementById('testResult').textContent = 'An error occurred. Please try again.';
                    document.getElementById('examAnalysisLink').style.display = 'none'; // Hide the link
                });
        }

        // Event listener for the "Check RTET Information" link
        document.getElementById('getExamDetails').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent the default link behavior

            // Show the modal immediately with the "Loading..." indicator
            const examDetailsModal = new bootstrap.Modal(document.getElementById('examDetailsModal'));
            examDetailsModal.show();

            // Fetch exam details
            fetchExamDetails();
        });

        // Event listener for the refresh button
        document.getElementById('refreshButton').addEventListener('click', function() {
            // Fetch exam details
            fetchExamDetails();
        });
    </script>
</body>

</html>