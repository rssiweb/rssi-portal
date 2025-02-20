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

// Base query to fetch data
$query = "
    SELECT s.*, m.fullname, m.filterstatus, m.effectivedate, m.job_type,m.engagement
    FROM associate_schedule s
    INNER JOIN rssimyaccount_members m ON s.associate_number = m.associatenumber
    WHERE (COALESCE('$associateNumber', '') = '' OR s.associate_number = '$associateNumber')
    ORDER BY s.associate_number, s.start_date, s.timestamp DESC
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
    ];

    // Get the previous entry for comparison
    $previousEntryIndex = count($data[$associateNumber]) - 1;
    if ($previousEntryIndex >= 0) {
        $previousEntry = &$data[$associateNumber][$previousEntryIndex];

        // Check if the timing changes
        if (
            $previousEntry['reporting_time'] === $reportingTime &&
            $previousEntry['exit_time'] === $exitTime
        ) {
            // Extend the previous entry's end_date
            $previousEntry['end_date'] = $startDate;
            continue;
        } else {
            // Finalize the previous entry's end_date as the day before the new start_date
            $previousEntry['end_date'] = date('Y-m-d', strtotime("$startDate -1 day"));
        }
    }

    // Add the new entry
    $data[$associateNumber][] = $entry;
}

// Finalize end_date for the last entry in each group
foreach ($data as $associateNumber => &$entries) {
    $lastEntryIndex = count($entries) - 1;

    if ($lastEntryIndex >= 0) {
        $lastEntry = &$entries[$lastEntryIndex];
        if (isset($lastEntry['effectivedate'])) {
            // Use effective date for end_date
            $lastEntry['end_date'] = $lastEntry['effectivedate'];
        } else {
            // Extend to current date
            $lastEntry['end_date'] = $currentDate;
        }
    }

    // Reverse entries for each associate to show latest first
    $entries = array_reverse($entries);
}

// Filter the results based on selected statuses
if (!empty($filterStatus)) {
    foreach ($data as $associateNumber => &$entries) {
        $entries = array_filter($entries, function ($entry) use ($filterStatus, $currentDate) {
            $status = (strtotime($entry['end_date']) >= strtotime($currentDate)) ? 'active' : 'history';
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
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
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
                    <li class="breadcrumb-item"><a href="#">Workforce Management</a></li>
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
                                    <div class="form-group">
                                        <!-- <label for="associate_number" class="form-label">Associate Number</label> -->
                                        <input type="text" class="form-control" id="associate_number" name="associate_number"
                                            placeholder="Enter Associate Number" value="<?php echo htmlspecialchars($_GET['associate_number'] ?? ''); ?>">
                                    </div>

                                    <!-- Status Multiselect Dropdown -->
                                    <div class="form-group">
                                        <!-- <label for="filter_status" class="form-label">Status</label> -->
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
                                            <th>Working Hours</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $associateRows): ?>
                                            <?php foreach ($associateRows as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['associate_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['job_type']); ?> -<?php echo htmlspecialchars($row['engagement']); ?></td>
                                                    <td><?php echo date("d/m/Y", strtotime($row['start_date'])); ?></td>
                                                    <td><?php echo (date("d/m/Y", strtotime($row['end_date'])) === date("d/m/Y")) ? null : date("d/m/Y", strtotime($row['end_date'])); ?></td>
                                                    <td>
                                                        <?php
                                                        echo date("h:i A", strtotime($row['reporting_time'])); // Format as HH:MM AM/PM
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        echo date("h:i A", strtotime($row['exit_time'])); // Format as HH:MM AM/PM
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $exit_time = $row['exit_time']; // e.g., "18:30:00"
                                                        $reporting_time = $row['reporting_time']; // e.g., "10:45:00"

                                                        // Convert time strings to seconds since the start of the day
                                                        $exit_seconds = strtotime($exit_time);
                                                        $reporting_seconds = strtotime($reporting_time);

                                                        // Calculate the duration in seconds
                                                        if ($exit_seconds !== false && $reporting_seconds !== false) {
                                                            $duration = $exit_seconds - $reporting_seconds;

                                                            // Convert the duration to a human-readable format
                                                            if ($duration < 60) {
                                                                // Less than 60 seconds
                                                                echo htmlspecialchars($duration . ' seconds');
                                                            } elseif ($duration < 3600) {
                                                                // Less than 60 minutes
                                                                $minutes = floor($duration / 60);
                                                                $seconds = $duration % 60;
                                                                echo htmlspecialchars($minutes . ' minutes ' . $seconds . ' seconds');
                                                            } else {
                                                                // 60 minutes or more
                                                                $hours = floor($duration / 3600);
                                                                $minutes = floor(($duration % 3600) / 60);
                                                                echo htmlspecialchars($hours . ' hours ' . $minutes . ' minutes');
                                                            }
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
            // Check if resultArr is empty
            <?php if (!empty($result)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#scheduleTable').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>