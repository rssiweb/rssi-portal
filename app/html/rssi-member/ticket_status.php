<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$result = pg_query($con, "SELECT * FROM support_ticket ORDER BY timestamp DESC");
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <!-- Add DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.6/css/dataTables.bootstrap5.min.css">

    <!-- Add DataTables JS -->
    <script type="text/javascript" src="https://cdn.datatables.net/2.0.6/js/dataTables.min.js"></script>

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
                            <div class="col" style="display: inline-block; width:99%; text-align:right">
                                Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                            </div>

                            <div class="container">
                                <div class="table-responsive">
                                    <table class="table" id="table-id">
                                        <thead>
                                            <tr>
                                                <th>Ticket Id</th>
                                                <th>Description</th>
                                                <th>Supporting file</th>
                                                <th>Severity</th>
                                                <th>Raised by</th>
                                                <th>Tagged to</th>
                                                <th>Timestamp</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($resultArr as $array) {
                                                echo '
                                                <tr>
                                                    <td>' . $array['ticket_id'] . '</td>
                                                    <td>' . $array['short_description'] .'<br>'. $array['long_description'] . '</td>
                                                    <td>' . $array['upload_file'] . '</td>
                                                    <td>' . $array['severity'] . '</td>
                                                    <td>' . $array['raised_by'] . '</td>
                                                    <td>' . $array['raised_for'] . '</td>
                                                    <td>' . @date("d/m/Y g:i a", strtotime($array['timestamp'])) . '</td>
                                                    <td>' . $array['status'] . '</td>
                                                </tr>';
                                            } ?>
                                        </tbody>
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