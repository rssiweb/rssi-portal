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
include("database.php");
include("member_data.php");
$view_users_query = "select * from allocationdb_allocationdb WHERE associatenumber='$user_check'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
    $hallocationdate = $row[0];
    $associatenumber = $row[1];
    $hfullname = $row[2];
    $hstatus = $row[3];
    $hmaxclass = $row[4];
    $hclasstaken = $row[5];
    $__hevo_id = $row[6];
    $__hevo__ingested_at = $row[7];
    $__hevo__marked_deleted = $row[8]
?>
<?php } ?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>My Allocation</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
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
    <?php $allocation_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">  
                <section class="box" style="padding: 2%;">
                Current Allocation

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Allocation Date</th>
                                <th scope="col">Max. Class<br>(Allocation start date to today)</th>
                                <th scope="col">Class Taken<br>(Inc. Extra class)</th>
                                <th scope="col">Leave</th>
                                <th scope="col">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="line-height: 2;"><?php echo $allocationdate ?></td>
                                <td style="line-height: 2;"><?php echo $maxclass ?></td>
                                <td style="line-height: 2;"><?php echo $classtaken ?>&nbsp;(<?php echo $ctp ?>)</td>
                                <td style="line-height: 2;"><?php echo $leave ?></td>
                                <td style="line-height: 2;"><?php echo $feedback ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <hr>
                    Historical Data
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Allocation Date</th>
                                <th scope="col">Max. Class<br>(Allocation start date to end date)</th>
                                <th scope="col">Class Taken<br>(Inc. Extra class)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="line-height: 2;"><?php echo $hallocationdate ?></td>
                                <td style="line-height: 2;"><?php echo $hmaxclass ?></td>
                                <td style="line-height: 2;"><?php echo $hclasstaken ?>&nbsp;(<?php echo number_format(($hclasstaken/$hmaxclass)*100, 2, '.', '')?>%)</td>
                            </tr>
                        </tbody>
                    </table>
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