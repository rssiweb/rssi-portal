<?php
session_start();
// Storing Session
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
  header("Location: index.php");
  exit;
} else if ($_SESSION['role'] != 'Admin') {

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
  <title>RSSI Faculty</title>
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
      width: 17%;
    }

    #cw2 {
      width: 15%;
    }

    #cw3 {
      width: 25%;
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
          <a href="facultyexp.php" target="_self" class="btn btn-danger btn-sm" role="button">Faculty Details</a>
          </div>
        </div>
        <section class="box" style="padding: 2%;">
          <form action="" method="POST">
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
          <thead style="font-size: 12px;">
          <tr>
          <th scope="col" id="cw">Photo</th>
          <th scope="col" id="cw1">Volunteer Details</th>
          <th scope="col" id="cw2">Contact</th>
          <th scope="col">Designation</th>
          <th scope="col">Class URL</th>
          <th scope="col" id="cw2">Association Status</th>
          <th scope="col" id="cw">Password</th>
          <th scope="col">Productivity</th>
          <th scope="col">Badge</th>
        </tr>
        </thead>' ?>
          <?php if (sizeof($resultArr) > 0) { ?>
            <?php
            echo '<tbody style="font-size: 13px;">';
            foreach ($resultArr as $array) {
              echo '<tr>
            <td><img src="' . $array['photo'] . '" width=50px/></td>
            <td>Name - <b>' . $array['fullname'] . '</b><br>Associate ID - <b>' . $array['associatenumber'] . '</b>
            <br><b>' . $array['gender'] . '&nbsp;(' . $array['age'] . ')</b><br><br>DOJ - ' . $array['doj'] . '<br>' . $array['yos'] . '</td>
            <td>' . $array['phone'] . '<br>' . $array['email'] . '</td>
            <td>' . substr($array['position'], 0, strrpos($array['position'], "-")) . '</td>' ?>
              <?php if ($id == "Active") { ?>
                <?php echo '<td><span class="noticea"><a href="' . $array['gm'] . '" target="_blank">' . substr($array['gm'], -12) . '</span></td>' ?>
              <?php } else { ?> <?php echo '<td></td>' ?>
              <?php } ?>

              <?php echo '<td style="white-space:unset">' . $array['astatus'] ?><br>

              <?php if ($array['on_leave'] != null && $array['filterstatus'] != 'Inactive') { ?>
                <?php echo '<br><p class="label label-danger">on leave</p>' ?>
              <?php } else {
              } ?>
              <?php if ($array['today'] != 0 && $array['filterstatus'] != 'Inactive') { ?>
                <?php echo '<br><p class="label label-warning">Attd. pending</p>' ?>
              <?php    } else {
              } ?>
            <?php echo '<br><br>' . $array['effectivedate'] . '&nbsp;' . $array['remarks'] . '</td><td>' . $array['colors'] . '</td>
            <td>' . $array['classtaken'] . '/' . $array['maxclass'] . '&nbsp' . $array['ctp'] . '<br><span class="noticea"><a href="' . $array['leaveapply'] . '" target="_blank">Apply leave</a></span><br>s-' . $array['slbal'] . ',&nbsp;c-' . $array['clbal'] . '</td>
            <td>' . $array['badge'] . '<br>' . $array['googlechat'] . '</td>
            </tr>';
            } ?>
          <?php
          } else if ($id == "") {
          ?>
            <tr>
              <td colspan="5">Please select Status.</td>
            </tr>
          <?php
          } else {
          ?>
            <tr>
              <td colspan="5">No record found for <?php echo $id ?></td>
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