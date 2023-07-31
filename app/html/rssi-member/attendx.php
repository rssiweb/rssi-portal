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
<!DOCTYPE html>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <style>
        .card-link {
            color: inherit;
            /* Inherit color from parent */
        }

        .card-body {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .icon {
            font-size: 60px;
            color: #444444;
        }

        .error{
            color: red;
            text-align: center;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>AttendX</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Class details</a></li>
                    <li class="breadcrumb-item"><a href="#">AttendX</a></li>
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

                            <div class="container mt-5">
                                <div class="row">
                                    <div class="col-md-4">
                                        <a href="scan.php" target="_blank" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-qr-code-scan icon"></i>
                                                    <h5 class="card-title mt-3">Scanning</h5>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="in_out_tracker.php" target="_blank" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-speedometer2 icon"></i>
                                                    <h5 class="card-title mt-3">Daily In-Out Report</h5>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="monthly_attd_report.php" target="_blank" class="card-link">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <i class="bi bi-calendar-check icon"></i>
                                                    <h5 class="card-title mt-3">Monthly Attendance Report</h5>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            <div class="col" style="display: inline-block; width:100%; text-align:right">
                                <a href="in_out_tracker.php" target="_blank" title="Set Goals Now">In-out Tracker</a>
                            </div>
                            <div id="qr-reader" style="width:100%;"></div>
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
                </div>
            </div><!-- End Reports -->
        </section>
    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        var enableLocationCheck = false
        var isFullScreen = false;
        var lastResult, lastScanTime = 0;
        var latitude; // Variable to store latitude
        var longitude; // Variable to store longitude
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
                    var html = `<div class="error">User ID not found in QR Code</div>`;
                    resultContainer.innerHTML = html;
                }
            }
        }

        function setUpCameraForScan(){
            var qrBoxSize = 400
            const width = window.innerWidth
            const height = window.innerHeight
            const aspectRatio = width / height
            const reverseAspectRatio = height / width

            const mobileAspectRatio = reverseAspectRatio > 1.5
                ? reverseAspectRatio + (reverseAspectRatio * 12 / 100)
                : reverseAspectRatio

            const videoConstraints = {
                facingMode: 'environment',
                aspectRatio: width < 600
                    ? mobileAspectRatio
                    : aspectRatio,
            }
            qrBoxSize = Math.round(width * 0.5)
            console.log('qrBoxSize', qrBoxSize)
            
            const html5QrCode = new Html5Qrcode("qr-reader", { formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ] });
            const config = {
                fps: 10, 
                qrbox: qrBoxSize, 
                disableFlip: true, 
                videoConstraints: null
            };
            html5QrCode.start({ facingMode: "user" }, config, onScanSuccess);
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
            return deg * (Math.PI/180)
        }

        function getDistance(lat,lon,office_lat, office_lon){
            var R = 6371; // Radius of the earth in km
            var dLat = deg2rad(lat-office_lat);  // deg2rad below
            var dLon = deg2rad(lon-office_lon); 
            var a = 
              Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(deg2rad(office_lat)) * Math.cos(deg2rad(lat)) * 
              Math.sin(dLon/2) * Math.sin(dLon/2)
              ; 
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
            var d = R * c; // Distance in km
            return d;
        }
          
        
        function showLoading(){
            loading = true;
            var loadingEl = document.getElementById('loading');
            loadingEl.style.display = 'block';
        }
        function hideLoading(){
            loading = false;
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

        function setElementBackToNormal(){
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

        function setElementToFullScreen(){
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
        function toggleFullScreen(){
            if (isFullScreen) {
                setElementBackToNormal();
                setUpCameraForScan();
            } else {
                setElementToFullScreen();
                setUpCameraForScan();
            }
        }

        async function checkIfAtOffice(){
            // Get latitude and longitude values
            loading = true;
            await getLocation();
            console.log(latitude, longitude);
            if (!latitude || !longitude) {
                hideLoading();
                alert("Error getting location. Please try again later or contact support.");
                return;
            }
            // restrict the location to the office location (50m radius) of a set point 
            var officeLocation = {
                //  RSSI LKO office
                latitude: 26.8659136,
                longitude: 81.0158809
            };
            var distance = getDistance(latitude, longitude, officeLocation.latitude, officeLocation.longitude);
            console.log("distance is:", distance);
            tolerance = 0.05; // 50m
            if (distance > tolerance) {
                hideLoading();
                distance = Math.round(distance * 100) / 100;
                alert(`Seems like you are not in office (${distance} KM away from office). Please try again when you are in office.`);
                return;
            }else{
                allowAttendance = true;
                hideLoading();
                setUpCameraForScan();
            }
        }
        

        async function submitAttendance(userId) {
            if(!allowAttendance){
                alert("You are not in office. Please try again when you are in office.");
                return;
            }
            try {
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

        var allowAttendance = !enableLocationCheck;
        if (enableLocationCheck) {
            showLoading();
            checkIfAtOffice();
        } else {
            hideLoading();
            setUpCameraForScan();
        }
    </script>

</body>

</html>
