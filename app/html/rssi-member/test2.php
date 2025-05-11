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
                FROM sanitary_pad_distribution pd
                JOIN rssimyprofile_student s ON pd.student_id = s.student_id
                JOIN rssimyaccount_members st ON pd.recorded_by = st.associatenumber
                WHERE s.filterstatus='Active'
                AND pd.distribution_date BETWEEN '$academicYearStart' AND '$academicYearEnd'";

// Initialize variables for all tabs
$healthResult = $periodResult = $padResult = $healthFilterResult = $periodFilterResult = $padFilterResult = null;

// Fetch stats for dashboard (always fetch these)
$statsQuery = "SELECT 
                (SELECT COUNT(*) FROM rssimyprofile_student WHERE filterstatus='Active') as total_students,
                (SELECT COUNT(*) FROM student_health_records 
                 WHERE record_date BETWEEN '$academicYearStart' AND '$academicYearEnd') as yearly_checks,
                (SELECT COUNT(*) FROM sanitary_pad_distribution
                 WHERE distribution_date BETWEEN '$academicYearStart' AND '$academicYearEnd') as yearly_pads";
$statsResult = pg_query($con, $statsQuery);
$stats = pg_fetch_assoc($statsResult);

// Fetch data based on active tab
switch ($activeTab) {
    case 'dashboard':
        $healthQuery = $baseHealthQuery . " ORDER BY sh.record_date DESC LIMIT 5";
        $healthResult = pg_query($con, $healthQuery);

        $periodQuery = $basePeriodQuery . " ORDER BY pr.cycle_start_date DESC LIMIT 10";
        $periodResult = pg_query($con, $periodQuery);

        $padQuery = $basePadQuery . " ORDER BY pd.distribution_date DESC LIMIT 10";
        $padResult = pg_query($con, $padQuery);
        break;

    case 'health-records':
        $healthFilterQuery = $baseHealthQuery;

        if (!empty($_GET['class'])) {
            $class = pg_escape_string($con, $_GET['class']);
            $healthFilterQuery .= " AND s.class = '$class'";
        }

        if (!empty($_GET['search'])) {
            $search = pg_escape_string($con, $_GET['search']);
            $healthFilterQuery .= " AND (s.studentname ILIKE '%$search%' OR s.student_id::text ILIKE '%$search%')";
        }

        $healthFilterQuery .= " ORDER BY sh.record_date DESC";
        $healthFilterResult = pg_query($con, $healthFilterQuery);
        break;

    case 'period-tracking':
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

        $periodFilterQuery .= " ORDER BY pr.cycle_start_date DESC";
        $periodFilterResult = pg_query($con, $periodFilterQuery);
        break;

    case 'pad-distribution':
        $padFilterQuery = $basePadQuery . " AND s.gender = 'Female'";

        if (isset($_GET['class']) && $_GET['class'] != '') {
            $padFilterQuery .= " AND s.class = '" . pg_escape_string($con, $_GET['class']) . "'";
        }

        if (isset($_GET['month']) && $_GET['month'] != '') {
            $month = $_GET['month'];
            $padFilterQuery .= " AND EXTRACT(MONTH FROM pd.distribution_date) = $month";
        }

        $padFilterQuery .= " ORDER BY pd.distribution_date DESC";
        $padFilterResult = pg_query($con, $padFilterQuery);
        break;

    case 'reports':
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
        break;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Health Records Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Your existing CSS styles */
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
                                href="<?php echo getCurrentUrlWithTab('dashboard'); ?>">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activeTab == 'health-records') ? 'active' : ''; ?>"
                                href="<?php echo getCurrentUrlWithTab('health-records'); ?>">
                                <i class="bi bi-clipboard2-pulse"></i> Health Records
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activeTab == 'period-tracking') ? 'active' : ''; ?>"
                                href="<?php echo getCurrentUrlWithTab('period-tracking'); ?>">
                                <i class="bi bi-calendar-heart"></i> Period Tracking
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activeTab == 'pad-distribution') ? 'active' : ''; ?>"
                                href="<?php echo getCurrentUrlWithTab('pad-distribution'); ?>">
                                <i class="bi bi-box-seam"></i> Sanitary Pads
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($activeTab == 'reports') ? 'active' : ''; ?>"
                                href="<?php echo getCurrentUrlWithTab('reports'); ?>">
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
                                    echo '<li><a class="dropdown-item ' . $activeClass . '" href="' . getCurrentUrlWithTab($activeTab) . '&academic_year=' . $ay . '">' . $ay . '</a></li>';
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="tab-content">
                    <!-- Dashboard Tab -->
                    <div class="tab-pane <?php echo $activeTab == 'dashboard' ? 'show active' : ''; ?>" id="dashboard">
                        <!-- Your dashboard content -->
                    </div>

                    <!-- Health Records Tab -->
                    <div class="tab-pane <?php echo $activeTab == 'health-records' ? 'show active' : ''; ?>" id="health-records">
                        <!-- Your health records content -->
                    </div>

                    <!-- Period Tracking Tab -->
                    <div class="tab-pane <?php echo $activeTab == 'period-tracking' ? 'show active' : ''; ?>" id="period-tracking">
                        <!-- Your period tracking content -->
                    </div>

                    <!-- Sanitary Pad Distribution Tab -->
                    <div class="tab-pane <?php echo $activeTab == 'pad-distribution' ? 'show active' : ''; ?>" id="pad-distribution">
                        <!-- Your pad distribution content -->
                    </div>

                    <!-- Reports Tab -->
                    <div class="tab-pane <?php echo $activeTab == 'reports' ? 'show active' : ''; ?>" id="reports">
                        <!-- Your reports content -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Simplified tab handling - let the server handle the tab switching via URL parameters
        document.addEventListener('DOMContentLoaded', function() {
            // Academic year dropdown functionality
            const academicYearLinks = document.querySelectorAll('.dropdown-item[data-year]');
            academicYearLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const selectedYear = this.getAttribute('data-year');
                    
                    // Update the URL with the new academic year
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('academic_year', selectedYear);
                    
                    // Reload the page with the new academic year
                    window.location.href = window.location.pathname + '?' + urlParams.toString();
                });
            });

            // Initialize charts if needed
            <?php if ($activeTab == 'dashboard'): ?>
                loadGrowthChart();
            <?php endif; ?>

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
        });

        function loadGrowthChart() {
            const growthCtx = document.getElementById('growthChart');
            if (!growthCtx) return;

            const academicYear = document.getElementById('currentAcademicYear').textContent;

            // This would be replaced with your actual data fetching logic
            const data = {
                months: ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
                avg_height: [140, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152],
                avg_weight: [40, 41, 41.5, 42, 42.5, 43, 43.5, 44, 44.5, 45, 45.5, 46]
            };

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
    </script>
</body>
</html>