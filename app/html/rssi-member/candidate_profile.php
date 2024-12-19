<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

$application_number = isset($_GET['application_number']) ? $_GET['application_number'] : null;

// SQL query to fetch current data
$sql = "SELECT * FROM signup WHERE application_number='$application_number'";
$result = pg_query($con, $sql);
$resultArr = pg_fetch_all($result);
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize an array to store field updates
    $updates = [];

    // Check each possible field and add it to the updates array if it exists in $_POST
    if (isset($_POST['photo_verification'])) {
        $photo_verification = pg_escape_string($con, $_POST['photo_verification']);
        $updates[] = "photo_verification = '$photo_verification'";
        // Conditional update for application_status based on the identity_verification status
        if ($photo_verification == 'Approved') {
            $updates[] = "application_status = 'Photo Verification Completed'";
        } elseif ($photo_verification == 'Rejected') {
            $updates[] = "application_status = 'Photo Verification Failed'";
        }
    }

    if (isset($_POST['identity_verification'])) {
        $identity_verification = pg_escape_string($con, $_POST['identity_verification']);
        $updates[] = "identity_verification = '$identity_verification'";

        // Conditional update for application_status based on the identity_verification status
        if ($identity_verification == 'Approved') {
            $updates[] = "application_status = 'Identity Verification Completed'";
        } elseif ($identity_verification == 'Rejected') {
            $updates[] = "application_status = 'Identity Verification Failed'";
        }
    }

    if (isset($_POST['tech_interview_schedule']) && !empty($_POST['tech_interview_schedule'])) {
        $tech_interview_schedule = pg_escape_string($con, $_POST['tech_interview_schedule']);
        $updates[] = "tech_interview_schedule = '$tech_interview_schedule'";
        $updates[] = "application_status = 'Technical Interview Scheduled'";
    }

    if (isset($_POST['hr_interview_schedule']) && !empty($_POST['hr_interview_schedule'])) {
        $hr_interview_schedule = pg_escape_string($con, $_POST['hr_interview_schedule']);
        $updates[] = "hr_interview_schedule = '$hr_interview_schedule'";
        $updates[] = "application_status = 'HR Interview Scheduled'";
    }
    if (isset($_POST['no_show']) && $_POST['no_show'] === 'on') {
        $no_show = pg_escape_string($con, $_POST['no_show']);
        $updates[] = "no_show = TRUE";
        $updates[] = "application_status = 'No-Show'";
    }

    if (isset($_POST['offer_extended'])) {
        $offer_extended = pg_escape_string($con, $_POST['offer_extended']);
        $updates[] = "offer_extended = '$offer_extended'";
        // Conditional update for application_status based on the offer_extended status
        if ($offer_extended == 'Yes') {
            $updates[] = "application_status = 'Offer Extended'";
        } elseif ($offer_extended == 'No') {
            $updates[] = "application_status = 'Offer Not Extended'";
        }
    }

    // If no updates are present, exit
    if (empty($updates)) {
        echo "No fields to update.";
        exit;
    }

    // Construct the dynamic UPDATE query
    $update_query = "UPDATE signup SET " . implode(", ", $updates) . " WHERE application_number = '$application_number'";

    // Execute the query
    $update_result = pg_query($con, $update_query);
    $cmdtuples = pg_affected_rows($update_result);

    if ($cmdtuples == 1) {
        // Success: Profile was updated
        echo '<script>
            var applicationNumber = "' . $_GET['application_number'] . '";
            alert("Profile updated successfully!");
            window.location.href = "candidate_profile.php?application_number=" + applicationNumber;  // Reload the page
        </script>';
    } else {
        // Failure: Record was not updated
        echo '<script>
            alert("Error: We encountered an error while updating the record. Please try again.");
        </script>';
    }
}

