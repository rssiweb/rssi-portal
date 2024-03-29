<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");


if (!isLoggedIn("sid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

// if ($role != 'Admin' && $role != 'Offline Manager') {
//     echo '<script type="text/javascript">';
//     echo 'alert("Access Denied. You are not authorized to access this web page.");';
//     echo 'window.location.href = "home.php";';
//     echo '</script>';
// }

date_default_timezone_set('Asia/Kolkata');

$result = pg_query($con, "SELECT * FROM certificate where awarded_to_id='$student_id' order by issuedon desc");
$totalgems = pg_query($con, "SELECT SUM(gems) FROM certificate where awarded_to_id='$student_id'");

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
$resultArrr = pg_fetch_result($totalgems, 0, 0);

?>

<!DOCTYPE html>
<html lang="en">

<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>RSSI-Certificate management</title>
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
            policyLink: 'https://www.rssi.in/disclaimer'
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
                <?php if ($resultArrr != null) { ?>
                    <div style="display: inline-block; width:100%; font-size:small; text-align:right;"><i class="fa-regular fa-gem" style="font-size:medium;" title="RSSI Gems"></i>&nbsp;<p class="badge label-success"><?php echo $resultArrr ?></p>
                    </div>
                <?php } ?>
                <?php if ($resultArrr == null) { ?>
                    <div style="display: inline-block; width:100%; font-size:small; text-align:right;"><i class="fa-regular fa-gem" style="font-size:medium;" title="RSSI Gems"></i>&nbsp;<p class="badge label-default">You're almost there</p>
                    </div>
                <?php } ?>
                <div class="row">
                    
                        <!-- <p>Home / Certificate Management System</p><br> -->
                        <div class="col" style="display: inline-block; width:100%; text-align:right">
                            Home / <span class="noticea"><a href="document.php">My Document</a></span> / My Certificate<br><br>
                        </div>

                        <div style="display: inline-block; width:100%; font-size:small; text-align:right;">Record count:&nbsp;<?php echo sizeof($resultArr) ?></div>

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
                    <table class="table" id="table-id">
                        <thead>
                            <tr>
                            <th scope="col">Certificate no</th>
                            <th scope="col">Badge name</th>
                            <th scope="col">Remarks</th>
                            <th scope="col">Gems</th>
                            <th scope="col">Issued on</th>
                            <th scope="col">Issued by</th>
                            <th scope="col">Certificate</th>
                            </tr>
                            </thead>' ?>
                        <?php if ($resultArr != null) {
                            echo '<tbody>';
                            foreach ($resultArr as $array) {
                                echo '
                            <tr>
                                <td>' . $array['certificate_no'] . '</td>
                                <td>' . $array['badge_name'] . '</td>
                                <td>' . $array['comment'] . '</td>
                                <td>' . $array['gems'] . '</td>' ?>
                                <?php if ($array['issuedon'] == null) { ?>
                                    <?php echo '<td></td>' ?>
                                <?php } else { ?>
                                    <?php echo '<td>' . @date("d/m/Y g:i a", strtotime($array['issuedon'])) . '</td>' ?>
                                <?php } ?>

                                <?php echo '<td>' . $array['issuedby'] . '</td>' ?>

                                <?php if ($array['certificate_url'] == null) { ?>
                                    <?php echo '<td></td>' ?>

                                <?php } else { ?>

                                    <?php echo '<td><a href="' . $array['certificate_url'] . '" target="_blank"><i class="bi bi-file-earmark-pdf" style="font-size: 16px ;color:#777777" title="' . $array['certificate_no'] . '" display:inline;></i></a></td>' ?>
                                <?php } ?>


                            <?php }
                            echo '</tr>' ?>
                        <?php
                        } else if (sizeof($resultArr) == 0) { ?>
                            <?php echo '<tr>
                                    <td colspan="5">No record found for ' ?><?php echo $student_id ?><?php echo '.</td>
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
        var data = <?php echo json_encode($resultArr) ?>;
        //For form submission - to update Remarks
        const scriptURL = 'payment-api.php'

        data.forEach(item => {
            const form = document.forms['cmsdelete_' + item.certificate_no]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['cmsdelete_' + item.certificate_no])
                    })
                    .then(response =>
                        alert("Record has been deleted.") +
                        location.reload()
                    )
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
