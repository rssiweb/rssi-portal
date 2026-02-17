<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_tap.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("tid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}

validation();

// Fetch application data
$sql = "SELECT application_status,
               tech_interview_schedule,
               hr_interview_schedule,
               skip_tech_interview,
               skip_hr_interview,
               timestamp
        FROM signup
        WHERE application_number='$application_number'";

$result = pg_query($con, $sql);
$data = pg_fetch_assoc($result);

if (!$data) {
  echo "An error occurred.";
  exit;
}

$application_status = $data['application_status'];

/* --------------------------------------------------
   STEP 1: Define Workflow Steps (1–9)
---------------------------------------------------*/

$steps = [
  1 => "Application Submission",
  2 => "Photo Verification",
  3 => "Identity Verification",
  4 => "Interview Scheduling",
  5 => "Interview",
  6 => "HR Interview Scheduling",
  7 => "HR Interview",
  8 => "Issuance of Offer Letter",
  9 => "Issuance of Joining Letter",
];

/* --------------------------------------------------
   STEP 2: Map Application Status to Step Number
---------------------------------------------------*/

$statusStepMap = [

  /* -------------------------------
     1️⃣ Application Submission
  --------------------------------*/
  "Application Submitted" => 1,
  "Application Re-Submitted" => 1,

  /* -------------------------------
     2️⃣ Photo Verification
  --------------------------------*/
  "Photo Verification Completed" => 2,
  "Photo Verification Failed" => 2,

  /* -------------------------------
     3️⃣ Identity Verification
  --------------------------------*/
  "Identity verification document submitted" => 3,
  "Identity Verification Completed" => 3,
  "Identity Verification Failed" => 3,

  /* -------------------------------
     4️⃣ Technical Interview Scheduling
  --------------------------------*/
  "Technical Interview Scheduled" => 4,

  /* -------------------------------
     5️⃣ Technical Interview
  --------------------------------*/
  "Technical Interview Completed" => 5,
  "No-Show" => 5, // If no HR scheduled yet, still step 5

  /* -------------------------------
     6️⃣ HR Interview Scheduling
  --------------------------------*/
  "HR Interview Scheduled" => 6,

  /* -------------------------------
     7️⃣ HR Interview Outcome
  --------------------------------*/
  "Recommended" => 7,
  "Not Recommended" => 7,
  "On Hold" => 7,

  /* -------------------------------
     8️⃣ Offer Letter
  --------------------------------*/
  "Offer Extended" => 8,
  "Offer Not Extended" => 8,

  /* -------------------------------
     9️⃣ Joining Letter
  --------------------------------*/
  "Joined" => 9,
];

/* --------------------------------------------------
   STEP 3: Detect Current Step
---------------------------------------------------*/

$currentStep = $statusStepMap[$application_status] ?? 1;

/* --------------------------------------------------
   STEP 4: Generate Workflow Dynamically
---------------------------------------------------*/

$workflow = [];

foreach ($steps as $stepNumber => $stepTitle) {

  /* -------- SKIP LOGIC -------- */

  // Skip Technical Interview
  if ($stepTitle == "Interview" && $data['skip_tech_interview'] === 't') {
    $workflow[$stepTitle] = [
      "status" => "Not Required",
      "remarks" => ""
    ];
    continue;
  }

  // Skip HR Interview
  if (($stepTitle == "HR Interview Scheduling" || $stepTitle == "HR Interview")
    && $data['skip_hr_interview'] === 't'
  ) {

    $workflow[$stepTitle] = [
      "status" => "Not Required",
      "remarks" => ""
    ];
    continue;
  }

  /* -------- PROGRESS LOGIC -------- */

  if ($stepNumber < $currentStep) {
    $status = "Completed";
  } elseif ($stepNumber == $currentStep) {
    $status = "Current";
  } else {
    $status = "Pending";
  }

  /* -------- REMARKS LOGIC -------- */

  $remarks = "";

  // Application Submission timestamp
  if ($stepTitle == "Application Submission" && !empty($data['timestamp'])) {
    $remarks = "Submitted on " . date('d/m/Y h:i A', strtotime($data['timestamp']));
  }

  // Technical Interview Scheduling
  if ($stepTitle == "Interview Scheduling" && !empty($data['tech_interview_schedule'])) {
    $remarks = "Scheduled for " . date('d/m/Y h:i A', strtotime($data['tech_interview_schedule']));
  }

  // technical Interview actual result in remarks
  if ($stepTitle == "Interview" && in_array($application_status, [
    "Technical Interview Completed",
    "No-Show"
  ])) {
    $remarks = $application_status;
  }

  // HR Interview Scheduling
  if ($stepTitle == "HR Interview Scheduling" && !empty($data['hr_interview_schedule'])) {
    $remarks = "Scheduled for " . date('d/m/Y h:i A', strtotime($data['hr_interview_schedule']));
  }

  // HR Interview actual result in remarks
  if ($stepTitle == "HR Interview" && in_array($application_status, [
    "Recommended",
    "Not Recommended",
    "On Hold"
  ])) {
    $remarks = "Result: " . $application_status;
  }

  // Offer Letter actual result in remarks
  if ($stepTitle == "Issuance of Offer Letter" && in_array($application_status, [
    "Offer Extended",
    "Offer Not Extended"
  ])) {
    $remarks = $application_status;
  }

  $workflow[$stepTitle] = [
    "status" => $status,
    "remarks" => $remarks
  ];
}

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

  <title>Application Workflow</title>

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
    .green-dot {
      display: inline-block;
      width: 8px;
      height: 8px;
      background-color: #28a745;
      border-radius: 50%;
      margin-right: 6px;
      vertical-align: middle;
    }
  </style>

</head>

<body>

  <?php include 'header.php'; ?>
  <?php include 'inactive_session_expire_check.php'; ?>

  <main id="main" class="main">

    <div class="pagetitle">
      <h1>Application Workflow</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="home.php">Home</a></li>
          <li class="breadcrumb-item">Application Workflow</li>
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
                <!-- <h2 class="text-center mb-4">Application Workflow</h2> -->
                <table class="table table-bordered">
                  <thead class="table-dark">
                    <tr>
                      <th scope="col">Action Items</th>
                      <th scope="col">Status</th>
                      <th scope="col">Remarks</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($workflow as $step => $details): ?>
                      <tr>
                        <td><?= htmlspecialchars($step) ?></td>
                        <td>
                          <?php if ($details['status'] === "Current"): ?>
                            <span class="green-dot"></span>
                          <?php endif; ?>
                          <?= htmlspecialchars($details['status']) ?>
                        </td>
                        <td><?= htmlspecialchars($details['remarks']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
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