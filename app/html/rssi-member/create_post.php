<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/drive.php");
include("../../util/email.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---- 1. Event ID (from Select2) — this is the ONLY trusted link to location ----
    $event_id = !empty($_POST['event_name']) ? (int)$_POST['event_name'] : null;

    if (!$event_id) {
        echo "<script>alert('Please select a valid event.');</script>";
        exit;
    }

    // ---- 2. Look up location from internal_events SERVER-SIDE (tamper-proof) ----
    $locRes = pg_query_params(
        $con,
        "SELECT location FROM internal_events WHERE id = \$1",
        [$event_id]
    );

    if (!$locRes || pg_num_rows($locRes) === 0) {
        echo "<script>alert('Invalid event selected.');</script>";
        exit;
    }

    $event_location = pg_fetch_result($locRes, 0, 'location'); // ID or null

    // ---- 3. Sanitize the rest of the inputs ----
    $event_name        = htmlspecialchars($_POST['event_name'], ENT_QUOTES, 'UTF-8');
    $event_description = $_POST['event_description'] ?? '';

    $config    = HTMLPurifier_Config::createDefault();
    $purifier  = new HTMLPurifier($config);
    $event_description = $purifier->purify($event_description);

    $event_date  = $_POST['event_date'];
    $created_by  = $associatenumber;

    // ---- 4. Optional image upload ----
    $event_image_url = null;

    if (!empty($_FILES['event_image']['name'])) {
        $event_image = $_FILES['event_image'];

        if ($event_image['size'] > 300 * 1024) {
            echo "<script>alert('Image size should not exceed 300 KB.');</script>";
            exit;
        }

        $filename          = $event_name . "_image_" . time();
        $parent_folder_id  = '1UXkDUMIVcr_XxNKimhFQTNuhlu_ek_AE';

        try {
            $event_image_url = uploadeToDrive($event_image, $parent_folder_id, $filename);
        } catch (Exception $e) {
            echo "<script>alert('Error uploading image: " . $e->getMessage() . "');</script>";
            exit;
        }
    }

    // ---- 5. Insert ----
    $sql = "INSERT INTO events 
                (event_name, event_description, event_date, event_location, event_image_url, created_by)
            VALUES ($1, $2, $3, $4, $5, $6)";

    $result = pg_query_params($con, $sql, array(
        $event_name,
        $event_description,
        $event_date,
        $event_location,
        $event_image_url,
        $created_by
    ));

    if ($result) {
        sendEmail("new_post", [
            "posttitle" => $event_name,
            "author"    => $created_by,
            "now"       => date("d/m/Y g:i a"),
        ], 'info@rssi.in');

        echo "<script>
            alert('Post successfully created. The post is currently under review and will be published on the home page after approval.');
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            window.location.href = 'home.php';
        </script>";
    } else {
        echo "<script>alert('Failed to create event.');</script>";
    }
}
