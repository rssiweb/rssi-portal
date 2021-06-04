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
    <link rel="shortcut icon" href="/img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <link rel="stylesheet" href="../rssi-student/style.css">
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <!------ Include the above in your HEAD tag ---------->

</head>
<style>
        @media (max-width:767px) {
            #cw, #cw1, #cw2, #cw3 {
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
                                <th scope="col">Question paper Template</th>
                                <th scope="col">Date Sheet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>QT1/2021</td>
                                <td id=cw>Descriptive written exam - Online, Full marks-50, Max. Time - 2 hours<br><br>

                                The written test will be descriptive as per the Board/School exam pattern. The concerned subject teacher will set the question paper. You can download question paper template from your profile. Please keep the same format (Font style, size, color, text alignment etc.) as shared in your profile.<br><br>

                                    After setting the question paper, teachers are requested to email the editable version (.docx file) of the question paper at&nbsp;<span class="noticet"><a href = "mailto: info@rssi.in">info@rssi.in</a></span>.</td>

                                <td style="line-height: 2;"><span class="noticet"><a href="https://drive.google.com/file/d/1tRelLLSOCxjC1TmbkdU4p2oSQ4RClAjs/view?usp=sharing" title="Download" target="_blank">Download</a></span></td>

                                <td><span class="noticet"><a href="https://drive.google.com/file/d/15cgBw0nOYKKzY_LHZ7U6u-rj4AxWxE8O/view" target="_blank">Question paper submission date</i></a></span><br>

                                <span class="noticet"><a href="https://drive.google.com/file/d/1w580k9cXeJB3XTvh4i9E83aZhraxGDmz/view" target="_blank">Examination Schedule</i></a></span></td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Evaluation Path</th>
                                <th scope="col">Marks upload</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
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