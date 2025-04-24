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
?>
<?php
// Retrieve student ID from form input
@$student_id = trim($_GET['student_id']);

// Query database for student information based on ID
$result = pg_query($con, "SELECT * FROM rssimyprofile_student WHERE student_id = '$student_id'");
$resultArr = pg_fetch_all($result);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}
?>
<?php
if (@$_POST['form-type'] == "admission_admin") {

    // First, fetch the current student data including DOA
    $student_id = $_POST['student-id'];
    $currentStudentQuery = "SELECT type_of_admission, doa FROM rssimyprofile_student WHERE student_id = '$student_id'";
    $currentStudentResult = pg_query($con, $currentStudentQuery);
    $currentStudentData = pg_fetch_assoc($currentStudentResult);

    $original_type_of_admission = $currentStudentData['type_of_admission'] ?? '';
    $original_doa = $currentStudentData['doa'] ?? date('Y-m-d'); // Fallback to today if not set

    // Get the form data
    $type_of_admission = $_POST['type-of-admission'];
    $effective_from_date = $_POST['effective-from-date'] ?? date('Y-m-d');

    // Validate effective date
    if (!empty($_POST['effective-from-date'])) {
        $effective_from_date = date('Y-m-d', strtotime($_POST['effective-from-date']));
        // Ensure effective date isn't before DOA
        if (strtotime($effective_from_date) < strtotime($original_doa)) {
            $effective_from_date = $original_doa;
        }
    } else {
        $effective_from_date = date('Y-m-d');
    }

    // Check if type of admission has changed
    $type_changed = ($type_of_admission != $original_type_of_admission);

    $student_name = $_POST['student-name'];
    $date_of_birth = $_POST['date-of-birth'];
    $gender = $_POST['gender'];

    $aadhar_available = $_POST['aadhar-card'];
    $aadhar_card = $_POST['aadhar-number']; // This should be a string, not an array

    $guardian_name = $_POST['guardian-name'];
    $guardian_relation = $_POST['relation'];
    $guardian_aadhar = $_POST['guardian-aadhar-number'];
    $state_of_domicile = $_POST['state'];
    $postal_address = htmlspecialchars($_POST['postal-address'], ENT_QUOTES, 'UTF-8');
    $permanent_address = htmlspecialchars($_POST['permanent-address'], ENT_QUOTES, 'UTF-8');
    $telephone_number = $_POST['telephone'];
    $email_address = $_POST['email'];
    $preferred_branch = $_POST['branch'];
    $class = $_POST['class'];
    $school_admission_required = $_POST['school-required'];
    $school_name = htmlspecialchars($_POST['school-name'], ENT_QUOTES, 'UTF-8');
    $board_name = $_POST['board-name'];
    $medium = $_POST['medium'];
    $family_monthly_income = $_POST['income'];
    $total_family_members = $_POST['family-members'];
    $payment_mode = $_POST['payment-mode'];
    $c_authentication_code = $_POST['c-authentication-code'];
    $transaction_id = $_POST['transaction-id'];
    $subject_select = $_POST['subject-select'];
    // $access_category = $_POST['access_category'];
    $payment_type = $_POST['payment_type'];

    $module = $_POST['module'];
    $category = $_POST['category'];
    $photo_url = $_POST['photo-url'];
    $id_card_issued = $_POST['id-card-issued'];
    $status = $_POST['status'];
    $age = $_POST['age'];

    if (!empty($_POST['effectivefrom'])) {
        $effective_from = $_POST['effectivefrom'];
        $effective_from_str = "effectivefrom='$effective_from'";
    } else {
        $effective_from_str = "effectivefrom=NULL";
    }

    $remarks = htmlspecialchars($_POST['remarks'], ENT_QUOTES, 'UTF-8');
    $scode = $_POST['scode'];
    $updated_by = $_POST['updatedby'];
    $student_id = $_POST['student-id'];
    @$timestamp = date('Y-m-d H:i:s');
    $student_photo = $_FILES['student-photo'] ?? null;
    $aadhar_card_upload = $_FILES['aadhar-card-upload'] ?? null;
    $caste_document = $_FILES['caste-document'] ?? null;
    $caste = $_POST['caste'];

    $doclink_student_photo = null;
    if (!empty($student_photo['name'])) {
        $filename = "photo_" . $student_id . "_" . $timestamp;
        $parent = '1ziDLJgSG7zTYG5i0LzrQ6pNq9--LQx3_t0_SoSR2tSJW8QTr-7EkPUBR67zn0os5NRfgeuDH';
        $doclink_student_photo = uploadeToDrive($student_photo, $parent, $filename);
    }

    $doclink_aadhar_card = null;
    if (!empty($aadhar_card_upload['name'])) {
        $filename = "aadhar_" . $student_id . "_" . $timestamp;
        $parent = '1NdMb6fh4eZ_2yVwaTK088M9s5Yn7MSVbq1D7oTU6loZIe4MokkI9yhhCorqD6RaSfISmPrya';
        $doclink_aadhar_card = uploadeToDrive($aadhar_card_upload, $parent, $filename);
    }

    $doclink_caste_document = null;
    if (!empty($caste_document['name'])) {
        $filename = "caste_" . $student_id . "_" . $timestamp;
        $parent = '1NdMb6fh4eZ_2yVwaTK088M9s5Yn7MSVbq1D7oTU6loZIe4MokkI9yhhCorqD6RaSfISmPrya';
        $doclink_caste_document = uploadeToDrive($caste_document, $parent, $filename);
    }

    // Build the SQL query conditionally
    $fields = [
        "type_of_admission='$type_of_admission'",
        "studentname='$student_name'",
        "dateofbirth='$date_of_birth'",
        "gender='$gender'",
        "aadhar_available='$aadhar_available'",
        "studentaadhar=" . ($aadhar_card ? "'$aadhar_card'" : "NULL"), // Handle aadhar card
        "guardiansname='$guardian_name'",
        "relationwithstudent='$guardian_relation'",
        "guardianaadhar='$guardian_aadhar'",
        "stateofdomicile='$state_of_domicile'",
        "postaladdress='$postal_address'",
        "permanentaddress='$permanent_address'",
        "contact='$telephone_number'",
        "emailaddress='$email_address'",
        "preferredbranch='$preferred_branch'",
        "class='$class'",
        "schooladmissionrequired='$school_admission_required'",
        "nameoftheschool='$school_name'",
        "nameoftheboard='$board_name'",
        "medium='$medium'",
        "familymonthlyincome='$family_monthly_income'",
        "totalnumberoffamilymembers='$total_family_members'",
        "payment_mode='$payment_mode'",
        "c_authentication_code='$c_authentication_code'",
        "transaction_id='$transaction_id'",
        "student_id='$student_id'",
        "nameofthesubjects='$subject_select'",
        "module='$module'",
        "category='$category'",
        "photourl='$photo_url'",
        "id_card_issued='$id_card_issued'",
        "filterstatus='$status'",
        "remarks='$remarks'",
        $effective_from_str,
        "scode='$scode'",
        "updated_by='$updated_by'",
        "age='$age'",
        "updated_on='$timestamp'",
        "payment_type='$payment_type'",
        "caste='$caste'"
        // "access_category='$access_category'"
    ];

    // Include file links only if they are set
    if ($doclink_student_photo) {
        $fields[] = "student_photo_raw='$doclink_student_photo'";
    }

    if ($doclink_aadhar_card) {
        $fields[] = "upload_aadhar_card='$doclink_aadhar_card'";
    }

    if ($doclink_caste_document) {
        $fields[] = "caste_document='$doclink_caste_document'";
    }

    // Ensure fields are strings and concatenate them
    $field_string = implode(", ", $fields);

    @$student_update = "UPDATE rssimyprofile_student SET $field_string WHERE student_id = '$student_id'";
    $resultt = pg_query($con, $student_update);
    $cmdtuples = pg_affected_rows($resultt);

    // If type of admission changed, update the history table
    if ($type_changed && $cmdtuples > 0) {
        // Determine the category type (New/Existing) based on admission type
        $category_type = $type_of_admission;

        // First, close any open history records for this student that overlap with the new effective date
        $closeHistoryQuery = "UPDATE student_category_history 
                            SET effective_until = DATE '$effective_from_date' - INTERVAL '1 day'
                            WHERE student_id = '$student_id' 
                            AND (effective_until IS NULL OR effective_until >= DATE '$effective_from_date')
                            AND effective_from < DATE '$effective_from_date'";
        pg_query($con, $closeHistoryQuery);

        // Also adjust any future-dated records that would now be incorrect
        $adjustFutureRecords = "UPDATE student_category_history 
                              SET effective_from = DATE '$effective_from_date'
                              WHERE student_id = '$student_id' 
                              AND effective_from >= DATE '$effective_from_date'";
        pg_query($con, $adjustFutureRecords);

        // Insert new history record
        $insertHistoryQuery = "INSERT INTO student_category_history (
                                student_id, 
                                category_type, 
                                effective_from, 
                                created_by
                              ) VALUES (
                                '$student_id', 
                                '$category_type', 
                                DATE '$effective_from_date', 
                                '$updated_by'
                              )";
        pg_query($con, $insertHistoryQuery);

        // For new admissions, ensure we have a complete history from admission date
        if (in_array($type_of_admission, ['Basic', 'Regular', 'Premium', 'General'])) {
            $checkHistoryQuery = "SELECT COUNT(*) as count, 
                                MIN(effective_from) as min_date 
                                FROM student_category_history 
                                WHERE student_id = '$student_id'";
            $historyResult = pg_query($con, $checkHistoryQuery);
            $historyData = pg_fetch_assoc($historyResult);
            $historyCount = $historyData['count'];
            $minHistoryDate = $historyData['min_date'];

            // If the earliest record isn't from admission date, add it
            if ($minHistoryDate != $original_doa) {
                $insertInitialHistory = "INSERT INTO student_category_history (
                                        student_id, 
                                        category_type, 
                                        effective_from, 
                                        effective_until,
                                        created_by
                                      ) VALUES (
                                        '$student_id', 
                                        '$original_type_of_admission', 
                                        DATE '$original_doa', 
                                        DATE '$effective_from_date' - INTERVAL '1 day',
                                        '$updated_by'
                                      )";
                pg_query($con, $insertInitialHistory);
            }
        }
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
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Admin Update Admission Form</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
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

        .back-to-top {
            position: fixed;
            visibility: hidden;
            opacity: 0;
            right: 15px;
            bottom: 15px;
            z-index: 99999;
            background: #4154f1;
            width: 40px;
            height: 40px;
            border-radius: 4px;
            transition: all 0.4s;
        }

        .back-to-top i {
            font-size: 24px;
            color: #fff;
            line-height: 0;
        }

        .back-to-top:hover {
            background: #6776f4;
            color: #fff;
        }

        .back-to-top.active {
            visibility: visible;
            opacity: 1;
        }
    </style>

