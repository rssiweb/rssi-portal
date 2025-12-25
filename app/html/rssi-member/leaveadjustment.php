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

include("../../util/email.php");

@$now = date('Y-m-d H:i:s');
if ($role == "Admin" && $filterstatus == 'Active') {
    if (@$_POST['form-type'] == "leaveadj") {
        @$leaveadjustmentid = 'RSD' . time();
        @$adj_applicantid = strtoupper($_POST['adj_applicantid']);
        @$adj_fromdate = $_POST['adj_fromdate'];
        @$adj_todate = $_POST['adj_todate'];
        @$day = round((strtotime($_POST['adj_todate']) - strtotime($_POST['adj_fromdate'])) / (60 * 60 * 24) + 1);
        @$adj_leavetype = $_POST['adj_leavetype'];
        @$adj_day = $_POST['adj_day'];
        @$adj_reason = $_POST['adj_reason'];
        @$adj_academicyear = $_POST['adj_academicyear'];
        @$adj_appliedby = $associatenumber;
        @$adj_appliedby_name = $fullname;

        if ($leaveadjustmentid != "") {

            if ($adj_fromdate != "" && $adj_todate != "") {

                $leaveadjustment = "INSERT INTO leaveadjustment (adj_regdate,leaveadjustmentid,adj_applicantid,adj_fromdate,adj_todate,adj_reason,adj_leavetype,adj_appliedby,adj_academicyear,adj_day,adj_appliedby_name) VALUES ('$now','$leaveadjustmentid','$adj_applicantid','$adj_fromdate','$adj_todate','$adj_reason','$adj_leavetype','$adj_appliedby','$adj_academicyear','$day','$adj_appliedby_name')";
            } else {
                $leaveadjustment = "INSERT INTO leaveadjustment (adj_regdate,leaveadjustmentid,adj_applicantid,adj_reason,adj_leavetype,adj_appliedby,adj_academicyear,adj_day,adj_appliedby_name) VALUES ('$now','$leaveadjustmentid','$adj_applicantid','$adj_reason','$adj_leavetype','$adj_appliedby','$adj_academicyear','$adj_day','$adj_appliedby_name')";
            }
            $result = pg_query($con, $leaveadjustment);
            $cmdtuples = pg_affected_rows($result);


            $resultt = pg_query($con, "Select fullname,email from rssimyaccount_members where associatenumber='$adj_applicantid'");
            @$nameassociate = pg_fetch_result($resultt, 0, 0);
            @$emailassociate = pg_fetch_result($resultt, 0, 1);

            $resulttt = pg_query($con, "Select studentname,emailaddress from rssimyprofile_student where student_id='$adj_applicantid'");
            @$namestudent = pg_fetch_result($resulttt, 0, 0);
            @$emailstudent = pg_fetch_result($resulttt, 0, 1);

            $emailfullname = $nameassociate . $namestudent;
            $emailemail = $emailassociate . $emailstudent;

            $emailadj_fromdate = "undefined";
            $emailadj_todate = "undefined";
            $emailadj_day = "undefined";
            if (@$cmdtuples == 1 && $adj_day == "") {

                $emailadj_fromdate = @date("d/m/Y", strtotime($adj_fromdate));
                $emailadj_todate = @date("d/m/Y", strtotime($adj_todate));
                $emailadj_day = $day;
            }
            if (@$cmdtuples == 1 && $adj_day != "") {

                $emailadj_fromdate = null;
                $emailadj_todate = null;
                $emailadj_day = $adj_day;
            }


            if (@$cmdtuples == 1 && $emailemail != "") {
                sendEmail("leaveadjustment", array(
                    "leaveadjustmentid" => $leaveadjustmentid,
                    "adj_applicantid" => $adj_applicantid,
                    "adj_applicantname" => $emailfullname,
                    "adj_fromdate" => $emailadj_fromdate,
                    "adj_todate" => $emailadj_todate,
                    "adj_day" => $emailadj_day,
                    "adj_leavetype" => $adj_leavetype,
                    "now" => @date("d/m/Y g:i a", strtotime($now)),
                    "adj_appliedby" => $adj_appliedby,
                    "adj_reason" => $adj_reason,
                ), $emailemail, False);
            }
        }
    }
}

