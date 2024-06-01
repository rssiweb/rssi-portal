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
        <h1 class="mb-4">Create Exam</h1>
        <form id="filterForm" method="post" action="">
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
            <div class="mb-3">
                <label for="exam_type" class="form-label">Exam Type</label>
                <select class="form-select" id="exam_type" name="exam_type" required>
                    <option value="First Term">First Term</option>
                    <option value="Half Yearly">Half Yearly</option>
                    <option value="Annual">Annual</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="academic_year" class="form-label">Academic Year</label>
                <select class="form-select" id="academic_year" name="academic_year" required>
                    <option value="2024-2025">2024-2025</option>
                    <option value="2025-2026">2025-2026</option>
                    <!-- Add more years as needed -->
                </select>
            </div>
            <div class="mb-3">
                <label for="exam_mode" class="form-label">Exam Mode</label>
                <select class="form-select" id="exam_mode" name="exam_mode[]" multiple="multiple" required>
                    <option value="Written" <?php if (isset($_POST['exam_mode']) && in_array('Written', $_POST['exam_mode'])) echo 'selected'; ?>>Written</option>
                    <option value="Viva" <?php if (isset($_POST['exam_mode']) && in_array('Viva', $_POST['exam_mode'])) echo 'selected'; ?>>Viva</option>
                </select>
            </div>
            <div class="mb-3" id="full_marks_written_div" style="display: none;">
                <label for="full_marks_written" class="form-label">Full Marks Written</label>
                <input type="number" class="form-control" id="full_marks_written" name="full_marks_written" value="<?php echo isset($_POST['full_marks_written']) ? htmlspecialchars($_POST['full_marks_written']) : ''; ?>">
            </div>
            <div class="mb-3" id="full_marks_viva_div" style="display: none;">
                <label for="full_marks_viva" class="form-label">Full Marks Viva</label>
                <input type="number" class="form-control" id="full_marks_viva" name="full_marks_viva" value="<?php echo isset($_POST['full_marks_viva']) ? htmlspecialchars($_POST['full_marks_viva']) : ''; ?>">
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $class = isset($_POST['class']) ? $_POST['class'] : [];
            $category = isset($_POST['category']) ? $_POST['category'] : [];
            $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : '';
            $excluded_ids = isset($_POST['excluded_ids']) ? $_POST['excluded_ids'] : '';
            $exam_mode = isset($_POST['exam_mode']) ? $_POST['exam_mode'] : [];

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

            if (count($conditions) > 0) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            $result = pg_query($con, $query);
            $resultArr = pg_fetch_all($result);

            if (!$result) {
                echo "An error occurred.\n";
                exit;
            }
        ?>

            <form action="save_students.php" method="post">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="exam_type" class="form-label">Exam Type</label>
                        <input type="text" class="form-control" id="exam_type" name="exam_type" value="<?php echo htmlspecialchars($_POST['exam_type']); ?>" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="academic_year" class="form-label">Academic Year</label>
                        <input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($_POST['academic_year']); ?>" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="teacher_id" class="form-label">Assigned Teacher's ID</label>
                        <input type="text" class="form-control" id="teacher_id" name="teacher_id">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select class="form-select" id="subject" name="subject">
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
                </div>

                <?php if (in_array('Written', $exam_mode)) : ?>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="full_marks_written" class="form-label">Full Marks Written</label>
                            <input type="number" class="form-control" id="full_marks_written" name="full_marks_written" value="<?php echo isset($_POST['full_marks_written']) ? htmlspecialchars($_POST['full_marks_written']) : ''; ?>">
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (in_array('Viva', $exam_mode)) : ?>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="full_marks_viva" class="form-label">Full Marks Viva</label>
                            <input type="number" class="form-control" id="full_marks_viva" name="full_marks_viva" value="<?php echo isset($_POST['full_marks_viva']) ? htmlspecialchars($_POST['full_marks_viva']) : ''; ?>">
                        </div>
                    </div>
                <?php endif; ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th scope="col">Student ID</th>
                            <th scope="col">Student Name</th>
                            <th scope="col">Category</th>
                            <th scope="col">Class</th>
                            <!-- <?php if (in_array('Viva', $exam_mode)) : ?>
                                <th scope="col">Viva</th>
                            <?php endif; ?>
                            <?php if (in_array('Written', $exam_mode)) : ?>
                                <th scope="col">Written</th>
                            <?php endif; ?> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultArr) : ?>
                            <?php foreach ($resultArr as $student) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['studentname']); ?></td>
                                    <td><?php echo htmlspecialchars($student['category']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class']); ?></td>
                                    <!-- <?php if (in_array('Viva', $exam_mode)) : ?>
                                        <td><input type="text" class="form-control" id="viva_<?php echo htmlspecialchars($student['student_id']); ?>" name="viva_<?php echo htmlspecialchars($student['student_id']); ?>"></td>
                                    <?php endif; ?>
                                    <?php if (in_array('Written', $exam_mode)) : ?>
                                        <td><input type="text" class="form-control" id="written_<?php echo htmlspecialchars($student['student_id']); ?>" name="written_<?php echo htmlspecialchars($student['student_id']); ?>"></td>
                                    <?php endif; ?> -->
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="<?php echo count($exam_mode) + 4; ?>" class="text-center">No active students found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="text-end mt-3 mb-3">
                    <button type="submit" class="btn btn-primary">Create Exam</button>
                </div>
            </form>
        <?php
        }
        ?>
    </div>
    <script>
        $(document).ready(function() {
            $('#class').select2();
            $('#category').select2();
            $('#exam_mode').select2();

            // Show/hide full marks fields based on exam mode
            function toggleFullMarksFields() {
                const examMode = $('#exam_mode').val();
                if (examMode.includes('Written')) {
                    $('#full_marks_written_div').show();
                } else {
                    $('#full_marks_written_div').hide();
                }
                if (examMode.includes('Viva')) {
                    $('#full_marks_viva_div').show();
                } else {
                    $('#full_marks_viva_div').hide();
                }
            }

            // Initial call to set the correct visibility
            toggleFullMarksFields();

            // Event listener for changes in exam mode
            $('#exam_mode').change(toggleFullMarksFields);
        });
    </script>
</body>

</html>