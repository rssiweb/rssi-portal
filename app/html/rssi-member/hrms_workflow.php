<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

// Start output buffering to prevent header issues
ob_start();

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get data from AJAX request
    $action = $_POST['action'];
    $associatenumber = $_POST['associatenumber'];
    $fieldname = $_POST['fieldname'];
    $workflow_id = $_POST['workflow_id']; // Assuming the workflow ID is passed with the request

    // Fetch the details for email notification
    // Fetch the details for email notification along with current and submitted values
    $details_query = "SELECT 
    members.fullname, 
    members.email,
    members.$fieldname AS current_value,
    workflow.submission_timestamp,
    workflow.submitted_value,
    workflow.workflow_id
 FROM hrms_workflow AS workflow
 JOIN rssimyaccount_members AS members 
    ON workflow.associatenumber = members.associatenumber
 WHERE workflow.workflow_id = $1";
    $details_result = pg_query_params($con, $details_query, [$workflow_id]);
    $details_row = pg_fetch_assoc($details_result);

    if ($details_row) {
        $requestedby_email = $details_row['email'];
        $requestedby_name = $details_row['fullname'];
        $requested_on = date('d/m/Y h:i A', strtotime($details_row['submission_timestamp']));
        $oldvalue = $details_row['current_value'];
        $newvalue = $details_row['submitted_value'];
    }

    // Approve Process
    if ($action === 'approve') {
        $value = $_POST['value'];

        // Begin transaction to ensure atomicity (both updates happen together)
        pg_query($con, "BEGIN");

        // Update the workflow table to mark the approval
        $query = "UPDATE hrms_workflow
          SET reviewer_status = 'Approved', 
              reviewer_id = $1, 
              reviewed_on = NOW()
          WHERE workflow_id = $2";

        // Prepare and execute the query
        $result = pg_query_params($con, $query, array($associatenumber, $workflow_id));

        if ($result) {
            // Sanitize the field name to prevent SQL injection
            $fieldname = pg_escape_string($con, $fieldname);

            // Construct the update query dynamically for rssimyaccount_members table
            $update_query = "UPDATE rssimyaccount_members SET $fieldname = $1 WHERE associatenumber = $2";
            $update_result = pg_query_params($con, $update_query, [$value, $associatenumber]);

            if ($update_result) {
                // Commit the transaction if both updates were successful
                pg_query($con, "COMMIT");

                if (!empty($requestedby_email)) {
                    sendEmail("hrms_review", [
                        "requested_by_name" => $requestedby_name,
                        "requested_on" => $requested_on,
                        "reviewer_status" => $action === 'approve' ? 'Approved' : 'Rejected',
                        "fieldname" => $fieldname,
                        "oldvalue" => $oldvalue,
                        "newvalue" => $newvalue,
                        "hide_approve" => 'style="display: none;"'
                    ], $requestedby_email,false);
                }
                echo "Field '$fieldname' has been approved and updated successfully.";
                exit;
            } else {
                // Rollback the transaction if the second update failed
                pg_query($con, "ROLLBACK");
                echo "Error updating field value in the members table.";
                exit;
            }
        } else {
            // Rollback the transaction if the workflow update failed
            pg_query($con, "ROLLBACK");
            echo "Error approving the change request.";
            exit;
        }
    }

    // Rejection Process
    if ($action === 'reject') {
        // Update the workflow table to mark the rejection
        $query = "UPDATE hrms_workflow
          SET reviewer_status = 'Rejected', 
              reviewer_id = $1, 
              reviewed_on = NOW()
          WHERE workflow_id = $2";

        // Prepare and execute the query
        $result = pg_query_params($con, $query, array($associatenumber, $workflow_id));

        if ($result) {
            if (!empty($requestedby_email)) {
                sendEmail("hrms_review", [
                    "requested_by_name" => $requestedby_name,
                    "requested_on" => $requested_on,
                    "reviewer_status" => $action === 'approve' ? 'Approved' : 'Rejected',
                    "fieldname" => $fieldname,
                    "hide_reject" => 'style="display: none;"'
                ], $requestedby_email,false);
            }
            echo "Field '$fieldname' has been rejected.";
            exit;
        } else {
            echo "Error rejecting the change request.";
            exit;
        }
    }
}
?>
<?php
// Query to get only the latest pending workflow record for each associate and field where status is still 'Pending'
$query = "
    SELECT workflow.workflow_id, 
           workflow.associatenumber, 
           workflow.fieldname, 
           workflow.submitted_value, 
           workflow.submission_timestamp, 
           members.fullname, 
           members.phone, 
           members.email
    FROM hrms_workflow AS workflow
    JOIN rssimyaccount_members AS members 
        ON workflow.associatenumber = members.associatenumber
    WHERE workflow.reviewer_status = 'Pending'
    AND workflow.workflow_id = (
        SELECT workflow_id
        FROM hrms_workflow
        WHERE associatenumber = workflow.associatenumber 
          AND fieldname = workflow.fieldname
        ORDER BY submission_timestamp DESC
        LIMIT 1
    )
    ORDER BY workflow.associatenumber, workflow.fieldname, workflow.submission_timestamp DESC
";

$result = pg_query($con, $query);
$data = [];

