<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("poll_functions.php");
include(__DIR__ . "/../image_functions.php");

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
?>
<?php
// Query for events
$query_event = "
    SELECT e.event_name, e.event_id, e.event_description, e.event_date, e.event_location, e.event_image_url, 
           e.created_at, m.fullname, m.photo 
    FROM events e
    JOIN rssimyaccount_members m ON e.created_by = m.associatenumber
    WHERE e.review_status = 'Approved'
    ORDER BY e.created_at DESC
    LIMIT 3";
$result_event = pg_query($con, $query_event);

if (!$result_event) {
  echo "<script>alert('Failed to fetch certificates or events');</script>";
  exit;
}
?>
<?php
// Assuming you have a database connection in $con and $associatenumber holds the current user ID.

function getLikeCount($event_id, $con)
{
  $query = "SELECT COUNT(*) FROM likes WHERE event_id = $1";
  $result = pg_query_params($con, $query, array($event_id));
  return pg_fetch_result($result, 0, 0);
}

function getLikedUsers($event_id, $con)
{
  $query = "
    SELECT COALESCE(m.fullname, s.applicant_name) AS fullname
    FROM likes l
    LEFT JOIN rssimyaccount_members m ON l.user_id = m.associatenumber
    LEFT JOIN signup s ON l.user_id = s.application_number
    WHERE l.event_id = $1
  ";
  $result = pg_query_params($con, $query, array($event_id));

  $likedUsers = [];
  while ($row = pg_fetch_assoc($result)) {
    $likedUsers[] = $row['fullname'];
  }

  shuffle($likedUsers);

  return $likedUsers;
}

