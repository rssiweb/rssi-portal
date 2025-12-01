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

if ($role !== 'Admin') {
    header("Location: index.php");
    exit;
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_fee_structure'])) {
        // Handle multiple fee submissions
        if (isset($_POST['multiple_fees'])) {
            $studentType = pg_escape_string($con, $_POST['student_type']);
            $effectiveFrom = $_POST['effective_from'];

            // Get the array of selected classes
            $selectedClasses = $_POST['class']; // This should be an array from your form

            foreach ($selectedClasses as $class) {
                $class = pg_escape_string($con, $class);

                foreach ($_POST['category_id'] as $index => $categoryId) {
                    if (!empty($categoryId) && isset($_POST['amount'][$index]) && !empty($_POST['amount'][$index])) {
                        $amount = $_POST['amount'][$index];
                        $categoryId = (int)$categoryId;

                        // End previous effective period for this class and category
                        $endPreviousQuery = "UPDATE fee_structure 
                                            SET effective_until = '$effectiveFrom'::date - INTERVAL '1 day'
                                            WHERE class = '$class' 
                                            AND student_type = '$studentType'
                                            AND category_id = $categoryId
                                            AND (effective_until IS NULL OR effective_until >= '$effectiveFrom')";
                        pg_query($con, $endPreviousQuery);

                        // Insert new fee structure for this class
                        $query = "INSERT INTO fee_structure 
                                 (class, student_type, category_id, amount, effective_from, created_at)
                                 VALUES ('$class', '$studentType', $categoryId, $amount, '$effectiveFrom', NOW())";
                        pg_query($con, $query);
                    }
                }
            }

            echo "<script>
                    alert('Fee structures added successfully for all selected classes!');
                    window.location.href = 'fee_structure_management.php';
                  </script>";
            exit;
        } else {
            // Original single fee submission
            $class = pg_escape_string($con, $_POST['class']);
            $studentType = pg_escape_string($con, $_POST['student_type']);
            $categoryId = $_POST['category_id'];
            $amount = $_POST['amount'];
            $effectiveFrom = $_POST['effective_from'];

            // End previous effective period
            $endPreviousQuery = "UPDATE fee_structure 
                                SET effective_until = '$effectiveFrom'::date - INTERVAL '1 day'
                                WHERE class = '$class' 
                                AND student_type = '$studentType'
                                AND category_id = $categoryId
                                AND effective_until IS NULL";
            pg_query($con, $endPreviousQuery);

            // Insert new fee structure
            $query = "INSERT INTO fee_structure 
                     (class, student_type, category_id, amount, effective_from)
                     VALUES ('$class', '$studentType', $categoryId, $amount, '$effectiveFrom')";
            pg_query($con, $query);

            echo "<script>
                    alert('Fee structure added successfully!');
                    window.location.href = 'fee_structure_management.php';
                  </script>";
            exit;
        }
    } elseif (isset($_POST['deactivate_fee'])) {
        $feeId = $_POST['fee_id'];
        $deactivateDate = $_POST['deactivate_date'];
        $feeType = $_POST['fee_type'] ?? 'standard'; // Default to standard if not specified

        // Validate date
        if (empty($deactivateDate)) {
            echo "<script>
                    alert('Please select a deactivation date');
                    window.location.href = 'fee_structure_management.php';
                  </script>";
            exit;
        }

        // Validate fee ID
        if (!is_numeric($feeId) || $feeId <= 0) {
            echo "<script>
                    alert('Invalid fee ID');
                    window.location.href = 'fee_structure_management.php';
                  </script>";
            exit;
        }

        // Determine which table to update based on fee type
        $tableName = ($feeType === 'student') ? 'student_specific_fees' : 'fee_structure';
        $idField = ($feeType === 'student') ? 'id' : 'id'; // Both tables use 'id' as primary key

        // Begin transaction
        pg_query($con, "BEGIN");

        try {
            // First verify the fee exists and is active
            $checkQuery = "SELECT 1 FROM $tableName 
                          WHERE $idField = $feeId 
                          AND (effective_until IS NULL OR effective_until >= '$deactivateDate')";
            $checkResult = pg_query($con, $checkQuery);

            if (pg_num_rows($checkResult) == 0) {
                throw new Exception("Fee not found or already inactive");
            }

            // Update effective_until date
            $updateQuery = "UPDATE $tableName 
                           SET effective_until = '$deactivateDate'
                           WHERE $idField = $feeId
                           AND (effective_until IS NULL OR effective_until > '$deactivateDate')";

            if (pg_query($con, $updateQuery)) {
                pg_query($con, "COMMIT");

                // Log the deactivation
                $logMessage = ($feeType === 'student')
                    ? "Student-specific fee ID $feeId deactivated effective $deactivateDate"
                    : "Standard fee ID $feeId deactivated effective $deactivateDate";

                // logActivity($con, $logMessage, $_SESSION['aid']);

                echo "<script>
                        alert('Fee deactivated successfully!');
                        window.location.href = 'fee_structure_management.php';
                      </script>";
            } else {
                throw new Exception(pg_last_error($con));
            }
        } catch (Exception $e) {
            pg_query($con, "ROLLBACK");
            echo "<script>
                    alert('Failed to deactivate fee: " . addslashes($e->getMessage()) . "');
                    window.location.href = 'fee_structure_management.php';
                  </script>";
        }
        exit;
    }
    // ADD BULK DEACTIVATION CODE RIGHT HERE - AFTER the individual deactivation block
    elseif (isset($_POST['bulk_deactivate_fees'])) {
        $feeIds = explode(',', $_POST['fee_ids']);
        $deactivateDate = $_POST['deactivate_date'];
        $feeType = $_POST['fee_type'] ?? 'standard';

        // Validate date
        if (empty($deactivateDate)) {
            echo "<script>
                    alert('Please select a deactivation date');
                    window.location.href = 'fee_structure_management.php';
                  </script>";
            exit;
        }

        // Validate fee IDs
        $validFeeIds = array_filter($feeIds, function ($id) {
            return is_numeric($id) && $id > 0;
        });

        if (empty($validFeeIds)) {
            echo "<script>
                    alert('Invalid fee IDs');
                    window.location.href = 'fee_structure_management.php';
                  </script>";
            exit;
        }

        // Determine which table to update based on fee type
        $tableName = ($feeType === 'student') ? 'student_specific_fees' : 'fee_structure';
        $idField = ($feeType === 'student') ? 'id' : 'id';

        // Begin transaction
        pg_query($con, "BEGIN");

        try {
            $successCount = 0;
            $errorIds = [];

            foreach ($validFeeIds as $feeId) {
                // First verify the fee exists and is active
                $checkQuery = "SELECT 1 FROM $tableName 
                              WHERE $idField = $feeId 
                              AND (effective_until IS NULL OR effective_until >= '$deactivateDate')";
                $checkResult = pg_query($con, $checkQuery);

                if (pg_num_rows($checkResult) == 0) {
                    $errorIds[] = $feeId;
                    continue;
                }

                // Update effective_until date
                $updateQuery = "UPDATE $tableName 
                               SET effective_until = '$deactivateDate'
                               WHERE $idField = $feeId
                               AND (effective_until IS NULL OR effective_until > '$deactivateDate')";

                if (pg_query($con, $updateQuery)) {
                    $successCount++;
                } else {
                    $errorIds[] = $feeId;
                }
            }

            if ($successCount > 0) {
                pg_query($con, "COMMIT");

                $message = "Successfully deactivated $successCount fee(s).";
                if (!empty($errorIds)) {
                    $message .= " Failed to deactivate " . count($errorIds) . " fee(s) (IDs: " . implode(', ', $errorIds) . ").";
                }

                echo "<script>
                        alert('$message');
                        window.location.href = 'fee_structure_management.php';
                      </script>";
            } else {
                throw new Exception("Failed to deactivate any fees. Please check if the fees are already inactive.");
            }
        } catch (Exception $e) {
            pg_query($con, "ROLLBACK");
            echo "<script>
                    alert('Failed to bulk deactivate fees: " . addslashes($e->getMessage()) . "');
                    window.location.href = 'fee_structure_management.php';
                  </script>";
        }
        exit;
    }
}

