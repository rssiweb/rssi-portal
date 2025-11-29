<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");

// Set initial values for form fields
$student_id = $_GET['student_id'] ?? '';
$exam_type = $_GET['exam_type'] ?? '';
$academic_year = $_GET['academic_year'] ?? '';
$print = (isset($_GET["print"]) ? $_GET["print"] : "False") == "True";

// Initialize flags
$student_exists = false;
$no_records_found = false;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['student_id']) && isset($_GET['exam_type']) && isset($_GET['academic_year'])) {

    // Extract the start year from the academic year
    list($start_year, $end_year) = explode('-', $academic_year);
    $start_year = intval($start_year);

    // Determine the start and end dates based on the exam type and academic year
    $today_date = date('Y-m-d');
    if ($exam_type == 'First Term') {
        $start_date = "$start_year-04-01";
        $end_date = "$start_year-07-31";
    } elseif ($exam_type == 'Half Yearly') {
        $start_date = "$start_year-08-01";
        $end_date = "$start_year-11-30";
    } else {  // annual
        $start_date = "$start_year-12-01";
        $end_date = ($start_year + 1) . "-03-31";
    }
    $end_date = min($end_date, $today_date);

    // Get the filter data from the form
    $student_id = $_GET['student_id'];
    $exam_type = $_GET['exam_type'];
    $academic_year = $_GET['academic_year'];

    // Fetch student details
    $student_query = "SELECT * FROM rssimyprofile_student WHERE student_id = $1";
    $student_result = pg_query_params($con, $student_query, [$student_id]);

    // Check if student exists
    if ($student_result && pg_num_rows($student_result) > 0) {
        $student_details = pg_fetch_assoc($student_result);
        $student_exists = true;

        // Fetch exam details and marks
        $marks_query = "
        SELECT 
            exam_marks_data.exam_id, 
            ROUND(exam_marks_data.viva_marks) AS viva_marks, 
            ROUND(exam_marks_data.written_marks) AS written_marks, 
            exams.subject, 
            exams.full_marks_written, 
            exams.full_marks_viva, 
            exams.exam_date_written, 
            exams.exam_date_viva, 
            student.doa,
            CASE
                WHEN attendance_written.attendance_status IS NULL 
                    AND student.doa <= exams.exam_date_written 
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM reexamination 
                        WHERE reexamination.student_id = exam_marks_data.student_id 
                        AND reexamination.date = exams.exam_date_written
                    ) THEN 'A'
                ELSE attendance_written.attendance_status
            END AS written_attendance_status,
            CASE
                WHEN attendance_viva.attendance_status IS NULL 
                    AND student.doa <= exams.exam_date_viva 
                    AND NOT EXISTS (
                        SELECT 1 
                        FROM reexamination 
                        WHERE reexamination.student_id = exam_marks_data.student_id 
                        AND reexamination.date = exams.exam_date_viva
                    ) THEN 'A'
                ELSE attendance_viva.attendance_status
            END AS viva_attendance_status
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
        WHERE exam_marks_data.student_id = $1 
        AND exams.exam_type = $2 
        AND exams.academic_year = $3";
        $marks_result = pg_query_params($con, $marks_query, [$student_id, $exam_type, $academic_year]);

        // Process and arrange marks data according to subject sequence
        if ($marks_result && pg_num_rows($marks_result) > 0) {
            $marks_data = [];
            while ($row = pg_fetch_assoc($marks_result)) {
                $marks_data[] = $row;
            }

            // Define the subject sequence
            $subject_sequence = ['Hindi', 'English', 'Mathematics', 'GK', 'Hamara Parivesh', 'Computer', 'Sulekh+Imla', 'Art & Craft'];
            $ordered_marks_data = [];
            $total_full_marks = 0;
            $total_obtained_marks = 0;

            // Arrange marks data according to subject sequence
            foreach ($subject_sequence as $subject) {
                foreach ($marks_data as $key => $data) {
                    if ($data['subject'] === $subject) {
                        $ordered_marks_data[] = $data;
                        $total_full_marks += $data['full_marks_written'] + $data['full_marks_viva'];
                        $total_obtained_marks += $data['written_marks'] + $data['viva_marks'];
                        unset($marks_data[$key]);
                    }
                }
            }

            // Add any remaining subjects that were not in the defined sequence
            foreach ($marks_data as $data) {
                $ordered_marks_data[] = $data;
                $total_full_marks += $data['full_marks_written'] + $data['full_marks_viva'];
                $total_obtained_marks += $data['written_marks'] + $data['viva_marks'];
            }

            // Calculate the attendance details for the specified period
            $attendance_query = "
                WITH date_range AS (
                    SELECT DISTINCT a.date AS attendance_date
                    FROM attendance a
                    WHERE a.date BETWEEN '$start_date' AND '$end_date'
                ),
                holidays AS (
                    SELECT holiday_date FROM holidays 
                    WHERE holiday_date BETWEEN '$start_date' AND '$end_date'
                ),
                student_exceptions AS (
                    SELECT 
                        m.student_id,
                        e.exception_date AS attendance_date
                    FROM 
                        student_class_days_exceptions e
                    JOIN 
                        student_exception_mapping m ON e.exception_id = m.exception_id
                    WHERE 
                        e.exception_date BETWEEN '$start_date' AND '$end_date'
                        AND m.student_id = $1
                ),
                attendance_data AS (
                    SELECT
                        s.student_id,
                        dr.attendance_date,
                        COALESCE(
                            CASE
                                WHEN a.user_id IS NOT NULL THEN 'P' -- Present if attendance record exists
                                WHEN h.holiday_date IS NOT NULL THEN NULL -- NULL for holidays (not counted)
                                WHEN ex.attendance_date IS NOT NULL THEN NULL -- NULL for exceptions (not counted)
                                WHEN a.user_id IS NULL
                                    AND EXISTS (SELECT 1 FROM attendance att WHERE att.date = dr.attendance_date)
                                    AND EXISTS (
                                        SELECT 1 FROM student_class_days cw
                                        WHERE cw.category = s.category
                                        AND cw.effective_from <= dr.attendance_date
                                        AND (cw.effective_to IS NULL OR cw.effective_to >= dr.attendance_date)
                                        AND POSITION(TO_CHAR(dr.attendance_date, 'Dy') IN cw.class_days) > 0
                                    )
                                    AND s.doa <= dr.attendance_date
                                    THEN 'A' -- Absent only if it's a class day and not holiday/exception
                                ELSE NULL -- NULL for non-class days
                            END
                        ) AS attendance_status
                    FROM date_range dr
                    CROSS JOIN rssimyprofile_student s
                    LEFT JOIN (
                        SELECT DISTINCT user_id, date
                        FROM attendance
                    ) a ON s.student_id = a.user_id AND a.date = dr.attendance_date
                    LEFT JOIN holidays h ON dr.attendance_date = h.holiday_date
                    LEFT JOIN student_exceptions ex ON dr.attendance_date = ex.attendance_date AND s.student_id = ex.student_id
                    WHERE s.student_id = $1
                    AND dr.attendance_date >= s.doa
                ),
                first_attendance AS (
                    SELECT MIN(attendance_date) AS first_attendance_date
                    FROM attendance_data
                )
                SELECT
                    student_id,
                    attendance_date,
                    attendance_status,
                    (SELECT COUNT(*) FROM (SELECT DISTINCT attendance_date FROM attendance_data WHERE attendance_status IS NOT NULL) AS subquery) AS total_classes,
                    (SELECT COUNT(*) FROM (SELECT DISTINCT attendance_date FROM attendance_data WHERE attendance_status = 'P') AS subquery) AS attended_classes,
                    CASE
                        WHEN (SELECT COUNT(*) FROM (SELECT DISTINCT attendance_date FROM attendance_data WHERE attendance_status IS NOT NULL) AS subquery) = 0 THEN NULL
                        ELSE CONCAT(
                            ROUND(
                                ((SELECT COUNT(*) FROM (SELECT DISTINCT attendance_date FROM attendance_data WHERE attendance_status = 'P') AS subquery) * 100.0) /
                                (SELECT COUNT(*) FROM (SELECT DISTINCT attendance_date FROM attendance_data WHERE attendance_status IS NOT NULL) AS subquery), 2
                            ),
                            '%'
                        )
                    END AS attendance_percentage,
                    (SELECT first_attendance_date FROM first_attendance) AS first_attendance_date
                FROM attendance_data
                ORDER BY attendance_date
            ";

            $attendance_result = pg_query_params($con, $attendance_query, [$student_id]);

            if ($attendance_result) {
                $attendance_data = pg_fetch_assoc($attendance_result);
                $average_attendance_percentage = $attendance_data['attendance_percentage'];
                $first_attendance_date = $attendance_data['first_attendance_date'];
            } else {
                $average_attendance_percentage = "N/A";
            }
        } else {
            $no_records_found = true;
        }
    }
}

