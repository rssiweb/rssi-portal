<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit;
}

$applicant_id = isset($_GET['applicant_id']) ? intval($_GET['applicant_id']) : 0;
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

if (!$applicant_id || !$job_id) {
    echo '<div class="alert alert-danger">Invalid parameters</div>';
    exit;
}

// Fetch applicant details with application info
$query = "SELECT jsd.*, ja.application_date, ja.status as application_status,
                 el.name as education_name
          FROM job_seeker_data jsd
          JOIN job_applications ja ON jsd.id = ja.job_seeker_id
          LEFT JOIN education_levels el ON jsd.education = el.id
          WHERE jsd.id = $1 AND ja.job_id = $2";

$result = pg_query_params($con, $query, [$applicant_id, $job_id]);

if (!$result || pg_num_rows($result) == 0) {
    echo '<div class="alert alert-danger">Applicant not found</div>';
    exit;
}

$applicant = pg_fetch_assoc($result);
?>

<div class="row">
    <div class="col-md-4">
        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="bi bi-person-circle" style="font-size: 4rem; color: #667eea;"></i>
            </div>
            <h4><?php echo htmlspecialchars($applicant['name']); ?></h4>
            <p class="text-muted">Applicant ID: <?php echo $applicant['id']; ?></p>
        </div>

        <div class="mb-4">
            <h6><i class="bi bi-telephone me-2"></i>Contact Information</h6>
            <div class="mb-2">
                <strong>Phone:</strong><br>
                <a href="tel:<?php echo htmlspecialchars($applicant['contact']); ?>">
                    <?php echo htmlspecialchars($applicant['contact']); ?>
                </a>
            </div>
            <?php if (!empty($applicant['email'])): ?>
                <div class="mb-2">
                    <strong>Email:</strong><br>
                    <a href="mailto:<?php echo htmlspecialchars($applicant['email']); ?>">
                        <?php echo htmlspecialchars($applicant['email']); ?>
                    </a>
                </div>
            <?php endif; ?>
            <?php if (!empty($applicant['address1'])): ?>
                <div class="mb-2">
                    <strong>Address:</strong><br>
                    <?php echo nl2br(htmlspecialchars($applicant['address1'])); ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($applicant['resume'])): ?>
            <div class="mb-4">
                <h6><i class="bi bi-file-earmark-pdf me-2"></i>Resume</h6>
                <a href="<?php echo htmlspecialchars($applicant['resume']); ?>"
                    target="_blank" class="btn btn-outline-primary w-100">
                    <i class="bi bi-download me-2"></i>Download Resume
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-8">
        <div class="mb-4">
            <h6><i class="bi bi-info-circle me-2"></i>Personal Information</h6>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <strong>Age:</strong><br>
                    <?php
                    if (!empty($applicant['dob'])) {
                        $dob = new DateTime($applicant['dob']);
                        $today = new DateTime();
                        $age = $today->diff($dob)->y;
                        echo $age . ' years';
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
                <div class="col-md-6 mb-2">
                    <strong>Education:</strong><br>
                    <?php echo htmlspecialchars($applicant['education_name'] ?? $applicant['education']); ?>
                </div>
                <div class="col-md-6 mb-2">
                    <strong>Applicant Status:</strong><br>
                    <?php if ($applicant['status'] === 'Active'): ?>
                        <span class="badge bg-success">Active</span>
                    <?php elseif ($applicant['status'] === 'Inactive'): ?>
                        <span class="badge bg-secondary">Inactive</span>
                    <?php else: ?>
                        <span class="badge bg-info"><?php echo htmlspecialchars($applicant['status']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-2">
                    <strong>Application Status:</strong><br>
                    <?php if ($applicant['application_status'] === 'Applied'): ?>
                        <span class="badge bg-primary">Applied</span>
                    <?php elseif ($applicant['application_status'] === 'Selected'): ?>
                        <span class="badge bg-success">Selected</span>
                    <?php elseif ($applicant['application_status'] === 'Rejected'): ?>
                        <span class="badge bg-danger">Rejected</span>
                    <?php else: ?>
                        <span class="badge bg-info"><?php echo htmlspecialchars($applicant['application_status']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-2">
                    <strong>Applied On:</strong><br>
                    <?php echo date('d/m/Y H:i:s', strtotime($applicant['application_date'])); ?>
                </div>
                <div class="col-md-6 mb-2">
                    <strong>Profile Created:</strong><br>
                    <?php echo date('d/m/Y', strtotime($applicant['created_at'])); ?>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <h6><i class="bi bi-tools me-2"></i>Skills</h6>
            <div class="p-3 bg-light rounded">
                <?php echo nl2br(htmlspecialchars($applicant['skills'])); ?>
            </div>
        </div>

        <?php if (!empty($applicant['preferences'])): ?>
            <div class="mb-4">
                <h6><i class="bi bi-heart me-2"></i>Job Preferences</h6>
                <div class="p-3 bg-light rounded">
                    <?php echo nl2br(htmlspecialchars($applicant['preferences'])); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($applicant['remarks'])): ?>
            <div class="mb-4">
                <h6><i class="bi bi-chat-left-text me-2"></i>Remarks</h6>
                <div class="p-3 bg-light rounded">
                    <?php echo nl2br(htmlspecialchars($applicant['remarks'])); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>