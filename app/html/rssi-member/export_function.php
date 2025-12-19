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
} else if ($export_type == "reimb_payment") {
  reimb_payment_export();
} else if ($export_type == "donation_old") {
  donation_old_export();
} else if ($export_type == "monthly_attd") {
  monthly_attd_export();
} else if ($export_type == "monthly_attd_associate") {
  monthly_attd_associate_export();
} else if ($export_type == "paydetails") {
  paydetails_export();
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

    echo substr($array['date'], 0, 10) . ',' . $array['studentid'] . ',' . strtok($array['studentname'], ' ') . ',' . $array['category'] . ',' . @strftime('%B', mktime(0, 0, 0, $array['month'])) . ',' . $array['fees'] . ',' . $array['fullname'] . ',' . $array['pstatus'] . "\n";
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
    $conditions[] = "(itemid = '$assetid' OR itemname ILIKE '%$assetid%')";
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

  echo 'Asset Id,Asset name,Asset type,Quantity,Tagged to,Status,Unit price,Purchase bill, Asset Recorded Date' . "\n";

  foreach ($resultArr as $array) {

    echo $array['itemid'] . ',"' . $array['itemname'] . '",' . $array['itemtype'] . ',' . $array['quantity'] . ',' . $array['taggedto'] . ',' . $array['asset_status'] . ',' . $array['unit_cost'] . ',' . $array['purchase_bill'] . ',' . $array['date'] . "\n";
  }
}

function reimb_export()
{
  global $con;
  @$status = $_POST['status'];
  @$id = $_POST['id'];
  @$reimbid = $_POST['reimbid'];

  // Initialize query conditions
  $queryConditions = [];

  // Split the comma-separated claim numbers into an array
  if ($reimbid !== null && $reimbid !== "") {
    $reimbidArray = explode(',', $reimbid);
    $reimbidConditions = array_map(function ($item) {
      return "'" . trim($item) . "'";
    }, $reimbidArray);
    $queryConditions[] = "claim.reimbid IN (" . implode(',', $reimbidConditions) . ")";
  }

  if ($id !== null && $id !== "") {
    $queryConditions[] = "claim.registrationid = '$id'";
  }

  if ($status !== null && $status !== "" && $status !== 'ALL') {
    $queryConditions[] = "claim.year = '$status'";
  }

  // Build the condition string
  $conditionString = implode(' AND ', $queryConditions);

  // Main query to fetch claim data
  $query = "SELECT claim.*, 
               REPLACE(claim.uploadeddocuments, 'view', 'preview') AS docp,
               faculty.fullname AS fullname, 
               faculty.phone AS phone,
               faculty.email AS email,
               student.studentname AS studentname, 
               student.contact AS contact,
               student.emailaddress AS emailaddress
        FROM claim
        LEFT JOIN rssimyaccount_members AS faculty ON claim.registrationid = faculty.associatenumber
        LEFT JOIN rssimyprofile_student AS student ON claim.registrationid = student.student_id";

  // Append the conditions and order by timestamp
  if (!empty($conditionString)) {
    $query .= " WHERE $conditionString";
  }

  $query .= " ORDER BY claim.timestamp DESC";
  $result = pg_query($con, $query);

  if (!$result) {
    echo "An error occurred.\n";
    exit;
  }

  $resultArr = pg_fetch_all($result);

  echo 'Claim Number,Registered On,ID/F Name,Category,Claim head details,Claimed Amount (₹),Amount Transfered (₹),Status,Transfered Date,Bill,Remarks' . "\n";

  foreach ($resultArr as $array) {

    echo $array['reimbid'] . ',"' . substr($array['timestamp'], 0, 10) . '",' . $array['registrationid'] . '/' . strtok($array['fullname'], ' ') . ',' . $array['selectclaimheadfromthelistbelow'] . ',"' . $array['claimheaddetails'] . '",' . $array['totalbillamount'] . ',' . $array['approvedamount'] . ',' . $array['claimstatus'] . ',' . $array['transfereddate'] . ',' . $array['uploadeddocuments'] . ',"' . $array['mediremarks'] . '"' . "\n";
  }
}

