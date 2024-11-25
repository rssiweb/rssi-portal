<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$exam_type_f = $_GET['exam_type'] ?? '';
$academic_year = $_GET['academic_year'] ?? '';

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
    WHERE exams.academic_year = $1
    AND rssimyprofile_student.student_id IN (
        SELECT DISTINCT exam_marks_data.student_id
        FROM exam_marks_data
        JOIN exams ON exam_marks_data.exam_id = exams.exam_id
        WHERE exams.exam_type = $2
        AND exams.academic_year = $1
    )
    ORDER BY rssimyprofile_student.class, exams.exam_type";

// Prepare for storing exam percentages for each student
$exam_percentages = [];

$percentage_result = pg_query_params($con, $percentage_query, [$academic_year, $exam_type_f]);

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
    <title>Students' Progress Curve</title>
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Students' Progress Curve</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Academic</a></li>
                    <li class="breadcrumb-item active">Students' Progress Curve</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container">
                                <form method="GET" action="" class="d-flex align-items-center mt-5">
                                    <label for="exam_type" class="me-2">Exam Type:</label>
                                    <select name="exam_type" class="form-select me-2" style="width: max-content; display: inline-block;" required>
                                        <?php if ($exam_type_f == null) { ?>
                                            <option value="" disabled selected hidden>Select Exam Name</option>
                                        <?php } else { ?>
                                            <option hidden selected><?php echo $exam_type_f ?></option>
                                        <?php } ?>
                                        <option>First Term</option>
                                        <option>Half Yearly</option>
                                        <option>Annual</option>
                                    </select>

                                    <label for="academic_year" class="me-2">Academic Year:</label>
                                    <select id="academic_year" name="academic_year" class="form-select me-2" style="width: max-content; display: inline-block;" required>
                                        <?php if ($academic_year == null) { ?>
                                            <option value="" disabled selected hidden>Select Year</option>
                                        <?php } else { ?>
                                            <option hidden selected><?php echo $academic_year ?></option>
                                        <?php } ?>
                                    </select>

                                    <button type="submit" class="btn btn-primary btn-sm" style="outline: none;">Apply Filters</button>
                                </form>
                                <!-- <h1 class="mt-5">Students' Progress Curve</h1> -->
                                <div class="table-responsive">
                                    <table id="table-id" class="table table-bordered mt-4">
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
                                            foreach ($exam_percentages as $student_data) { ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($student_data['student_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($student_data['student_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($student_data['category']); ?></td>
                                                    <td><?php echo htmlspecialchars($student_data['class']); ?></td>
                                                    <td><?php echo $student_data['First Term']; ?></td>
                                                    <td><?php echo $student_data['Half Yearly']; ?></td>
                                                    <td><?php echo $student_data['Annual']; ?></td>
                                                    <td>
                                                        <canvas class="progress-graph" width="50" height="10"
                                                            data-percentages='<?php echo json_encode([
                                                                                    str_replace('%', '', $student_data['First Term']),
                                                                                    str_replace('%', '', $student_data['Half Yearly']),
                                                                                    str_replace('%', '', $student_data['Annual']),
                                                                                ]); ?>'></canvas>
                                                    </td>
                                                </tr>
                                            <?php } ?>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

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
    <script>
        <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
            var currentYear = new Date().getFullYear() - 1;
        <?php } else { ?>
            var currentYear = new Date().getFullYear();
        <?php } ?>

        for (var i = 0; i < 5; i++) {
            var next = currentYear + 1;
            var year = currentYear + '-' + next;
            //next.toString().slice(-2) 
            $('#academic_year').append(new Option(year, year));
            currentYear--;
        }
    </script>
    <script>
        $(document).ready(function() {
            $('#table-id').DataTable({
                paging: false,
                "order": [] // Disable initial sorting
            });
        });
    </script>
</body>


</html>