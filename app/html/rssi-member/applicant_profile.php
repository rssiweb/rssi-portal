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
// Check if there are any results
if ($resultArr && count($resultArr) > 0) {
    // Accessing specific column values from the first result (assuming there is only one row)
    $applicant_email = $resultArr[0]['email'];
    $applicant_name = $resultArr[0]['applicant_name'];
    $application_number = $resultArr[0]['application_number'];
    $applicant_telephone = $resultArr[0]['telephone'];
    $association = $resultArr[0]['association'];
    $post_select = $resultArr[0]['post_select'];
}
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize an array to store field updates
    $updates = [];

    // Check if remarks is set and add it to the updates array
    if (isset($_POST['remarks'])) {
        $remarks = pg_escape_string($con, $_POST['remarks']);
        $updates[] = "remarks = '$remarks'";
    }

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

    if (isset($_POST['skip_tech_interview']) && $_POST['skip_tech_interview'] === 'on') {
        $no_show = pg_escape_string($con, $_POST['skip_tech_interview']);
        $updates[] = "skip_tech_interview = TRUE";
        $updates[] = "application_status = 'Technical Interview Completed'";
    }
    if (isset($_POST['skip_hr_interview']) && $_POST['skip_hr_interview'] === 'on') {
        $no_show = pg_escape_string($con, $_POST['skip_hr_interview']);
        $updates[] = "skip_hr_interview = TRUE";
        $updates[] = "application_status = 'Recommended'";
    }
    if (isset($_POST['cancel_application']) && $_POST['cancel_application'] === 'on') {
        $cancel_application = pg_escape_string($con, $_POST['cancel_application']);
        $updates[] = "is_active = false";
        $updates[] = "application_status = 'Cancelled'";
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
    if (isset($_POST['offer_extended'])) {
        // Retrieve the offer_extended value
        $offer_extended = $_POST['offer_extended'];

        // Conditional insert into rssimyaccount_members if offer_extended is 'Yes'
        if ($offer_extended === 'Yes') {
            $insert_query = "
            INSERT INTO rssimyaccount_members (
                fullname,
                email,
                basebranch,
                gender,
                dateofbirth,
                currentaddress,
                permanentaddress,
                workexperience,
                nationalidentifier,
                applicationnumber,
                position,
                engagement,
                phone,
                identifier,
                raw_photo,
                filterstatus,
                iddoc,
                eduq,
                mjorsub,
                approvedby,
                associatenumber,
                college_name,
                enrollment_number
            )
            SELECT 
                applicant_name AS fullname,
                email,
                branch AS basebranch,
                gender,
                date_of_birth AS dateofbirth,
                postal_address AS currentaddress,
                permanent_address AS permanentaddress,
                work_experience AS workexperience,
                identifier_number AS nationalidentifier,
                application_number AS applicationnumber,
                post_select AS position,
                association AS engagement,
                telephone AS phone,
                identifier,
                applicant_photo AS raw_photo,
                'In Progress' AS filterstatus,
                supporting_document AS iddoc,
                education_qualification AS eduq,
                specialization AS mjorsub,
                '$associatenumber' AS approvedby,
                CONCAT(
                RIGHT(EXTRACT(YEAR FROM CURRENT_DATE)::text, 2),   -- Current Year (2 digits)
                LPAD(EXTRACT(MONTH FROM CURRENT_DATE)::text, 2, '0'), -- Current Month (2 digits)
                LPAD(
                    (SELECT COUNT(*) + 1 
                    FROM rssimyaccount_members
                    )::text, 3, '0' -- Adding +1 to the total count
                )
            ) AS associatenumber,
            college_name,
            enrollment_number
            FROM signup 
            WHERE application_number = '$application_number';
            ";
            // Execute the query (assuming you have a database connection $con)
            $result_insert_query = pg_query($con, $insert_query);
        }
    }

    // Execute the query
    $update_result = pg_query($con, $update_query);
    $cmdtuples = pg_affected_rows($update_result);

    if (isset($_POST['photo_verification'])) {
        // Check if the query was successful
        // if ($cmdtuples == 1 && $photo_verification == 'Approved') {
        //     if ($applicant_email != "") {
        //         // Adjust the parameters for your sendEmail function accordingly
        //         sendEmail("tap_photo_verification_completed", array(
        //             "application_number" => $application_number,
        //             "applicant_name" => $applicant_name
        //         ), $applicant_email, False);
        //     }
        // }
        if ($cmdtuples == 1 && $photo_verification == 'Rejected') {
            if ($applicant_email != "") {
                // Adjust the parameters for your sendEmail function accordingly
                sendEmail("tap_photo_verification_failed", array(
                    "application_number" => $application_number,
                    "applicant_name" => $applicant_name
                ), $applicant_email, False);
            }
        }
    }
    if (isset($_POST['identity_verification'])) {
        if ($cmdtuples == 1 && $identity_verification == 'Approved') {
            if ($applicant_email != "") {
                // Adjust the parameters for your sendEmail function accordingly
                sendEmail("tap_identity_verification_completed", array(
                    "application_number" => $application_number,
                    "applicant_name" => $applicant_name
                ), $applicant_email, False);
            }
        }
        if ($cmdtuples == 1 && $identity_verification == 'Rejected') {
            if ($applicant_email != "") {
                // Adjust the parameters for your sendEmail function accordingly
                sendEmail("tap_identity_verification_failed", array(
                    "application_number" => $application_number,
                    "applicant_name" => $applicant_name
                ), $applicant_email, False);
            }
        }
    }
    if (isset($_POST['tech_interview_schedule']) && !empty($_POST['tech_interview_schedule'])) {
        if ($cmdtuples == 1 && !empty($tech_interview_schedule) && (empty($no_show) || $no_show == false)) {
            if ($applicant_email != "") {

                // Determine template based on association and post
                if ($association == "Volunteer" || $association == "Intern") {
                    $template = "tap_pi_schedule";
                } elseif ($association == "Employee" && $post_select != "Faculty") {
                    $template = "tap_pi_schedule";
                } else {
                    // For Employee with Faculty post OR direct Faculty association
                    $template = "tap_technical_interview_schedule";
                }

                // Send email with dynamic values
                sendEmail($template, array(
                    "application_number"      => $application_number,
                    "applicant_name"          => $applicant_name,
                    "tech_interview_schedule" => date("d/m/Y g:i a", strtotime($tech_interview_schedule)),
                    "post_select"             => $post_select
                ), $applicant_email, false);
            }
        }
    }
    if (isset($_POST['hr_interview_schedule']) && !empty($_POST['hr_interview_schedule'])) {
        if ($cmdtuples == 1 && !empty($hr_interview_schedule) && (empty($no_show) || $no_show == false)) {
            if ($applicant_email != "") {
                // Adjust the parameters for your sendEmail function accordingly
                sendEmail("tap_hr_interview_schedule", array(
                    "application_number" => $application_number,
                    "applicant_name" => $applicant_name,
                    "hr_interview_schedule" => date("d/m/Y g:i a", strtotime($hr_interview_schedule))
                ), $applicant_email, False);
            }
        }
    }
    if (isset($_POST['no_show']) && $_POST['no_show'] === 'on') {
        if ($cmdtuples == 1 && $no_show == true) {
            if ($applicant_email != "") {
                // Adjust the parameters for your sendEmail function accordingly
                sendEmail("tap_no_show", array(
                    "application_number" => $application_number,
                    "applicant_name" => $applicant_name
                ), $applicant_email, False);
            }
        }
    }

    if (isset($result_insert_query) && $result_insert_query && pg_affected_rows($result_insert_query) > 0) {
        // Insert was successful
        echo '<script>
            var applicationNumber = "' . htmlspecialchars($_GET['application_number']) . '";
            alert("Record successfully inserted into rssimyaccount_members.");
            window.location.href = "applicant_profile.php?application_number=" + applicationNumber;  // Redirect to the applicant profile page
        </script>';
    } elseif ($cmdtuples == 1) {
        // Profile was updated successfully
        echo '<script>
            var applicationNumber = "' . htmlspecialchars($_GET['application_number']) . '";
            alert("Changes to the Applicant Profile have been saved successfully.");
            window.location.href = "applicant_profile.php?application_number=" + applicationNumber;  // Reload the page
        </script>';
    } else {
        // Handle error case (either insert or update failed)
        echo '<script>
            alert("Error: Unable to complete the operation. ' . addslashes(pg_last_error($con)) . '");
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

    <title>Applicant_Profile_<?php echo $applicant_name; ?>_<?php echo $application_number; ?></title>

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
                                                                <label for="college_name" class="form-label">College/University Name:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["college_name"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="enrollment_number" class="form-label">Enrolment Number:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["enrollment_number"] ?>
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
                                                                <select class="form-select" id="photo_verification" name="photo_verification" <?php echo (($array['application_status'] == 'Application Submitted' || $array['application_status'] == 'Application Re-Submitted' || $array['application_status'] == 'Identity verification document submitted') && $array['photo_verification'] != 'Approved') ? '' : 'disabled'; ?>>
                                                                    <option disabled <?php echo empty($array['photo_verification']) ? 'selected' : ''; ?>>Select status</option>
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
                                                                <select class="form-select" id="identity_verification" name="identity_verification" <?php echo ((empty($array['photo_verification']) || $array['photo_verification'] != 'Approved' || $array['identity_verification'] == 'Approved' || empty($array['supporting_document']) || ($array['identity_verification'] == 'Rejected' && !empty($array['supporting_document']) && $array['application_status'] != 'Identity verification document submitted'))) ? 'disabled' : ''; ?>>
                                                                    <option disabled <?php echo empty($array['identity_verification']) ? 'selected' : ''; ?>>Select status</option>
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
                                                                        echo ($array['no_show'] == true) ? 'checked' : '';
                                                                    } else {
                                                                        // Disable for all other statuses
                                                                        echo ($array['no_show'] == true) ? 'checked disabled' : 'disabled';
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

                                                        <tr>
                                                            <td>
                                                                <label for="cancel_application">Cancel Application:</label>
                                                            </td>
                                                            <td>
                                                                <input type="checkbox" class="form-check-input" id="cancel_application"
                                                                    name="cancel_application"
                                                                    <?php
                                                                    // Enable checkbox only if the application is currently active
                                                                    if ($array['is_active']===true) {
                                                                        // Active applications: checkbox unchecked by default
                                                                        echo '';
                                                                    } else {
                                                                        // Inactive applications: checkbox checked and disabled
                                                                        echo 'checked disabled';
                                                                    }
                                                                    ?>>
                                                                <small id="cancel-help" class="form-text text-muted">
                                                                    Check to mark the application as cancelled.
                                                                </small>
                                                            </td>
                                                        </tr>

                                                        <!-- Offer Extended -->
                                                        <tr>
                                                            <td>
                                                                <label for="offer_extended">Offer Extended:</label>
                                                            </td>
                                                            <td>
                                                                <select class="form-select" id="offer_extended" name="offer_extended" <?php echo (!empty($array['offer_extended'])) || ($array['application_status'] != 'Recommended') ? 'disabled' : ''; ?>>
                                                                    <option disabled <?php echo empty($array['offer_extended']) ? 'selected' : ''; ?>>Select status</option>
                                                                    <option value="Yes" <?php echo ($array['offer_extended'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                                                    <option value="No" <?php echo ($array['offer_extended'] == 'No') ? 'selected' : ''; ?>>No</option>
                                                                </select>
                                                                <small id="offer-help" class="form-text text-muted">Confirm if the offer has been extended or not.</small>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="remarks">Remarks:</label>
                                                            </td>
                                                            <td>
                                                                <div class="form-floating mb-2">
                                                                    <textarea name="remarks" class="form-control" class="form-control" placeholder="Leave a comment here"><?php echo $array['remarks'] ?></textarea>
                                                                    <label for="remarks" class="form-label">Remarks</label>
                                                                </div>
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

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

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