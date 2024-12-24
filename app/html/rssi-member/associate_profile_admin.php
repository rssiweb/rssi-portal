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

$associatenumber = isset($_GET['associatenumber']) ? $_GET['associatenumber'] : null;

// SQL query to fetch current data
$sql = "SELECT * FROM rssimyaccount_members WHERE associatenumber='$associatenumber'";
$result = pg_query($con, $sql);
$resultArr = pg_fetch_all($result);
// Check if there are any results
if ($resultArr && count($resultArr) > 0) {
    // Accessing specific column values from the first result (assuming there is only one row)
    $associate_email = $resultArr[0]['email'];
    $associate_name = $resultArr[0]['fullname'];
    $associatenumber = $resultArr[0]['associatenumber'];
    $associate_telephone = $resultArr[0]['phone'];
}
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize an array to store field updates
    $updates = [];
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
    $update_query = "UPDATE rssimyaccount_members SET " . implode(", ", $updates) . " WHERE associatenumber = '$associatenumber'";

    // Execute the query
    $update_result = pg_query($con, $update_query);
    $cmdtuples = pg_affected_rows($update_result);

    if (isset($_POST['hr_interview_schedule']) && !empty($_POST['hr_interview_schedule'])) {
        if ($cmdtuples == 1 && !empty($hr_interview_schedule) && (empty($no_show) || $no_show == false)) {
            if ($associate_email != "") {
                // Adjust the parameters for your sendEmail function accordingly
                sendEmail("tap_hr_interview_schedule", array(
                    "associatenumber" => $associatenumber,
                    "applicant_name" => $applicant_name,
                    "hr_interview_schedule" => date("d/m/Y g:i a", strtotime($hr_interview_schedule))
                ), $associate_email, False);
            }
        }
    }

    // Check if profile was updated
    if ($cmdtuples == 1) {
        // Success: Profile was updated
        echo '<script>
        var applicationNumber = "' . htmlspecialchars($_GET['associatenumber']) . '";
        alert("Changes to the Applicant Profile have been saved successfully.");
        window.location.href = "applicant_profile.php?associatenumber=" + applicationNumber;  // Reload the page
    </script>';
    } else {
        // Failure: Profile was not updated
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

    <title>Applicant_Profile_<?php echo $applicant_name; ?>_<?php echo $associatenumber; ?></title>

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
            <h1>Applicant Profile</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">People Plus</li>
                    <li class="breadcrumb-item"><a href="talent_pool.php">Talent Pool</a></li>
                    <li class="breadcrumb-item">Applicant Profile</li>
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
                            if (!$associatenumber): ?>
                                <div class="container mt-5">
                                    <h4 class="mb-3">Enter Application Number</h4>
                                    <form method="GET" action="">
                                        <div class="input-group mb-3">
                                            <input type="text" name="associatenumber" class="form-control" placeholder="Enter Application Number" required>
                                            <button class="btn btn-primary" type="submit">Submit</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <?php foreach ($resultArr as $array) { ?>
                                <?php
                                // Function to generate the WhatsApp message link
                                function getWhatsAppLink($array, $custom_message)
                                {
                                    // Construct the message
                                    $message = "Dear " . $array['applicant_name'] . " (" . $array['associatenumber'] . "),\n\n"
                                        . $custom_message . "\n\n"
                                        . "--RSSI\n\n"
                                        . "**This is a system generated message.";

                                    // Encode the message to make it URL-safe
                                    $encoded_message = urlencode($message);

                                    // Generate and return the WhatsApp URL
                                    return "https://api.whatsapp.com/send?phone=" . $array['telephone'] . "&text=" . $encoded_message;
                                }

                                // Define different messages
                                $message1 = "As per the system records, you have not yet completed your identity verification. Please check your registered email address and complete the verification at your convenience. Post that the interview will be scheduled with the technical team of RSSI.";
                                $message2 = "Your photo has been rejected in the system due to one or more of the reasons mentioned below:\n\n"
                                    . "1) The photo is not formal.\n"
                                    . "2) The background of the photo is not clear.\n"
                                    . "3) The face is not camera-facing, straight, and formal, or the head or ears are covered.\n\n"
                                    . "Please log in to your account and re-submit a valid photo for verification.";
                                $message3 = "Your identity verification has been REJECTED in the system due to any of the reasons mentioned below:\n\n"
                                    . "1) The document is invalid.\n"
                                    . "2) The document is password protected.\n"
                                    . "3) The National Identifier Number is invalid.\n"
                                    . "4) Improper scanning of the uploaded document. Please scan the entire document and if the address or any other relevant information is mentioned on the other side, scan both sides of the National Identifier.\n\n"
                                    . "Please ensure that the scanned document is clearly legible, and re-upload the same.";
                                $message4 = "Your document has been successfully verified. You will receive your interview schedule soon.";
                                $message5 = "We are pleased to inform you that your interview slot for the Faculty position has been successfully booked. Please take note of the following details:\n\n"
                                    . "Reporting Date & Time: " . !empty($array['tech_interview_schedule']) && $array['tech_interview_schedule'] !== null ? (new DateTime($array['tech_interview_schedule']))->format('d/m/Y h:i a') : 'No interview scheduled' . "\n"
                                    . "Reporting Address: D/1/122, Vinamra Khand, Gomti Nagar, Lucknow, Uttar Pradesh 226010\n\n"
                                    . "To know more about the interview process and specific instructions, kindly check your registered email ID.\n\n"
                                    . "We appreciate your interest in joining RSSI NGO and look forward to your participation in the interview process.";
                                $message6 = "We are pleased to inform you that your profile has been shortlisted for the HR round. "
                                    . "You will receive the calendar invite shortly. Please keep checking your registered email ID for more details.";
                                $message7 = "Thank you for exploring career opportunities with Rina Shiksha Sahayak Foundation (RSSI). "
                                    . "We are pleased to inform you that you have successfully completed our initial selection process, and we are delighted to extend an offer to you.\n\n"
                                    . "We will share the offer letter with you shortly. Upon receipt, please follow the instructions provided to proceed with the next steps.";
                                $message8 = "Thank you for taking the time to interview with us. Your feedback is invaluable in helping us improve our recruitment process. "
                                    . "We would appreciate it if you could share your interview experience by leaving a review on Google.\n\n"
                                    . "https://g.page/r/CQkWqmErGMS7EAg/review\n\n"
                                    . "Your insights are important to us, and we are committed to continually enhancing our candidate experience. Thank you for your contribution.";

                                // Generate WhatsApp links
                                $link1 = getWhatsAppLink($array, $message1);
                                $link2 = getWhatsAppLink($array, $message2);
                                $link3 = getWhatsAppLink($array, $message3);
                                $link4 = getWhatsAppLink($array, $message4);
                                $link5 = getWhatsAppLink($array, $message5);
                                $link6 = getWhatsAppLink($array, $message6);
                                $link7 = getWhatsAppLink($array, $message7);
                                $link8 = getWhatsAppLink($array, $message8);
                                ?>
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
                                $photo_filename = !empty($array['photo']) ? get_file_name_from_google_drive(extract_file_id($array['photo']), $api_key) : null;
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
                                                                    <td><?php echo $array["associatenumber"] ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><label for="applicant-name">Applicant Name:</label></td>
                                                                    <td><?php echo $array["fullname"] ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><label for="date-of-birth">Date of Birth:</label></td>
                                                                    <td><?php echo $array["dateofbirth"] ?></td>
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
                                                                if (!empty($array['photo'])) {
                                                                    $preview_url = $array['photo'];
                                                                    echo '<img src="' . $preview_url . '" width="150" height="200" ></img>';
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
                                                                <?php echo $array["phone"] ?>
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
                                                                <?php echo $array["currentaddress"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="permanent-address">Permanent Address:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["permanentaddress"] ?>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <label for="education-qualification" class="form-label">Educational Qualification:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["eduq"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="specialization" class="form-label">Area of Specialization:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["mjorsub"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="work-experience" class="form-label">Work Experience:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["workexperience"] ?>
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

                                                        <tr>
                                                            <td>
                                                                <label for="branch">Preferred
                                                                    Branch:</label>
                                                            </td>
                                                            <td><?php echo $array["basebranch"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="class">Type of association:</label>
                                                            </td>
                                                            <td><?php echo $array["position"].'-'.$array["post_select"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td width="30%">
                                                                <label for="job-select">Job code:
                                                                </label>
                                                            </td>
                                                            <td><?php echo $array["job_type"] ?>
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
                                                                    <a href="<?php echo htmlspecialchars($array['photo']); ?>" target="_blank"><?php echo htmlspecialchars($photo_filename); ?></a>
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
                                                                <select class="form-select" id="photo_verification" name="photo_verification" <?php echo (($array['application_status'] == 'Application Submitted' || $array['application_status'] == 'Application Re-Submitted' || $array['application_status'] == 'Identity verification document submitted') && $array['photo_verification'] != 'Approved') ? '' : 'disabled'; ?>>
                                                                    <option value="" disabled <?php echo empty($array['photo_verification']) ? 'selected' : ''; ?>>Select status</option>
                                                                    <option value="Approved" <?php echo ($array['photo_verification'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                                                    <option value="Rejected" <?php echo ($array['photo_verification'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                                </select>
                                                                <small id="photo-help" class="form-text text-muted">Approve or reject the uploaded photo.</small>
                                                                <?php
                                                                if ($array['application_status'] == "Photo Verification Failed") {
                                                                    echo '<a href="' . $link2 . '" target="_blank">Photo Rejected</a>';
                                                                }
                                                                ?>
                                                            </td>
                                                        </tr>

                                                        <!-- Identity Verification -->
                                                        <tr>
                                                            <td>
                                                                <label for="identity_verification">Identity Verification:</label>
                                                            </td>
                                                            <td>
                                                                <select class="form-select" id="identity_verification" name="identity_verification" <?php echo ((empty($array['photo_verification']) || $array['photo_verification'] != 'Approved' || $array['identity_verification'] == 'Approved' || empty($array['supporting_document']) || ($array['identity_verification'] == 'Rejected' && !empty($array['supporting_document']) && $array['application_status'] != 'Identity verification document submitted'))) ? 'disabled' : ''; ?>>
                                                                    <option value="" disabled <?php echo empty($array['identity_verification']) ? 'selected' : ''; ?>>Select status</option>
                                                                    <option value="Approved" <?php echo ($array['identity_verification'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                                                    <option value="Rejected" <?php echo ($array['identity_verification'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                                </select>
                                                                <small id="identity-help" class="form-text text-muted">Approve or reject the identity verification status.</small>
                                                                <?php
                                                                if (empty($array['supporting_document']) || ($array['identity_verification'] == 'Rejected') && $array['application_status'] != 'Identity verification document submitted') {
                                                                    echo '<a href="' . $link1 . '" target="_blank">Reminder</a>';
                                                                }
                                                                ?>
                                                                <?php
                                                                switch ($array['application_status']) {
                                                                    case "Identity Verification Failed":
                                                                        echo '<a href="' . $link3 . '" target="_blank">Verification Rejected</a>';
                                                                        break;
                                                                    case "Identity Verification Completed":
                                                                        echo '<a href="' . $link4 . '" target="_blank">Verification Approved</a>';
                                                                        break;
                                                                        // Add more cases as needed
                                                                    default:
                                                                        // Optionally, handle the case where none of the statuses match
                                                                        break;
                                                                }
                                                                ?>

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
                                                                <?php
                                                                switch ($array['application_status']) {
                                                                    case "Technical Interview Scheduled":
                                                                        echo '<a href="' . $link5 . '" target="_blank">Interview Scheduled</a>';
                                                                        break;
                                                                    case "Technical Interview Completed":
                                                                        echo '<a href="' . $link8 . '" target="_blank">Interview Feedback</a>';
                                                                        break;
                                                                        // Add more cases as needed
                                                                    default:
                                                                        // Optionally, handle the case where none of the statuses match
                                                                        break;
                                                                }
                                                                ?>
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
                                                                <?php
                                                                if ($array['application_status'] == "HR Interview Scheduled") {
                                                                    echo '<a href="' . $link6 . '" target="_blank">HR Interview Scheduled</a>';
                                                                }
                                                                ?>
                                                                <a href="<?php echo $link6; ?>" target="_blank"></a>
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

                                                        <tr>
                                                            <td>
                                                                <label for="no_show">Skip Techniccal Interview:</label>
                                                            </td>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input" id="skip_tech_interview" name="skip_tech_interview"
                                                                    <?php
                                                                    // Enable checkbox only for 'Technical Interview Scheduled' or 'HR Interview Scheduled'
                                                                    if (in_array($array['application_status'], ['Identity Verification Completed'])) {
                                                                        // Check if the checkbox should be checked
                                                                        echo ($array['skip_tech_interview'] == true) ? 'checked' : '';
                                                                    } else {
                                                                        // Disable for all other statuses
                                                                        echo ($array['skip_tech_interview'] == true) ? 'checked disabled' : 'disabled';
                                                                    }
                                                                    ?>>
                                                                <small id="no-show-help" class="form-text text-muted">Check if the candidate is marked to skip the technical interview.</small>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="no_show">Skip HR Interview:</label>
                                                            </td>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input" id="skip_hr_interview" name="skip_hr_interview"
                                                                    <?php
                                                                    // Enable checkbox only for 'Technical Interview Completed'
                                                                    if (in_array($array['application_status'], ['Technical Interview Completed'])) {
                                                                        // Allow user interaction and check the box if 'skip_hr_interview' is true
                                                                        echo ($array['skip_hr_interview'] == true) ? 'checked' : '';
                                                                    } else {
                                                                        // Check if the checkbox should be checked, but it will be visually disabled
                                                                        echo ($array['skip_hr_interview'] == true) ? 'checked disabled' : 'disabled';
                                                                        // For all other statuses, disable the checkbox
                                                                        // echo 'disabled';
                                                                    }
                                                                    ?>>
                                                                <small id="no-show-help" class="form-text text-muted">Check if the candidate is marked to skip the HR interview.</small>
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
                                                                <?php
                                                                if ($array['application_status'] == "Offer Extended") {
                                                                    echo '<a href="' . $link7 . '" target="_blank">Offer Extended</a>';
                                                                }
                                                                ?>
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