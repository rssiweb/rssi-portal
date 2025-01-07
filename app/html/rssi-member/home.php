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
    SELECT e.event_name, e.event_id, e.event_description, e.event_date, e.event_location, e.event_image_url, 
           e.created_at, m.fullname, m.photo 
    FROM events e
    JOIN rssimyaccount_members m ON e.created_by = m.associatenumber
    WHERE e.review_status = 'approved'
    ORDER BY e.created_at DESC
    LIMIT 3";
$result_event = pg_query($con, $query_event);

// Check if both queries are successful
if (!$result || !$result_event) {
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
    // User already liked, so remove the like (unlike)
    $query = "DELETE FROM likes WHERE event_id = $1 AND user_id = $2";
    pg_query_params($con, $query, array($event_id, $user_id));
    echo json_encode(['success' => true, 'liked' => false, 'like_count' => getLikeCount($event_id, $con)]);
  } else {
    // User has not liked, so add the like
    $query = "INSERT INTO likes (event_id, user_id) VALUES ($1, $2)";
    pg_query_params($con, $query, array($event_id, $user_id));
    echo json_encode(['success' => true, 'liked' => true, 'like_count' => getLikeCount($event_id, $con)]);
  }
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

  <title>Home</title>

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
                            <div class="mb-4">
                              <div class="card shadow-sm mb-3">
                                <div class="card-body">
                                  <!-- Profile Header -->
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

                                  <!-- Event Description -->
                                  <p class="text-muted"><?= htmlspecialchars($event['event_description'], ENT_QUOTES, 'UTF-8') ?></p>

                                  <!-- Event Image (Only show if URL is valid) -->
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
                                  <?php endif; ?>

                                  <!-- Like Button -->
                                  <?php
                                  // Get like count and check if user has liked the post
                                  $event_id = $event['event_id'];
                                  $likeCountQuery = "SELECT COUNT(*) FROM likes WHERE event_id = $1";
                                  $likeCountResult = pg_query_params($con, $likeCountQuery, array($event_id));
                                  $likeCount = pg_fetch_result($likeCountResult, 0, 0);

                                  // Check if the user has already liked the post
                                  $liked = hasUserLiked($event_id, $associatenumber, $con);
                                  ?>
                                  <div class="d-flex align-items-center mt-3 text-muted" id="like-button-<?= $event['event_id'] ?>" data-user-id="<?= $associatenumber ?>">
                                    <div class="pointer" onclick="toggleLike(<?= $event['event_id'] ?>)">
                                      <i id="thumbs-up-icon-<?= $event['event_id'] ?>" class="bi bi-hand-thumbs-up me-1 <?= $liked ? 'text-primary' : '' ?>"></i>
                                      <span id="like-text-<?= $event['event_id'] ?>"><?= $liked ? 'Liked' : 'Like' ?></span>
                                      <span class="ms-2" id="like-count-<?= $event['event_id'] ?>"><?= $likeCount ?></span>
                                    </div>
                                  </div>
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
  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>

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
          <form method="POST" action="create_event.php" enctype="multipart/form-data">
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
              <label for="event_image" class="form-label required-field">Event Image</label>
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
        .find('input[required], select[required], textarea[required]')
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
              if (img.width !== 800 || img.height !== 400) {
                alert('Image should be resized to 800x400.');
                $('#event_image').val(''); // Clear the input
                imagePreview.hide(); // Hide preview
                return;
              }

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
  <!-- <script>
    // Function to handle like/unlike toggling
    function toggleLike(eventId) {
      const userId = document.getElementById(`like-button-${eventId}`).getAttribute('data-user-id');
      const likeButton = document.getElementById(`like-button-${eventId}`);
      const likeCountSpan = document.getElementById(`like-count-${eventId}`);
      const likeIcon = document.getElementById(`thumbs-up-icon-${eventId}`);
      const likeText = document.getElementById(`like-text-${eventId}`);

      // Prepare the data for the AJAX request
      const data = new FormData();
      data.append('event_id', eventId);
      data.append('user_id', userId);

      // Perform AJAX request to submit like/unlike
      fetch('home.php', {
          method: 'POST',
          body: data
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Toggle the UI based on like/unlike
            if (data.liked) {
              // User has liked the event
              likeIcon.classList.add('text-primary'); // Turn the icon color blue
              likeText.textContent = 'Liked';
              likeCountSpan.textContent = parseInt(likeCountSpan.textContent) + 1; // Increment the like count
            } else {
              // User has unliked the event
              likeIcon.classList.remove('text-primary'); // Reset the icon color
              likeText.textContent = 'Like';
              likeCountSpan.textContent = parseInt(likeCountSpan.textContent) - 1; // Decrement the like count
            }
          } else {
            console.error('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });
    }

    // Function to check if the user has already liked the event when the page loads
    function checkAlreadyLiked(eventId, liked) {
      const likeButton = document.getElementById(`like-button-${eventId}`);
      const likeIcon = document.getElementById(`thumbs-up-icon-${eventId}`);
      const likeText = document.getElementById(`like-text-${eventId}`);

      // If the user has already liked the event, mark the button as liked
      if (liked) {
        likeIcon.classList.add('text-muted'); // Set icon to gray to indicate it's already liked
        likeText.textContent = 'You Already Liked'; // Change text to reflect the state
      }
    }
  </script> -->
  <script>
    function toggleLike(eventId) {
      const userId = document.getElementById(`like-button-${eventId}`).getAttribute('data-user-id');
      const likeButton = document.getElementById(`like-button-${eventId}`);
      const likeIcon = document.getElementById(`thumbs-up-icon-${eventId}`);
      const likeText = document.getElementById(`like-text-${eventId}`);
      const likeCountSpan = document.getElementById(`like-count-${eventId}`);

      // Prepare the data for the AJAX request
      const data = new FormData();
      data.append('event_id', eventId);
      data.append('user_id', userId);

      // Perform AJAX request to submit like/unlike
      fetch('home.php', {
          method: 'POST',
          body: data
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Toggle the UI based on like/unlike
            if (data.liked) {
              // User has liked the event
              likeIcon.classList.add('text-primary'); // Turn the icon color blue
              likeText.textContent = 'Liked';
              likeCountSpan.textContent = data.like_count; // Update the like count
            } else {
              // User has unliked the event
              likeIcon.classList.remove('text-primary'); // Reset the icon color
              likeText.textContent = 'Like';
              likeCountSpan.textContent = data.like_count; // Update the like count
            }
          } else {
            console.error('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });
    }
  </script>

</body>

</html>