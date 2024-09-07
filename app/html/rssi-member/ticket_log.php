<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Handle filtering
$filter_ticket_id = isset($_POST['ticket_id']) ? $_POST['ticket_id'] : '';
$filter_status = isset($_POST['status']) ? $_POST['status'] : [];

// Set default statuses if no filter value is selected
if (empty($filter_status)) {
    $filter_status = ['Open', 'In Progress']; // Default statuses
}

// Base query
$query = "
    SELECT 
        t.ticket_id,
        t.short_description,
        t.severity,
        t.raised_by,
        t.raised_for,
        t.action,
        t.category,
        t.timestamp AS ticket_timestamp,
        COALESCE(a.assigned_to, '') AS assigned_to,
        COALESCE(a.timestamp, NULL) AS latest_assignment_timestamp,
        COALESCE(s.status, '') AS latest_status_description,
        COALESCE(s.timestamp, NULL) AS latest_status_timestamp
    FROM support_ticket t
    LEFT JOIN (
        SELECT ticket_id, assigned_to, timestamp
        FROM support_ticket_assignment
        WHERE (ticket_id, timestamp) IN (
            SELECT ticket_id, MAX(timestamp)
            FROM support_ticket_assignment
            GROUP BY ticket_id
        )
    ) a ON t.ticket_id = a.ticket_id
    LEFT JOIN (
        SELECT ticket_id, status, timestamp
        FROM support_ticket_status
        WHERE (ticket_id, timestamp) IN (
            SELECT ticket_id, MAX(timestamp)
            FROM support_ticket_status
            GROUP BY ticket_id
        )
    ) s ON t.ticket_id = s.ticket_id
    WHERE 1=1
";

// Apply role-based filtering
if ($role !== 'Admin') {
    $query .= " AND (
                    t.raised_by = '" . pg_escape_string($con, $associatenumber) . "' 
                    OR EXISTS (
                        SELECT 1
                        FROM support_ticket_assignment sa
                        WHERE sa.ticket_id = t.ticket_id
                        AND sa.assigned_to = '" . pg_escape_string($con, $associatenumber) . "'
                    )
                )";
}

// Apply ticket ID filter
if ($filter_ticket_id) {
    $query .= " AND t.ticket_id = '" . pg_escape_string($con, $filter_ticket_id) . "'";
}

// Apply multi-select status filter
if (!empty($filter_status)) {
    // Escape each status for SQL safety
    $escapedStatuses = array_map(function ($status) use ($con) {
        return "'" . pg_escape_string($con, $status) . "'";
    }, $filter_status);

    $query .= " AND s.status IN (" . implode(", ", $escapedStatuses) . ")";
}

// Order by ticket timestamp in descending order
$query .= " ORDER BY ticket_timestamp DESC";

// Execute the query
$result = pg_query($con, $query);
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

// Fetch all results
$resultArr = pg_fetch_all($result);

?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket Log</title>
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
        .filter-form {
            margin-bottom: 20px;
        }
    </style>

    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Ticket Log</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Support 360</a></li>
                    <li class="breadcrumb-item active">Ticket Log</li>
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
                            <div class="container">
                                <form method="POST" class="filter-form" style="display: flex;">
                                    <div class="form-group" style="margin-right: 10px;">
                                        <input type="text" id="ticket_id" name="ticket_id" class="form-control" placeholder="Ticket ID" value="<?php echo htmlspecialchars($filter_ticket_id); ?>" style="width: 200px;">
                                    </div>

                                    <div class="form-group" style="margin-right: 10px;">
                                        <select id="status" name="status[]" class="form-control" multiple style="width: 200px;">
                                            <option value="In Progress" <?php echo in_array('In Progress', $filter_status ?? []) ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="Open" <?php echo in_array('Open', $filter_status ?? []) ? 'selected' : ''; ?>>Open</option>
                                            <option value="Closed" <?php echo in_array('Closed', $filter_status ?? []) ? 'selected' : ''; ?>>Closed</option>
                                            <option value="Resolved" <?php echo in_array('Resolved', $filter_status ?? []) ? 'selected' : ''; ?>>Resolved</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-sm" style="outline: none;">
                                            <i class="bi bi-search"></i>&nbsp;Filter
                                        </button>
                                    </div>
                                </form>
                                <div class="table-responsive">
                                    <table class="table" id="table-id">
                                        <thead>
                                            <tr>
                                                <th>Ticket Id</th>
                                                <th>Description</th>
                                                <th>Type</th>
                                                <th>Category</th>
                                                <th>Severity</th>
                                                <th>Raised by</th>
                                                <th>Tagged to</th>
                                                <th>Ticket Timestamp</th>
                                                <th>Latest Assignment Timestamp</th>
                                                <th>Latest Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($resultArr)) { ?>
                                                <tr>
                                                    <td colspan="10">No records found</td>
                                                </tr>
                                            <?php } else { ?>
                                                <?php foreach ($resultArr as $array) { ?>
                                                    <tr>
                                                        <td><a href="ticket-dashboard.php?ticket_id=<?php echo urlencode($array['ticket_id']); ?>"><?php echo htmlspecialchars($array['ticket_id']); ?></a></td>
                                                    <?php echo '<td>' . $array['short_description'] . '</td>
                                                    <td>' . htmlspecialchars($array['action']) . '</td>
                                                    <td>' . @htmlspecialchars($array['category']) . '</td>
                                                    <td>' . htmlspecialchars($array['severity']) . '</td>
                                                    <td>' . htmlspecialchars($array['raised_by']) . '</td>
                                                    <td>' . htmlspecialchars($array['assigned_to']) . '</td>
                                                    <td>' . htmlspecialchars(@date("d/m/Y g:i a", strtotime($array['ticket_timestamp']))) . '</td>
                                                    <td>' . htmlspecialchars(@date("d/m/Y g:i a", strtotime($array['latest_assignment_timestamp']))) . '</td>
                                                    <td>' . htmlspecialchars($array['latest_status_description']) . '</td>
                                                    <!--<td>' . htmlspecialchars(@date("d/m/Y g:i a", strtotime($array['latest_status_timestamp']))) . '</td>-->
                                                </tr>';
                                                } ?>
                                                <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div><!-- End Reports -->
                </div>
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
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>