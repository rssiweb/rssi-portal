<?php
// Set error reporting - Add this at the VERY TOP
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/email.php");

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

// Increase upload limits for file uploads
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');

$response = ['success' => false, 'message' => ''];

try {
    // Clear any previous output
    ob_clean();

    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method. Use POST.');
    }

    // Check if required fields are set
    if (empty($_POST['email'])) {
        throw new Exception('Email is required');
    }

    $email = $_POST['email'] ?? '';
    $job_title = $_POST['job_title'] ?? '';
    $job_type = $_POST['job_type'] ?? '';
    $location = $_POST['location'] ?? '';
    $salary = $_POST['salary'] ?? '';
    $job_description = $_POST['job_description'] ?? '';
    $requirements = $_POST['requirements'] ?? '';
    $experience = $_POST['experience'] ?? '';
    $vacancies = $_POST['vacancies'] ?? '';
    $apply_by = $_POST['apply_by'] ?? '';

    error_log("Job post attempt - Email: $email, Job Title: $job_title");

    // Validation
    $required_fields = [
        'email' => 'Email',
        'job_title' => 'Job Title',
        'job_type' => 'Job Type',
        'location' => 'Location',
        'job_description' => 'Job Description',
        'requirements' => 'Requirements',
        'vacancies' => 'Number of Vacancies',
        'apply_by' => 'Apply By Date'
    ];

    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            throw new Exception($label . ' is required');
        }
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate apply_by date
    $apply_by_date = DateTime::createFromFormat('Y-m-d', $apply_by);
    if (!$apply_by_date) {
        throw new Exception('Invalid date format for Apply By. Use YYYY-MM-DD');
    }

    $today = new DateTime();
    if ($apply_by_date < $today) {
        throw new Exception('Apply by date must be in the future');
    }

    // Validate vacancies
    if (!is_numeric($vacancies) || $vacancies < 1) {
        throw new Exception('Number of vacancies must be at least 1');
    }

    // Get recruiter ID
    $query = "SELECT id, full_name, company_name FROM recruiters WHERE email = $1 AND is_verified = true";
    error_log("Checking recruiter with email: $email");

    $result = pg_query_params($con, $query, [$email]);

    if (!$result) {
        $error = pg_last_error($con);
        error_log("Database error checking recruiter: " . $error);
        throw new Exception('Database error. Please try again.');
    }

    if (pg_num_rows($result) == 0) {
        error_log("No verified recruiter found for email: $email");
        throw new Exception('Recruiter not found or not verified. Please complete registration first.');
    }

    $recruiter = pg_fetch_assoc($result);
    $recruiter_id = $recruiter['id'];
    $recruiter_name = $recruiter['full_name'];
    $company_name = $recruiter['company_name'];

    error_log("Recruiter found - ID: $recruiter_id, Name: $recruiter_name, Company: $company_name");

    // Handle job file upload
    $job_file_path = '';
    if (isset($_FILES['job_file']) && $_FILES['job_file']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/jobs/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                error_log("Failed to create jobs upload directory: $upload_dir");
                // Don't throw exception, continue without file
            }
        }

        if (file_exists($upload_dir)) {
            $original_name = basename($_FILES['job_file']['name']);
            $file_name = time() . '_' . preg_replace('/[^A-Za-z0-9\._-]/', '_', $original_name);
            $target_file = $upload_dir . $file_name;

            // Check file size (5MB)
            if ($_FILES['job_file']['size'] > 5242880) {
                throw new Exception('File size must be less than 5MB');
            }

            // Allow certain file formats
            $allowed_types = ['pdf', 'doc', 'docx'];
            $file_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Only PDF, DOC, DOCX files are allowed');
            }

            // Check MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['job_file']['tmp_name']);
            finfo_close($finfo);

            $allowed_mimes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (!in_array($mime_type, $allowed_mimes)) {
                throw new Exception('Invalid file type');
            }

            if (move_uploaded_file($_FILES['job_file']['tmp_name'], $target_file)) {
                $job_file_path = 'uploads/jobs/' . $file_name;
                error_log("Job file uploaded successfully: " . $job_file_path);
            } else {
                $upload_error = $_FILES['job_file']['error'];
                error_log("Job file upload failed with error code: " . $upload_error);
                // Don't throw exception, continue without file
            }
        }
    } elseif (isset($_FILES['job_file'])) {
        $upload_error = $_FILES['job_file']['error'];
        if ($upload_error != UPLOAD_ERR_NO_FILE) {
            error_log("Job file upload error: " . $upload_error);
        }
    }

    // Insert job post
    $query = "INSERT INTO job_posts (recruiter_id, job_title, job_type, location, salary, job_description, requirements, experience, vacancies, apply_by, job_file_path) 
              VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11) RETURNING id";

    error_log("Executing job insert query");

    $result = pg_query_params($con, $query, [
        $recruiter_id,
        $job_title,
        $job_type,
        $location,
        $salary,
        $job_description,
        $requirements,
        $experience,
        $vacancies,
        $apply_by,
        $job_file_path
    ]);

    if (!$result) {
        $error = pg_last_error($con);
        error_log("Database insert error: " . $error);
        throw new Exception('Failed to post job: Database error - ' . $error);
    }

    $job_id = pg_fetch_result($result, 0, 0);
    error_log("Job posted successfully with ID: $job_id");

    // Send confirmation email to recruiter
    try {
        $recruiterEmailData = [
            "recruiter_name" => $recruiter_name,
            "job_title" => $job_title,
            "company_name" => $company_name,
            "job_id" => $job_id,
            "post_date" => date("d/m/Y"),
            "apply_by" => date("d/m/Y", strtotime($apply_by)),
            "timestamp" => date("d/m/Y g:i a"),
            "vacancies" => $vacancies,
            "location" => $location
        ];

        $emailResult = sendEmail("job_post_confirmation", $recruiterEmailData, $email, false);
        error_log("Confirmation email sent to recruiter: $email");
    } catch (Exception $e) {
        error_log("Email sending to recruiter failed: " . $e->getMessage());
        // Don't fail job post if email fails
    }

    // Send notification to admin for approval
    try {
        // Use the same logic as your support system - check position column
        $adminQuery = "SELECT email FROM rssimyaccount_members WHERE position IN ('Director', 'Chief Human Resources Officer') AND filterstatus = 'Active'";
        $adminResult = pg_query($con, $adminQuery);

        if ($adminResult && pg_num_rows($adminResult) > 0) {
            $adminEmails = [];
            while ($row = pg_fetch_assoc($adminResult)) {
                if (!empty($row['email'])) {
                    $rawEmails = explode(',', $row['email']);
                    foreach ($rawEmails as $rawEmail) {
                        $rawEmail = trim($rawEmail);
                        if (filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
                            $adminEmails[] = $rawEmail;
                        }
                    }
                }
            }

            if (!empty($adminEmails)) {
                $adminEmailList = implode(',', $adminEmails);

                $adminEmailData = [
                    "recruiter_name" => $recruiter_name,
                    "company_name" => $company_name,
                    "email" => $email,
                    "job_title" => $job_title,
                    "job_id" => $job_id,
                    "post_date" => date("d/m/Y g:i a"),
                    "apply_by" => date("d/m/Y", strtotime($apply_by)),
                    "vacancies" => $vacancies,
                    "location" => $location,
                    "job_type" => ucfirst(str_replace('-', ' ', $job_type)),
                    "salary" => $salary ? "₹$salary per month" : "Not specified",
                    "approval_link" => "https://admin.rssi.in/job-approval.php?id=$job_id"
                ];

                // This will send to ALL admin emails at once (comma-separated)
                sendEmail("job_post_admin_notification", $adminEmailData, $adminEmailList, false);
                error_log("Notification email sent to " . count($adminEmails) . " admin(s): " . $adminEmailList);
            }
        }
    } catch (Exception $e) {
        error_log("Admin email notification failed: " . $e->getMessage());
        // Don't fail job post if admin email fails
    }

    $response['success'] = true;
    $response['message'] = 'Job posted successfully. It is now pending approval.';
    $response['job_id'] = $job_id;
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Job post error: " . $e->getMessage());
}

// Clean any output before sending JSON
ob_clean();
echo json_encode($response);

// End output buffering
ob_end_flush();
