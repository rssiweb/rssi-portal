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
if ($role != 'Admin') {

    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}

@$id = $_GET['get_aid'];

if ($id != null) {

    $result = pg_query($con, "select appraisee.fullname aname, appraisee.email aemail, manager.fullname mname, manager.email memail, reviewer.fullname rname, reviewer.email remail,*  from appraisee_response
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) appraisee ON appraisee.associatenumber = appraisee_response.appraisee_associatenumber
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) manager ON manager.associatenumber = appraisee_response.manager_associatenumber
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) reviewer ON reviewer.associatenumber = appraisee_response.manager_associatenumber
    WHERE appraisalyear='$id'");
}

if ($id == null) {

    $result = pg_query($con, "select * from appraisee_response WHERE goalsheetid is null");
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Appraisal Workflow</title>
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
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }

        #btn {
            border: none !important;
        }
    </style>

</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>

    <?php include 'header.php'; ?>
    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col" style="display: inline-block; width:50%;margin-left:1.5%; font-size:small">
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                    </div>
                    <div class="col" style="display: inline-block; width:47%; text-align:right">
                        Home / <span class="noticea"><a href="my_appraisal.php">My Appraisal</a></span> / Appraisal Workflow<br><br>
                    </div>
                </div>
                <section class="box" style="padding: 2%;">
                    <form action="" method="GET">
                        <div class="form-group">
                            <div class="col2" style="display: inline-block;">

                                <select name="get_aid" id="get_aid" class="form-control" style="width:max-content; display:inline-block" placeholder="Select policy year" required>
                                    <?php if ($id == null) { ?>
                                        <option value="" hidden selected>Select year</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $id ?></option>
                                    <?php }
                                    ?>
                                </select>
                            </div>
                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
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
                            $('#get_aid').append(new Option(year, year));
                            currentYear--;
                        }
                    </script>
                    <br><br>
                    <?php echo '
                        <table class="table">
                            <thead>
                                <tr>
                                <th scope="col">Goal sheet ID</th>
                                <th scope="col">Appraisee</th>    
                                <th scope="col">Manager</th>
                                <th scope="col">Reviewer</th>
                                <th scope="col">Appraisal details</th>
                                <th scope="col">Initiated on</th>
                                <th scope="col">IPF</th>
                                <th scope="col">Status</th>
                                <th scope="col">Appraisee responded on</th>
                                <th scope="col">Closed on</th>
                                </tr>
                            </thead>' ?>
                    <?php if ($resultArr != null) {
                        echo '<tbody>';
                        foreach ($resultArr as $array) {
                            echo '<tr><td>' . $array['goalsheetid'] . '</td>
                        <td>' . $array['aname'] . ' (' . $array['appraisee_associatenumber'] . ')' . '</td>
                        <td>' . $array['mname'] . ' (' . $array['manager_associatenumber'] . ')' . '</td>
                        <td>' . $array['rname'] . ' (' . $array['manager_associatenumber'] . ')' . '</td>
                        <td>' . $array['appraisaltype'] . '<br>' . $array['appraisalyear'] . '</td>
                        <td>' . date('d/m/y h:i:s a', strtotime($array['goalsheet_created_on'])) . '</td>  
                        <td>' . $array['ipf'] . '</td>     
                        <td>' ?><?php if ($array['appraisee_response_complete'] == "" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "") {
                                    echo '<span class="label label-danger float-end">Self-assessment</span>';
                                } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "") {
                                    echo '<span class="label label-warning float-end">Manager assessment in progress</span>';
                                } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "") {
                                    echo '<span class="label label-primary float-end">Reviewer assessment in progress</span>';
                                } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "yes" && $array['ipf_response'] == null) {
                                    echo '<span class="label label-success float-end">IPF released</span>';
                                } else if ($array['ipf_response'] == 'accepted') {
                                    echo '<span class="label label-success float-end">IPF Accepted</span>';
                                } else if ($array['ipf_response'] == 'rejected') {
                                    echo '<span class="label label-danger float-end">IPF Rejected</span>';
                                } ?><?php '</td>' ?>

                    <?php echo '<td>' . date('d/m/y h:i:s a', strtotime($array['ipf_response_on'])) . '</td>' ?>

                    <?php echo '<td>

                        <form name="ipfclose' . $array['goalsheetid'] . '" action="#" method="POST">
                        <input type="hidden" name="form-type" type="text" value="ipfclose">
                        <input type="hidden" name="ipfid" id="ipfid" type="text" value="' . $array['goalsheetid'] . '">
                        <input type="hidden" name="ipf_process_closed_by" id="ipf_process_closed_by" type="text" value="'.$associatenumber.'">' ?>

                    <?php if ($array['ipf_process_closed_by'] == null && $role == 'Admin') { ?>

                        <?php echo '<button type="submit" id="yes" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Closed"><i class="fa-solid fa-arrow-up-from-bracket"></i></button>' ?>
                    <?php } ?>
                    <?php echo '</form>' ?>

                    <?php if ($array['ipf_process_closed_by'] != null) { ?>
                        <?php echo date('d/m/y h:i:s a', strtotime($array['ipf_process_closed_on'])) ?>
                    <?php } else {
                            } ?>

                    <?php echo '</td>' ?>
                    <?php  }
                    } else if (@$id == null) {
                        echo '<tr>
                                <td colspan="6">Please select Filter value.</td>
                            </tr>';
                    } else {
                        echo '<tr>
                            <td colspan="6">No record found for' ?>&nbsp;<?php echo @$id ?>
                <?php echo '</td>
                        </tr>';
                    }
                    echo '</tbody>
                        </table>';
                ?>
                </section>
            </div>
        </section>
    </section>



    <!-- <script>
        function myFunction() {
            alert("The process has been closed in the system.");
        }
    </script> -->
    <script>
        var data = <?php echo json_encode($resultArr) ?>;
        
        const scriptURL = 'payment-api.php'

        data.forEach(item => {
            const form = document.forms['ipfclose' + item.goalsheetid]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['ipfclose' + item.goalsheetid])
                    })
                    .then(response =>
                        alert("The process has been closed in the system.") +
                        location.reload()
                    )
                    .catch(error => console.error('Error!', error.message))
            })

            console.log(item)
        })
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