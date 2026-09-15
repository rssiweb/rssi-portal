<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

$today = date("YmdHis");

//FEES EXPORT FUNTION

$export_type = $_POST["export_type"];
header("Content-type: application/csv");
header("Content-Disposition: attachment; filename={$export_type}_$today.csv");
header("Pragma: no-cache");
header("Expires: 0");

if ($export_type == "donation") {
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
    WHERE (
        (
            pd.donationid LIKE '%' || $1 || '%' OR
            pd.tel LIKE '%' || $1 || '%'
        ) OR $1 IS NULL
    ) AND (
        (
            CASE 
                WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp)
                ELSE EXTRACT(YEAR FROM pd.timestamp) - 1
            END || '-' ||
            CASE 
                WHEN EXTRACT(MONTH FROM pd.timestamp) >= 4 THEN EXTRACT(YEAR FROM pd.timestamp) + 1
                ELSE EXTRACT(YEAR FROM pd.timestamp)
            END
        ) = $2 OR $2 IS NULL
    ) AND status='Approved'
    ORDER BY pd.timestamp DESC";

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

    echo $counter . ',' . ',' . (!empty($array['id_type']) ? $array['id_type'] : (!empty($array['id_number']) ? 'pan' : '')) . ','
      . $array['id_number'] . ',' . 'Section 80G' . ',' . 'AAKCR2540KF20214' . ',' . date("d/m/Y g:i a", strtotime($array['timestamp'])) . ',' . $array['fullname'] . ',"' . $array['postaladdress'] . '",' . 'Corpus' . ',' . 'Electronic modes including account payee cheque/draft' . ',' . $array['currency'] . ',' . $array['amount'] . ',' . $array['donationid'] . ',' . 'https://login.rssi.in/donation_invoice.php?searchField=' . $array['donationid'] . "\n";
    $counter++; // Increment the counter for each iteration
  }
}

