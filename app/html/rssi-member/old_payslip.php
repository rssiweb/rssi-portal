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

    if ($id > 0 && $id != 'ALL') {
        $result = pg_query($con, "SELECT * FROM payslip WHERE associatenumber='$id' order by slno DESC");
        $totalamount = pg_query($con, "SELECT SUM(netpay) FROM payslip WHERE associatenumber='$id'");
    } else if ($id == 'ALL') {
        $result = pg_query($con, "SELECT * FROM payslip order by slno DESC");
        $totalamount = pg_query($con, "SELECT SUM(netpay) FROM payslip");
    } else {
        $result = pg_query($con, "SELECT * FROM payslip WHERE slno is null");
        $totalamount = pg_query($con, "SELECT SUM(netpay) FROM payslip WHERE slno is null");
    }
}


if ($role != 'Admin') {
    $result = pg_query($con, "select * from payslip where associatenumber='$associatenumber' ORDER BY slno DESC;");
    $totalamount = pg_query($con, "SELECT SUM(netpay) FROM payslip where associatenumber='$associatenumber'");
}
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
$resultArrr = pg_fetch_result($totalamount, 0, 0);
?>

<!DOCTYPE html>
<html>

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

    <title>Old Pay Details (Payslips till May 2023)</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Old Pay Details</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">My Services</a></li>
                    <li class="breadcrumb-item"><a href="document.php">My Document</a></li>
                    <li class="breadcrumb-item active">Old Pay Details</li>
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
                            <div class="alert alert-warning" role="alert">
                                <strong>Note:</strong> This portal contains payslips up to May 2023 only. For the latest payslips from June 2023 onward, please visit the <a href="pay_details.php" class="alert-link">Pay Details</a> portal.
                            </div>
                            <div class="row">
                                <div class="col">
                                    Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                                    <br>Total paid amount:&nbsp;<p class="badge bg-success"><?php echo ($resultArrr) ?></p>
                                </div>
                            </div>

                            <?php if ($role == 'Admin') { ?>
                                <form action="" method="POST">
                                    <div class="form-group" style="display: inline-block; margin-top:1%">
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
                            <?php } ?>
                            <?php echo '
                <div class="table-responsive">
                       <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Reference number</th>
                                <th scope="col">Issuance Date</th>
                                <th scope="col">Pay month</th>
                                <th scope="col">Days paid</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Transaction ID</th>
                                <th scope="col">Payslip</th>
                            </tr>
                        </thead>' ?>
                            <?php if (sizeof($resultArr) > 0) { ?>
                                <?php
                                echo '<tbody>';
                                foreach ($resultArr as $array) {
                                    echo '<tr>
                                <td>' . $array['payslipid'] . '</td>
                                <td>' . $array['date'] . '</td>
                                <td>' . $array['dateformat'] . '</td>
                                <td>' . $array['dayspaid'] . '</td>
                                <td>' . $array['netpay'] . '</td>
                                <td>' . $array['transaction_id'] . '</td>
                                <td><span class="noticea"><a href="' . $array['profile'] . '" target="_blank" title="' . $array['filename'] . '"><i class="bi bi-file-earmark-pdf" style="font-size:17px;color: #767676;"></i></a></span></td>
                                
                                </tr>';
                                } ?>
                            <?php
                            } else {
                            ?>
                                <tr>
                                    <td colspan="5">No record found or you are not eligible to withdraw salary from the organization.</td>
                                </tr>
                            <?php }

                            echo '</tbody>
                                    </table>
                                    </dv>';
                            ?>
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