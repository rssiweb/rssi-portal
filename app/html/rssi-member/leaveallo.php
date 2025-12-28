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

include("../../util/email.php");

// Get the current timestamp
$now = date('Y-m-d H:i:s');

// Check if the user is an Admin
if ($role == "Admin") {

    // Check if the form type is 'leaveallocation'
    if (isset($_POST['form-type']) && $_POST['form-type'] == "leaveallocation") {

        // Assign POST variables with proper checks
        $leaveallocationid = 'RLA' . time();
        $allo_applicantid = isset($_POST['allo_applicantid']) ? strtoupper($_POST['allo_applicantid']) : null;
        $allo_daycount = isset($_POST['allo_daycount']) ? $_POST['allo_daycount'] : null;
        $allo_leavetype = isset($_POST['allo_leavetype']) ? $_POST['allo_leavetype'] : null;
        $allo_remarks = isset($_POST['allo_remarks']) ? $_POST['allo_remarks'] : null;
        $allocatedbyid = $associatenumber;
        $allo_academicyear = isset($_POST['allo_academicyear']) ? $_POST['allo_academicyear'] : null;

        // Check if the leave allocation ID is set
        if ($leaveallocationid != "") {

            // Insert into the leaveallocation table
            $leaveallocation = "
                INSERT INTO leaveallocation (leaveallocationid, allo_applicantid, allo_daycount, allo_leavetype, allo_remarks, allocatedbyid, allo_date, allo_academicyear) 
                VALUES ('$leaveallocationid', '$allo_applicantid', '$allo_daycount', '$allo_leavetype', '$allo_remarks', '$allocatedbyid', '$now', '$allo_academicyear')
            ";

            // Execute the query
            $result = pg_query($con, $leaveallocation);
            $cmdtuples = pg_affected_rows($result); // Check how many rows were affected
        }
    }
}

// Get and sanitize inputs, if they exist
$id = isset($_GET['leaveallocationid']) ? $_GET['leaveallocationid'] : null;
$appid = isset($_GET['allo_applicantid']) ? strtoupper($_GET['allo_applicantid']) : null;
// Determine the current academic year
$month = date('m');
$currentYear = date('Y');

if ($month == 1 || $month == 2 || $month == 3) {
    $currentYear -= 1; // If it's Jan, Feb, or Mar, use the previous year
}

$allo_academicyear = isset($_GET['allo_academicyear_search']) ? $_GET['allo_academicyear_search'] : $currentYear . '-' . ($currentYear + 1); // Set default to current academic year
$is_user = isset($_GET['is_user']) ? $_GET['is_user'] : null;

date_default_timezone_set('Asia/Kolkata');

// Initialize the base query and conditions array
$baseQuery = "SELECT * FROM leaveallocation 
              LEFT JOIN (SELECT associatenumber, fullname, email, phone FROM rssimyaccount_members) faculty 
              ON leaveallocation.allo_applicantid = faculty.associatenumber 
              LEFT JOIN (SELECT student_id, studentname, emailaddress, contact FROM rssimyprofile_student) student 
              ON leaveallocation.allo_applicantid = student.student_id";

// Initialize an array to hold the conditions
$conditions = [];

// For Admin users, allow filtering by applicant ID, academic year, and leave allocation ID
if ($role == "Admin" && $filterstatus == 'Active') {
    // Apply filters based on parameters passed
    if ($appid) {
        $conditions[] = "allo_applicantid = '$appid'";
    }
    if ($allo_academicyear) {
        $conditions[] = "allo_academicyear = '$allo_academicyear'";
    }
    if ($id) {
        $conditions[] = "leaveallocationid = '$id'";
    }
} else {
    // For non-Admin users, only fetch their own data
    $conditions[] = "allo_applicantid = '$associatenumber'"; // Ensure they see only their data

    // Optionally filter by academic year, if provided
    if ($allo_academicyear) {
        $conditions[] = "allo_academicyear = '$allo_academicyear'";
    }
    if ($id) {
        $conditions[] = "leaveallocationid = '$id'";
    }
}

// Build the final query by adding conditions if any
$query = $baseQuery;

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY allo_date DESC";

// Execute the query
$result = pg_query($con, $query);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}

