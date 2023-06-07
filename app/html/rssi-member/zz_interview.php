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
        $ip_address = $_SERVER['HTTP_X_REAL_IP']; // Get the IP address of the user
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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <title>Teaching Staff Interview Evaluation Form</title>
</head>

<body>
    <div class="container">
        <form method="get" name="a_lookup" id="a_lookup">
            <h3>Candidate Information Lookup</h3>
            <hr>
            <div class="mb-3">
                <label for="associate-number" class="form-label">Application Number:</label>
                <input type="text" class="form-control" id="associate-number" name="associate-number" Value="<?php echo $associate_number ?>" placeholder="Enter associate number" required>
                <div class="form-text">Enter the associate number to search for their information.</div>
            </div>
            <button type="submit" class="btn btn-primary mb-3">Search</button>
        </form>
        <h3>Teaching Staff Interview Evaluation Form</h3>

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#joining-letter-modal-<?php echo @$array['certificate_no']; ?>">
                            <i class="far fa-file-pdf"></i>
                        </button>
                    </div>
                </div>
                <div class="row align-items-center">
                    <div class="col-md-4 d-flex flex-column justify-content-center align-items-center mb-3">
                        <img src="<?php echo @$array['photo'] ?>" alt="Profile picture" width="100px">
                    </div>

                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-12 d-flex align-items-center">
                                <h2><?php echo @$array['fullname'] ?></h2>
                                <?php if ($array['filterstatus'] == 'Active') : ?>
                                    <span class="badge bg-success ms-3">Active</span>
                                <?php else : ?>
                                    <span class="badge bg-danger ms-3">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Associate ID:</strong> <?php echo @$array['associatenumber'] ?></p>
                                <p><strong>Joining Date:</strong> <?php echo @date('M d, Y', strtotime($array['doj'])) ?></p>
                            </div>

                            <div class="col-md-6">
                                <p><strong>Engagement:</strong> <?php echo @$array['engagement']; ?></p>
                                <p><strong>Position:</strong> <?php echo @implode('-', array_slice(explode('-',  $array['position']), 0, 2)); ?></p>
                                <p><strong>Deputed Branch:</strong> <?php echo @$array['depb']; ?></p>
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
        <form>
            <div class="mb-3">
                <label for="communication" class="form-label">Communication Skills (1-10)</label>
                <input type="range" class="form-range" id="communication" min="1" max="10" step="0.1" value="5">
                <p id="communicationValue">5</p>
            </div>
            <div class="mb-3">
                <label for="subject" class="form-label">Subject Knowledge (1-10)</label>
                <input type="range" class="form-range" id="subject" min="1" max="10" step="0.1" value="5">
                <p id="subjectValue">5</p>
            </div>
            <div class="mb-3">
                <label for="teaching" class="form-label">Teaching Ability (1-10)</label>
                <input type="range" class="form-range" id="teaching" min="1" max="10" step="0.1" value="5">
                <p id="teachingValue">5</p>
            </div>
            <div class="mb-3">
                <label for="overall" class="form-label">Overall Evaluation (1-10)</label>
                <input type="range" class="form-range" id="overall" min="1" max="10" step="0.1" value="5">
                <p id="overallValue">5</p>
            </div>
            <div class="mb-3">
                <label for="comments" class="form-label">Additional Comments</label>
                <textarea class="form-control" id="comments" rows="3"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const communicationSlider = document.getElementById('communication');
        const subjectSlider = document.getElementById('subject');
        const teachingSlider = document.getElementById('teaching');
        const overallSlider = document.getElementById('overall');

        communicationSlider.addEventListener('input', updateSliderValue);
        subjectSlider.addEventListener('input', updateSliderValue);
        teachingSlider.addEventListener('input', updateSliderValue);
        overallSlider.addEventListener('input', updateSliderValue);

        function updateSliderValue(event) {
            const sliderValue = event.target.value;
            event.target.nextElementSibling.textContent = sliderValue;
        }
    </script>
</body>

</html>