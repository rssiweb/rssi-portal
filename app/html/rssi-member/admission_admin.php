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

// Define field names mapping at the beginning
$fieldNames = [
    'type_of_admission' => 'Type of Admission',
    'studentname' => 'Student Name',
    'dateofbirth' => 'Date of Birth',
    'gender' => 'Gender',
    'aadhar_available' => 'Aadhar Available',
    'studentaadhar' => 'Aadhar Number',
    'guardiansname' => 'Guardian Name',
    'relationwithstudent' => 'Relation with Student',
    'guardianaadhar' => 'Guardian Aadhar',
    'stateofdomicile' => 'State of Domicile',
    'postaladdress' => 'Postal Address',
    'permanentaddress' => 'Permanent Address',
    'contact' => 'Telephone Number',
    'emailaddress' => 'Email Address',
    'preferredbranch' => 'Preferred Branch',
    'class' => 'Class',
    'schooladmissionrequired' => 'School Admission Required',
    'nameoftheschool' => 'School Name',
    'nameoftheboard' => 'Board Name',
    'medium' => 'Medium',
    'familymonthlyincome' => 'Family Monthly Income',
    'totalnumberoffamilymembers' => 'Total Family Members',
    'nameofthesubjects' => 'Subjects',
    'module' => 'Module',
    'category' => 'Category',
    'photourl' => 'Photo URL',
    'filterstatus' => 'Status',
    'remarks' => 'Remarks',
    'effectivefrom' => 'Effective From',
    'scode' => 'Scode',
    'payment_type' => 'Payment Type',
    'caste' => 'Caste',
    'student_photo' => 'Student Photo',
    'aadhar_card_upload' => 'Aadhar Card Upload',
    'caste_document' => 'Caste Document',
    'effective_from_date' => 'Effective From Date'
];

// Retrieve student ID from form input
@$student_id = trim($_GET['student_id']);

// Query database for student information based on ID
$result = pg_query($con, "SELECT * FROM rssimyprofile_student WHERE student_id = '$student_id'");
$resultArr = pg_fetch_all($result);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

