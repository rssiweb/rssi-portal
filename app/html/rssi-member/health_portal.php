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

// Function to calculate academic year from date
function getAcademicYear($date)
{
    $year = date('Y', strtotime($date));
    $month = date('n', strtotime($date));
    return ($month >= 4) ? $year . '-' . ($year + 1) : ($year - 1) . '-' . $year;
}

// Get current academic year
$currentYear = date('Y');
$currentMonth = date('n');
$academicYear = ($currentMonth >= 4) ? $currentYear . '-' . ($currentYear + 1) : ($currentYear - 1) . '-' . $currentYear;

// Get the selected academic year or use current as default
$selectedAcademicYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : $academicYear;
list($startYear, $endYear) = explode('-', $selectedAcademicYear);
$academicYearStart = "$startYear-04-01";
$academicYearEnd = "$endYear-03-31";

// Common functions
function getCurrentUrlWithTab($tab)
{
    $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    $params['tab'] = $tab;
    return $currentUrl . '?' . http_build_query($params);
}

function getAcademicYearOptions($currentYear, $currentMonth, $selectedYear = null)
{
    $options = '';
    for ($i = 0; $i < 3; $i++) {
        $year = $currentYear - $i;
        $ay = ($currentMonth >= 4) ? $year . '-' . ($year + 1) : ($year - 1) . '-' . $year;
        $selected = ($selectedYear == $ay) ? 'selected' : '';
        $options .= "<option value='$ay' $selected>$ay</option>";
    }
    return $options;
}

// Determine active tab from URL
$activeTab = 'dashboard';
if (isset($_GET['tab'])) {
    $requestedTab = $_GET['tab'];
    if (in_array($requestedTab, ['dashboard', 'health-records', 'period-tracking', 'pad-distribution', 'reports'])) {
        $activeTab = $requestedTab;
    }
}

// Fetch classes for filters
$classesQuery = "SELECT DISTINCT class FROM rssimyprofile_student WHERE filterstatus='Active' ORDER BY class";
$classesResult = pg_query($con, $classesQuery);
$classes = [];
while ($classRow = pg_fetch_assoc($classesResult)) {
    $classes[] = $classRow['class'];
}

// Build base queries for each tab with academic year filter
$baseHealthQuery = "SELECT sh.*, s.studentname, s.gender, s.class, st.fullname as recorded_by_name
                   FROM student_health_records sh
                   JOIN rssimyprofile_student s ON sh.student_id = s.student_id
                   JOIN rssimyaccount_members st ON sh.recorded_by = st.associatenumber
                   WHERE s.filterstatus='Active'
                   AND sh.record_date BETWEEN '$academicYearStart' AND '$academicYearEnd'";

$basePeriodQuery = "SELECT pr.*, s.studentname, s.class, st.fullname as recorded_by_name
                   FROM student_period_records pr
                   JOIN rssimyprofile_student s ON pr.student_id = s.student_id
                   JOIN rssimyaccount_members st ON pr.recorded_by = st.associatenumber
                   WHERE s.gender = 'Female'
                   AND s.filterstatus='Active'
                   AND pr.cycle_start_date BETWEEN '$academicYearStart' AND '$academicYearEnd'";

$basePadQuery = "SELECT pd.*, s.studentname, s.class, st.fullname as recorded_by_name
                FROM stock_out pd
                JOIN rssimyprofile_student s ON pd.distributed_to = s.student_id
                JOIN rssimyaccount_members st ON pd.distributed_by = st.associatenumber
                WHERE s.filterstatus='Active'
                AND pd.item_distributed=149
                AND pd.date BETWEEN '$academicYearStart' AND '$academicYearEnd'";

// Initialize variables for all tabs
$healthResult = $periodResult = $padResult = $healthFilterResult = $periodFilterResult = $padFilterResult = null;

// Fetch stats for dashboard (always fetch these)
$statsQuery = "SELECT 
                (SELECT COUNT(*) FROM rssimyprofile_student WHERE filterstatus='Active') as total_students,
                (SELECT COUNT(*) FROM student_health_records 
                 WHERE record_date BETWEEN '$academicYearStart' AND '$academicYearEnd') as yearly_checks,
                (SELECT COUNT(*) FROM stock_out
                 WHERE date BETWEEN '$academicYearStart' AND '$academicYearEnd' AND item_distributed=149) as yearly_pads";
$statsResult = pg_query($con, $statsQuery);
$stats = pg_fetch_assoc($statsResult);

// Fetch data based on active tab
// echo $activeTab;

