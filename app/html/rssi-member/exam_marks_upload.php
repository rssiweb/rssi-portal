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

$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if exam type, subject are selected, and if either class or category is selected
    if (isset($_GET['exam_type']) && !empty($_GET['exam_type']) && isset($_GET['subject']) && !empty($_GET['subject']) && (isset($_GET['class']) || isset($_GET['category']))) {
        // Initialize query with base SELECT statement
        $query = "SELECT e.exam_id, e.exam_type, e.academic_year, e.subject, e.full_marks_written, e.full_marks_viva, emd.student_id, emd.viva_marks, emd.written_marks, s.studentname as studentname, s.category as category, s.class as class
                  FROM exams e
                  JOIN exam_marks_data emd ON e.exam_id = emd.exam_id
                  JOIN rssimyprofile_student s ON emd.student_id = s.student_id";

        // Initialize parameters array
        $params = [];

        // Initialize conditions array
        $conditions = [];

        // Check if exam_id is available
        if (isset($_GET['exam_id']) && !empty($_GET['exam_id'])) {
            $exam_id = $_GET['exam_id'];
            $conditions[] = "e.exam_id = $1";
            $params[] = $exam_id;
        }

        // Dynamically add filters based on the other GET parameters
        if (isset($_GET['exam_type']) && !empty($_GET['exam_type'])) {
            $exam_type = $_GET['exam_type'];
            $conditions[] = "e.exam_type = $" . (count($params) + 1);
            $params[] = $exam_type;
        }

        if (isset($_GET['academic_year']) && !empty($_GET['academic_year'])) {
            $academic_year = $_GET['academic_year'];
            $conditions[] = "e.academic_year = $" . (count($params) + 1);
            $params[] = $academic_year;
        }

        if (isset($_GET['subject']) && !empty($_GET['subject'])) {
            $subject = $_GET['subject'];
            $conditions[] = "e.subject = $" . (count($params) + 1);
            $params[] = $subject;
        }

        // Check if class is selected
        if (isset($_GET['class']) && !empty($_GET['class'])) {
            $class = $_GET['class'];
            // Add condition for class
            $conditions[] = "s.class IN (" . implode(',', array_fill(0, count($class), '$' . (count($params) + 1))) . ")";
            // Add parameters for class
            $params = array_merge($params, $class);
        }

        // Check if category is selected
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $category = $_GET['category'];
            // Add condition for category
            $conditions[] = "s.category IN (" . implode(',', array_fill(0, count($category), '$' . (count($params) + 1))) . ")";
            // Add parameters for category
            $params = array_merge($params, $category);
        }

        // Add WHERE clause if conditions are available
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        // Execute the query
        $result = pg_query_params($con, $query, $params);

        if (!$result) {
            die("Error in SQL query: " . pg_last_error());
        }

        while ($row = pg_fetch_assoc($result)) {
            $results[] = $row;
        }

        pg_free_result($result);
    } else {
        // If any of the required parameters are missing, don't fetch data
        echo "Please select exam type, subject, and at least one of class or category to fetch data.";
    }

    pg_close($con);
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
                                        <div class="mb-3">
                                            <label for="class" class="form-label">Class</label>
                                            <select class="form-select" id="class" name="class[]" multiple="multiple" required>
                                                <option value="Nursery" <?php if (isset($_GET['class']) && in_array('Nursery', $_GET['class'])) echo 'selected'; ?>>Nursery</option>
                                                <option value="LKG" <?php if (isset($_GET['class']) && in_array('LKG', $_GET['class'])) echo 'selected'; ?>>LKG</option>
                                                <option value="1" <?php if (isset($_GET['class']) && in_array('1', $_GET['class'])) echo 'selected'; ?>>1</option>
                                                <option value="2" <?php if (isset($_GET['class']) && in_array('2', $_GET['class'])) echo 'selected'; ?>>2</option>
                                                <option value="3" <?php if (isset($_GET['class']) && in_array('3', $_GET['class'])) echo 'selected'; ?>>3</option>
                                                <option value="4" <?php if (isset($_GET['class']) && in_array('4', $_GET['class'])) echo 'selected'; ?>>4</option>
                                                <option value="5" <?php if (isset($_GET['class']) && in_array('5', $_GET['class'])) echo 'selected'; ?>>5</option>
                                                <option value="6" <?php if (isset($_GET['class']) && in_array('6', $_GET['class'])) echo 'selected'; ?>>6</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="category" class="form-label">Category</label>
                                            <select class="form-select" id="category" name="category[]" multiple="multiple">
                                                <option value="LG1" <?php if (isset($_GET['category']) && in_array('LG1', $_GET['category'])) echo 'selected'; ?>>LG1</option>
                                                <option value="LG2-A" <?php if (isset($_GET['category']) && in_array('LG2-A', $_GET['category'])) echo 'selected'; ?>>LG2-A</option>
                                                <option value="LG2-B" <?php if (isset($_GET['category']) && in_array('LG2-B', $_GET['category'])) echo 'selected'; ?>>LG2-B</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="exam_type" class="form-label">Exam Type</label>
                                            <select class="form-select" id="exam_type" name="exam_type" required>
                                                <option value="" disabled <?php echo !isset($_GET['exam_type']) ? 'selected' : ''; ?>>Select Exam Type</option>
                                                <option value="First Term" <?php echo (isset($_GET['exam_type']) && $_GET['exam_type'] == 'First Term') ? 'selected' : ''; ?>>First Term</option>
                                                <option value="Half Yearly" <?php echo (isset($_GET['exam_type']) && $_GET['exam_type'] == 'Half Yearly') ? 'selected' : ''; ?>>Half Yearly</option>
                                                <option value="Annual" <?php echo (isset($_GET['exam_type']) && $_GET['exam_type'] == 'Annual') ? 'selected' : ''; ?>>Annual</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="academic_year" class="form-label">Academic Year</label>
                                            <select class="form-select" id="academic_year" name="academic_year">
                                                <!-- Populate academic years dynamically if needed -->
                                                <option value="" disabled>Select Academic Year</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="subject" class="form-label">Subject</label>
                                            <select class="form-select" id="subject" name="subject" required>
                                                <option value="" disabled <?php echo !isset($_GET['subject']) ? 'selected' : ''; ?>>Select a subject</option>
                                                <option value="Hindi" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'Hindi') ? 'selected' : ''; ?>>Hindi</option>
                                                <option value="English" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'English') ? 'selected' : ''; ?>>English</option>
                                                <option value="Mathematics" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'Mathematics') ? 'selected' : ''; ?>>Mathematics</option>
                                                <option value="GK" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'GK') ? 'selected' : ''; ?>>GK</option>
                                                <option value="Humara Parivesh" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'Humara Parivesh') ? 'selected' : ''; ?>>Humara Parivesh</option>
                                                <option value="Arts & Craft" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'Arts & Craft') ? 'selected' : ''; ?>>Arts & Craft</option>
                                                <option value="Sulekh+Imla" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'Sulekh+Imla') ? 'selected' : ''; ?>>Sulekh+Imla</option>
                                                <option value="Project" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'Project') ? 'selected' : ''; ?>>Project</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="teacher_id" class="form-label">Teacher ID</label>
                                            <select class="form-select" id="teacher_id" name="teacher_id">
                                                <option value="" disabled selected hidden>Select Teacher's ID</option>
                                                <?php foreach ($teachers as $teacher) { ?>
                                                    <option value="<?php echo $teacher['associatenumber']; ?>" <?php echo (isset($_GET['teacher_id']) && $_GET['teacher_id'] == $teacher['associatenumber']) ? 'selected' : ''; ?>>
                                                        <?php echo $teacher['associatenumber'] . ' - ' . $teacher['fullname']; ?>
                                                    </option>
                                                <?php } ?>
                                                <option value="" disabled>---</option>
                                                <option value="clear">Clear Selection</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 align-self-end">
                                            <button type="submit" name="search_by_id" class="btn btn-primary btn-sm">
                                                <i class="bi bi-search"></i>&nbsp;Search
                                            </button>
                                        </div>
                                    </div>
                                </form>


                                <?php if (!empty($results)) : ?>
                                    <h2 class="mt-4">Search Results</h2>
                                    <form method="POST" action="save_marks.php">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Student ID</th>
                                                    <th>Student Name</th>
                                                    <th>Category</th>
                                                    <th>Class</th>
                                                    <th>Written Marks</th>
                                                    <th>Viva Marks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($results as $row) : ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['studentname']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['class']); ?></td>
                                                        <td><input type="number" name="written_marks[<?php echo htmlspecialchars($row['exam_id'] . '_' . $row['student_id']); ?>]" value="<?php echo htmlspecialchars($row['written_marks']); ?>" class="form-control"></td>
                                                        <td><input type="number" name="viva_marks[<?php echo htmlspecialchars($row['exam_id'] . '_' . $row['student_id']); ?>]" value="<?php echo htmlspecialchars($row['viva_marks']); ?>" class="form-control"></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <button type="submit" id="save" class="btn btn-success">Save</button>
                                        <button type="submit" id="submit" class="btn btn-primary">Submit</button>
                                    </form>
                                <?php elseif (empty($_GET['exam_type']) && empty($_GET['subject'])) : ?>
                                    <p class="mt-4">Please select both exam type and subject to fetch data.</p>
                                <?php else : ?>
                                    <p class="mt-4">No records found for the selected filter criteria.</p>
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

            // Function to get URL parameter
            function getParameterByName(name) {
                name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
                var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                    results = regex.exec(location.search);
                return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
            }

            // Get the academic year from the URL if it exists
            var academicYearFromUrl = getParameterByName('academic_year');
            if (academicYearFromUrl) {
                selectedYear = academicYearFromUrl;
            }

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

            // Handle clear selection option
            var selects = document.querySelectorAll('select');
            selects.forEach(function(select) {
                select.addEventListener('change', function() {
                    if (this.value === 'clear') {
                        this.selectedIndex = 0;
                    }
                });
            });
        });
    </script>
</body>

</html>