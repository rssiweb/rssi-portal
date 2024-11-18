<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}
validation();

$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if exam_id is provided
    if (isset($_GET['exam_id']) && !empty($_GET['exam_id'])) {
        // Initialize the base query with LEFT JOIN for viva and written teacher
        $query = "SELECT e.exam_id, e.exam_type, e.exam_mode, e.academic_year, e.subject, e.teacher_id_viva, e.teacher_id_written,
                         e.full_marks_written, e.full_marks_viva, e.estatus, emd.id, emd.student_id, emd.viva_marks, emd.written_marks, 
                         s.studentname, s.category, s.class, 
                         viva_teacher.fullname AS fullname_viva,
                         written_teacher.fullname AS fullname_written
                  FROM exams e
                  JOIN exam_marks_data emd ON e.exam_id = emd.exam_id
                  JOIN rssimyprofile_student s ON emd.student_id = s.student_id
                  LEFT JOIN rssimyaccount_members viva_teacher ON e.teacher_id_viva = viva_teacher.associatenumber
                  LEFT JOIN rssimyaccount_members written_teacher ON e.teacher_id_written = written_teacher.associatenumber
                  WHERE e.exam_id = $1";

        // Initialize parameters array with exam_id
        $params = [$_GET['exam_id']];

        // Check if the user role is not Admin or Offline Manager
        if ($role !== 'Admin' && $role !== 'Offline Manager') {
            // Add condition to limit data to the teacher's records
            $query .= " AND (e.teacher_id_viva = $2 OR e.teacher_id_written = $2)";
            $params[] = $associatenumber; // For both viva and written teacher check
        }

        // Add ORDER BY clause
        $query .= " ORDER BY emd.id"; // Change this column if needed

        // Execute the query
        $result = pg_query_params($con, $query, $params);

        if (!$result) {
            die("Error in SQL query: " . pg_last_error());
        }

        // Fetch and store results
        $results = [];
        while ($row = pg_fetch_assoc($result)) {
            // Get the value of estatus from each row
            $estatus = $row['estatus'];
            $results[] = $row;
        }

        pg_free_result($result);

        // Fetch the last updated details
        $exam_id = $_GET['exam_id'];
        $lastupdatedon = pg_query($con, "SELECT DISTINCT ON (exam_id, update_timestamp) exam_id, update_timestamp, updated_by 
                                         FROM exam_update_history 
                                         WHERE exam_id = '$exam_id' 
                                         ORDER BY exam_id, update_timestamp DESC, updated_by;");
        @$update_timestamp = pg_fetch_result($lastupdatedon, 0, 1);
        @$updated_by = pg_fetch_result($lastupdatedon, 0, 2);
    }
}

