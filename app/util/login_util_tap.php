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
        // Start output buffering
        ob_start();

        // Show the alert and redirect using JavaScript
        echo '<script type="text/javascript">';
        echo 'alert("For security reasons, you must change your password before accessing additional features.");';
        echo 'window.location.href = "defaultpasswordreset.php";';
        echo '</script>';

        // End output buffering and send the output
        ob_end_flush();

        // Use exit to stop further script execution
        exit();
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
