<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");

$examData = null; // Variable to store fetched exam data
$message = ''; // Message to display errors or success

// Check if an exam ID was provided for fetching details
if (isset($_GET['fetch_exam_id'])) {
    $exam_id = $_GET['fetch_exam_id'];

    if (!$con) {
        $message = 'Database connection failed';
    } else {
        // Query to fetch exam details based on exam ID
        $query = "SELECT *,
                         viva_teacher.fullname AS fullname_viva,
                         written_teacher.fullname AS fullname_written
                  FROM exams e
                  LEFT JOIN rssimyaccount_members viva_teacher ON e.teacher_id_viva = viva_teacher.associatenumber
                  LEFT JOIN rssimyaccount_members written_teacher ON e.teacher_id_written = written_teacher.associatenumber
                  WHERE e.exam_id = $1";
        $result = pg_query_params($con, $query, array($exam_id));

        if ($result && pg_num_rows($result) > 0) {
            $examData = pg_fetch_assoc($result); // Fetch exam data
            
            // Assign values to individual variables
            $exam_type = $examData['exam_type'] ?? null;
            $academic_year = $examData['academic_year'] ?? null;
            $exam_mode = $examData['exam_mode'] ?? null;
            $subject = $examData['subject'] ?? null;
            $full_marks_written = $examData['full_marks_written'] ?? null;
            $full_marks_viva = $examData['full_marks_viva'] ?? null;
            $fullname_viva = $examData['fullname_viva'] ?? null;
            $fullname_written = $examData['fullname_written'] ?? null;
        
            // Include the exam ID
            $exam_id = $exam_id;
        } else {
            $message = 'Exam ID not found';
        }

        pg_free_result($result);
    }
}

