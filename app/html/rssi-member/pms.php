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

date_default_timezone_set('Asia/Kolkata');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $type = isset($_POST['type']) ? htmlspecialchars(trim($_POST['type'])) : null;
    $user_id = isset($_POST['userid']) ? htmlspecialchars(trim($_POST['userid'])) : null;
    $password = bin2hex(random_bytes(3)); // Generates a random 6-character alphanumeric password
    $now = date('Y-m-d H:i:s');
    $acc_setup = isset($_POST['acc_setup']) ? true : null;

    if ($type && $user_id && $password) {
        // Format user_id based on type
        $user_id = ($type === "Applicant") ? $user_id : strtoupper($user_id);

        // Hash the password
        $newpass_hash = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL query
        $queries = [
            "Associate" => "UPDATE rssimyaccount_members SET password = $1, default_pass_updated_by = $2, default_pass_updated_on = $3 WHERE associatenumber = $4",
            "Applicant" => "UPDATE signup SET password = $1, default_pass_updated_by = $2, default_pass_updated_on = $3 WHERE application_number = $4",
            "Student"   => "UPDATE rssimyprofile_student SET password = $1, default_pass_updated_by = $2, default_pass_updated_on = $3 WHERE student_id = $4",
        ];

        $change_password_query = $queries[$type] ?? null;

        if ($change_password_query) {
            // Execute the query using prepared statements
            $result = pg_query_params($con, $change_password_query, [$newpass_hash, $user_check, $now, $user_id]);

            if (!$result) {
                // If the query fails, show an error
                echo "<script type='text/javascript'>
                        alert('Error: Failed to update the password. Please try again.');
                      </script>";
                exit;
            } else {
                // Query to fetch fullname, email, and phone
                if ($type === 'Associate') {
                    $query = "SELECT fullname, email, phone FROM rssimyaccount_members WHERE associatenumber = '$user_id'";
                } elseif ($type === 'Applicant') {
                    $query = "SELECT applicant_name AS fullname, email, telephone AS phone 
                              FROM signup 
                              WHERE application_number = '$user_id'";
                } elseif ($type === 'Student') {
                    $query = "SELECT studentname AS fullname, emailaddress AS email, contact AS phone 
                              FROM rssimyprofile_students 
                              WHERE student_id = '$user_id'";
                } else {
                    // Handle invalid $type or throw an error
                    $query = null;
                    // Optionally log or throw an exception
                    error_log("Invalid type provided: $type");
                }

                // Execute the query
                $result = pg_query($con, $query);

                if ($result && pg_num_rows($result) > 0) {
                    // Fetch the result
                    $row = pg_fetch_assoc($result);

                    // Extract values
                    $fullname = $row['fullname'];
                    $email = $row['email'];
                    $phone = $row['phone'];
                }
                $template = ($acc_setup === true) ? 'new_acc_setup' : 'reg_pass_change';

                sendEmail(
                    $template,
                    [
                        "fullname" => $fullname,
                        "associatenumber" => $user_id,
                        "password" => $password,
                    ],
                    $email,
                    false
                );
                // If the query succeeds, show success message
                echo "<script type='text/javascript'>
                        alert('Password has been updated successfully for $user_id.');
                        if (window.history.replaceState) {
                        // Update the URL without causing a page reload or resubmission
                        window.history.replaceState(null, null, window.location.href);
                    }
                    window.location.reload(); // Trigger a page reload to reflect changes
                      </script>";
            }
        }
    }
}
// Initialize $resultArrr as an empty array if it hasn't been set before
$resultArrr = [];

// Fetch details from the form if POST is made
$get_id = isset($_POST['get_id']) ? htmlspecialchars(trim($_POST['get_id'])) : null;
$get_status = isset($_POST['get_status']) ? htmlspecialchars(trim($_POST['get_status'])) : null;

