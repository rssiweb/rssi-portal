<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

if ($_POST) {
    $ticket_id = uniqid();
    $short_description = $_POST['short_description'];
    $long_description = $_POST['long_description'];
    $severity = $_POST['severity'];
    $raised_for = isset($_POST['associates']) ? json_encode($_POST['associates']) : '[]'; // Serialize the array to JSON
    $timestamp = date('Y-m-d H:i:s');

    // Upload and insert passbook page if provided
    $doclink = null;
    if (!empty($_FILES['upload_file']['name'])) {
        $upload_file = $_FILES['upload_file'];
        $filename = $ticket_id . "_" . time();
        $parent = '19j8P2pM1kSy3Dc_Clr-GcQlYCl5ZMAiQ';
        $doclink = uploadeToDrive($upload_file, $parent, $filename);
    }

    $query = "INSERT INTO support (ticket_id, short_description, long_description, upload_file, severity, raised_by, raised_for, timestamp)
              VALUES ('$ticket_id', '$short_description', '$long_description', '$doclink', '$severity', '$associatenumber', '$raised_for', '$timestamp')";

    $result = pg_query($con, $query);
    $cmdtuples = pg_affected_rows($result);
}

// Query to fetch associates and students
$query = "SELECT associatenumber AS id, fullname AS name FROM rssimyaccount_members where filterstatus='Active'
          UNION
          SELECT student_id AS id, studentname AS name FROM rssimyprofile_student where filterstatus='Active'";

$result = pg_query($con, $query);

$results = [];
while ($row = pg_fetch_assoc($result)) {
    $results[] = ['id' => htmlspecialchars($row['id']), 'text' => htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['id']) . ")"];
}
?>

<!doctype html>
<html lang="en">

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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Support360</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

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
    </style>

</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Support360</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Support360</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <?php if ($role == 'Admin') { ?>
                                <?php if (@$policyid != null && @$cmdtuples == 0) { ?>

                                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>ERROR: Oops, something wasn't right.</span>
                                    </div>
                                <?php
                                } else if (@$cmdtuples == 1) { ?>

                                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-check2-circle"></i>
                                        <span>Database has been updated successfully for policy id <?php echo @$ticket_id ?>.</span>
                                    </div>
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                                <?php } ?>
                            <?php } ?>

                            <?php if ($role == 'Admin') { ?>
                                <div class="container my-5">
                                    <div class="row justify-content-center">
                                        <div class="col-lg-8">
                                            <form method="POST" enctype="multipart/form-data">
                                                <!-- Short Description -->
                                                <div class="mb-3">
                                                    <label for="short_description" class="form-label">Short Description</label>
                                                    <input type="text" class="form-control" id="short_description" name="short_description" required placeholder="Enter a brief summary of the issue">
                                                </div>

                                                <!-- Long Description -->
                                                <div class="mb-3">
                                                    <label for="long_description" class="form-label">Long Description</label>
                                                    <textarea class="form-control" id="long_description" name="long_description" rows="4" required placeholder="Provide a detailed description of the issue"></textarea>
                                                </div>

                                                <!-- Severity -->
                                                <div class="mb-3">
                                                    <label for="severity" class="form-label">Severity</label>
                                                    <select class="form-select" id="severity" name="severity" required>
                                                        <option value="">Select Severity Level</option>
                                                        <option value="Low">Low</option>
                                                        <option value="Medium">Medium</option>
                                                        <option value="High">High</option>
                                                        <option value="Critical">Critical</option>
                                                    </select>
                                                </div>

                                                <!-- Raised For -->
                                                <div class="mb-3">
                                                    <label for="associates" class="form-label required">Select Associates/Students</label>
                                                    <select id="associates" name="associates[]" class="form-control" multiple="multiple" required>
                                                        <!-- Options will be populated dynamically via PHP -->
                                                    </select>
                                                </div>
                                                <!-- Upload File -->
                                                <div class="mb-3">
                                                    <label for="upload_file" class="form-label">Upload File</label>
                                                    <input class="form-control" type="file" id="upload_file" name="upload_file" accept=".jpg,.jpeg,.png,.pdf">
                                                </div>

                                                <!-- Status -->
                                                <!-- <div class="mb-3">
                                                    <label for="status" class="form-label">Status</label>
                                                    <select class="form-select" id="status" name="status" required>
                                                        <option value="">Select Status</option>
                                                        <option value="Open">Open</option>
                                                        <option value="In Progress">In Progress</option>
                                                        <option value="Closed">Closed</option>
                                                    </select>
                                                </div> -->

                                                <div class="text-center">
                                                    <button type="submit" class="btn btn-primary">Submit</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>

                        </div>
                    </div>
                </div><!-- End Reports -->

            </div>
        </section>

    </main><!-- End #main -->

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for the associates input field
            $('#associates').select2({
                data: <?php echo json_encode($results); ?>,
                tags: true,
                placeholder: "Type and select associates/students",
                allowClear: true,
                tags: false
            });
        });
    </script>
</body>

</html>