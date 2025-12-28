<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}
validation();

// Determine the academic year
if (date('m') == 1 || date('m') == 2 || date('m') == 3) {
  $academic_year = (date('Y') - 1) . '-' . date('Y');
} else {
  $academic_year = date('Y') . '-' . (date('Y') + 1);
}
$currentAcademicYear = $academic_year;

// Retrieve the filters based on the role and filter status
if ($role == 'Admin' && $filterstatus == 'Active') {
  $status = isset($_POST['get_id']) ? $_POST['get_id'] : $currentAcademicYear;
  $id = isset($_POST['get_aid']) ? strtoupper($_POST['get_aid']) : null;
} else {
  $status = isset($_POST['get_id']) ? $_POST['get_id'] : $currentAcademicYear;
  $id = $associatenumber;
}

$reimbid = isset($_POST['reimbid']) ? $_POST['reimbid'] : null;

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

// Execute the query
$result = pg_query($con, $query);

// Queries for total approved and claimed amounts
$totalapprovedamountQuery = "SELECT SUM(approvedamount) FROM claim";
$totalclaimedamountQuery = "SELECT SUM(totalbillamount) FROM claim WHERE (claimstatus IS NULL OR claimstatus <> 'Rejected')";

if (!empty($conditionString)) {
  $totalapprovedamountQuery .= " WHERE $conditionString";
  $totalclaimedamountQuery .= " AND $conditionString";
}

$totalapprovedamount = pg_query($con, $totalapprovedamountQuery);
$totalclaimedamount = pg_query($con, $totalclaimedamountQuery);

if (!$result) {
  echo "An error occurred.\n";
  exit;
}

