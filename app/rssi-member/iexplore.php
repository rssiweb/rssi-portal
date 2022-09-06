<?php
session_start();
// Storing Session
include("../util/login_util.php");


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

date_default_timezone_set('Asia/Kolkata');

if ($role == 'Admin') {

    if ($_POST) {
        @$courseid = $_POST['courseid'];
        @$coursename = $_POST['coursename'];
        @$language = $_POST['language'];
        @$passingmarks = $_POST['passingmarks'];
        @$url = $_POST['url'];
        @$validity = $_POST['validity'];
        @$issuedby = $_POST['issuedby'];
        @$now = date('Y-m-d H:i:s');
        if ($courseid != "") {
            $wbt = "INSERT INTO wbt (date, courseid, coursename, language, passingmarks, url, issuedby,validity) VALUES ('$now','$courseid','$coursename','$language','$passingmarks','$url','$issuedby','$validity')";
            $result = pg_query($con, $wbt);
            $cmdtuples = pg_affected_rows($result);
        }
    }
}


@$courseid1 = trim($_GET['courseid1']);
@$language1 = $_GET['language1'];

if (($courseid1 == null && $language1 == 'ALL')) {
    $result1 = pg_query($con, "select * from wbt order by date desc");
} else if (($courseid1 == null && ($language1 != 'ALL' && $language1 != null))) {
    $result1 = pg_query($con, "select * from wbt where language='$language1' order by date desc");
}  else if (($courseid1 != null && ($language1 == null || $language1 == 'ALL'))) {
    $result1 = pg_query($con, "select * from wbt where courseid='$courseid1' order by date desc");
} else if (($courseid1 != null && ($language1 != 'ALL' && $language1 != null))) {
    $result1 = pg_query($con, "select * from wbt where courseid='$courseid1' AND language='$language1' order by date desc");
}

else {
    $result1 = pg_query($con, "select * from wbt order by date desc");
}
if (!$result1) {
    echo "An error occurred.\n";
    exit;
}

