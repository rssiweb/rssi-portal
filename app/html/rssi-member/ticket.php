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

$ticket_id = $_GET['ticket_id'] ?? '';
$comment = $_POST['comment'] ?? '';
$status = $_POST['status'] ?? '';
$assigned_to = $_POST['assigned_to'] ?? '';
$ticket = null;
$comments = [];

// Initialize default status value
$latest_status = '';

// Function to handle database insertion and prevent form resubmission
function handleInsertion($con, $query, $params)
{
    $result = pg_query_params($con, $query, $params);
    if ($result) {
        echo '<script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>';
    }
}

// Fetch ticket details with raised_by information
if ($ticket_id) {
    $result = pg_query_params($con, "
        SELECT st.*, rm.fullname AS raised_by_name, rm.phone AS raised_by_contact, rm.email AS raised_by_email 
        FROM support_ticket st
        LEFT JOIN rssimyaccount_members rm ON st.raised_by = rm.associatenumber
        WHERE st.ticket_id = $1
    ", array($ticket_id));
    if ($result) {
        $ticket = pg_fetch_assoc($result);
    }

    // Check if ticket was fetched successfully
    if ($ticket === false) {
        echo '<p class="text-danger">Error fetching ticket details.</p>';
    } else {
        // Fetch comments for the ticket with commented_by information
        $result = pg_query_params($con, "
            SELECT sc.*, rm.fullname AS commenter_name, rm.phone AS commenter_contact, rm.email AS commenter_email 
            FROM support_comment sc
            LEFT JOIN rssimyaccount_members rm ON sc.commented_by = rm.associatenumber
            WHERE sc.ticket_id = $1
            ORDER BY sc.timestamp DESC
        ", array($ticket_id));
        if ($result) {
            $comments = pg_fetch_all($result);
        }

        // Handle status insertion
        if ($status) {
            $inserted_by = $_SESSION['aid'];
            handleInsertion($con, "
                INSERT INTO support_ticket_status (ticket_id, status, updated_by) 
                VALUES ($1, $2, $3)
            ", array($ticket_id, $status, $inserted_by));
        }

        // Handle assigned_to insertion
        if ($assigned_to) {
            $inserted_by = $_SESSION['aid'];
            handleInsertion($con, "
                INSERT INTO support_ticket_assignment (ticket_id, assigned_to, assigned_by) 
                VALUES ($1, $2, $3)
            ", array($ticket_id, $assigned_to, $inserted_by));
        }

        // Handle new comment submission
        if ($comment) {
            $commented_by = $_SESSION['aid'];
            handleInsertion($con, "
                INSERT INTO support_comment (ticket_id, timestamp, comment, commented_by) 
                VALUES ($1, NOW(), $2, $3)
            ", array($ticket_id, $comment, $commented_by));

            // Refresh comments after inserting the new one
            $result = pg_query_params($con, "
                SELECT sc.*, rm.fullname AS commenter_name, rm.phone AS commenter_contact, rm.email AS commenter_email 
                FROM support_comment sc
                LEFT JOIN rssimyaccount_members rm ON sc.commented_by = rm.associatenumber
                WHERE sc.ticket_id = $1
                ORDER BY sc.timestamp DESC
            ", array($ticket_id));
            if ($result) {
                $comments = pg_fetch_all($result);
            }
        }

        // Fetch the latest status from support_ticket_status
        $result = pg_query_params($con, "
            SELECT status 
            FROM support_ticket_status 
            WHERE ticket_id = $1 
            ORDER BY timestamp DESC 
            LIMIT 1
        ", array($ticket_id));

        if ($result) {
            $latest_status_row = pg_fetch_assoc($result);
            $latest_status = $latest_status_row['status'] ?? ''; // Handle missing 'status' key gracefully
        }

        // Fetch the latest assigned_to from support_ticket_assignment
        $result = pg_query_params($con, "
            SELECT assigned_to 
            FROM support_ticket_assignment 
            WHERE ticket_id = $1 
            ORDER BY timestamp DESC 
            LIMIT 1
        ", array($ticket_id));

        if ($result) {
            $latest_assigned_row = pg_fetch_assoc($result);
            $ticket['assigned_to'] = $latest_assigned_row['assigned_to'] ?? ''; // Handle missing 'assigned_to' key gracefully
        }
    }
}

// Fetch data for Select2 dropdown (associates/students)
$select2_result = pg_query($con, "SELECT associatenumber AS id, fullname AS text FROM rssimyaccount_members WHERE filterstatus='Active'");
$results = pg_fetch_all($select2_result);
?>



<!DOCTYPE html>
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
    <title>Support Ticket Comments</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Fetch the assigned_to value from PHP
            const selectedValue = '<?php echo htmlspecialchars($ticket['assigned_to'] ?? ''); ?>';
            const hasResults = <?php echo json_encode(!empty($results)); ?>;

            // Define Select2 options
            const select2Options = {
                data: <?php echo json_encode($results); ?>,
                placeholder: "Assign to",
                allowClear: true,
                tags: false,
                templateResult: function(option) {
                    if (!option.id) {
                        return option.text;
                    }
                    return $('<span>').text(`${option.text} (${option.id})`);
                },
                templateSelection: function(option) {
                    return option.text;
                }
            };

            // Initialize Select2 for the Assigned To input field
            const $select = $('#assigned_to').select2(select2Options);

            // Set the selected value if there are results
            if (hasResults) {
                if (selectedValue) {
                    $select.val(selectedValue).trigger('change'); // Set the value and trigger change event
                } else {
                    $select.val(null).trigger('change'); // Set null if no value
                }
            } else {
                // No results, so just show the placeholder
                $select.val(null).trigger('change'); // Ensure placeholder is shown
            }
        });
    </script>

</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Create Ticket</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Support 360</a></li>
                    <li class="breadcrumb-item active">Create Ticket</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Ticket Details and Commenting -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">

                            <div class="container my-5">
                                <!-- Ticket ID Form -->
                                <form method="GET" class="mb-4">
                                    <div class="mb-3">
                                        <label for="ticket_id" class="form-label">Enter Ticket ID</label>
                                        <input type="text" class="form-control" id="ticket_id" name="ticket_id" placeholder="Enter Ticket ID" value="<?php echo htmlspecialchars($ticket_id); ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">View Ticket</button>
                                </form>

                                <?php if ($ticket): ?>
                                    <!-- Display Ticket Information -->
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <span>Ticket Details</span>
                                            <!-- Form to update Assigned To and Status -->
                                            <form method="POST" class="d-flex">
                                                <!-- Assigned To -->
                                                <div class="me-2">
                                                    <select id="assigned_to" name="assigned_to" class="form-select" style="width:max-content">
                                                        <!-- Options will be populated dynamically via PHP using Select2 -->
                                                        <?php if (empty($results)): ?>
                                                            <!-- Show a placeholder if there are no results -->
                                                            <option value="" selected disabled>Assign to</option>
                                                        <?php else: ?>
                                                            <?php foreach ($results as $result): ?>
                                                                <option value="<?php echo htmlspecialchars($result['id']); ?>" <?php echo $result['id'] === $ticket['assigned_to'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($result['text']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </select>
                                                </div>

                                                <!-- Status -->
                                                <div class="me-2">
                                                    <select id="status" name="status" class="form-select">
                                                        <option value="" disabled>Select Status</option>
                                                        <option value="In Progress" <?php echo $latest_status === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                        <option value="Resolved" <?php echo $latest_status === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                        <option value="Closed" <?php echo $latest_status === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                                                    </select>
                                                </div>

                                                <!-- Update Button -->
                                                <button type="submit" class="btn btn-primary">Update</button>
                                            </form>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Ticket ID:</strong> <?php echo htmlspecialchars($ticket['ticket_id']); ?></p>
                                            <p><strong>Subject:</strong> <?php echo htmlspecialchars($ticket['short_description']); ?></p>
                                            <p><strong>Description:</strong> <?php echo htmlspecialchars($ticket['long_description']); ?></p>
                                            <p><strong>Raised By:</strong> <?php echo htmlspecialchars($ticket['raised_by_name']); ?></p>
                                            <p><strong>Contact:</strong> <?php echo htmlspecialchars($ticket['raised_by_contact']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($ticket['raised_by_email']); ?></p>
                                        </div>
                                    </div>

                                    <!-- Comments Section -->
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Comments</h5>

                                            <ul class="list-group">
                                                <?php foreach ($comments as $comment): ?>
                                                    <li class="list-group-item">
                                                        <p><strong><?php echo htmlspecialchars($comment['commenter_name']); ?>:</strong></p>
                                                        <p><?php echo htmlspecialchars($comment['comment']); ?></p>
                                                        <p><small><em><?php echo htmlspecialchars((new DateTime($comment['timestamp']))->format('d/m/Y h:i A')); ?></em></small></p>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>

                                            <!-- Add Comment -->
                                            <form method="POST" class="mt-4">
                                                <div class="mb-3">
                                                    <label for="comment" class="form-label">Add Comment</label>
                                                    <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Submit Comment</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </section>

    </main><!-- End #main -->

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

</body>

</html>