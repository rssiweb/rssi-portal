<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

// Start output buffering to prevent header issues
ob_start();

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle bulk approval/rejection
    if (isset($_POST['bulk_action'], $_POST['selected_ids'], $_POST['bulk_remarks'])) {
        $selected_ids = explode(',', $_POST['selected_ids']);
        $bulk_action = $_POST['bulk_action'];
        $bulk_remarks = trim($_POST['bulk_remarks']);
        $reviewer_id = $associatenumber;

        if (!empty($selected_ids) && !empty($bulk_action)) {
            // Begin transaction
            pg_query($con, "BEGIN");

            try {
                // Process each selected ID
                foreach ($selected_ids as $workflow_id) {
                    $workflow_id = pg_escape_string($con, trim($workflow_id));

                    // First get the basic workflow details
                    $details_query = "SELECT 
                        w.associatenumber, 
                        w.fieldname, 
                        w.submitted_value, 
                        m.fullname, 
                        m.email
                    FROM hrms_workflow w
                    JOIN rssimyaccount_members m ON w.associatenumber = m.associatenumber
                    WHERE w.workflow_id = '$workflow_id'";

                    $details_result = pg_query($con, $details_query);
                    $details_row = pg_fetch_assoc($details_result);

                    if ($details_row) {
                        $associatenumber = $details_row['associatenumber'];
                        $fieldname = $details_row['fieldname'];
                        $submitted_value = $details_row['submitted_value'];
                        $requestedby_name = $details_row['fullname'];
                        $requestedby_email = $details_row['email'];
                        $requested_on = date('d/m/Y h:i A');

                        // Now get the current value using the proper fieldname
                        $currentValueQuery = "SELECT $fieldname AS current_value 
                                            FROM rssimyaccount_members 
                                            WHERE associatenumber = '$associatenumber'";
                        $currentValueResult = pg_query($con, $currentValueQuery);
                        $currentValueRow = pg_fetch_assoc($currentValueResult);
                        $current_value = $currentValueRow['current_value'] ?? null;

                        // Update workflow status
                        $update_query = "UPDATE hrms_workflow
                            SET reviewer_status = '$bulk_action', 
                                reviewer_id = '$reviewer_id', 
                                reviewed_on = NOW(),
                                remarks = '$bulk_remarks'
                            WHERE workflow_id = '$workflow_id'";

                        $update_result = pg_query($con, $update_query);

                        if ($bulk_action === 'Approved' && $update_result) {
                            // Update member record if approved
                            $fieldname_escaped = pg_escape_string($con, $fieldname);
                            $member_update = "UPDATE rssimyaccount_members 
                                SET $fieldname_escaped = '$submitted_value' 
                                WHERE associatenumber = '$associatenumber'";
                            pg_query($con, $member_update);
                        }

                        // Send email notification
                        if (!empty($requestedby_email)) {
                            sendEmail("hrms_review", [
                                "requested_by_name" => $requestedby_name,
                                "requested_on" => $requested_on,
                                "reviewer_status" => $bulk_action,
                                "fieldname" => $fieldname,
                                "oldvalue" => $current_value,
                                "newvalue" => $submitted_value,
                                "hide_approve" => 'style="display: none;"',
                                "remarks" => $bulk_remarks
                            ], $requestedby_email, false);
                        }
                    }
                }

                // Commit transaction
                pg_query($con, "COMMIT");
                echo json_encode(['status' => 'success', 'message' => 'Bulk action completed successfully']);
                exit;
            } catch (Exception $e) {
                // Rollback on error
                pg_query($con, "ROLLBACK");
                echo json_encode(['status' => 'error', 'message' => 'Error processing bulk action: ' . $e->getMessage()]);
                exit;
            }
        }
    }
}

// Query to get only the latest pending workflow record for each associate and field
$query = "
    WITH latest_submissions AS (
        SELECT 
            w.associatenumber,
            w.fieldname,
            MAX(w.workflow_id) as latest_workflow_id
        FROM hrms_workflow w
        GROUP BY w.associatenumber, w.fieldname
    )
    SELECT 
        w.workflow_id, 
        w.associatenumber, 
        w.fieldname, 
        w.submitted_value, 
        w.submission_timestamp, 
        w.reviewer_status,
        m.fullname, 
        m.phone, 
        m.email,
        w.remarks
    FROM hrms_workflow w
    JOIN rssimyaccount_members m ON w.associatenumber = m.associatenumber
    JOIN latest_submissions ls ON 
        w.associatenumber = ls.associatenumber AND 
        w.fieldname = ls.fieldname AND
        w.workflow_id = ls.latest_workflow_id
    WHERE w.reviewer_status = 'Pending'
    ORDER BY w.submission_timestamp DESC
";

