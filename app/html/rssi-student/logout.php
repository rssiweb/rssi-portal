<?php
// Start the session with the correct session name for members
session_id('rssi-student-session');  // Use the session name you defined for the member portal
session_start();

// Unset the session variable for the member
unset($_SESSION['aid']);  // Unset the session variable specific to the member portal

// Destroy the session to completely log out the member
session_destroy();

// Redirect to the login page or home page after logout
header("Location: index.php");  // Change this to the appropriate redirection URL
exit;
?>
