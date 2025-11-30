<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

header("Content-Type: application/json");

if (!isLoggedIn("aid")) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

/* ---------------------------------------------------------
   1. FETCH ACTIVE ROLES ASSIGNED TO THIS USER
--------------------------------------------------------- */

$rolesQuery = pg_query($con, "
    SELECT r.id, r.role_name
    FROM associate_roles ar
    JOIN roles r ON r.id = ar.role_id
    WHERE ar.associatenumber = '$associatenumber'
      AND ar.effective_from <= CURRENT_DATE
      AND (ar.effective_to IS NULL OR ar.effective_to >= CURRENT_DATE)
    ORDER BY r.role_name ASC
");

$roles = [];
while ($row = pg_fetch_assoc($rolesQuery)) {
    $roles[] = [
        "id" => (int)$row["id"],
        "role_name" => $row["role_name"]
    ];
}

/* ---------------------------------------------------------
   2. FETCH CURRENT ROLE FROM rssimyaccount_members
--------------------------------------------------------- */

$currentRoleQuery = pg_query($con, "
    SELECT role
    FROM rssimyaccount_members
    WHERE associatenumber = '$associatenumber'
    LIMIT 1
");

$current_role = null;
if ($row = pg_fetch_assoc($currentRoleQuery)) {
    $current_role = $row["role"] ?? null;   // <-- FIXED (no int cast)
}

/* ---------------------------------------------------------
   3. SEND JSON
--------------------------------------------------------- */

echo json_encode([
    "roles" => $roles,
    "current_role" => $current_role
]);

exit;
