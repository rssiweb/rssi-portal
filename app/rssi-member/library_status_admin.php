<?php
session_start();
// Storing Session
include("../util/login_util.php");

if(! isLoggedIn("aid")){
    header("Location: index.php");
}
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
@$id = $_POST['get_bid'];
@$status = $_POST['get_status'];

if ($id == null && $status == 'ALL') {
    $result = pg_query($con, "SELECT * FROM bookdata_book");
} else if ($id == null && $status != 'ALL') {
    $result = pg_query($con, "SELECT * FROM bookdata_book WHERE bookstatus='$status'");
} else if ($id > 0 && $status != 'ALL') {
    $result = pg_query($con, "SELECT * FROM bookdata_book WHERE yourid='$id' AND bookstatus='$status'");
} else if ($id > 0 && $status == 'ALL') {
    $result = pg_query($con, "SELECT * FROM bookdata_book WHERE yourid='$id'");
} else {
    $result = pg_query($con, "SELECT * FROM bookdata_book");
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
    <title>Library Status</title>
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
                        Home / Library Status
                    </div>
                </div>
                <section class="box" style="padding: 2%;">

                    <form action="" method="POST">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <input name="get_bid" class="form-control" style="width:max-content; display:inline-block" placeholder="Borrowers ID" value="<?php echo $id ?>">
                                <select name="get_status" class="form-control" style="width:max-content;display:inline-block" required>
                                    <?php if ($status == null) { ?>
                                        <option value="" hidden selected>Select book status</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $status ?></option>
                                    <?php }
                                    ?>
                                    <option>Issued</option>
                                    <option>Due</option>
                                    <option>Returned</option>
                                    <option>Canceled</option>
                                    <option>Duplicate-Canceled</option>
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
                                <th scope="col">Borrowers ID/F Name</th>
                                <th scope="col">Order ID</th>
                                <th scope="col">Order Date</th>
                                <th scope="col">Book ID</th>
                                <th scope="col">Book Name</th>
                                <th scope="col">Original Price</th>
                                <th scope="col">Issued On</th>
                                <th scope="col">Due Date</th>
                                <th scope="col">Book status</th>
                                <th scope="col">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>';
                    foreach ($resultArr as $array) {
                        echo '
                            <tr>
                                <td style="line-height: 1.7;">' . $array['yourid'] . '/'. strtok($array['yourname'],' ') .'</td>
                                <td style="line-height: 1.7;">' . $array['orderid'] . '</td>
                                <td style="line-height: 1.7;">' . $array['orderdate'] . '</td>
                                <td style="line-height: 1.7;">' . $array['bookregno'] . '</td>
                                <td style="line-height: 1.7;">' . $array['bookname'] . '</td>
                                <td style="line-height: 1.7;">' . $array['originalprice'] . '</td>
                                <td style="line-height: 1.7;">' . $array['issuedon'] . '</td>
                                <td style="line-height: 1.7;">' . $array['duedate'] . '</td>
                                <td style="line-height: 1.7;">' . $array['bookstatus'] . '</td>
                                <td style="line-height: 1.7;">' . $array['remarks'] . '</td>
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