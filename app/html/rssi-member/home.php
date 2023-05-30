<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

  echo '<script type="text/javascript">';
  echo 'window.location.href = "defaultpasswordreset.php";';
  echo '</script>';
}


$view_users_query = "select * from ipfsubmission WHERE memberid2='$user_check'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{

  $timestamp = $row[0];
  $memberid2 = $row[1];
  $membername2 = $row[2];
  $ipf = $row[3];
  $ipfinitiate = $row[4];
  $status2 = $row[5];
  $respondedon = $row[6];
  $ipfstatus = $row[7];
  $closedon = $row[8];
  $id = $row[9]
?>
<?php } ?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Class details</title>

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
</head>

<body>

  <?php include 'header.php'; ?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Class details</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="home.php">Home</a></li>
          <li class="breadcrumb-item active">Class details</li>
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
              <div class="container">
                <div class="row">
                  <div class="col-lg-6">
                    <div class="card mb-4">
                      <div class="card-body">
                        <h5 class="card-title">Class Allotment</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <span class="fs-5"><?php echo $class ?></span>
                          <?php if ($gm) : ?>
                            <a class="btn btn-outline-primary" href="<?php echo $gm ?>" target="_blank">
                              Open Class
                            </a>
                          <?php endif; ?>
                        </div>
                        <p class="card-text">
                          <strong>Class URL:</strong>
                          <span class="noticea">
                            <?php if ($gm) : ?>
                              <a href="<?php echo $gm ?>" target="_blank"><?php echo substr($gm, -12) ?></a>
                            <?php else : ?>
                              Not available
                            <?php endif; ?>
                          </span>
                        </p>
                        <p class="card-text">
                          <strong>Class Attendance:</strong>
                          <span class="noticea">
                            <a href="https://docs.google.com/spreadsheets/d/1ufn8vcA5tcpoVvbTgGBO9NsXmiYgjmz54Qqg_L2GZxI/edit#gid=311270786" target="_blank">Attendance sheet</a>
                          </span>
                          <?php if (@$attd_pending != null) : ?>
                            <span class="badge bg-warning text-dark">Pending: <?php echo $attd_pending ?></span>
                          <?php endif; ?>
                        </p>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="card">
                      <div class="card-body">
                        <h5 class="card-title">Quick Links</h5>
                        <div class="list-group">
                          <a href="https://drive.google.com/drive/u/0/folders/14FVzPdcCP-w1Oy22Xwrexn7_XWSFqTaI" class="list-group-item list-group-item-action" target="_blank">
                            <i class="bi bi-calendar-week me-2"></i> Class Schedule
                          </a>
                          <a href="https://ncert.nic.in/textbook.php" class="list-group-item list-group-item-action" target="_blank">
                            <i class="bi bi-book me-2"></i> NCERT Textbooks PDF (I-XII)
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>

                </div>
              </div>

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