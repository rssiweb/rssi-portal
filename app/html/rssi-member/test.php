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

if (@$_POST['form-type'] == "exam_filter") {
    $class = isset($_POST['class']) ? $_POST['class'] : [];
    $category = isset($_POST['category']) ? $_POST['category'] : [];
    $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : '';
    $excluded_ids = isset($_POST['excluded_ids']) ? $_POST['excluded_ids'] : '';

    $query = "SELECT student_id, studentname, category, class FROM rssimyprofile_student WHERE filterstatus='Active'";
    $conditions = [];

    if (!empty($class)) {
        $class_list = implode("','", $class);
        $conditions[] = "class IN ('$class_list')";
    }
    if (!empty($category)) {
        $category_list = implode("','", $category);
        $conditions[] = "category IN ('$category_list')";
    }
    if (!empty($student_ids)) {
        $student_ids_list = implode("','", array_map('trim', explode(',', $student_ids)));
        $conditions[] = "student_id IN ('$student_ids_list')";
    }
    if (!empty($excluded_ids)) {
        $excluded_ids_list = implode("','", array_map('trim', explode(',', $excluded_ids)));
        $conditions[] = "student_id NOT IN ('$excluded_ids_list')";
    }

    // Check if any filter condition is applied
    if (count($conditions) > 0) {
        $query .= " AND " . implode(" AND ", $conditions);
    } else {
        // If no filter condition applied, set $resultArr to null
        $resultArr = null;
    }

    $result = pg_query($con, $query);

    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    // Fetch results only if there are filter conditions applied
    if (count($conditions) > 0) {
        $resultArr = pg_fetch_all($result);
        $_SESSION['filtered_results'] = $resultArr; // Store filtered results in session
    }
}
if (@$_POST['form-type'] == "exam") {

    $successMessages = []; // Array to store success messages

    if (isset($_SESSION['filtered_results'])) {
        $resultArr = $_SESSION['filtered_results'];
    } else {
        // Handle case where filtered results are not available
        echo "Filtered results not available. Please apply filters first.\n";
        exit;
    }
    $exam_type = $_POST['exam_type'];
    $academic_year = $_POST['academic_year'];
    $teacher_id = $_POST['teacher_id'];
    $subject = $_POST['subject'];
    $exam_mode = $_POST['exam_mode'];
    $full_marks_written = $_POST['full_marks_written'];
    $full_marks_viva = $_POST['full_marks_viva'];
    $exam_id = uniqid();

    $exam_mode_pg_array = '{' . implode(',', $exam_mode) . '}';

    $exam_sql = "INSERT INTO exams (exam_type, academic_year, teacher_id, subject, exam_mode, full_marks_written, full_marks_viva, exam_id)
                    VALUES ($1, $2, $3, $4, $5, $6, $7, $8)";

    $exam_result = pg_query_params($con, $exam_sql, array($exam_type, $academic_year, $teacher_id, $subject, $exam_mode_pg_array, $full_marks_written, $full_marks_viva, $exam_id));

    // Insert fetched data into exam_marks_data table
    foreach ($resultArr as $row) {
        $student_id = $row['student_id'];
        $studentname = $row['studentname'];
        $category = $row['category'];
        $class = $row['class'];

        // Assuming exam_marks_data table has columns student_id, studentname, category, class
        $insertQuery = "INSERT INTO exam_marks_data (exam_id,student_id, category, class) VALUES ('$exam_id','$student_id',  '$category', '$class')";
        $insertResult = pg_query($con, $insertQuery);

        if ($exam_result && $insertResult) {
            $successMessages[] = "Data for $studentname inserted successfully"; // Store success message
        } else {
            echo "Error inserting data into exam_marks_data.\n";
            exit;
        }
    }

    // Output JavaScript alert message after all insertions are completed
    echo "<script>
            alert('" . implode("\\n", $successMessages) . "');
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            // window.location.reload();
            </script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <style>
        .select2-container {
            width: 100% !important;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Select Student</h1>
        <form id="filterForm" method="post" action="">
            <input type="hidden" name="form-type" type="text" value="exam_filter">
            <div class="mb-3">
                <label for="class" class="form-label">Class</label>
                <select class="form-select" id="class" name="class[]" multiple="multiple">
                    <option value="Nursery" <?php if (isset($_POST['class']) && in_array('Nursery', $_POST['class'])) echo 'selected'; ?>>Nursery</option>
                    <option value="LKG" <?php if (isset($_POST['class']) && in_array('LKG', $_POST['class'])) echo 'selected'; ?>>LKG</option>
                    <option value="1" <?php if (isset($_POST['class']) && in_array('1', $_POST['class'])) echo 'selected'; ?>>1</option>
                    <option value="2" <?php if (isset($_POST['class']) && in_array('2', $_POST['class'])) echo 'selected'; ?>>2</option>
                    <option value="3" <?php if (isset($_POST['class']) && in_array('3', $_POST['class'])) echo 'selected'; ?>>3</option>
                    <option value="4" <?php if (isset($_POST['class']) && in_array('4', $_POST['class'])) echo 'selected'; ?>>4</option>
                    <option value="5" <?php if (isset($_POST['class']) && in_array('5', $_POST['class'])) echo 'selected'; ?>>5</option>
                    <option value="6" <?php if (isset($_POST['class']) && in_array('6', $_POST['class'])) echo 'selected'; ?>>6</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category[]" multiple="multiple">
                    <option value="LG1" <?php if (isset($_POST['category']) && in_array('LG1', $_POST['category'])) echo 'selected'; ?>>LG1</option>
                    <option value="LG2-A" <?php if (isset($_POST['category']) && in_array('LG2-A', $_POST['category'])) echo 'selected'; ?>>LG2-A</option>
                    <option value="LG2-B" <?php if (isset($_POST['category']) && in_array('LG2-B', $_POST['category'])) echo 'selected'; ?>>LG2-B</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="student_ids" class="form-label">Student IDs (comma separated)</label>
                <input type="text" class="form-control" id="student_ids" name="student_ids" value="<?php echo isset($_POST['student_ids']) ? htmlspecialchars($_POST['student_ids']) : ''; ?>">
            </div>
            <div class="mb-3">
                <label for="excluded_ids" class="form-label">Excluded IDs (comma separated)</label>
                <input type="text" class="form-control" id="excluded_ids" name="excluded_ids" value="<?php echo isset($_POST['excluded_ids']) ? htmlspecialchars($_POST['excluded_ids']) : ''; ?>">
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary mb-3">Filter</button>
            </div>
        </form>
        <h1 class="mb-4">Exam creating for</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th scope="col">Student ID</th>
                    <th scope="col">Student Name</th>
                    <th scope="col">Category</th>
                    <th scope="col">Class</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($resultArr) && !empty($resultArr)) : ?>
                    <?php foreach ($resultArr as $student) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($student['studentname']); ?></td>
                            <td><?php echo htmlspecialchars($student['category']); ?></td>
                            <td><?php echo htmlspecialchars($student['class']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td class="text-center" colspan="4">No active students found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if (isset($resultArr) && !empty($resultArr)) : ?>
            <h1 class="mb-4">Exam Parameter</h1>
            <form action="test.php" name="exam" id="exam" method="post">
                <input type="hidden" name="form-type" type="text" value="exam">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="exam_type" class="form-label">Exam Type</label>
                        <select class="form-select" id="exam_type" name="exam_type" required>
                            <option value="First Term">First Term</option>
                            <option value="Half Yearly">Half Yearly</option>
                            <option value="Annual">Annual</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="academic_year" class="form-label">Academic Year</label>
                        <select class="form-select" id="academic_year" name="academic_year" required>
                            <option value="2024-2025">2024-2025</option>
                            <option value="2025-2026">2025-2026</option>
                            <!-- Add more years as needed -->
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="teacher_id" class="form-label">Assigned Teacher's ID</label>
                        <input type="text" class="form-control" id="teacher_id" name="teacher_id" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select class="form-select" id="subject" name="subject" required>
                            <option value="" disabled selected>Select a subject</option>
                            <option value="Hindi">Hindi</option>
                            <option value="English">English</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="GK">GK</option>
                            <option value="Humara Parivesh">Humara Parivesh</option>
                            <option value="Arts & Craft">Arts & Craft</option>
                            <option value="Sulekh+Imla">Sulekh+Imla</option>
                            <option value="Project">Project</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="exam_mode" class="form-label">Exam Mode</label>
                        <select class="form-select" id="exam_mode" name="exam_mode[]" multiple required>
                            <option value="Written">Written</option>
                            <option value="Viva">Viva</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="full_marks_written" class="form-label">Full Marks Written</label>
                        <input type="number" class="form-control" id="full_marks_written" name="full_marks_written" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="full_marks_viva" class="form-label">Full Marks Viva</label>
                        <input type="number" class="form-control" id="full_marks_viva" name="full_marks_viva" required>
                    </div>
                </div>
                <div class="text-end mt-3 mb-3">
                    <button type="submit" class="btn btn-primary">Create Exam</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>