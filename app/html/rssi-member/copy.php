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
    $updated_fields = []; // To track which fields were updated

    // Fetch existing values for the associatenumber
    $query = "SELECT * FROM rssimyaccount_members WHERE associatenumber = $1";
    $result = pg_query_params($con, $query, [$associatenumber]);

    if ($result && pg_num_rows($result) > 0) {
        $current_data = pg_fetch_assoc($result);

        // List of fields to check and update
        $fields_to_check = [
            'doj',
            'basebranch',
            'depb',
            'engagement',
            'job_type',
            'position',
            'class',
            'role',
            'immediate_manager',
            'filterstatus',
            'scode',
            'salary',
            'absconding',
            'shift'
        ];

        foreach ($fields_to_check as $field) {
            if (isset($_POST[$field])) {
                $new_value = pg_escape_string($con, $_POST[$field]);
                $current_value = $current_data[$field];

                // Compare new value with current value
                if ($new_value !== $current_value) {
                    $update_fields[] = "$field = '$new_value'";
                    $updated_fields[] = $field; // Track updated fields
                }
            }
        }

        // Special handling for 'position' to get and compare the grade
        if (isset($_POST['position'])) {
            $position = pg_escape_string($con, $_POST['position']);
            $query = "SELECT grade FROM designation WHERE designation = $1 AND is_inactive = FALSE";
            $result = pg_query_params($con, $query, [$position]);

            if ($result && pg_num_rows($result) > 0) {
                $row = pg_fetch_assoc($result);
                $grade = $row['grade'];
                if ($grade !== $current_data['grade']) {
                    $update_fields[] = "grade = '$grade'";
                    $updated_fields[] = "grade"; // Track updated fields
                }
            }
        }
    }

    // If there are fields to update, build the query and execute it
    if (!empty($update_fields)) {
        $update_sql = "UPDATE rssimyaccount_members SET " . implode(", ", $update_fields) . " WHERE associatenumber = '$associatenumber'";

        // Execute the update query
        $update_result = pg_query($con, $update_sql);

        if ($update_result) {
            // Inform the user about the updated fields
            $updated_fields_list = implode(", ", $updated_fields);
            echo "<script>
                alert('The following fields were updated: $updated_fields_list');
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, window.location.href);
                }
                window.location.reload();
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
    } else {
        // No fields to update
        echo "<script>
            alert('No changes were made.');
            window.history.back();
        </script>";
        exit;
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
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile UI</title>
    <!-- Favicons -->
    <link href="../img/favicon.ico" rel="icon">
    <!-- Vendor CSS Files -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .header_two {
            /* background-color: #31536C; */
            /* color: white; */
            padding: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            /* Allows wrapping on small screens */
        }

        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #d9d9d9;
            margin-right: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #fff;
        }

        .profile-img-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .initials {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
        }

        .primary-details {
            flex-grow: 1;
        }

        .primary-details p {
            margin: 5px 0;
        }

        .contact-info {
            text-align: right;
        }

        .contact-info p {
            margin: 0;
        }

        .sidebar_two {
            min-width: 250px;
            background-color: #ffffff;
            border-right: 1px solid #d9d9d9;
            padding-top: 20px;
            height: 100vh;
        }

        #sidebar_two .nav-link {
            color: #31536C;
            /* font-weight: bold; */
            padding: 10px 20px;
        }

        #sidebar_two .nav-link:hover,
        #sidebar_two .nav-link.active {
            background-color: #e7f1ff;
            border-left: 4px solid #31536C;
            color: #31536C;
        }

        .content {
            flex-grow: 1;
            background-color: white;
            padding: 20px;
        }

        .card {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #f8f9fa;
            color: black;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        .edit-icon,
        .save-icon {
            color: #d3d3d3;
            /* Light flat gray color */
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .edit-icon:hover,
        .save-icon:hover {
            color: #a0a0a0;
            /* Darker gray on hover for a subtle effect */
        }

        /* Media Queries */

        /* Mobile View */
        @media (max-width: 768px) {
            .header_two {
                flex-direction: column;
                align-items: flex-start;
            }

            .profile-img {
                width: 60px;
                height: 60px;
                margin-right: 10px;
            }

            .contact-info {
                text-align: left;
            }

            .menu-icon {
                display: block;
                font-size: 1.5rem;
                color: #005b96;
                cursor: pointer;
            }

            .content {
                padding: 15px;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            /* Hide sidebar and display menu button on mobile */
            .sidebar_two {
                display: none;
            }

            .offcanvas {
                width: 75%;
            }

            .btn-link {
                text-decoration: none;
                margin: 10px;
            }

            .offcanvas-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
        }
    </style>

    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
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
                                <div class="container-fluid">

                                    <!-- Accordion for Mobile (Visible Only on Small Screens) -->
                                    <div class="d-md-none accordion" id="mobileAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingMenu">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#menuCollapse" aria-expanded="false" aria-controls="menuCollapse">
                                                    Menu
                                                </button>
                                            </h2>
                                            <div id="menuCollapse" class="accordion-collapse collapse" aria-labelledby="headingMenu" data-bs-parent="#mobileAccordion">
                                                <div class="accordion-body">
                                                    <ul id="mobile-menu-items" class="nav flex-column">
                                                        <!-- Menu items will be injected here dynamically -->
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Header -->
                                    <div class="header_two">
                                        <div class="profile-img">
                                            <?php
                                            if (!empty($array['photo'])) {
                                                $preview_url = $array['photo'];
                                                echo '<img src="' . $preview_url . '" alt="Profile Image" class="profile-img-img">';
                                            } else {
                                                $name = $array['fullname'];
                                                $initials = strtoupper(substr($name, 0, 1) . substr(strrchr($name, ' '), 1, 1)); // Get initials
                                                echo '<span class="initials">' . $initials . '</span>';
                                            }
                                            ?>
                                        </div>

                                        <div class="primary-details">
                                            <p style="font-size: large;"><?php echo $array["fullname"] ?></p>
                                            <p><?php echo $array["associatenumber"] ?><br><?php echo $array["engagement"] ?><br>Designation: <?php echo $array["position"] ?></p>
                                        </div>
                                        <div class="contact-info">
                                            <p><?php echo $array["phone"] ?></p>
                                            <p><?php echo $array["basebranch"] ?></p>
                                            <p><?php echo $array["email"] ?></p>
                                        </div>
                                    </div>

                                    <!-- Main Layout -->
                                    <div class="d-flex">
                                        <!-- sidebar_two -->
                                        <!-- Container for Menu Items (Written Only Once) -->
                                        <ul id="menu-items" class="d-none">
                                            <li class="nav-item">
                                                <a class="nav-link active" href="#employee-details" data-bs-toggle="tab">Employee Details</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" href="#work-details" data-bs-toggle="tab">Work Details</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" href="#learnings" data-bs-toggle="tab">Learnings</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" href="#published-documents" data-bs-toggle="tab">Published Documents</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" href="#social" data-bs-toggle="tab">Social</a>
                                            </li>
                                        </ul>

                                        <!-- Sidebar for Desktop (Visible Only on Medium and Larger Screens) -->
                                        <div class="sidebar_two d-none d-md-block" id="sidebar_two">
                                            <ul class="nav flex-column" id="sidebar-menu">
                                                <!-- Menu items will be inserted here by JavaScript -->
                                            </ul>
                                        </div>

                                        <!-- Content Area -->

                                        <div class="content tab-content container-fluid">
                                            <form name="signup" id="signup" action="#" method="post" enctype="multipart/form-data">
                                                <fieldset>
                                                    <!-- Employee Details Tab -->
                                                    <div id="employee-details" class="tab-pane active" role="tabpanel">
                                                        <div class="card" id="address_details">
                                                            <div class="card-header">
                                                                Address Details
                                                                <!-- <span class="edit-icon" onclick="toggleEdit('address_details')"><i class="bi bi-pencil"></i></span>
                                                                <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                    <i class="bi bi-save"></i>
                                                                </span> -->
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td>Telephone Number:</td>
                                                                                <td>
                                                                                    <span id="phoneText"><?php echo $array['phone']; ?></span>
                                                                                    <!-- <input type="text" name="phone" id="phone" value="<?php echo $array['phone']; ?>" disabled style="display:none;"> -->
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Email Address:</td>
                                                                                <td>
                                                                                    <span id="emailText"><?php echo $array['email']; ?></span>
                                                                                    <!-- <input type="email" name="email" id="email" value="<?php echo $array['email']; ?>" disabled style="display:none;"> -->
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Current Address:</td>
                                                                                <td>
                                                                                    <span id="currentAddressText"><?php echo $array['currentaddress']; ?></span>
                                                                                    <!-- <input type="text" name="currentaddress" id="currentaddress" value="<?php echo $array['currentaddress']; ?>" disabled style="display:none;"> -->
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>Permanent Address:</td>
                                                                                <td>
                                                                                    <span id="permanentAddressText"><?php echo $array['permanentaddress']; ?></span>
                                                                                    <!-- <input type="text" name="permanentaddress" id="permanentaddress" value="<?php echo $array['permanentaddress']; ?>" disabled style="display:none;"> -->
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card" id="national_identifier">
                                                            <div class="card-header">
                                                                National Identifier
                                                                <!-- <span class="edit-icon" onclick="toggleEdit('national_identifier')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                    <i class="bi bi-save"></i>
                                                                </span> -->
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="nationalidentifier">National Identifier Number:</label></td>
                                                                                <td>
                                                                                    <?php echo $array["nationalidentifier"] ?><br>
                                                                                    <?php echo $array["identifier"] ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- PAN Number -->
                                                                            <tr>
                                                                                <td><label for="panno">PAN Number:</label></td>
                                                                                <td>
                                                                                    <?php echo $array["panno"] ?>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Work Details Tab -->
                                                    <div id="work-details" class="tab-pane" role="tabpanel">
                                                        <div class="card" id="current-location">
                                                            <div class="card-header">
                                                                Current Location
                                                                <span class="edit-icon" onclick="toggleEdit('current-location')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="basebranch">Base Branch:</label></td>
                                                                                <td>
                                                                                    <span id="baseBranchText"><?php echo $array['basebranch']; ?></span>
                                                                                    <select name="basebranch" id="basebranch" disabled class="form-select" style="display:none;">
                                                                                        <option disabled selected>Select Base Branch</option>
                                                                                        <?php
                                                                                        // List of Base Branches
                                                                                        $base_branches = ["Lucknow", "West Bengal"];
                                                                                        // Generate <option> elements dynamically for Base Branch
                                                                                        foreach ($base_branches as $branch) {
                                                                                            $selected = ($array["basebranch"] == $branch) ? "selected" : "";
                                                                                            echo "<option value=\"$branch\" $selected>$branch</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>

                                                                            <tr>
                                                                                <td><label for="depb">Deputed Branch:</label></td>
                                                                                <td>
                                                                                    <span id="deputedBranchText"><?php echo $array['depb']; ?></span>
                                                                                    <select name="depb" id="depb" disabled class="form-select" style="display:none;">
                                                                                        <option disabled selected>Select Deputed Branch</option>
                                                                                        <?php
                                                                                        // List of Deputed Branches
                                                                                        $deputed_branches = ["Lucknow", "West Bengal"];
                                                                                        // Generate <option> elements dynamically for Deputed Branch
                                                                                        foreach ($deputed_branches as $branch) {
                                                                                            $selected = ($array["depb"] == $branch) ? "selected" : "";
                                                                                            echo "<option value=\"$branch\" $selected>$branch</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card" id="experience">
                                                            <div class="card-header">
                                                                Employment Details
                                                                <span class="edit-icon" onclick="toggleEdit('experience')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <!-- Date of Join -->
                                                                            <tr>
                                                                                <td><label for="doj">Date of Join:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <span id="doj"><?php echo htmlspecialchars((new DateTime($array["doj"]))->format("d/m/Y"), ENT_QUOTES, 'UTF-8'); ?></span>
                                                                                    <input type="date" name="doj" id="doj" value="<?php echo $array["doj"]; ?>" disabled class="form-control" style="display:none;">
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Work Mode -->
                                                                            <tr>
                                                                                <td><label for="class">Work Mode:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <span id="class"><?php echo $array['class']; ?></span>
                                                                                    <!-- Work Mode Dropdown -->
                                                                                    <select name="class" id="class" disabled class="form-select" style="display:none;">
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
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Shift -->
                                                                            <tr>
                                                                                <td><label for="shift">Shift:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <span id="shift"><?php echo $array['shift']; ?></span>
                                                                                    <!-- Shift Dropdown -->
                                                                                    <select name="shift" id="shift" disabled class="form-select" style="display:none;">
                                                                                        <option disabled selected>Select Shift</option>
                                                                                        <?php
                                                                                        // List of Shift
                                                                                        $shift = [
                                                                                            "Morning",
                                                                                            "Afternoon",
                                                                                        ];
                                                                                        // Generate <option> elements dynamically
                                                                                        foreach ($shift as $type) {
                                                                                            $selected = ($array["shift"] == $type) ? "selected" : "";
                                                                                            echo "<option value=\"$type\" $selected>$type</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Work Experience -->
                                                                            <tr>
                                                                                <td><label for="workexperience">Work Experience:</label></td>
                                                                                <td>
                                                                                    <?php
                                                                                    // Example input dates
                                                                                    $doj = $array["doj"]; // Date of Joining
                                                                                    $effectiveFrom = $array["effectivedate"]; // Effective End Date, could be null

                                                                                    // Parse dates
                                                                                    $dojDate = new DateTime($doj);
                                                                                    $currentDate = new DateTime(); // Current date
                                                                                    $endDate = $effectiveFrom ? new DateTime($effectiveFrom) : $currentDate; // Use effective date if set, otherwise use today

                                                                                    // Check if DOJ is in the future
                                                                                    if ($dojDate > $currentDate) {
                                                                                        // If the DOJ is in the future, display a message
                                                                                        echo "Not yet commenced";
                                                                                    } else {
                                                                                        // Calculate the difference
                                                                                        $interval = $dojDate->diff($endDate);

                                                                                        // Extract years, months, and days
                                                                                        $years = $interval->y;
                                                                                        $months = $interval->m;
                                                                                        $days = $interval->d;

                                                                                        // Determine the format to display
                                                                                        if ($years > 0) {
                                                                                            $experience = number_format($years + ($months / 12), 2) . " year(s)";
                                                                                        } elseif ($months > 0) {
                                                                                            $experience = number_format($months + ($days / 30), 2) . " month(s)";
                                                                                        } else {
                                                                                            $experience = number_format($days, 2) . " day(s)";
                                                                                        }

                                                                                        // Output the result
                                                                                        echo (new DateTime($doj))->format("d M, Y") . " to " . ($effectiveFrom ? (new DateTime($effectiveFrom))->format("d M, Y") : "Today") . ": " . $experience;
                                                                                    }
                                                                                    ?>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card" id="roles">
                                                            <div class="card-header">
                                                                Roles
                                                                <span class="edit-icon" onclick="toggleEdit('roles')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <!-- Type of Association -->
                                                                            <tr>
                                                                                <td><label for="engagement">Type of Association:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <span id="engagement"><?php echo $array['engagement']; ?></span>
                                                                                    <!-- Type of Association Dropdown -->
                                                                                    <select name="engagement" id="engagement" disabled class="form-select" style="display:none;">
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
                                                                                </td>
                                                                            </tr>

                                                                            <!-- Job Type -->
                                                                            <tr>
                                                                                <td><label for="job_type">Job Type:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <span id="job_type"><?php echo $array['job_type']; ?></span>
                                                                                    <!-- Job Type Dropdown -->
                                                                                    <select name="job_type" id="job_type" disabled class="form-select" style="display:none;">
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
                                                                                </td>
                                                                            </tr>

                                                                            <!-- Designation and Grade -->
                                                                            <tr>
                                                                                <td><label for="position">Designation:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <!-- Position Dropdown -->
                                                                                    <span id="position"><?php echo $array['position']; ?></span>
                                                                                    <select name="position" id="position" disabled class="form-select" style="display:none;">
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
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Grade -->
                                                                            <tr>
                                                                                <td><label for="panno">Grade:</label></td>
                                                                                <td>
                                                                                    <?php echo $array["grade"] ?>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Access Role -->
                                                                            <tr>
                                                                                <td><label for="role">Access Role:</label></td>
                                                                                <td class="d-flex align-items-center">
                                                                                    <!-- Access Role Dropdown -->
                                                                                    <span id="role"><?php echo $array['role']; ?></span>
                                                                                    <select name="role" id="role" disabled style="display:none;" class="form-select">
                                                                                        <option disabled selected>Select Access Role</option>
                                                                                        <?php
                                                                                        // Generate <option> elements dynamically from the roles fetched from the database
                                                                                        foreach ($role_options as $role) {
                                                                                            $selected = ($array["role"] == $role) ? "selected" : "";
                                                                                            echo "<option value=\"$role\" $selected>$role</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Learnings Tab -->
                                                    <div id="learnings" class="tab-pane" role="tabpanel">
                                                        <div class="card" id="qualification">
                                                            <div class="card-header">
                                                                Qualification Details
                                                                <span class="edit-icon" onclick="toggleEdit('qualification')">
                                                                    <i class="bi bi-pencil"></i>
                                                                </span>
                                                                <span class="save-icon" id="saveIcon" style="display:none;" onclick="saveChanges()">
                                                                    <i class="bi bi-save"></i>
                                                                </span>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-borderless">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td><label for="eduq">Educational Qualification:</label></td>
                                                                                <td>
                                                                                    <?php echo $array["eduq"] ?>
                                                                                    </select>
                                                                                </td>
                                                                            </tr>
                                                                            <!-- Area of Specialization -->
                                                                            <tr>
                                                                                <td><label for="mjorsub">Area of Specialization:</label></td>
                                                                                <td>
                                                                                    <?php echo $array["mjorsub"] ?>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </fieldset>
                                            </form>

                                        </div>
                                    </div>
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
        document.addEventListener('DOMContentLoaded', () => {
            const menuItems = document.querySelector('#menu-items');
            const desktopMenu = document.querySelector('#sidebar-menu');
            const mobileMenu = document.querySelector('#mobile-menu-items');
            const accordionButton = document.querySelector('#headingMenu button');

            // Clone menu items for desktop and mobile
            menuItems.querySelectorAll('.nav-item').forEach(item => {
                // Clone the item for desktop sidebar
                const desktopItem = item.cloneNode(true);
                desktopMenu.appendChild(desktopItem);

                // Clone the item for mobile accordion
                const mobileItem = item.cloneNode(true);
                mobileMenu.appendChild(mobileItem);

                // Add click event listener to activate the respective tab and change button text
                mobileItem.querySelector('a').addEventListener('click', event => {
                    event.preventDefault();
                    const tabTarget = event.target.getAttribute('href'); // e.g., "#employee-details"

                    // Activate the tab using Bootstrap's default behavior
                    const tabElement = document.querySelector(`[href="${tabTarget}"]`);
                    if (tabElement) {
                        const tab = new bootstrap.Tab(tabElement);
                        tab.show();
                    }

                    // Update the accordion button text to reflect the selected menu
                    accordionButton.innerHTML = `Menu: ${event.target.innerText}`;

                    // Collapse the accordion after selection
                    const accordion = document.querySelector('#menuCollapse');
                    const bootstrapCollapse = new bootstrap.Collapse(accordion, {
                        toggle: true,
                    });
                });
            });
        });
    </script>
    <script>
        function toggleEdit(sectionId) {
            // Get the section based on the provided sectionId
            const section = document.getElementById(sectionId);

            // Get all input and select elements in the section
            const inputs = section.querySelectorAll('input, select');

            // Only proceed if there are input fields (input, select) in the section
            if (inputs.length > 0) {
                // Toggle edit mode for input elements in the section
                const textElements = section.querySelectorAll('span');

                // Toggle visibility of inputs (edit mode) and text (view mode)
                inputs.forEach(input => {
                    input.disabled = !input.disabled; // Toggle the disabled state
                    input.style.display = input.disabled ? 'none' : 'inline'; // Toggle input visibility
                });

                textElements.forEach(text => {
                    text.style.display = text.style.display === 'none' ? 'inline' : 'none'; // Toggle text visibility
                });

                // Toggle icons (replace pencil with save)
                const editIcon = section.querySelector('.edit-icon');
                const saveIcon = section.querySelector('.save-icon');

                if (editIcon && saveIcon) {
                    editIcon.style.display = 'none'; // Hide pencil icon
                    saveIcon.style.display = 'inline'; // Show save icon
                }
            }
        }

        function saveChanges() {
            // Submit the form when the save button is clicked
            document.getElementById('signup').submit();
        }
    </script>
</body>

</html>