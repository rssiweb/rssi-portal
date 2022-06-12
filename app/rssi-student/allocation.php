<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("sid")) {
    header("Location: index.php");
}
$user_check = $_SESSION['sid'];

if (!$_SESSION['sid']) {

  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;  
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
  <title>My Allocation</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
  <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
  <!-- Main css -->
  <style><?php include '../css/style.css'; ?></style>
  <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
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

</head>

<body>
  <?php $allocation_active = 'active'; ?>
  <?php include 'header.php'; ?>

  <section id="main-content">
    <section class="wrapper main-wrapper row">
      <div class="col-md-12">
        
                <section class="box" style="padding: 2%;">

          <table class="table">
            <thead style="font-size: 12px;">
              <tr>
                <th scope="col">Academic session</th>
                <th scope="col">Class allotted<br>(Academic session start date to today)</th>
                <th scope="col">Attendance percentage<br>(Inc. Extra class)</th>
                <th scope="col">Remarks</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><?php echo $allocationdate ?></td>
                <td><?php echo $maxclass ?></td>
                <td><?php echo $attd ?></td>
              </tr>
            </tbody>
          </table>
        </section>
      </div>

      <div class="clearfix"></div>
      <!--**************clearfix**************

           <div class="col-md-12">
                <section class="box">cccccccccccee33</section>
            </div>-->

    </section>
  </section>
</body>

</html>