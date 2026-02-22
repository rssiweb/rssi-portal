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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = pg_escape_string($con, $_POST['category']);
    $class_days = pg_escape_string($con, $_POST['class_days']);
    $effectiveFrom = pg_escape_string($con, $_POST['effective_from']);

    // Begin transaction
    pg_query($con, "BEGIN");

    // Update previous active record's effective_to
    $updateQuery = "UPDATE student_class_days 
                    SET effective_to = (DATE '$effectiveFrom' - INTERVAL '1 day')::date
                    WHERE category = '$category' AND effective_to IS NULL";
    pg_query($con, $updateQuery);

    // Insert new record
    $insertQuery = "INSERT INTO student_class_days (category, class_days, effective_from)
                    VALUES ('$category', '$class_days', '$effectiveFrom')";
    $result = pg_query($con, $insertQuery);

    if ($result) {
        pg_query($con, "COMMIT");
        $_SESSION['success_message'] = "Class days updated successfully!";
    } else {
        pg_query($con, "ROLLBACK");
        $_SESSION['error_message'] = "Failed to update class days: " . pg_last_error($con);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch current class days settings
$query = "SELECT * FROM student_class_days ORDER BY category, effective_from DESC";
$result = pg_query($con, $query);
$classDaysSettings = pg_fetch_all($result);

// Fetch active class days settings
$activeQuery = "SELECT * FROM student_class_days WHERE effective_to IS NULL ORDER BY category";
$activeResult = pg_query($con, $activeQuery);
$activeSettings = pg_fetch_all($activeResult);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include 'includes/meta.php' ?>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        .day-checkbox {
            display: inline-block;
            margin-right: 15px;
        }

        .current-settings {
            margin-top: 30px;
        }
    </style>
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
                            <div class="container mt-4">
                                <!-- <h2>Manage Category Schedule</h2> -->

                                <?php if (isset($_SESSION['success_message'])): ?>
                                    <div class="alert alert-success"><?= $_SESSION['success_message'] ?></div>
                                    <?php unset($_SESSION['success_message']); ?>
                                <?php endif; ?>

                                <?php if (isset($_SESSION['error_message'])): ?>
                                    <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
                                    <?php unset($_SESSION['error_message']); ?>
                                <?php endif; ?>

                                <form method="POST">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="category" class="form-label">Category</label>
                                            <select class="form-select" id="category" name="category" required>
                                                <option value="">Select Category</option>
                                                <option value="LG1">LG1</option>
                                                <option value="LG2-A">LG2-A</option>
                                                <option value="LG2-B">LG2-B</option>
                                                <option value="LG2-C">LG2-C</option>
                                                <option value="LG3">LG3</option>
                                                <option value="LG4">LG4</option>
                                                <!-- Add other categories as needed -->
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="effective_from" class="form-label">Effective From</label>
                                            <input type="date" class="form-control" id="effective_from" name="effective_from" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Class Days</label><br>
                                            <div class="day-checkbox">
                                                <input class="form-check-input" type="checkbox" id="mon" name="class_days[]" value="Mon">
                                                <label for="mon">Mon</label>
                                            </div>
                                            <div class="day-checkbox">
                                                <input class="form-check-input" type="checkbox" id="tue" name="class_days[]" value="Tue">
                                                <label for="tue">Tue</label>
                                            </div>
                                            <div class="day-checkbox">
                                                <input class="form-check-input" type="checkbox" id="wed" name="class_days[]" value="Wed">
                                                <label for="wed">Wed</label>
                                            </div>
                                            <div class="day-checkbox">
                                                <input class="form-check-input" type="checkbox" id="thu" name="class_days[]" value="Thu">
                                                <label for="thu">Thu</label>
                                            </div>
                                            <div class="day-checkbox">
                                                <input class="form-check-input" type="checkbox" id="fri" name="class_days[]" value="Fri">
                                                <label for="fri">Fri</label>
                                            </div>
                                            <div class="day-checkbox">
                                                <input class="form-check-input" type="checkbox" id="sat" name="class_days[]" value="Sat">
                                                <label for="sat">Sat</label>
                                            </div>
                                            <div class="day-checkbox">
                                                <input class="form-check-input" type="checkbox" id="sun" name="class_days[]" value="Sun">
                                                <label for="sun">Sun</label>
                                            </div>
                                            <input type="hidden" id="class_days" name="class_days">
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Save Settings</button>
                                </form>

                                <div class="current-settings">
                                    <h4>Current Active Settings</h4>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Class Days</th>
                                                <th>Effective From</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($activeSettings as $setting): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($setting['category']) ?></td>
                                                    <td><?= htmlspecialchars($setting['class_days']) ?></td>
                                                    <td><?= htmlspecialchars($setting['effective_from']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="historical-settings mt-4">
                                    <h4>Historical Settings</h4>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Class Days</th>
                                                <th>Effective From</th>
                                                <th>Effective To</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($classDaysSettings as $setting): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($setting['category']) ?></td>
                                                    <td><?= htmlspecialchars($setting['class_days']) ?></td>
                                                    <td><?= htmlspecialchars($setting['effective_from']) ?></td>
                                                    <td><?= isset($setting['effective_to']) ? htmlspecialchars($setting['effective_to']) : 'null'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        // Set today's date as default for effective_from
        document.getElementById('effective_from').valueAsDate = new Date();

        // Handle class_days checkbox to hidden field
        const checkboxes = document.querySelectorAll('input[name="class_days[]"]');
        const hiddenField = document.getElementById('class_days');

        function updateClassDays() {
            const selectedDays = Array.from(checkboxes)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.value)
                .join(',');
            hiddenField.value = selectedDays;
        }

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateClassDays);
        });
    </script>
</body>

</html>