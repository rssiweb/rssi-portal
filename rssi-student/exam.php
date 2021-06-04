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

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Classroom</title>
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
        width: 50%;
    }

    #cw1 {
        width: 25%;
    }

    #cw2 {
        width: 25%;
    }

    #cw3 {
        width: 20%;
    }
</style>

<body>
    <?php $exam_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class=col style="text-align: right;">Last synced: <?php echo $lastupdatedon ?></div>
                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Exam Name</th>
                                <th scope="col">Exam Description</th>
                                <th scope="col">Exam Schedule</th>
                                <th scope="col">Exam portal</th>
                                <th scope="col">Upload Answersheet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>QT1/2021</td>
                                <td id=cw>Descriptive written exam - Online, Full marks-50, Max. Time - 2 hours<br><br>

                                    The written test will be descriptive as per the Board/School exam pattern. The concerned subject teacher will set the question paper.<br><br></td>

                                <td><span class="noticet"><a href="https://drive.google.com/file/d/1w580k9cXeJB3XTvh4i9E83aZhraxGDmz/view" target="_blank">Click Here</i></a></span></td>

                                <td><span class="noticet"><a href="https://www.rssi.in/online-exam" target="_blank">Click Here</i></a></span></td>
                                <td><span class="noticet"><a href="https://docs.google.com/forms/d/e/1FAIpQLSepC8KPD0l0jblstx38F8OUGKZhCKKGUFPZx685wLDu6hsoqw/viewform?usp=pp_url&entry.77886097=<?php echo $studentname ?>/<?php echo $student_id ?>&entry.547244582=<?php echo $category ?>&entry.1683740731=<?php echo $class ?>" target="_blank">Click here to upload</i></a></span></td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Result portal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <embed src="https://www.rssi.in/result-portal#name=<?php echo $student_id ?>&ename=<?php echo $dateofbirth ?>&name1=2021-2022" style="width:100%; height: 300px;">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>

            <div class="clearfix"></div>
            <!--**************clearfix**************

           <div class="col-md-12">
                <section class="box">cccccccccccee33</section>
            </div>-->

        </section>
    </section>
</body>

</html>