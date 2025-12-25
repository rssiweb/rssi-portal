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
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role_filter'] ?? '';
$status_filter = $_GET['status_filter'] ?? 'active';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Function to log role changes
function logRoleAction($action, $associatenumber, $role_id, $details = [])
{
    global $con, $associatenumber; // admin's associatenumber

    $query = "INSERT INTO role_audit_log (action, associatenumber, role_id, performed_by, details, ip_address) 
              VALUES ($1, $2, $3, $4, $5, $6)";

    pg_query_params($con, $query, [
        $action,
        $associatenumber,
        $role_id,
        $associatenumber, // admin who performed the action
        json_encode($details),
        $_SERVER['REMOTE_ADDR']
    ]);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'assign_role':
                $user_id = $_POST['user_id'];
                $role_id = $_POST['role_id'];
                $effective_from = $_POST['effective_from'] ?? date('Y-m-d');
                $effective_to = !empty($_POST['effective_to']) ? $_POST['effective_to'] : null;

                // Convert empty string to null
                if ($effective_to === '') {
                    $effective_to = null;
                }

                // SIMPLER VALIDATION: Check for active assignments first
                $active_check = pg_query_params(
                    $con,
                    "SELECT id, effective_from, effective_to 
         FROM associate_roles 
         WHERE associatenumber = $1 
           AND role_id = $2
           AND (
               -- Check if new assignment overlaps with existing
               (effective_from <= $3::date AND 
                (effective_to IS NULL OR effective_to >= $3::date))
               OR
               -- Check if new assignment ends within existing (if end date provided)
               ($4::date IS NOT NULL AND 
                effective_from <= $4::date AND
                (effective_to IS NULL OR effective_to >= $4::date))
               OR
               -- Check if new assignment surrounds existing
               ($3::date <= effective_from AND
                ($4::date IS NULL OR $4::date >= COALESCE(effective_to, '9999-12-31'::date)))
           )",
                    [$user_id, $role_id, $effective_from, $effective_to]
                );

                if (!$active_check) {
                    $message = "error|Database error: " . pg_last_error($con);
                } elseif (pg_num_rows($active_check) > 0) {
                    $overlapping = pg_fetch_all($active_check);
                    $assignment_info = [];
                    foreach ($overlapping as $assign) {
                        $end_date = $assign['effective_to'] ?: 'No end date';
                        $assignment_info[] = "From {$assign['effective_from']} to {$end_date}";
                    }

                    $assignments_list = implode(', ', $assignment_info);
                    $message = "error|Role assignment overlaps with existing assignment(s): {$assignments_list}";
                } else {
                    // No overlap, proceed with insertion
                    $query = "INSERT INTO associate_roles (associatenumber, role_id, assigned_by, 
                 effective_from, effective_to) 
                 VALUES ($1, $2, $3, $4, $5)";

                    $result = pg_query_params($con, $query, [
                        $user_id,
                        $role_id,
                        $associatenumber,
                        $effective_from,
                        $effective_to
                    ]);

                    if ($result) {
                        // ... rest of successful insertion code ...
                        $message = "success|Role assigned successfully";
                    } else {
                        $message = "error|Failed to assign role: " . pg_last_error($con);
                    }
                }
                break;

            case 'revoke_role':
                $record_id = $_POST['record_id'];

                // Get role info for logging
                $role_info_query = pg_query(
                    $con,
                    "SELECT associatenumber, role_id FROM associate_roles WHERE id = $record_id"
                );

                if ($role_info_query && pg_num_rows($role_info_query) > 0) {
                    $role_info = pg_fetch_assoc($role_info_query);

                    // Set effective_to to today to revoke
                    $query = "UPDATE associate_roles SET effective_to = CURRENT_DATE WHERE id = $1";
                    $result = pg_query_params($con, $query, [$record_id]);

                    if ($result) {
                        logRoleAction('REVOKE', $role_info['associatenumber'], $role_info['role_id']);

                        // Check if this was user's current active role
                        $user_role_query = pg_query_params(
                            $con,
                            "SELECT role FROM rssimyaccount_members WHERE associatenumber = $1",
                            [$role_info['associatenumber']]
                        );

                        if ($user_role_query && pg_num_rows($user_role_query) > 0) {
                            $user_role = pg_fetch_assoc($user_role_query);
                            $role_name_query = pg_query_params(
                                $con,
                                "SELECT role_name FROM roles WHERE id = $1",
                                [$role_info['role_id']]
                            );

                            if ($role_name_query && pg_num_rows($role_name_query) > 0) {
                                $role_row = pg_fetch_assoc($role_name_query);
                                if ($user_role['role'] === $role_row['role_name']) {
                                    // User's current role was revoked, set to null
                                    pg_query_params(
                                        $con,
                                        "UPDATE rssimyaccount_members SET role = NULL WHERE associatenumber = $1",
                                        [$role_info['associatenumber']]
                                    );
                                }
                            }
                        }

                        $message = "success|Role revoked successfully";
                    } else {
                        $message = "error|Failed to revoke role";
                    }
                }
                break;

            case 'update_role':
                $record_id = $_POST['record_id'];
                $effective_from = $_POST['effective_from'];
                $effective_to = $_POST['effective_to'] ?: null;

                $query = "UPDATE associate_roles 
                         SET effective_from = $1, effective_to = $2 
                         WHERE id = $3";

                $result = pg_query_params($con, $query, [$effective_from, $effective_to, $record_id]);

                if ($result) {
                    // Log the action
                    $role_info_query = pg_query(
                        $con,
                        "SELECT associatenumber, role_id FROM associate_roles WHERE id = $record_id"
                    );

                    if ($role_info_query && pg_num_rows($role_info_query) > 0) {
                        $role_info = pg_fetch_assoc($role_info_query);
                        logRoleAction('UPDATE', $role_info['associatenumber'], $role_info['role_id'], [
                            'effective_from' => $effective_from,
                            'effective_to' => $effective_to
                        ]);
                    }

                    $message = "success|Role assignment updated successfully";
                } else {
                    $message = "error|Failed to update role assignment";
                }
                break;

            case 'create_role':
                $role_name = trim($_POST['role_name']);
                $description = trim($_POST['description'] ?? '');

                if (empty($role_name)) {
                    $message = "error|Role name is required";
                } else {
                    $query = "INSERT INTO roles (role_name, description) VALUES ($1, $2)";
                    $result = pg_query_params($con, $query, [$role_name, $description]);

                    if ($result) {
                        $message = "success|Role created successfully";
                    } else {
                        $message = "error|Failed to create role (possibly duplicate name)";
                    }
                }
                break;
        }

        // Redirect to avoid form resubmission
        header("Location: admin_role_management.php?message=" . urlencode($message));
        exit;
    }
}

