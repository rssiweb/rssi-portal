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
} ?>

<?php if ($role == 'Admin') {

    @$assetid = $_GET['assetid'];

    if ($assetid != null) {
        $result = pg_query($con, "SELECT * FROM gps_history where itemid='$assetid' order by date desc");
    } else {
        $result = pg_query($con, "SELECT * FROM gps_history order by date desc");
    }



    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);
} ?>
<?php if ($role != 'Admin') {

    @$assetid = $_GET['assetid'];

    if ($assetid != null) {
        $result = pg_query($con, "SELECT * FROM gps_history where itemid='$assetid' AND taggedto='$associatenumber' order by date desc");
    } else {
        $result = pg_query($con, "SELECT * FROM gps_history where taggedto='$associatenumber' order by date desc");
    }



    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);
} ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>GPS History</title>
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
            policyLink: 'https://drive.google.com/file/d/1o-ULIIYDLv5ipSRfUa6ROzxJZyoEZhDF/view'
        });
    </script>
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
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
                <div class="col" style="display: inline-block; width:50%;margin-left:1.5%">
                    Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                </div>
                <div class="col" style="display: inline-block; width:47%; text-align:right">
                    Home / <span class="noticea"><a href="gps.php">GPS Portal</a></span> / GPS History
                </div>
                <section class="box" style="padding: 2%;">

                    <form action="" method="GET">
                        <div class="form-group" style="display: inline-block;">
                            <div class="col2" style="display: inline-block;">
                                <input name="assetid" id="assetid" class="form-control" style="width:max-content; display:inline-block" placeholder="Asset id" value="<?php echo $assetid ?>">
                            </div>
                        </div>
                        <div class="col2 left" style="display: inline-block;">
                            <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                        </div>
                    </form>

                    <?php echo '
                    <div class="container">
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
                                <th scope="col">Asset Id</th>
                                <th scope="col" width="15%">Asset name</th>
                                <th scope="col">Quantity</th>' ?>
                    <?php if ($role == 'Admin') { ?>
                        <?php echo '
                                <th scope="col">Asset type</th>
                                <th scope="col" width="20%">Remarks</th>' ?>
                    <?php }
                    echo '
                                <th scope="col">Issued by</th>
                                <th scope="col">Tagged to</th>
                                <th scope="col">Last updated on</th></tr>
                        </thead>' ?>
                    <?php if (sizeof($resultArr) > 0) { ?>
                        <?php
                        echo '<tbody>';
                        foreach ($resultArr as $array) {
                            echo '<tr>
                                <td>
                                <span class="noticeas"><a href="gps_history.php?assetid=' . $array['itemid'] . '" target="_blank" title="Asset History">' . $array['itemid'] . '</a></span>
                                </td><td>' ?>

                            <?php if (@strlen($array['itemname']) <= 50) {

                                echo $array['itemname'] ?>

                            <?php } else { ?>

                                <?php echo substr($array['itemname'], 0, 50) .
                                    '&nbsp;...&nbsp;<button type="button" href="javascript:void(0)" onclick="showname(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i></button>' ?>

                            <?php }
                            echo '</td><td>' . $array['quantity'] . '</td>' ?>


                            <?php if ($role == 'Admin') { ?>
                                <?php echo '<td>' . $array['itemtype'] . '</td><td>' ?>
                                <?php if (@strlen($array['remarks']) <= 90) {

                                    echo $array['remarks'] ?>

                                <?php } else { ?>

                                    <?php echo substr($array['remarks'], 0, 90) .
                                        '&nbsp;...&nbsp;<button type="button" href="javascript:void(0)" onclick="showremarks(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i></button>' ?>
                                <?php }
                                echo '</td>' ?>
                            <?php } ?>

                            <?php echo '
                                <td>' . $array['collectedby'] . '</td>
                                <td>' . $array['taggedto'] . '</td>
                                <td>' ?>
                            <?php if ($array['date'] != null) { ?>

                                <?php echo @date("d/m/Y g:i a", strtotime($array['date'])) ?>

                            <?php } else {
                            } ?>




                        <?php echo '</tr>';
                        } ?>
                    <?php
                    } else if ($taggedto == null && $item_type == null) {
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
            </div>
            </div>
            </div>
        </section>
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

    <div id="myModal1" class="modal">

        <!-- Modal content -->
        <div class="modal-content">
            <span id="closeremarks" class="close">&times;</span>

            <div style="width:100%; text-align:right">
                <p class="label label-info" style="display: inline !important;"><span class="itemid"></span></p>
            </div>

            <span class="remarks"></span>
        </div>

    </div>

    <script>
        var data1 = <?php echo json_encode($resultArr) ?>

        // Get the modal
        var modal1 = document.getElementById("myModal1");
        var closeremarks = document.getElementById("closeremarks");

        function showremarks(id1) {
            var mydata1 = undefined
            data1.forEach(item1 => {
                if (item1["itemid"] == id1) {
                    mydata1 = item1;
                }
            })
            var keys1 = Object.keys(mydata1)
            keys1.forEach(key => {
                var span1 = modal1.getElementsByClassName(key)
                if (span1.length > 0)
                    span1[0].innerHTML = mydata1[key];
            })
            modal1.style.display = "block";

        }
        closeremarks.onclick = function() {
            modal1.style.display = "none";
        }
        // When the user clicks anywhere outside of the modal, close it SEE OTHER SCRIPT
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal1) {
                modal1.style.display = "none";
            } else if (event.target == modal2) {
                modal2.style.display = "none";
            }
        }
    </script>

    <div id="myModal2" class="modal">

        <!-- Modal content -->
        <div class="modal-content">
            <span id="closename" class="close">&times;</span>

            <div style="width:100%; text-align:right">
                <p class="label label-info" style="display: inline !important;"><span class="itemid"></span></p>
            </div>

            <span class="itemname"></span>
        </div>

    </div>

    <script>
        var data2 = <?php echo json_encode($resultArr) ?>

        // Get the modal
        var modal2 = document.getElementById("myModal2");
        var closeremarks = document.getElementById("closename");

        function showname(id2) {
            var mydata2 = undefined
            data2.forEach(item2 => {
                if (item2["itemid"] == id2) {
                    mydata2 = item2;
                }
            })
            var keys2 = Object.keys(mydata2)
            keys2.forEach(key => {
                var span2 = modal2.getElementsByClassName(key)
                if (span2.length > 0)
                    span2[0].innerHTML = mydata2[key];
            })
            modal2.style.display = "block";

        }
        closename.onclick = function() {
            modal2.style.display = "none";
        }
        // When the user clicks anywhere outside of the modal, close it SEE OTHER SCRIPT
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
