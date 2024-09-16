<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php"); // Make sure you have the email sending function
include("../../util/drive.php");

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
$category = isset($_POST['category']) ? json_encode($_POST['category']) : '[]';
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
    // Base query to fetch ticket details with raised_by information
    $ticketQuery = "
        SELECT st.*, rm.fullname AS raised_by_name, rm.phone AS raised_by_contact, rm.email AS raised_by_email, rm.photo AS raised_by_photo 
        FROM support_ticket st
        LEFT JOIN rssimyaccount_members rm ON st.raised_by = rm.associatenumber
        WHERE st.ticket_id = $1
    ";

    // Apply role-based filtering for non-admin users
    if ($role !== 'Admin') {
        $ticketQuery .= " AND (
                            st.raised_by = '" . pg_escape_string($con, $associatenumber) . "' 
                            OR EXISTS (
                                SELECT 1
                                FROM support_ticket_assignment sa
                                WHERE sa.ticket_id = st.ticket_id
                                AND sa.assigned_to = '" . pg_escape_string($con, $associatenumber) . "'
                            )
                        )";
    }

    // Execute the query for ticket details
    $result = pg_query_params($con, $ticketQuery, array($ticket_id));
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

            // Send email notification to the newly assigned person
            $result = pg_query_params($con, "
        SELECT rm.email, rm.fullname 
        FROM rssimyaccount_members rm 
        WHERE rm.associatenumber = $1
    ", array($assigned_to));

            if ($result) {
                $assigned_person = pg_fetch_assoc($result);
                if (!empty($assigned_person['email']) && ($status != 'Closed' && $status != 'Resolved')) {
                    sendEmail("ticketassign", array(
                        "ticket_id" => $ticket_id,
                        "assigned_to_name" => $assigned_person['fullname'],
                        "assigned_to_id" => $assigned_to,
                        "short_description" => $ticket['short_description'],
                        "severity" => $ticket['severity'],
                        "category" => $ticket['category']
                    ), $assigned_person['email'], False);
                }
                if (!empty($assigned_person['email']) && ($status == 'Closed' || $status == 'Resolved')) {
                    $recipients = $assigned_person['email'] . ',' . $ticket['raised_by_email'];

                    sendEmail("ticketStatus", array(
                        "ticket_id" => $ticket_id,
                        "short_description" => $ticket['short_description'],
                        "severity" => $ticket['severity'],
                        "status" => $status,
                        "category" => $ticket['category']
                    ), $recipients, false);
                }
            }
        }


        // Handle new comment submission
        if ($comment) {
            $commented_by = $_SESSION['aid'];
            // Upload and insert passbook page if provided
            $doclink = null;
            $filename = null;
            if (!empty($_FILES['attachment']['name'])) {
                $attachment = $_FILES['attachment'];
                // $filename = $ticket_id . "_" . time();
                $filename = basename($attachment['name']); // Extract original file name
                $parent = '19j8P2pM1kSy3Dc_Clr-GcQlYCl5ZMAiQ';
                $doclink = uploadeToDrive($attachment, $parent, $filename);
            }
            handleInsertion($con, "
                INSERT INTO support_comment (ticket_id, timestamp, comment, commented_by,attachment,attachment_name) 
                VALUES ($1, NOW(), $2, $3, $4, $5)
            ", array($ticket_id, $comment, $commented_by, $doclink, $filename));

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
            $latest_comment = ($result) ? pg_fetch_assoc($result) : [];
            // Send email notification to the commenter
            // if (!empty($latest_comment['commenter_email'])) {
            //     sendEmail("ticketcomment_self", array(
            //         "ticket_id" => $ticket_id,
            //         "short_description" => $ticket['short_description'],
            //         "comment" => $latest_comment['comment'],
            //         "commentby_name" => $latest_comment['commenter_name'],
            //         "commentby_id" => $latest_comment['commented_by'],
            //         "timestamp" => @date("d/m/Y g:i a", strtotime($latest_comment['timestamp']))
            //     ), $latest_comment['commenter_email'], False);
            // }
            // Send email notification to raised by
            if (!empty($ticket['raised_by_email']) && ($ticket['raised_by_email'] != $latest_comment['commenter_email'])) {
                sendEmail("ticketcomment_others", array(
                    "ticket_id" => $ticket_id,
                    "short_description" => $ticket['short_description'],
                    "severity" => $ticket['severity'],
                    "category" => $ticket['category'],
                    "comment" => $latest_comment['comment'],
                    "commentby_name" => $latest_comment['commenter_name'],
                    "commentby_initials" => strtoupper(implode('', array_map(function ($part) {
                        return $part[0];
                    }, explode(' ', $latest_comment['commenter_name'])))),
                    "commentby_id" => $latest_comment['commented_by'],
                    "ticket_raisedby_name" => $ticket['raised_by_name'],
                    "ticket_raisedby_id" => $ticket['raised_by'],
                ), $ticket['raised_by_email'], False);
            }

            // Fetch the latest assigned_to person for the ticket and their details
            $result = pg_query_params($con, "
    SELECT sta.assigned_to, rm.email, rm.fullname 
    FROM support_ticket_assignment sta
    JOIN rssimyaccount_members rm ON sta.assigned_to = rm.associatenumber
    WHERE sta.ticket_id = $1
    ORDER BY sta.timestamp DESC
    LIMIT 1
", array($ticket_id));

            if ($result && pg_num_rows($result) > 0) { // Check if the query result is valid and not empty
                $assigned_person = pg_fetch_assoc($result);
                $assigned_to = $assigned_person['assigned_to']; // Fetch the assigned_to value from the result
                if (!empty($assigned_person['email']) && ($assigned_person['email'] != $latest_comment['commenter_email'])) {
                    sendEmail("ticketcomment_others", array(
                        "ticket_id" => $ticket_id,
                        "short_description" => $ticket['short_description'],
                        "severity" => $ticket['severity'],
                        "category" => $ticket['category'],
                        "comment" => $latest_comment['comment'],
                        "commentby_name" => $latest_comment['commenter_name'],
                        "commentby_initials" => strtoupper(implode('', array_map(function ($part) {
                            return $part[0];
                        }, explode(' ', $latest_comment['commenter_name'])))),
                        "commentby_id" => $latest_comment['commented_by'],
                        "ticket_assigned_to_name" => $assigned_person['fullname'],
                        "ticket_assigned_to_id" => $assigned_to, // Now $assigned_to should have the correct value
                    ), $assigned_person['email']);
                }
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

// Assuming $con is your PostgreSQL connection resource
// and $ticket['raised_for'] contains the JSON-encoded IDs

$members = [];
if (!empty($ticket['raised_for'])) {
    // Decode the JSON string to an array of IDs
    $raisedForArray = json_decode($ticket['raised_for'], true);

    if (is_array($raisedForArray) && count($raisedForArray) > 0) {
        // Sanitize and format IDs for use in SQL query
        $escapedIds = array_map(function ($id) use ($con) {
            return "'" . pg_escape_string($con, $id) . "'";
        }, $raisedForArray);

        $idList = implode(',', $escapedIds);

        // Query to fetch data from both tables separately
        $sqlMembers = "
            SELECT fullname AS name, associatenumber AS id
            FROM rssimyaccount_members
            WHERE associatenumber IN ($idList);
        ";

        $sqlStudents = "
            SELECT studentname AS name, student_id AS id
            FROM rssimyprofile_student
            WHERE student_id IN ($idList);
        ";

        // Execute the queries
        $resultMembers = pg_query($con, $sqlMembers);
        $resultStudents = pg_query($con, $sqlStudents);

        if ($resultMembers) {
            $members = pg_fetch_all($resultMembers);
        }

        if ($resultStudents) {
            $students = pg_fetch_all($resultStudents);
            // Merge student results into members array
            $members = array_merge($members, $students);
        }
    }
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
if (isset($_POST['category_update'])) {
    // Update the category in the database
    pg_query_params($con, "
        UPDATE support_ticket 
        SET category = $1 
        WHERE ticket_id = $2
    ", array($category, $ticket_id));

    // Refresh the category after updating it
    $result = pg_query_params($con, "
        SELECT category 
        FROM support_ticket 
        WHERE ticket_id = $1
    ", array($ticket_id));

    if ($result) {
        $ticket['category'] = pg_fetch_result($result, 0, 'category');
    }
}
?>
<?php
if (!function_exists('makeClickableLinks')) {
    function makeClickableLinks($text)
    {
        // Regular expression to identify URLs in the text
        $text = preg_replace(
            '~(https?://[^\s]+)~i', // Match URLs starting with http or https
            '<a href="$1" target="_blank">$1</a>', // Replace with anchor tag
            $text
        );
        return $text;
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
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .hidden-record {
            display: none;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
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
                                            <form method="POST" class="d-flex">
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
                                                <button type="submit" class="btn btn-primary">Update</button>
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
                                                                <h4 class="fw-bold"><?php echo $ticket['short_description']; ?></h4>
                                                            </div>

                                                            <!-- Description -->
                                                            <div class="mb-3">
                                                                <p class="mb-0"><?php echo nl2br(makeClickableLinks($ticket['long_description'])); ?></p>
                                                            </div>

                                                            <!-- Raised for -->
                                                            <div class="mb-3">
                                                                Concerned Individual:
                                                                <?php if ($members): ?>
                                                                    <?php foreach ($members as $member): ?>
                                                                        <p class="mb-0"><?php echo htmlspecialchars($member['name']) . ' (' . htmlspecialchars($member['id']) . ')'; ?></p>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <p class="text-muted">No members found.</p>
                                                                <?php endif; ?>
                                                            </div>


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
                                                                                <input type="hidden" name="category_update" value="1">
                                                                                <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket_id, ENT_QUOTES, 'UTF-8'); ?>">

                                                                                <!-- Category -->
                                                                                <div class="mb-3">
                                                                                    <select id="category" name="category[]" class="form-control" multiple="multiple" style="width:100%">
                                                                                        <?php
                                                                                        // Decode current categories
                                                                                        $current_categories = !empty($ticket['category']) ? json_decode($ticket['category'], true) : [];

                                                                                        // Generate options
                                                                                        foreach ($categories as $type => $category_list) {
                                                                                            echo '<optgroup label="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '">';
                                                                                            foreach ($category_list as $category) {
                                                                                                $selected = in_array($category, $current_categories) ? ' selected' : '';
                                                                                                echo '<option value="' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '"' . $selected . '>'
                                                                                                    . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '</option>';
                                                                                            }
                                                                                            echo '</optgroup>';
                                                                                        }
                                                                                        ?>
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

                                                                <p class="mt-2 mb-0"><?php echo nl2br(makeClickableLinks($comment['comment'])); ?></p>
                                                                <!-- Supporting Documents -->
                                                                <?php if (!empty($comment['attachment'])): ?>
                                                                    <div class="mb-3">
                                                                        <a href="<?php echo htmlspecialchars($comment['attachment']); ?>" target="_blank"><?php echo $comment['attachment_name']; ?></a>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>

                                                <!-- Add Comment -->
                                                <form method="POST" enctype="multipart/form-data" class="mt-4">
                                                    <div class="mb-3">
                                                        <label for="comment" class="form-label">Add Comment</label>
                                                        <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="attachment" class="form-label">Attachment</label>
                                                        <input class="form-control" type="file" id="attachment" name="attachment">
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">Submit Comment</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-danger">We could not locate the ticket with ID <?php echo htmlspecialchars($ticket_id); ?>. This may be because the ticket does not exist or you do not have permission to access it. Please verify the details and try again.</p>
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