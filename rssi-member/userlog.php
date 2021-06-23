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
$result = pg_query($con, "SELECT * FROM userlog_member");
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
    <title>User log</title>
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
                <div class=col style="text-align: right;">Last synced: <?php echo $lastupdatedon ?>

                    <br><span class="noticet" style="line-height: 2;"><a href="#" onClick="javascript:history.go(-1)">Back to previous page</a></span>
                </div>
                <section class="box" style="padding: 2%;">
                    <?php echo '
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">SL.No</th>
                                <th scope="col">User name</th>
                                <th scope="col">Password</th>
                                <th scope="col">IP Address</th>
                                <th scope="col">Login time</th>
                            </tr>
                        </thead>
                        <tbody>';
                    foreach ($resultArr as $array) {
                        echo '
                            <tr>
                                <td style="line-height: 1.7;">' . $array['id'] . '</td>
                                <td style="line-height: 1.7;">' . $array['username'] . '</td>
                                <td style="line-height: 1.7;">' . $array['password'] . '</td>
                                <td style="line-height: 1.7;">' . $array['ipaddress'] . '</td>
                                <td style="line-height: 1.7;">' . $array['logintime'].'</td>
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