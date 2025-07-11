<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

$user_id = $associatenumber;
$user_role = $role;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_admission'])) {
        // Extract and normalize values
        $name = $_POST['name'];
        $class_id = $_POST['class_id'] ?: null;
        $mobile = $_POST['mobile'];
        $current_address = $_POST['current_address'] ?: null;
        $aadhar_submitted_by = $_POST['aadhar_submitted_by'] ?: null;
        $aadhar_number = $_POST['aadhar_number'] ?: null;
        $previous_school = $_POST['previous_school'] ?: null;
        $family_member_count = $_POST['family_member_count'] ?: null;
        $monthly_income = $_POST['monthly_income'] ?: null;
        $visit_type = $_POST['visit_type'];
        $fees_submission_date = $_POST['fees_submission_date'] ?: null;
        $total_fee = $_POST['total_fee'] ?: null;
        $deposited_amount = $_POST['deposited_amount'] ?: null;
        $due_amount = $total_fee - $deposited_amount;
        $fees_month = $_POST['fees_month'] ?: null;
        $payment_mode = $_POST['payment_mode'] ?: null;
        $transaction_id = $_POST['transaction_id'] ?: null;
        $remarks = $_POST['remarks'] ?: null;

        // Use parameterized query
        $query = "
            INSERT INTO parent_admissions (
                name, class_id, mobile, current_address, aadhar_submitted_by, aadhar_number,
                previous_school, family_member_count, monthly_income, visit_type, fees_submission_date, 
                total_fee, deposited_amount, due_amount, fees_month, payment_mode, 
                transaction_id, remarks, created_by
            ) VALUES (
                $1, $2, $3, $4, $5, $6,
                $7, $8, $9, $10, $11,
                $12, $13, $14, $15, $16,
                $17, $18, $19
            ) RETURNING id
        ";

        $params = [
            $name,
            $class_id,
            $mobile,
            $current_address,
            $aadhar_submitted_by,
            $aadhar_number,
            $previous_school,
            $family_member_count,
            $monthly_income,
            $visit_type,
            $fees_submission_date,
            $total_fee,
            $deposited_amount,
            $due_amount,
            $fees_month,
            $payment_mode,
            $transaction_id,
            $remarks,
            $user_id
        ];

        $result = pg_query_params($con, $query, $params);

        if ($result) {
            $_SESSION['success_message'] = "Record submitted successfully!";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $_SESSION['error_message'] = "Error submitting record: " . pg_last_error($con);
        }
    } elseif (isset($_POST['mark_completed'])) {
        $record_id = $_POST['record_id'];
        $query = "UPDATE parent_admissions SET system_process_completed = TRUE WHERE id = $1 AND created_by = $2";
        $result = pg_query_params($con, $query, [$record_id, $user_id]);

        if ($result) {
            $_SESSION['success_message'] = "Record marked as completed!";
        } else {
            $_SESSION['error_message'] = "Error updating record: " . pg_last_error($con);
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } elseif (isset($_POST['close_record'])) {
        if ($user_role == 'Admin') {
            $record_id = $_POST['record_id'];
            $close_remarks = $_POST['close_remarks'] ?: null;
            $query = "UPDATE parent_admissions SET is_closed = TRUE, closed_by = $1, closed_at = NOW(), close_remarks = $2 WHERE id = $3";
            $result = pg_query_params($con, $query, [$user_id, $close_remarks, $record_id]);

            if ($result) {
                $_SESSION['success_message'] = "Record closed successfully!";
            } else {
                $_SESSION['error_message'] = "Error closing record: " . pg_last_error($con);
            }
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    } elseif (isset($_POST['update_record'])) {
        $record_id = $_POST['record_id'];
        $name = $_POST['name'];
        $class_id = $_POST['class_id'] ?: null;
        $mobile = $_POST['mobile'];
        $current_address = $_POST['current_address'] ?: null;
        $aadhar_submitted_by = $_POST['aadhar_submitted_by'] ?: null;
        $aadhar_number = $_POST['aadhar_number'] ?: null;
        $previous_school = $_POST['previous_school'] ?: null;
        $family_member_count = $_POST['family_member_count'] ?: null;
        $monthly_income = $_POST['monthly_income'] ?: null;
        $visit_type = $_POST['visit_type'];
        $fees_submission_date = $_POST['fees_submission_date'] ?: null;
        $total_fee = $_POST['total_fee'] ?: null;
        $deposited_amount = $_POST['deposited_amount'] ?: null;
        $due_amount = $total_fee - $deposited_amount;
        $fees_month = $_POST['fees_month'] ?: null;
        $payment_mode = $_POST['payment_mode'] ?: null;
        $transaction_id = $_POST['transaction_id'] ?: null;
        $remarks = $_POST['remarks'] ?: null;

        $query = "
            UPDATE parent_admissions SET
                name = $1, class_id = $2, mobile = $3, current_address = $4, 
                aadhar_submitted_by = $5, aadhar_number = $6, previous_school = $7,
                family_member_count = $8, monthly_income = $9, visit_type = $10,
                fees_submission_date = $11, total_fee = $12, deposited_amount = $13,
                due_amount = $14, fees_month = $15, payment_mode = $16,
                transaction_id = $17, remarks = $18, updated_at = NOW()
            WHERE id = $19 AND created_by = $20 AND is_closed = FALSE
        ";

        $params = [
            $name,
            $class_id,
            $mobile,
            $current_address,
            $aadhar_submitted_by,
            $aadhar_number,
            $previous_school,
            $family_member_count,
            $monthly_income,
            $visit_type,
            $fees_submission_date,
            $total_fee,
            $deposited_amount,
            $due_amount,
            $fees_month,
            $payment_mode,
            $transaction_id,
            $remarks,
            $record_id,
            $user_id
        ];

        $result = pg_query_params($con, $query, $params);

        if ($result) {
            $_SESSION['success_message'] = "Record updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating record: " . pg_last_error($con);
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } elseif (isset($_POST['move_to_active'])) {
        if ($user_role == 'Admin') {
            $record_id = $_POST['record_id'];
            $query = "UPDATE parent_admissions SET system_process_completed = FALSE WHERE id = $1";
            $result = pg_query_params($con, $query, [$record_id]);

            if ($result) {
                $_SESSION['success_message'] = "Record moved back to Active status!";
            } else {
                $_SESSION['error_message'] = "Error updating record: " . pg_last_error($con);
            }
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

// Get classes for dropdown
$classes_result = pg_query($con, "SELECT id, class_name, value FROM school_classes ORDER BY value");
$classes = [];
while ($row = pg_fetch_assoc($classes_result)) {
    $classes[$row['id']] = $row['class_name'];
}

// Academic year filter
$current_year = date('Y');
$current_month = date('n');
$academic_year = ($current_month >= 4) ? $current_year : $current_year - 1;
$selected_academic_year = isset($_GET['academic_year']) ? intval($_GET['academic_year']) : $academic_year;

// Generate academic year options (last 5 years)
$academic_years = [];
for ($i = 0; $i < 5; $i++) {
    $year = $academic_year - $i;
    $academic_years[$year] = $year . '-' . ($year + 1);
}

// Academic year filter condition
$academic_year_condition = "";
$selected_tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';
$academic_year_filter_applied = isset($_GET['academic_year']);

if ($selected_tab != 'archive') {
    // For active and admin tabs, always filter by academic year
    $start_date = $selected_academic_year . '-04-01';
    $end_date = ($selected_academic_year + 1) . '-03-31';
    $academic_year_condition = "AND pa.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'";
}

// Get records based on status
$active_where = "pa.is_closed = FALSE AND (pa.system_process_completed = FALSE OR pa.system_process_completed IS NULL) $academic_year_condition";
$archive_where = "pa.is_closed = TRUE";

// Apply academic year filter to archive (same as other tabs)
if ($selected_tab == 'archive') {
    $start_date = $selected_academic_year . '-04-01';
    $end_date = ($selected_academic_year + 1) . '-03-31';
    $archive_where .= " AND pa.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'";
}

// Update the count query for archive to match the same conditions
$tabs['archive']['count'] = pg_fetch_result(pg_query($con, "SELECT COUNT(*) FROM parent_admissions pa WHERE $archive_where"), 0, 0);

$admin_where = "pa.is_closed = FALSE AND pa.system_process_completed = TRUE $academic_year_condition";

if ($user_role != 'Admin') {
    $admin_where .= " AND FALSE"; // Non-admins shouldn't see admin tab
}

// Pagination settings
$records_per_page = 3;
$current_page = isset($_GET['archive_page']) ? (int)$_GET['archive_page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

$tabs = [
    'active' => [
        'name' => 'Active',
        'where' => $active_where,
        'count' => pg_fetch_result(pg_query($con, "SELECT COUNT(*) FROM parent_admissions pa WHERE $active_where"), 0, 0)
    ],
    'admin' => [
        'name' => 'Admin Workflow',
        'where' => $admin_where,
        'count' => pg_fetch_result(pg_query($con, "SELECT COUNT(*) FROM parent_admissions pa WHERE $admin_where"), 0, 0)
    ],
    'archive' => [
        'name' => 'Archive',
        'where' => $archive_where,
        'count' => pg_fetch_result(pg_query($con, "SELECT COUNT(*) FROM parent_admissions pa WHERE $archive_where"), 0, 0)
    ]
];

$selected_tab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $tabs) ? $_GET['tab'] : 'active';

// Check if we're loading archive content via AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] == 'archive') {
    $current_page = isset($_GET['archive_page']) ? (int)$_GET['archive_page'] : 1;
    $offset = ($current_page - 1) * $records_per_page;

    // Get the selected academic year from the request
    $selected_academic_year = isset($_GET['academic_year']) ? intval($_GET['academic_year']) : $academic_year;

    // Build the archive WHERE clause with academic year filter
    $archive_where = "pa.is_closed = TRUE";
    $start_date = $selected_academic_year . '-04-01';
    $end_date = ($selected_academic_year + 1) . '-03-31';
    $archive_where .= " AND pa.created_at BETWEEN '$start_date' AND '$end_date 23:59:59'";

    $query = "SELECT pa.*, sc.class_name, sc.value, 
             creator.fullname as created_by_name,
             closer.fullname as closed_by_name
             FROM parent_admissions pa
             LEFT JOIN school_classes sc ON pa.class_id = sc.id
             LEFT JOIN rssimyaccount_members creator ON pa.created_by = creator.associatenumber
             LEFT JOIN rssimyaccount_members closer ON pa.closed_by = closer.associatenumber
             WHERE $archive_where
             ORDER BY pa.created_at DESC
             LIMIT $records_per_page OFFSET $offset";

    $result = pg_query($con, $query);

    // Get total count for pagination (using the same WHERE conditions)
    $count_query = "SELECT COUNT(*) FROM parent_admissions pa WHERE $archive_where";
    $total_records = pg_fetch_result(pg_query($con, $count_query), 0, 0);
    $total_pages = ceil($total_records / $records_per_page);

    if (pg_num_rows($result) == 0) {
        echo '<div class="alert alert-info">No archived records found.</div>';
    } else {
        while ($row = pg_fetch_assoc($result)) {
            echo '<div class="card record-card">';
            echo '<div class="card-body">';
            echo '<div class="d-flex justify-content-between align-items-start">';
            echo '<div>';
            echo '<h5 class="card-title">' . htmlspecialchars($row['name']) . '</h5>';
            echo '<p class="card-text mb-1"><small class="text-muted">Mobile: ' . htmlspecialchars($row['mobile']) . '</small></p>';
            if ($row['class_name']) {
                echo '<p class="card-text mb-1"><small class="text-muted">Class: ' . htmlspecialchars($row['class_name']) . ' (' . htmlspecialchars($row['value']) . ')</small></p>';
            }
            echo '<p class="card-text mb-1"><small class="text-muted">Visit Type: ' . ucfirst($row['visit_type']) . '</small></p>';
            echo '</div>';
            echo '<span class="badge status-closed">Closed</span>';
            echo '</div>';

            // Display additional information
            if ($row['visit_type'] == 'taking admission') {
                echo '<div class="row mt-2">';
                echo '<div class="col-md-4">';
                echo '<p class="mb-1 small"><strong>Family Info:</strong> ';
                echo 'Members: ' . ($row['family_member_count'] ?: 'N/A') . '<br>';
                echo 'Income: ' . ($row['monthly_income'] ? '₹' . $row['monthly_income'] : 'N/A') . '</p>';
                echo '</div>';
                echo '<div class="col-md-4">';
                echo '<p class="mb-1 small"><strong>Fees:</strong> ';
                echo 'Submitted: ' . ($row['fees_submission_date'] ? date('d M Y', strtotime($row['fees_submission_date'])) : 'N/A') . '<br>';
                echo 'Total: ' . ($row['total_fee'] ? '₹' . $row['total_fee'] : 'N/A') . '</p>';
                echo '</div>';
                echo '<div class="col-md-4">';
                echo '<p class="mb-1 small"><strong>Payment:</strong> ';
                echo 'Deposited: ' . ($row['deposited_amount'] ? '₹' . $row['deposited_amount'] : 'N/A') . '<br>';
                echo 'Due: ' . ($row['due_amount'] ? '₹' . $row['due_amount'] : 'N/A') . '<br>';
                echo 'Mode: ' . ($row['payment_mode'] ? ucfirst($row['payment_mode']) : 'N/A') . '</p>';
                echo '</div>';
                echo '</div>';
            }

            // Display remarks if exists
            if (!empty($row['remarks'])) {
                echo '<div class="alert alert-light mt-2 p-2">';
                echo '<strong>Remarks:</strong> ' . nl2br(htmlspecialchars($row['remarks']));
                echo '</div>';
            }

            echo '<div class="d-flex justify-content-between mt-2">';
            echo '<div>';
            echo '<small class="text-muted">Created by: ' . htmlspecialchars($row['created_by_name']) . ' on ' . date('d M Y H:i', strtotime($row['created_at'])) . '</small>';
            if ($row['closed_by_name']) {
                echo '<br><small class="text-muted">Closed by: ' . htmlspecialchars($row['closed_by_name']) . ' on ' . date('d M Y H:i', strtotime($row['closed_at'])) . '</small>';
            }
            echo '</div>';
            echo '</div>';

            echo '</div>';
            echo '</div>';
        }
        // Pagination controls
        echo '<nav aria-label="Archive pagination">';
        echo '<ul class="pagination justify-content-center mt-3">';

        // Previous button
        if ($current_page > 1) {
            echo '<li class="page-item">';
            echo '<a class="page-link" href="#" onclick="loadArchivePage(' . ($current_page - 1) . ')">Previous</a>';
            echo '</li>';
        } else {
            echo '<li class="page-item disabled">';
            echo '<span class="page-link">Previous</span>';
            echo '</li>';
        }

        // Page numbers
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);

        if ($start_page > 1) {
            echo '<li class="page-item">';
            echo '<a class="page-link" href="#" onclick="loadArchivePage(1)">1</a>';
            echo '</li>';
            if ($start_page > 2) {
                echo '<li class="page-item disabled">';
                echo '<span class="page-link">...</span>';
                echo '</li>';
            }
        }

        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $current_page) {
                echo '<li class="page-item active" aria-current="page">';
                echo '<span class="page-link">' . $i . '</span>';
                echo '</li>';
            } else {
                echo '<li class="page-item">';
                echo '<a class="page-link" href="#" onclick="loadArchivePage(' . $i . ')">' . $i . '</a>';
                echo '</li>';
            }
        }

        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                echo '<li class="page-item disabled">';
                echo '<span class="page-link">...</span>';
                echo '</li>';
            }
            echo '<li class="page-item">';
            echo '<a class="page-link" href="#" onclick="loadArchivePage(' . $total_pages . ')">' . $total_pages . '</a>';
            echo '</li>';
        }

        // Next button
        if ($current_page < $total_pages) {
            echo '<li class="page-item">';
            echo '<a class="page-link" href="#" onclick="loadArchivePage(' . ($current_page + 1) . ')">Next</a>';
            echo '</li>';
        } else {
            echo '<li class="page-item disabled">';
            echo '<span class="page-link">Next</span>';
            echo '</li>';
        }

        echo '</ul>';
        echo '</nav>';
    }
    exit;
}