function reimb_payment_export()
{
  global $con;
  @$status = $_POST['status'];
  @$id = $_POST['id'];
  @$reimbid = $_POST['reimbid'];
  $currentDate = date('Y-m-d');

  // Initialize query conditions
  $queryConditions = [];

  // Split the comma-separated claim numbers into an array
  if (!empty($reimbid)) {
    $reimbidArray = array_map('trim', explode(',', $reimbid));
    $reimbidConditions = implode(',', array_map(function ($item) use ($con) {
      return "'" . pg_escape_string($con, $item) . "'";
    }, $reimbidArray));
    $queryConditions[] = "claim.reimbid IN ($reimbidConditions)";
  }

  if (!empty($id)) {
    $queryConditions[] = "claim.registrationid = '" . pg_escape_string($con, $id) . "'";
  }

  if (!empty($status) && $status !== 'ALL') {
    $queryConditions[] = "claim.year = '" . pg_escape_string($con, $status) . "'";
  }

  // Build the condition string
  $conditionString = !empty($queryConditions) ? implode(' AND ', $queryConditions) : '';

  // Main query to fetch claim data
  $query = "SELECT claim.*, 
                REPLACE(claim.uploadeddocuments, 'view', 'preview') AS docp,
                faculty.fullname AS fullname, 
                faculty.phone AS phone,
                faculty.email AS email,
                student.studentname AS studentname, 
                student.contact AS contact,
                student.emailaddress AS emailaddress,
                COALESCE(bd.bank_account_number, savings_bd.bank_account_number) AS bank_account_number,
                COALESCE(bd.ifsc_code, savings_bd.ifsc_code) AS ifsc_code
            FROM claim
            LEFT JOIN rssimyaccount_members AS faculty ON claim.registrationid = faculty.associatenumber
            LEFT JOIN rssimyprofile_student AS student ON claim.registrationid = student.student_id
            LEFT JOIN (
    SELECT 
        b1.updated_for, 
        b1.bank_account_number, 
        b1.ifsc_code 
    FROM bankdetails b1
    INNER JOIN (
        SELECT updated_for, MAX(updated_on) AS max_updated_on 
        FROM bankdetails 
        WHERE account_nature = 'reimbursement'
        GROUP BY updated_for
    ) b2 ON b1.updated_for = b2.updated_for AND b1.updated_on = b2.max_updated_on
) AS bd ON bd.updated_for = claim.registrationid

LEFT JOIN (
    SELECT 
        b1.updated_for, 
        b1.bank_account_number, 
        b1.ifsc_code 
    FROM bankdetails b1
    INNER JOIN (
        SELECT updated_for, MAX(updated_on) AS max_updated_on 
        FROM bankdetails 
        WHERE account_nature = 'savings'
        GROUP BY updated_for
    ) b2 ON b1.updated_for = b2.updated_for AND b1.updated_on = b2.max_updated_on
) AS savings_bd ON savings_bd.updated_for = claim.registrationid 
AND bd.bank_account_number IS NULL";

  // Append the conditions and order by timestamp
  if (!empty($conditionString)) {
    $query .= " WHERE $conditionString";
  }

  $query .= " ORDER BY claim.timestamp DESC";

  $result = pg_query($con, $query);

  if (!$result) {
    echo "An error occurred.\n";
    exit;
  }

  $resultArr = pg_fetch_all($result);

  // Output CSV headers
  echo 'PYMT_PROD_TYPE_CODE, PYMT_MODE, DEBIT_ACC_NO, BNF_NAME, BENE_ACC_NO, BENE_IFSC, AMOUNT, DEBIT_NARR, CREDIT_NARR, MOBILE_NUM, EMAIL_ID, REMARK, PYMT_DATE, REF_NO, ADDL_INFO1, ADDL_INFO2, ADDL_INFO3, ADDL_INFO4, ADDL_INFO5' . "\n";

  foreach ($resultArr as $array) {
    echo 'PAB_VENDOR,NEFT,\'004201033772,' . $array['fullname'] . ',\'' . $array['bank_account_number'] . ',' . $array['ifsc_code'] . ',' . $array['approvedamount'] . ',,,' . $array['phone'] . ',' . $array['email'] . ',' . 'RSSI Reimbursement' . ',' . $currentDate . ',' . $array['reimbid'] . "\n";
  }
}

