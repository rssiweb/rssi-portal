<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("sid")) {
    header("Location: index.php");
}
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

<!DOCTYPE html>
<html>

<head>
<meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>My Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style><?php include '../css/style.css'; ?></style>
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

</head>

<body>
    <?php $profile_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                
                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Student Details</th>
                                <th scope="col">Profile Status</th>
                                <th scope="col">Badge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>

                                <td style="line-height: 1.7;"><b><?php echo $studentname ?></b><br>Student ID - <b><?php echo $student_id ?></b>, Roll No - <b><?php echo $roll_number ?></b><br>
                                <span style="line-height: 3;"><?php echo $gender ?> (<?php echo $age ?> Years)</span></td>
                                <td><?php echo $profilestatus ?><br><br><?php echo $remarks1 ?></td>
                                <td><?php echo @$badge ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Admission Date</th>
                                <th scope="col">Preferred Branch of RSSI</th>
                                <th scope="col">Class/Category</th>
                                <th scope="col">Date of Birth</th>
                                <th scope="col">Student Aadhaar</th>
                                <th scope="col">Aadhaar Card</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $doa ?></td>
                                <td><?php echo $preferredbranch ?></td>
                                <td><?php echo $class ?>/<?php echo $category ?></td>
                                <td><?php echo $dateofbirth ?></td>
                                <td><?php echo $studentaadhar ?></td>
                                <td><iframe sandbox="allow-forms allow-modals allow-orientation-lock allow-pointer-lock allow-presentation allow-same-origin allow-scripts allow-top-navigation allow-top-navigation-by-user-activation" src="https://drive.google.com/file/d/<?php echo substr($upload_aadhar_card, strpos($upload_aadhar_card, "=") + 1) ?>/preview" width="300px" height="200px" /></iframe></td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Guardian's Name</th>
                                <th scope="col">Guardian Aadhaar</th>
                                <th scope="col">Postal Address</th>
                                <th scope="col">Contact/Email Address</th>
                                <th scope="col">Family monthly income</th>
                                <th scope="col">Total number of family members</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $guardiansname ?> - <?php echo $relationwithstudent ?></td>
                                <td><?php echo $guardianaadhar ?></td>
                                <td><?php echo $postaladdress ?></td>
                                <td style="line-height: 1.5;"><?php echo $contact ?><br><?php echo $emailaddress ?></td>
                                <td><?php echo $familymonthlyincome ?></td>
                                <td><?php echo $totalnumberoffamilymembers ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">School Admission Required</th>
                                <th scope="col">Name Of The Subjects</th>
                                <th scope="col">Name Of The School</th>
                                <th scope="col">Name Of The Board</th>
                                <th scope="col">Medium</th>
                                <th scope="col">Admission form</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $schooladmissionrequired ?></td>
                                <td><?php echo $nameofthesubjects ?></td>
                                <td><?php echo $nameoftheschool ?></td>
                                <td><?php echo $nameoftheboard ?></td>
                                <td><?php echo $medium ?></td>
                                <td><span class="noticea"><a href="<?php echo $profile ?>" target="_blank"><?php echo $filename ?></a></span></td>
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