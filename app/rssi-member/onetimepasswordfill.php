<?php
session_start(); //session starts here 

define('SITE_KEY', '6LfJRc0aAAAAAEhNPCD7ju6si7J4qRUCBSN_8RsL');
define('SECRET_KEY', '6LfJRc0aAAAAAFuZLLd3_7KFmxQ7KPCZmLIiYLDH');
include("database.php");

$query = "select associatenumber, colors from rssimyaccount_members";
$results = pg_query($con, $query);

while ($row_users = pg_fetch_array($results)) {

    $associatenumber =   $row_users['associatenumber'];
    $pass = $row_users['colors'];

    $hash = password_hash($pass, PASSWORD_DEFAULT);

    $set_password_query = "UPDATE rssimyaccount_members SET password='$hash' where associatenumber='$associatenumber'";
    
    echo $set_password_query;
    if(pg_query($con, $set_password_query) === FALSE)
    {
       printf("<br/>Last PG error: %s<br />\n", pg_last_error($con));
    }
    else
    {
       printf("<br/>Succesfully updated<br />\n");
    }
}

?>
