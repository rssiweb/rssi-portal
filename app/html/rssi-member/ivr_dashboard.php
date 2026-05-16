<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}
validation();

// Ensure a valid database connection
if (!$con) {
    echo "Error: Unable to connect to the database.";
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = pg_escape_string($con, $_POST['id']);
    $status = pg_escape_string($con, $_POST['status']);
    $remarks = pg_escape_string($con, $_POST['remarks']);
    $reviewed_by = $associatenumber ?? 'Unknown';
    $reviewed_on = date('Y-m-d H:i:s');

    if (!empty($id)) {
        $update_query = "UPDATE vrc SET status = '$status', reviewed_by = '$reviewed_by', reviewed_on = '$reviewed_on', remarks = '$remarks' WHERE id = '$id'";
        $update_result = pg_query($con, $update_query);

        if (!$update_result) {
            $error_message = "Error updating status: " . pg_last_error($con);
        } else {
            $success_message = "Status updated successfully!";
        }
    } else {
        $error_message = "Error: No ID provided for update.";
    }
}

// Build query with filters
// Default: Show only pending and new entries (status IS NULL)
$where_conditions = [];

// Check if user has applied any filters
$has_filters = isset($_GET['date_range']) || isset($_GET['search_id']) || isset($_GET['status_filter']) || isset($_GET['search_by_id']);

if (!$has_filters) {
    // No filters applied - show only pending by default
    $where_conditions[] = "(status = 'pending' OR status IS NULL)";
} else {
    // Filters are applied - show all except rejected
    $where_conditions[] = "(status IS NULL OR status != 'rejected')";
}

// Handle date range from the single input field
if (!empty($_GET['date_range'])) {
    $date_parts = explode(' to ', $_GET['date_range']);
    $start_date = $date_parts[0];
    $end_date = $date_parts[1] ?? $date_parts[0];
} else {
    $start_date = date('Y-m-d', strtotime('-1 month'));
    $end_date = date('Y-m-d');
}

// Start building the subquery
$subquery = "SELECT DISTINCT ON (vrc.application_number) 
                vrc.*, 
                signup.applicant_name as name 
             FROM vrc 
             LEFT JOIN signup ON vrc.application_number = signup.application_number
             WHERE " . implode(" AND ", $where_conditions) . " 
             ORDER BY vrc.application_number, vrc.timestamp DESC";

// Start building the main query
$main_query = "SELECT * FROM ($subquery) AS latest_entries";

// Build WHERE conditions for the main query
$main_where_conditions = [];

// Check which search mode is active
$search_by_id_mode = (isset($_GET['search_by_id']) && $_GET['search_by_id'] == '1');

if ($search_by_id_mode && !empty($_GET['search_id'])) {
    // Search by ID mode
    $search_id = pg_escape_string($con, $_GET['search_id']);
    $main_where_conditions[] = "(CAST(latest_entries.id AS TEXT) ILIKE '%$search_id%' OR latest_entries.application_number ILIKE '%$search_id%')";
} elseif (!$search_by_id_mode && !empty($_GET['date_range'])) {
    // Search by date range mode
    $start_date = pg_escape_string($con, $start_date);
    $end_date = pg_escape_string($con, $end_date);
    $main_where_conditions[] = "latest_entries.timestamp::date BETWEEN '$start_date' AND '$end_date'";
} elseif (!$has_filters) {
    // Default: Show last 1 month but only for pending entries
    $default_start = date('Y-m-d', strtotime('-1 month'));
    $default_end = date('Y-m-d');
    $main_where_conditions[] = "latest_entries.timestamp::date BETWEEN '$default_start' AND '$default_end'";
}

// Apply status filter if selected (only when user explicitly chooses a status)
$status_filter = isset($_GET['status_filter']) && !empty($_GET['status_filter']) && $_GET['status_filter'] !== 'all'
    ? $_GET['status_filter'] : '';

if (!empty($status_filter)) {
    $main_where_conditions[] = "latest_entries.status = '$status_filter'";
}

// Add WHERE clause if there are conditions
if (count($main_where_conditions) > 0) {
    $main_query .= " WHERE " . implode(" AND ", $main_where_conditions);
}

