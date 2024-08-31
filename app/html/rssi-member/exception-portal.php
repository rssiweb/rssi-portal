<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");

// Ensure user is logged in
if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Form validation logic, if any
validation();

// Get current timestamp
$now = date('Y-m-d H:i:s');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Generate a unique ID for the request
    $id = uniqid(); // Generate a unique ID with 'REQ_' prefix

    // Retrieve form data
    $exceptionType = $_POST['exceptionType'];
    $startDateTime = !empty($_POST['startDateTime']) ? $_POST['startDateTime'] : null;
    $endDateTime = !empty($_POST['endDateTime']) ? $_POST['endDateTime'] : null;
    $reason = $_POST['reason'];
    $submittedBy = $associatenumber; // Replace with actual user information (e.g., session data)

    $success = true;

    // Prepare SQL statement for insertion
    $sql = "INSERT INTO exception_requests (id, exception_type, start_date_time, end_date_time, reason, submitted_on, submitted_by) 
    VALUES ('$id', '$exceptionType', " .
        ($startDateTime ? "'$startDateTime'" : "NULL") . ", " .
        ($endDateTime ? "'$endDateTime'" : "NULL") . ", " .
        "'$reason', '$now', '$submittedBy')";

    // Execute the SQL query
    $result = pg_query($con, $sql);

    // Check if the insertion was successful
    if (!$result) {
        $success = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exception Portal Form</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

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
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Exception Portal</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">My Services</a></li>
                    <li class="breadcrumb-item active">Exception Portal</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <?php if ($_POST && !$success) { ?>
                                <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span>An error occurred while submitting the request.</span>
                                </div>
                            <?php } elseif ($_POST && $success) { ?>
                                <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-check2-circle"></i>
                                    <span>Exception request submitted successfully. ID: <?php echo @$id ?>.</span>
                                </div>
                                <script>
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                </script>
                            <?php } ?>

                            <div class="container mt-4">
                                <form name="exception" id="exception" method="post" action="">
                                    <div class="mb-3">
                                        <label for="exceptionType" class="form-label">Exception Type <span class="text-danger">*</span></label>
                                        <select class="form-select" id="exceptionType" name="exceptionType" required onchange="toggleDateTimeFields()">
                                            <option value="" disabled selected>Select exception type</option>
                                            <option value="late-entry">Late Entry</option>
                                            <option value="early-exit">Early Exit</option>
                                        </select>
                                    </div>

                                    <!-- Start Date-Time Field -->
                                    <div class="mb-3" id="startDateTimeField" style="display: none;">
                                        <label for="startDateTime" class="form-label">Late Entry Date-Time <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="startDateTime" name="startDateTime" required>
                                    </div>

                                    <!-- End Date-Time Field -->
                                    <div class="mb-3" id="endDateTimeField" style="display: none;">
                                        <label for="endDateTime" class="form-label">Early Exit Date-Time <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="endDateTime" name="endDateTime" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Enter reason for exception" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>
    <!-- End #main -->

    <script>
        function toggleDateTimeFields() {
            const exceptionType = document.getElementById('exceptionType').value;
            const startDateTimeField = document.getElementById('startDateTimeField');
            const endDateTimeField = document.getElementById('endDateTimeField');

            // Hide both fields initially
            startDateTimeField.style.display = 'none';
            endDateTimeField.style.display = 'none';

            // Show the appropriate field based on the selected value
            if (exceptionType === 'late-entry') {
                startDateTimeField.style.display = 'block';
                document.getElementById('startDateTime').required = true; // Make field required
                document.getElementById('endDateTime').required = false; // Remove required from the other field
            } else if (exceptionType === 'early-exit') {
                endDateTimeField.style.display = 'block';
                document.getElementById('endDateTime').required = true; // Make field required
                document.getElementById('startDateTime').required = false; // Remove required from the other field
            }
        }
    </script>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
</body>

</html>