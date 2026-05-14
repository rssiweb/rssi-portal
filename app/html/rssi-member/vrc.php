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

// Get application_number from your existing source
$application_number = isset($_GET['application_number']) ? $_GET['application_number'] : '12345';

// Handle the video upload to Google Drive (EXACT same pattern as onboarding photo)
$upload_status = '';
$uploaded_file_link = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'upload_interview_video') {

    $app_num = isset($_POST['application_number']) ? $_POST['application_number'] : 'NA';
    $video_base64 = isset($_POST['video_base64']) ? $_POST['video_base64'] : '';

    if (!empty($video_base64) && $video_base64 != 'data:,') {
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

        // Check if upload was successful - uploadeToDrive returns file ID string or false
        if ($drive_response && is_string($drive_response)) {
            $upload_status = 'success';
            $uploaded_file_link = $drive_response;

            // Store in vrc table
            $now = date('Y-m-d H:i:s');

            $query = "INSERT INTO vrc (application_number, drive_file_link, timestamp) 
                      VALUES ($1, $2, $3)";
            $result = pg_query_params($con, $query, array($app_num, $uploaded_file_link, $now));

            if (!$result) {
                $upload_status = 'error';
                $error_message = 'Database insert failed: ' . pg_last_error($con);
            }
        } else {
            $upload_status = 'error';
            $error_message = 'Drive upload failed - no file ID returned';
        }
    } else {
        $upload_status = 'error';
        $error_message = 'No video data received';
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
    <?php include 'includes/meta.php'; ?>

    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <style>
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

        .question-card {
            background: #f8f9ff;
            border-left: 4px solid #0d6efd;
            margin-bottom: 0.8rem;
            padding: 0.8rem 1.2rem;
            border-radius: 12px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .question-card:hover {
            background: #e8ecf5;
            transform: translateX(3px);
        }

        .question-number {
            font-weight: 700;
            color: #0d6efd;
            margin-right: 10px;
            display: inline-block;
            width: 28px;
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
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Virtual Interview Portal</h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div>

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
                                    <br><small>Drive Link: <a href="<?php echo $uploaded_file_link; ?>" target="_blank"><?php echo $uploaded_file_link; ?></a></small>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <script>
                                    if (window.history.replaceState) {
                                        // Update the URL without causing a page reload or resubmission
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                </script>;
                            <?php elseif ($upload_status == 'error'): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <strong>Error!</strong> <?php echo htmlspecialchars($error_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <div class="interview-header">
                                <div class="d-flex flex-wrap justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-2"><i class="bi bi-camera-reels-fill me-2"></i>Online Interview Assessment</h2>
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

                            <div class="interview-layout">
                                <!-- LEFT PANEL: Questions -->
                                <div class="questions-panel">
                                    <h5 class="mb-3"><i class="bi bi-question-circle-fill text-primary"></i> Questions to Answer</h5>
                                    <div class="question-card"><span class="question-number">1.</span> Introduce yourself.</div>
                                    <div class="question-card"><span class="question-number">2.</span> What do you know about RSSI NGO and its key projects?</div>
                                    <div class="question-card"><span class="question-number">3.</span> Which shift are you most comfortable with: Pre-primary (11am-3pm) or Primary (2:30pm-6:30pm)?</div>
                                    <div class="question-card"><span class="question-number">4.</span> Are you aware that the internship requires a minimum commitment of 1 month, 4 days/week, 4 hours/day?</div>
                                    <div class="question-card"><span class="question-number">5.</span> What is your expected joining date?</div>
                                    <div class="question-card"><span class="question-number">6.</span> Would you be able to relocate to Lucknow for the 1-month duration?</div>
                                    <div class="question-card"><span class="question-number">7.</span> Which language(s) are you comfortable with? Are you comfortable teaching in Hindi?</div>
                                    <div class="question-card"><span class="question-number">8.</span> Are you comfortable visiting students' homes and interacting with parents?</div>
                                </div>

                                <!-- RIGHT PANEL: Video Recording -->
                                <div class="video-panel">
                                    <h5 class="mb-3"><i class="bi bi-camera-video-fill text-primary"></i> Record Your Response</h5>

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
            uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Uploading to Google Drive...';
        });

        window.addEventListener('load', () => {
            cameraToggle.checked = false;
            uploadBtn.disabled = true;
            videoStatusSpan.innerHTML = '<i class="bi bi-info-circle"></i> Turn on camera to start';
        });
    </script>
</body>

</html>