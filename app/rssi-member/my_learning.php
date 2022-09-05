<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

@$cid = $_GET['get_cid'];

if ($role == 'Admin') {
    @$aid = $_GET['get_aid'];


    if ($aid != null && $cid == null) {
        $result = pg_query($con, "SELECT * FROM wbt_status 
        left join wbt ON wbt.courseid=wbt_status.courseid WHERE wassociatenumber='$aid' order by timestamp desc");
    } else if ($aid == null && $cid != null) {
        $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wbt_status.courseid='$cid' order by timestamp desc");
    } else if ($aid != null && $cid != null) {
        $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wbt_status.courseid='$cid' AND wassociatenumber='$aid' order by timestamp desc");
    } else {
        $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wassociatenumber='$user_check' order by timestamp desc");
    }
}
if ($role != 'Admin' && $cid != null) {
    $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wassociatenumber='$user_check' AND wbt_status.courseid='$cid' order by timestamp desc");
} else if ($role != 'Admin' && $cid == null) {
    $result = pg_query($con, "SELECT * FROM wbt_status left join wbt ON wbt.courseid=wbt_status.courseid WHERE wassociatenumber='$user_check' order by timestamp desc");
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
    <title>iExplore-My Learning</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
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
                        Home / <span class="noticea"><a href="iexplore.php" target="_self">WBT</a></span> / My Learning
                    </div>
                </div>
                <section class="box" style="padding: 2%;">
                    <form action="" method="GET">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <?php if ($role == 'Admin') { ?>
                                    <input name="get_aid" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate number" value="<?php echo $aid ?>">
                                <?php } ?>
                                <input name="get_cid" class="form-control" style="width:max-content; display:inline-block" placeholder="Course id" value="<?php echo $cid ?>">
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                        </div>
                    </form>
                    <?php echo '
                    <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                            <th scope="col">Associate number</th>
                            <th scope="col">Completed on</th>
                            <th scope="col">Course id</th>    
                            <th scope="col">Course name</th>    
                            <th scope="col">Score</th>
                            <th scope="col">Status</th>
                            <th scope="col">Valid upto</th>
                            </tr>
                        </thead>' ?>
                    <?php if ($resultArr != null) {
                        echo '<tbody>';
                        foreach ($resultArr as $array) {
                            echo '
                                <tr><td>' . substr($array['wassociatenumber'], 0, 10) . '</td>
                                    <td>' . @date("d/m/Y g:i a", strtotime($array['timestamp'])) . '</td>
                                    <td>' . $array['courseid'] . '</td>
                                    <td>' . $array['coursename'] . '</td>
                                    <td>' . round((float)$array['f_score'] * 100) . '%' . '</td><td>' ?>

                            <?php
                            $validity = $array['validity'];
                            $date = date_create($array['timestamp']);
                            date_add($date, date_interval_create_from_date_string("$validity years"));
                            date_format($date, "d/m/Y g:i a");

                            if (($array['passingmarks'] <= round((float)$array['f_score'] * 100)) && date_format($date, "d/m/Y g:i a") >= date('d/m/Y g:i a', time())) { ?>

                                <?php echo '<p class="label label-success">Complete</p>&nbsp;<p class="label label-info">Active</p>' ?>

                            <?php } else if (($array['passingmarks'] <= round((float)$array['f_score'] * 100)) && date_format($date, "d/m/Y g:i a") <= date('d/m/Y g:i a', time())) { ?>

                                <?php echo '<p class="label label-default">Expired</p>' ?>

                            <?php } else { ?>

                                <?php echo '<p class="label label-danger">Incomplete</p>' ?>
                            <?php } ?>

                            <?php echo
                            '</td><td>' ?>
                            <?php if ($array['passingmarks'] <= round((float)$array['f_score'] * 100)) { ?>
                                <?php
                                // $validity = $array['validity'];
                                // $date = date_create($array['timestamp']);
                                // date_add($date, date_interval_create_from_date_string("$validity years"));
                                echo date_format($date, "d/m/Y g:i a");
                                ?>
                            <?php } ?>
                        <?php echo '</td></tr>';
                        }
                    } else if ($role == 'Admin' && $cid == null && $aid == null) { ?>
                        <?php echo '<tr><td colspan="5">Please select Filter value.</td> </tr>'; ?>
                    <?php } else if ($role != 'Admin' && $cid == null) { ?>
                        <?php echo '<tr><td colspan="5">Please select Filter value.</td> </tr>'; ?>
                        <?php } else {
                        echo '<tr>
                        <td colspan="5">No record found for' ?>&nbsp;

                        <?php if ($role == 'Admin') { ?>
                            <?php echo $cid ?>
                        <?php } ?>
                        <?php echo $cid ?>
                    <?php echo '</td>
                    </tr>';
                    }
                    echo '</tbody>
                     </table>';
                    ?>
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