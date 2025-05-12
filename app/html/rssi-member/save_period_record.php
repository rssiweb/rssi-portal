<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process form data
    $student_id = pg_escape_string($con, $_POST['student_id']);
    $record_date = pg_escape_string($con, $_POST['record_date']);
    $cycle_start_date = pg_escape_string($con, $_POST['cycle_start_date']);
    $cycle_end_date = (isset($_POST['cycle_end_date']) && $_POST['cycle_end_date'] !== '') 
    ? "'" . pg_escape_string($con, $_POST['cycle_end_date']) . "'" 
    : 'NULL';
    $symptoms = pg_escape_string($con, $_POST['symptoms']);
    $notes = pg_escape_string($con, $_POST['notes']);
    $recorded_by = $associatenumber;

    $query = "INSERT INTO student_period_records (
                student_id, record_date, cycle_start_date, cycle_end_date, 
                symptoms, notes, recorded_by
              ) VALUES (
                '$student_id', '$record_date', '$cycle_start_date', $cycle_end_date, 
                '$symptoms', '$notes', '$recorded_by'
              )";

    $result = pg_query($con, $query);

    if ($result) {
        $_SESSION['success_message'] = "Period record added successfully!";
    } else {
        $_SESSION['error_message'] = "Error saving period record: " . pg_last_error($con);
    }

    // Get all current GET parameters from the referring URL
    $referer = parse_url($_SERVER['HTTP_REFERER']);
    $queryParams = [];
    if (isset($referer['query'])) {
        parse_str($referer['query'], $queryParams);
    }

    // Ensure we're going back to the period-tracking tab
    $queryParams['tab'] = 'period-tracking';

    // Reconstruct the redirect URL
    $redirectUrl = "health_portal.php";
    if (!empty($queryParams)) {
        $redirectUrl .= '?' . http_build_query($queryParams);
    }

    // Add hash fragment for the tab
    $redirectUrl .= '#period-tracking';

    header("Location: $redirectUrl");
    exit;
} else {
    // For non-POST requests, redirect with original parameters
    $referer = parse_url($_SERVER['HTTP_REFERER']);
    $queryParams = [];
    if (isset($referer['query'])) {
        parse_str($referer['query'], $queryParams);
    }

    // Reconstruct the redirect URL
    $redirectUrl = "health_portal.php";
    if (!empty($queryParams)) {
        $redirectUrl .= '?' . http_build_query($queryParams);
    }

    header("Location: $redirectUrl");
    exit;
}
?>