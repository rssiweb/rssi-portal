<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");
$searchField = isset($_GET['searchField']) ? $_GET['searchField'] : '';

// Set up the database connection ($con) here if not already done.

if ($searchField !== '') {
    // Use parameterized queries to prevent SQL injection
    $query = "SELECT *
              FROM donation_paymentdata AS pd
              LEFT JOIN donation_userdata AS ud ON pd.tel = ud.tel
              WHERE pd.donationid = $1"; // Using $1 as a placeholder for the parameter
    $result = pg_query_params($con, $query, array($searchField));
    // Fetch all the rows as an associative array
    $resultArr = pg_fetch_all($result);
} else {
    // If $searchField is empty, set an empty result array
    $resultArr = array();
}
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
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Invoice_<?php echo $searchField ?></title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!------ Include the above in your HEAD tag ---------->
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
        .prebanner {
            display: none;
        }

        .logo {
            width: 50%;
        }

        .invoice-text {
            text-align: right;
        }

        .flex-align {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        /* Additional styles for the footer and QR code */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px;
            text-align: center;
        }

        hr {
            margin-top: 0;
        }

        #qrcode {
            margin-top: 10px;
            float: right;
        }

        .qr-code-section {
            margin-top: 10px;
            text-align: right;
        }

        @media print {

            /* Hide search and print section in print preview */
            .search-print-section {
                display: none;
            }

            /* Make sure the footer is visible in print preview */
            .report-footer {
                position: fixed;
                bottom: 0;
                display: inline-block;
                width: 100%;
                border-top: solid 1px #ccc;
                padding: 5px 0;
                text-align: center;
                background-color: #f9f9f9;
            }
        }
    </style>
    <!-- QR Code library -->
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
    <!-- JavaScript functions for search and print -->
    <script>
        function printDocument() {
            window.print();
        }

        document.addEventListener("DOMContentLoaded", function() {
            // Assuming $resultArr contains your database query results
            <?php if (sizeof($resultArr) > 0) { ?>
                var donationId = "<?php echo $resultArr[0]['donationid']; ?>"; // Assuming donationid is in the first row
            <?php } ?>

            // Generate and display QR code
            var qrcode = new QRCode(document.getElementById("qrcode"), {
                text: "https://login.rssi.in/donation_invoice.php?searchField=" + donationId,
                width: 128,
                height: 128
            });

            // Get current date and display it in the footer
            var today = new Date();
            var dateOptions = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            document.getElementById("printDate").innerText = today.toLocaleDateString(undefined, dateOptions);
        });
    </script>
</head>

<body>
    <div class="container-lg">
        <!-- Search and print section (Not visible in print preview) -->
        <form action="donation_invoice.php" method="get">
            <div class="row mb-3 search-print-section">
                <div class="col-md-3">
                    <input type="text" class="form-control" id="searchField" name="searchField" placeholder="Donation reference ID" value="<?php echo $searchField ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i>&nbsp;Search</button>
                    <!-- Add a small gap between the buttons -->
                    <button type="button" class="btn btn-danger btn-sm ms-1" onclick="printDocument()"><i class="bi bi-save"></i>&nbsp;Save</button>
                </div>
            </div>
        </form>
        <?php if (sizeof($resultArr) > 0) { ?>
            <?php foreach ($resultArr as $array) { ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="flex-align">
                            <div>
                                <img src="../img/brand_logo.png" alt="Logo" class="logo">
                            </div>
                            <div class="invoice-text">
                                <h2>Invoice</h2>
                                <p class="text-end">Invoice number: <?php echo $array['donationid'] ?></br>
                                    Date (dd/MM/yyyy): <?php echo date("d/m/Y g:i a", strtotime($array['timestamp'])) ?></p>
                            </div>
                        </div>
                        <hr>
                        <h2>Rina Shiksha Sahayak Foundation</h2>
                        <p>1074/801/A Jhapetapur, Backside of Municipality, West Medinipur, West Bengal, 721301, India<br>
                            CIN: U80101WB2020NPL237900<br>
                            Section-12A Reg No: AAKCR2540KE20214 dated 31-05-2021<br>
                            Section-80G Reg No: AAKCR2540KF20214 dated 31-05-2021<br>
                            Permanent Account Number (PAN): AAKCR2540K<br>
                            Tax Deduction and Collection Account Number (TAN): CALR17955A<br>
                            www.rssi.in
                        </p>
                    </div>
                    <div class="col-md-6">
                        <div class="qr-code-section">
                            <!-- QR code to check authenticity -->
                            <div id="qrcode"></div>
                        </div>
                        <h4>Bill to</h4>
                        <p><?php echo $array['fullname'] ?><br>
                            <?php echo $array['postaladdress'] ?><br>
                            <?php echo $array['documenttype'] ?> - <?php echo $array['nationalid'] ?><br>
                            <?php echo $array['tel'] ?><br>
                            <?php echo $array['email'] ?></p>
                        <hr>
                        <p class="text-end">Total in <?php echo $array['currency'] ?></p>
                        <h4 class="text-end">&#8377;<?php echo $array['amount'] ?></h4>
                        <hr>
                        <h4>Summary</h4>
                        <hr>
                        <table class="table">
                            <tr>
                                <td>Transaction ID</td>
                                <td><?php echo $array['transactionid'] ?></td>
                            </tr>
                            <tr>
                                <td>Mode of Payment</td>
                                <td>Online</td>
                            </tr>
                        </table>
                        <hr>
                        <p class="small">Donations to Rina Shiksha Sahayak Foundation shall be eligible for tax benefits under section 80G(5)(vi) of the Income Tax Act, 1961.</p>
                    </div>
                </div>
    </div>


    <!-- Footer to display document generated date and message -->
    <div class="report-footer p-2 bg-light text-end">
        <p class="small mb-0">Document generated on: <?php echo date("d/m/Y g:i a") ?>. This is a computer-generated document. No signature is required. </p>
    </div>
<?php }
        } else { ?>
<!-- Onboarding not initiated -->
<div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Access Denied</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php
                if (empty($searchField)) {
                    $error_message = "No donation ID entered.";
                } else {
                    if (pg_num_rows($result) == 0) {
                        $error_message = "No record found for the entered donation ID";
                    }
                }
                if (isset($error_message)) {
                    echo $error_message;
                } ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
<script>
    window.onload = function() {
        var myModal = new bootstrap.Modal(document.getElementById('myModal'), {
            backdrop: 'static',
            keyboard: false
        });
        myModal.show();
    };
</script>

</body>

</html>