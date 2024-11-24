<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
// Fetch all records from the associate_schedule table
$query = "
    SELECT s.*, m.fullname 
    FROM associate_schedule s
    INNER JOIN rssimyaccount_members m ON s.associate_number = m.associatenumber
    order by timestamp desc
";
$result = pg_query($con, $query);

if (!$result) {
    die("Error executing query: " . pg_last_error($con));
}

pg_close($con); // Close the connection when done
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

    <title>View Shift</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>View Shift</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Workforce Management</a></li>
                    <li class="breadcrumb-item active">View Shift</li>
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
                            <div class="table-responsive">
                                <table id="scheduleTable" class="table">
                                    <thead>
                                        <tr>
                                            <th>Associate Number</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Reporting Time</th>
                                            <th>Exit Time</th>
                                            <th>Timestamp</th>
                                            <th>Submitted By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = pg_fetch_assoc($result)) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['fullname']); ?> (<?php echo htmlspecialchars($row['associate_number']); ?>)</td>
                                                <td><?php echo date("d/m/Y", strtotime($row['start_date'])); ?></td>
                                                <td><?php echo date("d/m/Y", strtotime($row['end_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['reporting_time']); ?></td>
                                                <td><?php echo htmlspecialchars($row['exit_time']); ?></td>
                                                <td><?php echo date("d/m/Y H:i:s", strtotime($row['timestamp'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['submittedby']); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <!-- Bootstrap JS and Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($result)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#scheduleTable').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>