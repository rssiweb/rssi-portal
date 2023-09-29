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

include("../../util/email.php");

@$now = date('Y-m-d H:i:s');
if ($role == "Admin") {
    if (@$_POST['form-type'] == "leaveallocation") {
        @$leaveallocationid = 'RLA' . time();
        @$allo_applicantid = strtoupper($_POST['allo_applicantid']);
        @$allo_daycount = $_POST['allo_daycount'];
        @$allo_leavetype = $_POST['allo_leavetype'];
        @$allo_remarks = $_POST['allo_remarks'];
        @$allocatedbyid = $_POST['associatenumber'];
        @$allo_academicyear = $_POST['allo_academicyear'];
        @$allocatedbyid = $associatenumber;
        @$allocatedbyname = $fullname;

        if ($leaveallocationid != "") {

            $leaveallocation = "INSERT INTO leaveallocation (leaveallocationid,allo_applicantid,allo_daycount,allo_leavetype,allo_remarks,allocatedbyid,allo_date,allocatedbyname,allo_academicyear) VALUES ('$leaveallocationid','$allo_applicantid','$allo_daycount','$allo_leavetype','$allo_remarks','$allocatedbyid','$now','$allocatedbyname','$allo_academicyear')";

            $result = pg_query($con, $leaveallocation);
            $cmdtuples = pg_affected_rows($result);


            $resultt = pg_query($con, "Select fullname,email from rssimyaccount_members where associatenumber='$allo_applicantid'");
            @$nameassociate = pg_fetch_result($resultt, 0, 0);
            @$emailassociate = pg_fetch_result($resultt, 0, 1);

            $resulttt = pg_query($con, "Select studentname,emailaddress from rssimyprofile_student where student_id='$allo_applicantid'");
            @$namestudent = pg_fetch_result($resulttt, 0, 0);
            @$emailstudent = pg_fetch_result($resulttt, 0, 1);

            $fullname = $nameassociate . $namestudent;
            $email = $emailassociate . $emailstudent;
        }
    }
}

@$id = $_GET['leaveallocationid'];
@$appid = strtoupper($_GET['allo_applicantid']);
@$allo_academicyear = $_GET['allo_academicyear_search'];
@$is_user = $_GET['is_user'];

date_default_timezone_set('Asia/Kolkata');
// $date = date('Y-d-m h:i:s');

if ($role == "Admin") {

    if ($appid != null && $allo_academicyear != null) {
        $result = pg_query($con, "select * from leaveallocation left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leaveallocation.allo_applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leaveallocation.allo_applicantid=student.student_id  WHERE allo_applicantid='$appid' AND allo_academicyear='$allo_academicyear' order by allo_date desc");
    } else if ($id != null) {
        $result = pg_query($con, "select * from leaveallocation left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leaveallocation.allo_applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leaveallocation.allo_applicantid=student.student_id WHERE leaveallocationid='$id' order by allo_date desc");
    } else {
        $result = pg_query($con, "select * from leaveallocation left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leaveallocation.allo_applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leaveallocation.allo_applicantid=student.student_id order by allo_date desc");
    }
}

