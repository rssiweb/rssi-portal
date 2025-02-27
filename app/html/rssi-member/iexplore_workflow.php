<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");


if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
}

validation();
// File: approve_external_scores.php

// Database connection (assuming $con is already defined)
// Include your database connection script if necessary
// include 'db_connection.php';

// Handle approval action
if (isset($_POST['approve'])) {
    $externalScoreId = $_POST['external_score_id'];

    // Fetch the external score record
    $query = "SELECT * FROM external_exam_scores WHERE id = $1";
    $stmt = pg_prepare($con, "fetch_external_score", $query);
    $result = pg_execute($con, "fetch_external_score", [$externalScoreId]);

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);

        // Prepare data for insertion into wbt_status
        $associateNumber = $row['associate_number'];
        $courseId = $row['course_id'];
        $timestamp = $row['completion_date']; // Use completion_date as timestamp
        $fScore = $row['score'] / 100; // Divide score by 100
        $email = null; // Set email to null

        // Insert into wbt_status
        $insertQuery = "
            INSERT INTO wbt_status (
                associatenumber, 
                courseid, 
                timestamp, 
                f_score, 
                email
            ) VALUES ($1, $2, $3, $4, $5)
        ";
        $insertStmt = pg_prepare($con, "insert_wbt_status", $insertQuery);
        $insertResult = pg_execute($con, "insert_wbt_status", [
            $associateNumber,
            $courseId,
            $timestamp,
            $fScore,
            $email
        ]);

        if ($insertResult) {
            // Delete the approved record from external_exam_scores
            $deleteQuery = "DELETE FROM external_exam_scores WHERE id = $1";
            $deleteStmt = pg_prepare($con, "delete_external_score", $deleteQuery);
            $deleteResult = pg_execute($con, "delete_external_score", [$externalScoreId]);

            if ($deleteResult) {
                echo "<script>alert('Score approved and moved to wbt_status successfully!');</script>";
            } else {
                echo "<script>alert('Error: Failed to delete approved record.');</script>";
            }
        } else {
            echo "<script>alert('Error: Failed to insert into wbt_status.');</script>";
        }
    } else {
        echo "<script>alert('Error: External score record not found.');</script>";
    }
}

// Fetch all pending external score requests
$query = "SELECT * FROM external_exam_scores";
$result = pg_query($con, $query);

if (!$result) {
    die("Error fetching external score requests.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve External Scores</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Approve External Scores</h1>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Course ID</th>
                    <th>Associate Number</th>
                    <th>Completion Date</th>
                    <th>Score</th>
                    <th>Supporting File</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = pg_fetch_assoc($result)) : ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']); ?></td>
                        <td><?= htmlspecialchars($row['course_id']); ?></td>
                        <td><?= htmlspecialchars($row['associate_number']); ?></td>
                        <td><?= htmlspecialchars($row['completion_date']); ?></td>
                        <td><?= htmlspecialchars($row['score']); ?>%</td>
                        <td>
                            <?php if ($row['supporting_file']) : ?>
                                <a href="<?= htmlspecialchars($row['supporting_file']); ?>" target="_blank">View File</a>
                            <?php else : ?>
                                No file
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" action="#" style="display:inline;">
                                <input type="hidden" name="external_score_id" value="<?= $row['id']; ?>">
                                <button type="submit" name="approve" class="btn btn-success btn-sm">Approve</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS (Optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>