<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

@$associate_number = @strtoupper($_GET['associate-number']);
$result = pg_query($con, "SELECT fullname,associatenumber,doj,effectivedate,remarks,photo,engagement,position,depb,filterstatus,certificate_url,badge_name, onboard_initiated_by,onboarding_gen_otp_center_incharge,onboarding_gen_otp_associate,email FROM rssimyaccount_members 
LEFT JOIN (SELECT awarded_to_id,badge_name,certificate_url FROM certificate WHERE badge_name='Joining Letter') certificate ON certificate.awarded_to_id = rssimyaccount_members.associatenumber
LEFT JOIN resourcemovement ON resourcemovement.onboarding_associate_id = rssimyaccount_members.associatenumber
WHERE associatenumber = '$associate_number'");

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

    $query = "select onboarding_gen_otp_associate, onboarding_gen_otp_center_incharge from resourcemovement WHERE onboarding_associate_id='$otp_initiatedfor_main'";
    $result = pg_query($con, $query);
    $db_otp_associate = pg_fetch_result($result, 0, 0);
    $db_otp_centreincharge = pg_fetch_result($result, 0, 1);

    $authSuccess = password_verify($otp_associate, $db_otp_associate) && password_verify($otp_centreincharge, $db_otp_centreincharge);
    if ($authSuccess) {
        $otp_initiatedfor_main = $_POST['otp_initiatedfor_main'];
        $onboarding_photo = $_POST['photo'];
        $reporting_date_time = $_POST['reporting-date-time'];
        $now = date('Y-m-d H:i:s');
        $onboarded = "UPDATE resourcemovement SET onboarding_photo='$onboarding_photo', reporting_date_time='$reporting_date_time', onboarding_otp_associate='$otp_associate', onboarding_otp_center_incharge='$otp_centreincharge', onboarding_submitted_by='$associatenumber', onboarding_submitted_on='$now', onboarding_flag='yes'where onboarding_associate_id='$otp_initiatedfor_main'";
        $result = pg_query($con, $onboarded);
        $cmdtuples = pg_affected_rows($result);
    } else {
        $auth_failed_dialog = true;
    }
} else {
}

if (@$auth_failed_dialog) { ?>
    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: The OTP you entered is incorrect.</span>
    </div>
<?php } ?>
<?php
if (@$cmdtuples == 1) {
    echo '<div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">';
    echo '<span><i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>The associate has been onboarded succesfully.</span>';
    echo '</div>';

    // Redirect the user after a delay
    echo '<meta http-equiv="refresh" content="3;url=index.php">';
    exit; // End the script to prevent any further output
}
?>
<!DOCTYPE html>
<html>