// Get all categories
$categories = pg_fetch_all(pg_query($con, "SELECT * FROM fee_categories WHERE is_active = TRUE AND category_type='structured' ORDER BY id")) ?? [];

// Fetch active plans from database
$plansQuery = "SELECT name, division FROM plans WHERE is_active = true ORDER BY name";
$plansResult = pg_query($con, $plansQuery);

// Initialize arrays
$names = [];
$divisions = [];

if ($plansResult) {
    while ($row = pg_fetch_assoc($plansResult)) {
        // Add to names array if not already present
        if (!in_array($row['name'], $names)) {
            $names[] = $row['name'];
        }

        // Add to divisions array if not already present
        if (!in_array($row['division'], $divisions)) {
            $divisions[] = $row['division'];
        }
    }
}
// Get all classes
$classes = pg_fetch_all(pg_query($con, "SELECT DISTINCT class FROM rssimyprofile_student ORDER BY class")) ?? [];

// Get all student types
$studentTypes = pg_fetch_all(pg_query($con, "SELECT DISTINCT student_type FROM fee_structure ORDER BY student_type")) ?? [];

// Get filter parameters as arrays, trimmed of spaces
$filterClass = isset($_GET['filter_class']) && is_array($_GET['filter_class'])
    ? array_map('trim', $_GET['filter_class'])
    : [];

$filterStudentType = isset($_GET['filter_student_type']) && is_array($_GET['filter_student_type'])
    ? array_map('trim', $_GET['filter_student_type'])
    : [];

$filterCategory = isset($_GET['filter_category']) && is_array($_GET['filter_category'])
    ? array_map('intval', $_GET['filter_category'])
    : [];

$filterDivision = isset($_GET['filter_division']) && is_array($_GET['filter_division'])
    ? array_map('trim', $_GET['filter_division'])
    : [];

$filterStatus = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';

// Build WHERE clause
$whereClause = "WHERE 1=1";

// Filter by class
if (!empty($filterClass)) {
    $escaped = array_map(function ($val) use ($con) {
        return "'" . pg_escape_string($con, $val) . "'";
    }, $filterClass);
    $whereClause .= " AND fs.class IN (" . implode(",", $escaped) . ")";
}

// Filter by student type
if (!empty($filterStudentType)) {
    $escaped = array_map(function ($val) use ($con) {
        return "'" . pg_escape_string($con, $val) . "'";
    }, $filterStudentType);
    $whereClause .= " AND fs.student_type IN (" . implode(",", $escaped) . ")";
}

// Filter by category ID
if (!empty($filterCategory)) {
    $escaped = array_map('intval', $filterCategory);
    $whereClause .= " AND fs.category_id IN (" . implode(",", $escaped) . ")";
}

// Filter by division (through the plans table)
if (!empty($filterDivision)) {
    $escaped = array_map(function ($val) use ($con) {
        return "'" . pg_escape_string($con, $val) . "'";
    }, $filterDivision);
    $whereClause .= " AND fs.student_type IN (
        SELECT name FROM plans WHERE division IN (" . implode(",", $escaped) . ")
    )";
}

// Filter by status
if ($filterStatus === 'active') {
    $whereClause .= " AND (fs.effective_until IS NULL OR fs.effective_until >= CURRENT_DATE)";
} elseif ($filterStatus === 'inactive') {
    $whereClause .= " AND fs.effective_until < CURRENT_DATE";
}

if (empty($filterClass) && empty($filterStudentType) && empty($filterCategory) && empty($filterDivision) && empty($filterStatus)) {
    // Default to show no fees if no filters are applied
    $whereClause = "WHERE false"; // Ensures the query returns no rows
}

$feeStructureQuery = "SELECT fs.*, fc.category_name, fc.fee_type
    FROM fee_structure fs
    JOIN fee_categories fc ON fs.category_id = fc.id
    JOIN plans p ON fs.student_type = p.name
    $whereClause
    ORDER BY fs.class, fs.student_type, fc.category_name, fs.effective_from DESC";

// Execute the query
$result = pg_query($con, $feeStructureQuery);
$feeStructure = $result ? pg_fetch_all($result) : [];

?>
<?php
// Get the current filter status from GET parameters
$filterStatus_sp = isset($_GET['filter_status_sp']) ? $_GET['filter_status_sp'] : '';

// Prepare the status condition only if a filter is selected
$statusCondition = '';
if (!empty($filterStatus_sp)) {
    if ($filterStatus_sp === 'active') {
        $statusCondition = " (ssf.effective_until IS NULL OR ssf.effective_until >= CURRENT_DATE) ";
    } elseif ($filterStatus_sp === 'inactive') {
        $statusCondition = " (ssf.effective_until IS NOT NULL AND ssf.effective_until < CURRENT_DATE) ";
    }
}

