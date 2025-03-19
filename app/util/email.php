<?php
require_once __DIR__ . '/../bootstrap.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// function template($string, $hash)
// {
//     foreach ($hash as $ind => $val) {
//         @$string = str_replace('{{' . $ind . '}}', $val, $string);
//     }
//     $string = preg_replace('/\{\{(.*?)\}\}/is', '', $string);
//     return $string;
// }

function template($string, $hash)
{
    foreach ($hash as $ind => $val) {
        // Handle arrays (e.g., uploaded_files)
        if (is_array($val)) {
            $replacement = ''; // Initialize an empty string for array replacement
            foreach ($val as $item) {
                if (is_array($item)) {
                    // If the item is an array, convert it to a string (e.g., for uploaded_files)
                    $replacement .= implode(', ', $item) . "\n";
                } else {
                    // If the item is a string, append it directly
                    $replacement .= $item . "\n";
                }
            }
            $string = str_replace('{{' . $ind . '}}', $replacement, $string);
        } else {
            // Handle non-array values (existing behavior)
            // Add null check to avoid passing null to str_replace
            if ($val !== null) {
                $string = str_replace('{{' . $ind . '}}', $val, $string);
            }
        }
    }
    // Remove any remaining placeholders
    $string = preg_replace('/\{\{(.*?)\}\}/is', '', $string);
    return $string;
}

function template_file($file, $hash)
{
    $string = file_get_contents($file);
    if ($string) {
        $string = template($string, $hash);
    }
    return $string;
}

function sendEmail($template, $data, $emails, $bcc = true)
{
    $mail = new PHPMailer(true);

    try {
        // Load email template
        $template_name = $template;
        $template_data = $data;
        $content = template_file(__DIR__ . '/../email_templates/' . $template_name . '.html', $template_data);

        // Split template content into subject, alt body, and main body
        $lines = explode("\n", $content);
        $subject = $lines[0];  // First line is the subject
        $alt_body = $lines[1]; // Second line is the plain text version
        $content = implode("\n", array_slice($lines, 2));  // Rest is HTML body

        // Set up PHPMailer server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV["GMAIL_EMAIL"];
        $mail->Password   = $_ENV["GMAIL_PASSWORD"];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Set from address
        $mail->setFrom('info@rssi.in', 'RSSI No-Reply');

        // Check if emails is a comma-separated string, and convert it into an array
        if (is_string($emails)) {
            $emails = array_map('trim', explode(',', $emails));
        }

        // Add recipients (after converting to array)
        foreach ($emails as $email) {
            if (!empty($email)) {
                $mail->addAddress($email);  // Add each email individually
            }
        }

        // Optional BCC
        if ($bcc) {
            $mail->addBCC('info@rssi.in');
        }

        // Email content setup
        $mail->isHTML(true);
        $mail->Subject = $subject;  // Set email subject
        $mail->Body    = $content;  // Set HTML body
        $mail->AltBody = $alt_body; // Set plain-text body

        // Send email
        $mail->send();
        // echo 'Message has been sent to all recipients';

    } catch (Exception $e) {
        // Output any errors
        // echo 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
    }
}
