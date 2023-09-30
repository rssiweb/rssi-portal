<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

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
if ($role == 'Member') {

    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}

//For appraisee

@$type = $_GET['get_id'];
@$year = $_GET['get_year'];
if (@$_GET['form-type'] == "appraisee") {
    $result = pg_query($con, "select appraisee.fullname aname, appraisee.email aemail, manager.fullname mname, manager.email memail, reviewer.fullname rname, reviewer.email remail,*  from appraisee_response
    LEFT JOIN (SELECT associatenumber,fullname,email,filterstatus FROM rssimyaccount_members) appraisee ON appraisee.associatenumber = appraisee_response.appraisee_associatenumber
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) manager ON manager.associatenumber = appraisee_response.manager_associatenumber
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) reviewer ON reviewer.associatenumber = appraisee_response.reviewer_associatenumber
    WHERE appraisee_associatenumber='$associatenumber' AND filterstatus='Active' AND appraisaltype='$type' AND appraisalyear='$year' order by goalsheet_created_on desc");
} else {
    $result = pg_query($con, "select * from appraisee_response WHERE goalsheetid is null");
}
$resultArr = pg_fetch_all($result);
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

//For manager

@$yearm = $_GET['get_yearm'];
if (@$_GET['form-type'] == "manager") {
    $resultm = pg_query($con, "select appraisee.fullname aname, appraisee.email aemail, manager.fullname mname, manager.email memail, reviewer.fullname rname, reviewer.email remail,*  from appraisee_response
    LEFT JOIN (SELECT associatenumber,fullname,email,filterstatus FROM rssimyaccount_members) appraisee ON appraisee.associatenumber = appraisee_response.appraisee_associatenumber
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) manager ON manager.associatenumber = appraisee_response.manager_associatenumber
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) reviewer ON reviewer.associatenumber = appraisee_response.reviewer_associatenumber
    WHERE manager_associatenumber='$associatenumber' AND filterstatus='Active' AND appraisalyear='$yearm' AND appraisee_response_complete='yes' AND manager_evaluation_complete IS NULL order by goalsheet_evaluated_on desc");
} else {
    $resultm = pg_query($con, "select * from appraisee_response WHERE goalsheetid is null");
}
$resultArrm = pg_fetch_all($resultm);
if (!$resultm) {
    echo "An error occurred.\n";
    exit;
}

//For reviewer

@$yearr = $_GET['get_yearr'];
if (@$_GET['form-type'] == "reviewer") {
    $resultr = pg_query($con, "select appraisee.fullname aname, appraisee.email aemail, manager.fullname mname, manager.email memail, reviewer.fullname rname, reviewer.email remail,*  from appraisee_response
    LEFT JOIN (SELECT associatenumber,fullname,email,filterstatus FROM rssimyaccount_members) appraisee ON appraisee.associatenumber = appraisee_response.appraisee_associatenumber
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) manager ON manager.associatenumber = appraisee_response.manager_associatenumber
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) reviewer ON reviewer.associatenumber = appraisee_response.reviewer_associatenumber
    WHERE reviewer_associatenumber='$associatenumber' AND filterstatus='Active' AND appraisalyear='$yearr' AND appraisee_response_complete='yes' AND manager_evaluation_complete='yes' AND (reviewer_response_complete IS NULL OR ipf_response='rejected') order by goalsheet_evaluated_on desc");
} else {
    $resultr = pg_query($con, "select * from appraisee_response WHERE goalsheetid is null");
}
$resultArrr = pg_fetch_all($resultr);
if (!$resultr) {
    echo "An error occurred.\n";
    exit;
} ?>

<?php
function getAssessmentStatus($array)
{
    if ($array['appraisee_response_complete'] == "" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "") {
        return '<span class="badge bg-danger text-start">Self-assessment</span>';
    } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "") {
        return '<span class="badge bg-warning text-start">Manager assessment in progress</span>';
    } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "") {
        return '<span class="badge bg-primary text-start">Reviewer assessment in progress</span>';
    } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "yes" && $array['ipf_response'] == null) {
        return '<span class="badge bg-success text-start">IPF released</span>';
    } else if ($array['ipf_response'] == 'accepted') {
        return '<span class="badge bg-success text-start">IPF Accepted</span><br><br>' . date('d/m/y h:i:s a', strtotime($array['ipf_response_on']));
    } else if ($array['ipf_response'] == 'rejected') {
        return '<span class="badge bg-danger text-start">IPF Rejected</span><br><br>' . date('d/m/y h:i:s a', strtotime($array['ipf_response_on']));
    }
}
?>