function student_export()
{
  global $con;

  // Get search parameters from POST
  $module = $_POST['get_module'] ?? null;
  $id = $_POST['get_id'] ?? null;
  $category = $_POST['get_category'] ?? null;
  $class = isset($_POST['get_class']) ? explode(',', $_POST['get_class']) : null;
  $stid = $_POST['get_stid'] ?? null;
  $searchByIdOnly = $_POST['search_by_id_only'] ?? '0';

  // Build the query based on search parameters
  if ($searchByIdOnly == '1') {
    // Search by Student ID only
    if (!empty($stid)) {
      $result = pg_query_params(
        $con,
        "SELECT * FROM rssimyprofile_student WHERE student_id = $1",
        [$stid]
      );
    }
  } else {
    // Normal search (requires module and status)
    if (!empty($module) && !empty($id)) {
      $query = "SELECT * FROM rssimyprofile_student 
                     WHERE filterstatus = $1 AND module = $2";
      $params = [$id, $module];

      $paramCount = 3;

      if (!empty($category)) {
        $query .= " AND category = $$paramCount";
        $params[] = $category;
        $paramCount++;
      }

      if (!empty($class)) {
        $class = array_filter($class); // Remove empty values
        if (!empty($class)) {
          $placeholders = [];
          foreach ($class as $classItem) {
            $placeholders[] = "$$paramCount";
            $params[] = trim($classItem);
            $paramCount++;
          }
          $query .= " AND class IN (" . implode(',', $placeholders) . ")";
        }
      }

      $query .= " ORDER BY category ASC, class ASC, studentname ASC";
      $result = pg_query_params($con, $query, $params);
    }
  }

  if (!$result) {
    echo "An error occurred.\n";
    exit;
  }

  $resultArr = pg_fetch_all($result);

  if (empty($resultArr)) {
    echo "No records found.\n";
    exit;
  }

  // Set headers for CSV download
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=student_export_' . date('Y-m-d') . '.csv');

  // Create output stream
  $output = fopen('php://output', 'w');

  // Write CSV header
  fputcsv($output, [
    'Student Id',
    'Name',
    'Category',
    'Class',
    'Age',
    'Gender',
    'Contact',
    'Access',
    'Pay type',
    'Status',
    'DOA',
    'Caste',
    'Caste Doc',
    'Student Aadhaar',
    'Student Aadhaar Doc',
    'Parent Aadhaar',
    'DOT',
    'Remarks'
  ]);

  function maskAadhar($aadhar)
  {
    // Convert null to empty string
    $aadhar = (string) $aadhar;

    // If no digit exists in the string, return blank
    if (!preg_match('/\d/', $aadhar)) {
      return "";
    }

    // If it has digits, mask all except last 4
    if (strlen($aadhar) >= 4) {
      return str_repeat("X", strlen($aadhar) - 4) . substr($aadhar, -4);
    }

    // If it's shorter than 4 digits, just return blank
    return "";
  }

  // Write data rows
  foreach ($resultArr as $array) {
    fputcsv($output, [
      $array['student_id'],
      $array['studentname'],
      $array['category'],
      $array['class'],
      (new DateTime($array['dateofbirth']))->diff(new DateTime())->y,
      $array['gender'],
      $array['contact'],
      $array['access_category'],
      $array['payment_type'],
      $array['filterstatus'],
      $array['doa'],
      $array['caste'],
      !empty($array['caste_document']) ? "Yes" : "No",
      maskAadhar($array['studentaadhar']),
      !empty($array['upload_aadhar_card']) ? "Yes" : "No",
      maskAadhar($array['guardianaadhar']),
      $array['effectivefrom'],
      $array['remarks']
    ]);
  }

  fclose($output);
  exit;
}
function donation_old_export()
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
  echo 'Sl. No.,Pre Acknowledgement Number,ID Code,Unique Identification Number,Section Code,Unique Registration Number (URN),Date of Issuance of Unique Registration Number,Name of donor,Address of donor,Donation Type,Mode of receipt,Currency,Amount of donation,Invoice no,Invoice link' . "\n";
  $counter = 1; // Initialize the counter

  foreach ($resultArr as $array) {

    echo $counter . ',' . ',' . $array['uitype'] . ',' . $array['uinumber'] . ',' . 'Section 80G' . ',' . 'AAKCR2540KF20214' . ',' . date("d/m/Y g:i a", strtotime($array['timestamp'])) . ',' . $array['firstname'] . ' ' . $array['lastname'] . ',"' . $array['address'] . '",' . $array['donation_type'] . ',' . $array['modeofpayment'] . ',' . $array['currencyofthedonatedamount'] . ',' . $array['donatedamount'] . ',' . $array['invoice'] . ',' . $array['profile'] . "\n";
    $counter++; // Increment the counter for each iteration
  }
}
function exportAttendanceToCSV($attendanceData, $startDate, $endDate)
{
  // Set headers for CSV export
  header('Content-Type: text/csv');
  $today = date("YmdHis");
  $startMonthYear = date('M-Y', strtotime($startDate));
  header("Content-Disposition: attachment; filename={$startMonthYear}_attendance_report_$today.csv");
  // Create output stream
  $output = fopen('php://output', 'w');

  // Create CSV header row
  $csvHeaders = [
    'Sl. No.',
    'Student ID',
    'Student Name',
    'Category',
    'Class',
    'Contact',
  ];

  // Add date headers to CSV
  $currentDate = $startDate;
  while ($currentDate <= $endDate) {
    $formattedDate = date("j", strtotime($currentDate));
    $csvHeaders[] = $formattedDate;
    $currentDate = date("Y-m-d", strtotime($currentDate . ' + 1 day'));
  }

  $csvHeaders[] = 'Status';
  $csvHeaders[] = 'Present';
  $csvHeaders[] = 'Total Class';
  $csvHeaders[] = 'Percentage';

  fputcsv($output, $csvHeaders);

  // Create a data array to hold student-level data
  $studentData = [];

  foreach ($attendanceData as $array) {
    $studentID = $array['student_id'];

    if (!isset($studentData[$studentID])) {
      // Initialize student data
      $studentData[$studentID] = [
        'Sl. No.' => null,
        'Student ID' => $array['student_id'],
        'Student Name' => $array['studentname'],
        'Category' => $array['category'],
        'Class' => $array['class'],
        'Contact' => $array['contact'],
      ];

      // Initialize date columns
      $currentDate = $startDate;
      while ($currentDate <= $endDate) {
        $columnAlias = "day_" . date("j", strtotime($currentDate));
        $studentData[$studentID][$currentDate] = $array[$columnAlias];
        $currentDate = date("Y-m-d", strtotime($currentDate . ' + 1 day'));
      }

      // Initialize other columns
      $studentData[$studentID]['Status'] = $array['filterstatus'];
      $studentData[$studentID]['Present'] = $array['attended_classes'];
      $studentData[$studentID]['Total Class'] = $array['total_classes'];
      $studentData[$studentID]['Percentage'] = $array['attendance_percentage'];
    } else {
      // Concatenate 'A' and 'P' statuses for each date
      $currentDate = $startDate;
      while ($currentDate <= $endDate) {
        $columnAlias = "day_" . date("j", strtotime($currentDate));
        $existingStatus = $studentData[$studentID][$currentDate];
        $newStatus = $array[$columnAlias];
        if ($newStatus !== null) {
          if ($existingStatus === null) {
            $studentData[$studentID][$currentDate] = $newStatus;
          } else {
            $studentData[$studentID][$currentDate] .= ', ' . $newStatus;
          }
        }
        $currentDate = date("Y-m-d", strtotime($currentDate . ' + 1 day'));
      }
    }
  }

  // Export student-level data to CSV
  $counter = 1;
  foreach ($studentData as $studentRow) {
    $studentRow['Sl. No.'] = $counter;
    fputcsv($output, $studentRow);
    $counter++;
  }

  // Close the output stream
  fclose($output);
  exit();
}

// ... Rest of your code .

