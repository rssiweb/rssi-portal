<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
validation();

// Fetch associate numbers for the filter
$associates = [];
$associatesQuery = "SELECT DISTINCT associatenumber FROM wbt_status";
$result = pg_query($con, $associatesQuery);
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $associates[] = $row;
    }
}

// Handle the filter submission
$selectedAssociate = $_POST['associatenumber'] ?? null;
$data = [];

if ($selectedAssociate) {
    $query = "
    WITH LatestAttempts AS (
        SELECT 
            ws.associatenumber,
            ws.courseid,
            MAX(ws.timestamp) AS latest_timestamp
        FROM 
            wbt_status ws
        JOIN 
            wbt w ON ws.courseid = w.courseid
        WHERE 
            w.is_mandatory = TRUE
        GROUP BY 
            ws.associatenumber, ws.courseid
    )
    SELECT 
        ws.associatenumber,
        ws.timestamp AS completed_on,
        w.courseid,
        w.coursename,
        ROUND(ws.f_score * 100, 2) AS score_percentage,
        CASE 
            WHEN ROUND(ws.f_score * 100, 2) >= w.passingmarks THEN 'Completed'
            ELSE 'Incomplete'
        END AS status,
        CASE 
            WHEN ROUND(ws.f_score * 100, 2) >= w.passingmarks THEN 
                TO_CHAR(ws.timestamp + (w.validity || ' years')::INTERVAL, 'YYYY-MM-DD HH24:MI:SS')
            ELSE NULL
        END AS valid_upto,
        CASE 
            WHEN ROUND(ws.f_score * 100, 2) >= w.passingmarks THEN
                CASE 
                    WHEN ws.timestamp + (w.validity || ' years')::INTERVAL > NOW() THEN 'Active'
                    ELSE 'Expired'
                END
            ELSE NULL
        END AS additional_status
    FROM 
        wbt_status ws
    JOIN 
        LatestAttempts la ON ws.associatenumber = la.associatenumber 
                          AND ws.courseid = la.courseid 
                          AND ws.timestamp = la.latest_timestamp
    JOIN 
        wbt w ON ws.courseid = w.courseid
    WHERE 
        ws.associatenumber = $1";

    $stmt = pg_prepare($con, "fetch_data", $query);
    $result = pg_execute($con, "fetch_data", [$selectedAssociate]);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
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

    <title>My Learning</title>

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
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

</head>

<body>

    <body>
        <?php include 'inactive_session_expire_check.php'; ?>
        <?php include 'header.php'; ?>

        <main id="main" class="main">

            <div class="pagetitle">
                <h1>My Learning</h1>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">Learning & Collaboration</a></li>
                        <li class="breadcrumb-item"><a href="iexplore.php">iExplore</a></li>
                        <li class="breadcrumb-item active">My Learning</li>
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
                                <div class="container my-5">
                                    <!-- Filter Form -->
                                    <form method="POST" class="mb-4">
    <div class="mb-3">
        <label for="associatenumber" class="form-label">Associate Number</label>
        <div class="input-group">
            <input 
                type="text" 
                class="form-control" 
                id="associatenumber" 
                name="associatenumber" 
                value="<?= htmlspecialchars($selectedAssociate ?? '') ?>" 
                placeholder="Enter Associate Number" 
                required>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </div>
</form>



                                    <!-- Data Table -->
                                    <?php if ($data): ?>
                                        <div class="table-responsive">
                                            <table id="coursesTable" class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Associate Number</th>
                                                        <th>Completed On</th>
                                                        <th>Course ID</th>
                                                        <th>Course Name</th>
                                                        <th>Score</th>
                                                        <th>Status</th>
                                                        <th>Valid Upto</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($data as $row): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($row['associatenumber']) ?></td>
                                                            <td><?= htmlspecialchars($row['completed_on']) ?></td>
                                                            <td><?= htmlspecialchars($row['courseid']) ?></td>
                                                            <td><?= htmlspecialchars($row['coursename']) ?></td>
                                                            <td><?= htmlspecialchars($row['score_percentage']) ?>%</td>
                                                            <td>
                                                                <?php if ($row['status'] === 'Incomplete'): ?>
                                                                    Incomplete
                                                                <?php elseif ($row['status'] === 'Completed' && $row['additional_status'] === 'Active'): ?>
                                                                    Active
                                                                <?php elseif ($row['status'] === 'Completed' && $row['additional_status'] === 'Expired'): ?>
                                                                    Expired
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($row['status'] === 'Completed'): ?>
                                                                    <?= htmlspecialchars($row['valid_upto']) ?>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php elseif ($selectedAssociate): ?>
                                        <p class="text-danger">No records found for the selected associate number.</p>
                                    <?php endif; ?>
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
                <?php if (!empty($result)) : ?>
                    // Initialize DataTables only if resultArr is not empty
                    $('#coursesTable').DataTable({
                        // paging: false,
                        "order": [] // Disable initial sorting
                        // other options...
                    });
                <?php endif; ?>
            });
        </script>

    </body>

</html>