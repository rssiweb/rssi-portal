<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = pg_escape_string($con, $_POST['student_id']);
    $record_date = pg_escape_string($con, $_POST['record_date']);
    $cycle_start_date = pg_escape_string($con, $_POST['cycle_start_date']);
    $cycle_end_date = !empty($_POST['cycle_end_date']) ? pg_escape_string($con, $_POST['cycle_end_date']) : null;
    $symptoms = pg_escape_string($con, $_POST['symptoms']);
    $notes = pg_escape_string($con, $_POST['notes']);
    $recorded_by = $associatenumber;

    $query = "INSERT INTO student_period_records (
                student_id, record_date, cycle_start_date, cycle_end_date, 
                symptoms, notes, recorded_by
              ) VALUES (
                '$student_id', '$record_date', '$cycle_start_date', '$cycle_end_date', 
                '$symptoms', '$notes', '$recorded_by'
              )";

    $result = pg_query($con, $query);

    if ($result) {
        $_SESSION['success_message'] = "Period record added successfully!";
    } else {
        $_SESSION['error_message'] = "Error saving period record: " . pg_last_error($con);
    }

    header("Location: health_portal.php#period-tracking-tab");
    exit;
} else {
    header("Location: health_portal.php");
    exit;
}
?>