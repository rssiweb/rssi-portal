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
            $inserted_by = $associatenumber;
            handleInsertion($con, "
                INSERT INTO support_ticket_status (ticket_id, status, updated_by) 
                VALUES ($1, $2, $3)
            ", array($ticket_id, $status, $inserted_by));
        }

        // Handle assigned_to insertion
        if ($assigned_to) {
            $inserted_by = $associatenumber;
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
                    ), $assigned_person['email']);
                }
                if (!empty($assigned_person['email']) && ($status == 'Closed' || $status == 'Resolved')) {
                    $recipients = $assigned_person['email'] . ',' . $ticket['raised_by_email'];

                    sendEmail("ticket_status", array(
                        "ticket_id" => $ticket_id,
                        "short_description" => $ticket['short_description'],
                        "severity" => $ticket['severity'],
                        "status" => $status,
                        "category" => $ticket['category']
                    ), $recipients);
                }
            }
        }


        // Handle new comment submission
        if ($comment) {
            $commented_by = $associatenumber;
            // Upload and insert passbook page if provided
            $doclink = null;
            $filename = null;
            if (!empty($_FILES['attachment']['name'])) {
                $attachment = $_FILES['attachment'];
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

            // Fetch the latest assigned_to person for the ticket and their details
            $result = pg_query_params($con, "
        SELECT sta.assigned_to, rm.email, rm.fullname 
        FROM support_ticket_assignment sta
        JOIN rssimyaccount_members rm ON sta.assigned_to = rm.associatenumber
        WHERE sta.ticket_id = $1
        ORDER BY sta.timestamp DESC
        LIMIT 1
    ", array($ticket_id));

            $recipients = [];
            if (!empty($ticket['raised_by_email']) && ($ticket['raised_by_email'] != $latest_comment['commenter_email'])) {
                $recipients[] = $ticket['raised_by_email'];
            }

            if ($result && pg_num_rows($result) > 0) {
                $assigned_person = pg_fetch_assoc($result);
                if (!empty($assigned_person['email']) && ($assigned_person['email'] != $latest_comment['commenter_email'])) {
                    $recipients[] = $assigned_person['email'];
                }
            }

            if (!empty($recipients)) {
                // Send email notification to all recipients
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
                    "ticket_assigned_to_name" => isset($assigned_person) ? $assigned_person['fullname'] : null,
                    "ticket_assigned_to_id" => isset($assigned_person) ? $assigned_person['assigned_to'] : null,
                ), implode(',', $recipients));
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

// Fetch all assignment history from the support_ticket_assignment table
$assignment_results = pg_query_params($con, "
WITH assignment_status AS (
    SELECT 
        sta.ticket_id,
        sta.assigned_to,
        rm.fullname AS assigned_to_name,
        sta.assigned_by,
        sta.timestamp AS assigned_timestamp,
        sts.status,
        sts.updated_by,
        sts.timestamp AS status_timestamp,
        ABS(EXTRACT(EPOCH FROM (sts.timestamp - sta.timestamp))) AS time_diff
    FROM 
        support_ticket_assignment sta
        LEFT JOIN 
    rssimyaccount_members rm ON sta.assigned_to = rm.associatenumber -- Join to get assigned_to_name
    LEFT JOIN 
        support_ticket_status sts ON sta.ticket_id = sts.ticket_id
        AND sts.timestamp >= sta.timestamp - INTERVAL '1 second'
        AND sts.timestamp <= sta.timestamp + INTERVAL '1 second'
    WHERE 
        sta.ticket_id = $1
),
closest_status AS (
    SELECT 
        ticket_id,
        assigned_to,
        assigned_by,
        assigned_timestamp,
        status,
        updated_by, -- Include updated_by here
        status_timestamp,
        assigned_to_name,
        ROW_NUMBER() OVER (
            PARTITION BY ticket_id, assigned_to, assigned_by, assigned_timestamp
            ORDER BY time_diff ASC
        ) AS rn
    FROM 
        assignment_status
)
SELECT 
    sta.assigned_to,
    cs.status,
    cs.status_timestamp AS formatted_status_timestamp,
    cs.assigned_to_name,
    cs.updated_by -- Make sure updated_by is included in the final select
FROM 
    support_ticket_assignment sta
LEFT JOIN 
    closest_status cs ON sta.ticket_id = cs.ticket_id
    AND sta.assigned_to = cs.assigned_to
    AND sta.assigned_by = cs.assigned_by
    AND sta.timestamp = cs.assigned_timestamp
    AND cs.rn = 1
WHERE 
    sta.ticket_id = $1
ORDER BY 
    sta.timestamp DESC;
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

// Get the current action type for this ticket
$current_action = $ticket['action'] ?? '';

// Handle category update with AJAX
if (isset($_POST['category_update'])) {
    $category = isset($_POST['category']) ? $_POST['category'] : [];

    // Filter out empty values and trim whitespace
    $filtered_categories = array_filter($category, function ($cat) {
        return !empty(trim($cat));
    });

    // Remove duplicates and reindex array
    $filtered_categories = array_values(array_unique($filtered_categories));

    if (empty($filtered_categories)) {
        echo json_encode(['success' => false, 'message' => 'Please select at least one category']);
        exit;
    }

    // Proceed with the update
    $category_json = json_encode($filtered_categories, JSON_UNESCAPED_UNICODE);
    $result = pg_query_params($con, "
        UPDATE support_ticket 
        SET category = $1 
        WHERE ticket_id = $2
    ", array($category_json, $ticket_id));

    if ($result) {
        // CRITICAL: Fetch FRESH data from database after update
        $refresh_result = pg_query_params($con, "
            SELECT category 
            FROM support_ticket 
            WHERE ticket_id = $1
        ", array($ticket_id));

        if ($refresh_result) {
            $ticket['category'] = pg_fetch_result($refresh_result, 0, 'category');
        }

        echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update category. Please try again.']);
    }
    exit;
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
    <?php include 'includes/meta.php' ?>
    
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>
    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
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
                                                    <select id="assigned_to" name="assigned_to" class="form-select" required>
                                                        <option value="">Clear Selection</option>
                                                        <?php if (!empty($ticket['assigned_to'])):
                                                            // Fetch the specific assigned person's details
                                                            $assigned_query = "SELECT associatenumber as id, fullname 
                                                            FROM rssimyaccount_members 
                                                            WHERE associatenumber = $1";
                                                            $assigned_result = pg_query_params($con, $assigned_query, array($ticket['assigned_to']));
                                                            if ($assigned_result && pg_num_rows($assigned_result) > 0):
                                                                $assigned_person = pg_fetch_assoc($assigned_result);
                                                        ?>
                                                                <option value="<?php echo htmlspecialchars($assigned_person['id']); ?>" selected>
                                                                    <?php echo htmlspecialchars($assigned_person['fullname']); ?> (<?php echo htmlspecialchars($assigned_person['id']); ?>)
                                                                </option>
                                                        <?php endif;
                                                        endif; ?>
                                                    </select>
                                                </div>

                                                <!-- Status -->
                                                <div class="me-2">
                                                    <select id="status" name="status" class="form-select" required>
                                                        <option disabled>Select Status</option>
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
                                                            <div class="mb-3 d-flex align-items-start" id="subject-container">
                                                                <!-- Subject Text with Pencil Icon -->

                                                                <h4 class="fw-bold mb-0" id="subject-text">
                                                                    <?php echo $ticket['short_description']; ?>
                                                                </h4>

                                                                <!-- Edit Pencil Icon (Visible only to the raised_by user) -->
                                                                <?php if ($ticket['raised_by'] == $associatenumber): ?>
                                                                    <i class="bi bi-pencil edit-icon ms-2" id="edit-subject" style="cursor: pointer;"
                                                                        onclick="editSubject()" title="Edit Subject"></i>
                                                                <?php endif; ?>


                                                                <!-- Editable Input - Hidden by default -->
                                                                <div id="subject-edit-container" class="d-none mt-2 w-100">
                                                                    <form id="subject-form" method="POST" action="payment-api.php">
                                                                        <input type="hidden" name="form-type" value="short_description">
                                                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                                                        <input type="text" id="subject-input" class="form-control mb-2" name="value"
                                                                            value="<?php echo $ticket['short_description']; ?>">
                                                                        <!-- Submit Button to Save -->
                                                                        <div class="d-flex">
                                                                            <button type="submit" id="save-subject" class="btn">
                                                                                <i class="bi bi-save save-icon"></i>
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>

                                                            <script>
                                                                // Function to toggle between the subject text and editable input
                                                                function editSubject() {
                                                                    // Hide the text and show the input field
                                                                    document.getElementById('subject-text').classList.add('d-none');
                                                                    document.getElementById('edit-subject').classList.add('d-none');
                                                                    document.getElementById('subject-edit-container').classList.remove('d-none');
                                                                }
                                                            </script>

                                                            <div class="mb-3 align-items-start position-relative" id="description-container">
                                                                <!-- Description Text -->
                                                                <p class="mb-0 flex-grow-1" id="description-text">
                                                                    <?php echo nl2br(makeClickableLinks($ticket['long_description'])); ?>
                                                                </p>

                                                                <!-- Edit Pencil Icon (Only visible if the user is allowed to edit) -->
                                                                <?php if ($ticket['raised_by'] == $associatenumber): ?>
                                                                    <i class="bi bi-pencil edit-icon position-absolute" id="edit-description" style="cursor: pointer; top: 0; right: 10px;" onclick="editDescription()" title="Edit Description"></i>
                                                                <?php endif; ?>

                                                                <!-- Editable Textarea - Hidden by default -->
                                                                <div id="description-edit-container" class="d-none mt-2 w-100">
                                                                    <form id="description-form" method="POST" action="payment-api.php">
                                                                        <input type="hidden" name="form-type" value="long_description">
                                                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                                                        <textarea id="description-input" class="form-control mb-2 w-100" name="value"><?php echo $ticket['long_description']; ?></textarea>
                                                                        <!-- Submit Button to Save -->
                                                                        <div class="d-flex justify-content-start">
                                                                            <button type="submit" id="save-description" class="btn">
                                                                                <i class="bi bi-save save-icon"></i>
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>

                                                            <script>
                                                                // Toggle description text and text area visibility
                                                                function editDescription() {
                                                                    // Hide the text and show the textarea
                                                                    document.getElementById('description-text').classList.add('d-none');
                                                                    document.getElementById('edit-description').classList.add('d-none');
                                                                    document.getElementById('description-edit-container').classList.remove('d-none');
                                                                }
                                                            </script>


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

                                                                                <!-- Current Action Display -->
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Current Action</label>
                                                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_action); ?>" readonly>
                                                                                    <div class="form-text">Categories are filtered based on the selected action</div>
                                                                                </div>

                                                                                <!-- Category -->
                                                                                <div class="mb-3">
                                                                                    <label for="category" class="form-label">Category</label>
                                                                                    <select id="category" name="category[]" class="form-control" multiple="multiple" style="width:100%" disabled>
                                                                                        <option value="">Loading categories...</option>
                                                                                    </select>
                                                                                    <!-- Loading indicator below the input field -->
                                                                                    <div class="d-none mt-2" id="categoryLoading">
                                                                                        <div class="d-flex align-items-center">
                                                                                            <div class="spinner-border spinner-border-sm text-secondary me-2" role="status">
                                                                                                <span class="visually-hidden">Loading...</span>
                                                                                            </div>
                                                                                            <span class="small text-muted">Loading categories...</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </form>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                            <button type="submit" form="categoryForm" class="btn btn-primary" id="updateCategoryBtn" disabled>Update</button>
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
                                                                    <span class="text-wrap" style="max-width: 50%; word-wrap: break-word;"><?php echo htmlspecialchars($ticket['raised_by_name']); ?> (Raised)</span>
                                                                    <span class="text-muted" style="max-width: 50%; text-align: left;"><?php echo htmlspecialchars((new DateTime($ticket['timestamp']))->format('d/m/Y h:i A')); ?></span>
                                                                </div>
                                                            </li>

                                                            <!-- Assigned To -->
                                                            <?php foreach ($assignments as $index => $assignment): ?>
                                                                <?php if ($index < 5): // Show only the first 5 assignments 
                                                                ?>
                                                                    <li class="mb-2">
                                                                        <div class="d-flex justify-content-between">
                                                                            <span class="text-wrap" style="max-width: 50%; word-wrap: break-word;"><?php echo htmlspecialchars($assignment['assigned_to_name']); ?>/<?php echo htmlspecialchars($assignment['status']); ?></span>
                                                                            <span class="text-muted" style="max-width: 50%; text-align: left;"
                                                                                data-bs-toggle="tooltip"
                                                                                title="Updated by: <?php echo htmlspecialchars($assignment['updated_by']); ?>">
                                                                                <?php echo htmlspecialchars((new DateTime($assignment['formatted_status_timestamp']))->format('d/m/Y h:i A')); ?>
                                                                            </span>
                                                                        </div>
                                                                    </li>
                                                                <?php else: // Hide remaining assignments initially 
                                                                ?>
                                                                    <li class="mb-2 hidden-record">
                                                                        <div class="d-flex justify-content-between">
                                                                            <span class="text-wrap" style="max-width: 50%; word-wrap: break-word;"><?php echo htmlspecialchars($assignment['assigned_to_name']); ?>/<?php echo htmlspecialchars($assignment['status']); ?></span>
                                                                            <span class="text-muted" style="max-width: 50%; text-align: left;"
                                                                                data-bs-toggle="tooltip"
                                                                                title="Updated by: <?php echo htmlspecialchars($assignment['updated_by']); ?>">
                                                                                <?php echo htmlspecialchars((new DateTime($assignment['formatted_status_timestamp']))->format('d/m/Y h:i A')); ?>
                                                                            </span>
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

                                                    <script>
                                                        // Enable Bootstrap tooltip
                                                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                                                        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                                                            new bootstrap.Tooltip(tooltipTriggerEl);
                                                        });
                                                    </script>



                                                </div>
                                            </div>
                                        </div>


                                        <!-- Comments Section -->
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Comments</h5>

                                                <ul class="list-group p-0">
                                                    <?php foreach ($comments as $comment): ?>
                                                        <li class="list-group-item d-flex align-items-start mb-3 p-0 border-0" id="comment-<?php echo $comment['comment_id']; ?>">
                                                            <img src="<?php echo isset($comment['commenter_photo']) ? $comment['commenter_photo'] : 'https://res.cloudinary.com/hs4stt5kg/image/upload/v1609410219/faculties/blank.jpg'; ?>" alt="Commenter Image" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">

                                                            <div class="w-100">
                                                                <div class="d-flex justify-content-between">
                                                                    <div>
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
                                                                        <?php if ($comment['edit_flag'] == true): ?>
                                                                            &nbsp;<small class="text-muted">Edited</small>
                                                                        <?php endif; ?>
                                                                    </div>

                                                                </div>

                                                                <p class="mt-2 mb-0" id="comment-text-<?php echo $comment['comment_id']; ?>">
                                                                    <?php echo nl2br(makeClickableLinks($comment['comment'])); ?>
                                                                </p>
                                                                <!-- Supporting Documents -->
                                                                <?php if (!empty($comment['attachment'])): ?>
                                                                    <div class="mb-3">
                                                                        <a href="<?php echo htmlspecialchars($comment['attachment']); ?>" target="_blank">
                                                                            <?php echo htmlspecialchars($comment['attachment_name']); ?>
                                                                        </a>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <?php if ($comment['commented_by'] == $associatenumber): ?>
                                                                    <!-- Hidden Textarea (Initially hidden) -->
                                                                    <form id="comment-edit-form-<?php echo $comment['comment_id']; ?>" method="POST" action="payment-api.php">
                                                                        <input type="hidden" name="form-type" value="comment_edit">
                                                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                                                        <div id="edit-container-<?php echo $comment['comment_id']; ?>" class="d-none mt-2">
                                                                            <textarea class="form-control" id="comment-input-<?php echo $comment['comment_id']; ?>" name="comment"><?php echo htmlspecialchars(trim($comment['comment'])); ?></textarea>
                                                                            <button type="button" class="btn btn-sm btn-primary mt-2" onclick="saveCommentEdit(<?php echo $comment['comment_id']; ?>)">Save</button>
                                                                            <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="cancelEdit(<?php echo $comment['comment_id']; ?>)">Cancel</button>
                                                                        </div>
                                                                    </form>

                                                                    <!-- Edit and Delete options -->
                                                                    <div class="d-flex align-items-center mt-2">
                                                                        <span class="text-muted" style="cursor: pointer;" onclick="editComment(<?php echo $comment['comment_id']; ?>)">Edit</span>
                                                                        <span class="text-muted mx-2">.</span>
                                                                        <span class="text-muted" style="cursor: pointer;" onclick="deleteComment(<?php echo $comment['comment_id']; ?>)">Delete</span>
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
    <script src="../assets_new/js/text-refiner.js?v=1.2.0"></script>

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
    <script>
        $(document).ready(function() {
            const currentAction = '<?php echo $current_action; ?>';

            // Initialize Select2 when modal is shown
            $('#categoryModal').on('shown.bs.modal', function() {
                initializeCategorySelect2();
                loadCategoriesForAction(currentAction);
            });

            // Reset button state when modal is hidden
            $('#categoryModal').on('hidden.bs.modal', function() {
                resetButtonState();
            });

            function initializeCategorySelect2() {
                $('#category').select2({
                    dropdownParent: $('#categoryModal'),
                    placeholder: "Select a category...",
                    allowClear: true,
                    width: '100%'
                }).on('change', function() {
                    validateCategorySelection();
                });
            }

            function loadCategoriesForAction(action) {
                const $categorySelect = $('#category');
                const $categoryLoading = $('#categoryLoading');
                const $updateBtn = $('#updateCategoryBtn');

                // Reset and disable category field
                $categorySelect.empty().prop('disabled', true);
                $categorySelect.trigger('change');

                // Show loading spinner below the field
                $categoryLoading.removeClass('d-none');
                $updateBtn.prop('disabled', true);

                if (!action) {
                    $categorySelect.prop('disabled', true);
                    $categoryLoading.addClass('d-none');
                    return;
                }

                // AJAX call to get categories AND current selections
                $.ajax({
                    url: 'get_categories.php',
                    type: 'GET',
                    data: {
                        action: action,
                        ticket_id: '<?php echo $ticket_id; ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        $categorySelect.empty();

                        if (response.categories && response.categories.length > 0) {
                            // Add only actual categories, no empty option
                            response.categories.forEach(function(category) {
                                $categorySelect.append(
                                    $('<option>', {
                                        value: category,
                                        text: category
                                    })
                                );
                            });

                            // Enable the select
                            $categorySelect.prop('disabled', false);

                            // Use current categories from AJAX response (fresh from DB)
                            if (response.current_categories && response.current_categories.length > 0) {
                                $('#category').val(null).trigger('change');
                                setTimeout(function() {
                                    $('#category').val(response.current_categories).trigger('change');
                                }, 100);
                            }

                        } else {
                            $categorySelect.append('<option value="" disabled>No categories available</option>');
                            $categorySelect.prop('disabled', true);
                        }

                        // Hide loading spinner
                        $categoryLoading.addClass('d-none');
                        $categorySelect.trigger('change');
                        validateCategorySelection();
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to load categories:', error);

                        $categorySelect.empty();
                        $categorySelect.prop('disabled', true);
                        $categoryLoading.addClass('d-none');
                        $categorySelect.trigger('change');

                        alert('Failed to load categories. Please try again.');
                    }
                });
            }

            function validateCategorySelection() {
                const selectedCategories = $('#category').val();
                const filteredCategories = selectedCategories ? selectedCategories.filter(function(category) {
                    return category !== "" && category !== null && category !== undefined;
                }) : [];

                const isValid = filteredCategories.length > 0;
                $('#updateCategoryBtn').prop('disabled', !isValid);
            }

            function resetButtonState() {
                const $btn = $('#updateCategoryBtn');
                $btn.prop('disabled', true).html('Update');
            }

            // AJAX form submission
            $('#categoryForm').on('submit', function(e) {
                e.preventDefault();

                const selectedCategories = $('#category').val();
                const filteredCategories = selectedCategories ? selectedCategories.filter(function(category) {
                    return category !== "" && category !== null && category !== undefined;
                }) : [];

                if (filteredCategories.length === 0) {
                    alert('Please select at least one category');
                    return false;
                }

                // Show loading state on button
                const $btn = $('#updateCategoryBtn');
                const originalText = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Updating...');

                // AJAX submission
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            $('#categoryModal').modal('hide');
                            // No need to refresh data - next modal open will fetch fresh from DB
                        } else {
                            alert(response.message);
                            resetButtonState();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        alert('An error occurred while updating category. Please try again.');
                        resetButtonState();
                    }
                });
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Subject edit toggle
            const subjectText = document.getElementById("subject-text");
            const subjectEditContainer = document.getElementById("subject-edit-container");
            const editSubject = document.getElementById("edit-subject");

            // Description edit toggle
            const descriptionText = document.getElementById("description-text");
            const descriptionEditContainer = document.getElementById("description-edit-container");
            const editDescription = document.getElementById("edit-description");

            // Toggle Subject Edit Mode
            editSubject.addEventListener("click", function() {
                subjectText.classList.add("d-none");
                subjectEditContainer.classList.remove("d-none");
            });

            // Toggle Description Edit Mode
            editDescription.addEventListener("click", function() {
                descriptionText.classList.add("d-none");
                descriptionEditContainer.classList.remove("d-none");
            });

            // Handle form submission
            document.getElementById("subject-form").addEventListener("submit", function(event) {
                event.preventDefault(); // Prevent the form from submitting normally

                const formData = new FormData(this); // Gather form data
                submitForm(formData);
            });

            document.getElementById("description-form").addEventListener("submit", function(event) {
                event.preventDefault(); // Prevent the form from submitting normally

                const formData = new FormData(this); // Gather form data
                submitForm(formData);
            });

            // Function to submit form data using AJAX
            function submitForm(formData) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "payment-api.php", true);

                // Handle the response
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Success alert and page reload
                            alert("Data has been successfully updated!");
                            location.reload(); // Reload the page to show updated data
                        } else {
                            // Failure alert
                            alert(response.message || "Update failed. Please try again.");
                        }
                    }
                };

                // Send the form data
                xhr.send(formData);
            }
        });
    </script>
    <script>
        // Function to switch from the paragraph to textarea for editing
        function editComment(commentId) {
            // Hide the <p> tag and show the textarea form
            document.getElementById("comment-text-" + commentId).classList.add("d-none"); // Hide the paragraph
            document.getElementById("edit-container-" + commentId).classList.remove("d-none"); // Show the textarea
        }

        // Function to cancel editing and revert to the original comment text
        function cancelEdit(commentId) {
            document.getElementById("comment-text-" + commentId).classList.remove("d-none"); // Show the paragraph
            document.getElementById("edit-container-" + commentId).classList.add("d-none"); // Hide the textarea
        }
        // Function to save edited comment via AJAX
        function saveCommentEdit(commentId) {
            const commentText = document.getElementById("comment-input-" + commentId).value;

            const formData = new FormData();
            formData.append('form-type', 'comment_edit');
            formData.append('comment_id', commentId);
            formData.append('comment', commentText);

            fetch('payment-api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json()) // Parse the response as JSON
                .then(data => {
                    if (data.success) {
                        alert("Comment updated successfully.");
                        location.reload(); // Reload the page to show updated data
                    } else {
                        // Show error message from the server if available
                        alert(data.message || "Failed to update comment.");
                    }
                })
                .catch(error => {
                    // Log the error for debugging
                    console.error('Error:', error);
                    alert("An error occurred while updating the comment."); // General error alert
                });
        }

        // Function to delete comment via AJAX
        function deleteComment(commentId) {
            const formData = new FormData();
            formData.append('form-type', 'comment_delete');
            formData.append('comment_id', commentId);

            fetch('payment-api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Comment deleted successfully.");
                        location.reload(); // Reload the page to show updated data
                    } else {
                        alert("Failed to delete comment.");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("An error occurred while deleting the comment.");
                });
        }
    </script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for associate numbers
            $('#assigned_to').select2({
                ajax: {
                    url: 'fetch_associates.php',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term, // search term
                            isActive: true // only active associates
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: 'Select associate(s)',
                allowClear: true,
                width: '300px'
                // multiple: true
            });
        });
    </script>
</body>

</html>