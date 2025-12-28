<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");

// Start output buffering to prevent header issues
ob_start();

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();
// Adjust dates for same-day filtering
if (!empty($_GET['start_date']) && !empty($_GET['end_date']) && $_GET['start_date'] === $_GET['end_date']) {
    $start_date = $_GET['start_date'] . " 00:00:00";
    $end_date = $_GET['end_date'] . " 23:59:59";
} else {
    $start_date = !empty($_GET['start_date']) ? $_GET['start_date'] : NULL;
    $end_date = !empty($_GET['end_date']) ? $_GET['end_date'] : NULL;
}

// SQL query with placeholders
$query = "
    SELECT 
        events.*, 
        creator.fullname AS created_by_name, 
        reviewer.fullname AS reviewed_by_name
    FROM events
    LEFT JOIN rssimyaccount_members AS creator 
        ON events.created_by = creator.associatenumber
    LEFT JOIN rssimyaccount_members AS reviewer 
        ON events.reviewed_by = reviewer.associatenumber
    WHERE (
        ($1::timestamp IS NOT NULL AND $2::timestamp IS NOT NULL AND events.created_at BETWEEN $1::timestamp AND ($2::timestamp + interval '1 day' - interval '1 microsecond'))
        OR
        ($1::timestamp IS NULL AND $2::timestamp IS NULL AND review_status IS NULL)
    )
    ORDER BY events.created_at DESC;
";

// Prepare statement
$prep_result = pg_prepare($con, "filter_events", $query);
if (!$prep_result) {
    die("Error in preparing the statement: " . pg_last_error($con));
}

// Execute prepared statement with parameters
$result = pg_execute($con, "filter_events", array($start_date, $end_date));
if (!$result) {
    die("Error in executing the query: " . pg_last_error($con));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Worklist</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
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
            <h1>Post Worklist</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Worklist</a></li>
                    <li class="breadcrumb-item active">Post Workflow</li>
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
                            <form method="GET" action="#">
                                <label for="start_date">Start Date:</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">

                                <label for="end_date">End Date:</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : ''; ?>">

                                <button type="submit">Filter</button>
                            </form>

                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Event Name</th>
                                            <th>Event Date</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                            <th>Location</th>
                                            <th>Image</th>
                                            <th>Review Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        while ($row = pg_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['event_id']) ?></td>
                                                <td><?= htmlspecialchars($row['event_name']) ?></td>
                                                <td><?= htmlspecialchars((new DateTime($row['event_date']))->format('d/m/Y')); ?></td>

                                                <td>
                                                    <span class="short-description">
                                                        <?= htmlspecialchars(substr($row['event_description'], 0, 30)) ?>...
                                                    </span>
                                                    <span class="full-description d-none">
                                                        <?= htmlspecialchars($row['event_description']) ?>
                                                    </span>
                                                    <button class="btn btn-link toggle-description">Show More</button>
                                                </td>
                                                <td><?= htmlspecialchars($row['created_by_name']) ?><br>
                                                    <?= htmlspecialchars((new DateTime($row['created_at']))->format('d/m/Y h:i A')); ?></td>
                                                <td><?= htmlspecialchars($row['event_location']) ?></td>
                                                <td>
                                                    <?= $row['event_image_url'] ? '<a href="' . htmlspecialchars($row['event_image_url']) . '" target="_blank">Link</a>' : null ?>
                                                </td>
                                                <td>
                                                    <?php if ($row['review_status'] === 'Approved' || $row['review_status'] === 'Rejected'): ?>
                                                        <div>
                                                            <span class="badge <?= $row['review_status'] === 'Approved' ? 'bg-success' : 'bg-danger' ?>">
                                                                <?= htmlspecialchars($row['review_status']) ?>
                                                            </span>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?= htmlspecialchars($row['reviewed_by_name'] ?? 'Unknown') ?><br>
                                                                <?= htmlspecialchars((new DateTime($row['reviewed_at']))->format('d/m/Y h:i A') ?? 'N/A') ?>
                                                            </small>
                                                        </div>
                                                    <?php else: ?>
                                                        <button class="btn btn-outline-success review-action" data-id="<?= $row['event_id'] ?>" data-status="Approved" title="Approve">
                                                            &#10003;
                                                        </button>
                                                        <button class="btn btn-outline-danger review-action" data-id="<?= $row['event_id'] ?>" data-status="Rejected" title="Reject">
                                                            &#10008;
                                                        </button>
                                                    <?php endif; ?>
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
  <script src="../assets_new/js/text-refiner.js"></script>
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($result)) : ?>
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
        // Toggle description visibility
        $(document).on('click', '.toggle-description', function() {
            const row = $(this).closest('tr');
            row.find('.short-description').toggleClass('d-none');
            row.find('.full-description').toggleClass('d-none');
            $(this).text($(this).text() === 'Show More' ? 'Show Less' : 'Show More');
        });
    </script>
    <script>
        // Handle review actions
        $(document).on('click', '.review-action', function() {
            const eventId = $(this).data('id');
            const status = $(this).data('status');

            // Send POST request to handle review
            $.post('payment-api.php', {
                    event_id: eventId,
                    review_status: status,
                    reviewed_by: '<?php echo $associatenumber ?>',
                    'form-type': 'post_review'
                },
                function(response) {
                    if (response.success) {
                        alert(response.message); // Display success message
                        location.reload(); // Refresh the page
                    } else {
                        alert(response.message); // Display error message
                    }
                },
                'json' // Specify response format
            ).fail(function() {
                alert('An error occurred. Please try again.'); // Handle failure
            });
        });
    </script>
    <script>
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        // Disable invalid dates based on start date selection
        startDateInput.addEventListener('change', () => {
            const startDateValue = startDateInput.value;
            if (startDateValue) {
                endDateInput.min = startDateValue; // Set minimum end date
            }
        });

        // Disable invalid dates based on end date selection
        endDateInput.addEventListener('change', () => {
            const endDateValue = endDateInput.value;
            if (endDateValue) {
                startDateInput.max = endDateValue; // Set maximum start date
            }
        });

        // Initialize constraints if values are pre-filled
        window.addEventListener('DOMContentLoaded', () => {
            if (startDateInput.value) {
                endDateInput.min = startDateInput.value;
            }
            if (endDateInput.value) {
                startDateInput.max = endDateInput.value;
            }
        });
    </script>
</body>

</html>