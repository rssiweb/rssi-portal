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

// SQL query to fetch current data
$sql = "SELECT * FROM signup WHERE application_number='$application_number'";
$result = pg_query($con, $sql);
$resultArr = pg_fetch_all($result);
// Check if there are any results
if ($resultArr && count($resultArr) > 0) {
    // Accessing specific column values from the first result (assuming there is only one row)
    $applicant_email = $resultArr[0]['email'];
    $applicant_name = $resultArr[0]['applicant_name'];
    $application_number = $resultArr[0]['application_number'];
}
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["form-type"]) && $_POST["form-type"] == "verification") {
    // Sanitize and fetch form data
    $identifier = !empty($_POST['identifier']) ? pg_escape_string($con, $_POST['identifier']) : null;
    $identifier_number = !empty($_POST['identifierNumber']) ? pg_escape_string($con, $_POST['identifierNumber']) : null;
    $uploadedFile_identifier = $_FILES['supportingDocument'];
    $application_status = 'Identity verification document submitted';

    // Check current identity verification status
    $status_query = "SELECT identity_verification FROM signup WHERE application_number = '$application_number'";
    $status_result = pg_query($con, $status_query);

    if ($status_result && pg_num_rows($status_result) > 0) {
        $status_row = pg_fetch_assoc($status_result);
        if ($status_row['identity_verification'] === 'Approved') {
            // Show a message to the user and stop form processing
            echo '<script>
                    alert("Identity verification is already approved. You cannot submit the form again.");
                    window.location.href = "identity_verification.php";  // Redirect back to the form
                  </script>';
            exit; // Prevent further processing
        }
    } else {
        // Handle the case where the application number doesn't exist
        echo '<script>
                alert("Error: Invalid application number.");
                window.location.href = "identity_verification.php";
              </script>';
        exit; // Prevent further processing
    }

    // Handle file upload
    if (empty($_FILES['supportingDocument']['name'])) {
        $doclink_identifier = null;
    } else {
        $filename_identifier = "$identifier" . "_" . "$application_number" . "_" . time();
        $parent_identifier = '1Y2cwzAYoU0__F2i2-4XnZDz9uH-xDcTn';
        $doclink_identifier = uploadeToDrive($uploadedFile_identifier, $parent_identifier, $filename_identifier);
    }

    // Build the update query dynamically
    $update_fields = [];
    if ($identifier) $update_fields[] = "identifier = '$identifier'";
    if ($identifier_number) $update_fields[] = "identifier_number = '$identifier_number'";
    if ($doclink_identifier) $update_fields[] = "supporting_document = '$doclink_identifier'";
    if ($application_status) $update_fields[] = "application_status = '$application_status'";

    // If there are fields to update, execute the update query
    if (count($update_fields) > 0) {
        $update_query = "UPDATE signup SET " . implode(", ", $update_fields) . " WHERE application_number = '$application_number'";
        $result = pg_query($con, $update_query);
        $cmdtuples = pg_affected_rows($result);
    }

    // Send email if update was successful
    if ($cmdtuples == 1) {
        sendEmail("tap_identity_document_submitted", array(
            "application_number" => $application_number,
            "applicant_name" => $applicant_name,
        ), 'info@rssi.in');
    }

    // If update was successful or failed, show an alert
    if ($cmdtuples == 1) {
        echo '<script>
                alert("Verification record has been submitted successfully!");
                window.location.href = "identity_verification.php";
              </script>';
    } else {
        echo '<script>
                alert("Error: We encountered an error while updating the record. Please try again.");
              </script>';
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

    <title>Identity Verification</title>

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
    <style>
        .milestones {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #f0f0f0;
            border-radius: 10px;
            margin: 20px auto;
        }

        .milestone {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #ccc;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            position: relative;
        }

        .milestone.active {
            background-color: #4CAF50;
        }

        .milestone::after {
            content: '';
            width: 100%;
            height: 4px;
            background-color: #ccc;
            position: absolute;
            top: 50%;
            left: 100%;
            z-index: -1;
        }

        .milestone.active::after {
            background-color: #4CAF50;
            width: calc(100% - 50px);
        }

        .step-label {
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Identity Verification</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Identity Verification</li>
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

                            <?php foreach ($resultArr as $array) { ?>
                                <?php
                                $isFormDisabled = (empty($array["supporting_document"]) ||
                                    ($array["identity_verification"] === 'Rejected' && !empty($array["supporting_document"])))
                                    ? null
                                    : 'disabled';
                                ?>
                                <div class="container">
                                    <form name="verification" id="verification" action="#" method="post" enctype="multipart/form-data">
                                        <fieldset <?php echo $isFormDisabled; ?>>
                                            <input type="hidden" name="form-type" value="verification">
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <tbody>
                                                        <tr>
                                                            <td><label for="identifier">Choose any National Identifier from the below list.</label></td>
                                                            <td>
                                                                <select class="form-select" id="identifier" name="identifier" required>
                                                                    <option disabled <?php echo empty($array['identifier']) ? 'selected' : ''; ?>>-- Select an Identifier --</option>
                                                                    <?php
                                                                    $options = [
                                                                        "Aadhaar" => "Aadhaar",
                                                                        // "PAN (Permanent Account Number)" => "PAN (Permanent Account Number)",
                                                                        // "Voter ID" => "Voter ID",
                                                                        // "Passport" => "Passport",
                                                                        // "Ration Card" => "Ration Card",
                                                                        // "Driving License" => "Driving License",
                                                                        // "National Population Register (NPR) Number" => "National Population Register (NPR) Number",
                                                                        // "PR Card (Person of Indian Origin)" => "PR Card (Person of Indian Origin)"
                                                                    ];
                                                                    foreach ($options as $value => $label) {
                                                                        $selected = ($array['identifier'] ?? '') === $value ? 'selected' : '';
                                                                        echo "<option value=\"" . htmlspecialchars($value) . "\" $selected>" . htmlspecialchars($label) . "</option>";
                                                                    }
                                                                    ?>
                                                                </select>

                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td><label for="identifierNumber">Enter your National Identifier Number.</label></td>
                                                            <td>

                                                                <input type="text" class="form-control" id="identifierNumber" name="identifierNumber" placeholder="Enter your identifier number" value="<?php echo ($array["identifier_number"]); ?>" required>
                                                                <small class="form-text text-muted">
                                                                    In order for your application to be processed, all required documents must be submitted.
                                                                </small>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td><label for="supportingDocument">Upload a copy of your valid identification.</label></td>
                                                            <td>

                                                                <input type="file" class="form-control" id="supportingDocument" name="supportingDocument" accept=".pdf, image/*" required>
                                                                <?php if (!empty($array['supporting_document'])): ?>
                                                                    <div class="photo-box mt-2" id="candidatePhotoContainer" style="width: 400px; height: 250px;">
                                                                        <?php
                                                                        // Extract file ID using regular expression
                                                                        preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $array['supporting_document'], $matches);
                                                                        $file_id = $matches[1];
                                                                        // Generate the preview URL for iframe
                                                                        $preview_url = "https://drive.google.com/file/d/$file_id/preview";
                                                                        ?>
                                                                        <iframe src="<?php echo $preview_url; ?>" style="width: 100%; height: 100%;"
                                                                            frameborder="0" allow="autoplay" sandbox="allow-scripts allow-same-origin"></iframe>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <small class="form-text text-muted">
                                                                    Please upload a high-quality PDF/image, no larger than 1 MB. For ID cards, both sides must be included.
                                                                </small>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>Confidentiality Statement</td>
                                                            <td>
                                                                <div class="border p-3" style="max-height: 200px; overflow-y: auto;">
                                                                    <div>
                                                                        <ol>
                                                                            <li>
                                                                                <p>Personal Information Collection</p>
                                                                                <p>
                                                                                    All personal information collected by RSSI is done so exclusively with your consent, by means of a form
                                                                                    posted on our website, an email received from you, or by telephone. No information is collected automatically.
                                                                                </p>
                                                                            </li>
                                                                            <li>
                                                                                <p>Use of Your Personal Information</p>
                                                                                <p>
                                                                                    The personal information collected is only used by RSSI staff for the purposes defined at the time of the
                                                                                    collection or a use that complies with these purposes. We do not share your information with any third parties.
                                                                                </p>
                                                                                <p>
                                                                                    As mentioned above, we use your personal information to appropriately process your requests and present you
                                                                                    with the information you need to access. We also use all of the information you provide voluntarily in order
                                                                                    to make your visits on our site possible. This information might, at a later time, allow us to add customized
                                                                                    elements to our site or to plan its content more appropriately, based on user interests.
                                                                                </p>
                                                                                <p>
                                                                                    If you have granted us the permission to, we can use your personal information in order to send you newsletters,
                                                                                    with the intent of offering you the best service possible.
                                                                                </p>
                                                                            </li>
                                                                            <li>
                                                                                <p>Sharing of Your Personal Information</p>
                                                                                <p>
                                                                                    We will not, in any circumstances, share your personal information with other individuals or organizations without
                                                                                    your permission, including public organizations, corporations, or individuals, except when applicable by law. We
                                                                                    do not sell, communicate, or divulge your information to any mailing lists. We can offer to add your address to an
                                                                                    RSSI Network mailing list or list server if you request it. In this last case, you may at any time ask us to remove
                                                                                    your name from such lists.
                                                                                </p>
                                                                                <p>
                                                                                    The only exception is if the law or a court order compels us to. We will share your information with government
                                                                                    agencies if they need or request it.
                                                                                </p>
                                                                            </li>
                                                                            <li>
                                                                                <p>Storage and Safety of Your Personal Information</p>
                                                                                <p>
                                                                                    We store your file ourselves. In addition, we use and apply the appropriate security measures to preserve the
                                                                                    confidentiality of your information.
                                                                                </p>
                                                                                <p>
                                                                                    RSSI notably uses software to monitor traffic on its network in order to detect unauthorized attempts to download
                                                                                    or change information, or to otherwise damage the site. This software receives and records the IP address of the
                                                                                    computer used by the person visiting our website, the date and time of the visit, and the pages viewed. We do not
                                                                                    attempt to make the connection between these addresses and the identities of the individuals who visit our site,
                                                                                    unless an intrusion or disruption attempt is detected.
                                                                                </p>
                                                                            </li>
                                                                        </ol>
                                                                    </div>

                                                                    </p>
                                                                    <!-- Insert full text of confidentiality statement here -->
                                                                </div>
                                                                <div class="form-check mt-2">
                                                                    <input class="form-check-input" type="checkbox" id="agreeConfidentiality" name="agreeConfidentiality" required>
                                                                    <label class="form-check-label" for="agreeConfidentiality">
                                                                        I acknowledge that I have read and understand the Confidentiality Statement. I give my consent to the processing of my personal data by RSSI.
                                                                    </label>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="text-end">
                                                <button type="submit" class="btn btn-primary">Submit</button>
                                            </div>
                                        </fieldset>
                                    </form>
                                </div>
                            <?php } ?>


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
        document.getElementById('verification').addEventListener('submit', function(event) {
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
        $(document).ready(function() {
            $('input, select, textarea').each(function() {
                if ($(this).prop('required')) { // Check if the element has the required attribute
                    $(this).closest('td').prev('td').find('label').append(' <span style="color: red">*</span>');
                }
            });
        });
    </script>
</body>

</html>