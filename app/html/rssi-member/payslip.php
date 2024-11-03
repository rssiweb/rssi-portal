<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  $_SESSION["login_redirect_params"] = $_GET;
  header("Location: index.php");
  exit;
}

validation();
// Initialize $ref from GET parameter
$ref = isset($_GET['ref']) ? $_GET['ref'] : null;

// Initialize variables
$result = null;
$result_component = null;
$result_component_earning_total = null;
$result_component_deduction_total = null;
$result_accrued_bonus = null;
$result_payout_bonus = null;
$paymonth_comp = null;
$payyear_comp = null;
$employeeid_comp = null;
$check_employeeid = null;

// Check user role
if ($role == 'Admin') {
  // Query to retrieve payslip entry data
  $result = pg_query_params($con, "SELECT paymonth, payyear, employeeid,payslip_issued_on, * 
                                     FROM payslip_entry 
                                     LEFT JOIN rssimyaccount_members 
                                     ON rssimyaccount_members.associatenumber = payslip_entry.employeeid 
                                     WHERE payslip_entry_id = $1", array($ref));

  // Fetch required data from the result
  if ($row = pg_fetch_assoc($result)) {
    $paymonth_comp = $row['paymonth'];
    $payyear_comp = $row['payyear'];
    $employeeid_comp = $row['employeeid'];
    $payslip_issued_on = $row['payslip_issued_on'];

    // Query to retrieve payslip component data
    $result_component = pg_query_params($con, "SELECT * 
                                                   FROM payslip_component 
                                                   WHERE payslip_entry_id = $1", array($ref));

    // Query to calculate total earnings
    $result_component_earning_total = pg_query_params($con, "SELECT COALESCE(SUM(amount), 0) 
                                                                 FROM payslip_component 
                                                                 WHERE payslip_entry_id = $1 
                                                                 AND components = 'Earning'", array($ref));

    // Query to calculate total deductions
    $result_component_deduction_total = pg_query_params($con, "SELECT COALESCE(SUM(amount), 0) 
                                                                   FROM payslip_component 
                                                                   WHERE payslip_entry_id = $1 
                                                                   AND components = 'Deduction'", array($ref));

    // New query to retrieve accrued bonus and payout bonus data for admin users
    $query = "
    SELECT employeeid, 
           paymonth, 
           payyear,
           SUM(CASE WHEN subcategory = 'Monthly Bonus' THEN amount ELSE 0 END) AS monthly_bonus_amount,
           SUM(CASE WHEN subcategory = 'Bonus Payout' THEN amount ELSE 0 END) AS monthly_payout_bonus,
           SUM(CASE WHEN subcategory = 'Monthly Bonus' THEN amount ELSE 0 END) OVER (PARTITION BY employeeid ORDER BY payyear, paymonth) AS cumulative_accrued_bonus,
           SUM(CASE WHEN subcategory = 'Bonus Payout' THEN amount ELSE 0 END) OVER (PARTITION BY employeeid ORDER BY payyear, paymonth) AS cumulative_payout_bonus,
           -- Calculate the cumulative balance as accrued minus payout
           SUM(CASE WHEN subcategory = 'Monthly Bonus' THEN amount ELSE 0 END) OVER (PARTITION BY employeeid ORDER BY payyear, paymonth)
           - SUM(CASE WHEN subcategory = 'Bonus Payout' THEN amount ELSE 0 END) OVER (PARTITION BY employeeid ORDER BY payyear, paymonth) AS balance
    FROM payslip_component 
    JOIN payslip_entry ON payslip_entry.payslip_entry_id = payslip_component.payslip_entry_id 
    WHERE (subcategory = 'Monthly Bonus' OR subcategory = 'Bonus Payout') 
      AND paymonth <= $1 
      AND payyear <= $2 
      AND payslip_entry.employeeid = $3 
    GROUP BY employeeid, paymonth, payyear, subcategory, amount
    ORDER BY payyear, paymonth;
    ";

    // Prepare and execute the query with dynamic parameters for admin
    $result_accrued_bonus = pg_query_params($con, $query, array($paymonth_comp, $payyear_comp, $employeeid_comp));
  }
} else {
  // Query to retrieve payslip entry data for non-admin users
  $result = pg_query_params($con, "SELECT paymonth, payyear, employeeid,payslip_issued_on, * 
                                     FROM payslip_entry 
                                     LEFT JOIN rssimyaccount_members 
                                     ON rssimyaccount_members.associatenumber = payslip_entry.employeeid 
                                     WHERE payslip_entry_id = $1 
                                     AND employeeid = $2", array($ref, $user_check));

  // Fetch required data from the result
  if ($row = pg_fetch_assoc($result)) {
    $paymonth_comp = $row['paymonth'];
    $payyear_comp = $row['payyear'];
    $employeeid_comp = $row['employeeid'];
    $payslip_issued_on = $row['payslip_issued_on'];

    // Query to retrieve payslip component data for non-admin users
    $result_component = pg_query_params($con, "SELECT * 
                                                   FROM payslip_component 
                                                   WHERE payslip_entry_id = $1 
                                                   AND payslip_entry_id IN 
                                                       (SELECT payslip_entry_id 
                                                       FROM payslip_entry 
                                                       WHERE employeeid = $2)", array($ref, $user_check));

    // Query to calculate total earnings for non-admin users
    $result_component_earning_total = pg_query_params($con, "SELECT SUM(amount) 
                                                                 FROM payslip_component 
                                                                 WHERE payslip_entry_id = $1 
                                                                 AND components = 'Earning' 
                                                                 AND payslip_entry_id IN 
                                                                     (SELECT payslip_entry_id 
                                                                     FROM payslip_entry 
                                                                     WHERE employeeid = $2)", array($ref, $user_check));

    // Query to calculate total deductions for non-admin users
    $result_component_deduction_total = pg_query_params($con, "SELECT SUM(amount) 
                                                                   FROM payslip_component 
                                                                   WHERE payslip_entry_id = $1 
                                                                   AND components = 'Deduction' 
                                                                   AND payslip_entry_id IN 
                                                                       (SELECT payslip_entry_id 
                                                                       FROM payslip_entry 
                                                                       WHERE employeeid = $2)", array($ref, $user_check));

    // Query to check employeeid for non-admin users
    $result_check = pg_query_params($con, "SELECT employeeid 
                                               FROM payslip_entry 
                                               WHERE payslip_entry_id = $1", array($ref));
    $row_check = pg_fetch_assoc($result_check);
    $check_employeeid = $row_check['employeeid'];

    // New query to retrieve accrued bonus and payout bonus data for non-admin users
    // New query to retrieve accrued bonus and payout bonus data for non-admin users
    $query = "
SELECT employeeid, 
       paymonth, 
       payyear,
       SUM(CASE WHEN subcategory = 'Monthly Bonus' THEN amount ELSE 0 END) AS monthly_bonus_amount,
       SUM(CASE WHEN subcategory = 'Bonus Payout' THEN amount ELSE 0 END) AS monthly_payout_bonus,
       SUM(CASE WHEN subcategory = 'Monthly Bonus' THEN amount ELSE 0 END) OVER (PARTITION BY employeeid ORDER BY payyear, paymonth) AS cumulative_accrued_bonus,
       SUM(CASE WHEN subcategory = 'Bonus Payout' THEN amount ELSE 0 END) OVER (PARTITION BY employeeid ORDER BY payyear, paymonth) AS cumulative_payout_bonus,
       -- Calculate the cumulative balance as accrued minus payout
       SUM(CASE WHEN subcategory = 'Monthly Bonus' THEN amount ELSE 0 END) OVER (PARTITION BY employeeid ORDER BY payyear, paymonth)
       - SUM(CASE WHEN subcategory = 'Bonus Payout' THEN amount ELSE 0 END) OVER (PARTITION BY employeeid ORDER BY payyear, paymonth) AS balance
FROM payslip_component 
JOIN payslip_entry ON payslip_entry.payslip_entry_id = payslip_component.payslip_entry_id 
WHERE (subcategory = 'Monthly Bonus' OR subcategory = 'Bonus Payout') 
  AND paymonth <= $1 
  AND payyear <= $2 
  AND payslip_entry.employeeid = $3 
GROUP BY employeeid, paymonth, payyear, subcategory, amount
ORDER BY payyear, paymonth;
";

    // Prepare and execute the query with dynamic parameters for non-admin
    $result_accrued_bonus = pg_query_params($con, $query, array($paymonth_comp, $payyear_comp, $employeeid_comp));
  }
}

// Fetch results into arrays
$resultArr = pg_fetch_all($result);
$resultArr_result_component = pg_fetch_all($result_component);
$component_earning_total = pg_fetch_result($result_component_earning_total, 0, 0);
$component_deduction_total = pg_fetch_result($result_component_deduction_total, 0, 0);

// Check for errors in queries
if (!$result || !$result_component || !$result_component_earning_total || !$result_component_deduction_total) {
  echo "An error occurred.\n";
  exit;
}

// Initialize array to store latest submissions for each account nature
$latestSubmissions = [];

// Array of account natures
$accountNatures = ['savings'];

// Loop through each account nature
foreach ($accountNatures as $accountNature) {
  // Initialize latest submission variable
  $latestSubmission_bank = null;

  // Loop through resultArr
  foreach ($resultArr as $array) {
    // Query to select latest submission
    $selectLatestQuery_bank = "SELECT bank_account_number, bank_name, ifsc_code, account_holder_name, updated_for, updated_by, updated_on, passbook_page
                                   FROM bankdetails
                                   WHERE updated_for = $1
                                   AND account_nature = $2
                                   AND updated_on = (SELECT MAX(updated_on) 
                                                     FROM bankdetails 
                                                     WHERE updated_for = $3 
                                                     AND account_nature = $4)";

    // Execute query
    $result = pg_query_params($con, $selectLatestQuery_bank, array($array['associatenumber'], $accountNature, $array['associatenumber'], $accountNature));

    // Fetch latest submission data
    if ($result) {
      while ($row = pg_fetch_assoc($result)) {
        $latestSubmission_bank = [
          'bank_account_number' => $row['bank_account_number'],
          'bank_name' => $row['bank_name'],
          'ifsc_code' => $row['ifsc_code'],
          'account_holder_name' => $row['account_holder_name'],
          'updated_for' => $row['updated_for'],
          'updated_by' => $row['updated_by'],
          'updated_on' => $row['updated_on'],
          'passbook_page' => $row['passbook_page']
        ];
      }
    }
  }
  $latestSubmissions[$accountNature] = $latestSubmission_bank;
}
?>

<!DOCTYPE html>
<html>

<head>
  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
  <script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
      dataLayer.push(arguments);
    }
    gtag('js', new Date());

    gtag('config', 'AW-11316670180');
  </script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <?php if ($role != 'Admin') { ?>
    <title>Payslip_<?php echo $ref ?></title>
  <?php } ?>
  <?php if ($role == 'Admin' && $ref != null) { ?>
    <title>Payslip_<?php echo $ref ?></title>
  <?php } ?>
  <?php if ($role == 'Admin' && $ref == null) { ?>
    <title>Payslip_</title>
  <?php } ?>

  <!-- Favicons -->
  <link href="../img/favicon.ico" rel="icon">
  <!-- Vendor CSS Files -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

  <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
  <!-- Glow Cookies v3.0.1 -->
  <script>
    glowCookies.start('en', {
      analytics: 'G-S25QWTFJ2S',
      //facebookPixel: '',
      policyLink: 'https://www.rssi.in/disclaimer'
    });
  </script>
  <style>
    .prebanner {
      display: none;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      padding: 8px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #f2f2f2;
    }

    /* Media query for print */
    @media print {

      .earnings,
      .deductions {
        flex-basis: 50%;
      }

      .print-header {
        display: flex;
      }

      .print-header .right-content {
        text-align: right;
        margin-left: auto;
      }
    }

    .report-footer {
      background-color: #f8f9fa;
      padding: 10px;
    }

    .page-break {
      page-break-before: always;
    }
  </style>
</head>

<body>
  <?php include 'inactive_session_expire_check.php'; ?>
  <div class="container-lg">
    <?php if ($role == 'Admin') { ?>
      <form action="" method="GET" class="d-print-none">
        <div class="form-group" style="display: flex; justify-content: flex-end; margin-top: 5%;">
          <div class="col2">
            <input name="ref" class="form-control" placeholder="Reference number" value="<?php echo $ref ?>">
          </div>

          <div class="col2" style="margin-left: 10px;">
            <button type="submit" name="search_by_id" class="btn btn-success btn-sm">
              <i class="bi bi-search"></i> Search
            </button>
            <button type="button" onclick="window.print()" name="print" class="btn btn-primary btn-sm">
              <i class="bi bi-save"></i> Save
            </button>
          </div>
        </div>
      </form>
    <?php } ?>


    <?php if (sizeof($resultArr) > 0) { ?>
      <?php if ($role != 'Admin') { ?>
        <div style="display: flex; margin-top: 5%;" class="justify-content-end d-print-none">
          <button type="button" onclick="window.print()" name="print" class="btn btn-primary btn-sm" style="outline: none;">
            <i class="bi bi-save"></i>&nbsp;Save
          </button>
        </div>
      <?php } ?>
      <?php foreach ($resultArr as $array) { ?>
        <table class="table" style="border: none;">
          <thead>
            <tr>
              <td>
                <div class="print-header">
                  <div class="right-content d-none d-print-inline-flex">
                    <!-- Scan QR code to check authenticity -->
                    <?php
                    $a = 'https://login.rssi.in/rssi-member/payslip.php?ref=';
                    $b = $array['payslip_entry_id'];
                    $url = $a . $b;
                    $url = urlencode($url);
                    ?>
                    <img class="qrimage" src="https://qrcode.tec-it.com/API/QRCode?data=<?php echo $url ?>" width="100px" />
                  </div>
                </div>
                <h3>Payslip</h3>
                <div style="display: flex; justify-content: space-between;">
                  <span><?php echo date('F', mktime(0, 0, 0, $array['paymonth'], 1)) ?> <?php echo $array['payyear'] ?></span>
                  <span>Ref. number: <?php echo $ref ?></span>
                </div>
              </td>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="border: none;">
                <div class="row">
                  <table>
                    <tr>
                      <th colspan="3"><?php echo $array['fullname'] ?></th>
                    </tr>
                    <tr>
                      <th>Employee Data</th>
                      <th>Payment Details</th>
                      <th>Location Details</th>
                    </tr>
                    <tr>
                      <td>Employee ID: <?php echo $array['associatenumber'] ?><br>
                        Designation: <?php echo substr($array['position'], 0, strrpos($array['position'], "-")) ?><br>
                        Grade: <?php echo @$array['grade'] ?><br>
                        Employment Type: <?php echo $array['job_type'] ?>

                      </td>
                      <td><?php foreach ($latestSubmissions as $accountNature => $latestSubmission) : ?>
                          <?php if ($latestSubmission !== null) : ?>
                            <p class="mb-1">Bank Name: <?php echo isset($latestSubmission['bank_name']) ? $latestSubmission['bank_name'] : 'N/A'; ?></p>
                            <p class="mb-1">Acc No: <?php echo isset($latestSubmission['bank_account_number']) ? $latestSubmission['bank_account_number'] : 'N/A'; ?></p>
                            <!-- <p class="mb-1">IFSC Code: <?php echo isset($latestSubmission['ifsc_code']) ? $latestSubmission['ifsc_code'] : 'N/A'; ?></p> -->
                            <!-- <p class="mb-1">Account Holder Name: <?php echo isset($latestSubmission['account_holder_name']) ? $latestSubmission['account_holder_name'] : 'N/A'; ?></p> -->
                          <?php else : ?>
                            <!-- Handle case when bank details are not available for the current account nature -->
                            <p>No <?php echo ucfirst($accountNature); ?> account details available.</p>
                          <?php endif; ?>
                        <?php endforeach; ?>
                        Days paid: <?php echo $array['dayspaid'] ?> days
                      </td>
                      <td>Base Br.: <?php echo $array['basebranch'] ?><br>
                        Depute Br.: <?php echo $array['depb'] ?>
                      </td>
                    </tr>
                  </table>
                  <hr>
                  <div class="row">
                    <div class="col-md-6 earnings">
                      <h2>Earnings</h2>
                      <table class="table">
                        <thead>
                          <tr>
                            <th>Category</th>
                            <th>Amount(INR)</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (sizeof($resultArr_result_component) > 0) { ?>
                            <?php foreach ($resultArr_result_component as $array) { ?>
                              <?php if ($array['components'] === 'Earning') { ?>
                                <tr>
                                  <td><?php echo $array['subcategory'] ?></td>
                                  <td><?php echo $array['amount'] ?></td>
                                </tr>
                              <?php } ?>
                            <?php } ?>
                          <?php } ?>
                          <tr>
                            <td><strong>Total Earnings</strong></td>
                            <td><strong><?php echo $component_earning_total ?></strong></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                    <div class="col-md-6 deductions">
                      <h2>Deductions</h2>
                      <table class="table">
                        <thead>
                          <tr>
                            <th>Category</th>
                            <th>Amount(INR)</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (sizeof($resultArr_result_component) > 0) { ?>
                            <?php foreach ($resultArr_result_component as $array) { ?>
                              <?php if ($array['components'] === 'Deduction') { ?>
                                <tr>
                                  <td><?php echo $array['subcategory'] ?></td>
                                  <td><?php echo $array['amount'] ?></td>
                                </tr>
                              <?php } ?>
                            <?php } ?>
                          <?php } ?>
                          <td><strong>Total Deductions</strong></td>
                          <td><strong><?php echo $component_deduction_total ?></strong></td>
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <hr>
                  <div class="row">
                    <div class="col-md-6">
                      <h2>Net Pay</h2>
                      <h3>₹<?php echo number_format(($component_earning_total - $component_deduction_total), 2) ?></h3>
                    </div>
                  </div>
              </td>
            </tr>
            <!-- <tr>
              <td class="page-break" style="border: none;"></td>
            </tr> -->
            <tr>
              <td style="border: none;">
                <?php if (pg_num_rows($result_accrued_bonus) > 0) : ?>
                  <table>
                    <thead>
                      <tr>
                        <td colspan=4>
                          <h4>Bonus Information</h4>
                          <p>(The payout is subject to the organization's policy.)</p>
                        </td>
                      </tr>
                      <tr>
                        <th>Pay Month/Year</th>
                        <th>Monthly Bonus</th>
                        <th>Payout Bonus</th>
                        <th>Balance Bonus</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      // Initialize an array to store the total balance for each employee
                      $total_balance = [];

                      // Loop through the accrued bonus data
                      while ($row_accrued = pg_fetch_assoc($result_accrued_bonus)) :
                        $employee_id = $row_accrued['employeeid'];
                        $monthly_bonus_amount = $row_accrued['monthly_bonus_amount'];
                        $monthly_payout_bonus = $row_accrued['monthly_payout_bonus'];
                        $pay_month = $row_accrued['paymonth'];
                        $pay_year = $row_accrued['payyear'];

                        // Calculate balance bonus
                        if (!isset($total_balance[$employee_id])) :
                          $total_balance[$employee_id] = 0;
                        endif;
                        $total_balance[$employee_id] += $monthly_bonus_amount - $monthly_payout_bonus;

                        // Output the data in HTML table format
                        echo "<tr>";
                        echo "<td>" . date('M', mktime(0, 0, 0, $pay_month, 1)) . '-' . $pay_year . "</td>";
                        echo "<td>" . $monthly_bonus_amount . "</td>";
                        echo "<td>" . $monthly_payout_bonus . "</td>";
                        echo "<td>" . $total_balance[$employee_id] . "</td>";
                        echo "</tr>";
                      endwhile;
                      ?>

                    </tbody>
                  </table>
              </td>
            </tr>
          </tbody>
        <?php endif; ?>

      <?php } ?>
      <tfoot>
        <tr>
          <td colspan="2" style="border: none;">
            <div class="report-footer d-print-inline-flex" style="font-size: small" ;>
              <table style="width: 100%;">
                <tr>
                  <td style="width: 50%;">
                    <strong>Rina Shiksha Sahayak Foundation</strong><br>
                    1074/801, Jhapetapur, Backside of Municipality, West Midnapore, West Bengal 721301
                  </td>
                  <td style="text-align: right; width: 50%;">
                    Contact: 7980168159<br>
                    Email: info@rssi.in
                  </td>
                </tr>
                <tr>
                  <td colspan="2" style="text-align: right; border: none;">
                    Payslip generated on <?php echo date('d/m/Y h:i:s a', strtotime($payslip_issued_on)) ?>
                  </td>
                </tr>
              </table>
            </div>
          </td>
        </tr>
      </tfoot>
    <?php } else { ?>
      <!-- Onboarding not initiated -->
      <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="exampleModalLabel">Access Denied</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <?php
              if (empty($ref)) {
                $error_message = "No reference ID entered.";
              } else {
                if (pg_num_rows($result) == 0 && @$check_employeeid == null) {
                  $error_message = "No record found for the entered reference ID";
                } else if (pg_num_rows($result) == 0 && $check_employeeid != $user_check) {
                  $error_message = "You are trying to access data that does not belong to you. If you think this is a mistake, please contact RSSI support team.";
                }
              }
              if (isset($error_message)) {
                echo $error_message;
              } ?>
            </div>
          </div>
        </div>
      </div>
    <?php } ?>
  </div>
  <!-- Load Bootstrap JS -->
  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
  <script>
    window.onload = function() {
      var myModal = new bootstrap.Modal(document.getElementById('myModal'), {
        backdrop: 'static',
        keyboard: false
      });
      myModal.show();
    };
  </script>
</body>

</html>