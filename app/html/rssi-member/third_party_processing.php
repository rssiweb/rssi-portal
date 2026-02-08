<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Calculate current academic year based on April-March cycle
$currentYear = date('Y');
$currentMonth = date('n'); // 1-12

// April-March academic year
if ($currentMonth >= 4) {
    // April to December: Academic year is currentYear-nextYear
    $currentAcademicYear = $currentYear . '-' . ($currentYear + 1);
} else {
    // January to March: Academic year is previousYear-currentYear
    $currentAcademicYear = ($currentYear - 1) . '-' . $currentYear;
}

// Generate 5 academic years including current
$academicYears = [];
for ($i = 0; $i < 5; $i++) {
    $startYear = (int)explode('-', $currentAcademicYear)[0] - $i;
    $academicYears[] = $startYear . '-' . ($startYear + 1);
}

// Handle filters
$whereClause = "WHERE 1=1";
$params = [];
$paramCount = 0;

// Get selected academic year from GET or use current academic year
$selectedAcademicYear = isset($_GET['academic_year']) && !empty($_GET['academic_year'])
    ? $_GET['academic_year']
    : $currentAcademicYear;

// Always filter by academic year
$whereClause .= " AND tp.academic_year = $" . ++$paramCount;
$params[] = $selectedAcademicYear;

// Update $_GET for form display
$_GET['academic_year'] = $selectedAcademicYear;

if (isset($_GET['request_number']) && !empty($_GET['request_number'])) {
    $whereClause .= " AND tp.request_number ILIKE $" . ++$paramCount;
    $params[] = '%' . $_GET['request_number'] . '%';
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $whereClause .= " AND tp.status = $" . ++$paramCount;
    $params[] = $_GET['status'];
}

if (isset($_GET['vendor_name']) && !empty($_GET['vendor_name'])) {
    $whereClause .= " AND v.vendor_name ILIKE $" . ++$paramCount;
    $params[] = '%' . $_GET['vendor_name'] . '%';
}

// Handle CSV Export
if (isset($_POST['export']) && $_POST['export'] == 'csv') {
    // Use the same filters already applied
    $exportQuery = "SELECT 
                    tp.request_number,
                    v.vendor_name,
                    v.contact_number as vendor_contact,
                    v.email as vendor_email,
                    tp.invoice_number,
                    TO_CHAR(tp.invoice_date, 'DD-MM-YYYY') as invoice_date,
                    tp.amount,
                    tp.purpose,
                    tp.academic_year,
                    tp.category,
                    tp.submitted_by,
                    TO_CHAR(tp.submission_date, 'DD-MM-YYYY HH24:MI') as submission_date,
                    tp.status,
                    TO_CHAR(tp.payment_date, 'DD-MM-YYYY') as payment_date,
                    tp.transaction_id,
                    tp.payment_mode,
                    tp.bank_reference,
                    tp.payment_status,
                    v.gst_number,
                    v.bank_account_no,
                    v.bank_name,
                    v.ifsc_code
                  FROM third_party_payments tp
                  LEFT JOIN third_party_vendors v ON tp.vendor_id = v.vendor_id
                  $whereClause 
                  ORDER BY tp.submission_date DESC";

    $exportResult = pg_query_params($con, $exportQuery, $params);
    $exportData = pg_fetch_all($exportResult) ?: [];

    if (!empty($exportData)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="third_party_payments_' . date('Y-m-d_H-i-s') . '.csv"');

        $output = fopen('php://output', 'w');
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

        $headers = [
            'Request Number',
            'Vendor Name',
            'Vendor Contact',
            'Vendor Email',
            'Invoice Number',
            'Invoice Date',
            'Amount (₹)',
            'Purpose',
            'Academic Year',
            'Category',
            'Submitted By',
            'Submission Date',
            'Status',
            'Payment Date',
            'Transaction ID',
            'Payment Mode',
            'Bank Reference',
            'Payment Status',
            'GST Number',
            'Bank Account',
            'Bank Name',
            'IFSC Code'
        ];

        fputcsv($output, $headers);

        foreach ($exportData as $row) {
            fputcsv($output, [
                $row['request_number'],
                $row['vendor_name'],
                $row['vendor_contact'] ?? '',
                $row['vendor_email'] ?? '',
                $row['invoice_number'],
                $row['invoice_date'],
                $row['amount'],
                $row['purpose'],
                $row['academic_year'],
                $row['category'] ?? '',
                $row['submitted_by'],
                $row['submission_date'],
                $row['status'],
                $row['payment_date'] ?? '',
                $row['transaction_id'] ?? '',
                $row['payment_mode'] ?? '',
                $row['bank_reference'] ?? '',
                $row['payment_status'] ?? '',
                $row['gst_number'] ?? '',
                $row['bank_account_no'] ?? '',
                $row['bank_name'] ?? '',
                $row['ifsc_code'] ?? ''
            ]);
        }

        fclose($output);
        exit;
    } else {
        $_SESSION['error_message'] = "No data found to export.";
        header("Location: third_party_processing.php");
        exit;
    }
}

