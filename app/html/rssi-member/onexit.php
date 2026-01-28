<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}
validation();

$id = isset($_GET['get_aid']) ? strtoupper($_GET['get_aid']) : null;

// Fetch data for both onboarding and exit
$result = pg_query($con, "SELECT
    onboarding_associate_id AS associate_id,
    fullname AS associate_name,
    onboard_initiated_on AS onboarding_initiated_on,
    onboarding_submitted_on AS onboarding_submitted_on,
    exit_initiated_on AS exit_initiated_on,
    exit_submitted_on AS exit_submitted_on
    FROM onboarding
    LEFT JOIN associate_exit ON onboarding_associate_id = exit_associate_id
    LEFT JOIN rssimyaccount_members ON onboarding_associate_id = rssimyaccount_members.associatenumber
    ORDER BY onboarding_submitted_on DESC");

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);

if ($id != null) {
    // If $id is not null, filter the results for the specific associate
    $filteredResultArr = array_filter($resultArr, function ($row) use ($id) {
        return $row['associate_id'] == $id;
    });

    $resultArr = $filteredResultArr;
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
    <?php include 'includes/meta.php' ?>



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
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <form action="" method="GET" class="row g-2 align-items-center">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col-2" style="display: inline-block;">
                                        <label for="get_aid" class="form-label">Enter Associate Number:</label>
                                        <input type="text" name="get_aid" id="get_aid" class="form-control" placeholder="Associate Number" value="<?php echo isset($_GET['get_aid']) ? htmlspecialchars($_GET['get_aid']) : ''; ?>">
                                    </div>
                                    <div class="col-2" style="display: inline-block;">
                                        <button type="submit" name="search_by_id" class="btn btn-success" style="outline: none;">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <div style="display: inline-block; width:100%; text-align:right;">Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                            </div>
                            <!-- HTML Table -->
                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th scope="col">Associate Number</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Onboarded On</th>
                                            <th scope="col">Exited On</th>
                                            <th scope="col">View Form</th>
                                        </tr>
                                    </thead>
                                    <?php if ($resultArr != null) {
                                        echo '<tbody>';
                                        foreach ($resultArr as $array) {
                                            echo '<tr>';
                                            echo '<td>' . $array['associate_id'] . '</td>';
                                            echo '<td>' . $array['associate_name'] . '</td>';
                                            echo '<td>' . ($array['onboarding_initiated_on'] ? date('d/m/Y h:i:s a', strtotime($array['onboarding_initiated_on'])) : 'Not Onboarded') . '</td>';
                                            echo '<td>' . ($array['exit_initiated_on'] ? date('d/m/Y h:i:s a', strtotime($array['exit_initiated_on'])) : 'Not Exited') . '</td>';
                                            echo '<td>';
                                            if ($array['onboarding_initiated_on']) {
                                                echo '<a href="onboarding.php?associate-number=' . $array['associate_id'] . '">View Onboarding Form</a>';
                                            }
                                            if ($array['exit_initiated_on']) {
                                                if ($array['onboarding_initiated_on']) {
                                                    echo ' | ';
                                                }
                                                echo '<a href="exit.php?associate-number=' . $array['associate_id'] . '">View Exit Form</a>';
                                            }
                                            echo '</td>';

                                            echo '</tr>';
                                        }
                                        echo '</tbody>';
                                    } else {
                                        echo '<tbody><tr><td colspan="5">Associate with ID ' . $id . ' not found.</td></tr></tbody>';
                                    }
                                    ?>
                                </table>
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

</body>

</html>