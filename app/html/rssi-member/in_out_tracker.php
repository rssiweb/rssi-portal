<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}
validation();

$id = isset($_GET['get_aid']) ? strtoupper($_GET['get_aid']) : null;
$date = isset($_GET['get_date']) ? $_GET['get_date'] : '';

$query = "
WITH PunchInOut AS (
    SELECT
        a.user_id,
		a.status,
        DATE(a.punch_in) AS punch_date,
        MIN(a.punch_in) AS punch_in,
        CASE
            WHEN COUNT(*) = 1 THEN NULL
            ELSE MAX(a.punch_in)
        END AS punch_out
    FROM attendance a
    GROUP BY a.user_id, a.status, DATE(a.punch_in)
)
SELECT
    p.user_id,
	p.status,
    COALESCE(m.fullname, s.studentname) AS user_name,
    s.category AS category,
    s.class AS class,
    m.engagement AS engagement,
    p.punch_in,
    p.punch_out
FROM PunchInOut p
LEFT JOIN rssimyaccount_members m ON p.user_id = m.associatenumber
LEFT JOIN rssimyprofile_student s ON p.user_id = s.student_id";

// Now you can use the $query variable to execute the SQL query using your preferred database connection method.


// Add conditions based on user input
if (!empty($id) && !empty($date)) {
    // Case 4: If both user_id and date are provided
    $formattedDate = date('Y-m-d', strtotime($date));
    $query .= " WHERE p.user_id = '$id' AND DATE(p.punch_in) = '$formattedDate'";
} elseif (!empty($id) && empty($date)) {
    // Case 2: If only user_id is provided
    $query .= " WHERE p.user_id = '$id'";
} elseif (empty($id) && !empty($date)) {
    // Case 1: If only date is provided
    $formattedDate = date('Y-m-d', strtotime($date));
    $query .= " WHERE DATE(p.punch_in) = '$formattedDate'";
} else {
    // Case 3: If both user_id and date are null, show data based on today's date
    $formattedTodayDate = date('Y-m-d');
    $query .= " WHERE DATE(p.punch_in) = '$formattedTodayDate'";
}

$query .= " ORDER BY p.punch_in DESC";

// Add a variable to check if today's data is being shown
$showingTodayData = false;

// Check if both $id and $date are null (Case 3)
if (empty($id) && empty($date)) {
    $showingTodayData = true;
}

$result = pg_query($con, $query);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);

// If $date is null or empty, use today's date
$date_count = $date ? $date : date('Y-m-d');

// Prepare the SQL query with placeholders
$querycount = "SELECT
    s.category AS category,
    COUNT(DISTINCT a.user_id) AS category_count,
    COUNT(DISTINCT CASE WHEN s.class = 'Nursery' THEN a.user_id END) AS nursery_count,
    COUNT(DISTINCT CASE WHEN s.class = 'LKG' THEN a.user_id END) AS lkg_count,
    COUNT(DISTINCT CASE WHEN s.class = 'UKG' THEN a.user_id END) AS ukg_count,
    COUNT(DISTINCT CASE WHEN s.class = '1' THEN a.user_id END) AS class_1_count,
    COUNT(DISTINCT CASE WHEN s.class = '2' THEN a.user_id END) AS class_2_count,
    COUNT(DISTINCT CASE WHEN s.class = '3' THEN a.user_id END) AS class_3_count,
    COUNT(DISTINCT CASE WHEN s.class IN ('4','5','6') THEN a.user_id END) AS class_4_5_6_count
FROM attendance a
LEFT JOIN rssimyprofile_student s ON a.user_id = s.student_id
WHERE DATE(a.punch_in) = COALESCE($1, DATE(a.punch_in))
GROUP BY s.category";

// Prepare the statement
$stmt = pg_prepare($con, "querycount", $querycount);

// Execute the prepared statement with the date parameter
$resultcount = pg_execute($con, "querycount", array($date_count));

