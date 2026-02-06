<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$id = $_GET['id'] ?? '';

// Fetch existing schedule
$schedule = null;
if (!empty($id)) {
    $query = "SELECT * FROM associate_schedule_v2 WHERE id = $1";
    $result = pg_query_params($con, $query, [$id]);
    $schedule = pg_fetch_assoc($result);
}

if (!$schedule) {
    echo "<script>alert('Schedule not found'); window.location.href='view_shift.php';</script>";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workday = $_POST['workday'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $reporting_time = $_POST['reporting_time'] ?? '';
    $exit_time = $_POST['exit_time'] ?? '';

    $update_query = "UPDATE associate_schedule_v2 
                    SET workday = $1, 
                        start_date = $2, 
                        reporting_time = $3, 
                        exit_time = $4,
                        updated_at = CURRENT_TIMESTAMP,
                        submitted_by = $5
                    WHERE id = $6";

    $result = pg_query_params($con, $update_query, [
        $workday,
        $start_date,
        $reporting_time,
        $exit_time,
        $associatenumber,
        $id
    ]);

    if ($result) {
        echo "<script>alert('Schedule updated successfully'); window.location.href='view_shift.php';</script>";
        exit;
    } else {
        $error = pg_last_error($con);
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <!-- Add your header includes -->
    <?php include 'includes/meta.php' ?>
    <!-- Add necessary CSS/JS includes -->
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Day</label>
                                        <select class="form-select" name="workday" required>
                                            <option value="Mon" <?php echo $schedule['workday'] == 'Mon' ? 'selected' : ''; ?>>Monday</option>
                                            <option value="Tue" <?php echo $schedule['workday'] == 'Tue' ? 'selected' : ''; ?>>Tuesday</option>
                                            <option value="Wed" <?php echo $schedule['workday'] == 'Wed' ? 'selected' : ''; ?>>Wednesday</option>
                                            <option value="Thu" <?php echo $schedule['workday'] == 'Thu' ? 'selected' : ''; ?>>Thursday</option>
                                            <option value="Fri" <?php echo $schedule['workday'] == 'Fri' ? 'selected' : ''; ?>>Friday</option>
                                            <option value="Sat" <?php echo $schedule['workday'] == 'Sat' ? 'selected' : ''; ?>>Saturday</option>
                                            <option value="Sun" <?php echo $schedule['workday'] == 'Sun' ? 'selected' : ''; ?>>Sunday</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Associate</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($schedule['associate_number']); ?>" readonly>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" name="start_date"
                                            value="<?php echo $schedule['start_date']; ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Start Time</label>
                                        <input type="time" class="form-control" name="reporting_time"
                                            value="<?php echo substr($schedule['reporting_time'], 0, 5); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">End Time</label>
                                        <input type="time" class="form-control" name="exit_time"
                                            value="<?php echo substr($schedule['exit_time'], 0, 5); ?>" required>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <a href="view_shift.php" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Update Schedule</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>

</html>