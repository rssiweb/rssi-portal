<?php
// iexplore/logout.php

// Step 1: Start iexplore-session
session_name('iexplore-session');
session_start();

// Step 2: Clear iexplore-session data
unset($_SESSION['eid']);  // Unset the session variable specific to the member portal
session_destroy(); // Destroy the session
session_write_close();

// Step 3: Redirect to the login page or home page after logout
header("Location: home.php"); // Change this to the appropriate redirection URL
exit;
?>