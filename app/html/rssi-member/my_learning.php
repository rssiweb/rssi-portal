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

// Check user type
$isAdmin = ($role === 'Admin');
$isOfflineManager = ($role === 'Offline Manager');
$isRegularNonAdmin = (!$isAdmin && !$isOfflineManager);

// Handle filters based on user type
if ($isAdmin) {
    // Admin: all filters
    $selectedAssociate = ($_POST['associatenumber'] ?? null);
    $selectedCourse = ($_POST['course'] ?? null);
    $selectedAssociateStatus = ($_POST['associate_status'] ?? null);
    $selectedCourseStatus = ($_POST['course_status'] ?? null);
    $isFilterSelected = ($selectedAssociate || $selectedCourse || $selectedAssociateStatus || $selectedCourseStatus);
    $searchByCourseForActive = false;
} elseif ($isOfflineManager) {
    // Offline Manager: associate filter and course filter with checkbox
    $selectedAssociate = ($_POST['associatenumber'] ?? null);
    $selectedCourse = ($_POST['course'] ?? null);
    $searchByCourseForActive = isset($_POST['search_by_course']) ? true : false;
    $selectedAssociateStatus = null;
    $selectedCourseStatus = null;

    // If search_by_course is checked, course filter is allowed
    if ($searchByCourseForActive) {
        $isFilterSelected = ($selectedAssociate || $selectedCourse);
    } else {
        $isFilterSelected = ($selectedAssociate);
        $selectedCourse = null; // Ignore course if checkbox is not checked
    }
} else {
    // Regular Non-Admin: no filters, just show their own data
    $selectedAssociate = null;
    $selectedCourse = null;
    $selectedAssociateStatus = null;
    $selectedCourseStatus = null;
    $searchByCourseForActive = false;
    $isFilterSelected = true; // Always fetch data for regular non-admin
}