function gps_export()
{
  global $con, $role, $associatenumber; // $role should already be set in session/login

  // ======================================================
  // READ FILTERS (FOR BOTH ROLES)
  // ======================================================
  $item_type      = $_POST['item_type'] ?? '';
  $taggedto       = strtoupper($_POST['taggedto'] ?? '');
  $assetid        = trim($_POST['assetid'] ?? '');
  $assetstatus    = $_POST['asset_status'] ?? '';
  $assetcategory  = $_POST['asset_category'] ?? '';

  // ======================================================
  // BUILD CONDITIONS (ROLE SAFE)
  // ======================================================
  $conditions = [];

  // Non-Admin base restriction
  if ($role !== 'Admin') {
    $conditions[] = "gps.taggedto = '$associatenumber'";
  }

  // Asset ID search priority
  $isAssetSearch = ($assetid !== '');

  if ($isAssetSearch) {

    if (strpos($assetid, ',') !== false) {
      $ids = array_map('trim', explode(',', $assetid));
      $ids = array_map(fn($id) => pg_escape_string($con, $id), $ids);
      $conditions[] = "gps.itemid IN ('" . implode("','", $ids) . "')";
    } else {
      $safe = pg_escape_string($con, $assetid);
      $conditions[] = "(gps.itemid = '$safe' OR gps.itemname ILIKE '%$safe%')";
    }
  } else {

    if ($item_type !== "" && $item_type !== "ALL") {
      $conditions[] = "gps.itemtype = '$item_type'";
    }

    if ($assetcategory !== "" && $assetcategory !== "ALL") {
      $conditions[] = "gps.asset_category = '$assetcategory'";
    }

    if ($assetstatus !== "") {
      $conditions[] = "gps.asset_status = '$assetstatus'";
    }

    // Admin-only taggedto filter
    if ($role === 'Admin' && $taggedto !== "") {
      $conditions[] = "gps.taggedto = '$taggedto'";
    }
  }

  // No filter → show nothing
  $hasFilter =
    $isAssetSearch ||
    ($item_type) ||
    ($assetcategory) ||
    ($assetstatus) ||
    ($role === 'Admin' && $taggedto);

  if (!$hasFilter) {
    $conditions[] = "1 = 0";
  }

  // ======================================================
  // MAIN QUERY
  // ======================================================
  $query = "
  SELECT 
      gps.*,
      tmember.fullname AS tfullname,
      tmember.phone AS tphone,
      tmember.email AS temail,
      imember.fullname AS ifullname,
      imember.phone AS iphone,
      imember.email AS iemail,
      v.verification_date,
      v.verified_by,
      verified_member.fullname AS verified_by_name,
      v.verification_status,
      v.admin_review_status
  FROM gps
  LEFT JOIN rssimyaccount_members AS tmember
      ON gps.taggedto = tmember.associatenumber
  LEFT JOIN rssimyaccount_members AS imember
      ON gps.collectedby = imember.associatenumber
  LEFT JOIN (
      SELECT DISTINCT ON (asset_id)
          asset_id, verification_date, verified_by,
          verification_status, admin_review_status
      FROM gps_verifications
      ORDER BY asset_id, verification_date DESC
  ) v ON gps.itemid = v.asset_id
  LEFT JOIN rssimyaccount_members AS verified_member
      ON v.verified_by = verified_member.associatenumber
  ";

  if ($conditions) {
    $query .= " WHERE " . implode(" AND ", $conditions);
  }

  $query .= " ORDER BY gps.itemname ASC, gps.purchase_date DESC";

  $result = pg_query($con, $query);
  if (!$result) {
    echo "Error exporting data";
    exit;
  }

  $rows = pg_fetch_all($result) ?: [];

  /* -----------------------------
       CSV HEADER (ROLE BASED)
    ----------------------------- */
  if ($role === 'Admin') {

    echo "Asset Id,Asset Name,Asset Type,Quantity,Tagged To,Status,Unit Price,Purchase Bill,Purchase Date,Asset Photo,Last Verified,Verified By,Verification Status,Review Status\n";
  } else {

    echo "Asset Id,Asset Name,Quantity,Tagged To,Status,Purchase Bill,Asset Photo,Last Verified,Verified By,Verification Status\n";
  }

  /* -----------------------------
       CSV DATA
    ----------------------------- */
  foreach ($rows as $r) {

    $hasBill  = !empty($r['purchase_bill']) ? 'Yes' : 'No';
    $hasPhoto = !empty($r['asset_photo'])   ? 'Yes' : 'No';

    if ($role === 'Admin') {

      echo
      $r['itemid'] . ',"' . $r['itemname'] . '",' .
        $r['itemtype'] . ',' .
        $r['quantity'] . ',' .
        $r['taggedto'] . ',' .
        $r['asset_status'] . ',' .
        $r['unit_cost'] . ',' .
        $r['purchase_bill'] . ',' .
        $r['purchase_date'] . ',' .
        $r['asset_photo'] . ',' .
        $r['verification_date'] . ',"' .
        $r['verified_by_name'] . '",' .
        $r['verification_status'] . ',' .
        $r['admin_review_status'] . "\n";
    } else {

      echo
      $r['itemid'] . ',"' . $r['itemname'] . '",' .
        $r['quantity'] . ',' .
        $r['taggedto'] . ',' .
        $r['asset_status'] . ',' .
        $hasBill . ',' .
        $hasPhoto . ',' .
        $r['verification_date'] . ',"' .
        $r['verified_by_name'] . '",' .
        $r['verification_status'] . "\n";
    }
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
  $selected_location = $_POST['get_location'] ?? '';

  // Build the query based on search parameters
  if ($searchByIdOnly == '1') {
    // Search by Student ID only
    if (!empty($stid)) {
      $query = "SELECT * FROM rssimyprofile_student WHERE student_id = $1";
      $params = [$stid];

      // Add location filter if selected
      if (!empty($selected_location)) {
        $query .= " AND preferredbranch = $" . (count($params) + 1);
        $params[] = $selected_location;
      }

      $result = pg_query_params($con, $query, $params);
    }
  } else {
    // Normal search (requires module and status)
    if (!empty($module) && !empty($id)) {
      $query = "SELECT * FROM rssimyprofile_student 
                     WHERE filterstatus = $1 AND module = $2";
      $params = [$id, $module];

      $paramCount = 3;

      // Add location filter if selected
      if (!empty($selected_location)) {
        $query .= " AND preferredbranch = $$paramCount";
        $params[] = $selected_location;
        $paramCount++;
      }

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

  // Function to check form availability for a student
  function getStudentFormStatus($student_id, $con)
  {
    $forms = [
      'form_1a' => 'No',
      'form_1b' => 'No'
    ];

    // Check for Form 1A
    $query1A = "SELECT COUNT(*) as count 
                FROM student_applications 
                WHERE student_id = $1 
                AND document_type = 'Form 1A' 
                AND status = 'submitted'";
    $result1A = pg_query_params($con, $query1A, array($student_id));
    if ($result1A) {
      $row1A = pg_fetch_assoc($result1A);
      if ($row1A['count'] > 0) {
        $forms['form_1a'] = 'Yes';
      }
    }

    // Check for Form 1B
    $query1B = "SELECT COUNT(*) as count 
                FROM student_applications 
                WHERE student_id = $1 
                AND document_type = 'Form 1B' 
                AND status = 'submitted'";
    $result1B = pg_query_params($con, $query1B, array($student_id));
    if ($result1B) {
      $row1B = pg_fetch_assoc($result1B);
      if ($row1B['count'] > 0) {
        $forms['form_1b'] = 'Yes';
      }
    }

    return $forms;
  }

  // Set headers for CSV download
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=student_export_' . date('Y-m-d') . '.csv');

  // Create output stream
  $output = fopen('php://output', 'w');

  // Write CSV header - Added Form 1A and Form 1B columns
  fputcsv($output, [
    'Student Id',
    'Name',
    'Category',
    'Class',
    'Age',
    'Gender',
    'Contact',
    'Access',
    'Plan',
    'Status',
    'DOA',
    'Caste',
    'Caste Doc',
    'Student Aadhaar',
    'Student Aadhaar Doc',
    'Parent Aadhaar',
    'DOT',
    'Remarks',
    'Location',
    'Form 1A Available',
    'Form 1B Available'
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
    // Get form status for this student
    $formStatus = getStudentFormStatus($array['student_id'], $con);

    // Determine if Form 1B should be shown
    $showForm1B = !($array['category'] == 'LG1' || !empty($array['nameoftheschool']));

    // Set Form 1B value based on condition
    $form1bValue = $showForm1B ? $formStatus['form_1b'] : '';

    fputcsv($output, [
      $array['student_id'],
      $array['studentname'],
      $array['category'],
      $array['class'],
      (new DateTime($array['dateofbirth']))->diff(new DateTime())->y,
      $array['gender'],
      $array['contact'],
      $array['access_category'],
      $array['type_of_admission'],
      $array['filterstatus'],
      $array['doa'],
      $array['caste'],
      !empty($array['caste_document']) ? "Yes" : "No",
      maskAadhar($array['studentaadhar']),
      !empty($array['upload_aadhar_card']) ? "Yes" : "No",
      maskAadhar($array['guardianaadhar']),
      $array['effectivefrom'],
      $array['remarks'],
      $array['preferredbranch'],
      $formStatus['form_1a'],  // Form 1A Available
      $form1bValue              // Form 1B Available (only if condition met)
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
  @$selectedClasses = isset($_POST['classes']) ? $_POST['classes'] : [];
  @$selectedLocation = isset($_POST['get_location']) ? $_POST['get_location'] : '';

  // Calculate the start and end dates of the month
  $startDate = date("Y-m-01", strtotime($month));
  $endDate = date("Y-m-t", strtotime($month));

  // Validate categories against DB
  $categoriesQuery = "SELECT DISTINCT category FROM rssimyprofile_student WHERE category IS NOT NULL ORDER BY category";
  $categoriesResult = pg_query($con, $categoriesQuery);
  $allCategories = pg_fetch_all_columns($categoriesResult, 0);

  $validCategories = [];
  foreach ($selectedCategories as $cat) {
    if (in_array($cat, $allCategories)) {
      $validCategories[] = pg_escape_string($con, $cat);
    }
  }

  // Validate classes against DB
  $classesQuery = "SELECT DISTINCT class FROM rssimyprofile_student WHERE class IS NOT NULL ORDER BY class";
  $classesResult = pg_query($con, $classesQuery);
  $allClasses = pg_fetch_all_columns($classesResult, 0);

  $validClasses = [];
  foreach ($selectedClasses as $cls) {
    if (in_array($cls, $allClasses)) {
      $validClasses[] = pg_escape_string($con, $cls);
    }
  }

  // Build conditions
  $idCondition = "";
  if ($id != null) {
    $idCondition = "AND s.filterstatus = '" . pg_escape_string($con, $id) . "'";
  }

  $categoryCondition = "";
  if (!empty($validCategories)) {
    $categoryList = "'" . implode("','", $validCategories) . "'";
    $categoryCondition = "AND s.category IN ($categoryList)";
  }

  $classCondition = "";
  if (!empty($validClasses)) {
    $classList = "'" . implode("','", $validClasses) . "'";
    $classCondition = "AND s.class IN ($classList)";
  }

  $locationCondition = "";
  if (!empty($selectedLocation)) {
    $locationCondition = "AND s.preferredbranch = '" . pg_escape_string($con, $selectedLocation) . "'";
  }

  if (empty($validCategories) && empty($validClasses)) {
    $resultArr = [];
    exportAttendanceToCSV($resultArr, $startDate, $endDate);
    return;
  }

  // Escaped date literals for reuse
  $startDateEsc = pg_escape_literal($con, $startDate);
  $endDateEsc   = pg_escape_literal($con, $endDate);
  $monthEsc     = pg_escape_literal($con, $month);

  // Construct the optimized SQL query
  $query = "
WITH
-- 1) Filter students FIRST (small set), before any cross join
filtered_students AS (
    SELECT
        s.student_id,
        s.filterstatus,
        s.studentname,
        s.category,
        s.class,
        s.effectivefrom,
        s.doa,
        s.contact
    FROM rssimyprofile_student s
    WHERE
        (
            s.effectivefrom IS NULL OR
            DATE_TRUNC('month', s.effectivefrom)::DATE = DATE_TRUNC('month', TO_DATE($monthEsc, 'YYYY-MM'))::DATE
        )
        AND DATE_TRUNC('month', s.doa)::DATE <= DATE_TRUNC('month', TO_DATE($monthEsc, 'YYYY-MM'))::DATE
        $idCondition
        $categoryCondition
        $classCondition
        $locationCondition
),

-- 2) Expand student_class_days into actual dates ONCE
--    class_days is stored as e.g. 'Mon,Tue,Wed' -> split & match day names
class_day_dates AS (
    SELECT DISTINCT
        cw.category,
        d::date AS class_date
    FROM student_class_days cw
    CROSS JOIN LATERAL generate_series(
        GREATEST(cw.effective_from, $startDateEsc::date),
        LEAST(COALESCE(cw.effective_to, $endDateEsc::date), $endDateEsc::date),
        interval '1 day'
    ) d
    WHERE TRIM(TO_CHAR(d, 'Dy')) = ANY (
        string_to_array(REPLACE(cw.class_days, ' ', ''), ',')
    )
),

-- 3) Dates on which attendance was actually taken (small set, computed once)
days_with_attendance AS (
    SELECT DISTINCT date
    FROM attendance
    WHERE date BETWEEN $startDateEsc::date AND $endDateEsc::date
),

-- 4) Holidays in range
holidays_in_range AS (
    SELECT holiday_date
    FROM holidays
    WHERE holiday_date BETWEEN $startDateEsc::date AND $endDateEsc::date
),

-- 5) Student exceptions in range
student_exceptions AS (
    SELECT
        m.student_id,
        e.exception_date AS attendance_date
    FROM student_class_days_exceptions e
    JOIN student_exception_mapping m ON e.exception_id = m.exception_id
    WHERE e.exception_date BETWEEN $startDateEsc::date AND $endDateEsc::date
),

-- 6) Calendar days in range
date_range AS (
    SELECT generate_series($startDateEsc::date, $endDateEsc::date, interval '1 day')::date AS attendance_date
),

-- 7) Build attendance_data with proper joins (no correlated EXISTS)
attendance_data AS (
    SELECT
        s.student_id,
        s.filterstatus,
        s.studentname,
        s.category,
        s.class,
        s.contact,
        d.attendance_date,
        CASE
            WHEN a.user_id IS NOT NULL THEN 'P'
            WHEN h.holiday_date IS NOT NULL THEN NULL
            WHEN ex.attendance_date IS NOT NULL THEN NULL
            WHEN dwa.date IS NOT NULL
                 AND cd.class_date IS NOT NULL
                 AND s.doa <= d.attendance_date
                 THEN 'A'
            ELSE NULL
        END AS attendance_status
    FROM date_range d
    CROSS JOIN filtered_students s
    LEFT JOIN attendance a
           ON a.user_id = s.student_id
          AND a.date = d.attendance_date
    LEFT JOIN holidays_in_range h
           ON h.holiday_date = d.attendance_date
    LEFT JOIN student_exceptions ex
           ON ex.attendance_date = d.attendance_date
          AND ex.student_id = s.student_id
    LEFT JOIN days_with_attendance dwa
           ON dwa.date = d.attendance_date
    LEFT JOIN class_day_dates cd
           ON cd.category = s.category
          AND cd.class_date = d.attendance_date
),

-- 8) Compute totals per student ONCE (small aggregate)
student_totals AS (
    SELECT
        student_id,
        COUNT(*) FILTER (WHERE attendance_status IS NOT NULL) AS total_classes,
        COUNT(*) FILTER (WHERE attendance_status = 'P')      AS attended_classes
    FROM attendance_data
    GROUP BY student_id
)

