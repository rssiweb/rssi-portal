<?php
require_once __DIR__ . '/../bootstrap.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function template($string, $hash) {
    foreach ( $hash as $ind=>$val ) {
        $string = str_replace('{{'.$ind.'}}',$val,$string);
    }   
    $string = preg_replace('/\{\{(.*?)\}\}/is','',$string);
    return $string;
}

function template_file($file,$hash) {
    $string = file_get_contents($file);
    if ($string) {
        $string = template($string,$hash);
    }
    return $string;
}

function sendEmail($template, $data, $email) {
    $mail = new PHPMailer(true);
    $template_name = $template;
    $template_data = $data; 
    $content = template_file('../../email_templates/' . $template_name . '.html', $template_data);
    
    $to_email = $email;
    $lines=explode("\n", $content);

    $subject = $lines[0];
    $alt_body = $lines[1];

    $content = implode("\n", array_slice($lines, 2));

    
    //Server settings
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
    $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = $_ENV["GMAIL_EMAIL"];                    //SMTP username
    $mail->Password   = $_ENV["GMAIL_PASSWORD"];                             //SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
    $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    //Recipients
    $mail->setFrom('info@rssi.in', 'rssi no-reply');
    $mail->addAddress($to_email);     //Add a recipient
    // $mail->addAddress('ellen@example.com');               //Name is optional
    // $mail->addReplyTo('info@example.com', 'Information');
    // $mail->addCC('info@rssi.in');
    $mail->addBCC('info@rssi.in');

    // //Attachments
    // $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
    // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $content;
    $mail->AltBody = $alt_body;

    $mail->send();


}
