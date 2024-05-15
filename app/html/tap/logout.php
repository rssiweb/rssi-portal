<?php 
session_start();
session_destroy();  
header("Location: index.php");//use for the redirection to some page  
?>
