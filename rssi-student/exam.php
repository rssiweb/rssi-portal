<?php
session_start();
// Storing Session
$user_check = $_SESSION['sid'];

if (!$_SESSION['sid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
} else if ($_SESSION['feesflag'] == 'd') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "profile.php";';
    echo '</script>';
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
    <title>Examination</title>
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

                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Exam Name</th>
                                <th scope="col">Exam Description</th>
                                <th scope="col">Info</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                            <td>QT2/2021</td>
                                <td id=cw>MCQ and Descriptive written exam - Online<br>
                                    Full marks - 30<br>
                                    Max. Time - 1.5 hours<br>
                                    Project - 20 (Internal)<br><br>

                                    The written test will be descriptive as per the Board/School exam pattern. The concerned subject teacher will set the question paper.<br><br></td>

                                <td style="line-height: 2"><span class="noticet">
                                        <a href="https://drive.google.com/file/d/1Q_pWvJCGxz1U5YbSL1fevzp801pX9FOy/view" target="_blank">Examination Schedule</i></a><br>
                                        <a href="https://drive.google.com/file/d/1-vF45CbqRnWX1IzvbHTC9d5iPBVN4jix/view" target="_blank">Guidelines for Student</i></a>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>


                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Answersheet (Cover page)</th>
                                <th scope="col">Question paper</th>
                                <th scope="col">Upload Answersheet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="noticet"><a href="javascript:void(0)" target="_self">Download coverpage</i></a></span></td>
                                <td><span class="noticet"><a href="question.php">Question paper</i></a></span></td>
                                <td><span class="noticet"><a href="https://docs.google.com/forms/d/e/1FAIpQLSepC8KPD0l0jblstx38F8OUGKZhCKKGUFPZx685wLDu6hsoqw/viewform?usp=pp_url&entry.77886097=<?php echo $studentname ?>/<?php echo $student_id ?>&entry.547244582=<?php echo $category ?>&entry.1683740731=<?php echo $class ?>" target="_blank">Upload answersheet</i></a></span></td>
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