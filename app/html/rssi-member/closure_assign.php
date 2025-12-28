<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/drive.php");
include("../../util/email.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['selectedDate'];
    $assigned_to = $_POST['associateId'];
    $timestamp = date('Y-m-d H:i:s');
    $id = uniqid(); // Generate unique ID in PHP
    $assigned_by = $associatenumber; // Use the logged-in user's identifier for submission tracking

    // Prepare and execute query using $con
    $query = "INSERT INTO closure_assign (id, date, assigned_to, assigned_by, timestamp) VALUES ($1, $2, $3, $4, $5)";
    $result = pg_query_params($con, $query, [$id, $date, $assigned_to, $assigned_by, $timestamp]);

    if ($result) {

        // Fetch email of the assigned associate
        $query_email = "SELECT email, fullname FROM rssimyaccount_members WHERE associatenumber = $1";
        $res_email = pg_query_params($con, $query_email, [$assigned_to]);

        if ($res_email && pg_num_rows($res_email) > 0) {
            $row_email = pg_fetch_assoc($res_email);
            $email = $row_email['email'];
            $associate_name = $row_email['fullname'];

            // Send closing duty email
            if (!empty($email)) {
                sendEmail("closingduty", [
                    "name" => $associate_name,
                    "date" => date("d/m/Y", strtotime($date)),
                    "timestamp" => date("d/m/Y g:i a", strtotime($timestamp)),
                ], $email);
            }
        }

        // Return a success response
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => pg_last_error($con)]);
    }
    exit;
}

// Fetch assigned dates and associate names
$assignments = [];
$query = "
    SELECT date, assigned_to, 
           (SELECT fullname FROM rssimyaccount_members WHERE associatenumber = assigned_to) AS associate_name 
    FROM closure_assign";
$result = pg_query($con, $query);

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $assignments[] = $row;
    }
}

// Assuming $assignments are already fetched from the database as shown in your original code
$assignments = [];
$query = "
    SELECT date, assigned_to, 
           (SELECT fullname FROM rssimyaccount_members WHERE associatenumber = assigned_to) AS associate_name 
    FROM closure_assign";
$result = pg_query($con, $query);

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $assignments[] = $row;
    }
}

// Fetch active associates for dropdown
$associates = [];
$query_associates = "SELECT associatenumber, fullname FROM rssimyaccount_members WHERE filterstatus='Active'";
$result_associates = pg_query($con, $query_associates);