if ($activeTab == 'dashboard') {
    $healthQuery = $baseHealthQuery . " ORDER BY sh.record_date DESC LIMIT 5";
    $healthResult = pg_query($con, $healthQuery);

    $periodQuery = $basePeriodQuery . " ORDER BY pr.cycle_start_date DESC LIMIT 10";
    $periodResult = pg_query($con, $periodQuery);

    $padQuery = $basePadQuery . " ORDER BY pd.date DESC LIMIT 10";
    $padResult = pg_query($con, $padQuery);
} elseif ($activeTab == 'health-records') {
    $healthFilterQuery = $baseHealthQuery;

    if (!empty($_GET['class'])) {
        $class = pg_escape_string($con, $_GET['class']);
        $healthFilterQuery .= " AND s.class = '$class'";
    }

    if (!empty($_GET['search'])) {
        $search = pg_escape_string($con, $_GET['search']);
        $healthFilterQuery .= " AND (s.studentname ILIKE '%$search%' OR s.student_id::text ILIKE '%$search%')";
    }

    $healthFilterQuery .= " ORDER BY sh.created_at DESC";
    $healthFilterResult = pg_query($con, $healthFilterQuery);

    // Store results in an array for easier debugging
    $healthRecords = [];
    while ($row = pg_fetch_assoc($healthFilterResult)) {
        $healthRecords[] = $row;
    }

    // DEBUGGING - Show fetched data (use one of the following options)

    // Option 1: Use print_r() to display the raw array
    // echo "<pre>";
    // print_r($healthRecords);
    // echo "</pre>";

    // Option 2: Use var_dump() for detailed debugging
    // echo "<pre>";
    // var_dump($healthRecords);
    // echo "</pre>";

    // Option 3: Display the data in a table format for better readability
    // echo "<table border='1'>";
    // echo "<tr><th>Record Date</th><th>Student Name</th><th>Student ID</th></tr>";
    // foreach ($healthRecords as $record) {
    //     echo "<tr>";
    //     echo "<td>" . htmlspecialchars($record['record_date']) . "</td>";
    //     echo "<td>" . htmlspecialchars($record['studentname']) . "</td>";
    //     echo "<td>" . htmlspecialchars($record['student_id']) . "</td>";
    //     echo "</tr>";
    // }
    // echo "</table>";
} elseif ($activeTab == 'period-tracking') {
    $periodFilterQuery = $basePeriodQuery;

    if (isset($_GET['class']) && $_GET['class'] != '') {
        $periodFilterQuery .= " AND s.class = '" . pg_escape_string($con, $_GET['class']) . "'";
    }

    if (isset($_GET['month']) && $_GET['month'] != '') {
        $month = date('Y-m', strtotime($_GET['month']));
        $periodFilterQuery .= " AND (pr.cycle_start_date >= '$month-01' AND pr.cycle_start_date <= '$month-31')";
    }

    if (isset($_GET['search']) && $_GET['search'] != '') {
        $search = pg_escape_string($con, $_GET['search']);
        $periodFilterQuery .= " AND (s.studentname ILIKE '%$search%')";
    }

    $periodFilterQuery .= " ORDER BY pr.created_at DESC";
    $periodFilterResult = pg_query($con, $periodFilterQuery);
} elseif ($activeTab == 'pad-distribution') {
    $padFilterQuery = $basePadQuery . " AND s.gender = 'Female'";

    if (isset($_GET['class']) && $_GET['class'] != '') {
        $padFilterQuery .= " AND s.class = '" . pg_escape_string($con, $_GET['class']) . "'";
    }

    if (isset($_GET['month']) && $_GET['month'] != '') {
        $month = $_GET['month'];
        $padFilterQuery .= " AND EXTRACT(MONTH FROM pd.date) = $month";
    }

    $padFilterQuery .= " ORDER BY pd.timestamp DESC";
    $padFilterResult = pg_query($con, $padFilterQuery);
} elseif ($activeTab == 'reports') {
    if (isset($_GET['student_id']) && $_GET['student_id'] != '') {
        $studentId = pg_escape_string($con, $_GET['student_id']);
        $metric = isset($_GET['metric']) ? pg_escape_string($con, $_GET['metric']) : 'height';

        // Get student info
        $studentQuery = "SELECT studentname, class 
                        FROM rssimyprofile_student 
                        WHERE student_id = '$studentId'";
        $studentResult = pg_query($con, $studentQuery);
        $student = pg_fetch_assoc($studentResult);

        // Get health records for this student
        $recordsQuery = "SELECT record_date, height_cm, weight_kg, bmi 
                        FROM student_health_records 
                        WHERE student_id = '$studentId'
                        AND record_date BETWEEN '$academicYearStart' AND '$academicYearEnd'
                        ORDER BY record_date";
        $recordsResult = pg_query($con, $recordsQuery);

        $labels = [];
        $data = [];

        while ($record = pg_fetch_assoc($recordsResult)) {
            $labels[] = date('M Y', strtotime($record['record_date']));

            if ($metric == 'height') {
                $data[] = $record['height_cm'];
            } elseif ($metric == 'weight') {
                $data[] = $record['weight_kg'];
            } else {
                $data[] = $record['bmi'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Health Records Portal</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
            min-height: 100vh;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 5px;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link i {
            margin-right: 10px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
        }

        .chart-container {
            height: 300px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* .tab-content>.tab-pane {
            display: none;
        } */

        /* .tab-content>.active {
            display: block;
        } */
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse bg-primary">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">Health Portal</h4>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activeTab == 'dashboard') ? 'active' : ''; ?>"
                                href="<?php echo getCurrentUrlWithTab('dashboard'); ?>"
                                data-tab-target="dashboard">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activeTab == 'health-records') ? 'active' : ''; ?>"
                                href="<?php echo getCurrentUrlWithTab('health-records'); ?>"
                                data-tab-target="health-records">
                                <i class="bi bi-clipboard2-pulse"></i> Health Records
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activeTab == 'period-tracking') ? 'active' : ''; ?>"
                                href="<?php echo getCurrentUrlWithTab('period-tracking'); ?>"
                                data-tab-target="period-tracking">
                                <i class="bi bi-calendar-heart"></i> Period Tracking
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activeTab == 'pad-distribution') ? 'active' : ''; ?>"
                                href="<?php echo getCurrentUrlWithTab('pad-distribution'); ?>"
                                data-tab-target="pad-distribution">
                                <i class="bi bi-box-seam"></i> Sanitary Pads
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activeTab == 'reports') ? 'active' : ''; ?>"
                                href="<?php echo getCurrentUrlWithTab('reports'); ?>"
                                data-tab-target="reports">
                                <i class="bi bi-graph-up"></i> Reports
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Student Health Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="academicYearDropdown" data-bs-toggle="dropdown">
                                Academic Year: <span id="currentAcademicYear"><?php echo $selectedAcademicYear; ?></span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="academicYearDropdown">
                                <?php
                                for ($i = 0; $i < 3; $i++) {
                                    $year = $currentYear - $i;
                                    $ay = ($currentMonth >= 4) ? $year . '-' . ($year + 1) : ($year - 1) . '-' . $year;
                                    $activeClass = ($selectedAcademicYear == $ay) ? 'active' : '';
                                    echo '<li><a class="dropdown-item ' . $activeClass . '" href="#" data-year="' . $ay . '">' . $ay . '</a></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>


                    <!-- <div class="alert alert-info">
                        <h5>Debug Information:</h5>
                        <p>Active Tab: <?php echo $activeTab; ?></p>
                        <p>Academic Year: <?php echo $selectedAcademicYear; ?> (<?php echo $academicYearStart ?> to <?php echo $academicYearEnd ?>)</p>
                        <?php if (isset($healthFilterQuery)): ?>
                            <p>Query: <?php echo htmlspecialchars($healthFilterQuery); ?></p>
                            <p>Result Count: <?php echo pg_num_rows($healthFilterResult); ?></p>
                        <?php endif; ?>
                        <p>Filters:
                            Class: <?php echo isset($_GET['class']) ? htmlspecialchars($_GET['class']) : 'None'; ?>,
                            Search: <?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : 'None'; ?>
                        </p>
                        <?php
                        // Test query directly
                        $testQuery = "SELECT COUNT(*) FROM student_health_records 
                            WHERE record_date BETWEEN '$academicYearStart' AND '$academicYearEnd'";
                        $testResult = pg_query($con, $testQuery);
                        $testCount = pg_fetch_result($testResult, 0, 0);
                        ?>
                        <p>Total records in date range: <?php echo $testCount; ?></p>
                    </div> -->

                </div>

                <div class="tab-content">
                    <!-- Dashboard Tab -->
                    <div class="tab-pane fade <?php echo $activeTab == 'dashboard' ? 'show active' : ''; ?>" id="dashboard">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card text-white bg-success mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="card-title">Total Students</h6>
                                                <h2 class="card-text"><?php echo $stats['total_students']; ?></h2>
                                            </div>
                                            <i class="bi bi-people-fill fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-white bg-info mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="card-title">Health Checks (<?php echo $selectedAcademicYear; ?>)</h6>
                                                <h2 class="card-text"><?php echo $stats['yearly_checks']; ?></h2>
                                            </div>
                                            <i class="bi bi-clipboard2-pulse-fill fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-white bg-warning mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="card-title">Pads Distributed (<?php echo $selectedAcademicYear; ?>)</h6>
                                                <h2 class="card-text"><?php echo $stats['yearly_pads']; ?></h2>
                                            </div>
                                            <i class="bi bi-box-seam-fill fs-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Student Growth Trends (<?php echo $selectedAcademicYear; ?>)</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="growthChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Recent Health Checks</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="list-group">
                                            <?php
                                            if (!$healthResult) {
                                                echo '<div class="alert alert-danger">Query error</div>';
                                            } elseif (pg_num_rows($healthResult) == 0) {
                                                echo '<div class="alert alert-info">No records found</div>';
                                            } else {
                                                while ($row = pg_fetch_assoc($healthResult)) {
                                                    echo '<a href="#" class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1">' . htmlspecialchars($row['studentname']) . '</h6>
                                                        <small>' . date('M d', strtotime($row['record_date'])) . '</small>
                                                    </div>
                                                    <p class="mb-1">Class: ' . htmlspecialchars($row['class']) . '</p>
                                                    <small>' . htmlspecialchars($row['recorded_by_name']) . '</small>
                                                </a>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Health Records Tab -->
                    <div class="tab-pane fade <?php echo $activeTab == 'health-records' ? 'show active' : ''; ?>" id="health-records">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Student Health Records (<?php echo $selectedAcademicYear; ?>)</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addHealthRecordModal">
                                    <i class="bi bi-plus"></i> Add Record
                                </button>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="">
                                    <input type="hidden" name="tab" value="health-records">
                                    <input type="hidden" name="academic_year" value="<?php echo $selectedAcademicYear; ?>">
                                    <!-- Add current filter values as hidden fields if they exist -->
                                    <?php if (isset($_GET['class'])) : ?>
                                        <input type="hidden" name="class" value="<?php echo htmlspecialchars($_GET['class']); ?>">
                                    <?php endif; ?>
                                    <?php if (isset($_GET['search'])) : ?>
                                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
                                    <?php endif; ?>


                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <select class="form-select" name="class">
                                                <option value="">All Classes</option>
                                                <?php
                                                foreach ($classes as $class) {
                                                    $selected = (isset($_GET['class']) && $_GET['class'] == $class) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($class) . '" ' . $selected . '>' . htmlspecialchars($class) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" name="search" placeholder="Search student..."
                                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                            <a href="<?php echo getCurrentUrlWithTab('health-records'); ?>" class="btn btn-outline-secondary">Reset</a>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Date</th>
                                                <th>Height (cm)</th>
                                                <th>Weight (kg)</th>
                                                <th>BMI</th>
                                                <th>BP</th>
                                                <th>Vision</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($healthRecords)): ?>
                                                <?php foreach ($healthRecords as $row): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['studentname']) ?>&background=random" class="avatar me-2">
                                                                <div>
                                                                    <h6 class="mb-0"><?= htmlspecialchars($row['studentname']) ?></h6>
                                                                    <small class="text-muted"><?= htmlspecialchars($row['class']) ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?= date('d M Y', strtotime($row['record_date'])) ?></td>
                                                        <td><?= htmlspecialchars($row['height_cm']) ?></td>
                                                        <td><?= htmlspecialchars($row['weight_kg']) ?></td>
                                                        <td><?= htmlspecialchars($row['bmi']) ?></td>
                                                        <td><?= htmlspecialchars($row['blood_pressure']) ?></td>
                                                        <td><?= htmlspecialchars($row['vision_left']) ?>/<?= htmlspecialchars($row['vision_right']) ?></td>
                                                        <td>
                                                            <button class="btn btn-action btn-outline-primary view-record"
                                                                data-type="health"
                                                                data-id="<?= $row['id'] ?>"
                                                                title="View">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            <button class="btn btn-action btn-outline-secondary edit-record"
                                                                data-type="health"
                                                                data-id="<?= $row['id'] ?>"
                                                                title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">
                                                        <?php if (isset($healthFilterQuery)): ?>
                                                            No records found matching your criteria
                                                        <?php else: ?>
                                                            No health records available
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Period Tracking Tab -->
                    <div class="tab-pane fade <?php echo $activeTab == 'period-tracking' ? 'show active' : ''; ?>" id="period-tracking">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Period Tracking Records (<?php echo $selectedAcademicYear; ?>)</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPeriodRecordModal">
                                    <i class="bi bi-plus"></i> Add Record
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Only female students are shown in this section.
                                </div>

                                <form method="GET" action="">
                                    <input type="hidden" name="tab" value="period-tracking">
                                    <input type="hidden" name="academic_year" value="<?php echo $selectedAcademicYear; ?>">

                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <select class="form-select" name="class">
                                                <option value="">All Classes</option>
                                                <?php
                                                foreach ($classes as $class) {
                                                    $selected = (isset($_GET['class']) && $_GET['class'] == $class) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($class) . '" ' . $selected . '>' . htmlspecialchars($class) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="month" class="form-control" name="month"
                                                value="<?php echo isset($_GET['month']) ? htmlspecialchars($_GET['month']) : ''; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" name="search" placeholder="Search student..."
                                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                            <a href="<?php echo getCurrentUrlWithTab('period-tracking'); ?>" class="btn btn-outline-secondary">Reset</a>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Cycle Start</th>
                                                <th>Cycle End</th>
                                                <th>Symptoms</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (isset($periodFilterResult)) {
                                                while ($row = pg_fetch_assoc($periodFilterResult)) {
                                                    echo '<tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="https://ui-avatars.com/api/?name=' . urlencode($row['studentname']) . '&background=random" class="avatar me-2">
                                                                <div>
                                                                    <h6 class="mb-0">' . htmlspecialchars($row['studentname']) . '</h6>
                                                                    <small class="text-muted">' . htmlspecialchars($row['class']) . '</small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>' . date('d M Y', strtotime($row['cycle_start_date'])) . '</td>
                                                        <td>' . ($row['cycle_end_date'] ? date('d M Y', strtotime($row['cycle_end_date'])) : 'Ongoing') . '</td>
                                                        <td>' . htmlspecialchars(substr($row['symptoms'], 0, 30)) . '...</td>' ?>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <button class="btn btn-action btn-outline-primary view-record"
                                                                data-type="period"
                                                                data-id="<?= $row['id'] ?>"
                                                                title="View">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            <button class="btn btn-action btn-outline-info add-symptoms"
                                                                data-id="<?= $row['id'] ?>"
                                                                title="Add Symptoms">
                                                                <i class="bi bi-plus-circle"></i>
                                                            </button>
                                                            <button class="btn btn-action btn-outline-secondary edit-record"
                                                                data-type="period"
                                                                data-id="<?= $row['id'] ?>"
                                                                title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                    </td>
                                            <?php '</tr>';
                                                }

                                                if (pg_num_rows($periodFilterResult) == 0) {
                                                    echo '<tr><td colspan="5" class="text-center">No records found</td></tr>';
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sanitary Pad Distribution Tab -->
                    <div class="tab-pane fade <?php echo $activeTab == 'pad-distribution' ? 'show active' : ''; ?>" id="pad-distribution">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Sanitary Pad Distribution (<?php echo $selectedAcademicYear; ?>)</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPadDistributionModal">
                                    <i class="bi bi-plus"></i> Add Distribution
                                </button>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="">
                                    <input type="hidden" name="tab" value="pad-distribution">
                                    <input type="hidden" name="academic_year" value="<?php echo $selectedAcademicYear; ?>">

                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <select class="form-select" name="class">
                                                <option value="">All Classes</option>
                                                <?php
                                                foreach ($classes as $class) {
                                                    $selected = (isset($_GET['class']) && $_GET['class'] == $class) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($class) . '" ' . $selected . '>' . htmlspecialchars($class) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select" name="month">
                                                <option value="">All Months</option>
                                                <?php
                                                $months = [
                                                    1 => 'January',
                                                    2 => 'February',
                                                    3 => 'March',
                                                    4 => 'April',
                                                    5 => 'May',
                                                    6 => 'June',
                                                    7 => 'July',
                                                    8 => 'August',
                                                    9 => 'September',
                                                    10 => 'October',
                                                    11 => 'November',
                                                    12 => 'December'
                                                ];

                                                foreach ($months as $num => $name) {
                                                    $selected = (isset($_GET['month']) && $_GET['month'] == $num) ? 'selected' : '';
                                                    echo '<option value="' . $num . '" ' . $selected . '>' . $name . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                            <a href="<?php echo getCurrentUrlWithTab('pad-distribution'); ?>" class="btn btn-outline-secondary">Reset</a>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Distribution Date</th>
                                                <th>Quantity</th>
                                                <th>Academic Year</th>
                                                <th>Recorded By</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if (isset($padFilterResult)) {
                                                while ($row = pg_fetch_assoc($padFilterResult)) {
                                                    $academicYear = getAcademicYear($row['date']);

                                                    echo '<tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="https://ui-avatars.com/api/?name=' . urlencode($row['studentname']) . '&background=random" class="avatar me-2">
                                                                <div>
                                                                    <h6 class="mb-0">' . htmlspecialchars($row['studentname']) . '</h6>
                                                                    <small class="text-muted">' . htmlspecialchars($row['class']) . '</small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>' . date('d M Y', strtotime($row['date'])) . '</td>
                                                        <td>' . $row['quantity_distributed'] . '</td>
                                                        <td>' . $academicYear . '</td>
                                                        <td>' . htmlspecialchars($row['recorded_by_name']) . '</td>' ?>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <button class="btn btn-action btn-outline-primary view-record"
                                                                data-type="pad"
                                                                data-id="<?= $row['transaction_out_id'] ?>"
                                                                title="View">
                                                                <i class="bi bi-eye"></i>
                                                            </button>
                                                            <button class="btn btn-action btn-outline-success distribute-again"
                                                                data-id="<?= $row['distributed_to'] ?>"
                                                                title="Distribute Again">
                                                                <i class="bi bi-box-seam"></i>
                                                            </button>
                                                            <button class="btn btn-action btn-outline-secondary edit-record"
                                                                data-type="pad"
                                                                data-id="<?= $row['transaction_out_id'] ?>"
                                                                title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                    </td>
                                            <?php '</tr>';
                                                }

                                                if (pg_num_rows($padFilterResult) == 0) {
                                                    echo '<tr><td colspan="6" class="text-center">No records found</td></tr>';
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reports Tab -->
                    <div class="tab-pane fade <?php echo $activeTab == 'reports' ? 'show active' : ''; ?>" id="reports">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Health Reports (<?php echo $selectedAcademicYear; ?>)</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Growth Comparison</h5>
                                                <p class="card-text">Compare student growth metrics over time.</p>
                                                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#growthReportModal">
                                                    Generate Report
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Period Tracking Summary</h5>
                                                <p class="card-text">Summary of menstrual cycles and patterns.</p>
                                                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#periodReportModal">
                                                    Generate Report
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Pad Distribution</h5>
                                                <p class="card-text">Monthly and yearly distribution reports.</p>
                                                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#padReportModal">
                                                    Generate Report
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Student Growth Trends</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="GET" action="">
                                            <input type="hidden" name="tab" value="reports">
                                            <input type="hidden" name="academic_year" value="<?php echo $selectedAcademicYear; ?>">

                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <select class="form-select" name="student_id" required>
                                                        <option value="">Select Student</option>
                                                        <?php
                                                        $studentsQuery = "SELECT student_id, studentname, class 
                                                                        FROM rssimyprofile_student 
                                                                        WHERE filterstatus='Active'
                                                                        ORDER BY class, studentname";
                                                        $studentsResult = pg_query($con, $studentsQuery);

                                                        while ($student = pg_fetch_assoc($studentsResult)) {
                                                            $selected = (isset($_GET['student_id']) && $_GET['student_id'] == $student['student_id']) ? 'selected' : '';
                                                            echo '<option value="' . htmlspecialchars($student['student_id']) . '" ' . $selected . '>'
                                                                . htmlspecialchars($student['studentname']) . ' (' . htmlspecialchars($student['class']) . ')</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <select class="form-select" name="metric">
                                                        <option value="height" <?php echo (isset($_GET['metric']) && $_GET['metric'] == 'height') ? 'selected' : ''; ?>>Height</option>
                                                        <option value="weight" <?php echo (isset($_GET['metric']) && $_GET['metric'] == 'weight') ? 'selected' : ''; ?>>Weight</option>
                                                        <option value="bmi" <?php echo (isset($_GET['metric']) && $_GET['metric'] == 'bmi') ? 'selected' : ''; ?>>BMI</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <button type="submit" class="btn btn-primary">Generate Chart</button>
                                                </div>
                                            </div>
                                        </form>

                                        <?php
                                        if (isset($_GET['student_id']) && $_GET['student_id'] != '') {
                                        ?>
                                            <div class="chart-container">
                                                <canvas id="studentGrowthChart"></canvas>
                                            </div>

                                            <script>
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    const ctx = document.getElementById('studentGrowthChart').getContext('2d');
                                                    const chart = new Chart(ctx, {
                                                        type: 'line',
                                                        data: {
                                                            labels: <?php echo json_encode($labels); ?>,
                                                            datasets: [{
                                                                label: '<?php echo ucfirst($metric); ?> (<?php echo $metric == "height" ? "cm" : ($metric == "weight" ? "kg" : ""); ?>)',
                                                                data: <?php echo json_encode($data); ?>,
                                                                borderColor: 'rgba(78, 115, 223, 1)',
                                                                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                                                                tension: 0.1
                                                            }]
                                                        },
                                                        options: {
                                                            responsive: true,
                                                            maintainAspectRatio: false,
                                                            scales: {
                                                                y: {
                                                                    beginAtZero: false
                                                                }
                                                            }
                                                        }
                                                    });
                                                });
                                            </script>
                                        <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Health Record Modal -->
    <div class="modal fade" id="addHealthRecordModal" tabindex="-1" aria-labelledby="addHealthRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addHealthRecordModalLabel">Add Health Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="save_health_record.php" method="POST">
                    <input type="hidden" name="source_tab" value="<?php echo isset($_GET['tab']) ? htmlspecialchars($_GET['tab']) : 'health-records'; ?>">
                    <input type="hidden" name="academic_year" value="<?php echo isset($_GET['academic_year']) ? htmlspecialchars($_GET['academic_year']) : ''; ?>">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="studentSelect" class="form-label">Student</label>
                                <select class="form-select" id="studentSelect" name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php
                                    $studentsQuery = "SELECT student_id, studentname, class 
                                                     FROM rssimyprofile_student 
                                                     WHERE filterstatus='Active'
                                                     ORDER BY class, studentname";
                                    $studentsResult = pg_query($con, $studentsQuery);

                                    while ($student = pg_fetch_assoc($studentsResult)) {
                                        echo '<option value="' . htmlspecialchars($student['student_id']) . '">'
                                            . htmlspecialchars($student['studentname']) . ' (' . htmlspecialchars($student['class']) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="recordDate" class="form-label">Record Date</label>
                                <input type="date" class="form-control" id="recordDate" name="record_date" required
                                    value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="height" class="form-label">Height (cm)</label>
                                <input type="number" step="0.1" class="form-control" id="height" name="height_cm">
                            </div>
                            <div class="col-md-4">
                                <label for="weight" class="form-label">Weight (kg)</label>
                                <input type="number" step="0.1" class="form-control" id="weight" name="weight_kg">
                            </div>
                            <div class="col-md-4">
                                <label for="bmi" class="form-label">BMI</label>
                                <input type="number" step="0.1" class="form-control" id="bmi" name="bmi" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="bloodPressure" class="form-label">Blood Pressure</label>
                                <input type="text" class="form-control" id="bloodPressure" name="blood_pressure" placeholder="e.g. 120/80">
                            </div>
                            <div class="col-md-3">
                                <label for="visionLeft" class="form-label">Vision (Left)</label>
                                <input type="text" class="form-control" id="visionLeft" name="vision_left" placeholder="e.g. 20/20">
                            </div>
                            <div class="col-md-3">
                                <label for="visionRight" class="form-label">Vision (Right)</label>
                                <input type="text" class="form-control" id="visionRight" name="vision_right" placeholder="e.g. 20/20">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="healthNotes" class="form-label">General Health Notes</label>
                            <textarea class="form-control" id="healthNotes" name="health_notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Period Record Modal -->
    <div class="modal fade" id="addPeriodRecordModal" tabindex="-1" aria-labelledby="addPeriodRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPeriodRecordModalLabel">Add Period Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="save_period_record.php" method="POST">
                    <!-- Add these hidden fields at the top of your form -->
                    <input type="hidden" name="current_tab" value="period-tracking">
                    <?php if (isset($_GET['academic_year'])): ?>
                        <input type="hidden" name="academic_year" value="<?= htmlspecialchars($_GET['academic_year']) ?>">
                    <?php endif; ?>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="periodStudentSelect" class="form-label">Student</label>
                                <select class="form-select" id="periodStudentSelect" name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php
                                    $femaleStudentsQuery = "SELECT student_id, studentname, class 
                                                          FROM rssimyprofile_student 
                                                          WHERE gender = 'Female'
                                                          AND filterstatus='Active'
                                                          ORDER BY class, studentname";
                                    $femaleStudentsResult = pg_query($con, $femaleStudentsQuery);

                                    while ($student = pg_fetch_assoc($femaleStudentsResult)) {
                                        echo '<option value="' . $student['student_id'] . '">'
                                            . htmlspecialchars($student['studentname'] . ' (' . $student['class'] . ')')
                                            . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="recordDatePeriod" class="form-label">Record Date</label>
                                <input type="date" class="form-control" id="recordDatePeriod" name="record_date" required
                                    value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cycleStartDate" class="form-label">Cycle Start Date</label>
                                <input type="date" class="form-control" id="cycleStartDate" name="cycle_start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="cycleEndDate" class="form-label">Cycle End Date</label>
                                <input type="date" class="form-control" id="cycleEndDate" name="cycle_end_date">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="symptoms" class="form-label">Symptoms</label>
                            <textarea class="form-control" id="symptoms" name="symptoms" rows="2" placeholder="e.g. Cramps, headache, etc."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="periodNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="periodNotes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Pad Distribution Modal -->
    <div class="modal fade" id="addPadDistributionModal" tabindex="-1" aria-labelledby="addPadDistributionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPadDistributionModalLabel">Record Sanitary Pad Distribution</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="save_pad_distribution.php" method="POST">
                    <!-- Add hidden fields to preserve URL parameters -->
                    <input type="hidden" name="current_tab" value="pad-distribution">
                    <?php if (isset($_GET['academic_year'])): ?>
                        <input type="hidden" name="academic_year" value="<?= htmlspecialchars($_GET['academic_year']) ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['class'])): ?>
                        <input type="hidden" name="current_class" value="<?= htmlspecialchars($_GET['class']) ?>">
                    <?php endif; ?>
                    <?php if (isset($_GET['month'])): ?>
                        <input type="hidden" name="current_month" value="<?= htmlspecialchars($_GET['month']) ?>">
                    <?php endif; ?>

                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="padStudentSelect" class="form-label">Students</label>
                                <select class="form-select" id="padStudentSelect" name="student_ids[]" multiple="multiple" required>
                                    <?php
                                    $femaleStudentsQuery = "SELECT student_id, studentname, class 
                                          FROM rssimyprofile_student 
                                          WHERE gender = 'Female'
                                          AND filterstatus='Active'
                                          ORDER BY class, studentname";
                                    $femaleStudentsResult = pg_query($con, $femaleStudentsQuery);

                                    while ($student = pg_fetch_assoc($femaleStudentsResult)) {
                                        echo '<option value="' . $student['student_id'] . '">'
                                            . htmlspecialchars($student['studentname'] . ' (' . $student['class'] . ')')
                                            . '</option>';
                                    }
                                    ?>
                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple students</small>
                            </div>
                            <div class="col-md-6">
                                <label for="distributionDate" class="form-label">Distribution Date</label>
                                <input type="date" class="form-control" id="distributionDate" name="distribution_date" required
                                    value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="padQuantity" class="form-label">Quantity per Student</label>
                            <input type="number" class="form-control" id="padQuantity" name="quantity" value="1" min="1">
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bulkDistribution" name="bulk_distribution">
                                <label class="form-check-label" for="bulkDistribution">
                                    Bulk distribution to entire class
                                </label>
                            </div>
                        </div>

                        <div class="mb-3" id="bulkDistributionOptions" style="display: none;">
                            <label for="bulkClassSelect" class="form-label">Select Class</label>
                            <select class="form-select" id="bulkClassSelect" name="bulk_class">
                                <option value="">Select Class</option>
                                <?php
                                foreach ($classes as $class) {
                                    echo '<option value="' . htmlspecialchars($class) . '">' . htmlspecialchars($class) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Distribution</button>
                    </div>
                </form>

                <!-- Add this script to enhance the multi-select -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Initialize the multi-select dropdown
                        $('#padStudentSelect').select2({
                            placeholder: "Select students",
                            allowClear: true,
                            width: '100%'
                        });

                        // Toggle bulk distribution options
                        document.getElementById('bulkDistribution').addEventListener('change', function() {
                            const bulkOptions = document.getElementById('bulkDistributionOptions');
                            const studentSelect = document.getElementById('padStudentSelect');

                            if (this.checked) {
                                bulkOptions.style.display = 'block';
                                studentSelect.disabled = true;
                                studentSelect.removeAttribute('required');
                                document.getElementById('bulkClassSelect').setAttribute('required', '');
                            } else {
                                bulkOptions.style.display = 'none';
                                studentSelect.disabled = false;
                                studentSelect.setAttribute('required', '');
                                document.getElementById('bulkClassSelect').removeAttribute('required');
                            }
                        });
                    });
                </script>
            </div>
        </div>
    </div>

    <!-- Growth Report Modal -->
    <div class="modal fade" id="growthReportModal" tabindex="-1" aria-labelledby="growthReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="growthReportModalLabel">Growth Comparison Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="generate_growth_report.php" method="POST" target="_blank">
                    <div class="modal-body">
                        <input type="hidden" name="academic_year" value="<?php echo $selectedAcademicYear; ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Class</label>
                                <select class="form-select" name="class">
                                    <option value="">All Classes</option>
                                    <?php
                                    foreach ($classes as $class) {
                                        echo '<option value="' . htmlspecialchars($class) . '">' . htmlspecialchars($class) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Metric</label>
                                <select class="form-select" name="metric">
                                    <option value="height">Height</option>
                                    <option value="weight">Weight</option>
                                    <option value="bmi">BMI</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="from_date" value="<?php echo $academicYearStart; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="to_date" value="<?php echo $academicYearEnd; ?>">
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="compareAcademicYears" name="compare_academic_years">
                            <label class="form-check-label" for="compareAcademicYears">
                                Compare with previous academic year
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Period Report Modal -->
    <div class="modal fade" id="periodReportModal" tabindex="-1" aria-labelledby="periodReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="periodReportModalLabel">Period Tracking Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="generate_period_report.php" method="POST" target="_blank">
                    <div class="modal-body">
                        <input type="hidden" name="academic_year" value="<?php echo $selectedAcademicYear; ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Class</label>
                                <select class="form-select" name="class">
                                    <option value="">All Classes</option>
                                    <?php
                                    foreach ($classes as $class) {
                                        echo '<option value="' . htmlspecialchars($class) . '">' . htmlspecialchars($class) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Report Type</label>
                                <select class="form-select" name="report_type">
                                    <option value="summary">Summary</option>
                                    <option value="detailed">Detailed</option>
                                    <option value="irregularities">Irregularities</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="from_date" value="<?php echo $academicYearStart; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="to_date" value="<?php echo $academicYearEnd; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pad Report Modal -->
    <div class="modal fade" id="padReportModal" tabindex="-1" aria-labelledby="padReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="padReportModalLabel">Sanitary Pad Distribution Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="generate_pad_report.php" method="POST" target="_blank">
                    <div class="modal-body">
                        <input type="hidden" name="academic_year" value="<?php echo $selectedAcademicYear; ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Class</label>
                                <select class="form-select" name="class">
                                    <option value="">All Classes</option>
                                    <?php
                                    foreach ($classes as $class) {
                                        echo '<option value="' . htmlspecialchars($class) . '">' . htmlspecialchars($class) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Report Type</label>
                                <select class="form-select" name="report_type">
                                    <option value="monthly">Monthly Summary</option>
                                    <option value="yearly">Yearly Summary</option>
                                    <option value="student">By Student</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" name="from_date" value="<?php echo $academicYearStart; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" name="to_date" value="<?php echo $academicYearEnd; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Generate Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap tooltips and popovers
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Tab switching functionality
            function handleTabNavigation() {
                const urlParams = new URLSearchParams(window.location.search);
                const tabParam = urlParams.get('tab') || 'dashboard';

                // Remove all active classes
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('show', 'active');
                });

                document.querySelectorAll('[data-tab-target]').forEach(link => {
                    link.classList.remove('active');
                });

                // Activate the correct tab
                const activePane = document.getElementById(tabParam);
                if (activePane) {
                    activePane.classList.add('show', 'active');
                }

                const activeLink = document.querySelector(`[data-tab-target="${tabParam}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                }

                // Load chart if dashboard
                if (tabParam === 'dashboard') {
                    loadGrowthChart();
                }
            }

            // Handle tab clicks - MODIFIED FOR FULL PAGE RELOADS
            document.querySelectorAll('[data-tab-target]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabId = this.getAttribute('data-tab-target');

                    // Get current URL parameters
                    const urlParams = new URLSearchParams(window.location.search);

                    // Update tab parameter
                    urlParams.set('tab', tabId);

                    // Force full page reload with new URL
                    window.location.href = window.location.pathname + '?' + urlParams.toString();
                });
            });

            // Handle back/forward navigation
            window.addEventListener('popstate', function() {
                handleTabNavigation();
            });

            // Academic year dropdown functionality
            document.querySelectorAll('.dropdown-item[data-year]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const selectedYear = this.getAttribute('data-year');

                    // Update URL with new academic year
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('academic_year', selectedYear);

                    // Force full page reload to ensure all data refreshes
                    window.location.href = window.location.pathname + '?' + urlParams.toString();
                });
            });

            // Form handling - preserve all parameters when submitting
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    // Get current URL parameters
                    const urlParams = new URLSearchParams(window.location.search);

                    // Add hidden inputs for tab and academic_year if they don't exist
                    if (!this.querySelector('input[name="tab"]')) {
                        const tabInput = document.createElement('input');
                        tabInput.type = 'hidden';
                        tabInput.name = 'tab';
                        tabInput.value = urlParams.get('tab') || 'dashboard';
                        this.appendChild(tabInput);
                    }

                    if (!this.querySelector('input[name="academic_year"]') && urlParams.get('academic_year')) {
                        const yearInput = document.createElement('input');
                        yearInput.type = 'hidden';
                        yearInput.name = 'academic_year';
                        yearInput.value = urlParams.get('academic_year');
                        this.appendChild(yearInput);
                    }
                });
            });

            // BMI calculation
            const heightInput = document.getElementById('height');
            const weightInput = document.getElementById('weight');
            const bmiInput = document.getElementById('bmi');

            if (heightInput && weightInput && bmiInput) {
                heightInput.addEventListener('input', calculateBMI);
                weightInput.addEventListener('input', calculateBMI);

                function calculateBMI() {
                    const height = parseFloat(heightInput.value) / 100;
                    const weight = parseFloat(weightInput.value);

                    if (height && weight) {
                        const bmi = (weight / (height * height)).toFixed(1);
                        bmiInput.value = bmi;
                    }
                }
            }

            // Toggle bulk distribution options
            const bulkDistributionCheckbox = document.getElementById('bulkDistribution');
            if (bulkDistributionCheckbox) {
                bulkDistributionCheckbox.addEventListener('change', function() {
                    const bulkOptions = document.getElementById('bulkDistributionOptions');
                    const studentSelect = document.getElementById('padStudentSelect');

                    if (this.checked) {
                        bulkOptions.style.display = 'block';
                        studentSelect.disabled = true;
                        studentSelect.removeAttribute('required');
                        document.getElementById('bulkClassSelect').setAttribute('required', '');
                    } else {
                        bulkOptions.style.display = 'none';
                        studentSelect.disabled = false;
                        studentSelect.setAttribute('required', '');
                        document.getElementById('bulkClassSelect').removeAttribute('required');
                    }
                });
            }

            // Load growth chart data
            function loadGrowthChart() {
                const growthCtx = document.getElementById('growthChart');
                if (!growthCtx) return;

                const academicYear = document.getElementById('currentAcademicYear').textContent;

                fetch(`get_chart_data.php?academic_year=${encodeURIComponent(academicYear)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.months && data.avg_height && data.avg_weight) {
                            new Chart(growthCtx, {
                                type: 'line',
                                data: {
                                    labels: data.months,
                                    datasets: [{
                                            label: 'Average Height (cm)',
                                            data: data.avg_height,
                                            borderColor: 'rgba(78, 115, 223, 1)',
                                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                                            tension: 0.1
                                        },
                                        {
                                            label: 'Average Weight (kg)',
                                            data: data.avg_weight,
                                            borderColor: 'rgba(54, 185, 204, 1)',
                                            backgroundColor: 'rgba(54, 185, 204, 0.05)',
                                            tension: 0.1
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: false
                                        }
                                    }
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading chart data:', error);
                    });
            }

            // Initialize student growth chart if on reports tab with student selected
            <?php if ($activeTab == 'reports' && isset($_GET['student_id']) && $_GET['student_id'] != ''): ?>
                const studentGrowthCtx = document.getElementById('studentGrowthChart');
                if (studentGrowthCtx) {
                    new Chart(studentGrowthCtx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($labels); ?>,
                            datasets: [{
                                label: '<?php echo ucfirst($metric); ?> (<?php echo $metric == "height" ? "cm" : ($metric == "weight" ? "kg" : ""); ?>)',
                                data: <?php echo json_encode($data); ?>,
                                borderColor: 'rgba(78, 115, 223, 1)',
                                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                                tension: 0.1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: false
                                }
                            }
                        }
                    });
                }
            <?php endif; ?>

            // Initialize the correct tab on page load
            handleTabNavigation();
        });
    </script>
    <!-- View Record Modal (for all record types) -->
    <div class="modal fade" id="viewRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalTitle">Record Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewRecordContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="printRecordBtn"><i class="fas fa-print"></i> Print</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Record Modal (for all record types) -->
    <div class="modal fade" id="editRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalTitle">Edit Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editRecordForm">
                    <div class="modal-body" id="editRecordContent">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add Symptoms Modal -->
    <div class="modal fade" id="addSymptomsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Symptoms</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="add_symptoms.php" method="POST">
                    <input type="hidden" name="record_id" id="periodRecordId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Symptoms</label>
                            <textarea class="form-control" name="symptoms" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Symptoms</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const viewModal = new bootstrap.Modal(document.getElementById('viewRecordModal'));
            const editModal = new bootstrap.Modal(document.getElementById('editRecordModal'));

            // View Record Handler
            document.querySelectorAll('.view-record').forEach(btn => {
                btn.addEventListener('click', function() {
                    const recordType = this.getAttribute('data-type');
                    const recordId = this.getAttribute('data-id');

                    // Show spinner and clear previous content
                    document.getElementById('viewRecordContent').innerHTML = `
                    <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>`;

                    document.getElementById('viewModalTitle').textContent = `Loading ${recordType.charAt(0).toUpperCase() + recordType.slice(1)} Record...`;
                    viewModal.show();

                    // Set data attributes for print button
                    document.getElementById('viewRecordModal').dataset.type = recordType;
                    document.getElementById('viewRecordModal').dataset.id = recordId;

                    fetch(`get_record.php?type=${recordType}&id=${recordId}&mode=view`)
                        .then(response => {
                            if (!response.ok) {
                                return response.text().then(text => {
                                    throw new Error(text || 'Network response was not ok');
                                });
                            }
                            return response.text();
                        })
                        .then(html => {
                            document.getElementById('viewModalTitle').textContent = `${recordType.charAt(0).toUpperCase() + recordType.slice(1)} Record Details`;
                            document.getElementById('viewRecordContent').innerHTML = html;
                        })
                        .catch(error => {
                            document.getElementById('viewRecordContent').innerHTML = `
                            <div class="alert alert-danger">
                                Failed to load record: ${error.message}
                            </div>`;
                        });
                });
            });

            // Print button handler
            document.getElementById('printRecordBtn').addEventListener('click', function() {
                const type = document.getElementById('viewRecordModal').dataset.type;
                const id = document.getElementById('viewRecordModal').dataset.id;

                if (type && id) {
                    window.open(`http://localhost:8082/rssi-member/print_record.php?type=${type}&id=${id}`, '_blank');
                } else {
                    alert('Cannot print: Record information not available');
                }
            });

            // Edit Record Handler
            document.querySelectorAll('.edit-record').forEach(btn => {
                btn.addEventListener('click', function() {
                    const recordType = this.getAttribute('data-type');
                    const recordId = this.getAttribute('data-id');

                    // Show spinner and clear previous content
                    document.getElementById('editRecordContent').innerHTML = `
                    <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>`;

                    document.getElementById('editModalTitle').textContent = `Loading ${recordType.charAt(0).toUpperCase() + recordType.slice(1)} Record...`;
                    editModal.show();

                    fetch(`get_record.php?type=${recordType}&id=${recordId}&mode=edit`)
                        .then(response => {
                            if (!response.ok) {
                                return response.text().then(text => {
                                    throw new Error(text || 'Network response was not ok');
                                });
                            }
                            return response.text();
                        })
                        .then(html => {
                            document.getElementById('editModalTitle').textContent = `Edit ${recordType.charAt(0).toUpperCase() + recordType.slice(1)} Record`;
                            document.getElementById('editRecordContent').innerHTML = html;
                            // Set form action
                            document.getElementById('editRecordForm').action = `save_record.php?type=${recordType}&id=${recordId}`;
                        })
                        .catch(error => {
                            document.getElementById('editRecordContent').innerHTML = `
                            <div class="alert alert-danger">
                                Failed to load record: ${error.message}
                            </div>`;
                        });
                });
            });

            // Handle form submission for edit modal
            document.getElementById('editRecordForm').addEventListener('submit', function(e) {
                e.preventDefault();

                // Show spinner during form submission
                const submitButton = this.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
                submitButton.innerHTML = `
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Saving...
            `;
                submitButton.disabled = true;

                const formData = new FormData(this);

                fetch(this.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                throw new Error(err.error || 'Failed to save record');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Show success alert
                        alert('Record updated successfully!');
                        editModal.hide();
                        location.reload();
                    })
                    .catch(error => {
                        submitButton.innerHTML = originalButtonText;
                        submitButton.disabled = false;
                        alert('Error: ' + error.message);
                    });
            });

            // Add this to your existing script section
            document.querySelectorAll('.add-symptoms').forEach(btn => {
                btn.addEventListener('click', function() {
                    const recordId = this.getAttribute('data-id');
                    // Open modal to add symptoms
                    const modal = new bootstrap.Modal(document.getElementById('addSymptomsModal'));
                    document.getElementById('periodRecordId').value = recordId;
                    modal.show();
                });
            });

            // Handle form submission for symptoms modal
            document.getElementById('addSymptomsModal').querySelector('form').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = this;
                const submitButton = form.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;

                // Show loading state
                submitButton.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Saving...
    `;
                submitButton.disabled = true;

                // Get current URL parameters to preserve them
                const currentUrl = new URL(window.location.href);
                const urlParams = currentUrl.search;

                fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form)
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                throw new Error(err.error);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Show success alert
                            alert('Symptoms have been added successfully!');

                            // Close the modal
                            bootstrap.Modal.getInstance(document.getElementById('addSymptomsModal')).hide();

                            // Redirect back to original page with parameters
                            window.location.href = window.location.pathname + urlParams;
                        } else {
                            throw new Error(data.message || 'Failed to save symptoms');
                        }
                    })
                    .catch(error => {
                        alert('Error: ' + error.message);
                        submitButton.innerHTML = originalButtonText;
                        submitButton.disabled = false;
                    });
            });

            document.querySelectorAll('.distribute-again').forEach(btn => {
                btn.addEventListener('click', function() {
                    const studentId = this.getAttribute('data-id');
                    // Pre-fill the pad distribution modal with this student
                    const modal = new bootstrap.Modal(document.getElementById('addPadDistributionModal'));
                    document.getElementById('padStudentSelect').value = studentId;
                    modal.show();
                });
            });
        });
    </script>
</body>

</html>