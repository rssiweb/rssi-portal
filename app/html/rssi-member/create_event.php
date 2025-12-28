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

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name = pg_escape_string($con, $_POST['event_name']);
    $event_date = pg_escape_string($con, $_POST['event_date']);
    $event_type = pg_escape_string($con, $_POST['event_type']);
    $is_full_day = isset($_POST['is_full_day']) ? 'true' : 'false';

    // Handle time fields - convert 12-hour to 24-hour format
    $event_start_time = null;
    $event_end_time = null;
    $reporting_time = null;

    // ALWAYS check for time fields, regardless of is_full_day
    if (!empty($_POST['event_start_time'])) {
        $time_str = trim($_POST['event_start_time']);
        // Convert 12-hour format (e.g., "11:00 AM") to 24-hour format
        $event_start_time = date("H:i:00", strtotime($time_str));
        $event_start_time = pg_escape_string($con, $event_start_time);
    }

    if (!empty($_POST['event_end_time'])) {
        $time_str = trim($_POST['event_end_time']);
        $event_end_time = date("H:i:00", strtotime($time_str));
        $event_end_time = pg_escape_string($con, $event_end_time);
    }

    if (!empty($_POST['reporting_time'])) {
        $time_str = trim($_POST['reporting_time']);
        $reporting_time = date("H:i:00", strtotime($time_str));
        $reporting_time = pg_escape_string($con, $reporting_time);
    }

    $location = pg_escape_string($con, $_POST['location']);
    $description = pg_escape_string($con, $_POST['description']);
    $created_by = $associatenumber;

    // Check if event already exists on this date
    $check_sql = "SELECT COUNT(*) as count FROM internal_events WHERE event_date = $1 AND event_name = $2";
    $check_result = pg_query_params($con, $check_sql, [$event_date, $event_name]);
    $check_data = pg_fetch_assoc($check_result);

    if ($check_data['count'] > 0) {
        $message = 'An event with the same name already exists on this date!';
        $message_type = 'danger';
    } else {
        // Insert the event
        $insert_sql = "INSERT INTO internal_events (
            event_name, event_date, event_type, is_full_day, 
            event_start_time, event_end_time, reporting_time, 
            location, description, created_by, updated_by
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)";

        $params = [
            $event_name,
            $event_date,
            $event_type,
            $is_full_day,
            $event_start_time,
            $event_end_time,
            $reporting_time,
            $location,
            $description,
            $created_by,
            $created_by
        ];

        $result = pg_query_params($con, $insert_sql, $params);

        if ($result) {
            $message = 'Event created successfully!';
            $message_type = 'success';
            $_POST = []; // Clear form
        } else {
            $message = 'Error creating event: ' . pg_last_error($con);
            $message_type = 'danger';
        }
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
    <title>Create Event</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Flatpickr for date/time picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>

    <style>
        .required-field::after {
            content: " *";
            color: #dc3545;
        }

        .time-fields-container {
            display: none;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .btn-submit {
            background: #667eea;
            border: none;
            padding: 10px 30px;
            font-weight: 600;
        }

        .btn-submit:hover {
            background: #5a67d8;
        }

        .time-input-group .input-group-text {
            height: 38px;
        }

        .time-input-group .form-control {
            height: 38px;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Create Event</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item active">Create Event</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Event Details</h5>

                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                                    <?php echo $message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="row g-3">
                                    <!-- Event Name -->
                                    <div class="col-md-6">
                                        <label for="event_name" class="form-label required-field">Event Name</label>
                                        <input type="text" class="form-control" id="event_name" name="event_name"
                                            value=""
                                            required maxlength="255">
                                        <div class="invalid-feedback">Please enter event name.</div>
                                    </div>

                                    <!-- Event Date -->
                                    <div class="col-md-6">
                                        <label for="event_date" class="form-label required-field">Event Date</label>
                                        <input type="text" class="form-control flatpickr-date" id="event_date" name="event_date" value="" required>
                                        <div class="invalid-feedback">Please select event date.</div>
                                    </div>

                                    <!-- Event Type -->
                                    <div class="col-md-6">
                                        <label for="event_type" class="form-label required-field">Event Type</label>
                                        <select class="form-select" id="event_type" name="event_type" required>
                                            <option value="">Select Type</option>
                                            <option value="sports">Sports</option>
                                            <option value="meeting">Meeting</option>
                                            <option value="celebration">Celebration</option>
                                            <option value="cultural">Cultural</option>
                                            <option value="festival">Festival</option>
                                            <option value="exhibition">Exhibition</option>
                                            <option value="national">National</option>
                                            <option value="training">Training</option>
                                            <option value="workshop">Workshop</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <div class="invalid-feedback">Please select event type.</div>
                                    </div>

                                    <!-- Location -->
                                    <div class="col-md-6">
                                        <label for="location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="location" name="location"
                                            value=""
                                            maxlength="255" placeholder="e.g., Main Auditorium, Sports Ground" required>
                                    </div>

                                    <!-- Full Day Event -->
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_full_day" name="is_full_day"
                                                value="1" <?php echo isset($_POST['is_full_day']) ? 'checked' : 'checked'; ?>>
                                            <label class="form-check-label" for="is_full_day">
                                                This is a full day event
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Time Fields Section -->
                                    <div id="timeFieldsContainer" class="time-fields-container mt-3">
                                        <div class="row g-3">
                                            <!-- Start Time -->
                                            <div class="col-md-4">
                                                <label for="event_start_time" class="form-label">Start Time</label>
                                                <div class="input-group time-input-group">
                                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                                    <input type="text" class="form-control flatpickr-time" id="event_start_time" name="event_start_time"
                                                        value=""
                                                        placeholder="09:00 AM">
                                                </div>
                                                <small class="form-text text-muted">When the event begins</small>
                                            </div>

                                            <!-- End Time -->
                                            <div class="col-md-4">
                                                <label for="event_end_time" class="form-label">End Time</label>
                                                <div class="input-group time-input-group">
                                                    <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                                    <input type="text" class="form-control flatpickr-time" id="event_end_time" name="event_end_time"
                                                        value=""
                                                        placeholder="05:00 PM">
                                                </div>
                                                <small class="form-text text-muted">When the event ends</small>
                                            </div>

                                            <!-- Reporting Time -->
                                            <div class="col-md-4">
                                                <label for="reporting_time" class="form-label">Reporting Time</label>
                                                <div class="input-group time-input-group">
                                                    <span class="input-group-text"><i class="bi bi-person-check"></i></span>
                                                    <input type="text" class="form-control flatpickr-time" id="reporting_time" name="reporting_time"
                                                        value=""
                                                        placeholder="08:30 AM">
                                                </div>
                                                <small class="form-text text-muted">When to arrive</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Description -->
                                    <div class="col-12">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description"
                                            rows="4" maxlength="1000"
                                            placeholder="Enter event details, agenda, instructions..." required></textarea>
                                        <small class="form-text">Maximum 1000 characters</small>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="col-12 text-center mt-4">
                                        <button type="submit" class="btn btn-primary btn-submit">
                                            <i class="bi bi-calendar-plus"></i> Create Event
                                        </button>
                                        <a href="home.php" class="btn btn-outline-secondary ms-2">
                                            <i class="bi bi-calendar-week"></i> View Calendar
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Recent Events Section -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title">Recent Events</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Event Name</th>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Location</th>
                                            <th>Created By</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $recent_events_sql = "SELECT e.*, u.fullname 
                                                             FROM internal_events e 
                                                             LEFT JOIN rssimyaccount_members u ON e.created_by = u.associatenumber 
                                                             ORDER BY e.event_date DESC, e.created_at DESC 
                                                             LIMIT 10";
                                        $recent_events_result = pg_query($con, $recent_events_sql);

                                        if ($recent_events_result && pg_num_rows($recent_events_result) > 0) {
                                            while ($event = pg_fetch_assoc($recent_events_result)) {
                                                $event_type_badge = '';
                                                switch ($event['event_type']) {
                                                    case 'sports':
                                                        $event_type_badge = '<span class="badge bg-success">Sports</span>';
                                                        break;
                                                    case 'meeting':
                                                        $event_type_badge = '<span class="badge bg-primary">Meeting</span>';
                                                        break;
                                                    case 'celebration':
                                                        $event_type_badge = '<span class="badge bg-warning">Celebration</span>';
                                                        break;
                                                    default:
                                                        $event_type_badge = '<span class="badge bg-secondary">' . ucfirst($event['event_type']) . '</span>';
                                                }

                                                $time_info = '';
                                                if ($event['is_full_day'] == 't') {
                                                    $time_info = '<small class="text-muted">Full Day</small>';
                                                } else if ($event['event_start_time']) {
                                                    $start_time = date('h:i A', strtotime($event['event_start_time']));
                                                    $end_time = $event['event_end_time'] ? date('h:i A', strtotime($event['event_end_time'])) : '';
                                                    $time_info = '<small class="text-muted">' . $start_time . ($end_time ? ' - ' . $end_time : '') . '</small>';
                                                }
                                        ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>
                                                        <?php if ($event['description']): ?>
                                                            <br><small class="text-muted"><?php echo substr(htmlspecialchars($event['description']), 0, 50) . '...'; ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo date('d M Y', strtotime($event['event_date'])); ?>
                                                        <br><?php echo $time_info; ?>
                                                    </td>
                                                    <td><?php echo $event_type_badge; ?></td>
                                                    <td><?php echo htmlspecialchars($event['location'] ?: 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($event['fullname'] ?: 'User ' . $event['created_by']); ?></td>
                                                    <td>
                                                        <a href="edit_event.php?id=<?php echo $event['id']; ?>"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                        <?php
                                            }
                                        } else {
                                            echo '<tr><td colspan="6" class="text-center">No events found. Create your first event!</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script src="../assets_new/js/text-refiner.js?v=1.2.0"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Date picker
            flatpickr(".flatpickr-date", {
                dateFormat: "Y-m-d",
                minDate: "today",
                allowInput: true
            });

            // Time picker - 12-hour format
            flatpickr(".flatpickr-time", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "h:i K", // 12-hour with AM/PM
                time_24hr: false, // Important: false for 12-hour
                minuteIncrement: 5
            });

            // Toggle time fields
            const fullDayCheckbox = document.getElementById('is_full_day');
            const timeFieldsContainer = document.getElementById('timeFieldsContainer');

            function toggleTimeFields() {
                if (fullDayCheckbox.checked) {
                    timeFieldsContainer.style.display = 'none';
                    // DON'T clear time values - just hide them
                    // This way they still get submitted
                } else {
                    timeFieldsContainer.style.display = 'block';
                }
            }

            fullDayCheckbox.addEventListener('change', toggleTimeFields);
            toggleTimeFields(); // Initial call

            // Form validation
            // (function() {
            //     'use strict'
            //     var forms = document.querySelectorAll('.needs-validation')
            //     Array.prototype.slice.call(forms)
            //         .forEach(function(form) {
            //             form.addEventListener('submit', function(event) {
            //                 if (!form.checkValidity()) {
            //                     event.preventDefault()
            //                     event.stopPropagation()
            //                 }
            //                 form.classList.add('was-validated')
            //             }, false)
            //         })
            // })();
        });
    </script>
</body>

</html>