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

$associatenumber = isset($_GET['associatenumber']) ? $_GET['associatenumber'] : null;

// SQL query to fetch current data
$sql = "SELECT * FROM rssimyaccount_members WHERE associatenumber='$associatenumber'";
$result = pg_query($con, $sql);
$resultArr = pg_fetch_all($result);
// Check if there are any results
if ($resultArr && count($resultArr) > 0) {
    // Accessing specific column values from the first result (assuming there is only one row)
    $associate_email = $resultArr[0]['email'];
    $associate_name = $resultArr[0]['fullname'];
    $associatenumber = $resultArr[0]['associatenumber'];
    $associate_telephone = $resultArr[0]['phone'];
}
if (!$result) {
    echo "An error occurred.\n";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize an array to store field updates
    $updates = [];
    if (isset($_POST['offer_extended'])) {
        $offer_extended = pg_escape_string($con, $_POST['offer_extended']);
        $updates[] = "offer_extended = '$offer_extended'";
        // Conditional update for application_status based on the offer_extended status
        if ($offer_extended == 'Yes') {
            $updates[] = "application_status = 'Offer Extended'";
        } elseif ($offer_extended == 'No') {
            $updates[] = "application_status = 'Offer Not Extended'";
        }
    }

    // If no updates are present, exit
    if (empty($updates)) {
        echo "No fields to update.";
        exit;
    }

    // Construct the dynamic UPDATE query
    $update_query = "UPDATE rssimyaccount_members SET " . implode(", ", $updates) . " WHERE associatenumber = '$associatenumber'";

    // Execute the query
    $update_result = pg_query($con, $update_query);
    $cmdtuples = pg_affected_rows($update_result);

    // Check if profile was updated
    if ($cmdtuples == 1) {
        // Success: Profile was updated
        echo '<script>
        var applicationNumber = "' . htmlspecialchars($_GET['associatenumber']) . '";
        alert("Changes to the Associate Profile have been saved successfully.");
        window.location.href = "applicant_profile.php?associatenumber=" + applicationNumber;  // Reload the page
    </script>';
    } else {
        // Failure: Profile was not updated
        echo '<script>
        alert("Error: We encountered an error while updating the record. Please try again.");
    </script>';
    }
}

$isFormDisabled = null;
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

    <title>Applicant_Profile_<?php echo $associatenumber; ?></title>

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
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Associate Profile</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Work</li>
                    <li class="breadcrumb-item">Associate Profile</li>
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
                            <?php
                            // If no application number is provided, show the input form
                            if (!$associatenumber): ?>
                                <div class="container mt-5">
                                    <h4 class="mb-3">Enter Application Number</h4>
                                    <form method="GET" action="">
                                        <div class="input-group mb-3">
                                            <input type="text" name="associatenumber" class="form-control" placeholder="Enter Application Number" required>
                                            <button class="btn btn-primary" type="submit">Submit</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <?php foreach ($resultArr as $array) { ?>
                                <div class="container">
                                    <form name="signup" id="signup" action="#" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="form-type" value="signup">
                                        <fieldset <?php echo $isFormDisabled; ?>>

                                            <div class="table-responsive">
                                                <table class="table">
                                                    <tr>
                                                        <!-- Left Column (Application Details) -->
                                                        <td style="width: 50%; vertical-align: top;">
                                                            <table>
                                                                <tr>
                                                                    <td><label for="associatenumber">Associate Number:</label></td>
                                                                    <td><?php echo $array["associatenumber"] ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><label for="fullname">Associate Name:</label></td>
                                                                    <td><?php echo $array["fullname"] ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><label for="dateofbirth">Date of Birth:</label></td>
                                                                    <td><?php echo $array["dateofbirth"] ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><label for="gender">Gender:</label></td>
                                                                    <td><?php echo $array["gender"] ?></td>
                                                                </tr>
                                                            </table>
                                                        </td>

                                                        <!-- Right Column (Associate Photo) -->
                                                        <td style="width: 50%; vertical-align: top; text-align: center;">
                                                            <div class="photo-box mt-2" style="border: 1px solid #ccc; padding: 10px; width: 150px; height: 200px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                                                <?php
                                                                if (!empty($array['photo'])) {
                                                                    $preview_url = $array['photo'];
                                                                    echo '<img src="' . $preview_url . '" width="150" height="200" ></img>';
                                                                } else {
                                                                    echo "No photo available";
                                                                }
                                                                ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>

                                                <table class="table">
                                                    <tbody>
                                                        <tr>
                                                            <td><label for="phone">Telephone Number:</label></td>
                                                            <td>
                                                                <?php echo $array["phone"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="email">Email
                                                                    Address:</label>
                                                            </td>
                                                            <td><?php echo $array["email"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="currentaddress">Current Address:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["currentaddress"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="permanentaddress">Permanent Address:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["permanentaddress"] ?>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <label for="eduq" class="form-label">Educational Qualification:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["eduq"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="mjorsub" class="form-label">Area of Specialization:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["mjorsub"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="workexperience" class="form-label">Work Experience:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array["workexperience"] ?>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <label for="caste">Caste:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo @$array["caste"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="medium">Language proficiency:
                                                                </label>
                                                            </td>
                                                            <td><?php echo @$array["medium"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="identifier">National Identifier Number:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array['nationalidentifier'] ?><br>
                                                                <?php echo $array['identifier'] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="panno">PAN Number:</label>
                                                            </td>
                                                            <td>
                                                                <?php echo $array['panno'] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td><label for="Associate-name">Application Number:</label></td>
                                                            <td><?php echo $array["applicationnumber"] ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label for="photourl">Photo URL:</label></td>
                                                            <td><?php echo $array["photo"] ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="doj">Date of Join:</label>
                                                            </td>
                                                            <td>Original DOJ: <?php echo $array["doj"] ?><br>
                                                                Recent DOJ: <?php echo $array["originaldoj"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="basebranch">Base Branch:</label>
                                                            </td>
                                                            <td><?php echo $array["basebranch"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="basebranch">Deputed Branch:</label>
                                                            </td>
                                                            <td><?php echo $array["depb"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="engagement">Type of association:</label>
                                                            </td>
                                                            <td><?php echo $array["engagement"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="job_type">Job type:
                                                                </label>
                                                            </td>
                                                            <td><?php echo $array["job_type"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="position">Designation:
                                                                </label>
                                                            </td>
                                                            <td><?php echo $array["position"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="class">Work Mode:
                                                                </label>
                                                            </td>
                                                            <td><?php echo $array["class"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="position">Grade:
                                                                </label>
                                                            </td>
                                                            <td><?php echo $array["grade"] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="position">Access Role:
                                                                </label>
                                                            </td>
                                                            <td><?php echo $array["role"] ?>
                                                            </td>
                                                        </tr>

                                                    </tbody>
                                                </table>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Submit</button>
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
    <script>
        function copyAddress() {
            const currentAddress = document.getElementById('postal-address').value;
            const permanentAddressField = document.getElementById('permanent-address');
            const sameAddressCheckbox = document.getElementById('same-address');

            if (sameAddressCheckbox.checked) {
                permanentAddressField.value = currentAddress; // Copy current address to permanent address
                permanentAddressField.readOnly = true; // Make it read-only when checkbox is checked
            } else {
                permanentAddressField.value = ''; // Clear permanent address when checkbox is unchecked
                permanentAddressField.readOnly = false; // Make it editable again
            }
        }
    </script>

</body>

</html>