<?php
require_once __DIR__ . "/../bootstrap.php";

header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$phone = $_GET['phone'] ?? '';
$job_id = $_GET['job_id'] ?? '';
$job_title = $_GET['job_title'] ?? '';

// Sanitize inputs
$phone = htmlspecialchars($phone);
$job_title = htmlspecialchars($job_title);

// Fetch education levels from database
$education_levels = [];

try {
    $query = "SELECT id, name FROM education_levels WHERE status = 'Active' ORDER BY sort_order";
    $result = pg_query($con, $query);

    if ($result) {
        $education_levels = pg_fetch_all($result) ?: [];
    }
} catch (Exception $e) {
    error_log("Error fetching education levels: " . $e->getMessage());
}

// Generate education options HTML
$education_options = '';
foreach ($education_levels as $edu) {
    $education_options .= sprintf(
        '<option value="%s">%s</option>',
        htmlspecialchars($edu['id']),
        htmlspecialchars($edu['name'])
    );
}

// Return the form HTML
?>
<div class="modal fade" id="registrationModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Apply for: <?php echo $job_title; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Please provide your details to complete the application.</p>

                <form id="registrationForm" enctype="multipart/form-data">
                    <input type="hidden" name="phone" value="<?php echo $phone; ?>">
                    <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="age" class="form-label">Age *</label>
                            <input type="number" class="form-control" id="age" name="age" min="18" max="70" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="education" class="form-label">Education Level *</label>
                            <select class="form-select" id="education" name="education" required>
                                <option value="">Select Education</option>
                                <?php echo $education_options; ?>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="skills" class="form-label">Skills *</label>
                            <textarea class="form-control" id="skills" name="skills" rows="3"
                                placeholder="List your skills (e.g., Python, HTML, Communication)" required></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="preferences" class="form-label">Job Preferences</label>
                            <textarea class="form-control" id="preferences" name="preferences" rows="2"
                                placeholder="Any specific preferences or requirements"></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"
                                placeholder="Your current address"></textarea>
                        </div>

                        <!-- Resume Upload Section - Single PDF File Only -->
                        <div class="col-12 mb-3">
                            <label class="form-label">Upload Resume (PDF only, max 5MB) *</label>
                            <div class="file-upload-area">
                                <input type="file" class="form-control" id="resumeFile" name="resume"
                                    accept=".pdf">
                                <div class="form-text">
                                    <i class="bi bi-info-circle"></i> Upload a single PDF file (Max 5MB)
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelBtn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="submitApplicationBtn">
                    <i class="bi bi-send-check me-2"></i> Submit Application
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Simple file validation for single PDF file
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('resumeFile');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (file) {
                // Validate file type
                if (file.type !== 'application/pdf') {
                    alert('Please upload a PDF file only.');
                    fileInput.value = '';
                    return;
                }

                // Validate file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size should be less than 5MB.');
                    fileInput.value = '';
                    return;
                }

                console.log('File selected:', file.name, 'Size:', formatFileSize(file.size));
            }
        });

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    });
</script>