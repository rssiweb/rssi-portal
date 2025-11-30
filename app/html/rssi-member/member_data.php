<?php
require_once __DIR__ . "/../../bootstrap.php";

// Step 2: Get data from rssi-member-session
$user_check = $_SESSION['aid']; // Copy aid

$view_users_query = "
    SELECT * 
    FROM rssimyaccount_members 
    LEFT JOIN (
        SELECT applicantid, 1 as onleave 
        FROM leavedb_leavedb 
        WHERE CURRENT_DATE BETWEEN fromdate AND todate AND status='Approved'
    ) onleave 
    ON rssimyaccount_members.associatenumber = onleave.applicantid
    WHERE email = '$user_check';";

// Execute the query
$run = pg_query($con, $view_users_query);

// Fetch the result as an associative array
while ($row = pg_fetch_assoc($run)) {
    $associatenumber = $row['associatenumber'];
    $fullname = $row['fullname'];
    $photo = $row['photo'];
    $class = $row['class'];
    $gm = $row['gm'];
    $email = $row['email'];
    $position = $row['position'];
    $engagement = $row['engagement'];
    $identifier = $row['identifier'];
    $filterstatus = $row['filterstatus'];
    $role = $row['role'];
    $absconding = $row['absconding'];
    $password = $row['password'];
    $password_updated_by = $row['password_updated_by'];
    $password_updated_on = $row['password_updated_on'];
    $default_pass_updated_by = $row['default_pass_updated_by'];
    $default_pass_updated_on = $row['default_pass_updated_on'];
    $doj = $row['doj']; //used in point-mart
    $job_type = $row['job_type']; //used in leave
    $twofa_enabled = $row['twofa_enabled'];
    $twofa_secret = $row['twofa_secret'];
}
$active_roles_query = "
    SELECT r.role_name 
    FROM associate_roles ar
    JOIN roles r ON r.id = ar.role_id
    WHERE ar.associatenumber = '$associatenumber'
      AND (ar.effective_to IS NULL OR ar.effective_to >= CURRENT_DATE)
    ORDER BY r.role_name;
";

$active_roles_run = pg_query($con, $active_roles_query);

$active_roles = [];
while ($r = pg_fetch_assoc($active_roles_run)) {
    $active_roles[] = $r['role_name'];
}