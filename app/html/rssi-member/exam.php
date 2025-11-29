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
<html>

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
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Examination</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

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

</head>
<style>
    @media (max-width:767px) {

        #cw,
        #cw1,
        #cw2,
        #cw3 {
            width: 100% !important;
        }

    }

    #cw {
        width: 50%;
    }

    #cw1 {
        width: 60%;
    }

    #cw2 {
        width: 25%;
    }

    #cw3 {
        width: 20%;
    }
</style>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Examination</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Acadamis</a></li>
                    <li class="breadcrumb-item active">Examination</li>
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

                            <div class="container">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="card mb-4">
                                            <div class="card-body">
                                                <h5 class="card-title">Exam Description</h5>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Name of Exam</th>
                                                                <th>Month</th>
                                                                <th>Max. Marks</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>First Term Exam</td>
                                                                <td>July</td>
                                                                <td>50</td>
                                                            </tr>
                                                            <tr>
                                                                <td>Half Yearly Exam</td>
                                                                <td>November</td>
                                                                <td>50</td>
                                                            </tr>
                                                            <tr>
                                                                <td>Annual Exam</td>
                                                                <td>March</td>
                                                                <td>100</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <p>
                                                    The written test will be descriptive as per the Board/School exam pattern.
                                                    The concerned subject teacher will set the question paper.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="card mb-4">
                                            <div class="card-body">
                                                <h5 class="card-title">Exam Resources</h5>
                                                <ul class="list-group">
                                                    <li class="list-group-item">
                                                        <a href="https://drive.google.com/file/d/1q-7GP-0qV50Dw6BJyg9YVt4adrIYWxm3/view" target="_blank">Guidelines for Offline Exam Invigilators</a>
                                                    </li>
                                                    <li class="list-group-item">
                                                        <a href="https://drive.google.com/file/d/11NrSzM4EJvoVA16eQ73qtzpANZEl80iA/view" target="_blank">User Guide for Offline Examiners</a>
                                                    </li>
                                                    <li class="list-group-item">
                                                        <a href="https://drive.google.com/file/d/1Dr3SOmKUPe7gjaQg_V1Y7VAwufrOWJdj/view" target="_blank">Guidelines for Online Exam Invigilators</a>
                                                    </li>
                                                    <li class="list-group-item">
                                                        <a href="https://drive.google.com/file/d/13cH8Rd4aPYHPe0ltzQzQDNFAH1GRTruY/view" target="_blank">User Guide for Online Examiners</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Exam Details</h5>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Question Portal</th>
                                                                <!-- <th>Evaluation Path</th>
                                                                <th>Marks Upload</th> -->
                                                                <th>Examination Results</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>
                                                                    <a href="question.php">Question Papers</a>
                                                                </td>
                                                                <!-- <td>
                                                                    <a href="https://docs.google.com/spreadsheets/d/1d1dfSWWh_aM7Eq2rZc3ZxXJ2uMpqKfchy0ciNSF4KxU/edit?usp=sharing" target="_blank">Homework, QT Exam</a>
                                                                </td>
                                                                <td>
                                                                    <a href="https://docs.google.com/spreadsheets/d/1mjVN9VET3_ToFGDWRSSl7dAZO1sH1kNXgI66qPWfco8/edit?usp=sharing" target="_blank">Grade Summary Sheet</a>
                                                                </td> -->
                                                                <td>
                                                                    <p>
                                                                        <a href="https://www.rssi.in/result-portal" target="_blank">View Result</a>
                                                                    </p>
                                                                    <p>
                                                                        <a href="print-result.php" target="_blank">Generate Report Card</a>
                                                                    </p>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
</body>

</html>