function monthly_attd_export()
{
  global $con;
  @$id = $_POST['id'];
  @$month = $_POST['month'];
  @$selectedCategories = isset($_POST['categories']) ? $_POST['categories'] : [];

  // Calculate the start and end dates of the month
  $startDate = date("Y-m-01", strtotime($month));
  $endDate = date("Y-m-t", strtotime($month));

  // Get all available categories for validation
  $categoriesQuery = "SELECT DISTINCT category FROM rssimyprofile_student WHERE category IS NOT NULL ORDER BY category";
  $categoriesResult = pg_query($con, $categoriesQuery);
  $allCategories = pg_fetch_all_columns($categoriesResult, 0);

  // Validate selected categories against available categories
  $validCategories = [];
  foreach ($selectedCategories as $cat) {
    if (in_array($cat, $allCategories)) {
      $validCategories[] = pg_escape_string($con, $cat);
    }
  }

  // Build SQL conditions
  $idCondition = "";
  if ($id != null) {
    $idCondition = "AND s.filterstatus = '$id'";
  }

  $categoryCondition = "";
  if (!empty($validCategories)) {
    $categoryList = "'" . implode("','", $validCategories) . "'";
    $categoryCondition = "AND s.category IN ($categoryList)";
  } else {
    // If no categories selected, return empty result
    $resultArr = [];
    exportAttendanceToCSV($resultArr, $startDate, $endDate);
    return;
  }

  // Construct the SQL query with consistent attendance logic
  $query = "WITH date_range AS (
            SELECT generate_series(
                '$startDate'::date, '$endDate'::date, '1 day'::interval
            )::date AS attendance_date
        ),
        holidays AS (
            SELECT holiday_date FROM holidays 
            WHERE holiday_date BETWEEN '$startDate'::date AND '$endDate'::date
        ),
        student_exceptions AS (
            SELECT 
                m.student_id,
                e.exception_date AS attendance_date
            FROM 
                student_class_days_exceptions e
            JOIN 
                student_exception_mapping m ON e.exception_id = m.exception_id
            WHERE 
                e.exception_date BETWEEN '$startDate'::date AND '$endDate'::date
        ),
        attendance_data AS (
            SELECT
                s.student_id,
                s.filterstatus,
                s.studentname,
                s.category,
                s.class,
                s.effectivefrom,
                s.doa,
                s.contact,
                d.attendance_date,
                COALESCE(
                    CASE
                        WHEN a.user_id IS NOT NULL THEN 'P' -- Present if attendance record exists
                        WHEN h.holiday_date IS NOT NULL THEN NULL -- NULL for holidays (not counted)
                        WHEN ex.attendance_date IS NOT NULL THEN NULL -- NULL for exceptions (not counted)
                        WHEN a.user_id IS NULL
                             AND EXISTS (SELECT 1 FROM attendance att WHERE att.date = d.attendance_date)
                             AND EXISTS (
                                SELECT 1 FROM student_class_days cw
                                WHERE cw.category = s.category
                                  AND cw.effective_from <= d.attendance_date
                                  AND (cw.effective_to IS NULL OR cw.effective_to >= d.attendance_date)
                                  AND POSITION(TO_CHAR(d.attendance_date, 'Dy') IN cw.class_days) > 0
                             )
                             AND s.doa <= d.attendance_date
                             THEN 'A' -- Absent only if it's a class day and not holiday/exception
                        ELSE NULL -- NULL for non-class days
                    END
                ) AS attendance_status
            FROM
                date_range d
            CROSS JOIN
                rssimyprofile_student s
            LEFT JOIN
                attendance a ON s.student_id = a.user_id AND a.date = d.attendance_date
            LEFT JOIN
                holidays h ON d.attendance_date = h.holiday_date
            LEFT JOIN
                student_exceptions ex ON d.attendance_date = ex.attendance_date AND s.student_id = ex.student_id
            WHERE
                (
                    s.effectivefrom IS NULL OR 
                    DATE_TRUNC('month', s.effectivefrom)::DATE = DATE_TRUNC('month', TO_DATE('$month', 'YYYY-MM'))::DATE
                )
                AND DATE_TRUNC('month', s.doa)::DATE <= DATE_TRUNC('month', TO_DATE('$month', 'YYYY-MM'))::DATE
                $idCondition
                $categoryCondition
        )
        SELECT
            student_id,
            filterstatus,
            studentname,
            category,
            class,
            contact,
            attendance_date,
            attendance_status,
            " . generate_date_columns($startDate, $endDate) . ",
            COUNT(*) FILTER (WHERE attendance_status IS NOT NULL) OVER (PARTITION BY student_id) AS total_classes,
            COUNT(*) FILTER (WHERE attendance_status = 'P') OVER (PARTITION BY student_id) AS attended_classes,
            CASE
                WHEN COUNT(*) FILTER (WHERE attendance_status IS NOT NULL) OVER (PARTITION BY student_id) = 0 THEN NULL
                ELSE CONCAT(
                    ROUND(
                        (COUNT(*) FILTER (WHERE attendance_status = 'P') OVER (PARTITION BY student_id) * 100.0) /
                        COUNT(*) FILTER (WHERE attendance_status IS NOT NULL) OVER (PARTITION BY student_id), 2
                    ),
                    '%'
                )
            END AS attendance_percentage
        FROM attendance_data
        GROUP BY
            student_id,
            filterstatus,
            studentname,
            category,
            class,
            contact,
            attendance_date,
            attendance_status
        ORDER BY
            CASE WHEN class = 'Pre-school' THEN 0 ELSE 1 END,
            category,
            class,
            student_id,
            attendance_date;
";

  $result = pg_query($con, $query);

  if (!$result) {
    echo "An error occurred.\n";
    exit;
  }

  $resultArr = pg_fetch_all($result);

  // Call the export function to generate and download the CSV
  exportAttendanceToCSV($resultArr, $startDate, $endDate);
}
// Function to generate date columns
function generate_date_columns($startDate, $endDate)
{
  $dates = [];
  $currentDate = $startDate;
  while ($currentDate <= $endDate) {
    $columnAlias = "day_" . date("j", strtotime($currentDate));
    $dates[] = "MAX(CASE WHEN attendance_date = '$currentDate' THEN attendance_status END) AS \"$columnAlias\"";
    $currentDate = date("Y-m-d", strtotime($currentDate . ' + 1 day'));
  }
  return implode(', ', $dates);
}

function generate_date_headers($startDate, $endDate)
{
  $dates = [];
  $currentDate = $startDate;
  while ($currentDate <= $endDate) {
    $dates[] = date("j", strtotime($currentDate));
    $currentDate = date("Y-m-d", strtotime($currentDate . ' + 1 day'));
  }
  return implode(',', $dates);
}

