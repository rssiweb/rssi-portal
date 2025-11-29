<?php
require_once __DIR__ . '/../bootstrap.php';
include(__DIR__ . "/../util/login_util.php");

// Security: Multiple checks
function isAllowedAccess()
{
    $valid_api_key = 'YOUR_SECRET_API_KEY';
    $provided_api_key = $_GET['api_key'] ?? '';

    // Allowed origins
    $allowed_origins = [
        'http://localhost:8082',
        'https://login.rssi.in',
        'https://rssi.in',
        'https://www.rssi.in'
    ];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    // Allowed referer domains
    $allowed_domains = [
        'localhost',
        'login.rssi.in',
        'rssi.in',
        'www.rssi.in'
    ];
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $is_allowed_referer = false;

    foreach ($allowed_domains as $domain) {
        if (strpos($referer, $domain) !== false) {
            $is_allowed_referer = true;
            break;
        }
    }

    return ($provided_api_key === $valid_api_key)
        || in_array($origin, $allowed_origins)
        || $is_allowed_referer;
}

if (!isAllowedAccess()) {
    http_response_code(403);
    echo '<div class="alert alert-danger text-center">
            <h4>Access Denied</h4>
            <p>Please use the official student result portal to access your results.</p>
            <p><a href="https://rssi.in/result-portal" class="btn btn-primary">Go to Result Portal</a></p>
          </div>';
    exit;
}

// Set initial values from GET parameters
$student_id = $_GET['student_id'] ?? '';
$exam_type = $_GET['exam_type'] ?? '';
$academic_year = $_GET['academic_year'] ?? '';

// Initialize flags
$student_exists = false;
$no_records_found = false;
$result_published = false;

// NEW FUNCTION: Check if result is published
function isResultPublished($con, $exam_type, $academic_year)
{
    $query = "SELECT publish_date FROM result_publication_dates 
              WHERE exam_type = $1 AND academic_year = $2";
    $result = pg_query_params($con, $query, [$exam_type, $academic_year]);

    if ($result && pg_num_rows($result) > 0) {
        $row = pg_fetch_assoc($result);
        $publish_date = strtotime($row['publish_date']);
        $current_date = time();

        return $current_date >= $publish_date;
    }

    // If no record exists, consider result as not published
    return false;
}

// Check if required parameters are provided
if (isset($_GET['student_id']) && isset($_GET['exam_type']) && isset($_GET['academic_year'])) {

    // Check if result is published
    $result_published = isResultPublished($con, $exam_type, $academic_year);

    if (!$result_published) {
        // Result not published yet
        echo '<div class="alert alert-info text-center">
                <i class="glyphicon glyphicon-time"></i>
                <h4>Result Not Published Yet</h4>
                <p>The result for ' . htmlspecialchars($exam_type) . ' exam of academic year ' . htmlspecialchars($academic_year) . ' has not been published yet.</p>
                <p>Please check back later or contact your school administration.</p>
              </div>';
        exit;
    }

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
}

// Output the result HTML
?>
<?php if ($student_exists && !$no_records_found && isset($marks_result)): ?>
    <div class="result-card">
        <!-- Student Information -->
        <div class="panel panel-info">
            <div class="panel-heading">
                <h4 class="panel-title">Student Information</h4>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3"><strong>Student ID:</strong> <?php echo htmlspecialchars($student_details['student_id']); ?></div>
                    <div class="col-md-3"><strong>Student Name:</strong> <?php echo htmlspecialchars($student_details['studentname']); ?></div>
                    <div class="col-md-3"><strong>Class:</strong> <?php echo htmlspecialchars($class_category_data['category'] . '/' . $class_category_data['class']); ?></div>
                    <div class="col-md-3"><strong>Exam:</strong> <?php echo htmlspecialchars($exam_type); ?> <?php echo htmlspecialchars($academic_year); ?></div>
                </div>
            </div>
        </div>

        <!-- Marks Table -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title">Subject-wise Marks</h4>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th>Subject</th>
                                <th>Full Marks</th>
                                <th>Written Marks</th>
                                <th>Viva Marks</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
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
                                    <td><strong><?php echo htmlspecialchars($row['subject']); ?></strong></td>
                                    <td><?php echo $subjectFullMarks; ?></td>
                                    <td><?php echo $written_marks; ?></td>
                                    <td><?php echo $viva_marks; ?></td>
                                    <td><?php echo $total_marks; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-warning">
                                <td><strong>Grand Total</strong></td>
                                <td><strong><?php echo $grandFullMarks; ?></strong></td>
                                <td><strong><?php echo $grandWrittenMarks; ?></strong></td>
                                <td><strong><?php echo $grandVivaMarks; ?></strong></td>
                                <td><strong><?php echo $grandTotalMarks; ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Result Summary -->
        <div class="panel panel-success">
            <div class="panel-heading">
                <h4 class="panel-title">Result Summary</h4>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Marks Obtained:</strong><br>
                        <?php echo $total_obtained_marks; ?>/<?php echo $total_full_marks; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Percentage:</strong><br>
                        <?php echo $formattedPercentage; ?>%
                    </div>
                    <div class="col-md-3">
                        <strong>Grade:</strong><br>
                        <?php echo $grade . ' - ' . $gradeDescription; ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Result:</strong><br>
                        <span class="label label-<?php echo $passOrFail == 'Pass' ? 'success' : 'danger'; ?>">
                            <?php echo $passOrFail; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning text-center">
        <i class="glyphicon glyphicon-warning-sign"></i>
        <h4>No Result Found</h4>
        <p>Please check your Student ID, Exam Type, and Academic Year</p>
    </div>
<?php endif; ?>