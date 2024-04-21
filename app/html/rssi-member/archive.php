<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
if (isset($_POST['form-type']) && $_POST['form-type'] === "archive") {
    // Sanitize and validate user inputs

    // Define uploaded files
    $uploadedFiles = [
        'highschool' => $_FILES['highschool'] ?? null,
        'intermediate' => $_FILES['intermediate'] ?? null,
        'graduation' => $_FILES['graduation'] ?? null,
        'post_graduation' => $_FILES['post_graduation'] ?? null,
        'additional_certificate' => $_FILES['additional_certificate'] ?? null,
        'pan_card' => $_FILES['pan_card'] ?? null,
        'aadhar_card' => $_FILES['aadhar_card'] ?? null,
        'offer_letter' => $_FILES['offer_letter'] ?? null,
        'previous_employment_information' => $_FILES['previous_employment_information'] ?? null,
    ];

    // Get other form data
    $uploadedfor = $_POST['uploadedfor'] ?? '';
    $uploadedby = $associatenumber ?? ''; // Make sure you have the $associatenumber variable defined somewhere
    $now = date('Y-m-d H:i:s');
    $transaction_id = time();

    // Get bank details
    $bank_account_number = $_POST['bank_account_number'] ?? '';
    $bank_name = $_POST['bank_name'] ?? '';
    $ifsc_code = $_POST['ifsc_code'] ?? '';
    $account_holder_name = $_POST['account_holder_name'] ?? '';

    // Upload and insert passbook page if provided
    if (!empty($_FILES['passbook_page']['name'])) {
        $passbook_page = $_FILES['passbook_page'];
        $filename = $uploadedfor . "_passbook_page_" . time();
        $parent = '1S6uLPt5G7hX4Iacgzx73gqdXsO-uKA4R';
        $doclink = uploadeToDrive($passbook_page, $parent, $filename);

        if ($doclink !== null) {
            // Insert passbook page into bankdetails table
            $insertQuery = "INSERT INTO bankdetails (bank_account_number, bank_name, ifsc_code, account_holder_name, passbook_page, updated_for, updated_by, updated_on, transaction_id)
                            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";
            $result = pg_query_params($con, $insertQuery, array($bank_account_number, $bank_name, $ifsc_code, $account_holder_name, $doclink, $uploadedfor, $uploadedby, $now, $transaction_id));
            $cmdtuples = pg_affected_rows($result);
            if (!$result) {
                // Handle insert error
            }
        }
    }

    // Insert other uploaded files into archive table
    foreach ($uploadedFiles as $inputName => $uploadedFile) {
        if (!empty($uploadedFile['name'])) {
            $filename = $uploadedfor . "_" . $inputName . "_" . time();
            $parent = '1S6uLPt5G7hX4Iacgzx73gqdXsO-uKA4R';
            $doclink = uploadeToDrive($uploadedFile, $parent, $filename);

            if ($doclink !== null) {
                $insertQuery = "INSERT INTO archive (file_name, file_path, uploaded_for, uploaded_by, uploaded_on, transaction_id)
                                VALUES ($1, $2, $3, $4, $5, $6)";
                $result = pg_query_params($con, $insertQuery, array($inputName, $doclink, $uploadedfor, $uploadedby, $now, $transaction_id));
                $cmdtuples = pg_affected_rows($result);
                if (!$result) {
                    // Handle insert error
                }
            }
        }
    }
}
// Retrieve latest submissions from the archive table
$selectLatestQuery = "SELECT DISTINCT ON (file_name) file_name, file_path, uploaded_by, uploaded_on
FROM archive 
WHERE uploaded_for = '$associatenumber' 
ORDER BY file_name, uploaded_on DESC;
";
$result = pg_query($con, $selectLatestQuery);

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $latestSubmission[$row['file_name']] = [
            'file_path' => $row['file_path'],
            'uploaded_by' => $row['uploaded_by'],
            'uploaded_on' => $row['uploaded_on']
        ];
    }
}

// Initialize $latestSubmission_bank variable to null
$latestSubmission_bank = null;
// Retrieve latest submissions from the bankdetails table
$selectLatestQuery_bank = "SELECT bank_account_number, bank_name, ifsc_code, account_holder_name, updated_for, updated_by, updated_on, passbook_page
                      FROM bankdetails 
                      WHERE updated_for = '$associatenumber' 
                      AND updated_on = (SELECT MAX(updated_on) FROM bankdetails WHERE updated_for = '$associatenumber')";