$resultArr1 = pg_fetch_all($result1);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>iExplore</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
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
                <div class="row">
                    <?php if ($role == 'Admin') { ?>
                        <div class="col" style="display: inline-block; width:50%;margin-left:1.5%">Home / iExplore Management System
                        </div>
                    <?php } else { ?>
                        <div class="col" style="display: inline-block; width:50%;margin-left:1.5%">Home / iExplore Web-based training (WBT)
                        </div>
                    <?php } ?>
                    <div class="col" style="display: inline-block; width:47%; text-align:right">
                        <a href="my_learning.php" target="_self" class="btn btn-danger btn-sm" role="button">My Learnings</a>
                    </div>
                </div>
                <?php if ($role == 'Admin') { ?>

                    <?php if (@$courseid != null && @$cmdtuples == 0) { ?>

                        <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                        </div>
                    <?php
                    } else if (@$cmdtuples == 1) { ?>

                        <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Database has been updated successfully for course id <?php echo @$courseid ?>.</span>
                        </div>
                <?php } ?>

                    <section class="box" style="padding: 2%;">
                        <form autocomplete="off" name="wbt" id="wbt" action="iexplore.php" method="POST">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">

                                    <span class="input-help">
                                        <input type="text" name="courseid" class="form-control" style="width:max-content; display:inline-block" placeholder="Course id" required>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Course id</small>
                                    </span>
                                    <span class="input-help">
                                        <input type="text" name="coursename" class="form-control" style="width:max-content; display:inline-block" placeholder="Course name" required>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Course name</small>
                                    </span>

                                    <span class="input-help">
                                        <select name="language" class="form-control" style="width:max-content; display:inline-block" required>
                                            <?php if ($language == null) { ?>
                                                <option value="" disabled selected hidden>Language</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $language ?></option>
                                            <?php }
                                            ?>
                                            <option>English</option>
                                            <option>Hindi</option>
                                            <option>Bengali</option>
                                        </select>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Language</small>
                                    </span>
                                    <span class="input-help">
                                        <input type="number" name="passingmarks" max="100" accuracy="2" min="0" class="form-control" style="width:max-content; display:inline-block" placeholder="%" value="" required>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Mastery Score</small>
                                    </span>

                                    <span class="input-help">
                                        <input type="url" name="url" class="form-control" style="width:max-content; display:inline-block" placeholder="URL" value="" required>
                                        <small id="passwordHelpBlock" class="form-text text-muted">URL</small>
                                    </span>

                                    <span class="input-help">
                                        <select name="validity" class="form-control" style="width:max-content; display:inline-block" required>
                                            <?php if ($validity == null) { ?>
                                                <option value="" disabled selected hidden>Validity</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $validity ?></option>
                                            <?php }
                                            ?>
                                            <option>0.5</option>
                                            <option>1</option>
                                            <option>2</option>
                                            <option>3</option>
                                            <option>5</option>
                                        </select>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Validity (Year)</small>
                                    </span>

                                    <input type="hidden" name="issuedby" class="form-control" style="width:max-content; display:inline-block" placeholder="Issued by" value="<?php echo $fullname ?>" required readonly>

                                </div>

                            </div>

                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-plus"></i>&nbsp;&nbsp;Add</button>
                            </div>
                        </form>
                        <br>
                <?php } ?>
                    <?php if ($role != 'Admin') { ?>
                        <section class="box" style="padding: 2%;">
                    <?php } ?>
                        <form action="" method="GET">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">
                                    <span class="input-help">
                                        <input type="text" name="courseid1" class="form-control" style="width:max-content; display:inline-block" placeholder="Course id" value="<?php echo @$courseid1 ?>">
                                        <small id="passwordHelpBlock" class="form-text text-muted">Course id</small>
                                    </span>

                                    <span class="input-help">
                                        <select name="language1" class="form-control" style="width:max-content; display:inline-block">
                                            <?php if ($language1 == null) { ?>
                                                <option value="" disabled selected hidden>Language</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $language1 ?></option>
                                            <?php }
                                            ?>
                                            <option>ALL</option>
                                            <option>English</option>
                                            <option>Hindi</option>
                                            <option>Bengali</option>
                                        </select>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Language</small>
                                    </span>

                                </div>
                            </div>
                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                            </div>
                        </form>

                        <div class="col" style="display: inline-block; width:99%; text-align:right">
                            Record count:&nbsp;<?php echo sizeof($resultArr1) ?>
                        </div>
                        <?php echo '
                    <table class="table" id="table-id">
                        <thead>
                            <tr>
                                <th scope="col">Course id</th>
                                <th scope="col">Course name</th>
                                <th scope="col">Language</th>
                                <th scope="col">Mastery Score</th>
                                <th scope="col">Validity (Year)</th>
                                <th scope="col">Assesment</th>
                            </tr>
                        </thead>' ?>
                        <?php if ($resultArr1 != null) {
                            echo '<tbody>';
                            foreach ($resultArr1 as $array) {
                                echo '
                            <tr>
                                <td>' . $array['courseid'] . '</td>
                                <td>' . $array['coursename'] . '</td>
                                <td>' . $array['language'] . '</td>
                                <td>' . $array['passingmarks'] . '%</td>
                                <td>' . $array['validity'] . '</td>
                                <td><a href="' . $array['url'] . '" target="_blank" title="'.$array['coursename'].'-'.$array['language'].'"><button type="button" id="btn" class="btn btn-warning btn-sm" style="outline: none; color:#fff"></span>Launch&nbsp;'.$array['courseid'].'</button></a></td>
                            </tr>';
                            }
                        } else if ($courseid1 == null && $language1 == null) {
                            echo '<tr>
                                          <td colspan="5">Please enter at least one value to get the WBT details.</td>
                                      </tr>';
                        } else {
                            echo '<tr>
                                      <td colspan="5">No record found for' ?>&nbsp;<?php echo $courseid1 ?>&nbsp;<?php echo $language1 ?>
                    <?php echo '</td>
                                  </tr>';
                        }
                        echo '</tbody>
                        </table>';
                    ?>
            </div>
        </section>
    </section>

    <!-- Back top -->
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