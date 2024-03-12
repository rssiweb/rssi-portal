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
@$ref = $_GET['ref'];

if ($role == 'Admin') {
  $result = pg_query($con, "SELECT * FROM payslip_entry LEFT JOIN rssimyaccount_members ON rssimyaccount_members.associatenumber = payslip_entry.employeeid WHERE payslip_entry_id = '$ref'");

  $result_component = pg_query($con, "SELECT * FROM payslip_component WHERE payslip_entry_id = '$ref'");
  $result_component_earning_total = pg_query($con, "SELECT SUM(amount) FROM payslip_component WHERE payslip_entry_id = '$ref' AND components = 'Earning'");
  $result_component_deduction_total = pg_query($con, "SELECT SUM(amount) FROM payslip_component WHERE payslip_entry_id = '$ref' AND components = 'Deduction'");
}

if ($role != 'Admin') {
  $result = pg_query($con, "SELECT * FROM payslip_entry LEFT JOIN rssimyaccount_members ON rssimyaccount_members.associatenumber = payslip_entry.employeeid WHERE payslip_entry_id = '$ref' AND employeeid = '$user_check'");

  $result_component = pg_query($con, "SELECT * FROM payslip_component WHERE payslip_entry_id = '$ref' AND payslip_entry_id IN (SELECT payslip_entry_id FROM payslip_entry WHERE employeeid = '$user_check')");

  $result_component_earning_total = pg_query($con, "SELECT SUM(amount) FROM payslip_component WHERE payslip_entry_id = '$ref' AND components = 'Earning' AND payslip_entry_id IN (SELECT payslip_entry_id FROM payslip_entry WHERE employeeid = '$user_check')");
  $result_component_deduction_total = pg_query($con, "SELECT SUM(amount) FROM payslip_component WHERE payslip_entry_id = '$ref' AND components = 'Deduction' AND payslip_entry_id IN (SELECT payslip_entry_id FROM payslip_entry WHERE employeeid = '$user_check')");

  // Execute the query to retrieve the employee ID associated with the reference value
  $result_check = pg_query($con, "SELECT employeeid FROM payslip_entry WHERE payslip_entry_id = '$ref'");
  $row = pg_fetch_assoc($result_check);
  @$check_employeeid = $row['employeeid'];
}

$resultArr = pg_fetch_all($result);
if (!$result) {
  echo "An error occurred.\n";
  exit;
}

$resultArr_result_component = pg_fetch_all($result_component);
$component_earning_total = pg_fetch_result($result_component_earning_total, 0, 0);
$component_deduction_total = pg_fetch_result($result_component_deduction_total, 0, 0);

if (!$result_component) {
  echo "An error occurred.\n";
  exit;
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

      .print-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: #f8f9fa;
        padding: 10px;
        font-size: 12px;
      }

      .print-header {
        display: flex;
      }

      .print-header .right-content {
        text-align: right;
        margin-left: auto;
      }
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
        <div class="print-header">
          <div class="right-content d-none d-print-inline-flex">
            <!-- Scan QR code to check authenticity -->
            <?php
            $a = 'https://login.rssi.in/rssi-member/payslip.php?ref=';
            $b = $array['payslip_entry_id'];
            $url = $a . $b;
            $url = urlencode($url);
            ?>
            <img class="qrimage" src="https://chart.googleapis.com/chart?chs=85x85&cht=qr&chl=<?php echo $url ?>" width="100px" />
          </div>
        </div>
        <h3>Payslip</h3>
        <div style="display: flex; justify-content: space-between;">
          <span><?php echo date('F', mktime(0, 0, 0, $array['paymonth'], 1)) ?> <?php echo $array['payyear'] ?></span>
          <span>Ref. number: <?php echo $ref ?></span>
        </div>
        <hr>
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
              <td>Bank Name: <?php echo $array['bankname'] ?><br>
                Acc No.: <?php echo $array['accountnumber'] ?><br>
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
                  </tr>
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
        </div>
      <?php } ?>
      <div class="print-footer d-none d-print-inline-flex">
        <table>
          <tr>
            <td>
              <strong>Rina Shiksha Sahayak Foundation (RSSI)</strong><br>
              1074/801, Jhapetapur, Backside of Municipality, West Midnapore, West Bengal 721301
            </td>
            <td style="text-align: right;">
              Contact: 7980168159<br>
              Email: info@rssi.in
            </td>
          </tr>
          <tr>
            <td style="text-align: right; border:0" colspan="2">
              Payslip generated on <?php echo date('Y-m-d H:i:s'); ?>
            </td>
          </tr>
        </table>
      </div>
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