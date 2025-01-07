<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

date_default_timezone_set('Asia/Kolkata');
include("../../util/email.php");

if (@$_POST['form-type'] == "gms") {
    @$redeem_id1 = 'RSG' . time();;
    @$user_id1 = $associatenumber;
    @$redeem_gems_point = $_POST['redeem_gems_point'];
    @$redeem_type = $_POST['redeem_type'];
    @$now = date('Y-m-d H:i:s');
    @$email = $email;
    if ($redeem_id1 != "") {
        $redeem = "INSERT INTO gems (redeem_id, user_id, redeem_gems_point, redeem_type,requested_on) VALUES ('$redeem_id1','$user_id1','$redeem_gems_point','$redeem_type','$now')";
        $result = pg_query($con, $redeem);
        $cmdtuples = pg_affected_rows($result);
    }
    sendEmail("redeem_apply", array(
        "fullname" => $fullname,
        "user_id1" => $user_id1,
        "redeem_id1" => $redeem_id1,
        "redeem_gems_point" => $redeem_gems_point,
        "redeem_type" => $redeem_type,
        "now" => @date("d/m/Y g:i a", strtotime($now))
    ), $email);
}
?>
<?php if ($role == 'Admin' && $filterstatus == 'Active') {
    @$redeem_id = strtoupper($_GET['redeem_id']);
    @$user_id = strtoupper($_GET['user_id']);
    @$is_user = $_GET['is_user'];

    if (($redeem_id == null && $user_id == null)) {

        $result = pg_query($con, "SELECT * FROM gems 
        left join (SELECT associatenumber, fullname, email, phone FROM rssimyaccount_members) faculty ON gems.user_id=faculty.associatenumber 
        left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON gems.user_id=student.student_id
        order by requested_on desc");
        $totalgemsredeem = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems");
        $totalgemsreceived = pg_query($con, "SELECT COALESCE(SUM(gems),0) FROM certificate");
        $totalgemsredeem_admin = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems where user_id='$associatenumber'AND (reviewer_status is null or reviewer_status !='Rejected')");
        $totalgemsreceived_admin = pg_query($con, "SELECT COALESCE(SUM(gems),0) FROM certificate where awarded_to_id='$associatenumber'");
        $totalgemsredeem_approved = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems");
    }

    if (($redeem_id != null)) {

        $result = pg_query($con, "SELECT * FROM gems left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON gems.user_id=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON gems.user_id=student.student_id where redeem_id='$redeem_id' order by requested_on desc");
        $totalgemsredeem = pg_query($con, "SELECT SUM(redeem_gems_point) FROM gems where redeem_id=''");
        $totalgemsreceived = pg_query($con, "SELECT SUM(gems) FROM certificate where certificate_no=''");
        $totalgemsredeem_admin = pg_query($con, "SELECT SUM(redeem_gems_point) FROM gems where redeem_id=''");
        $totalgemsreceived_admin = pg_query($con, "SELECT SUM(gems) FROM certificate where certificate_no=''");
        $totalgemsredeem_approved = pg_query($con, "SELECT SUM(redeem_gems_point) FROM gems where redeem_id=''");
    }

    if (($user_id != null)) {

        $result = pg_query($con, "SELECT * FROM gems left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON gems.user_id=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON gems.user_id=student.student_id where user_id='$user_id' order by requested_on desc");
        $totalgemsredeem = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems where user_id='$user_id' AND (reviewer_status is null or reviewer_status !='Rejected')");
        $totalgemsreceived = pg_query($con, "SELECT COALESCE(SUM(gems),0) FROM certificate where awarded_to_id='$user_id'");
        $totalgemsredeem_admin = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems where user_id='$associatenumber' AND (reviewer_status is null or reviewer_status !='Rejected')");
        $totalgemsreceived_admin = pg_query($con, "SELECT COALESCE(SUM(gems),0) FROM certificate where awarded_to_id='$associatenumber'");
        $totalgemsredeem_approved = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems where user_id='$user_id' AND reviewer_status='Approved'");
    }

    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);
    $resultArrr = pg_fetch_result($totalgemsredeem, 0, 0);
    $resultArrrr = pg_fetch_result($totalgemsreceived, 0, 0);
    $resultArrr_admin = pg_fetch_result($totalgemsredeem_admin, 0, 0);
    $resultArrrr_admin = pg_fetch_result($totalgemsreceived_admin, 0, 0);
    $gems_approved = pg_fetch_result($totalgemsredeem_approved, 0, 0);
} else {

    $result = pg_query($con, "SELECT * FROM gems where user_id='$associatenumber' order by requested_on desc");
    $totalgemsredeem = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems where user_id='$associatenumber'AND (reviewer_status is null or reviewer_status !='Rejected')");
    $totalgemsreceived = pg_query($con, "SELECT COALESCE(SUM(gems),0) FROM certificate where awarded_to_id='$associatenumber'");

    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);
    $resultArrr = pg_fetch_result($totalgemsredeem, 0, 0);
    $resultArrrr = pg_fetch_result($totalgemsreceived, 0, 0);
} ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-11316670180');
    </script>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Gems Management System (CMS)</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">


    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

    <style>
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Gems Management System (CMS)</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Rewards & Recognition</a></li>
                    <li class="breadcrumb-item active">CMS</li>
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
                            <?php if ($role == 'Admin' && $filterstatus == 'Active') { ?>
                                <div class="col" style="display: inline-block; width: 100%; text-align: right">
                                    <?php if ($resultArrrr_admin - $resultArrr_admin != null) { ?>
                                        <div style="display: inline-block; width: 100%; text-align: right;">
                                            <i class="bi bi-gem" title="RSSI Gems"></i>&nbsp;
                                            <p class="badge bg-success"><?php echo ($resultArrrr_admin - $resultArrr_admin) ?></p>
                                        </div>
                                    <?php } else { ?>
                                        <i class="bi bi-gem" title="RSSI Gems"></i>&nbsp;
                                        <p class="badge bg-secondary">You're almost there</p>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <div class="col" style="display: inline-block; width: 100%; text-align: right">
                                    <?php if ($resultArrrr - $resultArrr != null) { ?>
                                        <div style="display: inline-block; width: 100%; text-align: right;">
                                            <i class="bi bi-gem" title="RSSI Gems"></i>&nbsp;
                                            <p class="badge bg-success"><?php echo ($resultArrrr - $resultArrr) ?></p>
                                        </div>
                                    <?php } else { ?>
                                        <i class="bi bi-gem" title="RSSI Gems"></i>&nbsp;
                                        <p class="badge bg-secondary">You're almost there</p>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <?php if (@$redeem_id1 != null && @$cmdtuples == 0) { ?>
                                <div class="alert alert-danger alert-dismissible text-center" role="alert">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span>ERROR: Oops, something wasn't right.</span>
                                </div>
                            <?php } else if (@$cmdtuples == 1) { ?>
                                <div class="alert alert-success alert-dismissible text-center" role="alert">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-check2-circle"></i>
                                    <span>Your request has been submitted. Redeem id <?php echo $redeem_id1 ?>.</span>
                                </div>
                                <script>
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                </script>
                            <?php } ?>
                            <form autocomplete="off" name="gms" id="gms" action="redeem_gems.php" method="POST">
                                <fieldset <?php echo ($filterstatus != 'Active') ? 'disabled' : ''; ?>>
                                    <div class="form-group" style="display: inline-block;">

                                        <input type="hidden" name="form-type" type="text" value="gms">

                                        <?php if ($role == 'Admin' && $filterstatus == 'Active') { ?>
                                            <span class="input-help">
                                                <input type="number" name="redeem_gems_point" class="form-control" placeholder="Gems" max="<?php echo ($resultArrrr_admin - $resultArrr_admin) ?>" min="1" required>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Redeem gems point</small>
                                            </span>

                                        <?php } else { ?>

                                            <span class="input-help">
                                                <input type="number" name="redeem_gems_point" class="form-control" placeholder="Gems" max="<?php echo ($resultArrrr - $resultArrr) ?>" min="1" required>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Redeem gems point</small>
                                            </span>
                                        <?php } ?>


                                        <span class="input-help">
                                            <select name="redeem_type" class="form-select" style="width:max-content; display:inline-block" required>
                                                <?php if ($redeem_type == null) { ?>
                                                    <option disabled selected hidden>Redeem type</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $redeem_type ?></option>
                                                <?php }
                                                ?>
                                                <option>Voucher</option>
                                                <option>Bank payment</option>

                                            </select>
                                            <small id="passwordHelpBlock" class="form-text text-muted">Redeem type*</small>
                                        </span>

                                        <input type="hidden" name="issuedby" class="form-control" placeholder="Issued by" value="<?php echo $fullname ?>" required readonly>

                                        <?php
                                        // Logic for enabling or disabling the button
                                        if ($role == 'Admin' && $filterstatus == 'Active') {
                                            $canRedeem = ($resultArrrr_admin - $resultArrr_admin) >= 1000;
                                        } else {
                                            $canRedeem = ($resultArrrr - $resultArrr) >= 1000;
                                        }

                                        // Determine the disabled attribute
                                        $disabled = $canRedeem ? '' : 'disabled';
                                        ?>
                                        <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;" <?php echo $disabled; ?>>
                                            <i class="bi bi-dash-lg"></i>&nbsp;&nbsp;Redeem
                                        </button>
                                    </div>
                                </fieldset>
                            </form>

                            <div style="display: inline-block; width:100%; text-align:right;">Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                            </div>
                            <?php if ($role == 'Admin' && $filterstatus == 'Active' && $user_id != null) { ?>
                                <div class="col" style="display: inline-block; width:100%; text-align:right">
                                    <div style="display: inline-block; width:100%; text-align:right;">Balance:&nbsp;
                                        <?php if ($resultArrrr - $gems_approved <= 0) { ?>
                                            <p class="badge bg-danger"><?php echo ($resultArrrr - $gems_approved) ?></p>
                                        <?php } else { ?>

                                            <p class="badge bg-info"><?php echo ($resultArrrr - $gems_approved) ?></p>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>


                            <?php if ($role == 'Admin' && $filterstatus == 'Active') { ?>

                                <form action="" method="GET">
                                    <div class="form-group" style="display: inline-block;">
                                        <div class="col2" style="display: inline-block;">
                                            <input name="redeem_id" id="redeem_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Redeem id" value="<?php echo $redeem_id ?>">
                                        </div>
                                        <div class="col2" style="display: inline-block;">
                                            <input name="user_id" id="user_id" class="form-control" style="width:max-content; display:inline-block" placeholder="User id" value="<?php echo $user_id ?>">
                                        </div>
                                    </div>
                                    <div class="col2 left" style="display: inline-block;">
                                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                            <i class="bi bi-search"></i>&nbsp;Search</button>
                                    </div>
                                    <div id="filter-checks">
                                        <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_GET['is_user'])) echo "checked='checked'"; ?> />
                                        <label for="is_user" style="font-weight: 400;">Search by Redeem id</label>
                                    </div>
                                </form>
                                <script>
                                    if ($('#is_user').not(':checked').length > 0) {

                                        document.getElementById("user_id").disabled = false;
                                        document.getElementById("redeem_id").disabled = true;

                                    } else {

                                        document.getElementById("user_id").disabled = true;
                                        document.getElementById("redeem_id").disabled = false;

                                    }

                                    const checkbox = document.getElementById('is_user');

                                    checkbox.addEventListener('change', (event) => {
                                        if (event.target.checked) {
                                            document.getElementById("user_id").disabled = true;
                                            document.getElementById("redeem_id").disabled = false;
                                        } else {
                                            document.getElementById("user_id").disabled = false;
                                            document.getElementById("redeem_id").disabled = true;
                                        }
                                    })
                                </script>

                            <?php } ?>
                            <br>

                            <?php echo '
                    <p>Select Number Of Rows</p>
                    <div class="form-group">
                        <select class="form-select" name="state" id="maxRows">
                            <option value="5000">Show ALL Rows</option>
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="70">70</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                    <table class="table" id="table-id">
                        <thead>
                            <tr>
                            <th scope="col">Redeem id</th>
                            <th scope="col">Requested on</th>' ?>

                            <?php if ($role == 'Admin') { ?>
                                <?php echo '<th scope="col">User id</th>' ?>
                            <?php } ?>
                            <?php echo ' <th scope="col">Gems point</th>
                            <th scope="col">Redeem type</th>
                            <th scope="col">Reviewer id</th>
                            <th scope="col">Reviewer status</th>
                            <th scope="col">Reviewer remarks</th>' ?>
                            <?php if ($role == 'Admin') { ?>
                                <?php echo '<th scope="col"></th>' ?>
                            <?php } ?>
                            <?php echo '</tr>
                            </thead>' ?>
                            <?php if ($resultArr != null) {
                                echo '<tbody>';
                                foreach ($resultArr as $array) {
                                    echo '
                            <tr>
                                <td>' . $array['redeem_id'] . '</td>' ?>

                                    <?php if ($array['requested_on'] == null) { ?>
                                        <?php echo '<td></td>' ?>
                                    <?php } else { ?>
                                        <?php echo '<td>' . @date("d/m/Y g:i a", strtotime($array['requested_on'])) . '</td>' ?>
                                    <?php } ?>
                                    <?php if ($role == 'Admin') { ?>
                                        <?php echo '<td>' . $array['user_id'] . '<br>' . $array['fullname'] . $array['studentname'] . '</td>' ?>
                                    <?php } ?>
                                    <?php echo '<td>' . $array['redeem_gems_point'] . '</td>
                                <td>' . $array['redeem_type'] . '</td>
                                <td>' . $array['reviewer_id'] . '<br>' . $array['reviewer_name'] . '</td>
                                <td>' . $array['reviewer_status'] . '<br>' ?>
                                    <?php if ($array['reviewer_status_updated_on'] == null) { ?>
                                        <?php echo null ?>
                                    <?php } else { ?>
                                        <?php echo @date("d/m/Y g:i a", strtotime($array['reviewer_status_updated_on'])) . '</td>' ?>
                                    <?php } ?>

                                    <?php echo '<td>' . $array['reviewer_remarks'] . '</td>' ?>

                                    <?php if ($role == 'Admin') { ?>

                                        <?php echo '

                                <td>
                                <button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['redeem_id'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="bi bi-box-arrow-up-right" style="font-size: 14px ;color:#777777" title="Show Details" display:inline;></i></button>&nbsp;&nbsp;' ?>
                                        <?php if (($array['phone'] != null || $array['contact'] != null) && $array['reviewer_status'] == 'Approved') { ?>
                                            <?php echo '<a href="https://api.whatsapp.com/send?phone=91' . $array['phone'] . $array['contact'] . '&text=Dear ' . $array['fullname'] . $array['studentname'] . ' (' . $array['user_id'] . '),%0A%0ARedeem id ' . $array['redeem_id'] . ' against the policy issued by the organization has been settled at Rs.' . $array['redeem_gems_point'] . ' on ' . @date("d/m/Y g:i a", strtotime($array['reviewer_status_updated_on'])) . '.%0A%0AThe amount has been credited to your account. It may take standard time for it to be reflected in your account.%0A%0AYou can track the status of your request in real-time from https://login.rssi.in/rssi-member/redeem_gems.php. For more information, please contact your HR or immediate supervisor.%0A%0A--RSSI%0A%0A**This is an automatically generated SMS
                                    " target="_blank"><i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS ' . $array['phone'] . $array['contact'] . '"></i></a>' ?>
                                        <?php } else { ?>
                                            <?php echo '<i class="bi bi-whatsapp" style="color:#A2A2A2;" title="Send SMS"></i>' ?>
                                            <?php } ?>&nbsp;&nbsp;

                                            <?php if (($array['email'] != null || $array['emailaddress'] != null) && $array['reviewer_status'] == 'Approved') { ?>
                                                <?php echo '<form  action="#" name="email-form-' . $array['redeem_id'] . '" method="POST" style="display: -webkit-inline-box;" >
                                    <input type="hidden" name="template" type="text" value="redeem_update">
                                    <input type="hidden" name="data[redeem_id]" type="text" value="' . $array['redeem_id'] . '">
                                    <input type="hidden" name="data[user_id]" type="text" value="' . $array['user_id'] . '">
                                    <input type="hidden" name="data[fullname]" type="text" value="' . $array['fullname'] . $array['studentname'] . '">
                                    <input type="hidden" name="data[redeem_gems_point]" type="text" value="' . $array['redeem_gems_point'] . '">
                                    <input type="hidden" name="data[reviewer_status]" type="text" value="' . $array['reviewer_status'] . '">
                                    <input type="hidden" name="data[reviewer_status_updated_on]" type="text" value="' . @date("d/m/Y g:i a", strtotime($array['reviewer_status_updated_on'])) . '">
                                    <input type="hidden" name="email" type="text" value="' . $array['email'] . $array['emailaddress'] . '">
                                    <button  style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;"
                                     type="submit"><i class="bi bi-envelope-at" style="color:#444444;" title="Send Email ' . $array['email'] . $array['emailaddress'] . '"></i></button>
                                </form>' ?>
                                            <?php } else { ?>
                                                <?php echo '<i class="bi bi-envelope-at" style="color:#A2A2A2;" title="Send Email"></i>' ?>
                                            <?php } ?>

                                            <?php echo '&nbsp;&nbsp;<form name="gemsdelete_' . $array['redeem_id'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                            <input type="hidden" name="form-type" type="text" value="gemsdelete">
                                            <input type="hidden" name="redeem_id" type="text" value="' . $array['redeem_id'] . '">

                                            <button type="submit" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['redeem_id'] . '"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                        </td>' ?>
                                        <?php } ?>
                                    <?php }
                                echo '</tr>' ?>
                                <?php
                            } else if (@$get_certificate_no == "" && @$get_nomineeid == "") {
                                ?>
                                    <tr>
                                        <td colspan="5">Please select Filter value.</td>
                                    </tr>
                                <?php
                            } else if (sizeof($resultArr) == 0 || (@$get_certificate_no != "" || @$get_nomineeid != "")) { ?>
                                    <?php echo '<tr>
                                    <td colspan="5">No record found for ' ?><?php echo $get_certificate_no ?><?php echo $get_nomineeid ?><?php echo '.</td>
                                </tr>' ?>
                                <?php
                            }
                            echo '</tbody>
                    </table>
                    </div>'
                                ?>


                                <!-- Start Pagination -->
                                <div class="pagination-container">
                                    <nav>
                                        <ul class="pagination">
                                            <li class="page-item" data-page="prev">
                                                <button class="page-link pagination-button" aria-label="Previous">&lt;</button>
                                            </li>
                                            <!-- Here the JS Function Will Add the Rows -->
                                            <li class="page-item">
                                                <button class="page-link pagination-button">1</button>
                                            </li>
                                            <li class="page-item">
                                                <button class="page-link pagination-button">2</button>
                                            </li>
                                            <li class="page-item">
                                                <button class="page-link pagination-button">3</button>
                                            </li>
                                            <li class="page-item" data-page="next" id="prev">
                                                <button class="page-link pagination-button" aria-label="Next">&gt;</button>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>

                                <script>
                                    getPagination('#table-id');

                                    function getPagination(table) {
                                        var lastPage = 1;

                                        $('#maxRows').on('change', function(evt) {
                                            lastPage = 1;
                                            $('.pagination').find('li').slice(1, -1).remove();
                                            var trnum = 0;
                                            var maxRows = parseInt($(this).val());

                                            if (maxRows == 5000) {
                                                $('.pagination').hide();
                                            } else {
                                                $('.pagination').show();
                                            }

                                            var totalRows = $(table + ' tbody tr').length;
                                            $(table + ' tr:gt(0)').each(function() {
                                                trnum++;
                                                if (trnum > maxRows) {
                                                    $(this).hide();
                                                }
                                                if (trnum <= maxRows) {
                                                    $(this).show();
                                                }
                                            });

                                            if (totalRows > maxRows) {
                                                var pagenum = Math.ceil(totalRows / maxRows);
                                                for (var i = 1; i <= pagenum; i++) {
                                                    $('.pagination #prev').before('<li class="page-item" data-page="' + i + '">\
                                                <button class="page-link pagination-button">' + i + '</button>\
                                                </li>').show();
                                                }
                                            }

                                            $('.pagination [data-page="1"]').addClass('active');
                                            $('.pagination li').on('click', function(evt) {
                                                evt.stopImmediatePropagation();
                                                evt.preventDefault();
                                                var pageNum = $(this).attr('data-page');

                                                var maxRows = parseInt($('#maxRows').val());

                                                if (pageNum == 'prev') {
                                                    if (lastPage == 1) {
                                                        return;
                                                    }
                                                    pageNum = --lastPage;
                                                }
                                                if (pageNum == 'next') {
                                                    if (lastPage == $('.pagination li').length - 2) {
                                                        return;
                                                    }
                                                    pageNum = ++lastPage;
                                                }

                                                lastPage = pageNum;
                                                var trIndex = 0;
                                                $('.pagination li').removeClass('active');
                                                $('.pagination [data-page="' + lastPage + '"]').addClass('active');
                                                limitPagging();
                                                $(table + ' tr:gt(0)').each(function() {
                                                    trIndex++;
                                                    if (
                                                        trIndex > maxRows * pageNum ||
                                                        trIndex <= maxRows * pageNum - maxRows
                                                    ) {
                                                        $(this).hide();
                                                    } else {
                                                        $(this).show();
                                                    }
                                                });
                                            });
                                            limitPagging();
                                        }).val(5).change();
                                    }

                                    function limitPagging() {
                                        if ($('.pagination li').length > 7) {
                                            if ($('.pagination li.active').attr('data-page') <= 3) {
                                                $('.pagination li.page-item:gt(5)').hide();
                                                $('.pagination li.page-item:lt(5)').show();
                                                $('.pagination [data-page="next"]').show();
                                            }
                                            if ($('.pagination li.active').attr('data-page') > 3) {
                                                $('.pagination li.page-item').hide();
                                                $('.pagination [data-page="next"]').show();
                                                var currentPage = parseInt($('.pagination li.active').attr('data-page'));
                                                for (let i = currentPage - 2; i <= currentPage + 2; i++) {
                                                    $('.pagination [data-page="' + i + '"]').show();
                                                }
                                            }
                                        }
                                    }
                                </script>

                                <!--------------- POP-UP BOX --------------->
                                <style>
                                    .modal {
                                        background-color: rgba(0, 0, 0, 0.4);
                                        /* Black w/ opacity */
                                    }
                                </style>
                                <div class="modal" id="myModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5" id="exampleModalLabel">Gems Redeem Details</h1>
                                                <button type="button" id="closedetails-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div style="width: 100%; text-align: right;">
                                                    <p id="status" class="badge" style="display: inline;"><span class="redeem_id"></span></p>
                                                </div>

                                                <form id="reviewform" action="#" method="POST">
                                                    <input type="hidden" name="form-type" value="gemsredeem" readonly>
                                                    <input type="hidden" name="reviewer_id" id="reviewer_id" value="<?php echo $associatenumber ?>" readonly>
                                                    <input type="hidden" name="reviewer_name" id="reviewer_name" value="<?php echo $fullname ?>" readonly>
                                                    <input type="hidden" name="redeem_idd" id="redeem_idd" readonly>

                                                    <div class="mb-3">
                                                        <label for="reviewer_status" class="form-label">Status</label>
                                                        <select name="reviewer_status" id="reviewer_status" class="form-select" required>
                                                            <option disabled selected hidden>Status</option>
                                                            <option value="Approved">Approved</option>
                                                            <option value="Under review">Under review</option>
                                                            <option value="Rejected">Rejected</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="reviewer_remarks" class="form-label">Reviewer Remarks</label>
                                                        <textarea name="reviewer_remarks" id="reviewer_remarks" class="form-control" placeholder="Reviewer remarks"></textarea>
                                                        <small id="passwordHelpBlock" class="form-text text-muted">Reviewer remarks</small>
                                                    </div>

                                                    <button type="submit" id="redeemupdate" class="btn btn-danger btn-sm">Update</button>
                                                </form>

                                                <div class="modal-footer">
                                                    <button type="button" id="closedetails-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <script>
                                    var data = <?php echo json_encode($resultArr) ?>

                                    // Get the modal
                                    var modal = document.getElementById("myModal");
                                    // Get the <span> element that closes the modal
                                    var closedetails = [
                                        document.getElementById("closedetails-header"),
                                        document.getElementById("closedetails-footer")
                                    ];

                                    function showDetails(id) {
                                        // console.log(modal)
                                        // console.log(modal.getElementsByClassName("data"))
                                        var mydata = undefined
                                        data.forEach(item => {
                                            if (item["redeem_id"] == id) {
                                                mydata = item;
                                            }
                                        })

                                        var keys = Object.keys(mydata)
                                        keys.forEach(key => {
                                            var span = modal.getElementsByClassName(key)
                                            if (span.length > 0)
                                                span[0].innerHTML = mydata[key];
                                        })
                                        modal.style.display = "block";

                                        //class add 
                                        var status = document.getElementById("status")
                                        if (mydata["reviewer_status"] === "Approved") {
                                            status.classList.add("bg-success")
                                            status.classList.remove("bg-danger")
                                        } else {
                                            status.classList.remove("bg-success")
                                            status.classList.add("bg-danger")
                                        }
                                        //class add end

                                        var profile = document.getElementById("redeem_idd")
                                        profile.value = mydata["redeem_id"]
                                        if (mydata["reviewer_status"] !== null) {
                                            profile = document.getElementById("reviewer_status")
                                            profile.value = mydata["reviewer_status"]
                                        }
                                        if (mydata["reviewer_remarks"] !== null) {
                                            profile = document.getElementById("reviewer_remarks")
                                            profile.value = mydata["reviewer_remarks"]
                                        }

                                        if (mydata["reviewer_status"] == 'Approved' || mydata["reviewer_status"] == 'Rejected') {
                                            document.getElementById("redeemupdate").disabled = true;
                                        } else {
                                            document.getElementById("redeemupdate").disabled = false;
                                        }
                                    }
                                    // When the user clicks the button, open the modal 
                                    // When the user clicks on <span> (x), close the modal
                                    closedetails.forEach(function(element) {
                                        element.addEventListener("click", closeModal);
                                    });

                                    function closeModal() {
                                        var modal1 = document.getElementById("myModal");
                                        modal1.style.display = "none";
                                    }
                                    // When the user clicks anywhere outside of the modal, close it
                                    // window.onclick = function(event) {
                                    //     if (event.target == modal) {
                                    //         modal.style.display = "none";
                                    //     }
                                    // }
                                </script>
                                <script>
                                    var data = <?php echo json_encode($resultArr) ?>;
                                    //For form submission - to update Remarks
                                    const scriptURL = 'payment-api.php'

                                    function validateForm() {
                                        if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {

                                            data.forEach(item => {
                                                const form = document.forms['gemsdelete_' + item.redeem_id]
                                                form.addEventListener('submit', e => {
                                                    e.preventDefault()
                                                    fetch(scriptURL, {
                                                            method: 'POST',
                                                            body: new FormData(document.forms['gemsdelete_' + item.redeem_id])
                                                        })
                                                        .then(response =>
                                                            alert("Record has been deleted.") +
                                                            location.reload()
                                                        )
                                                        .catch(error => console.error('Error!', error.message))
                                                })

                                                console.log(item)
                                            })
                                        } else {
                                            alert("Record has NOT been deleted.");
                                            return false;
                                        }
                                    }

                                    const form = document.getElementById('reviewform')
                                    form.addEventListener('submit', e => {
                                        e.preventDefault()
                                        fetch(scriptURL, {
                                                method: 'POST',
                                                body: new FormData(document.getElementById('reviewform'))
                                            })
                                            .then(response =>
                                                alert("Record has been updated.") +
                                                location.reload()
                                            )
                                            .catch(error => console.error('Error!', error.message))
                                    })

                                    data.forEach(item => {
                                        const formId = 'email-form-' + item.redeem_id
                                        const form = document.forms[formId]
                                        form.addEventListener('submit', e => {
                                            e.preventDefault()
                                            fetch('mailer.php', {
                                                    method: 'POST',
                                                    body: new FormData(document.forms[formId])
                                                })
                                                .then(response =>
                                                    alert("Email has been sent.")
                                                )
                                                .catch(error => console.error('Error!', error.message))
                                        })
                                    })
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