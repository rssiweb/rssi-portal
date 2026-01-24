<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Check if user is logged in
if (!isLoggedIn("aid")) {
    header("Location: login.php");
    exit;
}

// Get active roles directly from database
global $con, $associatenumber, $role;

// Get user's current role name (from rssimyaccount_members)
$current_role_query = "SELECT role FROM rssimyaccount_members WHERE associatenumber = $1";
$current_role_result = pg_query_params($con, $current_role_query, [$associatenumber]);
$current_role_name = 'Unknown Role';
if ($current_role_result && pg_num_rows($current_role_result) > 0) {
    $row = pg_fetch_assoc($current_role_result);
    $current_role_name = $row['role'] ?? 'Unknown Role';
}

// Get active roles
$query = "
    SELECT r.id, r.role_name
    FROM associate_roles ar
    JOIN roles r ON r.id = ar.role_id
    WHERE ar.associatenumber = $1
      AND ar.effective_from <= CURRENT_DATE
      AND (ar.effective_to IS NULL OR ar.effective_to >= CURRENT_DATE)
    ORDER BY r.role_name ASC
";

$result = pg_query_params($con, $query, [$associatenumber]);
$active_roles = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $active_roles[] = [
            'id' => $row['id'],
            'role_name' => $row['role_name']
        ];
    }
}

// If somehow user lands here but has no active roles, redirect
if (empty($active_roles)) {
    echo '<script type="text/javascript">';
    echo 'alert("Your account has no active roles assigned. Please contact support to assign a role.");';
    echo 'window.location.href = "logout.php";';
    echo '</script>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/meta.php' ?>
    
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .role-update-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
        .warning-icon {
            color: #f0ad4e;
            font-size: 48px;
            margin-bottom: 20px;
        }
        #roleDropdown {
            cursor: pointer;
        }
        .modal-backdrop {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="role-update-container">
        <div class="text-center mb-4">
            <div class="warning-icon">⚠️</div>
            <h4 class="text-warning mb-2">Role Update Required</h4>
            <p class="text-muted">You must select a new role to continue</p>
        </div>
        
        <div class="alert alert-warning">
            <strong>Notice:</strong> 
            <span id="currentRoleName"><?php echo htmlspecialchars($current_role_name); ?></span> 
            is no longer active. Please select a new role from your available options.
        </div>
        
        <form id="forceRoleForm" method="POST" action="switch_role.php">
            <div class="mb-4">
                <label for="roleDropdown" class="form-label fw-bold">Select New Role</label>
                <select name="new_role" id="roleDropdown" class="form-select form-select-lg" required>
                    <option value="">Choose a role...</option>
                    <?php foreach ($active_roles as $role_option): ?>
                        <option value="<?php echo $role_option['id']; ?>">
                            <?php echo htmlspecialchars($role_option['role_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Select one of your active roles to continue</div>
            </div>
            
            <div class="d-grid gap-3">
                <button type="submit" class="btn btn-primary btn-lg" id="updateBtn">
                    <span class="button-text">Update & Continue</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
                
                <a href="logout.php" class="btn btn-outline-secondary btn-lg" id="logoutBtn">
                    Logout Instead
                </a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('forceRoleForm');
            const updateBtn = document.getElementById('updateBtn');
            const roleDropdown = document.getElementById('roleDropdown');
            
            // Prevent form submission without selection
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!roleDropdown.value) {
                    alert('Please select a role');
                    roleDropdown.focus();
                    return;
                }
                
                // Show loading state
                const buttonText = updateBtn.querySelector('.button-text');
                const spinner = updateBtn.querySelector('.spinner-border');
                
                updateBtn.disabled = true;
                buttonText.textContent = 'Updating...';
                spinner.classList.remove('d-none');
                
                // Submit form via AJAX
                const formData = new FormData(this);
                
                fetch('switch_role.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        // Success - redirect to home
                        window.location.href = 'home.php';
                    } else {
                        alert(result.message || 'Failed to update role. Please try again.');
                        updateBtn.disabled = false;
                        buttonText.textContent = 'Update & Continue';
                        spinner.classList.add('d-none');
                    }
                })
                .catch(error => {
                    alert('Network error. Please try again.');
                    updateBtn.disabled = false;
                    buttonText.textContent = 'Update & Continue';
                    spinner.classList.add('d-none');
                    console.error('Error:', error);
                });
            });
            
            // Focus on dropdown when page loads
            roleDropdown.focus();
            
            // Prevent closing the page/tab without updating
            window.addEventListener('beforeunload', function(e) {
                if (!updateBtn.disabled) {
                    e.preventDefault();
                    e.returnValue = 'You need to update your role to continue. Are you sure you want to leave?';
                }
            });
        });
    </script>
</body>
</html>