// Check if the form was submitted to update exam details
if (isset($_POST['update_exam'])) {
    $exam_id = $_POST['exam_id_hidden'];
    $viva_exam_date = !empty($_POST['viva_exam_date']) ? $_POST['viva_exam_date'] : null;
    $written_exam_date = !empty($_POST['written_exam_date']) ? $_POST['written_exam_date'] : null;
    $examiner_viva = $_POST['examiner_viva'];
    $examiner_written = $_POST['examiner_written'];
    // Check if the checkbox for sending email notification to the Viva examiner is checked
    $send_email_viva = isset($_POST['send_email_viva']) ? true : false;

    // Check if the checkbox for sending email notification to the Written examiner is checked
    $send_email_written = isset($_POST['send_email_written']) ? true : false;

    // Query to update exam details
    $update_query = "UPDATE exams SET exam_date_viva = $1, exam_date_written = $2, 
                     teacher_id_viva = $3, teacher_id_written = $4 
                     WHERE exam_id = $5";
    $update_result = pg_query_params($con, $update_query, array($viva_exam_date, $written_exam_date, $examiner_viva, $examiner_written, $exam_id));
    $cmdtuples = pg_affected_rows($update_result);

    if ($update_result) {
        $message = 'Exam details updated successfully!';


        // Fetch details of both examiners in one query
        $examiner_ids = [$examiner_viva, $examiner_written];
        $examiner_data_query = pg_query($con, "SELECT associatenumber, phone, email, fullname 
                                   FROM rssimyaccount_members 
                                   WHERE associatenumber IN ('" . implode("','", $examiner_ids) . "')");

        $examiners = [];
        while ($row = pg_fetch_assoc($examiner_data_query)) {
            $examiners[$row['associatenumber']] = $row;
        }

        // Assign details for Viva and Written examiners
        $examiner_contact = $examiners[$examiner_viva]['phone'] ?? null;
        $examiner_email = $examiners[$examiner_viva]['email'] ?? null;
        $examiner_name = $examiners[$examiner_viva]['fullname'] ?? null;

        $examiner_contact_written = $examiners[$examiner_written]['phone'] ?? null;
        $examiner_email_written = $examiners[$examiner_written]['email'] ?? null;
        $examiner_name_written = $examiners[$examiner_written]['fullname'] ?? null;

        // Send emails if conditions are met
        if ($cmdtuples == 1 && !empty($examiner_email) &&  $send_email_viva == true) {
            sendEmail("exam_create", [
                "exam_id" => $exam_id,
                "exam_type" => $exam_type,
                "academic_year" => $academic_year,
                "subject" => $subject,
                "exam_mode" => $exam_mode_pg_array,
                "full_marks_written" => $full_marks_written,
                "full_marks_viva" => $full_marks_viva,
                "examiner_name" => $examiner_name,
            ], $examiner_email);
        }

        if ($cmdtuples == 1 && !empty($examiner_email_written) && $send_email_written == true) {
            sendEmail("exam_create", [
                "exam_id" => $exam_id,
                "exam_type" => $exam_type,
                "academic_year" => $academic_year,
                "subject" => $subject,
                "exam_mode" => $exam_mode_pg_array,
                "full_marks_written" => $full_marks_written,
                "full_marks_viva" => $full_marks_viva,
                "examiner_name" => $examiner_name_written,
            ], $examiner_email_written);
        }
        echo "<script type='text/javascript'>
                alert('$message');
                window.location.href = window.location.href;  // Reload the page once to reflect the latest data
              </script>";
    } else {
        $message = 'Failed to update exam details.';
        echo "<script type='text/javascript'>
                alert('$message');
              </script>";
    }
    pg_free_result($update_result);
}

// Fetching the data and populating the $teachers array
$query = "SELECT associatenumber, fullname FROM rssimyaccount_members WHERE filterstatus = 'Active'";
$result = pg_query($con, $query);

if (!$result) {
    die("Error in SQL query: " . pg_last_error());
}

$teachers = array();
while ($row = pg_fetch_assoc($result)) {
    $teachers[] = $row;
}

// Free resultset
pg_free_result($result);

pg_close($con);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Details Management</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .exam-info {
            line-height: 1.5;
        }

        .divider {
            border-bottom: 1px solid #dee2e6;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-4">Manage Exam Details</h2>

        <!-- Display messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Fetch Info Form -->
        <form method="GET" id="fetchForm" class="row g-3">
            <div class="col-auto">
                <label for="fetch_exam_id" class="visually-hidden">Exam ID</label>
                <input type="text" id="fetch_exam_id" name="fetch_exam_id" class="form-control" placeholder="Enter Exam ID" value="<?php echo @$exam_id ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary fetch-btn">Fetch Info</button>
            </div>
        </form>

        <!-- Update Form (shown if data is fetched) -->
        <?php if ($examData): ?>
            <div class="container mt-5">
                <div class="divider"></div>
                <div class="row exam-info">
                    <div class="col-md-6"><strong>Exam ID:</strong> <?php echo $examData['exam_id']; ?></div>
                    <div class="col-md-6"><strong>Exam Type:</strong> <?php echo $examData['exam_type']; ?></div>
                </div>

                <div class="row exam-info">
                    <div class="col-md-6"><strong>Academic Year: </strong><?php echo $examData['academic_year']; ?></div>
                    <div class="col-md-6"><strong>Subject:</strong> <?php echo $examData['subject']; ?></div>
                </div>

                <div class="row exam-info">
                    <div class="col-md-6">
                        <strong>Examiner for Viva:</strong>
                        <?php
                        if (!empty($examData['teacher_id_viva'])) {
                            echo $examData['teacher_id_viva'] . " - " . $examData['fullname_viva'];
                        } else {
                            echo "Not Assigned";
                        }
                        ?>
                    </div>

                    <div class="col-md-6"><strong>Exam mode:</strong>
                        <?php
                        if ($examData['full_marks_written'] !== null) {
                            echo 'W-' . htmlspecialchars($examData['full_marks_written']);
                        }
                        if ($examData['full_marks_viva'] !== null) {
                            echo ' V-' . htmlspecialchars($examData['full_marks_viva']);
                        }
                        ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Examiner for Written:</strong>
                        <?php
                        if (!empty($examData['teacher_id_written'])) {
                            echo $examData['teacher_id_written'] . " - " . $examData['fullname_written'];
                        } else {
                            echo "Not Assigned";
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="divider"></div>
            <form method="POST" id="updateForm" class="mt-4">
                <div class="mb-3">
                    <label for="viva_exam_date" class="form-label">Viva Exam Date</label>
                    <input type="date" id="viva_exam_date" name="viva_exam_date" class="form-control" value="<?php echo $examData['exam_date_viva']; ?>">
                </div>

                <div class="mb-3">
                    <label for="written_exam_date" class="form-label">Written Exam Date</label>
                    <input type="date" id="written_exam_date" name="written_exam_date" class="form-control" value="<?php echo $examData['exam_date_written']; ?>">
                </div>
                <div class="mb-3">
                    <label for="examiner_viva" class="form-label">Examiner for Viva</label>
                    <select id="examiner_viva" name="examiner_viva" class="form-select select2">
                        <!-- Default placeholder option -->
                        <option value="">Select Examiner</option>

                        <!-- Populate options from the teachers list -->
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo htmlspecialchars($teacher['associatenumber']); ?>"
                                <?php echo ($teacher['associatenumber'] == $examData['teacher_id_viva']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher['associatenumber']) . ' - ' . htmlspecialchars($teacher['fullname']); ?>
                            </option>
                        <?php endforeach; ?>

                        <!-- Add the examiner ID as a placeholder if not in the list and not null/blank -->
                        <?php if (!empty($examData['teacher_id_viva']) && !in_array($examData['teacher_id_viva'], array_column($teachers, 'associatenumber'))): ?>
                            <option value="<?php echo htmlspecialchars($examData['teacher_id_viva']); ?>" selected>
                                <?php echo htmlspecialchars($examData['teacher_id_viva'] . ' - ' . ($examData['fullname_viva'] ?? 'Unknown')) . ' (Not in the list)'; ?>
                            </option>
                        <?php endif; ?>
                    </select>

                    <!-- Viva Notification Checkbox -->
                    <label>
                        <input type="checkbox" name="send_email_viva" value="1" <?php echo isset($_POST['send_email_viva']) ? 'checked' : ''; ?>>
                        Send Email Notification to Viva Examiner
                    </label>
                </div>


                <div class="mb-3">
                    <label for="examiner_written" class="form-label">Examiner for Written</label>
                    <select id="examiner_written" name="examiner_written" class="form-select select2">
                        <!-- Default placeholder option -->
                        <option value="">Select Examiner</option>

                        <!-- Populate options from the teachers list -->
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo htmlspecialchars($teacher['associatenumber']); ?>"
                                <?php echo ($teacher['associatenumber'] == $examData['teacher_id_written']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher['associatenumber']) . ' - ' . htmlspecialchars($teacher['fullname']); ?>
                            </option>
                        <?php endforeach; ?>

                        <!-- Add the examiner ID as a placeholder if not in the list and not null/blank -->
                        <?php if (!empty($examData['teacher_id_written']) && !in_array($examData['teacher_id_written'], array_column($teachers, 'associatenumber'))): ?>
                            <option value="<?php echo htmlspecialchars($examData['teacher_id_written']); ?>" selected>
                                <?php echo htmlspecialchars($examData['teacher_id_written'] . ' - ' . ($examData['fullname_written'] ?? 'Unknown')) . ' (Not in the list)'; ?>
                            </option>
                        <?php endif; ?>
                    </select>

                    <!-- Written Notification Checkbox -->
                    <label>
                        <input type="checkbox" name="send_email_written" value="1" <?php echo isset($_POST['send_email_written']) ? 'checked' : ''; ?>>
                        Send Email Notification to Written Examiner
                    </label>
                </div>


                <input type="hidden" name="exam_id_hidden" value="<?php echo $exam_id; ?>">
                <button type="submit" name="update_exam" class="btn btn-primary">Update Exam Details</button>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
</body>

</html>