<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

@$associate_number = @strtoupper($_GET['associate-number']);
$result = pg_query($con, "SELECT fullname,associatenumber,doj,effectivedate,remarks,photo,engagement,position,depb FROM rssimyaccount_members WHERE associatenumber = '$associate_number'");
$resultArr = pg_fetch_all($result);
if (!$result) {
    echo "An error occurred.\n";
    exit;
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
    <title>Exit form</title>
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
                <input type="text" class="form-control" id="associate-number" name="associate-number" Value="<?php echo $associate_number ?>" placeholder="Enter associate number">
                <div class="form-text">Enter the associate number to search for their information.</div>
            </div>
            <button type="submit" class="btn btn-primary mb-3">Search</button>
        </form>
        <?php if (sizeof($resultArr) > 0) { ?>
            <?php foreach ($resultArr as $array) { ?>
                <?php if ($role == 'Admin' || $role == 'Offline Manager') { ?>
                    <form method="post" name="a_exit" id="a_exit">

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


                        <div class="mb-3">
                            <label for="reason-for-leaving" class="form-label">Reason for Leaving:</label>
                            <textarea class="form-control" rows="5" id="reason-for-leaving"><?php echo $array['remarks'] ?></textarea>
                            <div class="form-text">Enter the reason for the associate leaving the company.</div>
                        </div>

                        <div class="mb-3">
                            <label for="clearance">Clearance</label>
                            <div class="form-text">Prior to release, the associate must obtain the following clearances. <p>To know more details <a href="#" data-bs-toggle="modal" data-bs-target="#popup">click here</a>.</p>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="asset-clearance" name="clearance[]" value="asset-clearance">
                                <label class="form-check-label" for="asset-clearance">Asset Clearance</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="financial-clearance" name="clearance[]" value="financial-clearance">
                                <label class="form-check-label" for="financial-clearance">Financial Clearance</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="security-clearance" name="clearance[]" value="security-clearance">
                                <label class="form-check-label" for="security-clearance">Security Clearance</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="hr-clearance" name="clearance[]" value="hr-clearance">
                                <label class="form-check-label" for="hr-clearance">HR Clearance</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="work-clearance" name="clearance[]" value="work-clearance">
                                <label class="form-check-label" for="work-clearance">Work Clearance</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="legal-clearance" name="clearance[]" value="legal-clearance">
                                <label class="form-check-label" for="legal-clearance">Legal Clearance</label>
                            </div>
                        </div>



                        <div class="mb-3">
                            <label for="exit-interview" class="form-label">Exit Interview:</label>
                            <textarea class="form-control" rows="5" id="exit-interview"></textarea>
                            <div class="form-text">Enter any comments or feedback from the associate's exit interview.</div>
                        </div>

                        <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="reporting-date-time" class="form-label">Exit Form Date &amp; Time</label>
                                    <input type="datetime-local" class="form-control" id="reporting-date-time" name="reporting-date-time" value="<?php echo @$array['reporting_date_time'] ?>" required>
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


                    </form>
                <?php } ?>
            <?php } ?>
            <?php if ($role != 'Admin' && $role != 'Offline Manager') { ?><p>Oops! It looks like you're trying to access the data that doesn't belong to you.</p><?php } ?>
        <?php
        } else if ($associate_number == null) {
        ?>
            <p>Please enter the Associate ID.</p>
        <?php
        } else {
        ?>
            <p>We could not find any records matching the entered Goal sheet ID.</p>
        <?php } ?>
    </div>

    <!-- Bootstrap JS -->

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/js/bootstrap.min.js"></script>
</body>

</html>