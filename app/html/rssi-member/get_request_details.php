<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include(__DIR__ . "/../image_functions.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

$request_id = $_GET['id'] ?? '';

if (!$request_id) {
    die("Request ID is required.");
}

// Get verification request details
$query = "SELECT 
            v.*,
            g.itemname,
            g.itemid as asset_id,
            g.asset_status,
            verified_by_user.fullname as verified_by_name,
            reviewed_by_user.fullname as reviewed_by_name
          FROM gps_verifications v
          JOIN gps g ON v.asset_id = g.itemid
          LEFT JOIN rssimyaccount_members verified_by_user ON v.verified_by = verified_by_user.associatenumber
          LEFT JOIN rssimyaccount_members reviewed_by_user ON v.reviewed_by = reviewed_by_user.associatenumber
          WHERE v.id = $request_id";

$result = pg_query($con, $query);

if (!$result || pg_num_rows($result) === 0) {
    die("Request not found.");
}

$request = pg_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details - <?= htmlspecialchars($request['asset_id']) ?></title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        .detail-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .detail-label {
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #212529;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .status-badge {
            font-size: 0.9rem;
            padding: 5px 12px;
            border-radius: 20px;
        }

        .change-highlight {
            background-color: #e7f1ff;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #0d6efd;
            margin: 10px 0;
        }

        .back-button {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <body>
        <?php include 'inactive_session_expire_check.php'; ?>
        <?php include 'header.php'; ?>

        <main id="main" class="main">

            <div class="pagetitle">
                <h1>Request Details</h1>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">GPS</a></li>
                        <li class="breadcrumb-item active">Request Details</li>
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
                                <div class="container">
                                    <div class="back-button">
                                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left"></i> Back
                                        </a>
                                    </div>

                                    <div class="detail-card">
                                        <h4 class="mb-4">
                                            <i class="bi bi-file-text"></i> Request Details
                                            <?php
                                            $reviewStatus = $request['admin_review_status'];

                                            // Override label only when approved by system
                                            $displayStatus = (
                                                $request['admin_review_status'] === 'approved' &&
                                                $request['reviewed_by'] === 'system'
                                            )
                                                ? 'system_approved'
                                                : $reviewStatus;
                                            ?>

                                            <span class="badge 
                                                <?= $displayStatus === 'pending' ? 'bg-warning'
                                                    : ($displayStatus === 'approved' || $displayStatus === 'system_approved' ? 'bg-success'
                                                        : 'bg-danger') ?>
                                                float-end">

                                                <?= strtoupper(str_replace('_', ' ', $displayStatus)) ?>
                                            </span>
                                        </h4>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="detail-label">Asset Information</div>
                                                <div class="detail-value">
                                                    <strong><?= htmlspecialchars($request['itemname']) ?></strong><br>
                                                    <small class="text-muted">ID: <?= htmlspecialchars($request['asset_id']) ?></small>
                                                </div>

                                                <div class="detail-label">Request Type</div>
                                                <div class="detail-value">
                                                    <?php if ($request['verification_status'] === 'pending_update'): ?>
                                                        <span class="badge bg-warning status-badge">Update Request</span>
                                                    <?php elseif (strpos($request['verification_status'], 'discrepancy') !== false): ?>
                                                        <span class="badge bg-danger status-badge">Discrepancy Report</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info status-badge"><?= htmlspecialchars($request['verification_status']) ?></span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="detail-label">Submitted By</div>
                                                <div class="detail-value"><?= htmlspecialchars($request['verified_by_name']) ?> (<?= htmlspecialchars($request['verified_by']) ?>)</div>

                                                <div class="detail-label">Submission Date</div>
                                                <div class="detail-value"><?= date('d/m/Y g:i a', strtotime($request['verification_date'])) ?></div>
                                            </div>

                                            <div class="col-md-6">
                                                <?php if ($request['reviewed_by']): ?>
                                                    <div class="detail-label">Reviewed By</div>
                                                    <div class="detail-value">
                                                        <?php if ($request['reviewed_by'] === 'system'): ?>
                                                            System
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($request['reviewed_by_name']) ?>
                                                            (<?= htmlspecialchars($request['reviewed_by']) ?>)
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="detail-label">Review Date</div>
                                                    <div class="detail-value"><?= date('d/m/Y g:i a', strtotime($request['review_date'])) ?></div>

                                                    <?php if ($request['admin_remarks']): ?>
                                                        <div class="detail-label">Admin Remarks</div>
                                                        <div class="detail-value">
                                                            <div class="alert alert-light">
                                                                <?= nl2br(htmlspecialchars($request['admin_remarks'])) ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if ($request['verification_status'] === 'pending_update'): ?>
                                            <hr>
                                            <h5 class="mb-3"><i class="bi bi-arrow-left-right"></i> Requested Changes</h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="change-highlight">
                                                        <div class="detail-label">Current Quantity</div>
                                                        <div class="detail-value text-decoration-line-through"><?= $request['old_quantity'] ?></div>

                                                        <div class="detail-label">Requested Quantity</div>
                                                        <div class="detail-value text-success fw-bold"><?= $request['new_quantity'] ?></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if ($request['remarks']): ?>
                                                <div class="change-highlight">
                                                    <div class="detail-label">Reason for Change</div>
                                                    <div class="detail-value"><?= nl2br(htmlspecialchars($request['remarks'])) ?></div>
                                                </div>
                                            <?php endif; ?>

                                        <?php elseif (strpos($request['verification_status'], 'discrepancy') !== false): ?>
                                            <hr>
                                            <h5 class="mb-3"><i class="bi bi-exclamation-triangle"></i> Discrepancy Details</h5>

                                            <div class="change-highlight">
                                                <div class="row">
                                                    <!-- Left column: Issue details -->
                                                    <div class="col-md-6">
                                                        <div class="detail-label">Issue Type</div>
                                                        <div class="detail-value">
                                                            <?php
                                                            $issue_type = str_replace('discrepancy_', '', $request['issue_type'] ?? 'unknown');
                                                            echo ucwords(str_replace('_', ' ', $issue_type));
                                                            ?>
                                                        </div>

                                                        <?php if ($request['issue_description']): ?>
                                                            <div class="detail-label">Description</div>
                                                            <div class="detail-value"><?= nl2br(htmlspecialchars($request['issue_description'])) ?></div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Right column: Evidence photo -->
                                                    <div class="col-md-6">
                                                        <div class="detail-label">Evidence Photo</div>
                                                        <div class="detail-value">
                                                            <?php if (!empty($request['evidence_photo_path'])): ?>
                                                                <?php
                                                                // Clean the path - remove any query parameters for display
                                                                $clean_path = processImageUrl($request['evidence_photo_path'], '?');
                                                                ?>
                                                                <a href="<?= htmlspecialchars($request['evidence_photo_path']) ?>"
                                                                    target="_blank"
                                                                    title="Click to open in new window">
                                                                    <img src="<?= htmlspecialchars($clean_path) ?>"
                                                                        alt="Evidence Photo"
                                                                        class="img-fluid rounded border evidence-photo"
                                                                        style="max-height: 200px; object-fit: contain; cursor: pointer;">
                                                                </a>
                                                                <div class="mt-1 small text-muted">
                                                                    Click image to view full size
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="text-muted py-3">
                                                                    <i class="bi bi-image" style="font-size: 2rem;"></i>
                                                                    <div class="mt-2">No evidence photo</div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="detail-card">
                                        <h5 class="mb-3"><i class="bi bi-clock-history"></i> Request History</h5>

                                        <?php
                                        // Get history for this request
                                        $history_query = "SELECT * FROM gps_history 
                              WHERE (changes::json->>'request_id')::text = '$request_id' 
                              OR (changes::json->>'verification_id')::text = '$request_id'
                              ORDER BY date DESC";

                                        $history_result = pg_query($con, $history_query);

                                        if ($history_result && pg_num_rows($history_result) > 0): ?>
                                            <div class="list-group">
                                                <?php while ($history = pg_fetch_assoc($history_result)):
                                                    $changes = json_decode($history['changes'], true);
                                                ?>
                                                    <div class="list-group-item">
                                                        <div class="d-flex w-100 justify-content-between">
                                                            <h6 class="mb-1">
                                                                <?= htmlspecialchars($history['update_type']) ?>
                                                            </h6>
                                                            <small class="text-muted"><?= date('d/m/Y g:i a', strtotime($history['date'])) ?></small>
                                                        </div>
                                                        <p class="mb-1">By: <?= htmlspecialchars($history['updatedby']) ?></p>
                                                        <?php if ($changes && is_array($changes)): ?>
                                                            <small class="text-muted">
                                                                <?php
                                                                if (isset($changes['type'])) echo "Type: " . $changes['type'] . "<br>";
                                                                if (isset($changes['admin_remarks'])) echo "Remarks: " . htmlspecialchars($changes['admin_remarks']);
                                                                ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">No history found for this request.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- End Reports -->
                </div>
            </section>

        </main><!-- End #main -->

        <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Template Main JS File -->
        <script src="../assets_new/js/main.js"></script>
    </body>

</html>