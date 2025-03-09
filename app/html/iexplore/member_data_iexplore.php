<?php
require_once __DIR__ . "/../../bootstrap.php";

$user_check = $_SESSION['eid'];
$user_type = $_SESSION['user_type'];

// Always fetch id, name, and email from test_users
$base_query = "SELECT id, name, email FROM test_users WHERE email = '$user_check'";
$base_result = pg_query($con, $base_query);
$userData = pg_fetch_assoc($base_result);

if ($userData) {
    $id = $userData['id'];
    $name = $userData['name'];
    $email = $userData['email'];

    // Conditionally fetch additional fields based on user type
    if ($user_type == 'rssi-member') {
        $extra_query = "SELECT password_updated_by, password_updated_on, default_pass_updated_on FROM rssimyaccount_members WHERE email = '$user_check'";
    } elseif ($user_type == 'tap') {
        $extra_query = "SELECT password_updated_by, password_updated_on, default_pass_updated_on FROM signup WHERE email = '$user_check'";
    } elseif ($user_type == 'iexplore') {
        $extra_query = "SELECT password_updated_by, password_updated_on, default_pass_updated_on FROM test_users WHERE email = '$user_check'";
    } else {
        $extra_query = "";
    }

    if (!empty($extra_query)) {
        $extra_result = pg_query($con, $extra_query);
        if ($extra_row = pg_fetch_assoc($extra_result)) {
            $userData = array_merge($userData, $extra_row);
        }
    }

    $password_updated_by = $userData['password_updated_by'] ?? null;
    $password_updated_on = $userData['password_updated_on'] ?? null;
    $default_pass_updated_on = $userData['default_pass_updated_on'] ?? null;
}
?>