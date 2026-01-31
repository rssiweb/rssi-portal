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

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $associate_numbers = $_POST['associate_number'] ?? [];
    $start_date = $_POST['start_date'] ?? '';
    $schedules = $_POST['schedule'] ?? []; // Array of schedules

    // Validation
    if (empty($associate_numbers) || empty($start_date) || empty($schedules)) {
        $error_message = 'Please fill all required fields.';
    } else {
        $timestamp = date('Y-m-d H:i:s');
        $submittedBy = $associatenumber;

        // Begin transaction for data consistency
        pg_query($con, "BEGIN");

        try {
            $total_entries = 0;
            $first_id = null;

            foreach ($associate_numbers as $associate_number) {
                foreach ($schedules as $schedule) {
                    $days = $schedule['days'] ?? [];
                    $reporting_time = $schedule['reporting_time'] ?? '';
                    $exit_time = $schedule['exit_time'] ?? '';

                    // Validate schedule data
                    if (empty($days) || empty($reporting_time) || empty($exit_time)) {
                        continue;
                    }

                    foreach ($days as $day) {
                        $id = uniqid();
                        if ($first_id === null) {
                            $first_id = $id;
                        }

                        // Check if schedule already exists
                        $check_query = "SELECT id FROM associate_schedule_v2 
                                        WHERE associate_number = $1 
                                        AND start_date = $2 
                                        AND workday = $3";

                        $check_result = pg_query_params($con, $check_query, [
                            $associate_number,
                            $start_date,
                            $day
                        ]);

                        if (pg_num_rows($check_result) > 0) {
                            // Update existing
                            $update_query = "UPDATE associate_schedule_v2 
                                            SET reporting_time = $1, 
                                                exit_time = $2, 
                                                updated_at = $3,
                                                submitted_by = $4
                                            WHERE associate_number = $5 
                                            AND start_date = $6 
                                            AND workday = $7";

                            $result = pg_query_params($con, $update_query, [
                                $reporting_time,
                                $exit_time,
                                $timestamp,
                                $submittedBy,
                                $associate_number,
                                $start_date,
                                $day
                            ]);
                        } else {
                            // Insert new
                            $insert_query = "INSERT INTO associate_schedule_v2 
                                            (id, associate_number, start_date, workday, 
                                             reporting_time, exit_time, created_at, submitted_by)
                                            VALUES ($1, $2, $3, $4, $5, $6, $7, $8)";

                            $result = pg_query_params($con, $insert_query, [
                                $id,
                                $associate_number,
                                $start_date,
                                $day,
                                $reporting_time,
                                $exit_time,
                                $timestamp,
                                $submittedBy
                            ]);
                        }

                        $total_entries++;
                    }
                }
            }

            // Commit transaction
            pg_query($con, "COMMIT");

            if ($total_entries > 0) {
                $success_message = "Successfully created/updated $total_entries schedule entries.";
                if ($first_id) {
                    $success_message .= " First ID: $first_id";
                }
            } else {
                $error_message = "No schedule entries were created. Please check your inputs.";
            }
        } catch (Exception $e) {
            // Rollback on error
            pg_query($con, "ROLLBACK");
            $error_message = "An error occurred: " . $e->getMessage();
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
    <?php include 'includes/meta.php' ?>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">

    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <style>
        .schedule-entry {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            transition: all 0.3s;
        }

        .schedule-entry:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
        }

        .schedule-header {
            background-color: #e7f1ff;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            border-left: 4px solid #0d6efd;
        }

        .remove-schedule {
            transition: all 0.3s;
        }

        .day-selector {
            min-height: 100px;
        }

        .day-option {
            margin: 5px;
        }

        .form-control-time {
            max-width: 150px;
        }

        .copy-time-btn {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div>

        <section class="section dashboard">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <br>

                            <!-- Success/Error Messages -->
                            <?php if ($success_message): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <?php echo htmlspecialchars($success_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <script>
                                    if (window.history.replaceState) {
                                        window.history.replaceState(null, null, window.location.href);
                                    }
                                </script>
                            <?php endif; ?>

                            <?php if ($error_message): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <?php echo htmlspecialchars($error_message); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <!-- Instructions -->
                            <!-- <div class="alert alert-info">
                                <h5><i class="bi bi-info-circle me-2"></i>How to create schedules:</h5>
                                <ol class="mb-0">
                                    <li>Select one or more associates</li>
                                    <li>Set the start date for the schedule</li>
                                    <li>Click "Add Schedule Entry" to add shift timings</li>
                                    <li>For each entry: Select days, set start & end times</li>
                                    <li>Use "Copy Times to All" to apply same timings to all entries</li>
                                    <li>All selected associates will get the same schedule</li>
                                </ol>
                            </div> -->

                            <form action="#" id="roster-form" method="post">
                                <!-- Associate Selection -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <label for="associate_number" class="form-label">
                                            <strong>Select Associates</strong> <span class="text-danger">*</span>
                                            <small class="text-muted ms-2">(You can select multiple)</small>
                                        </label>
                                        <select class="form-control select2-multiple" id="associate_number" name="associate_number[]" multiple="multiple" required></select>
                                    </div>
                                </div>

                                <!-- Start Date -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label">
                                            <strong>Schedule Start Date</strong> <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">
                                            <strong>Selected Associates Count</strong>
                                        </label>
                                        <div class="form-control" id="selected-count">0 associates selected</div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Dynamic Schedule Builder -->
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h5 class="mb-3">
                                            <i class="bi bi-calendar3 me-2"></i>Schedule Builder
                                            <button type="button" id="add-schedule" class="btn btn-success btn-sm ms-2">
                                                <i class="bi bi-plus-circle"></i> Add Schedule Entry
                                            </button>
                                            <button type="button" id="copy-times" class="btn btn-outline-primary btn-sm ms-2">
                                                <i class="bi bi-copy"></i> Copy Times to All
                                            </button>
                                        </h5>

                                        <div id="schedule-container">
                                            <!-- Schedule entries will be added here dynamically -->
                                            <div class="text-center text-muted py-4" id="no-schedules">
                                                <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                                                <p class="mt-2">No schedule entries added yet. Click "Add Schedule Entry" to start.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="row">
                                    <div class="col-12 text-end">
                                        <button type="reset" class="btn btn-secondary">
                                            <i class="bi bi-x-circle"></i> Clear Form
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Create Schedules
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Schedule Entry Template (Hidden) -->
    <template id="schedule-template">
        <div class="schedule-entry" data-index="0">
            <div class="schedule-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-clock me-2"></i>
                    Schedule Entry <span class="entry-number">#1</span>
                </h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-schedule" disabled>
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>

            <div class="row">
                <!-- Days Selection -->
                <div class="col-md-6 mb-3">
                    <label class="form-label">Select Days <span class="text-danger">*</span></label>
                    <div class="day-selector">
                        <select class="form-control select2-multiple days-select" name="schedule[0][days][]" multiple="multiple" required>
                            <option value="Mon">Monday</option>
                            <option value="Tue">Tuesday</option>
                            <option value="Wed">Wednesday</option>
                            <option value="Thu">Thursday</option>
                            <option value="Fri">Friday</option>
                            <option value="Sat">Saturday</option>
                            <option value="Sun">Sunday</option>
                        </select>
                    </div>
                </div>

                <!-- Time Selection -->
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control form-control-time reporting-time" name="schedule[0][reporting_time]" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control form-control-time exit-time" name="schedule[0][exit_time]" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-text">
                                Selected days: <span class="selected-days-count">0</span> days
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Bootstrap JS and Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function() {
            let scheduleCount = 0;

            // Initialize Select2 for associate numbers
            $('#associate_number').select2({
                ajax: {
                    url: 'fetch_associates.php?isShiftPlanner=true',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results || []
                        };
                    },
                    cache: true
                },
                theme: 'bootstrap-5',
                placeholder: 'Search and select associates...',
                allowClear: true,
                multiple: true,
                minimumInputLength: 2,
                width: '100%'
            }).on('change', updateSelectedCount);

            // Set minimum date to today
            // var today = new Date().toISOString().split('T')[0];
            // $('#start_date').attr('min', today);

            // Add schedule entry
            $('#add-schedule').click(function() {
                addScheduleEntry();
            });

            // Remove schedule entry
            $(document).on('click', '.remove-schedule', function() {
                $(this).closest('.schedule-entry').remove();
                updateEntryNumbers();
                updateRemoveButtonStates(); // ADD THIS LINE
                // Reinitialize all Select2 instances
                $('.days-select').select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select days...',
                    allowClear: true,
                    multiple: true,
                    width: '100%'
                });
            });

            // Copy times from first entry to all
            $('#copy-times').click(function() {
                const firstEntry = $('.schedule-entry').first();
                if (firstEntry.length) {
                    const startTime = firstEntry.find('.reporting-time').val();
                    const endTime = firstEntry.find('.exit-time').val();

                    if (startTime && endTime) {
                        $('.reporting-time').val(startTime);
                        $('.exit-time').val(endTime);

                        // Show success feedback
                        const feedback = $('<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">' +
                            '<i class="bi bi-check-circle me-2"></i>Times copied to all entries successfully.' +
                            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                            '</div>');

                        $('#copy-times').after(feedback);
                        setTimeout(() => feedback.alert('close'), 3000);
                    }
                }
            });

            // Update selected days count
            $(document).on('change', '.days-select', function() {
                const count = $(this).val() ? $(this).val().length : 0;
                $(this).closest('.schedule-entry').find('.selected-days-count').text(count);
            });

            // Function to update selected associates count
            function updateSelectedCount() {
                const associateCount = $('#associate_number').val() ? $('#associate_number').val().length : 0;
                $('#selected-count').text(associateCount + ' associate' + (associateCount !== 1 ? 's' : '') + ' selected');
            }

            // Function to update remove button states
            function updateRemoveButtonStates() {
                const entryCount = $('.schedule-entry').length;
                $('.remove-schedule').prop('disabled', entryCount === 1);
            }

            // Function to add a new schedule entry
            function addScheduleEntry() {
                scheduleCount++;

                const template = $('#schedule-template').html();
                const newEntry = $(template.replace(/0/g, scheduleCount));

                newEntry.attr('data-index', scheduleCount);
                newEntry.find('.entry-number').text('#' + scheduleCount);

                // Update form field names
                newEntry.find('[name^="schedule[0]"]').each(function() {
                    const oldName = $(this).attr('name');
                    const newName = oldName.replace('[0]', '[' + scheduleCount + ']');
                    $(this).attr('name', newName);
                });

                // Hide "no schedules" message
                $('#no-schedules').hide();

                // Add to container
                $('#schedule-container').append(newEntry);

                // Update remove button states
                updateRemoveButtonStates(); // ADD THIS LINE

                // Initialize Select2 for days - MUST be done AFTER appending to DOM
                const daysSelect = newEntry.find('.days-select');
                daysSelect.select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select days...',
                    allowClear: true,
                    multiple: true,
                    width: '100%',
                    dropdownParent: newEntry // Important for dropdown positioning
                });

                // Update entry numbers
                updateEntryNumbers();
            }

            // Function to update entry numbers
            function updateEntryNumbers() {
                $('.schedule-entry').each(function(index) {
                    $(this).find('.entry-number').text('#' + (index + 1));
                    // Update the data-index attribute
                    $(this).attr('data-index', index + 1);
                });

                // Show "no schedules" message if empty
                if ($('.schedule-entry').length === 0) {
                    $('#no-schedules').show();
                }
            }

            // Handle form reset - actually clear all entries and start fresh
            $('button[type="reset"]').click(function(e) {
                e.preventDefault(); // Prevent default reset behavior

                // Remove all schedule entries
                $('#schedule-container').empty();

                // Show "no schedules" message
                $('#no-schedules').show();

                // Reset schedule count
                scheduleCount = 0;

                // Add one fresh entry
                setTimeout(() => {
                    addScheduleEntry();
                    updateRemoveButtonStates();
                }, 50);

                // Also clear other form fields (associates, date)
                $('#associate_number').val(null).trigger('change');
                $('#start_date').val('');
                $('#selected-count').text('0 associates selected');
            });

            // Form validation
            $('#roster-form').submit(function(e) {
                e.preventDefault();

                // Validate associates
                const associates = $('#associate_number').val();
                if (!associates || associates.length === 0) {
                    alert('Please select at least one associate.');
                    $('#associate_number').select2('open');
                    return false;
                }

                // Validate start date
                const startDate = $('#start_date').val();
                if (!startDate) {
                    alert('Please select a start date.');
                    $('#start_date').focus();
                    return false;
                }

                // Validate schedule entries
                const scheduleEntries = $('.schedule-entry').length;
                if (scheduleEntries === 0) {
                    alert('Please add at least one schedule entry.');
                    $('#add-schedule').focus();
                    return false;
                }

                // Validate each schedule entry
                let hasErrors = false;
                $('.schedule-entry').each(function(index) {
                    const days = $(this).find('.days-select').val();
                    const startTime = $(this).find('.reporting-time').val();
                    const endTime = $(this).find('.exit-time').val();

                    if (!days || days.length === 0) {
                        alert(`Schedule Entry #${index + 1}: Please select at least one day.`);
                        $(this).find('.days-select').select2('open');
                        hasErrors = true;
                        return false;
                    }

                    if (!startTime || !endTime) {
                        alert(`Schedule Entry #${index + 1}: Please enter both start and end times.`);
                        $(this).find('.reporting-time').focus();
                        hasErrors = true;
                        return false;
                    }

                    if (startTime >= endTime) {
                        alert(`Schedule Entry #${index + 1}: Start time must be earlier than end time.`);
                        $(this).find('.reporting-time').focus();
                        hasErrors = true;
                        return false;
                    }
                });

                if (hasErrors) return false;

                // Submit form
                this.submit();
            });

            // Add first schedule entry on page load (optional)
            setTimeout(() => {
                if ($('.schedule-entry').length === 0) {
                    addScheduleEntry();
                }

                // Initialize remove button states
                updateRemoveButtonStates(); // ADD THIS LINE

                // Initialize any existing Select2 instances (in case of page refresh)
                $('.days-select').each(function() {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({
                            theme: 'bootstrap-5',
                            placeholder: 'Select days...',
                            allowClear: true,
                            multiple: true,
                            width: '100%',
                            dropdownParent: $(this).closest('.schedule-entry')
                        });
                    }
                });
            }, 100);
        });
    </script>

</body>

</html>