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
            'absconding'
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

        .primary-details h4 {
            margin: 0;
            font-size: 1.5rem;
        }

        .primary-details p {
            margin: 5px 0;
            font-size: 0.9rem;
        }

        .contact-info {
            text-align: right;
            font-size: 0.9rem;
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

        .edit-icon {
            color: #31536C;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        .edit-icon {
            color: #d3d3d3;
            /* Light flat gray color */
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .edit-icon:hover {
            color: #a0a0a0;
            /* Darker gray on hover for a subtle effect */
        }

        /* Media Queries for Mobile Devices */
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

            .primary-details h4 {
                font-size: 1.25rem;
            }

            .primary-details p {
                font-size: 0.85rem;
            }

            .contact-info {
                text-align: left;
                font-size: 0.85rem;
            }

            .sidebar_two {
                width: 100%;
                min-width: 0;
                border-right: none;
                padding-top: 10px;
            }

            #sidebar_two .nav-link {
                padding: 8px 15px;
            }

            .content {
                padding: 15px;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .profile-img {
                width: 50px;
                height: 50px;
                margin-right: 8px;
            }

            .primary-details h4 {
                font-size: 1.1rem;
            }

            .primary-details p {
                font-size: 0.8rem;
            }

            .contact-info {
                font-size: 0.75rem;
            }

            .card {
                margin-bottom: 15px;
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
                                            <p style="font-size: medium;"><?php echo $array["fullname"] ?></p>
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
                                        <div class="sidebar_two" id="sidebar_two">
                                            <ul class="nav flex-column">
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
                                        </div>

                                        <!-- Content Area -->
                                        <div class="content tab-content">
                                            <div id="employee-details" class="tab-pane active">
                                                <div class="card">
                                                    <div class="card-header">
                                                        Address Details
                                                        <span class="edit-icon" onclick="toggleEdit(this)"><i class="bi bi-pencil"></i></span>
                                                    </div>
                                                    <div class="card-body">
                                                        <p contenteditable="false">40/702, EMAAR PALM HILLS, Sector 77<br>Gurugram, Haryana, India 122004</p>
                                                    </div>
                                                </div>
                                                <div class="card">
                                                    <div class="card-header">
                                                        Emergency Contacts
                                                        <span class="edit-icon" onclick="toggleEdit(this)"><i class="bi bi-pencil"></i></span>
                                                    </div>
                                                    <div class="card-body">
                                                        <p contenteditable="false">Saha, MR. Sourav (Brother) - 8697274669<br>Saha, MS. Rina (Mother) - 9674867184</p>
                                                    </div>
                                                </div>
                                                <div class="card">
                                                    <div class="card-header">
                                                        Visa Details
                                                        <span class="edit-icon" onclick="toggleEdit(this)"><i class="bi bi-pencil"></i></span>
                                                    </div>
                                                    <div class="card-body">
                                                        <p contenteditable="false">Visa details not available</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Other Tabs -->
                                            <!-- Work Details, Learnings, Published Documents, and Social Tabs go here -->
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
        function toggleEdit(icon) {
            const parent = icon.closest('.card');
            const content = parent.querySelector('.card-body p');
            const isEditable = content.getAttribute('contenteditable') === "true";
            content.setAttribute('contenteditable', !isEditable);
            content.focus();
        }
    </script>
</body>

</html>