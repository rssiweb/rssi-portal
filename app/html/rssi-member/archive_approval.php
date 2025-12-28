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

date_default_timezone_set('Asia/Kolkata');
include("../../util/email.php");

if ($role == 'Admin') {
    $user_id = isset($_GET['user_id']) ? strtoupper(trim($_GET['user_id'])) : '';

    // Base query:
    // For all file_name values → latest upload only
    // For 'additional_certificate' → show all certificate_name entries
    $baseQuery = "
        SELECT 
            a.remarks AS aremarks, 
            a.*, 
            rm.fullname, 
            rm.associatenumber
        FROM archive a
        JOIN rssimyaccount_members rm
            ON (a.uploaded_for = rm.associatenumber OR a.uploaded_for = rm.applicationnumber)
        WHERE 1=1
    ";

    // Apply filter by user if given
    if (!empty($user_id)) {
        $baseQuery .= " AND (rm.associatenumber = '$user_id' OR rm.applicationnumber = '$user_id')";
    }

    // If no filter selected → show only Pending verification
    if (empty($user_id)) {
        $baseQuery .= " AND a.verification_status IS NULL";
    }

    // Include only latest uploads except for 'additional_certificate'
    $baseQuery .= "
        AND (
            a.file_name = 'additional_certificate'
            OR (a.file_name != 'additional_certificate' AND a.uploaded_on = (
                SELECT MAX(uploaded_on)
                FROM archive sub
                WHERE sub.uploaded_for = a.uploaded_for
                AND sub.file_name = a.file_name
            ))
        )
        ORDER BY a.doc_id DESC
    ";

    $result = pg_query($con, $baseQuery);
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>

<!DOCTYPE html>
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
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Document Approval</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

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

    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <!-- In your head section -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <style>
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }

        /* Row selection styles */
        .clickable-row {
            cursor: pointer;
        }

        .clickable-row:hover {
            background-color: #f8f9fa !important;
        }

        .selected-row {
            background-color: #e3f2fd !important;
            border-left: 4px solid #2196F3;
        }

        .bulk-actions {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 15px;
            border-top: 1px solid #dee2e6;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 100;
        }

        .selection-count {
            font-weight: bold;
            color: #2196F3;
            background: #e3f2fd;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 8px;
        }

        .bulk-review-btn {
            display: flex;
            align-items: center;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Document Approval</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Document Approval</li>
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
                            <div style="display: inline-block; width:100%; text-align:right;">Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                            </div>
                            <?php if ($role == 'Admin' && $filterstatus == 'Active') { ?>

                                <form action="" method="GET" style="display: flex; align-items: center;">
                                    <div class="col-md-3 col-lg-2 pe-4">
                                        <div class="form-group">
                                            <select class="form-select" id="user_id" name="user_id" required>
                                                <?php if (!empty($user_id)): ?>
                                                    <option value="<?= $user_id ?>" selected><?= $user_id ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                            <i class="bi bi-search"></i>&nbsp;Search
                                        </button>
                                    </div>
                                </form>
                            <?php } ?>
                            <br>

                            <!-- Bulk Actions Panel -->
                            <div class="bulk-actions" id="bulkActionsPanel" style="display: none;">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <span id="selectedCountText">0</span> document(s) selected for review
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button type="button" class="btn btn-primary btn-sm bulk-review-btn" onclick="showBulkReviewModal()">
                                            <i class="bi bi-check2-all"></i> Bulk Review
                                            <span class="selection-count" id="selectedCount">0</span>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm ms-2" onclick="clearSelection()">
                                            <i class="bi bi-x"></i> Clear
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover" id="table-id">
                                    <thead>
                                        <tr>
                                            <th width="50px">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th>#</th>
                                            <th>Associate number</th>
                                            <th>File Name</th>
                                            <th>File Path</th>
                                            <th>Uploaded By</th>
                                            <th>Uploaded on</th>
                                            <th>Verification Status</th>
                                            <th>Field Status</th>
                                            <th>Reviewed by</th>
                                            <th>Reviewed on</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($resultArr)) : ?>
                                            <?php foreach ($resultArr as $array) : ?>
                                                <tr class="clickable-row" data-doc-id="<?= isset($array['doc_id']) ? htmlspecialchars($array['doc_id']) : '' ?>">
                                                    <td>
                                                        <input type="checkbox" class="row-checkbox form-check-input" value="<?= isset($array['doc_id']) ? htmlspecialchars($array['doc_id']) : '' ?>">
                                                    </td>
                                                    <td><?= isset($array['doc_id']) ? htmlspecialchars($array['doc_id']) : '' ?></td>
                                                    <td>
                                                        <?=
                                                        (isset($array['fullname']) ? htmlspecialchars($array['fullname']) : '-')
                                                            . ' ('
                                                            . (isset($array['associatenumber']) ? htmlspecialchars($array['associatenumber']) : '-')
                                                            . ')'
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if (isset($array['file_name']) && ($array['file_name'] === 'additional_certificate' || $array['file_name'] === 'additional_doc')) {
                                                            echo isset($array['certificate_name']) && !empty($array['certificate_name'])
                                                                ? htmlspecialchars($array['certificate_name'])
                                                                : 'Unnamed Certificate';
                                                        } else {
                                                            echo isset($array['file_name']) && !empty($array['file_name'])
                                                                ? htmlspecialchars($array['file_name'])
                                                                : '-';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($array['file_path'])): ?>
                                                            <a href="<?= htmlspecialchars($array['file_path']) ?>" target="_blank">Document</a>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $uploadedBy = isset($array['uploaded_by']) ? trim($array['uploaded_by']) : '';
                                                        $associateNumber = isset($array['associatenumber']) ? trim($array['associatenumber']) : '';
                                                        $applicationNumber = isset($array['applicationnumber']) ? trim($array['applicationnumber']) : '';

                                                        if ($uploadedBy === $associateNumber || $uploadedBy === $applicationNumber) {
                                                            echo 'Self';
                                                        } elseif (!empty($uploadedBy)) {
                                                            echo htmlspecialchars($uploadedBy);
                                                        } else {
                                                            echo '-';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?= !empty($array['uploaded_on']) ? date("d/m/Y g:i a", strtotime($array['uploaded_on'])) : '-' ?></td>
                                                    <td
                                                        title="<?= isset($array['aremarks']) && !empty($array['aremarks'])
                                                                    ? htmlspecialchars($array['aremarks'])
                                                                    : 'No remarks available' ?>">
                                                        <?= isset($array['verification_status']) && !empty($array['verification_status'])
                                                            ? htmlspecialchars($array['verification_status'])
                                                            : '-' ?>
                                                    </td>
                                                    <td><?= isset($array['field_status']) ? htmlspecialchars($array['field_status']) : '-' ?></td>
                                                    <td><?= isset($array['reviewed_by']) ? htmlspecialchars($array['reviewed_by']) : '-' ?></td>
                                                    <td><?= !empty($array['reviewed_on']) ? date("d/m/Y g:i a", strtotime($array['reviewed_on'])) : '-' ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php elseif (empty($user_id)) : ?>
                                            <tr>
                                                <td colspan="12" class="text-center text-muted">
                                                    No pending verifications found. Please select a filter to view specific data.
                                                </td>
                                            </tr>
                                        <?php else : ?>
                                            <tr>
                                                <td colspan="12" class="text-center text-muted">
                                                    No records found for the selected filters.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!--------------- BULK REVIEW MODAL --------------->
                            <div class="modal fade" id="bulkReviewModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Bulk Review</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="bulkReviewForm" action="#" method="POST">
                                                <input type="hidden" name="form-type" value="bulk-archive-approval" readonly>
                                                <input type="hidden" name="reviewer_id" value="<?php echo $associatenumber ?>" readonly>
                                                <input type="hidden" name="selected_docs" id="selected_docs" readonly>

                                                <div class="mb-3">
                                                    <label for="bulk_action_type" class="form-label">Action</label>
                                                    <select name="bulk_action_type" id="bulk_action_type" class="form-select" required>
                                                        <option value="" disabled selected hidden>Select Action</option>
                                                        <option value="approve">Approve</option>
                                                        <option value="reject">Reject</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="bulk_field_status" class="form-label">Field Status</label>
                                                    <select name="bulk_field_status" id="bulk_field_status" class="form-select" required>
                                                        <option value="disabled">Disabled</option>
                                                        <option value="null" selected>Null</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="bulk_reviewer_remarks" class="form-label">Reviewer Remarks</label>
                                                    <textarea name="bulk_reviewer_remarks" id="bulk_reviewer_remarks" class="form-control" placeholder="Enter remarks for bulk action" required></textarea>
                                                    <small class="form-text text-muted">These remarks will be applied to all selected documents</small>
                                                </div>

                                                <div class="alert alert-info">
                                                    <i class="bi bi-info-circle"></i> This action will affect <span id="modalSelectedCount" class="fw-bold">0</span> document(s).
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancelButton">Cancel</button>
                                            <button type="button" class="btn btn-primary" id="confirmButton" onclick="submitBulkAction()">
                                                <span class="button-text">Confirm</span>
                                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                            </button>
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

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  <script src="../assets_new/js/text-refiner.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTables if resultArr is not empty
            <?php if (!empty($resultArr)) : ?>
                $('#table-id').DataTable({
                    paging: false,
                    "order": [],
                    "columnDefs": [{
                            "orderable": false,
                            "targets": 0
                        } // Disable sorting for checkbox column
                    ]
                });
            <?php endif; ?>

            // Row selection functionality
            $('.clickable-row').click(function(e) {
                // Don't trigger selection if checkbox was clicked
                if ($(e.target).is('input[type="checkbox"]') || $(e.target).is('a')) {
                    return;
                }

                const checkbox = $(this).find('.row-checkbox');
                checkbox.prop('checked', !checkbox.prop('checked'));
                updateRowSelection(this);
                updateBulkActionsPanel();
            });

            // Checkbox change handler
            $('.row-checkbox').change(function() {
                updateRowSelection($(this).closest('tr'));
                updateBulkActionsPanel();
            });

            // Select all functionality
            $('#selectAll').change(function() {
                const isChecked = $(this).prop('checked');
                $('.row-checkbox').prop('checked', isChecked);
                $('.clickable-row').each(function() {
                    updateRowSelection(this);
                });
                updateBulkActionsPanel();
            });
        });

        function updateRowSelection(row) {
            const $row = $(row);
            const isChecked = $row.find('.row-checkbox').prop('checked');

            if (isChecked) {
                $row.addClass('selected-row');
            } else {
                $row.removeClass('selected-row');
            }
        }

        function updateBulkActionsPanel() {
            const selectedCount = $('.row-checkbox:checked').length;
            const $bulkPanel = $('#bulkActionsPanel');
            const $selectedCount = $('#selectedCount');
            const $selectedCountText = $('#selectedCountText');

            $selectedCount.text(selectedCount);
            $selectedCountText.text(selectedCount);

            if (selectedCount > 0) {
                $bulkPanel.show();
            } else {
                $bulkPanel.hide();
            }

            // Update select all checkbox
            const totalRows = $('.row-checkbox').length;
            const allChecked = $('.row-checkbox:checked').length === totalRows;
            $('#selectAll').prop('checked', allChecked);
        }

        function clearSelection() {
            $('.row-checkbox').prop('checked', false);
            $('.clickable-row').removeClass('selected-row');
            updateBulkActionsPanel();
        }

        function showBulkReviewModal() {
            const selectedDocs = getSelectedDocIds();
            if (selectedDocs.length === 0) {
                alert('Please select at least one document.');
                return;
            }

            // Reset form
            $('#bulk_action_type').val('');
            $('#bulk_reviewer_remarks').val('');
            $('#modalSelectedCount').text(selectedDocs.length);
            $('#selected_docs').val(selectedDocs.join(','));

            // Reset confirm button to normal state
            resetConfirmButton();

            new bootstrap.Modal(document.getElementById('bulkReviewModal')).show();
        }

        function setSubmittingState() {
            const confirmButton = document.getElementById('confirmButton');
            const cancelButton = document.getElementById('cancelButton');
            const buttonText = confirmButton.querySelector('.button-text');
            const spinner = confirmButton.querySelector('.spinner-border');

            // Disable button and show spinner
            confirmButton.disabled = true;
            buttonText.textContent = 'Submitting...';
            spinner.classList.remove('d-none');

            // Also disable cancel button during submission
            cancelButton.disabled = true;
        }

        function resetConfirmButton() {
            const confirmButton = document.getElementById('confirmButton');
            const cancelButton = document.getElementById('cancelButton');
            const buttonText = confirmButton.querySelector('.button-text');
            const spinner = confirmButton.querySelector('.spinner-border');

            // Enable button and hide spinner
            confirmButton.disabled = false;
            buttonText.textContent = 'Confirm';
            spinner.classList.add('d-none');

            // Enable cancel button
            cancelButton.disabled = false;
        }

        function getSelectedDocIds() {
            const selectedDocs = [];
            $('.row-checkbox:checked').each(function() {
                selectedDocs.push($(this).val());
            });
            return selectedDocs;
        }

        async function submitBulkAction() {
            const actionType = $('#bulk_action_type').val();
            const remarks = $('#bulk_reviewer_remarks').val();

            if (!actionType) {
                alert('Please select an action (Approve or Reject).');
                return;
            }

            if (!remarks.trim()) {
                alert('Please enter remarks for the bulk action.');
                return;
            }

            // Set submitting state
            setSubmittingState();

            try {
                const form = document.getElementById('bulkReviewForm');
                const formData = new FormData(form);

                const response = await fetch('payment-api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Close modal first
                    bootstrap.Modal.getInstance(document.getElementById('bulkReviewModal')).hide();
                    // Show success message
                    alert(data.message);
                    // Reload page to see changes
                    location.reload();
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error: ' + error.message);
                // Reset button state on error
                resetConfirmButton();
            }
        }
    </script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for associate numbers
            $('#user_id').select2({
                ajax: {
                    url: 'fetch_associates.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Select associate(s)',
                allowClear: true,
                width: '100%',
                theme: 'bootstrap-5'
            });
        });
    </script>
</body>

</html>