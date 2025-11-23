<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Initialize filter variables
$from_month = isset($_GET['from_month']) ? $_GET['from_month'] : '';
$to_month = isset($_GET['to_month']) ? $_GET['to_month'] : '';
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$category_ids = isset($_GET['category_ids']) ? $_GET['category_ids'] : [];

// Check if months are selected
$months_selected = !empty($from_month) && !empty($to_month);

// Build the query
$query = "
    SELECT 
        fp.id,
        fp.student_id,
        s.studentname,
        fp.academic_year,
        fp.month,
        fp.amount,
        fp.payment_type,
        fp.transaction_id,
        fp.collected_by,
        m.fullname as collector_name,
        fp.collection_date,
        fp.notes,
        fc.category_name
    FROM fee_payments fp
    LEFT JOIN rssimyprofile_student s ON fp.student_id = s.student_id
    LEFT JOIN rssimyaccount_members m ON fp.collected_by = m.associatenumber
    LEFT JOIN fee_categories fc ON fp.category_id = fc.id
    WHERE 1=1
";

$params = [];
$param_count = 0;

// Add date range filter only if months are selected
if ($months_selected) {
    // Convert YYYY-MM to academic year and month for filtering
    list($from_year, $from_month_num) = explode('-', $from_month);
    list($to_year, $to_month_num) = explode('-', $to_month);
    
    $from_month_name = date('F', mktime(0, 0, 0, $from_month_num, 1));
    $to_month_name = date('F', mktime(0, 0, 0, $to_month_num, 1));
    
    // For academic year: if month is Jan-Mar, academic year is previous year
    $from_academic_year = ($from_month_num >= 4) ? $from_year : $from_year - 1;
    $to_academic_year = ($to_month_num >= 4) ? $to_year : $to_year - 1;
    
    // Create month order mapping
    $month_order = [
        'January' => 1, 'February' => 2, 'March' => 3,
        'April' => 4, 'May' => 5, 'June' => 6,
        'July' => 7, 'August' => 8, 'September' => 9,
        'October' => 10, 'November' => 11, 'December' => 12
    ];
    
    // SIMPLIFIED AND CORRECT FILTERING LOGIC:
    // We need to find records where:
    // 1. Academic year is between from_academic_year and to_academic_year
    // 2. For the start academic year, only include months >= from_month
    // 3. For the end academic year, only include months <= to_month
    
    $query .= " AND (";
    
    // Case 1: Academic years completely between the range
    if ($to_academic_year - $from_academic_year > 1) {
        $query .= " (fp.academic_year::integer > $from_academic_year AND fp.academic_year::integer < $to_academic_year) OR";
    }
    
    // Case 2: Same academic year (both from and to in same academic year)
    if ($from_academic_year == $to_academic_year) {
        $query .= " (fp.academic_year::integer = $from_academic_year AND 
            CASE fp.month 
                WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
                WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
                WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
                WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
            END BETWEEN {$month_order[$from_month_name]} AND {$month_order[$to_month_name]})";
    } else {
        // Case 3: Different academic years
        // Start academic year - months from start month to March (month order 1-12)
        $query .= " (fp.academic_year::integer = $from_academic_year AND 
            CASE fp.month 
                WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
                WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
                WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
                WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
            END >= {$month_order[$from_month_name]}) OR";
        
        // Middle academic years (if any)
        if ($to_academic_year - $from_academic_year > 1) {
            $query .= " (fp.academic_year::integer > $from_academic_year AND fp.academic_year::integer < $to_academic_year) OR";
        }
        
        // End academic year - months from April to end month
        $query .= " (fp.academic_year::integer = $to_academic_year AND 
            CASE fp.month 
                WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3 
                WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6 
                WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9 
                WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12 
            END <= {$month_order[$to_month_name]})";
    }
    
    $query .= ")";
    
    // Debug output
    error_log("From: $from_month (Academic: $from_academic_year, Month: $from_month_name)");
    error_log("To: $to_month (Academic: $to_academic_year, Month: $to_month_name)");
    error_log("Query: " . $query);
}

// Only add other filters and execute query if months are selected
if ($months_selected) {
    // Add student_id filter
    if (!empty($student_id)) {
        $param_count++;
        $query .= " AND fp.student_id = $$param_count";
        $params[] = $student_id;
    }

    // Add category filter
    if (!empty($category_ids) && is_array($category_ids)) {
        $category_placeholders = [];
        foreach ($category_ids as $category_id) {
            $param_count++;
            $category_placeholders[] = "$$param_count";
            $params[] = $category_id;
        }
        if (!empty($category_placeholders)) {
            $query .= " AND fc.id IN (" . implode(',', $category_placeholders) . ")";
        }
    }

    // Add ordering
    $query .= " ORDER BY fp.academic_year DESC, 
        CASE fp.month 
            WHEN 'April' THEN 1 WHEN 'May' THEN 2 WHEN 'June' THEN 3 
            WHEN 'July' THEN 4 WHEN 'August' THEN 5 WHEN 'September' THEN 6 
            WHEN 'October' THEN 7 WHEN 'November' THEN 8 WHEN 'December' THEN 9 
            WHEN 'January' THEN 10 WHEN 'February' THEN 11 WHEN 'March' THEN 12 
        END DESC, 
        fp.collection_date DESC";

    // Debug final query
    error_log("Final Query: " . $query);
    error_log("Params: " . print_r($params, true));

    // Execute query
    $result = pg_query_params($con, $query, $params);
    
    if (!$result) {
        error_log("Query failed: " . pg_last_error($con));
    }
}

