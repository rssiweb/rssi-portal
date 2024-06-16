<?php

require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

// Set initial values for form fields
$student_id = $_GET['student_id'] ?? '';
$exam_type = $_GET['exam_type'] ?? '';
$academic_year = $_GET['academic_year'] ?? '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['student_id']) && isset($_GET['exam_type']) && isset($_GET['academic_year'])) {
    // Get the filter data from the form
    $student_id = $_GET['student_id'];
    $exam_type = $_GET['exam_type'];
    $academic_year = $_GET['academic_year'];

    // Fetch student details
    $student_query = "SELECT * FROM rssimyprofile_student WHERE student_id = $1";
    $student_result = pg_query_params($con, $student_query, [$student_id]);
    
    // Check if student exists
    if ($student_result) {
        $student_details = pg_fetch_assoc($student_result);

        // Fetch exam details and marks
        $marks_query = "SELECT exam_marks_data.exam_id, exam_marks_data.viva_marks, exam_marks_data.written_marks, 
                        exams.subject, exams.full_marks_written, exams.full_marks_viva
                        FROM exam_marks_data
                        JOIN exams ON exam_marks_data.exam_id = exams.exam_id
                        WHERE exam_marks_data.student_id = $1 AND exams.exam_type = $2 AND exams.academic_year = $3";
        $marks_result = pg_query_params($con, $marks_query, [$student_id, $exam_type, $academic_year]);

        if ($marks_result) {
            // Generate report card
            echo "<h1>Report Card</h1>";
            echo "<h2>Student Details</h2>";
            echo "Name: " . $student_details['studentname'] . "<br>";
            echo "ID: " . $student_details['student_id'] . "<br>";
            echo "Class: " . $student_details['class'] . "<br>";
            echo "Category: " . $student_details['category'] . "<br>";

            echo "<h2>Exam Results</h2>";
            echo "<table border='1'>
                    <tr>
                        <th>Subject</th>
                        <th>Full Marks (Written)</th>
                        <th>Full Marks (Viva)</th>
                        <th>Written Marks</th>
                        <th>Viva Marks</th>
                    </tr>";

            while ($row = pg_fetch_assoc($marks_result)) {
                echo "<tr>
                        <td>" . $row['subject'] . "</td>
                        <td>" . $row['full_marks_written'] . "</td>
                        <td>" . $row['full_marks_viva'] . "</td>
                        <td>" . $row['written_marks'] . "</td>
                        <td>" . $row['viva_marks'] . "</td>
                    </tr>";
            }

            echo "</table>";
        } else {
            echo "Error fetching exam details and marks.";
        }
    } else {
        echo "Student not found.";
    }
}

// Close the database connection
pg_close($con);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Report Card</title>
</head>
<body>
    <h1>Generate Report Card</h1>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET">
        <label for="student_id">Student ID:</label>
        <input type="text" id="student_id" name="student_id" value="<?php echo $student_id; ?>" required><br><br>

        <label for="exam_type">Exam Type:</label>
        <input type="text" id="exam_type" name="exam_type" value="<?php echo $exam_type; ?>" required><br><br>

        <label for="academic_year">Academic Year:</label>
        <input type="text" id="academic_year" name="academic_year" value="<?php echo $academic_year; ?>" required><br><br>

        <input type="submit" value="Generate Report">
    </form>
</body>
</html>
