<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util_tap.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}

validation();
?>
<!doctype html>
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

  <title>Talent Acquisition Portal</title>

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
  <style>
    .milestones {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px;
      background-color: #f0f0f0;
      border-radius: 10px;
      margin: 20px auto;
    }

    .milestone {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background-color: #ccc;
      display: flex;
      justify-content: center;
      align-items: center;
      font-size: 12px;
      position: relative;
    }

    .milestone.active {
      background-color: #4CAF50;
    }

    .milestone::after {
      content: '';
      width: 100%;
      height: 4px;
      background-color: #ccc;
      position: absolute;
      top: 50%;
      left: 100%;
      z-index: -1;
    }

    .milestone.active::after {
      background-color: #4CAF50;
      width: calc(100% - 50px);
    }

    .step-label {
      margin-top: 10px;
      text-align: center;
    }
  </style>
</head>

<body>

  <?php include 'header.php'; ?>
  <!-- <?php include 'inactive_session_expire_check.php'; ?> -->

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Talent Acquisition Portal (TAP)</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="home.php">Home</a></li>
          <li class="breadcrumb-item">TAP</li>
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
              <div class="milestones">
                <div class="milestone active">
                  <div class="step-label">Application Submission</div>
                </div>
                <div class="milestone">
                  <div class="step-label">Identity Verification</div>
                </div>
                <div class="milestone">
                  <div class="step-label">Interview Scheduling</div>
                </div>
                <div class="milestone">
                  <div class="step-label">Initiate Interview</div>
                </div>
                <div class="milestone">
                  <div class="step-label">Document Verification</div>
                </div>
                <div class="milestone">
                  <div class="step-label">Interview Status</div>
                </div>
                <div class="milestone">
                  <div class="step-label">HR Interview Scheduling</div>
                </div>
                <div class="milestone">
                  <div class="step-label">HR Interview Status</div>
                </div>
                <div class="milestone">
                  <div class="step-label">Issuance of Offer Letter</div>
                </div>
                <div class="milestone">
                  <div class="step-label">Issuance of Joining Letter</div>
                </div>
                <div class="milestone">
                  <div class="step-label">Onboarding</div>
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