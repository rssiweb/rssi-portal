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

// Get search parameters
$asset_id = $_GET['asset_id'] ?? '';
$search_type = $_GET['search_type'] ?? 'all'; // all, verified, pending, discrepancies
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query based on filters
$whereClauses = [];
$params = [];
$paramCount = 1;

if (!empty($asset_id)) {
    $whereClauses[] = "g.itemid ILIKE $" . $paramCount;
    $params[] = "%$asset_id%";
    $paramCount++;
}

if ($search_type !== 'all') {
    if ($search_type === 'verified') {
        $whereClauses[] = "v.verification_status = 'verified'";
    } elseif ($search_type === 'pending') {
        $whereClauses[] = "v.admin_review_status = 'pending'";
    } elseif ($search_type === 'discrepancies') {
        $whereClauses[] = "v.verification_status LIKE 'discrepancy_%'";
    }
}

if (!empty($date_from)) {
    $whereClauses[] = "v.verification_date >= $" . $paramCount;
    $params[] = $date_from;
    $paramCount++;
}

if (!empty($date_to)) {
    // Include the entire day (up to 23:59:59.999)
    $whereClauses[] = "v.verification_date <= $" . $paramCount;
    $params[] = $date_to . ' 23:59:59.999';
    $paramCount++;
}

// Main query for verification records
$query = "SELECT 
            v.*,
            g.itemname,
            g.itemtype,
            g.asset_category,
            g.quantity as current_quantity,
            g.taggedto as current_tagged_to,
            g.asset_status,
            verified_by_user.fullname as verified_by_name,
            reviewed_by_user.fullname as reviewed_by_name,
            CASE 
                WHEN v.verification_status = 'verified' THEN 'Verified'
                WHEN v.verification_status = 'pending_update' THEN 'Update Pending'
                WHEN v.verification_status LIKE 'discrepancy_%' THEN 'Discrepancy'
                ELSE v.verification_status
            END as status_display
          FROM gps_verifications v
          JOIN gps g ON v.asset_id = g.itemid
          LEFT JOIN rssimyaccount_members verified_by_user ON v.verified_by = verified_by_user.associatenumber
          LEFT JOIN rssimyaccount_members reviewed_by_user ON v.reviewed_by = reviewed_by_user.associatenumber";

if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(" AND ", $whereClauses);
}

$query .= " ORDER BY v.verification_date DESC LIMIT 100";

// Debug: Log the query and parameters
error_log("Query: " . $query);
error_log("Params: " . print_r($params, true));

// Execute query
if (!empty($params)) {
    $result = pg_query_params($con, $query, $params);
} else {
    $result = pg_query($con, $query);
}

// Check for query errors
if (!$result) {
    $error = pg_last_error($con);
    error_log("Database error: " . $error);
    // You might want to display a user-friendly error or log it
    $resultArr = [];
} else {
    $resultArr = pg_fetch_all($result) ?: [];
}

