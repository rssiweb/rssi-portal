<?php
session_start(); //session starts here  

?>

<html>

<head lang="en">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="/img/favicon.ico" type="image/x-icon" />
    <title>My Profile</title>
</head>
<style>
    .login-panel {
        margin-top: 150px;
    }
    .x-btn:focus, .button:focus, [type="submit"]:focus {
   outline: none;
}
</style>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-success">
                    <div class="panel-heading">
                        <h3 class="panel-title">Sign In</h3>
                    </div>
                    <div class="panel-body">
                        <form role="form" method="post" action="index.php">
                            <fieldset>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Student ID" name="sid" type="text" autofocus required>
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Password" name="pass" type="password" value="" required>
                                </div>
                                <input class="btn btn-lg btn-success btn-block" type="submit" value="Login" name="login">

                                <!-- Change this to a button or input when using this as a form -->
                                <!--  <a href="index.html" class="btn btn-lg btn-success btn-block">Login</a> -->
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    </script>
    <!--protected by reCAPTCHA-->
<script src="https://www.google.com/recaptcha/api.js?render=6LfJRc0aAAAAAEhNPCD7ju6si7J4qRUCBSN_8RsL"></script>
    <script>
        function onClick(e) {
            e.preventDefault();
            grecaptcha.ready(function() {
                grecaptcha.execute('6LfJRc0aAAAAAEhNPCD7ju6si7J4qRUCBSN_8RsL', {
                    action: 'submit'
                }).then(function(token) {
                    // Add your logic to submit to your backend server here.
                });
            });
        }
    </script>
</body>

</html>

<?php

include("database.php");

if (isset($_POST['login'])) {
    $student_id = strtoupper($_POST['sid']);
    $colors = $_POST['pass'];

    $check_user = "select * from rssimyprofile_student WHERE student_id='$student_id'AND colors='$colors'";

    $run = pg_query($con, $check_user);

    if (pg_num_rows($run)) {
        echo "<script>window.open('home.php','_self')</script>";

        $_SESSION['sid'] = $student_id; //here session is used and value of $user_email store in $_SESSION.

        $row = pg_fetch_row($run);
        $filterstatus= $row[27];

       $_SESSION['filterstatus'] = $filterstatus;

    } else {?>
        <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4" style="text-align: center;">
            <span style="color:red">Error: Login failed. Please enter valid credentials.</span>
            </div></div></div>
        
        
        <?php }
}
?>