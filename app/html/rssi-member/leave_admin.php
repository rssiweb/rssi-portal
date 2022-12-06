<?php
session_start();
// Storing Session
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

if ($role != 'Admin') {
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
include("../../util/email.php");
if (date('m') <= 4) { //Upto June 2014-2015
    $academic_year = (date('Y') - 1) . '-' . date('Y');
} else { //After June 2015-2016
    $academic_year = date('Y') . '-' . (date('Y') + 1);
}

@$now = date('Y-m-d H:i:s');
@$year = $academic_year;

if (@$_POST['form-type'] == "leaveapply") {
    @$leaveid = 'RSL' . time();
    @$applicantid = strtoupper($_POST['applicantid']);
    @$fromdate = $_POST['fromdate'];
    @$todate = $_POST['todate'];
    @$day = round((strtotime($_POST['todate']) - strtotime($_POST['fromdate'])) / (60 * 60 * 24) + 1);
    @$typeofleave = $_POST['typeofleave'];
    @$creason = $_POST['creason'];
    @$comment = $_POST['comment'];
    @$appliedby = $_POST['appliedby'];

    if ($leaveid != "") {
        $leave = "INSERT INTO leavedb_leavedb (timestamp,leaveid,applicantid,fromdate,todate,typeofleave,creason,comment,appliedby,lyear,days) VALUES ('$now','$leaveid','$applicantid','$fromdate','$todate','$typeofleave','$creason','$comment','$appliedby','$year','$day')";
        $result = pg_query($con, $leave);
        $cmdtuples = pg_affected_rows($result);


        $resultt = pg_query($con, "Select fullname,email from rssimyaccount_members where associatenumber='$applicantid'");
        @$nameassociate = pg_fetch_result($resultt, 0, 0);
        @$emailassociate = pg_fetch_result($resultt, 0, 1);

        $resulttt = pg_query($con, "Select studentname,emailaddress from rssimyprofile_student where student_id='$applicantid'");
        @$namestudent = pg_fetch_result($resulttt, 0, 0);
        @$emailstudent = pg_fetch_result($resulttt, 0, 1);

        $fullname = $nameassociate . $namestudent;
        $email = $emailassociate . $emailstudent;

        sendEmail("leaveapply_admin", array(
            "leaveid" => $leaveid,
            "applicantid" => $applicantid,
            "applicantname" => @$fullname . @$studentname,
            "fromdate" => @date("d/m/Y", strtotime($fromdate)),
            "todate" => @date("d/m/Y", strtotime($todate)),
            "typeofleave" => $typeofleave,
            "category" => $creason,
            "day" => round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1),
            "now" => $now,
            "fullname" => $fullname,
        ), $email);
    }
}

@$id = $_POST['get_id'];
@$appid = strtoupper($_POST['get_appid']);
@$is_user = $_POST['is_user'];

date_default_timezone_set('Asia/Kolkata');
// $date = date('Y-d-m h:i:s');

