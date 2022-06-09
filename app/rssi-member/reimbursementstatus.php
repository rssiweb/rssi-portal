<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
  header("Location: index.php");
}
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}
?>
<?php
include("member_data.php");
include("database.php");
@$status = $_POST['get_id'];

if ($_SESSION['role'] == 'Admin') {

  @$id = $_POST['get_aid'];

  if ($id == null && $status == 'ALL') {
    $result = pg_query($con, "SELECT * FROM claim order by id desc");
    $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim");
  } else if ($id == null && $status != 'ALL') {
    $result = pg_query($con, "SELECT * FROM claim WHERE year='$status' order by id desc");
    $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim WHERE year='$status'");
  } else if ($id > 0 && $status != 'ALL') {
    $result = pg_query($con, "SELECT * FROM claim WHERE registrationid='$id' AND year='$status' order by id desc");
    $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim WHERE registrationid='$id' AND year='$status'");
  } else if ($id > 0 && $status == 'ALL') {
    $result = pg_query($con, "SELECT * FROM claim WHERE registrationid='$id' order by id desc");
    $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim WHERE year='$status'");
  } else {
    $result = pg_query($con, "SELECT * FROM claim order by id desc");
    $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim WHERE year='$status'");
  }
}

if ($_SESSION['role'] != 'Admin') {

  $result = pg_query($con, "SELECT * FROM claim WHERE registrationid='$user_check' AND year='$status' order by id desc");
  $totalapprovedamount = pg_query($con, "SELECT SUM(approvedamount) FROM claim WHERE registrationid='$user_check' AND year='$status'");
}

if (!$result) {
  echo "An error occurred.\n";
  exit;
}

$resultArr = pg_fetch_all($result);
$resultArrr = pg_fetch_result($totalapprovedamount, 0, 0);
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
  <style>
    <?php include '../css/style.css'; ?>
  </style>
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
      policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
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
            Record count:&nbsp;<?php echo sizeof($resultArr) ?><br>Total Approved amount:&nbsp;<p class="label label-default"><?php echo ($resultArrr) ?></p>
          </div>
          <!-- <div class="col" style="display: inline-block; width:47%; text-align:right; font-size:small">
            Home / Reimbursement Status
          </div> -->
        </div>
        <section class="box" style="padding: 2%;">
          <form action="" method="POST">
            <div class="form-group" style="display: inline-block;">
              <div class="col2" style="display: inline-block;">
              <?php if ($_SESSION['role'] == 'Admin') { ?>
                <input name="get_aid" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate number" value="<?php echo $id ?>">
                <?php } ?>
                <select name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Select policy year" required>
                  <?php if ($status == null) { ?>
                    <option value="" hidden selected>Select policy year</option>
                  <?php
                  } else { ?>
                    <option hidden selected><?php echo $status ?></option>
                  <?php }
                  ?>
                  <option>2022-2023</option>
                  <option>2021-2022</option>
                  <option>ALL</option>
                </select>
              </div>
            </div>
            <div class="col2 left" style="display: inline-block;">
              <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
            </div>
          </form>
          <?php echo '
                        <table class="table">
                            <thead style="font-size: 12px;">
                                <tr>
                                <th scope="col">Claim Number</th>
                                <th scope="col">Registered On</th>    
                                <th scope="col">ID/F Name</th>
                                <th scope="col">Purpose</th>
                                <th scope="col">Claimed Amount (&#8377;)</th>
                                <th scope="col">Amount Transfered (&#8377;)</th>
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
                echo '<td><span class="noticea"><a href="' . $array['uploadeddocuments'] . '" target="_blank">' . $array['reimbid'] . '</a></span></td>'
                ?>
                <?php } else { ?><?php
                                  echo '<td>' . $array['reimbid'] . '</td>' ?>
              <?php } ?>
              <?php
              echo '<td>' . substr($array['timestamp'], 0, 10) . '</td>
                        <td>' . $array['registrationid'] . '/' . strtok($array['name'], ' ') . '</td>   
                        <td>' . $array['selectclaimheadfromthelistbelow'] . '</td>
                        <td>' . $array['totalbillamount'] . '</td>' ?>

              <?php if ($array['claimstatus'] != 'claim settled') { ?>
                <?php echo '<td></td>' ?> <?php } else { ?>

                <?php echo '<td>' . $array['approvedamount'] . '</td>' ?>
              <?php  } ?>
              <?php echo '
                        <td>' . $array['transfereddate'] . '</td>'
              ?>
              <?php if ($array['claimstatus'] == 'review' || $array['claimstatus'] == 'in progress' || $array['claimstatus'] == 'withdrawn') { ?>
                <?php echo '<td> <p class="label label-warning">' . $array['claimstatus'] . '</p>' ?>

              <?php } else if ($array['claimstatus'] == 'approved' || $array['claimstatus'] == 'claim settled') { ?>
                <?php echo '<td><p class="label label-success">' . $array['claimstatus'] . '</p>' ?>
              <?php    } else if ($array['claimstatus'] == 'rejected' || $array['claimstatus'] == 'on hold') { ?>
                <?php echo '<td><p class="label label-danger">' . $array['claimstatus'] . '</p>' ?>
              <?php    } else { ?>
                <?php echo '<td><p class="label label-info">' . $array['claimstatus'] . '</p>' ?>
              <?php } ?>

              <?php echo


              '<td><a href="javascript:void(0)" onclick="showDetails(\'' . $array['reimbid'] . '\')"><button type="button" id="btn" class="btn btn-info btn-sm" style="outline: none"><i class="fa-solid fa-eye"></i>&nbsp;Details</button></a></td></tr>';
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
      <span class="close">&times;</span>
      <p style="font-size: small;">
        Claim Number: <span class="reimbid"></span><br />
        Transaction Reference Number: <span class="transactionid"></span><br /><br>
        Bank Account Details:<br><span class="bankname"></span><br />Account Number: <span class="accountnumber"></span><br />Account Holder Name: <span class="accountholdername"></span><br />IFSC Code: <span class="ifsccode"></span><br>
        <br>Remarks: <span class="mediremarks"></span><br>
        <br>Closed on: <span class="closedon"></span>
      </p>
    </div>

  </div>
  <script>
    var data = <?php echo json_encode($resultArr) ?>

    // Get the modal
    var modal = document.getElementById("myModal");
    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

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
    }
    // When the user clicks the button, open the modal 

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
      modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    }
  </script>



</body>

</html>