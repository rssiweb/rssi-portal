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
// Query for certificates
$query = "
    SELECT c.awarded_to_id, c.badge_name, c.issuedon, m.fullname, m.photo
    FROM certificate c
    LEFT JOIN rssimyaccount_members m ON c.awarded_to_id = m.associatenumber
    WHERE c.badge_name NOT IN ('Experience Letter', 'Offer Letter', 'Joining Letter')
    ORDER BY c.issuedon DESC
    LIMIT 3";
$result = pg_query($con, $query);

// Query for events
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
                        <?php if ($result_event && pg_num_rows($result_event) > 0): ?>
    <?php while ($event = pg_fetch_assoc($result_event)): ?>
        <div class="mb-4">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <!-- Profile Header -->
                    <div class="d-flex align-items-center mb-3">
                        <div class="profile-photo" style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; margin-right: 10px;">
                            <img src="<?= htmlspecialchars($event['photo'] ?: 'https://via.placeholder.com/50', ENT_QUOTES, 'UTF-8') ?>" 
                                 alt="Profile Photo" 
                                 class="img-fluid">
                        </div>
                        <div>
                            <h6 class="mb-1"><?= htmlspecialchars($event['fullname'], ENT_QUOTES, 'UTF-8') ?></h6>
                            <small class="text-muted"><?= date('d/m/Y h:i A', strtotime($event['created_at'])) ?></small>
                        </div>
                    </div>

                    <!-- Event Image -->
                    <?php if (!empty($event['event_image_url'])): ?>
                        <?php
                        $pattern = '/\/d\/([a-zA-Z0-9_-]+)/';
                        if (preg_match($pattern, $event['event_image_url'], $matches)):
                            $photoID = $matches[1];
                            $previewUrl = "https://drive.google.com/file/d/{$photoID}/preview";
                        ?>
                            <iframe src="<?= $previewUrl ?>" class="responsive-iframe img-fluid rounded mb-3" frameborder="0" allow="autoplay" sandbox="allow-scripts allow-same-origin"></iframe>
                        <?php else: ?>
                            <p>Invalid Google Drive photo URL.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>No photo available.</p>
                    <?php endif; ?>

                    <!-- Event Details -->
                    <h6 class="mb-1"><?= htmlspecialchars($event['event_name'], ENT_QUOTES, 'UTF-8') ?></h6>
                    <p class="text-muted"><?= htmlspecialchars($event['event_description'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p>No events available to display.</p>
<?php endif; ?>

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