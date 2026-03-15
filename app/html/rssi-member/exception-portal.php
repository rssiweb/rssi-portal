<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");

// Ensure user is logged in
if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

// Form validation logic, if any
validation();

// Get current timestamp
$now = date('Y-m-d H:i:s');
$success = true;
$duplicateRequest = false;
$existingStatus = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $exceptionType = $_POST['exceptionType'];
    $subExceptionType = $_POST['subExceptionType'];
    $startDateTime = !empty($_POST['startDateTime']) ? $_POST['startDateTime'] : null;
    $endDateTime = !empty($_POST['endDateTime']) ? $_POST['endDateTime'] : null;
    $reason = htmlspecialchars($_POST['reason'], ENT_QUOTES, 'UTF-8');
    $submittedBy = $associatenumber;

    // Extract just the date portion (YYYY-MM-DD) for comparison
    $startDate = $startDateTime ? substr($startDateTime, 0, 10) : null;
    $endDate = $endDateTime ? substr($endDateTime, 0, 10) : null;

    // Get the exception date (either start or end date)
    $exceptionDate = $startDate ? $startDate : $endDate;

    // Check for existing non-rejected requests with same parameters on same date(s)
    $checkSql = "SELECT status FROM exception_requests 
                WHERE submitted_by = '$submittedBy' 
                AND exception_type = '$exceptionType' 
                AND sub_exception_type = '$subExceptionType'";

    if ($startDate) {
        $checkSql .= " AND (start_date_time::date = '$startDate'";
        if ($endDate) {
            $checkSql .= " OR end_date_time::date = '$endDate')";
        } else {
            $checkSql .= ")";
        }
    } else if ($endDate) {
        $checkSql .= " AND end_date_time::date = '$endDate'";
    } else {
        // If no dates provided at all, just check type/subtype
        $checkSql .= " AND start_date_time IS NULL AND end_date_time IS NULL";
    }

    $checkSql .= " AND status != 'Rejected'";

    $checkResult = pg_query($con, $checkSql);

    if (pg_num_rows($checkResult) > 0) {
        $existingRequest = pg_fetch_assoc($checkResult);
        $existingStatus = $existingRequest['status'];
        $duplicateRequest = true;
        $success = false;
    }

    // Only proceed if no existing pending/approved request found for same date(s)
    if (!$duplicateRequest) {
        // Generate a unique ID for the request
        $id = uniqid();

        // Prepare SQL statement for insertion
        $sql = "INSERT INTO exception_requests (id, exception_type, sub_exception_type, start_date_time, end_date_time, reason, submitted_on, submitted_by, status) 
        VALUES ('$id', '$exceptionType', '$subExceptionType'," .
            ($startDateTime ? "'$startDateTime'" : "NULL") . ", " .
            ($endDateTime ? "'$endDateTime'" : "NULL") . ", " .
            "'$reason', '$now', '$submittedBy', 'Pending')";

        // Execute the SQL query
        $result = pg_query($con, $sql);

        // Check if the insertion was successful
        if (!$result) {
            $success = false;
        }

        if ($success && $email != "") {
            sendEmail("exceptionapply", array(
                "id" => $id,
                "submittedBy" => $submittedBy,
                "applicantname" => @$fullname,
                "dateTime" => !empty($startDateTime)
                    ? @date("d/m/Y g:i a", strtotime($startDateTime))
                    : (!empty($endDateTime) ? @date("d/m/Y g:i a", strtotime($endDateTime)) : ''),
                "exceptionType" => $subExceptionType,
                "reason" => $reason,
                "now" => @date("d/m/Y g:i a", strtotime($now))
            ), $email);
        }
    }
}
// Fetch the latest reporting_time and exit_time for the associate
$query = "SELECT reporting_time, exit_time FROM associate_schedule WHERE associate_number = '$associatenumber' ORDER BY start_date DESC LIMIT 1";
$result = pg_query($con, $query);

