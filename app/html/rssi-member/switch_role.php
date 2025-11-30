<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

if (!isset($_POST['new_role']) || empty($_POST['new_role'])) {
    echo json_encode(["status" => "error", "message" => "No role selected"]);
    exit;
}

$new_role_id = $_POST['new_role']; // This is the role ID

// Step 1: Validate the role ID belongs to this user and is active
$validate_query = "
    SELECT r.id, r.role_name 
    FROM associate_roles ar
    JOIN roles r ON r.id = ar.role_id
    WHERE ar.associatenumber = $1
      AND r.id = $2
      AND (ar.effective_to IS NULL OR ar.effective_to >= CURRENT_DATE)
    LIMIT 1;
";

$validate_result = pg_prepare($con, "validate_role", $validate_query);
if (!$validate_result) {
    echo json_encode(["status" => "error", "message" => "Database preparation failed"]);
    exit;
}

$validate_result = pg_execute($con, "validate_role", array($associatenumber, $new_role_id));
if (!$validate_result) {
    echo json_encode(["status" => "error", "message" => "Database execution failed"]);
    exit;
}

if (pg_num_rows($validate_result) === 0) {
    echo json_encode(["status" => "error", "message" => "Invalid role selection!"]);
    exit;
}

// Get the role name for updating the session and rssimyaccount_members
$role_data = pg_fetch_assoc($validate_result);
$role_name = $role_data['role_name'];

// Step 2: Update role in rssimyaccount_members with role name
$update_query = "
    UPDATE rssimyaccount_members
    SET role = $1
    WHERE associatenumber = $2;
";

$update_result = pg_prepare($con, "update_role", $update_query);
if (!$update_result) {
    echo json_encode(["status" => "error", "message" => "Update preparation failed"]);
    exit;
}

$update_result = pg_execute($con, "update_role", array($role_name, $associatenumber));
if (!$update_result) {
    echo json_encode(["status" => "error", "message" => "Update execution failed"]);
    exit;
}

echo json_encode(["status" => "success", "message" => "Role updated successfully"]);
exit;
