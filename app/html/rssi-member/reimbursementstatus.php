<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}


@$status = $_POST['get_id'];

if ($role == 'Admin') {

  @$id = strtoupper($_POST['get_aid']);

  if ($id == null && ($status == 'ALL' || $status == null)) {
    $result = pg_query($con, "SELECT *, REPLACE (uploadeddocuments, 'view', 'preview') docp FROM claim 
    left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members) faculty ON claim.registrationid=faculty.associatenumber
    left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON claim.registrationid=student.student_id
    order by timestamp desc");
    $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim");
    $totalclaimedamount = pg_query($con, "SELECT SUM(totalbillamount) FROM claim WHERE claimstatus IS NULL OR claimstatus<>'Rejected'");
  } else if ($id == null && ($status != 'ALL' && $status != null)) {
    $result = pg_query($con, "SELECT *, REPLACE (uploadeddocuments, 'view', 'preview') docp FROM claim 
    left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members) faculty ON claim.registrationid=faculty.associatenumber
    left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON claim.registrationid=student.student_id
    WHERE year='$status' order by timestamp desc");
    $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim WHERE year='$status'");
    $totalclaimedamount = pg_query($con, "SELECT SUM(totalbillamount) FROM claim WHERE year='$status' AND (claimstatus IS NULL OR claimstatus<>'Rejected')");
  } else if ($id > 0 && ($status != 'ALL' && $status != null)) {
    $result = pg_query($con, "SELECT *, REPLACE (uploadeddocuments, 'view', 'preview') docp FROM claim 
    left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members) faculty ON claim.registrationid=faculty.associatenumber
    left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON claim.registrationid=student.student_id
    WHERE registrationid='$id' AND year='$status' order by timestamp desc");
    $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim WHERE registrationid='$id' AND year='$status'");
    $totalclaimedamount = pg_query($con, "SELECT SUM(totalbillamount) FROM claim WHERE registrationid='$id' AND year='$status' AND (claimstatus IS NULL OR claimstatus<>'Rejected')");
  } else if ($id > 0 && ($status == 'ALL' || $status == null)) {
    $result = pg_query($con, "SELECT *, REPLACE (uploadeddocuments, 'view', 'preview') docp FROM claim 
    left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members) faculty ON claim.registrationid=faculty.associatenumber
    left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON claim.registrationid=student.student_id
    WHERE registrationid='$id' order by timestamp desc");
    $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim WHERE registrationid='$id'");
    $totalclaimedamount = pg_query($con, "SELECT SUM(totalbillamount) FROM claim WHERE registrationid='$id' AND (claimstatus IS NULL OR claimstatus<>'Rejected')");
  }
}

if ($role != 'Admin' && $status != 'ALL') {

  $result = pg_query($con, "SELECT *, REPLACE (uploadeddocuments, 'view', 'preview') docp FROM claim 
  left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members WHERE associatenumber='$user_check') faculty ON claim.registrationid=faculty.associatenumber
  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student WHERE student_id='$user_check') student ON claim.registrationid=student.student_id
  WHERE registrationid='$user_check' AND year='$status' order by timestamp desc");
  $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim WHERE registrationid='$user_check' AND year='$status'");
  $totalclaimedamount = pg_query($con, "SELECT SUM(totalbillamount) FROM claim WHERE registrationid='$user_check' AND year='$status' AND (claimstatus IS NULL OR claimstatus<>'Rejected')");
}