$isFormDisabled = null;
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
                            <?php
                            // If no application number is provided, show the input form
                            if (!$application_number): ?>
                                <div class="container mt-5">
                                    <h4 class="mb-3">Enter Application Number</h4>
                                    <form method="GET" action="">
                                        <div class="input-group mb-3">
                                            <input type="text" name="application_number" class="form-control" placeholder="Enter Application Number" required>
                                            <button class="btn btn-primary" type="submit">Submit</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
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
                                $supporting_document_filename = !empty($array['supporting_document']) ? get_file_name_from_google_drive(extract_file_id($array['supporting_document']), $api_key) : null;
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
                                                                <?php echo $array["telephone"] ?>
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
                                                                <?php echo $array["postal_address"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="permanent-address">Permanent Address:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["permanent_address"] ?>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <label for="education-qualification" class="form-label">Educational Qualification:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["education_qualification"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="specialization" class="form-label">Area of Specialization:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["specialization"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="work-experience" class="form-label">Work Experience:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["work_experience"] ?>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <label for="caste">Caste:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["caste"] ?>
                                                            </td>
                                                        </tr>

                                                        <!-- Supporting Document Upload Field -->
                                                        <tr>
                                                            <td>
                                                                <label for="caste-document">Caste Certificate:</label>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($caste_filename)): ?>
                                                                    <a href="<?php echo htmlspecialchars($array['caste_document']); ?>" target="_blank"><?php echo htmlspecialchars($caste_filename); ?></a>
                                                                <?php else: ?>
                                                                    <span>No file uploaded yet.</span>
                                                                <?php endif; ?>
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
                                                                <?php if (!empty($photo_filename)): ?>
                                                                    <a href="<?php echo htmlspecialchars($array['applicant_photo']); ?>" target="_blank"><?php echo htmlspecialchars($photo_filename); ?></a>
                                                                <?php else: ?>
                                                                    <span>No file uploaded yet.</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="resume-upload">Upload Resume:</label>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($resume_filename)): ?>
                                                                    <a href="<?php echo htmlspecialchars($array['resume_upload']); ?>" target="_blank"><?php echo htmlspecialchars($resume_filename); ?></a>
                                                                <?php else: ?>
                                                                    <span>No file uploaded yet.</span>
                                                                <?php endif; ?>
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
                                                        <tr>
                                                            <td>
                                                                <label for="payment-photo">National Identifier Number:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array['identifier'] ?><br>
                                                                <?php echo $array['identifier_number'] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="applicant-photo">Supporting Document:</label>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($supporting_document_filename)): ?>
                                                                    <a href="<?php echo htmlspecialchars($array['supporting_document']); ?>" target="_blank"><?php echo htmlspecialchars($supporting_document_filename); ?></a>
                                                                <?php else: ?>
                                                                    <span>No file uploaded yet.</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <!-- Photo Verification -->
                                                        <tr>
                                                            <td>
                                                                <label for="photo_verification">Photo Verification:</label>
                                                            </td>
                                                            <td>
                                                                <select class="form-select" id="photo_verification" name="photo_verification" <?php echo (($array['application_status'] == 'Application Submitted' || $array['application_status'] == 'Application Re-Submitted') && $array['photo_verification'] != 'Approved') ? '' : 'disabled'; ?>>
                                                                    <option value="" disabled <?php echo empty($array['photo_verification']) ? 'selected' : ''; ?>>Select status</option>
                                                                    <option value="Approved" <?php echo ($array['photo_verification'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                                                    <option value="Rejected" <?php echo ($array['photo_verification'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                                </select>
                                                                <small id="photo-help" class="form-text text-muted">Approve or reject the uploaded photo.</small>
                                                            </td>
                                                        </tr>

                                                        <!-- Identity Verification -->
                                                        <tr>
                                                            <td>
                                                                <label for="identity_verification">Identity Verification:</label>
                                                            </td>
                                                            <td>
                                                                <select class="form-select" id="identity_verification" name="identity_verification" <?php echo ($array['identity_verification'] == 'Approved' || $array['application_status'] != 'Photo Verification Completed') ? 'disabled' : ''; ?>>
                                                                    <option value="" disabled <?php echo empty($array['identity_verification']) ? 'selected' : ''; ?>>Select status</option>
                                                                    <option value="Approved" <?php echo ($array['identity_verification'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                                                    <option value="Rejected" <?php echo ($array['identity_verification'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                                </select>
                                                                <small id="identity-help" class="form-text text-muted">Approve or reject the identity verification status.</small>
                                                            </td>
                                                        </tr>

                                                        <!-- Technical Interview Schedule -->
                                                        <tr>
                                                            <td>
                                                                <label for="tech_interview_schedule">Schedule Technical Interview:</label>
                                                            </td>
                                                            <td>
                                                                <input type="datetime-local" class="form-control" id="tech_interview_schedule" name="tech_interview_schedule"
                                                                    value="<?php echo htmlspecialchars($array['tech_interview_schedule'] ?? ''); ?>" <?php echo (!empty($array['tech_interview_schedule']) || $array['application_status'] != 'Identity Verification Completed') ? 'disabled' : ''; ?>>
                                                                <small id="tech-help" class="form-text text-muted">Select the date and time for the technical interview.</small>
                                                            </td>
                                                        </tr>

                                                        <!-- HR Interview Schedule -->
                                                        <tr>
                                                            <td>
                                                                <label for="hr_interview_schedule">Schedule HR Interview:</label>
                                                            </td>
                                                            <td>
                                                                <input type="datetime-local" class="form-control" id="hr_interview_schedule" name="hr_interview_schedule"
                                                                    value="<?php echo htmlspecialchars($array['hr_interview_schedule'] ?? ''); ?>"
                                                                    <?php echo (!empty($array['hr_interview_schedule']) || $array['application_status'] != 'Technical Interview Completed') ? 'disabled' : ''; ?>>
                                                                <small id="hr-help" class="form-text text-muted">Select the date and time for the HR interview.</small>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="no_show">No Show:</label>
                                                            </td>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input" id="no_show" name="no_show"
                                                                    <?php
                                                                    // Enable checkbox only for 'Technical Interview Scheduled' or 'HR Interview Scheduled'
                                                                    if (in_array($array['application_status'], ['Technical Interview Scheduled', 'HR Interview Scheduled'])) {
                                                                        // Check if the checkbox should be checked
                                                                        echo ($array['no_show'] == 'true') ? 'checked' : '';
                                                                    } else {
                                                                        // Disable for all other statuses
                                                                        echo 'disabled';
                                                                    }
                                                                    ?>>
                                                                <small id="no-show-help" class="form-text text-muted">Check if the candidate is marked as No-Show.</small>
                                                            </td>
                                                        </tr>

                                                        <!-- Offer Extended -->
                                                        <tr>
                                                            <td>
                                                                <label for="offer_extended">Offer Extended:</label>
                                                            </td>
                                                            <td>
                                                                <select class="form-select" id="offer_extended" name="offer_extended" <?php echo (!empty($array['offer_extended'])) || ($array['application_status'] != 'Recommended') ? 'disabled' : ''; ?>>
                                                                    <option value="" disabled <?php echo empty($array['offer_extended']) ? 'selected' : ''; ?>>Select status</option>
                                                                    <option value="Yes" <?php echo ($array['offer_extended'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                                                    <option value="No" <?php echo ($array['offer_extended'] == 'No') ? 'selected' : ''; ?>>No</option>
                                                                </select>
                                                                <small id="offer-help" class="form-text text-muted">Confirm if the offer has been extended or not.</small>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Submit</button>
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

</body>

</html>