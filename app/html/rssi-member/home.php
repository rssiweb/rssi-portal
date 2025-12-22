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
                        <h5 class="card-title mb-3">Calendar</h5>
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
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="dateInfoModalLabel">Date Information</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            <h6 id="modalDate" class="mb-3"></h6>
                            <div id="holidayInfo" class="mb-2" style="display: none;">
                              <div class="alert alert-danger py-2">
                                <i class="bi bi-calendar-event me-2"></i>
                                <strong id="holidayName"></strong>
                              </div>
                            </div>
                            <div id="eventInfo" class="mb-2" style="display: none;">
                              <div class="alert alert-success py-2">
                                <i class="bi bi-calendar-check me-2"></i>
                                <strong id="eventName"></strong>
                                <small id="eventType" class="d-block mt-1"></small>
                              </div>
                            </div>
                            <div id="noEventsInfo" style="display: none;">
                              <div class="alert alert-light py-2">
                                <i class="bi bi-calendar-x me-2"></i>
                                No holidays or events scheduled
                              </div>
                            </div>
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
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
      });
    });
  </script>

  <!-- Bootstrap Modal -->
  <div class="modal fade" id="editEventModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editEventModalLabel">Write a Post</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" action="create_post.php" enctype="multipart/form-data">
            <div class="mb-3">
              <label for="event_name" class="form-label required-field">Event Name</label>
              <input type="text" class="form-control" id="event_name" name="event_name" required>
            </div>
            <div class="mb-3">
              <label for="event_description" class="form-label">Event Description</label>
              <textarea class="form-control" id="event_description" name="event_description" rows="3" required maxlength="1000" oninput="updateCharacterCount()"></textarea>
              <small id="charCount" class="form-text text-muted">0/1000 characters used</small>
            </div>
            <div class="mb-3">
              <label for="event_date" class="form-label required-field">Event Date</label>
              <input type="datetime-local" class="form-control" id="event_date" name="event_date" required>
            </div>
            <div class="mb-3">
              <label for="event_location" class="form-label required-field">Event Location</label>
              <select class="form-select" id="event_location" name="event_location" required>
                <option>Lucknow</option>
                <option>West Bengal</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="event_image" class="form-label required-field">Event Image (The maximum file size allowed is 300KB, with any height and width.)</label>
              <input type="file" class="form-control" id="event_image" name="event_image" accept="image/*">
              <img id="imagePreview" src="#" alt="Image Preview" class="mt-3" style="max-width: 50%; height: auto;">
            </div>
            <button type="submit" class="btn btn-primary">Create Post</button>
          </form>
        </div>
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
          const label = $(this).closest('div').find('label'); // Ensure proper label selection
          if ($(this).prop('required') && label.length && !label.find('span').length) {
            label.append(' <span style="color: red">*</span>');
          }
        });

      // Image preview and validation
      $('#event_image').on('change', function(event) {
        const file = event.target.files[0];
        const imagePreview = $('#imagePreview');
        if (file) {
          // Check image size (300 KB limit)
          if (file.size > 300 * 1024) {
            alert('Image size should not exceed 300 KB.');
            $('#event_image').val(''); // Clear the input
            imagePreview.hide(); // Hide preview
            return;
          }

          const reader = new FileReader();
          reader.onload = function(e) {
            const img = new Image();
            img.src = e.target.result;

            img.onload = function() {
              // Check dimensions (800x400)
              // if (img.width !== 800 || img.height !== 400) {
              //   alert('Image should be resized to 800x400.');
              //   $('#event_image').val(''); // Clear the input
              //   imagePreview.hide(); // Hide preview
              //   return;
              // }

              // Valid image: show preview
              imagePreview.attr('src', e.target.result).show();
            };
          };

          reader.readAsDataURL(file);
        } else {
          // No file selected: hide preview
          imagePreview.hide();
        }
      });

      // Character count update
      $('#event_description').on('input', function() {
        const charCount = $('#charCount');
        charCount.text(`${this.value.length}/1000 characters used`);
      });

      // Initially hide the image preview
      $('#imagePreview').hide();
    });
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
      let currentData = {
        holidays: [],
        events: []
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

      // Initialize calendar with enhanced features
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

        // When calendar renders
        viewDidMount: function() {
          // Add month/year selector to toolbar
          addMonthYearSelector();
        },

        // When dates change (month navigation)
        datesSet: async function(info) {
          // Show loading spinner when changing months
          showMonthLoading();

          try {
            await loadData(info.start, info.end);
          } catch (error) {
            console.error('Error loading data:', error);
          } finally {
            // Hide loading spinner
            hideMonthLoading();
          }
        },

        // Customize day cells - MAKE ENTIRE CELL CLICKABLE
        dayCellDidMount: function(info) {
          const date = info.date;
          const dayCell = info.el;

          // Set data-date attribute for easy lookup
          const dateStr = formatDate(date);
          dayCell.setAttribute('data-date', dateStr);

          // Make entire cell clickable
          dayCell.style.cursor = 'pointer';
          dayCell.addEventListener('click', function(e) {
            e.stopPropagation();
            showDateInfo(dateStr, date);
          });

          // Color Sunday text red
          if (date.getDay() === 0) {
            dayCell.classList.add('fc-day-sun');
          }
        },

        // Clean up when day cells are destroyed
        dayCellWillUnmount: function(info) {
          // Remove all custom classes when cell is removed
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

      // Add Month/Year selector to toolbar
      function addMonthYearSelector() {
        const template = document.getElementById('monthYearTemplate');
        const clone = template.content.cloneNode(true);
        const monthYearDiv = clone.querySelector('.month-year-selector');

        // Insert after title
        const titleEl = document.querySelector('.fc-toolbar-title');
        titleEl.parentNode.insertBefore(monthYearDiv, titleEl.nextSibling);

        const monthSelect = monthYearDiv.querySelector('.month-select');
        const yearSelect = monthYearDiv.querySelector('.year-select');

        // Set current month/year
        const currentDate = calendar.getDate();
        monthSelect.value = currentDate.getMonth();

        // Populate years (10 years back and forward)
        const currentYear = currentDate.getFullYear();
        for (let year = currentYear - 10; year <= currentYear + 10; year++) {
          const option = document.createElement('option');
          option.value = year;
          option.textContent = year;
          yearSelect.appendChild(option);
        }
        yearSelect.value = currentYear;

        // Add change event listeners
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
          monthSelect.value = currentDate.getMonth();
          yearSelect.value = currentDate.getFullYear();
        });
      }

      // Load data from single API
      async function loadData(startDate, endDate) {
        try {
          const start = formatDate(startDate);
          const end = formatDate(endDate);

          // Clear previous data
          currentData = {
            holidays: [],
            events: []
          };

          // Clear previous classes from ALL cells
          document.querySelectorAll('.fc-daygrid-day').forEach(cell => {
            cell.classList.remove('holiday-date', 'event-date');
          });

          // Load all data from single API
          const apiData = await fetchAllData(start, end);

          // Store data for later use (in modal)
          currentData.holidays = apiData.holidays || [];
          currentData.events = apiData.events || [];

          // Mark dates on calendar
          markHolidayDates(currentData.holidays);
          markEventDates(currentData.events);

          console.log(`Loaded ${currentData.holidays.length} holidays and ${currentData.events.length} events`);

        } catch (error) {
          console.error('Error loading data:', error);
          throw error;
        }
      }

      // Fetch all data from single API
      async function fetchAllData(start, end) {
        const response = await fetch(`/../calendar_events_api.php?start=${start}&end=${end}`);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        // Your API returns {holidays: [...], events: [...]}
        // If it returns a different structure, adjust here
        return {
          holidays: Array.isArray(data.holidays) ? data.holidays : [],
          events: Array.isArray(data.events) ? data.events : []
        };
      }

      // Mark holiday dates on calendar
      function markHolidayDates(holidays) {
        holidays.forEach(function(holiday) {
          if (holiday.date) {
            const dateStr = holiday.date;
            const dateCell = document.querySelector(`.fc-daygrid-day[data-date="${dateStr}"]`);

            if (dateCell) {
              dateCell.classList.add('holiday-date');
              dateCell.setAttribute('data-holiday-name', holiday.name || 'Holiday');
            }
          }
        });
      }

      // Mark event dates on calendar
      function markEventDates(events) {
        events.forEach(function(event) {
          if (event.date) {
            const dateStr = event.date;
            const dateCell = document.querySelector(`.fc-daygrid-day[data-date="${dateStr}"]`);

            if (dateCell) {
              dateCell.classList.add('event-date');
              dateCell.setAttribute('data-event-name', event.name || 'Event');
              dateCell.setAttribute('data-event-type', event.type || 'event');
            }
          }
        });
      }

      // Show date information in modal
      function showDateInfo(dateStr, dateObj) {
        const formattedDate = dateObj.toLocaleDateString('en-US', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });

        // Update modal title with date
        document.getElementById('modalDate').textContent = formattedDate;

        // Find holiday and event for this date
        const holiday = currentData.holidays.find(h => h.date === dateStr);
        const event = currentData.events.find(e => e.date === dateStr);

        // Show/hide sections based on data
        const holidayInfo = document.getElementById('holidayInfo');
        const eventInfo = document.getElementById('eventInfo');
        const noEventsInfo = document.getElementById('noEventsInfo');

        if (holiday) {
          document.getElementById('holidayName').textContent = holiday.name;
          holidayInfo.style.display = 'block';
        } else {
          holidayInfo.style.display = 'none';
        }

        if (event) {
          document.getElementById('eventName').textContent = event.name;
          document.getElementById('eventType').textContent = event.type ? `Type: ${event.type}` : '';
          eventInfo.style.display = 'block';
        } else {
          eventInfo.style.display = 'none';
        }

        // Show no events message if neither holiday nor event
        if (!holiday && !event) {
          noEventsInfo.style.display = 'block';
        } else {
          noEventsInfo.style.display = 'none';
        }

        // Show Bootstrap modal
        dateInfoModal.show();
      }

      // Format date as YYYY-MM-DD
      function formatDate(date) {
        const d = new Date(date);
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      }

      // Initialize calendar properly
      function initializeCalendar() {
        // Render calendar first
        calendar.render();

        // Set initial view to current month
        calendar.gotoDate(new Date());

        // Show loading while fetching initial data
        showMonthLoading();

        // Load initial data
        const currentDate = new Date();
        const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);

        loadData(firstDay, lastDay).then(() => {
          // Hide both loaders and show calendar
          hideMonthLoading();
          setTimeout(showCalendar, 100);
        }).catch(error => {
          console.error('Failed to load data:', error);
          // Still show calendar even if data fails
          hideMonthLoading();
          setTimeout(showCalendar, 100);
        });

        // Update calendar size after a brief delay
        setTimeout(() => {
          calendar.updateSize();
        }, 200);
      }

      // Start initialization
      initializeCalendar();

      // Also update size on window resize
      window.addEventListener('resize', function() {
        if (calendar) {
          setTimeout(() => calendar.updateSize(), 100);
        }
      });
    });
  </script>
</body>

</html>