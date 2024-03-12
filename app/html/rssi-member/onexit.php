<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];

    header("Location: index.php");
    exit;
}
validation();

$id = isset($_GET['get_aid']) ? strtoupper($_GET['get_aid']) : null;

// Fetch data for both onboarding and exit
$result = pg_query($con, "SELECT
    onboarding_associate_id AS associate_id,
    fullname AS associate_name,
    onboard_initiated_on AS onboarding_initiated_on,
    onboarding_submitted_on AS onboarding_submitted_on,
    exit_initiated_on AS exit_initiated_on,
    exit_submitted_on AS exit_submitted_on
    FROM onboarding
    LEFT JOIN associate_exit ON onboarding_associate_id = exit_associate_id
    LEFT JOIN rssimyaccount_members ON onboarding_associate_id = rssimyaccount_members.associatenumber
    ORDER BY onboarding_submitted_on DESC");

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);

if ($id != null) {
    // If $id is not null, filter the results for the specific associate
    $filteredResultArr = array_filter($resultArr, function ($row) use ($id) {
        return $row['associate_id'] == $id;
    });

    $resultArr = $filteredResultArr;
}

?>

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

    <title>OnExit</title>

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
<?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>OnExit</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">OnExit</li>
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
                            <form action="" method="GET" class="row g-2 align-items-center">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col-2" style="display: inline-block;">
                                        <label for="get_aid" class="form-label">Enter Associate Number:</label>
                                        <input type="text" name="get_aid" id="get_aid" class="form-control" placeholder="Associate Number" value="<?php echo isset($_GET['get_aid']) ? htmlspecialchars($_GET['get_aid']) : ''; ?>">
                                    </div>
                                    <div class="col-2" style="display: inline-block;">
                                        <button type="submit" name="search_by_id" class="btn btn-success" style="outline: none;">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <div style="display: inline-block; width:100%; text-align:right;">Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                            </div>
                            <!-- HTML Table -->
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
                                            <th scope="col">Associate Number</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Onboarded On</th>
                                            <th scope="col">Exited On</th>
                                            <th scope="col">View Form</th>
                                        </tr>
                                    </thead>
                                    <?php if ($resultArr != null) {
                                        echo '<tbody>';
                                        foreach ($resultArr as $array) {
                                            echo '<tr>';
                                            echo '<td>' . $array['associate_id'] . '</td>';
                                            echo '<td>' . $array['associate_name'] . '</td>';
                                            echo '<td>' . ($array['onboarding_initiated_on'] ? date('d/m/Y h:i:s a', strtotime($array['onboarding_initiated_on'])) : 'Not Onboarded') . '</td>';
                                            echo '<td>' . ($array['exit_initiated_on'] ? date('d/m/Y h:i:s a', strtotime($array['exit_initiated_on'])) : 'Not Exited') . '</td>';
                                            echo '<td>';
                                            if ($array['onboarding_initiated_on']) {
                                                echo '<a href="onboarding.php?associate-number=' . $array['associate_id'] . '" target="_blank">View Onboarding Form</a>';
                                            }
                                            if ($array['exit_initiated_on']) {
                                                if ($array['onboarding_initiated_on']) {
                                                    echo ' | ';
                                                }
                                                echo '<a href="exit.php?associate-number=' . $array['associate_id'] . '" target="_blank">View Exit Form</a>';
                                            }
                                            echo '</td>';

                                            echo '</tr>';
                                        }
                                        echo '</tbody>';
                                    } else {
                                        echo '<tbody><tr><td colspan="5">Associate with ID ' . $id . ' not found.</td></tr></tbody>';
                                    }
                                    ?>
                                </table>

                            </div>
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