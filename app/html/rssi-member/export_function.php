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
  $searchField = $_POST['searchField_export'];
  $fyear = $_POST['fyear_export'];

  function fetchData($con, $searchField, $fyear)
  {
    $query = "SELECT
      pd.*,
      ud.*,
      CASE 
          WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp)
          ELSE EXTRACT(YEAR FROM pd.timestamp) - 1
      END || '-' ||
      CASE 
          WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp) + 1
          ELSE EXTRACT(YEAR FROM pd.timestamp)
      END AS financial_year
      FROM donation_paymentdata AS pd
      LEFT JOIN donation_userdata AS ud ON pd.tel = ud.tel
      WHERE ((pd.tel = $1 AND pd.donationid IS NOT NULL) OR $1 IS NULL) AND
            (((CASE 
                WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp)
                ELSE EXTRACT(YEAR FROM pd.timestamp) - 1
            END || '-' ||
            CASE 
                WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp) + 1
                ELSE EXTRACT(YEAR FROM pd.timestamp)
            END) = $2 AND $2 IS NOT NULL) OR $2 IS NULL) ORDER BY pd.timestamp DESC";

    $params = array();
    if ($searchField !== '') {
      $params[] = $searchField;
    } else {
      $params[] = null; // Placeholder value for $1
    }

    if ($fyear !== '') {
      $params[] = $fyear;
    } else {
      $params[] = null; // Placeholder value for $2
    }

    $result = pg_query_params($con, $query, $params);

    $resultArr = pg_fetch_all($result);

    return $resultArr;
  }

  $resultArr = fetchData($con, $searchField, $fyear);


  echo 'Sl. No.,Pre Acknowledgement Number,ID Code,Unique Identification Number,Section Code,Unique Registration Number (URN),Date of Issuance of Unique Registration Number,Name of donor,Address of donor,Donation Type,Mode of receipt,Currency,Amount of donation,Invoice no,Invoice link' . "\n";
  $counter = 1; // Initialize the counter

  foreach ($resultArr as $array) {

    echo $counter . ',' . ',' . $array['documenttype'] . ',' . $array['nationalid'] . ',' . 'Section 80G' . ',' . 'AAKCR2540KF20214' . ',' . date("d/m/Y g:i a", strtotime($array['timestamp'])) . ',' . $array['fullname'] . ',"' . $array['postaladdress'] . '",' . 'Corpus' . ',' . 'Electronic modes including account payee cheque/draft' . ',' . $array['currency'] . ',' . $array['amount'] . ',' . $array['donationid'] . ',' . 'https://login.rssi.in/donation_invoice.php?searchField=' . $array['donationid'] . "\n";
    $counter++; // Increment the counter for each iteration
  }
}

function gps_export()
{
  global $con;
  @$taggedto = $_POST['taggedto'];
  @$item_type = $_POST['item_type'];
  @$assetid = $_POST['assetid'];
  @$asset_status = $_POST['asset_status'];

  $conditions = [];

  if ($taggedto != "") {
    $conditions[] = "taggedto = '$taggedto'";
  }

  if ($item_type != "ALL" && $item_type != "") {
    $conditions[] = "itemtype = '$item_type'";
  }

  if ($assetid != "") {
    $conditions[] = "assetid = '$assetid'";
  }

  if ($asset_status != "") {
    $conditions[] = "asset_status = '$asset_status'";
  }

  $query = "SELECT * FROM gps";

  if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
  }

  $query .= " ORDER BY itemname ASC";

  $gpsdetails = $query;

  $result = pg_query($con, $gpsdetails);

  if (!$result) {
    echo "An error occurred.\n";
    exit;
  }

  $resultArr = pg_fetch_all($result);

  echo 'Asset Id,Asset name,Asset type,Quantity,Tagged to,Status' . "\n";

  foreach ($resultArr as $array) {

    echo $array['itemid'] . ',"' . $array['itemname'] . '",' . $array['itemtype'] . ',' . $array['quantity'] . ',' . $array['taggedto'] . ',' . $array['asset_status'] . "\n";
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
    } else if ($id != null && ($status == 'ALL' || $status == null)) {
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


  $query = "SELECT * FROM rssimyprofile_student 
    LEFT JOIN (SELECT studentid, to_char(max(make_date(feeyear,month,1)), 'Mon-YY') as maxmonth FROM fees GROUP BY studentid) fees ON fees.studentid=rssimyprofile_student.student_id
    WHERE filterstatus='$id' AND module='$module'";

  if ($category != null) {
    $query .= " AND category='$category'";
  }

  if ($class != null) {
    $query .= " AND class IN ('$classs')";
  }

  if ($stid != null) {
    $query = "SELECT * FROM rssimyprofile_student 
        LEFT JOIN (SELECT studentid, to_char(max(make_date(feeyear,month,1)), 'Mon-YY') as maxmonth FROM fees GROUP BY studentid) fees ON fees.studentid=rssimyprofile_student.student_id
        WHERE student_id='$stid'";
  }

  $query .= " ORDER BY category ASC, class ASC, studentname ASC";

  $result = pg_query($con, $query);

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
