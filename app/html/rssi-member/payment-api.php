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
  // echo json_encode($result);
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
  $item = $entityManager->getRepository('onboarding')->find($otp_initiatedfor); //primary key
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
  $item = $entityManager->getRepository('onboarding')->find($otp_initiatedfor); //primary key
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
    $message = $_POST['message'];
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
      $postalAddress = $_POST['postalAddress'];

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
  $now = date('Y-m-d H:i:s');
  $ipfclose = "UPDATE appraisee_response SET  ipf_response = '$status2', ipf_response_on = '$now', ipf_response_by='$ipf_response_by' WHERE goalsheetid = '$ipfid'";
  $ipf_history = "INSERT INTO ipf_history (goalsheetid, ipf_response, ipf_response_on, ipf_response_by, ipf) VALUES ('$ipfid','$status2','$now','$ipf_response_by',$ipf)";
  $result = pg_query($con, $ipfclose);
  $result_history = pg_query($con, $ipf_history);
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

  // Prepared statement for INSERT query
  $insert_query = 'INSERT INTO public.attendance(user_id, punch_in, ip_address, gps_location, recorded_by, date) VALUES ($1, $2, $3, $4, $5, $6)';
  pg_prepare($con, "insert_attendance", $insert_query);
  $result = pg_execute($con, "insert_attendance", array($user_id, $punch_time, $ip_address, $gps, $recorded_by, $date));

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

    // Prepared statement for second SELECT query
    $query = "SELECT fullname, filterstatus, engagement FROM rssimyaccount_members WHERE associatenumber='$user_id'";
    $result = pg_query($con, $query);
    $name = $status = $engagement = $category = $class = "";

    if (pg_num_rows($result) == 1) {
      $row = pg_fetch_assoc($result);
      $name = $row["fullname"];
      $status = $row["filterstatus"];
      $engagement = $row["engagement"];
    } else {
      $query = "SELECT studentname, filterstatus, category, class FROM rssimyprofile_student WHERE student_id='$user_id'";
      $result = pg_query($con, $query);

      if (pg_num_rows($result) == 1) {
        $row = pg_fetch_assoc($result);
        $name = $row["studentname"];
        $status = $row["filterstatus"];
        $category = $row["category"];
        $class = $row["class"];
      }
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
  $postal_address = $_POST['postal-address'];
  $telephone_number = $_POST['telephone'];
  $email_address = $_POST['email'];
  $preferred_branch = $_POST['branch'];
  $class = $_POST['class'];
  $school_admission_required = $_POST['school-required'];
  $school_name = $_POST['school-name'];
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
