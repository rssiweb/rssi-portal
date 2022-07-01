<!-- This API has been used in student.php file for fees collection -->
<?php
include("database.php");
date_default_timezone_set('Asia/Kolkata');
echo json_encode($_POST);
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
} ?>

<?php

if ($_POST['form-type'] == "transfer") {
  @$refid = $_POST['pid'];
  $pstatus = "UPDATE fees SET  pstatus = 'transferred' WHERE id = $refid";
  $result = pg_query($con, $pstatus);
} ?>

<?php

if ($_POST['form-type'] == "paydelete") {
  @$refid = $_POST['pid'];
  $paydelete = "DELETE from fees WHERE id = $refid";
  $result = pg_query($con, $paydelete);
} ?>

<?php

date_default_timezone_set('Asia/Kolkata');
echo json_encode($_POST);
if ($_POST['form-type'] == "ipfpush") {
  @$membername2 = $_POST['membername2'];
  @$memberid2 = $_POST['memberid2'];
  @$ipf = $_POST['ipf'];
  @$flag = $_POST['flag'];
  $now = date('Y-m-d H:i:s');
  $ipfpush = "INSERT INTO ipfsubmission VALUES ('$now','$memberid2','$membername2','$ipf','$flag')";
  $result = pg_query($con, $ipfpush);
} ?>

<?php

date_default_timezone_set('Asia/Kolkata');
if ($_POST['form-type'] == "ipfsubmission") {
  @$ipfid = $_POST['ipfid'];
  @$status2 = $_POST['status2'];
  $now = date('Y-m-d H:i:s');
  $ipfclose = "UPDATE ipfsubmission SET  status2 = '$status2', respondedon = '$now' WHERE id = $ipfid";
  $result = pg_query($con, $ipfclose);
} ?>

<?php

if ($_POST['form-type'] == "ipfclose") {
  @$ipfid = $_POST['ipfid'];
  @$ipfstatus = $_POST['ipfstatus'];
  $now = date('Y-m-d H:i:s');
  $ipfclose = "UPDATE ipfsubmission SET  ipfstatus = 'closed', closedon = '$now' WHERE id = $ipfid";
  $result = pg_query($con, $ipfclose);
} ?>

<?php

date_default_timezone_set('Asia/Kolkata');
echo json_encode($_POST);
if ($_POST['form-type'] == "test") {
  @$sname = $_POST['sname'];
  @$sid = $_POST['sid'];
  @$amount = $_POST['amount'];
  @$orderid = $_POST['orderid'];
  $now = date('Y-m-d H:i:s');
  $test = "INSERT INTO test VALUES ('$now','$sname','$sid','$amount','$orderid')";
  $result = pg_query($con, $test);
} ?>