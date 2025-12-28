<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}
validation();
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
<?php
// Fetch active office locations
$locations_result = pg_query($con, "SELECT name, latitude, longitude FROM office_locations WHERE is_active = TRUE");

$officeLocations = [];
while ($row = pg_fetch_assoc($locations_result)) {
    $officeLocations[] = [
        "name" => $row['name'],
        "latitude" => (float)$row['latitude'],
        "longitude" => (float)$row['longitude']
    ];
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

    <title>QR Attendance Scanner</title>

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
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>QR Attendance Scanner</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="attendx.php">AttendX</a></li>
                    <li class="breadcrumb-item active">QR Attendance Scanner</li>
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
                            <div id="qr-reader" style="width:100%"></div>
                            <div id="loading" class="text-center">
                                <div class="spinner-border" role="status"></div>
                                <div>Loading</div>
                            </div>
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
  <script src="../assets_new/js/text-refiner.js?v=1.1.0"></script>
    <script src="https://unpkg.com/mqtt@5.0.1/dist/mqtt.min.js"></script>
    <script>
        var enableLocationCheck = true;
        var isFullScreen = true;
        var lastResult, lastScanTime = 0;
        var latitude; // Variable to store latitude
        var longitude; // Variable to store longitude
        const sameQRScanInterval = 60000; // Threshold value for same QR code scan in milliseconds
        var resultContainer = document.getElementById('qr-reader-results');

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

        function setUpCameraForScan() {
            var qrBoxSize = 400
            const width = window.innerWidth
            const height = window.innerHeight
            const aspectRatio = width / height
            const reverseAspectRatio = height / width

            const mobileAspectRatio = reverseAspectRatio > 1.5 ?
                reverseAspectRatio + (reverseAspectRatio * 12 / 100) :
                reverseAspectRatio

            const videoConstraints = {
                facingMode: 'environment',
                aspectRatio: width < 600 ?
                    mobileAspectRatio : aspectRatio,
            }
            qrBoxSize = Math.round(width * 0.5)
            console.log('qrBoxSize', qrBoxSize)

            const html5QrCode = new Html5Qrcode("qr-reader", {
                formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE]
            });
            const config = {
                fps: 10,
                qrbox: qrBoxSize,
                disableFlip: true,
                videoConstraints: null
            };
            html5QrCode.start({
                facingMode: "environment"
            }, config, onScanSuccess);
        }

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

        function deg2rad(deg) {
            return deg * (Math.PI / 180)
        }

        function getDistance(lat, lon, office_lat, office_lon) {
            var R = 6371; // Radius of the earth in km
            var dLat = deg2rad(lat - office_lat); // deg2rad below
            var dLon = deg2rad(lon - office_lon);
            var a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(deg2rad(office_lat)) * Math.cos(deg2rad(lat)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            var d = R * c; // Distance in km
            return d;
        }

        function showLoading() {
            var loadingEl = document.getElementById('loading');
            loadingEl.style.display = 'block';
        }

        function hideLoading() {
            var loadingEl = document.getElementById('loading');
            loadingEl.style.display = 'none';
        }

        var elementStyleBackup = {
            width: "",
            height: "",
            position: "",
            top: "",
            left: "",
            zIndex: "",
        }
        const elementStyleFullScreen = {
            width: "100%",
            height: "100%",
            position: "absolute",
            top: "0",
            left: "0",
            zIndex: "1000",
        }

        function setElementBackToNormal() {
            var elem = document.getElementById("qr-reader");
            if (!elem) return;
            if (!isFullScreen) return;
            elem.style.width = elementStyleBackup.width;
            elem.style.height = elementStyleBackup.height;
            elem.style.position = elementStyleBackup.position;
            elem.style.top = elementStyleBackup.top;
            elem.style.left = elementStyleBackup.left;
            elem.style.zIndex = elementStyleBackup.zIndex;
            isFullScreen = false;
        }

        function setElementToFullScreen() {
            var elem = document.getElementById("qr-reader");
            if (!elem) return;
            if (isFullScreen) return;
            elementStyleBackup = {
                width: elem.style.width,
                height: elem.style.height,
                position: elem.style.position,
                top: elem.style.top,
                left: elem.style.left,
                zIndex: elem.style.zIndex,
            }
            elem.style.width = elementStyleFullScreen.width;
            elem.style.height = elementStyleFullScreen.height;
            elem.style.position = elementStyleFullScreen.position;
            elem.style.top = elementStyleFullScreen.top;
            elem.style.left = elementStyleFullScreen.left;
            elem.style.zIndex = elementStyleFullScreen.zIndex;
            // div {width: 100%; height: 100%;}
            // div {position: absolute; top: 0; left: 0;}
            // z-index: 1000;
            isFullScreen = true;
        }

        function toggleFullScreen() {
            if (isFullScreen) {
                setElementBackToNormal();
                setUpCameraForScan();
            } else {
                setElementToFullScreen();
                setUpCameraForScan();
            }
        }

        function checkIfAtOffice() {

            // Get latitude and longitude values
            console.log(latitude, longitude);
            if (!latitude || !longitude) {
                alert("Error getting location. Please try again later or contact support.");
                return;
            }

            var officeLocations = <?php echo json_encode($officeLocations); ?>; // Array of office locations from PHP
            var tolerance = 0.05; // 50 meters in KM
            var atOffice = false;
            var nearestLocation = null;
            var nearestDistance = Infinity;

            // Check each office location
            for (var i = 0; i < officeLocations.length; i++) {
                var loc = officeLocations[i];
                var distance = getDistance(latitude, longitude, loc.latitude, loc.longitude);
                console.log("Distance to", loc.name, "is:", distance, "KM");

                if (distance < nearestDistance) {
                    nearestDistance = distance;
                    nearestLocation = loc;
                }

                if (distance <= tolerance) {
                    atOffice = true;
                    nearestLocation = loc;
                    break; // User is within tolerance, no need to check further
                }
            }

            if (!atOffice) {
                hideLoading();
                nearestDistance = Math.round(nearestDistance * 100) / 100;
                alert(`Seems like you are not in office (${nearestDistance} KM away from ${nearestLocation.name}). Please try again when you are in office.`);
                return;
            } else {
                allowAttendance = true;
                hideLoading();
                setUpCameraForScan();
            }
        }

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


        async function submitAttendance(userId) {
            if (!allowAttendance) {
                alert("You are not in office. Please try again when you are in office.");
                return;
            }
            try {
                var data = new FormData();
                data.set("userId", userId);
                data.set("recorded_by", "<?php echo $associatenumber; ?>");
                data.set("form-type", "attendance");
                data.set("latitude", latitude);
                data.set("longitude", longitude);

                const response = await fetch("payment-api.php", {
                    method: 'POST',
                    body: data
                });

                const result = await response.json();

                if (result.error) {
                    alert(result.error); // Handles both general errors and absconding
                } else {
                    addRowInAttendanceTable(result);
                    playNotificationSound(); // Play notification sound on successful form submission
                    showSuccessToast(result.userId, result.userName); // Show the success notification toast
                    publishAttendanceEvent(result); // Publish the attendance event on MQTT server
                }
            } catch (error) {
                console.error(error);
            }
        }

        function addRowInAttendanceTable(attendanceRow) {
            var lastTr = document.getElementById('last-row');
            var newTr = document.createElement('tr');

            for (var key of ["userId", "userName", "category", "status", "timestamp", "ipAddress", "gpsLocation"]) {
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


        var allowAttendance = !enableLocationCheck;

        if (enableLocationCheck) {
            console.log("getting location")
            showLoading();
            getLocation().then(() => {
                hideLoading();
                checkIfAtOffice();
            });
        } else {
            console.log("getting location")
            getLocation().then(() => {
                console.log("getting location 1")
                hideLoading();
                setUpCameraForScan();
            });
        }

        // Publish events on MQTT server
        const mqttClient = mqtt.connect('wss://mqtt.rssi.in')
        const TOPIC = "attendance-record-events";
        mqttClient.on("connect", () => {
            console.log("MQTT client connected.");
            //TODO: show notification on successful connection
        })
        mqttClient.on('error', (error) => {
            console.log("MQTT client ERROR."); // never fires
            //add new code to show the connection NOT etablished.
        });

        function publishAttendanceEvent(record) {
            if (!mqttClient.connected) {
                console.log("MQTT client not connected. Skipping MQTT publish.");
                return;
            };
            var message = JSON.stringify(record);
            mqttClient.publish(TOPIC, message);
            console.log("MQTT message published on topic:", TOPIC, "message:", message);
        }
    </script>

</body>

</html>