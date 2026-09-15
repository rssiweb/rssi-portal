<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
validation();

// ============ Read filters ============
$id            = isset($_GET['get_aid'])         ? strtoupper(trim($_GET['get_aid'])) : null;
$fromDate      = isset($_GET['get_from_date'])   ? $_GET['get_from_date'] : '';
$toDate        = isset($_GET['get_to_date'])     ? $_GET['get_to_date']   : '';
$location      = isset($_GET['get_location'])    ? $_GET['get_location']  : '';
$searchByUser  = isset($_GET['search_by_user']) && $_GET['search_by_user'] === '1';

// Default to today → today
if (empty($fromDate)) $fromDate = date('Y-m-d');
if (empty($toDate))   $toDate   = date('Y-m-d');

// Validate + swap if reversed
$fromValid = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate);
$toValid   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate);
if ($fromValid && $toValid && strtotime($fromDate) > strtotime($toDate)) {
    [$fromDate, $toDate] = [$toDate, $fromDate];
}

// If checkbox is off, force single-day view
if (!$searchByUser) {
    $toDate = $fromDate;
}

// Cap range to 31 days max
$maxDays = 31;
if ($fromValid && $toValid) {
    $diffDays = (strtotime($toDate) - strtotime($fromDate)) / 86400;
    if ($diffDays > $maxDays) {
        $toDate = date('Y-m-d', strtotime("$fromDate +$maxDays days"));
    }
}

$showingTodayData = ($fromDate === date('Y-m-d') && $toDate === date('Y-m-d'));
$isSingleDayView  = ($fromDate === $toDate);

// Fetch locations for dropdown
$locationQuery  = "SELECT id, name FROM office_locations WHERE is_active = true ORDER BY name";
$locationResult = pg_query($con, $locationQuery);
$locations      = [];
if ($locationResult) {
    while ($row = pg_fetch_assoc($locationResult)) {
        $locations[] = $row;
    }
}

// ============ Main query (optimized) ============
$query = "
WITH filtered AS (
    SELECT
        a.user_id,
        a.status,
        a.punch_in,
        a.is_manual,
        a.recorded_by,
        a.gps_location,
        a.ip_address,
        a.remarks,
        DATE(a.punch_in) AS punch_date
    FROM attendance a
    WHERE a.punch_in >= '$fromDate'::date
      AND a.punch_in <  ('$toDate'::date + INTERVAL '1 day')
),
ranked AS (
    SELECT
        f.*,
        ROW_NUMBER() OVER (PARTITION BY user_id, punch_date ORDER BY punch_in ASC)  AS rn_asc,
        ROW_NUMBER() OVER (PARTITION BY user_id, punch_date ORDER BY punch_in DESC) AS rn_desc,
        COUNT(*)    OVER (PARTITION BY user_id, punch_date)                          AS cnt
    FROM filtered f
),
first_last AS (
    SELECT
        user_id,
        punch_date,
        MAX(punch_in)      FILTER (WHERE rn_asc  = 1) AS punch_in,
        BOOL_OR(is_manual) FILTER (WHERE rn_asc  = 1) AS punch_in_is_manual,
        MAX(recorded_by)   FILTER (WHERE rn_asc  = 1) AS punch_in_recorded_by,
        MAX(gps_location)  FILTER (WHERE rn_asc  = 1) AS punch_in_gps,
        MAX(ip_address)    FILTER (WHERE rn_asc  = 1) AS punch_in_ip,
        MAX(remarks)       FILTER (WHERE rn_asc  = 1) AS punch_in_remarks,
        MAX(status)        FILTER (WHERE rn_asc  = 1) AS status,
        MAX(punch_in)      FILTER (WHERE rn_desc = 1) AS punch_out,
        BOOL_OR(is_manual) FILTER (WHERE rn_desc = 1) AS punch_out_is_manual,
        MAX(recorded_by)   FILTER (WHERE rn_desc = 1) AS punch_out_recorded_by,
        MAX(gps_location)  FILTER (WHERE rn_desc = 1) AS punch_out_gps,
        MAX(ip_address)    FILTER (WHERE rn_desc = 1) AS punch_out_ip,
        MAX(remarks)       FILTER (WHERE rn_desc = 1) AS punch_out_remarks,
        MAX(cnt) AS cnt
    FROM ranked
    WHERE rn_asc = 1 OR rn_desc = 1
    GROUP BY user_id, punch_date
)
SELECT
    fl.user_id,
    fl.status,
    COALESCE(m.fullname, s.studentname) AS user_name,
    s.category,
    s.class,
    m.engagement,
    fl.punch_in,
    fl.punch_in_is_manual,
    fl.punch_in_recorded_by,
    fl.punch_in_gps,
    fl.punch_in_ip,
    fl.punch_in_remarks,
    CASE WHEN fl.cnt > 1 THEN fl.punch_out             ELSE NULL END AS punch_out,
    CASE WHEN fl.cnt > 1 THEN fl.punch_out_is_manual   ELSE NULL END AS punch_out_is_manual,
    CASE WHEN fl.cnt > 1 THEN fl.punch_out_recorded_by ELSE NULL END AS punch_out_recorded_by,
    CASE WHEN fl.cnt > 1 THEN fl.punch_out_gps         ELSE NULL END AS punch_out_gps,
    CASE WHEN fl.cnt > 1 THEN fl.punch_out_ip          ELSE NULL END AS punch_out_ip,
    CASE WHEN fl.cnt > 1 THEN fl.punch_out_remarks     ELSE NULL END AS punch_out_remarks
