<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['external_score_id'], $_POST['action_type'], $_POST['remarks'])) {
        $externalScoreId = intval($_POST['external_score_id']);
        $action = ($_POST['action_type'] === 'approve') ? 'approved' : 'rejected';
        $remarks = trim($_POST['remarks']);

        // Fetch the external score record
        $query = "
            SELECT e.*, m.fullname, m.email, w.coursename
            FROM external_exam_scores e
            LEFT JOIN rssimyaccount_members m ON e.associate_number = m.associatenumber
            LEFT JOIN wbt w ON e.course_id = w.courseid
            WHERE e.id = $1
        ";
        $stmt = pg_prepare($con, "fetch_external_score", $query);
        $result = pg_execute($con, "fetch_external_score", [$externalScoreId]);

        if ($result && pg_num_rows($result) > 0) {
            $row = pg_fetch_assoc($result);
            $courseId = $row['course_id'];
            $applicantid = $row['associate_number'];
            $email = $row['email'];
            $fullname = $row['fullname'];

            if ($action === 'approved') {
                // Insert data into wbt_status
                $timestamp = date('Y-m-d H:i:s');
                $fScore = $row['score'] / 100;

                $insertQuery = "
                    INSERT INTO wbt_status (associatenumber, courseid, timestamp, f_score, email) 
                    VALUES ($1, $2, $3, $4, $5)
                ";
                $insertStmt = pg_prepare($con, "insert_wbt_status", $insertQuery);
                $insertResult = pg_execute($con, "insert_wbt_status", [$applicantid, $courseId, $timestamp, $fScore, $email]);

                if (!$insertResult) {
                    echo "<script>alert('Error: Failed to insert into wbt_status.');</script>";
                    exit;
                }
            }

            // Send email for rejected requests
            if ($action === 'rejected') {
                $emailQuery = "SELECT email FROM rssimyaccount_members WHERE associatenumber = $1";
                $emailStmt = pg_prepare($con, "fetch_email", $emailQuery);
                $emailResult = pg_execute($con, "fetch_email", [$applicantid]);

                if ($emailResult && pg_num_rows($emailResult) > 0) {
                    $emailRow = pg_fetch_assoc($emailResult);
                    $associate_email = $emailRow['email'];

                    // Send rejection email
                    sendEmail("external_course_reject", [
                        "courseId" => $courseId,
                        "applicantid" => $applicantid,
                        "name" => $fullname,
                        "date" => date('Y-m-d H:i:s'),
                        "remarks" => $remarks,
                    ], $associate_email);
                } else {
                    error_log("No associate email found for rejection.");
                }
            }

            // Update the external_exam_scores table
            $updateQuery = "
                UPDATE external_exam_scores
                SET 
                    status = $1,
                    reviewed_by = $2,
                    reviewed_on = $3,
                    remarks = $4
                WHERE id = $5
            ";
            $updateStmt = pg_prepare($con, "update_external_score", $updateQuery);
            $updateResult = pg_execute($con, "update_external_score", [
                $action,
                $associatenumber,
                date('Y-m-d H:i:s'),
                $remarks,
                $externalScoreId
            ]);

            if ($updateResult) {
                echo "<script>alert('Request $action successfully!'); 
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.href);
                }
                window.location.reload();
                </script>";
                exit;
            } else {
                echo "<script>alert('Error: Failed to update external_exam_scores.');</script>";
                exit;
            }
        } else {
            echo "<script>alert('Error: External score record not found.');</script>";
            exit;
        }
    }
}

// Fetch all pending external score requests
$query = "
    SELECT e.*, m.fullname, w.coursename
    FROM external_exam_scores e
    LEFT JOIN rssimyaccount_members m ON e.associate_number = m.associatenumber
    LEFT JOIN wbt w ON e.course_id = w.courseid
    WHERE e.status IS NULL OR e.status = 'pending'
";
$result = pg_query($con, $query);

if (!$result) {
    die("Error fetching external score requests.");
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

    <title>iExplore Workflow</title>

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

</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>iExplore Workflow</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">iExplore Learner</a></li>
                    <li class="breadcrumb-item active">iExplore Workflow</li>
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
                            <div class="container mt-5">
                                <!-- <h1 class="mb-4">Approve External Scores</h1> -->
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Course Name</th>
                                            <th>Associate Name</th>
                                            <th>Completion Date</th>
                                            <th>Score</th>
                                            <th>Supporting File</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = pg_fetch_assoc($result)) : ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['id']); ?></td>
                                                <td><?= htmlspecialchars($row['coursename']); ?></td>
                                                <td><?= htmlspecialchars($row['fullname']); ?></td>
                                                <td><?= htmlspecialchars($row['completion_date']); ?></td>
                                                <td><?= htmlspecialchars($row['score']); ?>%</td>
                                                <td>
                                                    <?php if ($row['supporting_file']) : ?>
                                                        <a href="<?= htmlspecialchars($row['supporting_file']); ?>" target="_blank">View File</a>
                                                    <?php else : ?>
                                                        No file
                                                    <?php endif; ?>
                                                </td>
                                                <td>
    <!-- Approve/Reject Buttons -->
    <button type="button" class="btn btn-success btn-sm" onclick="openModal('Approve', '<?= $row['coursename']; ?>', '<?= $row['fullname']; ?>', '<?= $row['score']; ?>', '<?= $row['completion_date']; ?>', <?= $row['id']; ?>)">Approve</button>
    <button type="button" class="btn btn-danger btn-sm" onclick="openModal('Reject', '<?= $row['coursename']; ?>', '<?= $row['fullname']; ?>', '<?= $row['score']; ?>', '<?= $row['completion_date']; ?>', <?= $row['id']; ?>)">Reject</button>

    <!-- Bootstrap Modal -->
    <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Action Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="#">
                    <div class="modal-body">
                        <input type="hidden" name="external_score_id" id="external_score_id">
                        <input type="hidden" name="action_type" id="action_type">

                        <!-- Display Details for User Understanding -->
                        <div class="mb-3">
                            <p><strong>Course Name:</strong> <span id="courseName"></span></p>
                            <p><strong>Applicant Name:</strong> <span id="applicantName"></span></p>
                            <p><strong>Score:</strong> <span id="score"></span></p>
                            <p><strong>Completion Date:</strong> <span id="completionDate"></span></p>
                        </div>

                        <!-- Remarks Field -->
                        <div class="mb-3">
                            <label id="remarksLabel" class="form-label">Remarks for:</label>
                            <textarea name="remarks" id="remarks" class="form-control" placeholder="Enter remarks..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

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
</body>

</html>