// Get message from URL
$message = $_GET['message'] ?? '';
if ($message) {
    list($type, $text) = explode('|', $message, 2);
}

// Fetch all roles for filters
$roles_result = pg_query($con, "SELECT id, role_name FROM roles ORDER BY role_name");
$all_roles = [];
while ($row = pg_fetch_assoc($roles_result)) {
    $all_roles[$row['id']] = $row['role_name'];
}

// Build query for users
$query_params = [];
$query = "SELECT 
            m.associatenumber,
            m.fullname,
            m.email,
            m.role as current_role,
            m.filterstatus,
            array_agg(DISTINCT r.role_name) as assigned_roles,
            COUNT(ar.id) as role_count
          FROM rssimyaccount_members m
          LEFT JOIN associate_roles ar ON m.associatenumber = ar.associatenumber 
            AND ar.effective_from <= CURRENT_DATE 
            AND (ar.effective_to IS NULL OR ar.effective_to >= CURRENT_DATE)
          LEFT JOIN roles r ON ar.role_id = r.id
          WHERE 1=1";

// Add search filters
if ($search) {
    $query .= " AND (m.associatenumber ILIKE $1 OR m.fullname ILIKE $1 OR m.email ILIKE $1)";
    $query_params[] = "%$search%";
}

// Add role filter
if ($role_filter && is_numeric($role_filter)) {
    $query .= " AND ar.role_id = $" . (count($query_params) + 1);
    $query_params[] = $role_filter;
}

// Add status filter
if ($status_filter === 'inactive') {
    $query .= " AND m.filterstatus != 'Active'";
} elseif ($status_filter === 'active') {
    $query .= " AND m.filterstatus = 'Active'";
}

// Complete query
$query .= " GROUP BY m.associatenumber, m.fullname, m.email, m.role, m.filterstatus
            ORDER BY m.fullname ASC
            LIMIT $limit OFFSET $offset";

// Execute query
$result = pg_query_params($con, $query, $query_params);
$users = pg_fetch_all($result) ?: [];

// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT m.associatenumber) as total
                FROM rssimyaccount_members m
                LEFT JOIN associate_roles ar ON m.associatenumber = ar.associatenumber 
                  AND ar.effective_from <= CURRENT_DATE 
                  AND (ar.effective_to IS NULL OR ar.effective_to >= CURRENT_DATE)
                WHERE 1=1";

