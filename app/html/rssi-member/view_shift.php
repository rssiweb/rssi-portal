<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$currentDate = date('Y-m-d');

// Get filters from GET data
$filterStatus = isset($_GET['filter_status']) ? $_GET['filter_status'] : ['active'];
$associateNumber = isset($_GET['associate_number']) ? trim($_GET['associate_number']) : null;

// Base query to fetch data - ordered by start_date DESC to get latest first
$query = "
    SELECT s.*, m.fullname, m.filterstatus, m.effectivedate, m.job_type, m.engagement
    FROM associate_schedule s
    INNER JOIN rssimyaccount_members m ON s.associate_number = m.associatenumber
    WHERE (COALESCE('$associateNumber', '') = '' OR s.associate_number = '$associateNumber')
    ORDER BY s.associate_number, s.start_date DESC, s.timestamp DESC
";

$result = pg_query($con, $query);

if (!$result) {
    die("Error executing query: " . pg_last_error($con));
}

$data = [];
while ($row = pg_fetch_assoc($result)) {
    $associateNumber = $row['associate_number'];
    $startDate = $row['start_date'];
    $reportingTime = $row['reporting_time'];
    $exitTime = $row['exit_time'];
    $filterStatusDB = $row['filterstatus'];
    $effectiveDate = $row['effectivedate'];
    $workdays = $row['workdays'] ?? ''; // Handle null workdays

    if (!isset($data[$associateNumber])) {
        $data[$associateNumber] = [];
    }

    $entry = [
        'associate_number' => $associateNumber,
        'fullname' => $row['fullname'],
        'job_type' => $row['job_type'],
        'engagement' => $row['engagement'],
        'start_date' => $startDate,
        'end_date' => null, // Will be set dynamically
        'reporting_time' => $reportingTime,
        'exit_time' => $exitTime,
        'timestamp' => $row['timestamp'],
        'submittedby' => $row['submittedby'],
        'filterstatus' => $filterStatusDB,
        'effectivedate' => $effectiveDate,
        'workdays' => $workdays,
    ];

    // Get the previous entry for comparison (which is actually the next chronological entry)
    $previousEntryIndex = count($data[$associateNumber]) - 1;
    if ($previousEntryIndex >= 0) {
        $previousEntry = &$data[$associateNumber][$previousEntryIndex];

        // Check if the timing changes
        if (
            $previousEntry['reporting_time'] === $reportingTime &&
            $previousEntry['exit_time'] === $exitTime &&
            $previousEntry['workdays'] === $workdays
        ) {
            // Extend the previous entry's start_date to cover this one
            // Since we're processing in reverse chronological order
            continue;
        } else {
            // Set the current entry's end_date to the day before the next entry's start_date
            $entry['end_date'] = date('Y-m-d', strtotime($previousEntry['start_date'] . ' -1 day'));
        }
    }

    // Add the new entry
    $data[$associateNumber][] = $entry;
}

// Finalize end_date for the first entry (latest) in each group
foreach ($data as $associateNumber => &$entries) {
    if (count($entries) > 0) {
        // The first entry is the most recent one
        $firstEntry = &$entries[0];
        if (empty($firstEntry['end_date'])) {
            // If no end_date set, it's the current schedule - set to current date or effective date
            $firstEntry['end_date'] = !empty($firstEntry['effectivedate']) ?
                $firstEntry['effectivedate'] : $currentDate;
        }

        // Ensure all entries have valid end_dates
        foreach ($entries as &$entry) {
            if (empty($entry['end_date'])) {
                $entry['end_date'] = $currentDate;
            }
        }
    }
}

// Filter the results based on selected statuses
if (!empty($filterStatus)) {
    foreach ($data as $associateNumber => &$entries) {
        $entries = array_filter($entries, function ($entry) use ($filterStatus, $currentDate) {
            $isActive = strtotime($entry['end_date']) >= strtotime($currentDate);
            $status = $isActive ? 'active' : 'history';
            return in_array($status, $filterStatus);
        });
    }
    // Remove associates with no matching entries
    $data = array_filter($data);
}

