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

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>AMS</title>

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
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

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
</head>

<body>

    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>AMS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">AMS</li>
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
                            <?php if (@$noticeid != null && @$cmdtuples == 0) { ?>

                                <div class="alert alert-danger alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                    <span class="blink_me"><i class="bi bi-exclamation-triangle"></i></span>&nbsp;&nbsp;<span>ERROR: Oops, something wasn't right.</span>
                                </div>
                            <?php
                            } else if (@$cmdtuples == 1) { ?>

                                <div class="alert alert-success alert-dismissible" role="alert" style="text-align: -webkit-center;">
                                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                    <i class="bi bi-check2" style="font-size: medium;"></i></span>&nbsp;&nbsp;<span>Database has been updated successfully for notice id <?php echo @$noticeid ?>.</span>
                                </div>
                            <?php } ?>

                            <form autocomplete="off" name="ams" id="ams" action="ams.php" method="POST">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">

                                        <span class="input-help">
                                            <input type="text" name="noticeid" class="form-control" style="width:max-content; display:inline-block" placeholder="Notice Id" required>
                                            <small id="passwordHelpBlock" class="form-text text-muted">Notice ID</small>
                                        </span>

                                        <span class="input-help">
                                            <select name="category" class="form-select" style="width:max-content; display:inline-block" required>
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
                                        <i class="bi bi-plus-lg"></i>&nbsp;&nbsp;Add</button>
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
                                <td>' . $array['subject'] . '&nbsp;<p class="badge label-default">' . $array['category'] . '</p></td>
                                <td>
                                
                                <form name="noticebody_' . $array['noticeid'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="noticebodyedit">
                                <input type="hidden" name="noticeid" id="noticeid" type="text" value="' . $array['noticeid'] . '">
                                <textarea id="inp_' . $array['noticeid'] . '" name="noticebody" type="text" disabled>' . $array['noticebody'] . '</textarea>' ?>

                                <?php if ($role == 'Admin') { ?>

                                    <?php echo '&nbsp;

                                <button type="button" id="edit_' . $array['noticeid'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Edit"><i class="bi bi-box-arrow-up-right"></i></button>&nbsp;

                                <button type="submit" id="save_' . $array['noticeid'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Save"><i class="bi bi-save"></i></button>
                                
                                ' ?>

                                <?php } ?>

                                <?php echo '</form></td>' ?>

                                <?php if ($array['url'] == null) { ?>
                                    <?php echo '<td></td>' ?>

                                <?php } else { ?>

                                    <?php echo '<td><a href="' . $array['url'] . '" target="_blank"><i class="bi bi-file-earmark-pdf" style="font-size: 16px ;color:#777777" title="' . $array['noticeid'] . '" display:inline;></i></a></td>
                            </tr>'; ?>
                                <?php } ?>
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