// SQL query to fetch class and category from exam_marks_data based on filter criteria
$class_category_query = "
    SELECT emd.class, emd.category 
    FROM exam_marks_data emd
    INNER JOIN exams e ON emd.exam_id = e.exam_id
    WHERE emd.student_id = $1 
      AND e.exam_type = $2 
      AND e.academic_year = $3
    LIMIT 1;";

// Execute query
$class_category_result = pg_query_params($con, $class_category_query, [$student_id, $exam_type, $academic_year]);
$class_category_data = pg_fetch_assoc($class_category_result);

// Calculate rank
if ($class_category_data) {
    $class_group_query = "
    SELECT DISTINCT emd.exam_id
    FROM exam_marks_data emd
    JOIN exams e ON emd.exam_id = e.exam_id
    WHERE e.exam_type = $1
      AND e.academic_year = $2
    GROUP BY emd.exam_id
    HAVING COUNT(DISTINCT emd.class) > 1;
    ";

    $class_group_result = pg_query_params($con, $class_group_query, [$exam_type, $academic_year]);

    $grouped_exam_ids = [];
    if ($class_group_result) {
        while ($row = pg_fetch_assoc($class_group_result)) {
            $grouped_exam_ids[] = $row['exam_id'];
        }
    }

    $rank = 'N/A';

    // Only proceed with grouped ranking if we found grouped exams
    if (!empty($grouped_exam_ids)) {
        $placeholders = [];
        foreach ($grouped_exam_ids as $index => $id) {
            $placeholders[] = '$' . ($index + 3);
        }
        $placeholders_str = implode(',', $placeholders);

        $class_group_query = "
        SELECT emd.student_id, 
               ROUND(SUM(COALESCE(emd.written_marks, 0)) + SUM(COALESCE(emd.viva_marks, 0))) AS total_marks
        FROM exam_marks_data emd
        JOIN exams e ON emd.exam_id = e.exam_id
        WHERE e.exam_type = $1
          AND e.academic_year = $2
          AND emd.exam_id IN ($placeholders_str)
        GROUP BY emd.student_id
        ORDER BY total_marks DESC;
        ";

        $params = array_merge([$exam_type, $academic_year], $grouped_exam_ids);
        $class_group_result = pg_query_params($con, $class_group_query, $params);

        if ($class_group_result) {
            $class_marks = [];
            while ($row = pg_fetch_assoc($class_group_result)) {
                $class_marks[] = $row;
            }

            $class_marks = calculateRanksWithTies($class_marks);

            foreach ($class_marks as $student) {
                if ($student['student_id'] == $student_id) {
                    $rank = $student['rank'];
                    break;
                }
            }
        }
    }

    // If we didn't find the student in grouped classes or there were no grouped classes,
    // calculate rank within their own class with tie handling
    if ($rank === 'N/A') {
        $individual_class_query = "
        SELECT emd.student_id, 
            ROUND(SUM(COALESCE(emd.written_marks, 0)) + SUM(COALESCE(emd.viva_marks, 0))) AS total_marks
        FROM exam_marks_data emd
        JOIN exams e ON emd.exam_id = e.exam_id
        WHERE e.exam_type = $1
        AND e.academic_year = $2
        AND emd.class = (
            SELECT emd2.class 
            FROM exam_marks_data emd2
            JOIN exams e2 ON emd2.exam_id = e2.exam_id 
            WHERE emd2.student_id = $3 AND e2.exam_type = $1 AND e2.academic_year = $2
            ORDER BY emd2.id DESC 
            LIMIT 1
        )
        GROUP BY emd.student_id
        ORDER BY total_marks DESC;
        ";

        $individual_class_result = pg_query_params(
            $con,
            $individual_class_query,
            [$exam_type, $academic_year, $student_id]
        );

        if ($individual_class_result) {
            $class_marks = [];
            while ($row = pg_fetch_assoc($individual_class_result)) {
                $class_marks[] = $row;
            }

            $class_marks = calculateRanksWithTies($class_marks);

            foreach ($class_marks as $student) {
                if ($student['student_id'] == $student_id) {
                    $rank = $student['rank'];
                    break;
                }
            }
        }
    }
}

