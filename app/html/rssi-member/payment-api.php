<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/paytm-util.php");
include("../../util/email.php");
// require_once("email.php");

date_default_timezone_set('Asia/Kolkata');

if ($_POST['form-type'] == "payment") {
  @$sname = strtoupper($_POST['sname']);
  @$studentid = $_POST['studentid'];
  @$fees = $_POST['fees'];
  // @$month = join(',', $_POST['month']);
  @$month = $_POST['month'];
  @$collectedby = $_POST['collectedby'];
  @$ptype = $_POST['ptype'];
  @$feeyear = $_POST['year'];
  $now = date('Y-m-d H:i:s');
  $feesupdate = "INSERT INTO fees (date, sname, studentid, fees, month, collectedby, ptype,feeyear) VALUES ('$now','$sname','$studentid','$fees','$month','$collectedby','$ptype','$feeyear')";
  $result = pg_query($con, $feesupdate);
  // echo json_encode($result);
}

if ($_POST['form-type'] == "transfer") {
  @$refid = $_POST['pid'];
  $pstatus = "UPDATE fees SET  pstatus = 'transferred' WHERE id = $refid";
  $result = pg_query($con, $pstatus);
}

if ($_POST['form-type'] == "noticebodyedit") {
  @$noticeid = $_POST['noticeid'];
  @$noticebody = $_POST['noticebody'];
  $noticebodyedit = "UPDATE notice SET  noticebody = '$noticebody' WHERE noticeid = '$noticeid'";
  $result = pg_query($con, $noticebodyedit);
}

if ($_POST['form-type'] == "policybodyedit") {
  @$policyid = $_POST['policyid'];
  @$remarks = $_POST['remarks'];
  $policybodyeditt = "UPDATE policy SET  remarks = '$remarks' WHERE policyid = '$policyid'";
  $result = pg_query($con, $policybodyeditt);
}


if ($_POST['form-type'] == "paydelete") {
  @$refid = $_POST['pid'];
  $paydelete = "DELETE from fees WHERE id = $refid";
  $result = pg_query($con, $paydelete);
}

if ($_POST['form-type1'] == "policydelete") {
  @$policydid = $_POST['policydeleteid'];
  $deletepolicy = "DELETE from policy WHERE policyid = '$policydid'";
  $result = pg_query($con, $deletepolicy);
}

if ($_POST['form-type'] == "paydelete") {
  @$refid = $_POST['pid'];
  $paydelete = "DELETE from fees WHERE id = $refid";
  $result = pg_query($con, $paydelete);
}

if ($_POST['form-type'] == "leavedelete") {
  @$leavedeleteid = $_POST['leavedeleteid'];
  $leavedelete = "DELETE from leavedb_leavedb WHERE leaveid = '$leavedeleteid'";
  $result = pg_query($con, $leavedelete);
}

if ($_POST['form-type'] == "claimdelete") {
  @$claimdeleteid = $_POST['claimdeleteid'];
  $claimdelete = "DELETE from claim WHERE reimbid = '$claimdeleteid'";
  $result = pg_query($con, $claimdelete);
}

if ($_POST['form-type'] == "leaveadjdelete") {
  @$leaveadjdeleteid = $_POST['leaveadjdeleteid'];
  $leaveadjdelete = "DELETE from leaveadjustment WHERE leaveadjustmentid = '$leaveadjdeleteid'";
  $result = pg_query($con, $leaveadjdelete);
}

if ($_POST['form-type'] == "leaveallodelete") {
  @$leaveallodeleteid = $_POST['leaveallodeleteid'];
  $leaveallodelete = "DELETE from leaveallocation WHERE leaveallocationid = '$leaveallodeleteid'";
  $result = pg_query($con, $leaveallodelete);
}

if ($_POST['form-type'] == "cmsdelete") {
  @$certificate_no = $_POST['cmsid'];
  $cmsdelete = "DELETE from certificate WHERE certificate_no = '$certificate_no'";
  $result = pg_query($con, $cmsdelete);
}