SELECT
    ad.student_id,
    ad.filterstatus,
    ad.studentname,
    ad.category,
    ad.class,
    ad.contact,
    ad.attendance_date,
    ad.attendance_status,
    " . generate_date_columns($startDate, $endDate) . ",
    st.total_classes,
    st.attended_classes,
    CASE
        WHEN st.total_classes = 0 OR st.total_classes IS NULL THEN NULL
        ELSE CONCAT(ROUND((st.attended_classes * 100.0) / st.total_classes, 2), '%')
    END AS attendance_percentage
FROM attendance_data ad
JOIN student_totals st ON st.student_id = ad.student_id
GROUP BY
    ad.student_id,
    ad.filterstatus,
    ad.studentname,
    ad.category,
    ad.class,
    ad.contact,
    ad.attendance_date,
    ad.attendance_status,
    st.total_classes,
    st.attended_classes
ORDER BY
    CASE WHEN ad.class = 'Pre-school' THEN 0 ELSE 1 END,
    ad.category,
    ad.class,
    ad.student_id,
    ad.attendance_date;
";

  $result = pg_query($con, $query);

  if (!$result) {
    echo "An error occurred.\n";
    exit;
  }

  $resultArr = pg_fetch_all($result);

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
  @$engagementFilter = $_POST['engagementFilter'];

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

  // NEW: Construct the engagement condition
  $engagementCondition = '';
  if (!empty($engagementFilter)) {
    $engagementCondition = "AND m.engagement = '" . pg_escape_string($con, $engagementFilter) . "'";
  }

  // Construct the SQL query
  $query = "