FROM first_last fl
LEFT JOIN rssimyaccount_members m ON fl.user_id = m.associatenumber
LEFT JOIN rssimyprofile_student  s ON fl.user_id = s.student_id
WHERE 1=1
";

// Optional user filter
if (!empty($id)) {
    $query .= " AND fl.user_id = '" . pg_escape_string($con, $id) . "'";
}

// Optional location filter
if (!empty($location)) {
    $locationNameQuery  = "SELECT name FROM office_locations WHERE id = '" . pg_escape_string($con, $location) . "'";
    $locationNameResult = pg_query($con, $locationNameQuery);
    $locationNameRow    = pg_fetch_assoc($locationNameResult);
    $locationName       = $locationNameRow['name'] ?? '';

    if ($locationName !== '') {
        $query .= " AND (s.preferredbranch = '" . pg_escape_string($con, $locationName) . "'
                     OR m.basebranch = '" . pg_escape_string($con, $locationName) . "')";
    }
}

$query .= " ORDER BY fl.punch_in DESC";

// Debug
// echo "<pre>" . htmlspecialchars($query) . "</pre>";

$result = pg_query($con, $query);
if (!$result) {
    echo "An error occurred.";
    exit;
}
$resultArr = pg_fetch_all($result);

// ============ Summary query — only for single-day, no specific user ============
$resultArrcount = [];
$totalCount     = 0;