<head>
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
    <div class="container">
        <form method="get" name="a_lookup" id="a_lookup">
            <h3>Associate Information Lookup</h3>
            <hr>
            <div class="mb-3">
                <label for="associate-number" class="form-label">Associate Number:</label>
                <input type="text" class="form-control" id="associate-number" name="associate-number" Value="<?php echo $associate_number ?>" placeholder="Enter associate number" required>
                <div class="form-text">Enter the associate number to search for their information.</div>
            </div>
            <button type="submit" class="btn btn-primary mb-3">Search</button>
        </form>
        <?php if (sizeof($resultArr) > 0) { ?>
            <?php foreach ($resultArr as $array) { ?>
                <?php if (($role == 'Admin' || $role == 'Offline Manager') && $array['onboard_initiated_by'] != null && $array['certificate_url'] != null) { ?>
                    <form method="post" name="a_onboard" id="a_onboard" action="onboarding.php">
                        <input type="hidden" name="form-type" type="text" value="onboarding">
                        <input type="hidden" name="otp_initiatedfor_main" type="text" value="<?php echo $array['associatenumber'] ?>" readonly>

                        <h3>Associate Onboarding Form</h3>
                        <hr>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 text-end">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#joining-letter-modal">
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
                        <div class="modal fade" id="joining-letter-modal" tabindex="-1" aria-labelledby="joining-letter-modal-label" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="joining-letter-modal-label">Joining Letter</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Use the preview URL in the src attribute of the iframe tag -->
                                        <iframe src="<?php echo $preview_url; ?>" style="width:100%; height:500px;"></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label for="photo" class="form-label">Current Photo</label>
                            <input type="hidden" class="form-control" id="photo" name="photo">
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

                        <!-- <img id="photo-preview" class="img-thumbnail" alt="Captured Photo" width="320" height="240" src="">
                        <script>
                            const photoInput_display = document.getElementById('photo');
                            const photoPreview = document.getElementById('photo-preview');
                            photoPreview.setAttribute('src', photoInput_display.value);
                        </script> -->

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="reporting-date-time" class="form-label">Reporting Date &amp; Time</label>
                                <input type="datetime-local" class="form-control" id="reporting-date-time" name="reporting-date-time" required>
                            </div>
                            <div class="col-md-6">
                                <label for="otp-associate" class="form-label">OTP from Associate</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="otp-associate" name="otp-associate" placeholder="Enter OTP" required>
                                    <button class="btn btn-outline-secondary" type="submit" id="submit_gen_otp_associate">Generate OTP</button>
                                    <!--<?php echo ($array['onboarding_gen_otp_associate'] != null) ? "disabled" : ""; ?>-->
                                </div>
                                <div class="form-text">OTP will be sent to the registered email address.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="otp-center-incharge" class="form-label">OTP from Center Incharge</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="otp-center-incharge" name="otp-center-incharge" placeholder="Enter OTP" required>
                                <button class="btn btn-outline-secondary" type="submit" id="submit_gen_otp_centr">Generate OTP</button>
                                <!--<?php echo ($array['onboarding_gen_otp_center_incharge'] != null) ? "disabled" : ""; ?>-->
                            </div>
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>

                    <!-- Form gen_otp_associate -->
                    <form name="gen_otp_associate" id="gen_otp_associate" action="#" method="POST" style="display:inline;">
                        <input type="hidden" name="form-type" type="text" value="gen_otp_associate">
                        <input type="hidden" name="otp_initiatedfor" type="text" value="<?php echo $array['associatenumber'] ?>" readonly>
                    </form>

                    <!-- Form gen_otp_centr -->
                    <form name="gen_otp_centr" id="gen_otp_centr" action="#" method="POST" style="display:inline;">
                        <input type="hidden" name="form-type" type="text" value="gen_otp_centr">
                        <input type="hidden" name="otp_initiatedfor" type="text" value="<?php echo $array['associatenumber'] ?>" readonly>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper"></script> -->
    <!-- Bootstrap 5 JavaScript Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>

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
        }
    </script>

    <script>
        window.ready = function() {
            var myModal = new bootstrap.Modal(document.getElementById('myModal'), {
                backdrop: 'static',
                keyboard: false
            });
            myModal.show();
        };
    </script>
    <!-- <script>
        var myModal = document.getElementById('myModal');

        myModal.addEventListener('click', function(event) {
            if (event.target === myModal) {
                // Clicked outside the modal - close it and unfreeze the background
                $('#myModal').modal('hide');
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            }
        });
    </script> -->

    <script>
        const scriptURL = 'payment-api.php';

        // Get reference to form
        var form = document.forms['gen_otp_associate'];
        var form_centr = document.forms['gen_otp_centr'];

        // Add an event listener to the submit button with id "submit_gen_otp_associate"
        document.getElementById("submit_gen_otp_associate").addEventListener("click", function(event) {
            event.preventDefault(); // prevent default form submission

            if (confirm('Are you sure you want to generate OTP?')) {
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(form)
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

        // Add an event listener to the submit button with id "submit_gen_otp_associate"
        document.getElementById("submit_gen_otp_centr").addEventListener("click", function(event) {
            event.preventDefault(); // prevent default form submission

            if (confirm('Are you sure you want to generate OTP?')) {
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(form_centr)
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


</body>

</html>