/**
 * Calculate ranks with proper tie handling
 * Students with same total marks get the same rank
 */
function calculateRanksWithTies($students)
{
    if (empty($students)) {
        return [];
    }

    $ranked_students = [];
    $current_rank = 1;
    $previous_marks = null;
    $skip_count = 0;

    foreach ($students as $index => $student) {
        $current_marks = $student['total_marks'];

        if ($current_marks === $previous_marks) {
            // Same marks as previous student - same rank
            $student['rank'] = $current_rank;
            $skip_count++;
        } else {
            // Different marks - increment rank considering skipped positions
            $current_rank += $skip_count;
            $student['rank'] = $current_rank;
            $skip_count = 1; // Reset skip count for new rank group
        }

        $previous_marks = $current_marks;
        $ranked_students[] = $student;
    }

    return $ranked_students;
}

// Calculate percentage and grade
if (isset($total_obtained_marks) && isset($total_full_marks) && $total_full_marks > 0) {
    $percentage = ($total_obtained_marks / $total_full_marks) * 100;
    $formattedPercentage = number_format($percentage, 2);

    // Determine latest exam date (written & viva)
    $written_date = null;
    $viva_date = null;

    foreach ($ordered_marks_data as $row) {
        if (!empty($row['exam_date_written']) && $row['exam_date_written'] !== '0000-00-00') {
            $written_date = $row['exam_date_written'];
        }
        if (!empty($row['exam_date_viva']) && $row['exam_date_viva'] !== '0000-00-00') {
            $viva_date = $row['exam_date_viva'];
        }
    }

    $latest_exam_date = max($written_date, $viva_date);

    // Fetch applicable grade rule based on latest exam date
    $rule_query = "
        SELECT rule_id
        FROM grade_rules
        WHERE valid_from <= $1
        AND (valid_to IS NULL OR valid_to >= $1)
        ORDER BY valid_from DESC
        LIMIT 1
    ";
    $rule_result = pg_query_params($con, $rule_query, [$latest_exam_date]);

    if ($rule_result && pg_num_rows($rule_result) > 0) {
        $rule_row = pg_fetch_assoc($rule_result);
        $rule_id = $rule_row['rule_id'];
    } else {
        $rule_id = null;
    }

    // Fetch grade & description from grade_rule_details
    if ($rule_id !== null) {
        $grade_query = "
            SELECT grade, description
            FROM grade_rule_details
            WHERE rule_id = $1
            AND $2 BETWEEN min_percentage AND max_percentage
            LIMIT 1
        ";
        $grade_result = pg_query_params($con, $grade_query, [$rule_id, $percentage]);

        if ($grade_result && pg_num_rows($grade_result) > 0) {
            $grade_row = pg_fetch_assoc($grade_result);
            $grade = $grade_row['grade'];
            $gradeDescription = $grade_row['description'];
        } else {
            $grade = "N/A";
            $gradeDescription = "Grade not defined";
        }
    } else {
        $grade = "N/A";
        $gradeDescription = "No grading rule applied";
    }

    // Fetch fail cutoff directly from DB
    $fail_query = "
        SELECT max_percentage
        FROM grade_rule_details
        WHERE rule_id = $1
        AND LOWER(description) = 'fail'
    ";

    $fail_result = pg_query_params($con, $fail_query, [$rule_id]);

    $failMax = 0;

    if ($fail_result) {
        while ($row = pg_fetch_assoc($fail_result)) {
            if ($row['max_percentage'] > $failMax) {
                $failMax = $row['max_percentage'];
            }
        }
    }

    // Determine Pass / Fail
    $passOrFail = ($percentage > $failMax) ? 'Pass' : 'Fail';

    // Format rank with ordinal suffix
    function addOrdinalSuffix($number)
    {
        if (!in_array(($number % 100), [11, 12, 13])) {
            switch ($number % 10) {
                case 1:
                    return $number . 'st';
                case 2:
                    return $number . 'nd';
                case 3:
                    return $number . 'rd';
            }
        }
        return $number . 'th';
    }

    $formattedRank = ($rank !== 'N/A') ? addOrdinalSuffix($rank) : 'N/A';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --dark: #2c3e50;
            --light: #ecf0f1;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
        }

        .header {
            background: linear-gradient(135deg, var(--primary), #2980b9);
            color: white;
            padding: 20px 0;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .logo {
            font-weight: 700;
            font-size: 24px;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 10px;
            font-size: 28px;
        }

        .search-box {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .result-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
            <?php if (!$student_exists || $no_records_found): ?>display: none;
            <?php endif; ?>
        }

        /* Student Info section */
        .student-info-section {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .label-cell {
            padding: 6px 12px;
            width: 25%;
            color: #7f8c8d;
            font-weight: 600;
            border-right: 1px solid #eee;
        }

        .value-cell {
            padding: 6px 12px;
            width: 25%;
            border-right: 1px solid #eee;
            font-weight: 600;
        }

        /* Marks Table */
        .marks-table {
            width: 100%;
            border-collapse: collapse;
        }

        .marks-table th {
            background-color: var(--primary);
            color: white;
            padding: 12px 15px;
            text-align: left;
        }

        .marks-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .marks-table tr:hover {
            background-color: #f5f5f5;
        }

        .subject-name {
            font-weight: 600;
        }

        .total-row {
            background-color: var(--light);
            font-weight: 700;
        }

        /* Summary Section */
        .result-summary {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin: 20px 0;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        /* No Result */
        .no-result {
            text-align: center;
            padding: 50px 20px;
            <?php if ($student_exists && !$no_records_found): ?>display: none;
            <?php endif; ?>
        }

        .no-result i {
            font-size: 60px;
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .btn-search {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-search:hover {
            background: #2980b9;
        }

        .print-btn {
            background: var(--dark);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s;
            margin-left: 10px;
        }

        .print-btn:hover {
            background: #1c2833;
        }

        @media print {

            .search-box,
            .no-print {
                display: none;
            }

            .result-card {
                box-shadow: none;
                margin: 0;
            }
        }

        @media (max-width: 768px) {
            .result-summary {
                grid-template-columns: 1fr 1fr;
            }
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 30px;
        }
    </style>

</head>

<body>
    <div class="header">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="logo">
                        <i class="fas fa-graduation-cap"></i>
                        Student Result Portal
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <p>Check your exam results online</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="search-box">
            <h3><i class="fas fa-search"></i> Find Your Result</h3>
            <p class="text-muted">Enter your details to view your exam result</p>

            <form id="resultForm" method="GET">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="student_id">Student ID</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Enter your student ID" value="<?php echo htmlspecialchars($student_id); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="exam_type">Exam Type</label>
                            <select class="form-control" id="exam_type" name="exam_type" required>
                                <option value="">Select Exam</option>
                                <option value="First Term" <?php echo ($exam_type == 'First Term') ? 'selected' : ''; ?>>First Term</option>
                                <option value="Half Yearly" <?php echo ($exam_type == 'Half Yearly') ? 'selected' : ''; ?>>Half Yearly</option>
                                <option value="Annual" <?php echo ($exam_type == 'Annual') ? 'selected' : ''; ?>>Annual</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="academic_year">Academic Year</label>
                            <select class="form-control" id="academic_year" name="academic_year" required>
                                <option value="">Select Year</option>
                                <?php
                                $currentYear = (date('m') == 1 || date('m') == 2 || date('m') == 3) ? date('Y') - 1 : date('Y');
                                for ($i = 0; $i < 5; $i++) {
                                    $year = $currentYear - $i;
                                    $nextYear = $year + 1;
                                    $academicYear = $year . '-' . $nextYear;
                                    $selected = ($academic_year == $academicYear) ? 'selected' : '';
                                    echo "<option value=\"$academicYear\" $selected>$academicYear</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-check"></i> View Result
                    </button>
                    <button type="button" class="print-btn no-print" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Result
                    </button>
                </div>
            </form>
        </div>

        <?php if (isset($_GET['student_id']) && (!$student_exists || $no_records_found)): ?>
            <div class="no-result" id="noResult">
                <i class="fas fa-file-alt"></i>
                <h3>No Result Found</h3>
                <p>Please check your Student ID, Exam Type, and Academic Year</p>
            </div>
        <?php else: ?>
            <div class="no-result" id="noResult" style="display: none;">
                <i class="fas fa-file-alt"></i>
                <h3>No Result Found</h3>
                <p>Please check your Student ID, Exam Type, and Academic Year</p>
            </div>
        <?php endif; ?>

        <?php if ($student_exists && !$no_records_found && isset($marks_result)): ?>
            <div class="result-card" id="resultCard">
                <div class="student-info-section">
                    <table class="info-table">
                        <tr>
                            <td class="label-cell">Student ID</td>
                            <td class="value-cell"><?php echo htmlspecialchars($student_details['student_id']); ?></td>

                            <td class="label-cell">Exam</td>
                            <td class="value-cell"><?php echo htmlspecialchars($exam_type); ?> <?php echo htmlspecialchars($academic_year); ?></td>
                        </tr>

                        <tr>
                            <td class="label-cell">Student Name</td>
                            <td class="value-cell" colspan="3"><?php echo htmlspecialchars($student_details['studentname']); ?></td>
                        </tr>

                        <tr>
                            <td class="label-cell">Class</td>
                            <td class="value-cell"><?php echo htmlspecialchars($class_category_data['category'] . '/' . $class_category_data['class']); ?></td>
                        </tr>
                    </table>
                </div>


                <div class="table-responsive">
                    <table class="marks-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Full Marks</th>
                                <th>Written Marks</th>
                                <th>Viva Marks</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="marksTableBody">
                            <?php
                            $grandFullMarks = 0;
                            $grandWrittenMarks = 0;
                            $grandVivaMarks = 0;
                            $grandTotalMarks = 0;

                            foreach ($ordered_marks_data as $row):
                                $written_marks = ($row['written_attendance_status'] == 'A') ? 'A' : $row['written_marks'];
                                $viva_marks = ($row['viva_attendance_status'] == 'A') ? 'A' : $row['viva_marks'];

                                // Calculate total marks
                                if ($row['written_attendance_status'] == 'A' && $row['viva_attendance_status'] != 'A') {
                                    $total_marks = $row['viva_marks'];
                                } elseif ($row['viva_attendance_status'] == 'A' && $row['written_attendance_status'] != 'A') {
                                    $total_marks = $row['written_marks'];
                                } elseif ($row['written_attendance_status'] != 'A' && $row['viva_attendance_status'] != 'A') {
                                    $total_marks = $row['written_marks'] + $row['viva_marks'];
                                } else {
                                    $total_marks = 0;
                                }

                                $subjectFullMarks = $row['full_marks_written'] + $row['full_marks_viva'];

                                $grandFullMarks += $subjectFullMarks;
                                if ($written_marks !== 'A') $grandWrittenMarks += $written_marks;
                                if ($viva_marks !== 'A') $grandVivaMarks += $viva_marks;
                                $grandTotalMarks += $total_marks;
                            ?>
                                <tr>
                                    <td class="subject-name"><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><?php echo $subjectFullMarks; ?></td>
                                    <td><?php echo $written_marks; ?></td>
                                    <td><?php echo $viva_marks; ?></td>
                                    <td><?php echo $total_marks; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td><strong>Grand Total</strong></td>
                                <td><strong><?php echo $grandFullMarks; ?></strong></td>
                                <td><strong><?php echo $grandWrittenMarks; ?></strong></td>
                                <td><strong><?php echo $grandVivaMarks; ?></strong></td>
                                <td><strong><?php echo $grandTotalMarks; ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="result-summary">
                    <table class="summary-table">
                        <tr>
                            <td class="label-cell">Marks Obtained</td>
                            <td class="value-cell">
                                <?php echo $total_obtained_marks; ?>/<?php echo $total_full_marks; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-cell">Result</td>
                            <td class="value-cell" colspan="3">
                                <?php echo $passOrFail; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> Student Result Portal. All rights reserved.</p>
        <p>For any discrepancies, please contact your school administration.</p>
    </div>

    <script>
        // Simple form validation
        document.getElementById('resultForm').addEventListener('submit', function(e) {
            const studentId = document.getElementById('student_id').value;
            const examType = document.getElementById('exam_type').value;
            const academicYear = document.getElementById('academic_year').value;

            if (!studentId || !examType || !academicYear) {
                e.preventDefault();
                alert('Please fill in all fields before submitting.');
            }
        });
    </script>
</body>

</html>