<?php function displayTDs($array)
{
    $td1 = '<td>' . $array['aname'] . ' (' . $array['appraisee_associatenumber'] . ')<br>' . $array['aemail'] . '</td>';
    $td2 = '<td>' . $array['mname'] . ' (' . $array['manager_associatenumber'] . ')<br>' . $array['memail'] . '</td>';
    $td3 = '<td>' . $array['rname'] . ' (' . $array['reviewer_associatenumber'] . ')<br>' . $array['remail'] . '</td>';
    $td4 = '<td>' . $array['appraisaltype'] . '<br>' . $array['appraisalyear'] . '</td>';

    return $td1 . $td2 . $td3 . $td4;
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

    <title>My Appraisal</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

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
        /*
 CSS for the main interaction
*/
        .tabset>input[type="radio"] {
            position: absolute;
            left: -200vw;
        }

        .tabset .tab-panel {
            display: none;
        }

        .tabset>input:first-child:checked~.tab-panels>.tab-panel:first-child,
        .tabset>input:nth-child(3):checked~.tab-panels>.tab-panel:nth-child(2),
        .tabset>input:nth-child(5):checked~.tab-panels>.tab-panel:nth-child(3),
        .tabset>input:nth-child(7):checked~.tab-panels>.tab-panel:nth-child(4),
        .tabset>input:nth-child(9):checked~.tab-panels>.tab-panel:nth-child(5),
        .tabset>input:nth-child(11):checked~.tab-panels>.tab-panel:nth-child(6) {
            display: block;
        }

        .tabset>label {
            position: relative;
            display: inline-block;
            padding: 15px 15px 25px;
            border: 1px solid transparent;
            border-bottom: 0;
            cursor: pointer;
            font-weight: 600;
        }

        .tabset>label::after {
            content: "";
            position: absolute;
            left: 15px;
            bottom: 10px;
            width: 22px;
            height: 4px;
            background: #8d8d8d;
        }

        .tabset>label:hover,
        .tabset>input:focus+label {
            color: #06c;
        }

        .tabset>label:hover::after,
        .tabset>input:focus+label::after,
        .tabset>input:checked+label::after {
            background: #06c;
        }

        .tabset>input:checked+label {
            border-color: #ccc;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
        }

        .tab-panel {
            padding: 30px 0;
            border-top: 1px solid #ccc;
        }


        /* #conf {
            position: fixed;
            bottom: 0;
            width: 81%;
            background-color: #f9f9f9;
        } */

        #close {
            float: right;
            padding: 2px 5px;
            background: #ccc;
        }

        #close:hover {
            float: right;
            padding: 2px 5px;
            background: #ccc;
            color: #fff;
        }
    </style>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>My Appraisal</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Performance management</a></li>
                    <li class="breadcrumb-item active">My Appraisal</li>
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
                                <?php
                                if ($role == 'Admin') { ?>
                                    <div class="col" style="display: inline-block; width:100%; text-align:right">
                                        <span class="noticea"><a href="process.php" target="_blank" title="Set Goals Now">Goal Setting Form</a> | <a href="ipf-management.php" title="Appraisal Workflow">Appraisal Workflow</a></span>
                                    </div>
                                <?php } ?>


                                <div class="tabset">
                                    <!-- Tab 1 -->
                                    <input type="radio" name="tabset" id="tab1" aria-controls="marzen" <?php echo (@$_GET['form-type'] == "") ? "checked" : "checked"; ?>>
                                    <label for="tab1">Appraisee</label>
                                    <!-- Tab 2 -->
                                    <input type="radio" name="tabset" id="tab2" aria-controls="rauchbier" <?php if (@$_GET['form-type'] == "manager") {
                                                                                                                echo "checked";
                                                                                                            } ?>>
                                    <label for="tab2">Manager</label>
                                    <!-- Tab 3 -->
                                    <input type="radio" name="tabset" id="tab3" aria-controls="dunkles" <?php if (@$_GET['form-type'] == "reviewer") {
                                                                                                            echo "checked";
                                                                                                        } ?>>
                                    <label for="tab3">Reviewer</label>

                                    <div class="tab-panels">
                                        <section id="marzen" class="tab-panel">
                                            <form action="" method="GET">
                                                <div class="form-group" style="display: inline-block;">
                                                    <input type="hidden" name="form-type" value="appraisee">
                                                    <div class="col2" style="display: inline-block;">
                                                        <select name="get_id" class="form-select" style="width:max-content; display:inline-block" placeholder="Appraisal type" required>
                                                            <?php if ($type == null) { ?>
                                                                <option value="" hidden selected>Select Appraisal type</option>
                                                            <?php
                                                            } else { ?>
                                                                <option hidden selected><?php echo $type ?></option>
                                                            <?php }
                                                            ?>
                                                            <!--<option>Quarterly 2/2021</option>-->
                                                            <option>Quarterly</option>
                                                            <option>Annual</option>
                                                            <option>Project End</option>
                                                        </select>

                                                        <select name="get_year" id="get_year" class="form-select" style="width:max-content; display:inline-block" placeholder="Year" required>
                                                            <?php if ($year == null) { ?>
                                                                <option value="" hidden selected>Select Year</option>
                                                            <?php
                                                            } else { ?>
                                                                <option hidden selected><?php echo $year ?></option>
                                                            <?php }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col2 left" style="display: inline-block;">
                                                    <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                                                        <i class="bi bi-search"></i>&nbsp;Search</button>
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
                                                    $('#get_year').append(new Option(year, year));
                                                    currentYear--;
                                                }
                                            </script>
                                            <?php if (sizeof($resultArr) > 0) { ?>
                                                <div class="table-responsive">
                                                    <table class="table">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Goal sheet ID</th>
                                                                <th scope="col">Appraisee</th>
                                                                <th scope="col">Manager</th>
                                                                <th scope="col">Reviewer</th>
                                                                <th scope="col">Appraisal details</th>
                                                                <th scope="col">Status</th>
                                                                <th scope="col">IPF</th>
                                                                <th scope="col">Goal Sheet</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            foreach ($resultArr as $array) {
                                                            ?>
                                                                <tr>

                                                                    <td><?php echo $array['goalsheetid'] ?></td>
                                                                    <?php echo displayTDs($array); ?>
                                                                    <td>
                                                                        <?php echo getAssessmentStatus($array); ?>
                                                                    </td>
                                                                    <td><?php echo ($array['reviewer_response_complete'] == "yes") ? $array['ipf'] : "" ?></td>
                                                                    <td>
                                                                        <span class="noticea">
                                                                            <a href="appraisee_response.php?goalsheetid=<?php echo $array['goalsheetid'] ?>" target="_blank" class="edit-link" title="Access Goal Sheet">Access Goal Sheet</a>
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>

                                            <?php
                                            } else if ($type == null && $year == null) {
                                            ?>
                                                <p>Please enter the appraisal type and year.</p>
                                            <?php
                                            } else {
                                            ?>
                                                <p>No results found for the filter value entered. Please adjust your search criteria and try again.</p>
                                            <?php } ?>
                                        </section>
                                        <section id="rauchbier" class="tab-panel">
                                            <form action="" method="GET">
                                                <div class="form-group" style="display: inline-block;">
                                                    <input type="hidden" name="form-type" value="manager">
                                                    <div class="col2" style="display: inline-block;">

                                                        <select name="get_yearm" id="get_yearm" class="form-select" style="width:max-content; display:inline-block" placeholder="Year" required>
                                                            <?php if ($yearm == null) { ?>
                                                                <option value="" hidden selected>Select Year</option>
                                                            <?php
                                                            } else { ?>
                                                                <option hidden selected><?php echo $yearm ?></option>
                                                            <?php }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col2 left" style="display: inline-block;">
                                                    <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                                                        <i class="bi bi-search"></i>&nbsp;Search</button>
                                                </div>
                                            </form>
                                            <script>
                                                <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
                                                    var currentYearm = new Date().getFullYear() - 1;
                                                <?php } else { ?>
                                                    var currentYearm = new Date().getFullYear();
                                                <?php } ?>

                                                for (var i = 0; i < 5; i++) {
                                                    var nextm = currentYearm + 1;
                                                    var yearm = currentYearm + '-' + nextm;
                                                    //next.toString().slice(-2) 
                                                    $('#get_yearm').append(new Option(yearm, yearm));
                                                    currentYearm--;
                                                }
                                            </script>
                                            <?php if (sizeof($resultArrm) > 0) { ?>
                                                <div class="table-responsive">
                                                    <table class="table">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Goal sheet ID</th>
                                                                <th scope="col">Appraisee</th>
                                                                <th scope="col">Manager</th>
                                                                <th scope="col">Reviewer</th>
                                                                <th scope="col">Appraisal details</th>
                                                                <th scope="col">Status</th>
                                                                <th scope="col">IPF</th>
                                                                <th scope="col">Goal Sheet</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            foreach ($resultArrm as $array) {
                                                            ?>

                                                                <tr>
                                                                    <td><?php echo $array['goalsheetid'] ?></td>
                                                                    <?php echo displayTDs($array); ?>
                                                                    <td>
                                                                        <?php echo getAssessmentStatus($array); ?>
                                                                    </td>
                                                                    <td><?php echo ($array['reviewer_response_complete'] == "yes") ? $array['ipf'] : "" ?></td>
                                                                    <td>
                                                                        <span class="noticea">
                                                                            <a href="manager_response.php?goalsheetid=<?php echo $array['goalsheetid'] ?>" target="_blank" class="edit-link" title="Access Goal Sheet">Access Goal Sheet</a>
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php
                                            } else if ($yearm == null) {
                                            ?>
                                                <p>Please enter the appraisal year.</p>
                                            <?php
                                            } else {
                                            ?>
                                                <p>No results found for the filter value entered. Please adjust your search criteria and try again.</p>
                                            <?php } ?>
                                        </section>

                                        <!-- REVIEWER -->

                                        <section id="dunkles" class="tab-panel">
                                            <form action="" method="GET">
                                                <div class="form-group" style="display: inline-block;">
                                                    <input type="hidden" name="form-type" value="reviewer">
                                                    <div class="col2" style="display: inline-block;">

                                                        <select name="get_yearr" id="get_yearr" class="form-select" style="width:max-content; display:inline-block" placeholder="Year" required>
                                                            <?php if ($yearr == null) { ?>
                                                                <option value="" hidden selected>Select Year</option>
                                                            <?php
                                                            } else { ?>
                                                                <option hidden selected><?php echo $yearr ?></option>
                                                            <?php }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col2 left" style="display: inline-block;">
                                                    <button type="submit" name="search_by_id" class="btn btn-primary btn-sm" style="outline: none;">
                                                        <i class="bi bi-search"></i>&nbsp;Search</button>
                                                </div>
                                            </form>
                                            <script>
                                                <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
                                                    var currentYearr = new Date().getFullYear() - 1;
                                                <?php } else { ?>
                                                    var currentYearr = new Date().getFullYear();
                                                <?php } ?>

                                                for (var i = 0; i < 5; i++) {
                                                    var nextr = currentYearr + 1;
                                                    var yearr = currentYearr + '-' + nextr;
                                                    //next.toString().slice(-2) 
                                                    $('#get_yearr').append(new Option(yearr, yearr));
                                                    currentYearr--;
                                                }
                                            </script>
                                            <?php if (sizeof($resultArrr) > 0) { ?>
                                                <div class="table-responsive">
                                                    <table class="table">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Goal sheet ID</th>
                                                                <th scope="col">Appraisee</th>
                                                                <th scope="col">Manager</th>
                                                                <th scope="col">Reviewer</th>
                                                                <th scope="col">Appraisal details</th>
                                                                <th scope="col">Status</th>
                                                                <th scope="col">IPF</th>
                                                                <th scope="col">Goal Sheet</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            foreach ($resultArrr as $array) {
                                                            ?>
                                                                <tr>
                                                                    <td><?php echo $array['goalsheetid'] ?></td>
                                                                    <?php echo displayTDs($array); ?>
                                                                    <td>
                                                                        <?php echo getAssessmentStatus($array); ?>
                                                                    </td>
                                                                    <td><?php echo ($array['reviewer_response_complete'] == "yes") ? $array['ipf'] : "" ?></td>
                                                                    <td>
                                                                        <span class="noticea">
                                                                            <a href="reviewer_response.php?goalsheetid=<?php echo $array['goalsheetid'] ?>" target="_blank" class="edit-link" title="Access Goal Sheet">Access Goal Sheet</a>
                                                                        </span>
                                                                    </td>

                                                                </tr>
                                                            <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php
                                            } else if ($yearr == null) {
                                            ?>
                                                <p>Please enter the appraisal year.</p>
                                            <?php
                                            } else {
                                            ?>
                                                <p>You're up-to-date! No review is pending at your end.</p>
                                            <?php } ?>
                                        </section>
                                    </div>
                                </div>


                                <?php if (@$_GET['form-type'] == "appraisee" && @$array['reviewer_response_complete'] == "yes" && @$array['ipf_response'] == null) { ?>
                                    <div id="conf">
                                        <form name="ipfsubmission" action="#" method="POST">
                                            <span id='close'>x</span>
                                            <input type="hidden" name="form-type" type="text" value="ipfsubmission">
                                            <input type="hidden" type="text" name="status2" id="count2" value="" readonly required>
                                            <input type="hidden" type="number" name="ipf" id="ipf" value="<?php echo $array['ipf'] ?>" readonly required>
                                            <input type="hidden" type="text" name="ipfid" id="ipfid" value="<?php echo $array['goalsheetid'] ?>" readonly required>
                                            <input type="hidden" type="text" name="ipf_response_by" id="ipf_response_by" value="<?php echo $associatenumber ?>" readonly required>
                                            <p style="display: inline-block; word-break: break-word; margin-left:5%; margin-top:2%">If you are not satisfied with the outcome of your appraisal discussion and the Individual Performance Factor (IPF) issued against the Goal Sheet ID <strong><?php echo @$array['goalsheetid'] ?></strong>, you have the option to reject the IPF. If you choose to reject it, another round of discussion will be scheduled with the concerned team to address your concerns and ensure that the IPF accurately reflects your performance.<br>
                                                Please note that if we do not receive a response from you by <?php echo @date('d/m/y h:i:s a', strtotime('+3 days', strtotime($array['goalsheet_reviewed_on']))) ?>, the Goal Sheet will be auto-closed.</p>
                                            <div style="margin-left:5%;">
                                                <button type="submit" id="yes" class="btn btn-success btn-sm close-button2" style="white-space:normal !important;word-wrap:break-word"><i class="bi bi-check2-circle" style="font-size: 17px;"></i> Accept</button>
                                                <button type="submit" id="no" class="btn btn-danger btn-sm close-button2" style="white-space:normal !important;word-wrap:break-word;"><i class="bi bi-x-lg" style="font-size: 17px;"></i> Reject</button>
                                            </div><br>
                                        </form>
                                    </div>
                                <?php } ?>

        </section>
        </section>

        <script>
            $('#yes').click(function() {
                $('#count2').val('accepted');
            });

            $('#no').click(function() {
                $('#count2').val('rejected');
            });
        </script>

        <script>
            const scriptURL = 'payment-api.php'
            const form = document.forms['ipfsubmission']

            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['ipfsubmission'])
                    })
                    .then(response =>
                        alert("Your response has been recorded.") +
                        location.reload()
                    )
                    .catch(error => console.error('Error!', error.message))
            })
        </script>
        <script>
            $(document).ready(function() {

                $('.close-button2').click(function(e) {

                    $('#conf').delay(10).fadeOut(700);
                    e.stopPropagation();
                });
            });
        </script>

        <script>
            window.addEventListener("load", function() {
                {
                    document.getElementById('close').onclick = function() {
                        this.parentNode.parentNode.parentNode
                            .removeChild(this.parentNode.parentNode);
                        return false;
                    };
                };
            }, false);
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