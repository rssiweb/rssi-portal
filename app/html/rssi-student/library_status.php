<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("sid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

@$status = $_POST['get_status'];

if ($status == null) {
    $result = pg_query($con, "SELECT * FROM bookdata_book WHERE yourid='$user_check' order by timestamp desc");
} else if ($status == 'ALL') {
    $result = pg_query($con, "SELECT * FROM bookdata_book WHERE yourid='$user_check' order by timestamp desc");
} else if ($status > 0 && $status != 'ALL') {
    $result = pg_query($con, "SELECT * FROM bookdata_book WHERE yourid='$user_check' AND bookstatus='$status' order by timestamp desc");
} else {
    $result = pg_query($con, "SELECT * FROM bookdata_book WHERE yourid='$user_check' order by timestamp desc");
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
<link rel="stylesheet" href="/css/style.css" />
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
            policyLink: 'https://www.rssi.in/disclaimer'
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
                <div class=col style="text-align: right;">
                    <span class="noticet" style="line-height: 2;"><a href="#" onClick="javascript:history.go(-1)">Back to previous page</a></span>
                </div>
                
                    <form action="" method="POST">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
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
                                    <option>ALL</option>
                                </select>
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                                <i class="bi bi-search"></i>&nbsp;Search</button>
                        </div>
                    </form>
                    <?php echo '
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Student ID</th>
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
                                <td style="line-height: 1.7;">' . $array['yourid'] . '</td>
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
