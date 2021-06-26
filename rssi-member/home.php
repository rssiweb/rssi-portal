<?php
session_start();
// Storing Session
$user_check = $_SESSION['aid'];
@$uip = $_SERVER['REMOTE_ADDR'];

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
    <title>Class details</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
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
                <div class=col style="text-align: right;">Last synced: <?php echo $lastupdatedon ?>
                    <?php if (@$vaccination > 0) {
                    ?>
                        <br><img src="https://img.icons8.com/flat-round/32/4a90e2/protection-mask.png"/>&nbsp;<?php echo $vaccination ?>
                </div>
            <?php
                    } else {
            ?>
            </div><?php } ?>
        <section class="box" style="padding: 2%;">

            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Class Allotment</th>
                        <th scope="col">Class URL</th>
                        <th scope="col">Class attendance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="line-height: 2;"><?php echo $class ?></td>
                        <td style="line-height: 2;"><span class="noticet"><a href="<?php echo $gm ?>" target="_blank"><?php echo $gm ?></a></span></td>
                        <td style="line-height: 2;"><?php echo $attd ?></td>
                    </tr>
                </tbody>
            </table>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Evaluation path</th>
                        <th scope="col">Quick Link</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="line-height: 2;"><?php echo $evaluationpath ?></td>
                        <td style="line-height: 2;"><?php echo $quicklink ?></td>
                    </tr>
                </tbody>
            </table>
        </section>
        </div>

        <div class="clearfix"></div>
        </section>
    </section>
    <!--**************User confirmation**************-->
    <?php if (@$vaccination==null) {
    ?>

        <div id="thoverX" class="thover pop-up"></div>
        <div id="tpopupX" class="tpopup pop-up">
            <form name="submit-to-google-sheet" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername" type="text" value="<?php echo $fullname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid" type="text" value="<?php echo $associatenumber ?>" readonly>
                <input type="hidden" type="text" name="status" id="count" value="" readonly required>
                <p>Hi&nbsp;<?php echo $fullname ?>&nbsp;(<?php echo $associatenumber ?>), Please confirm whether you have taken Covid 19 vaccine?</p>
                <button type="submit" id="vaccinated" class="close-button btn btn-success">
                    <i class="fas fa-thumbs-up" aria-hidden="true"></i>&nbsp;Vaccinated
                </button>&nbsp;
                <button type="submit" id="notvaccinated" class="close-button btn btn-danger">
                    <i class="fas fa-thumbs-down" aria-hidden="true"></i>&nbsp;Not vaccinated
                </button>
                <br><br>
            </form>
        </div>
        <script>
            $('#vaccinated').click(function() {
                $('#count').val('Vaccinated');
            });

            $('#notvaccinated').click(function() {
                $('#count').val('Not vaccinated');
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

                if (Boolean(readCookie('name'))) {
                    $('.pop-up').hide();
                    $('.pop-up').fadeOut(1000);
                }
                $('.close-button').click(function(e) {

                    $('.pop-up').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("name", "30 days", 30);
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
    <?php } ?>
    <style>
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }
    </style>
</body>

</html>