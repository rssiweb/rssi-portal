<?php
function isLoggedIn(string $key)
{
    if (!isset($_SESSION[$key]) || !$_SESSION[$key]) {
        return false;
    }
    return true;
}
@include("member_data.php");
@include("student_data.php");

function passwordCheck($password_updated_by, $password_updated_on, $default_pass_updated_on)
{
    if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {
        // Start output buffering
        ob_start();

        // Show the alert and redirect using JavaScript
        echo '<script type="text/javascript">';
        echo 'alert("For security reasons, you must change your password before accessing additional features.");';
        echo 'window.location.href = "defaultpasswordreset.php";';
        echo '</script>';

        // End output buffering and send the output
        ob_end_flush();

        // Use exit to stop further script execution
        exit();
    }
}

function checkPageAccess()
{
    global $filterstatus;
    global $role;
    $currentUrl = $_SERVER['REQUEST_URI'];
    // Extract the page name between the last "/" and ".php"
    $lastSlashPosition = strrpos($currentUrl, "/");
    $lastDotPhpPosition = strrpos($currentUrl, ".php");
    $pageName = substr($currentUrl, $lastSlashPosition + 1, $lastDotPhpPosition - $lastSlashPosition - 1);

    // Define access control rules based on user status
    $statusAccessControl = array(
        "Inactive" => array("home", "leave", "document", "my_certificate", "pay_details", "allocation", "my_appraisal", "appraisee_response", "redeem_gems", "reimbursement", "reimbursementstatus", "myprofile") // Add pages for inactive users if needed
    );

    // Access control is role-based, where pages created under the "Admin" role are only accessible by "Admin" users, and pages created under the "Offline Manager" role are only accessible by "Offline Manager" users. Pages not explicitly assigned to a specific role are accessible by all users.
    $roleAccessControl = array(
        "Admin" => array("scan", "dashboard", "student", "fees", "process", "ipf-management", "faculty", "facultyexp", "leave_admin", "payroll_processing", "donationinfo_admin", "pms", "onexit", "userlog", "onboarding", "exit", "visitor", "admission_admin", "expletter", "offerletter", "archive_approval", "bankdetails_admin", "exam_create", "exam_data_update", "vrc_dashboard", "shift_planner", "view_shift", "interview_central", "technical_interview", "hr_interview", "tap_doc_approval","talent_pool","applicant_profile"),
        "Offline Manager" => array("scan", "dashboard", "student", "admission_admin", "onboarding", "exit", "visitor", "interview_central", "technical_interview", "tap_doc_approval"),
        "Advanced User" => array("scan", "student"),
        "User" => array("")
    );

    // Check user status access control
    if ($filterstatus != 'Active' && !in_array($pageName, $statusAccessControl['Inactive'])) {
        return "Access Denied. Your account status is currently inactive. Please contact the administrator for assistance.";
    }

    // Check if page name is mentioned in role access control
    $pageNameExists = false;
    foreach ($roleAccessControl as $rolePages) {
        if (in_array($pageName, $rolePages)) {
            $pageNameExists = true;
            break;
        }
    }
    if (!$pageNameExists) {
        return "allow"; // No need to check further, access is allowed for all roles
    }

    // Check role access control for the current user's role
    if (($pageNameExists) && !in_array($pageName, $roleAccessControl[$role])) {
        return "Access Denied. You do not have permission to access this page.";
    }
    // If everything is fine, allow access
    return "allow";
}
function validation()
{
    global $password_updated_by;
    global $password_updated_on;
    global $default_pass_updated_on;
    // Check default password
    passwordCheck($password_updated_by, $password_updated_on, $default_pass_updated_on);

    $checkPageAccessResult = checkPageAccess();
    if ($checkPageAccessResult != "allow") {
        echo '<script type="text/javascript">';
        echo 'alert("' . $checkPageAccessResult . '");';
        echo 'window.location.href = "home.php";';
        echo '</script>';
        exit; // Exit to prevent further execution
    }
}
