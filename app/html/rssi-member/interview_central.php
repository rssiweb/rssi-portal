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

// Get current date for default date range
$today = date("Y-m-d");
$defaultStartDate = $today;
$defaultEndDate = $today;

// Handle filtering - using POST like the Talent Pool page
$filter_search = isset($_POST['filter_search']) ? trim($_POST['filter_search']) : '';
$filter_status = isset($_POST['status']) ? $_POST['status'] : [];
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : $defaultStartDate;
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : $defaultEndDate;
$disable_filters = isset($_POST['disable_filters']) ? true : false;

// Server-side validation: if disable_filters is checked but no search is provided
if ($disable_filters) {
    if (empty($filter_search) && empty($filter_status)) {
        echo "<script>
            alert('Error: Please enter Application Number/Name or select at least one Status when disabling date filters.');
            window.onload = function() {
                document.getElementById('disable_filters').checked = false;
                document.getElementById('start_date').disabled = false;
                document.getElementById('end_date').disabled = false;
            };
        </script>";
        $disable_filters = false; // Reset disable_filters so form reloads with date range enabled
    }
}

// Start building the query
$query = "SELECT * FROM signup 
          WHERE application_status IN ('Technical Interview Scheduled', 'Technical Interview Completed', 'HR Interview Scheduled', 'HR Interview Completed') AND is_active=true";

// Add filters based on user input
$conditions = [];

if (!$disable_filters) {
    // Apply date range filter only when filters are not disabled
    if (!empty($start_date) && !empty($end_date)) {
        $conditions[] = "(
            (tech_interview_schedule::date BETWEEN '$start_date' AND '$end_date') OR 
            (hr_interview_schedule::date BETWEEN '$start_date' AND '$end_date')
        )";
    }
}

// Search by application number or applicant name
if (!empty($filter_search)) {
    $searchTerm = pg_escape_string($con, $filter_search);
    $conditions[] = "(application_number ILIKE '%$searchTerm%' OR applicant_name ILIKE '%$searchTerm%')";
}

if (!empty($filter_status) && !$disable_filters) {
    $statuses = array_map(function ($status) use ($con) {
        return pg_escape_string($con, $status);
    }, $filter_status);
    $conditions[] = "application_status IN ('" . implode("', '", $statuses) . "')";
}

// Append conditions dynamically
if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

// Add the ORDER BY clause
$query .= " ORDER BY tech_interview_schedule DESC";

// Add a limit of 100 if no filters are applied
if (empty($filter_search) && empty($filter_status) && $disable_filters) {
    $query .= " LIMIT 100";
}

// Execute the query
$result = pg_query($con, $query);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

