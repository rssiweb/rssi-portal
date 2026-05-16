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
    $unique_id = pg_escape_string($con, $_POST['unique_id']);
    $status = pg_escape_string($con, $_POST['status']);
    $remarks = pg_escape_string($con, $_POST['remarks']);
    $reviewed_by = $_SESSION['associatenumber'] ?? 'Unknown';
    $reviewed_on = date('Y-m-d H:i:s');

    $update_query = "UPDATE vrc SET status = '$status', reviewed_by = '$reviewed_by', reviewed_on = '$reviewed_on', remarks = '$remarks' WHERE unique_id = '$unique_id'";
    $update_result = pg_query($con, $update_query);

    if (!$update_result) {
        $error_message = "Error updating status: " . pg_last_error($con);
    } else {
        $success_message = "Status updated successfully!";
    }
}

// Build query with filters
$where_conditions = [];
$where_conditions[] = "(status IS NULL OR status != 'rejected')";

// Get unique latest entry per application_number with applicant name from signup table
$subquery = "SELECT DISTINCT ON (vrc.application_number) 
                vrc.*, 
                signup.applicant_name as name 
             FROM vrc 
             LEFT JOIN signup ON vrc.application_number = signup.application_number
             WHERE " . implode(" AND ", $where_conditions) . " 
             ORDER BY vrc.application_number, vrc.timestamp DESC";

$main_query = "SELECT * FROM ($subquery) AS latest_entries ORDER BY latest_entries.timestamp DESC";

// Check which search mode is active
$search_by_id_mode = (isset($_GET['search_by_id']) && $_GET['search_by_id'] == '1');

if ($search_by_id_mode && !empty($_GET['search_id'])) {
    // Search by ID mode
    $search_id = pg_escape_string($con, $_GET['search_id']);
    $main_query = "SELECT * FROM ($subquery) AS latest_entries 
                   WHERE latest_entries.unique_id ILIKE '%$search_id%' 
                      OR latest_entries.application_number ILIKE '%$search_id%' 
                   ORDER BY latest_entries.timestamp DESC";
} elseif (!$search_by_id_mode && !empty($_GET['start_date']) && !empty($_GET['end_date'])) {
    // Search by date range mode
    $start_date = pg_escape_string($con, $_GET['start_date']);
    $end_date = pg_escape_string($con, $_GET['end_date']);
    $main_query = "SELECT * FROM ($subquery) AS latest_entries 
                   WHERE latest_entries.timestamp::date BETWEEN '$start_date' AND '$end_date' 
                   ORDER BY latest_entries.timestamp DESC";
} else {
    // Default: Show last 1 month
    $default_start = date('Y-m-d', strtotime('-1 month'));
    $default_end = date('Y-m-d');
    $main_query = "SELECT * FROM ($subquery) AS latest_entries 
                   WHERE latest_entries.timestamp::date BETWEEN '$default_start' AND '$default_end' 
                   ORDER BY latest_entries.timestamp DESC";
}

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
                                    <!-- First Row: Search inputs (both visible, but one disabled based on checkbox) -->
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="search_id" class="form-label">Search by ID / Application Number</label>
                                            <input type="text" class="form-control" id="search_id" name="search_id"
                                                value="<?php echo isset($_GET['search_id']) ? htmlspecialchars($_GET['search_id']) : ''; ?>"
                                                placeholder="Enter ID or Application Number..."
                                                disabled>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date"
                                                value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : date('Y-m-d', strtotime('-1 month')); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date"
                                                value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">Search</button>
                                        </div>
                                    </div>

                                    <!-- Second Row: Single checkbox to toggle between ID and Date search -->
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

                                    <?php if (isset($_GET['search_id']) || isset($_GET['start_date'])): ?>
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
                                                <th>Unique ID</th>
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
                                                    <td><?php echo htmlspecialchars($row['status'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['reviewed_by'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['reviewed_on'] ?? '-'); ?></td>
                                                    <td><?php echo htmlspecialchars($row['remarks'] ?? '-'); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal"
                                                            data-unique-id="<?php echo htmlspecialchars($row['id']); ?>"
                                                            data-status="<?php echo htmlspecialchars($current_status); ?>"
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
                                <div class="alert alert-info">No interviews found matching the criteria.</div>
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
                        <input type="hidden" name="unique_id" id="modal_unique_id">

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

            // Handle checkbox toggle - Enable/Disable fields based on checkbox state
            function toggleSearchFields() {
                var isIdSearchMode = $('#toggleSearchCheckbox').is(':checked');

                if (isIdSearchMode) {
                    // ID Search Mode: Enable ID field, Disable Date fields
                    $('#search_id').prop('disabled', false);
                    $('#start_date').prop('disabled', true);
                    $('#end_date').prop('disabled', true);
                    // Clear date values when switching to ID mode
                    $('#start_date').val('');
                    $('#end_date').val('');
                } else {
                    // Date Range Mode: Disable ID field, Enable Date fields
                    $('#search_id').prop('disabled', true);
                    $('#start_date').prop('disabled', false);
                    $('#end_date').prop('disabled', false);
                    // Set default date range if empty
                    if (!$('#start_date').val()) {
                        var oneMonthAgo = new Date();
                        oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
                        $('#start_date').val(oneMonthAgo.toISOString().split('T')[0]);
                    }
                    if (!$('#end_date').val()) {
                        $('#end_date').val(new Date().toISOString().split('T')[0]);
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

            // Optional: Clear ID value when submitting in date mode
            $('#filterForm').on('submit', function() {
                if (!$('#toggleSearchCheckbox').is(':checked')) {
                    // If in date mode, clear ID field before submit
                    $('#search_id').val('');
                } else {
                    // If in ID mode, clear date fields before submit
                    $('#start_date').val('');
                    $('#end_date').val('');
                }
            });

            // Populate modal with data
            $('#statusModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var uniqueId = button.data('unique-id');
                var currentStatus = button.data('status');
                var currentRemarks = button.data('remarks');

                var modal = $(this);
                modal.find('#modal_unique_id').val(uniqueId);
                modal.find('#status').val(currentStatus);
                modal.find('#remarks').val(currentRemarks);
            });
        });
    </script>

</body>

</html>