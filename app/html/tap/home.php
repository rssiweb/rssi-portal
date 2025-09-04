<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util_tap.php");

if (!isLoggedIn("tid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
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
    echo "<script>alert('Failed to fetch events');</script>";
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
            $firstName = explode(' ', $applicant_name)[0];

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
                                                                    $liked = hasUserLiked($event_id, $application_number, $con);
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

                                    <!-- Right Sidebar (Poll, Wall of Fame, etc.) -->
                                    <div class="col-md-4 mb-4">
                                        <div class="card shadow-sm mb-4">
                                            <div class="card-body">
                                                <h5 class="card-title">Quick Links</h5>
                                                <a href="https://myaadhaar.uidai.gov.in/genricDownloadAadhaar/en" target="_blank" class="d-flex align-items-center text-muted text-decoration-none mb-2" title="Download Aadhaar">
                                                    <i class="bi bi-box-arrow-up-right me-2"></i>
                                                    Download Aadhaar
                                                </a>

                                                <a href="https://onlineservices.proteantech.in/paam/endUserRegisterContact.html" target="_blank" class="d-flex align-items-center text-muted text-decoration-none" title="Online PAN application">
                                                    <i class="bi bi-box-arrow-up-right me-2"></i>
                                                    Online PAN application
                                                </a>
                                            </div>
                                        </div>
                                        <div class="card shadow-sm mb-4">
                                            <div class="card-body">
                                                <!-- <h5 class="card-title">Instagram Feed</h5> -->
                                                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3559.230194800712!2d81.0229821760294!3d26.864426562162908!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x399be3fc575228e3%3A0xbbc4182b61aa1609!2sRSSI%20NGO!5e0!3m2!1sen!2sin!4v1757013981236!5m2!1sen!2sin" width=100% height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
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

    <!-- Scripts -->
    <script>
        function toggleLike(eventId) {
            const userId = document.getElementById(`like-button-${eventId}`).getAttribute('data-user-id');
            const likeIcon = document.getElementById(`thumbs-up-icon-${eventId}`);
            const likeText = document.getElementById(`like-text-${eventId}`);

            const data = new FormData();
            data.append('event_id', eventId);
            data.append('user_id', userId);

            fetch('home1.php', {
                    method: 'POST',
                    body: data
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        if (data.liked) {
                            likeIcon.classList.add('text-primary');
                            likeText.textContent = 'Liked';
                        } else {
                            likeIcon.classList.remove('text-primary');
                            likeText.textContent = 'Like';
                        }

                        // Update count and users
                        document.getElementById(`like-count-${eventId}`).textContent = data.like_count;

                        let displayText = '';
                        if (data.liked_users.length > 0) {
                            displayText = data.liked_users.slice(0, 2).join(', ');
                            if (data.liked_users.length > 2) {
                                displayText += ' and ' + (data.liked_users.length - 2) + ' others';
                            }
                        }
                        document.getElementById(`liked-users-${eventId}`).textContent = displayText;
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
        const current_user_id = '<?= $application_number ?>'; // Use PHP to set the current user ID

        const loadMoreBtn = document.getElementById('loadMoreBtn');
        const eventsContainer = document.getElementById('load_more');

        loadMoreBtn.addEventListener('click', function() {
            // Disable the button and show "Loading..."
            loadMoreBtn.disabled = true;
            loadMoreBtn.textContent = 'Loading...';

            fetch(`../get_blog_data.php?offset=${offset}&limit=${limit}&user_id=${current_user_id}`)
                .then((response) => response.json())
                .then((data) => {
                    if (data.success && data.events.length > 0) {
                        // Append new events to the container
                        data.events.forEach((event) => {
                            // Extract likes for this event
                            const likeData = data.likes[event.event_id] || {
                                like_count: 0,
                                liked_users: [],
                                liked: false
                            };

                            const likedUsersText = likeData.liked_users.length > 0 ?
                                `${likeData.liked_users.slice(0, 2).join(', ')}${
                            likeData.liked_users.length > 2 ? ` and ${likeData.liked_users.length - 2} others` : ''
                        }` :
                                '';

                            // Determine initial like state
                            const likeClass = likeData.liked ? 'text-primary' : '';
                            const likeText = likeData.liked ? 'Liked' : 'Like';

                            const eventHtml = `
                    <div class="mb-4 event-container" data-event-id="${event.event_id}">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="profile-photo" style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; margin-right: 10px;">
                                        ${event.photo ? `<img src="${event.photo}" alt="Profile Photo" class="img-fluid">` : 
                                        `<div class="profile-initials d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #ccc; font-size: 18px; color: #fff;">
                                            ${event.fullname ? event.fullname.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : '??'}
                                        </div>`}
                                    </div>
                                    <div>
                                        <h6 class="mb-1">${event.fullname || 'Unknown User'}</h6>
                                        <small class="text-muted">${new Date(event.created_at).toLocaleString()}</small>
                                    </div>
                                </div>

                                <p class="text-muted">${event.event_description || ''}</p>

                                ${event.event_image_url ? 
                                    `<iframe src="${event.event_image_url}" class="responsive-iframe img-fluid rounded mb-3" frameborder="0" allow="autoplay" sandbox="allow-scripts allow-same-origin"></iframe>` : 
                                    ''}

                                <div class="d-flex align-items-center mt-3 text-muted" id="like-button-${event.event_id}" data-user-id="<?php echo $application_number; ?>">
                                    <div class="pointer" onclick="toggleLike(${event.event_id})">
                                        <i id="thumbs-up-icon-${event.event_id}" class="bi bi-hand-thumbs-up me-1 ${likeClass}"></i>
                                        <span id="like-text-${event.event_id}">${likeText}</span>
                                        <span class="ms-2" id="like-count-${event.event_id}">${likeData.like_count}</span>
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