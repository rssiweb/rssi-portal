<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

define('SITE_KEY', '6LfJRc0aAAAAAEhNPCD7ju6si7J4qRUCBSN_8RsL');
define('SECRET_KEY', '6LfJRc0aAAAAAFuZLLd3_7KFmxQ7KPCZmLIiYLDH');

$date = date('Y-m-d H:i:s');
$login_failed_dialog = false;

function afterlogin($con, $date)
{

    $associatenumber = $_SESSION['aid']; //here session is used and value of $user_email store in $_SESSION.

    $user_query = "select * from rssimyaccount_members WHERE associatenumber='$associatenumber'";
    $result = pg_query($con, $user_query);

    $row = pg_fetch_row($result);
    $password_updated_by = $row[80];
    $password_updated_on = $row[81];
    $default_pass_updated_by = $row[82];
    $default_pass_updated_on = $row[83];
    // $role = $row[62];

    // instead of REMOTE_ADDR use HTTP_X_REAL_IP to get real client IP
    $query = "INSERT INTO userlog_member VALUES (DEFAULT,'$associatenumber','$_SERVER[HTTP_X_REAL_IP]','$date')";
    $result = pg_query($con, $query);

    if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {
        echo '<script type="text/javascript">';
        echo 'window.location.href = "defaultpasswordreset.php";';
        echo '</script>';
    }

    if (isset($_SESSION["login_redirect"])) {
        $params = "";
        if (isset($_SESSION["login_redirect_params"])) {
            foreach ($_SESSION["login_redirect_params"] as $key => $value) {
                $params = $params . "$key=$value&";
            }
            unset($_SESSION["login_redirect_params"]);
        }
        header("Location: " . $_SESSION["login_redirect"] . '?' . $params);
        unset($_SESSION["login_redirect"]);
    } else {
        // Line 25 we have the role value
        // if ($role !='Member') {
        header("Location: home.php");
        // } else {
        //     header("Location: myprofile.php");
        // }
    }
}
if (isLoggedIn("aid")) {
    afterlogin($con, $date);
    exit;
}

function checkLogin($con, $date)
{
    global $login_failed_dialog;
    $associatenumber = strtoupper($_POST['aid']);
    $colors = $_POST['pass'];

    $query = "select password from rssimyaccount_members WHERE associatenumber='$associatenumber'";
    $result = pg_query($con, $query);
    $user = pg_fetch_row($result);
    @$existingHashFromDb = $user[0];

    @$loginSuccess = password_verify($colors, $existingHashFromDb);

    // Do the login stuff...

    if ($loginSuccess) {
        $_SESSION['aid'] = $associatenumber;
        afterlogin($con, $date);
    } else {
        $login_failed_dialog = true;
    }
}

function getCaptcha($SecretKey)
{
    $Response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . SECRET_KEY . "&response={$SecretKey}");
    $Return = json_decode($Response);
    return $Return;
}

if ($_POST) {
    $islocal = $_ENV['IS_LOCAL'] ?? "false";
    if ($islocal == "true") {
        if (isset($_POST['login'])) {
            checkLogin($con, $date);
        }
    } else {
        $Return = getCaptcha($_POST['g-recaptcha-response']);
        if ($Return->success == true && $Return->score > 0.5) {
            if (isset($_POST['login'])) {
                checkLogin($con, $date);
            }
        } else {
            $login_failed_dialog = true;
        }
    }
}


?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>RSSI-My Account</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <style>
        @media (max-width: 767px) {

            /* Styles for mobile devices */
            .logo {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .logo span {
                margin: 5px 0;
            }
        }

        .by-line {
            background-color: #CE1212;
            padding: 1px 5px;
            border-radius: 0px;
            font-size: small !important;
            color: white !important;
            margin-left: 10%;
        }
    </style>

</head>

<body>

    <main>
        <div class="container">

            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

                            <div class="container text-center py-4">
                                <div class="logo">
                                    <span class="d-lg-block" style="margin-right:10%;">Phoenix</span>
                                    <span class="by-line">by RSSI NGO</span>
                                </div>
                            </div>



                            <div class="card mb-3">

                                <div class="card-body">

                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4">Login to Your Account</h5>
                                        <p class="text-center small">Enter your username & password to login</p>
                                    </div>

                                    <form class="row g-3 needs-validation" role="form" method="post" name="login" action="index.php">

                                        <div class="col-12">
                                            <label for="yourUsername" class="form-label">Username</label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text" id="inputGroupPrepend"><i class="bi bi-person"></i></span>
                                                <input type="text" name="aid" class="form-control" id="aid" placeholder="Associate Number" required>
                                                <div class="invalid-feedback">Please enter your username.</div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="pass" class="form-label">Password</label>
                                            <input type="password" name="pass" class="form-control" id="pass" required>
                                            <div class="invalid-feedback">Please enter your password!</div>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-check">
                                                <label for="show-password" class="form-label" style="margin-top: 5px;font-weight: unset;">
                                                    <input type="checkbox" class="form-check-input" id="show-password" class="field__toggle-input" style="display: inline-block;"> Show password
                                                </label>
                                            </div>
                                        </div>
                                        <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" />
                                        <div class="col-12">
                                            <button class="btn btn-primary w-100" type="submit" name="login" value="login">Login</button>
                                        </div>
                                        <div class="col-12">
                                            <p class="small mb-0">Forgot password? <a href="#" data-bs-toggle="modal" data-bs-target="#popup">Click here</a></p>
                                            </p>
                                        </div>
                                    </form>

                                </div>
                            </div>

                            <div class="credits">
                                Designed by <a href="https://www.rssi.in/">rssi.in</a>
                            </div>

                        </div>
                    </div>
                </div>

            </section>

        </div>
    </main><!-- End #main -->

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        var password = document.querySelector("#pass");
        var toggle = document.querySelector("#show-password");
        // I'm using the "(click)" event to make this works cross-browser.
        toggle.addEventListener("click", handleToggleClick, false);
        // I handle the toggle click, changing the TYPE of password input.
        function handleToggleClick(event) {

            if (this.checked) {

                console.warn("Change input 'type' to: text");
                password.type = "text";

            } else {

                console.warn("Change input 'type' to: password");
                password.type = "password";

            }

        }
    </script>

    <?php if ($login_failed_dialog) { ?>
        <div class="modal" tabindex="-1" role="dialog" id="errorModal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Error: Login Failed</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Please enter valid credentials.</p>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var myModal = new bootstrap.Modal(document.getElementById('errorModal'));
                myModal.show();
            });
        </script>
    <?php } ?>


    <!--protected by reCAPTCHA-->
    <script>
        grecaptcha.ready(function() {
            grecaptcha.execute('<?php echo SITE_KEY; ?>', {
                    action: 'homepage'
                })
                .then(function(token) {
                    //console.log(token);
                    document.getElementById('g-recaptcha-response').value = token;
                });
        });
    </script>

    <!-- Popup -->
    <div class="modal fade" id="popup" tabindex="-1" aria-labelledby="popupLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="popupLabel">Forgot password?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Body -->
                <div class="modal-body">
                    Please contact RSSI Admin at 7980168159 or email at info@rssi.in

                </div>
                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

</body>

</html>