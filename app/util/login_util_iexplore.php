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
    // Check if the password was never updated or is older than the default password update
    if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {
        redirectToPasswordReset();
    }

    // Check if the password was updated more than 3 months ago
    $current_time = time(); // Current timestamp
    $password_updated_timestamp = strtotime($password_updated_on); // Convert password_updated_on to timestamp
    $three_months_in_seconds = 3 * 30 * 24 * 60 * 60; // 3 months in seconds (approx)

    if (($current_time - $password_updated_timestamp) > $three_months_in_seconds) {
        redirectToPasswordReset();
    }
}

function redirectToPasswordReset()
{
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

function validation()
{
    global $password_updated_by;
    global $password_updated_on;
    global $default_pass_updated_on;
    // Check default password
    passwordCheck($password_updated_by, $password_updated_on, $default_pass_updated_on);
}
