<?php
session_start();
// Storing Session
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
?>

<?php
include("member_data.php");
include("database.php");
$result = pg_query($con, "select * from leavedb_leavedb WHERE associatenumber='$user_check' order by timeformat desc"); //select query for viewing users.  

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

</head>

<body>
    <?php $leave_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col" style="display: inline-block; width:99%; text-align:right">
                        FY 2021-2022
                        <!--<br>Opening balance is the balance carried forward from previous credit cycle and refers to the leave till the allocation end date.-->
                    </div>
                </div>

                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Opening Leave Balance (A)</th>
                                <th scope="col">Leave Approved (B)</th>
                                <th scope="col">Leave Adjusted (C)</th>
                                <th scope="col">Leave Balance (A-B)+C</th>
                                <th scope="col">Apply Leave</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="line-height: 2;">Sick Leave - <?php echo (int)$sl ?><br>Casual Leave - <?php echo (int)$cl ?></td>
                                <td style="line-height: 2;"><?php echo $sltaken + $cltaken +$othtaken +$adjustedleave ?>
                                </td>
                                <td style="line-height: 2;"><?php echo $adjustedleave ?></td>
                                <td style="line-height: 2;">Sick Leave - <?php echo $slbal ?>
                                <br>Casual Leave - <?php echo $clbal ?> 
                                <!--<br>Other Leave - <?php echo $elbal ?></td>-->
                                <td style="line-height: 2;">
                                    <?php if (@$filterstatus == 'Active') {
                                    ?>
                                        <span class="noticet"><a href="<?php echo $leaveapply ?>" target="_blank">Leave Request Form</a></span>
                                    <?php
                                    } else {
                                    }
                                    ?>

                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <hr>

                    Record count:&nbsp;<?php echo sizeof($resultArr) ?>

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
                                <th scope="col">Comment</th>
                            </tr>
                        </thead>
                        <tbody>';
                    foreach ($resultArr as $array) {
                        echo '
                            <tr>
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
                    }
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