<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}
validation();
?>
<!DOCTYPE html>
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

    <title>AttendX</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <style>
        .card-link {
            color: inherit;
            /* Inherit color from parent */
        }

        .card-body {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .icon {
            font-size: 60px;
            color: #444444;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>AttendX</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">AttendX</a></li>
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

                            <div class="container mt-5">
                                <div class="row">
                                    <div class="col-md-4">
                                        <a href="scan.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-qr-code-scan icon"></i>
                                                    <h5 class="card-title mt-3">QR Attendance Scanner</h5>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="remote_attendance.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-globe2 icon"></i>
                                                    <h5 class="card-title mt-3">Remote Attendance</h5>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="in_out_tracker.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-speedometer2 icon"></i>
                                                    <h5 class="card-title mt-3">Daily In-Out Report</h5>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="#" class="card-link" data-bs-toggle="modal" data-bs-target="#attendanceOptionsModal" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-calendar-check icon"></i>
                                                    <h5 class="card-title mt-3">Monthly Attendance Report</h5>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="sas.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-clipboard-check icon"></i>
                                                    <h5 class="card-title mt-3">Student Attendance Summary (SAS)</h5>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <div class="col-md-4">
                                        <a href="attendance-analytics.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-graph-up-arrow icon"></i>
                                                    <h5 class="card-title mt-3">Attendance Analytics Dashboard</h5>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div><!-- End Reports -->
        </section>
    </main><!-- End #main -->

    <!-- Modal -->
    <div class="modal fade" id="attendanceOptionsModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Monthly Attendance Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-body">
                        <a href="monthly_attd_report.php" class="btn btn-primary btn-block mb-2">Student's Monthly Attendance</a>
                        <a href="monthly_attd_report_associate.php" class="btn btn-primary btn-block">Teacher's Monthly Attendance</a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  <script src="../assets_new/js/text-refiner.js"></script>

</body>

</html>