<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/email.php");
include("../../util/drive.php");

/* =======================
   STRICT JSON BOOTSTRAP
======================= */
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

date_default_timezone_set('Asia/Kolkata');

/* =======================
   JSON RESPONSE HELPER
======================= */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* =======================
   FORM TYPE VALIDATION
======================= */
$formtype = $_POST['form-type'] ?? null;
if (!$formtype) {
    jsonResponse(['error' => true, 'message' => 'Invalid request'], 400);
}

/* =======================
   EMAIL VERIFY
======================= */
if ($formtype === 'email_verify_signup') {

    $email = strtolower(trim($_POST['email_verify'] ?? ''));

    if (!$email) {
        jsonResponse(['exists' => false]);
    }

    $q = "SELECT COUNT(*) FROM signup WHERE email=$1 AND is_active=true";
    $r = pg_query_params($con, $q, [$email]);

    $count = $r ? (int) pg_fetch_result($r, 0, 0) : 0;
    jsonResponse(['exists' => $count > 0]);
}

/* =======================
   SIGNUP FLOW
======================= */
if ($formtype !== 'signup') {
    jsonResponse(['error' => true, 'message' => 'Invalid form type'], 400);
}

/* =======================
   INPUTS
======================= */
$applicant_name = trim($_POST['applicant-name'] ?? null);
$date_of_birth  = $_POST['date-of-birth'] ?? null;
$gender         = $_POST['gender'] ?? null;
$telephone      = $_POST['telephone'] ?? null;
$email          = $_POST['email'] ?? null;
$branch         = $_POST['branch'] ?? null;
$association    = $_POST['association'] ?? null;
$job_select     = $_POST['job-select'] ?? null;
$purpose        = trim($_POST['purpose'] ?? null);
$interests      = $_POST['interests'] ?? null;
$post_select    = $_POST['post-select'] ?? null;
$membership_purpose = trim($_POST['membershipPurpose'] ?? null);
$subject1 = $_POST['subject1'] ?? null;
$subject2 = $_POST['subject2'] ?? null;
$subject3 = $_POST['subject3'] ?? null;
$heard_about = $_POST['heard_about'] ?? null;
$postal_address = trim($_POST['postal-address'] ?? null);
$permanent_address = trim($_POST['permanent-address'] ?? null);
$education_qualification = $_POST['education-qualification'] ?? null;
$specialization = trim($_POST['specialization'] ?? null);
$work_experience = trim($_POST['work-experience'] ?? null);
$college_name = $_POST['college_name'] ?? null;
$enrollment_number = $_POST['enrollment_number'] ?? null;

$duration = isset($_POST['duration']) && $_POST['duration'] !== ''
    ? (int) $_POST['duration']
    : null;
$availability = !empty($_POST['availability']) ? implode(',', $_POST['availability']) : null;
$medium = !empty($_POST['medium']) ? implode(',', $_POST['medium']) : null;
$consent = !empty($_POST['consent']) ? true : false;
$timestamp = date('Y-m-d H:i:s');

/* =======================
   APPLICATION NUMBER
======================= */
$q = "
SELECT CONCAT(
    TO_CHAR(CURRENT_DATE, 'YYMM'),                       -- Year + Month
    LPAD(NEXTVAL('application_seq')::text, 4, '0'),     -- Sequence padded to 4 digits
    LPAD(FLOOR(RANDOM() * 10000)::int::text, 4, '0')    -- Random 4-digit padding to reach 12 digits
) AS application_number;
  ";
$r = pg_query($con, $q);
$application_number = pg_fetch_result($r, 0, 0);

/* =======================
   PASSWORD
======================= */
$plainPassword = bin2hex(random_bytes(3));
$hashPassword  = password_hash($plainPassword, PASSWORD_DEFAULT);

/* =======================
   FILE UPLOAD HELPER
======================= */
function uploadOrNull($key, $parent, $prefix)
{
    if (empty($_FILES[$key]['name'])) {
        return null;
    }
    return uploadeToDrive($_FILES[$key], $parent, $prefix . '_' . time());
}

/* =======================
   DB TRANSACTION
======================= */
pg_query($con, 'BEGIN');

try {

    $sql = "
        INSERT INTO signup (
            applicant_name, date_of_birth, gender, telephone, email,
            branch, association, job_select, purpose, interests,
            post_select, membership_purpose, heard_about, consent,
            timestamp, application_number, subject1, subject2, subject3,
            password, default_pass_updated_on, postal_address,
            permanent_address, education_qualification, specialization,
            work_experience, application_status, college_name,
            enrollment_number, duration, availability, medium
        ) VALUES (
            $1,$2,$3,$4,$5,$6,$7,$8,$9,$10,
            $11,$12,$13,$14,$15,$16,$17,$18,$19,
            $20,$21,$22,$23,$24,$25,$26,$27,$28,
            $29,$30,$31,$32
        )
    ";

    pg_query_params($con, $sql, [
        $applicant_name,
        $date_of_birth,
        $gender,
        $telephone,
        $email,
        $branch,
        $association,
        $job_select,
        $purpose,
        $interests,
        $post_select,
        $membership_purpose,
        $heard_about,
        $consent,
        $timestamp,
        $application_number,
        $subject1,
        $subject2,
        $subject3,
        $hashPassword,
        $timestamp,
        $postal_address,
        $permanent_address,
        $education_qualification,
        $specialization,
        $work_experience,
        'Application Submitted',
        $college_name,
        $enrollment_number,
        $duration,
        $availability,
        $medium
    ]);

    /* FILE UPLOADS */
    $pay_doc   = uploadOrNull('payment-photo', '1_XqHbekgxQSSwjScG8V8-lL-3l_Spx52', "payment_$application_number");
    $photo_doc = uploadOrNull('applicant-photo', '1gv6JnDX5QTzlcZV-CekoherLdKeriH-A', "photo_$application_number");
    $cv_doc    = uploadOrNull('resume-upload', '1wxt6Q2lIvgWyP0fzMx8cjY5iVImmoxVA', "resume_$application_number");

    pg_query_params($con, "
        UPDATE signup
        SET payment_photo=$1, applicant_photo=$2, resume_upload=$3
        WHERE application_number=$4
    ", [$pay_doc, $photo_doc, $cv_doc, $application_number]);

    pg_query($con, 'COMMIT');
} catch (Throwable $e) {

    pg_query($con, 'ROLLBACK');

    error_log(
        "[" . date('Y-m-d H:i:s') . "] SIGNUP ERROR\n" .
            "Message: " . $e->getMessage() . "\n" .
            "File: " . $e->getFile() . "\n" .
            "Line: " . $e->getLine() . "\n\n",
        3,
        __DIR__ . "/error.log"
    );

    jsonResponse([
        'success' => false,
        'message' => 'Database error'
    ], 500);
}

/* =======================
   EMAIL
======================= */
if (!empty($email)) {
    sendEmail("signup_success", [
        "applicant_name" => $applicant_name,
        "branch" => $branch,
        "association" => $association,
        "application_number" => $application_number,
        "email" => $email,
        "randomPassword" => $plainPassword,
        "timestamp" => date("d/m/Y g:i a", strtotime($timestamp))
    ], $email);
}

/* =======================
   SUCCESS
======================= */
jsonResponse([
    'success' => true,
    'application_number' => $application_number
]);
