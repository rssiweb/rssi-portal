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
$category = isset($_POST['category']) ? json_encode($_POST['category']) : '[]'; // Serialize the array to JSON
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
        SELECT st.*, rm.fullname AS raised_by_name, rm.phone AS raised_by_contact, rm.email AS raised_by_email, rm.photo AS raised_by_photo 
        FROM support_ticket st
        LEFT JOIN rssimyaccount_members rm ON st.raised_by = rm.associatenumber
        WHERE st.ticket_id = $1
    ", array($ticket_id));
    if ($result) {
        $ticket = pg_fetch_assoc($result);
    }

    // Check if ticket was fetched successfully
    if ($ticket === false) {
        // echo '<p class="text-danger">Error fetching ticket details.</p>';
    } else {
        // Fetch comments for the ticket with commented_by information
        $result = pg_query_params($con, "
            SELECT sc.*, rm.fullname AS commenter_name, rm.phone AS commenter_contact, rm.email AS commenter_email, rm.photo AS commenter_photo 
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
                SELECT sc.*, rm.fullname AS commenter_name, rm.phone AS commenter_contact, rm.email AS commenter_email,rm.photo AS commenter_photo 
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

// Fetch data for dropdown
$dropdown_result = pg_query($con, "SELECT associatenumber AS id, fullname FROM rssimyaccount_members WHERE filterstatus='Active'");
$results = pg_fetch_all($dropdown_result);

// Fetch all assignment history from the support_ticket_assignment table
$assignment_results = pg_query_params($con, "
    SELECT sc.assigned_to, rm.fullname AS assigned_name, sc.assigned_by, sc.timestamp
    FROM support_ticket_assignment sc
    LEFT JOIN rssimyaccount_members rm ON sc.assigned_to = rm.associatenumber
    WHERE sc.ticket_id = $1 
    ORDER BY sc.timestamp DESC
", array($ticket_id));

$assignments = [];
if ($assignment_results) {
    $assignments = pg_fetch_all($assignment_results);
}
// Query to fetch categories from the database
$query = "SELECT category_type, category_name FROM ticket_categories ORDER BY category_type, category_name";
$result = pg_query($con, $query);

// Initialize an array to store categories
$categories = [];

while ($row = pg_fetch_assoc($result)) {
    $categories[$row['category_type']][] = $row['category_name'];
}

// Handle category update
if ($category) {
    // Update the category in the database
    pg_query_params($con, "
        UPDATE support_ticket 
        SET category = $1 
        WHERE ticket_id = $2
    ", array($category, $ticket_id));

    // Fetch the updated category data
    $result = pg_query_params($con, "
    SELECT category 
    FROM support_ticket 
    WHERE ticket_id = $1
", array($ticket_id));

    if ($result) {
        $updated_category = pg_fetch_assoc($result);
        // Decode the category JSON to an array
        $current_categories = !empty($updated_category['category']) ? json_decode($updated_category['category'], true) : [];
    }
}

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
    <style>
        .hidden-record {
            display: none;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Ticket dashboard</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Support 360</a></li>
                    <li class="breadcrumb-item active">Ticket dashboard</li>
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
                                            <form method="POST" class="d-flex mt-3">
                                                <!-- Assigned To -->
                                                <div class="me-2">
                                                    <select id="assigned_to" name="assigned_to" class="form-select">
                                                        <option value="" <?php echo empty($ticket['assigned_to']) ? 'selected' : ''; ?>>Clear Selection</option>
                                                        <?php foreach ($results as $result): ?>
                                                            <option value="<?php echo htmlspecialchars($result['id']); ?>" <?php echo $result['id'] === $ticket['assigned_to'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($result['fullname']); ?> (<?php echo htmlspecialchars($result['id']); ?>)
                                                            </option>
                                                        <?php endforeach; ?>
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
                                                <button type="submit" class="btn btn-primary" name="update_assigned_status">Update</button>
                                            </form>
                                        </div>

                                        <div class="card-body mt-2">
                                            <div class="card-body mt-2">
                                                <div class="row">
                                                    <!-- Main Content (Left) -->
                                                    <div class="col-md-8">
                                                        <div class="row mb-4">
                                                            <!-- Ticket Raiser Information -->
                                                            <div class="col-md-12">
                                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                                    <div class="d-flex align-items-center">
                                                                        <!-- Profile Image -->
                                                                        <div class="me-3">
                                                                            <img src="<?php echo htmlspecialchars($ticket['raised_by_photo']); ?>" alt="Profile Image" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                                                                        </div>
                                                                        <!-- Name and Timestamp -->
                                                                        <div>
                                                                            <h6 class="mb-1"><?php echo htmlspecialchars($ticket['raised_by_name']); ?></h6>
                                                                            <p class="text-muted mb-0"><?php echo htmlspecialchars((new DateTime($ticket['timestamp']))->format('d/m/Y h:i A')); ?></p>
                                                                        </div>
                                                                    </div>
                                                                    <!-- Tags -->
                                                                    <div class="d-flex align-items-center flex-wrap">
                                                                        <span class="badge bg-light text-dark border me-2">
                                                                            <?php echo htmlspecialchars($ticket['action']); ?>
                                                                        </span>
                                                                        <span class="badge bg-light text-dark border">
                                                                            <?php echo htmlspecialchars($ticket['severity']); ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row mb-4">
                                                            <!-- Subject -->
                                                            <div class="mb-3">
                                                                <h4 class="fw-bold"><?php echo htmlspecialchars($ticket['short_description']); ?></h4>
                                                            </div>

                                                            <!-- Description -->
                                                            <div class="mb-3">
                                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($ticket['long_description'])); ?></p>
                                                            </div>

                                                            <!-- Raised for -->
                                                            <?php
                                                            // Check if 'raised_for' is not empty and decode the JSON string to an array
                                                            if (!empty($ticket['raised_for'])):
                                                                $raisedForArray = json_decode($ticket['raised_for'], true);

                                                                if (is_array($raisedForArray) && count($raisedForArray) > 0):
                                                                    // Generate placeholders for the SQL query
                                                                    $placeholders = implode(',', array_map(function ($index) {
                                                                        return '$' . ($index + 1);
                                                                    }, array_keys($raisedForArray)));

                                                                    // Prepare the SQL query
                                                                    $sql = "SELECT associatenumber, fullname FROM rssimyaccount_members WHERE associatenumber IN ($placeholders)";

                                                                    // Prepare the query
                                                                    $query = pg_prepare($con, "fetch_members", $sql);

                                                                    // Execute the query with the array of IDs
                                                                    $result = pg_execute($con, "fetch_members", $raisedForArray);

                                                                    // Fetch all results
                                                                    $members = pg_fetch_all($result);

                                                            ?>
                                                                    <div class="mb-3">
                                                                        Concerned Individual:
                                                                        <?php if ($members): ?>
                                                                            <?php foreach ($members as $member): ?>
                                                                                <p class="mb-0"><?php echo htmlspecialchars($member['fullname']) . ' (' . htmlspecialchars($member['associatenumber']) . ')'; ?></p>
                                                                            <?php endforeach; ?>
                                                                        <?php endif; ?>
                                                                    </div>
                                                            <?php
                                                                endif;
                                                            endif;
                                                            ?>

                                                            <!-- Supporting Documents -->
                                                            <?php if (!empty($ticket['upload_file'])): ?>
                                                                <div class="mb-3">
                                                                    <a href="<?php echo htmlspecialchars($ticket['upload_file']); ?>" target="_blank">View Attachment</a>
                                                                </div>
                                                            <?php endif; ?>

                                                            <!-- Link to open the category form modal -->
                                                            <a href="#" data-bs-toggle="modal" data-bs-target="#categoryModal">Update Category</a>

                                                            <!-- Modal for updating category -->
                                                            <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="categoryModalLabel">Update Category</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <form method="POST" id="categoryForm">
                                                                                <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket_id); ?>">

                                                                                <!-- Category -->
                                                                                <div class="mb-3">
                                                                                    <select id="category" name="category[]" class="form-control" multiple="multiple" style="width:100%">
                                                                                        <!-- <option value="">Select a category...</option> -->
                                                                                        <?php
                                                                                        // Decode the current category from JSON to an array
                                                                                        $current_categories = !empty($ticket['category']) ? json_decode($ticket['category'], true) : [];

                                                                                        // Generate the options
                                                                                        foreach ($categories as $type => $category_list): ?>
                                                                                            <optgroup label="<?php echo htmlspecialchars($type); ?>">
                                                                                                <?php foreach ($category_list as $category): ?>
                                                                                                    <option value="<?php echo htmlspecialchars($category); ?>"
                                                                                                        <?php echo in_array($category, $current_categories) ? 'selected' : ''; ?>>
                                                                                                        <?php echo htmlspecialchars($category); ?>
                                                                                                    </option>
                                                                                                <?php endforeach; ?>
                                                                                            </optgroup>
                                                                                        <?php endforeach; ?>
                                                                                    </select>
                                                                                </div>
                                                                            </form>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                            <button type="submit" form="categoryForm" class="btn btn-primary">Update</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <h6 class="fw-bold">Workflow</h6>
                                                        <ul class="list-unstyled" id="workflow-list">
                                                            <!-- Ticket Raised -->
                                                            <li class="mb-2">
                                                                <div class="d-flex justify-content-between">
                                                                    <span><?php echo htmlspecialchars($ticket['raised_by_name']); ?> (Raised)</span>
                                                                    <span class="text-muted"><?php echo htmlspecialchars((new DateTime($ticket['timestamp']))->format('d/m/Y h:i A')); ?></span>
                                                                </div>
                                                            </li>

                                                            <!-- Assigned To -->
                                                            <?php foreach ($assignments as $index => $assignment): ?>
                                                                <?php if ($index < 5): // Show only the first 5 assignments 
                                                                ?>
                                                                    <li class="mb-2">
                                                                        <div class="d-flex justify-content-between">
                                                                            <span><?php echo htmlspecialchars($assignment['assigned_name']); ?></span>
                                                                            <span class="text-muted"><?php echo htmlspecialchars((new DateTime($assignment['timestamp']))->format('d/m/Y h:i A')); ?></span>
                                                                        </div>
                                                                    </li>
                                                                <?php else: // Hide remaining assignments initially 
                                                                ?>
                                                                    <li class="mb-2 hidden-record">
                                                                        <div class="d-flex justify-content-between">
                                                                            <span><?php echo htmlspecialchars($assignment['assigned_name']); ?></span>
                                                                            <span class="text-muted"><?php echo htmlspecialchars((new DateTime($assignment['timestamp']))->format('d/m/Y h:i A')); ?></span>
                                                                        </div>
                                                                    </li>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>

                                                            <!-- Show More/Show Less Option -->
                                                            <?php if (count($assignments) > 5): ?>
                                                                <li class="mb-2">
                                                                    <a href="#" id="toggle-more" class="text-decoration-none">Show More...</a>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Comments Section -->
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Comments</h5>

                                                <ul class="list-group p-0">
                                                    <?php foreach ($comments as $comment): ?>
                                                        <li class="list-group-item d-flex align-items-start mb-3 p-0 border-0">
                                                            <img src="<?php echo isset($comment['commenter_photo']) ? $comment['commenter_photo'] : 'https://res.cloudinary.com/hs4stt5kg/image/upload/v1609410219/faculties/blank.jpg'; ?>" alt="Commenter Image" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">

                                                            <div class="w-100">
                                                                <div class="d-flex align-items-center">
                                                                    <strong class="me-2"><?php echo htmlspecialchars($comment['commenter_name']); ?></strong>
                                                                    <small class="text-muted" style="white-space: nowrap;">
                                                                        <?php
                                                                        $commentTime = new DateTime($comment['timestamp']);
                                                                        $now = new DateTime();
                                                                        $interval = $now->diff($commentTime);

                                                                        if ($interval->days > 0) {
                                                                            echo htmlspecialchars($commentTime->format('d/m/Y h:i A'));
                                                                        } elseif ($interval->h > 0) {
                                                                            echo htmlspecialchars($interval->h . ' hours ago');
                                                                        } elseif ($interval->i > 0) {
                                                                            echo htmlspecialchars($interval->i . ' minutes ago');
                                                                        } else {
                                                                            echo htmlspecialchars($interval->s . ' seconds ago');
                                                                        }
                                                                        ?>
                                                                    </small>
                                                                </div>
                                                                <p class="mt-2 mb-0"><?php echo htmlspecialchars($comment['comment']); ?></p>
                                                            </div>
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
                                    </div>
                                <?php else: ?>
                                    <p class="text-danger">We could not locate the ticket with ID <?php echo htmlspecialchars($ticket_id); ?>. Please verify the details and try again.</p>
                                <?php endif; ?>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toggleLink = document.getElementById('toggle-more');
            var hiddenRecords = document.querySelectorAll('.hidden-record');

            // Initially set the link text to "Show More"
            var isShowingMore = false;

            // Toggle Show More/Show Less functionality
            toggleLink.addEventListener('click', function(event) {
                event.preventDefault();
                if (isShowingMore) {
                    // Hide additional records and update the link text
                    hiddenRecords.forEach(function(record) {
                        record.style.display = 'none';
                    });
                    toggleLink.textContent = 'Show More...';
                } else {
                    // Show additional records and update the link text
                    hiddenRecords.forEach(function(record) {
                        record.style.display = 'list-item';
                    });
                    toggleLink.textContent = 'Show Less...';
                }
                isShowingMore = !isShowingMore; // Toggle the state
            });
        });
    </script>
    <!-- Include the select2 script -->
    <script>
        $(document).ready(function() {
            $('#category').select2({
                dropdownParent: $('#categoryModal'),
                placeholder: "Select a category...",
                allowClear: true,
                // Other select2 options if needed
            });
        });
    </script>


</body>

</html>