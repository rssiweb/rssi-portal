<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/paytm-util.php");
include("../../util/email.php");
include("../../util/drive.php");
// require_once("email.php");

date_default_timezone_set('Asia/Kolkata');

$formtype = $_POST['form-type'];

if ($formtype == "payment") {
  @$studentid = $_POST['studentid'];
  @$fees = $_POST['fees'];
  // @$month = join(',', $_POST['month']);
  @$month = $_POST['month'];
  @$collectedby = $_POST['collectedby'];
  @$ptype = $_POST['ptype'];
  @$feeyear = $_POST['year'];
  $now = date('Y-m-d H:i:s');
  $feesupdate = "INSERT INTO fees (date, studentid, fees, month, collectedby, ptype,feeyear) VALUES ('$now','$studentid','$fees','$month','$collectedby','$ptype','$feeyear')";
  $result = pg_query($con, $feesupdate);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "distribution") {
  @$distributedto = $_POST['distributedto'];
  @$distributedby = $_POST['distributedby'];
  @$items = $_POST['items'];
  @$quantity = $_POST['quantity'];
  @$amount = $_POST['amount'];
  @$issuance_date = $_POST['issuance_date'];
  $now = date('Y-m-d H:i:s');
  $distribution_data = "INSERT INTO distribution_data (distributedto, distributedby, items, quantity, issuance_date,timestamp) VALUES ('$distributedto', '$distributedby', '$items', '$quantity', '$issuance_date','$now')";
  $result = pg_query($con, $distribution_data);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "transfer") {
  @$refid = $_POST['pid'];
  $pstatus = "UPDATE fees SET  pstatus = 'transferred' WHERE id = $refid";
  $result = pg_query($con, $pstatus);
}

if ($formtype == "noticebodyedit") {
  @$noticeid = $_POST['noticeid'];
  @$noticebody = $_POST['noticebody'];
  $noticebodyedit = "UPDATE notice SET  noticebody = '$noticebody' WHERE noticeid = '$noticeid'";
  $result = pg_query($con, $noticebodyedit);
}

if ($formtype == "pay_comment") {
  @$payslip_entry_id = $_POST['payslip_entry_id'];
  @$comment = $_POST['comment'];
  $commentedit = "UPDATE payslip_entry SET  comment = '$comment' WHERE payslip_entry_id = '$payslip_entry_id'";
  $result = pg_query($con, $commentedit);
}

if ($formtype == "policybodyedit") {
  @$policyid = $_POST['policyid'];
  @$remarks = $_POST['remarks'];
  $policybodyeditt = "UPDATE policy SET  remarks = '$remarks' WHERE policyid = '$policyid'";
  $result = pg_query($con, $policybodyeditt);
}


if ($formtype == "paydelete") {
  @$refid = $_POST['pid'];
  $paydelete = "DELETE from fees WHERE id = $refid";
  $result = pg_query($con, $paydelete);
}

if ($formtype == "policydelete") {
  @$policydid = $_POST['policydeleteid'];
  $deletepolicy = "DELETE from policy WHERE policyid = '$policydid'";
  $result = pg_query($con, $deletepolicy);
}

if ($formtype == "paydelete") {
  @$refid = $_POST['pid'];
  $paydelete = "DELETE from fees WHERE id = $refid";
  $result = pg_query($con, $paydelete);
}

if ($formtype == "leavedelete") {
  @$leavedeleteid = $_POST['leavedeleteid'];
  $leavedelete = "DELETE from leavedb_leavedb WHERE leaveid = '$leavedeleteid'";
  $result = pg_query($con, $leavedelete);
}

if ($formtype == "claimdelete") {
  @$claimdeleteid = $_POST['claimdeleteid'];
  $claimdelete = "DELETE from claim WHERE reimbid = '$claimdeleteid'";
  $result = pg_query($con, $claimdelete);
}

if ($formtype == "leaveadjdelete") {
  @$leaveadjdeleteid = $_POST['leaveadjdeleteid'];
  $leaveadjdelete = "DELETE from leaveadjustment WHERE leaveadjustmentid = '$leaveadjdeleteid'";
  $result = pg_query($con, $leaveadjdelete);
}

if ($formtype == "leaveallodelete") {
  @$leaveallodeleteid = $_POST['leaveallodeleteid'];
  $leaveallodelete = "DELETE from leaveallocation WHERE leaveallocationid = '$leaveallodeleteid'";
  $result = pg_query($con, $leaveallodelete);
}

if ($formtype == "cmsdelete") {
  @$certificate_no = $_POST['cmsid'];
  $cmsdelete = "DELETE from certificate WHERE certificate_no = '$certificate_no'";
  $result = pg_query($con, $cmsdelete);
}

if ($formtype == "gemsdelete") {
  @$redeem_id = $_POST['redeem_id'];
  $gemsdelete = "DELETE from gems WHERE redeem_id = '$redeem_id'";
  $result = pg_query($con, $gemsdelete);
}

if ($formtype == "gpsedit") {
  @$itemid = $_POST['itemid1'];
  @$itemname = $_POST['itemname'];
  @$itemtype = $_POST['itemtype'];
  @$quantity = $_POST['quantity'];
  @$remarks = $_POST['remarks'];
  @$updatedby = $_POST['updatedby'];
  @$asset_status = $_POST['asset_status'];
  @$collectedby = strtoupper($_POST['collectedby']);
  @$taggedto = strtoupper($_POST['taggedto']);
  $now = date('Y-m-d H:i:s');
  $gpshistory = "INSERT INTO gps_history (itemid, date, itemtype, itemname, quantity, remarks, collectedby,taggedto,asset_status,updatedby) VALUES ('$itemid','$now','$itemtype','$itemname','$quantity','$remarks','$collectedby','$taggedto','$asset_status','$updatedby')";
  $tagedit = "UPDATE gps SET  lastupdatedon='$now', itemid='$itemid', itemtype='$itemtype', itemname='$itemname', quantity='$quantity', remarks='$remarks', collectedby='$collectedby',taggedto='$taggedto',asset_status='$asset_status', lastupdatedby='$updatedby' WHERE itemid = '$itemid'";
  $result = pg_query($con, $tagedit);
  $result = pg_query($con, $gpshistory);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    else
      echo "failed";
  } else
    echo "bad request";
}