if ($role != "Admin") {

    if ($allo_academicyear != null) {
        $result = pg_query($con, "select * from leaveallocation left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leaveallocation.allo_applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leaveallocation.allo_applicantid=student.student_id  WHERE allo_applicantid='$associatenumber' AND allo_academicyear='$allo_academicyear' order by allo_date desc");
    } else if ($id != null) {
        $result = pg_query($con, "select * from leaveallocation left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leaveallocation.allo_applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leaveallocation.allo_applicantid=student.student_id WHERE allo_applicantid='$associatenumber' AND leaveallocationid='$id' order by allo_date desc");
    } else {
        $result = pg_query($con, "select * from leaveallocation left join (SELECT associatenumber,fullname, email, phone FROM rssimyaccount_members) faculty ON leaveallocation.allo_applicantid=faculty.associatenumber  left join (SELECT student_id,studentname,emailaddress, contact FROM rssimyprofile_student) student ON leaveallocation.allo_applicantid=student.student_id WHERE allo_applicantid='$associatenumber' order by allo_date desc");
    }
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html>

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

    <title>Leave Allocation</title>

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

    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Leave Allocation</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">My Services</a></li>
                    <li class="breadcrumb-item"><a href="leave.php">Apply for Leave</a></li>
                    <li class="breadcrumb-item active">Leave Allocation</li>
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
                                <?php if (@$leaveallocationid != null && @$cmdtuples == 0) { ?>
                                    <div class="alert alert-danger alert-dismissible text-center" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>ERROR: Oops, something wasn't right.</span>
                                    </div>
                                <?php } else if (@$cmdtuples == 1) { ?>
                                    <div class="alert alert-success alert-dismissible text-center" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-check2-circle"></i>
                                        <span>Your request has been submitted. Leave allocation id <?php echo $leaveallocationid ?>.</span>
                                    </div>
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                                <?php } ?>


                                <!-- <section style="padding: 2%;"> -->
                                <?php if ($role == "Admin") { ?>
                                    <div class="col text-end">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#popup">Import file</a>
                                    </div>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col" colspan="2">Allocate Leave</th>
                                            </tr>
                                        </thead>
                                    </table>

                                    <form autocomplete="off" name="leaveallocation" id="leaveallocation" action="leaveallo.php" method="POST">
                                        <div class="form-group" style="display: inline-block;">

                                            <input type="hidden" name="form-type" type="text" value="leaveallocation">

                                            <span class="input-help">
                                                <input type="text" name="allo_applicantid" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo @$_GET['allo_applicantid']; ?>" required>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Applicant ID*</small>
                                            </span>

                                            <span class="input-help">
                                                <input type="number" name="allo_daycount" id='allo_daycount' class="form-control" placeholder="Day count" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" required>
                                                <small id="passwordHelpBlock_allo_daycount" class="form-text text-muted">Allocated day*</small>
                                            </span>
                                            <span class="input-help">
                                                <select name="allo_leavetype" id="allo_leavetype" class="form-select" style="display: -webkit-inline-box; width:20vh; " required>
                                                    <option value="" disabled selected hidden>Types of Leave</option>
                                                    <option value="Sick Leave">Sick Leave</option>
                                                    <option value="Casual Leave">Casual Leave</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Adjusted Leave Type*</small>
                                            </span>
                                            <span class="input-help">
                                                <select name="allo_academicyear" id="allo_academicyear" class="form-select" style="display: -webkit-inline-box; width:20vh; " required>
                                                    <option value="" disabled selected hidden>Academic Year</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Academic Year</small>
                                            </span>

                                            <span class="input-help">
                                                <textarea type="text" name="allo_remarks" class="form-control" placeholder="Remarks" value=""></textarea>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Remarks</small>
                                            </span>
                                        </div>
                                        <div class="col2 left" style="display: inline-block;">
                                            <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">Add Leave</button>

                                        </div>

                                    </form>

                                    <script>
                                        <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
                                            var currentYear = new Date().getFullYear() - 1;
                                        <?php } else { ?>
                                            var currentYear = new Date().getFullYear();
                                        <?php } ?>
                                        for (var i = 0; i < 2; i++) {
                                            var next = currentYear + 1;
                                            var year = currentYear + '-' + next;
                                            //next.toString().slice(-2)
                                            $('#allo_academicyear').append(new Option(year, year));
                                            currentYear--;
                                        }
                                    </script>
                                <?php } ?>
                                <table class="table" style="margin-top: 2%;">
                                    <thead>
                                        <tr>
                                            <th scope="col" colspan="2">Leave Allocation Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <form action="" method="GET">
                                                    <div class="form-group" style="display: inline-block;">
                                                        <div class="col2" style="display: inline-block;">
                                                            <input name="leaveallocationid" id="leaveallocationid" class="form-control" style="width:max-content; display:inline-block" placeholder="Leave Allocation ID" value="<?php echo $id ?>">
                                                            <?php if ($role == "Admin") { ?>
                                                                <input name="allo_applicantid" id="allo_applicantid" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo $appid ?>" required>
                                                            <?php } ?>
                                                            <select name="allo_academicyear_search" id="allo_academicyear_search" class="form-select" style="width:max-content; display:inline-block" placeholder="Appraisal type" required>
                                                                <?php if ($allo_academicyear == null) { ?>
                                                                    <option value="" disabled selected hidden>Academic Year</option>
                                                                <?php
                                                                } else { ?>
                                                                    <option hidden selected><?php echo $allo_academicyear ?></option>
                                                                <?php }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col2 left" style="display: inline-block;">
                                                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                                            <i class="bi bi-search"></i>&nbsp;Search</button>
                                                    </div>
                                                    <?php if ($role == "Admin") { ?>
                                                        <div id="filter-checks">
                                                            <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_GET['is_user'])) echo "checked='checked'"; ?> />
                                                            <label for="is_user" style="font-weight: 400;">Search by Leave Allocation ID</label>
                                                        </div>
                                                    <?php } ?>
                                                </form>
                                                <?php if ($role == "Admin") { ?>
                                                    <script>
                                                        if ($('#is_user').not(':checked').length > 0) {

                                                            document.getElementById("leaveallocationid").disabled = true;
                                                            document.getElementById("allo_applicantid").disabled = false;
                                                            document.getElementById("allo_academicyear_search").disabled = false;

                                                        } else {

                                                            document.getElementById("leaveallocationid").disabled = false;
                                                            document.getElementById("allo_applicantid").disabled = true;
                                                            document.getElementById("allo_academicyear_search").disabled = true;

                                                        }

                                                        const checkbox = document.getElementById('is_user');

                                                        checkbox.addEventListener('change', (event) => {
                                                            if (event.target.checked) {
                                                                document.getElementById("leaveallocationid").disabled = false;
                                                                document.getElementById("allo_applicantid").disabled = true;
                                                                document.getElementById("allo_academicyear_search").disabled = true
                                                            } else {
                                                                document.getElementById("leaveallocationid").disabled = true;
                                                                document.getElementById("allo_applicantid").disabled = false;
                                                                document.getElementById("allo_academicyear_search").disabled = false;
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
                                                        $('#allo_academicyear_search').append(new Option(year, year));
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
                                <th scope="col">Leave allocation id</th>
                                <th scope="col">Applicant ID</th>
                                <th scope="col">Applied on</th>
                                <th scope="col">Allocated day(s)</th>
                                <th scope="col">Allocated Leave Type</th>
                                <th scope="col">Allocatd by</th>
                                <th scope="col" width="15%">Remarks</th>' ?>
                                <?php if ($role == "Admin") { ?>
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
                                        echo '<td>' . $array['leaveallocationid'] . '</td>
                                <td>' . $array['allo_applicantid'] . '<br>' . $array['fullname'] . $array['studentname'] . '</td>
                                <td>' . @date("d/m/Y g:i a", strtotime($array['allo_date'])) . '</td>
                                <td>' . $array['allo_daycount'] . '</td>
                                <td>' . $array['allo_leavetype'] . '/' . $array['allo_academicyear'] . '</td>
                                <td>' . $array['allocatedbyid'] . '<br>' . $array['allocatedbyname'] . '</td>
                                <td>' . $array['allo_remarks'] . '</td>' ?>

                                        <?php if ($role == "Admin") { ?>

                                            <?php echo '<td>
                                    
                                    <form name="leaveallodelete_' . $array['leaveallocationid'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                    <input type="hidden" name="form-type" type="text" value="leaveallodelete">
                                    <input type="hidden" name="leaveallodeleteid" id="leaveallodeleteid" type="text" value="' . $array['leaveallocationid'] . '">

                                    <button type="submit" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['leaveallocationid'] . '"><i class="bi bi-x-lg"></i></button>
                                </form>
                                </td>' ?>
                                        <?php } ?>
                                    <?php } ?>
                                <?php
                                } else if ($id == null && $allo_academicyear == null) {
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
                                                const form = document.forms['leaveallodelete_' + item.leaveallocationid]
                                                form.addEventListener('submit', e => {
                                                    e.preventDefault()
                                                    fetch(scriptURL, {
                                                            method: 'POST',
                                                            body: new FormData(document.forms['leaveallodelete_' + item.leaveallocationid])
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
    <!-- Popup -->
    <div class="modal fade" id="popup" tabindex="-1" aria-labelledby="popupLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="popupLabel">Import file</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Body -->
                <div class="modal-body">
                    <!-- <div class="container mt-5"> -->
                    <form action="upload.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="fileInput" class="form-label">Choose a File</label>
                            <input type="file" class="form-control" id="fileInput" name="fileInput" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Upload File</button>
                    </form>
                    <!-- </div> -->


                </div>
                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

</body>

</html>