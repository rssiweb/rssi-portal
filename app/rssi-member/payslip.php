<?php
session_start();
// Storing Session
include("../util/login_util.php");

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
    $result = pg_query($con, "select * from payslip where associatenumber='$user_check' ORDER BY slno DESC;");
    $totalamount = pg_query($con, "SELECT SUM(netpay) FROM payslip where associatenumber='$user_check'");
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
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Payslip</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Main css -->
    <style>
        <?php include '../css/style.css'; ?>
    </style>
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

</head>

<body>
    <?php include 'header.php'; ?>

    <section id="main-content">
        <section class="wrapper main-wrapper row">
            <div class="col-md-12">
                <div class="row">
                    <!-- <div class="col" style="display: inline-block; width:99%; text-align:right">
                        <p style="font-size:small"><span class="noticea" style="line-height: 2;"><a href="document.php">My Document</a></span> / Payslip</p>
                    </div>

                    <div class="col" style="display: inline-block; width:99%; text-align:right">
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                    </div> -->
                    <div class="col" style="display: inline-block; width:50%;margin-left:1.5%; font-size:small">
                        Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                        <br>Total paid amount:&nbsp;<p class="label label-success"><?php echo ($resultArrr) ?></p>
                    </div>
                </div><br>
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
                                <i class="fa-solid fa-magnifying-glass"></i>&nbsp;Search</button>
                        </div>
                    </form>
                <?php } ?>
                <?php echo '
                       <table class="table">
                        <thead style="font-size: 12px;">
                            <tr>
                                <th scope="col">Reference number</th>
                                <th scope="col">Date</th>
                                <th scope="col">Class count</th>
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
                                <td>' . $array['classcount'] . '</td>
                                <td>' . $array['netpay'] . '</td>
                                <td>' . $array['transaction_id'] . '</td>
                                <td><span class="noticea"><a href="' . $array['profile'] . '" target="_blank" title="' . $array['filename'] . '"><i class="far fa-file-pdf" style="font-size:17px;color: #767676;"></i></a></span></td>
                                
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
                                    </table>';
                ?>
            </div>
        </section>
    </section>
</body>

</html>