<?php
session_start();
// Storing Session
include("../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

date_default_timezone_set('Asia/Kolkata');
?>
<?php if ($role == 'Admin') {

    if ($_POST) {
        @$certificate_no = 'RSC' . time();
        @$awarded_to_id = strtoupper($_POST['awarded_to_id']);
        @$badge_name = $_POST['badge_name'];
        @$comment = $_POST['comment'];
        @$gems = $_POST['gems'];
        @$certificate_url = $_POST['certificate_url'];
        @$issuedby = $_POST['issuedby'];
        @$now = date('Y-m-d H:i:s');
        if ($certificate_no != "") {
            $certificate = "INSERT INTO certificate (certificate_no, issuedon, awarded_to_id, badge_name, comment, gems, certificate_url, issuedby) VALUES ('$certificate_no','$now','$awarded_to_id','$badge_name','$comment', NULLIF('$gems','')::integer,'$certificate_url','$issuedby')";
            $result = pg_query($con, $certificate);
            $cmdtuples = pg_affected_rows($result);
        }
    }

    @$get_certificate_no = strtoupper($_GET['get_certificate_no']);
    @$get_nomineeid = strtoupper($_GET['get_nomineeid']);
    @$is_user = $_GET['is_user'];

    if (($get_certificate_no == null && $get_nomineeid == null)) {

        $result = pg_query($con, "SELECT * FROM certificate  
        left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members) faculty ON certificate.awarded_to_id=faculty.associatenumber
        left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON certificate.awarded_to_id=student.student_id
        order by issuedon desc");
        $totalgemsredeem = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems");
        $totalgemsreceived = pg_query($con, "SELECT COALESCE(SUM(gems),0) FROM certificate");
        $totalgemsredeem_admin = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems where user_id='$associatenumber'AND (reviewer_status is null or reviewer_status !='Rejected')");
        $totalgemsreceived_admin = pg_query($con, "SELECT COALESCE(SUM(gems),0) FROM certificate where awarded_to_id='$associatenumber'");
        $totalgemsredeem_approved = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems");
    }

    if (($get_certificate_no != null)) {

        $result = pg_query($con, "SELECT * FROM certificate left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members) faculty ON certificate.awarded_to_id=faculty.associatenumber where certificate_no='$get_certificate_no' order by issuedon desc");
        $totalgemsredeem = pg_query($con, "SELECT SUM(redeem_gems_point) FROM gems where redeem_id=''");
        $totalgemsreceived = pg_query($con, "SELECT SUM(gems) FROM certificate where certificate_no=''");
        $totalgemsredeem_admin = pg_query($con, "SELECT SUM(redeem_gems_point) FROM gems where redeem_id=''");
        $totalgemsreceived_admin = pg_query($con, "SELECT SUM(gems) FROM certificate where certificate_no=''");
        $totalgemsredeem_approved = pg_query($con, "SELECT SUM(redeem_gems_point) FROM gems where redeem_id=''");
    }

    if (($get_nomineeid != null)) {

        $result = pg_query($con, "SELECT * FROM certificate left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members) faculty ON certificate.awarded_to_id=faculty.associatenumber where awarded_to_id='$get_nomineeid' order by issuedon desc");
        $totalgemsredeem = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems where user_id='$get_nomineeid' AND (reviewer_status is null or reviewer_status !='Rejected')");
        $totalgemsreceived = pg_query($con, "SELECT COALESCE(SUM(gems),0) FROM certificate where awarded_to_id='$get_nomineeid'");
        $totalgemsredeem_admin = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems where user_id='$associatenumber'AND (reviewer_status is null or reviewer_status !='Rejected')");
        $totalgemsreceived_admin = pg_query($con, "SELECT COALESCE(SUM(gems),0) FROM certificate where awarded_to_id='$associatenumber'");
        $totalgemsredeem_approved = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems where user_id='$get_nomineeid' AND reviewer_status='Approved'");
    }

    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);
    $resultArrr = pg_fetch_result($totalgemsreceived, 0, 0);
    $resultArrrr = pg_fetch_result($totalgemsredeem, 0, 0);
    $resultArrr_admin = pg_fetch_result($totalgemsreceived_admin, 0, 0);
    $resultArrrr_admin = pg_fetch_result($totalgemsredeem_admin, 0, 0);
    $gems_approved = pg_fetch_result($totalgemsredeem_approved, 0, 0);
} ?>
<?php if ($role != 'Admin') {

    $result = pg_query($con, "SELECT * FROM certificate where awarded_to_id='$associatenumber' order by issuedon desc");
    $totalgemsredeem = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems where user_id='$associatenumber'AND (reviewer_status is null or reviewer_status !='Rejected')");
    $totalgemsreceived = pg_query($con, "SELECT COALESCE(SUM(gems),0) FROM certificate where awarded_to_id='$associatenumber'");

    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);
    $resultArrr = pg_fetch_result($totalgemsreceived, 0, 0);
    $resultArrrr = pg_fetch_result($totalgemsredeem, 0, 0);
} ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>RSSI-Certificate management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>

    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
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

    <style>
        .checkbox {
            padding: 0;
            margin: 0;
            vertical-align: bottom;
            position: relative;
            top: 0px;
            overflow: hidden;
        }

        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            font-size: x-small;
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <?php if ($role == 'Admin') { ?>
                        <div class="col" style="display: inline-block; width:50%;margin-left:1.5%">Home / <span class="noticea"><a href="document.php">My Document</a></span> / Certificate Management System (CMS)
                        </div>
                    <?php } else { ?>
                        <div class="col" style="display: inline-block; width:50%;margin-left:1.5%">Home / <span class="noticea"><a href="document.php">My Document</a></span> / My Certificate
                        </div>
                    <?php } ?>
                    <div class="col" style="display: inline-block; width:47%; text-align:right">

                        <?php if ($role == 'Admin') { ?>
                            <div class="col" style="display: inline-block; width:47%; text-align:right">

                                <?php if ($resultArrr_admin - $resultArrrr_admin != null) { ?>
                                    <div style="display: inline-block; width:100%; font-size:small; text-align:right;"><i class="fa-regular fa-gem" style="font-size:medium;" title="RSSI Gems"></i>&nbsp;<p class="label label-success"><?php echo ($resultArrr_admin - $resultArrrr_admin) ?></p>
                                    </div>
                                <?php } else { ?>

                                    <i class="fa-regular fa-gem" style="font-size:medium;" title="RSSI Gems"></i>&nbsp;<p class="label label-default">You're almost there</p>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <div class="col" style="display: inline-block; width:47%; text-align:right">

                                <?php if ($resultArrr - $resultArrrr != null) { ?>
                                    <div style="display: inline-block; width:100%; font-size:small; text-align:right;"><i class="fa-regular fa-gem" style="font-size:medium;" title="RSSI Gems"></i>&nbsp;<p class="label label-success"><?php echo ($resultArrr - $resultArrrr) ?></p>
                                    </div>
                                <?php } else { ?>

                                    <i class="fa-regular fa-gem" style="font-size:medium;" title="RSSI Gems"></i>&nbsp;<p class="label label-default">You're almost there</p>
                                <?php } ?>
                            </div>
                        <?php } ?>
                        <br><br>

                        <a href="redeem_gems.php" target="_self" class="btn btn-danger btn-sm" role="button">Redeem Gems</a>
                    </div>
                </div>
                <?php if ($resultArrr == null) { ?>
                <?php } ?>
                <?php if ($role == 'Admin') { ?>
                    <?php if (@$certificate_no != null && @$cmdtuples == 0) { ?>

                        <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                        </div>
                    <?php
                    } else if (@$cmdtuples == 1) { ?>

                        <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Certificate no <?php echo @$certificate_no ?> has been added.</span>
                        </div>
                        <script>
                            if (window.history.replaceState) {
                                window.history.replaceState(null, null, window.location.href);
                            }
                        </script>
                    <?php } ?>
                <?php } ?>
                <div class="row">
                    <section class="box" style="padding: 2%;">

                        <?php if ($role == 'Admin') { ?>

                            <form autocomplete="off" name="cms" id="cms" action="my_certificate.php" method="POST">
                                <div class="form-group" style="display: inline-block;">

                                    <span class="input-help">
                                        <input type="text" name="awarded_to_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Nominee id" value="<?php echo @$_GET['awarded_to_id']; ?>" required>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Nominee id*</small>
                                    </span>
                                    <span class="input-help">
                                        <select name="badge_name" class="form-control" style="width:max-content; display:inline-block" required>
                                            <?php if ($badge_name == null) { ?>
                                                <option value="" disabled selected hidden>Badge name</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $badge_name ?></option>
                                            <?php }
                                            ?>
                                            <option>Certificate Of Appreciation</option>
                                            <option>Certificate Of Appreciation (Smile)</option>
                                            <option>Completion Certificate</option>
                                            <option>Experience Letter</option>
                                            <option>Learning Achievement Award</option>
                                            <option>Provisional Certificate</option>
                                            <option>Service & Commitment Award</option>
                                            <option>Smile</option>
                                            <option>Star Of The Month</option>
                                            <option>Star Of The Quarter</option>
                                            <option>Volunteer Of The Quarter</option>

                                        </select>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Badge name*</small>
                                    </span>

                                    <span class="input-help">
                                        <textarea type="text" name="comment" class="form-control" placeholder="Remarks" value=""></textarea>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Remarks</small>
                                    </span>
                                    <span class="input-help">
                                        <input type="number" name="gems" class="form-control" placeholder="Gems" min="1">
                                        <small id="passwordHelpBlock" class="form-text text-muted">Gems</small>
                                    </span>

                                    <span class="input-help">
                                        <input type="url" name="certificate_url" class="form-control" placeholder="Certificate url" value="">
                                        <small id="passwordHelpBlock" class="form-text text-muted">Certificate url</small>
                                    </span>

                                    <input type="hidden" name="issuedby" class="form-control" placeholder="Issued by" value="<?php echo $fullname ?>" required readonly>

                                    <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                        <i class="fa-solid fa-plus"></i>&nbsp;&nbsp;Add</button>

                                </div>

                            </form>

                            <?php if ($get_nomineeid != null) { ?>
                                <div class="col" style="display: inline-block; width:100%; text-align:right">


                                    <div style="display: inline-block; width:100%; font-size:small; text-align:right;">Total Gems:&nbsp;
                                        <p class="label label-default"><?php echo $resultArrr ?></p><br>

                                        Balance:&nbsp;
                                        <?php if ($resultArrr - $gems_approved <= 0) { ?>
                                            <p class="label label-danger"><?php echo ($resultArrr - $gems_approved) ?></p><br><br>
                                        <?php } else { ?>

                                            <p class="label label-info"><?php echo ($resultArrr - $gems_approved) ?></p><br><br>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                            <div style="display: inline-block; width:100%; font-size:small; text-align:right;">Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                            </div>


                        <?php } ?>

                        <?php if ($role == 'Admin') { ?>

                            <form action="" method="GET">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">
                                        <input name="get_certificate_no" id="get_certificate_no" class="form-control" style="width:max-content; display:inline-block" placeholder="Certificate no" value="<?php echo $get_certificate_no ?>">
                                    </div>
                                    <div class="col2" style="display: inline-block;">
                                        <input name="get_nomineeid" id="get_nomineeid" class="form-control" style="width:max-content; display:inline-block" placeholder="Nominee id" value="<?php echo $get_nomineeid ?>">
                                    </div>
                                </div>
                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                        <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                                </div>
                                <div id="filter-checks">
                                    <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_GET['is_user'])) echo "checked='checked'"; ?> />
                                    <label for="is_user" style="font-weight: 400;">Search by Nominee id</label>
                                </div>
                            </form>
                            <script>
                                if ($('#is_user').not(':checked').length > 0) {

                                    document.getElementById("get_certificate_no").disabled = false;
                                    document.getElementById("get_nomineeid").disabled = true;

                                } else {

                                    document.getElementById("get_certificate_no").disabled = true;
                                    document.getElementById("get_nomineeid").disabled = false;

                                }

                                const checkbox = document.getElementById('is_user');

                                checkbox.addEventListener('change', (event) => {
                                    if (event.target.checked) {
                                        document.getElementById("get_certificate_no").disabled = true;
                                        document.getElementById("get_nomineeid").disabled = false;
                                    } else {
                                        document.getElementById("get_certificate_no").disabled = false;
                                        document.getElementById("get_nomineeid").disabled = true;
                                    }
                                })
                            </script>

                        <?php } ?>

                        <?php echo '
                    <p>Select Number Of Rows</p>
                    <div class="form-group">
                        <select class="form-control" name="state" id="maxRows">
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
                    <table class="table" id="table-id">
                        <thead>
                            <tr>
                            <th scope="col">Certificate no</th>' ?>
                        <?php if ($role == 'Admin') { ?>
                            <?php echo '<th scope="col">Nominee Details</th>' ?>
                        <?php } ?>
                        <?php echo ' <th scope="col">Badge name</th>
                            <th scope="col" width="20%">Remarks</th>
                            <th scope="col">Gems</th>
                            <th scope="col">Issued on</th>
                            <th scope="col">Issued by</th>
                            <th scope="col">Certificate</th>' ?>
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
                                <td>' . $array['certificate_no'] . '</td>' ?>
                                <?php if ($role == 'Admin') { ?>
                                    <?php echo '<td>' . $array['awarded_to_id'] . '<br>' . @$array['fullname'] . @$array['studentname'] . '</td>' ?>
                                <?php } ?>
                                <?php echo '<td>' . $array['badge_name'] . '</td><td>' ?>

                                <?php if (@strlen($array['comment']) <= 90) {

                                    echo $array['comment'] ?>

                                <?php } else { ?>

                                    <?php echo substr($array['comment'], 0, 90) .
                                        '&nbsp;...&nbsp;<button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['certificate_no'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i></button>' ?>
                                <?php } ?>


                                <?php echo '</td><td>' . $array['gems'] . '</td>' ?>
                                <?php if ($array['issuedon'] == null) { ?>
                                    <?php echo '<td></td>' ?>
                                <?php } else { ?>
                                    <?php echo '<td>' . @date("d/m/Y g:i a", strtotime($array['issuedon'])) . '</td>' ?>
                                <?php } ?>

                                <?php echo '<td>' . $array['issuedby'] . '</td>' ?>

                                <?php if ($array['certificate_url'] == null) { ?>
                                    <?php echo '<td></td>' ?>

                                <?php } else { ?>

                                    <?php echo '<td><a href="' . $array['certificate_url'] . '" target="_blank"><i class="fa-regular fa-file-pdf" style="font-size: 16px ;color:#777777" title="' . $array['certificate_no'] . '" display:inline;></i></a></td>' ?>
                                <?php } ?>

                                <?php if ($role == 'Admin') { ?>

                                    <?php echo '

                                <td>' ?>
                                    <?php if ($array['phone'] != null || $array['contact'] != null) { ?>
                                        <?php echo '<a href="https://api.whatsapp.com/send?phone=91' . $array['phone'] . $array['contact'] . '&text=Dear ' . $array['fullname'] . $array['studentname'] . ' (' . $array['awarded_to_id'] . '),%0A%0AYou have received ' . $array['badge_name'] . '. To view your e-Certificate and Gems (if applicable), please log on to your Profile > My Documents > My Certificate or you can click on the link below to access it directly.%0A%0Ahttps://login.rssi.in/rssi-member/my_certificate.php?get_nomineeid=' . $array['awarded_to_id'] . '%0A%0A--RSSI%0A%0A**This is an automatically generated SMS
                                " target="_blank"><i class="fa-brands fa-whatsapp" style="color:#444444;" title="Send SMS ' . $array['phone'] . $array['contact'] . '"></i></a>' ?>
                                    <?php } else { ?>
                                        <?php echo '<i class="fa-brands fa-whatsapp" style="color:#A2A2A2;" title="Send SMS"></i>' ?>
                                        <?php } ?>&nbsp;&nbsp;


                                        <?php if ($array['email'] != null || $array['emailaddress'] != null) { ?>
                                            <?php echo '<form  action="#" name="email-form-' . $array['certificate_no'] . '" method="POST" style="display: -webkit-inline-box;" >
                                    <input type="hidden" name="template" type="text" value="badge">
                                    <input type="hidden" name="data[badge_name]" type="text" value="' . $array['badge_name'] . '">
                                    <input type="hidden" name="data[awarded_to_id]" type="text" value="' . $array['awarded_to_id'] . '">
                                    <input type="hidden" name="data[fullname]" type="text" value="' . $array['fullname'] . $array['studentname'] . '">
                                    <input type="hidden" name="email" type="text" value="' . $array['email'] . $array['emailaddress'] . '">
                                    <button  style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;"
                                     type="submit"><i class="fa-regular fa-envelope" style="color:#444444;" title="Send Email ' . $array['email'] . $array['emailaddress'] . '"></i></button>
                                </form>' ?>
                                        <?php } else { ?>
                                            <?php echo '<i class="fa-regular fa-envelope" style="color:#A2A2A2;" title="Send Email"></i>' ?>
                                        <?php } ?>

                                        <?php echo '&nbsp;&nbsp;<form name="cmsdelete_' . $array['certificate_no'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="cmsdelete">
                                <input type="hidden" name="cmsid" id="cmsid" type="text" value="' . $array['certificate_no'] . '">
                                
                                <button type="submit" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['certificate_no'] . '"><i class="fa-solid fa-xmark"></i></button> </form>
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
                    </table>'
                            ?>


                            <!--		Start Pagination -->
                            <div class='pagination-container'>
                                <nav>
                                    <ul class="pagination">

                                        <li data-page="prev">
                                            <span>
                                                < <span class="sr-only">(current)
                                            </span></span>
                                        </li>
                                        <!--	Here the JS Function Will Add the Rows -->
                                        <li data-page="next" id="prev">
                                            <span> > <span class="sr-only">(current)</span></span>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                    </section>

                    <script>
                        var data = <?php echo json_encode($resultArr) ?>;
                        const scriptURL = 'payment-api.php'

                        function validateForm() {
                            if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {

                                data.forEach(item => {
                                    const form = document.forms['cmsdelete_' + item.certificate_no]
                                    form.addEventListener('submit', e => {
                                        e.preventDefault()
                                        fetch(scriptURL, {
                                                method: 'POST',
                                                body: new FormData(document.forms['cmsdelete_' + item.certificate_no])
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


                        data.forEach(item => {
                            const formId = 'email-form-' + item.certificate_no
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

                    <script>
                        getPagination('#table-id');

                        function getPagination(table) {
                            var lastPage = 1;

                            $('#maxRows')
                                .on('change', function(evt) {
                                    //$('.paginationprev').html('');						// reset pagination

                                    lastPage = 1;
                                    $('.pagination')
                                        .find('li')
                                        .slice(1, -1)
                                        .remove();
                                    var trnum = 0; // reset tr counter
                                    var maxRows = parseInt($(this).val()); // get Max Rows from select option

                                    if (maxRows == 5000) {
                                        $('.pagination').hide();
                                    } else {
                                        $('.pagination').show();
                                    }

                                    var totalRows = $(table + ' tbody tr').length; // numbers of rows
                                    $(table + ' tr:gt(0)').each(function() {
                                        // each TR in  table and not the header
                                        trnum++; // Start Counter
                                        if (trnum > maxRows) {
                                            // if tr number gt maxRows

                                            $(this).hide(); // fade it out
                                        }
                                        if (trnum <= maxRows) {
                                            $(this).show();
                                        } // else fade in Important in case if it ..
                                    }); //  was fade out to fade it in
                                    if (totalRows > maxRows) {
                                        // if tr total rows gt max rows option
                                        var pagenum = Math.ceil(totalRows / maxRows); // ceil total(rows/maxrows) to get ..
                                        //	numbers of pages
                                        for (var i = 1; i <= pagenum;) {
                                            // for each page append pagination li
                                            $('.pagination #prev')
                                                .before(
                                                    '<li data-page="' +
                                                    i +
                                                    '">\
								  <span>' +
                                                    i++ +
                                                    '<span class="sr-only">(current)</span></span>\
								</li>'
                                                )
                                                .show();
                                        } // end for i
                                    } // end if row count > max rows
                                    $('.pagination [data-page="1"]').addClass('active'); // add active class to the first li
                                    $('.pagination li').on('click', function(evt) {
                                        // on click each page
                                        evt.stopImmediatePropagation();
                                        evt.preventDefault();
                                        var pageNum = $(this).attr('data-page'); // get it's number

                                        var maxRows = parseInt($('#maxRows').val()); // get Max Rows from select option

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
                                        var trIndex = 0; // reset tr counter
                                        $('.pagination li').removeClass('active'); // remove active class from all li
                                        $('.pagination [data-page="' + lastPage + '"]').addClass('active'); // add active class to the clicked
                                        // $(this).addClass('active');					// add active class to the clicked
                                        limitPagging();
                                        $(table + ' tr:gt(0)').each(function() {
                                            // each tr in table not the header
                                            trIndex++; // tr index counter
                                            // if tr index gt maxRows*pageNum or lt maxRows*pageNum-maxRows fade if out
                                            if (
                                                trIndex > maxRows * pageNum ||
                                                trIndex <= maxRows * pageNum - maxRows
                                            ) {
                                                $(this).hide();
                                            } else {
                                                $(this).show();
                                            } //else fade in
                                        }); // end of for each tr in table
                                    }); // end of on click pagination list
                                    limitPagging();
                                })
                                .val(5)
                                .change();

                            // end of on select change

                            // END OF PAGINATION
                        }

                        function limitPagging() {
                            // alert($('.pagination li').length)

                            if ($('.pagination li').length > 7) {
                                if ($('.pagination li.active').attr('data-page') <= 3) {
                                    $('.pagination li:gt(5)').hide();
                                    $('.pagination li:lt(5)').show();
                                    $('.pagination [data-page="next"]').show();
                                }
                                if ($('.pagination li.active').attr('data-page') > 3) {
                                    $('.pagination li:gt(0)').hide();
                                    $('.pagination [data-page="next"]').show();
                                    for (let i = (parseInt($('.pagination li.active').attr('data-page')) - 2); i <= (parseInt($('.pagination li.active').attr('data-page')) + 2); i++) {
                                        $('.pagination [data-page="' + i + '"]').show();

                                    }

                                }
                            }
                        }
                    </script>
                    <!--------------- POP-UP BOX ------------
-------------------------------------->
                    <style>
                        .modal {
                            display: none;
                            /* Hidden by default */
                            position: fixed;
                            /* Stay in place */
                            z-index: 100;
                            /* Sit on top */
                            padding-top: 100px;
                            /* Location of the box */
                            left: 0;
                            top: 0;
                            width: 100%;
                            /* Full width */
                            height: 100%;
                            /* Full height */
                            overflow: auto;
                            /* Enable scroll if needed */
                            background-color: rgb(0, 0, 0);
                            /* Fallback color */
                            background-color: rgba(0, 0, 0, 0.4);
                            /* Black w/ opacity */
                        }

                        /* Modal Content */

                        .modal-content {
                            background-color: #fefefe;
                            margin: auto;
                            padding: 20px;
                            border: 1px solid #888;
                            width: 100vh;
                        }

                        @media (max-width:767px) {
                            .modal-content {
                                width: 50vh;
                            }
                        }

                        /* The Close Button */

                        .close {
                            color: #aaaaaa;
                            float: right;
                            font-size: 28px;
                            font-weight: bold;
                            text-align: right;
                        }

                        .close:hover,
                        .close:focus {
                            color: #000;
                            text-decoration: none;
                            cursor: pointer;
                        }
                    </style>

                    <div id="myModal" class="modal">

                        <!-- Modal content -->
                        <div class="modal-content">
                            <span class="close">&times;</span>

                            <div style="width:100%; text-align:right">
                                <p class="label label-info" style="display: inline !important;"><span class="certificate_no"></span></p>
                            </div>


                            <p style="font-size: small;">
                                <span class="comment"></span>
                            </p>
                        </div>

                    </div>
                    <script>
                        var data = <?php echo json_encode($resultArr) ?>

                        // Get the modal
                        var modal = document.getElementById("myModal");
                        // Get the <span> element that closes the modal
                        var span = document.getElementsByClassName("close")[0];

                        function showDetails(id) {
                            // console.log(modal)
                            // console.log(modal.getElementsByClassName("data"))
                            var mydata = undefined
                            data.forEach(item => {
                                if (item["certificate_no"] == id) {
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
                        }
                        // When the user clicks the button, open the modal 
                        // When the user clicks on <span> (x), close the modal
                        span.onclick = function() {
                            modal.style.display = "none";
                        }
                        // When the user clicks anywhere outside of the modal, close it
                        window.onclick = function(event) {
                            if (event.target == modal) {
                                modal.style.display = "none";
                            }
                        }
                    </script>

                    <!-- Back top -->
                    <script>
                        $(document).ready(function() {
                            $(window).scroll(function() {
                                if ($(this).scrollTop() > 50) {
                                    $('#back-to-top').fadeIn();
                                } else {
                                    $('#back-to-top').fadeOut();
                                }
                            });
                            // scroll body to 0px on click
                            $('#back-to-top').click(function() {
                                $('body,html').animate({
                                    scrollTop: 0
                                }, 400);
                                return false;
                            });
                        });
                    </script>
                    <a id="back-to-top" href="#" class="go-top" role="button"><i class="fa fa-angle-up"></i></a>
</body>

</html>