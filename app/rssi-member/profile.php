<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
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
    <title>My Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
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
    <style>
        @media (max-width:767px) {

            #cw,
            #cw1,
            #cw2,
            #cw3 {
                width: 100% !important;
            }

        }

        #cw {
            width: 30%;
        }

        #cw1 {
            width: 25%;
        }

        #cw2 {
            width: 25%;
        }

        #cw3 {
            width: 20%;
        }
    </style>
</head>

<body>
    <?php $profile_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                
                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Associate Details</th>
                                <th scope="col">Date of Join</th>
                                <th scope="col">Association Status</th>
                                <th scope="col">Badge</th>
                            </tr>
                        </thead>
                        <tbody><tr>

                                <td id="cw1" style="line-height: 1.7;"><b><?php echo $fullname ?></b><br>
                                    Associate ID - <b><?php echo $associatenumber ?></b><br>
                                    <span style="line-height: 3;"><?php echo $engagement ?>, <?php echo $gender ?> (<?php echo $age ?> Years)</span>
                                </td>
                                <td id="cw2" style="line-height: 2;"><?php echo $doj ?>&nbsp;(<?php echo $yos ?>)<br>Original DOJ&nbsp;-&nbsp;<?php echo $originaldoj ?></td>
                                <td id="cw"><?php echo $astatus ?><br><br><?php echo $effectivedate ?>&nbsp;<?php echo $remarks ?></td>
                                <td id="cw3"><?php echo $badge ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Date of Birth</th>
                                <th scope="col">National Identifier</th>
                                <th scope="col">Last 4 digits of Identifier</th>
                            </tr>
                        </thead>
                        <tbody><tr>
                                <td><?php echo $dateofbirth ?></td>
                                <td>
                                <iframe sandbox="allow-forms allow-modals allow-orientation-lock allow-pointer-lock allow-presentation allow-same-origin allow-scripts allow-top-navigation allow-top-navigation-by-user-activation" src="<?php echo $iddoc ?>" width="300px" height="200px" /></iframe>
                                </td>
                                <td><?php echo $identifier ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Application Number</th>
                                <th scope="col">Designation</th>
                                <th scope="col">Base Branch</th>
                                <th scope="col">Deputed Branch</th>
                            </tr>
                        </thead>
                        <tbody><tr>
                                <td><?php echo $applicationnumber ?></td>
                                <td id=cw1><?php echo substr($position, 0, strrpos($position, "-")) ?></td>
                                <td><?php echo $basebranch ?></td>
                                <td><?php echo $depb ?></td>
                            </tr>
                        </tbody>
                    </table>



                    <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Current Address</th>
                                <th scope="col">Permanent Address</th>
                                <th scope="col">Contact/Email Address</th>
                            </tr>
                        </thead>
                        <tbody><tr>
                                <td><?php echo $currentaddress ?></td>
                                <td><?php echo $permanentaddress ?></td>
                                <td style="line-height: 1.5;"><?php echo $phone ?><br><?php echo $email ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Language Details</th>
                                <th scope="col">Educational qualifications</th>
                                <th scope="col">Area of specialization</th>
                                <th scope="col">Work Experience</th>
                                <th scope="col">Account Approved by</th>
                            </tr>
                        </thead>
                        <tbody><tr>
                                <td style="line-height: 2;">English - <?php echo $languagedetailsenglish ?><br>Hindi - <?php echo $languagedetailshindi ?></td>
                                <td><?php echo $eduq ?></td>
                                <td><?php echo $mjorsub ?></td>
                                <td><?php echo $workexperience ?></td>
                                <td><?php echo $approvedby ?></td>
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