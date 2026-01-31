<?php
require_once __DIR__ . "/../../../bootstrap.php";

include("../../../util/paytm-util.php");
include("../../../util/email.php");
include("../../../util/drive.php");

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
   FALLBACK
======================= */
jsonResponse(['error' => true, 'message' => 'Invalid form type'], 400);
