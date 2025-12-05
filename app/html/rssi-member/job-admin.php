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

    <title>Job Admin Panel</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <style>
        .card-link {
            color: inherit;
            text-decoration: none;
        }

        .card-body {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            height: 100%;
            padding: 2rem 1rem;
        }

        .icon {
            font-size: 60px;
            color: #444444;
            margin-bottom: 1rem;
        }

        .card-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .card-text {
            color: #666;
            font-size: 0.9rem;
        }

        .card {
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e0e0e0;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .pending-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #ffc107;
            color: #000;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Job Admin Panel</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Jobs</a></li>
                    <li class="breadcrumb-item active">Admin Panel</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <div class="container mt-3">
                                <div class="row g-4">

                                    <!-- Job Approval -->
                                    <div class="col-md-4">
                                        <a href="job-approval.php" class="card-link">
                                            <div class="card text-center position-relative">
                                                <div class="card-body">
                                                    <i class="bi bi-check-circle icon"></i>
                                                    <h5 class="card-title mt-2">Job Management</h5>
                                                    <p class="card-text">View and manage job postings and applications, including reviewing details and approving or rejecting listings.</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <!-- Recruiter Management -->
                                    <div class="col-md-4">
                                        <a href="recruiter-management.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-people icon"></i>
                                                    <h5 class="card-title mt-2">Recruiter Management</h5>
                                                    <p class="card-text">Manage existing recruiters and their companies.</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <!-- Add New Recruiter -->
                                    <div class="col-md-4">
                                        <a href="recruiter-add.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-person-plus icon"></i>
                                                    <h5 class="card-title mt-2">Add Recruiter</h5>
                                                    <p class="card-text">Register new recruiters and companies to post jobs.</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <!-- Add Job on Behalf -->
                                    <div class="col-md-4">
                                        <a href="job-add.php" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-briefcase icon"></i>
                                                    <h5 class="card-title mt-2">Add Job</h5>
                                                    <p class="card-text">Create job postings on behalf of recruiters.</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <!-- Settings -->
                                    <div class="col-md-4">
                                        <a href="#" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-gear icon"></i>
                                                    <h5 class="card-title mt-2">Settings</h5>
                                                    <p class="card-text">Configure job posting rules and notifications.</p>
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