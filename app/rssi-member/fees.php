<?php
session_start();
// Storing Session
include("../util/login_util.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}
if ($role != 'Admin' && $role != 'Offline Manager') {

    //header("Location: javascript:history.back()"); //redirect to the login page to secure the welcome page without login access.
    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
?>
<?php
setlocale(LC_TIME, 'fr_FR.UTF-8');


@$id = $_POST['get_aid'];
@$status = $_POST['get_id'];
@$section = $_POST['get_category'];
@$stid = $_POST['get_stid'];
@$is_user = $_POST['is_user'];

if (($section != null && $section != 'ALL') && ($status != null && $status != 'ALL')) {

    $result = pg_query($con, "SELECT * FROM fees 
    
    left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    
    WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND DATE_PART('year', date::date)=$id AND student.category='$section' order by id desc");

    $totalapprovedamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND DATE_PART('year', date::date)=$id AND student.category='$section'");

    $totaltransferredamount = pg_query($con, "SELECT SUM(fees) FROM fees
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND DATE_PART('year', date::date)=$id AND student.category='$section' AND pstatus='transferred'");
}


if (($section == 'ALL' || $section == null) && ($status != null && $status != 'ALL')) {

    $result = pg_query($con, "SELECT * FROM fees 
    
    left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
    left join (SELECT student_id, studentname,category,contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    
    WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND DATE_PART('year', date::date)=$id order by id desc");

    $totalapprovedamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND DATE_PART('year', date::date)=$id");
    $totaltransferredamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE month=EXTRACT(MONTH FROM TO_DATE('$status', 'Month')) AND DATE_PART('year', date::date)=$id AND pstatus='transferred'");
}

if (($section != null && $section != 'ALL') && ($status == 'ALL' || $status == null)) {

    $result = pg_query($con, "SELECT * FROM fees 
    
    left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
    left join (SELECT student_id, studentname,category,contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    
    WHERE DATE_PART('year', date::date)=$id AND student.category='$section' order by id desc");

    $totalapprovedamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE DATE_PART('year', date::date)=$id AND student.category='$section'");
    $totaltransferredamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE DATE_PART('year', date::date)=$id AND student.category='$section' AND pstatus='transferred'");
}

if (($section == 'ALL' || $section == null) && ($status == 'ALL' || $status == null) && $id != null) {

    $result = pg_query($con, "SELECT * FROM fees 
    
    left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
    left join (SELECT student_id, studentname,category,contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    
    WHERE DATE_PART('year', date::date)=$id order by id desc");

    $totalapprovedamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE DATE_PART('year', date::date)=$id");
    $totaltransferredamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE DATE_PART('year', date::date)=$id AND pstatus='transferred'");
}

if ($stid != null && $status == null && $section == null && $id == null) {

    $result = pg_query($con, "SELECT * FROM fees 
    
    left join (SELECT associatenumber, fullname FROM rssimyaccount_members) faculty ON fees.collectedby=faculty.associatenumber
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    
    WHERE fees.studentid='$stid' order by id desc");

    $totalapprovedamount = pg_query($con, "SELECT SUM(fees) FROM fees 
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE fees.studentid='$stid'");

    $totaltransferredamount = pg_query($con, "SELECT SUM(fees) FROM fees
    left join (SELECT student_id, studentname, category, contact FROM rssimyprofile_student) student ON fees.studentid=student.student_id
    WHERE fees.studentid='$stid' AND pstatus='transferred'");
}

if ($stid == null && $status == null && $section == null && $id == null) {
    $result = pg_query($con, "SELECT * FROM fees WHERE month='0'");
    $totalapprovedamount = pg_query($con, "SELECT SUM(fees) FROM fees WHERE month='0'");
    $totaltransferredamount = pg_query($con, "SELECT SUM(fees) FROM fees WHERE month='0'");
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
$resultArrr = pg_fetch_result($totalapprovedamount, 0, 0);
$resultArrrr = pg_fetch_result($totaltransferredamount, 0, 0);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Fees Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
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
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?><br>Total collected amount:&nbsp;<p class="label label-default"><?php echo ($resultArrr - $resultArrrr) ?></p> + <p class="label label-success"><?php echo ($resultArrrr) ?></p> = <p class="label label-info"><?php echo ($resultArrr) ?></p>
                    </div>
                    <div class="col" style="display: inline-block; width:47%; text-align:right">
                        Home / <span class="noticea"><a href="student.php" target="_self">RSSI Student</a></span> / Fees Details<br><br>
                        <form method="POST" action="export_function.php">
                            <input type="hidden" value="fees" name="export_type" />
                            <input type="hidden" value="<?php echo $id ?>" name="id" />
                            <input type="hidden" value="<?php echo $status ?>" name="status" />
                            <input type="hidden" value="<?php echo $section ?>" name="section" />

                            <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Export CSV"><i class="fa-regular fa-file-excel" style="font-size:large;"></i></button>
                        </form>
                    </div>
                </div>
                <section class="box" style="padding: 2%;">
                    <form action="" method="POST">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">

                                <select name="get_aid" id="get_aid" class="form-control" style="width:max-content; display:inline-block" placeholder="Select policy year" required>
                                    <?php if ($id == null) { ?>
                                        <option value="" hidden selected>Select year</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $id ?></option>
                                    <?php }
                                    ?>
                                    <option>2022</option>
                                    <option>2021</option>
                                </select>

                                <select name="get_id" id="get_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Select policy year">
                                    <?php if ($status == null) { ?>
                                        <option value="" hidden selected>Select month</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $status ?></option>
                                    <?php }
                                    ?>
                                    <option>January</option>
                                    <option>February</option>
                                    <option>March</option>
                                    <option>April</option>
                                    <option>May</option>
                                    <option>June</option>
                                    <option>July</option>
                                    <option>August</option>
                                    <option>September</option>
                                    <option>October</option>
                                    <option>November</option>
                                    <option>December</option>
                                    <option>ALL</option>
                                </select>

                                <select name="get_category" id="get_category" class="form-control" style="width:max-content;display:inline-block">
                                    <?php if ($section == null) { ?>
                                        <option value="" disabled selected hidden>Select Category</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $section ?></option>
                                    <?php }
                                    ?>
                                    <option>LG2-A</option>
                                    <option>LG2-B</option>
                                    <option>LG2-C</option>
                                    <option>LG3</option>
                                    <option>LG4</option>
                                    <option>LG4S1</option>
                                    <option>LG4S2</option>
                                    <option>WLG3</option>
                                    <option>WLG4S1</option>
                                    <option>Undefined</option>
                                    <option>ALL</option>
                                </select>
                                <input name="get_stid" id="get_stid" class="form-control" style="width:max-content; display:inline-block" placeholder="Student ID" value="<?php echo $stid ?>" required>
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                        </div>
                        <div id="filter-checks">
                            <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_POST['is_user'])) echo "checked='checked'"; ?> />
                            <label for="is_user" style="font-weight: 400;">Search by Student ID</label>
                        </div>
                    </form>
                    <script>
                        if ($('#is_user').not(':checked').length > 0) {

                            document.getElementById("get_aid").disabled = false;
                            document.getElementById("get_id").disabled = false;
                            document.getElementById("get_category").disabled = false;
                            document.getElementById("get_stid").disabled = true;

                        } else {

                            document.getElementById("get_aid").disabled = true;
                            document.getElementById("get_id").disabled = true;
                            document.getElementById("get_category").disabled = true;
                            document.getElementById("get_stid").disabled = false;

                        }

                        const checkbox = document.getElementById('is_user');

                        checkbox.addEventListener('change', (event) => {
                            if (event.target.checked) {
                                document.getElementById("get_aid").disabled = true;
                                document.getElementById("get_id").disabled = true;
                                document.getElementById("get_category").disabled = true;
                                document.getElementById("get_stid").disabled = false;
                            } else {
                                document.getElementById("get_aid").disabled = false;
                                document.getElementById("get_id").disabled = false;
                                document.getElementById("get_category").disabled = false;
                                document.getElementById("get_stid").disabled = true;
                            }
                        })
                    </script>
                    <?php echo '
                        <table class="table">
                            <thead style="font-size: 12px;">
                                <tr>
                                <th scope="col">Ref.</th>
                                <th scope="col">Fees collection date</th>
                                <th scope="col">ID/F Name</th>    
                                <th scope="col">Category</th>
                                <th scope="col">Month</th>
                                <th scope="col">Amount (&#8377;)</th>
                                <th scope="col">Type</th>
                                <th scope="col">Collected by</th>
                                <th scope="col"></th>
                                </tr>
                            </thead>' ?>
                    <?php if ($resultArr != null) {
                        echo '<tbody>';
                        foreach ($resultArr as $array) {
                            echo '<tr>
                            <td>' . $array['id'] . '</td>
                            <td>' . substr($array['date'], 0, 10) . '</td>
                        <td>' . $array['studentid'] . '/' . strtok($array['studentname'], ' ') . '</td>
                        <td>' . $array['category'] . '</td>   
                        <td>' . @strftime('%B', mktime(0, 0, 0,  $array['month'])) . '</td>  
                        <td>' . $array['fees'] . '</td>
                        <td>' . $array['ptype'] . '</td>
                        <td>' . $array['fullname'] . '</td>
                        <td>
                        <form name="transfer_' . $array['id'] . '" action="#" method="POST" onsubmit="myFunction()" style="display: -webkit-inline-box;">
                        <input type="hidden" name="form-type" type="text" value="transfer">
                        <input type="hidden" name="pid" id="pid" type="text" value="' . $array['id'] . '">' ?>

                            <?php if ($array['pstatus'] != 'transferred' && $role == 'Admin') { ?>

                                <?php echo '<button type="submit" id="yes" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Transfer"><i class="fa-solid fa-arrow-up-from-bracket"></i></button>' ?>
                            <?php } ?>
                            <?php echo ' </form>


                        <form name="paydelete_' . $array['id'] . '" action="#" method="POST" onsubmit="myFunctionn()" style="display: -webkit-inline-box;">
                        <input type="hidden" name="form-type" type="text" value="paydelete">
                        <input type="hidden" name="pid" id="pid" type="text" value="' . $array['id'] . '">' ?>

                            <?php if ($array['pstatus'] != 'transferred' && $role == 'Admin') { ?>

                                <?php echo '&nbsp;&nbsp;&nbsp;<button type="submit" id="yes" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete"><i class="fa-solid fa-xmark"></i></button>' ?>
                            <?php } ?>
                        <?php echo ' </form>
                        
                        
                        
                        </td></tr>';
                        } ?>
                    <?php
                    } else if ($id == "" && $stid == "") {
                    ?>
                        <tr>
                            <td colspan="5">Please select Filter value.</td>
                        </tr>
                    <?php
                    } else if (sizeof($resultArr) == 0 && $stid == "") {
                    ?>
                        <tr>
                            <td colspan="5">No record found for <?php echo @$id ?>, <?php echo @$status ?> and <?php echo @$section ?></td>
                        </tr>

                    <?php } else if (sizeof($resultArr) == 0 && $stid != "") {
                    ?>
                        <tr>
                            <td colspan="5">No record found for <?php echo $stid ?></td>
                        </tr>
                    <?php }
                    echo '</tbody>
                        </table>';
                    ?>
                </section>
            </div>
        </section>
    </section>



    <script>
        function myFunction() {
            alert("Amount has been transferred.");
        }

        function myFunctionn() {
            alert("Entry has been deleted.");
        }
    </script>
    <script>
        var data = <?php echo json_encode($resultArr) ?>;
        var aid = <?php echo '"' . $_SESSION['aid'] . '"' ?>;

        //var pid = document.getElementById("pid")
        //pid.value = mydata["pid"]

        const scriptURL = 'payment-api.php'

        data.forEach(item => {
            const form = document.forms['transfer_' + item.id]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['transfer_' + item.id])
                    })
                    .then(response => console.log('Success!', response))
                    .catch(error => console.error('Error!', error.message))
            })

            console.log(item)
        })

        data.forEach(item => {
            const form = document.forms['paydelete_' + item.id]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['paydelete_' + item.id])
                    })
                    .then(response => console.log('Success!', response))
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