if ($formtype == "gen_otp_associate") {
  @$otp_initiatedfor = $_POST['otp_initiatedfor'];
  @$associate_name = $_POST['associate_name'];
  @$email = $_POST['associate_email'];
  $item = $entityManager->getRepository('Onboarding')->find($otp_initiatedfor); //primary key
  if ($item) {
    // Generate a random 6 digit number
    $otp = rand(100000, 999999);
    $hashedValue = password_hash($otp, PASSWORD_DEFAULT);

    $item->setOnboardingGenOtpAssociate($hashedValue);
    $entityManager->persist($item);
    $entityManager->flush();
    echo "success";
    if ($email != "") {
      sendEmail("otp", array(
        "process" => 'onboarding',
        "otp" => @$otp,
        "receiver" => @$associate_name,
      ), $email, False);
    }
  } else {
    echo "failed";
  }
}

if ($formtype == "gen_otp_centr") {

  @$otp_initiatedfor = $_POST['otp_initiatedfor'];
  @$email = $_POST['centre_incharge_email'];
  @$centre_incharge_name = $_POST['centre_incharge_name'];
  $item = $entityManager->getRepository('Onboarding')->find($otp_initiatedfor); //primary key
  if ($item) {
    // Generate a random 6 digit number
    $otp = rand(100000, 999999);
    $hashedValue = password_hash($otp, PASSWORD_DEFAULT);

    $item->setOnboardingGenOtpCenterIncharge($hashedValue);
    $entityManager->persist($item);
    $entityManager->flush();
    echo "success";
    if ($email != "") {
      sendEmail("otp", array(
        "process" => 'onboarding',
        "otp" => @$otp,
        "receiver" => @$centre_incharge_name,
      ), $email, False);
    }
  } else {
    echo "failed";
  }
}

if ($formtype == "get_details") {
  @$contactnumber = $_POST['contactnumber_verify_input'];
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
  if (isset($_POST['form-type']) && $_POST['form-type'] === "donation_form") {
    $tel = $_POST['tel'];
    $currency = $_POST['currency'];
    $transactionId = $_POST['transactionid'];
    $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
    $donationAmount = $_POST['donationAmount'];
    $timestamp = date('Y-m-d H:i:s');
    $donationId = uniqid();
    $cmdtuples = 0; // Initialize cmdtuples
    $errorOccurred = false; // Flag to track if an error occurred
    $errorMessage = '';

    if ($_POST['donationType'] === "existing") {
      $donationQuery = "INSERT INTO donation_paymentdata (donationid, tel, currency, amount, transactionid, message, timestamp) 
                          VALUES ('$donationId', '$tel', '$currency', '$donationAmount', '$transactionId', '$message', '$timestamp')";
      $resultUserdata = pg_query($con, $donationQuery);

      if ($resultUserdata) {
        $cmdtuples = pg_affected_rows($resultUserdata);
      } else {
        $errorOccurred = true;
        $errorMessage = handleInsertionError($con, "Donation insertion", $tel);
      }
    } elseif ($_POST['donationType'] === "new") {
      $fullName = $_POST['fullName'];
      $email = $_POST['email'];
      $contactNumberNew = $_POST['contactNumberNew'];
      $documentType = $_POST['documentType'];
      $nationalId = $_POST['nationalId'];
      $postalAddress = htmlspecialchars($_POST['postalAddress'], ENT_QUOTES, 'UTF-8');

      // Insert userdata
      $userdataQuery = "INSERT INTO donation_userdata (fullname, email, tel, documenttype, nationalid, postaladdress) 
                          VALUES ('$fullName', '$email', '$contactNumberNew', '$documentType', '$nationalId', '$postalAddress')";
      @$resultUserdata = pg_query($con, $userdataQuery);

      if ($resultUserdata) {
        // Insert donation
        $donationQuery = "INSERT INTO donation_paymentdata (donationid, tel, currency, amount, transactionid, message, timestamp) 
                              VALUES ('$donationId', '$contactNumberNew', '$currency', '$donationAmount', '$transactionId', '$message', '$timestamp')";
        $resultDonation = pg_query($con, $donationQuery);

        if ($resultDonation) {
          $cmdtuples = pg_affected_rows($resultDonation);
        } else {
          $errorOccurred = true;
          $errorMessage = handleInsertionError($con, "Donation insertion", $contactNumberNew);
        }
      } else {
        $errorOccurred = true;
        $errorMessage = handleInsertionError($con, "Userdata insertion", $contactNumberNew);
      }
    }

    // After successful form submission
    if (!$errorOccurred) {
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
          "tel" => @$tel . @$contactNumberNew,
          "email" => $email,
          "transactionid" => $transactionId,
          "currency" => $currency,
          "amount" => $donationAmount
        ), $email, false);
      }
    }

    // Prepare the API response data
    $responseData = array(
      'error' => $errorOccurred,
      'errorMessage' => $errorOccurred ? $errorMessage : '', // Return the actual error message if an error occurred
      'cmdtuples' => $cmdtuples,
      'donationId' => $donationId
    );

    // Return the response as JSON
    echo json_encode($responseData);
    exit; // Stop further PHP execution
  }
}

