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
    LEFT JOIN (SELECT associatenumber,fullname,email FROM rssimyaccount_members) reviewer ON reviewer.associatenumber = appraisee_response.reviewer_associatenumber
    WHERE appraisalyear='$id' ORDER BY (ipf IS NULL) DESC, goalsheet_created_on DESC");
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


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Appraisal Workflow</title>

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
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>

    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Appraisal Workflow</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Performance management</a></li>
                    <li class="breadcrumb-item"><a href="my_appraisal.php">My Appraisal</a></li>
                    <li class="breadcrumb-item active">Appraisal Workflow</li>
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

                            <form action="" method="GET">
                                <div class="form-group">
                                    <div class="col2" style="display: inline-block;">

                                        <select name="get_aid" id="get_aid" class="form-select" style="width:max-content; display:inline-block" placeholder="Select policy year" required>
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
                                            <i class="bi bi-search"></i>&nbsp;Search</button>
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
                                        echo '<span class="badge bg-danger text-start">Self-assessment</span>';
                                    } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "") {
                                        echo '<span class="badge bg-warning text-start">Manager assessment in progress</span>';
                                    } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "") {
                                        echo '<span class="badge bg-primary text-start">Reviewer assessment in progress</span>';
                                    } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "yes" && $array['ipf_response'] == null) {
                                        echo '<span class="badge bg-success text-start">IPF released</span>';
                                    } else if ($array['ipf_response'] == 'accepted') {
                                        echo '<span class="badge bg-success text-start">IPF Accepted</span>';
                                    } else if ($array['ipf_response'] == 'rejected') {
                                        echo '<span class="badge bg-danger text-start">IPF Rejected</span>';
                                    } ?><?php '</td>' ?>

                            <td><?php echo ($array['ipf_response_on'] == null) ? "" : date('d/m/y h:i:s a', strtotime($array['ipf_response_on'])); ?></td>


                            <?php echo '<td>

                        <form name="ipfclose' . $array['goalsheetid'] . '" action="#" method="POST">
                        <input type="hidden" name="form-type" type="text" value="ipfclose">
                        <input type="hidden" name="ipfid" id="ipfid" type="text" value="' . $array['goalsheetid'] . '">
                        <input type="hidden" name="ipf_process_closed_by" id="ipf_process_closed_by" type="text" value="' . $associatenumber . '">' ?>

                            <?php if ($array['ipf_process_closed_by'] == null && $role == 'Admin') { ?>

                                <?php echo '<button type="submit" id="yes" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none;
                        padding: 0px;
                        border: none;" title="Closed"><i class="bi bi-box-arrow-up"></i></button>' ?>
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