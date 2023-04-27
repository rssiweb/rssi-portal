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

if ($password_updated_by == null || $password_updated_on < $default_pass_updated_on) {

    echo '<script type="text/javascript">';
    echo 'window.location.href = "defaultpasswordreset.php";';
    echo '</script>';
} ?>

<?php
// Retrieve student ID from form input
@$goalsheetid = $_GET['goalsheetid'];
// Query database for student information based on ID

$result = pg_query($con, "SELECT * FROM appraisee_response WHERE goalsheetid = '$goalsheetid'");
$resultArr = pg_fetch_all($result);

$result_a = pg_query($con, "SELECT fullname,email
FROM appraisee_response
LEFT JOIN rssimyaccount_members ON rssimyaccount_members.associatenumber = appraisee_response.appraisee_associatenumber WHERE goalsheetid = '$goalsheetid'");
@$appraisee_name = pg_fetch_result($result_a, 0, 0);
@$appraisee_email = pg_fetch_result($result_a, 0, 1);


$result_m = pg_query($con, "SELECT fullname, email
FROM appraisee_response
LEFT JOIN rssimyaccount_members ON rssimyaccount_members.associatenumber = appraisee_response.manager_associatenumber WHERE goalsheetid = '$goalsheetid'");
@$manager_name = pg_fetch_result($result_m, 0, 0);
@$manager_email = pg_fetch_result($result_m, 0, 1);


$result_r = pg_query($con, "SELECT fullname, email
FROM appraisee_response
LEFT JOIN rssimyaccount_members ON rssimyaccount_members.associatenumber = appraisee_response.reviewer_associatenumber WHERE goalsheetid = '$goalsheetid'");
@$reviewer_name = pg_fetch_result($result_r, 0, 0);
@$reviewer_email = pg_fetch_result($result_r, 0, 1);



if (!$result) {
    echo "An error occurred.\n";
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Reviewer Evaluation</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>

    <style>
        @media (max-width:767px) {

            #cw,
            #cw1 {
                width: 100% !important;
            }

        }

        #cw {
            width: 15%;
        }

        #cw1 {
            width: 30%;
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <?php
        // Get the current hour of the day
        $current_hour = date('G');

        // Define the greeting based on the current hour
        if ($current_hour < 12) {
            $greeting = "Good morning";
        } else if ($current_hour < 18) {
            $greeting = "Good afternoon";
        } else {
            $greeting = "Good evening";
        }
        ?>

        <div class="ribbon">
            <p class="ribbon-text" style="text-align: right;">
                <?php echo $greeting ?>, <?php echo $fullname ?> (<?php echo $associatenumber ?>)!
            </p>
        </div>
        <form method="GET" action="">
            <div class="form-group mb-3">
                <label for="goalsheetid" class="form-label">Goal sheet ID:</label>
                <input type="text" class="form-control" name="goalsheetid" Value="<?php echo @$_GET['goalsheetid'] ?>" placeholder="Goal sheet ID">
            </div>
            <input type="submit" name="submit" value="Search">
            <!-- <button type='button'>Lock / Unlock Form</button> -->
        </form>
        <br>
        <h2 class="text-center mb-4" style="background-color:#CE1212; color:white; padding:10px;">Reviewer Response</h2>


        <p>Unique Id: WB/2021/0282726 (NGO Darpan, NITI Aayog, Government of India)</p>
        <p></p>
        <hr>
        <?php if (sizeof($resultArr) > 0) { ?>
            <?php foreach ($resultArr as $array) { ?>
                <?php if ($array['reviewer_associatenumber'] == $associatenumber || $role == 'Admin') { ?>
                    <form method="post" name="r_response" id="r_response">

                        <input type="hidden" name="form-type" value="reviewer_remarks_update">
                        <input type="hidden" name="goalsheetid" Value="<?php echo $array['goalsheetid'] ?>" readonly>
                        <input type="hidden" name="appraisee_associatenumber" Value="<?php echo $array['appraisee_associatenumber'] ?>" readonly>
                        <input type="hidden" name="manager_associatenumber" Value="<?php echo $array['manager_associatenumber'] ?>" readonly>
                        <input type="hidden" name="reviewer_associatenumber" Value="<?php echo $array['reviewer_associatenumber'] ?>" readonly>

                        <fieldset disabled>


                            <?php if ($array['appraisee_response_complete'] == "" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "") { ?>
                                <span class="badge bg-danger float-end">Self-assessment</span>
                            <?php } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "") { ?>
                                <span class="badge bg-warning float-end">Manager assessment in progress</span>
                            <?php } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "") { ?>
                                <span class="badge bg-primary float-end">Reviewer assessment in progress</span>
                            <?php } else if ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "yes") { ?>
                                <span class="badge bg-success float-end">IPF released</span>

                            <?php } ?>

                            <table class="table">
                                <tr>
                                    <td>
                                        <label for="appraisee_associate_number" class="form-label">Appraisee:</label>
                                        &nbsp;<?php echo $appraisee_name ?> (<?php echo $array['appraisee_associatenumber'] ?>)
                                    </td>
                                    <td>
                                        <label for="role" class="form-label">Role:</label>
                                        &nbsp;<?php echo $array['role'] ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="manager_associate_number" class="form-label">Manager:</label>
                                        &nbsp;<?php echo $manager_name ?> (<?php echo $array['manager_associatenumber'] ?>)
                                    </td>
                                    <td>
                                        <label for="appraisal_type" class="form-label">Appraisal Type:</label>
                                        <?php echo $array['appraisaltype'] ?>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <label for="reviewer_associate_number" class="form-label">Reviewer:</label>
                                        &nbsp;<?php echo $reviewer_name ?> (<?php echo $array['reviewer_associatenumber'] ?>)
                                    </td>
                                    <td>
                                        <label for="appraisal_year" class="form-label">Appraisal Year:</label>
                                        <?php echo $array['appraisalyear'] ?>
                                    </td>
                                </tr>
                            </table>
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
                                        <th scope="col" id="cw">Parameter</th>
                                        <th scope="col" id="cw">Expectation</th>
                                        <th scope="col">Max Rating</th>
                                        <th scope="col">Appraisee Response</th>
                                        <th scope="col">Rating Obtained</th>
                                        <th scope="col">Manager Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php echo $array['parameter_1'] ?></td>
                                        <td><?php echo $array['expectation_1'] ?></td>
                                        <td><?php echo $array['max_rating_1'] ?></td>
                                        <td><textarea name="appraisee_response_1" id="appraisee_response_1" disabled><?php echo $array['appraisee_response_1'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_1" id="rating_obtained_1" class="form-select">

                                                <?php if ($array['rating_obtained_1'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_1'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>

                                        <td><textarea name="manager_remarks_1" id="manager_remarks_1"><?php echo $array['manager_remarks_1'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_2'] ?></td>
                                        <td><?php echo $array['expectation_2'] ?></td>
                                        <td><?php echo $array['max_rating_2'] ?></td>
                                        <td><textarea name="appraisee_response_2" id="appraisee_response_2" disabled><?php echo $array['appraisee_response_2'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_2" id="rating_obtained_2" class="form-select">

                                                <?php if ($array['rating_obtained_2'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_2'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_2" id="manager_remarks_2"><?php echo $array['manager_remarks_2'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_3'] ?></td>
                                        <td><?php echo $array['expectation_3'] ?></td>
                                        <td><?php echo $array['max_rating_3'] ?></td>
                                        <td><textarea name="appraisee_response_3" id="appraisee_response_3" disabled><?php echo $array['appraisee_response_3'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_3" id="rating_obtained_3" class="form-select">

                                                <?php if ($array['rating_obtained_3'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_3'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_3" id="manager_remarks_3"><?php echo $array['manager_remarks_3'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_4'] ?></td>
                                        <td><?php echo $array['expectation_4'] ?></td>
                                        <td><?php echo $array['max_rating_4'] ?></td>
                                        <td><textarea name="appraisee_response_4" id="appraisee_response_4" disabled><?php echo $array['appraisee_response_4'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_4" id="rating_obtained_4" class="form-select">

                                                <?php if ($array['rating_obtained_4'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_4'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_4" id="manager_remarks_4"><?php echo $array['manager_remarks_4'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_5'] ?></td>
                                        <td><?php echo $array['expectation_5'] ?></td>
                                        <td><?php echo $array['max_rating_5'] ?></td>
                                        <td><textarea name="appraisee_response_5" id="appraisee_response_5" disabled><?php echo $array['appraisee_response_5'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_5" id="rating_obtained_5" class="form-select">

                                                <?php if ($array['rating_obtained_5'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_5'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_5" id="manager_remarks_5"><?php echo $array['manager_remarks_5'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_6'] ?></td>
                                        <td><?php echo $array['expectation_6'] ?></td>
                                        <td><?php echo $array['max_rating_6'] ?></td>
                                        <td><textarea name="appraisee_response_6" id="appraisee_response_6" disabled><?php echo $array['appraisee_response_6'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_6" id="rating_obtained_6" class="form-select">

                                                <?php if ($array['rating_obtained_6'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_6'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_6" id="manager_remarks_6"><?php echo $array['manager_remarks_6'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_7'] ?></td>
                                        <td><?php echo $array['expectation_7'] ?></td>
                                        <td><?php echo $array['max_rating_7'] ?></td>
                                        <td><textarea name="appraisee_response_7" id="appraisee_response_7" disabled><?php echo $array['appraisee_response_7'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_7" id="rating_obtained_7" class="form-select">

                                                <?php if ($array['rating_obtained_7'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_7'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_7" id="manager_remarks_7"><?php echo $array['manager_remarks_7'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_8'] ?></td>
                                        <td><?php echo $array['expectation_8'] ?></td>
                                        <td><?php echo $array['max_rating_8'] ?></td>
                                        <td><textarea name="appraisee_response_8" id="appraisee_response_8" disabled><?php echo $array['appraisee_response_8'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_8" id="rating_obtained_8" class="form-select">

                                                <?php if ($array['rating_obtained_8'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_8'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_8" id="manager_remarks_8"><?php echo $array['manager_remarks_8'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_9'] ?></td>
                                        <td><?php echo $array['expectation_9'] ?></td>
                                        <td><?php echo $array['max_rating_9'] ?></td>
                                        <td><textarea name="appraisee_response_9" id="appraisee_response_9" disabled><?php echo $array['appraisee_response_9'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_9" id="rating_obtained_9" class="form-select">

                                                <?php if ($array['rating_obtained_9'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_9'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_9" id="manager_remarks_9"><?php echo $array['manager_remarks_9'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_10'] ?></td>
                                        <td><?php echo $array['expectation_10'] ?></td>
                                        <td><?php echo $array['max_rating_10'] ?></td>
                                        <td><textarea name="appraisee_response_10" id="appraisee_response_10" disabled><?php echo $array['appraisee_response_10'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_10" id="rating_obtained_10" class="form-select">

                                                <?php if ($array['rating_obtained_10'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_10'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_10" id="manager_remarks_10"><?php echo $array['manager_remarks_10'] ?></textarea></td>
                                    </tr>


                                </tbody>
                            </table>

                            <h2>Attributes</h2>
                            <p>Attributes are competencies essential for performing a role.</p>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th scope="col" id="cw">Parameter</th>
                                        <th scope="col" id="cw1">Expectation</th>
                                        <th scope="col">Max Rating</th>
                                        <th scope="col">Appraisee Response</th>
                                        <th scope="col">Rating Obtained</th>
                                        <th scope="col">Manager Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php echo $array['parameter_11'] ?></td>
                                        <td><?php echo $array['expectation_11'] ?></td>
                                        <td><?php echo $array['max_rating_11'] ?></td>
                                        <td><textarea name="appraisee_response_11" id="appraisee_response_11" disabled><?php echo $array['appraisee_response_11'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_11" id="rating_obtained_11" class="form-select">

                                                <?php if ($array['rating_obtained_11'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_11'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_11" id="manager_remarks_11"><?php echo $array['manager_remarks_11'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_12'] ?></td>
                                        <td><?php echo $array['expectation_12'] ?></td>
                                        <td><?php echo $array['max_rating_12'] ?></td>
                                        <td><textarea name="appraisee_response_12" id="appraisee_response_12" disabled><?php echo $array['appraisee_response_12'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_12" id="rating_obtained_12" class="form-select">

                                                <?php if ($array['rating_obtained_12'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_12'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_12" id="manager_remarks_12"><?php echo $array['manager_remarks_12'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_13'] ?></td>
                                        <td><?php echo $array['expectation_13'] ?></td>
                                        <td><?php echo $array['max_rating_13'] ?></td>
                                        <td><textarea name="appraisee_response_13" id="appraisee_response_13" disabled><?php echo $array['appraisee_response_13'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_13" id="rating_obtained_13" class="form-select">

                                                <?php if ($array['rating_obtained_13'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_13'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_13" id="manager_remarks_13"><?php echo $array['manager_remarks_13'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_14'] ?></td>
                                        <td><?php echo $array['expectation_14'] ?></td>
                                        <td><?php echo $array['max_rating_14'] ?></td>
                                        <td><textarea name="appraisee_response_14" id="appraisee_response_14" disabled><?php echo $array['appraisee_response_14'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_14" id="rating_obtained_14" class="form-select">

                                                <?php if ($array['rating_obtained_14'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_14'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_14" id="manager_remarks_14"><?php echo $array['manager_remarks_14'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_15'] ?></td>
                                        <td><?php echo $array['expectation_15'] ?></td>
                                        <td><?php echo $array['max_rating_15'] ?></td>
                                        <td><textarea name="appraisee_response_15" id="appraisee_response_15" disabled><?php echo $array['appraisee_response_15'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_15" id="rating_obtained_15" class="form-select">

                                                <?php if ($array['rating_obtained_15'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_15'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_15" id="manager_remarks_15"><?php echo $array['manager_remarks_15'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_16'] ?></td>
                                        <td><?php echo $array['expectation_16'] ?></td>
                                        <td><?php echo $array['max_rating_16'] ?></td>
                                        <td><textarea name="appraisee_response_16" id="appraisee_response_16" disabled><?php echo $array['appraisee_response_16'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_16" id="rating_obtained_16" class="form-select">

                                                <?php if ($array['rating_obtained_16'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_16'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_16" id="manager_remarks_16"><?php echo $array['manager_remarks_16'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_17'] ?></td>
                                        <td><?php echo $array['expectation_17'] ?></td>
                                        <td><?php echo $array['max_rating_17'] ?></td>
                                        <td><textarea name="appraisee_response_17" id="appraisee_response_17" disabled><?php echo $array['appraisee_response_17'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_17" id="rating_obtained_17" class="form-select">

                                                <?php if ($array['rating_obtained_17'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_17'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_17" id="manager_remarks_17"><?php echo $array['manager_remarks_17'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_18'] ?></td>
                                        <td><?php echo $array['expectation_18'] ?></td>
                                        <td><?php echo $array['max_rating_18'] ?></td>
                                        <td><textarea name="appraisee_response_18" id="appraisee_response_18" disabled><?php echo $array['appraisee_response_18'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_18" id="rating_obtained_18" class="form-select">

                                                <?php if ($array['rating_obtained_18'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_18'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_18" id="manager_remarks_18"><?php echo $array['manager_remarks_18'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_19'] ?></td>
                                        <td><?php echo $array['expectation_19'] ?></td>
                                        <td><?php echo $array['max_rating_19'] ?></td>
                                        <td><textarea name="appraisee_response_19" id="appraisee_response_19" disabled><?php echo $array['appraisee_response_19'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_19" id="rating_obtained_19" class="form-select">

                                                <?php if ($array['rating_obtained_19'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_19'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_19" id="manager_remarks_19"><?php echo $array['manager_remarks_19'] ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $array['parameter_20'] ?></td>
                                        <td><?php echo $array['expectation_20'] ?></td>
                                        <td><?php echo $array['max_rating_20'] ?></td>
                                        <td><textarea name="appraisee_response_20" id="appraisee_response_20" disabled><?php echo $array['appraisee_response_20'] ?></textarea></td>
                                        <td>
                                            <select name="rating_obtained_20" id="rating_obtained_20" class="form-select">

                                                <?php if ($array['rating_obtained_20'] == null) { ?>
                                                    <option value="" disabled selected hidden>Select</option>
                                                <?php
                                                } else { ?>
                                                    <option hidden selected><?php echo $array['rating_obtained_20'] ?></option>
                                                <?php }
                                                ?>
                                                <option>0</option>
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </td>
                                        <td><textarea name="manager_remarks_20" id="manager_remarks_20"><?php echo $array['manager_remarks_20'] ?></textarea></td>
                                    </tr>
                                </tbody>
                            </table>
                        </fieldset>
                        <fieldset <?php echo ($array['reviewer_response_complete'] == "yes") ? "disabled" : ""; ?>>
                            <div class="mb-3">
                                <label for="reviewer_remarks" class="form-label">Reviewer Remarks:</label>
                                <textarea class="form-control" id="reviewer_remarks" name="reviewer_remarks" rows="5" placeholder="Enter your remarks here"><?php echo $array['reviewer_remarks'] ?></textarea>
                                <div class="form-text">Please provide your feedback on the reviewed material</div>
                            </div>

                            <button type="submit" id="submit1" class="btn btn-warning">Unlock</button>
                            <button type="submit" id="submit2" class="btn btn-success">Approved</button>
                        </fieldset>
                        <br><br>
                        <hr>

                        <div class="card">
                            <div class="card-header">
                                Goal Sheet Status
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-6">Created On:</div>
                                        <div class="col-6">
                                            <?php echo ($array['goalsheet_created_on'] !== null) ? date('d/m/y h:i:s a', strtotime($array['goalsheet_created_on'])) . ' by ' . $array['goalsheet_created_by'] : '' ?>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-6">Submitted On:</div>
                                        <div class="col-6">
                                            <?php echo ($array['goalsheet_submitted_on'] !== null) ? date('d/m/y h:i:s a', strtotime($array['goalsheet_submitted_on'])) . ' by ' . $array['goalsheet_submitted_by'] : '' ?>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-6">Evaluated On:</div>
                                        <div class="col-6">
                                            <?php echo ($array['goalsheet_evaluated_on'] !== null) ? date('d/m/y h:i:s a', strtotime($array['goalsheet_evaluated_on'])) . ' by ' . $array['goalsheet_evaluated_by'] : '' ?>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        <div class="col-6">Process Closed On:</div>
                                        <div class="col-6">
                                            <?php echo ($array['goalsheet_reviewed_on'] !== null) ? date('d/m/y h:i:s a', strtotime($array['goalsheet_reviewed_on'])) . ' by ' . $array['goalsheet_reviewed_by'] : '' ?>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <br><br>
                    </form>
                <?php } ?>
            <?php } ?>
            <?php if ($array['reviewer_associatenumber'] != $associatenumber && $role != 'Admin') { ?><p>Oops! It looks like you're trying to access a goal sheet that doesn't belong to you.</p><?php } ?>
        <?php
        } else if ($goalsheetid == null) {
        ?>
            <p>Please enter the Goal sheet ID.</p>
        <?php
        } else {
        ?>
            <p>We could not find any records matching the entered Goal sheet ID.</p>
        <?php } ?>

    </div>

    <!-- Bootstrap JS -->

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/js/bootstrap.min.js"></script>
    <script>
        // Get all textarea elements in the form
        const textareas = document.querySelectorAll('form textarea');

        // Loop through each textarea element and add the "form-control" class
        textareas.forEach(textarea => {
            textarea.classList.add('form-control');
        });

        // Get all select elements in the form
        const selects = document.querySelectorAll('form select');

        // Loop through each select element and add the "form-select" class
        selects.forEach(select => {
            select.classList.add('form-select');
        });
    </script>

    <script>
        var form = document.getElementById('r_response');
        var submit1Button = document.getElementById('submit1');
        var submit2Button = document.getElementById('submit2');

        // Add event listeners to the submit buttons
        submit1Button.addEventListener('click', function() {
            form.action = 'rresponse_save.php'; // Set the form action to submit1.php
        });

        submit2Button.addEventListener('click', function() {
            form.action = 'rresponse_submit.php'; // Set the form action to submit2.php
        });
    </script>
    <script>
        $(document).ready(function() {
            $('input[required], select[required], textarea[required]').each(function() {
                $(this).closest('.form-group').find('label').append(' <span style="color: red">*</span>');
            });
        });
    </script>
</body>

</html>