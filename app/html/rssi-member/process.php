<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    $_SESSION["login_redirect_params"] = $_GET;
    header("Location: index.php");
    exit;
}

validation();
?>

<?php
// Retrieve student ID from form input
@$role_search = $_GET['role_search'];
// Query database for student information based on ID

$result = pg_query($con, "SELECT * FROM rolebasedgoal WHERE role_search = '$role_search'");
$resultArr = pg_fetch_all($result);

if (!$result) {
    echo "An error occurred.\n";
    exit;
}
?>

<?php
if (@$_POST['form-type'] == "appraisee_response") {
    $goalsheetid = uniqid();
    $appraisee_associatenumber = $_POST['appraisee_associate_number'];
    $manager_associatenumber = $_POST['manager_associate_number'];
    $manager1_associatenumber = $_POST['manager1_associatenumber'];
    $reviewer_associatenumber = $_POST['reviewer_associate_number'];
    $effective_start_date = $_POST['effective_start_date'];
    $effective_end_date = $_POST['effective_end_date'];
    $role = $_POST['role'];
    $appraisaltype = $_POST['appraisal_type'];
    $appraisalyear = $_POST['appraisal_year'];

    $parameters = [];
    for ($i = 1; $i <= 20; $i++) {
        $parameter = htmlspecialchars($_POST["parameter_$i"], ENT_QUOTES, 'UTF-8');
        $expectation = htmlspecialchars($_POST["expectation_$i"], ENT_QUOTES, 'UTF-8');
        $maxRating = isset($_POST["max_rating_$i"]) && !empty($_POST["max_rating_$i"]) ? $_POST["max_rating_$i"] : null;

        $parameters[] = compact("parameter", "expectation", "maxRating");
    }

    $goalsheet_created_by = $associatenumber;
    $goalsheet_created_on = date('Y-m-d H:i:s');

    $columns = ['goalsheetid', 'appraisee_associatenumber', 'manager1_associatenumber', 'manager_associatenumber', 'reviewer_associatenumber', 'effective_start_date', 'effective_end_date', 'role', 'appraisaltype', 'appraisalyear', 'goalsheet_created_by', 'goalsheet_created_on'];
    $values = [$goalsheetid, $appraisee_associatenumber, $manager1_associatenumber, $manager_associatenumber, $reviewer_associatenumber, $effective_start_date, $effective_end_date, $role, $appraisaltype, $appraisalyear, $goalsheet_created_by, $goalsheet_created_on];

    foreach ($parameters as $index => $paramData) {
        $index += 1; // Adjust the index to start from 1
        $columns[] = "parameter_$index";
        $values[] = $paramData['parameter'];

        $columns[] = "expectation_$index";
        $values[] = $paramData['expectation'];

        // $columns[] = "max_rating_$index";
        // $values[] = $paramData['maxRating'];
        if ($paramData['maxRating'] !== null) {
            $columns[] = "max_rating_$index";
            $values[] = $paramData['maxRating'];
        }
    }

    $columnsStr = implode(', ', $columns);
    // $valuesStr = "'" . implode("', '", $values) . "'";
    $valuesStr = implode(", ", array_map(function ($value) {
        return is_null($value) ? 'NULL' : "'$value'";
    }, $values));

    $appraisee_response = "INSERT INTO appraisee_response ($columnsStr) VALUES ($valuesStr)";

    $result = pg_query($con, $appraisee_response);
    $cmdtuples = pg_affected_rows($result);

    if (!$result) {
        echo "An error occurred.\n";
        exit;
    }

    $resultArr = pg_fetch_all($result);


    $resultt = pg_query($con, "Select phone,email,fullname from rssimyaccount_members where associatenumber='$appraisee_associatenumber'");
    @$appraisee_contact = pg_fetch_result($resultt, 0, 0);
    @$appraisee_email = pg_fetch_result($resultt, 0, 1);
    @$appraisee_name = pg_fetch_result($resultt, 0, 2);

    $resulttt = pg_query($con, "Select phone,email from rssimyaccount_members where associatenumber='$manager_associatenumber'");
    @$manager_contact = pg_fetch_result($resulttt, 0, 0);
    @$manager_email = pg_fetch_result($resulttt, 0, 1);

    $resultttt = pg_query($con, "Select phone,email from rssimyaccount_members where associatenumber='$reviewer_associatenumber'");
    @$reviewer_contact = pg_fetch_result($resultttt, 0, 0);
    @$reviewer_email = pg_fetch_result($resultttt, 0, 1);

    if (@$cmdtuples == 1 && $appraisee_email != "") {
        sendEmail("goal_sheet_creation_appraisee", array(
            "goalsheetid" => $goalsheetid,
            "appraisaltype" => @$appraisaltype,
            "appraisalyear" => @$appraisalyear,
            "appraisee_name" => @$appraisee_name,
            "link" => "https://login.rssi.in/rssi-member/my_appraisal.php?form-type=appraisee&get_id=" . urlencode(@$appraisaltype) . "&get_year=" . @$appraisalyear,
        ), $appraisee_email, False);
    }
} ?>