if ($role != 'Admin' && $status == 'ALL') {

  $result = pg_query($con, "SELECT *, REPLACE (uploadeddocuments, 'view', 'preview') docp FROM claim 
  left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members WHERE associatenumber='$user_check') faculty ON claim.registrationid=faculty.associatenumber
  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student WHERE student_id='$user_check') student ON claim.registrationid=student.student_id
  WHERE registrationid='$user_check' order by timestamp desc");
  $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim WHERE registrationid='$user_check'");
  $totalclaimedamount = pg_query($con, "SELECT SUM(totalbillamount) FROM claim WHERE registrationid='$user_check'AND (claimstatus IS NULL OR claimstatus<>'Rejected')");
}

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
  <meta name="description" content="">
  <meta name="author" content="">
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Reimbursement Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
  <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
  <!-- Main css -->
  <link rel="stylesheet" href="/css/style.css">
  <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

  <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
  <!------ Include the above in your HEAD tag ---------->

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
      font-size: x-small;
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
  <section id="main-content">
    <section class="wrapper main-wrapper row">
      <div class="col-md-12">
        <div class="row">
          <div class="col" style="display: inline-block; width:50%;margin-left:1.5%; font-size:small">
            Record count:&nbsp;<?php echo sizeof($resultArr) ?>
            <br>Total Approved amount:&nbsp;<p class="label label-success"><?php echo ($resultArrr) ?></p>&nbsp;/&nbsp;<p class="label label-default"><?php echo ($resultArrrr) ?></p>
          </div>
          <div class="col" style="display: inline-block; width:47%; text-align:right">
            Home / <span class="noticea"><a href="reimbursement.php">Reimbursement</a></span> / Reimbursement Status<br><br>
            <form method="POST" action="export_function.php">
              <input type="hidden" value="reimb" name="export_type" />
              <input type="hidden" value="<?php echo $id ?>" name="id" />
              <input type="hidden" value="<?php echo $status ?>" name="status" />
              <input type="hidden" value="<?php echo $role ?>" name="role" />
              <input type="hidden" value="<?php echo $user_check ?>" name="user_check" />

              <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Export CSV"><i class="fa-regular fa-file-excel" style="font-size:large;"></i></button>
            </form>
          </div>
        </div>
        <section class="box" style="padding: 2%;">
          <form action="" method="POST">
            <div class="form-group" style="display: inline-block;">
              <div class="col2" style="display: inline-block;">
                <?php if ($role == 'Admin') { ?>
                  <input name="get_aid" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate number" value="<?php echo $id ?>">
                <?php } ?>
                <select name="get_id" id="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Select policy year" required>
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
                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
            </div>
          </form>
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
                
                <span class="noticea"><a href="javascript:void(0)" onclick="showpdf(\'' . $array['reimbid'] . '\')">' . $array['reimbid'] . '</a></span>                
                
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
                <?php echo '<td> <p class="label label-warning">' . $array['claimstatus'] . '</p>' ?>

              <?php } else if ($array['claimstatus'] == 'Approved' || $array['claimstatus'] == 'Claim settled') { ?>
                <?php echo '<td><p class="label label-success">' . $array['claimstatus'] . '</p>' ?>
              <?php    } else if ($array['claimstatus'] == 'Rejected' || $array['claimstatus'] == 'On hold') { ?>
                <?php echo '<td><p class="label label-danger">' . $array['claimstatus'] . '</p>' ?>
              <?php    } else { ?>
                <?php echo '<td><p class="label label-info">' . $array['claimstatus'] . '</p>' ?>
              <?php } ?>

              <?php echo


              '<td>

              <button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['reimbid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
              <i class="fa-regular fa-pen-to-square" style="font-size:14px;color:#777777" title="Show Details" display:inline;></i></button>

             &nbsp;&nbsp;' ?>
              <?php if ($role == "Admin") { ?>
                <?php if (($array['phone'] != null || $array['contact'] != null) && @$array['claimstatus'] != null) { ?>

                  <?php if ($array['claimstatus'] == "Claim settled") { ?>
                    <?php echo '
                  
                  <a href="https://api.whatsapp.com/send?phone=91' . $array['phone'] . $array['contact'] . '&text=Dear ' . $array['fullname'] . $array['studentname'] . ',%0A%0AYour claim number ' . $array['reimbid'] . ' against the policy issued by the organization has been settled at Rs.' . $array['approvedamount'] . ' as against the amount claimed for Rs.' . $array['totalbillamount'] . ' on ' . @date("d/m/Y", strtotime($array['transfereddate'])) . '.
                  %0A%0AThe amount has been credited to your account. It may take standard time for it to reflect in your account.%0A%0AYou can track the status of your claim in real-time from https://login.rssi.in/rssi-member/reimbursementstatus.php. For more information, please contact your HR or immediate supervisor.%0A%0A--RSSI%0A%0A**This is an automatically generated SMS" target="_blank"><i class="fa-brands fa-whatsapp" style="color:#444444;" title="Send SMS ' . $array['phone'] . $array['contact'] . '"></i></a>'
                    ?>
                  <?php } else if ($array['claimstatus'] == "Rejected") { ?>
                    <?php echo '
                  
                  <a href="https://api.whatsapp.com/send?phone=91' . $array['phone'] . $array['contact'] . '&text=Dear ' . $array['fullname'] . $array['studentname'] . ',%0A%0AThe claim submitted on ' . @date("d/m/Y g:i a", strtotime($array['timestamp'])) . ' (Claim No: ' . $array['reimbid'] . ') has been rejected for the reasons mentioned below.%0A%0ARemarks- ' . $array['mediremarks'] . '%0A%0AYou can track the status of your claim in real-time from https://login.rssi.in/rssi-member/reimbursementstatus.php. For more information, please contact your HR or immediate supervisor.%0A%0A--RSSI%0A%0A**This is an automatically generated SMS" target="_blank"><i class="fa-brands fa-whatsapp" style="color:#444444;" title="Send SMS ' . $array['phone'] . $array['contact'] . '"></i></a>'
                    ?>
                  <?php } ?>
                <?php } else { ?>
                  <?php echo '<i class="fa-brands fa-whatsapp" style="color:#A2A2A2;" title="Send SMS"></i>' ?>
                  <?php } ?>&nbsp;&nbsp;

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

                  <button style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" type="submit"><i class="fa-regular fa-envelope" style="color:#444444;" title="Send Email ' . @$array['email'] . @$array['emailaddress'] . '"></i></button>
                  </form>' ?>
                  <?php } else { ?>
                    <?php echo '<i class="fa-regular fa-envelope" style="color:#A2A2A2;" title="Send Email"></i>' ?>
                  <?php } ?>

                  <!--<?php echo '&nbsp;&nbsp;<form name="claimdelete_' . $array['reimbid'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="claimdelete">
                                <input type="hidden" name="claimdeleteid" id="claimdeleteid" type="text" value="' . $array['reimbid'] . '">
                                
                                <button type="submit" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['reimbid'] . '"><i class="fa-solid fa-xmark"></i></button> </form>
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
                        </table>';
                ?>
        </section>
      </div>
    </section>
  </section>

  <!-- Back top -->
  <script>
    $(document).ready(function() {
      $(window).scroll(function() {
        if ($(this).scrollTop() > 50) {
          $('#back-to-top').fadeIn();
        } else {
          $('#back-to-top').fadeOut();
        }
      });
      // scroll body to 0px on click
      $('#back-to-top').click(function() {
        $('body,html').animate({
          scrollTop: 0
        }, 400);
        return false;
      });
    });
  </script>
  <a id="back-to-top" href="#" class="go-top" role="button"><i class="fa fa-angle-up"></i></a>


  <!--------------- POP-UP BOX ------------
-------------------------------------->
  <style>
    .modal {
      display: none;
      /* Hidden by default */
      position: fixed;
      /* Stay in place */
      z-index: 100;
      /* Sit on top */
      padding-top: 100px;
      /* Location of the box */
      left: 0;
      top: 0;
      width: 100%;
      /* Full width */
      height: 100%;
      /* Full height */
      overflow: auto;
      /* Enable scroll if needed */
      background-color: rgb(0, 0, 0);
      /* Fallback color */
      background-color: rgba(0, 0, 0, 0.4);
      /* Black w/ opacity */
    }

    /* Modal Content */

    .modal-content {
      background-color: #fefefe;
      margin: auto;
      padding: 20px;
      border: 1px solid #888;
      width: 100vh;
    }

    @media (max-width:767px) {
      .modal-content {
        width: 50vh;
      }
    }

    /* The Close Button */

    .close {
      color: #aaaaaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      text-align: right;
    }

    .close:hover,
    .close:focus {
      color: #000;
      text-decoration: none;
      cursor: pointer;
    }
  </style>
  <div id="myModal" class="modal">

    <!-- Modal content -->
    <div class="modal-content">
      <span id="closedetails" class="close">&times;</span>

      <div style="width:100%; text-align:right">
        <p id="status1" class="label " style="display: inline !important;"><span class="reimbid"></span></p>
      </div>

      <?php if ($role != "Admin") { ?>
        <p style="font-size: small;">
          Transaction id: <span class="transactionid"></span><br><br>
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
            <select name="claimstatus" id="claimstatus" class="form-control" style="display: -webkit-inline-box; width:20vh; font-size: small;" required>
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
          <br>
          <button type="submit" id="claimupdate" class="btn btn-danger btn-sm " style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none"><i class="fa-solid fa-arrows-rotate"></i>&nbsp;&nbsp;Update</button>
        </form>
      <?php } ?>
      <p style="font-size:small; text-align: right; font-style: italic; color:#A2A2A2;">Updated by: <span class="reviewer_name"></span> (<span class="reviewer_id"></span>) on <span class="updatedon"></span>
    </div>

  </div>

  <div id="myModalpdf" class="modal">

    <!-- Modal content -->
    <div class="modal-content">
      <span id="closepdf" class="close">&times;</span>

      <div style="width:100%; text-align:right">
        <p id="status2" class="label " style="display: inline !important;"><span class="claimstatus"></span></p>
      </div>


      <p style="font-size: small;">
        Claim Number: <span class="reimbid"></span><br />
        <object id="docid" data="#" type="application/pdf" width="100%" height="450px"></object>
      </p>
    </div>

  </div>
  <script>
    var data = <?php echo json_encode($resultArr) ?>

    // Get the modal
    var modal = document.getElementById("myModal");

    var closedetails = document.getElementById("closedetails");

    function showDetails(id) {
      // console.log(modal)
      // console.log(modal.getElementsByClassName("data"))
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

      //class add 
      var status = document.getElementById("status1")
      if (mydata["claimstatus"] === "Claim settled") {
        status.classList.add("label-success")
        status.classList.remove("label-danger")
      } else if (mydata["claimstatus"] === "Rejected") {
        status.classList.remove("label-success")
        status.classList.add("label-danger")
      } else {
        status.classList.remove("label-success")
        status.classList.remove("label-danger")
        status.classList.add("label-default")
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
    // When the user clicks the button, open the modal 
    // When the user clicks on <span> (x), close the modal
    closedetails.onclick = function() {
      modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
      if (event.target == modal) {
        modal.style.display = "none";
      } else if (event.target == modal1) {
        modal1.style.display = "none";
      }
    }
  </script>

  <script>
    var data1 = <?php echo json_encode($resultArr) ?>

    // Get the modal
    var modal1 = document.getElementById("myModalpdf");
    var closepdf = document.getElementById("closepdf");

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
        statuss.classList.add("label-success")
        statuss.classList.remove("label-danger")
      } else if (mydata1["claimstatus"] === "Rejected") {
        statuss.classList.remove("label-success")
        statuss.classList.add("label-danger")
      } else {
        statuss.classList.remove("label-success")
        statuss.classList.remove("label-danger")
      }
      //class add end
      var docid = document.getElementById("docid")
      docid.data = mydata1["docp"]
    }
    closepdf.onclick = function() {
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

</body>

</html>