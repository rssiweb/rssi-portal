<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

// if ($role == 'Admin') {

//     $id = isset($_GET['get_aid']) ? strtoupper($_GET['get_aid']) : '';
// }

$uploadedfor = !empty($id) ? $id : $associatenumber ?? '';


if (isset($_POST['form-type']) && $_POST['form-type'] === "bank_details") {
    // Sanitize and validate user inputs

    // Get other form data
    $uploadedfor = !empty($id) ? $id : $associatenumber ?? '';
    $uploadedby = $associatenumber ?? ''; // Make sure you have the $associatenumber variable defined somewhere
    $now = date('Y-m-d H:i:s');
    $transaction_id = time();

    // Get bank details
    $bank_account_number = $_POST['bank_account_number'] ?? '';
    $bank_name = $_POST['bank_name'] ?? '';
    $ifsc_code = $_POST['ifsc_code'] ?? '';
    $account_holder_name = $_POST['account_holder_name'] ?? '';
    $account_nature = $_POST['account_nature'] ?? '';

    // Upload and insert passbook page if provided
    if (!empty($_FILES['passbook_page']['name'])) {
        $passbook_page = $_FILES['passbook_page'];
        $filename = $uploadedfor . "_passbook_page_" . time();
        $parent = '1S6uLPt5G7hX4Iacgzx73gqdXsO-uKA4R';
        $doclink = uploadeToDrive($passbook_page, $parent, $filename);

        if ($doclink !== null) {
            // Insert passbook page into bankdetails table
            $insertQuery = "INSERT INTO bankdetails (bank_account_number, bank_name, ifsc_code, account_holder_name, passbook_page, updated_for, updated_by, updated_on, transaction_id,account_nature)
                            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9,$10)";
            $result = pg_query_params($con, $insertQuery, array($bank_account_number, $bank_name, $ifsc_code, $account_holder_name, $doclink, $uploadedfor, $uploadedby, $now, $transaction_id, $account_nature));
            $cmdtuples = pg_affected_rows($result);
            if (!$result) {
                // Handle insert error
            }
        }
    }
}

// Initialize array to store latest submissions for both account natures
$latestSubmissions = [];

// Array to hold account natures
$accountNatures = ['reimbursement', 'savings'];