if ($get_id) {
    // Define queries for fetching details based on $get_id and $get_status
    $detail_queries = [
        "Associate" => $get_status
            ? "SELECT * FROM rssimyaccount_members WHERE associatenumber = $1"
            : "SELECT * FROM rssimyaccount_members WHERE filterstatus = 'Active' AND default_pass_updated_on IS NOT NULL",
        "Student" => $get_status
            ? "SELECT * FROM rssimyprofile_student WHERE student_id = $1"
            : "SELECT * FROM rssimyprofile_student WHERE filterstatus = 'Active' AND default_pass_updated_on IS NOT NULL",
        "Applicant" => $get_status
            ? "SELECT * FROM signup WHERE application_number = $1"
            : "SELECT * FROM signup WHERE default_pass_updated_on IS NOT NULL",
    ];

    // Choose the query based on $get_id
    $change_details_query = isset($detail_queries[$get_id]) ? $detail_queries[$get_id] : "SELECT * FROM rssimyprofile_student WHERE student_id = ''";

    // Execute query with or without $get_status as a parameter
    if ($get_status) {
        $result = pg_query_params($con, $change_details_query, [$get_status]);
    } else {
        $result = pg_query($con, $change_details_query);
    }

    // Check if the query execution was successful
    if (!$result) {
        echo "An error occurred while fetching details.\n";
        exit;
    }

    // Fetch all the results into $resultArrr
    $resultArrr = pg_fetch_all($result);
    // Handle $resultArrr as needed, for example, display the results in a table
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

    <title>PMS</title>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>

    <style>
        .x-btn:focus,
        .button:focus,
        [type="submit"]:focus {
            outline: none;
        }
    </style>
    <!-- CSS Library Files -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.4/css/dataTables.bootstrap5.css">
    <!-- JavaScript Library Files -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.4/js/dataTables.bootstrap5.js"></script>
</head>

<!-- =========================
     NAVIGATION LINKS     
============================== -->

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
    <?php include 'header.php'; ?>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>PMS</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Work</a></li>
                    <li class="breadcrumb-item active">PMS</li>
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

                            <form autocomplete="off" name="pms" id="pms" action="pms.php" method="POST">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">
                                        <select name="type" class="form-select" style="width:max-content; display:inline-block" required>
                                            <?php if ($type == null) { ?>
                                                <option disabled selected hidden>Association Type</option>
                                            <?php
                                            } else { ?>
                                                <option hidden selected><?php echo $type ?></option>
                                            <?php }
                                            ?>
                                            <option>Associate</option>
                                            <option>Student</option>
                                            <option>Applicant</option>
                                        </select>
                                        <input type="text" name="userid" class="form-control" style="width:max-content; display:inline-block" placeholder="User ID" required>
                                        <!-- <input type="password" name="newpass" id="newpass" class="form-control" style="width:max-content; display:inline-block" placeholder="New password" required> -->
                                    </div>

                                </div>

                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_id" class="btn btn-danger btn-sm" style="outline: none;">
                                        Update</button>
                                </div>
                                <br>
                                <div class="form-check mt-3">
                                    <input type="checkbox" name="acc_setup" id="acc_setup" class="form-check-input" value="true">
                                    <label for="acc_setup" class="form-check-label">Account Configuration</label>
                                </div>
                                <!-- <div class="form-check">
                                    <label for="show-password" class="field__toggle">
                                        <input type="checkbox" class="form-check-input" id="show-password" class="field__toggle-input" style="display: inline-block;" />&nbsp;Show password
                                    </label>
                                </div> -->
                            </form>

                            <br><b><span class="underline">Password change details</span></b><br><br>
                            <form name="changedetails" id="changedetails" action="" method="POST">
                                <div class="form-group" style="display: inline-block;">
                                    <div class="col2" style="display: inline-block;">
                                        <select name="get_id" class="form-select" style="width:max-content; display:inline-block" required>
                                            <?php if ($get_id == null) { ?>
                                                <option disabled selected hidden>Association Type</option>
                                            <?php } else { ?>
                                                <option hidden selected><?php echo $get_id; ?></option>
                                            <?php } ?>
                                            <option>Associate</option>
                                            <option>Student</option>
                                            <option>Applicant</option>
                                        </select>&nbsp;
                                        <input type="text" name="get_status" class="form-control" style="width:max-content; display:inline-block" placeholder="User ID" value="<?php echo $get_status; ?>">
                                    </div>
                                </div>
                                <div class="col2 left" style="display: inline-block;">
                                    <button type="submit" name="search_by_idd" class="btn btn-primary btn-sm" style="outline: none;">
                                        <i class="bi bi-search"></i>&nbsp;Search</button>
                                </div>
                            </form>

                            <?php
                            // Start of PHP block for alerts
                            echo "<script type='text/javascript'>";
                            if ($cmdtuples == 0) {
                                echo "alert('Error: The association type and user ID you entered is incorrect.');";
                            } elseif ($cmdtuples == 1) {
                                echo "alert('Password has been updated successfully for $user_id.');";
                            }
                            echo "</script>";
                            ?>

                            <div class="table-responsive">
                                <table id="userDetailsTable" class="table">
                                    <thead>
                                        <tr>
                                            <th>User ID</th>
                                            <th>Set on</th>
                                            <th>Set by</th>
                                            <th>Changed on</th>
                                            <th>Changed by</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (sizeof($resultArrr) > 0) {
                                            foreach ($resultArrr as $array) { ?>
                                                <tr>
                                                    <td>
                                                        <?php echo @$array['associatenumber'] . @$array['student_id'] . @$array['application_number']; ?>
                                                        <?php if ($array['password_updated_by'] == null || $array['password_updated_on'] < $array['default_pass_updated_on']) { ?>
                                                            <p class="badge bg-warning">defaulter</p>
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <?php echo ($array['default_pass_updated_on'] != null) ? date("d/m/Y g:i a", strtotime($array['default_pass_updated_on'])) : ''; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo ($array['default_pass_updated_by'] ?? 'System'); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo ($array['password_updated_on'] != null) ? date("d/m/Y g:i a", strtotime($array['password_updated_on'])) : ''; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo $array['password_updated_by']; ?>
                                                    </td>
                                                </tr>
                                            <?php }
                                        } else {
                                            if ($get_id == null && $get_status == null) { ?>
                                                <tr>
                                                    <td colspan="5">Please select Filter value.</td>
                                                </tr>
                                            <?php } else { ?>
                                                <tr>
                                                    <td colspan="5">No record was found for the selected filter value.</td>
                                                </tr>
                                        <?php }
                                        } ?>
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
    <!-- <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        var password = document.querySelector("#newpass");
        var toggle = document.querySelector("#show-password");
        // I'm using the "(click)" event to make this works cross-browser.
        toggle.addEventListener("click", handleToggleClick, false);
        // I handle the toggle click, changing the TYPE of password input.
        function handleToggleClick(event) {

            if (this.checked) {

                console.warn("Change input 'type' to: text");
                password.type = "text";

            } else {

                console.warn("Change input 'type' to: password");
                password.type = "password";

            }

        }
    </script> -->
    <script>
        $(document).ready(function() {
            // Check if resultArr is empty
            <?php if (!empty($resultArrr)) : ?>
                // Initialize DataTables only if resultArr is not empty
                $('#userDetailsTable').DataTable({
                    // paging: false,
                    "order": [] // Disable initial sorting
                    // other options...
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>