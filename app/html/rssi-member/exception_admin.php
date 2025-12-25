<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

// Calculate academic year
if (date('m') == 1 || date('m') == 2 || date('m') == 3) { // Upto March
    $academic_year = (date('Y') - 1) . '-' . date('Y');
} else { // After March
    $academic_year = date('Y') . '-' . (date('Y') + 1);
}

// Retrieve POST data
@$exception_id = $_POST['exception_id'];
@$applicant_id = strtoupper($_POST['applicant_id']);
@$lyear = $_POST['lyear'] ? $_POST['lyear'] : $academic_year;
@$is_user = $_POST['is_user'];

date_default_timezone_set('Asia/Kolkata');

// Build SQL query with conditions based on role
$query = "SELECT *, 
                 CASE
                     WHEN submitted_on >= DATE_TRUNC('year', CURRENT_DATE) AND submitted_on < DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year' 
                     THEN CONCAT(EXTRACT(YEAR FROM CURRENT_DATE), '-', EXTRACT(YEAR FROM CURRENT_DATE) + 1)
                     WHEN submitted_on >= DATE_TRUNC('year', CURRENT_DATE) - INTERVAL '1 year' AND submitted_on < DATE_TRUNC('year', CURRENT_DATE)
                     THEN CONCAT(EXTRACT(YEAR FROM CURRENT_DATE) - 1, '-', EXTRACT(YEAR FROM CURRENT_DATE))
                 END AS lyear
          FROM exception_requests 
          LEFT JOIN (SELECT associatenumber, fullname, email, phone FROM rssimyaccount_members) faculty 
          ON exception_requests.submitted_by = faculty.associatenumber  
          LEFT JOIN (SELECT student_id, studentname, emailaddress, contact FROM rssimyprofile_student) student 
          ON exception_requests.submitted_by = student.student_id 
          WHERE 1=1";

// Apply filters based on role
if ($role != 'Admin') {
    // Non-admins should only see their own records
    $query .= " AND exception_requests.submitted_by = '$associatenumber'";
}

// Apply filters based on provided input
if (!empty($exception_id)) {
    $query .= " AND id = '$exception_id'";
}

if (!empty($applicant_id)) {
    $query .= " AND exception_requests.submitted_by = '$applicant_id'";
}

// Apply filter based on lyear
if (!empty($lyear)) {
    $lyearStart = explode('-', $lyear)[0] . '-04-01';
    $lyearEnd = explode('-', $lyear)[1] . '-03-31';
    $query .= " AND submitted_on BETWEEN '$lyearStart' AND '$lyearEnd'";
}

// Order by submitted_on desc
$query .= " ORDER BY submitted_on DESC";

