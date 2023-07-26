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
} ?>

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
                    <div id="success-toast" class="toast position-absolute top-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
                        <div class="toast-header">
                            <strong class="me-auto">Notification</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            <img src="../img/success.png" class="rounded mr-2" alt="success"> Attendance for <span id='success-userid'></span> recorded successfully!
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
                                // Adjust qrbox size dynamically based on screen width
                                function adjustQrBoxSize() {
                                    var qrReaderDiv = document.getElementById('qr-reader');
                                    var screenWidth = window.innerWidth;
                                    var qrboxSize = Math.min(screenWidth * 0.9, 800); // Maximum width is 800px or 90% of screen width
                                    qrReaderDiv.style.width = qrboxSize + 'px';
                                }

                                // Call the function once on page load
                                adjustQrBoxSize();
                                // Re-adjust the qrbox size when the window is resized
                                window.addEventListener('resize', adjustQrBoxSize);

                                var html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", {
                                    fps: 10,
                                    disableFlip: true,
                                });
                                html5QrcodeScanner.render(onScanSuccess);
                                // var html5QrcodeScanner = new Html5QrcodeScanner(
                                //     "qr-reader", {
                                //         fps: 10,
                                //         qrbox: 400,
                                //         disableFlip: true,
                                //     });
                                // html5QrcodeScanner.render(onScanSuccess);

                                function playNotificationSound() {
                                    var notificationSound = document.getElementById('notification-sound');
                                    notificationSound.play();
                                }

                                function showSuccessToast(userId) {
                                    var successToast = document.getElementById('success-toast');
                                    successToast.style.display = 'block';
                                    var useridEl = document.getElementById('success-userid');
                                    useridEl.innerHTML = userId

                                    // Hide the toast after a few seconds (adjust the time as needed)
                                    setTimeout(function() {
                                        successToast.style.display = 'none';
                                    }, 3000); // 3000 milliseconds = 3 seconds (adjust the time as needed)
                                }

                                function addRowInAttendanceTable(attendanceRow) {
                                    var lastTr = document.getElementById('last-row')
                                    var newTr = document.createElement('tr')
                                    for (var key of ["userId", "userName", "status", "punchIn"]) {
                                        var td = document.createElement('td')
                                        td.innerText = attendanceRow[key]
                                        newTr.appendChild(td)
                                    }
                                    lastTr.insertAdjacentElement("afterend", newTr)
                                }

                                function submitAttendance(userId) {
                                    var data = new FormData()
                                    data.set("userId", userId)
                                    data.set("form-type", "attendance")
                                    fetch("payment-api.php", {
                                            method: 'POST',
                                            body: data
                                        })
                                        .then(response => response.json())
                                        .then(result => {
                                            if (result.error) {

                                                alert("Errorrecording attendance. Please try again later or contact support.")
                                            } else {
                                                addRowInAttendanceTable(result)
                                                playNotificationSound(); // Play notification sound on successful form submission
                                                showSuccessToast(result.userId); // Show the success notification toast
                                            }
                                        })
                                }
                            </script>
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
                                            <th scope="col">Status</th>
                                            <th scope="col">Timestamp</th>
                                        </tr>
                                    </thead>
                                    <?php
                                    echo '<tbody>';
                                    echo '<tr style="display:none" id="last-row"></tr>';
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

</body>

</html>