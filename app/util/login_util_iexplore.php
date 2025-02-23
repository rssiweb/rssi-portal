<?php
function isLoggedIn(string $key)
{
    if (!isset($_SESSION[$key]) || !$_SESSION[$key]) {
        return false;
    }
    return true;
}
@include("member_data_iexplore.php");

function passwordCheck($password_updated_by, $password_updated_on, $default_pass_updated_on)
{
    // If the values are NULL, try fetching them from the session
    if ($password_updated_by === NULL || $password_updated_on === NULL || $default_pass_updated_on === NULL) {
        $password_updated_by = $_SESSION['password_updated_by'] ?? NULL;
        $password_updated_on = $_SESSION['password_updated_on'] ?? NULL;
        $default_pass_updated_on = $_SESSION['default_pass_updated_on'] ?? NULL;
    }
    if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {
        // Start output buffering
        ob_start();

        // Determine the redirect URL based on user type
        $redirect_url = "defaultpasswordreset.php"; // Default for iexplore
        if ($_SESSION['user_type'] === 'rssi-member') {
            $redirect_url = "../../rssi-member/index.php";
        } elseif ($_SESSION['user_type'] === 'tap') {
            $redirect_url = "../../tap/index.php";
        }

        // Show the alert and redirect using JavaScript
        echo '<script type="text/javascript">';
        echo 'alert("For security reasons, you must change your password before accessing additional features.");';
        echo 'window.location.href = "' . $redirect_url . '";';
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
