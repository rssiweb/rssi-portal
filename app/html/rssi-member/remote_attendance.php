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

// Get current associate number from session
// $associatenumber = $_SESSION['associatenumber'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remote Attendance System</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
    <style>
        .card-header {
            font-weight: 600;
            background: linear-gradient(135deg, #3a7bd5, #00d2ff);
        }

        .search-container {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .search-input {
            flex: 1;
        }

        .add-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 12px;
            min-width: 100px;
        }

        .btn-text {
            white-space: nowrap;
        }

        .selected-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
            margin-bottom: 8px;
        }

        .remove-item {
            color: #dc3545;
            cursor: pointer;
            margin-left: 10px;
        }

        .time-input {
            /* max-width: 200px; */
            width: max-content;
        }

        .form-section {
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .no-selection {
            color: #6c757d;
            font-style: italic;
            padding: 15px;
            text-align: center;
        }

        .match-item {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .match-item:hover {
            background-color: #f8f9fa;
        }

        .spinner-text {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1><?php echo getPageTitle(); ?></h1>
            <?php echo generateDynamicBreadcrumb(); ?>
        </div><!-- End Page Title -->

        <section class="section dashboard">
            <div class="row">

                <!-- Reports -->
                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <br>
                            <div class="container-fluid py-4">
                                <div class="card shadow-lg">
                                    <div class="card-header text-white py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h4 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Remote Attendance</h4>
                                            <span class="badge bg-light text-primary fs-6"><?php echo date('F j, Y'); ?></span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <form id="attendanceForm">
                                            <!-- Search Section -->
                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="bi bi-search me-2"></i>Find Person</h5>
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <label for="personSearch" class="form-label fw-semibold">Search by Name or ID</label>
                                                        <div class="search-container">
                                                            <div class="search-input">
                                                                <input type="text" class="form-control" id="personSearch" placeholder="Enter name or ID...">
                                                            </div>
                                                            <button type="button" class="btn btn-primary add-btn" id="addPerson">
                                                                <span><i class="bi bi-plus-lg me-1"></i> Add</span>
                                                            </button>
                                                        </div>
                                                        <div class="form-text">Enter student ID, associate number, or name to search</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Selected Persons -->
                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="bi bi-list-check me-2"></i>Attendance List (<span id="selectedCount">0</span>)</h5>
                                                <div id="selectedPersonsContainer">
                                                    <div class="no-selection" id="noSelectionMessage">No persons added yet</div>
                                                    <div id="selectedPersonsList"></div>
                                                </div>
                                            </div>

                                            <!-- Attendance Details -->
                                            <div class="form-section">
                                                <h5 class="mb-3"><i class="bi bi-clock me-2"></i>Attendance Details</h5>
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label for="punchInTime" class="form-label fw-semibold">Punch In Time</label>
                                                        <input type="datetime-local" class="form-control time-input" id="punchInTime" required>
                                                    </div>
                                                    <div class="col-md-8">
                                                        <label for="remarks" class="form-label fw-semibold">Remarks</label>
                                                        <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Reason for Remote Attendance..." required></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Submit Button -->
                                            <div class="text-end mt-4">
                                                <button type="submit" class="btn btn-success px-4 py-2" id="submitAttendance">
                                                    <i class="bi bi-check-circle-fill me-2"></i> Submit Attendance
                                                </button>
                                            </div>
                                        </form>
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
    <!-- Selection Modal for Multiple Matches -->
    <div class="modal fade" id="selectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Multiple Matches Found</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please select the correct person from the list below:</p>
                    <div class="list-group" id="matchesList"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden field to store selected persons -->
    <input type="hidden" id="selectedPersons" name="selectedPersons" value="">

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  

    <script>
        function getLocation() {
            return new Promise((resolve, reject) => {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        position => {
                            latitude = position.coords.latitude; // Store latitude in the variable
                            longitude = position.coords.longitude; // Store longitude in the variable
                            resolve(); // Resolve the promise when location is retrieved
                        },
                        error => {
                            alert("Error getting location: " + error.message);
                            reject(error); // Reject the promise on error
                        }
                    );
                } else {
                    alert("Geolocation is not supported by this browser.");
                    reject(new Error("Geolocation not supported"));
                }
            });
        }
    </script>

    <script>
        $(document).ready(function() {
            // Array to store selected persons
            let selectedPersons = [];

            // Set default punch in time to current time
            const now = new Date();
            const timezoneOffset = now.getTimezoneOffset() * 60000;
            const localISOTime = (new Date(now - timezoneOffset)).toISOString().slice(0, 16);
            $('#punchInTime').val(localISOTime);

            // Add person to list
            $('#addPerson').click(function() {
                const searchTerm = $('#personSearch').val().trim();

                if (!searchTerm) {
                    alert('Please enter a name or ID to search');
                    return;
                }

                const addBtn = $(this);
                const btnText = addBtn.find('span');
                // Save original text
                const originalText = btnText.text();

                // Update to loading state
                addBtn.prop('disabled', true);
                btnText.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Searching');

                $.ajax({
                    url: 'search_person.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        search_term: searchTerm
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.matches.length === 1) {
                                // Single match - add directly
                                addToAttendanceList(response.matches[0]);
                            } else if (response.matches.length > 1) {
                                // Multiple matches - show selection modal
                                showSelectionModal(response.matches);
                            } else {
                                alert('No matching person found');
                            }
                        } else {
                            alert(response.message || 'Person not found or not eligible for Remote Attendance');
                        }
                    },
                    error: function() {
                        alert('Error searching for person. Please try again.');
                    },
                    complete: function() {
                        btnText.html('<i class="bi bi-plus-lg me-1"></i> Add');
                        addBtn.prop('disabled', false);
                        $('#personSearch').val('');
                    }
                });
            });

            // Show selection modal - CORRECTED VERSION
            function showSelectionModal(matches) {
                const list = $('#matchesList');
                list.empty();

                matches.forEach(person => {
                    const item = $(`
            <a href="#" class="list-group-item list-group-item-action match-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${person.name}</strong>
                        ${person.class ? `<span class="text-muted ms-2">${person.class}</span>` : ''}
                        <div class="text-muted small">ID: ${person.id}</div>
                    </div>
                    <span class="badge ${person.filterstatus === 'Active' ? 'bg-success' : 'bg-danger'}">
                        ${person.filterstatus}
                    </span>
                </div>
            </a>
        `);
                    item.data('person', person);
                    list.append(item);
                });

                // SINGLE event handler binding
                list.off('click', '.match-item').on('click', '.match-item', function(e) {
                    e.preventDefault();
                    const person = $(this).data('person');
                    if (!addToAttendanceList(person)) {
                        alert(`${person.name} (ID: ${person.id}) is already in the list`);
                    } else {
                        $('#selectionModal').modal('hide');
                    }
                });

                $('#selectionModal').modal('show');
            }

            // Add person to attendance list - CORRECTED VERSION
            function addToAttendanceList(person) {
                const personId = `${person.type}_${person.id}`;

                // Debug: Log what we're checking against
                console.log('Checking:', personId, 'against', selectedPersons.map(p => p.id));

                if (!selectedPersons.some(p => p.id === personId)) {
                    selectedPersons.push({
                        id: personId,
                        name: person.name,
                        identifier: person.id,
                        type: person.type,
                        filterstatus: person.filterstatus,
                        class: person.class || ''
                    });
                    updateSelectedList();
                    return true;
                }
                return false;
            }

            // Remove person from list
            $(document).on('click', '.remove-item', function() {
                const personId = $(this).data('id');
                selectedPersons = selectedPersons.filter(p => p.id !== personId);
                updateSelectedList();
            });

            // Update the selected persons list
            function updateSelectedList() {
                const list = $('#selectedPersonsList');
                const noSelection = $('#noSelectionMessage');

                list.empty();

                if (selectedPersons.length === 0) {
                    noSelection.show();
                } else {
                    noSelection.hide();
                    selectedPersons.forEach(person => {
                        list.append(`
                            <div class="selected-item">
                                <div>
                                    <strong>${person.name}</strong>
                                    ${person.class ? `<span class="text-muted ms-2">${person.class}</span>` : ''}
                                    <div class="text-muted small">ID: ${person.identifier} (${person.filterstatus})</div>
                                </div>
                                <span class="remove-item" data-id="${person.id}">
                                    <i class="bi bi-x-circle-fill"></i>
                                </span>
                            </div>
                        `);
                    });
                }

                $('#selectedCount').text(selectedPersons.length);
                $('#selectedPersons').val(JSON.stringify(selectedPersons));
            }

            // Form submission
            $('#attendanceForm').on('submit', async function(e) {
                e.preventDefault();

                if (selectedPersons.length === 0) {
                    alert('Please add at least one person to the attendance list');
                    return;
                }

                const punchInTime = $('#punchInTime').val();
                if (!punchInTime) {
                    alert('Please select a valid punch in time');
                    return;
                }

                if (confirm(`Confirm attendance for ${selectedPersons.length} persons?`)) {
                    try {
                        // First get location
                        await getLocation(); // This will populate latitude/longitude variables

                        // Then submit attendance with location data
                        await submitAttendance();
                    } catch (error) {
                        if (error.message === "Geolocation not supported" ||
                            error.message.includes("Error getting location")) {
                            // If geolocation fails, submit without it
                            if (confirm("Location access was denied or not supported. Submit without location data?")) {
                                await submitAttendance(false);
                            }
                        } else {
                            alert("Error: " + error.message);
                        }
                    }
                }
            });

            async function submitAttendance(includeLocation = true) {
                const remarks = $('#remarks').val();
                const punchInTime = $('#punchInTime').val();
                const recordedBy = '<?php echo $associatenumber; ?>';
                const submitBtn = $('#submitAttendance');
                const btnText = submitBtn.html();

                // Prepare data with optional location
                const data = {
                    persons: selectedPersons.map(p => p.id),
                    remarks: remarks,
                    recorded_by: recordedBy,
                    punch_in_time: punchInTime
                };

                if (includeLocation && typeof latitude !== 'undefined' && typeof longitude !== 'undefined') {
                    data.latitude = latitude;
                    data.longitude = longitude;
                }

                try {
                    submitBtn.prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...');

                    const response = await $.ajax({
                        url: 'submit_manual_attendance.php',
                        method: 'POST',
                        contentType: 'application/json',
                        dataType: 'json',
                        data: JSON.stringify(data)
                    });

                    if (response.success) {
                        alert(`Attendance recorded successfully for ${response.count} persons`);
                        selectedPersons = [];
                        updateSelectedList();
                        $('#remarks').val('');
                        // Reset punch in time to current time
                        const now = new Date();
                        const timezoneOffset = now.getTimezoneOffset() * 60000;
                        const localISOTime = (new Date(now - timezoneOffset)).toISOString().slice(0, 16);
                        $('#punchInTime').val(localISOTime);
                    } else {
                        alert('Error: ' + response.message);
                    }
                } catch (error) {
                    alert('Failed to submit attendance. Please try again. Error: ' +
                        (error.responseJSON?.message || error.statusText || error));
                } finally {
                    submitBtn.prop('disabled', false).html(btnText);
                }
            }
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const input = document.getElementById("punchInTime");
            const now = new Date();

            // Format YYYY-MM-DD
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            const today = `${year}-${month}-${day}T00:00`;
            const tomorrow = `${year}-${month}-${day}T23:59`;

            // Set min and max so only current date is allowed
            input.setAttribute("min", today);
            input.setAttribute("max", tomorrow);
        });
    </script>
</body>

</html>