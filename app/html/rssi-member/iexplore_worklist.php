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

// Prepare statements once before any processing
$insertQuery = "
    INSERT INTO wbt_status (associatenumber, courseid, timestamp, f_score, email) 
    VALUES ($1, $2, $3, $4, $5)
";
pg_prepare($con, "insert_wbt_status", $insertQuery);

$updateQuery = "
    UPDATE external_exam_scores
    SET 
        status = $1,
        reviewed_by = $2,
        reviewed_on = $3,
        remarks = $4
    WHERE id = $5
";
pg_prepare($con, "update_external_score", $updateQuery);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle bulk approval/rejection
    if (isset($_POST['bulk_status'], $_POST['selected_ids'], $_POST['bulk_remarks'])) {
        $selected_ids = explode(',', $_POST['selected_ids']);
        $bulk_status = ($_POST['bulk_status'] === 'Approved') ? 'approved' : 'rejected';
        $bulk_remarks = trim($_POST['bulk_remarks']);
        $reviewer_id = $associatenumber;

        if (!empty($selected_ids) && !empty($bulk_status)) {
            // Escape and format the ID list properly
            $escaped_ids = array_map(function ($id) use ($con) {
                return "'" . pg_escape_string($con, trim($id)) . "'";
            }, $selected_ids);
            $ids_string = implode(',', $escaped_ids);

            // First, fetch all records to be updated for email notifications
            $query_fetch = "
                SELECT e.*, m.fullname, m.email, w.coursename
                FROM external_exam_scores e
                LEFT JOIN rssimyaccount_members m ON e.associate_number = m.associatenumber
                LEFT JOIN wbt w ON e.course_id = w.courseid
                WHERE e.id IN ($ids_string)
            ";
            $result_fetch = pg_query($con, $query_fetch);

            if ($result_fetch) {
                // Process each record
                while ($row = pg_fetch_assoc($result_fetch)) {
                    $externalScoreId = $row['id'];
                    $courseId = $row['course_id'];
                    $applicantid = $row['associate_number'];
                    $email = $row['email'];
                    $applicantname = $row['fullname'];
                    $coursename = $row['coursename'];
                    $supportingfile = $row['supporting_file'];

                    if ($bulk_status === 'approved') {
                        // Insert data into wbt_status for approved requests
                        $timestamp = $row['completion_date'];
                        $fScore = $row['score'] / 100;

                        $insertResult = pg_execute($con, "insert_wbt_status", [
                            $applicantid,
                            $courseId,
                            $timestamp,
                            $fScore,
                            $email
                        ]);

                        if (!$insertResult) {
                            error_log("Error inserting into wbt_status for ID: $externalScoreId");
                        }
                    }

                    // Update the external_exam_scores table
                    $updateResult = pg_execute($con, "update_external_score", [
                        $bulk_status,
                        $reviewer_id,
                        date('Y-m-d H:i:s'),
                        $bulk_remarks,
                        $externalScoreId
                    ]);

                    if ($updateResult) {
                        // Send email notification only for rejected requests
                        if (!empty($email) && $bulk_status === 'rejected') {
                            $emailData = [
                                "template" => "external_course_reject",
                                "data" => [
                                    "courseId" => $courseId,
                                    "coursename" => $coursename,
                                    "applicantid" => $applicantid,
                                    "name" => $applicantname,
                                    "date" => date('Y-m-d H:i:s'),
                                    "remarks" => $bulk_remarks,
                                    "score" => $row['score'],
                                    "doclink" => $supportingfile
                                ],
                                "email" => $email
                            ];

                            sendEmail($emailData['template'], $emailData['data'], $emailData['email'], false);
                        }
                    }
                }

                echo "<script>alert('Bulk review applied successfully.'); if (window.history.replaceState) {
                        // Update the URL without causing a page reload or resubmission
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload();</script>";
            }
        }
    }
}

// Fetch only the latest external score requests per course and associate
$query = "
WITH ranked_submissions AS (
    SELECT 
        e.*,
        m.fullname,
        w.coursename,
        ROW_NUMBER() OVER (
            PARTITION BY e.course_id, e.associate_number 
            ORDER BY e.submission_time DESC
        ) as rn
    FROM external_exam_scores e
    LEFT JOIN rssimyaccount_members m ON e.associate_number = m.associatenumber
    LEFT JOIN wbt w ON e.course_id = w.courseid
),
latest_pending_submissions AS (
    SELECT * FROM ranked_submissions 
    WHERE rn = 1 AND (status IS NULL OR status = 'pending')
)
SELECT * FROM latest_pending_submissions
ORDER BY submission_time DESC;
";
$result = pg_query($con, $query);

