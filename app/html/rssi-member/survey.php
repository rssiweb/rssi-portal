<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}
?>
<?php
// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Initialize connection to PostgreSQL database
    // Assuming $con is the connection object

    // Check for connection errors
    if (!$con) {
        die("Connection failed: " . pg_last_error());
    }

    try {
        // Start transaction
        pg_query($con, 'BEGIN');

        // Retrieve common survey data
        $family_id = uniqid();
        $parentName = $_POST['parentName'];
        $address = $_POST['address'];
        $contact = $_POST['contact'];
        $altcontact = $_POST['altcontact'];
        $houseStay = $_POST['houseStay'];
        $familyMembers = $_POST['familyMembers'];
        $earningSource = $_POST['earningSource'];
        $otherEarningSourceInput = $_POST['otherEarningSourceInput'];
        $additionalInfo = $_POST['additionalInfo'];
        $interestInAdmission = $_POST['interestInAdmission'];
        $surveyorId = $associatenumber;
        $timestamp = date("Y-m-d H:i:s");

        // Build the SQL query for survey data insertion
        $query = "INSERT INTO survey_data (parent_name, address, contact, house_stay, family_members, earning_source, additional_info, surveyor_id, family_id, alt_contact, interest_in_admission, timestamp, other_earning_source_input) 
                    VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13)";

        // Execute the query for survey data insertion
        $result = pg_query_params($con, $query, array($parentName, $address, $contact, $houseStay, $familyMembers, $earningSource, $additionalInfo, $surveyorId, $family_id, $altcontact, $interestInAdmission, $timestamp, $otherEarningSourceInput));

        // Check if the query was successful
        if (!$result) {
            throw new Exception("Failed to insert survey data");
        }

        // Check if student data is available and interest in admission is "Yes"
        if (isset($_POST['students']) && is_array($_POST['students']) && count($_POST['students']) > 0 && $interestInAdmission === "yes") {
            // Insert each student's data into the student_data table
            foreach ($_POST['students'] as $student) {
                // Ensure that array keys exist before accessing them
                $gender = isset($student['gender']) ? $student['gender'] : null;
                $grade = isset($student['grade']) ? $student['grade'] : null;
                $alreadyGoingSchool = isset($student['already_going_school']) ? $student['already_going_school'] : null;
                $alreadyCoaching = isset($student['already_coaching']) ? $student['already_coaching'] : null;

                // Build the SQL query for student data insertion
                $studentQuery = "INSERT INTO student_data (family_id, student_name, age, gender, grade, already_going_school, school_type, already_coaching, coaching_name) 
                                    VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";

                // Execute the query for student data insertion
                $studentResult = pg_query_params($con, $studentQuery, array($family_id, $student['name'], $student['age'], $gender, $grade, $alreadyGoingSchool, isset($student['school_type']) ? $student['school_type'] : null, $alreadyCoaching, isset($student['coaching_name']) ? $student['coaching_name'] : null));

                // Check if the query was successful
                if (!$studentResult) {
                    throw new Exception("Failed to insert student data");
                }
            }
        }

        // Commit the transaction if all data was inserted successfully
        pg_query($con, 'COMMIT');
        
        // Display success message and redirect
        echo "<script>alert('Data added successfully.'); window.location.href = 'survey.php';</script>";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        pg_query($con, 'ROLLBACK');

        // Display error message
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potential Student Survey</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .form-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="form-container">
                    <h1 class="text-center mb-4">Potential Student Survey</h1>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="surveyForm" onsubmit="return validateForm()">
                        <!-- Parent's Name -->
                        <div class="mb-3">
                            <label for="parentName" class="form-label">Parent's Name</label>
                            <input type="text" class="form-control" id="parentName" name="parentName" placeholder="Enter parent's name" required>
                        </div>
                        <!-- Address -->
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <div class="input-group">
                                <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter address" required></textarea>
                                <button class="btn btn-outline-secondary" type="button" id="getAddressBtn">Get Current Address</button>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Contact Number -->
                            <div class="col-md-6 mb-3">
                                <label for="contact" class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" id="contact" name="contact" placeholder="Enter contact number" required>
                            </div>
                            <!-- Alternative Contact Number -->
                            <div class="col-md-6 mb-3">
                                <label for="altcontact" class="form-label">Alternative Contact Number</label>
                                <input type="tel" class="form-control" id="altcontact" name="altcontact" placeholder="Enter alternative contact number">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="houseStay">How long does the family stay in the current house?</label>
                            <select class="form-select" id="houseStay" name="houseStay" required>
                                <option value="" disabled selected>Select duration</option>
                                <option value="<1">Less than 1 year</option>
                                <option value="1">1 year</option>
                                <option value="2">2 years</option>
                                <option value="3">3 years</option>
                                <option value="4">4 years</option>
                                <option value="5">5 years</option>
                                <option value=">5">More than 5 years</option>
                            </select>
                        </div>

                        <!-- Total number of family members -->
                        <div class="mb-3">
                            <label for="familyMembers">Total number of family members:</label>
                            <input type="number" class="form-control" id="familyMembers" name="familyMembers" required>
                        </div>

                        <!-- Main source of earning for the family -->
                        <div class="mb-3">
                            <label for="earningSource">Main source of earning for the family:</label>
                            <select class="form-select" id="earningSource" name="earningSource" onchange="checkOtherOption()" required>
                                <option value="" selected disabled>Select an option</option>
                                <option value="agriculture">Agriculture</option>
                                <option value="business">Business</option>
                                <option value="government_job">Government Job</option>
                                <option value="private_job">Private Job</option>
                                <option value="Construction Worker">Construction Worker</option>
                                <option value="Electrician">Electrician</option>
                                <option value="Plumber">Plumber</option>
                                <option value="Carpenter">Carpenter</option>
                                <option value="Welder">Welder</option>
                                <option value="Mechanic">Mechanic</option>
                                <option value="Painter">Painter</option>
                                <option value="Mason">Mason</option>
                                <option value="Roofer">Roofer</option>
                                <option value="Landscaper">Landscaper</option>
                                <option value="Janitor">Janitor</option>
                                <option value="Factory Worker">Factory Worker</option>
                                <option value="Warehouse Worker">Warehouse Worker</option>
                                <option value="Truck Driver">Truck Driver</option>
                                <option value="Delivery Driver">Delivery Driver</option>
                                <option value="other">Other</option>
                            </select>
                            <div id="otherEarningSource" style="display: none;">
                                <label for="otherEarningSourceInput">Enter other source:</label>
                                <input type="text" class="form-control" id="otherEarningSourceInput" name="otherEarningSourceInput">
                            </div>

                            <!-- Additional Information -->
                            <div class="mb-3">
                                <label for="additionalInfo" class="form-label">Additional Information</label>
                                <textarea class="form-control" id="additionalInfo" name="additionalInfo" rows="3" placeholder="Enter any additional information"></textarea>
                            </div>

                            <div class="card">
                                <div class="card-body">
                                    <!-- Checkbox for Interest in Admission -->
                                    <div class="mb-3">
                                        <label for="interestInAdmission">Interested in Admission</label>
                                        <select class="form-select" id="interestInAdmission" name="interestInAdmission" onchange="toggleStudentFields()" required>
                                            <option value="" selected disabled>Select an option</option>
                                            <option value="no">No</option>
                                            <option value="yes">Yes</option>
                                        </select>
                                    </div>
                                    <!-- Student Details -->
                                    <div id="studentsContainer" style="display: none;">
                                        <div class="student-details">
                                            <!-- Student's Name -->
                                            <div class="mb-3">
                                                <label for="sname">Student's Name</label>
                                                <input type="text" class="form-control" id="sname" name="students[0][name]" placeholder="Enter student's name">
                                            </div>

                                            <!-- Age and Gender -->
                                            <div class="row">
                                                <!-- Age -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="sage" class="form-label">Age</label>
                                                    <input type="number" class="form-control" id="sage" name="students[0][age]" placeholder="Enter student's age">
                                                </div>
                                                <!-- Gender -->
                                                <div class="col-md-6 mb-3">
                                                    <label for="sgender" class="form-label">Gender</label>
                                                    <select class="form-select" id="sgender" name="students[0][gender]">
                                                        <option value="" selected disabled>Select gender</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                        <option value="Binary">Binary</option>
                                                        <option value="Prefer not to say">Prefer not to say</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Grade Eligibility -->
                                            <div class="mb-3">
                                                <label for="sgrade">Grade Eligibility</label>
                                                <select class="form-select" id="sgrade" name="students[0][grade]">
                                                    <option value="" selected disabled>Select Grade</option>
                                                    <option value="Nursery">Nursery</option>
                                                    <option value="LKG">LKG</option>
                                                    <option value="UKG">UKG</option>
                                                    <option value="Class 1">Class 1</option>
                                                    <option value="Class 2">Class 2</option>
                                                    <option value="Class 3">Class 3</option>
                                                    <option value="Class 4">Class 4</option>
                                                    <option value="Class 5">Class 5</option>
                                                    <option value="Class 6">Class 6</option>
                                                    <!-- Add additional grade options here -->
                                                </select>
                                            </div>

                                            <!-- Other student details -->
                                            <div class="mb-3">
                                                <label for="alreadyGoingSchool">Is the student already going to school?</label>
                                                <select class="form-select" id="alreadyGoingSchool" name="students[0][already_going_school]">
                                                    <option value="" selected disabled>Select an option</option>
                                                    <option value="yes">Yes</option>
                                                    <option value="no">No</option>
                                                </select>
                                            </div>

                                            <!-- School Type -->
                                            <div class="mb-3" id="schoolType">
                                                <label for="schoolType">Type of school currently attending:</label>
                                                <select class="form-select" id="schoolType" name="students[0][school_type]">
                                                    <option value="" selected disabled>Select an option</option>
                                                    <option value="private">Private</option>
                                                    <option value="government">Government</option>
                                                </select>
                                            </div>

                                            <!-- Coaching Classes -->
                                            <div class="mb-3">
                                                <label for="alreadyCoaching">Is the student already attending any coaching classes?</label>
                                                <select class="form-select" id="alreadyCoaching" name="students[0][already_coaching]">
                                                    <option value="" selected disabled>Select an option</option>
                                                    <option value="yes">Yes</option>
                                                    <option value="no">No</option>
                                                </select>
                                            </div>

                                            <!-- Coaching Name -->
                                            <div class="mb-3" id="coachingNameInput">
                                                <label for="coachingName">Name of the coaching:</label>
                                                <input type="text" class="form-control" id="coachingName" name="students[0][coaching_name]">
                                            </div>

                                            <div class="mb-3">
                                                <button type="button" class="btn btn-primary" onclick="addStudentDetails()">Add Student</button>
                                                <!-- <button type="button" class="btn btn-danger" onclick="removeStudentDetails(this)">Remove Student</button> -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener("DOMContentLoaded", function() {
                                    toggleStudentFields();
                                    document.getElementById('interestInAdmission').addEventListener('change', toggleStudentFields);
                                });

                                function toggleStudentFields() {
                                    var dropdown = document.getElementById('interestInAdmission');
                                    var studentFields = document.querySelectorAll('.student-details .mb-3 input, .student-details .mb-3 select');

                                    if (dropdown.value === 'yes') {
                                        document.getElementById('studentsContainer').style.display = 'block';
                                        studentFields.forEach(function(field) {
                                            // Check if the field is the coachingName input
                                            if (field.id !== 'coachingName' && field.id !== 'schoolType') {
                                                // If it's not the coachingName input, mark it as required
                                                field.required = true;
                                            }
                                        });
                                    } else {
                                        document.getElementById('studentsContainer').style.display = 'none';
                                        studentFields.forEach(function(field) {
                                            field.required = false;
                                            field.value = ''; // Reset field value
                                        });
                                    }
                                }
                            </script>
                            <!-- Submit and Surveyor Details -->
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary mt-2">Submit</button>
                                <p class="mb-0">Surveyor Id: <?php echo $fullname . '&nbsp;(' . $associatenumber . ')' ?></p>
                            </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-AH1SsjB9g5JTYrtoDkVhY2Pn9HlXKG+C4fE9g6kfmHfAe8h+if3rpTkHidv+3wRK" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-EpDs/fzj3EhLioRy5zflSbSRnPZvC5Zx/9Tl8KZ5u3xN4G8W4FbWyRjJeabjkt+s" crossorigin="anonymous"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAO7Z3VLtKImi3UGFE6n6QKhDqfDBBCT3o&libraries=places"></script>


    <script>
        document.getElementById('getAddressBtn').addEventListener('click', function() {
            // Check if Geolocation is supported
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    // Retrieve latitude and longitude
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;

                    // Make a reverse geocoding request to get address
                    var geocoder = new google.maps.Geocoder();
                    var latlng = new google.maps.LatLng(lat, lng);
                    geocoder.geocode({
                        'location': latlng
                    }, function(results, status) {
                        if (status === google.maps.GeocoderStatus.OK) {
                            if (results[0]) {
                                // Update the address field with the retrieved address
                                document.getElementById('address').value = results[0].formatted_address;
                            } else {
                                alert('No results found');
                            }
                        } else {
                            alert('Geocoder failed due to: ' + status);
                        }
                    });
                });
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        });
    </script>

    <script>
        var studentCount = 0;

        function addStudentDetails() {
            studentCount++;

            var studentsContainer = document.getElementById('studentsContainer');
            var studentDetails = document.querySelector('.student-details').cloneNode(true);

            // Clear the input values in the cloned student details
            studentDetails.querySelectorAll('input').forEach(function(input) {
                input.value = '';
            });

            // Clear the select values in the cloned student details
            studentDetails.querySelectorAll('select').forEach(function(select) {
                select.selectedIndex = 0; // Reset to the default option
            });

            // Update input field names and IDs
            studentDetails.querySelectorAll('input, select').forEach(function(input) {
                var oldName = input.getAttribute('name');
                var newName = oldName.replace('[0]', '[' + studentCount + ']');
                input.setAttribute('name', newName);

                var oldId = input.getAttribute('id');
                if (oldId) {
                    var newId = oldId + '-' + studentCount;
                    input.setAttribute('id', newId);
                }
            });

            // Add remove button
            var removeButton = document.createElement('button');
            removeButton.textContent = 'Remove Student';
            removeButton.type = 'button';
            removeButton.classList.add('btn', 'btn-danger');
            removeButton.addEventListener('click', function() {
                removeStudentDetails(this);
            });
            studentDetails.appendChild(removeButton);

            studentsContainer.appendChild(studentDetails);
        }

        function removeStudentDetails(button) {
            var studentDetails = button.parentNode;
            studentDetails.remove();
        }
    </script>
    <script>
        function checkOtherOption() {
            var earningSource = document.getElementById('earningSource');
            var otherEarningSource = document.getElementById('otherEarningSource');
            var otherEarningSourceInput = document.getElementById('otherEarningSourceInput');

            if (earningSource.value === 'other') {
                otherEarningSource.style.display = 'block';
                otherEarningSourceInput.required = true;
            } else {
                otherEarningSource.style.display = 'none';
                otherEarningSourceInput.required = false;
            }
        }
    </script>
    <script>
        // Function to update labels with red asterisks
        function updateLabels() {
            // Get all input, select, and textarea elements with the "required" attribute
            var requiredElements = document.querySelectorAll('input[required], select[required], textarea[required]');

            // Loop through each required element
            requiredElements.forEach(function(element) {
                // Get the ID of the element
                var elementId = element.id;

                // Find the label associated with the element
                var label = document.querySelector('label[for="' + elementId + '"]');

                // If a label is found and does not already contain the asterisk, add it
                if (label && !label.querySelector('span.required-marker')) {
                    label.insertAdjacentHTML('beforeend', '<span class="required-marker" style="color: red;"> *</span>');
                }
            });
        }

        // Run the function initially when the DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            updateLabels();
        });

        // Use event delegation to handle dynamically added elements
        document.body.addEventListener('change', function(event) {
            // Update the labels whenever a change occurs
            updateLabels();
        });
    </script>
</body>

</html>