<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
    header("Location: index.php");
}

$user_check = $_SESSION['aid'];
@$uip = $_SERVER['REMOTE_ADDR'];

if (!$_SESSION['aid']) {

    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
?>

<?php
include("member_data.php");
?>
<?php
include("database.php");
$view_users_query = "select * from qpaper_qpaper WHERE associatenumber='$user_check'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{
    $name = $row[0];
    $date = $row[1];
    $qpaper = $row[2];
    $__hevo_id = $row[3];
    $__hevo__ingested_at = $row[4];
    $__hevo__marked_deleted = $row[5];
    $associatenumber = $row[6]
?>
<?php } ?>

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
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
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
                <div class=col style="text-align: right;"><?php echo $badge ?></div>
                <div class="alert alert-info alert-dismissible" role="alert" style="text-align: -webkit-center;">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <span class="noticet">Now you can download your ID card from your profile > My Document<!--<a href="document.php" target="_self">My Document</a></span>-->&nbsp;&nbsp;<span class="label label-danger blink_me">new</span>
                </div>
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
                            <?php if (@$filterstatus == 'Active') {
                            ?>
                                <tr>
                                    <td style="line-height: 2;"><?php echo $class ?></td>
                                    <td style="line-height: 2;"><span class="noticet"><a href="<?php echo $gm ?>" target="_blank"><?php echo substr($gm, -12) ?></a></span></td>
                                    <td style="line-height: 2;"><?php echo $attd ?>
                                        <?php if (@$attd_pending != null) {
                                        ?>
                                            <span class="label label-warning" style="display:-webkit-inline-box">pending&nbsp;<?php echo $attd_pending ?></span>
                                        <?php
                                        } else {
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php
                            } else {
                            }
                            ?>
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
                            <?php if (@$filterstatus == 'Active') {
                            ?>
                                <tr>
                                    <td style="line-height: 2;"><?php echo $evaluationpath ?></td>
                                    <td style="line-height: 2;"><?php echo $quicklink ?></td>
                                </tr>
                            <?php
                            } else {
                            }
                            ?>
                        </tbody>
                    </table>
                </section>
            </div>
            <div class="col-md-12">

                <div class="clearfix"></div>
        </section>
    </section>

    <!--**************Birth Day**************-->
    <?php
    if (@$hbday != null && $filterstatus == 'Active') {
    ?>

        <div id="thoverX" class="thover pop-uphb"></div>
        <div id="tpopupX" class="tpopup pop-uphb">
            <form name="submit-to-google-sheethb" action="" method="POST" class="hbday">
                <br>
                <input type="hidden" class="form-control" name="membernamehb" type="text" value="<?php echo $fullname ?>" readonly>
                <input type="hidden" class="form-control" name="memberidhb" type="text" value="<?php echo $associatenumber ?>" readonly>
                <input type="hidden" type="text" name="statushb" id="counthb" value="" readonly required>
                <div style="padding-left:5%;padding-right:5%"><br><br><br><br><br><br><br><br>
                    <p style="line-height: 2;">Dear&nbsp;<?php echo strtok($fullname, ' ') ?>&nbsp;, on your birthday we greet you for your merrier future. May your day be filled with moments and memories to cherish forever. Happy Birthday!<br>Your volunteer work has not given you dollar bills, but you have already gathered a lot of love and goodwill.</p>
                </div>

                <button type="submit" id="join" class="close-buttonhb btn btn-warning" onclick="startConfetti();">Close</button>
                <br><br><br>
            </form>
        </div>
        <script>
            $('#join').click(function() {
                $('#counthb').val('Enjoy');
            });
        </script>
        <script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycbwFqGt7liSNOxeZF5HcKAsWJqLemF78dEsqegVsXC7_ap4R5AU/exec'
            const form = document.forms['submit-to-google-sheethb']

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

                if (Boolean(readCookie('hbday'))) {
                    $('.pop-uphb').hide();
                    $('.pop-uphb').fadeOut(1000);
                }
                $('.close-buttonhb').click(function(e) {

                    $('.pop-uphb').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("hbday", "30 days", 30);
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
        </div>
        </div>
    <?php } else {
    } ?>

    <!--**************IPF CHECK CONFIRMATION**************-->
    <?php
    if ((@$vaccination == null) && @$googlechat != '') {
    ?>

        <div id="thoverX" class="thover pop-up2"></div>
        <div id="tpopupX" class="tpopup pop-up2">
            <form name="submit-to-google-sheet2" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername2" type="text" value="<?php echo $fullname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid2" type="text" value="<?php echo $associatenumber ?>" readonly>
                <input type="hidden" type="text" name="status2" id="count2" value="" readonly required>
                <p style="white-space:normal !important;word-wrap:break-word;">Hi&nbsp;<?php echo strtok($fullname, ' ') ?>,Your IPF has been issued. If you are not satisfied with your appraisal discussion and IPF then you can reject your IPF. In case of rejection, another round of discussion will be set up with the concerned team. You can check your IPF from <span class="noticet"><a href="my_appraisal.php" target="_blank">My Appraisal</a></span> portal.</p>

                Appraisal type - <?php echo substr($googlechat, strpos($googlechat, "-") + 1) ?>
                <br><br>

                <button type="submit" id="yes" class="btn btn-success btn-sm close-button2" style="white-space:normal !important;word-wrap:break-word;"><i class="fas fa-check" style="font-size: 17px;"></i> Accept</button>
                <button type="submit" id="no" class="btn btn-danger btn-sm close-button2" style="white-space:normal !important;word-wrap:break-word;"><i class="fas fa-times" style="font-size: 17px;"></i> Reject</button>

                <br><br>
            </form>
        </div>
        <script>
            $('#yes').click(function() {
                $('#count2').val('Accepted');
            });

            $('#no').click(function() {
                $('#count2').val('Rejected');
            });
        </script>
        <script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycby_0R2p9cBKr5ZQlpSJWKlyNVEdK25EWXaOevzT4lhVk7uqysM/exec'
            const form = document.forms['submit-to-google-sheet2']

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

                if (Boolean(readCookie('ipf22'))) {
                    $('.pop-up2').hide();
                    $('.pop-up2').fadeOut(1000);
                }
                $('.close-button2').click(function(e) {

                    $('.pop-up2').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("ipf22", "15 days", 15);
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
    } else if (@$googlechat != null && $filterstatus == 'Active') {
    ?>
    <?php } else {
    } ?>


    <!--**************NOTICE Display**************-->
    <?php
    if ((@$questionflag == null) && $filterstatus == 'Active') {
    ?>

        <div id="thoverX" class="thover pop-up2"></div>
        <div id="tpopupX" class="tpopup pop-up2">
            <form name="submit-to-google-sheet2" action="" method="POST">
                <br>
                <input type="hidden" class="form-control" name="membername2" type="text" value="<?php echo $fullname ?>" readonly>
                <input type="hidden" class="form-control" name="memberid2" type="text" value="<?php echo $associatenumber ?>" readonly>
                <input type="hidden" type="text" name="status2" id="count2" value="" readonly required>
                <embed class="hidden-xs" src="https://drive.google.com/file/d/1DlBalR4kvQ6g3V5QYTDyWeoclTRbagZi/preview" width="700px" height="400px" /></embed>
                <span class="noticet hidden-md hidden-sm hidden-lg"><a href="<?php echo $profile ?>" target="_blank"><?php echo $filename ?></a></span>
                <br><br>

                <button type="submit" id="yes" class="btn btn-success btn-sm close-button2" style="white-space:normal !important;word-wrap:break-word;">Agree</button>
                <button type="submit" id="no" class="btn btn-danger btn-sm close-button2" style="white-space:normal !important;word-wrap:break-word;">Disagree</button>

                <br><br>
            </form>
        </div>
        <script>
            $('#yes').click(function() {
                $('#count2').val('Agree');
            });

            $('#no').click(function() {
                $('#count2').val('Disagree');
            });
        </script>
        <script>
            const scriptURL = 'https://script.google.com/macros/s/AKfycbycsvlCllfvKdy257W77NyB05X5hbMpGilznY8n6x5VqL9xsTij/exec'
            const form = document.forms['submit-to-google-sheet2']

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

                if (Boolean(readCookie('notice02'))) {
                    $('.pop-up2').hide();
                    $('.pop-up2').fadeOut(1000);
                }
                $('.close-button2').click(function(e) {

                    $('.pop-up2').delay(10).fadeOut(700);
                    e.stopPropagation();

                    createCookie("notice02", "1 days", 1);
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
    } else if (@$googlechat != null && $filterstatus == 'Active') {
    ?>
    <?php } else {
    } ?>



    <style>
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        .alert {
            padding: 10px 0px !important;
        }

        .blink_me {
            animation: blinker 1s linear infinite;
        }

        @keyframes blinker {
            50% {
                opacity: 0;
            }
        }

        .hbday {
            background-image: url('https://media.istockphoto.com/vectors/happy-birthday-banner-birthday-party-flags-with-confetti-on-white-vector-id1078955654?k=20&m=1078955654&s=170667a&w=0&h=Y0jD25Q9d-Cssrn78spshBjcyzb8gyC5szud2Jds2Ko=');
            ;
        }
    </style>
    <script src="..//css/confetti.js"></script>

    <!-- Start of Async ProveSource Code --><script>!function(o,i){window.provesrc&&window.console&&console.error&&console.error("ProveSource is included twice in this page."),provesrc=window.provesrc={dq:[],display:function(){this.dq.push(arguments)}},o._provesrcAsyncInit=function(){provesrc.init({apiKey:"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhY2NvdW50SWQiOiI2MjQ4Njc2ODMxY2YwODExZDU5OTVjNmIiLCJpYXQiOjE2NDg5MTIyMzJ9.6HuHEvBtmvfhXqTPrbGSTsd_L-6uLNIKai3lJfLpjd0",v:"0.0.4"})};var r=i.createElement("script");r.type="text/javascript",r.async=!0,r["ch"+"ar"+"set"]="UTF-8",r.src="https://cdn.provesrc.com/provesrc.js";var e=i.getElementsByTagName("script")[0];e.parentNode.insertBefore(r,e)}(window,document);</script><!-- End of Async ProveSource Code -->

    <!-- Messenger Chat Plugin Code -->
    <div id="fb-root"></div>

    <!-- Your Chat Plugin code -->
    <div id="fb-customer-chat" class="fb-customerchat">
    </div>

    <script>
        var chatbox = document.getElementById('fb-customer-chat');
        chatbox.setAttribute("page_id", "215632685291793");
        chatbox.setAttribute("attribution", "biz_inbox");

        window.fbAsyncInit = function() {
            FB.init({
                xfbml: true,
                version: 'v12.0'
            });
        };

        (function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s);
            js.id = id;
            js.src = 'https://connect.facebook.net/en_US/sdk/xfbml.customerchat.js';
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));
    </script>
</body>

</html>