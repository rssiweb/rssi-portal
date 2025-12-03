<?php
require_once __DIR__ . "/../bootstrap.php";
include("../util/email.php");

header('Content-Type: application/json');

try {
    $email = $_POST['email'] ?? '';
    $name = $_POST['name'] ?? '';
    $job_title = $_POST['job_title'] ?? '';
    
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
        "application_date" => date("d/m/Y g:i a")
    ];
    
    // Send email
    $result = sendEmail("job_application_confirmation", $emailData, $email, false);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Confirmation email sent'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send email'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>