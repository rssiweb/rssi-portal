<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}
$query = "SELECT count(serial_number) AS onboarding_left FROM onboarding WHERE onboarding_flag IS NULL";
$result = pg_query($con, $query);
$onboarding_left = pg_fetch_result($result, 0, 'onboarding_left');

$query = "SELECT count(id) AS exit_left FROM associate_exit WHERE exit_flag IS NULL";
$result = pg_query($con, $query);
$exit_left = pg_fetch_result($result, 0, 'exit_left');
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Process Hub</title>

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
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>

<body>

    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Process Hub</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Process Hub</li>
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

                            <div class="row g-3">
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h4 class="card-title">Onboarding Process</h4>
                                            <span class="badge rounded-pill text-bg-danger position-absolute top-0 end-0 me-2 mt-2">Pending: <?php echo $onboarding_left ?></span>
                                            <p class="card-text">Welcome to the RSSI Onboarding Portal - your one-stop destination for a smooth and efficient onboarding process.</p>
                                            <a href="onboarding.php" target="_blank" class="btn btn-success btn-sm">Launch <i class="bi bi-box-arrow-up-right"></i></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h4 class="card-title">Exit Process</h4>
                                            <span class="badge rounded-pill text-bg-danger position-absolute top-0 end-0 me-2 mt-2">Pending: <?php echo $exit_left ?></span>
                                            <p class="card-text">Efficiently manage the separation of associates with the RSSI Exit Process. Conduct exit interviews, collect company property, provide benefit information, and complete necessary formalities in one place.</p>
                                            <a href="exit.php" target="_blank" class="btn btn-danger btn-sm">Launch <i class="bi bi-box-arrow-up-right"></i></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h4 class="card-title">Visitor Registration</h4>
                                            <p class="card-text">Welcome to the RSSI Visitor Registration Portal. This is your one-stop solution to efficiently register and track the details of visitors to our premises.</p>
                                            <a href="#" class="btn btn-warning btn-sm disabled" aria-disabled="true">Launch <i class="bi bi-box-arrow-up-right"></i></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h4 class="card-title">Student Registration</h4>
                                            <p class="card-text">Welcome to the RSSI Student Admission Portal. Here, you can easily manage student data and track their admission process.</p>
                                            <a href="#" class="btn btn-primary btn-sm disabled" aria-disabled="true">Launch <i class="bi bi-box-arrow-up-right"></i></a>
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