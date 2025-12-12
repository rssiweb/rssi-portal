<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

// Start output buffering to prevent header issues
ob_start();

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

// Define permission levels (higher number = higher permission)
$permission_levels = [
    'Admin' => 2,            // Highest level  
    'Offline Manager' => 1,  // Basic level
];

// Get current user's permission level
$current_user_level = isset($permission_levels[$role]) ? $permission_levels[$role] : 0;

// Define field access with minimum required level
$field_access_levels = [
    'admin_fields' => 2,        // Admin only
    'manager_fields' => 1,      // Offline Manager and above
];

// Define fields for each level
$field_definitions = [
    // Level 2: Admin only fields
    'admin_fields' => [
        'photourl',
    ],

    // Level 1: Manager and above fields
    'manager_fields' => [
        'studentname',
        'dateofbirth',
        'gender',
        'aadhar_available',
        'studentaadhar',
        'guardiansname',
        'relationwithstudent',
        'guardianaadhar',
        'stateofdomicile',
        'postaladdress',
        'permanentaddress',
        'contact',
        'alternate_number',
        'emergency_contact_name',
        'emergency_contact_number',
        'emergency_contact_relation',
        'emailaddress',
        'supporting_doc',
        'student_photo_raw',
        'upload_aadhar_card',
        'caste_document',
        'type_of_admission',
        'category',
        'module',
        'filterstatus',
        'remarks',
        'scode',
        'payment_type',
        'effectivefrom',
        'preferredbranch',
        'class',
        'schooladmissionrequired',
        'nameoftheschool',
        'nameoftheboard',
        'medium',
        'familymonthlyincome',
        'totalnumberoffamilymembers',
        'nameofthesubjects',
        'caste',
    ],
];

// Function to get field's required permission level
function getFieldLevel($field_name, $field_definitions, $field_access_levels)
{
    foreach ($field_definitions as $level_name => $fields) {
        if (in_array($field_name, $fields)) {
            return $field_access_levels[$level_name];
        }
    }
    return 0; // Default to user level
}

// Function to get all fields user can access
function getUserAccessibleFields($user_level, $field_definitions, $field_access_levels)
{
    $accessible_fields = [];

    foreach ($field_definitions as $level_name => $fields) {
        $required_level = $field_access_levels[$level_name];
        if ($user_level >= $required_level) {
            $accessible_fields = array_merge($accessible_fields, $fields);
        }
    }

    return array_unique($accessible_fields);
}

// Get user's accessible fields
$user_accessible_fields = getUserAccessibleFields($current_user_level, $field_definitions, $field_access_levels);

?>
<?php
$search_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;

// Fetch current student data
$sql = "SELECT * FROM rssimyprofile_student WHERE student_id = '$search_id'";
$result = pg_query($con, $sql);
$resultArr = pg_fetch_all($result);

if ($resultArr && count($resultArr) > 0) {
    $currentStudent = $resultArr[0];
}

// Close result
pg_free_result($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch existing student data
    $query = "SELECT * FROM rssimyprofile_student WHERE student_id = $1";
    $result = pg_query_params($con, $query, [$search_id]);

    if ($result && pg_num_rows($result) > 0) {
        $current_data = pg_fetch_assoc($result);
        $studentname = $current_data['studentname'];

        // Initialize arrays for updates
        $update_fields = [];
        $updated_fields = [];
        $unauthorized_updates = [];
        $pending_approval_fields = [];

        // Process each accessible field
        foreach ($user_accessible_fields as $field) {
            $is_file_field = in_array($field, ['student_photo_raw', 'upload_aadhar_card', 'caste_document', 'supporting_doc']);

            if ($is_file_field) {
                // Handle file upload
                $form_field_name = str_replace('_raw', '', $field); // student_photo_raw → student_photo
                if (!empty($_FILES[$form_field_name]['name'])) {
                    $timestamp = date('Y-m-d H:i:s');
                    $filename = $form_field_name . "_" . $search_id . "_" . $timestamp;

                    $parent_folders = [
                        'student_photo' => '1R1jZmG7xUxX_oaNJaT9gu68IV77zCbg9',
                        'upload_aadhar_card' => '186KMGzX07IohJUhQ72mfHQ6NHiIKV33E',
                        'caste_document' => '186KMGzX07IohJUhQ72mfHQ6NHiIKV33E',
                        'supporting_doc' => '1h2elj3V86Y65RFWkYtIXTJFMwG_KX_gC'
                    ];

                    $parent = $parent_folders[$form_field_name];
                    $doclink = uploadeToDrive($_FILES[$form_field_name], $parent, $filename);

                    if ($doclink) {
                        $new_value = $doclink;
                        $current_value = $current_data[$field] ?? null;

                        // Skip if no change
                        if ($new_value === $current_value) {
                            continue;
                        }

                        $field_level = getFieldLevel($field, $field_definitions, $field_access_levels);

                        if ($current_user_level >= $field_level) {
                            // Direct update allowed
                            $update_fields[] = "$field = '$new_value'";
                            $updated_fields[] = $field;
                        } elseif ($current_user_level >= 1) { // At least manager level
                            // Needs approval
                            $pending_approval_fields[] = $field;
                        } else {
                            $unauthorized_updates[] = $field;
                        }
                    }
                }
                continue; // Skip to next field
            }

            // Regular field processing
            if (isset($_POST[$field])) {
                $new_value = trim($_POST[$field]) === "" ? null : pg_escape_string($con, trim($_POST[$field]));
                $current_value = $current_data[$field] ?? null;

                // Skip if no change
                if ($new_value === $current_value) {
                    continue;
                }

                $field_level = getFieldLevel($field, $field_definitions, $field_access_levels);

                if ($current_user_level >= $field_level) {
                    // Direct update allowed
                    $update_fields[] = "$field = " . ($new_value === null ? "NULL" : "'$new_value'");
                    $updated_fields[] = $field;
                } elseif ($current_user_level >= 1) { // At least manager level
                    // Needs approval
                    $pending_approval_fields[] = $field;
                } else {
                    $unauthorized_updates[] = $field;
                }
            }
        }

        // Handle unauthorized updates
        if (!empty($unauthorized_updates)) {
            $unauthorized_list = implode(", ", $unauthorized_updates);
            echo "<script>
                alert('You are not authorized to update the following fields: $unauthorized_list');
                window.history.back();
            </script>";
            exit;
        }

        // Update database if there are changes
        if (!empty($update_fields)) {
            $update_sql = "UPDATE rssimyprofile_student SET " . implode(", ", $update_fields) .
                ", updated_by = '" . $associatenumber . "', updated_on = NOW() " .
                "WHERE student_id = '$search_id'";

            $update_result = pg_query($con, $update_sql);
            $cmdtuples = pg_affected_rows($update_result);
        }

        // Handle pending approval fields
        if (!empty($pending_approval_fields)) {
            $pending_fields_list = implode(", ", $pending_approval_fields);
            echo "<script>
                alert('Change request has been submitted for review: $pending_fields_list');
                window.location.reload();
            </script>";
            exit;
        }

        // Show success message
        if (isset($cmdtuples) && $cmdtuples > 0) {
            // Use mapping array (defined later)
            $updated_fields_readable = [];
            foreach ($updated_fields as $field) {
                $updated_fields_readable[] = isset($field_names_mapping[$field])
                    ? $field_names_mapping[$field]
                    : $field;
            }

            $updated_list = implode(", ", $updated_fields_readable);

            echo "<script>
                alert('The following fields were updated: " . addslashes($updated_list) . "');
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.href);
                }
                window.location.reload();
            </script>";
            exit;
        }

        if (empty($update_fields) && empty($pending_approval_fields)) {
            echo "<script>
                alert('No changes were made to the profile.');
                window.history.back();
            </script>";
            exit;
        }
    }
}
?>
<?php
// Define card access levels
$card_access_levels = [
    'basic_details' => 1,          // Manager and above
    'identification' => 1,
    'guardian_info' => 1,
    'address_details' => 1,
    'contact_info' => 1,
    'social_caste' => 1,
    'education_info' => 1,
    'family_info' => 1,
    'plan_enrollment' => 1,
    'student_status' => 1,
    'admin_documents' => 2,        // Admin only
];

