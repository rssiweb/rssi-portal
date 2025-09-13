<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
validation();

// Handle filtering
$filter_ticket_id = isset($_POST['ticket_id']) ? $_POST['ticket_id'] : '';
$filter_type = isset($_POST['type']) ? $_POST['type'] : '';
$filter_category = isset($_POST['category']) ? $_POST['category'] : '';
$filter_concerned_individual = isset($_POST['concerned_individual']) ? $_POST['concerned_individual'] : '';
$filter_raised_by = isset($_POST['raised_by']) ? $_POST['raised_by'] : '';
$filter_assigned_to = isset($_POST['assigned_to']) ? $_POST['assigned_to'] : '';
$filter_status = isset($_POST['status']) ? $_POST['status'] : [];
$filter_academic_year = isset($_POST['academic_year']) ? $_POST['academic_year'] : '';

// Parse academic year to get start and end dates
if ($filter_academic_year) {
    list($start_year, $end_year) = explode('-', $filter_academic_year);
    $academic_year_start = $start_year . '-04-01';
    $academic_year_end = $end_year . '-03-31';
} else {
    // Default to current academic year if not set
    $current_year = date('Y');
    $current_month = date('n');

    if ($current_month < 4) {
        $academic_year_start = ($current_year - 1) . '-04-01';
        $academic_year_end = $current_year . '-03-31';
        $filter_academic_year = ($current_year - 1) . '-' . $current_year;
    } else {
        $academic_year_start = $current_year . '-04-01';
        $academic_year_end = ($current_year + 1) . '-03-31';
        $filter_academic_year = $current_year . '-' . ($current_year + 1);
    }
}

// Use default statuses only if all filters are empty
$filter_status_for_query = (
    empty($filter_ticket_id) &&
    empty($filter_type) &&
    empty($filter_category) &&
    empty($filter_concerned_individual) &&
    empty($filter_raised_by) &&
    empty($filter_assigned_to) &&
    empty($filter_status) &&
    empty($filter_academic_year)
) ? ['Open', 'In Progress'] : $filter_status;

// Get unique values for filter dropdowns
$type_query = "SELECT DISTINCT action FROM support_ticket WHERE action IS NOT NULL ORDER BY action";
$type_result = pg_query($con, $type_query);
$types = pg_fetch_all($type_result);

$category_query = "SELECT DISTINCT category FROM support_ticket WHERE category IS NOT NULL ORDER BY category";
$category_result = pg_query($con, $category_query);
$categories = pg_fetch_all($category_result);

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
        COALESCE(s.timestamp, NULL) AS latest_status_timestamp,
        m1.fullname AS raised_by_name,
        m2.fullname AS assigned_to_name
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
    LEFT JOIN rssimyaccount_members m1 ON t.raised_by = m1.associatenumber
    LEFT JOIN rssimyaccount_members m2 ON a.assigned_to = m2.associatenumber
    WHERE 1=1
";

// Apply academic year filter
$query .= " AND t.timestamp >= '" . pg_escape_string($con, $academic_year_start) . "' 
            AND t.timestamp <= '" . pg_escape_string($con, $academic_year_end) . " 23:59:59'";

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

// Apply type filter
if ($filter_type) {
    $query .= " AND t.action = '" . pg_escape_string($con, $filter_type) . "'";
}

// Apply category filter
if ($filter_category) {
    $escaped_value = pg_escape_string($con, $filter_category);
    $query .= " AND t.category::jsonb ? '" . $escaped_value . "'";
}

// Apply concerned individual filter
if ($filter_concerned_individual) {
    $escaped_value = pg_escape_string($con, $filter_concerned_individual);
    $query .= " AND t.raised_for::jsonb ? '" . $escaped_value . "'";
}

// Apply raised by filter
if ($filter_raised_by) {
    $query .= " AND t.raised_by = '" . pg_escape_string($con, $filter_raised_by) . "'";
}

// Apply assigned to filter
if ($filter_assigned_to) {
    $query .= " AND a.assigned_to = '" . pg_escape_string($con, $filter_assigned_to) . "'";
}

