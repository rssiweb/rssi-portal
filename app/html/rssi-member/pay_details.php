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

setlocale(LC_TIME, 'fr_FR.UTF-8');

$year = isset($_POST['get_year']) ? $_POST['get_year'] : '';
$months = isset($_POST['get_month']) ? $_POST['get_month'] : [];

if ($role == 'Admin') {
    $id = isset($_POST['get_aid']) ? strtoupper($_POST['get_aid']) : '';

    // Construct the WHERE clause for filtering by year
    $yearFilter = !empty($year) ? "AND payyear = $year" : '';

    // Construct the WHERE clause for filtering by month
    $monthFilter = '';
    if (!empty($months)) {
        // Ensure $months is always an array
        if (!is_array($months)) {
            $months = [$months];
        }
        $monthFilter = "AND paymonth::integer IN (" . implode(',', $months) . ")";
    }

    // Check if both filters are empty; if so, set a default condition to return no data
    if (empty($year) && empty($months)) {
        $query = "SELECT * FROM payslip_entry WHERE 1=0"; // This will return no data
    } else {
        // Construct the SQL query with proper handling of empty $id
        $idFilter = !empty($id) ? "AND employeeid = '$id'" : '';
        $query = "SELECT * FROM payslip_entry 
            LEFT JOIN (
                SELECT associatenumber, fullname, email, phone FROM rssimyaccount_members
            ) AS associate ON payslip_entry.employeeid=associate.associatenumber
            WHERE 1=1 $idFilter $yearFilter $monthFilter
            ORDER BY payslip_issued_on DESC";
    }

    $result = pg_query($con, $query);
} elseif ($role != 'Admin') {
    // Construct the WHERE clause for filtering by year
    $yearFilter = !empty($year) ? "AND payyear = $year" : '';

    // Construct the WHERE clause for filtering by month
    $monthFilter = '';
    if (!empty($months)) {
        // Ensure $months is always an array
        if (!is_array($months)) {
            $months = [$months];
        }
        $monthFilter = "AND paymonth::integer IN (" . implode(',', $months) . ")";
    }

    // Check if both filters are empty; if so, set a default condition to return no data
    if (empty($year) && empty($months)) {
        $query = "SELECT * FROM payslip_entry WHERE 1=0"; // This will return no data
    } else {
        // Construct the SQL query for non-Admin role with month and year filters
        $query = "SELECT * FROM payslip_entry 
            WHERE employeeid = '$associatenumber' $yearFilter $monthFilter
            ORDER BY payslip_issued_on DESC";
    }

    $result = pg_query($con, $query);
}

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$totalGrossPay = 0;
$totalNetPay = 0;

