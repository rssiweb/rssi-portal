<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util_tap.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// SQL query
$sql = "SELECT *
        FROM signup WHERE application_number='$application_number'";

$result = pg_query($con, $sql);
$resultArr = pg_fetch_all($result);
if (!$result) {
    echo "An error occurred.\n";
    exit;
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

    <title>Application Form</title>

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
            <h1>Application Form</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Application Form</li>
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
                            <form name="signup" id="signup" action="#" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="form-type" value="signup">
                                <?php foreach ($resultArr as $array) { ?>
                                    <table class="table">
                                        <tbody>
                                            <tr>
                                                <td>

                                                    <label for="applicant-name">Applicant
                                                        Name:</label>
                                                </td>
                                                <td><?php echo $array["applicant_name"] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="date-of-birth">Date of
                                                        Birth:</label>
                                                </td>
                                                <td><?php echo $array["date_of_birth"] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="gender">Gender:</label>
                                                </td>
                                                <td><?php echo $array["gender"] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="telephone">Telephone
                                                        Number:</label>
                                                </td>
                                                <td><?php echo $array["telephone"] ?></span>
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
                                                    <label for="branch">Preferred
                                                        Branch:</label>
                                                </td>
                                                <td><?php echo $array["branch"] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="class">Type of association:</label>
                                                </td>
                                                <td><?php echo $array["association"] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td width="30%">
                                                    <label for="job-select">Job code:
                                                    </label>
                                                </td>
                                                <td><?php echo $array["job_select"] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="purpose">Purpose of Volunteering:</label>
                                                </td>
                                                <td>
                                                    <?php echo $array["purpose"] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="availability">Availability:</label>
                                                </td>
                                                <td>
                                                    <?php echo $array["availability"] ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>
                                                    <label for="interests">What types of work opportunities are you most interested in
                                                        pursuing?</label>
                                                </td>
                                                <td>
                                                    <?php echo $array["interests"] ?>
                                                </td>
                                            </tr>


                                            <tr>
                                                <td>
                                                    <label for="post-select">Post applied for:
                                                    </label>
                                                </td>
                                                <td><?php echo $array["post_select"] ?>
                                                </td>
                                            </tr>
                                            <tr id="subjectPreferences">
                                                <td>
                                                    <label for="subjectPreferences">Subject
                                                        Preferences (Select up to
                                                        3)</label>
                                                </td>
                                                <td>
                                                    <?php echo $array["subject1"] . ', ' . $array["subject2"] . ', ' . $array["subject3"] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="medium">Medium of instruction:
                                                    </label>
                                                </td>
                                                <td><?php echo $array["medium"] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="membershipPurpose">Purpose of Membership:</label>
                                                </td>
                                                <td>
                                                    <?php echo $array["membership_purpose"] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="duration">Duration:</label>
                                                </td>
                                                <td>
                                                    <?php echo $array["duration"] ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="payment-photo">Upload Payment Screenshot</label>
                                                </td>
                                                <td>
                                                    <?php echo $array["payment_photo"] ?>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>
                                                    <label for="applicant-photo">Upload Applicant Photo:</label>
                                                </td>
                                                <td>
                                                    <?php // Extract file ID using regular expression
                                                    preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $array["applicant_photo"], $matches);
                                                    $file_id = $matches[1]; ?>
                                                    <img id="applicant-photo-preview" src="<?php echo 'https://drive.google.com/thumbnail?id=' . $file_id ?>" alt="Uploaded Photo" style="max-width: 200px; max-height: 200px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="resume-upload">Upload
                                                        Resume:</label>
                                                </td>
                                                <!--<td>
                                                    <?php
                                                    preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $array["resume_upload"], $matches);
                                                    $file_id = $matches[1];
                                                    $api_key = "AIzaSyCtWC48inXWXUM8s6hSeX89LP78sfGLk_g"; // Replace with your actual Google Drive API
                                                    // Function to get file name from Google Drive using file ID
                                                    function get_file_name_from_google_drive($file_id, $api_key)
                                                    {
                                                        // Google Drive API endpoint for fetching file metadata
                                                        $url = "https://www.googleapis.com/drive/v3/files/$file_id?key=$api_key";

                                                        // Initialize cURL session
                                                        $ch = curl_init();

                                                        // Set cURL options
                                                        curl_setopt($ch, CURLOPT_URL, $url);
                                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disabling SSL verification (optional)

                                                        // Execute cURL request
                                                        $response = curl_exec($ch);

                                                        // Close cURL session
                                                        curl_close($ch);

                                                        // Decode JSON response
                                                        $data = json_decode($response, true);

                                                        // Extract file name from metadata
                                                        if (isset($data['name'])) {
                                                            return $data['name'];
                                                        } else {
                                                            return null;
                                                        }
                                                    }
                                                    $filename = get_file_name_from_google_drive($file_id, $api_key);
                                                    ?>

                                                    <a href="<?php echo $array["resume_upload"] ?>" target="_blank"><?php echo $filename ?></a>

                                                </td>-->
                                            </tr>
                                            <tr>
                                                <td>
                                                    <label for="heardAbout" class="form-label">Where did you hear about RSSI
                                                        NGO?</label>
                                                </td>
                                                <td><?php echo $array["heard_about"] ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="mb-3 form-check">
                                        <input class="form-check-input" type="checkbox" id="consent" name="consent" required <?php echo $array['consent'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="consent">
                                            I give my consent to the processing of my personal data by the RSSI. My consent is
                                            applicable to the following information: my surname, name, telephone, email, any other
                                            information relating to my personality.
                                        </label>
                                    </div>

                                <?php } ?>
                            </form>

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

</body>

</html>