<?php
$servername=$_ENV["DB_HOST"];
$username=$_ENV["DB_USER"];
$password=$_ENV["DB_PASSWORD"];
$dbname=$_ENV["DB_NAME"];
$connection_string = "host = $servername user = $username password = $password dbname = $dbname";
$con = pg_connect ( $connection_string )
// if(!$con){
//     die('Could not connect My Sql:' .mysql_error());
// }
?>