$result = pg_query($con, $query);
$data = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $fieldname = pg_escape_string($con, $row['fieldname']);
        $currentValueQuery = "SELECT $fieldname AS current_value FROM rssimyaccount_members WHERE associatenumber = '{$row['associatenumber']}'";
        $currentValueResult = pg_query($con, $currentValueQuery);
        $currentValueRow = pg_fetch_assoc($currentValueResult);
        $row['current_value'] = $currentValueRow['current_value'] ?? '<em>Not Available</em>';
        $data[] = $row;
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

    <title>HRMS Worklist</title>

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
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <style>
        tbody tr {
            cursor: pointer;
        }

        tbody tr:hover {
            background-color: #f5f5f5;
        }

        .form-check-input {
            margin-left: 0;
        }

        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            border-radius: 0.25rem;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>HRMS Worklist</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Worklist</a></li>
                    <li class="breadcrumb-item active">HRMS Worklist</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <?php if ($role == 'Admin') : ?>
                                <div class="row mb-3">
                                    <div class="col-md-12 text-end">
                                        <button id="bulk-review-button" class="btn btn-primary" disabled>Bulk Review (0)</button>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table" id="worklist-table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Workflow ID</th>
                                            <th>Submitted on</th>
                                            <th>Associate</th>
                                            <th>Full Name</th>
                                            <th>Field Name</th>
                                            <th>Current Value</th>
                                            <th>Submitted Value</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $row) : ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox"
                                                        class="form-check-input"
                                                        name="selected_ids[]"
                                                        value="<?= htmlspecialchars($row['workflow_id']) ?>"
                                                        <?= ($row['reviewer_status'] === 'Approved' || $row['reviewer_status'] === 'Rejected') ? 'disabled' : ''; ?>>
                                                </td>
                                                <td><?= htmlspecialchars($row['workflow_id']) ?></td>
                                                <td><?= date('d/m/Y h:i a', strtotime($row['submission_timestamp'])) ?></td>
                                                <td><?= htmlspecialchars($row['associatenumber']) ?></td>
                                                <td><?= htmlspecialchars($row['fullname']) ?></td>
                                                <td><?= htmlspecialchars($row['fieldname']) ?></td>
                                                <td><?= htmlspecialchars($row['current_value']) ?></td>
                                                <td><?= htmlspecialchars($row['submitted_value']) ?></td>
                                                <td><?= htmlspecialchars($row['reviewer_status']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Bulk Review Modal -->
    <div class="modal fade" id="bulkReviewModal" tabindex="-1" aria-labelledby="bulkReviewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkReviewModalLabel">Bulk Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bulk-review-form">
                        <input type="hidden" name="selected_ids" id="selected-ids">
                        <div class="mb-3">
                            <label for="bulk-action" class="form-label">Action</label>
                            <select name="bulk_action" id="bulk-action" class="form-select" required>
                                <option value="">Select Action</option>
                                <option value="Approved">Approve</option>
                                <option value="Rejected">Reject</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="bulk-remarks" class="form-label">Remarks</label>
                            <textarea name="bulk_remarks" id="bulk-remarks" class="form-control" placeholder="Enter remarks..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="confirm-bulk-action">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#worklist-table').DataTable({
                "order": [],
                "columnDefs": [{
                    "orderable": false,
                    //"targets": [0, 9]
                }]
            });

            // Make rows clickable (except for checkboxes and links)
            $('#worklist-table tbody').on('click', 'tr', function(e) {
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'A' && e.target.tagName !== 'I') {
                    const checkbox = $(this).find('td:first-child .form-check-input');
                    if (checkbox.length && !checkbox.prop('disabled')) {
                        checkbox.prop('checked', !checkbox.prop('checked'));
                        updateBulkReviewButton();
                    }
                }
            });

            // Update bulk review button
            function updateBulkReviewButton() {
                const selectedCount = $('#worklist-table tbody .form-check-input:checked').length;
                const bulkReviewButton = $('#bulk-review-button');
                bulkReviewButton.text(`Bulk Review (${selectedCount})`);
                bulkReviewButton.prop('disabled', selectedCount === 0);
            }

            // Initialize bulk review modal
            $('#bulk-review-button').click(function() {
                const selectedIds = [];
                $('#worklist-table tbody .form-check-input:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length > 0) {
                    $('#selected-ids').val(selectedIds.join(','));
                    const bulkReviewModal = new bootstrap.Modal(document.getElementById('bulkReviewModal'));
                    bulkReviewModal.show();
                } else {
                    alert('Please select at least one request to proceed.');
                }
            });

            // Handle bulk action submission
            $('#confirm-bulk-action').click(function() {
                const selectedIds = $('#selected-ids').val();
                const bulkAction = $('#bulk-action').val();
                const bulkRemarks = $('#bulk-remarks').val();

                if (!bulkAction) {
                    alert('Please select an action');
                    return;
                }

                $.ajax({
                    url: 'hrms_worklist.php',
                    method: 'POST',
                    data: {
                        bulk_action: bulkAction,
                        selected_ids: selectedIds,
                        bulk_remarks: bulkRemarks
                    },
                    success: function(response) {
                        const result = JSON.parse(response);
                        alert(result.message);
                        if (result.status === 'success') {
                            window.location.reload();
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("An error occurred: " + error);
                    }
                });
            });
        });
    </script>
</body>

</html>