if ($id == null && $appid == null) {
    $result = pg_query($con, "select * from leavedb_leavedb left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leavedb_leavedb.applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leavedb_leavedb.applicantid=student.student_id order by timestamp desc");
    $totalsl = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$appid' AND typeofleave='Sick Leave' AND lyear='$year' AND (status='Approved')");
    $totalcl = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$appid' AND typeofleave='Casual Leave' AND lyear='$year' AND (status='Approved')");
    $allocl = pg_query($con, "SELECT cl FROM rssimyaccount_members WHERE associatenumber='$appid'");
    $allosl = pg_query($con, "SELECT sl FROM rssimyaccount_members WHERE associatenumber='$appid'");
} else if ($id != null) {
    $result = pg_query($con, "select * from leavedb_leavedb left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leavedb_leavedb.applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leavedb_leavedb.applicantid=student.student_id WHERE leaveid='$id' order by timestamp desc");
    $totalsl = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$appid' AND typeofleave='Sick Leave' AND lyear='$year' AND (status='Approved')");
    $totalcl = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$appid' AND typeofleave='Casual Leave' AND lyear='$year' AND (status='Approved')");
    $allocl = pg_query($con, "SELECT cl FROM rssimyaccount_members WHERE associatenumber='$appid'");
    $allosl = pg_query($con, "SELECT sl FROM rssimyaccount_members WHERE associatenumber='$appid'");
} else if ($appid != null) {
    $result = pg_query($con, "select * from leavedb_leavedb left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leavedb_leavedb.applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leavedb_leavedb.applicantid=student.student_id WHERE applicantid='$appid' order by timestamp desc");
    $totalsl = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$appid' AND typeofleave='Sick Leave' AND lyear='$year' AND (status='Approved')");
    $totalcl = pg_query($con, "SELECT COALESCE(SUM(days),0) FROM leavedb_leavedb WHERE applicantid='$appid' AND typeofleave='Casual Leave' AND lyear='$year' AND (status='Approved')");
    $allocl = pg_query($con, "SELECT cl FROM rssimyaccount_members WHERE associatenumber='$appid'");
    $allosl = pg_query($con, "SELECT sl FROM rssimyaccount_members WHERE associatenumber='$appid'");
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
$resultArrsl = pg_fetch_result($totalsl, 0, 0);
$resultArrcl = pg_fetch_result($totalcl, 0, 0);
@$resultArrrcl = pg_fetch_result($allocl, 0, 0);
@$resultArrrsl = pg_fetch_result($allosl, 0, 0);
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>RSSI-LMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>.checkbox {
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
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
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
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <?php if (@$leaveid != null && @$cmdtuples == 0) { ?>

                        <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                        </div>
                    <?php
                    } else if (@$cmdtuples == 1) { ?>

                        <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Your request has been submitted. Leave id <?php echo $leaveid ?>.</span>
                        </div>
                        <script>
                            if (window.history.replaceState) {
                                window.history.replaceState(null, null, window.location.href);
                            }
                        </script>
                    <?php } ?>
                    <div class="col" style="display: inline-block; width:100%; text-align:right">
                        Home / Leave Management System (LMS)
                    </div>
                    <section class="box" style="padding: 2%;">

                        <form autocomplete="off" name="leaveapply" id="leaveapply" action="leave_admin.php" method="POST">
                            <div class="form-group" style="display: inline-block;">

                                <input type="hidden" name="form-type" type="text" value="leaveapply">

                                <span class="input-help">
                                    <input type="text" name="applicantid" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo @$_GET['applicantid']; ?>" required>
                                    <small id="passwordHelpBlock" class="form-text text-muted">Applicant ID*</small>
                                </span>
                                <span class="input-help">
                                    <input type="date" class="form-control" name="fromdate" id="fromdate" type="text" value="">
                                    <small id="passwordHelpBlock" class="form-text text-muted">From</small>
                                </span>
                                <span class="input-help">
                                    <input type="date" class="form-control" name="todate" id="todate" type="text" value="">
                                    <small id="passwordHelpBlock" class="form-text text-muted">To</small>
                                </span>
                                <span class="input-help">
                                    <select name="typeofleave" id="typeofleave" class="form-control" style="display: -webkit-inline-box; width:20vh; font-size: small;" required>
                                        <option value="" disabled selected hidden>Types of Leave</option>
                                        <option value="Sick Leave">Sick Leave</option>
                                        <option value="Casual Leave">Casual Leave</option>
                                    </select>
                                    <small id="passwordHelpBlock" class="form-text text-muted">Types of Leave</small>
                                </span>
                                <span class="input-help">
                                    <select name="creason" id='creason' class="form-control">
                                        <option>--Select--</option>
                                    </select>
                                    <small id="passwordHelpBlock" class="form-text text-muted">Leave Category*</small>
                                </span>

                                <span class="input-help">
                                    <textarea type="text" name="comment" class="form-control" placeholder="Remarks" value=""></textarea>
                                    <small id="passwordHelpBlock" class="form-text text-muted">Remarks</small>
                                </span>

                                <input type="hidden" name="appliedby" class="form-control" placeholder="Applied by" value="<?php echo $associatenumber ?>" required readonly>

                                <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">Apply</button>

                            </div>

                        </form>

                        <script>
                            function getType() {
                                var x = document.getElementById("typeofleave").value;
                                var items;
                                if (x === "Sick Leave") {
                                    items = ["Abdominal/Pelvic pain",
                                        "Anemia",
                                        "Appendicitis / Pancreatitis",
                                        "Asthma / bronchitis / pneumonia",
                                        "Burns",
                                        "Cancer -Carcinoma/ Malignant neoplasm",
                                        "Cardiac related ailments or Heart Disease",
                                        "Chest Pain",
                                        "Convulsions/ Epilepsy",
                                        "Dental Related Ailments - Tooth Ache / Impacted Tooth",
                                        "Emotional Well Being",
                                        "Digestive System Disorders/Indigestion/Food Poisoning/Diarrhea/Dysentry/Gastritis & Enteritis",
                                        "Excessive vomiting in pregnancy/Pregnancy induced hypertension",
                                        "Eye Related Ailments -Low Vision/Blindness/Eye Infections",
                                        "Fever/Cough/Cold",
                                        "Fracture/Injury/Dislocation/Sprain/Strain of joints/Ligaments of knee/Internal derangement/Other Orthopedic related ailments",
                                        "Gynecological Ailments/Disorders -Endometriosis/Fibroids",
                                        "Haemorrhoids (Piles)/Fissure/Fistula",
                                        "Headache/Nausea/Vomiting",
                                        "Hernia - Inguinal / Umbilical / Ventral",
                                        "Hepatitis",
                                        "Liver Related Ailments",
                                        "Maternity-Normal Delivery/Caesarean Section/Abortion",
                                        "Nervous Disorders",
                                        "Quarantine Leave",
                                        "Respiratory Related Ailments-Sinusitis/Tonsillitis,/Chronic rhinitis/Nasopharyngitis and pharyngitis/Congenital malformations of nose bronchitis",
                                        "Skin Related Ailments-Abscess/Swelling",
                                        "Spondilitis/ Intervertebral Disc Disorders / Spondylosis",
                                        "Urinary Tract Infections/Disorders",
                                        "Varicose veins of other sites",
                                    ];
                                } else if (x === "Casual Leave") {
                                    items = ["Other", "Timesheet leave"]
                                } else {
                                    items = ["--Select--"]
                                }
                                var str = ""
                                for (var item of items) {
                                    str += "<option>" + item + "</option>"
                                }
                                document.getElementById("creason").innerHTML = str;
                            }
                            document.getElementById("typeofleave").addEventListener("click", getType)
                        </script>




                        <table class="table">
                            <thead style="font-size: 12px;">
                                <tr>
                                    <th scope="col" colspan="2">Leave Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <form action="" method="POST">
                                            <div class="form-group" style="display: inline-block;">
                                                <div class="col2" style="display: inline-block;">
                                                    <input name="get_id" id="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Leave ID" value="<?php echo $id ?>">

                                                    <input name="get_appid" id="get_appid" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo $appid ?>">
                                                </div>
                                            </div>
                                            <div class="col2 left" style="display: inline-block;">
                                                <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                                    <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                                            </div>
                                            <div id="filter-checks">
                                                <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_POST['is_user'])) echo "checked='checked'"; ?> />
                                                <label for="is_user" style="font-weight: 400;">Search by Applicant ID</label>
                                            </div>
                                        </form>
                                        <script>
                                            if ($('#is_user').not(':checked').length > 0) {

                                                document.getElementById("get_id").disabled = true;
                                                document.getElementById("get_appid").disabled = false;

                                            } else {

                                                document.getElementById("get_id").disabled = false;
                                                document.getElementById("get_appid").disabled = true;

                                            }

                                            const checkbox = document.getElementById('is_user');

                                            checkbox.addEventListener('change', (event) => {
                                                if (event.target.checked) {
                                                    document.getElementById("get_id").disabled = false;
                                                    document.getElementById("get_appid").disabled = true;
                                                } else {
                                                    document.getElementById("get_id").disabled = true;
                                                    document.getElementById("get_appid").disabled = false;
                                                }
                                            })
                                        </script>
                                    </td>
                                    <?php if ($appid != null) { ?>
                                        <td>Sick Leave - <?php echo $resultArrrsl - $resultArrsl ?><br>Casual Leave - <?php echo $resultArrrcl - $resultArrcl ?>
                                        </td>
                                    <?php } else { ?><td></td><?php } ?>
                                </tr>
                            </tbody>
                        </table>

                        <div class="col" style="display: inline-block; width:100%; text-align:right;">
                            Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                        </div>

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
                    <table class="table" id="table-id" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th scope="col">Leave ID</th>
                                <th scope="col">Applicant ID</th>
                                <th scope="col">Applied on</th>
                                <th scope="col">From-To</th>
                                <th scope="col">Day(s) count</th>
                                <th scope="col">Leave Details</th>
                                <th scope="col">Status</th>
                                <th scope="col">Reviewer</th>
                                <th scope="col" width="15%">Remarks</th>
                                <th scope="col"></th>
                            </tr>
                        </thead>' ?>
                        <?php if (sizeof($resultArr) > 0) { ?>
                            <?php
                            echo '<tbody>';
                            foreach ($resultArr as $array) {
                                echo '<tr>'
                            ?>

                                <?php if ($array['doc'] != null) { ?>
                                    <?php
                                    echo '<td><span class="noticea"><a href="' . $array['doc'] . '" target="_blank">' . $array['leaveid'] . '</a></span></td>'
                                    ?>
                                    <?php    } else { ?><?php
                                                        echo '<td>' . $array['leaveid'] . '</td>' ?>
                                <?php } ?>
                                <?php
                                echo '  <td>' . $array['applicantid'] . '<br>' . $array['fullname'] . $array['studentname'] . '</td>
                                <td>' . @date("d/m/Y g:i a", strtotime($array['timestamp'])) . '</td>
                                <td>' .  @date("d/m/Y", strtotime($array['fromdate'])) . '—' .  @date("d/m/Y", strtotime($array['todate'])) . '</td>
                                <td>' . round((strtotime($array['todate']) - strtotime($array['fromdate'])) / (60 * 60 * 24) + 1) . '</td>
                                <td>' . $array['typeofleave'] . '<br>
                                ' . $array['creason'] . '<br>
                                ' . $array['applicantcomment'] . '</td>
                                <td>' . $array['status'] . '</td>
                                <td>' . $array['reviewer_id'] . '<br>' . $array['reviewer_name'] . '</td>
                                <td>' . $array['comment'] . '</td>
                                <td>
                                <button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['leaveid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="fa-regular fa-pen-to-square" style="font-size: 14px ;color:#777777" title="Show Details" display:inline;></i></button>&nbsp;' ?>
                                <?php if (($array['phone'] != null || $array['contact'] != null)) { ?>
                                    <?php echo '<a href="https://api.whatsapp.com/send?phone=91' . $array['phone'] . $array['contact'] . '&text=Dear ' . $array['fullname'] . $array['studentname'] . ' (' . $array['applicantid'] . '),%0A%0ARedeem id ' . $array['leaveid'] . ' against the policy issued by the organization has been settled at Rs.' . $array['leaveid'] . ' on ' . @date("d/m/Y g:i a", strtotime($array['reviewer_status_updated_on'])) . '.%0A%0AThe amount has been credited to your account. It may take standard time for it to reflect in your account.%0A%0AYou can track the status of your request in real-time from https://login.rssi.in/rssi-member/redeem_gems.php. For more information, please contact your HR or immediate supervisor.%0A%0A--RSSI%0A%0A**This is an automatically generated SMS
                                " target="_blank"><i class="fa-brands fa-whatsapp" style="color:#444444;" title="Send SMS ' . $array['phone'] . $array['contact'] . '"></i></a>' ?>
                                <?php } else { ?>
                                    <?php echo '<i class="fa-brands fa-whatsapp" style="color:#A2A2A2;" title="Send SMS"></i>' ?>
                                    <?php } ?>&nbsp;




                                    <?php if ((@$array['email'] != null || @$array['emailaddress'] != null)) { ?>
                                        <?php echo '<form  action="#" name="email-form-' . $array['leaveid'] . '" method="POST" style="display: -webkit-inline-box;" >
                                <input type="hidden" name="template" type="text" value="leaveconf">
                                <input type="hidden" name="data[leaveid]" type="text" value="' . $array['leaveid'] . '">
                                <input type="hidden" name="data[applicantid]" type="text" value="' . $array['applicantid'] . '">
                                <input type="hidden" name="data[typeofleave]" type="text" value="' . $array['typeofleave'] . '">
                                <input type="hidden" name="data[applicantname]" type="text" value="' . $array['fullname'] . $array['studentname'] . '">
                                <input type="hidden" name="data[category]" type="text" value="' . $array['creason'] . '">
                                <input type="hidden" name="data[comment]" type="text" value="' . $array['comment'] . '">
                                <input type="hidden" name="data[day]" type="text" value="' . round((strtotime($array['todate']) - strtotime($array['fromdate'])) / (60 * 60 * 24) + 1) . '">
                                <input type="hidden" name="data[fromdate]" type="text" value="' . @date("d/m/Y", strtotime($array['fromdate'])) . '">
                                <input type="hidden" name="data[todate]" type="text" value="' . @date("d/m/Y", strtotime($array['todate'])) . '">
                                <input type="hidden" name="data[status]" type="text" value="' . @strtoupper($array['status']) . '">
                                <input type="hidden" name="email" type="text" value="' . @$array['email'] . @$array['emailaddress'] . '">
                                
                                <button  style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;"
                                 type="submit"><i class="fa-regular fa-envelope" style="color:#444444;" title="Send Email ' . @$array['email'] . @$array['emailaddress'] . '"></i></button>
                            </form>' ?>
                                    <?php } else { ?>
                                        <?php echo '<i class="fa-regular fa-envelope" style="color:#A2A2A2;" title="Send Email"></i>' ?>
                                    <?php } ?>

                                    <?php echo '&nbsp;&nbsp;<form name="leavedelete_' . $array['leaveid'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="leavedelete">
                                <input type="hidden" name="leavedeleteid" id="leavedeleteid" type="text" value="' . $array['leaveid'] . '">
                                
                                <button type="submit" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['leaveid'] . '"><i class="fa-solid fa-xmark"></i></button> </form>
                                </td>' ?>
                                <?php } ?>
                            <?php
                        } else if ($id == null) {
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
                                    </table>';
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
        </section>

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
                    <p id="status" class="label " style="display: inline !important;"><span class="leaveid"></span></p>
                </div>

                <form id="leavereviewform" action="#" method="POST">
                    <input type="hidden" class="form-control" name="form-type" type="text" value="leavereviewform" readonly>
                    <input type="hidden" class="form-control" name="reviewer_id" id="reviewer_id" type="text" value="<?php echo $associatenumber ?>" readonly>
                    <input type="hidden" class="form-control" name="reviewer_name" id="reviewer_name" type="text" value="<?php echo $fullname ?>" readonly>
                    <input type="hidden" class="form-control" name="leaveidd" id="leaveidd" type="text" value="" readonly>
                    <span class="input-help">
                        <input type="date" class="form-control" name="fromdate" id="fromdated" type="text" value="">
                        <small id="passwordHelpBlock" class="form-text text-muted">From</small>
                    </span>
                    <span class="input-help">
                        <input type="date" class="form-control" name="todate" id="todated" type="text" value="">
                        <small id="passwordHelpBlock" class="form-text text-muted">To</small>
                    </span>

                    <select name="leave_status" id="leave_status" class="form-control" style="display: -webkit-inline-box; width:20vh; font-size: small;" required>
                        <option value="" disabled selected hidden>Status</option>
                        <option value="Approved">Approved</option>
                        <option value="Under review">Under review</option>
                        <option value="Rejected">Rejected</option>
                    </select>

                    <span class="input-help">
                        <textarea type="text" name="reviewer_remarks" id="reviewer_remarks" class="form-control" placeholder="HR remarks" value=""></textarea>
                        <small id="passwordHelpBlock" class="form-text text-muted">HR remarks</small>
                    </span>
                    <br><br>
                    <button type="submit" id="leaveupdate" class="btn btn-danger btn-sm " style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none"><i class="fa-solid fa-arrows-rotate"></i>&nbsp;&nbsp;Update</button>
                </form>
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
                    if (item["leaveid"] == id) {
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
                if (mydata["status"] === "Approved") {
                    status.classList.add("label-success")
                    status.classList.remove("label-danger")
                } else {
                    status.classList.remove("label-success")
                    status.classList.add("label-danger")
                }
                //class add end

                var profile = document.getElementById("leaveidd")
                profile.value = mydata["leaveid"]
                if (mydata["status"] !== null) {
                    profile = document.getElementById("leave_status")
                    profile.value = mydata["status"]
                }
                if (mydata["comment"] !== null) {
                    profile = document.getElementById("reviewer_remarks")
                    profile.value = mydata["comment"]
                }

                if (mydata["fromdate"] !== null) {
                    profile = document.getElementById("fromdated")
                    profile.value = mydata["fromdate"]
                }
                if (mydata["todate"] !== null) {
                    profile = document.getElementById("todated")
                    profile.value = mydata["todate"]
                }

                if (mydata["status"] == 'Approved' || mydata["status"] == 'Rejected') {
                    document.getElementById("leaveupdate").disabled = true;
                } else {
                    document.getElementById("leaveupdate").disabled = false;
                }
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

        <script>
            var data = <?php echo json_encode($resultArr) ?>;
            const scriptURL = 'payment-api.php'

            function validateForm() {
                if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {

                    data.forEach(item => {
                        const form = document.forms['leavedelete_' + item.leaveid]
                        form.addEventListener('submit', e => {
                            e.preventDefault()
                            fetch(scriptURL, {
                                    method: 'POST',
                                    body: new FormData(document.forms['leavedelete_' + item.leaveid])
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

            const form = document.getElementById('leavereviewform')
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.getElementById('leavereviewform'))
                    })
                    .then(response =>
                        alert("Record has been updated.") +
                        location.reload()
                    )
                    .catch(error => console.error('Error!', error.message))
            })

            data.forEach(item => {
                const formId = 'email-form-' + item.leaveid
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
    </section>
    </div>
    </section>
    </section>
</body>

</html>
