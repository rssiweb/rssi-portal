<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/drive.php");

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$response = ['success' => false, 'message' => ''];

try {
    // Validate required fields
    if (empty($_POST['job_id'])) {
        throw new Exception('Job ID is required');
    }

    $jobId = $_POST['job_id'];

    // Check if job exists
    $checkQuery = "SELECT id, job_file_path FROM job_posts WHERE id = $1";
    $checkResult = pg_query_params($con, $checkQuery, [$jobId]);

    if (pg_num_rows($checkResult) === 0) {
        throw new Exception('Job not found');
    }

    $existingJob = pg_fetch_assoc($checkResult);

    // Get form data
    $recruiter_id = $_POST['recruiter_id'] ?? null;
    $job_title = $_POST['job_title'] ?? '';
    $job_type = $_POST['job_type'] ?? '';
    $location = $_POST['location'] ?? '';
    $min_salary = $_POST['min_salary'] ?? 0;
    $max_salary = $_POST['max_salary'] ?? 0;
    $vacancies = $_POST['vacancies'] ?? 1;
    $job_description = $_POST['job_description'] ?? '';
    $requirements = $_POST['requirements'] ?? '';
    $benefits = $_POST['benefits'] ?? null;
    $experience = $_POST['experience'] ?? '';
    $education_levels = $_POST['education_levels'] ?? null;
    $apply_by = $_POST['apply_by'] ?? null;
    $status = $_POST['status'] ?? 'pending';
    $admin_notes = $_POST['admin_notes'] ?? null;

    // Validate required fields
    if (
        empty($recruiter_id) || empty($job_title) || empty($job_type) || empty($location) ||
        empty($job_description) || empty($requirements) || empty($experience) || empty($apply_by)
    ) {
        throw new Exception('All required fields must be filled');
    }

    // Validate salary
    if ($max_salary > 0 && $max_salary < $min_salary) {
        throw new Exception('Maximum salary must be greater than or equal to minimum salary');
    }

    // Validate dates
    $applyByDate = new DateTime($apply_by);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    if ($applyByDate < $today) {
        throw new Exception('Apply by date cannot be in the past');
    }

    // Handle file upload
    $job_file_path = $existingJob['job_file_path']; // Keep existing path by default

    // Check if existing file should be removed
    $removeExistingFile = empty($_POST['existing_file_path']) && !empty($existingJob['job_file_path']);

    if ($removeExistingFile) {
        $job_file_path = null; // Remove file path
    }

    // Check if new file is uploaded
    if (isset($_FILES['job_file']) && $_FILES['job_file']['error'] == UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['job_file'];

        // Check file size (5MB)
        if ($uploadedFile['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size must be less than 5MB');
        }

        // Allow certain file formats
        $allowed_types = ['pdf', 'doc', 'docx'];
        $original_name = basename($uploadedFile['name']);
        $file_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Only PDF, DOC, DOCX files are allowed');
        }

        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $uploadedFile['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        if (!in_array($mime_type, $allowed_mimes)) {
            throw new Exception('Invalid file type');
        }

        // Generate unique filename
        $new_file_name = "job_" . time() . "_" . preg_replace('/[^A-Za-z0-9_-]/', '_', $original_name);

        // Google Drive folder ID for job documents
        $drive_folder_id = 'YOUR_GOOGLE_DRIVE_FOLDER_ID'; // Replace with your folder ID

        // Upload to Google Drive
        $drive_link = uploadeToDrive($uploadedFile, $drive_folder_id, $new_file_name);

        if (!$drive_link) {
            throw new Exception('Failed to upload file to Google Drive');
        }

        $job_file_path = $drive_link;
    }

    // Update job in database
    $updateQuery = "UPDATE job_posts SET 
                    recruiter_id = $1,
                    job_title = $2,
                    job_type = $3,
                    location = $4,
                    min_salary = $5,
                    max_salary = $6,
                    vacancies = $7,
                    job_description = $8,
                    requirements = $9,
                    benefits = $10,
                    experience = $11,
                    education_levels = $12,
                    apply_by = $13,
                    status = $14,
                    job_file_path = $15,
                    admin_notes = $16,
                    updated_at = NOW()
                    WHERE id = $17";

    $params = [
        $recruiter_id,
        $job_title,
        $job_type,
        $location,
        $min_salary,
        $max_salary,
        $vacancies,
        $job_description,
        $requirements,
        $benefits,
        $experience,
        $education_levels,
        $apply_by,
        $status,
        $job_file_path,
        $admin_notes,
        $jobId
    ];

    $result = pg_query_params($con, $updateQuery, $params);

    if (!$result) {
        throw new Exception('Database update failed: ' . pg_last_error($con));
    }

    $response['success'] = true;
    $response['message'] = 'Job updated successfully';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
