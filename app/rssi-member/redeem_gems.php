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

if (@$_POST['form-type'] == "gms") {
    @$redeem_id1 = $_POST['redeem_id'];
    @$user_id1 = strtoupper($_POST['user_id']);
    @$user_name = $_POST['user_name'];
    @$redeem_gems_point = $_POST['redeem_gems_point'];
    @$redeem_type = $_POST['redeem_type'];
    @$now = date('Y-m-d H:i:s');
    if ($redeem_id1 != "") {
        $redeem = "INSERT INTO gems (redeem_id, user_id, user_name, redeem_gems_point, redeem_type,requested_on) VALUES ('$redeem_id1','$user_id1','$user_name','$redeem_gems_point','$redeem_type','$now')";
        $result = pg_query($con, $redeem);
        $cmdtuples = pg_affected_rows($result);
    }
}
?>
<?php if ($role == 'Admin') {
    @$redeem_id = strtoupper($_GET['redeem_id']);
    @$user_id = strtoupper($_GET['user_id']);
    @$is_user = $_GET['is_user'];

    if (($redeem_id == null && $user_id == null)) {

        $result = pg_query($con, "SELECT * FROM gems order by requested_on desc");
        $totalgemsredeem = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems");
        $totalgemsreceived = pg_query($con, "SELECT COALESCE(SUM(gems),0) FROM certificate");
        $totalgemsredeem_admin = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems where user_id='$associatenumber'AND (reviewer_status is null or reviewer_status !='Rejected')");
        $totalgemsreceived_admin = pg_query($con, "SELECT COALESCE(SUM(gems),0) FROM certificate where awarded_to_id='$associatenumber'");
        $totalgemsredeem_approved = pg_query($con, "SELECT COALESCE(SUM(redeem_gems_point),0) FROM gems");
    }

    if (($redeem_id != null)) {

        $result = pg_query($con, "SELECT * FROM gems where redeem_id='$redeem_id' order by requested_on desc");
        $totalgemsredeem = pg_query($con, "SELECT SUM(redeem_gems_point) FROM gems where redeem_id=''");
        $totalgemsreceived = pg_query($con, "SELECT SUM(gems) FROM certificate where certificate_no=''");
        $totalgemsredeem_admin = pg_query($con, "SELECT SUM(redeem_gems_point) FROM gems where redeem_id=''");
        $totalgemsreceived_admin = pg_query($con, "SELECT SUM(gems) FROM certificate where certificate_no=''");
        $totalgemsredeem_approved = pg_query($con, "SELECT SUM(redeem_gems_point) FROM gems where redeem_id=''");
    }

    if (($user_id != null)) {

        $result = pg_query($con, "SELECT * FROM gems where user_id='$user_id' order by requested_on desc");
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
} ?>
<?php if ($role != 'Admin') {

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
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>RSSI-Gems Redeem</title>
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
                        <div class="col" style="display: inline-block; width:50%;margin-left:1.5%">Home / <span class="noticea"><a href="my_certificate.php">My Certificate</a></span> / Gems Management System (CMS)
                        </div>
                    <?php } else { ?>
                        <div class="col" style="display: inline-block; width:50%;margin-left:1.5%">Home / <span class="noticea"><a href="my_certificate.php">My Certificate</a></span> / Gems Redeem
                        </div>
                    <?php } ?>
                    <?php if ($role == 'Admin') { ?>
                        <div class="col" style="display: inline-block; width:47%; text-align:right">

                            <?php if ($resultArrrr_admin - $resultArrr_admin != null) { ?>
                                <div style="display: inline-block; width:100%; font-size:small; text-align:right;"><i class="fa-regular fa-gem" style="font-size:medium;" title="RSSI Gems"></i>&nbsp;<p class="label label-success"><?php echo ($resultArrrr_admin - $resultArrr_admin) ?></p>
                                </div>
                            <?php } else { ?>

                                <i class="fa-regular fa-gem" style="font-size:medium;" title="RSSI Gems"></i>&nbsp;<p class="label label-default">You're almost there</p>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="col" style="display: inline-block; width:47%; text-align:right">

                            <?php if ($resultArrrr - $resultArrr != null) { ?>
                                <div style="display: inline-block; width:100%; font-size:small; text-align:right;"><i class="fa-regular fa-gem" style="font-size:medium;" title="RSSI Gems"></i>&nbsp;<p class="label label-success"><?php echo ($resultArrrr - $resultArrr) ?></p>
                                </div>
                            <?php } else { ?>

                                <i class="fa-regular fa-gem" style="font-size:medium;" title="RSSI Gems"></i>&nbsp;<p class="label label-default">You're almost there</p>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>

                <?php if (@$redeem_id1 != null && @$cmdtuples == 0) { ?>

                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                    </div>
                <?php
                } else if (@$cmdtuples == 1) { ?>

                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Your request has been submitted. Redeem id <?php echo $redeem_id1 ?>.</span>
                    </div>
                    <script>
                        if (window.history.replaceState) {
                            window.history.replaceState(null, null, window.location.href);
                        }
                    </script>
                <?php } ?>

                <div class="row">
                    <section class="box" style="padding: 2%;">
                        <!-- <button onclick="myFunction()">Try it</button>
                        <p id="demo"></p>
                        <script>
                            function myFunction() {
                                var x = document.getElementById("reviewform").name;
                                document.getElementById("demo").innerHTML = "The name of the form is: " + x;
                            }
                        </script> -->

                        <form autocomplete="off" name="gms" id="gms" action="redeem_gems.php" method="POST">
                            <div class="form-group" style="display: inline-block;">

                                <input type="hidden" name="form-type" type="text" value="gms">

                                <span class="input-help">
                                    <input type="hidden" name="redeem_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Redeem id" value="RSG<?php echo time() ?>" required readonly>
                                </span>

                                <span class="input-help">
                                    <input type="text" name="user_id" class="form-control" style="width:max-content; display:inline-block" placeholder="User Id" value="<?php echo $associatenumber ?>" required readonly>
                                    <small id="passwordHelpBlock" class="form-text text-muted">User Id*</small>
                                </span>

                                <span class="input-help">
                                    <input type="text" name="user_name" class="form-control" style="width:max-content; display:inline-block" placeholder="User name" value="<?php echo $fullname ?>" required readonly>
                                    <small id="passwordHelpBlock" class="form-text text-muted">User name*</small>
                                </span>

                                <?php if ($role == 'Admin') { ?>
                                    <span class="input-help">
                                        <input type="number" name="redeem_gems_point" class="form-control" placeholder="Gems" max="<?php echo ($resultArrrr_admin - $resultArrr_admin) ?>" min="1">
                                        <small id="passwordHelpBlock" class="form-text text-muted">Redeem gems point</small>
                                    </span>

                                <?php } ?>

                                <?php if ($role != 'Admin') { ?>

                                    <span class="input-help">
                                        <input type="number" name="redeem_gems_point" class="form-control" placeholder="Gems" max="<?php echo ($resultArrrr - $resultArrr) ?>" min="1">
                                        <small id="passwordHelpBlock" class="form-text text-muted">Redeem gems point</small>
                                    </span>
                                <?php } ?>


                                <span class="input-help">
                                    <select name="redeem_type" class="form-control" style="width:max-content; display:inline-block" required>
                                        <?php if ($redeem_type == null) { ?>
                                            <option value="" disabled selected hidden>Redeem type</option>
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

                                <?php if (($role == 'Admin') && ($resultArrrr_admin - $resultArrr_admin) == null) { ?>
                                    <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;" disabled>
                                        <i class="fa-solid fa-minus"></i>&nbsp;&nbsp;Redeem</button>
                                <?Php } ?>
                                <?php if (($role == 'Admin') && ($resultArrrr_admin - $resultArrr_admin) != null) { ?>
                                    <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                        <i class="fa-solid fa-minus"></i>&nbsp;&nbsp;Redeem</button>
                                <?Php } ?>


                                <?php if (($role != 'Admin') && ($resultArrrr - $resultArrr) == null) { ?>
                                    <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;" disabled>
                                        <i class="fa-solid fa-minus"></i>&nbsp;&nbsp;Redeem</button>
                                <?Php } ?>
                                <?php if (($role != 'Admin') && ($resultArrrr - $resultArrr) != null) { ?>
                                    <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                        <i class="fa-solid fa-minus"></i>&nbsp;&nbsp;Redeem</button>
                                <?Php } ?>

                            </div>

                        </form>

                        <div style="display: inline-block; width:100%; font-size:small; text-align:right;">Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                        </div>
                        <?php if ($role == 'Admin' && $user_id!=null) { ?>
                        <div class="col" style="display: inline-block; width:100%; text-align:right">
                            <div style="display: inline-block; width:100%; font-size:small; text-align:right;">Balance:&nbsp;
                                <?php if ($resultArrrr - $gems_approved <= 0) { ?>
                                    <p class="label label-danger"><?php echo ($resultArrrr - $gems_approved) ?></p>
                                <?php } else { ?>

                                    <p class="label label-info"><?php echo ($resultArrrr - $gems_approved) ?></p>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>


                    <?php if ($role == 'Admin') { ?>

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
                                    <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
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
                            <th scope="col">Redeem id</th>
                            <th scope="col">Requested on</th>' ?>

                    <?php if ($role == 'Admin') { ?>
                        <?php echo '<th scope="col">User id</th>' ?>
                    <?php } ?>
                    <?php echo ' <th scope="col">Gems point</th>
                            <th scope="col">Redeem type</th>
                            <th scope="col">Reviewer id</th>
                            <th scope="col">Reviewer status</th>
                            <th scope="col">Reviewer status updated on</th>
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
                                <?php echo '<td>' . $array['user_id'] . '<br>' . $array['user_name'] . '</td>' ?>
                            <?php } ?>
                            <?php echo '<td>' . $array['redeem_gems_point'] . '</td>
                                <td>' . $array['redeem_type'] . '</td>
                                <td>' . $array['reviewer_id'] . '<br>' . $array['reviewer_name'] . '</td>
                                <td>' . $array['reviewer_status'] . '</td>' ?>
                            <?php if ($array['reviewer_status_updated_on'] == null) { ?>
                                <?php echo '<td></td>' ?>
                            <?php } else { ?>
                                <?php echo '<td>' . @date("d/m/Y g:i a", strtotime($array['reviewer_status_updated_on'])) . '</td>' ?>
                            <?php } ?>

                            <?php echo '<td>' . $array['reviewer_remarks'] . '</td>' ?>

                            <?php if ($role == 'Admin') { ?>

                                <?php echo '

                                <td>
                                <button type="button" href="javascript:void(0)" onclick="showDetails(\'' . $array['redeem_id'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="fa-regular fa-pen-to-square" style="font-size: 14px ;color:#777777" title="Show Details" display:inline;></i></button>&nbsp;&nbsp;
                                <form name="gemsdelete_' . $array['redeem_id'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="gemsdelete">
                                <input type="hidden" name="redeem_id" type="text" value="' . $array['redeem_id'] . '">
                                
                                <button type="submit" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['redeem_id'] . '"><i class="fa-solid fa-xmark"></i></button> </form>
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
                </div>
        </section>
    </section>

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
                <p id="status" class="label " style="display: inline !important;"><span class="redeem_id"></span></p>
            </div>

            <form id="reviewform" action="#" method="POST">
                <input type="hidden" class="form-control" name="form-type" type="text" value="gemsredeem" readonly>
                <input type="hidden" class="form-control" name="reviewer_id" id="reviewer_id" type="text" value="<?php echo $associatenumber ?>" readonly>
                <input type="hidden" class="form-control" name="reviewer_name" id="reviewer_name" type="text" value="<?php echo $fullname ?>" readonly>
                <input type="hidden" class="form-control" name="redeem_idd" id="redeem_idd" type="text" value="" readonly>

                <select name="reviewer_status" id="reviewer_status" class="form-control" style="display: -webkit-inline-box; width:20vh; font-size: small;" required>
                    <option value="" disabled selected hidden>Status</option>
                    <option value="Approved">Approved</option>
                    <option value="Under review">Under review</option>
                    <option value="Rejected">Rejected</option>
                </select>

                <span class="input-help">
                    <textarea type="text" name="reviewer_remarks" id="reviewer_remarks" class="form-control" placeholder="Reviewer remarks" value=""></textarea>
                    <small id="passwordHelpBlock" class="form-text text-muted">Reviewer remarks</small>
                </span>
                <br><br>
                <button type="submit" class="btn btn-danger btn-sm " style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none"><i class="fa-solid fa-arrows-rotate"></i>&nbsp;&nbsp;Update</button>
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
                status.classList.add("label-success")
                status.classList.remove("label-danger")
            } else {
                status.classList.remove("label-success")
                status.classList.add("label-danger")
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
        //For form submission - to update Remarks
        const scriptURL = 'payment-api.php'

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

        console.log(item)
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