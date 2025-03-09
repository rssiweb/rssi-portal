<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_tap.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("tid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
?>
<?php
// SQL query to fetch current data
$sql = "SELECT * FROM signup WHERE application_number='$application_number'";
$result = pg_query($con, $sql);
$resultArr = pg_fetch_all($result);

// Check if there are any results
if ($resultArr && count($resultArr) > 0) {
    // Accessing specific column values from the first result (assuming there is only one row)
    $applicant_email = $resultArr[0]['email'];
    $applicant_name = $resultArr[0]['applicant_name'];
    $application_number = $resultArr[0]['application_number'];
    $photo_verification_status = $resultArr[0]['photo_verification']; // Add photo verification status
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["form-type"]) && $_POST["form-type"] == "signup") {
    // Initialize variables
    $telephone = isset($_POST['telephone']) ? pg_escape_string($con, $_POST['telephone']) : null;
    $postal_address = isset($_POST['postal_address']) ? pg_escape_string($con, $_POST['postal_address']) : null;
    $permanent_address = isset($_POST['permanent_address']) ? pg_escape_string($con, $_POST['permanent_address']) : null;
    $education_qualification = isset($_POST['education_qualification']) ? pg_escape_string($con, $_POST['education_qualification']) : null;
    $specialization = isset($_POST['specialization']) ? pg_escape_string($con, $_POST['specialization']) : null;
    $work_experience = isset($_POST['work_experience']) ? pg_escape_string($con, $_POST['work_experience']) : null;
    $caste = isset($_POST['caste']) ? pg_escape_string($con, $_POST['caste']) : null;
    $uploadedFile_caste = isset($_FILES['caste_document']) ? $_FILES['caste_document'] : null;
    $uploadedFile_photo = isset($_FILES['applicant_photo']) ? $_FILES['applicant_photo'] : null;
    $uploadedFile_resume = isset($_FILES['resume_upload']) ? $_FILES['resume_upload'] : null;

    // Initialize file links to null
    $doclink_caste_document = null;
    $doclink_applicant_photo = null;
    $doclink_resume = null;

    // Handle caste document upload
    if (!empty($uploadedFile_caste['name'])) {
        $filename_caste_document = "caste_" . "$application_number" . "_" . time();
        $parent_caste_document = '1SGPfr_1b85s4KAuvt8n098VXIrvkBGY9';
        $doclink_caste_document = uploadeToDrive($uploadedFile_caste, $parent_caste_document, $filename_caste_document);
    }

    // Server-side validation for the applicant photo (only if the photo is being resubmitted)
    if (!empty($uploadedFile_photo['name'])) {
        // Check if the photo verification is approved, if so, prevent resubmission
        if ($photo_verification_status === "Approved") {
            echo '<script>
                    alert("Your photo has already been approved and cannot be resubmitted.");
                    if (window.history.replaceState) {
                        // Update the URL without causing a page reload or resubmission
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload(); // Trigger a page reload to reflect changes
                </script>';
            exit;
        }

        // Check file type and size
        $photo_file = $uploadedFile_photo;
        $allowed_types = ['image/jpeg', 'image/png'];
        if (!in_array($photo_file['type'], $allowed_types)) {
            echo '<script>alert("Invalid file type for applicant photo. Only JPEG and PNG are allowed.");</script>';
            exit;
        }
        if ($photo_file['size'] > 300000) { // 300KB limit (300000 bytes)
            echo '<script>alert("Applicant photo is too large. Maximum allowed size is 300KB.");</script>';
            exit;
        }

        // Upload the photo
        $filename_applicant_photo = "photo_" . "$application_number" . "_" . time();
        $parent_applicant_photo = '1CgXW0M1ClTLRFrJjOCh490GVAq0IVAlM5OmAcfTtXVWxmnR9cx_I_Io7uD_iYE7-5rWDND82';
        $doclink_applicant_photo = uploadeToDrive($photo_file, $parent_applicant_photo, $filename_applicant_photo);
    }

    // Handle resume upload
    if (!empty($uploadedFile_resume['name'])) {
        $filename_resume = "resume_" . "$application_number" . "_" . time();
        $parent_resume = '1YyJLwbXQqNJeESSfPINjTW2OVFOh5IGD53Aaf1ZNqsnDeWAFdh6ECr3TnbNXM95yWdS5si-z';
        $doclink_resume = uploadeToDrive($uploadedFile_resume, $parent_resume, $filename_resume);
    }

    // Define the list of fields to compare
    $fields_to_compare = [
        'postal_address',
        'permanent_address',
        'education_qualification',
        'specialization',
        'work_experience',
        'caste'
        // Add more fields here as needed
    ];

    // Initialize arrays for the updated fields and changed fields
    $update_fields = [];
    $changed_fields = [];

    // Loop through the fields to compare
    foreach ($fields_to_compare as $field) {
        // Get the current and submitted values for the field, sanitizing them with trim only
        $current_value = isset($resultArr[0][$field]) ? trim($resultArr[0][$field]) : null;
        $submitted_value = isset($_POST[$field]) ? trim($_POST[$field]) : null;

        // Only proceed if the submitted value is not null, not disabled, and different from the current value
        if ($submitted_value !== null && $submitted_value !== "" && $submitted_value !== $current_value) {
            // Add the field to the update query and changed fields list
            $update_fields[] = "$field = '" . pg_escape_string($con, $submitted_value) . "'";
            $changed_fields[] = $field;
        }
    }
    // Add file fields to the update query if the file was uploaded
    if ($doclink_caste_document) {
        $update_fields[] = "caste_document = '$doclink_caste_document'";
        $changed_fields[] = 'caste_document';
    }

    if ($doclink_applicant_photo) {
        $update_fields[] = "applicant_photo = '$doclink_applicant_photo'";
        $changed_fields[] = 'applicant_photo';
    }

    if ($doclink_resume) {
        $update_fields[] = "resume_upload = '$doclink_resume'";
        $changed_fields[] = 'resume_upload';
    }

    // If there are any fields to update, proceed with the update query
    if (count($update_fields) > 0) {
        // Automatically set application_status to 'Application Re-Submitted'
        $update_fields['application_status'] = "application_status = 'Application Re-Submitted'";

        // Build and execute the update query dynamically
        $update_query = "UPDATE signup SET " . implode(", ", $update_fields) . " WHERE application_number = '$application_number'";
        $result = pg_query($con, $update_query);
        $cmdtuples = pg_affected_rows($result);
    }

    // Send email with only the changed fields
    if (isset($cmdtuples) && $cmdtuples == 1 && count($changed_fields) > 0) {
        sendEmail("tap_application_resubmitted", array(
            "application_number" => $application_number,
            "applicant_name" => $applicant_name,
            "changed_fields" => implode(", ", $changed_fields) // Send only changed fields in the email
        ), 'info@rssi.in');
    }
}

// If the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if update was successful
    if (isset($cmdtuples) && $cmdtuples == 1) {
        // If any fields were changed
        if (count($changed_fields) > 0) {
            echo '<script>
                    alert("The following fields were updated: ' . implode(', ', $changed_fields) . '");
                    if (window.history.replaceState) {
                        // Update the URL without causing a page reload or resubmission
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload(); // Trigger a page reload to reflect changes
                  </script>';
        } else {
            // No changes made
            echo '<script>
                    alert("Error: We encountered an error while updating the record. Please try again.");
                  </script>';
        }
    } else {
        // Failure: Error occurred
        echo '<script>
                alert("No changes were made to your profile.");
              </script>';
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

    <title>Application Form</title>

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
            <h1>Application Form</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Application Form</li>
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
                            <?php foreach ($resultArr as $array) { ?>
                                <?php
                                // Reusable function to extract file ID from Google Drive URL
                                function extract_file_id($url)
                                {
                                    if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $url, $matches)) {
                                        return $matches[1];
                                    }
                                    return null;
                                }

                                // Reusable function to get file name from Google Drive using file ID
                                function get_file_name_from_google_drive($file_id, $api_key)
                                {
                                    $url = "https://www.googleapis.com/drive/v3/files/$file_id?fields=name&key=$api_key";
                                    $ch = curl_init();
                                    curl_setopt($ch, CURLOPT_URL, $url);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                    $response = curl_exec($ch);
                                    curl_close($ch);

                                    $data = json_decode($response, true);
                                    return $data['name'] ?? 'Unknown File';
                                }

                                // API Key for Google Drive
                                $api_key = "AIzaSyCtWC48inXWXUM8s6hSeX89LP78sfGLk_g"; // Replace with your actual API key

                                // Handle the files for caste certificate, photo, and resume
                                $caste_filename = !empty($array['caste_document']) ? get_file_name_from_google_drive(extract_file_id($array['caste_document']), $api_key) : null;
                                $photo_filename = !empty($array['applicant_photo']) ? get_file_name_from_google_drive(extract_file_id($array['applicant_photo']), $api_key) : null;
                                $resume_filename = !empty($array['resume_upload']) ? get_file_name_from_google_drive(extract_file_id($array['resume_upload']), $api_key) : null;
                                $isFormDisabled = !empty($array["tech_interview_schedule"]) ? 'disabled' : null;
                                ?>

                                <div class="container">
                                    <form name="signup" id="signup" action="#" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="form-type" value="signup">
                                        <fieldset <?php echo $isFormDisabled; ?>>

                                            <div class="table-responsive">
                                                <table class="table">
                                                    <tr>
                                                        <!-- Left Column (Application Details) -->
                                                        <td style="width: 50%; vertical-align: top;">
                                                            <table>
                                                                <tr>
                                                                    <td><label for="applicant-name">Application Number:</label></td>
                                                                    <td><?php echo $array["application_number"] ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><label for="applicant-name">Applicant Name:</label></td>
                                                                    <td><?php echo $array["applicant_name"] ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><label for="date-of-birth">Date of Birth:</label></td>
                                                                    <td><?php echo $array["date_of_birth"] ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><label for="gender">Gender:</label></td>
                                                                    <td><?php echo $array["gender"] ?></td>
                                                                </tr>
                                                            </table>
                                                        </td>

                                                        <!-- Right Column (Applicant Photo) -->
                                                        <td style="width: 50%; vertical-align: top; text-align: center;">
                                                            <div class="photo-box mt-2" style="border: 1px solid #ccc; padding: 10px; width: 150px; height: 200px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                                                <?php
                                                                if (!empty($array['applicant_photo'])) {
                                                                    // Extract file ID using regular expression
                                                                    preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $array['applicant_photo'], $matches);
                                                                    $file_id = $matches[1];
                                                                    // Generate the preview URL for iframe
                                                                    $preview_url = "https://drive.google.com/file/d/$file_id/preview";
                                                                    echo '<iframe src="' . $preview_url . '" width="150" height="200" frameborder="0" allow="autoplay" sandbox="allow-scripts allow-same-origin"></iframe>';
                                                                } else {
                                                                    echo "No photo available";
                                                                }
                                                                ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>

                                                <table class="table">
                                                    <tbody>
                                                        <tr>
                                                            <td><label for="telephone">Telephone Number:</label></td>
                                                            <td>
                                                                <input class="form-control" type="tel" name="telephone" id="telephone"
                                                                    value="<?php echo htmlspecialchars($array['telephone']); ?>"
                                                                    pattern="^\d{10}$" required title="Please enter a valid 10-digit phone number"
                                                                    minlength="10" oninput="checkTelephoneLength()">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="email">Email
                                                                    Address:</label>
                                                            </td>
                                                            <td><?php echo $array["email"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="postal_address">Current Address:</label>
                                                            </td>
                                                            <td>
                                                                <textarea class="form-control" id="postal_address" name="postal_address" rows="3" placeholder="Enter current address" required><?php echo $array['postal_address'] ?? '' ?></textarea>
                                                                <small id="postal_address-help" class="form-text text-muted">Please enter the complete current address of the student.</small>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="permanent_address">Permanent Address:</label>
                                                            </td>
                                                            <td>
                                                                <textarea class="form-control" id="permanent_address" name="permanent_address" rows="3" placeholder="Enter permanent address" required><?php echo $array['permanent_address'] ?? '' ?></textarea>
                                                                <small id="permanent_address-help" class="form-text text-muted">Please enter the complete permanent address of the student.</small>
                                                                <div>
                                                                    <input type="checkbox" id="same-address" onclick="copyAddress()">
                                                                    <label for="same-address">Same as current address</label>
                                                                </div>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <label for="education_qualification" class="form-label">Educational Qualification:</label>
                                                            </td>
                                                            <td>
                                                                <select class="form-select" id="education_qualification" name="education_qualification" required>
                                                                    <option disabled <?php echo empty($array['education_qualification']) ? 'selected' : ''; ?>>Select your qualification</option>
                                                                    <?php
                                                                    $options = [
                                                                        "Bachelor Degree Regular" => "Bachelor Degree Regular",
                                                                        "Bachelor Degree Correspondence" => "Bachelor Degree Correspondence",
                                                                        "Master Degree" => "Master Degree",
                                                                        "PhD (Doctorate Degree)" => "PhD (Doctorate Degree)",
                                                                        "Post Doctorate or 5 years experience" => "Post Doctorate or 5 years experience",
                                                                        "Culture, Art & Sports etc." => "Culture, Art & Sports etc.",
                                                                        "Class 12th Pass" => "Class 12th Pass"
                                                                    ];
                                                                    foreach ($options as $value => $label) {
                                                                        $selected = ($array['education_qualification'] ?? '') === $value ? 'selected' : '';
                                                                        echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($label) . "</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="specialization" class="form-label">Area of Specialization:</label>
                                                            </td>
                                                            <td>
                                                                <input
                                                                    type="text"
                                                                    class="form-control"
                                                                    id="specialization"
                                                                    name="specialization"
                                                                    placeholder="e.g., Computer Science, Physics, Fine Arts"
                                                                    value="<?php echo htmlspecialchars($array['specialization'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                    required>
                                                                <small id="specialization-help" class="form-text text-muted">
                                                                    Please write the name of the subject, stream, or area of specialization in which you have done graduation or masters.
                                                                </small>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="work_experience" class="form-label">Work Experience:</label>
                                                            </td>
                                                            <td>
                                                                <input
                                                                    type="text"
                                                                    class="form-control"
                                                                    id="work_experience"
                                                                    name="work_experience"
                                                                    placeholder="e.g., 3 years in Teaching, 2 years in Marketing"
                                                                    value="<?php echo htmlspecialchars($array['work_experience'], ENT_QUOTES, 'UTF-8'); ?>">
                                                                <small id="work_experience-help" class="form-text text-muted">
                                                                    Specify your work experience, including job title and duration, if applicable.
                                                                </small>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <label for="caste">Caste:</label>
                                                            </td>
                                                            <td>
                                                                <select class="form-select" id="caste" name="caste">
                                                                    <option disabled <?php echo empty($array['caste']) ? 'selected' : ''; ?>>Select your caste</option>
                                                                    <?php
                                                                    $options = [
                                                                        "General" => "General",
                                                                        "SC" => "Scheduled Caste (SC)",
                                                                        "ST" => "Scheduled Tribe (ST)",
                                                                        "OBC" => "Other Backward Class (OBC)",
                                                                        "EWS" => "Economically Weaker Section (EWS)",
                                                                        "Prefer not to disclose" => "Prefer not to disclose"
                                                                    ];
                                                                    foreach ($options as $value => $label) {
                                                                        $selected = ($array['caste'] ?? '') === $value ? 'selected' : '';
                                                                        echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($label) . "</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                                <small id="caste-help" class="form-text text-muted">Please select your caste category as per government records.</small>
                                                            </td>
                                                        </tr>

                                                        <!-- Supporting Document Upload Field -->
                                                        <tr>
                                                            <td>
                                                                <label for="caste_document">Caste Certificate:</label>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex flex-column flex-sm-row align-items-center">
                                                                    <!-- Left side: File input -->
                                                                    <div class="mb-2 mb-sm-0">
                                                                        <input type="file" class="form-control" id="caste_document" name="caste_document" accept=".pdf,.jpg,.jpeg,.png">
                                                                    </div>

                                                                    <!-- Right side: Uploaded file or message -->
                                                                    <div class="ms-2 d-flex align-items-center w-100 w-sm-auto">
                                                                        <?php if (!empty($caste_filename)): ?>
                                                                            <a href="<?php echo htmlspecialchars($array['caste_document']); ?>" target="_blank"><?php echo htmlspecialchars($caste_filename); ?></a>
                                                                        <?php else: ?>
                                                                            <span>No file uploaded yet.</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <!-- Help text below the input field -->
                                                                <small id="caste_document-help" class="form-text text-muted">
                                                                    Upload your caste certificate (PDF, JPG, JPEG, or PNG).
                                                                </small>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <label for="branch">Preferred
                                                                    Branch:</label>
                                                            </td>
                                                            <td><?php echo $array["branch"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="class">Type of association:</label>
                                                            </td>
                                                            <td><?php echo $array["association"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td width="30%">
                                                                <label for="job-select">Job code:
                                                                </label>
                                                            </td>
                                                            <td><?php echo $array["job_select"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="purpose">Purpose of Volunteering:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($array["purpose"], ENT_QUOTES, 'UTF-8'); ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="availability">Availability:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["availability"] ?>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <label for="interests">What types of work opportunities are you most interested in
                                                                    pursuing?</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["interests"] ?>
                                                            </td>
                                                        </tr>


                                                        <tr>
                                                            <td>
                                                                <label for="post-select">Post applied for:
                                                                </label>
                                                            </td>
                                                            <td><?php echo $array["post_select"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr id="subjectPreferences">
                                                            <td>
                                                                <label for="subjectPreferences">Subject
                                                                    Preferences (Select up to
                                                                    3)</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["subject1"] . ', ' . $array["subject2"] . ', ' . $array["subject3"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="medium">Medium of instruction:
                                                                </label>
                                                            </td>
                                                            <td><?php echo $array["medium"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="membershipPurpose">Purpose of Membership:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo htmlspecialchars($array["membership_purpose"], ENT_QUOTES, 'UTF-8'); ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="duration">Duration:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["duration"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="payment-photo">Upload Payment Screenshot</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["payment_photo"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="applicant_photo">Upload Applicant Photo:</label>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex flex-column flex-sm-row align-items-center">
                                                                    <!-- Left side: File input -->
                                                                    <div class="mb-2 mb-sm-0">
                                                                        <input type="file" class="form-control" id="applicant_photo" name="applicant_photo" accept=".jpg,.jpeg,.png" onchange="validatePhotoFile(this)"
                                                                            <?php echo ($array["photo_verification"] === "Approved") ? 'disabled' : ''; ?>>
                                                                    </div>

                                                                    <!-- Right side: Uploaded file or message -->
                                                                    <div class="ms-2 d-flex align-items-center w-100 w-sm-auto">
                                                                        <?php if (!empty($photo_filename)): ?>
                                                                            <a href="<?php echo htmlspecialchars($array['applicant_photo']); ?>" target="_blank"><?php echo htmlspecialchars($photo_filename); ?></a>
                                                                        <?php else: ?>
                                                                            <span>No file uploaded yet.</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <!-- Help text below the input field -->
                                                                <small id="applicant_photo-help" class="form-text text-muted">
                                                                    Please upload a recent passport size photograph of the applicant.
                                                                </small>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="resume_upload">Upload Resume:</label>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex flex-column flex-sm-row align-items-center">
                                                                    <!-- Left side: File input -->
                                                                    <div class="mb-2 mb-sm-0">
                                                                        <input type="file" class="form-control" id="resume_upload" name="resume_upload">
                                                                    </div>

                                                                    <!-- Right side: Uploaded file or message -->
                                                                    <div class="ms-2 d-flex align-items-center w-100 w-sm-auto">
                                                                        <?php if (!empty($resume_filename)): ?>
                                                                            <a href="<?php echo htmlspecialchars($array['resume_upload']); ?>" target="_blank"><?php echo htmlspecialchars($resume_filename); ?></a>
                                                                        <?php else: ?>
                                                                            <span>No file uploaded yet.</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <!-- Help text below the input field -->
                                                                <small id="resume_upload-help" class="form-text text-muted">
                                                                    Please upload a scanned copy of the Resume.
                                                                </small>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <label for="heardAbout" class="form-label">Where did you hear about RSSI
                                                                    NGO?</label>
                                                            </td>
                                                            <td><?php echo $array["heard_about"] ?>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </fieldset>
                                    </form>
                                </div>
                            <?php } ?>
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
        function checkTelephoneLength() {
            var telephone = document.getElementById('telephone').value;

            // Limit the input to 10 digits
            if (telephone.length > 10) {
                alert("You can only enter up to 10 digits.");
                document.getElementById('telephone').value = telephone.slice(0, 10); // Truncate to 10 digits
            }
        }
    </script>
    <script>
        function copyAddress() {
            const currentAddress = document.getElementById('postal_address').value;
            const permanentAddressField = document.getElementById('permanent_address');
            const sameAddressCheckbox = document.getElementById('same-address');

            if (sameAddressCheckbox.checked) {
                permanentAddressField.value = currentAddress; // Copy current address to permanent address
                permanentAddressField.readOnly = true; // Make it read-only when checkbox is checked
            } else {
                permanentAddressField.value = ''; // Clear permanent address when checkbox is unchecked
                permanentAddressField.readOnly = false; // Make it editable again
            }
        }
    </script>
    <script>
        function validatePhotoFile(input) {
            const file = input.files[0]; // Get the uploaded file

            if (file) {
                // Allowed file types
                const validImageTypes = ['image/jpeg', 'image/png'];
                if (!validImageTypes.includes(file.type)) {
                    alert('Only image files (JPG, JPEG, PNG) are allowed.');
                    input.value = ''; // Clear the file input
                    return;
                }

                // Check file size (max 300 KB)
                const maxSize = 300 * 1024; // 300 KB in bytes
                if (file.size > maxSize) {
                    alert('The uploaded file exceeds the maximum size of 300 KB. Please upload a smaller image.');
                    input.value = ''; // Clear the file input
                    return;
                }

                // File is valid; no further actions required.
            }
        }
    </script>
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
        document.getElementById('signup').addEventListener('submit', function(event) {
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
        $(document).ready(function() {
            $('input, select, textarea').each(function() {
                if ($(this).prop('required')) { // Check if the element has the required attribute
                    $(this).closest('td').prev('td').find('label').append(' <span style="color: red">*</span>');
                }
            });
        });
    </script>
</body>

</html>