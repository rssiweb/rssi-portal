<?php
session_start(); //session starts here  

?>

<html>

<head lang="en">
    <meta charset="UTF-8">
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <title>Login</title>
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
                        <form role="form" autocomplete="off" method="post" action="index.php">
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
</body>

</html>

<?php

include("database.php");

if (isset($_POST['login'])) {
    $Student_ID = $_POST['sid'];
    $colors = $_POST['pass'];

    $check_user = "select * from studentdata WHERE Student_ID='$Student_ID'AND colors='$colors'";

    $run = pg_query($con, $check_user);

    if (pg_num_rows($run)) {
        echo "<script>window.open('home.php','_self')</script>";

        $_SESSION['sid'] = $Student_ID; //here session is used and value of $user_email store in $_SESSION. 

    } else {?>
        <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4" style="text-align: center;">
            <span style="color:red">Error: Login failed. Please enter valid credentials.</span>
            </div></div></div>
        
        
        <?php }
}
?>