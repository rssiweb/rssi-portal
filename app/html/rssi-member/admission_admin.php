<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

// Helper function to handle plan update errors
function handlePlanUpdateError($error_message, $updated_fields, $redirect_url = null)
{
    global $field_names_mapping;

    // If no redirect URL provided, create one
    if ($redirect_url === null) {
        $redirect_params = $_GET;
        $redirect_url = $_SERVER['PHP_SELF'] . '?' . http_build_query($redirect_params);
    }

    // Escape the error message for JavaScript - fix for newlines
    $escaped_error = str_replace(
        ["'", "\n"],
        ["\\'", "\\n"],
        $error_message
    );

    if (!empty($updated_fields)) {
        // Convert field names to readable format
        $changedFieldsList = implode(", ", array_map(function ($field) use ($field_names_mapping) {
            return $field_names_mapping[$field] ?? $field;
        }, $updated_fields));

        echo "<script>
            alert('Student details updated successfully. Changed fields: $changedFieldsList\\nPlan update failed: $escaped_error');
            
            // Clear form data in history
            if (window.history.replaceState) {
                window.history.replaceState(null, null, '$redirect_url');
            }
            
            // Redirect preserving all parameters
            setTimeout(function() {
                window.location.href = '$redirect_url';
            }, 100);
        </script>";
    } else {
        echo "<script>
            alert('Plan update failed: $escaped_error');
            
            // Clear form data in history
            if (window.history.replaceState) {
                window.history.replaceState(null, null, '$redirect_url');
            }
            
            // Redirect preserving all parameters
            setTimeout(function() {
                window.location.href = '$redirect_url';
            }, 100);
        </script>";
    }
}

// Helper function for month-based date calculations
function getFirstDayOfMonth($date)
{
    return date('Y-m-01', strtotime($date));
}

function getLastDayOfMonth($date)
{
    return date('Y-m-t', strtotime($date));
}

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
        'doa',
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

// Add this mapping array BEFORE form processing
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
    'division' => 'Division',
    'class' => 'Class',

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

$required_fields = [
    'studentname',
    'dateofbirth',
    'gender',
    'guardiansname',
    'relationwithstudent',
    'emergency_contact_number',
    'stateofdomicile',
    'postaladdress',
    'permanentaddress',
    'contact',
    'caste',
    'schooladmissionrequired',
    'category',
    'module',
    'filterstatus',
    'preferredbranch',
];

// Create the mapped array
$required_fields_with_names = [];
foreach ($required_fields as $field) {
    // Check if the field exists in your mapping
    if (isset($field_names_mapping[$field])) {
        $required_fields_with_names[$field] = $field_names_mapping[$field];
    }
}

