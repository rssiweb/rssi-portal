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

if ($role == 'Admin') {
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
}

$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Initialize query with base SELECT statement
    $query = "SELECT e.exam_id, e.exam_type, e.exam_mode, e.academic_year, e.subject, e.full_marks_written, e.full_marks_viva, e.teacher_id, e.estatus,a.fullname
              FROM exams e
              JOIN rssimyaccount_members a ON e.teacher_id = a.associatenumber";

    // Initialize parameters array
    $params = [];

    // Initialize conditions array
    $conditions = [];

    // Check if any filter value is provided
    $filterProvided = false;

    // Check if exam_id is available
    if (isset($_GET['exam_id']) && !empty($_GET['exam_id'])) {
        $exam_id = $_GET['exam_id'];
        $conditions[] = "e.exam_id = $" . (count($params) + 1);
        $params[] = $exam_id;
        $filterProvided = true;
    }

    if (isset($_GET['exam_type']) && !empty($_GET['exam_type'])) {
        $exam_type = $_GET['exam_type'];
        $conditions[] = "e.exam_type = $" . (count($params) + 1);
        $params[] = $exam_type;
        $filterProvided = true;
    }

    if (isset($_GET['academic_year']) && !empty($_GET['academic_year'])) {
        $academic_year = $_GET['academic_year'];
        $conditions[] = "e.academic_year = $" . (count($params) + 1);
        $params[] = $academic_year;
        $filterProvided = true;
    }

    if (isset($_GET['subject']) && !empty($_GET['subject'])) {
        $subject = $_GET['subject'];
        $conditions[] = "e.subject = $" . (count($params) + 1);
        $params[] = $subject;
        $filterProvided = true;
    }

    // Check if the user is not an admin
    if ($role !== 'Admin') {
        // For non-Admin, filter by teacher_id
        $conditions[] = "e.teacher_id = $" . (count($params) + 1);
        $params[] = $associatenumber;
    }

    // Check if the user is an admin and a teacher_id filter is provided
    if ($role === 'Admin' && isset($_GET['teacher_id']) && !empty($_GET['teacher_id'])) {
        $teacher_id = $_GET['teacher_id'];
        $conditions[] = "e.teacher_id = $" . (count($params) + 1);
        $params[] = $teacher_id;
        $filterProvided = true;
    }

    // Only execute the query if a filter is provided
    if ($filterProvided) {
        // Add WHERE clause if conditions are available
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        // Execute the query
        $result = pg_query_params($con, $query, $params);

        if (!$result) {
            die("Error in SQL query: " . pg_last_error());
        }

        // Fetch results
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
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Exam Allotment</title>

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
            <h1>Exam Allotment</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Academic</li>
                    <li class="breadcrumb-item active">Exam Allotment</li>
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
                                            <select class="form-select" id="subject" name="subject">
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
                                        <?php if ($role == 'Admin') { ?>
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
                                        <?php } ?>
                                        <div id="filter-checks">
                                            <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_GET['is_user'])) echo "checked='checked'"; ?> />
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
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Exam ID</th>
                                                    <th>Exam type</th>
                                                    <th>Academic year</th>
                                                    <th>Exam mode</th>
                                                    <th>Subject</th>
                                                    <th>Full Marks</th>
                                                    <th>Assigned to</th>
                                                    <?php if ($role == 'Admin') : ?>
                                                        <th>Unlink</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($results as $row) : ?>
                                                    <tr>
                                                        <td><?= $serialNumber++ ?></td> <!-- Display and increment serial number -->
                                                        <td><a href="http://localhost:8082/rssi-member/exam_marks_upload.php?exam_id=<?php echo htmlspecialchars($row['exam_id']); ?>"><?php echo htmlspecialchars($row['exam_id']); ?></a></td>
                                                        <td><?php echo htmlspecialchars($row['exam_type']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['exam_mode']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
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

                                                        <td><?php echo htmlspecialchars($row['teacher_id']); ?></td>

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
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php elseif (empty($_GET['exam_type']) && !isset($_GET['is_user'])) : ?>
                                    <p class="mt-4">Please select exam type to fetch data.</p>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
</body>

</html>