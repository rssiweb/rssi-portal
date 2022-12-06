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

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>

<?php

@$id = $_POST['get_id'];
$result = pg_query($con, "SELECT * FROM rssimyaccount_members WHERE filterstatus='$id' and position LIKE '%-Faculty%' order by filterstatus asc,today desc");
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
    <title>Faculty Details</title>
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
            width: 10%;
        }

        #cw1 {
            width: 15%;
        }

        #cw2 {
            width: 20%;
        }

        #cw3 {
            width: 25%;
        }
    </style>

</head>

<body>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col" style="display: inline-block; width:50%;margin-left:1.5%">
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                    </div>
                    <div class="col" style="display: inline-block; width:47%; text-align:right">
                        Home / <span class="noticet"><a href="faculty.php" target="_self">Faculty</a></span> / Faculty Details
                    </div>
                </div>

                <section class="box" style="padding: 2%;">

                    <form action="" method="POST">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <select name="get_id" class="form-control" style="width:max-content;" placeholder="Appraisal type" required>
                                    <?php if ($id == null) { ?>
                                        <option value="" disabled selected hidden>Select Status</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $id ?></option>
                                    <?php }
                                    ?>
                                    <option>Active</option>
                                    <!--<option>Inactive</option>-->
                                </select>
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                        </div>
                    </form>
                    <?php
                    echo '<table class="table">
          <thead style="font-size: 12px;">
          <tr>
          <th scope="col" id="cw">Photo</th>
          <th scope="col" id="cw1">Name</th>
          <th scope="col" id="cw2">Highest Educational Qualifications</th>
          <th scope="col" id="cw3">Area of specialization</th>
          <th scope="col">Work Experience</th>
        </tr>
        </thead>' ?>
                    <?php if (sizeof($resultArr) > 0) { ?>
                        <?php
                        echo '<tbody>';
                        foreach ($resultArr as $array) {
                            echo '<tr>' ?>
                            <?php if ($array['disc'] == null) { ?>
                                <?php echo '<td><img src="' . $array['photo'] . '" width=50px/></td>' ?>
                            <?php } else { ?> <?php echo '<td><img src="https://res.cloudinary.com/hs4stt5kg/image/upload/v1609410219/faculties/blank.jpg" width=50px/></td>' ?>
                            <?php } ?>
                            <?php echo '<td>' . $array['fullname']  ?>

                            <?php if ($array['on_leave'] != null) { ?>
                                <?php echo '<br><span class="label label-danger">on leave</span>'
                                ?>
                            <?php    } else {
                            } ?>
                        <?php
                            echo '
              </td><td>' . $array['eduq'] . '</td>
            <td>' . $array['mjorsub'] . '</td>
            <td>' . $array['workexperience'] . '</td>
            </tr>';
                        } ?>
                    <?php
                    } else if ($id == "") {
                    ?>
                        <tr>
                            <td colspan="5">Please select Status.</td>
                        </tr>
                    <?php
                    } else {
                    ?>
                        <tr>
                            <td colspan="5">No record found for <?php echo $id ?></td>
                        </tr>
                    <?php }

                    echo '</tbody>
                        </table>';
                    ?>
                </section>
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
