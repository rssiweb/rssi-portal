<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");


if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}

validation();

@$id = $_POST['get_aid'];
@$status = $_POST['get_id'];

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
$resultArrr = pg_fetch_result($totaldonatedamount, 0, 0);
?>


<!doctype html>
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
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Old_Donation (till June 23)</title>

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
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
<?php include 'inactive_session_expire_check.php'; ?>
  <?php include 'header.php'; ?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Donation</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="home.php">Home</a></li>
          <li class="breadcrumb-item"><a href="#">Work</a></li>
          <li class="breadcrumb-item active">Donation</li>
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
                  Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                  <br>Total donated amount:&nbsp;<p class="badge bg-secondary"><?php echo $resultArrr ?></p>
                </div>
                <div class="col text-end">
                  <a href="donationinfo_admin.php">
                    << New Donation data</a>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center position-absolute top-5 end-0 p-3">
                <form method="POST" action="export_function.php">
                  <input type="hidden" value="donation_old" name="export_type" />
                  <input type="hidden" value="<?php echo $id ?>" name="invoice" />
                  <input type="hidden" value="<?php echo $status ?>" name="fyear" />

                  <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Export CSV">
                    <i class="bi bi-file-earmark-excel" style="font-size:large;"></i>
                  </button>
                </form>
              </div>
              <br>
              <form action="" method="POST">
                <div class="form-group" style="display: inline-block;">
                  <div class="col2" style="display: inline-block;">
                    <input name="get_aid" class="form-control" style="width:max-content; display:inline-block" placeholder="Invoice number" value="<?php echo $id ?>">
                    <select name="get_id" id="get_id" class="form-select" style="width:max-content;display:inline-block" required>
                      <?php if ($status == null) { ?>
                        <option value="" disabled selected hidden>Select Year</option>
                      <?php
                      } else { ?>
                        <option hidden selected><?php echo $status ?></option>
                      <?php }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="col2 left" style="display: inline-block;">
                  <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                    <i class="bi bi-search"></i>&nbsp;Search</button>
                </div>
              </form>
              <?php echo '
          <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Name</th>    
                            <th scope="col">Contact</th>
                                <th scope="col">Transaction id</th>
                                <th scope="col">Amount</th>
                                <th scope="col">National Identifier/Number</th>
                        <th scope="col">Mode of payment</th>
                        <th scope="col">Project</th>
                        <th scope="col">Invoice</th>
                        <th scope="col">Status</th>
                        <th scope="col">By</th>
                            </tr>
                        </thead>' ?>
              <?php if ($resultArr != null) {
                echo '<tbody>';
                foreach ($resultArr as $array) {
                  echo '
                                <tr>' ?>
                  <?php
                  echo '

                                <td>' . date("d/m/Y", strtotime($array['timestamp'])) . '</td>
                                <td>' . $array['firstname'] . '&nbsp;' . $array['lastname'] . '</td>   
                                    <td>' . $array['mobilenumber'] . '<br>' . $array['emailaddress'] . '</td>
                                    <td>' . $array['transactionid'] . '</td>
                                    <td>' . $array['currencyofthedonatedamount'] . '&nbsp;' . $array['donatedamount'] . '</td>
                                    <td>' . $array['uitype'] . '/' . $array['uinumber'] . '</td>
                                    <td>' . $array['modeofpayment'] . '</td>
                                    <td>' . $array['youwantustospendyourdonationfor'] . '</td>' ?>


                  <?php if ($array['profile'] != null) { ?>
                    <?php
                    echo '<td><span class="noticea"><a href="' . $array['profile'] . '" target="_blank">' . $array['invoice'] . '</a></span></td>'
                    ?>
                    <?php    } else { ?><?php
                                        echo '<td>' . $array['invoice'] . '</td>' ?>
                  <?php } ?>


                  <?php if ($array['approvedby'] != '--' && $array['approvedby'] != 'rejected') { ?>
                    <?php echo '<td> <p class="badge label-success">accepted</p>' ?>

                  <?php } else if ($array['approvedby'] == 'rejected') { ?>
                    <?php echo '<td><p class="badge label-danger">' . $array['approvedby'] . '</p>' ?>
                  <?php    } else { ?>
                    <?php echo '<td><p class="badge label-info">on hold</p>' ?>
                  <?php } ?>

                  <?php echo '</td>' ?>

                  <?php if ($array['approvedby'] != 'rejected') { ?>
                    <?php
                    echo '<td>' . $array['approvedby'] . '</td>'
                    ?>
                    <?php    } else { ?><?php
                                        echo '<td></td>' ?>
                  <?php } ?>


                  <?php echo '</tr>';
                }
              } else if ($status == null) {
                echo '<tr>
                            <td colspan="5">Please select invoice no.</td>
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

                <script>
                  <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
                    var currentYear = new Date().getFullYear() - 1;
                  <?php } else { ?>
                    var currentYear = new Date().getFullYear();
                  <?php } ?>

                  for (var i = 0; i < 5; i++) {
                    var next = currentYear + 1;
                    var status = currentYear + '-' + next;
                    //next.toString().slice(-2) 
                    $('#get_id').append(new Option(status, status));
                    currentYear--;
                  }
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