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

// Fetch academic years and examination names for filters
$academicYearQuery = "SELECT DISTINCT academic_year FROM reexamination ORDER BY academic_year";
$academicYearResult = pg_query($con, $academicYearQuery);

$examinationNameQuery = "SELECT DISTINCT examination_name FROM reexamination ORDER BY examination_name";
$examinationNameResult = pg_query($con, $examinationNameQuery);

// Fetch filtered data for the reexamination table
$filterAcademicYear = $_GET['academic_year'] ?? '';
$filterExaminationName = $_GET['examination_name'] ?? '';

$query = "
    SELECT r.*, s.studentname, s.class, a.fullname 
    FROM reexamination r
    LEFT JOIN rssimyprofile_student s ON r.student_id = s.student_id
    LEFT JOIN rssimyaccount_members a ON r.taken_by = a.associatenumber
    WHERE ($1 = '' OR r.academic_year = $1)
    AND ($2 = '' OR r.examination_name = $2)
    ORDER BY r.re_date DESC, s.class
";

$params = [$filterAcademicYear, $filterExaminationName];
$result = pg_query_params($con, $query, $params);

if (!$result) {
    echo "An error occurred while fetching the data.";
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container mt-5">
                                <!-- Filter Form -->
                                <form method="get" class="mb-4">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="academic_year" class="form-label">Academic Year</label>
                                            <select name="academic_year" id="academic_year" class="form-select">
                                                <option value="">All</option>
                                                <?php while ($row = pg_fetch_assoc($academicYearResult)): ?>
                                                    <option value="<?php echo htmlspecialchars($row['academic_year']); ?>" <?php echo ($filterAcademicYear == $row['academic_year']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($row['academic_year']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="examination_name" class="form-label">Examination Name</label>
                                            <select name="examination_name" id="examination_name" class="form-select">
                                                <option value="">All</option>
                                                <?php while ($row = pg_fetch_assoc($examinationNameResult)): ?>
                                                    <option value="<?php echo htmlspecialchars($row['examination_name']); ?>" <?php echo ($filterExaminationName == $row['examination_name']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($row['examination_name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-primary mt-3">Filter</button>
                                        </div>
                                    </div>

                                </form>

                                <table id="reexaminationTable" class="table">
                                    <thead>
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                            <th>Class</th>
                                            <th>Date</th>
                                            <th>Re-exam Date</th>
                                            <th>Subject</th>
                                            <th>Examination Name</th>
                                            <th>Academic Year</th>
                                            <th>Taken By (Associate ID)</th>
                                            <th>Associate Name</th>
                                            <!-- <th>Remarks</th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = pg_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                                <td><?php echo htmlspecialchars($row['studentname'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($row['class'] ?? 'N/A'); ?></td>
                                                <td><?php echo !empty($row['date']) ? date('d/m/Y', strtotime($row['date'])) : ''; ?></td>
                                                <td><?php echo !empty($row['re_date']) ? date('d/m/Y', strtotime($row['re_date'])) : ''; ?></td>
                                                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                                <td><?php echo htmlspecialchars($row['examination_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                                                <td><?php echo htmlspecialchars($row['taken_by']); ?></td>
                                                <td><?php echo htmlspecialchars($row['fullname'] ?? 'N/A'); ?></td>
                                                <!-- <td><?php echo htmlspecialchars($row['remarks']); ?></td> -->
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
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

    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($result)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#reexaminationTable').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>