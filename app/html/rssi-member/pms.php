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

if ($role != 'Admin') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}

date_default_timezone_set('Asia/Kolkata');

if ($_POST) {
    @$user_id = strtoupper($_POST['userid']);
    @$password = $_POST['newpass'];
    @$type = $_POST['type'];
    @$newpass_hash = password_hash($password, PASSWORD_DEFAULT);
    $now = date('Y-m-d H:i:s');

    if ($type == "Associate") {
        $change_password_query = "UPDATE rssimyaccount_members SET password='$newpass_hash', default_pass_updated_by='$user_check', default_pass_updated_on='$now' where associatenumber='$user_id'";
    } else {
        $change_password_query = "UPDATE rssimyprofile_student SET password='$newpass_hash', default_pass_updated_by='$user_check', default_pass_updated_on='$now' where student_id='$user_id'";
    }
    $result = pg_query($con, $change_password_query);
    $cmdtuples = pg_affected_rows($result);
}

@$get_id = $_POST['get_id'];
@$get_status = strtoupper($_POST['get_status']);

if ($get_id == "Associate" && $get_status != null) {
    $change_details = "SELECT * from rssimyaccount_members where associatenumber='$get_status'";
} else if ($get_id == "Associate" && $get_status == null) {
    $change_details = "SELECT * from rssimyaccount_members where filterstatus='Active' AND default_pass_updated_on is not null";
} else if ($get_id == "Student" && $get_status != null) {
    $change_details = "SELECT * from rssimyprofile_student where student_id='$get_status'";
} else if ($get_id == "Student" && $get_status == null) {
    $change_details = "SELECT * from rssimyprofile_student where filterstatus='Active' AND default_pass_updated_on is not null";
} else {
    $change_details = "SELECT * from rssimyprofile_student where student_id=''";
}

$result = pg_query($con, $change_details);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArrr = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>RSSI-PMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
<link rel="stylesheet" href="/css/style.css" />

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
    </style>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">

                <?php if (@$type != null && @$cmdtuples == 0) { ?>

                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: The association type and user ID you entered is incorrect.</span>
                    </div>
                <?php
                } else if (@$cmdtuples == 1) { ?>

                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Password has been updated successfully for <?php echo @$user_id ?>.</span>
                    </div>
                <?php } ?>


                <div class="row">
                    <section class="box" style="padding: 2%;">
                        <p>Home / PMS (Password management system)</p><br><br>
                        <form autocomplete="off" name="pms" id="pms" action="pms.php" method="POST">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">
                                    <select name="type" class="form-control" style="width:max-content; display:inline-block" required>
                                        <?php if ($type == null) { ?>
                                            <option value="" disabled selected hidden>Association Type</option>
                                        <?php
                                        } else { ?>
                                            <option hidden selected><?php echo $type ?></option>
                                        <?php }
                                        ?>
                                        <option>Associate</option>
                                        <option>Student</option>
                                    </select>
                                    <input type="text" name="userid" class="form-control" style="width:max-content; display:inline-block" placeholder="User ID" value="" required>
                                    <input type="password" name="newpass" id="newpass" class="form-control" style="width:max-content; display:inline-block" placeholder="New password" value="" required>
                                </div>

                            </div>

                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-arrows-rotate"></i>&nbsp;&nbsp;Update</button>
                            </div>
                            <br>
                            <label for="show-password" class="field__toggle" style="margin-top: 5px;font-weight: unset;">
                                <input type="checkbox" class="checkbox" id="show-password" class="field__toggle-input" style="display: inline-block;" />&nbsp;Show password
                            </label>
                        </form>

                        <br><b><span class="underline">Password change details</span></b><br><br>

                        <form name="changedetails" id="changedetails" action="" method="POST">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">
                                    <select name="get_id" class="form-control" style="width:max-content; display:inline-block" required>
                                        <?php if ($get_id == null) { ?>
                                            <option value="" disabled selected hidden>Association Type</option>
                                        <?php
                                        } else { ?>
                                            <option hidden selected><?php echo $get_id ?></option>
                                        <?php }
                                        ?>
                                        <option>Associate</option>
                                        <option>Student</option>
                                    </select>&nbsp;
                                    <input type="text" name="get_status" class="form-control" style="width:max-content; display:inline-block" placeholder="User ID" value="">
                                </div>
                            </div>
                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_idd" class="btn btn-primary btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                            </div>
                        </form>
                        <div class="col" style="display: inline-block; width:99%; text-align:right">
                            Record count:&nbsp;<?php echo sizeof($resultArrr) ?>
                        </div>

                        <?php echo '
                       <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">User ID</th>
                                <th scope="col">Set on</th>
                                <th scope="col">Set by</th>
                                <th scope="col">Changed on</th>
                                <th scope="col">Changed by</th>
                                
                            </tr>
                        </thead>' ?>
                        <?php if (sizeof($resultArrr) > 0) { ?>
                            <?php
                            echo '<tbody>';
                            foreach ($resultArrr as $array) {
                                echo '<tr>
                                <td>' . @$array['associatenumber'] . @$array['student_id'] ?>

                                <?php if ($array['password_updated_by'] == null || $array['password_updated_on'] < $array['default_pass_updated_on']) { ?>
                                    <?php echo '<p class="label label-warning">defaulter</p>' ?><?php } ?>

                                    <?php
                                    echo '</td>' ?>

                                    <?php if ($array['default_pass_updated_on'] != null) { ?>

                                        <?php echo '<td>' . @date("d/m/Y g:i a", strtotime($array['default_pass_updated_on'])) . '</td>' ?>
                                    <?php } else { ?>
                                        <?php echo '<td></td>' ?>
                                    <?php } ?>


                                    <?php echo '<td>' . $array['default_pass_updated_by'] . '</td>' ?>

                                    <?php if ($array['password_updated_on'] != null) { ?>

                                        <?php echo '<td>' . @date("d/m/Y g:i a", strtotime($array['password_updated_on'])) . '</td>' ?>
                                    <?php } else { ?>
                                        <?php echo '<td></td>' ?>
                                    <?php } ?>
                                <?php echo '<td>' . $array['password_updated_by'] . '</td></tr>';
                            } ?>
                            <?php
                        } else if ($get_id == null && $get_status == null) {
                            ?>
                                <tr>
                                    <td colspan="5">Please select Filter value.</td>
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

                </div>
            </div>
            </div>
        </section>
        </div>
    </section>
    </section>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        var password = document.querySelector("#newpass");
        var toggle = document.querySelector("#show-password");
        // I'm using the "(click)" event to make this works cross-browser.
        toggle.addEventListener("click", handleToggleClick, false);
        // I handle the toggle click, changing the TYPE of password input.
        function handleToggleClick(event) {

            if (this.checked) {

                console.warn("Change input 'type' to: text");
                password.type = "text";

            } else {

                console.warn("Change input 'type' to: password");
                password.type = "password";

            }

        }
    </script>


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