// Check if we're loading edit form via AJAX
if (isset($_GET['edit'])) {
    $record_id = intval($_GET['edit']);
    $query = "SELECT * FROM parent_admissions WHERE id = $1 AND created_by = $2 AND is_closed = FALSE";
    $result = pg_query_params($con, $query, [$record_id, $user_id]);

    if ($row = pg_fetch_assoc($result)) {
?>
        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
            <input type="hidden" name="record_id" value="<?= $row['id'] ?>">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-2">
                        <label for="edit_name" class="form-label required-field">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" value="<?= isset($row['name']) ? htmlspecialchars($row['name']) : '' ?>" required>
                    </div>

                    <div class="mb-2">
                        <label for="edit_class_id" class="form-label">Class</label>
                        <select class="form-select" id="edit_class_id" name="class_id">
                            <option value="">Select class</option>
                            <?php foreach ($classes as $id => $name): ?>
                                <option value="<?= $id ?>" <?= isset($row['class_id']) && $id == $row['class_id'] ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label for="edit_mobile" class="form-label required-field">Mobile</label>
                        <input type="tel" class="form-control" id="edit_mobile" name="mobile" value="<?= isset($row['mobile']) ? htmlspecialchars($row['mobile']) : '' ?>" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-2">
                        <label for="edit_visit_type" class="form-label required-field">Type of Visit</label>
                        <select class="form-select" id="edit_visit_type" name="visit_type" required>
                            <option value="inquiry" <?= isset($row['visit_type']) && $row['visit_type'] == 'inquiry' ? 'selected' : '' ?>>Inquiry</option>
                            <option value="taking admission" <?= isset($row['visit_type']) && $row['visit_type'] == 'taking admission' ? 'selected' : '' ?>>Taking Admission</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label for="edit_previous_school" class="form-label">School Name (If applicable)</label>
                        <input type="text" class="form-control" id="edit_previous_school" name="previous_school" value="<?= isset($row['previous_school']) ? htmlspecialchars($row['previous_school']) : '' ?>">
                    </div>

                    <div class="mb-2">
                        <label for="edit_aadhar_submitted_by" class="form-label">Whose Aadhar Card Submitted?</label>
                        <select class="form-select" id="edit_aadhar_submitted_by" name="aadhar_submitted_by">
                            <option value="">Select</option>
                            <option value="self" <?= isset($row['aadhar_submitted_by']) && $row['aadhar_submitted_by'] == 'self' ? 'selected' : '' ?>>Self</option>
                            <option value="mother" <?= isset($row['aadhar_submitted_by']) && $row['aadhar_submitted_by'] == 'mother' ? 'selected' : '' ?>>Mother</option>
                            <option value="father" <?= isset($row['aadhar_submitted_by']) && $row['aadhar_submitted_by'] == 'father' ? 'selected' : '' ?>>Father</option>
                            <option value="legal guardian" <?= isset($row['aadhar_submitted_by']) && $row['aadhar_submitted_by'] == 'legal guardian' ? 'selected' : '' ?>>Legal Guardian</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-2">
                        <label for="edit_aadhar_number" class="form-label">Aadhar Number</label>
                        <input type="text" class="form-control" id="edit_aadhar_number" name="aadhar_number" value="<?= isset($row['aadhar_number']) ? htmlspecialchars($row['aadhar_number']) : '' ?>">
                    </div>

                    <div class="mb-2">
                        <label for="edit_current_address" class="form-label">Address</label>
                        <textarea class="form-control" id="edit_current_address" name="current_address" rows="3"><?= isset($row['current_address']) ? htmlspecialchars($row['current_address']) : '' ?></textarea>
                    </div>

                    <div class="mb-2">
                        <label for="edit_remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="edit_remarks" name="remarks" rows="3"><?= isset($row['remarks']) ? htmlspecialchars($row['remarks']) : '' ?></textarea>
                    </div>
                </div>
            </div>

            <div id="editAdmissionFields" style="<?= isset($row['visit_type']) && $row['visit_type'] == 'taking admission' ? 'display: block;' : 'display: none;' ?>">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-2">
                            <label for="edit_family_member_count" class="form-label">Family Member Count</label>
                            <input type="number" class="form-control" id="edit_family_member_count" name="family_member_count" min="1" value="<?= isset($row['family_member_count']) ? $row['family_member_count'] : '' ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-2">
                            <label for="edit_monthly_income" class="form-label">Monthly Income (₹)</label>
                            <input type="number" class="form-control" id="edit_monthly_income" name="monthly_income" min="0" step="0.01" value="<?= isset($row['monthly_income']) ? $row['monthly_income'] : '' ?>">
                        </div>
                    </div>
                </div>

                <div class="fee-calculation">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label for="edit_fees_submission_date" class="form-label">Date of fees submission</label>
                                <input type="date" class="form-control" id="edit_fees_submission_date" name="fees_submission_date" value="<?= isset($row['fees_submission_date']) ? $row['fees_submission_date'] : '' ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label for="edit_total_fee" class="form-label">Total Fee</label>
                                <input type="number" class="form-control" id="edit_total_fee" name="total_fee" min="0" step="0.01" value="<?= isset($row['total_fee']) ? $row['total_fee'] : '' ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label for="edit_deposited_amount" class="form-label">Deposited</label>
                                <input type="number" class="form-control" id="edit_deposited_amount" name="deposited_amount" min="0" step="0.01" value="<?= isset($row['deposited_amount']) ? $row['deposited_amount'] : '' ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-2">
                                <label for="edit_due_amount" class="form-label">Due</label>
                                <input type="number" class="form-control" id="edit_due_amount" name="due_amount" min="0" step="0.01" value="<?= isset($row['due_amount']) ? $row['due_amount'] : '' ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-2">
                                <label for="edit_fees_month" class="form-label">Fees Month</label>
                                <input type="month" class="form-control" id="edit_fees_month" name="fees_month" placeholder="e.g., April 2023" value="<?= isset($row['fees_month']) ? htmlspecialchars($row['fees_month']) : '' ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-2">
                                <label for="edit_payment_mode" class="form-label">Payment Mode</label>
                                <select class="form-select" id="edit_payment_mode" name="payment_mode">
                                    <option value="">Select</option>
                                    <option value="cash" <?= isset($row['payment_mode']) && $row['payment_mode'] == 'cash' ? 'selected' : '' ?>>Cash</option>
                                    <option value="online" <?= isset($row['payment_mode']) && $row['payment_mode'] == 'online' ? 'selected' : '' ?>>Online</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4" id="editTransactionIdField" style="<?= isset($row['payment_mode']) && $row['payment_mode'] == 'online' ? 'display: block;' : 'display: none;' ?>">
                            <div class="mb-2">
                                <label for="edit_transaction_id" class="form-label">Transaction ID</label>
                                <input type="text" class="form-control" id="edit_transaction_id" name="transaction_id" value="<?= isset($row['transaction_id']) ? htmlspecialchars($row['transaction_id']) : '' ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="update_record" class="btn btn-primary">Update Record</button>
            </div>
        </form>
<?php
    }
    exit;
}
?>

