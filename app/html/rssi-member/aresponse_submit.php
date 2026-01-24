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

if (@$_POST['form-type'] === "appraisee_response_update") {
    $goalsheetid = $_POST['goalsheetid'];
    $appraisee_associatenumber = $_POST['appraisee_associatenumber'];
    $manager_associatenumber = $_POST['manager_associatenumber'];
    $manager1_associatenumber = $_POST['manager1_associatenumber'];
    $goalsheet_submitted_by = $associatenumber;
    $goalsheet_submitted_on = date('Y-m-d H:i:s');

    // Initialize an array to store appraisee responses
    $appraisee_responses = array();

    // Collect the appraisee responses from $_POST and store them in the array
    for ($i = 1; $i <= 20; $i++) {
        $appraisee_responses[$i] = $_POST['appraisee_response_' . $i] ?? null;
    }

    // Prepare the SQL update query with placeholders
    $sql = "UPDATE appraisee_response 
            SET appraisee_response_complete = 'yes', 
            goalsheet_submitted_by = '$goalsheet_submitted_by',
            goalsheet_submitted_on = '$goalsheet_submitted_on',";

    // Generate the SET clause of the query dynamically
    for ($i = 1; $i <= 20; $i++) {
        $sql .= "appraisee_response_$i = $" . $i;
        if ($i !== 20) {
            $sql .= ", ";
        }
    }

    $sql .= " WHERE goalsheetid = $21";

    // Prepare the statement
    $stmt = pg_prepare($con, "update_query", $sql);

    // Build the parameters array
    $params = array_merge($appraisee_responses, array($goalsheetid));

    // Execute the prepared statement with the parameters
    $result = pg_execute($con, "update_query", $params);

    if ($result) {
        $cmdtuples = pg_affected_rows($result);
    } else {
        echo "Error while executing the update query: " . pg_last_error($con);
    }

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

    $result_m = pg_query($con, "SELECT fullname, email
FROM appraisee_response
LEFT JOIN rssimyaccount_members ON rssimyaccount_members.associatenumber = appraisee_response.manager1_associatenumber WHERE goalsheetid = '$goalsheetid'");
    @$manager1_name = pg_fetch_result($result_m, 0, 0);
    @$manager1_email = pg_fetch_result($result_m, 0, 1);


    $result_r = pg_query($con, "SELECT fullname, email
FROM appraisee_response
LEFT JOIN rssimyaccount_members ON rssimyaccount_members.associatenumber = appraisee_response.reviewer_associatenumber WHERE goalsheetid = '$goalsheetid'");
    @$reviewer_name = pg_fetch_result($result_r, 0, 0);
    @$reviewer_email = pg_fetch_result($result_r, 0, 1);


    $result_appraisal_details = pg_query($con, "SELECT appraisaltype, appraisalyear, appraisee_associatenumber FROM appraisee_response WHERE goalsheetid = '$goalsheetid'");
    @$appraisaltype = pg_fetch_result($result_appraisal_details, 0, 0);
    @$appraisalyear = pg_fetch_result($result_appraisal_details, 0, 1);
    @$appraisee_associatenumber = pg_fetch_result($result_appraisal_details, 0, 2); ?>

<?php
    if (@$cmdtuples == 1 && $manager1_email != "") {
        sendEmail("goal_sheet_evaluation_request_manager1", array(
            "goalsheetid" => $goalsheetid,
            "appraisaltype" => @$appraisaltype,
            "appraisalyear" => @$appraisalyear,
            "appraisee_name" => @$appraisee_name,
            "appraiseeemail" => @$appraisee_email,
            "appraiseeid" => @$appraisee_associatenumber,
            "manager1_name" => @$manager1_name,
        ), $manager1_email);
    }
    if (@$cmdtuples == 1) {
        $emailTemplate = ($manager1_email != "") ? "goal_sheet_evaluation_request_cc" : "goal_sheet_evaluation_request_manager1";

        sendEmail($emailTemplate, array(
            "goalsheetid" => $goalsheetid,
            "appraisaltype" => @$appraisaltype,
            "appraisalyear" => @$appraisalyear,
            "appraisee_name" => @$appraisee_name,
            "appraiseeemail" => @$appraisee_email,
            "appraiseeid" => @$appraisee_associatenumber,
            "manager_name" => @$manager_name,
            "manager1_name" => @$manager1_name,
            "manager1_email" => @$manager1_email,
            "manager1_associatenumber" => @$manager1_associatenumber,
        ), $manager_email);
    }
} ?>

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
    <?php include 'includes/meta.php' ?>
    
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/x-icon" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/58c4cdb942.js" crossorigin="anonymous"></script>
</head>

<?php if (@$cmdtuples == 0) { ?>

    <div class="container">
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Error submitting Goal Sheet!</h4>
            <hr>
            <p>Unfortunately, there was an error submitting your goal sheet. Please try again later or contact support for assistance.</p>
            <a href="appraisee_response.php?goalsheetid=<?php echo $goalsheetid ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

<?php } else if (@$cmdtuples == 1) { ?>

    <div class="container">
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Goal Sheet has been submitted successfully!</h4>
            <hr>
            <p>Your goal sheet has been submitted. The unique goal sheet ID is <?php echo $goalsheetid ?>.</p>
            <a href="appraisee_response.php?goalsheetid=<?php echo $goalsheetid ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
<?php } ?>