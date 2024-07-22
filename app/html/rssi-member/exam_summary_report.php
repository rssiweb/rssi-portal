<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

// Handle form submission
$exam_type = $_GET['exam_type'] ?? '';
$academic_year = $_GET['academic_year'] ?? '';

$students_query = "
    SELECT 
        student.student_id, 
        student.studentname AS student_name, 
        student.class,
        exam_marks_data.exam_id, 
        ROUND(exam_marks_data.viva_marks) AS viva_marks, 
        ROUND(exam_marks_data.written_marks) AS written_marks, 
        exams.subject, 
        exams.full_marks_written, 
        exams.full_marks_viva, 
        exams.exam_date_written, 
        exams.exam_date_viva, 
        student.doa, 
        student.dateofbirth, 
        student.photourl,
        CASE
            WHEN exams.exam_date_written IS NULL THEN NULL
            WHEN TO_DATE(student.doa, 'YYYY-MM-DD HH24:MI:SS') > exams.exam_date_written THEN 'NA'
            WHEN attendance_written.attendance_status IS NULL THEN 'A'
            ELSE COALESCE(attendance_written.attendance_status, 'A')
        END AS written_attendance_status,
        CASE
            WHEN exams.exam_date_viva IS NULL THEN NULL
            WHEN TO_DATE(student.doa, 'YYYY-MM-DD HH24:MI:SS') > exams.exam_date_viva THEN 'NA'
            WHEN attendance_viva.attendance_status IS NULL THEN 'A'
            ELSE COALESCE(attendance_viva.attendance_status, 'A')
        END AS viva_attendance_status,
        exams.full_marks_written AS full_marks_written,
        exams.full_marks_viva AS full_marks_viva
    FROM exam_marks_data
    JOIN exams ON exam_marks_data.exam_id = exams.exam_id
    LEFT JOIN (
        SELECT user_id, date, 'P' AS attendance_status
        FROM attendance
        GROUP BY user_id, date
    ) AS attendance_written
    ON exam_marks_data.student_id = attendance_written.user_id 
    AND exams.exam_date_written = attendance_written.date
    LEFT JOIN (
        SELECT user_id, date, 'P' AS attendance_status
        FROM attendance
        GROUP BY user_id, date
    ) AS attendance_viva
    ON exam_marks_data.student_id = attendance_viva.user_id 
    AND exams.exam_date_viva = attendance_viva.date
    JOIN rssimyprofile_student student ON exam_marks_data.student_id = student.student_id
    WHERE exams.exam_type = $1 
    AND exams.academic_year = $2
    ORDER BY 
        CASE 
            WHEN student.class = 'Nursery' THEN 1
            WHEN student.class = 'LKG' THEN 2
            WHEN student.class = 'UKG' THEN 3
            ELSE 4
        END,
        CASE 
            WHEN student.class = 'Nursery' THEN 0
            WHEN student.class = 'LKG' THEN 0
            WHEN student.class = 'UKG' THEN 0
            ELSE CAST(student.class AS INTEGER)
        END";

$students_result = pg_query_params($con, $students_query, [$exam_type, $academic_year]);

// Prepare the data
$students_data = [];
$subject_sequence = ['Hindi', 'English', 'Mathematics', 'GK', 'Hamara Parivesh', 'Computer', 'Sulekh+Imla', 'Art & Craft', 'Project'];

