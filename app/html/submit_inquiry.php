<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "../../util/email.php");
header('Content-Type: application/json');

header("Access-Control-Allow-Origin: http://localhost:8081");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");
header("Access-Control-Max-Age: 3600");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if it's form data (multipart/form-data) or JSON
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST; // Regular form submission
}

// Get form data
$parent_name = $data['name'] ?? '';
$phone = $data['phone'] ?? '';
$email = $data['email'] ?? '';
$child_name = $data['childName'] ?? '';
$child_age = $data['age'] ?? 0;
$program = $data['program'] ?? '';
$message = $data['message'] ?? '';
$sessionId = session_id();

// Validate required fields
if (empty($parent_name) || empty($phone) || empty($email) || empty($child_name) || empty($program) || $child_age < 2 || $child_age > 6) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill all required fields correctly. Child age must be between 2-6 years.'
    ]);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Validate phone (Indian phone format)
if (!preg_match('/^[6-9]\d{9}$/', preg_replace('/[^0-9]/', '', $phone))) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit Indian phone number.']);
    exit;
}

// Get additional info
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

try {
    // Insert into database using pg_query_params for security
    $query = "INSERT INTO admission_inquiries 
              (parent_name, phone, email, child_name, child_age, program, message, ip_address, user_agent, session_id) 
              VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10) 
              RETURNING id";

    $result = pg_query_params($con, $query, [
        $parent_name,
        $phone,
        $email,
        $child_name,
        $child_age,
        $program,
        $message,
        $ip_address,
        $user_agent,
        $sessionId
    ]);

    if (!$result) {
        $error = pg_last_error($con);
        error_log("Database error: " . $error);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save your inquiry. Please try again.'
        ]);
        exit;
    }

    // Get the inserted ID
    $row = pg_fetch_assoc($result);
    $inquiry_id = $row['id'];

    // Generate reference number
    $reference_id = 'KBS' . date('Ymd') . str_pad($inquiry_id, 4, '0', STR_PAD_LEFT);

    // // Send email to admissions team
    // $adminEmailQuery = "SELECT email FROM rssimyaccount_members WHERE position IN ('Director') AND filterstatus = 'Active'";
    // $adminEmailResult = pg_query($con, $adminEmailQuery);

    // $admin_emails = [];
    // if ($adminEmailResult && pg_num_rows($adminEmailResult) > 0) {
    //     while ($row = pg_fetch_assoc($adminEmailResult)) {
    //         if (!empty($row['email'])) {
    //             $rawEmails = explode(',', $row['email']);
    //             foreach ($rawEmails as $rawEmail) {
    //                 $rawEmail = trim($rawEmail);
    //                 if (filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
    //                     $admin_emails[] = $rawEmail;
    //                 }
    //             }
    //         }
    //     }
    // }

    // // Remove duplicates
    // $admin_emails = array_unique($admin_emails);

    // // If no admin emails found, use default
    // if (empty($admin_emails)) {
    //     $admin_emails = ['info@rssi.in'];
    // }

    // $toAdminEmails = implode(',', $admin_emails);

    // Prepare email data for parent
    $parentEmailData = [
        "parent_name" => $parent_name,
        "child_name" => $child_name,
        "child_age" => $child_age,
        "program" => $program,
        "reference_id" => $reference_id,
        "timestamp" => date("d/m/Y g:i a"),
        "phone" => $phone,
        "email" => $email,
        "message" => $message,
        "school_phone" => "+91 79801 68159",
        "school_email" => "info@rssi.in",
        "school_address" => "D/1/122, Vinamra Khand, Gomti Nagar, Lucknow, Uttar Pradesh 226010"
    ];

    // Prepare email data for admin
    // $adminEmailData = [
    //     "parent_name" => $parent_name,
    //     "child_name" => $child_name,
    //     "child_age" => $child_age,
    //     "program" => $program,
    //     "reference_id" => $reference_id,
    //     "timestamp" => date("d/m/Y g:i a"),
    //     "parent_phone" => $phone,
    //     "parent_email" => $email,
    //     "message" => $message,
    //     "ip_address" => $ip_address,
    //     "user_agent" => $user_agent,
    //     "session_id" => $sessionId
    // ];

    try {
        // Send confirmation email to parent
        $parentEmailResult = sendEmail("admission_inquiry_confirmation", $parentEmailData, $email);

        // Send notification to admin
        // $adminEmailResult = sendEmail("admission_notification", $adminEmailData, $toAdminEmails, false);

        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your inquiry! We have sent a confirmation email with next steps.',
            'reference_id' => $reference_id,
            'session_id' => $sessionId,
            'email_sent' => true
        ]);
        exit;
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());

        // Even if email fails, database insert was successful
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your inquiry! (Note: Confirmation email could not be sent)',
            'reference_id' => $reference_id,
            'session_id' => $sessionId,
            'email_sent' => false,
            'email_error' => $e->getMessage()
        ]);
        exit;
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.',
        'error' => $e->getMessage()
    ]);
    exit;
}