function handleInsertionError($connection, $errorMessage, $tel)
{
  $error = pg_last_error($connection);

  // Check for duplicate key violation error
  if (strpos($error, 'duplicate key value violates unique constraint') !== false) {
    return "already_registered"; // Return a specific code for duplicate key violation error
    // Additional handling specific to duplicate key violation error can be done here
  } else {
    // Log or display the generic error message
    error_log($errorMessage . " error: " . $error);
    return "generic_error"; // Return a specific code for generic error
  }
}

if ($formtype == "exit_gen_otp_associate") {
  @$otp_initiatedfor = $_POST['otp_initiatedfor'];
  @$associate_name = $_POST['associate_name'];
  @$email = $_POST['associate_email'];
  // Generate a random 6 digit number
  $otp = rand(100000, 999999);
  $hashedValue = password_hash($otp, PASSWORD_DEFAULT);
  $exit_otp_associate = "UPDATE associate_exit SET  exit_gen_otp_associate = '$hashedValue' WHERE exit_associate_id = '$otp_initiatedfor'";
  $result = pg_query($con, $exit_otp_associate);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    if ($email != "") {
      sendEmail("otp", array(
        "process" => 'exit',
        "otp" => @$otp,
        "receiver" => @$associate_name,
      ), $email, False);
    } else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "exit_gen_otp_centr") {

  @$otp_initiatedfor = $_POST['otp_initiatedfor'];
  @$email = $_POST['centre_incharge_email'];
  @$centre_incharge_name = $_POST['centre_incharge_name'];
  // Generate a random 6 digit number
  $otp = rand(100000, 999999);
  $hashedValue = password_hash($otp, PASSWORD_DEFAULT);
  $exit_otp_centreincharge = "UPDATE associate_exit SET  exit_gen_otp_center_incharge = '$hashedValue' WHERE exit_associate_id = '$otp_initiatedfor'";
  $result = pg_query($con, $exit_otp_centreincharge);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    if ($email != "") {
      sendEmail("otp", array(
        "process" => 'exit',
        "otp" => @$otp,
        "receiver" => @$centre_incharge_name,
      ), $email, False);
    } else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "initiatingonboarding") {
  @$initiatedfor = $_POST['initiatedfor'];
  @$initiatedby = $_POST['initiatedby'];
  $now = date('Y-m-d H:i:s');
  $initiatingonboarding = "INSERT INTO onboarding (onboarding_associate_id, onboard_initiated_by, onboard_initiated_on) VALUES ('$initiatedfor','$initiatedby','$now')";
  $result = pg_query($con, $initiatingonboarding);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    else
      echo "failed";
  } else
    echo "bad request";
}


if ($formtype == "initiatingexit") {
  @$initiatedfor = $_POST['initiatedfor'];
  @$initiatedby = $_POST['initiatedby'];
  $now = date('Y-m-d H:i:s');
  $initiatingexit = "INSERT INTO associate_exit (exit_associate_id, exit_initiated_by, exit_initiated_on) VALUES ('$initiatedfor','$initiatedby','$now')";
  $result = pg_query($con, $initiatingexit);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "ipfsubmission") {
  @$ipfid = $_POST['ipfid'];
  @$ipf = $_POST['ipf'];
  @$status2 = $_POST['status2'];
  @$ipf_response_by = $_POST['ipf_response_by'];
  @$name = $_POST['ipf_response_by_name'];
  @$email = $_POST['ipf_response_by_email'];
  $now = date('Y-m-d H:i:s');
  $ipfclose = "UPDATE appraisee_response SET  ipf_response = '$status2', ipf_response_on = '$now', ipf_response_by='$ipf_response_by' WHERE goalsheetid = '$ipfid'";
  $ipf_history = "INSERT INTO ipf_history (goalsheetid, ipf_response, ipf_response_on, ipf_response_by, ipf) VALUES ('$ipfid','$status2','$now','$ipf_response_by',$ipf)";
  $result = pg_query($con, $ipfclose);
  $result_history = pg_query($con, $ipf_history);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
    if ($email != "") {
      sendEmail("ipfsubmission", array(
        "goalsheetid" => @$ipfid,
        "responseby" => @$ipf_response_by,
        "name" => @$name,
        "status" => @$status2,
        "time" => @$now,
      ), $email);
    } else
      echo "failed";
  } else
    echo "bad request";
}

if ($formtype == "transfer_all") {
  $pid = $_POST['pid'];
  $selectedIds = explode(', ', $pid);
  $transferAllQuery = "UPDATE fees
                       SET pstatus = 'transferred'
                       WHERE id IN (" . implode(',', $selectedIds) . ")";
  $result = pg_query_params($con, $transferAllQuery, array());

  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples > 0) {
      echo "success";
    } else {
      echo "failed";
    }
  } else {
    // Handle the error here
    error_log(pg_last_error($con));
    echo "failed";
  }

  // Close the database connection
  pg_close($con);
}

if ($formtype == "gemsredeem") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$reviewer_name = $_POST['reviewer_name'];
  @$redeem_idd = $_POST['redeem_idd'];
  @$reviewer_status = $_POST['reviewer_status'];
  @$reviewer_remarks = $_POST['reviewer_remarks'];
  $now = date('Y-m-d H:i:s');
  $gemsredeem = "UPDATE gems SET  reviewer_id = '$reviewer_id',  reviewer_name = '$reviewer_name', reviewer_status = '$reviewer_status', reviewer_remarks = '$reviewer_remarks', reviewer_status_updated_on = '$now' WHERE redeem_id = '$redeem_idd'";
  $result = pg_query($con, $gemsredeem);
}

