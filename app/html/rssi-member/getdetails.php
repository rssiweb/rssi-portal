<?php
require_once __DIR__ . "/../../bootstrap.php";

// Initialize variables
$error = null;
$associateData = null;
$certificateData = null;

// Check if scode parameter exists and is not empty
if (isset($_GET['scode']) && !empty($_GET['scode'])) {
    $id = $_GET['scode'];
    
    // Fetch associate information
    $associateQuery = "
        SELECT 
            m.fullname,
            m.associatenumber,
            m.photo,
            m.engagement,
            m.position,
            m.doj,
            m.effectivedate,
            m.filterstatus,
            a.ipf,
            interviewer.fullname AS exit_submitted_by_name
        FROM rssimyaccount_members m
        LEFT JOIN (
            SELECT 
                ar1.appraisee_associatenumber,
                ar1.ipf
            FROM appraisee_response ar1
            INNER JOIN (
                SELECT 
                    appraisee_associatenumber,
                    MAX(goalsheet_created_on) AS max_date
                FROM appraisee_response
                WHERE ipf IS NOT NULL
                GROUP BY appraisee_associatenumber
            ) ar2 ON ar1.appraisee_associatenumber = ar2.appraisee_associatenumber 
                AND ar1.goalsheet_created_on = ar2.max_date
        ) a ON a.appraisee_associatenumber = m.associatenumber
        LEFT JOIN associate_exit e ON e.exit_associate_id = m.associatenumber
        LEFT JOIN rssimyaccount_members interviewer ON interviewer.associatenumber = e.exit_submitted_by
        WHERE m.scode = '$id'
    ";
    
    // Fetch certificate information
    $certificateQuery = "
        SELECT * FROM certificate 
        LEFT JOIN (SELECT associatenumber, scode, fullname FROM rssimyaccount_members) faculty 
        ON certificate.awarded_to_id = faculty.associatenumber 
        WHERE (scode = '$id' OR out_scode = '$id') 
        AND badge_name NOT IN ('Experience Letter', 'Offer Letter', 'Joining Letter') 
        ORDER BY issuedon DESC
    ";
    
    $associateResult = pg_query($con, $associateQuery);
    $certificateResult = pg_query($con, $certificateQuery);

    if (!$associateResult || !$certificateResult) {
        $error = "An error occurred while fetching data.";
    } else {
        $associateData = pg_fetch_assoc($associateResult);
        $certificateData = pg_fetch_all($certificateResult);
        
        if (!$associateData && !$certificateData) {
            $error = "No data found for the specified ID.";
        }
    }
} else {
    $error = "No verification code provided.";
}

function calculateExperience($doj, $effectivedate = null) {
    if (empty($doj)) return "DOJ not available";
    
    try {
        $dojDate = new DateTime($doj);
        $currentDate = new DateTime();
        $endDate = $effectivedate ? new DateTime($effectivedate) : $currentDate;

        if ($dojDate > $currentDate) return "Not yet commenced";

        $interval = $dojDate->diff($endDate);
        $years = $interval->y;
        $months = $interval->m;
        $days = $interval->d;

        if ($years > 0) return number_format($years + ($months/12), 2)." year(s)";
        elseif ($months > 0) return number_format($months + ($days/30), 2)." month(s)";
        else return number_format($days, 2)." day(s)";
    } catch (Exception $e) {
        return "Invalid date format";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSSI Certificate Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon">
    <style>
        .verification-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .profile-img {
            max-width: 120px;
            border: 3px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .certificate-card {
            transition: all 0.3s ease;
            border-left: 4px solid #0d6efd;
        }
        .certificate-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .profile-section, .certificate-section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="verification-header py-3 mb-4">
        <div class="container text-center">
            <img src="../img/logo_bg.png" alt="RSSI Logo" height="50" class="mb-2">
            <h1 class="h4 mb-1">Rina Shiksha Sahayak Foundation</h1>
            <h2 class="h5 text-muted">Certificate Verification Portal</h2>
        </div>
    </div>

    <div class="container mb-5">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
        <?php else: ?>

            <!-- Associate Profile Section -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="h5 mb-0">Associate Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                            <img src="<?= htmlspecialchars($associateData['photo'] ?? '../img/default-profile.png') ?>" class="img-fluid" alt="Photo" width="100">
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> <?= htmlspecialchars($associateData['fullname']) ?></p>
                                    <p><strong>Associate ID:</strong> <?= htmlspecialchars($associateData['associatenumber']) ?></p>
                                    <p><strong>Role:</strong> <?= htmlspecialchars($associateData['engagement']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Designation:</strong> <?= htmlspecialchars($associateData['position']) ?></p>
                                    <p><strong>Status:</strong> 
                                        <?= htmlspecialchars($associateData['filterstatus']) ?>
                                    </p>
                                    <p><strong>Service Period:</strong> 
                                        <?= htmlspecialchars($associateData['doj']) ?> to <?= htmlspecialchars($associateData['effectivedate'] ?? 'Present') ?>
                                    </p>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="d-flex flex-wrap justify-content-between">
                                        <div class="me-3">
                                            <strong>Years of Service:</strong> 
                                            <?= htmlspecialchars(calculateExperience($associateData['doj'], $associateData['effectivedate'])) ?>
                                        </div>
                                        <div class="me-3">
                                            <strong>IPF Score:</strong> 
                                            <?= $associateData['ipf'] !== null ? htmlspecialchars($associateData['ipf'])." / 5" : "N/A" ?>
                                        </div>
                                        <div>
                                            <strong>Exit Interview Conducted By:</strong> 
                                            <?= $associateData['filterstatus'] === 'Active' ? 'N/A (Currently Active)'
                                                : (empty($associateData['exit_submitted_by_name']) ? 'Formal exit not recorded'
                                                : htmlspecialchars($associateData['exit_submitted_by_name'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Certificates Section -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="h5 mb-0">Issued Certificates</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($certificateData)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Certificate #</th>
                                        <th>Type</th>
                                        <th>Issued On</th>
                                        <th>Issued By</th>
                                        <th>Remarks</th>
                                        <th>Document</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($certificateData as $cert): ?>
                                        <tr class="certificate-card">
                                            <td><?= htmlspecialchars($cert['certificate_no']) ?></td>
                                            <td><?= htmlspecialchars($cert['badge_name']) ?></td>
                                            <td><?= $cert['issuedon'] ? date("d/m/Y", strtotime($cert['issuedon'])) : 'N/A' ?></td>
                                            <td><?= htmlspecialchars($cert['nominatedby']) ?></td>
                                            <td><?= htmlspecialchars($cert['comment'] ?? '') ?></td>
                                            <td>
                                                <?php if ($cert['certificate_url']): ?>
                                                    <a href="<?= htmlspecialchars($cert['certificate_url']) ?>" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-file-earmark-pdf"></i> View
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">No certificates found for this associate.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Verification Footer -->
            <div class="mt-4 text-center text-muted small">
                <p>This is an auto-generated verification report. For official inquiries, please contact:</p>
                <p>Rina Shiksha Sahayak Foundation<br>
                Email: info@rssi.in | Phone: +91-7980168159 / 9717445551</p>
                <p class="mt-3">Report generated on: <?= date('d/m/Y H:i:s') ?></p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>