<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

// Fetch approved active jobs
$query = "SELECT jp.*, r.company_name, r.company_address 
          FROM job_posts jp 
          JOIN recruiters r ON jp.recruiter_id = r.id 
          WHERE jp.status = 'approved' 
          AND jp.apply_by >= CURRENT_DATE
          ORDER BY jp.created_at DESC";

$result = pg_query($con, $query);
$jobs = $result ? pg_fetch_all($result) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Opportunities - RSSI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .job-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .job-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .job-type-badge {
            font-size: 0.8em;
            padding: 5px 10px;
            border-radius: 20px;
            background-color: #e7f3ff;
            color: #0d6efd;
        }
        .urgent-badge {
            background-color: #f8d7da;
            color: #721c24;
        }
        .salary-text {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Include your header/navigation here -->
    <?php include 'header.php'; ?>

    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-center mb-3">Job Opportunities</h1>
                <p class="text-center text-muted">Find your dream job from our verified recruiters</p>
                
                <!-- Search and Filter -->
                <div class="row justify-content-center mb-4">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search jobs by title, company, or location..." id="jobSearch">
                            <button class="btn btn-primary" type="button">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($jobs)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <h4>No jobs available at the moment</h4>
                        <p>Check back later for new opportunities!</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($jobs as $job): 
                    // Check if job is urgent (less than 3 days to apply)
                    $days_to_apply = floor((strtotime($job['apply_by']) - time()) / (60 * 60 * 24));
                    $is_urgent = $days_to_apply <= 3;
                ?>
                <div class="col-lg-6 mb-3">
                    <div class="job-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4 class="mb-1"><?php echo htmlspecialchars($job['job_title']); ?></h4>
                                <h6 class="text-muted mb-2">
                                    <i class="bi bi-building"></i> <?php echo htmlspecialchars($job['company_name']); ?>
                                </h6>
                            </div>
                            <?php if ($is_urgent): ?>
                                <span class="job-type-badge urgent-badge">
                                    <i class="bi bi-clock"></i> Apply Soon
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <span class="job-type-badge me-2">
                                <?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?>
                            </span>
                            <span class="text-muted">
                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($job['location']); ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <p class="mb-1">
                                <i class="bi bi-people"></i> 
                                <strong>Vacancies:</strong> <?php echo $job['vacancies']; ?> position(s)
                            </p>
                            <?php if (!empty($job['salary'])): ?>
                            <p class="mb-1 salary-text">
                                <i class="bi bi-cash"></i> 
                                ₹<?php echo number_format($job['salary']); ?> per month
                            </p>
                            <?php endif; ?>
                            <p class="mb-1">
                                <i class="bi bi-calendar-check"></i> 
                                <strong>Apply by:</strong> <?php echo date('d/m/Y', strtotime($job['apply_by'])); ?>
                                <?php if ($is_urgent): ?>
                                    <small class="text-danger">(<?php echo $days_to_apply; ?> days left)</small>
                                <?php endif; ?>
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-briefcase"></i> 
                                <strong>Experience:</strong> <?php echo $job['experience'] ? htmlspecialchars($job['experience']) : 'Not specified'; ?>
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Job Description:</h6>
                            <p><?php echo nl2br(htmlspecialchars(substr($job['job_description'], 0, 150))) . '...'; ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Requirements:</h6>
                            <p><?php echo nl2br(htmlspecialchars(substr($job['requirements'], 0, 100))) . '...'; ?></p>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="job_details.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#applyModal<?php echo $job['id']; ?>">
                                <i class="bi bi-send"></i> Apply Now
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Application Modal (will be populated via JavaScript/AJAX) -->
    <div class="modal fade" id="applyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Apply for Job</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="applyModalBody">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Job search functionality
            $('#jobSearch').on('keyup', function() {
                var searchText = $(this).val().toLowerCase();
                $('.job-card').each(function() {
                    var jobText = $(this).text().toLowerCase();
                    $(this).toggle(jobText.indexOf(searchText) > -1);
                });
            });

            // Handle apply button click
            $('.apply-btn').on('click', function() {
                var jobId = $(this).data('job-id');
                var jobTitle = $(this).data('job-title');
                var companyName = $(this).data('company-name');
                
                // Set modal title
                $('#applyModal .modal-title').text('Apply for: ' + jobTitle);
                
                // Load application form via AJAX
                $.ajax({
                    url: 'application_form.php',
                    method: 'GET',
                    data: { job_id: jobId },
                    success: function(response) {
                        $('#applyModalBody').html(response);
                    },
                    error: function() {
                        $('#applyModalBody').html('<div class="alert alert-danger">Error loading application form. Please try again.</div>');
                    }
                });
            });
        });
    </script>
</body>
</html>