function generate_date_values($array, $startDate, $endDate)
{
  $values = [];
  $currentDate = $startDate;
  while ($currentDate <= $endDate) {
    $columnAlias = "day_" . date("j", strtotime($currentDate));
    $values[] = $array[$columnAlias];
    $currentDate = date("Y-m-d", strtotime($currentDate . ' + 1 day'));
  }
  return implode(',', $values);
}


//Associate monthly attendance export START
function exportAttendanceToCSVAssociate($attendanceData, $startDate, $endDate)
{
  // Set headers for CSV export
  header('Content-Type: text/csv');
  $today = date("YmdHis");
  $startMonthYear = date('M-Y', strtotime($startDate));
  header("Content-Disposition: attachment; filename={$startMonthYear}_attendance_report_$today.csv");

  // Create output stream
  $output = fopen('php://output', 'w');

  // Create CSV header row
  $csvHeaders = [
    'Sl. No.',
    'Associate number',
    'Name',
    'Category',
  ];

  // Add date headers to CSV
  $currentDate = $startDate;
  while ($currentDate <= $endDate) {
    $formattedDate = date("j", strtotime($currentDate));
    $csvHeaders[] = "{$formattedDate}(In)";
    $csvHeaders[] = "{$formattedDate}(Out)";
    $currentDate = date("Y-m-d", strtotime($currentDate . ' + 1 day'));
  }

  $csvHeaders[] = 'Status';
  $csvHeaders[] = 'Present';

  fputcsv($output, $csvHeaders);

  // Create a data array to hold associate-level data
  $associateData = [];

  foreach ($attendanceData as $array) {
    $associateNumber = $array['associatenumber'];

    if (!isset($associateData[$associateNumber])) {
      // Initialize associate data
      $associateData[$associateNumber] = [
        'Sl. No.' => null,
        'Associate number' => $array['associatenumber'],
        'Name' => $array['fullname'],
        'Category' => $array['engagement'],
      ];

      // Initialize date columns
      $currentDate = $startDate;
      while ($currentDate <= $endDate) {
        $inColumnAlias = "day_" . date("j", strtotime($currentDate)) . "_in";
        $outColumnAlias = "day_" . date("j", strtotime($currentDate)) . "_out";
        $associateData[$associateNumber][$inColumnAlias] = '';
        $associateData[$associateNumber][$outColumnAlias] = '';
        $currentDate = date("Y-m-d", strtotime($currentDate . ' + 1 day'));
      }

      // Initialize other columns
      $associateData[$associateNumber]['Status'] = $array['filterstatus'];
      $associateData[$associateNumber]['Present'] = $array['attended_classes'];
    }

    // Assign punch in and out times to the respective date columns
    $attendance_date = substr($array['attendance_date'] ?? '', 0, 10);
    $punch_in = $array['punch_in'] ? date('h:i:s A', strtotime($array['punch_in'])) : '';
    $punch_out = $array['punch_out'] ? date('h:i:s A', strtotime($array['punch_out'])) : '';
    $late_status = $array['late_status'] ?? '';
    $exception_status = $array['exception_status'] ?? '';
    $exit_status = $array['exit_status'] ?? '';

    if ($attendance_date) {
      // if ($punch_in) {
      $punchInWithStatus = $punch_in;

      // Use exception status if available; otherwise, use late status
      if ($exception_status) {
        $punchInWithStatus .= " ($exception_status)";
      } elseif ($late_status) {
        $punchInWithStatus .= " ($late_status)";
      }

      // Store the result
      $associateData[$associateNumber]["day_" . date("j", strtotime($attendance_date)) . "_in"] = $punchInWithStatus;
      // }
      if ($punch_out) {
        $punchOutWithStatus = $punch_out;
        if ($exit_status) {
          $punchOutWithStatus .= " ($exit_status)";
        }
        $associateData[$associateNumber]["day_" . date("j", strtotime($attendance_date)) . "_out"] = $punchOutWithStatus;
      }
    }
  }

  // Export student-level data to CSV
  $counter = 1;
  foreach ($associateData as $studentRow) {
    $studentRow['Sl. No.'] = $counter;
    fputcsv($output, $studentRow);
    $counter++;
  }

  // Close the output stream
  fclose($output);
  exit();
}