function hasUserLiked($event_id, $user_id, $con)
{
  $query = "SELECT COUNT(*) FROM likes WHERE event_id = $1 AND user_id = $2";
  $result = pg_query_params($con, $query, array($event_id, $user_id));
  return pg_fetch_result($result, 0, 0) > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $event_id = $_POST['event_id'];
  $user_id = $_POST['user_id'];

  if (hasUserLiked($event_id, $user_id, $con)) {
    $query = "DELETE FROM likes WHERE event_id = $1 AND user_id = $2";
    pg_query_params($con, $query, array($event_id, $user_id));

    $likedUsers = getLikedUsers($event_id, $con);

    echo json_encode([
      'success' => true,
      'liked' => false,
      'like_count' => getLikeCount($event_id, $con),
      'liked_users' => $likedUsers
    ]);
  } else {
    $query = "INSERT INTO likes (event_id, user_id) VALUES ($1, $2)";
    pg_query_params($con, $query, array($event_id, $user_id));

    $likedUsers = getLikedUsers($event_id, $con);

    echo json_encode([
      'success' => true,
      'liked' => true,
      'like_count' => getLikeCount($event_id, $con),
      'liked_users' => $likedUsers
    ]);
  }
  exit;
}
?>
<?php
if (!function_exists('makeClickableLinks')) {
  function makeClickableLinks($text)
  {
    // Regular expression to identify URLs in the text
    $text = preg_replace(
      '~(https?://[^\s]+)~i', // Match URLs starting with http or https
      '<a href="$1" target="_blank">$1</a>', // Replace with anchor tag
      $text
    );
    return $text;
  }
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

  <title>Home</title>

  <!-- Favicons -->
  <link href="../img/favicon.ico" rel="icon">
  <!-- Vendor CSS Files -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <!-- Template Main CSS File -->
  <link href="../assets_new/css/style.css" rel="stylesheet">
  <!-- Quill CSS -->
  <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

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

    .create-post-icon i {
      font-size: 1.5rem;
      /* Adjust icon size */
      opacity: 0.8;
      /* Slight fade */
      transition: opacity 0.3s ease, transform 0.2s ease;
    }

    .create-post-icon:hover i {
      opacity: 1;
      /* Fully visible on hover */
      transform: scale(1.1);
      /* Slightly enlarge */
    }

    .liked {
      cursor: pointer;
      color: #007bff;
    }

    .pointer {
      cursor: pointer;
    }

    .text-primary {
      color: #007bff !important;
    }
  </style>
  <!-- Add these in your <head> section -->
  <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
  <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
  <style>
    /* Professional Compact Calendar */
    .calendar-container {
      position: relative;
      min-height: 320px;
    }

    #calendar {
      font-size: 12px;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    /* Very compact cells */
    .fc .fc-daygrid-day-frame {
      min-height: 40px !important;
      padding: 0 !important;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .fc .fc-daygrid-day-top {
      padding: 0;
      justify-content: center;
    }

    .fc .fc-daygrid-day-number {
      padding: 2px !important;
      font-size: 11px;
      font-weight: 400;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none !important;
      color: #333 !important;
      border-radius: 3px;
      position: relative;
    }

    /* Make entire date cell clickable */
    .fc-daygrid-day {
      cursor: pointer;
    }

    .fc-daygrid-day:hover {
      background-color: #f8f9fa !important;
    }

    /* Today's date - blue circle */
    .fc-day-today {
      background-color: transparent !important;
    }

    .fc-day-today .fc-daygrid-day-number {
      background-color: #007bff !important;
      color: white !important;
      border-radius: 50%;
      font-weight: 500;
    }

    /* Sunday styling - red text */
    .fc-day-sun .fc-daygrid-day-number {
      color: #e74c3c !important;
      font-weight: 500;
    }

    /* Holiday highlight - LIGHT RED BACKGROUND */
    .fc-daygrid-day.holiday-date {
      background-color: #ffeaea !important;
    }

    .fc-daygrid-day.holiday-date .fc-daygrid-day-number {
      color: #d32f2f !important;
      font-weight: 400;
    }

    /* Holiday indicator - Red dot */
    .fc-daygrid-day.holiday-date .fc-daygrid-day-number::after {
      content: '';
      position: absolute;
      bottom: -2px;
      right: 2px;
      width: 4px;
      height: 4px;
      background-color: #e74c3c;
      border-radius: 50%;
    }

    /* Event highlight - LIGHT GREEN BACKGROUND */
    .fc-daygrid-day.event-date {
      background-color: #e8f5e9 !important;
    }

    .fc-daygrid-day.event-date .fc-daygrid-day-number {
      color: #2e7d32 !important;
      font-weight: 400;
    }

    /* Event indicator - Green dot */
    .fc-daygrid-day.event-date .fc-daygrid-day-number::after {
      content: '';
      position: absolute;
      bottom: -2px;
      right: 2px;
      width: 4px;
      height: 4px;
      background-color: #4caf50;
      border-radius: 50%;
    }

    /* Both holiday and event on same day */
    .fc-daygrid-day.holiday-date.event-date {
      background: linear-gradient(135deg, #ffeaea 50%, #e8f5e9 50%) !important;
    }

    .fc-daygrid-day.holiday-date.event-date .fc-daygrid-day-number::after {
      background: linear-gradient(135deg, #e74c3c 50%, #4caf50 50%);
    }

    /* Month/Year Selector */
    .month-year-selector {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-left: 15px;
    }

    .month-select,
    .year-select {
      padding: 0.25em 0.5em;
      font-size: 12px;
      border: 1px solid #dee2e6;
      border-radius: 4px;
      background: white;
      cursor: pointer;
    }

    .month-select:hover,
    .year-select:hover {
      border-color: #adb5bd;
    }

    /* Compact toolbar with enhanced controls */
    .fc-toolbar {
      padding: 0 0 10px 0 !important;
      margin-bottom: 10px !important;
      border-bottom: 1px solid #dee2e6;
      display: flex;
      align-items: center;
      flex-wrap: wrap;
    }

    .fc-toolbar-title {
      font-size: 16px !important;
      font-weight: 600;
      color: #2c3e50;
      margin: 0 15px !important;
      flex-grow: 1;
    }

    .fc-button {
      padding: 0.3em 0.6em !important;
      font-size: 12px !important;
      height: 26px !important;
      background-color: transparent !important;
      border: 1px solid #dee2e6 !important;
      color: #495057 !important;
      border-radius: 4px !important;
    }

    .fc-button:hover {
      background-color: #f8f9fa !important;
    }

    .fc-button:focus {
      box-shadow: none !important;
    }

    .fc-button-primary:not(:disabled).fc-button-active {
      background-color: #007bff !important;
      border-color: #007bff !important;
      color: white !important;
    }

    /* Header */
    .fc-col-header {
      background-color: #f8f9fa;
    }

    .fc-col-header-cell {
      padding: 8px 0 !important;
      font-size: 11px;
      font-weight: 600;
      color: #6c757d;
      text-transform: uppercase;
    }

    .fc-col-header-cell-cushion {
      padding: 4px 0 !important;
      text-decoration: none !important;
    }

    /* Clean borders */
    .fc-theme-standard td,
    .fc-theme-standard th {
      border: 1px solid #eaeaea !important;
    }

    .fc .fc-scrollgrid {
      border: none !important;
    }

    /* Loading spinner */
    .calendar-loading {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: white;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      border-radius: 4px;
    }

    .calendar-loading-overlay {
      position: absolute;
      top: 40px;
      left: 0;
      width: 100%;
      height: calc(100% - 40px);
      background: rgba(255, 255, 255, 0.9);
      display: none;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 999;
    }

    .spinner {
      width: 30px;
      height: 30px;
      border: 3px solid #f3f3f3;
      border-top: 3px solid #007bff;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-bottom: 10px;
    }

    .loading-text {
      font-size: 12px;
      color: #6c757d;
    }

    @keyframes spin {
      0% {
        transform: rotate(0deg);
      }

      100% {
        transform: rotate(360deg);
      }
    }

    /* Hide calendar while loading */
    #calendar.hidden {
      visibility: hidden;
      opacity: 0;
      height: 0;
      overflow: hidden;
    }

    #calendar.visible {
      visibility: visible;
      opacity: 1;
      height: auto;
      transition: opacity 0.3s ease;
    }

    /* Compact calendar height */
    .fc .fc-view-harness {
      min-height: 280px !important;
    }

    /* Gray out days from other months */
    .fc-day-other .fc-daygrid-day-number {
      color: #adb5bd !important;
      opacity: 0.7;
    }

    .fc-day-other.holiday-date {
      background-color: #fff0f0 !important;
    }

    .fc-day-other.event-date {
      background-color: #f0f8f0 !important;
    }

    /* Calendar legend */
    .calendar-legend {
      display: flex;
      gap: 15px;
      margin-top: 15px;
      padding-top: 10px;
      border-top: 1px solid #dee2e6;
      font-size: 11px;
    }

    .legend-item {
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .legend-color {
      width: 12px;
      height: 12px;
      border-radius: 2px;
    }

    .legend-holiday {
      background-color: #ffeaea;
    }

    .legend-event {
      background-color: #e8f5e9;
    }

    .legend-today {
      width: 12px;
      height: 12px;
      background-color: #007bff;
      border-radius: 50%;
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
                        <div class="d-flex justify-content-end mb-3">
                          <a href="#" class="text-muted create-post-icon" data-bs-toggle="modal" data-bs-target="#editEventModal" title="Write a Post">
                            <i class="bi bi-pencil-square"></i>
                          </a>
                        </div>
                        <?php if ($result_event && pg_num_rows($result_event) > 0): ?>
                          <?php while ($event = pg_fetch_assoc($result_event)): ?>
                            <div class="mb-4 event-container" data-event-id="<?= $event['event_id'] ?>">
                              <div class="card shadow-sm mb-3">
                                <div class="card-body">
                                  <div class="d-flex align-items-center mb-3">
                                    <div class="profile-photo" style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; margin-right: 10px;">
                                      <?php if (!empty($event['photo'])): ?>
                                        <img src="<?= htmlspecialchars($event['photo'], ENT_QUOTES, 'UTF-8') ?>" alt="Profile Photo" class="img-fluid">
                                      <?php else: ?>
                                        <div class="profile-initials d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #ccc; font-size: 18px; color: #fff;">
                                          <?= strtoupper(substr($event['fullname'], 0, 1)) . strtoupper(substr(explode(" ", $event['fullname'])[1], 0, 1)) ?>
                                        </div>
                                      <?php endif; ?>
                                    </div>
                                    <div>
                                      <h6 class="mb-1"><?= htmlspecialchars($event['fullname'], ENT_QUOTES, 'UTF-8') ?></h6>
                                      <small class="text-muted"><?= date('d/m/Y h:i A', strtotime($event['created_at'])) ?></small>
                                    </div>
                                  </div>

                                  <p class="text-muted"><?= nl2br(makeClickableLinks($event['event_description'])); ?></p>

                                  <?php if (!empty($event['event_image_url'])): ?>
                                    <?php
                                    // Process the Google Drive URL to proxy URL
                                    $processedUrl = processImageUrl($event['event_image_url']);
                                    ?>
                                    <img src="<?= $processedUrl ?>"
                                      class="img-fluid rounded mb-3"
                                      alt="Event image"
                                      style="max-height: 500px; object-fit: contain;"
                                      loading="lazy">
                                  <?php endif; ?>

                                  <?php
                                  $event_id = $event['event_id'];
                                  $likeCount = getLikeCount($event_id, $con);
                                  $liked = hasUserLiked($event_id, $associatenumber, $con);
                                  $likedUsers = getLikedUsers($event_id, $con);
                                  $displayText = '';
                                  if (count($likedUsers) > 0) {
                                    $displayText = implode(', ', array_slice($likedUsers, 0, 2));
                                    if (count($likedUsers) > 2) {
                                      $displayText .= ' and ' . (count($likedUsers) - 2) . ' others';
                                    }
                                  }
                                  ?>

                                  <div class="d-flex align-items-center mt-3 text-muted" id="like-button-<?= $event['event_id'] ?>" data-user-id="<?= $associatenumber ?>">
                                    <div class="pointer" onclick="toggleLike(<?= $event['event_id'] ?>)">
                                      <i id="thumbs-up-icon-<?= $event['event_id'] ?>" class="bi bi-hand-thumbs-up me-1 <?= $liked ? 'text-primary' : '' ?>"></i>
                                      <span id="like-text-<?= $event['event_id'] ?>"><?= $liked ? 'Liked' : 'Like' ?></span>
                                      <span class="ms-2" id="like-count-<?= $event['event_id'] ?>"><?= $likeCount ?></span>
                                    </div>
                                    <div class="ms-2" id="liked-users-<?= $event['event_id'] ?>"><?= $displayText ?></div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          <?php endwhile; ?>
                          <div id="load_more"></div>
                          <div class="d-flex justify-content-center mt-4">
                            <button id="loadMoreBtn" class="btn btn-primary">Load More</button>
                          </div>
                        <?php else: ?>
                          <p>No events available to display.</p>
                        <?php endif; ?>
                      </div>
                    </div>

                  </div>

                  <!-- Right Sidebar (Poll, Wall of Fame, etc.) -->
                  <div class="col-md-4 mb-4">
                    <div class="card shadow-sm mb-4">
                      <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <h5 class="card-title mb-0">Calendar</h5>
                          <a href="create_event.php" class="btn btn-primary btn-sm" id="createEventBtn">
                            <i class="bi bi-plus-circle me-1"></i> Create Event
                          </a>
                        </div>
                        <div class="calendar-container">
                          <div id="calendar" class="hidden"></div>
                          <div class="calendar-loading-overlay" id="monthLoadingOverlay">
                            <div class="spinner"></div>
                            <div class="loading-text">Loading...</div>
                          </div>
                        </div>

                        <!-- Calendar Legend -->
                        <div class="calendar-legend">
                          <div class="legend-item">
                            <div class="legend-color legend-today"></div>
                            <span>Today</span>
                          </div>
                          <div class="legend-item">
                            <div class="legend-color legend-holiday"></div>
                            <span>Holidays</span>
                          </div>
                          <div class="legend-item">
                            <div class="legend-color legend-event"></div>
                            <span>Events</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Bootstrap Modal for Date Information -->
                    <div class="modal fade" id="dateInfoModal" tabindex="-1" aria-labelledby="dateInfoModalLabel" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="dateInfoModalLabel">Date Information</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <!-- Content will be dynamically loaded here -->
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Month/Year Selector Template (will be added via JS) -->
                    <template id="monthYearTemplate">
                      <div class="month-year-selector">
                        <select class="month-select">
                          <option value="0">January</option>
                          <option value="1">February</option>
                          <option value="2">March</option>
                          <option value="3">April</option>
                          <option value="4">May</option>
                          <option value="5">June</option>
                          <option value="6">July</option>
                          <option value="7">August</option>
                          <option value="8">September</option>
                          <option value="9">October</option>
                          <option value="10">November</option>
                          <option value="11">December</option>
                        </select>
                        <select class="year-select"></select>
                      </div>
                    </template>

                    <div class="card shadow-sm mb-4">
                      <div class="card-body">
                        <h5 class="card-title">Quick Links</h5>
                        <a href="https://drive.google.com/drive/u/0/folders/14FVzPdcCP-w1Oy22Xwrexn7_XWSFqTaI" target="_blank" class="d-flex align-items-center text-muted text-decoration-none mb-2" title="Class Schedule (Google Drive)">
                          <i class="bi bi-box-arrow-up-right me-2"></i>
                          Class Schedule (Google Drive)
                        </a>
                        <!-- <a href="https://docs.google.com/spreadsheets/d/1ufn8vcA5tcpoVvbTgGBO9NsXmiYgjmz54Qqg_L2GZxI/edit#gid=1909211630" target="_blank" class="d-flex align-items-center text-muted text-decoration-none mb-2" title="Offline Attendance (Google Sheet)">
                          <i class="bi bi-box-arrow-up-right me-2"></i>
                          Offline Attendance (Google Sheet)
                        </a> -->
                        <a href="https://ncert.nic.in/textbook.php" target="_blank" class="d-flex align-items-center text-muted text-decoration-none" title="NCERT Textbooks PDF (I-XII)">
                          <i class="bi bi-box-arrow-up-right me-2"></i>
                          NCERT Textbooks PDF (I-XII)
                        </a>
                      </div>
                    </div>
                    <div class="card shadow-sm mb-4">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <h5 class="card-title mb-0">Opinion Poll</h5>
                          <a href="polls.php" class="text-muted text-decoration-none"
                            style="font-family: inherit; font-size: 0.9rem; cursor: pointer;" title="View Poll Archives">View Poll Archives →</a>
                        </div>
                        <?php
                        // Get the latest active poll
                        $latest_poll = get_latest_active_poll($con);
                        $user = get_user_id();
                        $user_id = $user ? $user['id'] : null;
                        $is_student = $user ? $user['is_student'] : false;

                        if ($latest_poll):
                          $poll_id = $latest_poll['poll_id'];
                          $has_voted = $user ? has_voted($con, $poll_id, $user_id, $is_student) : false;
                          $expired = is_poll_expired($con, $poll_id);
                          $results = get_poll_results($con, $poll_id);
                          $total_votes = array_sum(array_column($results, 'vote_count'));
                        ?>

                          <p class="poll-question"><?= htmlspecialchars($latest_poll['question']) ?></p>

                          <?php if (!$has_voted && !$expired && $user): ?>
                            <!-- Voting Form -->
                            <form action="polls.php" method="POST">
                              <input type="hidden" name="action" value="vote">
                              <input type="hidden" name="poll_id" value="<?= $poll_id ?>">
                              <div class="poll-options">
                                <?php foreach ($results as $option): ?>
                                  <div class="form-check mb-3">
                                    <input class="form-check-input no-required-mark"
                                      type="<?= $latest_poll['is_multiple_choice'] == 't' ? 'checkbox' : 'radio' ?>"
                                      name="<?= $latest_poll['is_multiple_choice'] == 't' ? 'option_id[]' : 'option_id' ?>"
                                      id="option_<?= $option['option_id'] ?>"
                                      value="<?= $option['option_id'] ?>"
                                      <?= $latest_poll['is_multiple_choice'] != 't' ? 'required' : '' ?>>
                                    <label class="form-check-label" for="option_<?= $option['option_id'] ?>">
                                      <?= htmlspecialchars($option['option_text']) ?>
                                    </label>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                              <button type="submit" class="btn btn-primary mt-3">Vote</button>
                            </form>
                          <?php else: ?>
                            <!-- Results Display -->
                            <?php if ($has_voted): ?>
                              <div class="alert alert-success mb-3">
                                <i class="bi bi-check-circle"></i> You voted in this poll
                              </div>
                            <?php elseif ($expired): ?>
                              <div class="alert alert-warning mb-3">
                                <i class="bi bi-exclamation-circle"></i> This poll has closed
                              </div>
                            <?php elseif (!$user): ?>
                              <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle"></i> Please login to vote
                              </div>
                            <?php endif; ?>

                            <div class="poll-results">
                              <div class="results-header">Results (<?= $total_votes ?> votes)</div>
                              <?php foreach ($results as $option): ?>
                                <div class="poll-option mb-2">
                                  <div class="option-text">
                                    <?= htmlspecialchars($option['option_text']) ?>
                                    (<?= $option['vote_count'] ?> votes, <?= $total_votes > 0 ? round(($option['vote_count'] / $total_votes) * 100, 1) : 0 ?>%)
                                  </div>
                                  <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" style="width: <?= $total_votes > 0 ? ($option['vote_count'] / $total_votes) * 100 : 0 ?>%"></div>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            </div>

                            <a href="polls.php?poll_id=<?= $poll_id ?>" class="btn btn-outline-secondary mt-3">View Poll Details</a>
                          <?php endif; ?>

                        <?php else: ?>
                          <div class="alert alert-info">No active polls available at this time.</div>
                        <?php endif; ?>
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
                                  <!-- Profile photo or initials -->
                                  <div class="profile-photo" style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; margin-right: 10px;">
                                    <?php if (!empty($row['photo'])): ?>
                                      <img src="<?= htmlspecialchars($row['photo'], ENT_QUOTES, 'UTF-8') ?>"
                                        class="rounded-circle" alt="<?= htmlspecialchars($row['fullname'], ENT_QUOTES, 'UTF-8') ?>"
                                        style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                      <!-- Use name initials if photo is not available -->
                                      <div class="profile-initials d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #ccc; font-size: 18px; color: #fff;">
                                        <?= strtoupper(substr($row['fullname'], 0, 1)) . strtoupper(substr(explode(" ", $row['fullname'])[1], 0, 1)) ?>
                                      </div>
                                    <?php endif; ?>
                                  </div>
                                  <div class="ms-3">
                                    <span><strong><?= htmlspecialchars($row['fullname'], ENT_QUOTES, 'UTF-8') ?></strong></span><br>
                                    <span class="text-muted"><?= htmlspecialchars($row['badge_name'], ENT_QUOTES, 'UTF-8') ?></span><br>
                                    <small class="text-muted">Received on: <?= date('d/m/Y', strtotime($row['issuedon'])) ?></small>
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
  <!-- JavaScript Library Files -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <!-- Template Main JS File -->
  <script src="../assets_new/js/main.js"></script>
  <script src="../assets_new/js/image-compressor-100kb.js"></script>
  <script src="../assets_new/js/text-refiner.js?v=1.2.0"></script>

  <!-- Quill JS -->
  <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

  <!-- Our Quill Manager -->
  <script src="../assets_new/js/quill-manager.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
      });
    });
  </script>

  <!-- Bootstrap Modal for Post Creation -->
  <div class="modal fade" id="editEventModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editEventModalLabel">Write a Post</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" action="create_post.php" enctype="multipart/form-data" id="eventForm">
            <div class="mb-3">
              <label for="event_name" class="form-label required-field">Event Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="event_name" name="event_name"
                placeholder="Enter event name (e.g., Annual Conference 2024)" required>
            </div>

            <!-- Rich Text Editor - NEW FORMAT -->
            <div class="mb-3">
              <label for="event_description" class="form-label required-field">Event Description <span class="text-danger">*</span></label>
              <!-- Quill Editor Container -->
              <div id="event_description_editor"
                class="quill-rich-text"
                data-hidden-input="event_description"
                data-label="event description"
                data-placeholder="Write your event description here..."
                style="height: 200px;">
              </div>
              <!-- Hidden Input for Form Submission -->
              <input type="hidden" id="event_description" name="event_description" required>
              <div class="form-text">You can format your text using the toolbar</div>
            </div>

            <div class="mb-3">
              <label for="event_date" class="form-label required-field">Event Date <span class="text-danger">*</span></label>
              <input type="datetime-local" class="form-control" id="event_date" name="event_date" required>
              <div class="form-text">Select date and time for the event</div>
            </div>

            <div class="mb-3">
              <label for="event_location" class="form-label required-field">Event Location <span class="text-danger">*</span></label>
              <select class="form-select" id="event_location" name="event_location" required>
                <option value="" disabled selected>Select location from the list</option>
                <option value="Lucknow">Lucknow</option>
                <option value="West Bengal">West Bengal</option>
              </select>
              <div class="form-text">Choose where the event will take place</div>
            </div>

            <div class="mb-3">
              <label for="event_image" class="form-label">Event Image</label>
              <input type="file" class="form-control" id="event_image" name="event_image" accept="image/*" onchange="compressImageBeforeUpload(this)">
              <div class="form-text">Upload an image for your event (optional)</div>
              <img id="imagePreview" src="#" alt="Preview" class="mt-2" style="max-width: 300px; max-height: 200px; display: none; object-fit: contain;">
            </div>
        </div>
        <div class="modal-footer">
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary" id="submitBtn">
              <span id="submitText">Create Post</span>
              <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetEventForm()" id="clearBtn">Clear</button>
          </div>
        </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    $(document).ready(function() {
      // Add red asterisk to required fields
      $('form')
        .find('input[required]:not(.no-required-mark), select[required], textarea[required]')
        .each(function() {
          const label = $(this).closest('div').find('label');
          if ($(this).prop('required') && label.length && !label.find('.text-danger').length) {
            label.append(' <span class="text-danger">*</span>');
          }
        });

      // Image preview handler - NO SIZE RESTRICTIONS
      document.getElementById('event_image').addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');
        const file = e.target.files[0];

        if (file) {
          const reader = new FileReader();
          reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
          }
          reader.readAsDataURL(file);
        } else {
          preview.style.display = 'none';
        }
      });

      // Form submission handler with loading state
      $('#eventForm').on('submit', function(e) {
        e.preventDefault();

        // Validate form before submission
        if (!validateForm()) {
          return false;
        }

        // Show loading state
        showLoadingState(true);

        // Get form data
        const formData = new FormData(this);

        // Get Quill content and add to form data
        const quill = window.quillManager?.getQuillInstance('event_description_editor');
        if (quill) {
          const quillContent = quill.root.innerHTML;
          formData.set('event_description', quillContent);
        }

        // Submit via AJAX
        $.ajax({
          url: $(this).attr('action'),
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function(response) {
            // Hide loading state
            showLoadingState(false);

            // Show the response (your PHP script shows alerts)
            $('body').append(response);

            // Reset form
            resetEventForm();

            // Close modal after 1.5 seconds to let user see success message
            setTimeout(function() {
              $('#editEventModal').modal('hide');

              // Reload the page after modal closes
              setTimeout(function() {
                window.location.reload();
              }, 500);
            }, 1500);
          },
          error: function(xhr, status, error) {
            // Hide loading state
            showLoadingState(false);

            // Show error message
            alert('Error submitting form: ' + error);

            // Re-enable form
            enableForm(true);
          }
        });

        return false;
      });

      // Initially hide the image preview
      $('#imagePreview').hide();
    });

    // Reset form function
    function resetEventForm() {
      const form = document.getElementById('eventForm');
      if (form) {
        form.reset();
      }

      // Clear Quill editor
      const quill = window.quillManager?.getQuillInstance('event_description_editor');
      if (quill) {
        quill.setContents([]);
        window.quillManager.saveEditorContent('event_description_editor');
      }

      // Hide preview
      document.getElementById('imagePreview').style.display = 'none';

      // Re-enable form if it was disabled
      enableForm(true);
    }

    // Show/hide loading state
    function showLoadingState(show) {
      const submitBtn = document.getElementById('submitBtn');
      const clearBtn = document.getElementById('clearBtn');
      const submitText = document.getElementById('submitText');
      const submitSpinner = document.getElementById('submitSpinner');

      if (show) {
        // Disable buttons
        submitBtn.disabled = true;
        clearBtn.disabled = true;

        // Show spinner, hide text
        submitText.textContent = 'Submitting...';
        submitSpinner.classList.remove('d-none');

        // Add processing class
        submitBtn.classList.add('processing');
      } else {
        // Enable buttons
        submitBtn.disabled = false;
        clearBtn.disabled = false;

        // Hide spinner, show original text
        submitText.textContent = 'Create Post';
        submitSpinner.classList.add('d-none');

        // Remove processing class
        submitBtn.classList.remove('processing');
      }
    }

    // Enable/disable form elements
    function enableForm(enable) {
      const form = document.getElementById('eventForm');
      const elements = form.querySelectorAll('input, select, textarea, button');

      elements.forEach(element => {
        if (element.id !== 'submitBtn' && element.id !== 'clearBtn') {
          element.disabled = !enable;
        }
      });

      // Enable/disable Quill editor
      const quill = window.quillManager?.getQuillInstance('event_description_editor');
      if (quill) {
        quill.enable(enable);
      }
    }

    // Form validation
    function validateForm() {
      const form = document.getElementById('eventForm');
      let isValid = true;

      // Clear previous errors
      form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
      form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
      form.querySelectorAll('.text-danger').forEach(el => {
        if (el.parentElement.classList.contains('form-text')) {
          el.remove();
        }
      });

      // Check required fields
      const requiredFields = form.querySelectorAll('[required]');
      requiredFields.forEach(field => {
        if (!field.value.trim()) {
          isValid = false;
          field.classList.add('is-invalid');

          // Add error message
          const errorDiv = document.createElement('div');
          errorDiv.className = 'invalid-feedback';
          errorDiv.textContent = 'This field is required';
          field.parentNode.appendChild(errorDiv);
        }
      });

      // Check Quill editor content
      const quill = window.quillManager?.getQuillInstance('event_description_editor');
      if (quill) {
        const quillText = quill.getText().trim();
        if (quillText === '') {
          isValid = false;
          const editorContainer = document.getElementById('event_description_editor');
          editorContainer.classList.add('border-danger');

          // Show error message
          const errorMsg = document.createElement('div');
          errorMsg.className = 'text-danger small mt-1';
          errorMsg.textContent = 'Event description is required';
          const formText = editorContainer.parentNode.querySelector('.form-text');
          if (formText) {
            formText.after(errorMsg);
          }
        }
      }

      return isValid;
    }

    // Add CSS for loading state
    const style = document.createElement('style');
    style.textContent = `
    #submitBtn.processing {
      opacity: 0.8;
      cursor: not-allowed;
    }
    
    .quill-rich-text.border-danger {
      border: 1px solid #dc3545 !important;
    }
    
    .quill-rich-text.border-danger .ql-toolbar {
      border-color: #dc3545 !important;
    }
    
    .quill-rich-text.border-danger .ql-container {
      border-color: #dc3545 !important;
    }
    
    .is-invalid {
      border-color: #dc3545 !important;
    }
    
    .invalid-feedback {
      display: block;
      color: #dc3545;
      font-size: 0.875em;
      margin-top: 0.25rem;
    }
    
    #submitBtn:disabled {
      cursor: not-allowed;
    }
    
    #clearBtn:disabled {
      cursor: not-allowed;
    }
  `;
    document.head.appendChild(style);
  </script>
  <script>
    function toggleLike(eventId) {
      const userId = document.getElementById(`like-button-${eventId}`).getAttribute('data-user-id');
      const likeButton = document.getElementById(`like-button-${eventId}`);
      const likeIcon = document.getElementById(`thumbs-up-icon-${eventId}`);
      const likeText = document.getElementById(`like-text-${eventId}`);
      const likeCountSpan = document.getElementById(`like-count-${eventId}`);
      const likedUsersSpan = document.getElementById(`liked-users-${eventId}`);

      const data = new FormData();
      data.append('event_id', eventId);
      data.append('user_id', userId);

      fetch('home.php', {
          method: 'POST',
          body: data
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            updateLikeUI(eventId, data); // Update UI using the new function
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });
    }

    function updateLikeUI(eventId, data) {
      const likeIcon = document.getElementById(`thumbs-up-icon-${eventId}`);
      const likeText = document.getElementById(`like-text-${eventId}`);
      const likeCountSpan = document.getElementById(`like-count-${eventId}`);
      const likedUsersSpan = document.getElementById(`liked-users-${eventId}`);

      if (data.liked) {
        likeIcon.classList.add('text-primary');
        likeText.textContent = 'Liked';
      } else {
        likeIcon.classList.remove('text-primary');
        likeText.textContent = 'Like';
      }

      likeCountSpan.textContent = data.like_count;

      // Update liked users if needed
      updateLikedUsers(eventId, data.liked_users);
    }

    function updateLikedUsers(eventId, likedUsers) {
      const likedUsersSpan = document.getElementById(`liked-users-${eventId}`);
      let displayText = '';

      if (likedUsers.length > 0) {
        displayText = likedUsers.slice(0, 2).join(', ');
        if (likedUsers.length > 2) {
          displayText += ' and ' + (likedUsers.length - 2) + ' others';
        }
      }

      likedUsersSpan.textContent = displayText;
    }

    let offset = 3; // Start after the initial batch of 3
    const limit = 3; // Number of events per batch

    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const eventsContainer = document.getElementById('load_more');

    loadMoreBtn.addEventListener('click', function() {
      // Disable the button and show "Loading..."
      loadMoreBtn.disabled = true;
      loadMoreBtn.textContent = 'Loading...';

      fetch(`load_more.php?offset=${offset}&limit=${limit}`)
        .then((response) => response.json())
        .then((data) => {
          if (data.success && data.events.length > 0) {
            // Append new events to the container
            data.events.forEach((event) => {
              const likedUsersText = event.liked_users.length > 0 ?
                `${event.liked_users.slice(0, 2).join(', ')}${
                              event.liked_users.length > 2 ? ` and ${event.liked_users.length - 2} others` : ''
                          }` :
                '';

              const eventHtml = `
                        <div class="mb-4 event-container" data-event-id="${event.event_id}">
                            <div class="card shadow-sm mb-3">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="profile-photo" style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; margin-right: 10px;">
                                            <img src="${event.photo}" alt="Profile Photo" class="img-fluid">
                                        </div>
                                        <div>
                                            <h6 class="mb-1">${event.fullname}</h6>
                                            <small class="text-muted">${new Date(event.created_at).toLocaleString()}</small>
                                        </div>
                                    </div>

                                    <p class="text-muted">${event.event_description}</p>

                                    ${event.event_image_url ? `<img src="${event.event_image_url}" class="img-fluid rounded mb-3" alt="Event image" style="max-height: 500px; object-fit: contain;" loading="lazy">` : ''}

                                    <div class="d-flex align-items-center mt-3 text-muted" id="like-button-${event.event_id}" data-user-id="<?php echo $associatenumber; ?>">
                                        <div class="pointer" onclick="toggleLike(${event.event_id})">
                                            <i id="thumbs-up-icon-${event.event_id}" class="bi bi-hand-thumbs-up me-1 ${event.liked === 't' ? 'text-primary' : ''}"></i>
                                            <span id="like-text-${event.event_id}">${event.liked === 't' ? 'Liked' : 'Like'}</span>
                                            <span class="ms-2" id="like-count-${event.event_id}">${event.like_count}</span>
                                        </div>
                                        <div class="ms-2" id="liked-users-${event.event_id}">${likedUsersText}</div>
                                    </div>
                                </div>
                            </div>
                        </div>`;
              eventsContainer.insertAdjacentHTML('beforeend', eventHtml);
            });

            // Increment the offset to load the next batch
            offset += data.events.length;

            // Re-enable the button
            loadMoreBtn.disabled = false;
            loadMoreBtn.textContent = 'Load More';
          } else {
            // No more events available, replace button with a message
            loadMoreBtn.remove();
            const noMoreMessage = `<p class="text-muted text-center mt-4">That's all we have for you now.</p>`;
            eventsContainer.insertAdjacentHTML('beforeend', noMoreMessage);
          }
        })
        .catch((error) => {
          console.error('Error loading more events:', error);
          // Reset the button on error
          loadMoreBtn.disabled = false;
          loadMoreBtn.textContent = 'Load More';
        });
    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const calendarContainer = document.querySelector('.calendar-container');
      const calendarEl = document.getElementById('calendar');
      const monthLoadingOverlay = document.getElementById('monthLoadingOverlay');

      // Create initial loading spinner
      const loadingDiv = document.createElement('div');
      loadingDiv.className = 'calendar-loading';
      loadingDiv.innerHTML = `
        <div class="spinner"></div>
        <div class="loading-text">Loading Calendar...</div>
      `;
      calendarContainer.appendChild(loadingDiv);

      // Get Bootstrap modal instance
      const dateInfoModal = new bootstrap.Modal(document.getElementById('dateInfoModal'));

      // Track if we're currently loading
      let isLoadingMonth = false;
      let currentDates = {
        holiday_dates: [],
        event_dates: []
      };

      // Function to show month loading spinner
      function showMonthLoading() {
        if (!isLoadingMonth) {
          monthLoadingOverlay.style.display = 'flex';
          isLoadingMonth = true;
        }
      }

      // Function to hide month loading spinner
      function hideMonthLoading() {
        if (isLoadingMonth) {
          monthLoadingOverlay.style.display = 'none';
          isLoadingMonth = false;
        }
      }

      // Initialize calendar
      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
          left: 'prev',
          center: 'title',
          right: 'next today'
        },
        height: 'auto',
        firstDay: 1,
        navLinks: false,
        editable: false,
        selectable: false,
        weekNumbers: false,
        dayMaxEvents: false,
        handleWindowResize: true,
        showNonCurrentDates: true,
        fixedWeekCount: false,

        // Set initial date to current
        initialDate: new Date(),

        // When dates change (month navigation)
        datesSet: async function(info) {
          showMonthLoading();

          try {
            await loadCalendarDates(info.start, info.end);
          } catch (error) {
            console.error('Error loading calendar dates:', error);
          } finally {
            hideMonthLoading();
          }
        },

        // Customize day cells
        dayCellDidMount: function(info) {
          const date = info.date;
          const dayCell = info.el;

          // Set data-date attribute
          const dateStr = formatDate(date);
          dayCell.setAttribute('data-date', dateStr);

          // Make entire cell clickable
          dayCell.style.cursor = 'pointer';
          dayCell.addEventListener('click', async function(e) {
            e.stopPropagation();
            await showDateDetails(dateStr, date);
          });

          // Color Sunday text red
          if (date.getDay() === 0) {
            dayCell.classList.add('fc-day-sun');
          }
        },

        // Clean up when day cells are destroyed
        dayCellWillUnmount: function(info) {
          info.el.classList.remove('holiday-date', 'event-date', 'fc-day-sun');
        }
      });

      // Function to show calendar after initial loading
      function showCalendar() {
        loadingDiv.style.display = 'none';
        calendarEl.classList.remove('hidden');
        calendarEl.classList.add('visible');
        calendar.updateSize();
      }

      // Load calendar dates (for highlighting)
      async function loadCalendarDates(startDate, endDate) {
        try {
          const start = formatDate(startDate);
          const end = formatDate(endDate);

          // Clear previous data
          currentDates = {
            holiday_dates: [],
            event_dates: []
          };

          // Clear previous classes
          document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
            cell.classList.remove('holiday-date', 'event-date');
          });

          // Load dates from API
          const apiData = await fetchCalendarDates(start, end);

          // Store data
          currentDates.holiday_dates = apiData.holiday_dates || [];
          currentDates.event_dates = apiData.event_dates || [];

          // Mark dates on calendar
          markHolidayDates(currentDates.holiday_dates);
          markEventDates(currentDates.event_dates);

          console.log(`Found ${currentDates.holiday_dates.length} holiday dates and ${currentDates.event_dates.length} event dates`);

        } catch (error) {
          console.error('Error loading calendar dates:', error);
          throw error;
        }
      }

      // Fetch calendar dates from API
      async function fetchCalendarDates(start, end) {
        const response = await fetch(`/../calendar_dates_api.php?start=${start}&end=${end}`);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
      }

      // Mark holiday dates on calendar
      function markHolidayDates(holidayDates) {
        holidayDates.forEach(function(dateStr) {
          const dateCell = document.querySelector(`.fc-daygrid-day[data-date="${dateStr}"]`);
          if (dateCell) {
            dateCell.classList.add('holiday-date');
          }
        });
      }

      // Mark event dates on calendar
      function markEventDates(eventDates) {
        eventDates.forEach(function(dateStr) {
          const dateCell = document.querySelector(`.fc-daygrid-day[data-date="${dateStr}"]`);
          if (dateCell) {
            dateCell.classList.add('event-date');
          }
        });
      }

      // Show date details with loading spinner
      async function showDateDetails(dateStr, dateObj) {
        const formattedDate = dateObj.toLocaleDateString('en-US', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });

        // Show loading state in modal
        showModalLoading(formattedDate);

        // Show modal immediately
        dateInfoModal.show();

        try {
          // Fetch detailed data for this date
          const dateData = await fetchDateDetails(dateStr);

          // Update modal with data
          updateModalContent(dateData, formattedDate);
        } catch (error) {
          console.error('Error loading date details:', error);
          showModalError('Failed to load date information', formattedDate);
        }
      }

      // Show loading state in modal
      function showModalLoading(formattedDate) {
        const modalBody = document.querySelector('#dateInfoModal .modal-body');
        modalBody.innerHTML = `
      <h6 class="mb-3 text-primary">${formattedDate}</h6>
      <div class="text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Loading date information...</p>
      </div>
    `;
      }

      // Show error in modal
      function showModalError(message, formattedDate) {
        const modalBody = document.querySelector('#dateInfoModal .modal-body');
        modalBody.innerHTML = `
      <h6 class="mb-3 text-primary">${formattedDate}</h6>
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i>
        ${message}
      </div>
    `;
      }

      // Fetch detailed date information
      async function fetchDateDetails(dateStr) {
        const response = await fetch(`/../date_details_api.php?date=${dateStr}`);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
      }

      // Update modal content with fetched data
      function updateModalContent(dateData, formattedDate) {
        const modalBody = document.querySelector('#dateInfoModal .modal-body');
        let content = `<h6 class="mb-3 text-primary">${formattedDate}</h6>`;

        // Check if there's any data
        if (!dateData.holidays?.length && !dateData.events?.length) {
          content += `
        <div class="alert alert-light">
          <i class="bi bi-calendar-x me-2"></i>
          <strong>No holidays or events scheduled for this date</strong>
        </div>
      `;
        } else {
          // Show holidays if any
          if (dateData.holidays?.length > 0) {
            content += `<h6 class="mt-3 mb-2"><i class="bi bi-calendar-event me-2"></i>Holidays</h6>`;
            dateData.holidays.forEach(holiday => {
              content += `
            <div class="card mb-2 border-danger">
              <div class="card-body p-3">
                <h6 class="card-title text-danger mb-1">
                  <i class="bi bi-flag me-2"></i>${holiday.name}
                </h6>
                <p class="card-text mb-0 small">
                  <i class="bi bi-calendar-date me-1"></i> ${holiday.date}
                </p>
              </div>
            </div>
          `;
            });
          }

          // Show events if any
          if (dateData.events?.length > 0) {
            content += `<h6 class="mt-4 mb-2"><i class="bi bi-calendar-check me-2"></i>Events</h6>`;
            dateData.events.forEach(event => {
              content += `
            <div class="card mb-3 border-success">
              <div class="card-body p-3">
                <h6 class="card-title text-success mb-1">
                  <i class="bi bi-calendar3 me-2"></i>${event.name}
                  <span class="badge bg-secondary text-white ms-2">${event.type}</span>
                </h6>
                
                ${event.description ? `<p class="card-text mb-2">${event.description}</p>` : ''}
                
                <div class="row small text-muted">
                  ${event.location ? `
                    <div class="col-12 mb-1">
                      <i class="bi bi-geo-alt me-1"></i> ${event.location}
                    </div>
                  ` : ''}
                  
                  ${event.is_full_day ? `
                    <div class="col-12 mb-1">
                      <i class="bi bi-clock me-1"></i> Full Day Event
                    </div>
                  ` : `
                    ${event.start_time ? `
                      <div class="col-12 mb-1">
                        <i class="bi bi-play-circle me-1"></i> Start: ${event.start_time}
                      </div>
                    ` : ''}
                    ${event.end_time ? `
                      <div class="col-12 mb-1">
                        <i class="bi bi-stop-circle me-1"></i> End: ${event.end_time}
                      </div>
                    ` : ''}
                    ${event.reporting_time ? `
                      <div class="col-12 mb-1">
                        <i class="bi bi-person-check me-1"></i> Reporting: ${event.reporting_time}
                      </div>
                    ` : ''}
                  `}
                  
                  <div class="col-12 mb-1">
                    <i class="bi bi-person-plus me-1"></i> Created by: ${event.created_by_name || event.created_by}
                  </div>
                  
                  ${event.updated_by_name ? `
                    <div class="col-12">
                      <i class="bi bi-person-check me-1"></i> Last updated by: ${event.updated_by_name}
                    </div>
                  ` : ''}
                </div>
              </div>
            </div>
          `;
            });
          }
        }

        modalBody.innerHTML = content;
      }

      // Format date as YYYY-MM-DD
      function formatDate(date) {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      }

      // Initialize calendar
      function initializeCalendar() {
        calendar.render();
        calendar.gotoDate(new Date());

        showMonthLoading();

        const currentDate = new Date();
        const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);

        loadCalendarDates(firstDay, lastDay).then(() => {
          hideMonthLoading();
          setTimeout(showCalendar, 100);
        }).catch(error => {
          console.error('Failed to load calendar dates:', error);
          hideMonthLoading();
          setTimeout(showCalendar, 100);
        });

        setTimeout(() => {
          calendar.updateSize();
          // Add month/year selector after calendar is rendered
          addMonthYearSelector();
        }, 200);
      }

      // Replace the addMonthYearSelector function with this:
      function addMonthYearSelector() {
        // Check if template exists
        const template = document.getElementById('monthYearTemplate');
        if (!template) return;

        const clone = template.content.cloneNode(true);
        const monthYearDiv = clone.querySelector('.month-year-selector');

        // Find the calendar title element
        const titleElement = document.querySelector('.fc-toolbar-title');
        if (titleElement && monthYearDiv) {
          // Replace the title with our month/year selector
          titleElement.style.display = 'none';

          // Create a container for our custom title
          const customTitleContainer = document.createElement('div');
          customTitleContainer.className = 'fc-custom-title';

          // Style the selectors
          const monthSelect = monthYearDiv.querySelector('.month-select');
          const yearSelect = monthYearDiv.querySelector('.year-select');

          monthSelect.style.cssText = `
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background-color: white;
            font-size: 1rem;
            margin-right: 10px;
        `;

          yearSelect.style.cssText = `
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background-color: white;
            font-size: 1rem;
        `;

          // Add selectors to container
          customTitleContainer.appendChild(monthSelect);
          customTitleContainer.appendChild(yearSelect);

          // Insert after the hidden title
          titleElement.parentNode.insertBefore(customTitleContainer, titleElement.nextSibling);

          // Set current month/year
          const currentDate = calendar.getDate();
          if (monthSelect) monthSelect.value = currentDate.getMonth();

          // Populate years (current year ± 5 years)
          const currentYear = currentDate.getFullYear();
          if (yearSelect) {
            // Clear existing options
            yearSelect.innerHTML = '';

            for (let year = currentYear - 5; year <= currentYear + 5; year++) {
              const option = document.createElement('option');
              option.value = year;
              option.textContent = year;
              option.selected = year === currentYear;
              yearSelect.appendChild(option);
            }
          }

          // Add change event listeners
          if (monthSelect && yearSelect) {
            monthSelect.addEventListener('change', function() {
              const year = parseInt(yearSelect.value);
              const month = parseInt(monthSelect.value);
              calendar.gotoDate(new Date(year, month, 1));
            });

            yearSelect.addEventListener('change', function() {
              const year = parseInt(yearSelect.value);
              const month = parseInt(monthSelect.value);
              calendar.gotoDate(new Date(year, month, 1));
            });

            // Update selectors when calendar changes
            calendar.on('datesSet', function(info) {
              const currentDate = info.view.currentStart;
              if (monthSelect) monthSelect.value = currentDate.getMonth();
              if (yearSelect) yearSelect.value = currentDate.getFullYear();
            });
          }
        }
      }

      // Start initialization
      initializeCalendar();

      // Update size on window resize
      window.addEventListener('resize', function() {
        if (calendar) {
          setTimeout(() => calendar.updateSize(), 100);
        }
      });
    });
  </script>
</body>

</html>