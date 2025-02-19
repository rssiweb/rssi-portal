<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
validation();

// Database connection (assuming $con is already defined)
// $con = pg_connect("host=localhost dbname=your_db_name user=your_db_user password=your_db_password");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $studentIds = $_POST['studentId'] ?? []; // Expecting an array
    $date = $_POST['date'] ?? null;
    $re_date = $_POST['re_date'] ?? null;
    $subject = $_POST['subject'] ?? null;
    $examinationName = $_POST['examinationName'] ?? null;
    $academicYear = $_POST['academicYear'] ?? null;
    $takenBy = $_POST['takenBy'] ?? null;
    $remarks = $_POST['remarks'] ?? null;

    // Insert into reexamination table for each student ID
    foreach ($studentIds as $studentId) {
        $query = "
            INSERT INTO reexamination (student_id, date, subject, examination_name, academic_year, taken_by, remarks, re_date)
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
            RETURNING reexamination_id;
        ";
        $params = [$studentId, $date, $subject, $examinationName, $academicYear, $takenBy, $remarks, $re_date];

        $result = pg_query_params($con, $query, $params);

        if (!$result) {
            echo json_encode(['status' => 'error', 'message' => 'An error occurred while saving the record for Student ID: ' . $studentId]);
            pg_close($con);
            exit;
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Re-examination records saved successfully!']);
    pg_close($con);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
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
                            <div class="container mt-5">
                                <div class="row justify-content-center">
                                    <div class="col-md-6">
                                        <form id="reexaminationForm">
                                            <!-- Student ID -->
                                            <div class="mb-3">
                                                <label for="studentId" class="form-label">Student ID</label>
                                                <select id="studentId" name="studentId[]" class="form-control" multiple="multiple" required>
                                                    <?php foreach ($students as $student): ?>
                                                        <option value="<?php echo htmlspecialchars($student['id']); ?>">
                                                            <?php echo htmlspecialchars($student['text']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="date" class="form-label">Actual date of examination</label>
                                                <input type="date" class="form-control" name="date" id="date" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="re_date" class="form-label">Date of Re-examination</label>
                                                <input type="date" class="form-control" name="re_date" id="re_date" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="subject" class="form-label">Subject</label>
                                                <!-- <input type="text" class="form-control" name="subject" id="subject" required> -->
                                                <select name="subject" id="subject" class="form-select" required>
                                                    <option disabled selected hidden value="">Select Subject</option>
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
                                            <div class="mb-3">
                                                <label for="examinationName" class="form-label">Examination Name</label>
                                                <select name="examinationName" id="examinationName" class="form-select" required>
                                                    <option disabled selected hidden value="">Select Exam Name</option>
                                                    <option>First Term</option>
                                                    <option>Half Yearly</option>
                                                    <option>Annual</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="academicYear" class="form-label">Academic Year</label>
                                                <select name="academicYear" id="academicYear" class="form-select" required>
                                                    <option disabled selected hidden value="">Select Year</option>
                                                </select>
                                            </div>
                                            <!-- Taken By -->
                                            <div class="mb-3">
                                                <label for="takenBy" class="form-label">Taken By</label>
                                                <select id="takenBy" name="takenBy" class="form-control" required>
                                                    <?php foreach ($associates as $associate): ?>
                                                        <option value="<?php echo htmlspecialchars($associate['id']); ?>">
                                                            <?php echo htmlspecialchars($associate['text']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="remarks" class="form-label">Remarks</label>
                                                <textarea class="form-control" name="remarks" id="remarks" rows="3"></textarea>
                                            </div>
                                            <div class="d-flex justify-content-end">
                                                <button type="submit" class="btn btn-primary">Submit</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
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
        document.getElementById('reexaminationForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent form submission

            // Create FormData object
            const formData = new FormData(this);

            // Send data to the backend
            fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        // Optionally reset the form after successful submission            
                        if (window.history.replaceState) {
                            // Update the URL without causing a page reload or resubmission
                            window.history.replaceState(null, null, window.location.href);
                        }
                        window.location.reload(); // Trigger a page reload to reflect changes
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    alert('An error occurred while saving the record.');
                });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('#studentId').select2();
            $('#takenBy').select2();
        });
    </script>
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
        document.addEventListener('DOMContentLoaded', function() {
            const requiredFields = document.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                const label = document.querySelector(`label[for="${field.id}"]`);
                if (label) {
                    label.innerHTML += ' <span style="color: red">*</span>';
                }
            });
        });
    </script>
</body>

</html>