if ($formtype == "archiveapproval") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$doc_idd = $_POST['doc_idd'];
  @$reviewer_status = $_POST['reviewer_status'];
  @$field_status = $_POST['field_status'];
  @$reviewer_remarks = $_POST['reviewer_remarks'];
  $now = date('Y-m-d H:i:s');
  $archive = "UPDATE archive SET  reviewed_by = '$reviewer_id',  verification_status = '$reviewer_status', field_status = '$field_status',remarks = '$reviewer_remarks', reviewed_on = '$now' WHERE doc_id = '$doc_idd'";
  $result = pg_query($con, $archive);
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

if ($formtype == "leavereviewform") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$reviewer_name = $_POST['reviewer_name'];
  @$leaveid = $_POST['leaveidd'];
  @$status = $_POST['leave_status'];
  @$comment = $_POST['reviewer_remarks'];
  @$fromdate = $_POST['fromdate'];
  @$todate = $_POST['todate'];
  @$halfdayhr = $_POST['is_userhr'] ?? 0;
  $now = date('Y-m-d H:i:s');

  if ($halfdayhr != '' && $halfdayhr != 0) {

    @$day = round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1) / 2;
  } else {
    @$day = round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1);
  }
  $leaveapproval = "UPDATE leavedb_leavedb SET  status = '$status', fromdate = '$fromdate',  todate = '$todate', comment = '$comment',reviewer_id = '$reviewer_id',  reviewer_name = '$reviewer_name', days = '$day', halfday = $halfdayhr WHERE leaveid = '$leaveid'";

  $result = pg_query($con, $leaveapproval);
}

if ($formtype == "claimreviewform") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$reviewer_name = $_POST['reviewer_name'];
  @$reimbidd = $_POST['reimbid'];
  @$claimstatus = $_POST['claimstatus'];
  @$approvedamount = $_POST['approvedamount'] ?? 0;
  @$transactionid = $_POST['transactionid'];
  @$transfereddate = $_POST['transfereddate'];
  @$closedon = $_POST['closedon'];
  @$mediremarks = $_POST['mediremarks'];
  $now = date('Y-m-d H:i:s');

  if ($claimstatus != "Rejected" && $claimstatus != "Under review") {

    $claimapproval = "UPDATE claim SET  reviewer_id = '$reviewer_id', reviewer_name = '$reviewer_name',  updatedon = '$now', claimstatus = '$claimstatus',approvedamount = $approvedamount,  transactionid = '$transactionid', transfereddate = '$transfereddate', closedon = '$closedon', mediremarks = '$mediremarks' WHERE reimbid = '$reimbidd'";
  } else {
    $claimapproval = "UPDATE claim SET  reviewer_id = '$reviewer_id', reviewer_name = '$reviewer_name',  updatedon = '$now', claimstatus = '$claimstatus', mediremarks = '$mediremarks', approvedamount = null,  transactionid = null, transfereddate = null WHERE reimbid = '$reimbidd'";
  }
  $result = pg_query($con, $claimapproval);
}

if ($formtype == "ipfclose") {
  @$ipfid = $_POST['ipfid'];
  @$ipf_process_closed_by = $_POST['ipf_process_closed_by'];
  $now = date('Y-m-d H:i:s');
  $ipfclose = "UPDATE appraisee_response SET  ipf_process_closed_by = '$ipf_process_closed_by', ipf_process_closed_on = '$now' WHERE goalsheetid = '$ipfid'";
  $result = pg_query($con, $ipfclose);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
  } else
    echo "failed";
}

if ($formtype == "manager_unlock") {
  @$goalsheetid = $_POST['goalsheetid'];
  $manager_unlock = "UPDATE appraisee_response SET appraisee_response_complete= null, manager_unlocked= 'yes' WHERE goalsheetid='$goalsheetid'";
  $result = pg_query($con, $manager_unlock);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
  } else
    echo "failed";
}

if ($formtype == "unlock_request") {
  @$goalsheetid = $_POST['goalsheetid'];
  $unlock_request = "UPDATE appraisee_response SET unlock_request= 'yes' WHERE goalsheetid='$goalsheetid'";
  $result = pg_query($con, $unlock_request);
  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1)
      echo "success";
  } else
    echo "failed";
}

if ($formtype == "test") {
  @$sname = $_POST['sname'];
  @$sid = $_POST['sid'];
  @$amount = $_POST['amount'];
  $orderid = "ORDER_" . time();
  $now = date('Y-m-d H:i:s');
  $test = "INSERT INTO test VALUES ('$now', '$sname', '$sid', '$amount','$orderid', 'initiated')";
  $result = pg_query($con, $test);


  // payment step 1: create a order in database - form saved orderId created.
  // create row in database
  $txn_token = get_paytm_tnx_token($orderid, $amount, $sid);
  echo json_encode(
    array(
      "txnToken" => $txn_token,
      "amount" => $amount,
      "orderid" => $orderid
    )
  );
}