// Check if the query was successful before fetching the results
if ($resultcount) {
    $resultArrcount = pg_fetch_all($resultcount);

    // Calculate the total count
    $totalCount = 0;
    foreach ($resultArrcount as $entry) {
        $totalCount += $entry['category_count'];
    }
} else {
    // Set default values if the query fails
    $resultArrcount = array();
    $totalCount = 0;
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

    <title>In-out tracker</title>

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
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" integrity="sha384-KyZXEAg3QhqLMpG8r+K/Sc6sWYS1/Jp6jz0c2i+cbS5J+d2G4n3ddN7jW5tM2Elk" crossorigin="anonymous"> -->

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

        .bg-success {
            background-color: #198754 !important;
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>In-out tracker</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Class details</a></li>
                    <li class="breadcrumb-item"><a href="attendx.php">AttendX</a></li>
                    <li class="breadcrumb-item active">In-out tracker</li>
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
                            <div class="alert alert-warning" id="status" role="alert">Connecting...</div>
                            <div class="row" style="display: flex; align-items: center;">
                                <div class="col-md-8 mb-3">
                                    <p>To customize the view result, please select a filter value.</p>
                                </div>
                                <div class="col-md-4" id="categoryCountSection" style="margin-left: auto;">
                                    <table class="table table-bordered table-sm" style="width: 20%; float: right;" id="summaryTable">
                                        <tbody>
                                            <tr>
                                                <td>Category</td>
                                                <td>Total</td>
                                                <td colspan="3">Class wise</td>
                                            </tr>
                                            <tr v-for="row in summaryRows">
                                                <td>{{ row.category || "Associate" }}</td>
                                                <td>{{ row.category_count }}</td>
                                                <template v-if="row.category == 'LG1'">
                                                    <td>{{ row.nursery_count }}</td>
                                                    <td>{{ row.lkg_count }}</td>
                                                    <td>{{ row.ukg_count }}</td>
                                                </template>
                                                <template v-else-if="row.category == 'LG2-A'">
                                                    <td>{{ row.class_1_count }}</td>
                                                    <td>{{ row.class_2_count }}</td>
                                                    <td></td>
                                                </template>
                                                <template v-else-if="row.category == 'LG2-B'">
                                                    <td>{{ row.class_3_count }}</td>
                                                    <td>{{ row.class_4_5_6_count }}</td>
                                                    <td></td>
                                                </template>
                                                <template v-else>
                                                    <td colspan="3"></td>
                                                </template>
                                            </tr>
                                            <tr>
                                                <td><b>Total:</b></td>
                                                <td colspan="4" id="totalCount">{{totalCount}}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <form action="" method="GET" class="row g-2 align-items-center">
                                <div class="row">
                                    <div class="col-12 col-sm-2">
                                        <div class="form-group">
                                            <input type="text" name="get_aid" id="get_aid" class="form-control" placeholder="User Id" value="<?php echo isset($_GET['get_aid']) ? htmlspecialchars($_GET['get_aid']) : ''; ?>">
                                            <small class="form-text text-muted">Enter User Id</small>
                                        </div>
                                    </div>

                                    <div class="col-12 col-sm-2">
                                        <div class="form-group">
                                            <input type="date" name="get_date" id="get_date" class="form-control" value="<?php echo isset($_GET['get_date']) ? htmlspecialchars($_GET['get_date']) : ''; ?>">
                                            <small class="form-text text-muted">Select Date</small>
                                        </div>
                                    </div>

                                    <div class="col-12 col-sm-2">
                                        <button type="submit" name="search_by_id" class="btn btn-success" style="outline: none;">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                    </div>
                                </div>

                            </form>

                            <?php if ($showingTodayData) {
                                $formattedToday = date('F j, Y'); // Format the current date in a user-friendly way
                            ?>

                                <div class="row align-items-center">
                                    <div class="col-6">
                                        <div class="notification">
                                            You are viewing data for <span class="blink-text"><?= $formattedToday ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6 text-end">
                                        <button id="syncLiveDataBtn" class="btn btn-danger btn-sm" onclick="showLoading()">
                                            Sync LIVE Data
                                        </button>
                                    </div>
                                </div>

                                <script>
                                    function showLoading() {
                                        var button = document.getElementById('syncLiveDataBtn');
                                        button.innerHTML = 'Loading...';
                                        button.disabled = true;
                                        setTimeout(function() {
                                            location.reload();
                                        }, 1000); // Adjust the time (in milliseconds) as needed
                                    }
                                </script>

                            <?php } ?>


                            <!-- HTML Table -->
                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th scope="col">User ID</th>
                                            <th scope="col">User Name</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">Class</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Punch In</th>
                                            <th scope="col">Punch Out</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($resultArr != null) {
                                            foreach ($resultArr as $array) {
                                                echo '<tr id="' . $array['user_id'] . '">';
                                                echo '<td>' . $array['user_id'] . '</td>';
                                                echo '<td>' . $array['user_name'] . '</td>';
                                                // echo '<td>' . $array['category'] . $array['engagement'] . (isset($array['class']) ? '-' . $array['class'] : '') . '</td>';
                                                echo '<td>' . $array['category'] . $array['engagement'] . '</td>';
                                                echo '<td>' . $array['class'] . '</td>';
                                                echo '<td>' . $array['status'] . '</td>';
                                                echo '<td>' . ($array['punch_in'] ? date('d/m/Y h:i:s a', strtotime($array['punch_in'])) : 'Not Available') . '</td>';
                                                echo '<td>' . ($array['punch_out'] ? date('d/m/Y h:i:s a', strtotime($array['punch_out'])) : 'Not Available') . '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr id="no-record"><td colspan="7">No records found.</td></tr>';
                                        }
                                        ?>
                                        <!-- <tr style="display:none" id="last-row"></tr> -->
                                    </tbody>
                                    <tfoot>
                                        <!-- Define last row with display:none -->
                                        <tr id="last-row" style="display:none"></tr>
                                    </tfoot>
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
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    paging: false,
                    // other options...
                });
            <?php endif; ?>
        });
    </script>

    <!-- <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script> -->

    <!-- Alternative CDNs for Vue.js -->
    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.3.4/vue.global.prod.min.js"></script> -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script> -->

    <script>
        const {
            createApp,
            ref
        } = Vue
        const summaryApp = createApp({
            setup() {
                const summaryRows = ref(<?php echo json_encode($resultArrcount); ?>)
                const totalCount = ref(<?php echo $totalCount; ?>)
                const addRow = (attendanceRow) => {
                    // update summary, by category
                    console.log(attendanceRow)
                    const category = attendanceRow.category
                    const summaryRow = summaryRows.value.find(row => row.category == category)
                    if (summaryRow) {
                        summaryRow.category_count += 1
                        if (attendanceRow.class == "2") {
                            summaryRow.class_2_count += 1
                        } else if (attendanceRow.class == "1") {
                            summaryRow.class_1_count += 1
                        } else if (attendanceRow.class == "Pre-school") {
                            summaryRow.preschool_count += 1
                        } else if (attendanceRow.class == "Nursery") {
                            summaryRow.nursery_count += 1
                        } else if (attendanceRow.class == "LKG") {
                            summaryRow.lkg_count += 1
                        } else if (attendanceRow.class == "UKG") {
                            summaryRow.ukg_count += 1
                        } else if (attendanceRow.class == "3") {
                            summaryRow.class_3_count += 1
                        } else if (attendanceRow.class == "4" || attendanceRow.class == "5" || attendanceRow.class == "6") {
                            summaryRow.class_4_5_6_count += 1
                        }
                    } else {
                        summaryRows.value.push({
                            category: attendanceRow.category,
                            category_count: 1,
                            preschool_count: attendanceRow.class == "Pre-school" ? 1 : 0,
                            nursery_count: attendanceRow.class == "Nursery" ? 1 : 0,
                            lkg_count: attendanceRow.class == "LKG" ? 1 : 0,
                            ukg_count: attendanceRow.class == "UKG" ? 1 : 0,
                            class_3_count: attendanceRow.class == "3" ? 1 : 0,
                            class_4_5_6_count: attendanceRow.class == "4" ? 1 : 0,
                            class_4_5_6_count: attendanceRow.class == "5" ? 1 : 0,
                            class_4_5_6_count: attendanceRow.class == "6" ? 1 : 0,
                            class_1_count: attendanceRow.class == "1" ? 1 : 0,
                            class_2_count: attendanceRow.class == "2" ? 1 : 0
                        })
                    }
                    totalCount.value += 1
                }
                return {
                    summaryRows,
                    addRow,
                    totalCount
                }
            }
        }).mount('#summaryTable')
    </script>

    <script src="https://unpkg.com/mqtt@5.0.1/dist/mqtt.min.js"></script>
    <script>
        const mqttClient = mqtt.connect('wss://mqtt.rssi.in');
        const TOPIC = "attendance-record-events";
        const statusElement = document.getElementById('status');

        mqttClient.on("connect", () => {
            console.log("MQTT client connected.");
            setStatus("Connected", "alert-success");
            mqttClient.subscribe(TOPIC);
        });

        mqttClient.on('error', (error) => {
            console.log("MQTT client ERROR.");
            setStatus("Error: " + error.message, "alert-danger");
        });

        function setStatus(message, alertClass) {
            statusElement.textContent = message;
            statusElement.classList.remove("alert-warning", "alert-success", "alert-danger");
            statusElement.classList.add(alertClass);
        }

        mqttClient.on('message', (topic, message) => {
            if (topic == TOPIC) {
                onNewAttendanceRecordEvent(message);
            }
        });

        function onNewAttendanceRecordEvent(message) {
            var attendanceRow = JSON.parse(message);
            addOrUpdateRowInAttendanceTable(attendanceRow);
        }

        function addRow(attendanceRow) {
            var lastTr = document.getElementById('last-row')
            tr = document.createElement('tr')
            tr.id = attendanceRow.userId
            for (var key of ["userId", "userName", "category", "class", "status", "punchIn", "punchOut"]) {
                var td = document.createElement('td')
                if (key == "punchIn" || key == "punchOut") {
                    td.innerText = attendanceRow[key] ? attendanceRow[key] : "Not Available"
                } else {
                    td.innerText = attendanceRow[key]
                }
                tr.appendChild(td)
            }
            lastTr.insertAdjacentElement("afterend", tr)
            return tr
        }

        function updateRow(tr, attendanceRow) {
            for (var key of ["userId", "userName", "category", "class", "status", "punchIn", "punchOut"]) {
                var keyIndex = ["userId", "userName", "category", "class", "status", "punchIn", "punchOut"].indexOf(key)
                var td = tr.querySelector('td:nth-child(' + (keyIndex + 1) + ')')
                if (key == "punchIn" || key == "punchOut") {
                    td.innerText = attendanceRow[key] ? attendanceRow[key] : "Not Available"
                } else {
                    td.innerText = attendanceRow[key]
                }
            }
        }

        function removeNoRecordTr() {
            var tr = document.getElementById("no-record")
            if (tr)
                tr.remove()
        }

        function addOrUpdateRowInAttendanceTable(attendanceRow) {
            removeNoRecordTr()
            const userId = attendanceRow['userId']
            var tr = document.getElementById(userId)
            if (tr != null) {
                // update tds inside tr
                updateRow(tr, attendanceRow)
            } else {
                // insert new row
                tr = addRow(attendanceRow)
                summaryApp.addRow(attendanceRow)
            }
            // flash the tr green 
            tr.classList.add('bg-success')
            setTimeout(function() {
                tr.classList.remove('bg-success')
            }, 1000)
        }
    </script>
</body>

</html>