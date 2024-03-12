<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

@$associate_number = @strtoupper($_GET['associate-number']);
$result = pg_query($con, "SELECT fullname, associatenumber, doj, effectivedate, rssimyaccount_members.remarks reason_remarks, photo, engagement, position, depb, filterstatus,exit_initiated_by, exit_gen_otp_center_incharge, exit_gen_otp_associate, email, exit_flag, exit_photo, exit_date_time, asset_clearance, financial_clearance, security_clearance, hr_clearance, work_clearance, legal_clearance, ip_address, exit_submitted_by, exit_submitted_on, exit_initiated_on, exit_initiated_by, associate_exit.remarks exit_remarks, exit_interview
    FROM rssimyaccount_members
    LEFT JOIN associate_exit ON associate_exit.exit_associate_id = rssimyaccount_members.associatenumber
    WHERE rssimyaccount_members.associatenumber = '$associate_number'");

$resultArr = pg_fetch_all($result);
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

?>
<?php

if (@$_POST['form-type'] == "exit") {

    $otp_associate = $_POST['otp-associate'];
    $otp_centreincharge = $_POST['otp-center-incharge'];
    $otp_initiatedfor_main = $_POST['otp_initiatedfor_main'];

    $query = "select exit_gen_otp_associate, exit_gen_otp_center_incharge from associate_exit WHERE exit_associate_id='$otp_initiatedfor_main'";
    $result = pg_query($con, $query);
    $db_otp_associate = pg_fetch_result($result, 0, 0);
    $db_otp_centreincharge = pg_fetch_result($result, 0, 1);

    @$authSuccess = password_verify($otp_associate, $db_otp_associate) && password_verify($otp_centreincharge, $db_otp_centreincharge);
    if ($authSuccess) {
        $otp_initiatedfor_main = $_POST['otp_initiatedfor_main'];
        $exit_photo = $_POST['photo'];
        $exit_remarks = htmlspecialchars($_POST['reason-for-leaving'], ENT_QUOTES, 'UTF-8');

        $asset_clearance = isset($_POST['clearance']) && in_array('asset-clearance', $_POST['clearance']) ? "TRUE" : "FALSE";
        $financial_clearance = isset($_POST['clearance']) && in_array('financial-clearance', $_POST['clearance']) ? "TRUE" : "FALSE";
        $security_clearance = isset($_POST['clearance']) && in_array('security-clearance', $_POST['clearance']) ? "TRUE" : "FALSE";
        $hr_clearance = isset($_POST['clearance']) && in_array('hr-clearance', $_POST['clearance']) ? "TRUE" : "FALSE";
        $work_clearance = isset($_POST['clearance']) && in_array('work-clearance', $_POST['clearance']) ? "TRUE" : "FALSE";
        $legal_clearance = isset($_POST['clearance']) && in_array('legal-clearance', $_POST['clearance']) ? "TRUE" : "FALSE";

        $exit_interview = htmlspecialchars($_POST['exit-interview'], ENT_QUOTES, 'UTF-8');
        $exit_date_time = $_POST['exit-date-time'];
        $now = date('Y-m-d H:i:s');
        $ip_address = $_SERVER['REMOTE_ADDR']; // Get the IP address of the user
        $exit = "UPDATE associate_exit SET exit_photo='$exit_photo', exit_date_time='$exit_date_time', otp_associate='$otp_associate', otp_center_incharge='$otp_centreincharge', exit_submitted_by='$associatenumber', exit_submitted_on='$now', exit_flag='yes', ip_address='$ip_address', remarks='$exit_remarks', asset_clearance=$asset_clearance, financial_clearance=$financial_clearance, security_clearance=$security_clearance, hr_clearance=$hr_clearance, work_clearance=$work_clearance,legal_clearance=$legal_clearance, exit_interview='$exit_interview' where exit_associate_id='$otp_initiatedfor_main'";
        $result = pg_query($con, $exit);
        $cmdtuples = pg_affected_rows($result);
    } else {
        $auth_failed_dialog = true;
    }
} else {
}

if (@$auth_failed_dialog) { ?>
    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
        <i class="bi bi-x-lg"></i>&nbsp;&nbsp;<span>ERROR: The OTP you entered is incorrect.</span>
    </div>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
<?php } ?>
<?php
if (@$cmdtuples == 1) {
    echo '<div class="alert alert-success" role="alert" style="text-align: -webkit-center;">';
    echo '<h4 class="alert-heading" style="font-size: 1.5rem;">The exit process has been successfully completed.</h4>
';

    // Redirect the user after a delay
    $redirect_url = 'exit.php?associate-number=' . $otp_initiatedfor_main;
    echo '<p style="font-size: 1.2rem;">Redirecting to the exit page in <span id="countdown">3</span> seconds.</p>';
    echo '</div>';
    echo '<script>
            var timeleft = 3;
            var countdown = setInterval(function(){
                document.getElementById("countdown").innerHTML = timeleft;
                timeleft -= 1;
                if(timeleft <= 0){
                    clearInterval(countdown);
                    window.location.href = "' . $redirect_url . '";
                }
            }, 1000);
          </script>';
    //   exit; 
}
?>
<!DOCTYPE html>
<html>

<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Exit form</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <div class="container">
        <form method="get" name="a_lookup" id="a_lookup">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Associate Information Lookup</h3>
                <a href="javascript:history.go(-1)">Go to previous link</a>
            </div>
            <hr>
            <div class="mb-3">
                <label for="associate-number" class="form-label">Associate Number:</label>
                <input type="text" class="form-control" id="associate-number" name="associate-number" Value="<?php echo $associate_number ?>" placeholder="Enter associate number">
                <div class="form-text">Enter the associate number to search for their information.</div>
            </div>
            <button type="submit" class="btn btn-primary mb-3">Search</button>
        </form>
        <?php if (sizeof($resultArr) > 0) { ?>
            <?php foreach ($resultArr as $array) { ?>
                <?php if (($role == 'Admin' || $role == 'Offline Manager') && $array['exit_initiated_by'] != null) { ?>
                    <form method="post" name="a_exit" id="a_exit" action="exit.php">

                        <h3>Associate Exit Form</h3>
                        <hr>
                        <div class="container">
                            <div class="row">
                                <div class="col-md-4 text-center mb-3">
                                    <img src="<?php echo $array['photo'] ?>" alt="Profile picture" width="100px">
                                </div>
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h2><?php echo $array['fullname'] ?></h2>
                                            <p><strong>Associate ID:</strong> <?php echo $array['associatenumber'] ?></p>
                                            <p><strong>Joining Date:</strong> <?php echo date('M d, Y', strtotime($array['doj'])) ?></p>
                                            <strong>Last Working Day:</strong> <?php echo ($array['effectivedate'] == null) ? "N/A" : date('M d, Y', strtotime($array['effectivedate'])); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Engagement:</strong> <?php echo $array['engagement']; ?></p>
                                            <p><strong>Position:</strong> <?php echo implode('-', array_slice(explode('-',  $array['position']), 0, 2)); ?></p>
                                            <p><strong>Deputed Branch:</strong> <?php echo $array['depb']; ?></p>
                                            <!-- Add any additional information you want to display here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <fieldset <?php echo ($array['exit_flag'] == "yes") ? "disabled" : ""; ?>>

                            <input type="hidden" name="form-type" type="text" value="exit">
                            <input type="hidden" name="otp_initiatedfor_main" type="text" value="<?php echo $array['associatenumber'] ?>" readonly>

                            <div class="mb-3">
                                <label for="photo" class="form-label">Current Photo</label>
                                <input type="hidden" class="form-control" id="photo" name="photo" value="<?php echo $array['exit_photo'] ?>">
                                <div class="mt-2">
                                    <button type="button" class="btn btn-primary" onclick="startCamera()">Start Camera</button>
                                    <button type="button" class="btn btn-primary d-none" id="capture-btn" onclick="capturePhoto()">Capture Photo</button>
                                </div>
                            </div>
                            <div class="mt-3">
                                <video id="video-preview" class="img-thumbnail d-none" alt="Preview" width="320" height="240"></video>
                                <canvas id="canvas-preview" class="d-none" width="640" height="480"></canvas>
                                <img id="photo-preview" class="d-none img-thumbnail" alt="Captured Photo" width="320" height="240" src="">
                            </div>
                            <?php if ($array['exit_photo'] != null) { ?>
                                <div class="row mb-3">
                                    <img id="photo-preview" class="img-thumbnail" alt="Captured Photo" style="width:500px;" src="<?php echo $array['exit_photo'] ?>">
                                </div>
                            <?php } ?>

                            <div class="mb-3">
                                <label for="reason-for-leaving" class="form-label">Reason for Leaving:</label>
                                <textarea class="form-control" rows="5" name="reason-for-leaving" id="reason-for-leaving"><?php echo $array['exit_remarks'] == null ? $array['reason_remarks'] : $array['exit_remarks']; ?>
                                </textarea>
                                <div class="form-text">Enter the reason for the associate leaving the company.</div>
                            </div>

                            <div class="mb-3">
                                <label for="clearance">Clearance</label>
                                <div class="form-text">Prior to release, the associate must obtain the following clearances. <p>To know more details <a href="#" data-bs-toggle="modal" data-bs-target="#popup">click here</a>.</p>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="asset-clearance" name="clearance[]" value="asset-clearance" <?php if ($array['asset_clearance'] === 't') echo 'checked'; ?> required>
                                    <label class="form-check-label" for="asset-clearance">Asset Clearance</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="financial-clearance" name="clearance[]" value="financial-clearance" <?php if ($array['financial_clearance'] === 't') echo 'checked'; ?> required>
                                    <label class="form-check-label" for="financial-clearance">Financial Clearance</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="security-clearance" name="clearance[]" value="security-clearance" <?php if ($array['security_clearance'] === 't') echo 'checked'; ?> required>
                                    <label class="form-check-label" for="security-clearance">Security Clearance</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="hr-clearance" name="clearance[]" value="hr-clearance" <?php if ($array['hr_clearance'] === 't') echo 'checked'; ?> required>
                                    <label class="form-check-label" for="hr-clearance">HR Clearance (Submission of Goal Sheet, Internship Report (if applicable), and <a href="https://www.google.com/search?q=rssi+ngo&oq=rssi+ngo&aqs=chrome.0.35i39i355i650j46i39i175i199i650j69i64j0i512j69i60l2j69i61j69i65.2251j0j4&sourceid=chrome&ie=UTF-8#lrd=0x399be3fc575228e3:0xbbc4182b61aa1609,1,,,," target="_blank">Google Review</a> etc.)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="work-clearance" name="clearance[]" value="work-clearance" <?php if ($array['work_clearance'] === 't') echo 'checked'; ?> required>
                                    <label class="form-check-label" for="work-clearance">Work Clearance</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="legal-clearance" name="clearance[]" value="legal-clearance" <?php if ($array['legal_clearance'] === 't') echo 'checked'; ?> required>
                                    <label class="form-check-label" for="legal-clearance">Legal Clearance</label>
                                </div>
                            </div>


                            <div class="mb-3">
                                <label for="exit-interview" class="form-label">Exit Interview:</label>
                                <textarea class="form-control" rows="5" name="exit-interview" id="exit-interview"><?php echo $array['exit_interview'] ?></textarea>
                                <div class="form-text">Enter any comments or feedback from the associate's exit interview.</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="exit-date-time" class="form-label">Exit Form Date &amp; Time</label>
                                    <input type="datetime-local" class="form-control" id="exit-date-time" name="exit-date-time" value="<?php echo @$array['exit_date_time'] ?>" required>
                                    <div class="form-text">Enter the date the exit form was completed.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="otp-associate" class="form-label">OTP from Associate</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="otp-associate" name="otp-associate" placeholder="Enter OTP" required>
                                        <button class="btn btn-outline-secondary" type="submit" id="submit_gen_otp_associate">Generate OTP</button>
                                    </div>
                                    <div class="form-text">OTP will be sent to the registered email address.</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="otp-center-incharge" class="form-label">OTP from Center Incharge</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="otp-center-incharge" name="otp-center-incharge" placeholder="Enter OTP" required>
                                    <button class="btn btn-outline-secondary" type="submit" id="submit_gen_otp_centr">Generate OTP</button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    Separation Status
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <div class="row">
                                            <div class="col-6">Initiated On:</div>
                                            <div class="col-6">
                                                <?php echo ($array['exit_initiated_on'] !== null) ? date('d/m/y h:i:s a', strtotime($array['exit_initiated_on'])) . ' by ' . $array['exit_initiated_by'] : '<span class="text-muted">Not initiated yet</span>' ?>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="list-group-item">
                                        <div class="row">
                                            <div class="col-6">Submitted On:</div>
                                            <div class="col-6">
                                                <?php
                                                if ($array['exit_submitted_on'] !== null) {
                                                    echo date('d/m/y h:i:s a', strtotime($array['exit_submitted_on'])) . ' by ' . $array['exit_submitted_by'] . '<br>';
                                                    echo 'IP Address: ' . $array['ip_address'];
                                                } else {
                                                    echo '<span class="text-muted">Not submitted yet</span>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <br><br>
                            <!-- Popup -->
                            <div class="modal fade" id="popup" tabindex="-1" aria-labelledby="popupLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <!-- Header -->
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="popupLabel">Types of clearance</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <!-- Body -->
                                        <div class="modal-body">
                                            <ol>
                                                <li>Asset clearance - The associate must return all company assets, such as laptops, phones, and equipment, and ensure that they are in good working condition.</li>
                                                <li>Financial clearance - The associate must settle any outstanding financial obligations, such as outstanding loans or unpaid expenses.</li>
                                                <li>Security clearance - The associate must return all security-related items, such as access cards, keys, and passwords, and ensure that any confidential information or data is secured or deleted.</li>
                                                <li>HR clearance - The associate must complete any HR-related tasks, such as exit interviews or paperwork, and ensure that their personal and professional details are updated and accurate.</li>
                                                <li>Work clearance - The associate must complete or delegate all outstanding work tasks and ensure that all projects or assignments are handed over to appropriate parties.</li>
                                                <li>Legal clearance - The associate must resolve any legal issues related to their work, such as contract or intellectual property disputes, and ensure that all legal requirements are fulfilled.</li>
                                            </ol>

                                        </div>
                                        <!-- Footer -->
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </fieldset>
                    </form>
                    <!-- Form gen_otp_associate -->
                    <form name="exit_gen_otp_associate" id="exit_gen_otp_associate" action="#" method="POST" style="display:inline;">
                        <input type="hidden" name="form-type" value="exit_gen_otp_associate">
                        <input type="hidden" name="otp_initiatedfor" value="<?php echo $array['associatenumber'] ?>" readonly>
                        <input type="hidden" name="associate_name" value="<?php echo $array['fullname'] ?>" readonly>
                        <input type="hidden" name="associate_email" value="<?php echo $array['email'] ?>" readonly>
                    </form>

                    <!-- Form gen_otp_centr -->
                    <form name="exit_gen_otp_centr" id="exit_gen_otp_centr" action="#" method="POST" style="display:inline;">
                        <input type="hidden" name="form-type" value="exit_gen_otp_centr">
                        <input type="hidden" name="otp_initiatedfor" value="<?php echo $array['associatenumber'] ?>" readonly>
                        <input type="hidden" name="centre_incharge_name" value="<?php echo $fullname ?>" readonly>
                        <input type="hidden" name="centre_incharge_email" value="<?php echo $email ?>" readonly>
                    </form>

                <?php } else if (($role == 'Admin' || $role == 'Offline Manager') && $array['exit_initiated_by'] == null) { ?>
                    <!-- Exit not initiated -->
                    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Access Denied</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Access Denied: Exit process has not been initiated in the system. Please contact RSSI support team for assistance.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php } else if ($role != 'Admin' && $role != 'Offline Manager') { ?>
                    <!-- Modal -->
                    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Access Denied</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Access Denied: Required permissions are missing. Contact RSSI support team if you believe this is a mistake.</p>
                                </div>
                            </div>
                        </div>
                    </div>

            <?php }
            } ?>

        <?php } else if ($associate_number != null) { ?>
            <!-- Modal -->
            <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Access Denied</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Access Denied: Associate not found. Contact RSSI support for assistance.</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php } ?>
    </div>
    <!-- Bootstrap JS -->

    <!-- jQuery Library -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper"></script> -->
    <!-- Bootstrap 5 JavaScript Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>

    <script>
        const scriptURL = 'payment-api.php';

        // Add an event listener to the submit button with id "submit_gen_otp_associate"
        document.getElementById("submit_gen_otp_associate").addEventListener("click", function(event) {
            event.preventDefault(); // prevent default form submission

            if (confirm('Are you sure you want to generate OTP?')) {
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['exit_gen_otp_associate'])
                    })
                    .then(response => response.text())
                    .then(result => {
                        if (result == 'success') {
                            alert("OTP generated successfully!")
                        } else {
                            alert("Error generating OTP. Please try again later or contact support.")
                        }
                    })
            } else {
                alert("OTP generation cancelled.");
                return false;
            }
        })

        // Add an event listener to the submit button with id "submit_gen_otp_centr"
        document.getElementById("submit_gen_otp_centr").addEventListener("click", function(event) {
            event.preventDefault(); // prevent default form submission

            if (confirm('Are you sure you want to generate OTP?')) {
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['exit_gen_otp_centr'])
                    })
                    .then(response => response.text())
                    .then(result => {
                        if (result == 'success') {
                            alert("OTP generated successfully!")
                        } else {
                            alert("Error generating OTP. Please try again later or contact support.")
                        }
                    })
            } else {
                alert("OTP generation cancelled.");
                return false;
            }
        })
    </script>

    <script>
        window.onload = function() {
            var myModal = new bootstrap.Modal(document.getElementById('myModal'), {
                backdrop: 'static',
                keyboard: false
            });
            myModal.show();
        };
    </script>


    <script>
        let videoPreview, canvasPreview, photoInput, captureBtn;

        function startCamera() {
            const constraints = {
                video: true,
                audio: false
            };

            videoPreview = document.getElementById('video-preview');
            canvasPreview = document.getElementById('canvas-preview');
            photoInput = document.getElementById('photo');
            captureBtn = document.getElementById('capture-btn');

            navigator.mediaDevices.getUserMedia(constraints)
                .then(stream => {
                    videoPreview.srcObject = stream;
                    videoPreview.play();
                    captureBtn.classList.remove('d-none');
                    // canvasPreview.classList.remove('d-none');
                    videoPreview.classList.remove('d-none');
                    document.getElementById('photo-preview').classList.add('d-none');
                })
                .catch(error => {
                    console.error('Error accessing camera: ', error);
                });

            videoPreview.addEventListener('canplay', () => {
                canvasPreview.width = videoPreview.videoWidth;
                canvasPreview.height = videoPreview.videoHeight;
                canvasPreview.getContext('2d').drawImage(videoPreview, 0, 0, canvasPreview.width, canvasPreview.height);
            });
        }
    </script>
    <script>
        let photoCaptured = false;

        function capturePhoto() {
            canvasPreview.getContext('2d').drawImage(videoPreview, 0, 0, canvasPreview.width, canvasPreview.height);
            const photoURL = canvasPreview.toDataURL('image/png');
            photoInput.value = photoURL;
            videoPreview.srcObject.getTracks().forEach(track => track.stop());
            canvasPreview.classList.add('d-none');
            videoPreview.classList.add('d-none');
            captureBtn.classList.add('d-none');
            document.getElementById('photo-preview').setAttribute('src', photoURL);
            document.getElementById('photo-preview').classList.remove('d-none');
            document.getElementById('video-preview').classList.add('d-none');
            // Set the flag to indicate that the photo has been captured
            photoCaptured = true;
        }
        // Validate the form before submission
        function validateForm(event) {
            if (!photoCaptured) {
                alert('Please capture the photo before submitting the form.');
                return false; // Prevent form submission
            }
            return true; // Allow form submission
        }
        // Attach the form validation to the form's submit event
        document.getElementById('a_exit').onsubmit = validateForm;
    </script>

</body>

</html>