WITH date_range AS (
    SELECT generate_series(
        '$startDate'::date,
        '$endDate'::date,
        '1 day'::interval
    )::date AS attendance_date
),

-- 1. Filter members ONCE
members AS (
    SELECT
        m.associatenumber,
        m.filterstatus,
        m.fullname,
        m.engagement,
        m.position,
        COALESCE(substring(m.class FROM '^[^-]+'), NULL) AS mode,
        m.effectivedate,
        m.doj
    FROM rssimyaccount_members m
    WHERE (m.filterstatus = 'Active'
           OR (m.filterstatus = 'Inactive'
               AND DATE_TRUNC('month', m.effectivedate)::date
                   >= DATE_TRUNC('month', TO_DATE('$month','YYYY-MM'))::date))
      AND DATE_TRUNC('month', m.doj)::date
          <= DATE_TRUNC('month', TO_DATE('$month','YYYY-MM'))::date
      $idCondition
      $teacherCondition
      $engagementCondition
      " . ($role !== 'Admin' ? "AND m.associatenumber = '$associatenumber'" : "") . "
),

-- 2. Pre-aggregate punch in/out per user per day (no status split)
punch_agg AS (
    SELECT
        a.user_id,
        DATE_TRUNC('day', a.punch_in)::date AS punch_date,
        MIN(a.punch_in) AS punch_in,
        CASE WHEN COUNT(*) = 1 THEN NULL ELSE MAX(a.punch_in) END AS punch_out
    FROM attendance a
    WHERE a.punch_in >= '$startDate'::date
      AND a.punch_in <  ('$endDate'::date + INTERVAL '1 day')
    GROUP BY a.user_id, DATE_TRUNC('day', a.punch_in)::date
),

