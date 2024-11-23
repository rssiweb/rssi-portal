<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Steps</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .step-item {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            position: relative;
        }
        .step-item:not(:last-child)::after {
            content: '';
            width: 50px;
            height: 2px;
            background-color: #ccc;
            position: absolute;
            top: calc(50% - 1px);
            left: calc(100% + 10px);
            z-index: -1;
        }
        .step-item.active::after {
            background-color: #4CAF50;
        }
        .step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .step-label {
            color: #999;
        }
        .step-item.active .step-circle {
            background-color: #4CAF50;
            color: white;
        }
        /* Arrow styles */
        .arrow-right {
            width: 0;
            height: 0;
            border-top: 10px solid transparent;
            border-bottom: 10px solid transparent;
            border-left: 10px solid #ccc;
            position: absolute;
            top: calc(50% - 10px);
            left: calc(100% + 5px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-auto">
                <div class="row g-0">
                    <div class="col">
                        <div class="step-item active">
                            <div class="step-circle">1</div>
                            <div class="step-label">Registration</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="step-item">
                            <div class="arrow-right"></div>
                            <div class="step-circle">2</div>
                            <div class="step-label">Identity Verification</div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="step-item">
                            <div class="arrow-right"></div>
                            <div class="step-circle">3</div>
                            <div class="step-label">Schedule Interview</div>
                        </div>
                    </div>
                    <!-- Add more steps as needed -->
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<!-- <tr>
                                                <td>
                                                    <label for="applicant-photo">Upload Applicant Photo:</label>
                                                </td>
                                                <td>
                                                <?php // Extract file ID using regular expression
                                                    preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $array["applicant_photo"], $matches);
                                                    $file_id = $matches[1]; ?>
                                                    <img id="applicant-photo-preview" src="<?php echo 'https://drive.google.com/thumbnail?id=' . $file_id ?>" alt="Uploaded Photo" style="max-width: 200px; max-height: 200px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="resume-upload">Upload
                                                        Resume:</label>
                                                </td>
                                                <td>
                                                    <?php
                                                    preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $array["resume_upload"], $matches);
                                                    $file_id = $matches[1];
                                                    $api_key = "AIzaSyCtWC48inXWXUM8s6hSeX89LP78sfGLk_g"; // Replace with your actual Google Drive API
                                                    // Function to get file name from Google Drive using file ID
                                                    function get_file_name_from_google_drive($file_id, $api_key)
                                                    {
                                                        // Google Drive API endpoint for fetching file metadata
                                                        $url = "https://www.googleapis.com/drive/v3/files/$file_id?key=$api_key";

                                                        // Initialize cURL session
                                                        $ch = curl_init();

                                                        // Set cURL options
                                                        curl_setopt($ch, CURLOPT_URL, $url);
                                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disabling SSL verification (optional)

                                                        // Execute cURL request
                                                        $response = curl_exec($ch);

                                                        // Close cURL session
                                                        curl_close($ch);

                                                        // Decode JSON response
                                                        $data = json_decode($response, true);

                                                        // Extract file name from metadata
                                                        if (isset($data['name'])) {
                                                            return $data['name'];
                                                        } else {
                                                            return null;
                                                        }
                                                    }
                                                    $filename = get_file_name_from_google_drive($file_id, $api_key);
                                                    ?>

                                                    <a href="<?php echo $array["resume_upload"] ?>" target="_blank"><?php echo $filename ?></a>

                                                </td>
                                            </tr> -->