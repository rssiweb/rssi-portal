<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

$date = date('Y-m-d H:i:s');
$login_failed_dialog = "";

function afterlogin($con, $date)
{
    $associatenumber = $_SESSION['aid'];
    $user_query = pg_query($con, "SELECT password_updated_by, password_updated_on, default_pass_updated_on FROM rssimyaccount_members WHERE associatenumber='$associatenumber'");
    $row = pg_fetch_row($user_query);
    $password_updated_by = $row[0];
    $password_updated_on = $row[1];
    $default_pass_updated_on = $row[2];

    passwordCheck($password_updated_by, $password_updated_on, $default_pass_updated_on);

    function getUserIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipList[0]);
            return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : $_SERVER['REMOTE_ADDR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    $user_ip = getUserIpAddr();
    pg_query($con, "INSERT INTO userlog_member VALUES (DEFAULT, '$associatenumber', '$user_ip', '$date')");

    if (isset($_SESSION["login_redirect"])) {
        $params = "";
        if (isset($_SESSION["login_redirect_params"])) {
            foreach ($_SESSION["login_redirect_params"] as $key => $value) {
                $params .= "$key=$value&";
            }
            unset($_SESSION["login_redirect_params"]);
        }
        header("Location: " . $_SESSION["login_redirect"] . '?' . $params);
        unset($_SESSION["login_redirect"]);
    } else {
        header("Location: home.php");
    }
    exit;
}

if (isLoggedIn("aid")) {
    afterlogin($con, $date);
}

