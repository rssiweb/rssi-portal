<?php
// Set error reporting - Add this at the VERY TOP
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);

// Start output buffering to catch any stray output
ob_start();

require_once __DIR__ . "/../../bootstrap.php";
include("../../util/email.php");
include("../../util/drive.php");

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean(); // Clean any output
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

    // Get form data
    $email = $_POST['email'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $company_name = $_POST['company_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $aadhar_number = trim($_POST['aadhar_number'] ?? '');
    // Convert empty string to NULL for admin-created recruiters
    if (empty(trim($aadhar_number))) {
        $aadhar_number = null;
    }
    $company_address = $_POST['company_address'] ?? '';
    $created_by = $_POST['created_by'] ?? null;  // Safe default
    $notes = $_POST['notes'] ?? null;  // Optional

    // Debug log
    error_log("Registration attempt - Email: $email, Name: $full_name");

    // Validation
    // Common required fields
    if (
        empty($email) ||
        empty($full_name) ||
        empty($company_name) ||
        empty($phone) ||
        empty($company_address)
    ) {
        throw new Exception('All fields are required');
    }

    // Aadhar validation
    // Required ONLY when created_by is NOT 'admin'
    if ($created_by !== 'admin') {

        // Aadhar is required
        if (empty($aadhar_number)) {
            throw new Exception('Aadhar number is required');
        }

        // Validate format
        if (!preg_match('/^[0-9]{12}$/', $aadhar_number)) {
            throw new Exception('Aadhar number must be 12 digits');
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if email already registered
    $checkQuery = "SELECT id FROM recruiters WHERE email = $1";
    $checkResult = pg_query_params($con, $checkQuery, [$email]);

    if (!$checkResult) {
        $error = pg_last_error($con);
        error_log("Database error checking email: " . $error);
        throw new Exception('Database error. Please try again.');
    }

    if (pg_num_rows($checkResult) > 0) {
        throw new Exception('Email already registered');
    }

    // Check if Aadhar already registered (only if aadhar is provided)
    if (!empty(trim($aadhar_number ?? ''))) {
        $checkAadharQuery = "SELECT id FROM recruiters WHERE aadhar_number = $1";
        $checkAadharResult = pg_query_params($con, $checkAadharQuery, [$aadhar_number]);

        if (!$checkAadharResult) {
            $error = pg_last_error($con);
            error_log("Database error checking Aadhar: " . $error);
            throw new Exception('Database error. Please try again.');
        }

        if (pg_num_rows($checkAadharResult) > 0) {
            throw new Exception('Aadhar number already registered');
        }
    }

    // Handle Aadhar file upload to Google Drive
    $aadhar_drive_link = null;
    $aadhar_file_name = null;

    // Check if file is uploaded
    $fileUploaded = isset($_FILES['aadhar_file']) && $_FILES['aadhar_file']['error'] == UPLOAD_ERR_OK;

    // If NOT admin → Aadhar file is required
    if ($created_by !== 'admin' && !$fileUploaded) {
        $upload_error = $_FILES['aadhar_file']['error'] ?? 'No file uploaded';
        throw new Exception('Aadhar file is required. Error: ' . $upload_error);
    }

    if ($fileUploaded) {
        $uploadedFile = $_FILES['aadhar_file'];

        // Check file size (2MB)
        if ($uploadedFile['size'] > 2097152) {
            throw new Exception('File size must be less than 2MB');
        }

        // Allow certain file formats
        $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
        $original_name = basename($uploadedFile['name']);
        $file_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Only PDF, JPG, JPEG, PNG files are allowed');
        }

        // Check MIME type for extra security
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $uploadedFile['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png'
        ];

        if (!in_array($mime_type, $allowed_mimes)) {
            throw new Exception('Invalid file type');
        }

        // Sanitize filename for Google Drive
        $aadhar_file_name = "aadhar_" . time() . "_" . substr($aadhar_number, -4) . "_" .
            preg_replace('/[^A-Za-z0-9_-]/', '_', strstr($original_name, '.', true) ?: $original_name);

        // Google Drive folder ID for Aadhar documents (create this folder in your Drive)
        // Replace with your actual Google Drive folder ID
        $drive_folder_id = '1LqQxq0V9DW6sRhlJL6ZW93eQHbV3OoUP';

        // Upload to Google Drive
        $aadhar_drive_link = uploadeToDrive($uploadedFile, $drive_folder_id, $aadhar_file_name);

        if (!$aadhar_drive_link) {
            throw new Exception('Failed to upload Aadhar file to Google Drive. Please try again.');
        }

        error_log("Aadhar file uploaded to Google Drive: " . $aadhar_drive_link);
    }

    // Generate random password (6 characters: letters and numbers mixed)
    $defaultPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

    // Insert into database using pg_query_params for security
    $query = "INSERT INTO recruiters (email, full_name, company_name, phone, aadhar_number, aadhar_file_path, company_address, password, notes, default_pass_updated_on, is_verified) 
              VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, true) RETURNING id";

    error_log("Executing query: $query");

    $result = pg_query_params($con, $query, [
        $email,
        $full_name,
        $company_name,
        $phone,
        $aadhar_number,
        $aadhar_drive_link, // Store Google Drive link instead of local path
        $company_address,
        $hashedPassword,  // Add this line
        $notes,
        date("Y-m-d H:i:s")
    ]);

    if (!$result) {
        $error = pg_last_error($con);
        error_log("Database insert error: " . $error);
        throw new Exception('Registration failed: Database error - ' . $error);
    }

    $recruiter_id = pg_fetch_result($result, 0, 0);
    error_log("Recruiter registered successfully with ID: $recruiter_id");

    // Send confirmation email
    try {
        $emailData = [
            "user_name" => $full_name,
            "user_email" => $email,
            "company_name" => $company_name,
            "registration_date" => date("d/m/Y"),
            "timestamp" => date("d/m/Y g:i a"),
            "recruiter_id" => $recruiter_id,
            "temp_password" => $defaultPassword  // Add this line
        ];

        $emailResult = sendEmail("recruiter_registration", $emailData, $email, false);
        error_log("Confirmation email sent to: $email");
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        // Don't fail registration if email fails
    }

    $response['success'] = true;
    $response['message'] = 'Registration successful';
    $response['recruiter_id'] = $recruiter_id;
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Registration error: " . $e->getMessage());
}

// Clean any output before sending JSON
ob_clean();
echo json_encode($response);

// End output buffering
ob_end_flush();
