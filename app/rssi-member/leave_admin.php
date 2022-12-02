<?php
session_start();
// Storing Session
include("../util/login_util.php");


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

@$id = $_POST['get_id'];
@$status = $_POST['get_status'];
@$statuse = $_POST['get_statuse'];
@$appid = $_POST['get_appid'];
@$is_user = $_POST['is_user'];

date_default_timezone_set('Asia/Kolkata');
// $date = date('Y-d-m h:i:s');

if (($id == null && $status == null && $statuse == null) && $appid == null) {
    $result = pg_query($con, "select * from leavedb_leavedb order by timestamp desc");
} else if ($id != null && $status !=null && $statuse != null) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE lyear='$id'AND status='$status' AND organizationalengagement='$statuse' order by timestamp desc");
} else if ($appid != null) {
    $result = pg_query($con, "select * from leavedb_leavedb WHERE associatenumber='$appid' order by timestamp desc");
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>My Leave</title>
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

</head>

<body>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col" style="display: inline-block; width:100%; text-align:right">
                        Home / Leave Tracker
                    </div>
                    <form action="" method="POST">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <select name="get_id" id="get_id" required class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type">
                                    <?php if ($id == null) { ?>
                                        <option value="" disabled selected hidden>Select Year</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $id ?></option>
                                    <?php }
                                    ?>
                                    <option>2022-2023</option>
                                    <option>2021-2022</option>
                                </select>&nbsp;
                                <select name="get_status" id="get_status" required class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type">
                                    <?php if ($status == null) { ?>
                                        <option value="" disabled selected hidden>Select Status</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $status ?></option>
                                    <?php }
                                    ?>
                                    <option>Approved</option>
                                    <option>Rejected</option>
                                </select>
                                <select name="get_statuse" id="get_statuse" required class="form-control" style="width:max-content; display:inline-block" placeholder="Appraisal type">
                                    <?php if ($status == null) { ?>
                                        <option value="" disabled selected hidden>Select Engagement</option>
                                    <?php
                                    } else { ?>
                                        <option hidden selected><?php echo $statuse ?></option>
                                    <?php }
                                    ?>
                                    <option>Volunteer</option>
                                    <option>Employee</option>
                                    <option>Intern</option>
                                    <option>Student</option>
                                </select>
                                <input name="get_appid" id="get_appid" required class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo $appid ?>">
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

                            document.getElementById("get_id").disabled = false;
                            document.getElementById("get_status").disabled = false;
                            document.getElementById("get_statuse").disabled = false;
                            document.getElementById("get_appid").disabled = true;

                        } else {

                            document.getElementById("get_id").disabled = true;
                            document.getElementById("get_status").disabled = true;
                            document.getElementById("get_statuse").disabled = true;
                            document.getElementById("get_appid").disabled = false;

                        }

                        const checkbox = document.getElementById('is_user');

                        checkbox.addEventListener('change', (event) => {
                            if (event.target.checked) {
                                document.getElementById("get_id").disabled = true;
                                document.getElementById("get_status").disabled = true;
                                document.getElementById("get_statuse").disabled = true;
                                document.getElementById("get_appid").disabled = false;
                            } else {
                                document.getElementById("get_id").disabled = false;
                                document.getElementById("get_status").disabled = false;
                                document.getElementById("get_statuse").disabled = false;
                                document.getElementById("get_appid").disabled = true;
                            }
                        })
                    </script>
                    <div class="col" style="display: inline-block; width:99%; text-align:right">
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
                    <table class="table" id="table-id">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Leave ID</th>
                                <th scope="col">Applicant ID/F name</th>
                                <th scope="col">Applied on</th>
                                <th scope="col">From</th>
                                <th scope="col">To</th>
                                <th scope="col">Day(s) count</th>
                                <th scope="col">Type of Leave/Leave Category</th>
                                <th scope="col">Status</th>
                                <th scope="col">HR remarks</th>
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
                            echo '  <td>' . $array['associatenumber'] . '/' . @strtok($array['applicantname'], ' ') . '</td>
                                <td>' . @date("d/m/Y g:i a", strtotime($array['timestamp'])) . '</td>
                                <td>' .  @date("d/m/Y", strtotime($array['from'])) . '</td>
                                <td>' .  @date("d/m/Y", strtotime($array['to'])) . '</td>
                                <td>' . $array['day'] . '</td>
                                <td>' . $array['typeofleave'] . '<br>
                                ' . $array['sreason'] . $array['creason'] . '</td>
                                <td>' . $array['status'] . '</td>
                                <td>' . $array['comment'] . '</td>
                                <td>
                                <form name="lms_' . $array['leaveid'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="lms">
                                <input type="hidden" name="lmsid" id="lmsid" type="text" value="' . $array['leaveid'] . '">
                                
                                <button type="submit" onclick=validateForm() style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Delete ' . $array['leaveid'] . '"><i class="fa-solid fa-xmark"></i></button> </form></td>
                            </tr>';
                        } ?>
                    <?php
                    } else if ($id == null && $status == null) {
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

                    <script>
                        var data = <?php echo json_encode($resultArr) ?>;
                        const scriptURL = 'payment-api.php'

                        function validateForm() {
                            if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {

                                data.forEach(item => {
                                    const form = document.forms['lms_' + item.leaveid]
                                    form.addEventListener('submit', e => {
                                        e.preventDefault()
                                        fetch(scriptURL, {
                                                method: 'POST',
                                                body: new FormData(document.forms['lms_' + item.leaveid])
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


                        // data.forEach(item => {
                        //     const formId = 'email-form-' + item.certificate_no
                        //     const form = document.forms[formId]
                        //     form.addEventListener('submit', e => {
                        //         e.preventDefault()
                        //         fetch('mailer.php', {
                        //                 method: 'POST',
                        //                 body: new FormData(document.forms[formId])
                        //             })
                        //             .then(response =>
                        //                 alert("Email has been sent.")
                        //             )
                        //             .catch(error => console.error('Error!', error.message))
                        //     })
                        // })
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