// Get fee categories for dropdown
$categories_result = pg_query($con, "SELECT id, category_name FROM fee_categories ORDER BY category_name");
$categories = [];
while ($row = pg_fetch_assoc($categories_result)) {
    $categories[$row['id']] = $row['category_name'];
}

// Calculate totals
$total_amount = 0;
$total_records = 0;
if ($months_selected && $result) {
    $total_records = pg_num_rows($result);
    while ($row = pg_fetch_assoc($result)) {
        $total_amount += floatval($row['amount']);
    }
    pg_result_seek($result, 0); // Reset pointer to beginning
}
?>

<!-- Rest of your HTML code remains the same -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Payments Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <style>
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: 1px solid #e3e6f0;
        }

        .table th {
            background-color: #4e73df;
            color: white;
        }

        .total-row {
            background-color: #f8f9fc;
            font-weight: bold;
        }

        .filter-section {
            background-color: #f8f9fc;
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .summary-card {
            transition: transform 0.2s;
        }

        .summary-card:hover {
            transform: translateY(-5px);
        }
        
        .alert-warning {
            border-left: 4px solid #ffc107;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card mt-4">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            Fee Payments Report
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Summary Cards - Only show if months are selected -->
                        <?php if ($months_selected): ?>
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card summary-card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <div class="text-white-50 small">Total Records</div>
                                                <div class="fs-5 fw-bold"><?php echo $total_records; ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-list fa-2x text-white-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card summary-card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <div class="text-white-50 small">Total Amount</div>
                                                <div class="fs-5 fw-bold">₹<?php echo number_format($total_amount, 2); ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-rupee-sign fa-2x text-white-50"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Filter Section -->
                        <div class="filter-section">
                            <form method="GET" class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Month Range *</label>
                                    <div class="input-group">
                                        <input type="month" class="form-control" id="from_month" name="from_month"
                                            value="<?php echo htmlspecialchars($from_month); ?>" required>
                                        <span class="input-group-text">to</span>
                                        <input type="month" class="form-control" id="to_month" name="to_month"
                                            value="<?php echo htmlspecialchars($to_month); ?>" required>
                                    </div>
                                    <div class="form-text text-danger">Please select both From and To months to view results</div>
                                </div>
                                <div class="col-md-3">
                                    <label for="student_id" class="form-label">Student ID</label>
                                    <input type="text" class="form-control" id="student_id" name="student_id"
                                        value="<?php echo htmlspecialchars($student_id); ?>"
                                        placeholder="Enter Student ID">
                                </div>
                                <div class="col-md-3">
                                    <label for="category_ids" class="form-label">Fee Categories</label>
                                    <select class="form-select" id="category_ids" name="category_ids[]" multiple="multiple">
                                        <?php foreach ($categories as $id => $name): ?>
                                            <option value="<?php echo $id; ?>"
                                                <?php echo in_array($id, $category_ids) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-filter me-1"></i> Apply Filters
                                    </button>
                                    <a href="fee_payments_report.php" class="btn btn-secondary">
                                        <i class="fas fa-redo me-1"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Results Section -->
                        <div class="table-responsive">
                            <?php if (!$months_selected): ?>
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Please select a month range</strong> to view fee payments data. Choose both "From" and "To" months above and click "Apply Filters".
                                </div>
                            <?php else: ?>
                                <table class="table table-bordered table-hover" id="paymentsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                            <th>Academic Year</th>
                                            <th>Month</th>
                                            <th>Category</th>
                                            <th>Amount</th>
                                            <th>Payment Type</th>
                                            <th>Transaction ID</th>
                                            <th>Collected By</th>
                                            <th>Collection Date</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result && pg_num_rows($result) > 0): ?>
                                            <?php while ($row = pg_fetch_assoc($result)): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['studentname'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['month']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                                    <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $row['payment_type'] == 'Online' ? 'success' : 'primary'; ?>">
                                                            <?php echo htmlspecialchars($row['payment_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['transaction_id'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($row['collector_name']); ?></td>
                                                    <td><?php echo date('d-M-Y', strtotime($row['collection_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($row['notes'] ?? ''); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="12" class="text-center text-muted py-4">
                                                    <i class="fas fa-search fa-2x mb-2"></i><br>
                                                    No fee payments found matching the selected criteria.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable only if table exists
            <?php if ($months_selected): ?>
            $('#paymentsTable').DataTable({
                "pageLength": 25,
                "order": [
                    [0, 'desc']
                ],
                "dom": '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
            });
            <?php endif; ?>

            // Initialize Select2 for multi-select
            $('#category_ids').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select fee categories',
                allowClear: true
            });

            // Date validation - ensure to_month is not before from_month
            $('#from_month, #to_month').change(function() {
                const fromMonth = $('#from_month').val();
                const toMonth = $('#to_month').val();

                if (fromMonth && toMonth && fromMonth > toMonth) {
                    alert('To Month cannot be before From Month');
                    $('#to_month').val(fromMonth);
                }
            });
        });
    </script>
</body>

</html>

<?php
// Close database connection
if (isset($result) && $result) {
    pg_free_result($result);
}
if ($categories_result) {
    pg_free_result($categories_result);
}
?>