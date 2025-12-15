<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Check authentication
if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Initialize variables
$students = [];
$action_result = '';

// Handle form submission for syncing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['sync_selected'])) {
        $selected_students = $_POST['selected_students'] ?? [];

        if (empty($selected_students)) {
            $action_result = '<div class="alert alert-warning">No students selected for syncing.</div>';
        } else {
            $success_count = 0;
            $failed_count = 0;
            $errors = [];

            // Start transaction
            pg_query($con, "BEGIN");

            foreach ($selected_students as $student_id) {
                // Get the current active plan from student_category_history
                $active_plan_query = "
                    SELECT category_type, class 
                    FROM student_category_history 
                    WHERE student_id = '$student_id' 
                    AND is_valid = true 
                    AND (effective_until IS NULL OR effective_until >= CURRENT_DATE)
                    AND effective_from <= CURRENT_DATE
                    ORDER BY effective_from DESC, created_at DESC 
                    LIMIT 1
                ";

                $active_plan_result = pg_query($con, $active_plan_query);

                if ($active_plan_result && pg_num_rows($active_plan_result) > 0) {
                    $active_plan = pg_fetch_assoc($active_plan_result);
                    $new_category_type = $active_plan['category_type'];
                    $new_class = $active_plan['class'];

                    // Update rssimyprofile_student
                    $update_query = "
                        UPDATE rssimyprofile_student 
                        SET type_of_admission = '$new_category_type', 
                            class = '$new_class',
                            updated_by = '$associatenumber',
                            updated_on = NOW()
                        WHERE student_id = '$student_id'
                    ";

                    if (pg_query($con, $update_query)) {
                        $success_count++;

                        // Log the sync action
                        $log_query = "
                            INSERT INTO student_sync_log 
                            (student_id, old_category_type, new_category_type, old_class, new_class, synced_by, synced_at)
                            SELECT 
                                '$student_id',
                                s.type_of_admission,
                                '$new_category_type',
                                s.class,
                                '$new_class',
                                '$associatenumber',
                                NOW()
                            FROM rssimyprofile_student s
                            WHERE s.student_id = '$student_id'
                        ";
                        pg_query($con, $log_query);
                    } else {
                        $failed_count++;
                        $errors[] = "Failed to update student ID: $student_id";
                    }
                } else {
                    $failed_count++;
                    $errors[] = "No active plan found for student ID: $student_id";
                }
            }

            // Commit transaction
            if (pg_query($con, "COMMIT")) {
                $action_result = '<div class="alert alert-success">';
                $action_result .= "Successfully synced $success_count student(s).";
                if ($failed_count > 0) {
                    $action_result .= "<br>$failed_count student(s) failed to sync.";
                    if (!empty($errors)) {
                        $action_result .= "<br>Errors: " . implode(', ', $errors);
                    }
                }
                $action_result .= '</div>';
            } else {
                pg_query($con, "ROLLBACK");
                $action_result = '<div class="alert alert-danger">Transaction failed. No changes were made.</div>';
            }
        }
    }
}

// Fetch students with mismatched data
$query = "
    SELECT 
        s.student_id,
        s.studentname,
        s.type_of_admission AS current_category_type,
        s.class AS current_class,
        s.filterstatus,
        s.scode,
        h.category_type AS history_category_type,
        h.class AS history_class,
        h.effective_from,
        h.effective_until
    FROM rssimyprofile_student s
    INNER JOIN (
        SELECT DISTINCT ON (student_id) 
            student_id,
            category_type,
            class,
            effective_from,
            effective_until
        FROM student_category_history 
        WHERE is_valid = true 
        AND (effective_until IS NULL OR effective_until >= CURRENT_DATE)
        AND effective_from <= CURRENT_DATE
        ORDER BY student_id, effective_from DESC, created_at DESC
    ) h ON s.student_id = h.student_id
    WHERE (s.type_of_admission != h.category_type OR s.class != h.class)
    ORDER BY s.studentname
";

$result = pg_query($con, $query);

