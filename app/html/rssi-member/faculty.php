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
?>
<?php
$date = date('Y-m-d H:i:s');

@$id = $_POST['get_id'];
@$aaid = strtoupper($_POST['get_aaid']);
@$lyear = $_POST['adj_academicyear'];
@$is_user = $_POST['is_user'];

if ($id != null) {
    $result = pg_query($con, "SELECT distinct * FROM rssimyaccount_members 
    left join (SELECT status,userid FROM asset) asset ON asset.userid=rssimyaccount_members.associatenumber 
    left join (SELECT distinct username, max(logintime) as logintime FROM userlog_member GROUP BY username) userlog_member ON rssimyaccount_members.associatenumber=userlog_member.username
    left join (SELECT taggedto FROM gps) gps ON rssimyaccount_members.associatenumber=gps.taggedto

    left join (SELECT applicantid, COALESCE(SUM(days),0) as sltd  FROM leavedb_leavedb WHERE typeofleave='Sick Leave' AND lyear='$lyear' AND (status='Approved') GROUP BY applicantid) sltaken ON rssimyaccount_members.associatenumber=sltaken.applicantid

    left join (SELECT applicantid, COALESCE(SUM(days),0) as cltd FROM leavedb_leavedb WHERE typeofleave='Casual Leave' AND lyear='$lyear' AND (status='Approved') GROUP BY applicantid) cltaken ON rssimyaccount_members.associatenumber=cltaken.applicantid

    left join (SELECT applicantid, COALESCE(SUM(days),0) as lwptd FROM leavedb_leavedb WHERE typeofleave='Leave Without Pay' AND lyear='$lyear' AND (status='Approved') GROUP BY applicantid) lwptaken ON rssimyaccount_members.associatenumber=lwptaken.applicantid

    left join (SELECT applicantid, 1 as onleave FROM leavedb_leavedb WHERE CURRENT_DATE BETWEEN fromdate AND todate AND lyear='$lyear' AND status='Approved') onleave ON rssimyaccount_members.associatenumber=onleave.applicantid

    left join (SELECT allo_applicantid, COALESCE(SUM(allo_daycount),0) as slad FROM leaveallocation WHERE allo_leavetype='Sick Leave' AND allo_academicyear='$lyear' GROUP BY allo_applicantid) slallo ON rssimyaccount_members.associatenumber=slallo.allo_applicantid

    left join (SELECT allo_applicantid, COALESCE(SUM(allo_daycount),0) as clad FROM leaveallocation WHERE allo_leavetype='Casual Leave' AND allo_academicyear='$lyear' GROUP BY allo_applicantid) clallo ON rssimyaccount_members.associatenumber=clallo.allo_applicantid

    left join (SELECT adj_applicantid, COALESCE(SUM(adj_day),0) as sladd FROM leaveadjustment WHERE adj_leavetype='Sick Leave' AND adj_academicyear='$lyear' GROUP BY adj_applicantid) sladj ON rssimyaccount_members.associatenumber=sladj.adj_applicantid

    left join (SELECT adj_applicantid, COALESCE(SUM(adj_day),0) as cladd FROM leaveadjustment WHERE adj_leavetype='Casual Leave' AND adj_academicyear='$lyear' GROUP BY adj_applicantid) cladj ON rssimyaccount_members.associatenumber=cladj.adj_applicantid

    left join (SELECT adj_applicantid, COALESCE(SUM(adj_day),0) as lwpadd FROM leaveadjustment WHERE adj_leavetype='Leave Without Pay' AND adj_academicyear='$lyear' GROUP BY adj_applicantid) lwpadj ON rssimyaccount_members.associatenumber=lwpadj.adj_applicantid

    left join (SELECT onboarding_associate_id,onboard_initiated_by,onboard_initiated_on FROM onboarding) onboarding ON rssimyaccount_members.associatenumber=onboarding.onboarding_associate_id

    left join (SELECT exit_associate_id,exit_initiated_by,exit_initiated_on FROM associate_exit) associate_exit ON rssimyaccount_members.associatenumber=associate_exit.exit_associate_id

    WHERE filterstatus='$id' order by filterstatus asc,today desc");
} else if ($aaid != null) {
    $result = pg_query($con, "SELECT distinct * FROM rssimyaccount_members 
    left join (SELECT status,userid FROM asset) asset ON asset.userid=rssimyaccount_members.associatenumber 
    left join (SELECT distinct username, max(logintime) as logintime FROM userlog_member GROUP BY username) userlog_member ON rssimyaccount_members.associatenumber=userlog_member.username
    left join (SELECT taggedto FROM gps) gps ON rssimyaccount_members.associatenumber=gps.taggedto

    left join (SELECT applicantid, COALESCE(SUM(days),0) as sltd  FROM leavedb_leavedb WHERE typeofleave='Sick Leave' AND lyear='$lyear' AND (status='Approved') GROUP BY applicantid) sltaken ON rssimyaccount_members.associatenumber=sltaken.applicantid

    left join (SELECT applicantid, COALESCE(SUM(days),0) as cltd FROM leavedb_leavedb WHERE typeofleave='Casual Leave' AND lyear='$lyear' AND (status='Approved') GROUP BY applicantid) cltaken ON rssimyaccount_members.associatenumber=cltaken.applicantid

    left join (SELECT applicantid, COALESCE(SUM(days),0) as lwptd FROM leavedb_leavedb WHERE typeofleave='Leave Without Pay' AND lyear='$lyear' AND (status='Approved') GROUP BY applicantid) lwptaken ON rssimyaccount_members.associatenumber=lwptaken.applicantid

    left join (SELECT applicantid, 1 as onleave FROM leavedb_leavedb WHERE CURRENT_DATE BETWEEN fromdate AND todate AND lyear='$lyear' AND status='Approved') onleave ON rssimyaccount_members.associatenumber=onleave.applicantid

    left join (SELECT allo_applicantid, COALESCE(SUM(allo_daycount),0) as slad FROM leaveallocation WHERE allo_leavetype='Sick Leave' AND allo_academicyear='$lyear' GROUP BY allo_applicantid) slallo ON rssimyaccount_members.associatenumber=slallo.allo_applicantid

    left join (SELECT allo_applicantid, COALESCE(SUM(allo_daycount),0) as clad FROM leaveallocation WHERE allo_leavetype='Casual Leave' AND allo_academicyear='$lyear' GROUP BY allo_applicantid) clallo ON rssimyaccount_members.associatenumber=clallo.allo_applicantid

    left join (SELECT adj_applicantid, COALESCE(SUM(adj_day),0) as sladd FROM leaveadjustment WHERE adj_leavetype='Sick Leave' AND adj_academicyear='$lyear' GROUP BY adj_applicantid) sladj ON rssimyaccount_members.associatenumber=sladj.adj_applicantid

    left join (SELECT adj_applicantid, COALESCE(SUM(adj_day),0) as cladd FROM leaveadjustment WHERE adj_leavetype='Casual Leave' AND adj_academicyear='$lyear' GROUP BY adj_applicantid) cladj ON rssimyaccount_members.associatenumber=cladj.adj_applicantid

    left join (SELECT adj_applicantid, COALESCE(SUM(adj_day),0) as lwpadd FROM leaveadjustment WHERE adj_leavetype='Leave Without Pay' AND adj_academicyear='$lyear' GROUP BY adj_applicantid) lwpadj ON rssimyaccount_members.associatenumber=lwpadj.adj_applicantid

    left join (SELECT onboarding_associate_id,onboard_initiated_by,onboard_initiated_on FROM onboarding) onboarding ON rssimyaccount_members.associatenumber=onboarding.onboarding_associate_id

    left join (SELECT exit_associate_id,exit_initiated_by,exit_initiated_on FROM associate_exit) associate_exit ON rssimyaccount_members.associatenumber=associate_exit.exit_associate_id
    
    WHERE associatenumber='$aaid' order by filterstatus asc,today desc");
} else {
    $result = pg_query($con, "SELECT * from rssimyaccount_members where associatenumber is null");
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

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-11316670180');
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>RSSI Faculty</title>

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
    <style>
        @media (max-width:767px) {
            td {
                width: 100%
            }
        }

        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }

        @media (max-width:767px) {

            #cw,
            #cw1,
            #cw2,
            #cw3 {
                width: 100% !important;
            }

        }

        #cw {
            width: 7%;
        }

        #cw1 {
            width: 17%;
        }

        #cw2 {
            width: 15%;
        }

        #cw3 {
            width: 25%;
        }

        .modal {
            background-color: rgba(0, 0, 0, 0.4);
            /* Black w/ opacity */
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>RSSI Faculty</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">RSSI Faculty</li>
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
                                <div class="col" style="display: inline-block;">
                                    Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                                </div>
                                <div class="col" style="display: inline-block; width:47%; text-align:right">
                                    <a href="facultyexp.php" target="_self" class="btn btn-danger btn-sm" role="button">Faculty Details</a>
                                </div>
                            </div>

                            <form action="" method="POST">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">
                                        <select name="get_id" id="get_id" class="form-select" style="width:max-content; display:inline-block" placeholder="Appraisal type" disabled>
                                            <?php if ($id == null) { ?>
                                                <option value="" disabled selected hidden>Select Status</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $id ?></option>
                                            <?php }
                                            ?>
                                            <option>Active</option>
                                            <option>Inactive</option>
                                            <option>In Progress</option>
                                        </select>
                                        <input name="get_aaid" id="get_aaid" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate number" value="<?php echo $aaid ?>">
                                    </div>
                                </div>
                                <select name="adj_academicyear" id="adj_academicyear" class="form-select" style="display: -webkit-inline-box; width:20vh;" required>
                                    <?php if ($lyear != null) { ?>
                                        <option hidden selected><?php echo $lyear ?></option>
                                    <?php }
                                    ?>
                                </select>

                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                        <i class="bi bi-search"></i>&nbsp;Search</button>
                                </div>

                                <div id="filter-checks">
                                    <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_POST['is_user'])) echo "checked='checked'"; ?> />
                                    <label for="is_user" style="font-weight: 400;">Search by Associate ID</label>
                                </div>
                            </form>
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
                            <script>
                                if ($('#is_user').not(':checked').length > 0) {

                                    document.getElementById("get_id").disabled = false;
                                    document.getElementById("get_aaid").disabled = true;

                                } else {

                                    document.getElementById("get_id").disabled = true;
                                    document.getElementById("get_aaid").disabled = false;

                                }

                                const checkbox = document.getElementById('is_user');

                                checkbox.addEventListener('change', (event) => {
                                    if (event.target.checked) {
                                        document.getElementById("get_id").disabled = true;
                                        document.getElementById("get_aaid").disabled = false;
                                    } else {
                                        document.getElementById("get_id").disabled = false;
                                        document.getElementById("get_aaid").disabled = true;
                                    }
                                })
                            </script>

                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th id="cw">Photo</th>
                                            <th id="cw1">Volunteer Details</th>
                                            <th>Contact</th>
                                            <th>Designation</th>
                                            <!--<th>Class URL</th>-->
                                            <th id="cw2">Association Status</th>
                                            <th>Productivity</th>
                                            <th>Worklist</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php if (sizeof($resultArr) > 0) { ?>
                                            <?php foreach ($resultArr as $array) { ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($array['photo'] != null) { ?>
                                                            <div class="icon-container">
                                                                <img src="<?php echo $array['photo']; ?>" class="rounded-circle me-2" alt="User Photo" width="50" height="50" />
                                                            </div>
                                                        <?php } else { ?>
                                                            <div class="icon-container">
                                                                <img src="https://res.cloudinary.com/hs4stt5kg/image/upload/v1609410219/faculties/blank.jpg" class="rounded-circle me-2" alt="Blank User Photo" width="50" height="50" />
                                                            </div>
                                                        <?php } ?>

                                                        <div class="status-container">
                                                            <?php if ($array['logintime'] != null) {
                                                                if (date('Y-m-d H:i:s', strtotime($array['logintime'] . ' + 24 minute')) > $date) { ?>
                                                                    <div class="status-circle" title="Online"></div>
                                                                <?php } else { ?>
                                                                    <div class="status-circle" style="background-color: #E5E5E5;" title="Offline"></div>
                                                                <?php }
                                                            } else { ?>
                                                                <div class="status-circle" style="background-color: #E5E5E5;" title="Offline"></div>
                                                            <?php } ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php echo 'Name - <b>' . $array['fullname'] . '</b><br>Associate ID - <b>' . $array['associatenumber'] . '</b>
                                                        <br><b>' . $array['gender'] . '&nbsp;(' . $array['age'] . ')</b><br><br>DOJ - ' . date('d/m/y', strtotime($array['doj'])) . '<br>' . $array['yos'] . '</td>
                                                        <td>' . $array['phone'] . '<br>' . $array['email'] . '</td>
                                                        <td>' . substr($array['position'], 0, strrpos($array['position'], "-")) ?>
                                                    </td>
                                                    <td style="white-space:unset">

                                                        <?php echo $array['filterstatus'] . '<br>';

                                                        if ($array['onleave'] != null) {
                                                            echo '<br><p class="badge bg-danger">on leave</p>';
                                                        }

                                                        if ($array['today'] != 0 && $array['today'] != null && $array['filterstatus'] != 'Inactive') {
                                                            echo '<br><p class="badge bg-warning">Attd. pending</p>';
                                                        }

                                                        if ($array['userid'] != null && $array['status'] != 'Closed') {
                                                            echo '<br><a href="asset-management.php?get_statuse=Associate&get_appid=' . $array['associatenumber'] . '" target="_blank" style="text-decoration:none" title="click here"><p class="badge bg-warning">agreement</p></a>';
                                                        }

                                                        if ($array['taggedto'] != null) {
                                                            echo '<br><a href="gps.php?taggedto=' . $array['associatenumber'] . '" target="_blank" style="text-decoration:none" title="click here"><p class="badge bg-danger">asset</p></a>';
                                                        }

                                                        echo '<br><br>' . (($array['effectivedate'] !== '') ? $array['effectivedate'] . '&nbsp;' : '') . $array['remarks'] ?>
                                                    </td>

                                                    <td>
                                                        <?php echo $array['classtaken'] . '/' . $array['maxclass'] . '&nbsp' . $array['ctp'] . '<br><br>LWP&nbsp;(' . ($array['lwptd'] - $array['lwpadd']) . ')&nbsp;s&nbsp;(' . ($array['slad'] + $array['sladd']) - $array['sltd'] . '),&nbsp;c&nbsp;(' . ($array['clad'] + $array['cladd']) - $array['cltd'] . ')' ?>
                                                    </td>
                                                    <td style="white-space: unset;">


                                                        <?php echo '<button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['associatenumber'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                                            <i class="bi bi-box-arrow-up-right"></i></button>
                                                        &nbsp;&nbsp;
                                                        <form name="initiatingonboarding' . $array['associatenumber'] . '" action="#" method="POST" style="display:inline;">
                                                            <input type="hidden" name="form-type" type="text" value="initiatingonboarding">
                                                            <input type="hidden" name="initiatedfor" type="text" value="' . $array['associatenumber'] . '" readonly>
                                                            <input type="hidden" name="initiatedby" type="text" value="' . $associatenumber . '" readonly>';
                                                        ?>
                                                        <!-- Initiate onboarding system -->
                                                        <?php if ($role == 'Admin' && $array['onboard_initiated_by'] == null) { ?>
                                                            <?php echo '<button type="submit" id="yes" onclick="validateForm()" style=" outline: none;background: none; padding: 0px; border: none;" title="Initiating Onboarding"><i class="bi bi-person-plus"></i></button>'; ?>
                                                        <?php } else {
                                                            echo date('d/m/y h:i:s a', strtotime($array['onboard_initiated_on'])) . ' by ' . $array['onboard_initiated_by'];
                                                        }
                                                        echo '</form>&nbsp;&nbsp;

                                                            <form name="initiatingexit' . $array['associatenumber'] . '" action="#" method="POST" style="display:inline;">
                                                            <input type="hidden" name="form-type" type="text" value="initiatingexit">
                                                            <input type="hidden" name="initiatedfor" type="text" value="' . $array['associatenumber'] . '" readonly>
                                                            <input type="hidden" name="initiatedby" type="text" value="' . $associatenumber . '" readonly>';
                                                        ?>
                                                        <!-- Initiate Exit system -->
                                                        <?php if ($role == 'Admin' && $array['exit_initiated_by'] == null) { ?>
                                                            <?php echo '<button type="submit" id="yes" onclick="exit_validateForm()" style=" outline: none;background: none; padding: 0px; border: none;" title="Initiating Exit"><i class="bi bi-box-arrow-in-right"></i></button>'; ?>
                                                        <?php } else {
                                                            echo date('d/m/y h:i:s a', strtotime($array['exit_initiated_on'])) . ' by ' . $array['exit_initiated_by'];
                                                        }
                                                        echo '</form>' ?>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } else { ?>
                                            <tr>
                                                <td colspan="9">No Data Found</td>
                                            </tr>

                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="modal" id="myModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Faculty Details</h1>
                                            <button type="button" id="closedetails-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="d-flex justify-content-end">
                                                <span id="status" class="fullname badge"></span>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p>WBT Completed: <span class="attd"></span></p>
                                                </div>
                                                <div class="col-md-6 text-end">
                                                    <a id="wbt_details" href="#" target="_blank">
                                                        <i class="bi bi-eye" style="font-size: 20px; color:#777777" title="WBT Details"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <a id="offer_letter" href="" target="_blank">Offer Letter</a><br>
                                                    <a id="joining_letter" href="" target="_blank">Joining Letter</a><br>
                                                </div>
                                                <div class="col-md-6">
                                                    <a id="certificate_issue" href="" target="_blank">Issue Document</a><br>
                                                    <a id="certificate_view" href="" target="_blank">View Document</a><br>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <a id="experience_letter" href="" target="_blank">Generate Experience Letter</a><br>
                                                </div>
                                                <div class="col-md-6">
                                                    <a id="profile" href="" target="_blank">Profile</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" id="closedetails-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                var data = <?php echo json_encode($resultArr); ?>;

                                // Get the modal
                                var modal = document.getElementById("myModal");
                                // Get the <span> element that closes the modal
                                var closedetails = [
                                    document.getElementById("closedetails-header"),
                                    document.getElementById("closedetails-footer")
                                ];

                                function showDetails(id) {
                                    var mydata = data.find(item => item.associatenumber === id);

                                    if (mydata) {
                                        console.log(mydata); // Log the mydata object to the console for debugging
                                        var keys = Object.keys(mydata);
                                        keys.forEach(key => {
                                            var span = modal.querySelector("." + key);
                                            if (span)
                                                span.textContent = mydata[key];
                                        });

                                        var fullnameBadge = modal.querySelector(".fullname");
                                        if (fullnameBadge)
                                            fullnameBadge.textContent = mydata.fullname;

                                        modal.style.display = "block";

                                        var status = document.getElementById("status");
                                        if (mydata.filterstatus === "Active") {
                                            status.classList.add("bg-success");
                                            status.classList.remove("bg-danger");
                                        } else {
                                            status.classList.remove("bg-success");
                                            status.classList.add("bg-danger");
                                        }

                                        document.getElementById("wbt_details").href = "/rssi-member/my_learning.php?get_aid=" + mydata.associatenumber;
                                        document.getElementById("offer_letter").href = "/rssi-member/offerletter.php?get_id=" + mydata.associatenumber;
                                        document.getElementById("certificate_issue").href = "/rssi-member/my_certificate.php?awarded_to_id=" + mydata.associatenumber + "&awarded_to_name=" + mydata.fullname;
                                        document.getElementById("certificate_view").href = "/rssi-member/my_certificate.php?get_nomineeid=" + mydata.associatenumber;
                                        document.getElementById("experience_letter").href = "/rssi-member/expletter.php?get_id=" + mydata.associatenumber;
                                        document.getElementById("joining_letter").href = "/rssi-member/joiningletter.php?get_id=" + mydata.associatenumber;
                                        document.getElementById("profile").href = "/rssi-member/myprofile.php?get_id=" + mydata.associatenumber;
                                    }
                                }

                                // Close modal using either cross or close button
                                closedetails.forEach(function(element) {
                                    element.addEventListener("click", closeModal);
                                });

                                function closeModal() {
                                    modal.style.display = "none";
                                }

                                // When the user clicks anywhere outside of the modal, close it
                                window.onclick = function(event) {
                                    if (event.target === modal) {
                                        modal.style.display = "none";
                                    }
                                };
                            </script>


                            <script>
                                var data = <?php echo json_encode($resultArr) ?>;
                                var aid = <?php echo '"' . $_SESSION['aid'] . '"' ?>;

                                const scriptURL = 'payment-api.php'

                                function validateForm() {
                                    if (confirm('Are you sure you want to onboard this associate?')) {

                                        data.forEach(item => {
                                            const form = document.forms['initiatingonboarding' + item.associatenumber]
                                            form.addEventListener('submit', e => {
                                                e.preventDefault()
                                                fetch(scriptURL, {
                                                        method: 'POST',
                                                        body: new FormData(document.forms['initiatingonboarding' + item.associatenumber])
                                                    })
                                                    .then(response => response.text())
                                                    .then(result => {
                                                        if (result == 'success') {
                                                            alert("The associate's onboarding process has been initiated successfully.") + location.reload()
                                                        } else {
                                                            alert("An error occurred while processing your request. Please try again later.") + location.reload()
                                                        }
                                                    })
                                            })
                                        })
                                    } else {
                                        alert("The onboarding process has been cancelled.");
                                        return false;
                                    }
                                }

                                function exit_validateForm() {
                                    if (confirm('Are you sure you want to initiate the exit process for this associate?')) {

                                        data.forEach(item => {
                                            const form = document.forms['initiatingexit' + item.associatenumber]
                                            form.addEventListener('submit', e => {
                                                e.preventDefault()
                                                fetch(scriptURL, {
                                                        method: 'POST',
                                                        body: new FormData(document.forms['initiatingexit' + item.associatenumber])
                                                    })
                                                    .then(response => response.text())
                                                    .then(result => {
                                                        if (result == 'success') {
                                                            alert("The process has been successfully initiated.") + location.reload()
                                                        } else {
                                                            alert("An error occurred while processing your request. Please try again later.") + location.reload()
                                                        }
                                                    })
                                            })
                                        })
                                    } else {
                                        alert("The process has been canceled.");
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
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>