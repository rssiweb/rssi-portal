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
include("database.php");
@$id = $_POST['get_id'];
@$status = $_POST['get_status'];
@$statuse = $_POST['get_statuse'];
@$appid = $_POST['get_appid'];

if ($id == null && $status == null && $statuse == null && $appid == null) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE leaveid=''");
} else if ($id != null && $status == 'ALL' && $statuse != null && $appid == null) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE lyear='$id'AND organizationalengagement='$statuse'");
} else if ($id != null && $status == 'ALL' && $statuse != null && $appid != null) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE lyear='$id' AND organizationalengagement='$statuse'AND associatenumber='$appid'");
} else if ($id != null && $status != 'ALL' && $status != null && $statuse != null && $appid == null) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE lyear='$id' AND status='$status' AND organizationalengagement='$statuse'");
} else {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE lyear='$id' AND status='$status' AND organizationalengagement='$statuse'AND associatenumber='$appid'");
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
    <title>My Leave</title>
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

</head>

<body>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col" style="display: inline-block; width:100%; text-align:right">
                        Home / Leave Tracker
                    </div>
                    <form action="" method="POST">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <select name="get_id" required class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type">
                                    <?php if ($id == null) { ?>
                                        <option value="" disabled selected hidden>Select Year</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $id ?></option>
                                    <?php }
                                    ?>
                                    <option>2022</option>
                                    <option>2021</option>
                                    <option>2020</option>
                                </select>&nbsp;
                                <select name="get_status" required class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type">
                                    <?php if ($status == null) { ?>
                                        <option value="" disabled selected hidden>Select Status</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $status ?></option>
                                    <?php }
                                    ?>
                                    <option>Approved</option>
                                    <option>Rejected</option>
                                    <option>ALL</option>
                                </select>
                                <select name="get_statuse" required class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type">
                                    <?php if ($status == null) { ?>
                                        <option value="" disabled selected hidden>Select Engagement</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $statuse ?></option>
                                    <?php }
                                    ?>
                                    <option>Volunteer</option>
                                    <option>Employee</option>
                                    <option>Intern</option>
                                    <option>Student</option>
                                </select>
                                <input name="get_appid" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo $appid ?>">
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" class="btn btn-success" style="outline: none;">
                                <span class="glyphicon glyphicon-search"></span>&nbsp;Search</button>
                        </div>
                    </form>
                    <div class="col" style="display: inline-block; width:99%; text-align:right">
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                    </div>

                    <?php echo '
                       <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Leave ID (Click to see the document)</th>
                                <th scope="col">Applicant ID/F name</th>
                                <th scope="col">Applied on</th>
                                <th scope="col">From</th>
                                <th scope="col">To</th>
                                <th scope="col">Day(s) count</th>
                                <th scope="col">Type of Leave/Leave Category</th>
                                <th scope="col">Status</th>
                                <th scope="col">HR remarks</th>
                            </tr>
                        </thead>' ?>
                    <?php if (sizeof($resultArr) > 0) { ?>
                        <?php
                        echo '<tbody style="font-size: 13px;">';
                        foreach ($resultArr as $array) {
                            echo '<tr>'
                        ?>

                            <?php if ($array['doc'] != null) { ?>
                                <?php
                                echo '<td><span class="noticea"><a href="' . $array['doc'] . '" target="_blank">' . $array['leaveid'] . '</a></span></td>'
                                ?>
                                <?php    } else { ?><?php
                                                    echo '<td>' . $array['leaveid'] . '</td>' ?>
                            <?php } ?>
                        <?php
                            echo '  <td>' . $array['associatenumber'] . '/' . strtok($array['applicantname'], ' ') . '</td>
                                <td>' . $array['timestamp'] . '</td>
                                <td>' . $array['from'] . '</td>
                                <td>' . $array['to'] . '</td>
                                <td>' . $array['day'] . '</td>
                                <td>' . $array['typeofleave'] . '<br>
                                ' . $array['sreason'] . $array['creason'] . '</td>
                                <td>' . $array['status'] . '</td>
                                <td>' . $array['comment'] . '</td>
                            </tr>';
                        } ?>
                    <?php
                    } else if ($id == null && $status == null) {
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
        <!--**************clearfix**************

           <div class="col-md-12">
                <section class="box">cccccccccccee33</section>
            </div>-->

    </section>
    </section>
</body>

</html>