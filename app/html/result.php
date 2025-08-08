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
    $start_year = intval($start_year);  // Ensure it's an integer

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
    $end_date = min($end_date, $today_date); // Consider the current date if before the term end date

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
            student.dateofbirth, 
            student.photourl,
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
                $attendance_data = pg_fetch_assoc($attendance_result);  // Fetch a single row as an associative array
                $average_attendance_percentage = $attendance_data['attendance_percentage'];
                $first_attendance_date = $attendance_data['first_attendance_date'];
                // Output attendance data for debugging
                // echo "<pre>";
                // print_r($attendance_data);
                // echo "</pre>";
            } else {
                $average_attendance_percentage = "N/A";
            }
        } else {
            $no_records_found = true;
        }
    }
}
?>
<?php
// Fetch exam results and calculate percentages for all exams conducted this academic year
$percentage_query = "
    SELECT 
        exams.exam_type,
        ROUND(exam_marks_data.written_marks) AS written_marks,
        ROUND(exam_marks_data.viva_marks) AS viva_marks,
        exams.full_marks_written,
        exams.full_marks_viva
    FROM exam_marks_data
    JOIN exams ON exam_marks_data.exam_id = exams.exam_id
    WHERE exam_marks_data.student_id = $1
    AND exams.academic_year = $2
    ORDER BY exams.exam_type";

// Prepare for storing exam percentages
$exam_percentages = [
    'First Term' => 'N/A',
    'Half Yearly' => 'N/A',
    'Annual' => 'N/A'
];

// Run the query to fetch marks data
$percentage_result = pg_query_params($con, $percentage_query, [$student_id, $academic_year]);

if ($percentage_result) {
    // Initialize variables to calculate marks for each exam type
    $exam_types = ['First Term', 'Half Yearly', 'Annual'];

    // Loop through each exam type and calculate percentage
    foreach ($exam_types as $exam_label) { // Using $exam_label instead of $exam_type
        // Initialize totals for each exam type using unique variable names
        $full_marks_for_exam = 0;
        $obtained_marks_for_exam = 0;

        // Fetch and process the data for the current exam type
        pg_result_seek($percentage_result, 0); // Reset the result pointer before processing
        while ($row = pg_fetch_assoc($percentage_result)) {
            if ($row['exam_type'] == $exam_label) { // Compare with $exam_label
                // Sum full marks and obtained marks using new variable names
                $full_marks_for_exam += $row['full_marks_written'] + $row['full_marks_viva'];
                $obtained_marks_for_exam += $row['written_marks'] + $row['viva_marks'];
            }
        }

        // Calculate percentage if total marks are greater than zero
        if ($full_marks_for_exam > 0) {
            $percentage = ($obtained_marks_for_exam / $full_marks_for_exam) * 100;
            $exam_percentages[$exam_label] = number_format($percentage, 2) . '%'; // Store result using $exam_label
        } else {
            $exam_percentages[$exam_label] = 'N/A';  // No data or no full marks
        }
    }
}

// Now you can use the $exam_percentages array to display the values in the HTML table

?>
<?php
// SQL query to fetch class and category from exam_marks_data based on filter criteria
$class_category_query = "
    SELECT emd.class, emd.category 
    FROM exam_marks_data emd
    INNER JOIN exams e ON emd.exam_id = e.exam_id
    WHERE emd.student_id = $1 
      AND e.exam_type = $2 
      AND e.academic_year = $3
    LIMIT 1;";  // Assuming class and category will be the same, fetching one row

