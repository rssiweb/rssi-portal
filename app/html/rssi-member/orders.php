<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    header("Location: books.php");
    exit;
}

// Prevent form resubmission
// if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['filter'])) {
//     header("Location: " . $_SERVER['REQUEST_URI']);
//     exit();
// }

$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['message'], $_SESSION['error']);

// Process issue book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['issue_book'])) {
    $order_id = $_POST['order_id'];
    $due_date = $_POST['due_date'];
    $issued_by = $fullname;

    $query = "UPDATE book_orders 
              SET status = 'issued', 
                  issued_by = '$issued_by', 
                  issue_date = NOW(), 
                  due_date = '$due_date'
              WHERE order_id = $order_id";

    if (pg_query($con, $query)) {
        $_SESSION['message'] = "Book issued successfully!";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        $_SESSION['error'] = "Error issuing book: " . pg_last_error($con);
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Process return book
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_book'])) {
    $order_id = $_POST['order_id'];
    $fine_amount = $_POST['fine_amount'] ?? 0;
    $book_id = $_POST['book_id'];

    // When recording a fine, mark it as not settled
    $query = "UPDATE book_orders 
SET status = 'returned', 
    return_date = NOW(), 
    fine_amount = $fine_amount,
    fine_settled = false
WHERE order_id = $order_id";

    if (pg_query($con, $query)) {
        // Increment available copies
        pg_query($con, "UPDATE books SET available_copies = available_copies + 1 WHERE book_id = $book_id");
        $_SESSION['message'] = "Book returned successfully!";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        $_SESSION['error'] = "Error returning book: " . pg_last_error($con);
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Get current academic year (April 1 to March 31)
$current_month = date('n');
$current_year = date('Y');
if ($current_month >= 4) { // April or later
    $academic_year_start = $current_year;
    $academic_year_end = $current_year + 1;
} else { // January-March
    $academic_year_start = $current_year - 1;
    $academic_year_end = $current_year;
}
$current_academic_year = "$academic_year_start-$academic_year_end";

// Get filter values
$selected_academic_year = isset($_POST['academic_year']) ? $_POST['academic_year'] : $current_academic_year;
$selected_status = isset($_POST['status']) ? $_POST['status'] : '';
$search_query = isset($_POST['search']) ? pg_escape_string($con, $_POST['search']) : '';

// Parse academic year for filtering
$academic_year_parts = explode('-', $selected_academic_year);
$academic_start_date = $academic_year_parts[0] . '-04-01';
$academic_end_date = $academic_year_parts[1] . '-03-31';



// In your settle_fines processing:
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['settle_fines'])) {
    $settlement_date = $_POST['settlement_date'];
    $settlement_notes = pg_escape_string($con, $_POST['settlement_notes']);
    $settled_by = $fullname;

    // Validate settlement date is within academic year
    $settlement_date_obj = new DateTime($settlement_date);
    $academic_start_obj = new DateTime($academic_start_date);
    $academic_end_obj = new DateTime($academic_end_date);

    if ($settlement_date_obj < $academic_start_obj || $settlement_date_obj > $academic_end_obj) {
        $_SESSION['error'] = "Settlement date must be within the selected academic year ($selected_academic_year)";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    // Get all UNSETTLED fines for the selected academic year
    $unsettled_fines = pg_query(
        $con,
        "SELECT SUM(fine_amount) as total, COUNT(*) as count 
         FROM book_orders 
         WHERE status = 'returned' AND fine_amount > 0 AND fine_settled = false
         AND return_date BETWEEN '$academic_start_date' AND '$academic_end_date'"
    );
    $unsettled_data = pg_fetch_assoc($unsettled_fines);
    $total_amount = $unsettled_data['total'];
    $fine_count = $unsettled_data['count'];

    if ($total_amount > 0) {
        // Record the settlement with academic year context
        $insert_query = "INSERT INTO fine_settlements 
                        (settlement_date, amount, settled_by, notes, fine_count, academic_year) 
                        VALUES ('$settlement_date', $total_amount, '$settled_by', 
                        '$settlement_notes', $fine_count, '$selected_academic_year')";

        if (pg_query($con, $insert_query)) {
            // Mark only fines from this academic year as settled
            pg_query(
                $con,
                "UPDATE book_orders 
                 SET fine_settled = true 
                 WHERE status = 'returned' AND fine_amount > 0 AND fine_settled = false
                 AND return_date BETWEEN '$academic_start_date' AND '$academic_end_date'"
            );

            $_SESSION['message'] = "Fines for academic year $selected_academic_year settled successfully! Total amount: ₹" .
                number_format($total_amount, 2) .
                " (for $fine_count fines)";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    } else {
        $_SESSION['error'] = "No outstanding fines to settle for academic year $selected_academic_year";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}


// Build base query with filters
$base_query = "SELECT o.*, b.title, b.author, b.isbn, b.publisher, b.category,
                CASE 
                    WHEN o.user_type = 'student' THEN (SELECT studentname FROM rssimyprofile_student WHERE student_id = o.user_id)
                    WHEN o.user_type = 'teacher' THEN (SELECT fullname FROM rssimyaccount_members WHERE associatenumber = o.user_id)
                    ELSE 'Unknown'
                END as user_name,
                CASE 
                    WHEN o.user_type = 'student' THEN (SELECT emailaddress FROM rssimyprofile_student WHERE student_id = o.user_id)
                    WHEN o.user_type = 'teacher' THEN (SELECT email FROM rssimyaccount_members WHERE associatenumber = o.user_id)
                    ELSE 'Unknown'
                END as user_email,
                CASE 
                    WHEN o.user_type = 'student' THEN (SELECT contact FROM rssimyprofile_student WHERE student_id = o.user_id)
                    WHEN o.user_type = 'teacher' THEN (SELECT phone FROM rssimyaccount_members WHERE associatenumber = o.user_id)
                    ELSE 'Unknown'
                END as user_mobile,
                CASE 
                    WHEN o.user_type = 'student' THEN (SELECT class FROM rssimyprofile_student WHERE student_id = o.user_id)
                    WHEN o.user_type = 'teacher' THEN (SELECT position FROM rssimyaccount_members WHERE associatenumber = o.user_id)
                    ELSE 'Unknown'
                END as user_details
                FROM book_orders o
                JOIN books b ON o.book_id = b.book_id
                WHERE (o.issue_date BETWEEN '$academic_start_date' AND '$academic_end_date' OR 
                      (o.issue_date IS NULL AND o.order_date BETWEEN '$academic_start_date' AND '$academic_end_date'))";

// Add status filter if selected
if (!empty($selected_status)) {
    $base_query .= " AND o.status = '$selected_status'";
}

// Add search filter if provided
if (!empty($search_query)) {
    $base_query .= " AND (
        b.title ILIKE '%$search_query%' OR 
        b.author ILIKE '%$search_query%' OR 
        (
            (o.user_type = 'student' AND (SELECT studentname FROM rssimyprofile_student WHERE student_id = o.user_id) ILIKE '%$search_query%') OR
            (o.user_type = 'teacher' AND (SELECT fullname FROM rssimyaccount_members WHERE associatenumber = o.user_id) ILIKE '%$search_query%')
        )
    )";
}

// Complete query with ordering
$orders_query = $base_query . " ORDER BY 
                    CASE WHEN o.status = 'pending' THEN 1
                         WHEN o.status = 'issued' THEN 2
                         ELSE 3
                    END,
                    o.order_date DESC";

$orders_result = pg_query($con, $orders_query);

// Get statistics for current academic year
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM book_orders WHERE status = 'pending' AND 
        (issue_date BETWEEN '$academic_start_date' AND '$academic_end_date' OR 
         (issue_date IS NULL AND order_date BETWEEN '$academic_start_date' AND '$academic_end_date'))) as pending_orders,
    (SELECT COUNT(*) FROM book_orders WHERE status = 'issued' AND 
        issue_date BETWEEN '$academic_start_date' AND '$academic_end_date') as issued_books,
    (SELECT COUNT(*) FROM book_orders WHERE status = 'returned' AND 
        return_date BETWEEN '$academic_start_date' AND '$academic_end_date') as returned_books,
    (SELECT COALESCE(SUM(fine_amount), 0) FROM book_orders WHERE status = 'returned' AND 
        return_date BETWEEN '$academic_start_date' AND '$academic_end_date' AND 
        (fine_settled IS NULL OR fine_settled = false)) as outstanding_fines,
    (SELECT COUNT(*) FROM books) as total_books,
    (SELECT COUNT(*) FROM books WHERE available_copies > 0) as available_books";

$stats_result = pg_query($con, $stats_query);
$stats = pg_fetch_assoc($stats_result);

// Get last settlement details
$last_settlement = pg_fetch_assoc(pg_query(
    $con,
    "SELECT * FROM fine_settlements ORDER BY settlement_id DESC LIMIT 1"
));

// Generate academic year options (last 5 years and next 2 years)
$academic_years = [];
$current_year = date('Y');
for ($i = $current_year - 4; $i <= $current_year + 2; $i++) {
    $academic_years[] = ($i) . '-' . ($i + 1);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">
    <style>
        .stat-card {
            border-left: 4px solid #0d6efd;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }

        .badge-issued {
            background-color: #198754;
        }

        .badge-returned {
            background-color: #0dcaf0;
            color: #000;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
        }

        .detail-value {
            color: #212529;
        }

        .order-details {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
        }

        .filter-section {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .settlement-card {
            border-left: 4px solid #6f42c1;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <main class="col-md-12 px-md-4 py-3">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Library Orders Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#settleFinesModal">
                                <i class="bi bi-cash-coin"></i> Settle Fines
                            </button>
                            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#settlementHistoryModal">
                                <i class="bi bi-clock-history"></i> History
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter Section -->
                <div class="filter-section mb-4">
                    <form method="POST" class="row g-3">
                        <div class="col-md-3">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <select class="form-select" id="academic_year" name="academic_year">
                                <?php foreach ($academic_years as $year): ?>
                                    <option value="<?= $year ?>" <?= $selected_academic_year == $year ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $selected_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="issued" <?= $selected_status == 'issued' ? 'selected' : '' ?>>Issued</option>
                                <option value="returned" <?= $selected_status == 'returned' ? 'selected' : '' ?>>Returned</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search"
                                placeholder="Search by book title, author or user name" value="<?= htmlspecialchars($search_query) ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" name="filter" class="btn btn-primary w-100">Apply Filters</button>
                        </div>
                    </form>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Books</h6>
                                        <h3 class="mb-0"><?= number_format($stats['total_books']) ?></h3>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-book text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Available Books</h6>
                                        <h3 class="mb-0"><?= number_format($stats['available_books']) ?></h3>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-check-circle text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Pending Orders</h6>
                                        <h3 class="mb-0"><?= number_format($stats['pending_orders']) ?></h3>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-clock text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Issued Books</h6>
                                        <h3 class="mb-0"><?= number_format($stats['issued_books']) ?></h3>
                                    </div>
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-hand-holding text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Returned Books</h6>
                                        <h3 class="mb-0"><?= number_format($stats['returned_books']) ?></h3>
                                    </div>
                                    <div class="bg-secondary bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-undo text-secondary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card settlement-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Outstanding Fines</h6>
                                        <h3 class="mb-0">₹<?= number_format($stats['outstanding_fines'], 2) ?></h3>
                                        <?php if ($last_settlement): ?>
                                            <small class="text-muted">Last settled: <?= date('d M Y', strtotime($last_settlement['settlement_date'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="bg-danger bg-opacity-10 p-3 rounded">
                                        <i class="fas fa-rupee-sign text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Order ID</th>
                                <th>Book Details</th>
                                <th>User Details</th>
                                <th>Order Info</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = pg_fetch_assoc($orders_result)): ?>
                                <tr>
                                    <td><?php echo $order['order_id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['title']); ?></strong><br>
                                        <small class="text-muted">by <?php echo htmlspecialchars($order['author']); ?></small><br>
                                        <small>ISBN: <?php echo htmlspecialchars($order['isbn']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['user_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo ucfirst($order['user_type']); ?> - <?php echo htmlspecialchars($order['user_details']); ?></small><br>
                                        <small><?php echo !empty($order['user_email']) ? htmlspecialchars($order['user_email']) : 'N/A'; ?></small><br>
                                        <small><?php echo htmlspecialchars($order['user_mobile']); ?></small>
                                    </td>
                                    <td>
                                        <small><strong>Ordered:</strong> <?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></small><br>
                                        <?php if ($order['status'] == 'issued' || $order['status'] == 'returned'): ?>
                                            <small><strong>Issued:</strong> <?php echo date('d M Y', strtotime($order['issue_date'])); ?></small><br>
                                            <small><strong>Due:</strong> <?php echo date('d M Y', strtotime($order['due_date'])); ?></small><br>
                                            <?php if ($order['status'] == 'returned'): ?>
                                                <small><strong>Returned:</strong> <?php echo date('d M Y', strtotime($order['return_date'])); ?></small><br>
                                                <?php if ($order['fine_amount'] > 0): ?>
                                                    <small><strong>Fine:</strong> ₹<?php echo number_format($order['fine_amount'], 2); ?></small>
                                                    <?php if (!empty($order['fine_settled']) && $order['fine_settled'] === 't'): ?>
                                                        <span class="badge bg-success">Settled</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Pending</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?php
                                                                        switch ($order['status']) {
                                                                            case 'pending':
                                                                                echo 'badge-pending';
                                                                                break;
                                                                            case 'issued':
                                                                                echo 'badge-issued';
                                                                                break;
                                                                            case 'returned':
                                                                                echo 'badge-returned';
                                                                                break;
                                                                            default:
                                                                                echo 'bg-light text-dark';
                                                                        }
                                                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                        <?php if ($order['status'] == 'issued'): ?>
                                            <?php
                                            $due_date = new DateTime($order['due_date']);
                                            $today = new DateTime();
                                            $interval = $today->diff($due_date);
                                            $days_left = $interval->format('%r%a');

                                            if ($days_left < 0) {
                                                echo '<br><small class="text-danger">Overdue by ' . abs($days_left) . ' days</small>';
                                            } elseif ($days_left == 0) {
                                                echo '<br><small class="text-warning">Due today</small>';
                                            } else {
                                                echo '<br><small class="text-success">' . $days_left . ' days left</small>';
                                            }
                                            ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($order['status'] == 'pending'): ?>
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#issueModal<?php echo $order['order_id']; ?>">
                                                <i class="bi bi-check-circle"></i> Issue
                                            </button>
                                        <?php elseif ($order['status'] == 'issued'): ?>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#returnModal<?php echo $order['order_id']; ?>">
                                                <i class="bi bi-arrow-return-left"></i> Return
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-secondary mt-1" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $order['order_id']; ?>">
                                            <i class="bi bi-info-circle"></i> Details
                                        </button>
                                    </td>
                                </tr>

                                <!-- Issue Modal -->
                                <?php if ($order['status'] == 'pending'): ?>
                                    <div class="modal fade" id="issueModal<?php echo $order['order_id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-success text-white">
                                                    <h5 class="modal-title">Issue Book</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label for="due_date" class="form-label">Due Date *</label>
                                                            <input type="date" class="form-control" id="due_date" name="due_date" required
                                                                min="<?php echo date('Y-m-d'); ?>"
                                                                value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
                                                        </div>
                                                        <div class="alert alert-info">
                                                            <i class="bi bi-info-circle"></i> Book: <strong><?php echo htmlspecialchars($order['title']); ?></strong><br>
                                                            User: <strong><?php echo htmlspecialchars($order['user_name']); ?></strong>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="issue_book" class="btn btn-success">Confirm Issue</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Return Modal -->
                                <?php if ($order['status'] == 'issued'): ?>
                                    <div class="modal fade" id="returnModal<?php echo $order['order_id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">Return Book</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                    <input type="hidden" name="book_id" value="<?php echo $order['book_id']; ?>">
                                                    <div class="modal-body">
                                                        <div class="alert alert-info">
                                                            <i class="bi bi-info-circle"></i>
                                                            <strong>Book:</strong> <?php echo htmlspecialchars($order['title']); ?><br>
                                                            <strong>User:</strong> <?php echo htmlspecialchars($order['user_name']); ?><br>
                                                            <strong>Issued On:</strong> <?php echo date('d M Y', strtotime($order['issue_date'])); ?><br>
                                                            <strong>Due Date:</strong> <?php echo date('d M Y', strtotime($order['due_date'])); ?>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="fine_amount" class="form-label">Fine Amount (₹)</label>
                                                            <input type="number" class="form-control" id="fine_amount" name="fine_amount"
                                                                min="0" step="0.01" value="0" placeholder="Enter 0 if no fine">
                                                            <small class="text-muted">Leave as 0 if no fine is applicable</small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="return_book" class="btn btn-primary">Confirm Return</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Details Modal -->
                                <div class="modal fade" id="detailsModal<?php echo $order['order_id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-secondary text-white">
                                                <h5 class="modal-title">Order Details - #<?php echo $order['order_id']; ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="order-details mb-4">
                                                            <h6 class="mb-3"><i class="bi bi-book"></i> Book Details</h6>
                                                            <div class="row mb-2">
                                                                <div class="col-4 detail-label">Title:</div>
                                                                <div class="col-8 detail-value"><?php echo htmlspecialchars($order['title']); ?></div>
                                                            </div>
                                                            <div class="row mb-2">
                                                                <div class="col-4 detail-label">Author:</div>
                                                                <div class="col-8 detail-value"><?php echo htmlspecialchars($order['author']); ?></div>
                                                            </div>
                                                            <div class="row mb-2">
                                                                <div class="col-4 detail-label">ISBN:</div>
                                                                <div class="col-8 detail-value"><?php echo htmlspecialchars($order['isbn']); ?></div>
                                                            </div>
                                                            <div class="row mb-2">
                                                                <div class="col-4 detail-label">Publisher:</div>
                                                                <div class="col-8 detail-value"><?php echo htmlspecialchars($order['publisher']); ?></div>
                                                            </div>
                                                            <div class="row mb-2">
                                                                <div class="col-4 detail-label">Category:</div>
                                                                <div class="col-8 detail-value"><?php echo htmlspecialchars($order['category']); ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="order-details mb-4">
                                                            <h6 class="mb-3"><i class="bi bi-person"></i> User Details</h6>
                                                            <div class="row mb-2">
                                                                <div class="col-4 detail-label">Name:</div>
                                                                <div class="col-8 detail-value"><?php echo htmlspecialchars($order['user_name']); ?></div>
                                                            </div>
                                                            <div class="row mb-2">
                                                                <div class="col-4 detail-label">Type:</div>
                                                                <div class="col-8 detail-value"><?php echo ucfirst($order['user_type']); ?></div>
                                                            </div>
                                                            <div class="row mb-2">
                                                                <div class="col-4 detail-label">Details:</div>
                                                                <div class="col-8 detail-value"><?php echo htmlspecialchars($order['user_details']); ?></div>
                                                            </div>
                                                            <div class="row mb-2">
                                                                <div class="col-4 detail-label">Email:</div>
                                                                <div class="col-8 detail-value"><?php echo !empty($order['user_email']) ? htmlspecialchars($order['user_email']) : 'N/A'; ?></small></div>
                                                            </div>
                                                            <div class="row mb-2">
                                                                <div class="col-4 detail-label">Phone:</div>
                                                                <div class="col-8 detail-value"><?php echo htmlspecialchars($order['user_mobile']); ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="order-details">
                                                    <h6 class="mb-3"><i class="bi bi-clock-history"></i> Order Timeline</h6>
                                                    <div class="row mb-2">
                                                        <div class="col-4 detail-label">Order Date:</div>
                                                        <div class="col-8 detail-value"><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-4 detail-label">Ordered By:</div>
                                                        <div class="col-8 detail-value"><?php echo htmlspecialchars($order['ordered_by']); ?></div>
                                                    </div>
                                                    <?php if ($order['status'] == 'issued' || $order['status'] == 'returned'): ?>
                                                        <div class="row mb-2">
                                                            <div class="col-4 detail-label">Issued On:</div>
                                                            <div class="col-8 detail-value"><?php echo date('d M Y, h:i A', strtotime($order['issue_date'])); ?></div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-4 detail-label">Issued By:</div>
                                                            <div class="col-8 detail-value"><?php echo htmlspecialchars($order['issued_by']); ?></div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-4 detail-label">Due Date:</div>
                                                            <div class="col-8 detail-value"><?php echo date('d M Y', strtotime($order['due_date'])); ?></div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($order['status'] == 'returned'): ?>
                                                        <div class="row mb-2">
                                                            <div class="col-4 detail-label">Returned On:</div>
                                                            <div class="col-8 detail-value"><?php echo date('d M Y, h:i A', strtotime($order['return_date'])); ?></div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-4 detail-label">Fine Amount:</div>
                                                            <div class="col-8 detail-value">₹<?php echo number_format($order['fine_amount'], 2); ?></div>
                                                        </div>
                                                        <?php if (isset($order['fine_settled']) && $order['fine_settled']): ?>
                                                            <div class="row mb-2">
                                                                <div class="col-4 detail-label">Fine Status:</div>
                                                                <div class="col-8">
                                                                    <span class="badge bg-success">Settled</span>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="row mb-2">
                                                                <div class="col-4 detail-label">Fine Status:</div>
                                                                <div class="col-8">
                                                                    <span class="badge bg-warning">Pending Settlement</span>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <div class="row mb-2">
                                                        <div class="col-4 detail-label">Status:</div>
                                                        <div class="col-8">
                                                            <span class="badge rounded-pill <?php
                                                                                            switch ($order['status']) {
                                                                                                case 'pending':
                                                                                                    echo 'badge-pending';
                                                                                                    break;
                                                                                                case 'issued':
                                                                                                    echo 'badge-issued';
                                                                                                    break;
                                                                                                case 'returned':
                                                                                                    echo 'badge-returned';
                                                                                                    break;
                                                                                                default:
                                                                                                    echo 'bg-light text-dark';
                                                                                            }
                                                                                            ?>">
                                                                <?php echo ucfirst($order['status']); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Settle Fines Modal -->
    <div class="modal fade" id="settleFinesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Settle Outstanding Fines - <?= $selected_academic_year ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="settleFinesForm">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle fs-4 me-2"></i>
                                <div>
                                    <p class="mb-1">Current outstanding fines for <strong><?= $selected_academic_year ?></strong>:</p>
                                    <h4 class="mb-1">₹<?= number_format($stats['outstanding_fines'], 2) ?></h4>
                                    <p class="mb-0 small">
                                        <i class="bi bi-calendar-range"></i>
                                        <?= date('d M Y', strtotime($academic_start_date)) ?> to <?= date('d M Y', strtotime($academic_end_date)) ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="settlement_date" class="form-label">Settlement Date *</label>
                            <input type="date" class="form-control" id="settlement_date" name="settlement_date"
                                required
                                min="<?= $academic_start_date ?>"
                                max="<?= $academic_end_date ?>"
                                value="<?= date('Y-m-d') ?>">
                            <div class="form-text">
                                Must be between <?= date('d M Y', strtotime($academic_start_date)) ?> and <?= date('d M Y', strtotime($academic_end_date)) ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="settlement_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="settlement_notes" name="settlement_notes"
                                rows="3" placeholder="Enter any additional notes about this settlement"></textarea>
                        </div>

                        <?php if ($last_settlement): ?>
                            <div class="alert alert-secondary">
                                <h6><i class="bi bi-clock-history"></i> Last Settlement Details</h6>
                                <div class="row small">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Date:</strong> <?= date('d M Y', strtotime($last_settlement['settlement_date'])) ?></p>
                                        <p class="mb-1"><strong>Amount:</strong> ₹<?= number_format($last_settlement['amount'], 2) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Settled By:</strong> <?= htmlspecialchars($last_settlement['settled_by']) ?></p>
                                        <?php if (!empty($last_settlement['notes'])): ?>
                                            <p class="mb-1"><strong>Notes:</strong> <?= htmlspecialchars($last_settlement['notes']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="settle_fines" class="btn btn-primary" id="submitSettlementBtn"
                            <?= $stats['outstanding_fines'] <= 0 ? 'disabled' : '' ?>>
                            <i class="bi bi-check-circle"></i> Confirm Settlement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Settlement History Modal -->
    <div class="modal fade" id="settlementHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title">Fine Settlement History - <?= $selected_academic_year ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    // Get settlements for the selected academic year
                    $settlements_query = "SELECT * FROM fine_settlements 
                                    WHERE settlement_date BETWEEN '$academic_start_date' AND '$academic_end_date'
                                    ORDER BY settlement_id DESC";
                    $settlements = pg_query($con, $settlements_query);

                    if (pg_num_rows($settlements) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Total Amount</th>
                                        <th>Fines Count</th>
                                        <th>Settled By</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($settlement = pg_fetch_assoc($settlements)): ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($settlement['settlement_date'])) ?></td>
                                            <td>₹<?= number_format($settlement['amount'], 2) ?></td>
                                            <td><?= $settlement['fine_count'] ?></td>
                                            <td><?= htmlspecialchars($settlement['settled_by']) ?></td>
                                            <td><?= !empty($settlement['notes']) ? htmlspecialchars($settlement['notes']) : 'N/A' ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No settlement records found for <?= $selected_academic_year ?>.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Settlement form validation
            const settleForm = document.getElementById('settleFinesForm');
            const settlementDateInput = document.getElementById('settlement_date');
            const submitBtn = document.getElementById('submitSettlementBtn');

            if (settleForm) {
                // Disable submit button if no outstanding fines
                if (<?= $stats['outstanding_fines'] <= 0 ? 'true' : 'false' ?>) {
                    submitBtn.disabled = true;
                    submitBtn.title = "No outstanding fines to settle";
                }

                // Validate settlement date is within academic year
                settleForm.addEventListener('submit', function(e) {
                    const settlementDate = new Date(settlementDateInput.value);
                    const academicStart = new Date('<?= $academic_start_date ?>');
                    const academicEnd = new Date('<?= $academic_end_date ?>');

                    if (settlementDate < academicStart || settlementDate > academicEnd) {
                        e.preventDefault();
                        alert('Error: Settlement date must be within the selected academic year (<?= $selected_academic_year ?>).\n\nValid range: <?= date('d M Y', strtotime($academic_start_date)) ?> to <?= date('d M Y', strtotime($academic_end_date)) ?>');
                        settlementDateInput.focus();
                        return false;
                    }
                    return true;
                });

                // Real-time date validation
                settlementDateInput.addEventListener('change', function() {
                    const settlementDate = new Date(this.value);
                    const academicStart = new Date('<?= $academic_start_date ?>');
                    const academicEnd = new Date('<?= $academic_end_date ?>');

                    if (settlementDate < academicStart || settlementDate > academicEnd) {
                        this.classList.add('is-invalid');
                        submitBtn.disabled = true;
                    } else {
                        this.classList.remove('is-invalid');
                        submitBtn.disabled = false;
                    }
                });
            }
        });
    </script>
</body>

</html>