// Apply multi-select status filter
if (!empty($filter_status_for_query)) {
    $escapedStatuses = array_map(function ($status) use ($con) {
        return "'" . pg_escape_string($con, $status) . "'";
    }, $filter_status_for_query);

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

// Dashboard metrics
$metrics_query = "
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN s.status IN ('Open', 'In Progress') THEN 1 ELSE 0 END) as open_tickets,
        SUM(CASE WHEN s.status = 'In Progress' THEN 1 ELSE 0 END) as inprogress_tickets,
        SUM(CASE WHEN s.status IN ('Closed', 'Resolved') THEN 1 ELSE 0 END) as closed_tickets,
        AVG(CASE WHEN s.status IN ('Closed', 'Resolved') THEN 
            EXTRACT(EPOCH FROM (s.timestamp - t.timestamp)) / 3600 
        END) as avg_hours_to_resolve
    FROM support_ticket t
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

// Apply academic year filter to metrics too
$metrics_query .= " AND t.timestamp >= '" . pg_escape_string($con, $academic_year_start) . "' 
                    AND t.timestamp <= '" . pg_escape_string($con, $academic_year_end) . " 23:59:59'";

// Apply role-based filtering to metrics too
if ($role !== 'Admin') {
    $metrics_query .= " AND (
                    t.raised_by = '" . pg_escape_string($con, $associatenumber) . "' 
                    OR EXISTS (
                        SELECT 1
                        FROM support_ticket_assignment sa
                        WHERE sa.ticket_id = t.ticket_id
                        AND sa.assigned_to = '" . pg_escape_string($con, $associatenumber) . "'
                    )
                )";
}

$metrics_result = pg_query($con, $metrics_query);
$metrics = pg_fetch_assoc($metrics_result);
?>
<?php
// Collect all unique selected associate numbers
$selected_associates = array_filter([
    $filter_concerned_individual,
    $filter_raised_by,
    $filter_assigned_to
]);

$selected_associates = array_unique($selected_associates);
$members_data = [];