-- 3. Pre-aggregate exceptions ONCE
entry_exc AS (
    SELECT
        e.submitted_by,
        DATE(e.start_date_time) AS exc_date,
        MIN(e.start_date_time) FILTER (WHERE e.sub_exception_type = 'missed-entry') AS missed_entry_time,
        MIN(e.start_date_time) FILTER (WHERE e.sub_exception_type = 'late-entry')   AS late_entry_time
    FROM exception_requests e
    WHERE e.status = 'Approved'
      AND e.exception_type = 'entry'
      AND e.start_date_time >= '$startDate'::date
      AND e.start_date_time <  ('$endDate'::date + INTERVAL '1 day')
    GROUP BY e.submitted_by, DATE(e.start_date_time)
),
exit_exc AS (
    SELECT
        e.submitted_by,
        DATE(e.end_date_time) AS exc_date,
        MIN(e.end_date_time) AS exit_time
    FROM exception_requests e
    WHERE e.status = 'Approved'
      AND e.exception_type = 'exit'
      AND e.end_date_time >= '$startDate'::date
      AND e.end_date_time <  ('$endDate'::date + INTERVAL '1 day')
    GROUP BY e.submitted_by, DATE(e.end_date_time)
),

-- 4. Pre-aggregate leaves ONCE per (applicant, date, halfday)
leave_agg AS (
    SELECT
        l.applicantid,
        d.attendance_date,
        MAX(CASE WHEN l.halfday = 0 THEN 1 ELSE 0 END) AS has_full_leave,
        MAX(CASE WHEN l.halfday = 1 THEN 1 ELSE 0 END) AS halfday_count
    FROM leavedb_leavedb l
    JOIN date_range d
      ON d.attendance_date BETWEEN l.fromdate AND l.todate
    WHERE l.status = 'Approved'
    GROUP BY l.applicantid, d.attendance_date
),

