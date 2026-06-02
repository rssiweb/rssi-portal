<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
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
),
-- Get the latest COMPLETED attempt (passed) to track expiration
LatestCompletedAttempt AS (
    SELECT 
        ws.associatenumber,
        ws.courseid,
        MAX(ws.timestamp) AS last_completed_timestamp
    FROM 
        wbt_status ws
    JOIN 
        wbt w ON ws.courseid = w.courseid
    WHERE 
        ROUND(ws.f_score * 100, 2) >= w.passingmarks
    GROUP BY 
        ws.associatenumber, ws.courseid
)
SELECT 
    ws.associatenumber,
    ram.fullname,
    ws.timestamp AS completed_on,
    w.courseid,
    w.coursename,
    ROUND(ws.f_score * 100, 2) AS score_percentage,
    CASE 
        WHEN ROUND(ws.f_score * 100, 2) >= w.passingmarks THEN 'Completed'
        ELSE 'Incomplete'
    END AS status,
    -- Original expiration based on the last completed (passed) attempt
    CASE 
        WHEN lca.last_completed_timestamp IS NOT NULL THEN 
            TO_CHAR(lca.last_completed_timestamp + (w.validity || ' years')::INTERVAL, 'YYYY-MM-DD HH24:MI:SS')
        ELSE NULL
    END AS valid_upto,
    CASE 
        WHEN lca.last_completed_timestamp IS NOT NULL THEN
            CASE 
                WHEN lca.last_completed_timestamp + (w.validity || ' years')::INTERVAL > NOW() THEN 'Active'
                ELSE 'Expired'
            END
        ELSE NULL
    END AS certificate_status,
    lca.last_completed_timestamp AS last_passed_date
FROM 
    wbt_status ws
JOIN 
    LatestAttempts la ON ws.associatenumber = la.associatenumber 
                      AND ws.courseid = la.courseid 
                      AND ws.timestamp = la.latest_timestamp
LEFT JOIN 
    LatestCompletedAttempt lca ON ws.associatenumber = lca.associatenumber 
                                AND ws.courseid = lca.courseid
JOIN 
    wbt w ON ws.courseid = w.courseid
JOIN 
    rssimyaccount_members ram ON ws.associatenumber = ram.associatenumber
