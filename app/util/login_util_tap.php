<?php
function isLoggedIn(string $key)
{
    if (!isset($_SESSION[$key]) || !$_SESSION[$key]) {
        return false;
    }
    return true;
}
@include("member_data_tap.php");

function passwordCheck($password_updated_by, $password_updated_on, $default_pass_updated_on)
{
    if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {
        // Redirect to default password reset page
        header("Location: defaultpasswordreset.php");
        exit;
    }
}

function validation()
{
    global $password_updated_by;
    global $password_updated_on;
    global $default_pass_updated_on;
    // Check default password
    passwordCheck($password_updated_by, $password_updated_on, $default_pass_updated_on);
}