function checkLogin($con, $date)
{
    global $login_failed_dialog;
    $associatenumber = strtoupper($_POST['aid']);
    $password = $_POST['pass'];

    $query = "SELECT password, absconding FROM rssimyaccount_members WHERE associatenumber='$associatenumber'";
    $result = pg_query($con, $query);
    if ($result) {
        $user = pg_fetch_assoc($result);
        if ($user) {
            $existingHashFromDb = $user['password'];
            $absconding = $user['absconding'];
            if (password_verify($password, $existingHashFromDb)) {
                if (!empty($absconding)) {
                    $login_failed_dialog = "Your account has been flagged as inactive. Please contact support.";
                } else {
                    $_SESSION['aid'] = $associatenumber;
                    afterlogin($con, $date);
                }
            } else {
                $login_failed_dialog = "Incorrect username or password.";
            }
        } else {
            $login_failed_dialog = "User not found.";
        }
    } else {
        $login_failed_dialog = "Error executing query.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        checkLogin($con, $date);
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RSSI-My Account</title>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        @media (max-width: 767px) {
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
                                    <img src="../img/phoenix.png" alt="Phoenix Logo" width="40%">
                                </div>
                            </div>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4">Login to Your Account</h5>
                                        <p class="text-center small">Enter your username & password to login</p>
                                    </div>
                                    <form class="row g-3 needs-validation" method="post" action="index.php">
                                        <div class="col-12">
                                            <label for="yourUsername" class="form-label">Username</label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                                <input type="text" name="aid" class="form-control" placeholder="Associate Number" required>
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
                                                <input type="checkbox" class="form-check-input" id="show-password">
                                                <label for="show-password" class="form-label">Show password</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-primary w-100" type="submit" name="login">Login</button>
                                        </div>
                                        <div class="col-12">
                                            <p class="small mb-0">Forgot password? <a href="#" data-bs-toggle="modal" data-bs-target="#popup">Click here</a></p>
                                        </div>
                                    </form>
                                </div>
                                <script>
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                    document.addEventListener("DOMContentLoaded", function() {
                                        var password = document.querySelector("#pass");
                                        var toggle = document.querySelector("#show-password");
                                        toggle.addEventListener("click", function() {
                                            password.type = this.checked ? "text" : "password";
                                        });
                                    });
                                </script>
                            </div>
                            <div class="credits">
                                Designed by <a href="https://www.rssi.in/">rssi.in</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php if (!empty($login_failed_dialog)) { ?>
        <div class="modal" tabindex="-1" role="dialog" id="errorModal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Error: Login Failed</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p><?php echo $login_failed_dialog ?></p>
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
    <div class="modal fade" id="popup" tabindex="-1" aria-labelledby="popupLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="popupLabel">Forgot password?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Please contact RSSI Admin at 7980168159 or email at info@rssi.in
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets_new/js/main.js"></script>

    <!-- <div class="modal fade show" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-modal="true"
        role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0"
                                class="active"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="1"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="2"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="3"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="4"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="5"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="6"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators"
                                data-bs-slide-to="7"></button>
                        </div>
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <a href="https://www.mygov.in/campaigns/poshan-abhiyaan-2024/?target=webview&type=campaign&nid=0"
                                    target="_blank"><img
                                        src="https://static.mygov.in/static/s3fs-public/styles/home-slider-image/public/mygov_1725880437110258821.jpg"
                                        class="d-block w-100" alt="Slide 1"></a>
                            </div>
                            <div class="carousel-item">
                                <a href="https://www.mygov.in/task/selfie-your-kitchen-garden/?target=inapp&type=task&nid=353550"
                                    target="_blank"><img
                                        src="https://static.mygov.in/static/s3fs-public/mygov_1725863329110258821.png"
                                        class="d-block w-100" alt="Slide 2"></a>
                            </div>
                            <div class="carousel-item">
                                <a href="https://www.mygov.in/task/drawing-competition-poshan-nutrition/?target=inapp&type=task&nid=353539"
                                    target="_blank"><img
                                        src="https://static.mygov.in/static/s3fs-public/mygov_1725862673110258821_1.png"
                                        class="d-block w-100" alt="Slide 3"></a>
                            </div>
                            <div class="carousel-item">
                                <a href="https://www.mygov.in/task/selfie-colorful-thali-depicting-diet-diversity/?target=inapp&type=task&nid=353517"
                                    target="_blank"><img
                                        src="https://static.mygov.in/static/s3fs-public/mygov_1725768844123183681.png"
                                        class="d-block w-100" alt="Slide 4"></a>
                            </div>
                            <div class="carousel-item">
                                <a href="https://www.mygov.in/task/slogan-writing-competition-nutrition-poshan/?target=inapp&type=task&nid=353506"
                                    target="_blank"><img
                                        src="https://static.mygov.in/static/s3fs-public/mygov_1725731570123183681.png"
                                        class="d-block w-100" alt="Slide 5"></a>
                            </div>
                            <div class="carousel-item">
                                <a href="https://www.mygov.in/task/poem-writing-competition-poshan/?target=inapp&type=task&nid=353495"
                                    target="_blank"><img
                                        src="https://static.mygov.in/static/s3fs-public/mygov_1725697827123183681_1.png"
                                        class="d-block w-100" alt="Slide 6"></a>
                            </div>

                            <div class="carousel-item">
                                <a href="https://quiz.mygov.in/quiz/quiz-competition-on-healthy-diet-complementary-feeding/"
                                    target="_blank"><img
                                        src="https://static.mygov.in/media/quiz/2024/09/mygov_66deba543b0af.jpg"
                                        class="d-block w-100" alt="Slide 7"></a>
                            </div>
                            <div class="carousel-item">
                                <a href="https://quiz.mygov.in/quiz/quiz-competition-on-anemia-first-1000-days/"
                                    target="_blank"><img
                                        src="https://static.mygov.in/media/quiz/2024/09/mygov_66ded9542454c.jpg"
                                        class="d-block w-100" alt="Slide 8"></a>
                            </div>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators"
                            data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators"
                            data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <div class="d-flex flex-grow-1">
                        <p class="mb-0" style="text-align: left;">
                            Click on the image above to learn more about this topic or to take further action.
                        </p>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div> -->
    <!-- <div class="modal fade show" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-modal="true"
        role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                        </div>
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <iframe class="d-block w-100" src="https://www.youtube.com/embed/VKNs5QOx634?autoplay=1&mute=1&loop=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="height: 500px;"></iframe>
                            </div>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <div class="d-flex flex-grow-1">
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>
    <script>
        var myModal = new bootstrap.Modal(document.getElementById('myModal'));
        myModal.show();
    </script> -->
</body>

</html>