<!DOCTYPE html>
<html>

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
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Goal Setting Form</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Template Main CSS File -->
    <link href="../assets_new/css/style.css" rel="stylesheet">
</head>

<body>
    <?php if (@$goalsheetid != null && @$cmdtuples == 0) { ?>

        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="text-align: -webkit-center;">
            Oops, something went wrong! We were unable to generate the goal sheet for associate number <?php echo $appraisee_associatenumber ?>. Please try again later or contact support for assistance.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } else if (@$cmdtuples == 1) { ?>

        <div class="alert alert-success alert-dismissible fade show" role="alert" style="text-align: -webkit-center;">
            Congratulations! The goal sheet for associate number <?php echo $appraisee_associatenumber ?> has been generated successfully. The unique goal sheet ID is <?php echo $goalsheetid ?>.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>
    <?php } ?>
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
                            <div class="container mt-5">
                                <form method="GET" action="" id="searchForm">
                                    <div class="form-group mb-3">
                                        <label for="role_search" class="form-label">Role:</label>
                                        <select class="form-select" name="role_search" required>

                                            <?php if (@$_GET['role_search'] == null) { ?>
                                                <option selected>--Select Role--</option>
                                            <?php
                                            } else { ?>
                                                <option selected>--Select Role--</option>
                                                <option hidden selected><?php echo @$_GET['role_search'] ?></option>
                                            <?php }
                                            ?>
                                            <option value="Teacher">Teacher</option>
                                            <option value="Faculty">Faculty</option>
                                            <option value="Intern">Intern</option>
                                            <option value="Centre In-Charge">Centre In-Charge</option>
                                            <option value="Centre Coordinator">Centre Coordinator</option>
                                            <option value="Member">Member</option>
                                            <option value="HR">HR</option>
                                            <option value="Counselor">Counselor</option>
                                            <option value="Support Staff">Support Staff</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="searchBtn" class="btn btn-primary mb-3" id="searchBtn">
                                        <span id="btnText">Search</span>
                                        <span id="loadingIndicator" style="display: none;">Loading...</span>
                                    </button>
                                </form>

                                <script>
                                    document.getElementById('searchForm').addEventListener('submit', function() {
                                        // Display loading indicator in the button
                                        document.getElementById('btnText').style.display = 'none';
                                        document.getElementById('loadingIndicator').style.display = 'inline-block';
                                        document.getElementById('searchBtn').setAttribute('disabled', 'disabled');

                                        // You may want to use AJAX to submit the form and fetch results from the server.
                                        // For demonstration purposes, I'll simulate a delay using setTimeout.
                                        setTimeout(function() {
                                            // Hide loading indicator and restore button text after a delay (replace this with your actual logic)
                                            document.getElementById('btnText').style.display = 'inline-block';
                                            document.getElementById('loadingIndicator').style.display = 'none';
                                            document.getElementById('searchBtn').removeAttribute('disabled');
                                        }, 2000); // 2000 milliseconds (2 seconds) delay, replace with your actual AJAX call
                                    });
                                </script>
                                <br>
                                <h2 class="text-center mb-4" style="background-color:#CE1212; color:white; padding:10px;">Goal Setting Form</h2>


                                <p>Unique Id: WB/2021/0282726 (NGO Darpan, NITI Aayog, Government of India)</p>
                                <p></p>
                                <hr>
                                <?php if (sizeof($resultArr) > 0) { ?>
                                    <?php
                                    foreach ($resultArr as $array) {
                                    ?>
                                        <form method="post" name="process" id="process" onsubmit="return checkAssociateNumbers()">

                                            <input type="hidden" name="form-type" value="appraisee_response">
                                            <!-- Add a checkbox at the beginning -->
                                            <div class="form-check mb-3">
                                                <input type="checkbox" class="form-check-input" id="disableFieldsCheckbox">
                                                <label class="form-check-label" for="disableFieldsCheckbox">Modify role-specific goal database</label>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label for="appraisee_associate_number" class="form-label">Appraisee Associate Number:</label>
                                                        <input type="text" class="form-control" name="appraisee_associate_number" required>
                                                        <div id="appraisee_associate_number_help" class="form-text">Please enter the unique associate number of the appraisee.</div>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label for="manager1_associatenumber" class="form-label">Immediate Manager Associate Number:</label>
                                                        <input type="text" class="form-control" name="manager1_associatenumber">
                                                        <div id="manager1_associatenumber_help" class="form-text">Please enter the unique associate number of the immediate manager.</div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label for="manager_associate_number" class="form-label">Manager Associate Number:</label>
                                                        <input type="text" class="form-control" name="manager_associate_number" required>
                                                        <div id="manager_associate_number_help" class="form-text">Please enter the unique associate number of the manager.</div>
                                                    </div>

                                                    <div class="form-group mb-3">
                                                        <label for="reviewer_associate_number" class="form-label">Reviewer Associate Number:</label>
                                                        <input type="text" class="form-control" name="reviewer_associate_number" required>
                                                        <div id="reviewer_associate_number_help" class="form-text">Please enter the unique associate number of the reviewer.</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label for="role" class="form-label">Role:</label>
                                                        <select class="form-select" name="role" required>
                                                            <option disabled selected>--Select Role--</option>
                                                            <option value="Teacher">Teacher</option>
                                                            <option value="Faculty">Faculty</option>
                                                            <option value="Intern">Intern</option>
                                                            <option value="Centre In-Charge">Centre In-Charge</option>
                                                            <option value="Centre Coordinator">Centre Coordinator</option>
                                                            <option value="Member">Member</option>
                                                            <option value="HR">HR</option>
                                                            <option value="Counselor">Counselor</option>
                                                            <option value="Support Staff">Support Staff</option>
                                                            <option value="Other">Other</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="form-group mb-3">
                                                        <label for="appraisal_type" class="form-label">Appraisal Type:</label>
                                                        <select class="form-select" name="appraisal_type" required>
                                                            <option disabled selected>--Select an option--</option>
                                                            <option value="Annual">Annual</option>
                                                            <option value="Quarterly">Quarterly</option>
                                                            <option value="Project End">Project End</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-4">

                                                    <div class="form-group mb-3">
                                                        <label for="effective_start_date" class="form-label">Effective Start Date:</label>
                                                        <input type="date" class="form-control" name="effective_start_date" required>
                                                        <div id="effective_start_date_help" class="form-text">Please select the effective start date.</div>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">

                                                    <div class="form-group mb-3">
                                                        <label for="effective_end_date" class="form-label">Effective End Date:</label>
                                                        <input type="date" class="form-control" name="effective_end_date" required>
                                                        <div id="effective_end_date_help" class="form-text">Please select the effective end date.</div>
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="form-group mb-3">
                                                        <label for="appraisal_year" class="form-label">Appraisal Year:</label>
                                                        <select class="form-select" name="appraisal_year" id="appraisal_year" required>
                                                            <?php if ($appraisal_year == null) { ?>
                                                                <option hidden selected>--Select Appraisal Year--</option>
                                                            <?php } else { ?>
                                                                <option hidden selected><?php echo $appraisal_year ?></option>
                                                            <?php } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <h2>Goals</h2>
                                            <p>Scoping & planning (Operational efficiency, Individual contribution, Gearing up for future, Student centricity, Audits & Compliance)</p>
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Parameter</th>
                                                        <th scope="col">Expectation</th>
                                                        <th scope="col">Max Rating</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_1" value="<?php echo $array['parameter_1'] ?>"></td>
                                                        <td><input type="text" class="form-control" name="expectation_1" value="<?php echo $array['expectation_1'] ?>"></td>
                                                        <td><input type="number" class="form-control" name="max_rating_1" value="<?php echo $array['max_rating_1'] ?>"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_2" value="<?php echo $array['parameter_2'] ?>"></td>
                                                        <td><input type="text" class="form-control" name="expectation_2" value="<?php echo $array['expectation_2'] ?>"></td>
                                                        <td><input type="number" class="form-control" name="max_rating_2" value="<?php echo $array['max_rating_2'] ?>"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_3" value="<?php echo $array['parameter_3'] ?>"></td>
                                                        <td><input type="text" class="form-control" name="expectation_3" value="<?php echo $array['expectation_3'] ?>"></td>
                                                        <td><input type="number" class="form-control" name="max_rating_3" value="<?php echo $array['max_rating_3'] ?>"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_4" value="<?php echo $array['parameter_4'] ?>"></td>
                                                        <td><input type="text" class="form-control" name="expectation_4" value="<?php echo $array['expectation_4'] ?>"></td>
                                                        <td><input type="number" class="form-control" name="max_rating_4" value="<?php echo $array['max_rating_4'] ?>"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_5" value="<?php echo $array['parameter_5'] ?>"></td>
                                                        <td><input type="text" class="form-control" name="expectation_5" value="<?php echo $array['expectation_5'] ?>"></td>
                                                        <td><input type="number" class="form-control" name="max_rating_5" value="<?php echo $array['max_rating_5'] ?>"></td>
                                                    </tr>

                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_6" value="<?php echo $array['parameter_6'] ?>"></td>
                                                        <td><input type="text" class="form-control" name="expectation_6" value="<?php echo $array['expectation_6'] ?>"></td>
                                                        <td><input type="number" class="form-control" name="max_rating_6" value="<?php echo $array['max_rating_6'] ?>"></td>
                                                    </tr>

                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_7" value="<?php echo $array['parameter_7'] ?>"></td>
                                                        <td><input type="text" class="form-control" name="expectation_7" value="<?php echo $array['expectation_7'] ?>"></td>
                                                        <td><input type="number" class="form-control" name="max_rating_7" value="<?php echo $array['max_rating_7'] ?>"></td>
                                                    </tr>

                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_8" value="<?php echo $array['parameter_8'] ?>"></td>
                                                        <td><input type="text" class="form-control" name="expectation_8" value="<?php echo $array['expectation_8'] ?>"></td>
                                                        <td><input type="number" class="form-control" name="max_rating_8" value="<?php echo $array['max_rating_8'] ?>"></td>
                                                    </tr>

                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_9" value="<?php echo $array['parameter_9'] ?>"></td>
                                                        <td><input type="text" class="form-control" name="expectation_9" value="<?php echo $array['expectation_9'] ?>"></td>
                                                        <td><input type="number" class="form-control" name="max_rating_9" value="<?php echo $array['max_rating_9'] ?>"></td>
                                                    </tr>

                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_10" value="<?php echo $array['parameter_10'] ?>"></td>
                                                        <td><input type="text" class="form-control" name="expectation_10" value="<?php echo $array['expectation_10'] ?>"></td>
                                                        <td><input type="number" class="form-control" name="max_rating_10" value="<?php echo $array['max_rating_10'] ?>"></td>
                                                    </tr>

                                                </tbody>
                                            </table>

                                            <h2>Attributes</h2>
                                            <p>Attributes are competencies essential for performing a role.</p>
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Parameter</th>
                                                        <th scope="col">Expectation</th>
                                                        <th scope="col">Max Rating</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_11" value="<?php echo $array['parameter_11'] ?>"></td>
                                                        <td><textarea class="form-control" name="expectation_11"><?php echo $array['expectation_11'] ?></textarea></td>
                                                        <td><input type="number" class="form-control" name="max_rating_11" value="<?php echo $array['max_rating_11'] ?>"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_12" value="<?php echo $array['parameter_12'] ?>"></td>
                                                        <td><textarea class="form-control" name="expectation_12"><?php echo $array['expectation_12'] ?></textarea></td>
                                                        <td><input type="number" class="form-control" name="max_rating_12" value="<?php echo $array['max_rating_12'] ?>"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_13" value="<?php echo $array['parameter_13'] ?>"></td>
                                                        <td><textarea class="form-control" name="expectation_13"><?php echo $array['expectation_13'] ?></textarea></td>
                                                        <td><input type="number" class="form-control" name="max_rating_13" value="<?php echo $array['max_rating_13'] ?>"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_14" value="<?php echo $array['parameter_14'] ?>"></td>
                                                        <td><textarea class="form-control" name="expectation_14"><?php echo $array['expectation_14'] ?></textarea></td>
                                                        <td><input type="number" class="form-control" name="max_rating_14" value="<?php echo $array['max_rating_14'] ?>"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_15" value="<?php echo $array['parameter_15'] ?>"></td>
                                                        <td><textarea class="form-control" name="expectation_15"><?php echo $array['expectation_15'] ?></textarea></td>
                                                        <td><input type="number" class="form-control" name="max_rating_15" value="<?php echo $array['max_rating_15'] ?>"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_16" value="<?php echo $array['parameter_16'] ?>"></td>
                                                        <td><textarea class="form-control" name="expectation_16"><?php echo $array['expectation_16'] ?></textarea></td>
                                                        <td><input type="number" class="form-control" name="max_rating_16" value="<?php echo $array['max_rating_16'] ?>"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_17" value="<?php echo $array['parameter_17'] ?>"></td>
                                                        <td><textarea class="form-control" name="expectation_17"><?php echo $array['expectation_17'] ?></textarea></td>
                                                        <td><input type="number" class="form-control" name="max_rating_17" value="<?php echo $array['max_rating_17'] ?>"></textarea></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_18" value="<?php echo $array['parameter_18'] ?>"></td>
                                                        <td><textarea class="form-control" name="expectation_18"><?php echo $array['expectation_18'] ?></textarea></td>
                                                        <td><input type="number" class="form-control" name="max_rating_18" value="<?php echo $array['max_rating_18'] ?>"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_19" value="<?php echo $array['parameter_19'] ?>"></td>
                                                        <td><textarea class="form-control" name="expectation_19"><?php echo $array['expectation_19'] ?></textarea></td>
                                                        <td><input type="number" class="form-control" name="max_rating_19" value="<?php echo $array['max_rating_19'] ?>"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control" name="parameter_20" value="<?php echo $array['parameter_20'] ?>"></td>
                                                        <td><textarea class="form-control" name="expectation_20"><?php echo $array['expectation_20'] ?></textarea></td>
                                                        <td><input type="number" class="form-control" name="max_rating_20" value="<?php echo $array['max_rating_20'] ?>"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <button type="submit" id="submit2" class="btn btn-warning">Update</button>
                                            <button type="submit" id="submit3" class="btn btn-danger">Add New</button>
                                            <button type="submit" id="submit" class="btn btn-success">Submit</button>

                                            <script>
                                                var form = document.getElementById('process');
                                                var submit2Button = document.getElementById('submit2');
                                                var submit3Button = document.getElementById('submit3');
                                                var submit1Button = document.getElementById('submit');
                                                var disableCheckbox = document.getElementById('disableFieldsCheckbox');

                                                // Initial check on page load
                                                updateButtonVisibility();

                                                // Add event listeners to the submit buttons
                                                submit2Button.addEventListener('click', function() {
                                                    form.action = 'goal_db_update.php';
                                                });

                                                submit3Button.addEventListener('click', function() {
                                                    form.action = 'goal_db_add.php';
                                                });

                                                submit1Button.addEventListener('click', function() {
                                                    form.action = 'process.php';
                                                });

                                                // Add an event listener to the checkbox
                                                disableCheckbox.addEventListener('change', function() {
                                                    updateButtonVisibility();
                                                });

                                                function updateButtonVisibility() {
                                                    // If the checkbox is checked, show Update and Add New buttons, and hide Submit button
                                                    if (disableCheckbox.checked) {
                                                        submit2Button.style.display = 'inline-block';
                                                        submit3Button.style.display = 'inline-block';
                                                        submit1Button.style.display = 'none';
                                                    } else {
                                                        // If the checkbox is unchecked, show Submit button, and hide Update and Add New buttons
                                                        submit2Button.style.display = 'none';
                                                        submit3Button.style.display = 'none';
                                                        submit1Button.style.display = 'inline-block';
                                                    }
                                                }
                                            </script>
                                            <br><br>
                                        </form>

                                    <?php } ?>
                                <?php
                                } else if ($role_search == null) {
                                ?>
                                    <p>Please enter the Student ID.</p>
                                <?php
                                } else {
                                ?>
                                    <p>We could not find any records matching the entered Student ID.</p>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div><!-- End Reports -->
            </div>
        </section>

    </main><!-- End #main -->

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    <!-- Bootstrap JS -->

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/js/bootstrap.min.js"></script>
    <!-- Template Main JS File -->
      <script src="../assets_new/js/main.js"></script>
  

    <script>
        function checkAssociateNumbers() {
            // Check if the checkbox is checked
            var disableCheckbox = document.getElementById('disableFieldsCheckbox');
            if (disableCheckbox.checked) {
                // The checkbox is checked, you can perform a different action or skip validation
                return true;
            }

            // Perform your validation logic
            var appraisee = document.getElementsByName('appraisee_associate_number')[0].value;
            var manager = document.getElementsByName('manager_associate_number')[0].value;
            var reviewer = document.getElementsByName('reviewer_associate_number')[0].value;

            if (appraisee == manager || appraisee == reviewer) {
                alert("Error: The appraisee cannot also be the manager or reviewer for the same goal sheet. Please select a different manager or reviewer.");
                return false;
            }

            return true;
        }
    </script>
    <script>
        $(document).ready(function() {
            $('input[required], select[required], textarea[required]').each(function() {
                $(this).closest('.form-group').find('label').append(' <span style="color: red">*</span>');
            });
        });
    </script>
    <script>
        var currentYear = new Date().getFullYear();
        for (var i = 0; i < 5; i++) {
            var nextYear = currentYear + 1;
            var yearRange = currentYear.toString() + '-' + nextYear.toString();
            var option = document.createElement('option');
            option.value = yearRange;
            option.text = yearRange;
            document.getElementById('appraisal_year').appendChild(option);
            // currentYear = nextYear;
            currentYear--;
        }
    </script>
    <script>
        // Get the checkbox and the fields you want to disable
        var disableFieldsCheckbox = document.getElementById('disableFieldsCheckbox');
        var fieldsToDisable = document.querySelectorAll('[name="appraisee_associate_number"], [name="manager1_associatenumber"], [name="manager_associate_number"], [name="reviewer_associate_number"], [name="effective_start_date"], [name="effective_end_date"], [name="appraisal_type"], [name="appraisal_year"]');

        // Add an event listener to the checkbox
        disableFieldsCheckbox.addEventListener('change', function() {
            // Toggle the disabled attribute for each field
            fieldsToDisable.forEach(function(field) {
                field.disabled = disableFieldsCheckbox.checked;
            });

            // If the checkbox is checked, remove the "required" attribute from fields
            if (disableFieldsCheckbox.checked) {
                fieldsToDisable.forEach(function(field) {
                    if (field.name !== 'manager1_associatenumber') {
                        field.removeAttribute('required');
                    }
                });
            } else {
                // If the checkbox is unchecked, add the "required" attribute back to fields
                fieldsToDisable.forEach(function(field) {
                    if (field.name !== 'manager1_associatenumber') {
                        field.setAttribute('required', 'required');
                    }
                });
            }
        });
    </script>
</body>

</html>