// Execute query
$class_category_result = pg_query_params($con, $class_category_query, [$student_id, $exam_type, $academic_year]);
$class_category_data = pg_fetch_assoc($class_category_result);
?>
<?php
if ($class_category_data) {
    // Step 1: Find grouped exam IDs for the exam type and academic year
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
        // Step 2: Build dynamic placeholders for IN clause
        $placeholders = [];
        foreach ($grouped_exam_ids as $index => $id) {
            $placeholders[] = '$' . ($index + 3);
        }
        $placeholders_str = implode(',', $placeholders);

        // Step 3: Create the query to fetch student ranks across grouped classes
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

        // Merge parameters for pg_query_params
        $params = array_merge([$exam_type, $academic_year], $grouped_exam_ids);

        // Step 4: Execute the query
        $class_group_result = pg_query_params($con, $class_group_query, $params);

        if ($class_group_result) {
            $class_marks = [];
            while ($row = pg_fetch_assoc($class_group_result)) {
                $class_marks[] = $row;
            }

            // Find the rank of the specific student
            foreach ($class_marks as $index => $student) {
                if ($student['student_id'] == $student_id) {
                    $rank = $index + 1;
                    break;
                }
            }
        }
    }

    // If we didn't find the student in grouped classes or there were no grouped classes,
    // calculate rank within their own class
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
            WHERE emd2.student_id = $3 
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

            foreach ($class_marks as $index => $student) {
                if ($student['student_id'] == $student_id) {
                    $rank = $index + 1;
                    break;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11316670180"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', 'AW-11316670180');
    </script>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>
        <?php
        if (
            !isset($student_id) || !isset($exam_type) || !isset($academic_year) ||
            $student_id === "" || $exam_type === "" || $academic_year === ""
        ) {
            echo 'Result Portal';
        } else {
            echo htmlspecialchars("$student_id" . "_" . "$exam_type" . "_" . "$academic_year");
        }
        ?>
    </title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Comfortaa');
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@600&display=swap');

        body {
            background: #ffffff;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        @media (min-width:767px) {
            .top {
                margin-top: 2%
            }
        }

        @media (max-width:767px) {
            .top {
                margin-top: 10%
            }

            .topbutton {
                margin-top: 5%
            }
        }

        @media print {
            .noprint {
                visibility: hidden;
                position: absolute;
                left: 0;
                top: 0;
            }

            .footer {
                position: fixed;
                bottom: 0;
            }
        }

        @media screen {
            .no-display {
                display: none;
            }
        }

        .report-footer {
            position: fixed;
            bottom: 0px;
            height: 20px;
            display: block;
            width: 90%;
            border-top: solid 1px #ccc;
            overflow: visible;
        }
    </style>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
</head>

<body>
    <div class="col-md-12">
        <?php if ($print == FALSE) { ?>
            <div class="noprint" style="display: flex; justify-content: flex-end; margin-top: 2%;">
                <form action="" method="GET" id="formid">
                    <input name="student_id" class="form-control" style="width: max-content; display: inline-block;" required placeholder="Student ID" value="<?php echo @$student_id ?>">
                    <select name="exam_type" class="form-control" style="width: max-content; display: inline-block;" required>
                        <?php if ($exam_type == null) { ?>
                            <option disabled selected hidden value="">Select Exam Name</option>
                        <?php } else { ?>
                            <option hidden selected><?php echo $exam_type ?></option>
                        <?php } ?>
                        <option>First Term</option>
                        <option>Half Yearly</option>
                        <option>Annual</option>
                    </select>
                    <select name="academic_year" id="academic_year" class="form-control" style="width: max-content; display: inline-block;" required>
                        <?php if ($academic_year == null) { ?>
                            <option disabled selected hidden value="">Select Year</option>
                        <?php } else { ?>
                            <option hidden selected><?php echo $academic_year ?></option>
                        <?php } ?>
                    </select>
                    <div class="col topbutton" style="display: inline-block;">
                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                            <i class="bi bi-search"></i>&nbsp;Search</button>
                        <button type="button" onclick="window.print()" name="print" class="btn btn-danger btn-sm" style="outline: none;">
                            <i class="fa-solid fa-print"></i>&nbsp;Print</button>
                    </div>
                </form>
                <br>
            </div>

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
        <?php } ?>

        <?php
        if ($exam_type > 0) {
            // Show error messages if any
            if (isset($_GET['student_id']) && !$student_exists) { ?>
                <div class="container alert alert-danger" role="alert" style="margin-top:20px;">
                    <h4 class="alert-heading">Student Not Found</h4>
                    <p>We couldn't find any student with the ID: <strong><?php echo htmlspecialchars($student_id); ?></strong></p>
                    <hr>
                    <p class="mb-0">Please verify the student ID and try again. If you believe this is an error, please contact support.</p>
                </div>
            <?php } elseif ($no_records_found) { ?>
                <div class="container alert alert-warning" role="alert" style="margin-top:20px;">
                    <h4 class="alert-heading">No Records Found</h4>
                    <p>We couldn't find any exam records matching your search criteria:</p>
                    <ul>
                        <li><strong>Student ID:</strong> <?php echo htmlspecialchars($student_id); ?></li>
                        <li><strong>Exam Type:</strong> <?php echo htmlspecialchars($exam_type); ?></li>
                        <li><strong>Academic Year:</strong> <?php echo htmlspecialchars($academic_year); ?></li>
                    </ul>
                    <hr>
                    <p class="mb-0">Please verify your search parameters and try again. If you believe this is an error, please contact your administrator.</p>
                </div>
            <?php } elseif ($student_exists && !$no_records_found && isset($marks_result)) { ?>

                <?php
                function calculateGrade($percentage)
                {
                    if ($percentage >= 90) {
                        return ["A1", "Excellent"];
                    } elseif ($percentage >= 80) {
                        return ["A2", "Very Good"];
                    } elseif ($percentage >= 70) {
                        return ["B1", "Good"];
                    } elseif ($percentage >= 60) {
                        return ["B2", "Above Average"];
                    } elseif ($percentage >= 50) {
                        return ["C1", "Fair / Average"];
                    } elseif ($percentage >= 40) {
                        return ["C2", "Below Average"];
                    } elseif ($percentage >= 20) {
                        return ["D", "Poor"];
                    } else {
                        return ["E", "Fail"];
                    }
                }

                $percentage = ($total_obtained_marks / $total_full_marks) * 100;
                $formattedPercentage = number_format($percentage, 2);
                $gradeAndDesc = calculateGrade($percentage);

                // Determine pass or fail based on the formatted percentage
                $passOrFail = ($percentage < 20) ? 'Fail' : 'Pass';
                ?>

                <table class="table" border="0">
                    <thead> <!--class="no-display"-->
                        <tr>
                            <td colspan=4>
                                <div class="row">
                                    <div class="col" style="display: inline-block; width:65%;">

                                        <?php
                                        if ($student_details['category'] == 'LG1') {
                                            echo '<p><b>KALPANA BUDS SCHOOL</b></p>';
                                        } else {
                                            echo '<p><b>RSSI NGO</b></p>';
                                        }
                                        ?>
                                        <p>(A division of Rina Shiksha Sahayak Foundation)</p>
                                        <p>NGO-DARPAN Id: WB/2021/0282726, CIN: U80101WB2020NPL237900</p>
                                        <p>Email: info@rssi.in, Website: www.rssi.in</p>
                                    </div>
                                    <div class="col" style="display: inline-block; width:32%; vertical-align: top;">
                                        <p style="font-size: small;">Scan QR code to check authenticity</p>
                                        <?php
                                        $exam = str_replace(" ", "%20", $exam_type);
                                        $url = "https://login.rssi.in/result.php?student_id=$student_id&exam_type=$exam_type&academic_year=$academic_year";
                                        $url_u = urlencode($url); ?>
                                        <!-- https://qrcode.tec-it.com/API/QRCode?data= -->
                                        <img class="qrimage" src="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo $url_u ?>" width="80px" />&nbsp;
                                        <img src=<?php echo $student_details['photourl'] ?> width=80px height=80px />
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4">
                                <h3 style="text-align:center;margin-top: 10px;font-family: 'Cinzel', serif;">Report card</h3>
                            </td>
                        </tr>
                        <tr>
                            <td>STUDENT ID</td>
                            <th><?php echo $student_details['student_id'] ?></th>
                            <td> LEARNING GROUP/CLASS </td>
                            <th><?php echo $class_category_data['category'] ?>/<?php echo $class_category_data['class'] ?></th>
                        </tr>
                        <tr>
                            <td>NAME OF STUDENT</td>
                            <th><?php echo $student_details['studentname'] ?></th>
                            <td>DATE OF BIRTH</td>
                            <th><?php echo date("d/m/Y", strtotime($student_details['dateofbirth'])) ?></th>
                        </tr>
                    </tbody>
                </table>
                <table>
                    <tr>
                        <td style="text-align:center;"><b><?php echo $exam_type ?>&nbsp;Exam&nbsp; <?php echo $academic_year ?></b></td>
                    </tr>
                </table>
                <br>
                <table class="table" border="1" align="center" style="width: 100%;">
                    <tr>
                        <th>Subject</th>
                        <th colspan="2">Full Marks</th>
                        <th colspan="2">Marks Obtained</th>
                        <th>Total Marks Obtained</th>
                        <th>Positional grade</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th>Viva</th>
                        <th>Written</th>
                        <th>Viva</th>
                        <th>Written</th>
                        <th></th>
                    </tr>

                    <?php foreach ($ordered_marks_data as $row) {
                        $written_marks = ($row['written_attendance_status'] == 'A') ? 'A' : $row['written_marks'];
                        $viva_marks = ($row['viva_attendance_status'] == 'A') ? 'A' : $row['viva_marks'];
                        $total_marks = 0;

                        if ($row['written_attendance_status'] == 'A' && $row['viva_attendance_status'] != 'A') {
                            $total_marks = $row['viva_marks'];
                        } elseif ($row['viva_attendance_status'] == 'A' && $row['written_attendance_status'] != 'A') {
                            $total_marks = $row['written_marks'];
                        } elseif ($row['written_attendance_status'] != 'A' && $row['viva_attendance_status'] != 'A') {
                            $total_marks = $row['written_marks'] + $row['viva_marks'];
                        } else {
                            $total_marks = 0;
                        } // Calculate total marks
                        echo "<tr>
                <td>" . $row['subject'] . "</td>
                <td>" . $row['full_marks_viva'] . "</td>
                <td>" . $row['full_marks_written'] . "</td>
                <td>" . $viva_marks . "</td>
                <td>" . $written_marks . "</td>
                <td>" . $total_marks . "</td>
                <!--<td>" . $row['written_attendance_status'] . "</td>-->
                <!--<td>" . $row['viva_attendance_status'] . "</td>-->
            </tr>";
                    }

                    // Add total row
                    echo "<tr>
            <td><strong>Total</strong></td>
            <td colspan='2'><strong>$total_full_marks</strong></td>
            <td colspan='2'></td>
            <td><strong>$total_obtained_marks ($formattedPercentage%)</strong></td>
            <th>$gradeAndDesc[0]-$gradeAndDesc[1]</th>
                </tr>";
                    echo "</table>"; ?>
                    <table border="0" align="right" style="width: 50%; margin: 0 auto;">
                        <tbody>
                            <tr>
                                <td>
                                    <table class="table" border="0" style="width: 100%;">
                                        <tbody>
                                            <tr>
                                                <td>Result</td>
                                                <th><?php echo strtoupper($passOrFail) ?></th>
                                            </tr>
                                            <tr>
                                                <td>Overall ranking</td>
                                                <?php
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

                                                echo ($formattedPercentage >= 75 && $rank <= 3) ? "<th>" . addOrdinalSuffix($rank) . "</th>" : "<th></th>";
                                                ?>
                                            </tr>
                                            <tr>
                                                <td>Attendance (<?php echo date('d/m/Y', strtotime($first_attendance_date)) ?>-<?php echo date('d/m/Y', strtotime($end_date)) ?>)</td>
                                                <td><?php echo $average_attendance_percentage ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table class="table visible-print-block" border="0" style="width: 100%; margin-top: 20%;">
                                        <tbody>
                                            <tr>
                                                <td>Signature of Class Teacher / Center In-charge<br><br>Date:</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>


                    <table class="table" border="1" style="width: 45%;">
                        <tr>
                            <th colspan="2">Summary of Examinations for Academic Year <?php echo $academic_year; ?></th>
                        </tr>
                        <tr>
                            <th>Exam Type</th>
                            <th>Marks Obtained</th>
                        </tr>
                        <tr>
                            <td>First Term</td>
                            <td><?php echo $exam_percentages['First Term']; ?></td>
                        </tr>
                        <tr>
                            <td>Half Yearly</td>
                            <td><?php echo $exam_percentages['Half Yearly']; ?></td>
                        </tr>
                        <tr>
                            <td>Annual</td>
                            <td><?php echo $exam_percentages['Annual']; ?></td>
                        </tr>
                    </table>

                    <p class="report-footer visible-print-block" style="text-align: right;">A - Absent denotes that the student was absent during the exam for that particular subject.</p>
                <?php }
        } elseif ($exam_type == "" && $student_id == "") {
                ?>
                <div class="noprint">
                    <style>
                        h1 {
                            font-size: 36px;
                            text-align: center;
                            margin-top: 50px;
                        }

                        p {
                            font-size: 24px;
                            text-align: center;
                            margin-top: 20px;
                        }

                        form {
                            margin: 50px auto;
                            width: 400px;
                            border: 2px solid #ccc;
                            padding: 20px;
                            border-radius: 10px;
                        }
                    </style>

                    <h1>Welcome to the Online Result Portal of RSSI NGO</h1>
                    <p>Please enter your Student ID, Exam Name, and Year to view your result.</p>
                </div>
            <?php
        }
            ?>
    </div>
    <script>
        function submit() {
            document.getElementById("formid").click();
            document.lostpasswordform.submit();
        }
    </script>
    <script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
</body>

</html>