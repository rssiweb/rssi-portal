<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("sid")) {
    header("Location: index.php");
}
$user_check = $_SESSION['sid'];

if (!$_SESSION['sid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;  
}
?>

<?php
include("student_data.php");
include("database.php");
@$id = $_POST['get_id'];
@$status = $_POST['get_status'];

if (($id==null && $status==null) || (($status > 0 && $status != 'ALL')&&($id > 0 && $id != 'ALL'))) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE associatenumber='$user_check' AND status='$status' AND lyear='$id'");
} else if (($id == 'ALL' && $status == null) || ($id == null && $status == 'ALL')) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE associatenumber='$user_check'");
} else if (($id > 0 && $id != 'ALL')&&($status==null)) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE associatenumber='$user_check' AND lyear='$id'");
} else if (($id > 0 && $id != 'ALL')&&($status=='ALL')) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE associatenumber='$user_check' AND lyear='$id'");
} else if (($status > 0 && $status != 'ALL')&&($id==null)) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE associatenumber='$user_check' AND status='$status'");
} else if (($status > 0 && $status != 'ALL')&&($id=='ALL')) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE associatenumber='$user_check' AND status='$status'");
} else {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE associatenumber='$user_check'");
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
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <title>Leave</title>
    <!-- Main css -->
    <style><?php include '../css/style.css'; ?></style>
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

</head>

<body>
    <?php $leave_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                
                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Leaves Approved</th>
                                <th scope="col">Apply Leave</th>
                                <th scope="col">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="line-height: 2;">Casual Leave - <?php echo $cltaken ?> <br>Sick Leave - <?php echo $sltaken ?> <br>Other Leave - <?php echo $othtaken ?></td>
                                <td><span class="noticet"><a href="<?php echo $leaveapply ?>" target="_blank">Leave Request Form</a></span></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                    <hr>
                    <b><span class="underline">Leave Details</span></b><br><br>
                    <form action="" method="POST">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <select name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type">
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
                                    <option>ALL</option>
                                </select>&nbsp;
                                <select name="get_status" class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type">
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
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" class="btn btn-primary" style="outline: none;">
                                <span class="glyphicon glyphicon-search"></span>&nbsp;Search</button>
                        </div>
                    </form>
                    <div class="col" style="display: inline-block; width:99%; text-align:right">
                    Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                    </div>

                    <?php echo '
                       <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Leave ID</th>
                                <th scope="col">Applied on</th>
                                <th scope="col">From</th>
                                <th scope="col">To</th>
                                <th scope="col">Day(s) count</th>
                                <th scope="col">Type of Leave</th>
                                <th scope="col">Certificate(s)</th>
                                <th scope="col">Status</th>
                                <th scope="col">HR remarks</th>
                            </tr>
                        </thead>' ?>
                        <?php if (sizeof($resultArr) > 0) { ?>
                            <?php
                            echo '<tbody>';
                            foreach ($resultArr as $array) {
                              echo '<tr>
                                <td>' . $array['leaveid'] . '</td>
                                <td>' . $array['timestamp'] . '</td>
                                <td>' . $array['from'] . '</td>
                                <td>' . $array['to'] . '</td>
                                <td>' . $array['day'] . '</td>
                                <td>' . $array['typeofleave'] . '</td>
                                <td>' . $array['doc'] . '</td>
                                <td>' . $array['status'] . '</td>
                                <td>' . $array['comment'] . '</td>
                            </tr>';
                        } ?>
                      <?php
                      } else if ($id == null && $status==null) {
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