function monthly_attd_associate_export()
{
  global $con;
  @$month = $_POST['month'];
  @$associatenumber = $_POST['associateNumber'];
  @$role = $_POST['role'];

  // Calculate the start and end dates of the month
  $startDate = date("Y-m-01", strtotime($month));
  $endDate = date("Y-m-t", strtotime($month));

  // Construct the ID condition
  $idCondition = isset($_POST['id']) ? "AND m.filterstatus = '" . pg_escape_string($con, $_POST['id']) . "'" : '';

  // Construct the teacher condition
  $teacherCondition = '';
  if (isset($_POST['selectedTeachers']) && !empty($_POST['selectedTeachers'])) {
    $escapedTeachers = array_map(function ($teacher) use ($con) {
      return pg_escape_string($con, $teacher);
    }, explode(',', $_POST['selectedTeachers']));
    $teacherList = implode("','", $escapedTeachers);
    $teacherCondition = "AND m.associatenumber IN ('$teacherList')";
  }

  // Construct the SQL query
  $query = "
WITH date_range AS (
    SELECT generate_series(
        '$startDate'::date,
        '$endDate'::date,
        '1 day'::interval
    ) AS attendance_date
),
DynamicSchedule AS (
    SELECT
        s.associate_number,
        s.start_date,
        s.reporting_time,
        s.exit_time,
        m.filterstatus,
        m.effectivedate,
        COALESCE(
    LEAD(s.start_date) OVER (PARTITION BY s.associate_number ORDER BY s.start_date) - INTERVAL '1 day',
    CASE
        WHEN m.effectivedate IS NOT NULL THEN m.effectivedate
        ELSE CURRENT_DATE
    END
) AS end_date
    FROM associate_schedule s
    INNER JOIN rssimyaccount_members m
        ON s.associate_number = m.associatenumber
    ORDER BY s.associate_number, s.start_date, s.timestamp DESC
),
PunchInOut AS (
    SELECT
        a.user_id,
        a.status,
        DATE_TRUNC('day', a.punch_in) AS punch_date,
        MIN(a.punch_in) AS punch_in,
        CASE
            WHEN COUNT(*) = 1 THEN NULL
            ELSE MAX(a.punch_in)
        END AS punch_out
    FROM attendance a
    GROUP BY a.user_id, a.status, DATE_TRUNC('day', a.punch_in)
),
attendance_data AS (
    SELECT
        m.associatenumber,
        m.filterstatus,
        m.fullname,
        m.engagement,
        COALESCE(substring(m.class FROM '^[^-]+'), NULL) AS mode,
        m.effectivedate,
        m.doj,
        d.attendance_date,
        
        -- Override punch_in if missed-entry exception exists and is approved
        COALESCE(
            (
                SELECT e.start_date_time
                FROM exception_requests e
                WHERE e.submitted_by = m.associatenumber
                AND e.status = 'Approved'
                AND e.exception_type = 'entry'
                AND e.sub_exception_type = 'missed-entry'
                AND d.attendance_date = DATE(e.start_date_time)
                LIMIT 1
            ),
            p.punch_in -- fallback to original punch_in if no exception
        ) AS punch_in,

        -- Handle punch_out logic similarly (using exception if available)
        COALESCE(
            (
                SELECT e.end_date_time
                FROM exception_requests e
                WHERE e.submitted_by = m.associatenumber
                AND e.status = 'Approved'
                AND e.exception_type = 'exit'
                AND d.attendance_date = DATE(e.end_date_time)
                LIMIT 1
            ),
            p.punch_out
        ) AS punch_out,

        -- Attendance status logic
        CASE
            WHEN p.punch_in IS NOT NULL THEN 'P'
            WHEN p.punch_in IS NULL AND d.attendance_date NOT IN (SELECT date FROM attendance) THEN NULL
            WHEN m.doj > d.attendance_date THEN NULL
            ELSE 'A'
        END AS attendance_status,

        ds.reporting_time,
        ds.exit_time,

        -- Updated Late status logic based on the overridden punch_in
        CASE
        -- Leave condition
            WHEN EXISTS (
                SELECT 1
                FROM leavedb_leavedb l
                WHERE l.applicantid = m.associatenumber
                AND l.status = 'Approved'
                AND l.halfday = 0
                AND d.attendance_date BETWEEN l.fromdate AND l.todate
            ) THEN 'Leave'
            
            -- Half-day condition
            WHEN EXISTS (
            SELECT 1
            FROM leavedb_leavedb l
            WHERE l.applicantid = m.associatenumber
            AND l.status = 'Approved'
            AND l.halfday = 1
            AND d.attendance_date BETWEEN l.fromdate AND l.todate
            GROUP BY l.applicantid, d.attendance_date
            HAVING COUNT(*) >= 2
            ) THEN 'Leave'
            
            -- Half-day condition
            WHEN EXISTS (
                SELECT 1
                FROM leavedb_leavedb l
                WHERE l.applicantid = m.associatenumber
                AND l.status = 'Approved'
                AND l.halfday = 1
                AND d.attendance_date BETWEEN l.fromdate AND l.todate
            ) THEN 'HF'
             -- Late status logic for entry exception with late-entry subcategory
            WHEN EXISTS (
                SELECT 1
                FROM exception_requests e
                WHERE e.submitted_by = m.associatenumber
                AND e.status = 'Approved'
                AND e.exception_type = 'entry'
                AND e.sub_exception_type = 'late-entry'
                AND d.attendance_date = DATE(e.start_date_time)
            ) THEN
                CASE
                    -- If punch_in is within the approved exception time
                    WHEN p.punch_in IS NOT NULL AND EXTRACT(EPOCH FROM p.punch_in::time) <= EXTRACT(EPOCH FROM (
                        SELECT e.start_date_time 
                        FROM exception_requests e 
                        WHERE e.submitted_by = m.associatenumber
                        AND e.status = 'Approved'
                        AND e.exception_type = 'entry'
                        AND e.sub_exception_type = 'late-entry'
                        AND d.attendance_date = DATE(e.start_date_time)
                    )::time) THEN 'Exc.'
                    -- If punch_in is after the approved exception time
                    WHEN p.punch_in IS NOT NULL THEN 'Exc.L'
                    ELSE NULL
                END
            -- If missed-entry exception is applied, recalculate the status
            WHEN EXISTS (
                SELECT 1
                FROM exception_requests e
                WHERE e.submitted_by = m.associatenumber
                AND e.status = 'Approved'
                AND e.exception_type = 'entry'
                AND e.sub_exception_type = 'missed-entry'
                AND d.attendance_date = DATE(e.start_date_time)
            ) THEN
                CASE
                    -- If the overridden punch_in is late (after reporting time + 10 mins), it should be 'L'
                    WHEN EXTRACT(EPOCH FROM COALESCE(
                        (
                            SELECT e.start_date_time
                            FROM exception_requests e
                            WHERE e.submitted_by = m.associatenumber
                            AND e.status = 'Approved'
                            AND e.exception_type = 'entry'
                            AND e.sub_exception_type = 'missed-entry'
                            AND d.attendance_date = DATE(e.start_date_time)
                            LIMIT 1
                        ), p.punch_in)::time) > EXTRACT(EPOCH FROM ds.reporting_time) + 600 THEN 'L'
                    -- If the overridden punch_in is within 10 mins of reporting time, it should be 'W'
                    WHEN EXTRACT(EPOCH FROM COALESCE(
                        (
                            SELECT e.start_date_time
                            FROM exception_requests e
                            WHERE e.submitted_by = m.associatenumber
                            AND e.status = 'Approved'
                            AND e.exception_type = 'entry'
                            AND e.sub_exception_type = 'missed-entry'
                            AND d.attendance_date = DATE(e.start_date_time)
                            LIMIT 1
                        ), p.punch_in)::time) > EXTRACT(EPOCH FROM ds.reporting_time)
                        AND EXTRACT(EPOCH FROM COALESCE(
                            (
                                SELECT e.start_date_time
                                FROM exception_requests e
                                WHERE e.submitted_by = m.associatenumber
                                AND e.status = 'Approved'
                                AND e.exception_type = 'entry'
                                AND e.sub_exception_type = 'missed-entry'
                                AND d.attendance_date = DATE(e.start_date_time)
                                LIMIT 1
                            ), p.punch_in)::time) <= EXTRACT(EPOCH FROM ds.reporting_time) + 600 THEN 'W'
                    -- If it's on time (or earlier), status should be NULL (not late)
                    ELSE NULL
                END
            -- For regular punch-ins, apply standard lateness logic
            WHEN p.punch_in IS NOT NULL THEN
                CASE
                    WHEN ds.reporting_time IS NULL THEN 'NA'
                    WHEN EXTRACT(EPOCH FROM p.punch_in::time) > EXTRACT(EPOCH FROM ds.reporting_time + INTERVAL '1 minute')
                        AND EXTRACT(EPOCH FROM p.punch_in::time) <= EXTRACT(EPOCH FROM ds.reporting_time + INTERVAL '1 minute') + 600 THEN 'W'
                    WHEN EXTRACT(EPOCH FROM p.punch_in::time) > EXTRACT(EPOCH FROM ds.reporting_time) + 600 THEN 'L'
                    ELSE NULL
                END
            ELSE NULL
        END AS late_status,

        -- Exit status logic with Early Exit (EE) condition
CASE
    WHEN EXISTS (
        SELECT 1
        FROM exception_requests e
        WHERE e.submitted_by = m.associatenumber
        AND e.status = 'Approved'
        AND e.exception_type = 'exit'
        AND d.attendance_date = DATE(e.end_date_time)
    ) THEN 'Exc.'
    -- Early Exit condition: no exception found AND punch_out exists AND punch_out is before exit_time
    -- EXCEPT when it's a half-day leave (HF status) - don't show EE for approved half-day leaves
    WHEN NOT EXISTS (
        SELECT 1
        FROM exception_requests e
        WHERE e.submitted_by = m.associatenumber
        AND e.status = 'Approved'
        AND e.exception_type = 'exit'
        AND d.attendance_date = DATE(e.end_date_time)
    ) AND p.punch_out IS NOT NULL 
       AND ds.exit_time IS NOT NULL
       AND EXTRACT(EPOCH FROM p.punch_out::time) < EXTRACT(EPOCH FROM ds.exit_time)
       -- Exclude half-day leave cases
       AND NOT EXISTS (
           SELECT 1
           FROM leavedb_leavedb l
           WHERE l.applicantid = m.associatenumber
           AND l.status = 'Approved'
           AND l.halfday = 1
           AND l.shift IN ('AFN', 'MSH', 'ASH')
           AND d.attendance_date BETWEEN l.fromdate AND l.todate
       ) THEN 'EE'
    ELSE NULL
END AS exit_status,

        -- Status 'Exc.' for overridden punch-in time from exception
        CASE
    -- Show 'Exc.' if approved exception exists
    WHEN EXISTS (
        SELECT 1
        FROM exception_requests e
        WHERE e.submitted_by = m.associatenumber
        AND e.status = 'Approved'
        AND e.exception_type = 'entry'
        AND e.sub_exception_type = 'missed-entry'
        AND d.attendance_date = DATE(e.start_date_time)
    ) THEN 
        -- Check if ds.reporting_time is NULL, then add 'NA'
        CASE 
            WHEN ds.reporting_time IS NULL THEN 'Exc.NA'
            ELSE 'Exc.'
        END
    ELSE NULL
END AS exception_status
    FROM
        date_range d
    CROSS JOIN
        rssimyaccount_members m
    LEFT JOIN
        PunchInOut p
        ON m.associatenumber = p.user_id AND p.punch_date = DATE_TRUNC('day', d.attendance_date)
    LEFT JOIN
        DynamicSchedule ds
        ON m.associatenumber = ds.associate_number
        AND d.attendance_date BETWEEN ds.start_date AND ds.end_date
    WHERE
        (
            (m.filterstatus = 'Active') OR
            (m.filterstatus = 'Inactive' AND DATE_TRUNC('month', m.effectivedate)::DATE >= DATE_TRUNC('month', TO_DATE('$month', 'YYYY-MM'))::DATE)
        )
        AND DATE_TRUNC('month', m.doj)::DATE <= DATE_TRUNC('month', TO_DATE('$month', 'YYYY-MM'))::DATE
        $idCondition
        $teacherCondition
        " . ($role !== 'Admin' ? "AND m.associatenumber = '$associatenumber'" : "") . "
)
SELECT
    associatenumber,
    filterstatus,
    fullname,
    engagement,
    mode,
    attendance_date,
    attendance_status,
    punch_in,
    punch_out,
    reporting_time,
    late_status,
    exit_status,
    exception_status,  -- Include the exception status in the final result
    COUNT(*) FILTER (WHERE attendance_status = 'P') OVER (PARTITION BY associatenumber) AS attended_classes
FROM attendance_data
-- WHERE mode = 'Offline'
WHERE engagement IN ('Employee', 'Intern')
GROUP BY
    associatenumber,
    filterstatus,
    fullname,
    engagement,
    mode,
    attendance_date,
    attendance_status,
    punch_in,
    punch_out,
    reporting_time,
    late_status,
    exit_status,
    exception_status
ORDER BY
    associatenumber,
    attendance_date;
";
  $result = pg_query($con, $query);

  if (!$result) {
    echo "An error occurred.\n";
    exit;
  }

  $resultArr = pg_fetch_all($result);

  // Call the export function to generate and download the CSV
  exportAttendanceToCSVAssociate($resultArr, $startDate, $endDate);
}

