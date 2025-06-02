<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
$query = "SELECT
    (SELECT COUNT(serial_number) FROM onboarding WHERE onboarding_flag IS NULL) AS onboarding_left,
    NULL AS exit_left,
    rssimyaccount_members.fullname,
    rssimyaccount_members.associatenumber,
    rssimyaccount_members.doj,
    rssimyaccount_members.effectivedate,
    rssimyaccount_members.remarks,
    rssimyaccount_members.photo,
    rssimyaccount_members.engagement,
    rssimyaccount_members.position,
    rssimyaccount_members.depb,
    rssimyaccount_members.filterstatus,
    'onboarding' AS process_type
FROM rssimyaccount_members
WHERE rssimyaccount_members.associatenumber IN (
    SELECT onboarding_associate_id FROM onboarding WHERE onboarding_flag IS NULL
)
UNION ALL
SELECT
    NULL AS onboarding_left,
    (SELECT COUNT(id) FROM associate_exit WHERE exit_flag IS NULL) AS exit_left,
    rssimyaccount_members.fullname,
    rssimyaccount_members.associatenumber,
    rssimyaccount_members.doj,
    rssimyaccount_members.effectivedate,
    rssimyaccount_members.remarks,
    rssimyaccount_members.photo,
    rssimyaccount_members.engagement,
    rssimyaccount_members.position,
    rssimyaccount_members.depb,
    rssimyaccount_members.filterstatus,
    'exit' AS process_type
FROM rssimyaccount_members
WHERE rssimyaccount_members.associatenumber IN (
    SELECT exit_associate_id FROM associate_exit WHERE exit_flag IS NULL
)";

$result = pg_query($con, $query);
$resultArr = pg_fetch_all($result);

if ($resultArr > 0) {
    $onboarding_left = $resultArr[0]['onboarding_left'] ?? 0;
    $exit_left = $resultArr[0]['exit_left'] ?? 0;
} else {
    // Error handling if the query fails
    $onboarding_left = 0;
    $exit_left = 0;
}


$query_admission = "SELECT studentname, emailaddress, contact, preferredbranch, student_id, doa, module,
    'admission' AS process_type,
    (SELECT COUNT(*) FROM rssimyprofile_student WHERE filterstatus IS NULL) AS row_count
FROM rssimyprofile_student
WHERE filterstatus IS NULL";

$result_admission = pg_query($con, $query_admission);
$resultArr_admission = pg_fetch_all($result_admission);

