<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}
validation();

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Initialize connection to PostgreSQL database
    // Assuming $con is the connection object

    // Check for connection errors
    if (!$con) {
        die("Connection failed: " . pg_last_error());
    }

    try {
        // Start transaction
        pg_query($con, 'BEGIN');

        // Retrieve common survey data
        $family_id = uniqid();
        $parentName = $_POST['parentName'];
        $address = $_POST['address'];
        $contact = $_POST['contact'];
        $altcontact = $_POST['altcontact'];
        $houseStay = $_POST['houseStay'];
        $familyMembers = $_POST['familyMembers'];
        $earningSource = $_POST['earningSource'];
        $otherEarningSourceInput = $_POST['otherEarningSourceInput'];
        $additionalInfo = $_POST['additionalInfo'];
        $interestInAdmission = $_POST['interestInAdmission'];
        $surveyorId = $associatenumber;
        $timestamp = date("Y-m-d H:i:s");

        // Citizen services data
        $needAssistance = $_POST['needAssistance'];
        $servicesNeeded = isset($_POST['servicesNeeded']) ? $_POST['servicesNeeded'] : [];
        $otherService = isset($_POST['otherService']) ? $_POST['otherService'] : null;
        $bookAppointment = isset($_POST['bookAppointment']) ? $_POST['bookAppointment'] : 'no';

        // Job assistance data
        $needJobAssistance = isset($_POST['needJobAssistance']) ? $_POST['needJobAssistance'] : 'no';

        // Process "Other" service if selected
        if (in_array('Other', $servicesNeeded) && !empty($otherService)) {
            // Replace "Other" with the specified service
            $servicesNeeded = array_filter($servicesNeeded, function ($value) {
                return $value !== 'Other';
            });
            $servicesNeeded[] = $otherService;
        }

        // Build the SQL query for survey data insertion
        $query = "INSERT INTO survey_data (
                    parent_name, address, contact, house_stay, family_members, 
                    earning_source, additional_info, surveyor_id, family_id, 
                    alt_contact, interest_in_admission, timestamp, other_earning_source_input,
                    need_assistance, services_needed, book_appointment, need_job_assistance
                ) VALUES (
                    $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17
                )";

        // Execute the query for survey data insertion
        $result = pg_query_params($con, $query, array(
            $parentName,
            $address,
            $contact,
            $houseStay,
            $familyMembers,
            $earningSource,
            $additionalInfo,
            $surveyorId,
            $family_id,
            $altcontact,
            $interestInAdmission,
            $timestamp,
            $otherEarningSourceInput,
            $needAssistance,
            json_encode($servicesNeeded),
            $bookAppointment,
            $needJobAssistance
        ));

        // Check if the query was successful
        if (!$result) {
            throw new Exception("Failed to insert survey data: " . pg_last_error($con));
        }

        // Check if we need to create a public health record and appointment
        if ($needAssistance === 'yes' && $bookAppointment === 'yes' && !empty($servicesNeeded)) {
            // Get personal info for appointment
            $dob = $_POST['dob'] ?? null;
            $gender = $_POST['gender'] ?? null;
            $email = $_POST['email'] ?? null;
            $appointmentDate = $_POST['appointmentDate'] ?? null;
            $appointmentTime = $_POST['appointmentTime'] ?? null;
            $photoData = $_POST['photo_data'] ?? null;

            // Handle photo upload if exists
            $photoUrl = null;
            if (!empty($photoData)) {
                $photoData = str_replace('data:image/jpeg;base64,', '', $photoData);
                $photoData = str_replace(' ', '+', $photoData);
                $data = base64_decode($photoData);

                // Create a temporary file
                $tempFileName = 'temp_profile_' . $contact . '_' . time() . '.jpg';
                $tempFilePath = sys_get_temp_dir() . '/' . $tempFileName;
                file_put_contents($tempFilePath, $data);

                // Prepare file for Google Drive upload
                $uploadedFile = [
                    'name' => 'profile_' . $contact . '_' . time() . '.jpg',
                    'type' => 'image/jpeg',
                    'tmp_name' => $tempFilePath,
                    'error' => 0,
                    'size' => filesize($tempFilePath)
                ];

                // Google Drive folder ID where you want to store photos
                $parentFolderId = '1LtKZNkfWzxrgMTN2GSHF1O-d6AmFsLRD';

                // Upload to Google Drive
                $photoUrl = uploadeToDrive($uploadedFile, $parentFolderId, 'profile_' . $contact);

                // Delete the temporary file
                unlink($tempFilePath);
            }

            // Insert into public_health_records
            $phrQuery = "INSERT INTO public_health_records (
                            contact_number, name, email, date_of_birth, 
                            gender, referral_source, profile_photo, 
                            registration_completed, created_at
                        ) VALUES (
                            $1, $2, $3, $4, $5, $6, $7, true, $8
                        ) RETURNING id";

            $phrResult = pg_query_params($con, $phrQuery, array(
                $contact,
                $parentName,
                $email,
                $dob,
                $gender,
                'Survey',
                $photoUrl,
                $timestamp
            ));

            if (!$phrResult) {
                throw new Exception("Failed to insert public health record: " . pg_last_error($con));
            }

            $phrRow = pg_fetch_assoc($phrResult);
            $beneficiary_id = $phrRow['id'];

            // Create appointment
            if (!empty($appointmentDate) && !empty($appointmentTime)) {
                $appointmentFor = implode(', ', $servicesNeeded);

                $appointmentQuery = "INSERT INTO appointments (
                                        beneficiary_id, appointment_for, 
                                        appointment_date, appointment_time, created_by
                                    ) VALUES (
                                        $1, $2, $3, $4, $5
                                    )";

                $appointmentResult = pg_query_params($con, $appointmentQuery, array(
                    $beneficiary_id,
                    $appointmentFor,
                    $appointmentDate,
                    $appointmentTime,
                    $associatenumber
                ));

                if (!$appointmentResult) {
                    throw new Exception("Failed to insert appointment: " . pg_last_error($con));
                }
            }
        }

        // Check if student data is available and interest in admission is "Yes"
        if (isset($_POST['students']) && is_array($_POST['students']) && count($_POST['students']) > 0 && $interestInAdmission === "yes") {
            // Insert each student's data into the student_data table
            foreach ($_POST['students'] as $student) {
                // Skip empty student records
                if (empty($student['name'])) {
                    continue;
                }

                // Ensure that array keys exist before accessing them
                $gender = isset($student['gender']) ? $student['gender'] : null;
                $grade = isset($student['grade']) ? $student['grade'] : null;
                $alreadyGoingSchool = isset($student['already_going_school']) ? $student['already_going_school'] : null;
                $alreadyCoaching = isset($student['already_coaching']) ? $student['already_coaching'] : null;

                // Build the SQL query for student data insertion
                $studentQuery = "INSERT INTO student_data (family_id, student_name, age, gender, grade, already_going_school, school_type, already_coaching, coaching_name) 
                                    VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";

                // Execute the query for student data insertion
                $studentResult = pg_query_params($con, $studentQuery, array(
                    $family_id,
                    $student['name'],
                    $student['age'],
                    $gender,
                    $grade,
                    $alreadyGoingSchool,
                    isset($student['school_type']) ? $student['school_type'] : null,
                    $alreadyCoaching,
                    isset($student['coaching_name']) ? $student['coaching_name'] : null
                ));

                // Check if the query was successful
                if (!$studentResult) {
                    throw new Exception("Failed to insert student data: " . pg_last_error($con));
                }
            }
        }

        // Check if job seeker data is available and needJobAssistance is "Yes"
        if (isset($_POST['jobSeekers']) && is_array($_POST['jobSeekers']) && count($_POST['jobSeekers']) > 0 && $needJobAssistance === "yes") {

            // Insert each job seeker's data into the job_seeker_data table
            foreach ($_POST['jobSeekers'] as $jobSeeker) {
                // Skip empty rows (in case user added but didn't fill)
                if (empty($jobSeeker['name']) && empty($jobSeeker['contact'])) {
                    continue;
                }

                // Ensure that array keys exist before accessing them
                $name = isset($jobSeeker['name']) ? pg_escape_string($con, $jobSeeker['name']) : null;
                $dob = isset($jobSeeker['dob']) ? $jobSeeker['dob'] : null;
                $email = isset($jobSeeker['email']) ? pg_escape_string($con, $jobSeeker['email']) : null;
                $contact = isset($jobSeeker['contact']) ? pg_escape_string($con, $jobSeeker['contact']) : null;
                $education = isset($jobSeeker['education']) ? pg_escape_string($con, $jobSeeker['education']) : null;
                $skills = isset($jobSeeker['skills']) ? pg_escape_string($con, $jobSeeker['skills']) : null;
                $preferences = isset($jobSeeker['preferences']) ? pg_escape_string($con, $jobSeeker['preferences']) : null;

                // Validate required fields
                if (empty($name) || empty($dob) || empty($contact) || empty($education)) {
                    continue; // Skip incomplete records
                }

                // Build the SQL query for job seeker data insertion
                $jobSeekerQuery = "INSERT INTO job_seeker_data (
                                    family_id, name, dob, contact, education, 
                                    skills, preferences, created_at, email
                                ) VALUES (
                                    $1, $2, $3, $4, $5, $6, $7, $8, $9
                                )";

                // Execute the query for job seeker data insertion
                $jobSeekerResult = pg_query_params($con, $jobSeekerQuery, array(
                    $family_id,
                    $name,
                    $dob,
                    $contact,
                    $education,
                    $skills,
                    $preferences,
                    $timestamp,
                    $email
                ));

                // Check if the query was successful
                if (!$jobSeekerResult) {
                    throw new Exception("Failed to insert job seeker data: " . pg_last_error($con));
                }
            }
        }

        // Commit the transaction if all data was inserted successfully
        pg_query($con, 'COMMIT');

        // Display success message and redirect
        echo "<script>alert('Data added successfully.'); window.location.href = 'survey.php';</script>";
    } catch (Exception $e) {
        // Rollback transaction on error
        pg_query($con, 'ROLLBACK');

        // Display error message
        echo "Error: " . $e->getMessage();
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

    <title>Create Survey</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
</head>

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
                            <div class="row justify-content-center">
                                <div class="col-md-8">
                                    <div class="form-container">
                                        <!-- <h1 class="text-center mb-4">Potential Student Survey</h1> -->
                                        <form method="post"
                                            action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                                            id="surveyForm" onsubmit="return validateForm()">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h5 class="card-title">Basic Information</h5>
                                                    <!-- Parent's Name -->
                                                    <div class="mb-3">
                                                        <label for="parentName" class="form-label">Respondent Name</label>
                                                        <input type="text" class="form-control" id="parentName"
                                                            name="parentName" placeholder="Enter respondent name" required>
                                                    </div>
                                                    <!-- Address -->
                                                    <div class="mb-3">
                                                        <label for="address" class="form-label">Address</label>
                                                        <div class="input-group">
                                                            <textarea class="form-control" id="address" name="address" rows="3"
                                                                placeholder="Enter address" required></textarea>
                                                            <button class="btn btn-outline-secondary" type="button"
                                                                id="getAddressBtn">Get Current Address</button>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <!-- Contact Number -->
                                                        <div class="col-md-6 mb-3">
                                                            <label for="contact" class="form-label">Contact Number</label>
                                                            <input type="tel" class="form-control" id="contact" name="contact"
                                                                placeholder="Enter contact number" pattern="[0-9]{10}"
                                                                title="Please enter a valid 10-digit contact number" required>
                                                        </div>

                                                        <!-- Alternative Contact Number -->
                                                        <div class="col-md-6 mb-3">
                                                            <label for="altcontact" class="form-label">Alternative Contact
                                                                Number</label>
                                                            <input type="tel" class="form-control" id="altcontact"
                                                                name="altcontact" placeholder="Enter alternative contact number"
                                                                pattern="[0-9]{10}"
                                                                title="Please enter a valid 10-digit alternative contact number">
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="houseStay">How long does the family stay in the current
                                                            house?</label>
                                                        <select class="form-select" id="houseStay" name="houseStay">
                                                            <option disabled selected>Select duration</option>
                                                            <option value="<1">Less than 1 year</option>
                                                            <option value="1">1 year</option>
                                                            <option value="2">2 years</option>
                                                            <option value="3">3 years</option>
                                                            <option value="4">4 years</option>
                                                            <option value="5">5 years</option>
                                                            <option value=">5">More than 5 years</option>
                                                        </select>
                                                    </div>

                                                    <!-- Total number of family members -->
                                                    <div class="mb-3">
                                                        <label for="familyMembers">Total number of family members:</label>
                                                        <input type="number" class="form-control" id="familyMembers"
                                                            name="familyMembers" placeholder="Enter total number of family members">
                                                    </div>

                                                    <!-- Main source of earning for the family -->
                                                    <div class="mb-3">
                                                        <label for="earningSource">Main source of earning for the
                                                            family:</label>
                                                        <select class="form-select" id="earningSource" name="earningSource"
                                                            onchange="checkOtherOption()">
                                                            <option selected disabled>Select an option</option>
                                                            <option value="agriculture">Agriculture</option>
                                                            <option value="business">Business</option>
                                                            <option value="government_job">Government Job</option>
                                                            <option value="private_job">Private Job</option>
                                                            <option value="Construction Worker">Construction Worker</option>
                                                            <option value="Electrician">Electrician</option>
                                                            <option value="Plumber">Plumber</option>
                                                            <option value="Carpenter">Carpenter</option>
                                                            <option value="Welder">Welder</option>
                                                            <option value="Mechanic">Mechanic</option>
                                                            <option value="Painter">Painter</option>
                                                            <option value="Mason">Mason</option>
                                                            <option value="Roofer">Roofer</option>
                                                            <option value="Landscaper">Landscaper</option>
                                                            <option value="Janitor">Janitor</option>
                                                            <option value="Factory Worker">Factory Worker</option>
                                                            <option value="Warehouse Worker">Warehouse Worker</option>
                                                            <option value="Truck Driver">Truck Driver</option>
                                                            <option value="Delivery Driver">Delivery Driver</option>
                                                            <option value="other">Other</option>
                                                        </select>
                                                        <div id="otherEarningSource" style="display: none;">
                                                            <label for="otherEarningSourceInput">Enter other source:</label>
                                                            <input type="text" class="form-control" id="otherEarningSourceInput"
                                                                name="otherEarningSourceInput">
                                                        </div>

                                                        <!-- Additional Information -->
                                                        <div class="mb-3">
                                                            <label for="additionalInfo" class="form-label">Additional
                                                                Information</label>
                                                            <textarea class="form-control" id="additionalInfo"
                                                                name="additionalInfo" rows="3"
                                                                placeholder="Enter any additional information"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Citizen Services Section -->
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Citizen Services Assistance</h5>

                                                    <!-- Need Assistance -->
                                                    <div class="mb-3">
                                                        <label for="needAssistance" class="form-label">Do you need any assistance regarding Citizen Services (Aadhar, PAN, Shram Card, ABHA, etc.)?</label>
                                                        <select class="form-select" id="needAssistance" name="needAssistance" onchange="toggleAppointmentSection()" required>
                                                            <option value="" selected disabled>Select option</option>
                                                            <option value="no">No</option>
                                                            <option value="yes">Yes</option>
                                                        </select>
                                                    </div>

                                                    <!-- Services Needed -->
                                                    <div id="servicesNeededSection" style="display: none;" class="mb-3">
                                                        <label for="servicesNeeded" class="form-label">Which services do you need assistance with? (Select all that apply)</label>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="serviceAadhar" name="servicesNeeded[]" value="Aadhar Card">
                                                            <label class="form-check-label" for="serviceAadhar">Aadhar Card</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="servicePAN" name="servicesNeeded[]" value="PAN Card">
                                                            <label class="form-check-label" for="servicePAN">PAN Card</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="serviceShram" name="servicesNeeded[]" value="Shram Card">
                                                            <label class="form-check-label" for="serviceShram">Shram Card</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="serviceABHA" name="servicesNeeded[]" value="ABHA Card">
                                                            <label class="form-check-label" for="serviceABHA">ABHA Card</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="serviceOther" name="servicesNeeded[]" value="Other">
                                                            <label class="form-check-label" for="serviceOther">Other (please specify)</label>
                                                            <input type="text" class="form-control mt-2" id="otherService" name="otherService" style="display: none;">
                                                        </div>
                                                    </div>

                                                    <!-- Book Appointment -->
                                                    <div id="bookAppointmentSection" style="display: none;" class="mb-3">
                                                        <label for="bookAppointment" class="form-label">Would you like to book an appointment for these services?</label>
                                                        <select class="form-select" id="bookAppointment" name="bookAppointment" onchange="togglePersonalInfoSection()">
                                                            <option value="" selected disabled>Select option</option>
                                                            <option value="no">No</option>
                                                            <option value="yes">Yes</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Personal Information for Appointment -->
                                            <div id="personalInfoSection" style="display: none;">
                                                <div class="card mb-4">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Personal Information for Appointment</h5>

                                                        <!-- Date of Birth -->
                                                        <div class="mb-3">
                                                            <label for="dob" class="form-label">Date of Birth</label>
                                                            <input type="date" class="form-control" id="dob" name="dob">
                                                        </div>

                                                        <!-- Gender -->
                                                        <div class="mb-3">
                                                            <label for="gender" class="form-label">Gender</label>
                                                            <select class="form-select" id="gender" name="gender">
                                                                <option value="" selected disabled>Select gender</option>
                                                                <option value="Male">Male</option>
                                                                <option value="Female">Female</option>
                                                                <option value="Other">Other</option>
                                                                <option value="Prefer not to say">Prefer not to say</option>
                                                            </select>
                                                        </div>

                                                        <!-- Email -->
                                                        <div class="mb-3">
                                                            <label for="email" class="form-label">Email</label>
                                                            <input type="email" class="form-control" id="email" name="email">
                                                        </div>

                                                        <!-- Appointment Date -->
                                                        <div class="mb-3">
                                                            <label for="appointmentDate" class="form-label">Preferred Appointment Date</label>
                                                            <input type="date" class="form-control" id="appointmentDate" name="appointmentDate">
                                                        </div>

                                                        <!-- Appointment Time -->
                                                        <div class="mb-3">
                                                            <label for="appointmentTime" class="form-label">Preferred Appointment Time</label>
                                                            <input type="time" class="form-control" id="appointmentTime" name="appointmentTime">
                                                        </div>

                                                        <!-- Profile Photo -->
                                                        <div class="mb-3">
                                                            <label for="profilePhoto" class="form-label">Profile Photo</label>
                                                            <input type="file" class="form-control" id="profilePhoto" name="profilePhoto" accept="image/*" capture="camera">
                                                            <input type="hidden" id="photo_data" name="photo_data">
                                                            <small class="text-muted">Take or upload a clear photo</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Job Search Assistance Section -->
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Job Search Assistance</h5>

                                                    <!-- Need Job Assistance -->
                                                    <div class="mb-3">
                                                        <label for="needJobAssistance" class="form-label">Do you need any assistance regarding job search?</label>
                                                        <select class="form-select" id="needJobAssistance" name="needJobAssistance" onchange="toggleJobAssistanceSection()" required>
                                                            <option value="" selected disabled>Select option</option>
                                                            <option value="no">No</option>
                                                            <option value="yes">Yes</option>
                                                        </select>
                                                    </div>

                                                    <!-- Job Seeker Details -->
                                                    <div id="jobSeekerSection" style="display: none;">
                                                        <div class="mb-3">
                                                            <label class="form-label">Job Seeker Details</label>
                                                            <div id="jobSeekerRows">
                                                                <!-- Initial row will be added here -->
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addJobSeekerRow()">
                                                                <i class="bi bi-plus"></i> Add Another Job Seeker
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card">
                                                <div class="card-body">
                                                    <h5 class="card-title">Potential Student Details</h5>
                                                    <!-- Checkbox for Interest in Admission -->
                                                    <div class="mb-3">
                                                        <label for="interestInAdmission">Interested in
                                                            Admission</label>
                                                        <select class="form-select" id="interestInAdmission"
                                                            name="interestInAdmission"
                                                            onchange="toggleStudentFields()" required>
                                                            <option value="" selected disabled>Select an option
                                                            </option>
                                                            <option value="no">No</option>
                                                            <option value="yes">Yes</option>
                                                        </select>
                                                    </div>
                                                    <!-- Student Details -->
                                                    <div id="studentsContainer" style="display: none;">
                                                        <div class="student-details">
                                                            <!-- Student's Name -->
                                                            <div class="mb-3">
                                                                <label for="sname">Student's Name</label>
                                                                <input type="text" class="form-control" id="sname"
                                                                    name="students[0][name]"
                                                                    placeholder="Enter student's name">
                                                            </div>

                                                            <!-- Age and Gender -->
                                                            <div class="row">
                                                                <!-- Age -->
                                                                <div class="col-md-6 mb-3">
                                                                    <label for="sage" class="form-label">Age</label>
                                                                    <input type="number" class="form-control"
                                                                        id="sage" name="students[0][age]"
                                                                        placeholder="Enter student's age">
                                                                </div>
                                                                <!-- Gender -->
                                                                <div class="col-md-6 mb-3">
                                                                    <label for="sgender"
                                                                        class="form-label">Gender</label>
                                                                    <select class="form-select" id="sgender" name="students[0][gender]">
                                                                        <option value="" selected disabled>Select gender</option>
                                                                        <option value="Male">Male</option>
                                                                        <option value="Female">Female</option>
                                                                        <option value="Binary">Binary</option>
                                                                        <option value="Prefer not to say">Prefer not
                                                                            to say</option>
                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <!-- Grade Eligibility -->
                                                            <div class="mb-3">
                                                                <label for="sgrade">Grade Eligibility</label>
                                                                <select class="form-select" id="sgrade" name="students[0][grade]">
                                                                    <option value="" selected disabled>Select Grade</option>
                                                                    <option value="Nursery">Nursery</option>
                                                                    <option value="LKG">LKG</option>
                                                                    <option value="UKG">UKG</option>
                                                                    <option value="Class 1">Class 1</option>
                                                                    <option value="Class 2">Class 2</option>
                                                                    <option value="Class 3">Class 3</option>
                                                                    <option value="Class 4">Class 4</option>
                                                                    <option value="Class 5">Class 5</option>
                                                                    <option value="Class 6">Class 6</option>
                                                                    <option value="Class 7">Class 7</option>
                                                                    <option value="Class 8">Class 8</option>
                                                                    <option value="Class 9">Class 9</option>
                                                                    <option value="Class 10">Class 10</option>
                                                                    <option value="Class 11">Class 11</option>
                                                                    <option value="Class 12">Class 12</option>
                                                                    <!-- Add additional grade options here -->
                                                                </select>
                                                            </div>

                                                            <!-- Other student details -->
                                                            <div class="mb-3">
                                                                <label for="alreadyGoingSchool">Is the student
                                                                    already going to school?</label>
                                                                <select class="form-select" id="alreadyGoingSchool" name="students[0][already_going_school]">
                                                                    <option value="" selected disabled>Select an option</option>
                                                                    <option value="yes">Yes</option>
                                                                    <option value="no">No</option>
                                                                </select>
                                                            </div>

                                                            <!-- School Type -->
                                                            <div class="mb-3" id="schoolType">
                                                                <label for="schoolType">Type of school currently
                                                                    attending:</label>
                                                                <select class="form-select" id="schoolType" name="students[0][school_type]">
                                                                    <option value="" selected disabled>Select an option</option>
                                                                    <option value="private">Private</option>
                                                                    <option value="government">Government</option>
                                                                </select>
                                                            </div>

                                                            <!-- Coaching Classes -->
                                                            <div class="mb-3">
                                                                <label for="alreadyCoaching">Is the student already
                                                                    attending any coaching classes?</label>
                                                                <select class="form-select" id="alreadyCoaching" name="students[0][already_coaching]">
                                                                    <option value="" selected disabled>Select an option</option>
                                                                    <option value="yes">Yes</option>
                                                                    <option value="no">No</option>
                                                                </select>
                                                            </div>

                                                            <!-- Coaching Name -->
                                                            <div class="mb-3" id="coachingNameInput">
                                                                <label for="coachingName">Name of the
                                                                    coaching:</label>
                                                                <input type="text" class="form-control"
                                                                    id="coachingName"
                                                                    name="students[0][coaching_name]"
                                                                    placeholder="Enter coaching name">
                                                            </div>

                                                            <div class="mb-3">
                                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2"
                                                                    onclick="addStudentDetails()"><i class="bi bi-plus"></i> Add
                                                                    Student</button>
                                                                <!-- <button type="button" class="btn btn-danger" onclick="removeStudentDetails(this)">Remove Student</button> -->
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <script>
                                                document.addEventListener("DOMContentLoaded", function() {
                                                    toggleStudentFields();
                                                    document.getElementById('interestInAdmission').addEventListener('change', toggleStudentFields);
                                                });

                                                function toggleStudentFields() {
                                                    var dropdown = document.getElementById('interestInAdmission');
                                                    var studentFields = document.querySelectorAll('.student-details .mb-3 input, .student-details .mb-3 select');

                                                    if (dropdown.value === 'yes') {
                                                        document.getElementById('studentsContainer').style.display = 'block';
                                                        studentFields.forEach(function(field) {
                                                            // Check if the field is the coachingName input
                                                            if (field.id !== 'coachingName' && field.id !== 'schoolType') {
                                                                // If it's not the coachingName input, mark it as required
                                                                field.required = true;
                                                            }
                                                        });
                                                    } else {
                                                        document.getElementById('studentsContainer').style.display = 'none';
                                                        studentFields.forEach(function(field) {
                                                            field.required = false;
                                                            field.value = ''; // Reset field value
                                                        });
                                                    }
                                                }
                                            </script>
                                            <!-- Submit and Surveyor Details -->
                                            <div class="d-flex justify-content-between align-items-center">
                                                <button type="submit" class="btn btn-primary mt-2">Submit</button>
                                                <p class="mb-0">Surveyor Id:
                                                    <?php echo $fullname . '&nbsp;(' . $associatenumber . ')' ?>
                                                </p>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Bootstrap JS -->
    <script
        src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"
        integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3"
        crossorigin="anonymous">
    </script>
    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>


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
        document.getElementById('surveyForm').addEventListener('submit', function(event) {
            // Show loading modal when form is submitted
            showLoadingModal();
        });

        // Optional: Close loading modal when the page is fully loaded
        window.addEventListener('load', function() {
            // Hide loading modal
            hideLoadingModal();
        });
    </script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($_ENV['GOOGLE_LOCATION_API_KEY']) ?>&libraries=places">
    </script>


    <script>
        document.getElementById('getAddressBtn').addEventListener('click', function() {
            // Check if Geolocation is supported
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    // Retrieve latitude and longitude
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;

                    // Make a reverse geocoding request to get address
                    var geocoder = new google.maps.Geocoder();
                    var latlng = new google.maps.LatLng(lat, lng);
                    geocoder.geocode({
                        'location': latlng
                    }, function(results, status) {
                        if (status === google.maps.GeocoderStatus.OK) {
                            if (results[0]) {
                                // Update the address field with the retrieved address
                                document.getElementById('address').value = results[0].formatted_address;
                            } else {
                                alert('No results found');
                            }
                        } else {
                            alert('Geocoder failed due to: ' + status);
                        }
                    });
                });
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        });
    </script>

    <script>
        var studentCount = 0;

        function addStudentDetails() {
            studentCount++;

            var studentsContainer = document.getElementById('studentsContainer');
            var studentDetails = document.querySelector('.student-details').cloneNode(true);

            // Clear the input values in the cloned student details
            studentDetails.querySelectorAll('input').forEach(function(input) {
                input.value = '';
            });

            // Clear the select values in the cloned student details
            studentDetails.querySelectorAll('select').forEach(function(select) {
                select.selectedIndex = 0; // Reset to the default option
            });

            // Update input field names and IDs
            studentDetails.querySelectorAll('input, select').forEach(function(input) {
                var oldName = input.getAttribute('name');
                var newName = oldName.replace('[0]', '[' + studentCount + ']');
                input.setAttribute('name', newName);

                var oldId = input.getAttribute('id');
                if (oldId) {
                    var newId = oldId + '-' + studentCount;
                    input.setAttribute('id', newId);
                }
            });

            // Add remove button
            var removeButton = document.createElement('button');
            removeButton.innerHTML = '<i class="bi bi-trash"></i> Remove Student';
            removeButton.type = 'button';
            removeButton.classList.add('btn', 'btn-sm', 'btn-outline-danger');
            removeButton.addEventListener('click', function() {
                removeStudentDetails(this);
            });
            studentDetails.appendChild(removeButton);

            studentsContainer.appendChild(studentDetails);
        }

        function removeStudentDetails(button) {
            var studentDetails = button.parentNode;
            studentDetails.remove();
        }
    </script>
    <script>
        function checkOtherOption() {
            var earningSource = document.getElementById('earningSource');
            var otherEarningSource = document.getElementById('otherEarningSource');
            var otherEarningSourceInput = document.getElementById('otherEarningSourceInput');

            if (earningSource.value === 'other') {
                otherEarningSource.style.display = 'block';
                otherEarningSourceInput.required = true;
            } else {
                otherEarningSource.style.display = 'none';
                otherEarningSourceInput.required = false;
            }
        }
    </script>
    <script>
        // Function to update labels with red asterisks
        function updateLabels() {
            // Get all input, select, and textarea elements with the "required" attribute
            var requiredElements = document.querySelectorAll('input[required], select[required], textarea[required]');

            // Loop through each required element
            requiredElements.forEach(function(element) {
                // Get the ID of the element
                var elementId = element.id;

                // Find the label associated with the element
                var label = document.querySelector('label[for="' + elementId + '"]');

                // If a label is found and does not already contain the asterisk, add it
                if (label && !label.querySelector('span.required-marker')) {
                    label.insertAdjacentHTML('beforeend', '<span class="required-marker" style="color: red;"> *</span>');
                }
            });
        }

        // Run the function initially when the DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            updateLabels();
        });

        // Use event delegation to handle dynamically added elements
        document.body.addEventListener('change', function(event) {
            // Update the labels whenever a change occurs
            updateLabels();
        });
    </script>

    <script>
        // Function to toggle services needed section
        function toggleAppointmentSection() {
            const needAssistance = document.getElementById('needAssistance').value;
            const servicesNeededSection = document.getElementById('servicesNeededSection');
            const bookAppointmentSection = document.getElementById('bookAppointmentSection');
            const personalInfoSection = document.getElementById('personalInfoSection');

            if (needAssistance === 'yes') {
                servicesNeededSection.style.display = 'block';
                bookAppointmentSection.style.display = 'block';
            } else {
                servicesNeededSection.style.display = 'none';
                bookAppointmentSection.style.display = 'none';
                personalInfoSection.style.display = 'none';
                // Reset all related fields
                document.querySelectorAll('#servicesNeededSection input[type="checkbox"]').forEach(checkbox => {
                    checkbox.checked = false;
                });
                document.getElementById('otherService').style.display = 'none';
                document.getElementById('bookAppointment').value = '';
            }
        }

        // Function to toggle personal info section
        function togglePersonalInfoSection() {
            const bookAppointment = document.getElementById('bookAppointment').value;
            const personalInfoSection = document.getElementById('personalInfoSection');

            if (bookAppointment === 'yes') {
                personalInfoSection.style.display = 'block';
            } else {
                personalInfoSection.style.display = 'none';
                // Reset personal info fields
                document.getElementById('dob').value = '';
                document.getElementById('gender').value = '';
                document.getElementById('email').value = '';
                document.getElementById('appointmentDate').value = '';
                document.getElementById('appointmentTime').value = '';
                document.getElementById('photo_data').value = '';
                document.getElementById('profilePhoto').value = '';
            }
        }

        // Handle "Other" service checkbox
        document.getElementById('serviceOther').addEventListener('change', function() {
            const otherServiceInput = document.getElementById('otherService');
            otherServiceInput.style.display = this.checked ? 'block' : 'none';
            if (!this.checked) otherServiceInput.value = '';
        });

        // Handle photo capture
        document.getElementById('profilePhoto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('photo_data').value = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add event listeners for the main toggles
            document.getElementById('needAssistance').addEventListener('change', toggleAppointmentSection);
            document.getElementById('bookAppointment').addEventListener('change', togglePersonalInfoSection);
            // Add event listener for job assistance
            document.getElementById('needJobAssistance').addEventListener('change', toggleJobAssistanceSection);
        });
    </script>
    <script>
        // Job Seeker Management
        let jobSeekerRowCount = 0;

        function toggleJobAssistanceSection() {
            const needJobAssistance = document.getElementById('needJobAssistance').value;
            const jobSeekerSection = document.getElementById('jobSeekerSection');

            if (needJobAssistance === 'yes') {
                jobSeekerSection.style.display = 'block';
                // Add initial row if none exists
                if (jobSeekerRowCount === 0) {
                    addJobSeekerRow();
                }
            } else {
                jobSeekerSection.style.display = 'none';
                // Clear all rows
                document.getElementById('jobSeekerRows').innerHTML = '';
                jobSeekerRowCount = 0;
            }
        }

        function addJobSeekerRow() {
            jobSeekerRowCount++;
            const jobSeekerRows = document.getElementById('jobSeekerRows');

            const rowDiv = document.createElement('div');
            rowDiv.className = 'job-seeker-row mb-3 p-3 border rounded';
            rowDiv.id = `jobSeekerRow-${jobSeekerRowCount}`;

            rowDiv.innerHTML = `
        <div class="row">
            <div class="col-md-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input same-as-parent" type="checkbox" 
                           id="sameAsParent-${jobSeekerRowCount}" 
                           onchange="fillFromParent(${jobSeekerRowCount})">
                    <label class="form-check-label" for="sameAsParent-${jobSeekerRowCount}">
                        Same as respondent details
                    </label>
                </div>
            </div>
            
            <!-- Row 1: Name, DOB, Contact -->
            <div class="col-md-4 mb-2">
                <label for="jobSeekerName-${jobSeekerRowCount}" class="form-label">Name</label>
                <input type="text" class="form-control job-seeker-name" 
                       id="jobSeekerName-${jobSeekerRowCount}" 
                       name="jobSeekers[${jobSeekerRowCount}][name]" required>
            </div>
            
            <div class="col-md-4 mb-2">
                <label for="jobSeekerDob-${jobSeekerRowCount}" class="form-label">Date of Birth</label>
                <input type="date" class="form-control job-seeker-dob" 
                       id="jobSeekerDob-${jobSeekerRowCount}" 
                       name="jobSeekers[${jobSeekerRowCount}][dob]" required
                       onchange="calculateAge(${jobSeekerRowCount})">
                <small class="form-text text-muted">Age: <span id="calculatedAge-${jobSeekerRowCount}">--</span> years</small>
            </div>
            
            <div class="col-md-4 mb-2">
                <label for="jobSeekerContact-${jobSeekerRowCount}" class="form-label">Contact Number</label>
                <input type="tel" class="form-control job-seeker-contact" 
                       id="jobSeekerContact-${jobSeekerRowCount}" 
                       name="jobSeekers[${jobSeekerRowCount}][contact]" 
                       pattern="[0-9]{10}" required>
            </div>

            <!-- Row 2: Email and Education -->
            <div class="col-md-6 mb-2">
                <label for="jobSeekerEmail-${jobSeekerRowCount}" class="form-label">Email Address</label>
                <input type="email" class="form-control job-seeker-email"
                       id="jobSeekerEmail-${jobSeekerRowCount}"
                       name="jobSeekers[${jobSeekerRowCount}][email]"
                       placeholder="example@email.com">
            </div>
            
            <div class="col-md-6 mb-2">
                <label for="jobSeekerEducation-${jobSeekerRowCount}" class="form-label">Educational Qualification</label>
                <select class="form-select job-seeker-education" 
                        id="jobSeekerEducation-${jobSeekerRowCount}" 
                        name="jobSeekers[${jobSeekerRowCount}][education]" required>
                    <option value="" selected disabled>Loading education levels...</option>
                </select>
            </div>
            
            <!-- Row 3: Skills -->
            <div class="col-md-12 mb-2">
                <label for="jobSeekerSkills-${jobSeekerRowCount}" class="form-label">Skills/Experience</label>
                <input type="text" class="form-control" 
                       id="jobSeekerSkills-${jobSeekerRowCount}" 
                       name="jobSeekers[${jobSeekerRowCount}][skills]" 
                       placeholder="e.g., Computer skills, driving, communication, etc.">
            </div>
            
            <!-- Row 4: Job Preferences -->
            <div class="col-md-12 mb-2">
                <label for="jobSeekerPreferences-${jobSeekerRowCount}" class="form-label">Job Preferences</label>
                <textarea class="form-control" 
                       id="jobSeekerPreferences-${jobSeekerRowCount}" 
                       name="jobSeekers[${jobSeekerRowCount}][preferences]" 
                       rows="2"
                       placeholder="e.g., Full-time, part-time, work from home, specific industry or location"></textarea>
            </div>
            
            ${jobSeekerRowCount > 1 ? `
            <!-- Remove button for additional rows -->
            <div class="col-md-12">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeJobSeekerRow(${jobSeekerRowCount})">
                    <i class="bi bi-trash"></i> Remove Job Seeker
                </button>
            </div>
            ` : ''}
        </div>
    `;

            jobSeekerRows.appendChild(rowDiv);

            // Load education levels for this row
            loadEducationLevels(jobSeekerRowCount);
        }

        function removeJobSeekerRow(rowId) {
            const rowToRemove = document.getElementById(`jobSeekerRow-${rowId}`);
            if (rowToRemove) {
                rowToRemove.remove();
                // Update the remaining rows if needed
                updateJobSeekerRowNumbers();
            }
        }

        function updateJobSeekerRowNumbers() {
            // This function would renumber rows if needed after deletion
            // For simplicity, we'll keep the current implementation
        }

        function fillFromParent(rowId) {
            const checkbox = document.getElementById(`sameAsParent-${rowId}`);
            const nameField = document.getElementById(`jobSeekerName-${rowId}`);
            const contactField = document.getElementById(`jobSeekerContact-${rowId}`);

            if (checkbox.checked) {
                // Fill with respondent details
                const parentName = document.getElementById('parentName').value;
                const parentContact = document.getElementById('contact').value;

                if (parentName) nameField.value = parentName;
                if (parentContact) contactField.value = parentContact;
            } else {
                // Clear and enable fields
                nameField.value = '';
                contactField.value = '';
            }
        }

        function validateForm() {
            // Existing validation code...

            // Validate job seeker section if needed
            const needJobAssistance = document.getElementById('needJobAssistance').value;
            // Update form validation in the validateForm() function
            if (needJobAssistance === 'yes') {
                const jobSeekerRows = document.querySelectorAll('.job-seeker-row');
                if (jobSeekerRows.length === 0) {
                    alert('Please add at least one job seeker details.');
                    return false;
                }

                // Validate each job seeker row
                for (let i = 0; i < jobSeekerRows.length; i++) {
                    const rowNumber = i + 1;
                    const name = jobSeekerRows[i].querySelector('.job-seeker-name').value;
                    const dob = jobSeekerRows[i].querySelector('.job-seeker-dob').value;
                    const contact = jobSeekerRows[i].querySelector('.job-seeker-contact').value;
                    const education = jobSeekerRows[i].querySelector('.job-seeker-education').value;

                    // First check for missing required fields
                    if (!name || !dob || !contact || !education) {
                        alert(`Please fill all required fields for Job Seeker #${rowNumber}.\n\nMissing fields:\n` +
                            (!name ? '• Name\n' : '') +
                            (!dob ? '• Date of Birth\n' : '') +
                            (!contact ? '• Contact Number\n' : '') +
                            (!education ? '• Educational Qualification\n' : ''));
                        return false;
                    }

                    // Then check for age validation
                    const calculatedAge = parseInt(document.getElementById(`calculatedAge-${rowNumber}`).textContent);
                    if (isNaN(calculatedAge)) {
                        alert(`Please enter a valid Date of Birth for Job Seeker #${rowNumber}.`);
                        return false;
                    }

                    if (calculatedAge < 18) {
                        alert(`Job Seeker #${rowNumber} must be at least 18 years old.\n\nCurrent age: ${calculatedAge} years`);
                        return false;
                    }

                    if (calculatedAge > 65) {
                        alert(`Job Seeker #${rowNumber} cannot be older than 65 years.\n\nCurrent age: ${calculatedAge} years`);
                        return false;
                    }
                }
            }

            return true;
        }
    </script>
    <script>
        // Function to calculate age from date of birth
        function calculateAge(rowId) {
            const dobInput = document.getElementById(`jobSeekerDob-${rowId}`);
            const ageSpan = document.getElementById(`calculatedAge-${rowId}`);

            if (dobInput.value) {
                const dob = new Date(dobInput.value);
                const today = new Date();

                // Check if date is valid
                if (isNaN(dob.getTime())) {
                    ageSpan.innerHTML = `<span class="text-danger">Invalid date</span>`;
                    dobInput.classList.add('is-invalid');
                    return false;
                }

                // Check if date is not in the future
                if (dob > today) {
                    ageSpan.innerHTML = `<span class="text-danger">Future date not allowed</span>`;
                    dobInput.classList.add('is-invalid');
                    return false;
                }

                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();

                // Adjust age if birthday hasn't occurred yet this year
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }

                // Update the age display
                if (age < 18) {
                    ageSpan.innerHTML = `<span class="text-danger">${age} (Minimum 18 required)</span>`;
                    dobInput.classList.add('is-invalid');
                    return false;
                } else if (age > 65) {
                    ageSpan.innerHTML = `<span class="text-warning">${age} (Maximum 65)</span>`;
                    dobInput.classList.remove('is-invalid');
                    return true;
                } else {
                    ageSpan.textContent = age;
                    dobInput.classList.remove('is-invalid');
                    return true;
                }
            } else {
                ageSpan.textContent = '--';
                dobInput.classList.remove('is-invalid');
                return false;
            }
        }
    </script>
    <script>
        // Function to load education levels from API
        function loadEducationLevels(rowId) {
            const API_BASE = window.location.hostname === 'localhost' ?
                'http://localhost:8082/job-api/' :
                'https://login.rssi.in/job-api/';

            const dropdown = document.getElementById(`jobSeekerEducation-${rowId}`);

            // Show loading state
            dropdown.innerHTML = '<option value="" selected disabled>Loading education levels...</option>';

            $.ajax({
                url: API_BASE + 'get_education_levels.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data && response.data.length > 0) {
                        // Clear and populate dropdown
                        dropdown.innerHTML = '<option value="" selected disabled>Select qualification</option>';

                        response.data.forEach(education => {
                            const option = document.createElement('option');
                            option.value = education.id;
                            option.textContent = education.name;
                            dropdown.appendChild(option);
                        });
                    } else {
                        // If no data or error, show fallback
                        dropdown.innerHTML = '<option value="" selected disabled>No education levels available</option>';
                        console.error('No education levels found or error:', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    dropdown.innerHTML = '<option value="" selected disabled>Error loading education levels</option>';
                    console.error('AJAX error:', error);
                }
            });
        }
    </script>
</body>

</html>