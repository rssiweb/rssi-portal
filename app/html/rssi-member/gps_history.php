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

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>GPS (Global Procurement System)</title>

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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }

        @media (max-width:767px) {

            #cw,
            #cw1 {
                width: 100% !important;
            }

        }

        #cw {
            width: 15%;
        }

        #cw1 {
            width: 20%;
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
            <h1>GPS History</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item"><a href="gps.php">GPS</a></li>
                    <li class="breadcrumb-item active">GPS History</li>
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
                            <div class="col" style="display: inline-block; width:100%; text-align:right">
                                Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                            </div>

                            <form action="" method="GET">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">
                                        <input name="assetid" id="assetid" class="form-control" style="width:max-content; display:inline-block" placeholder="Asset id" value="<?php echo $assetid ?>">
                                    </div>
                                </div>
                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                        <i class="bi bi-search"></i>&nbsp;Search</button>
                                </div>
                            </form><br>

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
                                <th scope="col">Asset Id</th>
                                <th scope="col" id="cw">Asset name</th>
                                <th scope="col">Quantity</th>' ?>
                            <?php if ($role == 'Admin') { ?>
                                <?php echo '
                                <th scope="col">Asset type</th>
                                <th scope="col" id="cw1">Remarks</th>' ?>
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
                                <td>' . $array['itemid'] . '</td><td>' ?>

                                    <?php if (@strlen($array['itemname']) <= 50) {

                                        echo $array['itemname'] ?>

                                    <?php } else { ?>

                                        <?php echo substr($array['itemname'], 0, 50) .
                                            '&nbsp;...&nbsp;<button type="button" href="javascript:void(0)" onclick="showname(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
<i class="bi bi-box-arrow-up-right"></i></button>' ?>


                                    <?php }
                                    echo '</td><td>' . $array['quantity'] . '</td>' ?>


                                    <?php if ($role == 'Admin') { ?>
                                        <?php echo '<td>' . $array['itemtype'] . '</td><td>' ?>
                                        <?php if (@strlen($array['remarks']) <= 90) {

                                            echo $array['remarks'] ?>

                                        <?php } else { ?>

                                            <?php echo substr($array['remarks'], 0, 90) .
                                                '&nbsp;...&nbsp;<button type="button" href="javascript:void(0)" onclick="showremarks(\'' . $array['itemid'] . '\')" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Details">
                                <i class="bi bi-box-arrow-up-right"></i></button>' ?>
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
                            } else if ($assetid == null) {
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

                            <!--------------- POP-UP BOX ------------
-------------------------------------->
                            <style>
                                .modal {
                                    background-color: rgba(0, 0, 0, 0.4);
                                    /* Black w/ opacity */
                                }
                            </style>

                            <div class="modal" id="myModal1" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Remarks</h1>
                                            <button type="button" id="closeremarks-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">

                                            <div style="width:100%; text-align:right">
                                                <p class="badge bg-info" style="display: inline !important;"><span class="itemid"></span></p>
                                            </div>

                                            <span class="remarks"></span>
                                            <div class="modal-footer">
                                                <button type="button" id="closeremarks-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                var data1 = <?php echo json_encode($resultArr) ?>

                                // Get the modal
                                var modal1 = document.getElementById("myModal1");
                                var closeremarks = [
                                    document.getElementById("closeremarks-header"),
                                    document.getElementById("closeremarks-footer")
                                ];

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
                                // When the user clicks on <span> (x), close the modal
                                closeremarks.forEach(function(element) {
                                    element.addEventListener("click", closeModal);
                                });

                                function closeModal() {
                                    var modal1 = document.getElementById("myModal1");
                                    modal1.style.display = "none";
                                }
                                // When the user clicks anywhere outside of the modal, close it
                                window.onclick = function(event) {
                                    // if (event.target == modal) {
                                    //     modal.style.display = "none";
                                    // } else 
                                    if (event.target == modal1) {
                                        modal1.style.display = "none";
                                    } else if (event.target == modal2) {
                                        modal2.style.display = "none";
                                    }
                                }
                            </script>

                            <div class="modal" id="myModal2" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Item name</h1>
                                            <button type="button" id="closename-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">

                                            <div style="width:100%; text-align:right">
                                                <p class="badge bg-info"><span class="itemid"></span></p>

                                            </div>
                                            <span class="itemname"></span>
                                            <div class="modal-footer">
                                                <button type="button" id="closename-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script>
                                var data2 = <?php echo json_encode($resultArr) ?>

                                // Get the modal
                                var modal2 = document.getElementById("myModal2");
                                var closename = [
                                    document.getElementById("closename-header"),
                                    document.getElementById("closename-footer")
                                ];

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
                                // When the user clicks on <span> (x), close the modal
                                closename.forEach(function(element) {
                                    element.addEventListener("click", closeModal);
                                });

                                function closeModal() {
                                    var modal1 = document.getElementById("myModal2");
                                    modal1.style.display = "none";
                                }
                                // When the user clicks anywhere outside of the modal, close it SEE OTHER SCRIPT
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