// Loop through the payslip entries
while ($row = pg_fetch_assoc($result)) {
    $payslipEntryID = $row['payslip_entry_id'];

    // --- Earnings excluding Bonus Payout (for Gross Pay) ---
    $grossEarningsQuery = pg_query($con, "
        SELECT COALESCE(SUM(amount), 0) AS total_gross_earning
        FROM payslip_component 
        WHERE payslip_entry_id = '$payslipEntryID'
        AND components = 'Earning'
        AND subcategory != 'Bonus Payout'
    ") or die(pg_last_error());
    $grossEarnings = pg_fetch_result($grossEarningsQuery, 0, 'total_gross_earning');

    // --- Earnings including Bonus Payout (for Net Pay) ---
    $netEarningsQuery = pg_query($con, "
        SELECT COALESCE(SUM(amount), 0) AS total_net_earning
        FROM payslip_component 
        WHERE payslip_entry_id = '$payslipEntryID'
        AND components = 'Earning'
    ") or die(pg_last_error());
    $netEarnings = pg_fetch_result($netEarningsQuery, 0, 'total_net_earning');

    // --- Deductions (all included) ---
    $deductionsQuery = pg_query($con, "
        SELECT COALESCE(SUM(amount), 0) AS total_deduction
        FROM payslip_component 
        WHERE payslip_entry_id = '$payslipEntryID'
        AND components = 'Deduction'
    ") or die(pg_last_error());
    $deductions = pg_fetch_result($deductionsQuery, 0, 'total_deduction');

    // --- Calculate Gross and Net Pay ---
    $grossPay = $grossEarnings;                    // Excludes Bonus Payout
    $netPay = $netEarnings - $deductions;          // Includes Bonus Payout

    // --- Add to totals ---
    $totalGrossPay += $grossPay;
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

        function gtag() {
            dataLayer.push(arguments);
        }
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

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
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <!-- JavaScript Library Files -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
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
                                    <p>Total Gross pay:&nbsp;<?php echo ($totalGrossPay) ?><br>
                                        Total Net Pay:&nbsp;<?php echo ($totalNetPay) ?></p>
                                </div>
                                <div class="col text-end">
                                    <?php if ($role == 'Admin') { ?>
                                        <div class="col" style="display: inline-block; width:100%; text-align:right">
                                            <form method="POST" action="export_function.php">
                                                <input type="hidden" value="paydetails" name="export_type" />

                                                <input type="hidden" value="<?php echo $id ?>" name="id" />

                                                <input type="hidden" value="<?php echo $year ?>" name="year" />
                                                <?php foreach ($months as $month) { ?>
                                                    <input type="hidden" value="<?php echo $month ?>" name="months[]" />
                                                <?php } ?>

                                                <button type="submit" id="export" name="export" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Export CSV">
                                                    <i class="bi bi-file-earmark-excel" style="font-size:large;"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <form action="" method="POST">
                                <div class="row g-2 align-items-end mb-3">
                                    <?php if ($role == 'Admin') { ?>
                                        <div class="col-md-2">
                                            <label for="get_aid" class="form-label">Associate number</label>
                                            <input name="get_aid" id="get_aid" class="form-control" placeholder="Associate number" value="<?php echo $id ?>">
                                        </div>
                                    <?php } ?>

                                    <div class="col-md-1">
                                        <label for="get_year" class="form-label">Year</label>
                                        <select name="get_year" id="get_year" class="form-select" required>
                                            <?php if ($year == null) { ?>
                                                <option value="" hidden selected>Select year</option>
                                            <?php } else { ?>
                                                <option hidden selected><?php echo $year ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="get_month" class="form-label">Months</label>
                                        <select name="get_month[]" id="get_month" class="form-select" multiple>
                                            <option value="1" <?php if (in_array('1', $months)) echo 'selected'; ?>>January</option>
                                            <option value="2" <?php if (in_array('2', $months)) echo 'selected'; ?>>February</option>
                                            <option value="3" <?php if (in_array('3', $months)) echo 'selected'; ?>>March</option>
                                            <option value="4" <?php if (in_array('4', $months)) echo 'selected'; ?>>April</option>
                                            <option value="5" <?php if (in_array('5', $months)) echo 'selected'; ?>>May</option>
                                            <option value="6" <?php if (in_array('6', $months)) echo 'selected'; ?>>June</option>
                                            <option value="7" <?php if (in_array('7', $months)) echo 'selected'; ?>>July</option>
                                            <option value="8" <?php if (in_array('8', $months)) echo 'selected'; ?>>August</option>
                                            <option value="9" <?php if (in_array('9', $months)) echo 'selected'; ?>>September</option>
                                            <option value="10" <?php if (in_array('10', $months)) echo 'selected'; ?>>October</option>
                                            <option value="11" <?php if (in_array('11', $months)) echo 'selected'; ?>>November</option>
                                            <option value="12" <?php if (in_array('12', $months)) echo 'selected'; ?>>December</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <button type="submit" name="search_by_id" class="btn btn-success">
                                            <i class="bi bi-search"></i>&nbsp;Search
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <script>
                                var currentYear = new Date().getFullYear();
                                for (var i = 0; i < 5; i++) {
                                    var year = currentYear;
                                    //next.toString().slice(-2)
                                    $('#get_year').append(new Option(year));
                                    currentYear--;
                                }
                            </script>

                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th scope="col">Reference number</th>
                                            <?php if ($role == 'Admin'): ?>
                                                <th scope="col">ID/F Name</th>
                                            <?php endif; ?>
                                            <th scope="col">Issuance Date</th>
                                            <th scope="col">Pay month</th>
                                            <th scope="col">Days paid</th>
                                            <th scope="col">Gross pay</th>
                                            <th scope="col">Netpay</th>
                                            <th scope="col">HR comments</th>
                                            <th scope="col">Payslip</th>
                                        </tr>
                                    </thead>
                                    <?php if (sizeof($resultArr) > 0): ?>
                                        <tbody>
                                            <?php foreach ($resultArr as $array):
                                                $payslip_entry_id = $array['payslip_entry_id'];

                                                // --- Earnings excluding Bonus Payout (for total_earning / Gross) ---
                                                $result_component_earning_total = pg_query($con, "
                                                    SELECT COALESCE(SUM(amount), 0) 
                                                    FROM payslip_component 
                                                    WHERE payslip_entry_id = '$payslip_entry_id' 
                                                    AND components = 'Earning' 
                                                    AND subcategory != 'Bonus Payout'
                                                ");
                                                $total_earning = pg_fetch_result($result_component_earning_total, 0, 0);

                                                // --- Earnings including Bonus Payout (for Net Pay) ---
                                                $result_component_earning_with_bonus = pg_query($con, "
                                                    SELECT COALESCE(SUM(amount), 0) 
                                                    FROM payslip_component 
                                                    WHERE payslip_entry_id = '$payslip_entry_id' 
                                                    AND components = 'Earning'
                                                ");
                                                $earning_including_bonus = pg_fetch_result($result_component_earning_with_bonus, 0, 0);

                                                // --- Deductions (all included) ---
                                                $result_component_deduction_total = pg_query($con, "
                                                    SELECT COALESCE(SUM(amount), 0) 
                                                    FROM payslip_component 
                                                    WHERE payslip_entry_id = '$payslip_entry_id' 
                                                    AND components = 'Deduction'
                                                ");
                                                $total_deduction = pg_fetch_result($result_component_deduction_total, 0, 0);

                                                // --- Net Pay (includes Bonus Payout) ---
                                                $net_pay = $earning_including_bonus - $total_deduction;
                                            ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($array['payslip_entry_id']) ?></td>
                                                    <?php if ($role == 'Admin'): ?>
                                                        <td><?= htmlspecialchars($array['associatenumber']) ?>/<?= htmlspecialchars(strtok($array['fullname'], ' ')) ?></td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <?= ($array['payslip_issued_on'] !== null) ? date('d/m/y h:i:s a', strtotime($array['payslip_issued_on'])) : '' ?>
                                                    </td>
                                                    <td><?= date('F', mktime(0, 0, 0, $array['paymonth'], 1)) ?>&nbsp;<?= htmlspecialchars($array['payyear']) ?></td>
                                                    <td><?= htmlspecialchars($array['dayspaid']) ?></td>
                                                    <td><?= htmlspecialchars($total_earning) ?></td>
                                                    <td><?= htmlspecialchars($net_pay) ?></td>
                                                    <td>
                                                        <form name="comment_<?= $payslip_entry_id ?>" action="#" method="POST" style="display: -webkit-inline-box;">
                                                            <input type="hidden" name="form-type" value="pay_comment">
                                                            <input type="hidden" name="payslip_entry_id" value="<?= $payslip_entry_id ?>">
                                                            <textarea id="inp_<?= $payslip_entry_id ?>" name="comment" disabled><?= htmlspecialchars($array['comment']) ?></textarea>
                                                            <?php if ($role == 'Admin'): ?>
                                                                <br>
                                                                <button type="button" id="edit_<?= $payslip_entry_id ?>" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Edit">
                                                                    <i class="bi bi-pencil-square"></i>
                                                                </button>&nbsp;
                                                                <button type="submit" id="save_<?= $payslip_entry_id ?>" style="display: -webkit-inline-box; width:fit-content; word-wrap:break-word;outline: none;background: none; padding: 0px; border: none;" title="Save">
                                                                    <i class="bi bi-save"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="dropdownMenuLink_<?= $payslip_entry_id ?>" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 4px 8px;">
                                                                <i class="bi bi-three-dots-vertical" style="font-size:14px;"></i>
                                                            </button>
                                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink_<?= $payslip_entry_id ?>">
                                                                <li>
                                                                    <a class="dropdown-item" href="payslip.php?ref=<?= urlencode($payslip_entry_id) ?>" target="_blank" title="Download Payslip">
                                                                        <i class="bi bi-file-earmark-pdf me-2"></i> Download Payslip
                                                                    </a>
                                                                </li>
                                                                <?php if ($role == 'Admin'): ?>
                                                                    <?php
                                                                    $phone = urlencode($array['phone']);
                                                                    $message = urlencode(
                                                                        "Dear {$array['fullname']} ({$array['associatenumber']}),\n\n" .
                                                                            "Your salary slip for the month of " . date('F', mktime(0, 0, 0, $array['paymonth'], 1)) . " {$array['payyear']} has been issued.\n\n" .
                                                                            "Please check your email for more details. Your salary payment has been processed. It may take standard time for it to be reflected in your account.\n\n" .
                                                                            "Need help? Call us at +91 7980168159 or contact us at info@rssi.in.\n\n" .
                                                                            "--RSSI\n\n**This is an automatically generated SMS"
                                                                    );
                                                                    $whatsapp_url = "https://api.whatsapp.com/send?phone=91{$phone}&text={$message}";
                                                                    ?>
                                                                    <li>
                                                                        <a class="dropdown-item" href="<?= $whatsapp_url ?>" target="_blank" title="Send SMS">
                                                                            <i class="bi bi-whatsapp me-2"></i> Send WhatsApp
                                                                        </a>
                                                                    </li>

                                                                <?php endif; ?>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    <?php else: ?>
                                        <tbody>
                                            <tr>
                                                <td colspan="9">No record found or you are not eligible to withdraw salary from the organization.</td>
                                            </tr>
                                        </tbody>
                                    <?php endif; ?>
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
  <script src="../assets_new/js/text-refiner.js?v=1.1.0"></script>
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
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#get_month').select2({
                placeholder: 'Select months',
                allowClear: false,
            });
        });
    </script>
</body>

</html>