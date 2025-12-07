<?php
require_once __DIR__ . "/../../bootstrap.php";

header('Content-Type: application/json');

try {
    $applicant_id = $_POST['applicant_id'] ?? 0;
    $job_id = $_POST['job_id'] ?? 0;

    if (!$applicant_id || !$job_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Applicant ID and Job ID are required'
        ]);
        exit;
    }

    // Check if already applied
    $check_query = "SELECT id FROM job_applications 
                    WHERE job_seeker_id = $1 
                    AND job_id = $2 
                    LIMIT 1";
    $check_result = pg_query_params($con, $check_query, [$applicant_id, $job_id]);

    if ($check_result && pg_num_rows($check_result) > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'You have already applied for this job'
        ]);
        exit;
    }

    // Insert application
    $query = "INSERT INTO job_applications 
              (job_seeker_id, job_id, application_date, status) 
              VALUES ($1, $2, NOW(), 'Applied') 
              RETURNING id";

    $result = pg_query_params($con, $query, [$applicant_id, $job_id]);

    if ($result) {
        // Get applicant and job details for email
        $details_query = "SELECT jsd.name, jsd.email, jp.job_title 
                          FROM job_seeker_data jsd 
                          CROSS JOIN job_posts jp 
                          WHERE jsd.id = $1 AND jp.id = $2 
                          LIMIT 1";
        $details_result = pg_query_params($con, $details_query, [$applicant_id, $job_id]);

        $applicant_name = '';
        $email = '';
        $job_title = '';

        if ($details_result && pg_num_rows($details_result) > 0) {
            $details = pg_fetch_assoc($details_result);
            $applicant_name = $details['name'];
            $email = $details['email'];
            $job_title = $details['job_title'];
        }

        // Commit transaction
        pg_query($con, "COMMIT");

        // Try to send email if email is provided
        $email_sent = false;
        $email_message = '';

        if (!empty($email)) {
            include("../../util/email.php");

            $emailData = [
                "applicant_name" => $applicant_name,
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
            'message' => 'Application submitted successfully',
            'applicant_name' => $applicant_name,
            'email' => $email,
            'job_title' => $job_title,
            'job_id' => $job_id  // Add this line
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to submit application'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
