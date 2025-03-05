<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
validation();

// Initialize $data as an empty array
$data = [];

// Handle the filter submission (only for Admin users)
$selectedAssociate = ($role === 'Admin') ? ($_POST['associatenumber'] ?? null) : null;
$selectedCourse = ($role === 'Admin') ? ($_POST['course'] ?? null) : null;
$selectedAssociateStatus = ($role === 'Admin') ? ($_POST['associate_status'] ?? null) : null;
$selectedCourseStatus = ($role === 'Admin') ? ($_POST['course_status'] ?? null) : null;

// Check if any filter is selected (only for Admin users)
$isFilterSelected = ($role === 'Admin') ? ($selectedAssociate || $selectedCourse || $selectedAssociateStatus || $selectedCourseStatus) : false;

// Define the base query template
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
    GROUP BY 
        ws.associatenumber, ws.courseid
)
SELECT 
    ws.associatenumber,
    ram.fullname, -- Fetch fullname from rssimyaccount_members
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
JOIN 
    rssimyaccount_members ram ON ws.associatenumber = ram.associatenumber -- Join with rssimyaccount_members
WHERE 
    1=1"; // Start with a true condition to allow dynamic WHERE clauses

// Add filters dynamically based on user input (only for Admin users)
$params = [];
if ($role === 'Admin') {
    if ($selectedAssociate) {
        $query .= " AND ws.associatenumber = $" . (count($params) + 1);
        $params[] = $selectedAssociate;
    }
    if ($selectedCourse) {
        $query .= " AND w.courseid = $" . (count($params) + 1);
        $params[] = $selectedCourse;
    }
    if ($selectedAssociateStatus) {
        $query .= " AND EXISTS (SELECT 1 FROM rssimyaccount_members ram WHERE ram.associatenumber = ws.associatenumber AND ram.filterstatus = $" . (count($params) + 1) . ")";
        $params[] = $selectedAssociateStatus;
    }
    if ($selectedCourseStatus) {
        if ($selectedCourseStatus === 'Active' || $selectedCourseStatus === 'Expired') {
            $query .= " AND ws.timestamp + (w.validity || ' years')::INTERVAL " . ($selectedCourseStatus === 'Active' ? ">" : "<=") . " NOW()";
        } elseif ($selectedCourseStatus === 'Incomplete') {
            $query .= " AND ROUND(ws.f_score * 100, 2) < w.passingmarks";
        }
    }

    // If no filters are selected for Admin users, do not fetch any data
    if (!$isFilterSelected) {
        $query .= " AND 1=0"; // Force no results
    }
} else {
    // For non-Admin users, restrict data to their own associatenumber
    $query .= " AND ws.associatenumber = $" . (count($params) + 1);
    $params[] = $user_check;
}

// Prepare the statement
$stmt = pg_prepare($con, "fetch_data", $query);

// Execute the query with the appropriate parameters
$result = pg_execute($con, "fetch_data", $params);

// Fetch the results if the query was successful
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $data[] = $row;
    }
}

// Fetch all courses for the course filter dropdown (only for Admin users)
$courses = [];
if ($role === 'Admin') {
    $coursesQuery = "SELECT courseid, coursename FROM wbt";
    $coursesResult = pg_query($con, $coursesQuery);
    $courses = pg_fetch_all($coursesResult);
}

