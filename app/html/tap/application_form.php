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

// SQL query to fetch current data
$sql = "SELECT * FROM signup WHERE application_number='$application_number'";
$result = pg_query($con, $sql);
$resultArr = pg_fetch_all($result);
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["form-type"]) && $_POST["form-type"] == "signup") {
    // Sanitize and fetch form data
    $telephone = !empty($_POST['telephone']) ? pg_escape_string($con, $_POST['telephone']) : null;
    $postal_address = !empty($_POST['postal-address']) ? pg_escape_string($con, $_POST['postal-address']) : null;
    $permanent_address = !empty($_POST['permanent-address']) ? pg_escape_string($con, $_POST['permanent-address']) : null;
    $education_qualification = !empty($_POST['education-qualification']) ? pg_escape_string($con, $_POST['education-qualification']) : null;
    $specialization = !empty($_POST['specialization']) ? pg_escape_string($con, $_POST['specialization']) : null;
    $work_experience = !empty($_POST['work-experience']) ? pg_escape_string($con, $_POST['work-experience']) : null;
    $caste = !empty($_POST['caste']) ? pg_escape_string($con, $_POST['caste']) : null;
    $uploadedFile_caste = $_FILES['caste-document'];
    $uploadedFile_photo = $_FILES['applicant-photo'];
    $uploadedFile_resume = $_FILES['resume-upload'];

    if (empty($_FILES['caste-document']['name'])) {
        $doclink_caste_document = null;
    } else {
        $filename_caste_document = "caste_" . "$application_number" . "_" . time();
        $parent_caste_document = '1YyJLwbXQqNJeESSfPINjTW2OVFOh5IGD53Aaf1ZNqsnDeWAFdh6ECr3TnbNXM95yWdS5si-z';
        $doclink_caste_document = uploadeToDrive($uploadedFile_caste, $parent_caste_document, $filename_caste_document);
    }
    if (empty($_FILES['applicant-photo']['name'])) {
        $doclink_applicant_photo = null;
    } else {
        $filename_applicant_photo = "photo_" . "$application_number" . "_" . time();
        $parent_applicant_photo = '1YyJLwbXQqNJeESSfPINjTW2OVFOh5IGD53Aaf1ZNqsnDeWAFdh6ECr3TnbNXM95yWdS5si-z';
        $doclink_applicant_photo = uploadeToDrive($uploadedFile_photo, $parent_applicant_photo, $filename_applicant_photo);
    }

    if (empty($_FILES['resume-upload']['name'])) {
        $doclink_resume = null;
    } else {
        $filename_resume = "resume_" . "$application_number" . "_" . time();
        $parent_resume = '1YyJLwbXQqNJeESSfPINjTW2OVFOh5IGD53Aaf1ZNqsnDeWAFdh6ECr3TnbNXM95yWdS5si-z';
        $doclink_resume = uploadeToDrive($uploadedFile_resume, $parent_resume, $filename_resume);
    }

    // Build the update query dynamically
    $update_fields = [];
    if ($telephone) $update_fields[] = "telephone = '$telephone'";
    if ($postal_address) $update_fields[] = "postal_address = '$postal_address'";
    if ($permanent_address) $update_fields[] = "permanent_address = '$permanent_address'";
    if ($education_qualification) $update_fields[] = "education_qualification = '$education_qualification'";
    if ($specialization) $update_fields[] = "specialization = '$specialization'";
    if ($work_experience) $update_fields[] = "work_experience = '$work_experience'";
    if ($caste) $update_fields[] = "caste = '$caste'";
    if ($doclink_caste_document) $update_fields[] = "caste_document = '$doclink_caste_document'";
    if ($doclink_applicant_photo) $update_fields[] = "applicant_photo = '$doclink_applicant_photo'";
    if ($doclink_resume) $update_fields[] = "resume_upload = '$doclink_resume'";

    // If there are fields to update, execute the update query
    if (count($update_fields) > 0) {
        $update_query = "UPDATE signup SET " . implode(", ", $update_fields) . " WHERE application_number = '$application_number'";
        $result = pg_query($con, $update_query);
        $cmdtuples = pg_affected_rows($result);
    }
}