// Function to generate date columns
function generate_date_columns_associate($startDate, $endDate)
{
  $dates = [];
  $currentDate = $startDate;
  while ($currentDate <= $endDate) {
    $columnAliasIn = "day_" . date("j", strtotime($currentDate)) . "_in";
    $columnAliasOut = "day_" . date("j", strtotime($currentDate)) . "_out";
    $dates[] = "MAX(CASE WHEN attendance_date = '$currentDate' THEN punch_in END) AS \"$columnAliasIn\"";
    $dates[] = "MAX(CASE WHEN attendance_date = '$currentDate' THEN punch_out END) AS \"$columnAliasOut\"";
    $currentDate = date("Y-m-d", strtotime($currentDate . ' + 1 day'));
  }
  return implode(', ', $dates);
}

function generate_date_headers_associate($startDate, $endDate)
{
  $dates = [];
  $currentDate = $startDate;
  while ($currentDate <= $endDate) {
    $dates[] = date("j", strtotime($currentDate)) . '(In)';
    $dates[] = date("j", strtotime($currentDate)) . '(Out)';
    $currentDate = date("Y-m-d", strtotime($currentDate . ' + 1 day'));
  }
  return implode(',', $dates);
}

function generate_date_values_associate($array, $startDate, $endDate)
{
  $values = [];
  $currentDate = $startDate;
  while ($currentDate <= $endDate) {
    $inColumnAlias = "day_" . date("j", strtotime($currentDate)) . "_in";
    $outColumnAlias = "day_" . date("j", strtotime($currentDate)) . "_out";
    $values[] = $array[$inColumnAlias];
    $values[] = $array[$outColumnAlias];
    $currentDate = date("Y-m-d", strtotime($currentDate . ' + 1 day'));
  }
  return implode(',', $values);
}