// Fetch the result
$resultArr = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html>

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

    <title>Leave Allocation</title>

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
    <!------ Include the above in your HEAD tag ---------->

    <script src="https://cdn.jsdelivr.net/gh/manucaralmo/GlowCookies@3.0.1/src/glowCookies.min.js"></script>
    <!-- Glow Cookies v3.0.1 -->
    <script>
        glowCookies.start('en', {
            analytics: 'G-S25QWTFJ2S',
            //facebookPixel: '',
            policyLink: 'https://www.rssi.in/disclaimer'
        });
    </script>
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
            <h1>Leave Allocation</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">My Services</a></li>
                    <li class="breadcrumb-item"><a href="leave.php">Apply for Leave</a></li>
                    <li class="breadcrumb-item active">Leave Allocation</li>
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
                            <div class="container">
                                <?php if (@$leaveallocationid != null && @$cmdtuples == 0) { ?>
                                    <div class="alert alert-danger alert-dismissible text-center" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-exclamation-triangle"></i>
                                        <span>ERROR: Oops, something wasn't right.</span>
                                    </div>
                                <?php } else if (@$cmdtuples == 1) { ?>
                                    <div class="alert alert-success alert-dismissible text-center" role="alert">
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        <i class="bi bi-check2-circle"></i>
                                        <span>Your request has been submitted. Leave allocation id <?php echo $leaveallocationid ?>.</span>
                                    </div>
                                    <script>
                                        if (window.history.replaceState) {
                                            window.history.replaceState(null, null, window.location.href);
                                        }
                                    </script>
                                <?php } ?>


                                <!-- <section style="padding: 2%;"> -->
                                <?php if ($role == "Admin" && $filterstatus == 'Active') { ?>
                                    <p>Allocate Leave</p>

                                    <form autocomplete="off" name="leaveallocation" id="leaveallocation" action="leaveallo.php" method="POST" class="mb-5">
                                        <div class="form-group" style="display: inline-block;">

                                            <input type="hidden" name="form-type" type="text" value="leaveallocation">

                                            <span class="input-help">
                                                <input type="text" name="allo_applicantid" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo @$_GET['allo_applicantid']; ?>" required>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Applicant ID*</small>
                                            </span>

                                            <span class="input-help">
                                                <input type="number" name="allo_daycount" id='allo_daycount' class="form-control" placeholder="Day count" step="0.01" pattern="^\d+(?:\.\d{1,2})?$" required>
                                                <small id="passwordHelpBlock_allo_daycount" class="form-text text-muted">Allocated day*</small>
                                            </span>
                                            <span class="input-help">
                                                <select name="allo_leavetype" id="allo_leavetype" class="form-select" style="display: -webkit-inline-box; width:20vh; " required>
                                                    <option disabled selected hidden>Types of Leave</option>
                                                    <option value="Sick Leave">Sick Leave</option>
                                                    <option value="Casual Leave">Casual Leave</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Leave Type*</small>
                                            </span>
                                            <span class="input-help">
                                                <select name="allo_academicyear" id="allo_academicyear" class="form-select" style="display: -webkit-inline-box; width:20vh; " required>
                                                    <option disabled selected hidden>Academic Year</option>
                                                </select>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Academic Year</small>
                                            </span>

                                            <span class="input-help">
                                                <textarea type="text" name="allo_remarks" class="form-control" placeholder="Remarks" value=""></textarea>
                                                <small id="passwordHelpBlock" class="form-text text-muted">Remarks</small>
                                            </span>
                                        </div>
                                        <div class="col2 left" style="display: inline-block;">
                                            <button type="Submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">Add Leave</button>

                                        </div>

                                    </form>
                                <?php } ?>
                                <form action="" method="GET" class="mb-2">
                                    <div class="form-group" style="display: inline-block;">
                                        <div class="col2" style="display: inline-block;">
                                            <input name="leaveallocationid" id="leaveallocationid" class="form-control" style="width:max-content; display:inline-block" placeholder="Leave Allocation ID" value="<?php echo $id ?>">
                                            <?php if ($role == "Admin" && $filterstatus == 'Active') { ?>
                                                <input name="allo_applicantid" id="allo_applicantid" class="form-control" style="width:max-content; display:inline-block" placeholder="Applicant ID" value="<?php echo $appid ?>" required>
                                            <?php } ?>
                                            <select name="allo_academicyear_search" id="allo_academicyear_search" class="form-select" style="width:max-content; display:inline-block" placeholder="Appraisal type" required>
                                                <?php if ($allo_academicyear == null) { ?>
                                                    <option disabled selected hidden>Academic Year</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $allo_academicyear ?></option>
                                                <?php }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col2 left" style="display: inline-block;">
                                        <button type="submit" name="search_by_id" class="btn btn-success btn-sm" style="outline: none;">
                                            <i class="bi bi-search"></i>&nbsp;Search</button>
                                    </div>
                                    <?php if ($role == "Admin" && $filterstatus == 'Active') { ?>
                                        <div id="filter-checks" class="mt-2">
                                            <input class="form-check-input" type="checkbox" name="is_user" id="is_user" value="1" <?php if (isset($_GET['is_user'])) echo "checked='checked'"; ?> />
                                            <label for="is_user" style="font-weight: 400;">Search by Leave Allocation ID</label>
                                        </div>
                                    <?php } ?>
                                </form>

                                <div class="table-responsive">
                                    <table class="table" id="table-id">
                                        <thead>
                                            <tr>
                                                <th scope="col">Leave allocation id</th>
                                                <th scope="col">Applicant ID</th>
                                                <th scope="col">Applied on</th>
                                                <th scope="col">Allocated day(s)</th>
                                                <th scope="col">Allocated Leave Type</th>
                                                <th scope="col" width="15%">Remarks</th>
                                                <?php if ($role == "Admin" && $filterstatus == 'Active'): ?>
                                                    <th scope="col"></th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (sizeof($resultArr) > 0): ?>
                                                <?php foreach ($resultArr as $array): ?>
                                                    <tr>
                                                        <td><?php echo $array['leaveallocationid']; ?></td>
                                                        <td><?php echo $array['allo_applicantid']; ?><br><?php echo $array['fullname'] . $array['studentname']; ?></td>
                                                        <td><?php echo date("d/m/Y g:i a", strtotime($array['allo_date'])); ?></td>
                                                        <td><?php echo $array['allo_daycount']; ?></td>
                                                        <td><?php echo $array['allo_leavetype'] . '/' . $array['allo_academicyear']; ?></td>
                                                        <td><?php echo $array['allo_remarks']; ?></td>

                                                        <?php if ($role == "Admin" && $filterstatus == 'Active'): ?>
                                                            <td>
                                                                <form name="leaveallodelete_<?php echo $array['leaveallocationid']; ?>" action="#" method="POST" style="display: inline-block;">
                                                                    <input type="hidden" name="form-type" value="leaveallodelete">
                                                                    <input type="hidden" name="leaveallodeleteid" value="<?php echo $array['leaveallocationid']; ?>">
                                                                    <button type="submit" onclick="validateForm()" style="background: none; border: none; padding: 0; cursor: pointer;" title="Delete <?php echo $array['leaveallocationid']; ?>"><i class="bi bi-x-lg"></i></button>
                                                                </form>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php elseif ($id == null && $allo_academicyear == null): ?>
                                                <tr>
                                                    <td colspan="7">Please select Filter value.</td>
                                                </tr>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7">No record was found for the selected filter value.</td>
                                                </tr>
                                            <?php endif; ?>
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
        var data = <?php echo json_encode($resultArr) ?>;
        const scriptURL = 'payment-api.php'

        function validateForm() {
            if (confirm('Are you sure you want to delete this record? Once you click OK the record cannot be reverted.')) {

                data.forEach(item => {
                    const form = document.forms['leaveallodelete_' + item.leaveallocationid]
                    form.addEventListener('submit', e => {
                        e.preventDefault()
                        fetch(scriptURL, {
                                method: 'POST',
                                body: new FormData(document.forms['leaveallodelete_' + item.leaveallocationid])
                            })
                            .then(response =>
                                alert("Record has been deleted.") +
                                location.reload()
                            )
                            .catch(error => console.error('Error!', error.message))
                    })

                    console.log(item)
                })
            } else {
                alert("Record has NOT been deleted.");
                return false;
            }
        }
    </script>
    <script>
        if ($('#is_user').not(':checked').length > 0) {

            document.getElementById("leaveallocationid").disabled = true;
            document.getElementById("allo_applicantid").disabled = false;
            document.getElementById("allo_academicyear_search").disabled = false;

        } else {

            document.getElementById("leaveallocationid").disabled = false;
            document.getElementById("allo_applicantid").disabled = true;
            document.getElementById("allo_academicyear_search").disabled = true;

        }

        const checkbox = document.getElementById('is_user');

        checkbox.addEventListener('change', (event) => {
            if (event.target.checked) {
                document.getElementById("leaveallocationid").disabled = false;
                document.getElementById("allo_applicantid").disabled = true;
                document.getElementById("allo_academicyear_search").disabled = true
            } else {
                document.getElementById("leaveallocationid").disabled = true;
                document.getElementById("allo_applicantid").disabled = false;
                document.getElementById("allo_academicyear_search").disabled = false;
            }
        })
    </script>
    <script>
        // Calculate the current year based on the month
        <?php
        $currentYear = date('Y');
        if (date('m') == 1 || date('m') == 2 || date('m') == 3) {
            $currentYear -= 1;  // Adjust for academic year starting in previous year
        }
        ?>
        var currentYear = <?php echo $currentYear; ?>;

        // Function to populate year options in a dropdown
        function populateYearDropdown(dropdownId) {
            let tempYear = currentYear; // Use a temporary variable to preserve the original year value
            for (var i = 0; i < 5; i++) {
                var next = tempYear + 1;
                var year = tempYear + '-' + next;
                $(dropdownId).append(new Option(year, year));
                tempYear--;
            }
        }

        // Populate academic year dropdown for both dropdowns
        populateYearDropdown('#allo_academicyear_search'); // For search dropdown
        populateYearDropdown('#allo_academicyear'); // For another dropdown
    </script>

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