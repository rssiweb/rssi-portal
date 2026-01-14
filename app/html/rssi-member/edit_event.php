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

// Function to check if user is admin
function isAdmin()
{
    global $role;
    return $role === 'Admin';
}

$event_id = $_GET['id'] ?? 0;

// Fetch event details
$event = null;
if ($event_id) {
    $sql = "SELECT e.*, u.fullname as creator_name 
            FROM internal_events e 
            LEFT JOIN rssimyaccount_members u ON e.created_by = u.associatenumber 
            WHERE e.id = $1";
    $result = pg_query_params($con, $sql, [$event_id]);
    $event = pg_fetch_assoc($result);
}

// Redirect if event not found
if (!$event) {
    $_SESSION['message'] = 'Event not found!';
    $_SESSION['message_type'] = 'danger';
    header("Location: create_event.php");
    exit;
}

// Check permission (only creator or admin can edit)
$canEdit = false;
if ($event['created_by'] == $associatenumber || isAdmin()) {
    $canEdit = true;
}

if (!$canEdit) {
    $_SESSION['message'] = 'You don\'t have permission to edit this event!';
    $_SESSION['message_type'] = 'danger';
    header("Location: create_event.php");
    exit;
}

// Handle form submission for update
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
    $updated_by = $associatenumber;

    // Check if event already exists on this date (excluding current event)
    // FIXED: Removed single quotes around placeholders
    $check_sql = "SELECT COUNT(*) as count FROM internal_events 
                  WHERE event_date = $1 AND event_name = $2 AND id != $3";
    $check_result = pg_query_params($con, $check_sql, [$event_date, $event_name, $event_id]);

    if (!$check_result) {
        $message = 'Database error: ' . pg_last_error($con);
        $message_type = 'danger';
    } else {
        $check_data = pg_fetch_assoc($check_result);

        if ($check_data['count'] > 0) {
            $message = 'Another event with the same name already exists on this date!';
            $message_type = 'danger';
        } else {
            // Update the event
            $update_sql = "UPDATE internal_events SET
                event_name = $1, 
                event_date = $2, 
                event_type = $3, 
                is_full_day = $4,
                event_start_time = $5, 
                event_end_time = $6, 
                reporting_time = $7,
                location = $8, 
                description = $9, 
                updated_by = $10, 
                updated_at = CURRENT_TIMESTAMP
                WHERE id = $11";

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
                $updated_by,
                $event_id
            ];

            $result = pg_query_params($con, $update_sql, $params);

            if ($result) {
                $_SESSION['message'] = 'Event updated successfully!';
                $_SESSION['message_type'] = 'success';
                header("Location: create_event.php");
                exit;
            } else {
                $message = 'Error updating event: ' . pg_last_error($con);
                $message_type = 'danger';
            }
        }
    }
}

// Format time for display (convert from 24h to 12h)
if ($event['event_start_time']) {
    // Convert 24h to 12h format
    $event['event_start_time_display'] = date("h:i A", strtotime($event['event_start_time']));
} else {
    $event['event_start_time_display'] = '';
}

if ($event['event_end_time']) {
    $event['event_end_time_display'] = date("h:i A", strtotime($event['event_end_time']));
} else {
    $event['event_end_time_display'] = '';
}

