<?php
require_once __DIR__ . "/../../bootstrap.php";

$user_check = $_SESSION['rid'];
$view_users_query = "SELECT * FROM recruiters
WHERE email='$user_check'"; // Select query for viewing users.
$run = pg_query($con, $view_users_query); // Execute the SQL query.

while ($row = pg_fetch_assoc($run)) { // Fetch the result as an associative array.
    // Now $row is an associative array with column names as keys.
    $userData = array(
        'id' => $row['id'],
        'full_name' => $row['full_name'],
        'email' => $row['email'],
        'password_updated_by' => $row['password_updated_by'],
        'password_updated_on' => $row['password_updated_on'],
        'default_pass_updated_on' => $row['default_pass_updated_on'],
        'recruiter_photo' => $row['recruiter_photo'],
    );
    // Accessing applicant_name
    $recruiter_id = $userData['id'];
    $recruiter_name = $userData['full_name'];
    $email = $userData['email'];
    $password_updated_by = $userData['password_updated_by'];
    $password_updated_on = $userData['password_updated_on'];
    $default_pass_updated_on = $userData['default_pass_updated_on'];
    $recruiter_photo = $userData['recruiter_photo'];
}
