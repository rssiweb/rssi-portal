<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$result = pg_query($con, "SELECT s.family_id, s.contact, s.parent_name, sd.student_name, sd.age, sd.gender, sd.grade, s.timestamp, s.surveyor_id, s.address, rm.fullname, s.earning_source, s.other_earning_source_input, sd.already_going_school, sd.school_type, sd.already_coaching, sd.coaching_name
        FROM survey_data s 
        LEFT JOIN student_data sd ON s.family_id = sd.family_id
        JOIN rssimyaccount_members rm ON s.surveyor_id = rm.associatenumber
        ORDER BY s.timestamp DESC");
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
// Check if the query was successful
if ($result) {
    $serialNumber = 1; // Initialize serial number for table rows
} else {
    // Output error message if query fails
    echo "Error executing query: " . pg_last_error($con);
    exit; // Terminate further execution if the query fails
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

    <title>User log</title>

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
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>User log</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">User log</li>
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

                            <?php echo '
                    <div class="container">
                    <div class="table-responsive">
                    <table class="table" id="table-id">
                        <thead>
                            <tr>
                                <th>SL</th>
                                                        <th>Family ID</th>
                                                        <th>Address</th>
                                                        <th>Contact</th>
                                                        <th>Parent Name</th>
                                                        <th>Student Name</th>
                                                        <th>Age</th>
                                                        <th>Gender</th>
                                                        <th>Grade</th>
                                                        <th>Timestamp</th>
                                                        <th>Misc</th>
                                                        <th>Surveyor Name</th>
                            </tr>
                        </thead>' ?>
                            <tbody>
                                <?php while ($row = pg_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo $serialNumber++; ?></td>
                                        <td><?php echo $row["family_id"]; ?></td>
                                        <td>
                                            <span class="short-address">
                                                <?php echo strlen($row["address"]) > 30 ? substr($row["address"], 0, 30) . "..." : $row["address"]; ?>
                                                <span class="full-address" style="display: none;">
                                                    <?php echo $row["address"]; ?>
                                                </span>
                                                <a href="#" class="more-link">more</a>
                                            </span>
                                        </td>
                                        <td><?php echo $row["contact"]; ?></td>
                                        <td><?php echo $row["parent_name"]; ?></td>
                                        <td><?php echo $row["student_name"]; ?></td>
                                        <td><?php echo $row["age"]; ?></td>
                                        <td><?php echo $row["gender"]; ?></td>
                                        <td><?php echo $row["grade"]; ?></td>
                                        <td><?php echo date('d/m/Y h:i A', strtotime($row["timestamp"])); ?></td>
                                        <td><a href="#" class="misc-link" data-bs-toggle="modal" data-bs-target="#miscModal<?php echo $row["family_id"]; ?>">View Details</a></td>
                                        <td><?php echo $row["fullname"]; ?></td>
                                    </tr>

                                    <!-- Modal for "Misc" data -->
                                    <div class="modal fade" id="miscModal<?php echo $row["family_id"]; ?>" tabindex="-1" aria-labelledby="miscModalLabel<?php echo $row["family_id"]; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="miscModalLabel<?php echo $row["family_id"]; ?>">Miscellaneous Data</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Student Name: <?php echo $row["student_name"] . '&nbsp;(' . $row["family_id"] . ')'; ?></p>
                                                    <p>Family Earning Source:
                                                        <?php echo $row["earning_source"] == "other" ? $row["other_earning_source_input"] : $row["earning_source"]; ?>
                                                    </p>
                                                    <p>Already Going to School: <?php echo $row["already_going_school"]; ?></p>
                                                    <p>School Type: <?php echo $row["school_type"]; ?></p>
                                                    <p>Already Coaching: <?php echo $row["already_coaching"]; ?></p>
                                                    <p>Coaching Name: <?php echo $row["coaching_name"]; ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </div>
                    </div>
                </div>
            </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
    <script>
        $(document).ready(function() {
            // Toggle full address visibility on "more" link click
            $('.more-link').click(function(e) {
                e.preventDefault();
                var shortAddress = $(this).siblings('.short-address');
                var fullAddress = $(this).siblings('.full-address');
                if (fullAddress.is(':visible')) {
                    shortAddress.show();
                    fullAddress.hide();
                    $(this).text('more');
                } else {
                    shortAddress.hide();
                    fullAddress.show();
                    $(this).text('less');
                }
            });
        });
    </script>

</body>

</html>