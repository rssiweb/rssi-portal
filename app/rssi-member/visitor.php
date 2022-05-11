<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
}
$user_check = $_SESSION['aid'];

if ($_SESSION['role'] != 'Admin' && $_SESSION['role'] != 'Offline Manager') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>

<?php
include("member_data.php");
include("database.php");
// @$appid = $_POST['get_appid'];
@$appid = $_GET['get_appid'];

if ($appid == null) {
    $result = pg_query($con, "select * from visitor WHERE visitorid is null");
}
if ($appid != null) {
    $result = pg_query($con, "select * from visitor WHERE visitorid='$appid' or existingid='$appid'");
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

</head>

<body>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col" style="display: inline-block; width:100%; text-align:right">
                        Home / Visitor pass
                    </div>
                    <form id="myform" action="" method="GET">
                        <!--onsubmit="process()-->
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <input name="get_appid" class="form-control" style="width:max-content; display:inline-block" placeholder="Visitor ID" value="<?php echo $appid ?>" required>
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" id="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>&nbsp;<a href="https://docs.google.com/forms/d/e/1FAIpQLSfGLdHHjI8J5b238SMAmf7LMkVVRJPAKnk1SjHcBUZSXATFQA/viewform" target="_blank" class="btn btn-info btn-sm" role="button"><i class="fa-solid fa-plus"></i>&nbsp;Registration</a>
                        </div>
                    </form>
                    <div class="col" style="display: inline-block; width:99%; text-align:right">
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                    </div>

                    <?php echo '
                       <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Visitor ID</th>
                                <th scope="col">Visitor details</th>
                                <th scope="col">Visit date from-to</th>
                                <th scope="col">Aadhar card</th>
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
                                <td>' . $array['visitdatefrom'] . '-' . $array['visitdateto'] . '</td><td> ' ?>

                            <?php if ($array['existingid'] != null) { ?><?php } ?>

                            <?php if ($array['existingid'] == null) { ?>
                                <?php echo
                                '<span class="noticea"><a href="' . $array['aadharcard'] . '" target="_blank"><i class="far fa-file-pdf" style="font-size:17px;color: #767676;"></i></a></span>' ?> <?php } ?>

                            <?php if ($array['existingid'] == null) { ?>
                                <?php echo
                                '</td><td><img src="' . str_replace("open", "uc", $array['photo']) . '" width="50" height="50"/></td>'

                                ?><?php } else { ?><?php echo
                                                    '</td><td></td>' ?><?php } ?>

                                <?php echo
                                '<td>' . $array['purposeofvisit'] . '</td>
                                <td>' . $array['branch'] . '</td>' ?>


                                <?php if ($array['status'] == 'Approved') { ?>
                                    <?php echo '<td><p class="label label-success">approved</p></td>' ?>
                                <?php }
                                if ($array['status'] == 'Rejected') { ?>
                                    <?php echo '<td><p class="label label-danger">rejected</p></td>' ?>
                                <?php }
                                if ($array['status'] == null) { ?>
                                    <?php echo '<td><p class="label label-default">under review</p></td>' ?>
                                <?php } ?>

                            <?php echo '</tr>';
                        } ?>
                        <?php
                    } else if ($appid == null) {
                        ?>
                            <tr>
                                <td colspan="5">Please enter Visitor Id.</td>
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

        <div class="clearfix"></div>
    </section>
    </section>
    <!-- <script>
        function process() {
            var form = document.getElementById('myform');
            var elements = form.elements;
            var values = [];

            for (var i = 0; i < elements.length; i++)
                values.push(encodeURIComponent(elements[i].name) + '=' + encodeURIComponent(elements[i].value));

            form.action += '?' + values.join('&');
        }
    </script> -->
</body>

</html>