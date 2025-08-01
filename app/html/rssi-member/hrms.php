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
?>
<?php
$search_id = ($role === 'Admin')
    ? (isset($_GET['associatenumber']) ? $_GET['associatenumber'] : null)
    : $associatenumber;

// Deny access if non-Admin user manipulates the URL
if ($role !== 'Admin' && isset($_GET['associatenumber']) && $_GET['associatenumber'] !== $associatenumber) {
    echo "<script>
    alert('You are not authorized to view this data.');
    window.location.href = 'hrms.php?associatenumber=$associatenumber';
</script>";
    exit;
}


// Step 1: Fetch current associate data (this part remains the same)
$sql = "SELECT 
    m.*,
    o.security_deposit_amount,
    o.security_deposit_currency,
    o.security_deposit_transaction_id,
    ae.security_refund
FROM 
    rssimyaccount_members m
LEFT JOIN 
    onboarding o ON o.onboarding_associate_id = m.associatenumber
LEFT JOIN 
    associate_exit ae ON ae.exit_associate_id = m.associatenumber
WHERE 
    m.associatenumber = '$search_id';
";
$result = pg_query($con, $sql);
$resultArr = pg_fetch_all($result);

// Ensure data is fetched correctly
if ($resultArr && count($resultArr) > 0) {
    $currentAssociate = $resultArr[0]; // Assuming we fetch one record
    $supervisorID = $currentAssociate['supervisor']; // Fetch the supervisor ID
}

// Step 2: Fetch active managers data
$sql_managers = "SELECT associatenumber, fullname FROM rssimyaccount_members WHERE filterstatus = 'Active'";
$result_managers = pg_query($con, $sql_managers);

// Check if there are any active managers
if ($result_managers && pg_num_rows($result_managers) > 0) {
    $managersArr = pg_fetch_all($result_managers);
} else {
    echo "No active managers found.";
    exit;
}

// Step 3: Fetch the supervisor's full name (can be inactive)
if (!empty($supervisorID)) {
    $sql_supervisor = "SELECT fullname FROM rssimyaccount_members WHERE associatenumber = '$supervisorID'";
    $result_supervisor = pg_query($con, $sql_supervisor);

    if ($result_supervisor && pg_num_rows($result_supervisor) > 0) {
        $supervisor = pg_fetch_assoc($result_supervisor);
        $supervisorFullName = $supervisor['fullname'];
    }
}

// Close the result resources
pg_free_result($result);
pg_free_result($result_managers);
if (isset($result_supervisor)) {
    pg_free_result($result_supervisor);
}
?>
<?php
// SQL query to fetch active designation
$sql = "SELECT designation, grade FROM designation WHERE is_inactive = FALSE ORDER BY designation ASC";
$result = pg_query($con, $sql);

// Check if the query was successful
if ($result) {
    $roles = pg_fetch_all($result);
} else {
    echo "An error occurred while fetching roles.";
    exit;
}
?>
<?php
// Query the database to get the available roles (excluding inactive roles)
$query = "SELECT role_name FROM roles WHERE is_inactive = FALSE";
$result = pg_query($con, $query);

// Generate the role options
$role_options = [];
if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        $role_options[] = $row['role_name'];
    }
}
?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Fetch existing values for the associatenumber
    $query = "SELECT * FROM rssimyaccount_members WHERE associatenumber = $1";
    $result = pg_query_params($con, $query, [$search_id]);

    if ($result && pg_num_rows($result) > 0) {
        $current_data = pg_fetch_assoc($result); // Initialize $current_data here
        $fullname = $current_data['fullname'];
        $requestedby_email = $current_data['email'];

        // Check if the user is updating eduq or mjorsub
        $isUpdatingEduq = isset($_POST['eduq']) && trim($_POST['eduq']) !== $current_data['eduq'];
        $isUpdatingMjorsub = isset($_POST['mjorsub']) && trim($_POST['mjorsub']) !== $current_data['mjorsub'];

        // If updating eduq or mjorsub, enforce checkbox validation
        if (($isUpdatingEduq || $isUpdatingMjorsub) && !isset($_POST['markSheetCheckbox'])) {
            echo "<script>
                alert('Please confirm that you have uploaded the mark sheet by checking the checkbox.');
                window.history.back();
            </script>";
            exit;
        }

        // Check if the user is updating workexperience
        $isUpdatingWorkexperience = isset($_POST['workexperience']) && trim($_POST['workexperience']) !== $current_data['workexperience'];

        // Check if the user is updating caste and the new value is not "General"
        $isUpdatingCaste = isset($_POST['caste']) && trim($_POST['caste']) !== $current_data['caste'];
        $isCasteNotGeneral = $isUpdatingCaste && strtolower(trim($_POST['caste'])) !== 'general';

        // If updating workexperience, enforce checkbox validation for supporting document
        if ($isUpdatingWorkexperience && !isset($_POST['workexperienceDocumentCheckbox'])) {
            echo "<script>
                          alert('Please confirm that you have uploaded the supporting document for Relevant Previous Experience by checking the checkbox.');
                          window.history.back();
                      </script>";
            exit;
        }

        // If updating caste and the new value is not "General", enforce checkbox validation for caste certificate
        if ($isCasteNotGeneral && !isset($_POST['casteCertificateCheckbox'])) {
            echo "<script>
                          alert('If you have selected a caste other than General, please confirm that you have uploaded a valid caste certificate by checking the checkbox.');
                          window.history.back();
                      </script>";
            exit;
        }
        // Initialize arrays to store the parts of the UPDATE query
        $update_fields = [];
        $updated_fields = []; // To track which fields were updated
        $unauthorized_updates = []; // To track unauthorized update attempts
        $pending_approval_fields = []; // Initialize to track fields for pending approval

        // Define field groups
        $admin_only_fields = [
            'doj',
            'basebranch',
            'depb',
            'engagement',
            'job_type',
            'position',
            'class',
            'role',
            'supervisor',
            'filterstatus',
            'salary',
            'absconding',
            'shift',
            'effectivedate',
            'remarks',
            'scode',
            'photo',
            'security_deposit'
        ];

        $user_editable_fields = [
            'workexperience',
            'eduq',
            'mjorsub',
            'phone',
            'alt_phone',
            'email',
            'alt_email',
            'currentaddress',
            'permanentaddress',
            'panno',
            'linkedin',
            'religion',
            'caste',
            'father_name',
            'mother_name',
            'blood_group',
            'emergency_contact1',
            'emergency_contact2',
            'contact_person1',
            'contact_person2',
            'college_name',
            'enrollment_number'
        ];

        $fields_requiring_approval = [
            'workexperience',
            'eduq',
            'mjorsub',
            'phone',
            'alt_phone',
            'email',
            'alt_email',
            'panno',
            'raw_photo',
            'college_name',
            'enrollment_number'
        ];

        // Process each field once
        foreach (array_merge($admin_only_fields, $user_editable_fields) as $field) {
            // Special handling for textarea fields and absconding
            if ($field === 'absconding') {
                $new_value = isset($_POST['absconding']) && $_POST['absconding'] === "Yes" ? "Yes" : null;
                $current_value = $current_data[$field];
            } elseif (isset($_POST[$field])) {
                $new_value = trim($_POST[$field]) === "" ? null : pg_escape_string($con, trim($_POST[$field]));
                $current_value = $current_data[$field];
            } else {
                continue; // Skip fields not included in the form
            }

            // Skip updates if the value hasn't changed
            if ($new_value === $current_value) {
                continue;
            }

            // Admin and user role validation as before
            if ($role === 'Admin' && in_array($field, $admin_only_fields)) {
                if ($search_id === $associatenumber) {
                    $unauthorized_updates[] = $field; // Admin cannot update their own data
                } else {
                    $update_fields[] = "$field = " . ($new_value === null ? "NULL" : "'$new_value'");
                    $updated_fields[] = $field;
                }
            } elseif ($search_id === $associatenumber && in_array($field, $user_editable_fields)) {
                if (in_array($field, $fields_requiring_approval)) {
                    // Track pending approval fields
                    $pending_approval_fields[] = $field;
                } else {
                    $update_fields[] = "$field = " . ($new_value === null ? "NULL" : "'$new_value'");
                    $updated_fields[] = $field;
                }
            } else {
                $unauthorized_updates[] = $field;
            }
        }
        // Special handling for 'position' to get and compare the grade
        if (isset($_POST['position']) && $role === 'Admin') {
            $position = pg_escape_string($con, $_POST['position']);
            $query = "SELECT grade FROM designation WHERE designation = $1 AND is_inactive = FALSE";
            $result = pg_query_params($con, $query, [$position]);

            if ($result && pg_num_rows($result) > 0) {
                $row = pg_fetch_assoc($result);
                $grade = $row['grade'];
                if ($grade !== $current_data['grade']) {
                    $update_fields[] = "grade = '$grade'";
                    $updated_fields[] = "grade";
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

        // If there are fields to update, build the query and execute it
        if (!empty($update_fields)) {
            $update_sql = "UPDATE rssimyaccount_members SET " . implode(", ", $update_fields) . " WHERE associatenumber = '$search_id'";

            $update_result = pg_query($con, $update_sql);
            $cmdtuples = pg_affected_rows($update_result);
        }

        // Insert pending approval fields into the workflow table
        foreach ($pending_approval_fields as $field) {
            $new_value = pg_escape_string($con, $_POST[$field]);

            // Check the status of the most recent request for this field
            $check_status_query = "SELECT reviewer_status FROM hrms_workflow 
                      WHERE associatenumber = $1 
                      AND fieldname = $2 
                      ORDER BY submission_timestamp DESC
                      LIMIT 1";
            $check_status_result = pg_query_params($con, $check_status_query, [$search_id, $field]);

            if (pg_num_rows($check_status_result) > 0) {
                $row = pg_fetch_assoc($check_status_result);
                $latest_status = $row['reviewer_status'];

                if ($latest_status === 'Pending') {
                    // Remove this field from pending_approval_fields as it already has a pending request
                    $key = array_search($field, $pending_approval_fields);
                    if ($key !== false) {
                        unset($pending_approval_fields[$key]);
                    }

                    // Show alert for this specific field
                    echo "<script>
                alert('You already have a pending request for $field. Please wait for approval or rejection before submitting a new request.');
            </script>";
                    continue;
                }
            }

            // If no pending request exists, proceed with insertion

            $workflow_query = "INSERT INTO hrms_workflow (associatenumber, fieldname, submitted_value, submission_timestamp, reviewer_status) VALUES ($1, $2, $3, NOW(), 'Pending')";
            $workflow_result = pg_query_params($con, $workflow_query, [$search_id, $field, $new_value]);

            if (!$workflow_result) {
                echo "<script>
                    alert('An error occurred while submitting the change request for the field: $field.');
                    window.history.back();
                </script>";
                exit;
            }
        }

        // Re-index the array after potential removals
        $pending_approval_fields = array_values($pending_approval_fields);

        // Show the alert for pending approval fields outside the $update_result check
        if (!empty($pending_approval_fields)) {
            $pending_fields_list = implode(", ", $pending_approval_fields);
            if (!empty($requestedby_email)) {
                sendEmail("hrms_workflow", [
                    "associatenumber" => $search_id,
                    "fullname" => $fullname,
                    "pending_fields_list" => $pending_fields_list,
                    "now" => date("d/m/Y g:i a"),
                ], $requestedby_email);
            }
            echo "<script>
                alert('Change request has been successfully submitted for the following fields: $pending_fields_list. These fields are under review for approval.');
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.href);
                }
                window.location.reload();
            </script>";
            exit;
        }

        // Handle the success or failure of the update operation
        if (!empty($update_fields)) {
            if (isset($cmdtuples) && $cmdtuples == 1) {
                echo "<script>
                alert('The following fields were updated: " . implode(", ", $updated_fields) . "');
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.href);
                }
                window.location.reload();
            </script>";
            } else {
                // No changes made
                echo '<script>
                    alert("Error: We encountered an error while updating the record. Please try again.");
                  </script>';
            }
        } else {
            // Failure: Error occurred
            echo "<script>
                alert('No changes were made to your profile.');
                window.history.back();
            </script>";
        }
    }
}
ob_end_flush();
?>

