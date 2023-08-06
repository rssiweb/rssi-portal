<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}

if ($role == 'Admin') {
  $status = isset($_POST['get_id']) ? $_POST['get_id'] : null;
  $id = isset($_POST['get_aid']) ? strtoupper($_POST['get_aid']) : null;
} else {
  $status = isset($_POST['get_id']) ? $_POST['get_id'] : null;
  $id = $user_check;
}

$queryConditions = [];

if ($id !== null && $id !== "") {
  $queryConditions[] = "claim.registrationid = '$id'";
}

if ($status !== null && $status !== "" && $status !== 'ALL') {
  $queryConditions[] = "claim.year = '$status'";
}

$conditionString = implode(' AND ', $queryConditions);

$query = "SELECT *, REPLACE(uploadeddocuments, 'view', 'preview') docp FROM claim 
    LEFT JOIN rssimyaccount_members AS faculty ON claim.registrationid = faculty.associatenumber
    LEFT JOIN rssimyprofile_student AS student ON claim.registrationid = student.student_id";

if (!empty($conditionString)) {
  $query .= " WHERE $conditionString";
}

$query .= " ORDER BY claim.timestamp DESC";

$result = pg_query($con, $query);

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


<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Reimbursement Status</title>

  <!-- Favicons -->
  <link href="../img/favicon.ico" rel="icon">
  <!-- Vendor CSS Files -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
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
  </style>

