<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
}
if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}
$user_check = $_SESSION['aid'];

if ($role != 'Admin' && $role != 'Offline Manager') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}

date_default_timezone_set('Asia/Kolkata');
$today = date("Y-m-d");
@$appid = $_GET['get_appid'];
@$status = $_GET['get_id'];

if ($appid == null && $status == null) {
    $result = pg_query($con, "select * from visitor WHERE visitorid is null");
}
if ($appid != null && ($status == null || $status == 'ALL')) {
    $result = pg_query($con, "select * from visitor WHERE visitorid='$appid' or existingid='$appid' order by visitdatefrom desc");
}
if ($appid != null && $status != null && $status != 'ALL') {
    $result = pg_query($con, "select * from visitor WHERE (visitorid='$appid' or existingid='$appid') AND EXTRACT(MONTH FROM TO_DATE('$status', 'Month'))=EXTRACT(MONTH FROM visitdatefrom) order by visitdatefrom desc");
}
if ($appid == null && $status != null && $status != 'ALL') {
    $result = pg_query($con, "select * from visitor WHERE EXTRACT(MONTH FROM TO_DATE('$status', 'Month'))=EXTRACT(MONTH FROM visitdatefrom) order by visitdatefrom desc");
}

if ($appid == null && $status == 'ALL') {
    $result = pg_query($con, "select * from visitor order by visitdatefrom desc");
}

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
    <title>Visitor pass</title>
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
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                    </div>
                    <div class="col" style="display: inline-block; width:47%; text-align:right">
                        Home / Visitor pass
                    </div>
                    <section class="box" style="padding: 2%;">
                        <form id="myform" action="" method="GET">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">
                                    <input name="get_appid" class="form-control" style="width:max-content; display:inline-block" placeholder="Visitor ID" value="<?php echo $appid ?>">
                                </div>
                                <select name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Select policy year">
                                    <?php if ($status == null) { ?>
                                        <option value="" hidden selected>Select month</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $status ?></option>
                                    <?php }
                                    ?>
                                    <option>January</option>
                                    <option>February</option>
                                    <option>March</option>
                                    <option>April</option>
                                    <option>May</option>
                                    <option>June</option>
                                    <option>July</option>
                                    <option>August</option>
                                    <option>September</option>
                                    <option>October</option>
                                    <option>November</option>
                                    <option>December</option>
                                    <option>ALL</option>
                                </select>


                            </div>
                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" id="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>&nbsp;<a href="https://docs.google.com/forms/d/e/1FAIpQLSfGLdHHjI8J5b238SMAmf7LMkVVRJPAKnk1SjHcBUZSXATFQA/viewform" target="_blank" class="btn btn-info btn-sm" role="button"><i class="fa-solid fa-plus"></i>&nbsp;Registration</a>
                            </div>
                        </form>

                        <?php echo '
                       <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Visitor ID</th>
                                <th scope="col">Visitor details</th>
                                <th scope="col">Visit date from</th>
                                <th scope="col">Visit date to</th>
                                <th scope="col">Identity proof</th>
                                <th scope="col">Photo</th>
                                <th scope="col">Purpose of visit</th>
                                <th scope="col">Branch name</th>
                                <th scope="col">HR remarks</th>
                            </tr>
                        </thead>' ?>
                        <?php if (sizeof($resultArr) > 0) { ?>
                            <?php
                            echo '<tbody>';
                            foreach ($resultArr as $array) {
                                echo '<tr><td>' ?>

                                <?php if ($array['existingid'] == null) { ?>
                                    <?php echo $array['visitorid'] ?>
                                <?php }
                                if ($array['existingid'] != null) { ?>
                                    <?php echo $array['existingid'] ?>
                                <?php } ?>

                                <?php echo '</td>
                                <td>' . $array['visitorname'] . '<br>' . $array['contact'] . '<br>' . $array['email'] . '</td>
                                <td>' . date("d/m/Y", strtotime($array['visitdatefrom'])) . '&nbsp;' . date("g:i a", strtotime($array['visittime'])) . '</td>
                                
                                <td>' . date("d/m/Y", strtotime($array['visitdateto'])) . '</td>
                                
                                <td><span class="noticea"><a href="' . $array['aadharcard'] . '" target="_blank"><i class="far fa-file-pdf" style="font-size:17px;color: #767676;"></i></a></span></td>
                                
                                <td><img src="' . str_replace("open", "uc", $array['photo']) . '" width="50" height="50"/></td>
                                <td>' . $array['purposeofvisit'] . '</td>
                                <td>' . $array['branch'] . '</td>'  ?>


                                    <?php if ($array['status'] == 'Approved' && $array['visitdateto'] >= $today) { ?>
                                        <?php echo '<td><p class="label label-success">approved</p></td>' ?>
                                    <?php }
                                    if ($array['status'] == 'Rejected' && $array['visitdateto'] >= $today) { ?>
                                        <?php echo '<td><p class="label label-danger">rejected</p></td>' ?>
                                    <?php }
                                    if ($array['status'] == null && $array['visitdateto'] >= $today) { ?>
                                        <?php echo '<td><p class="label label-default">under review</p></td>' ?>
                                    <?php } ?>

                                    <?php
                                    if ($array['status'] != 'Visited' && $array['visitdateto'] < $today) { ?>
                                        <?php echo '<td><p class="label label-default">expired</p></td>' ?>
                                    <?php } ?>
                                    <?php
                                    if ($array['status'] == 'Visited') { ?>
                                        <?php echo '<td><p class="label label-default">visited</p></td>' ?>
                                    <?php } ?>

                                <?php echo '</tr>';
                            } ?>
                            <?php } else if ($appid == null) {
                            ?>
                                <tr>
                                    <td colspan="5">Please enter filter value.</td>
                                </tr>
                            <?php
                        } else {
                            ?>
                                <tr>
                                    <td colspan="5">No record was found for the selected filter value.</td>
                                </tr>
                            <?php }

                        echo '</tbody>
                                    </table>';
                            ?>
                    </section>
                </div>
        </section>
    </section>
</body>

</html>
