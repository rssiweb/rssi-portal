<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

if (isset($_POST['form-type']) && $_POST['form-type'] === "reimbursementapply") {
    // Sanitize and validate user inputs
    $claimid = 'RSC' . time();
    $claimhead = filter_var($_POST['claimhead'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $claimheaddetails = filter_var($_POST['claimheaddetails'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $billno = filter_var($_POST['billno'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $uploadedFile = $_FILES['billdoc'] ?? null;
    $currency = filter_var($_POST['currency'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $amount = filter_var($_POST['amount'] ?? '', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $ack = filter_var($_POST['ack'] ?? 0, FILTER_VALIDATE_INT);
    $email = $email ?? ''; // Make sure you have the $email variable defined somewhere
    $applicantid = $associatenumber ?? ''; // Make sure you have the $associatenumber variable defined somewhere
    $now = date('Y-m-d H:i:s');
    $currentAcademicYear = (date('m') >= 1 && date('m') <= 3) ? (date('Y') - 1) . '-' . date('Y') : date('Y') . '-' . (date('Y') + 1);

    // send $file to google =======> google (rssi.in) // robotic service account credential.json
    $doclink = null;
    if (!empty($uploadedFile['name'])) {
        $filename = "doc_" . $claimid . "_" . $applicantid;
        $parent = '1MPw1VqHe_dvY3bZ-O1EWYYRsXGEx2wilyEGaCdHOq4HG2Fhg8qgNWfOejgB0USBGfZJNlnsC';
        $doclink = uploadeToDrive($uploadedFile, $parent, $filename);
    }

    if ($claimid !== "") {
        // Insert the claim using prepared statements
        $claimsubmit = "INSERT INTO claim (timestamp, reimbid, registrationid, selectclaimheadfromthelistbelow, claimheaddetails, billno, currency, totalbillamount, uploadeddocuments, ack, year)
                        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)";
        pg_prepare($con, "insert_claim", $claimsubmit);
        $data = array($now, $claimid, $applicantid, $claimhead, $claimheaddetails, $billno, $currency, $amount, $doclink, $ack, $currentAcademicYear);
        $result = pg_execute($con, "insert_claim", $data);
        $cmdtuples = pg_affected_rows($result);
    }

    if ($cmdtuples === 1 && !empty($email)) {
        // ... Send email notification ...
        sendEmail("claimapply", array(
            "reimbid" => $claimid,
            "registrationid" => $associatenumber,
            "fullname" => $fullname,
            "totalbillamount" => $amount,
            "currency" => $currency,
            "timestamp" => date("d/m/Y g:i a", strtotime($now))
        ), $email);
    }
}

// Initialize array to store latest submissions for both account natures
$latestSubmissions = [];

// Array to hold account natures
$accountNatures = ['reimbursement'];

// Loop through each account nature
foreach ($accountNatures as $accountNature) {
    // Initialize $latestSubmission_bank variable to null for each account nature
    $latestSubmission_bank = null;
    // Retrieve latest submissions from the bankdetails table for the current account nature
    $selectLatestQuery_bank = "SELECT bank_account_number, bank_name, ifsc_code, account_holder_name, updated_for, updated_by, updated_on, passbook_page
                          FROM bankdetails
                          WHERE updated_for = '$associatenumber' 
                          AND account_nature = '$accountNature'
                          AND updated_on = (SELECT MAX(updated_on) FROM bankdetails WHERE updated_for = '$associatenumber' AND account_nature = '$accountNature')";
    $result = pg_query($con, $selectLatestQuery_bank);

    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            // Store the latest submission for the current account nature
            $latestSubmission_bank = [
                'bank_account_number' => $row['bank_account_number'],
                'bank_name' => $row['bank_name'],
                'ifsc_code' => $row['ifsc_code'],
                'account_holder_name' => $row['account_holder_name'],
                'updated_for' => $row['updated_for'],
                'updated_by' => $row['updated_by'],
                'updated_on' => $row['updated_on'],
                'passbook_page' => $row['passbook_page']
            ];
        }
    }

    // Store the latest submission for the current account nature in the array
    $latestSubmissions[$accountNature] = $latestSubmission_bank;
}

// Now $latestSubmissions array will contain the latest submission data for both account natures
// You can access the data using $latestSubmissions['reimbursement'] and $latestSubmissions['savings']

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
    <?php include 'includes/meta.php' ?>



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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <style>
        .checkbox {
            padding: 0;
            margin: 0;
            vertical-align: bottom;
            position: relative;
            top: 0px;
            overflow: hidden;
        }

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

        #hidden-panel,
        #hidden-panel_ack {
            display: none;
        }
    </style>

</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <?php if ($_POST && $cmdtuples == 0) { ?>
                                <script>
                                    alert("ERROR: Oops, something wasn't right.");
                                </script>
                            <?php } else if ($_POST && $cmdtuples == 1) { ?>
                                <script>
                                    alert("Claim no <?php echo $claimid ?> has been submitted.");
                                    window.location.href = window.location.href;
                                </script>
                            <?php } ?>

                            <div class=col style="text-align: right;">
                                <a href="reimbursementstatus.php" target="_self" class="btn btn-danger btn-sm" role="button">Track Your Claim</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Submit claim</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <form autocomplete="off" name="reimbursement" id="reimbursement" action="reimbursement.php" method="POST" enctype="multipart/form-data">
                                                    <fieldset <?php echo ($filterstatus != 'Active') ? 'disabled' : ''; ?>>
                                                        <div class="form-group" style="display: inline-block;">

                                                            <input type="hidden" name="form-type" value="reimbursementapply">

                                                            <span class="input-help">
                                                                <select name="claimhead" id="claimhead" class="form-select" required>
                                                                    <option disabled selected hidden value="">Select</option>
                                                                    <option value="Business Meeting Expenses">Business Meeting Expenses</option>
                                                                    <option value="Client Entertainment">Client Entertainment</option>
                                                                    <option value="Communication Expenses">Communication Expenses</option>
                                                                    <option value="Conference and Training Courses">Conference and Training Courses</option>
                                                                    <option value="IT Peripherals">IT Peripherals</option>
                                                                    <option value="Local Conveyance">Local Conveyance</option>
                                                                    <option value="Marketing Expenses">Marketing Expenses</option>
                                                                    <option value="Office Expense">Office Expense</option>
                                                                    <option value="Professional Membership">Professional Membership</option>
                                                                    <option value="Shift Working / Extended Hours">Shift Working / Extended Hours</option>
                                                                    <option value="Staff Welfare">Staff Welfare</option>
                                                                    <option value="Student Welfare">Student Welfare</option>
                                                                    <option value="Visa & Passport Expenses">Visa & Passport Expenses</option>

                                                                </select>
                                                                <small id="passwordHelpBlock" class="form-text text-muted">Claim head<span style="color:red">*</span></small>
                                                            </span>

                                                            <span class="input-help">
                                                                <textarea class="form-control" name="claimheaddetails" id="claimheaddetails" placeholder="Claim head details" required></textarea>
                                                                <small id="passwordHelpBlock" class="form-text text-muted">Claim head details<span style="color:red">*</span><br>(Please mention the purpose of the expenditure.)</small>
                                                            </span>

                                                            <span class="input-help">
                                                                <input type="text" class="form-control" name="billno" id="billno" placeholder="Bill no" required>
                                                                <small id="passwordHelpBlock" class="form-text text-muted">Bill no<span style="color:red">*</span><br>(In case of multiple values ​​write it with comma (,).)</small>
                                                            </span>

                                                            <span class="input-help">
                                                                <select name="currency" id="currency" class="form-select" required>
                                                                    <option disabled selected hidden value="">Select</option>
                                                                    <option value="INR" selected>INR</option>
                                                                </select>
                                                                <small id="passwordHelpBlock" class="form-text text-muted">Currency<span style="color:red">*</span></small>
                                                            </span>

                                                            <span class="input-help">
                                                                <input type="number" class="form-control" name="amount" id="amount" placeholder="Amount" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" required>
                                                                <small id="passwordHelpBlock" class="form-text text-muted">Amount<span style="color:red">*</span></small>
                                                            </span>

                                                            <span class="input-help">
                                                                <input type="file" name="billdoc" class="form-control" onchange="compressImageBeforeUpload(this)" required />
                                                                <small id="passwordHelpBlock" class="form-text text-muted">Documents<span style="color:red">*</span></small>
                                                            </span>

                                                            <div id="filter-checksh">
                                                                <input class="form-check-input" type="checkbox" name="ack" id="ack" value="1" required />
                                                                <label for="ack" style="font-weight: 400;">I, hereby authorize Insurer/TPA to audit/investigate my claims also authorize to share the claim details with Government authorities/IRDA for audit requirements. I also agree and certify that all of the information pertaining to this claim is true and correct if it is found to be false and/or if it is proved that claim documents are manipulated then, I understand and agree that RSSI will initiate appropriate disciplinary proceedings which may also lead to termination of my employment with RSSI.</label>
                                                            </div>
                                                            </span>
                                                            <br>
                                                            <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">Submit</button>

                                                        </div>
                                                    </fieldset>
                                                </form>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Bank Account Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="line-height: 2;">
                                                <?php foreach ($latestSubmissions as $accountNature => $latestSubmission) : ?>
                                                    <?php if ($latestSubmission !== null) : ?>
                                                        <h4 class="mt-3"><?php echo ucfirst($accountNature); ?> Account Details</h4>
                                                        <p class="mb-1">Bank Account Number: <?php echo isset($latestSubmission['bank_account_number']) ? $latestSubmission['bank_account_number'] : 'N/A'; ?></p>
                                                        <p class="mb-1">Name of the Bank: <?php echo isset($latestSubmission['bank_name']) ? $latestSubmission['bank_name'] : 'N/A'; ?></p>
                                                        <p class="mb-1">IFSC Code: <?php echo isset($latestSubmission['ifsc_code']) ? $latestSubmission['ifsc_code'] : 'N/A'; ?></p>
                                                        <p class="mb-1">Account Holder Name: <?php echo isset($latestSubmission['account_holder_name']) ? $latestSubmission['account_holder_name'] : 'N/A'; ?></p>
                                                        <?php if (isset($latestSubmission['passbook_page'])) : ?>
                                                            <p class="mb-1"><a href="<?php echo $latestSubmission['passbook_page']; ?>" target="_blank">First Page of Bank Account Passbook</a></p>
                                                        <?php endif; ?>
                                                        <br>
                                                        <p>(Last updated by <?php echo isset($latestSubmission['updated_by']) ? $latestSubmission['updated_by'] : 'N/A'; ?> on <?php echo isset($latestSubmission['updated_on']) ? $latestSubmission['updated_on'] : 'N/A'; ?>)</p>
                                                    <?php else : ?>
                                                        <!-- Handle case when bank details are not available for the current account nature -->
                                                        <p>No <?php echo ucfirst($accountNature); ?> account details available.</p>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </td>
                                        </tr>
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
    <script src="../assets_new/js/image-compressor-100kb.js"></script>

    <!-- Add this script at the end of the HTML body -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Bootstrap Modal -->
    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p id="loadingMessage">Submission in progress.
                            Please do not close or reload this page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Create a new Bootstrap modal instance with backdrop: 'static' and keyboard: false options
        const myModal = new bootstrap.Modal(document.getElementById("myModal"), {
            backdrop: 'static',
            keyboard: false
        });
        // Add event listener to intercept Escape key press
        document.body.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                // Prevent default behavior of Escape key
                event.preventDefault();
            }
        });
    </script>
    <script>
        // Function to show loading modal
        function showLoadingModal() {
            $('#myModal').modal('show');
        }

        // Function to hide loading modal
        function hideLoadingModal() {
            $('#myModal').modal('hide');
        }

        // Add event listener to form submission
        document.getElementById('reimbursement').addEventListener('submit', function(event) {
            // Show loading modal when form is submitted
            showLoadingModal();
        });

        // Optional: Close loading modal when the page is fully loaded
        window.addEventListener('load', function() {
            // Hide loading modal
            hideLoadingModal();
        });
    </script>

</body>

</html>