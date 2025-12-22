<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Function to check if user is admin
function isAdmin() {
    global $role;
    return $role === 'admin' || $role === 'superadmin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'] ?? 0;
    
    if ($event_id) {
        // Fetch event to check permission
        $sql = "SELECT created_by FROM internal_events WHERE id = $1";
        $result = pg_query_params($con, $sql, [$event_id]);
        $event = pg_fetch_assoc($result);
        
        if ($event) {
            // Check permission (only creator or admin can delete)
            if ($event['created_by'] == $associatenumber || isAdmin()) {
                // Delete the event
                $delete_sql = "DELETE FROM internal_events WHERE id = $1";
                $delete_result = pg_query_params($con, $delete_sql, [$event_id]);
                
                if ($delete_result) {
                    $_SESSION['message'] = 'Event deleted successfully!';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Error deleting event: ' . pg_last_error($con);
                    $_SESSION['message_type'] = 'danger';
                }
            } else {
                $_SESSION['message'] = 'You don\'t have permission to delete this event!';
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $_SESSION['message'] = 'Event not found!';
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = 'Invalid event ID!';
        $_SESSION['message_type'] = 'danger';
    }
}

header("Location: create_event.php");
exit;
?>