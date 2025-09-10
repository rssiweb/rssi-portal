<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

// Check admin
$is_admin = ($role == 'Admin');
$associatenumber = $associatenumber;

// Check if supervisor
$is_supervisor = false;
$supervisor_check = pg_query($con, "SELECT supervisor FROM rssimyaccount_members WHERE associatenumber='$associatenumber'");
if ($supervisor_check && pg_num_rows($supervisor_check) > 0) {
    $supervisor_data = pg_fetch_assoc($supervisor_check);
    $is_supervisor = !empty($supervisor_data['supervisor']);
}

// Redirect if neither admin nor supervisor
if (!$is_admin && !$is_supervisor) {
    header("Location: access_denied.php");
    exit;
}

validation();

// Academic year calculation
if (in_array(date('m'), [1, 2, 3])) {
    $academic_year = (date('Y') - 1) . '-' . date('Y');
} else {
    $academic_year = date('Y') . '-' . (date('Y') + 1);
}

$now = date('Y-m-d H:i:s');
$year = $academic_year;

// --------------------
// Handle bulk approval
// --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['form-type']) && $_POST['form-type'] === "bulkapproval") {

    $message = '';
    $message_type = '';

    if (!empty($_POST['leave_ids']) && is_array($_POST['leave_ids'])) {
        $leave_ids = $_POST['leave_ids'];
        $status = $_POST['bulk_status'] ?? '';
        $comment = htmlspecialchars($_POST['bulk_comment'] ?? '', ENT_QUOTES, 'UTF-8');

        if (!empty($status)) {
            $success_count = 0;

            foreach ($leave_ids as $leave_id) {
                $update_query = "UPDATE leavedb_leavedb SET 
                                status = '$status', 
                                comment = '$comment',
                                reviewer_id = '$associatenumber',
                                reviewed_on = '$now'
                                WHERE leaveid = '$leave_id'";
                $result = pg_query($con, $update_query);

                if ($result) {
                    $success_count++;

                    // Fetch leave info
                    $leave_info = pg_query($con, "SELECT * FROM leavedb_leavedb WHERE leaveid = '$leave_id'");
                    if ($leave_info && pg_num_rows($leave_info) > 0) {
                        $leave_data = pg_fetch_assoc($leave_info);
                        $applicantid = $leave_data['applicantid'];

                        // Fetch applicant details from members table
                        $nameassociate = '';
                        $emailassociate = '';
                        $resultt = pg_query($con, "SELECT fullname, email FROM rssimyaccount_members WHERE associatenumber='$applicantid'");
                        if ($resultt && pg_num_rows($resultt) > 0) {
                            $nameassociate = pg_fetch_result($resultt, 0, 0);
                            $emailassociate = pg_fetch_result($resultt, 0, 1);
                        }

                        // Fetch applicant details from student table
                        $namestudent = '';
                        $emailstudent = '';
                        $resulttt = pg_query($con, "SELECT studentname, emailaddress FROM rssimyprofile_student WHERE student_id='$applicantid'");
                        if ($resulttt && pg_num_rows($resulttt) > 0) {
                            $namestudent = pg_fetch_result($resulttt, 0, 0);
                            $emailstudent = pg_fetch_result($resulttt, 0, 1);
                        }

                        $applicantname = trim($nameassociate . ' ' . $namestudent);
                        $emailList = array_filter([$emailassociate, $emailstudent]); // clean empty ones
                        $emails = implode(',', $emailList);

                        $fromdate = $leave_data['fromdate'];
                        $todate   = $leave_data['todate'];
                        $halfday  = $leave_data['halfday']; // assuming you have this column in leavedb_leavedb

                        $days = round((strtotime($todate) - strtotime($fromdate)) / (60 * 60 * 24) + 1);

                        if ($halfday == 1) {
                            $days = $days / 2;
                        }

                        if (!empty($emails)) {
                            sendEmail("leaveconf", [
                                "leaveid" => $leave_id,
                                "applicantid" => $applicantid,
                                "applicantname" => $applicantname,
                                "fromdate" => date("d/m/Y", strtotime($leave_data['fromdate'])),
                                "todate" => date("d/m/Y", strtotime($leave_data['todate'])),
                                "typeofleave" => $leave_data['typeofleave'],
                                "status" => $status,
                                "comment" => $comment,
                                "reviewer" => $fullname,
                                "now" => $now,
                                "day" => $days
                            ], $emails);
                        }
                    }
                }
            }

            $message = $success_count > 0
                ? "Successfully updated $success_count leave request(s)."
                : "No leaves were updated.";
            $message_type = $success_count > 0 ? "success" : "warning";
        } else {
            $message = "Please select a status for bulk action.";
            $message_type = "danger";
        }
    } else {
        $message = "Please select at least one leave request.";
        $message_type = "danger";
    }

    // --------------------
    // Redirect to prevent resubmission
    // --------------------
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_message_type'] = $message_type;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Retrieve flash message
$message = $_SESSION['flash_message'] ?? '';
$message_type = $_SESSION['flash_message_type'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

// --------------------
// Filters
// --------------------
$lyear = $_POST['lyear'] ?? $year;
$status_filter = $_POST['status_filter'] ?? '';
$type_filter = $_POST['type_filter'] ?? '';
$applicant_id = $_POST['applicant_id'] ?? '';
$leave_id = $_POST['leave_id'] ?? '';

// Build query
$where_conditions = ["l.lyear = '$lyear'"];
if (!empty($status_filter)) {
    $where_conditions[] = "l.status = '$status_filter'";
} else if (empty($status_filter) && empty($type_filter) && empty($applicant_id) && empty($leave_id)) {
    $where_conditions[] = "l.status IN ('Pending', 'Under review') AND f.filterstatus = 'Active'";
}
if (!empty($type_filter)) {
    $where_conditions[] = "l.typeofleave = '$type_filter'";
}
if (!empty($applicant_id)) {
    $where_conditions[] = "l.applicantid = '$applicant_id'";
}
if (!empty($leave_id)) {
    $where_conditions[] = "l.leaveid = '$leave_id'";
}

// Supervisor: show only team members' leaves
if ($is_supervisor && !$is_admin) {
    $team_members = pg_query($con, "SELECT associatenumber FROM rssimyaccount_members WHERE supervisor = '$associatenumber'");
    $team_ids = [];
    if ($team_members && pg_num_rows($team_members) > 0) {
        while ($member = pg_fetch_assoc($team_members)) {
            $team_ids[] = "'" . $member['associatenumber'] . "'";
        }
    }
    if (!empty($team_ids)) {
        $where_conditions[] = "l.applicantid IN (" . implode(",", $team_ids) . ")";
    } else {
        $where_conditions[] = "1 = 0";
    }
}

// Exclude current user's own leaves
$where_conditions[] = "l.applicantid != '$associatenumber'";
$where_clause = implode(" AND ", $where_conditions);

// Get leave requests
$result = pg_query($con, "SELECT l.*, REPLACE(l.doc, 'view', 'preview') docp,
                         COALESCE(f.fullname, s.studentname) as applicant_name,
                         COALESCE(f.email, s.emailaddress) as applicant_email,
                         r.fullname as reviewer_name
                         FROM leavedb_leavedb l
                         LEFT JOIN rssimyaccount_members f ON l.applicantid = f.associatenumber  
                         LEFT JOIN rssimyprofile_student s ON l.applicantid = s.student_id 
                         LEFT JOIN rssimyaccount_members r ON l.reviewer_id = r.associatenumber
                         WHERE $where_clause
                         ORDER BY l.timestamp DESC");

$resultArr = $result ? pg_fetch_all($result) : [];
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Leave Approval</title>

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

        tbody tr {
            cursor: pointer;
        }

        tbody tr:hover {
            background-color: #f5f5f5;
        }

        .bulk-actions {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }

        .checkbox-cell {
            width: 30px;
        }

        .pdf-viewer {
            width: 100%;
            height: 50vh;
            border: none;
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
            <h1>Leave Approval</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Leave Management System</a></li>
                    <li class="breadcrumb-item active">Leave Approval</li>
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

                            <?php if (!empty($message)): ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    <i class="bi <?php echo $message_type == 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?>"></i>
                                    <span><?php echo $message; ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="row align-items-center">
                                <div class="col-md-9">
                                    <form action="" method="POST" class="mb-3">
                                        <div class="form-group d-inline-block">
                                            <div class="col2 d-inline-block">
                                                <input name="leave_id" id="leave_id" class="form-control d-inline-block" style="width:max-content;" placeholder="Leave ID" value="<?php echo $leave_id ?>">

                                                <input name="applicant_id" id="applicant_id" class="form-control d-inline-block" style="width:max-content;" placeholder="Applicant ID" value="<?php echo $applicant_id ?>">

                                                <select name="lyear" id="lyear" class="form-select d-inline-block" style="width:max-content;" required>
                                                    <?php if ($lyear == null) { ?>
                                                        <option disabled selected hidden>Academic Year</option>
                                                    <?php } else { ?>
                                                        <option hidden selected><?php echo $lyear ?></option>
                                                    <?php } ?>
                                                    <?php
                                                    $currentYear = (in_array(date('m'), [1, 2, 3])) ? date('Y') - 1 : date('Y');
                                                    for ($i = 0; $i < 5; $i++) {
                                                        $startYear = $currentYear;
                                                        $endYear = $startYear + 1;
                                                        echo "<option value='$startYear-$endYear'>$startYear-$endYear</option>";
                                                        $currentYear--;
                                                    }
                                                    ?>
                                                </select>

                                                <select name="status_filter" id="status_filter" class="form-select d-inline-block" style="width:max-content;">
                                                    <option value="" <?php echo $status_filter == '' ? 'selected' : ''; ?> disabled>Select Status</option>
                                                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Under review" <?php echo $status_filter == 'Under review' ? 'selected' : ''; ?>>Under Review</option>
                                                    <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                </select>

                                                <select name="type_filter" id="type_filter" class="form-select d-inline-block" style="width:max-content;">
                                                    <option value="" <?php echo $type_filter == '' ? 'selected' : ''; ?> disabled>Select Type</option>
                                                    <option value="Sick Leave" <?php echo $type_filter == 'Sick Leave' ? 'selected' : ''; ?>>Sick Leave</option>
                                                    <option value="Casual Leave" <?php echo $type_filter == 'Casual Leave' ? 'selected' : ''; ?>>Casual Leave</option>
                                                    <option value="Leave Without Pay" <?php echo $type_filter == 'Leave Without Pay' ? 'selected' : ''; ?>>Leave Without Pay</option>
                                                    <option value="Adjustment Leave" <?php echo $type_filter == 'Adjustment Leave' ? 'selected' : ''; ?>>Adjustment Leave</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col2 left mb-3 d-inline-block">
                                            <button type="submit" name="search_by_id" class="btn btn-primary">
                                                <i class="bi bi-filter"></i>&nbsp;Search
                                            </button>
                                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                                                <i class="bi bi-arrow-clockwise"></i>&nbsp;Reset Filters
                                            </a>
                                        </div>
                                    </form>
                                </div>

                                <div class="col-md-3 text-end">
                                    <button id="bulk-review-button" class="btn btn-primary" disabled>Bulk Review (0)</button>
                                </div>
                            </div>

                            <!-- Bulk Review Modal -->
                            <div class="modal fade" id="bulkReviewModal" tabindex="-1" aria-labelledby="bulkReviewModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="bulkReviewModalLabel">Bulk Review</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="bulk-review-form" action="" method="POST">
                                                <input type="hidden" name="form-type" value="bulkapproval">
                                                <div class="mb-3">
                                                    <label for="bulk-status" class="form-label">Status</label>
                                                    <select name="bulk_status" id="bulk-status" class="form-select" required>
                                                        <option disabled selected hidden>Select Status</option>
                                                        <option value="Approved">Approved</option>
                                                        <option value="Under review">Under review</option>
                                                        <option value="Rejected">Rejected</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="bulk_comment" class="form-label">Remarks</label>
                                                    <textarea name="bulk_comment" id="bulk_comment" class="form-control" placeholder="Remarks"></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Apply</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <form id="main-form" method="POST" action="">
                                <input type="hidden" name="form-type" value="bulkapproval">

                                <div class="table-responsive">
                                    <table class="table" id="table-id">
                                        <thead>
                                            <tr>
                                                <th class="checkbox-cell"></th>
                                                <th>Leave ID</th>
                                                <th>Applicant</th>
                                                <th>Applied On</th>
                                                <th>From - To Date</th>
                                                <th>Days</th>
                                                <th>Leave Details</th>
                                                <th>Applied by</th>
                                                <th>Status</th>
                                                <th>Reviewer</th>
                                                <th width="15%">Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($resultArr)) { ?>
                                                <?php foreach ($resultArr as $array) { ?>
                                                    <tr>
                                                        <td class="checkbox-cell">
                                                            <input
                                                                type="checkbox"
                                                                class="form-check-input leave-checkbox"
                                                                name="leave_ids[]"
                                                                value="<?php echo $array['leaveid']; ?>"
                                                                <?php echo ($array['status'] === 'Approved' || $array['status'] === 'Rejected') ? 'disabled' : ''; ?>>
                                                        </td>
                                                        <td>
                                                            <?php if ($array['doc'] != null): ?>
                                                                <a href="javascript:void(0)" onclick="showpdf('<?php echo $array['docp']; ?>')">
                                                                    <?php echo $array['leaveid']; ?>
                                                                </a>
                                                            <?php else: ?>
                                                                <?php echo $array['leaveid']; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo $array['applicantid'] . '<br>' . $array['applicant_name']; ?></td>
                                                        <td><?php echo date("d/m/Y g:i a", strtotime($array['timestamp'])); ?></td>
                                                        <td><?php echo date("d/m/Y", strtotime($array['fromdate'])) . ' - ' . date("d/m/Y", strtotime($array['todate'])); ?></td>
                                                        <td><?php echo $array['days']; ?></td>
                                                        <td>
                                                            <?php
                                                            $typeofLeave = htmlspecialchars($array['typeofleave'] ?? '');
                                                            $shift = htmlspecialchars($array['shift'] ?? '');
                                                            echo $typeofLeave . ($shift ? '-' . $shift : '') . '<br>' . $array['creason'] . '<br>';

                                                            $applicantComment = isset($array['applicantcomment']) ? $array['applicantcomment'] : '';
                                                            $shortComment = strlen($applicantComment) > 30 ? substr($applicantComment, 0, 30) . "..." : $applicantComment;
                                                            echo '<span class="short-comment">' . htmlspecialchars($shortComment) . '</span>';
                                                            echo '<span class="full-comment" style="display: none;">' . htmlspecialchars($applicantComment) . '</span>';

                                                            if (strlen($applicantComment) > 30) {
                                                                echo ' <a href="#" class="more-link">more</a>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php echo ($array['appliedby'] === $array['applicantid']) ? 'Self' : 'System';  ?>
                                                        </td>
                                                        <td>
                                                            <?php echo $array['status']; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo $array['reviewer_id'] . '<br>' . $array['reviewer_name']; ?>
                                                        </td>
                                                        <td>
                                                            <?php echo $array['comment']; ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <tr>
                                                    <td colspan="11">No records found or you are not authorized to view this data.</td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </form>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <!-- PDF Viewer Modal -->
    <div class="modal fade" id="pdfViewerModal" tabindex="-1" aria-labelledby="pdfViewerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfViewerModalLabel">Document Viewer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe id="pdf-viewer" class="pdf-viewer" src="" sandbox="allow-scripts allow-same-origin"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    "order": [] // Disable initial sorting
                });
            <?php endif; ?>
        });

        // PDF viewer function
        function showpdf(docUrl) {
            $('#pdf-viewer').attr('src', docUrl);
            $('#pdfViewerModal').modal('show');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Make the entire row clickable to toggle the checkbox
            document.querySelectorAll('tbody tr').forEach(row => {
                row.addEventListener('click', (e) => {
                    // Check if the click was on a checkbox, link, or button to avoid unwanted toggling
                    if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
                        const checkbox = row.querySelector('td .leave-checkbox');
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
                const selectedCount = document.querySelectorAll('.leave-checkbox:checked').length;
                const bulkReviewButton = document.getElementById('bulk-review-button');
                bulkReviewButton.textContent = `Bulk Review (${selectedCount})`;
                bulkReviewButton.disabled = selectedCount === 0;
            }

            // Attach event listeners to table checkboxes only
            document.querySelectorAll('.leave-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkReviewButton);
            });

            // Initial update of the button
            updateBulkReviewButton();

            // Open the bulk review modal and populate selected IDs
            document.getElementById('bulk-review-button').addEventListener('click', () => {
                const selectedIds = getSelectedIds();
                if (selectedIds.length > 0) {
                    // Initialize and show the modal
                    const bulkReviewModal = new bootstrap.Modal(document.getElementById('bulkReviewModal'));
                    bulkReviewModal.show();
                } else {
                    alert('Please select at least one leave request to proceed.');
                }
            });

            // Handle form submission from the modal
            document.getElementById('bulk-review-form').addEventListener('submit', function(e) {
                // Get selected IDs
                const selectedIds = getSelectedIds();

                // Create hidden inputs for each selected ID
                selectedIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'leave_ids[]';
                    input.value = id;
                    this.appendChild(input);
                });

                // The form will now submit with all the necessary data
            });

            // Function to get selected IDs (only from table checkboxes)
            function getSelectedIds() {
                const selectedIds = [];
                document.querySelectorAll('.leave-checkbox:checked').forEach(checkbox => {
                    selectedIds.push(checkbox.value);
                });
                return selectedIds;
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            // Use event delegation for dynamically created rows
            $('#table-id').on('click', '.more-link', function(e) {
                e.preventDefault();
                var shortComment = $(this).siblings('.short-comment');
                var fullComment = $(this).siblings('.full-comment');
                if (fullComment.is(':visible')) {
                    shortComment.show();
                    fullComment.hide();
                    $(this).text('more');
                } else {
                    shortComment.hide();
                    fullComment.show();
                    $(this).text('less');
                }
            });
        });
    </script>
</body>

</html>