//Associate monthly attendance export END
//Associate Salary export START
function paydetails_export()
{
  global $con;
  $year = isset($_POST['year']) ? $_POST['year'] : '';
  $months = isset($_POST['months']) ? $_POST['months'] : [];
  $id = isset($_POST['id']) ? strtoupper($_POST['id']) : '';

  $currentDate = date('d-m-Y');

  // Construct the WHERE clauses
  $yearFilter = !empty($year) ? "AND payyear = $year" : '';
  $monthFilter = '';
  if (!empty($months)) {
    if (!is_array($months)) {
      $months = [$months];
    }
    $monthFilter = "AND paymonth::integer IN (" . implode(',', $months) . ")";
  }
  $idFilter = !empty($id) ? "AND employeeid = '$id'" : '';

  // Check if at least one filter is applied
  if (empty($yearFilter) && empty($monthFilter) && empty($idFilter)) {
    // No filters applied, return without querying
    return;
  }

  // Build the query with applied filters
  $query = "SELECT * 
          FROM payslip_entry 
          LEFT JOIN (
              SELECT associatenumber, fullname, email, phone FROM rssimyaccount_members
          ) AS associate ON payslip_entry.employeeid = associate.associatenumber
          LEFT JOIN (
              SELECT bd.bank_account_number, bd.ifsc_code, bd.updated_for
              FROM bankdetails bd
              JOIN (
                  SELECT updated_for, MAX(updated_on) AS latest_update
                  FROM bankdetails
                  WHERE account_nature = 'savings'
                  GROUP BY updated_for
              ) latest_bd ON bd.updated_for = latest_bd.updated_for AND bd.updated_on = latest_bd.latest_update
              WHERE bd.account_nature = 'savings'
          ) AS bankdetails ON payslip_entry.employeeid = bankdetails.updated_for
          WHERE 1=1 $idFilter $yearFilter $monthFilter
          ORDER BY payslip_issued_on DESC";

  $result = pg_query($con, $query);
  $resultArr = pg_fetch_all($result);

  if (!$resultArr) {
    return; // No data to export
  }

  // Output CSV headers
  echo 'PYMT_PROD_TYPE_CODE,PYMT_MODE,DEBIT_ACC_NO,BNF_NAME,BENE_ACC_NO,BENE_IFSC,AMOUNT,DEBIT_NARR,CREDIT_NARR,MOBILE_NUM,EMAIL_ID,REMARK,PYMT_DATE,REF_NO,ADDL_INFO1,ADDL_INFO2,ADDL_INFO3,ADDL_INFO4,ADDL_INFO5' . "\n";

  foreach ($resultArr as $array) {
    $payslip_entry_id = $array['payslip_entry_id'];

    $result_component_earning_total = pg_query($con, "SELECT SUM(amount) FROM payslip_component WHERE payslip_entry_id = '$payslip_entry_id' AND components = 'Earning'");
    $total_earning = pg_fetch_result($result_component_earning_total, 0, 0);

    $result_component_deduction_total = pg_query($con, "SELECT SUM(amount) FROM payslip_component WHERE payslip_entry_id = '$payslip_entry_id' AND components = 'Deduction'");
    $total_deduction = pg_fetch_result($result_component_deduction_total, 0, 0);

    $net_pay = $total_earning - $total_deduction;

    echo 'PAB_VENDOR,NEFT,\'004201033772,' . $array['fullname'] . ',\'' . $array['bank_account_number'] . ',' . $array['ifsc_code'] . ',' . $net_pay . ',,,' . $array['phone'] . ',' . $array['email'] . ',RSSI Salary ' . date('F', mktime(0, 0, 0, $array['paymonth'], 1)) . ' ' . $array['payyear'] . ',' . $currentDate . ',' . $array['payslip_entry_id'] . "\n";
  }
}
//Associate Salary export END