if ($event['reporting_time']) {
    $event['reporting_time_display'] = date("h:i A", strtotime($event['reporting_time']));
} else {
    $event['reporting_time_display'] = '';
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
    <title>Edit Event</title>

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
            display: <?php echo $event['is_full_day'] == 't' ? 'none' : 'block'; ?>;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .btn-update {
            background: #4facfe;
            border: none;
            padding: 10px 30px;
            font-weight: 600;
        }

        .btn-update:hover {
            background: #3d94e8;
        }

        .event-info-card {
            background: #f5f7fa;
            border-left: 4px solid #4facfe;
        }

        .btn-delete {
            background: #ff6b6b;
            border: none;
            color: white;
        }

        .btn-delete:hover {
            background: #ff5252;
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
            <h1>Edit Event</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="create_event.php">Create Event</a></li>
                    <li class="breadcrumb-item active">Edit Event</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <!-- Event Information Card -->
                    <div class="card event-info-card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="card-title">Event Information</h5>
                                    <p class="mb-1"><strong>Event ID:</strong> #<?php echo $event['id']; ?></p>
                                    <p class="mb-1"><strong>Created By:</strong> <?php echo htmlspecialchars($event['creator_name'] ?: 'User ' . $event['created_by']); ?></p>
                                    <p class="mb-1"><strong>Created On:</strong> <?php echo date('d M Y, h:i A', strtotime($event['created_at'])); ?></p>
                                    <?php if ($event['updated_at'] && $event['updated_at'] != $event['created_at']): ?>
                                        <p class="mb-0"><strong>Last Updated:</strong> <?php echo date('d M Y, h:i A', strtotime($event['updated_at'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if ($role == 'Admin'): ?>
                                    <div class="col-md-4 text-end">
                                        <button class="btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="bi bi-trash"></i> Delete Event
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Edit Event Details</h5>

                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                                    <?php echo $message; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="row g-3">
                                    <!-- Event Name -->
                                    <div class="col-md-6">
                                        <label for="event_name" class="form-label required-field">Event Name</label>
                                        <input type="text" class="form-control" id="event_name" name="event_name"
                                            value="<?php echo htmlspecialchars($_POST['event_name'] ?? $event['event_name']); ?>"
                                            required maxlength="255">
                                        <div class="invalid-feedback">Please enter event name.</div>
                                    </div>

                                    <!-- Event Date -->
                                    <div class="col-md-6">
                                        <label for="event_date" class="form-label required-field">Event Date</label>
                                        <input type="text" class="form-control flatpickr-date" id="event_date" name="event_date"
                                            value="<?php echo htmlspecialchars($_POST['event_date'] ?? $event['event_date']); ?>"
                                            required>
                                        <div class="invalid-feedback">Please select event date.</div>
                                    </div>

                                    <!-- Event Type -->
                                    <div class="col-md-6">
                                        <label for="event_type" class="form-label required-field">Event Type</label>
                                        <select class="form-select" id="event_type" name="event_type" required>
                                            <option value="">Select Type</option>
                                            <option value="sports" <?php echo ($_POST['event_type'] ?? $event['event_type']) == 'sports' ? 'selected' : ''; ?>>Sports</option>
                                            <option value="meeting" <?php echo ($_POST['event_type'] ?? $event['event_type']) == 'meeting' ? 'selected' : ''; ?>>Meeting</option>
                                            <option value="celebration" <?php echo ($_POST['event_type'] ?? $event['event_type']) == 'celebration' ? 'selected' : ''; ?>>Celebration</option>
                                            <option value="cultural" <?php echo ($_POST['event_type'] ?? $event['event_type']) == 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                                            <option value="festival" <?php echo ($_POST['event_type'] ?? $event['event_type']) == 'festival' ? 'selected' : ''; ?>>Festival</option>
                                            <option value="exhibition" <?php echo ($_POST['event_type'] ?? $event['event_type']) == 'exhibition' ? 'selected' : ''; ?>>Exhibition</option>
                                            <option value="national" <?php echo ($_POST['event_type'] ?? $event['event_type']) == 'national' ? 'selected' : ''; ?>>National</option>
                                            <option value="training" <?php echo ($_POST['event_type'] ?? $event['event_type']) == 'training' ? 'selected' : ''; ?>>Training</option>
                                            <option value="workshop" <?php echo ($_POST['event_type'] ?? $event['event_type']) == 'workshop' ? 'selected' : ''; ?>>Workshop</option>
                                            <option value="other" <?php echo ($_POST['event_type'] ?? $event['event_type']) == 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <div class="invalid-feedback">Please select event type.</div>
                                    </div>

                                    <!-- Location -->
                                    <div class="col-md-6">
                                        <label for="location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="location" name="location"
                                            value="<?php echo htmlspecialchars($_POST['location'] ?? $event['location'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                            maxlength="255" placeholder="e.g., Main Auditorium, Sports Ground">
                                    </div>

                                    <!-- Full Day Event -->
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_full_day" name="is_full_day"
                                                value="1" <?php echo (($_POST['is_full_day'] ?? $event['is_full_day']) == 't') ? 'checked' : ''; ?>>
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
                                                        value="<?php echo htmlspecialchars($_POST['event_start_time'] ?? $event['event_start_time_display']); ?>"
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
                                                        value="<?php echo htmlspecialchars($_POST['event_end_time'] ?? $event['event_end_time_display']); ?>"
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
                                                        value="<?php echo htmlspecialchars($_POST['reporting_time'] ?? $event['reporting_time_display']); ?>"
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
                                            placeholder="Enter event details, agenda, instructions...">
                                            <?php
                                            echo htmlspecialchars($_POST['description'] ?? $event['description'] ?? '', ENT_QUOTES, 'UTF-8');
                                            ?>
                                        </textarea>
                                        <small class="form-text">Maximum 1000 characters</small>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="col-12 text-center mt-4">
                                        <button type="submit" class="btn btn-primary btn-update">
                                            <i class="bi bi-check-circle"></i> Update Event
                                        </button>
                                        <a href="create_event.php" class="btn btn-outline-secondary ms-2">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle text-warning display-4 mb-3"></i>
                        <h5>Are you sure you want to delete this event?</h5>
                        <p class="text-muted">
                            <strong>"<?php echo htmlspecialchars($event['event_name']); ?>"</strong><br>
                            Scheduled for <?php echo date('d M Y', strtotime($event['event_date'])); ?>
                        </p>
                        <p class="text-danger"><small>This action cannot be undone.</small></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="delete_event.php" style="display: inline;">
                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                        <button type="submit" class="btn btn-danger">Delete Event</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
                minDate: "today"
            });

            // Time picker - 12-hour format
            flatpickr(".flatpickr-time", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "h:i K", // 12-hour with AM/PM
                time_24hr: false, // Important: false for 12-hour
                minuteIncrement: 15
            });

            // Toggle time fields
            const fullDayCheckbox = document.getElementById('is_full_day');
            const timeFieldsContainer = document.getElementById('timeFieldsContainer');

            function toggleTimeFields() {
                if (fullDayCheckbox.checked) {
                    timeFieldsContainer.style.display = 'none';
                    // DO NOT clear time values - let them be submitted
                } else {
                    timeFieldsContainer.style.display = 'block';
                }
            }

            fullDayCheckbox.addEventListener('change', toggleTimeFields);

            // Form validation
            (function() {
                'use strict'
                var forms = document.querySelectorAll('.needs-validation')
                Array.prototype.slice.call(forms)
                    .forEach(function(form) {
                        form.addEventListener('submit', function(event) {
                            if (!form.checkValidity()) {
                                event.preventDefault()
                                event.stopPropagation()
                            }
                            form.classList.add('was-validated')
                        }, false)
                    })
            })();

            // Confirm before leaving page if form has changes
            let formChanged = false;
            const formInputs = document.querySelectorAll('form input, form select, form textarea');
            const originalFormData = new FormData(document.querySelector('form'));

            // Function to check if form has changes
            function checkFormChanges() {
                const currentFormData = new FormData(document.querySelector('form'));

                // Compare original and current form data
                for (let [key, originalValue] of originalFormData.entries()) {
                    const currentValue = currentFormData.get(key) || '';
                    if (originalValue.toString().trim() !== currentValue.toString().trim()) {
                        return true;
                    }
                }
                return false;
            }

            // Track form changes
            formInputs.forEach(input => {
                input.addEventListener('input', () => {
                    formChanged = checkFormChanges();
                });
                input.addEventListener('change', () => {
                    formChanged = checkFormChanges();
                });
            });

            // Beforeunload event - only warn if there are unsaved changes AND form is not being submitted
            window.addEventListener('beforeunload', function(e) {
                if (formChanged) {
                    e.preventDefault();
                    e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                    return 'You have unsaved changes. Are you sure you want to leave?';
                }
            });

            // Reset formChanged when form is submitted
            document.querySelector('form').addEventListener('submit', function() {
                formChanged = false;
                // Allow the form to submit normally
            });
        });
    </script>
</body>

</html>