<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recorded_by = $associatenumber;
    $distribution_date = pg_escape_string($con, $_POST['distribution_date']);
    $quantity = pg_escape_string($con, $_POST['quantity']);

    if (isset($_POST['bulk_distribution']) && $_POST['bulk_distribution'] == 'on') {
        // Bulk distribution to entire class
        $class = pg_escape_string($con, $_POST['bulk_class']);

        // Get all female students in the class
        $studentsQuery = "SELECT student_id FROM rssimyprofile_student 
                          WHERE gender = 'Female' AND class = '$class' AND filterstatus = 'Active'";
        $studentsResult = pg_query($con, $studentsQuery);

        $successCount = 0;
        $errorCount = 0;

        while ($student = pg_fetch_assoc($studentsResult)) {
            $student_id = $student['student_id'];
            $query = "INSERT INTO sanitary_pad_distribution (
                        student_id, distribution_date, quantity, recorded_by
                      ) VALUES (
                        '$student_id', '$distribution_date', $quantity, '$recorded_by'
                      )";

            $result = pg_query($con, $query);

            if ($result) {
                $successCount++;
            } else {
                $errorCount++;
                error_log("Failed to record pad distribution for student $student_id: " . pg_last_error($con));
            }
        }

        if ($errorCount == 0) {
            $_SESSION['success_message'] = "Pads distributed to $successCount students in class $class!";
        } else {
            $_SESSION['warning_message'] = "Distributed to $successCount students, but failed for $errorCount students.";
        }
    } else {
        // Multiple student distribution
        if (!empty($_POST['student_ids'])) {
            $student_ids = $_POST['student_ids'];
            $successCount = 0;
            $errorCount = 0;

            foreach ($student_ids as $student_id) {
                $student_id = pg_escape_string($con, $student_id);
                $query = "INSERT INTO sanitary_pad_distribution (
                            student_id, distribution_date, quantity, recorded_by
                          ) VALUES (
                            '$student_id', '$distribution_date', $quantity, '$recorded_by'
                          )";

                $result = pg_query($con, $query);

                if ($result) {
                    $successCount++;
                } else {
                    $errorCount++;
                    error_log("Failed to record pad distribution for student $student_id: " . pg_last_error($con));
                }
            }

            if ($errorCount == 0) {
                $_SESSION['success_message'] = "Pads distributed to $successCount selected students!";
            } else {
                $_SESSION['warning_message'] = "Distributed to $successCount students, but failed for $errorCount students.";
            }
        } else {
            $_SESSION['error_message'] = "No students selected for distribution!";
        }
    }

    // Get all current parameters for redirect
    $queryParams = [];
    if (!empty($_POST['current_tab'])) {
        $queryParams['tab'] = $_POST['current_tab'];
    }
    if (!empty($_POST['academic_year'])) {
        $queryParams['academic_year'] = $_POST['academic_year'];
    }
    if (!empty($_POST['current_class'])) {
        $queryParams['class'] = $_POST['current_class'];
    }
    if (!empty($_POST['current_month'])) {
        $queryParams['month'] = $_POST['current_month'];
    }

    // Reconstruct the redirect URL
    $redirectUrl = "health_portal.php";
    if (!empty($queryParams)) {
        $redirectUrl .= '?' . http_build_query($queryParams);
    }
    $redirectUrl .= '#pad-distribution';

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