-- 5. Schedule lookup (single pass, uses end_date if available)
sched AS (
    SELECT DISTINCT ON (s.associate_number, s.workday, s.start_date)
        s.associate_number,
        s.workday,
        s.start_date,
        s.reporting_time,
        s.exit_time
    FROM associate_schedule_v2 s
    ORDER BY s.associate_number, s.workday, s.start_date DESC
),

-- 6. Base grid: members × date_range (small × days) joined to schedule & punches
base AS (
    SELECT
        m.associatenumber,
        m.filterstatus,
        m.fullname,
        m.engagement,
        m.position,
        m.mode,
        m.effectivedate,
        m.doj,
        d.attendance_date,
        p.punch_in,
        p.punch_out,
        sc.reporting_time,
        sc.exit_time,
        ee.missed_entry_time,
        ee.late_entry_time,
        xe.exit_time AS exc_exit_time,
        COALESCE(la.has_full_leave, 0)  AS has_full_leave,
        COALESCE(la.halfday_count, 0)   AS halfday_count
    FROM members m
    CROSS JOIN date_range d
    LEFT JOIN punch_agg p
           ON p.user_id = m.associatenumber
          AND p.punch_date = d.attendance_date
    LEFT JOIN sched sc
           ON sc.associate_number = m.associatenumber
          AND sc.workday = CASE EXTRACT(DOW FROM d.attendance_date)
                              WHEN 1 THEN 'Mon' WHEN 2 THEN 'Tue' WHEN 3 THEN 'Wed'
                              WHEN 4 THEN 'Thu' WHEN 5 THEN 'Fri' WHEN 6 THEN 'Sat'
                              WHEN 0 THEN 'Sun' END
          AND sc.start_date <= d.attendance_date
    LEFT JOIN entry_exc ee
           ON ee.submitted_by = m.associatenumber
          AND ee.exc_date = d.attendance_date
    LEFT JOIN exit_exc xe
           ON xe.submitted_by = m.associatenumber
          AND xe.exc_date = d.attendance_date
        LEFT JOIN leave_agg la
           ON la.applicantid = m.associatenumber
          AND la.attendance_date = d.attendance_date
),

