<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");
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
// Assuming you have a database connection in $con and $application_numberholds the current user ID.

function getLikeCount($event_id, $con)
{
  $query = "SELECT COUNT(*) FROM likes WHERE event_id = $1";
  $result = pg_query_params($con, $query, array($event_id));
  return pg_fetch_result($result, 0, 0);
}

function getLikedUsers($event_id, $con)
{
  $query = "
        SELECT m.fullname AS name
        FROM likes l
        JOIN rssimyaccount_members m ON l.user_id = m.associatenumber
        WHERE l.event_id = $1
        UNION
        SELECT s.applicant_name AS name
        FROM likes l
        JOIN signup s ON l.user_id = s.application_number
        WHERE l.event_id = $1
    ";
  $result = pg_query_params($con, $query, array($event_id));

  $likedUsers = [];
  while ($row = pg_fetch_assoc($result)) {
    $likedUsers[] = $row['name'];
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

  <main id="main" class="main">

    <section class="section dashboard">
      <div class="row">

        <!-- Reports -->
        <div class="col-12">
          <div class="card">

            <div class="card-body">
              <br>
              <!-- Top Bar -->

              <!-- <div class="container mt-4"> -->
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

                                <?php
                                $event_id = $event['event_id'];
                                $likeCount = getLikeCount($event_id, $con);
                                @$liked = hasUserLiked($event_id, $application_number, $con);
                                $likedUsers = getLikedUsers($event_id, $con);
                                $displayText = '';
                                if (count($likedUsers) > 0) {
                                  $displayText = implode(', ', array_slice($likedUsers, 0, 2));
                                  if (count($likedUsers) > 2) {
                                    $displayText .= ' and ' . (count($likedUsers) - 2) . ' others';
                                  }
                                }
                                ?>

                                <div class="d-flex align-items-center mt-3 text-muted" id="like-button-<?= $event['event_id'] ?>" data-user-id="<?= $application_number ?>">
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
              </div>
              <!-- </div> -->
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
          <form method="POST" action="../../create_event.php" enctype="multipart/form-data">
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

      fetch('blog.php', {
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
    const current_user_id = <?php echo json_encode($associatenumber ?? $application_number); ?>; // Get the current user's ID from PHP

    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const eventsContainer = document.getElementById('load_more');

    loadMoreBtn.addEventListener('click', function() {
      // Disable the button and show "Loading..."
      loadMoreBtn.disabled = true;
      loadMoreBtn.textContent = 'Loading...';

      // Pass current_user_id as a query parameter
      fetch(`../../load_more.php?offset=${offset}&limit=${limit}&current_user_id=${current_user_id}`)
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

                                    ${
                                        event.event_image_url
                                            ? (() => {
                                                  const pattern = /\/d\/([a-zA-Z0-9_-]+)/;
                                                  const match = event.event_image_url.match(pattern);
                                                  if (match) {
                                                      const photoID = match[1];
                                                      return `<iframe src="https://drive.google.com/file/d/${photoID}/preview" class="responsive-iframe img-fluid rounded mb-3" frameborder="0" allow="autoplay" sandbox="allow-scripts allow-same-origin"></iframe>`;
                                                  }
                                                  return '<p>Invalid Google Drive photo URL.</p>';
                                              })()
                                            : ''
                                    }

                                    <div class="d-flex align-items-center mt-3 text-muted" id="like-button-${event.event_id}" data-user-id="${current_user_id}">
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
</body>

</html>