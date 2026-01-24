<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid") || $role != 'Admin') {
    header("Location: index.php");
    exit;
}

// Fetch pending change requests
$query = "SELECT v.*, 
          g.itemname, g.itemtype,
          verified_by_user.fullname as verified_by_name
          FROM gps_verifications v
          JOIN gps g ON v.asset_id = g.itemid
          LEFT JOIN rssimyaccount_members verified_by_user ON v.verified_by = verified_by_user.associatenumber
          WHERE v.admin_review_status = 'pending' AND (v.verification_status = 'pending_update' OR v.verification_status LIKE 'discrepancy_%')
          ORDER BY v.verification_date DESC";

$result = pg_query($con, $query);
$resultArr = pg_fetch_all($result);
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

        .badge-update {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-discrepancy {
            background-color: #dc3545;
            color: white;
        }

        .badge-verified {
            background-color: #198754;
            color: white;
        }

        .change-details {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 10px;
            border-radius: 4px;
            margin: 5px 0;
        }

        .asset-name {
            font-weight: 600;
            color: #0d6efd;
        }

        .asset-id {
            font-family: 'Courier New', monospace;
            background-color: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.9em;
        }

        .action-buttons {
            white-space: nowrap;
        }

        .no-requests {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .no-requests i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .dropdown-actions {
            position: relative;
        }

        .dropdown-toggle-custom {
            background: transparent;
            border: none;
            color: #6c757d;
            padding: 0.25rem 0.5rem;
        }

        .dropdown-toggle-custom:hover {
            color: #0d6efd;
        }

        .dropdown-menu-custom {
            min-width: 160px;
            padding: 0.5rem 0;
        }

        .dropdown-item {
            padding: 0.375rem 1rem;
            font-size: 0.875rem;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .btn-approve {
            color: #198754;
        }

        .btn-reject {
            color: #dc3545;
        }

        .btn-view {
            color: #0d6efd;
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

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
                            <h5 class="card-title">Pending Verification Requests</h5>
                            <p class="card-subtitle mb-3 text-muted">
                                Review and approve or reject asset change requests submitted by users.
                            </p>

                            <?php if (!empty($resultArr)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="change-requests-table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Asset</th>
                                                <th scope="col">Request Type</th>
                                                <th scope="col">Changes Requested</th>
                                                <th scope="col">Requested By</th>
                                                <th scope="col">Date</th>
                                                <th scope="col" class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($resultArr as $row): ?>
                                                <tr>
                                                    <td>
                                                        <div class="asset-name"><?= htmlspecialchars($row['itemname']) ?></div>
                                                        <div class="asset-id"><?= htmlspecialchars($row['asset_id']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($row['itemtype']) ?></small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $type = $row['verification_status'];
                                                        $badge_class = '';
                                                        $badge_text = '';

                                                        if ($type === 'pending_update') {
                                                            $badge_class = 'badge-update';
                                                            $badge_text = 'Update Request';
                                                        } elseif (strpos($type, 'discrepancy') !== false) {
                                                            $badge_class = 'badge-discrepancy';
                                                            $badge_text = 'Discrepancy Report';
                                                        } elseif ($type === 'verified') {
                                                            $badge_class = 'badge-verified';
                                                            $badge_text = 'Verified';
                                                        } else {
                                                            $badge_class = 'badge-secondary';
                                                            $badge_text = $type;
                                                        }
                                                        ?>
                                                        <span class="badge <?= $badge_class ?>"><?= $badge_text ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['verification_status'] === 'pending_update'): ?>
                                                            <div class="change-details">
                                                                <div><strong>Quantity:</strong>
                                                                    <span class="text-decoration-line-through"><?= $row['old_quantity'] ?></span>
                                                                    → <span class="text-success fw-bold"><?= $row['new_quantity'] ?></span>
                                                                </div>
                                                            </div>
                                                        <?php elseif (strpos($row['verification_status'], 'discrepancy') !== false): ?>
                                                            <div class="change-details">
                                                                <div><strong>Issue Type:</strong>
                                                                    <span class="text-danger fw-bold"><?= htmlspecialchars(ucfirst(str_replace('discrepancy_', '', $row['issue_type'] ?? 'unknown'))) ?></span>
                                                                </div>
                                                                <div><strong>Description:</strong>
                                                                    <?= htmlspecialchars(substr($row['issue_description'] ?? '', 0, 100)) ?>
                                                                    <?php if (strlen($row['issue_description'] ?? '') > 100): ?>...<?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="text-muted">No changes requested</div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-medium"><?= htmlspecialchars($row['verified_by_name']) ?></div>
                                                        <small class="text-muted">ID: <?= htmlspecialchars($row['verified_by']) ?></small>
                                                    </td>
                                                    <td>
                                                        <?= date("d/m/Y", strtotime($row['verification_date'])) ?>
                                                        <br>
                                                        <small class="text-muted"><?= date("g:i a", strtotime($row['verification_date'])) ?></small>
                                                    </td>
                                                    <td class="action-buttons text-center">
                                                        <div class="dropdown dropdown-actions">
                                                            <button class="btn btn-link dropdown-toggle-custom" type="button"
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="bi bi-three-dots-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom">
                                                                <?php if ($row['verification_status'] === 'pending_update'): ?>
                                                                    <li>
                                                                        <a class="dropdown-item btn-approve" href="javascript:void(0)"
                                                                            onclick="reviewRequest(<?= $row['id'] ?>, 'approved', 'update')">
                                                                            <i class="bi bi-check-circle me-2"></i> Approve Changes
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item btn-reject" href="javascript:void(0)"
                                                                            onclick="reviewRequest(<?= $row['id'] ?>, 'rejected', 'update')">
                                                                            <i class="bi bi-x-circle me-2"></i> Reject Changes
                                                                        </a>
                                                                    </li>
                                                                <?php elseif (strpos($row['verification_status'], 'discrepancy') !== false): ?>
                                                                    <li>
                                                                        <a class="dropdown-item btn-approve" href="javascript:void(0)"
                                                                            onclick="reviewRequest(<?= $row['id'] ?>, 'approved', 'discrepancy')">
                                                                            <i class="bi bi-check-circle me-2"></i> Mark as Resolved
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a class="dropdown-item btn-reject" href="javascript:void(0)"
                                                                            onclick="reviewRequest(<?= $row['id'] ?>, 'rejected', 'discrepancy')">
                                                                            <i class="bi bi-x-circle me-2"></i> Reject Report
                                                                        </a>
                                                                    </li>
                                                                <?php endif; ?>
                                                                <li>
                                                                    <hr class="dropdown-divider">
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item btn-view" href="javascript:void(0)"
                                                                        onclick="viewDetails(<?= $row['id'] ?>)">
                                                                        <i class="bi bi-eye me-2"></i> View Details
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="no-requests">
                                    <i class="bi bi-inbox"></i>
                                    <h4>No Pending Requests</h4>
                                    <p class="text-muted">There are no pending change requests to review.</p>
                                    <a href="asset_verification_report.php" class="btn btn-primary">
                                        <i class="bi bi-arrow-left"></i> Back to GPS Dashboard
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewModalTitle">Review Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="reviewForm" method="POST" action="process_review.php">
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="request_id">
                        <input type="hidden" name="action" id="action">
                        <input type="hidden" name="request_type" id="request_type">

                        <div class="mb-3">
                            <label for="admin_remarks" class="form-label">Remarks (Optional)</label>
                            <textarea class="form-control" id="admin_remarks" name="admin_remarks" rows="3"
                                placeholder="Add remarks for this decision..."></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> This action will be logged in the audit trail.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitReview">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            // Initialize DataTables if there are records
            <?php if (!empty($resultArr)) : ?>
                $('#change-requests-table').DataTable({
                    "order": [],
                    "pageLength": 25,
                    "responsive": true,
                    "columnDefs": [{
                        "targets": [5], // Actions column
                        "orderable": false,
                        "searchable": false
                    }],
                    "language": {
                        "search": "Search requests:",
                        "lengthMenu": "Show _MENU_ requests per page",
                        "zeroRecords": "No matching requests found",
                        "info": "Showing _START_ to _END_ of _TOTAL_ requests",
                        "infoEmpty": "No requests available",
                        "infoFiltered": "(filtered from _MAX_ total requests)"
                    }
                });
            <?php endif; ?>

            // Initialize dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle-custom'));
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });

        function reviewRequest(requestId, action, requestType) {
            // Set form values
            document.getElementById('request_id').value = requestId;
            document.getElementById('action').value = action;
            document.getElementById('request_type').value = requestType;

            // Set modal title based on action
            let modalTitle = '';
            if (action === 'approved') {
                modalTitle = requestType === 'update' ? 'Approve Changes' : 'Mark as Resolved';
            } else {
                modalTitle = requestType === 'update' ? 'Reject Changes' : 'Reject Report';
            }

            document.getElementById('reviewModalTitle').textContent = modalTitle;

            // Set submit button text and color
            const submitBtn = document.getElementById('submitReview');
            if (action === 'approved') {
                submitBtn.className = 'btn btn-success';
                submitBtn.innerHTML = requestType === 'update' ? '<i class="bi bi-check-circle"></i> Approve Changes' : '<i class="bi bi-check-circle"></i> Mark as Resolved';
            } else {
                submitBtn.className = 'btn btn-danger';
                submitBtn.innerHTML = '<i class="bi bi-x-circle"></i> Reject Request';
            }

            // Clear previous remarks
            document.getElementById('admin_remarks').value = '';

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
            modal.show();
        }

        function viewDetails(requestId) {
            // Open the details page in a new tab
            window.open(`get_request_details.php?id=${requestId}`, '_self');
        }

        // Handle review form submission
        document.getElementById('reviewForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitReview');
            const originalText = submitBtn.innerHTML;

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

            fetch('process_review.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(result => {
                    if (result.success) {
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
                        if (modal) modal.hide();

                        // Show success message and reload page
                        alert(result.message);
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('Error: ' + result.message);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to submit review. Please check console for details.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        });
    </script>

</body>

</html>