$result = pg_query($con, $query);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>
<?php
// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_status'])) {
    // Retrieve and sanitize POST data
    $selected_ids = explode(',', $_POST['selected_ids']);
    $bulk_status = $_POST['bulk_status'];
    $bulk_remarks = $_POST['bulk_remarks'];
    $reviewer_id = $associatenumber;

    // Validate inputs
    if (!empty($selected_ids) && !empty($bulk_status)) {
        // Escape and format the ID list properly for VARCHAR type
        $escaped_ids = array_map(fn($id) => "'" . pg_escape_string($con, trim($id)) . "'", $selected_ids);
        $ids_string = implode(',', $escaped_ids); // Create a comma-separated list of quoted IDs

        // First, update the database
        $query_update = "UPDATE exception_requests 
                         SET status = '" . pg_escape_string($con, $bulk_status) . "',
                             reviewer_id = '" . pg_escape_string($con, $reviewer_id) . "',
                             reviewer_status_updated_on = NOW(),
                             reviewer_remarks = '" . pg_escape_string($con, $bulk_remarks) . "'
                         WHERE id IN ($ids_string)";
        $result_update = pg_query($con, $query_update);

        if ($result_update) {
            // Now, fetch the updated data for email notification
            $query_fetch = "SELECT e.id, e.submitted_by, e.exception_type, e.reason, e.start_date_time, 
                                   e.end_date_time, e.status, e.reviewer_remarks, m.email, m.fullname 
                            FROM exception_requests e
                            LEFT JOIN rssimyaccount_members m ON e.submitted_by = m.associatenumber
                            WHERE e.id IN ($ids_string)";
            $result_fetch = pg_query($con, $query_fetch);

            if ($result_fetch) {
                while ($row = pg_fetch_assoc($result_fetch)) {
                    $submitted_by = $row['submitted_by'];
                    $email = $row['email'];
                    $fullname = $row['fullname'];
                    $exception_type = $row['exception_type'];
                    $reason = $row['reason'];
                    $dateTime = !empty($row['start_date_time']) ? $row['start_date_time'] : $row['end_date_time'];
                    $exception_status = strtoupper($row['status']);  // Now contains updated status
                    $reviewer_remarks = $row['reviewer_remarks'];  // Now contains updated remarks

                    if (!empty($email)) {
                        $emailData = [
                            "template" => "exception_notify",
                            "data" => [
                                "id" => $row['id'],
                                "submitted_by" => $submitted_by,
                                "fullname" => $fullname,
                                "exception_type" => $exception_type,
                                "reason" => $reason,
                                "date_time" => @date("d/m/Y g:i a", strtotime($dateTime)),
                                "exception_status" => $exception_status,  // Now updated
                                "reviewer_remarks" => $reviewer_remarks  // Now updated
                            ],
                            "email" => $email
                        ];

                        // Send email
                        sendEmail("exception_notify", $emailData['data'], $emailData['email'], false);
                    }
                }
            }

            echo "<script>alert('Bulk review applied successfully.'); window.location.href = window.location.href;</script>";
        } else {
            echo "<script>alert('Error applying bulk review.');</script>";
        }
    } else {
        echo "<script>alert('No rows selected or status not provided.');</script>";
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Exception Dashboard</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
    <style>
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }
    </style>
    <style>
        tbody tr {
            cursor: pointer;
        }

        tbody tr:hover {
            background-color: #f5f5f5;
        }
    </style>
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
            <h1>Exception Dashboard</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Raise Exception</a></li>
                    <li class="breadcrumb-item active">Exception Dashboard</li>
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
                            <div class="row align-items-center">
                                <div class="col-md-9">
                                    <form action="" method="POST" class="mb-3">
                                        <div class="form-group d-inline-block">
                                            <div class="col2 d-inline-block">
                                                <input name="exception_id" id="exception_id" class="form-control d-inline-block" style="width:max-content;" placeholder="Exception ID" value="<?php echo $exception_id ?>">

                                                <?php if ($role == 'Admin') { ?>
                                                    <input name="applicant_id" id="applicant_id" class="form-control d-inline-block" style="width:max-content;" placeholder="Applicant ID" value="<?php echo $applicant_id ?>">
                                                <?php } ?>

                                                <select name="lyear" id="lyear" class="form-select d-inline-block" style="width:max-content;" required>
                                                    <?php if ($lyear == null) { ?>
                                                        <option disabled selected hidden>Academic Year</option>
                                                    <?php } else { ?>
                                                        <option hidden selected><?php echo $lyear ?></option>
                                                    <?php } ?>
                                                    <?php
                                                    $currentYear = date('Y');
                                                    for ($i = 0; $i < 5; $i++) {
                                                        $startYear = $currentYear - $i;
                                                        $endYear = $startYear + 1;
                                                        echo "<option value='$startYear-$endYear'>$startYear-$endYear</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col2 left mb-3 d-inline-block">
                                            <button type="submit" name="search_by_id" class="btn btn-success btn-sm">
                                                <i class="bi bi-search"></i>&nbsp;Search
                                            </button>
                                        </div>
                                        <div id="filter-checks">
                                            <input type="checkbox" class="form-check-input" name="is_user" id="is_user" value="1" <?php if (isset($_POST['is_user'])) echo "checked='checked'"; ?> />
                                            <label for="is_user" style="font-weight: 400;">Search by Exception ID</label>
                                        </div>
                                    </form>
                                </div>

                                <?php if ($role == 'Admin') { ?>
                                    <div class="col-md-3 text-end">
                                        <button id="bulk-review-button" class="btn btn-primary" disabled>Bulk Review (0)</button>
                                    </div>
                                <?php } ?>
                            </div>

                            <script>
                                // Initial check for checkbox status
                                const checkbox = document.getElementById('is_user');
                                const exceptionInput = document.getElementById("exception_id");
                                const applicantInput = document.getElementById("applicant_id");
                                const lyearInput = document.getElementById("lyear");

                                function updateFieldStatus() {
                                    if (checkbox.checked) {
                                        // When checkbox is checked, disable applicant_id and lyear, enable exception_id
                                        exceptionInput.disabled = false;
                                        if (applicantInput) applicantInput.disabled = true; // Check if applicantInput exists
                                        lyearInput.disabled = true;
                                    } else {
                                        // When checkbox is unchecked, enable applicant_id and lyear, disable exception_id
                                        exceptionInput.disabled = true;
                                        if (applicantInput) applicantInput.disabled = false; // Check if applicantInput exists
                                        lyearInput.disabled = false;
                                    }
                                }

                                // Update field status based on checkbox initial status
                                updateFieldStatus();

                                // Add event listener for checkbox changes
                                checkbox.addEventListener('change', updateFieldStatus);
                            </script>

                            <script>
                                <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
                                    var currentYear = new Date().getFullYear() - 1;
                                <?php } else { ?>
                                    var currentYear = new Date().getFullYear();
                                <?php } ?>
                                for (var i = 0; i < 5; i++) {
                                    var next = currentYear + 1;
                                    var year = currentYear + '-' + next;
                                    //next.toString().slice(-2)
                                    $('#lyear').append(new Option(year, year));
                                    currentYear--;
                                }
                            </script>

                            <div class="table-responsive">
                                <!-- Table with Checkboxes -->
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Exception ID</th>
                                            <th>Submitted By</th>
                                            <th>Submitted On</th>
                                            <th>Exception Type</th>
                                            <th>Exception Date & Time</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Reviewed By</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (sizeof($resultArr) > 0) { ?>
                                            <?php foreach ($resultArr as $array) { ?>
                                                <tr>
                                                    <td>
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            name="selected_ids[]"
                                                            value="<?php echo $array['id']; ?>"
                                                            <?php echo ($array['status'] === 'Approved' || $array['status'] === 'Rejected') ? 'disabled' : ''; ?>>
                                                    </td>
                                                    <td><?php echo $array['id']; ?></td>
                                                    <td><?php echo $array['submitted_by'] . '<br>' . (!empty($array['fullname']) ? $array['fullname'] : $array['studentname']); ?></td>
                                                    <td><?php echo @date("d/m/Y g:i a", strtotime($array['submitted_on'])); ?></td>
                                                    <td><?php echo htmlspecialchars($array['sub_exception_type'] ?? ''); ?></td>
                                                    <td>
                                                        <?php
                                                        if (!empty($array['start_date_time'])) {
                                                            echo @date("d/m/Y g:i a", strtotime($array['start_date_time']));
                                                        } elseif (!empty($array['end_date_time'])) {
                                                            echo @date("d/m/Y g:i a", strtotime($array['end_date_time']));
                                                        } else {
                                                            echo '-';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo $array['reason'] ?? ''; ?></td>
                                                    <td><?php echo htmlspecialchars($array['status'] ?? ''); ?></td>
                                                    <td>
                                                        <?php
                                                        echo htmlspecialchars($array['reviewer_id'] ?? '');
                                                        echo !empty($array['reviewer_status_updated_on'])
                                                            ? " on " . date("d/m/Y g:i a", strtotime($array['reviewer_status_updated_on']))
                                                            : '';
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($array['reviewer_remarks'] ?? ''); ?></td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="11">No records found for the selected filter value.</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <!-- Bulk Review Modal -->
    <div class="modal fade" id="bulkReviewModal" tabindex="-1" aria-labelledby="bulkReviewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkReviewModalLabel">Bulk Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bulk-review-form" action="exception_admin.php" method="POST">
                        <input type="hidden" name="selected_ids" id="selected-ids">
                        <div class="mb-3">
                            <label for="bulk-status" class="form-label">Status</label>
                            <select name="bulk_status" id="bulk-status" class="form-select" required>
                                <option disabled selected hidden>Select Status</option>
                                <option value="Approved">Approved</option>
                                <!-- <option value="Under review">Under review</option> -->
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="bulk-remarks" class="form-label">Remarks</label>
                            <textarea name="bulk_remarks" id="bulk-remarks" class="form-control" placeholder="Remarks"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script>
        $(document).ready(function() {
            // Toggle full comment visibility on "more" link click
            $('.more-link').click(function(e) {
                e.preventDefault();
                var shortComment = $(this).siblings('.short-comment');
                var fullComment = $(this).siblings('.full-comment');
                if (fullComment.is(':visible')) {
                    // If full comment is visible, toggle to show short comment
                    shortComment.show();
                    fullComment.hide();
                    $(this).text('more');
                } else {
                    // If short comment is visible, toggle to show full comment
                    shortComment.hide();
                    fullComment.show();
                    $(this).text('less');
                }
            });
        });
    </script>
    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Make the entire row clickable to toggle the checkbox
            document.querySelectorAll('tbody tr').forEach(row => {
                row.addEventListener('click', (e) => {
                    // Check if the click was on a checkbox, link, or button to avoid unwanted toggling
                    if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
                        const checkbox = row.querySelector('td .form-check-input'); // Select only table checkboxes
                        if (checkbox && !checkbox.disabled) {
                            checkbox.checked = !checkbox.checked;

                            // Trigger the change event manually to update the bulk review button
                            checkbox.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                        }
                    }
                });
            });

            // Function to update the bulk review button
            function updateBulkReviewButton() {
                // Only count checkboxes inside the table
                const selectedCount = document.querySelectorAll('tbody .form-check-input:checked').length;
                const bulkReviewButton = document.getElementById('bulk-review-button');
                bulkReviewButton.textContent = `Bulk Review (${selectedCount})`;
                bulkReviewButton.disabled = selectedCount === 0;
            }

            // Attach event listeners to table checkboxes only
            document.querySelectorAll('tbody .form-check-input').forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkReviewButton);
            });

            // Initial update of the button
            updateBulkReviewButton();

            // Open the bulk review modal and populate selected IDs
            document.getElementById('bulk-review-button').addEventListener('click', () => {
                const selectedIds = getSelectedIds();
                if (selectedIds.length > 0) {
                    document.getElementById('selected-ids').value = selectedIds.join(',');

                    // Initialize and show the modal
                    const bulkReviewModal = new bootstrap.Modal(document.getElementById('bulkReviewModal'));
                    bulkReviewModal.show();
                } else {
                    alert('Please select at least one row to proceed.');
                }
            });

            // Function to get selected IDs (only from table checkboxes)
            function getSelectedIds() {
                const selectedIds = [];
                document.querySelectorAll('tbody .form-check-input:checked').forEach(checkbox => {
                    selectedIds.push(checkbox.value);
                });
                return selectedIds;
            }
        });
    </script>

</body>

</html>