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
ob_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = $_POST['month'];
    $year = $_POST['year'];
    $action = $_POST['action'];

    // Debug output
    error_log("Processing $action action for $month $year");

    // Determine the lock status and action text
    $is_locked = ($action === 'lock') ? 'TRUE' : 'FALSE';
    $action_text = ($action === 'lock') ? 'Locked' : 'Unlocked';

    // Build the query with proper parameter handling
    $query = "INSERT INTO fee_collection_lock (month, year, is_locked, locked_by, locked_by_name, locked_at, last_action, action_history)
              VALUES ($1, $2, $3, $4, $5, NOW(), NOW(), $6)
              ON CONFLICT (month, year) DO UPDATE 
              SET is_locked = $3,
                  locked_by = $4,
                  locked_by_name = $5,
                  locked_at = CASE WHEN $3 THEN NOW() ELSE fee_collection_lock.locked_at END,
                  last_action = NOW(),
                  action_history = COALESCE(fee_collection_lock.action_history || '\n', '') || $6";

    $params = [
        $month,
        $year,
        $is_locked,
        $associatenumber,
        $fullname,
        "$action_text by $fullname on " . date('Y-m-d H:i:s')
    ];

    // Execute the query with parameters
    $result = pg_query_params($con, $query, $params);

    if (!$result) {
        $error = pg_last_error($con);
        error_log("Database error: " . $error);
        // For debugging - remove in production
        die("Database operation failed: " . $error);
    } else {
        error_log("Successfully processed $action action for $month $year");
    }

    ob_end_clean();
    header("Location: fee_lock_management.php?year=" . $_POST['year']);
    exit;
}

$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get distinct years from database
$yearsQuery = "SELECT DISTINCT year FROM fee_collection_lock ORDER BY year DESC";
$yearsResult = pg_query($con, $yearsQuery);
$availableYears = pg_fetch_all_columns($yearsResult) ?? [];

// Get current year and surrounding years
$currentYear = date('Y');
$requiredYears = [
    $currentYear - 1, // Previous year
    $currentYear,     // Current year
    $currentYear + 1  // Next year
];

// Merge and deduplicate
$availableYears = array_unique(array_merge($availableYears, $requiredYears));

// Sort in descending order
rsort($availableYears);

$lockStatusQuery = "SELECT *, 
                   COALESCE(locked_at, '1970-01-01'::timestamp) as locked_at,
                   COALESCE(last_action, '1970-01-01'::timestamp) as last_action,
                   COALESCE(TRIM(BOTH FROM action_history), '') as action_history
                   FROM fee_collection_lock 
                   ORDER BY year DESC, month DESC";
$lockStatusResult = pg_query($con, $lockStatusQuery);
$lockStatus = pg_fetch_all($lockStatusResult) ?? [];

$months = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December'
];
?>

<!DOCTYPE html>
<html>

<head>
    <title>Fee Collection Lock Management</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        .current-month {
            background-color: rgba(13, 110, 253, 0.1);
        }

        .action-history {
            /* max-width: 300px; */
            /* white-space: pre-wrap; */
            word-wrap: break-word;
        }
    </style>
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Fee Collection Lock Management</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Fee Portal</a></li>
                    <li class="breadcrumb-item active">Fee Collection Lock Management</li>
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
                            <div class="container-fluid mt-4">
                                <div class="card">
                                    <div class="card-header bg-primary text-white" style="display: flex; justify-content: space-between; align-items: center;">
                                        <h3><i class="fas fa-lock"></i> Fee Collection Lock Management</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="ms-auto mt-4"> <!-- This will push the form to the right -->
                                                <form method="get" class="d-flex">
                                                    <select name="year" class="form-select me-2" onchange="this.form.submit()">
                                                        <?php foreach ($availableYears as $year): ?>
                                                            <option value="<?= $year ?>" <?= $year == $selectedYear ? 'selected' : '' ?>>
                                                                <?= $year ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Month</th>
                                                        <th>Year</th>
                                                        <th>Status</th>
                                                        <th>Locked By</th>
                                                        <th>Last Action</th>
                                                        <th>Action History</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($months as $month):
                                                        $lockRecord = array_filter($lockStatus, function ($item) use ($month, $selectedYear) {
                                                            return $item['month'] === $month && $item['year'] == $selectedYear;
                                                        });
                                                        $lockRecord = reset($lockRecord);
                                                        $isLocked = $lockRecord ? ($lockRecord['is_locked'] === 't') : true;
                                                        $isCurrentMonth = $month === date('F') && $selectedYear == date('Y');

                                                        if (!$lockRecord) {
                                                            $lockRecord = [
                                                                'locked_by_name' => 'System',
                                                                'locked_at' => '1970-01-01',
                                                                'last_action' => '1970-01-01',
                                                                'action_history' => 'Initialized as locked by system'
                                                            ];
                                                        }
                                                    ?>
                                                        <tr class="<?= $isCurrentMonth ? 'current-month' : '' ?>">
                                                            <td><?= $month ?></td>
                                                            <td><?= $selectedYear ?></td>
                                                            <td>
                                                                <i class="fas fa-<?= $isLocked ? 'lock' : 'lock-open' ?> fa-lg"></i>
                                                                <?= $isCurrentMonth ? '<span class="badge bg-info ms-2">Current</span>' : '' ?>
                                                            </td>
                                                            <td>
                                                                <?= htmlspecialchars($lockRecord['locked_by_name']) ?>
                                                            </td>
                                                            <td>
                                                                <?= $lockRecord['last_action'] != '1970-01-01' ? date('d-M-Y H:i', strtotime($lockRecord['last_action'])) : 'Never' ?>
                                                            </td>
                                                            <td class="action-history">
                                                                <?= nl2br(htmlspecialchars(trim($lockRecord['action_history']))) ?>
                                                            </td>
                                                            <td>
                                                                <form method="post" style="display:inline">
                                                                    <input type="hidden" name="month" value="<?= $month ?>">
                                                                    <input type="hidden" name="year" value="<?= $selectedYear ?>">
                                                                    <input type="hidden" name="action" value="<?= $isLocked ? 'unlock' : 'lock' ?>">
                                                                    <button type="submit" class="btn btn-sm btn-<?= $isLocked ? 'success' : 'danger' ?>">
                                                                        <?= $isLocked ? 'Unlock' : 'Lock' ?>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  
</body>

</html>