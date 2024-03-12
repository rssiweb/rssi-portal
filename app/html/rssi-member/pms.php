<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

date_default_timezone_set('Asia/Kolkata');

if ($_POST) {
    @$user_id = strtoupper($_POST['userid']);
    @$password = $_POST['newpass'];
    @$type = $_POST['type'];
    @$newpass_hash = password_hash($password, PASSWORD_DEFAULT);
    $now = date('Y-m-d H:i:s');

    if ($type == "Associate") {
        $change_password_query = "UPDATE rssimyaccount_members SET password='$newpass_hash', default_pass_updated_by='$user_check', default_pass_updated_on='$now' where associatenumber='$user_id'";
    } else {
        $change_password_query = "UPDATE rssimyprofile_student SET password='$newpass_hash', default_pass_updated_by='$user_check', default_pass_updated_on='$now' where student_id='$user_id'";
    }
    $result = pg_query($con, $change_password_query);
    $cmdtuples = pg_affected_rows($result);
}

@$get_id = $_POST['get_id'];
@$get_status = strtoupper($_POST['get_status']);

if ($get_id == "Associate" && $get_status != null) {
    $change_details = "SELECT * from rssimyaccount_members where associatenumber='$get_status'";
} else if ($get_id == "Associate" && $get_status == null) {
    $change_details = "SELECT * from rssimyaccount_members where filterstatus='Active' AND default_pass_updated_on is not null";
} else if ($get_id == "Student" && $get_status != null) {
    $change_details = "SELECT * from rssimyprofile_student where student_id='$get_status'";
} else if ($get_id == "Student" && $get_status == null) {
    $change_details = "SELECT * from rssimyprofile_student where filterstatus='Active' AND default_pass_updated_on is not null";
} else {
    $change_details = "SELECT * from rssimyprofile_student where student_id=''";
}

$result = pg_query($con, $change_details);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArrr = pg_fetch_all($result);
?>

<!doctype html>
<html lang="en">

<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>PMS</title>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

    <style>
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }
    </style>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
<?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>PMS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">PMS</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>

                            <?php if (@$type != null && @$cmdtuples == 0) { ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert" style="text-align: -webkit-center;">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span>ERROR: The association type and user ID you entered is incorrect.</span>
                                </div>
                            <?php } else if (@$cmdtuples == 1) { ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert" style="text-align: -webkit-center;">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-check2-circle"></i>
                                    <span>Password has been updated successfully for <?php echo @$user_id ?>.</span>
                                </div>
                            <?php } ?>

                            <form autocomplete="off" name="pms" id="pms" action="pms.php" method="POST">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">
                                        <select name="type" class="form-select" style="width:max-content; display:inline-block" required>
                                            <?php if ($type == null) { ?>
                                                <option value="" disabled selected hidden>Association Type</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $type ?></option>
                                            <?php }
                                            ?>
                                            <option>Associate</option>
                                            <option>Student</option>
                                        </select>
                                        <input type="text" name="userid" class="form-control" style="width:max-content; display:inline-block" placeholder="User ID" value="" required>
                                        <input type="password" name="newpass" id="newpass" class="form-control" style="width:max-content; display:inline-block" placeholder="New password" value="" required>
                                    </div>

                                </div>

                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                        Update</button>
                                </div>
                                <br>
                                <label for="show-password" class="field__toggle" style="margin-top: 5px;font-weight: unset;">
                                    <input type="checkbox" class="checkbox" id="show-password" class="field__toggle-input" style="display: inline-block;" />&nbsp;Show password
                                </label>
                            </form>

                            <br><b><span class="underline">Password change details</span></b><br><br>

                            <form name="changedetails" id="changedetails" action="" method="POST">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">
                                        <select name="get_id" class="form-select" style="width:max-content; display:inline-block" required>
                                            <?php if ($get_id == null) { ?>
                                                <option value="" disabled selected hidden>Association Type</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $get_id ?></option>
                                            <?php }
                                            ?>
                                            <option>Associate</option>
                                            <option>Student</option>
                                        </select>&nbsp;
                                        <input type="text" name="get_status" class="form-control" style="width:max-content; display:inline-block" placeholder="User ID" value="<?php echo $get_status ?>">
                                    </div>
                                </div>
                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_idd" class="btn btn-primary btn-sm" style="outline: none;">
                                        <i class="bi bi-search"></i>&nbsp;Search</button>
                                </div>
                            </form>
                            <div class="col" style="display: inline-block; width:99%; text-align:right">
                                Record count:&nbsp;<?php echo sizeof($resultArrr) ?>
                            </div>

                            <?php echo '
                        <div class="table-responsive">
                       <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">User ID</th>
                                <th scope="col">Set on</th>
                                <th scope="col">Set by</th>
                                <th scope="col">Changed on</th>
                                <th scope="col">Changed by</th>
                                
                            </tr>
                        </thead>' ?>
                            <?php if (sizeof($resultArrr) > 0) { ?>
                                <?php
                                echo '<tbody>';
                                foreach ($resultArrr as $array) {
                                    echo '<tr>
                                <td>' . @$array['associatenumber'] . @$array['student_id'] ?>

                                    <?php if ($array['password_updated_by'] == null || $array['password_updated_on'] < $array['default_pass_updated_on']) { ?>
                                        <?php echo '<p class="badge bg-warning">defaulter</p>' ?><?php } ?>

                                        <?php
                                        echo '</td>' ?>

                                        <?php if ($array['default_pass_updated_on'] != null) { ?>

                                            <?php echo '<td>' . @date("d/m/Y g:i a", strtotime($array['default_pass_updated_on'])) . '</td>' ?>
                                        <?php } else { ?>
                                            <?php echo '<td></td>' ?>
                                        <?php } ?>


                                        <?php echo '<td>' . $array['default_pass_updated_by'] . '</td>' ?>

                                        <?php if ($array['password_updated_on'] != null) { ?>

                                            <?php echo '<td>' . @date("d/m/Y g:i a", strtotime($array['password_updated_on'])) . '</td>' ?>
                                        <?php } else { ?>
                                            <?php echo '<td></td>' ?>
                                        <?php } ?>
                                    <?php echo '<td>' . $array['password_updated_by'] . '</td></tr>';
                                } ?>
                                <?php
                            } else if ($get_id == null && $get_status == null) {
                                ?>
                                    <tr>
                                        <td colspan="5">Please select Filter value.</td>
                                    </tr>
                                <?php
                            } else {
                                ?>
                                    <tr>
                                        <td colspan="5">No record was found for the selected filter value.</td>
                                    </tr>
                                <?php }

                            echo '</tbody>
                                    </table>
                                    </div>';
                                ?>
                                <script>
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                    var password = document.querySelector("#newpass");
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


                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

</body>

</html>