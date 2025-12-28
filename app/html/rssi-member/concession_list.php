<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

// Function to determine academic year based on date
function getAcademicYear($date)
{
    if (!$date) return 'Indefinite';
    $date = new DateTime($date);
    $year = $date->format('Y');
    $month = $date->format('m');

    if ($month >= 4) { // April or later
        return $year . '-' . ($year + 1);
    } else { // January-March
        return ($year - 1) . '-' . $year;
    }
}

// Get filter parameters from URL if they exist
$filterParams = [
    'student_id' => isset($_GET['student_id']) ? (array)$_GET['student_id'] : [],
    'concession_category' => isset($_GET['concession_category']) ? (array)$_GET['concession_category'] : [],
    'academic_year' => isset($_GET['academic_year']) ? (array)$_GET['academic_year'] : []
];

// Remove empty values from filter arrays
foreach ($filterParams as $key => $value) {
    $filterParams[$key] = array_filter($value, function ($v) {
        return $v !== '';
    });
}

// Build the base query
$query = "SELECT sc.*, 
                 s.studentname, 
                 s.class, 
                 u.fullname as created_by_name,
                 TO_CHAR(sc.effective_from, 'Mon YYYY') as formatted_from_date,
                 CASE WHEN sc.effective_until IS NULL THEN 'Indefinite'
                      ELSE TO_CHAR(sc.effective_until, 'Mon YYYY') 
                 END as formatted_until_date
          FROM student_concessions sc
          JOIN rssimyprofile_student s ON sc.student_id = s.student_id
          JOIN rssimyaccount_members u ON sc.created_by = u.associatenumber";

// Add WHERE clauses if filters are present
$whereClauses = [];
$params = [];

function toPgArray($array, $con)
{
    return '{' . implode(',', array_map(fn($v) => pg_escape_string($con, $v), $array)) . '}';
}
if (!empty($filterParams['student_id'])) {
    $whereClauses[] = "sc.student_id = ANY($" . (count($params) + 1) . ")";
    $params[] = toPgArray($filterParams['student_id'], $con);
}

if (!empty($filterParams['concession_category'])) {
    $whereClauses[] = "sc.concession_category = ANY($" . (count($params) + 1) . ")";
    $params[] = toPgArray($filterParams['concession_category'], $con);
}

// Modify your query to use current academic year when no filter is set
$yearConditions = [];
if (empty($filterParams['academic_year'])) {
    // Default to current academic year when no filter is set
    $currentYear = date('Y');
    $currentMonth = date('m');
    $academicYear = ($currentMonth >= 4) ?
        $currentYear . '-' . ($currentYear + 1) : ($currentYear - 1) . '-' . $currentYear;

    $startYear = substr($academicYear, 0, 4);
    $endYear = substr($academicYear, 5, 4);
    $yearConditions[] = "(sc.effective_from >= '$startYear-04-01' AND sc.effective_from < '$endYear-04-01')";
} else {
    // Use the filter parameters if they exist
    foreach ($filterParams['academic_year'] as $year) {
        if ($year === 'Indefinite') {
            $yearConditions[] = "sc.effective_until IS NULL";
        } else {
            $startYear = substr($year, 0, 4);
            $endYear = substr($year, 5, 4);
            $yearConditions[] = "(sc.effective_from >= '$startYear-04-01' AND sc.effective_from < '$endYear-04-01')";
        }
    }
}

if (!empty($yearConditions)) {
    $whereClauses[] = "(" . implode(" OR ", $yearConditions) . ")";
}

if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

$query .= " ORDER BY sc.created_at DESC";

// Prepare and execute the query with parameters
$stmt = pg_prepare($con, "", $query);
if (!$stmt) {
    die("Query preparation failed: " . pg_last_error($con));
}

$result = pg_execute($con, "", $params);
if (!$result) {
    die("Database query failed: " . pg_last_error($con));
}

$concessions = pg_fetch_all($result);

// Calculate statistics
$stats = [
    'total_concessions' => 0,
    'total_amount' => 0,
    'categories' => [],
    'academic_years' => []
];

