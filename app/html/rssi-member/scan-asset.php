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

// Get asset ID from URL or start fresh
$asset_id = $_GET['asset_id'] ?? '';
$scan_mode = isset($_GET['scan']) ? true : false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Asset Verification Scanner</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- HTML5 QR Code Scanner -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <!-- In your <head> section, add this BEFORE Select2 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Add these lines to your <head> section -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        :root {
            --primary: #0d6efd;
            --success: #198754;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        /* body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        } */

        .scanner-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin: 1rem;
        }

        .scanner-header {
            background: var(--primary);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        #reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        #qr-shaded-region {
            border-color: var(--primary) !important;
        }

        .manual-input {
            padding: 1.5rem;
        }

        .btn-scan {
            background: linear-gradient(135deg, var(--primary), #0b5ed7);
            border: none;
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            border-radius: 50px;
            transition: all 0.3s;
        }

        .btn-scan:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.3);
        }

        .verification-card {
            display: none;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .status-active {
            background-color: var(--success);
        }

        .status-inactive {
            background-color: var(--danger);
        }

        .scan-animation {
            position: relative;
            height: 3px;
            background: var(--primary);
            width: 100%;
            animation: scanLine 2s infinite linear;
        }

        @keyframes scanLine {
            0% {
                top: 0;
            }

            50% {
                top: calc(100% - 3px);
            }

            100% {
                top: 0;
            }
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        @media (max-width: 768px) {
            .scanner-container {
                margin: 0.5rem;
                border-radius: 15px;
            }

            .scanner-header {
                padding: 1rem;
            }

            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container-fluid p-0">
                                <div class="row justify-content-center align-items-center m-0">
                                    <!-- <div class="col-12 col-md-8 col-lg-6"> -->
                                    <!-- Main Scanner Container -->
                                    <div class="scanner-container">

                                        <!-- Scanner Header -->
                                        <div class="scanner-header">
                                            <h2 class="mb-3">
                                                <i class="bi bi-qr-code-scan"></i> Asset Verification
                                            </h2>
                                            <p class="mb-0">Scan QR code on asset or enter manually</p>
                                        </div>

                                        <!-- QR Code Scanner -->
                                        <div id="reader" class="<?php echo $scan_mode ? '' : 'd-none'; ?>"></div>

                                        <?php if ($scan_mode): ?>
                                            <div class="scan-animation"></div>
                                        <?php endif; ?>

                                        <!-- Manual Input -->
                                        <div class="manual-input <?php echo $scan_mode ? 'd-none' : ''; ?>">
                                            <div class="mb-3">
                                                <label for="asset_id_input" class="form-label">
                                                    <i class="bi bi-upc-scan"></i> Enter Asset ID
                                                </label>
                                                <input type="text"
                                                    class="form-control form-control-lg"
                                                    id="asset_id_input"
                                                    placeholder="A123456789"
                                                    value="<?php echo htmlspecialchars($asset_id); ?>">
                                                <div class="form-text">Enter the Asset ID printed on the label</div>
                                            </div>

                                            <div class="d-grid gap-2">
                                                <button class="btn btn-scan" onclick="verifyAsset()">
                                                    <i class="bi bi-search"></i> Verify Asset
                                                </button>

                                                <button class="btn btn-outline-primary" onclick="toggleScanner()">
                                                    <i class="bi bi-camera"></i> Switch to Scanner
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Loading Spinner -->
                                        <div class="loading-spinner" id="loadingSpinner">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2">Fetching asset details...</p>
                                        </div>

                                        <!-- Verification Results (Hidden Initially) -->
                                        <div class="verification-card p-4" id="verificationCard">
                                            <!-- Will be populated by JavaScript -->
                                        </div>

                                    </div>

                                    <!-- Quick Stats -->
                                    <!-- <div class="text-center text-white mt-3">
                                            <small>
                                                <i class="bi bi-person-circle"></i>
                                                Logged in as: <?php echo htmlspecialchars($fullname); ?>
                                            </small>
                                        </div> -->
                                    <!-- </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- Verification Form Modal -->
    <div class="modal fade" id="verificationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submit Verification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="verificationForm" method="POST" action="submit_verification.php">
                    <div class="modal-body" id="verificationFormContent">
                        <!-- Will be populated by JavaScript -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Verification</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Submission Progress Modal -->
    <div class="modal fade" id="submissionModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Submitting Verification</h5>
                </div>
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 id="submissionStatus">Processing your submission...</h5>
                    <p class="text-muted" id="submissionMessage">
                        Please do not close or refresh the page.
                    </p>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                            role="progressbar" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="mb-3">Submission Successful!</h4>
                    <p id="successMessage" class="mb-4">Your verification has been recorded.</p>
                    <button class="btn btn-success btn-lg w-100" onclick="location.reload()">
                        <i class="bi bi-arrow-repeat"></i> Scan Next Asset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script src="../assets_new/js/image-compressor-100kb.js"></script>

    <script>
        let currentAsset = null;
        let scanner = null;
        const scanMode = <?php echo $scan_mode ? 'true' : 'false'; ?>;

        // Initialize QR Scanner if in scan mode
        if (scanMode) {
            initializeScanner();
        }

        function initializeScanner() {
            scanner = new Html5QrcodeScanner("reader", {
                fps: 10,
                qrbox: 250,
                aspectRatio: 1.0,
                showTorchButtonIfSupported: true
            });

            scanner.render(onScanSuccess, onScanFailure);
        }

        function onScanSuccess(decodedText) {
            // Stop scanner
            if (scanner) {
                scanner.clear();
            }

            // Show loading
            document.querySelector('.manual-input').classList.add('d-none');
            document.getElementById('reader').classList.add('d-none');
            document.getElementById('loadingSpinner').style.display = 'block';

            // Fetch asset details
            fetchAssetDetails(decodedText);
        }

        function onScanFailure(error) {
            // Handle scan failure
            console.warn(`QR scan failed: ${error}`);
        }

        function toggleScanner() {
            if (scanner) {
                scanner.clear();
                document.getElementById('reader').classList.add('d-none');
                document.querySelector('.manual-input').classList.remove('d-none');
                scanner = null;
            } else {
                window.location.href = 'scan-asset.php?scan=true';
            }
        }

        function verifyAsset() {
            const assetId = document.getElementById('asset_id_input').value.trim();
            if (!assetId) {
                alert('Please enter an Asset ID');
                return;
            }

            // Show loading
            document.querySelector('.manual-input').classList.add('d-none');
            document.getElementById('loadingSpinner').style.display = 'block';

            fetchAssetDetails(assetId);
        }

        function fetchAssetDetails(assetId) {
            fetch(`fetch_asset.php?asset_id=${encodeURIComponent(assetId)}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loadingSpinner').style.display = 'none';

                    if (data.error) {
                        alert(data.error);
                        location.reload();
                        return;
                    }

                    currentAsset = data;
                    displayAssetDetails(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to fetch asset details');
                    location.reload();
                });
        }

        function displayAssetDetails(asset) {
            const card = document.getElementById('verificationCard');

            // Determine status color
            const statusColor = asset.asset_status === 'Active' ? 'success' : 'danger';
            const statusIcon = asset.asset_status === 'Active' ? 'check-circle' : 'x-circle';

            card.innerHTML = `
            <div class="alert alert-${statusColor}">
                <h4 class="alert-heading">
                    <i class="bi bi-${statusIcon}"></i> ${asset.asset_status}
                </h4>
                Asset Found: <strong>${asset.itemname}</strong>
            </div>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Asset Information</h6>
                            <div class="row">
                                <div class="col-12 col-lg-7">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Asset ID:</strong></td>
                                            <td class="text-break">${asset.itemid}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Name:</strong></td>
                                            <td>${asset.itemname}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Type:</strong></td>
                                            <td>${asset.itemtype}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Category:</strong></td>
                                            <td>${asset.asset_category}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Quantity:</strong></td>
                                            <td id="currentQuantity">${asset.quantity}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-12 col-lg-5 mt-3 mt-lg-0">
                                    <div class="asset-photo-preview">
                                        <div class="card border">
                                            <div class="card-body text-center p-2">
                                                <h6 class="card-title text-muted mb-2">Asset Photo</h6>
                                                ${asset.processed_photo_url ? 
                                                    `<a href="${asset.asset_photo || asset.processed_photo_url}" target="_blank" title="View full size">
                                                        <img src="${asset.processed_photo_url}" 
                                                             class="img-fluid rounded" 
                                                             style="max-height: 120px; width: auto;"
                                                             alt="Asset Photo"
                                                             onerror="this.src='https://via.placeholder.com/150x120?text=Photo+Not+Found'">
                                                     </a>
                                                     <small class="d-block mt-1 text-muted">Click to view</small>` 
                                                    : 
                                                    `<div class="text-muted py-3">
                                                        <i class="bi bi-image" style="font-size: 2rem;"></i>
                                                        <p class="mt-2 mb-0 small">No photo available</p>
                                                     </div>`
                                                }
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Assignment</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Location:</strong></td>
                                    <td id="currentLocation">${asset.current_location || 'Not Assigned'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tagged To:</strong></td>
                                    <td id="currentTaggedTo">${asset.tagged_to_name || 'Not Assigned'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Associate ID:</strong></td>
                                    <td>${asset.taggedto || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Issued By:</strong></td>
                                    <td>${asset.issued_by_name || 'N/A'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Last Verified:</strong></td>
                                    <td>${asset.last_verified_on || 'Never'}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h6>Verification Actions</h6>
                <div class="d-grid gap-2 d-md-flex">
                    <button class="btn btn-success btn-lg flex-fill" onclick="prepareVerification('verified')">
                        <i class="bi bi-check-circle"></i> Verified Correct
                    </button>
                    <button class="btn btn-warning btn-lg flex-fill" onclick="prepareVerification('update')">
                        <i class="bi bi-pencil-square"></i> Update Details
                    </button>
                    <button class="btn btn-danger btn-lg flex-fill" onclick="prepareVerification('discrepancy')">
                        <i class="bi bi-exclamation-triangle"></i> Report Issue
                    </button>
                </div>
            </div>
            
            <div class="mt-3 text-center">
                <button class="btn btn-outline-secondary" onclick="location.reload()">
                    <i class="bi bi-arrow-repeat"></i> Scan Another
                </button>
            </div>
        `;

            card.style.display = 'block';
        }

        function prepareVerification(actionType) {
            if (!currentAsset) return;

            const modalContent = document.getElementById('verificationFormContent');
            let formContent = '';

            formContent += `<input type="hidden" name="asset_id" value="${currentAsset.itemid}">`;
            formContent += `<input type="hidden" name="verified_by" value="<?php echo $associatenumber; ?>">`;
            formContent += `<input type="hidden" name="action_type" value="${actionType}">`;

            if (actionType === 'verified') {
                formContent += `
                <div class="alert alert-success">
                    <h6><i class="bi bi-check-circle"></i> Confirm Verification</h6>
                    <p>All details are correct. This will record your verification.</p>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="confirmVerified" required>
                        <label class="form-check-label" for="confirmVerified">
                            I confirm that all asset details are correct
                        </label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Additional Remarks (Optional)</label>
                    <textarea name="remarks" class="form-control" rows="2" 
                              placeholder="Any notes about this verification..."></textarea>
                </div>
            `;
            } else if (actionType === 'update') {
                // Check if files exist
                const hasPhoto = currentAsset.asset_photo && currentAsset.asset_photo.trim() !== '';
                const hasBill = currentAsset.purchase_bill && currentAsset.purchase_bill.trim() !== '';

                formContent += `
                <div class="alert alert-warning">
                    <h6><i class="bi bi-pencil-square"></i> Update Asset Details</h6>
                    <p>Update quantity or upload missing files.</p>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-body">
                                <h6 class="card-title">Current Values</h6>
                                <p><strong>Quantity:</strong> ${currentAsset.quantity}</p>
                                <p><strong>Photo:</strong> ${hasPhoto ? 
                                    '<span class="text-success"><i class="bi bi-check-circle"></i> Available</span>' : 
                                    '<span class="text-danger"><i class="bi bi-x-circle"></i> Not available</span>'
                                }</p>
                                <p><strong>Bill:</strong> ${hasBill ? 
                                    '<span class="text-success"><i class="bi bi-check-circle"></i> Available</span>' : 
                                    '<span class="text-danger"><i class="bi bi-x-circle"></i> Not available</span>'
                                }</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-body">
                                <h6 class="card-title">Update Details</h6>
                                <div class="mb-3">
                                    <label class="form-label">New Quantity</label>
                                    <input type="number" name="new_quantity" class="form-control" 
                                           value="${currentAsset.quantity}" min="1"
                                           id="newQuantityInput">
                                </div>                       

                                <div class="mb-3">
                                    <label class="form-label">Asset Photo</label>
                                    ${hasPhoto ? 
                                        `<div class="form-control bg-light">
                                            <span class="text-muted"><i class="bi bi-check-circle"></i> Photo already available</span>
                                            <input type="hidden" name="asset_photo">
                                        </div>` : 
                                        `<input type="file" name="asset_photo" class="form-control" accept="image/*" id="photoInput" onchange="compressImageBeforeUpload(this)">`
                                    }
                                    ${!hasPhoto ? '<div class="form-text">Upload missing asset photo</div>' : ''}
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Purchase Bill</label>
                                    ${hasBill ? 
                                        `<div class="form-control bg-light">
                                            <span class="text-muted"><i class="bi bi-check-circle"></i> Bill already available</span>
                                            <input type="hidden" name="verification_bill">
                                        </div>` : 
                                        `<input type="file" name="verification_bill" class="form-control" accept=".pdf,image/*" id="billInput" onchange="compressImageBeforeUpload(this)">`
                                    }
                                    ${!hasBill ? '<div class="form-text">Upload missing purchase bill</div>' : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label">Remarks <span class="text-danger">*</span></label>
                        <textarea name="update_reason" class="form-control" rows="3" 
                                  placeholder="Explain the updates..." required></textarea>
                        <div class="form-text">Please describe why you're making these updates</div>
                    </div>
                </div>
            `;
            } else if (actionType === 'discrepancy') {
                formContent += `
                <div class="alert alert-danger">
                    <h6><i class="bi bi-exclamation-triangle"></i> Report Discrepancy</h6>
                    <p>This report will be reviewed by admin.</p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Issue Type <span class="text-danger">*</span></label>
                    <select name="issue_type" class="form-select" required>
                        <option value="">Select issue type</option>
                        <option value="missing">Asset Missing</option>
                        <option value="damaged">Damaged/Broken</option>
                        <option value="incorrect_info">Incorrect Information on Asset</option>
                        <option value="wrong_location">Asset in Wrong Location</option>
                        <option value="unauthorized_use">Unauthorized Use</option>
                        <option value="other">Other Issue</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Upload Evidence Photo (Optional)</label>
                    <input type="file" name="verification_photo" class="form-control" accept="image/*" onchange="compressImageBeforeUpload(this)">
                    <div class="form-text">Upload photo evidence of the issue</div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Detailed Description <span class="text-danger">*</span></label>
                    <textarea name="issue_description" class="form-control" rows="4" 
                              placeholder="Describe the issue in detail..." required></textarea>
                    <div class="form-text">Include location, condition, and any relevant information</div>
                </div>
            `;
            }

            modalContent.innerHTML = formContent;

            const modal = new bootstrap.Modal(document.getElementById('verificationModal'));
            modal.show();
        }

        // Form submission handler
        document.getElementById('verificationForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const actionType = document.querySelector('input[name="action_type"]')?.value;

            // Special validation only for update action
            if (actionType === 'update') {
                const quantityInput = document.querySelector('input[name="new_quantity"]');
                const photoInput = document.getElementById('photoInput');
                const billInput = document.getElementById('billInput');

                const originalQuantity = parseFloat(currentAsset?.quantity) || 0;
                const newQuantity = parseFloat(quantityInput?.value) || 0;

                // Check if file inputs exist (they might be hidden if files already exist)
                const hasPhotoFile = photoInput && photoInput.files && photoInput.files.length > 0;
                const hasBillFile = billInput && billInput.files && billInput.files.length > 0;

                // Check if any changes were made
                const quantityChanged = newQuantity !== originalQuantity;
                const filesUploaded = hasPhotoFile || hasBillFile;

                if (!quantityChanged && !filesUploaded) {
                    alert('No changes detected. Please update quantity or upload missing files to submit.');
                    return false;
                }
            }

            // Disable all buttons in the modal
            const modal = document.getElementById('verificationModal');
            const buttons = modal.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);

            // Close verification modal
            const bsModal = bootstrap.Modal.getInstance(modal);
            bsModal.hide();

            // Show submission progress modal
            const submissionModal = new bootstrap.Modal(document.getElementById('submissionModal'));
            submissionModal.show();

            // Prevent back button
            history.pushState(null, null, location.href);
            window.onpopstate = function() {
                history.go(1);
            };

            // Submit the form via AJAX
            const formData = new FormData(this);

            fetch('submit_verification.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            console.error("Non-JSON response:", text);
                            throw new Error('Server returned non-JSON response');
                        });
                    }
                    return response.json();
                })
                .then(result => {
                    document.getElementById('submissionStatus').textContent = 'Submission Complete';
                    document.getElementById('submissionMessage').textContent = 'Finalizing...';

                    setTimeout(() => {
                        submissionModal.hide();

                        if (result.success) {
                            const successMessage = document.getElementById('successMessage');
                            successMessage.textContent = result.message || 'Verification has been submitted successfully.';

                            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                            successModal.show();
                        } else {
                            alert('Error: ' + result.message);
                            location.reload();
                        }
                    }, 1000);
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    submissionModal.hide();
                    alert('Submission failed: ' + error.message);
                    location.reload();
                });
        });

        // Auto-focus on input on page load
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('asset_id_input');
            if (input && input.value === '') {
                input.focus();
            }
        });
    </script>
</body>

</html>