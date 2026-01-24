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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Initialize query with base SELECT statement
    $query = "
        SELECT 
            e.exam_id, 
            e.exam_type, 
            e.exam_mode, 
            e.academic_year, 
            e.subject, 
            e.full_marks_written, 
            e.full_marks_viva, 
            e.exam_date_written, 
            e.exam_date_viva, 
            e.teacher_id_viva, 
            e.teacher_id_written, 
            e.estatus, 
            STRING_AGG(DISTINCT emd.class::text, ',') AS classes, 
            a_viva.fullname AS fullname_viva,
            a_written.fullname AS fullname_written
        FROM exams e
        LEFT JOIN rssimyaccount_members a_viva ON e.teacher_id_viva = a_viva.associatenumber
        LEFT JOIN rssimyaccount_members a_written ON e.teacher_id_written = a_written.associatenumber
        JOIN exam_marks_data emd ON e.exam_id = emd.exam_id
    ";

    // Initialize parameters array and conditions array
    $params = [];
    $conditions = [];
    $filterProvided = false;

    // Build conditions based on GET parameters
    if (!empty($_GET['exam_id'])) {
        $conditions[] = "e.exam_id = $" . (count($params) + 1);
        $params[] = $_GET['exam_id'];
        $filterProvided = true;
    }

    if (!empty($_GET['exam_type'])) {
        $conditions[] = "e.exam_type = $" . (count($params) + 1);
        $params[] = $_GET['exam_type'];
        $filterProvided = true;
    }

    if (!empty($_GET['academic_year'])) {
        $conditions[] = "e.academic_year = $" . (count($params) + 1);
        $params[] = $_GET['academic_year'];
        $filterProvided = true;
    }

    if (!empty($_GET['subject'])) {
        $conditions[] = "e.subject = $" . (count($params) + 1);
        $params[] = $_GET['subject'];
        $filterProvided = true;
    }

    // Check user role and apply conditions accordingly
    if ($role !== 'Admin' && $role !== 'Offline Manager') {
        // Only include conditions for regular users to see their assigned records
        $conditions[] = "(e.teacher_id_viva = $" . (count($params) + 1) . " OR e.teacher_id_written = $" . (count($params) + 1) . ")";
        $params[] = $associatenumber; // for viva
    }

    // Only execute the query if a filter is provided
    if ($filterProvided) {
        // Add filter for Teacher ID (applies to both teacher_id_viva and teacher_id_written)
        if (!empty($_GET['teacher_id'])) {
            $conditions[] = "(e.teacher_id_viva = $" . (count($params) + 1) . " OR e.teacher_id_written = $" . (count($params) + 1) . ")";
            $params[] = $_GET['teacher_id'];
        }
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        // Add GROUP BY clause
        $query .= " GROUP BY e.exam_id, e.exam_type, e.exam_mode, e.academic_year, e.subject, 
                            e.full_marks_written, e.full_marks_viva, e.exam_date_written, e.exam_date_viva, 
                            e.teacher_id_viva, e.teacher_id_written, e.estatus, a_viva.fullname, a_written.fullname";

        // Execute the query
        $result = pg_query_params($con, $query, $params);

        // Handle potential query error
        if (!$result) {
            die("Error in SQL query: " . pg_last_error($con)); // Pass connection to get context of the error
        }

        // Fetch results
        $results = []; // Initialize results array
        while ($row = pg_fetch_assoc($result)) {
            $results[] = $row;
        }

        pg_free_result($result);
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
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Exam Allotment</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Include Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

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
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>

    <?php include 'header.php'; ?>
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
                            <div class="container">
                                <form id="filter_form" method="GET" action="">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-3">
                                            <label for="exam_id" class="form-label">Exam ID</label>
                                            <input type="text" class="form-control" id="exam_id" name="exam_id" value="<?php echo isset($_GET['exam_id']) ? htmlspecialchars($_GET['exam_id']) : ''; ?>">
                                        </div>

                                        <div class="col-md-3">
                                            <label for="exam_type" class="form-label">Exam Type</label>
                                            <select class="form-select" id="exam_type" name="exam_type" required>
                                                <option value="" disabled selected>Select Exam Type</option>
                                                <option value="First Term" <?php echo (isset($_GET['exam_type']) && $_GET['exam_type'] == 'First Term') ? 'selected' : ''; ?>>First Term</option>
                                                <option value="Half Yearly" <?php echo (isset($_GET['exam_type']) && $_GET['exam_type'] == 'Half Yearly') ? 'selected' : ''; ?>>Half Yearly</option>
                                                <option value="Annual" <?php echo (isset($_GET['exam_type']) && $_GET['exam_type'] == 'Annual') ? 'selected' : ''; ?>>Annual</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="academic_year" class="form-label">Academic Year</label>
                                            <select class="form-select" id="academic_year" name="academic_year">
                                                <!-- Populate academic years dynamically if needed -->
                                                <option disabled>Select Academic Year</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="subject" class="form-label">Subject</label>
                                            <select class="form-select" id="subject" name="subject">
                                                <option disabled <?php echo !isset($_GET['subject']) ? 'selected' : ''; ?>>Select a subject</option>
                                                <option value="Hindi" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'Hindi') ? 'selected' : ''; ?>>Hindi</option>
                                                <option value="English" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'English') ? 'selected' : ''; ?>>English</option>
                                                <option value="Mathematics" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'Mathematics') ? 'selected' : ''; ?>>Mathematics</option>
                                                <option value="GK" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'GK') ? 'selected' : ''; ?>>GK</option>
                                                <option value="Hamara Parivesh" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'Hamara Parivesh') ? 'selected' : ''; ?>>Hamara Parivesh</option>
                                                <option value="Computer" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'Computer') ? 'selected' : ''; ?>>Computer</option>
                                                <option value="Art & Craft" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'Art & Craft') ? 'selected' : ''; ?>>Art & Craft</option>
                                                <option value="Sulekh+Imla" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'Sulekh+Imla') ? 'selected' : ''; ?>>Sulekh+Imla</option>
                                                <option value="Project" <?php echo (isset($_GET['subject']) && $_GET['subject'] == 'Project') ? 'selected' : ''; ?>>Project</option>
                                            </select>
                                        </div>
                                        <?php if ($role == 'Admin' || $role == 'Offline Manager') { ?>
                                            <div class="col-md-3">
                                                <label for="teacher_id" class="form-label">Teacher ID</label>
                                                <select class="form-select select2" id="teacher_id" name="teacher_id">

                                                    <?php if (!empty($_GET['teacher_id'])): ?>
                                                        <!-- Pre-fill selected value -->
                                                        <option value="<?php echo htmlspecialchars($_GET['teacher_id']); ?>" selected>
                                                            <?php echo htmlspecialchars($_GET['teacher_id']); ?>
                                                        </option>
                                                    <?php else: ?>
                                                        <!-- Ajax will populate options here -->
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        <?php } ?>
                                        <div id="filter-checks">
                                            <input class="form-check-input" type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_GET['is_user'])) echo "checked='checked'"; ?> />
                                            <label for="is_user" style="font-weight: 400;">Search by Exam ID</label>
                                        </div>
                                        <div class="col-md-3 align-self-end">
                                            <button type="submit" name="search_by_id" class="btn btn-primary btn-sm">
                                                <i class="bi bi-search"></i>&nbsp;Search
                                            </button>
                                        </div>
                                    </div>
                                </form>


                                <?php if (!empty($results)) : ?>
                                    <?php $serialNumber = 1; // Initialize serial number 
                                    ?>
                                    <h2 class="mt-4">Search Results</h2>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="table-id">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Exam ID</th>
                                                    <th>Exam type</th>
                                                    <th>Academic year</th>
                                                    <th>Subject</th>
                                                    <th>Class</th>
                                                    <th>Exam mode</th>
                                                    <th>Full Marks</th>
                                                    <th>Date of exam</th>
                                                    <th>Assigned to</th>
                                                    <?php if ($role == 'Admin') : ?>
                                                        <th>Unlink</th>
                                                        <th>Edit</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($results as $row) : ?>
                                                    <tr>
                                                        <td><?= $serialNumber++ ?></td> <!-- Display and increment serial number -->
                                                        <td><a href="exam_marks_upload.php?exam_id=<?php echo htmlspecialchars($row['exam_id']); ?>"><?php echo htmlspecialchars($row['exam_id']); ?></a></td>
                                                        <td><?php echo htmlspecialchars($row['exam_type']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['classes']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['exam_mode']); ?></td>
                                                        <td>
                                                            <?php
                                                            if ($row['full_marks_written'] !== null) {
                                                                echo 'W-' . htmlspecialchars($row['full_marks_written']);
                                                            }
                                                            if ($row['full_marks_viva'] !== null) {
                                                                echo ' V-' . htmlspecialchars($row['full_marks_viva']);
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            if (!empty($row['exam_date_written'])) {
                                                                echo date('d/m/Y', strtotime($row['exam_date_written']));
                                                            }
                                                            if (!empty($row['exam_date_viva'])) {
                                                                echo ' | ' . date('d/m/Y', strtotime($row['exam_date_viva']));
                                                            }
                                                            ?>
                                                        </td>

                                                        <td>
                                                            <?php
                                                            $info = [];

                                                            // Check for written assignment
                                                            if (!empty($row['teacher_id_written'])) {
                                                                $info[] = htmlspecialchars($row['teacher_id_written'] . '-' . $row['fullname_written']);
                                                            } else {
                                                                $info[] = 'Written: Not assigned yet';
                                                            }

                                                            // Check for viva assignment
                                                            if (!empty($row['teacher_id_viva'])) {
                                                                $info[] = htmlspecialchars($row['teacher_id_viva'] . '-' . $row['fullname_viva']);
                                                            } else {
                                                                $info[] = 'Viva: Not assigned yet';
                                                            }

                                                            // Display the information
                                                            echo implode(' | ', $info);
                                                            ?>

                                                        </td>

                                                        <?php if ($role == 'Admin') : ?>
                                                            <td>
                                                                <?php if ($row['estatus'] != 'disabled') : ?>
                                                                    <form name="close_<?= htmlspecialchars($row['exam_id']) ?>" action="#" method="POST" style="display: -webkit-inline-box;">
                                                                        <input type="hidden" name="form-type" value="eclose">
                                                                        <input type="hidden" name="eid" value="<?= htmlspecialchars($row['exam_id']) ?>">
                                                                        <button type="submit" style="display: -webkit-inline-box; width: fit-content; word-wrap: break-word; outline: none; background: none; padding: 0px; border: none;" title="close">
                                                                            <i class="bi bi-cloud-slash"></i>
                                                                        </button>
                                                                    </form>
                                                                <?php else : ?>
                                                                    <?php echo htmlspecialchars($row['estatus']); ?>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!isset($row['estatus'])): ?>
                                                                    <a href="exam_data_update.php?fetch_exam_id=<?= urlencode($row['exam_id']) ?>" target="_blank">Edit</a>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php elseif (empty($_GET) || (!isset($_GET['is_user']) && empty($_GET['exam_type']))) : ?>
                                    <div class="alert alert-info mt-4">
                                        <i class="bi bi-info-circle"></i> Please select Exam Type to view exam data.
                                    </div>
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
        $(document).ready(function() {
            $('#table-id').DataTable({
                paging: false,
                "order": [] // Disable initial sorting
                // other options...
            });
        });
    </script>
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
        });
    </script>
    <script>
        // Check initial state on page load
        if (!$('#is_user').is(':checked')) {
            // Disable input fields if checkbox is not checked
            $("#exam_id").prop('disabled', true);
            $("#exam_type").prop('disabled', false);
            $("#academic_year").prop('disabled', false);
            $("#subject").prop('disabled', false);
            $("#teacher_id").prop('disabled', false);
        } else {
            // Enable input fields if checkbox is checked
            $("#exam_id").prop('disabled', false);
            $("#exam_type").prop('disabled', true);
            $("#academic_year").prop('disabled', true);
            $("#subject").prop('disabled', true);
            $("#teacher_id").prop('disabled', true);
        }

        // Add event listener to checkbox
        $('#is_user').change(function() {
            if ($(this).is(':checked')) {
                // Disable input fields if checkbox is checked
                $("#exam_id").prop('disabled', false);
                $("#exam_type").prop('disabled', true);
                $("#academic_year").prop('disabled', true);
                $("#subject").prop('disabled', true);
                $("#teacher_id").prop('disabled', true);
            } else {
                // Enable input fields if checkbox is not checked
                $("#exam_id").prop('disabled', true);
                $("#exam_type").prop('disabled', false);
                $("#academic_year").prop('disabled', false);
                $("#subject").prop('disabled', false);
                $("#teacher_id").prop('disabled', false);
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const specificForms = document.querySelectorAll('form[name^="close_"]');

            specificForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(form);
                    fetch('payment-api.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(data => {
                            if (data.trim() === 'success') {
                                alert("Exam has been closed.");
                                // Optionally, you can remove or disable the form/button to indicate it's closed
                                form.querySelector('button[type="submit"]').disabled = true;
                            } else {
                                alert("Failed to close the exam. Please try again.");
                            }
                        })
                        .catch(error => console.error('Error!', error.message));
                });
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById("filter_form");

            form.addEventListener("submit", function(e) {
                if (!document.getElementById("is_user").checked &&
                    !document.getElementById("exam_type").value) {
                    e.preventDefault();
                    alert("Please select an Exam Type or check 'Search by Exam ID'");
                    document.getElementById("exam_type").focus();
                }
            });
        });
    </script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for associate numbers
            $('#teacher_id').select2({
                ajax: {
                    url: 'fetch_associates.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            isActive: true
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Select associate(s)',
                allowClear: true,
                // multiple: true
            });
        });
    </script>
</body>

</html>