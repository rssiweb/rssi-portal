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
    <title>Form</title>
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
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-md-offset-4">
                <div class="login-panel panel panel-info" style="padding: 5%;">
                    <div class="panel-heading">
                        <h3 class="panel-title">Update your subjects</h3>
                    </div>
                    <form name="submit-to-google-sheet" action="" method="POST" onSubmit="alert('Your response has been recorded.');">
                        <br><label for="fname">Name:</label><br>
                        <input type="text" id="fname" name="fname" value="<?php echo $studentname ?>" readonly disable><br>
                        <label for="stid">Student ID:</label><br>
                        <input type="text" id="stid" name="stid" value="<?php echo $student_id ?>" readonly disable><br><br>
                        <label for="sub">Select subjects:</label><br>
                        <input type="checkbox" id="sub1" name="sub1" value="English,">
                        <label for="sub1"> English</label><br>
                        <input type="checkbox" id="sub2" name="sub2" value="Hindi,">
                        <label for="sub2"> Hindi</label><br>
                        <input type="checkbox" id="sub3" name="sub3" value="Bengali,">
                        <label for="sub3"> Bengali</label><br>
                        <input type="checkbox" id="sub4" name="sub4" value="Sanskrit,">
                        <label for="sub4"> Sanskrit</label><br>
                        <input type="checkbox" id="sub5" name="sub5" value="Physics,">
                        <label for="sub5"> Physics</label><br>
                        <input type="checkbox" id="sub6" name="sub6" value="Chemistry,">
                        <label for="sub6"> Chemistry</label><br>
                        <input type="checkbox" id="sub7" name="sub7" value="Mathematics,">
                        <label for="sub7"> Mathematics</label><br>
                        <input type="checkbox" id="sub8" name="sub8" value="Biology,">
                        <label for="sub8"> Biology</label><br>
                        <input type="checkbox" id="sub9" name="sub9" value="Science,">
                        <label for="sub9"> Science</label><br>
                        <input type="checkbox" id="sub10" name="sub10" value="Social Science,">
                        <label for="sub10"> Social Science</label><br>
                        <input type="checkbox" id="sub11" name="sub11" value="Computer,">
                        <label for="sub11"> Computer</label><br>
                        <input type="checkbox" id="sub12" name="sub12" value="GK/Current Affairs,">
                        <label for="sub12"> GK/Current Affairs</label><br>
                        <input type="checkbox" id="sub13" name="sub13" value="Accountancy,">
                        <label for="sub13"> Accountancy</label><br><br>
                        <input onclick='window.location.href="home.php"' type="submit" value="Submit">
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        $('#yes').click(function() {
            $('#count1').val('Agree');
        });

        $('#no').click(function() {
            $('#count1').val('NA');
        });
    </script>
    <script>
        const scriptURL = 'https://script.google.com/macros/s/AKfycby2Ok3NM5WqWbv9cuF36Vx3ueboXsbT4PPiqzK43Cdz0o-OnGM/exec'
        const form = document.forms['submit-to-google-sheet']

        form.addEventListener('submit', e => {
            e.preventDefault()
            fetch(scriptURL, {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => console.log('Success!', response))
                .catch(error => console.error('Error!', error.message))
        })
    </script>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>