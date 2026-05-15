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

// Handle the video upload to Google Drive (EXACT same pattern as onboarding photo)
$upload_status = '';
$uploaded_file_link = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'upload_interview_video') {

    $app_num = isset($_POST['application_number']) ? $_POST['application_number'] : 'NA';
    $video_base64 = isset($_POST['video_base64']) ? $_POST['video_base64'] : '';

    if (!empty($video_base64) && $video_base64 != 'data:,') {

        // Step 1: First insert into database to reserve the record
        $now = date('Y-m-d H:i:s');

        // Insert placeholder record with NULL drive_file_link first
        $insert_query = "INSERT INTO vrc (application_number, drive_file_link, timestamp) 
                         VALUES ($1, NULL, $2)
                         RETURNING id";
        $insert_result = pg_query_params($con, $insert_query, array($app_num, $now));

        if (!$insert_result) {
            $upload_status = 'error';
            $error_message = 'Database initialization failed: ' . pg_last_error($con);
        } else {
            $row = pg_fetch_assoc($insert_result);
            $inserted_id = $row['id'];

            // Step 2: Now process and upload the video to Google Drive
            // Convert base64 to file - same as onboarding
            $base64_string = $video_base64;

            // Remove the data:video/webm;base64, prefix (same as onboarding)
            if (strpos($base64_string, ',') !== false) {
                list(, $base64_string) = explode(',', $base64_string);
            }

            // Decode base64 string
            $video_data = base64_decode($base64_string);

            // Create a temporary file
            $temp_file = tempnam(sys_get_temp_dir(), 'interview_video_');
            file_put_contents($temp_file, $video_data);

            // Create file object for Google Drive upload (EXACT same as onboarding)
            $file_array = [
                'name' => 'interview_video_' . $app_num . '_' . time() . '.webm',
                'type' => 'video/webm',
                'tmp_name' => $temp_file,
                'error' => 0,
                'size' => filesize($temp_file)
            ];

            // Upload to Google Drive - uploadeToDrive returns the FILE ID directly (string)
            $filename = "interview_video_" . $app_num . "_" . time();
            $parent_folder_id = '1f7c9h0_k7_Biatgh4XrAhas8wxXuWW3V'; // Your folder ID
            $drive_response = uploadeToDrive($file_array, $parent_folder_id, $filename);

            // Clean up temporary file
            unlink($temp_file);

            // Step 3: Check if upload was successful
            if ($drive_response && is_string($drive_response)) {
                // Update the existing record with the Drive file link
                $update_query = "UPDATE vrc SET drive_file_link = $1 WHERE id = $2";
                $update_result = pg_query_params($con, $update_query, array($drive_response, $inserted_id));

                if ($update_result) {
                    $upload_status = 'success';
                    $uploaded_file_link = $drive_response;

                    // Fetch user details from signup table using application_number
                    $user_query = "SELECT applicant_name, email FROM signup WHERE application_number = $1";
                    $user_result = pg_query_params($con, $user_query, array($app_num));

                    $applicant_name = '';
                    $applicant_email = '';

                    if ($user_result && pg_num_rows($user_result) > 0) {
                        $user_data = pg_fetch_assoc($user_result);
                        $applicant_name = $user_data['applicant_name'];
                        $applicant_email = $user_data['email'];
                    }

                    // Send email notification to the applicant
                    if (!empty($applicant_email)) {
                        $email_sent = sendEmail("interview_video_submission", array(
                            "reference_number" => $inserted_id,
                            "application_number" => $app_num,
                            "applicant_name" => $applicant_name,
                            "drive_file_link" => $drive_response,
                            "submission_date" => date("d/m/Y g:i a", strtotime($now))
                        ), $applicant_email);

                        // Optional: Log if email sending fails (for debugging)
                        if (!$email_sent) {
                            error_log("Failed to send interview video submission email to: " . $applicant_email);
                        }
                    }
                } else {
                    // Drive upload succeeded but database update failed
                    // Note: The file is already uploaded to Drive, but we couldn't update the record
                    $upload_status = 'error';
                    $error_message = 'Database update failed after Drive upload. Please contact support. Record ID: ' . $inserted_id;
                    // Log this critical error for manual review
                    error_log("CRITICAL: Drive file uploaded but database update failed. Record ID: $inserted_id, Drive File ID: $drive_response");
                }
            } else {
                // Drive upload failed - delete the placeholder database record to avoid empty records
                $delete_query = "DELETE FROM vrc WHERE id = $1";
                $delete_result = pg_query_params($con, $delete_query, array($inserted_id));

                $upload_status = 'error';
                $error_message = 'Drive upload failed - no file ID returned. Record has been removed.';
            }
        }
    } else {
        $upload_status = 'error';
        $error_message = 'No video data received';
    }
}

