<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

validation();

// Calculate academic year
if (date('m') == 1 || date('m') == 2 || date('m') == 3) { // Upto March
    $academic_year = (date('Y') - 1) . '-' . date('Y');
} else { // After March
    $academic_year = date('Y') . '-' . (date('Y') + 1);
}

// Retrieve POST data
@$exception_id = $_POST['exception_id'];
@$applicant_id = strtoupper($_POST['applicant_id']);
@$lyear = $_POST['lyear'] ? $_POST['lyear'] : $academic_year;
@$is_user = $_POST['is_user'];

date_default_timezone_set('Asia/Kolkata');

// Build SQL query with conditions based on role
$query = "SELECT *, 
                 CASE
                     WHEN submitted_on >= DATE_TRUNC('year', CURRENT_DATE) AND submitted_on < DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year' 
                     THEN CONCAT(EXTRACT(YEAR FROM CURRENT_DATE), '-', EXTRACT(YEAR FROM CURRENT_DATE) + 1)
                     WHEN submitted_on >= DATE_TRUNC('year', CURRENT_DATE) - INTERVAL '1 year' AND submitted_on < DATE_TRUNC('year', CURRENT_DATE)
                     THEN CONCAT(EXTRACT(YEAR FROM CURRENT_DATE) - 1, '-', EXTRACT(YEAR FROM CURRENT_DATE))
                 END AS lyear
          FROM exception_requests 
          LEFT JOIN (SELECT associatenumber, fullname, email, phone FROM rssimyaccount_members) faculty 
          ON exception_requests.submitted_by = faculty.associatenumber  
          LEFT JOIN (SELECT student_id, studentname, emailaddress, contact FROM rssimyprofile_student) student 
          ON exception_requests.submitted_by = student.student_id 
          WHERE 1=1";

// Apply filters based on role
if ($role != 'Admin') {
    // Non-admins should only see their own records
    $query .= " AND exception_requests.submitted_by = '$associatenumber'";
}

// Apply filters based on provided input
if (!empty($exception_id)) {
    $query .= " AND id = '$exception_id'";
}

if (!empty($applicant_id)) {
    $query .= " AND exception_requests.submitted_by = '$applicant_id'";
}

// Apply filter based on lyear
if (!empty($lyear)) {
    $lyearStart = explode('-', $lyear)[0] . '-04-01';
    $lyearEnd = explode('-', $lyear)[1] . '-03-31';
    $query .= " AND submitted_on BETWEEN '$lyearStart' AND '$lyearEnd'";
}

// Order by submitted_on desc
$query .= " ORDER BY submitted_on DESC";

$result = pg_query($con, $query);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

