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
                                <td style="line-height: 2;">Casual Leave - <?php echo $cl ?> <br>Sick Leave - <?php echo $sl ?> <br>Other Leave - </td>
                                <td style="line-height: 2;">Casual Leave - <?php echo $cltaken ?> <br>Sick Leave - <?php echo $sltaken ?> <br>Other Leave - <?php echo $othtaken ?></td>
                                <td style="line-height: 2;">Casual Leave - <?php echo $clbal ?> <br>Sick Leave - <?php echo $slbal ?> <br>Other Leave - <?php echo $elbal ?></td>
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
                    #&nbsp;Opening balance is the balance carried forward from previous credit cycle and refers to the leave till the allocation end date.</b>
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