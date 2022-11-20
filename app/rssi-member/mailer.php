<?php
include("../util/email.php");
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function

// include("../util/login_util.php");


// if (!isLoggedIn("aid")) {
//     $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
//     header("Location: index.php");
//     exit;
// }

// if ($role != 'Admin') {
//     echo '<script type="text/javascript">';
//     echo 'alert("Access Denied. You are not authorized to access this web page.");';
//     echo 'window.location.href = "home.php";';
//     echo '</script>';
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_name = $_POST['template'];
    $template_data = $_POST['data']; 
    $email = $_POST['email'];
    sendEmail($template_name, $template_data, $email);
}
?>
