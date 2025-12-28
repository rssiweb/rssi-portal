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
$class_filter = $_GET['get_class'] ?? []; // Fetch selected classes (if any)

$students_query = "
    SELECT 
        student.student_id, 
        student.studentname AS student_name, 
        exam_marks_data.class,
        student.category,
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
            WHEN student.doa > exams.exam_date_written THEN 'BA'
            WHEN EXISTS (
                SELECT 1 
                FROM reexamination 
                WHERE reexamination.student_id = exam_marks_data.student_id 
                AND reexamination.date = exams.exam_date_written
            ) THEN 'P'
            WHEN attendance_written.attendance_status IS NULL THEN 'A'
            ELSE COALESCE(attendance_written.attendance_status, 'A')
        END AS written_attendance_status,
        CASE
            WHEN exams.exam_date_viva IS NULL THEN NULL
            WHEN student.doa > exams.exam_date_viva THEN 'BA'
            WHEN EXISTS (
                SELECT 1 
                FROM reexamination 
                WHERE reexamination.student_id = exam_marks_data.student_id 
                AND reexamination.date = exams.exam_date_viva
            ) THEN 'P'
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
    AND exams.academic_year = $2";

// Add the class filter if applicable
if (!empty($class_filter)) {
    $placeholders = array_map(function ($key) {
        return '$' . ($key + 3);
    }, array_keys($class_filter));
    $students_query .= " AND exam_marks_data.class IN (" . implode(', ', $placeholders) . ")";
}
// Order the results
$students_query .= "ORDER BY 
        CASE 
            WHEN exam_marks_data.class = 'Nursery' THEN 1
            WHEN exam_marks_data.class = 'LKG' THEN 2
            WHEN exam_marks_data.class = 'UKG' THEN 3
            ELSE 4
        END,
        CASE 
            WHEN exam_marks_data.class = 'Nursery' THEN 0
            WHEN exam_marks_data.class = 'LKG' THEN 0
            WHEN exam_marks_data.class = 'UKG' THEN 0
            ELSE CAST(exam_marks_data.class AS INTEGER)
        END";

// Prepare query parameters
$params = [$exam_type, $academic_year];
if (!empty($class_filter)) {
    $params = array_merge($params, $class_filter);
}

$students_result = pg_query_params($con, $students_query, $params);

// Prepare the data
$students_data = [];
$subject_sequence = ['Hindi', 'English', 'Mathematics', 'GK', 'Hamara Parivesh', 'Computer', 'Sulekh+Imla', 'Art & Craft', 'Project'];
$subjects_with_exams = [];

// Fetch all data into an array
$rows = [];
while ($row = pg_fetch_assoc($students_result)) {
    $rows[] = $row;
}