// Handle payment update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $id = $_POST['payment_id'];
    $paymentDate = $_POST['payment_date'];
    $transactionId = $_POST['transaction_id'];
    $paymentMode = $_POST['payment_mode'];
    $bankReference = $_POST['bank_reference'];
    $paymentStatus = $_POST['payment_status'];
    $status = $_POST['status'];

    // Handle payment proof upload
    $paymentProofPath = '';
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['payment_proof'];
        $timestamp = time();
        $fileName = "payment_proof_{$id}_{$timestamp}";

        // Upload to Google Drive
        $driveFolderId = "1MPw1VqHe_dvY3bZ-O1EWYYRsXGEx2wilyEGaCdHOq4HG2Fhg8qgNWfOejgB0USBGfZJNlnsC";
        include("../../util/drive.php");
        $paymentProofPath = uploadeToDrive($uploadedFile, $driveFolderId, $fileName);
    }

    $updateQuery = "
UPDATE third_party_payments SET 
    payment_date = $1,
    transaction_id = $2,
    payment_mode = $3,
    bank_reference = $4,
    payment_status = $5,
    status = $6::varchar,
    payment_proof_path = COALESCE($7, payment_proof_path),
    closed_date = CASE 
        WHEN $6::varchar = 'Closed' THEN CURRENT_TIMESTAMP 
        ELSE NULL 
    END,
    closed_by = CASE 
        WHEN $6::varchar = 'Closed' THEN $8 
        ELSE NULL 
    END
WHERE id = $9
";

    $updateParams = [
        $paymentDate,
        $transactionId,
        $paymentMode,
        $bankReference,
        $paymentStatus,
        $status,
        $paymentProofPath ?: null,
        $associatenumber,
        $id
    ];

    $result = pg_query_params($con, $updateQuery, $updateParams);

    if ($result) {
        $_SESSION['success_message'] = "Payment details updated successfully!";
        header("Location: third_party_processing.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Failed to update payment details.";
    }
}

// Fetch requests with JOIN to vendors table
$query = "SELECT 
            tp.*,
            v.vendor_name,
            v.contact_number as vendor_contact,
            v.email as vendor_email,
            v.address as vendor_address,
            v.gst_number,
            v.bank_account_no,
            v.bank_name,
            v.ifsc_code
          FROM third_party_payments tp
          LEFT JOIN third_party_vendors v ON tp.vendor_id = v.vendor_id
          $whereClause 
          ORDER BY tp.submission_date DESC";

