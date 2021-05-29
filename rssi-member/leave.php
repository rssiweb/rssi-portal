<?php
session_start();
// Storing Session
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

  header("Location: index.php"); //redirect to the login page to secure the welcome page without login access.  
}
?>

<?php
include("member_data.php");
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
    <link rel="shortcut icon" href="/img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <link rel="stylesheet" href="/rssi-student/style.css">
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <!------ Include the above in your HEAD tag ---------->

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
                                <th scope="col">Opening Leave Balance</th>
                                <th scope="col">Leaves Approved</th>
                                <th scope="col">Leave Balance</th>
                                <th scope="col">Apply Leave</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                            <td style="line-height: 2;">Casual Leave - <?php echo $CL ?> <br>Sick Leave - <?php echo $SL ?> <br>Other Leave - <?php echo $EL ?></td>
                            <td style="line-height: 2;">Casual Leave - <?php echo $CLTaken ?> <br>Sick Leave - <?php echo $SLTaken ?> <br>Other Leave - <?php echo $OTHTaken ?></td>
                                <td style="line-height: 2;">Casual Leave - <?php echo $CLBal ?> <br>Sick Leave - <?php echo $SLBal ?> <br>Other Leave - <?php echo $ELBal ?></td>
                                <td style="line-height: 2;"><span class="noticet"><a href="<?php echo $Leaveapply ?>" target="_blank">Leave Request Form</a></span></td>
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