<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}


$view_users_query = "select * from ipfsubmission WHERE memberid2='$user_check'"; //select query for viewing users.  
$run = pg_query($con, $view_users_query); //here run the sql query.  

while ($row = pg_fetch_array($run)) //while look to fetch the result and store in a array $row.  
{

    $timestamp = $row[0];
    $memberid2 = $row[1];
    $membername2 = $row[2];
    $ipf = $row[3];
    $ipfinitiate = $row[4];
    $status2 = $row[5];
    $respondedon = $row[6];
    $ipfstatus = $row[7];
    $closedon = $row[8];
    $id = $row[9]
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="/css/style.css">
    <!-- Main css -->
    <style>
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
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>

<body>
    <?php $home_active = 'active'; ?>
    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <!-- <div class="alert alert-info alert-dismissible" role="alert" style="text-align: -webkit-center;">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    <span class="noticet">The last date for submission of question paper is 3/July/2022. For more details please visit Examination Portal.&nbsp;&nbsp;<span class="label label-danger blink_me">new</span>
                </div> -->
                <section class="box" style="padding: 2%;">

                    <table class="table" style="font-size: 13px">
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
                                    <td style="line-height: 2;"><span class="noticea"><a href="<?php echo $gm ?>" target="_blank"><?php echo substr($gm, -12) ?></a></span></td>
                                    <td style="line-height: 2;"><span class=noticea><a href=https://docs.google.com/spreadsheets/d/1ufn8vcA5tcpoVvbTgGBO9NsXmiYgjmz54Qqg_L2GZxI/edit#gid=311270786 target=_blank>Attendance sheet</a></span>
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
                    <table class="table" style="font-size: 13px">
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
                                    <td style="line-height: 2;"><span class=noticea><a href="https://docs.google.com/spreadsheets/d/1d1dfSWWh_aM7Eq2rZc3ZxXJ2uMpqKfchy0ciNSF4KxU/edit?usp=sharing" target=_blank>Homework, QT Exam</a>
                                            <!-- <br><a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vS7xMLLw8oFxfw9x8PSjCyB_-D-vdE_zVfgeHqXsE74QIdoEh60jiybeKVNT9XeBFDXqZB0Fe0cVmrQ/pubhtml?gid=1995146093&single=true" target=_blank>Online Exam</a></span> -->
                                    </td>
                                    <td style="line-height: 2;">
                                        <span class=noticea>
                                            <a href="https://drive.google.com/drive/u/0/folders/14FVzPdcCP-w1Oy22Xwrexn7_XWSFqTaI" target=_blank>Class Schedule</a><br>
                                            <a href="https://ncert.nic.in/textbook.php" target=_blank>NCERT Textbooks PDF (I-XII)</a><br>
                                            <!-- <a href="https://www.rssi.in/digital-library" target=_blank>Digital Library</a><br> -->
                                            <a href=visco.php>VISCO - Digital Learning Portal</a><br>
                                            <a href="policy.php">RSSI HR Policies</a>
                                        </span>
                                    </td>
                                </tr>
                            <?php
                            } else {
                            }
                            ?>
                        </tbody>
                    </table>
                </section>
            </div>
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
    if (@$ipfinitiate == 'initiated' && @$status2 == null) {
    ?>

        <div id="thoverX" class="thover pop-up2"></div>
        <div id="tpopupX" class="tpopup pop-up2">
            <div class="close" style="margin-right: 5%;">&times;</div>
            <br>
            <div>
                <p style="white-space:normal !important;word-wrap:break-word; margin-left: 5%;">Hi&nbsp;<?php echo strtok($fullname, ' ') ?>,Your IPF has been issued. If you are not satisfied with your appraisal discussion and IPF then you can reject your IPF. In case of rejection, another round of discussion will be set up with the concerned team. You can check your IPF from <span class="noticea"><a href="my_appraisal.php?get_id=<?php echo strtok($ipf, '(') ?>&get_year=<?php echo explode(')', (explode('(', $ipf)[1]))[0] ?>" target="_blank">My Appraisal</a></span> portal.</p>

                Appraisal type - <?php echo str_replace("(", "&nbsp;(", $googlechat) ?>
            </div>
            <br><br>
        </div>

        <script>
            $(document).ready(function() {

                $('.close').click(function(e) {

                    $('.pop-up2').delay(10).fadeOut(700);
                    e.stopPropagation();
                });
            });
        </script>
    <?php
    } else {
    }
    ?>



    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
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


    <!-- Messenger Chat Plugin Code
    <div id="fb-root"></div>
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
    </script>-->

    <div id="hellobar-bar" class="regular closable hidden-xs" style="bottom: 0%;">
        <div class="hb-content-wrapper">
            <div class="hb-text-wrapper">
                <div class="hb-headline-text" style="display: inline; display: table-cell; vertical-align: middle;">
                    <span>Donations to Rina Shiksha Sahayak Foundation shall be eligible for tax benefits under section 80G(5)(vi) of the Income Tax Act, 1961&nbsp;</span>

                    <a href="https://www.rssi.in/donation-portal" target="_blank" style="color:#444444; text-decoration:none" title="Click here"><button>Donate Now</button></a>
                </div>
            </div>

        </div>

        <div class="hb-close-wrapper">
            <a href="javascript:void(0);" class="icon-close" onClick="$('#hellobar-bar').fadeOut ()">&#10006;</a>
        </div>
    </div>

    <script>
        $('#hellobar-bar').hide().fadeOut('slow');
        $(function() { // $(document).ready shorthand
            $('#hellobar-bar').fadeIn('slow');
        });
    </script>
</body>

</html>