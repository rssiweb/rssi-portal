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

date_default_timezone_set('Asia/Kolkata');

if ($_POST) {
    @$noticeid = $_POST['noticeid'];
    @$category = $_POST['category'];
    @$noticesub = $_POST['noticesub'];
    @$noticebody = $_POST['noticebody'];
    @$url = $_POST['url'];
    @$issuedby = $_POST['issuedby'];
    @$now = date('Y-m-d H:i:s');
    if ($noticeid != "") {
        $notice = "INSERT INTO notice (noticeid, date, subject, url, issuedby, category, noticebody) VALUES ('$noticeid','$now','$noticesub','$url','$issuedby','$category','$noticebody')";
        $result = pg_query($con, $notice);
        $cmdtuples = pg_affected_rows($result);
    }
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
    <title>RSSI-AMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
<link rel="stylesheet" href="/css/style.css" />

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
                <?php if (@$noticeid != null && @$cmdtuples == 0) { ?>

                    <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <span class="blink_me"><i class="glyphicon glyphicon-warning-sign"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                    </div>
                <?php
                } else if (@$cmdtuples == 1) { ?>

                    <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                        <i class="glyphicon glyphicon-ok" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Database has been updated successfully for notice id <?php echo @$noticeid ?>.</span>
                    </div>
                <?php } ?>

                <div class="row">
                    <section class="box" style="padding: 2%;">
                        <p>Home / AMS (Announcement Management System)</p><br><br>

                        <form autocomplete="off" name="ams" id="ams" action="ams.php" method="POST">
                            <div class="form-group" style="display: inline-block;">
                                <div class="col2" style="display: inline-block;">

                                    <span class="input-help">
                                        <input type="text" name="noticeid" class="form-control" style="width:max-content; display:inline-block" placeholder="Notice Id" required>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Notice ID</small>
                                    </span>

                                    <span class="input-help">
                                        <select name="category" class="form-control" style="width:max-content; display:inline-block" required>
                                            <?php if ($category == null) { ?>
                                                <option value="" disabled selected hidden>Category</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $category ?></option>
                                            <?php }
                                            ?>
                                            <option>Internal</option>
                                            <option>Public</option>
                                        </select>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Category</small>
                                    </span>

                                    <span class="input-help">
                                        <input type="text" name="noticesub" class="form-control" style="width:max-content; display:inline-block" placeholder="Notice Subject" value=""></input>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Subject</small>
                                    </span>

                                    <span class="input-help">
                                        <textarea type="text" name="noticebody" class="form-control" style="width:max-content; display:inline-block" placeholder="Notice Body" value=""></textarea>
                                        <small id="passwordHelpBlock" class="form-text text-muted">Details</small>
                                    </span>

                                    <span class="input-help">
                                        <input type="url" name="url" class="form-control" style="width:max-content; display:inline-block" placeholder="URL" value="">
                                        <small id="passwordHelpBlock" class="form-text text-muted">URL</small>
                                    </span>

                                    <input type="hidden" name="issuedby" class="form-control" style="width:max-content; display:inline-block" placeholder="Issued by" value="<?php echo $fullname ?>" required readonly>

                                </div>

                            </div>

                            <div class="col2 left" style="display: inline-block;">
                                <button type="submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                    <i class="fa-solid fa-plus"></i>&nbsp;&nbsp;Add</button>
                            </div>
                        </form>

                        <?php

                        $result = pg_query($con, "SELECT * FROM notice order by date desc");
                        if (!$result) {
                            echo "An error occurred.\n";
                            exit;
                        }

                        $resultArr = pg_fetch_all($result);
                        ?>
                        <?php echo '
                    <p>Select Number Of Rows</p>
                    <div class="form-group">
                        <!--		Show Numbers Of Rows 		-->
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
                            <th scope="col" width="10%">Notice Id</th>
                            <th scope="col" width="10%">Date</th>
                            <th scope="col" width="20%">Subject</th>
                            <th scope="col">Details</th>
                            <th scope="col" width="10%">Document</th>
                            </tr>
                        </thead>
                        <tbody>';
                        foreach ($resultArr as $array) {
                            echo '
                            <tr>
                                <td>' . $array['noticeid'] . '</td>
                                <td>' . @date("d/m/Y g:i a", strtotime($array['date'])) . '</td>
                                <td>' . $array['subject'] . '&nbsp;<p class="label label-default">' . $array['category'] . '</p></td>
                                <td>
                                
                                <form name="noticebody_' . $array['noticeid'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="noticebodyedit">
                                <input type="hidden" name="noticeid" id="noticeid" type="text" value="' . $array['noticeid'] . '">
                                <textarea id="inp_' . $array['noticeid'] . '" name="noticebody" type="text" disabled>' . $array['noticebody'] . '</textarea>' ?>

                            <?php if ($role == 'Admin') { ?>

                                <?php echo '&nbsp;

                                <button type="button" id="edit_' . $array['noticeid'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>&nbsp;

                                <button type="submit" id="save_' . $array['noticeid'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Save"><i class="fa-regular fa-floppy-disk"></i></button>
                                
                                ' ?>

                            <?php } ?>

                            <?php echo '</form></td>' ?>

                            <?php if ($array['url'] == null) { ?>
                                <?php echo '<td></td>' ?>

                            <?php } else { ?>

                                <?php echo '<td><a href="' . $array['url'] . '" target="_blank"><i class="fa-regular fa-file-pdf" style="font-size: 16px ;color:#777777" title="' . $array['noticeid'] . '" display:inline;></i></a></td>
                            </tr>'; ?>
                            <?php } ?>
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
                </div>
        </section>
    </section>
    <script>
        var data = <?php echo json_encode($resultArr) ?>;

        data.forEach(item => {

            const form = document.getElementById('edit_' + item.noticeid);

            form.addEventListener('click', function() {
                document.getElementById('inp_' + item.noticeid).disabled = false;
            });
        })

        //For form submission - to update Remarks
        const scriptURL = 'payment-api.php'

        data.forEach(item => {
            const form = document.forms['noticebody_' + item.noticeid]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['noticebody_' + item.noticeid])
                    })
                    .then(response => alert("Notice body has been updated.") +
                                location.reload())
                    .catch(error => console.error('Error!', error.message))
            })

            console.log(item)
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
