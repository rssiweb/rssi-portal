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
    <title>Classroom</title>
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
    <?php $home_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">

                <section class="box" style="padding: 2%;">

                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Class URL</th>
                                <th scope="col">Quick link</th>
                                <th scope="col">Annual Fee</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="line-height: 2;"><?php echo $classurl ?></td>
                                <td style="line-height: 2;"><?php echo $lastlogin ?></td>
                                <td style="line-height: 2;"><?php echo $fees ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <!--<table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Quick link</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="line-height: 2;"><?php echo $lastlogin ?></td>
                            </tr>
                        </tbody>
                    </table>-->
                </section>
            </div>

            <div class="clearfix"></div>
            <!--**************clearfix**************

           <div class="col-md-12">
                <section class="box">cccccccccccee33</section>
            </div>-->

        </section>
    </section>
    <!--**************User confirmation2**************-->
    <!--<?php
    if ($filterstatus == 'Active') {
    ?>

        <div id="thoverX" class="thover pop-up"></div>
        <div id="tpopupX" class="tpopup pop-up">
            <form name="submit-to-google-sheet" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername1" type="text" value="<?php echo $studentname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid1" type="text" value="<?php echo $student_id ?>" readonly>
                <input type="hidden" type="text" name="status1" id="count1" value="" readonly required>
                <p style="white-space:normal !important;word-wrap:break-word;">Hi&nbsp;<?php echo $studentname ?>&nbsp;(<?php echo $student_id ?>), Please confirm whether the subject combination given below is correct.</p>
                <b><?php echo $nameofthesubjects ?></b><br><br>
                <button type="submit" id="yes" class="close-button btn btn-success" style="white-space:normal !important;word-wrap:break-word;">
                    <i class="fas fa-smile" style="font-size:17px" aria-hidden="true"></i>&nbsp;Yes, Correct</button><br><br>
                <button onclick='window.location.href="form.php"' type="submit" id="no" class="close-button btn btn-default" style="white-space:normal !important;word-wrap:break-word;">
                    <i class="far fa-meh" style="font-size:17px" aria-hidden="true"></i>&nbsp;No, I want to change my subject combination.
                </button>
                <br><br>
            </form>
        </div>
        <script>
            $('#yes').click(function() {
                $('#count1').val('Yes, Correct');
            });

            $('#no').click(function() {
                $('#count1').val('No, I want to change my subject combination.');
            });
        </script>
        <script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycbzRMd98T75iCUIe9ZwMYatPiJcmzzmgleL3epY7WwquEyyfRwg/exec'
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
        <script>
            $(document).ready(function() {

                if (Boolean(readCookie('sub'))) {
                    $('.pop-up').hide();
                    $('.pop-up').fadeOut(1000);
                }
                $('.close-button').click(function(e) {

                    $('.pop-up').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("sub", "30 days", 30);
                    //return false;
                });

                function createCookie(name, value, days) {
                    if (days) {
                        var date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                        var expires = "; expires=" + date.toGMTString();
                    } else var expires = "";
                    document.cookie = name + "=" + value + expires + "; path=/";
                }



                function readCookie(name) {
                    var nameEQ = name + "=";
                    var ca = document.cookie.split(';');
                    for (var i = 0; i < ca.length; i++) {
                        var c = ca[i];
                        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                    }
                    return null;
                }

                function eraseCookie(name) {
                    createCookie(name, "", -1);
                }

            });
        </script>
    <?php
    } else {
    ?>
    <?php } ?>-->
    <!--**************User confirmation2**************-->
    <?php
    if ($filterstatus == 'Active') {
    ?>

        <div id="thoverX" class="thover pop-up3"></div>
        <div id="tpopupX" class="tpopup pop-up3">
            <form name="submit-to-google-sheet3" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername3" type="text" value="<?php echo $studentname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid3" type="text" value="<?php echo $student_id ?>" readonly>
                <input type="hidden" type="text" name="status3" id="count3" value="" readonly required>
                <div style="padding-left:5%;padding-right:5%"><p>Hi&nbsp;<?php echo $studentname ?>&nbsp;(<?php echo $student_id ?>), Did you know that from August 1st all official communication will be in Google Chat? If you haven't joined the Google Chatroom yet, please join the <span class="noticet"><a href="https://mail.google.com/chat/u/0/#chat/space/AAAAgNqt55Q" target="_blank">RSSI Student</a></span> group now.</p></div>

                <button onclick='window.location.href="https://mail.google.com/chat/u/0/#chat/space/AAAAgNqt55Q"' type="submit" id="join" class="close-button3 btn btn-success" style="white-space:normal !important;word-wrap:break-word;">
                    <i class="fas fa-smile" style="font-size:17px" aria-hidden="true"></i>&nbsp;Yes, I have joined.</button>
                <br><br>
            </form>
        </div>
        <script>
            $('#join').click(function() {
                $('#count3').val('Joined');
            });
        </script>
        <script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycbzFxxBLaI4b_gQFpS7IPLZLSgmaQjQWSa7o-qGDRF8y_xIpLrde/exec'
            const form = document.forms['submit-to-google-sheet3']

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
        <script>
            $(document).ready(function() {

                if (Boolean(readCookie('googlechat'))) {
                    $('.pop-up3').hide();
                    $('.pop-up3').fadeOut(1000);
                }
                $('.close-button3').click(function(e) {

                    $('.pop-up3').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("googlechat", "2 days", 2);
                    //return false;
                });

                function createCookie(name, value, days) {
                    if (days) {
                        var date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                        var expires = "; expires=" + date.toGMTString();
                    } else var expires = "";
                    document.cookie = name + "=" + value + expires + "; path=/";
                }



                function readCookie(name) {
                    var nameEQ = name + "=";
                    var ca = document.cookie.split(';');
                    for (var i = 0; i < ca.length; i++) {
                        var c = ca[i];
                        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                    }
                    return null;
                }

                function eraseCookie(name) {
                    createCookie(name, "", -1);
                }

            });
        </script>
    <?php } else {
    } ?>
</body>

</html>