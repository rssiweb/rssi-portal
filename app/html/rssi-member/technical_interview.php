<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

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
                'interview_timestamp' => $row['interview_timestamp'],
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

    // Execute the query
    $result = pg_query($con, $insert_query);
    $cmdtuples = pg_affected_rows($result);
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
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
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
            <h1>Technical Interview</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">People Plus</a></li>
                    <li class="breadcrumb-item"><a href="interview_central.php">Interview Central</a></li>
                    <li class="breadcrumb-item active">Technical Interview</li>
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
                                    if (empty($responseData['interview_timestamp'])) {
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
                                                                            <a href="<?php echo htmlspecialchars($responseData['resumeLink']); ?>"
                                                                                target="_blank" id="resumeText">View
                                                                                Applicant CV</a><br>
                                                                                <a href="tap_doc_approval.php?application_number=<?php echo htmlspecialchars($responseData['application_number']); ?>"
                                                                                target="_blank" id="resumeText">Centralized Document Verification System</a>
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
                                                                    // Extract photo ID from the Google Drive link
                                                                    $photoID = explode('id=', $responseData['photo'])[1];
                                                                    $previewUrl = "https://drive.google.com/file/d/{$photoID}/preview";
                                                                    echo '<iframe src="' . $previewUrl . '" width="150" height="200" frameborder="0" allow="autoplay" sandbox="allow-scripts allow-same-origin"></iframe>';
                                                                } else {
                                                                    echo "No photo available";
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
                                                                <select id="documents" name="documents[]" class="form-control"
                                                                    multiple="multiple" required>
                                                                    <option value="highschool_marksheet" <?php echo in_array('highschool_marksheet', $documentsListArray) ? 'selected' : ''; ?>>Highschool Marksheet</option>
                                                                    <option value="intermediate_marksheet" <?php echo in_array('intermediate_marksheet', $documentsListArray) ? 'selected' : ''; ?>>Intermediate Marksheet</option>
                                                                    <option value="graduation_marksheet" <?php echo in_array('graduation_marksheet', $documentsListArray) ? 'selected' : ''; ?>>Graduation Marksheet</option>
                                                                    <option value="post_graduation_marksheet" <?php echo in_array('post_graduation_marksheet', $documentsListArray) ? 'selected' : ''; ?>>Post-Graduation Marksheet</option>
                                                                    <option value="additional_training_course_certificate" <?php echo in_array('additional_training_course_certificate', $documentsListArray) ? 'selected' : ''; ?>>Additional
                                                                        training or course Certificate</option>
                                                                    <option value="previous_employment_info" <?php echo in_array('previous_employment_info', $documentsListArray) ? 'selected' : ''; ?>>Previous employment information</option>
                                                                    <option value="pan_card" <?php echo in_array('pan_card', $documentsListArray) ? 'selected' : ''; ?>>PAN Card</option>
                                                                    <option value="aadhar_card" <?php echo in_array('aadhar_card', $documentsListArray) ? 'selected' : ''; ?>>Aadhar Card
                                                                    </option>
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
                                                                <div class="input-group">
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
                        // Handle AJAX errors
                        console.error('AJAX Error:', error);
                        alert('An error occurred while fetching data.');
                    }
                });
            });
        });
    </script>

</body>

</html>