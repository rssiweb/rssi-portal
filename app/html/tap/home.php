<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_tap.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}

validation();

// SQL query to fetch current application status
$sql = "SELECT application_status, tech_interview_schedule, hr_interview_schedule,photo_verification,identity_verification,supporting_document,timestamp FROM signup WHERE application_number='$application_number'";
$result = pg_query($con, $sql);
$data = pg_fetch_assoc($result);

if (!$data) {
  echo "An error occurred.
";
  exit;
}

$application_status = $data['application_status'];

function getStatus($application_status, $conditions, $completedStatus, $defaultStatus)
{
  // Loop through the status conditions
  foreach ($conditions as $status => $valid_statuses) {
    // Check if the application status exists in the valid status array
    if (in_array($application_status, $valid_statuses)) {
      return $status;  // Return the matching status type
    }
  }
  return $defaultStatus;  // Return default if no match is found
}

$workflow = [
  "Application Submission" => [
    "status" => "Completed",
    "remarks" => !empty($data['timestamp'])
      ? "Application submitted on " . date('d/m/Y h:i A', strtotime($data['timestamp']))
      : "Application submission timestamp not available",
  ],
  "Photo Verification" => [
    "status" => ($data['photo_verification'] == 'Approved') ? "Completed" :
      getStatus($application_status, [
        "Completed" => ["Photo Verification Completed", "Identity Verification Completed", "Identity Verification Failed", "Technical Interview Scheduled", "Technical Interview Completed", "HR Interview Scheduled", "Recommended", "Not Recommended", "On Hold", "No-Show", "Offer Extended", "Offer Not Extended"],
        "Photo Verification Failed" => ["Photo Verification Failed"],
      ], "Pending", "Pending"),
    "remarks" => "",
  ],
  "Identity Verification" => [
    "status" => (empty($data['identity_verification']) && !empty($data['supporting_document'])) ? "Identity verification document submitted" :
      getStatus($application_status, [
        "Completed" => ["Identity Verification Completed", "Technical Interview Scheduled", "Technical Interview Completed", "HR Interview Scheduled", "Recommended", "Not Recommended", "On Hold", "No-Show", "Offer Extended", "Offer Not Extended"],
        "Identity Verification Document Submitted" => ["Identity verification document submitted"],
        "Identity Verification Failed" => ["Identity Verification Failed"],
      ], "Pending", "Pending"),
    "remarks" => "",
  ],
  "Interview Scheduling" => [
    "status" => !empty($data['tech_interview_schedule']) ? "Completed" : "Pending",
    "remarks" => !empty($data['tech_interview_schedule'])
      ? "Scheduled for " . date('d/m/Y h:i A', strtotime($data['tech_interview_schedule']))
      : "",
  ],
  "Interview" => [
    "status" => getStatus($application_status, [
      "Completed" => array_merge(
        ["Technical Interview Completed", "HR Interview Scheduled", "Recommended", "Not Recommended", "On Hold", "Offer Extended", "Offer Not Extended"],
        !empty($data['hr_interview_schedule']) && $application_status == "No-Show" ? ["No-Show"] : []
      ),
      "Technical Interview No-Show" => empty($data['hr_interview_schedule']) && $application_status == "No-Show" ? ["No-Show"] : [],
    ], "Pending", "Pending"),
    "remarks" => "",
  ],
  "HR Interview Scheduling" => [
    "status" => !empty($data['hr_interview_schedule']) ? "Completed" : "Pending",
    "remarks" => !empty($data['hr_interview_schedule'])
      ? "Scheduled for " . date('d/m/Y h:i A', strtotime($data['hr_interview_schedule']))
      : "",
  ],
  "HR Interview" => [
    "status" => getStatus($application_status, [
      "Completed" => ["Recommended", "Not Recommended", "On Hold", "Offer Extended", "Offer Not Extended"],
      "HR Interview No-Show" => !empty($data['hr_interview_schedule']) && $application_status == "No-Show" ? ["No-Show"] : [],
    ], "Pending", "Pending"),
    "remarks" => "",
  ],
  "Issuance of Offer Letter" => [
    "status" => $application_status === "Offer Extended" ? "Offer Extended" : ($application_status === "Offer Not Extended" ? "Offer Not Extended" : "Pending"),
    "remarks" => "",
  ],
  "Issuance of Joining Letter" => [
    "status" => "Pending",
    "remarks" => "",
  ],
];

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
                        <td><?= htmlspecialchars($details['status']) ?></td>
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