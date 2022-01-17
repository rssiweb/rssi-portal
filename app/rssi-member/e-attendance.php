<?php
session_start();
// Storing Session
include("../util/login_util.php");

if(! isLoggedIn("aid")){
    header("Location: index.php");
}
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
} else if ($_SESSION['filterstatus'] != 'Active') {

  //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
  echo '<script type="text/javascript">';
  echo 'alert("Access Denied. You are not authorized to access this web page.");';
  echo 'window.location.href = "home.php";';
  echo '</script>';
}
?>
<?php
include("member_data.php");
?>
<?php
include("database.php");

$result = pg_query($con, "SELECT * FROM rssimyprofile_student WHERE class='Vocational training' AND filterstatus='Active'");

if (!$result) {
  echo "An error occurred.\n";
  exit;
}

$resultArr = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html>

<head>
  <meta name="description" content="">
  <meta name="author" content="">
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>e-attendance</title>
  <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
  <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
  <!-- Main css -->
  <style>
    <?php include '../css/style.css'; ?>
  </style>
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
      td {
        width: 100%
      }
    }

    td {

      /* css-3 */
      white-space: -o-pre-wrap;
      word-wrap: break-word;
      white-space: pre-wrap;
      white-space: -moz-pre-wrap;
      white-space: -pre-wrap;

    }

    table {
      table-layout: fixed;
      width: 100%
    }

    @media (min-width:767px) {
      .left {
        margin-left: 2%;
      }
    }
    @media (max-width:767px) {

#cw,
#cw1,
#cw2,
#cw3 {
  width: 100% !important;
}

}

#cw {
width: 7%;
}

#cw1 {
width: 20%;
}

#cw2 {
width: 8%;
}

#cw3 {
width: 15%;
}
#cw4 {
width: 10%;
}
  </style>

</head>

<body>
  <?php include 'header.php'; ?>
  <section id="main-content">
    <section class="wrapper main-wrapper row">
      <div class="col-md-12">
        <div class="row">
          <div class="col" style="display: inline-block; width:50%;margin-left:1.5%">
            Record count:&nbsp;<?php echo sizeof($resultArr) ?>
          </div>
          <div class="col" style="display: inline-block; width:47%; text-align:right">
            Home / RSSI Student
          </div>
        </div>
        <section class="box" style="padding: 2%;">

          <?php
          echo '<table class="table">
          <thead style="font-size: 12px;">
          <tr>
          <th scope="col">Photo</th>
          <th scope="col">Student Details</th>
          <th scope="col">Class</th>
          <th scope="col">Attendance</th>
        </tr>
        </thead>' ?>
          <?php if (sizeof($resultArr) > 0) { ?>
            <?php
            echo '<tbody style="font-size: 13px;">';
            foreach ($resultArr as $array) {
              echo '<tr>
            <td><img src="' . $array['photourl'] . '" width=50px/></td>
            <td>Name - <b>' . $array['studentname'] . '</b><br>Student ID - <b>' . $array['student_id'] . '</b></td>
            <td>' . $array['class'] . '/' . $array['category'] . ' </td>
            <td style="white-space:unset">
            <a href="https://docs.google.com/forms/d/e/1FAIpQLSdAk2s6tvvANlB08RAPqn_4cK31PN2VlCpWY5QpYmvgb0F21g/formResponse?usp=pp_url&entry.2105567962=' . $array['studentname'] . '&entry.988384435=Present" target="_blank" class="btn btn-success btn-sm" role="button">Present</a>
            <a href="https://docs.google.com/forms/d/e/1FAIpQLSdAk2s6tvvANlB08RAPqn_4cK31PN2VlCpWY5QpYmvgb0F21g/formResponse?usp=pp_url&entry.2105567962=' . $array['studentname'] . '&entry.988384435=Absent" target="_blank" class="btn btn-danger btn-sm" role="button">Absent</a></td>
            </tr>';
            } ?>
          <?php
          } else if ($id == "" && $category == "") {
          ?>
            <tr>
              <td colspan="5">Please select Filter value.</td>
            </tr>
          <?php
          } else {
          ?>
            <tr>
              <td colspan="5">No record found for <?php echo $module ?>, <?php echo $id ?> and <?php echo $category ?>&nbsp;<?php echo $class ?></td>
            </tr>
          <?php }

          echo '</tbody>
                        </table>';
          ?>
      </div>
      </div>
      </div>
    </section>
    </div>
  </section>
  </section>
</body>

</html>