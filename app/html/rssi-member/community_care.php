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

// Build base queries for each tab with academic year filter and proper type casting
$baseHealthQuery = "SELECT sh.*, p.name, p.gender, p.date_of_birth, st.fullname as recorded_by_name,
                    -- Age calculation (as of record date)
                    EXTRACT(YEAR FROM AGE(sh.record_date::date, p.date_of_birth::date))::integer AS age_at_record
                   FROM student_health_records sh
                   JOIN public_health_records p ON sh.student_id = p.id::varchar
                   JOIN rssimyaccount_members st ON sh.recorded_by = st.associatenumber
                   WHERE sh.record_date BETWEEN '$academicYearStart' AND '$academicYearEnd'";

$basePeriodQuery = "SELECT pr.*, p.name, p.gender, p.date_of_birth, st.fullname as recorded_by_name
                   FROM student_period_records pr
                   JOIN public_health_records p ON pr.student_id = p.id::varchar
                   JOIN rssimyaccount_members st ON pr.recorded_by = st.associatenumber
                   WHERE p.gender = 'Female'
                   AND pr.cycle_start_date BETWEEN '$academicYearStart' AND '$academicYearEnd'";

$basePadQuery = "SELECT pd.*, p.name, p.gender, p.date_of_birth, st.fullname as recorded_by_name
                FROM stock_out pd
                JOIN public_health_records p ON pd.distributed_to = p.id::varchar
                JOIN rssimyaccount_members st ON pd.distributed_by = st.associatenumber
                WHERE pd.item_distributed=149
                AND p.gender = 'Female'
                AND pd.date BETWEEN '$academicYearStart' AND '$academicYearEnd'";

