<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");
include("schedule_helper.php");

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
$validationError = '';
$requestId = null;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $exceptionType = $_POST['exceptionType'] ?? '';
    $subExceptionType = $_POST['subExceptionType'] ?? '';
    $startDateTime = !empty($_POST['startDateTime']) ? $_POST['startDateTime'] : null;
    $endDateTime = !empty($_POST['endDateTime']) ? $_POST['endDateTime'] : null;
    $reason = htmlspecialchars($_POST['reason'] ?? '', ENT_QUOTES, 'UTF-8');
    $submittedBy = $associatenumber;

    // Extract just the date portion (YYYY-MM-DD) for comparison
    $startDate = $startDateTime ? substr($startDateTime, 0, 10) : null;
    $endDate = $endDateTime ? substr($endDateTime, 0, 10) : null;

    // Get the exception date (either start or end date)
    $exceptionDate = $startDate ? $startDate : $endDate;

    // ---- Basic input validation (whitelist) ----
    $allowedTypes = [
        'entry' => ['late-entry', 'missed-entry'],
        'exit'  => ['early-exit', 'missed-exit'],
    ];
    if (!isset($allowedTypes[$exceptionType]) || !in_array($subExceptionType, $allowedTypes[$exceptionType], true)) {
        $validationError = "Invalid exception type selected.";
    }

    // ---- Schedule-based validation (same rules as the JS alert) ----
    // Late entry: cannot be more than 1 hour after the reporting time of that day
    if ($validationError === '' && $subExceptionType === 'late-entry' && $startDateTime) {
        $sched = getScheduleForDate($con, $associatenumber, $startDate);
        if (!$sched || empty($sched['reporting_time'])) {
            $validationError = "No schedule is allocated for you on " . date('d/m/Y', strtotime($startDate)) . ". Please contact your supervisor.";
        } else {
            $limit = strtotime("$startDate {$sched['reporting_time']}") + 3600;
            if (strtotime($startDateTime) > $limit) {
                $validationError = "Late entry is more than 1 hour after your reporting time (" . date('h:i A', strtotime($sched['reporting_time'])) . "). Please apply for leave through the leave portal.";
            }
        }
    }

    // Early exit: cannot be more than 1 hour before the exit time of that day
    if ($validationError === '' && $subExceptionType === 'early-exit' && $endDateTime) {
        $sched = getScheduleForDate($con, $associatenumber, $endDate);
        if (!$sched || empty($sched['exit_time'])) {
            $validationError = "No schedule is allocated for you on " . date('d/m/Y', strtotime($endDate)) . ". Please contact your supervisor.";
        } else {
            $limit = strtotime("$endDate {$sched['exit_time']}") - 3600;
            if (strtotime($endDateTime) < $limit) {
                $validationError = "Early exit is more than 1 hour before your scheduled exit time (" . date('h:i A', strtotime($sched['exit_time'])) . "). Please apply for leave through the leave portal.";
            }
        }
    }

    if ($validationError !== '') {
        $success = false;
    }

    // ---- Duplicate check (parameterised) ----
    if ($validationError === '') {
        $params = [$submittedBy, $exceptionType, $subExceptionType];
        $checkSql = "SELECT status FROM exception_requests
                     WHERE submitted_by = $1
                       AND exception_type = $2
                       AND sub_exception_type = $3";

        if ($startDate) {
            $params[] = $startDate;
            $checkSql .= " AND (start_date_time::date = $" . count($params) . "::date";
            if ($endDate) {
                $params[] = $endDate;
                $checkSql .= " OR end_date_time::date = $" . count($params) . "::date)";
            } else {
                $checkSql .= ")";
            }
        } elseif ($endDate) {
            $params[] = $endDate;
            $checkSql .= " AND end_date_time::date = $" . count($params) . "::date";
        } else {
            // If no dates provided at all, just check type/subtype
            $checkSql .= " AND start_date_time IS NULL AND end_date_time IS NULL";
        }

        $checkSql .= " AND status != 'Rejected'";

        $checkResult = pg_query_params($con, $checkSql, $params);

        if ($checkResult && pg_num_rows($checkResult) > 0) {
            $existingRequest = pg_fetch_assoc($checkResult);
            $existingStatus = $existingRequest['status'];
            $duplicateRequest = true;
            $success = false;
        }
    }

    // Only proceed if validation passed and no existing pending/approved request for same date(s)
    if (!$duplicateRequest && $validationError === '') {

        // Insert the request and get the auto-increment id back
        $sql = "INSERT INTO exception_requests
            (exception_type, sub_exception_type, start_date_time, end_date_time, reason, submitted_on, submitted_by, status)
        VALUES ($1, $2, $3, $4, $5, $6, $7, 'Pending')
        RETURNING id";

        $result = pg_query_params($con, $sql, [
            $exceptionType,
            $subExceptionType,
            $startDateTime,   // null is stored as NULL
            $endDateTime,
            $reason,
            $now,
            $submittedBy
        ]);

        if (!$result) {
            $success = false;
        } else {
            $requestId = pg_fetch_result($result, 0, 'id');   // new exception_requests.id
        }

        if ($success && $email != "") {
            sendEmail("exceptionapply", array(
                "id" => $requestId,
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
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">

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
                                    <?php if ($validationError) { ?>
                                        alert(<?php echo json_encode($validationError); ?>);
                                    <?php } elseif ($duplicateRequest) { ?>
                                        alert(<?php echo json_encode("A similar request already exists with status: " . $existingStatus . ". Only rejected requests can be resubmitted."); ?>);
                                    <?php } else { ?>
                                        alert("An error occurred while submitting the request.");
                                    <?php } ?>
                                </script>
                            <?php } elseif ($_POST && $success) { ?>
                                <script>
                                    // Show success message with ID in a JavaScript alert
                                    alert("Exception request submitted successfully. ID: <?php echo (int)$requestId; ?>");
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
            const associateNumber = "<?php echo htmlspecialchars($associatenumber, ENT_QUOTES, 'UTF-8'); ?>";

            if (!exceptionDate) return true;

            try {
                const response = await fetch(`get_exception_count.php?associate=${encodeURIComponent(associateNumber)}&date=${encodeURIComponent(exceptionDate)}`);
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

            // Format date for display
            const formattedExceptionDate = exception.toLocaleDateString('en-GB', {
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

            // CASE 2: Past dates - allowed today (0) or up to 3 days past
            const daysPast = Math.abs(diffDays);

            if (daysPast <= 3) {
                return {
                    valid: true,
                    message: ''
                };
            }

            return {
                valid: false,
                message: `The selected date (${formattedExceptionDate}) is outside the allowed exception request window. Exception requests must be submitted within 3 calendar days of the exception date.`
            };
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
        document.getElementById('exception').addEventListener('submit', async function(event) {
            event.preventDefault();

            const submitBtn = document.getElementById('submit_button');
            const exceptionType = document.getElementById('exceptionType').value;
            const subExceptionType = document.getElementById('subExceptionType').value;
            const startDateTime = document.getElementById('startDateTime').value;
            const endDateTime = document.getElementById('endDateTime').value;
            const reasonValue = document.getElementById('reason').value;

            // Basic validation
            if (!exceptionType || !subExceptionType || !reasonValue.trim()) {
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

                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                if (canProceed) {
                    // Show loading modal
                    showLoadingModal();
                    // Submit the form (the server re-validates the schedule rules)
                    this.submit();
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
                subExceptionTypeField.style.display = 'block';

                subExceptionType.innerHTML = `
                <option disabled selected>Select sub exception type</option>
                <option value="late-entry">Late Entry</option>
                <option value="missed-entry">Missed Entry (Missed Punch In)</option>
            `;

                startDateTimeField.style.display = 'block';
                document.getElementById('startDateTime').required = true;

            } else if (exceptionType === 'exit') {
                subExceptionTypeField.style.display = 'block';

                subExceptionType.innerHTML = `
                <option disabled selected>Select sub exception type</option>
                <option value="early-exit">Early Exit</option>
                <option value="missed-exit">Missed Exit (Missed Punch Out)</option>
            `;

                endDateTimeField.style.display = 'block';
                document.getElementById('endDateTime').required = true;
            }
        }
    </script>

    <script>
        // Schedule-based checks: reporting_time / exit_time are looked up per selected date
        // (weekday + start_date/end_date) from associate_schedule_v2 via get_schedule.php
        document.addEventListener("DOMContentLoaded", function() {
            const scheduleCache = {};

            function timeToMinutes(timeStr) {
                const [h, m] = timeStr.split(':').map(Number);
                return h * 60 + m;
            }

            function formatMinutesToTime(minutes) {
                const hours = Math.floor(minutes / 60);
                const mins = Math.floor(minutes % 60);
                const period = hours >= 12 ? 'PM' : 'AM';
                const h12 = hours % 12 || 12;
                return `${String(h12).padStart(2, '0')}:${String(mins).padStart(2, '0')} ${period}`;
            }

            function formatDate(dateStr) {
                const [y, m, d] = dateStr.split('-');
                return `${d}/${m}/${y}`;
            }

            // Fetch the schedule for that specific date (cached per date)
            async function getSchedule(date) {
                if (scheduleCache[date]) return scheduleCache[date];
                try {
                    const res = await fetch(`get_schedule.php?date=${encodeURIComponent(date)}`);
                    if (!res.ok) return null;
                    const data = await res.json();
                    scheduleCache[date] = data;
                    return data;
                } catch (e) {
                    console.error('Schedule lookup failed:', e);
                    return null; // the server-side check will still enforce the rule
                }
            }

            function warn(title, text, fieldId) {
                Swal.fire({
                    title: title,
                    text: text,
                    icon: 'warning',
                    confirmButtonColor: '#3085d6'
                });
                document.getElementById(fieldId).value = '';
            }

            async function checkTimes() {
                const sub = document.getElementById('subExceptionType').value;
                const startVal = document.getElementById('startDateTime').value;
                const endVal = document.getElementById('endDateTime').value;

                // ---- Late entry: max 1 hour after reporting time ----
                if (sub === 'late-entry' && startVal) {
                    const [date, time] = startVal.split('T');
                    const s = await getSchedule(date);
                    if (s === null) return;
                    if (document.getElementById('startDateTime').value !== startVal) return; // changed meanwhile

                    if (!s.found || !s.reporting_time) {
                        return warn('No Schedule Found',
                            `You have no schedule allocated on ${formatDate(date)}. Please contact your supervisor.`,
                            'startDateTime');
                    }
                    const selected = timeToMinutes(time);
                    const reporting = timeToMinutes(s.reporting_time);
                    if (selected > reporting + 60) {
                        warn('Late Entry Warning',
                            `You are applying for a late-entry exception on ${formatDate(date)} at ${formatMinutesToTime(selected)}, which is more than 1 hour after your reporting time of ${formatMinutesToTime(reporting)}. Please apply for leave through the leave portal.`,
                            'startDateTime');
                    }
                }

                // ---- Early exit: max 1 hour before exit time ----
                if (sub === 'early-exit' && endVal) {
                    const [date, time] = endVal.split('T');
                    const s = await getSchedule(date);
                    if (s === null) return;
                    if (document.getElementById('endDateTime').value !== endVal) return; // changed meanwhile

                    if (!s.found || !s.exit_time) {
                        return warn('No Schedule Found',
                            `You have no schedule allocated on ${formatDate(date)}. Please contact your supervisor.`,
                            'endDateTime');
                    }
                    const selected = timeToMinutes(time);
                    const exit = timeToMinutes(s.exit_time);
                    if (selected < exit - 60) {
                        warn('Early Exit Warning',
                            `You are applying for an early-exit exception on ${formatDate(date)} at ${formatMinutesToTime(selected)}, which is more than 1 hour before your scheduled exit time of ${formatMinutesToTime(exit)}. Please apply for leave through the leave portal.`,
                            'endDateTime');
                    }
                }
            }

            ['subExceptionType', 'startDateTime', 'endDateTime'].forEach(function(id) {
                document.getElementById(id).addEventListener('change', checkTimes);
            });
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