$result = pg_query_params($con, $query, $params);
$requests = pg_fetch_all($result) ?: [];
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>
    <title>Third Party Payment Processing</title>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }

        .status-closed {
            background-color: #d6d8d9;
            color: #383d41;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .vendor-info-row td {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 140px;
            display: inline-block;
        }

        .info-value {
            color: #212529;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Filter Requests</h5>

                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo $_SESSION['success_message']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['success_message']); ?>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $_SESSION['error_message']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['error_message']); ?>
                            <?php endif; ?>

                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="academic_year" class="form-label">Academic Year</label>
                                    <select class="form-select" id="academic_year" name="academic_year">
                                        <?php foreach ($academicYears as $year): ?>
                                            <option value="<?php echo $year; ?>" <?php echo (isset($_GET['academic_year']) && $_GET['academic_year'] == $year) ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="request_number" class="form-label">Request Number</label>
                                    <input type="text" class="form-control" id="request_number" name="request_number"
                                        value="<?php echo isset($_GET['request_number']) ? htmlspecialchars($_GET['request_number']) : ''; ?>">
                                </div>

                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All Status</option>
                                        <option value="Pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Approved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                        <option value="Paid" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Closed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Closed') ? 'selected' : ''; ?>>Closed</option>
                                        <option value="Rejected" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="vendor_name" class="form-label">Vendor Name</label>
                                    <input type="text" class="form-control" id="vendor_name" name="vendor_name"
                                        value="<?php echo isset($_GET['vendor_name']) ? htmlspecialchars($_GET['vendor_name']) : ''; ?>">
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="third_party_processing.php" class="btn btn-secondary">Clear Filters</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title">Payment Requests</h5>
                                <div class="export-btn-container">
                                    <form method="POST" style="display: inline;">
                                        <!-- Pass all current filter parameters as hidden fields -->
                                        <input type="hidden" name="export" value="csv">
                                        <input type="hidden" name="academic_year" value="<?php echo htmlspecialchars($selectedAcademicYear); ?>">
                                        <input type="hidden" name="request_number" value="<?php echo isset($_GET['request_number']) ? htmlspecialchars($_GET['request_number']) : ''; ?>">
                                        <input type="hidden" name="status" value="<?php echo isset($_GET['status']) ? htmlspecialchars($_GET['status']) : ''; ?>">
                                        <input type="hidden" name="vendor_name" value="<?php echo isset($_GET['vendor_name']) ? htmlspecialchars($_GET['vendor_name']) : ''; ?>">
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-filetype-csv"></i> Export CSV
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover" id="paymentTable">
                                    <thead>
                                        <tr>
                                            <th>Request No.</th>
                                            <th>Vendor Name</th>
                                            <th>Invoice No.</th>
                                            <th>Amount</th>
                                            <th>Academic Year</th>
                                            <th>Submitted Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($requests)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No requests found</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($requests as $req): ?>
                                                <?php
                                                $statusClass = 'status-' . strtolower($req['status']);
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($req['request_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($req['vendor_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($req['invoice_number']); ?></td>
                                                    <td>₹<?php echo number_format($req['amount'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($req['academic_year']); ?></td>
                                                    <td><?php echo date('d-m-Y', strtotime($req['submission_date'])); ?></td>
                                                    <td>
                                                        <span class="status-badge <?php echo $statusClass; ?>">
                                                            <?php echo $req['status']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="bi bi-three-dots-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <button type="button" class="dropdown-item"
                                                                        data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $req['id']; ?>">
                                                                        <i class="bi bi-eye me-2"></i> View Details
                                                                    </button>
                                                                </li>
                                                                <?php if ($req['status'] == 'Pending' || $req['status'] == 'Approved'): ?>
                                                                    <li>
                                                                        <button type="button" class="dropdown-item"
                                                                            data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $req['id']; ?>">
                                                                            <i class="bi bi-cash-coin me-2"></i> Update Payment
                                                                        </button>
                                                                    </li>
                                                                <?php endif; ?>
                                                                <!-- You can add more actions here if needed -->
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <!-- View Details Modal -->
                                                <div class="modal fade" id="viewModal<?php echo $req['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Request Details: <?php echo $req['request_number']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <h6>Vendor Information</h6>
                                                                        <div class="mb-3">
                                                                            <span class="info-label">Vendor Name:</span>
                                                                            <span class="info-value"><?php echo htmlspecialchars($req['vendor_name']); ?></span>
                                                                        </div>
                                                                        <?php if (!empty($req['vendor_contact'])): ?>
                                                                            <div class="mb-3">
                                                                                <span class="info-label">Contact:</span>
                                                                                <span class="info-value"><?php echo htmlspecialchars($req['vendor_contact']); ?></span>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($req['vendor_email'])): ?>
                                                                            <div class="mb-3">
                                                                                <span class="info-label">Email:</span>
                                                                                <span class="info-value"><?php echo htmlspecialchars($req['vendor_email']); ?></span>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($req['gst_number'])): ?>
                                                                            <div class="mb-3">
                                                                                <span class="info-label">GST Number:</span>
                                                                                <span class="info-value"><?php echo htmlspecialchars($req['gst_number']); ?></span>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($req['vendor_address'])): ?>
                                                                            <div class="mb-3">
                                                                                <span class="info-label">Address:</span>
                                                                                <div class="info-value small"><?php echo nl2br(htmlspecialchars($req['vendor_address'])); ?></div>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6>Bank Information</h6>
                                                                        <?php if (!empty($req['bank_account_no'])): ?>
                                                                            <div class="mb-3">
                                                                                <span class="info-label">Account No:</span>
                                                                                <span class="info-value font-monospace"><?php echo htmlspecialchars($req['bank_account_no']); ?></span>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($req['bank_name'])): ?>
                                                                            <div class="mb-3">
                                                                                <span class="info-label">Bank Name:</span>
                                                                                <span class="info-value"><?php echo htmlspecialchars($req['bank_name']); ?></span>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($req['ifsc_code'])): ?>
                                                                            <div class="mb-3">
                                                                                <span class="info-label">IFSC Code:</span>
                                                                                <span class="info-value font-monospace"><?php echo htmlspecialchars($req['ifsc_code']); ?></span>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>

                                                                <hr>

                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <h6>Invoice Information</h6>
                                                                        <div class="mb-3">
                                                                            <span class="info-label">Invoice Number:</span>
                                                                            <span class="info-value"><?php echo htmlspecialchars($req['invoice_number']); ?></span>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <span class="info-label">Invoice Date:</span>
                                                                            <span class="info-value"><?php echo date('d-m-Y', strtotime($req['invoice_date'])); ?></span>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <span class="info-label">Amount:</span>
                                                                            <span class="info-value">₹<?php echo number_format($req['amount'], 2); ?></span>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <span class="info-label">Academic Year:</span>
                                                                            <span class="info-value"><?php echo htmlspecialchars($req['academic_year']); ?></span>
                                                                        </div>
                                                                        <?php if (!empty($req['category'])): ?>
                                                                            <div class="mb-3">
                                                                                <span class="info-label">Category:</span>
                                                                                <span class="info-value"><?php echo htmlspecialchars($req['category']); ?></span>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6>Request Information</h6>
                                                                        <div class="mb-3">
                                                                            <span class="info-label">Purpose:</span>
                                                                            <div class="info-value"><?php echo nl2br(htmlspecialchars($req['purpose'])); ?></div>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <span class="info-label">Submitted By:</span>
                                                                            <span class="info-value"><?php echo htmlspecialchars($req['submitted_by']); ?></span>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <span class="info-label">Submitted On:</span>
                                                                            <span class="info-value"><?php echo date('d-m-Y H:i', strtotime($req['submission_date'])); ?></span>
                                                                        </div>
                                                                        <?php if ($req['bill_document_path']): ?>
                                                                            <div class="mb-3">
                                                                                <span class="info-label">Bill/Invoice:</span>
                                                                                <a href="<?php echo htmlspecialchars($req['bill_document_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                                    <i class="bi bi-download"></i> Download
                                                                                </a>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>

                                                                <?php if (!empty($req['transaction_id'])): ?>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-md-12">
                                                                            <h6>Payment Information</h6>
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <div class="mb-3">
                                                                                        <span class="info-label">Transaction ID:</span>
                                                                                        <span class="info-value"><?php echo htmlspecialchars($req['transaction_id']); ?></span>
                                                                                    </div>
                                                                                    <div class="mb-3">
                                                                                        <span class="info-label">Payment Date:</span>
                                                                                        <span class="info-value"><?php echo date('d-m-Y', strtotime($req['payment_date'])); ?></span>
                                                                                    </div>
                                                                                    <div class="mb-3">
                                                                                        <span class="info-label">Payment Mode:</span>
                                                                                        <span class="info-value"><?php echo htmlspecialchars($req['payment_mode']); ?></span>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-md-6">
                                                                                    <?php if (!empty($req['bank_reference'])): ?>
                                                                                        <div class="mb-3">
                                                                                            <span class="info-label">Bank Reference:</span>
                                                                                            <span class="info-value"><?php echo htmlspecialchars($req['bank_reference']); ?></span>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                    <div class="mb-3">
                                                                                        <span class="info-label">Payment Status:</span>
                                                                                        <span class="info-value"><?php echo htmlspecialchars($req['payment_status']); ?></span>
                                                                                    </div>
                                                                                    <?php if ($req['payment_proof_path']): ?>
                                                                                        <div class="mb-3">
                                                                                            <span class="info-label">Payment Proof:</span>
                                                                                            <a href="<?php echo htmlspecialchars($req['payment_proof_path']); ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                                                                                <i class="bi bi-download"></i> Download
                                                                                            </a>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Update Payment Modal -->
                                                <div class="modal fade" id="paymentModal<?php echo $req['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Update Payment: <?php echo $req['request_number']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST" enctype="multipart/form-data">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="payment_id" value="<?php echo $req['id']; ?>">

                                                                    <div class="mb-3">
                                                                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                                                        <input type="date" class="form-control" name="payment_date"
                                                                            value="<?php echo date('Y-m-d'); ?>" required>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label class="form-label">Transaction ID/UTR <span class="text-danger">*</span></label>
                                                                        <input type="text" class="form-control" name="transaction_id" required>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                                                                        <select class="form-select" name="payment_mode" required>
                                                                            <option value="">Select Mode</option>
                                                                            <option value="NEFT">NEFT</option>
                                                                            <option value="RTGS">RTGS</option>
                                                                            <option value="IMPS">IMPS</option>
                                                                            <option value="Cheque">Cheque</option>
                                                                            <option value="UPI">UPI</option>
                                                                            <option value="Other">Other</option>
                                                                        </select>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label class="form-label">Bank Reference No.</label>
                                                                        <input type="text" class="form-control" name="bank_reference">
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label class="form-label">Payment Status <span class="text-danger">*</span></label>
                                                                        <select class="form-select" name="payment_status" required>
                                                                            <option value="Success">Success</option>
                                                                            <option value="Failed">Failed</option>
                                                                            <option value="Partial">Partial</option>
                                                                        </select>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label class="form-label">Update Status <span class="text-danger">*</span></label>
                                                                        <select class="form-select" name="status" required>
                                                                            <option value="Paid" selected>Paid</option>
                                                                            <option value="Closed">Paid & Close</option>
                                                                            <option value="Rejected">Reject</option>
                                                                        </select>
                                                                    </div>

                                                                    <div class="mb-3">
                                                                        <label class="form-label">Upload Payment Proof</label>
                                                                        <input type="file" class="form-control" name="payment_proof"
                                                                            accept=".pdf,.jpg,.jpeg,.png">
                                                                        <small class="text-muted">Max 5MB. PDF, JPG, PNG formats only.</small>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="update_payment" class="btn btn-primary">Update Payment</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        <?php if (!empty($requests)) : ?>
            $(document).ready(function() {
                $('#paymentTable').DataTable({
                    "order": [],
                    "pageLength": 25,
                    "language": {
                        "search": "Search:",
                        "lengthMenu": "Show _MENU_ entries",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries"
                    }
                });

                // Set today's date as default in payment modal forms
                $('input[name="payment_date"]').val(new Date().toISOString().split('T')[0]);
            });
        <?php endif; ?>
    </script>
</body>

</html>