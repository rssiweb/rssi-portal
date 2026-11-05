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

    // ---- Final enforcement: same validator the live-check API uses ----
    $check = validateExceptionRequest($con, $associatenumber, $exceptionType, $subExceptionType, $startDateTime, $endDateTime);
    if (!$check['valid']) {
        $validationError = $check['message'];
        $success = false;
    } else {
        // Keep only the date-time relevant to the exception type
        // (a hidden field may still hold a stale value)
        if ($exceptionType === 'entry') {
            $endDateTime = null;
        } else {
            $startDateTime = null;
        }
    }

    // Extract just the date portion (YYYY-MM-DD) for comparison
    $startDate = $startDateTime ? substr($startDateTime, 0, 10) : null;
    $endDate = $endDateTime ? substr($endDateTime, 0, 10) : null;

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
                                            <option value="" disabled selected>Select exception type</option>
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
        // Function to show / hide loading modal
        function showLoadingModal() {
            myModal.show();
        }

        function hideLoadingModal() {
            myModal.hide();
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

        // ------------------------------------------------------------------
        // Live rule check: every call goes to the server, nothing is cached.
        // ------------------------------------------------------------------
        let ruleCheckSeq = 0; // used to ignore out-of-order responses

        function getFormState() {
            return {
                exceptionType: document.getElementById('exceptionType').value,
                subType: document.getElementById('subExceptionType').value,
                start: document.getElementById('startDateTime').value,
                end: document.getElementById('endDateTime').value
            };
        }

        // Returns the server's verdict, {stale: true} if a newer check superseded this one,
        // or null if the request itself failed (the server re-validates on submit anyway).
        async function checkRule() {
            const seq = ++ruleCheckSeq;
            const s = getFormState();
            const params = new URLSearchParams({
                exception_type: s.exceptionType,
                sub_type: s.subType,
                start: s.start,
                end: s.end
            });

            try {
                const res = await fetch('check_exception_rule.php?' + params.toString(), {
                    cache: 'no-store',
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (seq !== ruleCheckSeq) return {
                    stale: true
                };
                return data;
            } catch (e) {
                console.error('Rule check failed:', e);
                return null;
            }
        }

        const RULE_TITLES = {
            window: 'Invalid Exception Date',
            invalid: 'Invalid Date and Time',
            no_schedule: 'No Schedule Found',
            late: 'Late Entry Warning',
            early: 'Early Exit Warning',
            unauthorized: 'Session Expired'
        };

        function showRuleError(data) {
            return Swal.fire({
                title: RULE_TITLES[data.code] || 'Request Not Allowed',
                text: data.message,
                icon: (data.code === 'window' || data.code === 'invalid') ? 'error' : 'warning',
                confirmButtonColor: '#3085d6'
            });
        }

        // Clear the date-time field that belongs to the selected exception type
        function clearRelevantField() {
            const type = document.getElementById('exceptionType').value;
            if (type === 'entry') document.getElementById('startDateTime').value = '';
            if (type === 'exit') document.getElementById('endDateTime').value = '';
        }

        // Runs on EVERY change of sub type / entry date-time / exit date-time
        async function onFormInputChange() {
            const s = getFormState();
            const relevant = s.exceptionType === 'entry' ? s.start : s.end;
            if (!s.exceptionType || !s.subType || !relevant) return; // nothing to check yet

            const data = await checkRule();
            if (!data || data.stale || data.valid || data.code === 'incomplete') return;

            clearRelevantField();
            showRuleError(data);
        }

        ['subExceptionType', 'startDateTime', 'endDateTime'].forEach(function(id) {
            document.getElementById(id).addEventListener('change', onFormInputChange);
        });

        // Override form submission
        document.getElementById('exception').addEventListener('submit', async function(event) {
            event.preventDefault();
            const form = this;

            const submitBtn = document.getElementById('submit_button');
            const s = getFormState();
            const reasonValue = document.getElementById('reason').value;

            // Basic validation
            if (!s.exceptionType || !s.subType || !reasonValue.trim()) {
                Swal.fire({
                    title: 'Incomplete Form',
                    text: 'Please fill in all required fields.',
                    icon: 'warning',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            // Get the exception date (entry uses start, exit uses end)
            const relevantValue = s.exceptionType === 'entry' ? s.start : s.end;
            const exceptionDate = relevantValue ? relevantValue.split('T')[0] : null;

            // Show loading state on button
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Checking...';
            submitBtn.disabled = true;

            try {
                // First check: live rule check against current server data
                const rule = await checkRule();
                if (rule && !rule.stale && !rule.valid) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    if (rule.code !== 'incomplete') clearRelevantField();
                    await showRuleError(rule);
                    return;
                }

                // Second check: exception count for the month
                const canProceed = await checkExceptionCount(exceptionDate);

                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                if (canProceed) {
                    showLoadingModal();
                    // The server runs the same validator again on POST
                    form.submit();
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

            // Remove required attribute and stale values from date-time fields
            document.getElementById('startDateTime').required = false;
            document.getElementById('endDateTime').required = false;
            document.getElementById('startDateTime').value = '';
            document.getElementById('endDateTime').value = '';

            // Clear sub-exception type options
            subExceptionType.innerHTML = '';

            if (exceptionType === 'entry') {
                subExceptionTypeField.style.display = 'block';

                subExceptionType.innerHTML = `
                <option value="" disabled selected>Select sub exception type</option>
                <option value="late-entry">Late Entry</option>
                <option value="missed-entry">Missed Entry (Missed Punch In)</option>
            `;

                startDateTimeField.style.display = 'block';
                document.getElementById('startDateTime').required = true;

            } else if (exceptionType === 'exit') {
                subExceptionTypeField.style.display = 'block';

                subExceptionType.innerHTML = `
                <option value="" disabled selected>Select sub exception type</option>
                <option value="early-exit">Early Exit</option>
                <option value="missed-exit">Missed Exit (Missed Punch Out)</option>
            `;

                endDateTimeField.style.display = 'block';
                document.getElementById('endDateTime').required = true;
            }
        }
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