$fields_requiring_approval = [
    'studentname',
    'dateofbirth',
    'contact',
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ==============================================
    // DECLARE REDIRECT URL ONCE - REUSE EVERYWHERE
    // ==============================================
    $redirect_params = $_GET;
    if (!empty($search_id)) {
        $redirect_params['student_id'] = $search_id;
    }
    $redirect_url = $_SERVER['PHP_SELF'] . '?' . http_build_query($redirect_params);
    // ==============================================
    // SERVER-SIDE VALIDATION - ONLY CHECK SUBMITTED FIELDS
    // ==============================================
    $missingFields = [];

    // Check each required field only if it exists in POST
    foreach ($required_fields_with_names as $field => $displayName) {
        // Only validate if this field was submitted in POST
        if (array_key_exists($field, $_POST)) {
            $value = $_POST[$field] ?? '';

            // For select fields
            if (is_array($value)) {
                if (empty($value)) {
                    $missingFields[] = $displayName;
                }
            }
            // For regular inputs
            elseif (trim($value) === '') {
                $missingFields[] = $displayName;
            }
        }
        // If field not in POST at all, don't validate it
        // (user didn't edit/change this field)
    }

    // Also check file uploads if needed
    $file_fields = ['student_photo', 'upload_aadhar_card', 'caste_document', 'supporting_doc'];
    foreach ($file_fields as $file_field) {
        if (isset($_FILES[$file_field]['name']) && empty($_FILES[$file_field]['name'])) {
            // File field was submitted but empty
            if (isset($required_fields_with_names[$file_field])) {
                $missingFields[] = $required_fields_with_names[$file_field];
            }
        }
    }

    if (!empty($missingFields)) {
        $js_message = "Please fill all required fields: " . implode(', ', $missingFields);
        $js_message = str_replace("'", "\\'", $js_message);

        echo "<script>
        alert('$js_message');
        
        // Clear form data in history
        if (window.history.replaceState) {
            window.history.replaceState(null, null, '$redirect_url');
        }
        
        // Redirect back preserving all parameters
        setTimeout(function() {
            window.location.href = '$redirect_url';
        }, 100);
    </script>";
        exit;
    }
    // ==============================================
    // CONTINUE WITH YOUR EXISTING CODE BELOW
    // ==============================================

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
                        $requires_approval = in_array($field, $fields_requiring_approval);

                        if ($current_user_level >= $field_level) {
                            if ($requires_approval && $current_user_level < 2) {
                                // Field requires approval AND user is not Admin
                                $pending_approval_fields[] = $field;
                            } else {
                                // Direct update allowed
                                $update_fields[] = "$field = '$new_value'";
                                $updated_fields[] = $field;
                            }
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
                $requires_approval = in_array($field, $fields_requiring_approval);

                if ($current_user_level >= $field_level) {
                    if ($requires_approval && $current_user_level < 2) {
                        // Field requires approval AND user is not Admin
                        $pending_approval_fields[] = $field;
                    } else {
                        // Direct update allowed
                        $update_fields[] = "$field = " . ($new_value === null ? "NULL" : "'$new_value'");
                        $updated_fields[] = $field;
                    }
                } elseif ($current_user_level >= 1) { // At least manager level
                    // User doesn't have field-level permission
                    $pending_approval_fields[] = $field;
                } else {
                    $unauthorized_updates[] = $field;
                }
            }
        }

        // ==============================================
        // PLAN UPDATE LOGIC (STRICT MODE – DOA SAFE + OVERLAP)
        // ==============================================

        $plan_update_fields = [
            'plan_update_type_of_admission',
            'plan_update_class',
            'plan_update_effective_from_date'
        ];

        $has_plan_update = false;
        foreach ($plan_update_fields as $field) {
            if (!empty($_POST[$field])) {
                $has_plan_update = true;
                break;
            }
        }
        if ($has_plan_update) {

            // ----------------------------------------------
            // INPUT
            // ----------------------------------------------
            $type_of_admission = $_POST['plan_update_type_of_admission'];
            $class             = $_POST['plan_update_class'];
            $remarks           = $_POST['plan_update_remarks'] ?? '';
            $updated_by        = $associatenumber;

            // ----------------------------------------------
            // FETCH STUDENT MASTER DATA
            // ----------------------------------------------
            $studentRow = pg_fetch_assoc(pg_query(
                $con,
                "SELECT doa,
            type_of_admission AS default_category,
            class AS default_class
     FROM rssimyprofile_student
     WHERE student_id = '$search_id'"
            ));

            if (empty($studentRow['doa'])) {
                handlePlanUpdateError("Date of admission not found.", $updated_fields);
                exit;
            }

            $doa              = $studentRow['doa'];
            $default_category = $studentRow['default_category'];
            $default_class    = $studentRow['default_class'];

            $doaMonthStart   = date('Y-m-01', strtotime($doa));
            $selectedMonth   = getFirstDayOfMonth($_POST['plan_update_effective_from_date']);
            $effective_from  = $selectedMonth;

            // ----------------------------------------------
            // VALIDATION: DOA BOUNDARY
            // ----------------------------------------------
            if (strtotime($effective_from) < strtotime($doaMonthStart)) {
                handlePlanUpdateError(
                    "Plan effective date cannot be earlier than admission month.",
                    $updated_fields
                );
                exit;
            }

            // ----------------------------------------------
            // TRANSACTION START
            // ----------------------------------------------
            pg_query($con, "BEGIN");

            try {

                // ----------------------------------------------
                // FETCH PLAN HISTORY COUNT
                // ----------------------------------------------
                $historyCount = (int) pg_fetch_result(
                    pg_query(
                        $con,
                        "SELECT COUNT(*) FROM student_category_history
             WHERE student_id = '$search_id' AND is_valid = true"
                    ),
                    0,
                    0
                );

                // ----------------------------------------------
                // STRICT BLOCK: SAME PLAN FROM ADMISSION
                // ----------------------------------------------
                if (
                    $type_of_admission === $default_category &&
                    $class === $default_class &&
                    strtotime($effective_from) >= strtotime($doaMonthStart)
                ) {
                    handlePlanUpdateError(
                        "Student is already on this plan. No plan change required.",
                        $updated_fields
                    );
                    pg_query($con, "ROLLBACK");
                    exit;
                }

                // ----------------------------------------------
                // FIRST PLAN + SAME ADMISSION MONTH → USE DOA
                // ----------------------------------------------
                if ($historyCount === 0 && strtotime($selectedMonth) === strtotime($doaMonthStart)) {
                    $effective_from = date('Y-m-d', strtotime($doa));
                }

                // ----------------------------------------------
                // VALIDATION: CLOSED PLAN OVERLAP
                // ----------------------------------------------
                $closedOverlap = pg_query($con, "
        SELECT 1
        FROM student_category_history
        WHERE student_id = '$search_id'
          AND is_valid = true
          AND effective_until IS NOT NULL
          AND DATE '$effective_from' BETWEEN effective_from AND effective_until
        LIMIT 1
    ");
                if (pg_num_rows($closedOverlap) > 0) {
                    handlePlanUpdateError(
                        "This period is already covered by a finalized plan.",
                        $updated_fields
                    );
                    pg_query($con, "ROLLBACK");
                    exit;
                }

                // ----------------------------------------------
                // CREATE DEFAULT PLAN (ONLY WHEN REQUIRED)
                // ----------------------------------------------
                if ($historyCount === 0 && strtotime($selectedMonth) > strtotime($doaMonthStart)) {
                    $doaDate = date('Y-m-d', strtotime($doa));
                    $prevDay = date('Y-m-d', strtotime($selectedMonth . ' -1 day'));

                    pg_query($con, "
            INSERT INTO student_category_history (
                student_id,
                category_type,
                class,
                effective_from,
                effective_until,
                created_by,
                is_valid,
                remarks
            ) VALUES (
                '$search_id',
                '$default_category',
                '$default_class',
                DATE '$doaDate',
                DATE '$prevDay',
                'System',
                true,
                'Default plan created from admission'
            )
        ");
                }

                // ----------------------------------------------
                // HANDLE OVERLAPS / INVALIDATE / CLOSE OPEN PLANS
                // ----------------------------------------------
                $overlapPlansQuery = "
        SELECT *
        FROM student_category_history
        WHERE student_id = '$search_id'
          AND is_valid = true
          AND (
                (effective_until IS NULL AND effective_from < DATE '$effective_from')
                OR effective_from = DATE '$effective_from'
          )
        ORDER BY effective_from ASC
    ";
                $overlapPlansResult = pg_query($con, $overlapPlansQuery);

                while ($plan = pg_fetch_assoc($overlapPlansResult)) {
                    if (strtotime($plan['effective_from']) === strtotime($effective_from)) {
                        // Same start date → invalidate old plan
                        pg_query($con, "
                UPDATE student_category_history
                SET is_valid = false,
                    updated_by = '$updated_by',
                    updated_on = NOW()
                WHERE id = {$plan['id']}
            ");
                    } elseif ($plan['effective_until'] === null) {
                        // Open-ended plan → close one day before new plan
                        $prevDay = date('Y-m-d', strtotime($effective_from . ' -1 day'));
                        pg_query($con, "
                UPDATE student_category_history
                SET effective_until = DATE '$prevDay',
                    updated_by = '$updated_by',
                    updated_on = NOW()
                WHERE id = {$plan['id']}
            ");
                    }
                }

                // ----------------------------------------------
                // INSERT NEW PLAN (OPEN ENDED)
                // ----------------------------------------------
                pg_query($con, "
        INSERT INTO student_category_history (
            student_id,
            category_type,
            class,
            effective_from,
            created_by,
            is_valid,
            remarks
        ) VALUES (
            '$search_id',
            '$type_of_admission',
            '$class',
            DATE '$effective_from',
            '$updated_by',
            true,
            '$remarks'
        )
    ");

                // ----------------------------------------------
                // UPDATE STUDENT MASTER (IF CURRENT)
                // ----------------------------------------------
                if (strtotime($effective_from) <= strtotime(date('Y-m-01'))) {
                    pg_query($con, "
            UPDATE rssimyprofile_student
            SET type_of_admission = '$type_of_admission',
                class = '$class',
                updated_by = '$updated_by',
                updated_on = NOW()
            WHERE student_id = '$search_id'
        ");
                }

                // ----------------------------------------------
                // COMMIT
                // ----------------------------------------------
                pg_query($con, "COMMIT");
                $updated_fields[] = 'plan_history_updated';
            } catch (Exception $e) {
                pg_query($con, "ROLLBACK");
                handlePlanUpdateError($e->getMessage(), $updated_fields);
                exit;
            }

            // ==============================================
            // END PLAN UPDATE LOGIC
            // ==============================================

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
            foreach ($pending_approval_fields as $field) {
                $submitted_value = null;

                if (in_array($field, ['student_photo_raw', 'upload_aadhar_card', 'caste_document', 'supporting_doc'])) {
                    // Handle file upload for workflow
                    $form_field_name = str_replace('_raw', '', $field);
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
                            $submitted_value = $doclink;
                        }
                    }
                } else {
                    // Handle regular fields
                    $submitted_value = isset($_POST[$field]) ? trim($_POST[$field]) : null;
                    if ($submitted_value === "") {
                        $submitted_value = null;
                    }
                }

                if ($submitted_value !== null) {
                    // Check if there's already a pending request for this field
                    $check_sql = "SELECT workflow_id FROM student_profile_update_workflow 
                          WHERE student_id = $1 
                          AND field_name = $2 
                          AND reviewer_status = 'Pending'";
                    $check_result = pg_query_params($con, $check_sql, [$search_id, $field]);

                    if (pg_num_rows($check_result) > 0) {
                        // Update existing pending request
                        $update_sql = "UPDATE student_profile_update_workflow 
                              SET submitted_value = $1,
                                  submitted_by = $2,
                                  submission_timestamp = NOW()
                              WHERE student_id = $3 
                              AND field_name = $4 
                              AND reviewer_status = 'Pending'";
                        pg_query_params($con, $update_sql, [
                            $submitted_value,
                            $associatenumber,
                            $search_id,
                            $field
                        ]);
                    } else {
                        // Insert new workflow request
                        $current_value = $current_data[$field] ?? null;

                        $insert_sql = "INSERT INTO student_profile_update_workflow 
                              (student_id, field_name, current_value, submitted_value, 
                               submitted_by, submission_timestamp, reviewer_status)
                              VALUES ($1, $2, $3, $4, $5, NOW(), 'Pending')";
                        pg_query_params($con, $insert_sql, [
                            $search_id,
                            $field,
                            $current_value,
                            $submitted_value,
                            $associatenumber
                        ]);
                    }
                }
            }

            $pending_fields_list = implode(", ", array_map(function ($field) use ($field_names_mapping) {
                return $field_names_mapping[$field] ?? $field;
            }, $pending_approval_fields));

            echo "<script>
        alert('Change request has been submitted for review: $pending_fields_list. An administrator will review and approve/reject these changes.');
        
        // Clear form data in history
        if (window.history.replaceState) {
            window.history.replaceState(null, null, '$redirect_url');
        }
        
        // Redirect preserving all parameters
        setTimeout(function() {
            window.location.href = '$redirect_url';
        }, 100);
    </script>";
            exit;
        }

        // Show success message
        $has_any_updates = !empty($update_fields) || in_array('plan_history_updated', $updated_fields);

        if ($has_any_updates) {
            // Use mapping array
            $updated_fields_readable = [];
            foreach ($updated_fields as $field) {
                if ($field !== 'plan_history_updated') {
                    $updated_fields_readable[] = isset($field_names_mapping[$field])
                        ? $field_names_mapping[$field]
                        : $field;
                }
            }

            $updated_list = !empty($updated_fields_readable) ? implode(", ", $updated_fields_readable) : "";

            // Check if plan was updated
            $has_plan_update_msg = in_array('plan_history_updated', $updated_fields) ?
                "\nPlan change has been recorded in history." : "";

            // Construct message
            if (!empty($updated_list) && !empty($has_plan_update_msg)) {
                $message = "The following fields were updated: " . $updated_list . "\n" . $has_plan_update_msg;
            } elseif (!empty($updated_list)) {
                $message = "The following fields were updated: " . $updated_list;
            } elseif (!empty($has_plan_update_msg)) {
                $message = "Plan change has been recorded in history.";
            } else {
                $message = "Changes have been saved.";
            }

            // Escape single quotes and newlines for JavaScript
            $js_message = str_replace(["'", "\n"], ["\\'", "\\n"], $message);

            echo "<script>
        alert('$js_message');
        
        // Clear form data in history
        if (window.history.replaceState) {
            window.history.replaceState(null, null, '$redirect_url');
        }
        
        // Redirect preserving all parameters
        setTimeout(function() {
            window.location.href = '$redirect_url';
        }, 100);
    </script>";
            exit;
        }

        if (empty($update_fields) && empty($pending_approval_fields)) {
            echo "<script>
        alert('No changes were made to the profile.');
        
        // Clear form data in history
        if (window.history.replaceState) {
            window.history.replaceState(null, null, '$redirect_url');
        }
        
        // Redirect back preserving all parameters
        setTimeout(function() {
            window.location.href = '$redirect_url';
        }, 100);
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
    'misc' => 1,
    'admin_console' => 2,        // Admin only
];

// Get accessible cards for current user
$accessible_cards = [];
foreach ($card_access_levels as $card => $required_level) {
    if ($current_user_level >= $required_level) {
        $accessible_cards[] = $card;
    }
}
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
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

        /* Fix for Select2 within input-group */
        .input-group .select2-container {
            flex: 1 1 auto;
            width: 1% !important;
            /* Override Select2 width */
        }

        .input-group .select2-container .select2-selection {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            height: calc(2.25rem + 2px);
            border-color: #ced4da;
        }

        /* Fix border when focused */
        .input-group .select2-container--focus .select2-selection {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* Remove default Bootstrap border radius from the button */
        .input-group .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
    </style>
    <style>
        /* Status Badge next to name */
        /* .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 50rem;
            vertical-align: middle;
        }

        .status-badge[data-status="Active"] {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .status-badge[data-status="Inactive"] {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        } */

        /* Status Flag in top-right corner */
        .status-flag-container {
            text-align: right;
        }

        .status-flag {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: default;
        }

        .status-flag:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .status-flag[data-status="Active"] {
            background: linear-gradient(135deg, #d1e7dd 0%, #a3cfbb 100%);
            color: #0a3622;
            border-left: 4px solid #198754;
        }

        .status-flag[data-status="Inactive"] {
            background: linear-gradient(135deg, #f8d7da 0%, #f1aeb5 100%);
            color: #58151c;
            border-left: 4px solid #dc3545;
        }

        .status-flag[data-status="Pending"] {
            background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
            color: #664d03;
            border-left: 4px solid #ffc107;
        }

        .flag-icon {
            margin-right: 0.5rem;
            font-size: 1rem;
        }

        .status-flag[data-status="Active"] .flag-icon {
            color: #198754;
        }

        .status-flag[data-status="Inactive"] .flag-icon {
            color: #dc3545;
        }

        .status-flag[data-status="Pending"] .flag-icon {
            color: #ffc107;
        }

        .flag-text {
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
        }

        .effective-date {
            font-size: 0.75rem;
            font-style: italic;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .status-flag-container {
                text-align: left;
                margin-bottom: 1rem;
            }

            .status-flag {
                display: inline-flex;
            }

            .contact-info p {
                text-align: left;
                margin-bottom: 0.25rem;
            }
        }
    </style>
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
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
                            <!-- Compact Top Right Search Form -->
                            <div class="card shadow-sm">
                                <div class="card-body p-3">
                                    <form method="GET" action="" class="mb-0">
                                        <div class="input-group">
                                            <select class="form-select select2-hidden-accessible"
                                                id="student_id"
                                                name="student_id"
                                                required
                                                style="width: 100%;">
                                                <?php if (!empty($_GET['student_id'])): ?>
                                                    <option value="<?= htmlspecialchars($_GET['student_id']) ?>" selected>
                                                        <?= htmlspecialchars($_GET['student_id']) ?>
                                                    </option>
                                                <?php endif; ?>
                                            </select>
                                            <button class="btn btn-primary" type="submit">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

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
                                            <p style="font-size: large;">
                                                <?php echo $array["studentname"] ?>
                                                <!-- <?php if (in_array('student_status', $accessible_cards)): ?>
                                                    Status badge next to name
                                                    <span class="status-badge ms-2" data-status="<?php echo $array['filterstatus']; ?>">
                                                        <?php echo $array['filterstatus']; ?>
                                                    </span>
                                                <?php endif; ?> -->
                                            </p>
                                            <p><?php echo $array["student_id"] ?><br>
                                                <?php echo $array["category"] ?><br>
                                                <?php echo $array["class"] ?? 'Class not specified' ?>/<?php echo $array["type_of_admission"] ?? 'Admission type not specified' ?></p><br>

                                        </div>

                                        <div class="contact-info">
                                            <!-- Status flag in top-right corner -->
                                            <?php if (in_array('student_status', $accessible_cards)): ?>
                                                <div class="status-flag-container position-relative mb-2">
                                                    <div class="status-flag" data-status="<?php echo $array['filterstatus']; ?>">
                                                        <span class="flag-icon">
                                                            <?php if ($array['filterstatus'] == 'Active'): ?>
                                                                <i class="bi bi-check-circle-fill"></i>
                                                            <?php elseif ($array['filterstatus'] == 'Inactive'): ?>
                                                                <i class="bi bi-x-circle-fill"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-question-circle-fill"></i>
                                                            <?php endif; ?>
                                                        </span>
                                                        <span class="flag-text"><?php echo $array['filterstatus']; ?></span>
                                                    </div>
                                                    <?php if (!empty($array['effectivefrom'])): ?>
                                                        <div class="effective-date small text-muted mt-1">
                                                            Since: <?php echo date('d/m/Y', strtotime($array['effectivefrom'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

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
                                                <?php if (in_array('misc', $accessible_cards)): ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="#misc" data-bs-toggle="tab">Miscellaneous Information</a>
                                                    </li>
                                                <?php endif; ?>

                                                <?php if (in_array('admin_console', $accessible_cards)): ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link" href="#admin_console" data-bs-toggle="tab">Admin console</a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>

                                        <!-- Content Area -->
                                        <div class="content tab-content container-fluid">
                                            <form name="studentProfileForm" id="studentProfileForm" action="#" method="post" enctype="multipart/form-data">
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
                                                                                <?php echo $array['student_id']; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="studentname">Student Name:</label></td>
                                                                            <td>
                                                                                <span id="studentnameText"><?php echo $array['studentname']; ?></span>
                                                                                <?php if (in_array('studentname', $user_accessible_fields)): ?>
                                                                                    <input type="text" name="studentname" id="studentname"
                                                                                        value="<?php echo $array['studentname']; ?>"
                                                                                        class="form-control" disabled style="display:none;">
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="dateofbirth">Date of Birth:</label></td>
                                                                            <td>
                                                                                <span id="dateofbirthText">
                                                                                    <?php echo !empty($array['dateofbirth']) ? date('d/m/Y', strtotime($array['dateofbirth'])) : ''; ?>
                                                                                </span>
                                                                                <?php if (in_array('dateofbirth', $user_accessible_fields)): ?>
                                                                                    <input type="date" name="dateofbirth" id="dateofbirth"
                                                                                        value="<?php echo $array['dateofbirth']; ?>"
                                                                                        class="form-control" disabled style="display:none;">
                                                                                <?php endif; ?>
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
                                                                                <?php if (in_array('gender', $user_accessible_fields)): ?>
                                                                                    <select name="gender" id="gender" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select Gender</option>
                                                                                        <option value="Male" <?php echo $array['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                                                                        <option value="Female" <?php echo $array['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                                                                        <option value="Binary" <?php echo $array['gender'] == 'Binary' ? 'selected' : ''; ?>>Binary</option>
                                                                                    </select>
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
                                                                                <?php if (in_array('aadhar_available', $user_accessible_fields)): ?>
                                                                                    <select name="aadhar_available" id="aadhar_available" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select</option>
                                                                                        <option value="Yes" <?php echo $array['aadhar_available'] == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                                                                        <option value="No" <?php echo $array['aadhar_available'] == 'No' ? 'selected' : ''; ?>>No</option>
                                                                                    </select>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="studentaadhar">Aadhar of Student:</label></td>
                                                                            <td>
                                                                                <span id="studentaadharText"><?php echo $array['studentaadhar']; ?></span>
                                                                                <?php if (in_array('studentaadhar', $user_accessible_fields)): ?>
                                                                                    <input type="text" name="studentaadhar" id="studentaadhar"
                                                                                        value="<?php echo $array['studentaadhar']; ?>"
                                                                                        class="form-control" disabled style="display:none;"
                                                                                        pattern="\d{12}" title="12-digit Aadhar number">
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label>Upload Student Photo:</label></td>
                                                                            <td>
                                                                                <?php if (in_array('student_photo_raw', $user_accessible_fields) && in_array('identification', $accessible_cards)): ?>
                                                                                    <input type="file" name="student_photo" id="student_photo"
                                                                                        class="form-control" disabled style="display:none;"
                                                                                        accept="image/*" onchange="compressImageBeforeUpload(this)">
                                                                                <?php endif; ?>
                                                                                <?php if (!empty($array['student_photo_raw'])): ?>
                                                                                    <a href="<?php echo $array['student_photo_raw']; ?>" target="_blank">View Current Photo</a><br>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label>Upload Aadhar Card:</label></td>
                                                                            <td>
                                                                                <?php if (in_array('upload_aadhar_card', $user_accessible_fields) && in_array('identification', $accessible_cards)): ?>
                                                                                    <input type="file" name="upload_aadhar_card" id="upload_aadhar_card"
                                                                                        class="form-control" disabled style="display:none;"
                                                                                        accept=".pdf,.jpg,.jpeg,.png" onchange="compressImageBeforeUpload(this)">
                                                                                <?php endif; ?>
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

                                                <!-- Plan & Enrollment Tab -->
                                                <?php if (in_array('plan_enrollment', $accessible_cards)): ?>
                                                    <div id="plan_enrollment" class="tab-pane" role="tabpanel">
                                                        <div class="card" id="plan_enrollment_card">
                                                            <div class="card-header d-flex align-items-center gap-2">
                                                                <span class="me-auto">Plan & Enrollment Information</span>

                                                                <span class="edit-icon" role="button"
                                                                    onclick="toggleEdit('plan_enrollment_card')"
                                                                    title="Edit">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>

                                                                <span class="save-icon" role="button"
                                                                    style="display:none;"
                                                                    onclick="saveChanges('plan_enrollment_card')"
                                                                    title="Save">
                                                                    <i class="bi bi-save"></i>
                                                                </span>

                                                                <span class="info-icon" role="button"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#planUpdateGuideModal"
                                                                    title="View Plan Update Guide">
                                                                    <i class="bi bi-info-circle"></i>
                                                                </span>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label>Current Plan:</label></td>
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
                                                                                                            <i class="bi bi-clock"></i> Pending Change
                                                                                                        </span>
                                                                                                    <?php endif; ?>
                                                                                                </span>
                                                                                                <small class="text-muted">(Plan will be applied to <?php echo $effectiveDateFormatted; ?> month's feesheet)</small>
                                                                                            </p>
                                                                                        </div>
                                                                                        <div class="ms-3">
                                                                                            <?php if (in_array('plan_enrollment', $accessible_cards)): ?>
                                                                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updatePlanModal-<?php echo $array['student_id']; ?>">
                                                                                                    Update Plan
                                                                                                </button>
                                                                                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" data-bs-toggle="modal" data-bs-target="#planHistoryModal-<?php echo $array['student_id']; ?>">
                                                                                                    View History
                                                                                                </button>
                                                                                            <?php endif; ?>
                                                                                        </div>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="supporting_doc">Supporting Document:</label></td>
                                                                                <td>
                                                                                    <?php if (in_array('supporting_doc', $user_accessible_fields)): ?>
                                                                                        <input type="file" name="supporting_doc" id="supporting_doc"
                                                                                            class="form-control" disabled style="display:none;"
                                                                                            accept=".pdf,.jpg,.jpeg,.png" onchange="compressImageBeforeUpload(this)">
                                                                                    <?php endif; ?>
                                                                                    <?php if (!empty($array['supporting_doc'])): ?>
                                                                                        <a href="<?php echo $array['supporting_doc']; ?>" target="_blank">View Document</a><br>
                                                                                    <?php endif; ?>
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
                                                                                <?php if (in_array('guardiansname', $user_accessible_fields)): ?>
                                                                                    <input type="text" name="guardiansname" id="guardiansname"
                                                                                        value="<?php echo $array['guardiansname']; ?>"
                                                                                        class="form-control" disabled style="display:none;">
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="relationwithstudent">Relation with Student:</label></td>
                                                                            <td>
                                                                                <span id="relationwithstudentText"><?php echo $array['relationwithstudent']; ?></span>
                                                                                <?php if (in_array('relationwithstudent', $user_accessible_fields)): ?>
                                                                                    <select name="relationwithstudent" id="relationwithstudent" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select Relation</option>
                                                                                        <option value="Mother" <?php echo $array['relationwithstudent'] == 'Mother' ? 'selected' : ''; ?>>Mother</option>
                                                                                        <option value="Father" <?php echo $array['relationwithstudent'] == 'Father' ? 'selected' : ''; ?>>Father</option>
                                                                                        <option value="Spouse" <?php echo $array['relationwithstudent'] == 'Spouse' ? 'selected' : ''; ?>>Spouse</option>
                                                                                        <option value="Other" <?php echo $array['relationwithstudent'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                                                                    </select>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="guardianaadhar">Aadhar of Guardian:</label></td>
                                                                            <td>
                                                                                <span id="guardianaadharText"><?php echo $array['guardianaadhar']; ?></span>
                                                                                <?php if (in_array('guardianaadhar', $user_accessible_fields)): ?>
                                                                                    <input type="text" name="guardianaadhar" id="guardianaadhar"
                                                                                        value="<?php echo $array['guardianaadhar']; ?>"
                                                                                        class="form-control" disabled style="display:none;"
                                                                                        pattern="\d{12}" title="12-digit Aadhar number">
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="emergency_contact_name">Emergency Contact Name:</label></td>
                                                                            <td>
                                                                                <span id="emergency_contact_nameText"><?php echo $array['emergency_contact_name']; ?></span>
                                                                                <?php if (in_array('emergency_contact_name', $user_accessible_fields)): ?>
                                                                                    <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                                                                                        value="<?php echo $array['emergency_contact_name']; ?>"
                                                                                        class="form-control" disabled style="display:none;">
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="emergency_contact_relation">Relation:</label></td>
                                                                            <td>
                                                                                <span id="emergency_contact_relationText"><?php echo $array['emergency_contact_relation']; ?></span>
                                                                                <?php if (in_array('emergency_contact_relation', $user_accessible_fields)): ?>
                                                                                    <select name="emergency_contact_relation" id="emergency_contact_relation" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select Relation</option>
                                                                                        <option value="Father" <?php echo $array['emergency_contact_relation'] == 'Father' ? 'selected' : ''; ?>>Father</option>
                                                                                        <option value="Mother" <?php echo $array['emergency_contact_relation'] == 'Mother' ? 'selected' : ''; ?>>Mother</option>
                                                                                        <option value="Guardian" <?php echo $array['emergency_contact_relation'] == 'Guardian' ? 'selected' : ''; ?>>Guardian</option>
                                                                                        <option value="Brother" <?php echo $array['emergency_contact_relation'] == 'Brother' ? 'selected' : ''; ?>>Brother</option>
                                                                                        <option value="Sister" <?php echo $array['emergency_contact_relation'] == 'Sister' ? 'selected' : ''; ?>>Sister</option>
                                                                                        <option value="Other" <?php echo $array['emergency_contact_relation'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                                                                    </select>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="emergency_contact_number">Emergency Contact Number:</label></td>
                                                                            <td>
                                                                                <span id="emergency_contact_numberText"><?php echo $array['emergency_contact_number']; ?></span>
                                                                                <?php if (in_array('emergency_contact_number', $user_accessible_fields)): ?>
                                                                                    <input type="tel" name="emergency_contact_number" id="emergency_contact_number"
                                                                                        value="<?php echo $array['emergency_contact_number']; ?>"
                                                                                        class="form-control" disabled style="display:none;"
                                                                                        pattern="\d{10}" maxlength="10">
                                                                                <?php endif; ?>
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
                                                                                <?php if (in_array('stateofdomicile', $user_accessible_fields)): ?>
                                                                                    <select name="stateofdomicile" id="stateofdomicile" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select State</option>
                                                                                        <option value="Uttar Pradesh" <?php echo $array['stateofdomicile'] == 'Uttar Pradesh' ? 'selected' : ''; ?>>Uttar Pradesh</option>
                                                                                        <option value="West Bengal" <?php echo $array['stateofdomicile'] == 'West Bengal' ? 'selected' : ''; ?>>West Bengal</option>
                                                                                        <!-- Add more states as needed -->
                                                                                    </select>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="postaladdress">Current Address:</label></td>
                                                                            <td>
                                                                                <span id="postaladdressText"><?php echo $array['postaladdress']; ?></span>
                                                                                <?php if (in_array('postaladdress', $user_accessible_fields)): ?>
                                                                                    <textarea name="postaladdress" id="postaladdress" class="form-control"
                                                                                        rows="3" disabled style="display:none;"><?php echo $array['postaladdress']; ?></textarea>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="permanentaddress">Permanent Address:</label></td>
                                                                            <td>
                                                                                <span id="permanentaddressText"><?php echo $array['permanentaddress']; ?></span>
                                                                                <?php if (in_array('permanentaddress', $user_accessible_fields)): ?>
                                                                                    <textarea name="permanentaddress" id="permanentaddress" class="form-control"
                                                                                        rows="3" disabled style="display:none;"><?php echo $array['permanentaddress']; ?></textarea>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td colspan="2">
                                                                                <?php if (in_array('address_details', $accessible_cards) && in_array('postaladdress', $user_accessible_fields) && in_array('permanentaddress', $user_accessible_fields)): ?>
                                                                                    <div class="form-check" style="display:none;" id="sameAddressCheckbox">
                                                                                        <input class="form-check-input" type="checkbox" id="same_address"
                                                                                            onclick="copyAddress()">
                                                                                        <label class="form-check-label" for="same_address">
                                                                                            Same as current address
                                                                                        </label>
                                                                                    </div>
                                                                                <?php endif; ?>
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
                                                                                <?php if (in_array('contact', $user_accessible_fields)): ?>
                                                                                    <input type="tel" name="contact" id="contact"
                                                                                        value="<?php echo $array['contact']; ?>"
                                                                                        class="form-control" disabled style="display:none;"
                                                                                        pattern="\d{10}" maxlength="10">
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="alternate_number">Alternate Number:</label></td>
                                                                            <td>
                                                                                <span id="alternate_numberText"><?php echo $array['alternate_number']; ?></span>
                                                                                <?php if (in_array('alternate_number', $user_accessible_fields)): ?>
                                                                                    <input type="tel" name="alternate_number" id="alternate_number"
                                                                                        value="<?php echo $array['alternate_number']; ?>"
                                                                                        class="form-control" disabled style="display:none;"
                                                                                        pattern="\d{10}" maxlength="10">
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="emailaddress">Email Address:</label></td>
                                                                            <td>
                                                                                <span id="emailaddressText"><?php echo $array['emailaddress']; ?></span>
                                                                                <?php if (in_array('emailaddress', $user_accessible_fields)): ?>
                                                                                    <input type="email" name="emailaddress" id="emailaddress"
                                                                                        value="<?php echo $array['emailaddress']; ?>"
                                                                                        class="form-control" disabled style="display:none;">
                                                                                <?php endif; ?>
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
                                                                                <?php if (in_array('caste', $user_accessible_fields)): ?>
                                                                                    <select name="caste" id="caste" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select Caste</option>
                                                                                        <option value="General" <?php echo $array['caste'] == 'General' ? 'selected' : ''; ?>>General</option>
                                                                                        <option value="SC" <?php echo $array['caste'] == 'SC' ? 'selected' : ''; ?>>Scheduled Caste (SC)</option>
                                                                                        <option value="ST" <?php echo $array['caste'] == 'ST' ? 'selected' : ''; ?>>Scheduled Tribe (ST)</option>
                                                                                        <option value="OBC" <?php echo $array['caste'] == 'OBC' ? 'selected' : ''; ?>>Other Backward Class (OBC)</option>
                                                                                        <option value="EWS" <?php echo $array['caste'] == 'EWS' ? 'selected' : ''; ?>>Economically Weaker Section (EWS)</option>
                                                                                    </select>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label>Caste Certificate:</label></td>
                                                                            <td>
                                                                                <?php if (in_array('caste_document', $user_accessible_fields)): ?>
                                                                                    <input type="file" name="caste_document" id="caste_document"
                                                                                        class="form-control" disabled style="display:none;"
                                                                                        accept=".pdf,.jpg,.jpeg,.png" onchange="compressImageBeforeUpload(this)">
                                                                                <?php endif; ?>
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
                                                                                <?php if (in_array('schooladmissionrequired', $user_accessible_fields)): ?>
                                                                                    <select name="schooladmissionrequired" id="schooladmissionrequired" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select</option>
                                                                                        <option value="Yes" <?php echo $array['schooladmissionrequired'] == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                                                                        <option value="No" <?php echo $array['schooladmissionrequired'] == 'No' ? 'selected' : ''; ?>>No</option>
                                                                                    </select>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="nameoftheschool">Name of the School:</label></td>
                                                                            <td>
                                                                                <span id="nameoftheschoolText"><?php echo $array['nameoftheschool']; ?></span>
                                                                                <?php if (in_array('nameoftheschool', $user_accessible_fields)): ?>
                                                                                    <input type="text" name="nameoftheschool" id="nameoftheschool"
                                                                                        value="<?php echo $array['nameoftheschool']; ?>"
                                                                                        class="form-control" disabled style="display:none;">
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="nameoftheboard">Name of the Board:</label></td>
                                                                            <td>
                                                                                <span id="nameoftheboardText"><?php echo $array['nameoftheboard']; ?></span>
                                                                                <?php if (in_array('nameoftheboard', $user_accessible_fields)): ?>
                                                                                    <select name="nameoftheboard" id="nameoftheboard" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select Board</option>
                                                                                        <option value="CBSE" <?php echo $array['nameoftheboard'] == 'CBSE' ? 'selected' : ''; ?>>CBSE</option>
                                                                                        <option value="ICSE" <?php echo $array['nameoftheboard'] == 'ICSE' ? 'selected' : ''; ?>>ICSE</option>
                                                                                        <option value="State Board" <?php echo $array['nameoftheboard'] == 'State Board' ? 'selected' : ''; ?>>State Board</option>
                                                                                    </select>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="medium">Medium:</label></td>
                                                                            <td>
                                                                                <span id="mediumText"><?php echo $array['medium']; ?></span>
                                                                                <?php if (in_array('medium', $user_accessible_fields)): ?>
                                                                                    <select name="medium" id="medium" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select Medium</option>
                                                                                        <option value="English" <?php echo $array['medium'] == 'English' ? 'selected' : ''; ?>>English</option>
                                                                                        <option value="Hindi" <?php echo $array['medium'] == 'Hindi' ? 'selected' : ''; ?>>Hindi</option>
                                                                                        <option value="Other" <?php echo $array['medium'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                                                                    </select>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="preferredbranch">Preferred Branch:</label></td>
                                                                            <td>
                                                                                <span id="preferredbranchText"><?php echo $array['preferredbranch']; ?></span>
                                                                                <?php if (in_array('preferredbranch', $user_accessible_fields)): ?>
                                                                                    <select name="preferredbranch" id="preferredbranch" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select Branch</option>
                                                                                        <option value="Lucknow" <?php echo $array['preferredbranch'] == 'Lucknow' ? 'selected' : ''; ?>>Lucknow</option>
                                                                                        <option value="West Bengal" <?php echo $array['preferredbranch'] == 'West Bengal' ? 'selected' : ''; ?>>West Bengal</option>
                                                                                    </select>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="nameofthesubjects">Select Subjects:</label></td>
                                                                            <td>
                                                                                <span id="nameofthesubjectsText"><?php echo $array['nameofthesubjects']; ?></span>
                                                                                <?php if (in_array('nameofthesubjects', $user_accessible_fields)): ?>
                                                                                    <select name="nameofthesubjects" id="nameofthesubjects" class="form-select" disabled style="display:none;">
                                                                                        <option value="">Select Subject</option>
                                                                                        <option value="ALL Subjects" <?php echo $array['nameofthesubjects'] == 'ALL Subjects' ? 'selected' : ''; ?>>ALL Subjects</option>
                                                                                        <option value="English" <?php echo $array['nameofthesubjects'] == 'English' ? 'selected' : ''; ?>>English</option>
                                                                                        <option value="Embroidery" <?php echo $array['nameofthesubjects'] == 'Embroidery' ? 'selected' : ''; ?>>Embroidery</option>
                                                                                    </select>
                                                                                <?php endif; ?>
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
                                                                                <?php if (in_array('familymonthlyincome', $user_accessible_fields)): ?>
                                                                                    <input type="number" name="familymonthlyincome" id="familymonthlyincome"
                                                                                        value="<?php echo $array['familymonthlyincome']; ?>"
                                                                                        class="form-control" disabled style="display:none;">
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><label for="totalnumberoffamilymembers">Total Family Members:</label></td>
                                                                            <td>
                                                                                <span id="totalnumberoffamilymembersText"><?php echo $array['totalnumberoffamilymembers']; ?></span>
                                                                                <?php if (in_array('totalnumberoffamilymembers', $user_accessible_fields)): ?>
                                                                                    <input type="number" name="totalnumberoffamilymembers" id="totalnumberoffamilymembers"
                                                                                        value="<?php echo $array['totalnumberoffamilymembers']; ?>"
                                                                                        class="form-control" disabled style="display:none;">
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Student Status Tab -->
                                                <?php if (in_array('student_status', $accessible_cards)): ?>
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
                                                                                <td><label for="module">Module:</label></td>
                                                                                <td>
                                                                                    <span id="moduleText"><?php echo $array['module']; ?></span>
                                                                                    <?php if (in_array('module', $user_accessible_fields)): ?>
                                                                                        <select name="module" id="module" class="form-select" disabled style="display:none;">
                                                                                            <option value="">Select Module</option>
                                                                                            <option value="National" <?php echo $array['module'] == 'National' ? 'selected' : ''; ?>>National</option>
                                                                                            <option value="State" <?php echo $array['module'] == 'State' ? 'selected' : ''; ?>>State</option>
                                                                                        </select>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="category">Category:</label></td>
                                                                                <td>
                                                                                    <span id="categoryText"><?php echo $array['category']; ?></span>
                                                                                    <?php if (in_array('category', $user_accessible_fields)): ?>
                                                                                        <select name="category" id="category" class="form-select" disabled style="display:none;">
                                                                                            <option value="">Select Category</option>
                                                                                            <option value="LG1" <?php echo $array['category'] == 'LG1' ? 'selected' : ''; ?>>LG1</option>
                                                                                            <option value="LG2-A" <?php echo $array['category'] == 'LG2-A' ? 'selected' : ''; ?>>LG2-A</option>
                                                                                            <option value="LG2-B" <?php echo $array['category'] == 'LG2-B' ? 'selected' : ''; ?>>LG2-B</option>
                                                                                            <option value="LG2-C" <?php echo $array['category'] == 'LG2-C' ? 'selected' : ''; ?>>LG2-C</option>
                                                                                            <option value="LG3" <?php echo $array['category'] == 'LG3' ? 'selected' : ''; ?>>LG3</option>
                                                                                            <option value="LG4" <?php echo $array['category'] == 'LG4' ? 'selected' : ''; ?>>LG4</option>
                                                                                        </select>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="payment_type">Payment Type:</label></td>
                                                                                <td>
                                                                                    <span id="payment_typeText"><?php echo $array['payment_type']; ?></span>
                                                                                    <?php if (in_array('payment_type', $user_accessible_fields)): ?>
                                                                                        <select name="payment_type" id="payment_type" class="form-select" disabled style="display:none;">
                                                                                            <option value="">Select Type</option>
                                                                                            <option value="Advance" <?php echo $array['payment_type'] == 'Advance' ? 'selected' : ''; ?>>Advance</option>
                                                                                            <option value="Regular" <?php echo $array['payment_type'] == 'Regular' ? 'selected' : ''; ?>>Regular</option>
                                                                                        </select>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label>Date of Application:</label></td>
                                                                                <td>
                                                                                    <?php echo date('d/m/Y', strtotime($array['doa'])); ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="filterstatus">Status:</label></td>
                                                                                <td>
                                                                                    <span id="filterstatusText">
                                                                                        <?php echo $array['filterstatus']; ?>
                                                                                    </span>
                                                                                    <?php if (in_array('filterstatus', $user_accessible_fields)): ?>
                                                                                        <select name="filterstatus" id="filterstatus" class="form-select" disabled style="display:none;"
                                                                                            onchange="handleStatusChange(this)">
                                                                                            <option value="Active" <?php echo $array['filterstatus'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                                                                            <option value="Inactive" <?php echo $array['filterstatus'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                                                        </select>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="effectivefrom">Effective From:</label></td>
                                                                                <td>
                                                                                    <span id="effectivefromText">
                                                                                        <?php echo !empty($array['effectivefrom']) ? date('d/m/Y', strtotime($array['effectivefrom'])) : ''; ?>
                                                                                    </span>
                                                                                    <?php if (in_array('effectivefrom', $user_accessible_fields)): ?>
                                                                                        <input type="date" name="effectivefrom" id="effectivefrom"
                                                                                            value="<?php echo $array['effectivefrom']; ?>"
                                                                                            class="form-control" disabled style="display:none;">
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="remarks">Remarks:</label></td>
                                                                                <td>
                                                                                    <span id="remarksText"><?php echo $array['remarks']; ?></span>
                                                                                    <?php if (in_array('remarks', $user_accessible_fields)): ?>
                                                                                        <textarea name="remarks" id="remarks" class="form-control"
                                                                                            rows="3" disabled style="display:none;"><?php echo $array['remarks']; ?></textarea>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Misc Tab -->
                                                <?php if (in_array('misc', $accessible_cards)): ?>
                                                    <div id="misc" class="tab-pane" role="tabpanel">
                                                        <div class="card" id="misc_card">
                                                            <div class="card-header">
                                                                Miscellaneous Information
                                                                <span class="edit-icon" onclick="toggleEdit('misc_card')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" style="display:none;" onclick="saveChanges('misc_card')">
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

                                                <!-- Admin Documents Tab -->
                                                <?php if (in_array('admin_console', $accessible_cards)): ?>
                                                    <div id="admin_console" class="tab-pane" role="tabpanel">
                                                        <div class="card" id="admin_console_card">
                                                            <div class="card-header">
                                                                Admin console
                                                                <span class="edit-icon" onclick="toggleEdit('admin_console_card')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" style="display:none;" onclick="saveChanges('admin_console_card')">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="photourl">Photo URL:</label></td>
                                                                                <td>
                                                                                    <?php if (in_array('photourl', $user_accessible_fields)): ?>
                                                                                        <input type="url" name="photourl" id="photourl"
                                                                                            value="<?php echo $array['photourl']; ?>"
                                                                                            class="form-control" disabled style="display:none;"
                                                                                            placeholder="Enter photo URL">
                                                                                    <?php endif; ?>
                                                                                    <span id="photourlText">
                                                                                        <?php if (!empty($array['photourl'])): ?>
                                                                                            <a href="<?php echo $array['photourl']; ?>" target="_blank">View Photo</a>
                                                                                        <?php else: ?>
                                                                                            No photo URL
                                                                                        <?php endif; ?>
                                                                                    </span>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td><label for="scode">Scode:</label></td>
                                                                                <td>
                                                                                    <!-- View mode -->
                                                                                    <span id="scodeText">
                                                                                        <?php
                                                                                        if (!empty($array['scode'])) {
                                                                                            echo htmlspecialchars($array['scode']);
                                                                                        } else {
                                                                                            echo '<span class="text-muted">Not generated</span>';
                                                                                        }
                                                                                        ?>
                                                                                    </span>

                                                                                    <!-- Edit mode (hidden by default) -->
                                                                                    <?php if (in_array('scode', $user_accessible_fields)): ?>
                                                                                        <div class="input-group" id="scodeInputGroup" style="display:none; width: 300px;">
                                                                                            <input type="text" name="scode" id="scodeInput"
                                                                                                value="<?= htmlspecialchars($array['scode'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                                                                class="form-control"
                                                                                                placeholder="Enter Scode manually">
                                                                                            <button class="btn btn-outline-secondary" type="button" id="generateScodeBtn">
                                                                                                <i class="bi bi-arrow-repeat"></i> Generate
                                                                                            </button>
                                                                                        </div>
                                                                                    <?php endif; ?>
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

        <!-- Bootstrap Modal -->
        <div class="modal fade" id="planUpdateGuideModal" tabindex="-1" aria-labelledby="planUpdateGuideModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="planUpdateGuideModalLabel">Student Plan Update – User Guide</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6><strong>Purpose</strong></h6>
                        <p>This document explains the rules, conditions, and outcomes when applying a new plan (category/class) for a student. It helps end users understand <strong>when a plan update will succeed, fail, or adjust automatically</strong>.</p>

                        <h6><strong>1. When to Apply a Plan Update</strong></h6>
                        <ul>
                            <li>Type of Admission</li>
                            <li>Class</li>
                            <li>Effective From Date</li>
                        </ul>
                        <p>If none of these fields are provided, <strong>no plan update will occur</strong>.</p>

                        <h6><strong>2. Effective Date Rules</strong></h6>
                        <ul>
                            <li>The plan cannot start before the student's month of admission.</li>
                            <li>If the first plan is applied in the admission month, the system automatically uses the Date of Admission (DOA).</li>
                            <li>If applying a plan in a later month, the system may create a default plan from admission to the month before the new plan automatically.</li>
                        </ul>

                        <h6><strong>3. Validation & Restrictions</strong></h6>
                        <ul>
                            <li><strong>No Duplicate Plan:</strong> If the student is already on the selected plan, the update is not allowed.</li>
                            <li><strong>No Overlap with Finalized Plans:</strong> If a finalized plan already covers the effective date, the update is blocked.</li>
                            <li><strong>Handling Open Plans:</strong> Any existing open-ended plans that overlap the new plan will be closed one day before the new plan starts.</li>
                        </ul>

                        <h6><strong>4. Automatic System Actions</strong></h6>
                        <ul>
                            <li><strong>Default Plan Creation:</strong> If this is the first plan update and effective month is after admission month, a default plan is created automatically.</li>
                            <li><strong>Student Master Update:</strong> If the new plan starts in the current month or earlier, the student’s master record is updated.</li>
                        </ul>

                        <h6><strong>5. Remarks and Audit</strong></h6>
                        <ul>
                            <li>Optional remarks can be added while applying a plan.</li>
                            <li>All changes are tracked with updated by and timestamp.</li>
                            <li>Invalidated or closed plans are kept for historical records.</li>
                        </ul>

                        <h6><strong>6. Key Notes for Users</strong></h6>
                        <ul>
                            <li>Check the admission month before setting effective date.</li>
                            <li>Avoid applying the same plan as current; system will block it.</li>
                            <li>Changing plan mid-month will adjust overlaps automatically.</li>
                            <li>Remarks help in audit and tracking.</li>
                            <li>The update is transactional; either everything is updated or nothing is changed if validation fails.</li>
                        </ul>

                        <h6><strong>7. Examples</strong></h6>
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Scenario</th>
                                    <th>Effective Date</th>
                                    <th>Outcome</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Plan same as admission plan</td>
                                    <td>Any</td>
                                    <td>Error: Plan already exists from admission</td>
                                </tr>
                                <tr>
                                    <td>First plan update in admission month</td>
                                    <td>DOA</td>
                                    <td>Effective date set to DOA</td>
                                </tr>
                                <tr>
                                    <td>Plan update in future month</td>
                                    <td>Month after DOA</td>
                                    <td>Default plan created from DOA to previous day</td>
                                </tr>
                                <tr>
                                    <td>Overlapping finalized plan exists</td>
                                    <td>Any</td>
                                    <td>Error: Period already covered</td>
                                </tr>
                                <tr>
                                    <td>Open-ended overlapping plan exists</td>
                                    <td>Any</td>
                                    <td>Previous plan closed one day before new plan</td>
                                </tr>
                            </tbody>
                        </table>

                        <p><strong>Summary:</strong> Ensure correct admission month, effective date, and existing plans before submitting a new plan update.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    </main>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script src="../assets_new/js/image-compressor-200kb.js"></script>
    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  <script src="../assets_new/js/text-refiner.js"></script>

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

        // Generate scode in format: 693ca50c910501faf5 (16-char hex)
        function generateScode() {
            // Create array for 8 random bytes (16 hex chars)
            const bytes = new Uint8Array(8);

            // Use crypto.getRandomValues for secure random numbers
            if (window.crypto && window.crypto.getRandomValues) {
                window.crypto.getRandomValues(bytes);
            } else {
                // Fallback for older browsers
                for (let i = 0; i < 8; i++) {
                    bytes[i] = Math.floor(Math.random() * 256);
                }
            }

            // Convert bytes to hex string
            let hex = '';
            for (let i = 0; i < bytes.length; i++) {
                // Convert byte to two-digit hex
                const hexByte = bytes[i].toString(16).padStart(2, '0');
                hex += hexByte;
            }

            return hex; // Returns format: 693ca50c910501faf5
        }

        // Handle Generate Scode button click
        $(document).ready(function() {
            // Generate scode when button is clicked
            $(document).on('click', '#generateScodeBtn', function() {
                const scodeInput = document.getElementById('scodeInput');
                if (scodeInput) {
                    const newScode = generateScode();
                    scodeInput.value = newScode;

                    // Show toast notification
                    const toast = document.createElement('div');
                    toast.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
                    toast.style.zIndex = '9999';
                    toast.innerHTML = `
                <strong>Scode Generated!</strong> ${newScode}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
                    document.body.appendChild(toast);

                    // Auto-remove after 3 seconds
                    setTimeout(() => {
                        toast.remove();
                    }, 3000);
                }
            });
        });

        // Update the toggleEdit function to handle scode
        function toggleEdit(cardId) {
            const card = document.getElementById(cardId);
            if (!card) {
                console.error('Card not found:', cardId);
                return;
            }

            const isEditing = card.classList.contains('editing');
            console.log('Toggling edit for:', cardId, 'isEditing:', isEditing);

            if (isEditing) {
                // Switch to view mode
                card.classList.remove('editing');

                // Hide all inputs
                card.querySelectorAll('input, select, textarea').forEach(element => {
                    element.disabled = true;
                    element.style.display = 'none';
                });

                // Hide input groups
                const scodeInputGroup = document.getElementById('scodeInputGroup');
                if (scodeInputGroup) {
                    scodeInputGroup.style.display = 'none';
                }

                // Update scode text from input
                const scodeText = document.getElementById('scodeText');
                const scodeInput = document.getElementById('scodeInput');
                if (scodeText && scodeInput) {
                    const value = scodeInput.value.trim();
                    if (value) {
                        scodeText.innerHTML = value;
                        scodeText.className = '';
                    } else {
                        scodeText.innerHTML = '<span class="text-muted">Not generated</span>';
                    }
                    scodeText.style.display = 'inline';
                }

                // Show all text spans
                card.querySelectorAll('span[id$="Text"]').forEach(span => {
                    span.style.display = 'inline';
                });

                // Show edit icon, hide save
                const editIcon = card.querySelector('.edit-icon');
                const saveIcon = card.querySelector('.save-icon');
                if (editIcon) editIcon.style.display = 'inline';
                if (saveIcon) saveIcon.style.display = 'none';

            } else {
                // Switch to edit mode
                card.classList.add('editing');

                // Show and enable all inputs
                card.querySelectorAll('input, select, textarea').forEach(element => {
                    element.disabled = false;
                    if (element.type === 'file') {
                        element.style.display = 'block';
                    } else if (element.type !== 'hidden') {
                        element.style.display = 'inline-block';
                    }
                });

                // Show scode input group
                const scodeInputGroup = document.getElementById('scodeInputGroup');
                if (scodeInputGroup) {
                    scodeInputGroup.style.display = 'flex';
                }

                // Hide scode text
                const scodeText = document.getElementById('scodeText');
                if (scodeText) {
                    scodeText.style.display = 'none';
                }

                // Hide other text spans
                card.querySelectorAll('span[id$="Text"]').forEach(span => {
                    const fieldName = span.id.replace('Text', '');
                    // Keep photourl text visible if it's a link
                    if (fieldName !== 'photourl') {
                        span.style.display = 'none';
                    }
                });

                // Hide edit icon, show save
                const editIcon = card.querySelector('.edit-icon');
                const saveIcon = card.querySelector('.save-icon');
                if (editIcon) editIcon.style.display = 'none';
                if (saveIcon) saveIcon.style.display = 'inline';

                // Show same address checkbox if applicable
                const sameAddressCheckbox = document.getElementById('sameAddressCheckbox');
                if (sameAddressCheckbox && cardId === 'address_details_card') {
                    sameAddressCheckbox.style.display = 'block';
                }
            }
        }

        // Use the PHP array you just created
        const requiredFields = <?php echo json_encode($required_fields_with_names); ?>;

        // Modified saveChanges function that validates ALL visible fields in ALL cards
        function saveChanges(cardId) {
            // Get ALL cards, not just the current one
            const allCards = document.querySelectorAll('.card[id$="_card"]');
            let missingFields = [];

            // Check ALL visible inputs in ALL cards
            allCards.forEach(card => {
                const visibleInputs = card.querySelectorAll('input:not([type="hidden"]), select, textarea');

                visibleInputs.forEach(input => {
                    if (input.style.display !== 'none' && !input.disabled) {
                        const fieldName = input.name;

                        // Check if this field is in required fields
                        if (requiredFields[fieldName]) {
                            const value = input.value ? input.value.trim() : '';

                            // For select elements, check if a valid option is selected
                            if (input.tagName === 'SELECT' && (!value || value === '')) {
                                missingFields.push(requiredFields[fieldName]);
                            }
                            // For other inputs, check if empty
                            else if (!value) {
                                missingFields.push(requiredFields[fieldName]);
                            }
                        }
                    }
                });
            });

            // If there are missing fields, show alert and prevent submission
            if (missingFields.length > 0) {
                alert(`Please fill ${missingFields.length} required field(s): ${missingFields.join(', ')}`);
                return false;
            }

            // If validation passes, submit the form
            document.getElementById('studentProfileForm').submit();
            return true;
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
    <script>
        function handleStatusChange(selectElement) {
            const newStatus = selectElement.value;

            // Get the inputs - they might be hidden if not in edit mode
            const effectiveFromInput = document.getElementById('effectivefrom');
            const remarksTextarea = document.getElementById('remarks');

            // Check if we're in edit mode (inputs are visible)
            const isEditMode = selectElement.style.display !== 'none';

            if (!isEditMode) {
                // If not in edit mode, show a simple alert
                alert('Please click the edit pencil icon first to enable editing before changing status.');
                // Reset to original value
                selectElement.value = selectElement.getAttribute('data-original-value') || '<?php echo $array["filterstatus"]; ?>';
                return;
            }

            // Store original value when first clicked (optional enhancement)
            if (!selectElement.hasAttribute('data-original-value')) {
                selectElement.setAttribute('data-original-value', selectElement.value);
            }

            // Check if user has access to these fields (they exist and are visible)
            const hasEffectiveFromAccess = effectiveFromInput && effectiveFromInput.style.display !== 'none';
            const hasRemarksAccess = remarksTextarea && remarksTextarea.style.display !== 'none';

            // Only proceed if user has access to at least one of the related fields
            if (!hasEffectiveFromAccess && !hasRemarksAccess) {
                console.log('User does not have access to related fields');
                return;
            }

            // Get current values
            const currentEffectiveFrom = hasEffectiveFromAccess ? effectiveFromInput.value : '';
            const currentRemarks = hasRemarksAccess ? remarksTextarea.value : '';

            // Prepare the new remark line
            const today = new Date().toISOString().split('T')[0]; // Get today's date in YYYY-MM-DD format
            let newRemarkLine = `\n${today} - Status has been changed to ${newStatus}.`;

            // If changing to Active, reset effective from date but mention previous date in remarks
            if (newStatus === 'Active') {
                if (hasEffectiveFromAccess && currentEffectiveFrom) {
                    newRemarkLine = `\nPrevious Effective From: ${currentEffectiveFrom}${newRemarkLine}`;
                    effectiveFromInput.value = ''; // Reset effective from date
                }
            }
            // If changing to Inactive, set effective from date to today if empty
            else if (newStatus === 'Inactive') {
                if (hasEffectiveFromAccess && !currentEffectiveFrom) {
                    effectiveFromInput.value = today;
                }
            }

            // Update remarks if user has access
            if (hasRemarksAccess) {
                remarksTextarea.value = currentRemarks + newRemarkLine;
            }
        }
    </script>
    <!-- Update Plan Modal -->
    <div class="modal fade" id="updatePlanModal-<?php echo $array['student_id']; ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="updatePlanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updatePlanModalLabel">Update Student Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="planUpdateForm-<?php echo $array['student_id']; ?>">
                        <div class="mb-3">
                            <label for="modal-division-select-<?php echo $array['student_id']; ?>" class="form-label">Division:</label>
                            <select class="form-select" id="modal-division-select-<?php echo $array['student_id']; ?>" name="plan_update_division" required>
                                <option value="">--Select Division--</option>
                                <option value="kalpana" <?php echo (!empty($array['division']) && $array['division'] == 'kalpana') ? 'selected' : '' ?>>Kalpana Buds School</option>
                                <option value="rssi" <?php echo (!empty($array['division']) && $array['division'] == 'rssi') ? 'selected' : '' ?>>RSSI NGO</option>
                                <option value="coaching" <?php echo (!empty($array['division']) && $array['division'] == 'coaching') ? 'selected' : '' ?>>Coaching</option>
                            </select>
                            <small class="form-text text-muted">Please select the division you're applying for.</small>
                        </div>

                        <!-- Class Dropdown -->
                        <div class="mb-3">
                            <label for="modal-class-select-<?php echo $array['student_id']; ?>" class="form-label">Class:</label>
                            <select class="form-select" id="modal-class-select-<?php echo $array['student_id']; ?>" name="plan_update_class" required>
                                <?php if (empty($array['class'])) { ?>
                                    <option value="" selected>--Select Class--</option>
                                <?php } else { ?>
                                    <option value="">--Select Class--</option>
                                    <option value="<?php echo $array['class']; ?>" selected><?php echo $array['class']; ?></option>
                                <?php } ?>
                            </select>
                            <small class="form-text text-muted" id="modal-class-help-<?php echo $array['student_id']; ?>">Please select the class the student wants to join.</small>
                        </div>

                        <div class="mb-3">
                            <label for="modal-type-of-admission-<?php echo $array['student_id']; ?>" class="form-label">Access Category:</label>
                            <select class="form-select" id="modal-type-of-admission-<?php echo $array['student_id']; ?>" name="plan_update_type_of_admission" required>
                                <?php if (empty($array['type_of_admission'])) { ?>
                                    <option value="" selected>--Select Access Category--</option>
                                <?php } else { ?>
                                    <option value="">--Select Access Category--</option>
                                    <option value="<?php echo $array['type_of_admission']; ?>" selected><?php echo $array['type_of_admission']; ?></option>
                                <?php } ?>
                            </select>
                            <small id="modal-type-of-admission-help-<?php echo $array['student_id']; ?>" class="form-text text-muted">
                                Please select the type of access you are applying for.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="modal-effective-from-date-<?php echo $array['student_id']; ?>" class="form-label">Effective From Month:</label>
                            <input type="month" class="form-control" id="modal-effective-from-date-<?php echo $array['student_id']; ?>" name="plan_update_effective_from_date" required>
                            <small class="form-text text-muted">
                                The selected plan will be applied to the feesheet starting from the first day of the selected month.
                            </small>
                        </div>
                        <div class="mb-3">
                            <label for="modal-remarks-<?php echo $array['student_id']; ?>" class="form-label">
                                Remarks:
                            </label>
                            <textarea
                                class="form-control"
                                id="modal-remarks-<?php echo $array['student_id']; ?>"
                                name="plan_update_remarks"
                                rows="3"
                                required
                                placeholder="Enter reason or remarks for plan update"></textarea>
                            <small class="form-text text-muted">Remarks are mandatory for tracking plan update reasons.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save-plan-changes-<?php echo $array['student_id']; ?>">Update Plan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Plan History Modal -->
    <div class="modal fade" id="planHistoryModal-<?php echo $array['student_id']; ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="planHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="planHistoryModalLabel">Plan Change History for <?php echo htmlspecialchars($array['studentname']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="planHistoryLoading-<?php echo $array['student_id']; ?>" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading history...</span>
                        </div>
                        <p class="mt-2">Loading plan history...</p>
                    </div>
                    <div id="planHistoryContent-<?php echo $array['student_id']; ?>" class="table-responsive" style="display: none;">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Plan Type</th>
                                    <th>Class</th>
                                    <th>Effective From</th>
                                    <th>Effective Until</th>
                                    <th>Remarks</th>
                                    <th>Changed On</th>
                                    <th>Changed By</th>
                                </tr>
                            </thead>
                            <tbody id="planHistoryBody-<?php echo $array['student_id']; ?>">
                                <!-- History data will be loaded here via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <div id="planHistoryEmpty-<?php echo $array['student_id']; ?>" class="text-center py-4" style="display: none;">
                        <p class="text-muted">No plan history found</p>
                    </div>
                    <div id="planHistoryError-<?php echo $array['student_id']; ?>" class="text-center py-4" style="display: none;">
                        <p class="text-danger">Failed to load plan history</p>
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
            $('#updatePlanModal-<?php echo $array["student_id"]; ?>').on('show.bs.modal', function() {
                const studentId = '<?php echo $array["student_id"]; ?>';

                $('#modal-division-select-' + studentId).val('<?php echo $array["division"] ?? ""; ?>');
                $('#modal-class-select-' + studentId).val('<?php echo $array["class"] ?? ""; ?>');
                $('#modal-type-of-admission-' + studentId).val('<?php echo $array["type_of_admission"] ?? ""; ?>');

                // Always keep Effective From blank
                $('#modal-effective-from-date-' + studentId).val('');

                // If division is selected, load classes and plans
                if ($('#modal-division-select-' + studentId).val()) {
                    loadClassesForDivision(studentId, $('#modal-division-select-' + studentId).val());
                    loadPlansForDivision(studentId, $('#modal-division-select-' + studentId).val());
                }
            });

            // When division changes, load corresponding classes and plans
            $(document).on('change', '[id^="modal-division-select-"]', function() {
                const idParts = this.id.split('-');
                const studentId = idParts[idParts.length - 1];
                const division = $(this).val();
                loadClassesForDivision(studentId, division);
                loadPlansForDivision(studentId, division);
            });

            function loadClassesForDivision(studentId, division) {
                const classSelect = $('#modal-class-select-' + studentId)[0];
                const helpText = $('#modal-class-help-' + studentId)[0];

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
                        const previousSelection = '<?php echo $array["class"] ?? ""; ?>';
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

            function loadPlansForDivision(studentId, division) {
                const admissionSelect = $('#modal-type-of-admission-' + studentId)[0];
                const helpText = $('#modal-type-of-admission-help-' + studentId)[0];

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
                        const previousSelection = '<?php echo $array["type_of_admission"] ?? ""; ?>';
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

            // Save plan changes - ENHANCED CONFIRMATION
            $(document).on('click', '[id^="save-plan-changes-"]', function() {

                const studentId = this.id.split('-').pop();

                const division = $('#modal-division-select-' + studentId).val();
                const classVal = $('#modal-class-select-' + studentId).val();
                const admissionType = $('#modal-type-of-admission-' + studentId).val();
                const effectiveMonth = $('#modal-effective-from-date-' + studentId).val();
                const remarks = $('#modal-remarks-' + studentId).val();

                // Current values from server
                const currentClass = '<?php echo $array["class"] ?? ""; ?>';
                const currentAdmissionType = '<?php echo $array["type_of_admission"] ?? ""; ?>';

                // Detect changes
                const classChanged = classVal && classVal !== currentClass;
                const admissionTypeChanged = admissionType && admissionType !== currentAdmissionType;
                const dateChanged = !!effectiveMonth;

                // Nothing changed
                if (!classChanged && !admissionTypeChanged && !dateChanged) {
                    alert('No changes detected. Please modify at least one field.');
                    return;
                }

                // Mandatory checks
                if (!effectiveMonth) {
                    alert('Please select an effective month.');
                    return;
                }

                if (classChanged && !classVal) {
                    alert('Please select a class.');
                    return;
                }

                if (admissionTypeChanged && !admissionType) {
                    alert('Please select an access category.');
                    return;
                }

                if (!remarks) {
                    alert('Remarks are required for plan updates.');
                    return;
                }

                // Format effective date (Month Year)
                const [year, month] = effectiveMonth.split('-');
                const dateObj = new Date(year, month - 1);
                const monthName = dateObj.toLocaleString('default', {
                    month: 'long'
                });
                const effectiveDateDisplay = `${monthName} ${year}`;

                // Resolve final values (new plan)
                const newPlanType = admissionTypeChanged ? admissionType : currentAdmissionType;
                const newClass = classChanged ? classVal : currentClass;

                // Build clear confirmation message
                let confirmationMessage = '';
                confirmationMessage += 'STUDENT PLAN CHANGE CONFIRMATION\n';
                confirmationMessage += '----------------------------------\n\n';

                confirmationMessage += 'CURRENT PLAN:\n';
                confirmationMessage += `• Plan Type : ${currentAdmissionType || 'N/A'}\n`;
                confirmationMessage += `• Class     : ${currentClass || 'N/A'}\n\n`;

                confirmationMessage += 'NEW PLAN:\n';
                confirmationMessage += `• Plan Type : ${newPlanType}\n`;
                confirmationMessage += `• Class     : ${newClass}\n`;
                confirmationMessage += `• Effective From : ${effectiveDateDisplay}\n\n`;

                confirmationMessage += 'This change will update the student’s plan history.\n';
                confirmationMessage += 'Click OK to confirm or Cancel to review changes.';

                // Final confirmation
                if (confirm(confirmationMessage)) {
                    submitPlanUpdate(
                        studentId,
                        newClass,
                        newPlanType,
                        effectiveMonth + '-01',
                        remarks
                    );
                }
            });

            // Simple submit function
            function submitPlanUpdate(studentId, classVal, admissionType, effectiveFromDate, remarks) {
                const mainForm = document.getElementById('studentProfileForm');

                // Add hidden inputs
                const addHiddenInput = (name, value) => {
                    const existing = mainForm.querySelector(`input[name="${name}"]`);
                    if (existing) existing.remove();

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value || '';
                    mainForm.appendChild(input);
                };

                addHiddenInput('plan_update_class', classVal);
                addHiddenInput('plan_update_type_of_admission', admissionType);
                addHiddenInput('plan_update_effective_from_date', effectiveFromDate);
                addHiddenInput('plan_update_remarks', remarks)

                // Close modal and submit
                $('#updatePlanModal-' + studentId).modal('hide');
                setTimeout(() => mainForm.submit(), 300);
            }
        });
        // Load plan history when modal opens
        $('[id^="planHistoryModal-"]').on('show.bs.modal', function() {
            const modalId = $(this).attr('id');
            const studentId = modalId.split('-').pop();

            // Get references to elements
            const loadingEl = $('#planHistoryLoading-' + studentId);
            const contentEl = $('#planHistoryContent-' + studentId);
            const emptyEl = $('#planHistoryEmpty-' + studentId);
            const errorEl = $('#planHistoryError-' + studentId);
            const bodyEl = $('#planHistoryBody-' + studentId);

            // Reset UI states
            loadingEl.show();
            contentEl.hide();
            emptyEl.hide();
            errorEl.hide();
            bodyEl.empty();

            // Load history via AJAX
            $.ajax({
                url: 'get_plan_history.php',
                type: 'GET',
                data: {
                    student_id: studentId
                },
                dataType: 'json',
                success: function(response) {
                    loadingEl.hide();

                    if (response.success && response.data.length > 0) {
                        let html = '';
                        const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD

                        response.data.forEach(function(plan) {
                            const effectiveFrom = plan.effective_from;
                            const effectiveUntil = plan.effective_until;

                            // Determine if this is the current active plan
                            let isCurrent = false;
                            if (effectiveUntil === null) {
                                isCurrent = (today >= effectiveFrom);
                            } else {
                                isCurrent = (today >= effectiveFrom && today <= effectiveUntil);
                            }
                            if (today < effectiveFrom) {
                                isCurrent = false;
                            }

                            const rowClass = isCurrent ? 'table-primary' : '';
                            const fromDate = new Date(effectiveFrom);
                            const fromFormatted = fromDate.toLocaleDateString('en-GB', {
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric'
                            });

                            let untilFormatted = '';
                            if (effectiveUntil) {
                                const untilDate = new Date(effectiveUntil);
                                untilFormatted = untilDate.toLocaleDateString('en-GB', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric'
                                });
                            }

                            const createdDate = new Date(plan.created_at);
                            const createdFormatted = createdDate.toLocaleDateString('en-GB', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: true
                            });

                            // Show name with ID in parentheses
                            const creatorDisplay = plan.created_by_name === plan.created_by_id ?
                                plan.created_by_id :
                                `${plan.created_by_name} (${plan.created_by_id})`;

                            html += `<tr class="${rowClass}">
                        <td>${escapeHtml(plan.category_type)}</td>
                        <td>${escapeHtml(plan.class || '')}</td>
                        <td>${fromFormatted}</td>
                        <td>${untilFormatted}</td>
                        <td>${escapeHtml(plan.remarks)}</td>
                        <td>${createdFormatted}</td>
                        <td>${escapeHtml(creatorDisplay)}</td>
                    </tr>`;
                        });

                        bodyEl.html(html);
                        contentEl.show();
                    } else {
                        emptyEl.show();
                    }
                },
                error: function() {
                    loadingEl.hide();
                    errorEl.show();
                }
            });
        });

        // Helper function to escape HTML - FIXED VERSION
        function escapeHtml(text) {
            // Handle null, undefined, or empty values
            if (text === null || text === undefined || text === '') {
                return '';
            }

            // Convert to string
            const stringText = String(text);

            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return stringText.replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        }
    </script>
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
                minimumInputLength: 2,
                placeholder: 'Search by Student ID or Name',
                allowClear: true,
                width: '100%', // Ensure proper width
            });

            // Pre-select if URL has student_id parameter
            <?php if (!empty($_GET['student_id'])): ?>
                var currentStudentId = '<?php echo $_GET['student_id']; ?>';

                // If it's already in the dropdown, select it
                var $studentSelect = $('#student_id');
                if ($studentSelect.find('option[value="' + currentStudentId + '"]').length === 0) {
                    // Fetch student name and add to dropdown
                    $.ajax({
                        url: 'fetch_students.php',
                        data: {
                            q: currentStudentId,
                            exact: true
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.results && data.results.length > 0) {
                                var student = data.results[0];
                                var option = new Option(
                                    student.text,
                                    student.id,
                                    true,
                                    true
                                );
                                $studentSelect.append(option).trigger('change');
                            } else {
                                // Just show the ID if we can't find details
                                var option = new Option(
                                    currentStudentId,
                                    currentStudentId,
                                    true,
                                    true
                                );
                                $studentSelect.append(option).trigger('change');
                            }
                        }
                    });
                }
            <?php endif; ?>

            // Fix for form submission - ensure select is valid
            $('form').on('submit', function(e) {
                var $select = $('#student_id');
                if (!$select.val()) {
                    e.preventDefault();
                    alert('Please select a student');
                    $select.focus();
                }
            });
        });
    </script>
    <!-- Add tooltip functionality if needed -->
    <script>
        $(document).ready(function() {
            // Add tooltip to status flag
            $('.status-flag').each(function() {
                var status = $(this).data('status');
                var tooltipText = 'Student Status: ' + status;

                $(this).attr('title', tooltipText);
                $(this).attr('data-bs-toggle', 'tooltip');
                $(this).attr('data-bs-placement', 'top');
            });

            // Initialize Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Optional: Add click to copy status
            $('.status-flag').on('click', function() {
                var status = $(this).data('status');
                navigator.clipboard.writeText(status).then(function() {
                    var originalText = $(this).find('.flag-text').text();
                    $(this).find('.flag-text').text('Copied!');

                    setTimeout(function() {
                        $('.status-flag .flag-text').text(originalText);
                    }, 1000);
                }.bind(this));
            });
        });
    </script>
</body>

</html>