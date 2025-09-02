<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Determine the current academic year
$academic_year = (date('m') <= 3)
    ? (date('Y') - 1) . '-' . date('Y')
    : date('Y') . '-' . (date('Y') + 1);

$now = date('Y-m-d H:i:s');
$currentAcademicYear = $academic_year;
$lyear = $currentAcademicYear;

// Fetch leave balances
$totalsl = pg_query($con, "SELECT COALESCE(SUM(days), 0) 
                           FROM leavedb_leavedb 
                           WHERE applicantid='$associatenumber' 
                             AND typeofleave='Sick Leave' 
                             AND lyear='$lyear' 
                             AND (status!='Rejected' OR status IS NULL)");
$totalcl = pg_query($con, "SELECT COALESCE(SUM(days), 0) 
                           FROM leavedb_leavedb 
                           WHERE applicantid='$associatenumber' 
                             AND typeofleave='Casual Leave' 
                             AND lyear='$lyear' 
                             AND (status!='Rejected' OR status IS NULL)");
$cladj = pg_query($con, "SELECT COALESCE(SUM(adj_day), 0) 
                         FROM leaveadjustment 
                         WHERE adj_applicantid='$associatenumber' 
                           AND adj_leavetype='Casual Leave' 
                           AND adj_academicyear='$lyear'");
$sladj = pg_query($con, "SELECT COALESCE(SUM(adj_day), 0) 
                         FROM leaveadjustment 
                         WHERE adj_applicantid='$associatenumber' 
                           AND adj_leavetype='Sick Leave' 
                           AND adj_academicyear='$lyear'");
$allocl = pg_query($con, "SELECT COALESCE(SUM(allo_daycount), 0) 
                          FROM leaveallocation 
                          WHERE allo_applicantid='$associatenumber' 
                            AND allo_leavetype='Casual Leave' 
                            AND allo_academicyear='$lyear'");
$allosl = pg_query($con, "SELECT COALESCE(SUM(allo_daycount), 0) 
                          FROM leaveallocation 
                          WHERE allo_applicantid='$associatenumber' 
                            AND allo_leavetype='Sick Leave' 
                            AND allo_academicyear='$lyear'");

// Calculate balances
$resultArrsl = pg_fetch_result($totalsl, 0, 0);
$resultArrcl = pg_fetch_result($totalcl, 0, 0);
$resultArr_cladj = pg_fetch_result($cladj, 0, 0);
$resultArr_sladj = pg_fetch_result($sladj, 0, 0);
$resultArrrcl = pg_fetch_result($allocl, 0, 0);
$resultArrrsl = pg_fetch_result($allosl, 0, 0);

$slbalance = ($resultArrrsl + $resultArr_sladj) - $resultArrsl;
$clbalance = ($resultArrrcl + $resultArr_cladj) - $resultArrcl;

// Handle leave application
if (isset($_POST['form-type']) && $_POST['form-type'] === "leaveapply") {
    $leaveid = 'RSL' . time();
    $applicantid = $associatenumber;
    $fromdate = $_POST['fromdate'];
    $todate = $_POST['todate'];
    $uploadedFile = $_FILES['medicalcertificate'];
    $typeofleave = $_POST['typeofleave'];
    $creason = isset($_POST['creason']) ? $_POST['creason'] : null;
    $appliedby = $associatenumber;
    $shift = isset($_POST['shift']) ? $_POST['shift'] : null;
    $applicantcomment = htmlspecialchars($_POST['applicantcomment'], ENT_QUOTES, 'UTF-8');
    $ack = $_POST['ack'] ?? 0;
    $halfday = $_POST['is_userh'] ?? 0;

    // Check for half-day leave overlap
    if ($halfday == 1) {
        $query = "SELECT typeofleave 
                  FROM leavedb_leavedb 
                  WHERE applicantid='$applicantid' 
                    AND fromdate='$fromdate' 
                    AND halfday=1";
        $result = pg_query($con, $query);
        $existingLeave = pg_fetch_assoc($result);

        if ($existingLeave) {
            $existingType = $existingLeave['typeofleave'];

            // Restrict mixing SL and CL but allow LWP
            if (
                in_array($existingType, ['Sick Leave', 'Casual Leave']) &&
                in_array($typeofleave, ['Sick Leave', 'Casual Leave']) &&
                $existingType !== $typeofleave
            ) {
                echo "<script>
                        alert('You have already applied for a half-day {$existingType} leave on this date. Mixing Sick Leave and Casual Leave is not allowed. Please apply for the same type of leave or use Leave Without Pay.');
                        window.history.back();
                      </script>";
                exit;
            }
        }
    }

    // Calculate leave days
    $day = ($halfday == 1) ?
        round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1) / 2 :
        round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1);

    // Validate leave balance and process application
    if (($slbalance >= $day && $typeofleave === "Sick Leave") ||
        ($clbalance >= $day && $typeofleave === "Casual Leave") ||
        $typeofleave === "Leave Without Pay" || "Adjustment Leave"
    ) {

        $doclink = !empty($uploadedFile['name'])
            ? uploadeToDrive($uploadedFile, '1zbevlcQJg2sZcldp23ix1uGqy5cy5Un-Sy8x8cwz0L15GRhSSdFy0k7HjMjraVwefgB6TfL0', "doc_{$leaveid}_{$applicantid}_" . time())
            : null;

        $leaveQuery = "INSERT INTO leavedb_leavedb 
                       (timestamp, leaveid, applicantid, fromdate, todate, typeofleave, creason, appliedby, lyear, applicantcomment, days, halfday, doc, ack, shift) 
                       VALUES ('$now', '$leaveid', '$applicantid', '$fromdate', '$todate', '$typeofleave', '$creason', '$appliedby', '$currentAcademicYear', '$applicantcomment', '$day', $halfday, '$doclink', '$ack', '$shift')";

        // Execute the query
        $queryResult = pg_query($con, $leaveQuery);

        // Check if the query was successful
        if ($queryResult) {
            $supervisorEmail = '';
            $supervisorQuery = pg_query($con, "
                SELECT COALESCE(NULLIF(alt_email, ''), email) AS email
                FROM rssimyaccount_members 
                WHERE associatenumber = (
                    SELECT supervisor 
                    FROM rssimyaccount_members 
                    WHERE associatenumber = '$applicantid'
                )
            ");
            if ($supervisorQuery && pg_num_rows($supervisorQuery) > 0) {
                $supervisorEmail = pg_fetch_result($supervisorQuery, 0, 0);
            }
            // Send email notification if the query is successful
            if (!empty($email)) {
                sendEmail("leaveapply", [
                    "leaveid" => $leaveid,
                    "applicantid" => $applicantid,
                    "applicantname" => $fullname,
                    "fromdate" => date("d/m/Y", strtotime($fromdate)),
                    "todate" => date("d/m/Y", strtotime($todate)),
                    "typeofleave" => $typeofleave,
                    "category" => $creason,
                    "day" => $day,
                    "now" => date("d/m/Y g:i a", strtotime($now)),
                ], $email, true, $supervisorEmail);
            }

            // Update balance
            if ($typeofleave === "Sick Leave") {
                $slbalance -= $day;
            } elseif ($typeofleave === "Casual Leave") {
                $clbalance -= $day;
            }

            // Show success message
            echo "<script>
                    alert('Your request has been submitted. Leave id {$leaveid}.');
                    if (window.history.replaceState) {
                        // Update the URL without causing a page reload or resubmission
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload(); // Trigger a page reload to reflect changes
                </script>";
        } else {
            // Handle query failure (optional)
            echo "<script>
                    alert('ERROR: Failed to submit leave request.');
                    window.history.back();
                  </script>";
        }
    } else {
        // Show error message if leave balance is insufficient
        if ($slbalance < $day && $typeofleave === "Sick Leave") {
            echo "<script>
                    alert('ERROR: Your SL request has not been submitted because you have applied for more than the leave balance.');
                    window.history.back();
                  </script>";
        } elseif ($clbalance < $day && $typeofleave === "Casual Leave") {
            echo "<script>
                    alert('ERROR: Your CL request has not been submitted because you have applied for more than the leave balance.');
                    window.history.back();
                  </script>";
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Apply for Leave</title>

    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

    <style>
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }

        #hidden-panel,
        #hidden-panel_ack,
        #hidden-panel_creason {
            display: none;
        }

        .modal {
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
        }

        .notification-box {
            align-self: flex-start;
            /* Ensures height is based on content */
            background-color: #e0f7fa;
            border-left: 5px solid #9EEAF9;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .notification-box a {
            color: #0277bd;
            text-decoration: underline;
        }

        @media (prefers-reduced-motion: reduce) {
            .notification-box {
                animation: none;
            }
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Apply for Leave</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">My Services</a></li>
                    <li class="breadcrumb-item active">Apply for Leave</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <div class="container">

                                <br>
                                <div class="row">
                                    <!-- Left Column (Leave Information) -->
                                    <div class="col-md-3 notification-box mb-3">
                                        <!-- Section Title -->
                                        <p class="fs-5 fw-bold mb-3">Leave Information</p>
                                        <span class="text-muted">Data reflects the current month. Leave credits are updated by the 5th of each month.</span>

                                        <!-- Current Leave Balance -->
                                        <div class="current-balance mt-3 mb-3">
                                            <p class="fs-6 mb-1">Current Leave Balance:</p>
                                            <ul class="list-unstyled ms-3">
                                                <li>Sick Leave: <?php echo $slbalance; ?></li>
                                                <li>Casual Leave: <?php echo $clbalance; ?></li>
                                            </ul>
                                        </div>

                                        <!-- Total Allocated Leave -->
                                        <div class="total-allocated mb-3">
                                            <p class="fs-6 mb-1">Total Leave Credits (up to current month):</p>
                                            <ul class="list-unstyled ms-3">
                                                <li>Sick Leave: <?php echo $resultArrrsl + $resultArr_sladj; ?></li>
                                                <li>Casual Leave: <?php echo $resultArrrcl + $resultArr_cladj; ?></li>
                                            </ul>
                                        </div>

                                        <div class="leave-links mt-3">
                                            <p class="fs-6 fw-bold mb-2">Quick Links:</p>
                                            <ul class="list-unstyled">
                                                <li class="mb-1">
                                                    <a href="my_leave.php">
                                                        My Leave Record
                                                    </a>
                                                </li>
                                                <li class="mb-1">
                                                    <a href="leaveallo.php">
                                                        Leave Allocation Report
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Right Column (wide) -->
                                    <div class="col-md-6 right-column px-5">
                                        <!-- Warning message for leave eligibility -->
                                        <span id="leaveWarning" style="color: red;"></span>

                                        <form autocomplete="off" name="leaveapply" id="leaveapply" action="leave.php" method="POST" enctype="multipart/form-data">
                                            <fieldset <?php echo ($filterstatus != 'Active') ? 'disabled' : ''; ?>>
                                                <div class="form-group">

                                                    <input type="hidden" name="form-type" value="leaveapply">

                                                    <!-- Start Date -->
                                                    <div class="form-group mb-2">
                                                        <label for="fromdate" class="form-label">Start Date</label>
                                                        <input type="date" class="form-control" name="fromdate" id="fromdate" max="" onchange="cal(); checkLeaveType();" required>
                                                    </div>

                                                    <!-- End Date -->
                                                    <div class="form-group mb-2">
                                                        <label for="todate" class="form-label">End Date</label>
                                                        <input type="date" class="form-control" name="todate" id="todate" min="" onchange="cal(); checkLeaveType();" required>
                                                    </div>

                                                    <!-- Half Day -->
                                                    <div id="filter-checksh" class="mb-2">
                                                        <input type="checkbox" name="is_userh" id="is_userh" value="1" onchange="cal(); toggleShiftField()" disabled />
                                                        <label for="is_userh" style="font-weight: 400;">Half day</label>
                                                    </div>

                                                    <!-- Day Count -->
                                                    <div class="form-group mb-2">
                                                        <input type="text" class="form-control" name="numdays2" id="numdays2" placeholder="Day count" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" size="10" readonly>
                                                        <small id="passwordHelpBlock" class="form-text text-muted">Days count</small>
                                                    </div>

                                                    <!-- Shift Selection (For Non-Full-Time Employees) -->
                                                    <div id="shiftField" class="form-group mb-2" style="display: none;">
                                                        <label for="shift" class="form-label">Shift</label>
                                                        <?php if ($job_type != 'Full-time') { ?>
                                                            <select name="shift" id="shift" class="form-select">
                                                                <option disabled selected hidden value="">Select</option>
                                                                <option value="MFH">Morning First Half</option>
                                                                <option value="MSH">Morning Second Half</option>
                                                                <option value="AFH">Afternoon First Half</option>
                                                                <option value="ASH">Afternoon Second Half</option>
                                                            </select>
                                                        <?php } else { ?>
                                                            <select name="shift" id="shift" class="form-select">
                                                                <option disabled selected hidden value="">Select</option>
                                                                <option value="MOR">Morning</option>
                                                                <option value="AFN">Afternoon</option>
                                                            </select>
                                                        <?php } ?>
                                                    </div>

                                                    <!-- Types of Leave -->
                                                    <div class="form-group mb-2">
                                                        <label for="typeofleave" class="form-label">Types of Leave</label>
                                                        <select name="typeofleave" id="typeofleave" class="typeofleave form-select" required>
                                                            <option disabled selected hidden value="">Select</option>
                                                            <option value="Sick Leave">Sick Leave</option>
                                                            <option value="Casual Leave">Casual Leave</option>

                                                            <?php if ($position !== 'Intern'): ?>
                                                                <option value="Leave Without Pay">Leave Without Pay</option>
                                                            <?php endif; ?>

                                                            <?php if ($position === 'Intern'): ?>
                                                                <option value="Adjustment Leave">Adjustment Leave</option>
                                                            <?php endif; ?>
                                                        </select>
                                                    </div>
                                                    <span name="hidden-panel_creason" id="hidden-panel_creason">
                                                        <span id="response"></span>
                                                    </span>

                                                    <!-- Medical Certificate Upload -->
                                                    <div name="hidden-panel" id="hidden-panel" class="form-group mb-2">
                                                        <label for="medicalcertificate" class="form-label">Documents</label>
                                                        <input type="file" name="medicalcertificate" id="medicalcertificate" class="form-control" />
                                                        <small id="passwordHelpBlock" class="form-text text-muted">Attach supporting medical documents</small>
                                                    </div>

                                                    <!-- Remarks Section -->
                                                    <div class="form-floating mb-2">
                                                        <textarea name="applicantcomment" class="form-control" class="form-control" placeholder="Leave a comment here"></textarea>
                                                        <label for="applicantcomment" class="form-label">Remarks</label>
                                                        <!-- <small id="passwordHelpBlock" class="form-text text-muted">Any additional comments or details</small> -->
                                                    </div>

                                                    <span name="hidden-panel_ack" id="hidden-panel_ack">
                                                        <div class="form-group mb-2">
                                                            <div id="filter-checksh">
                                                                <input type="checkbox" name="ack" id="ack" value="1" />
                                                                <label for="ack" style="font-weight: 400;"> I hereby confirm submitting the relevant supporting medical documents if the leave duration is more than 2 days.</label>
                                                            </div>
                                                        </div>
                                                    </span>
                                                    <button type="submit" name="search_by_id" class="btn btn-primary">Submit</button>
                                                </div>
                                            </fieldset>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End Reports -->
                    </div>
                </div>
        </section>
    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
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
        document.getElementById('leaveapply').addEventListener('submit', function(event) {
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
        function checkLeaveType() {
            // Get today's date from the server (IST timezone) and normalize it to midnight
            const today = new Date('<?php echo date("Y-m-d"); ?>T00:00:00');
            today.setHours(0, 0, 0, 0); // Normalize to midnight

            console.log("Today's normalized date (from server):", today); // Log the normalized today value

            // Get fromdate and todate values from input fields
            const fromdate = new Date(document.getElementById("fromdate").value);
            const todate = new Date(document.getElementById("todate").value);

            // Normalize fromdate and todate to midnight
            fromdate.setHours(0, 0, 0, 0);
            todate.setHours(0, 0, 0, 0);

            console.log("Normalized fromdate:", fromdate); // Log the normalized fromdate
            console.log("Normalized todate:", todate); // Log the normalized todate

            // Reset typeofleave if fromdate or todate changes
            document.getElementById("typeofleave").value = ""; // Reset the selected value

            // Disable Casual Leave if fromdate or todate is today or in the past
            if (fromdate <= today || todate <= today) {
                document.getElementById("typeofleave").options[2].disabled = true; // Disable Casual Leave
                document.getElementById("leaveWarning").innerText = "You have selected current or past date. You are not eligible to apply for Casual Leave.";
            } else {
                document.getElementById("typeofleave").options[2].disabled = false; // Enable Casual Leave
                document.getElementById("leaveWarning").innerText = ""; // Clear warning message
            }
        }
    </script>
    <script>
        if (<?php echo $slbalance ?> <= 0) {
            document.getElementById("typeofleave").options[1].disabled = true;
        } else {
            document.getElementById("typeofleave").options[1].disabled = false;
        }

        if (<?php echo $clbalance ?> <= 0) {
            document.getElementById("typeofleave").options[2].disabled = true;
        } else {
            document.getElementById("typeofleave").options[2].disabled = false;
        }
    </script>
    <script>
        function toggleShiftField() {
            var isHalfDay = document.getElementById('is_userh').checked;
            var shiftField = document.getElementById('shiftField');
            var shiftSelect = document.getElementById('shift');
            var isDisabled = document.getElementById('is_userh').disabled;

            if (isHalfDay && !isDisabled) {
                shiftField.style.display = 'block';
                shiftSelect.required = true;
            } else {
                shiftField.style.display = 'none';
                shiftSelect.required = false;
                shiftSelect.value = '';
            }
        }

        function cal() {
            if (document.getElementById("todate") || document.getElementById("fromdate")) {
                function GetDays() {
                    var todate = new Date(document.getElementById("todate").value);
                    var fromdate = new Date(document.getElementById("fromdate").value);
                    var diffDays = (todate - fromdate) / (24 * 3600 * 1000) + 1;

                    var todatecheck = document.forms["leaveapply"]["todate"].value;
                    var fromdatecheck = document.forms["leaveapply"]["fromdate"].value;

                    if ((todatecheck == null || fromdatecheck == null) || diffDays !== 1) {
                        document.getElementById("is_userh").disabled = true;
                        document.getElementById("is_userh").checked = false;
                        toggleShiftField(); // Call to hide shift field if needed
                    } else {
                        document.getElementById("is_userh").disabled = false;
                    }
                    if ($('#is_userh').not(':checked').length > 0) {
                        return (diffDays);

                    } else if (event.target.checked) {
                        return (diffDays / 2);
                    }
                    const checkbox = document.getElementById('is_userh');
                    checkbox.addEventListener('change', (event) => {
                        if (event.target.checked) {
                            return (diffDays / 2);
                        } else if ($('#is_userh').not(':checked').length > 0) {
                            return (diffDays);
                        }
                    })
                }
                document.getElementById("numdays2").value = GetDays();

                document.getElementById("todate").min = document.getElementById("fromdate").value;
                document.getElementById("fromdate").max = document.getElementById("todate").value;
            }
        }
    </script>
    <!--To make a filed (acknowledgement) required based on a dropdown value (sick leave)-->
    <script>
        if (document.getElementById('typeofleave').value == "Leave Without Pay" && document.getElementById('typeofleave').value == "Adjustment Leave" && document.getElementById('typeofleave').value == "Casual Leave") {

            document.getElementById("ack").required = false;
        } else {

            document.getElementById("ack").required = true;
        }

        const randvar = document.getElementById('typeofleave');

        randvar.addEventListener('change', (event) => {
            if (document.getElementById('typeofleave').value == "Sick Leave") {

                document.getElementById("ack").required = true;

            } else {

                document.getElementById("ack").required = false;
            }
        })
    </script>
    <script>
        $(document).ready(function() {
            // Function to add the red asterisk next to required fields
            function addAsteriskToRequiredFields(element) {
                var label = $(element).closest('.form-group').find('label');

                // Check if the field is required and the asterisk isn't already added
                if ($(element).prop('required') && !label.find('span').length) {
                    label.append(' <span style="color: red">*</span>');
                }
            }

            // Loop through all select, input, and textarea fields to add asterisks where required
            $('select, input, textarea').each(function() {
                addAsteriskToRequiredFields(this);
            });
        });
    </script>
    <!--Here .typeofleave is a class and has been assigned to the input filed id=typeofleave-->
    <script type="text/javascript">
        $(document).ready(function() {
            $("select.typeofleave").change(function() {
                var selectedtypeofleave = $(".typeofleave option:selected").val();
                $.ajax({
                    type: "POST",
                    url: "process-request.php",
                    data: {
                        typeofleave: selectedtypeofleave
                    }
                }).done(function(data) {
                    $("#response").html(data);
                });
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $("#typeofleave").change(function() {
                var leaveType = $("#typeofleave").val();

                // Reset checkbox and file input if hidden
                if (leaveType !== "Sick Leave") {
                    // Uncheck the 'ack' checkbox if leave type is not Sick Leave
                    $("#ack").prop('checked', false);
                    // Reset the file input field when the 'Documents' section is hidden
                    $("#medicalcertificate").val('');
                }

                // Show and hide panels based on the leave type
                if (leaveType == "Sick Leave") {
                    $("#hidden-panel").show();
                    $("#hidden-panel_ack").show();
                    $("#hidden-panel_creason").show();
                } else if (leaveType == "Casual Leave") {
                    $("#hidden-panel").hide();
                    $("#hidden-panel_ack").hide();
                    $("#hidden-panel_creason").show();
                } else if (leaveType == "Leave Without Pay" || leaveType == "Adjustment Leave") {
                    $("#hidden-panel").hide();
                    $("#hidden-panel_ack").hide();
                    $("#hidden-panel_creason").hide();
                } else {
                    $("#hidden-panel").hide();
                    $("#hidden-panel_ack").hide();
                    $("#hidden-panel_creason").hide();
                }
            });
        });
    </script>
</body>

</html>