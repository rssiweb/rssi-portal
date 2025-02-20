<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();


@$status = $_POST['get_status'];

if ($role == 'Admin') {
    @$id = $_POST['get_bid'];

    if ($id == null && $status == 'ALL') {
        $result = pg_query($con, "SELECT * FROM bookdata_book");
    }
    if ($id == null && $status != 'ALL') {
        $result = pg_query($con, "SELECT * FROM bookdata_book WHERE bookstatus='$status'");
    }
    if ($id > 0 && $status != 'ALL') {
        $result = pg_query($con, "SELECT * FROM bookdata_book WHERE yourid='$id' AND bookstatus='$status'");
    }
    if ($id > 0 && $status == 'ALL') {
        $result = pg_query($con, "SELECT * FROM bookdata_book WHERE yourid='$id'");
    }
    if ($id == null && $status == null) {
        $result = pg_query($con, "SELECT * FROM bookdata_book WHERE orderid=''");
    }
}

if ($role != 'Admin') {
    if ($status == null) {
        $result = pg_query($con, "SELECT * FROM bookdata_book WHERE yourid='$user_check' order by timestamp desc");
    } else if ($status == 'ALL') {
        $result = pg_query($con, "SELECT * FROM bookdata_book WHERE yourid='$user_check' order by timestamp desc");
    } else if ($status > 0 && $status != 'ALL') {
        $result = pg_query($con, "SELECT * FROM bookdata_book WHERE yourid='$user_check' AND bookstatus='$status' order by timestamp desc");
    } else {
        $result = pg_query($con, "SELECT * FROM bookdata_book WHERE yourid='$user_check' order by timestamp desc");
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
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-11316670180');
</script>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>My Book</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

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

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
<?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>My Book</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Learning & Collaboration</a></li>
                    <li class="breadcrumb-item"><a href="library.php">Libary</a></li>
                    <li class="breadcrumb-item active">My Book</li>
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
                            <div class="row">
                                <div class="col">
                                    Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                                </div>
                            </div>
                            

                                <form action="" method="POST">
                                    <div class="form-group" style="display: inline-block;">
                                        <div class="col2" style="display: inline-block;">
                                            <?php if ($role == 'Admin') { ?>
                                                <input name="get_bid" class="form-control" style="width:max-content; display:inline-block" placeholder="Borrowers ID" value="<?php echo $id ?>">
                                            <?php } ?>
                                            <select name="get_status" class="form-select" style="width:max-content;display:inline-block" required>
                                                <?php if ($status == null) { ?>
                                                    <option hidden selected>Select book status</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $status ?></option>
                                                <?php }
                                                ?>
                                                <option>Issued</option>
                                                <option>Due</option>
                                                <option>Returned</option>
                                                <option>Canceled</option>
                                                <option>Duplicate-Canceled</option>
                                                <option>ALL</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col2 left" style="display: inline-block;">
                                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                            <i class="bi bi-search"></i>&nbsp;Search</button>
                                    </div>
                                </form>
                                <?php echo '
                                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Borrowers ID/F Name</th>
                                <th scope="col">Order ID</th>
                                <th scope="col">Order Date</th>
                                <th scope="col">Book ID</th>
                                <th scope="col">Book Name</th>
                                <th scope="col">Original Price</th>
                                <th scope="col">Issued On</th>
                                <th scope="col">Due Date</th>
                                <th scope="col">Book status</th>
                                <th scope="col">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>';
                                foreach ($resultArr as $array) {
                                    echo '
                            <tr>
                                <td>' . $array['yourid'] . '/' . strtok($array['yourname'], ' ') . '</td>
                                <td>' . $array['orderid'] . '</td>
                                <td>' . $array['orderdate'] . '</td>
                                <td>' . $array['bookregno'] . '</td>
                                <td>' . $array['bookname'] . '</td>
                                <td>' . $array['originalprice'] . '</td>
                                <td>' . $array['issuedon'] . '</td>
                                <td>' . $array['duedate'] . '</td>
                                <td>' . $array['bookstatus'] . '</td>
                                <td>' . $array['remarks'] . '</td>
                            </tr>';
                                } ?>
                                <?php
                                if ($status == null && sizeof($resultArr) == 0) {
                                ?>
                                    <tr>
                                        <td colspan="5">Please select Filter value.</td>
                                    </tr>
                                <?php
                                }
                                if ($status != null && sizeof($resultArr) == 0) {
                                ?>
                                    <tr>
                                        <td colspan="5">No record was found for the selected filter value.</td>
                                    </tr>
                                <?php }

                                echo '</tbody>
                        </table>
                        </div>';
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