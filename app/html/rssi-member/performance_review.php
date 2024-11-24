<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$academic_year = '2024-2025';

// SQL query to fetch exam results for all students in the given academic year
$percentage_query = "
    SELECT 
        rssimyprofile_student.student_id, 
        rssimyprofile_student.studentname,
        rssimyprofile_student.filterstatus,
        rssimyprofile_student.category,
        rssimyprofile_student.class,
        exams.exam_type,
        ROUND(exam_marks_data.written_marks) AS written_marks,
        ROUND(exam_marks_data.viva_marks) AS viva_marks,
        exams.full_marks_written,
        exams.full_marks_viva
    FROM exam_marks_data
    JOIN exams ON exam_marks_data.exam_id = exams.exam_id
    JOIN rssimyprofile_student ON exam_marks_data.student_id = rssimyprofile_student.student_id
    WHERE exams.academic_year = $1 AND rssimyprofile_student.filterstatus='Active'
    ORDER BY rssimyprofile_student.class, exams.exam_type";

// Prepare for storing exam percentages for each student
$exam_percentages = [];

$percentage_result = pg_query_params($con, $percentage_query, [$academic_year]);

if ($percentage_result) {
    // Loop through the result and organize data by student
    while ($row = pg_fetch_assoc($percentage_result)) {
        $student_id = $row['student_id'];
        $exam_type = $row['exam_type'];

        // Initialize the student's percentage data if not already present
        if (!isset($exam_percentages[$student_id])) {
            $exam_percentages[$student_id] = [
                'student_id' => $row['student_id'],
                'student_name' => $row['studentname'],
                'category' => $row['category'],
                'class' => $row['class'],
                'First Term' => ['obtained_marks' => 0, 'total_marks' => 0],
                'Half Yearly' => ['obtained_marks' => 0, 'total_marks' => 0],
                'Annual' => ['obtained_marks' => 0, 'total_marks' => 0]
            ];
        }

        // Sum full marks and obtained marks for each exam type
        $full_marks_for_exam = $row['full_marks_written'] + $row['full_marks_viva'];
        $obtained_marks_for_exam = $row['written_marks'] + $row['viva_marks'];

        // Add the marks to the corresponding exam type
        if ($full_marks_for_exam > 0) {
            $exam_percentages[$student_id][$exam_type]['obtained_marks'] += $obtained_marks_for_exam;
            $exam_percentages[$student_id][$exam_type]['total_marks'] += $full_marks_for_exam;
        }
    }

    // Calculate the percentage for each student and each exam type
    foreach ($exam_percentages as $student_id => &$data) {
        foreach (['First Term', 'Half Yearly', 'Annual'] as $exam_type) {
            $total_marks = $data[$exam_type]['total_marks'];
            $obtained_marks = $data[$exam_type]['obtained_marks'];

            if ($total_marks > 0) {
                $percentage = ($obtained_marks / $total_marks) * 100;
                $data[$exam_type] = number_format($percentage, 2) . '%'; // Store the percentage as a string
            } else {
                $data[$exam_type] = 'N/A'; // No data available
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Exam Percentages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <h1 class="mt-5">Student Exam Percentages</h1>
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Category</th>
                    <th>Class</th>
                    <th>First Term</th>
                    <th>Half Yearly</th>
                    <th>Annual</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Loop through the data and display each student's result
                foreach ($exam_percentages as $student_data) {
                    // Output the percentages for each exam type
                    echo "<tr>
                        <td>" . htmlspecialchars($student_data['student_id']) . "</td>
                        <td>" . htmlspecialchars($student_data['student_name']) . "</td>
                        <td>" . $student_data['category'] . "</td>
                        <td>" . $student_data['class'] . "</td>
                        <td>" . $student_data['First Term'] . "</td>
                        <td>" . $student_data['Half Yearly'] . "</td>
                        <td>" . $student_data['Annual'] . "</td>
                        <td>
                            <canvas class='progress-graph' width='50' height='10' 
                                data-percentages='" . json_encode([
                                    str_replace('%', '', $student_data['First Term']),
                                    str_replace('%', '', $student_data['Half Yearly']),
                                    str_replace('%', '', $student_data['Annual'])
                                ]) . "'></canvas>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const graphs = document.querySelectorAll('.progress-graph');
            graphs.forEach((canvas) => {
                const ctx = canvas.getContext('2d');
                let percentages = JSON.parse(canvas.dataset.percentages);

                // Filter out "N/A" and empty values
                percentages = percentages.filter(p => p !== 'N/A' && p !== null && p !== '');

                // If there are no valid percentages, skip this canvas
                if (percentages.length === 0) return;

                const width = canvas.width;
                const height = canvas.height;

                // Set 100 as the max value for the Y-axis
                const maxPercentage = 100;

                // Normalize values to fit within canvas height
                const normalizedPoints = percentages.map(p => height - (p / maxPercentage) * height);

                // Clear the canvas before drawing
                ctx.clearRect(0, 0, width, height);

                // Draw the line connecting points
                ctx.beginPath();
                ctx.moveTo(0, normalizedPoints[0]); // Start at the first point

                // Draw the line to each point
                percentages.forEach((percentage, index) => {
                    const x = (index / (percentages.length - 1)) * width; // Spread the points along the X-axis
                    const y = normalizedPoints[index]; // Use the normalized Y position
                    ctx.lineTo(x, y);
                });

                // Style the line and draw it
                ctx.strokeStyle = '#007bff'; // Blue line
                ctx.lineWidth = 2; // Line thickness
                ctx.stroke();
            });
        });
    </script>
</body>


</html>