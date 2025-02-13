<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/email.php");
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
    $teacher_id_viva = $_POST['teacher_id_viva'];
    $teacher_id_written = $_POST['teacher_id_written'];
    $subject = $_POST['subject'];
    $exam_mode = $_POST['exam_mode'];
    $full_marks_written = $_POST['full_marks_written'];
    $exam_date_written = $_POST['exam_date_written'];
    $full_marks_viva = $_POST['full_marks_viva'];
    $exam_date_viva = $_POST['exam_date_viva'];
    $exam_id = uniqid();

    $exam_mode_pg_array = '{' . implode(',', $exam_mode) . '}';

    // Prepare the SQL query
    $exam_sql = "INSERT INTO exams (exam_type, academic_year, teacher_id_viva, teacher_id_written, subject, exam_mode, full_marks_written, full_marks_viva, exam_id, exam_date_written, exam_date_viva)
             VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10,$11)";

    // Bind parameters for the prepared statement
    $params = array($exam_type, $academic_year, $teacher_id_viva, $teacher_id_written, $subject, $exam_mode_pg_array);

    // Check if full_marks_written is not null, then set the parameter value
    if ($full_marks_written !== null && $full_marks_written !== "") {
        $params[] = $full_marks_written;
    } else {
        $params[] = null; // Add null placeholder
    }

    // Check if full_marks_viva is not null, then set the parameter value
    if ($full_marks_viva !== null && $full_marks_viva !== "") {
        $params[] = $full_marks_viva;
    } else {
        $params[] = null; // Add null placeholder
    }

    // Add the exam_id parameter
    $params[] = $exam_id;
    // Add the exam_date_written parameter
    if ($exam_date_written !== null && $exam_date_written !== "") {
        $params[] = $exam_date_written;
    } else {
        $params[] = null; // Add null placeholder
    }

    // Add the exam_date_viva parameter
    if ($exam_date_viva !== null && $exam_date_viva !== "") {
        $params[] = $exam_date_viva;
    } else {
        $params[] = null; // Add null placeholder
    }

    // Execute the query with parameters
    $exam_result = pg_query_params($con, $exam_sql, $params);


    // Insert fetched data into exam_marks_data table
    foreach ($resultArr as $row) {
        $student_id = $row['student_id'];
        $studentname = $row['studentname'];
        $category = $row['category'];
        $class = $row['class'];

        // Assuming exam_marks_data table has columns student_id, studentname, category, class
        $insertQuery = "INSERT INTO exam_marks_data (exam_id,student_id, category, class) VALUES ('$exam_id','$student_id',  '$category', '$class')";
        $insertResult = pg_query($con, $insertQuery);
        $cmdtuples = pg_affected_rows($insertResult);

        if ($exam_result && $insertResult) {
            $successMessages[] = "Data for $studentname inserted successfully"; // Store success message
        } else {
            echo "Error inserting data into exam_marks_data.\n";
            exit;
        }
    }
    // Fetch details of both examiners in one query
    $examiner_ids = [$teacher_id_viva, $teacher_id_written];
    $examiner_data_query = pg_query($con, "SELECT associatenumber, phone, email, fullname 
                                       FROM rssimyaccount_members 
                                       WHERE associatenumber IN ('" . implode("','", $examiner_ids) . "')");

    $examiners = [];
    while ($row = pg_fetch_assoc($examiner_data_query)) {
        $examiners[$row['associatenumber']] = $row;
    }

    // Assign details for Viva and Written examiners
    $examiner_contact = $examiners[$teacher_id_viva]['phone'] ?? null;
    $examiner_email = $examiners[$teacher_id_viva]['email'] ?? null;
    $examiner_name = $examiners[$teacher_id_viva]['fullname'] ?? null;

    $examiner_contact_written = $examiners[$teacher_id_written]['phone'] ?? null;
    $examiner_email_written = $examiners[$teacher_id_written]['email'] ?? null;
    $examiner_name_written = $examiners[$teacher_id_written]['fullname'] ?? null;

    // Determine the exam mode
    if ($examiner_email_written == $examiner_email) {
        $exam_mode = $exam_mode_pg_array;
    } else {
        $exam_mode_viva = "Viva";
        $exam_mode_written = "Written";
    }

    // Send email for viva examiner
    if ($cmdtuples == 1 && !empty($examiner_email)) {
        sendEmail("exam_create", [
            "exam_id" => $exam_id,
            "exam_type" => $exam_type,
            "academic_year" => $academic_year,
            "subject" => $subject,
            "class" => $class,
            "exam_mode" => ($examiner_email_written == $examiner_email) ? $exam_mode_pg_array : $exam_mode_viva,
            "full_marks_written" => $full_marks_written,
            "full_marks_viva" => $full_marks_viva,
            "examiner_name" => $examiner_name,
        ], $examiner_email);
    }

    // Send email for written examiner
    if ($cmdtuples == 1 && !empty($examiner_email_written) && ($examiner_email_written != $examiner_email)) {
        sendEmail("exam_create", [
            "exam_id" => $exam_id,
            "exam_type" => $exam_type,
            "academic_year" => $academic_year,
            "subject" => $subject,
            "class" => $class,
            "exam_mode" => $exam_mode_written,
            "full_marks_written" => $full_marks_written,
            "full_marks_viva" => $full_marks_viva,
            "examiner_name" => $examiner_name_written,
        ], $examiner_email_written);
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
// Assuming you have fetched both 'associatenumber' and 'fullname' columns from the database

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

    <title>Create Exam</title>

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
</head>

<body>

    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Create Exam</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Academic</li>
                    <li class="breadcrumb-item active">Create Exam</li>
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
                                <h1 class="mb-4">Creating Exams for</h1>
                                <div class="table-responsive">
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
                                </div>
                                <?php if (isset($resultArr) && !empty($resultArr)) : ?>
                                    <h1 class="mb-4">Exam Parameters</h1>
                                    <form action="exam_create.php" name="exam" id="exam" method="post">
                                        <input type="hidden" name="form-type" type="text" value="exam">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="exam_type" class="form-label">Exam Type</label>
                                                <select class="form-select" id="exam_type" name="exam_type" required>
                                                    <option disabled selected>Select Exam Type</option>
                                                    <option value="First Term">First Term</option>
                                                    <option value="Half Yearly">Half Yearly</option>
                                                    <option value="Annual">Annual</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="academic_year" class="form-label">Academic Year</label>
                                                <select class="form-select" id="academic_year" name="academic_year" required>
                                                </select>
                                            </div>
                                            <!-- <div class="col-md-4 mb-3">
                                                <label for="teacher_id_viva">Select Teacher's ID:</label>
                                                <select class="form-select" id="teacher_id_viva" name="teacher_id_viva" required>
                                                    <option disabled selected hidden>Select Teacher's ID</option>
                                                    <?php foreach ($teachers as $teacher) { ?>
                                                        <option value="<?php echo $teacher['associatenumber']; ?>"><?php echo $teacher['associatenumber'] . ' - ' . $teacher['fullname']; ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div> -->
                                            <!-- </div>

                                        <div class="row"> -->
                                            <div class="col-md-4 mb-3">
                                                <label for="subject" class="form-label">Subject</label>
                                                <select class="form-select" id="subject" name="subject" required>
                                                    <option disabled selected>Select a subject</option>
                                                    <option value="Hindi">Hindi</option>
                                                    <option value="English">English</option>
                                                    <option value="Mathematics">Mathematics</option>
                                                    <option value="GK">GK</option>
                                                    <option value="Hamara Parivesh">Hamara Parivesh</option>
                                                    <option value="Computer">Computer</option>
                                                    <option value="Art & Craft">Art & Craft</option>
                                                    <option value="Sulekh+Imla">Sulekh+Imla</option>
                                                    <option value="Project">Project</option>
                                                </select>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label for="exam_mode" class="form-label">Exam Mode</label>
                                                    <select class="form-select" id="exam_mode" name="exam_mode[]" multiple required>
                                                        <option value="Written">Written</option>
                                                        <option value="Viva">Viva</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4 mb-3" id="written_marks_wrapper" style="display: none;">
                                                    <label for="full_marks_written" class="form-label">Full Marks Written</label>
                                                    <input type="number" class="form-control" id="full_marks_written" name="full_marks_written">
                                                    <label for="exam_date_written" class="form-label">Written Exam Date</label>
                                                    <input type="date" class="form-control" id="exam_date_written" name="exam_date_written">
                                                    <label for="teacher_id_written">Assigned Written Teacher ID:</label>
                                                    <select class="form-select" id="teacher_id_written" name="teacher_id_written">
                                                        <option disabled selected hidden>Select Teacher's ID</option>
                                                        <?php foreach ($teachers as $teacher) { ?>
                                                            <option value="<?php echo $teacher['associatenumber']; ?>"><?php echo $teacher['associatenumber'] . ' - ' . $teacher['fullname']; ?></option>
                                                        <?php } ?>
                                                    </select>

                                                </div>
                                                <div class="col-md-4 mb-3" id="viva_marks_wrapper" style="display: none;">
                                                    <label for="full_marks_viva" class="form-label">Full Marks Viva</label>
                                                    <input type="number" class="form-control" id="full_marks_viva" name="full_marks_viva">
                                                    <label for="exam_date_viva" class="form-label">Viva Exam Date</label>
                                                    <input type="date" class="form-control" id="exam_date_viva" name="exam_date_viva">
                                                    <label for="teacher_id_viva">Assigned Viva Teacher ID:</label>
                                                    <select class="form-select" id="teacher_id_viva" name="teacher_id_viva">
                                                        <option disabled selected hidden>Select Teacher's ID</option>
                                                        <?php foreach ($teachers as $teacher) { ?>
                                                            <option value="<?php echo $teacher['associatenumber']; ?>"><?php echo $teacher['associatenumber'] . ' - ' . $teacher['fullname']; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="text-end mt-3 mb-3">
                                                <button type="submit" class="btn btn-primary">Create Exam</button>
                                            </div>
                                    </form>
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
            // PHP logic to determine the current year
            <?php
            if (date('m') == 1 || date('m') == 2 || date('m') == 3) {
                $currentYear = date('Y') - 1;
            } else {
                $currentYear = date('Y');
            }
            ?>
            var currentYear = <?php echo $currentYear; ?>;
            var selectedYear = currentYear + '-' + (currentYear + 1);

            // JavaScript to populate the academic years
            for (var i = 0; i < 5; i++) {
                var next = currentYear + 1;
                var year = currentYear + '-' + next;
                var option = new Option(year, year, false, year === selectedYear);
                document.getElementById('academic_year').appendChild(option);
                currentYear--;
            }

        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById("exam"); // Assuming the form has id="exam"
            const examModeSelect = document.getElementById("exam_mode");

            // Function to add asterisks to required fields
            function addAsterisksToRequiredFields() {
                const fields = form.querySelectorAll("[required]");

                fields.forEach(field => {
                    const label = form.querySelector(`label[for="${field.id}"]`);
                    if (label) {
                        // Create and append the asterisk if not already present
                        if (!label.querySelector('span.asterisk')) {
                            const asterisk = document.createElement('span');
                            asterisk.textContent = '*';
                            asterisk.style.color = 'red';
                            asterisk.style.marginLeft = '5px';
                            asterisk.classList.add('asterisk');
                            label.appendChild(asterisk);
                        }
                    }
                });
            }

            // Function to show/hide marks inputs based on selected exam mode
            function updateMarksInputs() {
                const writtenMarksWrapper = document.getElementById("written_marks_wrapper");
                const writtenMarksInput = document.getElementById("full_marks_written");
                const writtenDateInput = document.getElementById("exam_date_written");
                const vivaMarksWrapper = document.getElementById("viva_marks_wrapper");
                const vivaMarksInput = document.getElementById("full_marks_viva");
                const vivaDateInput = document.getElementById("exam_date_viva");

                const selectedOptions = Array.from(examModeSelect.selectedOptions).map(option => option.value);
                writtenMarksWrapper.style.display = selectedOptions.includes("Written") ? "block" : "none";
                writtenMarksInput.required = selectedOptions.includes("Written");
                writtenDateInput.required = selectedOptions.includes("Written");
                vivaMarksWrapper.style.display = selectedOptions.includes("Viva") ? "block" : "none";
                vivaMarksInput.required = selectedOptions.includes("Viva");
                vivaDateInput.required = selectedOptions.includes("Viva");

                // After updating the required attributes, re-run the function to add asterisks
                addAsterisksToRequiredFields();
            }

            // Call updateMarksInputs on page load
            updateMarksInputs();

            // Add event listener to exam mode select to update marks inputs when selection changes
            examModeSelect.addEventListener("change", updateMarksInputs);

            // Initial asterisk addition for required fields on page load
            addAsterisksToRequiredFields();
        });
    </script>
</body>

</html>