</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>

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

                <div class="col text-end">
                  <form method="POST" action="export_function.php" style="display:inline-block;">
                    <input type="hidden" value="reimb" name="export_type" />
                    <input type="hidden" value="<?php echo $id ?>" name="id" />
                    <input type="hidden" value="<?php echo $status ?>" name="status" />
                    <input type="hidden" value="<?php echo $role ?>" name="role" />
                    <input type="hidden" value="<?php echo $user_check ?>" name="user_check" />

                    <button type="submit" id="export" name="export" style="display:inline-box; outline:none; background:none; border:none;" title="Export CSV">
                      <i class="bi bi-file-earmark-excel" style="font-size: large;"></i>
                    </button>
                  </form>
                </div>
              </div>

              <form action="" method="POST">
                <div class="form-group" style="display: inline-block;">
                  <div class="col2" style="display: inline-block;">
                    <?php if ($role == 'Admin') { ?>
                      <input name="get_aid" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate number" value="<?php echo $id ?>">
                    <?php } ?>
                    <select name="get_id" id="get_id" class="form-select" style="width:max-content; display:inline-block" placeholder="Select policy year" required>
                      <?php if ($status == null) { ?>
                        <option value="" hidden selected>Select policy year</option>
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
              <?php echo '
                <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
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
                            </thead>' ?>
              <?php if ($resultArr != null) {
                echo '<tbody>';
                foreach ($resultArr as $array) {
                  echo '<tr>' ?>
                  <?php if ($array['uploadeddocuments'] != null) { ?>
                    <?php
                    echo '<td>
                
                <a href="javascript:void(0)" onclick="showpdf(\'' . $array['reimbid'] . '\')">' . $array['reimbid'] . '</a>
                </td>'
                    ?>
                    <?php } else { ?><?php
                                      echo '<td>' . $array['reimbid'] . '</td>' ?>
                  <?php } ?>
                  <?php
                  echo '<td>' . @date("d/m/Y g:i a", strtotime($array['timestamp'])) . '</td>
                        <td>' . $array['registrationid'] . '/' . strtok($array['fullname'] . $array['studentname'], ' ') . '</td>   
                        <td>' . $array['selectclaimheadfromthelistbelow'] . '<br>' . $array['claimheaddetails'] . '</td>
                        <td>' . $array['totalbillamount'] . '</td>
                        <td>' . $array['approvedamount'] . '</td>
                        <td>' ?>
                  <?php if ($array['transfereddate'] != null) { ?>
                    <?php echo @date("d/m/Y", strtotime($array['transfereddate'])) ?>
                  <?php } ?>
                  <?php echo '</td>' ?>

                  <?php if ($array['claimstatus'] == 'Review' || $array['claimstatus'] == 'In progress' || $array['claimstatus'] == 'Withdrawn') { ?>
                    <?php echo '<td> <p class="badge bg-warning">' . $array['claimstatus'] . '</p>' ?>

                  <?php } else if ($array['claimstatus'] == 'Approved' || $array['claimstatus'] == 'Claim settled') { ?>
                    <?php echo '<td><p class="badge bg-success">' . $array['claimstatus'] . '</p>' ?>
                  <?php    } else if ($array['claimstatus'] == 'Rejected' || $array['claimstatus'] == 'On hold') { ?>
                    <?php echo '<td><p class="badge bg-danger">' . $array['claimstatus'] . '</p>' ?>
                  <?php    } else { ?>
                    <?php echo '<td><p class="badge bg-info">' . $array['claimstatus'] . '</p>' ?>
                  <?php } ?>

                  <?php echo


                  '<td>

              <button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['reimbid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
              <i class="bi bi-box-arrow-up-right" style="font-size:14px;color:#777777" title="Show Details" display:inline;></i></button>

             ' ?>
                  <?php if ($role == "Admin") { ?>
                    <?php if (($array['phone'] != null || $array['contact'] != null) && @$array['claimstatus'] != null) { ?>

                      <?php if ($array['claimstatus'] == "Claim settled") { ?>
                        <?php echo '
                  
                  <a href="https://api.whatsapp.com/send?phone=91' . $array['phone'] . $array['contact'] . '&text=Dear ' . $array['fullname'] . $array['studentname'] . ',%0A%0AYour claim number ' . $array['reimbid'] . ' against the policy issued by the organization has been settled at Rs.' . $array['approvedamount'] . ' as against the amount claimed for Rs.' . $array['totalbillamount'] . ' on ' . @date("d/m/Y", strtotime($array['transfereddate'])) . '.
                  %0A%0AThe amount has been credited to your account. It may take standard time for it to reflect in your account.%0A%0AYou can track the status of your claim in real-time from https://login.rssi.in/rssi-member/reimbursementstatus.php. For more information, please contact your HR or immediate supervisor.%0A%0A--RSSI%0A%0A**This is an automatically generated SMS" target="_blank"><i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS ' . $array['phone'] . $array['contact'] . '"></i></a>'
                        ?>
                      <?php } else if ($array['claimstatus'] == "Rejected") { ?>
                        <?php echo '
                  
                  <a href="https://api.whatsapp.com/send?phone=91' . $array['phone'] . $array['contact'] . '&text=Dear ' . $array['fullname'] . $array['studentname'] . ',%0A%0AThe claim submitted on ' . @date("d/m/Y g:i a", strtotime($array['timestamp'])) . ' (Claim No: ' . $array['reimbid'] . ') has been rejected for the reasons mentioned below.%0A%0ARemarks- ' . $array['mediremarks'] . '%0A%0AYou can track the status of your claim in real-time from https://login.rssi.in/rssi-member/reimbursementstatus.php. For more information, please contact your HR or immediate supervisor.%0A%0A--RSSI%0A%0A**This is an automatically generated SMS" target="_blank"><i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS ' . $array['phone'] . $array['contact'] . '"></i></a>'
                        ?>
                      <?php } ?>
                    <?php } else { ?>
                      <?php echo '<i class="bi bi-whatsapp" style="color:#A2A2A2;" title="Send SMS"></i>' ?>
                    <?php } ?>

                    <?php if ((@$array['email'] != null || @$array['emailaddress'] != null) && @$array['claimstatus'] != null) { ?>
                      <?php echo '<form  action="#" name="email-form-' . $array['reimbid'] . '" method="POST" style="display: -webkit-inline-box;" >' ?>

                      <?php if (@$array['claimstatus'] == 'Claim settled') { ?>
                        <input type="hidden" name="template" type="text" value="claimapprove">
                      <?php } else if (@$array['claimstatus'] == 'Rejected') { ?>
                        <input type="hidden" name="template" type="text" value="claimreject">
                      <?php } ?>

                      <?php echo '<input type="hidden" name="data[reimbid]" type="text" value="' . $array['reimbid'] . '">
                  <input type="hidden" name="data[applicantname]" type="text" value="' . $array['fullname'] . $array['studentname'] . '">
                  <input type="hidden" name="data[approvedamount]" type="text" value="' . $array['approvedamount'] . '">
                  <input type="hidden" name="data[totalbillamount]" type="text" value="' . $array['totalbillamount'] . '">
                  <input type="hidden" name="data[timestamp]" type="text" value="' . @date("d/m/Y g:i a", strtotime($array['timestamp'])) . '">
                  <input type="hidden" name="data[mediremarks]" type="text" value="' . $array['mediremarks'] . '">
                  <input type="hidden" name="data[transfereddate]" type="text" value="' . @date("d/m/Y", strtotime($array['transfereddate'])) . '">
                  <input type="hidden" name="data[claimstatus]" type="text" value="' . @strtoupper($array['claimstatus']) . '">
                  <input type="hidden" name="email" type="text" value="' . @$array['email'] . @$array['emailaddress'] . '">

                  <button style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" type="submit"><i class="bi bi-envelope-at" style="color:#444444;" title="Send Email ' . @$array['email'] . @$array['emailaddress'] . '"></i></button>
                  </form>' ?>
                    <?php } else { ?>
                      <?php echo '<i class="bi bi-envelope-at" style="color:#A2A2A2;" title="Send Email"></i>' ?>
                    <?php } ?>

                    <!--<?php echo '<form name="claimdelete_' . $array['reimbid'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="claimdelete">
                                <input type="hidden" name="claimdeleteid" id="claimdeleteid" type="text" value="' . $array['reimbid'] . '">
                                
                                <button type="submit" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['reimbid'] . '"><i class="bi bi-x-lg"></i></button> </form>
                                </td>' ?>-->
                    <?php }
                }
              } else if ($status == null) {
                echo '<tr>
                                <td colspan="5">Please select policy year.</td>
                            </tr>';
              } else {
                echo '<tr>
                            <td colspan="5">No record found for' ?>&nbsp;<?php echo $status ?>
                  <?php echo '</td>
                        </tr>';
                }
                echo '</tbody>
                        </table>
                        </div>';
                  ?>
                  <!--------------- POP-UP BOX ------------
-------------------------------------->
                  <style>
                    .modal {
                      background-color: rgba(0, 0, 0, 0.4);
                      /* Black w/ opacity */
                    }
                  </style>
                  <div class="modal" id="myModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">Reimbursement Details</h1>
                          <button type="button" id="closedetails-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">

                          <div style="width:100%; text-align:right">
                            <p id="status_details" class="badge" style="display: inline;"></p>
                          </div>

                          <?php if ($role != "Admin") { ?>
                            <p>
                              Transaction id: <span class="transactionid"></span><br>
                              HR Remarks: <span class="mediremarks"></span><br>
                            </p>
                          <?php } ?>
                          <?php if ($role == "Admin") { ?>
                            <form id="claimreviewform" name="claimreviewform" action="#" method="POST">
                              <input type="hidden" class="form-control" name="form-type" type="text" value="claimreviewform" readonly>
                              <input type="hidden" class="form-control" name="reviewer_id" id="reviewer_id" type="text" value="<?php echo $associatenumber ?>" readonly>
                              <input type="hidden" class="form-control" name="reviewer_name" id="reviewer_name" type="text" value="<?php echo $fullname ?>" readonly>
                              <input type="hidden" class="form-control" name="reimbid" id="reimbid" type="text" value="" readonly>

                              <span class="input-help">
                                <select name="claimstatus" id="claimstatus" class="form-select" style="display: -webkit-inline-box; width:20vh;" required>
                                  <option value="" disabled selected hidden>Status</option>
                                  <option value="Approved">Approved</option>
                                  <option value="Claim settled">Claim settled</option>
                                  <option value="Under review">Under review</option>
                                  <option value="Rejected">Rejected</option>
                                </select>
                                <small id="passwordHelpBlock" class="form-text text-muted">Claim status<span style="color:red">*</span></small>
                              </span>

                              <span class="input-help">
                                <input type="number" class="form-control" name="approvedamount" id="approvedamount" placeholder="Amount" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" value="" required>
                                <small id="passwordHelpBlock" class="form-text text-muted">Approved amount<span style="color:red">*</span></small>
                              </span>
                              <span class="input-help">
                                <input type="text" name="transactionid" id="transactionid" class="form-control" placeholder="Transaction id" value="" required>
                                <small id="passwordHelpBlock" class="form-text text-muted">Transaction id<span style="color:red">*</span></small>
                              </span>
                              <span class="input-help">
                                <input type="date" class="form-control" name="transfereddate" id="transfereddate" value="" required>
                                <small id="passwordHelpBlock" class="form-text text-muted">Transfer Date<span style="color:red">*</span></small>
                              </span>

                              <span class="input-help">
                                <textarea type="text" name="mediremarks" id="mediremarks" class="form-control" placeholder="HR remarks" value=""></textarea>
                                <small id="passwordHelpBlock" class="form-text text-muted">HR remarks</small>
                              </span>
                              <span class="input-help">
                                <input type="date" class="form-control" name="closedon" id="closedon" value="">
                                <small id="passwordHelpBlock" class="form-text text-muted">Closed on</small>
                              </span>

                              <button type="submit" id="claimupdate" class="btn btn-danger btn-sm" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none">Update</button>
                            </form>
                          <?php } ?>
                          <p style="font-size:small; text-align: right; font-style: italic; color:#A2A2A2;">Updated by: <span class="reviewer_name"></span> (<span class="reviewer_id"></span>) on <span class="updatedon"></span>
                          <div class="modal-footer">
                            <button type="button" id="closedetails-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <script>
                    var data = <?php echo json_encode($resultArr) ?>

                    // Get the modal
                    var modal = document.getElementById("myModal");
                    var closedetails = [
                      document.getElementById("closedetails-header"),
                      document.getElementById("closedetails-footer")
                    ];

                    function showDetails(id) {
                      var mydata = undefined
                      data.forEach(item => {
                        if (item["reimbid"] == id) {
                          mydata = item;
                        }
                      })

                      var keys = Object.keys(mydata)
                      keys.forEach(key => {
                        var span = modal.getElementsByClassName(key)
                        if (span.length > 0)
                          span[0].innerHTML = mydata[key];
                      })
                      modal.style.display = "block";

                      // Update status_details content with the reimbid value
                      var statusDetailsElement = document.getElementById("status_details");
                      if (statusDetailsElement) {
                        statusDetailsElement.textContent = mydata["reimbid"];
                      }

                      //class add 
                      var status_details = document.getElementById("status_details")
                      if (mydata["claimstatus"] === "Claim settled") {
                        status_details.classList.add("bg-success")
                        status_details.classList.remove("bg-danger")
                      } else if (mydata["claimstatus"] === "Rejected") {
                        status_details.classList.remove("bg-success")
                        status_details.classList.add("bg-danger")
                      } else {
                        status_details.classList.remove("bg-success")
                        status_details.classList.remove("bg-danger")
                        status_details.classList.add("bg-secondary")
                      }
                      //class add end

                      var profile = document.getElementById("reimbid")
                      profile.value = mydata["reimbid"]
                      if (mydata["claimstatus"] !== null) {
                        profile = document.getElementById("claimstatus")
                        profile.value = mydata["claimstatus"]
                      } else {
                        profile = document.getElementById("claimstatus")
                        profile.value = ""
                      }
                      if (mydata["mediremarks"] !== null) {
                        profile = document.getElementById("mediremarks")
                        profile.value = mydata["mediremarks"]
                      } else {
                        profile = document.getElementById("mediremarks")
                        profile.value = ""
                      }
                      if (mydata["approvedamount"] !== null) {
                        profile = document.getElementById("approvedamount")
                        profile.value = mydata["approvedamount"]
                      } else {
                        profile = document.getElementById("approvedamount")
                        profile.value = ""
                      }
                      if (mydata["transactionid"] !== null) {
                        profile = document.getElementById("transactionid")
                        profile.value = mydata["transactionid"]
                      } else {
                        profile = document.getElementById("transactionid")
                        profile.value = ""
                      }

                      if (mydata["transfereddate"] !== null) {
                        profile = document.getElementById("transfereddate")
                        profile.value = mydata["transfereddate"]
                      } else {
                        profile = document.getElementById("transfereddate")
                        profile.value = ""
                      }
                      if (mydata["closedon"] !== null) {
                        profile = document.getElementById("closedon")
                        profile.value = mydata["closedon"]
                      } else {
                        profile = document.getElementById("closedon")
                        profile.value = ""
                      }


                      if (document.getElementById('claimstatus').value == "" || document.getElementById('claimstatus').value == "Rejected" || document.getElementById('claimstatus').value == "Under review") {

                        document.getElementById("approvedamount").disabled = true;
                        document.getElementById("transactionid").disabled = true;
                        document.getElementById("transfereddate").disabled = true;
                      } else {

                        document.getElementById("approvedamount").disabled = false;
                        document.getElementById("transactionid").disabled = false;
                        document.getElementById("transfereddate").disabled = false;
                      }
                      const randvar = document.getElementById('claimstatus');

                      randvar.addEventListener('change', (event) => {
                        if (document.getElementById('claimstatus').value == "Approved" || document.getElementById('claimstatus').value == "Claim settled") {
                          document.getElementById("approvedamount").disabled = false;
                          document.getElementById("transactionid").disabled = false;
                          document.getElementById("transfereddate").disabled = false;
                        } else {
                          document.getElementById("approvedamount").disabled = true;
                          document.getElementById("transactionid").disabled = true;
                          document.getElementById("transfereddate").disabled = true;
                        }
                      })

                      if (mydata["claimstatus"] == 'Claim settled' || mydata["claimstatus"] == 'Rejected') {
                        document.getElementById("claimupdate").disabled = true;
                      } else {
                        document.getElementById("claimupdate").disabled = false;
                      }
                    }
                    closedetails.forEach(function(element) {
                      element.addEventListener("click", closeModal);
                    });

                    function closeModal() {
                      var modal1 = document.getElementById("myModal");
                      modal1.style.display = "none";
                    }
                  </script>

                  <div class="modal" id="myModalpdf" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title fs-5" id="exampleModalLabel">Document</h1>
                          <button type="button" id="closepdf-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <div style="width:100%; text-align:right">
                            <p id="status2" class="badge" style="display: inline !important;"><span class="claimstatus"></span></p>
                          </div>

                          Claim Number: <span class="reimbid"></span><br>
                          <object id="docid" data="#" type="application/pdf" width="100%" height="450px"></object>
                        </div>
                        <div class="modal-footer">
                          <button type="button" id="closepdf-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <script>
                    var data1 = <?php echo json_encode($resultArr) ?>

                    // Get the modal
                    var modal1 = document.getElementById("myModalpdf");
                    var closepdf = [
                      document.getElementById("closepdf-header"),
                      document.getElementById("closepdf-footer")
                    ];

                    function showpdf(id1) {
                      var mydata1 = undefined
                      data1.forEach(item1 => {
                        if (item1["reimbid"] == id1) {
                          mydata1 = item1;
                        }
                      })
                      var keys1 = Object.keys(mydata1)
                      keys1.forEach(key => {
                        var span1 = modal1.getElementsByClassName(key)
                        if (span1.length > 0)
                          span1[0].innerHTML = mydata1[key];
                      })
                      modal1.style.display = "block";

                      //class add 
                      var statuss = document.getElementById("status2")
                      if (mydata1["claimstatus"] === "Claim settled") {
                        statuss.classList.add("bg-success")
                        statuss.classList.remove("bg-danger")
                      } else if (mydata1["claimstatus"] === "Rejected") {
                        statuss.classList.remove("bg-success")
                        statuss.classList.add("bg-danger")
                      } else {
                        statuss.classList.remove("bg-success")
                        statuss.classList.remove("bg-danger")
                      }
                      //class add end
                      var docid = document.getElementById("docid")
                      docid.data = mydata1["docp"]
                    }
                    //close model using either cross or close button
                    closepdf.forEach(function(element) {
                      element.addEventListener("click", closeModal);
                    });

                    function closeModal() {
                      var modal1 = document.getElementById("myModalpdf");
                      modal1.style.display = "none";
                    }
                  </script>
                  <script>
                    var data = <?php echo json_encode($resultArr) ?>;
                    const scriptURL = 'payment-api.php'

                    function validateForm() {
                      if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {

                        data.forEach(item => {
                          const form = document.forms['claimdelete_' + item.reimbid]
                          form.addEventListener('submit', e => {
                            e.preventDefault()
                            fetch(scriptURL, {
                                method: 'POST',
                                body: new FormData(document.forms['claimdelete_' + item.reimbid])
                              })
                              .then(response =>
                                alert("Record has been deleted.") +
                                location.reload()
                              )
                              .catch(error => console.error('Error!', error.message))
                          })

                          console.log(item)
                        })
                      } else {
                        alert("Record has NOT been deleted.");
                        return false;
                      }
                    }

                    const form = document.getElementById('claimreviewform')
                    form.addEventListener('submit', e => {
                      e.preventDefault()
                      fetch(scriptURL, {
                          method: 'POST',
                          body: new FormData(document.getElementById('claimreviewform'))
                        })
                        .then(response =>
                          alert("Record has been updated.") +
                          location.reload()
                        )
                        .catch(error => console.error('Error!', error.message))
                    })

                    data.forEach(item => {
                      const formId = 'email-form-' + item.reimbid
                      const form = document.forms[formId]
                      form.addEventListener('submit', e => {
                        e.preventDefault()
                        fetch('mailer.php', {
                            method: 'POST',
                            body: new FormData(document.forms[formId])
                          })
                          .then(response =>
                            alert("Email has been sent.")
                          )
                          .catch(error => console.error('Error!', error.message))
                      })
                    })
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

</body>

</html>