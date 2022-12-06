<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/paytm-util.php");
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
  $now = date('Y-m-d H:i:s');
  $feesupdate = "INSERT INTO fees (date, sname, studentid, fees, month, collectedby, ptype) VALUES ('$now','$sname','$studentid','$fees','$month','$collectedby','$ptype')";
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

if ($_POST['form-type'] == "paydelete") {
  @$refid = $_POST['pid'];
  $paydelete = "DELETE from fees WHERE id = $refid";
  $result = pg_query($con, $paydelete);
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

if ($_POST['form-type'] == "gpsdelete") {
  @$gpsid = $_POST['gpsid'];
  $gpsdelete = "DELETE from gps WHERE itemid = '$gpsid'";
  $result = pg_query($con, $gpsdelete);
}


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




if ($_POST['form-type'] == "ipfpush") {
  @$membername2 = $_POST['membername2'];
  @$memberid2 = $_POST['memberid2'];
  @$ipf = $_POST['ipf'];
  @$flag = $_POST['flag'];
  $now = date('Y-m-d H:i:s');
  $ipfpush = "INSERT INTO ipfsubmission VALUES ('$now','$memberid2','$membername2','$ipf','$flag')";
  $result = pg_query($con, $ipfpush);
}


if ($_POST['form-type'] == "ipfsubmission") {
  @$ipfid = $_POST['ipfid'];
  @$status2 = $_POST['status2'];
  $now = date('Y-m-d H:i:s');
  $ipfclose = "UPDATE ipfsubmission SET  status2 = '$status2', respondedon = '$now' WHERE id = $ipfid";
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
  @$day = round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1);
  $now = date('Y-m-d H:i:s');
  $leaveapproval = "UPDATE leavedb_leavedb SET  status = '$status', fromdate = '$fromdate',  todate = '$todate', comment = '$comment',reviewer_id = '$reviewer_id',  reviewer_name = '$reviewer_name', days = '$day' WHERE leaveid = '$leaveid'";
  $result = pg_query($con, $leaveapproval);
}

if ($_POST['form-type'] == "ipfclose") {
  @$ipfid = $_POST['ipfid'];
  @$ipfstatus = $_POST['ipfstatus'];
  $now = date('Y-m-d H:i:s');
  $ipfclose = "UPDATE ipfsubmission SET  ipfstatus = 'closed', closedon = '$now' WHERE id = $ipfid";
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