// Arrange data
foreach ($rows as $row) {
    $student_id = $row['student_id'];
    $subject = $row['subject'];

    // Initialize student data if not already
    if (!isset($students_data[$student_id])) {
        $students_data[$student_id] = [
            'student_name' => $row['student_name'],
            'class' => $row['class'],
            'category' => $row['category'],
            'subjects' => [],
            'total_full_marks' => 0,
            'total_marks_obtained' => 0,
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
    $students_data[$student_id]['total_full_marks'] += $row['full_marks_written'] + $row['full_marks_viva'];
    $students_data[$student_id]['total_marks_obtained'] += $row['written_marks'] + $row['viva_marks'];

    // Track subjects with exams
    if (!isset($subjects_with_exams[$subject])) {
        $subjects_with_exams[$subject] = [
            'viva' => !is_null($row['exam_date_viva']),
            'written' => !is_null($row['exam_date_written'])
        ];
    }
}
$classlist = [
    "Nursery",
    "LKG",
    "UKG",
    "Pre-school",
    "1",
    "2",
    "3",
    "4",
    "5",
    "6",
    "7",
    "8",
    "9",
    '10',
    "11",
    "12",
    "Vocational training",
]
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Include necessary meta tags and stylesheets -->
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Exam summary report</title>
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>
    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Exam summary report</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Academic</li>
                    <li class="breadcrumb-item"><a href="exam-management.php">Exam Management</a></li>
                    <li class="breadcrumb-item active">Exam summary report</li>
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
                                    <div class="row align-items-center g-2">
                                        <!-- Exam Type -->
                                        <div class="col-md-auto">
                                            <label for="exam_type" class="form-label me-2">Exam Type:</label>
                                            <select name="exam_type" class="form-select" required>
                                                <?php if ($exam_type == null) { ?>
                                                    <option disabled selected hidden>Select Exam Name</option>
                                                <?php } else { ?>
                                                    <option hidden selected><?php echo $exam_type ?></option>
                                                <?php } ?>
                                                <option>First Term</option>
                                                <option>Half Yearly</option>
                                                <option>Annual</option>
                                            </select>
                                        </div>

                                        <!-- Academic Year -->
                                        <div class="col-md-auto">
                                            <label for="academic_year" class="form-label me-2">Academic Year:</label>
                                            <select id="academic_year" name="academic_year" class="form-select" required>
                                                <?php if ($academic_year == null) { ?>
                                                    <option disabled selected hidden>Select Year</option>
                                                <?php } else { ?>
                                                    <option hidden selected><?php echo $academic_year ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <!-- Class -->
                                        <div class="col-md-auto">
                                            <label for="get_class" class="form-label me-2">Class:</label>
                                            <select name="get_class[]" id="get_class" class="form-select" multiple>
                                                <?php if ($class_filter == null) { ?>
                                                    <option disabled selected hidden>Select Class</option>
                                                    <?php foreach ($classlist as $cls) { ?>
                                                        <option><?php echo $cls ?></option>
                                                    <?php } ?>
                                                    <?php } else {
                                                    foreach ($classlist as $cls) { ?>
                                                        <option <?php if (in_array($cls, $class_filter)) {
                                                                    echo "selected";
                                                                } ?>><?php echo $cls ?></option>
                                                <?php }
                                                } ?>
                                            </select>
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="col-md-auto">
                                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                                        </div>
                                    </div>
                                </form>

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
                            </div>
                            <br>

                            <div class="container">
                                <!-- <h4>Exam Summary Report</h4> -->
                                <div class="table-responsive">
                                    <table id="table-id" class="table">
                                        <thead>
                                            <tr>
                                                <th>Student ID</th>
                                                <th>Student Name</th>
                                                <th>Class</th>
                                                <th>Category</th>
                                                <?php foreach ($subject_sequence as $subject) {
                                                    if (isset($subjects_with_exams[$subject])) {
                                                        if ($subjects_with_exams[$subject]['viva']) {
                                                            echo "<th>" . substr($subject, 0, 4) . " _V</th>";
                                                        }
                                                        if ($subjects_with_exams[$subject]['written']) {
                                                            echo "<th>" . substr($subject, 0, 4) . " _W</th>";
                                                        }
                                                    }
                                                } ?>
                                                <th>Total Full Marks</th>
                                                <th>Total Marks Obtained</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($students_data as $student_id => $student) : ?>
                                                <tr>
                                                    <td><?= $student_id ?></td>
                                                    <td><?= $student['student_name'] ?></td>
                                                    <td><?= $student['class'] ?></td>
                                                    <td><?= $student['category'] ?></td>
                                                    <?php foreach ($subject_sequence as $subject) {
                                                        if (isset($subjects_with_exams[$subject])) {

                                                            if ($subjects_with_exams[$subject]['viva']) {
                                                                echo "<td>" . ($student['subjects'][$subject]['viva_marks'] ?? $student['subjects'][$subject]['viva_attendance_status'] ?? '') . "</td>";
                                                            }
                                                            if ($subjects_with_exams[$subject]['written']) {
                                                                echo "<td>" . ($student['subjects'][$subject]['written_marks'] ?? $student['subjects'][$subject]['written_attendance_status'] ?? '') . "</td>";
                                                            }
                                                        }
                                                    } ?>
                                                    <td><?= $student['total_full_marks'] ?></td>
                                                    <td><?= $student['total_marks_obtained'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main><!-- End #main -->

    <!-- Scripts -->
    <script src="https://cdn.datatables.net/2.0.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#table-id').DataTable({
                paging: false,
                "order": [] // Disable initial sorting
            });
        });
    </script>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  
</body>

</html>