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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
<link rel="stylesheet" href="/css/style.css" />
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
            policyLink: 'https://www.rssi.in/disclaimer'
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

                    <table class="table" style="font-size: 13px">
                        <thead>
                            <tr>
                                <th scope="col">Official Letter</th>
                                <th scope="col">Payslip/Reimbursement</th>
                                <th scope="col">Miscellaneous</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>

                                <td style="line-height: 2;">
                                <span class="noticea"><a href="<?php echo $profile ?>" target="_blank" title="<?php echo $filename ?>">Letter of Offer</a><br>
                                <a href="my_certificate.php">My Certificate</a></span>
                                </td>
                                <td style="line-height: 2;"><span class="noticea"><a href="payslip.php" target="_self">Payslip</a></span></td>
                                <td style="line-height: 2;"><span class="noticea"><a href="idcard.php" target="_blank">RSSI Identity Card</a><br>
                                        <a href="gps.php" target="_self">GPS (Global Procurement System)</a>
                                    </span></td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>
        </section>
    </section>
</body>

</html>
