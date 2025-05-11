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
                          WHERE gender = 'Female' AND class = '$class'";
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
            }
        }

        if ($errorCount == 0) {
            $_SESSION['success_message'] = "Pads distributed to $successCount students in class $class!";
        } else {
            $_SESSION['warning_message'] = "Distributed to $successCount students, but failed for $errorCount students.";
        }
    } else {
        // Single student distribution
        $student_id = pg_escape_string($con, $_POST['student_id']);

        $query = "INSERT INTO sanitary_pad_distribution (
                    student_id, distribution_date, quantity, recorded_by
                  ) VALUES (
                    '$student_id', '$distribution_date', $quantity, '$recorded_by'
                  )";

        $result = pg_query($con, $query);

        if ($result) {
            $_SESSION['success_message'] = "Pad distribution recorded successfully!";
        } else {
            $_SESSION['error_message'] = "Error saving pad distribution: " . pg_last_error($con);
        }
    }

    header("Location: health_portal.php#pad-distribution-tab");
    exit;
} else {
    header("Location: health_portal.php");
    exit;
}
