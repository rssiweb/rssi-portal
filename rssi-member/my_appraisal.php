<?php
session_start();
// Storing Session
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

    header("Location: index.php"); //redirect to the login page to secure the welcome page without login access.  
}
?>
<?php
include("member_data.php");
?>
<?php
include("database.php");
@$id = $_POST['get_id'];
$view_users_query = "select * from myappraisal_sheet1 WHERE associatenumber='$user_check' AND appraisaltype='$id'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
    $associatenumber = $row[0];
    $fullname = $row[1];
    $appraisaltype = $row[2];
    $effectivestartdate = $row[3];
    $effectiveenddate = $row[4];
    $role = $row[5];
    $feedback = $row[6];
    $scopeofimprovement = $row[7];
    $ipf = $row[8];
    $__hevo_id = $row[9];
    $__hevo__ingested_at = $row[10];
    $__hevo__marked_deleted = $row[11]
?>
<?php } ?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>My Appraisal</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
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
            width: 15%;
        }

        #cw1 {
            width: 20%;
        }

        #cw2 {
            width: 25%;
        }

        #cw3 {
            width: 20%;
        }

        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }
    </style>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php $appraisal_active = 'active'; ?>
    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <section class="box" style="padding: 2%;">
                    <form action="" method="POST">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <select name="get_id" class="form-control" style="width:max-content;" placeholder="Appraisal type" required>
                                    <?php if ($id == null) { ?>
                                        <option value="" hidden selected>Select Appraisal type</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $id ?></option>
                                    <?php }
                                    ?>
                                    <option>Quarterly 2/2021</option>
                                    <option>Quarterly 1/2021</option>
                                </select>
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" class="btn btn-primary" style="outline: none;">
                                <span class="glyphicon glyphicon-search"></span>&nbsp;Search</button>
                        </div>
                    </form>

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Associate details</th>
                                <th scope="col">Appraisal type</th>
                                <th scope="col">Appraisal cycle</th>
                                <th scope="col">Feedback</th>
                                <th scope="col">Scope of improvement</th>
                                <th scope="col">IPF (Individual Performance Factor)</th>
                            </tr>
                        </thead>
                        <?php if (@$appraisaltype > 0) {
                        ?>
                            <tbody>
                                <tr>

                                    <td id="cw1" style="line-height: 1.7;"><b><?php echo $fullname ?></b><br>
                                        Associate ID - <b><?php echo $associatenumber ?></b><br>
                                        <span style="line-height: 3;"><?php echo $role ?></span>
                                    </td>
                                    <td style="line-height: 1.7;"><?php echo $appraisaltype ?></td>
                                    <td id="cw" style="line-height: 1.7;"><?php echo $effectivestartdate ?> to <?php echo $effectiveenddate ?></td>
                                    <td style="line-height: 1.7;"><?php echo $feedback ?></td>
                                    <td style="line-height: 1.7;"><?php echo $scopeofimprovement ?></td>
                                    <td style="line-height: 1.7;"><?php echo $ipf ?></td>
                                </tr>
                            <?php
                        } else if ($id == "") {
                            ?>
                                <tr>
                                    <td>Please select Appraisal type.</td>
                                </tr>
                            <?php
                        } else {
                            ?>
                                <tr>
                                    <td>No record found for <?php echo $id ?></td>
                                </tr>
                            <?php }
                            ?>
                            </tbody>
                    </table>


            </div>
            </div>
            </div>
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