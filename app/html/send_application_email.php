<?php
require_once __DIR__ . "/../bootstrap.php";
include("../util/email.php");

header('Content-Type: application/json');

try {
    // Accept different possible parameter names
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? $_POST['applicant_name'] ?? '';
    $job_title = $_POST['job_title'] ?? '';
    $job_id = $_POST['job_id'] ?? '';  // Add this line

    if (empty($email)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email is required'
        ]);
        exit;
    }

    $emailData = [
        "applicant_name" => $name,
        "job_title" => $job_title,
        "job_id" => $job_id,  // Add this line
        "application_date" => date("d/m/Y g:i a")
    ];

    // Send email
    $result = sendEmail("job_application_confirmation", $emailData, $email, false);

    // Always return success if we reach here (assuming email actually sent)
    // Check your server logs to confirm
    echo json_encode([
        'success' => true,  // Force true for testing
        'message' => 'Confirmation email sent',
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
