<?php
session_start();
// Storing Session
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;  
} else if ($_SESSION['filterstatus'] != 'Active') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>

<?php
include("member_data.php");
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
        width: 60%;
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
                                <th scope="col">Question paper Template</th>
                                <th scope="col">Info</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>QT1/2021</td>
                                <td id=cw>Descriptive written exam - Online, Full marks-50, Max. Time - 2 hours<br><br>

                                    The written test will be descriptive as per the Board/School exam pattern. The concerned subject teacher will set the question paper. You can download question paper template from your profile. Please keep the same format (Font style, size, color, text alignment etc.) as shared in your profile.<br><br>

                                    After setting the question paper, teachers are requested to email the editable version (.docx file) of the question paper at&nbsp;<span class="noticet"><a href="mailto: info@rssi.in">info@rssi.in</a></span>.</td>

                                <td><span class="noticet"><a href="https://drive.google.com/file/d/1tRelLLSOCxjC1TmbkdU4p2oSQ4RClAjs/view?usp=sharing" title="Download" target="_blank">Download</a></span></td>

                                <td style="line-height: 2"><span class="noticet">
                                        <a href="https://drive.google.com/file/d/15cgBw0nOYKKzY_LHZ7U6u-rj4AxWxE8O/view" target="_blank">Question paper submission date</i></a><br>

                                        <a href="https://drive.google.com/file/d/1w580k9cXeJB3XTvh4i9E83aZhraxGDmz/view" target="_blank">Examination Schedule</i></a><br>

                                        <a href="https://drive.google.com/file/d/1RR5MaWwSpogiRWjRK-REh-hXKfAbvttm/view" target="_blank">Invigilation duty list</i></a><br>

                                        <a href="https://drive.google.com/file/d/1Dr3SOmKUPe7gjaQg_V1Y7VAwufrOWJdj/view" target="_blank">Guidelines for Exam invigilator</i></a>
                                        <a href="https://drive.google.com/file/d/13cH8Rd4aPYHPe0ltzQzQDNFAH1GRTruY/view" target="_blank">Examiner User Guide</i></a>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Question paper&nbsp;<i class="far fa-question-circle" title="The submitted question paper will appear on the portal after 24 hours of submission."></i></th>
                                <th scope="col">Evaluation Path</th>
                                <th scope="col">Marks upload</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id=cw1><span class="noticet"><a href="question.php">Question paper</i></a></span></td>
                                <td style="line-height: 2;"><?php echo $evaluationpath ?></td>
                                <td><span class="noticet"><a href="https://docs.google.com/spreadsheets/d/1mjVN9VET3_ToFGDWRSSl7dAZO1sH1kNXgI66qPWfco8/edit?usp=sharing" target="_blank">Grade Summary Sheet</a></span></td>
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