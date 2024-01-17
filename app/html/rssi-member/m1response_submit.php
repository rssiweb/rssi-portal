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
?>
<?php

if (@$_POST['form-type'] === "manager_remarks_update") {
    $goalsheetid = $_POST['goalsheetid'];
    $appraisee_associatenumber = $_POST['appraisee_associatenumber'];
    $manager_associatenumber = $_POST['manager_associatenumber'];
    $manager1_associatenumber = $_POST['manager1_associatenumber'];
    $goalsheet_evaluated1_by = $associatenumber;
    $goalsheet_evaluated1_on = date('Y-m-d H:i:s');
    $manager_remarks = array();
    $rating_obtained = array();

    // Collect the manager remarks and rating obtained from $_POST and store them in arrays
    for ($i = 1; $i <= 20; $i++) {
        $manager_remarks[$i] = $_POST['manager_remarks_' . $i];
        $rating_obtained[$i] = isset($_POST['rating_obtained_' . $i]) ? (int)$_POST['rating_obtained_' . $i] : null;
    }

    // Prepare the SQL update query with placeholders for both manager_remarks and rating_obtained
    $sql = "UPDATE appraisee_response
            SET manager1_evaluation_complete = 'yes',
            goalsheet_evaluated1_by = '$goalsheet_evaluated1_by',
            goalsheet_evaluated1_on = '$goalsheet_evaluated1_on',";

    // Generate the SET clause of the query dynamically
    for ($i = 1; $i <= 20; $i++) {
        // Use the appropriate data type casting in the SQL statement
        $sql .= "manager_remarks_$i = $" . $i . "::text, ";
        // Use the same placeholder for rating_obtained values
        $sql .= "rating_obtained_$i = $" . ($i + 20) . "::integer";
        if ($i !== 20) {
            $sql .= ", ";
        }
    }

    // Calculate the average rating_obtained
    $rating_obtained_without_null = array_filter($rating_obtained, function ($value) {
        return $value !== null;
    });

    if (count($rating_obtained_without_null) > 0) {
        $average_rating = round(array_sum($rating_obtained_without_null) / count($rating_obtained_without_null), 2);
    } else {
        $average_rating = null;
    }

    // Append the average rating to the query
    $sql .= ", ipf = $" . ($i + 20) . "::numeric";

    // Add the WHERE clause to update only the rows with the specified goalsheetid
    $sql .= " WHERE goalsheetid = $" . ($i + 21); // The goalsheetid will be passed as the 41st parameter

    // Prepare the statement
    $stmt = pg_prepare($con, "update_query", $sql);

    // Merge the two arrays of manager_remarks and rating_obtained, and add the goalsheetid and average_rating to the parameters
    $params = array_merge($manager_remarks, $rating_obtained, array($average_rating, $goalsheetid));

    // Execute the prepared statement with the parameters
    $result = pg_execute($con, "update_query", $params);

    if ($result !== false) {
        $cmdtuples = pg_affected_rows($result);
    } else {
        // In case of an error, you might want to log or handle it appropriately
        echo "Error while executing the update query: " . pg_last_error($con);
        $cmdtuples = 0; // Set cmdtuples to 0 to indicate an error
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
    @$appraisee_associatenumber = pg_fetch_result($result_appraisal_details, 0, 2);

    if (@$cmdtuples == 1 && $manager_email != "") {
        sendEmail("goal_sheet_immediate_manager_evaluation_complete", array(
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
    <title>Manager Response</title>
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
            <a href="manager_response.php?goalsheetid=<?php echo $goalsheetid ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

<?php } else if (@$cmdtuples > 0) { ?>

    <div class="container">
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Goal Sheet has been submitted successfully!</h4>
            <hr>
            <p>Your goal sheet has been submitted. The unique goal sheet ID is <?php echo $goalsheetid ?>.</p>
            <a href="manager_response.php?goalsheetid=<?php echo $goalsheetid ?>" class="btn btn-primary">Back to Goal Sheet</a>
        </div>
    </div>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
<?php } ?>