if ($_POST['form-type'] == "gemsdelete") {
  @$redeem_id = $_POST['redeem_id'];
  $gemsdelete = "DELETE from gems WHERE redeem_id = '$redeem_id'";
  $result = pg_query($con, $gemsdelete);
}

// if ($_POST['form-type'] == "gpsdelete") {
//   @$gpsid = $_POST['gpsid'];
//   $gpsdelete = "DELETE from gps WHERE itemid = '$gpsid'";
//   $result = pg_query($con, $gpsdelete);
// }


if ($_POST['form-type'] == "gpsedit") {
  @$itemid = $_POST['itemid1'];
  @$itemname = $_POST['itemname'];
  @$itemtype = $_POST['itemtype'];
  @$quantity = $_POST['quantity'];
  @$remarks = $_POST['remarks'];
  @$collectedby = strtoupper($_POST['collectedby']);
  @$taggedto = strtoupper($_POST['taggedto']);
  $now = date('Y-m-d H:i:s');
  $gpshistory = "INSERT INTO gps_history (itemid, date, itemtype, itemname, quantity, remarks, collectedby,taggedto) VALUES ('$itemid','$now','$itemtype','$itemname','$quantity','$remarks','$collectedby','$taggedto')";
  $tagedit = "UPDATE gps SET  lastupdatedon='$now', itemid='$itemid', itemtype='$itemtype', itemname='$itemname', quantity='$quantity', remarks='$remarks', collectedby='$collectedby',taggedto='$taggedto' WHERE itemid = '$itemid'";
  $result = pg_query($con, $tagedit);
  $result = pg_query($con, $gpshistory);
}


if ($_POST['form-type'] == "gen_otp_associate") {
  @$otp_initiatedfor = $_POST['otp_initiatedfor'];

  // Generate a random 6 digit number
  $otp = rand(100000, 999999);
  $hashedValue = password_hash($otp, PASSWORD_DEFAULT);

  $gen_otp_associate = "UPDATE resourcemovement SET  onboarding_gen_otp_associate = '$hashedValue', otp_ass = '$otp' WHERE onboarding_associate_id = '$otp_initiatedfor'";
  $result = pg_query($con, $gen_otp_associate);
  if($result)
  {
      $rows = pg_num_rows($result);
      if($rows == 0){
        http_response_code(400);
      }
      echo $rows;
  } else {
    $error = pg_last_error($con);
    echo $error;
  }
}

if ($_POST['form-type'] == "gen_otp_centr") {

  @$otp_initiatedfor = $_POST['otp_initiatedfor'];

  // Generate a random 6 digit number
  $otp = rand(100000, 999999);
  $hashedValue = password_hash($otp, PASSWORD_DEFAULT);

  $gen_otp_centr = "UPDATE resourcemovement SET  onboarding_gen_otp_center_incharge = '$hashedValue', otp_centr = '$otp' WHERE onboarding_associate_id = '$otp_initiatedfor'";
  $result = pg_query($con, $gen_otp_centr);
  if($result)
  {
      $rows = pg_num_rows($result);
      if($rows == 0){
        http_response_code(400);
      }
      echo $rows;
  } else {
    $error = pg_last_error($con);
    echo $error;
  }
}

if ($_POST['form-type'] == "initiatingonboarding") {
  @$initiatedfor = $_POST['initiatedfor'];
  @$initiatedby = $_POST['initiatedby'];
  $now = date('Y-m-d H:i:s');
  $initiatingonboarding = "INSERT INTO resourcemovement (onboarding_associate_id, onboard_initiated_by, onboard_initiated_on) VALUES ('$initiatedfor','$initiatedby','$now')";
  $result = pg_query($con, $initiatingonboarding);
}


