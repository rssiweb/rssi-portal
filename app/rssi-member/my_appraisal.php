<?php
session_start();
include("../util/login_util.php");

if(! isLoggedIn("aid")){
    header("Location: index.php");
}
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
} //You are almost there! Your IPF (Individual Performance Factor) will be released on August 14, 2021.
?>
<?php
//session_start();
//include("../util/login_util.php");

//if(! isLoggedIn("aid")){
//    header("Location: index.php");
//}
//$user_check = $_SESSION['aid'];

//if (!$_SESSION['aid']) {

//  $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
//header("Location: index.php");
//exit;  
//} else if ($_SESSION['ipfl'] == '-') {

//echo '<script type="text/javascript">';
//echo 'alert("Your appraisal has been initiated in the system. You will no longer be able to access My appraisal portal.");';
//echo 'window.location.href = "home.php";';
//echo '</script>';
//}
?>
<?php
include("member_data.php");
?>
<?php
include("database.php");
@$id = $_POST['get_id'];
@$year = $_POST['get_year'];
$view_users_query = "select * from myappraisal_myappraisal WHERE associatenumber='$user_check' AND appraisaltype='$id' AND filter='$year'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
    $appraisaltype = $row[0];
    $associatenumber = $row[1];
    $fullname = $row[2];
    $effectivestartdate = $row[3];
    $effectiveenddate = $row[4];
    $role = $row[5];
    $feedback = $row[6];
    $scopeofimprovement = $row[7];
    $ipf = $row[8];
    $flag = $row[9];
    $filter = $row[10];
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
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
            width: 15%;
        }

        #cw1 {
            width: 20%;
        }

        #cw2 {
            width: 25%;
        }

        #cw3 {
            width: 60%;
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

            <div class="row">
                    <div class="col" style="display: inline-block; width:99%; text-align:right">
                    Academic year: <?php echo @$year ?><br>
                    <?php if (@$flag == "R") { ?>
                        <p class="label label-danger">Reviewer Evaluation in Progress</p>
                                    <?php
                                    } else if (@$flag == "C") { ?>
                                        <p class="label label-success">Process Closed</p>
                                    <?php }
                                    else { ?>
                                    <?php }
                                    ?>
                    </div>


                <section class="box" style="padding: 2%;">
                    <form action="" method="POST">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <select name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type" required>
                                    <?php if ($id == null) { ?>
                                        <option value="" hidden selected>Select Appraisal type</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $id ?></option>
                                    <?php }
                                    ?>
                                    <!--<option>Quarterly 2/2021</option>-->
                                    <option>Quarterly</option>
                                    <option>Annual</option>
                                    <option>Project end</option>
                                </select>

                                <select name="get_year" class="form-control" style="width:max-content; display:inline-block" placeholder="Year" required>
                                    <?php if ($year == null) { ?>
                                        <option value="" hidden selected>Select Year</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $year ?></option>
                                    <?php }
                                    ?>
                                    <!--<option>Quarterly 2/2021</option>-->
                                    <option>2021-2022</option>
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
                                <th scope="col" id="cw2">Associate details</th>
                                <th scope="col">Appraisal type</th>
                                <th scope="col" id="cw1">Appraisal cycle</th>
                                <th scope="col">IPF (Individual Performance Factor)/5</th>
                            </tr>
                        </thead>
                        <?php if (@$appraisaltype > 0 and @$flag != "R") {
                        ?>
                            <tbody>
                                <tr>

                                    <td id="cw1"><b><?php echo $fullname ?> (<?php echo $associatenumber ?>)</b><br><br>
                                    <span><?php echo $role ?>
                                    </td>
                                    <td><?php echo $appraisaltype ?></td>
                                    <td id="cw"><?php echo $effectivestartdate ?> to <?php echo $effectiveenddate ?></td>
                                    <td><?php echo $ipf ?></td>
                                </tr>
                            </tbody>
                    </table>



                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col" id="cw3">Feedback<br>(5- Very Satisfied, 4- Satisfied, 3- Neutral, 2- Unsatisfied, 1- Very Unsatisfied)</th>
                                <th scope="col">Remarks<br>(Based on general observations, system-generated reports, and student feedback)</th>
                            </tr>
                        </thead>
                        <thead>
                            <tr>
                                <td style="line-height: 1.7;"><?php echo $feedback ?></td>
                                <td style="line-height: 1.7;"><?php echo $scopeofimprovement ?></td>
                            </tr>
                        <?php
                        } else if (@$flag == "R") {
                        ?>
                            <tr>
                                <td colspan="3">Your appraisal has been initiated in the system. You can check your appraisal details once your IPF is released.</td>
                            </tr>
                        <?php
                        } else if (@$id=="") {
                            ?>
                                <tr>
                                    <td colspan="3">Please select Filter value.</td>
                                </tr>
                            <?php
                            } else {
                        ?>
                            <tr>
                                <td>No record found for <?php echo $id ?>&nbsp;<?php echo $year ?></td>
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