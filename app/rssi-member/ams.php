<?php
session_start();
// Storing Session
include("../util/login_util.php");


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

if ($role != 'Admin' && $role != 'Offline Manager') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}

date_default_timezone_set('Asia/Kolkata');

if ($_POST) {
    @$noticeid = $_POST['noticeid'];
    @$category = $_POST['category'];
    @$noticesub = $_POST['noticesub'];
    @$url = $_POST['url'];
    @$issuedby = $_POST['issuedby'];
    @$now = date('Y-m-d H:i:s');
    if ($noticeid != "") {
        $notice = "INSERT INTO notice (noticeid, date, subject, url, issuedby, category) VALUES ('$noticeid','$now','$noticesub','$url','$issuedby','$category')";
        $result = pg_query($con, $notice);
        $cmdtuples = pg_affected_rows($result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>RSSI-AMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
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
        .checkbox {
            padding: 0;
            margin: 0;
            vertical-align: bottom;
            position: relative;
            top: 0px;
            overflow: hidden;
        }

        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            font-size: x-small;
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <?php if (@$noticeid != null && @$cmdtuples == 0) { ?>

                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                    </div>
                <?php
                } else if (@$cmdtuples == 1) { ?>

                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Database has been updated successfully for notice id <?php echo @$noticeid ?>.</span>
                    </div>
                <?php } ?>

                <div class="row">
                    <section class="box" style="padding: 2%;">
                        <p>Home / AMS (Announcement Management System)</p><br><br>

                        <form autocomplete="off" name="ams" id="ams" action="ams.php" method="POST">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">

                                    <span class="input-help">
                                        <input type="text" name="noticeid" class="form-control" style="width:max-content; display:inline-block" placeholder="Notice Id" required>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Notice ID</small>
                                    </span>

                                    <span class="input-help">
                                        <select name="category" class="form-control" style="width:max-content; display:inline-block" required>
                                            <?php if ($category == null) { ?>
                                                <option value="" disabled selected hidden>Category</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $category ?></option>
                                            <?php }
                                            ?>
                                            <option>Internal</option>
                                            <option>Public</option>
                                        </select>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Category</small>
                                    </span>

                                    <span class="input-help">
                                        <textarea type="text" name="noticesub" class="form-control" style="width:max-content; display:inline-block" placeholder="Notice Subject" value=""></textarea>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Subject</small>
                                    </span>

                                    <span class="input-help">
                                        <input type="url" name="url" class="form-control" style="width:max-content; display:inline-block" placeholder="URL" value="" required>
                                        <small id="passwordHelpBlock" class="form-text text-muted">URL</small>
                                    </span>

                                    <input type="hidden" name="issuedby" class="form-control" style="width:max-content; display:inline-block" placeholder="Issued by" value="<?php echo $fullname ?>" required readonly>

                                </div>

                            </div>

                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-plus"></i>&nbsp;&nbsp;Add</button>
                            </div>
                        </form>
                    </section>
                </div>
                <script>
                    $(document).ready(function() {
                        $(window).scroll(function() {
                            if ($(this).scrollTop() > 50) {
                                $('#back-to-top').fadeIn();
                            } else {
                                $('#back-to-top').fadeOut();
                            }
                        });
                        // scroll body to 0px on click
                        $('#back-to-top').click(function() {
                            $('body,html').animate({
                                scrollTop: 0
                            }, 400);
                            return false;
                        });
                    });
                </script>
                <a id="back-to-top" href="#" class="go-top" role="button"><i class="fa fa-angle-up"></i></a>
</body>

</html>