if (empty($id) && $isSingleDayView) {
    $querycount = "
        SELECT
            category,
            COUNT(DISTINCT user_id) AS category_count,
            COUNT(DISTINCT user_id) FILTER (WHERE class = 'Nursery')      AS nursery_count,
            COUNT(DISTINCT user_id) FILTER (WHERE class = 'LKG')          AS lkg_count,
            COUNT(DISTINCT user_id) FILTER (WHERE class = 'UKG')          AS ukg_count,
            COUNT(DISTINCT user_id) FILTER (WHERE class = '1')            AS class_1_count,
            COUNT(DISTINCT user_id) FILTER (WHERE class = '2')            AS class_2_count,
            COUNT(DISTINCT user_id) FILTER (WHERE class = '3')            AS class_3_count,
            COUNT(DISTINCT user_id) FILTER (WHERE class IN ('4','5','6')) AS class_4_5_6_count
        FROM (
            SELECT a.user_id, s.category, s.class,
                   m.basebranch, s.preferredbranch
            FROM attendance a
            LEFT JOIN rssimyprofile_student s ON a.user_id = s.student_id
            LEFT JOIN rssimyaccount_members m ON a.user_id = m.associatenumber
            WHERE a.punch_in >= $1::date
              AND a.punch_in <  ($2::date + INTERVAL '1 day')
              " . (!empty($location) && !empty($locationName)
        ? " AND (s.preferredbranch = '" . pg_escape_string($con, $locationName) . "'
                              OR m.basebranch = '" . pg_escape_string($con, $locationName) . "')"
        : "") . "
        ) t
        GROUP BY category
    ";

    $stmt = pg_prepare($con, "querycount", $querycount);
    $resultcount = pg_execute($con, "querycount", array($fromDate, $toDate));

    if ($resultcount) {
        $resultArrcount = pg_fetch_all($resultcount) ?: [];
        foreach ($resultArrcount as $entry) {
            $totalCount += $entry['category_count'];
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
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
    <?php include 'includes/meta.php' ?>

    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets_new/css/style.css?v=1.1.0" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

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

    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <div class="alert alert-warning" id="status" role="alert">Connecting...</div>
                            <div class="row" style="display: flex; align-items: center;">
                                <div class="col-md-8 mb-3">
                                    <p>To customize the view result, please select a filter value.</p>
                                </div>

                                <?php if (empty($id) && $isSingleDayView): ?>
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
                                <?php endif; ?>
                            </div>

                            <form action="" method="GET" class="row g-2 align-items-center">
                                <div class="row">
                                    <div class="col-12 col-sm-2">
                                        <div class="form-group">
                                            <input type="text" name="get_aid" id="get_aid" class="form-control"
                                                placeholder="User Id"
                                                value="<?php echo isset($_GET['get_aid']) ? htmlspecialchars($_GET['get_aid']) : ''; ?>"
                                                <?php echo !$searchByUser ? 'disabled' : ''; ?>>
                                            <small class="form-text text-muted">Enter User Id</small>
                                        </div>
                                    </div>

                                    <div class="col-12 col-sm-3">
                                        <div class="form-group">
                                            <input type="text" id="date_range_picker" class="form-control"
                                                placeholder="Select date" readonly required>
                                            <input type="hidden" name="get_from_date" id="get_from_date" value="<?php echo htmlspecialchars($fromDate); ?>">
                                            <input type="hidden" name="get_to_date" id="get_to_date" value="<?php echo htmlspecialchars($toDate); ?>">
                                            <small class="form-text text-muted" id="date_range_hint">
                                                <?php echo $searchByUser ? 'Select Date Range' : 'Select Date'; ?>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-12 col-sm-2">
                                        <div class="form-group">
                                            <select name="get_location" id="get_location" class="form-select">
                                                <option value="">All Locations</option>
                                                <?php foreach ($locations as $loc): ?>
                                                    <option value="<?php echo htmlspecialchars($loc['id']); ?>" <?php echo (isset($_GET['get_location']) && $_GET['get_location'] == $loc['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($loc['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted">Select Location</small>
                                        </div>
                                    </div>

                                    <div class="col-12 col-sm-2">
                                        <button type="submit" name="search_by_id" class="btn btn-success" style="outline: none;">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="search_by_user" id="search_by_user" value="1"
                                                <?php echo $searchByUser ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="search_by_user" style="font-size: 0.9rem;">
                                                Search by User ID
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <?php if ($showingTodayData):
                                $formattedToday = date('F j, Y');
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
                                        }, 1000);
                                    }
                                </script>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th>User ID</th>
                                            <th>User Name</th>
                                            <th>Category</th>
                                            <th>Class</th>
                                            <th>Status</th>
                                            <th>Punch In</th>
                                            <th>Punch Out</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($resultArr != null) {
                                            foreach ($resultArr as $array) {
                                                echo '<tr id="' . $array['user_id'] . '">';
                                                echo '<td>' . $array['user_id'] . '</td>';
                                                echo '<td>' . $array['user_name'] . '</td>';
                                                echo '<td>' . $array['category'] . $array['engagement'] . '</td>';
                                                echo '<td>' . $array['class'] . '</td>';
                                                echo '<td>' . $array['status'] . '</td>'; ?>
                                                <td>
                                                    <?php
                                                    if ($array['punch_in']) {
                                                        echo date('d/m/Y h:i:s a', strtotime($array['punch_in']));
                                                        if ($array['punch_in_is_manual'] === 't') {
                                                            echo ' <span class="manual-dot"
                                                                    data-type="in"
                                                                    data-user="' . $array['user_id'] . '"
                                                                    data-time="' . $array['punch_in'] . '"
                                                                    data-recorded_by="' . htmlspecialchars($array['punch_in_recorded_by'] ?? 'N/A', ENT_QUOTES) . '"
                                                                    data-remarks="' . htmlspecialchars($array['punch_in_remarks'] ?? 'N/A', ENT_QUOTES) . '"
                                                                    style="cursor:pointer;" title="Manual Entry">&#x1F7E1;</span>';
                                                        }
                                                    } else {
                                                        echo 'Not Available';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if ($array['punch_out']) {
                                                        echo date('d/m/Y h:i:s a', strtotime($array['punch_out']));
                                                        if ($array['punch_out_is_manual'] === 't') {
                                                            echo ' <span class="manual-dot"
                                                                    data-type="out"
                                                                    data-user="' . $array['user_id'] . '"
                                                                    data-time="' . $array['punch_out'] . '"
                                                                    data-recorded_by="' . htmlspecialchars($array['punch_out_recorded_by'] ?? 'N/A', ENT_QUOTES) . '"
                                                                    data-remarks="' . htmlspecialchars($array['punch_out_remarks'] ?? 'N/A', ENT_QUOTES) . '"
                                                                    style="cursor:pointer;" title="Manual Entry">&#x1F7E1;</span>';
                                                        }
                                                    } else {
                                                        echo 'Not Available';
                                                    }
                                                    ?>
                                                </td>
                                        <?php echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr id="no-record"><td colspan="7">No records found.</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                    <tfoot>
                                        <tr id="last-row" style="display:none"></tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            <?php if (!empty($resultArr)) : ?>
                $('#table-id').DataTable({
                    paging: false
                });
            <?php endif; ?>
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js"></script>

    <?php if (empty($id) && $isSingleDayView): ?>
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
    <?php else: ?>
        <script>
            window.summaryApp = {
                addRow: function() {}
            };
        </script>
    <?php endif; ?>

    <script src="https://unpkg.com/mqtt@5.0.1/dist/mqtt.min.js"></script>
    <script>
        const mqttClient = mqtt.connect('wss://mqtt.rssi.in');
        const TOPIC = "attendance-record-events";
        const statusElement = document.getElementById('status');

        mqttClient.on("connect", () => {
            setStatus("Connected", "alert-success");
            mqttClient.subscribe(TOPIC);
        });
        mqttClient.on('error', (error) => {
            setStatus("Error: " + error.message, "alert-danger");
        });

        function setStatus(message, alertClass) {
            statusElement.textContent = message;
            statusElement.classList.remove("alert-warning", "alert-success", "alert-danger");
            statusElement.classList.add(alertClass);
        }
        mqttClient.on('message', (topic, message) => {
            if (topic == TOPIC) onNewAttendanceRecordEvent(message);
        });

        function onNewAttendanceRecordEvent(message) {
            var attendanceRow = JSON.parse(message);
            addOrUpdateRowInAttendanceTable(attendanceRow);
        }

        function addRow(attendanceRow) {
            var lastTr = document.getElementById('last-row')
            var tr = document.createElement('tr')
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
            if (tr) tr.remove()
        }

        function addOrUpdateRowInAttendanceTable(attendanceRow) {
            removeNoRecordTr()
            const userId = attendanceRow['userId']
            var tr = document.getElementById(userId)
            if (tr != null) {
                updateRow(tr, attendanceRow)
            } else {
                tr = addRow(attendanceRow)
                if (window.summaryApp && window.summaryApp.addRow) {
                    window.summaryApp.addRow(attendanceRow)
                }
            }
            tr.classList.add('bg-success')
            setTimeout(function() {
                tr.classList.remove('bg-success')
            }, 1000)
        }
    </script>

    <!-- Modal -->
    <div class="modal fade" id="manualEntryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manual Entry Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>User ID:</strong> <span id="modal-user-id"></span></p>
                    <p><strong>Entry Type:</strong> <span id="modal-entry-type"></span></p>
                    <p><strong>Time:</strong> <span id="modal-time"></span></p>
                    <p><strong>Recorded By:</strong> <span id="modal-recorded_by"></span></p>
                    <p><strong>Remarks:</strong> <span id="modal-remarks"></span></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const modal = new bootstrap.Modal(document.getElementById('manualEntryModal'));
            document.querySelectorAll('.manual-dot').forEach(dot => {
                dot.addEventListener('click', function() {
                    document.getElementById('modal-user-id').textContent = this.dataset.user;
                    document.getElementById('modal-entry-type').textContent = this.dataset.type === 'in' ? 'Punch In' : 'Punch Out';
                    document.getElementById('modal-time').textContent = new Date(this.dataset.time).toLocaleString();
                    document.getElementById('modal-recorded_by').textContent = this.dataset.recorded_by || 'N/A';
                    document.getElementById('modal-remarks').textContent = this.dataset.remarks || 'N/A';
                    modal.show();
                });
            });
        });
    </script>

    <!-- Flatpickr: date picker that switches mode based on checkbox -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const fromInput = document.getElementById('get_from_date');
            const toInput = document.getElementById('get_to_date');
            const display = document.getElementById('date_range_picker');
            const checkbox = document.getElementById('search_by_user');
            const aidInput = document.getElementById('get_aid');
            const dateHint = document.getElementById('date_range_hint');

            const todayEnd = new Date();
            todayEnd.setHours(23, 59, 59, 999);

            const fmt = d => {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${y}-${m}-${day}`;
            };

            let fp = null;

            function initPicker(useRange) {
                if (fp) fp.destroy();

                fp = flatpickr(display, {
                    mode: useRange ? "range" : "single",
                    dateFormat: "Y-m-d",
                    defaultDate: useRange ? [fromInput.value, toInput.value] : fromInput.value,
                    maxDate: todayEnd,
                    onChange: function(selectedDates) {
                        if (selectedDates.length === 0) return;

                        if (!useRange) {
                            const d = fmt(selectedDates[0]);
                            fromInput.value = d;
                            toInput.value = d;
                            display.value = d;
                        } else {
                            if (selectedDates.length === 1) {
                                fromInput.value = fmt(selectedDates[0]);
                                toInput.value = fmt(selectedDates[0]);
                            } else if (selectedDates.length === 2) {
                                fromInput.value = fmt(selectedDates[0]);
                                toInput.value = fmt(selectedDates[1]);
                            }
                            display.value = fromInput.value + " to " + toInput.value;
                        }
                    }
                });

                if (useRange) {
                    display.value = fromInput.value + " to " + toInput.value;
                } else {
                    toInput.value = fromInput.value;
                    display.value = fromInput.value;
                }
            }

            // Init based on current checkbox state
            initPicker(checkbox.checked);

            // React to checkbox change
            checkbox.addEventListener('change', function() {
                aidInput.disabled = !this.checked;
                if (!this.checked) {
                    aidInput.value = ''; // clear when disabling
                }
                dateHint.textContent = this.checked ? 'Select Date Range' : 'Select Date';
                initPicker(this.checked);
            });
        });
    </script>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const fromVal = document.getElementById('get_from_date').value;
            const toVal = document.getElementById('get_to_date').value;
            if (!fromVal || !toVal) {
                e.preventDefault();
                alert('Please select a date.');
            }
        });
    </script>

</body>

</html>