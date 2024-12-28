<?php
require_once __DIR__ . "/../../bootstrap.php";
include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

// Start output buffering to prevent header issues
ob_start();

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();

$associatenumber = isset($_GET['associatenumber']) ? $_GET['associatenumber'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize an array to store the parts of the UPDATE query
    $update_fields = [];

    // Check if each field is set and add to the update query
    if (isset($_POST['doj']) && !empty($_POST['doj'])) {
        $doj = pg_escape_string($con, $_POST['doj']); // Pass connection $con
        $update_fields[] = "doj = '$doj'";
    }
    if (isset($_POST['basebranch']) && !empty($_POST['basebranch'])) {
        $basebranch = pg_escape_string($con, $_POST['basebranch']); // Pass connection $con
        $update_fields[] = "basebranch = '$basebranch'";
    }
    if (isset($_POST['depb']) && !empty($_POST['depb'])) {
        $depb = pg_escape_string($con, $_POST['depb']); // Pass connection $con
        $update_fields[] = "depb = '$depb'";
    }
    if (isset($_POST['engagement']) && !empty($_POST['engagement'])) {
        $engagement = pg_escape_string($con, $_POST['engagement']); // Pass connection $con
        $update_fields[] = "engagement = '$engagement'";
    }
    if (isset($_POST['job_type']) && !empty($_POST['job_type'])) {
        $job_type = pg_escape_string($con, $_POST['job_type']); // Pass connection $con
        $update_fields[] = "job_type = '$job_type'";
    }
    if (isset($_POST['position']) && !empty($_POST['position'])) {
        $position = pg_escape_string($con, $_POST['position']); // Pass connection $con
        $update_fields[] = "position = '$position'";
    }
    if (isset($_POST['class']) && !empty($_POST['class'])) {
        $class = pg_escape_string($con, $_POST['class']); // Pass connection $con
        $update_fields[] = "class = '$class'";
    }
    // Check if 'position' is set in the POST data
    if (isset($_POST['position']) && !empty($_POST['position'])) {
        // Get the position value from POST
        $position = $_POST['position'];

        // Query the database to get the grade for the selected position
        $query = "SELECT grade FROM designation WHERE designation = $1 AND is_inactive = FALSE";
        $result = pg_query_params($con, $query, array($position));

        // Check if a result was returned
        if ($result && pg_num_rows($result) > 0) {
            // Fetch the grade for the position
            $row = pg_fetch_assoc($result);
            $grade = $row['grade'];
        } else {
            // If no grade is found, set grade to empty
            $grade = '';
        }

        // Now, you can use the grade value in your update query
        if (!empty($grade)) {
            $grade = pg_escape_string($con, $grade); // Escape the grade to prevent SQL injection
            $update_fields[] = "grade = '$grade'"; // Add grade to update fields array
        }
    }
    if (isset($_POST['role']) && !empty($_POST['role'])) {
        $role = pg_escape_string($con, $_POST['role']); // Pass connection $con
        $update_fields[] = "role = '$role'";
    }
    if (isset($_POST['immediate_manager']) && !empty($_POST['immediate_manager'])) {
        $immediate_manager = pg_escape_string($con, $_POST['immediate_manager']); // Pass connection $con

        // Check if immediate_manager is same as associatenumber
        if ($immediate_manager == $associatenumber) {
            // If the immediate manager is the same as the associate, show an alert
            echo "<script>alert('An associate cannot be their own immediate supervisor. Please choose another manager from the list.');</script>";
        } else {
            // If valid, add to update fields array
            $update_fields[] = "immediate_manager = '$immediate_manager'";
        }
    }
    if (isset($_POST['filterstatus']) && !empty($_POST['filterstatus'])) {
        $filterstatus = pg_escape_string($con, $_POST['filterstatus']); // Pass connection $con
        $update_fields[] = "filterstatus = '$filterstatus'";
    }
    if (isset($_POST['scode']) && !empty($_POST['scode'])) {
        $scode = pg_escape_string($con, $_POST['scode']); // Pass connection $con
        $update_fields[] = "scode = '$scode'";
    }
    if (isset($_POST['salary']) && !empty($_POST['salary'])) {
        $salary = pg_escape_string($con, $_POST['salary']); // Pass connection $con
        $update_fields[] = "salary = '$salary'";
    }
    if (isset($_POST['abscond'])) {
        $absconds = pg_escape_string($con, $_POST['abscond']); // Escape value for SQL safety
        $update_fields[] = "absconding = '$absconds'";
    }

    // If there are fields to update, build the query and execute it
    if (!empty($update_fields)) {
        $update_sql = "UPDATE rssimyaccount_members SET " . implode(", ", $update_fields) . " WHERE associatenumber = '$associatenumber'";

        // Execute the update query
        $update_result = pg_query($con, $update_sql);

        if ($update_result) {
            // If the update was successful, show an alert and then redirect
            echo "<script>
                alert('Information updated successfully.');
                if (window.history.replaceState) {
                        // Update the URL without causing a page reload or resubmission
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload(); // Trigger a page reload to reflect changes
            </script>";
            exit;
        } else {
            // If there's an error during the update, show an alert
            echo "<script>
                alert('An error occurred while updating the data.');
                window.history.back();
            </script>";
            exit;
        }
    }
}

// End output buffering and send the output
ob_end_flush();
?>
<?php
// Step 1: Fetch current associate data (this part remains the same)
$sql = "SELECT * FROM rssimyaccount_members WHERE associatenumber='$associatenumber'";
$result = pg_query($con, $sql);
$resultArr = pg_fetch_all($result);

// Check if there are any results for the associate
if ($resultArr && count($resultArr) > 0) {
    $associate_email = $resultArr[0]['email'];
    $associate_name = $resultArr[0]['fullname'];
    $associatenumber = $resultArr[0]['associatenumber'];
    $associate_telephone = $resultArr[0]['phone'];
} else {
    echo "An error occurred while fetching the associate data.";
    exit;
}

// Step 2: Fetch active managers data
$sql_managers = "SELECT associatenumber, fullname FROM rssimyaccount_members WHERE filterstatus = 'Active'";
$result_managers = pg_query($con, $sql_managers);

// Check if there are any active managers
if ($result_managers && pg_num_rows($result_managers) > 0) {
    $managersArr = pg_fetch_all($result_managers);
} else {
    echo "No active managers found.";
    exit;
}

// Close the result resource
pg_free_result($result);
pg_free_result($result_managers);
?>
<?php
// SQL query to fetch active roles and grades
$sql = "SELECT designation, grade FROM designation WHERE is_inactive = FALSE ORDER BY designation ASC";
$result = pg_query($con, $sql);

// Check if the query was successful
if ($result) {
    $roles = pg_fetch_all($result);
} else {
    echo "An error occurred while fetching roles.";
    exit;
}
?>
<?php
// Query the database to get the available roles (excluding inactive roles)
$query = "SELECT role_name FROM roles WHERE is_inactive = FALSE";
$result = pg_query($con, $query);

// Generate the role options
$role_options = [];
if ($result && pg_num_rows($result) > 0) {
    while ($row = pg_fetch_assoc($result)) {
        $role_options[] = $row['role_name'];
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

    <title>Applicant_Profile_<?php echo $associatenumber; ?></title>

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
    <!-- Add Select2 Script -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script> -->

</head>

<body>

    <?php include 'header.php'; ?>
    <?php include 'inactive_session_expire_check.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>People Manage</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item">Work</li>
                    <li class="breadcrumb-item">People Manage</li>
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
                            <?php
                            // If no application number is provided, show the input form
                            if (!$associatenumber): ?>
                                <div class="container mt-5">
                                    <h4 class="mb-3">Enter Application Number</h4>
                                    <form method="GET" action="">
                                        <div class="input-group mb-3">
                                            <input type="text" name="associatenumber" class="form-control" placeholder="Enter Application Number" required>
                                            <button class="btn btn-primary" type="submit">Submit</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                            <?php foreach ($resultArr as $array) { ?>
                                <div class="container">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <tr>
                                                <!-- Left Column (Application Details) -->
                                                <td style="width: 50%; vertical-align: top;">
                                                    <table>
                                                        <tr>
                                                            <td><label for="associatenumber">Associate Number:</label></td>
                                                            <td><?php echo $array["associatenumber"] ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label for="fullname">Associate Name:</label></td>
                                                            <td><?php echo $array["fullname"] ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label for="dateofbirth">Date of Birth:</label></td>
                                                            <td><?php echo $array["dateofbirth"] ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><label for="gender">Gender:</label></td>
                                                            <td><?php echo $array["gender"] ?></td>
                                                        </tr>
                                                    </table>
                                                </td>

                                                <!-- Right Column (Associate Photo) -->
                                                <td style="width: 50%; vertical-align: top; text-align: center;">
                                                    <div class="photo-box mt-2" style="border: 1px solid #ccc; padding: 10px; width: 150px; height: 200px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                                        <?php
                                                        if (!empty($array['photo'])) {
                                                            $preview_url = $array['photo'];
                                                            echo '<img src="' . $preview_url . '" width="150" height="200" ></img>';
                                                        } else {
                                                            echo "No photo available";
                                                        }
                                                        ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    <form name="signup" id="signup" action="#" method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="form-type" value="signup">
                                        <input type="hidden" name="associatenumber" value="<?php echo $associatenumber; ?>">

                                        <fieldset>
                                            <table class="table">
                                                <tbody>
                                                    <!-- Telephone Number -->
                                                    <tr>
                                                        <td><label for="phone">Telephone Number:</label></td>
                                                        <td>
                                                            <?php echo $array["phone"] ?>
                                                        </td>
                                                    </tr>
                                                    <!-- Email Address -->
                                                    <tr>
                                                        <td><label for="email">Email Address:</label></td>
                                                        <td>
                                                            <?php echo $array["email"] ?>
                                                        </td>
                                                    </tr>
                                                    <!-- Current Address -->
                                                    <tr>
                                                        <td><label for="currentaddress">Current Address:</label></td>
                                                        <td>
                                                            <?php echo $array["currentaddress"] ?>
                                                        </td>
                                                    </tr>
                                                    <!-- Permanent Address -->
                                                    <tr>
                                                        <td><label for="permanentaddress">Permanent Address:</label></td>
                                                        <td>
                                                            <?php echo $array["permanentaddress"] ?>
                                                        </td>
                                                    </tr>
                                                    <!-- Educational Qualification -->
                                                    <tr>
                                                        <td><label for="eduq">Educational Qualification:</label></td>
                                                        <td>
                                                            <?php echo $array["eduq"] ?>
                                                        </td>
                                                    </tr>
                                                    <!-- Area of Specialization -->
                                                    <tr>
                                                        <td><label for="mjorsub">Area of Specialization:</label></td>
                                                        <td>
                                                            <?php echo $array["mjorsub"] ?>
                                                        </td>
                                                    </tr>
                                                    <!-- Work Experience -->
                                                    <tr>
                                                        <td><label for="workexperience">Work Experience:</label></td>
                                                        <td>
                                                            <?php echo $array["workexperience"] ?>
                                                        </td>
                                                    </tr>
                                                    <!-- National Identifier -->
                                                    <tr>
                                                        <td><label for="identifier">National Identifier Number:</label></td>
                                                        <td>
                                                            <?php echo $array["nationalidentifier"] ?><br>
                                                            <?php echo $array["identifier"] ?>
                                                        </td>
                                                    </tr>
                                                    <!-- PAN Number -->
                                                    <tr>
                                                        <td><label for="panno">PAN Number:</label></td>
                                                        <td>
                                                            <?php echo $array["panno"] ?>
                                                        </td>
                                                    </tr>
                                                    <!-- Date of Join -->
                                                    <tr>
                                                        <td><label for="doj">Date of Join:</label></td>
                                                        <td class="d-flex align-items-center">
                                                            <input type="date" name="doj" id="doj" value="<?php echo $array["doj"]; ?>" disabled class="form-control">
                                                            <input type="checkbox" name="edit_fields[]" id="edit_doj" value="doj" class="ms-2">
                                                            <label class="form-check-label" for="edit_doj">Edit</label>
                                                        </td>
                                                    </tr>
                                                    <!-- Base Branch -->
                                                    <tr>
                                                        <td><label for="basebranch">Base Branch:</label></td>
                                                        <td class="d-flex align-items-center">
                                                            <!-- Base Branch Dropdown -->
                                                            <select name="basebranch" id="basebranch" disabled class="form-select">
                                                                <option disabled selected>Select Base Branch</option>
                                                                <?php
                                                                // List of Base Branches
                                                                $base_branches = [
                                                                    "Lucknow",
                                                                    "West Bengal"
                                                                ];

                                                                // Generate <option> elements dynamically for Base Branch
                                                                foreach ($base_branches as $branch) {
                                                                    $selected = ($array["basebranch"] == $branch) ? "selected" : "";
                                                                    echo "<option value=\"$branch\" $selected>$branch</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                            <!-- Checkbox to Enable Editing -->
                                                            <input type="checkbox" name="edit_fields[]" id="edit_basebranch" value="basebranch" class="ms-2">
                                                            <label class="form-check-label" for="edit_basebranch">Edit</label>
                                                        </td>
                                                    </tr>

                                                    <!-- Deputed Branch -->
                                                    <tr>
                                                        <td><label for="depb">Deputed Branch:</label></td>
                                                        <td class="d-flex align-items-center">
                                                            <!-- Deputed Branch Dropdown -->
                                                            <select name="depb" id="depb" disabled class="form-select">
                                                                <option disabled selected>Select Deputed Branch</option>
                                                                <?php
                                                                // List of Deputed Branches
                                                                $deputed_branches = [
                                                                    "Lucknow",
                                                                    "West Bengal"
                                                                ];

                                                                // Generate <option> elements dynamically for Deputed Branch
                                                                foreach ($deputed_branches as $branch) {
                                                                    $selected = ($array["depb"] == $branch) ? "selected" : "";
                                                                    echo "<option value=\"$branch\" $selected>$branch</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                            <!-- Checkbox to Enable Editing -->
                                                            <input type="checkbox" name="edit_fields[]" id="edit_depb" value="depb" class="ms-2">
                                                            <label class="form-check-label" for="edit_depb">Edit</label>
                                                        </td>
                                                    </tr>

                                                    <!-- Type of Association -->
                                                    <tr>
                                                        <td><label for="engagement">Type of Association:</label></td>
                                                        <td class="d-flex align-items-center">
                                                            <!-- Type of Association Dropdown -->
                                                            <select name="engagement" id="engagement" disabled class="form-select">
                                                                <option disabled selected>Select Type of Association</option>
                                                                <?php
                                                                // List of Engagement Types
                                                                $engagement_types = [
                                                                    "Employee",
                                                                    "Volunteer",
                                                                    "Intern",
                                                                    "Member"
                                                                ];

                                                                // Generate <option> elements dynamically
                                                                foreach ($engagement_types as $type) {
                                                                    $selected = ($array["engagement"] == $type) ? "selected" : "";
                                                                    echo "<option value=\"$type\" $selected>$type</option>";
                                                                }
                                                                ?>
                                                            </select>

                                                            <!-- Checkbox to Enable Editing -->
                                                            <input type="checkbox" name="edit_fields[]" id="edit_engagement" value="engagement" class="ms-2">
                                                            <label class="form-check-label" for="edit_engagement">Edit</label>
                                                        </td>
                                                    </tr>

                                                    <!-- Job Type -->
                                                    <tr>
                                                        <td><label for="job_type">Job Type:</label></td>
                                                        <td class="d-flex align-items-center">
                                                            <!-- Job Type Dropdown -->
                                                            <select name="job_type" id="job_type" disabled class="form-select">
                                                                <option disabled selected>Select Job Type</option>
                                                                <?php
                                                                // List of Job Types
                                                                $job_types = [
                                                                    "Full-time",
                                                                    "Part-time",
                                                                    "Contractual",
                                                                    "Voluntary"
                                                                ];

                                                                // Generate <option> elements dynamically
                                                                foreach ($job_types as $type) {
                                                                    $selected = ($array["job_type"] == $type) ? "selected" : "";
                                                                    echo "<option value=\"$type\" $selected>$type</option>";
                                                                }
                                                                ?>
                                                            </select>

                                                            <!-- Checkbox to Enable Editing -->
                                                            <input type="checkbox" name="edit_fields[]" id="edit_job_type" value="job_type" class="ms-2">
                                                            <label class="form-check-label" for="edit_job_type">Edit</label>
                                                        </td>
                                                    </tr>

                                                    <!-- Designation and Grade -->
                                                    <tr>
                                                        <td><label for="position">Designation:</label></td>
                                                        <td class="d-flex align-items-center">
                                                            <!-- Position Dropdown -->
                                                            <select name="position" id="position" class="form-select" disabled>
                                                                <option disabled selected>Select Designation</option>
                                                                <?php
                                                                if ($roles) {
                                                                    foreach ($roles as $role) {
                                                                        $selected = ($array["position"] == $role['designation']) ? "selected" : "";
                                                                        echo "<option value=\"{$role['designation']}\" $selected>{$role['designation']}</option>";
                                                                    }
                                                                }
                                                                ?>
                                                            </select>
                                                            <!-- Checkbox to Enable Editing -->
                                                            <input type="checkbox" name="edit_fields[]" id="edit_position" value="position" class="ms-2">
                                                            <label class="form-check-label" for="edit_position">Edit</label>
                                                        </td>
                                                    </tr>
                                                    <!-- Grade -->
                                                    <tr>
                                                        <td><label for="panno">Grade:</label></td>
                                                        <td>
                                                            <?php echo $array["grade"] ?>
                                                        </td>
                                                    </tr>

                                                    <!-- Work Mode -->
                                                    <tr>
                                                        <td><label for="class">Work Mode:</label></td>
                                                        <td class="d-flex align-items-center">
                                                            <!-- Work Mode Dropdown -->
                                                            <select name="class" id="class" disabled class="form-select">
                                                                <option disabled selected>Select Work Mode</option>
                                                                <?php
                                                                // List of Work Mode Options
                                                                $work_modes = [
                                                                    "Online",
                                                                    "Offline",
                                                                    "Hybrid"
                                                                ];

                                                                // Generate <option> elements dynamically
                                                                foreach ($work_modes as $mode) {
                                                                    $selected = ($array["class"] == $mode) ? "selected" : "";
                                                                    echo "<option value=\"$mode\" $selected>$mode</option>";
                                                                }
                                                                ?>
                                                            </select>

                                                            <!-- Checkbox to Enable Editing -->
                                                            <input type="checkbox" name="edit_fields[]" id="edit_class" value="class" class="ms-2">
                                                            <label class="form-check-label" for="edit_class">Edit</label>
                                                        </td>
                                                    </tr>

                                                    <!-- Access Role -->
                                                    <tr>
                                                        <td><label for="role">Access Role:</label></td>
                                                        <td class="d-flex align-items-center">
                                                            <!-- Access Role Dropdown -->
                                                            <select name="role" id="role" disabled class="form-select">
                                                                <option disabled selected>Select Access Role</option>
                                                                <?php
                                                                // Generate <option> elements dynamically from the roles fetched from the database
                                                                foreach ($role_options as $role) {
                                                                    $selected = ($array["role"] == $role) ? "selected" : "";
                                                                    echo "<option value=\"$role\" $selected>$role</option>";
                                                                }
                                                                ?>
                                                            </select>

                                                            <!-- Checkbox to Enable Editing -->
                                                            <input type="checkbox" name="edit_fields[]" id="edit_role" value="role" class="ms-2">
                                                            <label class="form-check-label" for="edit_role">Edit</label>
                                                        </td>
                                                    </tr>

                                                    <!-- HTML for Immediate Manager Dropdown -->
                                                    <tr>
                                                        <td><label for="immediate_manager">Immediate Manager:</label></td>
                                                        <td class="d-flex align-items-center">
                                                            <select name="immediate_manager" id="immediate_manager" class="form-select" disabled>
                                                                <option disabled selected>Select Immediate Manager</option>
                                                                <?php
                                                                // Populate dropdown with active managers
                                                                foreach ($managersArr as $manager) {
                                                                    $selected = ($manager['associatenumber'] == @$array['immediate_manager']) ? "selected" : "";
                                                                    echo "<option value=\"{$manager['associatenumber']}\" $selected>{$manager['fullname']} - {$manager['associatenumber']}</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                            <!-- Checkbox to Enable Editing -->
                                                            <input type="checkbox" name="edit_fields[]" id="edit_immediate_manager" value="immediate_manager" class="ms-2">
                                                            <label class="form-check-label" for="edit_immediate_manager">Edit</label>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><label for="filterstatus">Status:</label></td>
                                                        <td class="d-flex align-items-center">
                                                            <!-- Status Dropdown -->
                                                            <select name="filterstatus" id="filterstatus" disabled class="form-select">
                                                                <option disabled selected>Select Status</option>
                                                                <?php
                                                                // List of Status Options
                                                                $status_options = [
                                                                    "Active",
                                                                    "Inactive",
                                                                    "In Progress"
                                                                ];

                                                                // Generate <option> elements dynamically
                                                                foreach ($status_options as $status) {
                                                                    $selected = ($array["filterstatus"] == $status) ? "selected" : "";
                                                                    echo "<option value=\"$status\" $selected>$status</option>";
                                                                }
                                                                ?>
                                                            </select>

                                                            <!-- Checkbox to Enable Editing -->
                                                            <input type="checkbox" name="edit_fields[]" id="edit_filterstatus" value="filterstatus" class="ms-2">
                                                            <label class="form-check-label" for="edit_filterstatus">Edit</label>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td><label for="scode">Scode:</label></td>
                                                        <td class="d-flex align-items-center">
                                                            <input type="text" name="scode" id="scode" value="<?php echo $array["scode"]; ?>" disabled class="form-control">
                                                            <input type="checkbox" name="edit_fields[]" id="edit_scode" value="scode" class="ms-2">
                                                            <label class="form-check-label" for="edit_scode">Edit</label>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><label for="salary">Current Gross Salary (Monthly):</label></td>
                                                        <td class="d-flex align-items-center">
                                                            <input type="text" name="salary" id="salary" value="<?php echo $array["salary"]; ?>" disabled class="form-control">
                                                            <input type="checkbox" name="edit_fields[]" id="edit_salary" value="salary" class="ms-2">
                                                            <label class="form-check-label" for="edit_salary">Edit</label>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><label for="abscond">Abscond:</label></td>
                                                        <td class="d-flex align-items-center">
                                                            <!-- Checkbox for Abscond -->
                                                            <input type="checkbox" name="abscond" id="abscond" value="Y"
                                                                <?php echo ($array["absconding"] == "Y") ? "checked" : ""; ?>
                                                                disabled class="form-check-input">
                                                            <label class="form-check-label ms-2" for="abscond">Yes</label>

                                                            <!-- Checkbox to Enable Editing -->
                                                            <input type="checkbox" name="edit_fields[]" id="edit_abscond" value="abscond" class="ms-2">
                                                            <label class="form-check-label" for="edit_abscond">Edit</label>
                                                        </td>
                                                    </tr>
                                                    <!-- Submit Button -->
                                                    <tr>
                                                        <td colspan="2" style="text-align: center;">
                                                            <button type="button" class="btn btn-primary" id="editBtn" disabled>Enable Edits</button>
                                                            <button type="submit" class="btn btn-success" id="submitBtn" style="display:none;">Submit Changes</button>
                                                            <button type="button" class="btn btn-secondary" id="returnBtn" style="display:none;">Return to Selection</button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </fieldset>

                                    </form>

                                    <script>
                                        // Enable "Enable Edits" button if any checkbox is selected
                                        const checkboxes = document.querySelectorAll('input[name="edit_fields[]"]');
                                        const editBtn = document.getElementById('editBtn');
                                        const submitBtn = document.getElementById('submitBtn');
                                        const returnBtn = document.getElementById('returnBtn');

                                        checkboxes.forEach(function(checkbox) {
                                            checkbox.addEventListener('change', function() {
                                                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                                                editBtn.disabled = !anyChecked;
                                            });
                                        });

                                        // Handle "Enable Edits" button click
                                        editBtn.addEventListener('click', function() {
                                            const selectedCheckboxes = document.querySelectorAll('input[name="edit_fields[]"]:checked');

                                            // Enable the selected fields for editing
                                            selectedCheckboxes.forEach(function(checkbox) {
                                                const field = document.getElementById(checkbox.value);
                                                if (field) {
                                                    field.disabled = false;
                                                }
                                            });

                                            // Hide the "Enable Edits" button and show the "Submit Changes" button
                                            submitBtn.style.display = 'inline-block';
                                            editBtn.style.display = 'none';

                                            // Disable all checkboxes
                                            checkboxes.forEach(function(checkbox) {
                                                checkbox.disabled = true;
                                            });

                                            // Show the "Return to Selection" button
                                            returnBtn.style.display = 'inline-block';
                                        });

                                        // Handle "Return to Selection" button click
                                        returnBtn.addEventListener('click', function() {
                                            // Re-enable all checkboxes but keep their checked state
                                            checkboxes.forEach(function(checkbox) {
                                                checkbox.disabled = false;
                                            });

                                            // Reset all fields to disabled
                                            document.querySelectorAll('input[type="text"], input[type="email"], input[type="number"], input[type="date"], select').forEach(function(field) {
                                                field.disabled = true;
                                            });

                                            // Hide the "Submit Changes" button and show the "Enable Edits" button
                                            submitBtn.style.display = 'none';
                                            editBtn.style.display = 'inline-block';

                                            // Hide the "Return to Selection" button
                                            returnBtn.style.display = 'none';
                                        });
                                    </script>
                                </div>
                            <?php } ?>
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
    <script>
        function checkTelephoneLength() {
            var telephone = document.getElementById('telephone').value;

            // Limit the input to 10 digits
            if (telephone.length > 10) {
                alert("You can only enter up to 10 digits.");
                document.getElementById('telephone').value = telephone.slice(0, 10); // Truncate to 10 digits
            }
        }
    </script>
    <script>
        $(document).ready(function() {
            $('input, select, textarea').each(function() {
                if ($(this).prop('required')) { // Check if the element has the required attribute
                    $(this).closest('td').prev('td').find('label').append(' <span style="color: red">*</span>');
                }
            });
        });
    </script>
    <script>
        function copyAddress() {
            const currentAddress = document.getElementById('postal-address').value;
            const permanentAddressField = document.getElementById('permanent-address');
            const sameAddressCheckbox = document.getElementById('same-address');

            if (sameAddressCheckbox.checked) {
                permanentAddressField.value = currentAddress; // Copy current address to permanent address
                permanentAddressField.readOnly = true; // Make it read-only when checkbox is checked
            } else {
                permanentAddressField.value = ''; // Clear permanent address when checkbox is unchecked
                permanentAddressField.readOnly = false; // Make it editable again
            }
        }
    </script>
</body>

</html>