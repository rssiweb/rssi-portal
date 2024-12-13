<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Check if the form data has been submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve and sanitize form data
    $application_number = htmlspecialchars($_POST['applicationNumber_verify'] ?? '');
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $now = date('Y-m-d H:i:s');

    function getUserIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ipList[0]);
            return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : $_SERVER['REMOTE_ADDR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    $ip_address = getUserIpAddr();

    // Generate a unique ID
    $unique_id = uniqid();

    // Validate database connection
    if (!$con) {
        die("Error: Unable to connect to the database.");
    }
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch form data
    $interview_id = uniqid();
    $application_number = pg_escape_string($con, $_POST['applicationNumber_verify']);
    $applicant_name = pg_escape_string($con, $_POST['name']);
    $applicant_email = pg_escape_string($con, $_POST['email']);
    $subject_knowledge = (int) $_POST['subjectKnowledge'];
    $computer_knowledge = (int) $_POST['computerKnowledge'];
    $demo_class = (int) $_POST['demoClass'];
    $written_test = isset($_POST['writtenTest']) ? (int) $_POST['writtenTest'] : NULL;
    $experience = pg_escape_string($con, $_POST['experience']);
    $remarks = pg_escape_string($con, $_POST['remarks']);

    // Check if interviewer_ids is set and not empty, if not, set it to an empty string
    $interviewer_ids_string = isset($_POST['interviewer_ids']) ? pg_escape_string($con, $_POST['interviewer_ids']) : '';

    $interview_duration = (int) $_POST['interview_duration'];

    // Correct the declaration field to boolean (true/false)
    $declaration = isset($_POST['declaration']) && $_POST['declaration'] == 'on' ? 'true' : 'false';

    // Insert data into the interview table
    $insert_query = "INSERT INTO interview (application_number, applicant_name, applicant_email, subject_knowledge, computer_knowledge, demo_class, written_test, experience, remarks, interviewer_ids, interview_duration, declaration) 
                     VALUES ('$application_number', '$applicant_name', '$applicant_email', $subject_knowledge, $computer_knowledge, $demo_class, $written_test, '$experience', '$remarks', '$interviewer_ids_string', $interview_duration, $declaration)";

    // Execute the query
    $result = pg_query($con, $insert_query);
    $cmdtuples = pg_affected_rows($result);
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

    <title>Technical Interview</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

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
    <style>
        @media (min-width:767px) {
            .left {
                margin-left: 2%;
            }
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
    <!-- Include Select2 CSS and JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>


</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Interview Assessment</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">Interview Assessment</li>
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
                            <?php if (@$ticket_id != null && @$cmdtuples == 0) { ?>

                                <div class="alert alert-danger alert-dismissible" role="alert"
                                    style="text-align: -webkit-center;">
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span>ERROR: Oops, something wasn't right.</span>
                                </div>
                                <?php
                            } else if (@$cmdtuples == 1) { ?>

                                    <div class="alert alert-success alert-dismissible" role="alert"
                                        style="text-align: -webkit-center;">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                        <i class="bi bi-check2-circle"></i>
                                        <span>Ticket successfully created. Your Ticket ID is <?php echo @$ticket_id ?>.</span>
                                    </div>
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                            <?php } ?>
                            <div class="container">

                                <form id="applicationForm" method="POST">
                                    <!-- Application Number Input -->
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="applicationNumber_verify"
                                            name="applicationNumber_verify" placeholder="Enter your Application Number"
                                            required>
                                        <button type="button" class="btn btn-primary" id="verifybutton">Fetch Applicant
                                            Data</button>
                                    </div>
                                    <div id="detailsSection" class="d-none">
                                        <!-- Name Input -->
                                        <input type="hidden" class="form-control" id="name" name="name" readonly>
                                        <input type="hidden" class="form-control" id="email" name="email" readonly>
                                        <div class="card">
                                            <div class="card-body mt-3">
                                                <div class="row align-items-center">
                                                    <!-- First Table (Contact details) -->
                                                    <div class="col-md-5">
                                                        <table style="width: 100%; border-collapse: collapse;">
                                                            <tbody>
                                                                <tr>
                                                                    <td><strong>Applicant Name:</strong></td>
                                                                    <td><span id="applicantFullName"></span></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Application Number:</strong></td>
                                                                    <td><span id="applicationNumber"></span></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Contact Number:</strong></td>
                                                                    <td><span id="contactNumber"></span></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Email:</strong></td>
                                                                    <td><span id="email_view"></span></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <!-- Second Table (Additional details) -->
                                                    <div class="col-md-4">
                                                        <table style="width: 100%; border-collapse: collapse;">
                                                            <tbody>
                                                                <tr>
                                                                    <td><strong>Aadhar Card Number:</strong></td>
                                                                    <td><span id="aadharNumberElement"></span></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Association Type:</strong></td>
                                                                    <td><span id="associationType"></span></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Work Profile:</strong></td>
                                                                    <td><span id="position"></span></td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Subject Preference 1:</strong></td>
                                                                    <td><span id="subjectPreference1"></span></td>
                                                                </tr>
                                                                <tr>
                                                                    <td></td>
                                                                    <td><span id="resumeText"></span></td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <!-- Candidate Photo (in the same row) -->
                                                    <div class="col-md-3 d-flex justify-content-center">
                                                        <div class="photo-box"
                                                            style="border: 1px solid #ccc; padding: 10px; width: 150px; height: 200px; display: flex; align-items: center; justify-content: center;"
                                                            id="candidatePhotoContainer">
                                                            <!-- The iframe will be dynamically inserted here if the photo is available -->
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="container my-5">
                                            <!-- <h2 class="mb-4 text-center">Interview Assessment Form</h2> -->

                                            <div class="row g-3 align-items-center">

                                                <!-- Subject Knowledge -->
                                                <div class="col-md-6">
                                                    <label for="documents" class="form-label">Documents
                                                        Checklist</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="documents" class="form-label">Verified Documents</label>
                                                    <select id="documents" name="documents[]" class="form-control"
                                                        multiple="multiple">
                                                        <option value="highschool_marksheet">Highschool Marksheet
                                                        </option>
                                                        <option value="intermediate_marksheet">Intermediate Marksheet
                                                        </option>
                                                        <option value="graduation_marksheet">Graduation Marksheet
                                                        </option>
                                                        <option value="post_graduation_marksheet">Post-Graduation
                                                            Marksheet</option>
                                                        <option value="additional_training_course_certificate">
                                                            Additional training or course Certificate</option>
                                                        <option value="previous_employment_info">Previous employment
                                                            information</option>
                                                        <option value="pan_card">PAN Card</option>
                                                        <option value="aadhar_card">Aadhar Card</option>
                                                    </select>
                                                </div>
                                                <!-- Subject Knowledge -->
                                                <div class="col-md-6">
                                                    <label for="subjectKnowledge" class="form-label">Subject
                                                        Knowledge</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="number" class="form-control" name="subjectKnowledge"
                                                        id="subjectKnowledge" min="1" max="10"
                                                        placeholder="Enter marks (1-10)" required>
                                                </div>

                                                <!-- Computer Knowledge -->
                                                <div class="col-md-6">
                                                    <label for="computerKnowledge" class="form-label">Computer
                                                        Knowledge</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="number" class="form-control" name="computerKnowledge"
                                                        id="computerKnowledge" min="1" max="10"
                                                        placeholder="Enter marks (1-10)" required>
                                                </div>

                                                <!-- Demo Class Performance -->
                                                <div class="col-md-6">
                                                    <label for="demoClass" class="form-label">Demo Class
                                                        Performance</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="number" class="form-control" name="demoClass"
                                                        id="demoClass" min="1" max="10" placeholder="Enter marks (1-10)"
                                                        required>
                                                </div>

                                                <!-- Written Test Marks -->
                                                <div class="col-md-6">
                                                    <label for="writtenTest" class="form-label">Written Test
                                                        Marks</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="number" class="form-control" name="writtenTest"
                                                        id="writtenTest" placeholder="Enter marks">
                                                </div>

                                                <!-- Experience and Qualifications -->
                                                <div class="col-md-6">
                                                    <label for="experience" class="form-label">Experience and
                                                        Qualifications</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <textarea class="form-control" name="experience" id="experience"
                                                        rows="3" placeholder="Enter details" required></textarea>
                                                </div>

                                                <!-- Remarks -->
                                                <div class="col-md-6">
                                                    <label for="remarks" class="form-label">Remarks</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <textarea class="form-control" name="remarks" id="remarks" rows="3"
                                                        placeholder="Enter remarks" required></textarea>
                                                </div>

                                                <!-- Interviewer Panel Section -->
                                                <div class="row border p-3 rounded mt-5">

                                                    <div class="col-md-12 mt-3">
                                                        <table class="table table-bordered" id="interviewer_table">
                                                            <thead>
                                                                <tr>
                                                                    <th>Employee No.</th>
                                                                    <th>Name</th>
                                                                    <th>Designation</th>
                                                                    <th>Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <!-- Dynamic rows will be added here -->
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <div class="col-md-12 mb-3">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <input type="text" id="employee_no" class="form-control"
                                                                    placeholder="Enter Employee ID for multiple Interviewers">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <button type="button" id="add_interviewer"
                                                                    class="btn btn-primary">Add Interviewer</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="hidden" id="interviewer_ids" name="interviewer_ids">
                                                <div class="row mt-3">
                                                    <div class="col-md-12">
                                                        <label class="form-label fw-bold">Interview Assessment filled
                                                            by: </label>
                                                        <span
                                                            class="fw-normal"><?php echo $fullname . ' (' . $associatenumber . ')'; ?></span>
                                                    </div>
                                                </div>
                                                <div class="row mt-3">
                                                    <div class="col-md-6">
                                                        <label for="interview_duration" class="form-label">Interview
                                                            Duration:</label>
                                                        <input type="number" name="interview_duration"
                                                            id="interview_duration" class="form-control"
                                                            placeholder="Minutes">
                                                    </div>
                                                </div>

                                                <div class="mb-3 form-check">
                                                    <input type="checkbox" class="form-check-input" id="declaration"
                                                        name="declaration" required>
                                                    <label class="form-check-label" for="declaration">I accept that I
                                                        have read the terms of agreement and agree to abide by the terms
                                                        and conditions mentioned therein.</label>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="text-center mt-4">
                                                <button type="submit" class="btn btn-primary">Submit</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <!-- Hidden Form for AJAX Request -->
                                <form id="get_details_tr" action="#" method="POST">
                                    <input type="hidden" name="form-type" value="get_details_tr">
                                    <input type="hidden" name="applicationNumber_verify_input">
                                </form>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <script>
        const applicationNumberInput = document.getElementById("applicationNumber_verify");
        const hiddenApplicationNumberInput = document.getElementsByName("applicationNumber_verify_input")[0];
        const nameInput = document.getElementById("name");
        const emailInput = document.getElementById("email");
        const detailsSection = document.getElementById("detailsSection");

        // Card fields
        const applicantFullName = document.getElementById("applicantFullName");
        const applicationNumber = document.getElementById("applicationNumber");
        const contactNumber = document.getElementById("contactNumber");
        const email_view = document.getElementById("email_view");
        const aadharNumberElement = document.getElementById("aadharNumberElement");
        const associationType = document.getElementById("associationType");
        // const preferredBranch = document.getElementById("preferredBranch");
        const position = document.getElementById("position");
        const subjectPreference1 = document.getElementById("subjectPreference1");
        const resumeLink = document.getElementById("resumeLink");

        applicationNumberInput.addEventListener("input", function () {
            hiddenApplicationNumberInput.value = this.value;
        });

        document.getElementById("verifybutton").addEventListener("click", function (event) {
            event.preventDefault();

            fetch('http://localhost:8082/rssi-member/payment-api.php', {
                method: 'POST',
                body: new FormData(document.getElementById("get_details_tr"))
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Populate the card fields with fetched data
                        nameInput.value = data.data.applicantFullName;
                        emailInput.value = data.data.email;

                        applicantFullName.textContent = data.data.applicantFullName || "N/A";
                        applicationNumber.textContent = data.data.application_number || "N/A";
                        contactNumber.textContent = data.data.contact || "N/A";
                        email_view.textContent = data.data.email || "N/A";
                        // aadharNumber.textContent = data.data.aadhar_number || "N/A";

                        // Fetch and mask the Aadhar number
                        let aadharNumber = data.data.aadhar_number || "N/A";

                        // Check if Aadhar number is valid (12 digits)
                        if (aadharNumber !== "N/A" && aadharNumber.length === 12) {
                            aadharNumber = aadharNumber.slice(0, 2) + "XX-XXXX" + aadharNumber.slice(-4);
                        }

                        // Update the DOM element with the masked Aadhar number
                        aadharNumberElement.textContent = aadharNumber;

                        associationType.textContent = data.data.association_type || "N/A";
                        // preferredBranch.textContent = data.data.base_branch || "N/A";
                        position.textContent = data.data.position || "N/A";
                        subjectPreference1.textContent = data.data.subject_preference_1 || "N/A";

                        // Handle Resume Link
                        if (data.data.resumeLink) {
                            resumeText.innerHTML = `<a href="${data.data.resumeLink}" target="_blank">View Applicant CV</a>`;
                        } else {
                            resumeText.textContent = "No resume uploaded yet"; // Display plain text instead of a link
                        }

                        const candidatePhotoContainer = document.getElementById("candidatePhotoContainer");

                        if (data.data.photo) {
                            // Extract file ID from the Google Drive link
                            const photoID = data.data.photo.split("id=")[1];

                            // Generate the preview URL for iframe
                            const previewUrl = `https://drive.google.com/file/d/${photoID}/preview`;

                            // Create iframe dynamically
                            const iframe = document.createElement('iframe');
                            iframe.src = previewUrl;
                            iframe.width = "150";
                            iframe.height = "200";
                            iframe.frameborder = "0";
                            iframe.allow = "autoplay";
                            iframe.sandbox = "allow-scripts allow-same-origin"

                            // Clear previous content and append the iframe
                            candidatePhotoContainer.innerHTML = '';
                            candidatePhotoContainer.appendChild(iframe);
                        } else {
                            // Default placeholder when no photo is available
                            candidatePhotoContainer.innerHTML = "No photo available";
                        }

                        // Make the details section visible
                        detailsSection.classList.remove("d-none");
                        // Disable the form fields and button after fetching the data
                        // applicationNumberInput.disabled = true;
                        // document.getElementById("verifybutton").disabled = true;
                        alert("User data fetched successfully!");
                    } else if (data.status === 'no_records') {
                        // Clear the card fields
                        applicantFullName.textContent = "";
                        applicationNumber.textContent = "";
                        contactNumber.textContent = "";
                        email_view.textContent = "";
                        aadharNumberElement.textContent = "";
                        associationType.textContent = "";
                        // preferredBranch.textContent = "";
                        position.textContent = "";
                        subjectPreference1.textContent = "";
                        resumeText.textContent = "";

                        // Hide the details section
                        detailsSection.classList.add("d-none");
                        alert("No records found in the database.");
                    } else {
                        console.error('Error:', data.message);
                        alert("Error retrieving user data.");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Error fetching user data. Please try again later.");
                });
        });
    </script>
    <script>
        window.onload = function () {
            // Select all input elements with the 'required' attribute
            const requiredFields = document.querySelectorAll('input[required], select[required], textarea[required]');

            // Loop through each required field
            requiredFields.forEach(function (field) {
                // Get the label associated with the field
                const label = document.querySelector(`label[for="${field.id}"]`);

                // If label exists, add the asterisk
                if (label) {
                    label.innerHTML = label.innerHTML + ' <span style="color:red;">*</span>';
                }
            });
        }
    </script>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>

    <script>
        $(document).ready(function () {
            const interviewerIds = []; // Array to hold the IDs of interviewers

            // Add interviewer
            $('#add_interviewer').on('click', function () {
                const employeeNo = $('#employee_no').val();

                if (!employeeNo) {
                    alert('Please enter an Employee No.');
                    return;
                }

                // Disable button and show loading text
                $('#add_interviewer').prop('disabled', true).text('Loading...');

                // Fetch employee details via AJAX
                $.ajax({
                    url: 'payment-api.php',
                    type: 'POST',
                    data: { 'form-type': 'fetch_employee', employee_no: employeeNo },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            const { id, name, position } = response.data;

                            // Check if interviewer is already added
                            if (interviewerIds.includes(id)) {
                                alert('This interviewer has already been added.');
                            } else {
                                // Add the interviewer ID to the array
                                interviewerIds.push(id);

                                // Create a new row for the added interviewer
                                const newRow = `
                            <tr id="row-${id}">
                                <td>${id}</td>
                                <td>${name}</td>
                                <td>${position}</td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-row" data-id="${id}">X</button></td>
                            </tr>
                        `;
                                $('#interviewer_table tbody').append(newRow);
                                $('#employee_no').val(''); // Clear the input
                            }

                            // Update the hidden input field with the current list of interviewer IDs
                            $('#interviewer_ids').val(interviewerIds.join(','));
                        } else {
                            alert(response.message || 'Unable to fetch employee details.');
                        }
                    },
                    error: function (xhr) {
                        const errorMessage = xhr.responseJSON?.message || 'An error occurred while fetching employee details.';
                        alert(errorMessage);
                    },
                    complete: function () {
                        $('#add_interviewer').prop('disabled', false).text('Add Interviewer');
                    },
                });
            });

            // Remove interviewer
            $('#interviewer_table').on('click', '.remove-row', function () {
                const id = $(this).data('id');

                // Remove the row from the table
                $(`#row-${id}`).remove();

                // Remove the interviewer ID from the array
                const index = interviewerIds.indexOf(id);
                if (index > -1) {
                    interviewerIds.splice(index, 1);
                }

                // Update the hidden input field with the current list of interviewer IDs
                $('#interviewer_ids').val(interviewerIds.join(','));
            });
        });


    </script>

</body>

</html>