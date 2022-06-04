<?php
session_start();
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}
?>
<?php
include("member_data.php");
?>
<?php
include("database.php");
@$type = $_GET['get_id'];
@$year = $_GET['get_year'];
$view_users_query = "select * from myappraisal_myappraisal WHERE associatenumber='$user_check' AND appraisaltype='$type' AND filter='$year'";
$run = pg_query($con, $view_users_query);

while ($row = pg_fetch_array($run)) {
    $appraisaltype = $row[0];
    $associatenumber = $row[1];
    $fullname = $row[2];
    $effectivestartdate = $row[3];
    $effectiveenddate = $row[4];
    $rolea = $row[5];
    $feedback = $row[6];
    $scopeofimprovement = $row[7];
    $ipfc = $row[8];
    $flag = $row[9];
    $filter = $row[10];

    $view_users_queryy = "select * from ipfsubmission WHERE memberid2='$user_check'"; //select query for viewing users.  
    $runn = pg_query($con, $view_users_queryy); //here run the sql query.  

    while ($row = pg_fetch_array($runn)) //while look to fetch the result and store in a array $row.  
    {

        $timestamp = $row[0];
        $memberid2 = $row[1];
        $membername2 = $row[2];
        $ipf = $row[3];
        $ipfinitiate = $row[4];
        $status2 = $row[5];
        $respondedon = $row[6];
        $ipfstatus = $row[7];
        $closedon = $row[8];
        $id = $row[9]


?>
<?php }
} ?>


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

        #footer {
            position: fixed;
            bottom: 0;
            width: 81%;
            background-color: #f9f9f9;
        }

        #close {
            float: right;
            padding: 2px 5px;
            background: #ccc;
        }

        #close:hover {
            float: right;
            padding: 2px 5px;
            background: #ccc;
            color: #fff;
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

                        <?php if (@$ipfstatus == null && @$status2 == null && @$ipfinitiate == 'initiated' && @$type == strtok(@$ipf,  '(') && @$year == explode(')', (explode('(', $ipf)[1]))[0]) { ?>
                            <a href="my_appraisal_workflow.php?get_aid=<?php echo $year ?>" style="text-decoration: none;" title="Workflow">
                                <p class="label label-danger">In Progress</p>
                            </a>
                        <?php } ?>

                        <?php if (@$ipfstatus == null && @$status2 == 'IPF Accepted' && @$ipfinitiate == 'initiated' && @$type == strtok(@$ipf,  '(') && @$year == explode(')', (explode('(', $ipf)[1]))[0]) { ?>
                            <a href="my_appraisal_workflow.php?get_aid=<?php echo $year ?>" style="text-decoration: none;" title="Workflow">
                                <p class="label label-success"><?php echo $status2 ?></p>
                            </a>
                        <?php } ?>

                        <?php if (@$ipfstatus == null && @$status2 == 'IPF Rejected' && @$ipfinitiate == 'initiated' && @$type == strtok(@$ipf,  '(') && @$year == explode(')', (explode('(', $ipf)[1]))[0]) { ?>
                            <a href="my_appraisal_workflow.php?get_aid=<?php echo $year ?>" style="text-decoration: none;" title="Workflow">
                                <p class="label label-danger"><?php echo $status2 ?></p>
                            </a>
                        <?php } ?>


                        <?php if (@$ipfstatus != null && @$status2 != null && @$ipfinitiate == 'initiated' && @$type == strtok(@$ipf,  '(') && @$year == explode(')', (explode('(', $ipf)[1]))[0]) { ?>
                            <a href="my_appraisal_workflow.php?get_aid=<?php echo $year ?>" style="text-decoration: none;" title="Workflow">
                                <p class="label label-success">Process Closed</p>
                            </a>
                        <?php } ?>

                    </div>


                    <section class="box" style="padding: 2%;">
                        <form action="" method="GET">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">
                                    <select name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type" required>
                                        <?php if ($type == null) { ?>
                                            <option value="" hidden selected>Select Appraisal type</option>
                                        <?php
                                        } else { ?>
                                            <option hidden selected><?php echo $type ?></option>
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
                                        <option>2022-2023</option>
                                        <option>2021-2022</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                            </div>
                        </form>

                        <table class="table">
                            <thead style="font-size: 12px;">
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
                                            <span><?php echo $rolea ?>
                                        </td>
                                        <td><?php echo $appraisaltype ?></td>
                                        <td id="cw"><?php echo $effectivestartdate ?> to <?php echo $effectiveenddate ?></td>
                                        <td><?php echo $ipfc ?></td>
                                    </tr>
                                </tbody>
                        </table>



                        <table class="table">
                            <thead style="font-size: 12px;">
                                <tr>
                                    <th scope="col" id="cw3">Feedback<br>(5- Very Satisfied, 4- Satisfied, 3- Neutral, 2- Unsatisfied, 1- Very Unsatisfied)</th>
                                    <th scope="col">Remarks<br>(Based on general observations, system-generated reports, and student feedback)</th>
                                </tr>
                            </thead>
                            <thead style="font-size: 12px;">
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
                            } else if (@$type == "") {
                            ?>
                                <tr>
                                    <td colspan="3">Please select Filter value.</td>
                                </tr>
                            <?php
                            } else {
                            ?>
                                <tr>
                                    <td>No record found for <?php echo $type ?>&nbsp;<?php echo $year ?></td>
                                </tr>
                            <?php }
                            ?>
                            </tbody>
                        </table>


                </div>
            </div>


            <?php if (@$status2 == null && @$appraisaltype != null && @$type == strtok(@$ipf,  '(') && @$year == explode(')', (explode('(', $ipf)[1]))[0]) { ?>
                <div id="footer">
                    <form name="ipfsubmission" action="#" method="POST" onsubmit="myFunction()">
                        <span id='close'>x</span>
                        <input type="hidden" name="form-type" type="text" value="ipfsubmission">
                        <input type="hidden" type="text" name="status2" id="count2" value="" readonly required>
                        <input type="hidden" type="text" name="ipfid" id="ipfid" value="<?php echo $id ?>" readonly required>
                        <p style="display: inline-block; word-break: break-word; margin-left:5%; margin-top:2%">If you are not satisfied with your appraisal discussion and IPF then you can reject your IPF. In case of rejection, another round of discussion will be set up with the concerned team.</p>
                        <div style="margin-left:5%;">
                            <button type="submit" id="yes" class="btn btn-success btn-sm close-button2" style="white-space:normal !important;word-wrap:break-word"><i class="fas fa-check" style="font-size: 17px;"></i> Accept</button>
                            <button type="submit" id="no" class="btn btn-danger btn-sm close-button2" style="white-space:normal !important;word-wrap:break-word;"><i class="fas fa-times" style="font-size: 17px;"></i> Reject</button>
                        </div><br>
                    </form>
                </div>
            <?php } ?>
            <script>
                $('#yes').click(function() {
                    $('#count2').val('IPF Accepted');
                });

                $('#no').click(function() {
                    $('#count2').val('IPF Rejected');
                });
            </script>
            <script>
                function myFunction() {
                    alert("Your response has been recorded.");
                }
            </script>
            <script>
                const scriptURL = 'payment-api.php'
                const form = document.forms['ipfsubmission']

                form.addEventListener('submit', e => {
                    e.preventDefault()
                    fetch(scriptURL, {
                            method: 'POST',
                            body: new FormData(document.forms['ipfsubmission'])
                        })
                        .then(response => console.log('Success!', response))
                        .catch(error => console.error('Error!', error.message))
                })
            </script>
            <script>
                $(document).ready(function() {

                    $('.close-button2').click(function(e) {

                        $('#footer').delay(10).fadeOut(700);
                        e.stopPropagation();
                    });
                });
            </script>
            <script>
                window.onload = function() {
                    document.getElementById('close').onclick = function() {
                        this.parentNode.parentNode.parentNode
                            .removeChild(this.parentNode.parentNode);
                        return false;
                    };
                };
            </script>


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