if ($result && pg_num_rows($result) > 0) {
    $schedule = pg_fetch_assoc($result);
    $latestReportingTime = $schedule['reporting_time'];
    $latestExitTime = $schedule['exit_time'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/meta.php' ?>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Template Main CSS File -->
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
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>
    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <?php if ($_POST && !$success) { ?>
                                <script>
                                    // Show error message in a JavaScript alert
                                    <?php if ($duplicateRequest) { ?>
                                        alert("A similar request already exists with status: <?php echo $existingStatus ?>. Only rejected requests can be resubmitted.");
                                    <?php } else { ?>
                                        alert("An error occurred while submitting the request.");
                                    <?php } ?>
                                </script>
                            <?php } elseif ($_POST && $success) { ?>
                                <script>
                                    // Show success message with ID in a JavaScript alert
                                    alert("Exception request submitted successfully. ID: <?php echo @$id ?>");
                                    if (window.history.replaceState) {
                                        // Update the URL without causing a page reload or resubmission
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                    window.location.reload(); // Trigger a page reload to reflect changes
                                </script>
                            <?php } ?>
                            <div class="container mt-4">
                                <form name="exception" id="exception" method="post" action="">
                                    <div class="mb-3">
                                        <label for="exceptionType" class="form-label">Exception Type</label>
                                        <select class="form-select" id="exceptionType" name="exceptionType" required onchange="toggleDateTimeFields()">
                                            <option disabled selected>Select exception type</option>
                                            <option value="entry">Entry</option>
                                            <option value="exit">Exit</option>
                                        </select>
                                    </div>

                                    <!-- Sub Exception Type Field -->
                                    <div class="mb-3" id="subExceptionTypeField" style="display: none;">
                                        <label for="subExceptionType" class="form-label">Sub Exception Type</label>
                                        <select class="form-select" id="subExceptionType" name="subExceptionType" required>
                                            <!-- Options will be populated dynamically -->
                                        </select>
                                    </div>

                                    <!-- Start Date-Time Field -->
                                    <div class="mb-3" id="startDateTimeField" style="display: none;">
                                        <label for="startDateTime" class="form-label">Entry Date-Time</label>
                                        <input type="datetime-local" class="form-control" id="startDateTime" name="startDateTime" required>
                                    </div>

                                    <!-- End Date-Time Field -->
                                    <div class="mb-3" id="endDateTimeField" style="display: none;">
                                        <label for="endDateTime" class="form-label">Exit Date-Time</label>
                                        <input type="datetime-local" class="form-control" id="endDateTime" name="endDateTime" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="reason" class="form-label">Reason</label>
                                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Enter reason for exception" required></textarea>
                                    </div>
                                    <button type="submit" id="submit_button" class="btn btn-primary">Submit</button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>
    <!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <!-- Add this script at the end of the HTML body -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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

        // Function to check exception count for the month
        async function checkExceptionCount(exceptionDate) {
            const associateNumber = "<?php echo $associatenumber; ?>";

            if (!exceptionDate) return true;

            try {
                const response = await fetch(`get_exception_count.php?associate=${associateNumber}&date=${exceptionDate}`);
                const data = await response.json();

                if (data.count >= 3) {
                    const result = await Swal.fire({
                        title: 'Exception Count Exceeded',
                        html: `You already have <strong>${data.count}</strong> approved/pending exception requests for this month.<br><br>The maximum allowed is 3 exceptions per month.<br><br>Do you still want to submit this exception for review?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, submit anyway',
                        cancelButtonText: 'No, cancel'
                    });

                    return result.isConfirmed;
                }
                return true;
            } catch (error) {
                console.error('Error checking exception count:', error);
                return true;
            }
        }

        // Function to check if exception date is within allowed window
        function checkExceptionDateValidity(exceptionDate) {
            if (!exceptionDate) return {
                valid: true,
                message: ''
            };

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const exception = new Date(exceptionDate);
            exception.setHours(0, 0, 0, 0);

            // Calculate difference in days
            const diffTime = exception - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            // Format dates for display
            const formattedExceptionDate = exception.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });

            const formattedToday = today.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });

            // CASE 1: Future dates are allowed (diffDays > 0)
            if (diffDays > 0) {
                return {
                    valid: true,
                    message: ''
                };
            }

            // CASE 2: Past dates - check if within 3 days (including today)
            // diffDays will be negative for past dates
            const daysPast = Math.abs(diffDays);

            if (daysPast <= 3) {
                // Allowed: today (0 days past) or up to 3 days past
                return {
                    valid: true,
                    message: ''
                };
            } else {
                // Not allowed: more than 3 days past
                const lastAllowedDate = new Date(today);
                lastAllowedDate.setDate(today.getDate() - 3);
                const formattedLastAllowed = lastAllowedDate.toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });

                return {
                    valid: false,
                    message: `The selected date (${formattedExceptionDate}) is outside the allowed exception request window. Exception requests must be submitted within 3 calendar days of the exception date.`
                };
            }
        }

        // Update the date input validation
        document.getElementById('startDateTime').addEventListener('change', function() {
            const exceptionDate = this.value.split('T')[0];
            const validation = checkExceptionDateValidity(exceptionDate);

            if (!validation.valid) {
                Swal.fire({
                    title: 'Invalid Exception Date',
                    text: validation.message,
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
                this.value = '';
            }
        });

        document.getElementById('endDateTime').addEventListener('change', function() {
            const exceptionDate = this.value.split('T')[0];
            const validation = checkExceptionDateValidity(exceptionDate);

            if (!validation.valid) {
                Swal.fire({
                    title: 'Invalid Exception Date',
                    text: validation.message,
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
                this.value = '';
            }
        });

        // Override form submission
        // Override form submission
        document.getElementById('exception').addEventListener('submit', async function(event) {
            event.preventDefault();

            const submitBtn = document.getElementById('submit_button');
            const exceptionType = document.getElementById('exceptionType').value;
            const subExceptionType = document.getElementById('subExceptionType').value;
            const startDateTime = document.getElementById('startDateTime').value;
            const endDateTime = document.getElementById('endDateTime').value;

            // Basic validation
            if (!exceptionType || !subExceptionType || !reason.value.trim()) {
                Swal.fire({
                    title: 'Incomplete Form',
                    text: 'Please fill in all required fields.',
                    icon: 'warning',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            // Get the exception date (either start or end date)
            const exceptionDate = startDateTime ? startDateTime.split('T')[0] : (endDateTime ? endDateTime.split('T')[0] : null);

            // First check: Date validity (must be within rules)
            if (exceptionDate) {
                const dateValidation = checkExceptionDateValidity(exceptionDate);
                if (!dateValidation.valid) {
                    await Swal.fire({
                        title: 'Invalid Exception Date',
                        text: dateValidation.message,
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                    return;
                }
            }

            // Show loading state on button
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Checking...';
            submitBtn.disabled = true;

            try {
                // Second check: Exception count for the month
                const canProceed = await checkExceptionCount(exceptionDate);

                if (canProceed) {
                    // Restore button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;

                    // Show loading modal
                    showLoadingModal();
                    // Submit the form
                    this.submit();
                } else {
                    // Restore button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while checking. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
            }
        });

        function toggleDateTimeFields() {
            const exceptionType = document.getElementById('exceptionType').value;
            const startDateTimeField = document.getElementById('startDateTimeField');
            const endDateTimeField = document.getElementById('endDateTimeField');
            const subExceptionTypeField = document.getElementById('subExceptionTypeField');
            const subExceptionType = document.getElementById('subExceptionType');

            // Hide all fields initially
            startDateTimeField.style.display = 'none';
            endDateTimeField.style.display = 'none';
            subExceptionTypeField.style.display = 'none';

            // Remove required attribute from date-time fields
            document.getElementById('startDateTime').required = false;
            document.getElementById('endDateTime').required = false;

            // Clear sub-exception type options
            subExceptionType.innerHTML = '';

            if (exceptionType === 'entry') {
                // Show sub-exception type dropdown
                subExceptionTypeField.style.display = 'block';

                // Populate sub-exception type options for entry
                subExceptionType.innerHTML = `
                <option disabled selected>Select sub exception type</option>
                <option value="late-entry">Late Entry</option>
                <option value="missed-entry">Missed Entry (Missed Punch In)</option>
            `;

                // Show start date-time field
                startDateTimeField.style.display = 'block';
                document.getElementById('startDateTime').required = true;

            } else if (exceptionType === 'exit') {
                // Show sub-exception type dropdown
                subExceptionTypeField.style.display = 'block';

                // Populate sub-exception type options for exit
                subExceptionType.innerHTML = `
                <option disabled selected>Select sub exception type</option>
                <option value="early-exit">Early Exit</option>
                <option value="missed-exit">Missed Exit (Missed Punch Out)</option>
            `;

                // Show end date-time field
                endDateTimeField.style.display = 'block';
                document.getElementById('endDateTime').required = true;
            }
        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Fetch reporting time and exit time from PHP
            const latestReportingTime = "<?php echo $latestReportingTime; ?>";
            const latestExitTime = "<?php echo $latestExitTime; ?>";

            // Function to convert time string (HH:MM:SS) to minutes since midnight
            function timeToMinutes(timeStr) {
                const [hours, minutes] = timeStr.split(':').map(Number);
                return (hours * 60) + minutes;
            }

            // Convert the time strings to minutes
            const reportingTimeMinutes = timeToMinutes(latestReportingTime);
            const exitTimeMinutes = timeToMinutes(latestExitTime);

            // Calculate thresholds in minutes
            const reportingTimeThreshold = reportingTimeMinutes + 60; // 1 hour late
            const exitTimeThreshold = exitTimeMinutes - 60; // 1 hour early

            // Function to format minutes to HH:MM AM/PM
            function formatMinutesToTime(minutes) {
                const hours = Math.floor(minutes / 60);
                const mins = Math.floor(minutes % 60);
                const period = hours >= 12 ? 'PM' : 'AM';
                const formattedHours = hours % 12 || 12; // Convert to 12-hour format
                return `${String(formattedHours).padStart(2, '0')}:${String(mins).padStart(2, '0')} ${period}`;
            }

            // Function to format date as dd/mm/yyyy
            function formatDate(dateStr) {
                const [year, month, day] = dateStr.split('-');
                return `${day}/${month}/${year}`;
            }

            // Format the thresholds
            const formattedReportingTimeThreshold = formatMinutesToTime(reportingTimeMinutes);
            const formattedExitTimeThreshold = formatMinutesToTime(exitTimeMinutes);

            // Handle change events for time inputs and exception type
            document.getElementById('subExceptionType').addEventListener('change', function() {
                checkTimes();
            });

            document.getElementById('startDateTime').addEventListener('change', function() {
                checkTimes();
            });

            document.getElementById('endDateTime').addEventListener('change', function() {
                checkTimes();
            });

            function checkTimes() {
                const subExceptionType = document.getElementById('subExceptionType').value;
                const selectedStartTime = document.getElementById('startDateTime').value;
                const selectedEndTime = document.getElementById('endDateTime').value;

                if (subExceptionType === 'late-entry' && selectedStartTime) {
                    const selectedStartDateTime = new Date(selectedStartTime);
                    const selectedStartMinutes = timeToMinutes(selectedStartTime.split('T')[1]);
                    if (selectedStartMinutes > reportingTimeThreshold) {
                        Swal.fire({
                            title: 'Late Entry Warning',
                            text: `You are applying for a late-entry exception on ${formatDate(selectedStartTime.split('T')[0])} at ${formatMinutesToTime(selectedStartMinutes)}, which is more than 1 hour after your actual reporting time of ${formattedReportingTimeThreshold}. Please apply for leave through the leave portal.`,
                            icon: 'warning',
                            confirmButtonColor: '#3085d6'
                        });
                        document.getElementById('startDateTime').value = ''; // Clear the selected time
                    }
                }

                if (subExceptionType === 'early-exit' && selectedEndTime) {
                    const selectedEndDateTime = new Date(selectedEndTime);
                    const selectedEndMinutes = timeToMinutes(selectedEndTime.split('T')[1]);
                    if (selectedEndMinutes < exitTimeThreshold) {
                        Swal.fire({
                            title: 'Early Exit Warning',
                            text: `You are applying for an early-exit exception on ${formatDate(selectedEndTime.split('T')[0])} at ${formatMinutesToTime(selectedEndMinutes)}, which is more than 1 hour before your actual exit time of ${formattedExitTimeThreshold}. Please apply for leave through the leave portal.`,
                            icon: 'warning',
                            confirmButtonColor: '#3085d6'
                        });
                        document.getElementById('endDateTime').value = ''; // Clear the selected time
                    }
                }
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            $('input, select, textarea').each(function() {
                if ($(this).prop('required')) { // Check if the element has the required attribute
                    $(this).closest('.mb-3').find('label').append(' <span style="color: red">*</span>');
                }
            });
        });
    </script>
</body>

</html>