<?php
// Define card access based on role
$card_access = [
    'Admin' => ['current-location', 'current_project', 'roles', 'compensation', 'employee_status', 'admin_console', 'miscellaneous'], // Admin can edit these cards
];

// Determine accessible cards for Admin
$accessible_cards = isset($card_access[$role]) ? $card_access[$role] : [];

// Non-Admin specific logic
if ($search_id === $associatenumber) {
    $accessible_cards = ['address_details', 'national_identifier', 'religion-caste', 'qualification', 'experience', 'social', 'emergency_contacts']; // Non-Admin can edit these cards only for their own data
}
?>
<?php
// Query to get the latest submission status for each fieldname per associatenumber, excluding older 'Pending' requests
$query = "
    SELECT workflow.fieldname, workflow.reviewer_status
    FROM hrms_workflow AS workflow
    WHERE workflow.reviewer_status = 'Pending'
      AND workflow.associatenumber = $1
      AND workflow.workflow_id = (
          SELECT workflow_id
          FROM hrms_workflow
          WHERE associatenumber = workflow.associatenumber 
            AND fieldname = workflow.fieldname
          ORDER BY submission_timestamp DESC
          LIMIT 1
      )
    ORDER BY workflow.fieldname
";

$result = pg_query_params($con, $query, [$search_id]);

$pendingFields = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $pendingFields[] = [
            'fieldname' => $row['fieldname'],
            'status' => $row['reviewer_status']
        ];
    }
}

// Pass filtered pendingFields to JavaScript
echo "<script>
    var pendingFields = " . json_encode($pendingFields) . ";
</script>";
?>

<script>
    // Pass the PHP array of pending fields to JavaScript
    var pendingFields = <?php echo json_encode($pendingFields); ?>;
