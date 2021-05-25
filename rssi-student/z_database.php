<?php
$servername='ec2-35-170-85-206.compute-1.amazonaws.com';
$username='qodkygpumethmw';
$password='078e05e9b52d93eede9e35174f9b985281c0539ee9fb62374baedd727f09fbe8';
$dbname="studentlogin";
$con = mysqli_connect($servername, $username, $password, "$dbname");
if(!$con){
    die('Could not connect My Sql:' .mysql_error());
}
?>