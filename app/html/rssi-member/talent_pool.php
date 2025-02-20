<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
// Fetch POST values or set default dates
$filter_application_number = isset($_POST['filter_application_number']) ? trim($_POST['filter_application_number']) : '';
$filter_status = isset($_POST['status']) ? $_POST['status'] : [];
$filter_from_date = isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-d', strtotime('-1 month')); // Default: 1 month past date
$filter_to_date = isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d'); // Default: Today's date

// Start building the query
$query = "SELECT *, 
          CASE 
              WHEN EXTRACT(MONTH FROM timestamp) IN (1, 2, 3) THEN 
                  (EXTRACT(YEAR FROM timestamp) - 1) || '-' || EXTRACT(YEAR FROM timestamp)
              ELSE 
                  EXTRACT(YEAR FROM timestamp) || '-' || (EXTRACT(YEAR FROM timestamp) + 1)
          END AS academic_year
          FROM signup";

// Add filters based on user input
$conditions = [];

if (!empty($filter_application_number)) {
    // If application number is provided, ignore date and status filters
    $conditions[] = "application_number = '" . pg_escape_string($con, $filter_application_number) . "'";
} else {
    // Add date and status filters only if application number is not provided
    if (!empty($filter_status)) {
        $statuses = array_map(function ($status) use ($con) {
            return pg_escape_string($con, $status);
        }, $filter_status);
        $conditions[] = "application_status IN ('" . implode("', '", $statuses) . "')";
    }

    // Validate date range and add filter if applicable
    if (!empty($filter_from_date) && !empty($filter_to_date)) {
        $from_date = pg_escape_string($con, $filter_from_date);
        $to_date = pg_escape_string($con, $filter_to_date);

        // Ensure $to_date includes the entire day (23:59:59)
        $to_date = $to_date . ' 23:59:59';

        if ($from_date <= $to_date) { // Ensure valid range
            // Use the timestamp::date to compare only the date part of the timestamp
            $conditions[] = "timestamp::date >= '$from_date' AND timestamp::date <= '$to_date'";
        }
    }
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY timestamp DESC";

// Execute the query
$result = pg_query($con, $query);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

// Fetch the results
$resultArr = pg_fetch_all($result);
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

    <title>Talent Pool</title>

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
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <style>
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
            /* Space between the indicator and text */
        }

        .status-indicator.yellow {
            background-color: #FFBF00;
            /* Yellow color */
        }

        .status-indicator.green {
            background-color: #28a745;
            /* Green color */
        }

        .status-indicator.red {
            background-color: #dc3545;
            /* Red color */
        }

        .send-link {
            color: #888;
            /* Light gray color for the text */
            text-decoration: none;
            /* Remove underline */
            font-weight: normal;
            /* Normal weight for text appearance */
            cursor: pointer;
            /* Pointer cursor to indicate clickable */
            opacity: 0.6;
            /* Slightly faded for inactive state */
            transition: opacity 0.3s;
            /* Smooth transition on hover */
        }

        .send-link:hover {
            color: #555;
            /* Darker gray when hovered */
            opacity: 1;
            /* Full opacity on hover */
        }
    </style>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Talent Pool</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">People Plus</a></li>
                    <li class="breadcrumb-item active">Talent Pool</li>
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
                            <div class="container">
                                <form method="POST" class="filter-form d-flex flex-wrap" style="gap: 10px;">
                                    <div class="form-group">
                                        <input type="text" id="filter_application_number" name="filter_application_number" class="form-control" placeholder="Application Number" value="<?php echo htmlspecialchars($filter_application_number); ?>" style="max-width: 200px;">
                                    </div>

                                    <!-- Date Range Filter -->
                                    <div class="form-group">
                                        <input type="date" name="from_date" id="from_date" class="form-control"
                                            value="<?php echo isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : date('Y-m-d', strtotime('-1 month')); ?>"
                                            required />
                                        <small class="form-text text-muted">Select the starting date for the range.</small>
                                    </div>

                                    <div class="form-group">
                                        <input type="date" name="to_date" id="to_date" class="form-control"
                                            value="<?php echo isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : date('Y-m-d'); ?>"
                                            required />
                                        <small class="form-text text-muted">Select the ending date for the range.</small>
                                    </div>
                                    <div class="form-group">
                                        <select id="status" name="status[]" class="form-select" multiple>
                                            <option value="Application Submitted" <?php echo in_array('Application Submitted', $filter_status ?? []) ? 'selected' : ''; ?>>Application Submitted</option>
                                            <option value="Application Re-Submitted" <?php echo in_array('Application Re-Submitted', $filter_status ?? []) ? 'selected' : ''; ?>>Application Re-Submitted</option>
                                            <option value="Identity verification document submitted" <?php echo in_array('Identity verification document submitted', $filter_status ?? []) ? 'selected' : ''; ?>>Identity verification document submitted</option>
                                            <option value="Photo Verification Completed" <?php echo in_array('Photo Verification Completed', $filter_status ?? []) ? 'selected' : ''; ?>>Photo Verification Completed</option>
                                            <option value="Photo Verification Failed" <?php echo in_array('Photo Verification Failed', $filter_status ?? []) ? 'selected' : ''; ?>>Photo Verification Failed</option>
                                            <option value="Identity Verification Completed" <?php echo in_array('Identity Verification Completed', $filter_status ?? []) ? 'selected' : ''; ?>>Identity Verification Completed</option>
                                            <option value="Identity Verification Failed" <?php echo in_array('Identity Verification Failed', $filter_status ?? []) ? 'selected' : ''; ?>>Identity Verification Failed</option>
                                            <option value="Technical Interview Scheduled" <?php echo in_array('Technical Interview Scheduled', $filter_status ?? []) ? 'selected' : ''; ?>>Technical Interview Scheduled</option>
                                            <option value="Technical Interview Completed" <?php echo in_array('Technical Interview Completed', $filter_status ?? []) ? 'selected' : ''; ?>>Technical Interview Completed</option>
                                            <option value="HR Interview Scheduled" <?php echo in_array('HR Interview Scheduled', $filter_status ?? []) ? 'selected' : ''; ?>>HR Interview Scheduled</option>
                                            <option value="Recommended" <?php echo in_array('Recommended', $filter_status ?? []) ? 'selected' : ''; ?>>Recommended</option>
                                            <option value="Not Recommended" <?php echo in_array('Not Recommended', $filter_status ?? []) ? 'selected' : ''; ?>>Not Recommended</option>
                                            <option value="On Hold" <?php echo in_array('On Hold', $filter_status ?? []) ? 'selected' : ''; ?>>On Hold</option>
                                            <option value="No-Show" <?php echo in_array('No-Show', $filter_status ?? []) ? 'selected' : ''; ?>>No-Show</option>
                                            <option value="Offer Extended" <?php echo in_array('Offer Extended', $filter_status ?? []) ? 'selected' : ''; ?>>Offer Extended</option>
                                            <option value="Offer Not Extended" <?php echo in_array('Offer Not Extended', $filter_status ?? []) ? 'selected' : ''; ?>>Offer Not Extended</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-search"></i>&nbsp;Filter
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th scope="col">Applied on</th>
                                            <th scope="col">Application Number</th>
                                            <th scope="col">Applicant Name</th>
                                            <th scope="col">Association</th>
                                            <th scope="col">Post</th>
                                            <th scope="col">Subject Preference</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Interview Schedule</th>
                                            <th scope="col">Link</th>
                                            <th scope="col"></th>
                                            <th scope="col"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Function to generate the WhatsApp message link
                                        function getWhatsAppLink($array, $custom_message)
                                        {
                                            // Construct the message
                                            $message = "Dear " . $array['applicant_name'] . " (" . $array['application_number'] . "),\n\n"
                                                . $custom_message . "\n\n"
                                                . "--RSSI\n\n"
                                                . "**This is a system generated message.";

                                            // Encode the message to make it URL-safe
                                            $encoded_message = urlencode($message);

                                            // Generate and return the WhatsApp URL
                                            return "https://api.whatsapp.com/send?phone=91" . $array['telephone'] . "&text=" . $encoded_message;
                                        }
                                        // Iterate through the fetched candidate information
                                        foreach ($resultArr as $array) {
                                            $interviewTimestamp = empty($array['tech_interview_schedule']) ? '' : @date("d/m/Y g:i a", strtotime($array['tech_interview_schedule']));
                                            $hrTimestamp = empty($array['hr_interview_schedule']) ? '' : @date("d/m/Y g:i a", strtotime($array['hr_interview_schedule']));
                                            $linkToShow = '';

                                            $interviewStatus = empty($array['application_status']) ? '' : $array['application_status'];

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
                                                . "Reporting Date & Time: " . (!empty($array['tech_interview_schedule']) && $array['tech_interview_schedule'] !== null
                                                    ? (new DateTime($array['tech_interview_schedule']))->format('d/m/Y h:i a')
                                                    : 'No interview scheduled') . "\n"
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
                                            $message9 = "Just a quick reminder that your interview for the Faculty position at RSSI NGO is scheduled for today " . (!empty($array['tech_interview_schedule']) && $array['tech_interview_schedule'] !== null
                                                ? (new DateTime($array['tech_interview_schedule']))->format('d/m/Y h:i a')
                                                : 'No interview scheduled') . ". Please ensure you arrive on time at the interview location.\n\n"
                                                . "Please ensure you have the following items with you for the interview:\n"
                                                . "• Your Gmail ID and password\n"
                                                . "• Proof of identity\n"
                                                . "• Educational certificates\n"
                                                . "• Professional certifications (if any)\n"
                                                . "• Any other relevant paperwork\n\n"
                                                . "Please note that the interview process typically takes approximately 2.5 hours. However, in certain unforeseen circumstances, it may take longer.\n\n"
                                                . "We look forward to meeting you.";

                                            // Generate WhatsApp links
                                            $link1 = getWhatsAppLink($array, $message1);
                                            $link2 = getWhatsAppLink($array, $message2);
                                            $link3 = getWhatsAppLink($array, $message3);
                                            $link4 = getWhatsAppLink($array, $message4);
                                            $link5 = getWhatsAppLink($array, $message5);
                                            $link6 = getWhatsAppLink($array, $message6);
                                            $link7 = getWhatsAppLink($array, $message7);
                                            $link8 = getWhatsAppLink($array, $message8);
                                            $link9 = getWhatsAppLink($array, $message9);
                                        ?>
                                            <tr>
                                                <td><?php
                                                    // Example: check the application_status and display the yellow indicator for specific statuses
                                                    $status = $array['application_status']; // Get the application status

                                                    if ($status == 'Identity verification document submitted' or $status == 'Technical Interview Completed' or $status == 'Application Re-Submitted' or $status == 'Recommended' or $status == 'On Hold') { // Or any other status you want to check
                                                        echo '<span class="status-indicator yellow"></span>';
                                                    } elseif ($status == 'Offer Extended') {
                                                        echo '<span class="status-indicator green"></span>';
                                                    } elseif ($status == 'Offer Not Extended') {
                                                        echo '<span class="status-indicator red"></span>';
                                                    }
                                                    ?>
                                                    <?php echo !empty($array['timestamp']) ? @date("d/m/Y g:i a", strtotime($array['timestamp'])) : ''; ?></td>
                                                <td><?php echo $array['application_number']; ?></td>
                                                <td><?php echo $array['applicant_name']; ?></td>
                                                <td><?php echo $array['association']; ?></td>
                                                <td><?php echo $array['post_select']; ?></td>
                                                <td><?php echo $array['subject1']; ?>,<?php echo $array['subject2']; ?>,<?php echo $array['subject3']; ?></td>
                                                <td><?php echo $interviewStatus; ?></td>
                                                <td>
                                                    <?php
                                                    // Format interview timestamps
                                                    $interviewTimestamp = empty($array['tech_interview_schedule']) ? '' : @date("d/m/Y g:i a", strtotime($array['tech_interview_schedule']));
                                                    $hrTimestamp = empty($array['hr_interview_schedule']) ? '' : @date("d/m/Y g:i a", strtotime($array['hr_interview_schedule']));

                                                    // Check application status for technical interview completion
                                                    if (!empty($interviewTimestamp) && empty($hrTimestamp)) {
                                                        // Assuming the 'application_status' column indicates whether the technical interview is completed
                                                        if ($array['application_status'] == 'Technical Interview Scheduled' or $array['application_status'] == 'Technical Interview Completed') {
                                                            echo $interviewTimestamp . '<br>';
                                                        }
                                                    }

                                                    // Display HR Interview timestamp if scheduled or recommended
                                                    if (!empty($hrTimestamp)) {
                                                        if ($array['application_status'] == 'HR Interview Scheduled' || $array['application_status'] == 'Recommended') {
                                                            echo $hrTimestamp . '<br>';
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $linkToShow = '';
                                                    // Check if HR interview is scheduled
                                                    if (!empty($array['hr_interview_schedule'])) {
                                                        $linkToShow = '<a href="hr_interview.php?applicationNumber_verify=' . $array['application_number'] . '" target="_blank">HR Interview</a>';
                                                    }
                                                    // Check if TR interview is scheduled and HR interview is not scheduled
                                                    elseif (!empty($array['tech_interview_schedule']) && $array['application_status'] != 'No-Show') {
                                                        $linkToShow = '<a href="technical_interview.php?applicationNumber_verify=' . $array['application_number'] . '" target="_blank">Technical Interview</a>';
                                                    }

                                                    if (!empty($linkToShow)) {
                                                        echo $linkToShow;
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    // Define the conditions and corresponding titles and links
                                                    $links = [
                                                        "Photo Verification Failed" => ["link" => $link2, "title" => "Photo Rejected"],
                                                        "Reminder" => ["link" => $link1, "title" => "Reminder to complete identity verification"],
                                                        "Identity Verification Failed" => ["link" => $link3, "title" => "Verification Rejected"],
                                                        "Identity Verification Completed" => ["link" => $link4, "title" => "Verification Approved"],
                                                        "Technical Interview Scheduled" => ["link" => $link5, "title" => "Interview Scheduled"],
                                                        "Technical Interview Completed" => ["link" => $link8, "title" => "Interview Feedback"],
                                                        "HR Interview Scheduled" => ["link" => $link6, "title" => "HR Interview Scheduled"],
                                                        "Interview Reminder" => ["link" => $link9, "title" => "Interview Reminder"],
                                                        "Offer Extended" => ["link" => $link7, "title" => "Offer Extended"]
                                                    ];

                                                    // Get today's date for comparison
                                                    $today = date('Y-m-d');

                                                    // Variable to track if space should be added before the next "Send" link
                                                    $previousSendDisplayed = false;

                                                    // Check conditions and display the respective text links
                                                    foreach ($links as $status => $data) {
                                                        // Check if the "Reminder" link should be displayed
                                                        if ($status == "Reminder" && (empty($array['supporting_document']) || ($array['identity_verification'] == 'Rejected' && $array['application_status'] != 'Identity verification document submitted'))) {
                                                            // Add space if the previous link was "Send"
                                                            if ($previousSendDisplayed) {
                                                                echo ' '; // Add a space between links
                                                            }
                                                            // Display reminder link with "Send" text
                                                            echo '<a href="' . $data['link'] . '" target="_blank" title="' . $data['title'] . '" class="send-link">Send</a>';
                                                            $previousSendDisplayed = true;
                                                        }
                                                        // Check if the "Interview Reminder" link should be displayed
                                                        elseif ($status == "Interview Reminder" && !empty($array['tech_interview_schedule']) && date('Y-m-d', strtotime($array['tech_interview_schedule'])) == $today && empty($array['no_show'])) {
                                                            // Add space if the previous link was "Send"
                                                            if ($previousSendDisplayed) {
                                                                echo ' '; // Add a space between links
                                                            }
                                                            // If the interview is scheduled for today, show the reminder message
                                                            echo '<a href="' . $data['link'] . '" target="_blank" title="' . $data['title'] . '" class="send-link">Send</a>';
                                                            $previousSendDisplayed = true;
                                                        }
                                                        // Check for other application status
                                                        elseif ($array['application_status'] == $status && $status != "Interview Reminder") {
                                                            // Add space if the previous link was "Send"
                                                            if ($previousSendDisplayed) {
                                                                echo ' '; // Add a space between links
                                                            }
                                                            // Display other links except "Interview Reminder"
                                                            echo '<a href="' . $data['link'] . '" target="_blank" title="' . $data['title'] . '" class="send-link">Send</a>';
                                                            $previousSendDisplayed = true;
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="applicant_profile.php?application_number=<?php echo $array['application_number']; ?>" target="_blank" style="text-decoration: none;">
                                                        <i class="bi bi-box-arrow-up-right" style="color: gray;" title="Applicant Profile"></i> <!-- Gray icon without link effect -->
                                                    </a>
                                                </td>

                                            </tr>
                                        <?php
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div><!-- End Reports -->
                </div>
            </div>
        </section>
    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
    <script>
        // Get today's date in 'YYYY-MM-DD' format
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const maxDate = `${yyyy}-${mm}-${dd}`;

        // Set max attribute for both date inputs
        const fromDateInput = document.getElementById('from_date');
        const toDateInput = document.getElementById('to_date');

        fromDateInput.max = maxDate; // Disable future dates for From Date
        toDateInput.max = maxDate; // Disable future dates for To Date

        // Function to disable dates before the selected from_date
        function updateToDateMin() {
            const fromDate = new Date(fromDateInput.value);
            if (fromDateInput.value) {
                toDateInput.min = fromDateInput.value; // Disable dates before the selected from_date
            } else {
                toDateInput.min = '2020-01-01'; // Fallback for empty from_date
            }
        }

        // Function to disable dates before the selected to_date
        function updateFromDateMax() {
            const toDate = new Date(toDateInput.value);
            if (toDateInput.value) {
                fromDateInput.max = toDateInput.value; // Disable dates after the selected to_date
            } else {
                fromDateInput.max = maxDate; // Reset to maxDate if to_date is empty
            }
        }

        // Event listener to ensure to_date is always greater than or equal to from_date
        toDateInput.addEventListener('change', function() {
            if (toDateInput.value < fromDateInput.value) {
                alert('To Date cannot be before From Date');
                toDateInput.value = fromDateInput.value;
            }
            updateFromDateMax(); // Update the max for from_date based on the new to_date
        });

        // Event listener to ensure from_date is always less than or equal to to_date
        fromDateInput.addEventListener('change', function() {
            if (fromDateInput.value > toDateInput.value) {
                alert('From Date cannot be after To Date');
                fromDateInput.value = toDateInput.value;
            }
            updateToDateMin(); // Update the min for to_date based on the new from_date
        });

        // Call the update functions initially in case there is a pre-selected date range
        updateToDateMin();
        updateFromDateMax();
    </script>
</body>

</html>