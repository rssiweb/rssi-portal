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
    <title>Applications</title>
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

<body>
    <?php $application_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Applications</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo $MyDocument ?></td>
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