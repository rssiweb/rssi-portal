<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
    header("Location: index.php");
}
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
} ?>

<?php
include("member_data.php");
include("database.php");

$result = pg_query($con, "select * from asset inner join rssimyaccount_members ON asset.userid=rssimyaccount_members.associatenumber where associatenumber='$user_check'");
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Asset Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
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
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col" style="display: inline-block; width:99%; text-align:right">
                    <span class="noticet" style="line-height: 2;"><a href="document.php">Back to My Document</a></span>
                    </div>

                    <div class="col" style="display: inline-block; width:99%; text-align:right">
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                    </div>
                </div>

                <?php echo '
                       <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Reference number</th>
                                <th scope="col">Asset/Agreement details</th>
                                <th scope="col">Issued on</th>
                                <th scope="col">Agreement</th>
                                <th scope="col">Returned on</th>
                                <th scope="col">Received on</th>
                                <th scope="col">Status</th>
                                <th scope="col">HR remarks</th>
                            </tr>
                        </thead>' ?>
                <?php if (sizeof($resultArr) > 0) { ?>
                    <?php
                    echo '<tbody style="font-size: 13px;">';
                    foreach ($resultArr as $array) {
                        echo '<tr>
                                <td>' . $array['submissionid'] . '</td>
                                <td>' . $array['assetdetails'] . $array['agreementname'] ?>

                        <?php if ($array['category'] == 'Asset') { ?>
                            <?php echo '<p class="label label-danger">asset</p>' ?>
                        <?php } else {
                        } ?>

                    <?php echo '</td>
                                <td>' . $array['issuedon'] . '</td>
                                <td><span class="noticea"><a href="' . $array['agreement'] . '" target="_blank"><i class="far fa-file-pdf" style="font-size:17px;color: #767676;"></i></a></span>
                                
                                </td>
                                <td>' . $array['returnedon'] . '</td>
                                <td>' . $array['receivedon'] . '</td>
                                <td>' . $array['status'] . '</td>
                                <td>' . $array['comment'] . '</td>
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
        </section>
        </div>

        <div class="clearfix"></div>
    </section>
    </section>
</body>

</html>