?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Arrays to hold SQL cases and parameters
    $written_marks_cases = [];
    $viva_marks_cases = [];
    $written_params = [];
    $viva_params = [];
    $written_param_index = 1;
    $viva_param_index = 1;
    $history_entries = [];

    // Prepare the update cases for written marks
    if (isset($_POST['written_marks']) && !empty($_POST['written_marks'])) {
        foreach ($_POST['written_marks'] as $key => $written_mark) {
            if (is_numeric($written_mark)) {
                list($exam_id, $student_id) = explode('_', $key);

                // Fetch current written marks
                $current_written_marks_query = "SELECT written_marks FROM exam_marks_data WHERE exam_id = $1 AND student_id = $2";
                $current_written_marks_result = pg_query_params($con, $current_written_marks_query, [$exam_id, $student_id]);
                $current_written_mark = pg_fetch_result($current_written_marks_result, 0, 0);

                if ($current_written_mark != $written_mark) {
                    $written_marks_cases[] = "WHEN exam_id = $" . $written_param_index . " AND student_id = $" . ($written_param_index + 1) . " THEN $" . ($written_param_index + 2);
                    $written_params[] = $exam_id;
                    $written_params[] = $student_id;
                    $written_params[] = $written_mark;
                    $written_param_index += 3;

                    // Prepare history entry
                    $history_entries[] = [$exam_id, $student_id, 'written', $written_mark, $associatenumber];
                }
            }
        }
    }

    // Prepare the update cases for viva marks
    if (isset($_POST['viva_marks']) && !empty($_POST['viva_marks'])) {
        foreach ($_POST['viva_marks'] as $key => $viva_mark) {
            if (is_numeric($viva_mark)) {
                list($exam_id, $student_id) = explode('_', $key);

                // Fetch current viva marks
                $current_viva_marks_query = "SELECT viva_marks FROM exam_marks_data WHERE exam_id = $1 AND student_id = $2";
                $current_viva_marks_result = pg_query_params($con, $current_viva_marks_query, [$exam_id, $student_id]);
                $current_viva_mark = pg_fetch_result($current_viva_marks_result, 0, 0);

                if ($current_viva_mark != $viva_mark) {
                    $viva_marks_cases[] = "WHEN exam_id = $" . $viva_param_index . " AND student_id = $" . ($viva_param_index + 1) . " THEN $" . ($viva_param_index + 2);
                    $viva_params[] = $exam_id;
                    $viva_params[] = $student_id;
                    $viva_params[] = $viva_mark;
                    $viva_param_index += 3;

                    // Prepare history entry
                    $history_entries[] = [$exam_id, $student_id, 'viva', $viva_mark, $associatenumber];
                }
            }
        }
    }

    // Build and execute the written marks update query
    if (!empty($written_marks_cases)) {
        $written_marks_query = "UPDATE exam_marks_data SET written_marks = CASE " . implode(' ', $written_marks_cases) . " ELSE written_marks END WHERE (exam_id, student_id) IN (";
        for ($i = 0; $i < count($written_params); $i += 3) {
            $written_marks_query .= "($" . ($i + 1) . ", $" . ($i + 2) . "),";
        }
        $written_marks_query = rtrim($written_marks_query, ',') . ")";

        $result = pg_query_params($con, $written_marks_query, $written_params);
        if (!$result) {
            die("Error in SQL query (Written Marks): " . pg_last_error($con));
        }
    }

    // Build and execute the viva marks update query
    if (!empty($viva_marks_cases)) {
        $viva_marks_query = "UPDATE exam_marks_data SET viva_marks = CASE " . implode(' ', $viva_marks_cases) . " ELSE viva_marks END WHERE (exam_id, student_id) IN (";
        for ($i = 0; $i < count($viva_params); $i += 3) {
            $viva_marks_query .= "($" . ($i + 1) . ", $" . ($i + 2) . "),";
        }
        $viva_marks_query = rtrim($viva_marks_query, ',') . ")";

        $result = pg_query_params($con, $viva_marks_query, $viva_params);
        if (!$result) {
            die("Error in SQL query (Viva Marks): " . pg_last_error($con));
        }
    }

    // Insert update history
    if (!empty($history_entries)) {
        $update_history_query = "INSERT INTO exam_update_history (exam_id, student_id, mode_of_exam, marks, updated_by) VALUES ";
        $history_params = [];
        $history_index = 1;

        foreach ($history_entries as $entry) {
            list($exam_id, $student_id, $mode_of_exam, $marks, $updated_by) = $entry;
            $update_history_query .= "($" . $history_index . ", $" . ($history_index + 1) . ", $" . ($history_index + 2) . ", $" . ($history_index + 3) . ", $" . ($history_index + 4) . "),";
            $history_params[] = $exam_id;
            $history_params[] = $student_id;
            $history_params[] = $mode_of_exam;
            $history_params[] = $marks;
            $history_params[] = $updated_by;
            $history_index += 5;
        }

        // Remove trailing comma and execute the query
        $update_history_query = rtrim($update_history_query, ',');
        $result = pg_query_params($con, $update_history_query, $history_params);
        if (!$result) {
            die("Error in SQL query (Update History): " . pg_last_error($con));
        }
    }

    // Close the database connection
    pg_close($con);

    // Output JavaScript to show alert and redirect
    echo "<script>
            alert('Data has been successfully updated.');
            window.location.href = 'exam_marks_upload.php?exam_id=" . urlencode($exam_id) . "';
        </script>";
    exit();
}
?>
<!doctype html>
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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Upload Marks</title>

    <!-- Favicons -->
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

    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Upload Marks</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Academic</li>
                    <li class="breadcrumb-item"><a href="exam_allotment.php">Exam Allotment</a></li>
                    <li class="breadcrumb-item active">Upload Marks</li>
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
                                <form id="filter_form" method="GET" action="">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-3">
                                            <label for="exam_id" class="form-label">Exam ID</label>
                                            <input type="text" class="form-control" id="exam_id" name="exam_id" value="<?php echo isset($_GET['exam_id']) ? htmlspecialchars($_GET['exam_id']) : ''; ?>">
                                        </div>
                                        <div class="col-md-3 align-self-end">
                                            <button type="submit" name="search_by_id" class="btn btn-primary btn-sm">
                                                <i class="bi bi-search"></i>&nbsp;Search
                                            </button>
                                        </div>
                                    </div>
                                </form>


                                <?php if (!empty($results)) :
                                    $exam_mode = $results[0]['exam_mode']; // Assuming exam_type is the same for all rows
                                ?>
                                    <?php $serialNumber = 1; // Initialize serial number 
                                    ?>

                                    <?php if (!empty($results)) : ?>
                                        <?php
                                        $unique_exams = [];
                                        foreach ($results as $row) {
                                            $key = $row['exam_id'] . $row['exam_type'] . $row['academic_year'] . $row['subject'] . $row['teacher_id_viva'] . $row['fullname_viva'] . $row['fullname_written'] . $row['full_marks_written'] . $row['full_marks_viva'] . $row['exam_mode'];
                                            $unique_exams[$key] = $row;
                                        }
                                        ?>
                                        <?php foreach ($unique_exams as $unique_exam) : ?>
                                            <div class="container mt-5">
                                                <div class="divider"></div>
                                                <div class="row exam-info">
                                                    <div class="col-md-6"><strong>Exam ID:</strong> <?php echo $unique_exam['exam_id']; ?></div>
                                                    <div class="col-md-6"><strong>Exam Type:</strong> <?php echo $unique_exam['exam_type']; ?></div>
                                                </div>

                                                <div class="row exam-info">
                                                    <div class="col-md-6"><strong>Academic Year: </strong><?php echo $unique_exam['academic_year']; ?></div>
                                                    <div class="col-md-6"><strong>Subject:</strong> <?php echo $unique_exam['subject']; ?></div>

                                                </div>

                                                <div class="row exam-info">
                                                    <!-- <div class="col-md-6"><strong>Teacher ID:</strong> <?php echo $unique_exam['teacher_id_viva']; ?>-<?php echo $unique_exam['fullname_viva']; ?></div> -->
                                                    <div class="col-md-6">
                                                        <strong>Examiner for Viva:</strong>
                                                        <?php
                                                        if (!empty($unique_exam['teacher_id_viva'])) {
                                                            echo $unique_exam['teacher_id_viva'] . " - " . $unique_exam['fullname_viva'];
                                                        } else {
                                                            echo "Not Assigned";
                                                        }
                                                        ?>
                                                    </div>

                                                    <div class="col-md-6"><strong>Exam mode:</strong>
                                                        <?php
                                                        if ($row['full_marks_written'] !== null) {
                                                            echo 'W-' . htmlspecialchars($row['full_marks_written']);
                                                        }
                                                        if ($row['full_marks_viva'] !== null) {
                                                            echo ' V-' . htmlspecialchars($row['full_marks_viva']);
                                                        }
                                                        ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Examiner for Written:</strong>
                                                        <?php
                                                        if (!empty($unique_exam['teacher_id_written'])) {
                                                            echo $unique_exam['teacher_id_written'] . " - " . $unique_exam['fullname_written'];
                                                        } else {
                                                            echo "Not Assigned";
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <p class="text-center">No exam information found.</p>
                                    <?php endif; ?>
                                    <div class="divider"></div>
                                    <form method="POST" action="exam_marks_upload.php">
                                        <fieldset <?php echo $estatus; ?>>
                                            <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($_GET['exam_id']); ?>">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Student ID</th>
                                                            <th>Student Name</th>
                                                            <th>Category</th>
                                                            <th>Class</th>
                                                            <?php if ($exam_mode === '{Written}' || $exam_mode === '{Written,Viva}' || $exam_mode === '{Viva,Written}') : ?>
                                                                <th>Written Marks</th>
                                                            <?php endif; ?>
                                                            <?php if ($exam_mode === '{Viva}' || $exam_mode === '{Written,Viva}' || $exam_mode === '{Viva,Written}') : ?>
                                                                <th>Viva Marks</th>
                                                            <?php endif; ?>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($results as $row) : ?>
                                                            <tr>
                                                                <td><?= $serialNumber++ ?></td> <!-- Display and increment serial number -->
                                                                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                                                <td><?php echo htmlspecialchars($row['studentname']); ?></td>
                                                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                                                <td><?php echo htmlspecialchars($row['class']); ?></td>
                                                                <?php if ($exam_mode === '{Written}' || $exam_mode === '{Written,Viva}' || $exam_mode === '{Viva,Written}') : ?>
                                                                    <td>
                                                                        <input type="number" step="0.01" max="<?php echo $row['full_marks_written'] ?>" name="written_marks[<?php echo htmlspecialchars($row['exam_id'] . '_' . $row['student_id']); ?>]" value="<?php echo htmlspecialchars($row['written_marks']); ?>" class="form-control">
                                                                    </td>
                                                                <?php endif; ?>
                                                                <?php if ($exam_mode === '{Viva}' || $exam_mode === '{Written,Viva}' || $exam_mode === '{Viva,Written}') : ?>
                                                                    <td>
                                                                        <input type="number" step="0.01" max="<?php echo $row['full_marks_viva'] ?>" name="viva_marks[<?php echo htmlspecialchars($row['exam_id'] . '_' . $row['student_id']); ?>]" value="<?php echo htmlspecialchars($row['viva_marks']); ?>" class="form-control">
                                                                    </td>
                                                                <?php endif; ?>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <!-- <button type="submit" id="save" class="btn btn-success">Save</button> -->
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <button type="submit" id="submit" class="btn btn-primary">Submit</button>
                                                <div>
                                                    Last Updated by:
                                                    <?php echo $updated_by ? $updated_by : "Not available"; ?>
                                                    <?php if (!empty($update_timestamp)) : ?>
                                                        on <?php echo date('d/m/Y h:i:s A', strtotime($update_timestamp)); ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                        </fieldset>
                                    </form>
                                <?php elseif (empty($_GET['exam_id'])) : ?>
                                    <p class="mt-4">Please provide an exam ID to fetch data.</p>
                                <?php else : ?>
                                    <p class="mt-4">No records match your selected filters or you are not authorized to access this exam ID. Please try adjusting your filters or contact your instructor or administrator.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div><!-- End Reports -->
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById("filter_form"); // Assuming the form has id="exam"
            const fields = form.querySelectorAll("[required], input[type='number'][name^='full_marks']");

            fields.forEach(field => {
                const label = form.querySelector(`label[for="${field.id}"]`);
                if (label) {
                    const asterisk = document.createElement('span');
                    asterisk.textContent = '*';
                    asterisk.style.color = 'red';
                    asterisk.style.marginLeft = '5px';
                    label.appendChild(asterisk);
                }
            });
        });
    </script>
</body>

</html>