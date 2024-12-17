<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util_tap.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// SQL query to fetch current data
$sql = "SELECT * FROM signup WHERE application_number='$application_number'";
$result = pg_query($con, $sql);
$resultArr = pg_fetch_all($result);
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

    if (empty($_FILES['supportingDocument']['name'])) {
        $doclink_identifier = null;
    } else {
        $filename_identifier = "$identifier" . "_" . "$application_number" . "_" . time();
        $parent_identifier = '1YyJLwbXQqNJeESSfPINjTW2OVFOh5IGD53Aaf1ZNqsnDeWAFdh6ECr3TnbNXM95yWdS5si-z';
        $doclink_identifier = uploadeToDrive($uploadedFile_identifier, $parent_identifier, $filename_identifier);
    }

    // Build the update query dynamically
    $update_fields = [];
    if ($identifier) $update_fields[] = "identifier = '$identifier'";
    if ($identifier_number) $update_fields[] = "identifier_number = '$identifier_number'";
    if ($doclink_identifier) $update_fields[] = "supporting_document = '$doclink_identifier'";

    // If there are fields to update, execute the update query
    if (count($update_fields) > 0) {
        $update_query = "UPDATE signup SET " . implode(", ", $update_fields) . " WHERE application_number = '$application_number'";
        $result = pg_query($con, $update_query);
        $cmdtuples = pg_affected_rows($result);
    }
}

// If update was successful or failed, show an alert
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($cmdtuples == 1) {
        // Success: Profile was updated
        echo '<script>
                alert("Verification record has been submitted successfully!");
                window.location.href = "identity_verification.php";  // Reload the page
              </script>';
    } else {
        // Failure: Record was not updated
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
    <!-- <?php include 'inactive_session_expire_check.php'; ?> -->

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
                                <div class="container">
                                    <form name="verification" id="verification" action="#" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="form-type" value="verification">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <tbody>
                                                    <tr>
                                                        <td><label for="identifier">Choose any National Identifier from the below list.</label></td>
                                                        <td>

                                                            <select class="form-select" id="identifier" name="identifier" required>
                                                                <option value="" disabled <?php echo empty($array['identifier']) ? 'selected' : ''; ?>>-- Select an Identifier --</option>
                                                                <?php if (!empty($array['identifier'])) { ?>
                                                                    <option value="<?php echo htmlspecialchars($array['identifier']); ?>" selected><?php echo htmlspecialchars($array['identifier']); ?></option>
                                                                <?php } ?>
                                                                <option value="Aadhaar">Aadhaar</option>
                                                                <option value="PAN (Permanent Account Number)">PAN (Permanent Account Number)</option>
                                                                <option value="Voter ID">Voter ID</option>
                                                                <option value="Passport">Passport</option>
                                                                <option value="Ration Card">Ration Card</option>
                                                                <option value="Driving License">Driving License</option>
                                                                <option value="National Population Register (NPR) Number">National Population Register (NPR) Number</option>
                                                                <option value="PR Card (Person of Indian Origin)">PR Card (Person of Indian Origin)</option>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        function checkTelephoneLength() {
            var telephone = document.getElementById('telephone').value;

            // Limit the input to 10 digits
            if (telephone.length > 10) {
                alert("You can only enter up to 10 digits.");
                document.getElementById('telephone').value = telephone.slice(0, 10); // Truncate to 10 digits
            }
        }
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