<?php
// This API has been used in student.php file for fees collection 
include("database.php");
include("../util/paytm-util.php");

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


if ($_POST['form-type'] == "remarksedit") {
  @$itemid = $_POST['itemid'];
  @$remarks = $_POST['remarks'];
  $remarksedit = "UPDATE gps SET  remarks = '$remarks' WHERE itemid = '$itemid'";
  $result = pg_query($con, $remarksedit);
} 

if ($_POST['form-type'] == "nameedit") {
  @$itemid = $_POST['itemid'];
  @$itemname = $_POST['itemname'];
  $nameedit = "UPDATE gps SET  itemname = '$itemname' WHERE itemid = '$itemid'";
  $result = pg_query($con, $nameedit);
} 

if ($_POST['form-type'] == "noticebodyedit") {
  @$noticeid = $_POST['noticeid'];
  @$noticebody = $_POST['noticebody'];
  $noticebodyedit = "UPDATE notice SET  noticebody = '$noticebody' WHERE noticeid = '$noticeid'";
  $result = pg_query($con, $noticebodyedit);
} 

if ($_POST['form-type'] == "tagedit") {
  @$itemid = $_POST['itemid'];
  @$taggedto = $_POST['taggedto'];
  $tagedit = "UPDATE gps SET  taggedto = '$taggedto' WHERE itemid = '$itemid'";
  $result = pg_query($con, $tagedit);
} 

if ($_POST['form-type'] == "issuedbyedit") {
  @$itemid = $_POST['itemid'];
  @$issuedby = $_POST['issuedby'];
  $issuedbyedit = "UPDATE gps SET  collectedby = '$issuedby' WHERE itemid = '$itemid'";
  $result = pg_query($con, $issuedbyedit);
} 

if ($_POST['form-type'] == "paydelete") {
  @$refid = $_POST['pid'];
  $paydelete = "DELETE from fees WHERE id = $refid";
  $result = pg_query($con, $paydelete);
} 

if ($_POST['form-type'] == "gpsdelete") {
  @$gpsid = $_POST['gpsid'];
  $gpsdelete = "DELETE from gps WHERE itemid = '$gpsid'";
  $result = pg_query($con, $gpsdelete);
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
  $orderid  = "ORDER_".time();
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
} ?>