if ($formtype === "attendance") {
  $user_id = @$_POST['userId'];
  $punch_time = date('Y-m-d H:i:s');
  $date = date('Y-m-d');
  $ip_address = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['REMOTE_ADDR']; // Fallback to REMOTE_ADDR if REMOTE_ADDR is not set
  $recorded_by = $_SESSION['aid'];

  // Assuming the GPS location is sent from the frontend in the following format
  $latitude = @$_POST['latitude'];
  $longitude = @$_POST['longitude'];
  $gps = ($latitude && $longitude) ? "POINT($latitude $longitude)" : null;

  // Fetch the status from rssimyprofile_student table
  $select_status_query = 'SELECT studentname, filterstatus, category, class FROM rssimyprofile_student WHERE student_id = $1 LIMIT 1';
  pg_prepare($con, "select_status", $select_status_query);
  $status_result = pg_execute($con, "select_status", array($user_id));
  $status_row = pg_fetch_assoc($status_result);

  if ($status_row) {
    // If a result is found in rssimyprofile_student table
    $status = $status_row['filterstatus'];
    $name = $status_row['studentname'];
    $category = $status_row['category'];
    $class = $status_row['class'];
    $engagement = ""; // Assuming engagement is not present in rssimyprofile_student
  } else {
    // If no result is found in rssimyprofile_student table, fetch from rssimyaccount_members table
    $select_status_query = 'SELECT filterstatus, fullname, engagement FROM rssimyaccount_members WHERE associatenumber = $1 LIMIT 1';
    pg_prepare($con, "select_status_members", $select_status_query);
    $status_result_members = pg_execute($con, "select_status_members", array($user_id));
    $status_row_members = pg_fetch_assoc($status_result_members);

    if ($status_row_members) {
      // If a result is found in rssimyaccount_members table
      $status = $status_row_members['filterstatus'];
      $name = $status_row_members['fullname'];
      $engagement = $status_row_members['engagement'];
      $category = ""; // Set a default value or handle accordingly, as this column doesn't exist in rssimyaccount_members
      $class = ""; // Set a default value or handle accordingly, as this column doesn't exist in rssimyaccount_members
    } else {
      // If no result is found in both tables, set default values or handle accordingly
      $status = 'default_status';
      $name = 'default_name';
      $engagement = 'default_engagement';
      $category = 'default_category';
      $class = 'default_class';
    }
  }

  // Now $status, $name, $engagement, $category, and $class contain the desired values.

  // Prepared statement for INSERT query
  $insert_query = 'INSERT INTO public.attendance(user_id, punch_in, ip_address, gps_location, recorded_by, date, status) VALUES ($1, $2, $3, $4, $5, $6, $7)';
  pg_prepare($con, "insert_attendance", $insert_query);
  $result = pg_execute($con, "insert_attendance", array($user_id, $punch_time, $ip_address, $gps, $recorded_by, $date, $status));

  if (pg_affected_rows($result) !== 1) {
    echo json_encode(
      array(
        "error" => "Unable to record attendance"
      )
    );
  } else {
    // Prepared statement for first SELECT query
    $select_query = "SELECT user_id, min(punch_in) punch_in, max(punch_in) punch_out FROM attendance WHERE date='$date' AND user_id='$user_id' GROUP BY user_id";
    $result = pg_query($con, $select_query);
    $row = pg_fetch_assoc($result);
    $punch_in = $row["punch_in"];
    $punch_out = $row["punch_out"];
    if (strtotime($punch_in) === strtotime($punch_out)) {
      // not yet punched out
      $punch_out = "";
    } else {
      $punch_out = date('d/m/Y h:i:s a', strtotime($punch_out));
    }

    // Set the category field based on its availability (either category or engagement)
    $categoryValue = ($category !== "") ? $category : $engagement;

    echo json_encode(
      array(
        "userId" => $user_id,
        "userName" => $name,
        "punchIn" => date('d/m/Y h:i:s a', strtotime($punch_in)),
        "timestamp" => date('d/m/Y h:i:s a', strtotime($punch_time)),
        "status" => $status,
        "category" => $categoryValue,
        "class" => $class,
        "ipAddress" => $ip_address,
        "gpsLocation" => $gps,
        "punchOut" => $punch_out,
      )
    );
  }
}

