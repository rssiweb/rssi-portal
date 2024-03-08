<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

if ($role == 'Admin') {

    @$id = strtoupper($_POST['get_aid']);

    if ($id > 0) {
        $result = pg_query($con, "select * from allocationdb_allocationdb WHERE associatenumber='$id'");
        $resultc = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$id'");
    } else {
        $result = pg_query($con, "select * from allocationdb_allocationdb WHERE associatenumber='$user_check'"); //select query for viewing users.
        $resultc = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$user_check'");
    }
}

if ($role != 'Admin') {

    $result = pg_query($con, "select * from allocationdb_allocationdb WHERE associatenumber='$user_check'"); //select query for viewing users.
    $resultc = pg_query($con, "select * from rssimyaccount_members WHERE associatenumber='$user_check'");
}
if (!$result) {
    echo "An error occurred.\n";
    exit;
}
if (!$resultc) {
    echo "An error occurred.\n";
    exit;
}


$resultArr = pg_fetch_all($result);
$resultArrc = pg_fetch_all($resultc);
?>

<!doctype html>
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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>My Allocation</title>

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
        /*
 CSS for the main interaction
*/
        .tabset>input[type="radio"] {
            position: absolute;
            left: -200vw;
        }

        .tabset .tab-panel {
            display: none;
        }

        .tabset>input:first-child:checked~.tab-panels>.tab-panel:first-child,
        .tabset>input:nth-child(3):checked~.tab-panels>.tab-panel:nth-child(2),
        .tabset>input:nth-child(5):checked~.tab-panels>.tab-panel:nth-child(3),
        .tabset>input:nth-child(7):checked~.tab-panels>.tab-panel:nth-child(4),
        .tabset>input:nth-child(9):checked~.tab-panels>.tab-panel:nth-child(5),
        .tabset>input:nth-child(11):checked~.tab-panels>.tab-panel:nth-child(6) {
            display: block;
        }

        .tabset>label {
            position: relative;
            display: inline-block;
            padding: 15px 15px 25px;
            border: 1px solid transparent;
            border-bottom: 0;
            cursor: pointer;
            font-weight: 600;
        }

        .tabset>label::after {
            content: "";
            position: absolute;
            left: 15px;
            bottom: 10px;
            width: 22px;
            height: 4px;
            background: #8d8d8d;
        }

        .tabset>label:hover,
        .tabset>input:focus+label {
            color: #06c;
        }

        .tabset>label:hover::after,
        .tabset>input:focus+label::after,
        .tabset>input:checked+label::after {
            background: #06c;
        }

        .tabset>input:checked+label {
            border-color: #ccc;
            border-bottom: 1px solid #fff;
            margin-bottom: -1px;
        }

        .tab-panel {
            padding: 30px 0;
            border-top: 1px solid #ccc;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>My Allocation</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">My Services</a></li>
                    <li class="breadcrumb-item active">My Allocation</li>
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
                            <?php if ($role == 'Admin') { ?>
                                <form action="" method="POST">
                                    <div class="form-group" style="display: inline-block;">
                                        <div class="col2" style="display: inline-block;">
                                            <?php if ($role == 'Admin') { ?>
                                                <input name="get_aid" class="form-control" style="width:max-content; display:inline-block" placeholder="Associate number" value="<?php echo $id ?>">
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="col2 left" style="display: inline-block;">
                                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                            <i class="bi bi-search"></i>&nbsp;Search</button>
                                    </div>
                                </form>
                                <br>
                            <?php } ?>

                            <div class="tabset">
                                <!-- Tab 1 -->
                                <input type="radio" name="tabset" id="tab1" aria-controls="marzen" checked>
                                <label for="tab1">Current Allocation</label>
                                <!-- Tab 2 -->
                                <input type="radio" name="tabset" id="tab2" aria-controls="rauchbier">
                                <label for="tab2">History Allocation</label>
                                <!-- Tab 3 -->
                                <input type="radio" name="tabset" id="tab3" aria-controls="dunkles">
                                <label for="tab3">Future Allocation</label>

                                <div class="tab-panels">
                                    <section id="marzen" class="tab-panel">
                                        <?php echo '
                                <div class="table-responsive">
                                <table class="table">
                        <thead>
                            <tr>
                            <th scope="col">Allocation Date</th>
                            <th scope="col">Max. Class<br>(Allocation start date to today)</th>
                            <th scope="col">Class Taken<br>(Inc. Extra class)</th>
                            <th scope="col">Off Class</th>
                            <th scope="col">Allocation Index</th>
                            </tr>
                        </thead>
                        <tbody>';
                                        foreach ($resultArrc as $array) {
                                            echo '
                            <tr>
                            <td style="line-height: 2;">' . $array['allocationdate'] . '</td>
                            <td style="line-height: 2;">' . $array['maxclass'] . '</td>
                            <td style="line-height: 2;">' . $array['classtaken'] . '</td>
                            <td style="line-height: 2;">' . $array['leave'] . '</td>
                            <td style="line-height: 2;">' ?>
                                            <?php if (@$array['allocationdate'] != null) {
                                                echo $array['ctp'] . '&nbsp;<meter id="disk_c" value="' . strtok($array['ctp'], '%') . '" min="0" max="100"></meter>' ?>
                                            <?php
                                            } else {
                                            }
                                            ?>
                                        <?php echo '</td>
                            </tr>';
                                        }
                                        echo '</tbody>
                                </table>
                                </div>'; ?>
                                    </section>
                                    <section id="rauchbier" class="tab-panel">
                                        <?php echo '
                                <div class="table-responsive">
                     <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Allocation Date</th>
                                <th scope="col">Max. Class<br>(Allocation start date to end date)</th>
                                <th scope="col">Class Taken<br>(Inc. Extra class)</th>
                            </tr>
                        </thead>
                        <tbody>';
                                        foreach ($resultArr as $array) {
                                            echo '
                            <tr>
                            <td style="line-height: 2;">' . $array['hallocationdate'] . '</td>
                            <td style="line-height: 2;">' . $array['hmaxclass'] . '</td>
                            <td style="line-height: 2;">' . $array['hclasstaken'] ?>
                                            <?php if ($array['hmaxclass'] != "Unallocated" && $array['hmaxclass'] != 0 && $array['hclasstaken'] != 0) { ?>
                                                <?php echo   '&nbsp;(' . number_format($array['hclasstaken'] / $array['hmaxclass'] * '100', '2', '.', '') . '%)' ?>
                                                <?php
                                            } else {
                                            }
                                                ?><?php echo '</td>
                            </tr>';
                                        }
                                        echo '</tbody>
                                </table>
                                </div>';
                                            ?>
                                    </section>
                                    <section id="dunkles" class="tab-panel">
                                        <p>No data available</p>
                                    </section>
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