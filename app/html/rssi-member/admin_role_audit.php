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

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$action_filter = $_GET['action_filter'] ?? '';
$user_filter = $_GET['user_filter'] ?? '';
$admin_filter = $_GET['admin_filter'] ?? '';

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

// Build query
$query_params = [];
$query = "SELECT 
            ral.*,
            m.fullname as user_name,
            r.role_name,
            am.fullname as admin_name
          FROM role_audit_log ral
          LEFT JOIN rssimyaccount_members m ON ral.associatenumber = m.associatenumber
          LEFT JOIN roles r ON ral.role_id = r.id
          LEFT JOIN rssimyaccount_members am ON ral.performed_by = am.associatenumber
          WHERE ral.performed_at BETWEEN $1 AND $2";

$query_params[] = $start_date . ' 00:00:00';
$query_params[] = $end_date . ' 23:59:59';

if ($action_filter) {
    $query .= " AND ral.action = $" . (count($query_params) + 1);
    $query_params[] = $action_filter;
}

if ($user_filter) {
    $query .= " AND (ral.associatenumber ILIKE $" . (count($query_params) + 1) .
        " OR m.fullname ILIKE $" . (count($query_params) + 1) . ")";
    $query_params[] = "%$user_filter%";
}

if ($admin_filter) {
    $query .= " AND (ral.performed_by ILIKE $" . (count($query_params) + 1) .
        " OR am.fullname ILIKE $" . (count($query_params) + 1) . ")";
    $query_params[] = "%$admin_filter%";
}

$query .= " ORDER BY ral.performed_at DESC
            LIMIT $limit OFFSET $offset";

$result = pg_query_params($con, $query, $query_params);
$logs = pg_fetch_all($result) ?: [];

// Get total count
$count_query = "SELECT COUNT(*) as total FROM role_audit_log ral
                LEFT JOIN rssimyaccount_members m ON ral.associatenumber = m.associatenumber
                LEFT JOIN rssimyaccount_members am ON ral.performed_by = am.associatenumber
                WHERE ral.performed_at BETWEEN $1 AND $2";

$count_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

if ($user_filter) {
    $count_query .= " AND (ral.associatenumber ILIKE $" . (count($count_params) + 1) .
        " OR m.fullname ILIKE $" . (count($count_params) + 1) . ")";
    $count_params[] = "%$user_filter%";
}

if ($admin_filter) {
    $count_query .= " AND (ral.performed_by ILIKE $" . (count($count_params) + 1) .
        " OR am.fullname ILIKE $" . (count($count_params) + 1) . ")";
    $count_params[] = "%$admin_filter%";
}

$count_result = pg_query_params($con, $count_query, $count_params);
$total_count = pg_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_count / $limit);

// Get unique actions for filter
$actions_result = pg_query($con, "SELECT DISTINCT action FROM role_audit_log ORDER BY action");
$actions = pg_fetch_all_columns($actions_result, 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Audit Log</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .badge-assign {
            background-color: #28a745;
        }

        .badge-revoke {
            background-color: #dc3545;
        }

        .badge-update {
            background-color: #ffc107;
            color: #000;
        }

        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container-fluid py-4">
                                <!-- Header -->
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h1 class="h3">Role Audit Log</h1>
                                        <p class="text-muted">Track all role-related activities</p>
                                    </div>
                                    <div>
                                        <a href="admin_role_management.php" class="btn btn-outline-primary">
                                            <i class="bi bi-arrow-left"></i> Back to Management
                                        </a>
                                    </div>
                                </div>

                                <!-- Filters -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <form method="GET" class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Start Date</label>
                                                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">End Date</label>
                                                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Action</label>
                                                <select class="form-select" name="action_filter">
                                                    <option value="">All Actions</option>
                                                    <?php foreach ($actions as $action): ?>
                                                        <option value="<?php echo $action; ?>" <?php echo $action_filter == $action ? 'selected' : ''; ?>>
                                                            <?php echo $action; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">User (Target)</label>
                                                <input type="text" class="form-control" name="user_filter" value="<?php echo htmlspecialchars($user_filter); ?>"
                                                    placeholder="User ID or Name">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Admin (Performed By)</label>
                                                <input type="text" class="form-control" name="admin_filter" value="<?php echo htmlspecialchars($admin_filter); ?>"
                                                    placeholder="Admin ID or Name">
                                            </div>
                                            <div class="col-md-12 d-grid">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-filter"></i> Apply Filters
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Audit Log Table -->
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Audit Log Entries</h5>
                                        <span class="badge bg-secondary">Total: <?php echo $total_count; ?></span>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Timestamp</th>
                                                        <th>Action</th>
                                                        <th>User</th>
                                                        <th>Role</th>
                                                        <th>Performed By</th>
                                                        <th>IP Address</th>
                                                        <th>Details</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($logs)): ?>
                                                        <tr>
                                                            <td colspan="7" class="text-center py-4">No audit logs found</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($logs as $log): ?>
                                                            <tr>
                                                                <td>
                                                                    <small><?php echo date('Y-m-d H:i:s', strtotime($log['performed_at'])); ?></small>
                                                                </td>
                                                                <td>
                                                                    <span class="badge badge-<?php echo strtolower($log['action']); ?>">
                                                                        <?php echo $log['action']; ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <strong><?php echo htmlspecialchars($log['user_name'] ?: $log['associatenumber']); ?></strong><br>
                                                                        <small class="text-muted"><?php echo $log['associatenumber']; ?></small>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <?php if ($log['role_name']): ?>
                                                                        <span class="badge bg-primary"><?php echo htmlspecialchars($log['role_name']); ?></span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <div>
                                                                        <strong><?php echo htmlspecialchars($log['admin_name'] ?: $log['performed_by']); ?></strong><br>
                                                                        <small class="text-muted"><?php echo $log['performed_by']; ?></small>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <code><?php echo $log['ip_address']; ?></code>
                                                                </td>
                                                                <td>
                                                                    <?php if ($log['details'] && $log['details'] !== 'null'): ?>
                                                                        <button class="btn btn-sm btn-outline-secondary"
                                                                            onclick="viewDetails(<?php echo htmlspecialchars($log['details']); ?>)">
                                                                            <i class="bi bi-eye"></i> View
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Pagination -->
                                        <?php if ($total_pages > 1): ?>
                                            <div class="card-footer">
                                                <nav aria-label="Page navigation">
                                                    <ul class="pagination justify-content-center mb-0">
                                                        <?php if ($page > 1): ?>
                                                            <li class="page-item">
                                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                                    Previous
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>

                                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                                    <?php echo $i; ?>
                                                                </a>
                                                            </li>
                                                        <?php endfor; ?>

                                                        <?php if ($page < $total_pages): ?>
                                                            <li class="page-item">
                                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                                    Next
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </nav>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="detailsContent"></pre>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  
    <script>
        function viewDetails(details) {
            document.getElementById('detailsContent').textContent =
                JSON.stringify(details, null, 2);
            new bootstrap.Modal(document.getElementById('detailsModal')).show();
        }
    </script>
</body>

</html>