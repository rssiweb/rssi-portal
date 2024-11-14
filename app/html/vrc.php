<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");
include(__DIR__ . "../../util/email.php");

// Check if the form data has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data and sanitize
    $application_number = $_POST['applicationNumber_verify'];
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
    $query = "INSERT INTO vrc (unique_id, application_number, name, video,timestamp,ip_address,email) VALUES ($1, $2, $3, $4,$5,$6,$7)";

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
                <span>Video response received successfully! Your submission reference ID is <?php echo $unique_id ?>. Please keep this ID for any future correspondence.</span>
            </div>
            <script>
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.href);
                }
            </script>
        <?php } ?>

    </div><br>

    <div class="container">
        <h2 class="text-center">Virtual Response Center</h2>

        <form id="applicationForm" method="POST" enctype="multipart/form-data">
            <!-- Application Number Input -->
            <div class="input-group mb-3">
                <input type="text" class="form-control" id="applicationNumber_verify" name="applicationNumber_verify" placeholder="Enter your Application Number" required>
                <!-- Change button type to 'button' to prevent form submission -->
                <button type="button" class="btn btn-primary" id="verifybutton">Find your details</button>
            </div>

            <!-- Name Input -->
            <div class="mb-3">
                <label for="name" class="form-label">Name:</label>
                <input type="text" class="form-control" id="name" name="name" readonly>
            </div>

            <!-- Email Input -->
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email" readonly>
            </div>

            <!-- Video Recording Section -->
            <div class="mb-3">
                <label class="form-label">Video Recording:</label>

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

                <div class="mt-3 d-flex justify-content-between">
                    <div>
                        <button type="button" class="btn btn-primary" id="startRecording">Start Recording</button>
                        <button type="button" class="btn btn-danger" id="stopRecording" disabled>Stop Recording</button>
                        <button type="button" class="btn btn-secondary" id="previewRecording" hidden>Preview</button>
                        <button type="button" class="btn btn-warning" id="retakeRecording" hidden>Retake</button>
                    </div>
                    <!-- Submit Button aligned to the right -->
                    <button type="submit" class="btn btn-success">Submit Application</button>
                </div>

            </div>

            <!-- Hidden Input for Video Blob -->
            <input type="hidden" name="videoData" id="videoData">
        </form>
        <!-- Hidden Form for AJAX Request -->
        <form name="get_details_vrc" id="get_details_vrc" action="#" method="POST">
            <input type="hidden" name="form-type" value="get_details_vrc">
            <input type="hidden" name="applicationNumber_verify_input">
        </form>
    </div>
    <script>
        let userDataFetched = false; // To track if user data is fetched
        let videoRecorded = false; // To track if video is recorded
        const applicationNumberInput = document.getElementById("applicationNumber_verify");
        const applicationNumberVerifyInput = document.getElementsByName("applicationNumber_verify_input")[0]; // Accessing the hidden field properly
        const nameInput = document.getElementById("name");
        const emailInput = document.getElementById("email");
        const submitButton = document.querySelector('button[type="submit"]');

        // Copy value from applicationNumber_verify to hidden applicationNumber_verify_input
        applicationNumberInput.addEventListener("input", function() {
            applicationNumberVerifyInput.value = this.value;
        });

        // Function to handle the "verifybutton" click event
        function handleVerifyButtonClick(event) {
            event.preventDefault(); // prevent default form submission

            // http://localhost:8082/rssi-member/payment-api.php

            fetch('https://login.rssi.in/rssi-member/payment-api.php', {
                    method: 'POST',
                    body: new FormData(document.forms['get_details_vrc'])
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        nameInput.value = data.data.fullname;
                        emailInput.value = data.data.email;
                        userDataFetched = true; // User data fetched successfully
                        alert("User data fetched successfully!");
                    } else if (data.status === 'no_records') {
                        userDataFetched = false; // No records found
                        nameInput.value = "";
                        emailInput.value = "";
                        alert("No records found in the database. Please proceed with other options.");
                    } else {
                        console.error('Error:', data.message);
                        alert("Error retrieving user data. Please try again later or contact support.");
                    }
                    // Call checkSubmitButtonStatus after data fetch
                    checkSubmitButtonStatus();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Error fetching user data. Please try again later or contact support.");
                });
        }

        // Add event listener to the "verifybutton" element
        document.getElementById("verifybutton").addEventListener("click", handleVerifyButtonClick);
    </script>

    <script>
        let mediaRecorder;
        let recordedChunks = [];
        let stream;
        let countdownTimer;
        let remainingTime = 60; // 1-minute timer

        const startRecordingButton = document.getElementById('startRecording');
        const stopRecordingButton = document.getElementById('stopRecording');
        const previewRecordingButton = document.getElementById('previewRecording');
        const retakeRecordingButton = document.getElementById('retakeRecording');
        const videoElement = document.getElementById('video');
        const previewVideoElement = document.getElementById('previewVideo');
        const videoText = document.getElementById('videoText');
        const countdownDisplay = document.createElement('p'); // Countdown timer display

        // Add countdown display below video text
        videoText.after(countdownDisplay);

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
            clearInterval(countdownTimer); // Stop the countdown timer
            remainingTime = 60; // Reset for next recording
            countdownDisplay.innerText = ""; // Clear countdown display
        }

        // Initialize camera and disable submit button on page load
        window.onload = function() {
            startCamera(); // Start the camera
            submitButton.disabled = true; // Disable submit button initially
        };

        // Toggle camera on/off
        cameraToggle.addEventListener('change', () => {
            if (cameraToggle.checked) {
                startCamera();
            } else {
                stopCamera();
            }
        });

        // Function to update countdown display
        function updateCountdown() {
            countdownDisplay.innerText = `Time Remaining: ${remainingTime} seconds`;
            remainingTime--;

            if (remainingTime < 0) {
                stopRecordingButton.click(); // Automatically stop recording
                clearInterval(countdownTimer);
                remainingTime = 60; // Reset for next recording
            }
        }

        // Function to start recording
        startRecordingButton.addEventListener('click', () => {
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
                const videoSizeMB = (blob.size / (1024 * 1024)).toFixed(2); // Calculate size in MB
                alert(`Recording stopped! Final video size: ${videoSizeMB} MB`);

                // Check if the video size is 0, and disable submit button if true
                if (blob.size === 0) {
                    submitButton.disabled = true; // Disable submit button
                    alert("The video file size is 0. Please record a valid video.");
                    videoRecorded = false; // Video is not valid
                } else {
                    videoRecorded = true; // Valid video
                    checkSubmitButtonStatus(); // Check if submit button can be enabled
                }

                const videoURL = URL.createObjectURL(blob);
                previewVideoElement.src = videoURL;
                previewVideoElement.hidden = false;
                videoElement.hidden = true;
                videoText.hidden = true;
                retakeRecordingButton.hidden = false;
                startRecordingButton.disabled = true;
                stopRecordingButton.disabled = true;

                blobToBase64(blob, base64Video => {
                    document.getElementById('videoData').value = base64Video;
                });
            };

            mediaRecorder.start();
            countdownTimer = setInterval(updateCountdown, 1000); // Start countdown
            remainingTime = 60; // Reset timer to 60 seconds
            countdownDisplay.innerText = `Time Remaining: ${remainingTime} seconds`;

            startRecordingButton.disabled = true;
            stopRecordingButton.disabled = false;
        });

        // Function to stop recording
        stopRecordingButton.addEventListener('click', () => {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                clearInterval(countdownTimer); // Stop the countdown
                remainingTime = 60; // Reset for next recording
            }
        });

        // Retake video functionality
        retakeRecordingButton.addEventListener('click', async () => {
            // Ensure the camera toggle switch is set to "on"
            cameraToggle.checked = true; // This forces the toggle to be in the "on" position
            stopCamera();
            await startCamera();

            recordedChunks = [];
            previewVideoElement.hidden = true;
            videoElement.hidden = false;
            videoText.hidden = false;
            startRecordingButton.disabled = false;
            stopRecordingButton.disabled = true;
            previewRecordingButton.hidden = true;
            retakeRecordingButton.hidden = true;
            countdownDisplay.innerText = ""; // Reset countdown display
            // Disable the submit button until a valid recording is made
            submitButton.disabled = true;
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
        // Function to check submit button status
        function checkSubmitButtonStatus() {
            // Enable submit button only if user data is fetched and video is recorded
            if (userDataFetched && videoRecorded) {
                submitButton.disabled = false;
            } else {
                submitButton.disabled = true;
            }
        }
    </script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>