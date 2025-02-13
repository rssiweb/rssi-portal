<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");

// Set initial values for form fields
$student_id = $_GET['student_id'] ?? '';
$exam_type = $_GET['exam_type'] ?? '';
$academic_year = $_GET['academic_year'] ?? '';
$print = (isset($_GET["print"]) ? $_GET["print"] : "False") == "True";


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
    if ($student_result) {
        $student_details = pg_fetch_assoc($student_result);

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
                        AND student.doa <= exams.exam_date_written THEN 'A'
                    ELSE attendance_written.attendance_status
                END AS written_attendance_status,
                CASE
                    WHEN attendance_viva.attendance_status IS NULL 
                        AND student.doa <= exams.exam_date_viva THEN 'A'
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
        if ($marks_result) {
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
    attendance_data AS (
        SELECT
            s.student_id,
            dr.attendance_date,
            CASE
                WHEN a.user_id IS NOT NULL THEN 'P'
                ELSE 'A'
            END AS attendance_status
        FROM date_range dr
        CROSS JOIN rssimyprofile_student s
        LEFT JOIN (
            SELECT DISTINCT user_id, date
            FROM attendance
        ) a ON s.student_id = a.user_id AND a.date = dr.attendance_date
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
        (SELECT COUNT(*) FROM (SELECT DISTINCT attendance_date FROM attendance_data) AS subquery) AS total_classes,
        (SELECT COUNT(*) FROM (SELECT DISTINCT attendance_date FROM attendance_data WHERE attendance_status = 'P') AS subquery) AS attended_classes,
        CASE
            WHEN (SELECT COUNT(*) FROM (SELECT DISTINCT attendance_date FROM attendance_data) AS subquery) = 0 THEN NULL
            ELSE CONCAT(
                ROUND(
                    ((SELECT COUNT(*) FROM (SELECT DISTINCT attendance_date FROM attendance_data WHERE attendance_status = 'P') AS subquery) * 100.0) /
                    (SELECT COUNT(*) FROM (SELECT DISTINCT attendance_date FROM attendance_data) AS subquery), 2
                ),
                '%'
            )
        END AS attendance_percentage,
         (SELECT first_attendance_date FROM first_attendance) AS first_attendance_date -- Ensure proper usage of subquery
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
                            <option disabled selected hidden>Select Exam Name</option>
                        <?php } else { ?>
                            <option hidden selected><?php echo $exam_type ?></option>
                        <?php } ?>
                        <option>First Term</option>
                        <option>Half Yearly</option>
                        <option>Annual</option>
                    </select>
                    <select name="academic_year" id="academic_year" class="form-control" style="width: max-content; display: inline-block;" required>
                        <?php if ($academic_year == null) { ?>
                            <option disabled selected hidden>Select Year</option>
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
        if (@$exam_type > 0) {
            if ($marks_result) { ?>

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
                            <th><?php echo $student_details['category'] ?>/<?php echo $student_details['class'] ?></th>
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
                                                <th></th>
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
                                    <table class="table visible-xs" border="0" style="width: 100%; margin-top: 20%;">
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


                <?php } else {
                echo "Error fetching exam details and marks.";
            }
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
        } else {
            ?>
                No record found for <?php echo $student_id; ?>&nbsp;<?php echo $exam_type; ?>&nbsp;<?php echo $academic_year; ?>
            <?php
        }
            ?>
            <p class="report-footer visible-xs" style="text-align: right;">A - Absent denotes that the student was absent during the exam for that particular subject.</p>

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