WHERE 
    1=1";

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
            $query .= " AND EXISTS (
                SELECT 1 FROM LatestCompletedAttempt lca2 
                WHERE lca2.associatenumber = ws.associatenumber 
                AND lca2.courseid = ws.courseid
                AND lca2.last_completed_timestamp + (w.validity || ' years')::INTERVAL " . ($selectedCourseStatus === 'Active' ? ">" : "<=") . " NOW()
            )";
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
    $params[] = $associatenumber;
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
    <?php include 'includes/meta.php' ?>

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
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <!-- AJAX for Associatenumber and Course Dropdowns -->
    <script>
        $(document).ready(function() {
            // Fetch Associates
            $('#associatenumber').select2({
                ajax: {
                    url: 'fetch_associates.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1
            });

            // Fetch Courses
            $('#course').select2({
                ajax: {
                    url: 'fetch_courses.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
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

    <style>
        .certificate-status {
            font-size: 0.9em;
        }

        .certificate-status small {
            display: block;
            margin-top: 4px;
            font-size: 0.85em;
        }

        .text-active {
            color: #28a745;
            font-weight: 500;
        }

        .text-expired {
            color: #dc3545;
            font-weight: 500;
        }

        .text-warning-info {
            color: #856404;
        }

        .badge-incomplete {
            background-color: #ffc107;
            color: #856404;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div>

        <section class="section dashboard">
            <div class="row">

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
                                                        <option value="<?= htmlspecialchars($selectedAssociate) ?>" selected>
                                                            <?= htmlspecialchars($selectedAssociate) ?>
                                                        </option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="course" class="form-label">Course</label>
                                                <select class="form-control select2" id="course" name="course">
                                                    <option value="">Select Course</option>
                                                    <?php if ($selectedCourse): ?>
                                                        <option value="<?= htmlspecialchars($selectedCourse) ?>" selected>
                                                            <?= htmlspecialchars($selectedCourse) ?>
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
                                                <label for="course_status" class="form-label">Certificate Status</label>
                                                <select class="form-select" id="course_status" name="course_status">
                                                    <option value="">Select Status</option>
                                                    <option value="Active" <?= ($selectedCourseStatus == 'Active') ? 'selected' : '' ?>>Active</option>
                                                    <option value="Expired" <?= ($selectedCourseStatus == 'Expired') ? 'selected' : '' ?>>Expired</option>
                                                    <option value="Incomplete" <?= ($selectedCourseStatus == 'Incomplete') ? 'selected' : '' ?>>No Certificate (Failed)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <button type="submit" class="btn btn-primary">Filter</button>
                                                <button type="button" id="resetFilters" class="btn btn-secondary">Reset</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>

                                <?php if ($data): ?>
                                    <div class="table-responsive">
                                        <table id="coursesTable" class="table">
                                            <thead>
                                                <tr>
                                                    <?php if ($role === 'Admin'): ?>
                                                        <th>Associate</th>
                                                        <th>Name</th>
                                                    <?php endif; ?>
                                                    <th>Attempt Date</th>
                                                    <th>Course</th>
                                                    <th>Score</th>
                                                    <th>Status</th>
                                                    <th>Valid Until</th>
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
                                                        <td>
                                                            <?= htmlspecialchars($row['coursename']) ?><br>
                                                            <small class="text-muted">ID: <?= htmlspecialchars($row['courseid']) ?></small>
                                                        </td>
                                                        <td><?= htmlspecialchars($row['score_percentage']) ?>%</td>
                                                        <td>
                                                            <?php if ($row['status'] === 'Incomplete'): ?>
                                                                <?php if (!empty($row['valid_upto']) && $row['certificate_status'] == 'Expired'): ?>
                                                                    <span class="badge bg-danger">Expired</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-incomplete">❌ Failed Attempt</span>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <?php if ($row['certificate_status'] == 'Active'): ?>
                                                                    <span class="badge bg-success">Active</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-danger">Expired</span>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <?php
                                                        // Calculate if certificate is about to expire (within 30 days)
                                                        $isAboutToExpire = false;
                                                        if (!empty($row['valid_upto'])) {
                                                            $currentDate = new DateTime();
                                                            $expiryDateObj = new DateTime($row['valid_upto']);
                                                            $daysUntilExpiry = $currentDate->diff($expiryDateObj)->days;

                                                            // Only show "About to Expire" if certificate is active and within 30 days of expiry
                                                            if ($row['certificate_status'] == 'Active' && $daysUntilExpiry <= 30 && $expiryDateObj > $currentDate) {
                                                                $isAboutToExpire = true;
                                                            }
                                                        }
                                                        ?>
                                                        <td>
                                                            <?php
                                                            // Only proceed if there's a valid_upto date (certificate exists)
                                                            if (!empty($row['valid_upto'])) {
                                                                $validDate = date('d/m/Y', strtotime($row['valid_upto']));
                                                                // Certificate is expired if status is 'Expired' OR if it's a completed attempt with date in past
                                                                $isExpired = ($row['certificate_status'] == 'Expired') ||
                                                                    ($row['status'] === 'Completed' && strtotime($row['valid_upto']) < time());
                                                                echo '<span class="' . ($isExpired ? 'text-danger' : '') . '">' . $validDate . '</span>';

                                                                // Add about to expire indicator with tooltip
                                                                if ($isAboutToExpire && !$isExpired) {
                                                                    $daysLeft = $currentDate->diff($expiryDateObj)->days;
                                                                    $tooltipMessage = "⚠️ Certificate will expire in {$daysLeft} days! Last successful completion: " . date('d/m/Y', strtotime($row['last_passed_date'])) . ". Please retake the course before " . date('d/m/Y', strtotime($row['valid_upto'])) . " to maintain compliance and avoid any certification gaps.";
                                                                    echo ' <span class="badge bg-warning text-dark" style="font-size: 0.7rem; cursor: help;" data-bs-toggle="tooltip" data-bs-html="true" title="' . htmlspecialchars($tooltipMessage) . '"><i class="bi bi-exclamation-triangle"></i></span>';
                                                                }
                                                            } else {
                                                                // No certificate exists
                                                                echo '<span class="text-muted">No certificate issued yet</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php elseif ($role === 'Admin' && $isFilterSelected): ?>
                                    <div class="alert alert-warning">No records found for the selected filters.</div>
                                <?php elseif ($role === 'Admin'): ?>
                                    <div class="alert alert-info">Please select at least one filter to view results.</div>
                                <?php else: ?>
                                    <div class="alert alert-warning">No records found.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            <?php if (!empty($result)) : ?>
                $('#coursesTable').DataTable({
                    "order": [],
                    "pageLength": 25,
                    "language": {
                        "emptyTable": "No data available"
                    }
                });
            <?php endif; ?>
        });

        // Reset Filters Button
        $(document).ready(function() {
            $('#resetFilters').on('click', function() {
                $('#associatenumber').val('').trigger('change');
                $('#course').val('').trigger('change');
                $('#associate_status').val('');
                $('#course_status').val('');
                window.location.href = window.location.pathname;
            });
        });
    </script>

</body>

</html>