// Process the result to dynamically fetch current values
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        // Dynamically set the fieldname to fetch current value for the specified field
        $fieldname = pg_escape_string($con, $row['fieldname']);

        // Build query to get the current value from the members table
        $currentValueQuery = "
            SELECT $fieldname AS current_value 
            FROM rssimyaccount_members 
            WHERE associatenumber = '{$row['associatenumber']}'
        ";

        $currentValueResult = pg_query($con, $currentValueQuery);
        $currentValueRow = pg_fetch_assoc($currentValueResult);

        // Add the current value to the row or mark as not available
        $row['current_value'] = $currentValueRow['current_value'] ?? '<em>Not Available</em>';

        // Add the processed row to the data array
        $data[] = $row;
    }
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

    <title>HRMS Workflow</title>

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
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>

</head>

<body>

    <body>
        <?php include 'inactive_session_expire_check.php'; ?>
        <?php include 'header.php'; ?>

        <main id="main" class="main">

            <div class="pagetitle">
                <h1>HRMS Workflow</h1>
                <nav>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">Work</a></li>
                        <li class="breadcrumb-item active">HRMS Workflow</li>
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

                                    <?php if (!empty($data)) : ?>
                                        <!-- Table for Pending Approvals -->
                                        <table class="table" id="table-id">
                                            <thead>
                                                <tr>
                                                    <th>Workflow Id</th>
                                                    <th>Submitted on</th>
                                                    <th>Associatenumber</th>
                                                    <th>Full Name</th>
                                                    <th>Field Name</th>
                                                    <th>Current Value</th>
                                                    <th>Submitted Value</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data as $row) : ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['workflow_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo isset($row['submission_timestamp']) ? (new DateTime($row['submission_timestamp']))->format('d/m/Y h:i a') : '<em>No timestamp</em>'; ?></td>
                                                        <td><?php echo htmlspecialchars($row['associatenumber'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo htmlspecialchars($row['fullname'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo htmlspecialchars($row['fieldname'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo htmlspecialchars($row['current_value'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo htmlspecialchars($row['submitted_value'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td class="text-center">
                                                            <!-- Approve: Using a checkmark (tick) icon -->
                                                            <i class="bi bi-check-circle-fill text-success approve-btn mx-3"
                                                                data-associatenumber="<?php echo htmlspecialchars($row['associatenumber'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-fieldname="<?php echo htmlspecialchars($row['fieldname'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-value="<?php echo htmlspecialchars($row['submitted_value'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-workflowid="<?php echo htmlspecialchars($row['workflow_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                style="cursor: pointer;"></i>

                                                            <!-- Reject: Using an X icon -->
                                                            <i class="bi bi-x-circle-fill text-danger reject-btn"
                                                                data-associatenumber="<?php echo htmlspecialchars($row['associatenumber'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-fieldname="<?php echo htmlspecialchars($row['fieldname'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-workflowid="<?php echo htmlspecialchars($row['workflow_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                style="cursor: pointer;"></i>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else : ?>
                                        <p>No pending approvals found.</p>
                                    <?php endif; ?>
                                </div>

                                <!-- Bootstrap 5 Modal for confirmation -->
                                <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="approvalModalLabel">Approval Confirmation</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to approve this change request?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-primary" id="confirm-approve">Confirm</button>
                                            </div>
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

        <!-- Vendor JS Files -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
        <!-- Template Main JS File -->
        <script src="../assets_new/js/main.js"></script>

        <script>
            $(document).ready(function() {
                // Handle approve button click
                $('.approve-btn').click(function() {
                    const associatenumber = $(this).data('associatenumber');
                    const fieldname = $(this).data('fieldname');
                    const value = $(this).data('value');
                    const workflowid = $(this).data('workflowid');

                    // Store data in modal and show it
                    $('#approvalModal').modal('show');

                    // Handle confirm button click
                    $('#confirm-approve').off('click').on('click', function() {
                        $.ajax({
                            url: 'hrms_workflow.php',
                            method: 'POST',
                            data: {
                                action: 'approve',
                                associatenumber: associatenumber,
                                fieldname: fieldname,
                                value: value,
                                workflow_id: workflowid
                            },
                            success: function(response) {
                                alert(response); // Show the success message from PHP
                                window.location.reload(); // Reload the page to reflect the updates
                            },
                            error: function(xhr, status, error) {
                                alert("An error occurred: " + error);
                            }
                        });
                        $('#approvalModal').modal('hide');
                    });
                });

                // Handle reject button click
                $('.reject-btn').click(function() {
                    const associatenumber = $(this).data('associatenumber');
                    const fieldname = $(this).data('fieldname');
                    const workflowid = $(this).data('workflowid');

                    // Directly reject the field in the workflow
                    $.ajax({
                        url: 'hrms_workflow.php',
                        method: 'POST',
                        data: {
                            action: 'reject',
                            associatenumber: associatenumber,
                            fieldname: fieldname,
                            workflow_id: workflowid
                        },
                        success: function(response) {
                            alert(response); // Show the rejection message
                            window.location.reload(); // Reload the page to reflect the updates
                        },
                        error: function(xhr, status, error) {
                            alert("An error occurred: " + error);
                        }
                    });
                });
            });
        </script>
        <script>
            $(document).ready(function() {
                // Check if resultArr is empty
                <?php if (!empty($data)) : ?>
                    // Initialize DataTables only if resultArr is not empty
                    $('#table-id').DataTable({
                        // paging: false,
                        "order": [] // Disable initial sorting
                        // other options...
                    });
                <?php endif; ?>
            });
        </script>
    </body>

</html>