// Loop through each account nature
foreach ($accountNatures as $accountNature) {
    // Initialize $latestSubmission_bank variable to null for each account nature
    $latestSubmission_bank = null;
    $datafor = $fullname;
    // Try to fetch $datafor from rssimyaccount_members table
    $selectMemberQuery = "SELECT fullname FROM rssimyaccount_members WHERE associatenumber = '$uploadedfor'";
    $memberResult = pg_query($con, $selectMemberQuery);

    if ($memberResult && pg_num_rows($memberResult) > 0) {
        // Fetch $datafor from rssimyaccount_members table
        $memberData = pg_fetch_assoc($memberResult);
        $datafor = $memberData['fullname'];
    }
    // Retrieve latest submissions from the bankdetails table for the current account nature
    $selectLatestQuery_bank = "SELECT bank_account_number, bank_name, ifsc_code, account_holder_name, updated_for, updated_by, updated_on, passbook_page
                          FROM bankdetails
                          JOIN rssimyaccount_members ON bankdetails.updated_for = rssimyaccount_members.associatenumber
                          WHERE updated_for = '$uploadedfor' 
                          AND account_nature = '$accountNature'
                          AND updated_on = (SELECT MAX(updated_on) FROM bankdetails WHERE updated_for = '$uploadedfor' AND account_nature = '$accountNature')";
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

    <title>Bank Details</title>

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
    <!-- <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script> -->
    <style>
        .colored-area {
            background-color: #f2f2f2;
            padding: 10px;
        }

        .blink-text {
            color: red;
            animation: blinkAnimation 1s infinite;
        }

        @keyframes blinkAnimation {

            0%,
            50% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Bank Details</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="document.php">My Document</a></li>
                    <li class="breadcrumb-item active">Bank Details</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <!-- <?php if ($role == 'Admin') { ?>
                                <form action="" method="GET">
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
                                <div class="row align-items-center">
                                    <div class="col-6">
                                        <?php if ($uploadedfor !== null) : ?>
                                            You are viewing data for
                                            <span class="blink-text"><?= $datafor ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php } ?> -->
                            <?php if (@$transaction_id != null && @$cmdtuples == 0) { ?>
                                <div class="alert alert-danger alert-dismissible text-center" role="alert">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span>ERROR: Oops, something wasn't right.</span>
                                </div>
                            <?php } else if (@$cmdtuples == 1) { ?>
                                <div class="alert alert-success alert-dismissible text-center" role="alert">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi bi-check2-circle" style="font-size: medium;"></i>
                                    <span>Transaction id <?php echo @$transaction_id ?> has been submitted.</span>
                                </div>
                                <script>
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                </script>
                            <?php } ?>
                            <div class="container">
                                <div class="table-responsive">
                                    <table class="table" id="table-id">
                                        <thead>
                                            <tr>
                                                <th>A/c Type</th>
                                                <th>A/c Number</th>
                                                <th>Bank Name</th>
                                                <th>IFSC</th>
                                                <th>A/c Holder Name</th>
                                                <th>Passbook Page</th>
                                                <th>Last Updated</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($latestSubmissions as $accountNature => $latestSubmission) : ?>
                                                <tr>
                                                    <td><?php echo ucfirst($accountNature); ?></td>
                                                    <td><?php echo isset($latestSubmission['bank_account_number']) ? $latestSubmission['bank_account_number'] : 'N/A'; ?></td>
                                                    <td><?php echo isset($latestSubmission['bank_name']) ? $latestSubmission['bank_name'] : 'N/A'; ?></td>
                                                    <td><?php echo isset($latestSubmission['ifsc_code']) ? $latestSubmission['ifsc_code'] : 'N/A'; ?></td>
                                                    <td><?php echo isset($latestSubmission['account_holder_name']) ? $latestSubmission['account_holder_name'] : 'N/A'; ?></td>
                                                    <td>
                                                        <?php if (isset($latestSubmission['passbook_page'])) : ?>
                                                            <a href="<?php echo $latestSubmission['passbook_page']; ?>" target="_blank">Passbook</a>
                                                        <?php else : ?>
                                                            N/A
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        echo isset($latestSubmission['updated_by']) ? $latestSubmission['updated_by'] : 'N/A';
                                                        ?>
                                                        on
                                                        <?php
                                                        echo isset($latestSubmission['updated_on']) ? date('d/m/Y h:i A', strtotime($latestSubmission['updated_on'])) : 'N/A';
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- First Column - Form -->
                                <div class="container">
                                    <form action="#" method="post" enctype="multipart/form-data" id="bank_details">
                                        <input type="hidden" name="form-type" value="bank_details">
                                        <h3 class="mt-4">Add or Update Bank Details</h3>
                                        <div class="mb-3 colored-area">
                                            <p>Enter valid account number. You may include zero in the beginning of the account number (if required)</p>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="account_nature" class="form-label">Account Nature:</label>
                                                <select class="form-select" id="account_nature" name="account_nature" required>
                                                    <option selected disabled>Select your account nature</option>
                                                    <option value="reimbursement">Reimbursement Account</option>
                                                    <option value="savings">Savings Account</option>
                                                </select>
                                                <div class="form-text">Please select the nature of your account.</div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="bank_account_number" class="form-label">Bank Account Number:</label>
                                                <input class="form-control" type="text" id="bank_account_number" name="bank_account_number" placeholder="Enter your bank account number" required>
                                                <div class="form-text">Please enter your bank account number.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="bank_account_number_confirm" class="form-label">Confirm Bank Account Number:</label>
                                                <input class="form-control" type="password" id="bank_account_number_confirm" name="bank_account_number_confirm" placeholder="Re-enter your bank account number" required onpaste="return false;">
                                                <div class="form-text">Please re-enter your bank account number for confirmation.</div>
                                                <div class="form-text" id="account_number_error" style="color: red;"></div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="bank_name" class="form-label">Name of the Bank:</label>
                                                <input class="form-control" type="text" id="bank_name" name="bank_name" placeholder="Enter the name of your bank" required>
                                                <div class="form-text">Please enter the name of your bank.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="ifsc_code" class="form-label">Bank IFSC Code:</label>
                                                <input class="form-control" type="text" id="ifsc_code" name="ifsc_code" placeholder="Enter the IFSC code of your bank" required>
                                                <div class="form-text">Please enter the IFSC code of your bank.</div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="account_holder_name" class="form-label">Name of the Account Holder:</label>
                                                <input class="form-control" type="text" id="account_holder_name" name="account_holder_name" placeholder="Enter your name" required>
                                                <div class="form-text">Please enter the name of the account holder.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="passbook_page" class="form-label">First Page of Bank Account Passbook:</label>
                                                <input class="form-control" type="file" id="passbook_page" name="passbook_page" required>
                                                <div class="form-text">Please upload the first page of your bank account passbook.</div>
                                            </div>
                                        </div>
                                        <hr>
                                        <button type="submit" id="submit_button" class="btn btn-primary">Submit</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>
    <!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  
    <script>
        // Loop through each required field and append a red asterisk to its label
        document.querySelectorAll('[required]').forEach(field => {
            document.querySelector(`label[for="${field.id}"]`).innerHTML += '<span style="color: red;"> *</span>';
        });
    </script>

    <script>
        // Get references to the input fields and submit button
        const accountNumberInput = document.getElementById('bank_account_number');
        const confirmAccountNumberInput = document.getElementById('bank_account_number_confirm');
        const errorDiv = document.getElementById('account_number_error');
        const submitButton = document.getElementById('submit_button');

        // Function to validate account numbers and enable/disable submit button
        function validateAccountNumbers() {
            // Check if the values match
            if (accountNumberInput.value !== confirmAccountNumberInput.value) {
                // Display error message and disable submit button
                errorDiv.textContent = "Account number doesn't match. Please enter correctly.";
                submitButton.disabled = true;
            } else {
                // Clear error message and enable submit button
                errorDiv.textContent = "";
                submitButton.disabled = false;
            }
        }

        // Add event listeners to the inputs
        accountNumberInput.addEventListener('input', validateAccountNumbers);
        confirmAccountNumberInput.addEventListener('input', validateAccountNumbers);
    </script>
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
        document.getElementById('bank_details').addEventListener('submit', function(event) {
            // Show loading modal when form is submitted
            showLoadingModal();
        });

        // Optional: Close loading modal when the page is fully loaded
        window.addEventListener('load', function() {
            // Hide loading modal
            hideLoadingModal();
        });
    </script>
    <script>
        document.getElementById("bank_account_number_confirm").addEventListener("paste", function(event) {
            event.preventDefault();
            document.getElementById("account_number_error").innerText = "Pasting is not allowed.";
        });
    </script>

</body>

</html>