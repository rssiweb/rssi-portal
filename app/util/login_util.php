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

    // Fetch roles and associated pages dynamically from the database
    $roleAccessControl = array();

    // Add a default "User" role entry
    $roleAccessControl["User"] = array(""); 

    // Legacy db connection using pg_connect
    $servername = $_ENV["DB_HOST"];
    $username = $_ENV["DB_USER"];
    $password = $_ENV["DB_PASSWORD"];
    $dbname = $_ENV["DB_NAME"];
    $connection_string = "host=$servername user=$username password=$password dbname=$dbname";
    $con = pg_connect($connection_string);

    // SQL Query to fetch role names and associated pages
    $sql = "
        SELECT r.role_name, 
               COALESCE(array_agg(p.page_name), '{}') AS pages
        FROM roles r
        LEFT JOIN page_roles pr ON r.id = pr.role_id
        LEFT JOIN pages p ON pr.page_id = p.id
        WHERE pr.has_access = true
        GROUP BY r.role_name
    ";

    // Execute the query
    $result = pg_query($con, $sql); // Assuming $con is your PostgreSQL connection

    // Fetch the results and populate the $roleAccessControl array
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            // Split the page string into an array, remove ".php" and add to the role access control
            $pages = explode(",", trim($row['pages'], "{}"));
            $roleAccessControl[$row['role_name']] = array_map(function ($page) {
                return rtrim($page, ".php"); // Remove ".php" from page name
            }, $pages);
        }
    } else {
        return "Error fetching role access data.";
    }

    // // Debugging step: Print the final $roleAccessControl array to check how it looks
    // echo "<pre>"; // For better readability
    // print_r($roleAccessControl); // Print the array
    // echo "</pre>";

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
