<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get all the original query parameters
    $referer = parse_url($_SERVER['HTTP_REFERER']);
    $queryParams = [];
    if (isset($referer['query'])) {
        parse_str($referer['query'], $queryParams);
    }

    // Process form data
    $student_id = pg_escape_string($con, $_POST['student_id']);
    $record_date = pg_escape_string($con, $_POST['record_date']);
    // Convert empty strings to NULL for numeric fields
    $height_cm = (isset($_POST['height_cm']) && $_POST['height_cm'] !== '') ? pg_escape_string($con, $_POST['height_cm']) : 'NULL';
    $weight_kg = (isset($_POST['weight_kg']) && $_POST['weight_kg'] !== '') ? pg_escape_string($con, $_POST['weight_kg']) : 'NULL';
    $bmi = (isset($_POST['bmi']) && $_POST['bmi'] !== '') ? pg_escape_string($con, $_POST['bmi']) : 'NULL';
    $blood_pressure = pg_escape_string($con, $_POST['blood_pressure']);
    $vision_left = pg_escape_string($con, $_POST['vision_left']);
    $vision_right = pg_escape_string($con, $_POST['vision_right']);
    $health_notes = pg_escape_string($con, $_POST['health_notes']);
    $recorded_by = $associatenumber;

    $query = "INSERT INTO student_health_records (
                student_id, record_date, height_cm, weight_kg, bmi, 
                blood_pressure, vision_left, vision_right, general_health_notes, recorded_by
              ) VALUES (
                '$student_id', '$record_date', $height_cm, $weight_kg, $bmi, 
                '$blood_pressure', '$vision_left', '$vision_right', '$health_notes', '$recorded_by'
              )";

    $result = pg_query($con, $query);

    if ($result) {
        $_SESSION['success_message'] = "Health record added successfully!";
    } else {
        $_SESSION['error_message'] = "Error saving health record: " . pg_last_error($con);
    }

    // Reconstruct the original URL with all parameters
    $redirectUrl = basename($referer['path']);
    if (!empty($queryParams)) {
        $redirectUrl .= '?' . http_build_query($queryParams);
    }

    header("Location: $redirectUrl");
    exit;
} else {
    // If not POST, redirect with original parameters
    $referer = parse_url($_SERVER['HTTP_REFERER']);
    $queryParams = [];
    if (isset($referer['query'])) {
        parse_str($referer['query'], $queryParams);
    }

    $redirectUrl = basename($referer['path']);
    if (!empty($queryParams)) {
        $redirectUrl .= '?' . http_build_query($queryParams);
    }

    header("Location: $redirectUrl");
    exit;
}