$resultArr = pg_fetch_all($result);
?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Exception Dashboard</title>

    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
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
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }

        #passwordHelpBlock {
            display: block;
        }

        .input-help {
            vertical-align: top;
            display: inline-block;
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Exception Dashboard</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Raise Exception</a></li>
                    <li class="breadcrumb-item active">Exception Dashboard</li>
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
                            <form action="" method="POST">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">
                                        <input name="exception_id" id="exception_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Exception ID" value="<?php echo $exception_id ?>">

                                        <?php if ($role == 'Admin') { ?>
                                            <!-- Only show this input if the user is an admin -->
                                            <input name="applicant_id" id="applicant_id" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo $applicant_id ?>">
                                        <?php } ?>

                                        <select name="lyear" id="lyear" class="form-select" style="width:max-content; display:inline-block" placeholder="Academic Year" required>
                                            <?php if ($lyear == null) { ?>
                                                <option disabled selected hidden>Academic Year</option>
                                            <?php } else { ?>
                                                <option hidden selected><?php echo $lyear ?></option>
                                            <?php } ?>
                                            <!-- Add options dynamically if needed -->
                                            <?php
                                            // Dynamically generate the academic year options
                                            $currentYear = date('Y');
                                            for ($i = 0; $i < 5; $i++) {
                                                $startYear = $currentYear - $i;
                                                $endYear = $startYear + 1;
                                                $value = "$startYear-$endYear";
                                                echo "<option value='$value'>$value</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                        <i class="bi bi-search"></i>&nbsp;Search</button>
                                </div>
                                <div id="filter-checks">
                                    <input type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_POST['is_user'])) echo "checked='checked'"; ?> />
                                    <label for="is_user" style="font-weight: 400;">Search by Exception ID</label>
                                </div>
                            </form>

                            <script>
                                // Initial check for checkbox status
                                const checkbox = document.getElementById('is_user');
                                const exceptionInput = document.getElementById("exception_id");
                                const applicantInput = document.getElementById("applicant_id");
                                const lyearInput = document.getElementById("lyear");

                                function updateFieldStatus() {
                                    if (checkbox.checked) {
                                        // When checkbox is checked, disable applicant_id and lyear, enable exception_id
                                        exceptionInput.disabled = false;
                                        if (applicantInput) applicantInput.disabled = true; // Check if applicantInput exists
                                        lyearInput.disabled = true;
                                    } else {
                                        // When checkbox is unchecked, enable applicant_id and lyear, disable exception_id
                                        exceptionInput.disabled = true;
                                        if (applicantInput) applicantInput.disabled = false; // Check if applicantInput exists
                                        lyearInput.disabled = false;
                                    }
                                }

                                // Update field status based on checkbox initial status
                                updateFieldStatus();

                                // Add event listener for checkbox changes
                                checkbox.addEventListener('change', updateFieldStatus);
                            </script>

                            <script>
                                <?php if (date('m') == 1 || date('m') == 2 || date('m') == 3) { ?>
                                    var currentYear = new Date().getFullYear() - 1;
                                <?php } else { ?>
                                    var currentYear = new Date().getFullYear();
                                <?php } ?>
                                for (var i = 0; i < 5; i++) {
                                    var next = currentYear + 1;
                                    var year = currentYear + '-' + next;
                                    //next.toString().slice(-2)
                                    $('#lyear').append(new Option(year, year));
                                    currentYear--;
                                }
                            </script>

                            <div class="col" style="display: inline-block; width:100%; text-align:right;">
                                Record count:&nbsp;<?php echo sizeof($resultArr) ?>
                            </div>

                            <div class="table-responsive">
                                <table class="table" id="table-id">
                                    <thead>
                                        <tr>
                                            <th>Exception ID</th>
                                            <th>Submitted By</th>
                                            <th>Submitted On</th>
                                            <th>Exception Type</th>
                                            <th>Exception Date & Time</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Reviewed By</th>
                                            <th>Remarks</th>
                                            <?php if ($role == 'Admin') { ?>
                                                <th>Actions</th>
                                            <?php } ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (sizeof($resultArr) > 0) { ?>
                                            <?php foreach ($resultArr as $array) { ?>
                                                <tr>
                                                    <td><?php echo $array['id']; ?></td>
                                                    <td>
                                                        <?php echo $array['submitted_by'] . '<br>' . (!empty($array['fullname']) ? $array['fullname'] : $array['studentname']); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo @date("d/m/Y g:i a", strtotime($array['submitted_on'])); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($array['sub_exception_type'] ?? ''); ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if (!empty($array['start_date_time'])) {
                                                            echo @date("d/m/Y g:i a", strtotime($array['start_date_time']));
                                                        } elseif (!empty($array['end_date_time'])) {
                                                            echo @date("d/m/Y g:i a", strtotime($array['end_date_time']));
                                                        } else {
                                                            echo '-';
                                                        }
                                                        ?>
                                                    </td>

                                                    <td>
                                                        <?php echo $array['reason'] ?? ''; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($array['status'] ?? ''); ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        echo htmlspecialchars($array['reviewer_id'] ?? '');

                                                        echo !empty($array['reviewer_status_updated_on'])
                                                            ? " on " . date("d/m/Y g:i a", strtotime($array['reviewer_status_updated_on']))
                                                            : '';
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($array['reviewer_remarks'] ?? ''); ?>
                                                    </td>
                                                    <?php if ($role == 'Admin') { ?>
                                                        <td>
                                                            <button type="button" onclick="showDetails('<?php echo $array['id']; ?>')" title="Details" style="background: none; border: none;">
                                                                <i class="bi bi-box-arrow-up-right" style="font-size: 14px; color:#777777;"></i>
                                                            </button>

                                                            <?php if (!empty($array['phone']) || !empty($array['contact'])) { ?>
                                                                <a href="https://api.whatsapp.com/send?phone=91<?php echo $array['phone'] ?? $array['contact']; ?>&text=Dear <?php echo $array['fullname'] ?? $array['studentname']; ?> (<?php echo $array['submitted_by']; ?>),%0A%0AYour exception request (ID: <?php echo $array['id']; ?>) has been processed. Status: <?php echo $array['status']; ?>.%0A%0AFor more detailed information, please check your email.%0A%0A--RSSI%0A%0A**This is a system generated message." target="_blank">
                                                                    <i class="bi bi-whatsapp" style="color:#444444;" title="Send SMS"></i>
                                                                </a>
                                                            <?php } else { ?>
                                                                <i class="bi bi-whatsapp" style="color:#A2A2A2;" title="Send SMS"></i>
                                                            <?php } ?>

                                                            <?php if (!empty($array['email'])) { ?>
                                                                <form action="#" name="email-form-<?php echo $array['id']; ?>" id="email-form-<?php echo $array['id']; ?>" method="POST" style="display: inline-block;">

                                                                    <!-- Existing hidden fields -->
                                                                    <input type="hidden" name="template" value="exception_notify">
                                                                    <input type="hidden" name="data[id]" value="<?php echo $array['id']; ?>">
                                                                    <input type="hidden" name="data[submitted_by]" value="<?php echo $array['submitted_by']; ?>">
                                                                    <input type="hidden" name="data[fullname]" value="<?php echo $array['fullname']; ?>">
                                                                    <input type="hidden" name="data[exception_type]" value="<?php echo $array['exception_type']; ?>">
                                                                    <input type="hidden" name="data[reason]" value="<?php echo $array['reason']; ?>">
                                                                    <?php
                                                                    $dateTime = !empty($array['start_date_time']) ? $array['start_date_time'] : $array['end_date_time'];
                                                                    ?>
                                                                    <input type="hidden" name="data[date_time]" value="<?php echo @date("d/m/Y g:i a", strtotime($dateTime)); ?>">

                                                                    <!-- Additional hidden fields similar to the sample provided -->
                                                                    <input type="hidden" name="data[exception_status]" value="<?php echo @strtoupper($array['status']); ?>">
                                                                    <input type="hidden" name="data[reviewer_remarks]" value="<?php echo $array['reviewer_remarks']; ?>">

                                                                    <!-- Include email fields -->
                                                                    <input type="hidden" name="email" value="<?php echo $array['email']; ?>">

                                                                    <!-- Submit button to send email -->
                                                                    <button type="submit" style="background: none; border: none;">
                                                                        <i class="bi bi-envelope-at" style="color:#444444;" title="Send Email"></i>
                                                                    </button>
                                                                </form>
                                                            <?php } else { ?>
                                                                <i class="bi bi-envelope-at" style="color:#A2A2A2;" title="Send Email"></i>
                                                            <?php } ?>

                                                        </td>
                                                    <?php } ?>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr>
                                                <td colspan="10">No records found for the selected filter value.</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <!--------------- POP-UP BOX FOR EXCEPTION REQUEST UPDATE ------------ -------------------------------------->

                            <style>
                                .modal {
                                    background-color: rgba(0, 0, 0, 0.4);
                                    /* Black w/ opacity */
                                }
                            </style>

                            <div class="modal" id="myModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="exampleModalLabel">Exception Request Details</h1>
                                            <button type="button" id="closedetails-header" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">

                                            <div style="width:100%; text-align:right">
                                                <p id="status" class="badge" style="display: inline !important;"><span class="id"></span></p>
                                            </div>

                                            <!-- Update Form for Exception Request -->
                                            <form id="exceptionreviewform" name="exceptionreviewform" action="#" method="POST">
                                                <input type="hidden" class="form-control" name="form-type" value="exceptionreviewform" readonly>
                                                <input type="hidden" class="form-control" name="reviewer_id" id="reviewer_id" value="<?php echo $associatenumber ?>" readonly>
                                                <input type="hidden" class="form-control" name="exceptionid" id="exceptionid" readonly>

                                                <select name="exception_status" id="exception_status" class="form-select" required>
                                                    <option disabled selected hidden>Status</option>
                                                    <option value="Approved">Approved</option>
                                                    <option value="Under review">Under review</option>
                                                    <option value="Rejected">Rejected</option>
                                                </select>

                                                <div class="mb-3">
                                                    <label for="reviewer_remarks" class="form-label">Reviewer Remarks</label>
                                                    <textarea name="reviewer_remarks" id="reviewer_remarks" class="form-control" placeholder="Remarks"></textarea>
                                                </div>

                                                <br>
                                                <button type="submit" id="exceptionupdate" class="btn btn-danger btn-sm">Update</button>
                                            </form>
                                            <div class="modal-footer">
                                                <button type="button" id="closedetails-footer" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script>
                                var data = <?php echo json_encode($resultArr); ?>;

                                // Get the modal
                                var modal = document.getElementById("myModal");

                                // Get the elements that close the modal
                                var closedetails = [
                                    document.getElementById("closedetails-header"),
                                    document.getElementById("closedetails-footer")
                                ];

                                function showDetails(id) {
                                    var mydata = undefined;

                                    // Find the data item with the matching id
                                    data.forEach(item => {
                                        if (item["id"] == id) {
                                            mydata = item;
                                        }
                                    });

                                    if (!mydata) return; // Exit if no data found

                                    // Update modal content dynamically based on mydata
                                    var keys = Object.keys(mydata);
                                    keys.forEach(key => {
                                        var span = modal.getElementsByClassName(key);
                                        if (span.length > 0) {
                                            span[0].innerHTML = mydata[key];
                                        }
                                    });

                                    // Show the modal
                                    modal.style.display = "block";

                                    // Update status badge class based on status
                                    var status = document.getElementById("status");
                                    if (mydata["status"] === "Approved") {
                                        status.classList.add("bg-success");
                                        status.classList.remove("bg-danger");
                                    } else {
                                        status.classList.remove("bg-success");
                                        status.classList.add("bg-danger");
                                    }

                                    // Populate form fields with data
                                    document.getElementById("exceptionid").value = mydata["id"]; // Correctly set exceptionid
                                    if (mydata["status"] !== null) {
                                        document.getElementById("exception_status").value = mydata["status"];
                                    }

                                    // Correctly set reviewer remarks
                                    if (mydata["reviewer_remarks"] !== null && mydata["reviewer_remarks"] !== undefined) {
                                        document.getElementById("reviewer_remarks").value = mydata["reviewer_remarks"];
                                    } else {
                                        document.getElementById("reviewer_remarks").value = ""; // Ensure it's empty if not set
                                    }

                                    // Set approval date and disable update button if already approved
                                    if (mydata["status"] == 'Approved' || mydata["status"] == 'Rejected') {
                                        document.getElementById("exceptionupdate").disabled = true;
                                    } else {
                                        document.getElementById("exceptionupdate").disabled = false;
                                    }
                                }

                                // Close the modal when user clicks on the close buttons
                                closedetails.forEach(function(element) {
                                    element.addEventListener("click", closeModal);
                                });

                                function closeModal() {
                                    modal.style.display = "none";
                                }
                            </script>

                            <script>
                                var data = <?php echo json_encode($resultArr) ?>;
                                //For form submission - to update Remarks
                                const scriptURL = 'payment-api.php'

                                const form = document.getElementById('exceptionreviewform')
                                form.addEventListener('submit', e => {
                                    e.preventDefault()
                                    fetch(scriptURL, {
                                            method: 'POST',
                                            body: new FormData(document.getElementById('exceptionreviewform'))
                                        })
                                        .then(response => response.text())
                                        .then(result => {
                                            if (result === 'success') {
                                                alert("Record has been updated.");
                                                location.reload();
                                            } else {
                                                alert("Error updating record. Please try again later or contact support.");
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error!', error.message);
                                        });
                                });

                                data.forEach(item => {
                                    const formId = 'email-form-' + item.id
                                    const form = document.forms[formId]
                                    form.addEventListener('submit', e => {
                                        e.preventDefault()
                                        fetch('mailer.php', {
                                                method: 'POST',
                                                body: new FormData(document.forms[formId])
                                            })
                                            .then(response =>
                                                alert("Email has been sent.")
                                            )
                                            .catch(error => console.error('Error!', error.message))
                                    })
                                })
                            </script>

                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script>
        $(document).ready(function() {
            // Toggle full comment visibility on "more" link click
            $('.more-link').click(function(e) {
                e.preventDefault();
                var shortComment = $(this).siblings('.short-comment');
                var fullComment = $(this).siblings('.full-comment');
                if (fullComment.is(':visible')) {
                    // If full comment is visible, toggle to show short comment
                    shortComment.show();
                    fullComment.hide();
                    $(this).text('more');
                } else {
                    // If short comment is visible, toggle to show full comment
                    shortComment.hide();
                    fullComment.show();
                    $(this).text('less');
                }
            });
        });
    </script>
    <!-- Vendor JS Files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($resultArr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#table-id').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>

</body>

</html>