if (@$_POST['form-type'] == "admission") {

  $type_of_admission = $_POST['type-of-admission'];
  $student_name = $_POST['student-name'];
  $date_of_birth = $_POST['date-of-birth'];
  $gender = $_POST['gender'];
  $uploadedFile_student_photo = $_FILES['student-photo'];
  $aadhar_available = $_POST['aadhar-card'];
  $aadhar_card = $_POST['aadhar-number'];
  $uploadedFile_aadhar_card = $_FILES['aadhar-card-upload'];
  $guardian_name = $_POST['guardian-name'];
  $guardian_relation = $_POST['relation'];
  $guardian_aadhar = $_POST['guardian-aadhar-number'];
  $state_of_domicile = $_POST['state'];
  $postal_address = htmlspecialchars($_POST['postal-address'], ENT_QUOTES, 'UTF-8');
  $telephone_number = $_POST['telephone'];
  $email_address = $_POST['email'];
  $preferred_branch = $_POST['branch'];
  $class = $_POST['class'];
  $school_admission_required = $_POST['school-required'];
  $school_name = htmlspecialchars($_POST['school-name'], ENT_QUOTES, 'UTF-8');
  $board_name = $_POST['board-name'];
  $medium = $_POST['medium'];
  $family_monthly_income = $_POST['income'];
  $total_family_members = $_POST['family-members'];
  $payment_mode = $_POST['payment-mode'];
  $c_authentication_code = $_POST['c-authentication-code'];
  $transaction_id = $_POST['transaction-id'];
  $subject_select = $_POST['subject-select'];
  @$timestamp = date('Y-m-d H:i:s');

  // Determine if student is new admission or not
  $is_new_admission = ''; // Set to true if new admission, false otherwise

  if ($type_of_admission == 'New Admission') {
    $is_new_admission = 'A';
  } else {
    $is_new_admission = 'B';
  }

  // Determine branch code
  $branch = ''; // Set branch code (LKO for Lucknow, KOL for West Bengal)

  // Replace with the correct branch code based on the branch location
  if ($preferred_branch == 'Lucknow') {
    $branch = 'LKO';
  } elseif ($preferred_branch == 'West Bengal') {
    $branch = 'KOL';
  }

  // Determine current year
  $current_year = date('y'); // Two-digit year from current date

  // Generate a random 3-digit number
  $random_number = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);

  // Generate student ID
  $logic_1 = $is_new_admission; // Logic 1
  $logic_2 = $branch; // Logic 2
  $logic_3 = $current_year; // Logic 3
  $logic_4 = $random_number; // Logic 4

  $student_id = $logic_1 . $logic_2 . $logic_3 . $logic_4; // Concatenate all four logics

  // send uploaded file to drive
  // get the drive link
  if (empty($_FILES['aadhar-card-upload']['name'])) {
    $doclink_aadhar_card = null;
  } else {
    $filename_aadhar_card = "doc_" . $student_name . "_" . time();
    $parent_aadhar_card = '1NdMb6fh4eZ_2yVwaTK088M9s5Yn7MSVbq1D7oTU6loZIe4MokkI9yhhCorqD6RaSfISmPrya';
    $doclink_aadhar_card = uploadeToDrive($uploadedFile_aadhar_card, $parent_aadhar_card, $filename_aadhar_card);
  }
  if (empty($_FILES['student-photo']['name'])) {
    $doclink_student_photo = null;
  } else {
    $filename_student_photo = "doc_" . $student_name . "_" . time();
    $parent_student_photo = '1ziDLJgSG7zTYG5i0LzrQ6pNq9--LQx3_t0_SoSR2tSJW8QTr-7EkPUBR67zn0os5NRfgeuDH';
    $doclink_student_photo = uploadeToDrive($uploadedFile_student_photo, $parent_student_photo, $filename_student_photo);
  }

  $student = "INSERT INTO rssimyprofile_student (type_of_admission,studentname,dateofbirth,gender,student_photo_raw,aadhar_available,studentaadhar,upload_aadhar_card,guardiansname,relationwithstudent,guardianaadhar,stateofdomicile,postaladdress,contact,emailaddress,preferredbranch,class,schooladmissionrequired,nameoftheschool,nameoftheboard,medium,familymonthlyincome,totalnumberoffamilymembers,payment_mode,c_authentication_code,transaction_id,student_id,nameofthesubjects,doa) VALUES ('$type_of_admission','$student_name','$date_of_birth','$gender','$doclink_student_photo','$aadhar_available','$aadhar_card','$doclink_aadhar_card','$guardian_name','$guardian_relation','$guardian_aadhar','$state_of_domicile','$postal_address','$telephone_number','$email_address','$preferred_branch','$class','$school_admission_required','$school_name','$board_name','$medium','$family_monthly_income','$total_family_members','$payment_mode','$c_authentication_code','$transaction_id','$student_id','$subject_select','$timestamp')";

  $result = pg_query($con, $student);

  if ($result) {
    $cmdtuples = pg_affected_rows($result);
    if ($cmdtuples == 1) {
      $response = array("success" => true, "message" => "Form submitted successfully.");
      echo json_encode($response);
      if (@$cmdtuples == 1 && $email_address != "") {
        sendEmail("admission_success", array(
          "student_id" => $student_id,
          "student_name" => $student_name,
          "preferred_branch" => $preferred_branch,
          "timestamp" => @date("d/m/Y g:i a", strtotime($timestamp))
        ), $email_address);
      }
    } else {
      $response = array("success" => false, "message" => "Form submission failed.");
      echo json_encode($response);
    }
  } else {
    $response = array("success" => false, "message" => "Form submission failed.");
    echo json_encode($response);
  }
}

