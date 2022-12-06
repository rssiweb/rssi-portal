<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}


if ($role == 'Admin') {

    // @$statuse = $_POST['get_statuse'];
    // @$appid = $_POST['get_appid'];
    @$statuse = $_GET['get_statuse'];
    @$statusee = $_GET['get_statusee'];
    @$appid = $_GET['get_appid'];

    if ($statuse == 'Associate' && $statusee != null && $statusee != 'ALL' && $appid == null) {
        $result = pg_query($con, "select * from asset inner join rssimyaccount_members ON asset.userid=rssimyaccount_members.associatenumber WHERE usertype='Associate' AND status='$statusee' order by timestamp desc");
    } else if ($statuse == 'Associate' && ($statusee == null || $statusee == 'ALL') && $appid == null) {
        $result = pg_query($con, "select * from asset inner join rssimyaccount_members ON asset.userid=rssimyaccount_members.associatenumber WHERE usertype='Associate' order by timestamp desc");
    } else if ($statuse == 'Associate' && ($statusee == null || $statusee == 'ALL') && $appid != null) {
        $result = pg_query($con, "select * from asset inner join rssimyaccount_members ON asset.userid=rssimyaccount_members.associatenumber WHERE usertype='Associate' AND userid='$appid' order by timestamp desc");
    } else if ($statuse == 'Associate' && $statusee != null && $statusee != 'ALL' && $appid != null) {
        $result = pg_query($con, "select * from asset inner join rssimyaccount_members ON asset.userid=rssimyaccount_members.associatenumber WHERE usertype='Associate' AND status='$statusee' AND userid='$appid' order by timestamp desc");
    } else if ($statuse == 'Student' && $statusee != null && $statusee != 'ALL' && $appid == null) {
        $result = pg_query($con, "select * from asset inner join rssimyprofile_student ON asset.userid=rssimyprofile_student.student_id WHERE usertype='Student' AND asset.status='$statusee' order by timestamp desc");
    } else if ($statuse == 'Student' && ($statusee == null || $statusee == 'ALL') && $appid == null) {
        $result = pg_query($con, "select * from asset inner join rssimyprofile_student ON asset.userid=rssimyprofile_student.student_id WHERE usertype='Student' order by timestamp desc");
    } else if ($statuse == 'Student' && ($statusee == null || $statusee == 'ALL') && $appid != null) {
        $result = pg_query($con, "select * from asset inner join rssimyprofile_student ON asset.userid=rssimyprofile_student.student_id WHERE usertype='Student' AND userid='$appid' order by timestamp desc");
    } else if ($statuse == 'Student' && $statusee != null && $statusee != 'ALL' && $appid != null) {
        $result = pg_query($con, "select * from asset inner join rssimyprofile_student ON asset.userid=rssimyprofile_student.student_id WHERE usertype='Student' AND asset.status='$statusee' AND userid='$appid' order by timestamp desc");
    } else {
        $result = pg_query($con, "select * from asset WHERE usertype=''");
    }
}
if ($role != 'Admin') {
    $result = pg_query($con, "select * from asset inner join rssimyaccount_members ON asset.userid=rssimyaccount_members.associatenumber where associatenumber='$user_check'");
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
    <title>Asset Movement</title>
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
            policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
        });
    </script>

</head>

<body>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    
                        <div class="col" style="display: inline-block; width:100%; text-align:right">
                            Home / <span class="noticea"><a href="document.php">My Document</a></span> / <span class="noticea"><a href="gps.php">GPS</a></span> / Asset Movement<br><br>
                        </div>
                        <?php if ($role == 'Admin') { ?>
                        <form id="myform" action="" method="GET" onsubmit="process()">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">
                                    <select name="get_statuse" required class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type">
                                        <?php if ($statuse == null) { ?>
                                            <option value="" disabled selected hidden>Select Engagement</option>
                                        <?php
                                        } else { ?>
                                            <option hidden selected><?php echo $statuse ?></option>
                                        <?php }
                                        ?>
                                        <option>Associate</option>
                                        <option>Student</option>
                                    </select>


                                    <select name="get_statusee" class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type">
                                        <?php if ($statusee == null) { ?>
                                            <option value="" disabled selected hidden>Select Status</option>
                                        <?php
                                        } else { ?>
                                            <option hidden selected><?php echo $statusee ?></option>
                                        <?php }
                                        ?>
                                        <option>Initiated</option>
                                        <option>Despatched</option>
                                        <option>Delivered</option>
                                        <option>Return initiated</option>
                                        <option>Closed</option>
                                        <option>ALL</option>
                                    </select>


                                    <input name="get_appid" class="form-control" style="width:max-content; display:inline-block" placeholder="User ID" value="<?php echo $appid ?>">
                                </div>
                            </div>
                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>&nbsp;<a href="https://docs.google.com/forms/d/e/1FAIpQLScLENQKgw2bEDuhZFRLDuxcmwuXIh-6H7zXm8NbCSv6x63fNw/viewform" target="_blank" class="btn btn-danger btn-sm" role="button"><i class="fa-solid fa-plus"></i>&nbsp;Ticket</a>
                            </div>
                        </form>
                    <?php } ?>
                    <div class="col" style="display: inline-block; width:99%; text-align:right">
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                    </div>

                    <?php echo '
                       <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Reference number</th>
                                <th scope="col">First party details</th>
                                <th scope="col">Asset/Agreement details</th>
                                <th scope="col">Issued on</th>
                                <th scope="col">Agreement</th>
                                <th scope="col">Returned on</th>
                                <th scope="col">Received on</th>
                                <th scope="col">Status</th>
                                <th scope="col">HR remarks</th>
                            </tr>
                        </thead>' ?>
                    <?php if (sizeof($resultArr) > 0) { ?>
                        <?php
                        echo '<tbody>';
                        foreach ($resultArr as $array) {
                            echo '<tr>
                                <td>' . $array['submissionid'] . '</td>
                                <td>' . $array['associatenumber'] . '<br>' . $array['fullname'] . '</td>
                                <td>' . $array['assetdetails'] . $array['agreementname'] ?>

                            <!-- <?php if ($array['category'] == 'Asset') { ?>
                                <?php echo '<p class="label label-danger">asset</p>' ?>
                            <?php } else {
                                    } ?> -->

                        <?php echo '</td>
                                <td>' . $array['issuedon'] . '</td>
                                <td><span class="noticea"><a href="' . $array['agreement'] . '" target="_blank"><i class="far fa-file-pdf" style="font-size:17px;color: #767676;"></i></a></span>
                                
                                </td>
                                <td>' . $array['returnedon'] . '</td>
                                <td>' . $array['receivedon'] . '</td>
                                <td>' . $array['status'] . '</td>
                                <td>' . $array['comment'] . '</td>
                            </tr>';
                        } ?>
                    <?php
                    } else if (@$statuse == null && @$appid == null) {
                    ?>
                        <tr>
                            <td colspan="5">Please select Filter value.</td>
                        </tr>
                    <?php
                    } else {
                    ?>
                        <tr>
                            <td colspan="5">No record was found for the selected filter value.</td>
                        </tr>
                    <?php }

                    echo '</tbody>
                                    </table>';
                    ?>
        </section>
        </div>

        <div class="clearfix"></div>
    </section>
    </section>
    <script>
        function process() {
            var form = document.getElementById('myform');
            var elements = form.elements;
            var values = [];

            for (var i = 0; i < elements.length; i++)
                values.push(encodeURIComponent(elements[i].name) + '=' + encodeURIComponent(elements[i].value));

            form.action += '?' + values.join('&');
        }
    </script>
</body>

</html>
