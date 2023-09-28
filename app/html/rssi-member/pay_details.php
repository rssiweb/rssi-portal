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

if ($role == 'Admin') {

    @$id = strtoupper($_POST['get_aid']);

    if ($id > 0 && $id != 'ALL') {
        $result = pg_query($con, "SELECT * FROM payslip_entry 
        left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members) associate ON payslip_entry.employeeid=associate.associatenumber
        WHERE employeeid='$id' order by payslip_issued_on DESC");
    } else if ($id == 'ALL') {
        $result = pg_query($con, "SELECT * FROM payslip_entry
        left join (SELECT associatenumber,fullname,email, phone FROM rssimyaccount_members) associate ON payslip_entry.employeeid=associate.associatenumber
        order by payslip_issued_on DESC");
    } else {
        $result = pg_query($con, "SELECT * FROM payslip_entry WHERE payslip_entry_id is null");
    }
}


if ($role != 'Admin') {
    $result = pg_query($con, "SELECT * FROM payslip_entry WHERE employeeid = '$user_check' ORDER BY payslip_issued_on DESC");
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

// Initialize total net pay variable
$totalNetPay = 0;

// Loop through the payslip entries
while ($row = pg_fetch_assoc($result)) {
    $payslipEntryID = $row['payslip_entry_id'];

    // Query to get the total earnings for each payslip entry
    $earningsQuery = pg_query($con, "SELECT SUM(amount) FROM payslip_component WHERE payslip_entry_id = '$payslipEntryID' AND components = 'Earning'") or die(pg_last_error());
    $earningsResult = pg_fetch_assoc($earningsQuery);
    $earnings = $earningsResult['sum'];

    // Query to get the total deductions for each payslip entry
    $deductionsQuery = pg_query($con, "SELECT SUM(amount) FROM payslip_component WHERE payslip_entry_id = '$payslipEntryID' AND components = 'Deduction'") or die(pg_last_error());
    $deductionsResult = pg_fetch_assoc($deductionsQuery);
    $deductions = $deductionsResult['sum'];

    // Calculate the net pay
    $netPay = $earnings - $deductions;

    // Add the net pay to the total net pay
    $totalNetPay += $netPay;
}

$resultArr = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html>

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

    <title>Pay Details</title>

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
</head>

<body>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Pay Details</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">My Services</a></li>
                    <li class="breadcrumb-item"><a href="document.php">My Document</a></li>
                    <li class="breadcrumb-item active">Pay Details</li>
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
                                    <br>Total paid amount:&nbsp;<p class="badge bg-success"><?php echo ($totalNetPay) ?></p>
                                </div>
                                <div class="col text-end">
                                    <a href="old_payslip.php">Payslip till May 23 >></a>
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
                                <th scope="col">Reference number</th>' ?>
                            <?php if ($role == 'Admin') { ?>
                                <th scope="col">ID/F Name</th>
                            <?php } ?>
                            <?php echo '<th scope="col">Issuance Date</th>
                                <th scope="col">Pay month</th>
                                <th scope="col">Days paid</th>
                                <th scope="col">Netpay</th>
                                <th scope="col">HR comments</th>
                                <th scope="col">Payslip</th>
                            </tr>
                        </thead>' ?>
                            <?php if (sizeof($resultArr) > 0) { ?>
                                <?php
                                echo '<tbody>';
                                foreach ($resultArr as $array) {
                                    $payslip_entry_id = $array['payslip_entry_id'];

                                    // Get total earning for the payslip entry
                                    $result_component_earning_total = pg_query($con, "SELECT SUM(amount) FROM payslip_component WHERE payslip_entry_id = '$payslip_entry_id' AND components = 'Earning'");
                                    $total_earning = pg_fetch_result($result_component_earning_total, 0, 0);

                                    // Get total deduction for the payslip entry
                                    $result_component_deduction_total = pg_query($con, "SELECT SUM(amount) FROM payslip_component WHERE payslip_entry_id = '$payslip_entry_id' AND components = 'Deduction'");
                                    $total_deduction = pg_fetch_result($result_component_deduction_total, 0, 0);

                                    // Calculate net pay
                                    $net_pay = $total_earning - $total_deduction;
                                    echo '<tr>
                                <td>' . $array['payslip_entry_id'] . '</td>' ?>
                                    <?php if ($role == 'Admin') { ?>
                                        <?php echo '<td>' . $array['associatenumber'] . '/' . strtok($array['fullname'], ' ') . '</td>' ?>
                                    <?php } ?>
                                    <?php echo '<td>' . (($array['payslip_issued_on'] !== null) ? date('d/m/y h:i:s a', strtotime($array['payslip_issued_on'])) : '') . '</td>
                                <td>' . date('F', mktime(0, 0, 0, $array['paymonth'], 1)) . '&nbsp;' . $array['payyear'] . '</td>
                                <td>' . $array['dayspaid'] . '</td>
                                <td>' . $net_pay . '</td>
                                <td>
                                
                                <form name="comment_' . $array['payslip_entry_id'] . '" action="#" method="POST" style="display: -webkit-inline-box;">
                                <input type="hidden" name="form-type" type="text" value="pay_comment">
                                <input type="hidden" name="payslip_entry_id" id="payslip_entry_id" type="text" value="' . $array['payslip_entry_id'] . '">
                                <textarea id="inp_' . $array['payslip_entry_id'] . '" name="comment" type="text" disabled>' . $array['comment'] . '</textarea>' ?>

                                    <?php if ($role == 'Admin') { ?>

                                        <?php echo '<br><button type="button" id="edit_' . $array['payslip_entry_id'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Edit"><i class="bi bi-pencil-square"></i></button>&nbsp;

                                <button type="submit" id="save_' . $array['payslip_entry_id'] . '" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Save"><i class="bi bi-save"></i></button>' ?>
                                    <?php } ?>
                                    <?php echo '</form></td>

                                <td><a href="payslip.php?ref=' . $array['payslip_entry_id'] . '" target="_blank" title="' . $array['payslip_entry_id'] . '"><i class="bi bi-file-earmark-pdf" style="font-size:17px;color: #767676;"></i></a>' ?>

                                    <?php if ($role == 'Admin') { ?>
                                        <?php echo '&nbsp;
                                        <a href="https://api.whatsapp.com/send?phone=91' . $array['phone']. '&text=Dear ' . $array['fullname'] . ' (' . $array['associatenumber'] . '),%0A%0AYour salary slip for the month of ' . date('F', mktime(0, 0, 0, $array['paymonth'], 1)) . '&nbsp;' . $array['payyear'] . ' has been issued. Please check your email for more details.%0A%0ANeed help? Call us at +91 7980168159 or contact us at info@rssi.in.
                                        %0A%0A--RSSI%0A%0A**This is an automatically generated SMS
                                        " target="_blank"><i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS ' . $array['phone'] .'"></i></a>
                                        ' ?>
                                    <?php } ?>
                                <?php echo '</td>
                                
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
    <script>
        var data = <?php echo json_encode($resultArr) ?>;

        data.forEach(item => {

            const form = document.getElementById('edit_' + item.payslip_entry_id);

            form.addEventListener('click', function() {
                document.getElementById('inp_' + item.payslip_entry_id).disabled = false;
            });
        })

        //For form submission - to update Remarks
        const scriptURL = 'payment-api.php'

        data.forEach(item => {
            const form = document.forms['comment_' + item.payslip_entry_id]
            form.addEventListener('submit', e => {
                e.preventDefault()
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['comment_' + item.payslip_entry_id])
                    })
                    .then(response => alert("Comment has been updated.") +
                        location.reload())
                    .catch(error => console.error('Error!', error.message))
            })

            console.log(item)
        })
    </script>
</body>

</html>