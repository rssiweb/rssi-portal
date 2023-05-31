<?php
require_once __DIR__ . "/../../bootstrap.php";

include("../../util/login_util.php");
include("../../util/email.php");
include("../../util/drive.php");

if (!isLoggedIn("aid")) {
    $_SESSION["login_redirect"] = $_SERVER["PHP_SELF"];
    header("Location: index.php");
    exit;
}

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
}

if ($role != 'Admin') {

    echo '<script type="text/javascript">';
    echo 'alert("Access Denied. You are not authorized to access this web page.");';
    echo 'window.location.href = "home.php";';
    echo '</script>';
}
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
    $reviewer_associatenumber = $_POST['reviewer_associate_number'];
    $role = $_POST['role'];
    $appraisaltype = $_POST['appraisal_type'];
    $appraisalyear = $_POST['appraisal_year'];
    $parameter_1 = $_POST['parameter_1'];
    $expectation_1 = $_POST['expectation_1'];
    $parameter_2 = $_POST['parameter_2'];
    $expectation_2 = $_POST['expectation_2'];
    $parameter_3 = $_POST['parameter_3'];
    $expectation_3 = $_POST['expectation_3'];
    $parameter_4 = $_POST['parameter_4'];
    $expectation_4 = $_POST['expectation_4'];
    $parameter_5 = $_POST['parameter_5'];
    $expectation_5 = $_POST['expectation_5'];
    $parameter_6 = $_POST['parameter_6'];
    $expectation_6 = $_POST['expectation_6'];
    $parameter_7 = $_POST['parameter_7'];
    $expectation_7 = $_POST['expectation_7'];
    $parameter_8 = $_POST['parameter_8'];
    $expectation_8 = $_POST['expectation_8'];
    $parameter_9 = $_POST['parameter_9'];
    $expectation_9 = $_POST['expectation_9'];
    $parameter_10 = $_POST['parameter_10'];
    $expectation_10 = $_POST['expectation_10'];
    $parameter_11 = $_POST['parameter_11'];
    $expectation_11 = $_POST['expectation_11'];
    $parameter_12 = $_POST['parameter_12'];
    $expectation_12 = $_POST['expectation_12'];
    $parameter_13 = $_POST['parameter_13'];
    $expectation_13 = $_POST['expectation_13'];
    $parameter_14 = $_POST['parameter_14'];
    $expectation_14 = $_POST['expectation_14'];
    $parameter_15 = $_POST['parameter_15'];
    $expectation_15 = $_POST['expectation_15'];
    $parameter_16 = $_POST['parameter_16'];
    $expectation_16 = $_POST['expectation_16'];
    $parameter_17 = $_POST['parameter_17'];
    $expectation_17 = $_POST['expectation_17'];
    $parameter_18 = $_POST['parameter_18'];
    $expectation_18 = $_POST['expectation_18'];
    $parameter_19 = $_POST['parameter_19'];
    $expectation_19 = $_POST['expectation_19'];
    $parameter_20 = $_POST['parameter_20'];
    $expectation_20 = $_POST['expectation_20'];
    $max_rating_1 = isset($_POST['max_rating_1']) && !empty($_POST['max_rating_1']) ? $_POST['max_rating_1'] : 'NULL';
    $max_rating_2 = isset($_POST['max_rating_2']) && !empty($_POST['max_rating_2']) ? $_POST['max_rating_2'] : 'NULL';
    $max_rating_3 = isset($_POST['max_rating_3']) && !empty($_POST['max_rating_3']) ? $_POST['max_rating_3'] : 'NULL';
    $max_rating_4 = isset($_POST['max_rating_4']) && !empty($_POST['max_rating_4']) ? $_POST['max_rating_4'] : 'NULL';
    $max_rating_5 = isset($_POST['max_rating_5']) && !empty($_POST['max_rating_5']) ? $_POST['max_rating_5'] : 'NULL';
    $max_rating_6 = isset($_POST['max_rating_6']) && !empty($_POST['max_rating_6']) ? $_POST['max_rating_6'] : 'NULL';
    $max_rating_7 = isset($_POST['max_rating_7']) && !empty($_POST['max_rating_7']) ? $_POST['max_rating_7'] : 'NULL';
    $max_rating_8 = isset($_POST['max_rating_8']) && !empty($_POST['max_rating_8']) ? $_POST['max_rating_8'] : 'NULL';
    $max_rating_9 = isset($_POST['max_rating_9']) && !empty($_POST['max_rating_9']) ? $_POST['max_rating_9'] : 'NULL';
    $max_rating_10 = isset($_POST['max_rating_10']) && !empty($_POST['max_rating_10']) ? $_POST['max_rating_10'] : 'NULL';
    $max_rating_11 = isset($_POST['max_rating_11']) && !empty($_POST['max_rating_11']) ? $_POST['max_rating_11'] : 'NULL';
    $max_rating_12 = isset($_POST['max_rating_12']) && !empty($_POST['max_rating_12']) ? $_POST['max_rating_12'] : 'NULL';
    $max_rating_13 = isset($_POST['max_rating_13']) && !empty($_POST['max_rating_13']) ? $_POST['max_rating_13'] : 'NULL';
    $max_rating_14 = isset($_POST['max_rating_14']) && !empty($_POST['max_rating_14']) ? $_POST['max_rating_14'] : 'NULL';
    $max_rating_15 = isset($_POST['max_rating_15']) && !empty($_POST['max_rating_15']) ? $_POST['max_rating_15'] : 'NULL';
    $max_rating_16 = isset($_POST['max_rating_16']) && !empty($_POST['max_rating_16']) ? $_POST['max_rating_16'] : 'NULL';
    $max_rating_17 = isset($_POST['max_rating_17']) && !empty($_POST['max_rating_17']) ? $_POST['max_rating_17'] : 'NULL';
    $max_rating_18 = isset($_POST['max_rating_18']) && !empty($_POST['max_rating_18']) ? $_POST['max_rating_18'] : 'NULL';
    $max_rating_19 = isset($_POST['max_rating_19']) && !empty($_POST['max_rating_19']) ? $_POST['max_rating_19'] : 'NULL';
    $max_rating_20 = isset($_POST['max_rating_20']) && !empty($_POST['max_rating_20']) ? $_POST['max_rating_20'] : 'NULL';

    $goalsheet_created_by = $associatenumber;
    $goalsheet_created_on = date('Y-m-d H:i:s');


    $appraisee_response = "INSERT INTO appraisee_response (goalsheetid,
        appraisee_associatenumber, 
        manager_associatenumber, 
        reviewer_associatenumber, 
        role, 
        appraisaltype, 
        appraisalyear,
        parameter_1, 
        expectation_1, 
        max_rating_1, 
        parameter_2, 
        expectation_2, 
        max_rating_2, 
        parameter_3, 
        expectation_3, 
        max_rating_3,  
        parameter_4, 
        expectation_4, 
        max_rating_4,  
        parameter_5, 
        expectation_5, 
        max_rating_5,  
        parameter_6, 
        expectation_6, 
        max_rating_6,
        parameter_7, 
        expectation_7, 
        max_rating_7,  
        parameter_8, 
        expectation_8, 
        max_rating_8,  
        parameter_9, 
        expectation_9, 
        max_rating_9,  
        parameter_10, 
        expectation_10, 
        max_rating_10,  
        parameter_11, 
        expectation_11, 
        max_rating_11,  
        parameter_12, 
        expectation_12, 
        max_rating_12,  
        parameter_13, 
        expectation_13, 
        max_rating_13,  
        parameter_14, 
        expectation_14, 
        max_rating_14,  
        parameter_15, 
        expectation_15, 
        max_rating_15,  
        parameter_16, 
        expectation_16, 
        max_rating_16,
        parameter_17, 
        expectation_17, 
        max_rating_17,
        parameter_18, 
        expectation_18, 
        max_rating_18,
        parameter_19, 
        expectation_19, 
        max_rating_19,
        parameter_20, 
        expectation_20, 
        max_rating_20,
        goalsheet_created_by,
        goalsheet_created_on
      ) VALUES (
        '$goalsheetid','$appraisee_associatenumber','$manager_associatenumber','$reviewer_associatenumber','$role','$appraisaltype','$appraisalyear','$parameter_1','$expectation_1',$max_rating_1,'$parameter_2','$expectation_2',$max_rating_2,'$parameter_3','$expectation_3',$max_rating_3,'$parameter_4','$expectation_4',$max_rating_4,'$parameter_5','$expectation_5',$max_rating_5,'$parameter_6','$expectation_6',$max_rating_6,'$parameter_7','$expectation_7',$max_rating_7,'$parameter_8','$expectation_8',$max_rating_8,'$parameter_9','$expectation_9',$max_rating_9,'$parameter_10','$expectation_10',$max_rating_10,'$parameter_11','$expectation_11',$max_rating_11,'$parameter_12','$expectation_12',$max_rating_12,'$parameter_13','$expectation_13',$max_rating_13,'$parameter_14','$expectation_14',$max_rating_14,'$parameter_15','$expectation_15',$max_rating_15,'$parameter_16','$expectation_16',$max_rating_16,'$parameter_17','$expectation_17',$max_rating_17,'$parameter_18','$expectation_18',$max_rating_18,'$parameter_19','$expectation_19',$max_rating_19,'$parameter_20','$expectation_20',$max_rating_20,'$goalsheet_created_by','$goalsheet_created_on')";

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

    <div class="container mt-5">
        <form method="GET" action="">
            <div class="form-group mb-3">
                <label for="role_search" class="form-label">Role:</label>
                <select class="form-select" name="role_search" required>

                    <?php if (@$_GET['role_search'] == null) { ?>
                        <option value="" selected>--Select Role--</option>
                    <?php
                    } else { ?>
                        <option value="" selected>--Select Role--</option>
                        <option hidden selected><?php echo @$_GET['role_search'] ?></option>
                    <?php }
                    ?>
                    <option value="Teacher">Teacher</option>
                    <option value="Administrator">Administrator</option>
                    <option value="Counselor">Counselor</option>
                    <option value="Support Staff">Support Staff</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <input type="submit" name="submit" value="Search">
            <!-- <button type='button'>Lock / Unlock Form</button> -->
        </form>
        <br>
        <h2 class="text-center mb-4" style="background-color:#CE1212; color:white; padding:10px;">Goal Setting Form</h2>


        <p>Unique Id: WB/2021/0282726 (NGO Darpan, NITI Aayog, Government of India)</p>
        <p></p>
        <hr>
        <?php if (sizeof($resultArr) > 0) { ?>
            <?php
            foreach ($resultArr as $array) {
            ?>
                <form method="post" name="process" id="process" action="process.php" onsubmit="return checkAssociateNumbers()">

                    <input type="hidden" name="form-type" value="appraisee_response">

                    <div class="form-group mb-3">
                        <label for="appraisee_associate_number" class="form-label">Appraisee Associate Number:</label>
                        <input type="text" class="form-control" name="appraisee_associate_number" required>
                        <div id="appraisee_associate_number_help" class="form-text">Please enter the unique associate number of the appraisee.</div>
                    </div>

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

                    <div class="form-group mb-3">
                        <label for="role" class="form-label">Role:</label>
                        <select class="form-select" name="role" required>
                            <option value="" disabled selected>--Select Role--</option>
                            <option value="Teacher">Teacher</option>
                            <option value="Administrator">Administrator</option>
                            <option value="Counselor">Counselor</option>
                            <option value="Support Staff">Support Staff</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="appraisal_type" class="form-label">Appraisal Type:</label>
                        <select class="form-select" name="appraisal_type" required>
                            <option value="" disabled selected>--Select an option--</option>
                            <option value="Annual">Annual</option>
                            <option value="Quarterly">Quarterly</option>
                            <option value="Project End">Project End</option>
                        </select>
                    </div>


                    <div class="form-group mb-3">
                        <label for="appraisal_year" class="form-label">Appraisal Year:</label>
                        <select class="form-select" name="appraisal_year" id="appraisal_year" required>
                            <?php if ($appraisal_year == null) { ?>
                                <option value="" hidden selected>--Select Appraisal Year--</option>
                            <?php
                            } else { ?>
                                <option hidden selected><?php echo $appraisal_year ?></option>
                            <?php }
                            ?>
                        </select>
                    </div>

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
                    <button type="submit" class="btn btn-primary">Submit</button>
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

    <!-- Bootstrap JS -->

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/js/bootstrap.min.js"></script>

    <script>
        function checkAssociateNumbers() {
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


    <!-- <script>
        var form = document.getElementById('process'), // select form by ID
            btn1 = document.querySelectorAll('button')[0];

        btn1.addEventListener('click', lockForm);

        function lockForm() {
            if (form.classList.toggle('locked')) {
                // Form is now locked
                btn1.textContent = 'Unlock Form';
                [].slice.call(form.elements).forEach(function(item) {
                    item.disabled = true;
                });
            } else {
                // Form is now unlocked
                btn1.textContent = 'Lock Form';
                [].slice.call(form.elements).forEach(function(item) {
                    item.disabled = false;
                });
            }
        }
        // Lock the form when the page is loaded
        lockForm();
    </script> -->
    <script>
        $(document).ready(function() {
            $('input[required], select[required], textarea[required]').each(function() {
                $(this).closest('.form-group').find('label').append(' <span style="color: red">*</span>');
            });
        });
    </script>
</body>

</html>