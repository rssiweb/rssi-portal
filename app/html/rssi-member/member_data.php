<?php
require_once __DIR__ . "/../../bootstrap.php";

$user_check = $_SESSION['aid'];
$_SESSION['user_type'] = 'rssi-member';
$view_users_query = "
    SELECT * 
    FROM rssimyaccount_members 
    LEFT JOIN (
        SELECT applicantid, 1 as onleave 
        FROM leavedb_leavedb 
        WHERE CURRENT_DATE BETWEEN fromdate AND todate AND status='Approved'
    ) onleave 
    ON rssimyaccount_members.associatenumber = onleave.applicantid
    WHERE associatenumber = '$user_check';";

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
}