if ($_POST['form-type'] == "ipfsubmission") {
  @$ipfid = $_POST['ipfid'];
  @$status2 = $_POST['status2'];
  @$ipf_response_by = $_POST['ipf_response_by'];
  $now = date('Y-m-d H:i:s');
  $ipfclose = "UPDATE appraisee_response SET  ipf_response = '$status2', ipf_response_on = '$now', ipf_response_by='$ipf_response_by' WHERE goalsheetid = '$ipfid'";
  $result = pg_query($con, $ipfclose);
}

if ($_POST['form-type'] == "gemsredeem") {
  @$reviewer_id = $_POST['reviewer_id'];
  @$reviewer_name = $_POST['reviewer_name'];
  @$redeem_idd = $_POST['redeem_idd'];
  @$reviewer_status = $_POST['reviewer_status'];
  @$reviewer_remarks = $_POST['reviewer_remarks'];
  $now = date('Y-m-d H:i:s');
  $gemsredeem = "UPDATE gems SET  reviewer_id = '$reviewer_id',  reviewer_name = '$reviewer_name', reviewer_status = '$reviewer_status', reviewer_remarks = '$reviewer_remarks', reviewer_status_updated_on = '$now' WHERE redeem_id = '$redeem_idd'";
  $result = pg_query($con, $gemsredeem);
}

if ($_POST['form-type'] == "leavereviewform") {
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


  // $applicantid = pg_query($con, "Select applicantid from leavedb_leavedb where leaveid='$leaveid'");
  // $resultt = pg_query($con, "Select fullname,email from rssimyaccount_members where associatenumber='$applicantid'");
  // @$nameassociate = pg_fetch_result($resultt, 0, 0);
  // @$emailassociate = pg_fetch_result($resultt, 0, 1);

  // $resulttt = pg_query($con, "Select studentname,emailaddress from rssimyprofile_student where student_id='$applicantid'");
  // @$namestudent = pg_fetch_result($resulttt, 0, 0);
  // @$emailstudent = pg_fetch_result($resulttt, 0, 1);

  // $applicantname = $nameassociate . $namestudent;
  // $email = $emailassociate . $emailstudent;

  // sendEmail("leaveapply_admin", array(
  //   "leaveid" => $leaveid,
  //   "applicantid" => $applicantid,
  //   "applicantname" => @$applicantname,
  //   "fromdate" => @date("d/m/Y", strtotime($fromdate)),
  //   "todate" => @date("d/m/Y", strtotime($todate)),
  //   "typeofleave" => $typeofleave,
  //   "category" => $creason,
  //   "status" => $status,
  //   "day" => round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1),
  //   "comment" => $comment,
  // ), $email);
}

if ($_POST['form-type'] == "claimreviewform") {
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

if ($_POST['form-type'] == "ipfclose") {
  @$ipfid = $_POST['ipfid'];
  @$ipf_process_closed_by = $_POST['ipf_process_closed_by'];
  $now = date('Y-m-d H:i:s');
  $ipfclose = "UPDATE appraisee_response SET  ipf_process_closed_by = '$ipf_process_closed_by', ipf_process_closed_on = '$now' WHERE goalsheetid = '$ipfid'";
  $result = pg_query($con, $ipfclose);
}

if ($_POST['form-type'] == "test") {
  @$sname = $_POST['sname'];
  @$sid = $_POST['sid'];
  @$amount = $_POST['amount'];
  $orderid  = "ORDER_" . time();
  $now = date('Y-m-d H:i:s');
  $test = "INSERT INTO test VALUES ('$now', '$sname', '$sid', '$amount','$orderid', 'initiated')";
  $result = pg_query($con, $test);


  // payment step 1: create a order in database - form saved orderId created.
  // create row in database
  $txn_token = get_paytm_tnx_token($orderid, $amount, $sid);
  echo json_encode(array(
    "txnToken" => $txn_token,
    "amount" => $amount,
    "orderid" => $orderid
  ));
}