if (count($selected_associates) > 0) {
    $params = array_values($selected_associates);
    $placeholders = [];
    foreach ($params as $i => $param) {
        $placeholders[] = '$' . ($i + 1);
    }
    $in_clause = implode(',', $placeholders);

    $query = "SELECT associatenumber, fullname FROM rssimyaccount_members WHERE associatenumber IN ($in_clause)";
    $result = pg_query_params($con, $query, $params);

    if ($result && pg_num_rows($result) > 0) {
        while ($row = pg_fetch_assoc($result)) {
            $members_data[$row['associatenumber']] = $row['fullname'];
        }
    }
}
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

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

        .dashboard-card {
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
            position: relative;
            overflow: hidden;
            border: 1px solid #f0f0f0;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .stats-number {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .card-border-left {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 5px;
        }

        .card-icon {
            opacity: 0.7;
        }

        .select2-container--bootstrap-5 {
            width: 100% !important;
        }
    </style>

    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
            <!-- Reports -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <!-- Dashboard metrics - Updated design -->
                            <div class="row">
                                <!-- Total Tickets -->
                                <div class="col-lg-2 col-md-6 mb-3">
                                    <div class="card dashboard-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div class="card-content">
                                                    <h5 class="card-title text-muted">Total Tickets</h5>
                                                    <div class="stats-number"><?php echo $metrics['total_tickets'] ?? 0; ?></div>
                                                </div>
                                                <div class="card-icon">
                                                    <i class="bi bi-ticket-detailed fs-1 text-primary"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-border-left bg-primary"></div>
                                    </div>
                                </div>

                                <!-- Open Tickets -->
                                <div class="col-lg-2 col-md-6 mb-3">
                                    <div class="card dashboard-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div class="card-content">
                                                    <h5 class="card-title text-muted">Open Tickets</h5>
                                                    <div class="stats-number"><?php echo $metrics['open_tickets'] ?? 0; ?></div>
                                                </div>
                                                <div class="card-icon">
                                                    <i class="bi bi-folder2-open fs-1 text-warning"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-border-left bg-warning"></div>
                                    </div>
                                </div>

                                <!-- In Progress -->
                                <div class="col-lg-2 col-md-6 mb-3">
                                    <div class="card dashboard-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div class="card-content">
                                                    <h5 class="card-title text-muted">In Progress</h5>
                                                    <div class="stats-number"><?php echo $metrics['inprogress_tickets'] ?? 0; ?></div>
                                                </div>
                                                <div class="card-icon">
                                                    <i class="bi bi-arrow-clockwise fs-1 text-info"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-border-left bg-info"></div>
                                    </div>
                                </div>

                                <!-- Closed Tickets -->
                                <div class="col-lg-2 col-md-6 mb-3">
                                    <div class="card dashboard-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div class="card-content">
                                                    <h5 class="card-title text-muted">Closed Tickets</h5>
                                                    <div class="stats-number"><?php echo $metrics['closed_tickets'] ?? 0; ?></div>
                                                </div>
                                                <div class="card-icon">
                                                    <i class="bi bi-check-circle fs-1 text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-border-left bg-success"></div>
                                    </div>
                                </div>

                                <!-- Average Resolution Time - Now in the same row -->
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <div class="card dashboard-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div class="card-content">
                                                    <h5 class="card-title text-muted">Avg. Resolution Time</h5>
                                                    <div class="stats-number">
                                                        <?php
                                                        if (isset($metrics['avg_hours_to_resolve']) && $metrics['avg_hours_to_resolve'] > 0) {
                                                            $avg_hours = floatval($metrics['avg_hours_to_resolve']);
                                                            if ($avg_hours < 24) {
                                                                echo number_format($avg_hours, 1) . " hrs";
                                                            } else {
                                                                echo number_format($avg_hours / 24, 1) . " days";
                                                            }
                                                        } else {
                                                            echo "N/A";
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="card-icon">
                                                    <i class="bi bi-clock-history fs-1 text-secondary"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-border-left bg-secondary"></div>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" class="filter-form row g-3">
                                <div class="col-md-2">
                                    <input type="text" id="ticket_id" name="ticket_id" class="form-control" placeholder="Ticket ID" value="<?php echo htmlspecialchars($filter_ticket_id); ?>">
                                    <div class="form-text">Exact match only</div>
                                </div>

                                <div class="col-md-2">
                                    <select id="academic_year" name="academic_year" class="form-control select2">
                                        <?php
                                        // Get current academic year (April to March)
                                        $current_year = date('Y');
                                        $current_month = date('n');

                                        // Generate academic year options (current + 4 previous years)
                                        for ($i = 0; $i < 5; $i++) {
                                            $year_offset = $i;
                                            if ($current_month < 4) {
                                                $start_year = $current_year - 1 - $year_offset;
                                                $end_year = $current_year - $year_offset;
                                            } else {
                                                $start_year = $current_year - $year_offset;
                                                $end_year = $current_year + 1 - $year_offset;
                                            }
                                            $academic_year_value = $start_year . '-' . $end_year;
                                            $is_selected = ($filter_academic_year == $academic_year_value) ? 'selected' : '';
                                            echo "<option value='$academic_year_value' $is_selected>$academic_year_value</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="form-text">Academic Year</div>
                                </div>

                                <div class="col-md-2">
                                    <select id="type" name="type" class="form-control select2">
                                        <option value="">All Types</option>
                                        <?php foreach ($types as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type['action']); ?>" <?php echo $filter_type == $type['action'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($type['action']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Select Type</div>
                                </div>

                                <div class="col-md-2">
                                    <select id="category" name="category" class="form-control select2">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category):
                                            // Decode JSON if needed
                                            $catData = json_decode($category['category'], true);
                                            $catName = is_array($catData) && count($catData) > 0 ? $catData[0] : $category['category'];
                                        ?>
                                            <option value="<?php echo htmlspecialchars($catName); ?>" <?php echo $filter_category == $catName ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($catName); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Select Category</div>
                                </div>

                                <div class="col-md-2">
                                    <select id="concerned_individual" name="concerned_individual" class="form-control select2">
                                        <option value="<?php echo htmlspecialchars($filter_concerned_individual); ?>" <?php echo $filter_concerned_individual ? 'selected' : ''; ?>>
                                            <?php
                                            if ($filter_concerned_individual && isset($members_data[$filter_concerned_individual])) {
                                                echo htmlspecialchars($members_data[$filter_concerned_individual]);
                                            } elseif ($filter_concerned_individual) {
                                                echo htmlspecialchars($filter_concerned_individual);
                                            } else {
                                                echo 'Select Concerned Individual';
                                            }
                                            ?>
                                        </option>
                                    </select>
                                    <div class="form-text">Select Concerned Individual</div>
                                </div>

                                <div class="col-md-2">
                                    <select id="raised_by" name="raised_by" class="form-control select2">
                                        <option value="<?php echo htmlspecialchars($filter_raised_by); ?>" <?php echo $filter_raised_by ? 'selected' : ''; ?>>
                                            <?php
                                            if ($filter_raised_by && isset($members_data[$filter_raised_by])) {
                                                echo htmlspecialchars($members_data[$filter_raised_by]);
                                            } elseif ($filter_raised_by) {
                                                echo htmlspecialchars($filter_raised_by);
                                            } else {
                                                echo 'Select Raised By';
                                            }
                                            ?>
                                        </option>
                                    </select>
                                    <div class="form-text">Select Raised By</div>
                                </div>

                                <div class="col-md-2">
                                    <select id="assigned_to" name="assigned_to" class="form-control select2">
                                        <option value="<?php echo htmlspecialchars($filter_assigned_to); ?>" <?php echo $filter_assigned_to ? 'selected' : ''; ?>>
                                            <?php
                                            if ($filter_assigned_to && isset($members_data[$filter_assigned_to])) {
                                                echo htmlspecialchars($members_data[$filter_assigned_to]);
                                            } elseif ($filter_assigned_to) {
                                                echo htmlspecialchars($filter_assigned_to);
                                            } else {
                                                echo 'Select Assigned To';
                                            }
                                            ?>
                                        </option>
                                    </select>
                                    <div class="form-text">Select Assigned To</div>
                                </div>

                                <div class="col-md-2">
                                    <select id="status" name="status[]" class="form-control select2" multiple>
                                        <option value="In Progress" <?php echo in_array('In Progress', $filter_status) ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="Open" <?php echo in_array('Open', $filter_status) ? 'selected' : ''; ?>>Open</option>
                                        <option value="Closed" <?php echo in_array('Closed', $filter_status) ? 'selected' : ''; ?>>Closed</option>
                                        <option value="Resolved" <?php echo in_array('Resolved', $filter_status) ? 'selected' : ''; ?>>Resolved</option>
                                    </select>
                                    <div class="form-text">Select Status (Multi-select)</div>
                                </div>

                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary" style="outline: none;">
                                        <i class="bi bi-search"></i>&nbsp;Filter
                                    </button>
                                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i>&nbsp;Reset
                                    </a>
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
                                                    <td><?php echo htmlspecialchars($array['short_description']); ?></td>
                                                    <td><?php echo htmlspecialchars($array['action']); ?></td>
                                                    <td><?php echo htmlspecialchars(json_decode($array['category'], true)[0] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($array['severity']); ?></td>
                                                    <td><?php echo htmlspecialchars($array['raised_by_name'] ?: $array['raised_by']); ?></td>
                                                    <td><?php echo htmlspecialchars($array['assigned_to_name'] ?: $array['assigned_to']); ?></td>
                                                    <td><?php echo htmlspecialchars(date("d/m/Y g:i a", strtotime($array['ticket_timestamp']))); ?></td>
                                                    <td><?php echo !empty($array['latest_assignment_timestamp']) ? htmlspecialchars(date("d/m/Y g:i a", strtotime($array['latest_assignment_timestamp']))) : 'N/A'; ?></td>
                                                    <td><?php echo htmlspecialchars($array['latest_status_description']); ?></td>
                                                </tr>
                                            <?php } ?>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
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
            // Map of select IDs to their placeholders
            const selects = {
                'academic_year': 'Academic Year',
                'type': 'Select Type',
                'category': 'Select Category',
                'status': 'Select Status (Multi-select)'
            };

            // Loop through each select and initialize Select2
            $.each(selects, function(id, placeholder) {
                $('#' + id).select2({
                    theme: 'bootstrap-5',
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%'
                });
            });

            // Check if resultArr is empty
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    "order": [], // Disable initial sorting
                    "pageLength": 25,
                    "language": {
                        "search": "Search in table:",
                        "lengthMenu": "Show _MENU_ entries"
                    }
                });
            <?php endif; ?>

            // Initialize Select2 for associate numbers
            function initializeSelect2WithAjax(selector, placeholderText) {
                $(selector).select2({
                    ajax: {
                        url: 'fetch_associates.php',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results
                            };
                        },
                        cache: true
                    },
                    theme: 'bootstrap-5',
                    minimumInputLength: 1,
                    placeholder: placeholderText,
                    allowClear: true
                });
            }

            // Initialize Select2 fields
            $(document).ready(function() {
                initializeSelect2WithAjax('#concerned_individual', 'Select Concerned Individual');
                initializeSelect2WithAjax('#raised_by', 'Select Raised By');
                initializeSelect2WithAjax('#assigned_to', 'Select Assigned To');
            });
        });
    </script>
</body>

</html>