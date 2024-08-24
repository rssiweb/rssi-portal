<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

date_default_timezone_set('Asia/Kolkata');
include("../../util/email.php");

if ($role == 'Admin') {
    $id = isset($_GET['get_status']) ? $_GET['get_status'] : 'Active';
    @$user_id = strtoupper($_GET['user_id']);

    if ($user_id > 0) {
        $result = pg_query($con, "SELECT bd.*, rm.fullname
            FROM bankdetails bd
            JOIN rssimyaccount_members rm ON bd.updated_for = rm.associatenumber
            WHERE bd.updated_for='$user_id' AND rm.filterstatus='$id'
            AND bd.updated_on = (
                SELECT MAX(updated_on)
                FROM bankdetails
                WHERE updated_for = bd.updated_for AND account_nature = bd.account_nature
            )");
    } else {
        $result = pg_query($con, "SELECT bd.*, rm.fullname
            FROM bankdetails bd
            JOIN rssimyaccount_members rm ON bd.updated_for = rm.associatenumber
            WHERE rm.filterstatus='$id'
            AND bd.updated_on = (
                SELECT MAX(updated_on)
                FROM bankdetails
                WHERE updated_for = bd.updated_for AND account_nature = bd.account_nature
            )"); // Select query for viewing users.
    }
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}
$resultArr = pg_fetch_all($result);
?>

<!DOCTYPE html>
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
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>HR Banking Records</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

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

    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

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
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>HR Banking Records</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">HR Banking Records</li>
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
                            <div style="display: inline-block; width:100%; text-align:right;">Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                            </div>
                            <?php if ($role == 'Admin' && $filterstatus == 'Active') { ?>

                                <form action="" method="GET" style="display: flex; align-items: center;">
                                    <div class="form-group" style="margin-right: 10px;">
                                        <select name="get_status" id="get_status" class="form-select">
                                            <?php if ($id == null) { ?>
                                                <option value="" disabled selected hidden>Select Status</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $id ?></option>
                                            <?php }
                                            ?>
                                            <option>Active</option>
                                            <option>Inactive</option>
                                        </select>
                                        <!-- <small class="form-text text-muted">Select Status</small> -->
                                    </div>
                                    <div class="form-group" style="margin-right: 10px;">
                                        <input name="user_id" id="user_id" class="form-control" style="width: auto;" placeholder="User id" value="<?php echo $user_id ?>">
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                            <i class="bi bi-search"></i>&nbsp;Search
                                        </button>
                                    </div>
                                </form>

                            <?php } ?>
                            <br>
                            <div class="table-responsive">
                                <table class="table table-hover" id="table-id">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Associate number</th>
                                            <th>Name</th>
                                            <th>Account Nature</th>
                                            <th>Bank Name</th>
                                            <th>A/c no</th>
                                            <th>IFSC Code</th>
                                            <th>Passbook</th>
                                            <th>Updated on</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($resultArr != null) : ?>
                                            <?php $serialNumber = 1; // Initialize serial number 
                                            ?>
                                            <?php foreach ($resultArr as $array) : ?>
                                                <tr>
                                                    <td><?= $serialNumber++ ?></td> <!-- Display and increment serial number -->
                                                    <td><?= $array['updated_for'] ?></td>
                                                    <td><?= $array['fullname'] ?></td>
                                                    <td><?= $array['account_nature'] ?></td>
                                                    <td><?= $array['bank_name'] ?></td>
                                                    <td><?= $array['bank_account_number'] ?></td>
                                                    <td><?= $array['ifsc_code'] ?></td>
                                                    <td><a href="<?= $array['passbook_page'] ?>" target="_blank">Passbook</a></td>
                                                    <td><?= $array['updated_on'] ? date("d/m/Y g:i a", strtotime($array['updated_on'])) : '' ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php elseif (@$user_id == "") : ?>
                                            <tr>
                                                <td colspan="7">Please select Filter value.</td>
                                            </tr>
                                        <?php elseif (sizeof($resultArr) == 0 || (@$user_id != "")) : ?>
                                            <tr>
                                                <td colspan="7">No record found for <?= $user_id ?>.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
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
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>