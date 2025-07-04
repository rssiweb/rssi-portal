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
@$goalsheetid = $_GET['goalsheetid'];
// Query database for student information based on ID

$result = pg_query($con, "SELECT * FROM appraisee_response WHERE goalsheetid = '$goalsheetid'");
$resultArr = pg_fetch_all($result);

$result_a = pg_query($con, "SELECT fullname,email
FROM appraisee_response
LEFT JOIN rssimyaccount_members ON rssimyaccount_members.associatenumber = appraisee_response.appraisee_associatenumber WHERE goalsheetid = '$goalsheetid'");
@$appraisee_name = pg_fetch_result($result_a, 0, 0);
@$appraisee_email = pg_fetch_result($result_a, 0, 1);

$result_m1 = pg_query($con, "SELECT fullname, email
FROM appraisee_response
LEFT JOIN rssimyaccount_members ON rssimyaccount_members.associatenumber = appraisee_response.manager1_associatenumber WHERE goalsheetid = '$goalsheetid'");
@$manager1_name = pg_fetch_result($result_m1, 0, 0);
@$manager1_email = pg_fetch_result($result_m1, 0, 1);

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
    <title>Appraisee Response</title>
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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

        .resizable-table th {
            position: relative;
            overflow: hidden;
        }

        .resizable-table th:after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 10px;
            /* Adjust the handle width as needed */
            cursor: col-resize;
            background-color: #f4f4f4;
        }

        .resizable-table th:last-child:after {
            content: none;
            /* Hide handle for the last column */
        }

        .back-to-top {
            position: fixed;
            visibility: hidden;
            opacity: 0;
            right: 15px;
            bottom: 15px;
            z-index: 99999;
            background: #4154f1;
            width: 40px;
            height: 40px;
            border-radius: 4px;
            transition: all 0.4s;
        }

        .back-to-top i {
            font-size: 24px;
            color: #fff;
            line-height: 0;
        }

        .back-to-top:hover {
            background: #6776f4;
            color: #fff;
        }

        .back-to-top.active {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>

<body>
    <?php include 'inactive_session_expire_check.php'; ?>
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
                <?php echo $greeting ?>,
                <?php echo $fullname ?> (
                <?php echo $associatenumber ?>)!
            </p>
        </div>
        <form method="GET" action="" id="searchForm">
            <div class="form-group mb-3">
                <label for="goalsheetid" class="form-label">Goal sheet ID:</label>
                <input type="text" class="form-control" name="goalsheetid" Value="<?php echo @$_GET['goalsheetid'] ?>" placeholder="Goal sheet ID">
            </div>
            <button type="submit" name="submit" class="btn btn-primary mb-3" id="searchBtn">
                <span id="btnText">Search</span>
                <span id="loadingIndicator" style="display: none;">Loading...</span>
            </button>
        </form>
        <br>
        <h2 class="text-center mb-4" style="background-color:#CE1212; color:white; padding:10px;">Associate Appraisal
            Form</h2>

        <?php if (sizeof($resultArr) > 0) { ?>
            <?php foreach ($resultArr as $array) { ?>
                <div class="row">
                    <div class="col-md-6">
                        <p>Unique Id: WB/2021/0282726 (NGO Darpan, NITI Aayog, Government of India)</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <?php
                        // Check if appraisee_response_complete is 'yes', manager_evaluation_complete is null, ipf_response is null, and unlock_request is null
                        $enableButton = ($array['appraisee_response_complete'] === 'yes' &&
                            $array['manager_evaluation_complete'] === null &&
                            $array['ipf_response'] === null &&
                            $array['unlock_request'] === null);

                        // Check if unlock_request is not null
                        if ($array['unlock_request'] !== null) {
                            // Update button text based on unlock_request value
                            $buttonText = ($array['unlock_request'] === 'yes') ? 'Unlock Request Sent' : 'Error Sending Request';
                        } else {
                            // Use default button text if unlock_request is null
                            $buttonText = 'Request Unlock';
                        }

                        // Check if manager_unlocked value is 'yes'
                        if ($array['manager_unlocked'] === 'yes') {
                            // Update button text to indicate goal sheet is unlocked
                            $buttonText = 'Goalsheet Unlocked';
                        }

                        echo '<form name="unlock_request" action="#" method="POST" style="display: -webkit-inline-box;">
                            <input type="hidden" name="form-type" value="unlock_request">
                            <input type="hidden" name="goalsheetid" value="' . $array['goalsheetid'] . '">             
                            <button type="submit" id="submit3" class="btn btn-sm btn-warning" ' . ($enableButton ? '' : 'disabled') . '>
                                ' . $buttonText . '
                            </button>
                        </form>';
                        ?>
                    </div>
                </div>
                <hr>
                <?php if ($array['appraisee_associatenumber'] == $associatenumber || ($role == 'Admin' && $filterstatus == 'Active')) { ?>
                    <form method="post" name="a_response" id="a_response">

                        <fieldset <?php echo (($array['appraisee_response_complete'] == "yes" || $array['ipf_process_closed_on'] != null) || $filterstatus != 'Active') ? "disabled" : ""; ?>>

                            <?php
                            $status = '';

                            if ($array['appraisee_response_complete'] == "" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "" && $array['ipf_process_closed_on'] == null) {
                                $status = 'Self-assessment';
                            } elseif ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "" && $array['ipf_process_closed_on'] == null) {
                                $status = 'Manager assessment in progress';
                            } elseif ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "" && $array['ipf_process_closed_on'] == null) {
                                $status = 'Reviewer assessment in progress';
                            } elseif ($array['appraisee_response_complete'] == "yes" && $array['manager_evaluation_complete'] == "yes" && $array['reviewer_response_complete'] == "yes" && $array['ipf_response'] == null) {
                                $status = 'IPF released';
                            } elseif ($array['ipf_response'] == 'accepted') {
                                $status = 'IPF Accepted';
                            } elseif ($array['ipf_response'] == 'rejected') {
                                $status = 'IPF Rejected';
                            } elseif (($array['appraisee_response_complete'] == "" || $array['appraisee_response_complete'] == "yes") && $array['manager_evaluation_complete'] == "" && $array['reviewer_response_complete'] == "" && $array['ipf_process_closed_on'] != null) {
                                $status = 'Incomplete';
                            }

                            $badge_color = '';
                            switch ($status) {
                                case 'IPF Rejected':
                                case 'Self-assessment':
                                    $badge_color = 'danger';
                                    break;
                                case 'IPF Accepted':
                                case 'IPF released':
                                    $badge_color = 'success';
                                    break;
                                case 'Manager assessment in progress':
                                    $badge_color = 'warning';
                                    break;
                                case 'Reviewer assessment in progress':
                                    $badge_color = 'primary';
                                    break;
                                default:
                                    $badge_color = 'secondary';
                                    break;
                            }

                            echo '<span class="float-end badge bg-' . $badge_color . ' text-start">' . $status . '</span>';
                            ?>

                            <input type="hidden" name="form-type" value="appraisee_response_update">
                            <input type="hidden" name="goalsheetid" Value="<?php echo $array['goalsheetid'] ?>" readonly>
                            <input type="hidden" name="manager_associatenumber" Value="<?php echo $array['manager_associatenumber'] ?>" readonly>
                            <input type="hidden" name="manager1_associatenumber" Value="<?php echo $array['manager1_associatenumber'] ?>" readonly>
                            <input type="hidden" name="appraisee_associatenumber" Value="<?php echo $array['appraisee_associatenumber'] ?>" readonly>
                            <table class="table">
                                <tr>
                                    <td>
                                        <label for="appraisee_associate_number" class="form-label">Appraisee:</label>
                                        &nbsp;
                                        <?php echo $appraisee_name ?> (
                                        <?php echo $array['appraisee_associatenumber'] ?>)
                                    </td>
                                    <td>
                                        <label for="role" class="form-label">Role:</label>
                                        &nbsp;
                                        <?php echo $array['role'] ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php if (!empty($manager1_name)) : ?>
                                            <label for="manager1_associate_number" class="form-label">Immediate Manager:</label>
                                            &nbsp;
                                            <?php echo $manager1_name ?> (
                                            <?php echo $array['manager1_associatenumber'] ?>)<br>
                                        <?php endif; ?>

                                        <label for="manager_associate_number" class="form-label">Manager:</label>
                                        &nbsp;
                                        <?php echo $manager_name ?> (
                                        <?php echo $array['manager_associatenumber'] ?>)
                                    </td>
                                    <td>
                                        <label for="appraisal_type" class="form-label">Appraisal Type:</label>
                                        <?php $effectiveStartDate = isset($array['effective_start_date']) ? date("d/m/Y", strtotime($array['effective_start_date'])) : null;
                                        $effectiveEndDate = isset($array['effective_end_date']) ? date("d/m/Y", strtotime($array['effective_end_date'])) : null; ?>
                                        <?php echo ($effectiveStartDate ? $effectiveStartDate : '') . ($effectiveStartDate && $effectiveEndDate ? ' - ' : '') . ($effectiveEndDate ? $effectiveEndDate : ''); ?>
                                        <?php echo $array['appraisaltype'] ?>&nbsp;
                                        <?php echo $array['appraisalyear'] ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>
                                        <label for="reviewer_associate_number" class="form-label">Reviewer:</label>
                                        &nbsp;
                                        <?php echo $reviewer_name ?> (
                                        <?php echo $array['reviewer_associatenumber'] ?>)
                                    </td>
                                    <td>
                                        <label for="appraisal_year" class="form-label">IPF:</label>
                                        <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['ipf'] : "" ?>
                                    </td>
                                    </td>
                                </tr>
                            </table>
                            <h2>Goals</h2>
                            <p>Scoping & planning (Operational efficiency, Individual contribution, Gearing up for future,
                                Student centricity, Audits & Compliance)</p>
                            <?php echo ($array['reviewer_response_complete'] == "yes") ? "<p>Rating Scale: 5- Very Satisfied, 4- Satisfied, 3- Neutral, 2- Unsatisfied, 1- Very Unsatisfied</p>" : "" ?>
                            <table class="table table-bordered resizable-table">
                                <thead>
                                    <tr>
                                        <th scope="col" id="cw">Parameter</th>
                                        <th scope="col" id="cw">Expectation</th>
                                        <th scope="col">Appraisee Response</th>
                                        <th scope="col">Rating Obtained</th>
                                        <th scope="col">Manager Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_1'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_1'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_1" id="appraisee_response_1"><?php echo $array['appraisee_response_1'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_1'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_1'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_2'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_2'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_2" id="appraisee_response_2"><?php echo $array['appraisee_response_2'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_2'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_2'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_3'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_3'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_3" id="appraisee_response_3"><?php echo $array['appraisee_response_3'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_3'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_3'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_4'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_4'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_4" id="appraisee_response_4"><?php echo $array['appraisee_response_4'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_4'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_4'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_5'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_5'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_5" id="appraisee_response_5"><?php echo $array['appraisee_response_5'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_5'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_5'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_6'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_6'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_6" id="appraisee_response_6"><?php echo $array['appraisee_response_6'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_6'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_6'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_7'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_7'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_7" id="appraisee_response_7"><?php echo $array['appraisee_response_7'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_7'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_7'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_8'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_8'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_8" id="appraisee_response_8"><?php echo $array['appraisee_response_8'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_8'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_8'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_9'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_9'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_9" id="appraisee_response_9"><?php echo $array['appraisee_response_9'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_9'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_9'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_10'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_10'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_10" id="appraisee_response_10"><?php echo $array['appraisee_response_10'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_10'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_10'] : "" ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <p>*SLA - Service level agreement, KPI - Key performance indicator</p>
                            <h2>Attributes</h2>
                            <p>Attributes are competencies essential for performing a role.</p>
                            <table class="table table-bordered resizable-table">
                                <thead>
                                    <tr>
                                        <th scope="col" id="cw">Parameter</th>
                                        <th scope="col" id="cw1">Expectation</th>
                                        <th scope="col">Appraisee Response</th>
                                        <th scope="col">Rating Obtained</th>
                                        <th scope="col">Manager Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_11'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_11'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_11" id="appraisee_response_11"><?php echo $array['appraisee_response_11'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_11'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_11'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_12'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_12'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_12" id="appraisee_response_12"><?php echo $array['appraisee_response_12'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_12'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_12'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_13'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_13'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_13" id="appraisee_response_13"><?php echo $array['appraisee_response_13'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_13'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_13'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_14'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_14'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_14" id="appraisee_response_14"><?php echo $array['appraisee_response_14'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_14'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_14'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_15'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_15'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_15" id="appraisee_response_15"><?php echo $array['appraisee_response_15'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_15'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_15'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_16'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_16'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_16" id="appraisee_response_16"><?php echo $array['appraisee_response_16'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_16'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_16'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_17'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_17'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_17" id="appraisee_response_17"><?php echo $array['appraisee_response_17'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_17'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_17'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_18'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_18'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_18" id="appraisee_response_18"><?php echo $array['appraisee_response_18'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_18'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_18'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_19'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_19'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_19" id="appraisee_response_19"><?php echo $array['appraisee_response_19'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_19'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_19'] : "" ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?php echo $array['parameter_20'] ?>
                                        </td>
                                        <td>
                                            <?php echo $array['expectation_20'] ?>
                                        </td>
                                        <td><textarea name="appraisee_response_20" id="appraisee_response_20"><?php echo $array['appraisee_response_20'] ?></textarea>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['rating_obtained_20'] : "" ?>
                                        </td>
                                        <td>
                                            <?php echo ($array['reviewer_response_complete'] == "yes") ? $array['manager_remarks_20'] : "" ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="submit" id="submit1" class="btn btn-success">Save</button>
                            <?php
                            // Get the effective end date from your array
                            $effectiveEndDate = $array['effective_end_date'];

                            // Calculate the current date
                            $currentDate = date('Y-m-d');

                            // Calculate the date 30 days before the effective end date
                            $enableDate = date('Y-m-d', strtotime($effectiveEndDate . ' -30 days'));

                            // Output the values for debugging
                            // echo "Effective End Date: " . $effectiveEndDate . "<br>";
                            // echo "Current Date: " . $currentDate . "<br>";
                            // echo "Enable Date: " . $enableDate . "<br>";
                            ?>

                            <!-- Your HTML content here -->

                            <button type="submit" id="submit2" class="btn btn-primary">Submit</button>
                            <br><br>
                            <?php if ("<?php echo $currentDate; ?>" < "<?php echo $enableDate; ?>") { ?>
                                <p>Goal Sheet Submission Opens Soon! To know more details <a href="#" data-bs-toggle="modal" data-bs-target="#popup">click here</a></p>
                                <!-- Popup -->
                                <div class="modal fade" id="popup" tabindex="-1" aria-labelledby="popupLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <!-- Header -->
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="popupLabel">Goal Sheet Submission Opens Soon!</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <!-- Body -->
                                            <div class="modal-body">
                                                <p>You can submit your goal sheets starting <b>
                                                        <?php echo date("F j, Y", strtotime($enableDate)) ?>
                                                    </b>.</p>
                                                <p>To ensure a complete and timely submission, we encourage you to proactively
                                                    maintain and update your goal sheet regularly. Don't let your hard work go
                                                    unnoticed!</p>

                                            </div>
                                            <!-- Footer -->
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
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
                                                <?php echo ($array['goalsheet_evaluated1_on'] !== null) ? date('d/m/y h:i:s a', strtotime($array['goalsheet_evaluated1_on'])) . ' by ' . $array['goalsheet_evaluated1_by'] : '' ?>
                                                <?php echo ($array['goalsheet_evaluated_on'] !== null) ? date('d/m/y h:i:s a', strtotime($array['goalsheet_evaluated_on'])) . ' by ' . $array['goalsheet_evaluated_by'] : '' ?>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="list-group-item">
                                        <div class="row">
                                            <div class="col-6">Process Closed On:</div>
                                            <div class="col-6">
                                                <?php echo ($array['goalsheet_reviewed_on'] !== null) ? date('d/m/y h:i:s a', strtotime($array['goalsheet_reviewed_on'])) . ' by ' . $array['goalsheet_reviewed_by'] : '' ?>
                                                <?php echo ($array['ipf_process_closed_on'] !== null) ? date('d/m/y h:i:s a', strtotime($array['ipf_process_closed_on'])) . ' by ' . $array['ipf_process_closed_by'] : '' ?>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <br><br>
                        </fieldset>
                    </form>
                <?php } ?>
            <?php } ?>
            <?php if ($array['appraisee_associatenumber'] != $associatenumber && ($role != 'Admin' || $filterstatus != 'Active')) { ?>
                <p>Oops! It looks like you're trying to access a goal sheet that doesn't belong to you.</p>
            <?php } ?>
        <?php
        } else if ($goalsheetid == null) {
        ?>
            <p>Please enter the Goal sheet ID.</p>
        <?php
        } else { ?>
            <p>We could not find any records matching the entered Goal sheet ID.</p>
        <?php } ?>
        <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
    </div>

    <!-- Bootstrap JS -->

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/js/bootstrap.min.js"></script>
    <!-- Template Main JS File -->
    <script src="../assets_new/js/main.js"></script>
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
    <!-- Add this script in your HTML file -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the submit button element
            var submitButton = document.getElementById('submit2');

            // Check if the current date is on or after 30 days before the effective end date
            if ("<?php echo $currentDate; ?>" >= "<?php echo $enableDate; ?>") {
                // Enable the submit button
                submitButton.disabled = false;
            } else {
                // Disable the submit button
                submitButton.disabled = true;
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let isResizing = false;
            let currentTh = null;
            let startX = 0;
            let startWidth = 0;

            document.addEventListener('mousemove', function(e) {
                if (!isResizing) return;

                const mouseX = e.clientX;
                const movementX = mouseX - startX;
                const newWidth = startWidth + movementX;

                currentTh.style.width = newWidth + 'px';
                requestAnimationFrame(() => {
                    updateTableWidth();
                });
            });

            document.addEventListener('mouseup', function() {
                isResizing = false;
                currentTh = null;
            });

            const headers = document.querySelectorAll('.resizable-table th');

            headers.forEach(th => {
                th.addEventListener('mousedown', function(e) {
                    isResizing = true;
                    currentTh = th;
                    startX = e.clientX;
                    startWidth = currentTh.offsetWidth;
                });
            });

            function updateTableWidth() {
                const table = document.querySelector('.resizable-table');
                const thArray = Array.from(table.querySelectorAll('th'));
                const colWidths = thArray.map(th => th.offsetWidth);
                thArray.forEach((th, index) => {
                    th.style.width = colWidths[index] + 'px';
                });
            }
        });
    </script>
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
        var form = document.getElementById('a_response');
        var submit1Button = document.getElementById('submit1');
        var submit2Button = document.getElementById('submit2');

        // Handle Save button
        submit1Button.addEventListener('click', function() {
            form.action = 'aresponse_save.php';
            form.submit(); // optional if you're not using <button type="submit">
        });

        // Handle Submit button with confirmation
        submit2Button.addEventListener('click', function(e) {
            e.preventDefault(); // prevent default form submission

            var confirmSubmit = confirm("Are you sure you want to submit the Goalsheet?\n\nPlease note:\n- Once submitted, you will not be able to modify it again.\n- If you wish to save your data without submitting, click 'Save'.\n\nClick 'OK' to submit or 'Cancel' to go back.");

            if (confirmSubmit) {
                form.action = 'aresponse_submit.php';
                form.submit(); // manually submit if user confirms
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            $('input[required], select[required], textarea[required]').each(function() {
                $(this).closest('.form-group').find('label').append(' <span style="color: red">*</span>');
            });
        });
    </script>
    <script>
        const scriptURL = 'payment-api.php';

        // Add an event listener to the submit button with id "submit_gen_otp_associate"
        document.getElementById("submit3").addEventListener("click", function(event) {
            event.preventDefault(); // prevent default form submission

            if (confirm('Are you sure you want to request the unlock of the Goalsheet?')) {
                fetch(scriptURL, {
                        method: 'POST',
                        body: new FormData(document.forms['unlock_request'])
                    })
                    .then(response => response.text())
                    .then(result => {
                        if (result == 'success') {
                            alert("The unlock request has been successfully submitted.")
                            location.reload()
                        } else {
                            alert("Failed to send unlock request. Please try again later or contact support.")
                        }
                    })
            } else {
                alert("Goalsheet unlock request cancelled.");
                return false;
            }
        })
    </script>
</body>

</html>