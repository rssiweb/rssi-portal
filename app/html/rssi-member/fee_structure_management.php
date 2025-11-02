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
                                                    <div class="table-responsive">
                                                        <table class="table table-hover" id="feeTable">
                                                            <thead class="table-light">
                                                                <tr>
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
                                                <!-- Student Specific Fees Table -->
                                                <div>
                                                    <h5 class="mb-3"><i class="fas fa-user-graduate me-2"></i>Student Specific Fees</h5>

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

    <!-- Required JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
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
                    minimumInputLength: 1,
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

            // Handle both standard and student fee deactivation
            $(document).on('click', '.btn-deactivate, .btn-deactivate-student', function() {
                const feeId = $(this).data('fee-id');
                const isStudentFee = $(this).data('fee-type') === 'student';

                $('#deactivateFeeId').val(feeId);
                $('#deactivateFeeType').val(isStudentFee ? 'student' : 'standard');

                // Set default deactivation date to today
                $('#deactivate_date').val(new Date().toISOString().split('T')[0]);

                const deactivateModal = new bootstrap.Modal(document.getElementById('deactivateModal'));
                deactivateModal.show();
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
                    "order": [] // Disable initial sorting
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
</body>

</html>