// Initialize variables for all tabs
$healthResult = $periodResult = $padResult = $healthFilterResult = $periodFilterResult = $padFilterResult = null;
// Fetch stats for dashboard (always fetch these)
$statsQuery = "SELECT 
                (SELECT COUNT(*) AS active_students_count
                FROM public_health_records
                WHERE 
                    -- Student was admitted before or during the academic year
                    created_at <= '$academicYearEnd'
                    AND (
                        -- Student is still active (no effectivefrom date)
                        effectivefrom IS NULL
                        -- OR student became inactive after our academic year ended
                        OR effectivefrom > '$academicYearEnd'
                    )
                    -- Optional: include status filter if you have it
                    -- AND filtertstatus = 'Active'
                ) as total_students,
                (
                    SELECT COUNT(*)
                    FROM student_health_records shr
                    INNER JOIN public_health_records phr ON phr.id::varchar = shr.student_id
                    WHERE shr.record_date BETWEEN '$academicYearStart' AND '$academicYearEnd'
                ) AS yearly_checks,
                (
                    SELECT COALESCE(SUM(so.quantity_distributed), 0)
                    FROM stock_out so
                    INNER JOIN public_health_records shr ON so.distributed_to = shr.id::varchar
                    WHERE so.date BETWEEN '$academicYearStart' AND '$academicYearEnd'
                    AND so.item_distributed = 149
                ) AS yearly_pads";
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

    if (!empty($_GET['search'])) {
        $search = pg_escape_string($con, $_GET['search']);
        $healthFilterQuery .= " AND (p.name ILIKE '%$search%' OR p.id::text ILIKE '%$search%' OR p.contact_number ILIKE '%$search%')";
    }

    if (!empty($_GET['gender']) && in_array($_GET['gender'], ['Male', 'Female'])) {
        $gender = pg_escape_string($con, $_GET['gender']);
        $healthFilterQuery .= " AND p.gender = '$gender'";
    }

    $healthFilterQuery .= " ORDER BY sh.created_at DESC";
    $healthFilterResult = pg_query($con, $healthFilterQuery);

    // Store results in an array for easier debugging
    $healthRecords = [];
    while ($row = pg_fetch_assoc($healthFilterResult)) {
        $healthRecords[] = $row;
    }
} elseif ($activeTab == 'period-tracking') {
    $periodFilterQuery = $basePeriodQuery;

    if (!empty($_GET['search'])) {
        $search = pg_escape_string($con, $_GET['search']);
        $periodFilterQuery .= " AND (p.name ILIKE '%$search%' OR p.id::text ILIKE '%$search%' OR p.contact_number ILIKE '%$search%')";
    }

    if (isset($_GET['month']) && $_GET['month'] != '') {
        $month = date('Y-m', strtotime($_GET['month']));
        $periodFilterQuery .= " AND (pr.cycle_start_date >= '$month-01' AND pr.cycle_start_date <= '$month-31')";
    }

    $periodFilterQuery .= " ORDER BY pr.cycle_start_date DESC";
    $periodFilterResult = pg_query($con, $periodFilterQuery);
} elseif ($activeTab == 'pad-distribution') {
    $padFilterQuery = $basePadQuery;

    if (!empty($_GET['search'])) {
        $search = pg_escape_string($con, $_GET['search']);
        $padFilterQuery .= " AND (p.name ILIKE '%$search%' OR p.id::text ILIKE '%$search%' OR p.contact_number ILIKE '%$search%')";
    }

    if (isset($_GET['month']) && $_GET['month'] != '') {
        $month = $_GET['month'];
        $padFilterQuery .= " AND EXTRACT(MONTH FROM pd.date) = $month";
    }

    $padFilterQuery .= " ORDER BY pd.date DESC";
    $padFilterResult = pg_query($con, $padFilterQuery);
} elseif ($activeTab == 'reports') {
    if (isset($_GET['student_id']) && $_GET['student_id'] != '') {
        $studentId = pg_escape_string($con, $_GET['student_id']);
        $metric = isset($_GET['metric']) ? pg_escape_string($con, $_GET['metric']) : 'height';

        // Get student info
        $studentQuery = "SELECT name, gender, date_of_birth 
                        FROM public_health_records 
                        WHERE id = '$studentId'::int";
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

<?php
function getBaseFilterUrl()
{
    $url = $_SERVER['REQUEST_URI'];
    $parsedUrl = parse_url($url);
    parse_str($parsedUrl['query'] ?? '', $queryParams);

    // Only keep tab and academic_year
    $allowedParams = ['tab', 'academic_year'];
    $filteredParams = array_intersect_key($queryParams, array_flip($allowedParams));

    $newQuery = http_build_query($filteredParams);
    $basePath = $parsedUrl['path'];

    return $newQuery ? "$basePath?$newQuery" : $basePath;
}
?>
<?php
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get academic year from query parameters
$academicYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : null;

// Validate academic year format (YYYY-YYYY)
if ($academicYear && !preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
    die("Invalid academic year format. Please use YYYY-YYYY format.");
}

// Base condition for active students
$academicCondition = '';
if ($academicYear) {
    list($startYear, $endYear) = explode('-', $academicYear);
    $academicYearEnd = $endYear . '-03-31'; // Academic year ends March 31

    $academicCondition = "WHERE created_at <= '$academicYearEnd'
                          AND (
                              effectivefrom IS NULL
                              OR effectivefrom > '$academicYearEnd'
                          )";
}

// Search functionality
$search = isset($_GET['search']) ? pg_escape_string($con, $_GET['search']) : '';
$search_condition = '';
if ($search) {
    $search_condition = (empty($academicCondition) ? 'WHERE' : 'AND') .
        " (name ILIKE '%$search%' OR contact_number LIKE '%$search%' OR email ILIKE '%$search%')";
}

// Combine conditions
$where_clause = $academicCondition . $search_condition;

// Get total records for pagination
$total_query = "SELECT COUNT(*) FROM public_health_records $where_clause";
$total_result = pg_query($con, $total_query);
$total_records = pg_fetch_result($total_result, 0, 0);
$total_pages = ceil($total_records / $records_per_page);

// Get records for current page
$query = "SELECT * FROM public_health_records $where_clause
          ORDER BY created_at DESC 
          LIMIT $records_per_page OFFSET $offset";
$result = pg_query($con, $query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Care Portal</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- In your head section -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
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
    <style>
        .bg-purple {
            --bs-bg-opacity: 0.1;
            background-color: rgba(111, 66, 193, var(--bs-bg-opacity)) !important;
            color: #6f42c1 !important;
            border-color: rgba(111, 66, 193, 0.25) !important;
        }

        .bg-purple-light {
            --bs-bg-opacity: 0.1;
            background-color: rgba(180, 160, 220, var(--bs-bg-opacity)) !important;
            color: #6f42c1 !important;
            border-color: rgba(180, 160, 220, 0.25) !important;
        }

        .bg-amber {
            --bs-bg-opacity: 0.1;
            background-color: rgba(255, 193, 7, var(--bs-bg-opacity)) !important;
            color: #b88a00 !important;
            border-color: rgba(255, 193, 7, 0.25) !important;
        }
    </style>
    <style>
        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }

        .search-box {
            position: relative;
        }

        .search-box .form-control {
            padding-left: 40px;
            border-radius: 20px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 10px;
            color: #6c757d;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .export-btn {
            border-radius: 20px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse bg-primary">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">Community Care Portal</h4>
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
                    <h1 class="h2">Community Care Dashboard</h1>
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
                                                <h6 class="card-title">Total Beneficiaries</h6>
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
                                    <div class="card-header text-white d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">Beneficiary List</h5>
                                        <a href="register_beneficiary.php" class="btn btn-light btn-sm" target="_blank">
                                            <i class="fas fa-user-plus me-1"></i> New Registration
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <!-- <div class="chart-container">
                                            <canvas id="growthChart"></canvas>
                                        </div> -->

                                        <!-- <div class="container py-4"> -->
                                        <!-- <div class="card"> -->
                                        <!-- <div class="card-header text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Beneficiary List</h5>
                <a href="register_beneficiary.php" class="btn btn-light btn-sm" target="_blank">
                    <i class="fas fa-user-plus me-1"></i> New Registration
                </a>
            </div> -->

                                        <div class="card-body">
                                            <div class="row mb-4">
                                                <div class="col-md-8">
                                                    <form method="get" class="search-box">
                                                        <i class="fas fa-search"></i>
                                                        <input type="text" name="search" class="form-control" placeholder="Search by name, mobile or email..."
                                                            value="<?= htmlspecialchars($search) ?>">
                                                    </form>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <?php
                                                    // Get current filter parameters from URL
                                                    $academicYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
                                                    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

                                                    // Build export URL with current filters
                                                    $exportUrl = 'export_beneficiaries.php';
                                                    $params = [];

                                                    if (!empty($academicYear)) {
                                                        $params[] = 'academic_year=' . urlencode($academicYear);
                                                    }
                                                    if (!empty($searchTerm)) {
                                                        $params[] = 'search=' . urlencode($searchTerm);
                                                    }

                                                    if (!empty($params)) {
                                                        $exportUrl .= '?' . implode('&', $params);
                                                    }
                                                    ?>
                                                    <a href="<?= $exportUrl ?>"
                                                        class="btn btn-outline-primary export-btn">
                                                        <i class="fas fa-file-export me-1"></i> Export to CSV
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Profile</th>
                                                            <th>Name</th>
                                                            <th>Mobile</th>
                                                            <th>Email</th>
                                                            <th>Age</th>
                                                            <th>Gender</th>
                                                            <th>Registered On</th>
                                                            <!-- <th>Actions</th> -->
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (pg_num_rows($result) > 0): ?>
                                                            <?php while ($row = pg_fetch_assoc($result)): ?>
                                                                <tr>
                                                                    <td><?= $row['id'] ?></td>
                                                                    <td>
                                                                        <?php if (!empty($row['profile_photo'])): ?>
                                                                            <?php
                                                                            // Extract file ID from Google Drive URL
                                                                            preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $row['profile_photo'], $matches);
                                                                            $file_id = $matches[1] ?? null;

                                                                            if ($file_id):
                                                                                $preview_url = "https://drive.google.com/file/d/$file_id/preview";
                                                                            ?>
                                                                                <div class="drive-photo-preview"
                                                                                    data-toggle="tooltip"
                                                                                    title="Click to view full photo"
                                                                                    onclick="showPhotoModal('<?= $preview_url ?>')">
                                                                                    <iframe src="<?= $preview_url ?>"
                                                                                        width="40"
                                                                                        height="40"
                                                                                        frameborder="0"
                                                                                        style="border-radius: 50%;"
                                                                                        allow="autoplay">
                                                                                    </iframe>
                                                                                </div>
                                                                            <?php else: ?>
                                                                                <div class="d-flex align-items-center">
                                                                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['name']) ?>&background=random&size=40" class="avatar me-2" style="border-radius: 50%;">
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        <?php else: ?>
                                                                            <div class="d-flex align-items-center">
                                                                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['name']) ?>&background=random&size=40" class="avatar me-2" style="border-radius: 50%;">
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                                                    <td><?= htmlspecialchars($row['contact_number']) ?></td>
                                                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                                                    <td>
                                                                        <?php
                                                                        $dob = new DateTime($row['date_of_birth']);
                                                                        $now = new DateTime();
                                                                        echo $now->diff($dob)->y;
                                                                        ?>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($row['gender']) ?></td>
                                                                    <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                                                    <!-- <td>
                                            <a href="view_beneficiary.php?id=<?= $row['id'] ?>"
                                                class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_beneficiary.php?id=<?= $row['id'] ?>"
                                                class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td> -->
                                                                </tr>
                                                            <?php endwhile; ?>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td colspan="9" class="text-center py-4">
                                                                    <i class="fas fa-user-slash fa-2x mb-3" style="color: #6c757d;"></i>
                                                                    <h5>No beneficiaries found</h5>
                                                                    <?php if ($search): ?>
                                                                        <p>Try a different search term</p>
                                                                    <?php else: ?>
                                                                        <p>No beneficiaries have registered yet</p>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <?php if ($total_pages > 1): ?>
                                                <nav aria-label="Page navigation">
                                                    <ul class="pagination justify-content-center">
                                                        <?php if ($page > 1): ?>
                                                            <li class="page-item">
                                                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" aria-label="Previous">
                                                                    <span aria-hidden="true">&laquo;</span>
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>

                                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                                <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                                                            </li>
                                                        <?php endfor; ?>

                                                        <?php if ($page < $total_pages): ?>
                                                            <li class="page-item">
                                                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" aria-label="Next">
                                                                    <span aria-hidden="true">&raquo;</span>
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </nav>
                                            <?php endif; ?>
                                            <!-- </div> -->
                                            <!-- </div> -->
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
                                                        <h6 class="mb-1">' . htmlspecialchars($row['name']) . '</h6>
                                                        <small>' . date('M d', strtotime($row['record_date'])) . '</small>
                                                    </div>
                                                    <p class="mb-1">Age: ' . htmlspecialchars($row['age_at_record']) . '</p>
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
                                <h5 class="mb-0">Beneficiary Health Records (<?php echo $selectedAcademicYear; ?>)</h5>
                                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addHealthRecordModal">
                                    <i class="bi bi-plus"></i> Add Record
                                </button>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="">
                                    <input type="hidden" name="tab" value="health-records">
                                    <input type="hidden" name="academic_year" value="<?php echo $selectedAcademicYear; ?>">
                                    <!-- Add current filter values as hidden fields if they exist -->
                                    <!-- <?php if (isset($_GET['class'])) : ?>
                                        <input type="hidden" name="class" value="<?php echo htmlspecialchars($_GET['class']); ?>">
                                    <?php endif; ?> -->
                                    <?php if (isset($_GET['search'])) : ?>
                                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
                                    <?php endif; ?>


                                    <div class="row mb-3">
                                        <!-- <div class="col-md-4">
                                            <select class="form-select" name="class">
                                                <option value="">All Classes</option>
                                                <?php
                                                foreach ($classes as $class) {
                                                    $selected = (isset($_GET['class']) && $_GET['class'] == $class) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($class) . '" ' . $selected . '>' . htmlspecialchars($class) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div> -->
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" name="search" placeholder="Search beneficiary..."
                                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                            <a href="<?php echo getBaseFilterUrl(); ?>" class="btn btn-outline-secondary">Reset</a>
                                        </div>
                                    </div>
                                </form>

                                <?php
                                /**
                                 * Calculate health statuses based on Indian medical standards
                                 */
                                function calculateHealthStatuses($age, $bmi, $bp, $vision)
                                {
                                    $statuses = [];

                                    // BMI Status (Ages 4-15) - Aligned with CDC Percentiles & WHO Categories
                                    if ($age >= 4 && $age <= 15) {
                                        $bmiThresholds = [
                                            // Age => [Underweight(<5%), Healthy(5-85%), Overweight(85-95%), Obese(>95%)]
                                            4 => [14.0, 14.0, 16.8, 17.8],
                                            5 => [13.8, 13.8, 17.2, 18.4],
                                            6 => [13.6, 13.6, 17.6, 19.2],
                                            7 => [13.5, 13.5, 18.0, 20.0],
                                            8 => [13.5, 13.5, 18.5, 21.0],
                                            9 => [13.8, 13.8, 19.2, 22.0],
                                            10 => [14.2, 14.2, 20.0, 23.0],
                                            11 => [14.8, 14.8, 20.8, 24.0],
                                            12 => [15.5, 15.5, 21.5, 25.0],
                                            13 => [16.0, 16.0, 22.0, 26.0],
                                            14 => [16.5, 16.5, 22.5, 26.5],
                                            15 => [17.0, 17.0, 23.0, 27.0]
                                        ];

                                        if (isset($bmiThresholds[$age])) {
                                            [$severeThin, $healthyMin, $overweightMin, $obeseMin] = $bmiThresholds[$age];

                                            if ($bmi < $severeThin) {
                                                $statuses[] = [
                                                    'type' => 'BMI',
                                                    'status' => 'Underweight',
                                                    'class' => 'info',
                                                    'icon' => 'bi bi-info-circle',
                                                    'description' => 'Severe thinness for age'
                                                ];
                                            } elseif ($bmi < $healthyMin) {
                                                $statuses[] = [
                                                    'type' => 'BMI',
                                                    'status' => 'Underweight',
                                                    'class' => 'info',
                                                    'icon' => 'bi bi-info-circle',
                                                    'description' => 'Moderate thinness for age'
                                                ];
                                            } elseif ($bmi >= $obeseMin) {
                                                $statuses[] = [
                                                    'type' => 'BMI',
                                                    'status' => 'Obese',
                                                    'class' => 'danger',
                                                    'icon' => 'exclamation-triangle-fill',
                                                    'description' => 'Obese for age'
                                                ];
                                            } elseif ($bmi >= $overweightMin) {
                                                $statuses[] = [
                                                    'type' => 'BMI',
                                                    'status' => 'Overweight',
                                                    'class' => 'amber',
                                                    'icon' => 'exclamation-triangle',
                                                    'description' => 'At risk of overweight'
                                                ];
                                            }
                                            // Normal weight (5-85%) shows no status
                                        }
                                    }

                                    // Blood Pressure Status (India-specific thresholds)
                                    if (preg_match('/^(\d+)\/(\d+)$/', $bp, $matches)) {
                                        $systolic = (int)$matches[1];
                                        $diastolic = (int)$matches[2];

                                        if ($systolic >= 130 || $diastolic >= 85) { // Modified for Indian population
                                            $statuses[] = [
                                                'type' => 'BP',
                                                'status' => 'High BP',
                                                'class' => 'danger',
                                                'icon' => 'heart-pulse',
                                                'description' => '≥130/85 mmHg (Indian standards)'
                                            ];
                                        } elseif ($systolic >= 120 && $diastolic < 85) {
                                            $statuses[] = [
                                                'type' => 'BP',
                                                'status' => 'Elevated',
                                                'class' => 'amber',
                                                'icon' => 'heart',
                                                'description' => '120-129/<85 mmHg'
                                            ];
                                        }
                                    }

                                    // Vision Status (checks both eyes)
                                    if (preg_match('/^(\d+)\/(\d+)\s*\/\s*(\d+)\/(\d+)$/', $vision, $matches)) {
                                        // Format: "left_num/left_denom / right_num/right_denom" (e.g., "20/20 / 20/45")
                                        $leftNumerator = (int)$matches[1];
                                        $leftDenominator = (int)$matches[2];
                                        $rightNumerator = (int)$matches[3];
                                        $rightDenominator = (int)$matches[4];

                                        // Check if EITHER eye is worse than 20/40 (denominator/numerator > 2)
                                        $leftRatio = $leftDenominator / $leftNumerator;  // 20/20 → 1.0 (normal)
                                        $rightRatio = $rightDenominator / $rightNumerator; // 20/45 → 2.25 (concern)

                                        if ($leftRatio > 2 || $rightRatio > 2) {
                                            $statuses[] = [
                                                'type' => 'Vision',
                                                'status' => 'Vision Concern',
                                                'class' => 'amber',
                                                'icon' => 'bi bi-eye-slash',
                                                'description' => 'One or both eyes worse than 20/40'
                                            ];
                                        }
                                    }

                                    return empty($statuses) ? [
                                        [
                                            'type' => 'Overall',
                                            'status' => 'Normal',
                                            'class' => 'success',
                                            'icon' => 'check-circle',
                                            'description' => 'All parameters within normal range'
                                        ]
                                    ] : $statuses;
                                }
                                ?>
                                <!-- Health Reference Modal -->
                                <div class="modal fade" id="healthStatusModal" tabindex="-1" aria-labelledby="healthModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="healthModalLabel">Health Status Reference</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">

                                                <!-- BMI Reference Table -->
                                                <h6 class="fw-normal mb-3">BMI Classification (Ages 4-15)</h6>
                                                <div class="table-responsive mb-4">
                                                    <table class="table table-bordered">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th width="30%">BMI Range</th>
                                                                <th width="20%">Status</th>
                                                                <th width="50%">Indicator</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>Below 5th percentile</td>
                                                                <td>Underweight</td>
                                                                <td>
                                                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                                                        <i class="bi bi-info-circle me-1"></i>Underweight
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>5th - 85th percentile</td>
                                                                <td>Healthy Weight</td>
                                                                <td>
                                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                                        <i class="bi bi-check-circle me-1"></i>Healthy
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>85th - 95th percentile</td>
                                                                <td>Overweight (At Risk)</td>
                                                                <td>
                                                                    <span class="badge bg-amber bg-opacity-10 text-amber border border-warning border-opacity-25">
                                                                        <i class="bi bi-exclamation-triangle me-1"></i>Overweight
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Above 95th percentile</td>
                                                                <td>Obese</td>
                                                                <td>
                                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Obese
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <p class="small text-muted mb-0">
                                                        CDC/WHO Growth Standards:
                                                        Underweight (<5th %ile), Healthy (5-85%ile),
                                                            Overweight (85-95%ile), Obese (>95%ile)
                                                    </p>
                                                    <p class="small text-muted">
                                                        Sample Age Cutoffs (5th/85th/95th %ile):
                                                        4y=14.0/16.8/17.8, 8y=14.5/19.5/21.5,
                                                        12y=15.5/21.5/25.0, 15y=17.0/23.0/27.0
                                                    </p>
                                                </div>

                                                <!-- BP Reference Table -->
                                                <h6 class="fw-normal mb-3">Blood Pressure Classification</h6>
                                                <div class="table-responsive mb-4">
                                                    <table class="table table-bordered">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th width="30%">BP Range</th>
                                                                <th width="20%">Status</th>
                                                                <th width="50%">Indicator</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>&lt;120/80</td>
                                                                <td>Normal</td>
                                                                <td>
                                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                                        <i class="bi bi-heart me-1"></i>Normal BP
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>120-129/&lt;85</td>
                                                                <td>Elevated</td>
                                                                <td>
                                                                    <span class="badge bg-amber bg-opacity-10 text-amber border border-amber border-opacity-25">
                                                                        <i class="bi bi-heart me-1"></i>Elevated BP
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>≥130/85</td>
                                                                <td>High BP</td>
                                                                <td>
                                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                                                        <i class="bi bi-heart-pulse me-1"></i>High BP
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <!-- Vision Reference Table -->
                                                <h6 class="fw-normal mb-3">Vision Classification</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th width="30%">Visual Acuity</th>
                                                                <th width="20%">Status</th>
                                                                <th width="50%">Indicator</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>20/20 to 20/40</td>
                                                                <td>Normal</td>
                                                                <td>
                                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                                                        <i class="bi bi-eye me-1"></i>Normal Vision
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Worse than 20/40</td>
                                                                <td>Vision Concern</td>
                                                                <td>
                                                                    <span class="badge bg-amber bg-opacity-10 text-amber border border-amber border-opacity-25">
                                                                        <i class="bi bi-eye-slash me-1"></i>Vision Concern
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <div class="alert alert-light border">
                                                    <i class="bi bi-lightbulb text-warning"></i>
                                                    <small class="text-muted">
                                                        Screening tool only - Consult a healthcare professional.
                                                        Status based on CDC growth charts (children) and WHO BMI standards (adults).
                                                    </small>
                                                </div>
                                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <!-- Main Table with Health Status Column -->
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Beneficiary</th>
                                                <th>Age <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip" title="At recording time"></i></th>
                                                <th>Date</th>
                                                <th>Height (cm)</th>
                                                <th>Weight (kg)</th>
                                                <th>BMI</th>
                                                <th>BP</th>
                                                <th>Vision</th>
                                                <th>
                                                    Health Status
                                                    <button class="btn btn-link p-0 ms-1" data-bs-toggle="modal" data-bs-target="#healthStatusModal">
                                                        <i class="bi bi-info-circle text-muted"></i>
                                                    </button>
                                                </th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($healthRecords)): ?>
                                                <?php foreach ($healthRecords as $row):
                                                    $statuses = calculateHealthStatuses(
                                                        $row['age_at_record'] ?? 0,
                                                        $row['bmi'] ?? 0,
                                                        $row['blood_pressure'] ?? '',
                                                        ($row['vision_left'] ?? '') . '/' . ($row['vision_right'] ?? '')
                                                    );
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($row['name']) ?>&background=random" class="avatar me-2">
                                                                <div>
                                                                    <h6 class="mb-0"><?= htmlspecialchars($row['name']) ?></h6>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?= htmlspecialchars($row['age_at_record'] ?? '') ?></td>
                                                        <td><?= date('d M Y', strtotime($row['record_date'])) ?></td>
                                                        <td><?= htmlspecialchars($row['height_cm'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['weight_kg'] ?? '') ?></td>
                                                        <td><?= number_format($row['bmi'] ?? 0, 1) ?></td>
                                                        <td><?= htmlspecialchars($row['blood_pressure'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($row['vision_left'] ?? '') ?>/<?= htmlspecialchars($row['vision_right'] ?? '') ?></td>
                                                        <td>
                                                            <div class="d-flex flex-wrap gap-1">
                                                                <?php foreach ($statuses as $status): ?>
                                                                    <span class="badge bg-<?= $status['class'] ?> bg-opacity-10 text-<?= $status['class'] ?> border border-<?= $status['class'] ?> border-opacity-25"
                                                                        data-bs-toggle="tooltip" title="<?= $status['type'] ?>">
                                                                        <i class="bi bi-<?= $status['icon'] ?> me-1"></i><?= $status['status'] ?>
                                                                    </span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </td>
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
                                                    <td colspan="10" class="text-center">
                                                        <?= isset($healthFilterQuery) ? 'No matching records' : 'No health records' ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <script>
                                    // Initialize tooltips
                                    document.addEventListener('DOMContentLoaded', function() {
                                        [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).forEach(function(el) {
                                            new bootstrap.Tooltip(el);
                                        });
                                    });
                                </script>
                            </div>
                        </div>
                    </div>

                    <!-- Period Tracking Tab -->
                    <div class="tab-pane fade <?php echo $activeTab == 'period-tracking' ? 'show active' : ''; ?>" id="period-tracking">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Period Tracking Records (<?php echo $selectedAcademicYear; ?>)</h5>
                                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addPeriodRecordModal">
                                    <i class="bi bi-plus"></i> Add Record
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Only female beneficiaries are shown in this section.
                                </div>

                                <form method="GET" action="">
                                    <input type="hidden" name="tab" value="period-tracking">
                                    <input type="hidden" name="academic_year" value="<?php echo $selectedAcademicYear; ?>">

                                    <div class="row mb-3">
                                        <!-- <div class="col-md-3">
                                            <select class="form-select" name="class">
                                                <option value="">All Classes</option>
                                                <?php
                                                foreach ($classes as $class) {
                                                    $selected = (isset($_GET['class']) && $_GET['class'] == $class) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($class) . '" ' . $selected . '>' . htmlspecialchars($class) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div> -->
                                        <div class="col-md-3">
                                            <input type="month" class="form-control" name="month"
                                                value="<?php echo isset($_GET['month']) ? htmlspecialchars($_GET['month']) : ''; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" class="form-control" name="search" placeholder="Search beneficiary..."
                                                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-primary">Filter</button>
                                            <a href="<?php echo getBaseFilterUrl(); ?>" class="btn btn-outline-secondary">Reset</a>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Beneficiary</th>
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
                                                                <img src="https://ui-avatars.com/api/?name=' . urlencode($row['name']) . '&background=random" class="avatar me-2">
                                                                <div>
                                                                    <h6 class="mb-0">' . htmlspecialchars($row['name']) . '</h6>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>' . date('d M Y', strtotime($row['cycle_start_date']) ?? '') . '</td>
                                                        <td>' . ($row['cycle_end_date'] ? date('d M Y', strtotime($row['cycle_end_date'])) : 'Ongoing') . '</td>
                                                        <td>' . htmlspecialchars(substr($row['symptoms'] ?? '', 0, 30)) . '</td>' ?>
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
                                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addPadDistributionModal">
                                    <i class="bi bi-plus"></i> Add Distribution
                                </button>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="">
                                    <input type="hidden" name="tab" value="pad-distribution">
                                    <input type="hidden" name="academic_year" value="<?php echo $selectedAcademicYear; ?>">

                                    <div class="row mb-3">
                                        <!-- <div class="col-md-3">
                                            <select class="form-select" name="class">
                                                <option value="">All Classes</option>
                                                <?php
                                                foreach ($classes as $class) {
                                                    $selected = (isset($_GET['class']) && $_GET['class'] == $class) ? 'selected' : '';
                                                    echo '<option value="' . htmlspecialchars($class) . '" ' . $selected . '>' . htmlspecialchars($class) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div> -->
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
                                            <a href="<?php echo getBaseFilterUrl(); ?>" class="btn btn-outline-secondary">Reset</a>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Beneficiary</th>
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
                                                                <img src="https://ui-avatars.com/api/?name=' . urlencode($row['name']) . '&background=random" class="avatar me-2">
                                                                <div>
                                                                    <h6 class="mb-0">' . htmlspecialchars($row['name']) . '</h6>
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
                                                <!-- <div class="col-md-4">
                                                    <select class="form-select" id="studentGrowth" name="student_id" required>
                                                        <option value="">Select Student</option>
                                                        <?php foreach ($students as $student): ?>
                                                            <option
                                                                value="<?= htmlspecialchars($student['id']); ?>"
                                                                <?= (isset($_GET['student_id']) && $_GET['student_id'] == $student['id']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($student['text']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <small class="text-muted">Choose a student from the list.</small>
                                                    <div class="invalid-feedback">Please select a student.</div>
                                                </div> -->
                                                <div class="col-md-4">
                                                    <select class="form-select js-data-ajax-all" id="studentGrowth" name="student_id" required>
                                                        <option value="">Select Student</option>
                                                    </select>
                                                    <small class="text-muted">Choose a student from the list.</small>
                                                    <div class="invalid-feedback">Please select a student.</div>
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
                <form id="healthRecordForm" action="save_health_record.php" method="POST">
                    <input type="hidden" name="source_tab" value="<?php echo isset($_GET['tab']) ? htmlspecialchars($_GET['tab']) : 'health-records'; ?>">
                    <input type="hidden" name="academic_year" value="<?php echo isset($_GET['academic_year']) ? htmlspecialchars($_GET['academic_year']) : ''; ?>">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <!-- <div class="col-md-6">
                                <label for="studentSelect" class="form-label">Beneficiaries</label>
                                <select class="form-select" id="studentSelect" name="student_id" required>
                                    <option value="">Select Beneficiary</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= $student['id']; ?>"><?= $student['text']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-muted">
                                    First-time user? <a href="register_beneficiary.php" target="_blank">Register here</a>
                                </div>
                            </div> -->
                            <div class="col-md-6">
                                <label for="studentSelect" class="form-label">Beneficiaries</label>
                                <select class="form-select js-data-ajax" id="studentSelect" name="student_id" required>
                                    <option value="">Select Beneficiary</option>
                                    <!-- Initial options can be kept or removed -->
                                </select>
                                <div class="form-text text-muted">
                                    First-time user? <a href="register_beneficiary.php" target="_blank">Register here</a>
                                </div>
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
                                <input type="number" step="0.1" class="form-control" id="height" name="height_cm" required>
                            </div>
                            <div class="col-md-4">
                                <label for="weight" class="form-label">Weight (kg)</label>
                                <input type="number" step="0.1" class="form-control" id="weight" name="weight_kg" required>
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
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <span class="default-text">Save Record</span>
                            <span class="loading-text d-none">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Submitting...
                            </span>
                        </button>
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
                <form id="periodRecordForm" action="save_period_record.php" method="POST">
                    <!-- Add these hidden fields at the top of your form -->
                    <input type="hidden" name="current_tab" value="period-tracking">
                    <?php if (isset($_GET['academic_year'])): ?>
                        <input type="hidden" name="academic_year" value="<?= htmlspecialchars($_GET['academic_year']) ?>">
                    <?php endif; ?>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <!-- <div class="col-md-6">
                                <label for="periodStudentSelect" class="form-label">Beneficiary</label>
                                <select class="form-select" id="periodStudentSelect" name="student_id" required>
                                    <option value="">Select Beneficiary</option>
                                    <?php foreach ($femaleStudents as $student): ?>
                                        <option value="<?= htmlspecialchars($student['id']); ?>"><?= htmlspecialchars($student['text']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Choose a beneficiary from the list.</small>
                                <div class="invalid-feedback">Please select a beneficiary.</div>
                            </div> -->
                            <div class="col-md-6">
                                <label for="periodStudentSelect" class="form-label">Beneficiary</label>
                                <select class="form-select js-data-ajax-female" id="periodStudentSelect" name="student_id" required>
                                    <option value="">Select Beneficiary</option>
                                </select>
                                <small class="text-muted">Choose a beneficiary from the list.</small>
                                <div class="invalid-feedback">Please select a beneficiary.</div>
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
                        <button type="submit" class="btn btn-primary" id="submitPeriodBtn">
                            <span class="default-text">Save Record</span>
                            <span class="loading-text d-none">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Submitting...
                            </span>
                        </button>
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
                <form id="padDistributionForm" action="save_pad_distribution.php" method="POST">
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
                            <!-- <div class="col-md-12">
                                <label for="padStudentSelect" class="form-label">Search and Select Beneficiaries</label>
                                <select id="padStudentSelect" name="student_ids[]" class="form-control" multiple="multiple" required>
                                    <?php foreach ($femaleStudents as $student): ?>
                                        <option value="<?= htmlspecialchars($student['id']); ?>"><?= htmlspecialchars($student['text']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Start typing to search beneficiaries. You can select multiple beneficiaries.</small>
                                <div class="invalid-feedback">Please select at least one beneficiary.</div>
                            </div> -->
                            <div class="col-md-12">
                                <label for="padStudentSelect" class="form-label">Search and Select Beneficiaries</label>
                                <select id="padStudentSelect" name="student_ids[]" class="form-control js-data-ajax-female-multiple" multiple="multiple" required>
                                </select>
                                <small class="text-muted">Start typing to search beneficiaries. You can select multiple beneficiaries.</small>
                                <div class="invalid-feedback">Please select at least one beneficiary.</div>
                            </div>
                        </div>
                        <div class="row g-3"> <!-- Better grid spacing -->
                            <div class="col-md-6">
                                <label for="distributionDate" class="form-label">Distribution Date</label>
                                <input type="date" class="form-control" id="distributionDate" name="distribution_date" required
                                    value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="padQuantity" class="form-label">Quantity per Student</label>
                            <input type="number" class="form-control" id="padQuantity" name="quantity" value="1" min="1" required>
                        </div>

                        <!-- <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="bulkDistribution" name="bulk_distribution">
                                <label class="form-check-label" for="bulkDistribution">
                                    Bulk distribution to entire class
                                </label>
                            </div>
                        </div> -->

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
                        <button type="submit" class="btn btn-primary" id="submitPadBtn">
                            <span class="default-text">Save Distribution</span>
                            <span class="loading-text d-none">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Submitting...
                            </span>
                        </button>
                    </div>
                </form>
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
                        <input type="hidden" name="current_script" value="community_care">

                        <div class="row mb-3">
                            <!-- <div class="col-md-6">
                                <label class="form-label">Class</label>
                                <select class="form-select" name="class">
                                    <option value="">All Classes</option>
                                    <?php
                                    foreach ($classes as $class) {
                                        echo '<option value="' . htmlspecialchars($class) . '">' . htmlspecialchars($class) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div> -->
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

                        <!-- <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="compareAcademicYears" name="compare_academic_years">
                            <label class="form-check-label" for="compareAcademicYears">
                                Compare with previous academic year
                            </label>
                        </div> -->
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
                        <input type="hidden" name="current_script" value="community_care">

                        <div class="row mb-3">
                            <!-- <div class="col-md-6">
                                <label class="form-label">Class</label>
                                <select class="form-select" name="class">
                                    <option value="">All Classes</option>
                                    <?php
                                    foreach ($classes as $class) {
                                        echo '<option value="' . htmlspecialchars($class) . '">' . htmlspecialchars($class) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div> -->
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
                        <input type="hidden" name="current_script" value="community_care">

                        <div class="row mb-3">
                            <!-- <div class="col-md-6">
                                <label class="form-label">Class</label>
                                <select class="form-select" name="class">
                                    <option value="">All Classes</option>
                                    <?php
                                    foreach ($classes as $class) {
                                        echo '<option value="' . htmlspecialchars($class) . '">' . htmlspecialchars($class) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div> -->
                            <div class="col-md-6">
                                <label class="form-label">Report Type</label>
                                <select class="form-select" name="report_type">
                                    <option value="monthly">Monthly Summary</option>
                                    <option value="yearly">Yearly Summary</option>
                                    <option value="student">By Beneficiary</option>
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
                    <button type="button" class="btn btn-primary" id="printRecordBtn"><i class="bi bi-printer"></i> Print</button>
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
    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

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
                    window.open(`print_record.php?type=${type}&id=${id}`, '_blank');
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
    <script>
        $(document).ready(function() {
            // Array of modal and select configurations
            const modals = [{
                    modal: '#addPadDistributionModal',
                    select: '#padStudentSelect',
                    allowClear: false
                },
                {
                    modal: '#addHealthRecordModal',
                    select: '#studentSelect',
                    allowClear: true
                },
                {
                    modal: '#addPeriodRecordModal',
                    select: '#periodStudentSelect',
                    allowClear: true
                },
            ];

            // Loop through each modal and initialize Select2
            modals.forEach(function(item) {
                $(item.modal).on('shown.bs.modal', function() {
                    $(item.select).select2({
                        placeholder: "Search by name or contact number...",
                        allowClear: item.allowClear,
                        width: '100%',
                        dropdownParent: $(this) // Use the current modal as parent
                    });
                });
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#studentGrowth').select2({
                placeholder: "Search by name or contact number...",
                allowClear: true
            });
        });
    </script>
    <script>
        // General function to handle form submission
        function handleFormSubmit(formId, submitBtnId) {
            document.getElementById(formId).addEventListener('submit', function() {
                const btn = document.getElementById(submitBtnId);
                btn.disabled = true;
                btn.querySelector('.default-text').classList.add('d-none');
                btn.querySelector('.loading-text').classList.remove('d-none');
            });
        }

        // Call the function for each form
        handleFormSubmit('healthRecordForm', 'submitBtn');
        handleFormSubmit('periodRecordForm', 'submitPeriodBtn');
        handleFormSubmit('padDistributionForm', 'submitPadBtn');
    </script>
    <script>
        $(document).ready(function() {
            // Initialize the non-modal select (studentGrowth)
            $('#studentGrowth').select2({
                ajax: {
                    url: 'search_beneficiaries.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results || []
                        };
                    }
                },
                minimumInputLength: 1,
                placeholder: 'Search all students'
            });

            // Initialize modal selects when their modals are shown
            $('#addHealthRecordModal').on('shown.bs.modal', function() {
                $('#studentSelect').select2({
                    dropdownParent: $(this),
                    ajax: {
                        url: 'search_beneficiaries.php',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results || []
                            };
                        }
                    },
                    minimumInputLength: 1,
                    placeholder: 'Search by name'
                });
            });

            $('#addPeriodRecordModal').on('shown.bs.modal', function() { // Make sure this matches your female select modal ID
                $('#periodStudentSelect').select2({
                    dropdownParent: $(this),
                    ajax: {
                        url: 'search_beneficiaries.php',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term,
                                gender: 'Female'
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results || []
                            };
                        }
                    },
                    minimumInputLength: 1,
                    placeholder: 'Search female beneficiaries'
                });
            });

            $('#addPadDistributionModal').on('shown.bs.modal', function() { // Make sure this matches your multiple select modal ID
                $('#padStudentSelect').select2({
                    dropdownParent: $(this),
                    ajax: {
                        url: 'search_beneficiaries.php',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term,
                                gender: 'Female'
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results || []
                            };
                        }
                    },
                    minimumInputLength: 1,
                    placeholder: 'Search female beneficiaries',
                    closeOnSelect: false
                });
            });

            // Destroy Select2 when modals close to prevent memory leaks
            $('.modal').on('hidden.bs.modal', function() {
                $(this).find('select').select2('destroy');
            });
        });
    </script>
</body>

</html>