// Query data only if a valid filter is selected
$studentFees = [];
if (!empty($statusCondition)) {
    $studentFeesQuery = "SELECT ssf.*, 
        s.studentname, s.class,
        fc.category_name, fc.fee_type
        FROM student_specific_fees ssf
        JOIN rssimyprofile_student s ON ssf.student_id = s.student_id
        JOIN fee_categories fc ON ssf.category_id = fc.id
        WHERE $statusCondition
        ORDER BY s.class, s.studentname, fc.category_name, ssf.effective_from DESC";

    $result = pg_query($con, $studentFeesQuery);
    $studentFees = $result ? pg_fetch_all($result) : [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Fee Structure Management</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- In your head section -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        .fee-type-badge {
            font-size: 0.75rem;
        }

        .one-time {
            background-color: #6f42c1;
        }

        .recurring {
            background-color: #20c997;
        }

        .on-demand {
            background-color: #fd7e14;
        }

        .btn-deactivate {
            min-width: 100px;
        }

        .deactivated {
            color: #dc3545;
            font-weight: bold;
        }

        .active-fee {
            background-color: #f8f9fa;
        }

        .inactive-fee {
            background-color: #fff;
            opacity: 0.8;
        }

        .card-header {
            font-weight: 600;
        }

        .form-section {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }

        .form-section h5 {
            margin-bottom: 15px;
            color: #495057;
        }

        .fee-row {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
            background-color: #fff;
            border: 1px solid #dee2e6;
        }

        .nav-tabs .nav-link.active {
            font-weight: 600;
            border-bottom: 2px solid #0d6efd;
        }

        .tab-content {
            padding: 20px 0;
        }

        .table th {
            font-weight: 600;
            color: #495057;
        }

        .table td {
            vertical-align: middle;
        }

        .badge {
            font-weight: 500;
        }

        .bootstrap-select .dropdown-menu {
            max-height: 300px;
            overflow-y: auto;
        }

        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .filter-btn {
            margin-top: 32px;
        }

        .reset-btn {
            margin-top: 32px;
        }

        /* Add row selection styles */
        .table tbody tr {
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa !important;
        }

        .table tbody tr.selected {
            background-color: #e3f2fd !important;
            border-left: 3px solid #0d6efd;
        }

        /* Ensure checkboxes are properly aligned */
        .table .form-check-input {
            cursor: pointer;
            margin: 0;
        }

        /* Disable row selection for inactive fees */
        .table tbody tr.inactive-fee {
            cursor: default;
        }

        /* CSV Import Styles */
        .csv-template-info {
            background-color: #e7f3ff;
            border-left: 4px solid #0d6efd;
        }

        .csv-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 5px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .csv-upload-area:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }

        .csv-upload-area.dragover {
            border-color: #198754;
            background-color: #e8f5e9;
        }

        .csv-preview-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            white-space: nowrap;
        }

        .import-summary {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .import-success {
            background-color: #d1e7dd;
            border-left: 4px solid #198754;
        }

        .import-error {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .import-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }

        /* Template download button */
        .btn-template {
            background-color: #20c997;
            border-color: #20c997;
            color: white;
        }

        .btn-template:hover {
            background-color: #199d76;
            border-color: #199d76;
            color: white;
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Fee Structure Management</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Fee Portal</a></li>
                    <li class="breadcrumb-item active">Fee Structure Management</li>
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
                            <div class="container mt-4">
                                <!-- Add Fee Structure Form -->
                                <div class="card mb-4 shadow-sm">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-plus-circle me-2"></i>Configure Fee Structure
                                    </div>
                                    <div class="card-body">
                                        <ul class="nav nav-tabs mb-4" id="feeTab" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="multiple-tab" data-bs-toggle="tab" data-bs-target="#multiple" type="button" role="tab">Multiple Fees</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="student-specific-tab" data-bs-toggle="tab" data-bs-target="#student-specific" type="button" role="tab">Student Specific</button>
                                            </li>
                                        </ul>

                                        <div class="tab-content" id="feeTabContent">

                                            <!-- Multiple Fees Tab -->
                                            <div class="tab-pane fade show active" id="multiple" role="tabpanel">
                                                <form method="post">
                                                    <input type="hidden" name="multiple_fees" value="1">
                                                    <div class="row mb-3">
                                                        <div class="col-md-3">
                                                            <label for="class" class="form-label">Class (Select multiple if needed)</label>
                                                            <select name="class[]" id="class" class="form-select select2" multiple required>
                                                                <?php foreach ($classes as $class): ?>
                                                                    <option value="<?= htmlspecialchars($class['class']) ?>">
                                                                        <?= htmlspecialchars($class['class']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="student_type" class="form-label">Plan</label>
                                                            <select name="student_type" id="student_type" class="form-select" required>
                                                                <option value="">--Select Plan--</option>
                                                                <?php
                                                                // Reset the result pointer and loop again
                                                                pg_result_seek($plansResult, 0);

                                                                while ($plan = pg_fetch_assoc($plansResult)) {
                                                                    $selected = (isset($array['student_type']) && $array['student_type'] == $plan['name']) ? 'selected' : '';
                                                                    echo '<option value="' . htmlspecialchars($plan['name']) . '" ' . $selected . '>' . htmlspecialchars($plan['name'] . '-' . $plan['division']) . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <!-- First Effective From (change type to month) -->
                                                        <div class="col-md-3">
                                                            <label for="effective_from_month1" class="form-label">Effective From</label>
                                                            <input type="month" id="effective_from_month1" class="form-control month-first-day" required>
                                                            <input type="hidden" name="effective_from" id="effective_from1" value="<?= date('Y-m-01') ?>">
                                                        </div>
                                                    </div>

                                                    <div class="form-section">
                                                        <h5><i class="fas fa-list me-2"></i>Fee Categories</h5>

                                                        <?php foreach (array_chunk($categories, 3) as $categoryGroup): ?>
                                                            <div class="row">
                                                                <?php foreach ($categoryGroup as $category): ?>
                                                                    <div class="col-md-4 mb-3">
                                                                        <div class="fee-row">
                                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                <span class="fw-medium"><?= $category['category_name'] ?></span>
                                                                                <span class="badge fee-type-badge <?= strtolower(str_replace(' ', '-', $category['fee_type'])) ?>">
                                                                                    <?= $category['fee_type'] ?>
                                                                                </span>
                                                                            </div>
                                                                            <div class="input-group">
                                                                                <span class="input-group-text">₹</span>
                                                                                <input type="hidden" name="category_id[]" value="<?= $category['id'] ?>">
                                                                                <input type="number" name="amount[]" class="form-control" placeholder="0.00" step="0.01" min="0">
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>

                                                    <div class="gap-2">
                                                        <button type="submit" name="add_fee_structure" class="btn btn-primary">
                                                            <i class="fas fa-save me-1"></i> Save All Fees
                                                        </button>
                                                    </div>
                                                </form>
                                                <!-- Filter Section -->
                                                <div class="filter-section mb-4">
                                                    <h5><i class="fas fa-filter me-2"></i>Filter Standard Fee Structure</h5>
                                                    <form method="get" action="">
                                                        <div class="row">
                                                            <div class="col-md-3">
                                                                <label for="filter_class" class="form-label">Class</label>
                                                                <select name="filter_class[]" id="filter_class" class="form-select select2" multiple="multiple">
                                                                    <?php foreach ($classes as $class): ?>
                                                                        <option value="<?= htmlspecialchars($class['class']) ?>"
                                                                            <?= in_array((string)$class['class'], array_map('strval', $filterClass), true) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($class['class']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="col-md-3">
                                                                <label for="filter_student_type" class="form-label">Plans</label>
                                                                <select name="filter_student_type[]" id="filter_student_type" class="form-select select2" multiple="multiple">
                                                                    <?php foreach ($names as $name): ?>
                                                                        <option value="<?= htmlspecialchars($name) ?>"
                                                                            <?= in_array((string)$name, array_map('strval', $filterStudentType), true) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($name) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="col-md-3">
                                                                <label for="filter_division" class="form-label">Division</label>
                                                                <select name="filter_division[]" id="filter_division" class="form-select select2" multiple="multiple">
                                                                    <?php foreach ($divisions as $division): ?>
                                                                        <option value="<?= htmlspecialchars($division) ?>"
                                                                            <?= in_array((string)$division, array_map('strval', $filterDivision), true) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($division) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="col-md-3">
                                                                <label for="filter_category" class="form-label">Fee Type</label>
                                                                <select name="filter_category[]" id="filter_category" class="form-select select2" multiple="multiple">
                                                                    <?php foreach ($categories as $category): ?>
                                                                        <option value="<?= $category['id'] ?>"
                                                                            <?= in_array((int)$category['id'], array_map('intval', $filterCategory), true) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($category['category_name']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>

                                                            <div class="col-md-3">
                                                                <label for="filter_status" class="form-label">Status</label>
                                                                <select name="filter_status" id="filter_status" class="form-select select2">
                                                                    <option value="">-- Select Status --</option>
                                                                    <option value="active" <?= (isset($filterStatus) && $filterStatus === 'active') ? 'selected' : '' ?>>Active</option>
                                                                    <option value="inactive" <?= (isset($filterStatus) && $filterStatus === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                                                </select>
                                                            </div>
                                                            <!-- Add this to both filter forms (standard and student-specific) -->
                                                            <input type="hidden" name="tab" value="<?= isset($_GET['tab']) ? htmlspecialchars($_GET['tab']) : 'multiple' ?>">

                                                            <div class="col-md-3 align-self-end">
                                                                <button type="submit" class="btn btn-primary filter-btn">
                                                                    <i class="fas fa-filter me-1"></i> Apply Filters
                                                                </button>
                                                                <a href="fee_structure_management.php" class="btn btn-secondary reset-btn">
                                                                    <i class="fas fa-times me-1"></i> Reset
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </form>

                                                </div>

                                                <!-- Standard Fee Structure Table -->
                                                <div class="mb-4">
                                                    <h5 class="mb-3"><i class="fas fa-list-alt me-2"></i>Standard Fee Structure</h5>
                                                    <!-- Bulk Actions for Standard Fees -->
                                                    <div class="bulk-actions mb-3" id="standardBulkActions" style="display: none;">
                                                        <div class="d-flex align-items-center gap-2 p-3 bg-light rounded">
                                                            <span class="fw-medium" id="selectedStandardCount">0 fees selected</span>
                                                            <select class="form-select form-select-sm" style="width: auto;" id="standardBulkAction">
                                                                <option value="">Choose action...</option>
                                                                <option value="deactivate">Deactivate Selected</option>
                                                            </select>
                                                            <button class="btn btn-sm btn-danger" id="applyStandardBulkAction">
                                                                <i class="fas fa-play me-1"></i> Apply
                                                            </button>
                                                            <button class="btn btn-sm btn-secondary" id="cancelStandardSelection">
                                                                <i class="fas fa-times me-1"></i> Cancel
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-hover" id="feeTable">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>
                                                                        <input type="checkbox" id="selectAllStandard" class="form-check-input">
                                                                    </th>
                                                                    <th>Class</th>
                                                                    <th>Plan</th>
                                                                    <th>Fee Type</th>
                                                                    <th>Amount</th>
                                                                    <th>Effective From</th>
                                                                    <th>Effective Until</th>
                                                                    <th>Status</th>
                                                                    <th>Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if (empty($feeStructure)): ?>
                                                                    <tr>
                                                                        <td colspan="9" class="text-center">
                                                                            <?php if (empty($filterClass) && empty($filterStudentType) && empty($filterCategory) && empty($filterDivision) && empty($filterStatus)): ?>
                                                                                Please select at least one filter to view the fee structure.
                                                                            <?php else: ?>
                                                                                No fee structure found matching your criteria.
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php else: ?>
                                                                    <?php foreach ($feeStructure as $fee):
                                                                        $isActive = empty($fee['effective_until']) || $fee['effective_until'] >= date('Y-m-d');
                                                                        $rowClass = $isActive ? 'active-fee' : 'inactive-fee';
                                                                    ?>
                                                                        <tr>
                                                                            <td>
                                                                                <?php if ($isActive): ?>
                                                                                    <input type="checkbox" class="form-check-input fee-checkbox" data-fee-id="<?= $fee['id'] ?>" data-fee-type="standard">
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td><?= $fee['class'] ?></td>
                                                                            <td><?= $fee['student_type'] ?></td>
                                                                            <td><?= $fee['category_name'] ?></td>
                                                                            <td>₹<?= number_format($fee['amount'], 2) ?></td>
                                                                            <td><?= date('d-M-Y', strtotime($fee['effective_from'])) ?></td>
                                                                            <td>
                                                                                <?= !empty($fee['effective_until']) ? date('d-M-Y', strtotime($fee['effective_until'])) : 'N/A' ?>
                                                                            </td>
                                                                            <td>
                                                                                <?php if ($isActive): ?>
                                                                                    <span class="badge bg-success">Active</span>
                                                                                <?php else: ?>
                                                                                    <span class="badge bg-secondary">Inactive</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td>
                                                                                <div class="dropdown">
                                                                                    <button class="btn btn-sm btn-link text-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0.15rem 0.5rem;" id="dropdownMenuButton<?= $fee['id'] ?>">
                                                                                        <i class="fas fa-ellipsis-v"></i>
                                                                                    </button>
                                                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?= $fee['id'] ?>">
                                                                                        <?php if ($isActive): ?>
                                                                                            <li>
                                                                                                <button class="dropdown-item btn-deactivate" data-fee-id="<?= $fee['id'] ?>">
                                                                                                    <i class="fas fa-ban me-1"></i> Deactivate
                                                                                                </button>
                                                                                            </li>
                                                                                        <?php else: ?>
                                                                                            <li>
                                                                                                <span class="dropdown-item text-muted">No actions available</span>
                                                                                            </li>
                                                                                        <?php endif; ?>
                                                                                    </ul>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Student Specific Fees Tab -->
                                            <div class="tab-pane fade" id="student-specific" role="tabpanel">
                                                <form method="post" action="assign_student_fees.php">
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Students (select multiple)</label>
                                                            <select name="student_ids[]" id="spf_id" class="form-select" multiple="multiple" required>
                                                                <!-- Options will be loaded via AJAX -->
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">Fee Type</label>
                                                            <select name="category_id" class="form-select" required>
                                                                <option value="">Select Type</option>
                                                                <?php foreach ($categories as $cat): ?>
                                                                    <option value="<?= $cat['id'] ?>"><?= $cat['category_name'] ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">Amount (₹)</label>
                                                            <input type="number" name="amount" class="form-control"
                                                                step="0.01" min="0" required>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <!-- Second Effective From (change type to month) -->
                                                        <div class="col-md-4">
                                                            <label for="effective_from_month2" class="form-label">Effective From</label>
                                                            <input type="month" id="effective_from_month2" class="form-control month-first-day" required>
                                                            <input type="hidden" name="effective_from" id="effective_from2" value="<?= date('Y-m-01') ?>">
                                                        </div>

                                                        <!-- Effective Until (change type to month) -->
                                                        <div class="col-md-4">
                                                            <label for="effective_until_month" class="form-label">Effective Until (optional)</label>
                                                            <input type="month" id="effective_until_month" class="form-control month-last-day">
                                                            <input type="hidden" name="effective_until" id="effective_until">
                                                        </div>
                                                        <div class="col-md-4 d-flex align-items-end">
                                                            <button type="submit" name="assign_fee" class="btn btn-primary">
                                                                <i class="fas fa-save me-1"></i> Assign Fee
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                                <!-- Add this after the Assign Fee form in the Student Specific tab -->
                                                <div class="card mb-4 shadow-sm">
                                                    <div class="card-header bg-info text-white">
                                                        <i class="fas fa-file-import me-2"></i> Bulk Import via CSV
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="alert alert-info">
                                                            <!-- <i class="fas fa-info-circle me-2"></i> -->
                                                            <strong>Instructions:</strong>
                                                            1. Download the template CSV file.<br>
                                                            2. Fill in the data (remove sample rows).<br>
                                                            3. Upload the CSV file here.
                                                            <a href="download_csv_template.php" class="btn btn-outline-info btn-sm ms-2">
                                                                <i class="fas fa-download me-1"></i> Download Template
                                                            </a>
                                                        </div>

                                                        <form method="post" action="process_csv_import.php" enctype="multipart/form-data" id="csvImportForm">
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <label for="csv_file" class="form-label">Select CSV File</label>
                                                                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                                                    <div class="form-text">Only CSV files are allowed. Max size: 10MB</div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label">Action</label>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="import_action" id="previewOnly" value="preview" checked>
                                                                        <label class="form-check-label" for="previewOnly">
                                                                            Preview Only (Don't Import)
                                                                        </label>
                                                                    </div>
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="radio" name="import_action" id="importData" value="import">
                                                                        <label class="form-check-label" for="importData">
                                                                            Import Data
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <label for="effective_from_csv" class="form-label">Default Effective From (if not specified in CSV)</label>
                                                                    <input type="date" class="form-control" id="effective_from_csv" name="effective_from_csv"
                                                                        value="<?= date('Y-m-01') ?>" required>
                                                                </div>
                                                                <div class="col-md-6 d-flex align-items-end">
                                                                    <button type="submit" class="btn btn-info" name="import_csv">
                                                                        <i class="fas fa-upload me-1"></i> Upload & Process CSV
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </form>

                                                        <!-- Preview Table (will be populated via AJAX) -->
                                                        <div id="csvPreview" class="mt-4" style="display: none;">
                                                            <h5><i class="fas fa-eye me-2"></i>CSV Preview</h5>
                                                            <div class="table-responsive">
                                                                <table class="table table-sm table-bordered" id="previewTable">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th>Student ID</th>
                                                                            <th>Student Name</th>
                                                                            <th>Class</th>
                                                                            <th>Category</th>
                                                                            <th>Amount</th>
                                                                            <th>Effective From</th>
                                                                            <th>Effective Until</th>
                                                                            <th>Status</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody id="previewBody">
                                                                        <!-- Preview rows will be inserted here -->
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                                <div id="previewSummary"></div>
                                                                <button type="button" class="btn btn-success" id="confirmImport" style="display: none;">
                                                                    <i class="fas fa-check me-1"></i> Confirm Import
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Student Specific Fees Table -->
                                                <div>
                                                    <h5 class="mb-3"><i class="fas fa-user-graduate me-2"></i>Student Specific Fees</h5>

                                                    <!-- Bulk Actions for Student Fees -->
                                                    <div class="bulk-actions mb-3" id="studentBulkActions" style="display: none;">
                                                        <div class="d-flex align-items-center gap-2 p-3 bg-light rounded">
                                                            <span class="fw-medium" id="selectedStudentCount">0 fees selected</span>
                                                            <select class="form-select form-select-sm" style="width: auto;" id="studentBulkAction">
                                                                <option value="">Choose action...</option>
                                                                <option value="deactivate">Deactivate Selected</option>
                                                            </select>
                                                            <button class="btn btn-sm btn-danger" id="applyStudentBulkAction">
                                                                <i class="fas fa-play me-1"></i> Apply
                                                            </button>
                                                            <button class="btn btn-sm btn-secondary" id="cancelStudentSelection">
                                                                <i class="fas fa-times me-1"></i> Cancel
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Filter Form -->
                                                    <form method="get" class="mb-3">
                                                        <!-- Add this to both filter forms (standard and student-specific) -->
                                                        <input type="hidden" name="tab" value="<?= isset($_GET['tab']) ? htmlspecialchars($_GET['tab']) : 'multiple' ?>">
                                                        <div class="row g-2 align-items-center">
                                                            <div class="col-auto">
                                                                <label for="filter_status_sp" class="form-label">Status</label>
                                                                <select name="filter_status_sp" id="filter_status_sp" class="form-select">
                                                                    <option value="">-- Select Status --</option>
                                                                    <option value="active" <?= ($filterStatus_sp === 'active') ? 'selected' : '' ?>>Active</option>
                                                                    <option value="inactive" <?= ($filterStatus_sp === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-3 align-self-end">
                                                                <button type="submit" class="btn btn-primary">Filter</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                    <div class="table-responsive">
                                                        <table class="table table-hover">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>
                                                                        <input type="checkbox" id="selectAllStudent" class="form-check-input">
                                                                    </th>
                                                                    <th>Student</th>
                                                                    <th>Class</th>
                                                                    <th>Category</th>
                                                                    <th>Type</th>
                                                                    <th>Amount</th>
                                                                    <th>Effective From</th>
                                                                    <th>Effective Until</th>
                                                                    <th>Status</th>
                                                                    <th>Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php if (empty($filterStatus_sp)): ?>
                                                                    <tr>
                                                                        <td colspan="9" class="text-center text-muted">
                                                                            Please select a status filter to view the student specific fees.
                                                                        </td>
                                                                    </tr>
                                                                <?php else: ?>
                                                                    <?php if (count($studentFees) > 0): ?>
                                                                        <?php foreach ($studentFees as $fee):
                                                                            $isActive = empty($fee['effective_until']) || $fee['effective_until'] >= date('Y-m-d');
                                                                        ?>
                                                                            <tr>
                                                                                <td>
                                                                                    <?php if ($isActive): ?>
                                                                                        <input type="checkbox" class="form-check-input fee-checkbox" data-fee-id="<?= $fee['id'] ?>" data-fee-type="student">
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                                <td><?= htmlspecialchars($fee['studentname']) ?></td>
                                                                                <td><?= htmlspecialchars($fee['class']) ?></td>
                                                                                <td><?= htmlspecialchars($fee['category_name']) ?></td>
                                                                                <td>
                                                                                    <span class="badge fee-type-badge <?= strtolower(str_replace(' ', '-', $fee['fee_type'])) ?>">
                                                                                        <?= htmlspecialchars($fee['fee_type']) ?>
                                                                                    </span>
                                                                                </td>
                                                                                <td>₹<?= number_format($fee['amount'], 2) ?></td>
                                                                                <td><?= date('d-M-Y', strtotime($fee['effective_from'])) ?></td>
                                                                                <td>
                                                                                    <?= !empty($fee['effective_until']) ? date('d-M-Y', strtotime($fee['effective_until'])) : 'N/A' ?>
                                                                                </td>
                                                                                <td>
                                                                                    <?php if ($isActive): ?>
                                                                                        <span class="badge bg-success">Active</span>
                                                                                    <?php else: ?>
                                                                                        <span class="badge bg-secondary">Inactive</span>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                                <td>
                                                                                    <?php if ($isActive): ?>
                                                                                        <button class="btn btn-sm btn-outline-danger btn-deactivate-student"
                                                                                            data-fee-id="<?= $fee['id'] ?>"
                                                                                            data-fee-type="student">
                                                                                            <i class="fas fa-ban me-1"></i> Deactivate
                                                                                        </button>
                                                                                    <?php else: ?>
                                                                                        <span class="text-muted">No actions</span>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    <?php else: ?>
                                                                        <tr>
                                                                            <td colspan="9" class="text-center text-muted">No student specific fees found for the selected filter.</td>
                                                                        </tr>
                                                                    <?php endif; ?>
                                                                <?php endif; ?> <!-- THIS WAS MISSING -->
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
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- Deactivation Modal -->
    <div class="modal fade" id="deactivateModal" tabindex="-1" aria-labelledby="deactivateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deactivateModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Deactivate Fee Structure
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <input type="hidden" name="deactivate_fee" value="1">
                    <input type="hidden" id="deactivateFeeId" name="fee_id">
                    <input type="hidden" id="deactivateFeeType" name="fee_type" value="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="deactivate_date" class="form-label">Deactivation Date:</label>
                            <input type="date" class="form-control" id="deactivate_date" name="deactivate_date"
                                min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="alert alert-warning mb-0">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle fs-4 me-3"></i>
                                <div>
                                    <h6 class="alert-heading mb-1">Warning</h6>
                                    <p class="mb-0">This will prevent this fee from being automatically applied to students after the selected date.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-check me-1"></i> Confirm Deactivation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Bulk Deactivation Modal -->
    <div class="modal fade" id="bulkDeactivateModal" tabindex="-1" aria-labelledby="bulkDeactivateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="bulkDeactivateModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Bulk Deactivate Fees
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="" id="bulkDeactivateForm">
                    <input type="hidden" name="bulk_deactivate_fees" value="1">
                    <input type="hidden" id="bulkFeeIds" name="fee_ids">
                    <input type="hidden" id="bulkFeeType" name="fee_type" value="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="bulk_deactivate_date" class="form-label">Deactivation Date:</label>
                            <input type="date" class="form-control" id="bulk_deactivate_date" name="deactivate_date"
                                min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="alert alert-warning mb-0">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle fs-4 me-3"></i>
                                <div>
                                    <h6 class="alert-heading mb-1">Warning</h6>
                                    <p class="mb-0" id="bulkDeactivateMessage">This will deactivate the selected fees.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-check me-1"></i> Confirm Bulk Deactivation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Required JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            function initializeSelect2() {
                $('#spf_id').select2({
                    ajax: {
                        url: 'fetch_students.php?isActive=true',
                        dataType: 'json',
                        delay: 250,
                        data: params => ({
                            q: params.term
                        }),
                        processResults: data => ({
                            results: data.results
                        }),
                        cache: true
                    },
                    placeholder: 'Search by name or ID',
                    width: '100%',
                    minimumInputLength: 2,
                    multiple: true,
                    theme: 'bootstrap-5'
                });
            }

            // On tab show
            $('#student-specific-tab').on('shown.bs.tab', function() {
                initializeSelect2();
            });

            // If tab is already active on page load
            if ($('#student-specific-tab').hasClass('active')) {
                initializeSelect2();
            }

            // Handle individual deactivation
            $(document).on('click', '.btn-deactivate, .btn-deactivate-student', function() {
                const feeId = $(this).data('fee-id');
                const isStudentFee = $(this).data('fee-type') === 'student';

                $('#deactivateFeeId').val(feeId);
                $('#deactivateFeeType').val(isStudentFee ? 'student' : 'standard');
                $('#deactivate_date').val(new Date().toISOString().split('T')[0]);

                const deactivateModal = new bootstrap.Modal(document.getElementById('deactivateModal'));
                deactivateModal.show();
            });

            // Row selection functionality
            function setupRowSelection(tableType) {
                const tableContainer = $(`#${tableType.toLowerCase()}BulkActions`).closest('.mb-4').find('.table-responsive');
                const table = tableContainer.find('table');
                const checkboxes = $(`.fee-checkbox[data-fee-type="${tableType.toLowerCase()}"]`);

                console.log(`Setting up row selection for ${tableType}, found ${checkboxes.length} checkboxes`);

                // Remove any existing click handlers to prevent duplicates
                table.find('tbody tr').off('click.rowSelection');

                // Handle row click
                table.find('tbody tr').on('click.rowSelection', function(e) {
                    // Don't trigger if clicking on the checkbox itself or actions dropdown
                    if ($(e.target).is('input[type="checkbox"]') ||
                        $(e.target).closest('.dropdown, .btn-deactivate, .btn-deactivate-student, .dropdown-toggle, .dropdown-menu').length) {
                        return;
                    }

                    const checkbox = $(this).find('.fee-checkbox');
                    const isActive = !$(this).hasClass('inactive-fee');

                    console.log('Row clicked, checkbox found:', checkbox.length, 'isActive:', isActive);

                    // Only allow selection for active fees
                    if (checkbox.length > 0 && isActive) {
                        const isChecked = checkbox.prop('checked');
                        checkbox.prop('checked', !isChecked).trigger('change');
                        console.log('Checkbox toggled to:', !isChecked);
                    }
                });

                // Remove any existing change handlers to prevent duplicates
                checkboxes.off('change.rowSelection');

                // Handle checkbox change to update row styling
                checkboxes.on('change.rowSelection', function() {
                    const row = $(this).closest('tr');
                    if ($(this).is(':checked')) {
                        row.addClass('selected');
                    } else {
                        row.removeClass('selected');
                    }

                    console.log('Checkbox changed, row selected:', $(this).is(':checked'));

                    // Update bulk actions
                    updateBulkActions(tableType);
                });

                // Initialize selected state for existing checkboxes
                checkboxes.each(function() {
                    const row = $(this).closest('tr');
                    if ($(this).is(':checked')) {
                        row.addClass('selected');
                    } else {
                        row.removeClass('selected');
                    }
                });
            }

            // Bulk Selection Functionality
            function setupBulkActions(tableType) {
                const selectAll = $(`#selectAll${tableType}`);
                const checkboxes = $(`.fee-checkbox[data-fee-type="${tableType.toLowerCase()}"]`);
                const bulkActions = $(`#${tableType.toLowerCase()}BulkActions`);
                const selectedCount = $(`#selected${tableType}Count`);
                const applyBtn = $(`#apply${tableType}BulkAction`);
                const cancelBtn = $(`#cancel${tableType}Selection`);

                console.log(`Setting up bulk actions for ${tableType}, found ${checkboxes.length} checkboxes`);

                // Select All functionality
                selectAll.off('change.bulkActions').on('change.bulkActions', function() {
                    const isChecked = $(this).is(':checked');
                    checkboxes.prop('checked', isChecked).trigger('change');
                    updateBulkActions(tableType);
                });

                // Individual checkbox change
                checkboxes.off('change.bulkActions').on('change.bulkActions', function() {
                    updateBulkActions(tableType);
                });

                // Cancel selection
                cancelBtn.off('click').on('click', function() {
                    checkboxes.prop('checked', false).trigger('change');
                    selectAll.prop('checked', false);
                    bulkActions.hide();
                });

                // Apply bulk action
                applyBtn.off('click').on('click', function() {
                    const selectedIds = checkboxes.filter(':checked').map(function() {
                        return $(this).data('fee-id');
                    }).get();

                    if (selectedIds.length === 0) {
                        alert('Please select at least one fee to deactivate.');
                        return;
                    }

                    const bulkAction = $(`#${tableType.toLowerCase()}BulkAction`).val();
                    if (bulkAction === 'deactivate') {
                        $('#bulkFeeIds').val(selectedIds.join(','));
                        $('#bulkFeeType').val(tableType.toLowerCase());
                        $('#bulk_deactivate_date').val(new Date().toISOString().split('T')[0]);
                        $('#bulkDeactivateMessage').text(`This will deactivate ${selectedIds.length} selected ${tableType.toLowerCase()} fees.`);

                        const bulkModal = new bootstrap.Modal(document.getElementById('bulkDeactivateModal'));
                        bulkModal.show();
                    }
                });

                // Initialize row selection for this table type
                setupRowSelection(tableType);

                // Initial update
                updateBulkActions(tableType);
            }

            function updateBulkActions(tableType) {
                const checkboxes = $(`.fee-checkbox[data-fee-type="${tableType.toLowerCase()}"]`);
                const selectedCount = checkboxes.filter(':checked').length;
                const bulkActions = $(`#${tableType.toLowerCase()}BulkActions`);
                const countElement = $(`#selected${tableType}Count`);

                if (selectedCount > 0) {
                    countElement.text(`${selectedCount} ${tableType.toLowerCase()} fee(s) selected`);
                    bulkActions.show();
                } else {
                    bulkActions.hide();
                }

                // Update select all checkbox
                const totalActive = checkboxes.length;
                $(`#selectAll${tableType}`).prop('checked', selectedCount === totalActive && totalActive > 0);

                console.log(`Updated bulk actions for ${tableType}: ${selectedCount} selected out of ${totalActive}`);
            }

            // Initialize bulk actions and row selection for both tables with delay to ensure DOM is ready
            setTimeout(() => {
                setupBulkActions('Standard');
                setupBulkActions('Student');
            }, 100);

            // Month input handling
            const currentMonth = new Date().toISOString().slice(0, 7);
            $('.month-first-day').each(function() {
                $(this).val(currentMonth);
                updateDateFromMonthInput(this, true);
            }).on('change', function() {
                updateDateFromMonthInput(this, true);
            });

            $('.month-last-day').each(function() {
                updateDateFromMonthInput(this, false);
            }).on('change', function() {
                updateDateFromMonthInput(this, false);
            });

            function updateDateFromMonthInput(monthInput, isFirstDay) {
                if (!monthInput.value) return;

                const [year, month] = monthInput.value.split('-');
                const date = isFirstDay ?
                    new Date(year, month - 1, 1) : // First day of month
                    new Date(year, month, 0); // Last day of month

                const hiddenInput = monthInput.nextElementSibling;
                if (hiddenInput && hiddenInput.tagName === 'INPUT' && hiddenInput.type === 'hidden') {
                    hiddenInput.value = date.toISOString().split('T')[0];
                }
            }

            // Tab URL handling
            function getQueryParam(name) {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(name);
            }

            function activateTabFromQuery() {
                const tabName = getQueryParam('tab');
                if (tabName) {
                    const tabTrigger = document.querySelector(`[data-bs-target="#${tabName}"]`);
                    if (tabTrigger) {
                        const tab = new bootstrap.Tab(tabTrigger);
                        tab.show();

                        // Reinitialize bulk actions when tab is shown
                        setTimeout(() => {
                            if (tabName === 'multiple') {
                                setupBulkActions('Standard');
                            } else if (tabName === 'student-specific') {
                                setupBulkActions('Student');
                            }
                        }, 300);
                    }
                }
            }

            // Update URL with tab parameter when a tab is clicked
            $('#feeTab').on('click', '.nav-link', function(e) {
                const target = $(this).data('bs-target');
                if (target) {
                    const tabName = target.replace('#', '');
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('tab', tabName);
                    const newUrl = window.location.pathname + '?' + urlParams.toString();
                    history.replaceState(null, null, newUrl);

                    // Reinitialize bulk actions when tab changes
                    setTimeout(() => {
                        if (tabName === 'multiple') {
                            setupBulkActions('Standard');
                        } else if (tabName === 'student-specific') {
                            setupBulkActions('Student');
                        }
                    }, 300);
                }
            });

            // Add tab parameter to filter forms
            $('form[method="get"]').on('submit', function() {
                const activeTab = $('.nav-link.active');
                if (activeTab.length) {
                    const target = activeTab.data('bs-target');
                    if (target) {
                        const tabName = target.replace('#', '');
                        let tabInput = $(this).find('input[name="tab"]');
                        if (tabInput.length === 0) {
                            $(this).append('<input type="hidden" name="tab" value="' + tabName + '">');
                        } else {
                            tabInput.val(tabName);
                        }
                    }
                }
            });

            // Activate correct tab on page load
            activateTabFromQuery();

            // Handle back/forward navigation
            $(window).on('popstate', function() {
                activateTabFromQuery();
            });

            // Prevent alert from auto-closing
            $('.alert').alert('dispose');
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set current month as default for all Effective From fields
            const currentMonth = new Date().toISOString().slice(0, 7);

            // Initialize all Effective From fields (month selection, 1st day as value)
            document.querySelectorAll('.month-first-day').forEach(monthInput => {
                monthInput.value = currentMonth;
                updateDateFromMonthInput(monthInput, true);
                monthInput.addEventListener('change', function() {
                    updateDateFromMonthInput(this, true);
                });
            });

            // Initialize Effective Until field (month selection, last day as value)
            const untilInput = document.querySelector('.month-last-day');
            if (untilInput) {
                updateDateFromMonthInput(untilInput, false);
                untilInput.addEventListener('change', function() {
                    updateDateFromMonthInput(this, false);
                });
            }

            function updateDateFromMonthInput(monthInput, isFirstDay) {
                if (!monthInput.value) return;

                const [year, month] = monthInput.value.split('-');
                const date = isFirstDay ?
                    new Date(year, month - 1, 1) : // First day of month
                    new Date(year, month, 0); // Last day of month

                // Find the nearest hidden input with the same base name
                const hiddenInput = monthInput.nextElementSibling;
                if (hiddenInput && hiddenInput.tagName === 'INPUT' && hiddenInput.type === 'hidden') {
                    hiddenInput.value = date.toISOString().split('T')[0];
                }
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const feeTab = document.getElementById('feeTab');

            // Function to get query parameter by name
            function getQueryParam(name) {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(name);
            }

            // Function to set query parameter
            function setQueryParam(name, value) {
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set(name, value);
                const newUrl = window.location.pathname + '?' + urlParams.toString();
                history.pushState(null, null, newUrl);
            }

            // Function to activate tab based on query parameter
            function activateTabFromQuery() {
                const tabName = getQueryParam('tab');
                if (tabName) {
                    const tabTrigger = document.querySelector(`[data-bs-target="#${tabName}"]`);
                    if (tabTrigger) {
                        const tab = new bootstrap.Tab(tabTrigger);
                        tab.show();
                    }
                }
            }

            // Update query parameter when a tab is clicked
            feeTab.addEventListener('click', function(e) {
                if (e.target.classList.contains('nav-link')) {
                    const target = e.target.getAttribute('data-bs-target');
                    if (target) {
                        const tabName = target.replace('#', '');
                        setQueryParam('tab', tabName);
                    }
                }
            });

            // Activate correct tab on page load
            activateTabFromQuery();

            // Also handle back/forward navigation
            window.addEventListener('popstate', function() {
                activateTabFromQuery();
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($feeStructure)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#feeTable').DataTable({
                    // paging: false,
                    "order": [], // Disable initial sorting
                    "drawCallback": function(settings) {
                        // Re-initialize row selection after DataTables redraws
                        console.log('DataTables redraw completed, reinitializing row selection');
                        setTimeout(() => {
                            setupBulkActions('Standard');
                        }, 100);
                    },
                    "initComplete": function(settings, json) {
                        console.log('DataTables initialization completed');
                        setTimeout(() => {
                            setupBulkActions('Standard');
                        }, 200);
                    }
                    // other options...
                });
            <?php endif; ?>
        });
    </script>

    <!-- Initialize Select2 -->
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                placeholder: 'Select options',
                allowClear: true,
                theme: 'bootstrap-5'
            });
        });
    </script>

    <script>
        // Add this script to your page
        document.addEventListener('DOMContentLoaded', function() {
            // Function to get query parameter by name
            function getQueryParam(name) {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(name);
            }

            // Function to activate tab based on query parameter
            function activateTabFromQuery() {
                const tabName = getQueryParam('tab');
                if (tabName) {
                    const tabTrigger = document.querySelector(`[data-bs-target="#${tabName}"]`);
                    if (tabTrigger) {
                        const tab = new bootstrap.Tab(tabTrigger);
                        tab.show();
                    }
                }
            }

            // Update URL with tab parameter when a tab is clicked
            const feeTab = document.getElementById('feeTab');
            if (feeTab) {
                feeTab.addEventListener('click', function(e) {
                    if (e.target.classList.contains('nav-link')) {
                        const target = e.target.getAttribute('data-bs-target');
                        if (target) {
                            const tabName = target.replace('#', '');
                            const urlParams = new URLSearchParams(window.location.search);
                            urlParams.set('tab', tabName);
                            const newUrl = window.location.pathname + '?' + urlParams.toString();
                            history.replaceState(null, null, newUrl);
                        }
                    }
                });
            }

            // Activate correct tab on page load
            activateTabFromQuery();

            // Also add tab parameter to filter forms to preserve it when submitting
            const filterForms = document.querySelectorAll('form[method="get"]');
            filterForms.forEach(form => {
                form.addEventListener('submit', function() {
                    const activeTab = document.querySelector('.nav-link.active');
                    if (activeTab) {
                        const target = activeTab.getAttribute('data-bs-target');
                        if (target) {
                            const tabName = target.replace('#', '');

                            // Check if there's already a tab input
                            let tabInput = form.querySelector('input[name="tab"]');
                            if (!tabInput) {
                                tabInput = document.createElement('input');
                                tabInput.type = 'hidden';
                                tabInput.name = 'tab';
                                form.appendChild(tabInput);
                            }
                            tabInput.value = tabName;
                        }
                    }
                });
            });

            // On page load, if there's a tab parameter but no active tab, activate it
            const urlTab = getQueryParam('tab');
            if (urlTab && !document.querySelector('.nav-link.active')) {
                activateTabFromQuery();
            }
        });
    </script>
    <script>
        // CSV Import Functionality
        $(document).ready(function() {
            // CSV Import form submission
            $('#csvImportForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const formData = new FormData(this);

                // Show loading state
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');

                $.ajax({
                    url: 'process_csv_import.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    // In the success callback of your AJAX call, update this part:
                    success: function(response) {
                        submitBtn.prop('disabled', false).html(originalText);

                        if (response.success) {
                            displayCSVPreview(response.data, response.summary);

                            if (response.message) {
                                // Show message in a better way
                                const alertDiv = $('<div class="alert alert-info alert-dismissible fade show" role="alert">' +
                                    '<i class="fas fa-info-circle me-2"></i>' + response.message +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                                    '</div>');
                                $('#csvPreview').before(alertDiv);
                            }

                            // Show errors if any
                            if (response.errors && response.errors.length > 0) {
                                const errorDiv = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                                    '<h5><i class="fas fa-exclamation-triangle me-2"></i>Validation Errors</h5>' +
                                    '<ul class="mb-0">' +
                                    response.errors.slice(0, 5).map(error => '<li>' + error + '</li>').join('') +
                                    '</ul>' +
                                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                                    '</div>');
                                $('#csvPreview').before(errorDiv);
                            }

                            if (form.find('input[name="import_action"]:checked').val() === 'preview') {
                                $('#confirmImport').show().off('click').on('click', function() {
                                    if (confirm(`About to import ${response.summary.valid_rows} records. Continue?`)) {
                                        form.find('#importData').prop('checked', true);
                                        $('#csvImportForm').submit();
                                    }
                                });
                            } else {
                                // Show success message
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            }
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error); // Debug log
                        submitBtn.prop('disabled', false).html(originalText);
                        alert('An error occurred: ' + error);
                    }
                });
            });

            function displayCSVPreview(data, summary) {
                console.log('Displaying preview:', data, summary); // Debug log

                const previewBody = $('#previewBody');
                previewBody.empty();

                data.forEach(record => {
                    const rowClass = record.is_valid ? '' : 'table-danger';
                    const statusClass = record.status === 'Error' ? 'badge bg-danger' :
                        record.status === 'Imported' ? 'badge bg-success' :
                        record.status === 'Pending' ? 'badge bg-warning' : 'badge bg-secondary';

                    const row = `
                <tr class="${rowClass}">
                    <td>${escapeHtml(record.student_id)}</td>
                    <td>${escapeHtml(record.student_name || 'N/A')}</td>
                    <td>${escapeHtml(record.class || 'N/A')}</td>
                    <td>${escapeHtml(record.category_name)}</td>
                    <td>₹${parseFloat(record.amount || 0).toFixed(2)}</td>
                    <td>${escapeHtml(record.effective_from)}</td>
                    <td>${escapeHtml(record.effective_until || 'N/A')}</td>
                    <td><span class="${statusClass}">${escapeHtml(record.status)}</span></td>
                </tr>
            `;
                    previewBody.append(row);
                });

                // Update summary
                const summaryHtml = `
            <div class="alert alert-light">
                <strong>Summary:</strong><br>
                Total Rows: ${summary.total_rows}<br>
                Valid Rows: <span class="text-success">${summary.valid_rows}</span><br>
                Errors: <span class="text-danger">${summary.error_rows}</span>
            </div>
        `;
                $('#previewSummary').html(summaryHtml);

                // Show preview section
                $('#csvPreview').show();
            }

            function escapeHtml(text) {
                if (text === null || text === undefined) return '';
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.toString().replace(/[&<>"']/g, function(m) {
                    return map[m];
                });
            }

            // File input validation
            $('#csv_file').on('change', function() {
                const file = this.files[0];
                if (file) {
                    // Check file extension
                    const fileName = file.name.toLowerCase();
                    if (!fileName.endsWith('.csv')) {
                        alert('Please select a CSV file');
                        $(this).val('');
                    }

                    // Check file size
                    const maxSize = 10 * 1024 * 1024; // 10MB
                    if (file.size > maxSize) {
                        alert('File size exceeds 10MB limit');
                        $(this).val('');
                    }
                }
            });
        });
    </script>
</body>

</html>