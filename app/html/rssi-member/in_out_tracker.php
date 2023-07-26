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

if ($role != 'Admin' && $role != 'Offline Manager') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}

$id = isset($_GET['get_aid']) ? strtoupper($_GET['get_aid']) : null;
$date = isset($_GET['get_date']) ? $_GET['get_date'] : '';

$query = "
WITH PunchInOut AS (
    SELECT
        a.user_id,
        DATE(a.punch_in) AS punch_date,
        MIN(a.punch_in) AS punch_in,
        CASE
            WHEN COUNT(*) = 1 THEN NULL
            ELSE MAX(a.punch_in)
        END AS punch_out
    FROM attendance a
    GROUP BY a.user_id, DATE(a.punch_in)
)
SELECT
    p.user_id,
    COALESCE(m.fullname, s.studentname) AS user_name,
    COALESCE(m.filterstatus, s.filterstatus) AS status,
    p.punch_in,
    p.punch_out
FROM PunchInOut p
LEFT JOIN rssimyaccount_members m ON p.user_id = m.associatenumber
LEFT JOIN rssimyprofile_student s ON p.user_id = s.student_id";

// Now you can use the $query variable to execute the SQL query using your preferred database connection method.


// Add conditions based on user input
if (!empty($id) && !empty($date)) {
    // Case 4: If both user_id and date are provided
    $formattedDate = date('Y-m-d', strtotime($date));
    $query .= " WHERE p.user_id = '$id' AND DATE(p.punch_in) = '$formattedDate'";
} elseif (!empty($id) && empty($date)) {
    // Case 2: If only user_id is provided
    $query .= " WHERE p.user_id = '$id'";
} elseif (empty($id) && !empty($date)) {
    // Case 1: If only date is provided
    $formattedDate = date('Y-m-d', strtotime($date));
    $query .= " WHERE DATE(p.punch_in) = '$formattedDate'";
} else {
    // Case 3: If both user_id and date are null, show data based on today's date
    $formattedTodayDate = date('Y-m-d');
    $query .= " WHERE DATE(p.punch_in) = '$formattedTodayDate'";
}

$query .= " ORDER BY p.punch_in DESC";

// Add a variable to check if today's data is being shown
$showingTodayData = false;

// Check if both $id and $date are null (Case 3)
if (empty($id) && empty($date)) {
    $showingTodayData = true;
}

$result = pg_query($con, $query);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>In-out tracker</title>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <style>
        .blink-text {
            color: red;
            animation: blinkAnimation 1s infinite;
        }

        @keyframes blinkAnimation {

            0%,
            50% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }
    </style>


</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>

    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>In-out tracker</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Class details</a></li>
                    <li class="breadcrumb-item"><a href="#">AttendX</a></li>
                    <li class="breadcrumb-item active">In-out tracker</li>
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
                            <form action="" method="GET" class="row g-2 align-items-center">
                                To customize the view result, please select a filter value.
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col-2" style="display: inline-block;">
                                        <input type="text" name="get_aid" id="get_aid" class="form-control" placeholder="User Id" value="<?php echo isset($_GET['get_aid']) ? htmlspecialchars($_GET['get_aid']) : ''; ?>">
                                    </div>
                                    <div class="col-2" style="display: inline-block;">
                                        <input type="date" name="get_date" id="get_date" class="form-control" value="<?php echo isset($_GET['get_date']) ? htmlspecialchars($_GET['get_date']) : ''; ?>">
                                    </div>
                                    <div class="col-2" style="display: inline-block; vertical-align: bottom;">
                                        <button type="submit" name="search_by_id" class="btn btn-success" style="outline: none;">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <div style="display: inline-block; width:100%; text-align:right;">Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                            </div>
                            <?php if ($showingTodayData) {
                                $formattedToday = date('F j, Y'); // Format the current date in a user-friendly way
                                echo '<div class="notification">You are viewing data for <span class="blink-text">' . $formattedToday . '</span></div>';
                            } ?>
                            <!-- HTML Table -->
                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th scope="col">User ID</th>
                                            <th scope="col">User Name</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Punch In</th>
                                            <th scope="col">Punch Out</th>
                                        </tr>
                                    </thead>
                                    <?php
                                    echo '<tbody>';
                                    if ($resultArr != null) {
                                        foreach ($resultArr as $array) {
                                            echo '<tr>';
                                            echo '<td>' . $array['user_id'] . '</td>';
                                            echo '<td>' . $array['user_name'] . '</td>';
                                            echo '<td>' . $array['status'] . '</td>';
                                            echo '<td>' . ($array['punch_in'] ? date('d/m/Y h:i:s a', strtotime($array['punch_in'])) : 'Not Available') . '</td>';
                                            echo '<td>' . ($array['punch_out'] ? date('d/m/Y h:i:s a', strtotime($array['punch_out'])) : 'Not Available') . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="8">No records found.</td></tr>';
                                    }

                                    echo '<tr style="display:none" id="last-row"></tr>';
                                    echo '</tbody>';
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

</body>

</html>