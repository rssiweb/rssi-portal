<?php
// rssi-member/logout.php

// Step 1: Start rssi-member-session
session_name('rssi-member-session');
session_start();

// Step 2: Clear rssi-member-session data
unset($_SESSION['aid']);  // Unset the session variable specific to the member portal
session_destroy(); // Destroy the session
session_write_close();
// Step 3: Start iexplore-session
session_name('iexplore-session');
session_start();

// // Step 4: Clear iexplore-session data
unset($_SESSION['eid']);  // Unset the session variable specific to the member portal
session_destroy(); // Destroy the session
session_write_close();

// Step 5: Redirect to the login page or home page after logout
header("Location: index.php"); // Change this to the appropriate redirection URL
exit;
?>