if (@$_POST['form-type'] == "admission_admin") {
    // First, fetch the current student data including DOA
    $student_id = $_POST['student-id'];
    $currentStudentQuery = "SELECT * FROM rssimyprofile_student WHERE student_id = '$student_id'";
    $currentStudentResult = pg_query($con, $currentStudentQuery);
    $currentStudentData = pg_fetch_assoc($currentStudentResult);

    // Array to track changed fields
    $changedFields = array();
    $updates = array();

    // Helper function to compare and track changes
    function checkAndAddUpdate(&$updates, &$changedFields, $fieldName, $newValue, $originalValue, $isFile = false)
    {
        if ($isFile) {
            return;
        }

        $newValue = trim($newValue);
        $originalValue = trim($originalValue ?? '');

        if ($newValue != $originalValue) {
            $updates[] = "$fieldName=" . ($newValue !== '' ? "'$newValue'" : "NULL");
            $changedFields[] = $fieldName;
        }
    }

    // Check each field for changes
    checkAndAddUpdate($updates, $changedFields, 'type_of_admission', $_POST['type_of_admission'], $currentStudentData['type_of_admission']);
    checkAndAddUpdate($updates, $changedFields, 'studentname', $_POST['student-name'], $currentStudentData['studentname']);
    checkAndAddUpdate($updates, $changedFields, 'dateofbirth', $_POST['date-of-birth'], $currentStudentData['dateofbirth']);
    checkAndAddUpdate($updates, $changedFields, 'gender', $_POST['gender'], $currentStudentData['gender']);
    checkAndAddUpdate($updates, $changedFields, 'aadhar_available', $_POST['aadhar-card'], $currentStudentData['aadhar_available']);
    checkAndAddUpdate($updates, $changedFields, 'studentaadhar', $_POST['aadhar-number'], $currentStudentData['studentaadhar']);
    checkAndAddUpdate($updates, $changedFields, 'guardiansname', $_POST['guardian-name'], $currentStudentData['guardiansname']);
    checkAndAddUpdate($updates, $changedFields, 'relationwithstudent', $_POST['relation'], $currentStudentData['relationwithstudent']);
    checkAndAddUpdate($updates, $changedFields, 'guardianaadhar', $_POST['guardian-aadhar-number'], $currentStudentData['guardianaadhar']);
    checkAndAddUpdate($updates, $changedFields, 'stateofdomicile', $_POST['state'], $currentStudentData['stateofdomicile']);
    checkAndAddUpdate($updates, $changedFields, 'postaladdress', htmlspecialchars($_POST['postal-address'], ENT_QUOTES, 'UTF-8'), $currentStudentData['postaladdress']);
    checkAndAddUpdate($updates, $changedFields, 'permanentaddress', htmlspecialchars($_POST['permanent-address'], ENT_QUOTES, 'UTF-8'), $currentStudentData['permanentaddress']);
    checkAndAddUpdate($updates, $changedFields, 'contact', $_POST['telephone'], $currentStudentData['contact']);
    checkAndAddUpdate($updates, $changedFields, 'emailaddress', $_POST['email'], $currentStudentData['emailaddress']);
    checkAndAddUpdate($updates, $changedFields, 'preferredbranch', $_POST['branch'], $currentStudentData['preferredbranch']);
    checkAndAddUpdate($updates, $changedFields, 'class', $_POST['class'], $currentStudentData['class']);
    checkAndAddUpdate($updates, $changedFields, 'schooladmissionrequired', $_POST['school-required'], $currentStudentData['schooladmissionrequired']);
    checkAndAddUpdate($updates, $changedFields, 'nameoftheschool', htmlspecialchars($_POST['school-name'], ENT_QUOTES, 'UTF-8'), $currentStudentData['nameoftheschool']);
    checkAndAddUpdate($updates, $changedFields, 'nameoftheboard', $_POST['board-name'], $currentStudentData['nameoftheboard']);
    checkAndAddUpdate($updates, $changedFields, 'medium', $_POST['medium'], $currentStudentData['medium']);
    checkAndAddUpdate($updates, $changedFields, 'familymonthlyincome', $_POST['income'], $currentStudentData['familymonthlyincome']);
    checkAndAddUpdate($updates, $changedFields, 'totalnumberoffamilymembers', $_POST['family-members'], $currentStudentData['totalnumberoffamilymembers']);
    checkAndAddUpdate($updates, $changedFields, 'nameofthesubjects', $_POST['subject-select'], $currentStudentData['nameofthesubjects']);
    checkAndAddUpdate($updates, $changedFields, 'module', $_POST['module'], $currentStudentData['module']);
    checkAndAddUpdate($updates, $changedFields, 'category', $_POST['category'], $currentStudentData['category']);
    checkAndAddUpdate($updates, $changedFields, 'photourl', $_POST['photo-url'], $currentStudentData['photourl']);
    checkAndAddUpdate($updates, $changedFields, 'filterstatus', $_POST['status'], $currentStudentData['filterstatus']);
    checkAndAddUpdate($updates, $changedFields, 'remarks', htmlspecialchars($_POST['remarks'], ENT_QUOTES, 'UTF-8'), $currentStudentData['remarks']);
    checkAndAddUpdate($updates, $changedFields, 'scode', $_POST['scode'], $currentStudentData['scode']);
    checkAndAddUpdate($updates, $changedFields, 'payment_type', $_POST['payment_type'], $currentStudentData['payment_type']);
    checkAndAddUpdate($updates, $changedFields, 'caste', $_POST['caste'], $currentStudentData['caste']);

    if (!empty($_POST['effectivefrom'])) {
        $effective_from = $_POST['effectivefrom'];
        if ($effective_from != $currentStudentData['effectivefrom']) {
            $updates[] = "effectivefrom='$effective_from'";
            $changedFields[] = 'effectivefrom';
        }
    } else {
        if ($currentStudentData['effectivefrom'] !== null) {
            $updates[] = "effectivefrom=NULL";
            $changedFields[] = 'effectivefrom';
        }
    }

    // Handle file uploads
    $timestamp = date('Y-m-d H:i:s');
    $student_photo = $_FILES['student-photo'] ?? null;
    $aadhar_card_upload = $_FILES['aadhar-card-upload'] ?? null;
    $caste_document = $_FILES['caste-document'] ?? null;

    // Check if new files were uploaded
    if (!empty($student_photo['name'])) {
        $filename = "photo_" . $student_id . "_" . $timestamp;
        $parent = '1ziDLJgSG7zTYG5i0LzrQ6pNq9--LQx3_t0_SoSR2tSJW8QTr-7EkPUBR67zn0os5NRfgeuDH';
        $doclink_student_photo = uploadeToDrive($student_photo, $parent, $filename);
        $updates[] = "student_photo_raw='$doclink_student_photo'";
        $changedFields[] = 'student_photo';
    }

    if (!empty($aadhar_card_upload['name'])) {
        $filename = "aadhar_" . $student_id . "_" . $timestamp;
        $parent = '1NdMb6fh4eZ_2yVwaTK088M9s5Yn7MSVbq1D7oTU6loZIe4MokkI9yhhCorqD6RaSfISmPrya';
        $doclink_aadhar_card = uploadeToDrive($aadhar_card_upload, $parent, $filename);
        $updates[] = "upload_aadhar_card='$doclink_aadhar_card'";
        $changedFields[] = 'aadhar_card_upload';
    }

    if (!empty($caste_document['name'])) {
        $filename = "caste_" . $student_id . "_" . $timestamp;
        $parent = '1NdMb6fh4eZ_2yVwaTK088M9s5Yn7MSVbq1D7oTU6loZIe4MokkI9yhhCorqD6RaSfISmPrya';
        $doclink_caste_document = uploadeToDrive($caste_document, $parent, $filename);
        $updates[] = "caste_document='$doclink_caste_document'";
        $changedFields[] = 'caste_document';
    }

    // Handle effective_from_date separately
    $original_doa = $currentStudentData['doa'] ?? date('Y-m-d');

    // Get current effective date first
    $currentPlanQuery = "SELECT effective_from FROM student_category_history 
                       WHERE student_id = '$student_id'
                       AND is_valid = true
                       AND (effective_until >= CURRENT_DATE OR effective_until IS NULL)
                       ORDER BY effective_from DESC, created_at DESC 
                       LIMIT 1";
    $currentResult = pg_query($con, $currentPlanQuery);
    $currentRow = pg_fetch_assoc($currentResult);
    $current_effective_from_date = $currentRow['effective_from'] ?? $original_doa;

    // Process effective_from_date
    if (isset($_POST['effective_from_date']) && !empty($_POST['effective_from_date'])) {
        $submitted_date = date('Y-m-d', strtotime($_POST['effective_from_date']));
        if (strtotime($submitted_date) < strtotime($original_doa)) {
            $submitted_date = $original_doa;
        }
        $date_changed = ($submitted_date != $current_effective_from_date);
        $effective_from_date = $submitted_date;
    } else {
        $effective_from_date = $current_effective_from_date;
        $date_changed = false;
    }

    if ($date_changed) {
        $changedFields[] = 'effective_from_date';
    }

    // Always update these fields
    $updates[] = "updated_by='" . $_POST['updatedby'] . "'";
    $updates[] = "updated_on='$timestamp'";

    // Only proceed with update if there are changes
    if (!empty($updates) || $date_changed) {
        $field_string = implode(", ", $updates);
        $student_update = "UPDATE rssimyprofile_student SET $field_string WHERE student_id = '$student_id'";
        $resultt = pg_query($con, $student_update);
        $cmdtuples = pg_affected_rows($resultt);

        // Check if type of admission or class changed
        $type_changed = in_array('type_of_admission', $changedFields);
        $class_changed = in_array('class', $changedFields);

        // If type of admission or class changed, update the history table
        if (($type_changed || $class_changed || $date_changed) && $cmdtuples > 0) {
            $type_of_admission = $_POST['type_of_admission'];
            $class = $_POST['class'];
            $updated_by = $_POST['updatedby'];

            // Check for existing plans
            $checkExistingPlan = "SELECT 1 FROM student_category_history 
                                WHERE student_id = '$student_id'
                                AND category_type = '$type_of_admission'
                                AND class = '$class'
                                AND effective_from = DATE '$effective_from_date'";
            $planExists = pg_num_rows(pg_query($con, $checkExistingPlan)) > 0;

            $checkCoveredPlan = "SELECT 1 FROM student_category_history 
                               WHERE student_id = '$student_id'
                               AND category_type = '$type_of_admission'
                               AND class = '$class'
                               AND effective_from <= DATE '$effective_from_date'
                               AND (effective_until >= DATE '$effective_from_date' OR effective_until IS NULL)";
            $planCovered = pg_num_rows(pg_query($con, $checkCoveredPlan)) > 0;

            if ($planExists || $planCovered) {
                $nonPlanChanges = array_diff($changedFields, ['type_of_admission', 'class', 'effective_from_date']);

                if (!empty($nonPlanChanges)) {
                    $changedFieldsList = implode(", ", array_map(function ($field) use ($fieldNames) {
                        return $fieldNames[$field] ?? $field;
                    }, $nonPlanChanges));

                    echo "<script>
                        alert('Student details updated successfully. Changed fields: $changedFieldsList\\n\\nNote: Plan change was not processed as this plan already exists for the selected period.');
                        window.location.href = 'admission_admin.php?student_id=$student_id';
                    </script>";
                } else {
                    echo "<script>
                        alert('No changes processed. This plan already exists for the selected period.');
                        window.location.href = 'admission_admin.php?student_id=$student_id';
                    </script>";
                }
                exit;
            }

            // 1. For new admissions, ensure complete history from admission date
            $checkInitialRecord = "SELECT 1 FROM student_category_history 
                WHERE student_id = '$student_id' 
                AND effective_from = DATE '$original_doa'";
            $initialRecordExists = pg_num_rows(pg_query($con, $checkInitialRecord)) > 0;

            if (!$initialRecordExists) {
                $insertInitialHistory = "INSERT INTO student_category_history (
                      student_id, 
                      category_type, 
                      effective_from, 
                      effective_until,
                      created_by,
                      class
                    ) VALUES (
                      '$student_id', 
                      '" . $currentStudentData['type_of_admission'] . "', 
                      DATE '$original_doa', 
                      DATE '$effective_from_date' - INTERVAL '1 day',
                      '$updated_by',
                      '" . $currentStudentData['class'] . "'
                    )";
                pg_query($con, $insertInitialHistory);
            }

            // 2. First close ALL existing active records (where effective_until is NULL)
            $closeAllActiveRecords = "UPDATE student_category_history 
                                SET effective_until = DATE '$effective_from_date' - INTERVAL '1 day'
                                WHERE student_id = '$student_id'
                                AND effective_until IS NULL";
            pg_query($con, $closeAllActiveRecords);

            // 3. Close any records that overlap with the new effective date
            $closeHistoryQuery = "UPDATE student_category_history 
                            SET effective_until = DATE '$effective_from_date' - INTERVAL '1 day'
                            WHERE student_id = '$student_id' 
                            AND (effective_until IS NULL OR effective_until >= DATE '$effective_from_date')
                            AND effective_from < DATE '$effective_from_date'";
            pg_query($con, $closeHistoryQuery);

            // 4. Adjust any future-dated records
            $adjustFutureRecords = "UPDATE student_category_history 
                        SET is_valid = false
                        WHERE student_id = '$student_id' 
                        AND effective_from >= DATE '$effective_from_date'";
            pg_query($con, $adjustFutureRecords);

            // 5. Insert the new record
            $insertHistoryQuery = "INSERT INTO student_category_history (
                                student_id, 
                                category_type, 
                                effective_from, 
                                created_by,
                                class
                              ) VALUES (
                                '$student_id', 
                                '$type_of_admission', 
                                DATE '$effective_from_date', 
                                '$updated_by',
                                '$class'
                              )";
            pg_query($con, $insertHistoryQuery);
        }

        // Prepare success message
        $changedFieldsList = implode(", ", array_map(function ($field) use ($fieldNames) {
            return $fieldNames[$field] ?? $field;
        }, $changedFields));

        echo "<script>
            alert('Student record updated successfully. Changed fields: $changedFieldsList');
            window.location.href = 'admission_admin.php?student_id=$student_id';
        </script>";
    } else {
        echo "<script>
            alert('No changes detected in the student record.');
            window.location.href = 'admission_admin.php?student_id=$student_id';
        </script>";
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
    <title>Update Admission Form</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2 for student IDs
            $('#student_id').select2({
                ajax: {
                    url: 'fetch_students.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results || []
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1,
                placeholder: 'Select student',
                allowClear: true,
                width: '100%' // Ensure proper width
            });
        });
    </script>

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
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Update Admission Form</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item"><a href="student.php">Student Database</a></li>
                    <li class="breadcrumb-item active">Update Admission Form</li>
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
                            <div class="container">
                                <form method="get" name="a_lookup" id="a_lookup">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <h3>Student Information Lookup</h3>
                                    </div>
                                    <hr>
                                    <!-- StudentId Dropdown -->
                                    <div class="mb-3">
                                        <label for="student_id" class="form-label">Student ID:</label>
                                        <select class="form-select" id="student_id" name="student_id" required>
                                            <?php if (!empty($student_id)): ?>
                                                <option value="<?= $student_id ?>" selected><?= $student_id ?></option>
                                            <?php endif; ?>
                                        </select>
                                        <div class="form-text">Enter the student id to search for their information.</div>
                                    </div>

                                    <input type="submit" name="submit" value="Search" class="btn btn-primary mb-3">
                                    <button type='button' id="lockButton" class="btn btn-primary mb-3" <?php if (empty($_GET['student_id'])) echo 'disabled'; ?>>Lock / Unlock Form</button>
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
                                                        <a href="pdf_application.php?student_id=<?php echo $array['student_id'] ?>" target="_blank">Retention Form</a>
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
                                                        <!-- Current Plan Display -->
                                                        <tr>
                                                            <td>
                                                                <label>Current Plan:</label>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-grow-1">
                                                                        <?php
                                                                        // Default values
                                                                        $currentCategory = !empty($array['class']) ? $array['class'] . '/' . $array['type_of_admission'] : 'Not selected';
                                                                        $effectiveDateFormatted = !empty($array['doa']) ? date('F Y', strtotime($array['doa'])) : 'Not set';
                                                                        $hasFuturePlan = false;

                                                                        if (!empty($array['student_id'])) {
                                                                            $currentDate = date('Y-m-d');

                                                                            // Query to get current active plan (latest by created_at if same effective_from)
                                                                            $currentPlanQuery = "SELECT category_type, class, effective_from 
                                                                            FROM student_category_history 
                                                                            WHERE student_id = '" . $array['student_id'] . "'
                                                                            AND is_valid = true
                                                                            AND effective_from <= '$currentDate'
                                                                            AND (effective_until >= '$currentDate' OR effective_until IS NULL)
                                                                            ORDER BY effective_from DESC, created_at DESC LIMIT 1";

                                                                            // Query to check for future plans
                                                                            $futurePlanQuery = "SELECT 1 FROM student_category_history
                                                                            WHERE student_id = '" . $array['student_id'] . "'
                                                                            AND is_valid = true
                                                                            AND effective_from > '$currentDate'
                                                                            LIMIT 1";

                                                                            // Get current active plan
                                                                            $currentResult = pg_query($con, $currentPlanQuery);
                                                                            if ($currentRow = pg_fetch_assoc($currentResult)) {
                                                                                $currentCategory = ($currentRow['class'] ?? $array['class']) . '/' . $currentRow['category_type'];
                                                                                $effectiveDateFormatted = date('F Y', strtotime($currentRow['effective_from']));
                                                                            }

                                                                            // Check for future plans
                                                                            $futureResult = pg_query($con, $futurePlanQuery);
                                                                            $hasFuturePlan = (pg_num_rows($futureResult) > 0);
                                                                        }
                                                                        ?>

                                                                        <p class="mb-1">
                                                                            <strong>Access Category:</strong>
                                                                            <span id="current-admission-display"><?php echo $currentCategory; ?></span>
                                                                        </p>
                                                                        <p class="mb-1">
                                                                            <strong>Effective From:</strong>
                                                                            <span id="current-effective-date-display">
                                                                                <?php echo $effectiveDateFormatted; ?>
                                                                                <?php if ($hasFuturePlan): ?>
                                                                                    <span class="badge bg-warning text-dark ms-2" title="Future plan exists">
                                                                                        <i class="fas fa-clock"></i> Pending Change
                                                                                    </span>
                                                                                <?php endif; ?>
                                                                            </span>
                                                                            <small class="text-muted">(Plan will be applied to <?php echo $effectiveDateFormatted; ?> month's feesheet)</small>
                                                                        </p>
                                                                    </div>
                                                                    <div class="ms-3">
                                                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updatePlanModal">
                                                                            Update Plan
                                                                        </button>
                                                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" data-bs-toggle="modal" data-bs-target="#planHistoryModal">
                                                                            View History
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <!-- Hidden fields to store the actual values -->
                                                                <input type="hidden" id="division-select" name="division" value="<?php echo $array['division'] ?? '' ?>">
                                                                <input type="hidden" id="class-select" name="class" value="<?php echo $array['class'] ?? '' ?>">
                                                                <input type="hidden" id="type-of-admission" name="type_of_admission" value="<?php echo $array['type_of_admission'] ?? '' ?>">
                                                                <input type="hidden" id="effective-from-date" name="effective_from_date" value="<?php echo $array['effective_from_date'] ?? '' ?>">
                                                                <input type="hidden" name="original_type_of_admission" value="<?php echo $array['type_of_admission'] ?? '' ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="student-name">Student Name:</label>
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control" id="student-name" name="student-name" placeholder="Enter student name" value="<?php echo $array['studentname'] ?>" required>
                                                                <small id="student-name-help" class="form-text text-muted">Please enter the name of the student.</small>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <label for="date-of-birth">Date of Birth:</label>
                                                            </td>
                                                            <td class="d-flex flex-column">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <input type="date" class="form-control" id="date-of-birth" name="date-of-birth"
                                                                        value="<?php echo $array['dateofbirth'] ?>" required style="max-width: 250px;">
                                                                    <span id="age-display" class="text-secondary" style="white-space: nowrap;">
                                                                        <!-- Age will be populated here -->
                                                                    </span>
                                                                </div>
                                                                <small id="date-of-birth-help" class="form-text text-muted mt-1">
                                                                    Please enter the date of birth of the student.
                                                                </small>
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
                                                                        <input type="checkbox" class="form-check-input" id="same-address" onclick="copyAddress()">
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
                                                                        <option value="NIOS">NIOS</option>
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
                                                            <!-- <tr>
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
                                                            </tr> -->
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
                                                                        <option value="LG4">LG4</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <!-- <tr>
                                                                <td>
                                                                    <label for="age">Age</label>
                                                                </td>
                                                                <td>
                                                                    <input type="number" class="form-control" id="age" name="age" placeholder="Enter Age" value="<?php $today = new DateTime();
                                                                                                                                                                    echo $today->diff(new DateTime($array['dateofbirth']))->y ?>" readonly>
                                                                </td>
                                                            </tr> -->

                                                            <tr>
                                                                <td>
                                                                    <label for="photo-url">Photo URL</label>
                                                                </td>
                                                                <td>
                                                                    <input type="url" class="form-control" id="photo-url" name="photo-url" placeholder="Enter Photo URL" value="<?php echo $array['photourl'] ?>" required>
                                                                </td>
                                                            </tr>
                                                            <!-- <tr>
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
                                                            </tr> -->

                                                            <tr>
                                                                <td>
                                                                    <label for="status">Status</label>
                                                                </td>
                                                                <td>
                                                                    <select class="form-select" id="status" name="status" required onchange="handleStatusChange(this)">
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
                                                                    <div class="input-group">
                                                                        <input type="text" class="form-control" id="scode" name="scode" placeholder="Enter Scode" value="<?php echo $array['scode'] ?>">
                                                                        <button class="btn btn-outline-secondary <?php echo !empty($array['scode']) ? 'disabled' : '' ?>" type="button" id="generateScode">Generate Code</button>
                                                                    </div>
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
            // Get the form reliably (fallback to the button's parent form if ID changes)
            const lockBtn = document.getElementById('lockButton');
            const form = document.getElementById('admission_admin') || lockBtn.closest('form');

            // Buttons to never disable inside the form
            const EXCLUDE = 'button[data-bs-target="#planHistoryModal"], #lockButton';

            function setLocked(locked) {
                // Disable everything except the excluded buttons
                const toToggle = form.querySelectorAll(
                    `input, select, textarea, button:not(${EXCLUDE})`
                );
                toToggle.forEach(el => {
                    el.disabled = locked;
                });

                // Make absolutely sure the excluded buttons stay enabled
                form.querySelectorAll(EXCLUDE).forEach(el => {
                    el.disabled = false;
                    el.classList.remove('disabled');
                    el.setAttribute('aria-disabled', 'false');
                });

                // Update state/text
                form.classList.toggle('locked', locked);
                lockBtn.textContent = locked ? 'Unlock Form' : 'Lock Form';
            }

            // Toggle on click
            lockBtn.addEventListener('click', function() {
                const nowLocked = !form.classList.contains('locked');
                setLocked(nowLocked);
            });

            // Start locked on load
            setLocked(true);
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
    <!-- Update Plan Modal -->
    <div class="modal fade" id="updatePlanModal" tabindex="-1" aria-labelledby="updatePlanModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updatePlanModalLabel">Update Student Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modal-division-select" class="form-label">Division:</label>
                        <select class="form-select" id="modal-division-select" required>
                            <option value="">--Select Division--</option>
                            <option value="kalpana" <?php echo (!empty($array['division']) && $array['division'] == 'kalpana') ? 'selected' : '' ?>>Kalpana Buds School</option>
                            <option value="rssi" <?php echo (!empty($array['division']) && $array['division'] == 'rssi') ? 'selected' : '' ?>>RSSI NGO</option>
                        </select>
                        <small class="form-text text-muted">Please select the division you're applying for.</small>
                    </div>

                    <!-- Class Dropdown -->
                    <div class="mb-3">
                        <label for="modal-class-select" class="form-label">Class:</label>
                        <select class="form-select" id="modal-class-select" name="class" required>
                            <?php if (empty($array['class'])) { ?>
                                <option value="" selected>--Select Class--</option>
                            <?php } else { ?>
                                <option value="">--Select Class--</option>
                                <option value="<?php echo $array['class']; ?>" selected><?php echo $array['class']; ?></option>
                            <?php } ?>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                        <small class="form-text text-muted" id="modal-class-help">Please select the class the student wants to join.</small>
                    </div>

                    <div class="mb-3">
                        <label for="modal-type-of-admission" class="form-label">Access Category:</label>
                        <select class="form-select" id="modal-type-of-admission" required>
                            <?php if (empty($array['type_of_admission'])) { ?>
                                <option value="" selected>--Select Access Category--</option>
                            <?php } else { ?>
                                <option value="">--Select Access Category--</option>
                                <option value="<?php echo $array['type_of_admission']; ?>" selected><?php echo $array['type_of_admission']; ?></option>
                            <?php } ?>
                        </select>
                        <small id="modal-type-of-admission-help" class="form-text text-muted">
                            Please select the type of access you are applying for.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="modal-effective-from-date" class="form-label">Effective From Month:</label>
                        <input type="month" class="form-control" id="modal-effective-from-date">
                        <small class="form-text text-muted">
                            The selected plan will be applied to the feesheet starting from the first day of the selected month.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save-plan-changes">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Plan History Modal -->
    <div class="modal fade" id="planHistoryModal" tabindex="-1" aria-labelledby="planHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="planHistoryModalLabel">Plan Change History for <?php echo htmlspecialchars($array['studentname']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Plan Type</th>
                                    <th>Class</th>
                                    <th>Effective From</th>
                                    <th>Effective Until</th>
                                    <th>Changed On</th>
                                    <th>Changed By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $historyQuery = "SELECT category_type, class, effective_from, effective_until, created_at, created_by 
                                           FROM student_category_history 
                                           WHERE student_id = '" . pg_escape_string($con, $array['student_id']) . "' 
                                           AND (is_valid = true OR is_valid IS NULL)
                                           ORDER BY created_at DESC";
                                $historyResult = pg_query($con, $historyQuery);
                                $today = date('Y-m-d');

                                if (pg_num_rows($historyResult) > 0) {
                                    while ($row = pg_fetch_assoc($historyResult)) {
                                        $effectiveFrom = $row['effective_from'];
                                        $effectiveUntil = $row['effective_until'];

                                        // Determine if this is the current active plan
                                        $isCurrent = false;
                                        if ($effectiveUntil === null) {
                                            // No end date - check if effective_from is in past
                                            $isCurrent = ($today >= $effectiveFrom);
                                        } else {
                                            // Has end date - check if today is within range
                                            $isCurrent = ($today >= $effectiveFrom && $today <= $effectiveUntil);
                                        }
                                        // Future plans should never be highlighted
                                        if ($today < $effectiveFrom) {
                                            $isCurrent = false;
                                        }
                                ?>
                                        <tr class="<?php echo $isCurrent ? 'table-primary' : '' ?>">
                                            <td><?php echo htmlspecialchars($row['category_type']) ?></td>
                                            <td><?php echo htmlspecialchars($row['class'] ?? $array['class']) ?></td>
                                            <td><?php echo date('d M Y', strtotime($effectiveFrom)) ?></td>
                                            <td>
                                                <?php echo $effectiveUntil === null ? '' : date('d M Y', strtotime($effectiveUntil)) ?>
                                            </td>
                                            <td><?php echo date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                                            <td><?php echo htmlspecialchars($row['created_by']) ?></td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center py-4">No plan history found</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            // When update modal opens, populate fields with current values
            $('#updatePlanModal').on('show.bs.modal', function() {
                $('#modal-division-select').val($('#division-select').val());
                $('#modal-class-select').val($('#class-select').val());
                $('#modal-type-of-admission').val($('#type-of-admission').val());
                $('#modal-effective-from-date').val($('#effective-from-date').val());

                // If division is already selected, load its classes and plans
                if ($('#modal-division-select').val()) {
                    loadClassesForDivision($('#modal-division-select').val());
                    loadPlansForDivision($('#modal-division-select').val());
                }
            });

            // When division changes, load corresponding classes and plans
            $('#modal-division-select').change(function() {
                const division = $(this).val();
                loadClassesForDivision(division);
                loadPlansForDivision(division);
            });

            function loadClassesForDivision(division) {
                const classSelect = $('#modal-class-select')[0];
                const helpText = $('#modal-class-help')[0];

                // Reset and disable the select initially
                classSelect.innerHTML = '<option value="" selected>--Select Class--</option>';
                classSelect.disabled = !division;

                if (!division) {
                    helpText.textContent = 'Please select a division first.';
                    return;
                }

                // Create and show loading spinner
                const spinner = document.createElement('span');
                spinner.className = 'spinner-border spinner-border-sm ms-2';
                spinner.setAttribute('role', 'status');
                spinner.setAttribute('aria-hidden', 'true');
                helpText.innerHTML = 'Loading classes... ';
                helpText.appendChild(spinner);

                // Determine API URL based on host
                const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
                const apiUrl = isLocalhost ?
                    'http://localhost:8082/get_classes.php' :
                    'https://login.rssi.in/get_classes.php';

                // Fetch classes via AJAX
                fetch(`${apiUrl}?division=${encodeURIComponent(division)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.error || 'Failed to load classes');
                        }

                        // Create new select with placeholder selected by default
                        classSelect.innerHTML = '<option value="" selected>--Select Class--</option>';

                        if (data.data.length === 0) {
                            const noOption = document.createElement('option');
                            noOption.textContent = 'No classes available';
                            noOption.disabled = true;
                            classSelect.appendChild(noOption);
                            helpText.textContent = 'No classes available for this division.';
                            return;
                        }

                        data.data.forEach(classItem => {
                            const option = document.createElement('option');
                            option.value = classItem.value;
                            option.textContent = classItem.class_name;
                            classSelect.appendChild(option);
                        });

                        // If there was a previous selection, try to preserve it
                        const previousSelection = $('#class-select').val();
                        if (previousSelection) {
                            // Check if the previous selection exists in the new options
                            const optionExists = Array.from(classSelect.options).some(
                                option => option.value === previousSelection
                            );
                            if (optionExists) {
                                $(classSelect).val(previousSelection);
                            }
                        }

                        classSelect.disabled = false;
                        helpText.textContent = 'Please select the class the student wants to join.';
                    })
                    .catch(error => {
                        console.error('Error fetching classes:', error);
                        classSelect.innerHTML = '<option value="" selected>--Select Class--</option>';
                        const errorOption = document.createElement('option');
                        errorOption.textContent = 'Error loading classes';
                        errorOption.disabled = true;
                        classSelect.appendChild(errorOption);
                        helpText.textContent = 'Failed to load classes. Please try again.';
                    })
                    .finally(() => {
                        classSelect.disabled = false;
                        // Remove spinner if it still exists
                        if (spinner.parentNode === helpText) {
                            helpText.removeChild(spinner);
                        }
                    });
            }

            // Keep your existing loadPlansForDivision function exactly as is
            function loadPlansForDivision(division) {
                const admissionSelect = $('#modal-type-of-admission')[0];
                const helpText = $('#modal-type-of-admission-help')[0];

                // Reset and disable the select initially
                admissionSelect.innerHTML = '<option value="" selected>--Select Access Category--</option>';
                admissionSelect.disabled = !division;

                if (!division) {
                    helpText.textContent = 'Please select the type of access you are applying for.';
                    return;
                }

                // Create and show loading spinner
                const spinner = document.createElement('span');
                spinner.className = 'spinner-border spinner-border-sm ms-2';
                spinner.setAttribute('role', 'status');
                spinner.setAttribute('aria-hidden', 'true');
                helpText.innerHTML = 'Loading plans... ';
                helpText.appendChild(spinner);

                // Determine API URL based on host
                const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
                const apiUrl = isLocalhost ?
                    'http://localhost:8082/get_plans.php' :
                    'https://login.rssi.in/get_plans.php';

                // Fetch plans via AJAX
                fetch(`${apiUrl}?division=${division}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(plans => {
                        // Create new select with placeholder selected by default
                        admissionSelect.innerHTML = '<option value="" selected>--Select Access Category--</option>';

                        if (plans.length === 0) {
                            const noOption = document.createElement('option');
                            noOption.textContent = 'No plans available';
                            noOption.disabled = true;
                            admissionSelect.appendChild(noOption);
                            helpText.textContent = 'No plans available for this division.';
                            return;
                        }

                        plans.forEach(plan => {
                            const option = document.createElement('option');
                            option.value = plan.name;
                            option.textContent = plan.name;
                            admissionSelect.appendChild(option);
                        });

                        // If there was a previous selection, try to preserve it
                        const previousSelection = $('#type-of-admission').val();
                        if (previousSelection) {
                            // Check if the previous selection exists in the new options
                            const optionExists = Array.from(admissionSelect.options).some(
                                option => option.value === previousSelection
                            );
                            if (optionExists) {
                                $(admissionSelect).val(previousSelection);
                            }
                        }

                        admissionSelect.disabled = false;
                        helpText.textContent = 'Please select the type of access you are applying for.';
                    })
                    .catch(error => {
                        console.error('Error fetching plans:', error);
                        admissionSelect.innerHTML = '<option value="" selected>--Select Access Category--</option>';
                        const errorOption = document.createElement('option');
                        errorOption.textContent = 'Error loading plans';
                        errorOption.disabled = true;
                        admissionSelect.appendChild(errorOption);
                        helpText.textContent = 'Failed to load plans. Please try again.';
                    })
                    .finally(() => {
                        admissionSelect.disabled = false;
                        // Remove spinner if it still exists
                        if (spinner.parentNode === helpText) {
                            helpText.removeChild(spinner);
                        }
                    });
            }

            // Keep your existing save changes and date handling code
            $('#save-plan-changes').click(function() {
                const division = $('#modal-division-select').val();
                const classVal = $('#modal-class-select').val();
                const admissionType = $('#modal-type-of-admission').val();
                const effectiveMonth = $('#modal-effective-from-date').val();

                if (!division || !classVal || !admissionType || !effectiveMonth) {
                    alert('Please fill all fields');
                    return;
                }

                const effectiveDate = effectiveMonth + '-01';

                $('#division-select').val(division);
                $('#class-select').val(classVal);
                $('#type-of-admission').val(admissionType);
                $('#effective-from-date').val(effectiveDate);

                $('#current-admission-display').text(classVal + '/' + admissionType);

                const [year, month] = effectiveMonth.split('-');
                const dateObj = new Date(year, month - 1);
                const monthName = dateObj.toLocaleString('default', {
                    month: 'long'
                });
                $('#current-effective-date-display').text(monthName + ' ' + year);

                $('#updatePlanModal').modal('hide');
            });

            // Set default to next month
            const today = new Date();
            const nextMonth = today.getMonth() === 11 ?
                `${today.getFullYear() + 1}-01` :
                `${today.getFullYear()}-${String(today.getMonth() + 2).padStart(2, '0')}`;

            if (!$('#effective-from-date').val()) {
                $('#modal-effective-from-date').val(nextMonth);
            }
        });
    </script>
    <script>
        function calculateAge(dobString) {
            const dob = new Date(dobString);
            if (isNaN(dob)) return '';
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const m = today.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            return age >= 0 ? `Age (as of today’s date): ${age} year${age !== 1 ? 's' : ''}` : '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const dobInput = document.getElementById('date-of-birth');
            const ageSpan = document.getElementById('age-display');

            function updateAgeDisplay() {
                const dobValue = dobInput.value;
                ageSpan.textContent = calculateAge(dobValue);
            }

            // Initial update (if DOB is pre-filled)
            updateAgeDisplay();

            // Update on change
            dobInput.addEventListener('input', updateAgeDisplay);
        });
    </script>
    <script>
        function handleStatusChange(selectElement) {
            const newStatus = selectElement.value;
            const effectiveFromInput = document.getElementById('effectivefrom');
            const remarksTextarea = document.getElementById('remarks');

            // Get current values
            const currentEffectiveFrom = effectiveFromInput.value;
            const currentRemarks = remarksTextarea.value;

            // Prepare the new remark line
            const today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format
            let newRemarkLine = `\n${today} - Status has been changed to ${newStatus}.`;

            // If changing to Active, reset effective from date but mention previous date in remarks
            if (newStatus === 'Active') {
                if (currentEffectiveFrom) {
                    newRemarkLine = `\nPrevious Effective From: ${currentEffectiveFrom}${newRemarkLine}`;
                }
                effectiveFromInput.value = ''; // Reset effective from date
            }
            // If changing to Inactive, set effective from date to today if empty
            else if (newStatus === 'Inactive' && !currentEffectiveFrom) {
                effectiveFromInput.value = today;
            }

            // Update remarks
            remarksTextarea.value = currentRemarks + newRemarkLine;
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const scodeInput = document.getElementById('scode');
            const generateBtn = document.getElementById('generateScode');

            // Disable generate button if scode already has a value
            if (scodeInput.value.trim() !== '') {
                generateBtn.disabled = true;
            }

            // Generate unique code when button is clicked
            generateBtn.addEventListener('click', function() {
                // Generate a unique code similar to PHP's uniqid()
                const uniqueCode = generateUniqueId();
                scodeInput.value = uniqueCode;
                generateBtn.disabled = true;
            });

            // Enable/disable generate button based on input changes
            scodeInput.addEventListener('input', function() {
                generateBtn.disabled = this.value.trim() !== '';
            });

            // Function to generate a unique ID similar to PHP's uniqid()
            function generateUniqueId() {
                // Get current timestamp in microseconds (similar to PHP)
                const now = new Date();
                const seconds = Math.floor(now.getTime() / 1000).toString(16);
                const microseconds = Math.floor(now.getMilliseconds() * 1000).toString(16).padStart(5, '0');

                // Combine with some randomness
                const randomPart = Math.floor(Math.random() * 1000000).toString(16).padStart(5, '0');

                return seconds + microseconds + randomPart;
            }
        });
    </script>
</body>

</html>