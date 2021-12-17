<?php
session_start();
// Storing Session
$user_check = $_SESSION['sid'];

if (!$_SESSION['sid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
?>
<?php
include("student_data.php");
?>
<?php
include("database.php");
@$id = $_POST['get_id'];
$view_users_query = "select * from result_database_result WHERE studentid='$user_check' AND examname='$id'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
    $filename = $row[0];
    $name = $row[1];
    $studentid = $row[2];
    $category = $row[3];
    $class = $row[4];
    $dob = $row[5];
    $result = $row[6];
    $examname = $row[7];
    $year = $row[8];
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
    <title>Result</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
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
    <?php $result_active = 'active'; ?>
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
                                        <option value="" disabled selected hidden>Select Exam name</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $id ?></option>
                                    <?php }
                                    ?>
                                    <option>QT2/2021</option>
                                    <option>QT1/2021</option>
                                    <option>QT3/2021</option>
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
                                <th scope="col">Academic year</th>
                                <th scope="col">Student ID</th>
                                <th scope="col">Student Name</th>
                                <th scope="col">Category</th>
                                <th scope="col">Class</th>
                                <th scope="col">Exam name</th>
                                <th scope="col">Download report card</th>
                            </tr>
                        </thead>
                        <?php if (@$examname > 0) {
                        ?>
                            <tbody>
                                <tr>

                                    <td style="line-height: 1.7;"><?php echo $year ?></td>
                                    <td style="line-height: 1.7;"><?php echo $studentid ?></td>
                                    <td style="line-height: 1.7;"><?php echo $name ?></td>
                                    <td style="line-height: 1.7;"><?php echo $category ?></td>
                                    <td style="line-height: 1.7;"><?php echo $class ?></td>
                                    <td style="line-height: 1.7;"><?php echo $examname ?></td>
                                    <td style="line-height: 1.7;"><span class="noticet"><a href="<?php echo $result ?>" target="_blank"><?php echo $examname ?>/<?php echo $filename ?></a></span></td>
                                </tr>
                            <?php
                        } else if ($id == "") {
                            ?>
                                <tr>
                                    <td>Please select Exam name.</td>
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