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


if ($filterstatus != 'Active') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
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
                <!-- <div class="alert alert-info" role="alert" style="text-align: -webkit-center;">Please download and install the Kalpurush.ttf file given here in the portal before opening or editing the question template of the State Module.
                </div> -->

                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Exam Description</th>
                                <th scope="col">Question paper Template</th>
                                <!-- <th scope="col">Download Bengali font</th> -->
                                <th scope="col">Info</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id=cw>1st Term Examination (QT1/2022)- Hybrid, Max. Time - 2 hours<br><br>


                                    <table style="border:1px solid black; width:80%">
                                        <tr style="border:1px solid black">
                                            <th style="border:1px solid black">Online-LG4S1-9</th>
                                            <th style="border:1px solid black">Offline</th>
                                        </tr>
                                        <tr>
                                            <td style="border:1px solid black">Written exam - 50</td>
                                            <td style="border:1px solid black">Written exam - 30<br>Project - 20</td>
                                        </tr>
                                    </table>
                                    <br>
                                    The written test will be descriptive as per the Board/School exam pattern. The concerned subject teacher will set the question paper. You can download question paper template from your profile. Please keep the same format (Font style, size, color, text alignment etc.) as shared in your profile.<br><br>
                                    <!--<img src="../images/qp.png" width=70%><br><br>-->

                                    After setting the question paper, teachers are requested to email the editable version (.docx file) of the question paper at&nbsp;<span class="noticea"><a href="mailto: info@rssi.in">info@rssi.in</a></span>.
                                </td>

                                <td><span class="noticea">
                                        <!-- <a href="https://drive.google.com/uc?id=1RMjfSsFNPaDQixvNPsO2ee5xgraRzhVt&export=download" title="Download" target="_blank">State module</a><br> -->
                                        <a href="https://drive.google.com/uc?id=15yFD54mAJQzZN0l1sK3EuMRsCYSKR5ec&export=download" title="Download" target="_blank">National module</a><br></td>

                                <!-- <td style="line-height: 2"><span class="noticea"><a href="https://drive.google.com/uc?id=15L-QSLklMZ1k3aWIdhPqsAeNhJL7ggFz&export=download" title="Download" target="_blank">Kalpurush.ttf</a></span></td> -->

                                <td style="line-height: 2"><span class="noticea">
                                        <a href="https://drive.google.com/file/d/1S5iE7baHY2i49EZgRSfVRLhnQGO7QGYZ/view" target="_blank">Question paper submission date</i></a><br>

                                        <a href="https://drive.google.com/file/d/1Q_pWvJCGxz1U5YbSL1fevzp801pX9FOy/view" target="_blank">Examination Schedule</i></a><br>

                                        <!-- <a href="javascript:void(0)" target="_self">Invigilation duty list</i></a><br> -->
                                        <a href="https://drive.google.com/file/d/1wrTxXQLzPPuJr0T8BnyfkNjkM00JpzLY/view" target="_blank">Invigilation duty list</i></a><br>

                                        <a href="https://drive.google.com/file/d/1Dr3SOmKUPe7gjaQg_V1Y7VAwufrOWJdj/view" target="_blank">Guidelines for Exam invigilator</i></a><br>
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
                                <th scope="col">Examination Results</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id=cw1><span class="noticea"><a href="question.php">Question paper</i></a></span></td>
                                <td style="line-height: 2;"><span class=noticea><a href="https://docs.google.com/spreadsheets/d/1d1dfSWWh_aM7Eq2rZc3ZxXJ2uMpqKfchy0ciNSF4KxU/edit?usp=sharing" target=_blank>Homework, QT Exam</a><br><a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vS7xMLLw8oFxfw9x8PSjCyB_-D-vdE_zVfgeHqXsE74QIdoEh60jiybeKVNT9XeBFDXqZB0Fe0cVmrQ/pubhtml?gid=1995146093&single=true" target=_blank>Online Exam</a></span></td>
                                <td><span class="noticea"><a href="https://docs.google.com/spreadsheets/d/1mjVN9VET3_ToFGDWRSSl7dAZO1sH1kNXgI66qPWfco8/edit?usp=sharing" target="_blank">Grade Summary Sheet</a></span></td>
                                <td><span class="noticea"><a href="../result.php" target="_blank">Results</a></span></td>
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
