<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

if ($role !== 'Admin') {
    header("Location: index.php");
    exit;
}

// Initialize variables
$success = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_fee'])) {
    // Validate required fields
    if (empty($_POST['student_ids']) || empty($_POST['category_id']) || empty($_POST['amount']) || empty($_POST['effective_from'])) {
        $message = 'Please fill all required fields.';
    } else {
        // Sanitize inputs
        $studentIds = $_POST['student_ids'];
        $categoryId = pg_escape_string($con, $_POST['category_id']);
        $amount = pg_escape_string($con, $_POST['amount']);
        $effectiveFrom = pg_escape_string($con, $_POST['effective_from']);
        $effectiveUntil = !empty($_POST['effective_until']) ? pg_escape_string($con, $_POST['effective_until']) : null;
        $createdBy = pg_escape_string($con, $_SESSION['aid']);

        // Validate amount
        if (!is_numeric($amount) || $amount <= 0) {
            $message = 'Amount must be a positive number.';
        } else {
            // Validate dates
            $today = date('Y-m-d');
            // if ($effectiveFrom < $today) {
            //     $message = 'Effective From date cannot be in the past.';
            // } elseif ($effectiveUntil && $effectiveUntil < $effectiveFrom) {
            //     $message = 'Effective Until date cannot be before Effective From date.';
            // } else {
                // Begin transaction
                pg_query($con, "BEGIN");

                try {
                    $successCount = 0;
                    $errorCount = 0;
                    $errors = [];

                    foreach ($studentIds as $studentId) {
                        $studentId = pg_escape_string($con, $studentId);

                        // Check if student exists
                        $studentCheck = pg_query($con, "SELECT student_id FROM rssimyprofile_student WHERE student_id = '$studentId'");
                        if (pg_num_rows($studentCheck) == 0) {
                            $errors[] = "Student ID $studentId not found";
                            $errorCount++;
                            continue;
                        }

                        // Check if category exists and is active
                        $categoryCheck = pg_query($con, "SELECT id FROM fee_categories WHERE id = $categoryId AND is_active = TRUE");
                        if (pg_num_rows($categoryCheck) == 0) {
                            $errors[] = "Fee category not found or inactive";
                            $errorCount++;
                            continue;
                        }

                        // End any previous effective period for this student and category
                        $endPreviousQuery = "UPDATE student_specific_fees 
                                           SET effective_until = '$effectiveFrom'::date - INTERVAL '1 day'
                                           WHERE student_id = '$studentId'
                                           AND category_id = $categoryId
                                           AND (effective_until IS NULL OR effective_until >= '$effectiveFrom')";
                        pg_query($con, $endPreviousQuery);

                        // Insert new fee assignment
                        $query = "INSERT INTO student_specific_fees 
                                 (student_id, category_id, amount, effective_from, effective_until, created_by)
                                 VALUES ('$studentId', $categoryId, $amount, '$effectiveFrom', " . 
                                 ($effectiveUntil ? "'$effectiveUntil'" : "NULL") . ", '$createdBy')";

                        if (pg_query($con, $query)) {
                            $successCount++;
                        } else {
                            $errors[] = "Error assigning fee to student $studentId: " . pg_last_error($con);
                            $errorCount++;
                        }
                    }

                    if ($errorCount > 0) {
                        // Partial success - some records were inserted
                        pg_query($con, "COMMIT");
                        $message = "Successfully assigned fees to $successCount students. ";
                        if ($errorCount > 0) {
                            $message .= "Failed for $errorCount students. Errors: " . implode(", ", array_slice($errors, 0, 5));
                            if (count($errors) > 5) {
                                $message .= " (and " . (count($errors) - 5) . " more)";
                            }
                        }
                    } else {
                        // Full success
                        pg_query($con, "COMMIT");
                        $success = true;
                        $message = "Fees assigned successfully to all selected students!";
                    }
                } catch (Exception $e) {
                    pg_query($con, "ROLLBACK");
                    $message = "Transaction failed: " . $e->getMessage();
                }
            // }
        }
    }
}

// Redirect back with appropriate message
$_SESSION['fee_assignment_result'] = [
    'success' => $success,
    'message' => $message
];

header("Location: fee_structure_management.php");
exit;
?>