// Get statistics
$statsQuery = "SELECT 
                COUNT(*) as total_verifications,
                SUM(CASE WHEN verification_status = 'verified' THEN 1 ELSE 0 END) as verified_count,
                SUM(CASE WHEN verification_status = 'pending_update' AND admin_review_status = 'pending' THEN 1 ELSE 0 END) as pending_updates,
                SUM(CASE WHEN verification_status LIKE 'discrepancy_%' AND admin_review_status = 'pending' THEN 1 ELSE 0 END) as pending_discrepancies,
                SUM(CASE WHEN admin_review_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN admin_review_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
               FROM gps_verifications";
$statsResult = pg_query($con, $statsQuery);
$stats = pg_fetch_assoc($statsResult);
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

    <title>Asset Verification Status</title>

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

        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .badge-verified {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .badge-pending {
            background-color: #fff3cd;
            color: #664d03;
        }

        .badge-discrepancy {
            background-color: #f8d7da;
            color: #842029;
        }

        .badge-approved {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .badge-rejected {
            background-color: #f8d7da;
            color: #842029;
        }

        .badge-review-pending {
            background-color: #cff4fc;
            color: #055160;
        }

        .verification-card {
            border-left: 4px solid #dee2e6;
            padding-left: 12px;
            margin-bottom: 15px;
        }

        .verification-verified {
            border-left-color: #198754;
        }

        .verification-pending {
            border-left-color: #ffc107;
        }

        .verification-discrepancy {
            border-left-color: #dc3545;
        }

        .stats-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .stats-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .asset-info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .filter-card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #6c757d;
        }

        .timeline-item.verified::before {
            background: #198754;
        }

        .timeline-item.pending::before {
            background: #ffc107;
        }

        .timeline-item.discrepancy::before {
            background: #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .action-dropdown {
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
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Asset Verification Status</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">GPS</a></li>
                    <li class="breadcrumb-item active">Verification Status</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <!-- Statistics Cards -->
                <div class="col-lg-3 col-md-6">
                    <div class="stats-card" style="background: linear-gradient(135deg, #e3f2fd, #bbdefb);">
                        <div class="stats-icon text-primary">
                            <i class="bi bi-check-all"></i>
                        </div>
                        <div class="stats-value"><?= $stats['total_verifications'] ?? 0 ?></div>
                        <div class="stats-label">Total Verifications</div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-card" style="background: linear-gradient(135deg, #e8f5e9, #c8e6c9);">
                        <div class="stats-icon text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stats-value"><?= $stats['verified_count'] ?? 0 ?></div>
                        <div class="stats-label">Verified Assets</div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-card" style="background: linear-gradient(135deg, #fff8e1, #ffecb3);">
                        <div class="stats-icon text-warning">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="stats-value"><?= ($stats['pending_updates'] + $stats['pending_discrepancies']) ?? 0 ?></div>
                        <div class="stats-label">Pending Review</div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-card" style="background: linear-gradient(135deg, #ffebee, #ffcdd2);">
                        <div class="stats-icon text-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="stats-value"><?= $stats['pending_discrepancies'] ?? 0 ?></div>
                        <div class="stats-label">Discrepancies</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <!-- Filter Card -->
                    <div class="filter-card">
                        <h5 class="card-title mb-3">Search & Filter</h5>
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="asset_id" class="form-label">Asset ID</label>
                                <input type="text" class="form-control" id="asset_id" name="asset_id"
                                    value="<?= htmlspecialchars($asset_id) ?>" placeholder="Enter Asset ID">
                            </div>

                            <div class="col-md-3">
                                <label for="search_type" class="form-label">Status Type</label>
                                <select class="form-select" id="search_type" name="search_type">
                                    <option value="all" <?= $search_type === 'all' ? 'selected' : '' ?>>All Verifications</option>
                                    <option value="verified" <?= $search_type === 'verified' ? 'selected' : '' ?>>Verified Only</option>
                                    <option value="pending" <?= $search_type === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                                    <option value="discrepancies" <?= $search_type === 'discrepancies' ? 'selected' : '' ?>>Discrepancies Only</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="date_from" name="date_from"
                                    value="<?= htmlspecialchars($date_from) ?>">
                            </div>

                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="date_to" name="date_to"
                                    value="<?= htmlspecialchars($date_to) ?>">
                            </div>

                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Verification Records</h5>
                            <p class="card-subtitle mb-3 text-muted">
                                Showing <?= count($resultArr) ?> verification records
                            </p>

                            <?php if (!empty($resultArr)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="verification-table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Asset</th>
                                                <th scope="col">Verification Type</th>
                                                <th scope="col">Status</th>
                                                <th scope="col">Submitted By</th>
                                                <th scope="col">Verification Date</th>
                                                <th scope="col">Admin Review</th>
                                                <th scope="col" class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($resultArr as $row): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold" title="<?= htmlspecialchars($row['itemname']) ?>">
                                                            <?= htmlspecialchars(mb_strimwidth($row['itemname'], 0, 40, '…')) ?>
                                                        </div>
                                                        <div class="text-muted small"><?= htmlspecialchars($row['asset_id']) ?></div>
                                                        <div class="badge bg-secondary"><?= htmlspecialchars($row['itemtype']) ?></div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $type = $row['verification_status'];
                                                        if ($type === 'verified') {
                                                            echo '<span class="badge-verified status-badge">Verified</span>';
                                                        } elseif ($type === 'pending_update') {
                                                            echo '<span class="badge-pending status-badge">Update Request</span>';
                                                        } elseif (strpos($type, 'discrepancy') !== false) {
                                                            $issueType = str_replace('discrepancy_', '', $type);
                                                            echo '<span class="badge-discrepancy status-badge">Discrepancy: ' . ucfirst($issueType) . '</span>';
                                                        } else {
                                                            echo '<span class="badge bg-secondary status-badge">' . htmlspecialchars($type) . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($row['verification_status'] === 'pending_update'): ?>
                                                            <div class="small">
                                                                <div>Quantity: <?= $row['old_quantity'] ?> → <?= $row['new_quantity'] ?></div>
                                                            </div>
                                                        <?php elseif (strpos($row['verification_status'], 'discrepancy') !== false): ?>
                                                            <div class="small">
                                                                <div><?= htmlspecialchars(substr($row['remarks'] ?? $row['issue_description'] ?? '', 0, 50)) ?>...</div>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="small">
                                                                <div>Verified as correct</div>
                                                                <div class="text-muted"><?= htmlspecialchars(substr($row['remarks'] ?? '', 0, 50)) ?></div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div><?= htmlspecialchars($row['verified_by_name']) ?></div>
                                                        <div class="text-muted small"><?= htmlspecialchars($row['verified_by']) ?></div>
                                                    </td>
                                                    <td>
                                                        <?= date("d/m/Y", strtotime($row['verification_date'])) ?>
                                                        <br>
                                                        <small class="text-muted"><?= date("g:i a", strtotime($row['verification_date'])) ?></small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $reviewStatus = $row['admin_review_status'] ?? 'pending';
                                                        $reviewedBy   = $row['reviewed_by'] ?? '';
                                                        $reviewerName = $row['reviewed_by_name'] ?? '';

                                                        // System Approved condition
                                                        if ($reviewStatus === 'approved' && $reviewedBy === 'system') {

                                                            echo '<span class="badge-approved status-badge">System Approved</span>';
                                                        } elseif ($reviewStatus === 'approved') {

                                                            echo '<span class="badge-approved status-badge">Approved</span>';
                                                            if ($reviewerName) {
                                                                echo '<div class="small text-muted">By: ' . htmlspecialchars($reviewerName) . '</div>';
                                                            }
                                                        } elseif ($reviewStatus === 'rejected') {

                                                            echo '<span class="badge-rejected status-badge">Rejected</span>';
                                                            if ($reviewerName) {
                                                                echo '<div class="small text-muted">By: ' . htmlspecialchars($reviewerName) . '</div>';
                                                            }
                                                        } elseif (($row['verification_status'] ?? '') === 'verified') {

                                                            echo '<span class="badge-verified status-badge">Not Required</span>';
                                                        } else {

                                                            echo '<span class="badge-review-pending status-badge">Pending Review</span>';
                                                        }
                                                        ?>
                                                    </td>

                                                    <td class="text-center">
                                                        <div class="action-dropdown">
                                                            <button class="btn btn-link dropdown-toggle-custom" type="button"
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="bi bi-three-dots-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li>
                                                                    <a class="dropdown-item" href="get_request_details.php?id=<?= $row['id'] ?>">
                                                                        <i class="bi bi-eye me-2"></i> View Details
                                                                    </a>
                                                                </li>
                                                                <?php
                                                                if (
                                                                    $role === 'Admin'
                                                                    && ($reviewStatus === 'pending' || $reviewStatus === '')
                                                                    && (
                                                                        $row['verification_status'] === 'pending_update'
                                                                        || str_starts_with($row['verification_status'], 'discrepancy_')
                                                                    )
                                                                ):
                                                                ?>
                                                                    <li>
                                                                        <a class="dropdown-item" href="admin_change_requests.php">
                                                                            <i class="bi bi-check-circle me-2"></i> Review Request
                                                                        </a>
                                                                    </li>
                                                                <?php endif; ?>
                                                                <li>
                                                                    <a class="dropdown-item" href="gps.php?assetid=<?= urlencode($row['asset_id']) ?>" target="_blank">
                                                                        <i class="bi bi-box-arrow-up-right me-2"></i> View Asset
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
                                <div class="empty-state">
                                    <i class="bi bi-inbox"></i>
                                    <h4>No Verification Records Found</h4>
                                    <p class="text-muted">Try adjusting your search filters or verify some assets first.</p>
                                    <a href="scan-asset.php" class="btn btn-primary">
                                        <i class="bi bi-qr-code-scan"></i> Start Verifying Assets
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset Verification Summary (if asset_id is specified) -->
            <?php if (!empty($asset_id)): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Asset Verification Summary: <?= htmlspecialchars($asset_id) ?></h5>

                                <?php
                                // Get asset details
                                $assetQuery = "SELECT 
                                    g.*, 
                                    tm.fullname as tagged_to_name,
                                    im.fullname as issued_by_name,
                                    -- Get last verification info
                                    latest_verification.verified_by,
                                    latest_verification.verified_by_name,
                                    latest_verification.verification_date as last_verified_on,
                                    latest_verification.reviewed_by,
                                    latest_verification.reviewed_by_name,
                                    latest_verification.review_date,
                                    latest_verification.verification_status,
                                    latest_verification.admin_review_status,
                                    -- Get verification count
                                    COALESCE(verification_stats.verified_count, 0) as verified_count
                                FROM gps g
                                LEFT JOIN rssimyaccount_members tm ON g.taggedto = tm.associatenumber
                                LEFT JOIN rssimyaccount_members im ON g.collectedby = im.associatenumber
                                -- Get the latest verification record
                                LEFT JOIN LATERAL (
                                    SELECT 
                                        v.verification_date,
                                        v.verified_by,
                                        verified_user.fullname as verified_by_name,
                                        v.reviewed_by,
                                        reviewed_user.fullname as reviewed_by_name,
                                        v.review_date,
                                        v.verification_status,
                                        v.admin_review_status
                                    FROM gps_verifications v
                                    LEFT JOIN rssimyaccount_members verified_user ON v.verified_by = verified_user.associatenumber
                                    LEFT JOIN rssimyaccount_members reviewed_user ON v.reviewed_by = reviewed_user.associatenumber
                                    WHERE v.asset_id = g.itemid
                                    AND (v.verification_status = 'verified' 
                                            OR v.admin_review_status = 'approved'
                                            OR v.verification_status = 'file_uploaded')
                                    ORDER BY 
                                        CASE 
                                            WHEN v.verification_status = 'verified' THEN 1
                                            WHEN v.admin_review_status = 'approved' THEN 2
                                            ELSE 3
                                        END,
                                        v.verification_date DESC
                                    LIMIT 1
                                ) latest_verification ON true
                                -- Get verification statistics
                                LEFT JOIN (
                                    SELECT 
                                        asset_id,
                                        COUNT(*) as verified_count
                                    FROM gps_verifications
                                    WHERE verification_status = 'verified'
                                    GROUP BY asset_id
                                ) verification_stats ON verification_stats.asset_id = g.itemid
                                WHERE g.itemid = $1";
                                $assetResult = pg_query_params($con, $assetQuery, [$asset_id]);
                                $assetDetails = pg_fetch_assoc($assetResult);

                                if ($assetDetails):
                                ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="asset-info-card">
                                                <h6>Asset Information</h6>
                                                <table class="table table-sm table-borderless">
                                                    <tr>
                                                        <td width="40%"><strong>Name:</strong></td>
                                                        <td><?= htmlspecialchars($assetDetails['itemname']) ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Type:</strong></td>
                                                        <td><?= htmlspecialchars($assetDetails['itemtype']) ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Category:</strong></td>
                                                        <td><?= !empty($assetDetails['asset_category'])
                                                                ? htmlspecialchars($assetDetails['asset_category'])
                                                                : '<span class="text-muted">N/A</span>' ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Quantity:</strong></td>
                                                        <td><?= $assetDetails['quantity'] ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Status:</strong></td>
                                                        <td>
                                                            <span class="badge bg-<?= $assetDetails['asset_status'] === 'Active' ? 'success' : 'danger' ?>">
                                                                <?= htmlspecialchars($assetDetails['asset_status']) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="asset-info-card">
                                                <h6>Verification Status</h6>
                                                <table class="table table-sm table-borderless">
                                                    <tr>
                                                        <td width="40%"><strong>Last Verified:</strong></td>
                                                        <td>
                                                            <?= $assetDetails['last_verified_on'] ?
                                                                date("d/m/Y g:i a", strtotime($assetDetails['last_verified_on'])) :
                                                                '<span class="text-muted">Never</span>' ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Verified By:</strong></td>
                                                        <td>
                                                            <?= $assetDetails['verified_by_name'] ?
                                                                htmlspecialchars($assetDetails['verified_by_name']) .
                                                                ' (' . htmlspecialchars($assetDetails['verified_by']) . ')' :
                                                                '<span class="text-muted">N/A</span>' ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Verification Count:</strong></td>
                                                        <td><?= $assetDetails['verified_count'] ?? 0 ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Tagged To:</strong></td>
                                                        <td>
                                                            <?= $assetDetails['tagged_to_name'] ?
                                                                htmlspecialchars($assetDetails['tagged_to_name']) .
                                                                ' (' . htmlspecialchars($assetDetails['taggedto']) . ')' :
                                                                '<span class="text-muted">Not assigned</span>' ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Verification Timeline -->
                                    <div class="mt-4">
                                        <h6>Verification Timeline</h6>
                                        <div class="timeline">
                                            <?php
                                            $timelineQuery = "SELECT 
                                                v.*,
                                                verified_user.fullname as verified_by_name,
                                                reviewed_user.fullname as reviewed_by_name
                                            FROM gps_verifications v
                                            LEFT JOIN rssimyaccount_members verified_user ON v.verified_by = verified_user.associatenumber
                                            LEFT JOIN rssimyaccount_members reviewed_user ON v.reviewed_by = reviewed_user.associatenumber
                                            WHERE v.asset_id = $1 
                                            ORDER BY v.verification_date DESC 
                                            LIMIT 5";
                                            $timelineResult = pg_query_params($con, $timelineQuery, [$asset_id]);

                                            if ($timelineResult && pg_num_rows($timelineResult) > 0):
                                                while ($timeline = pg_fetch_assoc($timelineResult)):
                                                    $timelineClass = '';
                                                    $displayStatus = '';
                                                    $displayReviewStatus = '';
                                                    $reviewStatusBadge = '';

                                                    // Determine timeline class and display status
                                                    if ($timeline['verification_status'] === 'verified') {
                                                        $timelineClass = 'verified';
                                                        $displayStatus = 'Verified';
                                                        $displayReviewStatus = 'system approved';
                                                        $reviewStatusBadge = 'success';
                                                    } elseif ($timeline['verification_status'] === 'pending_update') {
                                                        $timelineClass = 'pending';
                                                        $displayStatus = 'Update Pending Approval';
                                                        $displayReviewStatus = $timeline['admin_review_status'] ?? 'pending';
                                                        $reviewStatusBadge = $displayReviewStatus === 'approved' ? 'success' : ($displayReviewStatus === 'rejected' ? 'danger' : 'warning');
                                                    } elseif ($timeline['verification_status'] === 'file_uploaded') {
                                                        $timelineClass = 'updated';
                                                        $displayStatus = 'Files Updated';
                                                        $displayReviewStatus = 'system approved';
                                                        $reviewStatusBadge = 'success';
                                                    } elseif (strpos($timeline['verification_status'], 'discrepancy') !== false) {
                                                        $timelineClass = 'discrepancy';
                                                        $displayStatus = 'Discrepancy Reported';
                                                        $displayReviewStatus = $timeline['admin_review_status'] ?? 'pending';
                                                        $reviewStatusBadge = $displayReviewStatus === 'approved' ? 'success' : ($displayReviewStatus === 'rejected' ? 'danger' : 'warning');
                                                    } else {
                                                        $timelineClass = 'info';
                                                        $displayStatus = ucfirst(str_replace('_', ' ', $timeline['verification_status']));
                                                        $displayReviewStatus = $timeline['admin_review_status'] ?? '';
                                                        $reviewStatusBadge = $displayReviewStatus === 'approved' ? 'success' : ($displayReviewStatus === 'rejected' ? 'danger' : ($displayReviewStatus === 'pending' ? 'warning' : 'secondary'));
                                                    }

                                                    // Determine who to show as the actor
                                                    $actorName = '';
                                                    $actorPrefix = '';

                                                    // Check if this is auto-approved by system (verified or file_uploaded)
                                                    if (($timeline['verification_status'] === 'verified' || $timeline['verification_status'] === 'file_uploaded') &&
                                                        $displayReviewStatus === 'approved'
                                                    ) {
                                                        // Auto-approved by system
                                                        $actorName = 'system';
                                                        $actorPrefix = 'Reviewed by: ';
                                                    }
                                                    // Check if it's approved by a human reviewer
                                                    elseif ($displayReviewStatus === 'approved' && $displayReviewStatus !== '') {
                                                        // Approved by human reviewer
                                                        $actorName = $timeline['reviewed_by_name'] ?? $timeline['reviewed_by'] ?? 'Unknown';
                                                        $actorPrefix = 'Reviewed by: ';
                                                    }
                                                    // Check if it's pending approval (pending_update or discrepancy pending)
                                                    elseif (
                                                        $displayReviewStatus === 'pending' ||
                                                        ($timeline['verification_status'] === 'pending_update' && empty($displayReviewStatus)) ||
                                                        (strpos($timeline['verification_status'], 'discrepancy') !== false && empty($displayReviewStatus))
                                                    ) {
                                                        // Pending review - show who submitted it
                                                        $actorName = $timeline['verified_by_name'] ?? $timeline['verified_by'] ?? 'Unknown';
                                                        $actorPrefix = 'Submitted by: ';
                                                    }
                                                    // Default case - show who verified/reported it
                                                    else {
                                                        $actorName = $timeline['verified_by_name'] ?? $timeline['verified_by'] ?? 'Unknown';
                                                        $actorPrefix = 'By: ';
                                                    }
                                            ?>
                                                    <div class="timeline-item <?= $timelineClass ?>">
                                                        <div class="d-flex justify-content-between">
                                                            <strong><?= htmlspecialchars($displayStatus) ?></strong>
                                                            <small class="text-muted">
                                                                <?= date("d/m/Y g:i a", strtotime($timeline['verification_date'])) ?>
                                                            </small>
                                                        </div>
                                                        <div class="small">
                                                            <?= $actorPrefix ?><?= htmlspecialchars($actorName) ?>
                                                            <?php if ($displayReviewStatus): ?>
                                                                <span class="badge bg-<?= $reviewStatusBadge ?> ms-2">
                                                                    <?= ucfirst($displayReviewStatus) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if ($timeline['remarks']): ?>
                                                            <div class="text-muted small mt-1">
                                                                <?= htmlspecialchars(substr($timeline['remarks'], 0, 100)) ?>
                                                                <?php if (strlen($timeline['remarks']) > 100): ?>...<?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($timeline['update_reason'] && $timeline['verification_status'] === 'pending_update'): ?>
                                                            <div class="text-muted small mt-1">
                                                                <strong>Reason:</strong> <?= htmlspecialchars(substr($timeline['update_reason'], 0, 100)) ?>
                                                                <?php if (strlen($timeline['update_reason']) > 100): ?>...<?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($timeline['issue_description'] && strpos($timeline['verification_status'], 'discrepancy') !== false): ?>
                                                            <div class="text-muted small mt-1">
                                                                <strong>Issue:</strong> <?= htmlspecialchars(substr($timeline['issue_description'], 0, 100)) ?>
                                                                <?php if (strlen($timeline['issue_description']) > 100): ?>...<?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php
                                                endwhile;
                                            else:
                                                ?>
                                                <div class="text-muted text-center py-3">
                                                    <i class="bi bi-info-circle"></i> No verification history found for this asset.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle"></i> Asset not found: <?= htmlspecialchars($asset_id) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#verification-table').DataTable({
                    "order": [
                        [4, "desc"]
                    ], // Sort by verification date descending
                    "pageLength": 25,
                    "responsive": true,
                    "language": {
                        "search": "Search verifications:",
                        "lengthMenu": "Show _MENU_ records per page",
                        "zeroRecords": "No matching records found",
                        "info": "Showing _START_ to _END_ of _TOTAL_ records",
                        "infoEmpty": "No records available",
                        "infoFiltered": "(filtered from _MAX_ total records)"
                    },
                    "columnDefs": [{
                        "targets": [6], // Actions column
                        "orderable": false,
                        "searchable": false
                    }]
                });
            <?php endif; ?>

            // Set date_to to today by default
            if (!$('#date_to').val()) {
                const today = new Date().toISOString().split('T')[0];
                $('#date_to').val(today);
            }

            // Set date_from to 30 days ago by default
            if (!$('#date_from').val()) {
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                $('#date_from').val(thirtyDaysAgo.toISOString().split('T')[0]);
            }

            // Initialize dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle-custom'));
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });
    </script>

</body>

</html>