if ($resultArr_admission > 0) {
    $admission_left = $resultArr_admission[0]['row_count'] ?? 0;
} else {
    // Error handling if the query fails
    $admission_left = 0;
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

    <title>Process Hub</title>

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
</head>

<body>
<?php include 'inactive_session_expire_check.php'; ?>
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
                                            <span class="badge rounded-pill text-bg-danger position-absolute top-0 end-0 me-2 mt-2">
                                                Pending: <?php echo $onboarding_left; ?>
                                            </span>
                                            <p class="card-text">
                                                Welcome to the RSSI Onboarding Portal - your one-stop destination for a smooth and efficient onboarding process.
                                            </p>
                                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#myModal">
                                                Launch <i class="bi bi-box-arrow-up-right"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal -->
                                <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel">Onboarding Process</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <?php echo '
                                                <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                        <th scope="col">Associate number</th>
                                                        <th scope="col">Name</th>
                                                        <th scope="col">Date of joining</th>    
                                                        <th scope="col">Engagement</th>
                                                        <th scope="col">Deputed Branch</th>
                                                        <th scope="col">Action</th>
                                                        </tr>
                                                    </thead>' ?>
                                                <?php if (sizeof($resultArr) > 0) { ?>
                                                <?php
                                                    foreach ($resultArr as $array) {
                                                        if ($array['process_type'] === 'onboarding') {
                                                            echo '
                                                        <tr>
                                                            <td>' . $array['associatenumber'] . '</td>
                                                            <td>' . $array['fullname'] . '</td>
                                                            <td>' . date("d/m/Y", strtotime($array['doj'])) . '</td>
                                                            <td>' . $array['engagement'] . '</td>
                                                            <td>' . $array['depb'] . '</td>
                                                            <td><a href="onboarding.php?associate-number=' . $array['associatenumber'] . '">Click Here</a></td>
                                                        </tr>';
                                                        }
                                                    }
                                                }
                                                ?>
                                                <?php echo '</table>
                                                          </div>'; ?>

                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h4 class="card-title">Exit Process</h4>
                                            <span class="badge rounded-pill text-bg-danger position-absolute top-0 end-0 me-2 mt-2">Pending: <?php echo $exit_left ?></span>
                                            <p class="card-text">Efficiently manage the separation of associates with the RSSI Exit Process. Conduct exit interviews, collect company property, provide benefit information, and complete necessary formalities in one place.</p>
                                            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#myModal_exit">
                                                Launch <i class="bi bi-box-arrow-up-right"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Modal -->
                                <div class="modal fade" id="myModal_exit" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel">Exit Process</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <?php echo '
                                                <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col">Associate number</th>
                                                            <th scope="col">Name</th>
                                                            <th scope="col">Last Working Day</th>    
                                                            <th scope="col">Engagement</th>
                                                            <th scope="col">Deputed Branch</th>
                                                            <th scope="col">Action</th>
                                                        </tr>
                                                    </thead>' ?>
                                                <?php if (sizeof($resultArr) > 0) { ?>
                                                <?php
                                                    foreach ($resultArr as $array) {
                                                        if ($array['process_type'] === 'exit') {
                                                            echo '
                                                                <tr>
                                                                    <td>' . $array['associatenumber'] . '</td>
                                                                    <td>' . $array['fullname'] . '</td>
                                                                    <td>' . (($array['effectivedate'] == null) ? "N/A" : date('M d, Y', strtotime($array['effectivedate']))) . '</td>
                                                                    <td>' . $array['engagement'] . '</td>
                                                                    <td>' . $array['depb'] . '</td>
                                                                    <td><a href="exit.php?associate-number=' . $array['associatenumber'] . '">Click Here</a></td>
                                                                </tr>';
                                                        }
                                                    }
                                                }
                                                ?>
                                                <?php echo '</table>
                                                </div>'; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h4 class="card-title">Visitor Registration</h4>
                                            <p class="card-text">Welcome to the RSSI Visitor Registration Portal. This is your one-stop solution to efficiently register and track the details of visitors to our premises.</p>
                                            <a href="visitor.php" class="btn btn-warning btn-sm" aria-disabled="true">Launch <i class="bi bi-box-arrow-up-right"></i></a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h4 class="card-title">Student Registration</h4>
                                            <span class="badge rounded-pill text-bg-danger position-absolute top-0 end-0 me-2 mt-2">
                                                Pending: <?php echo $admission_left; ?>
                                            </span>
                                            <p class="card-text">Welcome to the RSSI Student Admission Portal. Here, you can easily manage student data and track their admission process.</p>
                                            <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#myModal_admission">Launch <i class="bi bi-box-arrow-up-right"></i></a>
                                        </div>
                                    </div>
                                </div>
                                <!-- Modal -->
                                <div class="modal fade" id="myModal_admission" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel">New Admission</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <?php echo '
                                                <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col">Date of application</th>    
                                                            <th scope="col">Student Id</th>
                                                            <th scope="col">Name</th>
                                                            <th scope="col">Preferred Branch</th>
                                                            <th scope="col">Contact</th>
                                                            <th scope="col">Email</th>
                                                            <th scope="col">Action</th>
                                                        </tr>
                                                    </thead>' ?>
                                                <?php if (sizeof($resultArr_admission) > 0) { ?>
                                                <?php
                                                    foreach ($resultArr_admission as $array) {
                                                        if ($array['process_type'] === 'admission') {
                                                            echo '
                                                                <tr>
                                                                    <td>' . $array['doa'] . '</td>
                                                                    <td>' . $array['student_id'] . '</td>
                                                                    <td>' . $array['studentname'] . '</td>
                                                                    <td>' . $array['preferredbranch'] . '</td>
                                                                    <td>' . $array['contact'] . '</td>
                                                                    <td>' . $array['emailaddress'] . '</td>
                                                                    <td><a href="admission_admin.php?student_id=' . $array['student_id'] . '">Click Here</a></td>
                                                                </tr>';
                                                        }
                                                    }
                                                }
                                                ?>
                                                <?php echo '</table>
                                                </div>'; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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