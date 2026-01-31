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
  echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
  exit;
}

/* =======================
   INPUT VALIDATION
======================= */
$formtype = $_POST['form-type'] ?? null;

if (!$formtype) {
  jsonResponse(['error' => true, 'message' => 'Invalid request'], 400);
}

/* =======================
   GET DETAILS
======================= */
if ($formtype === "get_details") {

  $contactnumber = $_POST['contactnumber_verify_input'] ?? null;

  if (!$contactnumber) {
    jsonResponse(['error' => true, 'message' => 'Contact number missing'], 400);
  }

  $sql = "SELECT fullname, email, tel FROM donation_userdata WHERE tel = $1";
  $result = pg_query_params($con, $sql, [$contactnumber]);

  if (!$result) {
    jsonResponse(['error' => true, 'message' => 'Database error'], 500);
  }

  $row = pg_fetch_assoc($result);

  if ($row) {
    jsonResponse(['status' => 'success', 'data' => $row]);
  }

  jsonResponse([
    'status' => 'no_records',
    'message' => 'No records found. Donate as a new user.'
  ]);
}

/* =======================
   DONATION FORM
======================= */
if ($formtype === "donation_form") {

  $donationType   = $_POST['donationType'] ?? null;
  $currency       = $_POST['currency'] ?? null;
  $transactionId  = $_POST['transactionid'] ?? null;
  $donationAmount = $_POST['donationAmount'] ?? null;
  $message        = !empty($_POST['message'])
    ? htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8')
    : null;

  $timestamp      = date('Y-m-d H:i:s');
  $donationId     = null;
  $cmdtuples      = 0;
  $errorOccurred  = false;
  $errorMessage   = '';

  if (!in_array($donationType, ['existing', 'new'], true)) {
    jsonResponse(['error' => true, 'message' => 'Invalid donation type'], 400);
  }

  /* -------- Existing Donor -------- */
  if ($donationType === "existing") {

    $tel = $_POST['tel'] ?? null;

    if (!$tel) {
      jsonResponse(['error' => true, 'message' => 'Phone number missing'], 400);
    }

    $donationId = uniqid();

    $sql = "
            INSERT INTO donation_paymentdata
            (donationid, tel, currency, amount, transactionid, message, timestamp)
            VALUES ($1, $2, $3, $4, $5, $6, $7)
        ";

    $result = pg_query_params($con, $sql, [
      $donationId,
      $tel,
      $currency,
      $donationAmount,
      $transactionId,
      $message,
      $timestamp
    ]);

    if (!$result) {
      jsonResponse(['error' => true, 'message' => 'Donation insert failed'], 500);
    }

    $cmdtuples = pg_affected_rows($result);

    $emailSql = "SELECT email, fullname FROM donation_userdata WHERE tel = $1";
    $emailRes = pg_query_params($con, $emailSql, [$tel]);
  }

  /* -------- New Donor -------- */
  if ($donationType === "new") {

    $fullName         = $_POST['fullName'] ?? null;
    $email            = $_POST['email'] ?? null;
    $contactNumberNew = $_POST['contactNumberNew'] ?? null;
    $idNumber         = $_POST['idNumber'] ?? null;
    $postalAddress    = !empty($_POST['postalAddress'])
      ? htmlspecialchars($_POST['postalAddress'], ENT_QUOTES, 'UTF-8')
      : null;

    if (!$fullName || !$email || !$contactNumberNew) {
      jsonResponse(['error' => true, 'message' => 'Missing required fields'], 400);
    }

    $checkSql = "SELECT 1 FROM donation_userdata WHERE tel = $1";
    $checkRes = pg_query_params($con, $checkSql, [$contactNumberNew]);

    if (pg_num_rows($checkRes) > 0) {
      jsonResponse(['error' => true, 'errorMessage' => 'already_registered']);
    }

    $userSql = "
            INSERT INTO donation_userdata
            (fullname, email, tel, id_number, postaladdress)
            VALUES ($1, $2, $3, $4, $5)
        ";

    $userRes = pg_query_params($con, $userSql, [
      $fullName,
      $email,
      $contactNumberNew,
      $idNumber,
      $postalAddress
    ]);

    if (!$userRes) {
      jsonResponse(['error' => true, 'message' => 'User insert failed'], 500);
    }

    $donationId = uniqid();

    $donationSql = "
            INSERT INTO donation_paymentdata
            (donationid, tel, currency, amount, transactionid, message, timestamp)
            VALUES ($1, $2, $3, $4, $5, $6, $7)
        ";

    $donRes = pg_query_params($con, $donationSql, [
      $donationId,
      $contactNumberNew,
      $currency,
      $donationAmount,
      $transactionId,
      $message,
      $timestamp
    ]);

    if (!$donRes) {
      jsonResponse(['error' => true, 'message' => 'Donation insert failed'], 500);
    }

    $cmdtuples = pg_affected_rows($donRes);

    $emailSql = "SELECT email, fullname FROM donation_userdata WHERE tel = $1";
    $emailRes = pg_query_params($con, $emailSql, [$contactNumberNew]);
  }

  /* -------- Send Email -------- */
  if ($emailRes && ($row = pg_fetch_assoc($emailRes))) {
    sendEmail("donation_ack", [
      "fullname"      => $row['fullname'],
      "donationId"    => $donationId,
      "timestamp"     => $timestamp,
      "tel"           => $donationType === "existing" ? $tel : $contactNumberNew,
      "email"         => $row['email'],
      "transactionid" => $transactionId,
      "currency"      => $currency,
      "amount"        => $donationAmount
    ], $row['email']);
  }

  jsonResponse([
    'error'        => false,
    'cmdtuples'    => $cmdtuples,
    'donationId'   => $donationId
  ]);
}

/* =======================
   DONATION REVIEW
======================= */
if ($formtype === "donation_review") {

  $reviewer_id       = $_POST['reviewer_id'] ?? null;
  $donationid        = $_POST['donationid'] ?? null;
  $reviewer_status   = $_POST['reviewer_status'] ?? null;
  $reviewer_remarks  = $_POST['reviewer_remarks'] ?? null;
  $now               = date('Y-m-d H:i:s');

  if (!$reviewer_id || !$donationid || !$reviewer_status) {
    jsonResponse(['error' => true, 'message' => 'Missing fields'], 400);
  }

  $sql = "
        UPDATE donation_paymentdata
        SET reviewedby = $1,
            status = $2,
            reviewer_remarks = $3,
            reviewedon = $4
        WHERE donationid = $5
    ";

  $result = pg_query_params($con, $sql, [
    $reviewer_id,
    $reviewer_status,
    $reviewer_remarks,
    $now,
    $donationid
  ]);

  if (!$result || pg_affected_rows($result) !== 1) {
    jsonResponse(['error' => true, 'message' => 'Update failed'], 500);
  }

  jsonResponse(['status' => 'success']);
}

/* =======================
   FALLBACK
======================= */
jsonResponse(['error' => true, 'message' => 'Invalid form type'], 400);
