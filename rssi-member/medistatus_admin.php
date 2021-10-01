<?php
session_start();
// Storing Session
$user_check = $_SESSION['aid'];

if(!$_SESSION['aid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;  
  }
  else if ($_SESSION['role']!='Admin') {

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
@$id = $_POST['get_aid'];
@$status = $_POST['get_id'];

if ($id == null && $status == 'ALL') {
  $result = pg_query($con, "SELECT * FROM medimate_medimate order by timestamp desc");
} else if ($id == null && $status != 'ALL') {
  $result = pg_query($con, "SELECT * FROM medimate_medimate WHERE financialyear='$status' order by timestamp desc");
} else if ($id > 0 && $status != 'ALL') {
  $result = pg_query($con, "SELECT * FROM medimate_medimate WHERE id='$id' AND financialyear='$status' order by timestamp desc");
} else if ($id > 0 && $status == 'ALL') {
  $result = pg_query($con, "SELECT * FROM medimate_medimate WHERE id='$id' order by timestamp desc");
} else {
  $result = pg_query($con, "SELECT * FROM medimate_medimate order by timestamp desc");
}

if (!$result) {
  echo "An error occurred.\n";
  exit;
}

$resultArr = pg_fetch_all($result);
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta name="description" content="">
  <meta name="author" content="">
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=Edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>Medistatus Admin</title>
  <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
  <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
  <!-- Main css -->
  <style>
    <?php include '../css/style.css'; ?>
  </style>
  <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
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
    @media (min-width:767px) {
      .left {
        margin-left: 2%;
      }
    }
  </style>

</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

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
            Home / Medimate Status
          </div>
        </div>
        <section class="box" style="padding: 2%;">
          <form action="" method="POST">
            <div class="form-group" style="display: inline-block;">
              <div class="col2" style="display: inline-block;">
                <input name="get_aid" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate number" value="<?php echo $id ?>">
                <select name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Select policy year" required>
                  <?php if ($status == null) { ?>
                    <option value="" hidden selected>Select policy year</option>
                  <?php
                  } else { ?>
                    <option hidden selected><?php echo $status ?></option>
                  <?php }
                  ?>
                  <option>FY 2021-2022</option>
                  <option>FY 2020-2021</option>
                  <option>ALL</option>
                </select>
              </div>
            </div>
            <div class="col2 left" style="display: inline-block;">
              <button type="submit" name="search_by_id" class="btn btn-success" style="outline: none;">
                <span class="glyphicon glyphicon-search"></span>&nbsp;Search</button>
            </div>
          </form>
          <?php echo '
                    <table class="table">
                        <thead>
                            <tr>
                            <th scope="col">Claim ID (Click to see the document)</th>
                            <th scope="col">Registered On</th>    
                            <th scope="col">ID/F Name</th>    
                                <th scope="col">Beneficiary</th>
                                <th scope="col">Account Number</th>
                                <th scope="col">Claimed(&#8377;)</th>
                                <th scope="col">Approved(&#8377;)</th>
                                <th scope="col">Claim Status</th>
                                <th scope="col">Closed on</th>
                                <th scope="col">Remarks</th>
                            </tr>
                        </thead>' ?>
          <?php if ($resultArr != null) {
            echo '<tbody>';
            foreach ($resultArr as $array) {
              echo '
                                <tr>' ?>

                                <?php if ($array['uploadeddocuments'] != null) { ?>
                                    <?php
                                    echo '<td><span class="noticet"><a href="' . $array['uploadeddocuments'] . '" target="_blank">' . $array['claimid'] . '</a></span></td>'
                                    ?>
                                    <?php    } else { ?><?php
                                                        echo '<td>' . $array['claimid'] . '</td>' ?>
                                <?php } ?>
                            <?php
                                echo '

                                <td>' . substr($array['timestamp'], 0, 10) . '</td>
                                <td>' . $array['id'] . '<br>' . strtok($array['name'],' ') . '</td>   
                                    
                                    <td>' . $array['selectbeneficiary'] . '</td>
                                    <td>' . substr($array['accountnumber'], 1, -1) . '<br>'. $array['bankname'] .'/'.$array['ifsccode'].'</td>
                                    <td>' . $array['totalbillamount'] . '</td>
                                    <td>' . $array['approved'] . '</td>
                                    <td>' . $array['currentclaimstatus'] . '</td>
                                    <td>' . $array['closedon'] . '</td>
                                    <td>' . $array['mediremarks'] . '</td>
                                    </tr>';
            }
          } else if ($status == null) {
            echo '<tr>
                            <td colspan="5">Please select policy year.</td>
                        </tr>';
          } else {
            echo '<tr>
                        <td colspan="5">No record found for' ?>&nbsp;<?php echo $status ?>
        <?php echo '</td>
                    </tr>';
          }
          echo '</tbody>
                     </table>';
        ?>
      </div>
      <div class="col-md-12" style="text-align: right;">
        <span class="noticet" style="line-height: 2;"><a href="medimate.php">Back to Medimate</a></span>
      </div>
      </div>
    </section>
    </div>
  </section>
  </section>


  <!-- Back top -->
  <script>
    $(document).ready(function() {
      $(window).scroll(function() {
        if ($(this).scrollTop() > 50) {
          $('#back-to-top').fadeIn();
        } else {
          $('#back-to-top').fadeOut();
        }
      });
      // scroll body to 0px on click
      $('#back-to-top').click(function() {
        $('body,html').animate({
          scrollTop: 0
        }, 400);
        return false;
      });
    });
  </script>
  <a id="back-to-top" href="#" class="go-top" role="button"><i class="fa fa-angle-up"></i></a>
</body>

</html>