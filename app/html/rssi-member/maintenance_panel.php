<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$directory_path = '/var/www/html/rssi-member/';
$page_files = array_diff(scandir($directory_path), array('.', '..'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maintenance_status = $_POST['maintenance'] ?? [];
    $end_dates = $_POST['end_date'] ?? [];

    // Fetch current maintenance data for comparison
    $maintenanceData = [];
    $result = pg_query($con, "SELECT * FROM active_maintenance");
    while ($row = pg_fetch_assoc($result)) {
        $maintenanceData[$row['page_name']] = $row;
    }

    foreach ($page_files as $pageName) {
        $is_under_maintenance = array_key_exists($pageName, $maintenance_status) ? 'true' : 'false';
        $end_date = !empty($end_dates[$pageName]) ? $end_dates[$pageName] : null;

        // Fetch the current maintenance status from the database
        $current_status = isset($maintenanceData[$pageName]) && $maintenanceData[$pageName]['is_under_maintenance'] === 't' ? 'true' : 'false';

        if ($is_under_maintenance === 'true') {
            // Insert or update active maintenance record only if under maintenance
            $sql = "INSERT INTO active_maintenance (page_name, is_under_maintenance, start_date, end_date)
                    VALUES ($1, $2, CURRENT_TIMESTAMP, $3)
                    ON CONFLICT (page_name) 
                    DO UPDATE SET 
                        is_under_maintenance = EXCLUDED.is_under_maintenance, 
                        end_date = EXCLUDED.end_date;";
            pg_query_params($con, $sql, [$pageName, $is_under_maintenance, $end_date]);
        } else {
            // If maintenance is being turned off (i.e., status changes from true to false)
            if ($current_status === 'true' && $is_under_maintenance === 'false') {
                // Move completed maintenance records to maintenance_log before deletion
                $archiveQuery = "INSERT INTO maintenance_log (page_name, start_date, end_date)
                                 SELECT page_name, start_date, CURRENT_TIMESTAMP 
                                 FROM active_maintenance 
                                 WHERE page_name = $1;";
                pg_query_params($con, $archiveQuery, [$pageName]);

                // Delete the record from active_maintenance
                $deleteQuery = "DELETE FROM active_maintenance WHERE page_name = $1;";
                pg_query_params($con, $deleteQuery, [$pageName]);
            }
        }
    }

    echo "<script>alert('Maintenance status updated successfully!'); window.location.href = 'maintenance_panel.php';</script>";
    exit;
}

// Fetch existing maintenance statuses for display
$maintenanceData = [];
$result = pg_query($con, "SELECT * FROM active_maintenance");
while ($row = pg_fetch_assoc($result)) {
    $maintenanceData[$row['page_name']] = $row;
}

// Define the edit mode variable for the frontend
$isEditMode = isset($_GET['edit']) && $_GET['edit'] == 'true'; // Example condition to toggle edit mode
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Panel</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
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
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Maintenance Panel</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Maintenance Panel</li>
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
                                <form method="POST" action="#">
                                    <fieldset>
                                        <table id="maintenanceTable" class="table">
                                            <thead>
                                                <tr>
                                                    <th>Page Name</th>
                                                    <th>Under Maintenance</th>
                                                    <th>End Date & Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($page_files as $file):
                                                    $pageName = htmlspecialchars($file);
                                                    $isChecked = isset($maintenanceData[$pageName]) && $maintenanceData[$pageName]['is_under_maintenance'] === 't' ? 'checked' : '';
                                                    $endDate = $maintenanceData[$pageName]['end_date'] ?? '';
                                                ?>
                                                    <tr>
                                                        <td><?php echo $pageName; ?></td>
                                                        <td>
                                                            <?php if ($isEditMode): ?>
                                                                <input type="checkbox" class="form-check-input" name="maintenance[<?php echo $pageName; ?>]" value="1" <?php echo $isChecked; ?>>
                                                            <?php else: ?>
                                                                <span><?php echo $isChecked ? 'Yes' : 'No'; ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($isEditMode): ?>
                                                                <input type="datetime-local" name="end_date[<?php echo $pageName; ?>]" value="<?php echo $endDate; ?>">
                                                            <?php else: ?>
                                                                <span><?php echo $endDate; ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>

                                        <!-- Form Row -->
                                        <div class="row align-items-center">
                                            <!-- Edit/Save Button (left side) -->
                                            <div class="col-6 d-flex">
                                                <button type="button" id="toggleButton" class="btn btn-warning me-2" onclick="toggleEdit()">Edit</button>
                                                <!-- Save/Submit Button -->
                                                <button type="submit" id="submitButton" class="btn btn-primary" style="display:none;">Save</button>
                                            </div>
                                        </div>
                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>
    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            $('#maintenanceTable').DataTable();
        });

        let isEditMode = <?php echo $isEditMode ? 'true' : 'false'; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggleButton');
            const submitButton = document.getElementById('submitButton');
            const table = document.getElementById('maintenanceTable');

            // Function to toggle between editable and non-editable modes
            toggleButton.addEventListener('click', function() {
                isEditMode = !isEditMode;
                toggleFormMode();
            });

            // Function to update the form mode (editable or non-editable)
            function toggleFormMode() {
                const rows = table.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const maintenanceCell = row.querySelector('td:nth-child(2)');
                    const endDateCell = row.querySelector('td:nth-child(3)');

                    const pageName = row.querySelector('td:first-child').textContent.trim();

                    if (isEditMode) {
                        // Switch to edit mode: Replace spans with inputs
                        // Maintenance Checkbox
                        const maintenanceSpan = maintenanceCell.querySelector('span');
                        if (maintenanceSpan) {
                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.classList.add('form-check-input');
                            checkbox.name = `maintenance[${pageName}]`;
                            checkbox.value = '1';
                            checkbox.checked = maintenanceSpan.textContent.trim() === 'Yes';
                            maintenanceSpan.replaceWith(checkbox);
                        }

                        // End Date Input
                        const endDateSpan = endDateCell.querySelector('span');
                        if (endDateSpan) {
                            const dateInput = document.createElement('input');
                            dateInput.type = 'datetime-local';
                            dateInput.name = `end_date[${pageName}]`;
                            dateInput.value = endDateSpan.textContent.trim();
                            endDateSpan.replaceWith(dateInput);
                        }
                    } else {
                        // Switch to non-edit mode: Replace inputs with spans
                        // Maintenance Checkbox
                        const maintenanceCheckbox = maintenanceCell.querySelector('input[type="checkbox"]');
                        if (maintenanceCheckbox) {
                            const span = document.createElement('span');
                            span.textContent = maintenanceCheckbox.checked ? 'Yes' : 'No';
                            maintenanceCheckbox.replaceWith(span);
                        }

                        // End Date Input
                        const endDateInput = endDateCell.querySelector('input[type="datetime-local"]');
                        if (endDateInput) {
                            const span = document.createElement('span');
                            span.textContent = endDateInput.value;
                            endDateInput.replaceWith(span);
                        }
                    }
                });

                // Update button visibility
                toggleButton.style.display = isEditMode ? 'none' : 'inline-block';
                submitButton.style.display = isEditMode ? 'inline-block' : 'none';
            }

            // Initial toggle on page load
            toggleFormMode();
        });
    </script>
</body>

</html>