</script>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .header_two {
            /* background-color: #31536C; */
            /* color: white; */
            padding: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            /* Allows wrapping on small screens */
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
            /* font-weight: bold; */
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
            /* Light flat gray color */
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .edit-icon:hover,
        .save-icon:hover {
            color: #a0a0a0;
            /* Darker gray on hover for a subtle effect */
        }

        /* Media Queries */

        /* Mobile View */
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

            .menu-icon {
                display: block;
                font-size: 1.5rem;
                color: #005b96;
                cursor: pointer;
            }

            .content {
                padding: 15px;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            /* Hide sidebar and display menu button on mobile */
            .sidebar_two {
                display: none;
            }

            .btn-link {
                text-decoration: none;
                margin: 10px;
            }
        }

        .floating-dropdown {
            position: absolute;
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            padding: 10px;
            max-width: 300px;
            z-index: 1000;
            overflow-y: auto;
            max-height: 400px;
            font-size: 0.85em;
            /* Slightly smaller than the default system font */
        }

        .hierarchy-item {
            display: flex;
            align-items: center;
            padding: 5px 0;
        }

        .icon-container img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            /* Ensures the image is circular */
        }

        .hierarchy-item .profile-pic {
            width: 65px;
            height: 40px;
            border-radius: 50%;
            /* Circular shape for initials */
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #ddd;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            color: #555;
            margin-right: 10px;
        }
    </style>
    <style>
        /* Button initially hidden but layout reserved */
        #generateButton {
            visibility: hidden;
            /* Invisible but layout preserved */
        }

        /* Button visible */
        #generateButton.visible {
            visibility: visible;
            /* Show button */
        }
    </style>
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Custom CSS to make Select2 look like an input field */
        .select2-container .select2-selection--single {
            height: 38px;
            /* Match Bootstrap input height */
            border: 1px solid #ced4da;
            /* Match Bootstrap input border */
            border-radius: 0.25rem;
            /* Match Bootstrap input border radius */
            padding: 0.375rem 0.75rem;
            /* Match Bootstrap input padding */
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            /* Align arrow with input height */
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            /* Align text vertically */
            padding-left: 0;
            /* Remove extra padding */
        }
    </style>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#associatenumber').select2({
                ajax: {
                    url: 'fetch_associates.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term // Search term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 1, // Require at least 1 character to start searching
                placeholder: "Enter Associate Number", // Placeholder text
                allowClear: true // Allow clearing the selection
            });

            // Make Select2 look like an input field
            $('#associatenumber').on('select2:open', function() {
                document.querySelector('.select2-search__field').focus();
            });
        });
    </script>
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Profile</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active">Profile</li>
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
                            if (!$search_id): ?>
                                <div class="container mt-5">
                                    <h4 class="mb-3">Enter Associate Number</h4>
                                    <form method="GET" action="">
                                        <div class="input-group mb-3">
                                            <!-- Replace the input with a Select2 dropdown -->
                                            <select class="form-control select2" id="associatenumber" name="associatenumber" required>
                                                <option value="">Enter Associate Number</option>
                                                <?php if ($selectedAssociate): ?>
                                                    <option value="<?= htmlspecialchars($selectedAssociate) ?>" selected>
                                                        <?= htmlspecialchars($selectedAssociate) ?>
                                                    </option>
                                                <?php endif; ?>
                                            </select>
                                            <button class="btn btn-primary" type="submit">Submit</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <?php foreach ($resultArr as $array) { ?>
                                <div class="container-fluid">

                                    <!-- Header -->
                                    <div class="header_two">
                                        <div class="profile-img">
                                            <?php
                                            if (!empty($array['photo'])) {
                                                $preview_url = $array['photo'];
                                                echo '<img src="' . $preview_url . '" alt="Profile Image" class="profile-img-img">';
                                            } else {
                                                $name = $array['fullname'];
                                                $initials = strtoupper(substr($name, 0, 1) . substr(strrchr($name, ' '), 1, 1)); // Get initials
                                                echo '<span class="initials">' . $initials . '</span>';
                                            }
                                            ?>
                                        </div>

                                        <div class="primary-details">
                                            <p style="font-size: large;"><?php echo $array["fullname"] ?></p>
                                            <p><?php echo $array["associatenumber"] ?><br><?php echo $array["engagement"] ?><br>Designation: <?php echo $array["position"] ?></p>
                                            <!-- Wrapper for both dropdowns -->
                                            <div class="dropdowns-container d-flex gap-3">
                                                <!-- View Hierarchy clickable text with down arrow -->
                                                <div class="dropdown-container" id="viewHierarchyContainer">
                                                    <span id="viewHierarchyBtn" data-associate="<?php echo $array['associatenumber']; ?>" style="cursor: pointer;">
                                                        View Hierarchy
                                                        <span class="down-arrow" style="font-size: 16px; margin-left: 5px;">
                                                            <i class="bi bi-chevron-down"></i>
                                                        </span> <!-- Down arrow -->
                                                    </span>

                                                    <div id="hierarchyDropdown" class="floating-dropdown" style="display: none;">
                                                        <!-- Hierarchy will be dynamically populated here -->
                                                    </div>
                                                </div>

                                                <!-- View Reportees clickable text with down arrow -->
                                                <div class="dropdown-container" id="viewReporteesContainer">
                                                    <span id="viewReporteesBtn" data-associate="<?php echo $array['associatenumber']; ?>" style="cursor: pointer;">
                                                        View Reportees
                                                        <span class="down-arrow" style="font-size: 16px; margin-left: 5px;">
                                                            <i class="bi bi-chevron-down"></i>
                                                        </span> <!-- Down arrow -->
                                                    </span>

                                                    <div id="reporteesDropdown" class="floating-dropdown" style="display: none;">
                                                        <!-- Reportees will be dynamically populated here -->
                                                    </div>
                                                </div>
                                            </div>


                                        </div>
                                        <div class="contact-info">
                                            <p><?php echo $array["phone"] ?></p>
                                            <p><?php echo $array["basebranch"] ?></p>
                                            <p><?php echo $array["email"] ?></p>
                                        </div>
                                    </div>

                                    <!-- Main Layout -->
                                    <!-- Accordion for Mobile (Visible Only on Small Screens) -->
                                    <div class="d-md-none accordion" id="mobileAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingMenu">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#menuCollapse" aria-expanded="false" aria-controls="menuCollapse">
                                                    Menu
                                                </button>
                                            </h2>
                                            <div id="menuCollapse" class="accordion-collapse collapse" aria-labelledby="headingMenu" data-bs-parent="#mobileAccordion">
                                                <div class="accordion-body">
                                                    <ul id="mobile-menu-items" class="nav flex-column">
                                                        <!-- Menu items will be injected here dynamically -->
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex">
                                        <!-- sidebar_two -->
                                        <!-- Container for Menu Items (Written Only Once) -->
                                        <ul id="menu-items" class="d-none">
                                            <li class="nav-item">
                                                <a class="nav-link active" href="#employee-details" data-bs-toggle="tab">Employee Details</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" href="#work-details" data-bs-toggle="tab">Work Details</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" href="#qualifications_experience" data-bs-toggle="tab">Qualifications and Experience</a>
                                            </li>
                                            <!-- <li class="nav-item">
                                                <a class="nav-link" href="#published-documents" data-bs-toggle="tab">Learnings</a>
                                            </li> -->
                                            <li class="nav-item">
                                                <a class="nav-link" href="#status_compensation" data-bs-toggle="tab">Status & Compensation</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" href="#social" data-bs-toggle="tab">Social</a>
                                            </li>
                                            <?php if ($role === 'Admin'): ?>
                                                <li class="nav-item">
                                                    <a class="nav-link" href="#admin_console" data-bs-toggle="tab">Admin console</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>

                                        <!-- Sidebar for Desktop (Visible Only on Medium and Larger Screens) -->
                                        <div class="sidebar_two d-none d-md-block" id="sidebar_two">
                                            <ul class="nav flex-column" id="sidebar-menu">
                                                <!-- Menu items will be inserted here by JavaScript -->
                                            </ul>
                                        </div>

                                        <!-- Content Area -->

                                        <div class="content tab-content container-fluid">
                                            <form name="signup" id="signup" action="#" method="post" enctype="multipart/form-data">
                                                <fieldset>
                                                    <!-- Employee Details Tab -->
                                                    <div id="employee-details" class="tab-pane active" role="tabpanel">
                                                        <div class="card" id="address_details">
                                                            <div class="card-header">
                                                                Communication Details
                                                                <?php if (in_array('address_details', $accessible_cards)) : ?>
                                                                    <span class="edit-icon" onclick="toggleEdit('address_details')"><i class="bi bi-pencil"></i></span>
                                                                    <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                        <i class="bi bi-save"></i>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="phone">Telephone Number:</label></td>
                                                                                <td>
                                                                                    <span id="phoneText"><?php echo $array['phone']; ?></span>
                                                                                    <input class="form-control" type="text" name="phone" id="phone"
                                                                                        value="<?php echo $array['phone']; ?>" disabled style="display:none;"
                                                                                        maxlength="10" pattern="\d{10}" title="Enter a valid 10-digit phone number"
                                                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="alt_phone">Telephone Number (Alt):</label></td>
                                                                                <td>
                                                                                    <span id="alt_phoneText"><?php echo $array['alt_phone']; ?></span>
                                                                                    <input class="form-control" type="text" name="alt_phone" id="alt_phone"
                                                                                        value="<?php echo $array['alt_phone']; ?>" disabled style="display:none;"
                                                                                        maxlength="10" pattern="\d{10}" title="Enter a valid 10-digit alternate phone number"
                                                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="email">Email Address:</label></td>
                                                                                <td>
                                                                                    <span id="emailText"><?php echo $array['email']; ?></span>
                                                                                    <input class="form-control" type="email" name="email" id="email" value="<?php echo $array['email']; ?>" disabled style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="alt_email">Email Address (Alt):</label></td>
                                                                                <td>
                                                                                    <span id="alt_emailText"><?php echo $array['alt_email']; ?></span>
                                                                                    <input class="form-control" type="email" name="alt_email" id="alt_email" value="<?php echo $array['alt_email']; ?>" disabled style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="currentaddress">Current Address:</label></td>
                                                                                <td>
                                                                                    <span id="currentAddressText"><?php echo $array['currentaddress']; ?></span>
                                                                                    <textarea name="currentaddress" id="currentaddress" class="form-control" rows="3" disabled style="display: none;"><?php echo !empty($array['currentaddress']) ? $array['currentaddress'] : ''; ?></textarea>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="permanentaddress">Permanent Address:</label></td>
                                                                                <td>
                                                                                    <span id="permanentAddressText"><?php echo $array['permanentaddress']; ?></span>
                                                                                    <textarea name="permanentaddress" id="permanentaddress" class="form-control" rows="3" disabled style="display: none;"><?php echo !empty($array['permanentaddress']) ? $array['permanentaddress'] : ''; ?></textarea>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card" id="national_identifier">
                                                            <div class="card-header">
                                                                Personal Details
                                                                <?php if (in_array('national_identifier', $accessible_cards)) : ?>
                                                                    <span class="edit-icon" onclick="toggleEdit('national_identifier')">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </span>
                                                                    <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                        <i class="bi bi-save"></i>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="dateofbirth">Date of Birth:</label></td>
                                                                                <td>
                                                                                    <?php echo !empty($array["dateofbirth"]) ? (new DateTime($array["dateofbirth"]))->format("d/m/Y") : ''; ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="nationalidentifier">National Identifier Number:</label></td>
                                                                                <td>
                                                                                    <?php echo $array["nationalidentifier"] ?><br>
                                                                                    <?php echo $array["identifier"] ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- PAN Number -->
                                                                            <tr>
                                                                                <td><label for="panno">PAN Number:</label></td>
                                                                                <td>
                                                                                    <span id="pannoText"><?php echo $array['panno']; ?></span>
                                                                                    <input type="text" name="panno" id="panno" placeholder="Enter your PAN number" value="<?php echo $array["panno"]; ?>" disabled class="form-control" style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Father's Name -->
                                                                            <tr>
                                                                                <td><label for="father_name">Father's Name:</label></td>
                                                                                <td>
                                                                                    <span id="fatherNameText"><?php echo $array['father_name']; ?></span>
                                                                                    <input type="text" name="father_name" id="father_name" placeholder="Enter your Father's Name" value="<?php echo $array['father_name']; ?>" disabled class="form-control" style="display:none;">
                                                                                </td>
                                                                            </tr>

                                                                            <!-- Mother's Name -->
                                                                            <tr>
                                                                                <td><label for="mother_name">Mother's Name:</label></td>
                                                                                <td>
                                                                                    <span id="motherNameText"><?php echo $array['mother_name']; ?></span>
                                                                                    <input type="text" name="mother_name" id="mother_name" placeholder="Enter your Mother's Name" value="<?php echo $array['mother_name']; ?>" disabled class="form-control" style="display:none;">
                                                                                </td>
                                                                            </tr>

                                                                            <!-- Blood Group -->
                                                                            <tr>
                                                                                <td><label for="blood_group">Blood Group:</label></td>
                                                                                <td>
                                                                                    <span id="bloodGroupText"><?php echo $array['blood_group']; ?></span>
                                                                                    <select name="blood_group" id="blood_group" class="form-select" style="display:none;" disabled>
                                                                                        <option value="">Select Blood Group</option>
                                                                                        <?php
                                                                                        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                                                                        foreach ($bloodGroups as $group) {
                                                                                            $selected = ($array['blood_group'] == $group) ? 'selected' : '';
                                                                                            echo "<option value=\"$group\" $selected>$group</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>

                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>


                                                        <div class="card" id="emergency_contacts">
                                                            <div class="card-header">
                                                                Emergency Contact Details
                                                                <?php if (in_array('emergency_contacts', $accessible_cards)) : ?>
                                                                    <span class="edit-icon" onclick="toggleEdit('emergency_contacts')">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </span>
                                                                    <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                        <i class="bi bi-save"></i>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <!-- Emergency Contact Set 1 -->
                                                                            <tr>
                                                                                <td><label for="contact_person1">Emergency Contact Person 1:</label></td>
                                                                                <td>
                                                                                    <span id="contactPerson1Text"><?php echo $array['contact_person1']; ?></span>
                                                                                    <input type="text" name="contact_person1" id="contact_person1"
                                                                                        placeholder="Name of Contact Person 1"
                                                                                        value="<?php echo $array['contact_person1']; ?>"
                                                                                        disabled class="form-control" style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="emergency_contact1">Emergency Contact Number 1:</label></td>
                                                                                <td>
                                                                                    <span id="emergencyContact1Text"><?php echo $array['emergency_contact1']; ?></span>
                                                                                    <input type="text" name="emergency_contact1" id="emergency_contact1"
                                                                                        placeholder="Contact Number 1"
                                                                                        value="<?php echo $array['emergency_contact1']; ?>"
                                                                                        disabled class="form-control" style="display:none;"
                                                                                        maxlength="10" pattern="\d{10}" title="Enter a valid 10-digit contact number"
                                                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
                                                                                </td>
                                                                            </tr>

                                                                            <!-- Toggle Button to Show/Hide Second Emergency Contact (only shown when contact2 is empty in view mode) -->
                                                                            <tr id="toggleSecondContactRow" <?php echo (!empty($array['contact_person2'])) ? 'style="display:none;"' : ''; ?>>
                                                                                <td colspan="2">
                                                                                    <button type="button" class="btn btn-link" id="addSecondContact" onclick="showSecondEmergencyContact()">
                                                                                        <i class="bi bi-plus-circle"></i> Show Second Emergency Contact
                                                                                    </button>
                                                                                </td>
                                                                            </tr>

                                                                            <!-- Emergency Contact Set 2 (shown if data exists or in edit mode) -->
                                                                            <tr id="secondContactRow1"
                                                                                <?php echo (empty($array['contact_person2'])) ? 'style="display:none;"' : '' ?>>
                                                                                <td><label for="contact_person2">Emergency Contact Person 2:</label></td>
                                                                                <td>
                                                                                    <span id="contactPerson2Text"><?php echo $array['contact_person2']; ?></span>
                                                                                    <input type="text" name="contact_person2" id="contact_person2"
                                                                                        placeholder="Name of Contact Person 2"
                                                                                        value="<?php echo $array['contact_person2']; ?>"
                                                                                        disabled class="form-control" style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                            <tr id="secondContactRow2"
                                                                                <?php echo (empty($array['contact_person2'])) ? 'style="display:none;"' : '' ?>>
                                                                                <td><label for="emergency_contact2">Emergency Contact Number 2:</label></td>
                                                                                <td>
                                                                                    <span id="emergencyContact2Text"><?php echo $array['emergency_contact2']; ?></span>
                                                                                    <input type="text" name="emergency_contact2" id="emergency_contact2"
                                                                                        placeholder="Contact Number 2"
                                                                                        value="<?php echo $array['emergency_contact2']; ?>"
                                                                                        disabled class="form-control" style="display:none;"
                                                                                        maxlength="10" pattern="\d{10}" title="Enter a valid 10-digit contact number"
                                                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="card" id="religion-caste">
                                                            <div class="card-header">
                                                                Religion and Caste Details
                                                                <?php if (in_array('religion-caste', $accessible_cards)) : ?>
                                                                    <span class="edit-icon" onclick="toggleEdit('religion-caste')">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </span>
                                                                    <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                        <i class="bi bi-save"></i>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="religion">Religion:</label></td>
                                                                                <td>
                                                                                    <span id="religionText"><?php echo $array['religion']; ?></span>
                                                                                    <select name="religion" id="religion" disabled class="form-select" style="display:none;">
                                                                                        <option disabled selected>Select Religion</option>
                                                                                        <?php
                                                                                        // List of Religions
                                                                                        $religion_select = [
                                                                                            "Hinduism",
                                                                                            "Islam",
                                                                                            "Christianity",
                                                                                            "Sikhism",
                                                                                            "Buddhism",
                                                                                            "Jainism",
                                                                                            "Zoroastrianism",
                                                                                            "Judaism",
                                                                                            "Bahá'í Faith",
                                                                                            "Others"
                                                                                        ];

                                                                                        // Generate <option> elements dynamically for Religion
                                                                                        foreach ($religion_select as $religion) {
                                                                                            // Compare $array["religion"] with $religion (not $branch)
                                                                                            $selected = ($array["religion"] == $religion) ? "selected" : "";
                                                                                            echo "<option value=\"$religion\" $selected>$religion</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>

                                                                            <tr>
                                                                                <td><label for="caste">Caste:</label></td>
                                                                                <td>
                                                                                    <span id="casteText"><?php echo $array['caste']; ?></span>
                                                                                    <select name="caste" id="caste" disabled class="form-select" style="display:none;">
                                                                                        <option value="" disabled selected>Select Caste</option>
                                                                                        <?php
                                                                                        // List of castes with value as key and display name as value
                                                                                        $caste_select = [
                                                                                            "General" => "General",
                                                                                            "SC" => "Scheduled Caste (SC)",
                                                                                            "ST" => "Scheduled Tribe (ST)",
                                                                                            "OBC" => "Other Backward Class (OBC)",
                                                                                            "EWS" => "Economically Weaker Section (EWS)",
                                                                                            "Prefer not to disclose" => "Prefer not to disclose"
                                                                                        ];

                                                                                        // Generate <option> elements dynamically
                                                                                        foreach ($caste_select as $value => $display) {
                                                                                            $selected = (isset($array["caste"]) && $array["caste"] == $value) ? "selected" : "";
                                                                                            echo "<option value=\"$value\" $selected>$display</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Checkbox for Caste Certificate -->
                                                                            <tr id="casteCertificateRow" style="display: none;">
                                                                                <td></td>
                                                                                <td>
                                                                                    <div id="casteCertificateSection" style="display: none;">
                                                                                        <div class="form-check" style="display: none;">
                                                                                            <input class="form-check-input" type="checkbox" value="" id="casteCertificateCheckbox" name="casteCertificateCheckbox" required>
                                                                                            <label class="form-check-label" for="casteCertificateCheckbox">
                                                                                                I have uploaded a valid caste certificate.
                                                                                            </label>
                                                                                        </div>
                                                                                        <!-- Help text with link -->
                                                                                        <div class="form-text mt-2">
                                                                                            If you have not uploaded the caste certificate yet, <a href="digital_archive.php" target="_blank">click here to upload</a>.
                                                                                        </div>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Work Details Tab -->
                                                    <div id="work-details" class="tab-pane" role="tabpanel">
                                                        <div class="card" id="current-location">
                                                            <div class="card-header">
                                                                Current Location
                                                                <?php if (in_array('current-location', $accessible_cards)) : ?>
                                                                    <span class="edit-icon" onclick="toggleEdit('current-location')">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </span>
                                                                    <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                        <i class="bi bi-save"></i>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="basebranch">Base Branch:</label></td>
                                                                                <td>
                                                                                    <span id="baseBranchText"><?php echo $array['basebranch']; ?></span>
                                                                                    <select name="basebranch" id="basebranch" disabled class="form-select" style="display:none;">
                                                                                        <option disabled selected>Select Base Branch</option>
                                                                                        <?php
                                                                                        // List of Base Branches
                                                                                        $base_branches = ["Lucknow", "West Bengal"];
                                                                                        // Generate <option> elements dynamically for Base Branch
                                                                                        foreach ($base_branches as $branch) {
                                                                                            $selected = ($array["basebranch"] == $branch) ? "selected" : "";
                                                                                            echo "<option value=\"$branch\" $selected>$branch</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>

                                                                            <tr>
                                                                                <td><label for="depb">Deputed Branch:</label></td>
                                                                                <td>
                                                                                    <span id="deputedBranchText"><?php echo $array['depb']; ?></span>
                                                                                    <select name="depb" id="depb" disabled class="form-select" style="display:none;">
                                                                                        <option disabled selected>Select Deputed Branch</option>
                                                                                        <?php
                                                                                        // List of Deputed Branches
                                                                                        $deputed_branches = ["Lucknow", "West Bengal"];
                                                                                        // Generate <option> elements dynamically for Deputed Branch
                                                                                        foreach ($deputed_branches as $branch) {
                                                                                            $selected = ($array["depb"] == $branch) ? "selected" : "";
                                                                                            echo "<option value=\"$branch\" $selected>$branch</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card" id="current_project">
                                                            <div class="card-header">
                                                                Current Project
                                                                <?php if (in_array('current_project', $accessible_cards)) : ?>
                                                                    <span class="edit-icon" onclick="toggleEdit('current_project')">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </span>
                                                                    <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                        <i class="bi bi-save"></i>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <!-- Date of Join -->
                                                                            <tr>
                                                                                <td><label for="doj">Date of Join:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <span id="doj"><?php echo !empty($array["doj"]) ? (new DateTime($array["doj"]))->format("d/m/Y") : ''; ?></span>
                                                                                    <input type="date" name="doj" id="doj" value="<?php echo $array["doj"]; ?>" disabled class="form-control" style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Work Mode -->
                                                                            <tr>
                                                                                <td><label for="class">Work Mode:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <span id="class"><?php echo $array['class']; ?></span>
                                                                                    <!-- Work Mode Dropdown -->
                                                                                    <select name="class" id="class" disabled class="form-select" style="display:none;">
                                                                                        <option disabled selected>Select Work Mode</option>
                                                                                        <?php
                                                                                        // List of Work Mode Options
                                                                                        $work_modes = [
                                                                                            "Online",
                                                                                            "Offline",
                                                                                            "Hybrid"
                                                                                        ];

                                                                                        // Generate <option> elements dynamically
                                                                                        foreach ($work_modes as $mode) {
                                                                                            $selected = ($array["class"] == $mode) ? "selected" : "";
                                                                                            echo "<option value=\"$mode\" $selected>$mode</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Shift -->
                                                                            <tr>
                                                                                <td><label for="shift">Shift:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <span id="shift"><?php echo $array['shift']; ?></span>
                                                                                    <!-- Shift Dropdown -->
                                                                                    <select name="shift" id="shift" disabled class="form-select" style="display:none;">
                                                                                        <option disabled selected>Select Shift</option>
                                                                                        <?php
                                                                                        // List of Shift
                                                                                        $shift = [
                                                                                            "Morning",
                                                                                            "Afternoon",
                                                                                        ];
                                                                                        // Generate <option> elements dynamically
                                                                                        foreach ($shift as $type) {
                                                                                            $selected = ($array["shift"] == $type) ? "selected" : "";
                                                                                            echo "<option value=\"$type\" $selected>$type</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- HTML for Supervisor -->
                                                                            <tr>
                                                                                <td><label for="immediate_manager">Supervisor:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <span id="supervisorText"><?php echo !empty($array['supervisor']) ? (!empty($supervisorFullName) ? "$supervisorFullName ($array[supervisor])" : $array['supervisor']) : "Supervisor not assigned"; ?></span>
                                                                                    <select name="supervisor" id="supervisor" class="form-select" disabled style="display:none;">
                                                                                        <option disabled selected>Select Supervisor</option>
                                                                                        <?php
                                                                                        // Populate dropdown with active managers
                                                                                        foreach ($managersArr as $manager) {
                                                                                            $selected = ($manager['associatenumber'] == @$array['supervisor']) ? "selected" : "";
                                                                                            echo "<option value=\"{$manager['associatenumber']}\" $selected>{$manager['fullname']} - {$manager['associatenumber']}</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Work Experience -->
                                                                            <tr>
                                                                                <td><label for="duration_of_service">Duration of Service:</label></td>
                                                                                <td>
                                                                                    <?php
                                                                                    // Example input dates
                                                                                    $doj = $array["doj"]; // Date of Joining
                                                                                    $effectiveFrom = $array["effectivedate"]; // Effective End Date, could be null
                                                                                    // Check if DOJ is available and valid
                                                                                    if (empty($doj) || !strtotime($doj)) {
                                                                                        echo "DOJ not available or invalid";
                                                                                    } else {
                                                                                        // Parse dates
                                                                                        $dojDate = new DateTime($doj);
                                                                                        $currentDate = new DateTime(); // Current date
                                                                                        $endDate = $effectiveFrom ? new DateTime($effectiveFrom) : $currentDate; // Use effective date if set, otherwise use today

                                                                                        // Check if DOJ is in the future
                                                                                        if ($dojDate > $currentDate) {
                                                                                            // If the DOJ is in the future, display a message
                                                                                            echo "Not yet commenced";
                                                                                        } else {
                                                                                            // Calculate the difference
                                                                                            $interval = $dojDate->diff($endDate);

                                                                                            // Extract years, months, and days
                                                                                            $years = $interval->y;
                                                                                            $months = $interval->m;
                                                                                            $days = $interval->d;

                                                                                            // Determine the format to display
                                                                                            if ($years > 0) {
                                                                                                $experience = number_format($years + ($months / 12), 2) . " year(s)";
                                                                                            } elseif ($months > 0) {
                                                                                                $experience = number_format($months + ($days / 30), 2) . " month(s)";
                                                                                            } else {
                                                                                                $experience = number_format($days, 2) . " day(s)";
                                                                                            }

                                                                                            // Output the result
                                                                                            echo (new DateTime($doj))->format("d M, Y") . " to " . ($effectiveFrom ? (new DateTime($effectiveFrom))->format("d M, Y") : "Today") . ": " . $experience;
                                                                                        }
                                                                                    }
                                                                                    ?>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card" id="roles">
                                                            <div class="card-header">
                                                                Roles
                                                                <?php if (in_array('current_project', $accessible_cards)) : ?>
                                                                    <span class="edit-icon" onclick="toggleEdit('roles')">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </span>
                                                                    <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                        <i class="bi bi-save"></i>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <!-- Type of Association -->
                                                                            <tr>
                                                                                <td><label for="engagement">Type of Association:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <span id="engagement"><?php echo $array['engagement']; ?></span>
                                                                                    <!-- Type of Association Dropdown -->
                                                                                    <select name="engagement" id="engagement" disabled class="form-select" style="display:none;">
                                                                                        <option disabled selected>Select Type of Association</option>
                                                                                        <?php
                                                                                        // List of Engagement Types
                                                                                        $engagement_types = [
                                                                                            "Employee",
                                                                                            "Volunteer",
                                                                                            "Intern",
                                                                                            "Member"
                                                                                        ];

                                                                                        // Generate <option> elements dynamically
                                                                                        foreach ($engagement_types as $type) {
                                                                                            $selected = ($array["engagement"] == $type) ? "selected" : "";
                                                                                            echo "<option value=\"$type\" $selected>$type</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>

                                                                            <!-- Job Type -->
                                                                            <tr>
                                                                                <td><label for="job_type">Job Type:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <span id="job_type"><?php echo $array['job_type']; ?></span>
                                                                                    <!-- Job Type Dropdown -->
                                                                                    <select name="job_type" id="job_type" disabled class="form-select" style="display:none;">
                                                                                        <option disabled selected>Select Job Type</option>
                                                                                        <?php
                                                                                        // List of Job Types
                                                                                        $job_types = [
                                                                                            "Full-time",
                                                                                            "Part-time",
                                                                                            "Contractual",
                                                                                            "Voluntary"
                                                                                        ];

                                                                                        // Generate <option> elements dynamically
                                                                                        foreach ($job_types as $type) {
                                                                                            $selected = ($array["job_type"] == $type) ? "selected" : "";
                                                                                            echo "<option value=\"$type\" $selected>$type</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>

                                                                            <!-- Designation and Grade -->
                                                                            <tr>
                                                                                <td><label for="position">Designation:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <!-- Position Dropdown -->
                                                                                    <span id="position"><?php echo $array['position']; ?></span>
                                                                                    <select name="position" id="position" disabled class="form-select" style="display:none;">
                                                                                        <option disabled selected>Select Designation</option>
                                                                                        <?php
                                                                                        if ($roles) {
                                                                                            foreach ($roles as $position_list) {
                                                                                                $selected = ($array["position"] == $position_list['designation']) ? "selected" : "";
                                                                                                echo "<option value=\"{$position_list['designation']}\" $selected>{$position_list['designation']}</option>";
                                                                                            }
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Grade -->
                                                                            <tr>
                                                                                <td><label for="grade">Grade:</label></td>
                                                                                <td>
                                                                                    <?php echo $array["grade"] ?>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Access Role -->
                                                                            <tr>
                                                                                <td><label for="role">Access Role:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <!-- Access Role Dropdown -->
                                                                                    <span id="role"><?php echo $array['role']; ?></span>
                                                                                    <select name="role" id="role" disabled style="display:none;" class="form-select">
                                                                                        <option disabled selected>Select Access Role</option>
                                                                                        <?php
                                                                                        // Generate <option> elements dynamically from the roles fetched from the database
                                                                                        foreach ($role_options as $access_role) {
                                                                                            $selected = ($array["role"] == $access_role) ? "selected" : "";
                                                                                            echo "<option value=\"$access_role\" $selected>$access_role</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Qualifications and Experience Tab -->
                                                    <div id="qualifications_experience" class="tab-pane" role="tabpanel">
                                                        <div class="card" id="qualification">
                                                            <div class="card-header">
                                                                Qualification Details
                                                                <?php if (in_array('qualification', $accessible_cards)) : ?>
                                                                    <span class="edit-icon" onclick="toggleEdit('qualification')">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </span>
                                                                    <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                        <i class="bi bi-save"></i>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>

                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="college_name">College name:</label></td>
                                                                                <td>
                                                                                    <span id="college_nameText"><?php echo is_null($array['college_name']) ? 'N/A' : $array['college_name']; ?></span>
                                                                                    <input type="text" name="college_name" id="college_name" value="<?php echo $array["college_name"]; ?>" disabled class="form-control" style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="enrollment_number">Enrolment Number:</label></td>
                                                                                <td>
                                                                                    <span id="enrollment_numberText"><?php echo is_null($array['enrollment_number']) ? 'N/A' : $array['enrollment_number']; ?></span>
                                                                                    <input type="text" name="enrollment_number" id="enrollment_number" value="<?php echo $array["enrollment_number"]; ?>" disabled class="form-control" style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="eduq">Educational Qualification:</label></td>
                                                                                <td>
                                                                                    <span id="eduqText"><?php echo $array['eduq']; ?></span>
                                                                                    <select name="eduq" id="eduq" disabled class="form-select" style="display:none;">
                                                                                        <option disabled selected>Select Status</option>
                                                                                        <?php
                                                                                        // List of Status Options
                                                                                        $eduq_options = [
                                                                                            "Bachelor Degree Regular",
                                                                                            "Bachelor Degree Correspondence",
                                                                                            "Master Degree",
                                                                                            "PhD (Doctorate Degree)",
                                                                                            "Post Doctorate or 5 years experience",
                                                                                            "Culture, Art & Sports etc.",
                                                                                            "Class 12th Pass"
                                                                                        ];

                                                                                        // Generate <option> elements dynamically
                                                                                        foreach ($eduq_options as $status) {
                                                                                            $selected = ($array["eduq"] == $status) ? "selected" : "";
                                                                                            echo "<option value=\"$status\" $selected>$status</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Area of Specialization -->
                                                                            <tr>
                                                                                <td><label for="mjorsub">Area of Specialization:</label></td>
                                                                                <td>
                                                                                    <span id="mjorsubText"><?php echo $array['mjorsub']; ?></span>
                                                                                    <input type="text" name="mjorsub" id="mjorsub" value="<?php echo $array["mjorsub"]; ?>" placeholder="e.g., Computer Science, Physics, Fine Arts" disabled class="form-control" style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td></td>
                                                                                <td>
                                                                                    <!-- Checkbox with required attribute -->
                                                                                    <div id="upload_marksheet" style="display: none;">
                                                                                        <div class="form-check" style="display:none;">
                                                                                            <input class="form-check-input" type="checkbox" value="" id="markSheetCheckbox" name="markSheetCheckbox" required>
                                                                                            <label class="form-check-label" for="markSheetCheckbox">
                                                                                                I have uploaded the mark sheet as supporting documentation for the changes in qualification details.
                                                                                            </label>
                                                                                        </div>
                                                                                        <!-- Help text with link -->
                                                                                        <div class="form-text mt-2">
                                                                                            If you have not uploaded the document yet, <a href="digital_archive.php" target="_blank">click here to upload</a>.
                                                                                        </div>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card" id="experience">
                                                            <div class="card-header">
                                                                Experience
                                                                <?php if (in_array('experience', $accessible_cards)) : ?>
                                                                    <span class="edit-icon" onclick="toggleEdit('experience')">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </span>
                                                                    <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                        <i class="bi bi-save"></i>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="workexperience">Relevant Previous Experience:</label></td>
                                                                                <td>
                                                                                    <span id="workexperienceText"><?php echo $array['workexperience']; ?></span>
                                                                                    <textarea name="workexperience" id="workexperience" class="form-control" rows="3" disabled style="display: none;"><?php echo !empty($array['workexperience']) ? $array['workexperience'] : ''; ?></textarea>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Checkbox for Relevant Previous Experience -->
                                                                            <tr>
                                                                                <td></td>
                                                                                <td>
                                                                                    <div id="workexperienceDocumentSection" style="display: none;">
                                                                                        <div class="form-check" style="display: none;">
                                                                                            <input class="form-check-input" type="checkbox" value="" id="workexperienceDocumentCheckbox" name="workexperienceDocumentCheckbox" required>
                                                                                            <label class="form-check-label" for="workexperienceDocumentCheckbox">
                                                                                                I have uploaded the supporting document for Relevant Previous Experience.
                                                                                            </label>
                                                                                        </div>
                                                                                        <!-- Help text with link -->
                                                                                        <div class="form-text mt-2">
                                                                                            If you have not uploaded the document yet, <a href="digital_archive.php" target="_blank">click here to upload</a>.
                                                                                        </div>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Status & Compensation -->
                                                    <div id="status_compensation" class="tab-pane" role="tabpanel">
                                                        <div class="card" id="compensation">
                                                            <div class="card-header">
                                                                Compensation
                                                                <?php if (in_array('compensation', $accessible_cards)) : ?>
                                                                    <span class="edit-icon" onclick="toggleEdit('compensation')">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </span>
                                                                    <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                        <i class="bi bi-save"></i>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="salary">CTC per Annum:</label></td>
                                                                                <td>
                                                                                    <span id="salary"><?php echo $array['salary']; ?></span>
                                                                                    <input type="number" name="salary" id="salary" value="<?php echo $array["salary"]; ?>" disabled class="form-control" style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card" id="employee_status">
                                                            <div class="card-header">
                                                                Employee Status
                                                                <?php if (in_array('employee_status', $accessible_cards)) : ?>
                                                                    <span class="edit-icon" onclick="toggleEdit('employee_status')">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </span>
                                                                    <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                        <i class="bi bi-save"></i>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="filterstatus">Status:</label></td>
                                                                                <td>
                                                                                    <span id="filterstatus"><?php echo $array['filterstatus']; ?></span>
                                                                                    <select name="filterstatus" id="filterstatus" disabled class="form-select" style="display:none;">
                                                                                        <option disabled selected>Select Status</option>
                                                                                        <?php
                                                                                        // List of Status Options
                                                                                        $status_options = [
                                                                                            "Active",
                                                                                            "Inactive",
                                                                                            "In Progress"
                                                                                        ];

                                                                                        // Generate <option> elements dynamically
                                                                                        foreach ($status_options as $status) {
                                                                                            $selected = ($array["filterstatus"] == $status) ? "selected" : "";
                                                                                            echo "<option value=\"$status\" $selected>$status</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Last Working Day -->
                                                                            <tr>
                                                                                <td><label for="effectivedate">Last Working Day:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <span id="effectivedateText"><?php echo !empty($array["effectivedate"]) ? (new DateTime($array["effectivedate"]))->format("d/m/Y") : ''; ?></span>
                                                                                    <input type="date" name="effectivedate" id="effectivedate"
                                                                                        value="<?php echo $array["effectivedate"]; ?>"
                                                                                        disabled class="form-control" style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="remarks">Remarks:</label></td>
                                                                                <td>
                                                                                    <span id="remarks"><?php echo $array['remarks']; ?></span>
                                                                                    <textarea name="remarks" id="remarks" class="form-control" rows="3" disabled style="display: none;"><?php echo !empty($array['remarks']) ? $array['remarks'] : ''; ?></textarea>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="scode">Scode:</label></td>
                                                                                <td>
                                                                                    <span id="scodeText"><?php echo $array['scode']; ?></span>
                                                                                    <div class="form-group">
                                                                                        <fieldset <?php echo !empty($array['scode']) ? 'disabled' : ''; ?>>
                                                                                            <div class="input-group">
                                                                                                <input type="text" name="scode" id="scode" value="<?php echo $array['scode']; ?>" disabled class="form-control" style="display:none;">
                                                                                                <button type="button" id="generateButton" class="btn btn-primary">Generate Code</button>
                                                                                            </div>
                                                                                        </fieldset>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="absconding">Abscond:</label></td>
                                                                                <td>
                                                                                    <span id="abscondingText"><?php echo htmlspecialchars($array['absconding'] ?? ''); ?></span>

                                                                                    <!-- Container for checkbox and label -->
                                                                                    <div id="absconding-container" style="display: none;">
                                                                                        <!-- Hidden field to send NULL if checkbox is unchecked -->
                                                                                        <input type="hidden" name="absconding" value="NULL">

                                                                                        <input type="checkbox" name="absconding" id="absconding" value="Yes"
                                                                                            <?php echo ($array["absconding"] == "Yes") ? "checked" : ""; ?>
                                                                                            disabled class="form-check-input">
                                                                                        <label class="form-check-label ms-2" for="absconding">Yes</label>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Social -->
                                                    <div id="social" class="tab-pane" role="tabpanel">
                                                        <div class="card" id="social">
                                                            <div class="card-header">
                                                                Social
                                                                <?php if (in_array('social', $accessible_cards)) : ?>
                                                                    <span class="edit-icon" onclick="toggleEdit('social')">
                                                                        <i class="bi bi-pencil"></i>
                                                                    </span>
                                                                    <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                        <i class="bi bi-save"></i>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="linkedin">LinkedIn:</label></td>
                                                                                <td>
                                                                                    <span id="linkedinText"><a href="<?php echo $array['linkedin']; ?>" id="linkedinText" target="_blank"><?php echo $array['linkedin']; ?></a></span>
                                                                                    <input type="url" name="linkedin" id="linkedin" value="<?php echo $array["linkedin"]; ?>" disabled class="form-control" style="display:none;" placeholder="Enter LinkedIn profile URL">
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Admin -->
                                                    <?php if ($role === 'Admin'): ?>
                                                        <div id="admin_console" class="tab-pane" role="tabpanel">
                                                            <div class="card" id="admin_console">
                                                                <div class="card-header">
                                                                    Photo Approval
                                                                    <?php if (in_array('admin_console', $accessible_cards)) : ?>
                                                                        <span class="edit-icon" onclick="toggleEdit('admin_console')">
                                                                            <i class="bi bi-pencil"></i>
                                                                        </span>
                                                                        <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                            <i class="bi bi-save"></i>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-borderless">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td><label for="raw_photo">Uploaded photo:</label></td>
                                                                                    <td>
                                                                                        <?php echo !empty($array['raw_photo']) ? '<a href="' . htmlspecialchars($array['raw_photo'], ENT_QUOTES, 'UTF-8') . '" target="_blank">View</a>' : 'Photo not uploaded yet'; ?>
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <td><label for="photo">Photo:</label></td>
                                                                                    <td>
                                                                                        <span id="photoText"><a href="<?php echo $array['photo']; ?>" id="photoText" target="_blank"><?php echo $array['photo']; ?></a></span>
                                                                                        <input type="url" name="photo" id="photo" value="<?php echo $array["photo"]; ?>" disabled class="form-control" style="display:none;" placeholder="Enter Photo URL">
                                                                                    </td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="card" id="miscellaneous">
                                                                <div class="card-header">
                                                                    Miscellaneous
                                                                    <?php if (in_array('miscellaneous', $accessible_cards)) : ?>
                                                                        <span class="edit-icon" onclick="toggleEdit('miscellaneous')">
                                                                            <i class="bi bi-pencil"></i>
                                                                        </span>
                                                                        <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                            <i class="bi bi-save"></i>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="table-responsive">
                                                                        <table class="table table-borderless">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td><label for="security_deposit">Security Deposit:</label></td>
                                                                                    <td>
                                                                                        <p id="security_depositText" class="mb-1">
                                                                                            <?php
                                                                                            echo is_null($array['security_deposit_amount']) ?
                                                                                                'No security deposit has been made.' : $array['security_deposit_currency'] . ' ' . $array['security_deposit_amount'];
                                                                                            ?>
                                                                                        </p>
                                                                                        <p id="security_deposit_transaction_idText" class="mb-1">
                                                                                            Transaction ID:
                                                                                            <?php
                                                                                            echo is_null($array['security_deposit_transaction_id']) ?
                                                                                                'N/A' :
                                                                                                $array['security_deposit_transaction_id'];
                                                                                            ?>
                                                                                        </p>
                                                                                        <p id="refund_statusText" class="mb-0">
                                                                                            Refund:
                                                                                            <?php
                                                                                            echo is_null($array['security_refund']) ?
                                                                                                'N/A' : ($array['security_refund'] == 'Yes' ? 'Yes' : 'No');
                                                                                            ?>
                                                                                        </p>
                                                                                    </td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </fieldset>
                                            </form>

                                        </div>
                                    </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menuItems = document.querySelector('#menu-items');
            const desktopMenu = document.querySelector('#sidebar-menu');
            const mobileMenu = document.querySelector('#mobile-menu-items');
            const accordionButton = document.querySelector('#headingMenu button');

            // Clone menu items for desktop and mobile
            menuItems.querySelectorAll('.nav-item').forEach(item => {
                // Clone the item for desktop sidebar
                const desktopItem = item.cloneNode(true);
                desktopMenu.appendChild(desktopItem);

                // Clone the item for mobile accordion
                const mobileItem = item.cloneNode(true);
                mobileMenu.appendChild(mobileItem);

                // Add click event listener to activate the respective tab and change button text
                mobileItem.querySelector('a').addEventListener('click', event => {
                    event.preventDefault();
                    const tabTarget = event.target.getAttribute('href'); // e.g., "#employee-details"

                    // Activate the tab using Bootstrap's default behavior
                    const tabElement = document.querySelector(`[href="${tabTarget}"]`);
                    if (tabElement) {
                        const tab = new bootstrap.Tab(tabElement);
                        tab.show();
                    }

                    // Update the accordion button text to reflect the selected menu
                    accordionButton.innerHTML = `Menu: ${event.target.innerText}`;

                    // Collapse the accordion after selection
                    const accordion = document.querySelector('#menuCollapse');
                    const bootstrapCollapse = new bootstrap.Collapse(accordion, {
                        toggle: true,
                    });
                });
            });
        });
    </script>
    <script>
        function toggleEdit(sectionId) {
            // Get the section based on the provided sectionId
            const section = document.getElementById(sectionId);

            // Get all input, select, textarea, and checkbox elements in the section
            const inputs = section.querySelectorAll('input, select, textarea, input[type="checkbox"]');

            // Only proceed if there are input fields (input, select, textarea, checkbox) in the section
            if (inputs.length > 0) {
                // Toggle edit mode for input elements in the section
                const textElements = section.querySelectorAll('span');

                // Toggle visibility of inputs (edit mode) and text (view mode)
                inputs.forEach(input => {
                    if (input.tagName.toLowerCase() === "textarea" || input.type === "checkbox") {
                        if (input.type === "checkbox") {
                            // For checkboxes, toggle the 'disabled' state and visibility of its container
                            const container = input.closest('div'); // Get the parent container (e.g., upload_marksheet)
                            if (container) {
                                container.style.display = container.style.display === 'none' ? 'block' : 'none'; // Toggle visibility of checkbox container
                                input.disabled = !input.disabled; // Toggle the disabled state of the checkbox
                            }
                        } else if (input.tagName.toLowerCase() === "textarea") {
                            // For textarea, toggle visibility and disabled state
                            input.disabled = !input.disabled;
                            input.style.display = input.disabled ? 'none' : 'block'; // Toggle textarea visibility
                        }
                    } else {
                        // For other inputs (e.g., select, text input), toggle the 'disabled' state and visibility
                        input.disabled = !input.disabled;
                        input.style.display = input.disabled ? 'none' : 'inline'; // Toggle input visibility
                    }
                });

                // Toggle visibility of text elements (span elements)
                textElements.forEach(text => {
                    text.style.display = text.style.display === 'none' ? 'inline' : 'none'; // Toggle text visibility
                });

                // Toggle icons (replace pencil with save)
                const editIcon = section.querySelector('.edit-icon');
                const saveIcon = section.querySelector('.save-icon');

                if (editIcon && saveIcon) {
                    editIcon.style.display = 'none'; // Hide pencil icon
                    saveIcon.style.display = 'inline'; // Show save icon
                }

                // Explicitly handle the #upload_marksheet container and its checkbox
                const uploadMarksheetContainer = section.querySelector('#upload_marksheet');
                const markSheetCheckbox = section.querySelector('#markSheetCheckbox');

                if (uploadMarksheetContainer && markSheetCheckbox) {
                    uploadMarksheetContainer.style.display = uploadMarksheetContainer.style.display === 'none' ? 'block' : 'none'; // Toggle container visibility
                    markSheetCheckbox.disabled = !markSheetCheckbox.disabled; // Toggle checkbox disabled state
                    markSheetCheckbox.style.display = markSheetCheckbox.disabled ? 'none' : 'inline'; // Toggle checkbox visibility
                }

                // Explicitly handle the #workexperienceDocumentSection container and its checkbox
                const workexperienceDocumentSection = section.querySelector('#workexperienceDocumentSection');
                const workexperienceDocumentCheckbox = section.querySelector('#workexperienceDocumentCheckbox');

                if (workexperienceDocumentSection && workexperienceDocumentCheckbox) {
                    workexperienceDocumentSection.style.display = workexperienceDocumentSection.style.display === 'none' ? 'block' : 'none'; // Toggle container visibility
                    workexperienceDocumentCheckbox.disabled = !workexperienceDocumentCheckbox.disabled; // Toggle checkbox disabled state
                    workexperienceDocumentCheckbox.style.display = workexperienceDocumentCheckbox.disabled ? 'none' : 'inline'; // Toggle checkbox visibility
                }

                // Explicitly handle the #casteCertificateSection container and its checkbox
                const casteCertificateSection = section.querySelector('#casteCertificateSection');
                const casteCertificateCheckbox = section.querySelector('#casteCertificateCheckbox');

                if (casteCertificateSection && casteCertificateCheckbox) {
                    casteCertificateSection.style.display = casteCertificateSection.style.display === 'none' ? 'block' : 'none'; // Toggle container visibility
                    casteCertificateCheckbox.disabled = !casteCertificateCheckbox.disabled; // Toggle checkbox disabled state
                    casteCertificateCheckbox.style.display = casteCertificateCheckbox.disabled ? 'none' : 'inline'; // Toggle checkbox visibility
                }
            }
        }

        function saveChanges() {
            // Submit the form when the save button is clicked
            document.getElementById('signup').submit();
        }
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Loop through the pendingFields array and check which fields have 'Pending' status
            pendingFields.forEach(function(field) {
                var fieldname = field.fieldname;

                // Find the label element for the field (assuming the label 'for' attribute matches fieldname)
                var label = document.querySelector('label[for="' + fieldname + '"]');

                if (label) {
                    // Create a badge element
                    var badge = document.createElement("span");
                    badge.classList.add("badge", "bg-warning", "text-dark", "ms-2");
                    badge.textContent = "Under Review";

                    // Append the badge next to the label
                    label.appendChild(badge);
                }

            });
        });
    </script>
    <script>
        // Function to extract initials from the full name
        function getInitials(fullname) {
            return fullname
                .split(' ')
                .map(name => name.charAt(0).toUpperCase())
                .join('');
        }
        // Function to extract initials from the full name
        function getInitials(fullname) {
            return fullname
                .split(' ')
                .map(name => name.charAt(0).toUpperCase())
                .join('');
        }

        document.getElementById('viewHierarchyBtn').addEventListener('click', function() {
            const associatenumber = this.getAttribute('data-associate');
            const dropdown = document.getElementById('hierarchyDropdown');
            const arrowIcon = this.querySelector('i'); // Get the icon element

            // Check if the dropdown is already visible
            const isVisible = dropdown.style.display === 'block';

            // Toggle the dropdown visibility
            if (isVisible) {
                dropdown.style.display = 'none';
                // Change the icon to down chevron when closed
                arrowIcon.classList.remove('bi-chevron-up');
                arrowIcon.classList.add('bi-chevron-down');
            } else {
                dropdown.style.display = 'block';
                // Change the icon to up chevron when expanded
                arrowIcon.classList.remove('bi-chevron-down');
                arrowIcon.classList.add('bi-chevron-up');

                // Show loading state
                dropdown.innerHTML = 'Loading...';

                // Fetch hierarchy via AJAX
                fetch('payment-api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            'form-type': 'hierarchy',
                            'associatenumber': associatenumber
                        })
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (data.length > 0) {
                            dropdown.innerHTML = data.map(item => {
                                const profilePic = item.photo ?
                                    `<div class="icon-container">
                          <img src="${item.photo}" class="rounded-circle me-2" alt="${item.fullname}" width="40" height="40" />
                      </div>` :
                                    `<div class="profile-pic initials">${getInitials(item.fullname)}</div>`;

                                return `
                    <div class="hierarchy-item">
                        ${profilePic}
                        <span>${item.fullname} (${item.associatenumber}) - ${item.position}</span>
                    </div>
                `;
                            }).join('');
                        } else {
                            dropdown.innerHTML = 'No hierarchy data found.';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching hierarchy:', error);
                        dropdown.innerHTML = 'An error occurred. Please try again.';
                    });
            }
        });

        // Close the dropdown when clicked outside of it
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('hierarchyDropdown');
            const viewHierarchyBtn = document.getElementById('viewHierarchyBtn');

            // If the click is outside the dropdown and the button, close the dropdown
            if (!viewHierarchyBtn.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
                // Change the icon to down chevron when closed
                const arrowIcon = viewHierarchyBtn.querySelector('i');
                arrowIcon.classList.remove('bi-chevron-up');
                arrowIcon.classList.add('bi-chevron-down');
            }
        });
    </script>
    <script>
        // Function to extract initials from the full name
        function getInitials(fullname) {
            return fullname
                .split(' ')
                .map(name => name.charAt(0).toUpperCase())
                .join('');
        }

        // Handle the "View Reportees" button click
        document.getElementById('viewReporteesBtn').addEventListener('click', function() {
            const associatenumber = this.getAttribute('data-associate');
            const dropdown = document.getElementById('reporteesDropdown');
            const arrowIcon = this.querySelector('i'); // Get the icon element

            // Check if the dropdown is already visible
            const isVisible = dropdown.style.display === 'block';

            // Toggle the dropdown visibility
            if (isVisible) {
                dropdown.style.display = 'none';
                // Change the icon to down chevron when closed
                arrowIcon.classList.remove('bi-chevron-up');
                arrowIcon.classList.add('bi-chevron-down');
            } else {
                dropdown.style.display = 'block';
                // Change the icon to up chevron when expanded
                arrowIcon.classList.remove('bi-chevron-down');
                arrowIcon.classList.add('bi-chevron-up');

                // Show loading state
                dropdown.innerHTML = 'Loading...';

                // Fetch reportees via AJAX
                fetch('payment-api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            'form-type': 'reportees',
                            'associatenumber': associatenumber
                        })
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (data.length > 0) {
                            dropdown.innerHTML = data.map(item => {
                                const profilePic = item.photo ?
                                    `<div class="icon-container">
                          <img src="${item.photo}" class="rounded-circle me-2" alt="${item.fullname}" width="40" height="40" />
                      </div>` :
                                    `<div class="profile-pic initials">${getInitials(item.fullname)}</div>`;

                                return `
                    <div class="hierarchy-item">
                        ${profilePic}
                        <span>${item.fullname} (${item.associatenumber}) - ${item.position}</span>
                    </div>
                `;
                            }).join('');
                        } else {
                            dropdown.innerHTML = 'No reportees found.';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching reportees:', error);
                        dropdown.innerHTML = 'An error occurred. Please try again.';
                    });
            }
        });

        // Close the dropdown when clicked outside of it
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('reporteesDropdown');
            const viewReporteesBtn = document.getElementById('viewReporteesBtn');

            // If the click is outside the dropdown and the button, close the dropdown
            if (!viewReporteesBtn.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
                // Change the icon to down chevron when closed
                const arrowIcon = viewReporteesBtn.querySelector('i');
                arrowIcon.classList.remove('bi-chevron-up');
                arrowIcon.classList.add('bi-chevron-down');
            }
        });
    </script>
    <script>
        function syncVisibility() {
            const scodeInput = document.getElementById('scode');
            const generateButton = document.getElementById('generateButton');

            // If the input field is hidden, hide the button; otherwise, show it
            const isVisible = scodeInput.style.display !== 'none';
            generateButton.style.visibility = isVisible ? 'visible' : 'hidden';
        }

        // Generate code logic and visibility toggle
        document.getElementById('generateButton').addEventListener('click', function() {
            // Generate a random 20-character string
            const characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            const randomString = Array.from({
                    length: 20
                }, () =>
                characters.charAt(Math.floor(Math.random() * characters.length))
            ).join('');

            // Set the generated string in the input and show the input field
            const scodeInput = document.getElementById('scode');
            scodeInput.value = randomString;
            scodeInput.style.display = 'block';

            // Sync visibility after showing the input
            syncVisibility();
        });

        // Monitor changes to the input field's style and sync visibility
        const scodeInput = document.getElementById('scode');
        const observer = new MutationObserver(syncVisibility);
        observer.observe(scodeInput, {
            attributes: true,
            attributeFilter: ['style']
        });

        // Initial sync to ensure visibility consistency
        syncVisibility();
    </script>
    <!-- JavaScript to handle dynamic visibility -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const casteSelect = document.getElementById('caste');
            const casteCertificateRow = document.getElementById('casteCertificateRow');

            if (casteSelect && casteCertificateRow) {
                // Function to toggle visibility of caste certificate row
                function toggleCasteCertificateRow() {
                    if (casteSelect.value !== "General" && casteSelect.value !== "") {
                        casteCertificateRow.style.display = 'table-row'; // Show the row
                    } else {
                        casteCertificateRow.style.display = 'none'; // Hide the row
                    }
                }

                // Attach event listener to caste select
                casteSelect.addEventListener('change', toggleCasteCertificateRow);

                // Initial check on page load
                toggleCasteCertificateRow();
            }
        });
    </script>
    <script>
        // Tab URL handling
        document.addEventListener('DOMContentLoaded', function() {
            // Function to activate a tab by its ID
            function activateTab(tabId) {
                // Hide all tab panes
                document.querySelectorAll('.tab-pane').forEach(pane => {
                    pane.classList.remove('active');
                });

                // Show the selected tab pane
                const targetPane = document.getElementById(tabId);
                if (targetPane) {
                    targetPane.classList.add('active');
                }

                // Update the active state in the sidebar
                document.querySelectorAll('#sidebar-menu .nav-link, #mobile-menu-items .nav-link').forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${tabId}`) {
                        link.classList.add('active');
                    }
                });
            }

            // Function to clean up URL parameters (keep only one tab parameter)
            function cleanUrlParams(activeTab) {
                const url = new URL(window.location.href);
                const params = new URLSearchParams(url.search);

                // Remove all tab parameters
                params.delete('tab');

                // Add only the active tab parameter
                if (activeTab) {
                    params.append('tab', activeTab);
                }

                // Build new URL
                url.search = params.toString();
                return url.toString();
            }

            // Handle tab clicks - update URL
            document.querySelectorAll('.nav-link[data-bs-toggle="tab"]').forEach(link => {
                link.addEventListener('click', function(e) {
                    const tabId = this.getAttribute('href').substring(1);
                    const cleanUrl = cleanUrlParams(tabId);
                    window.history.pushState({
                        tab: tabId
                    }, '', cleanUrl);
                });
            });

            // Check for tab parameter on page load
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');

            if (tabParam) {
                activateTab(tabParam);
                // Clean up URL if multiple tab parameters exist
                const cleanUrl = cleanUrlParams(tabParam);
                if (cleanUrl !== window.location.href) {
                    window.history.replaceState({
                        tab: tabParam
                    }, '', cleanUrl);
                }
            }

            // Handle browser back/forward navigation
            window.addEventListener('popstate', function(event) {
                if (event.state && event.state.tab) {
                    activateTab(event.state.tab);
                } else {
                    const urlParams = new URLSearchParams(window.location.search);
                    const tabParam = urlParams.get('tab');
                    if (tabParam) {
                        activateTab(tabParam);
                    }
                }
            });
        });
    </script>
    <script>
        let isSecondContactVisible = false;

        function showSecondEmergencyContact() {
            const row1 = document.getElementById('secondContactRow1');
            const row2 = document.getElementById('secondContactRow2');
            const btn = document.getElementById('addSecondContact');

            if (!isSecondContactVisible) {
                // Show second contact fields
                row1.style.display = '';
                row2.style.display = '';
                btn.innerHTML = '<i class="bi bi-x-circle"></i> Hide Second Emergency Contact';
                isSecondContactVisible = true;
            } else {
                // Hide second contact fields
                row1.style.display = 'none';
                row2.style.display = 'none';
                btn.innerHTML = '<i class="bi bi-plus-circle"></i> Show Second Emergency Contact';
                isSecondContactVisible = false;
            }
        }
    </script>

</body>

</html>