// Get accessible cards for current user
$accessible_cards = [];
foreach ($card_access_levels as $card => $required_level) {
    if ($current_user_level >= $required_level) {
        $accessible_cards[] = $card;
    }
}

// Check if user is admin (for UI display)
$is_admin = $current_user_level >= 2;

// Add this mapping array
$field_names_mapping = [
    // Basic Details
    'student_id' => 'Student ID',
    'studentname' => 'Student Name',
    'dateofbirth' => 'Date of Birth',
    'gender' => 'Gender',
    'photourl' => 'Photo URL',
    'filterstatus' => 'Status',

    // Identification
    'aadhar_available' => 'Aadhar Card Available',
    'studentaadhar' => 'Aadhar Number',
    'student_photo_raw' => 'Student Photo',
    'upload_aadhar_card' => 'Aadhar Card Upload',

    // Plan & Enrollment
    'type_of_admission' => 'Access Category',
    'category' => 'Category',
    'module' => 'Module',
    'effectivefrom' => 'Effective From',
    'supporting_doc' => 'Supporting Document',
    'remarks' => 'Remarks',
    'scode' => 'Scode',
    'payment_type' => 'Payment Type',

    // Guardian Info
    'guardiansname' => 'Guardian Name',
    'relationwithstudent' => 'Relation with Student',
    'guardianaadhar' => 'Guardian Aadhar',
    'emergency_contact_name' => 'Emergency Contact Name',
    'emergency_contact_relation' => 'Emergency Contact Relation',
    'emergency_contact_number' => 'Emergency Contact Number',

    // Address Details
    'stateofdomicile' => 'State of Domicile',
    'postaladdress' => 'Current Address',
    'permanentaddress' => 'Permanent Address',

    // Contact Info
    'contact' => 'Telephone Number',
    'alternate_number' => 'Alternate Number',
    'emailaddress' => 'Email Address',

    // Social & Caste
    'caste' => 'Caste',
    'caste_document' => 'Caste Certificate',

    // Education Info
    'schooladmissionrequired' => 'School Admission Required',
    'nameoftheschool' => 'School Name',
    'nameoftheboard' => 'Board Name',
    'medium' => 'Medium',
    'preferredbranch' => 'Preferred Branch',
    'nameofthesubjects' => 'Subjects',
    'class' => 'Class',

    // Family Info
    'familymonthlyincome' => 'Family Monthly Income',
    'totalnumberoffamilymembers' => 'Total Family Members',

    // System fields
    'updated_by' => 'Updated By',
    'updated_on' => 'Updated On',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Reuse HRMS portal styles */
        .header_two {
            padding: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #d9d9d9;
            margin-right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #fff;
        }

        .profile-img-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .initials {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
        }

        .primary-details {
            flex-grow: 1;
        }

        .primary-details p {
            margin: 5px 0;
        }

        .contact-info {
            text-align: right;
        }

        .contact-info p {
            margin: 0;
        }

        .sidebar_two {
            min-width: 250px;
            background-color: #ffffff;
            border-right: 1px solid #d9d9d9;
            padding-top: 20px;
            height: 100vh;
        }

        #sidebar_two .nav-link {
            color: #31536C;
            padding: 10px 20px;
        }

        #sidebar_two .nav-link:hover,
        #sidebar_two .nav-link.active {
            background-color: #e7f1ff;
            border-left: 4px solid #31536C;
            color: #31536C;
        }

        .content {
            flex-grow: 1;
            background-color: white;
            padding: 20px;
        }

        .card {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #f8f9fa;
            color: black;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        .edit-icon,
        .save-icon {
            color: #d3d3d3;
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .edit-icon:hover,
        .save-icon:hover {
            color: #a0a0a0;
        }

        /* Media Queries */
        @media (max-width: 768px) {
            .header_two {
                flex-direction: column;
                align-items: flex-start;
            }

            .profile-img {
                width: 60px;
                height: 60px;
                margin-right: 10px;
            }

            .contact-info {
                text-align: left;
            }

            .sidebar_two {
                display: none;
            }
        }

        .accordion-body {
            padding: 0;
        }

        .accordion-button:not(.collapsed) {
            background-color: #e7f1ff;
            color: #31536C;
        }

        .form-check {
            margin-bottom: 10px;
        }
    </style>
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Student Profile</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="student.php">Student Database</a></li>
                    <li class="breadcrumb-item active">Student Profile</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <?php if (!$search_id): ?>
                                <div class="container mt-5">
                                    <h4 class="mb-3">Enter Student ID</h4>
                                    <form method="GET" action="">
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" name="student_id" placeholder="Enter Student ID" required>
                                            <button class="btn btn-primary" type="submit">Submit</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <?php if ($search_id && !$resultArr): ?>
                                <div class="alert alert-warning">
                                    No student found with ID: <?php echo htmlspecialchars($search_id); ?>
                                </div>
                            <?php endif; ?>

                            <?php foreach ($resultArr as $array): ?>
                                <div class="container-fluid">
                                    <!-- Header -->
                                    <div class="header_two">
                                        <div class="profile-img">
                                            <?php
                                            if (!empty($array['photourl'])) {
                                                $preview_url = $array['photourl'];
                                                echo '<img src="' . $preview_url . '" alt="Profile Image" class="profile-img-img">';
                                            } else {
                                                $name = $array['studentname'];
                                                $initials = strtoupper(substr($name, 0, 1) . substr(strrchr($name, ' '), 1, 1));
                                                echo '<span class="initials">' . $initials . '</span>';
                                            }
                                            ?>
                                        </div>

                                        <div class="primary-details">
                                            <p style="font-size: large;"><?php echo $array["studentname"] ?></p>
                                            <p><?php echo $array["student_id"] ?><br>
                                                <?php echo $array["class"] ?? 'Class not specified' ?><br>
                                                <?php echo $array["type_of_admission"] ?? 'Admission type not specified' ?></p>
                                        </div>
                                        <div class="contact-info">
                                            <p><?php echo $array["contact"] ?? 'No contact' ?></p>
                                            <p><?php echo $array["preferredbranch"] ?? 'Branch not specified' ?></p>
                                            <p><?php echo $array["emailaddress"] ?? 'No email' ?></p>
                                        </div>
                                    </div>

                                    <!-- Main Layout -->
                                    <div class="d-md-none accordion" id="mobileAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingMenu">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#menuCollapse" aria-expanded="false" aria-controls="menuCollapse">
                                                    Menu
                                                </button>
                                            </h2>
                                            <div id="menuCollapse" class="accordion-collapse collapse" aria-labelledby="headingMenu" data-bs-parent="#mobileAccordion">
                                                <div class="accordion-body">
                                                    <ul id="mobile-menu-items" class="nav flex-column"></ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex">
                                        <!-- Sidebar -->
                                        <div class="sidebar_two d-none d-md-block" id="sidebar_two">
                                            <ul class="nav flex-column" id="sidebar-menu">
                                                <?php if (in_array('basic_details', $accessible_cards)): ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link active" href="#basic_details" data-bs-toggle="tab">Basic Details</a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if (in_array('identification', $accessible_cards)): ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="#identification" data-bs-toggle="tab">Identification</a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if (in_array('plan_enrollment', $accessible_cards)): ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="#plan_enrollment" data-bs-toggle="tab">Plan & Enrollment</a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if (in_array('guardian_info', $accessible_cards)): ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="#guardian_info" data-bs-toggle="tab">Guardian Info</a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if (in_array('address_details', $accessible_cards)): ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="#address_details" data-bs-toggle="tab">Address Details</a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if (in_array('contact_info', $accessible_cards)): ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="#contact_info" data-bs-toggle="tab">Contact Info</a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if (in_array('social_caste', $accessible_cards)): ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="#social_caste" data-bs-toggle="tab">Social & Caste</a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if (in_array('education_info', $accessible_cards)): ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="#education_info" data-bs-toggle="tab">Education Info</a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if (in_array('family_info', $accessible_cards)): ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="#family_info" data-bs-toggle="tab">Family Info</a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if (in_array('student_status', $accessible_cards)): ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="#student_status" data-bs-toggle="tab">Student Status</a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if (in_array('admin_documents', $accessible_cards)): ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="#admin_documents" data-bs-toggle="tab">Admin Documents</a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>

                                        <!-- Content Area -->
                                        <div class="content tab-content container-fluid">
                                            <form name="studentProfileForm" id="studentProfileForm" action="#" method="post" enctype="multipart/form-data">
                                                <input type="hidden" name="student_id" value="<?php echo $search_id; ?>">

                                                <!-- Basic Details Tab -->
                                                <div id="basic_details" class="tab-pane active" role="tabpanel">
                                                    <div class="card" id="basic_details_card">
                                                        <div class="card-header">
                                                            Basic Details
                                                            <?php if (in_array('basic_details', $accessible_cards)): ?>
                                                                <span class="edit-icon" onclick="toggleEdit('basic_details_card')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" style="display:none;" onclick="saveChanges('basic_details_card')">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td><label for="student_id">Student ID:</label></td>
                                                                            <td>
                                                                                <span id="student_idText"><?php echo $array['student_id']; ?></span>
                                                                                <!-- No input field - this field is read-only -->
                                                                                <input type="hidden" name="student_id" id="student_id"
                                                                                    value="<?php echo $array['student_id']; ?>">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="studentname">Student Name:</label></td>
                                                                            <td>
                                                                                <span id="studentnameText"><?php echo $array['studentname']; ?></span>
                                                                                <input type="text" name="studentname" id="studentname"
                                                                                    value="<?php echo $array['studentname']; ?>"
                                                                                    class="form-control" disabled style="display:none;">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="dateofbirth">Date of Birth:</label></td>
                                                                            <td>
                                                                                <span id="dateofbirthText">
                                                                                    <?php echo !empty($array['dateofbirth']) ? date('d/m/Y', strtotime($array['dateofbirth'])) : ''; ?>
                                                                                </span>
                                                                                <input type="date" name="dateofbirth" id="dateofbirth"
                                                                                    value="<?php echo $array['dateofbirth']; ?>"
                                                                                    class="form-control" disabled style="display:none;">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="age">Age:</label></td>
                                                                            <td>
                                                                                <?php
                                                                                if (!empty($array['dateofbirth'])) {
                                                                                    $birthDate = new DateTime($array['dateofbirth']);
                                                                                    $today = new DateTime();
                                                                                    $age = $today->diff($birthDate)->y;
                                                                                    echo $age . " years";
                                                                                } else {
                                                                                    echo 'N/A';
                                                                                }
                                                                                ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="gender">Gender:</label></td>
                                                                            <td>
                                                                                <span id="genderText"><?php echo $array['gender']; ?></span>
                                                                                <select name="gender" id="gender" class="form-select" disabled style="display:none;">
                                                                                    <option value="">Select Gender</option>
                                                                                    <option value="Male" <?php echo $array['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                                                                    <option value="Female" <?php echo $array['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                                                                    <option value="Binary" <?php echo $array['gender'] == 'Binary' ? 'selected' : ''; ?>>Binary</option>
                                                                                </select>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="filterstatus">Status:</label></td>
                                                                            <td>
                                                                                <span id="filterstatusText">
                                                                                    <?php echo $array['filterstatus']; ?>
                                                                                </span>
                                                                                <?php if ($is_admin): ?>
                                                                                    <select name="filterstatus" id="filterstatus" class="form-select" disabled style="display:none;">
                                                                                        <option value="Active" <?php echo $array['filterstatus'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                                                                        <option value="Inactive" <?php echo $array['filterstatus'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                                                    </select>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="photourl">Photo URL:</label></td>
                                                                            <td>
                                                                                <span id="photourlText">
                                                                                    <?php if (!empty($array['photourl'])): ?>
                                                                                        <a href="<?php echo $array['photourl']; ?>" target="_blank">View Photo</a>
                                                                                    <?php else: ?>
                                                                                        No photo URL
                                                                                    <?php endif; ?>
                                                                                </span>
                                                                                <?php if ($is_admin): ?>
                                                                                    <input type="url" name="photourl" id="photourl"
                                                                                        value="<?php echo $array['photourl']; ?>"
                                                                                        class="form-control" disabled style="display:none;"
                                                                                        placeholder="Enter photo URL">
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Identification Tab -->
                                                <div id="identification" class="tab-pane" role="tabpanel">
                                                    <div class="card" id="identification_card">
                                                        <div class="card-header">
                                                            Identification
                                                            <?php if (in_array('identification', $accessible_cards)): ?>
                                                                <span class="edit-icon" onclick="toggleEdit('identification_card')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" style="display:none;" onclick="saveChanges('identification_card')">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td><label for="aadhar_available">Aadhar Card Available:</label></td>
                                                                            <td>
                                                                                <span id="aadhar_availableText"><?php echo $array['aadhar_available']; ?></span>
                                                                                <select name="aadhar_available" id="aadhar_available" class="form-select" disabled style="display:none;">
                                                                                    <option value="">Select</option>
                                                                                    <option value="Yes" <?php echo $array['aadhar_available'] == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                                                                    <option value="No" <?php echo $array['aadhar_available'] == 'No' ? 'selected' : ''; ?>>No</option>
                                                                                </select>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="studentaadhar">Aadhar of Student:</label></td>
                                                                            <td>
                                                                                <span id="studentaadharText"><?php echo $array['studentaadhar']; ?></span>
                                                                                <input type="text" name="studentaadhar" id="studentaadhar"
                                                                                    value="<?php echo $array['studentaadhar']; ?>"
                                                                                    class="form-control" disabled style="display:none;"
                                                                                    pattern="\d{12}" title="12-digit Aadhar number">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label>Upload Student Photo:</label></td>
                                                                            <td>
                                                                                <input type="file" name="student_photo" id="student_photo"
                                                                                    class="form-control" disabled style="display:none;"
                                                                                    accept="image/*">
                                                                                <?php if (!empty($array['student_photo_raw'])): ?>
                                                                                    <a href="<?php echo $array['student_photo_raw']; ?>" target="_blank">View Current Photo</a><br>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label>Upload Aadhar Card:</label></td>
                                                                            <td>
                                                                                <input type="file" name="upload_aadhar_card" id="upload_aadhar_card"
                                                                                    class="form-control" disabled style="display:none;"
                                                                                    accept=".pdf,.jpg,.jpeg,.png">
                                                                                <?php if (!empty($array['upload_aadhar_card'])): ?>
                                                                                    <a href="<?php echo $array['upload_aadhar_card']; ?>" target="_blank">View Aadhar Card</a><br>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Plan & Enrollment Tab (Admin only) -->
                                                <?php if ($is_admin): ?>
                                                    <div id="plan_enrollment" class="tab-pane" role="tabpanel">
                                                        <div class="card" id="plan_enrollment_card">
                                                            <div class="card-header">
                                                                Plan & Enrollment Information
                                                                <span class="edit-icon" onclick="toggleEdit('plan_enrollment_card')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" style="display:none;" onclick="saveChanges('plan_enrollment_card')">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label>Current Plan:</label></td>
                                                                                <td>
                                                                                    <?php
                                                                                    $currentCategory = !empty($array['class']) ? $array['class'] . '/' . $array['type_of_admission'] : 'Not selected';
                                                                                    echo $currentCategory;
                                                                                    ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="type_of_admission">Access Category:</label></td>
                                                                                <td>
                                                                                    <span id="type_of_admissionText"><?php echo $array['type_of_admission']; ?></span>
                                                                                    <select name="type_of_admission" id="type_of_admission" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select Category</option>
                                                                                        <option value="Basic" <?php echo $array['type_of_admission'] == 'Basic' ? 'selected' : ''; ?>>Basic</option>
                                                                                        <option value="Regular" <?php echo $array['type_of_admission'] == 'Regular' ? 'selected' : ''; ?>>Regular</option>
                                                                                        <option value="Premium" <?php echo $array['type_of_admission'] == 'Premium' ? 'selected' : ''; ?>>Premium</option>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="category">Category:</label></td>
                                                                                <td>
                                                                                    <span id="categoryText"><?php echo $array['category']; ?></span>
                                                                                    <select name="category" id="category" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select Category</option>
                                                                                        <option value="LG1" <?php echo $array['category'] == 'LG1' ? 'selected' : ''; ?>>LG1</option>
                                                                                        <option value="LG2-A" <?php echo $array['category'] == 'LG2-A' ? 'selected' : ''; ?>>LG2-A</option>
                                                                                        <option value="LG2-B" <?php echo $array['category'] == 'LG2-B' ? 'selected' : ''; ?>>LG2-B</option>
                                                                                        <option value="LG2-C" <?php echo $array['category'] == 'LG2-C' ? 'selected' : ''; ?>>LG2-C</option>
                                                                                        <option value="LG3" <?php echo $array['category'] == 'LG3' ? 'selected' : ''; ?>>LG3</option>
                                                                                        <option value="LG4" <?php echo $array['category'] == 'LG4' ? 'selected' : ''; ?>>LG4</option>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="module">Module:</label></td>
                                                                                <td>
                                                                                    <span id="moduleText"><?php echo $array['module']; ?></span>
                                                                                    <select name="module" id="module" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select Module</option>
                                                                                        <option value="National" <?php echo $array['module'] == 'National' ? 'selected' : ''; ?>>National</option>
                                                                                        <option value="State" <?php echo $array['module'] == 'State' ? 'selected' : ''; ?>>State</option>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="effectivefrom">Effective From (Plan):</label></td>
                                                                                <td>
                                                                                    <span id="effectivefromText">
                                                                                        <?php echo !empty($array['effectivefrom']) ? date('d/m/Y', strtotime($array['effectivefrom'])) : ''; ?>
                                                                                    </span>
                                                                                    <input type="date" name="effectivefrom" id="effectivefrom"
                                                                                        value="<?php echo $array['effectivefrom']; ?>"
                                                                                        class="form-control" disabled style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="supporting_doc">Supporting Document:</label></td>
                                                                                <td>
                                                                                    <input type="file" name="supporting_doc" id="supporting_doc"
                                                                                        class="form-control" disabled style="display:none;"
                                                                                        accept=".pdf,.jpg,.jpeg,.png">
                                                                                    <?php if (!empty($array['supporting_doc'])): ?>
                                                                                        <a href="<?php echo $array['supporting_doc']; ?>" target="_blank">View Document</a><br>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="remarks">Remarks:</label></td>
                                                                                <td>
                                                                                    <span id="remarksText"><?php echo $array['remarks']; ?></span>
                                                                                    <textarea name="remarks" id="remarks" class="form-control"
                                                                                        rows="3" disabled style="display:none;"><?php echo $array['remarks']; ?></textarea>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="scode">Scode:</label></td>
                                                                                <td>
                                                                                    <span id="scodeText"><?php echo $array['scode']; ?></span>
                                                                                    <input type="text" name="scode" id="scode"
                                                                                        value="<?php echo $array['scode']; ?>"
                                                                                        class="form-control" disabled style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="updated_by">Updated By:</label></td>
                                                                                <td>
                                                                                    <?php echo $array['updated_by']; ?> on <?php echo $array['updated_on']; ?>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Guardian Information Tab -->
                                                <div id="guardian_info" class="tab-pane" role="tabpanel">
                                                    <div class="card" id="guardian_info_card">
                                                        <div class="card-header">
                                                            Guardian / Parent Information
                                                            <?php if (in_array('guardian_info', $accessible_cards)): ?>
                                                                <span class="edit-icon" onclick="toggleEdit('guardian_info_card')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" style="display:none;" onclick="saveChanges('guardian_info_card')">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td><label for="guardiansname">Guardian's Name:</label></td>
                                                                            <td>
                                                                                <span id="guardiansnameText"><?php echo $array['guardiansname']; ?></span>
                                                                                <input type="text" name="guardiansname" id="guardiansname"
                                                                                    value="<?php echo $array['guardiansname']; ?>"
                                                                                    class="form-control" disabled style="display:none;">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="relationwithstudent">Relation with Student:</label></td>
                                                                            <td>
                                                                                <span id="relationwithstudentText"><?php echo $array['relationwithstudent']; ?></span>
                                                                                <select name="relationwithstudent" id="relationwithstudent" class="form-select" disabled style="display:none;">
                                                                                    <option value="">Select Relation</option>
                                                                                    <option value="Mother" <?php echo $array['relationwithstudent'] == 'Mother' ? 'selected' : ''; ?>>Mother</option>
                                                                                    <option value="Father" <?php echo $array['relationwithstudent'] == 'Father' ? 'selected' : ''; ?>>Father</option>
                                                                                    <option value="Spouse" <?php echo $array['relationwithstudent'] == 'Spouse' ? 'selected' : ''; ?>>Spouse</option>
                                                                                    <option value="Other" <?php echo $array['relationwithstudent'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                                                                </select>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="guardianaadhar">Aadhar of Guardian:</label></td>
                                                                            <td>
                                                                                <span id="guardianaadharText"><?php echo $array['guardianaadhar']; ?></span>
                                                                                <input type="text" name="guardianaadhar" id="guardianaadhar"
                                                                                    value="<?php echo $array['guardianaadhar']; ?>"
                                                                                    class="form-control" disabled style="display:none;"
                                                                                    pattern="\d{12}" title="12-digit Aadhar number">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="emergency_contact_name">Emergency Contact Name:</label></td>
                                                                            <td>
                                                                                <span id="emergency_contact_nameText"><?php echo $array['emergency_contact_name']; ?></span>
                                                                                <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                                                                                    value="<?php echo $array['emergency_contact_name']; ?>"
                                                                                    class="form-control" disabled style="display:none;">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="emergency_contact_relation">Relation:</label></td>
                                                                            <td>
                                                                                <span id="emergency_contact_relationText"><?php echo $array['emergency_contact_relation']; ?></span>
                                                                                <select name="emergency_contact_relation" id="emergency_contact_relation" class="form-select" disabled style="display:none;">
                                                                                    <option value="">Select Relation</option>
                                                                                    <option value="Father" <?php echo $array['emergency_contact_relation'] == 'Father' ? 'selected' : ''; ?>>Father</option>
                                                                                    <option value="Mother" <?php echo $array['emergency_contact_relation'] == 'Mother' ? 'selected' : ''; ?>>Mother</option>
                                                                                    <option value="Guardian" <?php echo $array['emergency_contact_relation'] == 'Guardian' ? 'selected' : ''; ?>>Guardian</option>
                                                                                    <option value="Brother" <?php echo $array['emergency_contact_relation'] == 'Brother' ? 'selected' : ''; ?>>Brother</option>
                                                                                    <option value="Sister" <?php echo $array['emergency_contact_relation'] == 'Sister' ? 'selected' : ''; ?>>Sister</option>
                                                                                    <option value="Other" <?php echo $array['emergency_contact_relation'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                                                                </select>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="emergency_contact_number">Emergency Contact Number:</label></td>
                                                                            <td>
                                                                                <span id="emergency_contact_numberText"><?php echo $array['emergency_contact_number']; ?></span>
                                                                                <input type="tel" name="emergency_contact_number" id="emergency_contact_number"
                                                                                    value="<?php echo $array['emergency_contact_number']; ?>"
                                                                                    class="form-control" disabled style="display:none;"
                                                                                    pattern="\d{10}" maxlength="10">
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Address Details Tab -->
                                                <div id="address_details" class="tab-pane" role="tabpanel">
                                                    <div class="card" id="address_details_card">
                                                        <div class="card-header">
                                                            Address Details
                                                            <?php if (in_array('address_details', $accessible_cards)): ?>
                                                                <span class="edit-icon" onclick="toggleEdit('address_details_card')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" style="display:none;" onclick="saveChanges('address_details_card')">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td><label for="stateofdomicile">State of Domicile:</label></td>
                                                                            <td>
                                                                                <span id="stateofdomicileText"><?php echo $array['stateofdomicile']; ?></span>
                                                                                <select name="stateofdomicile" id="stateofdomicile" class="form-select" disabled style="display:none;">
                                                                                    <option value="">Select State</option>
                                                                                    <option value="Uttar Pradesh" <?php echo $array['stateofdomicile'] == 'Uttar Pradesh' ? 'selected' : ''; ?>>Uttar Pradesh</option>
                                                                                    <option value="West Bengal" <?php echo $array['stateofdomicile'] == 'West Bengal' ? 'selected' : ''; ?>>West Bengal</option>
                                                                                    <!-- Add more states as needed -->
                                                                                </select>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="postaladdress">Current Address:</label></td>
                                                                            <td>
                                                                                <span id="postaladdressText"><?php echo $array['postaladdress']; ?></span>
                                                                                <textarea name="postaladdress" id="postaladdress" class="form-control"
                                                                                    rows="3" disabled style="display:none;"><?php echo $array['postaladdress']; ?></textarea>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="permanentaddress">Permanent Address:</label></td>
                                                                            <td>
                                                                                <span id="permanentaddressText"><?php echo $array['permanentaddress']; ?></span>
                                                                                <textarea name="permanentaddress" id="permanentaddress" class="form-control"
                                                                                    rows="3" disabled style="display:none;"><?php echo $array['permanentaddress']; ?></textarea>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td colspan="2">
                                                                                <div class="form-check" style="display:none;" id="sameAddressCheckbox">
                                                                                    <input class="form-check-input" type="checkbox" id="same_address"
                                                                                        onclick="copyAddress()">
                                                                                    <label class="form-check-label" for="same_address">
                                                                                        Same as current address
                                                                                    </label>
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Contact Information Tab -->
                                                <div id="contact_info" class="tab-pane" role="tabpanel">
                                                    <div class="card" id="contact_info_card">
                                                        <div class="card-header">
                                                            Contact Information
                                                            <?php if (in_array('contact_info', $accessible_cards)): ?>
                                                                <span class="edit-icon" onclick="toggleEdit('contact_info_card')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" style="display:none;" onclick="saveChanges('contact_info_card')">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td><label for="contact">Telephone Number:</label></td>
                                                                            <td>
                                                                                <span id="contactText"><?php echo $array['contact']; ?></span>
                                                                                <input type="tel" name="contact" id="contact"
                                                                                    value="<?php echo $array['contact']; ?>"
                                                                                    class="form-control" disabled style="display:none;"
                                                                                    pattern="\d{10}" maxlength="10">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="alternate_number">Alternate Number:</label></td>
                                                                            <td>
                                                                                <span id="alternate_numberText"><?php echo $array['alternate_number']; ?></span>
                                                                                <input type="tel" name="alternate_number" id="alternate_number"
                                                                                    value="<?php echo $array['alternate_number']; ?>"
                                                                                    class="form-control" disabled style="display:none;"
                                                                                    pattern="\d{10}" maxlength="10">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="emailaddress">Email Address:</label></td>
                                                                            <td>
                                                                                <span id="emailaddressText"><?php echo $array['emailaddress']; ?></span>
                                                                                <input type="email" name="emailaddress" id="emailaddress"
                                                                                    value="<?php echo $array['emailaddress']; ?>"
                                                                                    class="form-control" disabled style="display:none;">
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Social & Caste Tab -->
                                                <div id="social_caste" class="tab-pane" role="tabpanel">
                                                    <div class="card" id="social_caste_card">
                                                        <div class="card-header">
                                                            Social & Caste Information
                                                            <?php if (in_array('social_caste', $accessible_cards)): ?>
                                                                <span class="edit-icon" onclick="toggleEdit('social_caste_card')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" style="display:none;" onclick="saveChanges('social_caste_card')">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td><label for="caste">Caste:</label></td>
                                                                            <td>
                                                                                <span id="casteText"><?php echo $array['caste']; ?></span>
                                                                                <select name="caste" id="caste" class="form-select" disabled style="display:none;">
                                                                                    <option value="">Select Caste</option>
                                                                                    <option value="General" <?php echo $array['caste'] == 'General' ? 'selected' : ''; ?>>General</option>
                                                                                    <option value="SC" <?php echo $array['caste'] == 'SC' ? 'selected' : ''; ?>>Scheduled Caste (SC)</option>
                                                                                    <option value="ST" <?php echo $array['caste'] == 'ST' ? 'selected' : ''; ?>>Scheduled Tribe (ST)</option>
                                                                                    <option value="OBC" <?php echo $array['caste'] == 'OBC' ? 'selected' : ''; ?>>Other Backward Class (OBC)</option>
                                                                                    <option value="EWS" <?php echo $array['caste'] == 'EWS' ? 'selected' : ''; ?>>Economically Weaker Section (EWS)</option>
                                                                                </select>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label>Caste Certificate:</label></td>
                                                                            <td>
                                                                                <input type="file" name="caste_document" id="caste_document"
                                                                                    class="form-control" disabled style="display:none;"
                                                                                    accept=".pdf,.jpg,.jpeg,.png">
                                                                                <?php if (!empty($array['caste_document'])): ?>
                                                                                    <a href="<?php echo $array['caste_document']; ?>" target="_blank">View Certificate</a><br>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Education Information Tab -->
                                                <div id="education_info" class="tab-pane" role="tabpanel">
                                                    <div class="card" id="education_info_card">
                                                        <div class="card-header">
                                                            Education Information
                                                            <?php if (in_array('education_info', $accessible_cards)): ?>
                                                                <span class="edit-icon" onclick="toggleEdit('education_info_card')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" style="display:none;" onclick="saveChanges('education_info_card')">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td><label for="schooladmissionrequired">School Admission Required:</label></td>
                                                                            <td>
                                                                                <span id="schooladmissionrequiredText"><?php echo $array['schooladmissionrequired']; ?></span>
                                                                                <select name="schooladmissionrequired" id="schooladmissionrequired" class="form-select" disabled style="display:none;">
                                                                                    <option value="">Select</option>
                                                                                    <option value="Yes" <?php echo $array['schooladmissionrequired'] == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                                                                    <option value="No" <?php echo $array['schooladmissionrequired'] == 'No' ? 'selected' : ''; ?>>No</option>
                                                                                </select>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="nameoftheschool">Name of the School:</label></td>
                                                                            <td>
                                                                                <span id="nameoftheschoolText"><?php echo $array['nameoftheschool']; ?></span>
                                                                                <input type="text" name="nameoftheschool" id="nameoftheschool"
                                                                                    value="<?php echo $array['nameoftheschool']; ?>"
                                                                                    class="form-control" disabled style="display:none;">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="nameoftheboard">Name of the Board:</label></td>
                                                                            <td>
                                                                                <span id="nameoftheboardText"><?php echo $array['nameoftheboard']; ?></span>
                                                                                <select name="nameoftheboard" id="nameoftheboard" class="form-select" disabled style="display:none;">
                                                                                    <option value="">Select Board</option>
                                                                                    <option value="CBSE" <?php echo $array['nameoftheboard'] == 'CBSE' ? 'selected' : ''; ?>>CBSE</option>
                                                                                    <option value="ICSE" <?php echo $array['nameoftheboard'] == 'ICSE' ? 'selected' : ''; ?>>ICSE</option>
                                                                                    <option value="State Board" <?php echo $array['nameoftheboard'] == 'State Board' ? 'selected' : ''; ?>>State Board</option>
                                                                                </select>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="medium">Medium:</label></td>
                                                                            <td>
                                                                                <span id="mediumText"><?php echo $array['medium']; ?></span>
                                                                                <select name="medium" id="medium" class="form-select" disabled style="display:none;">
                                                                                    <option value="">Select Medium</option>
                                                                                    <option value="English" <?php echo $array['medium'] == 'English' ? 'selected' : ''; ?>>English</option>
                                                                                    <option value="Hindi" <?php echo $array['medium'] == 'Hindi' ? 'selected' : ''; ?>>Hindi</option>
                                                                                    <option value="Other" <?php echo $array['medium'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                                                                </select>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="preferredbranch">Preferred Branch:</label></td>
                                                                            <td>
                                                                                <span id="preferredbranchText"><?php echo $array['preferredbranch']; ?></span>
                                                                                <select name="preferredbranch" id="preferredbranch" class="form-select" disabled style="display:none;">
                                                                                    <option value="">Select Branch</option>
                                                                                    <option value="Lucknow" <?php echo $array['preferredbranch'] == 'Lucknow' ? 'selected' : ''; ?>>Lucknow</option>
                                                                                    <option value="West Bengal" <?php echo $array['preferredbranch'] == 'West Bengal' ? 'selected' : ''; ?>>West Bengal</option>
                                                                                </select>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="nameofthesubjects">Select Subjects:</label></td>
                                                                            <td>
                                                                                <span id="nameofthesubjectsText"><?php echo $array['nameofthesubjects']; ?></span>
                                                                                <select name="nameofthesubjects" id="nameofthesubjects" class="form-select" disabled style="display:none;">
                                                                                    <option value="">Select Subject</option>
                                                                                    <option value="ALL Subjects" <?php echo $array['nameofthesubjects'] == 'ALL Subjects' ? 'selected' : ''; ?>>ALL Subjects</option>
                                                                                    <option value="English" <?php echo $array['nameofthesubjects'] == 'English' ? 'selected' : ''; ?>>English</option>
                                                                                    <option value="Embroidery" <?php echo $array['nameofthesubjects'] == 'Embroidery' ? 'selected' : ''; ?>>Embroidery</option>
                                                                                </select>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Family Information Tab -->
                                                <div id="family_info" class="tab-pane" role="tabpanel">
                                                    <div class="card" id="family_info_card">
                                                        <div class="card-header">
                                                            Family Information
                                                            <?php if (in_array('family_info', $accessible_cards)): ?>
                                                                <span class="edit-icon" onclick="toggleEdit('family_info_card')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" style="display:none;" onclick="saveChanges('family_info_card')">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="table-responsive">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td><label for="familymonthlyincome">Family Monthly Income:</label></td>
                                                                            <td>
                                                                                <span id="familymonthlyincomeText"><?php echo $array['familymonthlyincome']; ?></span>
                                                                                <input type="number" name="familymonthlyincome" id="familymonthlyincome"
                                                                                    value="<?php echo $array['familymonthlyincome']; ?>"
                                                                                    class="form-control" disabled style="display:none;">
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="totalnumberoffamilymembers">Total Family Members:</label></td>
                                                                            <td>
                                                                                <span id="totalnumberoffamilymembersText"><?php echo $array['totalnumberoffamilymembers']; ?></span>
                                                                                <input type="number" name="totalnumberoffamilymembers" id="totalnumberoffamilymembers"
                                                                                    value="<?php echo $array['totalnumberoffamilymembers']; ?>"
                                                                                    class="form-control" disabled style="display:none;">
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Student Status Tab (Admin only) -->
                                                <?php if ($is_admin): ?>
                                                    <div id="student_status" class="tab-pane" role="tabpanel">
                                                        <div class="card" id="student_status_card">
                                                            <div class="card-header">
                                                                Student Status
                                                                <span class="edit-icon" onclick="toggleEdit('student_status_card')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" style="display:none;" onclick="saveChanges('student_status_card')">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="payment_type">Payment Type:</label></td>
                                                                                <td>
                                                                                    <span id="payment_typeText"><?php echo $array['payment_type']; ?></span>
                                                                                    <select name="payment_type" id="payment_type" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select Type</option>
                                                                                        <option value="Advance" <?php echo $array['payment_type'] == 'Advance' ? 'selected' : ''; ?>>Advance</option>
                                                                                        <option value="Regular" <?php echo $array['payment_type'] == 'Regular' ? 'selected' : ''; ?>>Regular</option>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label>Date of Application:</label></td>
                                                                                <td>
                                                                                    <?php echo date('d/m/Y', strtotime($array['doa'])); ?>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Admin Documents Tab (Admin only) -->
                                                <?php if ($is_admin): ?>
                                                    <div id="admin_documents" class="tab-pane" role="tabpanel">
                                                        <div class="card" id="admin_documents_card">
                                                            <div class="card-header">
                                                                Admin Documents
                                                                <span class="edit-icon" onclick="toggleEdit('admin_documents_card')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" style="display:none;" onclick="saveChanges('admin_documents_card')">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td colspan="2">
                                                                                    <h6>Document Links:</h6>
                                                                                    <ul>
                                                                                        <li>
                                                                                            <a href="student-profile.php?get_id=<?php echo $array['student_id']; ?>" target="_blank">
                                                                                                Admission Form
                                                                                            </a>
                                                                                        </li>
                                                                                        <li>
                                                                                            <a href="pdf_application.php?student_id=<?php echo $array['student_id']; ?>" target="_blank">
                                                                                                Retention Form
                                                                                            </a>
                                                                                        </li>
                                                                                    </ul>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        // Mobile menu setup (similar to HRMS)
        document.addEventListener('DOMContentLoaded', () => {
            const desktopMenu = document.querySelector('#sidebar-menu');
            const mobileMenu = document.querySelector('#mobile-menu-items');
            const accordionButton = document.querySelector('#headingMenu button');

            if (desktopMenu) {
                const menuItems = desktopMenu.innerHTML;
                mobileMenu.innerHTML = menuItems;

                // Add click handlers for mobile menu
                mobileMenu.querySelectorAll('.nav-link').forEach(link => {
                    link.addEventListener('click', event => {
                        event.preventDefault();
                        const tabTarget = event.target.getAttribute('href');

                        // Activate the tab
                        const tabElement = document.querySelector(`[href="${tabTarget}"]`);
                        if (tabElement) {
                            const tab = new bootstrap.Tab(tabElement);
                            tab.show();
                        }

                        // Update accordion button text
                        accordionButton.innerHTML = `Menu: ${event.target.innerText}`;

                        // Collapse the accordion
                        const accordion = document.querySelector('#menuCollapse');
                        const bootstrapCollapse = new bootstrap.Collapse(accordion, {
                            toggle: true,
                        });
                    });
                });
            }
        });

        // Edit/Save toggle function
        function toggleEdit(cardId) {
            const card = document.getElementById(cardId);
            const isEditing = card.classList.contains('editing');

            if (isEditing) {
                // Switch to view mode
                card.classList.remove('editing');
                card.querySelectorAll('input, select, textarea').forEach(element => {
                    element.disabled = true;
                    // For file inputs, hide them completely
                    if (element.type === 'file') {
                        element.style.display = 'none';
                    } else {
                        element.style.display = 'none';
                    }
                });
                // Show all text spans
                card.querySelectorAll('span[id$="Text"]').forEach(span => {
                    span.style.display = 'inline';
                });
                // Show edit icon, hide save
                card.querySelector('.edit-icon').style.display = 'inline';
                card.querySelector('.save-icon').style.display = 'none';

            } else {
                // Switch to edit mode
                card.classList.add('editing');
                card.querySelectorAll('input, select, textarea').forEach(element => {
                    element.disabled = false;
                    // For file inputs, show them as block
                    if (element.type === 'file') {
                        element.style.display = 'block';
                    } else if (element.type !== 'hidden') {
                        element.style.display = 'inline-block';
                    }
                });
                // Hide text spans (except for read-only fields)
                card.querySelectorAll('span[id$="Text"]').forEach(span => {
                    const fieldName = span.id.replace('Text', '');
                    const hasEditableField = card.querySelector(`[name="${fieldName}"]:not([type="hidden"])`);
                    if (hasEditableField) {
                        span.style.display = 'none';
                    }
                });
                // Hide edit icon, show save
                card.querySelector('.edit-icon').style.display = 'none';
                card.querySelector('.save-icon').style.display = 'inline';

                // Show same address checkbox in edit mode for address section
                const sameAddressCheckbox = document.getElementById('sameAddressCheckbox');
                if (sameAddressCheckbox && cardId === 'address_details_card') {
                    sameAddressCheckbox.style.display = 'block';
                }
            }
        }

        // Save changes function
        function saveChanges(cardId) {
            document.getElementById('studentProfileForm').submit();
        }

        // Copy address function
        function copyAddress() {
            const currentAddress = document.getElementById('postaladdress');
            const permanentAddress = document.getElementById('permanentaddress');
            const checkbox = document.getElementById('same_address');

            if (checkbox.checked) {
                permanentAddress.value = currentAddress.value;
            }
        }

        // Tab URL handling (similar to HRMS)
        document.addEventListener('DOMContentLoaded', function() {
            function activateTab(tabId) {
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('active');
                });

                const targetPane = document.getElementById(tabId);
                if (targetPane) {
                    targetPane.classList.add('active');
                }

                document.querySelectorAll('#sidebar-menu .nav-link, #mobile-menu-items .nav-link').forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${tabId}`) {
                        link.classList.add('active');
                    }
                });
            }

            // Handle tab clicks
            document.querySelectorAll('.nav-link[data-bs-toggle="tab"]').forEach(link => {
                link.addEventListener('click', function(e) {
                    const tabId = this.getAttribute('href').substring(1);
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tabId);
                    window.history.pushState({
                        tab: tabId
                    }, '', url);
                });
            });

            // Check for tab parameter on page load
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');

            if (tabParam) {
                activateTab(tabParam);
            }

            // Handle browser back/forward
            window.addEventListener('popstate', function(event) {
                if (event.state && event.state.tab) {
                    activateTab(event.state.tab);
                }
            });
        });
    </script>
</body>

</html>