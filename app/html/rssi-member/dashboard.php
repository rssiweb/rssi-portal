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
    $query = "SELECT count(serial_number) AS onboarding_left FROM onboarding WHERE onboarding_flag IS NULL";
    $result = pg_query($con, $query);
    $onboarding_left = pg_fetch_result($result, 0, 'onboarding_left');

    $query = "SELECT count(id) AS exit_left FROM associate_exit WHERE exit_flag IS NULL";
    $result = pg_query($con, $query);
    $exit_left = pg_fetch_result($result, 0, 'exit_left');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RSSI-ProcessHub</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="/css/style.css">

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <!------ Include the above in your HEAD tag ---------->

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->

    <style>
        .equal-height {
            height: 100px;
            /* adjust to desired height */
            overflow: auto;
        }
    </style>
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
</head>

<body>
    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="col" style="display: inline-block; width:99%; text-align:right">
                    Home / ProcessHub
                </div>
                <section class="box" style="padding: 2%;">
                    <div class="row placeholders">
                        <div class="col-xs-6 col-sm-3 placeholder">
                            <h4>
                                Onboarding Process
                                <label class="label label-danger pull-right">Pending: <?php echo $onboarding_left ?></label>
                            </h4>
                            <p class="text-muted equal-height">Welcome to the RSSI Onboarding Portal - your one-stop destination for a smooth and efficient onboarding process.</p>
                            <a href="onboarding.php" target="_blank" class="btn btn-success btn-sm btn-block">Launch&nbsp;&nbsp;<i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>

                        <div class="col-xs-6 col-sm-3 placeholder">
                            <h4>
                            Exit Process
                                <label class="label label-danger pull-right">Pending: <?php echo $exit_left ?></label>
                            </h4>
                            <p class="text-muted equal-height">Efficiently manage the separation of associates with the RSSI Exit Process. Conduct exit interviews, collect company property, provide benefit information, and complete necessary formalities in one place.</p>
                            <a href="exit.php" target="_blank" class="btn btn-danger btn-sm btn-block">Launch&nbsp;&nbsp;<i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>

                        <div class="col-xs-6 col-sm-3 placeholder">
                            <h4>Visitor Registration</h4>
                            <p class="text-muted equal-height">Welcome to the RSSI Visitor Registration Portal. This is your one-stop solution to efficiently register and track the details of visitors to our premises.</p>
                            <a href="#" class="btn btn-warning btn-sm btn-block" disabled>Launch&nbsp;&nbsp;<i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                        <div class="col-xs-6 col-sm-3 placeholder">
                            <h4>Student Registration</h4>
                            <p class="text-muted equal-height">Welcome to the RSSI Student Admission Portal. Here, you can easily manage student data and track their admission process.</p>
                            <a href="#" class="btn btn-primary btn-sm btn-block" disabled>Launch&nbsp;&nbsp;<i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </section>
</body>

</html>