if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile Sync</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #31536C;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }

        .table th {
            background-color: #f8f9fa;
        }

        .mismatch {
            background-color: #fff3cd !important;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 50rem;
        }

        .status-active {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #842029;
        }

        .btn-sync {
            background-color: #198754;
            border-color: #198754;
        }

        .btn-sync:hover {
            background-color: #157347;
            border-color: #146c43;
        }

        #selectAll {
            margin-right: 10px;
        }
    </style>
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
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Student Profile Sync</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Fee Portal</a></li>
                    <li class="breadcrumb-item active">SPS</li>
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
                                <div class="row">
                                    <div class="col-12">
                                        <?php echo $action_result; ?>

                                        <div class="alert alert-info mt-3">
                                            <h5><i class="bi bi-info-circle"></i> About This Page</h5>
                                            <p>This page shows students whose current profile data (class and access category) differs from their active plan in the plan history. You can select students and sync their profile to match the active plan.</p>
                                            <p><strong>Total mismatched students found:</strong> <?php echo count($students); ?></p>
                                        </div>

                                        <?php if (count($students) > 0): ?>
                                            <form method="POST" action="" id="syncForm">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div>
                                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                                        <label for="selectAll" class="form-check-label">Select All</label>
                                                    </div>
                                                    <div>
                                                        <button type="submit" name="sync_selected" class="btn btn-sync" onclick="return confirmSync()">
                                                            <i class="bi bi-arrow-repeat"></i> Sync Selected Students
                                                        </button>
                                                        <a href="student.php" class="btn btn-secondary">
                                                            <i class="bi bi-arrow-left"></i> Back to Student Database
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-hover" id="studentsTable">
                                                        <thead>
                                                            <tr>
                                                                <th width="50">Select</th>
                                                                <th>Student ID</th>
                                                                <th>Student Name</th>
                                                                <th>Status</th>
                                                                <th>Current Profile</th>
                                                                <th>Active Plan History</th>
                                                                <th>Effective Dates</th>
                                                                <th>Mismatch</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($students as $student): ?>
                                                                <?php
                                                                $mismatch_category = $student['current_category_type'] != $student['history_category_type'];
                                                                $mismatch_class = $student['current_class'] != $student['history_class'];
                                                                $has_mismatch = $mismatch_category || $mismatch_class;

                                                                $status_class = $student['filterstatus'] == 'Active' ? 'status-active' : 'status-inactive';
                                                                ?>
                                                                <tr class="<?php echo $has_mismatch ? 'mismatch' : ''; ?>">
                                                                    <td>
                                                                        <input type="checkbox" name="selected_students[]" value="<?php echo $student['student_id']; ?>" class="form-check-input student-checkbox">
                                                                    </td>
                                                                    <td>
                                                                        <strong><?php echo htmlspecialchars($student['student_id']); ?></strong><br>
                                                                        <small class="text-muted"><?php echo htmlspecialchars($student['scode'] ?? 'No Scode'); ?></small>
                                                                    </td>
                                                                    <td><?php echo htmlspecialchars($student['studentname']); ?></td>
                                                                    <td>
                                                                        <span class="status-badge <?php echo $status_class; ?>">
                                                                            <?php echo htmlspecialchars($student['filterstatus']); ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <div class="current-values">
                                                                            <strong>Access Category:</strong><br>
                                                                            <span class="<?php echo $mismatch_category ? 'text-danger' : 'text-success'; ?>">
                                                                                <i class="bi bi-<?php echo $mismatch_category ? 'x-circle' : 'check-circle'; ?>"></i>
                                                                                <?php echo htmlspecialchars($student['current_category_type']); ?>
                                                                            </span><br>
                                                                            <strong>Class:</strong><br>
                                                                            <span class="<?php echo $mismatch_class ? 'text-danger' : 'text-success'; ?>">
                                                                                <i class="bi bi-<?php echo $mismatch_class ? 'x-circle' : 'check-circle'; ?>"></i>
                                                                                <?php echo htmlspecialchars($student['current_class']); ?>
                                                                            </span>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div class="history-values">
                                                                            <strong>Access Category:</strong><br>
                                                                            <span class="text-primary">
                                                                                <?php echo htmlspecialchars($student['history_category_type']); ?>
                                                                            </span><br>
                                                                            <strong>Class:</strong><br>
                                                                            <span class="text-primary">
                                                                                <?php echo htmlspecialchars($student['history_class']); ?>
                                                                            </span>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <small>
                                                                            <strong>From:</strong> <?php echo date('d/m/Y', strtotime($student['effective_from'])); ?><br>
                                                                            <strong>Until:</strong>
                                                                            <?php
                                                                            echo $student['effective_until']
                                                                                ? date('d/m/Y', strtotime($student['effective_until']))
                                                                                : '<span class="text-success">Ongoing</span>';
                                                                            ?>
                                                                        </small>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($has_mismatch): ?>
                                                                            <span class="badge bg-warning text-dark">
                                                                                <i class="bi bi-exclamation-triangle"></i>
                                                                                <?php
                                                                                $mismatches = [];
                                                                                if ($mismatch_category) $mismatches[] = "Category";
                                                                                if ($mismatch_class) $mismatches[] = "Class";
                                                                                echo implode(", ", $mismatches);
                                                                                ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-success">
                                                                                <i class="bi bi-check-circle"></i> In Sync
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <div class="text-center py-5">
                                                <i class="bi bi-check-circle-fill text-success" style="font-size: 48px;"></i>
                                                <h3 class="mt-3">All Students Are In Sync!</h3>
                                                <p class="text-muted">No mismatches found between student profiles and active plans.</p>
                                                <a href="student.php" class="btn btn-primary">
                                                    <i class="bi bi-arrow-left"></i> Back to Student Database
                                                </a>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (count($students) > 0): ?>
                                            <div class="card">
                                                <div class="card-header">
                                                    <h5><i class="bi bi-clock-history"></i> Recent Sync History</h5>
                                                </div>
                                                <div class="card-body">
                                                    <?php
                                                    // Fetch recent sync history
                                                    $history_query = "
                                                        SELECT 
                                                            sl.*,
                                                            s.studentname
                                                        FROM student_sync_log sl
                                                        JOIN rssimyprofile_student s ON sl.student_id = s.student_id
                                                        ORDER BY sl.synced_at DESC
                                                        LIMIT 10
                                                    ";

                                                    $history_result = pg_query($con, $history_query);

                                                    if ($history_result && pg_num_rows($history_result) > 0):
                                                    ?>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Date</th>
                                                                        <th>Student</th>
                                                                        <th>Changes Made</th>
                                                                        <th>Synced By</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php while ($history = pg_fetch_assoc($history_result)): ?>
                                                                        <tr>
                                                                            <td><?php echo date('d/m/Y H:i', strtotime($history['synced_at'])); ?></td>
                                                                            <td>
                                                                                <?php echo htmlspecialchars($history['studentname']); ?><br>
                                                                                <small class="text-muted"><?php echo $history['student_id']; ?></small>
                                                                            </td>
                                                                            <td>
                                                                                <?php
                                                                                $changes = [];
                                                                                if ($history['old_category_type'] != $history['new_category_type']) {
                                                                                    $changes[] = "Category: " . $history['old_category_type'] . " → " . $history['new_category_type'];
                                                                                }
                                                                                if ($history['old_class'] != $history['new_class']) {
                                                                                    $changes[] = "Class: " . $history['old_class'] . " → " . $history['new_class'];
                                                                                }
                                                                                echo implode('<br>', $changes);
                                                                                ?>
                                                                            </td>
                                                                            <td><?php echo htmlspecialchars($history['synced_by']); ?></td>
                                                                        </tr>
                                                                    <?php endwhile; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php else: ?>
                                                        <p class="text-muted text-center">No sync history found.</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
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
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#studentsTable').DataTable({
                "pageLength": 25,
                "order": [
                    [1, 'asc']
                ],
                "language": {
                    "search": "Search students:",
                    "lengthMenu": "Show _MENU_ students per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ mismatched students",
                    "infoEmpty": "No mismatched students found",
                    "infoFiltered": "(filtered from _MAX_ total students)"
                }
            });

            // Select All functionality
            $('#selectAll').on('change', function() {
                $('.student-checkbox').prop('checked', this.checked);
            });

            // Update select all checkbox when individual checkboxes change
            $('.student-checkbox').on('change', function() {
                if ($('.student-checkbox:checked').length === $('.student-checkbox').length) {
                    $('#selectAll').prop('checked', true);
                } else {
                    $('#selectAll').prop('checked', false);
                }
            });
        });

        function confirmSync() {
            const selectedCount = $('.student-checkbox:checked').length;

            if (selectedCount === 0) {
                alert('Please select at least one student to sync.');
                return false;
            }

            return confirm(`Are you sure you want to sync ${selectedCount} student(s)?\n\nThis will update their class and access category in the main student profile to match the active plan history.`);
        }

        // Refresh page after 5 minutes to check for new mismatches
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutes
    </script>
</body>

</html>