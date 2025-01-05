<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
}

validation();
?>
<?php
// SQL query to fetch the latest 3 certificates excluding certain badges
$query = "SELECT c.awarded_to_id, c.badge_name, c.issuedon, m.fullname, m.photo
          FROM certificate c
          LEFT JOIN rssimyaccount_members m ON c.awarded_to_id = m.associatenumber
          WHERE c.badge_name NOT IN ('Experience Letter', 'Offer Letter', 'Joining Letter')
          ORDER BY c.issuedon DESC LIMIT 3";

// Execute the query
$result = pg_query($con, $query);
?>
<?php
// Assuming $con is already the PostgreSQL connection resource
// Fetch the latest 3 approved events with creator's information
$query_event = "
    SELECT e.event_name, e.event_description, e.event_date, e.event_location, e.event_image_url, 
           e.created_at, m.fullname, m.photo 
    FROM events e
    JOIN rssimyaccount_members m ON e.created_by = m.associatenumber
    WHERE e.review_status = 'approved'
    ORDER BY e.created_at DESC
    LIMIT 3";
$result_event = pg_query($con, $query_event);

if (!$result_event) {
  echo "<script>alert('Failed to fetch events');</script>";
  exit;
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
  <style>
    /* Default desktop view */
    .responsive-iframe {
      width: 800px;
      height: 400px;
    }

    /* Mobile view */
    @media (max-width: 768px) {
      .responsive-iframe {
        width: 100%;
        height: auto;
      }
    }
  </style>
</head>

<body>

  <?php include 'header.php'; ?>
  <?php include 'inactive_session_expire_check.php'; ?>

  <main id="main" class="main">

    <div class="pagetitle">
      <?php

      // Extract the first name from the full name
      $firstName = explode(' ', $fullname)[0];

      // Get the current hour
      $currentHour = date('H');

      // Determine the time of day and display the appropriate message
      if ($currentHour >= 5 && $currentHour < 12) {
        $greeting = "Good Morning";
      } elseif ($currentHour >= 12 && $currentHour < 17) {
        $greeting = "Good Afternoon";
      } else {
        $greeting = "Good Evening";
      }

      // Display the message
      echo "<h2>$greeting, $firstName!</h2>";
      ?>

      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="#">Discover What’s New and Stay Connected.</a></li>
          <!-- <li class="breadcrumb-item active">Class details</li> -->
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
              <!-- Top Bar -->

              <div class="container mt-4">
                <div class="row">
                  <!-- Main Content (Latest Activities - Blog Style) -->
                  <div class="col-md-8 mb-4" style="padding-left: 20px;">
                    <div class="card shadow-sm mb-3">
                      <div class="card-body">
                        <h5 class="card-title">Latest Updates</h5>

                        <!-- Sample Activity 1 -->
                        <!-- <div class="mb-4">
                          <img src="https://via.placeholder.com/800x400/ff7f7f/333333" alt="Event Image" class="img-fluid rounded mb-3">
                          <h6 class="mb-1">Annual Science Fair 2025</h6>
                          <p class="text-muted">An exhibition showcasing innovative projects by students.</p>
                          <div class="d-flex justify-content-between text-muted small">
                            <span><strong>Conducted By:</strong> Dr. Sarah Thompson</span>
                            <span><strong>Date:</strong> 2025-01-01</span>
                          </div>
                        </div> -->


                        <?php
                        // Fetch each row and populate the HTML
                        while ($event = pg_fetch_assoc($result_event)) {
                          // Format the event date to dd/mm/yyyy h:i AM/PM format
                          $event_date = date('d/m/Y h:i A', strtotime($event['event_date']));
                          $created_at = date('d/m/Y h:i A', strtotime($event['created_at'])); // Format created_at
                        ?>
                          <div class="mb-4">
                            <div class="card shadow-sm mb-3">
                              <div class="card-body">

                                <!-- Post Header: Profile Picture, Name, and Post Time -->
                                <div class="d-flex align-items-center mb-3">
                                  <div class="profile-photo" style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; margin-right: 10px;">
                                    <img src="<?= $event['photo'] ?>" alt="Profile Photo" class="img-fluid">
                                  </div>
                                  <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($event['fullname'], ENT_QUOTES, 'UTF-8') ?></h6>
                                    <small class="text-muted"><?= $created_at ?></small>
                                  </div>
                                </div>

                                <!-- Event Image -->
                                <!-- <img src="<?= $event['event_image_url'] ?>" alt="Event Image" class="img-fluid rounded mb-3"> -->
                                <?php
                                // Assuming $event['event_image_url'] contains the Google Drive URL
                                if (!empty($event['event_image_url'])) {
                                  // Extract the file ID from the Google Drive URL using a regular expression
                                  $pattern = '/\/d\/([a-zA-Z0-9_-]+)/';
                                  if (preg_match($pattern, $event['event_image_url'], $matches)) {
                                    $photoID = $matches[1]; // Extracted file ID
                                    // Generate the preview URL for embedding in an iframe
                                    $previewUrl = "https://drive.google.com/file/d/{$photoID}/preview";
                                    echo '<iframe src="' . $previewUrl . '" class="responsive-iframe img-fluid rounded mb-3" frameborder="0" allow="autoplay" sandbox="allow-scripts allow-same-origin"></iframe>';
                                  } else {
                                    // If no valid file ID is found, display an error
                                    echo "Invalid Google Drive photo URL.";
                                  }
                                } else {
                                  // If no photo is provided, display a placeholder or message
                                  echo "No photo available.";
                                }
                                ?>
                                <!-- Event Details -->
                                <h6 class="mb-1"><?= htmlspecialchars($event['event_name'], ENT_QUOTES, 'UTF-8') ?></h6>
                                <p class="text-muted"><?= htmlspecialchars($event['event_description'], ENT_QUOTES, 'UTF-8') ?></p>

                                <!-- <div class="d-flex justify-content-between text-muted small">
                                  <span><strong>Location:</strong> <?= htmlspecialchars($event['event_location'], ENT_QUOTES, 'UTF-8') ?></span>
                                  <span><strong>Date:</strong> <?= $event_date ?></span>
                                </div> -->

                              </div>
                            </div>
                          </div>

                        <?php
                        }
                        ?>
                      </div>
                    </div>
                  </div>

                  <!-- Right Sidebar (Poll, Wall of Fame, etc.) -->
                  <div class="col-md-4 mb-4">
                    <div class="card shadow-sm mb-4">
                      <div class="card-body">
                        <h5 class="card-title">Opinion Poll</h5>
                        <form id="pollForm">
                          <div class="form-check">
                            <input class="form-check-input" type="radio" name="pollOption" id="option1" value="Option 1">
                            <label class="form-check-label" for="option1">Option 1: More Sports Events</label>
                          </div>
                          <div class="form-check">
                            <input class="form-check-input" type="radio" name="pollOption" id="option2" value="Option 2">
                            <label class="form-check-label" for="option2">Option 2: Community Involvement</label>
                          </div>
                          <div class="form-check">
                            <input class="form-check-input" type="radio" name="pollOption" id="option3" value="Option 3">
                            <label class="form-check-label" for="option3">Option 3: Virtual Events</label>
                          </div>
                          <button type="submit" class="btn btn-primary mt-3">Vote</button>
                        </form>
                      </div>
                    </div>

                    <!-- Wall of Fame Section -->
                    <div class="card shadow-sm mb-4">
                      <div class="card-body">
                        <h5 class="card-title">Recognitions</h5>
                        <ul class="list-unstyled">
                          <?php if ($result && pg_num_rows($result) > 0): ?>
                            <?php while ($row = pg_fetch_assoc($result)): ?>
                              <li class="mb-3">
                                <div class="d-flex align-items-center">
                                  <img src="<?php echo $row['photo'] ?: 'https://via.placeholder.com/50'; ?>"
                                    class="rounded-circle"
                                    alt="<?php echo htmlspecialchars($row['fullname']); ?>"
                                    style="width: 50px; height: 50px; object-fit: cover;">
                                  <div class="ms-3">
                                    <span><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></span><br>
                                    <span class="text-muted"><?php echo htmlspecialchars($row['badge_name']); ?></span><br>
                                    <small class="text-muted">Received on: <?php echo date('d/m/Y', strtotime($row['issuedon'])); ?></small>
                                  </div>
                                </div>
                              </li>
                            <?php endwhile; ?>
                          <?php else: ?>
                            <p>No certificates available to display.</p>
                          <?php endif; ?>
                        </ul>
                      </div>
                    </div>

                    <!-- Add other blocks here if needed (e.g., latest news, updates, etc.) -->
                  </div>
                </div>
              </div>
              <!-- <div class="container">
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

                          <?php if ($gm) : ?>
                            <a href="<?php echo $gm ?>" target="_blank"><?php echo substr($gm, -12) ?></a>
                          <?php else : ?>
                            Not available
                          <?php endif; ?>
                        </p>
                        <p class="card-text">
                          <strong>Class Attendance:</strong>
                          <a href="https://docs.google.com/spreadsheets/d/1ufn8vcA5tcpoVvbTgGBO9NsXmiYgjmz54Qqg_L2GZxI/edit#gid=311270786" target="_blank">Attendance sheet</a>&nbsp;|&nbsp;
                          <a href="attendx.php">New Attendance System</a>
                          <?php if (@$attd_pending != null) : ?>
                            <span class="badge bg-warning">Pending: <?php echo $attd_pending ?></span>
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
              </div> -->

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