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

if ($formtype === "get_details_visit") {
    $contactnumber = $_POST['contactnumber_verify_input'] ?? null;

    if (!$contactnumber) {
        jsonResponse(['error' => true, 'message' => 'Missing contact number'], 400);
    }

    $query = "SELECT fullname, email, tel FROM visitor_userdata WHERE tel = $1";
    $result = pg_query_params($con, $query, [$contactnumber]);

    if (!$result) {
        jsonResponse(['error' => true, 'message' => 'Database error'], 500);
    }

    $row = pg_fetch_assoc($result);

    if ($row) {
        jsonResponse(['status' => 'success', 'data' => $row]);
    } else {
        jsonResponse(['status' => 'no_records', 'message' => 'No records found']);
    }
}

if ($formtype == "visitor_form") {
    if (isset($_POST['form-type']) && $_POST['form-type'] === "visitor_form") {
        $tel = $_POST['tel'];
        $visitbranch = $_POST['visitbranch'];
        $visitstartdatetime = $_POST['visitstartdatetime'];
        $visitenddate = $_POST['visitenddate'];
        $visitpurpose = $_POST['visitpurpose'];
        $other_reason = htmlspecialchars($_POST['other_reason'], ENT_QUOTES, 'UTF-8');
        $institutename = $_POST['institutename'];
        $enrollmentnumber = $_POST['enrollmentnumber'];
        $instituteid = $_FILES['instituteid'];
        $mentoremail = $_POST['mentoremail'];
        $paymentdoc = $_FILES['paymentdoc'];
        $declaration = $_POST['declaration'];
        $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
        $timestamp = date('Y-m-d H:i:s');
        $visitId = null; // initialize as null
        $cmdtuples = 0;
        $errorOccurred = false;
        $errorMessage = '';
        $additional_services_array = [];

        if (!empty($_POST['additional_services'])) {
            foreach ($_POST['additional_services'] as $service) {
                if ($service === 'videography') {
                    $camera_count = isset($_POST['camera_count']) ? intval($_POST['camera_count']) : 1;
                    $additional_services_array[] = "videography({$camera_count})";
                } elseif ($service === 'interview') {
                    $duration = isset($_POST['interview_duration']) ? intval($_POST['interview_duration']) : 30;
                    $additional_services_array[] = "interview({$duration}min)";
                } else {
                    $additional_services_array[] = $service;
                }
            }
        }

        $additional_services = implode(',', $additional_services_array);

        // Upload files first (these will be used for both new and existing visitors)
        if (empty($_FILES['paymentdoc']['name'])) {
            $doclink_paymentdoc = null;
        } else {
            $filename_paymentdoc = "doc_" . time() . "_" . rand(1000, 9999);
            $parent_paymentdoc = '1f_UvwDaxvloRyYgNs9rjl6ZGW8nUB8RwXHQtQ3RsA9W6SaYY-7xLNn0kXvGV8A9fAjJ6x9yZ';
            $doclink_paymentdoc = uploadeToDrive($paymentdoc, $parent_paymentdoc, $filename_paymentdoc);
        }

        if (empty($_FILES['instituteid']['name'])) {
            $doclink_instituteid = null;
        } else {
            $filename_instituteid = "doc_" . time() . "_" . rand(1000, 9999);
            $parent_instituteid = '1OFTSdUFZm1RVYNWaux1jv_EW5iftuKh_RSLHXkdbqLacK9nziY-Vx4KrUrJoiNIvohQPSzi3';
            $doclink_instituteid = uploadeToDrive($instituteid, $parent_instituteid, $filename_instituteid);
        }

        if ($_POST['visitorType'] === "existing") {
            // Only generate visitId if we're going to use it
            $visitId = uniqid();
            $visitorQuery = "
        INSERT INTO visitor_visitdata 
        (visitid, tel, visitbranch, visitstartdatetime, visitenddate, visitpurpose, 
         institutename, enrollmentnumber, instituteid, mentoremail, paymentdoc, declaration, 
         timestamp, other_reason, additional_services)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15)
      ";

            $resultVisitor = pg_query_params($con, $visitorQuery, [
                $visitId,
                $tel,
                $visitbranch,
                $visitstartdatetime,
                $visitenddate,
                $visitpurpose,
                $institutename,
                $enrollmentnumber,
                $doclink_instituteid,
                $mentoremail,
                $doclink_paymentdoc,
                $declaration,
                $timestamp,
                $other_reason,
                $additional_services
            ]);

            if ($resultVisitor) {
                $cmdtuples = pg_affected_rows($resultVisitor);
            } else {
                $errorOccurred = true;
                $visitId = null; // Reset visitId on error
            }
        } elseif ($_POST['visitorType'] === "new") {
            $fullName = $_POST['fullName'];
            $email = $_POST['email'];
            $contactNumberNew = $_POST['contactNumberNew'];
            $idType = $_POST['idType'];
            $idNumber = !empty($_POST['idNumber']) ? $_POST['idNumber'] : null;
            $governmentId = $_FILES['governmentId'];
            $photo = $_FILES['photo'];

            // Check if visitor already exists
            $checkQuery = "SELECT 1 FROM visitor_userdata WHERE tel = $1";
            $checkResult = pg_query_params($con, $checkQuery, [$contactNumberNew]);

            if ($checkResult && pg_num_rows($checkResult) > 0) {
                $errorOccurred = true;
                $errorMessage = 'already_registered';
            } else {
                // Upload files for new visitor
                $doclink_governmentId = null;
                if (!empty($governmentId['name'])) {
                    $filename_governmentId = "doc_" . $fullName . "_" . time();
                    $parent_governmentId = '1sdu_dkdrRezOr6IdRMJOknFOSovz1qGP2zRg9Db5IyLIMtVxwWgy-Io8aV36B4uTx9-Gwg3W';
                    $doclink_governmentId = uploadeToDrive($governmentId, $parent_governmentId, $filename_governmentId);
                }

                $doclink_photo = null;
                if (!empty($photo['name'])) {
                    $filename_photo = "doc_" . $fullName . "_" . time();
                    $parent_photo = '1bgjv3Ei5Go073xcZa7sXKjlOFSTHdoqo8Ffdba_ICNFdcq4ashhxTHGEr0rbX3KdH3CjDbwH';
                    $doclink_photo = uploadeToDrive($photo, $parent_photo, $filename_photo);
                }

                // Insert userdata
                $userdataQuery = "INSERT INTO visitor_userdata (fullname, email, tel, id_type, id_number, nationalid, photo) 
                          VALUES ($1, $2, $3, $4, $5, $6, $7)";
                $resultUserdata = pg_query_params($con, $userdataQuery, [
                    $fullName,
                    $email,
                    $contactNumberNew,
                    $idType,
                    $idNumber,
                    $doclink_governmentId,
                    $doclink_photo
                ]);

                if ($resultUserdata) {
                    // Only generate visitId if user insertion succeeded
                    $visitId = uniqid();

                    $visitQuery = "INSERT INTO visitor_visitdata 
                           (visitid, tel, visitbranch, visitstartdatetime, visitenddate, visitpurpose, 
                            institutename, enrollmentnumber, instituteid, mentoremail, paymentdoc, declaration, 
                            timestamp, other_reason, additional_services)
                           VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15)";
                    $resultVisitor = pg_query_params($con, $visitQuery, [
                        $visitId,
                        $contactNumberNew,
                        $visitbranch,
                        $visitstartdatetime,
                        $visitenddate,
                        $visitpurpose,
                        $institutename,
                        $enrollmentnumber,
                        $doclink_instituteid,
                        $mentoremail,
                        $doclink_paymentdoc,
                        $declaration,
                        $timestamp,
                        $other_reason,
                        $additional_services
                    ]);

                    if ($resultVisitor) {
                        $cmdtuples = pg_affected_rows($resultVisitor);
                    } else {
                        $errorOccurred = true;
                        $visitId = null; // Reset visitId on error
                    }
                } else {
                    $errorOccurred = true;
                }
            }
        }

        // After successful form submission
        if (!$errorOccurred && $errorMessage !== "already_registered") {
            // Sending email based on the visitor type
            if ($_POST['visitorType'] === "existing") {
                $emailQuery = "SELECT email, fullname FROM visitor_userdata WHERE tel = $1";
                $result = pg_query_params($con, $emailQuery, [$tel]);
            } elseif ($_POST['visitorType'] === "new") {
                $emailQuery = "SELECT email, fullname FROM visitor_userdata WHERE tel = $1";
                $result = pg_query_params($con, $emailQuery, [$contactNumberNew]);
            }

            if ($result) {
                $row = pg_fetch_assoc($result);
                $email = $row['email'];
                $name = $row['fullname'];
            } else {
                $email = null;
                $name = null;
            }

            if (($_POST['visitorType'] === "existing" || $_POST['visitorType'] === "new") && $email != "") {
                sendEmail("visit_request_ack", array(
                    "fullname" => $name,
                    "visitId" => $visitId,
                    "timestamp" => date("d/m/Y h:i A", strtotime($timestamp)),
                    "tel" => $_POST['visitorType'] === "existing" ? $tel : $contactNumberNew,
                    "email" => $email,
                    "visitstartdatetime" => date("d/m/Y h:i A", strtotime($visitstartdatetime)),
                    "visitenddate" => $visitenddate,
                    "visitpurpose" => $visitpurpose
                ), $email, true);
            }
        }

        // Prepare the API response data
        $responseData = array(
            'error' => $errorOccurred,
            'errorMessage' => $errorOccurred ? $errorMessage : '',
            'cmdtuples' => $cmdtuples,
            'visitorId' => $errorOccurred ? null : $visitId // Only return visitorId if no error
        );

        // Return the response as JSON
        echo json_encode($responseData);
        exit;
    }
}

if ($formtype === "visitreviewform") {

    $reviewer_id = $_POST['reviewer_id'] ?? null;
    $visitid     = $_POST['visitid'] ?? null;
    $visitstatus = $_POST['visitstatus'] ?? null;
    $hrremarks   = htmlspecialchars($_POST['hrremarks'] ?? '', ENT_QUOTES, 'UTF-8');
    $now = date('Y-m-d H:i:s');

    if (!$reviewer_id || !$visitid || !$visitstatus) {
        jsonResponse(['error' => true, 'message' => 'Missing fields'], 400);
    }

    $sql = "
        UPDATE visitor_visitdata
        SET visitstatusupdatedby = $1,
            visitstatusupdatedon = $2,
            visitstatus = $3,
            remarks = $4
        WHERE visitid = $5
    ";

    $result = pg_query_params($con, $sql, [
        $reviewer_id,
        $now,
        $visitstatus,
        $hrremarks,
        $visitid
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