if (!$result) {
    die("Error fetching external score requests.");
}
?>

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

    <title>iExplore Worklist</title>

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
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }
    </style>
    <!-- Add DataTables CSS and JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <style>
        tbody tr {
            cursor: pointer;
        }

        tbody tr:hover {
            background-color: #f5f5f5;
        }

        .form-check-input {
            margin-left: 0;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>iExplore Worklist</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Worklist</a></li>
                    <li class="breadcrumb-item active">iExplore Worklist</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>
                            <?php if ($role == 'Admin') { ?>
                                <div class="row mb-3">
                                    <div class="col-md-12 text-end">
                                        <button id="bulk-review-button" class="btn btn-primary" disabled>Bulk Review (0)</button>
                                    </div>
                                </div>
                            <?php } ?>

                            <div class="table-responsive">
                                <table class="table" id="worklist-table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>ID</th>
                                            <th>Course Name</th>
                                            <th>Associate Name</th>
                                            <th>Completion Date</th>
                                            <th>Score</th>
                                            <th>Supporting File</th>
                                            <th>Submission Timestamp</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = pg_fetch_assoc($result)) : ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox"
                                                        class="form-check-input"
                                                        name="selected_ids[]"
                                                        value="<?= htmlspecialchars($row['id']); ?>"
                                                        <?= ($row['status'] === 'approved' || $row['status'] === 'rejected') ? 'disabled' : ''; ?>>
                                                </td>
                                                <td><?= htmlspecialchars($row['id']); ?></td>
                                                <td><?= htmlspecialchars($row['coursename']); ?></td>
                                                <td><?= htmlspecialchars($row['fullname']); ?></td>
                                                <td><?= date('d/m/Y', strtotime($row['completion_date'])); ?></td>
                                                <td><?= htmlspecialchars($row['score']); ?>%</td>
                                                <td>
                                                    <?php if ($row['supporting_file']) : ?>
                                                        <a href="<?= htmlspecialchars($row['supporting_file']); ?>" target="_blank">View File</a>
                                                    <?php else : ?>
                                                        No file
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d/m/Y h:i A', strtotime($row['submission_time'])); ?></td>
                                                <td><?= htmlspecialchars($row['status'] ?? 'Pending'); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Bulk Review Modal -->
    <div class="modal fade" id="bulkReviewModal" tabindex="-1" aria-labelledby="bulkReviewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkReviewModalLabel">Bulk Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bulk-review-form" method="POST" action="#">
                        <input type="hidden" name="selected_ids" id="selected-ids">
                        <div class="mb-3">
                            <label for="bulk-status" class="form-label">Status</label>
                            <select name="bulk_status" id="bulk-status" class="form-select" required>
                                <option disabled selected hidden>Select Status</option>
                                <option value="Approved">Approve</option>
                                <option value="Rejected">Reject</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="bulk-remarks" class="form-label">Remarks</label>
                            <textarea name="bulk_remarks" id="bulk-remarks" class="form-control" placeholder="Enter remarks..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- [Keep your existing scripts] -->
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  <script src="../assets_new/js/text-refiner.js?v=1.1.0"></script>

    <!-- JavaScript to Trigger Modal and Set Form Values -->
    <script>
        function openModal(action, courseName, applicantName, score, completionDate, id) {
            // Set the action type and score ID in the modal form
            document.getElementById('external_score_id').value = id;
            document.getElementById('action_type').value = action.toLowerCase();

            // Update modal title and remarks label based on action type
            const modalTitle = action === 'Approve' ? 'Approve Request' : 'Reject Request';
            const remarksLabel = `Remarks for ${action}:`;
            document.getElementById('modalLabel').textContent = modalTitle;
            document.getElementById('remarksLabel').textContent = remarksLabel;

            // Set the course details in the modal
            document.getElementById('courseName').textContent = courseName;
            document.getElementById('applicantName').textContent = applicantName;
            document.getElementById('score').textContent = score;
            document.getElementById('completionDate').textContent = completionDate;

            // Show the modal
            var modal = new bootstrap.Modal(document.getElementById('approvalModal'));
            modal.show();
        }
    </script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#worklist-table').DataTable({
                "order": [], // Disable initial sorting
                "columnDefs": [{
                        "orderable": false,
                        //"targets": [0, 6]
                    } // Disable sorting for checkbox and action columns
                ]
            });

            // Make rows clickable (except for checkboxes and links)
            $('#worklist-table tbody').on('click', 'tr', function(e) {
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'A') {
                    const checkbox = $(this).find('td:first-child .form-check-input');
                    if (checkbox.length && !checkbox.prop('disabled')) {
                        checkbox.prop('checked', !checkbox.prop('checked'));
                        updateBulkReviewButton();
                    }
                }
            });

            // Update bulk review button
            function updateBulkReviewButton() {
                const selectedCount = $('#worklist-table tbody .form-check-input:checked').length;
                const bulkReviewButton = $('#bulk-review-button');
                bulkReviewButton.text(`Bulk Review (${selectedCount})`);
                bulkReviewButton.prop('disabled', selectedCount === 0);
            }

            // Attach event listeners to checkboxes
            $('#worklist-table tbody').on('change', '.form-check-input', updateBulkReviewButton);

            // Initialize bulk review modal
            $('#bulk-review-button').click(function() {
                const selectedIds = [];
                $('#worklist-table tbody .form-check-input:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length > 0) {
                    $('#selected-ids').val(selectedIds.join(','));
                    const bulkReviewModal = new bootstrap.Modal(document.getElementById('bulkReviewModal'));
                    bulkReviewModal.show();
                } else {
                    alert('Please select at least one request to proceed.');
                }
            });
        });
    </script>
</body>

</html>