// Fetch all associate statuses for the status filter dropdown (only for Admin users)
$statuses = [];
if ($role === 'Admin') {
    $statusQuery = "SELECT DISTINCT filterstatus FROM rssimyaccount_members";
    $statusResult = pg_query($con, $statusQuery);
    $statuses = pg_fetch_all($statusResult);
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

    <title>My Learnings</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <!-- AJAX for Associatenumber and Course Dropdowns -->
    <script>
        $(document).ready(function() {
            // Fetch Associates
            // Initialize Select2 for associatenumber dropdown
            $('#associatenumber').select2({
                ajax: {
                    url: 'fetch_associates.php', // Path to the PHP script
                    dataType: 'json',
                    delay: 250, // Delay in milliseconds before sending the request
                    data: function(params) {
                        return {
                            q: params.term // Search term
                        };
                    },
                    processResults: function(data) {
                        // Map the results to the format expected by Select2
                        return {
                            results: data.results
                        };
                    },
                    cache: true // Cache results for better performance
                },
                minimumInputLength: 1 // Require at least 1 character to start searching
            });

            // Fetch Courses
            $('#course').select2({
                ajax: {
                    url: 'fetch_courses.php', // Create this file to fetch courses
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term // Search term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });
        });
    </script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>My Learnings</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">iExplore Learner</a></li>
                    <li class="breadcrumb-item active">My Learnings</li>
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
                                <?php if ($role === 'Admin'): ?>
                                    <!-- Filter Form for Admin Users -->
                                    <form method="POST" class="mb-4">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="associatenumber" class="form-label">Associate</label>
                                                <select class="form-control select2" id="associatenumber" name="associatenumber">
                                                    <option value="">Select Associate</option>
                                                    <?php if ($selectedAssociate): ?>
                                                        <!-- Pre-select the selected associate if it exists -->
                                                        <option value="<?= htmlspecialchars($selectedAssociate) ?>" selected>
                                                            <?= htmlspecialchars($selectedAssociate) ?> <!-- You can fetch and display the associate's name here if needed -->
                                                        </option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="course" class="form-label">Course</label>
                                                <select class="form-control select2" id="course" name="course">
                                                    <option value="">Select Course</option>
                                                    <?php if ($selectedCourse): ?>
                                                        <!-- Pre-select the selected course if it exists -->
                                                        <option value="<?= htmlspecialchars($selectedCourse) ?>" selected>
                                                            <?= htmlspecialchars($selectedCourse) ?> <!-- You can fetch and display the course name here if needed -->
                                                        </option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="associate_status" class="form-label">Associate Status</label>
                                                <select class="form-select" id="associate_status" name="associate_status">
                                                    <option value="">Select Status</option>
                                                    <?php foreach ($statuses as $status): ?>
                                                        <option value="<?= htmlspecialchars($status['filterstatus']) ?>" <?= ($selectedAssociateStatus == $status['filterstatus']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($status['filterstatus']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label for="course_status" class="form-label">Course Status</label>
                                                <select class="form-select" id="course_status" name="course_status">
                                                    <option value="">Select Status</option>
                                                    <option value="Active" <?= ($selectedCourseStatus == 'Active') ? 'selected' : '' ?>>Active</option>
                                                    <option value="Expired" <?= ($selectedCourseStatus == 'Expired') ? 'selected' : '' ?>>Expired</option>
                                                    <option value="Incomplete" <?= ($selectedCourseStatus == 'Incomplete') ? 'selected' : '' ?>>Incomplete</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <button type="submit" class="btn btn-primary">Filter</button>
                                                <!-- Reset Button -->
                                                <button type="button" id="resetFilters" class="btn btn-secondary">Reset</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                                <!-- Data Table -->
                                <?php if ($data): ?>
                                    <div class="table-responsive">
                                        <table id="coursesTable" class="table">
                                            <thead>
                                                <tr>
                                                    <?php if ($role === 'Admin'): ?>
                                                        <th>Associate Number</th>
                                                        <th>Name</th>
                                                    <?php endif; ?>
                                                    <th>Attempt Date</th>
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
                                                        <?php if ($role === 'Admin'): ?>
                                                            <td><?= htmlspecialchars($row['associatenumber']) ?></td>
                                                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                                                        <?php endif; ?>
                                                        <td><?= date('d/m/Y h:i A', strtotime($row['completed_on'])) ?></td>
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
                                                                <?= date('d/m/Y h:i A', strtotime($row['valid_upto'])) ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php elseif ($role === 'Admin' && $isFilterSelected): ?>
                                    <!-- Show "No records found" message only for Admin users when filters are applied -->
                                    <p class="text-danger">No records found for the selected filters.</p>
                                <?php elseif ($role === 'Admin'): ?>
                                    <!-- Show "Please select at least one filter" message only for Admin users when no filters are selected -->
                                    <p class="text-danger">Please select at least one filter to view results.</p>
                                <?php else: ?>
                                    <!-- Show a generic message for non-Admin users if no data is found -->
                                    <p class="text-danger">No records found.</p>
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
    <script>
        $(document).ready(function() {
            // Reset Filters Button
            $('#resetFilters').on('click', function() {
                // Reset form fields
                $('#associatenumber').val('').trigger('change'); // Clear Select2 dropdown
                $('#course').val('').trigger('change'); // Clear Select2 dropdown
                $('#associate_status').val(''); // Clear associate status dropdown
                $('#course_status').val(''); // Clear course status dropdown

                // Option 1: Reload the page to reset everything
                window.location.href = window.location.pathname; // Reload the page without query parameters

                // Option 2: Reset the table (if you don't want to reload the page)
                // fetchAndDisplayData(); // Call a function to fetch and display all data
            });
        });
    </script>

</body>

</html>