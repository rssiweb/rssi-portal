<?php
session_start();
// Storing Session
$user_check = $_SESSION['aid_admin'];

if (!$_SESSION['aid_admin']) {

  header("Location: index_admin.php"); //redirect to the login page to secure the welcome page without login access.
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta name="description" content="">
  <meta name="author" content="">
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Admin Corner</title>
  <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
  <link rel="shortcut icon" href="/img/favicon.ico" type="image/x-icon" />
  <!-- Main css -->
  <link rel="stylesheet" href="../rssi-student/style.css">
  <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <!------ Include the above in your HEAD tag ---------->

</head>

<?php
include("database.php");
$result = pg_query($con, "SELECT * FROM memberdata order by filterstatus asc,today desc");
if (!$result) {
  echo "An error occurred.\n";
  exit;
}

$resultArr = pg_fetch_all($result);
//print_r($resultArr);

echo '<table class="table">
<thead>
    <tr>
         <th>Photo</th>
         <th>Volunteer Details</th>
         <th>Designation</th>
         <th>Class Allotment</th>
         <th>Class URL</th>
         <th>Association Status</th>
         <th>Timesheet defaulters</th>
         <th>Password</th>
         <th>Productivity</th>
         <th>Badge</th>
        </tr>
        </thead>
        <tbody>';


foreach ($resultArr as $array) {
  echo '<tr>
            <td width="7%"><img src="' . $array['photo'] . '" width=50px/></td>
            <td width="20%">Name - <b>' . $array['fullname'] . '</b><br>Associate ID - <b>' . $array['associatenumber'] . '</b>
            <br><b>' . $array['gender'] . '&nbsp;(' . $array['age'] . ')<br><br>DOJ - ' . $array['doj'] . '</td>
            <td width="15%">' . $array['position'] . '</td>
            <td width="15%">' . $array['class'] . '</td>
            <td width="15%"><span class="noticet"><a href="' . $array['gm'] . '" target="_blank">' . $array['gm'] . '</td>
            <td>' . $array['astatus'] . '<br><br>' . $array['effectivedate'] . '&nbsp;' . $array['remarks'] . '</td>
            <td>' . $array['today'] . '</td>
            <td>' . $array['colors'] . '</td>
            <td>' . $array['classtaken'] . '/' . $array['maxclass'] . '&nbsp' . $array['ctp'] . '</td>
            <td>' . $array['badge'] . '</td>
          </tr>';
}
echo '</table>';
?>