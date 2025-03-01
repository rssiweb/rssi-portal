<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
}

validation();
?>
<?php
// File: #

// Database connection (assuming $con is already defined)
// Include your database connection script if necessary
// include 'db_connection.php';

// Handle approval or rejection action
if (isset($_POST['approve']) || isset($_POST['reject'])) {
    $externalScoreId = $_POST['external_score_id'];
    $action = isset($_POST['approve']) ? 'approved' : 'rejected';

    // Fetch the external score record
    $query = "
        SELECT e.*, m.fullname, w.coursename
        FROM external_exam_scores e
        LEFT JOIN rssimyaccount_members m ON e.associate_number = m.associatenumber
        LEFT JOIN wbt w ON e.course_id = w.courseid
        WHERE e.id = $1
    ";
    $stmt = pg_prepare($con, "fetch_external_score", $query);
    $result = pg_execute($con, "fetch_external_score", [$externalScoreId]);

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);

        if ($action === 'approved') {
            // Prepare data for insertion into wbt_status
            $associateNumber = $row['associate_number'];
            $courseId = $row['course_id'];
            $timestamp = $row['completion_date']; // Use completion_date as timestamp
            $fScore = $row['score'] / 100; // Divide score by 100
            $email = null; // Set email to null

            // Insert into wbt_status
            $insertQuery = "
                INSERT INTO wbt_status (
                    associatenumber, 
                    courseid, 
                    timestamp, 
                    f_score, 
                    email
                ) VALUES ($1, $2, $3, $4, $5)
            ";
            $insertStmt = pg_prepare($con, "insert_wbt_status", $insertQuery);
            $insertResult = pg_execute($con, "insert_wbt_status", [
                $associateNumber,
                $courseId,
                $timestamp,
                $fScore,
                $email
            ]);

            if (!$insertResult) {
                echo "<script>alert('Error: Failed to insert into wbt_status.');</script>";
                exit;
            }
        }

        // Update the external_exam_scores table
        $updateQuery = "
            UPDATE external_exam_scores
            SET 
                status = $1,
                reviewed_by = $2,
                reviewed_on = $3
            WHERE id = $4
        ";
        $updateStmt = pg_prepare($con, "update_external_score", $updateQuery);
        $updateResult = pg_execute($con, "update_external_score", [
            $action,
            $user_check, // Assuming $user_check contains the logged-in user's data
            date('Y-m-d H:i:s'), // Current timestamp
            $externalScoreId
        ]);

        if ($updateResult) {
            echo "<script>alert('Request $action successfully!'); 
            if (window.history.replaceState) {
                        // Update the URL without causing a page reload or resubmission
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload(); // Trigger a page reload to reflect changes
            </script>";
            exit;
        } else {
            echo "<script>alert('Error: Failed to update external_exam_scores.');</script>";
            exit;
        }
    } else {
        echo "<script>alert('Error: External score record not found.');</script>";
        exit;
    }
}

// Fetch all pending external score requests
$query = "
    SELECT e.*, m.fullname, w.coursename
    FROM external_exam_scores e
    LEFT JOIN rssimyaccount_members m ON e.associate_number = m.associatenumber
    LEFT JOIN wbt w ON e.course_id = w.courseid
    WHERE e.status IS NULL OR e.status = 'pending'
";
$result = pg_query($con, $query);

if (!$result) {
    die("Error fetching external score requests.");
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

    <title>iExplore Workflow</title>

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
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }
    </style>

</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>iExplore Workflow</h1>
            <nav>
            <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Learning & Collaboration</a></li>
                    <li class="breadcrumb-item active">iExplore Workflow</li>
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
                                <!-- <h1 class="mb-4">Approve External Scores</h1> -->
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Course Name</th>
                                            <th>Associate Name</th>
                                            <th>Completion Date</th>
                                            <th>Score</th>
                                            <th>Supporting File</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = pg_fetch_assoc($result)) : ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['id']); ?></td>
                                                <td><?= htmlspecialchars($row['coursename']); ?></td>
                                                <td><?= htmlspecialchars($row['fullname']); ?></td>
                                                <td><?= htmlspecialchars($row['completion_date']); ?></td>
                                                <td><?= htmlspecialchars($row['score']); ?>%</td>
                                                <td>
                                                    <?php if ($row['supporting_file']) : ?>
                                                        <a href="<?= htmlspecialchars($row['supporting_file']); ?>" target="_blank">View File</a>
                                                    <?php else : ?>
                                                        No file
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form method="POST" action="#" style="display:inline;">
                                                        <input type="hidden" name="external_score_id" value="<?= $row['id']; ?>">
                                                        <button type="submit" name="approve" class="btn btn-success btn-sm">Approve</button>
                                                        <button type="submit" name="reject" class="btn btn-danger btn-sm">Reject</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
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
</body>

</html>