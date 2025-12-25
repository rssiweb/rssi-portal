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

// Database connection (assuming $con is already defined)
// $con = pg_connect("host=localhost dbname=your_db_name user=your_db_user password=your_db_password");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get common fields
    $commonData = [
        're_date' => $_POST['re_date'],
        'examinationName' => $_POST['examinationName'],
        'academicYear' => $_POST['academicYear']
    ];

    // Get class-wise data
    $classes = $_POST['class'] ?? [];
    $subjects = $_POST['subject'] ?? [];
    $studentIds = $_POST['studentId'] ?? [];
    $actualDates = $_POST['actualDate'] ?? [];
    $takenBys = $_POST['takenBy'] ?? []; // New: Taken By is now part of subject data

    // Insert into reexamination table
    foreach ($classes as $classIndex => $class) {
        foreach ($subjects[$classIndex] as $subjectIndex => $subject) {
            $takenBy = $takenBys[$classIndex][$subjectIndex]; // Get Taken By for this subject
            foreach ($studentIds[$classIndex][$subjectIndex] as $studentId) {
                $query = "
                    INSERT INTO reexamination (
                        re_date, examination_name, academic_year, 
                        class, subject, student_id, date, taken_by
                    ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
                ";
                $params = [
                    $commonData['re_date'],
                    $commonData['examinationName'],
                    $commonData['academicYear'],
                    $class,
                    $subject,
                    $studentId,
                    $actualDates[$classIndex][$subjectIndex],
                    $takenBy // Use the subject-specific Taken By value
                ];

                $result = pg_query_params($con, $query, $params);

                if (!$result) {
                    echo json_encode(['status' => 'error', 'message' => 'Error saving record']);
                    exit;
                }
            }
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Records saved successfully!']);
    exit;
}

// Query for students
$student_query = "
    SELECT student_id AS id, studentname AS name 
    FROM rssimyprofile_student 
    WHERE filterstatus = 'Active'
";
$student_result = pg_query($con, $student_query);
$students = [];
while ($row = pg_fetch_assoc($student_result)) {
    $students[] = [
        'id' => $row['id'],
        'text' => $row['name'] . ' (' . $row['id'] . ')'
    ];
}

// Query for associates
$associate_query = "
    SELECT associatenumber AS id, fullname AS name 
    FROM rssimyaccount_members 
    WHERE filterstatus = 'Active'
";
$associate_result = pg_query($con, $associate_query);
$associates = [];
while ($row = pg_fetch_assoc($associate_result)) {
    $associates[] = [
        'id' => $row['id'],
        'text' => $row['name'] . ' (' . $row['id'] . ')'
    ];
}
$classlist = [
    "Nursery",
    "LKG",
    "UKG",
    "Pre-school",
    "1",
    "2",
    "3",
    "4",
    "5",
    "6",
    "7",
    "8",
    "9",
    '10',
    "11",
    "12",
    "Vocational training",
]
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Re-examination Form</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Re-examination</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Academic</a></li>
                    <li class="breadcrumb-item"><a href="exam-management.php">Exam Management</a></li>
                    <li class="breadcrumb-item active">Re-examination</li>
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
                            <section class="container section dashboard">
                                <div class="row">
                                    <div class="col-12">
                                        <!-- <div class="card"> -->
                                        <!-- <div class="card-body"> -->
                                        <form id="reexaminationForm">
                                            <!-- Common Fields -->
                                            <div class="common-fields mb-5 border p-3">
                                                <h5>Common Information</h5>
                                                <div class="row g-3">
                                                    <div class="col-md-3">
                                                        <label class="form-label">Re-exam Date</label>
                                                        <input type="date" name="re_date" class="form-control" required>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Examination Name</label>
                                                        <select name="examinationName" class="form-select" required>
                                                            <option value="">Select Exam</option>
                                                            <option>First Term</option>
                                                            <option>Half Yearly</option>
                                                            <option>Annual</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Academic Year</label>
                                                        <select name="academicYear" id="academicYear" class="form-select" required></select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Class Sections -->
                                            <div id="classSections">
                                                <!-- Class section will be dynamically added here -->
                                            </div>

                                            <div class="mt-3">
                                                <button type="button" class="btn btn-success" id="addClass">Add Class</button>
                                                <button type="submit" class="btn btn-primary">Submit All</button>
                                            </div>
                                        </form>
                                        <!-- </div> -->
                                        <!-- </div> -->
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <!-- JavaScript for Form Submission -->
    <script>
        <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
            var currentYear = new Date().getFullYear() - 1;
        <?php } else { ?>
            var currentYear = new Date().getFullYear();
        <?php } ?>

        for (var i = 0; i < 2; i++) {
            var next = currentYear + 1;
            var year = currentYear + '-' + next;
            //next.toString().slice(-2) 
            $('#academicYear').append(new Option(year, year));
            currentYear--;
        }
    </script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2();

            // Class counter
            let classCounter = 0;

            // Add Class
            $('#addClass').click(function() {
                classCounter++;
                const newClass = `
                <div class="class-section mb-4 border p-3">
                    <div class="class-header d-flex justify-content-between mb-3">
                        <h5>Class Information</h5>
                        <button type="button" class="btn btn-danger btn-sm remove-class">×</button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Class Name</label>
                            <!--<input type="text" name="class[]" class="form-control" required>-->
                            <select name="class[]" class="form-select" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classlist as $cls) { ?>
                                                        <option><?php echo $cls ?></option>
                                                    <?php } ?>
                </select>
                        </div>
                    </div>
                    <div class="subject-sections mt-3"></div>
                    <button type="button" class="btn btn-secondary btn-sm add-subject">Add Subject</button>
                </div>
            `;
                $('#classSections').append(newClass);
            });

            // Add Subject
            $(document).on('click', '.add-subject', function() {
                const classSection = $(this).closest('.class-section');
                const classIndex = classSection.index();
                const subjectCount = classSection.find('.subject-section').length;

                const newSubject = `
    <div class="subject-section mb-3 border p-3">
        <div class="subject-header d-flex justify-content-between mb-3">
            <h6>Subject Information</h6>
            <button type="button" class="btn btn-danger btn-sm remove-subject">×</button>
        </div>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Subject</label>
                <select name="subject[${classIndex}][]" class="form-select" required>
                    <option value="">Select Subject</option>
                    <option> Hindi </option>
                                                    <option> English </option>
                                                    <option> Science </option>
                                                    <option> Physics </option>
                                                    <option> Physical science </option>
                                                    <option> Chemistry </option>
                                                    <option> Biology </option>
                                                    <option> Life science </option>
                                                    <option> Mathematics </option>
                                                    <option> Social Science </option>
                                                    <option> Accountancy </option>
                                                    <option> Computer </option>
                                                    <option> GK </option>
                                                    <option> Hamara Parivesh </option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Actual Exam Date</label>
                <input type="date" name="actualDate[${classIndex}][]" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Students</label>
                <select name="studentId[${classIndex}][${subjectCount}][]" class="form-control select2-multiple" multiple required>
                    <?php foreach ($students as $student): ?>
                    <option value="<?= $student['id'] ?>"><?= $student['text'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Taken By</label>
                <select name="takenBy[${classIndex}][${subjectCount}]" class="form-select select2" required>
                <option value="">Select Associate</option>
                    <?php foreach ($associates as $associate): ?>
                    <option value="<?= $associate['id'] ?>"><?= $associate['text'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
`;
                classSection.find('.subject-sections').append(newSubject);
                classSection.find('.select2-multiple').select2();
            });

            // Remove Class
            $(document).on('click', '.remove-class', function() {
                $(this).closest('.class-section').remove();
            });

            // Remove Subject
            $(document).on('click', '.remove-subject', function() {
                $(this).closest('.subject-section').remove();
            });

            // Form Submission
            $('#reexaminationForm').submit(function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        alert(data.message);
                        if (data.status === 'success') window.location.reload();
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    </script>
</body>

</html>