<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");
$searchField = isset($_GET['searchField']) ? trim($_GET['searchField']) : '';

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

// Check if donation exists and if its status is Approved
$isValidDonation = false;

if (is_array($resultArr) && count($resultArr) > 0) {
    if (strtolower($resultArr[0]['status'] ?? '') === 'approved') {
        $isValidDonation = true;
    }
}
// Close the database connection if needed
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
                /* width: 100%; */
                border-top: solid 1px #ccc;
                /* padding: 5px 0;
                text-align: center; */
                /* background-color: #f9f9f9; */
                word-wrap: break-word;
            }
        }

        @media print {
            .watermark {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 60px;
                width: 80%;
                color: rgba(0, 0, 0, 0.15);
                /* Light gray with transparency */
                z-index: 9999;
                pointer-events: none;
                font-weight: bold;
                text-align: center;
                /* opacity: 0.8; */
            }
        }

        /*@media print {
            .watermark {
                position: fixed;
                width: 100%;
                height: 100%;
                top: 0;
                left: 0;
                z-index: 9999;
                pointer-events: none;
                opacity: 0.1;
                background: url('path/to/watermark-image.png') center center no-repeat;
                background-size: 50% auto;
            }
        }*/

        @media screen {
            .watermark {
                display: none;
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
        <form action="donation_invoice.php" method="get" class="mt-3">
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
                <?php if ($isValidDonation): ?>
                    <!-- Display the invoice only if the donation is valid -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="flex-align">
                                <div>
                                    <img src="../img/logo_bg.png" alt="Logo" class="logo">
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
                                <?php
                                if (!empty($array['id_number'])) {
                                    $idType = !empty($array['id_type']) ? $array['id_type'] : 'pan';

                                    echo $idType . ' - '
                                        . str_repeat('X', max(0, strlen($array['id_number']) - 4))
                                        . substr($array['id_number'], -4) . '<br>';
                                }
                                ?>
                                <?php echo $array['tel'] ?><br>
                                <?php echo $array['email'] ?></p>
                            <hr>
                            <p class="text-end">Total in <?php echo $array['currency'] ?></p>
                            <h4 class="text-end">&#8377;<?php echo $array['amount'] ?></h4>
                            <?php
                            function amountInWords($amount)
                            {
                                $words = [
                                    0 => 'Zero',
                                    1 => 'One',
                                    2 => 'Two',
                                    3 => 'Three',
                                    4 => 'Four',
                                    5 => 'Five',
                                    6 => 'Six',
                                    7 => 'Seven',
                                    8 => 'Eight',
                                    9 => 'Nine',
                                    10 => 'Ten',
                                    11 => 'Eleven',
                                    12 => 'Twelve',
                                    13 => 'Thirteen',
                                    14 => 'Fourteen',
                                    15 => 'Fifteen',
                                    16 => 'Sixteen',
                                    17 => 'Seventeen',
                                    18 => 'Eighteen',
                                    19 => 'Nineteen',
                                    20 => 'Twenty',
                                    30 => 'Thirty',
                                    40 => 'Forty',
                                    50 => 'Fifty',
                                    60 => 'Sixty',
                                    70 => 'Seventy',
                                    80 => 'Eighty',
                                    90 => 'Ninety'
                                ];

                                $amount = number_format($amount, 2, '.', '');
                                [$rupees, $paise] = explode('.', $amount);

                                $rupees = (int)$rupees;

                                if ($rupees === 0) {
                                    $result = 'Zero Rupees';
                                } else {
                                    $result = '';

                                    $levels = [
                                        10000000 => 'Crore',
                                        100000   => 'Lakh',
                                        1000     => 'Thousand',
                                        100      => 'Hundred'
                                    ];

                                    foreach ($levels as $value => $label) {
                                        if ($rupees >= $value) {
                                            $count = intdiv($rupees, $value);
                                            $rupees %= $value;
                                            $result .= numberToWords($count, $words) . " $label ";
                                        }
                                    }

                                    if ($rupees > 0) {
                                        $result .= numberToWords($rupees, $words) . ' ';
                                    }

                                    $result = trim($result) . ' Rupees';
                                }

                                if ((int)$paise > 0) {
                                    $result .= ' and ' . numberToWords((int)$paise, $words) . ' Paise';
                                }

                                return $result . ' Only';
                            }

                            function numberToWords($num, $words)
                            {
                                if ($num < 21) {
                                    return $words[$num];
                                }
                                if ($num < 100) {
                                    return $words[intdiv($num, 10) * 10] . ($num % 10 ? ' ' . $words[$num % 10] : '');
                                }
                                return '';
                            }
                            ?>
                            <p class="text-end small fst-italic">
                                (<?= amountInWords($array['amount']); ?>)
                            </p>
                            <!-- <hr> -->
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
                            <!-- <hr> -->

                            <?php if ($array['id_number'] !== null) { ?>
                                <p class="small">Donations to Rina Shiksha Sahayak Foundation shall be eligible for tax benefits under section 80G(5)(vi) of the Income Tax Act, 1961.</p>
                            <?php } else { ?>
                                <p class="small">PAN has not been provided. As per Income Tax rules, the donor is not eligible
                                    to claim 50% tax exemption under Section 80G of the Income Tax Act, 1961.</p>
                                <div id="watermark" class="watermark">NOT ELIGIBLE FOR 80G TAX BENEFIT</div>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Footer to display document generated date and message -->
                    <div class="report-footer p-2 text-end">
                        <p class="small mb-0">Document generated on: <?php echo date("d/m/Y g:i a") ?>. This is a computer-generated document. No signature is required. </p>
                    </div>
                <?php else: ?>
                    <!-- Show the rejected donation modal only -->
                    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Access Denied</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    This donation has been rejected and cannot be used to generate an invoice. Please contact support for further assistance.
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                        window.onload = function() {
                            var myModal = new bootstrap.Modal(document.getElementById('myModal'), {
                                backdrop: 'static',
                                keyboard: false
                            });
                            myModal.show();
                        };
                    </script>
                <?php endif; ?>
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