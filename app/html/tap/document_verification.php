<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_tap.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("tid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
$uploadedfor = !empty($id) ? $id : $application_number ?? '';

$selectMemberQuery = "SELECT applicant_name,education_qualification,application_status,tech_interview_schedule,identity_verification,email FROM signup WHERE application_number='$application_number'";
$memberResult = pg_query($con, $selectMemberQuery);

if ($memberResult && pg_num_rows($memberResult) > 0) {
    // Fetch $datafor from rssimyaccount_members table
    $memberData = pg_fetch_assoc($memberResult);
    $datafor = $memberData['applicant_name'];
    $education_qualification = $memberData['education_qualification'];
    $uploadedfor_email = $memberData['email'];
    $isFormDisabled = ($memberData["identity_verification"] !== 'Approved' ||
        (!empty($memberData["tech_interview_schedule"]) && $memberData["application_status"] === 'No-Show'))
        ? 'disabled'
        : null;
}


if (isset($_POST['form-type']) && $_POST['form-type'] === "archive") {
    $uploadedFiles = [
        'highschool' => $_FILES['highschool'] ?? null,
        'intermediate' => $_FILES['intermediate'] ?? null,
        'graduation' => $_FILES['graduation'] ?? null,
        'post_graduation' => $_FILES['post_graduation'] ?? null,
        'additional_certificate' => $_FILES['additional_certificate'] ?? null,
        'pan_card' => $_FILES['pan_card'] ?? null,
        'aadhar_card' => $_FILES['aadhar_card'] ?? null,
        'previous_employment_information' => $_FILES['previous_employment_information'] ?? null,
        'additional_doc' => $_FILES['additional_doc'] ?? null,
    ];

    $uploadedfor = $application_number ?? '';
    $uploadedby = $application_number ?? '';
    $now = date('Y-m-d H:i:s');
    $transaction_id = time();

    $alreadyVerified = [];
    $successfullyUploaded = [];

    foreach ($uploadedFiles as $inputName => $uploadedFile) {
        if (!empty($uploadedFile['name'])) {
            // Check if the file has already been verified
            $checkQuery = "SELECT verification_status FROM archive WHERE file_name = $1 AND uploaded_for = $2 ORDER BY uploaded_on DESC LIMIT 1";
            $checkResult = pg_query_params($con, $checkQuery, [$inputName, $uploadedfor]);

            if ($checkResult && pg_num_rows($checkResult) > 0) {
                $statusRow = pg_fetch_assoc($checkResult);
                if ($statusRow['verification_status'] === 'Verified') {
                    $alreadyVerified[] = $inputName;
                    continue;
                }
            }

            // Proceed to upload the file if not verified
            $filename = $uploadedfor . "_" . $inputName . "_" . time();
            $parent = '1PLipvcQ5qtyEHvmH9cRVM6usy2wuwDwq';
            $doclink = uploadeToDrive($uploadedFile, $parent, $filename);

            if ($doclink !== null) {
                $insertQuery = "INSERT INTO archive (file_name, file_path, uploaded_for, uploaded_by, uploaded_on, transaction_id)
                                VALUES ($1, $2, $3, $4, $5, $6)
                                RETURNING doc_id"; // Add RETURNING clause
                $result = pg_query_params($con, $insertQuery, [$inputName, $doclink, $uploadedfor, $uploadedby, $now, $transaction_id]);

                if ($result && pg_affected_rows($result) > 0) {
                    $successfullyUploaded[] = $inputName;
                }

                if ($result) {
                    // Fetch the returned doc_id
                    $row = pg_fetch_assoc($result);
                    $doc_id = $row['doc_id'];
                    // Add uploaded file details to the array
                    $uploadedFilesData[] = [
                        "file_name" => $inputName,
                        "transaction_id" => $transaction_id,
                        "uploaded_on" => date("d/m/Y g:i a", strtotime($now)),
                        "doc_id" => $doc_id, // Add the returned doc_id
                    ];
                }
            }
        }
    }

    // Prepare a combined alert message
    $message = '';
    if (!empty($alreadyVerified)) {
        $message .= "The following files are already verified and cannot be re-submitted: " . implode(", ", $alreadyVerified) . ". ";
    }
    if (!empty($successfullyUploaded)) {
        $message .= "The following files have been uploaded successfully: " . implode(", ", $successfullyUploaded) . ".";
    }
    if (empty($message)) {
        $message = "No files were uploaded.";
    }

    // Send a single email with all uploaded file details
    if (!empty($uploadedfor_email) && !empty($uploadedFilesData)) {
        sendEmail("doc_upload", [
            "datafor" => $datafor,
            "uploaded_files" => $uploadedFilesData, // Pass all uploaded file details
        ], $uploadedfor_email);
    }

    // Output the JavaScript alert and redirection with form clearing logic
    echo '<script>
        alert("' . $message . '");
        // Clear form fields after submission and prevent resubmission on back navigation
        window.history.replaceState(null, null, window.location.href);
        window.location.href = "document_verification.php";
    </script>';
    exit;
}

// Map file names to their corresponding actual names
$fileNamesMapping = array(
    "aadhar_card" => "Aadhar Card",
    "additional_certificate" => "Additional Certificate",
    "additional_doc" => "Additional Document",
    "graduation" => "Graduation",
    "highschool" => "High School",
    "intermediate" => "Intermediate",
    // "offer_letter" => "Offer Letter",
    "pan_card" => "PAN Card",
    "post_graduation" => "Post Graduation",
    "previous_employment_information" => "Previous employment information"
);
// Construct SQL Query
$selectLatestQuery = "";
foreach ($fileNamesMapping as $dbName => $humanName) {
    $selectLatestQuery .= "(SELECT ";
    if ($dbName === 'additional_certificate' || $dbName === 'previous_employment_information') {
        $selectLatestQuery .= "file_name, file_path, uploaded_by, uploaded_on, transaction_id, verification_status, field_status,certificate_name,remarks,reviewed_by,reviewed_on ";
    } else {
        $selectLatestQuery .= "DISTINCT ON (file_name) file_name, file_path, uploaded_by, uploaded_on, transaction_id, verification_status, field_status,certificate_name,remarks,reviewed_by,reviewed_on ";
    }
    $selectLatestQuery .= "FROM archive WHERE uploaded_for = '$uploadedfor' AND file_name = '$dbName' ";
    if (!($dbName === 'additional_certificate' || $dbName === 'previous_employment_information')) {
        $selectLatestQuery .= "ORDER BY file_name, uploaded_on DESC ";
    } else {
        $selectLatestQuery .= "ORDER BY uploaded_on DESC ";
    }
    $selectLatestQuery .= ")";
    if ($dbName !== array_key_last($fileNamesMapping)) {
        $selectLatestQuery .= " UNION ALL ";
    }
}

// Execute Query
$result = pg_query($con, $selectLatestQuery);

// Initialize $latestSubmission as an empty array
$latestSubmission = array();

// Check for query result
if ($result) {
    // Fetch Data
    while ($row = pg_fetch_assoc($result)) {
        // Process each row of data
        $latestSubmission[] = array(
            'file_path' => $row['file_path'],
            'uploaded_by' => $row['uploaded_by'],
            'uploaded_on' => $row['uploaded_on'],
            'transaction_id' => $row['transaction_id'],
            'file_name' => isset($fileNamesMapping[$row['file_name']]) ? $fileNamesMapping[$row['file_name']] : $row['file_name'],
            'verification_status' => $row['verification_status'],
            'field_status' => $row['field_status'],
            'certificate_name' => $row['certificate_name'],
            'remarks' => $row['remarks'],
            'reviewed_by' => $row['reviewed_by'],
            'reviewed_on' => $row['reviewed_on']
        );
    }
} else {
    // Handle Error if Query Fails
    echo "Error: Unable to retrieve latest submissions.";
}
// Initialize an associative array to hold field_status values for each file
$fieldStatusValues = array();

// Iterate over $latestSubmission array to collect field_status values for each file
foreach ($latestSubmission as $submission) {
    $fieldStatusValues[$submission['file_name']] = $submission['field_status'];
}

// Now $fieldStatusValues array contains field_status values for each file

?>
<?php

// Initialize an array to hold the mapping of education qualifications to required fields
$requiredFields = [
    "11" => ["highschool"],
    "12" => ["highschool", "intermediate"],
    "Bachelor" => ["highschool", "intermediate", "graduation"],
    "Master" => ["highschool", "intermediate", "graduation", "post_graduation"],
    "Doctorate" => ["highschool", "intermediate", "graduation", "post_graduation"]
];

// Get the corresponding required fields based on the value of $eduq
$required = [];
foreach ($requiredFields as $qualification => $fields) {
    if (!empty($education_qualification) && strpos($education_qualification, $qualification) !== false) {
        $required = array_merge($required, $fields);
    }
}

// Function to generate the required attribute for input fields
function generateRequiredAttribute($field)
{
    return in_array($field, $GLOBALS['required']) ? 'required' : '';
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

    <title>Document Verification</title>

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
            <h1>Document Verification</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active">Document Verification</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container">
                                <form action="#" method="post" enctype="multipart/form-data" id="archive">
                                    <fieldset <?php echo $isFormDisabled; ?>>
                                        <input type="hidden" name="form-type" value="archive">
                                        <h3 class="mt-4">Upload Documents</h3>
                                        <div class="mb-3 colored-area">
                                            <p>To save your changes, please submit the form. Once submitted, the updated
                                                information will be displayed here for your reference.</p>
                                        </div>
                                        <br>
                                        <!-- Highschool Marksheet -->
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label for="highschool" class="form-label">Highschool Marksheet:</label>
                                            </div>
                                            <div class="col-md-4">
                                                <input class="form-control" type="file" id="highschool" name="highschool" <?= generateRequiredAttribute("highschool") ?> <?php echo isset($fieldStatusValues['High School']) ? $fieldStatusValues['High School'] : ''; ?>>
                                            </div>
                                        </div>

                                        <!-- Intermediate Marksheet -->
                                        <div class="row mt-2">
                                            <div class="col-md-4">
                                                <label for="intermediate" class="form-label">Intermediate Marksheet:</label>
                                            </div>
                                            <div class="col-md-4">
                                                <input class="form-control" type="file" id="intermediate" name="intermediate" <?= generateRequiredAttribute("intermediate") ?> <?php echo isset($fieldStatusValues['Intermediate']) ? $fieldStatusValues['Intermediate'] : ''; ?>>
                                            </div>
                                        </div>

                                        <!-- Graduation Marksheet -->
                                        <div class="row mt-2">
                                            <div class="col-md-4">
                                                <label for="graduation" class="form-label">Graduation Marksheet:</label>
                                            </div>
                                            <div class="col-md-4">
                                                <input class="form-control" type="file" id="graduation" name="graduation" <?= generateRequiredAttribute("graduation") ?> <?php echo isset($fieldStatusValues['Graduation']) ? $fieldStatusValues['Graduation'] : ''; ?>>
                                            </div>
                                        </div>

                                        <!-- Post-Graduation Marksheet -->
                                        <div class="row mt-2">
                                            <div class="col-md-4">
                                                <label for="post_graduation" class="form-label">Post-Graduation Marksheet:</label>
                                            </div>
                                            <div class="col-md-4">
                                                <input class="form-control" type="file" id="post_graduation" name="post_graduation" <?= generateRequiredAttribute("post_graduation") ?> <?php echo isset($fieldStatusValues['Post Graduation']) ? $fieldStatusValues['Post Graduation'] : ''; ?>>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <!-- Additional training or course Certificate -->
                                            <div class="col-md-4">
                                                <label for="additional_certificate" class="form-label">Additional training
                                                    or course Certificate:</label>
                                            </div>
                                            <div class="col-md-4">
                                                <input class="form-control" type="file" id="additional_certificate" name="additional_certificate" <?php echo isset($fieldStatusValues['Additional Certificate']) ? $fieldStatusValues['Additional Certificate'] : ''; ?>>
                                            </div>
                                            <!-- Certificate Name Input Field -->
                                            <div class="col-md-4">
                                                <input class="form-control" type="text" id="additional_certificate_name" name="additional_certificate_name" placeholder="Training/Certificate Name">
                                            </div>
                                        </div>

                                        <div class="row mt-2">
                                            <!-- Previous employment information -->
                                            <div class="col-md-4">
                                                <label for="previous_employment_information" class="form-label">Previous
                                                    employment information:</label>
                                            </div>
                                            <div class="col-md-4">
                                                <input class="form-control" type="file" id="previous_employment_information" name="previous_employment_information" <?php echo isset($fieldStatusValues['Previous employment information']) ? $fieldStatusValues['Previous employment information'] : ''; ?>>
                                            </div>
                                        </div>

                                        <div class="row mt-2">
                                            <!-- PAN Card -->
                                            <div class="col-md-4">
                                                <label for="pan_card" class="form-label">PAN Card:</label>
                                            </div>
                                            <div class="col-md-4">
                                                <input class="form-control" type="file" id="pan_card" name="pan_card" <?php echo isset($fieldStatusValues['PAN Card']) ? $fieldStatusValues['PAN Card'] : ''; ?>>
                                            </div>
                                        </div>

                                        <div class="row mt-2">
                                            <!-- Aadhar Card -->
                                            <div class="col-md-4">
                                                <label for="aadhar_card" class="form-label">Aadhar Card:</label>
                                            </div>
                                            <div class="col-md-4">
                                                <input class="form-control" type="file" id="aadhar_card" name="aadhar_card" <?php echo isset($fieldStatusValues['Aadhar Card']) ? $fieldStatusValues['Aadhar Card'] : ''; ?> required>
                                            </div>
                                        </div>

                                        <!-- <div class="row mt-2">
                                        <div class="col-md-4">
                                            <label for="offer_letter" class="form-label">Offer Letter (Signed Copy):</label>
                                        </div>
                                        <div class="col-md-4">
                                            <input class="form-control" type="file" id="offer_letter" name="offer_letter" <?php echo isset($fieldStatusValues['Offer Letter']) ? $fieldStatusValues['Offer Letter'] : ''; ?> required>
                                        </div>
                                    </div> -->

                                        <div class="row mt-2">
                                            <!-- Additional doc -->
                                            <div class="col-md-4">
                                                <label for="additional_doc" class="form-label">Additional document:</label>
                                            </div>
                                            <div class="col-md-4">
                                                <input class="form-control" type="file" id="additional_doc" name="additional_doc" <?php echo isset($fieldStatusValues['Additional Document']) ? $fieldStatusValues['Additional Document'] : ''; ?>>
                                            </div>
                                            <!-- Certificate Name Input Field -->
                                            <div class="col-md-4">
                                                <select class="form-select" id="additional_doc_name" name="additional_doc_name">
                                                    <option value="">Select Document Type</option>
                                                    <option value="caste_certificate">Caste Certificate</option>
                                                    <option value="college_id">College ID</option>
                                                    <!-- Add more options as needed -->
                                                </select>
                                            </div>

                                        </div>
                                        <hr>
                                        <button type="submit" id="submit_button" class="btn btn-primary">Submit</button>
                                    </fieldset>
                                </form>


                                <h2 class="mt-4">Document List</h2>
                                <hr>
                                <!-- Display the data in a tabular format using Bootstrap -->
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>File Name</th>
                                                <!-- <th>File</th> -->
                                                <th>Uploaded By</th>
                                                <th>Uploaded On</th>
                                                <!-- <th>Transaction ID</th> -->
                                                <th>Verification Status</th>
                                                <th>Remarks</th>
                                                <th>Reviewed By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($latestSubmission as $submission) { ?>
                                                <tr>
                                                    <td><?php echo $submission['file_name']; ?><?php echo isset($submission['certificate_name']) ? ' - ' . $submission['certificate_name'] : ''; ?>
                                                    </td>
                                                    <!-- <td><a href="<?php echo $submission['file_path']; ?>" target="_blank" title="<?php echo $submission['file_name']; ?>_<?php echo $submission['transaction_id']; ?>">Document</a></td> -->
                                                    <td><?php echo $submission['uploaded_by']; ?></td>
                                                    <td><?php echo date('d/m/Y g:i a', strtotime($submission['uploaded_on'])); ?>
                                                    </td>
                                                    <!-- <td><?php echo $submission['transaction_id']; ?></td> -->
                                                    <td><?php echo $submission['verification_status']; ?></td>
                                                    <td><?php echo $submission['remarks']; ?></td>
                                                    <td><?php echo !empty($submission['reviewed_by']) ? "{$submission['reviewed_by']} on " . date('d/m/Y g:i a', strtotime($submission['reviewed_on'])) : ''; ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>
    </main><!-- End #main -->
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

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <!-- Add this script in your HTML file -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function toggleRequiredAttribute(fileInput, nameInput) {
                if (fileInput.files.length > 0) {
                    nameInput.setAttribute('required', 'required');
                } else {
                    nameInput.removeAttribute('required');
                }
            }

            // Select your file input elements
            var additionalCertificateInput = document.getElementById('additional_certificate');
            var additionalDocumentInput = document.getElementById('additional_doc');

            // Select your corresponding certificate name inputs
            var additionalCertificateNameInput = document.getElementById('additional_certificate_name');
            var additionalDocumentNameInput = document.getElementById('additional_doc_name');

            // Attach event listeners to the file inputs
            additionalCertificateInput.addEventListener('change', function() {
                toggleRequiredAttribute(additionalCertificateInput, additionalCertificateNameInput);
            });

            additionalDocumentInput.addEventListener('change', function() {
                toggleRequiredAttribute(additionalDocumentInput, additionalDocumentNameInput);
            });
        });
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
        document.getElementById('archive').addEventListener('submit', function(event) {
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
        // Loop through each required field and append a red asterisk to its label
        document.querySelectorAll('[required]').forEach(field => {
            document.querySelector(`label[for="${field.id}"]`).innerHTML += '<span style="color: red;"> *</span>';
        });
    </script>

</body>

</html>