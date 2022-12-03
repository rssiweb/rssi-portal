<?php
session_start();
// Storing Session
include("../util/login_util.php");

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


@$id = $_POST['get_id'];
@$status = $_POST['get_status'];
date_default_timezone_set('Asia/Kolkata');

if (($id == null && $status == null) || (($status > 0 && $status != 'ALL') && ($id > 0 && $id != 'ALL'))) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE applicantid='$user_check' AND status='$status' AND lyear='$id' order by timestamp desc");
} else if (($id == 'ALL' && $status == null) || ($id == null && $status == 'ALL')) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE applicantid='$user_check' order by timestamp desc");
} else if (($id > 0 && $id != 'ALL') && ($status == null)) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE applicantid='$user_check' AND lyear='$id' order by timestamp desc");
} else if (($id > 0 && $id != 'ALL') && ($status == 'ALL')) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE associatenumber='$user_check' AND lyear='$id' order by timestamp desc");
} else if (($status > 0 && $status != 'ALL') && ($id == null)) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE applicantid='$user_check' AND status='$status' order by timestamp desc");
} else if (($status > 0 && $status != 'ALL') && ($id == 'ALL')) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE applicantid='$user_check' AND status='$status' order by timestamp desc");
} else {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE applicantid='$user_check' order by timestamp desc");
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
    <?php $leave_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col" style="display: inline-block; width:99%; text-align:right">
                        Academic year: 2022-2023
                        <!--<br>Opening balance is the balance carried forward from previous credit cycle and refers to the leave till the allocation end date.-->
                    </div>
                    <?php
                    if ((@$clbal == 0 || @$clbal < 0) && ($slbal == 0 || $slbal < 0) && $filterstatus == 'Active') {
                    ?>
                        <div class="alert alert-danger" role="alert" style="text-align: -webkit-center;"><span class="blink_me"><i class="fas fa-exclamation-triangle" style="color: #A9444C;"></i></span>&nbsp;
                            <b><span id="demo" style="display: inline-block;"></span></b>&nbsp; Inadequate SL and CL balance. You are not eligible to take leave. Please take a makeup class to enable the apply leave option.
                        </div>
                    <?php
                    } else if ((@$clbal == 0 || @$clbal < 0) && $filterstatus == 'Active') {
                    ?>
                        <div class="alert alert-warning" role="alert" style="text-align: -webkit-center;"><span class="blink_me"><i class="fas fa-exclamation-triangle" style="color: #A9444C;"></i></span>&nbsp;
                            <b><span id="demo" style="display: inline-block;"></span></b>&nbsp; Insufficient CL balance. You are not eligible for casual leave. Please take makeup class to increase CL balance.
                        </div>
                    <?php
                    } else if ((@$slbal == 0 || @$slbal < 0) && $filterstatus == 'Active') {
                    ?>
                        <div class="alert alert-warning" role="alert" style="text-align: -webkit-center;"><span class="blink_me"><i class="fas fa-exclamation-triangle" style="color: #A9444C;"></i></span>&nbsp;
                            <b><span id="demo" style="display: inline-block;"></span></b>&nbsp; Insufficient SL balance. You are not eligible for sick leave. Please take makeup class to increase SL balance.
                        </div>
                    <?php
                    } else {
                    }
                    ?>
                </div>

                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Leave Balance</th>
                                <th scope="col">Apply Leave</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <!-- <td style="line-height: 2;">Sick Leave - <?php echo (int)$sl ?><br>Casual Leave - <?php echo (int)$cl ?></td> -->
                                <td style="line-height: 2;">Sick Leave - <?php echo $slbal ?>
                                    <br>Casual Leave - <?php echo $clbal ?>
                                    <!--<br>Other Leave - <?php echo $elbal ?></td>-->
                                <td style="line-height: 2;">
                                    <?php if ((@$slbal > 0 || @$clbal > 0) && @$filterstatus == 'Active') {
                                    ?>
                                        <span class="noticea"><a href="https://docs.google.com/forms/d/e/1FAIpQLScAuTVl6IirArMKi5yoj69z7NEYLKqvvNwn8SYo9UGa6RWT0A/viewform?entry.1592136078=<?php echo $associatenumber ?>&entry.593057865=<?php echo $fullname ?>&entry.1085056032=<?php echo $email ?>&entry.1932332750=<?php echo strtok($position,  '-') ?>" target="_blank">Leave Request Form</a></span>
                                    <?php
                                    } else { ?>
                                        <span class="noticea"><a href="//" onclick="return false;">Leave Request Form</a></span>
                                    <?php }
                                    ?>

                                </td>
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
                                    <option>2022-2023</option>
                                    <option>2021-2022</option>
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
                            <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                        </div>
                    </form>
                    <div class="col" style="display: inline-block; width:99%; text-align:right">
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                    </div>

                    <?php echo '
                       <table class="table">
                        <thead style="font-size: 12px;">
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
                                <td>' . @date("d/m/Y g:i a", strtotime($array['timestamp'])) . '</td>
                                <td>' . @date("d/m/Y", strtotime($array['from'])) . '</td>
                                <td>' . @date("d/m/Y", strtotime($array['to'])) . '</td>
                                <td>' . $array['day'] . '</td>
                                <td>' . $array['typeofleave'] . '</td>
                                <td>' . $array['doc'] . '</td>
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
        </section>
    </section>
</body>

</html>