if (!empty($concessions)) {
    foreach ($concessions as $concession) {
        // Skip non-billable concessions
        if ($concession['concession_category'] === 'non_billable') {
            continue;
        }

        // Calculate total amount
        $stats['total_concessions']++;
        $stats['total_amount'] += floatval($concession['concession_amount']);

        // Count by category
        $category = !empty($concession['concession_category']) ?
            ucwords(str_replace('_', ' ', $concession['concession_category'])) :
            'Uncategorized';
        if (!isset($stats['categories'][$category])) {
            $stats['categories'][$category] = [
                'count' => 0,
                'amount' => 0
            ];
        }
        $stats['categories'][$category]['count']++;
        $stats['categories'][$category]['amount'] += floatval($concession['concession_amount']);

        // Count by academic year
        $academicYear = getAcademicYear($concession['effective_from']);
        if (!isset($stats['academic_years'][$academicYear])) {
            $stats['academic_years'][$academicYear] = [
                'count' => 0,
                'amount' => 0
            ];
        }
        $stats['academic_years'][$academicYear]['count']++;
        $stats['academic_years'][$academicYear]['amount'] += floatval($concession['concession_amount']);
    }
}

// Get distinct categories for filter
$categoriesQuery = "SELECT DISTINCT concession_category FROM student_concessions ORDER BY concession_category";
$categoriesResult = pg_query($con, $categoriesQuery);
$categories = pg_fetch_all($categoriesResult);

// Free the result sets
pg_free_result($result);
pg_free_result($categoriesResult);
?>
<?php
// In your PHP code (before the HTML)
// Replace the existing generateAcademicYears() function with this:
function getAvailableAcademicYears($con)
{
    // Query to get all distinct effective_from dates
    $query = "SELECT DISTINCT effective_from FROM student_concessions WHERE effective_from IS NOT NULL ORDER BY effective_from DESC";
    $result = pg_query($con, $query);
    $dates = pg_fetch_all($result);

    $years = [];

    // Add all academic years from the database
    foreach ($dates as $date) {
        $year = getAcademicYear($date['effective_from']);
        if (!in_array($year, $years)) {
            $years[] = $year;
        }
    }

    // Add "Indefinite" option
    $years[] = 'Indefinite';

    return $years;
}

// Then replace the line where $academicYearOptions is set:
$academicYearOptions = getAvailableAcademicYears($con);
$selectedYears = isset($_GET['academic_year']) ? (array)$_GET['academic_year'] : [];