// Only fetch data if filters are selected (or for regular non-admin always fetch)
if ($isFilterSelected) {
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

    // Add filters dynamically based on user input
    $params = [];

    // Apply associate filter based on user type
    if ($isAdmin) {
        if ($selectedAssociate) {
            $query .= " AND ws.associatenumber = $" . (count($params) + 1);
            $params[] = $selectedAssociate;
        }
    } elseif ($isOfflineManager) {
        if ($selectedAssociate) {
            // Check if selected associate is authorized (self or reportee)
            $authQuery = "SELECT COUNT(*) as count FROM rssimyaccount_members WHERE associatenumber = $1 AND (associatenumber = $2 OR supervisor = $2)";
            $authResult = pg_query_params($con, $authQuery, array($selectedAssociate, $associatenumber));
            $authRow = pg_fetch_assoc($authResult);

            if ($authRow['count'] > 0) {
                $query .= " AND ws.associatenumber = $" . (count($params) + 1);
                $params[] = $selectedAssociate;
            } else {
                // Unauthorized - force no results
                $query .= " AND 1=0";
            }
        } else {
            // No associate selected - show only self and reportees
            $query .= " AND (ws.associatenumber = $" . (count($params) + 1) . " OR ws.associatenumber IN (SELECT associatenumber FROM rssimyaccount_members WHERE supervisor = $" . (count($params) + 1) . "))";
            $params[] = $associatenumber;
        }

        // If search by course for active associates is enabled, add filterstatus condition
        if ($searchByCourseForActive && $selectedCourse && !$selectedAssociate) {
            // Only show associates with Active status when searching by course
            $query .= " AND ram.filterstatus = 'Active'";
        }
    } else {
        // Regular non-admin - only show their own data
        $query .= " AND ws.associatenumber = $" . (count($params) + 1);
        $params[] = $associatenumber;
    }

    // Apply course filter
    if ($isAdmin && $selectedCourse) {
        $query .= " AND w.courseid = $" . (count($params) + 1);
        $params[] = $selectedCourse;
    } elseif ($isOfflineManager && $searchByCourseForActive && $selectedCourse) {
        $query .= " AND w.courseid = $" . (count($params) + 1);
        $params[] = $selectedCourse;
    }

    // Apply associate status filter (only for Admin)
    if ($isAdmin && $selectedAssociateStatus) {
        $query .= " AND EXISTS (SELECT 1 FROM rssimyaccount_members ram2 WHERE ram2.associatenumber = ws.associatenumber AND ram2.filterstatus = $" . (count($params) + 1) . ")";
        $params[] = $selectedAssociateStatus;
    }

    // Apply certificate status filter (only for Admin)
    if ($isAdmin && $selectedCourseStatus) {
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

    // Prepare and execute the statement
    $statementName = "fetch_data_" . md5($query);
    $stmt = pg_prepare($con, $statementName, $query);
    $result = pg_execute($con, $statementName, $params);

    // Fetch the results if the query was successful
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
}

// Fetch all courses for the course filter dropdown (for Admin and Offline Manager)
$courses = [];
if ($isAdmin || $isOfflineManager) {
    $coursesQuery = "SELECT courseid, coursename FROM wbt ORDER BY coursename";
    $coursesResult = pg_query($con, $coursesQuery);
    $courses = pg_fetch_all($coursesResult);
}

// Fetch all associate statuses for the status filter dropdown (only for Admin)
$statuses = [];
if ($isAdmin) {
    $statusQuery = "SELECT DISTINCT filterstatus FROM rssimyaccount_members ORDER BY filterstatus";
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
            <?php if ($isAdmin || $isOfflineManager): ?>
                // Fetch Associates with supervisor restriction for Offline Manager
                $('#associatenumber').select2({
                    ajax: {
                        url: 'fetch_associates.php',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term,
                                restrictToSupervisor: <?php echo $isOfflineManager ? 'true' : 'false'; ?>
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1,
                    allowClear: true,
                    placeholder: 'Select Associate'
                });
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                // Fetch Courses for Admin
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
                    minimumInputLength: 1,
                    allowClear: true,
                    placeholder: 'Select Course'
                });
            <?php elseif ($isOfflineManager): ?>
                // Initialize course select2 for Offline Manager (initially disabled)
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
                    minimumInputLength: 1,
                    allowClear: true,
                    placeholder: 'Select Course',
                    disabled: true
                });

                // Handle checkbox change for Offline Manager
                $('#search_by_course').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#course').prop('disabled', false);
                        $('#course').next('.select2-container').find('.select2-selection').css('background-color', '#ffffff');
                    } else {
                        $('#course').prop('disabled', true);
                        $('#course').val(null).trigger('change');
                        $('#course').next('.select2-container').find('.select2-selection').css('background-color', '#e9ecef');
                    }
                });

                // Set initial state based on checkbox
                <?php if ($searchByCourseForActive): ?>
                    $('#search_by_course').prop('checked', true);
                    $('#course').prop('disabled', false);
                    $('#course').next('.select2-container').find('.select2-selection').css('background-color', '#ffffff');
                <?php else: ?>
                    $('#course').prop('disabled', true);
                    $('#course').next('.select2-container').find('.select2-selection').css('background-color', '#e9ecef');
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($selectedAssociate && ($isAdmin || $isOfflineManager)): ?>
                // Set selected associate
                var selectedOption = new Option('<?php echo $selectedAssociate; ?>', '<?php echo $selectedAssociate; ?>', true, true);
                $('#associatenumber').append(selectedOption).trigger('change');
            <?php endif; ?>

            <?php if ($selectedCourse && $isAdmin): ?>
                // Set selected course for Admin
                var selectedCourse = new Option('<?php echo $selectedCourse; ?>', '<?php echo $selectedCourse; ?>', true, true);
                $('#course').append(selectedCourse).trigger('change');
            <?php endif; ?>

            <?php if ($selectedCourse && $isOfflineManager && $searchByCourseForActive): ?>
                // Set selected course for Offline Manager when checkbox is checked
                var selectedCourse = new Option('<?php echo $selectedCourse; ?>', '<?php echo $selectedCourse; ?>', true, true);
                $('#course').append(selectedCourse).trigger('change');
            <?php endif; ?>

            <?php if ($selectedAssociateStatus && $isAdmin): ?>
                $('#associate_status').val('<?php echo $selectedAssociateStatus; ?>').trigger('change');
            <?php endif; ?>

            <?php if ($selectedCourseStatus && $isAdmin): ?>
                $('#course_status').val('<?php echo $selectedCourseStatus; ?>').trigger('change');
            <?php endif; ?>
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

        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }

        .checkbox-label {
            margin-left: 10px;
            font-weight: normal;
            cursor: pointer;
        }

        .help-text {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 5px;
        }

        .select2-container--default.select2-container--disabled .select2-selection--single {
            background-color: #e9ecef !important;
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
                                <!-- Filter Form based on user type -->
                                <?php if ($isAdmin): ?>
                                    <!-- Admin: Full filters -->
                                    <form method="POST" class="mb-4">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label for="associatenumber" class="form-label">Associate</label>
                                                <select class="form-control select2" id="associatenumber" name="associatenumber">
                                                    <option value="">Select Associate</option>
                                                </select>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="course" class="form-label">Course</label>
                                                <select class="form-control select2" id="course" name="course">
                                                    <option value="">Select Course</option>
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

                                <?php elseif ($isOfflineManager): ?>
                                    <!-- Offline Manager: Associate filter with checkbox for course search -->
                                    <form method="POST" class="mb-4">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="associatenumber" class="form-label">Associate</label>
                                                <select class="form-control select2" id="associatenumber" name="associatenumber">
                                                    <option value="">Select Associate</option>
                                                </select>
                                                <small class="text-muted">You can only view data for yourself and your reportees</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="search_by_course" name="search_by_course" value="1">
                                                    <label class="form-check-label checkbox-label" for="search_by_course">
                                                        Search by Course for Active Associates
                                                    </label>
                                                </div>
                                                <label for="course" class="form-label">Course</label>
                                                <select class="form-control select2" id="course" name="course">
                                                    <option value="">Select Course</option>
                                                </select>
                                                <div class="help-text">
                                                    <small>Note: When searching by course, only associates with "Active" status will be included.</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <button type="submit" class="btn btn-primary">Filter</button>
                                                <button type="button" id="resetFilters" class="btn btn-secondary">Reset</button>
                                            </div>
                                        </div>
                                    </form>

                                <?php else: ?>

                                <?php endif; ?>

                                <?php if ($data): ?>
                                    <div class="table-responsive">
                                        <table id="coursesTable" class="table">
                                            <thead>
                                                <tr>
                                                    <th>Associate Number</th>
                                                    <th>Name</th>
                                                    <th>Latest Attempt Date</th>
                                                    <th>Course</th>
                                                    <th>Score</th>
                                                    <th>Status</th>
                                                    <th>Valid Until</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $row): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row['associatenumber']) ?></td>
                                                        <td><?= htmlspecialchars($row['fullname']) ?></td>
                                                        <td><?= date('d/m/Y h:i A', strtotime($row['completed_on'])) ?></td>
                                                        <td>
                                                            <?= htmlspecialchars($row['coursename']) ?><br>
                                                            <small class="text-muted">ID: <?= htmlspecialchars($row['courseid']) ?></small>
                                                        </td>
                                                        <td><?= htmlspecialchars($row['score_percentage']) ?>%</td>
                                                        <td>
                                                            <?php if ($row['status'] === 'Incomplete'): ?>
                                                                <span class="badge badge-incomplete">❌ Failed Attempt</span>
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
                                                            $hasCertificate = !empty($row['valid_upto']);
                                                            $hasPreviousPass = !empty($row['last_passed_date']);

                                                            if ($hasCertificate) {
                                                                $validDate = date('d/m/Y', strtotime($row['valid_upto']));
                                                                $isExpired = ($row['certificate_status'] == 'Expired') ||
                                                                    ($row['status'] === 'Completed' && strtotime($row['valid_upto']) < time());

                                                                if ($row['status'] === 'Completed') {
                                                                    // SUCCESSFUL ATTEMPT - Show certificate status
                                                                    echo '<span class="' . ($isExpired ? 'text-danger' : '') . '">' . $validDate . '</span>';

                                                                    if ($isAboutToExpire && !$isExpired) {
                                                                        $daysLeft = $currentDate->diff($expiryDateObj)->days;
                                                                        $tooltipMessage = "⚠️ Certificate will expire in {$daysLeft} days! Last successful completion: " . date('d/m/Y', strtotime($row['last_passed_date'])) . ". Please retake the course before " . date('d/m/Y', strtotime($row['valid_upto'])) . " to maintain compliance and avoid any certification gaps.";
                                                                        echo ' <span class="badge bg-warning text-dark" style="font-size: 0.7rem; cursor: help;" data-bs-toggle="tooltip" data-bs-html="true" title="' . htmlspecialchars($tooltipMessage) . '"><i class="bi bi-exclamation-triangle"></i></span>';
                                                                    }
                                                                } elseif ($hasPreviousPass) {
                                                                    // FAILED ATTEMPT but has previous success
                                                                    $lastPassedDate = date('d/m/Y', strtotime($row['last_passed_date']));


                                                                    if ($isExpired) {
                                                                        // Case: Certificate expired
                                                                        // echo '<i class="bi bi-exclamation-triangle text-danger"></i> Certificate expired';
                                                                        echo '<span class="text-danger">' . $validDate . '</span>';
                                                                    } else {
                                                                        // Case: Certificate still valid
                                                                        $daysLeft = floor((strtotime($row['valid_upto']) - time()) / 86400);
                                                                        echo '<i class="bi bi-info-circle"></i> Certificate valid until';
                                                                        echo '<br><strong class="text-success">' . $validDate . '</strong>';
                                                                        if ($daysLeft <= 30) {
                                                                            echo '<br><small class="text-warning">⚠️ Expires in ' . $daysLeft . ' days</small>';
                                                                        }
                                                                    }
                                                                    echo '<div class="text-muted" style="font-size: 0.85rem;">';
                                                                    echo '<small>Last passed: ' . $lastPassedDate . '</small>';
                                                                    echo '</div>';
                                                                } else {
                                                                    // No previous success (should not happen normally)
                                                                    echo '<span class="text-muted">No certificate issued yet</span>';
                                                                }
                                                            } else {
                                                                // NEVER PASSED - No certificate at all
                                                                echo '<span class="text-muted">No certificate issued yet</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php elseif ($isAdmin && !$isFilterSelected): ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        Please select at least one filter to view results.
                                    </div>
                                <?php elseif ($isOfflineManager && !$isFilterSelected): ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        Please select an Associate, or check "Search by Course" and select a Course to view results.
                                    </div>
                                <?php elseif (empty($data) && !$isRegularNonAdmin): ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        No records found for the selected filters.
                                    </div>
                                <?php elseif ($isRegularNonAdmin && empty($data)): ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        No records found. You may not have attempted any courses yet.
                                    </div>
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
            <?php if (!empty($data)) : ?>
                $('#coursesTable').DataTable({
                    "order": [],
                    "pageLength": 25,
                    "language": {
                        "emptyTable": "No data available"
                    }
                });
            <?php endif; ?>

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Reset Filters Button
        $(document).ready(function() {
            $('#resetFilters').on('click', function() {
                window.location.href = window.location.pathname;
            });
        });
    </script>

</body>

</html>