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
$result = pg_query($con, "SELECT fullname, associatenumber, doj, effectivedate, remarks, photo, engagement, position, depb, filterstatus, COALESCE(latest_certificate.certificate_url, certificate.certificate_url) AS certificate_url, COALESCE(latest_certificate.badge_name, certificate.badge_name) AS badge_name, onboard_initiated_by, onboarding_gen_otp_center_incharge, onboarding_gen_otp_associate, email, onboarding_flag, onboarding_photo, reporting_date_time, disclaimer, ip_address, onboarding_submitted_by, onboarding_submitted_on, onboard_initiated_on, onboard_initiated_by, COALESCE(latest_certificate.issuedon, certificate.issuedon) AS issuedon, COALESCE(latest_certificate.certificate_no, certificate.certificate_no) AS certificate_no
    FROM rssimyaccount_members
    LEFT JOIN (
        SELECT awarded_to_id, badge_name, certificate_url, issuedon, certificate_no
        FROM certificate
        WHERE badge_name = 'Joining Letter'
        ORDER BY issuedon DESC
        LIMIT 1
    ) latest_certificate ON latest_certificate.awarded_to_id = rssimyaccount_members.associatenumber
    LEFT JOIN certificate ON certificate.awarded_to_id = rssimyaccount_members.associatenumber
    LEFT JOIN onboarding ON onboarding.onboarding_associate_id = rssimyaccount_members.associatenumber
    WHERE rssimyaccount_members.associatenumber = '$associate_number'
    ORDER BY certificate.issuedon DESC
    LIMIT 1");

$resultArr = pg_fetch_all($result);
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

?>
<?php

if (@$_POST['form-type'] == "onboarding") {

    $otp_associate = $_POST['otp-associate'];
    $otp_centreincharge = $_POST['otp-center-incharge'];
    $otp_initiatedfor_main = $_POST['otp_initiatedfor_main'];

    $query = "select onboarding_gen_otp_associate, onboarding_gen_otp_center_incharge from onboarding WHERE onboarding_associate_id='$otp_initiatedfor_main'";
    $result = pg_query($con, $query);
    $db_otp_associate = pg_fetch_result($result, 0, 0);
    $db_otp_centreincharge = pg_fetch_result($result, 0, 1);

    @$authSuccess = password_verify($otp_associate, $db_otp_associate) && password_verify($otp_centreincharge, $db_otp_centreincharge);
    if ($authSuccess) {
        $otp_initiatedfor_main = $_POST['otp_initiatedfor_main'];
        $onboarding_photo = $_POST['photo'];
        $reporting_date_time = $_POST['reporting-date-time'];
        $disclaimer = $_POST['onboarding_complete'];
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
        $onboarded = "UPDATE onboarding SET onboarding_photo='$onboarding_photo', reporting_date_time='$reporting_date_time', onboarding_otp_associate='$otp_associate', onboarding_otp_center_incharge='$otp_centreincharge', onboarding_submitted_by='$associatenumber', onboarding_submitted_on='$now', onboarding_flag='yes', disclaimer='$disclaimer', ip_address='$ip_address' where onboarding_associate_id='$otp_initiatedfor_main'";
        $result = pg_query($con, $onboarded);
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
    echo '<h4 class="alert-heading" style="font-size: 1.5rem;">The associate has been onboarded successfully!</h4>
';

    // Redirect the user after a delay
    $redirect_url = 'onboarding.php?associate-number=' . $otp_initiatedfor_main;
    echo '<p style="font-size: 1.2rem;">Redirecting to the onboarding page in <span id="countdown">3</span> seconds.</p>';
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

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-11316670180');
    </script>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Onboarding form</title>
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
                <?php if (($role == 'Admin' || $role == 'Offline Manager') && $array['onboard_initiated_by'] != null && $array['certificate_url'] != null) { ?>
                    <form method="post" name="a_onboard" id="a_onboard" action="onboarding.php">

                        <h3>Associate Onboarding Form</h3>
                        <hr>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 text-end">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#joining-letter-modal-<?php echo $array['certificate_no']; ?>">
                                            <i class="far fa-file-pdf"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="row align-items-center">
                                    <div class="col-md-4 d-flex flex-column justify-content-center align-items-center mb-3">
                                        <img src="<?php echo $array['photo'] ?>" alt="Profile picture" width="100px">
                                    </div>

                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-12 d-flex align-items-center">
                                                <h2><?php echo $array['fullname'] ?></h2>
                                                <?php if ($array['filterstatus'] == 'Active') : ?>
                                                    <span class="badge bg-success ms-3">Active</span>
                                                <?php else : ?>
                                                    <span class="badge bg-danger ms-3">Inactive</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Associate ID:</strong> <?php echo $array['associatenumber'] ?></p>
                                                <p><strong>Joining Date:</strong> <?php echo date('M d, Y', strtotime($array['doj'])) ?></p>
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
                        </div>
                        <?php
                        // Google Drive URL stored in $array['certificate_url']
                        $url = $array['certificate_url'];

                        // Extract the file ID using regular expressions
                        preg_match('/\/file\/d\/(.+?)\//', $url, $matches);
                        $file_id = $matches[1];

                        // Generate the preview URL
                        $preview_url = "https://drive.google.com/file/d/$file_id/preview";
                        ?>
                        <!-- Modal -->

                        <div class="modal fade" id="joining-letter-modal-<?php echo $array['certificate_no']; ?>" tabindex="-1" aria-labelledby="joining-letter-modal-label" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="joining-letter-modal-label">Joining Letter</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <iframe src="<?php echo $preview_url; ?>" style="width:100%; height:500px;"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <fieldset <?php echo ($array['onboarding_flag'] == "yes") ? "disabled" : ""; ?>>

                            <input type="hidden" name="form-type" type="text" value="onboarding">
                            <input type="hidden" name="otp_initiatedfor_main" type="text" value="<?php echo $array['associatenumber'] ?>" readonly>

                            <div class="mb-3">
                                <label for="photo" class="form-label">Current Photo</label>
                                <input type="hidden" class="form-control" id="photo" name="photo" value="<?php echo $array['onboarding_photo'] ?>">
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
                            <?php if ($array['onboarding_photo'] != null) { ?>
                                <div class="row mb-3">
                                    <img id="photo-preview" class="img-thumbnail" alt="Captured Photo" style="width:500px;" src="<?php echo $array['onboarding_photo'] ?>">
                                </div>
                            <?php } ?>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="reporting-date-time" class="form-label">Reporting Date &amp; Time</label>
                                    <input type="datetime-local" class="form-control" id="reporting-date-time" name="reporting-date-time" value="<?php echo $array['reporting_date_time'] ?>" required>
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
                                <label>
                                    <input type="checkbox" name="onboarding_complete" value="yes" required <?php if ($array['disclaimer'] == 'yes') {
                                                                                                                echo "checked";
                                                                                                            } ?>>
                                    I confirm that I have completed all the onboarding tasks for this associate, including:
                                    <ol>
                                        <li>Reviewing the associate's job description and responsibilities</li>
                                        <li>Providing the associate with access to the required tools and resources</li>
                                        <li>Conducting a briefing on the NGO's policies and procedures</li>
                                        <li>Introducing the associate to their team and colleagues</li>
                                    </ol>
                                </label>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    Onboarding Status
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <div class="row">
                                            <div class="col-6">Initiated On:</div>
                                            <div class="col-6">
                                                <?php echo ($array['onboard_initiated_on'] !== null) ? date('d/m/y h:i:s a', strtotime($array['onboard_initiated_on'])) . ' by ' . $array['onboard_initiated_by'] : '<span class="text-muted">Not initiated yet</span>' ?>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="list-group-item">
                                        <div class="row">
                                            <div class="col-6">Submitted On:</div>
                                            <div class="col-6">
                                                <?php
                                                if ($array['onboarding_submitted_on'] !== null) {
                                                    echo date('d/m/y h:i:s a', strtotime($array['onboarding_submitted_on'])) . ' by ' . $array['onboarding_submitted_by'] . '<br>';
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
                        </fieldset>
                    </form>

                    <!-- Form gen_otp_associate -->
                    <form name="gen_otp_associate" id="gen_otp_associate" action="#" method="POST" style="display:inline;">
                        <input type="hidden" name="form-type" value="gen_otp_associate">
                        <input type="hidden" name="otp_initiatedfor" value="<?php echo $array['associatenumber'] ?>" readonly>
                        <input type="hidden" name="associate_name" value="<?php echo $array['fullname'] ?>" readonly>
                        <input type="hidden" name="associate_email" value="<?php echo $array['email'] ?>" readonly>
                    </form>

                    <!-- Form gen_otp_centr -->
                    <form name="gen_otp_centr" id="gen_otp_centr" action="#" method="POST" style="display:inline;">
                        <input type="hidden" name="form-type" value="gen_otp_centr">
                        <input type="hidden" name="otp_initiatedfor" value="<?php echo $array['associatenumber'] ?>" readonly>
                        <input type="hidden" name="centre_incharge_name" value="<?php echo $fullname ?>" readonly>
                        <input type="hidden" name="centre_incharge_email" value="<?php echo $email ?>" readonly>
                    </form>

                <?php } else if (($role == 'Admin' || $role == 'Offline Manager') && $array['onboard_initiated_by'] == null) { ?>
                    <!-- Onboarding not initiated -->
                    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Access Denied</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Access Denied: Onboarding process has not been initiated in the system. Please contact RSSI support team for assistance.</p>
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
                <?php } else if ($array['certificate_url'] == null) { ?>
                    <!-- Modal -->
                    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Access Denied</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Access Denied: Joining letter not issued yet. Contact RSSI support team for assistance.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php } ?>
            <?php } ?>

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
                            <p>Access Denied: Contact RSSI support team for assistance.</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php } ?>
    </div>

    <!-- Bootstrap JS -->

    <!-- jQuery Library -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper"></script> -->
    <!-- Bootstrap 5 JavaScript Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>

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
        const scriptURL = 'payment-api.php';

        // Add an event listener to the submit button with id "submit_gen_otp_associate"
        document.getElementById("submit_gen_otp_associate").addEventListener("click", function(event) {
            event.preventDefault(); // prevent default form submission

            if (confirm('Are you sure you want to generate OTP?')) {
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['gen_otp_associate'])
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
                        body: new FormData(document.forms['gen_otp_centr'])
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
        document.getElementById('a_onboard').onsubmit = validateForm;
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const style = document.createElement('style');
            style.textContent = '.required-asterisk { color: red; font-size: 1.2em; margin-left: 0.2em; }';
            document.head.appendChild(style);

            // Select all required input, textarea, select, and checkbox elements
            document.querySelectorAll('input[required], textarea[required], select[required]').forEach(input => {
                const label = input.closest('.mb-3').querySelector('label' + (input.type === 'checkbox' ? '' : '[for="' + input.id + '"]'));
                if (label) {
                    const asterisk = document.createElement('span');
                    asterisk.className = 'required-asterisk';
                    asterisk.textContent = '*';
                    label.appendChild(asterisk);
                }
            });
        });
    </script>

</body>

</html>