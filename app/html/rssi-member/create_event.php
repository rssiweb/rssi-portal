<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/drive.php");
include("../../util/email.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();
?>
<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input data
    $event_name = htmlspecialchars($_POST['event_name'], ENT_QUOTES, 'UTF-8');
    $event_description = htmlspecialchars($_POST['event_description'], ENT_QUOTES, 'UTF-8');
    $event_date = $_POST['event_date']; // Ensure the format matches your TIMESTAMP requirement
    $event_location = htmlspecialchars($_POST['event_location'], ENT_QUOTES, 'UTF-8');
    $created_by = $associatenumber; // Replace this with dynamic user identification logic (e.g., session)

    // Initialize variables for the event image URL and upload status
    $event_image_url = null;

    // Handle file upload for the event image
    if (!empty($_FILES['event_image']['name'])) {
        $event_image = $_FILES['event_image'];

        // Check if file size is less than 300 KB
        if ($event_image['size'] > 300 * 1024) {
            echo "<script>alert('Image size should not exceed 300 KB.');</script>";
            exit;
        }

        // Resize image to 800x400 (client-side)
        // This requires JavaScript and client-side logic (covered below in HTML)

        $filename = $event_name . "_image_" . time(); // Unique filename
        $parent_folder_id = '1UXkDUMIVcr_XxNKimhFQTNuhlu_ek_AE'; // Replace with your Google Drive folder ID
        try {
            $event_image_url = uploadeToDrive($event_image, $parent_folder_id, $filename);
        } catch (Exception $e) {
            echo "<script>alert('Error uploading image: " . $e->getMessage() . "');</script>";
            exit;
        }
    }

    // SQL query for insertion
    $sql = "INSERT INTO events (event_name, event_description, event_date, event_location, event_image_url, created_by)
            VALUES ($1, $2, $3, $4, $5, $6)";

    // Use pg_query_params for parameterized query to prevent SQL injection
    $result = pg_query_params($con, $sql, array($event_name, $event_description, $event_date, $event_location, $event_image_url, $created_by));

    if ($result) {
        // Send email notification if the query is successful
        sendEmail("new_post", [
            "posttitle" => $event_name,
            "author" => $created_by,
            "now" => date("d/m/Y g:i a"),
        ], 'info@rssi.in');
        echo "<script>
    alert('Event successfully created. The post is currently under review and will be published on the home page after approval.');
    
    if (window.history.replaceState) {
        // Update the URL without causing a page reload or resubmission
        window.history.replaceState(null, null, window.location.href);
    }
    
    // Redirect the user to the home page
    window.location.href = 'home.php';
</script>";
    } else {
        echo "<script>alert('Failed to create event.');</script>";
    }
}
?>