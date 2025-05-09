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

// Get all classes
$classes = pg_fetch_all(pg_query($con, "SELECT DISTINCT class FROM rssimyprofile_student ORDER BY class")) ?? [];

// Get current fee structure with category names
$feeStructure = pg_fetch_all(pg_query(
    $con,
    "SELECT fs.*, fc.category_name, fc.fee_type
     FROM fee_structure fs
     JOIN fee_categories fc ON fs.category_id = fc.id
     ORDER BY fs.class, fs.student_type, fc.category_name, fs.effective_from DESC"
)) ?? [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Fee Structure Management</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
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
    </style>
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
                                            <!-- <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="single-tab" data-bs-toggle="tab" data-bs-target="#single" type="button" role="tab">Single Fee</button>
                                            </li> -->
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="multiple-tab" data-bs-toggle="tab" data-bs-target="#multiple" type="button" role="tab">Multiple Fees</button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="student-specific-tab" data-bs-toggle="tab" data-bs-target="#student-specific" type="button" role="tab">Student Specific</button>
                                            </li>
                                        </ul>

                                        <div class="tab-content" id="feeTabContent">
                                            <!-- Single Fee Tab -->
                                            <!-- <div class="tab-pane fade show active" id="single" role="tabpanel">
                                                <form method="post">
                                                    <div class="row g-3">
                                                        <div class="col-md-3">
                                                            <label for="class" class="form-label">Class</label>
                                                            <select name="class" class="form-select" required>
                                                                <option value="">Select Class</option>
                                                                <?php foreach ($classes as $class): ?>
                                                                    <option value="<?= $class['class'] ?>"><?= $class['class'] ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label for="student_type" class="form-label">Access Category</label>
                                                            <select name="student_type" id="student_type" class="form-select" required>
                                                                <option value="">--Select Access Category--</option>
                                                                <?php
                                                                // Fetch active plans from database
                                                                $plansQuery = "SELECT name,division FROM plans WHERE is_active = true ORDER BY name";
                                                                $plansResult = pg_query($con, $plansQuery);

                                                                while ($plan = pg_fetch_assoc($plansResult)) {
                                                                    $selected = (isset($array['student_type']) && $array['student_type'] == $plan['name']) ? 'selected' : '';
                                                                    echo '<option value="' . htmlspecialchars($plan['name']) . '" ' . $selected . '>' . htmlspecialchars($plan['name'] . '-' . $plan['division']) . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label for="category_id" class="form-label">Fee Category</label>
                                                            <select name="category_id" class="form-select" required>
                                                                <option value="">Select Category</option>
                                                                <?php foreach ($categories as $category): ?>
                                                                    <option value="<?= $category['id'] ?>">
                                                                        <?= $category['category_name'] ?>
                                                                        (<?= $category['fee_type'] ?>)
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label for="amount" class="form-label">Amount (₹)</label>
                                                            <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="0" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label for="effective_from" class="form-label">Effective From</label>
                                                            <input type="date" name="effective_from" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                                        </div>
                                                        <div class="col-md-2 d-flex align-items-end">
                                                            <button type="submit" name="add_fee_structure" class="btn btn-primary w-100">
                                                                <i class="fas fa-save me-1"></i> Save
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div> -->

                                            <!-- Multiple Fees Tab -->
                                            <div class="tab-pane fade show active" id="multiple" role="tabpanel">
                                                <form method="post">
                                                    <input type="hidden" name="multiple_fees" value="1">
                                                    <div class="row mb-3">
                                                        <div class="col-md-3">
                                                            <label for="class" class="form-label">Class (Select multiple if needed)</label>
                                                            <select name="class[]" id="class" class="form-select" multiple required>
                                                                <?php foreach ($classes as $class): ?>
                                                                    <option value="<?= htmlspecialchars($class['class']) ?>">
                                                                        <?= htmlspecialchars($class['class']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label for="student_type" class="form-label">Access Category</label>
                                                            <select name="student_type" id="student_type" class="form-select" required>
                                                                <option value="">--Select Access Category--</option>
                                                                <?php
                                                                // Fetch active plans from database
                                                                $plansQuery = "SELECT name,division FROM plans WHERE is_active = true ORDER BY name";
                                                                $plansResult = pg_query($con, $plansQuery);

                                                                while ($plan = pg_fetch_assoc($plansResult)) {
                                                                    $selected = (isset($array['student_type']) && $array['student_type'] == $plan['name']) ? 'selected' : '';
                                                                    echo '<option value="' . htmlspecialchars($plan['name']) . '" ' . $selected . '>' . htmlspecialchars($plan['name'] . '-' . $plan['division']) . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label for="effective_from" class="form-label">Effective From</label>
                                                            <input type="date" name="effective_from" class="form-control" value="<?= date('Y-m-d') ?>" required>
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
                                            </div>

                                            <!-- Student Specific Fees Tab -->
                                            <div class="tab-pane fade" id="student-specific" role="tabpanel">
                                                <form method="post" action="assign_student_fees.php">
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Students (select multiple)</label>
                                                            <select name="student_ids[]" class="form-select selectpicker" multiple required
                                                                data-live-search="true" data-actions-box="true">
                                                                <?php
                                                                $students = pg_fetch_all(pg_query(
                                                                    $con,
                                                                    "SELECT student_id, studentname, class 
                                             FROM rssimyprofile_student 
                                             WHERE filterstatus='Active'
                                             ORDER BY class, studentname"
                                                                ));
                                                                foreach ($students as $student): ?>
                                                                    <option value="<?= $student['student_id'] ?>">
                                                                        <?= $student['student_id'] . '-' . $student['studentname'] ?> (<?= $student['class'] ?>)
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">Fee Category</label>
                                                            <select name="category_id" class="form-select" required>
                                                                <option value="">Select Category</option>
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
                                                        <div class="col-md-4">
                                                            <label class="form-label">Effective From</label>
                                                            <input type="date" name="effective_from" class="form-control"
                                                                value="<?= date('Y-m-d') ?>" required>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label">Effective Until (optional)</label>
                                                            <input type="date" name="effective_until" class="form-control">
                                                        </div>
                                                        <div class="col-md-4 d-flex align-items-end">
                                                            <button type="submit" name="assign_fee" class="btn btn-primary">
                                                                <i class="fas fa-save me-1"></i> Assign Fee
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Current Fee Structure -->
                                <div class="card shadow-sm">
                                    <div class="card-header bg-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span><i class="fas fa-list-alt me-2"></i>Fee Structure</span>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="showInactiveToggle">
                                                <label class="form-check-label" for="showInactiveToggle">Show Inactive Fees</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <!-- Standard Fee Structure Table -->
                                        <div class="mb-4">
                                            <h5 class="mb-3"><i class="fas fa-list-alt me-2"></i>Standard Fee Structure</h5>
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Access Category</th>
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
                                                        <?php foreach ($feeStructure as $fee):
                                                            $isActive = empty($fee['effective_until']) || $fee['effective_until'] >= date('Y-m-d');
                                                            $rowClass = $isActive ? 'active-fee' : 'inactive-fee';
                                                        ?>
                                                            <tr class="<?= $rowClass ?> <?= $isActive ? 'active-row' : 'inactive-row' ?>" style="<?= $isActive ? '' : 'display: none;' ?>">
                                                                <td><?= $fee['class'] ?>/<?= $fee['student_type'] ?></td>
                                                                <td><?= $fee['category_name'] ?></td>
                                                                <td>
                                                                    <span class="badge fee-type-badge <?= strtolower(str_replace(' ', '-', $fee['fee_type'])) ?>">
                                                                        <?= $fee['fee_type'] ?>
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
                                                                        <button class="btn btn-sm btn-outline-danger btn-deactivate" data-fee-id="<?= $fee['id'] ?>">
                                                                            <i class="fas fa-ban me-1"></i> Deactivate
                                                                        </button>
                                                                    <?php else: ?>
                                                                        <span class="text-muted">No actions</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Student Specific Fees Table -->
                                        <div>
                                            <h5 class="mb-3"><i class="fas fa-user-graduate me-2"></i>Student Specific Fees</h5>
                                            <?php
                                            // Query for student specific fees
                                            $studentFeesQuery = "SELECT ssf.*, 
                                            s.studentname, s.class,
                                            fc.category_name, fc.fee_type
                                            FROM student_specific_fees ssf
                                            JOIN rssimyprofile_student s ON ssf.student_id = s.student_id
                                            JOIN fee_categories fc ON ssf.category_id = fc.id
                                            ORDER BY s.class, s.studentname, fc.category_name, ssf.effective_from DESC";
                                            $studentFees = pg_fetch_all(pg_query($con, $studentFeesQuery)) ?? [];
                                            ?>

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
                                                        <?php foreach ($studentFees as $fee):
                                                            $isActive = empty($fee['effective_until']) || $fee['effective_until'] >= date('Y-m-d');
                                                            $rowClass = $isActive ? 'active-fee' : 'inactive-fee';
                                                        ?>
                                                            <tr class="<?= $rowClass ?> <?= $isActive ? 'active-row' : 'inactive-row' ?>" style="<?= $isActive ? '' : 'display: none;' ?>">
                                                                <td><?= $fee['studentname'] ?></td>
                                                                <td><?= $fee['class'] ?></td>
                                                                <td><?= $fee['category_name'] ?></td>
                                                                <td>
                                                                    <span class="badge fee-type-badge <?= strtolower(str_replace(' ', '-', $fee['fee_type'])) ?>">
                                                                        <?= $fee['fee_type'] ?>
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
                                                    </tbody>
                                                </table>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize select picker
            $('.selectpicker').selectpicker();

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

            // Toggle inactive fees visibility
            $('#showInactiveToggle').change(function() {
                if ($(this).is(':checked')) {
                    // Show all rows (both active and inactive)
                    $('tr.inactive-row').show();
                } else {
                    // Hide only inactive rows, keep active rows visible
                    $('tr.inactive-row').hide();
                    $('tr.active-row').show(); // This ensures active rows stay visible
                }
            });

            // Prevent alert from auto-closing
            $('.alert').alert('dispose');
        });
    </script>
</body>

</html>