// Check if user has already submitted a video
$has_submitted_video = false;
$video_status = null;
$can_proceed = true;
$online_interview_initiated = false;
$message = '';

// Get application_number from session or wherever you store it
// Assuming you have $application_number from your existing source
if (isset($application_number) && !empty($application_number)) {
    $check_query = "SELECT 
    vrc.drive_file_link,
    vrc.status,
    vrc.timestamp,
    signup.online_interview_initiated
FROM signup
LEFT JOIN vrc 
    ON signup.application_number = vrc.application_number
    WHERE signup.application_number = $1 ORDER BY timestamp DESC LIMIT 1";
    $check_result = pg_query_params($con, $check_query, array($application_number));

    if ($check_result && pg_num_rows($check_result) > 0) {
        $existing_record = pg_fetch_assoc($check_result);
        $has_submitted_video = true;
        $video_status = $existing_record['status'];
        $submission_time = $existing_record['timestamp'];
        $online_interview_initiated = $existing_record['online_interview_initiated'];

        // Check if status is 'rejected' - allow to proceed
        if ($video_status === 'rejected' || $video_status === null) {
            $can_proceed = true;
        } else {
            // Status is pending, approved, or any other status - don't allow new submission
            $can_proceed = false;
        }

        // Check if status is 'true' - allow to proceed
        if ($online_interview_initiated === 't' && $online_interview_initiated !== null) {
            $online_interview_initiated = true;
        } else {
            // Status is pending, approved, or any other status - don't allow new submission
            $online_interview_initiated = false;
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
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
    <!-- <?php include 'includes/meta.php'; ?> -->
    <title>Interview Video Recorder (IVR)</title>

    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <style>
        /* Your existing styles remain the same */
        .interview-layout {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .questions-panel {
            flex: 1;
            min-width: 280px;
        }

        .video-panel {
            flex: 1;
            min-width: 380px;
        }

        .video-tv-frame {
            background: linear-gradient(145deg, #2c2c2c, #1a1a1a);
            border-radius: 30px;
            padding: 20px 20px 25px 20px;
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.3);
            width: 100%;
        }

        .video-screen {
            background: #000;
            border-radius: 16px;
            overflow: hidden;
            aspect-ratio: 16 / 9;
            position: relative;
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.5);
        }

        .video-stream {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #111;
        }

        .tv-stand {
            width: 80px;
            height: 8px;
            background: #555;
            margin: 12px auto 0;
            border-radius: 4px;
        }

        .timer-badge {
            background: #dc3545;
            color: white;
            padding: 5px 18px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 1rem;
            font-family: monospace;
        }

        .recording-btn-group .btn {
            border-radius: 40px;
            padding: 6px 18px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .camera-toggle-switch {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #eef2f9;
            border-radius: 40px;
            padding: 4px 15px;
        }

        .toggle-switch {
            appearance: none;
            width: 44px;
            height: 22px;
            background: #ccc;
            border-radius: 30px;
            position: relative;
            cursor: pointer;
            transition: 0.2s;
        }

        .toggle-switch:checked {
            background: #198754;
        }

        .toggle-switch::before {
            content: "";
            position: absolute;
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 3px;
            transition: 0.2s;
        }

        .toggle-switch:checked::before {
            transform: translateX(21px);
        }

        .submit-drive-btn {
            background: linear-gradient(135deg, #0b5e2e, #1e8a3e);
            border: none;
            padding: 12px 28px;
            font-weight: 600;
            border-radius: 50px;
            width: 100%;
        }

        .interview-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.2rem 1.5rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
        }

        .app-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 18px;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .interview-layout {
                flex-direction: column;
            }
        }

        .questions-list-modern {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
            counter-reset: q-counter;
        }

        .questions-list-modern li {
            counter-increment: q-counter;
            margin-bottom: 1.25rem;
            background: #ffffff;
            border-radius: 14px;
            padding: 0.9rem 1rem;
            border: 1px solid #edf2f7;
            transition: all 0.2s ease;
        }

        .questions-list-modern li:hover {
            border-color: #cbd5e1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }

        .question-number {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            background-color: #eef2ff;
            color: #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .question-number::before {
            content: counter(q-counter);
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Interview Video Recorder (IVR)</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Interview Video Recorder (IVR)</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>

                            <?php if ($upload_status == 'success'): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <strong>Success!</strong> Your interview video has been submitted successfully!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <script>
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                </script>
                            <?php elseif ($upload_status == 'error'): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <strong>Error!</strong> <?php echo htmlspecialchars($error_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            <?php if ($online_interview_initiated): ?>
                                <div class="interview-header">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                                        <div>
                                            <p class="mb-0">Please answer all questions in sequence within 1 minute.</p>
                                        </div>
                                        <div class="mt-2 mt-sm-0">
                                            <span class="app-badge">
                                                <i class="bi bi-hash"></i> Application Number:
                                                <strong><?php echo htmlspecialchars($application_number); ?></strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rest of your HTML remains the same -->
                                <div class="interview-layout">
                                    <!-- LEFT PANEL: Questions -->
                                    <div class="questions-panel" style="max-height: 560px; overflow-y: auto; padding-right: 6px;">
                                        <!-- Header -->
                                        <div class="d-flex align-items-center gap-2 mb-4 pb-1 border-bottom border-2" style="border-color: #e9ecef !important;">
                                            <div class="rounded-circle bg-primary bg-opacity-10 p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                <i class="bi bi-question-circle-fill text-primary" style="font-size: 1.3rem;"></i>
                                            </div>
                                            <h5 class="fw-semibold mb-0" style="letter-spacing: -0.2px; color: #1e2a3e;">Questions to Answer</h5>
                                            <span class="ms-auto badge bg-light text-dark rounded-pill fw-normal">8 questions</span>
                                        </div>

                                        <!-- Instructions Box -->
                                        <div class="mb-4 p-3 rounded-4" style="background: linear-gradient(135deg, #f8faff 0%, #f0f4fe 100%); border: 1px solid rgba(59, 130, 246, 0.15); border-radius: 16px;">
                                            <div class="d-flex gap-2 align-items-start">
                                                <i class="bi bi-info-circle-fill text-primary mt-1" style="font-size: 1.1rem;"></i>
                                                <div>
                                                    <span class="fw-semibold" style="color: #1e40af;">Before you begin</span>
                                                    <ul class="mt-2 mb-0 ps-3" style="font-size: 0.82rem; color: #2c3e50; line-height: 1.5;">
                                                        <li>Answer all 8 questions in sequence within <strong>1 minute</strong></li>
                                                        <li>Ensure a quiet, well-lit environment - speak clearly and look at the camera</li>
                                                        <li>You can retake the video before submitting</li>
                                                        <li>Your video will be stored securely in Google Drive after submission</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <ul class="questions-list-modern">
                                            <!-- Your existing questions list -->
                                            <li>
                                                <div class="d-flex gap-3">
                                                    <div class="question-number"></div>
                                                    <div>
                                                        <strong style="color: #0f172a; font-weight: 600;">Introduce yourself.</strong>
                                                        <div class="mt-2 pt-2 border-top" style="font-size: 0.7rem; color: #6c757d;">
                                                            Show the front and back of your Aadhar Card, holding each side in the frame for 5 seconds. Ensure both your face and the document are clearly visible in the frame.
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="d-flex gap-3">
                                                    <div class="question-number"></div>
                                                    <div>
                                                        <strong style="color: #0f172a; font-weight: 600;">What do you know about RSSI NGO and its key projects?</strong>
                                                    </div>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="d-flex gap-3">
                                                    <div class="question-number"></div>
                                                    <div>
                                                        <strong style="color: #0f172a; font-weight: 600;">Preferred shift: Pre-primary (11am–3pm) / Primary (2:30pm–6:30pm)?</strong>
                                                        <div class="mt-2 pt-2 border-top" style="font-size: 0.7rem; color: #6c757d;">
                                                            Final allocation is subject to business requirements and operational feasibility.
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="d-flex gap-3">
                                                    <div class="question-number"></div>
                                                    <div>
                                                        <strong style="color: #0f172a; font-weight: 600;">Are you aware that the internship requires a minimum commitment of 1 month, with a schedule of 4 days/week, 4 hours/day? Will you be able to manage this?</strong>
                                                        <div class="mt-2 pt-2 border-top" style="font-size: 0.7rem; color: #6c757d;">
                                                            If you are a student of UPES, the minimum internship duration is 2 months, with a commitment of 4 days per week and 4 hours per day.
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="d-flex gap-3">
                                                    <div class="question-number"></div>
                                                    <div>
                                                        <strong style="color: #0f172a; font-weight: 600;">What is your expected joining date?</strong>
                                                    </div>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="d-flex gap-3">
                                                    <div class="question-number"></div>
                                                    <div>
                                                        <strong style="color: #0f172a; font-weight: 600;">Would you be able to relocate to Lucknow for the 1-month duration of the internship?</strong>
                                                        <div class="mt-2 pt-2 border-top" style="font-size: 0.7rem; color: #6c757d;">
                                                            There is no provision for remote work or hybrid arrangements for this internship. Candidates must be able to work on-site in Lucknow for the entire duration of the internship.
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="d-flex gap-3">
                                                    <div class="question-number"></div>
                                                    <div>
                                                        <strong style="color: #0f172a; font-weight: 600;">Which language(s) are you comfortable with? Are you comfortable teaching in Hindi as the primary medium of instruction?</strong>
                                                    </div>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="d-flex gap-3">
                                                    <div class="question-number"></div>
                                                    <div>
                                                        <strong style="color: #0f172a; font-weight: 600;">Are you comfortable visiting students' homes and interacting with their parents to understand their lifestyle and challenges for your case study?</strong>
                                                        <div class="mt-2 pt-2 border-top" style="font-size: 0.7rem; color: #6c757d;">
                                                            This internship involves fieldwork that requires visiting students' homes and engaging with their parents to gain insights into their lifestyle and challenges. Candidates must be comfortable with this aspect of the role.
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- RIGHT PANEL: Video Recording -->
                                    <div class="video-panel">
                                        <h5 class="mb-3"><i class="bi bi-camera-video-fill text-primary"></i> Record Your Response</h5>

                                        <?php if (!$can_proceed): ?>
                                            <!-- Show message when video already submitted and not rejected -->
                                            <div class="alert alert-secondary text-center p-5" style="border-radius: 20px;">
                                                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                                <h4 class="mt-3">Submission Complete</h4>
                                                <p>Your interview video has already been submitted on <?php echo htmlspecialchars($submission_time); ?>.</p>
                                                <p class="mb-0 text-muted">Thank you for your participation!</p>
                                            </div>
                                        <?php else: ?>
                                            <!-- Show video recording interface -->

                                            <form method="POST" id="interviewForm">
                                                <input type="hidden" name="action" value="upload_interview_video">
                                                <input type="hidden" name="application_number" value="<?php echo htmlspecialchars($application_number); ?>">
                                                <input type="hidden" name="video_base64" id="videoBase64Data" value="">

                                                <div class="video-tv-frame">
                                                    <div class="video-screen">
                                                        <video id="liveVideo" class="video-stream" autoplay muted playsinline style="display: none;"></video>
                                                        <video id="previewVideo" class="video-stream" controls style="display: none;"></video>
                                                    </div>
                                                    <div class="tv-stand"></div>

                                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                                                        <div class="camera-toggle-switch">
                                                            <input type="checkbox" class="toggle-switch" id="cameraToggle">
                                                            <label class="toggle-label text-dark mb-0 fw-semibold" for="cameraToggle" style="font-size:0.8rem;">Camera On/Off</label>
                                                        </div>
                                                        <div id="timerDisplay" class="timer-badge"><i class="bi bi-stopwatch"></i> 01:00</div>
                                                    </div>

                                                    <div class="recording-btn-group d-flex flex-wrap gap-2 justify-content-center mt-3">
                                                        <button type="button" class="btn btn-success" id="startRecordBtn" disabled><i class="bi bi-record-circle"></i> Record</button>
                                                        <button type="button" class="btn btn-danger" id="stopRecordBtn" disabled><i class="bi bi-stop-circle"></i> Stop</button>
                                                        <button type="button" class="btn btn-outline-secondary" id="retakeBtn" hidden><i class="bi bi-arrow-repeat"></i> Retake</button>
                                                    </div>

                                                    <div class="text-center mt-2">
                                                        <span id="videoStatusText" class="badge bg-light text-dark p-2"><i class="bi bi-info-circle"></i> Turn on camera to start</span>
                                                    </div>
                                                </div>

                                                <div class="mt-4">
                                                    <button type="submit" id="uploadToDriveBtn" class="btn submit-drive-btn text-white" disabled>
                                                        <i class="bi bi-cloud-upload-fill"></i> Upload Interview Video
                                                    </button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!$online_interview_initiated): ?>
                                <div class="alert alert-info alert-dismissible fade show mt-4" role="alert" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
                                    <div class="d-flex gap-3">
                                        <div class="flex-shrink-0">
                                            <i class="bi bi-info-circle-fill" style="font-size: 2rem; color: #0dcaf0;"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="alert-heading mb-2" style="color: #055160;">
                                                <i class="bi bi-camera-video-off me-2"></i>Online Interview Not Initiated
                                            </h5>

                                            <p class="mb-3">Your online interview process has not been initiated yet.</p>

                                            <div class="mt-3 p-3 rounded" style="background: rgba(25, 135, 84, 0.05); border: 1px solid rgba(25, 135, 84, 0.2);">
                                                <h6 class="mb-2" style="color: #198754;">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Who is eligible for online interview?
                                                </h6>
                                                <p class="mb-0">Online interviews are available <strong>only for Internship/Volunteer positions</strong> if you meet <strong>ALL</strong> of these conditions:</p>
                                                <ul class="mt-2 mb-0">
                                                    <li>Currently enrolled in a course/diploma/degree program</li>
                                                    <li>Institution/college/university is located outside the preferred branch location</li>
                                                    <li>Candidate is residing outside the preferred branch location (outstation candidate)</li>
                                                </ul>
                                            </div>

                                            <div class="mt-3 p-3 rounded" style="background: rgba(13, 202, 240, 0.1); border: 1px solid rgba(13, 202, 240, 0.2);">
                                                <h6 class="mb-2" style="color: #055160;">
                                                    How to request online interview:
                                                </h6>
                                                <p class="mb-1">Send an email to info@rssi.in with the following details/documents:</p>
                                                <ul class="mb-0">
                                                    <li>Your application number</li>
                                                    <li>College letter OR valid college ID card as proof that your institution is outside the preferred branch location</li>
                                                </ul>
                                            </div>

                                            <div class="mt-3 alert alert-light mb-0 p-2" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.2);">
                                                <strong>Next Step:</strong> Once initiated, you'll receive a confirmation email and the video recording option will appear here.
                                            </div>

                                            <div class="mt-3">
                                                <small class="text-danger">
                                                    <strong>Important Note:</strong> Candidates residing in the preferred branch location or studying at an institution located in the preferred branch location are not eligible for an online interview. Such candidates are required to appear for an in-person interview at the respective office/branch.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
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
        document.getElementById('interviewForm').addEventListener('submit', function(event) {
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
        const liveVideo = document.getElementById('liveVideo');
        const previewVideo = document.getElementById('previewVideo');
        const startBtn = document.getElementById('startRecordBtn');
        const stopBtn = document.getElementById('stopRecordBtn');
        const retakeBtn = document.getElementById('retakeBtn');
        const cameraToggle = document.getElementById('cameraToggle');
        const uploadBtn = document.getElementById('uploadToDriveBtn');
        const timerDisplay = document.getElementById('timerDisplay');
        const videoStatusSpan = document.getElementById('videoStatusText');
        const videoBase64Input = document.getElementById('videoBase64Data');
        const interviewForm = document.getElementById('interviewForm');

        let mediaStream = null;
        let mediaRecorder = null;
        let recordedChunks = [];
        let recordingInterval = null;
        let recordingSeconds = 0;
        const MAX_SEC = 60;
        let isRecording = false;
        let recordedBlob = null;
        let recordedBase64 = null;

        function formatTime(sec) {
            let mins = Math.floor(sec / 60);
            let remain = sec % 60;
            return `${mins.toString().padStart(2,'0')}:${remain.toString().padStart(2,'0')}`;
        }

        function updateTimerUI() {
            timerDisplay.innerHTML = `<i class="bi bi-stopwatch"></i> ${formatTime(recordingSeconds)}`;
            if (recordingSeconds >= MAX_SEC && isRecording && mediaRecorder && mediaRecorder.state === 'recording') {
                stopRecording();
            }
        }

        function startTimer() {
            if (recordingInterval) clearInterval(recordingInterval);
            recordingSeconds = 0;
            updateTimerUI();
            recordingInterval = setInterval(() => {
                if (isRecording && recordingSeconds < MAX_SEC) {
                    recordingSeconds++;
                    updateTimerUI();
                    if (recordingSeconds >= MAX_SEC && mediaRecorder && mediaRecorder.state === 'recording') {
                        stopRecording();
                        videoStatusSpan.innerHTML = '<i class="bi bi-clock-history"></i> Time limit reached (60s)';
                    }
                }
            }, 1000);
        }

        function stopTimer() {
            if (recordingInterval) {
                clearInterval(recordingInterval);
                recordingInterval = null;
            }
        }

        async function startCamera() {
            if (mediaStream) return;
            try {
                const constraints = {
                    video: true,
                    audio: true
                };
                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                mediaStream = stream;
                liveVideo.srcObject = stream;
                liveVideo.style.display = 'block';
                previewVideo.style.display = 'none';
                await liveVideo.play();
                startBtn.disabled = false;
                videoStatusSpan.innerHTML = '<i class="bi bi-camera-video-fill"></i> Camera ready - Press Record';
            } catch (err) {
                console.error(err);
                videoStatusSpan.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Camera/Mic access denied';
                startBtn.disabled = true;
                cameraToggle.checked = false;
            }
        }

        function stopCamera() {
            if (mediaStream) {
                mediaStream.getTracks().forEach(track => track.stop());
                mediaStream = null;
                liveVideo.srcObject = null;
            }
            liveVideo.style.display = 'none';
            startBtn.disabled = true;
            stopBtn.disabled = true;
            videoStatusSpan.innerHTML = '<i class="bi bi-camera-off"></i> Camera is off';
        }

        function startRecording() {
            if (!mediaStream) {
                alert('Please turn on camera first');
                return;
            }
            if (recordedBase64) {
                if (!confirm('Recording a new video will replace the previous one. Continue?')) return;
                retakeRecording();
            }
            recordedChunks = [];
            try {
                mediaRecorder = new MediaRecorder(mediaStream, {
                    mimeType: 'video/webm'
                });
                mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) recordedChunks.push(e.data);
                };
                mediaRecorder.onstop = () => {
                    if (recordedChunks.length === 0) return;
                    const blob = new Blob(recordedChunks, {
                        type: 'video/webm'
                    });
                    if (blob.size < 5000) {
                        alert('Recording too short. Please record a longer response.');
                        return;
                    }
                    recordedBlob = blob;
                    const vidUrl = URL.createObjectURL(blob);
                    liveVideo.style.display = 'none';
                    previewVideo.style.display = 'block';
                    previewVideo.src = vidUrl;
                    previewVideo.controls = true;

                    const reader = new FileReader();
                    reader.onloadend = () => {
                        recordedBase64 = reader.result;
                        videoBase64Input.value = recordedBase64;
                        uploadBtn.disabled = false;
                        videoStatusSpan.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Video recorded! Ready to upload.';
                    };
                    reader.readAsDataURL(blob);

                    retakeBtn.hidden = false;
                    startBtn.disabled = true;
                    stopBtn.disabled = true;
                    isRecording = false;
                    stopTimer();

                    stopCamera();
                    cameraToggle.checked = false;
                };
                mediaRecorder.start(100);
                isRecording = true;
                startTimer();
                startBtn.disabled = true;
                stopBtn.disabled = false;
                videoStatusSpan.innerHTML = '<i class="bi bi-record-circle text-danger"></i> Recording... max 60s';
            } catch (e) {
                alert('Recording error: ' + e.message);
            }
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                isRecording = false;
                stopTimer();
            }
        }

        function retakeRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') mediaRecorder.stop();
            isRecording = false;
            stopTimer();
            recordedChunks = [];
            recordedBlob = null;
            recordedBase64 = null;
            videoBase64Input.value = '';
            uploadBtn.disabled = true;
            liveVideo.style.display = 'block';
            previewVideo.style.display = 'none';
            if (previewVideo.src) {
                URL.revokeObjectURL(previewVideo.src);
                previewVideo.src = '';
            }
            startBtn.disabled = false;
            stopBtn.disabled = true;
            retakeBtn.hidden = true;
            videoStatusSpan.innerHTML = '<i class="bi bi-camera-reels"></i> Ready to record again';
            recordingSeconds = 0;
            updateTimerUI();

            if (!mediaStream && cameraToggle.checked) {
                startCamera();
            } else if (mediaStream) {
                liveVideo.srcObject = mediaStream;
            }
        }

        startBtn.addEventListener('click', startRecording);
        stopBtn.addEventListener('click', stopRecording);
        retakeBtn.addEventListener('click', retakeRecording);

        cameraToggle.addEventListener('change', async (e) => {
            if (e.target.checked) {
                await startCamera();
            } else {
                if (mediaRecorder && mediaRecorder.state === 'recording') {
                    stopRecording();
                }
                stopCamera();
                videoStatusSpan.innerHTML = '<i class="bi bi-camera-off"></i> Camera is off';
            }
        });

        interviewForm.addEventListener('submit', function(e) {
            if (!recordedBase64) {
                e.preventDefault();
                alert('Please record your video response first');
                return;
            }
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Uploading...';
        });

        window.addEventListener('load', () => {
            cameraToggle.checked = false;
            uploadBtn.disabled = true;
            videoStatusSpan.innerHTML = '<i class="bi bi-info-circle"></i> Turn on camera to start';
        });
    </script>
</body>

</html>