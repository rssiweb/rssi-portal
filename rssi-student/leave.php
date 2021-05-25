<?php
session_start();
// Storing Session
$user_check = $_SESSION['sid'];

if (!$_SESSION['sid']) {

    header("Location: unauth.php"); //redirect to the login page to secure the welcome page without login access.  
}
?>

<?php
include("student_data.php");
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Leave</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <!-- Main css -->
    <style>
        <?php include 'style.css'; ?>
    </style>
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
                                <th scope="col">Leaves Approved</th>
                                <th scope="col">Apply Leave</th>
                                <th scope="col">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="line-height: 2;">Casual Leave - <?php echo $CLTaken ?> <br>Sick Leave - <?php echo $SLTaken ?> <br>Other Leave - <?php echo $OTHTaken ?></td>
                                <td><span class="noticet"><a href="<?php echo $Leaveapply ?>" target="_blank">Leave Request Form</a></span></td>
                                <td></td>
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