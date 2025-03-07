<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $associate_number = $_POST['associate_number'];
    $start_date = $_POST['start_date'];
    // $end_date = $_POST['end_date'];
    $reporting_time = $_POST['reporting_time'];
    $exit_time = $_POST['exit_time_s'];
    $timestamp = date('Y-m-d H:i:s');
    $id = uniqid(); // Generate unique ID in PHP
    $submittedBy = $associatenumber; // Use the logged-in user's identifier for submission tracking

    // Prepare SQL query
    $query = "INSERT INTO associate_schedule (id, associate_number, start_date, reporting_time, exit_time, timestamp, submittedby)
              VALUES ($1, $2, $3, $4, $5, $6, $7)";

    // Execute the query
    $result = pg_query_params($con, $query, [
        $id,
        $associate_number,
        $start_date,
        // $end_date,
        $reporting_time,
        $exit_time,
        $timestamp,
        $submittedBy
    ]);

    $cmdtuples = pg_affected_rows($result);
}

// Close the database connection
pg_close($con);
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

    <title>Shift Planner</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Shift Planner</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Workforce Management</a></li>
                    <li class="breadcrumb-item active">Shift Planner</li>
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
                            <?php if (@$id != null && @$cmdtuples == 0) { ?>

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
                                    <span>Schedule successfully created. The transaction ID is <?php echo htmlspecialchars($id); ?>.</span>
                                </div>
                                <script>
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                </script>
                            <?php } ?>
                            <div class="container mt-4">
                                <form action="#" id="roster" method="post">

                                    <!-- Associate Number -->
                                    <div class="row mb-3">
                                        <label for="associate_number" class="col-sm-3 col-form-label">Associate Number</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" id="associate_number" name="associate_number" placeholder="Enter Associate Number" required>
                                        </div>
                                    </div>

                                    <!-- Start Date -->
                                    <div class="row mb-3">
                                        <label for="start_date" class="col-sm-3 col-form-label">Start Date</label>
                                        <div class="col-sm-9">
                                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                                        </div>
                                    </div>

                                    <!-- End Date -->
                                    <!-- <div class="row mb-3">
                                        <label for="end_date" class="col-sm-3 col-form-label">End Date</label>
                                        <div class="col-sm-9">
                                            <input type="date" class="form-control" id="end_date" name="end_date">
                                        </div>
                                    </div> -->

                                    <!-- Reporting Time -->
                                    <div class="row mb-3">
                                        <label for="reporting_time" class="col-sm-3 col-form-label">Reporting Time</label>
                                        <div class="col-sm-9">
                                            <input type="time" class="form-control" id="reporting_time" name="reporting_time" required>
                                        </div>
                                    </div>

                                    <!-- Exit Time -->
                                    <div class="row mb-3">
                                        <label for="exit_time_s" class="col-sm-3 col-form-label">Exit Time</label>
                                        <div class="col-sm-9">
                                            <input type="time" class="form-control" id="exit_time_s" name="exit_time_s" required>
                                        </div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="text-end">
                                        <button type="submit" name="search_by_id" class="btn btn-primary">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <!-- Bootstrap JS and Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
</body>

</html>