// Add ORDER BY
$main_query .= " ORDER BY latest_entries.timestamp DESC";

// For debugging - uncomment to see the query
// echo "<pre>$main_query</pre>";

$result = pg_query($con, $main_query);

if (!$result) {
    echo "Error: " . pg_last_error($con);
    exit;
}
?>

<!DOCTYPE html>
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
    <?php include 'includes/meta.php' ?>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

    <!-- Add to head -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <style>
        .status-pending {
            color: orange;
            font-weight: bold;
        }

        .status-approved {
            color: green;
            font-weight: bold;
        }

        .status-rejected {
            color: red;
            font-weight: bold;
        }

        .filter-section {
            /* background: #f8f9fa; */
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .no-data-message {
            text-align: center;
            padding: 40px 20px;
        }

        .no-data-message i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #6c757d;
        }

        .no-data-message h4 {
            margin-bottom: 10px;
            color: #495057;
        }

        .no-data-message p {
            color: #6c757d;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Virtual Response Center - Dashboard</h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>

                            <?php if (isset($success_message)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?php echo $success_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($error_message)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?php echo $error_message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <!-- Filter Section -->
                            <div class="filter-section">
                                <form method="GET" action="" id="filterForm">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="search_id" class="form-label">Search by ID / Application Number</label>
                                            <input type="text" class="form-control" id="search_id" name="search_id"
                                                value="<?php echo isset($_GET['search_id']) ? htmlspecialchars($_GET['search_id']) : ''; ?>"
                                                placeholder="Enter ID or Application Number..."
                                                disabled>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="date_range" class="form-label">Date Range</label>
                                            <input type="text" class="form-control" id="date_range" name="date_range"
                                                value="<?php echo isset($_GET['date_range']) ? htmlspecialchars($_GET['date_range']) : ''; ?>"
                                                placeholder="Select date range">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="status_filter" class="form-label">Status</label>
                                            <select class="form-select" id="status_filter" name="status_filter">
                                                <option value="all">All Status</option>
                                                <option value="pending" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                                <option value="rejected" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                            </select>
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">Search</button>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="toggleSearchCheckbox" name="search_by_id" value="1"
                                                    <?php echo (isset($_GET['search_by_id']) && $_GET['search_by_id'] == '1') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="toggleSearchCheckbox">
                                                    Search by ID/Application Number
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (isset($_GET['search_id']) || isset($_GET['date_range']) || isset($_GET['status_filter'])): ?>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <a href="?" class="btn btn-secondary btn-sm">Clear All Filters</a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>

                            <?php if (pg_num_rows($result) > 0): ?>
                                <div class="table-responsive">
                                    <table id="vrcTable" class="table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Submitted on</th>
                                                <th>Application Number</th>
                                                <th>Name</th>
                                                <th>Video</th>
                                                <th>Status</th>
                                                <th>Reviewed By</th>
                                                <th>Reviewed On</th>
                                                <th>Remarks</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = pg_fetch_assoc($result)): ?>
                                                <?php
                                                $status_class = '';
                                                if ($row['status'] == 'pending') $status_class = 'status-pending';
                                                elseif ($row['status'] == 'approved') $status_class = 'status-approved';
                                                elseif ($row['status'] == 'rejected') $status_class = 'status-rejected';
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['application_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                    <td>
                                                        <?php if (!empty($row['drive_file_link'])): ?>

                                                            <?php
                                                            $drive_url = $row['drive_file_link'];
                                                            $file_id = null;

                                                            if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $drive_url, $matches)) {
                                                                $file_id = $matches[1];
                                                            }
                                                            ?>

                                                            <?php if ($file_id): ?>

                                                                <div class="video-container">
                                                                    <iframe
                                                                        src="https://drive.google.com/file/d/<?php echo htmlspecialchars($file_id); ?>/preview"
                                                                        width="480"
                                                                        height="360"
                                                                        allow="autoplay"
                                                                        frameborder="0">
                                                                    </iframe>
                                                                </div>

                                                            <?php else: ?>

                                                                <span class="text-muted">Invalid Drive link</span>

                                                            <?php endif; ?>

                                                        <?php else: ?>

                                                            <span class="text-muted">No Video</span>

                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($row['status'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['reviewed_by'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['reviewed_on'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['remarks'] ?? '-'); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal"
                                                            data-id="<?php echo htmlspecialchars($row['id']); ?>"
                                                            data-status="<?php echo htmlspecialchars($row['status'] ?? 'pending'); ?>"
                                                            data-remarks="<?php echo htmlspecialchars($row['remarks'] ?? ''); ?>">
                                                            Update Status
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <?php if (!$has_filters): ?>
                                    <!-- No filters applied and no data found - Show "no pending interviews" message -->
                                    <div class="no-data-message">
                                        <i class="bi bi-check-circle"></i>
                                        <h4>No Pending Interviews</h4>
                                        <p>All interviews have been reviewed. Great job!</p>
                                        <p class="text-muted">To view all interviews, use the filters above.</p>
                                    </div>
                                <?php else: ?>
                                    <!-- Filters applied but no data found - Show "no results for filters" message -->
                                    <div class="no-data-message">
                                        <i class="bi bi-search"></i>
                                        <h4>No Interviews Found</h4>
                                        <p>No interviews match your search criteria.</p>
                                        <p class="text-muted">Try adjusting your filters or clearing them to see more results.</p>
                                        <a href="?" class="btn btn-primary btn-sm mt-3">Clear All Filters</a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="statusModalLabel">Update Interview Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" id="modal_id">

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Add your remarks here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTables
            <?php if (pg_num_rows($result) > 0) : ?>
                $('#vrcTable').DataTable({
                    "order": [
                        [1, "desc"]
                    ],
                    "pageLength": 25,
                    "lengthMenu": [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "All"]
                    ],
                    "responsive": true
                });
            <?php endif; ?>

            // Initialize Date Range Picker
            $('#date_range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'YYYY-MM-DD'
                },
                startDate: moment().subtract(1, 'month'),
                endDate: moment()
            });

            $('#date_range').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
            });

            $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            // Handle checkbox toggle - Enable/Disable fields based on checkbox state
            function toggleSearchFields() {
                var isIdSearchMode = $('#toggleSearchCheckbox').is(':checked');

                if (isIdSearchMode) {
                    // ID Search Mode: Enable ID field, Disable Date Range and Status fields
                    $('#search_id').prop('disabled', false);
                    $('#date_range').prop('disabled', true);
                    $('#status_filter').prop('disabled', true);
                    // Clear date and status values when switching to ID mode
                    $('#date_range').val('');
                    $('#status_filter').val('all');
                } else {
                    // Date Range Mode: Disable ID field, Enable Date Range and Status fields
                    $('#search_id').prop('disabled', true);
                    $('#date_range').prop('disabled', false);
                    $('#status_filter').prop('disabled', false);
                    // Set default date range if empty
                    if (!$('#date_range').val()) {
                        var startDate = moment().subtract(1, 'month');
                        var endDate = moment();
                        $('#date_range').val(startDate.format('YYYY-MM-DD') + ' to ' + endDate.format('YYYY-MM-DD'));
                    }
                    // Clear ID value when switching to date mode
                    $('#search_id').val('');
                }
            }

            // Initial toggle on page load
            toggleSearchFields();

            // Toggle when checkbox changes
            $('#toggleSearchCheckbox').change(function() {
                toggleSearchFields();
            });

            // Clear appropriate fields when submitting based on mode
            $('#filterForm').on('submit', function() {
                if (!$('#toggleSearchCheckbox').is(':checked')) {
                    // If in date mode, clear ID field before submit
                    $('#search_id').val('');
                } else {
                    // If in ID mode, clear date field before submit
                    $('#date_range').val('');
                    // Also reset status to 'all' when in ID mode
                    $('#status_filter').val('all');
                }
            });

            // Populate modal with data
            $('#statusModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var id = button.data('id');
                var currentStatus = button.data('status');
                var currentRemarks = button.data('remarks');

                var modal = $(this);
                modal.find('#modal_id').val(id);
                modal.find('#status').val(currentStatus);
                modal.find('#remarks').val(currentRemarks);
            });
        });
    </script>

</body>

</html>