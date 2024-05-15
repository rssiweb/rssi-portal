<?php
require_once __DIR__ . "/../../bootstrap.php";

$user_check = $_SESSION['aid'];
$view_users_query = "SELECT * FROM signup
WHERE application_number='$user_check'"; // Select query for viewing users.
$run = pg_query($con, $view_users_query); // Execute the SQL query.

while ($row = pg_fetch_assoc($run)) { // Fetch the result as an associative array.
// Now $row is an associative array with column names as keys.
$userData = array(
'application_number' => $row['application_number'],
'applicant_name' => $row['applicant_name'],
'date_of_birth' => $row['date_of_birth'],
'email' => $row['email'],
'password_updated_by' => $row['password_updated_by'],
'password_updated_on' => $row['password_updated_on'],
'default_pass_updated_on' => $row['default_pass_updated_on'],
'applicant_photo' => $row['applicant_photo'],
);
// Accessing applicant_name
    $application_number = $userData['application_number'];
    $applicant_name = $userData['applicant_name'];
    $date_of_birth = $userData['date_of_birth'];
    $email = $userData['email'];
    $password_updated_by = $userData['password_updated_by'];
    $password_updated_on = $userData['password_updated_on'];
    $default_pass_updated_on = $userData['default_pass_updated_on'];
    $applicant_photo = $userData['applicant_photo'];
}
?>