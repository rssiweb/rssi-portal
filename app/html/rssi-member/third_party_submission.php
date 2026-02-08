<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/drive.php");

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

// Generate 3 academic years including current
$academicYears = [];
for ($i = 0; $i < 3; $i++) {
    $startYear = (int)explode('-', $currentAcademicYear)[0] - $i;
    $academicYears[] = $startYear . '-' . ($startYear + 1);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendorId = null;
    $vendorName = trim($_POST['vendor_name']);
    $selectedVendorId = $_POST['selected_vendor_id'] ?? null;

    // Get current form data
    $currentData = [
        'contact' => $_POST['vendor_contact'] ?? '',
        'email' => $_POST['vendor_email'] ?? '',
        'address' => $_POST['vendor_address'] ?? '',
        'gst' => $_POST['gst_number'] ?? '',
        'bank_account' => $_POST['bank_account_no'] ?? '',
        'bank_name' => $_POST['bank_name'] ?? '',
        'ifsc' => $_POST['ifsc_code'] ?? ''
    ];

    // Track if we're creating a new vendor or updating
    $isNewVendor = false;
    $isBankChanged = false;

    // If we have a selected vendor ID
    if ($selectedVendorId && $selectedVendorId !== '') {
        // Fetch existing vendor
        $checkQuery = "SELECT * FROM third_party_vendors WHERE vendor_id = $1";
        $checkResult = pg_query_params($con, $checkQuery, [$selectedVendorId]);

        if (pg_num_rows($checkResult) > 0) {
            $existingVendor = pg_fetch_assoc($checkResult);

            // Check for bank changes
            $isBankChanged = false;
            $bankFields = [
                'bank_account_no' => $currentData['bank_account'],
                'bank_name' => $currentData['bank_name'],
                'ifsc_code' => $currentData['ifsc']
            ];

            foreach ($bankFields as $field => $newValue) {
                $oldValue = $existingVendor[$field] ?? '';
                if (trim($newValue) !== trim($oldValue)) {
                    $isBankChanged = true;
                    break;
                }
            }

            if ($isBankChanged) {
                // Bank changed - CREATE NEW VENDOR
                $isNewVendor = true;
                $insertQuery = "INSERT INTO third_party_vendors (
                                vendor_name, contact_person, contact_number, email,
                                address, gst_number, bank_account_no, bank_name, ifsc_code,
                                created_by
                              ) VALUES (
                                $1, $2, $3, $4, $5, $6, $7, $8, $9, $10
                              ) RETURNING vendor_id";

                $params = [
                    $vendorName,
                    $currentData['contact'],
                    $currentData['contact'],
                    $currentData['email'],
                    $currentData['address'],
                    $currentData['gst'],
                    $currentData['bank_account'],
                    $currentData['bank_name'],
                    $currentData['ifsc'],
                    $associatenumber
                ];

                $result = pg_query_params($con, $insertQuery, $params);
                if ($result) {
                    $row = pg_fetch_assoc($result);
                    $vendorId = $row['vendor_id'];
                }
            } else {
                // No bank change - UPDATE EXISTING VENDOR
                $vendorId = $selectedVendorId;

                // Check for non-bank changes
                $nonBankChanged = false;
                $nonBankFields = [
                    'contact_number' => $currentData['contact'],
                    'email' => $currentData['email'],
                    'address' => $currentData['address'],
                    'gst_number' => $currentData['gst']
                ];

                foreach ($nonBankFields as $field => $newValue) {
                    $oldValue = $existingVendor[$field] ?? '';
                    if (trim($newValue) !== trim($oldValue)) {
                        $nonBankChanged = true;
                        break;
                    }
                }

                if ($nonBankChanged) {
                    // Update non-bank fields
                    $updateQuery = "UPDATE third_party_vendors SET 
                                    contact_person = $1,
                                    contact_number = $2,
                                    email = $3,
                                    address = $4,
                                    gst_number = $5,
                                    updated_at = CURRENT_TIMESTAMP
                                    WHERE vendor_id = $6";

                    $updateParams = [
                        $currentData['contact'],
                        $currentData['contact'],
                        $currentData['email'],
                        $currentData['address'],
                        $currentData['gst'],
                        $vendorId
                    ];

                    pg_query_params($con, $updateQuery, $updateParams);
                }

                // Always update usage stats
                $updateUsageQuery = "UPDATE third_party_vendors 
                                   SET usage_count = usage_count + 1, 
                                       last_used_date = CURRENT_TIMESTAMP
                                   WHERE vendor_id = $1";
                pg_query_params($con, $updateUsageQuery, [$vendorId]);
            }
        }
    } else {
        // No selected vendor - check if vendor exists by name
        $checkQuery = "SELECT vendor_id FROM third_party_vendors 
                      WHERE LOWER(vendor_name) = LOWER($1)";
        $checkResult = pg_query_params($con, $checkQuery, [$vendorName]);

        if (pg_num_rows($checkResult) > 0) {
            // Vendor exists - use existing ID
            $row = pg_fetch_assoc($checkResult);
            $vendorId = $row['vendor_id'];

            // Update usage stats
            $updateQuery = "UPDATE third_party_vendors 
                           SET usage_count = usage_count + 1, 
                               last_used_date = CURRENT_TIMESTAMP
                           WHERE vendor_id = $1";
            pg_query_params($con, $updateQuery, [$vendorId]);
        } else {
            // Create new vendor
            $isNewVendor = true;
            $insertQuery = "INSERT INTO third_party_vendors (
                            vendor_name, contact_person, contact_number, email,
                            address, gst_number, bank_account_no, bank_name, ifsc_code,
                            created_by
                          ) VALUES (
                            $1, $2, $3, $4, $5, $6, $7, $8, $9, $10
                          ) RETURNING vendor_id";

            $params = [
                $vendorName,
                $currentData['contact'],
                $currentData['contact'],
                $currentData['email'],
                $currentData['address'],
                $currentData['gst'],
                $currentData['bank_account'],
                $currentData['bank_name'],
                $currentData['ifsc'],
                $associatenumber
            ];

            $result = pg_query_params($con, $insertQuery, $params);
            if ($result) {
                $row = pg_fetch_assoc($result);
                $vendorId = $row['vendor_id'];
            }
        }
    }

    if (!$vendorId) {
        $_SESSION['error_message'] = "Failed to process vendor information.";
        header("Location: third_party_submission.php");
        exit;
    }

    // Generate request number
    $month = date('Ym');
    $query = pg_query($con, "SELECT COUNT(*) as count FROM third_party_payments 
                            WHERE request_number LIKE 'VEND/$month/%'");
    $result = pg_fetch_assoc($query);
    $count = $result['count'] + 1;
    $requestNumber = sprintf("VEND/%s/%03d", $month, $count);

    // Handle file upload to Google Drive
    $billDocumentPath = '';
    if (isset($_FILES['bill_document']) && $_FILES['bill_document']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['bill_document'];
        $timestamp = time();
        $fileName = "third_party_{$requestNumber}_{$timestamp}";

        // Upload to Google Drive
        $driveFolderId = "1MPw1VqHe_dvY3bZ-O1EWYYRsXGEx2wilyEGaCdHOq4HG2Fhg8qgNWfOejgB0USBGfZJNlnsC";
        $billDocumentPath = uploadeToDrive($uploadedFile, $driveFolderId, $fileName);
    }

    // Insert payment record into third_party_payments
    $query = "INSERT INTO third_party_payments (
                request_number, 
                vendor_id, 
                invoice_number, 
                invoice_date, 
                amount, 
                purpose, 
                academic_year, 
                category, 
                bill_document_path,
                submitted_by, 
                submission_date, 
                status
              ) VALUES (
                $1, $2, $3, $4, $5, $6, $7, $8, $9, $10, CURRENT_TIMESTAMP, 'Pending'
              )";

    $params = [
        $requestNumber,
        $vendorId,
        $_POST['invoice_number'],
        $_POST['invoice_date'],
        $_POST['amount'],
        $_POST['purpose'],
        $_POST['academic_year'],
        $_POST['category'] ?? '',
        $billDocumentPath,
        $associatenumber
    ];

    $result = pg_query_params($con, $query, $params);

    if ($result) {
        // Success message with appropriate info
        $message = "Vendor payment request submitted successfully! Request Number: $requestNumber";

        if ($selectedVendorId && $isBankChanged) {
            $message .= " (New vendor created due to bank details change)";
        } elseif ($selectedVendorId && !$isBankChanged) {
            $message .= " (Existing vendor updated)";
        } elseif ($isNewVendor) {
            $message .= " (New vendor created)";
        }

        $_SESSION['success_message'] = $message;
        header("Location: third_party_submission.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Failed to submit request. Please try again.";
    }
}
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
    <?php include 'includes/meta.php' ?>

    <title>Third Party Payment Submission</title>

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
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

    <!-- Add jQuery BEFORE your custom scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    <style>
        .vendor-search-container {
            position: relative;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            z-index: 1000;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        }

        .vendor-result {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .vendor-result:hover {
            background-color: #f8f9fa;
        }

        .existing-vendor-badge {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-left: 10px;
        }

        .vendor-actions {
            margin-top: 10px;
        }

        .required-star {
            color: red;
        }

        .bg-light {
            background-color: #f8f9fa !important;
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
                            <h5 class="card-title">Submit Vendor Payment Request</h5>

                            <!-- Success/Error messages -->
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

                            <form method="POST" enctype="multipart/form-data" class="row g-3" id="vendorForm">
                                <!-- Vendor Search Section -->
                                <div class="col-md-12 vendor-search-container">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label">Vendor Selection</label>
                                        <div id="vendorStatus" class="d-none">
                                            <span class="existing-vendor-badge">Existing Vendor</span>
                                        </div>
                                    </div>

                                    <div class="input-group">
                                        <input type="text" class="form-control" id="vendor_search"
                                            placeholder="Type vendor name to search existing vendors...">
                                        <button class="btn btn-outline-primary" type="button" id="searchVendorBtn">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn">
                                            <i class="bi bi-x-circle"></i> Clear
                                        </button>
                                    </div>

                                    <div class="vendor-actions">
                                        <small class="text-muted">
                                            Search for existing vendor or manually enter details for new vendor
                                        </small>
                                    </div>

                                    <div class="search-results" id="searchResults"></div>

                                    <!-- Hidden field to store selected vendor ID -->
                                    <input type="hidden" id="selected_vendor_id" name="selected_vendor_id">
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <h6>Vendor Details</h6>
                                </div>

                                <!-- Vendor Details -->
                                <div class="col-md-6">
                                    <label for="vendor_name" class="form-label">Vendor Name</label>
                                    <input type="text" class="form-control" id="vendor_name" name="vendor_name" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="vendor_contact" class="form-label">Contact Number</label>
                                    <input type="text" class="form-control" id="vendor_contact" name="vendor_contact">
                                </div>

                                <div class="col-md-6">
                                    <label for="vendor_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="vendor_email" name="vendor_email">
                                </div>

                                <div class="col-md-6">
                                    <label for="gst_number" class="form-label">GST Number</label>
                                    <input type="text" class="form-control" id="gst_number" name="gst_number">
                                </div>

                                <div class="col-12">
                                    <label for="vendor_address" class="form-label">Address</label>
                                    <textarea class="form-control" id="vendor_address" name="vendor_address" rows="2"></textarea>
                                </div>

                                <!-- Bank Details (Optional) -->
                                <div class="col-md-6">
                                    <label for="bank_account_no" class="form-label">Bank Account Number</label>
                                    <input type="text" class="form-control" id="bank_account_no" name="bank_account_no">
                                </div>

                                <div class="col-md-6">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name">
                                </div>

                                <div class="col-md-6">
                                    <label for="ifsc_code" class="form-label">IFSC Code</label>
                                    <input type="text" class="form-control" id="ifsc_code" name="ifsc_code">
                                </div>

                                <div class="col-12">
                                    <hr>
                                    <h6>Invoice Details</h6>
                                </div>

                                <!-- Invoice Details -->
                                <div class="col-md-6">
                                    <label for="invoice_number" class="form-label">Invoice Number</label>
                                    <input type="text" class="form-control" id="invoice_number" name="invoice_number" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="invoice_date" class="form-label">Invoice Date</label>
                                    <input type="date" class="form-control" id="invoice_date" name="invoice_date" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="amount" class="form-label">Amount (₹)</label>
                                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="academic_year" class="form-label">Academic Year</label>
                                    <select class="form-select" id="academic_year" name="academic_year" required>
                                        <option value="">Select Academic Year</option>
                                        <?php foreach ($academicYears as $year): ?>
                                            <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category">
                                        <option value="">Select Category</option>
                                        <option value="Services">Services</option>
                                        <option value="Goods">Goods</option>
                                        <option value="Rent">Rent</option>
                                        <option value="Maintenance">Maintenance</option>
                                        <option value="Subscription">Subscription</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="purpose" class="form-label">Purpose/Description</label>
                                    <textarea class="form-control" id="purpose" name="purpose" rows="3" required></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label for="bill_document" class="form-label">Upload Bill/Invoice</label>
                                    <input type="file" class="form-control" id="bill_document" name="bill_document"
                                        accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="text-muted">Accepted formats: PDF, JPG, PNG (Max 5MB)</small>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle"></i> Submit Request
                                    </button>
                                    <button type="reset" class="btn btn-secondary" id="resetFormBtn">
                                        <i class="bi bi-x-circle"></i> Clear Form
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <!-- REQUIRED FIELD STAR SCRIPT -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document
                .querySelectorAll("input[required], select[required], textarea[required]")
                .forEach(function(field) {
                    if (!field.id) return;

                    const label = document.querySelector(`label[for='${field.id}']`);
                    if (label && !label.querySelector(".required-star")) {
                        const star = document.createElement("span");
                        star.classList.add("text-danger", "required-star");
                        star.textContent = " *";
                        label.appendChild(star);
                    }
                });
        });
    </script>

    <script>
        $(document).ready(function() {
            console.log('Vendor search script loaded');

            let selectedVendorId = null;
            let originalVendorData = null;
            let isSearching = false;
            let searchTimer;

            // Search button click
            $('#searchVendorBtn').on('click', function(e) {
                e.preventDefault();
                searchVendor();
            });

            // Clear search
            $('#clearSearchBtn').on('click', function(e) {
                e.preventDefault();
                $('#vendor_search').val('');
                $('#searchResults').hide();
                resetVendorSelection();
            });

            // Search on Enter key
            $('#vendor_search').on('keyup', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchVendor();
                }
            });

            // Auto-search after typing
            $('#vendor_search').on('input', function() {
                clearTimeout(searchTimer);
                const searchTerm = $(this).val().trim();

                if (searchTerm.length >= 2) {
                    searchTimer = setTimeout(searchVendor, 500);
                } else {
                    $('#searchResults').hide();
                }
            });

            function searchVendor() {
                const searchTerm = $('#vendor_search').val().trim();

                if (!searchTerm || searchTerm.length < 2) {
                    $('#searchResults').hide();
                    return;
                }

                if (isSearching) return;

                isSearching = true;

                // Show loading
                $('#searchResults').html(`
            <div class="text-center p-3">
                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                <span class="ms-2">Searching vendors...</span>
            </div>
        `).show();

                // Make AJAX request
                $.ajax({
                    url: 'vendor_search.php',
                    type: 'GET',
                    data: {
                        vendor_name: searchTerm
                    },
                    dataType: 'json',
                    success: function(response) {
                        isSearching = false;

                        if (response.error) {
                            $('#searchResults').html(`
                        <div class="alert alert-danger m-2">
                            <i class="bi bi-exclamation-triangle"></i> ${response.error}
                        </div>
                    `).show();
                            return;
                        }

                        if (!response || response.length === 0) {
                            $('#searchResults').html(`
                        <div class="alert alert-info m-2">
                            <i class="bi bi-info-circle"></i> No existing vendor found.
                            <div class="mt-1">You can manually enter vendor details below.</div>
                        </div>
                    `).show();
                            resetVendorSelection();
                        } else if (response.length === 1) {
                            // Auto-fill if only one result
                            fillVendorDetails(response[0]);
                            $('#searchResults').hide();
                            showAlert('success', `Vendor "${response[0].name}" loaded. You can edit details.`);
                        } else {
                            showResults(response);
                        }
                    },
                    error: function(xhr, status, error) {
                        isSearching = false;
                        $('#searchResults').html(`
                    <div class="alert alert-danger m-2">
                        <i class="bi bi-exclamation-triangle"></i> Search failed!
                        <div class="mt-1">Error: ${error}</div>
                    </div>
                `).show();
                    }
                });
            }

            function showResults(vendors) {
                let html = '<div class="list-group">';

                html += `
            <div class="list-group-item list-group-item-light">
                <small class="text-muted">Found ${vendors.length} vendor(s). Click to load details:</small>
            </div>
        `;

                vendors.forEach(function(vendor) {
                    // Show bank details in search results
                    let bankInfo = '';
                    if (vendor.bank_account || vendor.bank_name || vendor.ifsc) {
                        bankInfo = '<div class="vendor-bank-info mt-2 border-top pt-2">';

                        if (vendor.bank_account) {
                            bankInfo += `<div class="mb-1">
                        <span class="text-muted small">Account No:</span>
                        <span class="ms-2">${vendor.bank_account}</span>
                    </div>`;
                        }

                        if (vendor.bank_name) {
                            bankInfo += `<div class="mb-1">
                        <span class="text-muted small">Bank Name:</span>
                        <span class="ms-2">${vendor.bank_name}</span>
                    </div>`;
                        }

                        if (vendor.ifsc) {
                            bankInfo += `<div class="mb-1">
                        <span class="text-muted small">IFSC Code:</span>
                        <span class="ms-2">${vendor.ifsc}</span>
                    </div>`;
                        }

                        bankInfo += '</div>';
                    }

                    html += `
                <div class="list-group-item list-group-item-action vendor-result" 
                     data-id="${vendor.vendor_id}"
                     data-name="${vendor.name}"
                     data-contact="${vendor.contact || ''}"
                     data-email="${vendor.email || ''}"
                     data-address="${vendor.address || ''}"
                     data-gst="${vendor.gst || ''}"
                     data-bankaccount="${vendor.bank_account || ''}"
                     data-bankname="${vendor.bank_name || ''}"
                     data-ifsc="${vendor.ifsc || ''}"
                     style="cursor: pointer;">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-primary">${vendor.name}</h6>
                            ${vendor.contact ? `<div><small><i class="bi bi-telephone"></i> ${vendor.contact}</small></div>` : ''}
                            ${vendor.email ? `<div><small><i class="bi bi-envelope"></i> ${vendor.email}</small></div>` : ''}
                            ${vendor.gst ? `<div class="mt-1"><span class="badge bg-info">GST: ${vendor.gst}</span></div>` : ''}
                            ${vendor.address ? `<div class="mt-1"><small><i class="bi bi-geo-alt"></i> ${vendor.address.substring(0, 50)}...</small></div>` : ''}
                            ${bankInfo}
                        </div>
                        <div class="text-end ms-2">
                            <span class="badge bg-light text-dark">ID: ${vendor.vendor_id}</span><br>
                            <button class="btn btn-sm btn-outline-primary mt-1">Load</button>
                        </div>
                    </div>
                </div>
            `;
                });

                html += '</div>';

                $('#searchResults').html(html).show();

                // Add click handler to results
                $('.vendor-result').on('click', function() {
                    const vendorData = {
                        vendor_id: $(this).data('id'),
                        name: $(this).data('name'),
                        contact: $(this).data('contact'),
                        email: $(this).data('email'),
                        address: $(this).data('address'),
                        gst: $(this).data('gst'),
                        bank_account: $(this).data('bankaccount'),
                        bank_name: $(this).data('bankname'),
                        ifsc: $(this).data('ifsc')
                    };
                    fillVendorDetails(vendorData);
                    $('#searchResults').hide();
                    showAlert('success', `Vendor "${vendorData.name}" loaded. You can edit details.`);
                });
            }

            function fillVendorDetails(vendor) {
                // Store original data for comparison
                originalVendorData = JSON.parse(JSON.stringify(vendor));
                selectedVendorId = vendor.vendor_id;
                $('#selected_vendor_id').val(vendor.vendor_id);

                // Fill all fields - BUT DO NOT MAKE READ-ONLY
                $('#vendor_name').val(vendor.name || '');
                $('#vendor_contact').val(vendor.contact || '');
                $('#vendor_email').val(vendor.email || '');
                $('#vendor_address').val(vendor.address || '');
                $('#gst_number').val(vendor.gst || '');
                $('#bank_account_no').val(vendor.bank_account || '');
                $('#bank_name').val(vendor.bank_name || '');
                $('#ifsc_code').val(vendor.ifsc || '');

                // Update search field
                $('#vendor_search').val(vendor.name);

                // Show vendor status
                showVendorStatus(true);

                // Highlight fields with existing data
                highlightExistingFields();
            }

            function highlightExistingFields() {
                // Add subtle background to fields with existing data
                $('input, textarea').each(function() {
                    if ($(this).val().trim() !== '') {
                        $(this).addClass('bg-light');
                    } else {
                        $(this).removeClass('bg-light');
                    }
                });
            }

            function resetVendorSelection() {
                originalVendorData = null;
                selectedVendorId = null;
                $('#selected_vendor_id').val('');
                showVendorStatus(false);

                // Remove highlight
                $('input, textarea').removeClass('bg-light');
            }

            function showVendorStatus(isExisting) {
                if (isExisting) {
                    $('#vendorStatus').removeClass('d-none');
                } else {
                    $('#vendorStatus').addClass('d-none');
                }
            }

            function showAlert(type, message) {
                // Remove existing alerts
                $('.vendor-alert').remove();

                // Create alert
                const alert = $(`
            <div class="alert alert-${type} alert-dismissible fade show mt-2 vendor-alert" role="alert">
                <i class="bi ${type === 'success' ? 'bi-check-circle' : type === 'warning' ? 'bi-exclamation-triangle' : 'bi-info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);

                // Insert after search container
                $('.vendor-search-container').after(alert);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    alert.alert('close');
                }, 5000);
            }

            // Reset form
            $('#resetFormBtn').on('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to clear the entire form?')) {
                    $('#vendorForm')[0].reset();
                    $('#vendor_search').val('');
                    $('#searchResults').hide();
                    resetVendorSelection();
                    $('#invoice_date').val(new Date().toISOString().split('T')[0]);
                    showAlert('info', 'Form cleared successfully.');
                }
            });

            // Set today's date as default
            const today = new Date().toISOString().split('T')[0];
            $('#invoice_date').val(today);

            // Close search results when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.vendor-search-container').length) {
                    $('#searchResults').hide();
                }
            });

            // Form submission validation
            $('#vendorForm').on('submit', function(e) {
                const vendorName = $('#vendor_name').val().trim();

                if (!vendorName) {
                    e.preventDefault();
                    showAlert('danger', 'Vendor name is required');
                    $('#vendor_name').focus();
                    return false;
                }

                // If we have a selected vendor, check for changes
                if (selectedVendorId && originalVendorData) {
                    const currentData = {
                        name: $('#vendor_name').val().trim(),
                        contact: $('#vendor_contact').val().trim(),
                        email: $('#vendor_email').val().trim(),
                        address: $('#vendor_address').val().trim(),
                        gst: $('#gst_number').val().trim(),
                        bank_account: $('#bank_account_no').val().trim(),
                        bank_name: $('#bank_name').val().trim(),
                        ifsc: $('#ifsc_code').val().trim()
                    };

                    // Check for bank changes
                    const bankChanged =
                        currentData.bank_account !== originalVendorData.bank_account ||
                        currentData.bank_name !== originalVendorData.bank_name ||
                        currentData.ifsc !== originalVendorData.ifsc;

                    // Check for non-bank changes
                    const nonBankChanged =
                        currentData.contact !== originalVendorData.contact ||
                        currentData.email !== originalVendorData.email ||
                        currentData.address !== originalVendorData.address ||
                        currentData.gst !== originalVendorData.gst;

                    if (bankChanged) {
                        // Show warning for bank changes
                        if (!confirm('Bank details have been modified. This will create a NEW vendor entry with these bank details. Continue?')) {
                            e.preventDefault();
                            return false;
                        }
                        showAlert('warning', 'Creating new vendor entry with updated bank details.');
                    } else if (nonBankChanged) {
                        // Show info for non-bank changes
                        showAlert('info', 'Updating existing vendor with modified contact information.');
                    }
                }

                return true;
            });

            // Highlight fields when they have data
            $('input, textarea').on('input', function() {
                if ($(this).val().trim() !== '') {
                    $(this).addClass('bg-light');
                } else {
                    $(this).removeClass('bg-light');
                }
            });

            // Clear vendor selection if name is manually edited
            $('#vendor_name').on('input', function() {
                const currentName = $(this).val().trim();
                if (selectedVendorId && currentName !== originalVendorData?.name) {
                    // User changed the vendor name - treat as new vendor
                    resetVendorSelection();
                    showAlert('info', 'Vendor name changed. Creating new vendor entry.');
                }
            });
        });
    </script>
</body>

</html>