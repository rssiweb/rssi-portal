<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Check if user has admin access
function hasAdminAccess()
{
    global $role;
    $admin_roles = ['Admin', 'Super Admin', 'HR Admin'];
    return in_array($role, $admin_roles);
}

if (!isLoggedIn("aid") || !hasAdminAccess()) {
    die("Access Denied");
}

$user_id = $_GET['user_id'] ?? '';

if (empty($user_id)) {
    die("User ID required");
}

// Get user's current and all role assignments
$query = "SELECT 
            ar.id as assignment_id,
            r.id as role_id,
            r.role_name,
            ar.effective_from,
            ar.effective_to,
            ar.assigned_by,
            ar.assigned_at,
            CASE 
                WHEN ar.effective_from <= CURRENT_DATE 
                AND (ar.effective_to IS NULL OR ar.effective_to >= CURRENT_DATE) 
                THEN 'Active'
                ELSE 'Inactive'
            END as status
          FROM associate_roles ar
          JOIN roles r ON ar.role_id = r.id
          WHERE ar.associatenumber = $1
          ORDER BY ar.effective_from DESC";

$result = pg_query_params($con, $query, [$user_id]);

if (!$result) {
    die("Database error");
}

$assignments = pg_fetch_all($result) ?: [];
?>

<?php if (empty($assignments)): ?>
    <div class="alert alert-info">No roles assigned to this user.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Effective From</th>
                    <th>Effective To</th>
                    <th>Assigned By</th>
                    <th>Assigned On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $assignment): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($assignment['role_name']); ?></strong>
                    </td>
                    <td>
                        <span class="badge bg-<?php echo $assignment['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                            <?php echo $assignment['status']; ?>
                        </span>
                    </td>
                    <td><?php echo $assignment['effective_from']; ?></td>
                    <td><?php echo $assignment['effective_to'] ?: 'Permanent'; ?></td>
                    <td><?php echo htmlspecialchars($assignment['assigned_by']); ?></td>
                    <td><?php echo $assignment['assigned_at']; ?></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <?php if ($assignment['status'] === 'Active'): ?>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="confirmRevoke(<?php echo $assignment['assignment_id']; ?>, '<?php echo $assignment['role_name']; ?>')">
                                    <i class="bi bi-x-circle"></i> Revoke
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-outline-primary btn-sm" 
                                    onclick="openUpdateModal(<?php echo $assignment['assignment_id']; ?>, 
                                                            '<?php echo $assignment['effective_from']; ?>',
                                                            '<?php echo $assignment['effective_to']; ?>')">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Update Role Modal (inline) -->
    <div class="modal fade" id="updateRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" onsubmit="return updateRoleAssignment(this)" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Role Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Effective From *</label>
                            <input type="date" class="form-control" name="effective_from" id="update_effective_from" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Effective To</label>
                            <input type="date" class="form-control" name="effective_to" id="update_effective_to">
                            <div class="form-text">Leave empty for permanent</div>
                        </div>
                    </div>
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="record_id" id="update_record_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUpdateModal(recordId, effectiveFrom, effectiveTo) {
            document.getElementById('update_record_id').value = recordId;
            document.getElementById('update_effective_from').value = effectiveFrom;
            document.getElementById('update_effective_to').value = effectiveTo || '';
            new bootstrap.Modal(document.getElementById('updateRoleModal')).show();
        }
    </script>
<?php endif; ?>