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
    <title>My Document</title>
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
    <?php $document_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                
                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Official Letter</th>
                                <th scope="col">Payslip/Reimbursement</th>
                                <th scope="col">Miscellaneous</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                              
                                <td style="line-height: 2;"><span class="noticet"><a href="<?php echo $profile ?>" target="_blank"><?php echo $filename ?></a></span>
                                <?php echo $officialdoc ?>
                                </td>
                                <td style="line-height: 2;"><?php echo $notification ?></td>
                                <td style="line-height: 2;"><span class="noticet"><a href="idcard.php" target="_blank">RSSI Identity Card</a><br>
                                <a href="myasset.php" target="_self">My Asset</a>
                            </span></td>
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