<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}
if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

if ($role != 'Admin' && $role != 'Offline Manager') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
$formattedTodayDate = date('Y-m-d');
$query = "
SELECT p.sl_no, p.user_id, p.punch_in, p.ip_address, p.recorded_by, p.gps_location,
       COALESCE(m.fullname, s.studentname) AS user_name,
       COALESCE(m.filterstatus, s.filterstatus) AS status,
       s.category AS category,
       s.class AS class,
       m.engagement AS engagement
FROM attendance p
LEFT JOIN rssimyaccount_members m ON p.user_id = m.associatenumber
LEFT JOIN rssimyprofile_student s ON p.user_id = s.student_id
WHERE DATE(p.punch_in) = '$formattedTodayDate'
ORDER BY p.punch_in DESC";

$result = pg_query($con, $query);
$resultArr = pg_fetch_all($result);
if (!$result) {
    echo "An error occurred.\n";
    exit;
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>AttendX</title>

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
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <style>
        .blink-text {
            color: red;
            animation: blinkAnimation 1s infinite;
        }

        @keyframes blinkAnimation {

            0%,
            50% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        #success-toast {
            display: none;
            z-index: 1050;
        }
    </style>


</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>

    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>AttendX</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Class details</a></li>
                    <li class="breadcrumb-item active">AttendX</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <audio id="notification-sound" src="../img/success.mp3"></audio>
                    <div id="success-toast" class="toast position-fixed top-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
                        <div class="toast-header">
                            <img src="../img/success.png" class="rounded mr-2" alt="success">&nbsp;
                            <strong class="me-auto">Notification</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            Attendance for <span id='success-username'></span> (<span id='success-userid'></span>) recorded successfully!
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <div class="col" style="display: inline-block; width:100%; text-align:right">
                                <a href="in_out_tracker.php" target="_blank" title="Set Goals Now">In-out Tracker</a>
                            </div>
                            <div id="qr-reader" style="width:800px"></div>
                            <div id="qr-reader-results"></div>
                            <br>
                            <?php
                            $formattedToday = date('F j, Y'); // Format the current date in a user-friendly way
                            echo '<div class="notification">You are viewing data for <span class="blink-text">' . $formattedToday . '</span></div>';
                            ?>
                            <!-- HTML Table -->
                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th scope="col">User ID</th>
                                            <th scope="col">User Name</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Timestamp</th>
                                            <th scope="col">IP Address</th>
                                            <th scope="col">GPS location</th>
                                        </tr>
                                    </thead>
                                    <?php
                                    echo '<tbody>';
                                    echo '<tr style="display:none" id="last-row"></tr>';
                                    if ($resultArr != null) {
                                        foreach ($resultArr as $array) {
                                            echo '<tr>';
                                            echo '<td>' . $array['user_id'] . '</td>';
                                            echo '<td>' . $array['user_name'] . '</td>';
                                            // echo '<td>' . $array['category'] . $array['engagement'] . '</td>';
                                            echo '<td>' . $array['category'] . $array['engagement'] . (isset($array['class']) ? '/' . $array['class'] : '') . '</td>';
                                            echo '<td>' . $array['status'] . '</td>';
                                            echo '<td>' . ($array['punch_in'] ? date('d/m/Y h:i:s a', strtotime($array['punch_in'])) : 'Not Available') . '</td>';
                                            echo '<td>' . $array['ip_address'] . '</td>';
                                            echo '<td>' . $array['gps_location'] . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="8">No records found.</td></tr>';
                                    }
                                    echo '</tbody>';
                                    ?>
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

    <script>
        var resultContainer = document.getElementById('qr-reader-results');
        var lastResult, lastScanTime = 0;

        function onScanSuccess(decodedText, decodedResult) {
            var diff = (Number(new Date()) - lastScanTime)
            if (decodedText !== lastResult || diff >= 60000) {
                lastResult = decodedText;
                lastScanTime = Number(new Date());
                var segments = decodedText.split("get_id=");
                resultContainer.innerHTML = "";
                if (segments.length > 1) {
                    submitAttendance(segments[1]);
                } else {
                    var html = `<div class="result">User ID not found in QR Code</div>`;
                    resultContainer.innerHTML = html;
                }
            }
        }

        var html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", {
                fps: 10,
                qrbox: 400,
                disableFlip: true,
            });
        html5QrcodeScanner.render(onScanSuccess);

        function playNotificationSound() {
            var notificationSound = document.getElementById('notification-sound');
            notificationSound.play();
        }

        function showSuccessToast(userId, userName) {
            var successToast = document.getElementById('success-toast');
            successToast.style.display = 'block';
            var useridEl = document.getElementById('success-userid');
            useridEl.innerHTML = userId;
            var usernameEl = document.getElementById('success-username');
            usernameEl.innerHTML = userName;

            // Hide the toast after a few seconds (adjust the time as needed)
            setTimeout(function() {
                successToast.style.display = 'none';
            }, 3000); // 3000 milliseconds = 3 seconds (adjust the time as needed)
        }

        var latitude; // Variable to store latitude
        var longitude; // Variable to store longitude

        function getLocation() {
            return new Promise((resolve, reject) => {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        position => {
                            latitude = position.coords.latitude; // Store latitude in the variable
                            longitude = position.coords.longitude; // Store longitude in the variable
                            resolve(); // Resolve the promise when location is retrieved
                        },
                        error => {
                            alert("Error getting location: " + error.message);
                            reject(error); // Reject the promise on error
                        }
                    );
                } else {
                    alert("Geolocation is not supported by this browser.");
                    reject(new Error("Geolocation not supported"));
                }
            });
        }

        async function submitAttendance(userId) {
            try {
                // Get latitude and longitude values
                await getLocation();

                var data = new FormData();
                data.set("userId", userId);
                data.set("form-type", "attendance");
                data.set("latitude", latitude);
                data.set("longitude", longitude);

                const response = await fetch("payment-api.php", {
                    method: 'POST',
                    body: data
                });

                const result = await response.json();

                if (result.error) {
                    alert("Error recording attendance. Please try again later or contact support.");
                } else {
                    addRowInAttendanceTable(result);
                    playNotificationSound(); // Play notification sound on successful form submission
                    showSuccessToast(result.userId, result.userName); // Show the success notification toast
                }
            } catch (error) {
                console.error(error);
            }
        }

        function addRowInAttendanceTable(attendanceRow) {
            var lastTr = document.getElementById('last-row');
            var newTr = document.createElement('tr');

            for (var key of ["userId", "userName", "category", "status", "punchIn", "ipAddress", "gpsLocation"]) {
                var td = document.createElement('td');

                // If the key is "category" or "class", concatenate them with a slash (/) in between if "class" is available
                if (key === "category" && attendanceRow["class"]) {
                    td.innerText = attendanceRow["category"] + '/' + attendanceRow["class"];
                } else {
                    td.innerText = attendanceRow[key];
                }

                newTr.appendChild(td);
            }

            lastTr.insertAdjacentElement("afterend", newTr);
        }
    </script>

</body>

</html>