// If update was successful or failed, show an alert
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($cmdtuples == 1) {
        // Success: Profile was updated
        echo '<script>
                alert("Your profile has been updated successfully!");
                window.location.href = "application_form.php";  // Reload the page
              </script>';
    } else {
        // Failure: Record was not updated
        echo '<script>
                alert("Error: We encountered an error while updating the record. Please try again.");
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
        .milestones {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #f0f0f0;
            border-radius: 10px;
            margin: 20px auto;
        }

        .milestone {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #ccc;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            position: relative;
        }

        .milestone.active {
            background-color: #4CAF50;
        }

        .milestone::after {
            content: '';
            width: 100%;
            height: 4px;
            background-color: #ccc;
            position: absolute;
            top: 50%;
            left: 100%;
            z-index: -1;
        }

        .milestone.active::after {
            background-color: #4CAF50;
            width: calc(100% - 50px);
        }

        .step-label {
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>
    <!-- <?php include 'inactive_session_expire_check.php'; ?> -->

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
                            <form name="signup" id="signup" action="#" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="form-type" value="signup">
                                <?php foreach ($resultArr as $array) { ?>
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td>

                                                    <label for="applicant-name">Applicant
                                                        Name:</label>
                                                </td>
                                                <td><?php echo $array["applicant_name"] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="date-of-birth">Date of
                                                        Birth:</label>
                                                </td>
                                                <td><?php echo $array["date_of_birth"] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="gender">Gender:</label>
                                                </td>
                                                <td><?php echo $array["gender"] ?>
                                                </td>
                                            </tr>
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
                                                    <label for="postal-address">Current Address:</label>
                                                </td>
                                                <td>
                                                    <textarea class="form-control" id="postal-address" name="postal-address" rows="3" placeholder="Enter current address" required><?php echo $array['postal_address'] ?? '' ?></textarea>
                                                    <small id="postal-address-help" class="form-text text-muted">Please enter the complete current address of the student.</small>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="permanent-address">Permanent Address:</label>
                                                </td>
                                                <td>
                                                    <textarea class="form-control" id="permanent-address" name="permanent-address" rows="3" placeholder="Enter permanent address" required><?php echo $array['permanent_address'] ?? '' ?></textarea>
                                                    <small id="permanent-address-help" class="form-text text-muted">Please enter the complete permanent address of the student.</small>
                                                    <div>
                                                        <input type="checkbox" id="same-address" onclick="copyAddress()">
                                                        <label for="same-address">Same as current address</label>
                                                    </div>
                                                </td>
                                            </tr>

                                            <script>
                                                function copyAddress() {
                                                    const currentAddress = document.getElementById('postal-address').value;
                                                    const permanentAddressField = document.getElementById('permanent-address');
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
                                            <tr>
                                                <td>
                                                    <label for="education-qualification" class="form-label">Educational Qualification:</label>
                                                </td>
                                                <td>
                                                    <select class="form-select" id="education-qualification" name="education-qualification" required>
                                                        <option value="" disabled <?php echo empty($array['education_qualification']) ? 'selected' : ''; ?>>Select your qualification</option>
                                                        <?php if (!empty($array['education_qualification'])) { ?>
                                                            <option value="<?php echo htmlspecialchars($array['education_qualification']); ?>" selected><?php echo htmlspecialchars($array['education_qualification']); ?></option>
                                                        <?php } ?>
                                                        <option value="Bachelor Degree Regular">Bachelor Degree Regular</option>
                                                        <option value="Bachelor Degree Correspondence">Bachelor Degree Correspondence</option>
                                                        <option value="Master Degree">Master Degree</option>
                                                        <option value="PhD (Doctorate Degree)">PhD (Doctorate Degree)</option>
                                                        <option value="Post Doctorate or 5 years experience">Post Doctorate or 5 years experience</option>
                                                        <option value="Culture, Art & Sports etc.">Culture, Art & Sports etc.</option>
                                                        <option value="Class 12th Pass">Class 12th Pass</option>
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
                                                        value="<?php echo ($array["specialization"]); ?>"
                                                        required>
                                                    <small id="specialization-help" class="form-text text-muted">
                                                        Please write the name of the subject, stream, or area of specialization in which you have done graduation or masters.
                                                    </small>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="work-experience" class="form-label">Work Experience:</label>
                                                </td>
                                                <td>
                                                    <input
                                                        type="text"
                                                        class="form-control"
                                                        id="work-experience"
                                                        name="work-experience"
                                                        placeholder="e.g., 3 years in Teaching, 2 years in Marketing"
                                                        value="<?php echo ($array["work_experience"]); ?>">
                                                    <small id="work-experience-help" class="form-text text-muted">
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
                                                        <option value="" disabled <?php echo empty($array['caste']) ? 'selected' : ''; ?>>Select your caste</option>
                                                        <?php if (!empty($array['caste'])) { ?>
                                                            <option value="<?php echo htmlspecialchars($array['caste']); ?>" selected><?php echo htmlspecialchars($array['caste']); ?></option>
                                                        <?php } ?>

                                                        <option value="General">General</option>
                                                        <option value="SC">Scheduled Caste (SC)</option>
                                                        <option value="ST">Scheduled Tribe (ST)</option>
                                                        <option value="OBC">Other Backward Class (OBC)</option>
                                                        <option value="EWS">Economically Weaker Section (EWS)</option>
                                                        <option value="Prefer not to disclose">Prefer not to disclose</option>
                                                        <!-- Add additional options as necessary -->
                                                    </select>
                                                    <small id="caste-help" class="form-text text-muted">Please select your caste category as per government records.</small>
                                                </td>
                                            </tr>

                                            <!-- Supporting Document Upload Field -->
                                            <tr>
                                                <td>
                                                    <label for="caste-document">Caste Certificate:</label>
                                                </td>
                                                <td>
                                                    <?php
                                                    // Check if caste_document is not null
                                                    if (!empty($array['caste_document'])) {
                                                        // Extract file ID from Google Drive link
                                                        function extract_file_id_caste($url)
                                                        {
                                                            if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $url, $matches)) {
                                                                return $matches[1];
                                                            }
                                                            return null;
                                                        }

                                                        // Function to get file name from Google Drive using file ID
                                                        function get_file_name_from_google_drive_caste($file_id, $api_key)
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

                                                        $api_key = "AIzaSyCtWC48inXWXUM8s6hSeX89LP78sfGLk_g"; // Replace with your actual Google Drive API Key
                                                        $photo_file_id = extract_file_id_caste($array['caste_document']);
                                                        $photo_filename = $photo_file_id ? get_file_name_from_google_drive_caste($photo_file_id, $api_key) : null;

                                                        // Now $photo_filename will hold the file name, or null if caste_document is empty or invalid.
                                                    } else {
                                                        // If caste_document is null or empty, handle accordingly
                                                        $photo_filename = null;
                                                    }
                                                    ?>

                                                    <input type="file" class="form-control" id="caste-document" name="caste-document" accept=".pdf,.jpg,.jpeg,.png">
                                                    <!-- Display existing Caste Certificate if available -->
                                                    <?php if (!empty($photo_filename)): ?>
                                                        <div>
                                                            <a href="<?php echo htmlspecialchars($array['caste_document']); ?>" target="_blank"><?php echo htmlspecialchars($photo_filename); ?></a>
                                                        </div>
                                                    <?php endif; ?>
                                                    <small id="caste-document-help" class="form-text text-muted">Upload your caste certificate (PDF, JPG, JPEG, or PNG).</small>
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
                                                    <?php echo $array["purpose"] ?>
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
                                                    <?php echo $array["membership_purpose"] ?>
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
                                                    <label for="applicant-photo">Upload Applicant Photo:</label>
                                                </td>
                                                <td>
                                                    <?php
                                                    // Extract file ID from Google Drive link
                                                    function extract_file_id($url)
                                                    {
                                                        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $url, $matches)) {
                                                            return $matches[1];
                                                        }
                                                        return null;
                                                    }

                                                    // Function to get file name from Google Drive using file ID
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

                                                    $api_key = "AIzaSyCtWC48inXWXUM8s6hSeX89LP78sfGLk_g"; // Replace with your actual Google Drive API Key
                                                    $photo_file_id = extract_file_id($array['applicant_photo']);
                                                    $photo_filename = $photo_file_id ? get_file_name_from_google_drive($photo_file_id, $api_key) : null;
                                                    ?>
                                                    <input type="file" class="form-control" id="applicant-photo" name="applicant-photo" accept="image/*">

                                                    <?php if (!empty($photo_filename)): ?>
                                                        <div>
                                                            <a href="<?php echo htmlspecialchars($array['applicant_photo']); ?>" target="_blank"><?php echo htmlspecialchars($photo_filename); ?></a>
                                                        </div>
                                                    <?php endif; ?>

                                                    <small id="applicant-photo-help" class="form-text text-muted">
                                                        Please upload a recent passport size photograph of the applicant.
                                                    </small>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="resume-upload">Upload Resume:</label>
                                                </td>
                                                <td>
                                                    <?php
                                                    $resume_file_id = extract_file_id($array['resume_upload']);
                                                    $resume_filename = $resume_file_id ? get_file_name_from_google_drive($resume_file_id, $api_key) : null;
                                                    ?>
                                                    <input type="file" class="form-control" id="resume-upload" name="resume-upload">

                                                    <?php if (!empty($resume_filename)): ?>
                                                        <div>
                                                            <a href="<?php echo htmlspecialchars($array['resume_upload']); ?>" target="_blank"><?php echo htmlspecialchars($resume_filename); ?></a>
                                                        </div>
                                                    <?php endif; ?>

                                                    <small id="resume-upload-help" class="form-text text-muted">
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
                                <?php } ?>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </form>

                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

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