<?php
// Check if we're loading view details via AJAX
if (isset($_GET['view'])) {
    $record_id = intval($_GET['view']);
    $query = "SELECT pa.*, sc.class_name, sc.value, 
             creator.fullname as created_by_name,
             closer.fullname as closed_by_name
             FROM parent_admissions pa
             LEFT JOIN school_classes sc ON pa.class_id = sc.id
             LEFT JOIN rssimyaccount_members creator ON pa.created_by = creator.associatenumber
             LEFT JOIN rssimyaccount_members closer ON pa.closed_by = closer.associatenumber
             WHERE pa.id = $1";

    $result = pg_query_params($con, $query, [$record_id]);

    if ($row = pg_fetch_assoc($result)) {
?>
        <div class="view-details">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5>Basic Information</h5>
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Name</th>
                            <td><?= isset($row['name']) ? htmlspecialchars($row['name']) : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <th>Mobile</th>
                            <td><?= isset($row['mobile']) ? htmlspecialchars($row['mobile']) : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <th>Class</th>
                            <td><?= isset($row['class_name']) ? htmlspecialchars($row['value']) : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <th>Visit Type</th>
                            <td><?= isset($row['visit_type']) ? ucfirst($row['visit_type']) : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <th>School Name</th>
                            <td><?= isset($row['previous_school']) ? htmlspecialchars($row['previous_school']) : 'N/A' ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Additional Information</h5>
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Address</th>
                            <td><?= isset($row['current_address']) ? nl2br(htmlspecialchars($row['current_address'])) : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <th>Whose Aadhar Card Submitted?</th>
                            <td><?= isset($row['aadhar_submitted_by']) ? ucwords(str_replace('_', ' ', $row['aadhar_submitted_by'])) : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <th>Aadhar Number</th>
                            <td><?= isset($row['aadhar_number']) ? htmlspecialchars($row['aadhar_number']) : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <th>Created By</th>
                            <td><?= isset($row['created_by_name']) ? htmlspecialchars($row['created_by_name']) : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <th>Created On</th>
                            <td><?= isset($row['created_at']) ? date('d M Y H:i', strtotime($row['created_at'])) : 'N/A' ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php if ($row['visit_type'] == 'taking admission'): ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>Family Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Family Member Count</th>
                                <td><?= isset($row['family_member_count']) ? $row['family_member_count'] : 'N/A' ?></td>
                            </tr>
                            <tr>
                                <th>Monthly Income</th>
                                <td><?= isset($row['monthly_income']) ? '₹' . number_format($row['monthly_income'], 2) : 'N/A' ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Fee Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Date of fees submission</th>
                                <td><?= isset($row['fees_submission_date']) ? date('d M Y', strtotime($row['fees_submission_date'])) : 'N/A' ?></td>
                            </tr>
                            <tr>
                                <th>Total Fee</th>
                                <td><?= isset($row['total_fee']) ? '₹' . number_format($row['total_fee'], 2) : 'N/A' ?></td>
                            </tr>
                            <tr>
                                <th>Deposited Amount</th>
                                <td><?= isset($row['deposited_amount']) ? '₹' . number_format($row['deposited_amount'], 2) : 'N/A' ?></td>
                            </tr>
                            <tr>
                                <th>Due Amount</th>
                                <td><?= isset($row['due_amount']) ? '₹' . number_format($row['due_amount'], 2) : 'N/A' ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h5>Payment Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Payment Mode</th>
                                <td><?= isset($row['payment_mode']) ? ucfirst($row['payment_mode']) : 'N/A' ?></td>
                            </tr>
                            <tr>
                                <th>Transaction ID</th>
                                <td><?= isset($row['transaction_id']) ? htmlspecialchars($row['transaction_id']) : 'N/A' ?></td>
                            </tr>
                            <tr>
                                <th>Fees Month</th>
                                <td><?= !empty($row['fees_month']) ? (DateTime::createFromFormat('Y-m', $row['fees_month'])?->format('F, Y') ?? htmlspecialchars($row['fees_month'])) : 'N/A' ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($row['remarks'])): ?>
                <div class="mt-3">
                    <h5>Remarks</h5>
                    <div class="alert alert-light p-2">
                        <?= nl2br(htmlspecialchars($row['remarks'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($user_role == 'Admin' && $row['is_closed'] == false && $row['system_process_completed'] == true): ?>
                <div class="mt-3 text-end">
                    <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" style="display: inline;">
                        <input type="hidden" name="record_id" value="<?= $row['id'] ?>">
                        <button type="submit" name="move_to_active" class="btn btn-warning btn-sm">
                            <i class="bi bi-arrow-counterclockwise"></i> Move to Active
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
<?php
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquiry Portal</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        .form-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #0d6efd;
        }

        .record-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 10px;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .status-active {
            background-color: #ffc107;
            color: #000;
        }

        .status-completed {
            background-color: #198754;
            color: #fff;
        }

        .status-closed {
            background-color: #6c757d;
            color: #fff;
        }

        .required-field::after {
            content: " *";
            color: red;
        }

        .compact-form .row {
            margin-bottom: 10px;
        }

        .compact-form .form-label {
            margin-bottom: 0.2rem;
            font-size: 0.9rem;
        }

        .compact-form .form-control,
        .compact-form .form-select {
            padding: 0.3rem 0.5rem;
            font-size: 0.9rem;
            height: calc(1.5em + 0.6rem + 2px);
        }

        .compact-form .btn {
            padding: 0.3rem 0.75rem;
            font-size: 0.9rem;
        }

        .fee-calculation {
            background-color: #f0f8ff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Enquiry Portal</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Survey</li>
                    <li class="breadcrumb-item active">Enquiry Portal</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                                    <?= $_SESSION['success_message'] ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['success_message']); ?>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                                    <?= $_SESSION['error_message'] ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['error_message']); ?>
                            <?php endif; ?>

                            <div class="form-section mt-3 mb-4">
                                <h5><i class="bi bi-person-plus"></i> New Admission/Enquiry</h5>
                                <form method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <label for="name" class="form-label required-field">Name</label>
                                                <input type="text" class="form-control" id="name" name="name" required>
                                            </div>

                                            <div class="mb-2">
                                                <label for="class_id" class="form-label">Class</label>
                                                <select class="form-select" id="class_id" name="class_id">
                                                    <option value="">Select class</option>
                                                    <?php foreach ($classes as $id => $name): ?>
                                                        <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-2">
                                                <label for="mobile" class="form-label required-field">Mobile</label>
                                                <input type="tel" class="form-control" id="mobile" name="mobile" required>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <label for="visit_type" class="form-label required-field">Type of Visit</label>
                                                <select class="form-select" id="visit_type" name="visit_type" required>
                                                    <option value="">Select</option>
                                                    <option value="inquiry">Inquiry</option>
                                                    <option value="taking admission">Taking Admission</option>
                                                </select>
                                            </div>

                                            <div class="mb-2">
                                                <label for="previous_school" class="form-label">School Name (If applicable)</label>
                                                <input type="text" class="form-control" id="previous_school" name="previous_school">
                                            </div>

                                            <div class="mb-2">
                                                <label for="aadhar_submitted_by" class="form-label">Whose Aadhar Card Submitted?</label>
                                                <select class="form-select" id="aadhar_submitted_by" name="aadhar_submitted_by">
                                                    <option value="">Select</option>
                                                    <option value="self">Self</option>
                                                    <option value="mother">Mother</option>
                                                    <option value="father">Father</option>
                                                    <option value="legal guardian">Legal Guardian</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <label for="aadhar_number" class="form-label">Aadhar Number</label>
                                                <input type="text" class="form-control" id="aadhar_number" name="aadhar_number">
                                            </div>

                                            <div class="mb-2">
                                                <label for="current_address" class="form-label">Address</label>
                                                <textarea class="form-control" id="current_address" name="current_address" rows="3"></textarea>
                                            </div>

                                            <div class="mb-2">
                                                <label for="remarks" class="form-label">Remarks</label>
                                                <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="admissionFields" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="mb-2">
                                                    <label for="family_member_count" class="form-label">Family Member Count</label>
                                                    <input type="number" class="form-control" id="family_member_count" name="family_member_count" min="1">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-2">
                                                    <label for="monthly_income" class="form-label">Monthly Income (₹)</label>
                                                    <input type="number" class="form-control" id="monthly_income" name="monthly_income" min="0" step="0.01">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="fee-calculation">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="mb-2">
                                                        <label for="fees_submission_date" class="form-label">Date of fees submission</label>
                                                        <input type="date" class="form-control" id="fees_submission_date" name="fees_submission_date">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-2">
                                                        <label for="total_fee" class="form-label">Total Fee</label>
                                                        <input type="number" class="form-control" id="total_fee" name="total_fee" min="0" step="0.01">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-2">
                                                        <label for="deposited_amount" class="form-label">Deposited</label>
                                                        <input type="number" class="form-control" id="deposited_amount" name="deposited_amount" min="0" step="0.01">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-2">
                                                        <label for="due_amount" class="form-label">Due</label>
                                                        <input type="number" class="form-control" id="due_amount" name="due_amount" min="0" step="0.01" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-2">
                                                        <label for="fees_month" class="form-label">Fees Month</label>
                                                        <input type="month" class="form-control" id="fees_month" name="fees_month" placeholder="e.g., April 2023">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="mb-2">
                                                        <label for="payment_mode" class="form-label">Payment Mode</label>
                                                        <select class="form-select" id="payment_mode" name="payment_mode">
                                                            <option value="">Select</option>
                                                            <option value="cash">Cash</option>
                                                            <option value="online">Online</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4" id="transactionIdField" style="display: none;">
                                                    <div class="mb-2">
                                                        <label for="transaction_id" class="form-label">Transaction ID</label>
                                                        <input type="text" class="form-control" id="transaction_id" name="transaction_id">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" name="submit_admission" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Submit
                                    </button>
                                </form>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <ul class="nav nav-tabs" id="admissionTabs" role="tablist">
                                    <?php foreach ($tabs as $tab_id => $tab): ?>
                                        <?php if ($tab_id != 'admin' || $user_role == 'Admin'): ?>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link <?= $selected_tab == $tab_id ? 'active' : '' ?>"
                                                    id="<?= $tab_id ?>-tab"
                                                    data-bs-toggle="tab"
                                                    data-bs-target="#<?= $tab_id ?>"
                                                    type="button" role="tab">
                                                    <?= $tab['name'] ?>
                                                    <span class="badge bg-<?= $tab_id == 'active' ? 'warning' : ($tab_id == 'admin' ? 'primary' : 'secondary') ?> ms-1">
                                                        <?= $tab['count'] ?>
                                                    </span>
                                                </button>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>

                                <!-- In the HTML, remove the disabled attribute from the academic year filter -->
                                <div class="d-flex align-items-center">
                                    <label for="academic_year" class="form-label me-2 mb-0">Academic Year:</label>
                                    <select class="form-select form-select-sm" id="academic_year" style="width: 120px;">
                                        <?php foreach ($academic_years as $year => $label): ?>
                                            <option value="<?= $year ?>" <?= $year == $selected_academic_year ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="admissionTabsContent">
                                <?php foreach ($tabs as $tab_id => $tab): ?>
                                    <?php if ($tab_id != 'admin' || $user_role == 'Admin'): ?>
                                        <div class="tab-pane fade <?= $selected_tab == $tab_id ? 'show active' : '' ?>" id="<?= $tab_id ?>" role="tabpanel">
                                            <?php if ($tab_id == 'archive'): ?>
                                                <div id="archiveContent">
                                                    <?php if ($selected_tab == 'archive'): ?>
                                                        <div class="text-center py-4">
                                                            <div class="spinner-border text-primary" role="status">
                                                                <span class="visually-hidden">Loading...</span>
                                                            </div>
                                                            <p class="mt-2">Loading archive records...</p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <?php
                                                $query = "SELECT pa.*, sc.class_name, sc.value 
                                                         FROM parent_admissions pa
                                                         LEFT JOIN school_classes sc ON pa.class_id = sc.id
                                                         WHERE {$tab['where']}
                                                         ORDER BY pa.created_at DESC";
                                                $result = pg_query($con, $query);
                                                ?>

                                                <?php if (pg_num_rows($result) == 0): ?>
                                                    <div class="alert alert-info">No records found.</div>
                                                <?php else: ?>
                                                    <?php while ($row = pg_fetch_assoc($result)): ?>
                                                        <div class="card record-card">
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between align-items-start">
                                                                    <div>
                                                                        <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>
                                                                        <p class="card-text mb-1"><small class="text-muted">Mobile: <?= htmlspecialchars($row['mobile']) ?></small></p>
                                                                        <?php if ($row['class_name']): ?>
                                                                            <p class="card-text mb-1"><small class="text-muted">Class: <?= htmlspecialchars($row['class_name']) ?> (<?= htmlspecialchars($row['value']) ?>)</small></p>
                                                                        <?php endif; ?>
                                                                        <p class="card-text mb-1"><small class="text-muted">Visit Type: <?= ucfirst($row['visit_type']) ?></small></p>
                                                                    </div>
                                                                    <span class="badge <?= $tab_id == 'active' ? 'status-active' : ($tab_id == 'admin' ? 'status-completed' : 'status-closed') ?>">
                                                                        <?= $tab['name'] ?>
                                                                    </span>
                                                                </div>

                                                                <?php if ($row['visit_type'] == 'taking admission'): ?>
                                                                    <div class="row mt-2">
                                                                        <div class="col-md-4">
                                                                            <p class="mb-1 small"><strong>Family Info:</strong>
                                                                                Members: <?= $row['family_member_count'] ?: 'N/A' ?><br>
                                                                                Income: <?= $row['monthly_income'] ? '₹' . $row['monthly_income'] : 'N/A' ?></p>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <p class="mb-1 small"><strong>Fees:</strong>
                                                                                Submitted: <?= $row['fees_submission_date'] ? date('d M Y', strtotime($row['fees_submission_date'])) : 'N/A' ?><br>
                                                                                Total: <?= $row['total_fee'] ? '₹' . $row['total_fee'] : 'N/A' ?></p>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <p class="mb-1 small"><strong>Payment:</strong>
                                                                                Deposited: <?= $row['deposited_amount'] ? '₹' . $row['deposited_amount'] : 'N/A' ?><br>
                                                                                Due: <?= $row['due_amount'] ? '₹' . $row['due_amount'] : 'N/A' ?><br>
                                                                                Mode: <?= $row['payment_mode'] ? ucfirst($row['payment_mode']) : 'N/A' ?></p>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <?php if (!empty($row['remarks'])): ?>
                                                                    <div class="alert alert-light mt-2 p-2">
                                                                        <strong>Remarks:</strong> <?= nl2br(htmlspecialchars($row['remarks'])) ?>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <div class="d-flex justify-content-between mt-2">
                                                                    <div>
                                                                        <small class="text-muted">Created on <?= date('d M Y H:i', strtotime($row['created_at'])) ?></small>
                                                                    </div>
                                                                    <div>
                                                                        <?php if ($tab_id == 'active' && $row['created_by'] == $user_id): ?>
                                                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="loadEditForm(<?= $row['id'] ?>)" data-bs-toggle="modal" data-bs-target="#editModal">
                                                                                <i class="bi bi-pencil"></i> Edit
                                                                            </button>
                                                                        <?php endif; ?>

                                                                        <button class="btn btn-sm btn-outline-secondary" onclick="loadViewDetails(<?= $row['id'] ?>)" data-bs-toggle="modal" data-bs-target="#viewModal">
                                                                            <i class="bi bi-eye"></i> View
                                                                        </button>

                                                                        <?php if ($tab_id == 'active' && $row['created_by'] == $user_id): ?>
                                                                            <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" style="display: inline;">
                                                                                <input type="hidden" name="record_id" value="<?= $row['id'] ?>">
                                                                                <button type="submit" name="mark_completed" class="btn btn-sm btn-success ms-1">
                                                                                    <i class="bi bi-check-circle"></i> Mark Completed
                                                                                </button>
                                                                            </form>
                                                                        <?php endif; ?>

                                                                        <?php if ($tab_id == 'admin' && $user_role == 'Admin'): ?>
                                                                            <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" style="display: inline;">
                                                                                <input type="hidden" name="record_id" value="<?= $row['id'] ?>">
                                                                                <button type="submit" name="move_to_active" class="btn btn-sm btn-warning ms-1">
                                                                                    <i class="bi bi-arrow-counterclockwise"></i> Move to Active
                                                                                </button>
                                                                            </form>
                                                                            <button class="btn btn-sm btn-outline-danger ms-1" onclick="showCloseModal(<?= $row['id'] ?>)">
                                                                                <i class="bi bi-archive"></i> Close
                                                                            </button>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Edit Record Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="editModalBody">
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewModalBody">
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Close Record Modal -->
    <div class="modal fade" id="closeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Close Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>">
                    <div class="modal-body">
                        <input type="hidden" name="record_id" id="closeRecordId">
                        <div class="mb-3">
                            <label for="close_remarks" class="form-label">Remarks (Optional)</label>
                            <textarea class="form-control" id="close_remarks" name="close_remarks" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="close_record" class="btn btn-danger">Confirm Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets_new/js/main.js"></script>
    <script>
        // Main form field handlers
        function setupFormFieldHandlers() {
            // Show/hide admission fields based on visit type
            const visitTypeField = document.getElementById('visit_type');
            if (visitTypeField) {
                visitTypeField.addEventListener('change', handleVisitTypeChange);
            }

            // Show/hide transaction ID field based on payment mode
            const paymentModeField = document.getElementById('payment_mode');
            if (paymentModeField) {
                paymentModeField.addEventListener('change', handlePaymentModeChange);
            }

            // Calculate due amount automatically
            const totalFeeField = document.getElementById('total_fee');
            const depositedAmountField = document.getElementById('deposited_amount');
            if (totalFeeField && depositedAmountField) {
                totalFeeField.addEventListener('input', calculateDue);
                depositedAmountField.addEventListener('input', calculateDue);
            }
        }

        function handleVisitTypeChange() {
            const admissionFields = document.getElementById('admissionFields');
            const isAdmission = this.value === 'taking admission';

            // Toggle visibility
            admissionFields.style.display = isAdmission ? 'block' : 'none';

            // List of all admission-related fields
            const admissionFieldsList = [
                'family_member_count', 'monthly_income', 'fees_submission_date',
                'total_fee', 'deposited_amount', 'due_amount', 'fees_month',
                'payment_mode', 'transaction_id'
            ];

            // Handle field requirements and reset values
            admissionFieldsList.forEach(field => {
                const element = document.getElementById(field);
                if (!element) return;

                // Set required status for relevant fields
                const requiredFields = [
                    'family_member_count', 'monthly_income', 'fees_submission_date',
                    'total_fee', 'deposited_amount', 'fees_month', 'payment_mode'
                ];

                if (requiredFields.includes(field)) {
                    element.required = isAdmission;
                }

                // Reset values when switching to inquiry
                if (!isAdmission) {
                    if (element.tagName === 'SELECT') {
                        element.selectedIndex = 0;
                    } else {
                        element.value = '';
                    }

                    // Special handling for payment mode
                    if (field === 'payment_mode') {
                        document.getElementById('transactionIdField').style.display = 'none';
                        document.getElementById('transaction_id').required = false;
                    }
                }
            });

            // Recalculate due amount if needed
            if (isAdmission) {
                calculateDue();
            } else {
                const dueAmountField = document.getElementById('due_amount');
                if (dueAmountField) dueAmountField.value = '';
            }
        }

        function handlePaymentModeChange() {
            const transactionIdField = document.getElementById('transactionIdField');
            const transactionIdInput = document.getElementById('transaction_id');
            const showTransactionId = this.value === 'online';

            transactionIdField.style.display = showTransactionId ? 'block' : 'none';
            transactionIdInput.required = showTransactionId;

            if (!showTransactionId) {
                transactionIdInput.value = '';
            }
        }

        function calculateDue() {
            try {
                const totalFee = parseFloat(document.getElementById('total_fee').value) || 0;
                const deposited = parseFloat(document.getElementById('deposited_amount').value) || 0;
                const due = totalFee - deposited;
                document.getElementById('due_amount').value = due.toFixed(2);
            } catch (error) {
                console.error('Error calculating due amount:', error);
            }
        }

        // Tab and academic year handling
        function setupTabHandlers() {
            // Tab change event
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
                tab.addEventListener('shown.bs.tab', handleTabChange);
            });

            // Academic year change event
            const academicYearField = document.getElementById('academic_year');
            if (academicYearField) {
                academicYearField.addEventListener('change', handleAcademicYearChange);
            }
        }

        function handleTabChange(event) {
            const tabName = event.target.getAttribute('data-bs-target').replace('#', '');
            const academicYear = document.getElementById('academic_year').value;

            updateUrlParameter('tab', tabName);
            updateUrlParameter('academic_year', academicYear);

            if (tabName === 'archive') {
                loadArchiveContent();
            } else {
                window.location.search = new URLSearchParams({
                    tab: tabName,
                    academic_year: academicYear
                }).toString();
            }
        }

        function handleAcademicYearChange() {
            const urlParams = new URLSearchParams(window.location.search);
            const currentTab = urlParams.get('tab') || 'active';
            const academicYear = this.value;

            updateUrlParameter('academic_year', academicYear);

            if (currentTab === 'archive') {
                loadArchiveContent(1);
            } else {
                window.location.search = new URLSearchParams({
                    tab: currentTab,
                    academic_year: academicYear
                }).toString();
            }
        }

        // Archive content loading
        function loadArchiveContent(page = 1) {
            const archiveContent = document.getElementById('archiveContent');
            if (!archiveContent) return;

            archiveContent.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading archive records...</p>
            </div>
        `;

            const academicYear = document.getElementById('academic_year').value;

            // Update URL without reloading
            const url = new URL(window.location);
            url.searchParams.set('archive_page', page);
            url.searchParams.set('academic_year', academicYear);
            window.history.pushState({}, '', url);

            fetch(`<?= $_SERVER['PHP_SELF'] ?>?ajax=archive&archive_page=${page}&academic_year=${academicYear}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(data => {
                    archiveContent.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error loading archive data:', error);
                    archiveContent.innerHTML = `
                    <div class="alert alert-danger">
                        Error loading archive data. Please try again.
                    </div>
                `;
                });
        }

        function loadArchivePage(page) {
            loadArchiveContent(page);
            return false;
        }

        // Modal loading functions
        function loadEditForm(recordId) {
            const editModalBody = document.getElementById('editModalBody');
            if (!editModalBody) return;

            editModalBody.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

            fetch(`<?= $_SERVER['PHP_SELF'] ?>?edit=${recordId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(data => {
                    editModalBody.innerHTML = data;
                    if (typeof calculateEditDue === 'function') {
                        calculateEditDue();
                    }
                })
                .catch(error => {
                    console.error('Error loading edit form:', error);
                    editModalBody.innerHTML = `
                    <div class="alert alert-danger">
                        Error loading form. Please try again.
                    </div>
                `;
                });
        }

        function loadViewDetails(recordId) {
            const viewModalBody = document.getElementById('viewModalBody');
            if (!viewModalBody) return;

            viewModalBody.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;

            fetch(`<?= $_SERVER['PHP_SELF'] ?>?view=${recordId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(data => {
                    viewModalBody.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error loading details:', error);
                    viewModalBody.innerHTML = `
                    <div class="alert alert-danger">
                        Error loading details. Please try again.
                    </div>
                `;
                });
        }

        function showCloseModal(recordId) {
            const closeRecordIdField = document.getElementById('closeRecordId');
            if (closeRecordIdField) {
                closeRecordIdField.value = recordId;
                const closeModal = new bootstrap.Modal(document.getElementById('closeModal'));
                closeModal.show();
            }
        }

        // Utility functions
        function updateUrlParameter(key, value) {
            const url = new URL(window.location);
            url.searchParams.set(key, value);
            window.history.replaceState({}, '', url);
        }

        // Initialize everything when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            setupFormFieldHandlers();
            setupTabHandlers();

            if (window.location.href.includes('tab=archive')) {
                const urlParams = new URLSearchParams(window.location.search);
                const archivePage = urlParams.get('archive_page') || 1;
                loadArchiveContent(archivePage);
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to update required fields based on current form state
            function updateRequiredFields() {
                const isAdmission = document.getElementById('edit_visit_type')?.value === 'taking admission';
                const isOnlinePayment = document.getElementById('edit_payment_mode')?.value === 'online';

                const admissionFields = [
                    'edit_family_member_count',
                    'edit_monthly_income',
                    'edit_fees_submission_date',
                    'edit_total_fee',
                    'edit_deposited_amount',
                    'edit_fees_month',
                    'edit_payment_mode'
                ];

                // Update required status for all admission fields
                admissionFields.forEach(field => {
                    const element = document.getElementById(field);
                    if (element) {
                        element.required = isAdmission;
                    }
                });

                // Special handling for transaction ID
                const transactionIdField = document.getElementById('edit_transaction_id');
                if (transactionIdField) {
                    transactionIdField.required = isAdmission && isOnlinePayment;
                }
            }

            // Delegate events for the edit modal
            document.addEventListener('change', function(e) {
                // Handle visit type change
                if (e.target && e.target.id === 'edit_visit_type') {
                    const admissionFields = document.getElementById('editAdmissionFields');
                    const isAdmission = e.target.value === 'taking admission';

                    // Toggle visibility
                    admissionFields.style.display = isAdmission ? 'block' : 'none';

                    // List of all admission-related fields to reset
                    const admissionFieldsList = [
                        'edit_family_member_count',
                        'edit_monthly_income',
                        'edit_fees_submission_date',
                        'edit_total_fee',
                        'edit_deposited_amount',
                        'edit_due_amount',
                        'edit_fees_month',
                        'edit_payment_mode',
                        'edit_transaction_id'
                    ];

                    // Reset fields when switching to inquiry
                    if (!isAdmission) {
                        admissionFieldsList.forEach(field => {
                            const element = document.getElementById(field);
                            if (element) {
                                if (element.tagName === 'SELECT') {
                                    element.selectedIndex = 0;
                                } else {
                                    element.value = '';
                                }
                            }
                        });

                        // Hide transaction ID field
                        document.getElementById('editTransactionIdField').style.display = 'none';
                    }

                    // Update required fields
                    updateRequiredFields();
                }

                // Handle payment mode change
                if (e.target && e.target.id === 'edit_payment_mode') {
                    const transactionIdField = document.getElementById('editTransactionIdField');
                    const showTransactionId = e.target.value === 'online';
                    transactionIdField.style.display = showTransactionId ? 'block' : 'none';

                    // Clear transaction ID if not online
                    if (!showTransactionId) {
                        document.getElementById('edit_transaction_id').value = '';
                    }

                    // Update required fields
                    updateRequiredFields();
                }
            });

            // Update required fields on any input change
            document.addEventListener('input', function(e) {
                // For calculation fields
                if (e.target && (e.target.id === 'edit_total_fee' || e.target.id === 'edit_deposited_amount')) {
                    calculateEditDue();
                }

                // Update required status for any field change
                updateRequiredFields();
            });

            function calculateEditDue() {
                const totalFee = parseFloat(document.getElementById('edit_total_fee')?.value) || 0;
                const deposited = parseFloat(document.getElementById('edit_deposited_amount')?.value) || 0;
                const due = totalFee - deposited;
                const dueField = document.getElementById('edit_due_amount');
                if (dueField) dueField.value = due.toFixed(2);
            }

            // Form submission validation - modified to use browser's native validation
            document.getElementById('editModal')?.addEventListener('submit', function(e) {
                updateRequiredFields(); // Final check before submission

                const form = this.querySelector('form');
                if (form) {
                    // First remove any previously prevented submissions
                    form.addEventListener('submit', function(submitEvent) {
                        submitEvent.preventDefault();
                        submitEvent.stopPropagation();
                    }, {
                        once: true
                    });

                    // Trigger browser validation
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                        form.reportValidity(); // This forces the browser to show validation messages

                        // Find and focus first invalid field
                        const firstInvalid = form.querySelector(':invalid');
                        if (firstInvalid) {
                            firstInvalid.focus();
                        }
                    }
                }
            });

            // Initialize when modal opens
            document.getElementById('editModal')?.addEventListener('shown.bs.modal', function() {
                if (typeof calculateEditDue === 'function') {
                    calculateEditDue();
                }
                updateRequiredFields();
            });
        });
    </script>
</body>

</html>