// Fetch and process the results
$resultArr = pg_fetch_all($result);

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

    <title>Interview Central</title>

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
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section Interview Central">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container">
                                <form id="filterForm" method="POST" class="filter-form d-flex flex-wrap" style="gap: 10px;">
                                    <div class="form-group">
                                        <input type="text" id="filter_search" name="filter_search" class="form-control" placeholder="Application Number or Name" value="<?php echo htmlspecialchars($filter_search); ?>" style="max-width: 200px;">
                                        <small class="form-text text-muted">Application Number or Name</small>
                                    </div>

                                    <!-- Date Range Filter -->
                                    <div class="form-group">
                                        <input type="date" name="start_date" id="start_date" class="form-control"
                                            value="<?php echo htmlspecialchars($start_date); ?>" />
                                        <small class="form-text text-muted">Select the starting date for the range.</small>
                                    </div>

                                    <div class="form-group">
                                        <input type="date" name="end_date" id="end_date" class="form-control"
                                            value="<?php echo htmlspecialchars($end_date); ?>" />
                                        <small class="form-text text-muted">Select the ending date for the range.</small>
                                    </div>

                                    <div class="form-group">
                                        <select id="status" name="status[]" class="form-select" multiple>
                                            <option value="Technical Interview Scheduled" <?php echo in_array('Technical Interview Scheduled', $filter_status ?? []) ? 'selected' : ''; ?>>Technical Interview Scheduled</option>
                                            <option value="Technical Interview Completed" <?php echo in_array('Technical Interview Completed', $filter_status ?? []) ? 'selected' : ''; ?>>Technical Interview Completed</option>
                                            <option value="HR Interview Scheduled" <?php echo in_array('HR Interview Scheduled', $filter_status ?? []) ? 'selected' : ''; ?>>HR Interview Scheduled</option>
                                            <option value="HR Interview Completed" <?php echo in_array('HR Interview Completed', $filter_status ?? []) ? 'selected' : ''; ?>>HR Interview Completed</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-search"></i>&nbsp;Filter
                                        </button>
                                    </div>
                                    <!-- New line for checkbox -->
                                    <div class="w-100"></div> <!-- This forces a new line in flex-wrap -->

                                    <!-- Disable Date Filters Checkbox -->
                                    <div class="form-group">
                                        <input class="form-check-input" type="checkbox" name="disable_filters" id="disable_filters" value="1" <?php echo isset($disable_filters) && $disable_filters ? 'checked' : ''; ?>>
                                        <label for="disable_filters">Ignore Date Range</label>
                                    </div>
                                </form>
                            </div>
                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th scope="col">Application Number</th>
                                            <th scope="col">Applicant Name</th>
                                            <th scope="col">Technical Interview Scheduled On</th>
                                            <th scope="col">HR Interview Scheduled On</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Enter Evaluation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($disable_filters && !empty($filter_search) && empty($resultArr)) {
                                            echo '<tr><td colspan="6" class="text-center">No interviews scheduled for "' . htmlspecialchars($filter_search) . '"</td></tr>';
                                        } elseif (empty($resultArr)) {
                                            echo '<tr><td colspan="6" class="text-center">No interviews found for the selected criteria.</td></tr>';
                                        } else {
                                            foreach ($resultArr as $array) {
                                                $interviewTimestamp = empty($array['tech_interview_schedule']) ? 'Not scheduled yet' : @date("d/m/Y g:i a", strtotime($array['tech_interview_schedule']));
                                                $hrTimestamp = empty($array['hr_interview_schedule']) ? 'Not scheduled yet' : @date("d/m/Y g:i a", strtotime($array['hr_interview_schedule']));
                                                $linkToShow = '';

                                                // Check if HR interview is scheduled
                                                if (!empty($array['hr_interview_schedule'])) {
                                                    $linkToShow = '<a href="hr_interview.php?applicationNumber_verify=' . $array['application_number'] . '">HR Interview</a>';
                                                }
                                                // Check if TR interview is scheduled and HR interview is not scheduled
                                                elseif (!empty($array['tech_interview_schedule']) && $array['application_status'] != 'No-Show') {
                                                    $linkToShow = '<a href="technical_interview.php?applicationNumber_verify=' . $array['application_number'] . '">Technical Interview</a>';
                                                }

                                                $interviewStatus = empty($array['application_status']) ? '' : $array['application_status'];
                                        ?>
                                                <tr>
                                                    <td><?php echo $array['application_number']; ?></td>
                                                    <td><?php echo $array['applicant_name']; ?></td>
                                                    <td><?php echo $interviewTimestamp; ?></td>
                                                    <td><?php echo $hrTimestamp; ?></td>
                                                    <td><?php echo $interviewStatus; ?></td>
                                                    <td><?php echo $linkToShow; ?></td>
                                                </tr>
                                        <?php
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
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
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const disableFiltersCheckbox = document.getElementById('disable_filters');
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const filterSearch = document.getElementById('filter_search');
            const statusSelect = document.getElementById('status');
            const filterForm = document.getElementById('filterForm');

            function toggleFilterInputs() {
                const isDisabled = disableFiltersCheckbox.checked;
                startDate.disabled = isDisabled;
                endDate.disabled = isDisabled;
                statusSelect.disabled = isDisabled;

                if (isDisabled) {
                    filterSearch.focus();
                }
            }

            disableFiltersCheckbox.addEventListener('change', toggleFilterInputs);
            toggleFilterInputs();

            // Form validation before submit (client-side)
            filterForm.addEventListener('submit', function(e) {
                if (disableFiltersCheckbox.checked) {
                    const searchTerm = filterSearch.value.trim();
                    const statusSelected = Array.from(statusSelect.selectedOptions).length > 0;

                    if (searchTerm === '' && !statusSelected) {
                        e.preventDefault();
                        alert("Please enter Application Number/Name or select at least one Status when disabling date filters.");
                        disableFiltersCheckbox.checked = false;
                        toggleFilterInputs();
                    }
                }
            });

            // Set default dates to today if empty
            const today = new Date().toISOString().split('T')[0];
            if (!startDate.value) {
                startDate.value = today;
            }
            if (!endDate.value) {
                endDate.value = today;
            }
        });
    </script>
</body>

</html>