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
    die(json_encode([]));
}

$user_id = $_GET['user_id'] ?? '';
$role_id = $_GET['role_id'] ?? '';

if (empty($user_id) || empty($role_id)) {
    die(json_encode([]));
}

$query = "SELECT id, effective_from, effective_to 
          FROM associate_roles 
          WHERE associatenumber = $1 
            AND role_id = $2
          ORDER BY effective_from DESC";

$result = pg_query_params($con, $query, [$user_id, $role_id]);
$assignments = [];

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $assignments[] = [
            'id' => $row['id'],
            'effective_from' => $row['effective_from'],
            'effective_to' => $row['effective_to']
        ];
    }
}

echo json_encode($assignments);
