<?php
session_start();
// Storing Session
$user_check = $_SESSION['sid'];

if (!$_SESSION['sid']) {

    header("Location: index.php"); //redirect to the login page to secure the welcome page without login access.  
}
?>
<?php
include("student_data.php");
?>
<?php  
        include("database.php");  
        $view_users_query="select * from result_database_result WHERE studentid='$user_check'";//select query for viewing users.  
        $run=pg_query($con,$view_users_query);//here run the sql query.  
  
        while($row=pg_fetch_array($run))//while look to fetch the result and store in a array $row.  
        { 
            $name=$row[0];
            $studentid=$row[1];
            $category=$row[2];
            $class=$row[3];
            $dob=$row[4];
            $filename=$row[5];
            $result=$row[6];
            $examname=$row[7];
            $year=$row[8];
            $__hevo_id=$row[9];
            $__hevo__ingested_at=$row[10];
            $__hevo__marked_deleted=$row[11]                       
?>     
<?php }?>    


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
    <link rel="shortcut icon" href="/img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include 'style.css'; ?>
    </style>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <!------ Include the above in your HEAD tag ---------->

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
                <div class=col style="text-align: right;">Last synced: <?php echo $lastupdatedon ?></div>
                <section class="box" style="padding: 2%;">

                    <!--<div class="container">
                        <div class="row" style="background-color: rgb(255, 245, 194);height: 110%; padding-top: 0; padding-bottom: 1.5%;  padding-left: 1.5%">
                            <div class=col2ass>
                                <label for="name">Student ID<span style="color: #F2545F"></span>&nbsp;</label><input type="text" disabled="disabled" id="name" name="name" value="<?php echo $student_id ?>" minlength="9" maxlength="12" size="12" style="text-transform:uppercase; background-color:#dddddd">
                            </div>
                            <div class=col2ass>
                                <label for="ename">Date of Birth<span style="color: #F2545F"></span>&nbsp;</label><input type="text" disabled="disabled" name="ename" id="ename" value="<?php echo $dateofbirth ?>" minlength="10" maxlength="10" size="12" style="text-transform:uppercase;background-color:#dddddd">
                            </div>
                            <div class=col2ass>
                                <label for="name1">Academic year<span style="color: #F2545F"></span>&nbsp;</label>
                                <select name="name1" id="name1">
                                    <option selected>--</option>
                                    <option>2021-2022</option>
                                    <option>2020-2021</option>
                                </select>
                            </div>

                            <div class="col2">
                                <button type="button" class="exam_btn" onclick="loaddata()"><i class="fas fa-search"></i>
                                    search</button>
                            </div>


                        </div>
                    </div>-->

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