$count_params = [];
if ($search) {
    $count_query .= " AND (m.associatenumber ILIKE $1 OR m.fullname ILIKE $1 OR m.email ILIKE $1)";
    $count_params[] = "%$search%";
}

if ($status_filter === 'inactive') {
    $count_query .= " AND m.filterstatus != 'Active'";
} elseif ($status_filter === 'active') {
    $count_query .= " AND m.filterstatus = 'Active'";
}

$count_result = pg_query_params($con, $count_query, $count_params);
$total_count = pg_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_count / $limit);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Management Console</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .badge-role {
            background-color: #6c757d;
            color: white;
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }

        .status-active {
            color: #28a745;
        }

        .status-inactive {
            color: #dc3545;
        }

        .modal-lg {
            max-width: 800px;
        }

        .nav-tabs .nav-link.active {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>RMC</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">RMC</li>
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
                            <div class="container-fluid py-4">
                                <!-- Header -->
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h1 class="h3">Role Management Console</h1>
                                        <p class="text-muted">Manage user roles and permissions</p>
                                    </div>
                                    <div>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                                            <i class="bi bi-plus-circle"></i> Create New Role
                                        </button>
                                        <a href="admin_role_audit.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-clock-history"></i> Audit Log
                                        </a>
                                    </div>
                                </div>

                                <!-- Message Alert -->
                                <?php if (!empty($message)): ?>
                                    <div class="alert alert-<?php echo $type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                                        <?php echo htmlspecialchars($text); ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <!-- Filters -->
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <form method="GET" class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Search Users</label>
                                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                                    placeholder="Name, Email, or ID">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Filter by Role</label>
                                                <select class="form-select" name="role_filter">
                                                    <option value="">All Roles</option>
                                                    <?php foreach ($all_roles as $id => $name): ?>
                                                        <option value="<?php echo $id; ?>" <?php echo $role_filter == $id ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($name); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status_filter">
                                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Users</option>
                                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive Users</option>
                                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Users</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <i class="bi bi-filter"></i> Filter
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Users Table -->
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">User Roles</h5>
                                        <span class="badge bg-secondary">Total: <?php echo $total_count; ?></span>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>User ID</th>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Status</th>
                                                        <th>Current Role</th>
                                                        <th>Assigned Roles</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($users)): ?>
                                                        <tr>
                                                            <td colspan="7" class="text-center py-4">No users found</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($users as $user): ?>
                                                            <tr>
                                                                <td>
                                                                    <code><?php echo htmlspecialchars($user['associatenumber']); ?></code>
                                                                </td>
                                                                <td>
                                                                    <?php echo htmlspecialchars($user['fullname']); ?>
                                                                </td>
                                                                <td>
                                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                                </td>
                                                                <td>
                                                                    <span class="status-<?php echo strtolower($user['filterstatus']); ?>">
                                                                        <i class="bi bi-circle-fill"></i>
                                                                        <?php echo htmlspecialchars($user['filterstatus']); ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <?php if ($user['current_role']): ?>
                                                                        <span class="badge bg-primary"><?php echo htmlspecialchars($user['current_role']); ?></span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-secondary">No Role</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    $assigned_roles = explode(',', $user['assigned_roles']);
                                                                    foreach ($assigned_roles as $role_name):
                                                                        if (!empty(trim($role_name))):
                                                                    ?>
                                                                            <span class="badge badge-role"><?php echo htmlspecialchars(trim($role_name)); ?></span>
                                                                        <?php
                                                                        endif;
                                                                    endforeach;
                                                                    if ($user['role_count'] == 0): ?>
                                                                        <span class="text-muted">No active roles</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <div class="btn-group btn-group-sm">
                                                                        <button class="btn btn-outline-primary"
                                                                            onclick="openManageModal('<?php echo $user['associatenumber']; ?>', 
                                                                           '<?php echo htmlspecialchars($user['fullname']); ?>')">
                                                                            <i class="bi bi-gear"></i> Manage
                                                                        </button>
                                                                        <button class="btn btn-outline-success"
                                                                            onclick="openAssignModal('<?php echo $user['associatenumber']; ?>', 
                                                                           '<?php echo htmlspecialchars($user['fullname']); ?>')">
                                                                            <i class="bi bi-plus-circle"></i> Assign
                                                                        </button>
                                                                    </div>
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

    <!-- Create Role Modal -->
    <div class="modal fade" id="createRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Role Name *</label>
                        <input type="text" class="form-control" name="role_name" required
                            placeholder="e.g., Project Manager, Team Lead">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"
                            placeholder="Describe this role's responsibilities"></textarea>
                    </div>
                    <input type="hidden" name="action" value="create_role">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Role</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Role Modal -->
    <div class="modal fade" id="assignRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Role to User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">User</label>
                        <input type="text" class="form-control" id="assign_user_name" readonly>
                        <input type="hidden" name="user_id" id="assign_user_id">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role_id" required>
                            <option value="">Select a role</option>
                            <?php foreach ($all_roles as $id => $name): ?>
                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Effective From *</label>
                            <input type="date" class="form-control" name="effective_from"
                                value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Effective To (Optional)</label>
                            <input type="date" class="form-control" name="effective_to">
                            <div class="form-text">Leave empty for permanent assignment</div>
                        </div>
                    </div>
                    <input type="hidden" name="action" value="assign_role">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Role</button>
                </div>
            </form>
            <!-- In assignRoleModal, after the form fields -->
            <div id="existingAssignments" class="mt-3" style="display: none;">
                <div class="alert alert-info">
                    <h6>Existing Assignments for this Role:</h6>
                    <ul id="assignmentsList" class="mb-0"></ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Manage Roles Modal -->
    <div class="modal fade" id="manageRolesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage User Roles</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 id="manage_user_name" class="mb-3"></h6>
                    <div id="rolesList">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        // Open Assign Role Modal
        function openAssignModal(userId, userName) {
            document.getElementById('assign_user_id').value = userId;
            document.getElementById('assign_user_name').value = userName;
            new bootstrap.Modal(document.getElementById('assignRoleModal')).show();
        }

        // Open Manage Roles Modal
        function openManageModal(userId, userName) {
            document.getElementById('manage_user_name').textContent = userName + ' - Roles';

            // Load user's roles via AJAX
            $.ajax({
                url: 'ajax_get_user_roles.php',
                method: 'GET',
                data: {
                    user_id: userId
                },
                success: function(response) {
                    $('#rolesList').html(response);
                    new bootstrap.Modal(document.getElementById('manageRolesModal')).show();
                },
                error: function() {
                    $('#rolesList').html('<div class="alert alert-danger">Failed to load roles</div>');
                    new bootstrap.Modal(document.getElementById('manageRolesModal')).show();
                }
            });
        }

        // Revoke role confirmation
        function confirmRevoke(recordId, roleName) {
            if (confirm('Are you sure you want to revoke the role: ' + roleName + '?')) {
                $.ajax({
                    url: 'admin_role_management.php',
                    method: 'POST',
                    data: {
                        action: 'revoke_role',
                        record_id: recordId
                    },
                    success: function() {
                        location.reload();
                    }
                });
            }
        }

        // Update role assignment
        function updateRoleAssignment(form) {
            if (confirm('Update this role assignment?')) {
                $.ajax({
                    url: 'admin_role_management.php',
                    method: 'POST',
                    data: $(form).serialize(),
                    success: function() {
                        location.reload();
                    }
                });
            }
            return false;
        }
    </script>
    <script>
        // Add this to your existing JavaScript
        document.querySelector('select[name="role_id"]').addEventListener('change', function() {
            const userId = document.getElementById('assign_user_id').value;
            const roleId = this.value;

            if (userId && roleId) {
                // Fetch existing assignments for this user and role
                $.ajax({
                    url: 'ajax_get_existing_assignments.php',
                    method: 'GET',
                    data: {
                        user_id: userId,
                        role_id: roleId
                    },
                    success: function(response) {
                        const container = document.getElementById('existingAssignments');
                        const list = document.getElementById('assignmentsList');

                        if (response.length > 0) {
                            list.innerHTML = '';
                            response.forEach(assignment => {
                                const item = document.createElement('li');
                                item.innerHTML = `
                            ${assignment.effective_from} 
                            ${assignment.effective_to ? 'to ' + assignment.effective_to : '(no end date)'}
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2" 
                                    onclick="editAssignment(${assignment.id})">
                                Edit
                            </button>
                        `;
                                list.appendChild(item);
                            });
                            container.style.display = 'block';
                        } else {
                            container.style.display = 'none';
                        }
                    }
                });
            }
        });

        function editAssignment(assignmentId) {
            // Close current modal and open edit modal
            bootstrap.Modal.getInstance(document.getElementById('assignRoleModal')).hide();
            // You would need to implement this function
            // openEditAssignmentModal(assignmentId);
        }
    </script>
</body>

</html>