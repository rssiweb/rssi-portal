<?php
require_once __DIR__ . "/../bootstrap.php";

header('Content-Type: application/json');

try {
    // Get form data
    $phone = $_POST['phone'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $education = $_POST['education'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $preferences = $_POST['preferences'] ?? '';
    $address = $_POST['address'] ?? '';
    $job_id = $_POST['job_id'] ?? 0;

    // Initialize resume variables
    $resume_drive_link = null;
    $resume_file_name = null;

    // Validate required fields
    if (empty($phone) || empty($name) || empty($dob) || empty($education) || empty($skills) || empty($job_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'All required fields must be filled'
        ]);
        exit;
    }

    // Validate email format if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format'
        ]);
        exit;
    }

    // Handle Resume file upload to Google Drive - OPTIONAL
    $resume_drive_link = null; // Initialize as null

    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['resume'];

        // Check file size (5MB)
        if ($uploadedFile['size'] > 5 * 1024 * 1024) {
            throw new Exception('Resume file size must be less than 5MB');
        }

        // Allow only PDF files
        $allowed_types = ['pdf'];
        $original_name = basename($uploadedFile['name']);
        $file_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Only PDF files are allowed for resume');
        }

        // Check MIME type for extra security
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $uploadedFile['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = ['application/pdf'];

        if (!in_array($mime_type, $allowed_mimes)) {
            throw new Exception('Invalid file type. Only PDF files are allowed.');
        }

        // Sanitize filename for Google Drive
        $resume_file_name = "resume_" . time() . "_" . substr($phone, -4) . "_" .
            preg_replace('/[^A-Za-z0-9_-]/', '_', strstr($original_name, '.', true) ?: $original_name);

        // Google Drive folder ID for Resumes
        $drive_folder_id = '18uRvl5MibzbL2FBa9X9mmXqydsqbVQad';

        // Upload to Google Drive
        if (!function_exists('uploadeToDrive')) {
            include(__DIR__ . "/../util/drive.php");
        }

        $resume_drive_link = uploadeToDrive($uploadedFile, $drive_folder_id, $resume_file_name);

        if (!$resume_drive_link) {
            throw new Exception('Failed to upload resume to Google Drive. Please try again.');
        }

        error_log("Resume file uploaded to Google Drive: " . $resume_drive_link);
    } elseif (isset($_FILES['resume']) && $_FILES['resume']['error'] != UPLOAD_ERR_NO_FILE) {
        // If there's a file upload error (not just "no file" error), throw exception
        $upload_error = $_FILES['resume']['error'];
        throw new Exception('Resume upload failed. Error: ' . $upload_error);
    }
    // If no resume uploaded (UPLOAD_ERR_NO_FILE), $resume_drive_link remains null

    // Start transaction
    pg_query($con, "BEGIN");

    // Insert into job_seeker_data WITH RESUME
    $seeker_query = "INSERT INTO job_seeker_data 
                     (name, contact, email, dob, education, skills, preferences, address1, resume, status, created_at) 
                     VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, 'Active', NOW()) 
                     RETURNING id";

    $seeker_result = pg_query_params($con, $seeker_query, [
        $name,
        $phone,
        $email,
        $dob,
        $education,
        $skills,
        $preferences,
        $address,
        $resume_drive_link  // Store the Google Drive link in resume column
    ]);

    if (!$seeker_result) {
        pg_query($con, "ROLLBACK");
        error_log("Failed to save applicant data: " . pg_last_error($con));
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save applicant data: ' . pg_last_error($con)
        ]);
        exit;
    }

    $seeker_row = pg_fetch_assoc($seeker_result);
    $seeker_id = $seeker_row['id'];

    // Insert into job_applications
    $application_query = "INSERT INTO job_applications 
                          (job_seeker_id, job_id, application_date, status) 
                          VALUES ($1, $2, NOW(), 'Applied') 
                          RETURNING id";

    $application_result = pg_query_params($con, $application_query, [$seeker_id, $job_id]);

    if (!$application_result) {
        pg_query($con, "ROLLBACK");
        error_log("Failed to submit application: " . pg_last_error($con));
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit application'
        ]);
        exit;
    }

    // Get job title for email
    $job_query = "SELECT job_title FROM job_posts WHERE id = $1";
    $job_result = pg_query_params($con, $job_query, [$job_id]);
    $job_title = '';
    if ($job_result && pg_num_rows($job_result) > 0) {
        $job = pg_fetch_assoc($job_result);
        $job_title = $job['job_title'];
    }

    // Commit transaction
    pg_query($con, "COMMIT");

    // Try to send email if email is provided
    $email_sent = false;
    $email_message = '';

    if (!empty($email)) {
        include_once(__DIR__ . "/../util/email.php");

        $emailData = [
            "applicant_name" => $name,
            "job_title" => $job_title,
            "job_id" => $job_id,
            "application_date" => date("d/m/Y g:i a")
        ];

        try {
            $email_result = sendEmail("job_application_confirmation", $emailData, $email);
            $email_sent = true;
            $email_message = 'Confirmation email sent.';
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            $email_message = 'Application submitted but email sending failed.';
        }
    } else {
        $email_message = 'Application submitted. No email provided for confirmation.';
    }

    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully! ' . $email_message,
        'applicant_id' => $seeker_id,
        'applicant_name' => $name,
        'email' => $email,
        'job_title' => $job_title,
        'job_id' => $job_id,
        'email_sent' => $email_sent,
        'resume_uploaded' => !empty($resume_drive_link)
    ]);
} catch (Exception $e) {
    // Rollback transaction if started
    pg_query($con, "ROLLBACK");
    error_log("Exception in submit_application.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