$result = pg_query($con, $selectLatestQuery_bank);

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
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

    <title>Document Archive</title>

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
    <!-- <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script> -->
    <style>
        .colored-area {
            background-color: #f2f2f2;
            padding: 10px;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Document Archive</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">My Document</li>
                    <li class="breadcrumb-item active">Document Archive</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
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
                                <form action="#" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="form-type" value="archive">
                                    <input type="hidden" name="uploadedfor" value="<?php echo $associatenumber ?>">
                                    <h3 class="mt-4">Upload Documents</h3>
                                    <div class="mb-3 colored-area">
                                        <p>To save your changes, please submit the form. Once submitted, the updated information will be displayed here for your reference.</p>
                                    </div>
                                    <br>
                                    <!-- Highschool Marksheet -->
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="highschool" class="form-label">Highschool Marksheet:</label>
                                        </div>
                                        <div class="col-md-4">
                                            <input class="form-control" type="file" id="highschool" name="highschool">
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['highschool'])) : ?>
                                                <a href="<?php echo $latestSubmission['highschool']['file_path']; ?>" target="_blank">Document</a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['highschool'])) : ?>
                                                <small>Last updated by <?php echo $latestSubmission['highschool']['uploaded_by']; ?> on <?php echo $latestSubmission['highschool']['uploaded_on']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Intermediate Marksheet -->
                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <label for="intermediate" class="form-label">Intermediate Marksheet:</label>
                                        </div>
                                        <div class="col-md-4">
                                            <input class="form-control" type="file" id="intermediate" name="intermediate">
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['intermediate'])) : ?>
                                                <a href="<?php echo $latestSubmission['intermediate']['file_path']; ?>" target="_blank">Document</a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['intermediate'])) : ?>
                                                <small>Last updated by <?php echo $latestSubmission['intermediate']['uploaded_by']; ?> on <?php echo $latestSubmission['intermediate']['uploaded_on']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Graduation Marksheet -->
                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <label for="graduation" class="form-label">Graduation Marksheet:</label>
                                        </div>
                                        <div class="col-md-4">
                                            <input class="form-control" type="file" id="graduation" name="graduation">
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['graduation'])) : ?>
                                                <a href="<?php echo $latestSubmission['graduation']['file_path']; ?>" target="_blank">Document</a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['graduation'])) : ?>
                                                <small>Last updated by <?php echo $latestSubmission['graduation']['uploaded_by']; ?> on <?php echo $latestSubmission['graduation']['uploaded_on']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Post-Graduation Marksheet -->
                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <label for="post_graduation" class="form-label">Post-Graduation Marksheet:</label>
                                        </div>
                                        <div class="col-md-4">
                                            <input class="form-control" type="file" id="post_graduation" name="post_graduation">
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['post_graduation'])) : ?>
                                                <a href="<?php echo $latestSubmission['post_graduation']['file_path']; ?>" target="_blank">Document</a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['post_graduation'])) : ?>
                                                <small>Last updated by <?php echo $latestSubmission['post_graduation']['uploaded_by']; ?> on <?php echo $latestSubmission['post_graduation']['uploaded_on']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <!-- Additional training or course Certificate -->
                                        <div class="col-md-4">
                                            <label for="additional_certificate" class="form-label">Additional training or course Certificate:</label>
                                        </div>
                                        <div class="col-md-4">
                                            <input class="form-control" type="file" id="additional_certificate" name="additional_certificate">
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['additional_certificate'])) : ?>
                                                <a href="<?php echo $latestSubmission['additional_certificate']['file_path']; ?>" target="_blank">Document</a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['additional_certificate'])) : ?>
                                                <small>Last updated by <?php echo $latestSubmission['additional_certificate']['uploaded_by']; ?> on <?php echo $latestSubmission['additional_certificate']['uploaded_on']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <!-- Previous employment information -->
                                        <div class="col-md-4">
                                            <label for="previous_employment_information" class="form-label">Previous employment information:</label>
                                        </div>
                                        <div class="col-md-4">
                                            <input class="form-control" type="file" id="previous_employment_information" name="previous_employment_information">
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['previous_employment_information'])) : ?>
                                                <a href="<?php echo $latestSubmission['previous_employment_information']['file_path']; ?>" target="_blank">Document</a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['previous_employment_information'])) : ?>
                                                <small>Last updated by <?php echo $latestSubmission['previous_employment_information']['uploaded_by']; ?> on <?php echo $latestSubmission['previous_employment_information']['uploaded_on']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <!-- PAN Card -->
                                        <div class="col-md-4">
                                            <label for="pan_card" class="form-label">PAN Card:</label>
                                        </div>
                                        <div class="col-md-4">
                                            <input class="form-control" type="file" id="pan_card" name="pan_card">
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['pan_card'])) : ?>
                                                <a href="<?php echo $latestSubmission['pan_card']['file_path']; ?>" target="_blank">Document</a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['pan_card'])) : ?>
                                                <small>Last updated by <?php echo $latestSubmission['pan_card']['uploaded_by']; ?> on <?php echo $latestSubmission['pan_card']['uploaded_on']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <!-- Aadhar Card -->
                                        <div class="col-md-4">
                                            <label for="aadhar_card" class="form-label">Aadhar Card:</label>
                                        </div>
                                        <div class="col-md-4">
                                            <input class="form-control" type="file" id="aadhar_card" name="aadhar_card">
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['aadhar_card'])) : ?>
                                                <a href="<?php echo $latestSubmission['aadhar_card']['file_path']; ?>" target="_blank">Document</a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['aadhar_card'])) : ?>
                                                <small>Last updated by <?php echo $latestSubmission['aadhar_card']['uploaded_by']; ?> on <?php echo $latestSubmission['aadhar_card']['uploaded_on']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <!-- Offer letter -->
                                        <div class="col-md-4">
                                            <label for="offer_letter" class="form-label">Offer letter:</label>
                                        </div>
                                        <div class="col-md-4">
                                            <input class="form-control" type="file" id="offer_letter" name="offer_letter">
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['offer_letter'])) : ?>
                                                <a href="<?php echo $latestSubmission['offer_letter']['file_path']; ?>" target="_blank">Document</a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <?php if (isset($latestSubmission['offer_letter'])) : ?>
                                                <small>Last updated by <?php echo $latestSubmission['offer_letter']['uploaded_by']; ?> on <?php echo $latestSubmission['offer_letter']['uploaded_on']; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <hr>
                                    <div class="row">
                                        <!-- First Column -->
                                        <div class="col-md-6">
                                            <h3 class="mt-4">Bank Account Details</h3>
                                            <div class="mb-3 colored-area" onclick="toggleCheckbox()">
                                                <input type="checkbox" id="enable_bank_details" onclick="handleCheckboxClick(event)">
                                                <label for="enable_bank_details" onclick="toggleCheckbox()">Update Bank Details</label>
                                            </div>

                                            <div class="mb-3">
                                                <label for="bank_account_number" class="form-label">Bank Account Number:</label>
                                                <input class="form-control" type="text" id="bank_account_number" name="bank_account_number" placeholder="Enter your bank account number" required>
                                                <div class="form-text">Please enter your bank account number.</div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="bank_account_number_confirm" class="form-label">Confirm Bank Account Number:</label>
                                                <input class="form-control" type="password" id="bank_account_number_confirm" name="bank_account_number_confirm" placeholder="Re-enter your bank account number" required>

                                                <div class="form-text">Please re-enter your bank account number for confirmation.</div>
                                                <div class="form-text" id="account_number_error" style="color: red;"></div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="bank_name" class="form-label">Name of the Bank:</label>
                                                <input class="form-control" type="text" id="bank_name" name="bank_name" placeholder="Enter the name of your bank" required>
                                                <div class="form-text">Please enter the name of your bank.</div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="ifsc_code" class="form-label">Bank IFSC Code:</label>
                                                <input class="form-control" type="text" id="ifsc_code" name="ifsc_code" placeholder="Enter the IFSC code of your bank" required>
                                                <div class="form-text">Please enter the IFSC code of your bank.</div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="account_holder_name" class="form-label">Name of the account holder:</label>
                                                <input class="form-control" type="text" id="account_holder_name" name="account_holder_name" placeholder="Enter your name" required>
                                                <div class="form-text">Please enter the name of the account holder.</div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="passbook_page" class="form-label">First Page of Bank Account Passbook:</label>
                                                <input class="form-control" type="file" id="passbook_page" name="passbook_page" required>
                                                <div class="form-text">Please upload the first page of your bank account passbook.</div>
                                            </div>
                                        </div>


                                        <!-- Divider Line -->
                                        <div class="col-md-1 d-flex align-items-center justify-content-center">
                                            <hr class="my-4">
                                        </div>

                                        <!-- Second Column -->
                                        <div class="col-md-5">
                                            <div>
                                                <h3 class="mt-4">Your Current Bank Account Details</h3>
                                                <?php if ($latestSubmission_bank !== null) : ?>
                                                    <p class="mb-1">Bank Account Number: <?php echo isset($latestSubmission_bank['bank_account_number']) ? $latestSubmission_bank['bank_account_number'] : 'N/A'; ?></p>
                                                    <p class="mb-1">Name of the Bank: <?php echo isset($latestSubmission_bank['bank_name']) ? $latestSubmission_bank['bank_name'] : 'N/A'; ?></p>
                                                    <p class="mb-1">IFSC Code: <?php echo isset($latestSubmission_bank['ifsc_code']) ? $latestSubmission_bank['ifsc_code'] : 'N/A'; ?></p>
                                                    <p class="mb-1">Account Holder Name: <?php echo isset($latestSubmission_bank['account_holder_name']) ? $latestSubmission_bank['account_holder_name'] : 'N/A'; ?></p>
                                                    <?php if (isset($latestSubmission_bank['passbook_page'])) : ?>
                                                        <p class="mb-1"><a href="<?php echo $latestSubmission_bank['passbook_page']; ?>" target="_blank">First Page of Bank Account Passbook</a></p>
                                                    <?php endif; ?>
                                                    <br>
                                                    <p>(Last updated by <?php echo isset($latestSubmission_bank['updated_by']) ? $latestSubmission_bank['updated_by'] : 'N/A'; ?> on <?php echo isset($latestSubmission_bank['updated_on']) ? $latestSubmission_bank['updated_on'] : 'N/A'; ?>)</p>
                                                <?php else : ?>
                                                    <!-- Handle case when bank details are not available -->
                                                    <p>No bank details available.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <button type="submit" id="submit_button" class="btn btn-primary">Submit</button>
                                </form>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Get the checkbox element
            const enableBankDetailsCheckbox = document.getElementById('enable_bank_details');

            // Get all input fields for bank details
            const bankDetailsInputs = document.querySelectorAll('#bank_account_number, #bank_account_number_confirm, #bank_name, #ifsc_code, #account_holder_name, #passbook_page');

            // Disable all bank details inputs initially
            bankDetailsInputs.forEach(input => {
                input.disabled = true;
            });

            // Get reference to submit button
            const submitButton = document.getElementById('submit_button');

            // Add event listener to the checkbox
            enableBankDetailsCheckbox.addEventListener('change', function() {
                // Toggle disabled attribute of bank details inputs based on checkbox state
                bankDetailsInputs.forEach(input => {
                    input.disabled = !this.checked;
                });

                // Enable submit button if checkbox is unchecked
                if (!this.checked) {
                    submitButton.disabled = false;
                    // Clear error message
                    errorDiv.textContent = "";
                }

                // Reset the values of specified fields if the checkbox is unchecked
                if (!this.checked) {
                    const fieldsToReset = ['#bank_account_number', '#bank_account_number_confirm', '#bank_name', '#ifsc_code', '#account_holder_name', '#passbook_page'];
                    fieldsToReset.forEach(field => {
                        document.querySelector(field).value = ''; // Clear the value of each specified field
                    });
                }
            });
        });

        function toggleCheckbox() {
            const checkbox = document.getElementById('enable_bank_details');
            checkbox.checked = !checkbox.checked;

            // Trigger the change event manually to update input fields
            checkbox.dispatchEvent(new Event('change'));
        }

        function handleCheckboxClick(event) {
            // Prevent event propagation to avoid double event triggering
            event.stopPropagation();
        }
    </script>

    <script>
        // Get references to the input fields
        const accountNumberInput = document.getElementById('bank_account_number');
        const confirmAccountNumberInput = document.getElementById('bank_account_number_confirm');
        const errorDiv = document.getElementById('account_number_error');
        const submitButton = document.getElementById('submit_button');

        // Function to enable/disable submit button based on account number validation
        function toggleSubmitButton() {
            if (accountNumberInput.value !== confirmAccountNumberInput.value) {
                // If account numbers don't match, disable submit button
                submitButton.disabled = true;
            } else {
                // If account numbers match, enable submit button
                submitButton.disabled = false;
            }
        }
        // Add event listeners to the inputs
        accountNumberInput.addEventListener('input', function() {
            toggleSubmitButton();
        });

        // Add event listener to the confirm account number input
        confirmAccountNumberInput.addEventListener('input', function() {
            // Check if the values match
            if (accountNumberInput.value !== confirmAccountNumberInput.value) {
                // Display error message
                errorDiv.textContent = "Account number doesn't match. Please enter correctly.";
                // Disable submit button
                submitButton.disabled = true;
            } else {
                // Clear error message if values match
                errorDiv.textContent = "";
                // Enable submit button
                toggleSubmitButton();
            }
        });
    </script>
    <script>
        // Loop through each required field and append a red asterisk to its label
        document.querySelectorAll('[required]').forEach(field => {
            document.querySelector(`label[for="${field.id}"]`).innerHTML += '<span style="color: red;"> *</span>';
        });
    </script>
</body>

</html>