<?php
session_start();
// Storing Session
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

  header("Location: index.php"); //redirect to the login page to secure the welcome page without login access.  
} else if ($_SESSION['role'] != 'Admin') {

  //header("Location: index.php"); //redirect to the login page to secure the welcome page without login access.
  echo '<script type="text/javascript">';
  echo 'alert("Access Denied. You are not authorized to access this web page.");';
  echo 'window.location.href = "home.php";';
  echo '</script>';
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
  <script src="https://use.fontawesome.com/releases/v5.15.3/js/all.js" data-auto-replace-svg="nest"></script>
  <!------ Include the above in your HEAD tag ---------->

<script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
        });
    </script>
    <style>
    @media (max-width:767px) {
            td {width:100%}
        }
    </style>

</head>
<div class=container>
  <div class=row>
    <div class=col style="text-align: right; padding: 1%;">
      <span style="display: inline-block;">[<?php echo $user_check ?>]&nbsp;<span class="noticet"><a href="logout.php">Logout</a>
        </span>&nbsp;&nbsp;
        <form action="" method="POST" style="display: inline-block">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <select name="get_id" class="form-control" style="width:max-content;" placeholder="Appraisal type" required>
                                    <option value="" disabled selected hidden>Select Status</option>
                                    <option>Active</option>
                                    <option>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" class="btn btn-success" style="outline: none;">
                                <span class="glyphicon glyphicon-search"></span>&nbsp;Search</button>
                        </div>
                    </form>
    </div>
  </div>
</div>
<?php
include("database.php");
@$id = $_POST['get_id'];
$result = pg_query($con, "SELECT * FROM rssimyaccount_members WHERE filterstatus='$id' order by filterstatus asc,today desc");
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
            <br><b>' . $array['gender'] . '&nbsp;(' . $array['age'] . ')</b><br><br>DOJ - ' . $array['doj'] . '&nbsp;' . $array['yos'] . '</td>
            <td width="15%">' . $array['position'] . '</td>
            <td width="15%">' . $array['class'] . '</td>
            <td><span class="noticet"><a href="' . $array['gm'] . '" target="_blank">' . $array['gm'] . '</td>
            <td width="15%">' . $array['astatus'] . '<br><br>' . $array['effectivedate'] . '&nbsp;' . $array['remarks'] . '</td>
            <td>' . $array['today'] . '</td>
            <td>' . $array['colors'] . '</td>
            <td>' . $array['classtaken'] . '/' . $array['maxclass'] . '&nbsp' . $array['ctp'] . '</td>
            <td>' . $array['badge'] . '</td>
          </tr>';
}
echo '</table>';
?>