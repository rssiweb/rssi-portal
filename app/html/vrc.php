<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");
include(__DIR__ . "../../util/email.php");

// Check if the form data has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data and sanitize
    $application_number = $_POST['applicationNumber'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $videoData = $_POST['videoData'];
    $now = date('Y-m-d H:i:s');
    function getUserIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // HTTP_X_FORWARDED_FOR can contain a comma-separated list of IPs. The first one is the client's real IP.
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipList[0]);
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    $ip_address = getUserIpAddr();

    // Generate a unique ID
    $unique_id = uniqid();

    // Decode Base64 video data to binary
    $videoBinary = base64_decode($videoData);

    // Encode video data as Base64 to store in TEXT column
    $base64EncodedVideo = base64_encode($videoBinary);

    // Ensure that you have a valid database connection
    if (!$con) {
        echo "Error: Unable to connect to the database.";
        exit;
    }

    // Prepare SQL statement to insert data into the table
    $query = "INSERT INTO onlineinterview (unique_id, application_number, name, video,timestamp,ip_address,email) VALUES ($1, $2, $3, $4,$5,$6,$7)";

    // Using pg_query_params with correct parameters for a prepared statement
    $result = pg_query_params($con, $query, array($unique_id, $application_number, $name, $base64EncodedVideo, $now, $ip_address, $email));
    $cmdtuples = pg_affected_rows($result);

    if (@$cmdtuples == 1 && $email != "") {
        sendEmail("vrc", array(
            "unique_id" => $unique_id,
            "application_number" => $application_number,
            "fullname" => @$name,
            "now" => @date("d/m/Y g:i a", strtotime($now))
        ), $email);
    }

    // Check if insertion was successful
    if ($result) {
        // echo "Application submitted successfully!";
    } else {
        echo "Error: " . pg_last_error($con);
    }

    // Close the connection
    pg_close($con);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Response Center</title>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Container Styles */
        .video-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border-radius: 10px;
            background-color: #f5f5f5;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* Video Stream Styles */
        .video-stream {
            width: 100%;
            height: 300px;
            border-radius: 10px;
            background-color: #000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 15px;
        }

        /* Toggle Switch Styles */
        .camera-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
        }

        .toggle-switch {
            appearance: none;
            width: 40px;
            height: 20px;
            background-color: #ddd;
            border-radius: 10px;
            position: relative;
            outline: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .toggle-switch:checked {
            background-color: #4CAF50;
        }

        .toggle-switch::before {
            content: "";
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            background-color: #fff;
            border-radius: 50%;
            transition: transform 0.3s;
        }

        .toggle-switch:checked::before {
            transform: translateX(20px);
        }

        .toggle-label {
            font-size: 1rem;
            margin-left: 10px;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="row">
        <?php if (@$cmdtuples == 0 && @$unique_id != null) { ?>
            <div class="alert alert-danger alert-dismissible text-center" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <i class="bi bi-x-lg"></i>
                <span>ERROR: Submission failed. Please check your internet connection and try again. If the issue persists, contact support.</span>
            </div>
        <?php } else if (@$cmdtuples == 1) { ?>
            <div class="alert alert-success alert-dismissible text-center" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <i class="bi bi-check2-circle"></i>
                <span>Interview submitted successfully! Your submission reference ID is <?php echo $unique_id ?>. Please keep this ID for any future correspondence.</span>
            </div>
            <script>
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.href);
                }
            </script>
        <?php } ?>

    </div><br>

    <div class="container mt-5">
        <h2 class="text-center">Virtual Response Center</h2>

        <form id="applicationForm" method="POST" enctype="multipart/form-data">
            <!-- Application Number Input -->
            <div class="mb-3">
                <label for="applicationNumber" class="form-label">Application Number:</label>
                <input type="text" class="form-control" id="applicationNumber" name="applicationNumber" required>
            </div>

            <!-- Name Input -->
            <div class="mb-3">
                <label for="name" class="form-label">Name:</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <!-- Email Input -->
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <!-- Video Recording Section -->
            <div class="mb-3">
                <label class="form-label">Video Recording:</label>
                <!-- <div class="video-container">
                    <video id="video" width="100%" height="300" autoplay muted></video>
                    <video id="previewVideo" width="100%" height="300" controls hidden></video>

                    <div class="mb-3 form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="cameraToggle" checked>
                        <label class="form-check-label" for="cameraToggle">Camera On/Off</label>
                    </div>
                </div> -->

                <div class="video-container">
                    <video id="video" class="video-stream" autoplay muted></video>
                    <video id="previewVideo" class="video-stream" controls hidden></video>
                    <!-- Camera Toggle Switch -->
                    <div class="camera-toggle">
                        <input type="checkbox" class="toggle-switch" id="cameraToggle" checked>
                        <label class="toggle-label" for="cameraToggle">Camera On/Off</label>
                    </div>
                </div>

                <p id="videoText">When you are ready, please start recording.</p>
                <div class="mt-3">
                    <button type="button" class="btn btn-primary" id="startRecording">Start Recording</button>
                    <button type="button" class="btn btn-danger" id="stopRecording" disabled>Stop Recording</button>
                    <button type="button" class="btn btn-secondary" id="previewRecording" hidden>Preview</button>
                    <button type="button" class="btn btn-warning" id="retakeRecording" hidden>Retake</button>
                </div>
            </div>

            <!-- Hidden Input for Video Blob -->
            <input type="hidden" name="videoData" id="videoData">

            <!-- Submit Button -->
            <button type="submit" class="btn btn-success">Submit Application</button>
        </form>
    </div>

    <script>
        let mediaRecorder;
        let recordedChunks = [];
        let stream;

        const startRecordingButton = document.getElementById('startRecording');
        const stopRecordingButton = document.getElementById('stopRecording');
        const previewRecordingButton = document.getElementById('previewRecording');
        const retakeRecordingButton = document.getElementById('retakeRecording');
        const videoElement = document.getElementById('video');
        const previewVideoElement = document.getElementById('previewVideo');
        const videoText = document.getElementById('videoText');
        const cameraToggle = document.getElementById('cameraToggle');

        // Function to start the camera
        async function startCamera() {
            stream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            });
            videoElement.srcObject = stream;
            startRecordingButton.disabled = false;
        }

        // Function to stop the camera
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                videoElement.srcObject = null;
                startRecordingButton.disabled = true;
                stopRecordingButton.disabled = true;
            }
        }

        // Initialize camera on page load
        window.onload = startCamera;

        // Toggle camera on/off
        cameraToggle.addEventListener('change', () => {
            if (cameraToggle.checked) {
                startCamera();
            } else {
                stopCamera();
            }
        });

        // Show webcam feed as soon as the page loads (before starting recording)
        window.onload = async function() {
            stream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            });
            videoElement.srcObject = stream;
        };

        // Start recording and turn on the camera when button is clicked
        startRecordingButton.addEventListener('click', async () => {
            recordedChunks = [];
            mediaRecorder = new MediaRecorder(stream);

            mediaRecorder.ondataavailable = function(event) {
                if (event.data.size > 0) {
                    recordedChunks.push(event.data);
                }
            };

            mediaRecorder.onstop = function() {
                const blob = new Blob(recordedChunks, {
                    type: 'video/webm'
                });
                recordedChunks = [];
                const videoURL = URL.createObjectURL(blob);

                // Set preview video source and show preview
                previewVideoElement.src = videoURL;
                previewVideoElement.hidden = false;
                videoElement.hidden = true;

                videoText.hidden = true;
                previewRecordingButton.hidden = true;
                retakeRecordingButton.hidden = false;
                startRecordingButton.disabled = true;
                stopRecordingButton.disabled = true;

                // Save video data as Base64 to send with form submission
                blobToBase64(blob, base64Video => {
                    document.getElementById('videoData').value = base64Video;
                });
            };

            videoText.hidden = true; // Hide the prompt when recording starts
            mediaRecorder.start();

            // Button states
            startRecordingButton.disabled = true;
            stopRecordingButton.disabled = false;
            previewRecordingButton.hidden = true;
            retakeRecordingButton.hidden = true;
        });

        // Stop recording and turn off the camera
        stopRecordingButton.addEventListener('click', () => {
            if (mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                startRecordingButton.disabled = false;
                stopRecordingButton.disabled = true;

                // Stop all tracks to release the camera
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        });

        // Preview the recorded video
        previewRecordingButton.addEventListener('click', () => {
            videoElement.hidden = true;
            previewVideoElement.hidden = false;
        });

        // Retake video (reset to pre-recording state)
        retakeRecordingButton.addEventListener('click', async () => {
            // Stop current stream (if any)
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }

            // Re-enable the webcam feed
            stream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            });
            videoElement.srcObject = stream;

            // Reset the UI to initial state before recording
            recordedChunks = [];
            previewVideoElement.hidden = true;
            videoElement.hidden = false;
            videoText.hidden = false; // Show the prompt again
            startRecordingButton.disabled = false;
            stopRecordingButton.disabled = true;
            previewRecordingButton.hidden = true;
            retakeRecordingButton.hidden = true;
        });

        // Helper function to convert Blob to Base64
        function blobToBase64(blob, callback) {
            const reader = new FileReader();
            reader.onload = function() {
                const base64Data = reader.result.split(',')[1];
                callback(base64Data);
            };
            reader.readAsDataURL(blob);
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>