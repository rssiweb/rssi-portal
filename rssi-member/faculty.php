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
<?php
include("member_data.php");
?>
<?php
include("database.php");
@$id = $_POST['get_id'];
$result = pg_query($con, "SELECT * FROM rssimyaccount_members WHERE filterstatus='$id' order by filterstatus asc,today desc");
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
  <title>Admin Corner</title>
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
  </style>

</head>

<body>
  <?php include 'header.php'; ?>
  <section id="main-content">
    <section class="wrapper main-wrapper row">
      <div class="col-md-12">
        <section class="box" style="padding: 2%;">
          <form action="" method="POST" style="display: inline-block">
            <div class="form-group" style="display: inline-block;">
              <div class="col2" style="display: inline-block;">
                <select name="get_id" class="form-control" style="width:max-content;" placeholder="Appraisal type" required>
                  <?php if ($id == null) { ?>
                    <option value="" disabled selected hidden>Select Status</option>
                  <?php
                  } else { ?>
                    <option hidden selected><?php echo $id ?></option>
                  <?php }
                  ?>
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
          <?php
          echo '<table class="table">
          <thead>
          <tr>
          <th scope="col" width="7%">Photo</th>
          <th scope="col" width="20%">Volunteer Details</th>
          <th scope="col" width="15%">Contact</th>
          <th scope="col">Designation</th>
          <th scope="col">Class URL</th>
          <th scope="col" width="15%">Association Status</th>
          <th scope="col" width="7%">Password</th>
          <th scope="col">Productivity</th>
          <th scope="col">Badge</th>
        </tr>
        </thead>
        <tbody>';
          foreach ($resultArr as $array) {
            echo '<tr>
            <td><img src="' . $array['photo'] . '" width=50px/></td>
            <td>Name - <b>' . $array['fullname'] . '</b><br>Associate ID - <b>' . $array['associatenumber'] . '</b>
            <br><b>' . $array['gender'] . '&nbsp;(' . $array['age'] . ')</b><br><br>DOJ - ' . $array['doj'] . '<br>' . $array['yos'] . '</td>
            <td>' . $array['phone'] . '<br>'. $array['email'] .'</td>
            <td>' . $array['position'] . '</td>' ?>
            <?php if ($id == "Active") { ?>
              <?php echo '<td><span class="noticet"><a href="' . $array['gm'] . '" target="_blank">' . substr($array['gm'],-12) . '</td>' ?>
            <?php } else { ?>  <?php echo '<td></td>'?>
            <?php } ?>
          <?php echo '<td>' . $array['astatus'] . '<br><br>' . $array['effectivedate'] . '&nbsp;' . $array['remarks'] . '</td>
            <td>' . $array['colors'] . '</td>
            <td>' . $array['classtaken'] . '/' . $array['maxclass'] . '&nbsp' . $array['ctp'] . '</td>
            <td>' . $array['badge'] . '<br>' . $array['vaccination'] . '</td>
            </tr>';
          }
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