if ($result_associates) {
    while ($row = pg_fetch_assoc($result_associates)) {
        $associates[] = $row;
    }
}
// Pass this value to JavaScript (you can also use session or other ways to store the role)
echo "<script>var userRole = '$role';</script>";
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

    <title>Closing Duty Roster</title>

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
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.css" rel="stylesheet">
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Closing Duty Roster</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Schedule Hub</a></li>
                    <li class="breadcrumb-item active">Closing Duty Roster</li>
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

                            <!-- Year and Month Navigation -->
                            <div class="d-flex justify-content-center mb-3">
                                <select id="yearDropdown" class="form-select w-auto mx-2"></select>
                                <select id="monthDropdown" class="form-select w-auto mx-2">
                                    <option value="0">January</option>
                                    <option value="1">February</option>
                                    <option value="2">March</option>
                                    <option value="3">April</option>
                                    <option value="4">May</option>
                                    <option value="5">June</option>
                                    <option value="6">July</option>
                                    <option value="7">August</option>
                                    <option value="8">September</option>
                                    <option value="9">October</option>
                                    <option value="10">November</option>
                                    <option value="11">December</option>
                                </select>
                                <button id="goToDate" class="btn btn-primary mx-2">Go</button>
                            </div>
                            <div class="container">
                                <div class="row justify-content-center">
                                    <div class="col-12 col-md-8 col-lg-6">
                                        <div id="calendar" class="w-100"></div>
                                    </div>
                                </div>
                            </div>
                            <!-- Modal for Assigning Associate -->
                            <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="assignModalLabel">Assign Associate</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="assignForm" action="#">
                                                <div class="mb-3">
                                                    <label for="selectedDate" class="form-label">Selected Date</label>
                                                    <input type="text" id="selectedDate" name="selectedDate" class="form-control" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="associateId" class="form-label">Select Associate</label>
                                                    <select id="associateId" name="associateId" class="form-select">
                                                        <option disabled selected>Select Associate</option>
                                                        <!-- Dynamically populated -->
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-primary" id="saveAssignment">
                                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="saveSpinner"></span>
                                                    <span id="saveText">Save</span>
                                                </button>

                                            </form>
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
  <script src="../assets_new/js/text-refiner.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const yearDropdown = document.getElementById('yearDropdown');
            const monthDropdown = document.getElementById('monthDropdown');
            const goToDateButton = document.getElementById('goToDate');

            const modal = new bootstrap.Modal(document.getElementById('assignModal'));
            const selectedDateInput = document.getElementById('selectedDate');
            const associateIdSelect = document.getElementById('associateId');
            const formid = document.getElementById('assignForm'); // Get the form element

            // Check if the user is an admin
            if (userRole !== 'Admin') {
                // Disable all form elements inside the form
                Array.from(formid.elements).forEach(element => {
                    element.disabled = true;
                });
            }

            // Dynamic data for associates and assignments from PHP
            const associates = <?php echo json_encode($associates); ?>;
            const assignments = <?php echo json_encode($assignments); ?>;

            // Populate Associate Dropdown
            associates.forEach(associate => {
                const option = document.createElement('option');
                option.value = associate.associatenumber; // Set value as associatenumber
                option.textContent = `${associate.associatenumber} - ${associate.fullname}`; // Display associatenumber-fullname
                associateIdSelect.appendChild(option);
            });

            // Initialize FullCalendar
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                dateClick: function(info) {
                    openAssignModal(info.dateStr);
                },
                customButtons: {
                    today: {
                        text: 'Today',
                        click: function() {
                            calendar.today();
                            updateDropdowns(new Date());
                        }
                    }
                }
            });

            // Fetch assignments from PHP and add events to the calendar
            assignments.forEach(assignment => {
                calendar.addEvent({
                    title: assignment.associate_name,
                    date: assignment.date
                });
            });

            calendar.render();

            // Get the current date
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear();
            const currentMonth = currentDate.getMonth(); // 0-based index for month

            // Populate the year dropdown
            for (let year = currentYear - 5; year <= currentYear + 5; year++) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                if (year === currentYear) {
                    option.selected = true; // Select the current year
                }
                yearDropdown.appendChild(option);
            }

            // Set the current month in the month dropdown
            monthDropdown.value = currentMonth; // Set the value directly

            // "Go" Button Handler
            goToDateButton.addEventListener('click', () => {
                const selectedYear = yearDropdown.value;
                const selectedMonth = monthDropdown.value;
                const newDate = new Date(selectedYear, selectedMonth, 1);
                calendar.gotoDate(newDate); // Navigate calendar to the selected year and month
                updateDropdowns(newDate); // Sync dropdowns
            });

            function openAssignModal(date) {
                selectedDateInput.value = date; // Set the selected date in the modal

                // Find the assignment for the selected date
                const assignmentForDate = assignments.find(assignment => assignment.date === date);

                // Clear the dropdown and re-populate options
                associateIdSelect.innerHTML = '<option disabled selected>Select Associate</option>';

                // Check if there's no assignment for the selected date
                if (!assignmentForDate) {
                    // If no assignment, just populate the dropdown with associates
                    associates.forEach(associate => {
                        const option = document.createElement('option');
                        option.value = associate.associatenumber; // Set value as associatenumber
                        option.textContent = `${associate.associatenumber} - ${associate.fullname}`; // Display associatenumber-fullname
                        associateIdSelect.appendChild(option);
                    });
                } else {
                    // If there is an assignment, pre-select the assigned associate
                    associates.forEach(associate => {
                        const option = document.createElement('option');
                        option.value = associate.associatenumber; // Set value as associatenumber
                        option.textContent = `${associate.associatenumber} - ${associate.fullname}`; // Display associatenumber-fullname
                        if (assignmentForDate.assigned_to === associate.associatenumber) {
                            option.selected = true; // Pre-select if this associate is already assigned
                        }
                        associateIdSelect.appendChild(option);
                    });
                }

                modal.show();
            }

            function updateDropdowns(date) {
                // Update year and month dropdowns based on the given date
                yearDropdown.value = date.getFullYear();
                monthDropdown.value = date.getMonth();
            }

            // Ensure dropdowns sync on calendar navigation
            calendar.on('datesSet', function(info) {
                const currentDate = calendar.getDate();
                updateDropdowns(currentDate);
            });

            // Handle form submission via AJAX
            const saveBtn = document.getElementById('saveAssignment');
            const spinner = document.getElementById('saveSpinner');
            const saveText = document.getElementById('saveText');

            assignForm.addEventListener('submit', function(event) {
                event.preventDefault();

                // Show spinner
                spinner.classList.remove('d-none');
                saveText.textContent = 'Saving...';
                saveBtn.disabled = true;

                const formData = new FormData(assignForm);

                fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Reset button
                        spinner.classList.add('d-none');
                        saveText.textContent = 'Save';
                        saveBtn.disabled = false;

                        if (data.success) {
                            alert('Assignment saved successfully');
                            modal.hide();
                            calendar.refetchEvents();
                            location.reload();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    })
                    .catch(error => {
                        spinner.classList.add('d-none');
                        saveText.textContent = 'Save';
                        saveBtn.disabled = false;
                        console.error('Error:', error);
                        alert('Something went wrong');
                    });
            });


        });
    </script>
</body>

</html>