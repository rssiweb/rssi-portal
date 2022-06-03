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
  $now = date('Y-m-d H:i:s');
  $feesupdate = "INSERT INTO fees VALUES ('$now','$sname','$studentid','$fees','$month','$collectedby')";
  $result = pg_query($con, $feesupdate);
} ?>

<?php
include("database.php");
if ($_POST['form-type'] == "transfer") {
  @$refid = $_POST['pid'];
  $pstatus = "UPDATE fees SET  pstatus = 'transferred' WHERE id = $refid";
  $result = pg_query($con, $pstatus);
} ?>

<?php
include("database.php");
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
include("database.php");
if ($_POST['form-type'] == "ipfsubmission") {
  @$ipfid = $_POST['ipfid'];
  @$status2 = $_POST['status2'];
  $ipfclose = "UPDATE ipfsubmission SET  status2 = '$status2' WHERE id = $ipfid";
  $result = pg_query($con, $ipfclose);
} ?>

<?php
include("database.php");
if ($_POST['form-type'] == "ipfclose") {
  @$ipfid = $_POST['ipfid'];
  @$ipfstatus = $_POST['ipfstatus'];
  $now = date('Y-m-d H:i:s');
  $ipfclose = "UPDATE ipfsubmission SET  ipfstatus = 'closed', closedon = '$now' WHERE id = $ipfid";
  $result = pg_query($con, $ipfclose);
} ?>