</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php if (@$type_of_admission != null && @$cmdtuples == 0) { ?>
        <div class="alert alert-danger alert-dismissible text-center" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <span>Error: We encountered an error while updating the record. Please try again.</span>
        </div>
    <?php } else if (@$cmdtuples == 1) {

        echo '<script>
        var student_id = "' . $student_id . '";
        if (confirm("The student profile has been updated successfully! Click OK to view the updated profile.")) {
            window.location.href = "admission_admin.php?student_id=" + student_id;
        }
    </script>';
    }
    ?>

    <div class="container">
        <form method="get" name="a_lookup" id="a_lookup">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Student Information Lookup</h3>
                <!-- <a href="javascript:history.go(-1)">Go to previous link</a> -->
            </div>
            <hr>
            <div class="mb-3">
                <label for="student_id" class="form-label">Student ID:</label>
                <input type="text" class="form-control" id="student_id" name="student_id" Value="<?php echo @$_GET['student_id'] ?>" placeholder="Enter student id" required>
                <div class="form-text">Enter the student id to search for their information.</div>
            </div>
            <input type="submit" name="submit" value="Search" class="btn btn-primary mb-3"> <button type='button' id="lockButton" class="btn btn-primary mb-3" <?php if (empty($_GET['student_id']) || sizeof($resultArr) == 0)
                                                                                                                                                                    echo 'disabled'; ?>>Lock / Unlock Form</button>
        </form>
        <br>
        <?php if (sizeof($resultArr) > 0) { ?>
            <?php
            foreach ($resultArr as $array) {
            ?>
                <h3>Student Onboarding Form</h3>
                <hr>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 text-end">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#joining-letter-modal-<?php echo $array['student_id'] ?>">
                                    <img src="https://cdn.iconscout.com/icon/free/png-256/free-aadhaar-2085055-1747945.png" alt="Student's Aadhaar" title="Student's Aadhaar" width="70px" />
                                </a><br>
                                <a href="student-profile.php?get_id=<?php echo $array['student_id'] ?>" target="_blank">Admission form</a>
                                <br>
                                <a href="pdf_application.php?student_id=<?php echo $array['student_id'] ?>" target="_blank">Parental Consent Form for Repeating a Grade</a>
                            </div>
                        </div>
                        <div class="row align-items-center">
                            <div class="col-md-4 d-flex flex-column justify-content-center align-items-center mb-3">
                                <img src="<?php echo $array['photourl'] ?>" alt="Profile picture" width="100px">
                            </div>

                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-12 d-flex align-items-center">
                                        <h2><?php echo $array['studentname'] ?></h2>
                                        <?php if ($array['filterstatus'] == 'Active') : ?>
                                            <span class="badge bg-success ms-3">Active</span>
                                        <?php else : ?>
                                            <span class="badge bg-danger ms-3">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Student ID:</strong> <?php echo $array['student_id'] ?></p>
                                        <p><strong>Date of application:</strong>
                                            <?php echo date('M d, Y', strtotime($array['doa'])) ?></p>
                                    </div>

                                    <div class="col-md-6">
                                        <p><strong>Preferred Branch:</strong> <?php echo $array['preferredbranch']; ?></p>
                                        <p><strong>Contact:</strong> <?php echo $array['contact']; ?></p>
                                        <p><strong>Email:</strong> <?php echo $array['emailaddress']; ?></p>
                                        <!-- Add any additional information you want to display here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                // Google Drive URL stored in $array['certificate_url']
                $url = $array['upload_aadhar_card'];

                // Extract the file ID using regular expressions
                if (preg_match('/\/file\/d\/([^\/]+)\//', $url, $matches) || preg_match('/[?&]id=([^&]+)/', $url, $matches)) {
                    $file_id = $matches[1];
                    // Generate the preview URL
                    $preview_url = "https://drive.google.com/file/d/$file_id/preview";
                } else {
                    echo "File id not found in the URL.";
                }
                ?>
                <!-- Modal -->

                <div class="modal fade" id="joining-letter-modal-<?php echo $array['student_id'] ?>" tabindex="-1" aria-labelledby="joining-letter-modal-label" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="joining-letter-modal-label">Aadhar card</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <iframe src="<?php echo $preview_url; ?>" style="width:100%; height:500px;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>
                <form name="admission_admin" id="admission_admin" action="admission_admin.php" method="post" enctype="multipart/form-data">

                    <button type="submit" id="submitBtn" class="btn btn-danger">Save Changes</button>&nbsp;<button type="button" class="btn btn-warning reload-button"><i class="bi bi-x-lg"></i>&nbsp;Discard
                    </button>
                    <p style="font-size:small; text-align: right; font-style: italic; color:#A2A2A2;">Last updated on
                        <?php echo $array['updated_on'] ?> by <?php echo $array['updated_by'] ?>
                    </p>

                    <fieldset>


                        <input type="hidden" name="form-type" value="admission_admin">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <td>
                                        <label for="student-id">Student ID:</label>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" id="student-id" name="student-id" placeholder="Enter student ID" value="<?php echo $array['student_id'] ?>" required readonly>
                                        <small id="student-id-help" class="form-text text-muted"></small>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="type-of-admission">Access Category:</label>
                                    </td>
                                    <td>
                                        <select class="form-select" id="type-of-admission" name="type-of-admission" required>
                                            <?php if ($array['type_of_admission'] == null) { ?>
                                                <option selected>--Select Access Category--</option>
                                            <?php } else { ?>
                                                <option selected>--Select Access Category--</option>
                                                <option hidden selected><?php echo $array['type_of_admission'] ?></option>
                                            <?php } ?>
                                            <option value="Basic">Basic</option>
                                            <option value="Regular">Regular</option>
                                            <option value="Premium">Premium</option>
                                            <option value="Classic">Classic</option>
                                            <option value="Excellence">Excellence</option>
                                        </select>
                                        <!-- Add hidden field to store original type for comparison -->
                                        <input type="hidden" name="original_type_of_admission" value="<?php echo $array['type_of_admission'] ?? '' ?>">
                                        <small id="type-of-admission-help" class="form-text text-muted">
                                            Please select the type of access you are applying for.
                                            <a href="#" id="show-plan-details" data-bs-toggle="modal" data-bs-target="#planDetailsModal">View Plan Details</a>
                                        </small>
                                    </td>
                                </tr>
                                <tr id="effective-date-row" style="display:none;">
                                    <td>
                                        <label for="effective-from-date">Effective From Date:</label>
                                    </td>
                                    <td>
                                        <input type="date" class="form-control" id="effective-from-date" name="effective-from-date">
                                        <small class="form-text text-muted">Select the date when this admission type should take effect</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="student-name">Student Name:</label>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" id="student-name" name="student-name" placeholder="Enter student name" value="<?php echo $array['studentname'] ?>" required>
                                        <small id="student-name-help" class="form-text text-muted">Please enter the name of the
                                            student.</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="date-of-birth">Date of Birth:</label>
                                    </td>
                                    <td>
                                        <input type="date" class="form-control" id="date-of-birth" name="date-of-birth" value="<?php echo $array['dateofbirth'] ?>" required>
                                        <small id="date-of-birth-help" class="form-text text-muted">Please enter the date of
                                            birth
                                            of the student.</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="gender">Gender:</label>
                                    </td>
                                    <td>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <?php if ($array['gender'] == null) { ?>
                                                <option selected>--Select Gender--</option>
                                            <?php
                                            } else { ?>
                                                <option selected>--Select Gender--</option>
                                                <option hidden selected><?php echo $array['gender'] ?></option>
                                            <?php }
                                            ?>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Binary">Binary</option>
                                        </select>
                                        <small id="gender-help" class="form-text text-muted">Please select the gender of the
                                            student.</small>
                                    </td>
                                </tr>

                                <!-- <input type="file" id="photo-upload" name="photo" accept="image/*" capture> -->
                                <tr>
                                    <td>
                                        <label for="student-photo">Upload Student Photo:</label>
                                    </td>
                                    <td>
                                        <!-- File input for uploading a new photo -->
                                        <input type="file" class="form-control" id="student-photo" name="student-photo" accept="image/*">

                                        <!-- Display existing photo link if available -->
                                        <?php if (!empty($array['student_photo_raw'])): ?>
                                            <div>
                                                <a href="<?php echo htmlspecialchars($array['student_photo_raw']); ?>" target="_blank">View Current Photo</a>
                                            </div>
                                        <?php endif; ?>

                                        <small id="student-photo-help" class="form-text text-muted">
                                            Please upload a recent passport size photograph of the student.
                                        </small>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <label for="aadhar-card">Aadhar Card Available?:</label>
                                    </td>
                                    <td>
                                        <select class="form-select" id="aadhar-card" name="aadhar-card" required>
                                            <?php if ($array['aadhar_available'] == null) { ?>
                                                <option selected>--Select--</option>
                                            <?php
                                            } else { ?>
                                                <option selected>--Select--</option>
                                                <option hidden selected><?php echo $array['aadhar_available'] ?></option>
                                            <?php }
                                            ?>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                        <small id="aadhar-card-help" class="form-text text-muted">Please select whether you have
                                            an
                                            Aadhar card or not.</small>
                                    </td>
                                </tr>


                                <div id="hidden-panel">
                                    <tr>
                                        <td>
                                            <label for="aadhar-number">Aadhar of the Student:</label>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" id="aadhar-number" name="aadhar-number" placeholder="Enter Aadhar number" value="<?php echo $array['studentaadhar'] ?>">
                                            <small id="aadhar-number-help" class="form-text text-muted">Please enter the Aadhar
                                                number of the student.</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="aadhar-card-upload">Upload Aadhar Card:</label>
                                        </td>
                                        <td>
                                            <!-- File input for uploading a new Aadhar card -->
                                            <input type="file" class="form-control" id="aadhar-card-upload" name="aadhar-card-upload">

                                            <!-- Display existing Aadhar card link if available -->
                                            <?php if (!empty($array['upload_aadhar_card'])): ?>
                                                <div>
                                                    <a href="<?php echo htmlspecialchars($array['upload_aadhar_card']); ?>" target="_blank">View Current Aadhar Card</a>
                                                </div>
                                            <?php endif; ?>

                                            <small id="aadhar-card-upload-help" class="form-text text-muted">
                                                Please upload a scanned copy of the Aadhar card (if available).
                                            </small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="guardian-name">Guardian's Name:</label>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" id="guardian-name" name="guardian-name" placeholder="Enter guardian name" value="<?php echo $array['guardiansname'] ?>" required>
                                            <small id="guardian-name-help" class="form-text text-muted">Please enter the name of
                                                the
                                                student's guardian.</small>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label for="relation">Relation with Student:</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="relation" name="relation" required>
                                                <?php if ($array['relationwithstudent'] == null) { ?>
                                                    <option selected>--Select Type of Relation--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select Type of Relation--</option>
                                                    <option hidden selected><?php echo $array['relationwithstudent'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="Mother">Mother</option>
                                                <option value="Father">Father</option>
                                                <option value="Spouse">Spouse</option>
                                                <option value="Other">Other</option>
                                            </select>
                                            <small id="relation-help" class="form-text text-muted">Please enter the relation of
                                                the
                                                guardian with the student.</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="guardian-aadhar-number">Aadhar of Guardian:</label>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" id="guardian-aadhar-number" name="guardian-aadhar-number" placeholder="Enter Aadhar number" value="<?php echo $array['guardianaadhar'] ?>">
                                            <small id="guardian-aadhar-number-help" class="form-text text-muted">Please enter
                                                the
                                                Aadhar number of the guardian.</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="state">State of Domicile:</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="state" name="state" required>
                                                <?php if ($array['stateofdomicile'] == null) { ?>
                                                    <option selected>--Select State--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select State--</option>
                                                    <option hidden selected><?php echo $array['stateofdomicile'] ?></option>
                                                <?php }
                                                ?>

                                                <option value="Andhra Pradesh">Andhra Pradesh</option>
                                                <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                                                <option value="Assam">Assam</option>
                                                <option value="Bihar">Bihar</option>
                                                <option value="Chhattisgarh">Chhattisgarh</option>
                                                <option value="Goa">Goa</option>
                                                <option value="Gujarat">Gujarat</option>
                                                <option value="Haryana">Haryana</option>
                                                <option value="Himachal Pradesh">Himachal Pradesh</option>
                                                <option value="Jammu Kashmir">Jammu and Kashmir</option>
                                                <option value="Jharkhand">Jharkhand</option>
                                                <option value="Karnataka">Karnataka</option>
                                                <option value="Kerala">Kerala</option>
                                                <option value="Madhya Pradesh">Madhya Pradesh</option>
                                                <option value="Maharashtra">Maharashtra</option>
                                                <option value="Manipur">Manipur</option>
                                                <option value="Meghalaya">Meghalaya</option>
                                                <option value="Mizoram">Mizoram</option>
                                                <option value="Nagaland">Nagaland</option>
                                                <option value="Odisha">Odisha</option>
                                                <option value="Punjab">Punjab</option>
                                                <option value="Rajasthan">Rajasthan</option>
                                                <option value="Sikkim">Sikkim</option>
                                                <option value="Tamil Nadu">Tamil Nadu</option>
                                                <option value="Telangana">Telangana</option>
                                                <option value="Tripura">Tripura</option>
                                                <option value="Uttar Pradesh">Uttar Pradesh</option>
                                                <option value="Uttarakhand">Uttarakhand</option>
                                                <option value="West Bengal">West Bengal</option>
                                            </select>
                                            <small id="state-help" class="form-text text-muted">Please select the state where
                                                the
                                                student resides.</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="postal-address">Current Address:</label>
                                        </td>
                                        <td>
                                            <textarea class="form-control" id="postal-address" name="postal-address" rows="3" placeholder="Enter current address" required><?php echo $array['postaladdress'] ?? '' ?></textarea>
                                            <small id="postal-address-help" class="form-text text-muted">Please enter the complete current address of the student.</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="permanent-address">Permanent Address:</label>
                                        </td>
                                        <td>
                                            <textarea class="form-control" id="permanent-address" name="permanent-address" rows="3" placeholder="Enter permanent address" required><?php echo $array['permanentaddress'] ?? '' ?></textarea>
                                            <small id="permanent-address-help" class="form-text text-muted">Please enter the complete permanent address of the student.</small>
                                            <div>
                                                <input type="checkbox" id="same-address" onclick="copyAddress()">
                                                <label for="same-address">Same as current address</label>
                                            </div>
                                        </td>
                                    </tr>

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

                                    <tr>
                                        <td>
                                            <label for="telephone">Telephone Number:</label>
                                        </td>
                                        <td>
                                            <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="Enter telephone number" value="<?php echo $array['contact'] ?>">
                                            <small id="telephone-help" class="form-text text-muted">Please enter a valid
                                                telephone
                                                number.</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="email">Email Address:</label>
                                        </td>
                                        <td>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address" value="<?php echo $array['emailaddress'] ?>">
                                            <small id="email-help" class="form-text text-muted">Please enter a valid email
                                                address.</small>
                                        </td>
                                    </tr>
                                    <!-- Caste Dropdown Field -->
                                    <tr>
                                        <td>
                                            <label for="caste">Caste:</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="caste" name="caste" required>
                                                <!-- Check if the caste value is null or empty -->
                                                <?php if (empty($array['caste'])): ?>
                                                    <option hidden selected>Select your caste</option>
                                                <?php else: ?>
                                                    <option hidden selected><?php echo htmlspecialchars($array['caste']); ?></option>
                                                <?php endif; ?>

                                                <option value="General">General</option>
                                                <option value="SC">Scheduled Caste (SC)</option>
                                                <option value="ST">Scheduled Tribe (ST)</option>
                                                <option value="OBC">Other Backward Class (OBC)</option>
                                                <option value="EWS">Economically Weaker Section (EWS)</option>
                                                <option value="Prefer not to disclose">Prefer not to disclose</option>
                                                <option value="Do not know">Do not know</option>
                                                <!-- Add additional options as necessary -->
                                            </select>

                                            <small id="caste-help" class="form-text text-muted">Please select your caste category as per government records.</small>
                                        </td>
                                    </tr>

                                    <!-- Supporting Document Upload Field -->
                                    <tr>
                                        <td>
                                            <label for="caste-document">Caste Certificate:</label>
                                        </td>
                                        <td>
                                            <input type="file" class="form-control" id="caste-document" name="caste-document" accept=".pdf,.jpg,.jpeg,.png">
                                            <!-- Display existing Caste Certificate if available -->
                                            <?php if (!empty($array['caste_document'])): ?>
                                                <div>
                                                    <a href="<?php echo htmlspecialchars($array['caste_document']); ?>" target="_blank">View Caste Certificate</a>
                                                </div>
                                            <?php endif; ?>
                                            <small id="caste-document-help" class="form-text text-muted">Upload your caste certificate (PDF, JPG, JPEG, or PNG).</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="branch">Preferred Branch:</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="branch" name="branch" required>
                                                <?php if ($array['preferredbranch'] == null) { ?>
                                                    <option selected>--Select Branch--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select Branch--</option>
                                                    <option hidden selected><?php echo $array['preferredbranch'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="Lucknow">Lucknow</option>
                                                <option value="West Bengal">West Bengal</option>
                                            </select>
                                            <small id="branch-help" class="form-text text-muted">Please select the preferred
                                                branch of
                                                study.</small>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label for="class">Class:</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="class" name="class" required>
                                                <?php if ($array['class'] == null) { ?>
                                                    <option selected>--Select Class--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select Class--</option>
                                                    <option hidden selected><?php echo $array['class'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="Nursery">Nursery</option>
                                                <option value="LKG">LKG</option>
                                                <option value="UKG">UKG</option>
                                                <option value="1">Class 1</option>
                                                <option value="2">Class 2</option>
                                                <option value="3">Class 3</option>
                                                <option value="4">Class 4</option>
                                                <option value="5">Class 5</option>
                                                <option value="6">Class 6</option>
                                                <option value="7">Class 7</option>
                                                <option value="8">Class 8</option>
                                                <option value="9">Class 9</option>
                                                <option value="10">Class 10</option>
                                                <option value="11">Class 11</option>
                                                <option value="12">Class 12</option>
                                                <option value="Vocational training">Vocational training</option>
                                            </select>
                                            <small id="class-help" class="form-text text-muted">Please select the class the
                                                student
                                                wants to join.</small>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label for="subject-select">Select subject(s): </label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="subject-select" name="subject-select" required>
                                                <?php if ($array['nameofthesubjects'] == null) { ?>
                                                    <option selected>--Select Subject--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select Subject--</option>
                                                    <option hidden selected><?php echo $array['nameofthesubjects'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="ALL Subjects">ALL Subjects</option>
                                                <option value="English">English</option>
                                                <option value="Embroidery">Embroidery</option>
                                            </select>
                                            <small class="form-text text-muted">Please select the subject(s) that you want to
                                                study from
                                                the drop-down list.</small>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label for="school-required">School Admission Required:</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="school-required" name="school-required">
                                                <?php if ($array['schooladmissionrequired'] == null) { ?>
                                                    <option selected>--Select--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select--</option>
                                                    <option hidden selected><?php echo $array['schooladmissionrequired'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                            <small id="school-required-help" class="form-text text-muted">Do you require
                                                admission in a
                                                new school?</small>
                                        </td>
                                    </tr>



                                    <tr>
                                        <td>
                                            <label for="school-name">Name Of The School:</label>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" id="school-name" name="school-name" placeholder="Enter name of the school" value="<?php echo $array['nameoftheschool'] ?>">
                                            <small id="school-name-help" class="form-text text-muted">Please enter the name
                                                of the
                                                current school or the new school you want to join.</small>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label for="board-name">Name Of The Board:</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="board-name" name="board-name">
                                                <?php if ($array['nameoftheboard'] == null) { ?>
                                                    <option selected>--Select--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select--</option>
                                                    <option hidden selected><?php echo $array['nameoftheboard'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="CBSE">CBSE</option>
                                                <option value="ICSE">ICSE</option>
                                                <option value="ISC">ISC</option>
                                                <option value="State Board">State Board</option>
                                            </select>
                                            <small id="board-name-help" class="form-text text-muted">Please enter the name of
                                                the
                                                board of education.</small>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label for="medium">Medium:</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="medium" name="medium">
                                                <?php if ($array['medium'] == null) { ?>
                                                    <option selected>--Select--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select--</option>
                                                    <option hidden selected><?php echo $array['medium'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="English">English</option>
                                                <option value="Hindi">Hindi</option>
                                                <option value="Other">Other</option>
                                            </select>
                                            <small id="medium-help" class="form-text text-muted">Please select the medium of
                                                instruction.</small>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label for="income">Family Monthly Income</label>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="income" name="income" value="<?php echo $array['familymonthlyincome'] ?>">
                                            <small id="income-help" class="form-text text-muted">Please enter the total monthly
                                                income
                                                of the student's family.</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="family-members">Total Number of Family Members</label>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="family-members" name="family-members" value="<?php echo $array['totalnumberoffamilymembers'] ?>">
                                            <small id="family-members-help" class="form-text text-muted">Please enter the total
                                                number
                                                of members in the student's family.</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="payment-mode">Payment Mode:</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="payment-mode" name="payment-mode" required>
                                                <?php if ($array['payment_mode'] == null) { ?>
                                                    <option selected>--Select--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select--</option>
                                                    <option hidden selected><?php echo $array['payment_mode'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="cash">Cash</option>
                                                <option value="online">Online</option>
                                            </select>
                                            <small id="payment-mode-help" class="form-text text-muted">Please select the payment
                                                mode
                                                for the admission fee.</small>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label for="c-authentication-code">C-Authentication Code:</label>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" id="c-authentication-code" name="c-authentication-code" placeholder="Enter C-Authentication code" value="<?php echo $array['c_authentication_code'] ?>">
                                            <small id="c-authentication-code-help" class="form-text text-muted">Please enter the
                                                C-Authentication code if you are paying by cash.</small>
                                        </td>
                                    </tr>


                                    <tr>
                                        <td>
                                            <label for="transaction-id">Transaction ID:</label>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" id="transaction-id" name="transaction-id" value="<?php echo $array['transaction_id'] ?>">
                                            <small id="online-declaration-help" class="form-text text-muted">Please enter the
                                                transaction ID if you have paid the admission fee online.</small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="payment_type" class="form-label">Payment Type:</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="payment_type" name="payment_type" required>
                                                <?php if ($array['payment_type'] == null) { ?>
                                                    <option selected>--Select--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select--</option>
                                                    <option hidden selected><?php echo $array['payment_type'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="Advance">Advance</option>
                                                <option value="Regular">Regular</option>
                                            </select>
                                        </td>
                                    </tr>

                                    <!-- <tr>
                                        <td>
                                            <label for="access_category" class="form-label">Access Category:</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="access_category" name="access_category" required>
                                                <?php if ($array['access_category'] == null) { ?>
                                                    <option selected>--Select--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select--</option>
                                                    <option hidden selected><?php echo $array['access_category'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="Premium">Premium</option>
                                                <option value="Regular">Regular</option>
                                            </select>
                                        </td>
                                    </tr> -->
                                    <tr>
                                        <td>
                                            <label for="module">Module</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="module" name="module" required>
                                                <?php if ($array['module'] == null) { ?>
                                                    <option selected>--Select Module--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select Module--</option>
                                                    <option hidden selected><?php echo $array['module'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="National">National</option>
                                                <option value="State">State</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="category">Category</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="category" name="category" required>
                                                <?php if ($array['category'] == null) { ?>
                                                    <option selected>--Select Category--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select Category--</option>
                                                    <option hidden selected><?php echo $array['category'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="LG1">LG1</option>
                                                <option value="LG2-A">LG2-A</option>
                                                <option value="LG2-B">LG2-B</option>
                                                <option value="LG2-C">LG2-C</option>
                                                <option value="LG3">LG3</option>
                                                <option value="LG3">LG4</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="age">Age</label>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" id="age" name="age" placeholder="Enter Age" value="<?php $today = new DateTime();
                                                                                                                                            echo $today->diff(new DateTime($array['dateofbirth']))->y ?>" readonly>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label for="photo-url">Photo URL</label>
                                        </td>
                                        <td>
                                            <input type="url" class="form-control" id="photo-url" name="photo-url" placeholder="Enter Photo URL" value="<?php echo $array['photourl'] ?>" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="id-card-issued">ID Card Issued</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="id-card-issued" name="id-card-issued" required>
                                                <?php if ($array['id_card_issued'] == null) { ?>
                                                    <option selected>--Select Option--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select Option--</option>
                                                    <option hidden selected><?php echo $array['id_card_issued'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <label for="status">Status</label>
                                        </td>
                                        <td>
                                            <select class="form-select" id="status" name="status" required>
                                                <?php if ($array['filterstatus'] == null) { ?>
                                                    <option selected>--Select Option--</option>
                                                <?php
                                                } else { ?>
                                                    <option selected>--Select Option--</option>
                                                    <option hidden selected><?php echo $array['filterstatus'] ?></option>
                                                <?php }
                                                ?>
                                                <option value="Active">Active</option>
                                                <option value="Inactive">Inactive</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="effectivefrom">Effective From</label>
                                        </td>
                                        <td>
                                            <input type="date" class="form-control" id="effectivefrom" name="effectivefrom" value="<?php echo $array['effectivefrom'] !== null && $array['effectivefrom'] !== '' ? date("Y-m-d", strtotime($array['effectivefrom'])) : ''; ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="remarks">Remarks</label>
                                        </td>
                                        <td>
                                            <textarea class="form-control" id="remarks" name="remarks" rows="3"><?php echo $array['remarks'] ?></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="scode">Scode</label>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" id="scode" name="scode" placeholder="Enter Scode" value="<?php echo $array['scode'] ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="updatedby">Updated By</label>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" id="updatedby" name="updatedby" placeholder="Enter Exit Interview" value="<?php echo $associatenumber ?>" readonly>
                                        </td>
                                    </tr>
                            </tbody>
                        </table>
                        <br>
                        <button type="submit" id="submitBtn" class="btn btn-danger">Save
                            Changes</button>&nbsp;<button type="button" class="btn btn-warning reload-button"><i class="bi bi-x-lg"></i>&nbsp;Discard
                        </button>
                        <p style="font-size:small; text-align: right; font-style: italic; color:#A2A2A2;">Last
                            updated
                            on <?php echo $array['updated_on'] ?> by <?php echo $array['updated_by'] ?></p>
                    </fieldset>
                </form>

            <?php } ?>
        <?php
        } else if ($student_id == null) {
        ?>
            <p>Please enter the Student ID.</p>
        <?php
        } else {
        ?>
            <p>We could not find any records matching the entered Student ID.</p>
        <?php } ?>

    </div>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper"></script>
    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
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
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.getElementById('admission_admin'); // select form by ID
            var btn1 = document.getElementById('lockButton');

            btn1.addEventListener('click', lockForm);

            function lockForm() {
                if (form.classList.toggle('locked')) {
                    // Form is now locked
                    btn1.textContent = 'Unlock Form';
                    [].slice.call(form.elements).forEach(function(item) {
                        item.disabled = true;
                    });
                } else {
                    // Form is now unlocked
                    btn1.textContent = 'Form Unlocked';
                    [].slice.call(form.elements).forEach(function(item) {
                        item.disabled = false;
                        btn1.disabled = true; // Disable the button
                    });
                }
            }

            // Lock the form when the page is loaded
            lockForm();
        });
    </script>
    <script>
        window.onload = function() {
            var myModal = new bootstrap.Modal(document.getElementById('myModal'), {
                backdrop: 'static',
                keyboard: false
            });
            myModal.show();
        };
    </script>
    <script>
        var buttons = document.querySelectorAll(".reload-button");

        buttons.forEach(function(button) {
            button.addEventListener("click", function() {
                location.reload();
            });
        });
    </script>
    <script>
        document.getElementById('type-of-admission').addEventListener('change', function() {
            const originalType = document.querySelector('input[name="original_type_of_admission"]').value;
            const newType = this.value;
            const effectiveDateRow = document.getElementById('effective-date-row');

            // Show effective date only if type is changing
            if (originalType && newType !== originalType) {
                effectiveDateRow.style.display = '';
                // Set default effective date to today
                document.getElementById('effective-from-date').valueAsDate = new Date();
            } else {
                effectiveDateRow.style.display = 'none';
            }
        });
    </script>
    <!-- Bootstrap Modal for Plan Details -->
    <div class="modal fade" id="planDetailsModal" tabindex="-1" aria-labelledby="planDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="planDetailsModalLabel">Plan Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="plan-details-content">
                    Basic, Regular, and Premium plans are for Kalpana Buds School. Classic and Excellence plans are for RSSI NGO. For detailed fee structure, please refer to the Fee Structure section.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>