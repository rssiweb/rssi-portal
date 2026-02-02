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
    <?php include 'includes/meta.php' ?>

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

        .spinner-text {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .profile-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e9ecef;
        }

        .profile-photo-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            border: 2px solid #e9ecef;
        }

        .match-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 15px !important;
        }

        .person-info {
            flex: 1;
        }

        .person-details {
            display: flex;
            flex-direction: column;
        }

        .selected-person-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e9ecef;
        }

        .selected-photo-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
            border: 2px solid #e9ecef;
        }

        .selected-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-radius: 8px;
            background-color: #f8f9fa;
            margin-bottom: 10px;
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }

        .selected-item:hover {
            background-color: #f0f2f5;
            border-color: #dee2e6;
        }

        .person-photo {
            flex-shrink: 0;
        }

        .badge-sm {
            font-size: 0.7em;
            padding: 0.25em 0.5em;
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
        // Global variables
        let selectedPersons = [];
        let latitude, longitude; // Declare globally

        function getLocation() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error("Geolocation is not supported by this browser."));
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    position => {
                        latitude = position.coords.latitude;
                        longitude = position.coords.longitude;
                        resolve({
                            latitude,
                            longitude
                        });
                    },
                    error => {
                        const errorMessages = {
                            1: "Permission denied",
                            2: "Position unavailable",
                            3: "Request timeout"
                        };
                        const message = errorMessages[error.code] || "Unknown error";
                        reject(new Error(message));
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            });
        }

        // Utility functions
        function getInitials(name) {
            if (!name) return '';
            const nameParts = name.trim().split(' ');
            const firstInitial = nameParts[0].charAt(0);
            const secondInitial = nameParts[1] ? nameParts[1].charAt(0) :
                (nameParts[0].length > 1 ? nameParts[0].charAt(1) : '');
            return (firstInitial + secondInitial).toUpperCase();
        }

        function getPhotoUrl(person) {
            return person.type === 'student' ?
                (person.photourl || person.photo || '') :
                (person.photo || person.photourl || '');
        }

        function getStatusBadgeClass(filterstatus) {
            switch (filterstatus) {
                case 'Inactive':
                    return 'bg-danger';
                case 'Pending':
                    return 'bg-warning';
                default:
                    return 'bg-success';
            }
        }

        function getTypeBadgeInfo(type) {
            return {
                class: type === 'student' ? 'bg-info' : 'bg-primary',
                text: type === 'student' ? 'Student' : 'Associate'
            };
        }

        function handleImageError(img, initials, size = 40) {
            const placeholder = document.createElement('div');
            placeholder.className = 'profile-photo-placeholder';
            placeholder.style.width = `${size}px`;
            placeholder.style.height = `${size}px`;
            placeholder.style.fontSize = `${size/3}px`;
            placeholder.textContent = initials;

            img.parentNode.replaceChild(placeholder, img);
        }

        function getPersonIdentifier(person) {
            return `${person.type}_${person.id}`;
        }

        function setCurrentDateTime() {
            const now = new Date();
            const timezoneOffset = now.getTimezoneOffset() * 60000;
            return new Date(now - timezoneOffset).toISOString().slice(0, 16);
        }

        function setDateInputLimits() {
            const input = document.getElementById("punchInTime");
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            input.min = `${year}-${month}-${day}T00:00`;
            input.max = `${year}-${month}-${day}T23:59`;
        }

        // Person-related functions
        function isPersonAlreadySelected(personId) {
            return selectedPersons.some(p => p.id === personId);
        }

        function addToAttendanceList(person) {
            const personId = getPersonIdentifier(person);

            if (isPersonAlreadySelected(personId)) {
                return false;
            }

            selectedPersons.push({
                id: personId,
                name: person.name,
                identifier: person.id,
                type: person.type,
                filterstatus: person.filterstatus,
                class: person.class || '',
                photourl: person.photourl || '',
                photo: person.photo || ''
            });

            updateSelectedList();
            return true;
        }

        function removePerson(personId) {
            selectedPersons = selectedPersons.filter(p => p.id !== personId);
            updateSelectedList();
        }

        // UI update functions
        function updateSelectedList() {
            const list = $('#selectedPersonsList');
            const noSelection = $('#noSelectionMessage');

            list.empty();

            // ALWAYS update count & hidden input
            $('#selectedCount').text(selectedPersons.length);
            $('#selectedPersons').val(JSON.stringify(selectedPersons));

            if (selectedPersons.length === 0) {
                noSelection.show();
                return;
            }

            noSelection.hide();

            selectedPersons.forEach(person => {
                const photoUrl = getPhotoUrl(person);
                const initials = getInitials(person.name);
                const statusBadgeClass = getStatusBadgeClass(person.filterstatus);
                const typeBadgeInfo = getTypeBadgeInfo(person.type);

                let photoHtml;
                if (photoUrl) {
                    photoHtml = `
                    <img src="${photoUrl}" 
                         alt="${person.name}" 
                         class="selected-person-photo" 
                         onerror="handleImageError(this, '${initials}', 40)">`;
                } else {
                    photoHtml = `<div class="selected-photo-placeholder">${initials}</div>`;
                }

                list.append(`
                <div class="selected-item">
                    <div class="d-flex align-items-center">
                        <div class="person-photo me-3">
                            ${photoHtml}
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center mb-1">
                                <strong class="me-2">${person.name}</strong>
                                ${person.class ? `<span class="text-muted">${person.class}</span>` : ''}
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="text-muted small me-2">ID: ${person.identifier}</span>
                                <span class="badge ${statusBadgeClass} badge-sm me-2">${person.filterstatus}</span>
                                <span class="badge ${typeBadgeInfo.class} badge-sm">${typeBadgeInfo.text}</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-link p-0 remove-item" data-id="${person.id}" title="Remove">
                        <i class="bi bi-x-circle-fill text-danger"></i>
                    </button>
                </div>
            `);
            });

            $('#selectedCount').text(selectedPersons.length);
            $('#selectedPersons').val(JSON.stringify(selectedPersons));
        }

        function showSelectionModal(matches) {
            const list = $('#matchesList');
            list.empty();

            matches.forEach(person => {
                const photoUrl = getPhotoUrl(person);
                const initials = getInitials(person.name);
                const typeBadgeInfo = getTypeBadgeInfo(person.type);
                const statusBadgeClass = getStatusBadgeClass(person.filterstatus);

                let photoHtml;
                if (photoUrl) {
                    photoHtml = `<img src="${photoUrl}" alt="${person.name}" class="profile-photo" onerror="handleImageError(this, '${initials}')">`;
                } else {
                    photoHtml = `<div class="profile-photo-placeholder">${initials}</div>`;
                }

                const item = $(`
                <a href="#" class="list-group-item list-group-item-action match-item">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            ${photoHtml}
                        </div>
                        <div class="person-info">
                            <div class="person-details">
                                <strong>${person.name}</strong>
                                ${person.class ? `<span class="text-muted ms-2">${person.class}</span>` : ''}
                                <div class="text-muted small">
                                    ID: ${person.id} 
                                    <span class="badge ${statusBadgeClass} ms-2">
                                        ${person.filterstatus}
                                    </span>
                                    <span class="badge ${typeBadgeInfo.class} ms-2">
                                        ${typeBadgeInfo.text}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            `);

                item.data('person', person);
                list.append(item);
            });

            // Event delegation for better performance
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

        // AJAX functions
        function searchPerson(searchTerm) {
            return $.ajax({
                url: 'search_person.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    search_term: searchTerm
                }
            });
        }

        async function submitAttendanceData(data) {
            return $.ajax({
                url: 'submit_manual_attendance.php',
                method: 'POST',
                contentType: 'application/json',
                dataType: 'json',
                data: JSON.stringify(data)
            });
        }

        // Event handlers
        async function handleAddPerson() {
            const searchTerm = $('#personSearch').val().trim();
            const addBtn = $('#addPerson');
            const btnText = addBtn.find('span');

            if (!searchTerm) {
                alert('Please enter a name or ID to search');
                return;
            }

            // Save original state
            const originalHtml = btnText.html();
            addBtn.prop('disabled', true);
            btnText.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Searching');

            try {
                const response = await searchPerson(searchTerm);

                if (response.success) {
                    if (response.matches.length === 1) {
                        addToAttendanceList(response.matches[0]);
                    } else if (response.matches.length > 1) {
                        showSelectionModal(response.matches);
                    } else {
                        alert('No matching person found');
                    }
                } else {
                    alert(response.message || 'Person not found or not eligible for Remote Attendance');
                }
            } catch (error) {
                alert('Error searching for person. Please try again.');
                console.error('Search error:', error);
            } finally {
                // Restore button state
                btnText.html(originalHtml);
                addBtn.prop('disabled', false);
                $('#personSearch').val('');
            }
        }

        async function handleFormSubmit(e) {
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

            if (!confirm(`Confirm attendance for ${selectedPersons.length} person(s)?`)) {
                return;
            }

            try {
                await getLocation();
                await submitAttendance();
            } catch (error) {
                if (error.message === "Geolocation not supported" ||
                    error.message.includes("Permission denied")) {
                    const proceed = confirm("Location access was denied or not supported. Submit without location data?");
                    if (proceed) {
                        await submitAttendance(false);
                    }
                } else {
                    alert("Error: " + error.message);
                }
            }
        }

        async function submitAttendance(includeLocation = true) {
            const remarks = $('#remarks').val();
            const punchInTime = $('#punchInTime').val();
            const recordedBy = '<?php echo $associatenumber; ?>';
            const submitBtn = $('#submitAttendance');

            // Save original state
            const originalHtml = submitBtn.html();
            submitBtn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...');

            // Prepare data
            const data = {
                persons: selectedPersons.map(p => p.id),
                remarks: remarks,
                recorded_by: recordedBy,
                punch_in_time: punchInTime
            };

            if (includeLocation && latitude !== undefined && longitude !== undefined) {
                data.latitude = latitude;
                data.longitude = longitude;
            }

            try {
                const response = await submitAttendanceData(data);

                if (response.success) {
                    alert(`Attendance recorded successfully for ${response.count} person(s)`);
                    resetForm();
                } else {
                    throw new Error(response.message || 'Unknown error');
                }
            } catch (error) {
                const errorMessage = error.responseJSON?.message || error.statusText || error.message;
                alert('Failed to submit attendance. Please try again. Error: ' + errorMessage);
            } finally {
                // Restore button state
                submitBtn.prop('disabled', false).html(originalHtml);
            }
        }

        function resetForm() {
            selectedPersons = [];
            updateSelectedList();
            $('#remarks').val('');
            $('#punchInTime').val(setCurrentDateTime());
        }

        // Main initialization
        $(document).ready(function() {
            // Set up initial values
            $('#punchInTime').val(setCurrentDateTime());
            setDateInputLimits();

            // Event bindings
            $('#addPerson').click(handleAddPerson);
            $('#attendanceForm').on('submit', handleFormSubmit);

            // Event delegation for remove buttons
            $(document).on('click', '.remove-item', function() {
                const personId = $(this).data('id');
                removePerson(personId);
            });

            // Enter key support for search
            $('#personSearch').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    handleAddPerson();
                }
            });
        });

        // Initialize date limits when DOM is loaded
        document.addEventListener("DOMContentLoaded", setDateInputLimits);
    </script>

</body>

</html>