// Determine active tab from URL
$activeTab = 'billable'; // Default
if (isset($_GET['tab']) && in_array($_GET['tab'], ['billable', 'non-billable'])) {
    $activeTab = $_GET['tab'];
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
    <title>Student Concessions</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }

        .stat-card {
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #0d6efd;
            background-color: white;
            transition: transform 0.3s;
        }

        .stat-card.total-concessions {
            border-left-color: #0d6efd;
        }

        .stat-card.total-amount {
            border-left-color: #198754;
        }

        .stat-card.academic-years {
            border-left-color: #0dcaf0;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .category-badge {
            font-size: 0.85rem;
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        .chart-container {
            position: relative;
            height: 250px;
        }

        .show-chart-btn {
            cursor: pointer;
            color: #0d6efd;
        }

        .show-chart-btn:hover {
            text-decoration: underline;
        }

        .filter-active {
            background-color: #e9ecef;
            font-weight: bold;
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Student Concessions</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Fee Portal</a></li>
                    <li class="breadcrumb-item active">Student Concessions</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <!-- Statistics Cards -->
                <div class="col-lg-12">
                    <div class="row">
                        <!-- Total Concessions Card -->
                        <div class="col-md-4">
                            <div class="card stat-card total-concessions mb-4">
                                <div class="card-body">
                                    <div class="stat-value"><?= number_format($stats['total_concessions']) ?></div>
                                    <div class="stat-label">Total Concessions</div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Amount Card -->
                        <div class="col-md-4">
                            <div class="card stat-card total-amount mb-4">
                                <div class="card-body">
                                    <div class="stat-value">₹<?= number_format($stats['total_amount'], 2) ?></div>
                                    <div class="stat-label">Total Concession Amount</div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Academic Years Card -->
                        <div class="col-md-4">
                            <div class="card stat-card academic-years mb-4">
                                <div class="card-body">
                                    <div class="stat-value"><?= count($stats['academic_years']) ?></div>
                                    <div class="stat-label">Academic Years</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <div class="container-fluid">
                                <h2 class="mb-4">Student Concessions Management</h2>

                                <!-- Statistics Section -->
                                <div class="row mb-4">
                                    <!-- Categories Breakdown -->
                                    <div class="col-md-8">
                                        <div class="card mb-4">
                                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                <h5 class="card-title mb-0">Concessions by Category</h5>
                                                <span class="badge bg-primary"><?= count($stats['categories']) ?> Categories</span>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <?php foreach ($stats['categories'] as $category => $data): ?>
                                                        <div class="col-md-6 mb-3">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span><?= $category ?></span>
                                                                <span class="badge bg-primary rounded-pill">
                                                                    <?= $data['count'] ?> (₹<?= number_format($data['amount'], 2) ?>)
                                                                </span>
                                                            </div>
                                                            <div class="progress mt-2" style="height: 8px;">
                                                                <div class="progress-bar" role="progressbar"
                                                                    style="width: <?= ($data['count'] / $stats['total_concessions']) * 100 ?>%"
                                                                    aria-valuenow="<?= $data['count'] ?>"
                                                                    aria-valuemin="0"
                                                                    aria-valuemax="<?= $stats['total_concessions'] ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Academic Year Breakdown -->
                                    <div class="col-md-4">
                                        <div class="card mb-4">
                                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                <h5 class="card-title mb-0">Concessions by Academic Year</h5>
                                                <span class="show-chart-btn" onclick="toggleChart()"><i class="bi bi-bar-chart"></i> Show Chart</span>
                                            </div>
                                            <div class="card-body">
                                                <div class="chart-container" id="chartContainer" style="display: none;">
                                                    <canvas id="academicYearChart"></canvas>
                                                </div>
                                                <div id="yearList">
                                                    <?php
                                                    // Sort academic years in descending order
                                                    krsort($stats['academic_years']);
                                                    foreach ($stats['academic_years'] as $year => $data): ?>
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <span><?= $year ?></span>
                                                            <span class="badge bg-primary rounded-pill">
                                                                <?= $data['count'] ?> (₹<?= number_format($data['amount'], 2) ?>)
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filter Section -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <form id="filterForm" method="get">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <label class="form-label">Student</label>
                                                    <select name="student_id[]" id="student-select" class="form-control select2" multiple="multiple">
                                                        <?php
                                                        // Always output selected options, not just when $search_term exists
                                                        $studentIds = is_array($_GET['student_id'] ?? []) ? $_GET['student_id'] : [];
                                                        foreach ($studentIds as $id) {
                                                            $student = pg_fetch_assoc(pg_query_params(
                                                                $con,
                                                                "SELECT student_id, studentname FROM rssimyprofile_student WHERE student_id = $1",
                                                                array($id)
                                                            ));
                                                            if ($student) {
                                                                echo '<option value="' . $student['student_id'] . '" selected>' .
                                                                    htmlspecialchars($student['studentname']) . ' - ' .
                                                                    htmlspecialchars($student['student_id']) . '</option>';
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">Category</label>
                                                    <select class="form-select select2-multiple" name="concession_category[]" multiple="multiple">
                                                        <?php foreach ($categories as $category):
                                                            $selected = in_array($category['concession_category'], $filterParams['concession_category']) ? 'selected' : '';
                                                        ?>
                                                            <option value="<?= htmlspecialchars($category['concession_category']) ?>" <?= $selected ?>>
                                                                <?= ucwords(str_replace('_', ' ', $category['concession_category'] ?? '')) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label">Academic Year</label>
                                                    <select class="form-select select2-multiple" name="academic_year[]" multiple="multiple" id="academicYearFilter">
                                                        <?php foreach ($academicYearOptions as $year): ?>
                                                            <option value="<?= htmlspecialchars($year) ?>"
                                                                <?= in_array($year, $selectedYears) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($year) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 d-flex align-items-end mb-3">
                                                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                                                    <a href="concession_list.php" class="btn btn-outline-secondary">Reset</a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mb-3">
                                    <button class="btn btn-success export-btn">
                                        <i class="bi bi-download"></i> Export CSV
                                    </button>
                                </div>

                                <!-- Concessions Table -->
                                <div class="card">
                                    <div class="card-body">
                                        <ul class="nav nav-tabs" id="concessionTabs" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <a class="nav-link <?= $activeTab === 'billable' ? 'active' : '' ?>" href="?tab=billable" id="billable-tab" data-bs-toggle="tab"
                                                    data-bs-target="#billable" type="button" role="tab"
                                                    aria-controls="billable" aria-selected="true">
                                                    Billable Concessions
                                                </a>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <a class="nav-link <?= $activeTab === 'non-billable' ? 'active' : '' ?>" href="?tab=non-billable" id="non-billable-tab" data-bs-toggle="tab"
                                                    data-bs-target="#non-billable" type="button" role="tab"
                                                    aria-controls="non-billable" aria-selected="false">
                                                    Non-Billable (Audit)
                                                </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content" id="concessionTabsContent">
                                            <div class="tab-pane fade <?= $activeTab === 'billable' ? 'show active' : '' ?>" id="billable" role="tabpanel" aria-labelledby="billable-tab">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover" id="billableTable">
                                                        <thead>
                                                            <tr>
                                                                <th>Student ID</th>
                                                                <th>Student</th>
                                                                <th>Class</th>
                                                                <th>Category</th>
                                                                <th>Amount</th>
                                                                <th>Academic Year</th>
                                                                <th>Effective From</th>
                                                                <th>Effective Until</th>
                                                                <th>Reason</th>
                                                                <th>Supporting Document</th>
                                                                <th>Created By</th>
                                                                <th>Created At</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($concessions)): ?>
                                                                <?php foreach ($concessions as $concession):
                                                                    if ($concession['concession_category'] === 'non_billable') continue;
                                                                    $academicYear = getAcademicYear($concession['effective_from']);
                                                                ?>
                                                                    <tr>
                                                                        <td><?= htmlspecialchars($concession['student_id']) ?></td>
                                                                        <td><?= htmlspecialchars($concession['studentname']) ?></td>
                                                                        <td><?= htmlspecialchars($concession['class']) ?></td>
                                                                        <td>
                                                                            <?= !empty($concession['concession_category']) ?
                                                                                ucwords(str_replace('_', ' ', $concession['concession_category'])) :
                                                                                'Uncategorized' ?>
                                                                        </td>
                                                                        <td>₹<?= number_format($concession['concession_amount'], 2) ?></td>
                                                                        <td><?= $academicYear ?></td>
                                                                        <td><?= $concession['formatted_from_date'] ?></td>
                                                                        <td><?= $concession['formatted_until_date'] ?></td>
                                                                        <td><?= nl2br(htmlspecialchars($concession['reason'])) ?></td>
                                                                        <td>
                                                                            <?php if (!empty($concession['supporting_document'])): ?>
                                                                                <a href="<?= htmlspecialchars($concession['supporting_document']) ?>" target="_blank">View</a>
                                                                            <?php else: ?>
                                                                                N/A
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td><?= htmlspecialchars($concession['created_by_name']) ?></td>
                                                                        <td><?= date('d M Y H:i', strtotime($concession['created_at'])) ?></td>
                                                                        <td>
                                                                            <?php if ($role === 'Admin'): ?>
                                                                                <button class="btn btn-sm btn-primary edit-concession"
                                                                                    data-id="<?= $concession['id'] ?>">
                                                                                    <i class="bi bi-pencil"></i> Edit
                                                                                </button>
                                                                            <?php endif; ?>
                                                                            <button class="btn btn-sm btn-info history-concession"
                                                                                data-id="<?= $concession['id'] ?>">
                                                                                <i class="bi bi-clock-history"></i> History
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <tr>
                                                                    <td colspan="12" class="text-center">No billable concessions found</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade <?= $activeTab === 'non-billable' ? 'show active' : '' ?>" id="non-billable" role="tabpanel" aria-labelledby="non-billable-tab">
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover" id="nonBillableTable">
                                                        <thead>
                                                            <tr>
                                                                <th>Student ID</th>
                                                                <th>Student</th>
                                                                <th>Class</th>
                                                                <th>Category</th>
                                                                <th>Amount</th>
                                                                <th>Academic Year</th>
                                                                <th>Effective From</th>
                                                                <th>Effective Until</th>
                                                                <th>Reason</th>
                                                                <th>Supporting Document</th>
                                                                <th>Created By</th>
                                                                <th>Created At</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($concessions)): ?>
                                                                <?php foreach ($concessions as $concession):
                                                                    if ($concession['concession_category'] !== 'non_billable') continue;
                                                                    $academicYear = getAcademicYear($concession['effective_from']);
                                                                ?>
                                                                    <tr>
                                                                        <td><?= htmlspecialchars($concession['student_id']) ?></td>
                                                                        <td><?= htmlspecialchars($concession['studentname']) ?></td>
                                                                        <td><?= htmlspecialchars($concession['class']) ?></td>
                                                                        <td>
                                                                            <?= !empty($concession['concession_category']) ?
                                                                                ucwords(str_replace('_', ' ', $concession['concession_category'])) :
                                                                                'Uncategorized' ?>
                                                                        </td>
                                                                        <td>₹<?= number_format($concession['concession_amount'], 2) ?></td>
                                                                        <td><?= $academicYear ?></td>
                                                                        <td><?= $concession['formatted_from_date'] ?></td>
                                                                        <td><?= $concession['formatted_until_date'] ?></td>
                                                                        <td><?= nl2br(htmlspecialchars($concession['reason'])) ?></td>
                                                                        <td>
                                                                            <?php if (!empty($concession['supporting_document'])): ?>
                                                                                <a href="<?= htmlspecialchars($concession['supporting_document']) ?>" target="_blank">View</a>
                                                                            <?php else: ?>
                                                                                N/A
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td><?= htmlspecialchars($concession['created_by_name']) ?></td>
                                                                        <td><?= date('d M Y H:i', strtotime($concession['created_at'])) ?></td>
                                                                        <td>
                                                                            <?php if ($role === 'Admin'): ?>
                                                                                <button class="btn btn-sm btn-primary edit-concession"
                                                                                    data-id="<?= $concession['id'] ?>">
                                                                                    <i class="bi bi-pencil"></i> Edit
                                                                                </button>
                                                                            <?php endif; ?>
                                                                            <button class="btn btn-sm btn-info history-concession"
                                                                                data-id="<?= $concession['id'] ?>">
                                                                                <i class="bi bi-clock-history"></i> History
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <tr>
                                                                    <td colspan="12" class="text-center">No non-billable concessions found</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  

    <!-- Edit Modal -->
    <div class="modal fade" id="editConcessionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Modal content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Concession History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Changed By</th>
                                <th>Changes</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <!-- Will be populated by AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize chart data
        const academicYearLabels = <?= json_encode(array_keys($stats['academic_years'])) ?>;
        const academicYearData = <?= json_encode(array_column($stats['academic_years'], 'amount')) ?>;
        let academicYearChart = null;

        function toggleChart() {
            const container = document.getElementById('chartContainer');
            const yearList = document.getElementById('yearList');
            const btn = document.querySelector('.show-chart-btn');

            if (container.style.display === 'none') {
                container.style.display = 'block';
                yearList.style.display = 'none';
                btn.innerHTML = '<i class="bi bi-list-ul"></i> Show List';

                // Initialize chart if not already done
                if (!academicYearChart) {
                    const academicYearCtx = document.getElementById('academicYearChart').getContext('2d');
                    academicYearChart = new Chart(academicYearCtx, {
                        type: 'bar',
                        data: {
                            labels: academicYearLabels,
                            datasets: [{
                                label: 'Concession Amount (₹)',
                                data: academicYearData,
                                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return '₹' + context.raw.toLocaleString('en-IN', {
                                                minimumFractionDigits: 2
                                            });
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return '₹' + value.toLocaleString('en-IN');
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            } else {
                container.style.display = 'none';
                yearList.style.display = 'block';
                btn.innerHTML = '<i class="bi bi-bar-chart"></i> Show Chart';
            }
        }

        $(document).ready(function() {
            // Initialize Select2 for multi-select filters
            $('.select2-multiple').select2({
                //theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Select options',
                allowClear: true
            });

            // Initialize DataTable with server-side processing
            $(document).ready(function() {
                // Initialize DataTables
                <?php if (!empty($concessions)) : ?>
                    $('#billableTable').DataTable({
                        "order": [],
                        "stateSave": true,
                        "stateDuration": -1
                    });
                <?php endif; ?>

                // Show non-billable tab if URL has ?tab=non-billable
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('tab') === 'non-billable') {
                    $('#non-billable-tab').tab('show');

                    // Initialize non-billable table
                    <?php if (!empty($concessions)) : ?>
                        $('#nonBillableTable').DataTable({
                            "order": [],
                            "stateSave": true,
                            "stateDuration": -1
                        });
                    <?php endif; ?>
                }

                // Handle tab changes
                $('#concessionTabs a').on('shown.bs.tab', function(e) {
                    const tab = $(e.target).attr('href').split('=')[1];

                    // Update URL without reloading
                    const newUrl = updateQueryStringParameter('tab', tab);
                    window.history.pushState({
                        path: newUrl
                    }, '', newUrl);

                    // Initialize non-billable table if needed
                    if (tab === 'non-billable' && !$.fn.DataTable.isDataTable('#nonBillableTable')) {
                        <?php if (!empty($concessions)) : ?>
                            $('#nonBillableTable').DataTable({
                                "order": [],
                                "stateSave": true,
                                "stateDuration": -1
                            });
                        <?php endif; ?>
                    }
                });
            });

            // Helper function to update URL parameters
            function updateQueryStringParameter(key, value) {
                const url = new URL(window.location);
                url.searchParams.set(key, value);
                return url.toString();
            }

            // Handle filter form submission
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();

                // Get form data
                const formData = $(this).serialize();

                // Update URL with filter parameters
                const url = new URL(window.location.href);
                const params = new URLSearchParams(formData);

                // Update URL without reloading (for better UX)
                history.pushState({}, '', '?' + params.toString());

                // Submit the form normally to reload with filters
                this.submit();
            });

            // [Rest of your existing JavaScript code for edit/history modals...]
            // Handle Edit button click
            $('body').on('click', '.edit-concession', function() {
                const concessionId = $(this).data('id');
                const modal = $('#editConcessionModal');

                // Show loading placeholder
                modal.find('.modal-content').html(`
                <div class="modal-header">
                    <h5 class="modal-title">Loading Concession...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);
                modal.modal('show');

                // Fetch concession data
                $.get('get_concession.php?id=' + concessionId, function(response) {
                    if (response.success) {
                        // Populate modal with form
                        modal.find('.modal-content').html(`
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Concession</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="editConcessionForm" enctype="multipart/form-data">
                            <div class="modal-body">
                                <input type="hidden" name="id" value="${response.data.id}">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Student</label>
                                        <input type="text" class="form-control" value="${response.data.studentname} (${response.data.class})" readonly>
                                        <input type="hidden" name="student_id" value="${response.data.student_id}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Academic Year</label>
                                        <input type="text" class="form-control" value="${getAcademicYear(response.data.effective_from)}" readonly>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="editConcessionCategory" class="form-label">Concession Category <span class="text-danger">*</span></label>
                                        <select class="form-select" id="editConcessionCategory" name="concession_category" required>
                                            <option value="">-- Select Category --</option>
                                            ${generateOptions(response.data.concession_category)}
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="editSupportingDocument" class="form-label">Supporting Document</label>
                                        <input type="file" class="form-control" id="editSupportingDocument" name="supporting_document">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label for="editConcessionReason" class="form-label">Reason <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="editConcessionReason" name="reason" rows="3" required>${response.data.reason || ''}</textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="editConcessionFromMonth" class="form-label">Effective From (Month) <span class="text-danger">*</span></label>
                                        <input type="month" class="form-control" id="editConcessionFromMonth" value="${response.data.effective_from_month}" required>
                                        <input type="hidden" name="effective_from" id="editEffectiveFrom" value="${response.data.effective_from}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="editConcessionUntilMonth" class="form-label">Effective Until (Month)</label>
                                        <input type="month" class="form-control" id="editConcessionUntilMonth" value="${response.data.effective_until_month || ''}">
                                        <input type="hidden" name="effective_until" id="editEffectiveUntil" value="${response.data.effective_until || ''}">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="editConcessionAmount" class="form-label">Concession Amount <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="editConcessionAmount" name="concession_amount" value="${response.data.concession_amount}" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    `);

                        // Bind dynamic date update
                        $('#editConcessionFromMonth, #editConcessionUntilMonth').on('change', function() {
                            const date = new Date($(this).val() + '-01');
                            const formatted = date.toISOString().split('T')[0];
                            if ($(this).attr('id') === 'editConcessionFromMonth') {
                                $('#editEffectiveFrom').val(formatted);
                            } else {
                                $('#editEffectiveUntil').val(formatted);
                            }
                        });
                    } else {
                        showModalError(modal, response.message);
                    }
                }).fail(function() {
                    showModalError(modal, 'Failed to load concession data. Please try again.');
                });
            });

            // Form submission handler
            $(document).on('submit', '#editConcessionForm', function(e) {
                e.preventDefault();
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();

                submitBtn.prop('disabled', true).html(`
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...
    `);

                // Use FormData to handle file upload
                var formData = new FormData(this);

                $.ajax({
                    url: 'update_concession.php',
                    type: 'POST',
                    data: formData,
                    processData: false, // Important for file upload
                    contentType: false, // Important for file upload
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#editConcessionModal').modal('hide');
                            alert('Concession updated successfully');
                            location.reload();
                        } else {
                            alert(response.message);
                            submitBtn.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function(xhr) {
                        alert('Request failed: ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'));
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Helper: show error in modal
            function showModalError(modal, message) {
                modal.find('.modal-content').html(`
                <div class="modal-header">
                    <h5 class="modal-title">Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">${message}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            `);
            }

            // Helper: generate category options
            function generateOptions(selected) {
                const options = [
                    ['non_billable', 'Non-Billable Adjustment'],
                    ['rounding_off', 'Rounding Off Adjustment'],
                    ['financial_hardship', 'Financial Hardship / Economic Background'],
                    ['sibling', 'Sibling Concession'],
                    ['staff_child', 'Staff Child Concession'],
                    ['special_talent', 'Special Talent / Merit-Based'],
                    ['early_bird', 'Promotional / Early Bird Offer'],
                    ['scholarship', 'Scholarship-Based'],
                    ['referral', 'Referral / Community Support'],
                    ['special_case', 'Special Cases / Discretionary'],
                ];
                return options.map(opt => `<option value="${opt[0]}" ${selected === opt[0] ? 'selected' : ''}>${opt[1]}</option>`).join('');
            }

            // Handle History button click
            $('body').on('click', '.history-concession', function() {
                const concessionId = $(this).data('id');
                const modal = $('#historyModal');

                // Show loading state
                $('#historyTableBody').html(`
        <tr>
            <td colspan="4" class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </td>
        </tr>
    `);

                modal.modal('show');

                // Fetch concession history
                $.get('get_concession_history.php?id=' + concessionId, function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '';
                        response.data.forEach(entry => {
                            html += `
                <tr>
                    <td>${entry.formatted_date}</td>
                    <td>${entry.action}</td>
                    <td>${entry.changed_by_name}</td>
                    <td>${formatChanges(entry.old_values, entry.new_values)}</td>
                </tr>
                `;
                        });
                        $('#historyTableBody').html(html);
                    } else {
                        $('#historyTableBody').html(`
                <tr>
                    <td colspan="4" class="text-center">No history found for this concession</td>
                </tr>
            `);
                    }
                }).fail(function() {
                    $('#historyTableBody').html(`
            <tr>
                <td colspan="4" class="text-center text-danger">Failed to load history</td>
            </tr>
        `);
                });
            });

            // Function to format changes for display
            function formatChanges(oldValues, newValues) {
                try {
                    // Parse the JSON if it's a string
                    const oldData = typeof oldValues === 'string' ? JSON.parse(oldValues) : oldValues || {};
                    const newData = typeof newValues === 'string' ? JSON.parse(newValues) : newValues || {};

                    let changes = [];

                    // Check each field for changes - ADDED supporting_document
                    const fieldsToCheck = [
                        'concession_category', 'concession_amount',
                        'effective_from', 'effective_until', 'reason', 'supporting_document'
                    ];

                    fieldsToCheck.forEach(field => {
                        const oldVal = oldData[field];
                        const newVal = newData[field];

                        // Skip if both are null/undefined/empty or equal
                        if ((!oldVal && !newVal) || oldVal === newVal) return;

                        // Handle supporting_document field differently
                        if (field === 'supporting_document') {
                            if (!oldVal && newVal) {
                                changes.push('Document: Added');
                            } else if (oldVal && !newVal) {
                                changes.push('Document: Removed');
                            } else if (oldVal !== newVal) {
                                changes.push('Document: Updated');
                            }
                        } else {
                            // Format field name for display
                            const fieldName = field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

                            // Format values for display
                            const displayOldVal = oldVal !== undefined ?
                                (field === 'concession_amount' ? '₹' + parseFloat(oldVal).toFixed(2) : oldVal) :
                                'empty';
                            const displayNewVal = newVal !== undefined ?
                                (field === 'concession_amount' ? '₹' + parseFloat(newVal).toFixed(2) : newVal) :
                                'empty';

                            changes.push(`${fieldName}: ${displayOldVal} → ${displayNewVal}`);
                        }
                    });

                    return changes.length > 0 ? changes.join('<br>') : 'No changes detected';
                } catch (e) {
                    console.error('Error formatting changes:', e);
                    return 'Changes could not be displayed';
                }
            }

            // Function to determine academic year from date (client-side version)
            function getAcademicYear(dateString) {
                if (!dateString) return 'Indefinite';
                const date = new Date(dateString);
                const year = date.getFullYear();
                const month = date.getMonth() + 1; // JS months are 0-indexed

                if (month >= 4) { // April or later
                    return year + '-' + (year + 1);
                } else { // January-March
                    return (year - 1) + '-' + year;
                }
            }
        });
        // Replace the Select2 initialization for academic year filter with this:
        $('#academicYearFilter').select2({
            width: '100%',
            placeholder: 'Select academic years',
            allowClear: true,
            minimumResultsForSearch: 5 // Only show search box when there are more than 5 options
        });

        // Add this to limit the visible options while keeping all available
        $(document).ready(function() {
            const academicYearSelect = $('#academicYearFilter');
            const options = academicYearSelect.find('option');

            // Hide all options except the first 5 (which should be the latest years)
            options.each(function(index) {
                if (index >= 5 && $(this).val() !== '') {
                    $(this).addClass('d-none');
                }
            });

            // Show all options when searching
            academicYearSelect.on('select2:opening', function() {
                options.removeClass('d-none');
            });

            // Hide non-matching options after search
            academicYearSelect.on('select2:searching', function(e) {
                if (!e.target.value) {
                    options.each(function(index) {
                        if (index >= 5 && $(this).val() !== '') {
                            $(this).addClass('d-none');
                        }
                    });
                }
            });
        });
        $('#filterForm').on('reset', function() {
            setTimeout(function() {
                $('#academicYearFilter').val(null).trigger('change');
                // Hide all options except first 5 again
                $('#academicYearFilter').find('option').each(function(index) {
                    if (index >= 5 && $(this).val() !== '') {
                        $(this).addClass('d-none');
                    }
                });
                $('#filterForm').submit();
            }, 10);
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#student-select').select2({
                ajax: {
                    url: 'fetch_students.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term, // search term
                            // isActive: true // or false depending on your needs
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Search by Student ID or Name',
                // allowClear: true,
                width: '100%'
            });
        });
    </script>
    <script>
        // Track current active tab
        let currentTab = '<?= $activeTab ?>'; // Initialize with PHP value

        // Update currentTab when switching tabs
        $('#concessionTabs a').on('shown.bs.tab', function(e) {
            const tabHref = $(e.target).attr('href');
            currentTab = tabHref.includes('non-billable') ? 'non-billable' : 'billable';
        });

        // Export functionality
        $(document).on('click', '.export-btn', function(e) {
            e.preventDefault();

            // Show loading indicator
            const originalText = $(this).html();
            $(this).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Exporting...');

            // Submit form to export endpoint
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'export_concessions.php';

            // Add tab parameter based on currentTab
            const tabInput = document.createElement('input');
            tabInput.type = 'hidden';
            tabInput.name = 'tab';
            tabInput.value = currentTab;
            form.appendChild(tabInput);

            // Add filter parameters
            const filters = getCurrentFilters();
            Object.entries(filters).forEach(([key, values]) => {
                if (Array.isArray(values)) {
                    values.forEach(value => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `filters[${key}][]`;
                        input.value = value;
                        form.appendChild(input);
                    });
                }
            });

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

            // Restore button text after a delay
            setTimeout(() => {
                $('.export-btn').html(originalText);
            }, 3000);
        });

        // Helper function to get current filter values
        function getCurrentFilters() {
            return {
                student_id: $('#student-select').val() || [],
                concession_category: $('select[name="concession_category[]"]').val() || [],
                academic_year: $('#academicYearFilter').val() || []
            };
        }
    </script>
</body>

</html>