<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("sid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

setlocale(LC_TIME, 'fr_FR.UTF-8');

$result = pg_query($con, "SELECT * FROM fees
    
    left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
    left join (SELECT student_id, studentname,category,contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    where student_id='$user_check'");

$totalapprovedamount = pg_query($con, "SELECT SUM(fees) FROM fees WHERE fees.studentid='$user_check'");

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
$resultArrr = pg_fetch_result($totalapprovedamount, 0, 0);
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>My fees</title>
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
            policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
        });
    </script>

</head>

<body>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col" style="display: inline-block; width:50%;margin-left:1.5%; font-size:small">
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?><br>Total fees submitted:&nbsp;<p class="label label-default"><?php echo ($resultArrr) ?></p>
                    </div>
                    <div class="col" style="display: inline-block; width:47%; text-align:right">
                        <span class="noticea"><a href="home.php" target="_self">Home</a></span> / My Fees
                    </div>
                </div><br>

                <?php echo '
                       <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Reference number</th>
                                <th scope="col">Month</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Submitted on</th>
                                <th scope="col">Collected by</th>
                            </tr>
                        </thead>' ?>
                <?php if (sizeof($resultArr) > 0) { ?>
                    <?php
                    echo '<tbody>';
                    foreach ($resultArr as $array) {
                        echo '<tr>
                                <td>' . $array['id'] . '</td>
                                <td>' . @strftime('%B', mktime(0, 0, 0,  $array['month'])) . '</td>
                                <td>' . $array['fees'] . '</td>
                                <td>' . $array['date'] . '</td>
                                <td>' . $array['fullname'] . '</td>
                            </tr>';
                    } ?>
                <?php
                } else {
                ?>
                    <tr>
                        <td colspan="5">No record was found for you.</td>
                    </tr>
                <?php }

                echo '</tbody>
                                    </table>';
                ?>
            </div>
        </section>
    </section>
</body>

</html>
