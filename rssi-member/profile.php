<?php
session_start();
// Storing Session
$user_check = $_SESSION['aid'];

if (!$_SESSION['aid']) {

  header("Location: unauth.php"); //redirect to the login page to secure the welcome page without login access.  
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
    <title>My Profile</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="/img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <link rel="stylesheet" href="/rssi-student/style.css">
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <!------ Include the above in your HEAD tag ---------->

</head>

<body>
    <?php $profile_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Associate Details</th>
                                <th scope="col">Date of Join</th>
                                <th scope="col">Association Status</th>
                                <th scope="col">Badge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>

                                <td>Name - <b><?php echo $Fullname ?></b><br>Associate ID - <b><?php echo $AssociateNumber ?></b></b><br><br><b><?php echo $Gender ?> (<?php echo $Age ?> Years)</b></td>
                                <td><?php echo $DOJ ?></td> 
                                <td><?php echo $Astatus ?><br><br><?php echo $Remarks ?></td>
                                <td><?php echo $Badge ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Date of Birth</th>
                                <th scope="col">National Identifier</th>
                                <th scope="col">Last 4 digits of Identifier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $DateofBirth ?></td>
                                <td><?php echo $NationalIdentifier ?></td>
                                <td><?php echo $Identifier ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Designation</th>
                                <th scope="col">Base Branch</th>
                                <th scope="col">Deputed Branch</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $Position ?></td>    
                                <td><?php echo $BaseBranch ?></td>
                                <td>Lucknow, UP</td>
                            </tr>
                        </tbody>
                    </table>

                    

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Current Address</th>
                                <th scope="col">Permanent Address</th>
                                <th scope="col">Contact/Email Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $CurrentAddress ?></td>
                                <td><?php echo $PermanentAddress ?></td>
                                <td style="line-height: 1.5;"><?php echo $Phone ?><br><?php echo $Email ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Language Details</th>
                                <th scope="col">Work Experience</th>
                                <th scope="col">Account Approved by</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="line-height: 2;">English - <?php echo $LanguageDetailsEnglish ?><br>हिंदी - <?php echo $LanguageDetailsHindi ?></td>
                                <td><?php echo $WorkExperience ?></td>
                                <td><?php echo $ApprovedBy ?></td>
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