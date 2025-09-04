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
                                                <h5 class="card-title">Instagram Feed</h5>
                                                <blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/DNm26zcBn3d/?utm_source=ig_embed&amp;utm_campaign=loading" data-instgrm-version="14" style=" background:#FFF; border:0; border-radius:3px; box-shadow:0 0 1px 0 rgba(0,0,0,0.5),0 1px 10px 0 rgba(0,0,0,0.15); margin: 1px; max-width:540px; min-width:326px; padding:0; width:99.375%; width:-webkit-calc(100% - 2px); width:calc(100% - 2px);">
                                                    <div style="padding:16px;"> <a href="https://www.instagram.com/p/DNm26zcBn3d/?utm_source=ig_embed&amp;utm_campaign=loading" style=" background:#FFFFFF; line-height:0; padding:0 0; text-align:center; text-decoration:none; width:100%;" target="_blank">
                                                            <div style=" display: flex; flex-direction: row; align-items: center;">
                                                                <div style="background-color: #F4F4F4; border-radius: 50%; flex-grow: 0; height: 40px; margin-right: 14px; width: 40px;"></div>
                                                                <div style="display: flex; flex-direction: column; flex-grow: 1; justify-content: center;">
                                                                    <div style=" background-color: #F4F4F4; border-radius: 4px; flex-grow: 0; height: 14px; margin-bottom: 6px; width: 100px;"></div>
                                                                    <div style=" background-color: #F4F4F4; border-radius: 4px; flex-grow: 0; height: 14px; width: 60px;"></div>
                                                                </div>
                                                            </div>
                                                            <div style="padding: 19% 0;"></div>
                                                            <div style="display:block; height:50px; margin:0 auto 12px; width:50px;"><svg width="50px" height="50px" viewBox="0 0 60 60" version="1.1" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink">
                                                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                                        <g transform="translate(-511.000000, -20.000000)" fill="#000000">
                                                                            <g>
                                                                                <path d="M556.869,30.41 C554.814,30.41 553.148,32.076 553.148,34.131 C553.148,36.186 554.814,37.852 556.869,37.852 C558.924,37.852 560.59,36.186 560.59,34.131 C560.59,32.076 558.924,30.41 556.869,30.41 M541,60.657 C535.114,60.657 530.342,55.887 530.342,50 C530.342,44.114 535.114,39.342 541,39.342 C546.887,39.342 551.658,44.114 551.658,50 C551.658,55.887 546.887,60.657 541,60.657 M541,33.886 C532.1,33.886 524.886,41.1 524.886,50 C524.886,58.899 532.1,66.113 541,66.113 C549.9,66.113 557.115,58.899 557.115,50 C557.115,41.1 549.9,33.886 541,33.886 M565.378,62.101 C565.244,65.022 564.756,66.606 564.346,67.663 C563.803,69.06 563.154,70.057 562.106,71.106 C561.058,72.155 560.06,72.803 558.662,73.347 C557.607,73.757 556.021,74.244 553.102,74.378 C549.944,74.521 548.997,74.552 541,74.552 C533.003,74.552 532.056,74.521 528.898,74.378 C525.979,74.244 524.393,73.757 523.338,73.347 C521.94,72.803 520.942,72.155 519.894,71.106 C518.846,70.057 518.197,69.06 517.654,67.663 C517.244,66.606 516.755,65.022 516.623,62.101 C516.479,58.943 516.448,57.996 516.448,50 C516.448,42.003 516.479,41.056 516.623,37.899 C516.755,34.978 517.244,33.391 517.654,32.338 C518.197,30.938 518.846,29.942 519.894,28.894 C520.942,27.846 521.94,27.196 523.338,26.654 C524.393,26.244 525.979,25.756 528.898,25.623 C532.057,25.479 533.004,25.448 541,25.448 C548.997,25.448 549.943,25.479 553.102,25.623 C556.021,25.756 557.607,26.244 558.662,26.654 C560.06,27.196 561.058,27.846 562.106,28.894 C563.154,29.942 563.803,30.938 564.346,32.338 C564.756,33.391 565.244,34.978 565.378,37.899 C565.522,41.056 565.552,42.003 565.552,50 C565.552,57.996 565.522,58.943 565.378,62.101 M570.82,37.631 C570.674,34.438 570.167,32.258 569.425,30.349 C568.659,28.377 567.633,26.702 565.965,25.035 C564.297,23.368 562.623,22.342 560.652,21.575 C558.743,20.834 556.562,20.326 553.369,20.18 C550.169,20.033 549.148,20 541,20 C532.853,20 531.831,20.033 528.631,20.18 C525.438,20.326 523.257,20.834 521.349,21.575 C519.376,22.342 517.703,23.368 516.035,25.035 C514.368,26.702 513.342,28.377 512.574,30.349 C511.834,32.258 511.326,34.438 511.181,37.631 C511.035,40.831 511,41.851 511,50 C511,58.147 511.035,59.17 511.181,62.369 C511.326,65.562 511.834,67.743 512.574,69.651 C513.342,71.625 514.368,73.296 516.035,74.965 C517.703,76.634 519.376,77.658 521.349,78.425 C523.257,79.167 525.438,79.673 528.631,79.82 C531.831,79.965 532.853,80.001 541,80.001 C549.148,80.001 550.169,79.965 553.369,79.82 C556.562,79.673 558.743,79.167 560.652,78.425 C562.623,77.658 564.297,76.634 565.965,74.965 C567.633,73.296 568.659,71.625 569.425,69.651 C570.167,67.743 570.674,65.562 570.82,62.369 C570.966,59.17 571,58.147 571,50 C571,41.851 570.966,40.831 570.82,37.631"></path>
                                                                            </g>
                                                                        </g>
                                                                    </g>
                                                                </svg></div>
                                                            <div style="padding-top: 8px;">
                                                                <div style=" color:#3897f0; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:550; line-height:18px;">View this post on Instagram</div>
                                                            </div>
                                                            <div style="padding: 12.5% 0;"></div>
                                                            <div style="display: flex; flex-direction: row; margin-bottom: 14px; align-items: center;">
                                                                <div>
                                                                    <div style="background-color: #F4F4F4; border-radius: 50%; height: 12.5px; width: 12.5px; transform: translateX(0px) translateY(7px);"></div>
                                                                    <div style="background-color: #F4F4F4; height: 12.5px; transform: rotate(-45deg) translateX(3px) translateY(1px); width: 12.5px; flex-grow: 0; margin-right: 14px; margin-left: 2px;"></div>
                                                                    <div style="background-color: #F4F4F4; border-radius: 50%; height: 12.5px; width: 12.5px; transform: translateX(9px) translateY(-18px);"></div>
                                                                </div>
                                                                <div style="margin-left: 8px;">
                                                                    <div style=" background-color: #F4F4F4; border-radius: 50%; flex-grow: 0; height: 20px; width: 20px;"></div>
                                                                    <div style=" width: 0; height: 0; border-top: 2px solid transparent; border-left: 6px solid #f4f4f4; border-bottom: 2px solid transparent; transform: translateX(16px) translateY(-4px) rotate(30deg)"></div>
                                                                </div>
                                                                <div style="margin-left: auto;">
                                                                    <div style=" width: 0px; border-top: 8px solid #F4F4F4; border-right: 8px solid transparent; transform: translateY(16px);"></div>
                                                                    <div style=" background-color: #F4F4F4; flex-grow: 0; height: 12px; width: 16px; transform: translateY(-4px);"></div>
                                                                    <div style=" width: 0; height: 0; border-top: 8px solid #F4F4F4; border-left: 8px solid transparent; transform: translateY(-4px) translateX(8px);"></div>
                                                                </div>
                                                            </div>
                                                            <div style="display: flex; flex-direction: column; flex-grow: 1; justify-content: center; margin-bottom: 24px;">
                                                                <div style=" background-color: #F4F4F4; border-radius: 4px; flex-grow: 0; height: 14px; margin-bottom: 6px; width: 224px;"></div>
                                                                <div style=" background-color: #F4F4F4; border-radius: 4px; flex-grow: 0; height: 14px; width: 144px;"></div>
                                                            </div>
                                                        </a>
                                                        <p style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; line-height:17px; margin-bottom:0; margin-top:8px; overflow:hidden; padding:8px 0 7px; text-align:center; text-overflow:ellipsis; white-space:nowrap;"><a href="https://www.instagram.com/p/DNm26zcBn3d/?utm_source=ig_embed&amp;utm_campaign=loading" style=" color:#c9c8cd; font-family:Arial,sans-serif; font-size:14px; font-style:normal; font-weight:normal; line-height:17px; text-decoration:none;" target="_blank">A post shared by RSSI NGO (@rssi.in)</a></p>
                                                    </div>
                                                </blockquote>
                                                <script async src="//www.instagram.com/embed.js"></script>
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