@$id = $_GET['leaveadjustmentid'];
@$appid = strtoupper($_GET['adj_applicantid_search']);
@$adj_academicyear_search = $_GET['adj_academicyear_search'];
@$is_user = $_GET['is_user'];

date_default_timezone_set('Asia/Kolkata');
// $date = date('Y-d-m h:i:s');

if ($role == "Admin" && $filterstatus == 'Active') {

    if ($appid != null && $adj_academicyear_search != null) {
        $result = pg_query($con, "select * from leaveadjustment left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leaveadjustment.adj_applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leaveadjustment.adj_applicantid=student.student_id WHERE adj_applicantid='$appid' AND adj_academicyear='$adj_academicyear_search' order by adj_regdate desc");
    } else if ($id != null) {
        $result = pg_query($con, "select * from leaveadjustment left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leaveadjustment.adj_applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leaveadjustment.adj_applicantid=student.student_id WHERE leaveadjustmentid='$id' order by adj_regdate desc");
    } else {
        $result = pg_query($con, "select * from leaveadjustment left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leaveadjustment.adj_applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leaveadjustment.adj_applicantid=student.student_id order by adj_regdate desc");
    }
} else {

    if ($id == null && $adj_academicyear_search == null) {
        $result = pg_query($con, "select * from leaveadjustment left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leaveadjustment.adj_applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leaveadjustment.adj_applicantid=student.student_id where adj_applicantid='$associatenumber' order by adj_regdate desc");
    } else if ($id == null && $adj_academicyear_search != null) {
        $result = pg_query($con, "select * from leaveadjustment left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leaveadjustment.adj_applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leaveadjustment.adj_applicantid=student.student_id  WHERE adj_applicantid='$associatenumber' AND adj_academicyear='$adj_academicyear_search' order by adj_regdate desc");
    } else if ($id != null && $adj_academicyear_search != null) {
        $result = pg_query($con, "select * from leaveadjustment left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leaveadjustment.adj_applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leaveadjustment.adj_applicantid=student.student_id WHERE adj_applicantid='$associatenumber' AND leaveadjustmentid='$id' AND adj_academicyear='$adj_academicyear_search' order by adj_regdate desc");
    }
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
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

    <title>Leave Adjustment</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
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

</head>

<body>
<?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Leave Adjustment</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">My Services</a></li>
                    <li class="breadcrumb-item"><a href="leave.php">Apply for Leave</a></li>
                    <li class="breadcrumb-item active">Leave Adjustment</li>
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
                            <div class="row">
                                <?php if (@$leaveadjustmentid != null && @$cmdtuples == 0) { ?>
                                    <div class="alert alert-danger alert-dismissible text-center" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>ERROR: Oops, something wasn't right.</span>
                                    </div>
                                <?php } else if (@$cmdtuples == 1) { ?>
                                    <div class="alert alert-success alert-dismissible text-center" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-check2-circle"></i>
                                        <span>Your request has been submitted. Leave adjustment id <?php echo $leaveadjustmentid ?>.</span>
                                    </div>
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                                <?php } ?>

                                <?php if ($role == "Admin" && $filterstatus == 'Active') { ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col" colspan="2">Apply Leave Adjustment</th>
                                            </tr>
                                        </thead>
                                    </table>

                                    <form autocomplete="off" name="leaveadj" id="leaveadj" action="leaveadjustment.php" method="POST">
                                        <div class="form-group" style="display: inline-block;">

                                            <input type="hidden" name="form-type" type="text" value="leaveadj">

                                            <span class="input-help">
                                                <input type="text" name="adj_applicantid" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo @$_GET['adj_applicantid']; ?>" required>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Applicant ID*</small>
                                            </span>
                                            <span class="input-help">
                                                <input type="date" class="form-control" name="adj_fromdate" id="adj_fromdate" max="" onchange="cal();" required>
                                                <small id="passwordHelpBlock_from" class="form-text text-muted">From*</small>
                                            </span>
                                            <span class="input-help">
                                                <input type="date" class="form-control" name="adj_todate" id="adj_todate" min="" onchange="cal();" required>
                                                <small id="passwordHelpBlock_to" class="form-text text-muted">To*</small>
                                            </span>
                                            <span class="input-help">
                                                <input type="number" name="adj_day" id='adj_day' class="form-control" placeholder="Day count" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" required>
                                                <small id="passwordHelpBlock_dayadjusted" class="form-text text-muted">No of day adjusted*</small>
                                            </span>
                                            <span class="input-help">
                                                <select name="adj_leavetype" id="adj_leavetype" class="form-select" style="display: -webkit-inline-box; width:20vh; " required>
                                                    <option disabled selected hidden>Types of Leave</option>
                                                    <option value="Sick Leave">Sick Leave</option>
                                                    <option value="Casual Leave">Casual Leave</option>
                                                    <option value="Leave Without Pay">Leave Without Pay</option>
                                                    <option value="Adjustment Leave">Adjustment Leave</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Adjusted Leave Type</small>
                                            </span>
                                            <span class="input-help">
                                                <select name="adj_academicyear" id="adj_academicyear" class="form-select" style="display: -webkit-inline-box; width:20vh; " required>
                                                    <option disabled selected hidden>Academic Year</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Academic Year</small>
                                            </span>

                                            <span class="input-help">
                                                <textarea type="text" name="adj_reason" class="form-control" placeholder="Remarks" value=""></textarea>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Remarks</small>
                                            </span>
                                        </div>
                                        <div class="col2 left" style="display: inline-block;">
                                            <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">Adjust Leave</button>

                                        </div>

                                        <div id="filter-checkss">
                                            <input class="form-check-input" type="checkbox" name="is_users" id="is_users" value="1" <?php if (isset($_GET['is_users'])) echo "checked='checked'"; ?> />
                                            <label for="is_users" style="font-weight: 400;">Adjust Leave With Salary/Other</label>
                                        </div>

                                    </form>
                                    <script>
                                        if ($('#is_users').not(':checked').length > 0) {

                                            document.getElementById("adj_day").disabled = true;
                                            $('#adj_day').get(0).type = 'hidden';
                                            document.getElementById("passwordHelpBlock_dayadjusted").classList.add("d-none");

                                            document.getElementById("adj_fromdate").disabled = false;
                                            document.getElementById("adj_todate").disabled = false;
                                            $('#adj_fromdate').get(0).type = 'date';
                                            $('#adj_todate').get(0).type = 'date';
                                            document.getElementById("passwordHelpBlock_from").classList.remove("d-none");
                                            document.getElementById("passwordHelpBlock_to").classList.remove("d-none");

                                        } else {

                                            document.getElementById("adj_day").disabled = false;
                                            $('#adj_day').get(0).type = 'number';
                                            document.getElementById("passwordHelpBlock_dayadjusted").classList.remove("d-none");
                                            document.getElementById("adj_fromdate").disabled = true;
                                            document.getElementById("adj_todate").disabled = true;
                                            $('#adj_fromdate').get(0).type = 'hidden';
                                            $('#adj_todate').get(0).type = 'hidden';
                                            document.getElementById("passwordHelpBlock_from").classList.add("d-none");
                                            document.getElementById("passwordHelpBlock_to").classList.add("d-none");

                                        }

                                        const checkboxs = document.getElementById('is_users');

                                        checkboxs.addEventListener('change', (event) => {
                                            if (event.target.checked) {
                                                document.getElementById("adj_day").disabled = false;
                                                $('#adj_day').get(0).type = 'number';
                                                document.getElementById("passwordHelpBlock_dayadjusted").classList.remove("d-none");
                                                document.getElementById("adj_fromdate").disabled = true;
                                                document.getElementById("adj_todate").disabled = true;
                                                $('#adj_fromdate').get(0).type = 'hidden';
                                                $('#adj_todate').get(0).type = 'hidden';
                                                document.getElementById("passwordHelpBlock_from").classList.add("d-none");
                                                document.getElementById("passwordHelpBlock_to").classList.add("d-none");
                                            } else {
                                                document.getElementById("adj_day").disabled = true;
                                                $('#adj_day').get(0).type = 'hidden';
                                                document.getElementById("passwordHelpBlock_dayadjusted").classList.add("d-none");
                                                document.getElementById("adj_fromdate").disabled = false;
                                                document.getElementById("adj_todate").disabled = false;
                                                $('#adj_fromdate').get(0).type = 'date';
                                                $('#adj_todate').get(0).type = 'date';
                                                document.getElementById("passwordHelpBlock_from").classList.remove("d-none");
                                                document.getElementById("passwordHelpBlock_to").classList.remove("d-none");
                                            }
                                        })

                                        function cal() {
                                            if (document.getElementById("adj_todate") || document.getElementById("adj_fromdate")) {
                                                document.getElementById("adj_todate").min = document.getElementById("adj_fromdate").value;
                                                document.getElementById("adj_fromdate").max = document.getElementById("adj_todate").value;
                                            }
                                        }
                                    </script>
                                    <script>
                                        <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
                                            var currentYear = new Date().getFullYear() - 1;
                                        <?php } else { ?>
                                            var currentYear = new Date().getFullYear();
                                        <?php } ?>
                                        for (var i = 0; i < 5; i++) {
                                            var next = currentYear + 1;
                                            var year = currentYear + '-' + next;
                                            //next.toString().slice(-2)
                                            $('#adj_academicyear').append(new Option(year, year));
                                            currentYear--;
                                        }
                                    </script>
                                <?php } ?>
                                <table class="table" style="margin-top: 2%;">
                                    <thead>
                                        <tr>
                                            <th scope="col" colspan="2">Leave Adjustment Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <form action="" method="GET">
                                                    <div class="form-group" style="display: inline-block;">
                                                        <div class="col2" style="display: inline-block;">
                                                            <input name="leaveadjustmentid" id="leaveadjustmentid" class="form-control" style="width:max-content; display:inline-block" placeholder="Leave Adjustment ID" value="<?php echo $id ?>">
                                                            <?php if ($role == "Admin" && $filterstatus == 'Active') { ?>
                                                                <input name="adj_applicantid_search" id="adj_applicantid_search" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo $appid ?>">
                                                            <?php } ?>
                                                            <select name="adj_academicyear_search" id="adj_academicyear_search" class="form-select" style="width:max-content; display:inline-block" placeholder="Appraisal type" required>
                                                                <?php if ($adj_academicyear_search == null) { ?>
                                                                    <option disabled selected hidden>Academic Year</option>
                                                                <?php
                                                                } else { ?>
                                                                    <option hidden selected><?php echo $adj_academicyear_search ?></option>
                                                                <?php }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col2 left" style="display: inline-block;">
                                                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                                            <i class="bi bi-search"></i>&nbsp;Search</button>
                                                    </div>
                                                    <?php if ($role == "Admin" && $filterstatus == 'Active') { ?>
                                                        <div id="filter-checks">
                                                            <input class="form-check-input" type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_POST['is_user'])) echo "checked='checked'"; ?> />
                                                            <label for="is_user" style="font-weight: 400;">Search by Leave Adjustment ID</label>
                                                        </div>
                                                    <?php } ?>
                                                </form>
                                                <?php if ($role == "Admin" && $filterstatus == 'Active') { ?>
                                                    <script>
                                                        if ($('#is_user').not(':checked').length > 0) {

                                                            document.getElementById("leaveadjustmentid").disabled = true;
                                                            document.getElementById("adj_applicantid_search").disabled = false;
                                                            document.getElementById("adj_academicyear_search").disabled = false;

                                                        } else {

                                                            document.getElementById("leaveadjustmentid").disabled = false;
                                                            document.getElementById("adj_applicantid_search").disabled = true;
                                                            document.getElementById("adj_academicyear_search").disabled = true;

                                                        }

                                                        const checkbox = document.getElementById('is_user');

                                                        checkbox.addEventListener('change', (event) => {
                                                            if (event.target.checked) {
                                                                document.getElementById("leaveadjustmentid").disabled = false;
                                                                document.getElementById("adj_applicantid_search").disabled = true;
                                                                document.getElementById("adj_academicyear_search").disabled = true;
                                                            } else {
                                                                document.getElementById("leaveadjustmentid").disabled = true;
                                                                document.getElementById("adj_applicantid_search").disabled = false;
                                                                document.getElementById("adj_academicyear_search").disabled = false;
                                                            }
                                                        })
                                                    </script>
                                                <?php } ?>
                                                <script>
                                                    <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
                                                        var currentYear = new Date().getFullYear() - 1;
                                                    <?php } else { ?>
                                                        var currentYear = new Date().getFullYear();
                                                    <?php } ?>
                                                    for (var i = 0; i < 5; i++) {
                                                        var next = currentYear + 1;
                                                        var year = currentYear + '-' + next;
                                                        //next.toString().slice(-2)
                                                        $('#adj_academicyear_search').append(new Option(year, year));
                                                        currentYear--;
                                                    }
                                                </script>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="col" style="display: inline-block; width:100%; text-align:right;">
                                    Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                                </div>

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
                                <th scope="col">Leave adjustment id</th>
                                <th scope="col">Applicant ID</th>
                                <th scope="col">Applied on</th>
                                <th scope="col">Adjusted on</th>
                                <th scope="col">No of day(s) adjusted</th>
                                <th scope="col">Adjusted Leave Type</th>
                                <th scope="col">Reviewer</th>
                                <th scope="col" width="15%">Remarks</th>' ?>
                                <?php if ($role == "Admin" && $filterstatus == 'Active') { ?>
                                    <?php echo '<th scope="col"></th>' ?>
                                <?php } ?>
                                </tr>
                                <?php echo '</thead>' ?>
                                <?php if (sizeof($resultArr) > 0) { ?>
                                    <?php
                                    echo '<tbody>';
                                    foreach ($resultArr as $array) {
                                        echo '<tr>'
                                    ?>
                                        <?php
                                        echo '<td>' . $array['leaveadjustmentid'] . '</td>
                                <td>' . $array['adj_applicantid'] . '<br>' . $array['fullname'] . $array['studentname'] . '</td>
                                <td>' . @date("d/m/Y g:i a", strtotime($array['adj_regdate'])) . '</td>' ?>
                                        <?php if ($array['adj_fromdate'] != "" && $array['adj_todate'] != "") { ?>
                                            <?php echo '<td>' .  @date("d/m/Y", strtotime($array['adj_fromdate'])) . '—' .  @date("d/m/Y", strtotime($array['adj_todate'])) . '</td>' ?>
                                        <?php } else { ?>
                                            <?php echo '<td></td>' ?>
                                        <?php } ?>

                                        <?php echo '<td>' . $array['adj_day'] . '</td>
                                <td>' . $array['adj_leavetype'] . '/' . $array['adj_academicyear'] . '</td>
                                <td>' . $array['adj_appliedby'] . '<br>' . $array['adj_appliedby_name'] . '</td>
                                <td>' . $array['adj_reason'] . '</td>' ?>

                                        <?php if ($role == "Admin" && $filterstatus == 'Active') { ?>

                                            <?php if (($array['phone'] != null || $array['contact'] != null)) { ?>
                                                <?php echo '<td><a href="https://api.whatsapp.com/send?phone=91' . $array['phone'] . $array['contact'] . '&text=Dear ' . $array['fullname'] . $array['studentname'] . ' (' . $array['adj_applicantid'] . '),%0A%0AYour ' . $array['adj_day'] . ' day(s) ' . $array['adj_leavetype'] . ' has been adjusted in the system. Please check your registered email for more details.%0A%0AYou can always check your leave adjustment details from My Account>Leave>Leave adjustment. For further information, you may contact your HR.%0A%0A--RSSI%0A%0A**This is an automatically generated SMS
                                " target="_blank"><i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS ' . $array['phone'] . $array['contact'] . '"></i></a>' ?>
                                            <?php } else { ?>
                                                <?php echo '<td><i class="bi bi-whatsapp" style="color:#A2A2A2;" title="Send SMS"></i>' ?>
                                            <?php } ?>

                                            <?php echo '&nbsp;&nbsp;<form name="leaveadjdelete_' . $array['leaveadjustmentid'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                    <input type="hidden" name="form-type" type="text" value="leaveadjdelete">
                                    <input type="hidden" name="leaveadjdeleteid" id="leaveadjdeleteid" type="text" value="' . $array['leaveadjustmentid'] . '">

                                    <button type="submit" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['leaveadjustmentid'] . '"><i class="bi bi-x-lg"></i></button>
                                </form></td>' ?>
                                        <?php } ?>
                                    <?php } ?>
                                <?php
                                } else if ($id == null && $adj_academicyear_search == null) {
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

                                <script>
                                    var data = <?php echo json_encode($resultArr) ?>;
                                    const scriptURL = 'payment-api.php'

                                    function validateForm() {
                                        if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {

                                            data.forEach(item => {
                                                const form = document.forms['leaveadjdelete_' + item.leaveadjustmentid]
                                                form.addEventListener('submit', e => {
                                                    e.preventDefault()
                                                    fetch(scriptURL, {
                                                            method: 'POST',
                                                            body: new FormData(document.forms['leaveadjdelete_' + item.leaveadjustmentid])
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