// Arrange data
while ($row = pg_fetch_assoc($students_result)) {
    $student_id = $row['student_id'];
    $subject = $row['subject'];

    // Initialize student data if not already
    if (!isset($students_data[$student_id])) {
        $students_data[$student_id] = [
            'student_name' => $row['student_name'],
            'class' => $row['class'],
            'subjects' => [],
            'total_full_marks_written' => 0,
            'total_full_marks_viva' => 0,
            'total_marks_obtained_written' => 0,
            'total_marks_obtained_viva' => 0,
        ];
    }

    // Store marks based on subject
    $students_data[$student_id]['subjects'][$subject] = [
        'written_marks' => $row['written_marks'],
        'viva_marks' => $row['viva_marks'],
        'written_attendance_status' => $row['written_attendance_status'],
        'viva_attendance_status' => $row['viva_attendance_status'],
        'full_marks_written' => $row['full_marks_written'],
        'full_marks_viva' => $row['full_marks_viva']
    ];

    // Update totals
    $students_data[$student_id]['total_full_marks_written'] += $row['full_marks_written'];
    $students_data[$student_id]['total_full_marks_viva'] += $row['full_marks_viva'];
    $students_data[$student_id]['total_marks_obtained_written'] += $row['written_marks'];
    $students_data[$student_id]['total_marks_obtained_viva'] += $row['viva_marks'];
}
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Include necessary meta tags and stylesheets -->
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Exam Allotment</title>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #f4f4f4;
            text-align: left;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.6/css/dataTables.bootstrap5.min.css">
    <script type="text/javascript" src="https://cdn.datatables.net/2.0.6/js/dataTables.min.js"></script>
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>
    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Exam Allotment</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Academic</li>
                    <li class="breadcrumb-item active">Exam Allotment</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <div class="container">
                                <form method="GET" action="">
                                    <label for="exam_type">Exam Type:</label>
                                    <select id="exam_type" name="exam_type">
                                        <option value="First Term" <?= $exam_type === 'First Term' ? 'selected' : '' ?>>First Term</option>
                                        <option value="Second Term" <?= $exam_type === 'Second Term' ? 'selected' : '' ?>>Second Term</option>
                                        <option value="Annual" <?= $exam_type === 'Annual' ? 'selected' : '' ?>>Annual</option>
                                    </select>

                                    <label for="academic_year">Academic Year:</label>
                                    <input type="text" id="academic_year" name="academic_year" value="<?= htmlspecialchars($academic_year) ?>" placeholder="e.g., 2024-2025">

                                    <input type="submit" value="Fetch Data">
                                </form>

                                <?php if ($exam_type && $academic_year) : ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="student-data-table">
                                            <thead>
                                                <tr>
                                                    <th>Student ID</th>
                                                    <th>Student Name</th>
                                                    <th>Class</th>
                                                    <?php foreach ($subject_sequence as $subject) : ?>
                                                        <th><?= htmlspecialchars(substr($subject, 0, 4) ?? '') ?> _V</th>
                                                        <th><?= htmlspecialchars(substr($subject, 0, 4) ?? '') ?> _W</th>
                                                    <?php endforeach; ?>
                                                    <th>Total Full Marks</th>
                                                    <th>Total Marks Obtained</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students_data as $student_id => $student_info) : ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($student_id ?? '') ?></td>
                                                        <td><?= htmlspecialchars($student_info['student_name'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($student_info['class'] ?? '') ?></td>
                                                        <?php foreach ($subject_sequence as $subject) : ?>
                                                            <?php if (isset($student_info['subjects'][$subject])) : ?>
                                                                <?php $marks = $student_info['subjects'][$subject]; ?>
                                                                <td><?= htmlspecialchars($marks['viva_marks'] ?? $marks['viva_attendance_status'] ?? '') ?></td>
                                                                <td><?= htmlspecialchars($marks['written_marks'] ?? $marks['written_attendance_status'] ?? '') ?></td>
                                                            <?php else : ?>
                                                                <td></td>
                                                                <td></td>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                        <td><?= htmlspecialchars($student_info['total_full_marks_written'] + $student_info['total_full_marks_viva']) ?></td>
                                                        <td><?= htmlspecialchars($student_info['total_marks_obtained_written'] + $student_info['total_marks_obtained_viva']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main><!-- End #main -->

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-Qz4v4VXpE57jznP2TknxkX6KbX1n0E4kI1MfhV9Y8kpAp8GHFfSB2I0qQ13cGbE3" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/2.0.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#student-data-table').DataTable();
        });
    </script>
</body>

</html>
