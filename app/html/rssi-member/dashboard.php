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
} ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard</title>
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
            height: 150px;
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
                    Home / My Allocation
                </div>
                <section class="box" style="padding: 2%;">
                    <h1 class="page-header">Dashboard</h1>
                    <div class="row placeholders">
                        <div class="col-xs-6 col-sm-3 placeholder">

                            <h4>Onboarding Process</h4>
                            <p class="text-muted equal-height">Onboarding is the process of integrating new associates into the organization and familiarizing them with the organization's culture, values, and expectations. This includes introducing them to their team members, providing training and resources, setting goals, and ensuring they have the necessary tools to succeed in their new role.</p>
                            <a href="#" class="btn btn-primary btn-sm btn-block">Launch</a>
                        </div>
                        <div class="col-xs-6 col-sm-3 placeholder">

                            <h4>Exit Process</h4>
                            <p class="text-muted equal-height">Exit process is the process of separating an associate from the organization, which includes conducting exit interviews, collecting company property, providing necessary information about other benefits, and completing any necessary paperwork or formalities.</p>
                            <a href="#" class="btn btn-primary btn-sm btn-block">Launch</a>
                        </div>
                        <div class="col-xs-6 col-sm-3 placeholder">
                            <a href="#" class="btn btn-primary btn-sm btn-block"><i class="fa fa-rocket"></i> Launch Admission App</a>
                            <h4>Admission Process</h4>
                            <p class="text-muted equal-height">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis vestibulum velit nec mauris faucibus, vitae finibus tellus aliquam. Duis tempor augue non elit pharetra congue.</p>
                        </div>
                        <div class="col-xs-6 col-sm-3 placeholder">
                            <a href="#" class="btn btn-primary btn-sm btn-block"><i class="fa fa-rocket"></i> Launch Visitor Registration App</a>
                            <h4>Visitor Registration</h4>
                            <p class="text-muted equal-height">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis vestibulum velit nec mauris faucibus, vitae finibus tellus aliquam. Duis tempor augue non elit pharetra congue.</p>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </section>
</body>

</html>