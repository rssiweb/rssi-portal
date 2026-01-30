<?php
require_once __DIR__ . "/../../../bootstrap.php";

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

include("../../../util/paytm-util.php");
include("../../../util/email.php");
include("../../../util/drive.php");

date_default_timezone_set('Asia/Kolkata');

$formtype = $_POST['form-type'];

if ($formtype == "get_details") {
  $contactnumber = $_POST['contactnumber_verify_input'];
  $getdetails = "SELECT fullname, email, tel FROM donation_userdata WHERE tel='$contactnumber'";
  $result = pg_query($con, $getdetails);
  if ($result) {
    $row = pg_fetch_assoc($result);
    if ($row) {
      echo json_encode(array('status' => 'success', 'data' => $row));
    } else {
      echo json_encode(array('status' => 'no_records', 'message' => 'No records found in the database. Donate as a new user.'));
    }
  } else {
    echo json_encode(array('status' => 'error', 'message' => 'Error retrieving user data'));
  }
}

if ($formtype == "donation_form") {
  $tel = $_POST['tel'];
  $currency = $_POST['currency'];
  $transactionId = $_POST['transactionid'];
  $message = !empty($_POST['message'])
    ? htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8')
    : null;
  $donationAmount = $_POST['donationAmount'];
  $timestamp = date('Y-m-d H:i:s');
  $donationId = null; // initialize as null
  $cmdtuples = 0;
  $errorOccurred = false;
  $errorMessage = '';

  if ($_POST['donationType'] === "existing") {
    // Only generate donationId if we're going to use it (no error yet)
    $donationId = uniqid();
    $donationQuery = "
        INSERT INTO donation_paymentdata
        (donationid, tel, currency, amount, transactionid, message, timestamp)
        VALUES ($1, $2, $3, $4, $5, $6, $7)
      ";

    $resultUserdata = pg_query_params(
      $con,
      $donationQuery,
      [
        $donationId,
        $tel,
        $currency,
        $donationAmount,
        $transactionId,
        $message,
        $timestamp
      ]
    );

    if ($resultUserdata) {
      $cmdtuples = pg_affected_rows($resultUserdata);
    } else {
      $errorOccurred = true;
      $donationId = null; // Reset donationId on error
    }
  } elseif ($_POST['donationType'] === "new") {
    $fullName         = $_POST['fullName'];
    $email            = $_POST['email'];
    $contactNumberNew = $_POST['contactNumberNew'];
    $idNumber         = !empty($_POST['idNumber']) ? $_POST['idNumber'] : null;
    $postalAddress    = !empty($_POST['postalAddress']) ? htmlspecialchars($_POST['postalAddress'], ENT_QUOTES, 'UTF-8') : null;

    // Check if user already exists
    $checkQuery = "SELECT 1 FROM donation_userdata WHERE tel = $1";
    $checkResult = pg_query_params($con, $checkQuery, [$contactNumberNew]);

    if ($checkResult && pg_num_rows($checkResult) > 0) {
      $errorOccurred = true;
      $errorMessage  = "already_registered";
    } else {
      // User does not exist, safe to insert
      $userdataQuery = "
            INSERT INTO donation_userdata
            (fullname, email, tel, id_number, postaladdress)
            VALUES ($1, $2, $3, $4, $5)
        ";

      $resultUserdata = pg_query_params(
        $con,
        $userdataQuery,
        [
          $fullName,
          $email,
          $contactNumberNew,
          $idNumber,
          $postalAddress
        ]
      );

      if ($resultUserdata) {
        // Only generate donationId if user insertion succeeded
        $donationId = uniqid();
        // Insert donation record
        $donationQuery = "
                INSERT INTO donation_paymentdata
                (donationid, tel, currency, amount, transactionid, message, timestamp)
                VALUES ($1, $2, $3, $4, $5, $6, $7)
            ";

        $resultDonation = pg_query_params(
          $con,
          $donationQuery,
          [
            $donationId,
            $contactNumberNew,
            $currency,
            $donationAmount,
            $transactionId,
            $message,
            $timestamp
          ]
        );

        if ($resultDonation) {
          $cmdtuples = pg_affected_rows($resultDonation);
        } else {
          $errorOccurred = true;
          $donationId = null; // Reset donationId on error
        }
      } else {
        $errorOccurred = true;
      }
    }
  }

  // After successful form submission
  if (!$errorOccurred && $errorMessage !== "already_registered") {
    // Sending email based on the donation type
    if ($_POST['donationType'] === "existing") {
      $emailQuery = "SELECT email, fullname FROM donation_userdata WHERE tel='$tel'";
    } else if ($_POST['donationType'] === "new") {
      $emailQuery = "SELECT email, fullname FROM donation_userdata WHERE tel='$contactNumberNew'";
    }
    $result = pg_query($con, $emailQuery);

    if ($result) {
      $row = pg_fetch_assoc($result);
      $email = $row['email'];
      $name = $row['fullname'];
    } else {
      // Handle error if the query fails
      $email = null;
      $name = null;
    }

    if (($_POST['donationType'] === "existing" || $_POST['donationType'] === "new") && $email != "") {
      sendEmail("donation_ack", array(
        "fullname" => $name,
        "donationId" => $donationId,
        "timestamp" => $timestamp,
        "tel" => $_POST['donationType'] === "existing" ? $tel : $contactNumberNew,
        "email" => $email,
        "transactionid" => $transactionId,
        "currency" => $currency,
        "amount" => $donationAmount
      ), $email);
    }
  }

  // Prepare the API response data
  $responseData = array(
    'error' => $errorOccurred,
    'errorMessage' => $errorOccurred ? $errorMessage : '',
    'cmdtuples' => $cmdtuples,
    'donationId' => $errorOccurred ? null : $donationId // Only return donationId if no error
  );

  if (ob_get_length()) {
    ob_clean();
  }

  echo json_encode($responseData);
  exit;
}

if ($formtype == "donation_review") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$donationid = $_POST['donationid'];
  @$reviewer_status = $_POST['reviewer_status'];
  @$reviewer_remarks = $_POST['reviewer_remarks'];
  $now = date('Y-m-d H:i:s');
  $donation_review = "UPDATE donation_paymentdata SET  reviewedby = '$reviewer_id', status = '$reviewer_status', reviewer_remarks = '$reviewer_remarks', reviewedon = '$now' WHERE donationid = '$donationid'";
  $result = pg_query($con, $donation_review);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
  } else
    echo "failed";
}
