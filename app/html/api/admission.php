<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/email.php");
include("../../util/drive.php");

/* =======================
   STRICT JSON BOOTSTRAP
======================= */
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);
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
   BASIC VALIDATION
======================= */
$formtype = $_POST['form-type'] ?? null;
if ($formtype !== 'admission') {
  jsonResponse(['error' => true, 'message' => 'Invalid form type'], 400);
}

/* =======================
   INPUTS
======================= */
$type_of_admission   = $_POST['type-of-admission'] ?? null;
$student_name        = $_POST['student-name'] ?? null;
$date_of_birth       = $_POST['date-of-birth'] ?? null;
$gender              = $_POST['gender'] ?? null;
$aadhar_available    = $_POST['aadhar-card'] ?? null;
$aadhar_card         = $_POST['aadhar-number'] ?? null;
$guardian_name       = $_POST['guardian-name'] ?? null;
$guardian_relation   = $_POST['relation'] ?? null;
$guardian_aadhar     = $_POST['guardian-aadhar-number'] ?? null;
$state_of_domicile   = $_POST['state'] ?? null;
$postal_address      = $_POST['postal-address'] ?? null;
$permanent_address   = $_POST['permanent-address'] ?? null;
$telephone_number    = $_POST['telephone'] ?? null;
$email_address       = $_POST['email'] ?? null;
$preferred_branch    = $_POST['branch'] ?? null;
$class               = $_POST['class'] ?? null;
$school_required     = $_POST['school-required'] ?? null;
$school_name         = $_POST['school-name'] ?? null;
$board_name          = $_POST['board-name'] ?? null;
$medium              = $_POST['medium'] ?? null;
$income              = $_POST['income'] ?? null;
$family_members      = $_POST['family-members'] ?? null;
$payment_mode        = $_POST['payment-mode'] ?? null;
$c_auth_code         = $_POST['c-authentication-code'] ?? null;
$transaction_id      = $_POST['transaction-id'] ?? null;
$subject_select      = $_POST['subject-select'] ?? null;
$timestamp           = $_POST['admission-date'] ?? date('Y-m-d');
$caste               = $_POST['caste'] ?? null;
$c_auth_session_id   = $_POST['c_auth_session_id'] ?? null;

/* =======================
   CASH VERIFICATION
======================= */
$is_verified = false;

if ($payment_mode === 'cash' && $c_auth_session_id) {
  $q = "SELECT is_verified FROM cash_verification_codes WHERE session_id = $1 LIMIT 1";
  $r = pg_query_params($con, $q, [$c_auth_session_id]);
  if ($row = pg_fetch_assoc($r)) {
    $is_verified = $row['is_verified'] === 't';
  }
}

if ($payment_mode === 'cash' && !$is_verified) {
  jsonResponse(['success' => false, 'message' => 'Cash verification failed'], 400);
}

/* =======================
   FILE UPLOADS
======================= */
function uploadOrNull($key, $parent, $prefix)
{
  if (empty($_FILES[$key]['name'])) {
    return null;
  }
  return uploadeToDrive($_FILES[$key], $parent, $prefix . '_' . time());
}

/* =======================
   DATABASE TRANSACTION
======================= */
pg_query($con, 'BEGIN');

try {

  /* STUDENT INSERT (with placeholders for file links) */
  $studentSQL = "
  INSERT INTO rssimyprofile_student (
    type_of_admission, studentname, dateofbirth, gender,
    aadhar_available, studentaadhar,
    guardiansname, relationwithstudent,
    guardianaadhar, stateofdomicile, postaladdress,
    permanentaddress, contact, emailaddress, preferredbranch,
    class, schooladmissionrequired, nameoftheschool,
    nameoftheboard, medium, familymonthlyincome,
    totalnumberoffamilymembers, payment_mode,
    c_authentication_code, transaction_id,
    nameofthesubjects, doa, caste,
    student_id
  ) VALUES (
    $1, $2, $3, $4, $5, $6, $7, $8,
    $9, $10, $11, $12, $13, $14, $15, $16, $17, $18,
    $19, $20, $21, $22, $23, $24,
    $25, $26, $27, $28,
    get_next_student_id()
  )
  RETURNING student_id
";

  $studentParams = [
    $type_of_admission,      // $1
    $student_name,           // $2
    $date_of_birth,          // $3
    $gender,                 // $4
    $aadhar_available,       // $5
    $aadhar_card,            // $6
    $guardian_name,          // $7
    $guardian_relation,      // $8
    $guardian_aadhar,        // $9
    $state_of_domicile,      // $10
    $postal_address,         // $11
    $permanent_address,      // $12
    $telephone_number,       // $13
    $email_address,          // $14
    $preferred_branch,       // $15
    $class,                  // $16
    $school_required,        // $17
    $school_name,            // $18
    $board_name,             // $19
    $medium,                 // $20
    $income,                 // $21
    $family_members,         // $22
    $payment_mode,           // $23
    $c_auth_code,            // $24
    $transaction_id,         // $25
    $subject_select,         // $26
    $timestamp,              // $27
    $caste,                  // $28
    // student_id is generated by get_next_student_id()
  ];

  // Execute student insert and get the returned student_id
  $result = pg_query_params($con, $studentSQL, $studentParams);
  if (!$result) {
    throw new Exception("Failed to insert student record: " . pg_last_error($con));
  }
  $student_id = pg_fetch_result($result, 0, 'student_id');

  // Now upload files only AFTER DB insert
  $doc_student_photo = uploadOrNull('student-photo', '1R1jZmG7xUxX_oaNJaT9gu68IV77zCbg9', "photo_$student_name");
  $doc_aadhar        = uploadOrNull('aadhar-card-upload', '186KMGzX07IohJUhQ72mfHQ6NHiIKV33E', "aadhar_$student_name");
  $doc_caste         = uploadOrNull('caste-document', '186KMGzX07IohJUhQ72mfHQ6NHiIKV33E', "caste_$student_name");
  $doc_support       = uploadOrNull('supporting-document', '1h2elj3V86Y65RFWkYtIXTJFMwG_KX_gC', "sup_$student_name");

  // Update student record with uploaded file links
  $updateSQL = "
    UPDATE rssimyprofile_student
    SET student_photo_raw=$1,
        upload_aadhar_card=$2,
        caste_document=$3,
        supporting_doc=$4
    WHERE student_id=$5
  ";
  pg_query_params($con, $updateSQL, [
    $doc_student_photo,
    $doc_aadhar,
    $doc_caste,
    $doc_support,
    $student_id
  ]);

  /* CATEGORY HISTORY */
  $historySQL = "
    INSERT INTO student_category_history (
      student_id, category_type, effective_from, class, created_by
    ) VALUES ($1, $2, $3, $4, $5)
  ";
  pg_query_params($con, $historySQL, [
    $student_id,
    $type_of_admission,
    $timestamp,
    $class,
    'System'
  ]);

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
   SUCCESS RESPONSE
======================= */
if (!empty($email_address)) {
  sendEmail("admission_success", [
    "student_id" => $student_id,
    "student_name" => $student_name,
    "preferred_branch" => $preferred_branch,
    "doa" => date('d/m/Y', strtotime($timestamp)),
    "timestamp" => date("d/m/Y g:i a")
  ], $email_address);
}

jsonResponse(['success' => true, 'message' => 'Form submitted successfully']);
