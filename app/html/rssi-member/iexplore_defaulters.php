<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
}

validation();
// Fetching data from the database (you can modify the database connection as per your configuration)
$query = "
WITH MandatoryCourses AS (
    SELECT 
        courseid, 
        coursename
    FROM 
        wbt
    WHERE 
        is_mandatory = TRUE
),
AssociateCourses AS (
    SELECT 
        ws.associatenumber, 
        ws.courseid,
        ws.timestamp,
        ROUND(ws.f_score * 100, 2) AS score
    FROM 
        wbt_status ws
    JOIN 
        rssimyaccount_members rm ON ws.associatenumber = rm.associatenumber
    WHERE 
        rm.filterstatus = 'Active'
        AND rm.engagement IN ('Employee', 'Intern')
),
ExpiredCourses AS (
    SELECT
        ws.associatenumber,
        rm.fullname,
        w.courseid,
        w.coursename,
        TO_CHAR(ws.timestamp + (w.validity || ' years')::INTERVAL, 'YYYY-MM-DD') AS expired_on
    FROM
        wbt_status ws
    JOIN
        wbt w ON ws.courseid = w.courseid
    JOIN
        rssimyaccount_members rm ON ws.associatenumber = rm.associatenumber
    WHERE
        ROUND(ws.f_score * 100, 2) >= w.passingmarks
        AND ws.timestamp + (w.validity || ' years')::INTERVAL < NOW()
        AND rm.filterstatus = 'Active'
        AND rm.engagement IN ('Employee', 'Intern')
),
LatestAssociateCourses AS (
    SELECT
        ws.associatenumber,
        ws.courseid,
        ws.timestamp,
        ROUND(ws.f_score * 100, 2) AS score
    FROM
        wbt_status ws
    JOIN
        rssimyaccount_members rm ON ws.associatenumber = rm.associatenumber
    WHERE
        rm.filterstatus = 'Active'
        AND rm.engagement IN ('Employee', 'Intern')
        AND ws.timestamp = (
            SELECT MAX(timestamp)
            FROM wbt_status
            WHERE associatenumber = ws.associatenumber
              AND courseid = ws.courseid
        )
),
MissingCourses AS (
    SELECT 
        rm.associatenumber,
        mc.courseid,
        mc.coursename
    FROM 
        rssimyaccount_members rm
    CROSS JOIN MandatoryCourses mc
    LEFT JOIN LatestAssociateCourses lac 
        ON rm.associatenumber = lac.associatenumber 
        AND mc.courseid = lac.courseid
    LEFT JOIN wbt w ON mc.courseid = w.courseid -- joining to get the passing mark for each course
    WHERE 
        rm.filterstatus = 'Active'
        AND rm.engagement IN ('Employee', 'Intern')
        AND (
            lac.associatenumber IS NULL  -- not taken
            OR lac.score < w.passingmarks  -- failed (score < passing mark)
        )
)
-- Now, we combine the expired courses and the missing mandatory courses
SELECT 
    mc.associatenumber,
    rm.fullname,
    mc.courseid,
    mc.coursename,
    'Pending' AS status,
    NULL AS expired_on  -- Adding NULL for the expired_on column for Pending courses
FROM 
    MissingCourses mc
JOIN 
    rssimyaccount_members rm ON mc.associatenumber = rm.associatenumber

UNION ALL

SELECT 
    ec.associatenumber,
    ec.fullname,
    ec.courseid,
    ec.coursename,
    'Expired' AS status,
    ec.expired_on  -- Including the expired_on for Expired courses
FROM 
    ExpiredCourses ec
ORDER BY 
    associatenumber, courseid;
";

$result = pg_query($con, $query); // Assuming $con is your PostgreSQL connection
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

    <title>iExplore Defaulters</title>

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
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>iExplore Defaulters</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Learning & Collaboration</a></li>
                    <li class="breadcrumb-item"><a href="iexplore.php">iExplore</a></li>
                    <li class="breadcrumb-item active">iExplore Defaulters</li>
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
                            <div class="container my-4">
                                <!-- <h3 class="mb-3">iExplore Defaulters</h3> -->

                                <div class="table-responsive">
                                    <table id="coursesTable" class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Associate Number</th>
                                                <th scope="col">Full Name</th>
                                                <th scope="col">Course Id</th>
                                                <th scope="col">Course Name</th>
                                                <th scope="col">Expired On</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = pg_fetch_assoc($result)) { ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['associatenumber']) ?></td>
                                                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                                                    <td><?= htmlspecialchars($row['courseid']) ?></td>
                                                    <td><?= htmlspecialchars($row['coursename']) ?></td>
                                                    <td><?= $row['expired_on'] ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
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