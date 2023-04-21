<?php
require_once __DIR__ . "/../../bootstrap.php";

$today = date("YmdHis");

//FEES EXPORT FUNTION

$export_type = $_POST["export_type"];
header("Content-type: application/csv");
header("Content-Disposition: attachment; filename={$export_type}_$today.csv");
header("Pragma: no-cache");
header("Expires: 0");

if ($export_type == "fees") {
  fees_export();
} else if ($export_type == "donation") {
  donation_export();
} else if ($export_type == "student") {
  student_export();
} else if ($export_type == "gps") {
  gps_export();
} else if ($export_type == "reimb") {
  reimb_export();
}

function fees_export()
{
  global $con;
  @$id = $_POST['id'];
  @$status = $_POST['status'];
  @$section = $_POST['section'];
  @$sections = $_POST['sections'];
  @$stid = $_POST['stid'];


  if (($section != null && $section != 'ALL') && ($status != null && $status != 'ALL')) {
    $result = pg_query($con, "SELECT * FROM fees 
    
    left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    
    WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND feeyear=$id AND student.category IN ('$sections') order by id desc");

    $totalapprovedamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND feeyear=$id AND student.category IN ('$sections')");

    $totaltransferredamount = pg_query($con, "SELECT SUM(fees) FROM fees
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND feeyear=$id AND student.category IN ('$sections') AND pstatus='transferred'");
  }


  if (($section == 'ALL' || $section == null) && ($status != null && $status != 'ALL')) {

    $result = pg_query($con, "SELECT * FROM fees 
    
    left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
    left join (SELECT student_id, studentname,category,contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    
    WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND feeyear=$id order by id desc");

    $totalapprovedamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND feeyear=$id");
    $totaltransferredamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND feeyear=$id AND pstatus='transferred'");
  }

  if (($section != null && $section != 'ALL') && ($status == 'ALL' || $status == null)) {

    $result = pg_query($con, "SELECT * FROM fees 
    
    left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
    left join (SELECT student_id, studentname,category,contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    
    WHERE feeyear=$id AND student.category='$section' order by id desc");

    $totalapprovedamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE feeyear=$id AND student.category='$section'");
    $totaltransferredamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE feeyear=$id AND student.category='$section' AND pstatus='transferred'");
  }

  if (($section == 'ALL' || $section == null) && ($status == 'ALL' || $status == null) && $id != null) {

    $result = pg_query($con, "SELECT * FROM fees 
    
    left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
    left join (SELECT student_id, studentname,category,contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    
    WHERE feeyear=$id order by id desc");

    $totalapprovedamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE feeyear=$id");
    $totaltransferredamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE feeyear=$id AND pstatus='transferred'");
  }

  if ($stid != null && $status == null && $section == null && $id == null) {

    $result = pg_query($con, "SELECT * FROM fees 
    
    left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    
    WHERE fees.studentid='$stid' order by id desc");

    $totalapprovedamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE fees.studentid='$stid'");

    $totaltransferredamount = pg_query($con, "SELECT SUM(fees) FROM fees
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE fees.studentid='$stid' AND pstatus='transferred'");
  }

  if ($stid == null && $status == null && $section == null && $id == null) {
    $result = pg_query($con, "SELECT * FROM fees WHERE month='0'");
    $totalapprovedamount = pg_query($con, "SELECT SUM(fees) FROM fees WHERE month='0'");
    $totaltransferredamount = pg_query($con, "SELECT SUM(fees) FROM fees WHERE month='0'");
  }

  if (!$result) {
    echo "An error occurred.\n";
    exit;
  }

  $resultArr = pg_fetch_all($result);

  echo 'Fees collection date,ID,F Name,Category,Month,Amount,Collected by,Status' . "\n";

  foreach ($resultArr as $array) {

    echo substr($array['date'], 0, 10) . ',' . $array['studentid'] . ',' . strtok($array['studentname'], ' ') . ',' . $array['category'] . ',' . @strftime('%B', mktime(0, 0, 0,  $array['month'])) . ',' . $array['fees'] . ',' . $array['fullname'] . ',' . $array['pstatus'] . "\n";
  }
}

function donation_export()
{


  global $con;
  @$id = $_POST['invoice'];
  @$status = $_POST['fyear'];


  if ($id == null && $status == 'ALL') {
    $result = pg_query($con, "SELECT * FROM donation order by id desc");
    $totaldonatedamount = pg_query($con, "SELECT SUM(donatedamount) FROM donation");
  } else if ($id == null && $status != 'ALL') {
    $result = pg_query($con, "SELECT * FROM donation WHERE year='$status' order by id desc");
    $totaldonatedamount = pg_query($con, "SELECT SUM(donatedamount) FROM donation WHERE year='$status'");
  } else if ($id > 0 && $status != 'ALL') {
    $result = pg_query($con, "SELECT * FROM donation WHERE invoice='$id' AND year='$status' order by id desc");
    $totaldonatedamount = pg_query($con, "SELECT SUM(donatedamount) FROM donation WHERE invoice='$id' AND year='$status'");
  } else if ($id > 0 && $status == 'ALL') {
    $result = pg_query($con, "SELECT * FROM donation WHERE invoice='$id' order by id desc");
    $totaldonatedamount = pg_query($con, "SELECT SUM(donatedamount) FROM donation WHERE invoice='$id'");
  } else {
    $result = pg_query($con, "SELECT * FROM donation order by id desc");
    $totaldonatedamount = pg_query($con, "SELECT SUM(donatedamount) FROM donation");
  }

  if (!$result) {
    echo "An error occurred.\n";
    exit;
  }

  $resultArr = pg_fetch_all($result);

  echo 'Sl. No.,Pre Acknowledgement Number,ID Code,Unique Identification Number,Section Code,Unique Registration Number (URN),Date of Issuance of Unique Registration Number,Name of donor,Address of donor,Donation Type,Mode of receipt,Amount of donation (Indian rupees)' . "\n";

  foreach ($resultArr as $array) {

    echo ',' . ',' . $array['uitype'] . ',' . $array['uinumber'] . ',' . $array['section_code'] . ',AAKCR2540KF20214,' . date("d/m/Y g:i a", strtotime($array['timestamp'])) . ',' . $array['firstname'] . ' ' . $array['lastname'] . ',"' . $array['address'] . '",' . $array['donation_type'] . ',' . $array['modeofpayment'] . ',' . $array['currencyofthedonatedamount'] . ' ' . $array['donatedamount'] . "\n";
  }
}

function gps_export()
{


  global $con;
  @$taggedto = $_POST['taggedto'];
  @$item_type = $_POST['item_type'];
  @$assetid = $_POST['assetid'];


  if ($item_type == 'ALL' && $taggedto == "" && $assetid == "") {
    $gpsdetails = "SELECT * from gps order by itemname asc";
  } else if (($item_type == 'ALL' && $taggedto != "") || ($item_type == "" && $taggedto != "" && $assetid == "")) {
    $gpsdetails = "SELECT * from gps where taggedto='$taggedto' order by itemname asc";
  } else if ($item_type != "ALL" && $item_type != "" && $taggedto != "" && $assetid == "") {
    $gpsdetails = "SELECT * from gps where taggedto='$taggedto' and itemtype='$item_type' order by itemname asc";
  } else if ($item_type != "ALL" && $item_type != "" && $taggedto == "" && $assetid == "") {
    $gpsdetails = "SELECT * from gps where itemtype='$item_type' order by itemname asc";
  } else if ($assetid != "" && $item_type == "" && $taggedto == "") {
    $gpsdetails = "SELECT * from gps where assetid='$assetid' order by itemname asc";
  } else {
    $gpsdetails = "SELECT * from gps";
  }

  $result = pg_query($con, $gpsdetails);

  if (!$result) {
    echo "An error occurred.\n";
    exit;
  }

  $resultArr = pg_fetch_all($result);

  echo 'Asset Id,Asset name,Asset type,Quantity,Tagged to' . "\n";

  foreach ($resultArr as $array) {

    echo $array['itemid'] . ',"' . $array['itemname'] . '",' . $array['itemtype'] . ',' . $array['quantity'] . ',' . $array['taggedto'] . "\n";
  }
}

function reimb_export()
{


  global $con;
  @$status = $_POST['status'];
  @$role = $_POST['role'];
  @$user_check = $_POST['user_check'];

  if ($role == 'Admin') {

    @$id = strtoupper($_POST['id']);

    if ($id == null && ($status == 'ALL' || $status == null)) {
      $result = pg_query($con, "SELECT *, REPLACE (uploadeddocuments, 'view', 'preview') docp FROM claim 
      left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members) faculty ON claim.registrationid=faculty.associatenumber
      left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON claim.registrationid=student.student_id
      order by timestamp desc");
    } else if ($id == null && ($status != 'ALL' && $status != null)) {
      $result = pg_query($con, "SELECT *, REPLACE (uploadeddocuments, 'view', 'preview') docp FROM claim 
      left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members) faculty ON claim.registrationid=faculty.associatenumber
      left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON claim.registrationid=student.student_id
      WHERE year='$status' order by timestamp desc");
    } else if ($id > 0 && ($status != 'ALL' && $status != null)) {
      $result = pg_query($con, "SELECT *, REPLACE (uploadeddocuments, 'view', 'preview') docp FROM claim 
      left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members) faculty ON claim.registrationid=faculty.associatenumber
      left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON claim.registrationid=student.student_id
      WHERE registrationid='$id' AND year='$status' order by timestamp desc");
    } else if ($id !=null && ($status == 'ALL' || $status == null)) {
      $result = pg_query($con, "SELECT *, REPLACE (uploadeddocuments, 'view', 'preview') docp FROM claim 
      left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members) faculty ON claim.registrationid=faculty.associatenumber
      left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON claim.registrationid=student.student_id
      WHERE registrationid='$id' order by timestamp desc");
    }
  }

  if ($role != 'Admin' && $status != 'ALL') {

    $result = pg_query($con, "SELECT *, REPLACE (uploadeddocuments, 'view', 'preview') docp FROM claim 
    left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members WHERE associatenumber='$user_check') faculty ON claim.registrationid=faculty.associatenumber
    left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student WHERE student_id='$user_check') student ON claim.registrationid=student.student_id
    WHERE registrationid='$user_check' AND year='$status' order by timestamp desc");
    $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim WHERE registrationid='$user_check' AND year='$status'");
    $totalclaimedamount = pg_query($con, "SELECT SUM(totalbillamount) FROM claim WHERE registrationid='$user_check' AND year='$status' AND claimstatus!='rejected'");
  }

  if ($role != 'Admin' && $status == 'ALL') {

    $result = pg_query($con, "SELECT *, REPLACE (uploadeddocuments, 'view', 'preview') docp FROM claim 
    left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members WHERE associatenumber='$user_check') faculty ON claim.registrationid=faculty.associatenumber
    left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student WHERE student_id='$user_check') student ON claim.registrationid=student.student_id
    WHERE registrationid='$user_check' order by timestamp desc");
    $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim WHERE registrationid='$user_check'");
    $totalclaimedamount = pg_query($con, "SELECT SUM(totalbillamount) FROM claim WHERE registrationid='$user_check'AND claimstatus!='rejected'");
  }

  if (!$result) {
    echo "An error occurred.\n";
    exit;
  }

  $resultArr = pg_fetch_all($result);

  echo 'Claim Number,Registered On,ID/F Name,Category,Claim head details,Claimed Amount (₹),Amount Transfered (₹),Status,Transfered Date,Bill,Remarks' . "\n";

  foreach ($resultArr as $array) {

    echo $array['reimbid'] . ',"' . substr($array['timestamp'], 0, 10) . '",' .  $array['registrationid'] . '/' . strtok($array['fullname'], ' ') . ',' . $array['selectclaimheadfromthelistbelow'] . ',"' . $array['claimheaddetails'] . '",' . $array['totalbillamount'] . ',' . $array['approvedamount'] . ',' . $array['claimstatus'] . ',' . $array['transfereddate'] . ',' . $array['uploadeddocuments'] . ',"' . $array['mediremarks'] . '"' . "\n";
  }
}

function student_export()
{


  global $con;
  @$module = $_POST['module'];
  @$id = $_POST['id'];
  @$category = $_POST['category'];
  @$classs = $_POST['classs'];
  @$class = $_POST['class'];
  @$stid = $_POST['stid'];


  if ($category == null && $class == null) {
    $result = pg_query($con, "SELECT * FROM rssimyprofile_student 
    left join (SELECT studentid, to_char(max(make_date(feeyear,month,1)), 'Mon-YY') as maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
    WHERE filterstatus='$id' AND module='$module' order by category asc, class asc, studentname asc");
  }

  if ($category != null && $class == null) {
    $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, to_char(max(make_date(feeyear,month,1)), 'Mon-YY') as maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
    WHERE filterstatus='$id' AND module='$module' AND category='$category' order by category asc, class asc, studentname asc");
  }

  if ($category == null && $class != null) {
    $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, to_char(max(make_date(feeyear,month,1)), 'Mon-YY') as maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
    WHERE filterstatus='$id' AND module='$module' AND class IN ('$classs') order by category asc, class asc, studentname asc");
  }

  if ($category != null && $class != null) {
    $result = pg_query($con, "SELECT * FROM rssimyprofile_student left join (SELECT studentid, to_char(max(make_date(feeyear,month,1)), 'Mon-YY') as maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
    WHERE filterstatus='$id' AND module='$module' AND class IN ('$classs') AND category='$category' order by category asc, class asc, studentname asc");
  }

  if ($stid != null) {
    $result = pg_query($con, "SELECT * FROM rssimyprofile_student 
    left join (SELECT studentid, to_char(max(make_date(feeyear,month,1)), 'Mon-YY') as maxmonth FROM fees group by studentid) fees ON fees.studentid=rssimyprofile_student.student_id
    WHERE student_id='$stid'");
  }

  if (!$result) {
    echo "An error occurred.\n";
    exit;
  }

  $resultArr = pg_fetch_all($result);

  echo 'Student Id,Name,Category,Class,DOA,Paid month,Special Service' . "\n";

  foreach ($resultArr as $array) {

    echo $array['student_id'] . ',' . $array['studentname'] . ',' . $array['category'] . ',' . $array['class'] . ',' . $array['doa'] . ',' . $array['maxmonth'] . ',' . $array['special_service'] . "\n";
  }
}