pg_close($con); // Close the connection
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

    <title>View Shift</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for associate numbers
            $('#associate_number').select2({
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
                minimumInputLength: 2,
                placeholder: 'Select associate(s)',
                allowClear: true,
                multiple: false
            });
        });
    </script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>View Shift</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Schedule Hub</a></li>
                    <li class="breadcrumb-item active">View Shift</li>
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
                            <div class="container">
                                <form method="GET" action="" class="filter-form d-flex flex-wrap" style="gap: 10px;">
                                    <!-- Associate Number Input -->
                                    <div class="col-md-3 col-lg-2">
                                        <div class="form-group">
                                            <select class="form-control" id="associate_number" name="associate_number" required>
                                                <?php if (!empty($associateNumber)): ?>
                                                    <option value="<?= isset($_GET['associate_number']) ? trim($_GET['associate_number']) : null ?>" selected><?= isset($_GET['associate_number']) ? trim($_GET['associate_number']) : null ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Status Multiselect Dropdown -->
                                    <div class="form-group">
                                        <select class="form-select" style="min-width: 200px;" id="filter_status" name="filter_status[]" multiple>
                                            <option value="active" <?php echo in_array('active', $_GET['filter_status'] ?? ['active']) ? 'selected' : ''; ?>>Active</option>
                                            <option value="history" <?php echo in_array('history', $_GET['filter_status'] ?? []) ? 'selected' : ''; ?>>History</option>
                                        </select>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-sm" style="outline: none;">
                                            <i class="bi bi-search"></i>&nbsp;Filter
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="table-responsive">
                                <table id="scheduleTable" class="table">
                                    <thead>
                                        <tr>
                                            <th>Associate Number</th>
                                            <th>Associate Name</th>
                                            <th>Association Type</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Reporting Time</th>
                                            <th>Exit Time</th>
                                            <th>Work Days</th>
                                            <th>Working Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $associateRows): ?>
                                            <?php foreach ($associateRows as $row): ?>
                                                <?php
                                                // Determine if this is an active record
                                                $isActive = strtotime($row['end_date']) >= strtotime($currentDate);
                                                $rowClass = $isActive ? 'table-success' : '';
                                                ?>
                                                <tr class="<?php echo $rowClass; ?>">
                                                    <td><?php echo htmlspecialchars($row['associate_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['job_type']); ?> -<?php echo htmlspecialchars($row['engagement']); ?></td>
                                                    <td><?php echo date("d/m/Y", strtotime($row['start_date'])); ?></td>
                                                    <td>
                                                        <?php
                                                        $endDate = $row['end_date'];
                                                        if ($endDate && $endDate != $currentDate) {
                                                            echo date("d/m/Y", strtotime($endDate));
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo date("h:i A", strtotime($row['reporting_time'])); ?></td>
                                                    <td><?php echo date("h:i A", strtotime($row['exit_time'])); ?></td>
                                                    <td><?php echo htmlspecialchars($row['workdays']); ?></td>
                                                    <td>
                                                        <?php
                                                        $exit_time = $row['exit_time'];
                                                        $reporting_time = $row['reporting_time'];

                                                        $exit_seconds = strtotime($exit_time);
                                                        $reporting_seconds = strtotime($reporting_time);

                                                        if ($exit_seconds !== false && $reporting_seconds !== false) {
                                                            $duration = $exit_seconds - $reporting_seconds;
                                                            $hours = floor($duration / 3600);
                                                            $minutes = floor(($duration % 3600) / 60);
                                                            echo htmlspecialchars($hours . 'h ' . $minutes . 'm');
                                                        } else {
                                                            echo 'Invalid time format';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <!-- Bootstrap JS and Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#scheduleTable').DataTable({
                order: [], // Disable initial sorting
                columnDefs: [{
                        targets: [2, 3, 4, 5, 6, 7, 8],
                        orderable: false
                    } // Disable sorting on all columns except first two
                ]
            });

            // Initialize Select2 for status filter
            $('#filter_status').select2({
                placeholder: "Select status",
                allowClear: true
            });
        });
    </script>
</body>

</html>