<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("sid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

if ($feesflag == 'd') {

    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "profile.php";';
    echo '</script>';
}
?>

<!DOCTYPE html>
<html>

<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
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
            policyLink: 'https://www.rssi.in/disclaimer'
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

                

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Exam Description</th>
                                <th scope="col">Info</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td id="cw">
                                    <table style="border:1px solid black; width:80%">
                                        <tr style="border:1px solid black">
                                            <th style="border:1px solid black">Name of exam</th>
                                            <th style="border:1px solid black">Month</th>
                                            <th style="border:1px solid black">Max. Marks</th>
                                        </tr>
                                        <tr>
                                            <td style="border:1px solid black">First Term Exam</td>
                                            <td style="border:1px solid black">July</td>
                                            <td style="border:1px solid black">50</td>
                                        </tr>
                                        <tr>
                                            <td style="border:1px solid black">Half Yearly Exam</td>
                                            <td style="border:1px solid black">November</td>
                                            <td style="border:1px solid black">50</td>
                                        </tr>
                                        <tr>
                                            <td style="border:1px solid black">Annual Exam</td>
                                            <td style="border:1px solid black">March</td>
                                            <td style="border:1px solid black">100</td>
                                        </tr>
                                    </table>
                                    <br>
                                    The written test will be descriptive as per the Board/School exam pattern. The concerned subject teacher will set the question paper.
                                </td>

                                <td style="line-height: 2"><span class="noticea">
                                        <!-- <a href="https://drive.google.com/file/d/1Q_pWvJCGxz1U5YbSL1fevzp801pX9FOy/view" target="_blank">Examination Schedule</i></a><br> -->
                                        <a href="https://drive.google.com/file/d/1-vF45CbqRnWX1IzvbHTC9d5iPBVN4jix/view" target="_blank">Online Exams: Essential Guidelines for Students</i></a>
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>


                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Download</th>
                                <th scope="col">Question Portal</th>
                                <th scope="col">Answersheet Submission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <!--<td><span class="noticea"><a href="javascript:void(0)" target="_self">Download coverpage</i></a></span></td>-->
                                <td><span class="noticea">
                                        <!-- <a href="https://drive.google.com/file/d/1B0sONvc9bb5igUOo1gVO_INfIod9sfIW/view" title="Download" target="_blank">State module</a><br> -->
                                        <a href="https://drive.google.com/file/d/184NK-U45sxlPMTP35JH_YAdAa38gcBVb/view" title="Cover page" target="_blank">Answer Book Front Page</a><br></td>
                                <td><span class="noticea"><a href="question.php">Question Papers</i></a></span></td>
                                <td><span class="noticea"><a href="https://docs.google.com/forms/d/e/1FAIpQLSepC8KPD0l0jblstx38F8OUGKZhCKKGUFPZx685wLDu6hsoqw/viewform?usp=pp_url&entry.77886097=<?php echo $studentname ?>/<?php echo $student_id ?>&entry.547244582=<?php echo $category ?>&entry.1683740731=<?php echo $class ?>" target="_blank">Submission Form</button></i></a></span></td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>
        </section>
    </section>
</body>

</html>