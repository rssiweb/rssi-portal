<?php
$servername='localhost';
$username='root';
$password='';
$dbname="studentlogin";
$con = mysqli_connect($servername, $username, $password, "$dbname");
if(!$con){
    die('Could not connect My Sql:' .mysql_error());
}
?>