if ($formtype == "get_details_visit") {
  @$contactnumber = $_POST['contactnumber_verify_input'];
  $getdetails = "SELECT fullname, email, tel FROM visitor_userdata WHERE tel='$contactnumber'";
  $result = pg_query($con, $getdetails);
  if ($result) {
    $row = pg_fetch_assoc($result);
    if ($row) {
      echo json_encode(array('status' => 'success', 'data' => $row));
    } else {
      echo json_encode(array('status' => 'no_records', 'message' => 'No records found in the database. Register as a new user.'));
    }
  } else {
    echo json_encode(array('status' => 'error', 'message' => 'Error retrieving user data'));
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
    $mentoremail = $_POST['mentoremail'];
    $declaration = $_POST['declaration'];
    $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
    $timestamp = date('Y-m-d H:i:s');
    $visitId = uniqid();
    $cmdtuples = 0; // Initialize cmdtuples
    $errorOccurred = false; // Flag to track if an error occurred
    $errorMessage = '';

    // This is for paymentdoc and instituteid which will be require in both new and existing visitor

    if (empty($_FILES['paymentdoc']['name'])) {
      $doclink_paymentdoc = null;
    } else {
      $filename_paymentdoc = "doc_" . $visitId . "_" . time();
      $parent_paymentdoc = '1f_UvwDaxvloRyYgNs9rjl6ZGW8nUB8RwXHQtQ3RsA9W6SaYY-7xLNn0kXvGV8A9fAjJ6x9yZ';
      $doclink_paymentdoc = uploadeToDrive($paymentdoc, $parent_paymentdoc, $filename_paymentdoc);
    }
    if (empty($_FILES['instituteid']['name'])) {
      $doclink_instituteid = null;
    } else {
      $filename_instituteid = "doc_" . $visitId . "_" . time();
      $parent_instituteid = '1OFTSdUFZm1RVYNWaux1jv_EW5iftuKh_RSLHXkdbqLacK9nziY-Vx4KrUrJoiNIvohQPSzi3';
      $doclink_instituteid = uploadeToDrive($instituteid, $parent_instituteid, $filename_instituteid);
    }

    if ($_POST['visitorType'] === "existing") {
      $visitorQuery = "INSERT INTO visitor_visitdata (visitid, tel, visitbranch, visitstartdatetime, visitenddate, visitpurpose, institutename, enrollmentnumber, instituteid, mentoremail, paymentdoc, declaration, timestamp, other_reason) 
                          VALUES ('$visitId', '$tel', '$visitbranch', '$visitstartdatetime', '$visitenddate', '$visitpurpose', '$institutename', '$enrollmentnumber', '$doclink_instituteid', '$mentoremail', '$doclink_paymentdoc', '$declaration', '$timestamp', '$other_reason')";
      $resultUserdata = pg_query($con, $visitorQuery);

      if ($resultUserdata) {
        $cmdtuples = pg_affected_rows($resultUserdata);
      } else {
        $errorOccurred = true;
        $errorMessage = handleInsertionErrorVisit($con, "Visitor insertion", $tel);
      }
    } elseif ($_POST['visitorType'] === "new") {
      $fullName = $_POST['fullName'];
      $email = $_POST['email'];
      $contactNumberNew = $_POST['contactNumberNew'];
      $nationalId = $_FILES['nationalId'];
      $photo = $_FILES['photo'];

      // This is for nationalId and photo which will be require for new visitor

      if (empty($_FILES['nationalId']['name'])) {
        $doclink_nationalId = null;
      } else {
        $filename_nationalId = "doc_" . $fullName . "_" . time();
        $parent_nationalId = '1sdu_dkdrRezOr6IdRMJOknFOSovz1qGP2zRg9Db5IyLIMtVxwWgy-Io8aV36B4uTx9-Gwg3W';
        $doclink_nationalId = uploadeToDrive($nationalId, $parent_nationalId, $filename_nationalId);
      }
      if (empty($_FILES['photo']['name'])) {
        $doclink_photo = null;
      } else {
        $filename_photo = "doc_" . $fullName . "_" . time();
        $parent_photo = '1bgjv3Ei5Go073xcZa7sXKjlOFSTHdoqo8Ffdba_ICNFdcq4ashhxTHGEr0rbX3KdH3CjDbwH';
        $doclink_photo = uploadeToDrive($photo, $parent_photo, $filename_photo);
      }

      // Insert userdata
      $userdataQuery = "INSERT INTO visitor_userdata (fullname, email, tel, nationalid, photo) 
                          VALUES ('$fullName', '$email', '$contactNumberNew', '$doclink_nationalId', '$doclink_photo')";
      @$resultUserdata = pg_query($con, $userdataQuery);

      if ($resultUserdata) {
        // Insert visit
        $visitQuery = "INSERT INTO visitor_visitdata (visitid, tel, visitbranch, visitstartdatetime, visitenddate, visitpurpose, institutename, enrollmentnumber, instituteid, mentoremail, paymentdoc, declaration, timestamp, other_reason) 
                              VALUES ('$visitId', '$contactNumberNew', '$visitbranch', '$visitstartdatetime', '$visitenddate', '$visitpurpose', '$institutename', '$enrollmentnumber', '$doclink_instituteid', '$mentoremail', '$doclink_paymentdoc', '$declaration', '$timestamp', '$other_reason')";
        $resultVisitor = pg_query($con, $visitQuery);

        if ($resultVisitor) {
          $cmdtuples = pg_affected_rows($resultVisitor);
        } else {
          $errorOccurred = true;
          $errorMessage = handleInsertionErrorVisit($con, "Visitor insertion", $contactNumberNew);
        }
      } else {
        $errorOccurred = true;
        $errorMessage = handleInsertionErrorVisit($con, "Userdata insertion", $contactNumberNew);
      }
    }

    // After successful form submission
    if (!$errorOccurred) {
      // Sending email based on the visitor type
      if ($_POST['visitorType'] === "existing") {
        $emailQuery = "SELECT email, fullname FROM visitor_userdata WHERE tel='$tel'";
      } else if ($_POST['visitorType'] === "new") {
        $emailQuery = "SELECT email, fullname FROM visitor_userdata WHERE tel='$contactNumberNew'";
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

      if (($_POST['visitorType'] === "existing" || $_POST['visitorType'] === "new") && $email != "") {
        sendEmail("visit_request_ack", array(
          "fullname" => $name,
          "visitId" => $visitId,
          "timestamp" => date("d/m/Y h:i A", strtotime($timestamp)),
          "tel" => @$tel . @$contactNumberNew,
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
      'errorMessage' => $errorOccurred ? $errorMessage : '', // Return the actual error message if an error occurred
      'cmdtuples' => $cmdtuples,
      'visitorId' => $visitId
    );

    // Return the response as JSON
    echo json_encode($responseData);
    exit; // Stop further PHP execution
  }
}

function handleInsertionErrorVisit($connection, $errorMessage, $tel)
{
  $error = pg_last_error($connection);

  // Check for duplicate key violation error
  if (strpos($error, 'duplicate key value violates unique constraint') !== false) {
    return "already_registered"; // Return a specific code for duplicate key violation error
    // Additional handling specific to duplicate key violation error can be done here
  } else {
    // Log or display the generic error message
    error_log($errorMessage . " error: " . $error);
    return "generic_error"; // Return a specific code for generic error
  }
}

if ($formtype == "visitreviewform") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$reviewer_name = $_POST['reviewer_name'];
  @$visitid = $_POST['visitid'];
  @$visitbranch = $_POST['visitbranch'];
  @$visitstartdatetime = $_POST['visitstartdatetime'];
  @$visitenddate = $_POST['visitenddate'];
  @$visitstatus = $_POST['visitstatus'];
  @$hrremarks = htmlspecialchars($_POST['hrremarks'], ENT_QUOTES, 'UTF-8');
  $now = date('Y-m-d H:i:s');

  $visitapproval = "UPDATE visitor_visitdata SET  visitstatusupdatedby = '$reviewer_id', visitstatusupdatedon = '$now', visitstatus = '$visitstatus',remarks = '$hrremarks', visitbranch = '$visitbranch', visitstartdatetime = '$visitstartdatetime', visitenddate = '$visitenddate' WHERE visitid = '$visitid'";
  $result = pg_query($con, $visitapproval);
}

if ($_POST['form-type'] == "contact_Form") {
  // Retrieve form data
  $queryid = uniqid();
  $name = $_POST['name'];
  $email = $_POST['email'];
  $contact = $_POST['contact'];
  $message = htmlspecialchars($_POST['message'], ENT_QUOTES, 'UTF-8');
  $timestamp = date('Y-m-d H:i:s');

  // Insert data into the database
  $contactinsert = "INSERT INTO contact (queryid, name, email, contact, message, timestamp) 
                      VALUES (
                          '$queryid', 
                          '$name', 
                          '$email', 
                          '$contact', 
                          '$message', 
                          '$timestamp'
                      );";

  // Execute the query
  $result = pg_query($con, $contactinsert);

  // Check if the query was successful
  if ($result) {
    // Query executed successfully
    echo json_encode(array("error" => false, "queryid" => $queryid));
  } else {
    // Query failed
    echo json_encode(array("error" => true, "errorMessage" => "Error occurred during form submission."));
  }
  if ($email != "") {
    sendEmail("contact_us_ack", array(
      "name" => $name,
      "queryid" => $queryid,
      "email" => $email,
      "timestamp" => date("d/m/Y h:i A", strtotime($timestamp)),
      "contact" => @$contact,
      "message" => $message
    ), $email, true);
  }
}

if (@$_POST['form-type'] == "signup") {
  $applicant_name = $_POST['applicant-name'];
  $date_of_birth = $_POST['date-of-birth'];
  $gender = $_POST['gender'];
  $telephone = $_POST['telephone'];
  $email = $_POST['email'];
  $branch = $_POST['branch'];
  $association = $_POST['association'];
  $job_select = $_POST['job-select'];
  $purpose = $_POST['purpose'];
  $interests = $_POST['interests'];
  $post_select = $_POST['post-select'];
  $medium = $_POST['medium'];
  $membership_purpose = $_POST['membershipPurpose'];
  $subject1 = $_POST['subject1'];
  $subject2 = $_POST['subject2'];
  $subject3 = $_POST['subject3'];
  $heard_about = $_POST['heard_about'];
  $consent = !empty($_POST['consent']) ? 1 : 0;
  $application_number = uniqid();
  $timestamp = date('Y-m-d H:i:s');
  $uploadedFile_payment = $_FILES['payment-photo'];
  $uploadedFile_photo = $_FILES['applicant-photo'];
  $uploadedFile_resume = $_FILES['resume-upload'];
  $duration = $_POST['duration'] ?? null;
  $availability = !empty($_POST['availability']) ? implode(",", $_POST['availability']) : null;

  if (empty($_FILES['payment-photo']['name'])) {
    $doclink_payment_photo = null;
  } else {
    $filename_payment_photo = "payment_" . "$application_number" . "_" . time();
    $parent_payment_photo = '1dEIPRCM8PQZkCg1rezT6Eggqm_H-a_S4kWNKvfcPCuYMYJp6r8EoD0p_NHcijrqPks7C0KNq';
    $doclink_payment_photo = uploadeToDrive($uploadedFile_payment, $parent_payment_photo, $filename_payment_photo);
  }

  if (empty($_FILES['applicant-photo']['name'])) {
    $doclink_applicant_photo = null;
  } else {
    $filename_applicant_photo = "photo_" . "$application_number" . "_" . time();
    $parent_applicant_photo = '1CgXW0M1ClTLRFrJjOCh490GVAq0IVAlM5OmAcfTtXVWxmnR9cx_I_Io7uD_iYE7-5rWDND82';
    $doclink_applicant_photo = uploadeToDrive($uploadedFile_photo, $parent_applicant_photo, $filename_applicant_photo);
  }

  if (empty($_FILES['resume-upload']['name'])) {
    $doclink_resume_photo = null;
  } else {
    $filename_resume_photo = "resume_" . "$application_number" . "_" . time();
    $parent_resume_photo = '1YyJLwbXQqNJeESSfPINjTW2OVFOh5IGD53Aaf1ZNqsnDeWAFdh6ECr3TnbNXM95yWdS5si-z';
    $doclink_resume_photo = uploadeToDrive($uploadedFile_resume, $parent_resume_photo, $filename_resume_photo);
  }


  // Build the SQL query
  $columns = "applicant_name, date_of_birth, gender, telephone, email, branch, association, job_select, purpose, interests, post_select, medium, membership_purpose, payment_photo, applicant_photo, resume_upload, heard_about, consent, timestamp, application_number, subject1, subject2, subject3";
  $values = "'$applicant_name', '$date_of_birth', '$gender', '$telephone', '$email', '$branch', '$association', '$job_select', '$purpose', '$interests', '$post_select', '$medium', '$membership_purpose', '$doclink_payment_photo', '$doclink_applicant_photo','$doclink_resume_photo','$heard_about', '$consent','$timestamp','$application_number','$subject1','$subject2','$subject3'";

  // Conditionally add duration to columns and values
  if ($duration != null) {
    $columns .= ", duration";
    $values .= ", $duration";
  }
  if ($availability != null) {
    $columns .= ", availability";
    $values .= ", '$availability'";
  }


  // Build the full query
  $signup = "INSERT INTO signup ($columns) VALUES ($values)";

  // Execute the query
  $result = pg_query($con, $signup);
  $cmdtuples = pg_affected_rows($result);

  // Check if the query was successful
  if ($result) {
    if ($cmdtuples == 1) {
      echo json_encode(array("error" => false, "application_number" => $application_number));
      if ($email != "") {
        // Adjust the parameters for your sendEmail function accordingly
        sendEmail("signup_success", array(
          "applicant_name" => $applicant_name,
          "branch" => $branch,
          "association" => $association,
          "application_number" => $application_number,
          "timestamp" => date("d/m/Y g:i a", strtotime($timestamp))
        ), $email);
      }
    }
  } else {
    // Query failed
    echo json_encode(array("error" => true, "errorMessage" => "Error occurred during form submission."));
  }
}
