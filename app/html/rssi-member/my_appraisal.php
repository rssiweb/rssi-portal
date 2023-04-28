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
    $result = pg_query($con, "select * from appraisee_response WHERE appraisee_associatenumber='$associatenumber' AND appraisaltype='$type' AND appraisalyear='$year'");
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
    $resultm = pg_query($con, "select * from appraisee_response WHERE manager_associatenumber='$associatenumber'AND appraisalyear='$yearm' order by manager_evaluation_complete asc");
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
    $resultr = pg_query($con, "select * from appraisee_response WHERE reviewer_associatenumber='$associatenumber'AND appraisalyear='$yearr' order by reviewer_response_complete asc");
} else {
    $resultr = pg_query($con, "select * from appraisee_response WHERE goalsheetid is null");
}
$resultArrr = pg_fetch_all($resultr);
if (!$resultr) {
    echo "An error occurred.\n";
    exit;
}





?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Appraisal details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <link rel="stylesheet" href="/css/style.css" />
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
    </style>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php $appraisal_active = 'active'; ?>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">

                <div class="row">
                    <!-- <div class="col" style="display: inline-block; width:100%; text-align:right;vertical-align: top;">
                        <span class="noticea" title="Click here"><a href="ipf-management.php?get_aid=<?php echo $year ?>">Appraisal Workflow</a></span>
                    </div> -->

                    <section class="box" style="padding: 2%;">

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
                                                <select name="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type" required>
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
                                                    <option>Project end</option>
                                                </select>

                                                <select name="get_year" id="get_year" class="form-control" style="width:max-content; display:inline-block" placeholder="Year" required>
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
                                                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
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
                                        <?php
                                        foreach ($resultArr as $array) {
                                        ?>

                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Associate details</th>
                                                        <th scope="col">Appraisal type</th>
                                                        <th scope="col">Appraisal cycle</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col">View/Edit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>

                                                        <td><?php echo $array['appraisee_associatenumber'] ?></td>
                                                        <td><?php echo $array['appraisaltype'] ?></td>
                                                        <td><?php echo $array['appraisalyear'] ?></td>
                                                        <td>
                                                            <?php if ($array['appraisee_response_complete'] == "" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "") { ?>
                                                                <span class="label label-danger float-end">Self-assessment</span>
                                                            <?php } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "") { ?>
                                                                <span class="label label-warning float-end">Manager assessment in progress</span>
                                                            <?php } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "") { ?>
                                                                <span class="label label-primary float-end">Reviewer assessment in progress</span>
                                                            <?php } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "yes") { ?>
                                                                <span class="label label-success float-end">IPF released</span>


                                                            <?php } ?>
                                                        </td>
                                                        <td><span class="noticet"><a href="appraisee_response.php?goalsheetid=<?php echo $array['goalsheetid'] ?>" target="_blank" title="<?php echo $array['goalsheetid'] ?>">Goal Sheet</a></span></td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        <?php
                                        }
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

                                                <select name="get_yearm" id="get_yearm" class="form-control" style="width:max-content; display:inline-block" placeholder="Year" required>
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
                                                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
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
                                        <?php
                                        foreach ($resultArrm as $array) {
                                        ?>

                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Associate details</th>
                                                        <th scope="col">Appraisal type</th>
                                                        <th scope="col">Appraisal cycle</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col">View/Edit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>

                                                        <td><?php echo $array['appraisee_associatenumber'] ?></td>
                                                        <td><?php echo $array['appraisaltype'] ?></td>
                                                        <td><?php echo $array['appraisalyear'] ?></td>
                                                        <td>
                                                            <?php if ($array['appraisee_response_complete'] == "" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "") { ?>
                                                                <span class="label label-danger float-end">Self-assessment</span>
                                                            <?php } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "") { ?>
                                                                <span class="label label-warning float-end">Manager assessment in progress</span>
                                                            <?php } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "") { ?>
                                                                <span class="label label-primary float-end">Reviewer assessment in progress</span>
                                                            <?php } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "yes") { ?>
                                                                <span class="label label-success float-end">IPF released</span>


                                                            <?php } ?>
                                                        </td>
                                                        <td><span class="noticet"><a href="manager_response.php?goalsheetid=<?php echo $array['goalsheetid'] ?>" target="_blank" title="<?php echo $array['goalsheetid'] ?>">Goal Sheet</a></span></td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        <?php
                                        }
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

                                                <select name="get_yearr" id="get_yearr" class="form-control" style="width:max-content; display:inline-block" placeholder="Year" required>
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
                                                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
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
                                        <?php
                                        foreach ($resultArrr as $array) {
                                        ?>

                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Associate details</th>
                                                        <th scope="col">Appraisal type</th>
                                                        <th scope="col">Appraisal cycle</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col">View/Edit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>

                                                        <td><?php echo $array['appraisee_associatenumber'] ?></td>
                                                        <td><?php echo $array['appraisaltype'] ?></td>
                                                        <td><?php echo $array['appraisalyear'] ?></td>
                                                        <td>
                                                            <?php if ($array['appraisee_response_complete'] == "" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "") { ?>
                                                                <span class="label label-danger float-end">Self-assessment</span>
                                                            <?php } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "") { ?>
                                                                <span class="label label-warning float-end">Manager assessment in progress</span>
                                                            <?php } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "") { ?>
                                                                <span class="label label-primary float-end">Reviewer assessment in progress</span>
                                                            <?php } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "yes") { ?>
                                                                <span class="label label-success float-end">IPF released</span>


                                                            <?php } ?>
                                                        </td>
                                                        <td><span class="noticet"><a href="reviewer_response.php?goalsheetid=<?php echo $array['goalsheetid'] ?>" target="_blank" title="<?php echo $array['goalsheetid'] ?>">Goal Sheet</a></span></td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                        <?php
                                        }
                                    } else if ($yearm == null) {
                                        ?>
                                        <p>Please enter the appraisal year.</p>
                                    <?php
                                    } else {
                                    ?>
                                        <p>No results found for the filter value entered. Please adjust your search criteria and try again.</p>
                                    <?php } ?>
                                </section>
                            </div>
                        </div>


                    </section>
        </section>

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