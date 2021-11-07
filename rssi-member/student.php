<?php
session_start();
// Storing Session
$user_check = $_SESSION['aid'];

if(!$_SESSION['aid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;  
  }
  else if ($_SESSION['filterstatus']!='Active') {

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
@$category = $_POST['get_category'];
@$module = $_POST['get_module'];

if ($id == 'ALL' && $category == 'ALL') {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student WHERE module='$module' order by filterstatus asc, category asc");
} else if ($id == 'ALL' && $category != 'ALL') {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student WHERE module='$module' AND category='$category' order by filterstatus asc,category asc");
} else if ($id > 0 && $category != 'ALL') {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student WHERE module='$module' AND filterstatus='$id' AND category='$category' order by category asc");
} else if ($id > 0 && $category == 'ALL') {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student WHERE module='$module' AND filterstatus='$id' order by category asc");
} else {
  $result = pg_query($con, "SELECT * FROM rssimyprofile_student WHERE module='$module' AND filterstatus='$id' order by category asc");
}

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
  <title>Student database</title>
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
          <form action="" method="POST">
            <div class="form-group" style="display: inline-block;">
              <div class="col2" style="display: inline-block;">
              <select name="get_module" class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type" required>
                  <?php if ($id == null) { ?>
                    <option value="" disabled selected hidden>Select Module</option>
                  <?php
                  } else { ?>
                    <option hidden selected><?php echo $module ?></option>
                  <?php }
                  ?>
                  <option>National</option>
                  <option>State</option>
                </select>
                <select name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type" required>
                  <?php if ($id == null) { ?>
                    <option value="" disabled selected hidden>Select Status</option>
                  <?php
                  } else { ?>
                    <option hidden selected><?php echo $id ?></option>
                  <?php }
                  ?>
                  <option>Active</option>
                  <option>Inactive</option>
                  <option>ALL</option>
                </select>
                <select name="get_category" class="form-control" style="width:max-content;display:inline-block" placeholder="Appraisal type" required>
                  <?php if ($category == null) { ?>
                    <option value="" disabled selected hidden>Select Category</option>
                  <?php
                  } else { ?>
                    <option hidden selected><?php echo $category ?></option>
                  <?php }
                  ?>
                  <option>LG2-A</option>
                  <option>LG2-B</option>
                  <option>LG2-C</option>
                  <option>LG3</option>
                  <option>LG4</option>
                  <option>LG4S1</option>
                  <option>LG4S2</option>
                  <option>Undefined</option>
                  <option>ALL</option>
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
          <th scope="col" width="20%">Student Details</th>
          <th scope="col" width="8%">Class</th>
          <th scope="col" width="15%">Contact</th>
          <th scope="col" width="10%">Status</th>' ?>
          <?php if ($role == 'Admin') { ?>
            <?php
            echo '<th scope="col" width="7%">Password</th>' ?>
          <?php    } else {
          } ?>
          <?php
          echo '<th scope="col" width="20%">Subject</th>
          <th scope="col">Medium</th>
          <th scope="col">Badge</th>
        </tr>
        </thead>' ?>
          <?php if (sizeof($resultArr) > 0) { ?>
            <?php
            echo '<tbody>';
            foreach ($resultArr as $array) {
              echo '<tr>
            <td><img src="' . $array['photourl'] . '" width=50px/></td>
            <td>Name - <b>' . $array['studentname'] . '</b><br>Student ID - <b>' . $array['student_id'] . '</b>
            <br><b>' . $array['gender'] . '&nbsp;(' . $array['age'] . ')</b><br><br>DOA - ' . $array['doa'] . '</td>
            <td>' . $array['class'] . '/' . $array['category'] . ' </td>
            <td>' . $array['contact'] . '<br>' . $array['emailaddress'] . '</td>
            <td>' . $array['profilestatus'] ?>

              <?php if ($role == 'Admin') { ?>

                <?php echo '<br><br>' . $array['remarks'] . '</td>            
            <td>' . $array['colors'] ?>
              <?php    } else {
              } ?>
            <?php
              echo '</td> 
            <td>' . $array['nameofthesubjects'] . '<br><br>Attendance -&nbsp;' . $array['attd'] . '<br>' . $array['allocationdate'] . '</td>
            <td>' . $array['medium'] . '/'. $array['nameoftheboard'] .'</td>
            <td>' . $array['badge'] . '</td>
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
              <td colspan="5">No record found for <?php echo $module ?>, <?php echo $id ?> and <?php echo $category ?></td>
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