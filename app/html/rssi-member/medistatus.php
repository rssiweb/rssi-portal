<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}
validation();

@$status = $_POST['get_id'];

if ($role == 'Admin') {
  @$id = $_POST['get_aid'];


  if ($id == null && $status == 'ALL') {
    $result = pg_query($con, "SELECT * FROM medimate order by id desc");
  } else if ($id == null && $status != 'ALL') {
    $result = pg_query($con, "SELECT * FROM medimate WHERE year='$status' order by id desc");
  } else if ($id > 0 && $status != 'ALL') {
    $result = pg_query($con, "SELECT * FROM medimate WHERE registrationid='$id' AND year='$status' order by id desc");
  } else if ($id > 0 && $status == 'ALL') {
    $result = pg_query($con, "SELECT * FROM medimate WHERE registrationid='$id' order by id desc");
  } else {
    $result = pg_query($con, "SELECT * FROM medimate order by id desc");
  }
}
if ($role != 'Admin') {
  $result = pg_query($con, "SELECT * FROM medimate WHERE registrationid='$user_check' AND year='$status' order by id desc");
}
if (!$result) {
  echo "An error occurred.\n";
  exit;
}

$resultArr = pg_fetch_all($result);
?>


<!DOCTYPE html>
<html lang="en">

<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
  <meta name="description" content="">
  <meta name="author" content="">
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Medistatus Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
  <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
  <!-- Main css -->
  <link rel="stylesheet" href="/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
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
  </style>

</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
  <?php include 'inactive_session_expire_check.php'; ?>
  <?php include 'header.php'; ?>
  <section id="main-content">
    <section class="wrapper main-wrapper row">
      <div class="col-md-12">
        <div class="row">
          <div class="col" style="display: inline-block; width:50%;margin-left:1.5%">
            Record count:&nbsp;<?php echo sizeof($resultArr) ?>
          </div>
          <div class="col" style="display: inline-block; width:47%; text-align:right">
            Home / <span class="noticea"><a href="medimate.php">Medimate</a></span> / Medimate Status<br><br>
          </div>
        </div>
        
          <form action="" method="POST">
            <div class="form-group" style="display: inline-block;">
              <div class="col2" style="display: inline-block;">
                <?php if ($role == 'Admin') { ?>
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
                  <option>2022</option>
                  <option>ALL</option>
                </select>
              </div>
            </div>
            <div class="col2 left" style="display: inline-block;">
              <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                <i class="bi bi-search"></i>&nbsp;Search</button>
            </div>
          </form>
          <?php echo '
                    <table class="table">
                        <thead>
                            <tr>
                            <th scope="col">Claim Number</th>
                            <th scope="col">Registered On</th>    
                            <th scope="col">ID/F Name</th>    
                                <th scope="col">Beneficiary</th>
                                <th scope="col">Account Number</th>
                                <th scope="col">Claimed Amount (&#8377;)</th>
                        <th scope="col">Amount Transfered (&#8377;)</th>
                        <th scope="col">Transaction Reference Number</th>
                        <th scope="col">Transfered Date</th>
                        <th scope="col">Claim Status</th>
                        <th scope="col">Closed on</th>
                        <th scope="col">Remarks</th>
                            </tr>
                        </thead>' ?>
          <?php if ($resultArr != null) {
            echo '<tbody>';
            foreach ($resultArr as $array) {
              echo '
                                <tr>' ?>

              <?php if ($array['uploadeddocuments'] != null) { ?>
                <?php
                echo '<td><span class="noticea"><a href="' . $array['uploadeddocuments'] . '" target="_blank">' . $array['claimid'] . '</a></span></td>'
                ?>
                <?php    } else { ?><?php
                                    echo '<td>' . $array['claimid'] . '</td>' ?>
              <?php } ?>
              <?php
              echo '

                                <td>' . substr($array['timestamp'], 0, 10) . '</td>
                                <td>' . $array['registrationid'] . '<br>' . strtok($array['name'], ' ') . '</td>   
                                    
                                    <td>' . $array['selectbeneficiary'] . '</td>
                                    <td>' . $array['accountnumber'] . '<br>' . $array['bankname'] . '/' . $array['ifsccode'] . '</td>
                                    <td>' . $array['totalbillamount'] . '</td>
                                    <td>' . $array['approvedamount'] . '</td>
                                    <td>' . $array['transactionid'] . '</td>
                                    <td>' . $array['transfereddate'] . '</td>'
              ?>
              <?php if ($array['claimstatus'] == 'review' || $array['claimstatus'] == 'in progress' || $array['claimstatus'] == 'withdrawn') { ?>
                <?php echo '<td> <p class="badge label-warning">' . $array['claimstatus'] . '</p>' ?>

              <?php } else if ($array['claimstatus'] == 'approved' || $array['claimstatus'] == 'claim settled') { ?>
                <?php echo '<td><p class="badge label-success">' . $array['claimstatus'] . '</p>' ?>
              <?php    } else if ($array['claimstatus'] == 'rejected' || $array['claimstatus'] == 'on hold') { ?>
                <?php echo '<td><p class="badge label-danger">' . $array['claimstatus'] . '</p>' ?>
              <?php    } else { ?>
                <?php echo '<td><p class="badge label-info">' . $array['claimstatus'] . '</p>' ?>
              <?php } ?>


              <?php echo
              '</td><td>' . $array['closedon'] . '</td>
                                    <td>' . $array['mediremarks'] . '</td>
                                    </tr>';
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
      </div>
      </div>
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
</body>

</html>
