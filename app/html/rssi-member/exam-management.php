<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
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

    <title>Exam Management</title>

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
            <h1>Exam Management</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Academic</a></li>
                    <li class="breadcrumb-item"><a href="#">Exam Management</a></li>
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
                                        <a href="exam_create.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-plus-circle-dotted icon"></i>
                                                    <h5 class="card-title mt-3">Create Exam</h5>
                                                    <p class="text-muted text-center small mt-2">Set up new examinations with schedules and details</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="exam_allotment.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-person-check icon"></i>
                                                    <h5 class="card-title mt-3">Exam Allotment</h5>
                                                    <p class="text-muted text-center small mt-2">Assign invigilators and manage exam responsibilities</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="exam_summary_report.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-file-text icon"></i>
                                                    <h5 class="card-title mt-3">Exam Summary Report</h5>
                                                    <p class="text-muted text-center small mt-2">Comprehensive overview of all examination activities</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="progress_curve.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-graph-up icon"></i>
                                                    <h5 class="card-title mt-3">Students' Progress Curve</h5>
                                                    <p class="text-muted text-center small mt-2">Track and analyze student performance trends</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="reexam.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-arrow-repeat icon"></i>
                                                    <h5 class="card-title mt-3">Re-examination Form</h5>
                                                    <p class="text-muted text-center small mt-2">Process applications for supplementary examinations</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <div class="col-md-4">
                                        <a href="reexam_record.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-archive icon"></i>
                                                    <h5 class="card-title mt-3">Re-examination Records</h5>
                                                    <p class="text-muted text-center small mt-2">View history and status of all re-examinations</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="append-students.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-person-plus icon"></i>
                                                    <h5 class="card-title mt-3">Add Students (Post Exam Creation)</h5>
                                                    <p class="text-muted text-center small mt-2">Enroll additional students after exam setup</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="result-scheduler.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-calendar-event icon"></i>
                                                    <h5 class="card-title mt-3">Result Scheduler</h5>
                                                    <p class="text-muted text-center small mt-2">Plan and automate result declaration timelines</p>
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

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  

</body>

</html>