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
        // Initialize query with base SELECT statement
        $query = "SELECT e.exam_id, e.exam_type, e.exam_mode,e.academic_year, e.subject, e.full_marks_written, e.full_marks_viva, emd.id, emd.student_id, emd.viva_marks, emd.written_marks, s.studentname as studentname, s.category as category, s.class as class
                  FROM exams e
                  JOIN exam_marks_data emd ON e.exam_id = emd.exam_id
                  JOIN rssimyprofile_student s ON emd.student_id = s.student_id
                  WHERE e.exam_id = $1
                  ORDER BY emd.id"; // Change this column if needed

        // Initialize parameters array with exam_id
        $params = [$_GET['exam_id']];

        // Execute the query
        $result = pg_query_params($con, $query, $params);

        if (!$result) {
            die("Error in SQL query: " . pg_last_error());
        }

        // Fetch and store results
        $results = [];
        while ($row = pg_fetch_assoc($result)) {
            $results[] = $row;
        }

        pg_free_result($result);
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

    // Prepare the update cases for written marks
    if (isset($_POST['written_marks']) && !empty($_POST['written_marks'])) {
        foreach ($_POST['written_marks'] as $key => $written_mark) {
            if (is_numeric($written_mark)) {
                list($exam_id, $student_id) = explode('_', $key);
                $written_marks_cases[] = "WHEN exam_id = $" . $written_param_index . " AND student_id = $" . ($written_param_index + 1) . " THEN $" . ($written_param_index + 2);
                $written_params[] = $exam_id;
                $written_params[] = $student_id;
                $written_params[] = $written_mark;
                $written_param_index += 3;
            }
        }
    }

    // Prepare the update cases for viva marks
    if (isset($_POST['viva_marks']) && !empty($_POST['viva_marks'])) {
        foreach ($_POST['viva_marks'] as $key => $viva_mark) {
            if (is_numeric($viva_mark)) {
                list($exam_id, $student_id) = explode('_', $key);
                $viva_marks_cases[] = "WHEN exam_id = $" . $viva_param_index . " AND student_id = $" . ($viva_param_index + 1) . " THEN $" . ($viva_param_index + 2);
                $viva_params[] = $exam_id;
                $viva_params[] = $student_id;
                $viva_params[] = $viva_mark;
                $viva_param_index += 3;
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
                                    <h2 class="mt-4">Search Results</h2>
                                    <form method="POST" action="exam_marks_upload.php">
                                        <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($_GET['exam_id']); ?>">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
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
                                                        <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['studentname']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['class']); ?></td>
                                                        <?php if ($exam_mode === '{Written}' || $exam_mode === '{Written,Viva}' || $exam_mode === '{Viva,Written}') : ?>
                                                            <td>
                                                                <input type="number" step="0.01" name="written_marks[<?php echo htmlspecialchars($row['exam_id'] . '_' . $row['student_id']); ?>]" value="<?php echo htmlspecialchars($row['written_marks']); ?>" class="form-control">
                                                            </td>
                                                        <?php endif; ?>
                                                        <?php if ($exam_mode === '{Viva}' || $exam_mode === '{Written,Viva}' || $exam_mode === '{Viva,Written}') : ?>
                                                            <td>
                                                                <input type="number" step="0.01" name="viva_marks[<?php echo htmlspecialchars($row['exam_id'] . '_' . $row['student_id']); ?>]" value="<?php echo htmlspecialchars($row['viva_marks']); ?>" class="form-control">
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <!-- <button type="submit" id="save" class="btn btn-success">Save</button> -->
                                        <button type="submit" id="submit" class="btn btn-primary">Submit</button>
                                    </form>
                                <?php elseif (empty($_GET['exam_id'])) : ?>
                                    <p class="mt-4">Please provide an exam ID to fetch data.</p>
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