$resultArr = pg_fetch_all($result);
$resultArrr = pg_fetch_result($totalapprovedamount, 0, 0);
$resultArrrr = pg_fetch_result($totalclaimedamount, 0, 0);
?>
<!DOCTYPE html>
<html lang="en">

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

  <title>Reimbursement Status</title>

  <!-- Favicons -->
  <link href="../img/favicon.ico" rel="icon">
  <!-- Vendor CSS Files -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Template Main CSS File -->
  <link href="../assets_new/css/style.css" rel="stylesheet">

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
    @media (min-width:767px) {
      .left {
        margin-left: 2%;
      }
    }

    #btn {
      border: none !important;
    }

    .checkbox {
      padding: 0;
      margin: 0;
      vertical-align: bottom;
      position: relative;
      top: 0px;
      overflow: hidden;
    }

    .x-btn:focus,
    .button:focus,
    [type="submit"]:focus {
      outline: none;
    }

    #passwordHelpBlock {
      display: block;
    }

    .input-help {
      vertical-align: top;
      display: inline-block;
    }

    #hidden-panel,
    #hidden-panel_ack {
      display: none;
    }

    @media (max-width:767px) {

      #cw {
        width: 100% !important;
      }

    }

    #cw {
      width: 20%;
    }

    /* New styles for row selection */
    .table-hover tbody tr {
      cursor: pointer;
    }

    .table-hover tbody tr.selected {
      background-color: rgba(13, 110, 253, 0.1) !important;
    }

    .select-all-container {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }

    .selected-count {
      margin-left: 10px;
      font-size: 0.9rem;
      color: #6c757d;
    }
  </style>
  <!-- CSS Library Files -->
  <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
  <!-- JavaScript Library Files -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
  <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
  <?php include 'inactive_session_expire_check.php'; ?>
  <?php include 'header.php'; ?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Reimbursement Status</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="home.php">Home</a></li>
          <li class="breadcrumb-item"><a href="#">Claims and Advances</a></li>
          <li class="breadcrumb-item"><a href="reimbursement.php">Reimbursement</a></li>
          <li class="breadcrumb-item active">Reimbursement Status</li>
        </ol>
      </nav>
    </div><!-- End Page Title -->

    <section class="section dashboard">
      <div class="row">

        <!-- Reports -->
        <div class="col-12">
          <div class="card">

            <div class="card-body">
              <br>
              <div class="row">
                <div class="col">
                  Record count: <?php echo sizeof($resultArr) ?><br>
                  Total Approved amount: <p class="badge bg-success"><?php echo ($resultArrr) ?></p> / <p class="badge bg-secondary"><?php echo ($resultArrrr) ?></p>
                </div>

                <div class="d-flex justify-content-end">
                  <form method="POST" action="export_function.php" style="display:inline-block; margin-right: 10px;">
                    <input type="hidden" value="reimb" name="export_type" />
                    <input type="hidden" value="<?php echo $id ?>" name="id" />
                    <input type="hidden" value="<?php echo $status ?>" name="status" />
                    <input type="hidden" value="<?php echo $reimbid ?>" name="reimbid" />

                    <button type="submit" id="export" name="export" style="display:inline-box; outline:none; background:none; border:none;" title="Export CSV">
                      <i class="bi bi-file-earmark-excel" style="font-size: large;"></i>
                    </button>
                  </form>

                  <form method="POST" action="export_function.php" style="display:inline-block;">
                    <input type="hidden" value="reimb_payment" name="export_type" />
                    <input type="hidden" value="<?php echo $id ?>" name="id" />
                    <input type="hidden" value="<?php echo $status ?>" name="status" />
                    <input type="hidden" value="<?php echo $reimbid ?>" name="reimbid" />

                    <button type="submit" id="export" name="export" style="display:inline-box; outline:none; background:none; border:none;" title="Download Payment File">
                      <i class="bi bi-file-earmark-arrow-down" style="font-size: large;"></i>
                    </button>
                  </form>
                </div>


                <form action="" method="POST" id="filterForm">
                  <div class="form-group" style="display: inline-block;">
                    <div class="col2" style="display: inline-block;">
                      <input name="reimbid" id="reimbidFilter" class="form-control" style="width:max-content; display:inline-block" placeholder="Enter Claim Numbers separated by commas" value="<?php echo $reimbid ?>">
                      <?php if ($role == 'Admin' && $filterstatus == 'Active') { ?>
                        <input name="get_aid" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate number" value="<?php echo $id ?>">
                      <?php } ?>
                      <select name="get_id" id="get_id" class="form-select" style="width:max-content; display:inline-block" placeholder="Select policy year" required>
                        <?php if ($status == null) { ?>
                          <option hidden selected>Select policy year</option>
                        <?php
                        } else { ?>
                          <option hidden selected><?php echo $status ?></option>
                        <?php }
                        ?>
                        <option>ALL</option>
                      </select>
                    </div>
                  </div>
                  <div class="col2 left" style="display: inline-block;">
                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                      <i class="bi bi-search"></i>&nbsp;Search</button>
                  </div>
                </form>
                <br>
                <script>
                  <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
                    var currentYear = new Date().getFullYear() - 1;
                  <?php } else { ?>
                    var currentYear = new Date().getFullYear();
                  <?php } ?>
                  for (var i = 0; i < 5; i++) {
                    var next = currentYear + 1;
                    var year = currentYear + '-' + next;
                    //next.toString().slice(-2)
                    $('#get_id').append(new Option(year, year));
                    currentYear--;
                  }
                </script>

                <!-- Select All Controls -->
                <div class="select-all-container">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                    <label class="form-check-label" for="selectAllCheckbox">
                      Select all
                    </label>
                  </div>
                  <span class="selected-count" id="selectedCount">0 claims selected</span>
                </div>

                <div class="table-responsive">
                  <table class="table table-hover" id="table-id">
                    <thead>
                      <tr>
                        <th scope="col" style="width: 30px;">
                          <!-- Checkbox column header -->
                        </th>
                        <th scope="col">Claim Number</th>
                        <th scope="col">Registered On</th>
                        <th scope="col">ID/F Name</th>
                        <th id="cw" scope="col">Purpose</th>
                        <th scope="col">Claimed Amount (&#8377;)</th>
                        <th scope="col">Amount Approved (&#8377;)</th>
                        <th scope="col">Transfered Date</th>
                        <th scope="col">Claim Status</th>
                        <th scope="col"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if ($resultArr != null): ?>
                        <?php foreach ($resultArr as $array): ?>
                          <tr data-reimbid="<?php echo $array['reimbid']; ?>">
                            <td>
                              <input type="checkbox" class="row-checkbox form-check-input" name="selected_claims[]" value="<?php echo $array['reimbid']; ?>">
                            </td>
                            <td>
                              <?php if ($array['uploadeddocuments'] != null): ?>
                                <a href="javascript:void(0)" onclick="showpdf('<?php echo $array['reimbid']; ?>')">
                                  <?php echo $array['reimbid']; ?>
                                </a>
                              <?php else: ?>
                                <?php echo $array['reimbid']; ?>
                              <?php endif; ?>
                            </td>
                            <td><?php echo @date("d/m/Y g:i a", strtotime($array['timestamp'])); ?></td>
                            <td><?php echo $array['registrationid'] . '/' . strtok($array['fullname'] . $array['studentname'], ' '); ?></td>
                            <td><?php echo $array['selectclaimheadfromthelistbelow'] . '<br>' . $array['claimheaddetails']; ?></td>
                            <td><?php echo $array['totalbillamount']; ?></td>
                            <td><?php echo $array['approvedamount']; ?></td>
                            <td>
                              <?php if ($array['transfereddate'] != null): ?>
                                <?php echo @date("d/m/Y", strtotime($array['transfereddate'])); ?>
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if (in_array($array['claimstatus'], ['Review', 'In progress', 'Withdrawn'])): ?>
                                <p class="badge bg-warning"><?php echo $array['claimstatus']; ?></p>
                              <?php elseif (in_array($array['claimstatus'], ['Approved', 'Claim settled'])): ?>
                                <p class="badge bg-success"><?php echo $array['claimstatus']; ?></p>
                              <?php elseif (in_array($array['claimstatus'], ['Rejected', 'On hold'])): ?>
                                <p class="badge bg-danger"><?php echo $array['claimstatus']; ?></p>
                              <?php else: ?>
                                <p class="badge bg-info"><?php echo $array['claimstatus']; ?></p>
                              <?php endif; ?>
                            </td>
                            <td>
                              <div class="dropdown">
                                <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="actionMenu<?php echo $array['reimbid']; ?>"
                                  data-bs-toggle="dropdown" aria-expanded="false" style="padding: 4px 8px;">
                                  <i class="bi bi-three-dots-vertical" style="font-size:14px;"></i>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="actionMenu<?php echo $array['reimbid']; ?>">
                                  <li>
                                    <button type="button" class="dropdown-item" onclick="showDetails('<?php echo $array['reimbid']; ?>')">
                                      <i class="bi bi-box-arrow-up-right"></i> Details
                                    </button>
                                  </li>
                                  <?php if ($role == "Admin"): ?>
                                    <?php if (($array['phone'] != null || $array['contact'] != null) && $array['claimstatus'] != null): ?>
                                      <?php if ($array['claimstatus'] == "Claim settled"): ?>
                                        <li>
                                          <a class="dropdown-item"
                                            href="https://api.whatsapp.com/send?phone=91<?php echo $array['phone'] . $array['contact']; ?>&text=Dear <?php echo $array['fullname'] . $array['studentname']; ?>,%0A%0AYour claim number <?php echo $array['reimbid']; ?> against the policy issued by the organization has been settled at Rs.<?php echo $array['approvedamount']; ?> as against the amount claimed for Rs.<?php echo $array['totalbillamount']; ?> on <?php echo @date("d/m/Y", strtotime($array['transfereddate'])); ?>.%0A%0AThe amount has been credited to your account. It may take standard time for it to be reflected in your account.%0A%0AYou can track the status of your claim in real-time from https://login.rssi.in/rssi-member/reimbursementstatus.php. For more information, please contact your HR or immediate supervisor.%0A%0A--RSSI%0A%0A**This is an automatically generated SMS"
                                            target="_blank">
                                            <i class="bi bi-whatsapp"></i> Send SMS
                                          </a>
                                        </li>
                                      <?php elseif ($array['claimstatus'] == "Rejected"): ?>
                                        <li>
                                          <a class="dropdown-item"
                                            href="https://api.whatsapp.com/send?phone=91<?php echo $array['phone'] . $array['contact']; ?>&text=Dear <?php echo $array['fullname'] . $array['studentname']; ?>,%0A%0AThe claim submitted on <?php echo @date("d/m/Y g:i a", strtotime($array['timestamp'])); ?> (Claim No: <?php echo $array['reimbid']; ?>) has been rejected for the reasons mentioned below.%0A%0ARemarks- <?php echo $array['mediremarks']; ?>%0A%0AYou can track the status of your claim in real-time from https://login.rssi.in/rssi-member/reimbursementstatus.php. For more information, please contact your HR or immediate supervisor.%0A%0A--RSSI%0A%0A**This is an automatically generated SMS"
                                            target="_blank">
                                            <i class="bi bi-whatsapp"></i> Send SMS
                                          </a>
                                        </li>
                                      <?php endif; ?>
                                    <?php else: ?>
                                      <li>
                                        <span class="dropdown-item disabled"><i class="bi bi-whatsapp text-muted"></i> Send SMS</span>
                                      </li>
                                    <?php endif; ?>

                                    <?php if (($array['email'] != null || $array['emailaddress'] != null) && $array['claimstatus'] != null): ?>
                                      <li>
                                        <form action="#" name="email-form-<?php echo $array['reimbid']; ?>"
                                          id="email-form-<?php echo $array['reimbid']; ?>" method="POST" style="display:inline;">
                                          <input type="hidden" name="template"
                                            value="<?php echo ($array['claimstatus'] == 'Claim settled' ? 'claimapprove' : 'claimreject'); ?>">
                                          <input type="hidden" name="data[reimbid]"
                                            value="<?php echo $array['reimbid']; ?>">
                                          <input type="hidden" name="data[applicantname]"
                                            value="<?php echo $array['fullname'] . $array['studentname']; ?>">
                                          <input type="hidden" name="data[approvedamount]"
                                            value="<?php echo $array['approvedamount']; ?>">
                                          <input type="hidden" name="data[totalbillamount]"
                                            value="<?php echo $array['totalbillamount']; ?>">
                                          <input type="hidden" name="data[timestamp]"
                                            value="<?php echo @date("d/m/Y g:i a", strtotime($array['timestamp'])); ?>">
                                          <input type="hidden" name="data[mediremarks]"
                                            value="<?php echo $array['mediremarks']; ?>">
                                          <input type="hidden" name="data[transfereddate]"
                                            value="<?php echo @date("d/m/Y", strtotime($array['transfereddate'])); ?>">
                                          <input type="hidden" name="data[claimstatus]"
                                            value="<?php echo @strtoupper($array['claimstatus']); ?>">
                                          <input type="hidden" name="email"
                                            value="<?php echo @$array['email'] . @$array['emailaddress']; ?>">
                                          <button type="submit" class="dropdown-item">
                                            <i class="bi bi-envelope-at"></i> Send Email
                                          </button>
                                        </form>
                                      </li>
                                    <?php else: ?>
                                      <li>
                                        <span class="dropdown-item disabled"><i class="bi bi-envelope-at text-muted"></i> Send Email</span>
                                      </li>
                                    <?php endif; ?>
                                  <?php endif; ?>
                                </ul>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php elseif ($status == null): ?>
                        <tr>
                          <td colspan="10">Please select policy year.</td>
                        </tr>
                      <?php else: ?>
                        <tr>
                          <td colspan="10">No record found for <?php echo $status; ?></td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>

                <script>
                  // Function to update the filter field with selected claim numbers
                  function updateReimbidFilter() {
                    const selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
                    const reimbidValues = Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
                    document.getElementById('reimbidFilter').value = reimbidValues.join(',');

                    // Update selected count
                    document.getElementById('selectedCount').textContent = `${reimbidValues.length} claims selected`;
                  }

                  // Function to handle row click
                  function setupRowSelection() {
                    const table = document.getElementById('table-id');
                    const rows = table.querySelectorAll('tbody tr');
                    const selectAllCheckbox = document.getElementById('selectAllCheckbox');

                    // Add click event to each row
                    rows.forEach(row => {
                      // Skip rows that don't have a reimbid (empty rows)
                      if (!row.dataset.reimbid) return;

                      row.addEventListener('click', (e) => {
                        // Don't trigger if clicking on a link, button, or the checkbox itself
                        if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.tagName === 'I' || e.target.classList.contains('dropdown-toggle')) {
                          return;
                        }

                        // Find the checkbox in this row
                        const checkbox = row.querySelector('.row-checkbox');
                        if (checkbox) {
                          checkbox.checked = !checkbox.checked;
                          row.classList.toggle('selected', checkbox.checked);
                          updateReimbidFilter();
                          updateSelectAllCheckbox();
                        }
                      });
                    });

                    // Add change event to each checkbox
                    const checkboxes = document.querySelectorAll('.row-checkbox');
                    checkboxes.forEach(checkbox => {
                      checkbox.addEventListener('change', function() {
                        const row = this.closest('tr');
                        row.classList.toggle('selected', this.checked);
                        updateReimbidFilter();
                        updateSelectAllCheckbox();
                      });
                    });

                    // Select all checkbox functionality
                    selectAllCheckbox.addEventListener('change', function() {
                      const isChecked = this.checked;
                      checkboxes.forEach(checkbox => {
                        checkbox.checked = isChecked;
                        const row = checkbox.closest('tr');
                        if (row) {
                          row.classList.toggle('selected', isChecked);
                        }
                      });
                      updateReimbidFilter();
                    });

                    // Function to update the select all checkbox state
                    function updateSelectAllCheckbox() {
                      const checkboxes = document.querySelectorAll('.row-checkbox');
                      const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;

                      if (checkedCount === 0) {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = false;
                      } else if (checkedCount === checkboxes.length) {
                        selectAllCheckbox.checked = true;
                        selectAllCheckbox.indeterminate = false;
                      } else {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = true;
                      }
                    }
                  }

                  // Initialize row selection when document is loaded
                  document.addEventListener('DOMContentLoaded', function() {
                    setupRowSelection();
                  });
                </script>

                <!-- Rest of your modal code remains the same -->
                <!--------------- POP-UP BOX ------------>
                <!-- No custom style needed; Bootstrap modal background is handled by default -->

                <!-- Reimbursement Details Modal -->
                <div class="modal fade" id="myModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Reimbursement Details</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">

                        <div style="width:100%; text-align:right">
                          <p id="status_details" class="badge"></p>
                        </div>

                        <?php if ($role != "Admin") { ?>
                          <p>
                            Transaction id: <span class="transactionid"></span><br>
                            HR Remarks: <span class="mediremarks"></span><br>
                          </p>
                        <?php } ?>

                        <?php if ($role == "Admin") { ?>
                          <form id="claimreviewform" name="claimreviewform" action="#" method="POST">
                            <input type="hidden" name="form-type" value="claimreviewform" readonly>
                            <input type="hidden" name="reviewer_id" id="reviewer_id" value="<?php echo $associatenumber ?>" readonly>
                            <input type="hidden" name="reviewer_name" id="reviewer_name" value="<?php echo $fullname ?>" readonly>
                            <input type="hidden" name="reimbid" id="reimbid" readonly>

                            <div class="mb-3">
                              <select name="claimstatus" id="claimstatus" class="form-select" required>
                                <option value="" disabled selected>Select Status</option>
                                <option value="Approved">Approved</option>
                                <option value="Claim settled">Claim settled</option>
                                <option value="Under review">Under review</option>
                                <option value="Rejected">Rejected</option>
                              </select>
                              <small class="form-text text-muted">Claim status <span style="color:red">*</span></small>
                            </div>

                            <div class="mb-3">
                              <input type="number" name="approvedamount" id="approvedamount" class="form-control" placeholder="Amount" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" required>
                              <small class="form-text text-muted">Approved amount <span style="color:red">*</span></small>
                            </div>

                            <div class="mb-3">
                              <input type="text" name="transactionid" id="transactionid" class="form-control" placeholder="Transaction id" required>
                              <small class="form-text text-muted">Transaction id <span style="color:red">*</span></small>
                            </div>

                            <div class="mb-3">
                              <input type="date" name="transfereddate" id="transfereddate" class="form-control" required>
                              <small class="form-text text-muted">Transfer Date <span style="color:red">*</span></small>
                            </div>

                            <div class="mb-3">
                              <textarea name="mediremarks" id="mediremarks" class="form-control" placeholder="HR remarks"></textarea>
                              <small class="form-text text-muted">HR remarks</small>
                            </div>

                            <div class="mb-3">
                              <input type="date" name="closedon" id="closedon" class="form-control">
                              <small class="form-text text-muted">Closed on</small>
                            </div>

                            <button type="submit" id="claimupdate" class="btn btn-danger btn-sm">Update</button>
                          </form>
                        <?php } ?>

                        <p style="font-size:small; text-align:right; font-style:italic; color:#A2A2A2;">
                          Updated by: <span class="reviewer_name"></span> (<span class="reviewer_id"></span>) on <span class="updatedon"></span>
                        </p>

                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- PDF Modal -->
                <div class="modal fade" id="myModalpdf" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h1 class="modal-title fs-5" id="pdfModalLabel">Document</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">

                        <div style="width:100%; text-align:right">
                          <p id="status2" class="badge"><span class="claimstatus"></span></p>
                        </div>

                        Claim Number: <span class="reimbid"></span><br>
                        <div id="pdfContainer">
                          <object id="docid" data="" type="application/pdf" width="100%" height="500px">
                            PDF not supported.
                          </object>
                        </div>

                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                      </div>
                    </div>
                  </div>
                </div>

                <script>
                  var data = <?php echo json_encode($resultArr) ?>;

                  function showDetails(id) {
                    var mydata = data.find(item => item.reimbid == id);
                    if (!mydata) return;

                    // Populate spans and inputs
                    Object.keys(mydata).forEach(key => {
                      var elems = document.getElementsByClassName(key);
                      if (elems.length > 0) {
                        elems[0].innerHTML = mydata[key];
                      }
                    });

                    document.getElementById("status_details").textContent = mydata["reimbid"];
                    var statusDetails = document.getElementById("status_details");
                    statusDetails.classList.remove("bg-success", "bg-danger", "bg-secondary");

                    if (mydata["claimstatus"] === "Claim settled") {
                      statusDetails.classList.add("bg-success");
                    } else if (mydata["claimstatus"] === "Rejected") {
                      statusDetails.classList.add("bg-danger");
                    } else {
                      statusDetails.classList.add("bg-secondary");
                    }

                    document.getElementById("reimbid").value = mydata["reimbid"] || "";
                    document.getElementById("claimstatus").value = mydata["claimstatus"] || "";
                    document.getElementById("mediremarks").value = mydata["mediremarks"] || "";
                    document.getElementById("approvedamount").value = mydata["approvedamount"] || "";
                    document.getElementById("transactionid").value = mydata["transactionid"] || "";
                    document.getElementById("transfereddate").value = mydata["transfereddate"] || "";
                    document.getElementById("closedon").value = mydata["closedon"] || "";

                    function updateFieldStatus() {
                      const claimStatus = document.getElementById('claimstatus').value;
                      const approved = document.getElementById("approvedamount");
                      const transaction = document.getElementById("transactionid");
                      const transfered = document.getElementById("transfereddate");

                      if (claimStatus === "" || claimStatus === "Rejected" || claimStatus === "Under review") {
                        approved.disabled = true;
                        transaction.disabled = true;
                        transfered.disabled = true;
                      } else if (claimStatus === "Approved") {
                        approved.disabled = false;
                        transaction.disabled = true;
                        transfered.disabled = true;
                      } else if (claimStatus === "Claim settled") {
                        approved.disabled = false;
                        transaction.disabled = false;
                        transfered.disabled = false;
                      }
                    }

                    document.getElementById('claimstatus').addEventListener('change', updateFieldStatus);
                    updateFieldStatus();

                    const updateButton = document.getElementById("claimupdate");
                    if (mydata["claimstatus"] == 'Claim settled' || mydata["claimstatus"] == 'Rejected') {
                      updateButton.disabled = true;
                    } else {
                      updateButton.disabled = false;
                    }

                    var modalEl = document.getElementById('myModal');
                    var modal = new bootstrap.Modal(modalEl);
                    modal.show();
                  }

                  function showpdf(id1) {
                    var mydata = data.find(item => item.reimbid == id1);
                    if (!mydata) return;

                    Object.keys(mydata).forEach(key => {
                      var elems = document.getElementsByClassName(key);
                      if (elems.length > 0) {
                        elems[0].innerHTML = mydata[key];
                      }
                    });

                    var statusElem = document.getElementById("status2");
                    statusElem.classList.remove("bg-success", "bg-danger");

                    if (mydata["claimstatus"] === "Claim settled") {
                      statusElem.classList.add("bg-success");
                    } else if (mydata["claimstatus"] === "Rejected") {
                      statusElem.classList.add("bg-danger");
                    }

                    var pdfUrl = mydata["docp"] || "#";
                    pdfUrl += (pdfUrl.includes('?') ? '&' : '?') + 't=' + new Date().getTime();

                    var container = document.getElementById("pdfContainer");
                    container.innerHTML = '';

                    var newObj = document.createElement("object");
                    newObj.id = "docid";
                    newObj.data = pdfUrl;
                    newObj.type = "application/pdf";
                    newObj.width = "100%";
                    newObj.height = "500px";
                    newObj.innerHTML = "PDF not supported.";

                    container.appendChild(newObj);

                    var modalEl = document.getElementById('myModalpdf');
                    var modal = new bootstrap.Modal(modalEl);
                    modal.show();
                  }
                </script>

                <script>
                  var data = <?php echo json_encode($resultArr) ?>;
                  const scriptURL = 'payment-api.php'

                  document.getElementById('claimreviewform').addEventListener('submit', e => {
                    e.preventDefault();

                    fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(e.target)
                      })
                      .then(response => response.text())
                      .then(data => {
                        alert(data.trim() === 'success' ? "Record has been successfully updated." : "Update failed. Please try again.");
                        location.reload();
                      })
                      .catch(error => console.error('Error!', error.message));
                  });

                  document.querySelectorAll('form[id^="email-form-"]').forEach(form => {
                    form.addEventListener('submit', e => {
                      e.preventDefault();
                      fetch('mailer.php', {
                          method: 'POST',
                          body: new FormData(form)
                        })
                        .then(response => alert("Email has been sent."))
                        .catch(error => console.error('Error!', error.message));
                    });
                  });
                </script>

              </div>
            </div>
          </div><!-- End Reports -->
        </div>
    </section>

  </main><!-- End #main -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

  <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
  <script src="../assets_new/js/text-refiner.js"></script>
  <script>
    $(document).ready(function() {
      // Check if resultArr is empty
      <?php if (!empty($resultArr)) : ?>
        // Initialize DataTables only if resultArr is not empty
        $('#table-id').DataTable({
          paging: false,
          "order": [], // Disable initial sorting
          "columnDefs": [{
              "orderable": false,
              "targets": 0
            } // Disable sorting on checkbox column
          ]
          // other options...
        });
      <?php endif; ?>
    });
  </script>
</body>

</html>