-- 7. Apply all the CASE logic in one place
final AS (
    SELECT
        b.*,
        COALESCE(b.missed_entry_time, b.punch_in)   AS eff_punch_in,
        COALESCE(b.exc_exit_time,     b.punch_out)  AS eff_punch_out,

        CASE
            WHEN b.punch_in IS NOT NULL THEN 'P'
            WHEN b.doj > b.attendance_date THEN NULL
            ELSE 'A'
        END AS attendance_status,

        CASE
            WHEN b.has_full_leave = 1 THEN 'Leave'
            WHEN b.halfday_count >= 2 THEN 'Leave'
            WHEN b.halfday_count = 1  THEN 'HF'
            WHEN b.late_entry_time IS NOT NULL THEN
                CASE
                    WHEN b.punch_in IS NOT NULL
                     AND b.punch_in::time <= b.late_entry_time::time THEN 'Exc.'
                    WHEN b.punch_in IS NOT NULL THEN 'Exc.L'
                    ELSE NULL
                END
            WHEN b.missed_entry_time IS NOT NULL THEN
                CASE
                    WHEN b.missed_entry_time::time
                         > b.reporting_time + INTERVAL '10 minutes' THEN 'L'
                    WHEN b.missed_entry_time::time > b.reporting_time
                         AND b.missed_entry_time::time
                             <= b.reporting_time + INTERVAL '10 minutes' THEN 'W'
                    ELSE NULL
                END
            WHEN b.punch_in IS NOT NULL THEN
                CASE
                    WHEN b.reporting_time IS NULL THEN 'NA'
                    WHEN b.punch_in::time > b.reporting_time + INTERVAL '1 minute'
                     AND b.punch_in::time <= b.reporting_time + INTERVAL '11 minutes' THEN 'W'
                    WHEN b.punch_in::time > b.reporting_time + INTERVAL '10 minutes' THEN 'L'
                    ELSE NULL
                END
            ELSE NULL
        END AS late_status,

        CASE
            WHEN b.exc_exit_time IS NOT NULL THEN 'Exc.'
            WHEN b.punch_out IS NOT NULL
             AND b.exit_time IS NOT NULL
             AND b.punch_out::time < b.exit_time
             AND b.halfday_count = 0 THEN 'EE'
            ELSE NULL
        END AS exit_status,

        CASE
            WHEN b.missed_entry_time IS NOT NULL THEN
                CASE WHEN b.reporting_time IS NULL THEN 'Exc.NA' ELSE 'Exc.' END
            ELSE NULL
        END AS exception_status
    FROM base b
)

SELECT
    associatenumber,
    filterstatus,
    fullname,
    engagement,
    position,
    mode,
    attendance_date,
    attendance_status,
    eff_punch_in AS punch_in,
    eff_punch_out AS punch_out,
    reporting_time,
    late_status,
    exit_status,
    exception_status,
    COUNT(*) FILTER (WHERE attendance_status = 'P')
        OVER (PARTITION BY associatenumber) AS attended_classes
FROM final
ORDER BY associatenumber, attendance_date;
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