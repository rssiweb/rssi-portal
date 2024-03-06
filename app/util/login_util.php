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

function passwordCheck()
{
    global $password_updated_by;
    global $password_updated_on;
    global $default_pass_updated_on;
    if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

        return "For security reasons, you must change your password before accessing additional features.";
    }
}
function checkPageAccess()
{
    global $filterstatus;
    global $role;
    $currentUrl = $_SERVER['REQUEST_URI'];

    if ($currentUrl == "/rssi-member/leave_admin.php") {

        if ($filterstatus != 'Active') {
            return "Access Denied. Your account status is currently inactive. Please contact the administrator for assistance.";
        }
        if ($role != 'Admin') {
            return "Access Denied. Only administrators have permission to access this page.";
        }
    }
}
